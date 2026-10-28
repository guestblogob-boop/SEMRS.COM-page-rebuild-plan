<?php
/**
 * Free blocks: Icon, Icon List, Fancy Heading, Posts (grid / list / masonry /
 * overlay / compact), Countdown, Pricing Card, Service Card, Free Media Sites.
 * The Contact Form block lives in contact.php.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the free block definitions.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_blocks_free( $blocks ) {
	$align = array( 'type' => 'select', 'label' => __( 'Alignment', 'semrs-blocks' ), 'default' => 'left', 'options' => array( 'left' => __( 'Left', 'semrs-blocks' ), 'center' => __( 'Center', 'semrs-blocks' ), 'right' => __( 'Right', 'semrs-blocks' ) ) );

	$blocks['semrs/icon'] = array(
		'title'    => __( 'Icon', 'semrs-blocks' ),
		'icon'     => 'star-filled',
		'keywords' => array( 'icon', 'svg', 'symbol' ),
		'render'   => 'semrs_blocks_render_icon',
		'controls' => array(
			'icon'      => array( 'type' => 'icon', 'label' => __( 'Icon', 'semrs-blocks' ), 'default' => 'rocket' ),
			'size'      => array( 'type' => 'range', 'label' => __( 'Size (px)', 'semrs-blocks' ), 'default' => 48, 'min' => 16, 'max' => 200 ),
			'color'     => array( 'type' => 'color', 'label' => __( 'Icon colour', 'semrs-blocks' ), 'default' => '' ),
			'bg'        => array( 'type' => 'color', 'label' => __( 'Background colour', 'semrs-blocks' ), 'default' => '' ),
			'shape'     => array( 'type' => 'select', 'label' => __( 'Background shape', 'semrs-blocks' ), 'default' => 'none', 'options' => array( 'none' => __( 'None', 'semrs-blocks' ), 'circle' => __( 'Circle', 'semrs-blocks' ), 'rounded' => __( 'Rounded square', 'semrs-blocks' ) ) ),
			'url'       => array( 'type' => 'url', 'label' => __( 'Link (optional)', 'semrs-blocks' ), 'default' => '' ),
			'label'     => array( 'type' => 'text', 'label' => __( 'Accessible label (empty = decorative)', 'semrs-blocks' ), 'default' => '' ),
			'position'  => $align,
		),
	);

	$blocks['semrs/icon-list'] = array(
		'title'    => __( 'Icon List', 'semrs-blocks' ),
		'icon'     => 'editor-ul',
		'keywords' => array( 'list', 'features', 'checklist' ),
		'render'   => 'semrs_blocks_render_icon_list',
		'controls' => array(
			'items'   => array( 'type' => 'textarea', 'label' => __( 'Items (one per line)', 'semrs-blocks' ), 'default' => "Free SEO review\nPublic prices\nHuman approval on every change" ),
			'icon'    => array( 'type' => 'icon', 'label' => __( 'Icon', 'semrs-blocks' ), 'default' => 'check-circle' ),
			'color'   => array( 'type' => 'color', 'label' => __( 'Icon colour', 'semrs-blocks' ), 'default' => '' ),
			'size'    => array( 'type' => 'range', 'label' => __( 'Icon size', 'semrs-blocks' ), 'default' => 20, 'min' => 12, 'max' => 48 ),
			'columns' => array( 'type' => 'range', 'label' => __( 'Columns (desktop)', 'semrs-blocks' ), 'default' => 1, 'min' => 1, 'max' => 4 ),
		),
	);

	$blocks['semrs/fancy-heading'] = array(
		'title'    => __( 'Fancy Heading', 'semrs-blocks' ),
		'icon'     => 'heading',
		'keywords' => array( 'heading', 'typing', 'gradient', 'highlight', 'animated' ),
		'render'   => 'semrs_blocks_render_fancy_heading',
		'controls' => array(
			'before'  => array( 'type' => 'text', 'label' => __( 'Text before', 'semrs-blocks' ), 'default' => 'SEO that brings you' ),
			'fancy'   => array( 'type' => 'textarea', 'label' => __( 'Fancy words (one per line rotates in Typing style)', 'semrs-blocks' ), 'default' => "more leads\nmore calls\nmore sales" ),
			'after'   => array( 'type' => 'text', 'label' => __( 'Text after', 'semrs-blocks' ), 'default' => '' ),
			'style'   => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'typing', 'options' => array( 'typing' => __( 'Typing (rotating words)', 'semrs-blocks' ), 'gradient' => __( 'Gradient text', 'semrs-blocks' ), 'highlight' => __( 'Marker highlight', 'semrs-blocks' ), 'underline' => __( 'Curved underline', 'semrs-blocks' ), 'outline' => __( 'Outline text', 'semrs-blocks' ) ) ),
			'level'   => array( 'type' => 'select', 'label' => __( 'Heading level', 'semrs-blocks' ), 'default' => 'h2', 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => __( 'Paragraph', 'semrs-blocks' ) ) ),
			'color1'  => array( 'type' => 'color', 'label' => __( 'Colour 1 (fancy words / gradient start)', 'semrs-blocks' ), 'default' => '' ),
			'color2'  => array( 'type' => 'color', 'label' => __( 'Colour 2 (gradient end / highlight)', 'semrs-blocks' ), 'default' => '' ),
			'size'    => array( 'type' => 'range', 'label' => __( 'Font size (px, 0 = theme)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 96 ),
			'textAlign' => $align,
		),
	);

	$blocks['semrs/posts'] = array(
		'title'    => __( 'Posts (Grid / List / Masonry)', 'semrs-blocks' ),
		'icon'     => 'grid-view',
		'keywords' => array( 'posts', 'blog', 'grid', 'list', 'masonry', 'query' ),
		'render'   => 'semrs_blocks_render_posts',
		'panels'   => array( __( 'Query', 'semrs-blocks' ) => array( 'postType', 'category', 'count', 'offset', 'orderby', 'excludeCurrent' ), __( 'Layout', 'semrs-blocks' ) => array( 'layout', 'columns', 'imageRatio' ), __( 'Show', 'semrs-blocks' ) => array( 'showImage', 'showDate', 'showAuthor', 'showCategory', 'showExcerpt', 'excerptWords', 'showMore', 'moreText' ) ),
		'controls' => array(
			'postType'       => array( 'type' => 'posttype', 'label' => __( 'Post type', 'semrs-blocks' ), 'default' => 'post' ),
			'category'       => array( 'type' => 'category', 'label' => __( 'Category', 'semrs-blocks' ), 'default' => '' ),
			'count'          => array( 'type' => 'range', 'label' => __( 'Number of posts', 'semrs-blocks' ), 'default' => 6, 'min' => 1, 'max' => 24 ),
			'offset'         => array( 'type' => 'range', 'label' => __( 'Skip first (offset)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 20 ),
			'orderby'        => array( 'type' => 'select', 'label' => __( 'Order', 'semrs-blocks' ), 'default' => 'date', 'options' => array( 'date' => __( 'Newest first', 'semrs-blocks' ), 'modified' => __( 'Recently updated', 'semrs-blocks' ), 'title' => __( 'Title A–Z', 'semrs-blocks' ), 'comment_count' => __( 'Most commented', 'semrs-blocks' ), 'rand' => __( 'Random', 'semrs-blocks' ) ) ),
			'excludeCurrent' => array( 'type' => 'toggle', 'label' => __( 'Exclude the current post', 'semrs-blocks' ), 'default' => true ),
			'layout'         => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'grid', 'options' => array( 'grid' => __( 'Card grid', 'semrs-blocks' ), 'list' => __( 'List (image left)', 'semrs-blocks' ), 'masonry' => __( 'Masonry', 'semrs-blocks' ), 'overlay' => __( 'Overlay (title on image)', 'semrs-blocks' ), 'compact' => __( 'Compact list (titles only)', 'semrs-blocks' ) ) ),
			'columns'        => array( 'type' => 'range', 'label' => __( 'Columns (desktop)', 'semrs-blocks' ), 'default' => 3, 'min' => 1, 'max' => 4 ),
			'imageRatio'     => array( 'type' => 'select', 'label' => __( 'Image shape', 'semrs-blocks' ), 'default' => '16/9', 'options' => array( '16/9' => '16:9', '4/3' => '4:3', '1/1' => __( 'Square', 'semrs-blocks' ), 'auto' => __( 'Original', 'semrs-blocks' ) ) ),
			'showImage'      => array( 'type' => 'toggle', 'label' => __( 'Featured image', 'semrs-blocks' ), 'default' => true ),
			'showDate'       => array( 'type' => 'toggle', 'label' => __( 'Date', 'semrs-blocks' ), 'default' => true ),
			'showAuthor'     => array( 'type' => 'toggle', 'label' => __( 'Author', 'semrs-blocks' ), 'default' => false ),
			'showCategory'   => array( 'type' => 'toggle', 'label' => __( 'Category', 'semrs-blocks' ), 'default' => true ),
			'showExcerpt'    => array( 'type' => 'toggle', 'label' => __( 'Excerpt', 'semrs-blocks' ), 'default' => true ),
			'excerptWords'   => array( 'type' => 'range', 'label' => __( 'Excerpt length (words)', 'semrs-blocks' ), 'default' => 20, 'min' => 5, 'max' => 60 ),
			'showMore'       => array( 'type' => 'toggle', 'label' => __( 'Read more link', 'semrs-blocks' ), 'default' => true ),
			'moreText'       => array( 'type' => 'text', 'label' => __( 'Read more text', 'semrs-blocks' ), 'default' => 'Read more' ),
		),
	);

	$blocks['semrs/countdown'] = array(
		'title'    => __( 'Countdown Timer', 'semrs-blocks' ),
		'icon'     => 'clock',
		'keywords' => array( 'countdown', 'timer', 'offer', 'launch', 'deadline' ),
		'render'   => 'semrs_blocks_render_countdown',
		'controls' => array(
			'date'     => array( 'type' => 'datetime', 'label' => __( 'Ends at (your site timezone)', 'semrs-blocks' ), 'default' => '' ),
			'expired'  => array( 'type' => 'text', 'label' => __( 'Message when finished', 'semrs-blocks' ), 'default' => 'This offer has ended.' ),
			'style'    => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'boxes', 'options' => array( 'boxes' => __( 'Boxes', 'semrs-blocks' ), 'inline' => __( 'Inline text', 'semrs-blocks' ) ) ),
			'showDays' => array( 'type' => 'toggle', 'label' => __( 'Show days', 'semrs-blocks' ), 'default' => true ),
			'bg'       => array( 'type' => 'color', 'label' => __( 'Box colour', 'semrs-blocks' ), 'default' => '' ),
			'color'    => array( 'type' => 'color', 'label' => __( 'Number colour', 'semrs-blocks' ), 'default' => '' ),
			'position' => $align,
		),
	);

	$blocks['semrs/pricing'] = array(
		'title'    => __( 'Pricing Card', 'semrs-blocks' ),
		'icon'     => 'money-alt',
		'keywords' => array( 'pricing', 'plan', 'price', 'table', 'package' ),
		'render'   => 'semrs_blocks_render_pricing',
		'controls' => array(
			'name'        => array( 'type' => 'text', 'label' => __( 'Plan name', 'semrs-blocks' ), 'default' => 'Growth' ),
			'price'       => array( 'type' => 'text', 'label' => __( 'Price', 'semrs-blocks' ), 'default' => '$499' ),
			'period'      => array( 'type' => 'text', 'label' => __( 'Period', 'semrs-blocks' ), 'default' => '/month' ),
			'prefix'      => array( 'type' => 'text', 'label' => __( 'Small text above price (e.g. From)', 'semrs-blocks' ), 'default' => 'From' ),
			'description' => array( 'type' => 'textarea', 'label' => __( 'Short description', 'semrs-blocks' ), 'default' => 'For businesses ready to grow organic leads every month.' ),
			'features'    => array( 'type' => 'textarea', 'label' => __( 'Features (one per line; start with - for not included)', 'semrs-blocks' ), 'default' => "Monthly SEO plan\n4 optimized pages\nMonthly report with real data\n-Paid ads management" ),
			'button'      => array( 'type' => 'text', 'label' => __( 'Button text', 'semrs-blocks' ), 'default' => 'Get started' ),
			'url'         => array( 'type' => 'url', 'label' => __( 'Button link', 'semrs-blocks' ), 'default' => '/get-a-quote/' ),
			'featured'    => array( 'type' => 'toggle', 'label' => __( 'Highlight this plan', 'semrs-blocks' ), 'default' => false ),
			'badge'       => array( 'type' => 'text', 'label' => __( 'Badge (e.g. Most popular)', 'semrs-blocks' ), 'default' => '' ),
			'accent'      => array( 'type' => 'color', 'label' => __( 'Accent colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/service-card'] = array(
		'title'    => __( 'Service / Feature Card', 'semrs-blocks' ),
		'icon'     => 'id-alt',
		'keywords' => array( 'service', 'feature', 'box', 'card', 'icon box' ),
		'render'   => 'semrs_blocks_render_service',
		'controls' => array(
			'icon'    => array( 'type' => 'icon', 'label' => __( 'Icon', 'semrs-blocks' ), 'default' => 'chart' ),
			'image'   => array( 'type' => 'media', 'label' => __( 'Image instead of icon (optional)', 'semrs-blocks' ), 'default' => 0 ),
			'title'   => array( 'type' => 'text', 'label' => __( 'Title', 'semrs-blocks' ), 'default' => 'SEO Services' ),
			'text'    => array( 'type' => 'textarea', 'label' => __( 'Text', 'semrs-blocks' ), 'default' => 'Monthly SEO that fixes, writes and builds what Google needs to rank you.' ),
			'price'   => array( 'type' => 'text', 'label' => __( 'Price line (optional)', 'semrs-blocks' ), 'default' => '' ),
			'link'    => array( 'type' => 'text', 'label' => __( 'Link text', 'semrs-blocks' ), 'default' => 'Learn more' ),
			'url'     => array( 'type' => 'url', 'label' => __( 'Link URL', 'semrs-blocks' ), 'default' => '' ),
			'style'   => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'card', 'options' => array( 'card' => __( 'Card', 'semrs-blocks' ), 'plain' => __( 'Plain', 'semrs-blocks' ), 'side' => __( 'Icon on the side', 'semrs-blocks' ) ) ),
			'color'   => array( 'type' => 'color', 'label' => __( 'Icon colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/media-sources'] = array(
		'title'    => __( 'Free Media Sites', 'semrs-blocks' ),
		'icon'     => 'format-gallery',
		'keywords' => array( 'images', 'free', 'stock', 'icons', '3d', 'gif', 'video', 'pixabay', 'pexels', 'unsplash' ),
		'render'   => 'semrs_blocks_render_media_sources',
		'controls' => array(
			'group'   => array( 'type' => 'select', 'label' => __( 'Show', 'semrs-blocks' ), 'default' => 'all', 'options' => array( 'all' => __( 'All types', 'semrs-blocks' ), 'photos' => __( 'Photos', 'semrs-blocks' ), 'video' => __( 'Videos', 'semrs-blocks' ), 'illustrations' => __( 'Illustrations & icons', 'semrs-blocks' ), 'animation' => __( 'Animations & GIFs', 'semrs-blocks' ), '3d' => __( '3D models & textures', 'semrs-blocks' ) ) ),
			'columns' => array( 'type' => 'range', 'label' => __( 'Columns', 'semrs-blocks' ), 'default' => 3, 'min' => 1, 'max' => 4 ),
		),
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_blocks_free', 5 );

/**
 * Inline style string from a map.
 *
 * @param array $map prop => value ('' skipped).
 * @return string
 */
