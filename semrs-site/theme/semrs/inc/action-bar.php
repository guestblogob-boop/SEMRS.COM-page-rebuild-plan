<?php
/**
 * Quick contact bar on phones: Call · WhatsApp · header button.
 * Off by default (Customizer → SEMRS Theme → Header builder). Uses the Phone,
 * WhatsApp profile URL and header button settings. Child themes that print
 * their own bar (SEMRS Child SEO) take over automatically.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bar shown on this request?
 *
 * @return bool
 */
function semrs_theme_action_bar_on() {
	return (bool) semrs_theme_opt( 'action_bar' ) && ! function_exists( 'semrs_conversion_widgets' );
}

/**
 * Body class reserves space for the bar.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function semrs_theme_action_bar_class( $classes ) {
	if ( semrs_theme_action_bar_on() ) {
		$classes[] = 'sp-has-action-bar';
	}
	return $classes;
}
add_filter( 'body_class', 'semrs_theme_action_bar_class' );

/**
 * Print the bar.
 */
function semrs_theme_action_bar() {
	if ( ! semrs_theme_action_bar_on() ) {
		return;
	}
	$phone = preg_replace( '/[^\d+]/', '', (string) semrs_theme_opt( 'phone' ) );
	$wa    = (string) semrs_theme_opt( 'social_whatsapp' );
	$text  = (string) semrs_theme_opt( 'cta_text' );
	$url   = (string) semrs_theme_opt( 'cta_url' );
	if ( ! $phone && ! $wa && ! ( $text && $url ) ) {
		return;
	}
	$icon = function ( $d ) {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="' . esc_attr( $d ) . '"/></svg>';
	};
	echo '<nav class="sp-action-bar" aria-label="' . esc_attr__( 'Quick contact', 'semrs' ) . '">';
	if ( $phone ) {
		printf( '<a href="tel:%s">%s<span>%s</span></a>', esc_attr( $phone ), $icon( 'M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.3.2 2.5.57 3.6a1 1 0 0 1-.25 1z' ), esc_html__( 'Call', 'semrs' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped SVG.
	}
	if ( $wa ) {
		$nets = semrs_theme_social_networks();
		printf( '<a href="%s" target="_blank" rel="noopener">%s<span>WhatsApp</span></a>', esc_url( $wa ), $icon( $nets['whatsapp'][2] ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped SVG.
	}
	if ( $text && $url ) {
		printf( '<a class="sp-action-bar__primary" href="%s">%s</a>', esc_url( $url ), esc_html( $text ) );
	}
	echo '</nav>';
}
add_action( 'wp_footer', 'semrs_theme_action_bar', 6 );
