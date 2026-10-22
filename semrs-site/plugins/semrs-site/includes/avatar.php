<?php
/**
 * Author photos from the media library: a user can pick an image (Users →
 * Profile → "Author photo") that replaces their Gravatar everywhere
 * WordPress shows an avatar, including the theme's author box under posts.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_meta(
			'user',
			'semrs_avatar_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function ( $allowed, $key, $user_id ) {
					return current_user_can( 'edit_user', $user_id );
				},
			)
		);
	}
);

/**
 * Use the chosen media library image as the avatar.
 *
 * @param array $args        Avatar data.
 * @param mixed $id_or_email User, ID, email or comment.
 * @return array
 */
function semrs_site_avatar_data( $args, $id_or_email ) {
	$user_id = 0;
	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( $id_or_email instanceof WP_User ) {
		$user_id = $id_or_email->ID;
	} elseif ( $id_or_email instanceof WP_Post ) {
		$user_id = (int) $id_or_email->post_author;
	} elseif ( $id_or_email instanceof WP_Comment ) {
		$user_id = (int) $id_or_email->user_id;
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user    = get_user_by( 'email', $id_or_email );
		$user_id = $user ? $user->ID : 0;
	}
	$att = $user_id ? (int) get_user_meta( $user_id, 'semrs_avatar_id', true ) : 0;
	if ( ! $att ) {
		return $args;
	}
	$size = max( 96, (int) ( $args['size'] ?? 96 ) * 2 ); // Sharp on high-density screens.
	$src  = wp_get_attachment_image_src( $att, array( $size, $size ) );
	if ( $src ) {
		$args['url']          = $src[0];
		$args['found_avatar'] = true;
		$args['alt']          = $args['alt'] ? $args['alt'] : get_the_author_meta( 'display_name', $user_id );
	}
	return $args;
}
add_filter( 'pre_get_avatar_data', 'semrs_site_avatar_data', 10, 2 );

if ( ! is_admin() ) {
	return;
}

/**
 * Profile field.
 *
 * @param WP_User $user User.
 */
function semrs_site_avatar_field( $user ) {
	$att = (int) get_user_meta( $user->ID, 'semrs_avatar_id', true );
	wp_enqueue_media();
	?>
	<h2><?php esc_html_e( 'Author photo', 'semrs-site' ); ?></h2>
	<table class="form-table" role="presentation"><tr><th><?php esc_html_e( 'Photo', 'semrs-site' ); ?></th><td>
		<?php wp_nonce_field( 'semrs_avatar', 'semrs_avatar_nonce' ); ?>
		<input type="hidden" name="semrs_avatar_id" id="semrs-avatar-id" value="<?php echo esc_attr( $att ); ?>">
		<div id="semrs-avatar-preview" style="margin-bottom:8px"><?php echo $att ? wp_get_attachment_image( $att, array( 96, 96 ), false, array( 'style' => 'border-radius:50%' ) ) : ''; ?></div>
		<button type="button" class="button" id="semrs-avatar-pick"><?php esc_html_e( 'Choose from media library', 'semrs-site' ); ?></button>
		<button type="button" class="button-link" id="semrs-avatar-clear"><?php esc_html_e( 'Use Gravatar', 'semrs-site' ); ?></button>
		<p class="description"><?php esc_html_e( 'Shown in the author box under posts and anywhere WordPress shows your avatar. A square photo of at least 300 × 300 pixels works best.', 'semrs-site' ); ?></p>
	</td></tr></table>
	<script>
	(function () {
		var f;
		document.getElementById('semrs-avatar-pick').addEventListener('click', function () {
			f = f || wp.media({ title: <?php echo wp_json_encode( __( 'Author photo', 'semrs-site' ) ); ?>, library: { type: 'image' }, multiple: false });
			f.off('select').on('select', function () {
				var a = f.state().get('selection').first().toJSON();
				document.getElementById('semrs-avatar-id').value = a.id;
				var url = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
				document.getElementById('semrs-avatar-preview').innerHTML = '<img src="' + url + '" width="96" height="96" style="border-radius:50%" alt="">';
			});
			f.open();
		});
		document.getElementById('semrs-avatar-clear').addEventListener('click', function () {
			document.getElementById('semrs-avatar-id').value = '';
			document.getElementById('semrs-avatar-preview').innerHTML = '';
		});
	})();
	</script>
	<?php
}
add_action( 'show_user_profile', 'semrs_site_avatar_field' );
add_action( 'edit_user_profile', 'semrs_site_avatar_field' );

/**
 * Save the profile field.
 *
 * @param int $user_id User ID.
 */
function semrs_site_avatar_save( $user_id ) {
	if ( ! isset( $_POST['semrs_avatar_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_avatar_nonce'] ) ), 'semrs_avatar' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$att = absint( $_POST['semrs_avatar_id'] ?? 0 );
	if ( $att && wp_attachment_is_image( $att ) ) {
		update_user_meta( $user_id, 'semrs_avatar_id', $att );
	} else {
		delete_user_meta( $user_id, 'semrs_avatar_id' );
	}
}
add_action( 'personal_options_update', 'semrs_site_avatar_save' );
add_action( 'edit_user_profile_update', 'semrs_site_avatar_save' );
