<?php
/**
 * Customizer → SEMRS Theme → "Contact & conversion" (child settings).
 *
 * @package semrs-child-seo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register settings.
 *
 * @param WP_Customize_Manager $wpc Customizer.
 */
function semrs_child_customize_register( $wpc ) {
	$wpc->add_section(
		'semrs_contact',
		array(
			'title'    => __( 'Contact & conversion', 'semrs-child-seo' ),
			'panel'    => 'semrs_theme',
			'priority' => 5,
		)
	);
	$fields = array(
		'phone'           => array( 'text', __( 'Phone (international format, e.g. +1 555 123 4567)', 'semrs-child-seo' ), '' ),
		'whatsapp'        => array( 'text', __( 'WhatsApp number (digits with country code)', 'semrs-child-seo' ), '' ),
		'cta_text'        => array( 'text', __( 'Mobile bar main button text', 'semrs-child-seo' ), 'Free SEO review' ),
		'cta_url'         => array( 'url', __( 'Mobile bar main button link', 'semrs-child-seo' ), '/get-a-quote/' ),
		'mobile_cta'      => array( 'checkbox', __( 'Sticky mobile CTA bar (hidden on /tools/)', 'semrs-child-seo' ), true ),
		'whatsapp_button' => array( 'checkbox', __( 'WhatsApp button on desktop', 'semrs-child-seo' ), true ),
	);
	foreach ( $fields as $key => $f ) {
		$sanitize = 'checkbox' === $f[0] ? 'rest_sanitize_boolean' : ( 'url' === $f[0] ? 'esc_url_raw' : 'sanitize_text_field' );
		$wpc->add_setting( 'semrs_' . $key, array( 'default' => $f[2], 'sanitize_callback' => $sanitize ) );
		$wpc->add_control( 'semrs_' . $key, array( 'label' => $f[1], 'section' => 'semrs_contact', 'type' => $f[0] ) );
	}
}
add_action( 'customize_register', 'semrs_child_customize_register', 20 );
