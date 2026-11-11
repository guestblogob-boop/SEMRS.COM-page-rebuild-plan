<?php
/**
 * SEMRS → Assets Protector screen: overview, firewall, malware scan, spam.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'SEMRS Assets Protector', 'semrs-pro' ), __( 'Assets Protector', 'semrs-pro' ), 'manage_options', 'semrs-protect', 'semrs_secpro_page' );
	},
	33
);

// Keep the early firewall loader in step with the settings (cheap check, admins only).
add_action(
	'admin_init',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( semrs_fw_enabled() xor is_file( semrs_fw_loader_path() ) ) {
			semrs_fw_sync_loader();
		}
		// Administrators already signed in: remember their address too (once a day).
		$ip = semrs_fw_ip();
		$l  = (array) get_option( 'semrs_fw_admin_ips', array() );
		if ( $ip && (int) ( $l[ $ip ] ?? 0 ) < time() - DAY_IN_SECONDS ) {
			semrs_fw_remember_admin( '', wp_get_current_user() );
		}
	}
);

/**
 * Save settings.
 */
add_action(
	'admin_post_semrs_secpro_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_secpro_save' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
		}
		$tab = sanitize_key( wp_unslash( $_POST['tab'] ?? '' ) );
		$in  = (array) wp_unslash( $_POST['semrs_secpro'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per key below.
		$s   = (array) get_option( 'semrs_secpro', array() );
		$ips = function ( $v ) {
			$ok = array();
			foreach ( preg_split( '/[\s,]+/', (string) $v ) as $r ) {
				$r  = trim( $r );
				$ip = false !== strpos( $r, '/' ) ? strtok( $r, '/' ) : $r;
				if ( '' !== $r && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					$ok[] = $r;
				}
			}
			return implode( "\n", array_unique( $ok ) );
		};
		$boxes = array(
			'firewall' => array( 'fw_on', 'fw_bots', 'fw_flood', 'fw_repeat', 'fw_wordfence' ),
			'scan'     => array( 'scan_daily', 'scan_email', 'scan_wordfence' ),
			'spam'     => array( 'spam_on', 'spam_comments', 'spam_forms', 'spam_register', 'spam_script', 'spam_sfs' ),
		);
		foreach ( $boxes[ $tab ] ?? array() as $k ) {
			$s[ $k ] = empty( $in[ $k ] ) ? '' : '1';
		}
		if ( 'firewall' === $tab ) {
			$s['fw_mode']  = 'log' === ( $in['fw_mode'] ?? '' ) ? 'log' : 'block';
			$s['fw_allow'] = $ips( $in['fw_allow'] ?? '' );
			$s['fw_block'] = $ips( $in['fw_block'] ?? '' );
			// Never block yourself.
			$me = semrs_fw_ip();
			if ( $me && semrs_fw_ip_listed( $me, $s['fw_block'] ) ) {
				$s['fw_block'] = implode( "\n", array_filter( explode( "\n", $s['fw_block'] ), function ( $r ) use ( $me ) { return ! semrs_fw_ip_in( $me, $r ); } ) );
			}
		}
		if ( 'spam' === $tab ) {
			$s['spam_links'] = max( 0, min( 20, absint( $in['spam_links'] ?? 2 ) ) );
			$s['spam_days']  = max( 7, min( 365, absint( $in['spam_days'] ?? 30 ) ) );
			$s['spam_words'] = implode( "\n", array_filter( array_map( 'sanitize_text_field', preg_split( '/[\r\n]+/', (string) ( $in['spam_words'] ?? '' ) ) ) ) );
		}
		update_option( 'semrs_secpro', $s );
		if ( 'firewall' === $tab ) {
			semrs_fw_sync_loader();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-protect&tab=' . $tab . '&saved=1' ) );
		exit;
	}
);

/**
 * Small actions: unblock, clear logs, scan fixes.
 */
add_action(
	'admin_post_semrs_secpro_do',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_secpro_do' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
		}
		$do  = sanitize_key( wp_unslash( $_REQUEST['do'] ?? '' ) );
		$id  = sanitize_text_field( wp_unslash( $_REQUEST['id'] ?? '' ) );
		$tab = 'firewall';
		$res = true;
		switch ( $do ) {
			case 'unblock':
				$t = (array) get_option( 'semrs_fw_blocks', array() );
				unset( $t[ $id ] );
				update_option( 'semrs_fw_blocks', $t, true );
				break;
			case 'unblock_all':
				update_option( 'semrs_fw_blocks', array(), true );
				break;
			case 'fw_clear':
				update_option( 'semrs_fw_log', array(), false );
				break;
			case 'spam_clear':
				update_option( 'semrs_spam_log', array(), false );
				$tab = 'spam';
				break;
			case 'restore':
				$res = semrs_scan_restore( $id );
				$tab = 'scan';
				break;
			case 'quarantine':
				$res = semrs_scan_quarantine( $id );
				$tab = 'scan';
				break;
			case 'ignore':
				semrs_scan_ignore( $id );
				$tab = 'scan';
				break;
			case 'unquarantine':
				$res = semrs_scan_unquarantine( $id );
				$tab = 'scan';
				break;
			case 'unignore':
				update_option( 'semrs_scan_ignored', array(), false );
				$tab = 'scan';
				break;
		}
		$args = array( 'page' => 'semrs-protect', 'tab' => $tab, 'done' => is_wp_error( $res ) ? 0 : 1 );
		if ( is_wp_error( $res ) ) {
			set_transient( 'semrs_secpro_err_' . get_current_user_id(), $res->get_error_message(), 60 );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
);

/**
 * Scan steps from the screen.
 */
add_action(
	'wp_ajax_semrs_scan_step',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'semrs_scan', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'semrs-pro' ) ), 403 );
		}
		if ( ! empty( $_POST['start'] ) && ! semrs_scan_start( 'manual' ) ) {
			wp_send_json_error( array( 'message' => __( 'The scan folder could not be created in wp-content. Check the folder permissions.', 'semrs-pro' ) ) );
		}
		$s      = semrs_scan_step( 6 );
		$labels = array(
			'core'    => __( 'Checking WordPress core files…', 'semrs-pro' ),
			'plugins' => __( 'Checking plugins against WordPress.org…', 'semrs-pro' ),
			'files'   => __( 'Scanning themes, plugins and uploads for malware…', 'semrs-pro' ),
			'db'      => __( 'Checking the database…', 'semrs-pro' ),
			'health'  => __( 'Checking updates and settings…', 'semrs-pro' ),
		);
		wp_send_json_success(
			array(
				'status' => $s['status'],
				'label'  => $labels[ $s['phase'] ] ?? __( 'Finishing…', 'semrs-pro' ),
				'pct'    => 'files' === $s['phase'] && $s['total'] ? (int) ( 40 + 50 * $s['pos'] / max( 1, $s['total'] ) ) : array_search( $s['phase'], array( '', 'core', 'plugins', 'files', 'db', 'health' ), true ) * 15,
			)
		);
	}
);

