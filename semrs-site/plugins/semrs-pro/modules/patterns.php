<?php
/**
 * Pro block patterns (Block inserter → Patterns → SEMRS).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Pro patterns from patterns/*.html.
 */
function semrs_pro_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	$titles = array(
		'services' => __( 'SEMRS Pro: Service cards (3 columns)', 'semrs-pro' ),
		'process'  => __( 'SEMRS Pro: 6-step process with approval step', 'semrs-pro' ),
		'pricing'  => __( 'SEMRS Pro: Pricing cards (3 plans)', 'semrs-pro' ),
		'tool'     => __( 'SEMRS Pro: Free tool + service CTA + ad slot', 'semrs-pro' ),
		'form'     => __( 'SEMRS Pro: Contact form section', 'semrs-pro' ),
	);
	foreach ( $titles as $slug => $title ) {
		$file = SEMRS_PRO_DIR . 'patterns/' . $slug . '.html';
		if ( file_exists( $file ) ) {
			register_block_pattern(
				'semrs-pro/' . $slug,
				array(
					'title'      => $title,
					'categories' => array( 'semrs' ),
					'content'    => (string) file_get_contents( $file ), // phpcs:ignore WordPress.WP.AlternativeFunctions
				)
			);
		}
	}
}
add_action( 'init', 'semrs_pro_register_patterns' );
