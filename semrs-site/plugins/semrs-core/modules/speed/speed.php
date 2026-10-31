<?php
/**
 * Speed Optimization (free): bloat removal, Heartbeat and revision limits,
 * YouTube click-to-play, resource hints, key-image preload, deferred scripts,
 * per-block CSS, inlined or non-blocking CSS, hover prefetch, a page cache,
 * database cleanup and a speed check.
 * Every option is a switch under SEMRS → Speed. Safe options are on by
 * default; options that can affect other plugins are off by default.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Options: key => [label, default, description].
 *
 * @return array
 */
function semrs_speed_fields() {
	return array(
		'emojis'       => array( __( 'Remove emoji scripts', 'semrs-core' ), true, __( 'Browsers show emoji natively; saves a script and styles on every page.', 'semrs-core' ) ),
		'embeds'       => array( __( 'Remove oEmbed discovery and wp-embed script', 'semrs-core' ), true, __( 'Your own embeds of YouTube etc. keep working.', 'semrs-core' ) ),
		'dashicons'    => array( __( 'Dashicons only for logged-in users', 'semrs-core' ), true, __( 'Visitors never need the admin icon font.', 'semrs-core' ) ),
		'migrate'      => array( __( 'Remove jQuery Migrate on the front end', 'semrs-core' ), true, __( 'Turn off if an old plugin breaks.', 'semrs-core' ) ),
		'head'         => array( __( 'Clean the <head> (RSD, WLW, generator, shortlink)', 'semrs-core' ), true, '' ),
		'heartbeat'    => array( __( 'Slow the Heartbeat API to every 60 seconds', 'semrs-core' ), true, __( 'Lowers server load from open admin tabs. Autosave still works.', 'semrs-core' ) ),
		'revisions'    => array( __( 'Keep at most 10 revisions per post', 'semrs-core' ), true, __( 'Keeps the database small.', 'semrs-core' ) ),
		'youtube'      => array( __( 'YouTube click-to-play (lightweight preview)', 'semrs-core' ), true, __( 'Shows the thumbnail and loads the video player only when clicked. One of the biggest speed wins on pages with videos.', 'semrs-core' ) ),
		'preload_img'  => array( __( 'Preload the featured image on posts and pages', 'semrs-core' ), true, __( 'Helps Largest Contentful Paint (LCP).', 'semrs-core' ) ),
		'hints'        => array( __( 'Preconnect to Google ad and analytics servers when used', 'semrs-core' ), true, __( 'Adds preconnect hints only when SEMRS Ads or Google tags are active.', 'semrs-core' ) ),
		'block_css'    => array( __( 'Load block styles only for blocks on the page', 'semrs-core' ), true, __( 'Instead of one big stylesheet for every block, each page gets only the CSS of the blocks it uses.', 'semrs-core' ) ),
		'inline_css'   => array( __( 'Inline small stylesheets (under 10 KB)', 'semrs-core' ), false, __( 'Puts small CSS files straight into the page, saving render-blocking requests. Best together with the page cache.', 'semrs-core' ) ),
		'async_css'    => array( __( 'Load chosen stylesheets without blocking the page', 'semrs-core' ), false, __( 'For CSS that is not needed for the top of the page (e.g. a slider or form plugin). List the style handles below.', 'semrs-core' ) ),
		'prefetch'     => array( __( 'Prefetch pages when a visitor hovers over a link', 'semrs-core' ), false, __( 'The next page starts loading about 200 ms before the click, so it opens almost instantly. Uses the browser\'s Speculation Rules; admin, login, cart and links with ? are skipped.', 'semrs-core' ) ),
		'xmlrpc'       => array( __( 'Disable XML-RPC', 'semrs-core' ), false, __( 'Blocks a common attack target. Leave off if you use Jetpack or the WordPress mobile app.', 'semrs-core' ) ),
		'defer'        => array( __( 'Defer front-end scripts', 'semrs-core' ), false, __( 'Scripts load after the page is drawn. Test your site after switching on; add any script handle that breaks to the exclusions.', 'semrs-core' ) ),
	);
}

/**
 * Settings merged with defaults.
 *
 * @return array
 */
