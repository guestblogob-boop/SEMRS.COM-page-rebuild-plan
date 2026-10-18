<?php
/**
 * Page cache (free, off by default): finished HTML pages are saved to
 * wp-content/cache/semrs/ and served to visitors before WordPress runs the
 * query and the theme. Logged-in users, carts, comment authors, searches,
 * 404s, previews, password-protected pages and URLs with query strings are
 * never cached. Separate copies for phones and computers keep plugins that
 * use wp_is_mobile() correct. The whole cache clears when content, menus,
 * widgets, the Customizer or SEMRS settings change.
 *
 * Pauses automatically when another page-cache plugin is active.
 *
 * Other code can change the final HTML through the `semrs_speed_html`
 * filter (SEMRS Pro uses it for delayed scripts and the CDN).
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache folder.
 *
 * @return string
 */
function semrs_cache_dir() {
	return trailingslashit( WP_CONTENT_DIR ) . 'cache/semrs/';
}

/**
 * Name of another active page cache, or ''.
 *
 * @return string
 */
function semrs_cache_conflict() {
	$known = array(
		'WP_ROCKET_VERSION'      => 'WP Rocket',
		'LSCWP_V'                => 'LiteSpeed Cache',
		'W3TC'                   => 'W3 Total Cache',
		'WPFC_MAIN_PATH'         => 'WP Fastest Cache',
		'WPCACHEHOME'            => 'WP Super Cache',
		'BREEZE_VERSION'         => 'Breeze',
		'WPO_VERSION'            => 'WP-Optimize',
		'CACHE_ENABLER_VERSION'  => 'Cache Enabler',
		'COMET_CACHE_PLUGIN_FILE' => 'Comet Cache',
		'HUMMINGBIRD_VERSION'    => 'Hummingbird',
		'SWIS_PLUGIN_VERSION'    => 'SWIS Performance',
	);
	foreach ( $known as $const => $name ) {
		if ( defined( $const ) ) {
			return $name;
		}
	}
	if ( class_exists( 'SiteGround_Optimizer\Loader\Loader' ) ) {
		return 'SiteGround Optimizer';
	}
	if ( defined( 'WP_CACHE' ) && WP_CACHE && file_exists( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ) ) {
		return __( 'a page-cache drop-in (advanced-cache.php)', 'semrs-core' );
	}
	return '';
}

/**
 * Is the page cache switched on and free to run?
 *
 * @return bool
 */
function semrs_cache_enabled() {
	return ! empty( semrs_speed_settings()['cache'] ) && '' === semrs_cache_conflict() && ! ( defined( 'SEMRS_CACHE_OFF' ) && SEMRS_CACHE_OFF );
}

/**
 * Cache file for the current request, or '' when this request must not be cached.
 *
 * @return string
 */
function semrs_cache_request_file() {
	static $file = null;
	if ( null !== $file ) {
		return $file;
	}
	$file   = '';
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return $file;
	}
	foreach ( array_keys( $_COOKIE ) as $name ) {
		if ( preg_match( '/^(wordpress_logged_in_|wp-postpass_|comment_author_|woocommerce_items_in_cart|woocommerce_cart_hash|wp_woocommerce_session_|edd_items_in_cart|semrs_nocache)/', (string) $name ) ) {
			return $file;
		}
	}
	$home = wp_parse_url( home_url( '/' ) );
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
	$want = strtolower( $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) );
	if ( $host !== $want ) {
		return $file; // Only the real site address (no host-header tricks).
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only hashed and matched.
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$qs   = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	if ( '' !== $qs ) {
		parse_str( $qs, $args );
		foreach ( array_keys( $args ) as $k ) {
			if ( preg_match( '/^(utm_[a-z]+|gclid|gbraid|wbraid|fbclid|msclkid|mc_cid|mc_eid|_ga|_gl|ref)$/', (string) $k ) ) {
				unset( $args[ $k ] );
			}
		}
		if ( $args ) {
			return $file; // Real query strings (searches, filters, forms) are never cached.
		}
	}
	if ( '' === $path || preg_match( '#(/wp-admin|/wp-login|/wp-json|/xmlrpc|\.php$|/feed/?$|sitemap|robots\.txt|llms\.txt|/cart/|/checkout/|/my-account/|\.xml$|\.txt$)#i', $path ) ) {
		return $file;
	}
	foreach ( array_filter( array_map( 'trim', preg_split( '/\R/', (string) semrs_speed_settings()['cache_exclude'] ) ) ) as $rule ) {
		$rule = '/' . ltrim( $rule, '/' );
		if ( '*' === substr( $rule, -1 ) ? 0 === strpos( $path, rtrim( $rule, '*' ) ) : untrailingslashit( $path ) === untrailingslashit( $rule ) ) {
			return $file;
		}
	}
	$file = semrs_cache_dir() . preg_replace( '/[^a-z0-9.\-]/', '_', $host ) . '/' . md5( $path ) . ( wp_is_mobile() ? '-m' : '' ) . '.html';
	return $file;
}

