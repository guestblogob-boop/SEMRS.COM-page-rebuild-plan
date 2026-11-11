<?php
/**
 * SEMRS Backups: Pro extras.
 *
 * - Rollback copies: before WordPress updates a plugin or theme (by hand or
 *   automatically), the old version is zipped, so one click rolls it back.
 * - Import: restore or migrate from UpdraftPlus backup sets and
 *   All-in-One WP Migration .wpress files.
 * - Rescan storage: find backups in S3, Dropbox, Google Drive, FTP, SFTP or
 *   WebDAV (for example from a fresh WordPress after the server was lost).
 * - WP-CLI: wp semrs backup run|list|restore|rescan|key|pull.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Backup sets on this server or in history: id => info.
 *
 * @return array
 */
function semrs_bk_sets() {
	$hist = (array) get_option( 'semrs_bk_history', array() );
	$dir  = semrs_bk_dir();
	foreach ( (array) glob( $dir . '*-manifest.json' ) as $mf ) {
		$id = substr( basename( $mf ), 0, -14 );
		if ( ! isset( $hist[ $id ] ) ) {
			$m           = json_decode( (string) file_get_contents( $mf ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$hist[ $id ] = array( 'time' => (int) ( $m['created'] ?? filemtime( $mf ) ), 'by' => 'uploaded', 'size' => (int) ( $m['size'] ?? 0 ), 'files' => array_merge( array_keys( (array) ( $m['files'] ?? array() ) ), array( basename( $mf ) ) ), 'parts' => (array) ( $m['parts'] ?? array() ), 'dests' => array(), 'home' => $m['home'] ?? '', 'base' => (string) ( $m['base'] ?? '' ) );
		}
	}
	foreach ( $hist as $id => &$h ) {
		$h['local'] = is_file( $dir . $id . '-manifest.json' );
	}
	unset( $h );
	uasort( $hist, function ( $a, $b ) { return $b['time'] <=> $a['time']; } );
	return $hist;
}

/**
 * Check a backup without restoring it: every zip opens and passes its
 * consistency check, encrypted files decrypt, and the database file is complete.
 *
 * @param string $id   Set id.
 * @param string $pass Encryption password ('' = saved one).
 * @return array|WP_Error Lines of the report.
 */
function semrs_bk_verify( $id, $pass = '' ) {
	$dir = semrs_bk_dir();
	$mf  = $dir . $id . '-manifest.json';
	$m   = is_file( $mf ) ? json_decode( (string) file_get_contents( $mf ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! is_array( $m ) ) {
		return new WP_Error( 'semrs', __( 'This backup is not on this server.', 'semrs-pro' ) );
	}
	@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$out = array();
	$bad = 0;
	foreach ( (array) $m['files'] as $f => $size ) {
		$path = $dir . $f;
		if ( ! is_file( $path ) || (int) filesize( $path ) !== (int) $size ) {
			$out[] = '✗ ' . $f . ': ' . __( 'missing or wrong size', 'semrs-pro' );
			++$bad;
			continue;
		}
		$tmp = '';
		if ( '.enc' === substr( $f, -4 ) ) {
			$tmp = $dir . 'verify-' . wp_generate_password( 6, false ) . '.zip';
			if ( ! semrs_bk_crypt_file( $path, $tmp, '' !== $pass ? $pass : (string) semrs_bk_settings()['pass'], false ) ) {
				$out[] = '✗ ' . $f . ': ' . __( 'cannot be decrypted with this password', 'semrs-pro' );
				++$bad;
				continue;
			}
			$path = $tmp;
		}
		$zip = new ZipArchive();
		$res = $zip->open( $path, ZipArchive::CHECKCONS );
		if ( true !== $res ) {
			$out[] = '✗ ' . $f . ': ' . __( 'damaged zip', 'semrs-pro' ) . ' (' . $res . ')';
			++$bad;
		} else {
			$note = $zip->numFiles . ' ' . __( 'files', 'semrs-pro' );
			if ( false !== strpos( $f, '-db.zip' ) ) {
				$st   = $zip->statName( 'database.sql' );
				$fh   = $zip->getStream( 'database.sql' );
				$tail = '';
				$n    = 0;
				while ( $fh && ! feof( $fh ) ) {
					$chunk = (string) fread( $fh, MB_IN_BYTES ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$n    += substr_count( $chunk, ";\n" );
					$tail  = substr( $tail . $chunk, -64 );
				}
				if ( $fh ) {
					fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				}
				$ok   = ";\n" === substr( $tail, -2 );
				$note = sprintf( '%s SQL, %d %s', size_format( (int) ( $st['size'] ?? 0 ) ), $n, __( 'statements', 'semrs-pro' ) ) . ( $ok ? '' : ' — ' . __( 'ends early!', 'semrs-pro' ) );
				$bad += $ok ? 0 : 1;
			}
			$out[] = ( false === strpos( $note, '!' ) ? '✓ ' : '✗ ' ) . $f . ': ' . $note . ( $tmp ? ' (' . __( 'decrypted', 'semrs-pro' ) . ')' : '' );
			$zip->close();
		}
		if ( $tmp ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
	}
	array_unshift( $out, $bad ? __( 'Problems found:', 'semrs-pro' ) : __( 'Backup checked: every file is complete and readable.', 'semrs-pro' ) );
	return $out;
}

/*
 * Rollback copies before updates.
 */

/**
 * Zip a folder into the backups folder.
 *
 * @param string $src  Folder.
 * @param string $zipf Zip path.
 * @param string $root Name of the top folder inside the zip.
 * @return bool
 */
function semrs_bk_zip_folder( $src, $zipf, $root ) {
	$zip = new ZipArchive();
	if ( true !== $zip->open( $zipf, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		return false;
	}
	$src = untrailingslashit( str_replace( '\\', '/', $src ) );
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		if ( $f->isFile() ) {
			$zip->addFile( $f->getPathname(), $root . '/' . ltrim( substr( str_replace( '\\', '/', $f->getPathname() ), strlen( $src ) ), '/' ) );
		}
	}
	return $zip->close();
}

/**
 * Folder size (stops counting past $limit).
 *
 * @param string $dir   Folder.
 * @param int    $limit Bytes.
 * @return int
 */
function semrs_bk_dir_size( $dir, $limit ) {
	$n = 0;
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
		$n += $f->getSize();
		if ( $n > $limit ) {
			break;
		}
	}
	return $n;
}

/**
 * Save a rollback copy of a plugin or theme.
 *
 * @param string $type plugin|theme.
 * @param string $slug Folder name.
 * @param string $why  before-update|before-rollback.
 * @return bool
 */
function semrs_bk_rollback_copy( $type, $slug, $why = 'before-update' ) {
	$base = 'plugin' === $type ? WP_PLUGIN_DIR : get_theme_root();
	$src  = $base . '/' . $slug;
	$dir  = semrs_bk_dir();
	if ( '' === $slug || ! is_dir( $src ) || '' === $dir || ! class_exists( 'ZipArchive' ) || semrs_bk_dir_size( $src, 300 * MB_IN_BYTES ) > 300 * MB_IN_BYTES ) {
		return false;
	}
	if ( 'plugin' === $type ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$ver = '';
		foreach ( get_plugins( '/' . $slug ) as $data ) {
			$ver = (string) $data['Version'];
			break;
		}
	} else {
		$ver = (string) wp_get_theme( $slug )->get( 'Version' );
	}
	$name = 'rollback-' . $type . '-' . sanitize_file_name( $slug ) . '-' . preg_replace( '/[^0-9a-z.]/i', '', $ver ) . '-' . gmdate( 'YmdHis' ) . '.zip';
	if ( ! semrs_bk_zip_folder( $src, $dir . $name, $slug ) ) {
		return false;
	}
	$list = (array) get_option( 'semrs_bk_rollbacks', array() );
	$list[ $name ] = array( 'type' => $type, 'slug' => $slug, 'ver' => $ver, 'time' => time(), 'why' => $why );
	// Keep the newest 3 copies per plugin or theme.
	$mine = array_filter( $list, function ( $r ) use ( $type, $slug ) { return $r['type'] === $type && $r['slug'] === $slug; } );
	uasort( $mine, function ( $a, $b ) { return $b['time'] <=> $a['time']; } );
	foreach ( array_slice( array_keys( $mine ), 3 ) as $old ) {
		@unlink( $dir . $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		unset( $list[ $old ] );
	}
	update_option( 'semrs_bk_rollbacks', $list, false );
	return true;
}

add_filter(
	'upgrader_pre_install',
	function ( $ret, $extra ) {
		if ( is_wp_error( $ret ) || '1' !== semrs_bk_settings()['preupdate'] ) {
			return $ret;
		}
		if ( ! empty( $extra['plugin'] ) && false !== strpos( $extra['plugin'], '/' ) ) {
			semrs_bk_rollback_copy( 'plugin', dirname( $extra['plugin'] ) );
		} elseif ( ! empty( $extra['theme'] ) ) {
			semrs_bk_rollback_copy( 'theme', (string) $extra['theme'] );
		}
		return $ret;
	},
	5,
	2
);

/**
 * Roll a plugin or theme back to a saved copy (the current version is saved first).
 *
 * @param string $name Rollback file.
 * @return true|WP_Error
 */
function semrs_bk_rollback( $name ) {
	$list = (array) get_option( 'semrs_bk_rollbacks', array() );
	$r    = $list[ $name ] ?? null;
	$dir  = semrs_bk_dir();
	if ( ! $r || ! is_file( $dir . $name ) ) {
		return new WP_Error( 'semrs', __( 'That copy is no longer available.', 'semrs-pro' ) );
	}
	$base = 'plugin' === $r['type'] ? WP_PLUGIN_DIR : get_theme_root();
	$cur  = $base . '/' . $r['slug'];
	if ( is_dir( $cur ) ) {
		semrs_bk_rollback_copy( $r['type'], $r['slug'], 'before-rollback' );
		$tmp = $cur . '-semrs-replaced-' . time();
		if ( ! @rename( $cur, $tmp ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			return new WP_Error( 'semrs', __( 'The current version could not be moved aside. Check the folder permissions.', 'semrs-pro' ) );
		}
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $dir . $name ) || ! $zip->extractTo( $base ) ) {
		if ( isset( $tmp ) ) {
			@rename( $tmp, $cur ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
		return new WP_Error( 'semrs', __( 'The copy could not be unpacked.', 'semrs-pro' ) );
	}
	$zip->close();
	if ( isset( $tmp ) ) {
		semrs_bk_rmdir( $tmp ); // The newer version is kept as a rollback copy above.
	}
	if ( function_exists( 'wp_clean_plugins_cache' ) ) {
		wp_clean_plugins_cache();
	}
	return true;
}

/*
 * Import from UpdraftPlus and All-in-One WP Migration.
 */

/**
 * Backups from other plugins found in the backups folder.
 *
 * @return array key => [type, label, files].
 */
function semrs_bk_imports() {
	$dir = semrs_bk_dir();
	$out = array();
	foreach ( (array) glob( $dir . '*.wpress' ) as $f ) {
		$out[ basename( $f ) ] = array( 'wpress', 'All-in-One WP Migration: ' . basename( $f ), array( basename( $f ) ), filesize( $f ) );
	}
	foreach ( (array) glob( $dir . 'backup_*-db*.gz' ) as $f ) {
		$stem  = preg_replace( '/-db\d*\.gz$/', '', basename( $f ) );
		$files = array_map( 'basename', (array) glob( $dir . $stem . '-*' ) );
		$size  = 0;
		foreach ( $files as $x ) {
			$size += (int) filesize( $dir . $x );
		}
		$out[ $stem ] = array( 'updraft', 'UpdraftPlus: ' . $stem, $files, $size );
	}
	return $out;
}

/**
 * Start restoring a backup made by another plugin.
 *
 * @param string $key Import key (from semrs_bk_imports()).
 * @return true|WP_Error
 */
function semrs_bk_import_start( $key ) {
	$all = semrs_bk_imports();
	if ( ! isset( $all[ $key ] ) ) {
		return new WP_Error( 'semrs', __( 'That backup is no longer in the folder.', 'semrs-pro' ) );
	}
	global $wpdb;
	$dir  = semrs_bk_dir();
	$item = $all[ $key ];
	$job  = array(
		'type'    => 'restore',
		'id'      => 'import',
		'dir'     => $dir,
		'm'       => array( 'prefix' => $wpdb->prefix, 'home' => home_url(), 'abspath' => ABSPATH ),
		'parts'   => array( 'db', 'plugins', 'themes', 'uploads', 'mu', 'others' ),
		'queue'   => array(),
		'map'     => array(),
		'db'      => true,
		'sqlmode' => 'generic',
		'migrate' => false,
		'phase'   => 'files',
		'cur'     => array(),
		'started' => time(),
		'touched' => time(),
		'token'   => wp_generate_password( 32, false ),
		'log'     => array(),
		'target'  => array(
			'home'    => home_url(),
			'siteurl' => site_url(),
			'abspath' => ABSPATH,
			'prefix'  => $wpdb->prefix,
			'bkdir'   => get_option( 'semrs_bk_dir' ),
			'hist'    => get_option( 'semrs_bk_history', array() ),
			'dests'   => get_option( 'semrs_bk_dest' ),
			'set'     => get_option( 'semrs_bk' ),
		),
	);
	if ( 'wpress' === $item[0] ) {
		$job['phase']   = 'wpress';
		$job['wpress']  = $dir . $item[2][0];
		$job['sqlfile'] = $dir . 'database.sql';
		$job['m']['prefix'] = 'SERVMASK_PREFIX_';
	} else {
		$map = array( 'plugins' => 'plugins|plugins/', 'themes' => 'themes|themes/', 'uploads' => 'uploads|uploads/', 'mu-plugins' => 'mu|mu-plugins/', 'others' => 'others|' );
		foreach ( $item[2] as $f ) {
			if ( preg_match( '/-(plugins|themes|uploads|mu-plugins|others)\d*\.zip$/', $f, $mm ) ) {
				$job['queue'][]     = $f;
				$job['map'][ $f ]   = $map[ $mm[1] ];
			} elseif ( preg_match( '/-db\d*\.gz$/', $f ) ) {
				$job['gz'] = $dir . $f;
			}
		}
		if ( empty( $job['gz'] ) ) {
			$job['db'] = false;
		} else {
			// The header tells the old address and table prefix.
			$gz = gzopen( $job['gz'], 'rb' );
			for ( $i = 0; $i < 60 && $gz && ! gzeof( $gz ); $i++ ) {
				$l = (string) gzgets( $gz, 4096 );
				if ( preg_match( '/^# (Home URL|Backup of|Table prefix|ABSPATH): (.+)$/', trim( $l ), $mm ) ) {
					$k = array( 'Home URL' => 'home', 'Backup of' => 'home', 'Table prefix' => 'prefix', 'ABSPATH' => 'abspath' )[ $mm[1] ];
					if ( 'home' !== $k || 'Home URL' === $mm[1] || ! isset( $job['m']['_home'] ) ) {
						$job['m'][ $k ] = trim( $mm[2] );
					}
					if ( 'Home URL' === $mm[1] ) {
						$job['m']['_home'] = 1;
					}
				}
			}
			if ( $gz ) {
				gzclose( $gz );
			}
			$job['phase']   = 'gunzip';
			$job['sqlfile'] = $dir . 'database.sql';
		}
		$job['migrate'] = untrailingslashit( $job['m']['home'] ) !== untrailingslashit( home_url() );
	}
	semrs_bk_log( $job, sprintf( 'Import of %s started.', $item[1] ) );
	semrs_bk_save_job( $job );
	return true;
}

/**
 * UpdraftPlus database: unpack the .gz once.
 *
 * @param array $job Job.
 * @return array
 */
function semrs_bk_restore_gunzip( $job ) {
	@set_time_limit( 900 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$in  = gzopen( $job['gz'], 'rb' );
	$out = fopen( $job['sqlfile'], 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	while ( $in && ! gzeof( $in ) ) {
		fwrite( $out, (string) gzread( $in, MB_IN_BYTES ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( $in ) {
		gzclose( $in );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	semrs_bk_log( $job, 'Database file unpacked.' );
	$job['phase'] = 'files';
	return $job;
}

/**
 * All-in-One WP Migration .wpress: unpack entry by entry (resumes mid-file).
 * Format: 4377-byte header (name 255, size 14, mtime 12, path 4096), then the
 * file, repeated; an all-zero header ends the archive.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_restore_wpress( $job, $end ) {
	$c   = $job['cur'] + array( 'pos' => 0, 'left' => 0, 'to' => '', 'n' => 0 );
	$fh  = fopen( $job['wpress'], 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$own = basename( dirname( dirname( __DIR__ ) ) );
	fseek( $fh, $c['pos'] );
	$done = false;
	while ( microtime( true ) < $end ) {
		if ( $c['left'] > 0 ) {
			$data = (string) fread( $fh, (int) min( $c['left'], MB_IN_BYTES ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( '' === $data ) {
				throw new Exception( 'The .wpress file ends too early (damaged or incomplete upload).' );
			}
			if ( '' !== $c['to'] ) {
				file_put_contents( $c['to'], $data, FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
			$c['left'] -= strlen( $data );
			$c['pos']   = ftell( $fh );
			continue;
		}
		if ( empty( $job['pkg'] ) && is_file( $job['dir'] . 'package.json' ) && 'package.json' === basename( (string) $c['to'] ) ) {
			$pkg = json_decode( (string) file_get_contents( $job['dir'] . 'package.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! empty( $pkg['Encrypted'] ) || ! empty( $pkg['Compression']['Enabled'] ) ) {
				throw new Exception( __( 'This .wpress file is encrypted or compressed by All-in-One WP Migration Pro and cannot be read. Export it again without encryption or compression.', 'semrs-pro' ) );
			}
			$job['pkg'] = 1;
		}
		$h = fread( $fh, 4377 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $h || strlen( $h ) < 4377 || '' === trim( $h, "\0" ) ) {
			$done = true;
			break;
		}
		$name = rtrim( substr( $h, 0, 255 ), "\0" );
		$size = (int) trim( substr( $h, 255, 14 ), "\0 " );
		$path = rtrim( substr( $h, 281, 4096 ), "\0" );
		$rel  = ltrim( str_replace( '\\', '/', ( '.' === $path || '' === $path ? '' : $path . '/' ) . $name ), '/' );
		$to   = '';
		if ( false === strpos( $rel, '..' ) ) {
			if ( in_array( $rel, array( 'package.json', 'database.sql', 'multisite.json', 'blogs.json', 'settings.json' ), true ) ) {
				$to = $job['dir'] . $rel;
			} elseif ( 0 !== strpos( $rel, 'plugins/' . $own . '/' ) && ! preg_match( '#^(?:ai1wm-backups|semrs-backups-|semrs-security-|cache/|upgrade/)#', $rel ) ) {
				$to = trailingslashit( WP_CONTENT_DIR ) . $rel;
			}
		}
		if ( '' !== $to ) {
			wp_mkdir_p( dirname( $to ) );
			file_put_contents( $to, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		$c['to']   = $to;
		$c['left'] = $size;
		$c['pos']  = ftell( $fh );
		++$c['n'];
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( $done ) {
		$pkg = json_decode( (string) @file_get_contents( $job['dir'] . 'package.json' ), true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		if ( is_array( $pkg ) ) {
			$job['m']['home']    = (string) ( $pkg['HomeURL'] ?? $pkg['SiteURL'] ?? home_url() );
			$job['m']['abspath'] = (string) ( $pkg['WordPress']['Absolute'] ?? ABSPATH );
			@unlink( $job['dir'] . 'package.json' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
		$job['migrate'] = untrailingslashit( $job['m']['home'] ) !== untrailingslashit( home_url() );
		/* translators: %d: files */
		semrs_bk_log( $job, sprintf( 'Unpacked %d files from the .wpress archive.', $c['n'] ) );
		$job['phase'] = is_file( $job['sqlfile'] ) ? 'db' : 'finish';
		$job['db']    = is_file( $job['sqlfile'] );
		$c            = array();
	}
	$job['cur'] = $c;
	return $job;
}

/*
 * Rescan remote storage.
 */

/**
 * Backup files in a destination: name => remote id.
 *
 * @param string $d Destination.
 * @return array|WP_Error
 */
function semrs_bk_remote_list( $d ) {
	$o      = semrs_bk_dest_opts( $d );
	$folder = semrs_bk_remote_folder( $o );
	$out    = array();
	switch ( $d ) {
		case 's3':
			$token = '';
			do {
				$q = array( 'list-type' => '2', 'prefix' => $folder . '/' );
				if ( $token ) {
					$q['continuation-token'] = $token;
				}
				$res = semrs_bk_s3_call( $o, 'GET', '', $q );
				if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
					return semrs_bk_http_error( $res, 'S3' );
				}
				$xml = (string) wp_remote_retrieve_body( $res );
				preg_match_all( '#<Key>([^<]+)</Key>#', $xml, $mm );
				foreach ( $mm[1] as $k ) {
					$out[ basename( html_entity_decode( $k ) ) ] = '';
				}
				$token = preg_match( '#<NextContinuationToken>([^<]+)#', $xml, $t ) ? html_entity_decode( $t[1] ) : '';
			} while ( $token );
			break;
		case 'dropbox':
			$tok = semrs_bk_dropbox_token( $o );
			if ( is_wp_error( $tok ) ) {
				return $tok;
			}
			$url  = 'https://api.dropboxapi.com/2/files/list_folder';
			$body = array( 'path' => '/' . $folder );
			do {
				$res = wp_remote_post( $url, array( 'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ), 'timeout' => 30 ) );
				$j   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
				if ( ! isset( $j['entries'] ) ) {
					return semrs_bk_http_error( $res, 'Dropbox' );
				}
				foreach ( $j['entries'] as $e ) {
					$out[ $e['name'] ] = '';
				}
				$url  = 'https://api.dropboxapi.com/2/files/list_folder/continue';
				$body = array( 'cursor' => $j['cursor'] ?? '' );
			} while ( ! empty( $j['has_more'] ) );
			break;
		case 'gdrive':
			$tok = semrs_bk_gdrive_token( $o );
			if ( is_wp_error( $tok ) ) {
				return $tok;
			}
			if ( empty( $o['folder_id'] ) ) {
				return $out;
			}
			$page = '';
			do {
				$res = wp_remote_get( add_query_arg( array( 'q' => "'" . $o['folder_id'] . "' in parents and trashed=false", 'fields' => 'nextPageToken,files(id,name)', 'pageSize' => 1000, 'pageToken' => $page ), 'https://www.googleapis.com/drive/v3/files' ), array( 'headers' => array( 'Authorization' => 'Bearer ' . $tok ), 'timeout' => 30 ) );
				$j   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
				if ( ! isset( $j['files'] ) ) {
					return semrs_bk_http_error( $res, 'Google Drive' );
				}
				foreach ( $j['files'] as $f ) {
					$out[ $f['name'] ] = $f['id'];
				}
				$page = (string) ( $j['nextPageToken'] ?? '' );
			} while ( $page );
			break;
		case 'ftp':
			$c = semrs_bk_ftp_connect( $o );
			if ( is_wp_error( $c ) ) {
				return $c;
			}
			foreach ( (array) ftp_nlist( $c, '.' ) as $f ) {
				$out[ basename( $f ) ] = '';
			}
			ftp_close( $c );
			break;
		case 'sftp':
			$c = semrs_bk_sftp_connect( $o );
			if ( is_wp_error( $c ) ) {
				return $c;
			}
			foreach ( (array) @scandir( $c[1] ) as $f ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
				$out[ $f ] = '';
			}
			break;
		case 'webdav':
			$url = trailingslashit( (string) ( $o['url'] ?? '' ) ) . implode( '/', array_map( 'rawurlencode', explode( '/', $folder ) ) ) . '/';
			$res = wp_remote_request( $url, array( 'method' => 'PROPFIND', 'headers' => semrs_bk_webdav_auth( $o ) + array( 'Depth' => '1' ), 'timeout' => 30 ) );
			preg_match_all( '#<(?:\w+:)?href>([^<]+)</(?:\w+:)?href>#i', (string) wp_remote_retrieve_body( $res ), $mm );
			foreach ( $mm[1] as $h ) {
				$out[ rawurldecode( basename( $h ) ) ] = '';
			}
			break;
		default:
			return new WP_Error( 'semrs', __( 'This storage cannot be scanned.', 'semrs-pro' ) );
	}
	return $out;
}

/**
 * Add backups found in storage to the list (they can then be fetched and restored).
 *
 * @param string $d Destination.
 * @return int|WP_Error Backups found that were not listed yet.
 */
function semrs_bk_rescan( $d ) {
	$files = semrs_bk_remote_list( $d );
	if ( is_wp_error( $files ) ) {
		return $files;
	}
	$hist = (array) get_option( 'semrs_bk_history', array() );
	$new  = 0;
	$dir  = semrs_bk_dir();
	foreach ( $files as $name => $rid ) {
		if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2}-\d{4}-[0-9a-z]{4})-manifest\.json$/', $name, $mm ) ) {
			continue;
		}
		$id = $mm[1];
		if ( isset( $hist[ $id ] ) ) {
			if ( ! in_array( $d, (array) $hist[ $id ]['dests'], true ) ) {
				$hist[ $id ]['dests'][] = $d;
			}
			continue;
		}
		$tmp = $dir . 'scan-' . $name;
		$st  = array();
		$res = semrs_bk_remote_get( $d, $name, $rid, $tmp, $st, microtime( true ) + 30 );
		$m   = true === $res ? json_decode( (string) file_get_contents( $tmp ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		if ( ! is_array( $m ) ) {
			continue;
		}
		$list = array_merge( array_keys( (array) $m['files'] ), array( $name ) );
		$ids  = array();
		foreach ( $list as $f ) {
			$ids[ $f ] = (string) ( $files[ $f ] ?? '' );
		}
		$hist[ $id ] = array( 'time' => (int) ( $m['created'] ?? time() ), 'by' => 'remote', 'size' => (int) ( $m['size'] ?? 0 ), 'files' => $list, 'parts' => (array) ( $m['parts'] ?? array() ), 'dests' => array( $d ), 'remote' => array( $d => $ids ), 'local' => false, 'home' => (string) ( $m['home'] ?? '' ), 'base' => (string) ( $m['base'] ?? '' ) );
		++$new;
	}
	update_option( 'semrs_bk_history', $hist, false );
	return $new;
}

/*
 * WP-CLI.
 */
if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
	/**
	 * Run the current job to the end, printing the log.
	 */
	$semrs_bk_cli_run = function () {
		$seen = 0;
		do {
			$job = semrs_bk_step( 30 );
			$log = (array) ( $job['log'] ?? array() );
			foreach ( array_slice( $log, $seen ) as $l ) {
				WP_CLI::log( $l );
			}
			$seen = count( $log );
		} while ( $job && 'done' !== $job['phase'] );
		if ( ! empty( $job['error'] ) ) {
			semrs_bk_save_job( null );
			WP_CLI::error( $job['error'] );
		}
		semrs_bk_save_job( null );
		WP_CLI::success( 'Done.' );
	};
	WP_CLI::add_command(
		'semrs backup',
		new class( $semrs_bk_cli_run ) {
			/**
			 * Runner.
			 *
			 * @var callable
			 */
			private $run;

			/**
			 * Constructor.
			 *
			 * @param callable $run Runner.
			 */
			public function __construct( $run ) {
				$this->run = $run;
			}

			/**
			 * Make a backup now.
			 *
			 * ## OPTIONS
			 *
			 * [--parts=<parts>]
			 * : Comma list of db,plugins,themes,uploads,mu,others. Default: your settings.
			 *
			 * @param array $args  Args.
			 * @param array $assoc Options.
			 */
			public function run( $args, $assoc ) {
				$parts = isset( $assoc['parts'] ) ? array_values( array_intersect( array( 'db', 'plugins', 'themes', 'uploads', 'mu', 'others' ), explode( ',', $assoc['parts'] ) ) ) : null;
				$res   = semrs_bk_start( 'manual', $parts );
				if ( is_wp_error( $res ) ) {
					WP_CLI::error( $res->get_error_message() );
				}
				( $this->run )();
			}

			/**
			 * List backups.
			 */
			public function list() {
				$rows = array();
				foreach ( semrs_bk_sets() as $id => $h ) {
					$rows[] = array( 'id' => $id, 'date' => wp_date( 'Y-m-d H:i', (int) $h['time'] ), 'parts' => implode( ',', (array) $h['parts'] ), 'size' => size_format( (int) $h['size'] ), 'local' => $h['local'] ? 'yes' : 'no', 'storage' => implode( ',', (array) ( $h['dests'] ?? array() ) ) );
				}
				WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'date', 'parts', 'size', 'local', 'storage' ) );
			}

			/**
			 * Restore a backup on this server.
			 *
			 * ## OPTIONS
			 *
			 * <id>
			 * : Backup id from "wp semrs backup list".
			 *
			 * [--parts=<parts>]
			 * : Comma list. Default: everything in the backup.
			 *
			 * [--password=<password>]
			 * : For encrypted database backups.
			 *
			 * [--yes]
			 * : Do not ask for confirmation.
			 *
			 * @param array $args  Args.
			 * @param array $assoc Options.
			 */
			public function restore( $args, $assoc ) {
				$sets = semrs_bk_sets();
				if ( ! isset( $sets[ $args[0] ] ) ) {
					WP_CLI::error( 'Unknown backup.' );
				}
				WP_CLI::confirm( 'Replace this site with backup ' . $args[0] . '?', $assoc );
				$parts = isset( $assoc['parts'] ) ? explode( ',', $assoc['parts'] ) : (array) $sets[ $args[0] ]['parts'];
				$res   = semrs_bk_restore_start( $args[0], $parts, false, (string) ( $assoc['password'] ?? '' ) );
				if ( is_wp_error( $res ) ) {
					WP_CLI::error( $res->get_error_message() );
				}
				( $this->run )();
			}

			/**
			 * Find backups in remote storage.
			 *
			 * ## OPTIONS
			 *
			 * <storage>
			 * : s3, dropbox, gdrive, ftp, sftp or webdav.
			 *
			 * @param array $args Args.
			 */
			public function rescan( $args ) {
				$n = semrs_bk_rescan( $args[0] );
				if ( is_wp_error( $n ) ) {
					WP_CLI::error( $n->get_error_message() );
				}
				WP_CLI::success( $n . ' new backup(s) found.' );
			}

			/**
			 * Make a migration package and print its key.
			 */
			public function key() {
				$res = semrs_bk_start( 'migrate', array( 'db', 'plugins', 'themes', 'uploads', 'mu', 'others' ) );
				if ( is_wp_error( $res ) ) {
					WP_CLI::error( $res->get_error_message() );
				}
				$id = semrs_bk_job()['id'];
				do {
					$job = semrs_bk_step( 30 );
				} while ( $job && 'done' !== $job['phase'] );
				semrs_bk_save_job( null );
				if ( ! empty( $job['error'] ) ) {
					WP_CLI::error( $job['error'] );
				}
				WP_CLI::log( semrs_bk_make_key( $id ) );
			}

			/**
			 * Copy a site here with a migration key (replaces this site).
			 *
			 * ## OPTIONS
			 *
			 * <key>
			 * : Migration key from the old site.
			 *
			 * [--yes]
			 * : Do not ask for confirmation.
			 *
			 * @param array $args  Args.
			 * @param array $assoc Options.
			 */
			public function pull( $args, $assoc ) {
				WP_CLI::confirm( 'Replace this site with the old site?', $assoc );
				$res = semrs_bk_pull_start( $args[0] );
				if ( is_wp_error( $res ) ) {
					WP_CLI::error( $res->get_error_message() );
				}
				( $this->run )();
			}
		}
	);
	unset( $semrs_bk_cli_run );
}
