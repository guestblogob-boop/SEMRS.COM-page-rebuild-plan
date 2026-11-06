<?php
/**
 * Server tools REST API (public, rate-limited, SSRF-safe).
 *
 * GET  /wp-json/semrs-tools/v1/redirects?url=https://example.com   Redirect Checker
 * GET  /wp-json/semrs-tools/v1/onpage?url=https://example.com      On-Page SEO Checker
 * GET  /wp-json/semrs-tools/v1/robots?url=https://example.com      Robots.txt Tester
 * POST /wp-json/semrs-tools/v1/usage?tool=…&ev=…                   Pro usage report counter
 *
 * Every hop of every request is checked: http(s) only, standard ports and
 * public IP addresses only, so the tools can never reach internal servers.
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register routes.
 */
function semrs_tools_rest_routes() {
	$url_arg = array(
		'url' => array(
			'required'          => true,
			'sanitize_callback' => 'esc_url_raw',
		),
	);
	foreach ( array( 'redirects' => 'semrs_tools_rest_redirects', 'onpage' => 'semrs_tools_rest_onpage', 'robots' => 'semrs_tools_rest_robots' ) as $route => $cb ) {
		register_rest_route(
			'semrs-tools/v1',
			'/' . $route,
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => $cb,
				'args'                => $url_arg,
			)
		);
	}
	register_rest_route(
		'semrs-tools/v1',
		'/usage',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_tools_rest_usage',
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
	$ips  = filter_var( $host, FILTER_VALIDATE_IP ) ? array( $host ) : (array) gethostbynamel( $host );
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
 * Shared start of every server tool: tool switched on, URL normalised and
 * public, cached answer, rate limit.
 *
 * @param WP_REST_Request $req    Request.
 * @param string          $tool   Tool id.
 * @param string          $prefix Cache prefix.
 * @return array|WP_Error|WP_REST_Response [url, cache key] or an error / cached response.
 */
function semrs_tools_rest_begin( $req, $tool, $prefix ) {
	if ( ! semrs_tools_enabled( $tool ) ) {
		return new WP_Error( 'semrs_off', __( 'This tool is switched off.', 'semrs-tools' ), array( 'status' => 404 ) );
	}
	$url = trim( (string) $req->get_param( 'url' ) );
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		$url = 'https://' . ltrim( $url, '/' );
	}
	if ( ! semrs_tools_public_url( $url ) ) {
		return new WP_Error( 'semrs_bad_url', __( 'Enter a public website address, like https://example.com/page/.', 'semrs-tools' ), array( 'status' => 400 ) );
	}
	$key    = $prefix . md5( $url );
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}
	$limit = semrs_tools_hourly_limit();
	if ( ! semrs_tools_rate_ok( 'server', $limit ) ) {
		/* translators: %d: checks per hour */
		return new WP_Error( 'semrs_rate', sprintf( __( 'Limit reached (%d checks per hour). Please try again later.', 'semrs-tools' ), $limit ), array( 'status' => 429 ) );
	}
	return array( $url, $key );
}

/**
 * Save a result for the configured number of minutes.
 *
 * @param string $key  Cache key.
 * @param array  $data Result.
 */
function semrs_tools_rest_cache( $key, $data ) {
	$min = (int) semrs_tools_options()['cache'];
	if ( $min > 0 ) {
		set_transient( $key, $data, $min * MINUTE_IN_SECONDS );
	}
}

/**
 * Follow redirects hop by hop (max 10), validating every hop.
 *
 * @param string $url    Start URL.
 * @param bool   $body   Fetch the final body (GET) instead of HEAD.
 * @param int    $bytes  Max body size.
 * @return array{hops:array, response:array|WP_Error|null}
 */
