<?php
/**
 * Website Showcase (free): a card grid like the WordPress.org theme
 * directory. Each card shows a large preview image with a Desktop / Mobile
 * switch, a name, a badge and buttons. For themes, templates, portfolios
 * and client websites.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Definitions.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_blocks_showcase( $blocks ) {
	$blocks['semrs/showcase'] = array(
		'title'       => __( 'Website Showcase', 'semrs-blocks' ),
		'description' => __( 'Cards with desktop and mobile previews, like the WordPress.org theme directory. For themes, templates, portfolios and client sites.', 'semrs-blocks' ),
		'icon'        => 'desktop',
		'keywords'    => array( 'showcase', 'portfolio', 'themes', 'templates', 'preview', 'mockup', 'websites' ),
		'render'      => 'semrs_blocks_render_showcase',
		'inner'       => true,
		'allowed'     => array( 'semrs/showcase-item' ),
		'template'    => array( array( 'semrs/showcase-item', array( 'title' => 'Website one' ) ), array( 'semrs/showcase-item', array( 'title' => 'Website two' ) ), array( 'semrs/showcase-item', array( 'title' => 'Website three' ) ) ),
		'controls'    => array(
			'columns' => array( 'type' => 'range', 'label' => __( 'Columns (desktop)', 'semrs-blocks' ), 'default' => 3, 'min' => 1, 'max' => 4 ),
			'ratio'   => array( 'type' => 'select', 'label' => __( 'Preview shape', 'semrs-blocks' ), 'default' => '4/3', 'options' => array( '4/3' => __( '4:3 (like WordPress.org)', 'semrs-blocks' ), '16/10' => '16:10', '1/1' => __( 'Square', 'semrs-blocks' ) ) ),
			'toggle'  => array( 'type' => 'toggle', 'label' => __( 'Show the Desktop / Mobile switch', 'semrs-blocks' ), 'default' => true ),
		),
	);
	$blocks['semrs/showcase-item'] = array(
		'title'    => __( 'Showcase card', 'semrs-blocks' ),
		'icon'     => 'desktop',
		'render'   => 'semrs_blocks_render_showcase_item',
		'parent'   => array( 'semrs/showcase' ),
		'panels'   => array(
			__( 'Card', 'semrs-blocks' )    => array( 'title', 'badge', 'text' ),
			__( 'Previews', 'semrs-blocks' ) => array( 'desktop', 'mobile', 'alt' ),
			__( 'Buttons', 'semrs-blocks' ) => array( 'url', 'button', 'url2', 'button2' ),
		),
		'controls' => array(
			'title'   => array( 'type' => 'text', 'label' => __( 'Name', 'semrs-blocks' ), 'default' => 'Website' ),
			'badge'   => array( 'type' => 'select', 'label' => __( 'Badge', 'semrs-blocks' ), 'default' => '', 'options' => array( '' => __( 'None', 'semrs-blocks' ), 'free' => __( 'Free', 'semrs-blocks' ), 'pro' => __( 'Pro', 'semrs-blocks' ), 'new' => __( 'New', 'semrs-blocks' ), 'installed' => __( 'Installed', 'semrs-blocks' ) ) ),
			'text'    => array( 'type' => 'textarea', 'label' => __( 'Short description (optional)', 'semrs-blocks' ), 'default' => '' ),
			'desktop' => array( 'type' => 'media', 'label' => __( 'Desktop preview image', 'semrs-blocks' ), 'default' => 0 ),
			'mobile'  => array( 'type' => 'media', 'label' => __( 'Mobile preview image (optional)', 'semrs-blocks' ), 'default' => 0 ),
			'alt'     => array( 'type' => 'text', 'label' => __( 'Image description (alt text)', 'semrs-blocks' ), 'default' => '' ),
			'url'     => array( 'type' => 'url', 'label' => __( 'Main button link (preview / demo)', 'semrs-blocks' ), 'default' => '' ),
			'button'  => array( 'type' => 'text', 'label' => __( 'Main button text', 'semrs-blocks' ), 'default' => 'Preview' ),
			'url2'    => array( 'type' => 'url', 'label' => __( 'Second button link (download / details)', 'semrs-blocks' ), 'default' => '' ),
			'button2' => array( 'type' => 'text', 'label' => __( 'Second button text', 'semrs-blocks' ), 'default' => 'Details' ),
		),
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_blocks_showcase', 7 );

/**
 * Grid.
 *
 * @param array    $a       Attributes.
 * @param WP_Block $block   Block.
 * @param string   $content Cards.
 * @return string
 */
