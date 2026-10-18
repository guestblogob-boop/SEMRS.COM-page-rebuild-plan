<?php
/**
 * Server tools REST API (public, rate-limited, SSRF-safe).
 *
 * GET /wp-json/semrs-tools/v1/redirects?url=https://example.com
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register routes.
 */
function semrs_tools_rest_routes() {
	register_rest_route(
		'semrs-tools/v1',
		'/redirects',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_tools_rest_redirects',
			'args'                => array(
				'url' => array(
					'required'          => true,
					'sanitize_callback' => 'esc_url_raw',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'semrs_tools_rest_routes' );

/**
 * Per-IP rate limit. Returns true when the request is allowed.
 *
 * @param string $bucket Name.
 * @param int    $limit  Requests per hour.
 * @return bool
 */
function semrs_tools_rate_ok( $bucket, $limit ) {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'semrs_rl_' . $bucket . '_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= $limit ) {
		return false;
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * Is this URL safe to fetch? http(s) only, standard ports, public IPs only.
 *
 * @param string $url URL.
 * @return bool
 */
function semrs_tools_public_url( $url ) {
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
		return false;
	}
	if ( isset( $parts['port'] ) && ! in_array( (int) $parts['port'], array( 80, 443, 8080, 8443 ), true ) ) {
		return false;
	}
	if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return false;
	}
	$host = trim( $parts['host'], '[]' );
	$ips = filter_var( $host, FILTER_VALIDATE_IP ) ? array( $host ) : (array) gethostbynamel( $host );
	if ( ! array_filter( $ips ) && function_exists( 'dns_get_record' ) ) {
		// IPv6-only hosts: check their AAAA records too.
		foreach ( (array) @dns_get_record( $host, DNS_AAAA ) as $rec ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			if ( ! empty( $rec['ipv6'] ) ) {
				$ips[] = $rec['ipv6'];
			}
		}
	}
	$ips = array_filter( $ips );
	if ( ! $ips ) {
		return false;
	}
	foreach ( $ips as $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return false;
		}
	}
	return (bool) wp_http_validate_url( $url );
}

/**
 * Trace redirects hop by hop (max 10), validating every hop.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_tools_rest_redirects( $req ) {
	$url = trim( (string) $req->get_param( 'url' ) );
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		$url = 'https://' . ltrim( $url, '/' );
	}
	if ( ! semrs_tools_public_url( $url ) ) {
		return new WP_Error( 'semrs_bad_url', __( 'Enter a public website address, like https://example.com/page/.', 'semrs-tools' ), array( 'status' => 400 ) );
	}
	$cache_key = 'semrs_rc_' . md5( $url );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}
	if ( ! semrs_tools_rate_ok( 'redirects', 30 ) ) {
		return new WP_Error( 'semrs_rate', __( 'Limit reached (30 checks per hour). Please try again later.', 'semrs-tools' ), array( 'status' => 429 ) );
	}

	$hops    = array();
	$seen    = array();
	$current = $url;
	$args    = array(
		'redirection'        => 0,
		'timeout'            => 8,
		'reject_unsafe_urls' => true,
		'user-agent'         => 'Mozilla/5.0 (compatible; SEMRS-RedirectChecker/1.0; +' . home_url( '/tools/redirect-checker/' ) . ')',
		'headers'            => array( 'Accept' => 'text/html,*/*' ),
		'limit_response_size' => 1024,
	);

	for ( $i = 0; $i < 10; $i++ ) {
		if ( isset( $seen[ $current ] ) ) {
			$hops[] = array( 'url' => $current, 'status' => 0, 'location' => '', 'note' => 'loop' );
			break;
		}
		$seen[ $current ] = true;
		if ( ! semrs_tools_public_url( $current ) ) {
			$hops[] = array( 'url' => $current, 'status' => 0, 'location' => '', 'note' => 'blocked' );
			break;
		}
		$start = microtime( true );
		$res   = wp_remote_head( $current, $args );
		if ( ! is_wp_error( $res ) && in_array( (int) wp_remote_retrieve_response_code( $res ), array( 403, 405, 501 ), true ) ) {
			$res = wp_remote_get( $current, $args ); // Some servers refuse HEAD.
		}
		$ms = (int) round( ( microtime( true ) - $start ) * 1000 );
		if ( is_wp_error( $res ) ) {
			$hops[] = array( 'url' => $current, 'status' => 0, 'location' => '', 'note' => $res->get_error_message(), 'ms' => $ms );
			break;
		}
		$code     = (int) wp_remote_retrieve_response_code( $res );
		$location = (string) wp_remote_retrieve_header( $res, 'location' );
		if ( $location ) {
			$location = WP_Http::make_absolute_url( $location, $current );
		}
		$hops[] = array(
			'url'      => $current,
			'status'   => $code,
			'location' => $location,
			'ms'       => $ms,
			'robots'   => (string) wp_remote_retrieve_header( $res, 'x-robots-tag' ),
		);
		if ( $code >= 300 && $code < 400 && $location ) {
			$current = $location;
			continue;
		}
		break;
	}

	$data = array(
		'url'     => $url,
		'hops'    => $hops,
		'checked' => gmdate( 'c' ),
	);
	set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );
	return rest_ensure_response( $data );
}