function semrs_tools_follow( $url, $body = false, $bytes = 1024 ) {
	$hops    = array();
	$seen    = array();
	$current = $url;
	$res     = null;
	$args    = array(
		'redirection'         => 0,
		'timeout'             => $body ? 12 : 8,
		'reject_unsafe_urls'  => true,
		'user-agent'          => 'Mozilla/5.0 (compatible; SEMRS-Tools/1.4; +' . home_url( '/tools/' ) . ')',
		'headers'             => array( 'Accept' => 'text/html,application/xhtml+xml,text/plain,*/*' ),
		'limit_response_size' => $bytes,
	);
	for ( $i = 0; $i < 10; $i++ ) {
		if ( isset( $seen[ $current ] ) ) {
			$hops[] = array( 'url' => $current, 'status' => 0, 'location' => '', 'note' => 'loop' );
			$res    = null;
			break;
		}
		$seen[ $current ] = true;
		if ( ! semrs_tools_public_url( $current ) ) {
			$hops[] = array( 'url' => $current, 'status' => 0, 'location' => '', 'note' => 'blocked' );
			$res    = null;
			break;
		}
		$start = microtime( true );
		$res   = $body ? wp_remote_get( $current, $args ) : wp_remote_head( $current, $args );
		if ( ! $body && ! is_wp_error( $res ) && in_array( (int) wp_remote_retrieve_response_code( $res ), array( 403, 405, 501 ), true ) ) {
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
	return array( 'hops' => $hops, 'response' => $res );
}

/**
 * Redirect Checker.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_tools_rest_redirects( $req ) {
	$begin = semrs_tools_rest_begin( $req, 'redirect-checker', 'semrs_rc_' );
	if ( ! is_array( $begin ) ) {
		return $begin;
	}
	list( $url, $key ) = $begin;
	$r    = semrs_tools_follow( $url );
	$data = array(
		'url'     => $url,
		'hops'    => $r['hops'],
		'checked' => gmdate( 'c' ),
	);
	semrs_tools_rest_cache( $key, $data );
	return rest_ensure_response( $data );
}

/**
 * Text of a DOM node list item, trimmed and shortened.
 *
 * @param DOMNode|null $n   Node.
 * @param int          $max Max length.
 * @return string
 */
function semrs_tools_node_text( $n, $max = 300 ) {
	if ( ! $n ) {
		return '';
	}
	$t = trim( preg_replace( '/\s+/u', ' ', (string) $n->textContent ) );
	return function_exists( 'mb_substr' ) ? mb_substr( $t, 0, $max ) : substr( $t, 0, $max );
}

/**
 * On-Page SEO Checker: fetch one page and report what search engines read.
 * The browser turns these facts into checks and advice.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_tools_rest_onpage( $req ) {
	$begin = semrs_tools_rest_begin( $req, 'on-page-seo-checker', 'semrs_op_' );
	if ( ! is_array( $begin ) ) {
		return $begin;
	}
	list( $url, $key ) = $begin;
	if ( ! class_exists( 'DOMDocument' ) ) {
		return new WP_Error( 'semrs_dom', __( 'This server cannot read HTML (the PHP DOM extension is missing).', 'semrs-tools' ), array( 'status' => 500 ) );
	}
	$r     = semrs_tools_follow( $url, true, 2 * MB_IN_BYTES );
	$res   = $r['response'];
	$final = end( $r['hops'] );
	if ( ! $res || is_wp_error( $res ) || empty( $final['status'] ) ) {
		$note = $final['note'] ?? '';
		/* translators: %s: error */
		return new WP_Error( 'semrs_fetch', sprintf( __( 'Could not load the page%s.', 'semrs-tools' ), $note ? ': ' . $note : '' ), array( 'status' => 502 ) );
	}
	$html  = (string) wp_remote_retrieve_body( $res );
	$type  = (string) wp_remote_retrieve_header( $res, 'content-type' );
	$page  = $final['url'];
	$host  = (string) wp_parse_url( $page, PHP_URL_HOST );
	$data  = array(
		'url'      => $url,
		'final'    => $page,
		'status'   => (int) $final['status'],
		'hops'     => count( $r['hops'] ) - 1,
		'ms'       => array_sum( wp_list_pluck( $r['hops'], 'ms' ) ),
		'bytes'    => strlen( $html ),
		'type'     => $type,
		'xrobots'  => (string) $final['robots'],
		'https'    => 0 === stripos( $page, 'https://' ),
		'checked'  => gmdate( 'c' ),
	);
	if ( false === stripos( $type, 'html' ) && false === stripos( $html, '<html' ) ) {
		$data['nothtml'] = true;
		semrs_tools_rest_cache( $key, $data );
		return rest_ensure_response( $data );
	}

	$prev = libxml_use_internal_errors( true );
	$dom  = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$xp = new DOMXPath( $dom );

	$meta = function ( $attr, $name ) use ( $xp ) {
		$n = $xp->query( '//meta[translate(@' . $attr . ',"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="' . $name . '"]/@content' )->item( 0 );
		return $n ? trim( (string) $n->nodeValue ) : '';
	};
	$link = function ( $rel ) use ( $xp ) {
		$n = $xp->query( '//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="' . $rel . '"]/@href' )->item( 0 );
		return $n ? trim( (string) $n->nodeValue ) : '';
	};

	$data['title']       = semrs_tools_node_text( $xp->query( '//title' )->item( 0 ) );
	$data['titles']      = $xp->query( '//head/title' )->length;
	$data['description'] = $meta( 'name', 'description' );
	$data['descs']       = $xp->query( '//meta[translate(@name,"DESCRIPTION","description")="description"]' )->length;
	$data['robots']      = $meta( 'name', 'robots' );
	$data['viewport']    = $meta( 'name', 'viewport' );
	$data['canonical']   = $link( 'canonical' );
	$data['lang']        = $dom->documentElement ? $dom->documentElement->getAttribute( 'lang' ) : '';
	$data['charset']     = $xp->query( '//meta[@charset]' )->length > 0 || false !== stripos( $type, 'charset' );
	$data['favicon']     = $xp->query( '//link[contains(translate(@rel,"ICON","icon"),"icon")]' )->length > 0;
	$data['og']          = array(
		'title'       => $meta( 'property', 'og:title' ),
		'description' => $meta( 'property', 'og:description' ),
		'image'       => $meta( 'property', 'og:image' ),
		'type'        => $meta( 'property', 'og:type' ),
		'twitter'     => $meta( 'name', 'twitter:card' ),
	);
	$data['hreflang']    = $xp->query( '//link[@rel="alternate"][@hreflang]' )->length;

	$heads = array();
	foreach ( $xp->query( '//h1|//h2|//h3|//h4|//h5|//h6' ) as $hn ) {
		if ( count( $heads ) >= 80 ) {
			break;
		}
		$heads[] = array( (int) substr( $hn->nodeName, 1 ), semrs_tools_node_text( $hn, 140 ) );
	}
	$data['headings'] = $heads;

	$types = array();
	foreach ( $xp->query( '//script[@type="application/ld+json"]' ) as $s ) {
		$j = json_decode( trim( (string) $s->textContent ), true );
		if ( null === $j ) {
			$types[] = '!invalid';
			continue;
		}
		$stack = array( $j );
		while ( $stack && count( $types ) < 30 ) {
			$item = array_pop( $stack );
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( isset( $item['@type'] ) ) {
				foreach ( (array) $item['@type'] as $t ) {
					$types[] = (string) $t;
				}
			}
			foreach ( $item as $v ) {
				if ( is_array( $v ) ) {
					$stack[] = $v;
				}
			}
		}
	}
	$data['schema'] = array_values( array_unique( $types ) );

	// Images a visitor can see: skip ones without a source yet (hidden popups, galleries filled by script) and noscript copies.
	$imgs = $xp->query( '//body//img[(@src and @src!="") or @srcset][not(ancestor::noscript)][not(@aria-hidden="true")]' );
	$noalt = 0;
	$nosize = 0;
	$lazy  = 0;
	foreach ( $imgs as $img ) {
		if ( ! $img->hasAttribute( 'alt' ) ) {
			$noalt++;
		}
		if ( ! $img->hasAttribute( 'width' ) || ! $img->hasAttribute( 'height' ) ) {
			$nosize++;
		}
		if ( 'lazy' === strtolower( $img->getAttribute( 'loading' ) ) ) {
			$lazy++;
		}
	}
	$data['images'] = array( 'total' => $imgs->length, 'noalt' => $noalt, 'nosize' => $nosize, 'lazy' => $lazy );

	$int   = 0;
	$ext   = 0;
	$nof   = 0;
	$empty = 0;
	foreach ( $xp->query( '//body//a[@href]' ) as $a ) {
		$href = trim( $a->getAttribute( 'href' ) );
		if ( '' === $href || '#' === $href[0] || preg_match( '#^(mailto|tel|javascript):#i', $href ) ) {
			continue;
		}
		$abs = WP_Http::make_absolute_url( $href, $page );
		$h   = (string) wp_parse_url( $abs, PHP_URL_HOST );
		if ( $h && strtolower( preg_replace( '/^www\./', '', $h ) ) !== strtolower( preg_replace( '/^www\./', '', $host ) ) ) {
			$ext++;
		} else {
			$int++;
		}
		if ( false !== stripos( $a->getAttribute( 'rel' ), 'nofollow' ) ) {
			$nof++;
		}
		if ( '' === semrs_tools_node_text( $a, 10 ) && '' === trim( $a->getAttribute( 'aria-label' ) . $a->getAttribute( 'aria-labelledby' ) . $a->getAttribute( 'title' ) ) && 0 === $xp->query( './/img[@alt!=""]|.//*[@aria-label!=""]|.//*[local-name()="title"]', $a )->length ) {
			$empty++;
		}
	}
	$data['links'] = array( 'internal' => $int, 'external' => $ext, 'nofollow' => $nof, 'empty' => $empty );

	foreach ( $xp->query( '//script|//style|//noscript|//template' ) as $n ) {
		$n->parentNode->removeChild( $n );
	}
	$body          = $xp->query( '//body' )->item( 0 );
	$text          = $body ? trim( preg_replace( '/\s+/u', ' ', (string) $body->textContent ) ) : '';
	$data['words'] = $text ? count( preg_split( '/\s+/u', $text ) ) : 0;
	$data['scripts'] = substr_count( strtolower( $html ), '<script' );

	semrs_tools_rest_cache( $key, $data );
	return rest_ensure_response( $data );
}

