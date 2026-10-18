<?php
/**
 * Proof content: Case Studies, Portfolio, Testimonials.
 *
 * Brand rule: no proof without written permission. Case studies and
 * portfolio items cannot be published until permission is confirmed;
 * testimonials only display with permission AND a source link.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the three post types.
 */
function semrs_core_register_proof_types() {
	register_post_type(
		'semrs_case_study',
		array(
			'labels'       => array(
				'name'          => __( 'Case Studies', 'semrs-pro' ),
				'singular_name' => __( 'Case Study', 'semrs-pro' ),
				'add_new_item'  => __( 'Add case study', 'semrs-pro' ),
			),
			'public'       => true,
			'has_archive'  => 'case-studies',
			'rewrite'      => array( 'slug' => 'case-studies', 'with_front' => false ),
			'menu_icon'    => 'dashicons-chart-line',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		)
	);

	register_post_type(
		'semrs_portfolio',
		array(
			'labels'       => array(
				'name'          => __( 'Portfolio', 'semrs-pro' ),
				'singular_name' => __( 'Portfolio Item', 'semrs-pro' ),
				'add_new_item'  => __( 'Add portfolio item', 'semrs-pro' ),
			),
			'public'       => true,
			'has_archive'  => 'portfolio',
			'rewrite'      => array( 'slug' => 'portfolio', 'with_front' => false ),
			'menu_icon'    => 'dashicons-portfolio',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		)
	);

	register_post_type(
		'semrs_testimonial',
		array(
			'labels'             => array(
				'name'          => __( 'Testimonials', 'semrs-pro' ),
				'singular_name' => __( 'Testimonial', 'semrs-pro' ),
				'add_new_item'  => __( 'Add testimonial', 'semrs-pro' ),
			),
			'public'             => false,
			'show_ui'            => true,
			'publicly_queryable' => false,
			'menu_icon'          => 'dashicons-format-quote',
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor', 'revisions' ),
		)
	);
}
add_action( 'init', 'semrs_core_register_proof_types' );

/**
 * Meta fields per post type: key => label.
 *
 * @return array<string, array<string, string>>
 */
function semrs_core_proof_fields() {
	return array(
		'semrs_case_study'  => array(
			'semrs_client'     => __( 'Client name', 'semrs-pro' ),
			'semrs_industry'   => __( 'Industry', 'semrs-pro' ),
			'semrs_country'    => __( 'Country', 'semrs-pro' ),
			'semrs_evidence'   => __( 'Evidence link (GSC/GA4 screenshot folder, report)', 'semrs-pro' ),
		),
		'semrs_portfolio'   => array(
			'semrs_client'     => __( 'Client name', 'semrs-pro' ),
			'semrs_live_url'   => __( 'Live site URL', 'semrs-pro' ),
			'semrs_services'   => __( 'Services delivered', 'semrs-pro' ),
		),
		'semrs_testimonial' => array(
			'semrs_person'     => __( 'Person name', 'semrs-pro' ),
			'semrs_company'    => __( 'Company / role', 'semrs-pro' ),
			'semrs_source_url' => __( 'Source link (Google review, LinkedIn, email on file)', 'semrs-pro' ),
		),
	);
}

/**
 * Meta box.
 */
function semrs_core_proof_metabox() {
	foreach ( array_keys( semrs_core_proof_fields() ) as $type ) {
		add_meta_box( 'semrs_proof', __( 'Proof & permission', 'semrs-pro' ), 'semrs_core_render_proof_metabox', $type, 'side', 'high' );
	}
}
add_action( 'add_meta_boxes', 'semrs_core_proof_metabox' );

/**
 * Render the meta box.
 *
 * @param WP_Post $post Post.
 */
function semrs_core_render_proof_metabox( $post ) {
	$fields = semrs_core_proof_fields();
	wp_nonce_field( 'semrs_proof_save', 'semrs_proof_nonce' );
	foreach ( $fields[ $post->post_type ] as $key => $label ) {
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="text" class="widefat" id="%1$s" name="%1$s" value="%3$s"></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( get_post_meta( $post->ID, $key, true ) )
		);
	}
	printf(
		'<p><label><input type="checkbox" name="semrs_permission" value="1" %s> <strong>%s</strong></label></p><p class="description">%s</p>',
		checked( get_post_meta( $post->ID, 'semrs_permission', true ), '1', false ),
		esc_html__( 'Written client permission is on file', 'semrs-pro' ),
		esc_html__( 'Required before this can be published or shown. Brand rule: no proof without permission.', 'semrs-pro' )
	);
}

