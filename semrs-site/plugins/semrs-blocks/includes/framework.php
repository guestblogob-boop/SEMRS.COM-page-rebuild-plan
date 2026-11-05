<?php
/**
 * Block framework: every SEMRS block is defined once in PHP (attributes,
 * editor controls, render callback). One generic editor script builds the
 * settings sidebar and a live server-rendered preview for all of them.
 *
 * Add-ons (SEMRS Pro) register more blocks with the `semrs_blocks` filter.
 *
 * Control types: text, textarea, number, range, select, toggle, color, url,
 * datetime, media, gallery, icon, category, posttype.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every block definition (free and add-ons), including switched-off ones.
 *
 * @return array name => definition.
 */
function semrs_blocks_all() {
	static $blocks = null;
	if ( null === $blocks ) {
		$blocks = apply_filters( 'semrs_blocks', array() );
	}
	return $blocks;
}

/**
 * Switched-on block definitions (SEMRS → Blocks).
 *
 * @return array name => definition.
 */
function semrs_blocks_registry() {
	$off = (array) get_option( 'semrs_blocks_disabled', array() );
	return array_diff_key( semrs_blocks_all(), array_flip( $off ) );
}

/**
 * Pro blocks shown locked in the inserter and Blocks screen while SEMRS Pro
 * is not installed: name => [title, dashicon, description].
 *
 * @return array
 */
function semrs_blocks_pro_teasers() {
	return array(
		'semrs/slider'   => array( __( 'Slider / Carousel', 'semrs-blocks' ), 'slides', __( 'Image slider or post carousel with arrows, dots, swipe and autoplay.', 'semrs-blocks' ) ),
		'semrs/counter'  => array( __( 'Animated Counter', 'semrs-blocks' ), 'chart-bar', __( 'Numbers that count up when they scroll into view.', 'semrs-blocks' ) ),
		'semrs/modal' => array( __( 'Popup / Modal', 'semrs-blocks' ), 'external', __( 'A button that opens a popup with any blocks, or a popup that opens by itself.', 'semrs-blocks' ) ),
		'semrs/pricing-switch' => array( __( 'Pricing Table with Switch', 'semrs-blocks' ), 'money-alt', __( 'Plans side by side with a monthly / yearly switch.', 'semrs-blocks' ) ),
		'semrs/sticky-bar' => array( __( 'Sticky Bar', 'semrs-blocks' ), 'align-full-width', __( 'A slim bar fixed to the top or bottom with a message and a button.', 'semrs-blocks' ) ),
		'semrs/before-after' => array( __( 'Before / After', 'semrs-blocks' ), 'image-flip-horizontal', __( 'Drag a handle to compare two images.', 'semrs-blocks' ) ),
		'semrs/hotspots' => array( __( 'Image Hotspots', 'semrs-blocks' ), 'location', __( 'Clickable points on an image that open a short note.', 'semrs-blocks' ) ),
		'semrs/lottie' => array( __( 'Lottie Animation', 'semrs-blocks' ), 'controls-play', __( 'Lightweight vector animations from Lottie files.', 'semrs-blocks' ) ),
		'semrs/marquee' => array( __( 'Logo / Text Marquee', 'semrs-blocks' ), 'leftright', __( 'Logos or phrases scrolling sideways in an endless loop.', 'semrs-blocks' ) ),
		'semrs/video-popup' => array( __( 'Video Popup', 'semrs-blocks' ), 'video-alt3', __( 'A thumbnail; the YouTube or Vimeo video opens in a popup only when clicked.', 'semrs-blocks' ) ),
		'semrs/switcher' => array( __( 'Content Switcher', 'semrs-blocks' ), 'randomize', __( 'A switch between two contents, e.g. For shops / For agencies.', 'semrs-blocks' ) ),
		'semrs/data-table' => array( __( 'Data Table', 'semrs-blocks' ), 'database', __( 'Sortable, searchable table from pasted rows or CSV.', 'semrs-blocks' ) ),
		'semrs/timeline-pro' => array( __( 'Animated Timeline', 'semrs-blocks' ), 'backup', __( 'Timeline with icons; events slide in as they scroll into view.', 'semrs-blocks' ) ),
		'semrs/reviews' => array( __( 'Reviews Slider', 'semrs-blocks' ), 'format-quote', __( 'Real reviews in a swipeable slider with stars and links to the originals.', 'semrs-blocks' ) ),
		'semrs/badges' => array( __( 'Trust Badges', 'semrs-blocks' ), 'shield', __( 'Guarantees row: secure checkout, refunds, support.', 'semrs-blocks' ) ),
		'semrs/model-3d' => array( __( '3D Model Viewer', 'semrs-blocks' ), 'visibility', __( 'Rotate and zoom 3D models (.glb / .gltf), with "view in your room" AR on phones.', 'semrs-blocks' ) ),
	);
}