/**
 * Serve a fresh cached copy and stop, if there is one.
 */
function semrs_cache_serve() {
	if ( ! semrs_cache_enabled() ) {
		return;
	}
	$file = semrs_cache_request_file();
	if ( '' === $file || ! file_exists( $file ) ) {
		return;
	}
	// One handle: the copy can be cleared by another request at any moment.
	$fh = @fopen( $file, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- a cleared copy is a normal miss.
	if ( ! $fh ) {
		return;
	}
	$stat = fstat( $fh );
	$ttl  = max( 1, min( 10, (int) semrs_speed_settings()['cache_ttl'] ) ) * HOUR_IN_SECONDS;
	if ( ! $stat || $stat['size'] < 100 || $stat['mtime'] + $ttl < time() ) {
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return;
	}
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset', 'UTF-8' ) );
	header( 'X-SEMRS-Cache: HIT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $stat['mtime'] ) . ' GMT' );
	if ( 'HEAD' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET' ) ) {
		fpassthru( $fh );
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
semrs_cache_serve(); // Runs while SEMRS Core loads its modules (plugins_loaded), before the query and theme.

/**
 * May the page being rendered be saved?
 *
 * @return bool
 */
function semrs_cache_can_store() {
	return semrs_cache_enabled() && '' !== semrs_cache_request_file() && ! is_user_logged_in() && ! is_404() && ! is_search() && ! is_feed() && ! is_preview() && ! is_customize_preview() && ! is_robots() && ! is_trackback() && ! is_embed()
		&& ! ( is_singular() && post_password_required() ) && ! ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );
}

/**
 * Start one output buffer for the page: SEMRS filters, then the cache write.
 */
function semrs_cache_start_buffer() {
	$store = semrs_cache_can_store();
	if ( ! $store && ! has_filter( 'semrs_speed_html' ) ) {
		return;
	}
	if ( $store && ! headers_sent() ) {
		header( 'X-SEMRS-Cache: MISS' );
	}
	ob_start(
		function ( $html ) use ( $store ) {
			if ( false === stripos( $html, '</html>' ) ) {
				return $html;
			}
			$html = (string) apply_filters( 'semrs_speed_html', $html );
			if ( ! $store || 200 !== http_response_code() || ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) ) {
				return $html;
			}
			foreach ( headers_list() as $h ) {
				if ( 0 === stripos( $h, 'content-type:' ) && false === stripos( $h, 'text/html' ) ) {
					return $html;
				}
			}
			$file = semrs_cache_request_file();
			if ( wp_mkdir_p( dirname( $file ) ) ) {
				$tmp = $file . '.' . wp_rand() . '.tmp';
				// phpcs:ignore WordPress.WP.AlternativeFunctions -- writing the cache file.
				if ( false !== file_put_contents( $tmp, $html . "\n<!-- SEMRS page cache: " . gmdate( 'c' ) . ' -->', LOCK_EX ) ) {
					rename( $tmp, $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				}
			}
			return $html;
		}
	);
}
add_action( 'template_redirect', 'semrs_cache_start_buffer', 1000 );

/**
 * Delete every cached page.
 *
 * @return int Files deleted.
 */
function semrs_cache_purge_all() {
	$dir = semrs_cache_dir();
	$n   = 0;
	foreach ( (array) glob( $dir . '*', GLOB_ONLYDIR ) as $host ) {
		foreach ( (array) glob( $host . '/*' ) as $f ) {
			if ( is_file( $f ) && preg_match( '/\.(html|tmp)$/', $f ) && wp_delete_file_from_directory( $f, $dir ) ) {
				$n++;
			}
		}
	}
	do_action( 'semrs_cache_purged' );
	return $n;
}

/**
 * Cache size: [files, bytes].
 *
 * @return int[]
 */
function semrs_cache_stats() {
	$files = 0;
	$bytes = 0;
	foreach ( (array) glob( semrs_cache_dir() . '*/*.html' ) as $f ) {
		$files++;
		$bytes += (int) @filesize( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- may be cleared meanwhile.
	}
	return array( $files, $bytes );
}

/**
 * Purge when something visitors see changes.
 */
function semrs_cache_purge_on_change() {
	static $done = false;
	if ( ! $done ) {
		$done = true;
		semrs_cache_purge_all();
	}
}
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( ( 'publish' === $new || 'publish' === $old ) && ! wp_is_post_revision( $post ) && is_post_type_viewable( $post->post_type ) ) {
			semrs_cache_purge_on_change();
		}
	},
	10,
	3
);
add_action(
	'save_post',
	function ( $id, $post ) {
		if ( 'publish' === $post->post_status && ! wp_is_post_revision( $id ) ) {
			semrs_cache_purge_on_change(); // Updates to already-published posts.
		}
	},
	99,
	2
);
foreach ( array( 'deleted_post', 'comment_post', 'wp_set_comment_status', 'edit_comment', 'switch_theme', 'customize_save_after', 'wp_update_nav_menu', 'activated_plugin', 'deactivated_plugin', 'upgrader_process_complete', 'edited_term', 'delete_term' ) as $semrs_hook ) {
	add_action( $semrs_hook, 'semrs_cache_purge_on_change' );
}
unset( $semrs_hook );
add_action(
	'updated_option',
	function ( $name ) {
		if ( in_array( $name, array( 'blogname', 'blogdescription', 'page_on_front', 'show_on_front', 'page_for_posts', 'permalink_structure', 'site_icon', 'sidebars_widgets', 'posts_per_page' ), true )
			|| 0 === strpos( $name, 'theme_mods_' ) || 0 === strpos( $name, 'widget_' )
			|| ( 0 === strpos( $name, 'semrs_' ) && ! preg_match( '/audit|imports|cache|check|_score/', $name ) ) ) {
			semrs_cache_purge_on_change();
		}
	}
);

// Daily: remove expired copies.
add_action(
	'semrs_cache_gc',
	function () {
		$ttl = max( 1, min( 10, (int) semrs_speed_settings()['cache_ttl'] ) ) * HOUR_IN_SECONDS;
		foreach ( (array) glob( semrs_cache_dir() . '*/*' ) as $f ) {
			$m = @filemtime( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- may be cleared meanwhile.
			if ( $m && preg_match( '/\.(html|tmp)$/', $f ) && $m + $ttl < time() ) {
				wp_delete_file_from_directory( $f, semrs_cache_dir() );
			}
		}
	}
);
add_action(
	'init',
	function () {
		$on = semrs_cache_enabled();
		$at = wp_next_scheduled( 'semrs_cache_gc' );
		if ( $on && ! $at ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'semrs_cache_gc' );
		} elseif ( ! $on && $at ) {
			wp_unschedule_event( $at, 'semrs_cache_gc' );
		}
	}
);

// "Clear cache" in the admin bar.
add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! semrs_cache_enabled() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'semrs-cache-clear',
				'title' => __( 'Clear cache', 'semrs-core' ),
				'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=semrs_cache_clear' ), 'semrs_cache_clear' ),
			)
		);
	},
	100
);
add_action(
	'admin_post_semrs_cache_clear',
	function () {
		check_admin_referer( 'semrs_cache_clear' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'semrs-core' ) );
		}
		$n    = semrs_cache_purge_all();
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=semrs-speed&tab=cache' );
		wp_safe_redirect( add_query_arg( 'semrs_cleared', $n, $back ) );
		exit;
	}
);
add_action(
	'admin_notices',
	function () {
		if ( isset( $_GET['semrs_cleared'] ) && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only.
			/* translators: %d: files */
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( 'Page cache cleared (%d pages).', 'semrs-core' ), absint( $_GET['semrs_cleared'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
	}
);
