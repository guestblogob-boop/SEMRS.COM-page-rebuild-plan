<?php
/**
 * SEO fields for categories, tags and custom taxonomy terms: SEO title,
 * meta description and focus keyword (Posts → Categories → Edit), also
 * editable over the REST API. meta.php uses them on archive pages.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * The SEO fields stored on terms.
 *
 * @return array<string, string> meta key => label
 */
function semrs_seo_term_fields() {
	return array(
		'_semrs_title'       => __( 'SEO title', 'semrs-core' ),
		'_semrs_description' => __( 'Meta description', 'semrs-core' ),
		'_semrs_focus'       => __( 'Focus keyword', 'semrs-core' ),
	);
}

add_action(
	'init',
	function () {
		foreach ( array_keys( semrs_seo_term_fields() ) as $key ) {
			register_meta(
				'term',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => '_semrs_description' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field',
					'auth_callback'     => function ( $allowed, $meta_key, $term_id ) {
						return current_user_can( 'edit_term', $term_id );
					},
				)
			);
		}
	}
);

if ( ! is_admin() ) {
	return;
}

/**
 * Fields on the term edit screen.
 *
 * @param WP_Term $term Term.
 */
function semrs_seo_term_form( $term ) {
	wp_nonce_field( 'semrs_seo_term', 'semrs_seo_term_nonce' );
	echo '<tr class="form-field"><th colspan="2"><h2 style="margin:0">' . esc_html__( 'SEMRS SEO', 'semrs-core' ) . '</h2></th></tr>';
	foreach ( semrs_seo_term_fields() as $key => $label ) {
		$value = (string) get_term_meta( $term->term_id, $key, true );
		$id    = 'semrs' . $key;
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( '_semrs_description' === $key ) {
			echo '<textarea id="' . esc_attr( $id ) . '" name="semrs_term[' . esc_attr( $key ) . ']" rows="3" maxlength="300">' . esc_textarea( $value ) . '</textarea><p class="description">' . esc_html__( '120–155 characters. Leave empty to use the category description.', 'semrs-core' ) . '</p>';
		} else {
			echo '<input type="text" id="' . esc_attr( $id ) . '" name="semrs_term[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">';
			if ( '_semrs_title' === $key ) {
				echo '<p class="description">' . esc_html__( '30–60 characters. Leave empty to use the title template.', 'semrs-core' ) . '</p>';
			}
		}
		echo '</td></tr>';
	}
}

/**
 * Save the fields.
 *
 * @param int $term_id Term ID.
 */
function semrs_seo_term_save( $term_id ) {
	if ( ! isset( $_POST['semrs_seo_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_seo_term_nonce'] ) ), 'semrs_seo_term' ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	$in = isset( $_POST['semrs_term'] ) ? (array) wp_unslash( $_POST['semrs_term'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
	foreach ( array_keys( semrs_seo_term_fields() ) as $key ) {
		$value = '_semrs_description' === $key ? sanitize_textarea_field( $in[ $key ] ?? '' ) : sanitize_text_field( $in[ $key ] ?? '' );
		if ( '' === $value ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $value );
		}
	}
}

add_action(
	'admin_init',
	function () {
		foreach ( get_taxonomies( array( 'public' => true ) ) as $tax ) {
			add_action( $tax . '_edit_form_fields', 'semrs_seo_term_form' );
			add_action( 'edited_' . $tax, 'semrs_seo_term_save' );
		}
	}
);
