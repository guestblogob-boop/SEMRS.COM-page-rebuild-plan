<?php
/**
 * Admin: Releases, License keys, Live sites.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function semrs_us_menu() {
	add_menu_page( __( 'SEMRS Updates', 'semrs-update-server' ), __( 'SEMRS Updates', 'semrs-update-server' ), 'manage_options', 'semrs-updates', 'semrs_us_page_releases', 'dashicons-update', 59 );
	add_submenu_page( 'semrs-updates', __( 'Releases', 'semrs-update-server' ), __( 'Releases', 'semrs-update-server' ), 'manage_options', 'semrs-updates', 'semrs_us_page_releases' );
	add_submenu_page( 'semrs-updates', __( 'License keys', 'semrs-update-server' ), __( 'License keys', 'semrs-update-server' ), 'manage_options', 'semrs-updates-keys', 'semrs_us_page_keys' );
	add_submenu_page( 'semrs-updates', __( 'Live sites', 'semrs-update-server' ), __( 'Live sites', 'semrs-update-server' ), 'manage_options', 'semrs-updates-sites', 'semrs_us_page_sites' );
}
add_action( 'admin_menu', 'semrs_us_menu' );

/**
 * Read slug + version from a package zip (folder name + header).
 *
 * @param string $zip_path Path to zip.
 * @return array{slug:string,version:string}|null
 */
function semrs_us_read_zip( $zip_path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return null;
	}
	$z = new ZipArchive();
	if ( true !== $z->open( $zip_path ) ) {
		return null;
	}
	$first = $z->getNameIndex( 0 );
	$slug  = $first ? strtok( str_replace( '\\', '/', $first ), '/' ) : '';
	$ver   = '';
	foreach ( array( $slug . '/style.css', $slug . '/' . $slug . '.php' ) as $f ) {
		$src = $z->getFromName( $f );
		if ( $src && preg_match( '/^[ \t\/*#@]*Version:\s*([0-9A-Za-z.\-]+)/mi', $src, $m ) ) {
			$ver = $m[1];
			break;
		}
	}
	$z->close();
	return $slug && $ver ? array( 'slug' => sanitize_key( $slug ), 'version' => $ver ) : null;
}

/**
 * Handle release upload / key actions.
 */
