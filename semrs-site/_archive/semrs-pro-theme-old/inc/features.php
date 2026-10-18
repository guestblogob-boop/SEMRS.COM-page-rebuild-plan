<?php
/**
 * Blog features: table of contents, progress bar, updated date, author box,
 * related posts, post navigation, back to top.
 *
 * Content filter order: TOC (20) → SEMRS Ads in-content (22) → author box (25).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main single-post content being rendered?
 *
 * @return bool
 */
function semrs_pro_is_post_content() {
	return is_singular( 'post' ) && in_the_loop() && is_main_query();
}

/**
 * Progress bar markup.
 */
function semrs_pro_progress_bar() {
	if ( is_singular( 'post' ) && semrs_pro_opt( 'progress' ) ) {
		echo '<div class="sp-progress" aria-hidden="true"><span></span></div>';
	}
}
add_action( 'wp_body_open', 'semrs_pro_progress_bar', 5 );

/**
 * Table of contents from H2/H3, with ids added to headings.
 *
 * @param string $content Rendered content.
 * @return string
 */
function semrs_pro_toc( $content ) {
	if ( ! semrs_pro_is_post_content() || ! semrs_pro_opt( 'toc' ) || false !== strpos( $content, 'rank-math-toc' ) || false !== strpos( $content, 'sp-toc' ) ) {
		return $content;
	}
	if ( ! preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) || count( $matches ) < 3 ) {
		return $content;
	}
	$used  = array();
	$items = '';
	foreach ( $matches as $m ) {
		$level = (int) $m[1];
		$text  = wp_strip_all_tags( $m[3] );
		if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $m[2], $idm ) ) {
			$id = $idm[1];
		} else {
			$base = sanitize_title( $text );
			$base = '' === $base ? 'section' : $base;
			$id   = $base;
			for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
				$id = $base . '-' . $n;
			}
			$new     = '<h' . $level . $m[2] . ' id="' . esc_attr( $id ) . '">' . $m[3] . '</h' . $level . '>';
			$at      = strpos( $content, $m[0] );
			$content = false === $at ? $content : substr_replace( $content, $new, $at, strlen( $m[0] ) );
		}
		$used[ $id ] = true;
		$items      .= sprintf( '<li class="sp-toc-l%d"><a href="#%s">%s</a></li>', $level, esc_attr( $id ), esc_html( $text ) );
	}
	$toc = '<details class="sp-toc" open><summary>' . esc_html__( 'On this page', 'semrs-pro' ) . '</summary><ol>' . $items . '</ol></details>';
	$pos = strpos( $content, '</p>' );
	return false === $pos ? $toc . $content : substr_replace( $content, '</p>' . $toc, $pos, 4 );
}
add_filter( 'the_content', 'semrs_pro_toc', 20 );

/**
 * "Updated on" line before and author box after post content.
 *
 * @param string $content Content.
 * @return string
 */
function semrs_pro_post_extras( $content ) {
	if ( ! semrs_pro_is_post_content() ) {
		return $content;
	}
	$id     = get_the_ID();
	$before = '';
	$after  = '';
	if ( semrs_pro_opt( 'updated_date' ) && get_the_modified_date( 'U', $id ) > get_the_date( 'U', $id ) + DAY_IN_SECONDS ) {
		$before = sprintf(
			'<p class="sp-updated">%s <time datetime="%s">%s</time></p>',
			esc_html__( 'Updated on', 'semrs-pro' ),
			esc_attr( get_the_modified_date( 'c', $id ) ),
			esc_html( get_the_modified_date( '', $id ) )
		);
	}
	$author = (int) get_post_field( 'post_author', $id );
	if ( semrs_pro_opt( 'author_box' ) && $author && get_the_author_meta( 'display_name', $author ) ) {
		$reviewer = get_post_meta( $id, 'semrs_reviewed_by', true );
		$after    = sprintf(
			'<aside class="sp-author-box" aria-label="%1$s">%2$s<div><p class="sp-author-name">%3$s <a href="%4$s">%5$s</a></p>%6$s%7$s</div></aside>',
			esc_attr__( 'About the author', 'semrs-pro' ),
			get_avatar( $author, 72, '', '', array( 'loading' => 'lazy' ) ),
			esc_html__( 'Written by', 'semrs-pro' ),
			esc_url( get_author_posts_url( $author ) ),
			esc_html( get_the_author_meta( 'display_name', $author ) ),
			wpautop( esc_html( get_the_author_meta( 'description', $author ) ) ),
			$reviewer ? '<p class="sp-reviewer">' . esc_html__( 'Reviewed by', 'semrs-pro' ) . ' ' . esc_html( $reviewer ) . '</p>' : ''
		);
	}
	return $before . $content . $after;
}
add_filter( 'the_content', 'semrs_pro_post_extras', 25 );

/**
 * Related posts (same category) after single posts.
 */
function semrs_pro_related_posts() {
	if ( ! is_singular( 'post' ) || ! semrs_pro_opt( 'related' ) ) {
		return;
	}
	$cats = wp_get_post_categories( get_the_ID() );
	if ( ! $cats ) {
		return;
	}
	$q = new WP_Query(
		array(
			'category__in'           => $cats,
			'post__not_in'           => array( get_the_ID() ),
			'posts_per_page'         => 3,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);
	if ( ! $q->have_posts() ) {
		return;
	}
	echo '<section class="sp-related" aria-labelledby="sp-related-title"><h2 id="sp-related-title">' . esc_html__( 'Related articles', 'semrs-pro' ) . '</h2><div class="sp-posts">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/card', null, array( 'excerpt' => false ) );
	}
	echo '</div></section>';
	wp_reset_postdata();
}
add_action( 'semrs_after_entry', 'semrs_pro_related_posts', 20 );

/**
 * Previous / next links.
 */
function semrs_pro_post_nav() {
	if ( is_singular( 'post' ) && semrs_pro_opt( 'post_nav' ) ) {
		the_post_navigation(
			array(
				'prev_text' => '<span class="meta-nav">' . esc_html__( 'Previous', 'semrs-pro' ) . '</span> %title',
				'next_text' => '<span class="meta-nav">' . esc_html__( 'Next', 'semrs-pro' ) . '</span> %title',
			)
		);
	}
}
add_action( 'semrs_after_entry', 'semrs_pro_post_nav', 10 );

/**
 * Back-to-top button.
 */
function semrs_pro_to_top() {
	if ( semrs_pro_opt( 'to_top' ) ) {
		echo '<button type="button" class="sp-to-top" aria-label="' . esc_attr__( 'Back to top', 'semrs-pro' ) . '" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4l-7 7 1.4 1.4L11 7.8V20h2V7.8l4.6 4.6L19 11z"/></svg></button>';
	}
}
add_action( 'wp_footer', 'semrs_pro_to_top', 5 );
