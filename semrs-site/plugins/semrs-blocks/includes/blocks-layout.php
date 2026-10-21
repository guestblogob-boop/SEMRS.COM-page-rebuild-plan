<?php
/**
 * Free layout & content blocks: Container (section / row / grid), Tabs,
 * Accordion (FAQ), Gallery with lightbox, Testimonial, Table of Contents.
 *
 * Container, Tabs and Accordion hold other blocks ("inner" blocks).
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the definitions.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_blocks_layout( $blocks ) {
	$hide = array( 'type' => 'toggle', 'default' => false );

	$blocks['semrs/container'] = array(
		'title'       => __( 'Container (Section / Row / Grid)', 'semrs-blocks' ),
		'description' => __( 'Group blocks in a full-width section, a row or a grid, with background colour, gradient or image, spacing and width.', 'semrs-blocks' ),
		'icon'        => 'layout',
		'keywords'    => array( 'section', 'container', 'row', 'grid', 'columns', 'background', 'wrapper' ),
		'render'      => 'semrs_blocks_render_container',
		'inner'       => true,
		'panels'      => array(
			__( 'Layout', 'semrs-blocks' )     => array( 'layout', 'columns', 'columnsTablet', 'gap', 'valign', 'justify', 'width', 'tag' ),
			__( 'Spacing', 'semrs-blocks' )    => array( 'padTop', 'padBottom', 'padSides', 'minHeight', 'radius' ),
			__( 'Background', 'semrs-blocks' ) => array( 'bg', 'gradient', 'image', 'imageUrl', 'overlay', 'overlayOpacity', 'textColor', 'fixed' ),
		),
		'controls'    => array(
			'layout'         => array( 'type' => 'select', 'label' => __( 'Arrange inner blocks', 'semrs-blocks' ), 'default' => 'stack', 'options' => array( 'stack' => __( 'Stack (one under another)', 'semrs-blocks' ), 'row' => __( 'Row (side by side, wraps on phones)', 'semrs-blocks' ), 'grid' => __( 'Grid (equal columns)', 'semrs-blocks' ) ) ),
			'columns'        => array( 'type' => 'range', 'label' => __( 'Grid columns (desktop)', 'semrs-blocks' ), 'default' => 3, 'min' => 1, 'max' => 6 ),
			'columnsTablet'  => array( 'type' => 'range', 'label' => __( 'Grid columns (tablet; phones use 1)', 'semrs-blocks' ), 'default' => 2, 'min' => 1, 'max' => 4 ),
			'gap'            => array( 'type' => 'range', 'label' => __( 'Gap between blocks (px)', 'semrs-blocks' ), 'default' => 24, 'min' => 0, 'max' => 96 ),
			'valign'         => array( 'type' => 'select', 'label' => __( 'Vertical alignment', 'semrs-blocks' ), 'default' => 'stretch', 'options' => array( 'stretch' => __( 'Stretch', 'semrs-blocks' ), 'start' => __( 'Top', 'semrs-blocks' ), 'center' => __( 'Middle', 'semrs-blocks' ), 'end' => __( 'Bottom', 'semrs-blocks' ) ) ),
			'justify'        => array( 'type' => 'select', 'label' => __( 'Horizontal alignment (row)', 'semrs-blocks' ), 'default' => 'start', 'options' => array( 'start' => __( 'Left', 'semrs-blocks' ), 'center' => __( 'Centre', 'semrs-blocks' ), 'end' => __( 'Right', 'semrs-blocks' ), 'space-between' => __( 'Space between', 'semrs-blocks' ) ) ),
			'width'          => array( 'type' => 'select', 'label' => __( 'Content width', 'semrs-blocks' ), 'default' => 'container', 'options' => array( 'full' => __( 'Full width', 'semrs-blocks' ), 'container' => __( 'Site container', 'semrs-blocks' ), 'narrow' => __( 'Narrow (reading width)', 'semrs-blocks' ) ) ),
			'tag'            => array( 'type' => 'select', 'label' => __( 'HTML element', 'semrs-blocks' ), 'default' => 'div', 'options' => array( 'div' => 'div', 'section' => 'section', 'header' => 'header', 'footer' => 'footer', 'aside' => 'aside', 'article' => 'article' ) ),
			'padTop'         => array( 'type' => 'range', 'label' => __( 'Padding top (px)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 240 ),
			'padBottom'      => array( 'type' => 'range', 'label' => __( 'Padding bottom (px)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 240 ),
			'padSides'       => array( 'type' => 'range', 'label' => __( 'Padding left & right (px)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 120 ),
			'minHeight'      => array( 'type' => 'range', 'label' => __( 'Minimum height (px, 0 = auto)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 1000 ),
			'radius'         => array( 'type' => 'range', 'label' => __( 'Rounded corners (px)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 48 ),
			'bg'             => array( 'type' => 'color', 'label' => __( 'Background colour', 'semrs-blocks' ), 'default' => '' ),
			'gradient'       => array( 'type' => 'select', 'label' => __( 'Background gradient', 'semrs-blocks' ), 'default' => '', 'options' => array( '' => __( 'None', 'semrs-blocks' ), 'navy' => __( 'Navy', 'semrs-blocks' ), 'sunrise' => __( 'Sunrise', 'semrs-blocks' ), 'ocean' => __( 'Ocean', 'semrs-blocks' ), 'mint' => __( 'Mint', 'semrs-blocks' ), 'soft' => __( 'Soft grey', 'semrs-blocks' ) ) ),
			'image'          => array( 'type' => 'media', 'label' => __( 'Background image', 'semrs-blocks' ), 'default' => 0 ),
			'imageUrl'       => array( 'type' => 'text', 'label' => __( 'Background image address (used when no image is chosen above)', 'semrs-blocks' ), 'default' => '' ),
			'overlay'        => array( 'type' => 'color', 'label' => __( 'Overlay colour (over the image)', 'semrs-blocks' ), 'default' => '' ),
			'overlayOpacity' => array( 'type' => 'range', 'label' => __( 'Overlay opacity (%)', 'semrs-blocks' ), 'default' => 50, 'min' => 0, 'max' => 100 ),
			'textColor'      => array( 'type' => 'color', 'label' => __( 'Text colour', 'semrs-blocks' ), 'default' => '' ),
			'fixed'          => array( 'type' => 'toggle', 'label' => __( 'Fixed background (parallax feel, desktop only)', 'semrs-blocks' ), 'default' => false ),
		),
	);

	$blocks['semrs/tabs'] = array(
		'title'       => __( 'Tabs', 'semrs-blocks' ),
		'description' => __( 'Content in tabs. Each tab can hold any blocks. Search engines see all tab content.', 'semrs-blocks' ),
		'icon'        => 'index-card',
		'keywords'    => array( 'tabs', 'tabbed', 'toggle', 'switch' ),
		'render'      => 'semrs_blocks_render_tabs',
		'inner'       => true,
		'allowed'     => array( 'semrs/tab' ),
		'template'    => array( array( 'semrs/tab', array( 'title' => 'Tab one' ) ), array( 'semrs/tab', array( 'title' => 'Tab two' ) ) ),
		'controls'    => array(
			'style'    => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'underline', 'options' => array( 'underline' => __( 'Underline', 'semrs-blocks' ), 'pills' => __( 'Pills', 'semrs-blocks' ), 'boxed' => __( 'Boxed', 'semrs-blocks' ), 'vertical' => __( 'Vertical (tabs on the left)', 'semrs-blocks' ) ) ),
			'tabAlign' => array( 'type' => 'select', 'label' => __( 'Tab alignment', 'semrs-blocks' ), 'default' => 'start', 'options' => array( 'start' => __( 'Left', 'semrs-blocks' ), 'center' => __( 'Centre', 'semrs-blocks' ), 'stretch' => __( 'Full width', 'semrs-blocks' ) ) ),
			'accent'   => array( 'type' => 'color', 'label' => __( 'Active tab colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);
	$blocks['semrs/tab'] = array(
		'title'    => __( 'Tab', 'semrs-blocks' ),
		'icon'     => 'index-card',
		'render'   => 'semrs_blocks_render_tab',
		'inner'    => true,
		'parent'   => array( 'semrs/tabs' ),
		'controls' => array(
			'title' => array( 'type' => 'text', 'label' => __( 'Tab title', 'semrs-blocks' ), 'default' => 'Tab' ),
			'icon'  => array( 'type' => 'icon', 'label' => __( 'Icon (optional)', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/accordion'] = array(
		'title'       => __( 'Accordion / FAQ', 'semrs-blocks' ),
		'description' => __( 'Questions and answers that open and close. Builds FAQ schema with SEMRS Core SEO.', 'semrs-blocks' ),
		'icon'        => 'list-view',
		'keywords'    => array( 'accordion', 'faq', 'questions', 'toggle', 'collapse', 'details' ),
		'render'      => 'semrs_blocks_render_accordion',
		'inner'       => true,
		'allowed'     => array( 'semrs/accordion-item' ),
		'template'    => array( array( 'semrs/accordion-item', array( 'title' => 'First question?' ) ), array( 'semrs/accordion-item', array( 'title' => 'Second question?' ) ) ),
		'controls'    => array(
			'style'     => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'cards', 'options' => array( 'cards' => __( 'Cards', 'semrs-blocks' ), 'lines' => __( 'Lines', 'semrs-blocks' ), 'minimal' => __( 'Minimal', 'semrs-blocks' ) ) ),
			'single'    => array( 'type' => 'toggle', 'label' => __( 'Only one open at a time', 'semrs-blocks' ), 'default' => false ),
			'iconStyle' => array( 'type' => 'select', 'label' => __( 'Open/close icon', 'semrs-blocks' ), 'default' => 'plus', 'options' => array( 'plus' => __( 'Plus / minus', 'semrs-blocks' ), 'chevron' => __( 'Arrow', 'semrs-blocks' ) ) ),
			'faqSchema' => array( 'type' => 'toggle', 'label' => __( 'Use as FAQ schema (SEMRS Core SEO)', 'semrs-blocks' ), 'default' => true, 'help' => __( 'Only for real questions and answers that are visible on the page.', 'semrs-blocks' ) ),
		),
	);
	$blocks['semrs/accordion-item'] = array(
		'title'    => __( 'Accordion item', 'semrs-blocks' ),
		'icon'     => 'list-view',
		'render'   => 'semrs_blocks_render_accordion_item',
		'inner'    => true,
		'parent'   => array( 'semrs/accordion' ),
		'controls' => array(
			'title' => array( 'type' => 'text', 'label' => __( 'Question / title', 'semrs-blocks' ), 'default' => 'Question?' ),
			'open'  => array( 'type' => 'toggle', 'label' => __( 'Open when the page loads', 'semrs-blocks' ), 'default' => false ),
		),
	);

	$blocks['semrs/gallery'] = array(
		'title'    => __( 'Gallery & Lightbox', 'semrs-blocks' ),
		'icon'     => 'format-gallery',
		'keywords' => array( 'gallery', 'images', 'lightbox', 'masonry', 'photos', 'portfolio' ),
		'render'   => 'semrs_blocks_render_gallery',
		'controls' => array(
			'images'   => array( 'type' => 'gallery', 'label' => __( 'Images', 'semrs-blocks' ) ),
			'layout'   => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'grid', 'options' => array( 'grid' => __( 'Grid (same size)', 'semrs-blocks' ), 'masonry' => __( 'Masonry', 'semrs-blocks' ) ) ),
			'columns'  => array( 'type' => 'range', 'label' => __( 'Columns (desktop)', 'semrs-blocks' ), 'default' => 3, 'min' => 1, 'max' => 6 ),
			'gap'      => array( 'type' => 'range', 'label' => __( 'Gap (px)', 'semrs-blocks' ), 'default' => 12, 'min' => 0, 'max' => 48 ),
			'ratio'    => array( 'type' => 'select', 'label' => __( 'Image shape (grid)', 'semrs-blocks' ), 'default' => '4/3', 'options' => array( '1/1' => __( 'Square', 'semrs-blocks' ), '4/3' => '4:3', '16/9' => '16:9', '3/4' => __( 'Portrait 3:4', 'semrs-blocks' ) ) ),
			'lightbox' => array( 'type' => 'toggle', 'label' => __( 'Open images in a lightbox', 'semrs-blocks' ), 'default' => true ),
			'captions' => array( 'type' => 'toggle', 'label' => __( 'Show captions', 'semrs-blocks' ), 'default' => false ),
			'radius'   => array( 'type' => 'range', 'label' => __( 'Rounded corners (px)', 'semrs-blocks' ), 'default' => 8, 'min' => 0, 'max' => 32 ),
		),
	);

	$blocks['semrs/testimonial'] = array(
		'title'       => __( 'Testimonial', 'semrs-blocks' ),
		'description' => __( 'A real customer quote with name, role and photo. Use only genuine testimonials you have permission to publish.', 'semrs-blocks' ),
		'icon'        => 'format-quote',
		'keywords'    => array( 'testimonial', 'review', 'quote', 'client', 'feedback' ),
		'render'      => 'semrs_blocks_render_testimonial',
		'controls'    => array(
			'quote'   => array( 'type' => 'textarea', 'label' => __( 'Quote (real words from a real customer)', 'semrs-blocks' ), 'default' => '' ),
			'name'    => array( 'type' => 'text', 'label' => __( 'Name', 'semrs-blocks' ), 'default' => '' ),
			'role'    => array( 'type' => 'text', 'label' => __( 'Role and company', 'semrs-blocks' ), 'default' => '' ),
			'photo'   => array( 'type' => 'media', 'label' => __( 'Photo (with permission)', 'semrs-blocks' ), 'default' => 0 ),
			'rating'  => array( 'type' => 'range', 'label' => __( 'Star rating (0 = hide)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 5 ),
			'source'  => array( 'type' => 'url', 'label' => __( 'Link to the original review (e.g. Google)', 'semrs-blocks' ), 'default' => '' ),
			'style'   => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'card', 'options' => array( 'card' => __( 'Card', 'semrs-blocks' ), 'centered' => __( 'Centred', 'semrs-blocks' ), 'quote' => __( 'Big quote mark', 'semrs-blocks' ) ) ),
		),
	);

	$blocks['semrs/toc'] = array(
		'title'       => __( 'Table of Contents', 'semrs-blocks' ),
		'description' => __( 'Automatic list of the headings on this page, with jump links.', 'semrs-blocks' ),
		'icon'        => 'list-view',
		'keywords'    => array( 'toc', 'table of contents', 'contents', 'headings', 'jump links', 'index' ),
		'render'      => 'semrs_blocks_render_toc',
		'controls'    => array(
			'title'     => array( 'type' => 'text', 'label' => __( 'Title', 'semrs-blocks' ), 'default' => 'On this page' ),
			'levels'    => array( 'type' => 'select', 'label' => __( 'Headings to include', 'semrs-blocks' ), 'default' => '23', 'options' => array( '2' => 'H2', '23' => 'H2 + H3', '234' => 'H2 + H3 + H4' ) ),
			'numbered'  => array( 'type' => 'toggle', 'label' => __( 'Numbered list', 'semrs-blocks' ), 'default' => true ),
			'collapsed' => array( 'type' => 'toggle', 'label' => __( 'Start closed', 'semrs-blocks' ), 'default' => false ),
		),
	);
	unset( $hide );
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_blocks_layout', 6 );

/**
 * Container.
 *
 * @param array    $a       Attributes.
 * @param WP_Block $block   Block.
 * @param string   $content Inner blocks HTML.
 * @return string
 */
