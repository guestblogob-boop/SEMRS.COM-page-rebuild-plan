<?php
/**
 * Releases and license keys (stored as options; small data sets).
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * All releases: slug => [ version => release data ].
 *
 * @return array
 */
function semrs_us_releases() {
	$r = get_option( 'semrs_us_releases', array() );
	return is_array( $r ) ? $r : array();
}

/**
 * Latest release for a product, or null.
 *
 * @param string $slug Product slug.
 * @return array|null
 */
function semrs_us_latest( $slug ) {
	$all = semrs_us_releases();
	if ( empty( $all[ $slug ] ) ) {
		return null;
	}
	$versions = array_keys( $all[ $slug ] );
	usort( $versions, 'version_compare' );
	$latest = end( $versions );
	return array_merge( $all[ $slug ][ $latest ], array( 'version' => $latest ) );
}

/**
 * Save a release.
 *
 * @param string $slug    Product slug.
 * @param string $version Version.
 * @param array  $data    file, changelog, requires, tested, requires_php, notice.
 */
function semrs_us_save_release( $slug, $version, $data ) {
	$all                       = semrs_us_releases();
	$all[ $slug ][ $version ]  = array_merge( $data, array( 'date' => gmdate( 'Y-m-d H:i:s' ) ) );
	update_option( 'semrs_us_releases', $all, false );
}

/**
 * All license keys: key => data.
 *
 * @return array
 */
function semrs_us_keys() {
	$k = get_option( 'semrs_us_keys', array() );
	return is_array( $k ) ? $k : array();
}

/**
 * Create a license key.
 *
 * @param string $email   Customer email.
 * @param int    $sites   Max active sites (0 = unlimited).
 * @param string $expires Y-m-d or '' (never).
 * @return string The key.
 */
function semrs_us_create_key( $email, $sites, $expires ) {
	$key  = strtoupper( implode( '-', str_split( substr( bin2hex( random_bytes( 10 ) ), 0, 16 ), 4 ) ) );
	$all  = semrs_us_keys();
	$all[ $key ] = array(
		'email'   => sanitize_email( $email ),
		'sites'   => max( 0, (int) $sites ),
		'expires' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expires ) ? $expires : '',
		'status'  => 'active',
		'created' => gmdate( 'Y-m-d' ),
	);
	update_option( 'semrs_us_keys', $all, false );
	return $key;
}

/**
 * Normalise a site URL to a host (+ path for subfolder installs).
 *
 * @param string $site Site URL.
 * @return string
 */
function semrs_us_host( $site ) {
	$p = wp_parse_url( (string) $site );
	if ( empty( $p['host'] ) ) {
		return '';
	}
	$host = strtolower( preg_replace( '/^www\./', '', $p['host'] ) );
	$path = isset( $p['path'] ) ? untrailingslashit( $p['path'] ) : '';
	return substr( $host . $path, 0, 191 );
}

/**
 * Is a host a local/staging install? (Does not count towards site limits.)
 *
 * @param string $host Host.
 * @return bool
 */
function semrs_us_is_dev_host( $host ) {
	return (bool) preg_match( '/^(localhost|127\.0\.0\.1|\[::1\])|\.(test|local|localhost|invalid|example)(\/|$)|^(staging|test|dev)\./', $host );
}

/**
 * License status for a key + site.
 *
 * @param string $key  License key.
 * @param string $host Site host.
 * @return string valid|expired|invalid|limit|missing
 */
function semrs_us_license_status( $key, $host ) {
	global $wpdb;
	if ( '' === $key ) {
		return 'missing';
	}
	$all = semrs_us_keys();
	if ( empty( $all[ $key ] ) || 'active' !== $all[ $key ]['status'] ) {
		return 'invalid';
	}
	$k = $all[ $key ];
	if ( $k['expires'] && strtotime( $k['expires'] . ' 23:59:59 UTC' ) < time() ) {
		return 'expired';
	}
	if ( $k['sites'] > 0 && ! semrs_us_is_dev_host( $host ) ) {
		$hosts = $wpdb->get_col( $wpdb->prepare( "SELECT host FROM {$wpdb->prefix}semrs_sites WHERE slug = 'semrs-pro' AND license_key = %s AND last_seen > %s", $key, gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$hosts = array_values( array_filter( (array) $hosts, function ( $h ) { return ! semrs_us_is_dev_host( $h ); } ) );
		if ( ! in_array( $host, $hosts, true ) && count( $hosts ) >= $k['sites'] ) {
			return 'limit';
		}
	}
	return 'valid';
}

/**
 * Record a check-in from a site.
 *
 * @param string $slug    Product.
 * @param string $host    Host.
 * @param string $version Installed version.
 * @param string $wp      WP version.
 * @param string $php     PHP version.
 * @param string $key     License key.
 */
function semrs_us_record( $slug, $host, $version, $wp, $php, $key ) {
	global $wpdb;
	$now = gmdate( 'Y-m-d H:i:s' );
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}semrs_sites (slug, host, version, wp, php, license_key, first_seen, last_seen) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
			ON DUPLICATE KEY UPDATE version = VALUES(version), wp = VALUES(wp), php = VALUES(php), license_key = VALUES(license_key), last_seen = VALUES(last_seen)",
			$slug,
			$host,
			substr( $version, 0, 32 ),
			substr( $wp, 0, 16 ),
			substr( $php, 0, 16 ),
			substr( $key, 0, 64 ),
			$now,
			$now
		)
	);
}

/**
 * Signed, expiring download token.
 *
 * @param string $slug    Product.
 * @param string $version Version.
 * @param string $key     License key.
 * @param string $host    Host.
 * @param int    $expires Unix time.
 * @return string
 */
function semrs_us_token( $slug, $version, $key, $host, $expires ) {
	return hash_hmac( 'sha256', implode( '|', array( $slug, $version, $key, $host, $expires ) ), wp_salt( 'auth' ) );
}