/**
 * Where "See SEMRS Pro" links go.
 *
 * @return string
 */
function semrs_blocks_pro_url() {
	return apply_filters( 'semrs_pro_url', 'https://semrs.com/semrs-pro/' );
}

/**
 * Block category "SEMRS" in the inserter.
 *
 * @param array $cats Categories.
 * @return array
 */
function semrs_blocks_category( $cats ) {
	array_unshift( $cats, array( 'slug' => 'semrs', 'title' => __( 'SEMRS', 'semrs-blocks' ), 'icon' => null ) );
	return $cats;
}
add_filter( 'block_categories_all', 'semrs_blocks_category' );

/**
 * Attribute type for a control type.
 *
 * @param string $control Control type.
 * @return string
 */
function semrs_blocks_attr_type( $control ) {
	switch ( $control ) {
		case 'number':
		case 'range':
		case 'media':
			return 'number';
		case 'toggle':
			return 'boolean';
		case 'gallery':
			return 'array';
		default:
			return 'string';
	}
}

/**
 * Register every block on the server (dynamic, PHP-rendered).
 */
function semrs_blocks_register() {
	wp_register_script(
		'semrs-blocks-editor',
		SEMRS_BLOCKS_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data' ),
		SEMRS_BLOCKS_VERSION,
		true
	);
	wp_register_style( 'semrs-blocks-front', SEMRS_BLOCKS_URL . 'assets/blocks.css', array(), SEMRS_BLOCKS_VERSION );
	wp_register_style( 'semrs-blocks-editor', SEMRS_BLOCKS_URL . 'assets/editor.css', array( 'semrs-blocks-front' ), SEMRS_BLOCKS_VERSION );

	foreach ( semrs_blocks_registry() as $name => $b ) {
		$attrs = array(
			'className' => array( 'type' => 'string', 'default' => '' ),
			'align'     => array( 'type' => 'string', 'default' => '' ),
			'anchor'    => array( 'type' => 'string', 'default' => '' ),
		);
		foreach ( $b['controls'] as $key => $c ) {
			$type          = semrs_blocks_attr_type( $c['type'] );
			$attrs[ $key ] = array( 'type' => $type );
			if ( array_key_exists( 'default', $c ) ) {
				$attrs[ $key ]['default'] = $c['default'];
			} elseif ( 'array' === $type ) {
				$attrs[ $key ]['default'] = array();
			}
		}
		register_block_type(
			$name,
			array(
				'api_version'     => 3,
				'title'           => $b['title'],
				'category'        => 'semrs',
				'description'     => $b['description'] ?? '',
				'keywords'        => $b['keywords'] ?? array(),
				'attributes'      => $attrs,
				'supports'        => array( 'align' => array( 'wide', 'full', 'left', 'center', 'right' ), 'html' => false, 'anchor' => true ),
				'editor_script'   => 'semrs-blocks-editor',
				'editor_style'    => 'semrs-blocks-editor',
				'parent'          => $b['parent'] ?? null,
				'render_callback' => function ( $atts, $content = '', $block = null ) use ( $name, $b ) {
					$atts = wp_parse_args( $atts, array() );
					$html = call_user_func( $b['render'], $atts, $block, $content );
					if ( '' === $html ) {
						return '';
					}
					$classes = 'semrs-block semrs-block--' . sanitize_html_class( str_replace( 'semrs/', '', $name ) );
					if ( ! empty( $atts['align'] ) ) {
						$classes .= ' align' . sanitize_html_class( $atts['align'] );
					}
					if ( ! empty( $atts['className'] ) ) {
						$classes .= ' ' . esc_attr( $atts['className'] );
					}
					$anchor = ! empty( $atts['anchor'] ) ? ' id="' . esc_attr( $atts['anchor'] ) . '"' : '';
					return semrs_blocks_css_once() . '<div class="' . esc_attr( $classes ) . '"' . $anchor . '>' . $html . '</div>';
				},
			)
		);
	}
}
add_action( 'init', 'semrs_blocks_register', 20 );

/**
 * Data for the editor script: control specs, icon names, categories.
 */
