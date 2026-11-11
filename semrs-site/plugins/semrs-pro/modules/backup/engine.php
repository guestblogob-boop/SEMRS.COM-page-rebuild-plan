<?php
/**
 * SEMRS Backups: engine.
 *
 * Backups are made and restored in short steps (a few seconds each), from
 * the screen or in the background, so they never hit server time limits.
 *
 * A backup set holds:
 * - {id}-db.zip       the database (one SQL statement per line);
 * - {id}-{part}-N.zip plugins, themes, uploads, must-use plugins and the rest
 *                     of wp-content, split into parts of the size you choose;
 * - {id}-manifest.json what is inside, the site address, table prefix and
 *                     WordPress version (used by restore and migration).
 *
 * Job state lives in wp-content/semrs-backup-state.php (a PHP file that
 * exits at once, so it cannot be read from the web), not in the database,
 * because a restore replaces the database while it runs.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with best-practice defaults.
 *
 * @return array
 */
function semrs_bk_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_bk', array() ),
		array(
			'schedule' => 'daily',
			'hour'     => 3,
			'db'       => '1',
			'plugins'  => '1',
			'themes'   => '1',
			'uploads'  => '1',
			'mu'       => '1',
			'others'   => '1',
			'keep'     => 7,
			'split'    => 100,
			'maxfile'  => 0,
			'exclude'  => '',
			'dests'    => array(),
			'notify'   => 'fail',
			'others_plugins' => '',
			'migrate_max' => 10240,
			'skip_tables' => array(),
			'encrypt'  => '',
			'pass'     => '',
			'diff'     => '',
			'full_every' => 7,
			'preupdate' => '1',
		)
	);
}

/**
 * Protected backups folder (random name, web access denied).
 *
 * @return string Path with trailing slash, or ''.
 */