/**
 * Save meta and enforce the permission gate.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function semrs_core_save_proof( $post_id, $post ) {
	$fields = semrs_core_proof_fields();
	if ( ! isset( $fields[ $post->post_type ] ) || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( isset( $_POST['semrs_proof_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_proof_nonce'] ) ), 'semrs_proof_save' ) && current_user_can( 'edit_post', $post_id ) ) {
		foreach ( array_keys( $fields[ $post->post_type ] ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			if ( in_array( $key, array( 'semrs_evidence', 'semrs_live_url', 'semrs_source_url' ), true ) ) {
				$value = esc_url_raw( $value );
			}
			update_post_meta( $post_id, $key, $value );
		}
		update_post_meta( $post_id, 'semrs_permission', empty( $_POST['semrs_permission'] ) ? '0' : '1' );
	}

	// Gate: case studies and portfolio stay draft without permission.
	if ( 'semrs_testimonial' !== $post->post_type && 'publish' === $post->post_status && '1' !== get_post_meta( $post_id, 'semrs_permission', true ) ) {
		remove_action( 'save_post', 'semrs_core_save_proof', 10 );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		add_action( 'save_post', 'semrs_core_save_proof', 10, 2 );
		set_transient( 'semrs_proof_blocked_' . get_current_user_id(), 1, 60 );
	}
}
add_action( 'save_post', 'semrs_core_save_proof', 10, 2 );

/**
 * Notice after a blocked publish.
 */
function semrs_core_proof_notice() {
	$key = 'semrs_proof_blocked_' . get_current_user_id();
	if ( get_transient( $key ) ) {
		delete_transient( $key );
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Saved as draft: tick "Written client permission is on file" before publishing.', 'semrs-pro' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'semrs_core_proof_notice' );

/**
 * [semrs_testimonials count="3"]: only permitted testimonials with a source link.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function semrs_core_testimonials_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'count' => 3 ), $atts, 'semrs_testimonials' );
	$query = new WP_Query(
		array(
			'post_type'      => 'semrs_testimonial',
			'posts_per_page' => absint( $atts['count'] ),
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- small set.
				'relation' => 'AND',
				array( 'key' => 'semrs_permission', 'value' => '1' ),
				array( 'key' => 'semrs_source_url', 'value' => '', 'compare' => '!=' ),
			),
		)
	);
	if ( ! $query->have_posts() ) {
		return '';
	}
	$out = '<div class="semrs-testimonials">';
	foreach ( $query->posts as $p ) {
		$out .= sprintf(
			'<figure class="semrs-card semrs-testimonial"><blockquote>%1$s</blockquote><figcaption><cite>%2$s</cite>%3$s<a class="semrs-testimonial__source" href="%4$s" rel="nofollow noopener" target="_blank">%5$s</a></figcaption></figure>',
			wp_kses_post( wpautop( $p->post_content ) ),
			esc_html( get_post_meta( $p->ID, 'semrs_person', true ) ),
			get_post_meta( $p->ID, 'semrs_company', true ) ? ', ' . esc_html( get_post_meta( $p->ID, 'semrs_company', true ) ) : '',
			esc_url( get_post_meta( $p->ID, 'semrs_source_url', true ) ),
			esc_html__( 'View source', 'semrs-pro' )
		);
	}
	return $out . '</div>';
}
add_shortcode( 'semrs_testimonials', 'semrs_core_testimonials_shortcode' );

/**
 * [semrs_work type="case-studies|portfolio" count="6"]: card grid.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function semrs_core_work_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'type' => 'case-studies', 'count' => 6 ), $atts, 'semrs_work' );
	$type  = 'portfolio' === $atts['type'] ? 'semrs_portfolio' : 'semrs_case_study';
	$query = new WP_Query(
		array(
			'post_type'      => $type,
			'posts_per_page' => absint( $atts['count'] ),
			'no_found_rows'  => true,
		)
	);
	if ( ! $query->have_posts() ) {
		return '';
	}
	$out = '<div class="semrs-work-grid">';
	foreach ( $query->posts as $p ) {
		$out .= sprintf(
			'<article class="semrs-card"><a href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a><div class="semrs-card__body"><h3><a href="%1$s">%3$s</a></h3><p>%4$s</p></div></article>',
			esc_url( get_permalink( $p ) ),
			get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy' ) ),
			esc_html( get_the_title( $p ) ),
			esc_html( get_the_excerpt( $p ) )
		);
	}
	return $out . '</div>';
}
add_shortcode( 'semrs_work', 'semrs_core_work_shortcode' );