/**
 * Days → total from a stats array.
 *
 * @param string $opt  Option.
 * @param int    $days Days.
 * @return int
 */
function semrs_secpro_count( $opt, $days ) {
	$n = 0;
	foreach ( (array) get_option( $opt, array() ) as $d => $c ) {
		if ( strtotime( $d ) >= strtotime( gmdate( 'Y-m-d' ) ) - ( $days - 1 ) * DAY_IN_SECONDS ) {
			$n += (int) $c;
		}
	}
	return $n;
}

/**
 * A switch row.
 *
 * @param array  $s     Settings.
 * @param string $k     Key.
 * @param string $label Label.
 * @param string $help  Help.
 */
function semrs_secpro_box( $s, $k, $label, $help = '' ) {
	printf(
		'<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="semrs_secpro[%2$s]" value="1" %3$s> %4$s</label></td></tr>',
		esc_html( $label ),
		esc_attr( $k ),
		checked( '1', (string) ( $s[ $k ] ?? '' ), false ),
		esc_html( $help )
	);
}

/**
 * The screen.
 */
function semrs_secpro_page() {
	$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? 'overview' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'overview' => __( 'Overview', 'semrs-pro' ),
		'firewall' => __( 'Firewall', 'semrs-pro' ),
		'scan'     => __( 'Malware scan', 'semrs-pro' ),
		'spam'     => __( 'Spam protection', 'semrs-pro' ),
	);
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'overview';
	$err  = get_transient( 'semrs_secpro_err_' . get_current_user_id() );
	if ( $err ) {
		delete_transient( 'semrs_secpro_err_' . get_current_user_id() );
	}
	$do = function ( $what, $id = '', $extra = array() ) {
		return wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'semrs_secpro_do', 'do' => $what, 'id' => $id ), $extra ), admin_url( 'admin-post.php' ) ), 'semrs_secpro_do' );
	};
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'SEMRS Assets Protector', 'semrs-pro' ); ?></h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'semrs-pro' ); ?></p></div>
		<?php elseif ( $err ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
		<?php elseif ( ! empty( $_GET['done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Done.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $l ) : ?>
				<a class="nav-tab <?php echo $k === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-protect&tab=' . $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		call_user_func( 'semrs_secpro_tab_' . $tab, $do );
		?>
	</div>
	<?php
}

/**
 * Overview tab.
 */
function semrs_secpro_tab_overview() {
	$fw    = semrs_fw_status();
	$scan  = semrs_scan_state();
	$finds = semrs_scan_findings();
	$sev   = array_count_values( wp_list_pluck( $finds, 'sev' ) );
	$core  = function_exists( 'semrs_security_settings' ) ? semrs_security_settings() : array();
	$fw_l  = array(
		'early'    => __( 'On, runs before other plugins (strongest)', 'semrs-pro' ),
		'standard' => __( 'On (standard: the must-use folder is not writable)', 'semrs-pro' ),
		'paused'   => __( 'Paused: Wordfence’s firewall is active, so SEMRS steps aside', 'semrs-pro' ),
		'off'      => __( 'Off', 'semrs-pro' ),
	);
	$card  = 'background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 18px;flex:1 1 240px';
	?>
	<div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:16px">
		<div style="<?php echo esc_attr( $card ); ?>">
			<h2 style="margin-top:0"><?php esc_html_e( 'Firewall', 'semrs-pro' ); ?></h2>
			<p><strong><?php echo esc_html( $fw_l[ $fw ] ); ?></strong></p>
			<?php /* translators: 1: today, 2: 30 days */ ?>
			<p><?php echo esc_html( sprintf( __( 'Attacks stopped: %1$d today, %2$d in 30 days.', 'semrs-pro' ), semrs_secpro_count( 'semrs_fw_stats', 1 ), semrs_secpro_count( 'semrs_fw_stats', 30 ) ) ); ?></p>
		</div>
		<div style="<?php echo esc_attr( $card ); ?>">
			<h2 style="margin-top:0"><?php esc_html_e( 'Malware scan', 'semrs-pro' ); ?></h2>
			<?php if ( $scan['finished'] ) : ?>
				<?php /* translators: %s: time ago */ ?>
				<p><?php echo esc_html( sprintf( __( 'Last scan: %s ago.', 'semrs-pro' ), human_time_diff( (int) $scan['finished'] ) ) ); ?></p>
				<p><strong>
				<?php
				if ( ! empty( $sev['critical'] ) || ! empty( $sev['high'] ) ) {
					/* translators: %d: number */
					echo esc_html( sprintf( _n( '%d serious problem needs your attention.', '%d serious problems need your attention.', (int) ( $sev['critical'] ?? 0 ) + (int) ( $sev['high'] ?? 0 ), 'semrs-pro' ), (int) ( $sev['critical'] ?? 0 ) + (int) ( $sev['high'] ?? 0 ) ) );
				} else {
					esc_html_e( 'No malware found.', 'semrs-pro' );
				}
				?>
				</strong></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Not scanned yet.', 'semrs-pro' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-protect&tab=scan' ) ); ?>"><?php esc_html_e( 'Run the first scan', 'semrs-pro' ); ?></a></p>
			<?php endif; ?>
		</div>
		<div style="<?php echo esc_attr( $card ); ?>">
			<h2 style="margin-top:0"><?php esc_html_e( 'Spam protection', 'semrs-pro' ); ?></h2>
			<p><strong><?php echo '1' === semrs_spam_settings()['spam_on'] ? esc_html__( 'On', 'semrs-pro' ) : esc_html__( 'Off', 'semrs-pro' ); ?></strong><?php echo defined( 'AKISMET_VERSION' ) ? ' ' . esc_html__( '(working together with Akismet)', 'semrs-pro' ) : ''; ?></p>
			<?php /* translators: 1: today, 2: 30 days */ ?>
			<p><?php echo esc_html( sprintf( __( 'Spam stopped: %1$d today, %2$d in 30 days.', 'semrs-pro' ), semrs_secpro_count( 'semrs_spam_stats', 1 ), semrs_secpro_count( 'semrs_spam_stats', 30 ) ) ); ?></p>
		</div>
	</div>
	<h2><?php esc_html_e( 'Protection checklist', 'semrs-pro' ); ?></h2>
	<table class="widefat striped" style="max-width:820px">
		<tbody>
		<?php
		$rows = array(
			array( 'early' === $fw || 'standard' === $fw || 'paused' === $fw, __( 'Firewall', 'semrs-pro' ), 'admin.php?page=semrs-protect&tab=firewall' ),
			array( '1' === semrs_scan_settings()['scan_daily'], __( 'Daily malware scan with email alerts', 'semrs-pro' ), 'admin.php?page=semrs-protect&tab=scan' ),
			array( '1' === semrs_spam_settings()['spam_on'], __( 'Spam protection for comments and forms', 'semrs-pro' ), 'admin.php?page=semrs-protect&tab=spam' ),
			array( '1' === ( $core['limit'] ?? '' ), __( 'Log-in attempt limit', 'semrs-pro' ), 'admin.php?page=semrs-security' ),
			array( '1' === ( $core['enum'] ?? '' ), __( 'Usernames hidden from scans', 'semrs-pro' ), 'admin.php?page=semrs-security' ),
			array( '1' === ( $core['headers'] ?? '' ), __( 'Security headers', 'semrs-pro' ), 'admin.php?page=semrs-security' ),
			array( function_exists( 'semrs_2fa_enabled' ) ? semrs_2fa_enabled( get_current_user_id() ) : (bool) get_user_meta( get_current_user_id(), '_semrs_2fa_secret', true ), __( 'Two-factor log-in for your account', 'semrs-pro' ), 'profile.php' ),
		);
		foreach ( $rows as $r ) {
			printf( '<tr><td style="width:32px">%1$s</td><td>%2$s</td><td><a href="%3$s">%4$s</a></td></tr>', $r[0] ? '<span style="color:#15803d;font-weight:700">✓</span>' : '<span style="color:#b91c1c;font-weight:700">✗</span>', esc_html( $r[1] ), esc_url( admin_url( $r[2] ) ), $r[0] ? esc_html__( 'Settings', 'semrs-pro' ) : esc_html__( 'Turn on', 'semrs-pro' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		</tbody>
	</table>
	<?php
}

/**
 * Firewall tab.
 *
 * @param callable $do Action link builder.
 */
function semrs_secpro_tab_firewall( $do ) {
	$s     = semrs_fw_settings();
	$log   = (array) get_option( 'semrs_fw_log', array() );
	$temp  = array_filter( (array) get_option( 'semrs_fw_blocks', array() ), function ( $b ) { return (int) $b[0] > time(); } );
	$state = semrs_fw_status();
	?>
	<?php if ( 'paused' === $state ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Wordfence’s firewall is active on this site, so the SEMRS firewall is paused and nothing is checked twice. Tick “Run alongside Wordfence” to use both.', 'semrs-pro' ); ?></p></div>
	<?php elseif ( 'standard' === $state ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'The firewall is on, but it could not add its early loader to wp-content/mu-plugins (folder not writable). It still protects every request, just after other plugins have loaded.', 'semrs-pro' ); ?></p></div>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="semrs_secpro_save"><input type="hidden" name="tab" value="firewall"><?php wp_nonce_field( 'semrs_secpro_save' ); ?>
		<table class="form-table" role="presentation">
			<?php semrs_secpro_box( $s, 'fw_on', __( 'Firewall', 'semrs-pro' ), __( 'Block SQL injection, cross-site scripting, file inclusion, code injection, requests for private files, PHP in uploads and XML-RPC brute force, before WordPress and other plugins run.', 'semrs-pro' ) ); ?>
			<tr><th scope="row"><?php esc_html_e( 'Mode', 'semrs-pro' ); ?></th><td>
				<label><input type="radio" name="semrs_secpro[fw_mode]" value="block" <?php checked( 'block', $s['fw_mode'] ); ?>> <?php esc_html_e( 'Block attacks (recommended)', 'semrs-pro' ); ?></label><br>
				<label><input type="radio" name="semrs_secpro[fw_mode]" value="log" <?php checked( 'log', $s['fw_mode'] ); ?>> <?php esc_html_e( 'Log only: record matches without blocking (for testing a new site)', 'semrs-pro' ); ?></label>
			</td></tr>
			<?php semrs_secpro_box( $s, 'fw_bots', __( 'Attack tools', 'semrs-pro' ), __( 'Block known hacking and vulnerability-scanning tools.', 'semrs-pro' ) ); ?>
			<?php semrs_secpro_box( $s, 'fw_flood', __( 'Missing-page floods', 'semrs-pro' ), __( 'Block an address for 30 minutes after 40 missing pages in 2 minutes (scanners guessing file names). Search engines are never blocked.', 'semrs-pro' ) ); ?>
			<?php semrs_secpro_box( $s, 'fw_repeat', __( 'Repeat attackers', 'semrs-pro' ), __( 'Block an address for an hour after 5 blocked attacks in 10 minutes.', 'semrs-pro' ) ); ?>
			<tr><th scope="row"><?php esc_html_e( 'Always allow', 'semrs-pro' ); ?></th><td><textarea name="semrs_secpro[fw_allow]" rows="3" class="large-text code" placeholder="203.0.113.10&#10;198.51.100.0/24"><?php echo esc_textarea( $s['fw_allow'] ); ?></textarea>
				<?php /* translators: %s: IP */ ?>
				<p class="description"><?php echo esc_html( sprintf( __( 'One IP address or range per line. Your address now: %s', 'semrs-pro' ), semrs_fw_ip() ) ); ?></p></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Always block', 'semrs-pro' ); ?></th><td><textarea name="semrs_secpro[fw_block]" rows="3" class="large-text code"><?php echo esc_textarea( $s['fw_block'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'One IP address or range per line. Your own address is never blocked.', 'semrs-pro' ); ?></p></td></tr>
			<?php if ( semrs_fw_wordfence() ) : ?>
				<?php semrs_secpro_box( $s, 'fw_wordfence', __( 'Run alongside Wordfence', 'semrs-pro' ), __( 'Use both firewalls. Usually not needed.', 'semrs-pro' ) ); ?>
			<?php endif; ?>
		</table>
		<?php submit_button(); ?>
	</form>
	<p class="description"><?php esc_html_e( 'Emergency switch: add define( \'SEMRS_FIREWALL_OFF\', true ); to wp-config.php.', 'semrs-pro' ); ?></p>

	<h2><?php esc_html_e( 'Blocked for now', 'semrs-pro' ); ?></h2>
	<?php if ( ! $temp ) : ?>
		<p><?php esc_html_e( 'No address is blocked right now.', 'semrs-pro' ); ?></p>
	<?php else : ?>
		<table class="widefat striped" style="max-width:820px"><thead><tr><th><?php esc_html_e( 'IP address', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Why', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Until', 'semrs-pro' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( $temp as $ip => $b ) : ?>
			<tr><td><code><?php echo esc_html( $ip ); ?></code></td><td><?php echo esc_html( $b[1] ); ?></td><td><?php echo esc_html( wp_date( 'H:i', (int) $b[0] ) ); ?></td><td><a href="<?php echo esc_url( $do( 'unblock', $ip ) ); ?>"><?php esc_html_e( 'Unblock', 'semrs-pro' ); ?></a></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p><a class="button" href="<?php echo esc_url( $do( 'unblock_all' ) ); ?>"><?php esc_html_e( 'Unblock all', 'semrs-pro' ); ?></a></p>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Recent blocked requests', 'semrs-pro' ); ?></h2>
	<?php if ( ! $log ) : ?>
		<p><?php esc_html_e( 'Nothing blocked yet.', 'semrs-pro' ); ?></p>
	<?php else : ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'When', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'IP address', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Rule', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Address', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Matched', 'semrs-pro' ); ?></th></tr></thead><tbody>
		<?php foreach ( array_slice( $log, 0, 100 ) as $l ) : ?>
			<tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $l['t'] ) ); ?></td><td><code><?php echo esc_html( $l['ip'] ); ?></code></td><td><?php echo esc_html( $l['l'] ); ?></td><td><code style="word-break:break-all"><?php echo esc_html( $l['u'] ); ?></code></td><td><code style="word-break:break-all"><?php echo esc_html( $l['v'] ); ?></code></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p><a class="button" href="<?php echo esc_url( $do( 'fw_clear' ) ); ?>"><?php esc_html_e( 'Clear the log', 'semrs-pro' ); ?></a></p>
	<?php endif; ?>
	<?php
}

/**
 * Malware scan tab.
 *
 * @param callable $do Action link builder.
 */
function semrs_secpro_tab_scan( $do ) {
	$s     = semrs_scan_settings();
	$st    = semrs_scan_state();
	$finds = semrs_scan_findings();
	$q     = (array) get_option( 'semrs_scan_quarantine', array() );
	$order = array( 'critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3 );
	uasort( $finds, function ( $a, $b ) use ( $order ) { return ( $order[ $a['sev'] ] ?? 9 ) <=> ( $order[ $b['sev'] ] ?? 9 ); } );
	$color = array( 'critical' => '#b91c1c', 'high' => '#c2410c', 'medium' => '#a16207', 'low' => '#475569' );
	$sevl  = array( 'critical' => __( 'Critical', 'semrs-pro' ), 'high' => __( 'High', 'semrs-pro' ), 'medium' => __( 'Medium', 'semrs-pro' ), 'low' => __( 'Low', 'semrs-pro' ) );
	?>
	<p style="margin-top:16px">
		<button type="button" class="button button-primary" id="semrs-scan-go"><?php echo 'running' === $st['status'] ? esc_html__( 'Continue the scan', 'semrs-pro' ) : esc_html__( 'Scan now', 'semrs-pro' ); ?></button>
		<span id="semrs-scan-msg" style="margin-left:10px">
		<?php
		if ( $st['finished'] ) {
			/* translators: 1: time ago, 2: files, 3: verified files */
			echo esc_html( sprintf( __( 'Last scan %1$s ago: %2$d files, %3$d verified identical to WordPress.org.', 'semrs-pro' ), human_time_diff( (int) $st['finished'] ), (int) $st['files'], (int) $st['verified'] ) );
		}
		?>
		</span>
	</p>
	<div id="semrs-scan-bar" style="display:none;max-width:520px;height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden"><span style="display:block;height:100%;width:0;background:#2563eb;transition:width .3s"></span></div>
	<script>
	(function(){var b=document.getElementById('semrs-scan-go'),m=document.getElementById('semrs-scan-msg'),bar=document.getElementById('semrs-scan-bar'),fill=bar.firstElementChild,first=<?php echo 'running' === $st['status'] ? 'false' : 'true'; ?>;
	function step(){var d=new FormData();d.append('action','semrs_scan_step');d.append('nonce','<?php echo esc_js( wp_create_nonce( 'semrs_scan' ) ); ?>');if(first){d.append('start','1');first=false}
	fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:d}).then(function(r){return r.json()}).then(function(j){if(!j.success){m.textContent=(j.data&&j.data.message)||'Error';b.disabled=false;return}m.textContent=j.data.label;fill.style.width=Math.min(100,j.data.pct)+'%';if(j.data.status==='running'){step()}else{m.textContent='<?php echo esc_js( __( 'Scan finished. Loading the results…', 'semrs-pro' ) ); ?>';location.reload()}}).catch(function(){setTimeout(step,3000)})}
	b.addEventListener('click',function(){b.disabled=true;bar.style.display='block';m.textContent='<?php echo esc_js( __( 'Listing files…', 'semrs-pro' ) ); ?>';step()})})();
	</script>

	<h2><?php esc_html_e( 'Results', 'semrs-pro' ); ?></h2>
	<?php if ( ! $finds ) : ?>
		<p><?php echo $st['finished'] ? esc_html__( 'Nothing found. Your site looks clean.', 'semrs-pro' ) : esc_html__( 'Run a scan to see results.', 'semrs-pro' ); ?></p>
	<?php else : ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Level', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Problem', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Where', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Fix', 'semrs-pro' ); ?></th></tr></thead><tbody>
		<?php foreach ( $finds as $id => $f ) : ?>
			<tr>
				<td><strong style="color:<?php echo esc_attr( $color[ $f['sev'] ] ?? '#475569' ); ?>"><?php echo esc_html( $sevl[ $f['sev'] ] ?? $f['sev'] ); ?></strong></td>
				<td><?php echo esc_html( $f['msg'] ); ?><?php echo '' !== $f['detail'] ? '<br><code style="font-size:12px;word-break:break-all">' . esc_html( $f['detail'] ) . '</code>' : ''; ?></td>
				<td><code style="word-break:break-all"><?php echo esc_html( $f['file'] ); ?></code></td>
				<td style="white-space:nowrap">
					<?php if ( ! empty( $f['extra']['restore'] ) ) : ?><a class="button button-small" href="<?php echo esc_url( $do( 'restore', $id ) ); ?>"><?php esc_html_e( 'Restore original', 'semrs-pro' ); ?></a> <?php endif; ?>
					<?php if ( ! empty( $f['extra']['quarantine'] ) ) : ?><a class="button button-small" href="<?php echo esc_url( $do( 'quarantine', $id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Move this file to quarantine? It can be put back later.', 'semrs-pro' ) ); ?>')"><?php esc_html_e( 'Quarantine', 'semrs-pro' ); ?></a> <?php endif; ?>
					<?php if ( ! empty( $f['extra']['edit'] ) ) : ?><a class="button button-small" href="<?php echo esc_url( $f['extra']['edit'] ); ?>"><?php esc_html_e( 'Open', 'semrs-pro' ); ?></a> <?php endif; ?>
					<a class="button button-small button-link" href="<?php echo esc_url( $do( 'ignore', $id ) ); ?>"><?php esc_html_e( 'Ignore', 'semrs-pro' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="description"><?php esc_html_e( 'Restore downloads the original file from WordPress.org (the changed copy is kept in quarantine). Quarantine moves a file to a protected folder; nothing is deleted. Ignore hides a result until the file changes.', 'semrs-pro' ); ?></p>
	<?php endif; ?>

	<?php if ( $q ) : ?>
		<h2><?php esc_html_e( 'Quarantine', 'semrs-pro' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'File', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Why', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'When', 'semrs-pro' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( $q as $qid => $item ) : ?>
			<tr><td><code><?php echo esc_html( $item['file'] ); ?></code></td><td><?php echo esc_html( $item['msg'] ); ?><?php echo empty( $item['moved'] ) ? ' ' . esc_html__( '(copy kept before restoring)', 'semrs-pro' ) : ''; ?></td><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $item['time'] ) ); ?></td><td><?php if ( ! empty( $item['moved'] ) ) : ?><a href="<?php echo esc_url( $do( 'unquarantine', $qid ) ); ?>"><?php esc_html_e( 'Put back', 'semrs-pro' ); ?></a><?php endif; ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Settings', 'semrs-pro' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="semrs_secpro_save"><input type="hidden" name="tab" value="scan"><?php wp_nonce_field( 'semrs_secpro_save' ); ?>
		<table class="form-table" role="presentation">
			<?php semrs_secpro_box( $s, 'scan_daily', __( 'Daily scan', 'semrs-pro' ), __( 'Scan the whole site every night in short background steps.', 'semrs-pro' ) ); ?>
			<?php /* translators: %s: email */ ?>
			<?php semrs_secpro_box( $s, 'scan_email', __( 'Email alerts', 'semrs-pro' ), sprintf( __( 'Email %s when a scan finds a new serious problem.', 'semrs-pro' ), get_option( 'admin_email' ) ) ); ?>
			<?php if ( semrs_fw_wordfence() ) : ?>
				<?php semrs_secpro_box( $s, 'scan_wordfence', __( 'Daily scan with Wordfence', 'semrs-pro' ), __( 'Wordfence is active, so the daily SEMRS scan pauses. Tick to run both.', 'semrs-pro' ) ); ?>
			<?php endif; ?>
		</table>
		<?php submit_button(); ?>
	</form>
	<?php if ( get_option( 'semrs_scan_ignored' ) ) : ?>
		<p><a href="<?php echo esc_url( $do( 'unignore' ) ); ?>"><?php esc_html_e( 'Show ignored results again at the next scan', 'semrs-pro' ); ?></a></p>
	<?php endif; ?>
	<?php
}

/**
 * Spam tab.
 *
 * @param callable $do Action link builder.
 */
function semrs_secpro_tab_spam( $do ) {
	$s       = semrs_spam_settings();
	$log     = (array) get_option( 'semrs_spam_log', array() );
	$reasons = semrs_spam_reasons();
	$where   = array( 'comment' => __( 'Comment', 'semrs-pro' ), 'register' => __( 'Sign-up', 'semrs-pro' ), 'cf7' => 'Contact Form 7', 'wpforms' => 'WPForms', 'gravity' => 'Gravity Forms', 'elementor' => 'Elementor' );
	?>
	<?php if ( defined( 'AKISMET_VERSION' ) ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Akismet is active too. SEMRS stops bots first; Akismet still checks what passes. You can keep both, or rely on SEMRS alone.', 'semrs-pro' ); ?></p></div>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="semrs_secpro_save"><input type="hidden" name="tab" value="spam"><?php wp_nonce_field( 'semrs_secpro_save' ); ?>
		<table class="form-table" role="presentation">
			<?php semrs_secpro_box( $s, 'spam_on', __( 'Spam protection', 'semrs-pro' ), __( 'Checks on your own server. No CAPTCHA, no outside service, nothing slows your pages (one tiny script).', 'semrs-pro' ) ); ?>
			<?php semrs_secpro_box( $s, 'spam_comments', __( 'Comments and reviews', 'semrs-pro' ), __( 'Spam goes to the Spam folder, so nothing is lost.', 'semrs-pro' ) ); ?>
			<?php semrs_secpro_box( $s, 'spam_forms', __( 'Contact forms', 'semrs-pro' ), __( 'Contact Form 7, WPForms, Gravity Forms and Elementor Pro forms.', 'semrs-pro' ) ); ?>
			<?php semrs_secpro_box( $s, 'spam_register', __( 'Sign-ups', 'semrs-pro' ), __( 'Stop fake account sign-ups (WordPress and WooCommerce).', 'semrs-pro' ) ); ?>
			<tr><th scope="row"><?php esc_html_e( 'Links allowed', 'semrs-pro' ); ?></th><td><input type="number" min="0" max="20" name="semrs_secpro[spam_links]" value="<?php echo esc_attr( $s['spam_links'] ); ?>" style="width:70px"> <?php esc_html_e( 'links at most in one message', 'semrs-pro' ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Blocked words', 'semrs-pro' ); ?></th><td><textarea name="semrs_secpro[spam_words]" rows="4" class="large-text"><?php echo esc_textarea( $s['spam_words'] ); ?></textarea><p class="description"><?php esc_html_e( 'One word or phrase per line. Messages, names or emails that contain one are spam.', 'semrs-pro' ); ?></p></td></tr>
			<?php semrs_secpro_box( $s, 'spam_script', __( 'Other alphabets', 'semrs-pro' ), __( 'Treat messages written mostly in Cyrillic, Chinese, Korean, Thai, Arabic or Hebrew as spam (only for sites that never get them).', 'semrs-pro' ) ); ?>
			<?php semrs_secpro_box( $s, 'spam_sfs', __( 'StopForumSpam check', 'semrs-pro' ), __( 'Also look up the sender’s IP and email in StopForumSpam’s free list of known spammers. This sends them to stopforumspam.org, so mention it in your privacy policy.', 'semrs-pro' ) ); ?>
			<tr><th scope="row"><?php esc_html_e( 'Empty the Spam folder', 'semrs-pro' ); ?></th><td><?php esc_html_e( 'after', 'semrs-pro' ); ?> <input type="number" min="7" max="365" name="semrs_secpro[spam_days]" value="<?php echo esc_attr( $s['spam_days'] ); ?>" style="width:70px"> <?php esc_html_e( 'days', 'semrs-pro' ); ?></td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<h2><?php esc_html_e( 'Recently stopped', 'semrs-pro' ); ?></h2>
	<?php if ( ! $log ) : ?>
		<p><?php esc_html_e( 'Nothing stopped yet.', 'semrs-pro' ); ?></p>
	<?php else : ?>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'When', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Where', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Why', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Text', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'IP (shortened)', 'semrs-pro' ); ?></th></tr></thead><tbody>
		<?php foreach ( $log as $l ) : ?>
			<tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $l['t'] ) ); ?></td><td><?php echo esc_html( $where[ $l['w'] ] ?? $l['w'] ); ?></td><td><?php echo esc_html( $reasons[ $l['r'] ] ?? $l['r'] ); ?></td><td><?php echo esc_html( $l['x'] ); ?></td><td><code><?php echo esc_html( $l['ip'] ); ?></code></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p><a class="button" href="<?php echo esc_url( $do( 'spam_clear' ) ); ?>"><?php esc_html_e( 'Clear the list', 'semrs-pro' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'edit-comments.php?comment_status=spam' ) ); ?>"><?php esc_html_e( 'Open the Spam folder', 'semrs-pro' ); ?></a></p>
	<?php endif; ?>
	<?php
}
