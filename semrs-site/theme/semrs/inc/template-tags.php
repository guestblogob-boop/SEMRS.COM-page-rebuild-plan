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

/**
 * A person's profile links as the same brand icons the header uses. Links come from the
 * author's profile (SEMRS Core → "SEO author profile" → Profile links, and the Website field);
 * each address is matched to its network by domain. Other sites get a globe icon.
 *
 * @param int    $user_id User ID.
 * @param string $class   Extra wrapper class.
 * @return string
 */
function semrs_theme_author_social( $user_id, $class = 'sp-author-social' ) {
	$links = array_filter( array_map( 'trim', preg_split( '/\R/', (string) get_user_meta( $user_id, '_semrs_author_same_as', true ) ) ) );
	$site  = (string) get_the_author_meta( 'user_url', $user_id );
	if ( $site && untrailingslashit( $site ) !== untrailingslashit( home_url() ) ) {
		$links[] = $site;
	}
	$links = (array) apply_filters( 'semrs_theme_author_links', array_values( array_unique( $links ) ), $user_id );
	if ( ! $links ) {
		return '';
	}
	$hosts = array(
		'facebook'  => '/(^|\.)(facebook\.com|fb\.com)$/',
		'x'         => '/(^|\.)(x\.com|twitter\.com)$/',
		'instagram' => '/(^|\.)instagram\.com$/',
		'linkedin'  => '/(^|\.)linkedin\.com$/',
		'youtube'   => '/(^|\.)(youtube\.com|youtu\.be)$/',
		'tiktok'    => '/(^|\.)tiktok\.com$/',
		'pinterest' => '/(^|\.)pinterest\.[a-z.]+$/',
		'whatsapp'  => '/(^|\.)(wa\.me|whatsapp\.com)$/',
		'threads'   => '/(^|\.)threads\.(net|com)$/',
	);
	$nets  = semrs_theme_social_networks();
	$globe = array( __( 'Website', 'semrs' ), '#475569', 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.9 6h-2.9a15.7 15.7 0 0 0-1.4-3.6A8 8 0 0 1 18.9 8zM12 4c.8 1.2 1.5 2.5 1.9 4h-3.8c.4-1.5 1.1-2.8 1.9-4zM4.3 14a8.2 8.2 0 0 1 0-4h3.4a16.5 16.5 0 0 0 0 4H4.3zm.8 2h2.9c.3 1.3.8 2.5 1.4 3.6A8 8 0 0 1 5.1 16zM8 8H5.1a8 8 0 0 1 4.3-3.6C8.8 5.5 8.4 6.7 8 8zm4 12c-.8-1.2-1.5-2.5-1.9-4h3.8c-.4 1.5-1.1 2.8-1.9 4zm2.3-6H9.7a14.7 14.7 0 0 1 0-4h4.6a14.7 14.7 0 0 1 0 4zm.3 5.6c.6-1.1 1.1-2.3 1.4-3.6h2.9a8 8 0 0 1-4.3 3.6zm1.7-5.6a16.5 16.5 0 0 0 0-4h3.4a8.2 8.2 0 0 1 0 4h-3.4z' );
	$items = '';
	foreach ( $links as $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $host ) {
			continue;
		}
		$key = 'website';
		$net = $globe;
		foreach ( $hosts as $k => $re ) {
			if ( isset( $nets[ $k ] ) && preg_match( $re, $host ) ) {
				$key = $k;
				$net = $nets[ $k ];
				break;
			}
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
	if ( '' === $items ) {
		return '';
	}
	// Always the brand colors here: the "mono" header style is see-through, made for dark headers.
	/* translators: %s: person's name */
	return '<ul class="sp-social ' . esc_attr( $class ) . '" aria-label="' . esc_attr( sprintf( __( '%s on social media', 'semrs' ), get_the_author_meta( 'display_name', $user_id ) ) ) . '">' . $items . '</ul>';
}
