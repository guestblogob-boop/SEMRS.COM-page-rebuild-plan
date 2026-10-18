<?php
/**
 * Front-end weight reduction that is safe on any site.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

// Emoji script/styles (all modern browsers render emoji natively).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );

// Head clutter.
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

/**
 * Load each core block's CSS only when that block is on the page.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * No jQuery Migrate on the front end.
 *
 * @param WP_Scripts $scripts Scripts.
 */
function semrs_pro_no_jquery_migrate( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
}
add_action( 'wp_default_scripts', 'semrs_pro_no_jquery_migrate' );

/**
 * Dashicons only for logged-in users; no classic-themes CSS.
 */
function semrs_pro_trim_styles() {
	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'semrs_pro_trim_styles', 100 );

/**
 * First featured image on singular pages loads with high priority (LCP);
 * other images keep native lazy loading.
 *
 * @param array        $attr       Attributes.
 * @param WP_Post      $attachment Attachment.
 * @param string|int[] $size       Size.
 * @return array
 */
function semrs_pro_lcp_image( $attr, $attachment, $size ) {
	static $done = false;
	if ( ! $done && is_singular() && in_the_loop() && get_post_thumbnail_id() === $attachment->ID ) {
		$attr['fetchpriority'] = 'high';
		$attr['loading']       = 'eager';
		$attr['decoding']      = 'async';
		$done                  = true;
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'semrs_pro_lcp_image', 10, 3 );
