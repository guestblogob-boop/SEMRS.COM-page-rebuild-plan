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
				'rest'     => esc_url_raw( rest_url( 'semrs-tools/v1/' ) ),
				'home'     => esc_url_raw( home_url( '/' ) ),
				'site'     => get_bloginfo( 'name' ),
				'remember' => '1' === semrs_tools_options()['remember'],
				'track'    => '1' === semrs_tools_options()['track'],
				'bulk'     => semrs_tools_bulk_on() ? 25 : 0,
				'usage'    => semrs_tools_is_pro() && '1' === semrs_tools_options()['usage'],
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
		// Tools on this page: their scripts are preloaded from <head> so the tool appears sooner.
		if ( preg_match_all( '/\[semrs_tool\s+id=["\']?([a-z0-9-]+)/', $content, $m ) ) {
			$GLOBALS['semrs_tools_preload'] = array_values( array_filter( array_unique( $m[1] ), 'semrs_tools_enabled' ) );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'semrs_tools_enqueue_head_style', 20 );

/**
 * Preload the core and tool scripts (same URLs WordPress prints, so they are
 * fetched once). Scripts stay deferred; they just start downloading early.
 *
 * @param array $res Preload resources.
 * @return array
 */
function semrs_tools_preload( $res ) {
	$ids   = $GLOBALS['semrs_tools_preload'] ?? array();
	$tools = semrs_tools_list();
	if ( ! $ids ) {
		return $res;
	}
	$handles = array_merge( array( 'semrs-tools-core' ), array_map( function ( $id ) { return 'semrs-tool-' . $id; }, array_slice( array_intersect( $ids, array_keys( $tools ) ), 0, 3 ) ) );
	foreach ( $handles as $h ) {
		$reg = wp_scripts()->registered[ $h ] ?? null;
		if ( $reg && $reg->src ) {
			$res[] = array(
				'href' => add_query_arg( 'ver', $reg->ver ? $reg->ver : get_bloginfo( 'version' ), $reg->src ),
				'as'   => 'script',
			);
		}
	}
	return $res;
}
add_filter( 'wp_preload_resources', 'semrs_tools_preload' );

/**
 * Height each tool takes once it has loaded (px, [phone, desktop]), measured
 * in its default state. The box reserves this space before the script runs,
 * so nothing below it moves (no layout shift, CLS 0).
 *
 * @return array<string, int[]>
 */
function semrs_tools_heights() {
	return apply_filters(
		'semrs_tools_heights',
		array(
			'serp-snippet-preview'            => array( 1390, 700 ),
			'meta-description-checker'        => array( 720, 640 ),
			'robots-txt-generator'            => array( 2320, 1340 ),
			'redirect-checker'                => array( 620, 640 ),
			'schema-markup-generator'         => array( 1570, 1100 ),
			'local-business-schema-generator' => array( 4100, 2830 ),
			'word-counter'                    => array( 1160, 670 ),
			'keyword-ideas-generator'         => array( 590, 460 ),
			'google-review-link-generator'    => array( 530, 430 ),
			'llms-txt-generator'              => array( 2390, 1810 ),
			'utm-builder'                     => array( 1540, 950 ),
			'image-compressor'                => array( 740, 490 ),
			'seo-roi-calculator'              => array( 2240, 1170 ),
			'on-page-seo-checker'             => array( 740, 730 ),
			'open-graph-generator'            => array( 2500, 2490 ),
			'robots-txt-tester'               => array( 870, 890 ),
			'hreflang-generator'              => array( 1220, 930 ),
			'redirect-rules-generator'        => array( 1070, 1100 ),
			'readability-checker'             => array( 700, 1230 ),
			'roas-calculator'                 => array( 1670, 730 ),
			'color-contrast-checker'          => array( 1300, 830 ),
		)
	);
}

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
	if ( ! semrs_tools_enabled( $id ) ) {
		return current_user_can( 'edit_posts' ) ? '<p><strong>SEMRS Tools:</strong> "' . esc_html( $tools[ $id ][0] ) . '" is switched off in the Free Tools settings. Visitors see nothing here.</p>' : '';
	}
	wp_enqueue_style( 'semrs-tools' );
	wp_enqueue_script( 'semrs-tool-' . $id );

	$cta = semrs_tools_cta( $tools[ $id ][1] );
	$hts = semrs_tools_heights()[ $id ] ?? null;
	$css = $hts ? sprintf( ' style="--semrs-tool-h:%dpx;--semrs-tool-hd:%dpx"', (int) $hts[0], (int) $hts[1] ) : '';

	return sprintf(
		'<div class="semrs-tool-app"%7$s data-semrs-tool="%1$s" data-service-label="%2$s" data-service-url="%3$s" data-service-text="%6$s"><noscript><p>%4$s</p></noscript><p class="semrs-tool-loading">%5$s</p></div>',
		esc_attr( $id ),
		esc_attr( $cta ? $cta[0] : '' ),
		esc_url( $cta ? $cta[2] : '' ),
		esc_html__( 'This free tool needs JavaScript. Please enable it in your browser.', 'semrs-tools' ),
		esc_html__( 'Loading tool…', 'semrs-tools' ),
		esc_attr( $cta ? $cta[1] : '' ),
		$css // Built from integers above.
	);
}
add_shortcode( 'semrs_tool', 'semrs_tools_tool_shortcode' );

/**
 * Tools shown in the directory: switched on, and with a published page at
 * /tools/<id>/ (so new tools never link to a missing page). Sites with their
 * own URLs can change the list with the semrs_tools_listed filter.
 *
 * @return string[]
 */
function semrs_tools_listed_ids() {
	$ids = array_values( array_filter( array_keys( semrs_tools_list() ), 'semrs_tools_enabled' ) );
	$hub = get_page_by_path( 'tools' );
	if ( $hub ) {
		$slugs = wp_list_pluck(
			get_pages(
				array(
					'parent'      => $hub->ID,
					'post_status' => 'publish',
				)
			),
			'post_name'
		);
		$ids   = array_values( array_intersect( $ids, $slugs ) );
	}
	return apply_filters( 'semrs_tools_listed', $ids );
}

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
	$listed  = semrs_tools_listed_ids();
	$out     = '<div class="semrs-tools-dir">';

	foreach ( $cats as $slug => $cat ) {
		if ( $only && $only !== $slug ) {
			continue;
		}
		$cards = '';
		foreach ( $tools as $id => $t ) {
			if ( $t[1] !== $slug || in_array( $id, $exclude, true ) || ! in_array( $id, $listed, true ) ) {
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