function semrs_us_actions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-update-server' ) );
	}
	check_admin_referer( 'semrs_us' );
	$do   = isset( $_POST['semrs_do'] ) ? sanitize_key( $_POST['semrs_do'] ) : '';
	$back = 'semrs-updates';
	$msg  = '';

	if ( 'release' === $do && ! empty( $_FILES['zip']['tmp_name'] ) ) {
		$tmp  = $_FILES['zip']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- moved, not output.
		$info = semrs_us_read_zip( $tmp );
		if ( ! $info || ! isset( semrs_us_products()[ $info['slug'] ] ) ) {
			$msg = 'badzip';
		} else {
			$name = $info['slug'] . '-' . $info['version'] . '-' . bin2hex( random_bytes( 16 ) ) . '.zip';
			if ( move_uploaded_file( $tmp, semrs_us_dir() . $name ) ) {
				semrs_us_save_release(
					$info['slug'],
					$info['version'],
					array(
						'file'         => $name,
						'changelog'    => isset( $_POST['changelog'] ) ? wp_kses_post( wp_unslash( $_POST['changelog'] ) ) : '',
						'notice'       => isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : '',
						'requires'     => isset( $_POST['requires'] ) ? sanitize_text_field( wp_unslash( $_POST['requires'] ) ) : '6.5',
						'tested'       => isset( $_POST['tested'] ) ? sanitize_text_field( wp_unslash( $_POST['tested'] ) ) : '',
						'requires_php' => isset( $_POST['requires_php'] ) ? sanitize_text_field( wp_unslash( $_POST['requires_php'] ) ) : '7.4',
					)
				);
				$msg = 'published-' . $info['slug'] . '-' . $info['version'];
			}
		}
	} elseif ( 'key_create' === $do ) {
		$key  = semrs_us_create_key(
			isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			isset( $_POST['sites'] ) ? absint( $_POST['sites'] ) : 1,
			isset( $_POST['expires'] ) ? sanitize_text_field( wp_unslash( $_POST['expires'] ) ) : ''
		);
		$msg  = 'key-' . $key;
		$back = 'semrs-updates-keys';
	} elseif ( 'key_status' === $do && isset( $_POST['key'] ) ) {
		$all = semrs_us_keys();
		$k   = sanitize_text_field( wp_unslash( $_POST['key'] ) );
		if ( isset( $all[ $k ] ) ) {
			$all[ $k ]['status'] = 'active' === $all[ $k ]['status'] ? 'revoked' : 'active';
			update_option( 'semrs_us_keys', $all, false );
		}
		$back = 'semrs-updates-keys';
	}
	wp_safe_redirect( add_query_arg( array( 'page' => $back, 'msg' => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_us', 'semrs_us_actions' );

/**
 * Releases page.
 */
function semrs_us_page_releases() {
	$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Releases', 'semrs-update-server' ); ?></h1>
		<?php if ( 0 === strpos( $msg, 'published-' ) ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( /* translators: %s: product and version */ __( 'Published %s. Every site using it will see "Update available" at its next update check (within about 12 hours, or right away with Dashboard → Updates → Check again).', 'semrs-update-server' ), substr( $msg, 10 ) ) ); ?></p></div>
		<?php elseif ( 'badzip' === $msg ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'That zip is not a known SEMRS package (folder name + Version header not found).', 'semrs-update-server' ); ?></p></div>
		<?php endif; ?>
		<h2><?php esc_html_e( 'Publish a new version', 'semrs-update-server' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'semrs_us' ); ?>
			<input type="hidden" name="action" value="semrs_us"><input type="hidden" name="semrs_do" value="release">
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Release zip', 'semrs-update-server' ); ?></th><td><input type="file" name="zip" accept=".zip" required><p class="description"><?php esc_html_e( 'From dist/releases/. Product and version are read from the zip.', 'semrs-update-server' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Changelog', 'semrs-update-server' ); ?></th><td><textarea name="changelog" class="large-text" rows="5" placeholder="<?php esc_attr_e( 'One change per line', 'semrs-update-server' ); ?>"></textarea></td></tr>
				<tr><th><?php esc_html_e( 'Upgrade notice (optional)', 'semrs-update-server' ); ?></th><td><input type="text" name="notice" class="large-text"></td></tr>
				<tr><th><?php esc_html_e( 'Requires WP / Tested up to / PHP', 'semrs-update-server' ); ?></th><td><input name="requires" value="6.5" size="5"> <input name="tested" value="<?php echo esc_attr( get_bloginfo( 'version' ) ); ?>" size="5"> <input name="requires_php" value="7.4" size="5"></td></tr>
			</table>
			<?php submit_button( __( 'Publish release', 'semrs-update-server' ) ); ?>
		</form>
		<h2><?php esc_html_e( 'Current versions', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped" style="max-width:900px"><thead><tr><th><?php esc_html_e( 'Product', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Latest', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Published', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Older versions', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php foreach ( semrs_us_products() as $slug => $p ) : $l = semrs_us_latest( $slug ); $all = semrs_us_releases(); ?>
			<tr><td><strong><?php echo esc_html( $p[0] ); ?></strong><?php echo $p[2] ? ' <em>(license)</em>' : ''; ?></td><td><?php echo esc_html( $l ? $l['version'] : '—' ); ?></td><td><?php echo esc_html( $l ? $l['date'] . ' UTC' : '' ); ?></td><td><?php echo esc_html( isset( $all[ $slug ] ) ? implode( ', ', array_diff( array_keys( $all[ $slug ] ), array( $l ? $l['version'] : '' ) ) ) : '' ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="description"><?php echo esc_html( sprintf( /* translators: %s: URL */ __( 'Update API: %s', 'semrs-update-server' ), rest_url( 'semrs-updates/v1/check' ) ) ); ?></p>
	</div>
	<?php
}

/**
 * Keys page.
 */
function semrs_us_page_keys() {
	$msg = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	global $wpdb;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Pro license keys', 'semrs-update-server' ); ?></h1>
		<?php if ( 0 === strpos( $msg, 'key-' ) ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'New key:', 'semrs-update-server' ); ?> <code><?php echo esc_html( substr( $msg, 4 ) ); ?></code></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_us' ); ?>
			<input type="hidden" name="action" value="semrs_us"><input type="hidden" name="semrs_do" value="key_create">
			<input type="email" name="email" placeholder="customer@example.com" required>
			<label><?php esc_html_e( 'Sites', 'semrs-update-server' ); ?> <input type="number" name="sites" value="1" min="0" style="width:70px"></label>
			<label><?php esc_html_e( 'Expires', 'semrs-update-server' ); ?> <input type="date" name="expires" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 year' ) ) ); ?>"></label>
			<?php submit_button( __( 'Create key', 'semrs-update-server' ), 'primary', 'submit', false ); ?>
		</form>
		<p class="description"><?php esc_html_e( 'Sites = 0 means unlimited. Local and staging sites never count towards the limit.', 'semrs-update-server' ); ?></p>
		<table class="widefat striped" style="max-width:1000px;margin-top:1rem"><thead><tr><th><?php esc_html_e( 'Key', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Customer', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Sites used / allowed', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Expires', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-update-server' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( semrs_us_keys() as $key => $k ) : $used = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}semrs_sites WHERE slug='semrs-pro' AND license_key=%s AND last_seen > %s", $key, gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB ?>
			<tr><td><code><?php echo esc_html( $key ); ?></code></td><td><?php echo esc_html( $k['email'] ); ?></td><td><?php echo esc_html( $used . ' / ' . ( $k['sites'] ? $k['sites'] : '∞' ) ); ?></td><td><?php echo esc_html( $k['expires'] ? $k['expires'] : __( 'Never', 'semrs-update-server' ) ); ?></td><td><?php echo esc_html( $k['status'] ); ?></td>
			<td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'semrs_us' ); ?><input type="hidden" name="action" value="semrs_us"><input type="hidden" name="semrs_do" value="key_status"><input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>"><button class="button button-small"><?php echo 'active' === $k['status'] ? esc_html__( 'Revoke', 'semrs-update-server' ) : esc_html__( 'Re-activate', 'semrs-update-server' ); ?></button></form></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}

/**
 * Live sites page.
 */
function semrs_us_page_sites() {
	global $wpdb;
	$filter = isset( $_GET['product'] ) ? sanitize_key( $_GET['product'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$since  = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	$counts = $wpdb->get_results( $wpdb->prepare( "SELECT slug, COUNT(*) n FROM {$wpdb->prefix}semrs_sites WHERE last_seen > %s GROUP BY slug", $since ), OBJECT_K ); // phpcs:ignore WordPress.DB
	$sql    = "SELECT * FROM {$wpdb->prefix}semrs_sites WHERE last_seen > %s" . ( $filter ? ' AND slug = %s' : '' ) . ' ORDER BY last_seen DESC LIMIT 500';
	$rows   = $filter ? $wpdb->get_results( $wpdb->prepare( $sql, $since, $filter ) ) : $wpdb->get_results( $wpdb->prepare( $sql, $since ) ); // phpcs:ignore WordPress.DB
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Live sites', 'semrs-update-server' ); ?></h1>
		<p><?php esc_html_e( 'Sites that checked for updates in the last 30 days. Every one of them shows the WordPress "Update available" notice when you publish a newer release.', 'semrs-update-server' ); ?></p>
		<ul class="subsubsub">
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-updates-sites' ) ); ?>" <?php echo $filter ? '' : 'class="current"'; ?>><?php esc_html_e( 'All', 'semrs-update-server' ); ?></a></li>
			<?php foreach ( semrs_us_products() as $slug => $p ) : ?>
				<li> | <a href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-updates-sites&product=' . $slug ) ); ?>" <?php echo $filter === $slug ? 'class="current"' : ''; ?>><?php echo esc_html( $p[0] ); ?> <span class="count">(<?php echo isset( $counts[ $slug ] ) ? (int) $counts[ $slug ]->n : 0; ?>)</span></a></li>
			<?php endforeach; ?>
		</ul>
		<table class="widefat striped" style="clear:both"><thead><tr><th><?php esc_html_e( 'Site', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Product', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Installed', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Latest', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'WordPress / PHP', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Last check', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php if ( ! $rows ) : ?><tr><td colspan="6"><?php esc_html_e( 'No sites have checked in yet.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( $rows as $r ) : $l = semrs_us_latest( $r->slug ); $old = $l && version_compare( $r->version, $l['version'], '<' ); ?>
			<tr><td><?php echo esc_html( $r->host ); ?></td><td><?php echo esc_html( isset( semrs_us_products()[ $r->slug ] ) ? semrs_us_products()[ $r->slug ][0] : $r->slug ); ?></td><td style="<?php echo $old ? 'color:#b32d2e;font-weight:600' : ''; ?>"><?php echo esc_html( $r->version ); ?><?php echo $old ? ' (' . esc_html__( 'update pending', 'semrs-update-server' ) . ')' : ''; ?></td><td><?php echo esc_html( $l ? $l['version'] : '—' ); ?></td><td><?php echo esc_html( $r->wp . ' / ' . $r->php ); ?></td><td><?php echo esc_html( human_time_diff( strtotime( $r->last_seen . ' UTC' ) ) . ' ' . __( 'ago', 'semrs-update-server' ) ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}
