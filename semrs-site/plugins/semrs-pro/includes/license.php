<?php
/**
 * SEMRS → License: Activate / Deactivate the SEMRS Pro key with semrs.com.
 *
 * Activate checks the key immediately and takes one site slot; Deactivate
 * frees the slot (e.g. before moving the license to another site). The
 * result is stored and also refreshed by every update check.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function semrs_pro_license_menu() {
	add_submenu_page( 'semrs', __( 'SEMRS Pro License', 'semrs-pro' ), __( 'License', 'semrs-pro' ), 'manage_options', 'semrs-license', 'semrs_pro_license_page' );
}
add_action( 'admin_menu', 'semrs_pro_license_menu', 90 );

/**
 * Handle the Activate / Deactivate buttons.
 */
function semrs_pro_license_actions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
	}
	check_admin_referer( 'semrs_pro_license' );
	$do  = isset( $_POST['semrs_do'] ) ? sanitize_key( wp_unslash( $_POST['semrs_do'] ) ) : '';
	$key = isset( $_POST['semrs_pro_license_key'] ) ? preg_replace( '/[^A-Za-z0-9-]/', '', strtoupper( sanitize_text_field( wp_unslash( $_POST['semrs_pro_license_key'] ) ) ) ) : '';
	$msg = '';

	if ( 'activate' === $do ) {
		$call = semrs_pro_license_call( 'activate', $key );
		if ( $call['ok'] ) {
			update_option( 'semrs_pro_license_key', $key, false );
			semrs_pro_license_apply( $call['data'], true );
			$msg = sanitize_text_field( $call['data']['message'] ?? '' );
		} else {
			$msg = $call['error'];
		}
	} elseif ( 'deactivate' === $do ) {
		$current = (string) get_option( 'semrs_pro_license_key', '' );
		$call    = semrs_pro_license_call( 'deactivate', $current );
		delete_option( 'semrs_pro_license_key' );
		delete_option( 'semrs_pro_license_remote' );
		delete_option( 'semrs_pro_license_expires' );
		update_option( 'semrs_pro_addons', array(), false ); // Pro modules leave with the key.
		$msg = $call['ok'] ? sanitize_text_field( $call['data']['message'] ?? '' ) : __( 'Removed from this site. The server could not be reached, so the slot will free itself after 30 days without checks.', 'semrs-pro' );
	} elseif ( 'refresh' === $do ) {
		$call = semrs_pro_license_refresh();
		$msg  = $call['ok'] ? __( 'Checked. Your add-ons are up to date.', 'semrs-pro' ) : ( $call['error'] ? $call['error'] : __( 'No key saved.', 'semrs-pro' ) );
	}
	delete_site_transient( 'semrs_upd_' . md5( 'semrs-pro' ) );
	set_transient( 'semrs_pro_license_msg_' . get_current_user_id(), $msg, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=semrs-license' ) );
	exit;
}
add_action( 'admin_post_semrs_pro_license', 'semrs_pro_license_actions' );

/**
 * License status: missing | unverified | valid | expired | invalid | limit.
 *
 * @return string
 */
function semrs_pro_license_status() {
	$key    = (string) get_option( 'semrs_pro_license_key', '' );
	$status = '' === $key ? 'missing' : 'unverified';
	$remote = (string) get_option( 'semrs_pro_license_remote', '' );
	if ( '' !== $key && in_array( $remote, array( 'valid', 'expired', 'invalid', 'limit' ), true ) ) {
		$status = $remote; // From the Activate button or the latest update check.
	}
	return (string) apply_filters( 'semrs_pro_license_status', $status, $key );
}

/**
 * Admin notice on SEMRS screens until a license is active.
 */
function semrs_pro_license_notice() {
	$screen = get_current_screen();
	if ( ! $screen || false === strpos( (string) $screen->id, 'semrs' ) || 'admin_page_semrs-license' === $screen->id || false !== strpos( (string) $screen->id, 'semrs-license' ) ) {
		return;
	}
	if ( 'valid' !== semrs_pro_license_status() && current_user_can( 'manage_options' ) ) {
		printf(
			'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
			semrs_pro_addons() ? esc_html__( 'Activate your SEMRS Pro license to get automatic updates and support.', 'semrs-pro' ) : esc_html__( 'Activate your SEMRS Pro license to unlock your Pro add-ons.', 'semrs-pro' ),
			esc_url( admin_url( 'admin.php?page=semrs-license' ) ),
			esc_html__( 'Activate license', 'semrs-pro' )
		);
	}
}
add_action( 'admin_notices', 'semrs_pro_license_notice' );

/**
 * Page.
 */
