<?php
/**
 * "Page options" box: hide title, per-page sidebar override.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the box.
 */
function semrs_pro_page_options_box() {
	foreach ( array( 'page', 'post' ) as $type ) {
		add_meta_box( 'semrs_pro_page_options', __( 'Page options', 'semrs-pro' ), 'semrs_pro_render_page_options', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'semrs_pro_page_options_box' );

/**
 * Render.
 *
 * @param WP_Post $post Post.
 */
function semrs_pro_render_page_options( $post ) {
	wp_nonce_field( 'semrs_pro_page_options', 'semrs_pro_po_nonce' );
	$layout = get_post_meta( $post->ID, 'semrs_layout', true );
	if ( 'page' === $post->post_type ) {
		printf(
			'<p><label><input type="checkbox" name="semrs_hide_title" value="1" %s> %s</label></p>',
			checked( (bool) get_post_meta( $post->ID, 'semrs_hide_title', true ), true, false ),
			esc_html__( 'Hide the page title (your content must then include an H1)', 'semrs-pro' )
		);
	}
	echo '<p><label for="semrs_layout"><strong>' . esc_html__( 'Sidebar', 'semrs-pro' ) . '</strong></label><br><select id="semrs_layout" name="semrs_layout">';
	foreach ( array( '' => __( 'Default (Customizer)', 'semrs-pro' ), 'right' => __( 'Right sidebar', 'semrs-pro' ), 'left' => __( 'Left sidebar', 'semrs-pro' ), 'none' => __( 'No sidebar', 'semrs-pro' ) ) as $val => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $layout, $val, false ), esc_html( $label ) );
	}
	echo '</select></p>';
}

/**
 * Save.
 *
 * @param int $post_id Post ID.
 */
function semrs_pro_save_page_options( $post_id ) {
	if ( ! isset( $_POST['semrs_pro_po_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_pro_po_nonce'] ) ), 'semrs_pro_page_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( empty( $_POST['semrs_hide_title'] ) ) {
		delete_post_meta( $post_id, 'semrs_hide_title' );
	} else {
		update_post_meta( $post_id, 'semrs_hide_title', '1' );
	}
	$layout = isset( $_POST['semrs_layout'] ) ? sanitize_key( $_POST['semrs_layout'] ) : '';
	if ( in_array( $layout, array( 'right', 'left', 'none' ), true ) ) {
		update_post_meta( $post_id, 'semrs_layout', $layout );
	} else {
		delete_post_meta( $post_id, 'semrs_layout' );
	}
}
add_action( 'save_post', 'semrs_pro_save_page_options' );

/**
 * Apply the per-page sidebar override.
 *
 * @param string $layout Layout.
 * @return string
 */
function semrs_pro_page_layout_override( $layout ) {
	if ( is_singular() ) {
		$own = get_post_meta( get_queried_object_id(), 'semrs_layout', true );
		if ( in_array( $own, array( 'right', 'left', 'none' ), true ) ) {
			return ( 'none' !== $own && ! is_active_sidebar( 'sidebar-1' ) ) ? 'none' : $own;
		}
	}
	return $layout;
}
add_filter( 'semrs_pro_layout', 'semrs_pro_page_layout_override' );
