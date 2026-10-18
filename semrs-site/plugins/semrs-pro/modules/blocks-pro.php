<?php
/**
 * Pro blocks for SEMRS Blocks: Slider / Carousel (images or posts),
 * Animated Counter, 3D Model Viewer, and a Scroll Animation option on
 * every block. Needs the free SEMRS Blocks plugin.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_blocks_registry' ) ) {
	add_action(
		'admin_notices',
		function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'SEMRS Pro blocks need the free SEMRS Blocks plugin. Install and activate it to use sliders, counters, 3D models and scroll animations.', 'semrs-pro' ) . '</p></div>';
			}
		}
	);
	return;
}

/**
 * Register Pro block definitions.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_pro_blocks( $blocks ) {
	$blocks['semrs/slider'] = array(
		'title'    => __( 'Slider / Carousel (Pro)', 'semrs-pro' ),
		'icon'     => 'slides',
		'tier'     => 'pro',
		'keywords' => array( 'slider', 'carousel', 'gallery', 'slideshow', 'posts' ),
		'render'   => 'semrs_pro_render_slider',
		'panels'   => array(
			__( 'Content', 'semrs-pro' )  => array( 'source', 'images', 'postType', 'category', 'count' ),
			__( 'Display', 'semrs-pro' )  => array( 'perView', 'height', 'showCaption', 'arrows', 'dots' ),
			__( 'Motion', 'semrs-pro' )   => array( 'autoplay', 'interval', 'loop' ),
		),
		'controls' => array(
			'source'      => array( 'type' => 'select', 'label' => __( 'Slides from', 'semrs-pro' ), 'default' => 'images', 'options' => array( 'images' => __( 'Images', 'semrs-pro' ), 'posts' => __( 'Posts (carousel)', 'semrs-pro' ) ) ),
			'images'      => array( 'type' => 'gallery', 'label' => __( 'Images', 'semrs-pro' ) ),
			'postType'    => array( 'type' => 'posttype', 'label' => __( 'Post type', 'semrs-pro' ), 'default' => 'post' ),
			'category'    => array( 'type' => 'category', 'label' => __( 'Category', 'semrs-pro' ), 'default' => '' ),
			'count'       => array( 'type' => 'range', 'label' => __( 'Number of posts', 'semrs-pro' ), 'default' => 6, 'min' => 2, 'max' => 20 ),
			'perView'     => array( 'type' => 'range', 'label' => __( 'Slides visible (desktop)', 'semrs-pro' ), 'default' => 1, 'min' => 1, 'max' => 4 ),
			'height'      => array( 'type' => 'range', 'label' => __( 'Height (px, 0 = auto)', 'semrs-pro' ), 'default' => 0, 'min' => 0, 'max' => 900 ),
			'showCaption' => array( 'type' => 'toggle', 'label' => __( 'Show captions / titles', 'semrs-pro' ), 'default' => true ),
			'arrows'      => array( 'type' => 'toggle', 'label' => __( 'Arrows', 'semrs-pro' ), 'default' => true ),
			'dots'        => array( 'type' => 'toggle', 'label' => __( 'Dots', 'semrs-pro' ), 'default' => true ),
			'autoplay'    => array( 'type' => 'toggle', 'label' => __( 'Autoplay (pauses on hover and focus)', 'semrs-pro' ), 'default' => false ),
			'interval'    => array( 'type' => 'range', 'label' => __( 'Seconds per slide', 'semrs-pro' ), 'default' => 5, 'min' => 2, 'max' => 15 ),
			'loop'        => array( 'type' => 'toggle', 'label' => __( 'Loop back to the first slide', 'semrs-pro' ), 'default' => true ),
		),
	);

	$blocks['semrs/counter'] = array(
		'title'    => __( 'Animated Counter (Pro)', 'semrs-pro' ),
		'icon'     => 'chart-bar',
		'tier'     => 'pro',
		'keywords' => array( 'counter', 'number', 'stats', 'statistics' ),
		'render'   => 'semrs_pro_render_counter',
		'controls' => array(
			'number'   => array( 'type' => 'number', 'label' => __( 'Number', 'semrs-pro' ), 'default' => 120 ),
			'prefix'   => array( 'type' => 'text', 'label' => __( 'Before (e.g. $)', 'semrs-pro' ), 'default' => '' ),
			'suffix'   => array( 'type' => 'text', 'label' => __( 'After (e.g. + or %)', 'semrs-pro' ), 'default' => '+' ),
			'label'    => array( 'type' => 'text', 'label' => __( 'Label', 'semrs-pro' ), 'default' => 'Pages optimized' ),
			'icon'     => array( 'type' => 'icon', 'label' => __( 'Icon', 'semrs-pro' ), 'default' => 'trending-up' ),
			'duration' => array( 'type' => 'range', 'label' => __( 'Animation length (seconds)', 'semrs-pro' ), 'default' => 2, 'min' => 1, 'max' => 6 ),
			'color'    => array( 'type' => 'color', 'label' => __( 'Number colour', 'semrs-pro' ), 'default' => '' ),
		),
	);

	$blocks['semrs/model-3d'] = array(
		'title'    => __( '3D Model Viewer (Pro)', 'semrs-pro' ),
		'icon'     => 'visibility',
		'tier'     => 'pro',
		'keywords' => array( '3d', 'model', 'glb', 'gltf', 'ar', 'product' ),
		'render'   => 'semrs_pro_render_model',
		'controls' => array(
			'src'        => array( 'type' => 'url', 'label' => __( '3D model file URL (.glb or .gltf)', 'semrs-pro' ), 'default' => '' ),
			'poster'     => array( 'type' => 'media', 'label' => __( 'Poster image (shown while loading)', 'semrs-pro' ), 'default' => 0 ),
			'alt'        => array( 'type' => 'text', 'label' => __( 'Description (for screen readers)', 'semrs-pro' ), 'default' => '3D model' ),
			'height'     => array( 'type' => 'range', 'label' => __( 'Height (px)', 'semrs-pro' ), 'default' => 420, 'min' => 200, 'max' => 900 ),
			'autoRotate' => array( 'type' => 'toggle', 'label' => __( 'Auto-rotate', 'semrs-pro' ), 'default' => true ),
			'controls'   => array( 'type' => 'toggle', 'label' => __( 'Let visitors rotate and zoom', 'semrs-pro' ), 'default' => true ),
			'ar'         => array( 'type' => 'toggle', 'label' => __( 'View in your room (AR on phones)', 'semrs-pro' ), 'default' => false ),
			'bg'         => array( 'type' => 'color', 'label' => __( 'Background', 'semrs-pro' ), 'default' => '' ),
		),
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_pro_blocks', 20 );

/**
 * Pro block CSS (inline once, like the free blocks).
 *
 * @return string
 */
