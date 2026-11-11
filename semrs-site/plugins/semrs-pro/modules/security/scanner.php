<?php
/**
 * SEMRS Malware scanner.
 *
 * 1. WordPress core: every file compared with WordPress.org's official
 *    fingerprints (changed, missing and unknown files).
 * 2. Plugins from WordPress.org: every file compared with that version's
 *    official fingerprints (changed and added files).
 * 3. Everything that could not be verified (premium plugins, themes,
 *    uploads, the WordPress root, must-use plugins): checked for known
 *    malware and backdoor code, hidden scripts, PHP in uploads and
 *    dangerous .htaccess rules. Verified files are skipped, so legitimate
 *    plugins do not raise false alarms.
 * 4. The database: scripts injected into posts and settings, and new
 *    administrator accounts since the last scan.
 * 5. Health: WordPress, plugin and theme updates waiting, plugins removed
 *    from WordPress.org, very old plugins and risky settings.
 *
 * Runs in short steps (a few seconds each) so it never times out: from
 * the screen, or every day in the background. Changed core and plugin
 * files can be restored from WordPress.org in one click; suspicious files
 * are moved to a protected quarantine folder (never deleted) and can be put
 * back.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Protected working folder (file lists, quarantine).
 *
 * @return string Path with trailing slash, or '' when it cannot be made.
 */
function semrs_secpro_dir() {
	$name = (string) get_option( 'semrs_secpro_dir', '' );
	if ( '' === $name ) {
		$name = 'semrs-security-' . strtolower( wp_generate_password( 12, false ) );
		update_option( 'semrs_secpro_dir', $name, false );
	}
	$dir = trailingslashit( WP_CONTENT_DIR ) . $name . '/';
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return '';
	}
	if ( ! file_exists( $dir . '.htaccess' ) ) {
		file_put_contents( $dir . '.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $dir . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $dir . 'web.config', '<?xml version="1.0"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/**
 * Scanner settings.
 *
 * @return array
 */
function semrs_scan_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_secpro', array() ),
		array(
			'scan_daily'     => '1',
			'scan_email'     => '1',
			'scan_wordfence' => '',
		)
	);
}

/**
 * Folders never scanned by content (caches, backups, other security tools'
 * signature files, our own rules).
 *
 * @param string $rel Path relative to ABSPATH.
 * @return bool
 */
function semrs_scan_skip_path( $rel ) {
	$content = trim( str_replace( ABSPATH, '', trailingslashit( WP_CONTENT_DIR ) ), '/' );
	$skip    = array(
		$content . '/cache/',
		$content . '/upgrade/',
		$content . '/upgrade-temp-backup/',
		$content . '/updraft/',
		$content . '/ai1wm-backups/',
		$content . '/backups-dup-lite/',
		$content . '/wflogs/',
		$content . '/et-cache/',
		$content . '/' . get_option( 'semrs_secpro_dir', 'semrs-security-x' ) . '/',
		'/node_modules/',
		'/.git/',
		'semrs-pro/modules/security/',
	);
	foreach ( array( 'wordfence', 'sucuri-scanner', 'better-wp-security', 'ithemes-security-pro', 'all-in-one-wp-security-and-firewall', 'malcare-security', 'wp-cerber', 'ninjafirewall', 'defender-security', 'wpdefender', 'jetpack-protect', 'shield-security', 'anti-malware-security-and-brute-force-firewall', 'mainwp-child' ) as $p ) {
		$skip[] = '/plugins/' . $p . '/';
	}
	foreach ( $skip as $s ) {
		if ( false !== strpos( '/' . $rel, '/' . ltrim( $s, '/' ) ) || false !== strpos( '/' . $rel, $s ) ) {
			return true;
		}
	}
	return false;
}

/**
 * File extensions worth reading.
 *
 * @param string $rel Path.
 * @return string php|js|htaccess|ico|html|'' .
 */
function semrs_scan_kind( $rel ) {
	$b = strtolower( basename( $rel ) );
	if ( '.htaccess' === $b || '.user.ini' === $b || 'php.ini' === $b ) {
		return 'htaccess';
	}
	if ( preg_match( '/\.(?:php\d?|phtml|phar|pht|inc|suspected)$|\.php\.\w+$/', $b ) ) {
		return 'php';
	}
	if ( preg_match( '/\.(?:js|mjs)$/', $b ) ) {
		return 'js';
	}
	if ( preg_match( '/\.(?:ico)$/', $b ) ) {
		return 'ico';
	}
	if ( preg_match( '/\.(?:html?|svg)$/', $b ) ) {
		return 'html';
	}
	return '';
}

/**
 * Malware signatures: id => [severity, kind, label, pattern].
 *
 * @return array
 */