function semrs_blocks_style( $map ) {
	$out = '';
	foreach ( $map as $prop => $val ) {
		if ( '' !== (string) $val ) {
			$out .= $prop . ':' . $val . ';';
		}
	}
	return $out ? ' style="' . esc_attr( $out ) . '"' : '';
}

/**
 * Icon.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_icon( $a ) {
	$size  = (int) semrs_blocks_a( $a, 'size', 48 );
	$shape = semrs_blocks_a( $a, 'shape', 'none' );
	$svg   = semrs_blocks_icon_svg( semrs_blocks_a( $a, 'icon', 'rocket' ), 'none' === $shape ? $size : (int) round( $size * 0.55 ), semrs_blocks_a( $a, 'color' ), semrs_blocks_a( $a, 'label' ) );
	$wrap  = 'none' === $shape ? $svg : '<span class="semrs-icon-bg semrs-icon-bg--' . esc_attr( $shape ) . '"' . semrs_blocks_style( array( 'width' => $size . 'px', 'height' => $size . 'px', 'background' => semrs_blocks_color( semrs_blocks_a( $a, 'bg' ) ) ) ) . '>' . $svg . '</span>';
	$url   = semrs_blocks_a( $a, 'url' );
	if ( $url ) {
		$wrap = '<a href="' . esc_url( $url ) . '">' . $wrap . '</a>';
	}
	return '<div class="semrs-icon-wrap" style="text-align:' . esc_attr( in_array( semrs_blocks_a( $a, 'position', 'left' ), array( 'left', 'center', 'right' ), true ) ? semrs_blocks_a( $a, 'position', 'left' ) : 'left' ) . '">' . $wrap . '</div>';
}

/**
 * Icon list.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_icon_list( $a ) {
	$items = semrs_blocks_lines( semrs_blocks_a( $a, 'items' ) );
	if ( ! $items ) {
		return '';
	}
	$icon = semrs_blocks_icon_svg( semrs_blocks_a( $a, 'icon', 'check-circle' ), (int) semrs_blocks_a( $a, 'size', 20 ), semrs_blocks_a( $a, 'color' ) );
	$out  = '<ul class="semrs-icon-list" style="--semrs-cols:' . max( 1, min( 4, (int) semrs_blocks_a( $a, 'columns', 1 ) ) ) . '">';
	foreach ( $items as $item ) {
		$out .= '<li>' . $icon . '<span>' . wp_kses( $item, array( 'strong' => array(), 'em' => array(), 'a' => array( 'href' => array() ) ) ) . '</span></li>';
	}
	return $out . '</ul>';
}

/**
 * Fancy heading.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_fancy_heading( $a ) {
	$tag   = in_array( semrs_blocks_a( $a, 'level', 'h2' ), array( 'h1', 'h2', 'h3', 'h4', 'p' ), true ) ? semrs_blocks_a( $a, 'level', 'h2' ) : 'h2';
	$style = semrs_blocks_a( $a, 'style', 'typing' );
	$words = semrs_blocks_lines( semrs_blocks_a( $a, 'fancy' ) );
	$c1    = semrs_blocks_color( semrs_blocks_a( $a, 'color1' ) );
	$c2    = semrs_blocks_color( semrs_blocks_a( $a, 'color2' ) );
	$first = $words ? $words[0] : '';
	if ( 'typing' === $style && count( $words ) > 1 ) {
		semrs_blocks_script( 'semrs-blocks-fancy', 'fancy.js' );
		// Start on the longest word: the first paint already has the heading's tallest height, so typing never shifts the layout.
		$first = array_reduce( $words, function ( $long, $w ) { return mb_strlen( $w ) > mb_strlen( $long ) ? $w : $long; }, '' );
		$fancy = '<span class="semrs-fancy semrs-fancy--typing" data-words="' . esc_attr( wp_json_encode( $words ) ) . '"' . semrs_blocks_style( array( 'color' => $c1 ) ) . '>' . esc_html( $first ) . '</span><span class="semrs-fancy__caret" aria-hidden="true"></span>';
		// Screen readers get all words once instead of the animation.
		$fancy = '<span class="screen-reader-text">' . esc_html( implode( ', ', $words ) ) . '</span><span aria-hidden="true">' . $fancy . '</span>';
	} else {
		$vars  = array(
			'--semrs-c1' => $c1,
			'--semrs-c2' => $c2,
		);
		$fancy = '<span class="semrs-fancy semrs-fancy--' . esc_attr( $style ) . '"' . semrs_blocks_style( $vars ) . '>' . esc_html( implode( ' ', $words ) ) . '</span>';
	}
	$size  = (int) semrs_blocks_a( $a, 'size', 0 );
	$align = semrs_blocks_a( $a, 'textAlign', 'left' );
	return sprintf(
		'<%1$s class="semrs-fancy-heading"%2$s>%3$s %4$s %5$s</%1$s>',
		tag_escape( $tag ),
		semrs_blocks_style( array( 'text-align' => in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : '', 'font-size' => $size ? $size . 'px' : '' ) ),
		esc_html( semrs_blocks_a( $a, 'before' ) ),
		$fancy,
		esc_html( semrs_blocks_a( $a, 'after' ) )
	);
}

/**
 * Posts.
 *
 * @param array    $a     Attributes.
 * @param WP_Block $block Block (unused).
 * @return string
 */
