<?php
/**
 * Server-level speed (Apache / LiteSpeed):
 *
 * 1. Server rules in .htaccess between "# BEGIN SEMRS Speed" markers:
 *    Brotli + gzip compression, a year of browser caching (immutable) for
 *    versioned files, no ETags, Vary: Accept-Encoding, CORS for fonts and the
 *    right types for AVIF, WebP and WOFF2.
 * 2. Leftovers of old cache plugins (WP Rocket, W3 Total Cache, WP Super
 *    Cache, WP Fastest Cache, LiteSpeed Cache, Hummingbird, Breeze, SiteGround
 *    Optimizer): their .htaccess blocks and advanced-cache.php drop-in, shown
 *    only when that plugin is no longer active, removed with a backup.
 * 3. Early page cache: an advanced-cache.php drop-in that serves the SEMRS
 *    page cache before plugins and the theme load (lower server response
 *    time). Same cache files and rules as the normal page cache; it does
 *    nothing when the cache is off, SEMRS Core is missing or SEMRS_CACHE_OFF
 *    is set.
 *
 * Every change to .htaccess, advanced-cache.php or wp-config.php is made only
 * when an administrator clicks the button, and a dated backup is kept.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Paths and server detection
 * ---------------------------------------------------------------------- */

/**
 * The site's .htaccess file.
 *
 * @return string
 */
function semrs_server_htaccess() {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	return get_home_path() . '.htaccess';
}

/**
 * Does the server read .htaccess files (Apache or LiteSpeed)?
 *
 * @return bool
 */
function semrs_server_reads_htaccess() {
	$soft = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : '';
	return false !== strpos( $soft, 'apache' ) || false !== strpos( $soft, 'litespeed' ) || ( function_exists( 'apache_get_modules' ) );
}

/**
 * Copy a file next to itself as file.semrs-bak-YYYYMMDD-HHMMSS before changing it.
 *
 * @param string $file File.
 * @return bool
 */