function semrs_blocks_editor_data() {
	$blocks = array();
	foreach ( semrs_blocks_registry() as $name => $b ) {
		$blocks[ $name ] = array(
			'title'    => $b['title'],
			'icon'     => $b['icon'] ?? 'star-filled',
			'tier'     => $b['tier'] ?? 'free',
			'controls' => $b['controls'],
			'panels'   => $b['panels'] ?? array(),
			'example'  => $b['example'] ?? null,
			'inner'    => ! empty( $b['inner'] ),
			'parent'   => $b['parent'] ?? null,
			'allowed'  => $b['allowed'] ?? null,
			'template' => $b['template'] ?? null,
			'description' => $b['description'] ?? '',
		);
	}
	$cats = array( array( 'value' => '', 'label' => __( 'All categories', 'semrs-blocks' ) ) );
	foreach ( get_categories( array( 'hide_empty' => false, 'number' => 200 ) ) as $c ) {
		$cats[] = array( 'value' => (string) $c->term_id, 'label' => $c->name );
	}
	$teasers = array();
	if ( ! semrs_blocks_has_pro() && get_option( 'semrs_blocks_show_pro', true ) ) {
		foreach ( semrs_blocks_pro_teasers() as $name => $t ) {
			$teasers[ $name ] = array( 'title' => $t[0], 'icon' => $t[1], 'description' => $t[2] );
		}
	}
	$types = array();
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
		if ( 'attachment' !== $pt->name ) {
			$types[] = array( 'value' => $pt->name, 'label' => $pt->labels->singular_name );
		}
	}
	wp_add_inline_script(
		'semrs-blocks-editor',
		'window.SEMRS_BLOCKS=' . wp_json_encode(
			array(
				'blocks'     => $blocks,
				'icons'      => array_keys( semrs_blocks_icons() ),
				'iconSvgs'   => semrs_blocks_icons(),
				'categories' => $cats,
				'postTypes'  => $types,
				'pro'        => semrs_blocks_has_pro(),
				'teasers'    => $teasers,
				'proUrl'     => semrs_blocks_pro_url(),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'semrs_blocks_editor_data' );

/**
 * True when block HTML is going into the visible page (or an editor
 * preview). Content rendered inside <head>, e.g. by SEO plugins building a
 * meta description, must not use up the "print CSS once" flags.
 *
 * @return bool
 */
function semrs_blocks_page_output() {
	if ( wp_is_json_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return true;
	}
	return did_action( 'wp_head' ) && ! doing_action( 'wp_head' );
}

/**
 * Front-end stylesheet, printed once inline on the first SEMRS block
 * (keeps pages without SEMRS blocks completely untouched, no flash).
 *
 * @return string
 */
function semrs_blocks_css_once() {
	static $done = false;
	if ( $done || ! semrs_blocks_page_output() ) {
		return '';
	}
	$done = true;
	$file = SEMRS_BLOCKS_DIR . 'assets/blocks.min.css';
	if ( ! file_exists( $file ) ) {
		$file = SEMRS_BLOCKS_DIR . 'assets/blocks.css';
	}
	return '<style id="semrs-blocks-css">' . wp_strip_all_tags( (string) file_get_contents( $file ) ) . '</style>'; // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Enqueue a front-end script once (deferred, footer).
 *
 * @param string $handle Handle.
 * @param string $file   File in assets/js/.
 * @param array  $deps   Dependencies.
 */
function semrs_blocks_script( $handle, $file, $deps = array() ) {
	if ( ! wp_script_is( $handle, 'registered' ) ) {
		wp_register_script( $handle, SEMRS_BLOCKS_URL . 'assets/js/' . $file, $deps, SEMRS_BLOCKS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	wp_enqueue_script( $handle );
}

/**
 * Helpers for render callbacks.
 *
 * @param array  $a   Attributes.
 * @param string $key Key.
 * @param mixed  $def Default.
 * @return mixed
 */
function semrs_blocks_a( $a, $key, $def = '' ) {
	return isset( $a[ $key ] ) && '' !== $a[ $key ] && null !== $a[ $key ] ? $a[ $key ] : $def;
}

/**
 * Lines of a textarea attribute.
 *
 * @param string $text Text.
 * @return string[]
 */
function semrs_blocks_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $text ) ), 'strlen' ) );
}

/**
 * Safe CSS colour value.
 *
 * @param string $c Colour.
 * @return string
 */
function semrs_blocks_color( $c ) {
	$c = trim( (string) $c );
	return preg_match( '/^(#[0-9a-fA-F]{3,8}|rgba?\([\d\s.,%]+\)|var\(--[a-z0-9-]+\)|[a-z]+)$/', $c ) ? $c : '';
}

/**
 * Print a block's own CSS once per page (only pages that use the block get it).
 *
 * @param string $key Unique key.
 * @param string $css CSS.
 * @return string
 */
function semrs_blocks_css_part( $key, $css ) {
	static $done = array();
	if ( isset( $done[ $key ] ) || ! semrs_blocks_page_output() ) {
		return '';
	}
	$done[ $key ] = true;
	return '<style id="semrs-css-' . esc_attr( $key ) . '">' . wp_strip_all_tags( $css ) . '</style>';
}