function semrs_blocks_render_posts( $a, $block = null ) {
	$type = post_type_exists( semrs_blocks_a( $a, 'postType', 'post' ) ) ? semrs_blocks_a( $a, 'postType', 'post' ) : 'post';
	$args = array(
		'post_type'           => $type,
		'posts_per_page'      => max( 1, min( 24, (int) semrs_blocks_a( $a, 'count', 6 ) ) ),
		'offset'              => max( 0, (int) semrs_blocks_a( $a, 'offset', 0 ) ),
		'orderby'             => in_array( semrs_blocks_a( $a, 'orderby', 'date' ), array( 'date', 'modified', 'title', 'comment_count', 'rand' ), true ) ? semrs_blocks_a( $a, 'orderby', 'date' ) : 'date',
		'order'               => 'title' === semrs_blocks_a( $a, 'orderby' ) ? 'ASC' : 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
	);
	if ( semrs_blocks_a( $a, 'category' ) && 'post' === $type ) {
		$args['cat'] = (int) $a['category'];
	}
	if ( ! empty( $a['excludeCurrent'] ) && is_singular() ) {
		$args['post__not_in'] = array( get_queried_object_id() ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- one ID: the post being viewed.
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'No posts match these settings yet.', 'semrs-blocks' ) . '</p>' : '';
	}
	$layout = semrs_blocks_a( $a, 'layout', 'grid' );
	$ratio  = semrs_blocks_a( $a, 'imageRatio', '16/9' );
	$out    = '<div class="semrs-posts semrs-posts--' . esc_attr( $layout ) . '" style="--semrs-cols:' . max( 1, min( 4, (int) semrs_blocks_a( $a, 'columns', 3 ) ) ) . ';--semrs-ratio:' . esc_attr( 'auto' === $ratio ? 'auto' : $ratio ) . '">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$link  = get_permalink();
		$title = get_the_title();
		if ( 'compact' === $layout ) {
			$out .= '<article class="semrs-post"><a class="semrs-post__title" href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>' . ( ! empty( $a['showDate'] ) ? ' <time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>' : '' ) . '</article>';
			continue;
		}
		$img = ! empty( $a['showImage'] ) && has_post_thumbnail() ? '<a class="semrs-post__media" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( null, 'medium_large', array( 'loading' => 'lazy', 'alt' => get_the_title() ) ) . '</a>' : '';
		$meta = array();
		if ( ! empty( $a['showCategory'] ) && 'post' === $type ) {
			$cats = get_the_category();
			if ( $cats ) {
				$meta[] = '<a class="semrs-post__cat" href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
			}
		}
		if ( ! empty( $a['showDate'] ) ) {
			$meta[] = '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>';
		}
		if ( ! empty( $a['showAuthor'] ) ) {
			$meta[] = '<span>' . esc_html( get_the_author() ) . '</span>';
		}
		$out .= '<article class="semrs-post">' . $img . '<div class="semrs-post__body">';
		$out .= $meta ? '<div class="semrs-post__meta">' . implode( ' · ', $meta ) . '</div>' : '';
		$out .= '<h3 class="semrs-post__title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';
		if ( ! empty( $a['showExcerpt'] ) && 'overlay' !== $layout ) {
			$out .= '<p class="semrs-post__excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), (int) semrs_blocks_a( $a, 'excerptWords', 20 ), '…' ) ) . '</p>';
		}
		if ( ! empty( $a['showMore'] ) && 'overlay' !== $layout ) {
			$out .= '<a class="semrs-post__more" href="' . esc_url( $link ) . '">' . esc_html( semrs_blocks_a( $a, 'moreText', 'Read more' ) ) . '<span class="screen-reader-text"> ' . esc_html( $title ) . '</span> →</a>';
		}
		$out .= '</div></article>';
	}
	wp_reset_postdata();
	return $out . '</div>';
}

