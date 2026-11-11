<?php
/**
 * Piracy watch: keys used on more sites than they allow, made-up or
 * revoked keys, and SEMRS Pro copies that check for updates without any key
 * (nulled or shared copies). Such sites never get Pro updates or file repairs;
 * this screen and a weekly email show who they are.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Note a refused key use.
 *
 * @param string $key    Key as sent.
 * @param string $host   Site host.
 * @param string $status invalid|limit.
 */
function semrs_us_piracy_note( $key, $host, $status ) {
	if ( '' === $host || semrs_us_is_dev_host( $host ) ) {
		return;
	}
	$p   = (array) get_option( 'semrs_us_piracy', array() );
	$key = substr( $key, 0, 64 );
	if ( ! isset( $p[ $key ] ) && count( $p ) >= 500 ) {
		return; // Never let made-up keys flood the list.
	}
	$row                   = $p[ $key ] ?? array( 'hosts' => array(), 'status' => $status, 'first' => time(), 'alerted' => 0 );
	$row['status']         = $status;
	$row['hosts'][ $host ] = time();
	if ( count( $row['hosts'] ) > 50 ) {
		arsort( $row['hosts'] );
		$row['hosts'] = array_slice( $row['hosts'], 0, 50, true );
	}
	$p[ $key ] = $row;
	update_option( 'semrs_us_piracy', $p, false );
}

/**
 * Weekly email when new abuse shows up.
 */
function semrs_us_piracy_digest() {
	global $wpdb;
	$p     = (array) get_option( 'semrs_us_piracy', array() );
	$keys  = semrs_us_keys();
	$lines = array();
	foreach ( $p as $key => &$row ) {
		$recent = array_filter( (array) $row['hosts'], function ( $t ) { return $t > time() - 30 * DAY_IN_SECONDS; } );
		if ( count( $recent ) < ( 'limit' === $row['status'] ? 1 : 2 ) || $row['alerted'] > time() - 7 * DAY_IN_SECONDS ) {
			continue;
		}
		$who     = isset( $keys[ $key ] ) ? $keys[ $key ]['email'] : __( 'unknown key', 'semrs-update-server' );
		$lines[] = sprintf( '- %s (%s, %s): %s', semrs_us_mask_key( $key ), $who, 'limit' === $row['status'] ? __( 'over its site limit', 'semrs-update-server' ) : __( 'not a valid key', 'semrs-update-server' ), implode( ', ', array_slice( array_keys( $recent ), 0, 8 ) ) );
		$row['alerted'] = time();
	}
	unset( $row );
	$since    = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
	$unlicensed = (array) $wpdb->get_col( $wpdb->prepare( "SELECT host FROM {$wpdb->prefix}semrs_sites WHERE slug = 'semrs-pro' AND license_key = '' AND first_seen > %s LIMIT 50", $since ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$unlicensed = array_values( array_filter( $unlicensed, function ( $h ) { return ! semrs_us_is_dev_host( $h ); } ) );
	if ( ! $lines && ! $unlicensed ) {
		return;
	}
	update_option( 'semrs_us_piracy', $p, false );
	$body = __( 'SEMRS piracy watch, last 7 days:', 'semrs-update-server' ) . "\n\n";
	if ( $lines ) {
		$body .= __( 'Keys used where they should not be:', 'semrs-update-server' ) . "\n" . implode( "\n", $lines ) . "\n\n";
	}
	if ( $unlicensed ) {
		$body .= __( 'New sites running SEMRS Pro without a license (no updates or repairs are sent to them):', 'semrs-update-server' ) . "\n- " . implode( "\n- ", $unlicensed ) . "\n\n";
	}
	$body .= admin_url( 'admin.php?page=semrs-updates-piracy' );
	wp_mail( get_option( 'admin_email' ), __( '[SEMRS] Piracy watch', 'semrs-update-server' ), $body );
}
add_action( 'semrs_us_piracy_weekly', 'semrs_us_piracy_digest' );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'semrs_us_piracy_weekly' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', 'semrs_us_piracy_weekly' );
		}
	}
);

/**
 * Key with the middle hidden.
 *
 * @param string $key Key.
 * @return string
 */
function semrs_us_mask_key( $key ) {
	return strlen( $key ) > 9 ? substr( $key, 0, 4 ) . '…' . substr( $key, -4 ) : $key;
}

