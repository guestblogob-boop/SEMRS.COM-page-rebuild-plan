<?php
/**
 * Banner Slider: auto-rotating promo banners (heading, text, button, image,
 * background) like the top of a marketplace shop. Free; SEMRS Pro's Slider
 * offers the same banners as "Slides from: Banners".
 *
 * Accessible: pauses on hover, focus and when the tab is hidden, has a
 * pause button, respects "reduce motion", and every slide is a labelled
 * group. Fast: the first image loads eagerly with high priority (it is often
 * the LCP), the rest lazily, and the box keeps its height so nothing shifts.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block definition.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_blocks_banners_def( $blocks ) {
	$blocks['semrs/banner-slider'] = array(
		'title'       => __( 'Banner Slider', 'semrs-blocks' ),
		'description' => __( 'Auto-rotating promo banners with heading, text, button and image, for the top of a shop or landing page.', 'semrs-blocks' ),
		'icon'        => 'slides',
		'keywords'    => array( 'slider', 'banner', 'carousel', 'hero', 'promo', 'sale', 'shop' ),
		'render'      => 'semrs_blocks_render_banners',
		'panels'      => array(
			__( 'Slides', 'semrs-blocks' ) => array( 'slides' ),
			__( 'Display', 'semrs-blocks' ) => array( 'layout', 'height', 'heightMobile', 'textColor', 'arrows', 'dots' ),
			__( 'Motion', 'semrs-blocks' )  => array( 'autoplay', 'interval' ),
		),
		'controls'    => array(
			'slides'       => array(
				'type'    => 'textarea',
				'label'   => __( 'One slide per line: Heading | Text | Button text | Button link | Image URL | Background colour', 'semrs-blocks' ),
				'default' => "Holiday prints are here | Ready to download in minutes | Shop now | /shop/ |  | #fde7d9\nNew patterns every week | Clear steps for every skill level | See what is new | /shop/ |  | #e3efe6\nMade just for you | Personalised gifts with names and dates | Start yours | /shop/ |  | #e6e9f7",
				'help'    => __( 'Leave a part empty to skip it. The image shows beside the text (or behind it with the "Image behind text" layout).', 'semrs-blocks' ),
			),
			'layout'       => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'split', 'options' => array( 'split' => __( 'Text beside image', 'semrs-blocks' ), 'cover' => __( 'Image behind text', 'semrs-blocks' ), 'text' => __( 'Text only', 'semrs-blocks' ) ) ),
			'height'       => array( 'type' => 'range', 'label' => __( 'Height on desktop (px)', 'semrs-blocks' ), 'default' => 360, 'min' => 200, 'max' => 700 ),
			'heightMobile' => array( 'type' => 'range', 'label' => __( 'Height on phones (px)', 'semrs-blocks' ), 'default' => 420, 'min' => 200, 'max' => 800 ),
			'textColor'    => array( 'type' => 'color', 'label' => __( 'Text colour', 'semrs-blocks' ), 'default' => '' ),
			'arrows'       => array( 'type' => 'toggle', 'label' => __( 'Arrows', 'semrs-blocks' ), 'default' => true ),
			'dots'         => array( 'type' => 'toggle', 'label' => __( 'Dots', 'semrs-blocks' ), 'default' => true ),
			'autoplay'     => array( 'type' => 'toggle', 'label' => __( 'Rotate automatically (pauses on hover and focus)', 'semrs-blocks' ), 'default' => true ),
			'interval'     => array( 'type' => 'range', 'label' => __( 'Seconds per slide', 'semrs-blocks' ), 'default' => 6, 'min' => 3, 'max' => 15 ),
		),
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_blocks_banners_def', 7 );

/**
 * Parse the slides textarea.
 *
 * @param string $text Lines.
 * @return array<int, array{h:string,t:string,b:string,u:string,i:string,bg:string}>
 */
function semrs_blocks_banner_slides( $text ) {
	$out = array();
	foreach ( semrs_blocks_lines( $text ) as $line ) {
		$p     = array_map( 'trim', explode( '|', $line ) ) + array( '', '', '', '', '', '' );
		$out[] = array( 'h' => $p[0], 't' => $p[1], 'b' => $p[2], 'u' => $p[3], 'i' => $p[4], 'bg' => semrs_blocks_color( $p[5] ) );
	}
	return array_slice( $out, 0, 8 );
}

