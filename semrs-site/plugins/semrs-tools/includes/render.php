<?php
/**
 * Shortcodes and asset loading.
 *
 * [semrs_tool id="serp-snippet-preview"]   → one tool
 * [semrs_tools]                            → directory of all tools by category
 * [semrs_tools category="technical-seo"]   → one category's tools
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register assets (enqueued only where a tool is used).
 */
function semrs_tools_register_assets() {
	wp_register_style( 'semrs-tools', SEMRS_TOOLS_URL . 'assets/tools.css', array(), SEMRS_TOOLS_VERSION );
	wp_register_script( 'semrs-tools-core', SEMRS_TOOLS_URL . 'assets/js/core.js', array(), SEMRS_TOOLS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script(
		'semrs-tools-core',
		'window.SEMRS_TOOLS=' . wp_json_encode(
			array(
				'rest'  => esc_url_raw( rest_url( 'semrs-tools/v1/' ) ),
				'home'  => esc_url_raw( home_url( '/' ) ),
				'site'  => get_bloginfo( 'name' ),
			)
		) . ';',
		'before'
	);
	foreach ( array_keys( semrs_tools_list() ) as $id ) {
		wp_register_script( 'semrs-tool-' . $id, SEMRS_TOOLS_URL . 'assets/js/tools/' . $id . '.js', array( 'semrs-tools-core' ), SEMRS_TOOLS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'semrs_tools_register_assets', 5 );

/**
 * Load the stylesheet in <head> on pages that contain a tool shortcode
 * (avoids a late style flash).
 */
function semrs_tools_enqueue_head_style() {
	if ( is_singular() ) {
		$content = (string) get_post_field( 'post_content', get_queried_object_id() );
		if ( has_shortcode( $content, 'semrs_tool' ) || has_shortcode( $content, 'semrs_tools' ) ) {
			wp_enqueue_style( 'semrs-tools' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'semrs_tools_enqueue_head_style', 20 );

/**
 * [semrs_tool id="…"]
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_tools_tool_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'id' => '' ), $atts, 'semrs_tool' );
	$id    = sanitize_key( $atts['id'] );
	$tools = semrs_tools_list();
	if ( ! isset( $tools[ $id ] ) ) {
		return current_user_can( 'edit_posts' ) ? '<p><strong>SEMRS Tools:</strong> unknown tool id "' . esc_html( $id ) . '".</p>' : '';
	}
	wp_enqueue_style( 'semrs-tools' );
	wp_enqueue_script( 'semrs-tool-' . $id );

	$cats = semrs_tools_categories();
	$cat  = isset( $cats[ $tools[ $id ][1] ] ) ? $cats[ $tools[ $id ][1] ] : null;

	return sprintf(
		'<div class="semrs-tool-app" data-semrs-tool="%1$s" data-service-label="%2$s" data-service-url="%3$s"><noscript><p>%4$s</p></noscript><p class="semrs-tool-loading">%5$s</p></div>',
		esc_attr( $id ),
		esc_attr( $cat ? $cat[2] : '' ),
		esc_url( $cat ? home_url( $cat[3] ) : '' ),
		esc_html__( 'This free tool needs JavaScript. Please enable it in your browser.', 'semrs-tools' ),
		esc_html__( 'Loading tool…', 'semrs-tools' )
	);
}
add_shortcode( 'semrs_tool', 'semrs_tools_tool_shortcode' );

/**
 * [semrs_tools category=""] directory.
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_tools_directory_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'category' => '', 'exclude' => '' ), $atts, 'semrs_tools' ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- a shortcode attribute (tool slugs), not a query.
	wp_enqueue_style( 'semrs-tools' );
	$cats    = semrs_tools_categories();
	$tools   = semrs_tools_list();
	$only    = sanitize_key( $atts['category'] );
	$exclude = array_map( 'sanitize_key', array_filter( explode( ',', $atts['exclude'] ) ) );
	$out     = '<div class="semrs-tools-dir">';

	foreach ( $cats as $slug => $cat ) {
		if ( $only && $only !== $slug ) {
			continue;
		}
		$cards = '';
		foreach ( $tools as $id => $t ) {
			if ( $t[1] !== $slug || in_array( $id, $exclude, true ) ) {
				continue;
			}
			$cards .= sprintf(
				'<a class="semrs-tools-card" href="%1$s"><span class="semrs-tools-card__title">%2$s</span><span class="semrs-tools-card__desc">%3$s</span><span class="semrs-tools-card__go">%4$s</span></a>',
				esc_url( semrs_tools_url( $id ) ),
				esc_html( $t[0] ),
				esc_html( $t[2] ),
				esc_html__( 'Use free tool →', 'semrs-tools' )
			);
		}
		if ( ! $cards ) {
			continue;
		}
		if ( ! $only ) {
			$out .= sprintf(
				'<section class="semrs-tools-dir__cat" id="%1$s"><h2><a href="%2$s">%3$s</a></h2><p>%4$s</p>',
				esc_attr( $slug ),
				esc_url( home_url( '/tools/' . $slug . '/' ) ),
				esc_html( $cat[0] ),
				esc_html( $cat[1] )
			);
		}
		$out .= '<div class="semrs-tools-grid">' . $cards . '</div>';
		if ( ! $only ) {
			$out .= '</section>';
		}
	}
	return $out . '</div>';
}
add_shortcode( 'semrs_tools', 'semrs_tools_directory_shortcode' );

/**
 * Tool pages include [semrs_ad slot="tool"]. Without SEMRS Core Ads that
 * shortcode would print as text, so register a silent fallback.
 */
function semrs_tools_ad_fallback() {
	if ( ! shortcode_exists( 'semrs_ad' ) ) {
		add_shortcode( 'semrs_ad', '__return_empty_string' );
	}
}
add_action( 'init', 'semrs_tools_ad_fallback', 99 );