function semrs_scan_signatures() {
	$in = '\$_(?:GET|POST|REQUEST|COOKIE|SERVER|FILES)\b';
	return array(
		'eval_decode'  => array( 'critical', 'php', __( 'Runs hidden (encoded) code', 'semrs-pro' ), '/\b(?:eval|assert)\s*\(\s*@?\s*(?:base64_decode|gzinflate|gzuncompress|gzdecode|str_rot13|strrev|rawurldecode|hex2bin|convert_uudecode)\s*\(/i' ),
		'input_exec'   => array( 'critical', 'php', __( 'Runs code or commands sent by a visitor (backdoor)', 'semrs-pro' ), '/\b(?:eval|assert|system|exec|shell_exec|passthru|popen|proc_open|pcntl_exec|create_function)\s*\(\s*@?\s*(?:stripslashes\s*\(\s*|trim\s*\(\s*|base64_decode\s*\(\s*)?' . $in . '/i' ),
		'preg_e'       => array( 'critical', 'php', __( 'Runs code through a regular expression (/e)', 'semrs-pro' ), '/preg_replace\s*\(\s*([\'"])\/[^\'"]*\/[a-z]*e[a-z]*\1\s*,/i' ),
		'shell'        => array( 'critical', 'php', __( 'Known web shell or hack tool', 'semrs-pro' ), '/FilesMan|\bWSO\s?[0-9]\.[0-9]|c99shell|r57shell|\bb374k\b|IndoXploit|AnonymousFox|Alfa[_\s-]?(?:Team|Shell)|wp-vcd|marijuana\s?shell|\bMini\s?Shell\b|\bTinyFileManager\b.*\$auth_users/i' ),
		'include_img'  => array( 'critical', 'php', __( 'Loads PHP code hidden in an image or text file', 'semrs-pro' ), '/\b(?:include|require)(?:_once)?\s*\(?\s*@?\s*[\'"][^\'"\n]+\.(?:ico|png|jpe?g|gif|bmp|txt)[\'"]/i' ),
		'include_hex'  => array( 'critical', 'php', __( 'Loads a file with a hidden (encoded) name', 'semrs-pro' ), '/@?(?:include|require)(?:_once)?\s*\(?\s*["\'](?:\\\\x[0-9a-f]{2}|\\\\[0-7]{3}){4,}/i' ),
		'var_input'    => array( 'high', 'php', __( 'Calls a function named by a visitor', 'semrs-pro' ), '/(?<![>:\w$])@?\$\w{1,3}\s*\(\s*' . $in . '\s*\[|' . $in . '\s*\[[^\]]+\]\s*\(/i' ),
		'gz_b64'       => array( 'high', 'php', __( 'Layered encoding typical of malware', 'semrs-pro' ), '/\b(?:gzinflate|gzuncompress|gzdecode|str_rot13)\s*\(\s*(?:base64_decode|str_rot13|gzinflate|strrev)\s*\(/i' ),
		'hex_obf'      => array( 'high', 'php', __( 'Long hidden (hex-encoded) text', 'semrs-pro' ), '/(?:\\\\x[0-9a-fA-F]{2}){40,}/' ),
		'chr_chain'    => array( 'high', 'php', __( 'Text built character by character to hide it', 'semrs-pro' ), '/(?:chr\s*\(\s*\d+\s*\)\s*\.\s*){12,}/i' ),
		'var_hex'      => array( 'high', 'php', __( 'Hidden variable names', 'semrs-pro' ), '/\$\{\s*["\'](?:\\\\x[0-9a-f]{2}){3,}/i' ),
		'remote_eval'  => array( 'critical', 'php', __( 'Downloads code from another site and runs it', 'semrs-pro' ), '/\b(?:eval|assert)\s*\(\s*@?\s*(?:file_get_contents|wp_remote_retrieve_body|curl_exec)\s*\(/i' ),
		'js_ndsw'      => array( 'critical', 'js', __( 'Known injected redirect script (NDSW family)', 'semrs-pro' ), '/;\s*if\s*\(\s*(?:typeof\s+)?nds[jwx]\b|\bvar\s+nds[jwx]\s*=\s*true/i' ),
		'js_charcode'  => array( 'high', 'js', __( 'Script hidden as character codes', 'semrs-pro' ), '/String\.fromCharCode\(\s*(?:\d+\s*,\s*){40,}/' ),
		'js_unescape'  => array( 'high', 'js,html', __( 'Writes hidden code into the page', 'semrs-pro' ), '/document\.write\s*\(\s*unescape\s*\(/i' ),
		'js_packed'    => array( 'medium', 'js', __( 'Packed (hidden) script', 'semrs-pro' ), '/eval\s*\(\s*function\s*\(\s*p\s*,\s*a\s*,\s*c\s*,\s*k\s*,\s*e\s*,\s*[rd]\s*\)/' ),
		'html_iframe'  => array( 'high', 'html', __( 'Invisible frame to another site', 'semrs-pro' ), '/<iframe[^>]+(?:width\s*=\s*["\']?0|height\s*=\s*["\']?0|display\s*:\s*none|visibility\s*:\s*hidden)[^>]*src\s*=\s*["\']?https?:/i' ),
		'svg_script'   => array( 'medium', 'html', __( 'Script inside an image or page file', 'semrs-pro' ), '/<script[\s>][^<]{0,400}(?:eval|atob|fromCharCode|document\.cookie|location\s*=)/i' ),
	);
}

/**
 * Scan state.
 *
 * @return array
 */
function semrs_scan_state() {
	return wp_parse_args(
		(array) get_option( 'semrs_scan_state', array() ),
		array( 'status' => 'idle', 'phase' => '', 'pos' => 0, 'total' => 0, 'started' => 0, 'finished' => 0, 'files' => 0, 'verified' => 0, 'by' => '' )
	);
}

/**
 * Save the state.
 *
 * @param array $s State.
 */
function semrs_scan_save_state( $s ) {
	update_option( 'semrs_scan_state', $s, false );
}

/**
 * Findings: id => [sev, type, file, msg, detail, time].
 *
 * @return array
 */
function semrs_scan_findings() {
	return (array) get_option( 'semrs_scan_findings', array() );
}

