<?php
/**
 * Template helpers: layout resolution, body classes, meta, breadcrumbs.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sidebar layout for the current view: right | left | none.
 *
 * @return string
 */
function semrs_pro_layout() {
	if ( is_page_template( array( 'page-templates/full-width.php', 'page-templates/canvas.php' ) ) || is_404() ) {
		$layout = 'none';
	} elseif ( is_singular( 'post' ) ) {
		$layout = semrs_pro_opt( 'layout_post' );
	} elseif ( is_singular() ) {
		$layout = semrs_pro_opt( 'layout_page' );
	} else {
		$layout = semrs_pro_opt( 'layout_archive' );
	}
	if ( 'none' !== $layout && ! is_active_sidebar( 'sidebar-1' ) ) {
		$layout = 'none';
	}
	/**
	 * Filter the layout (right|left|none).
	 *
	 * @param string $layout Layout.
	 */
	return apply_filters( 'semrs_pro_layout', $layout );
}

/**
 * Body classes for layout and header modes.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function semrs_pro_body_classes( $classes ) {
	$classes[] = 'none' === semrs_pro_layout() ? 'layout-no-sidebar' : 'layout-' . semrs_pro_layout() . '-sidebar';
	if ( semrs_pro_opt( 'sticky_header' ) ) {
		$classes[] = 'sp-sticky-header';
	}
	if ( is_front_page() && semrs_pro_opt( 'transparent_front' ) ) {
		$classes[] = 'sp-transparent-header';
	}
	if ( is_home() || is_archive() || is_search() ) {
		$classes[] = 'list' === semrs_pro_opt( 'archive_style' ) ? 'sp-archive-list' : 'sp-archive-grid';
	}
	if ( is_page_template( 'page-templates/full-width.php' ) ) {
		$classes[] = 'sp-full-width';
	}
	return $classes;
}
add_filter( 'body_class', 'semrs_pro_body_classes' );

/**
 * Posted on / author / categories line.
 */
function semrs_pro_entry_meta() {
	$parts   = array();
	$parts[] = sprintf(
		'<span class="posted-on"><time datetime="%s">%s</time></span>',
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);
	if ( get_the_author() ) {
		$parts[] = sprintf(
			'<span class="byline">%s <a href="%s">%s</a></span>',
			esc_html__( 'by', 'semrs-pro' ),
			esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		);
	}
	$cats = get_the_category_list( ', ' );
	if ( $cats && 'post' === get_post_type() ) {
		$parts[] = '<span class="cat-links">' . $cats . '</span>';
	}
	echo '<div class="entry-meta">' . implode( '', $parts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
}

/**
 * Breadcrumbs from SEMRS Core SEO, else Rank Math, else nothing.
 */
function semrs_pro_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	if ( function_exists( 'semrs_seo_breadcrumbs' ) && function_exists( 'semrs_seo_active' ) && semrs_seo_active() ) {
		semrs_seo_breadcrumbs();
	} elseif ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		rank_math_the_breadcrumbs();
	}
}
add_action( 'semrs_content_top', 'semrs_pro_breadcrumbs', 5 );

/**
 * Excerpt length and "more" marker.
 */
add_filter(
	'excerpt_length',
	function () {
		return (int) semrs_pro_opt( 'excerpt_length' );
	}
);
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/**
 * Footer copyright with {year} and {site}.
 *
 * @return string
 */
function semrs_pro_copyright() {
	return strtr(
		(string) semrs_pro_opt( 'copyright' ),
		array(
			'{year}' => gmdate( 'Y' ),
			'{site}' => get_bloginfo( 'name' ),
		)
	);
}

/**
 * Mobile submenu toggles are added by JS; mega menu column class here.
 *
 * @param WP_Post[] $items Menu items.
 * @return WP_Post[]
 */
function semrs_pro_mega_classes( $items ) {
	$children = array();
	foreach ( $items as $item ) {
		$p              = (int) $item->menu_item_parent;
		$children[ $p ] = isset( $children[ $p ] ) ? $children[ $p ] + 1 : 1;
	}
	foreach ( $items as $item ) {
		if ( 0 === (int) $item->menu_item_parent && in_array( 'semrs-mega', (array) $item->classes, true ) ) {
			$n               = isset( $children[ (int) $item->ID ] ) ? $children[ (int) $item->ID ] : 0;
			$item->classes[] = 'semrs-mega-cols-' . max( 2, min( 4, (int) ceil( $n / 4 ) ) );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'semrs_pro_mega_classes' );

/**
 * Show menu item descriptions (useful in mega menus).
 *
 * @param string   $title Item title.
 * @param WP_Post  $item  Item.
 * @param stdClass $args  Args.
 * @param int      $depth Depth.
 * @return string
 */
function semrs_pro_menu_description( $title, $item, $args, $depth ) {
	if ( $depth > 0 && ! empty( $item->description ) && isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$title .= '<span class="menu-item-description">' . esc_html( $item->description ) . '</span>';
	}
	return $title;
}
add_filter( 'nav_menu_item_title', 'semrs_pro_menu_description', 10, 4 );

/**
 * Archive titles without the "Category:" / "Tag:" prefix.
 */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