/**
 * Banner slides markup (shared with the Pro Slider's "Banners" source).
 *
 * @param array  $slides Parsed slides.
 * @param string $layout split|cover|text.
 * @param bool   $first_is_lcp First slide's image loads eagerly.
 * @return string[] One HTML string per slide.
 */
function semrs_blocks_banner_items( $slides, $layout = 'split', $first_is_lcp = true ) {
	$items = array();
	foreach ( $slides as $n => $s ) {
		$img = '';
		if ( $s['i'] && 'text' !== $layout ) {
			$img = sprintf(
				'<img class="semrs-banner__img" src="%1$s" alt="" %2$s decoding="async" width="800" height="600">',
				esc_url( $s['i'] ),
				0 === $n && $first_is_lcp ? 'fetchpriority="high"' : 'loading="lazy"'
			);
		}
		$btn     = $s['b'] && $s['u'] ? '<a class="semrs-btn semrs-banner__btn" href="' . esc_url( $s['u'] ) . '">' . esc_html( $s['b'] ) . '</a>' : '';
		$text    = '<div class="semrs-banner__text">' . ( $s['h'] ? '<p class="semrs-banner__h">' . esc_html( $s['h'] ) . '</p>' : '' ) . ( $s['t'] ? '<p class="semrs-banner__t">' . esc_html( $s['t'] ) . '</p>' : '' ) . $btn . '</div>';
		$items[] = '<div class="semrs-banner__slide"' . ( $s['bg'] ? ' style="--bn-bg:' . esc_attr( $s['bg'] ) . '"' : '' ) . '>' . $text . $img . '</div>';
	}
	return $items;
}

/**
 * Banner slider CSS (printed once on pages that use it).
 *
 * @return string
 */
function semrs_blocks_banner_css() {
	return semrs_blocks_css_part(
		'banner-slider',
		'.semrs-banners{position:relative;overflow:hidden;border-radius:var(--b-radius);height:var(--bn-hm,420px);color:var(--bn-fg,var(--b-text))}'
		. '@media(min-width:782px){.semrs-banners{height:var(--bn-h,360px)}}'
		. '.semrs-banners__track{display:flex;height:100%;transition:transform .55s cubic-bezier(.2,.7,.2,1);outline-offset:-3px}'
		. '.semrs-banner__slide{flex:0 0 100%;min-width:0;height:100%;display:grid;grid-template-rows:auto 1fr;background:var(--bn-bg,var(--b-surface));position:relative;overflow:hidden}'
		. '@media(min-width:782px){.semrs-banners--split .semrs-banner__slide{grid-template-columns:1fr 1fr;grid-template-rows:1fr}}'
		. '.semrs-banner__text{display:flex;flex-direction:column;justify-content:center;gap:.6rem;padding:clamp(1.25rem,4vw,3rem);position:relative;z-index:1}'
		. '.semrs-banner__h{margin:0;font-size:clamp(1.5rem,3.4vw,2.6rem);font-weight:800;line-height:1.15;color:inherit}.semrs-banner__t{margin:0;font-size:clamp(1rem,1.6vw,1.2rem);opacity:.9}.semrs-banner__btn{align-self:flex-start;margin-top:.5rem}'
		. '.semrs-banner__img{width:100%;height:100%;min-height:0;object-fit:cover;display:block}'
		. '.semrs-banners--cover .semrs-banner__slide{grid-template-columns:1fr;grid-template-rows:1fr}.semrs-banners--cover .semrs-banner__img{position:absolute;inset:0}.semrs-banners--cover .semrs-banner__text{background:linear-gradient(90deg,rgba(0,0,0,.55),rgba(0,0,0,0) 75%);color:#fff;height:100%}'
		. '@media(min-width:782px){.semrs-banners--arrows .semrs-banner__text{padding-left:max(clamp(1.25rem,4vw,3rem),72px)}.semrs-banners--arrows.semrs-banners--cover .semrs-banner__text,.semrs-banners--arrows.semrs-banners--text .semrs-banner__text{padding-right:max(clamp(1.25rem,4vw,3rem),72px)}}'
		. '@media(max-width:781px){.semrs-banners__nav{display:none}}'
		. '.semrs-banners__nav{position:absolute;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;border:0;background:rgba(255,255,255,.92);color:#111;font-size:1.6rem;line-height:1;cursor:pointer;z-index:2;box-shadow:0 2px 8px rgba(0,0,0,.15)}.semrs-banners__prev{left:12px}.semrs-banners__next{right:12px}'
		. '.semrs-banners__bar{position:absolute;left:0;right:0;bottom:10px;display:flex;justify-content:center;align-items:center;gap:6px;z-index:2}'
		. '.semrs-banners__dot{width:24px;height:24px;padding:0;border:0;background:none;cursor:pointer;display:grid;place-items:center}.semrs-banners__dot::before{content:"";width:9px;height:9px;border-radius:50%;background:rgba(0,0,0,.3)}.semrs-banners__dot[aria-current="true"]::before{background:var(--b-primary);width:22px;border-radius:5px}'
		. '.semrs-banners__pause{width:28px;height:28px;border:0;border-radius:50%;background:rgba(255,255,255,.92);color:#111;font-size:.75rem;cursor:pointer}'
		. '@media(prefers-reduced-motion:reduce){.semrs-banners__track{transition:none}}'
	);
}

