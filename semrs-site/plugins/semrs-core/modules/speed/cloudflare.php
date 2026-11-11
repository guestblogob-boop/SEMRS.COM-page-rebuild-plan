<?php
/**
 * SEMRS Speed → Cloudflare.
 *
 * - Connect with an API token; the zone is found from the site address.
 * - Auto purge: a post update clears only its own pages at Cloudflare
 *   (post, home, its categories, tags, author and feeds); site-wide changes
 *   (theme, menus, settings, "Clear cache") clear everything.
 * - Purge everything and development mode buttons.
 * - Recommended Cloudflare settings in one click (the old values are saved,
 *   and one click puts them back).
 * - Edge page cache (opt-in): logged-out pages are cached in Cloudflare's
 *   data centers with a Cache Rule, like the paid APO. Admin, log-in, cart,
 *   checkout, logged-in users and commenters are never cached.
 * - Real visitor IP addresses behind Cloudflare.
 *
 * Steps aside when the official Cloudflare plugin or WP Rocket's Cloudflare
 * add-on already purges the cache.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings.
 *
 * @return array
 */
function semrs_cf_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_cf', array() ),
		array(
			'token'   => '',
			'email'   => '',
			'key'     => '',
			'zone'    => '',
			'zone_name' => '',
			'purge'   => '1',
			'real_ip' => '1',
			'edge'    => '',
			'prev'    => array(),
		)
	);
}

/**
 * Connected?
 *
 * @return bool
 */
function semrs_cf_connected() {
	$s = semrs_cf_settings();
	return '' !== $s['zone'] && ( '' !== $s['token'] || ( '' !== $s['email'] && '' !== $s['key'] ) );
}

/**
 * Another plugin that already purges Cloudflare (its name), or ''.
 *
 * @return string
 */
function semrs_cf_other() {
	if ( defined( 'CLOUDFLARE_PLUGIN_DIR' ) || class_exists( 'CF\WordPress\Hooks', false ) ) {
		return 'Cloudflare';
	}
	if ( function_exists( 'get_rocket_option' ) && get_rocket_option( 'do_cloudflare' ) ) {
		return 'WP Rocket';
	}
	return '';
}

/**
 * Call the Cloudflare API.
 *
 * @param string     $method HTTP method.
 * @param string     $path   Path after /client/v4.
 * @param array|null $body   JSON body.
 * @param array|null $auth   Credentials to use (default: saved ones).
 * @return array|WP_Error Decoded "result" (or the whole answer when there is none).
 */
function semrs_cf_api( $method, $path, $body = null, $auth = null ) {
	$s = $auth ? $auth : semrs_cf_settings();
	$h = array( 'Content-Type' => 'application/json' );
	if ( ! empty( $s['token'] ) ) {
		$h['Authorization'] = 'Bearer ' . $s['token'];
	} else {
		$h['X-Auth-Email'] = (string) $s['email'];
		$h['X-Auth-Key']   = (string) $s['key'];
	}
	$res = wp_remote_request(
		'https://api.cloudflare.com/client/v4' . $path,
		array(
			'method'  => $method,
			'headers' => $h,
			'body'    => null === $body ? null : wp_json_encode( $body ),
			'timeout' => 20,
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$j = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $j ) || empty( $j['success'] ) ) {
		$msg = '';
		foreach ( (array) ( $j['errors'] ?? array() ) as $e ) {
			$msg .= ( $msg ? '; ' : '' ) . ( $e['message'] ?? '' ) . ( isset( $e['code'] ) ? ' (' . $e['code'] . ')' : '' );
		}
		return new WP_Error( 'semrs_cf', $msg ? $msg : 'Cloudflare answered ' . wp_remote_retrieve_response_code( $res ), array( 'status' => (int) wp_remote_retrieve_response_code( $res ) ) );
	}
	return array_key_exists( 'result', $j ) && null !== $j['result'] ? $j['result'] : $j;
}

/**
 * Find the zone for this site (tries the host, then its parent domains).
 *
 * @param array $auth Credentials.
 * @return array|WP_Error [id, name].
 */
