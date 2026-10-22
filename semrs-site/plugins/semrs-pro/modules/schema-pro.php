<?php
/**
 * Custom schema (Pro): add your own JSON-LD to any post or page (Event,
 * Product, Course, Recipe, HowTo, Review of a product you sell, …) from a
 * box in the editor, with ready-to-fill starters. It is checked as JSON on
 * save and printed in the page head next to the SEMRS or Rank Math graph.
 *
 * Google's rule still applies: mark up only what visitors can see on the
 * page.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the saved JSON-LD.
 */
function semrs_schema_pro_print() {
	if ( ! is_singular() ) {
		return;
	}
	$json = (string) get_post_meta( get_queried_object_id(), '_semrs_custom_schema', true );
	$data = $json ? json_decode( $json, true ) : null;
	if ( ! is_array( $data ) ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON with HEX_TAG.
}
add_action( 'wp_head', 'semrs_schema_pro_print', 30 );

if ( ! is_admin() ) {
	return;
}

/**
 * Starters (placeholders in CAPITALS).
 *
 * @return array<string, array>
 */
function semrs_schema_pro_starters() {
	$ctx = array( '@context' => 'https://schema.org' );
	return array(
		'Event'   => $ctx + array( '@type' => 'Event', 'name' => 'EVENT NAME', 'startDate' => '2026-12-01T10:00', 'endDate' => '2026-12-01T12:00', 'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode', 'eventStatus' => 'https://schema.org/EventScheduled', 'location' => array( '@type' => 'VirtualLocation', 'url' => 'JOIN URL' ), 'organizer' => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ) ),
		'Product' => $ctx + array( '@type' => 'Product', 'name' => 'PRODUCT NAME', 'description' => 'SHORT DESCRIPTION', 'image' => 'IMAGE URL', 'brand' => array( '@type' => 'Brand', 'name' => get_bloginfo( 'name' ) ), 'offers' => array( '@type' => 'Offer', 'price' => '0.00', 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock', 'url' => 'PAGE URL' ) ),
		'Course'  => $ctx + array( '@type' => 'Course', 'name' => 'COURSE NAME', 'description' => 'WHAT STUDENTS LEARN', 'provider' => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'sameAs' => home_url( '/' ) ) ),
		'HowTo'   => $ctx + array( '@type' => 'HowTo', 'name' => 'HOW TO …', 'step' => array( array( '@type' => 'HowToStep', 'text' => 'FIRST STEP' ), array( '@type' => 'HowToStep', 'text' => 'SECOND STEP' ) ) ),
		'Recipe'  => $ctx + array( '@type' => 'Recipe', 'name' => 'RECIPE NAME', 'image' => 'IMAGE URL', 'recipeIngredient' => array( 'INGREDIENT' ), 'recipeInstructions' => array( array( '@type' => 'HowToStep', 'text' => 'STEP' ) ) ),
		'VideoObject' => $ctx + array( '@type' => 'VideoObject', 'name' => 'VIDEO TITLE', 'description' => 'WHAT THE VIDEO SHOWS', 'thumbnailUrl' => 'THUMBNAIL URL', 'uploadDate' => '2026-10-21', 'contentUrl' => 'VIDEO URL' ),
	);
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			if ( 'attachment' !== $type ) {
				add_meta_box( 'semrs_custom_schema', __( 'Custom schema (JSON-LD)', 'semrs-pro' ), 'semrs_schema_pro_box', $type, 'normal', 'low' );
			}
		}
	}
);

/**
 * Editor box.
 *
 * @param WP_Post $post Post.
 */
function semrs_schema_pro_box( $post ) {
	$json  = (string) get_post_meta( $post->ID, '_semrs_custom_schema', true );
	$error = get_transient( 'semrs_schema_err_' . $post->ID );
	wp_nonce_field( 'semrs_custom_schema', 'semrs_custom_schema_nonce' );
	if ( $error ) {
		delete_transient( 'semrs_schema_err_' . $post->ID );
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The schema was not saved: it is not valid JSON.', 'semrs-pro' ) . '</p></div>';
	}
	?>
	<p>
		<label for="semrs-schema-starter"><?php esc_html_e( 'Start from:', 'semrs-pro' ); ?></label>
		<select id="semrs-schema-starter"><option value=""><?php esc_html_e( '— choose —', 'semrs-pro' ); ?></option>
			<?php foreach ( semrs_schema_pro_starters() as $name => $data ) : ?>
				<option value="<?php echo esc_attr( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener"><?php esc_html_e( 'Test in Google Rich Results', 'semrs-pro' ); ?></a>
	</p>
	<textarea id="semrs-custom-schema" name="semrs_custom_schema" rows="10" class="large-text code" spellcheck="false"><?php echo esc_textarea( $json ); ?></textarea>
	<p class="description"><?php esc_html_e( 'One JSON-LD object (or a list). Replace every CAPITALISED placeholder, and only describe what visitors can see on this page. Leave empty for none.', 'semrs-pro' ); ?></p>
	<script>
	document.getElementById('semrs-schema-starter').addEventListener('change', function () {
		var t = document.getElementById('semrs-custom-schema');
		if (this.value && (!t.value.trim() || confirm(<?php echo wp_json_encode( __( 'Replace the current schema?', 'semrs-pro' ) ); ?>))) { t.value = this.value; }
		this.value = '';
	});
	</script>
	<?php
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['semrs_custom_schema_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_custom_schema_nonce'] ) ), 'semrs_custom_schema' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = trim( (string) wp_unslash( $_POST['semrs_custom_schema'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as JSON below.
		if ( '' === $raw ) {
			delete_post_meta( $post_id, '_semrs_custom_schema' );
			return;
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			set_transient( 'semrs_schema_err_' . $post_id, 1, 300 );
			return;
		}
		update_post_meta( $post_id, '_semrs_custom_schema', wp_slash( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	}
);
