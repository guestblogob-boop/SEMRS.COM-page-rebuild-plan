<?php
/**
 * User roles (free): Users → Roles lets an administrator change what each
 * role can do, add a role copied from an existing one, add a custom
 * capability and delete roles created here. A user's profile can give
 * them extra roles next to their main role.
 *
 * Safety: the Administrator role cannot be edited or deleted, only roles
 * created on this screen can be deleted (their users move to the default
 * role), and every change needs a nonce and the manage_options capability.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_admin() ) {
	return;
}

/**
 * Roles created on this screen (the only ones that may be deleted).
 *
 * @return string[]
 */
function semrs_roles_created() {
	return array_values( array_filter( (array) get_option( 'semrs_roles_created', array() ), 'is_string' ) );
}

/**
 * Every capability known to any role, sorted, grouped by a simple prefix.
 *
 * @return array<string, string[]>
 */
function semrs_roles_all_caps() {
	$caps = array();
	foreach ( wp_roles()->roles as $role ) {
		foreach ( array_keys( (array) $role['capabilities'] ) as $cap ) {
			if ( ! preg_match( '/^level_\d+$/', $cap ) ) {
				$caps[ $cap ] = true;
			}
		}
	}
	foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $type ) {
		foreach ( (array) $type->cap as $cap ) {
			$caps[ $cap ] = true;
		}
	}
	ksort( $caps );
	$groups = array();
	foreach ( array_keys( $caps ) as $cap ) {
		if ( preg_match( '/(post|page|block|template|navigation|pattern)s?\b|_posts?$|_pages?$/', $cap ) ) {
			$group = __( 'Posts and pages', 'semrs-core' );
		} elseif ( preg_match( '/user|role|promote/', $cap ) ) {
			$group = __( 'Users', 'semrs-core' );
		} elseif ( preg_match( '/plugin|theme|update|install|core|export|import|options|customize|files|network|site/', $cap ) ) {
			$group = __( 'Site administration', 'semrs-core' );
		} elseif ( preg_match( '/categor|term|tag|link|comment|upload|unfiltered|read/', $cap ) ) {
			$group = __( 'Content and media', 'semrs-core' );
		} else {
			$group = __( 'Other (plugins and custom)', 'semrs-core' );
		}
		$groups[ $group ][] = $cap;
	}
	return $groups;
}

add_action(
	'admin_menu',
	function () {
		add_users_page( __( 'Roles', 'semrs-core' ), __( 'Roles', 'semrs-core' ), 'manage_options', 'semrs-roles', 'semrs_roles_page' );
	}
);

/**
 * Handle the role forms.
 */