/**
 * Robots.txt Tester: fetch the live robots.txt of a site. Matching runs in
 * the browser so visitors can test many paths and crawlers without new requests.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_tools_rest_robots( $req ) {
	$begin = semrs_tools_rest_begin( $req, 'robots-txt-tester', 'semrs_rt_' );
	if ( ! is_array( $begin ) ) {
		return $begin;
	}
	list( $url ) = $begin;
	$p     = wp_parse_url( $url );
	$robot = strtolower( $p['scheme'] ) . '://' . $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' ) . '/robots.txt';
	$key   = 'semrs_rt_' . md5( $robot );
	$c     = get_transient( $key );
	if ( false !== $c ) {
		return rest_ensure_response( $c );
	}
	$r     = semrs_tools_follow( $robot, true, 512 * KB_IN_BYTES );
	$final = end( $r['hops'] );
	$res   = $r['response'];
	$data  = array(
		'url'     => $url,
		'robots'  => $robot,
		'final'   => $final['url'] ?? $robot,
		'status'  => (int) ( $final['status'] ?? 0 ),
		'note'    => (string) ( $final['note'] ?? '' ),
		'text'    => ( $res && ! is_wp_error( $res ) && 200 === (int) ( $final['status'] ?? 0 ) ) ? (string) wp_remote_retrieve_body( $res ) : '',
		'checked' => gmdate( 'c' ),
	);
	semrs_tools_rest_cache( $key, $data );
	return rest_ensure_response( $data );
}

/**
 * Pro usage report: count one action for one tool today (30 days kept).
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function semrs_tools_rest_usage( $req ) {
	$tool = sanitize_key( (string) $req->get_param( 'tool' ) );
	$ev   = sanitize_key( (string) $req->get_param( 'ev' ) );
	if ( ! semrs_tools_is_pro() || '1' !== semrs_tools_options()['usage'] || ! isset( semrs_tools_list()[ $tool ] ) || ! in_array( $ev, array( 'use', 'run', 'copy', 'download', 'cta' ), true ) || ! semrs_tools_rate_ok( 'usage', 300 ) ) {
		return new WP_REST_Response( null, 204 );
	}
	$day  = gmdate( 'Y-m-d' );
	$data = (array) get_option( 'semrs_tools_usage', array() );
	if ( empty( $data[ $day ][ $tool ][ $ev ] ) ) {
		$data[ $day ][ $tool ][ $ev ] = 0;
	}
	$data[ $day ][ $tool ][ $ev ]++;
	ksort( $data );
	$data = array_slice( $data, -30, null, true );
	update_option( 'semrs_tools_usage', $data, false );
	return new WP_REST_Response( null, 204 );
}