function semrs_cf_find_zone( $auth ) {
	$host  = strtolower( preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
	$parts = explode( '.', $host );
	while ( count( $parts ) >= 2 ) {
		$name = implode( '.', $parts );
		$r    = semrs_cf_api( 'GET', '/zones?name=' . rawurlencode( $name ), null, $auth );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		if ( ! empty( $r[0]['id'] ) ) {
			return array( $r[0]['id'], $r[0]['name'] );
		}
		array_shift( $parts );
	}
	/* translators: %s: domain */
	return new WP_Error( 'semrs_cf', sprintf( __( 'No Cloudflare zone for %s was found with these details. Check that the site is added to this Cloudflare account and that the token can read zones.', 'semrs-core' ), $host ) );
}

/*
 * Real visitor IP addresses.
 */

/**
 * Cloudflare's address ranges.
 *
 * @return string[]
 */
function semrs_cf_ranges() {
	return function_exists( 'semrs_security_cloudflare_ranges' ) ? semrs_security_cloudflare_ranges() : array( '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32' );
}

/**
 * IP in a CIDR range?
 *
 * @param string $ip    IP.
 * @param string $range Range.
 * @return bool
 */
function semrs_cf_ip_in( $ip, $range ) {
	if ( function_exists( 'semrs_security_ip_in' ) ) {
		return semrs_security_ip_in( $ip, $range );
	}
	list( $net, $bits ) = explode( '/', $range, 2 );
	$a = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$b = @inet_pton( $net ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( false === $a || false === $b || strlen( $a ) !== strlen( $b ) ) {
		return false;
	}
	$bits  = (int) $bits;
	$bytes = intdiv( $bits, 8 );
	if ( substr( $a, 0, $bytes ) !== substr( $b, 0, $bytes ) ) {
		return false;
	}
	$rest = $bits % 8;
	return ! $rest || ( ( $a[ $bytes ] & chr( ( 0xff << ( 8 - $rest ) ) & 0xff ) ) === ( $b[ $bytes ] & chr( ( 0xff << ( 8 - $rest ) ) & 0xff ) ) );
}

if ( '1' === semrs_cf_settings()['real_ip'] && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
	$semrs_cf_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
	$semrs_cf_ra = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	if ( filter_var( $semrs_cf_ip, FILTER_VALIDATE_IP ) && $semrs_cf_ip !== $semrs_cf_ra ) {
		foreach ( semrs_cf_ranges() as $semrs_cf_r ) {
			if ( semrs_cf_ip_in( $semrs_cf_ra, $semrs_cf_r ) ) {
				$_SERVER['REMOTE_ADDR'] = $semrs_cf_ip; // Only when the request really came through Cloudflare.
				break;
			}
		}
	}
	unset( $semrs_cf_ip, $semrs_cf_ra, $semrs_cf_r );
}

/*
 * Purging.
 */

/**
 * Purge everything.
 *
 * @return true|WP_Error
 */
function semrs_cf_purge_all() {
	$s = semrs_cf_settings();
	$r = semrs_cf_api( 'POST', '/zones/' . rawurlencode( $s['zone'] ) . '/purge_cache', array( 'purge_everything' => true ) );
	return is_wp_error( $r ) ? $r : true;
}

/**
 * Purge some addresses (30 per call).
 *
 * @param string[] $urls URLs.
 * @return true|WP_Error
 */
function semrs_cf_purge_urls( $urls ) {
	$s    = semrs_cf_settings();
	$urls = array_values( array_unique( array_filter( $urls ) ) );
	foreach ( array_chunk( $urls, 30 ) as $chunk ) {
		$r = semrs_cf_api( 'POST', '/zones/' . rawurlencode( $s['zone'] ) . '/purge_cache', array( 'files' => $chunk ) );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
	}
	return true;
}

/**
 * Pages that show a post.
 *
 * @param WP_Post $post Post.
 * @return string[]
 */
function semrs_cf_post_urls( $post ) {
	$urls = array( get_permalink( $post ), home_url( '/' ), get_bloginfo( 'rss2_url' ) );
	if ( 'post' === $post->post_type ) {
		$urls[] = get_author_posts_url( (int) $post->post_author );
		$page   = (int) get_option( 'page_for_posts' );
		if ( $page ) {
			$urls[] = get_permalink( $page );
		}
	} elseif ( get_post_type_archive_link( $post->post_type ) ) {
		$urls[] = get_post_type_archive_link( $post->post_type );
	}
	foreach ( get_object_taxonomies( $post->post_type ) as $tax ) {
		if ( ! is_taxonomy_viewable( $tax ) ) {
			continue;
		}
		foreach ( (array) wp_get_post_terms( $post->ID, $tax ) as $t ) {
			if ( ! is_wp_error( $t ) ) {
				$urls[] = get_term_link( $t );
			}
		}
	}
	$out = array();
	foreach ( $urls as $u ) {
		if ( is_string( $u ) && '' !== $u ) {
			$out[] = $u;
			$out[] = trailingslashit( $u ) . 'feed/';
		}
	}
	return (array) apply_filters( 'semrs_cf_post_urls', array_values( array_unique( $out ) ), $post );
}

/**
 * What to purge at the end of this request.
 *
 * @param string|string[]|null $add 'all', URLs, or null to read.
 * @return array
 */
function semrs_cf_queue( $add = null ) {
	static $q = array( 'all' => false, 'urls' => array() );
	if ( 'all' === $add ) {
		$q['all'] = true;
	} elseif ( is_array( $add ) ) {
		$q['urls'] = array_merge( $q['urls'], $add );
	}
	return $q;
}

if ( '1' === semrs_cf_settings()['purge'] && semrs_cf_connected() ) {
	// A published post changed: purge only its pages (runs before SEMRS's own page-cache purge).
	add_action(
		'transition_post_status',
		function ( $new, $old, $post ) {
			if ( ( 'publish' === $new || 'publish' === $old ) && ! wp_is_post_revision( $post ) && is_post_type_viewable( $post->post_type ) ) {
				$GLOBALS['semrs_cf_post_change'] = true;
				semrs_cf_queue( semrs_cf_post_urls( $post ) );
			}
		},
		5,
		3
	);
	add_action(
		'save_post',
		function ( $id, $post ) {
			if ( 'publish' === $post->post_status && ! wp_is_post_revision( $id ) && is_post_type_viewable( $post->post_type ) ) {
				$GLOBALS['semrs_cf_post_change'] = true;
				semrs_cf_queue( semrs_cf_post_urls( $post ) );
			}
		},
		5,
		2
	);
	// Anything else SEMRS clears the page cache for (theme, menus, settings, "Clear cache"): purge everything.
	add_action(
		'semrs_cache_purged',
		function () {
			if ( empty( $GLOBALS['semrs_cf_post_change'] ) || doing_action( 'admin_post_semrs_cache_clear' ) ) {
				semrs_cf_queue( 'all' );
			}
		}
	);
	add_action(
		'shutdown',
		function () {
			if ( semrs_cf_other() ) {
				return;
			}
			$q = semrs_cf_queue();
			if ( $q['all'] ) {
				semrs_cf_purge_all();
			} elseif ( $q['urls'] ) {
				semrs_cf_purge_urls( $q['urls'] );
			}
		},
		5
	);
}

/*
 * Recommended settings and edge page cache.
 */

/**
 * Recommended zone settings: id => [value, label].
 *
 * @return array
 */
function semrs_cf_recommended() {
	return array(
		'always_use_https'         => array( 'on', __( 'Always use HTTPS', 'semrs-core' ) ),
		'automatic_https_rewrites' => array( 'on', __( 'Fix mixed content (HTTP links on HTTPS pages)', 'semrs-core' ) ),
		'min_tls_version'          => array( '1.2', __( 'Minimum TLS 1.2 (older versions are insecure)', 'semrs-core' ) ),
		'tls_1_3'                  => array( 'on', __( 'TLS 1.3 (faster, safer connections)', 'semrs-core' ) ),
		'http3'                    => array( 'on', __( 'HTTP/3', 'semrs-core' ) ),
		'early_hints'              => array( 'on', __( 'Early Hints (starts loading files sooner)', 'semrs-core' ) ),
		'browser_cache_ttl'        => array( 0, __( 'Browser cache: respect the headers SEMRS sends', 'semrs-core' ) ),
		'rocket_loader'            => array( 'off', __( 'Rocket Loader off (it can break scripts; SEMRS already delays them safely)', 'semrs-core' ) ),
		'email_obfuscation'        => array( 'on', __( 'Hide email addresses from spam bots', 'semrs-core' ) ),
	);
}

/**
 * Apply the recommended settings (old values saved first).
 *
 * @return array|WP_Error Lines of the report.
 */
function semrs_cf_apply_recommended() {
	$s    = semrs_cf_settings();
	$cur  = semrs_cf_api( 'GET', '/zones/' . rawurlencode( $s['zone'] ) . '/settings' );
	if ( is_wp_error( $cur ) ) {
		return $cur;
	}
	$now = array();
	foreach ( (array) $cur as $c ) {
		$now[ $c['id'] ] = $c['value'];
	}
	$prev = array();
	$out  = array();
	foreach ( semrs_cf_recommended() as $id => $x ) {
		if ( ! array_key_exists( $id, $now ) ) {
			continue; // Not available on this plan.
		}
		if ( $now[ $id ] === $x[0] ) {
			$out[] = '✓ ' . $x[1];
			continue;
		}
		$r = semrs_cf_api( 'PATCH', '/zones/' . rawurlencode( $s['zone'] ) . '/settings/' . $id, array( 'value' => $x[0] ) );
		if ( is_wp_error( $r ) ) {
			$out[] = '✗ ' . $x[1] . ': ' . $r->get_error_message();
		} else {
			$prev[ $id ] = $now[ $id ];
			$out[]       = '✓ ' . $x[1] . ' (' . __( 'changed', 'semrs-core' ) . ')';
		}
	}
	if ( $prev ) {
		$s['prev'] = $prev + (array) $s['prev'];
		update_option( 'semrs_cf', $s, false );
	}
	if ( isset( $now['ssl'] ) && 'flexible' === $now['ssl'] ) {
		$out[] = '! ' . __( 'SSL is set to Flexible. Your server has HTTPS, so set SSL/TLS to “Full (strict)” in Cloudflare to avoid redirect loops and keep traffic encrypted all the way.', 'semrs-core' );
	}
	return $out;
}

/**
 * Put back the values that were there before.
 *
 * @return true|WP_Error
 */
function semrs_cf_restore_previous() {
	$s = semrs_cf_settings();
	foreach ( (array) $s['prev'] as $id => $v ) {
		$r = semrs_cf_api( 'PATCH', '/zones/' . rawurlencode( $s['zone'] ) . '/settings/' . $id, array( 'value' => $v ) );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
	}
	$s['prev'] = array();
	update_option( 'semrs_cf', $s, false );
	return true;
}

/**
 * The Cache Rule expression: logged-out GET requests for normal pages.
 *
 * @return string
 */
function semrs_cf_edge_expression() {
	$host  = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$paths = array( '/wp-admin', '/wp-login.php', '/wp-json', '/xmlrpc.php', '/wp-cron.php', '/cart', '/checkout', '/my-account', '/basket', '/order', '/account' );
	if ( function_exists( 'semrs_login_slug' ) && semrs_login_slug() ) {
		$paths[] = '/' . trim( semrs_login_slug(), '/' );
	}
	$skip = (array) apply_filters( 'semrs_cf_edge_skip_paths', $paths );
	$expr = '(http.host eq "' . $host . '" and http.request.method in {"GET" "HEAD"}';
	foreach ( $skip as $p ) {
		$expr .= ' and not starts_with(http.request.uri.path, "' . str_replace( '"', '', $p ) . '")';
	}
	foreach ( array( 'wordpress_logged_in', 'wp-postpass', 'comment_author', 'woocommerce_items_in_cart', 'wp_woocommerce_session', 'edd_items_in_cart', 'wordpress_no_cache' ) as $c ) {
		$expr .= ' and not http.cookie contains "' . $c . '"';
	}
	foreach ( array( 'preview', 'add-to-cart', 'nocache', 's=' ) as $q ) {
		$expr .= ' and not http.request.uri.query contains "' . $q . '"';
	}
	return $expr . ' and not http.request.uri.path contains "/feed")';
}

/**
 * Switch the edge page cache rule on or off (other rules on the zone are kept).
 *
 * @param bool $on On.
 * @return true|WP_Error
 */
function semrs_cf_edge_set( $on ) {
	$s     = semrs_cf_settings();
	$base  = '/zones/' . rawurlencode( $s['zone'] ) . '/rulesets/phases/http_request_cache_settings/entrypoint';
	$cur   = semrs_cf_api( 'GET', $base );
	$rules = array();
	if ( is_wp_error( $cur ) ) {
		if ( 404 !== (int) ( $cur->get_error_data()['status'] ?? 0 ) ) {
			return $cur;
		}
	} else {
		foreach ( (array) ( $cur['rules'] ?? array() ) as $r ) {
			if ( 0 !== strpos( (string) ( $r['description'] ?? '' ), 'SEMRS' ) ) {
				$rules[] = array_intersect_key( $r, array_flip( array( 'expression', 'action', 'action_parameters', 'description', 'enabled' ) ) );
			}
		}
	}
	if ( $on ) {
		$ttl     = function_exists( 'semrs_speed_settings' ) ? max( 1, min( 10, (int) ( semrs_speed_settings()['cache_ttl'] ?? 10 ) ) ) * HOUR_IN_SECONDS : 10 * HOUR_IN_SECONDS;
		$rules[] = array(
			'description'       => 'SEMRS: cache pages at the edge (logged-out visitors)',
			'expression'        => semrs_cf_edge_expression(),
			'action'            => 'set_cache_settings',
			'action_parameters' => array(
				'cache'       => true,
				'edge_ttl'    => array( 'mode' => 'override_origin', 'default' => $ttl ),
				'browser_ttl' => array( 'mode' => 'respect_origin' ),
			),
			'enabled'           => true,
		);
	}
	$r = semrs_cf_api( 'PUT', $base, array( 'rules' => $rules ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$s['edge'] = $on ? '1' : '';
	update_option( 'semrs_cf', $s, false );
	semrs_cf_purge_all();
	return true;
}

if ( is_admin() ) {
	require __DIR__ . '/cloudflare-admin.php';
}