function semrs_roles_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'semrs-core' ) );
	}
	check_admin_referer( 'semrs_roles' );
	$in   = wp_unslash( $_POST );
	$role = sanitize_key( $in['role'] ?? '' );
	$do   = sanitize_key( $in['do'] ?? '' );
	$msg  = 'saved';

	if ( 'add' === $do ) {
		$name = sanitize_text_field( $in['name'] ?? '' );
		$slug = sanitize_key( str_replace( ' ', '_', strtolower( $name ) ) );
		$from = get_role( sanitize_key( $in['copy'] ?? 'subscriber' ) );
		if ( '' === $slug || get_role( $slug ) ) {
			$msg = 'exists';
		} else {
			$caps = $from ? $from->capabilities : array( 'read' => true );
			if ( 'administrator' === ( $in['copy'] ?? '' ) ) {
				$caps = array( 'read' => true ); // Never clone full admin rights by accident.
			}
			add_role( $slug, $name, $caps );
			update_option( 'semrs_roles_created', array_values( array_unique( array_merge( semrs_roles_created(), array( $slug ) ) ) ), false );
			$role = $slug;
			$msg  = 'added';
		}
	} elseif ( 'delete' === $do && in_array( $role, semrs_roles_created(), true ) ) {
		$fallback = get_option( 'default_role', 'subscriber' );
		$fallback = $fallback === $role ? 'subscriber' : $fallback;
		foreach ( get_users( array( 'role' => $role, 'fields' => 'ID' ) ) as $uid ) {
			$user = new WP_User( (int) $uid );
			$user->remove_role( $role );
			if ( ! $user->roles ) {
				$user->add_role( $fallback );
			}
		}
		remove_role( $role );
		update_option( 'semrs_roles_created', array_values( array_diff( semrs_roles_created(), array( $role ) ) ), false );
		$role = 'editor';
		$msg  = 'deleted';
	} elseif ( 'caps' === $do && 'administrator' !== $role && ( $obj = get_role( $role ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
		$want = array_map( 'sanitize_key', (array) ( $in['caps'] ?? array() ) );
		$new  = sanitize_key( $in['new_cap'] ?? '' );
		if ( $new ) {
			$want[] = $new;
		}
		$known = array();
		foreach ( semrs_roles_all_caps() as $list ) {
			$known = array_merge( $known, $list );
		}
		if ( $new ) {
			$known[] = $new;
		}
		foreach ( array_unique( $known ) as $cap ) {
			$has = ! empty( $obj->capabilities[ $cap ] );
			$set = in_array( $cap, $want, true );
			if ( $set && ! $has ) {
				$obj->add_cap( $cap );
			} elseif ( ! $set && $has ) {
				$obj->remove_cap( $cap );
			}
		}
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-roles', 'role' => $role, 'msg' => $msg ), admin_url( 'users.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_roles', 'semrs_roles_save' );

/**
 * Roles screen.
 */
function semrs_roles_page() {
	$roles   = wp_roles()->get_names();
	$current = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : 'editor'; // phpcs:ignore WordPress.Security.NonceVerification -- navigation.
	$current = isset( $roles[ $current ] ) ? $current : 'editor';
	$obj     = get_role( $current );
	$locked  = 'administrator' === $current;
	$msg     = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	$notes   = array(
		'saved'   => __( 'Role saved.', 'semrs-core' ),
		'added'   => __( 'Role added.', 'semrs-core' ),
		'deleted' => __( 'Role deleted; its users now have the default role.', 'semrs-core' ),
		'exists'  => __( 'A role with that name already exists.', 'semrs-core' ),
	);
	$count   = count_users();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Roles', 'semrs-core' ); ?></h1>
		<?php if ( isset( $notes[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo 'exists' === $msg ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $notes[ $msg ] ); ?></p></div>
		<?php endif; ?>
		<form method="get" style="margin:12px 0">
			<input type="hidden" name="page" value="semrs-roles">
			<label for="semrs-role"><strong><?php esc_html_e( 'Role:', 'semrs-core' ); ?></strong></label>
			<select id="semrs-role" name="role" onchange="this.form.submit()">
				<?php foreach ( $roles as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $current ); ?>><?php echo esc_html( translate_user_role( $name ) . ' (' . (int) ( $count['avail_roles'][ $slug ] ?? 0 ) . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<noscript><?php submit_button( __( 'Show', 'semrs-core' ), 'secondary', '', false ); ?></noscript>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_roles' ); ?>
			<input type="hidden" name="action" value="semrs_roles">
			<input type="hidden" name="do" value="caps">
			<input type="hidden" name="role" value="<?php echo esc_attr( $current ); ?>">
			<?php if ( $locked ) : ?>
				<p class="description"><?php esc_html_e( 'The Administrator role always keeps every capability, so it cannot be edited here.', 'semrs-core' ); ?></p>
			<?php endif; ?>
			<?php foreach ( semrs_roles_all_caps() as $group => $caps ) : ?>
				<fieldset style="margin:14px 0;padding:10px 14px;border:1px solid #dcdcde;background:#fff;max-width:1100px">
					<legend style="font-weight:600;padding:0 6px"><?php echo esc_html( $group ); ?></legend>
					<div style="columns:260px">
						<?php foreach ( $caps as $cap ) : ?>
							<label style="display:block;break-inside:avoid;padding:2px 0"><input type="checkbox" name="caps[]" value="<?php echo esc_attr( $cap ); ?>" <?php checked( ! empty( $obj->capabilities[ $cap ] ) ); ?> <?php disabled( $locked ); ?>> <code><?php echo esc_html( $cap ); ?></code></label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>
			<?php if ( ! $locked ) : ?>
				<p><label><?php esc_html_e( 'Add a capability:', 'semrs-core' ); ?> <input type="text" name="new_cap" class="regular-text code" placeholder="manage_bookings"></label></p>
				<?php submit_button( __( 'Save role', 'semrs-core' ) ); ?>
			<?php endif; ?>
		</form>

		<?php if ( in_array( $current, semrs_roles_created(), true ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm(this.dataset.ask)" data-ask="<?php esc_attr_e( 'Delete this role? Its users get the default role.', 'semrs-core' ); ?>">
				<?php wp_nonce_field( 'semrs_roles' ); ?>
				<input type="hidden" name="action" value="semrs_roles">
				<input type="hidden" name="do" value="delete">
				<input type="hidden" name="role" value="<?php echo esc_attr( $current ); ?>">
				<?php submit_button( __( 'Delete this role', 'semrs-core' ), 'delete', 'submit', false ); ?>
			</form>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Add a role', 'semrs-core' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_roles' ); ?>
			<input type="hidden" name="action" value="semrs_roles">
			<input type="hidden" name="do" value="add">
			<label><?php esc_html_e( 'Name', 'semrs-core' ); ?> <input type="text" name="name" required class="regular-text"></label>
			<label><?php esc_html_e( 'copied from', 'semrs-core' ); ?>
				<select name="copy">
					<?php foreach ( $roles as $slug => $name ) : ?>
						<?php if ( 'administrator' !== $slug ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( 'subscriber', $slug ); ?>><?php echo esc_html( translate_user_role( $name ) ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</label>
			<?php submit_button( __( 'Add role', 'semrs-core' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Extra roles on the user profile (for users the current user may promote).
 *
 * @param WP_User $user User being edited.
 */
function semrs_roles_profile_field( $user ) {
	if ( ! current_user_can( 'promote_user', $user->ID ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$primary = $user->roles ? reset( $user->roles ) : '';
	?>
	<h2><?php esc_html_e( 'Extra roles', 'semrs-core' ); ?></h2>
	<table class="form-table" role="presentation"><tr><th><?php esc_html_e( 'Also give this user', 'semrs-core' ); ?></th><td>
		<?php wp_nonce_field( 'semrs_extra_roles', 'semrs_extra_roles_nonce' ); ?>
		<?php foreach ( get_editable_roles() as $slug => $role ) : ?>
			<?php if ( $slug !== $primary && 'administrator' !== $slug ) : ?>
				<label style="display:inline-block;margin:0 16px 6px 0"><input type="checkbox" name="semrs_extra_roles[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $user->roles, true ) ); ?>> <?php echo esc_html( translate_user_role( $role['name'] ) ); ?></label>
			<?php endif; ?>
		<?php endforeach; ?>
		<p class="description"><?php esc_html_e( 'The main role is set above; extra roles add their capabilities on top.', 'semrs-core' ); ?></p>
	</td></tr></table>
	<?php
}
add_action( 'edit_user_profile', 'semrs_roles_profile_field' );

/**
 * Save extra roles.
 *
 * @param int $user_id User ID.
 */
function semrs_roles_profile_save( $user_id ) {
	if ( ! isset( $_POST['semrs_extra_roles_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_extra_roles_nonce'] ) ), 'semrs_extra_roles' ) ) {
		return;
	}
	if ( ! current_user_can( 'promote_user', $user_id ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$user     = new WP_User( $user_id );
	$primary  = $user->roles ? reset( $user->roles ) : '';
	$editable = array_diff( array_keys( get_editable_roles() ), array( 'administrator', $primary ) );
	$want     = array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['semrs_extra_roles'] ?? array() ) ), $editable );
	foreach ( $editable as $slug ) {
		$has = in_array( $slug, $user->roles, true );
		if ( in_array( $slug, $want, true ) && ! $has ) {
			$user->add_role( $slug );
		} elseif ( ! in_array( $slug, $want, true ) && $has ) {
			$user->remove_role( $slug );
		}
	}
}
// After WordPress saves the main role (which would drop extra roles).
add_action( 'profile_update', 'semrs_roles_profile_save', 20 );
