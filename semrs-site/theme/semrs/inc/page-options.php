<?php
/**
 * "Page options" box: hide title, hide header/footer, transparent header,
 * per-page sidebar override.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the box.
 */
function semrs_theme_page_options_box() {
	foreach ( array( 'page', 'post' ) as $type ) {
		add_meta_box( 'semrs_theme_page_options', __( 'Page options', 'semrs' ), 'semrs_theme_render_page_options', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'semrs_theme_page_options_box' );

/**
 * Render.
 *
 * @param WP_Post $post Post.
 */
function semrs_theme_render_page_options( $post ) {
	wp_nonce_field( 'semrs_theme_page_options', 'semrs_theme_po_nonce' );
	$layout = get_post_meta( $post->ID, 'semrs_layout', true );
	if ( 'page' === $post->post_type ) {
		printf(
			'<p><label><input type="checkbox" name="semrs_hide_title" value="1" %s> %s</label></p>',
			checked( (bool) get_post_meta( $post->ID, 'semrs_hide_title', true ), true, false ),
			esc_html__( 'Hide the page title (your content must then include an H1)', 'semrs' )
		);
	}
	foreach ( array( 'header' => __( 'Hide the header on this page', 'semrs' ), 'footer' => __( 'Hide the footer on this page', 'semrs' ) ) as $part => $label ) {
		printf( '<p><label><input type="checkbox" name="semrs_hide_%1$s" value="1" %2$s> %3$s</label></p>', esc_attr( $part ), checked( (bool) get_post_meta( $post->ID, 'semrs_hide_' . $part, true ), true, false ), esc_html( $label ) );
	}
	$trans = get_post_meta( $post->ID, 'semrs_transparent', true );
	echo '<p><label for="semrs_transparent"><strong>' . esc_html__( 'Transparent header', 'semrs' ) . '</strong></label><br><select id="semrs_transparent" name="semrs_transparent">';
	foreach ( array( '' => __( 'Default (Customizer)', 'semrs' ), 'on' => __( 'On', 'semrs' ), 'off' => __( 'Off', 'semrs' ) ) as $val => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $trans, $val, false ), esc_html( $label ) );
	}
	echo '</select></p>';
	echo '<p><label for="semrs_layout"><strong>' . esc_html__( 'Sidebar', 'semrs' ) . '</strong></label><br><select id="semrs_layout" name="semrs_layout">';
	foreach ( array( '' => __( 'Default (Customizer)', 'semrs' ), 'right' => __( 'Right sidebar', 'semrs' ), 'left' => __( 'Left sidebar', 'semrs' ), 'none' => __( 'No sidebar', 'semrs' ) ) as $val => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $layout, $val, false ), esc_html( $label ) );
	}
	echo '</select></p>';
}

/**
 * Save.
 *
 * @param int $post_id Post ID.
 */
function semrs_theme_save_page_options( $post_id ) {
	if ( ! isset( $_POST['semrs_theme_po_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_theme_po_nonce'] ) ), 'semrs_theme_page_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( empty( $_POST['semrs_hide_title'] ) ) {
		delete_post_meta( $post_id, 'semrs_hide_title' );
	} else {
		update_post_meta( $post_id, 'semrs_hide_title', '1' );
	}
	foreach ( array( 'header', 'footer' ) as $part ) {
		if ( empty( $_POST[ 'semrs_hide_' . $part ] ) ) {
			delete_post_meta( $post_id, 'semrs_hide_' . $part );
		} else {
			update_post_meta( $post_id, 'semrs_hide_' . $part, '1' );
		}
	}
	$trans = isset( $_POST['semrs_transparent'] ) ? sanitize_key( $_POST['semrs_transparent'] ) : '';
	if ( in_array( $trans, array( 'on', 'off' ), true ) ) {
		update_post_meta( $post_id, 'semrs_transparent', $trans );
	} else {
		delete_post_meta( $post_id, 'semrs_transparent' );
	}
	$layout = isset( $_POST['semrs_layout'] ) ? sanitize_key( $_POST['semrs_layout'] ) : '';
	if ( in_array( $layout, array( 'right', 'left', 'none' ), true ) ) {
		update_post_meta( $post_id, 'semrs_layout', $layout );
	} else {
		delete_post_meta( $post_id, 'semrs_layout' );
	}
}
add_action( 'save_post', 'semrs_theme_save_page_options' );

/**
 * Apply the per-page sidebar override.
 *
 * @param string $layout Layout.
 * @return string
 */
function semrs_theme_page_layout_override( $layout ) {
	if ( is_singular() ) {
		$own = get_post_meta( get_queried_object_id(), 'semrs_layout', true );
		if ( in_array( $own, array( 'right', 'left', 'none' ), true ) ) {
			return ( 'none' !== $own && ! is_active_sidebar( 'sidebar-1' ) ) ? 'none' : $own;
		}
	}
	return $layout;
}
add_filter( 'semrs_theme_layout', 'semrs_theme_page_layout_override' );