function semrs_bk_dir() {
	$name = (string) get_option( 'semrs_bk_dir', '' );
	if ( '' === $name ) {
		$name = 'semrs-backups-' . strtolower( wp_generate_password( 12, false ) );
		update_option( 'semrs_bk_dir', $name, false );
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
 * Other backup plugins that already run scheduled backups (their name), or ''.
 *
 * @return string
 */
function semrs_bk_other_plugin() {
	$list = array(
		'UpdraftPlus'        => class_exists( 'UpdraftPlus', false ) || defined( 'UPDRAFTPLUS_DIR' ),
		'BackWPup'           => class_exists( 'BackWPup', false ),
		'BlogVault'          => defined( 'BVVERSION' ) || class_exists( 'BVBackupCtrl', false ),
		'Jetpack VaultPress' => class_exists( 'Jetpack_Backup', false ) || defined( 'JETPACK_BACKUP_PLUGIN_VERSION' ),
		'WPvivid'            => defined( 'WPVIVID_PLUGIN_DIR' ),
		'Duplicator'         => defined( 'DUPLICATOR_VERSION' ) || defined( 'DUPLICATOR_PRO_VERSION' ),
		'All-in-One WP Migration' => defined( 'AI1WM_VERSION' ),
		'BackupBuddy'        => class_exists( 'pb_backupbuddy', false ),
	);
	foreach ( $list as $n => $on ) {
		if ( $on ) {
			return $n;
		}
	}
	return '';
}

/*
 * Job state (a file, so a restore can replace the database safely).
 */

/**
 * State file path.
 *
 * @return string
 */
function semrs_bk_state_file() {
	return trailingslashit( WP_CONTENT_DIR ) . 'semrs-backup-state.php';
}

/**
 * Current job, or null.
 *
 * @return array|null
 */
function semrs_bk_job() {
	$f = semrs_bk_state_file();
	if ( ! is_file( $f ) ) {
		return null;
	}
	$raw  = (string) file_get_contents( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$json = json_decode( (string) substr( $raw, (int) strpos( $raw, "\n" ) + 1 ), true );
	return is_array( $json ) ? $json : null;
}

/**
 * Save the job (or clear it with null).
 *
 * @param array|null $job Job.
 */
function semrs_bk_save_job( $job ) {
	$f = semrs_bk_state_file();
	if ( null === $job ) {
		if ( is_file( $f ) ) {
			@unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
		return;
	}
	file_put_contents( $f, "<?php exit; ?>\n" . wp_json_encode( $job ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Add a line to the job log.
 *
 * @param array  $job Job (by reference).
 * @param string $msg Message.
 */
function semrs_bk_log( &$job, $msg ) {
	$job['log'][] = gmdate( 'H:i:s' ) . ' ' . $msg;
	if ( count( $job['log'] ) > 200 ) {
		$job['log'] = array_slice( $job['log'], -200 );
	}
}

/*
 * What is backed up.
 */

/**
 * Parts and their folders: key => [folder, label].
 *
 * @return array
 */
function semrs_bk_parts() {
	return array(
		'plugins' => array( WP_PLUGIN_DIR, __( 'Plugins', 'semrs-pro' ) ),
		'themes'  => array( get_theme_root(), __( 'Themes', 'semrs-pro' ) ),
		'uploads' => array( wp_get_upload_dir()['basedir'], __( 'Media uploads', 'semrs-pro' ) ),
		'mu'      => array( defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins', __( 'Must-use plugins', 'semrs-pro' ) ),
		'others'  => array( WP_CONTENT_DIR, __( 'Other files in wp-content', 'semrs-pro' ) ),
	);
}

/**
 * Should a path (relative to its part folder) be left out?
 *
 * @param string $part Part key.
 * @param string $rel  Relative path.
 * @param array  $set  Settings.
 * @return bool
 */
function semrs_bk_excluded( $part, $rel, $set ) {
	$rel = str_replace( '\\', '/', $rel );
	if ( 'others' === $part ) {
		$top  = strtok( $rel, '/' );
		$skip = array( basename( WP_PLUGIN_DIR ), basename( get_theme_root() ), 'mu-plugins', basename( wp_get_upload_dir()['basedir'] ), get_option( 'semrs_bk_dir', 'x' ), get_option( 'semrs_secpro_dir', 'x' ), 'cache', 'upgrade', 'upgrade-temp-backup', 'updraft', 'ai1wm-backups', 'backups-dup-lite', 'backups-dup-pro', 'wpvividbackups', 'backwpup-temp', 'wflogs', 'et-cache', 'litespeed', 'debug.log', 'semrs-backup-state.php', 'advanced-cache.php', 'object-cache.php' );
		if ( in_array( $top, $skip, true ) || 0 === strpos( $top, 'semrs-backups-' ) || 0 === strpos( $top, 'semrs-security-' ) || 0 === strpos( $top, 'backup' ) ) {
			return true;
		}
	}
	if ( preg_match( '#(^|/)(?:node_modules|\.git|\.svn)(/|$)#', $rel ) ) {
		return true;
	}
	foreach ( preg_split( '/[\r\n]+/', (string) $set['exclude'] ) as $x ) {
		$x = trim( str_replace( '\\', '/', $x ), "/ \t" );
		if ( '' === $x ) {
			continue;
		}
		$x = preg_replace( '#^wp-content/#', '', $x );
		$x = preg_replace( '#^(?:plugins|themes|uploads|mu-plugins)/#', '', $x );
		if ( $rel === $x || 0 === strpos( $rel, $x . '/' ) || fnmatch( $x, $rel ) ) {
			return true;
		}
	}
	return false;
}

/*
 * Starting.
 */

/**
 * Start a backup.
 *
 * @param string $by    manual|schedule|migrate.
 * @param array  $parts Parts to include (default: settings).
 * @return true|WP_Error
 */
function semrs_bk_start( $by = 'manual', $parts = null ) {
	$cur = semrs_bk_job();
	if ( $cur && 'done' !== $cur['phase'] && $cur['touched'] > time() - 15 * MINUTE_IN_SECONDS ) {
		return new WP_Error( 'semrs', __( 'A backup or restore is already running.', 'semrs-pro' ) );
	}
	$dir = semrs_bk_dir();
	if ( '' === $dir || ! wp_is_writable( $dir ) ) {
		return new WP_Error( 'semrs', __( 'The backups folder in wp-content could not be created. Check the folder permissions.', 'semrs-pro' ) );
	}
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'semrs', __( 'This server has no ZIP support (PHP zip extension). Ask your host to switch it on.', 'semrs-pro' ) );
	}
	$set   = semrs_bk_settings();
	$parts = null === $parts ? array_values( array_filter( array( 'db', 'plugins', 'themes', 'uploads', 'mu', 'others' ), function ( $p ) use ( $set ) { return '1' === $set[ $p ]; } ) ) : $parts;
	$id    = gmdate( 'Y-m-d-Hi' ) . '-' . strtolower( wp_generate_password( 4, false ) );
	global $wpdb, $wp_version;
	$job = array(
		'type'    => 'backup',
		'by'      => $by,
		'id'      => $id,
		'dir'     => $dir,
		'parts'   => $parts,
		'queue'   => array_values( $parts ),
		'phase'   => 'run',
		'cur'     => array(),
		'files'   => array(),
		'started' => time(),
		'touched' => time(),
		'token'   => wp_generate_password( 32, false ),
		'log'     => array(),
		'manifest' => array(
			'id'      => $id,
			'home'    => home_url(),
			'siteurl' => site_url(),
			'abspath' => ABSPATH,
			'prefix'  => $wpdb->prefix,
			'wp'      => $wp_version,
			'php'     => PHP_VERSION,
			'roots'   => array(),
			'created' => time(),
			'parts'   => $parts,
			'by'      => $by,
		),
		'dests'   => 'migrate' === $by ? array() : array_values( (array) $set['dests'] ),
		'upload'  => array(),
		'est'     => 0,
		'base'    => '',
		'since'   => 0,
	);
	// Differential backups: files changed since the last full backup (the database is always full).
	if ( '1' === $set['diff'] && 'migrate' !== $by && array_diff( $parts, array( 'db' ) ) ) {
		foreach ( (array) get_option( 'semrs_bk_history', array() ) as $hid => $h ) {
			if ( empty( $h['base'] ) && in_array( $h['by'] ?? '', array( 'manual', 'schedule' ), true ) && ! array_diff( $parts, (array) $h['parts'] ) && $h['time'] > time() - max( 1, (int) $set['full_every'] ) * DAY_IN_SECONDS && ( ! $job['base'] || $h['time'] > $job['since'] ) && is_file( $dir . $hid . '-manifest.json' ) ) {
				$job['base']  = $hid;
				$job['since'] = (int) $h['time'];
			}
		}
		$job['manifest']['base'] = $job['base'];
		if ( $job['base'] ) {
			semrs_bk_log( $job, sprintf( 'Only files changed since the full backup %s are added.', $job['base'] ) );
		}
	}
	semrs_bk_log( $job, sprintf( 'Backup %s started (%s).', $id, implode( ', ', $parts ) ) );
	semrs_bk_save_job( $job );
	return true;
}

/**
 * Run the current job for up to $budget seconds.
 *
 * @param int $budget Seconds.
 * @return array|null Job.
 */
function semrs_bk_step( $budget = 8 ) {
	$job = semrs_bk_job();
	if ( ! $job || 'done' === $job['phase'] ) {
		return $job;
	}
	@set_time_limit( $budget + 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	@ignore_user_abort( true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$end = microtime( true ) + $budget;
	try {
		while ( microtime( true ) < $end && 'done' !== $job['phase'] ) {
			$fn = 'semrs_bk_' . $job['type'] . '_' . $job['phase'];
			if ( ! function_exists( $fn ) ) {
				$job['phase'] = 'done';
				break;
			}
			$job = $fn( $job, $end );
			$job['touched'] = time();
			semrs_bk_save_job( $job );
		}
	} catch ( Throwable $e ) {
		semrs_bk_log( $job, 'Error: ' . $e->getMessage() );
		$job['error'] = $e->getMessage();
		if ( 'backup' === $job['type'] ) {
			foreach ( (array) glob( $job['dir'] . $job['id'] . '-*' ) as $part ) {
				@unlink( $part ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions -- unfinished backup files.
			}
		}
		$job['phase'] = 'done';
		semrs_bk_save_job( $job );
		semrs_bk_finished( $job );
	}
	return $job;
}

/*
 * Backup phases.
 */

/**
 * Work through the queue: database first, then each file part.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_backup_run( $job, $end ) {
	if ( ! $job['queue'] ) {
		$job['phase'] = 'manifest';
		return $job;
	}
	$part = $job['queue'][0];
	$job  = 'db' === $part ? semrs_bk_dump_db( $job, $end ) : semrs_bk_zip_part( $job, $part, $end );
	return $job;
}

/**
 * Database dump, a few thousand rows per step.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_dump_db( $job, $end ) {
	global $wpdb;
	$c = $job['cur'];
	if ( empty( $c['tables'] ) && ! isset( $c['t'] ) ) {
		$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW FULL TABLES WHERE Table_type = %s', 'BASE TABLE' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$skip   = (array) semrs_bk_settings()['skip_tables'];
		$tables = array_values( array_filter( (array) $tables, function ( $t ) use ( $wpdb, $skip ) { return 0 === strpos( $t, $wpdb->prefix ) && ! in_array( $t, $skip, true ); } ) );
		$c      = array( 'tables' => $tables, 't' => 0, 'row' => 0, 'file' => $job['dir'] . $job['id'] . '-db.sql' );
		file_put_contents( $c['file'], "-- SEMRS backup " . $job['id'] . "\nSET NAMES " . $wpdb->charset . ";\nSET foreign_key_checks = 0;\nSET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		semrs_bk_log( $job, sprintf( 'Database: %d tables.', count( $tables ) ) );
	}
	$fh = fopen( $c['file'], 'ab' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	while ( $c['t'] < count( $c['tables'] ) && microtime( true ) < $end ) {
		$t = $c['tables'][ $c['t'] ];
		if ( 0 === $c['row'] ) {
			$create = $wpdb->get_row( 'SHOW CREATE TABLE `' . esc_sql( $t ) . '`', ARRAY_N ); // phpcs:ignore WordPress.DB
			fwrite( $fh, 'DROP TABLE IF EXISTS `' . $t . "`;\n" . str_replace( array( "\r", "\n" ), ' ', (string) $create[1] ) . ";\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			// Big tables: page by a numeric primary key (fast at any size) instead of OFFSET.
			$keys      = $wpdb->get_results( 'SHOW KEYS FROM `' . esc_sql( $t ) . "` WHERE Key_name = 'PRIMARY'" ); // phpcs:ignore WordPress.DB
			$c['pk']   = '';
			$c['pki']  = 0;
			$c['last'] = null;
			if ( 1 === count( (array) $keys ) ) {
				$cols = $wpdb->get_results( 'SHOW COLUMNS FROM `' . esc_sql( $t ) . '`' ); // phpcs:ignore WordPress.DB
				foreach ( (array) $cols as $i => $col ) {
					if ( $col->Field === $keys[0]->Column_name && preg_match( '/int/i', $col->Type ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						$c['pk']  = $col->Field; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						$c['pki'] = $i;
					}
				}
			}
		}
		if ( '' !== $c['pk'] ) {
			$rows = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $t ) . '`' . ( null === $c['last'] ? '' : ' WHERE `' . esc_sql( $c['pk'] ) . '` > ' . (int) $c['last'] ) . ' ORDER BY `' . esc_sql( $c['pk'] ) . '` LIMIT 500', ARRAY_N ); // phpcs:ignore WordPress.DB
			if ( $rows ) {
				$c['last'] = (int) $rows[ count( $rows ) - 1 ][ $c['pki'] ];
			}
		} else {
			$rows = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $t ) . '` LIMIT 500 OFFSET ' . (int) $c['row'], ARRAY_N ); // phpcs:ignore WordPress.DB
		}
		if ( $rows ) {
			// Statements stay under ~1 MB so they fit MySQL's packet limit on any host.
			$vals = array();
			$len  = 0;
			foreach ( $rows as $r ) {
				$cells = array();
				foreach ( $r as $v ) {
					$cells[] = null === $v ? 'NULL' : "'" . $wpdb->remove_placeholder_escape( $wpdb->_real_escape( (string) $v ) ) . "'";
				}
				$row    = '(' . implode( ',', $cells ) . ')';
				if ( $vals && $len + strlen( $row ) > MB_IN_BYTES ) {
					fwrite( $fh, 'INSERT INTO `' . $t . '` VALUES ' . implode( ',', $vals ) . ";\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$vals = array();
					$len  = 0;
				}
				$vals[] = $row;
				$len   += strlen( $row );
			}
			fwrite( $fh, 'INSERT INTO `' . $t . '` VALUES ' . implode( ',', $vals ) . ";\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( count( (array) $rows ) < 500 ) {
			++$c['t'];
			$c['row'] = 0;
		} else {
			$c['row'] += 500;
		}
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( $c['t'] >= count( $c['tables'] ) ) {
		$zipf = $job['dir'] . $job['id'] . '-db.zip';
		$zip  = new ZipArchive();
		if ( true === $zip->open( $zipf, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			$zip->addFile( $c['file'], 'database.sql' );
			$zip->close();
		}
		@unlink( $c['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		$set = semrs_bk_settings();
		if ( '1' === $set['encrypt'] && '' !== (string) $set['pass'] && 'migrate' !== $job['by'] ) {
			semrs_bk_crypt_file( $zipf, $zipf . '.enc', (string) $set['pass'], true );
			@unlink( $zipf ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			$zipf .= '.enc';
			$job['manifest']['encrypted'] = true;
		}
		$job['files'][] = basename( $zipf );
		semrs_bk_log( $job, sprintf( 'Database done (%s)%s.', size_format( (int) filesize( $zipf ) ), '.enc' === substr( $zipf, -4 ) ? ', encrypted' : '' ) );
		array_shift( $job['queue'] );
		$c = array();
	}
	$job['cur'] = $c;
	return $job;
}

/**
 * Zip one part (plugins, themes…) in batches, split into parts of N MB.
 *
 * @param array  $job  Job.
 * @param string $part Part key.
 * @param float  $end  Deadline.
 * @return array
 */
function semrs_bk_zip_part( $job, $part, $end ) {
	$set   = semrs_bk_settings();
	$roots = semrs_bk_parts();
	$root  = untrailingslashit( str_replace( '\\', '/', $roots[ $part ][0] ) );
	$c     = $job['cur'];
	if ( empty( $c['list'] ) ) {
		$list = $job['dir'] . $job['id'] . '-' . $part . '-list.txt';
		$fh   = fopen( $list, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$n    = 0;
		$big  = 0;
		$size = 0;
		$max  = (int) $set['maxfile'] * MB_IN_BYTES;
		if ( is_dir( $root ) ) {
			$dirs = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
			$filt = new RecursiveCallbackFilterIterator(
				$dirs,
				function ( $cur ) use ( $part, $root, $set ) {
					$rel = ltrim( substr( str_replace( '\\', '/', $cur->getPathname() ), strlen( $root ) ), '/' );
					return ! semrs_bk_excluded( $part, $rel, $set );
				}
			);
			foreach ( new RecursiveIteratorIterator( $filt ) as $f ) {
				if ( ! $f->isFile() || ! $f->isReadable() ) {
					continue;
				}
				if ( $max && $f->getSize() > $max ) {
					++$big;
					continue;
				}
				if ( $job['since'] && $f->getMTime() <= $job['since'] ) {
					continue; // Unchanged since the full backup.
				}
				$size += $f->getSize();
				fwrite( $fh, ltrim( substr( str_replace( '\\', '/', $f->getPathname() ), strlen( $root ) ), '/' ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				++$n;
			}
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$job['est'] = (int) ( $job['est'] ?? 0 ) + $size;
		// Migration size limit (default 10 GB) and free disk space.
		$cap = max( 100, (int) $set['migrate_max'] ) * MB_IN_BYTES;
		if ( 'migrate' === $job['by'] && $job['est'] > $cap ) {
			@unlink( $list ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			/* translators: 1: size, 2: limit */
			throw new Exception( sprintf( __( 'The site is %1$s, over your migration limit of %2$s. Leave out large folders or files (Schedule & contents), or raise the limit.', 'semrs-pro' ), size_format( $job['est'] ), size_format( $cap ) ) );
		}
		$free = function_exists( 'disk_free_space' ) ? @disk_free_space( $job['dir'] ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( false !== $free && $free < $size * 1.05 + 50 * MB_IN_BYTES ) {
			@unlink( $list ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			/* translators: 1: needed, 2: free */
			throw new Exception( sprintf( __( 'Not enough disk space: about %1$s needed, %2$s free. Delete old backups, leave out large folders, or ask your host for more space.', 'semrs-pro' ), size_format( $size * 1.05 ), size_format( $free ) ) );
		}
		$c = array( 'list' => $list, 'pos' => 0, 'n' => $n, 'num' => 1, 'bytes' => 0 );
		$job['manifest']['roots'][ $part ] = $root;
		/* translators: 1: part, 2: files, 3: size */
		semrs_bk_log( $job, sprintf( '%1$s: %2$d files, %3$s.', $roots[ $part ][1], $n, size_format( $size ) ) . ( $big ? ' ' . sprintf( '%d files over the size limit left out.', $big ) : '' ) );
	}
	$names = file( $c['list'], FILE_IGNORE_NEW_LINES ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$split = max( 10, (int) $set['split'] ) * MB_IN_BYTES;
	$zipf  = $job['dir'] . $job['id'] . '-' . $part . '-' . $c['num'] . '.zip';
	$zip   = new ZipArchive();
	if ( true !== $zip->open( $zipf, ZipArchive::CREATE ) ) {
		throw new Exception( 'Cannot write ' . basename( $zipf ) );
	}
	$batch = 0;
	$store = method_exists( $zip, 'setCompressionName' );
	while ( $c['pos'] < count( $names ) && $batch < 40 * MB_IN_BYTES && microtime( true ) < $end - 1.5 ) {
		$rel = $names[ $c['pos']++ ];
		$abs = $root . '/' . $rel;
		if ( ! is_file( $abs ) ) {
			continue;
		}
		$sz = (int) filesize( $abs );
		$zip->addFile( $abs, $part . '/' . $rel );
		if ( $store && preg_match( '/\.(?:jpe?g|png|gif|webp|avif|mp4|mov|webm|mp3|zip|gz|7z|rar|woff2?|pdf)$/i', $rel ) ) {
			$zip->setCompressionName( $part . '/' . $rel, ZipArchive::CM_STORE );
		}
		$batch     += $sz;
		$c['bytes'] += $sz;
		if ( $c['bytes'] >= $split ) {
			break;
		}
	}
	$zip->close();
	if ( ! in_array( basename( $zipf ), $job['files'], true ) && is_file( $zipf ) ) {
		$job['files'][] = basename( $zipf );
	}
	if ( $c['bytes'] >= $split && $c['pos'] < count( $names ) ) {
		++$c['num'];
		$c['bytes'] = 0;
	}
	if ( $c['pos'] >= count( $names ) ) {
		@unlink( $c['list'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		array_shift( $job['queue'] );
		$c = array();
	}
	$job['cur'] = $c;
	return $job;
}

/**
 * Write the manifest, then upload or finish.
 *
 * @param array $job Job.
 * @return array
 */
function semrs_bk_backup_manifest( $job ) {
	$m          = $job['manifest'];
	$m['files'] = array();
	$total      = 0;
	foreach ( $job['files'] as $f ) {
		$sz             = (int) @filesize( $job['dir'] . $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$m['files'][ $f ] = $sz;
		$total         += $sz;
	}
	$m['size'] = $total;
	$mf        = $job['id'] . '-manifest.json';
	file_put_contents( $job['dir'] . $mf, wp_json_encode( $m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$job['files'][]       = $mf;
	$job['manifest']      = $m;
	$job['upload']        = array( 'd' => 0, 'f' => 0, 'st' => array(), 'remote' => array() );
	$job['phase']         = $job['dests'] ? 'upload' : 'finish';
	semrs_bk_log( $job, sprintf( 'Backup files ready: %d files, %s.', count( $job['files'] ), size_format( $total ) ) );
	return $job;
}

/**
 * Send every file to every chosen destination, chunk by chunk.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_backup_upload( $job, $end ) {
	$u = $job['upload'];
	while ( $u['d'] < count( $job['dests'] ) && microtime( true ) < $end ) {
		$dest = $job['dests'][ $u['d'] ];
		if ( $u['f'] >= count( $job['files'] ) ) {
			semrs_bk_log( $job, sprintf( 'Copied to %s.', semrs_bk_dest_label( $dest ) ) );
			++$u['d'];
			$u['f']  = 0;
			$u['st'] = array();
			continue;
		}
		$name = $job['files'][ $u['f'] ];
		$res  = semrs_bk_remote_put( $dest, $job['dir'] . $name, $name, $u['st'], $end );
		if ( is_wp_error( $res ) ) {
			semrs_bk_log( $job, sprintf( '%1$s: %2$s', semrs_bk_dest_label( $dest ), $res->get_error_message() ) );
			$job['warn'][]   = semrs_bk_dest_label( $dest ) . ': ' . $res->get_error_message();
			$job['failed'][] = $dest;
			++$u['d'];
			$u['f']  = 0;
			$u['st'] = array();
			continue;
		}
		if ( true === $res ) {
			if ( ! empty( $u['st']['remote_id'] ) ) {
				$u['remote'][ $dest ][ $name ] = $u['st']['remote_id'];
			}
			++$u['f'];
			$u['st'] = array();
		}
	}
	$job['upload'] = $u;
	if ( $u['d'] >= count( $job['dests'] ) ) {
		$job['phase'] = 'finish';
	}
	return $job;
}

/**
 * Record the backup, apply retention, notify.
 *
 * @param array $job Job.
 * @return array
 */
function semrs_bk_backup_finish( $job ) {
	$set             = semrs_bk_settings();
	$hist            = (array) get_option( 'semrs_bk_history', array() );
	$hist[ $job['id'] ] = array(
		'time'   => $job['started'],
		'by'     => $job['by'],
		'size'   => (int) ( $job['manifest']['size'] ?? 0 ),
		'files'  => $job['files'],
		'parts'  => $job['parts'],
		'dests'  => array_values( array_diff( $job['dests'], (array) ( $job['failed'] ?? array() ) ) ),
		'remote' => $job['upload']['remote'] ?? array(),
		'local'  => true,
		'warn'   => $job['warn'] ?? array(),
		'base'   => $job['base'] ?? '',
	);
	// Keep the newest N scheduled/manual backups (migration packages are removed after a day).
	$keep  = max( 1, (int) $set['keep'] );
	$older = array_keys(
		array_filter(
			$hist,
			function ( $h ) {
				return 'migrate' !== ( $h['by'] ?? '' );
			}
		)
	);
	usort( $older, function ( $a, $b ) use ( $hist ) { return $hist[ $b ]['time'] <=> $hist[ $a ]['time']; } );
	foreach ( array_slice( $older, $keep ) as $old ) {
		// A full backup that newer differential backups still build on is kept.
		$needed = false;
		foreach ( $hist as $hh ) {
			if ( ( $hh['base'] ?? '' ) === $old ) {
				$needed = true;
			}
		}
		if ( $needed || ! isset( $hist[ $old ] ) ) {
			continue;
		}
		semrs_bk_delete_set( $old, $hist[ $old ] );
		unset( $hist[ $old ] );
	}
	update_option( 'semrs_bk_history', $hist, false );
	semrs_bk_log( $job, 'Finished.' );
	$job['phase'] = 'done';
	semrs_bk_finished( $job );
	return $job;
}

/**
 * Delete a backup set everywhere it was stored.
 *
 * @param string $id Set id.
 * @param array  $h  History entry.
 */
function semrs_bk_delete_set( $id, $h ) {
	$dir = semrs_bk_dir();
	foreach ( (array) $h['files'] as $f ) {
		if ( is_file( $dir . $f ) ) {
			@unlink( $dir . $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
	}
	foreach ( (array) ( $h['dests'] ?? array() ) as $d ) {
		foreach ( (array) $h['files'] as $f ) {
			semrs_bk_remote_delete( $d, $f, $h['remote'][ $d ][ $f ] ?? '' );
		}
	}
}

/**
 * After any job: email the owner when asked.
 *
 * @param array $job Job.
 */
function semrs_bk_finished( $job ) {
	$set  = semrs_bk_settings();
	$fail = ! empty( $job['error'] ) || ! empty( $job['warn'] );
	if ( 'never' === $set['notify'] || ( 'fail' === $set['notify'] && ! $fail ) || 'migrate' === ( $job['by'] ?? '' ) ) {
		return;
	}
	$what = 'restore' === $job['type'] ? __( 'Restore', 'semrs-pro' ) : __( 'Backup', 'semrs-pro' );
	wp_mail(
		get_option( 'admin_email' ),
		/* translators: 1: site, 2: Backup/Restore, 3: result */
		sprintf( '[%1$s] %2$s %3$s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $what, $fail ? __( 'had problems', 'semrs-pro' ) : __( 'finished', 'semrs-pro' ) ),
		implode( "\n", (array) $job['log'] ) . "\n\n" . admin_url( 'admin.php?page=semrs-backups' )
	);
}

/*
 * Restore.
 */

/**
 * Start a restore of a local backup set.
 *
 * @param string $id      Set id.
 * @param array  $parts   Parts to restore.
 * @param bool   $migrate Moving from another site (replace its address with this one).
 * @return true|WP_Error
 */
function semrs_bk_restore_start( $id, $parts, $migrate = false, $pass = '' ) {
	$dir = semrs_bk_dir();
	$mf  = $dir . $id . '-manifest.json';
	if ( ! is_file( $mf ) ) {
		return new WP_Error( 'semrs', __( 'This backup is not on this server. Download it from your storage first, or upload it on this screen.', 'semrs-pro' ) );
	}
	$m = json_decode( (string) file_get_contents( $mf ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! is_array( $m ) ) {
		return new WP_Error( 'semrs', __( 'The backup description file is damaged.', 'semrs-pro' ) );
	}
	foreach ( array_keys( (array) $m['files'] ) as $f ) {
		if ( ! is_file( $dir . $f ) ) {
			/* translators: %s: file */
			return new WP_Error( 'semrs', sprintf( __( 'Part of the backup is missing: %s', 'semrs-pro' ), $f ) );
		}
	}
	global $wpdb;
	$queue = array();
	// A differential backup restores its full backup first, then the changed files.
	$sets = array( $m );
	if ( ! empty( $m['base'] ) ) {
		$bf = $dir . $m['base'] . '-manifest.json';
		$bm = is_file( $bf ) ? json_decode( (string) file_get_contents( $bf ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! is_array( $bm ) ) {
			/* translators: %s: backup id */
			return new WP_Error( 'semrs', sprintf( __( 'This backup only holds changes; its full backup %s is needed on this server too.', 'semrs-pro' ), $m['base'] ) );
		}
		array_unshift( $sets, $bm );
	}
	foreach ( $sets as $sm ) {
		foreach ( (array) $sm['files'] as $f => $sz ) {
			if ( preg_match( '/-(plugins|themes|uploads|mu|others)-\d+\.zip$/', $f, $mm ) && in_array( $mm[1], $parts, true ) ) {
				if ( ! is_file( $dir . $f ) ) {
					/* translators: %s: file */
					return new WP_Error( 'semrs', sprintf( __( 'Part of the backup is missing: %s', 'semrs-pro' ), $f ) );
				}
				$queue[] = $f;
			}
		}
	}
	$dbf = '';
	foreach ( array_keys( (array) $m['files'] ) as $f ) {
		if ( preg_match( '/-db\.zip(?:\.enc)?$/', $f ) ) {
			$dbf = $f;
		}
	}
	$job = array(
		'type'     => 'restore',
		'id'       => $id,
		'dir'      => $dir,
		'm'        => $m,
		'parts'    => $parts,
		'queue'    => $queue,
		'db'       => in_array( 'db', $parts, true ) && '' !== $dbf,
		'dbf'      => $dbf,
		'pass'     => $pass,
		'migrate'  => $migrate,
		'phase'    => 'files',
		'cur'      => array(),
		'started'  => time(),
		'touched'  => time(),
		'token'    => wp_generate_password( 32, false ),
		'log'      => array(),
		'target'   => array(
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
	semrs_bk_log( $job, sprintf( 'Restore of %s started (%s).', $id, implode( ', ', $parts ) ) );
	semrs_bk_save_job( $job );
	return true;
}

/**
 * Restore files, a batch of entries per step.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_restore_files( $job, $end ) {
	if ( ! $job['queue'] ) {
		$job['phase'] = $job['db'] ? 'db' : 'finish';
		$job['cur']   = array();
		return $job;
	}
	$f     = $job['queue'][0];
	$zip   = new ZipArchive();
	if ( true !== $zip->open( $job['dir'] . $f ) ) {
		throw new Exception( 'Cannot open ' . $f );
	}
	$roots = semrs_bk_parts();
	$c     = $job['cur'] + array( 'i' => 0 );
	$own   = basename( dirname( dirname( __DIR__ ) ) );
	while ( $c['i'] < $zip->numFiles && microtime( true ) < $end ) {
		$batch = array();
		$part  = '';
		for ( $n = 0; $n < 300 && $c['i'] < $zip->numFiles; $n++ ) {
			$name = (string) $zip->getNameIndex( $c['i']++ );
			if ( '/' === substr( $name, -1 ) ) {
				continue;
			}
			$p    = strtok( $name, '/' );
			$rel  = substr( $name, strlen( $p ) + 1 );
			if ( isset( $job['map'][ $f ] ) ) {
				// Zips from other tools: "part|prefix" (entries may or may not start with the prefix).
				list( $mp, $pre ) = array_pad( explode( '|', $job['map'][ $f ], 2 ), 2, '' );
				$p   = $mp;
				$rel = '' !== $pre && 0 === strpos( $name, $pre ) ? substr( $name, strlen( $pre ) ) : $name;
			}
			// Never overwrite SEMRS Pro itself while it is running the restore.
			if ( 'plugins' === $p && 0 === strpos( $rel, $own . '/' ) ) {
				continue;
			}
			if ( '' === $part ) {
				$part = $p;
			}
			if ( $p !== $part || false !== strpos( $name, '..' ) || ! isset( $roots[ $p ] ) ) {
				continue;
			}
			$batch[ $name ] = $rel;
		}
		if ( $batch && $part ) {
			// Extract to a temp folder, then move into place (zip paths carry the part name).
			$tmp = $job['dir'] . 'restore-tmp/';
			wp_mkdir_p( $tmp );
			$zip->extractTo( $tmp, array_keys( $batch ) );
			$dest = untrailingslashit( $roots[ $part ][0] );
			foreach ( $batch as $name => $rel ) {
				$from = $tmp . $name;
				$to   = $dest . "/" . $rel;
				if ( is_file( $from ) ) {
					wp_mkdir_p( dirname( $to ) );
					if ( ! @rename( $from, $to ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
						@copy( $from, $to ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
						@unlink( $from ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
					}
				}
			}
		}
	}
	$done = $c['i'] >= $zip->numFiles;
	$zip->close();
	if ( $done ) {
		semrs_bk_log( $job, 'Restored ' . $f . '.' );
		array_shift( $job['queue'] );
		$c = array();
	}
	$job['cur'] = $c;
	return $job;
}

/**
 * Import the database, statement by statement.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_restore_db( $job, $end ) {
	global $wpdb;
	$c = $job['cur'];
	if ( empty( $c['sql'] ) ) {
		if ( ! empty( $job['sqlfile'] ) ) {
			$sql = $job['sqlfile']; // Imported from another backup plugin.
		} else {
			$dbf = $job['dir'] . ( $job['dbf'] ?? $job['id'] . '-db.zip' );
			if ( '.enc' === substr( $dbf, -4 ) ) {
				$pass = '' !== (string) ( $job['pass'] ?? '' ) ? $job['pass'] : (string) semrs_bk_settings()['pass'];
				if ( ! semrs_bk_crypt_file( $dbf, $job['dir'] . 'database-dec.zip', $pass, false ) ) {
					throw new Exception( __( 'The database backup is encrypted and the password is wrong or missing (Schedule & contents → Encryption).', 'semrs-pro' ) );
				}
				$dbf = $job['dir'] . 'database-dec.zip';
			}
			$zip = new ZipArchive();
			if ( true !== $zip->open( $dbf ) ) {
				throw new Exception( 'Cannot open the database backup.' );
			}
			$zip->extractTo( $job['dir'], array( 'database.sql' ) );
			$zip->close();
			if ( false !== strpos( $dbf, 'database-dec.zip' ) ) {
				@unlink( $dbf ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
			}
			$sql = $job['dir'] . 'database.sql';
		}
		$c = array( 'sql' => $sql, 'pos' => 0, 'n' => 0, 'buf' => '' );
		semrs_bk_log( $job, 'Importing the database…' );
	}
	$old     = (string) $job['m']['prefix'];
	$new     = $wpdb->prefix;
	$generic = ! empty( $job['sqlmode'] ); // Dumps from other tools: statements may span lines.
	$fh      = fopen( $c['sql'], 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fseek( $fh, (int) $c['pos'] );
	$wpdb->query( 'SET foreign_key_checks = 0' ); // phpcs:ignore WordPress.DB
	$wpdb->query( "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'" ); // phpcs:ignore WordPress.DB
	$wpdb->query( 'SET NAMES ' . $wpdb->charset ); // phpcs:ignore WordPress.DB
	while ( microtime( true ) < $end ) {
		$line = fgets( $fh );
		if ( false === $line ) {
			break;
		}
		$c['pos'] = ftell( $fh );
		if ( $generic ) {
			if ( '' === $c['buf'] && ( '' === trim( $line ) || preg_match( '/^(?:--|#|\/\*(?!!)|SET |LOCK TABLES|UNLOCK TABLES)/', $line ) ) ) {
				continue;
			}
			$c['buf'] .= $line;
			$t = rtrim( $c['buf'] );
			if ( ';' !== substr( $t, -1 ) || ! semrs_bk_sql_complete( $t ) ) {
				if ( strlen( $c['buf'] ) > 64 * MB_IN_BYTES ) {
					$c['buf'] = ''; // A broken statement: skip it.
				}
				continue;
			}
			$line     = $t;
			$c['buf'] = '';
		} else {
			$line = rtrim( $line, "\r\n" );
			if ( '' === $line || 0 === strpos( $line, '--' ) || 0 === strpos( $line, 'SET ' ) ) {
				continue;
			}
		}
		if ( $old !== $new ) {
			$line = preg_replace( '/^(DROP TABLE IF EXISTS `|CREATE TABLE (?:IF NOT EXISTS )?`|INSERT INTO `|ALTER TABLE `)' . preg_quote( $old, '/' ) . '/', '$1' . $new, $line );
		}
		$wpdb->query( $line ); // phpcs:ignore WordPress.DB
		++$c['n'];
	}
	$eof = feof( $fh );
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( $eof ) {
		@unlink( $c['sql'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		if ( $old !== $new ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$new}options SET option_name = CONCAT(%s, SUBSTRING(option_name, %d)) WHERE option_name LIKE %s", $new, strlen( $old ) + 1, $wpdb->esc_like( $old ) . '%' ) ); // phpcs:ignore WordPress.DB
			$wpdb->query( $wpdb->prepare( "UPDATE {$new}usermeta SET meta_key = CONCAT(%s, SUBSTRING(meta_key, %d)) WHERE meta_key LIKE %s", $new, strlen( $old ) + 1, $wpdb->esc_like( $old ) . '%' ) ); // phpcs:ignore WordPress.DB
		}
		wp_cache_flush();
		// Keep this site's own backups folder and storage settings.
		update_option( 'semrs_bk_dir', $job['target']['bkdir'], false );
		update_option( 'semrs_bk_history', $job['target']['hist'], false ); // The list of backups on THIS server.
		if ( $job['migrate'] ) {
			update_option( 'semrs_bk_dest', $job['target']['dests'], false );
			update_option( 'semrs_bk', $job['target']['set'] );
		}
		/* translators: %d: statements */
		semrs_bk_log( $job, sprintf( 'Database imported (%d statements).', $c['n'] ) );
		$c            = array();
		$job['phase'] = $job['migrate'] && untrailingslashit( $job['m']['home'] ) !== untrailingslashit( $job['target']['home'] ) ? 'replace' : 'finish';
	}
	$job['cur'] = $c;
	return $job;
}

/**
 * Is a statement complete (not ending inside a quoted string)?
 *
 * @param string $s Statement.
 * @return bool
 */
function semrs_bk_sql_complete( $s ) {
	$q   = '';
	$len = strlen( $s );
	for ( $i = 0; $i < $len; $i++ ) {
		$ch = $s[ $i ];
		if ( '' !== $q ) {
			if ( '\\' === $ch ) {
				++$i;
			} elseif ( $ch === $q ) {
				$q = '';
			}
		} elseif ( "'" === $ch || '"' === $ch || '`' === $ch ) {
			$q = $ch;
		}
	}
	return '' === $q;
}

/**
 * Encrypt or decrypt a file with AES-256 (chunked, so any size works).
 * Format: "SEMRSENC1" + 16-byte salt, then blocks of [4-byte length][16-byte IV][data].
 *
 * @param string $in      Source.
 * @param string $out     Target.
 * @param string $pass    Password.
 * @param bool   $encrypt Encrypt (true) or decrypt.
 * @return bool
 */
function semrs_bk_crypt_file( $in, $out, $pass, $encrypt ) {
	if ( ! function_exists( 'openssl_encrypt' ) || '' === $pass ) {
		return false;
	}
	$r = fopen( $in, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$w = fopen( $out, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( $encrypt ) {
		$salt = random_bytes( 16 );
		$key  = hash_pbkdf2( 'sha256', $pass, $salt, 100000, 32, true );
		fwrite( $w, 'SEMRSENC1' . $salt ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		while ( ! feof( $r ) ) {
			$data = (string) fread( $r, 8 * MB_IN_BYTES ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( '' === $data ) {
				break;
			}
			$iv  = random_bytes( 16 );
			$enc = (string) openssl_encrypt( $data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
			fwrite( $w, pack( 'N', strlen( $enc ) ) . $iv . $enc ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		$ok = true;
	} else {
		$ok = 'SEMRSENC1' === fread( $r, 9 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$key = $ok ? hash_pbkdf2( 'sha256', $pass, (string) fread( $r, 16 ), 100000, 32, true ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
		while ( $ok && ! feof( $r ) ) {
			$len = fread( $r, 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( false === $len || 4 !== strlen( $len ) ) {
				break;
			}
			$n   = unpack( 'N', $len )[1];
			$iv  = (string) fread( $r, 16 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$dec = openssl_decrypt( (string) fread( $r, $n ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( false === $dec ) {
				$ok = false;
				break;
			}
			fwrite( $w, $dec ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	fclose( $r ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $w ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $ok ) {
		@unlink( $out ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
	}
	return $ok;
}

/**
 * Migration: replace the old address and folder with this site's, in every
 * text column of every table (serialized data kept valid).
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_restore_replace( $job, $end ) {
	global $wpdb;
	$c = $job['cur'];
	if ( empty( $c['tables'] ) ) {
		$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$c      = array( 'tables' => array_values( (array) $tables ), 't' => 0, 'col' => 0, 'last' => '' );
		$pairs  = array();
		$oh     = untrailingslashit( $job['m']['home'] );
		$nh     = untrailingslashit( $job['target']['home'] );
		$pairs[ $oh ] = $nh;
		$pairs[ str_replace( '/', '\/', $oh ) ] = str_replace( '/', '\/', $nh );
		$pairs[ preg_replace( '#^https?:#', '', $oh ) ] = preg_replace( '#^https?:#', '', $nh );
		$pairs[ preg_replace( '#^https:#', 'http:', $oh ) ] = $nh;
		$pairs[ rawurlencode( $oh ) ] = rawurlencode( $nh );
		if ( untrailingslashit( $job['m']['abspath'] ) !== untrailingslashit( $job['target']['abspath'] ) ) {
			$pairs[ untrailingslashit( $job['m']['abspath'] ) ] = untrailingslashit( $job['target']['abspath'] );
		}
		$c['pairs'] = $pairs;
		semrs_bk_log( $job, sprintf( 'Changing the site address from %1$s to %2$s…', $oh, $nh ) );
	}
	while ( $c['t'] < count( $c['tables'] ) && microtime( true ) < $end ) {
		$t    = $c['tables'][ $c['t'] ];
		$cols = $wpdb->get_results( 'SHOW COLUMNS FROM `' . esc_sql( $t ) . '`' ); // phpcs:ignore WordPress.DB
		$pk   = '';
		$text = array();
		foreach ( (array) $cols as $col ) {
			if ( 'PRI' === $col->Key && '' === $pk ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$pk = $col->Field; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			}
			if ( preg_match( '/char|text|blob/i', $col->Type ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$text[] = $col->Field; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			}
		}
		if ( '' === $pk || ! $text || $c['col'] >= count( $text ) ) {
			++$c['t'];
			$c['col']  = 0;
			$c['last'] = '';
			continue;
		}
		$col  = $text[ $c['col'] ];
		$like = array();
		foreach ( array_keys( $c['pairs'] ) as $from ) {
			$like[] = $wpdb->prepare( '`' . esc_sql( $col ) . '` LIKE %s', '%' . $wpdb->esc_like( $from ) . '%' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT `' . esc_sql( $pk ) . '` AS id, `' . esc_sql( $col ) . '` AS v FROM `' . esc_sql( $t ) . '` WHERE `' . esc_sql( $pk ) . '` > %s AND (' . implode( ' OR ', $like ) . ') ORDER BY `' . esc_sql( $pk ) . '` LIMIT 200', $c['last'] ) ); // phpcs:ignore WordPress.DB
		foreach ( (array) $rows as $r ) {
			$nv = semrs_bk_replace_value( $r->v, $c['pairs'] );
			if ( $nv !== $r->v ) {
				$wpdb->update( $t, array( $col => $nv ), array( $pk => $r->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			$c['last'] = $r->id;
		}
		if ( count( (array) $rows ) < 200 ) {
			++$c['col'];
			$c['last'] = '';
		}
	}
	if ( $c['t'] >= count( $c['tables'] ) ) {
		semrs_bk_log( $job, 'Site address changed everywhere.' );
		$job['phase'] = 'finish';
		$c            = array();
	}
	$job['cur'] = $c;
	return $job;
}

/**
 * Replace in a value, unpacking serialized data safely.
 *
 * @param mixed $v     Value.
 * @param array $pairs from => to.
 * @return mixed
 */
function semrs_bk_replace_value( $v, $pairs ) {
	if ( is_string( $v ) && is_serialized( $v ) ) {
		$un = @unserialize( $v, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.PHP.DiscouragedPHPFunctions
		if ( false !== $un || 'b:0;' === $v ) {
			return serialize( semrs_bk_replace_value( $un, $pairs ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		}
		return $v;
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			$v[ $k ] = semrs_bk_replace_value( $x, $pairs );
		}
		return $v;
	}
	if ( is_object( $v ) && ! $v instanceof __PHP_Incomplete_Class ) {
		foreach ( get_object_vars( $v ) as $k => $x ) {
			$v->$k = semrs_bk_replace_value( $x, $pairs );
		}
		return $v;
	}
	return is_string( $v ) ? strtr( $v, $pairs ) : $v;
}

/**
 * Finish a restore.
 *
 * @param array $job Job.
 * @return array
 */
function semrs_bk_restore_finish( $job ) {
	$dir = $job['dir'] . 'restore-tmp/';
	if ( is_dir( $dir ) ) {
		semrs_bk_rmdir( $dir );
	}
	if ( $job['migrate'] ) {
		update_option( 'home', $job['target']['home'] );
		update_option( 'siteurl', $job['target']['siteurl'] );
	}
	delete_option( 'rewrite_rules' );
	wp_cache_flush();
	semrs_bk_log( $job, $job['db'] ? 'Restore finished. If you are asked to log in, use the username and password from the backup.' : 'Restore finished.' );
	$job['phase'] = 'done';
	semrs_bk_finished( $job );
	return $job;
}

/**
 * Remove a folder we made (temp files only).
 *
 * @param string $dir Folder.
 */
function semrs_bk_rmdir( $dir ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) {
		$f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
	}
	@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
}

/*
 * Schedule.
 */
add_action(
	'semrs_bk_scheduled',
	function () {
		$set = semrs_bk_settings();
		if ( 'off' === $set['schedule'] || ( semrs_bk_other_plugin() && '1' !== ( $set['others_plugins'] ?? '' ) ) ) {
			return;
		}
		$last = (int) get_option( 'semrs_bk_last_scheduled', 0 );
		$gap  = array( 'daily' => DAY_IN_SECONDS, 'twicedaily' => 12 * HOUR_IN_SECONDS, 'weekly' => WEEK_IN_SECONDS, 'monthly' => 30 * DAY_IN_SECONDS );
		if ( time() - $last < ( $gap[ $set['schedule'] ] ?? DAY_IN_SECONDS ) - HOUR_IN_SECONDS ) {
			return;
		}
		if ( true === semrs_bk_start( 'schedule' ) ) {
			update_option( 'semrs_bk_last_scheduled', time(), false );
			wp_schedule_single_event( time() + 10, 'semrs_bk_cron_step' );
		}
	}
);
add_action(
	'semrs_bk_cron_step',
	function () {
		$job = semrs_bk_step( 25 );
		if ( $job && 'done' !== $job['phase'] ) {
			wp_schedule_single_event( time() + 15, 'semrs_bk_cron_step' );
		}
	}
);
if ( ! wp_next_scheduled( 'semrs_bk_scheduled' ) ) {
	$semrs_h = (int) semrs_bk_settings()['hour'];
	wp_schedule_event( strtotime( 'tomorrow ' . $semrs_h . ':' . wp_rand( 10, 50 ) ), 'twicedaily', 'semrs_bk_scheduled' );
	unset( $semrs_h );
}
// A background job that stalled (server restarted) continues on the next cron run.
add_action(
	'init',
	function () {
		$job = wp_doing_cron() ? semrs_bk_job() : null;
		if ( $job && 'done' !== $job['phase'] && $job['touched'] < time() - 2 * MINUTE_IN_SECONDS && ! wp_next_scheduled( 'semrs_bk_cron_step' ) ) {
			wp_schedule_single_event( time() + 5, 'semrs_bk_cron_step' );
		}
	}
);