function semrs_pro_license_page() {
	$status  = semrs_pro_license_status();
	$key     = (string) get_option( 'semrs_pro_license_key', '' );
	$expires = (string) get_option( 'semrs_pro_license_expires', '' );
	$msg     = get_transient( 'semrs_pro_license_msg_' . get_current_user_id() );
	delete_transient( 'semrs_pro_license_msg_' . get_current_user_id() );
	$badge = array(
		'valid'      => array( '#0f7b4d', __( 'Active', 'semrs-pro' ) ),
		'expired'    => array( '#8a5a00', __( 'Expired', 'semrs-pro' ) ),
		'invalid'    => array( '#b42318', __( 'Invalid', 'semrs-pro' ) ),
		'limit'      => array( '#b42318', __( 'Site limit reached', 'semrs-pro' ) ),
		'unverified' => array( '#5b6575', __( 'Not checked yet', 'semrs-pro' ) ),
		'missing'    => array( '#5b6575', __( 'Not activated', 'semrs-pro' ) ),
	);
	$b = isset( $badge[ $status ] ) ? $badge[ $status ] : $badge['missing'];
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Pro License', 'semrs-pro' ); ?></h1>
		<?php if ( $msg ) : ?>
			<div class="notice <?php echo 'valid' === $status || 'missing' === $status ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>
		<p>
			<?php esc_html_e( 'Status:', 'semrs-pro' ); ?>
			<span style="display:inline-block;padding:2px 10px;border-radius:999px;color:#fff;background:<?php echo esc_attr( $b[0] ); ?>"><?php echo esc_html( $b[1] ); ?></span>
			<?php if ( $expires && 'valid' === $status ) : ?>
				<?php /* translators: %s: date */ echo esc_html( sprintf( __( 'Updates and support until %s', 'semrs-pro' ), $expires ) ); ?>
			<?php endif; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_pro_license' ); ?>
			<input type="hidden" name="action" value="semrs_pro_license">
			<?php if ( 'valid' === $status || ( 'expired' === $status && semrs_pro_addons() ) ) : ?>
				<input type="text" class="regular-text code" value="<?php echo esc_attr( substr( $key, 0, 4 ) . '-••••-••••-' . substr( $key, -4 ) ); ?>" readonly>
				<button class="button" name="semrs_do" value="refresh"><?php esc_html_e( 'Check again', 'semrs-pro' ); ?></button>
				<button class="button" name="semrs_do" value="deactivate"><?php esc_html_e( 'Deactivate license', 'semrs-pro' ); ?></button>
				<p class="description"><?php esc_html_e( 'Deactivate before moving SEMRS Pro to another site, so the slot is freed. Bought another add-on? Click Check again.', 'semrs-pro' ); ?></p>
			<?php else : ?>
				<input type="text" class="regular-text code" name="semrs_pro_license_key" value="<?php echo esc_attr( $key ); ?>" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off" required>
				<button class="button button-primary" name="semrs_do" value="activate"><?php esc_html_e( 'Activate license', 'semrs-pro' ); ?></button>
				<?php if ( $key ) : ?>
					<button class="button" name="semrs_do" value="deactivate" formnovalidate><?php esc_html_e( 'Remove key', 'semrs-pro' ); ?></button>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Your key is in your purchase email. Local and staging sites do not use a site slot.', 'semrs-pro' ); ?></p>
			<?php endif; ?>
		</form>
		<h2><?php esc_html_e( 'Your add-ons', 'semrs-pro' ); ?></h2>
		<table class="widefat striped" style="max-width:640px"><tbody>
		<?php foreach ( semrs_pro_addon_labels() as $slug => $label ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $label ); ?></strong></td>
				<?php if ( semrs_pro_has( $slug ) ) : ?>
					<td style="color:#0f7b4d;font-weight:600"><?php esc_html_e( '✔ Unlocked', 'semrs-pro' ); ?></td><td></td>
				<?php else : ?>
					<td><?php esc_html_e( '🔒 Not included', 'semrs-pro' ); ?></td>
					<td style="text-align:right"><a class="button button-small" href="<?php echo esc_url( semrs_pro_buy_url() ); ?>" target="_blank" rel="noopener"><?php /* translators: %s: add-on name */ echo esc_html( sprintf( __( 'Add %s', 'semrs-pro' ), $label ) ); ?></a></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="description"><?php esc_html_e( 'Add-ons you bought keep working when a license expires; the license controls updates and support. Add-ons bought later go on the same key and switch on by themselves within a day.', 'semrs-pro' ); ?></p>
	</div>
	<?php
}

/**
 * Plugins screen: Settings and License links.
 *
 * @param string[] $links Links.
 * @return string[]
 */
function semrs_pro_action_links( $links ) {
	$url = menu_page_url( 'semrs-license', false );
	if ( $url ) {
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'License', 'semrs-pro' ) . '</a>' );
	}
	$url = menu_page_url( 'semrs', false );
	if ( $url ) {
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'semrs-pro' ) . '</a>' );
	}
	return $links;
}
add_filter( 'plugin_action_links_semrs-pro/semrs-pro.php', 'semrs_pro_action_links' );
