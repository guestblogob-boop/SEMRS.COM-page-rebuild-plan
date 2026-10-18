<?php
/**
 * Dark mode (Pro, SEMRS theme): a sun/moon switch for the header builder.
 * The header, top bar and footer stay navy in both modes.
 *
 * Light weight: one 300-byte inline script in <head> (sets the mode before
 * the first paint, so there is no white flash), one small stylesheet and a
 * tiny click handler. The choice is kept in the visitor's browser only.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customizer setting (Header builder section).
 *
 * @param array $map Theme settings.
 * @return array
 */
function semrs_pro_dark_settings( $map ) {
	$map['dark_mode'] = array(
		'hbuilder',
		'select',
		__( 'Dark mode switch (Pro): add the "Dark / light switch" element to a header row', 'semrs-pro' ),
		'toggle',
		array(
			'off'    => __( 'Off', 'semrs-pro' ),
			'toggle' => __( 'Switch, starts light', 'semrs-pro' ),
			'auto'   => __( 'Switch, starts with the visitor\'s device setting', 'semrs-pro' ),
		),
	);
	return $map;
}
add_filter( 'semrs_theme_settings', 'semrs_pro_dark_settings' );

/**
 * Current mode setting.
 *
 * @return string off|toggle|auto
 */
function semrs_pro_dark_mode() {
	return function_exists( 'semrs_theme_opt' ) ? (string) semrs_theme_opt( 'dark_mode' ) : 'off';
}

/**
 * Header builder element.
 *
 * @param array $els Elements.
 * @return array
 */
function semrs_pro_dark_element( $els ) {
	$els['darkmode'] = __( 'Dark / light switch (Pro)', 'semrs-pro' );
	return $els;
}
add_filter( 'semrs_theme_header_elements', 'semrs_pro_dark_element' );

/**
 * Render the switch.
 */
function semrs_pro_dark_button() {
	if ( 'off' === semrs_pro_dark_mode() ) {
		return;
	}
	printf(
		'<button type="button" class="sp-dark-toggle" aria-pressed="false" aria-label="%1$s" title="%1$s"><svg class="sp-dark-toggle__moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg><svg class="sp-dark-toggle__sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></button>',
		esc_attr__( 'Dark mode', 'semrs-pro' )
	);
}
add_action( 'semrs_theme_render_element_darkmode', 'semrs_pro_dark_button' );

/**
 * Set the mode before the page paints (no flash).
 */
function semrs_pro_dark_head() {
	$mode = semrs_pro_dark_mode();
	if ( 'off' === $mode ) {
		return;
	}
	$auto = 'auto' === $mode ? 'true' : 'false';
	echo "<script id=\"semrs-dark-init\">(function(){var d=document.documentElement,s=null;try{s=localStorage.getItem('semrs-theme')}catch(e){}if(s?s==='dark':({$auto}&&window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)){d.setAttribute('data-theme','dark')}})();</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- static script.
}
add_action( 'wp_head', 'semrs_pro_dark_head', 0 );

/**
 * Styles and the click handler.
 */
function semrs_pro_dark_assets() {
	if ( 'off' === semrs_pro_dark_mode() ) {
		return;
	}
	wp_enqueue_style( 'semrs-pro-dark', SEMRS_PRO_URL . 'assets/dark.css', array( 'semrs' ), SEMRS_PRO_VERSION );
	wp_enqueue_script( 'semrs-pro-dark', SEMRS_PRO_URL . 'assets/js/dark.js', array(), SEMRS_PRO_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'semrs_pro_dark_assets', 20 );
