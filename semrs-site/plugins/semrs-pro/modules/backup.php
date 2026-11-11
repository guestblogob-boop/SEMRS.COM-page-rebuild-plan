<?php
/**
 * SEMRS Backups (part of SEMRS Assets Protector): scheduled full or partial
 * backups to this server and to remote storage, one-click restore, and
 * one-click migration of a whole site to another address or host.
 *
 * Works alongside UpdraftPlus and other backup plugins: while one of them is
 * active, the SEMRS schedule pauses (manual backups still work), so two
 * backups never run at the same time.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/backup/engine.php';
require_once __DIR__ . '/backup/remote.php';
require_once __DIR__ . '/backup/pro.php';

/*
 * Migration: the old site hands its package to the new site, file by file,
 * with a one-day secret key. Nothing else can be downloaded with it.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'semrs-backup/v1',
			'/pkg',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'semrs_bk_rest_pkg',
			)
		);
		register_rest_route(
			'semrs-backup/v1',
			'/pkg-file',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'semrs_bk_rest_pkg_file',
			)
		);
	}
);

/**
 * Check a migration key.
 *
 * @param WP_REST_Request $req Request.
 * @return array|WP_Error Package record.
 */
function semrs_bk_pkg_auth( $req ) {
	$p = (array) get_option( 'semrs_bk_migrate', array() );
	if ( empty( $p['token'] ) || (int) $p['expires'] < time() || (string) $req->get_param( 'i' ) !== $p['id'] || ! hash_equals( $p['token'], (string) $req->get_param( 't' ) ) ) {
		$k = 'semrs_bk_pkg_bad_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		set_transient( $k, (int) get_transient( $k ) + 1, HOUR_IN_SECONDS );
		return new WP_Error( 'semrs', 'Migration key not valid or expired.', array( 'status' => 403 ) );
	}
	if ( (int) get_transient( 'semrs_bk_pkg_bad_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) ) > 20 ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return new WP_Error( 'semrs', 'Too many tries.', array( 'status' => 429 ) );
	}
	return $p;
}

/**
 * Package manifest.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_bk_rest_pkg( $req ) {
	$p = semrs_bk_pkg_auth( $req );
	if ( is_wp_error( $p ) ) {
		return $p;
	}
	$mf = semrs_bk_dir() . $p['id'] . '-manifest.json';
	if ( ! is_file( $mf ) ) {
		return new WP_Error( 'semrs', 'Package not found.', array( 'status' => 404 ) );
	}
	return rest_ensure_response( json_decode( (string) file_get_contents( $mf ), true ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * One 8 MB piece of a package file.
 *
 * @param WP_REST_Request $req Request (f, o).
 * @return WP_Error|void
 */
function semrs_bk_rest_pkg_file( $req ) {
	$p = semrs_bk_pkg_auth( $req );
	if ( is_wp_error( $p ) ) {
		return $p;
	}
	$f  = basename( (string) $req->get_param( 'f' ) );
	$mf = semrs_bk_dir() . $p['id'] . '-manifest.json';
	$m  = is_file( $mf ) ? json_decode( (string) file_get_contents( $mf ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( $f !== $p['id'] . '-manifest.json' && ! isset( $m['files'][ $f ] ) ) {
		return new WP_Error( 'semrs', 'Not part of the package.', array( 'status' => 404 ) );
	}
	$path = semrs_bk_dir() . $f;
	$off  = max( 0, (int) $req->get_param( 'o' ) );
	nocache_headers();
	header( 'Content-Type: application/octet-stream' );
	echo semrs_bk_read( $path, $off, 16 * MB_IN_BYTES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Migration key for a finished package.
 *
 * @param string $id Set id.
 * @return string
 */
function semrs_bk_make_key( $id ) {
	$p = array( 'id' => $id, 'token' => wp_generate_password( 40, false ), 'expires' => time() + DAY_IN_SECONDS );
	update_option( 'semrs_bk_migrate', $p, false );
	return rtrim( strtr( base64_encode( wp_json_encode( array( 'u' => rest_url( 'semrs-backup/v1/' ), 'i' => $id, 't' => $p['token'] ) ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/**
 * Start pulling a package from another site (this site will be replaced).
 *
 * @param string $key Migration key.
 * @return true|WP_Error
 */
function semrs_bk_pull_start( $key ) {
	$k = json_decode( (string) base64_decode( strtr( trim( $key ), '-_', '+/' ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	if ( ! is_array( $k ) || empty( $k['u'] ) || empty( $k['i'] ) || empty( $k['t'] ) || ! wp_http_validate_url( $k['u'] ) ) {
		return new WP_Error( 'semrs', __( 'That migration key is not complete. Copy it again from the old site.', 'semrs-pro' ) );
	}
	$res = wp_remote_get( add_query_arg( array( 'i' => $k['i'], 't' => $k['t'] ), $k['u'] . 'pkg' ), array( 'timeout' => 30 ) );
	$m   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) || empty( $m['files'] ) ) {
		return semrs_bk_http_error( $res, __( 'The old site did not hand over its package (the key may have expired)', 'semrs-pro' ) );
	}
	$cur = semrs_bk_job();
	if ( $cur && 'done' !== $cur['phase'] && $cur['touched'] > time() - 15 * MINUTE_IN_SECONDS ) {
		return new WP_Error( 'semrs', __( 'A backup or restore is already running.', 'semrs-pro' ) );
	}
	$files   = array_keys( (array) $m['files'] );
	$files[] = $k['i'] . '-manifest.json';
	$job     = array(
		'type'    => 'pull',
		'id'      => $k['i'],
		'dir'     => semrs_bk_dir(),
		'src'     => $k,
		'files'   => $files,
		'sizes'   => $m['files'],
		'f'       => 0,
		'off'     => 0,
		'phase'   => 'get',
		'started' => time(),
		'touched' => time(),
		'token'   => wp_generate_password( 32, false ),
		'log'     => array(),
	);
	/* translators: 1: site, 2: size */
	semrs_bk_log( $job, sprintf( 'Copying %1$s (%2$s)…', $m['home'], size_format( (int) $m['size'] ) ) );
	semrs_bk_save_job( $job );
	return true;
}

/**
 * Download the package, 8 MB at a time.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_pull_get( $job, $end ) {
	while ( $job['f'] < count( $job['files'] ) && microtime( true ) < $end ) {
		$f   = $job['files'][ $job['f'] ];
		$res = wp_remote_get( add_query_arg( array( 'i' => $job['src']['i'], 't' => $job['src']['t'], 'f' => $f, 'o' => $job['off'] ), $job['src']['u'] . 'pkg-file' ), array( 'timeout' => 90 ) );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			throw new Exception( semrs_bk_http_error( $res, $f )->get_error_message() );
		}
		$data = (string) wp_remote_retrieve_body( $res );
		file_put_contents( $job['dir'] . $f, $data, $job['off'] ? FILE_APPEND : 0 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$job['off'] += strlen( $data );
		// Done when the known size is reached (the manifest itself: when a short piece arrives).
		$want = isset( $job['sizes'][ $f ] ) ? (int) $job['sizes'][ $f ] : -1;
		if ( ( $want >= 0 && $job['off'] >= $want ) || ( $want < 0 && strlen( $data ) < 8 * MB_IN_BYTES ) || '' === $data ) {
			if ( $want >= 0 && $job['off'] !== $want ) {
				/* translators: %s: file */
				throw new Exception( sprintf( __( '%s did not copy completely. Start the migration again.', 'semrs-pro' ), $f ) );
			}
			++$job['f'];
			$job['off'] = 0;
		}
	}
	if ( $job['f'] >= count( $job['files'] ) ) {
		$job['phase'] = 'restore';
		semrs_bk_log( $job, 'Package copied. Restoring…' );
	}
	return $job;
}

/**
 * Hand over to the restore.
 *
 * @param array $job Job.
 * @return array
 */
function semrs_bk_pull_restore( $job ) {
	$log = $job['log'];
	semrs_bk_save_job( null );
	$res = semrs_bk_restore_start( $job['id'], array( 'db', 'plugins', 'themes', 'uploads', 'mu', 'others' ), true );
	if ( is_wp_error( $res ) ) {
		throw new Exception( $res->get_error_message() );
	}
	$new        = semrs_bk_job();
	$new['log'] = array_merge( $log, $new['log'] );
	$new['token'] = $job['token']; // The screen keeps working with the same key.
	return $new;
}

/**
 * Start fetching a backup back from remote storage (when it is no longer on this server).
 *
 * @param string $id   Set id.
 * @param string $dest Destination.
 * @return true|WP_Error
 */
function semrs_bk_fetch_start( $id, $dest ) {
	$h = ( (array) get_option( 'semrs_bk_history', array() ) )[ $id ] ?? null;
	if ( ! $h ) {
		return new WP_Error( 'semrs', __( 'Unknown backup.', 'semrs-pro' ) );
	}
	$job = array(
		'type'    => 'fetch',
		'id'      => $id,
		'dir'     => semrs_bk_dir(),
		'dest'    => $dest,
		'files'   => array_values( $h['files'] ),
		'rids'    => $h['remote'][ $dest ] ?? array(),
		'f'       => 0,
		'st'      => array(),
		'phase'   => 'get',
		'started' => time(),
		'touched' => time(),
		'token'   => wp_generate_password( 32, false ),
		'log'     => array(),
	);
	semrs_bk_log( $job, sprintf( 'Fetching %1$s from %2$s…', $id, semrs_bk_dest_label( $dest ) ) );
	semrs_bk_save_job( $job );
	return true;
}

/**
 * Fetch files.
 *
 * @param array $job Job.
 * @param float $end Deadline.
 * @return array
 */
function semrs_bk_fetch_get( $job, $end ) {
	while ( $job['f'] < count( $job['files'] ) && microtime( true ) < $end ) {
		$f   = $job['files'][ $job['f'] ];
		$st  = $job['st'];
		$res = semrs_bk_remote_get( $job['dest'], $f, (string) ( $job['rids'][ $f ] ?? '' ), $job['dir'] . $f, $st, $end );
		if ( is_wp_error( $res ) ) {
			throw new Exception( $res->get_error_message() );
		}
		$job['st'] = $st;
		if ( true === $res ) {
			++$job['f'];
			$job['st'] = array();
		}
	}
	if ( $job['f'] >= count( $job['files'] ) ) {
		$hist = (array) get_option( 'semrs_bk_history', array() );
		if ( isset( $hist[ $job['id'] ] ) ) {
			$hist[ $job['id'] ]['local'] = true;
			update_option( 'semrs_bk_history', $hist, false );
		}
		semrs_bk_log( $job, 'Backup is on this server again. You can restore it now.' );
		$job['phase'] = 'done';
	}
	return $job;
}

/*
 * Step runner for the screen. Restores replace the users table while they
 * run, so the job's own secret token is accepted as well as an admin login.
 */
add_action( 'wp_ajax_semrs_bk_step', 'semrs_bk_ajax_step' );
add_action( 'wp_ajax_nopriv_semrs_bk_step', 'semrs_bk_ajax_step' );

/**
 * One step.
 */
function semrs_bk_ajax_step() {
	$job = semrs_bk_job();
	$tok = (string) wp_unslash( $_POST['token'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
	if ( ! $job || '' === $tok || ! hash_equals( (string) $job['token'], $tok ) ) {
		wp_send_json_error( array( 'message' => __( 'No backup job is running.', 'semrs-pro' ) ), 403 );
	}
	$job = semrs_bk_step( 8 );
	$pct = 0;
	if ( 'backup' === $job['type'] ) {
		$left = count( (array) $job['queue'] );
		$all  = max( 1, count( (array) $job['parts'] ) );
		$pct  = 'run' === $job['phase'] ? (int) ( 80 * ( $all - $left ) / $all ) : ( 'upload' === $job['phase'] ? 85 : 100 );
	} elseif ( 'restore' === $job['type'] ) {
		$pct = array( 'files' => 30, 'db' => 60, 'replace' => 85, 'finish' => 95, 'done' => 100 )[ $job['phase'] ] ?? 50;
	} elseif ( in_array( $job['type'], array( 'pull', 'fetch' ), true ) ) {
		$pct = (int) ( 100 * $job['f'] / max( 1, count( $job['files'] ) ) );
	}
	wp_send_json_success(
		array(
			'phase' => $job['phase'],
			'pct'   => $pct,
			'log'   => array_slice( (array) $job['log'], -8 ),
			'error' => $job['error'] ?? '',
		)
	);
}

if ( is_admin() ) {
	require __DIR__ . '/backup/admin.php';
}