/**
 * Add a finding to the running scan (stored separately until it finishes).
 *
 * @param string $sev    critical|high|medium|low.
 * @param string $type   core|plugin|file|upload|db|user|update|config.
 * @param string $file   Relative path or object.
 * @param string $msg    What it is.
 * @param string $detail Matched code or extra detail.
 * @param array  $extra  Extra data (slug, version).
 */
function semrs_scan_add( $sev, $type, $file, $msg, $detail = '', $extra = array() ) {
	$new = (array) get_option( 'semrs_scan_new', array() );
	$id  = substr( md5( $type . '|' . $file . '|' . $msg ), 0, 12 );
	if ( count( $new ) >= 2000 ) {
		return;
	}
	$new[ $id ] = array(
		'sev'    => $sev,
		'type'   => $type,
		'file'   => $file,
		'msg'    => $msg,
		'detail' => mb_substr( (string) preg_replace( '/[^\P{C}\n\t]/u', '?', wp_check_invalid_utf8( (string) $detail, true ) ), 0, 300 ),
		'time'   => time(),
		'extra'  => $extra,
	);
	update_option( 'semrs_scan_new', $new, false );
}

/**
 * Is a finding ignored (same file contents as when the owner ignored it)?
 *
 * @param string $id   Finding id.
 * @param string $file Relative path.
 * @return bool
 */
function semrs_scan_is_ignored( $id, $file ) {
	$ign = (array) get_option( 'semrs_scan_ignored', array() );
	if ( ! isset( $ign[ $id ] ) ) {
		return false;
	}
	$path = ABSPATH . $file;
	return ! is_file( $path ) || md5_file( $path ) === $ign[ $id ];
}

/*
 * Starting and stepping.
 */

/**
 * Start a scan: list the files.
 *
 * @param string $by manual|daily.
 * @return bool
 */