function semrs_blocks_render_container( $a, $block = null, $content = '' ) {
	$tag       = in_array( semrs_blocks_a( $a, 'tag', 'div' ), array( 'div', 'section', 'header', 'footer', 'aside', 'article' ), true ) ? $a['tag'] : 'div';
	$gradients = array(
		'navy'    => 'linear-gradient(135deg,#0b1f3a,#1e3a66)',
		'sunrise' => 'linear-gradient(135deg,#fbf4e0,#f7d9a8)',
		'ocean'   => 'linear-gradient(135deg,#0b4f6c,#20a4c8)',
		'mint'    => 'linear-gradient(135deg,#e6f6ef,#c9eedd)',
		'soft'    => 'linear-gradient(180deg,#f6f7f9,#ffffff)',
	);
	$img       = (int) semrs_blocks_a( $a, 'image', 0 ) ? wp_get_attachment_image_url( (int) $a['image'], 'full' ) : '';
	if ( ! $img && preg_match( '#^https?://#i', (string) semrs_blocks_a( $a, 'imageUrl', '' ) ) ) {
		$img = (string) $a['imageUrl']; // Template photos before they are in the Media Library.
	}
	$layers    = array();
	if ( $img ) {
		$layers[] = 'url(' . esc_url_raw( $img ) . ') center/cover no-repeat';
	}
	$grad = $gradients[ semrs_blocks_a( $a, 'gradient' ) ] ?? '';
	if ( $grad ) {
		$layers[] = $grad;
	}
	$style = array(
		'--c-gap'      => (int) semrs_blocks_a( $a, 'gap', 24 ) . 'px',
		'--c-cols'     => max( 1, min( 6, (int) semrs_blocks_a( $a, 'columns', 3 ) ) ),
		'--c-cols-t'   => max( 1, min( 4, (int) semrs_blocks_a( $a, 'columnsTablet', 2 ) ) ),
		'padding-top'  => (int) semrs_blocks_a( $a, 'padTop', 0 ) ? (int) $a['padTop'] . 'px' : '',
		'padding-bottom' => (int) semrs_blocks_a( $a, 'padBottom', 0 ) ? (int) $a['padBottom'] . 'px' : '',
		'--c-pad-x'    => (int) semrs_blocks_a( $a, 'padSides', 0 ) ? (int) $a['padSides'] . 'px' : '',
		'min-height'   => (int) semrs_blocks_a( $a, 'minHeight', 0 ) ? (int) $a['minHeight'] . 'px' : '',
		'border-radius' => (int) semrs_blocks_a( $a, 'radius', 0 ) ? (int) $a['radius'] . 'px' : '',
		'background-color' => semrs_blocks_color( semrs_blocks_a( $a, 'bg' ) ),
		'background'   => $layers ? implode( ',', $layers ) : '',
		'color'        => semrs_blocks_color( semrs_blocks_a( $a, 'textColor' ) ),
	);
	if ( ! empty( $style['background'] ) && $style['background-color'] ) {
		$style['background'] .= ',' . $style['background-color'];
		$style['background-color'] = '';
	}
	$overlay = '';
	if ( $img && semrs_blocks_color( semrs_blocks_a( $a, 'overlay' ) ) ) {
		$overlay = '<span class="semrs-container__overlay" aria-hidden="true"' . semrs_blocks_style( array( 'background' => semrs_blocks_color( $a['overlay'] ), 'opacity' => max( 0, min( 100, (int) semrs_blocks_a( $a, 'overlayOpacity', 50 ) ) ) / 100 ) ) . '></span>';
	}
	$classes = array(
		'semrs-container',
		'semrs-container--' . sanitize_html_class( semrs_blocks_a( $a, 'layout', 'stack' ) ),
		'semrs-container--w-' . sanitize_html_class( semrs_blocks_a( $a, 'width', 'container' ) ),
		'semrs-container--v-' . sanitize_html_class( semrs_blocks_a( $a, 'valign', 'stretch' ) ),
		'semrs-container--h-' . sanitize_html_class( semrs_blocks_a( $a, 'justify', 'start' ) ),
	);
	if ( $img && ! empty( $a['fixed'] ) ) {
		$classes[] = 'semrs-container--fixed';
	}
	if ( $overlay ) {
		$classes[] = 'has-overlay';
	}
	if ( $style['color'] ) {
		$classes[] = 'semrs-has-color';
	}
	return sprintf( '<%1$s class="%2$s"%3$s>%4$s<div class="semrs-container__inner">%5$s</div></%1$s>', $tag, esc_attr( implode( ' ', $classes ) ), semrs_blocks_style( $style ), $overlay, $content );
}

