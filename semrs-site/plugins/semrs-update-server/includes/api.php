<?php
/**
 * Public update API.
 *
 * GET /wp-json/semrs-updates/v1/check?slug=&version=&site=&wp=&php=&key=
 * GET /wp-json/semrs-updates/v1/download?slug=&version=&key=&host=&expires=&token=
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes.
 */
function semrs_us_routes() {
	register_rest_route(
		'semrs-updates/v1',
		'/check',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_us_check',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/activate',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_us_activate',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/license',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_us_license',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/lemonsqueezy',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true', // Checked by the webhook signature.
			'callback'            => 'semrs_us_ls_webhook',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/paddle',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true', // Checked by the webhook signature.
			'callback'            => 'semrs_us_paddle_webhook',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/deactivate',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_us_deactivate',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/free',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_us_free_download',
		)
	);
	register_rest_route(
		'semrs-updates/v1',
		'/download',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_us_download',
		)
	);
}
add_action( 'rest_api_init', 'semrs_us_routes' );

/**
 * Update check.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_us_check( $req ) {
	$slug     = sanitize_key( (string) $req->get_param( 'slug' ) );
	$products = semrs_us_products();
	if ( ! isset( $products[ $slug ] ) ) {
		return new WP_Error( 'semrs_unknown', 'Unknown product.', array( 'status' => 404 ) );
	}
	$host    = semrs_us_host( (string) $req->get_param( 'site' ) );
	$version = preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $req->get_param( 'version' ) );
	$key     = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $req->get_param( 'key' ) );
	// Check the license BEFORE recording this visit, otherwise a new site would
	// already count as one of the key's sites and pass the site limit.
	$license = $products[ $slug ][2] ? semrs_us_license_status( $key, $host ) : 'free';
	if ( $host ) {
		// Sites over the limit are listed, but not tied to the key (so they never take a slot).
		$record_key = $products[ $slug ][2] && 'valid' === $license ? $key : '';
		semrs_us_record( $slug, $host, $version, sanitize_text_field( (string) $req->get_param( 'wp' ) ), sanitize_text_field( (string) $req->get_param( 'php' ) ), $record_key );
	}
	$rel = semrs_us_latest( $slug );
	if ( ! $rel ) {
		return new WP_Error( 'semrs_no_release', 'No release published.', array( 'status' => 404 ) );
	}
	$out     = array(
		'slug'         => $slug,
		'version'      => $rel['version'],
		'homepage'     => home_url( '/' ),
		'requires'     => isset( $rel['requires'] ) ? $rel['requires'] : '6.5',
		'tested'       => isset( $rel['tested'] ) ? $rel['tested'] : '',
		'requires_php' => isset( $rel['requires_php'] ) ? $rel['requires_php'] : '7.4',
		'last_updated' => isset( $rel['date'] ) ? $rel['date'] : '',
		'changelog'    => isset( $rel['changelog'] ) ? wpautop( wp_kses_post( $rel['changelog'] ) ) : '',
		'notice'       => isset( $rel['notice'] ) ? $rel['notice'] : '',
		'license'      => $license,
		'package'      => '',
		'icons'        => semrs_us_art( $slug, 'icon' ),
		'banners'      => semrs_us_art( $slug, 'banner' ),
	);
	// Free products always get a package; Pro only with a valid license.
	if ( 'free' === $license || 'valid' === $license ) {
		$expires        = time() + 6 * HOUR_IN_SECONDS;
		$out['package'] = add_query_arg(
			array(
				'slug'    => $slug,
				'version' => $rel['version'],
				'key'     => $key,
				'host'    => $host,
				'expires' => $expires,
				'token'   => semrs_us_token( $slug, $rel['version'], $key, $host, $expires ),
			),
			rest_url( 'semrs-updates/v1/download' )
		);
	}
	return rest_ensure_response( $out );
}

/**
 * Stream a release zip after verifying the signed token.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_Error|void
 */
function semrs_us_download( $req ) {
	$slug    = sanitize_key( (string) $req->get_param( 'slug' ) );
	$version = preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $req->get_param( 'version' ) );
	$key     = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $req->get_param( 'key' ) );
	$host    = substr( (string) $req->get_param( 'host' ), 0, 191 );
	$expires = (int) $req->get_param( 'expires' );
	$token   = (string) $req->get_param( 'token' );
	if ( $expires < time() || ! hash_equals( semrs_us_token( $slug, $version, $key, $host, $expires ), $token ) ) {
		return new WP_Error( 'semrs_expired', 'Download link expired. Check for updates again in WordPress.', array( 'status' => 403 ) );
	}
	$all = semrs_us_releases();
	if ( empty( $all[ $slug ][ $version ]['file'] ) ) {
		return new WP_Error( 'semrs_missing', 'Release not found.', array( 'status' => 404 ) );
	}
	$file = semrs_us_dir() . basename( $all[ $slug ][ $version ]['file'] );
	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'semrs_missing', 'Release file missing.', array( 'status' => 404 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $slug . '.zip"' );
	header( 'Content-Length: ' . filesize( $file ) );
	readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

/**
 * Public download of the latest release of a FREE product (store buttons).
 * Paid products are never served here; they need a signed update link.
 *
 * @param WP_REST_Request $req Request (slug).
 * @return WP_Error|void
 */
