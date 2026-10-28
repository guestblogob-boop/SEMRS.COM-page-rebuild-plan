<?php
/**
 * Ads module bootstrap: positions, defaults, settings, includes.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_ADS_DIR', __DIR__ . '/' );


/**
 * Ad positions.
 *
 * @return array<string, string>
 */
function semrs_ads_positions() {
	/**
	 * Ad positions (SEMRS Pro adds more).
	 *
	 * @param array $positions key => label.
	 */
	return apply_filters( 'semrs_ads_positions', array(
		'before_content'  => __( 'Before content (below the title)', 'semrs-core' ),
		'after_paragraph' => __( 'After paragraph N', 'semrs-core' ),
		'mid_content'     => __( 'Middle of content', 'semrs-core' ),
		'after_content'   => __( 'End of content', 'semrs-core' ),
		'tool'            => __( 'Manual: [semrs_ad slot="tool"] (below a tool result)', 'semrs-core' ),
		'sidebar'         => __( 'Manual: [semrs_ad slot="sidebar"] (sticky, wide screens)', 'semrs-core' ),
		'manual'          => __( 'Manual: [semrs_ad slot="manual"] (anywhere)', 'semrs-core' ),
	) );
}

/**
 * Default settings. Excluded paths keep conversion and legal pages ad-free.
 *
 * @return array
 */
function semrs_ads_defaults() {
	$slots = array();
	foreach ( array_keys( semrs_ads_positions() ) as $key ) {
		$slots[ $key ] = array(
			'enabled' => in_array( $key, array( 'after_paragraph', 'mid_content', 'after_content', 'tool', 'sidebar' ), true ),
			'network' => 'adsense',
			'ad_slot' => '',
			'format'  => in_array( $key, array( 'after_paragraph', 'mid_content' ), true ) ? 'in-article' : 'auto',
			'code'    => '',
			'device'  => 'sidebar' === $key ? 'wide' : 'all',
		);
	}
	return array(
		'enabled'         => false,
		'publisher_id'    => '',
		'load_adsense'    => true,
		'head_code'       => '',
		'lazy'            => true,
		'hide_for_admins' => true,
		'label'           => 'Advertisement',
		'paragraph_n'     => 2,
		'min_words'       => 300,
		'max_incontent'   => 3,
		'min_gap'         => 3,
		'min_gap_words'   => 250,
		'exclude_front'   => true,
		'exclude_search'  => true,
		'exclude_paths'   => "/services/\n/pricing/\n/contact-us/\n/get-a-quote/\n/thank-you/\n/about/\n/how-we-work/\n/privacy-policy/\n/terms-and-conditions/\n/cookie-policy/\n/disclosure/\n/guest-post-submission-form/\n/registration-form/\n/portfolio/\n/checkout/\n/cart/\n/my-account/\n/semrs-pro/\n/my-licenses/",
		'exclude_ids'     => '',
		'post_types'      => array( 'post', 'page' ),
		'manage_adstxt'   => false,
		'adstxt'          => '',
		'slots'           => $slots,
	);
}

/**
 * Settings merged with defaults.
 *
 * @return array
 */
function semrs_ads_settings() {
	static $settings = null;
	if ( null === $settings ) {
		$defaults = semrs_ads_defaults();
		$saved    = get_option( 'semrs_ads', array() );
		$settings = array_merge( $defaults, is_array( $saved ) ? $saved : array() );
		foreach ( $defaults['slots'] as $key => $slot ) {
			$settings['slots'][ $key ] = array_merge( $slot, isset( $settings['slots'][ $key ] ) ? (array) $settings['slots'][ $key ] : array() );
		}
	}
	return $settings;
}

require SEMRS_ADS_DIR . 'rules.php';
require SEMRS_ADS_DIR . 'render.php';
require SEMRS_ADS_DIR . 'adstxt.php';
if ( is_admin() ) {
	require SEMRS_ADS_DIR . 'admin.php';
}