/**
 * Countdown.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_countdown( $a ) {
	$date = semrs_blocks_a( $a, 'date' );
	$ts   = $date ? strtotime( get_gmt_from_date( str_replace( 'T', ' ', $date ) ) . ' UTC' ) : false;
	if ( ! $ts ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Choose an end date for the countdown.', 'semrs-blocks' ) . '</p>' : '';
	}
	semrs_blocks_script( 'semrs-blocks-countdown', 'countdown.js' );
	$units = array( 'd' => __( 'Days', 'semrs-blocks' ), 'h' => __( 'Hours', 'semrs-blocks' ), 'm' => __( 'Minutes', 'semrs-blocks' ), 's' => __( 'Seconds', 'semrs-blocks' ) );
	if ( empty( $a['showDays'] ) ) {
		unset( $units['d'] );
	}
	$boxes = '';
	foreach ( $units as $k => $label ) {
		$boxes .= '<span class="semrs-cd__unit"' . semrs_blocks_style( array( 'background' => semrs_blocks_color( semrs_blocks_a( $a, 'bg' ) ) ) ) . '><b data-u="' . esc_attr( $k ) . '"' . semrs_blocks_style( array( 'color' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ) ) ) . '>00</b><small>' . esc_html( $label ) . '</small></span>';
	}
	$pos = semrs_blocks_a( $a, 'position', 'left' );
	return '<div class="semrs-cd semrs-cd--' . esc_attr( semrs_blocks_a( $a, 'style', 'boxes' ) ) . '" data-end="' . esc_attr( $ts * 1000 ) . '" data-expired="' . esc_attr( semrs_blocks_a( $a, 'expired', '' ) ) . '" role="timer" aria-live="off" style="justify-content:' . esc_attr( 'center' === $pos ? 'center' : ( 'right' === $pos ? 'flex-end' : 'flex-start' ) ) . '">' . $boxes . '</div>';
}

/**
 * Pricing card.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_pricing( $a ) {
	$feat = '';
	foreach ( semrs_blocks_lines( semrs_blocks_a( $a, 'features' ) ) as $f ) {
		$no    = 0 === strpos( $f, '-' );
		$feat .= '<li class="' . ( $no ? 'is-no' : 'is-yes' ) . '">' . semrs_blocks_icon_svg( $no ? 'x' : 'check', 18 ) . '<span>' . esc_html( ltrim( $f, '- ' ) ) . '</span>' . ( $no ? '<span class="screen-reader-text"> ' . esc_html__( '(not included)', 'semrs-blocks' ) . '</span>' : '' ) . '</li>';
	}
	$badge = semrs_blocks_a( $a, 'badge' );
	return sprintf(
		'<div class="semrs-pricing%1$s"%2$s>%3$s<h3 class="semrs-pricing__name">%4$s</h3><p class="semrs-pricing__desc">%5$s</p><p class="semrs-pricing__price"><small>%6$s</small> <b>%7$s</b> <span>%8$s</span></p><ul class="semrs-pricing__features">%9$s</ul>%10$s</div>',
		! empty( $a['featured'] ) ? ' is-featured' : '',
		semrs_blocks_style( array( '--semrs-accent' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ),
		$badge ? '<span class="semrs-pricing__badge">' . esc_html( $badge ) . '</span>' : '',
		esc_html( semrs_blocks_a( $a, 'name' ) ),
		esc_html( semrs_blocks_a( $a, 'description' ) ),
		esc_html( semrs_blocks_a( $a, 'prefix' ) ),
		esc_html( semrs_blocks_a( $a, 'price' ) ),
		esc_html( semrs_blocks_a( $a, 'period' ) ),
		$feat,
		semrs_blocks_a( $a, 'button' ) ? '<a class="semrs-btn" href="' . esc_url( semrs_blocks_a( $a, 'url', '#' ) ) . '">' . esc_html( $a['button'] ) . '</a>' : ''
	);
}

/**
 * Service card.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_service( $a ) {
	$img   = (int) semrs_blocks_a( $a, 'image', 0 );
	$media = $img ? wp_get_attachment_image( $img, 'medium', false, array( 'class' => 'semrs-service__img', 'loading' => 'lazy' ) ) : '<span class="semrs-service__icon">' . semrs_blocks_icon_svg( semrs_blocks_a( $a, 'icon', 'chart' ), 32, semrs_blocks_a( $a, 'color' ) ) . '</span>';
	$url   = semrs_blocks_a( $a, 'url' );
	return sprintf(
		'<div class="semrs-service semrs-service--%1$s">%2$s<div class="semrs-service__body"><h3>%3$s</h3><p>%4$s</p>%5$s%6$s</div></div>',
		esc_attr( semrs_blocks_a( $a, 'style', 'card' ) ),
		$media,
		$url ? '<a href="' . esc_url( $url ) . '">' . esc_html( semrs_blocks_a( $a, 'title' ) ) . '</a>' : esc_html( semrs_blocks_a( $a, 'title' ) ),
		esc_html( semrs_blocks_a( $a, 'text' ) ),
		semrs_blocks_a( $a, 'price' ) ? '<p class="semrs-service__price">' . esc_html( $a['price'] ) . '</p>' : '',
		$url && semrs_blocks_a( $a, 'link' ) ? '<a class="semrs-service__link" href="' . esc_url( $url ) . '" aria-hidden="true" tabindex="-1">' . esc_html( $a['link'] ) . ' →</a>' : ''
	);
}

/**
 * Free media sites (curated, with licence notes).
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_media_sources( $a ) {
	$sites = array(
		array( 'Pixabay', 'https://pixabay.com/', 'photos', __( 'Photos, illustrations, vectors, videos and music. Free under the Pixabay Content License.', 'semrs-blocks' ) ),
		array( 'Pexels', 'https://www.pexels.com/', 'photos', __( 'High-quality photos and videos, free to use under the Pexels License.', 'semrs-blocks' ) ),
		array( 'Unsplash', 'https://unsplash.com/', 'photos', __( 'Large photo library under the Unsplash License (some Unsplash+ images are paid).', 'semrs-blocks' ) ),
		array( 'Openverse', 'https://openverse.org/', 'photos', __( 'Search engine for openly licensed images and audio. Check each item\'s licence.', 'semrs-blocks' ) ),
		array( 'Wikimedia Commons', 'https://commons.wikimedia.org/', 'photos', __( 'Millions of freely licensed files. Attribution is often required.', 'semrs-blocks' ) ),
		array( 'StockSnap', 'https://stocksnap.io/', 'photos', __( 'CC0 photos.', 'semrs-blocks' ) ),
		array( 'Burst by Shopify', 'https://burst.shopify.com/', 'photos', __( 'Free business and product photos.', 'semrs-blocks' ) ),
		array( 'Coverr', 'https://coverr.co/', 'video', __( 'Free stock videos for websites and backgrounds.', 'semrs-blocks' ) ),
		array( 'Mixkit', 'https://mixkit.co/', 'video', __( 'Free videos, music and sound effects under the Mixkit licence.', 'semrs-blocks' ) ),
		array( 'unDraw', 'https://undraw.co/', 'illustrations', __( 'Open-source illustrations you can recolour to your brand.', 'semrs-blocks' ) ),
		array( 'Lucide', 'https://lucide.dev/', 'illustrations', __( 'Open-source line icons (ISC licence).', 'semrs-blocks' ) ),
		array( 'Tabler Icons', 'https://tabler.io/icons', 'illustrations', __( 'Thousands of open-source SVG icons (MIT).', 'semrs-blocks' ) ),
		array( 'LottieFiles (free animations)', 'https://lottiefiles.com/free-animations', 'animation', __( 'Free lightweight animations; check each file\'s licence.', 'semrs-blocks' ) ),
		array( 'GIPHY', 'https://giphy.com/', 'animation', __( 'GIFs and stickers. Use their embed or share options and follow GIPHY\'s terms.', 'semrs-blocks' ) ),
		array( 'Poly Haven', 'https://polyhaven.com/', '3d', __( 'CC0 3D models, textures and HDRIs.', 'semrs-blocks' ) ),
		array( 'Sketchfab (downloadable)', 'https://sketchfab.com/features/free-3d-models', '3d', __( 'Free 3D models; licence varies by model (often CC BY).', 'semrs-blocks' ) ),
		array( 'Kenney', 'https://kenney.nl/assets', '3d', __( 'CC0 game and 3D assets.', 'semrs-blocks' ) ),
	);
	$group = semrs_blocks_a( $a, 'group', 'all' );
	$icons = array( 'photos' => 'camera', 'video' => 'play', 'illustrations' => 'pen', 'animation' => 'bolt', '3d' => 'layers' );
	$out   = '<div class="semrs-sources" style="--semrs-cols:' . max( 1, min( 4, (int) semrs_blocks_a( $a, 'columns', 3 ) ) ) . '">';
	foreach ( $sites as $s ) {
		if ( 'all' !== $group && $group !== $s[2] ) {
			continue;
		}
		$out .= '<a class="semrs-source" href="' . esc_url( $s[1] ) . '" target="_blank" rel="noopener nofollow">' . semrs_blocks_icon_svg( $icons[ $s[2] ], 22 ) . '<span><b>' . esc_html( $s[0] ) . '</b><small>' . esc_html( $s[3] ) . '</small></span></a>';
	}
	return $out . '</div><p class="semrs-sources__note">' . esc_html__( 'Licences change: always check the licence shown on each file before using it.', 'semrs-blocks' ) . '</p>';
}