/**
 * Render.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_banners( $a ) {
	$slides = semrs_blocks_banner_slides( semrs_blocks_a( $a, 'slides', '' ) );
	if ( ! $slides ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add at least one slide (one per line).', 'semrs-blocks' ) . '</p>' : '';
	}
	$layout = in_array( semrs_blocks_a( $a, 'layout', 'split' ), array( 'split', 'cover', 'text' ), true ) ? $a['layout'] : 'split';
	$many   = count( $slides ) > 1;
	if ( $many ) {
		semrs_blocks_script( 'semrs-blocks-banners', 'banners.js' );
	}
	$items = '';
	foreach ( semrs_blocks_banner_items( $slides, $layout ) as $n => $html ) {
		/* translators: 1: slide number, 2: total */
		$items .= str_replace( '<div class="semrs-banner__slide"', '<div class="semrs-banner__slide" role="group" aria-roledescription="slide" aria-label="' . esc_attr( sprintf( __( '%1$d of %2$d', 'semrs-blocks' ), $n + 1, count( $slides ) ) ) . '"', $html );
	}
	return semrs_blocks_banner_css() . sprintf(
		'<div class="semrs-banners semrs-banners--%1$s%9$s" role="region" aria-roledescription="carousel" aria-label="%2$s" data-autoplay="%3$s" data-interval="%4$d"%5$s><div class="semrs-banners__track" tabindex="0">%6$s</div>%7$s%8$s</div>',
		esc_attr( $layout ),
		esc_attr__( 'Promotions', 'semrs-blocks' ),
		$many && ! empty( $a['autoplay'] ) ? '1' : '0',
		max( 3, min( 15, (int) semrs_blocks_a( $a, 'interval', 6 ) ) ) * 1000,
		semrs_blocks_style(
			array(
				'--bn-h'  => max( 200, min( 700, (int) semrs_blocks_a( $a, 'height', 360 ) ) ) . 'px',
				'--bn-hm' => max( 200, min( 800, (int) semrs_blocks_a( $a, 'heightMobile', 420 ) ) ) . 'px',
				'--bn-fg' => semrs_blocks_color( semrs_blocks_a( $a, 'textColor', '' ) ),
			)
		),
		$items,
		$many && ! empty( $a['arrows'] ) ? '<button type="button" class="semrs-banners__nav semrs-banners__prev" aria-label="' . esc_attr__( 'Previous slide', 'semrs-blocks' ) . '">‹</button><button type="button" class="semrs-banners__nav semrs-banners__next" aria-label="' . esc_attr__( 'Next slide', 'semrs-blocks' ) . '">›</button>' : '',
		$many && ( ! empty( $a['dots'] ) || ! empty( $a['autoplay'] ) ) ? '<div class="semrs-banners__bar"' . ( empty( $a['dots'] ) ? ' data-nodots' : '' ) . '></div>' : '',
		$many && ! empty( $a['arrows'] ) ? ' semrs-banners--arrows' : '' // Text keeps clear of the arrows.
	);
}