function semrs_pro_blocks_css() {
	static $done = false;
	if ( $done || ! semrs_blocks_page_output() ) {
		return '';
	}
	$done = true;
	return '<style id="semrs-pro-blocks-css">' . wp_strip_all_tags( (string) file_get_contents( SEMRS_PRO_DIR . 'assets/pro-blocks.css' ) ) . '</style>'; // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Enqueue a Pro front-end script once.
 *
 * @param string $handle Handle.
 * @param string $file   File in assets/.
 */
function semrs_pro_blocks_script( $handle, $file ) {
	if ( ! wp_script_is( $handle, 'registered' ) ) {
		wp_register_script( $handle, SEMRS_PRO_URL . 'assets/' . $file, array(), SEMRS_PRO_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	wp_enqueue_script( $handle );
}

/**
 * Slider / carousel.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_slider( $a ) {
	$slides = array();
	if ( 'posts' === semrs_blocks_a( $a, 'source', 'images' ) ) {
		$q = new WP_Query(
			array(
				'post_type'           => post_type_exists( semrs_blocks_a( $a, 'postType', 'post' ) ) ? $a['postType'] : 'post',
				'posts_per_page'      => max( 2, min( 20, (int) semrs_blocks_a( $a, 'count', 6 ) ) ),
				'cat'                 => (int) semrs_blocks_a( $a, 'category', 0 ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		while ( $q->have_posts() ) {
			$q->the_post();
			$img      = has_post_thumbnail() ? get_the_post_thumbnail( null, 'large', array( 'loading' => 'lazy', 'alt' => '' ) ) : '<span class="semrs-slide__noimg"></span>';
			$slides[] = '<a class="semrs-slide__link" href="' . esc_url( get_permalink() ) . '">' . $img . ( ! empty( $a['showCaption'] ) ? '<span class="semrs-slide__caption">' . esc_html( get_the_title() ) . '</span>' : '' ) . '</a>';
		}
		wp_reset_postdata();
	} else {
		foreach ( (array) semrs_blocks_a( $a, 'images', array() ) as $i => $id ) {
			$img = wp_get_attachment_image( (int) $id, 'large', false, array( 'loading' => 0 === $i ? 'eager' : 'lazy' ) );
			if ( ! $img ) {
				continue;
			}
			$cap      = ! empty( $a['showCaption'] ) ? wp_get_attachment_caption( (int) $id ) : '';
			$slides[] = $img . ( $cap ? '<span class="semrs-slide__caption">' . esc_html( $cap ) . '</span>' : '' );
		}
	}
	if ( ! $slides ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add images or choose posts for the slider.', 'semrs-pro' ) . '</p>' : '';
	}
	semrs_pro_blocks_script( 'semrs-pro-slider', 'js/slider.js' );
	$height = (int) semrs_blocks_a( $a, 'height', 0 );
	$items  = '';
	foreach ( $slides as $n => $s ) {
		/* translators: 1: slide number, 2: total */
		$items .= '<li class="semrs-slide" role="group" aria-roledescription="slide" aria-label="' . esc_attr( sprintf( __( '%1$d of %2$d', 'semrs-pro' ), $n + 1, count( $slides ) ) ) . '">' . $s . '</li>';
	}
	return semrs_pro_blocks_css() . sprintf(
		'<div class="semrs-slider" role="region" aria-roledescription="carousel" aria-label="%1$s" data-autoplay="%2$s" data-interval="%3$d" data-loop="%4$s" style="--semrs-per:%5$d;%6$s"><ul class="semrs-slider__track">%7$s</ul>%8$s%9$s</div>',
		esc_attr__( 'Slider', 'semrs-pro' ),
		! empty( $a['autoplay'] ) ? '1' : '0',
		max( 2, (int) semrs_blocks_a( $a, 'interval', 5 ) ) * 1000,
		! empty( $a['loop'] ) ? '1' : '0',
		max( 1, min( 4, (int) semrs_blocks_a( $a, 'perView', 1 ) ) ),
		$height ? '--semrs-h:' . $height . 'px' : '',
		$items,
		! empty( $a['arrows'] ) ? '<button type="button" class="semrs-slider__prev" aria-label="' . esc_attr__( 'Previous slide', 'semrs-pro' ) . '">‹</button><button type="button" class="semrs-slider__next" aria-label="' . esc_attr__( 'Next slide', 'semrs-pro' ) . '">›</button>' : '',
		! empty( $a['dots'] ) ? '<div class="semrs-slider__dots" role="tablist" aria-label="' . esc_attr__( 'Choose slide', 'semrs-pro' ) . '"></div>' : ''
	);
}

/**
 * Animated counter.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_counter( $a ) {
	semrs_pro_blocks_script( 'semrs-pro-counter', 'js/counter.js' );
	$num = (float) semrs_blocks_a( $a, 'number', 0 );
	return semrs_pro_blocks_css() . sprintf(
		'<div class="semrs-counter">%1$s<p class="semrs-counter__num"%2$s><span>%3$s</span><span class="semrs-counter__value" data-to="%4$s" data-duration="%5$d">%6$s</span><span>%7$s</span></p><p class="semrs-counter__label">%8$s</p></div>',
		semrs_blocks_icon_svg( semrs_blocks_a( $a, 'icon', 'trending-up' ), 32 ),
		semrs_blocks_style( array( 'color' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ) ) ),
		esc_html( semrs_blocks_a( $a, 'prefix' ) ),
		esc_attr( $num ),
		max( 1, (int) semrs_blocks_a( $a, 'duration', 2 ) ) * 1000,
		esc_html( number_format_i18n( $num, floor( $num ) == $num ? 0 : 1 ) ), // Real number for no-JS and search engines.
		esc_html( semrs_blocks_a( $a, 'suffix' ) ),
		esc_html( semrs_blocks_a( $a, 'label' ) )
	);
}

/**
 * 3D model viewer (Google <model-viewer>, Apache-2.0, loaded only when used).
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_model( $a ) {
	$src = esc_url( semrs_blocks_a( $a, 'src' ) );
	if ( ! $src || ! preg_match( '/\.(glb|gltf)(\?|$)/i', $src ) ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add the URL of a .glb or .gltf file (upload it to the Media Library or use a free model, e.g. from Poly Haven).', 'semrs-pro' ) . '</p>' : '';
	}
	wp_enqueue_script( 'semrs-model-viewer', 'https://cdn.jsdelivr.net/npm/@google/model-viewer@3.5.0/dist/model-viewer.min.js', array(), '3.5.0', array( 'in_footer' => true, 'strategy' => 'defer' ) );
	add_filter(
		'script_loader_tag',
		function ( $tag, $handle ) {
			return 'semrs-model-viewer' === $handle ? str_replace( '<script ', '<script type="module" ', $tag ) : $tag;
		},
		10,
		2
	);
	$poster = (int) semrs_blocks_a( $a, 'poster', 0 ) ? wp_get_attachment_image_url( (int) $a['poster'], 'large' ) : '';
	return sprintf(
		'<model-viewer src="%1$s" alt="%2$s"%3$s%4$s%5$s%6$s loading="lazy" style="width:100%%;height:%7$dpx;%8$s"></model-viewer>',
		$src,
		esc_attr( semrs_blocks_a( $a, 'alt', '3D model' ) ),
		$poster ? ' poster="' . esc_url( $poster ) . '"' : '',
		! empty( $a['autoRotate'] ) ? ' auto-rotate' : '',
		! empty( $a['controls'] ) ? ' camera-controls touch-action="pan-y"' : '',
		! empty( $a['ar'] ) ? ' ar ar-modes="webxr scene-viewer quick-look"' : '',
		max( 200, min( 900, (int) semrs_blocks_a( $a, 'height', 420 ) ) ),
		semrs_blocks_color( semrs_blocks_a( $a, 'bg' ) ) ? 'background:' . esc_attr( semrs_blocks_color( $a['bg'] ) ) . ';' : ''
	);
}

/**
 * Scroll animation on any block: editor control + front-end trigger.
 */
function semrs_pro_animate_editor() {
	wp_enqueue_script( 'semrs-pro-animate-editor', SEMRS_PRO_URL . 'assets/js/animate-editor.js', array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), SEMRS_PRO_VERSION, true );
}
add_action( 'enqueue_block_editor_assets', 'semrs_pro_animate_editor' );