/**
 * Tabs: tab buttons are built from the child Tab titles; panels are the
 * children. Without JavaScript every panel is shown with its title.
 *
 * @param array    $a       Attributes.
 * @param WP_Block $block   Block.
 * @param string   $content Inner HTML (panels).
 * @return string
 */
function semrs_blocks_render_tabs( $a, $block = null, $content = '' ) {
	static $n = 0;
	$n++;
	$id   = 'semrs-tabs-' . $n;
	$tabs = '';
	$kids = $block instanceof WP_Block ? $block->parsed_block['innerBlocks'] : array();
	foreach ( $kids as $i => $kid ) {
		$t     = $kid['attrs']['title'] ?? 'Tab';
		$icon  = ! empty( $kid['attrs']['icon'] ) ? semrs_blocks_icon_svg( $kid['attrs']['icon'], 18 ) : '';
		$tabs .= sprintf( '<button type="button" role="tab" id="%1$s-t%2$d" aria-controls="%1$s-p%2$d" aria-selected="%3$s" tabindex="%4$s">%5$s<span>%6$s</span></button>', esc_attr( $id ), $i, 0 === $i ? 'true' : 'false', 0 === $i ? '0' : '-1', $icon, esc_html( $t ) );
	}
	semrs_blocks_script( 'semrs-blocks-tabs', 'tabs.js' );
	return sprintf(
		'<div class="semrs-tabs semrs-tabs--%1$s semrs-tabs--a-%2$s" id="%3$s"%4$s><div class="semrs-tabs__list" role="tablist">%5$s</div><div class="semrs-tabs__panels">%6$s</div></div>',
		esc_attr( semrs_blocks_a( $a, 'style', 'underline' ) ),
		esc_attr( semrs_blocks_a( $a, 'tabAlign', 'start' ) ),
		esc_attr( $id ),
		semrs_blocks_style( array( '--tab-accent' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ),
		$tabs,
		$content
	);
}

/**
 * One tab panel.
 *
 * @param array    $a       Attributes.
 * @param WP_Block $block   Block.
 * @param string   $content Inner HTML.
 * @return string
 */
function semrs_blocks_render_tab( $a, $block = null, $content = '' ) {
	return '<div class="semrs-tab" role="tabpanel"><p class="semrs-tab__title">' . esc_html( semrs_blocks_a( $a, 'title', 'Tab' ) ) . '</p>' . $content . '</div>';
}

/**
 * Accordion wrapper.
 *
 * @param array    $a       Attributes.
 * @param WP_Block $block   Block.
 * @param string   $content Inner HTML (items).
 * @return string
 */
function semrs_blocks_render_accordion( $a, $block = null, $content = '' ) {
	if ( ! empty( $a['single'] ) ) {
		semrs_blocks_script( 'semrs-blocks-accordion', 'accordion.js' );
	}
	return sprintf(
		'<div class="semrs-acc semrs-acc--%1$s semrs-acc--i-%2$s"%3$s>%4$s</div>',
		esc_attr( semrs_blocks_a( $a, 'style', 'cards' ) ),
		esc_attr( semrs_blocks_a( $a, 'iconStyle', 'plus' ) ),
		! empty( $a['single'] ) ? ' data-single="1"' : '',
		$content
	);
}

/**
 * Accordion item: native <details>, works without JavaScript.
 *
 * @param array    $a       Attributes.
 * @param WP_Block $block   Block.
 * @param string   $content Inner HTML.
 * @return string
 */
function semrs_blocks_render_accordion_item( $a, $block = null, $content = '' ) {
	return sprintf(
		'<details class="semrs-acc__item"%1$s><summary>%2$s</summary><div class="semrs-acc__body">%3$s</div></details>',
		! empty( $a['open'] ) ? ' open' : '',
		esc_html( semrs_blocks_a( $a, 'title', 'Question?' ) ),
		$content
	);
}

/**
 * Gallery with optional lightbox.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_gallery( $a ) {
	$ids = array_filter( array_map( 'intval', (array) semrs_blocks_a( $a, 'images', array() ) ) );
	if ( ! $ids ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Choose images in the block settings.', 'semrs-blocks' ) . '</p>' : '';
	}
	$lightbox = ! empty( $a['lightbox'] );
	if ( $lightbox ) {
		semrs_blocks_script( 'semrs-blocks-lightbox', 'lightbox.js' );
	}
	$items = '';
	foreach ( $ids as $i => $id ) {
		$img = wp_get_attachment_image( $id, 'large', false, array( 'loading' => $i < 3 ? 'eager' : 'lazy' ) );
		if ( ! $img ) {
			continue;
		}
		$cap  = ! empty( $a['captions'] ) ? wp_get_attachment_caption( $id ) : '';
		$full = wp_get_attachment_image_url( $id, 'full' );
		$alt  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		$body = $lightbox && $full ? sprintf( '<a href="%1$s" class="semrs-gallery__link" data-caption="%2$s">%3$s</a>', esc_url( $full ), esc_attr( $cap ? $cap : $alt ), $img ) : $img;
		$items .= '<figure class="semrs-gallery__item">' . $body . ( $cap ? '<figcaption>' . esc_html( $cap ) . '</figcaption>' : '' ) . '</figure>';
	}
	return sprintf(
		'<div class="semrs-gallery semrs-gallery--%1$s"%2$s>%3$s</div>',
		esc_attr( semrs_blocks_a( $a, 'layout', 'grid' ) ),
		semrs_blocks_style(
			array(
				'--g-cols'   => max( 1, min( 6, (int) semrs_blocks_a( $a, 'columns', 3 ) ) ),
				'--g-gap'    => (int) semrs_blocks_a( $a, 'gap', 12 ) . 'px',
				'--g-ratio'  => in_array( semrs_blocks_a( $a, 'ratio', '4/3' ), array( '1/1', '4/3', '16/9', '3/4' ), true ) ? $a['ratio'] : '4/3',
				'--g-radius' => (int) semrs_blocks_a( $a, 'radius', 8 ) . 'px',
			)
		),
		$items
	);
}

/**
 * Testimonial. No Review schema: Google does not show stars for reviews a
 * business publishes about itself, and fake markup breaks its guidelines.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_testimonial( $a ) {
	$quote = trim( (string) semrs_blocks_a( $a, 'quote' ) );
	if ( '' === $quote ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add a real customer quote in the block settings.', 'semrs-blocks' ) . '</p>' : '';
	}
	$rating = max( 0, min( 5, (int) semrs_blocks_a( $a, 'rating', 0 ) ) );
	$stars  = '';
	if ( $rating ) {
		/* translators: %d: rating out of 5 */
		$stars = '<p class="semrs-testi__stars" aria-label="' . esc_attr( sprintf( __( '%d out of 5 stars', 'semrs-blocks' ), $rating ) ) . '">' . str_repeat( '<span aria-hidden="true">★</span>', $rating ) . str_repeat( '<span class="is-off" aria-hidden="true">★</span>', 5 - $rating ) . '</p>';
	}
	$photo  = (int) semrs_blocks_a( $a, 'photo', 0 ) ? wp_get_attachment_image( (int) $a['photo'], 'thumbnail', false, array( 'class' => 'semrs-testi__photo', 'loading' => 'lazy', 'alt' => '' ) ) : '';
	$source = semrs_blocks_a( $a, 'source' );
	return sprintf(
		'<figure class="semrs-testi semrs-testi--%1$s">%2$s<blockquote><p>%3$s</p></blockquote><figcaption>%4$s<span class="semrs-testi__who"><strong>%5$s</strong>%6$s%7$s</span></figcaption></figure>',
		esc_attr( semrs_blocks_a( $a, 'style', 'card' ) ),
		$stars,
		nl2br( esc_html( $quote ) ),
		$photo,
		esc_html( semrs_blocks_a( $a, 'name' ) ),
		semrs_blocks_a( $a, 'role' ) ? '<span>' . esc_html( $a['role'] ) . '</span>' : '',
		$source ? '<a href="' . esc_url( $source ) . '" target="_blank" rel="noopener nofollow">' . esc_html__( 'Read the original review', 'semrs-blocks' ) . '</a>' : ''
	);
}

