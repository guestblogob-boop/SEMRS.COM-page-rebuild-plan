<?php
/**
 * LiteSpeed server cache, built into SEMRS Speed.
 *
 * On LiteSpeed web servers (Hostinger and many other hosts) the server itself can keep finished
 * pages and send them without starting PHP, which is faster than any PHP page cache. This module
 * talks to that server cache directly, so the LiteSpeed Cache plugin is not needed:
 * - pages that SEMRS would cache get "X-LiteSpeed-Cache-Control: public,max-age=…" and a site tag;
 *   everything else (logged-in users, carts, forms with query strings, admin, searches, 404s) gets
 *   "no-cache";
 * - logged-in users get the "_lscache_vary" cookie, so they are never shown a visitor's copy;
 * - whenever SEMRS clears its cache (content, menus, settings, themes or plugins change), the
 *   server cache is purged with "X-LiteSpeed-Purge"; if that response never reaches the browser
 *   (for example a CLI cron run), the purge is sent with the next uncached response instead;
 * - the server rules (SEMRS → Speed → Server) add "CacheLookup on" inside <IfModule LiteSpeed>.
 * It stays off while the LiteSpeed Cache plugin is active (that plugin does the same job), when the
 * SEMRS page cache setting is off, or when SEMRS_CACHE_OFF is defined. While it is on, the SEMRS
 * PHP page cache steps aside so pages are never cached twice.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is this request served by a LiteSpeed web server with its cache module?
 *
 * @return bool
 */
function semrs_litespeed_server() {
	$soft = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) : '';
	return false !== strpos( $soft, 'litespeed' ) || ! empty( $_SERVER['X-LSCACHE'] ) || ! empty( $_SERVER['HTTP_X_LSCACHE'] ) || ( defined( 'SEMRS_LITESPEED' ) && SEMRS_LITESPEED );
}

/**
 * Should SEMRS drive the LiteSpeed server cache on this request?
 *
 * @return bool
 */
function semrs_litespeed_active() {
	static $on = null;
	if ( null === $on ) {
		$on = semrs_litespeed_server() && ! defined( 'LSCWP_V' ) && ! empty( semrs_speed_settings()['cache'] ) && ! ( defined( 'SEMRS_CACHE_OFF' ) && SEMRS_CACHE_OFF );
	}
	return $on;
}

/**
 * Page lifetime in the server cache, from SEMRS → Speed → Page cache (1–10 hours).
 *
 * @return int Seconds.
 */
function semrs_litespeed_ttl() {
	return max( 1, min( 10, (int) semrs_speed_settings()['cache_ttl'] ) ) * HOUR_IN_SECONDS;
}

/**
 * Tag shared by every page of this site (purged as one).
 *
 * @return string
 */
function semrs_litespeed_tag() {
	return 'semrs_' . get_current_blog_id();
}

/**
 * Send a purge for the whole site cache with this response (or queue it for the next one).
 */
function semrs_litespeed_purge() {
	if ( ! semrs_litespeed_server() || defined( 'LSCWP_V' ) ) {
		return;
	}
	if ( ! headers_sent() && ! ( defined( 'WP_CLI' ) && WP_CLI ) && 'cli' !== PHP_SAPI ) {
		header( 'X-LiteSpeed-Purge: ' . semrs_litespeed_tag(), false );
		delete_option( 'semrs_ls_cache_purge' );
	} else {
		update_option( 'semrs_ls_cache_purge', time(), true ); // Sent with the next uncached response.
	}
}
add_action( 'semrs_cache_purged', 'semrs_litespeed_purge' );

/**
 * Cache warm-up (like the LiteSpeed Cache crawler): after a purge, the next cron run requests the
 * home page and the newest posts and pages, so the first real visitor gets a cached page.
 */
function semrs_litespeed_warm() {
	$urls = array( home_url( '/' ) );
	foreach ( get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'publish', 'posts_per_page' => 20, 'orderby' => 'modified', 'fields' => 'ids', 'no_found_rows' => true ) ) as $id ) {
		$urls[] = get_permalink( $id );
	}
	foreach ( array_unique( array_filter( $urls ) ) as $u ) {
		wp_remote_get( $u, array( 'blocking' => false, 'timeout' => 1, 'redirection' => 0, 'user-agent' => 'SEMRS cache warm-up', 'cookies' => array() ) );
	}
}
add_action(
	'semrs_cache_purged',
	function () {
		if ( semrs_litespeed_server() && ! defined( 'LSCWP_V' ) ) {
			update_option( 'semrs_ls_cache_warm', time(), false );
		}
	}
);
add_action(
	'wp_loaded',
	function () {
		$at = (int) get_option( 'semrs_ls_cache_warm', 0 );
		// Warm on a cron run, a little after the purge (so the purge has reached the server).
		if ( $at && wp_doing_cron() && time() - $at >= 20 && semrs_litespeed_active() ) {
			delete_option( 'semrs_ls_cache_warm' );
			semrs_litespeed_warm();
		}
	},
	50
);

// Cache headers for front-end pages.
add_action(
	'template_redirect',
	function () {
		if ( ! semrs_litespeed_active() || headers_sent() ) {
			return;
		}
		$ok = '' !== semrs_cache_request_file() && ! is_user_logged_in() && ! is_404() && ! is_search() && ! is_feed() && ! is_preview() && ! is_customize_preview() && ! is_robots() && ! is_trackback() && ! is_embed()
			&& ! ( is_singular() && post_password_required() ) && ! ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );
		if ( $ok ) {
			header( 'X-LiteSpeed-Cache-Control: public,max-age=' . semrs_litespeed_ttl() );
			header( 'X-LiteSpeed-Tag: ' . semrs_litespeed_tag() );
		} else {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		}
	},
	0
);

// Never cache admin, AJAX, REST, cron or login responses; send any queued purge with them.
add_action(
	'init',
	function () {
		if ( ! semrs_litespeed_server() || defined( 'LSCWP_V' ) || headers_sent() ) {
			return;
		}
		$front = ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		if ( ! $front || is_user_logged_in() ) {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		}
		// Users who logged in before this feature existed get the vary cookie now.
		if ( is_user_logged_in() && empty( $_COOKIE['_lscache_vary'] ) ) {
			setcookie( '_lscache_vary', 'semrs_logged_in', array( 'expires' => time() + 2 * DAY_IN_SECONDS, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		}
		if ( get_option( 'semrs_ls_cache_purge' ) ) {
			semrs_litespeed_purge();
		}
	},
	1
);

// Logged-in users get their own cache key, so they never see a visitor's copy (and visitors never see theirs).
add_action(
	'set_logged_in_cookie',
	function ( $cookie, $expire ) {
		if ( semrs_litespeed_server() && ! defined( 'LSCWP_V' ) && ! headers_sent() ) {
			setcookie( '_lscache_vary', 'semrs_logged_in', array( 'expires' => (int) $expire, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		}
	},
	10,
	2
);
add_action(
	'clear_auth_cookie',
	function () {
		if ( semrs_litespeed_server() && ! defined( 'LSCWP_V' ) && ! headers_sent() ) {
			setcookie( '_lscache_vary', '', array( 'expires' => time() - YEAR_IN_SECONDS, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		}
	}
);

// "CacheLookup on" in the SEMRS Speed server rules (harmless on Apache: it sits inside <IfModule LiteSpeed>).
add_filter(
	'semrs_server_rules',
	function ( $rules ) {
		$rules[] = '<IfModule LiteSpeed>';
		$rules[] = '	RewriteEngine on';
		$rules[] = '	CacheLookup on';
		$rules[] = '</IfModule>';
		return $rules;
	}
);
