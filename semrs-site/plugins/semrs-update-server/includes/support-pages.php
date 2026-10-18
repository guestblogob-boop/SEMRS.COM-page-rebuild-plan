<?php
/**
 * SEMRS Updates → Support pages: installs the user manual (content/support,
 * built by build-support.mjs) as DRAFT pages under /support/, so each page
 * can be reviewed before it is published. Existing pages are never changed.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function semrs_us_support_menu() {
	add_submenu_page( 'semrs-updates', __( 'Support pages', 'semrs-update-server' ), __( 'Support pages', 'semrs-update-server' ), 'manage_options', 'semrs-updates-support', 'semrs_us_support_page' );
}
add_action( 'admin_menu', 'semrs_us_support_menu', 20 );

/**
 * Manual pages from content/support/pages.json, in order.
 *
 * @return array slug => [title, description, order]
 */
function semrs_us_support_manifest() {
	$file  = SEMRS_US_DIR . 'content/support/pages.json';
	$pages = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! is_array( $pages ) ) {
		return array();
	}
	uasort(
		$pages,
		function ( $a, $b ) {
			return (int) $a['order'] - (int) $b['order'];
		}
	);
	return $pages;
}

/**
 * Install missing pages as drafts. The hub is /support/, the rest are children.
 *
 * @return int Pages created.
 */
function semrs_us_install_support_pages() {
	$made   = 0;
	$parent = 0;
	foreach ( semrs_us_support_manifest() as $slug => $p ) {
		$is_hub = 'support' === $slug;
		$path   = $is_hub ? 'support' : 'support/' . $slug;
		$found  = get_page_by_path( $path );
		if ( $found ) {
			if ( $is_hub ) {
				$parent = (int) $found->ID;
			}
			continue;
		}
		$id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $p['title'],
				'post_name'    => $slug,
				'post_parent'  => $is_hub ? 0 : $parent,
				'menu_order'   => (int) $p['order'],
				'post_content' => (string) file_get_contents( SEMRS_US_DIR . 'content/support/' . sanitize_file_name( $slug ) . '.html' ), // phpcs:ignore WordPress.WP.AlternativeFunctions
			)
		);
		if ( ! $id ) {
			continue;
		}
		$made++;
		if ( $is_hub ) {
			$parent = $id;
		}
		update_post_meta( $id, '_semrs_description', sanitize_text_field( $p['description'] ) );
		update_post_meta( $id, '_wp_page_template', 'default' );
	}
	return $made;
}

/**
 * Handle the install button.
 */
function semrs_us_support_install_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-update-server' ) );
	}
	check_admin_referer( 'semrs_us_support' );
	$n = semrs_us_install_support_pages();
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-updates-support', 'made' => $n ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_us_support', 'semrs_us_support_install_action' );

/**
 * Screen.
 */
function semrs_us_support_page() {
	$pages = semrs_us_support_manifest();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Support pages (user manual)', 'semrs-update-server' ); ?></h1>
		<?php if ( isset( $_GET['made'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p>
				<?php
				/* translators: %d: number of pages */
				printf( esc_html__( '%d draft pages created. Review each one, then publish.', 'semrs-update-server' ), (int) $_GET['made'] ); // phpcs:ignore WordPress.Security.NonceVerification
				?>
			</p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Creates the SEMRS user manual as draft pages under /support/. Pages that already exist are skipped and never overwritten.', 'semrs-update-server' ); ?></p>
		<table class="widefat striped" style="max-width:900px">
			<thead><tr><th><?php esc_html_e( 'Page', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Address', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-update-server' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $pages as $slug => $p ) : ?>
				<?php
				$path  = 'support' === $slug ? 'support' : 'support/' . $slug;
				$found = get_page_by_path( $path );
				?>
				<tr>
					<td><?php echo esc_html( $p['title'] ); ?></td>
					<td><code>/<?php echo esc_html( $path ); ?>/</code></td>
					<td><?php echo $found ? '<a href="' . esc_url( get_edit_post_link( $found->ID ) ) . '">' . esc_html( get_post_status_object( get_post_status( $found ) )->label ) . '</a>' : esc_html__( 'Not created', 'semrs-update-server' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px">
			<input type="hidden" name="action" value="semrs_us_support">
			<?php wp_nonce_field( 'semrs_us_support' ); ?>
			<?php submit_button( __( 'Create missing pages as drafts', 'semrs-update-server' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
