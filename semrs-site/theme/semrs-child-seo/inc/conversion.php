<?php
/**
 * Sticky mobile CTA bar (Call · WhatsApp · main CTA) and desktop WhatsApp button.
 *
 * @package semrs-child-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * WhatsApp link, or ''.
 *
 * @return string
 */
function semrs_whatsapp_url() {
	$number = preg_replace( '/\D+/', '', (string) semrs_opt( 'whatsapp', '' ) );
	return $number ? 'https://wa.me/' . $number : '';
}

/**
 * Mobile bar shown on this request? Hidden on /tools/ so it never stacks
 * with a mobile anchor ad.
 *
 * @return bool
 */
function semrs_show_mobile_cta() {
	return (bool) semrs_opt( 'mobile_cta', true ) && ! semrs_is_tools_page();
}

/**
 * Body class reserves space for the bar.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function semrs_mobile_cta_body_class( $classes ) {
	if ( semrs_show_mobile_cta() ) {
		$classes[] = 'semrs-has-mobile-cta';
	}
	if ( semrs_whatsapp_url() && semrs_opt( 'whatsapp_button', true ) ) {
		$classes[] = 'semrs-has-whatsapp';
	}
	return $classes;
}
add_filter( 'body_class', 'semrs_mobile_cta_body_class' );

/**
 * Inline SVG icon.
 *
 * @param string $name phone|whatsapp.
 * @return string
 */
function semrs_icon( $name ) {
	$paths = array(
		'phone'    => 'M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.3.2 2.5.57 3.6a1 1 0 0 1-.25 1z',
		'whatsapp' => 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2z',
	);
	return isset( $paths[ $name ] ) ? '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true" focusable="false"><path d="' . $paths[ $name ] . '"/></svg>' : '';
}

/**
 * Print the widgets.
 */
function semrs_conversion_widgets() {
	$phone = (string) semrs_opt( 'phone', '' );
	$wa    = semrs_whatsapp_url();

	if ( semrs_show_mobile_cta() ) {
		echo '<nav class="semrs-mobile-cta" aria-label="' . esc_attr__( 'Quick contact', 'semrs-child-seo' ) . '">';
		if ( $phone ) {
			printf( '<a href="tel:%s">%s<span>%s</span></a>', esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ), semrs_icon( 'phone' ), esc_html__( 'Call', 'semrs-child-seo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
		}
		if ( $wa ) {
			printf( '<a href="%s" target="_blank" rel="noopener">%s<span>WhatsApp</span></a>', esc_url( $wa ), semrs_icon( 'whatsapp' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
		}
		printf( '<a class="semrs-mobile-cta__primary" href="%s">%s</a>', esc_url( semrs_opt( 'cta_url', '/get-a-quote/' ) ), esc_html( semrs_opt( 'cta_text', 'Free SEO review' ) ) );
		echo '</nav>';
	}

	if ( $wa && semrs_opt( 'whatsapp_button', true ) ) {
		printf( '<a class="semrs-whatsapp" href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>', esc_url( $wa ), esc_attr__( 'Chat on WhatsApp', 'semrs-child-seo' ), semrs_icon( 'whatsapp' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
	}
}
add_action( 'wp_footer', 'semrs_conversion_widgets', 6 );