/**
 * Headings of a post for the table of contents: [level, text, id].
 * The same ids are added to the headings when the page renders.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function semrs_blocks_toc_headings( $post_id ) {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	$out  = array();
	$used = array();
	$walk = function ( $blocks ) use ( &$walk, &$out, &$used ) {
		foreach ( $blocks as $b ) {
			if ( 'core/heading' === $b['blockName'] ) {
				$level = (int) ( $b['attrs']['level'] ?? 2 );
				$text  = trim( wp_strip_all_tags( $b['innerHTML'] ) );
				if ( '' === $text ) {
					continue;
				}
				if ( ! empty( $b['attrs']['anchor'] ) ) {
					$id = $b['attrs']['anchor'];
				} elseif ( preg_match( '/\sid="([^"]+)"/', $b['innerHTML'], $m ) ) {
					$id = $m[1];
				} else {
					$base = sanitize_title( $text );
					$base = '' === $base ? 'section' : $base;
					$id   = $base;
					for ( $i = 2; isset( $used[ $id ] ); $i++ ) {
						$id = $base . '-' . $i;
					}
				}
				$used[ $id ] = true;
				$out[]       = array( $level, $text, $id );
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$post = get_post( $post_id );
	if ( $post ) {
		$walk( parse_blocks( $post->post_content ) );
	}
	$cache[ $post_id ] = $out;
	return $out;
}

/**
 * Table of contents.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_toc( $a ) {
	$post_id = get_the_ID() ? get_the_ID() : ( isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$levels  = str_split( preg_replace( '/[^2-4]/', '', (string) semrs_blocks_a( $a, 'levels', '23' ) ) );
	$items   = array_filter(
		semrs_blocks_toc_headings( (int) $post_id ),
		function ( $h ) use ( $levels ) {
			return in_array( (string) $h[0], $levels, true );
		}
	);
	if ( ! $items ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add headings (H2, H3) to this page; they appear here automatically.', 'semrs-blocks' ) . '</p>' : '';
	}
	$min  = min( array_map( function ( $h ) { return $h[0]; }, $items ) );
	$list = '';
	foreach ( $items as $h ) {
		$list .= sprintf( '<li class="semrs-toc__l%1$d"><a href="#%2$s">%3$s</a></li>', (int) ( $h[0] - $min + 1 ), esc_attr( $h[2] ), esc_html( $h[1] ) );
	}
	$tag = ! empty( $a['numbered'] ) ? 'ol' : 'ul';
	return sprintf(
		'<nav class="semrs-toc" aria-label="%1$s"><details%2$s><summary>%3$s</summary><%4$s>%5$s</%4$s></details></nav>',
		esc_attr( semrs_blocks_a( $a, 'title', 'On this page' ) ),
		empty( $a['collapsed'] ) ? ' open' : '',
		esc_html( semrs_blocks_a( $a, 'title', 'On this page' ) ),
		$tag,
		$list
	);
}

/**
 * Give headings the ids the table of contents links to (only on pages that
 * contain a Table of Contents block, and only when the heading has none).
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function semrs_blocks_toc_heading_ids( $html, $block ) {
	static $taken = array();
	if ( 'core/heading' !== $block['blockName'] || is_admin() || ! semrs_blocks_page_output() ) {
		return $html;
	}
	$post_id = get_the_ID();
	if ( ! $post_id || ! has_block( 'semrs/toc', $post_id ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag() || $p->get_attribute( 'id' ) ) {
		return $html;
	}
	$text = trim( wp_strip_all_tags( $html ) );
	foreach ( semrs_blocks_toc_headings( $post_id ) as $i => $h ) {
		$key = $post_id . ':' . $i;
		if ( $h[1] === $text && empty( $taken[ $key ] ) ) {
			$taken[ $key ] = true;
			$p->set_attribute( 'id', $h[2] );
			return $p->get_updated_html();
		}
	}
	return $html;
}
add_filter( 'render_block', 'semrs_blocks_toc_heading_ids', 10, 2 );
