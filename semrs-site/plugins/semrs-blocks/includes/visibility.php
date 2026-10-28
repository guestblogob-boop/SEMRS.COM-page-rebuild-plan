<?php
/**
 * Free device visibility for every block: hide on phones, tablets or
 * desktops (block sidebar → Visibility). Done with CSS classes, so it works
 * with page caching. SEMRS Pro adds login, role, schedule and page rules.
 *
 * Breakpoints: phone < 782 px, tablet 782–1023 px, desktop ≥ 1024 px.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Device keys.
 *
 * @return string[]
 */
function semrs_blocks_devices() {
	return array( 'Mobile', 'Tablet', 'Desktop' );
}

/**
 * Every block accepts the visibility attributes on the server, so dynamic
 * blocks and their editor previews validate them.
 *
 * @param array $args Block type args.
 * @return array
 */
function semrs_blocks_visibility_attrs( $args ) {
	$args['attributes'] = $args['attributes'] ?? array();
	foreach ( semrs_blocks_devices() as $d ) {
		$args['attributes'][ 'semrsHide' . $d ] = array( 'type' => 'boolean', 'default' => false );
	}
	return $args;
}
add_filter( 'register_block_type_args', 'semrs_blocks_visibility_attrs' );

/**
 * Add the hide classes to the block's outer element and print the three
 * CSS rules once.
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function semrs_blocks_visibility_render( $html, $block ) {
	$classes = array();
	foreach ( semrs_blocks_devices() as $d ) {
		if ( ! empty( $block['attrs'][ 'semrsHide' . $d ] ) ) {
			$classes[] = 'semrs-hide-' . strtolower( $d );
		}
	}
	if ( ! $classes || '' === trim( $html ) || is_admin() || wp_is_json_request() ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	do {
		if ( ! $p->next_tag() ) {
			return $html;
		}
	} while ( in_array( $p->get_tag(), array( 'STYLE', 'SCRIPT', 'LINK' ), true ) );
	foreach ( $classes as $c ) {
		$p->add_class( $c );
	}
	return semrs_blocks_visibility_css() . $p->get_updated_html();
}
add_filter( 'render_block', 'semrs_blocks_visibility_render', 20, 2 );

/**
 * The visibility rules, once per page.
 *
 * @return string
 */
function semrs_blocks_visibility_css() {
	static $done = false;
	if ( $done || ! semrs_blocks_page_output() ) {
		return '';
	}
	$done = true;
	return '<style id="semrs-visibility-css">@media (max-width:781px){.semrs-hide-mobile{display:none!important}}@media (min-width:782px) and (max-width:1023px){.semrs-hide-tablet{display:none!important}}@media (min-width:1024px){.semrs-hide-desktop{display:none!important}}</style>';
}

/**
 * Editor script: the Visibility panel on every block.
 */
function semrs_blocks_visibility_editor() {
	wp_enqueue_script( 'semrs-blocks-extensions', SEMRS_BLOCKS_URL . 'assets/extensions.js', array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), SEMRS_BLOCKS_VERSION, true );
	wp_localize_script(
		'semrs-blocks-extensions',
		'SEMRS_EXT',
		array(
			'pro'    => semrs_blocks_has_pro(),
			'proUrl' => semrs_blocks_pro_url(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'semrs_blocks_visibility_editor' );