/**
 * Every block accepts the animation attribute on the server too, so dynamic
 * blocks (and their editor previews) validate it.
 *
 * @param array $args Block type args.
 * @return array
 */
function semrs_pro_animate_attr( $args ) {
	$args['attributes']                   = $args['attributes'] ?? array();
	$args['attributes']['semrsAnimation'] = array( 'type' => 'string', 'default' => '' );
	return $args;
}
add_filter( 'register_block_type_args', 'semrs_pro_animate_attr' );

/**
 * Add the animation class to the block's outer element on the server
 * (no change to saved markup, so no block validation errors), and load the
 * small script + styles only on pages that use animations.
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function semrs_pro_animate_render( $html, $block ) {
	$anim = $block['attrs']['semrsAnimation'] ?? '';
	if ( ! $anim || ! in_array( $anim, array( 'fade-up', 'fade-in', 'zoom-in', 'slide-left', 'slide-right' ), true ) || '' === trim( $html ) || is_admin() || wp_is_json_request() ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	do {
		if ( ! $p->next_tag() ) {
			return $html;
		}
	} while ( in_array( $p->get_tag(), array( 'STYLE', 'SCRIPT', 'LINK' ), true ) );
	$p->add_class( 'semrs-anim' );
	$p->add_class( 'semrs-anim--' . $anim );
	semrs_pro_blocks_script( 'semrs-pro-animate', 'js/animate.js' );
	return semrs_pro_blocks_css() . $p->get_updated_html();
}
add_filter( 'render_block', 'semrs_pro_animate_render', 10, 2 );
