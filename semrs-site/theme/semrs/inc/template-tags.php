<?php
/**
 * Template helpers: layout resolution, body classes, meta, breadcrumbs.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sidebar layout for the current view: right | left | none.
 *
 * @return string
 */
function semrs_theme_layout() {
	if ( is_page_template( array( 'page-templates/full-width.php', 'page-templates/canvas.php' ) ) || is_404() ) {
		$layout = 'none';
	} elseif ( is_singular( 'post' ) ) {
		$layout = semrs_theme_opt( 'layout_post' );
	} elseif ( is_singular() ) {
		$layout = semrs_theme_opt( 'layout_page' );
	} else {
		$layout = semrs_theme_opt( 'layout_archive' );
	}
	if ( 'none' !== $layout && ! is_active_sidebar( 'sidebar-1' ) ) {
		$layout = 'none';
	}
	/**
	 * Filter the layout (right|left|none).
	 *
	 * @param string $layout Layout.
	 */
	return apply_filters( 'semrs_theme_layout', $layout );
}

/**
 * Body classes for layout and header modes.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function semrs_theme_body_classes( $classes ) {
	$classes[] = 'none' === semrs_theme_layout() ? 'layout-no-sidebar' : 'layout-' . semrs_theme_layout() . '-sidebar';
	if ( is_home() || is_archive() || is_search() ) {
		$classes[] = 'list' === semrs_theme_opt( 'archive_style' ) ? 'sp-archive-list' : 'sp-archive-grid';
	}
	if ( is_page_template( 'page-templates/full-width.php' ) ) {
		$classes[] = 'sp-full-width';
	}
	return $classes;
}
add_filter( 'body_class', 'semrs_theme_body_classes' );

/**
 * Posted on / author / categories line.
 */
function semrs_theme_entry_meta() {
	$parts   = array();
	$parts[] = sprintf(
		'<span class="posted-on"><time datetime="%s">%s</time></span>',
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);
	if ( get_the_author() ) {
		$parts[] = sprintf(
			'<span class="byline">%s <a href="%s">%s</a></span>',
			esc_html__( 'by', 'semrs' ),
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
function semrs_theme_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	if ( function_exists( 'semrs_seo_breadcrumbs' ) && function_exists( 'semrs_seo_active' ) && semrs_seo_active() ) {
		semrs_seo_breadcrumbs();
	} elseif ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		rank_math_the_breadcrumbs();
	}
}
add_action( 'semrs_content_top', 'semrs_theme_breadcrumbs', 5 );

/**
 * Excerpt length and "more" marker.
 */
add_filter(
	'excerpt_length',
	function () {
		return (int) semrs_theme_opt( 'excerpt_length' );
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
function semrs_theme_copyright() {
	return strtr(
		(string) semrs_theme_opt( 'copyright' ),
		array(
			'{year}' => gmdate( 'Y' ),
			'{site}' => get_bloginfo( 'name' ),
		)
	);
}


/**
 * Show menu item descriptions (useful in mega menus).
 *
 * @param string   $title Item title.
 * @param WP_Post  $item  Item.
 * @param stdClass $args  Args.
 * @param int      $depth Depth.
 * @return string
 */
function semrs_theme_menu_description( $title, $item, $args, $depth ) {
	if ( $depth > 0 && ! empty( $item->description ) && isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$title .= '<span class="menu-item-description">' . esc_html( $item->description ) . '</span>';
	}
	return $title;
}
add_filter( 'nav_menu_item_title', 'semrs_theme_menu_description', 10, 4 );

/**
 * Archive titles without the "Category:" / "Tag:" prefix.
 */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

/**
 * Social profile icons (brand colours, filled), linked from the Customizer.
 *
 * @param string $class Extra wrapper class.
 * @return string
 */
function semrs_theme_social_icons( $class = '' ) {
	$items = '';
	foreach ( semrs_theme_social_networks() as $key => $net ) {
		$url = semrs_theme_opt( 'social_' . $key );
		if ( ! $url ) {
			continue;
		}
		$items .= sprintf(
			'<li><a class="sp-social__link sp-social--%1$s" href="%2$s" style="--sp-brand:%3$s" target="_blank" rel="noopener me"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="%4$s"/></svg><span class="screen-reader-text">%5$s</span></a></li>',
			esc_attr( $key ),
			esc_url( $url ),
			esc_attr( $net[1] ),
			esc_attr( $net[2] ),
			esc_html( $net[0] )
		);
	}
	if ( ! $items ) {
		return '';
	}
	$style = 'mono' === semrs_theme_opt( 'social_style' ) ? ' sp-social--mono' : '';
	return '<ul class="sp-social' . $style . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-label="' . esc_attr__( 'Social profiles', 'semrs' ) . '">' . $items . '</ul>';
}