function semrs_us_free_download( $req ) {
	$slug     = sanitize_key( (string) $req->get_param( 'slug' ) );
	$products = semrs_us_products();
	if ( ! isset( $products[ $slug ] ) || ! empty( $products[ $slug ][2] ) ) {
		return new WP_Error( 'semrs_not_free', 'This product is not a free download.', array( 'status' => 404 ) );
	}
	$latest = semrs_us_latest( $slug );
	$file   = $latest && ! empty( $latest['file'] ) ? semrs_us_dir() . basename( $latest['file'] ) : '';
	if ( ! $file || ! file_exists( $file ) ) {
		return new WP_Error( 'semrs_missing', 'Release not found.', array( 'status' => 404 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $slug . '.zip"' );
	header( 'Content-Length: ' . filesize( $file ) );
	readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

/**
 * Activate a Pro key on a site (Activate button in SEMRS Pro → License).
 *
 * @param WP_REST_Request $req Request (key, site, version).
 * @return WP_REST_Response
 */
function semrs_us_activate( $req ) {
	$key     = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $req->get_param( 'key' ) );
	$host    = semrs_us_host( (string) $req->get_param( 'site' ) );
	$version = preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $req->get_param( 'version' ) );
	if ( ! $host ) {
		return rest_ensure_response( array( 'license' => 'invalid', 'message' => 'Missing site address.' ) );
	}
	$status = semrs_us_license_status( $key, $host );
	if ( 'valid' === $status ) {
		semrs_us_record( 'semrs-pro', $host, $version, '', '', $key );
	}
	$keys     = semrs_us_keys();
	$messages = array(
		'valid'   => 'License activated on this site.',
		'expired' => 'This license has expired. Renew it to get updates and support.',
		'invalid' => 'This key is not valid. Check it for typos.',
		'limit'   => 'This key is already active on its maximum number of sites. Deactivate it on another site first.',
		'missing' => 'Enter a license key.',
	);
	return rest_ensure_response(
		array(
			'license' => $status,
			'message' => $messages[ $status ],
			'expires' => isset( $keys[ $key ]['expires'] ) ? $keys[ $key ]['expires'] : '',
			'sites'   => isset( $keys[ $key ]['sites'] ) ? (int) $keys[ $key ]['sites'] : 0,
			'addons'  => isset( $keys[ $key ] ) && in_array( $status, array( 'valid', 'expired' ), true ) ? semrs_us_key_addons( $keys[ $key ] ) : array(),
			'manage'  => semrs_us_account_url(),
		)
	);
}

/**
 * License status for a site that already uses a key (daily check from SEMRS Pro).
 * Paid add-ons stay listed after expiry; only refunds and revoked keys remove them.
 *
 * @param WP_REST_Request $req Request (key, site, version).
 * @return WP_REST_Response
 */
function semrs_us_license( $req ) {
	$key     = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $req->get_param( 'key' ) );
	$host    = semrs_us_host( (string) $req->get_param( 'site' ) );
	$version = preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $req->get_param( 'version' ) );
	$status  = $host ? semrs_us_license_status( $key, $host ) : 'invalid';
	if ( 'valid' === $status ) {
		semrs_us_record( 'semrs-pro', $host, $version, '', '', $key ); // Keeps the slot.
	}
	$k = semrs_us_keys()[ $key ] ?? null;
	return rest_ensure_response(
		array(
			'license' => $status,
			'expires' => $k ? $k['expires'] : '',
			'sites'   => $k ? (int) $k['sites'] : 0,
			'addons'  => $k && in_array( $status, array( 'valid', 'expired', 'limit' ), true ) ? semrs_us_key_addons( $k ) : array(),
			'manage'  => semrs_us_account_url(),
		)
	);
}

/**
 * Deactivate a Pro key on a site (frees the slot).
 *
 * @param WP_REST_Request $req Request (key, site).
 * @return WP_REST_Response
 */
function semrs_us_deactivate( $req ) {
	global $wpdb;
	$key  = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $req->get_param( 'key' ) );
	$host = semrs_us_host( (string) $req->get_param( 'site' ) );
	$done = 0;
	if ( $key && $host ) {
		$done = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}semrs_sites SET license_key = '' WHERE slug = 'semrs-pro' AND host = %s AND license_key = %s", $host, $key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
	return rest_ensure_response( array( 'deactivated' => $done > 0, 'message' => $done ? 'License deactivated on this site. The slot is free.' : 'This key was not active on this site.' ) );
}

/**
 * Icon / banner URLs for WordPress's update screens (files in assets/art/).
 *
 * @param string $slug Product slug.
 * @param string $kind icon|banner.
 * @return array
 */
function semrs_us_art( $slug, $kind ) {
	$sizes = 'icon' === $kind ? array( '1x' => '128x128', '2x' => '256x256' ) : array( 'low' => '772x250', 'high' => '1544x500' );
	$out   = array();
	foreach ( $sizes as $key => $size ) {
		$file = 'assets/art/' . sanitize_key( $slug ) . '/' . $kind . '-' . $size . '.png';
		if ( file_exists( SEMRS_US_DIR . $file ) ) {
			$out[ $key ] = SEMRS_US_URL . $file;
		}
	}
	return $out;
}