function semrs_server_backup( $file ) {
	return ! file_exists( $file ) || copy( $file, $file . '.semrs-bak-' . gmdate( 'Ymd-His' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- site root file.
}

/* -------------------------------------------------------------------------
 * 1. Server rules
 * ---------------------------------------------------------------------- */

/**
 * The SEMRS Speed .htaccess rules.
 *
 * @return string[]
 */
function semrs_server_rules() {
	$text  = 'text/html text/plain text/css text/xml text/javascript application/javascript application/x-javascript application/json application/ld+json application/manifest+json application/xml application/rss+xml application/atom+xml image/svg+xml image/x-icon font/ttf font/otf application/vnd.ms-fontobject';
	$year  = 'access plus 1 year';
	$rules = array(
		'# Speed rules written by SEMRS Core (SEMRS → Speed → Server). Edit there, not here.',
		'AddDefaultCharset UTF-8',
		'<IfModule mod_mime.c>',
		'	AddType image/avif .avif',
		'	AddType image/webp .webp',
		'	AddType font/woff2 .woff2',
		'	AddType font/woff .woff',
		'	AddType application/manifest+json .webmanifest',
		'	AddCharset UTF-8 .css .js .mjs .json .svg .xml .txt',
		'</IfModule>',
		'# Compression: Brotli where the server has it, gzip everywhere else.',
		'<IfModule mod_brotli.c>',
		'	AddOutputFilterByType BROTLI_COMPRESS ' . $text,
		'</IfModule>',
		'<IfModule mod_deflate.c>',
		'	AddOutputFilterByType DEFLATE ' . $text,
		'	<IfModule mod_setenvif.c>',
		'		BrowserMatch ^Mozilla/4\.0[678] no-gzip',
		'		BrowserMatch \bMSIE !no-gzip !gzip-only-text/html',
		'	</IfModule>',
		'</IfModule>',
		'# Browser caching: files WordPress versions (?ver=) and media for a year; pages are never stored by browsers.',
		'<IfModule mod_expires.c>',
		'	ExpiresActive On',
		'	ExpiresDefault "access plus 1 month"',
		'	ExpiresByType text/html "access plus 0 seconds"',
		'	ExpiresByType application/json "access plus 0 seconds"',
		'	ExpiresByType application/xml "access plus 0 seconds"',
		'	ExpiresByType text/xml "access plus 0 seconds"',
		'	ExpiresByType application/rss+xml "access plus 1 hour"',
		'	ExpiresByType application/atom+xml "access plus 1 hour"',
		'	ExpiresByType text/css "' . $year . '"',
		'	ExpiresByType text/javascript "' . $year . '"',
		'	ExpiresByType application/javascript "' . $year . '"',
		'	ExpiresByType image/avif "' . $year . '"',
		'	ExpiresByType image/webp "' . $year . '"',
		'	ExpiresByType image/jpeg "' . $year . '"',
		'	ExpiresByType image/png "' . $year . '"',
		'	ExpiresByType image/gif "' . $year . '"',
		'	ExpiresByType image/svg+xml "' . $year . '"',
		'	ExpiresByType image/x-icon "' . $year . '"',
		'	ExpiresByType image/vnd.microsoft.icon "' . $year . '"',
		'	ExpiresByType video/mp4 "' . $year . '"',
		'	ExpiresByType video/webm "' . $year . '"',
		'	ExpiresByType audio/mpeg "' . $year . '"',
		'	ExpiresByType font/woff2 "' . $year . '"',
		'	ExpiresByType font/woff "' . $year . '"',
		'	ExpiresByType font/ttf "' . $year . '"',
		'	ExpiresByType font/otf "' . $year . '"',
		'	ExpiresByType application/vnd.ms-fontobject "' . $year . '"',
		'	ExpiresByType application/manifest+json "access plus 1 week"',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'	<FilesMatch "\.(avif|webp|jpe?g|png|gif|svg|ico|mp4|webm|mp3|woff2?|ttf|otf|eot|css|js|mjs)$">',
		'		Header set Cache-Control "public, max-age=31536000, immutable"',
		'	</FilesMatch>',
		'	<FilesMatch "\.(woff2?|ttf|otf|eot)$">',
		'		Header set Access-Control-Allow-Origin "*"',
		'	</FilesMatch>',
		'	<FilesMatch "\.(css|js|mjs|svg|json|xml|txt|html?)$">',
		'		Header append Vary Accept-Encoding',
		'	</FilesMatch>',
		'	Header unset ETag',
		'	Header unset X-Powered-By',
		'</IfModule>',
		'FileETag None',
	);
	return (array) apply_filters( 'semrs_server_rules', $rules );
}

/**
 * Are the SEMRS Speed rules in .htaccess now?
 *
 * @return bool
 */
function semrs_server_rules_present() {
	$f = semrs_server_htaccess();
	return file_exists( $f ) && false !== strpos( (string) file_get_contents( $f ), '# Speed rules written by SEMRS Core' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- insert_with_markers() adds its own comment lines after the BEGIN marker.
}

/**
 * Write (or remove) the SEMRS Speed block.
 *
 * @param bool $on Add the rules.
 * @return bool
 */
function semrs_server_write_rules( $on ) {
	$f = semrs_server_htaccess();
	if ( ( file_exists( $f ) && ! wp_is_writable( $f ) ) || ( ! file_exists( $f ) && ! wp_is_writable( dirname( $f ) ) ) ) {
		return false;
	}
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	semrs_server_backup( $f );
	if ( $on ) {
		return insert_with_markers( $f, 'SEMRS Speed', semrs_server_rules() );
	}
	return semrs_server_remove_block( $f, 'SEMRS Speed' );
}

/* -------------------------------------------------------------------------
 * 2. Leftovers of other cache plugins
 * ---------------------------------------------------------------------- */

/**
 * Known cache plugins: name => [plugin file, .htaccess marker regex, drop-in signature regex].
 *
 * @return array
 */
function semrs_server_known_plugins() {
	return array(
		'WP Rocket'            => array( 'wp-rocket/wp-rocket.php', '/^# BEGIN WP Rocket.*?^# END WP Rocket[^\n]*\n?/ms', '/WP_ROCKET|wp-rocket/i' ),
		'W3 Total Cache'       => array( 'w3-total-cache/w3-total-cache.php', '/^# BEGIN W3TC.*?^# END W3TC[^\n]*\n?/ms', '/W3TC|W3 Total Cache/i' ),
		'WP Super Cache'       => array( 'wp-super-cache/wp-cache.php', '/^# BEGIN WPSuperCache.*?^# END WPSuperCache[^\n]*\n?/ms', '/WP SUPER CACHE|wp-super-cache|wpsc/i' ),
		'WP Fastest Cache'     => array( 'wp-fastest-cache/wpFastestCache.php', '/^# BEGIN WpFastestCache.*?^# END WpFastestCache[^\n]*\n?/ms', '/WpFastestCache/i' ),
		'LiteSpeed Cache'      => array( 'litespeed-cache/litespeed-cache.php', '/^# BEGIN LSCACHE.*?^# END LSCACHE[^\n]*\n?|^# BEGIN NON_LSCACHE.*?^# END NON_LSCACHE[^\n]*\n?/ms', '/LiteSpeed/i' ),
		'Hummingbird'          => array( 'hummingbird-performance/wp-hummingbird.php', '/^# BEGIN WP-HUMMINGBIRD-[A-Z]+.*?^# END WP-HUMMINGBIRD-[A-Z]+[^\n]*\n?/ms', '/Hummingbird|WPHB/i' ),
		'Breeze'               => array( 'breeze/breeze.php', '/^# Begin Breeze[^\n]*.*?^# End Breeze[^\n]*\n?|^#Begin GzipofBreezeWPCache.*?^#End GzipofBreezeWPCache[^\n]*\n?|^#Expires headers configuration added by BREEZE WP CACHE plugin.*?^#End of expires headers configuration[^\n]*\n?/msi', '/Breeze/i' ),
		'SiteGround Optimizer' => array( 'sg-cachepress/sg-cachepress.php', '/^# BEGIN SGO[^\n]*.*?^# END SGO[^\n]*\n?|^# SGO [^\n]*.*?^# SGO [^\n]*END[^\n]*\n?/ms', '/SiteGround|SG_CachePress|SGO/i' ),
	);
}

/**
 * Leftovers found now.
 *
 * @return array{htaccess: string[], dropin: string} Plugin names with .htaccess blocks; plugin name of a foreign advanced-cache.php.
 */
function semrs_server_leftovers() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$out  = array( 'htaccess' => array(), 'dropin' => '' );
	$f    = semrs_server_htaccess();
	$body = file_exists( $f ) ? (string) file_get_contents( $f ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	$drop = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
	$dsrc = file_exists( $drop ) ? (string) file_get_contents( $drop, false, null, 0, 4096 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	foreach ( semrs_server_known_plugins() as $name => $p ) {
		if ( is_plugin_active( $p[0] ) ) {
			continue; // Its rules are in use.
		}
		if ( $body && preg_match( $p[1], $body ) ) {
			$out['htaccess'][] = $name;
		}
		if ( $dsrc && ! semrs_server_dropin_is_ours() && preg_match( $p[2], $dsrc ) ) {
			$out['dropin'] = $name;
		}
	}
	return $out;
}

/**
 * Remove one "# BEGIN name … # END name" block (and an empty line after it).
 *
 * @param string $f    File.
 * @param string $name Marker.
 * @return bool
 */
function semrs_server_remove_block( $f, $name ) {
	if ( ! file_exists( $f ) ) {
		return true;
	}
	$body = (string) file_get_contents( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$new  = preg_replace( '/^# BEGIN ' . preg_quote( $name, '/' ) . '\R.*?^# END ' . preg_quote( $name, '/' ) . '\R?(\R)?/ms', '', $body );
	return null !== $new && false !== file_put_contents( $f, $new, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Remove the leftovers of plugins that are no longer active.
 *
 * @return string[] What was removed.
 */
function semrs_server_clean_leftovers() {
	$left  = semrs_server_leftovers();
	$done  = array();
	$known = semrs_server_known_plugins();
	if ( $left['htaccess'] ) {
		$f = semrs_server_htaccess();
		if ( wp_is_writable( $f ) && semrs_server_backup( $f ) ) {
			$body = (string) file_get_contents( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			foreach ( $left['htaccess'] as $name ) {
				$body   = (string) preg_replace( $known[ $name ][1], '', $body );
				$done[] = $name . ' (.htaccess)';
			}
			$body = preg_replace( "/\n{3,}/", "\n\n", $body );
			file_put_contents( $f, ltrim( $body ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	if ( $left['dropin'] ) {
		$drop = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
		if ( rename( $drop, $drop . '.semrs-bak-' . gmdate( 'Ymd-His' ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			$done[] = $left['dropin'] . ' (advanced-cache.php)';
		}
	}
	return $done;
}

/* -------------------------------------------------------------------------
 * 3. Early page cache (advanced-cache.php)
 * ---------------------------------------------------------------------- */

/**
 * Is the advanced-cache.php in wp-content ours?
 *
 * @return bool
 */
function semrs_server_dropin_is_ours() {
	$drop = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
	return file_exists( $drop ) && false !== strpos( (string) file_get_contents( $drop, false, null, 0, 300 ), 'SEMRS early page cache' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Settings file the drop-in reads (plain PHP array, written on every settings save).
 *
 * @return string
 */
function semrs_server_cache_config_file() {
	return semrs_cache_dir() . 'config.php';
}

/**
 * Write the drop-in's settings: cache on, lifetime, exclusions, site host and headers.
 *
 * @return bool
 */
function semrs_server_write_cache_config() {
	// Read the options fresh: this also runs right after they are saved.
	$s    = array_merge( array( 'cache' => false, 'cache_ttl' => 10, 'cache_exclude' => '' ), (array) get_option( 'semrs_speed', array() ) );
	$sec  = (array) get_option( 'semrs_security', array() );
	$home = wp_parse_url( home_url( '/' ) );
	$cfg  = array(
		'on'      => ! empty( $s['cache'] ) && '' === semrs_cache_conflict(),
		'ttl'     => max( 1, min( 10, (int) $s['cache_ttl'] ) ) * HOUR_IN_SECONDS,
		'host'    => strtolower( $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) ),
		'exclude' => array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['cache_exclude'] ) ) ) ),
		'charset' => get_option( 'blog_charset', 'UTF-8' ),
		'headers' => ! empty( $sec['headers'] ) && ! ( defined( 'SEMRS_SECURITY_OFF' ) && SEMRS_SECURITY_OFF ),
		'hsts'    => ! empty( $sec['hsts'] ),
	);
	if ( ! wp_mkdir_p( semrs_cache_dir() ) ) {
		return false;
	}
	return false !== file_put_contents( semrs_server_cache_config_file(), "<?php\n// SEMRS early page cache settings. Written by SEMRS Core; do not edit.\nreturn " . var_export( $cfg, true ) . ";\n", LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions.error_log_var_export
}
add_action( 'update_option_semrs_speed', 'semrs_server_write_cache_config' );
add_action( 'add_option_semrs_speed', 'semrs_server_write_cache_config' );
add_action( 'update_option_semrs_security', 'semrs_server_write_cache_config' );

/**
 * Source of the drop-in. It repeats the page cache's rules without WordPress
 * functions, because it runs before WordPress has loaded them.
 *
 * @return string
 */
function semrs_server_dropin_source() {
	return <<<'PHP'
<?php
/**
 * SEMRS early page cache (advanced-cache.php). Written by SEMRS Core:
 * SEMRS → Speed → Server. Serves cached pages before plugins and the theme
 * load. Remove it there, or delete this file; define( 'SEMRS_CACHE_OFF', true )
 * in wp-config.php switches it off at once.
 */
defined( 'ABSPATH' ) || exit;
( function () {
	if ( ( defined( 'SEMRS_CACHE_OFF' ) && SEMRS_CACHE_OFF ) || ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) ) {
		return;
	}
	$dir = WP_CONTENT_DIR . '/cache/semrs/';
	if ( ! is_file( WP_CONTENT_DIR . '/plugins/semrs-core/semrs-core.php' ) || ! is_file( $dir . 'config.php' ) ) {
		return;
	}
	$cfg = include $dir . 'config.php';
	if ( ! is_array( $cfg ) || empty( $cfg['on'] ) ) {
		return;
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return;
	}
	foreach ( array_keys( $_COOKIE ) as $name ) {
		if ( preg_match( '/^(wordpress_logged_in_|wp-postpass_|comment_author_|woocommerce_items_in_cart|woocommerce_cart_hash|wp_woocommerce_session_|edd_items_in_cart|semrs_nocache)/', (string) $name ) ) {
			return;
		}
	}
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( $host !== $cfg['host'] ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = (string) parse_url( $uri, PHP_URL_PATH );
	$qs   = (string) parse_url( $uri, PHP_URL_QUERY );
	if ( '' !== $qs ) {
		parse_str( $qs, $args );
		foreach ( array_keys( $args ) as $k ) {
			if ( preg_match( '/^(utm_[a-z]+|gclid|gbraid|wbraid|fbclid|msclkid|mc_cid|mc_eid|_ga|_gl|ref)$/', (string) $k ) ) {
				unset( $args[ $k ] );
			}
		}
		if ( $args ) {
			return;
		}
	}
	if ( '' === $path || preg_match( '#(/wp-admin|/wp-login|/wp-json|/xmlrpc|\.php$|/feed/?$|sitemap|robots\.txt|llms\.txt|/cart/|/checkout/|/my-account/|\.xml$|\.txt$)#i', $path ) ) {
		return;
	}
	foreach ( (array) $cfg['exclude'] as $rule ) {
		$rule = '/' . ltrim( $rule, '/' );
		if ( '*' === substr( $rule, -1 ) ? 0 === strpos( $path, rtrim( $rule, '*' ) ) : rtrim( $path, '/' ) === rtrim( $rule, '/' ) ) {
			return;
		}
	}
	// Same phone test as wp_is_mobile().
	$ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	$mobile = isset( $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ) ? ( '?1' === $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ) : ( '' !== $ua && ( false !== strpos( $ua, 'Mobile' ) || false !== strpos( $ua, 'Android' ) || false !== strpos( $ua, 'Silk/' ) || false !== strpos( $ua, 'Kindle' ) || false !== strpos( $ua, 'BlackBerry' ) || false !== strpos( $ua, 'Opera Mini' ) || false !== strpos( $ua, 'Opera Mobi' ) ) );
	$file   = $dir . preg_replace( '/[^a-z0-9.\-]/', '_', $host ) . '/' . md5( $path ) . ( $mobile ? '-m' : '' ) . '.html';
	$fh     = @fopen( $file, 'rb' );
	if ( ! $fh ) {
		return;
	}
	$stat = fstat( $fh );
	if ( ! $stat || $stat['size'] < 100 || $stat['mtime'] + (int) $cfg['ttl'] < time() ) {
		fclose( $fh );
		return;
	}
	header( 'Content-Type: text/html; charset=' . $cfg['charset'] );
	header( 'X-SEMRS-Cache: HIT-EARLY' );
	if ( ! empty( $cfg['headers'] ) ) {
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), payment=(), usb=(), interest-cohort=()' );
		if ( ! empty( $cfg['hsts'] ) && ( ( isset( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ) || ( isset( $_SERVER['SERVER_PORT'] ) && '443' === (string) $_SERVER['SERVER_PORT'] ) ) ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}
	}
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $stat['mtime'] ) . ' GMT' );
	if ( 'HEAD' !== $method ) {
		fpassthru( $fh );
	}
	fclose( $fh );
	exit;
} )();
PHP;
}

/**
 * Is WP_CACHE switched on in wp-config.php (needed for any advanced-cache.php)?
 *
 * @return bool
 */
function semrs_server_wp_cache_on() {
	return defined( 'WP_CACHE' ) && WP_CACHE;
}

/**
 * wp-config.php path (the site folder or the one above it, as WordPress allows).
 *
 * @return string
 */
function semrs_server_wp_config() {
	if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
		return ABSPATH . 'wp-config.php';
	}
	return file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ? dirname( ABSPATH ) . '/wp-config.php' : '';
}

/**
 * Install or remove the early page cache.
 *
 * @param bool $on Install.
 * @return string '' on success, else the reason.
 */
function semrs_server_set_early_cache( $on ) {
	$drop = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
	if ( ! $on ) {
		if ( semrs_server_dropin_is_ours() ) {
			unlink( $drop ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return '';
	}
	if ( file_exists( $drop ) && ! semrs_server_dropin_is_ours() ) {
		return __( 'Another advanced-cache.php is in wp-content. Remove the old cache plugin\'s leftovers first.', 'semrs-core' );
	}
	if ( ! semrs_cache_enabled() ) {
		return __( 'Switch on the page cache first.', 'semrs-core' );
	}
	if ( ! semrs_server_write_cache_config() ) {
		return __( 'The cache folder cannot be written.', 'semrs-core' );
	}
	if ( false === file_put_contents( $drop, semrs_server_dropin_source(), LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return __( 'wp-content cannot be written. Ask your host to make it writable.', 'semrs-core' );
	}
	if ( ! semrs_server_wp_cache_on() ) {
		$cfg = semrs_server_wp_config();
		if ( '' === $cfg || ! wp_is_writable( $cfg ) ) {
			return __( 'Almost done: add define( \'WP_CACHE\', true ); to wp-config.php above the line "That\'s all, stop editing!". wp-config.php is not writable for SEMRS.', 'semrs-core' );
		}
		$src = (string) file_get_contents( $cfg ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! preg_match( '/define\(\s*[\'"]WP_CACHE[\'"]/', $src ) ) {
			semrs_server_backup( $cfg );
			$src = preg_replace( '/^<\?php\s*/', "<?php\ndefine( 'WP_CACHE', true ); // Added by SEMRS Core (early page cache).\n", $src, 1 );
			file_put_contents( $cfg, $src, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		} else {
			return __( 'Almost done: wp-config.php sets WP_CACHE to false. Change it to true to use the early page cache.', 'semrs-core' );
		}
	}
	return '';
}

/**
 * Remove our drop-in when SEMRS Core is deactivated, so no cached page outlives the plugin.
 */
function semrs_server_on_deactivate() {
	if ( semrs_server_dropin_is_ours() ) {
		unlink( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	$cfg = semrs_cache_dir() . 'config.php';
	if ( file_exists( $cfg ) ) {
		unlink( $cfg ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}
register_deactivation_hook( SEMRS_CORE_DIR . 'semrs-core.php', 'semrs_server_on_deactivate' );

/* -------------------------------------------------------------------------
 * Admin: SEMRS → Speed → Server
 * ---------------------------------------------------------------------- */

add_action(
	'admin_post_semrs_server',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-core' ) );
		}
		check_admin_referer( 'semrs_server' );
		$do  = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : '';
		$msg = '';
		if ( 'rules_on' === $do || 'rules_off' === $do ) {
			$msg = semrs_server_write_rules( 'rules_on' === $do ) ? $do : 'not_writable';
		} elseif ( 'clean' === $do ) {
			$done = semrs_server_clean_leftovers();
			$msg  = $done ? 'cleaned' : 'nothing';
			set_transient( 'semrs_server_cleaned', $done, 300 );
		} elseif ( 'early_on' === $do || 'early_off' === $do ) {
			$err = semrs_server_set_early_cache( 'early_on' === $do );
			if ( '' !== $err ) {
				set_transient( 'semrs_server_error', $err, 300 );
				$msg = 'error';
			} else {
				$msg = $do;
			}
		}
		if ( function_exists( 'semrs_cache_purge_all' ) ) {
			semrs_cache_purge_all();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-speed&tab=server&msg=' . $msg ) );
		exit;
	}
);

/**
 * Small form button.
 *
 * @param string $do    Action.
 * @param string $label Label.
 * @param string $class Button class.
 */
function semrs_server_button( $do, $label, $class = 'button' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:6px">';
	wp_nonce_field( 'semrs_server' );
	echo '<input type="hidden" name="action" value="semrs_server"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
}

/**
 * Server tab.
 */
function semrs_speed_server_tab() {
	$msg = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$notes = array(
		'rules_on'     => __( 'Server rules added to .htaccess. A backup of the old file was kept.', 'semrs-core' ),
		'rules_off'    => __( 'Server rules removed from .htaccess.', 'semrs-core' ),
		'not_writable' => __( '.htaccess cannot be written. Ask your host, or copy the rules below into it by hand.', 'semrs-core' ),
		'nothing'      => __( 'No leftovers to remove.', 'semrs-core' ),
		'early_on'     => __( 'Early page cache switched on.', 'semrs-core' ),
		'early_off'    => __( 'Early page cache switched off.', 'semrs-core' ),
	);
	if ( 'cleaned' === $msg ) {
		/* translators: %s: removed items */
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Removed: %s. Backups were kept next to each file.', 'semrs-core' ), implode( ', ', (array) get_transient( 'semrs_server_cleaned' ) ) ) ) . '</p></div>';
	} elseif ( 'error' === $msg ) {
		echo '<div class="notice notice-warning"><p>' . esc_html( (string) get_transient( 'semrs_server_error' ) ) . '</p></div>';
	} elseif ( isset( $notes[ $msg ] ) ) {
		echo '<div class="notice ' . ( 'not_writable' === $msg ? 'notice-warning' : 'notice-success' ) . '"><p>' . esc_html( $notes[ $msg ] ) . '</p></div>';
	}
	$reads = semrs_server_reads_htaccess();
	$left  = semrs_server_leftovers();
	$early = semrs_server_dropin_is_ours() && semrs_server_wp_cache_on();
	?>
	<p style="max-width:820px"><?php esc_html_e( 'Speed settings for the web server itself. Each change is made only when you click its button, and a dated backup of the file is kept (for example .htaccess.semrs-bak-20261107-101500).', 'semrs-core' ); ?></p>

	<h2><?php esc_html_e( 'Leftovers of old cache plugins', 'semrs-core' ); ?></h2>
	<?php if ( ! $left['htaccess'] && '' === $left['dropin'] ) : ?>
		<p><span class="dashicons dashicons-yes-alt" style="color:#00813a"></span> <?php esc_html_e( 'None found. No old cache plugin rules or drop-ins are left on this site.', 'semrs-core' ); ?></p>
	<?php else : ?>
		<p style="max-width:820px">
			<?php
			$items = $left['htaccess'];
			if ( $left['dropin'] ) {
				$items[] = $left['dropin'] . ' advanced-cache.php';
			}
			/* translators: %s: plugin names */
			echo esc_html( sprintf( __( 'Found rules from plugins that are no longer active: %s. They can conflict with SEMRS Speed, slow the server and announce a plugin you no longer use.', 'semrs-core' ), implode( ', ', $items ) ) );
			?>
		</p>
		<p><?php semrs_server_button( 'clean', __( 'Remove leftovers', 'semrs-core' ), 'button button-primary' ); ?></p>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Server rules (.htaccess)', 'semrs-core' ); ?></h2>
	<?php if ( ! $reads ) : ?>
		<p style="max-width:820px"><?php esc_html_e( 'This server does not use .htaccess files (for example Nginx). Ask your host to switch on compression and browser caching; the rules below show what to set.', 'semrs-core' ); ?></p>
	<?php else : ?>
		<p style="max-width:820px"><?php esc_html_e( 'Compression (Brotli and gzip), a year of browser caching for images, fonts, CSS and JavaScript (WordPress adds a version to their addresses, so updates still show at once), no ETags, Vary: Accept-Encoding, CORS for fonts and the right types for AVIF, WebP and WOFF2.', 'semrs-core' ); ?></p>
		<p>
			<?php
			if ( semrs_server_rules_present() ) {
				echo '<span class="dashicons dashicons-yes-alt" style="color:#00813a"></span> ' . esc_html__( 'On.', 'semrs-core' ) . ' ';
				semrs_server_button( 'rules_on', __( 'Update rules', 'semrs-core' ) );
				semrs_server_button( 'rules_off', __( 'Remove rules', 'semrs-core' ), 'button button-link-delete' );
			} else {
				semrs_server_button( 'rules_on', __( 'Add server rules', 'semrs-core' ), 'button button-primary' );
			}
			?>
		</p>
	<?php endif; ?>
	<details style="max-width:820px"><summary><?php esc_html_e( 'Show the rules', 'semrs-core' ); ?></summary><pre style="background:#fff;border:1px solid #dcdcde;padding:10px;max-height:320px;overflow:auto"><?php echo esc_html( "# BEGIN SEMRS Speed\n" . implode( "\n", semrs_server_rules() ) . "\n# END SEMRS Speed" ); ?></pre></details>

	<h2><?php esc_html_e( 'Early page cache', 'semrs-core' ); ?></h2>
	<p style="max-width:820px"><?php esc_html_e( 'Serves cached pages before any plugin or the theme loads, so the server answers faster (lower Time to First Byte). It uses the same cached pages and rules as the page cache; logged-in users, carts and forms are never served from it. It adds a small file, wp-content/advanced-cache.php, and switches on WP_CACHE in wp-config.php (with a backup).', 'semrs-core' ); ?></p>
	<p>
		<?php
		if ( $early ) {
			echo '<span class="dashicons dashicons-yes-alt" style="color:#00813a"></span> ' . esc_html__( 'On.', 'semrs-core' ) . ' ';
			semrs_server_button( 'early_off', __( 'Switch off', 'semrs-core' ), 'button button-link-delete' );
		} elseif ( ! semrs_cache_enabled() ) {
			esc_html_e( 'Switch on the page cache (Page cache tab) first.', 'semrs-core' );
		} else {
			semrs_server_button( 'early_on', __( 'Switch on early page cache', 'semrs-core' ), 'button button-primary' );
		}
		?>
	</p>
	<p class="description" style="max-width:820px"><?php esc_html_e( 'Emergency switch: define( \'SEMRS_CACHE_OFF\', true ); in wp-config.php turns off both page caches at once. Deactivating SEMRS Core removes the early cache file automatically.', 'semrs-core' ); ?></p>
	<?php
}