function semrs_scan_start( $by = 'manual' ) {
	$dir = semrs_secpro_dir();
	if ( '' === $dir ) {
		return false;
	}
	@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$list  = array();
	$roots = array( ABSPATH );
	// WordPress root: files only (core folders and wp-content follow).
	foreach ( (array) scandir( ABSPATH ) as $f ) {
		if ( '.' !== $f && '..' !== $f && is_file( ABSPATH . $f ) ) {
			$list[] = $f;
		}
	}
	foreach ( array( 'wp-admin', 'wp-includes', trim( str_replace( ABSPATH, '', WP_CONTENT_DIR ), '/' ) ) as $sub ) {
		$base = ABSPATH . $sub;
		if ( ! is_dir( $base ) ) {
			continue;
		}
		try {
			$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::LEAVES_ONLY );
			foreach ( $it as $file ) {
				$rel = str_replace( '\\', '/', substr( $file->getPathname(), strlen( ABSPATH ) ) );
				if ( 0 === strpos( $rel, 'wp-admin/' ) || 0 === strpos( $rel, 'wp-includes/' ) || ( '' !== semrs_scan_kind( $rel ) && ! semrs_scan_skip_path( $rel ) ) ) {
					$list[] = $rel;
				}
				if ( count( $list ) > 250000 ) {
					break;
				}
			}
		} catch ( Exception $e ) {
			continue;
		}
	}
	unset( $roots );
	file_put_contents( $dir . 'scan-files.txt', implode( "\n", $list ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	file_put_contents( $dir . 'scan-verified.txt', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	update_option( 'semrs_scan_new', array(), false );
	semrs_scan_save_state(
		array(
			'status'   => 'running',
			'phase'    => 'core',
			'pos'      => 0,
			'total'    => count( $list ),
			'started'  => time(),
			'finished' => 0,
			'files'    => count( $list ),
			'verified' => 0,
			'by'       => $by,
		)
	);
	return true;
}

/**
 * The listed files.
 *
 * @return string[]
 */
function semrs_scan_list() {
	static $list = null;
	if ( null === $list ) {
		$f    = semrs_secpro_dir() . 'scan-files.txt';
		$list = is_file( $f ) ? array_filter( explode( "\n", (string) file_get_contents( $f ) ) ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$list = array_values( $list );
	}
	return $list;
}

/**
 * Mark files as verified (identical to WordPress.org).
 *
 * @param string[] $files Relative paths.
 */
function semrs_scan_verified_add( $files ) {
	if ( $files ) {
		file_put_contents( semrs_secpro_dir() . 'scan-verified.txt', implode( "\n", $files ) . "\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}

/**
 * Run the scan for up to $budget seconds.
 *
 * @param int $budget Seconds.
 * @return array State.
 */
function semrs_scan_step( $budget = 6 ) {
	$s = semrs_scan_state();
	if ( 'running' !== $s['status'] ) {
		return $s;
	}
	@set_time_limit( $budget + 30 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$end = microtime( true ) + $budget;
	while ( microtime( true ) < $end && 'running' === $s['status'] ) {
		switch ( $s['phase'] ) {
			case 'core':
				$s = semrs_scan_phase_core( $s, $end );
				break;
			case 'plugins':
				$s = semrs_scan_phase_plugins( $s, $end );
				break;
			case 'files':
				$s = semrs_scan_phase_files( $s, $end );
				break;
			case 'db':
				$s = semrs_scan_phase_db( $s );
				break;
			case 'health':
				$s = semrs_scan_phase_health( $s, $end );
				break;
			default:
				$s = semrs_scan_finish( $s );
		}
		semrs_scan_save_state( $s );
	}
	return $s;
}

/**
 * Core files against WordPress.org fingerprints.
 *
 * @param array $s   State.
 * @param float $end Deadline.
 * @return array
 */
function semrs_scan_phase_core( $s, $end ) {
	global $wp_version, $wp_local_package;
	$sums = get_transient( 'semrs_scan_core_sums' );
	if ( ! is_array( $sums ) || ( $sums['v'] ?? '' ) !== $wp_version ) {
		if ( ! function_exists( 'get_core_checksums' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$c = get_core_checksums( $wp_version, isset( $wp_local_package ) ? $wp_local_package : 'en_US' );
		if ( ! is_array( $c ) ) {
			$c = get_core_checksums( $wp_version, 'en_US' );
		}
		if ( ! is_array( $c ) ) {
			semrs_scan_add( 'low', 'core', 'WordPress', __( 'WordPress.org fingerprints could not be loaded, so core files were only checked for malware code.', 'semrs-pro' ) );
			$s['phase'] = 'plugins';
			$s['pos']   = 0;
			return $s;
		}
		$sums = array( 'v' => $wp_version, 'files' => $c );
		set_transient( 'semrs_scan_core_sums', $sums, DAY_IN_SECONDS );
	}
	$files = $sums['files'];
	$keys  = array_keys( $files );
	$ok    = array();
	$n     = count( $keys );
	$skip  = trim( str_replace( ABSPATH, '', WP_CONTENT_DIR ), '/' ) . '/';
	while ( $s['pos'] < $n && microtime( true ) < $end ) {
		$rel  = $keys[ $s['pos']++ ];
		$path = ABSPATH . $rel;
		if ( 0 === strpos( $rel, 'wp-content/' ) || 0 === strpos( $rel, $skip ) ) {
			// Bundled themes and plugins: verified when unchanged, otherwise scanned for malware code.
			if ( is_file( $path ) && md5_file( $path ) === $files[ $rel ] ) {
				$ok[] = $rel;
			}
			continue;
		}
		if ( ! is_file( $path ) ) {
			if ( ! in_array( $rel, array( 'wp-config-sample.php', 'readme.html', 'license.txt', 'wp-admin/install.php' ), true ) ) {
				semrs_scan_add( 'low', 'core', $rel, __( 'WordPress file is missing', 'semrs-pro' ) );
			}
			continue;
		}
		if ( md5_file( $path ) === $files[ $rel ] ) {
			$ok[] = $rel;
		} else {
			$php = 'php' === semrs_scan_kind( $rel );
			semrs_scan_add( $php ? 'critical' : 'high', 'core', $rel, __( 'WordPress core file was changed', 'semrs-pro' ), '', array( 'restore' => 'core' ) );
		}
	}
	semrs_scan_verified_add( $ok );
	$s['verified'] += count( $ok );
	if ( $s['pos'] >= $n ) {
		// Unknown PHP files inside wp-admin and wp-includes, and in the root.
		foreach ( semrs_scan_list() as $rel ) {
			$in_core = 0 === strpos( $rel, 'wp-admin/' ) || 0 === strpos( $rel, 'wp-includes/' ) || false === strpos( $rel, '/' );
			if ( $in_core && ! isset( $files[ $rel ] ) && 'php' === semrs_scan_kind( $rel ) && ! in_array( $rel, array( 'wp-config.php', 'wp-config-local.php' ), true ) ) {
				$root = false === strpos( $rel, '/' );
				semrs_scan_add( $root ? 'medium' : 'high', 'core', $rel, $root ? __( 'Unknown PHP file in the WordPress folder', 'semrs-pro' ) : __( 'Unknown file inside a WordPress core folder', 'semrs-pro' ), '', array( 'quarantine' => 1 ) );
			}
		}
		$s['phase'] = 'plugins';
		$s['pos']   = 0;
	}
	return $s;
}

/**
 * Plugins from WordPress.org against their official fingerprints.
 *
 * @param array $s   State.
 * @param float $end Deadline.
 * @return array
 */
function semrs_scan_phase_plugins( $s, $end ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugins = array_values( array_filter( array_keys( get_plugins() ), function ( $f ) { return false !== strpos( $f, '/' ); } ) );
	$all     = get_plugins();
	$list    = semrs_scan_list();
	$pdir    = trim( str_replace( ABSPATH, '', WP_PLUGIN_DIR ), '/' ) . '/';
	while ( $s['pos'] < count( $plugins ) && microtime( true ) < $end ) {
		$file = $plugins[ $s['pos']++ ];
		$slug = dirname( $file );
		$ver  = (string) ( $all[ $file ]['Version'] ?? '' );
		if ( '' === $ver || ! empty( $all[ $file ]['UpdateURI'] ) && false === strpos( (string) $all[ $file ]['UpdateURI'], 'wordpress.org' ) ) {
			continue; // Not from WordPress.org: checked for malware code instead.
		}
		$key  = 'semrs_scan_ps_' . md5( $slug . $ver );
		$sums = get_transient( $key );
		if ( false === $sums ) {
			$res  = wp_remote_get( 'https://downloads.wordpress.org/plugin-checksums/' . rawurlencode( $slug ) . '/' . rawurlencode( $ver ) . '.json', array( 'timeout' => 10 ) );
			$data = is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ? null : json_decode( (string) wp_remote_retrieve_body( $res ), true );
			$sums = is_array( $data['files'] ?? null ) ? $data['files'] : array();
			set_transient( $key, $sums, WEEK_IN_SECONDS );
		}
		if ( ! $sums ) {
			continue;
		}
		$prefix = $pdir . $slug . '/';
		$ok     = array();
		foreach ( $list as $rel ) {
			if ( 0 !== strpos( $rel, $prefix ) ) {
				continue;
			}
			$inner = substr( $rel, strlen( $prefix ) );
			if ( ! isset( $sums[ $inner ] ) ) {
				if ( 'php' === semrs_scan_kind( $rel ) ) {
					/* translators: %s: plugin name */
					semrs_scan_add( 'high', 'plugin', $rel, sprintf( __( 'File added to %s (not in the official version)', 'semrs-pro' ), $all[ $file ]['Name'] ), '', array( 'quarantine' => 1 ) );
				}
				continue;
			}
			$md5 = (array) ( $sums[ $inner ]['md5'] ?? array() );
			if ( in_array( md5_file( ABSPATH . $rel ), $md5, true ) ) {
				$ok[] = $rel;
			} else {
				/* translators: %s: plugin name */
				semrs_scan_add( 'php' === semrs_scan_kind( $rel ) ? 'critical' : 'high', 'plugin', $rel, sprintf( __( '%s file was changed', 'semrs-pro' ), $all[ $file ]['Name'] ), '', array( 'restore' => 'plugin', 'slug' => $slug, 'ver' => $ver ) );
			}
		}
		semrs_scan_verified_add( $ok );
		$s['verified'] += count( $ok );
	}
	if ( $s['pos'] >= count( $plugins ) ) {
		$s['phase'] = 'files';
		$s['pos']   = 0;
	}
	return $s;
}

/**
 * Malware code in files that could not be verified.
 *
 * @param array $s   State.
 * @param float $end Deadline.
 * @return array
 */
function semrs_scan_phase_files( $s, $end ) {
	static $verified = null;
	if ( null === $verified ) {
		$f        = semrs_secpro_dir() . 'scan-verified.txt';
		$verified = is_file( $f ) ? array_flip( array_filter( explode( "\n", (string) file_get_contents( $f ) ) ) ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	$list    = semrs_scan_list();
	$n       = count( $list );
	$sigs    = semrs_scan_signatures();
	$uploads = trim( str_replace( ABSPATH, '', wp_get_upload_dir()['basedir'] ), '/' ) . '/';
	$read    = 0;
	while ( $s['pos'] < $n && microtime( true ) < $end && $read < 24 * MB_IN_BYTES ) {
		$rel = $list[ $s['pos']++ ];
		if ( isset( $verified[ $rel ] ) || semrs_scan_skip_path( $rel ) ) {
			continue;
		}
		$kind = semrs_scan_kind( $rel );
		$path = ABSPATH . $rel;
		if ( '' === $kind || ! is_file( $path ) || ! is_readable( $path ) ) {
			continue;
		}
		$size = (int) filesize( $path );
		if ( $size > 3 * MB_IN_BYTES ) {
			continue;
		}
		$in_up = 0 === strpos( $rel, $uploads );
		$code  = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$read += $size;
		// PHP and server rules inside uploads.
		if ( $in_up && 'php' === $kind && ! preg_match( '/^<\?php\s*(?:\/\/[^\n]*|\/\*.*?\*\/)?\s*(?:\?>)?\s*$/s', $code ) ) {
			semrs_scan_add( 'high', 'upload', $rel, __( 'PHP file inside the uploads folder (uploads should only hold media)', 'semrs-pro' ), mb_substr( $code, 0, 160 ), array( 'quarantine' => 1 ) );
		}
		if ( $in_up && 'htaccess' === $kind && preg_match( '/(?:AddHandler|SetHandler|AddType)[^\n]*(?:php|x-httpd)|auto_prepend_file|auto_append_file/i', $code, $m ) ) {
			semrs_scan_add( 'critical', 'upload', $rel, __( 'Server rule that lets uploads run as code', 'semrs-pro' ), $m[0], array( 'quarantine' => 1 ) );
		}
		if ( 'ico' === $kind && false !== stripos( $code, '<?php' ) ) {
			semrs_scan_add( 'critical', 'file', $rel, __( 'PHP code hidden in an icon file', 'semrs-pro' ), '', array( 'quarantine' => 1 ) );
			continue;
		}
		$hit = false;
		foreach ( $sigs as $id => $sig ) {
			if ( false === strpos( $sig[1], 'ico' === $kind ? 'php' : $kind ) ) {
				continue;
			}
			if ( preg_match( $sig[3], $code, $m, PREG_OFFSET_CAPTURE ) ) {
				$line = substr_count( substr( $code, 0, (int) $m[0][1] ), "\n" ) + 1;
				/* translators: 1: what was found, 2: line number */
				semrs_scan_add( $sig[0], $in_up ? 'upload' : 'file', $rel, $sig[2], sprintf( __( 'Line %2$d: %1$s', 'semrs-pro' ), mb_substr( (string) $m[0][0], 0, 160 ), $line ), array( 'quarantine' => 1, 'sig' => $id ) );
				$hit = true;
				break;
			}
		}
		if ( ! $hit && 'php' === $kind && false !== stripos( $code, 'base64_decode' ) && preg_match( '/[A-Za-z0-9+\/]{2000,}={0,2}/', $code ) ) {
			semrs_scan_add( 'high', $in_up ? 'upload' : 'file', $rel, __( 'Large hidden (encoded) block that is decoded and used', 'semrs-pro' ), '', array( 'quarantine' => 1 ) );
		}
	}
	if ( $s['pos'] >= $n ) {
		$s['phase'] = 'db';
		$s['pos']   = 0;
	}
	return $s;
}

/**
 * Injected scripts in posts and settings; new administrators.
 *
 * @param array $s State.
 * @return array
 */
function semrs_scan_phase_db( $s ) {
	global $wpdb;
	$re   = 'eval\\(|fromCharCode\\(|document\\.write\\(unescape|atob\\(|nds[jwx]|<iframe[^>]+(width=.?0[^0-9]|display: ?none)';
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_type FROM {$wpdb->posts} WHERE post_status IN ('publish','private','draft','future') AND post_type NOT IN ('revision','nav_menu_item','wp_global_styles') AND post_content REGEXP %s LIMIT 50", '<script[^>]*>[^<]*(' . $re . ')|' . $re ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $rows as $r ) {
		/* translators: 1: post type, 2: title */
		semrs_scan_add( 'high', 'db', 'post:' . $r->ID, sprintf( __( 'Hidden script in %1$s “%2$s”', 'semrs-pro' ), $r->post_type, $r->post_title ), '', array( 'edit' => get_edit_post_link( (int) $r->ID, 'raw' ) ) );
	}
	$opts = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s AND option_value REGEXP %s LIMIT 30", '\_transient%', '\_site\_transient%', '<script[^>]*>[^<]*(' . $re . ')' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $opts as $o ) {
		/* translators: %s: setting name */
		semrs_scan_add( 'high', 'db', 'option:' . $o, sprintf( __( 'Hidden script in the setting “%s”', 'semrs-pro' ), $o ) );
	}
	$admins = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID', 'user_login', 'user_registered' ) ) );
	$known  = get_option( 'semrs_scan_admins', null );
	$now    = array();
	foreach ( $admins as $a ) {
		$now[] = (int) $a->ID;
		if ( is_array( $known ) && ! in_array( (int) $a->ID, $known, true ) ) {
			/* translators: %s: username */
			semrs_scan_add( 'high', 'user', 'user:' . $a->ID, sprintf( __( 'New administrator since the last scan: %s. If you did not add this person, remove the account and change your passwords.', 'semrs-pro' ), $a->user_login ), '', array( 'edit' => admin_url( 'user-edit.php?user_id=' . (int) $a->ID ) ) );
		}
	}
	update_option( 'semrs_scan_admins', $now, false );
	$s['phase'] = 'health';
	$s['pos']   = 0;
	return $s;
}

/**
 * Updates, removed plugins and risky settings.
 *
 * @param array $s   State.
 * @param float $end Deadline.
 * @return array
 */
function semrs_scan_phase_health( $s, $end ) {
	if ( 0 === (int) $s['pos'] ) {
		if ( ! function_exists( 'get_core_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$core = get_core_updates();
		if ( is_array( $core ) && isset( $core[0]->response ) && 'upgrade' === $core[0]->response ) {
			/* translators: %s: version */
			semrs_scan_add( 'medium', 'update', 'WordPress', sprintf( __( 'WordPress %s is available. Updates often fix security holes.', 'semrs-pro' ), $core[0]->current ), '', array( 'edit' => admin_url( 'update-core.php' ) ) );
		}
		$pu = get_site_transient( 'update_plugins' );
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all = get_plugins();
		foreach ( (array) ( $pu->response ?? array() ) as $f => $u ) {
			/* translators: 1: plugin, 2: version */
			semrs_scan_add( 'medium', 'update', $f, sprintf( __( 'Update waiting: %1$s %2$s', 'semrs-pro' ), $all[ $f ]['Name'] ?? $f, $u->new_version ?? '' ), '', array( 'edit' => admin_url( 'plugins.php?plugin_status=upgrade' ) ) );
		}
		$tu = get_site_transient( 'update_themes' );
		foreach ( (array) ( $tu->response ?? array() ) as $t => $u ) {
			/* translators: 1: theme, 2: version */
			semrs_scan_add( 'medium', 'update', $t, sprintf( __( 'Theme update waiting: %1$s %2$s', 'semrs-pro' ), $t, $u['new_version'] ?? '' ), '', array( 'edit' => admin_url( 'themes.php' ) ) );
		}
		// Settings.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) ) {
			semrs_scan_add( 'medium', 'config', 'wp-config.php', __( 'Errors are shown to visitors (WP_DEBUG_DISPLAY). They can reveal file paths to attackers.', 'semrs-pro' ) );
		}
		$cfg = file_exists( ABSPATH . 'wp-config.php' ) ? ABSPATH . 'wp-config.php' : dirname( ABSPATH ) . '/wp-config.php';
		if ( is_file( $cfg ) && ( fileperms( $cfg ) & 0002 ) ) {
			semrs_scan_add( 'high', 'config', 'wp-config.php', __( 'wp-config.php can be changed by anyone on the server (permissions). Set it to 640 or 600.', 'semrs-pro' ) );
		}
		if ( username_exists( 'admin' ) ) {
			semrs_scan_add( 'low', 'config', 'user:admin', __( 'A user named “admin” exists. Bots try this name first; use a different administrator name.', 'semrs-pro' ) );
		}
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			/* translators: %s: PHP version */
			semrs_scan_add( 'low', 'config', 'PHP', sprintf( __( 'PHP %s no longer gets security fixes. Ask your host for PHP 8.2 or newer.', 'semrs-pro' ), PHP_VERSION ) );
		}
		$s['pos'] = 1;
	}
	// Plugins closed on WordPress.org (one lookup per plugin, cached a week).
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$all  = get_plugins();
	$keys = array_keys( $all );
	while ( $s['pos'] - 1 < count( $keys ) && microtime( true ) < $end ) {
		$f    = $keys[ $s['pos'] - 1 ];
		++$s['pos'];
		$slug = dirname( $f );
		if ( '.' === $slug || ( ! empty( $all[ $f ]['UpdateURI'] ) && false === strpos( (string) $all[ $f ]['UpdateURI'], 'wordpress.org' ) ) ) {
			continue;
		}
		$key  = 'semrs_scan_pi_' . md5( $slug );
		$info = get_transient( $key );
		if ( false === $info ) {
			$res  = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=' . rawurlencode( $slug ) . '&request[fields][sections]=0', array( 'timeout' => 8 ) );
			$data = is_wp_error( $res ) ? array() : (array) json_decode( (string) wp_remote_retrieve_body( $res ), true );
			$info = array(
				'closed'  => ! empty( $data['error'] ) && ( false !== stripos( (string) $data['error'], 'closed' ) || ! empty( $data['closed'] ) ),
				'updated' => (string) ( $data['last_updated'] ?? '' ),
			);
			set_transient( $key, $info, WEEK_IN_SECONDS );
		}
		if ( ! empty( $info['closed'] ) ) {
			/* translators: %s: plugin */
			semrs_scan_add( 'high', 'update', $f, sprintf( __( '%s was removed from WordPress.org (often for a security problem). Replace it with a maintained plugin.', 'semrs-pro' ), $all[ $f ]['Name'] ) );
		} elseif ( $info['updated'] && strtotime( $info['updated'] ) < time() - 2 * YEAR_IN_SECONDS ) {
			/* translators: %s: plugin */
			semrs_scan_add( 'low', 'update', $f, sprintf( __( '%s has not been updated for over two years.', 'semrs-pro' ), $all[ $f ]['Name'] ) );
		}
	}
	if ( $s['pos'] - 1 >= count( $keys ) ) {
		$s['phase'] = 'done';
	}
	return $s;
}

/**
 * Finish: keep the results, email new serious problems.
 *
 * @param array $s State.
 * @return array
 */
function semrs_scan_finish( $s ) {
	$new  = (array) get_option( 'semrs_scan_new', array() );
	$keep = array();
	foreach ( $new as $id => $f ) {
		if ( ! semrs_scan_is_ignored( $id, (string) $f['file'] ) ) {
			$keep[ $id ] = $f;
		}
	}
	$old = semrs_scan_findings();
	update_option( 'semrs_scan_findings', $keep, false );
	delete_option( 'semrs_scan_new' );
	$s['status']   = 'idle';
	$s['phase']    = '';
	$s['finished'] = time();
	$fresh         = array_diff_key(
		array_filter(
			$keep,
			function ( $f ) {
				return in_array( $f['sev'], array( 'critical', 'high' ), true );
			}
		),
		$old
	);
	if ( $fresh && '1' === semrs_scan_settings()['scan_email'] ) {
		$lines = array();
		foreach ( array_slice( $fresh, 0, 20 ) as $f ) {
			$lines[] = '- [' . strtoupper( $f['sev'] ) . '] ' . $f['msg'] . ': ' . $f['file'];
		}
		wp_mail(
			get_option( 'admin_email' ),
			/* translators: %s: site name */
			sprintf( __( '[%s] Security scan found problems', 'semrs-pro' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
			__( 'The SEMRS security scan found new problems on your site:', 'semrs-pro' ) . "\n\n" . implode( "\n", $lines ) . "\n\n" . __( 'Review and fix them here:', 'semrs-pro' ) . ' ' . admin_url( 'admin.php?page=semrs-protect&tab=scan' )
		);
	}
	@unlink( semrs_secpro_dir() . 'scan-verified.txt' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
	return $s;
}

/*
 * Fixes.
 */

/**
 * Restore a core or plugin file from WordPress.org (old copy quarantined first).
 *
 * @param string $id Finding id.
 * @return true|WP_Error
 */
function semrs_scan_restore( $id ) {
	global $wp_version;
	$f = semrs_scan_findings()[ $id ] ?? null;
	if ( ! $f || empty( $f['extra']['restore'] ) ) {
		return new WP_Error( 'semrs', __( 'This file cannot be restored automatically.', 'semrs-pro' ) );
	}
	$rel = (string) $f['file'];
	if ( 'core' === $f['extra']['restore'] ) {
		$url = 'https://core.svn.wordpress.org/tags/' . rawurlencode( $wp_version ) . '/' . str_replace( '%2F', '/', rawurlencode( $rel ) );
	} else {
		$inner = substr( $rel, strlen( trim( str_replace( ABSPATH, '', WP_PLUGIN_DIR ), '/' ) . '/' . $f['extra']['slug'] . '/' ) );
		$url   = 'https://plugins.svn.wordpress.org/' . rawurlencode( $f['extra']['slug'] ) . '/tags/' . rawurlencode( $f['extra']['ver'] ) . '/' . str_replace( '%2F', '/', rawurlencode( $inner ) );
	}
	$res = wp_remote_get( $url, array( 'timeout' => 20 ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return new WP_Error( 'semrs', __( 'The original file could not be downloaded from WordPress.org. Try again later, or reinstall the plugin or WordPress.', 'semrs-pro' ) );
	}
	$body = (string) wp_remote_retrieve_body( $res );
	semrs_scan_quarantine( $id, false );
	if ( false === file_put_contents( ABSPATH . $rel, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error( 'semrs', __( 'The file could not be written. Check the folder permissions.', 'semrs-pro' ) );
	}
	semrs_scan_drop( $id );
	return true;
}

/**
 * Move a file to quarantine (or copy it, before a restore).
 *
 * @param string $id   Finding id.
 * @param bool   $move Move (true) or copy (false).
 * @return true|WP_Error
 */
function semrs_scan_quarantine( $id, $move = true ) {
	$f = semrs_scan_findings()[ $id ] ?? null;
	if ( ! $f || false !== strpos( (string) $f['file'], ':' ) ) {
		return new WP_Error( 'semrs', __( 'Nothing to move.', 'semrs-pro' ) );
	}
	$rel  = (string) $f['file'];
	$path = ABSPATH . $rel;
	if ( in_array( basename( $rel ), array( 'wp-config.php', 'index.php' ), true ) && false === strpos( $rel, '/' ) ) {
		return new WP_Error( 'semrs', __( 'This file is needed by WordPress and is never moved. Restore or edit it instead.', 'semrs-pro' ) );
	}
	$dir = semrs_secpro_dir();
	if ( ! is_file( $path ) || '' === $dir ) {
		return new WP_Error( 'semrs', __( 'The file is already gone.', 'semrs-pro' ) );
	}
	wp_mkdir_p( $dir . 'quarantine' );
	$qid  = gmdate( 'Ymd-His' ) . '-' . $id;
	$ok   = $move ? rename( $path, $dir . 'quarantine/' . $qid . '.bin' ) : copy( $path, $dir . 'quarantine/' . $qid . '.bin' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $ok ) {
		return new WP_Error( 'semrs', __( 'The file could not be moved. Check the folder permissions.', 'semrs-pro' ) );
	}
	$q          = (array) get_option( 'semrs_scan_quarantine', array() );
	$q[ $qid ]  = array( 'file' => $rel, 'time' => time(), 'msg' => $f['msg'], 'moved' => $move );
	update_option( 'semrs_scan_quarantine', $q, false );
	if ( $move ) {
		semrs_scan_drop( $id );
	}
	return true;
}

/**
 * Put a quarantined file back.
 *
 * @param string $qid Quarantine id.
 * @return true|WP_Error
 */
function semrs_scan_unquarantine( $qid ) {
	$q   = (array) get_option( 'semrs_scan_quarantine', array() );
	$dir = semrs_secpro_dir();
	if ( ! isset( $q[ $qid ] ) || ! is_file( $dir . 'quarantine/' . $qid . '.bin' ) ) {
		return new WP_Error( 'semrs', __( 'That file is no longer in quarantine.', 'semrs-pro' ) );
	}
	$dest = ABSPATH . $q[ $qid ]['file'];
	if ( file_exists( $dest ) ) {
		return new WP_Error( 'semrs', __( 'A file with that name exists again; it was not overwritten.', 'semrs-pro' ) );
	}
	wp_mkdir_p( dirname( $dest ) );
	if ( ! rename( $dir . 'quarantine/' . $qid . '.bin', $dest ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error( 'semrs', __( 'The file could not be put back. Check the folder permissions.', 'semrs-pro' ) );
	}
	unset( $q[ $qid ] );
	update_option( 'semrs_scan_quarantine', $q, false );
	return true;
}

/**
 * Remove a finding from the list.
 *
 * @param string $id Id.
 */
function semrs_scan_drop( $id ) {
	$all = semrs_scan_findings();
	unset( $all[ $id ] );
	update_option( 'semrs_scan_findings', $all, false );
}

/**
 * Ignore a finding until the file changes.
 *
 * @param string $id Id.
 */
function semrs_scan_ignore( $id ) {
	$f = semrs_scan_findings()[ $id ] ?? null;
	if ( ! $f ) {
		return;
	}
	$ign        = (array) get_option( 'semrs_scan_ignored', array() );
	$path       = ABSPATH . $f['file'];
	$ign[ $id ] = is_file( $path ) ? md5_file( $path ) : 'x';
	update_option( 'semrs_scan_ignored', $ign, false );
	semrs_scan_drop( $id );
}

/*
 * Daily scan in the background (WP-Cron, short steps).
 */
add_action(
	'semrs_scan_daily',
	function () {
		$set = semrs_scan_settings();
		if ( '1' !== $set['scan_daily'] || ( semrs_fw_wordfence() && '1' !== $set['scan_wordfence'] ) ) {
			return;
		}
		$st = semrs_scan_state();
		if ( 'running' === $st['status'] && $st['started'] > time() - 6 * HOUR_IN_SECONDS ) {
			return;
		}
		if ( semrs_scan_start( 'daily' ) ) {
			wp_schedule_single_event( time() + 20, 'semrs_scan_cron_step' );
		}
	}
);
add_action(
	'semrs_scan_cron_step',
	function () {
		$st = semrs_scan_step( 20 );
		if ( 'running' === $st['status'] ) {
			wp_schedule_single_event( time() + 20, 'semrs_scan_cron_step' );
		}
	}
);
if ( ! wp_next_scheduled( 'semrs_scan_daily' ) ) {
	wp_schedule_event( strtotime( 'tomorrow 03:17' ) + wp_rand( 0, 3000 ), 'daily', 'semrs_scan_daily' );
}
