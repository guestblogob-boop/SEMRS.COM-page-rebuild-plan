<?php
/**
 * Settings (option semrs_tools_options) and the Pro switch.
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Are the Pro features unlocked? SEMRS Pro's "Free Tools Pro" module turns this on.
 *
 * @return bool
 */
function semrs_tools_is_pro() {
	return (bool) apply_filters( 'semrs_tools_pro', false );
}

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_tools_options() {
	static $opts = null;
	if ( null === $opts ) {
		$opts = wp_parse_args(
			(array) get_option( 'semrs_tools_options', array() ),
			array(
				'off'        => array(),  // Tool ids switched off.
				'cta'        => '1',      // Expert-help box under each tool.
				'cta_custom' => array(),  // Pro: category slug => [label, text, url].
				'remember'   => '1',      // Remember visitor inputs in their browser.
				'track'      => '1',      // GA4 events when gtag exists.
				'limit'      => 30,       // Server checks per visitor per hour.
				'cache'      => 10,       // Minutes a server result is reused.
				'bulk'       => '1',      // Pro: bulk mode in the server tools.
				'usage'      => '1',      // Pro: count tool use for the usage report.
			)
		);
		$opts['off'] = array_map( 'sanitize_key', (array) $opts['off'] );
	}
	return $opts;
}

/**
 * Is a tool switched on?
 *
 * @param string $id Tool id.
 * @return bool
 */
function semrs_tools_enabled( $id ) {
	return ! in_array( $id, semrs_tools_options()['off'], true );
}

/**
 * Hourly limit for server checks (free: up to 60, Pro: up to 500).
 *
 * @return int
 */
function semrs_tools_hourly_limit() {
	$max = semrs_tools_is_pro() ? 500 : 60;
	return max( 1, min( $max, (int) semrs_tools_options()['limit'] ) );
}

/**
 * Bulk mode available (Pro and switched on)?
 *
 * @return bool
 */
function semrs_tools_bulk_on() {
	return semrs_tools_is_pro() && '1' === semrs_tools_options()['bulk'];
}

/**
 * Expert-help box for a category: [label, text, url] or null when switched off.
 *
 * @param string $cat Category slug.
 * @return array|null
 */
function semrs_tools_cta( $cat ) {
	$o = semrs_tools_options();
	if ( '1' !== $o['cta'] ) {
		return null;
	}
	if ( semrs_tools_is_pro() && ! empty( $o['cta_custom'][ $cat ]['url'] ) ) {
		$c = $o['cta_custom'][ $cat ];
		return array( (string) ( $c['label'] ?? '' ), (string) ( $c['text'] ?? '' ), (string) $c['url'] );
	}
	$cats = semrs_tools_categories();
	if ( empty( $cats[ $cat ][3] ) ) {
		return null;
	}
	return array( $cats[ $cat ][2], '', home_url( $cats[ $cat ][3] ) );
}

/**
 * Register the setting.
 */
function semrs_tools_register_setting() {
	register_setting(
		'semrs_tools_options',
		'semrs_tools_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'semrs_tools_sanitize_options',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'semrs_tools_register_setting' );

/**
 * Settings change what tool pages and the directory show: clear cached pages
 * (SEMRS Core page cache, or any cache plugin listening to the hook).
 */
function semrs_tools_settings_changed() {
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
	do_action( 'semrs_tools_settings_changed' );
}
add_action( 'add_option_semrs_tools_options', 'semrs_tools_settings_changed' );
add_action( 'update_option_semrs_tools_options', 'semrs_tools_settings_changed' );

/**
 * Sanitize settings.
 *
 * @param mixed $in Raw.
 * @return array
 */
function semrs_tools_sanitize_options( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$ids = array_keys( semrs_tools_list() );
	if ( isset( $in['off'] ) && ! isset( $in['on'] ) ) {
		// Already-clean value: WordPress runs this a second time when the option is first created.
		$off = array_values( array_intersect( array_map( 'sanitize_key', (array) $in['off'] ), $ids ) );
	} else {
		$off = array_values( array_diff( $ids, array_map( 'sanitize_key', (array) ( $in['on'] ?? array() ) ) ) );
	}
	$out = array(
		'off'      => $off,
		'cta'      => empty( $in['cta'] ) ? '' : '1',
		'remember' => empty( $in['remember'] ) ? '' : '1',
		'track'    => empty( $in['track'] ) ? '' : '1',
		'limit'    => max( 1, min( 500, absint( $in['limit'] ?? 30 ) ) ),
		'cache'    => max( 0, min( 1440, absint( $in['cache'] ?? 10 ) ) ),
		'bulk'     => empty( $in['bulk'] ) ? '' : '1',
		'usage'    => empty( $in['usage'] ) ? '' : '1',
	);
	$custom = array();
	foreach ( (array) ( $in['cta_custom'] ?? array() ) as $cat => $c ) {
		$cat = sanitize_key( $cat );
		$url = esc_url_raw( trim( (string) ( $c['url'] ?? '' ) ) );
		if ( $cat && $url ) {
			$custom[ $cat ] = array(
				'label' => sanitize_text_field( $c['label'] ?? '' ),
				'text'  => sanitize_text_field( $c['text'] ?? '' ),
				'url'   => $url,
			);
		}
	}
	$out['cta_custom'] = $custom;
	if ( ! semrs_tools_is_pro() ) { // Pro fields are not on the form without Pro: keep what was saved.
		$old = (array) get_option( 'semrs_tools_options', array() );
		foreach ( array( 'cta_custom', 'bulk', 'usage' ) as $k ) {
			if ( isset( $old[ $k ] ) ) {
				$out[ $k ] = $old[ $k ];
			}
		}
		$out['limit'] = min( 60, $out['limit'] );
	}
	return $out;
}