if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs-updates', __( 'Piracy watch', 'semrs-update-server' ), __( 'Piracy watch', 'semrs-update-server' ), 'manage_options', 'semrs-updates-piracy', 'semrs_us_page_piracy' );
		},
		20
	);
	add_action(
		'admin_post_semrs_us_piracy_clear',
		function () {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_us_piracy_clear' ) ) {
				wp_die( esc_html__( 'Not allowed.', 'semrs-update-server' ) );
			}
			update_option( 'semrs_us_piracy', array(), false );
			wp_safe_redirect( admin_url( 'admin.php?page=semrs-updates-piracy' ) );
			exit;
		}
	);
}

/**
 * Piracy watch screen.
 */
function semrs_us_page_piracy() {
	global $wpdb;
	$p     = (array) get_option( 'semrs_us_piracy', array() );
	$keys  = semrs_us_keys();
	$since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	$rows  = (array) $wpdb->get_results( $wpdb->prepare( "SELECT host, version, last_seen FROM {$wpdb->prefix}semrs_sites WHERE slug = 'semrs-pro' AND license_key = '' AND last_seen > %s ORDER BY last_seen DESC LIMIT 300", $since ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rows  = array_values( array_filter( $rows, function ( $r ) { return ! semrs_us_is_dev_host( $r->host ); } ) );
	uasort( $p, function ( $a, $b ) { return max( (array) $b['hosts'] ) <=> max( (array) $a['hosts'] ); } );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Piracy watch', 'semrs-update-server' ); ?></h1>
		<p><?php esc_html_e( 'Sites shown here never receive SEMRS Pro updates or file repairs. Pro features themselves keep working (GPL), so these lists are for follow-up: contact the customer, raise the site limit, or revoke a shared key on the License keys screen. A summary is emailed weekly when something new appears.', 'semrs-update-server' ); ?></p>

		<h2><?php esc_html_e( 'Keys used where they should not be', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Key', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Customer', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Problem', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Sites that tried it', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Last try', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php if ( ! $p ) : ?><tr><td colspan="5"><?php esc_html_e( 'Nothing so far.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( array_slice( $p, 0, 200, true ) as $key => $row ) : ?>
			<tr>
				<td><code><?php echo esc_html( semrs_us_mask_key( (string) $key ) ); ?></code></td>
				<td><?php echo isset( $keys[ $key ] ) ? esc_html( $keys[ $key ]['email'] . ' (' . sprintf( /* translators: %d: sites */ _n( '%d site allowed', '%d sites allowed', (int) $keys[ $key ]['sites'], 'semrs-update-server' ), (int) $keys[ $key ]['sites'] ) . ')' ) : '—'; ?></td>
				<td><?php echo 'limit' === $row['status'] ? esc_html__( 'Over its site limit', 'semrs-update-server' ) : esc_html__( 'Not a valid key (made up, refunded or revoked)', 'semrs-update-server' ); ?></td>
				<td><?php echo esc_html( implode( ', ', array_slice( array_keys( (array) $row['hosts'] ), 0, 10 ) ) ); ?><?php echo count( (array) $row['hosts'] ) > 10 ? ' …' : ''; ?></td>
				<td><?php echo esc_html( human_time_diff( (int) max( (array) $row['hosts'] ) ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
		<?php if ( $p ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px"><input type="hidden" name="action" value="semrs_us_piracy_clear"><?php wp_nonce_field( 'semrs_us_piracy_clear' ); ?><?php submit_button( __( 'Clear this list', 'semrs-update-server' ), 'secondary', '', false ); ?></form>
		<?php endif; ?>

		<h2><?php esc_html_e( 'SEMRS Pro running without a license (last 30 days)', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Site', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Version', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Last check', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php if ( ! $rows ) : ?><tr><td colspan="3"><?php esc_html_e( 'None.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( $rows as $r ) : ?>
			<tr><td><?php echo esc_html( $r->host ); ?></td><td><?php echo esc_html( $r->version ); ?></td><td><?php echo esc_html( human_time_diff( strtotime( $r->last_seen . ' UTC' ) ) ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="description"><?php esc_html_e( 'Some of these may be customers who have not entered their key yet; local and staging sites are left out.', 'semrs-update-server' ); ?></p>
	</div>
	<?php
}
