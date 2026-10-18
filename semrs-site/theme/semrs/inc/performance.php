<?php
/**
 * Front-end weight reduction that is safe on any site.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

// Script and head clean-up (emoji, jQuery Migrate, Dashicons, head links) is a
// plugin feature: SEMRS Core → Speed. The theme only does what themes should.

/**
 * Load each core block's CSS only when that block is on the page.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * First featured image on singular pages loads with high priority (LCP);
 * other images keep native lazy loading.
 *
 * @param array        $attr       Attributes.
 * @param WP_Post      $attachment Attachment.
 * @param string|int[] $size       Size.
 * @return array
 */
function semrs_theme_lcp_image( $attr, $attachment, $size ) {
	static $done = false;
	if ( ! $done && is_singular() && in_the_loop() && get_post_thumbnail_id() === $attachment->ID ) {
		$attr['fetchpriority'] = 'high';
		$attr['loading']       = 'eager';
		$attr['decoding']      = 'async';
		$done                  = true;
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'semrs_theme_lcp_image', 10, 3 );