function semrs_speed_settings() {
	static $s = null;
	if ( null === $s ) {
		// Plain defaults (no labels): this runs at plugins_loaded, before translations may load.
		$defaults = array(
			'emojis'        => true,
			'embeds'        => true,
			'dashicons'     => true,
			'migrate'       => true,
			'head'          => true,
			'heartbeat'     => true,
			'revisions'     => true,
			'youtube'       => true,
			'preload_img'   => true,
			'hints'         => true,
			'xmlrpc'        => false,
			'defer'         => false,
			'defer_exclude' => "jquery\njquery-core",
			'block_css'     => true,
			'inline_css'    => false,
			'async_css'     => false,
			'async_handles' => '',
			'prefetch'      => false,
			'cache'         => false,
			'cache_ttl'     => 10,
			'cache_exclude' => '',
		);
		$saved = get_option( 'semrs_speed', array() );
		$s     = array_merge( $defaults, is_array( $saved ) ? $saved : array() );
	}
	return $s;
}

/**
 * Register front-end optimizations.
 */
function semrs_speed_boot() {
	$s = semrs_speed_settings();

	if ( $s['emojis'] ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' ); // Also drops the emoji DNS-prefetch hint.
	}
	if ( $s['embeds'] ) {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_action( 'wp_footer', function () { wp_dequeue_script( 'wp-embed' ); } );
	}
	if ( $s['dashicons'] ) {
		add_action( 'wp_enqueue_scripts', function () { if ( ! is_user_logged_in() ) { wp_dequeue_style( 'dashicons' ); } }, 100 );
	}
	if ( $s['migrate'] ) {
		add_action(
			'wp_default_scripts',
			function ( $scripts ) {
				if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
					$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
				}
			}
		);
	}
	if ( $s['head'] ) {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}
	if ( $s['heartbeat'] ) {
		add_filter( 'heartbeat_settings', function ( $set ) { $set['interval'] = 60; return $set; } );
	}
	if ( $s['revisions'] ) {
		add_filter( 'wp_revisions_to_keep', function ( $num ) { return ( $num < 0 || $num > 10 ) ? 10 : $num; } );
	}
	if ( $s['xmlrpc'] ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', function ( $h ) { unset( $h['X-Pingback'] ); return $h; } );
	}
	if ( $s['youtube'] ) {
		add_filter( 'render_block_core/embed', 'semrs_speed_youtube_facade', 10, 2 );
	}
	if ( $s['preload_img'] ) {
		if ( semrs_speed_lcp_first() ) {
			// Main image first: before the ad and analytics tags (priority 2), and only the first
			// image in the content loads eagerly, so lower images don't compete with it.
			add_action( 'wp_head', 'semrs_speed_preload_image', 1 );
			add_filter( 'wp_omit_loading_attr_threshold', function () { return 1; } );
		} else {
			add_action( 'wp_head', 'semrs_speed_preload_image', 2 );
		}
	}
	if ( $s['hints'] ) {
		add_filter( 'wp_resource_hints', 'semrs_speed_hints', 10, 2 );
	}
	if ( $s['defer'] && ! is_admin() ) {
		add_filter( 'script_loader_tag', 'semrs_speed_defer', 10, 3 );
	}
	if ( $s['block_css'] ) {
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
	}
	if ( ( $s['inline_css'] || $s['async_css'] ) && ! is_admin() ) {
		add_filter( 'style_loader_tag', 'semrs_speed_style_tag', 20, 4 );
	}
	if ( $s['prefetch'] ) {
		semrs_speed_prefetch();
	}
}
add_action( 'plugins_loaded', 'semrs_speed_boot', 20 );

