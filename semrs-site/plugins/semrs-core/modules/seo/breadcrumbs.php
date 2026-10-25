<?php
/**
 * Breadcrumb trail (shared by the visible breadcrumbs and BreadcrumbList schema).
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb name of the current post: its "Breadcrumb title" (SEMRS SEO → Advanced) or the post title.
 *
 * @return string
 */
function semrs_seo_crumb_title() {
	$custom = trim( (string) semrs_seo_meta( get_queried_object_id(), 'breadcrumb_title' ) );
	return '' !== $custom ? $custom : get_the_title();
}

/**
 * Trail for the current request: list of [ name, url ].
 *
 * @return array<int, array{0:string,1:string}>
 */
function semrs_seo_breadcrumb_trail() {
	static $trail = null;
	if ( null !== $trail ) {
		return $trail;
	}
	$s     = semrs_seo_settings();
	$trail = array( array( $s['breadcrumb_home'], home_url( '/' ) ) );

	if ( is_front_page() ) {
		return $trail;
	}

	$add_term_chain = function ( $term ) use ( &$trail ) {
		$chain = array();
		while ( $term && ! is_wp_error( $term ) ) {
			array_unshift( $chain, array( $term->name, get_term_link( $term ) ) );
			$term = $term->parent ? get_term( $term->parent, $term->taxonomy ) : null;
		}
		$trail = array_merge( $trail, $chain );
	};

	if ( is_home() ) {
		$page    = (int) get_option( 'page_for_posts' );
		$trail[] = array( $page ? get_the_title( $page ) : __( 'Blog', 'semrs-core' ), $page ? get_permalink( $page ) : home_url( '/' ) );
	} elseif ( is_singular( 'post' ) ) {
		$page = (int) get_option( 'page_for_posts' );
		if ( $page ) {
			$trail[] = array( get_the_title( $page ), get_permalink( $page ) );
		}
		$primary = (int) get_post_meta( get_queried_object_id(), '_semrs_primary_category', true );
		$primary = $primary ? $primary : (int) get_post_meta( get_queried_object_id(), 'rank_math_primary_category', true );
		$cat     = $primary ? get_term( $primary, 'category' ) : null;
		if ( ! $cat || is_wp_error( $cat ) ) {
			$cats = get_the_category( get_queried_object_id() );
			$cat  = $cats ? $cats[0] : null;
		}
		if ( $cat ) {
			$add_term_chain( $cat );
		}
		$trail[] = array( semrs_seo_crumb_title(), get_permalink() );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $ancestor ) {
			$trail[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
		$trail[] = array( semrs_seo_crumb_title(), get_permalink() );
	} elseif ( is_singular() ) {
		$type = get_post_type_object( get_post_type() );
		if ( $type && $type->has_archive ) {
			$trail[] = array( $type->labels->name, get_post_type_archive_link( $type->name ) );
		}
		$trail[] = array( semrs_seo_crumb_title(), get_permalink() );
	} elseif ( is_post_type_archive() ) {
		$trail[] = array( post_type_archive_title( '', false ), get_post_type_archive_link( get_query_var( 'post_type' ) ) );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$add_term_chain( get_queried_object() );
	} elseif ( is_author() ) {
		$trail[] = array( get_the_author_meta( 'display_name', get_queried_object_id() ), get_author_posts_url( get_queried_object_id() ) );
	} elseif ( is_search() ) {
		/* translators: %s: search query */
		$trail[] = array( sprintf( __( 'Search: %s', 'semrs-core' ), get_search_query() ), get_search_link() );
	} elseif ( is_404() ) {
		$trail[] = array( __( 'Page not found', 'semrs-core' ), '' );
	} elseif ( is_date() ) {
		$trail[] = array( get_the_archive_title(), '' );
	}

	/**
	 * Filter the breadcrumb trail.
	 *
	 * @param array $trail List of [name, url].
	 */
	$trail = apply_filters( 'semrs_breadcrumb_trail', $trail );
	return $trail;
}

/**
 * Print visible breadcrumbs. Themes call this (SEMRS Pro does automatically).
 */
function semrs_seo_breadcrumbs() {
	if ( is_front_page() || ! semrs_seo_settings()['breadcrumbs'] ) {
		return;
	}
	$trail = semrs_seo_breadcrumb_trail();
	$last  = count( $trail ) - 1;
	echo '<nav class="semrs-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'semrs-core' ) . '"><ol>';
	foreach ( $trail as $i => $crumb ) {
		if ( $i === $last || '' === $crumb[1] ) {
			echo '<li aria-current="page">' . esc_html( $crumb[0] ) . '</li>';
		} else {
			echo '<li><a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a></li>';
		}
	}
	echo '</ol></nav>';
}