function semrs_blocks_render_showcase( $a, $block = null, $content = '' ) {
	if ( ! empty( $a['toggle'] ) ) {
		semrs_blocks_script( 'semrs-blocks-showcase', 'showcase.js' );
	}
	$ratio = in_array( semrs_blocks_a( $a, 'ratio', '4/3' ), array( '4/3', '16/10', '1/1' ), true ) ? $a['ratio'] : '4/3';
	return sprintf(
		'<div class="semrs-showcase%1$s"%2$s>%3$s</div>',
		empty( $a['toggle'] ) ? ' no-toggle' : '',
		semrs_blocks_style( array( '--sc-cols' => max( 1, min( 4, (int) semrs_blocks_a( $a, 'columns', 3 ) ) ), '--sc-ratio' => $ratio ) ),
		$content
	);
}

/**
 * One card.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_showcase_item( $a ) {
	$title = semrs_blocks_a( $a, 'title', 'Website' );
	$alt   = semrs_blocks_a( $a, 'alt', '' );
	/* translators: %s: website or theme name */
	$alt   = $alt ? $alt : sprintf( __( '%s preview', 'semrs-blocks' ), $title );
	$desk  = (int) semrs_blocks_a( $a, 'desktop', 0 ) ? wp_get_attachment_image( (int) $a['desktop'], 'large', false, array( 'alt' => $alt, 'loading' => 'lazy', 'class' => 'semrs-show__img', 'sizes' => '(min-width: 1024px) 420px, (min-width: 640px) 50vw, 100vw' ) ) : '';
	$mob   = (int) semrs_blocks_a( $a, 'mobile', 0 ) ? wp_get_attachment_image( (int) $a['mobile'], 'medium_large', false, array( 'alt' => $alt . ' – ' . __( 'mobile', 'semrs-blocks' ), 'loading' => 'lazy', 'class' => 'semrs-show__img', 'sizes' => '200px' ) ) : '';
	if ( ! $desk ) {
		$desk = '<span class="semrs-show__placeholder">' . esc_html__( 'Choose a desktop preview image', 'semrs-blocks' ) . '</span>';
	}
	$badges = array( 'free' => __( 'Free', 'semrs-blocks' ), 'pro' => __( 'Pro', 'semrs-blocks' ), 'new' => __( 'New', 'semrs-blocks' ), 'installed' => __( 'Installed', 'semrs-blocks' ) );
	$badge  = semrs_blocks_a( $a, 'badge' );
	$toggle = $mob ? sprintf( '<div class="semrs-show__switch" role="group" aria-label="%1$s"><button type="button" aria-pressed="true" data-view="desktop">%2$s</button><button type="button" aria-pressed="false" data-view="mobile">%3$s</button></div>', esc_attr__( 'Preview size', 'semrs-blocks' ), esc_html__( 'Desktop', 'semrs-blocks' ), esc_html__( 'Mobile', 'semrs-blocks' ) ) : '';
	$btns   = '';
	if ( semrs_blocks_a( $a, 'url' ) ) {
		$btns .= '<a class="semrs-btn" href="' . esc_url( $a['url'] ) . '">' . esc_html( semrs_blocks_a( $a, 'button', 'Preview' ) ) . '</a>';
	}
	if ( semrs_blocks_a( $a, 'url2' ) ) {
		$btns .= '<a class="semrs-btn semrs-btn--ghost" href="' . esc_url( $a['url2'] ) . '">' . esc_html( semrs_blocks_a( $a, 'button2', 'Details' ) ) . '</a>';
	}
	return sprintf(
		'<article class="semrs-show"><div class="semrs-show__media"><div class="semrs-show__desk">%1$s</div>%2$s%5$s</div><div class="semrs-show__bar"><h3 class="semrs-show__title">%3$s</h3>%4$s</div>%6$s%7$s</article>',
		$desk,
		$mob ? '<div class="semrs-show__phone" hidden><div class="semrs-show__frame">' . $mob . '</div></div>' : '',
		esc_html( $title ),
		$badge && isset( $badges[ $badge ] ) ? '<span class="semrs-show__badge semrs-show__badge--' . esc_attr( $badge ) . '">' . esc_html( $badges[ $badge ] ) . '</span>' : '',
		$toggle,
		semrs_blocks_a( $a, 'text' ) ? '<p class="semrs-show__text">' . esc_html( $a['text'] ) . '</p>' : '',
		$btns ? '<div class="semrs-show__btns">' . $btns . '</div>' : ''
	);
}
