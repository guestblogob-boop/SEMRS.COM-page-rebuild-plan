<?php
/**
 * Free blog features: previous/next links, back to top, reading progress,
 * "Updated on" date, author box and related posts.
 *
 * SEMRS Pro adds the table of contents, mega menu and white label.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Previous / next links.
 */
function semrs_theme_post_nav() {
	if ( is_singular( 'post' ) && semrs_theme_opt( 'post_nav' ) ) {
		the_post_navigation(
			array(
				'prev_text' => '<span class="meta-nav">' . esc_html__( 'Previous', 'semrs' ) . '</span> %title',
				'next_text' => '<span class="meta-nav">' . esc_html__( 'Next', 'semrs' ) . '</span> %title',
			)
		);
	}
}
add_action( 'semrs_after_entry', 'semrs_theme_post_nav', 10 );

/**
 * Back-to-top button.
 */
function semrs_theme_to_top() {
	if ( semrs_theme_opt( 'to_top' ) ) {
		echo '<button type="button" class="sp-to-top" aria-label="' . esc_attr__( 'Back to top', 'semrs' ) . '" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4l-7 7 1.4 1.4L11 7.8V20h2V7.8l4.6 4.6L19 11z"/></svg></button>';
	}
}
add_action( 'wp_footer', 'semrs_theme_to_top', 5 );

/**
 * Reading progress bar markup (posts).
 */
function semrs_theme_progress_bar() {
	if ( is_singular( 'post' ) && semrs_theme_opt( 'progress' ) ) {
		echo '<div class="sp-progress" aria-hidden="true"><span></span></div>';
	}
}
add_action( 'wp_body_open', 'semrs_theme_progress_bar', 5 );

/**
 * "Updated on" line before post content (only when changed a day or more later).
 */
function semrs_theme_updated_date() {
	if ( ! is_singular( 'post' ) || ! semrs_theme_opt( 'updated_date' ) ) {
		return;
	}
	$id = get_the_ID();
	if ( get_the_modified_date( 'U', $id ) > get_the_date( 'U', $id ) + DAY_IN_SECONDS ) {
		printf(
			'<p class="sp-updated">%s <time datetime="%s">%s</time></p>',
			esc_html__( 'Updated on', 'semrs' ),
			esc_attr( get_the_modified_date( 'c', $id ) ),
			esc_html( get_the_modified_date( '', $id ) )
		);
	}
}
add_action( 'semrs_before_entry_content', 'semrs_theme_updated_date' );

/**
 * Author box after post content. "Reviewed by" comes from the
 * semrs_reviewed_by custom field when set.
 */
function semrs_theme_author_box() {
	if ( ! is_singular( 'post' ) || ! semrs_theme_opt( 'author_box' ) ) {
		return;
	}
	$id     = get_the_ID();
	$author = (int) get_post_field( 'post_author', $id );
	if ( ! $author || ! get_the_author_meta( 'display_name', $author ) ) {
		return;
	}
	$reviewer = get_post_meta( $id, 'semrs_reviewed_by', true );
	printf(
		'<aside class="sp-author-box" aria-label="%1$s">%2$s<div><p class="sp-author-name">%3$s <a href="%4$s">%5$s</a></p>%6$s%7$s</div></aside>',
		esc_attr__( 'About the author', 'semrs' ),
		get_avatar( $author, 72, '', '', array( 'loading' => 'lazy' ) ),
		esc_html__( 'Written by', 'semrs' ),
		esc_url( get_author_posts_url( $author ) ),
		esc_html( get_the_author_meta( 'display_name', $author ) ),
		wpautop( esc_html( get_the_author_meta( 'description', $author ) ) ),
		$reviewer ? '<p class="sp-reviewer">' . esc_html__( 'Reviewed by', 'semrs' ) . ' ' . esc_html( $reviewer ) . '</p>' : ''
	);
}
add_action( 'semrs_after_entry_content', 'semrs_theme_author_box' );

/**
 * Related posts (same category) after single posts.
 */
function semrs_theme_related_posts() {
	if ( ! is_singular( 'post' ) || ! semrs_theme_opt( 'related' ) ) {
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
	echo '<section class="sp-related" aria-labelledby="sp-related-title"><h2 id="sp-related-title">' . esc_html__( 'Related articles', 'semrs' ) . '</h2><div class="sp-posts">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/card', null, array( 'excerpt' => false ) );
	}
	echo '</div></section>';
	wp_reset_postdata();
}
add_action( 'semrs_after_entry', 'semrs_theme_related_posts', 20 );