/**
 * YouTube embed → thumbnail button; the iframe loads on click.
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function semrs_speed_youtube_facade( $html, $block ) {
	$url = $block['attrs']['url'] ?? '';
	if ( 'youtube' !== ( $block['attrs']['providerNameSlug'] ?? '' ) || ! preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', (string) $url, $m ) ) {
		return $html;
	}
	$id      = $m[1];
	$caption = preg_match( '~<figcaption[^>]*>.*?</figcaption>~s', $html, $c ) ? $c[0] : '';
	static $js = false;
	if ( ! $js ) {
		$js = true;
		add_action(
			'wp_footer',
			function () {
				echo "<style>.semrs-yt{position:relative;aspect-ratio:16/9;background:#000 center/cover no-repeat;border:0;width:100%;cursor:pointer;display:block;padding:0}.semrs-yt::after{content:'';position:absolute;inset:50% auto auto 50%;width:68px;height:48px;transform:translate(-50%,-50%);background:#f00;border-radius:14px;box-shadow:0 0 0 0 transparent}.semrs-yt::before{content:'';position:absolute;inset:50% auto auto 50%;transform:translate(-35%,-50%);border-style:solid;border-width:11px 0 11px 19px;border-color:transparent transparent transparent #fff;z-index:1}.semrs-yt iframe{position:absolute;inset:0;width:100%;height:100%;border:0}</style>";
				echo "<script>document.addEventListener('click',function(e){var b=e.target.closest('.semrs-yt');if(!b||b.querySelector('iframe'))return;var f=document.createElement('iframe');f.src='https://www.youtube-nocookie.com/embed/'+b.dataset.id+'?autoplay=1&rel=0';f.allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share';f.allowFullscreen=true;f.title=b.getAttribute('aria-label');b.appendChild(f);});</script>";
			},
			99
		);
	}
	return sprintf(
		'<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper"><button type="button" class="semrs-yt" data-id="%1$s" aria-label="%2$s" style="background-image:url(https://i.ytimg.com/vi/%1$s/hqdefault.jpg)"></button><noscript><a href="%3$s">%2$s</a></noscript></div>%4$s</figure>',
		esc_attr( $id ),
		esc_attr__( 'Play video', 'semrs-core' ),
		esc_url( $url ),
		$caption // From the saved block.
	);
}

/**
 * "Main image first" mode (on by default): the featured image preload uses the image's own sizes,
 * only the first content image loads eagerly, and unused early connections are dropped.
 * Tested on semrs.com articles (2026-10-31, PageSpeed mobile, 6 runs each): average 92 → 95,
 * lowest 88 → 94. Turn off with add_filter( 'semrs_speed_lcp_first', '__return_false' ).
 *
 * @return bool
 */
function semrs_speed_lcp_first() {
	return (bool) apply_filters( 'semrs_speed_lcp_first', true );
}

/**
 * Preload the featured image on singular pages.
 */
function semrs_speed_preload_image() {
	if ( ! is_singular() || ! has_post_thumbnail() ) {
		return;
	}
	$id     = get_post_thumbnail_id();
	$src    = wp_get_attachment_image_url( $id, 'large' );
	$srcset = wp_get_attachment_image_srcset( $id, 'large' );
	// The same sizes the theme prints on the image, so the browser preloads the file it will show.
	$sizes = semrs_speed_lcp_first() ? (string) wp_get_attachment_image_sizes( $id, 'large' ) : '';
	if ( $src ) {
		printf(
			'<link rel="preload" as="image" href="%s"%s fetchpriority="high">' . "\n",
			esc_url( $src ),
			$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( '' !== $sizes ? $sizes : '(max-width: 1200px) 100vw, 1200px' ) . '"' : ''
		);
	}
}

/**
 * Preconnect hints for Google ads/analytics when those are in use.
 *
 * @param array  $urls Hints.
 * @param string $type Relation type.
 * @return array
 */
function semrs_speed_hints( $urls, $type ) {
	if ( 'preconnect' !== $type ) {
		return $urls;
	}
	// "Main image first" keeps early connections for what loads straight away only: the ad server and
	// Google Tag Manager are reached later (the tag is delayed until interaction), and an unused
	// preconnect takes a connection slot from the main image on slow phones.
	$lean = semrs_speed_lcp_first();
	if ( function_exists( 'semrs_ads_allowed' ) && semrs_ads_allowed() ) {
		$urls[] = array( 'href' => 'https://pagead2.googlesyndication.com', 'crossorigin' );
		if ( ! $lean ) {
			$urls[] = array( 'href' => 'https://googleads.g.doubleclick.net', 'crossorigin' );
		}
	}
	if ( ! $lean && wp_script_is( 'google_gtagjs', 'enqueued' ) ) {
		$urls[] = array( 'href' => 'https://www.googletagmanager.com', 'crossorigin' );
	}
	return $urls;
}

/**
 * Add defer to enqueued scripts that are safe to defer.
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @param string $src    Source.
 * @return string
 */
function semrs_speed_defer( $tag, $handle, $src ) {
	$exclude = array_filter( array_map( 'trim', preg_split( '/\R/', (string) semrs_speed_settings()['defer_exclude'] ) ) );
	if ( ! $src || in_array( $handle, $exclude, true ) || false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) || false !== strpos( $tag, 'type="module"' ) ) {
		return $tag;
	}
	// Scripts with inline code printed after them must run in order: leave them alone.
	if ( wp_scripts()->get_data( $handle, 'after' ) ) {
		return $tag;
	}
	return str_replace( ' src=', ' defer src=', $tag );
}

require __DIR__ . '/css.php';
require __DIR__ . '/cache.php';
if ( is_admin() ) {
	require __DIR__ . '/admin.php';
	require __DIR__ . '/cleanup.php';
	require __DIR__ . '/check.php';
}
