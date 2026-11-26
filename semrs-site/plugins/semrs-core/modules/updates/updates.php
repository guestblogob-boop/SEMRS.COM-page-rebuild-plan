<?php
/**
 * SEMRS → Auto-updates: safe automatic updates for WordPress, plugins, themes and translations.
 *
 * - Choose what updates automatically: WordPress (security/minor only, or all versions), plugins
 *   (all, or all except a skip list), themes and translations.
 * - Update window: automatic updates run only in quiet hours (default 02:00–05:00 site time); a
 *   daily run starts at the window, so updates do not wait for random visits.
 * - Health check after every update run: the home page and the log-in page must load without a
 *   server error or a "critical error" message. If they fail and SEMRS Pro Backups saved a copy of
 *   an updated plugin or theme before the update, that item is rolled back automatically.
 *   (WordPress itself also rolls back a plugin that fails with a fatal error during an auto-update.)
 * - Email report and an update log (last 50 runs).
 * Replaces host-managed auto-update plugins: switch those off in the hosting panel so updates are
 * not done twice.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_autoupd_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_autoupd', array() ),
		array(
			'core'       => 'minor', // minor | all | off.
			'plugins'    => 'all',   // all | off.
			'skip'       => '',      // Plugin files to skip, one per line.
			'themes'     => 'all',   // all | off.
			'lang'       => '1',
			'win_from'   => 2,
			'win_to'     => 5,
			'health'     => '1',
			'email'      => '1',
		)
	);
}

/**
 * Is "now" inside the update window (site time)?
 *
 * @return bool
 */
function semrs_autoupd_in_window() {
	$s    = semrs_autoupd_settings();
	$from = (int) $s['win_from'];
	$to   = (int) $s['win_to'];
	$h    = (int) wp_date( 'G' );
	if ( $from === $to ) {
		return true; // Any time.
	}
	return $from < $to ? ( $h >= $from && $h < $to ) : ( $h >= $from || $h < $to );
}

// What updates automatically.
add_filter( 'allow_minor_auto_core_updates', function ( $v ) { return 'off' !== semrs_autoupd_settings()['core']; } );
add_filter( 'allow_major_auto_core_updates', function ( $v ) { return 'all' === semrs_autoupd_settings()['core']; } );
add_filter( 'allow_dev_auto_core_updates', '__return_false' );
add_filter(
	'auto_update_plugin',
	function ( $update, $item ) {
		$s = semrs_autoupd_settings();
		if ( 'off' === $s['plugins'] ) {
			return false;
		}
		$file = is_object( $item ) && isset( $item->plugin ) ? (string) $item->plugin : '';
		$skip = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['skip'] ) ) );
		return $file && in_array( $file, $skip, true ) ? false : true;
	},
	20,
	2
);
add_filter( 'auto_update_theme', function ( $update ) { return 'all' === semrs_autoupd_settings()['themes']; }, 20 );
add_filter( 'auto_update_translation', function ( $update ) { return '1' === semrs_autoupd_settings()['lang']; }, 20 );

// Only update inside the window (our own daily run is inside it by design).
add_filter(
	'automatic_updater_disabled',
	function ( $disabled ) {
		return $disabled || ! ( semrs_autoupd_in_window() || ! empty( $GLOBALS['semrs_autoupd_now'] ) );
	},
	20
);

// Daily run at the start of the window: check for updates, then run them.
add_action(
	'init',
	function () {
		$s    = semrs_autoupd_settings();
		$next = wp_next_scheduled( 'semrs_autoupd_run' );
		$want = strtotime( 'today ' . sprintf( '%02d:05', (int) $s['win_from'] ), current_time( 'timestamp' ) ) - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- converting site time to UTC.
		if ( $want < time() ) {
			$want += DAY_IN_SECONDS;
		}
		if ( ! $next || abs( $next - $want ) > HOUR_IN_SECONDS ) {
			if ( $next ) {
				wp_unschedule_event( $next, 'semrs_autoupd_run' );
			}
			wp_schedule_event( $want, 'daily', 'semrs_autoupd_run' );
		}
	}
);
add_action(
	'semrs_autoupd_run',
	function () {
		require_once ABSPATH . 'wp-admin/includes/update.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		wp_version_check();
		wp_update_plugins();
		wp_update_themes();
		if ( function_exists( 'wp_maybe_auto_update' ) ) {
			wp_maybe_auto_update();
		}
	}
);

/**
 * Do the home page and the log-in page load without errors?
 *
 * @return array [ok, message]
 */
function semrs_autoupd_health() {
	foreach ( array( home_url( '/?semrs_health=' . time() ), wp_login_url() ) as $url ) {
		$r = wp_remote_get( $url, array( 'timeout' => 20, 'redirection' => 2, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
		if ( is_wp_error( $r ) ) {
			return array( false, $url . ': ' . $r->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $r );
		$body = (string) wp_remote_retrieve_body( $r );
		if ( $code >= 500 || false !== stripos( $body, 'critical error on this website' ) || false !== stripos( $body, 'Fatal error' ) ) {
			return array( false, $url . ': HTTP ' . $code );
		}
	}
	return array( true, __( 'Home page and log-in page load normally.', 'semrs-core' ) );
}

// After every automatic update run: health check, rollback if needed, log and email.
add_action(
	'automatic_updates_complete',
	function ( $results ) {
		$s       = semrs_autoupd_settings();
		$updated = array();
		$failed  = array();
		$items   = array();
		foreach ( (array) $results as $type => $list ) {
			foreach ( (array) $list as $res ) {
				$name = isset( $res->name ) ? wp_strip_all_tags( (string) $res->name ) : $type;
				if ( ! empty( $res->result ) && ! is_wp_error( $res->result ) ) {
					$updated[] = $name;
					$items[]   = array( $type, $res->item ?? null );
				} elseif ( isset( $res->result ) ) {
					$failed[] = $name;
				}
			}
		}
		if ( ! $updated && ! $failed ) {
			return;
		}
		$health     = '1' === $s['health'] ? semrs_autoupd_health() : array( true, '' );
		$rolledback = array();
		if ( ! $health[0] && function_exists( 'semrs_bk_rollback' ) ) {
			$copies = (array) get_option( 'semrs_bk_rollbacks', array() );
			foreach ( $items as list( $type, $item ) ) {
				$slug = 'plugin' === $type && isset( $item->plugin ) ? dirname( (string) $item->plugin ) : ( 'theme' === $type && isset( $item->theme ) ? (string) $item->theme : '' );
				if ( ! $slug ) {
					continue;
				}
				// The newest copy saved for this item in the last two hours (saved just before the update).
				$best = '';
				foreach ( $copies as $file => $c ) {
					if ( $c['slug'] === $slug && $c['type'] === $type && $c['time'] > time() - 2 * HOUR_IN_SECONDS && ( ! $best || $c['time'] > $copies[ $best ]['time'] ) ) {
						$best = $file;
					}
				}
				if ( $best && true === semrs_bk_rollback( $best ) ) {
					$rolledback[] = $slug;
				}
			}
			if ( $rolledback ) {
				$health = semrs_autoupd_health();
			}
		}
		$entry = array( 'time' => time(), 'updated' => $updated, 'failed' => $failed, 'ok' => $health[0], 'note' => $health[1], 'rolledback' => $rolledback );
		$log   = (array) get_option( 'semrs_autoupd_log', array() );
		array_unshift( $log, $entry );
		update_option( 'semrs_autoupd_log', array_slice( $log, 0, 50 ), false );
		if ( '1' === $s['email'] ) {
			$lines   = array();
			$lines[] = $updated ? __( 'Updated:', 'semrs-core' ) . ' ' . implode( ', ', $updated ) : '';
			$lines[] = $failed ? __( 'Could not update:', 'semrs-core' ) . ' ' . implode( ', ', $failed ) : '';
			$lines[] = $health[0] ? __( 'Health check: the site loads normally.', 'semrs-core' ) : __( 'Health check failed:', 'semrs-core' ) . ' ' . $health[1];
			$lines[] = $rolledback ? __( 'Rolled back to the previous version:', 'semrs-core' ) . ' ' . implode( ', ', $rolledback ) : '';
			$lines[] = admin_url( 'admin.php?page=semrs-auto-updates' );
			/* translators: %s: site name */
			wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] Automatic updates report', 'semrs-core' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ), implode( "\n", array_filter( $lines ) ) );
		}
	},
	20
);

// WordPress's own per-update emails are replaced by the SEMRS report (when the report is on).
add_filter( 'auto_plugin_update_send_email', function ( $send ) { return '1' === semrs_autoupd_settings()['email'] ? false : $send; } );
add_filter( 'auto_theme_update_send_email', function ( $send ) { return '1' === semrs_autoupd_settings()['email'] ? false : $send; } );

/**
 * Host-managed auto-update plugins found in mu-plugins (should be switched off in the hosting panel).
 *
 * @return string[]
 */
function semrs_autoupd_host_plugins() {
	if ( ! function_exists( 'get_mu_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$out = array();
	foreach ( get_mu_plugins() as $file => $p ) {
		if ( preg_match( '/auto.?update/i', $p['Name'] . ' ' . $file ) && false === stripos( $p['Author'], 'SEMRS' ) ) {
			$out[] = $p['Name'];
		}
	}
	return $out;
}

// Admin page.
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Auto-updates', 'semrs-core' ), __( 'Auto-updates', 'semrs-core' ), 'manage_options', 'semrs-auto-updates', 'semrs_autoupd_page' );
	},
	30
);
add_action(
	'admin_post_semrs_autoupd_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'semrs-core' ) );
		}
		check_admin_referer( 'semrs_autoupd' );
		$in = wp_unslash( $_POST['semrs_autoupd'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below.
		$s  = array(
			'core'     => in_array( $in['core'] ?? '', array( 'minor', 'all', 'off' ), true ) ? $in['core'] : 'minor',
			'plugins'  => in_array( $in['plugins'] ?? '', array( 'all', 'off' ), true ) ? $in['plugins'] : 'all',
			'skip'     => implode( "\n", array_map( 'sanitize_text_field', (array) ( $in['skip'] ?? array() ) ) ),
			'themes'   => in_array( $in['themes'] ?? '', array( 'all', 'off' ), true ) ? $in['themes'] : 'all',
			'lang'     => empty( $in['lang'] ) ? '0' : '1',
			'win_from' => max( 0, min( 23, (int) ( $in['win_from'] ?? 2 ) ) ),
			'win_to'   => max( 0, min( 23, (int) ( $in['win_to'] ?? 5 ) ) ),
			'health'   => empty( $in['health'] ) ? '0' : '1',
			'email'    => empty( $in['email'] ) ? '0' : '1',
		);
		update_option( 'semrs_autoupd', $s, false );
		wp_clear_scheduled_hook( 'semrs_autoupd_run' );
		if ( isset( $_POST['run_now'] ) ) {
			$GLOBALS['semrs_autoupd_now'] = true; // The owner asked for it: run outside the window too.
			do_action( 'semrs_autoupd_run' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-auto-updates&saved=1' ) );
		exit;
	}
);

/**
 * Render SEMRS → Auto-updates.
 */
function semrs_autoupd_page() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$s    = semrs_autoupd_settings();
	$skip = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['skip'] ) ) );
	$log  = (array) get_option( 'semrs_autoupd_log', array() );
	$host = semrs_autoupd_host_plugins();
	$next = wp_next_scheduled( 'semrs_autoupd_run' );
	$pro  = function_exists( 'semrs_bk_rollback' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Auto-updates', 'semrs-core' ); ?></h1>
		<p style="max-width:820px"><?php esc_html_e( 'Keep WordPress, plugins, themes and translations up to date automatically, in quiet hours, with a health check after every update run and an emailed report.', 'semrs-core' ); ?></p>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification -- display only. ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'semrs-core' ); ?></p></div>
		<?php endif; ?>
		<?php if ( $host ) : ?>
			<div class="notice notice-warning inline" style="max-width:820px"><p>
				<?php
				/* translators: %s: plugin names */
				echo esc_html( sprintf( __( 'Also active: %s. Switch it off in your hosting panel (for Hostinger: hPanel → WordPress → Overview → Auto updates) so updates are not done twice. SEMRS Auto-updates does the same job with a health check and rollback.', 'semrs-core' ), implode( ', ', $host ) ) );
				?>
			</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_autoupd' ); ?>
			<input type="hidden" name="action" value="semrs_autoupd_save">
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'WordPress', 'semrs-core' ); ?></th><td><select name="semrs_autoupd[core]">
					<option value="minor" <?php selected( $s['core'], 'minor' ); ?>><?php esc_html_e( 'Security and maintenance releases (recommended)', 'semrs-core' ); ?></option>
					<option value="all" <?php selected( $s['core'], 'all' ); ?>><?php esc_html_e( 'All versions, including major releases', 'semrs-core' ); ?></option>
					<option value="off" <?php selected( $s['core'], 'off' ); ?>><?php esc_html_e( 'Off', 'semrs-core' ); ?></option>
				</select></td></tr>
				<tr><th><?php esc_html_e( 'Plugins', 'semrs-core' ); ?></th><td>
					<select name="semrs_autoupd[plugins]"><option value="all" <?php selected( $s['plugins'], 'all' ); ?>><?php esc_html_e( 'Update all, except the ones ticked below', 'semrs-core' ); ?></option><option value="off" <?php selected( $s['plugins'], 'off' ); ?>><?php esc_html_e( 'Off', 'semrs-core' ); ?></option></select>
					<fieldset style="margin-top:8px"><legend class="screen-reader-text"><?php esc_html_e( 'Plugins to skip', 'semrs-core' ); ?></legend>
					<?php foreach ( get_plugins() as $file => $p ) : ?>
						<label style="display:block"><input type="checkbox" name="semrs_autoupd[skip][]" value="<?php echo esc_attr( $file ); ?>" <?php checked( in_array( $file, $skip, true ) ); ?>> <?php echo esc_html( sprintf( /* translators: %s: plugin name */ __( 'Skip %s', 'semrs-core' ), $p['Name'] ) ); ?></label>
					<?php endforeach; ?>
					</fieldset>
				</td></tr>
				<tr><th><?php esc_html_e( 'Themes', 'semrs-core' ); ?></th><td><select name="semrs_autoupd[themes]"><option value="all" <?php selected( $s['themes'], 'all' ); ?>><?php esc_html_e( 'Update all', 'semrs-core' ); ?></option><option value="off" <?php selected( $s['themes'], 'off' ); ?>><?php esc_html_e( 'Off', 'semrs-core' ); ?></option></select></td></tr>
				<tr><th><?php esc_html_e( 'Translations', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="semrs_autoupd[lang]" value="1" <?php checked( '1', $s['lang'] ); ?>> <?php esc_html_e( 'Update translations automatically', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Update window (site time)', 'semrs-core' ); ?></th><td>
					<label><?php esc_html_e( 'From', 'semrs-core' ); ?> <input type="number" min="0" max="23" name="semrs_autoupd[win_from]" value="<?php echo esc_attr( (string) $s['win_from'] ); ?>" style="width:70px">:00</label>
					<label><?php esc_html_e( 'to', 'semrs-core' ); ?> <input type="number" min="0" max="23" name="semrs_autoupd[win_to]" value="<?php echo esc_attr( (string) $s['win_to'] ); ?>" style="width:70px">:00</label>
					<p class="description"><?php esc_html_e( 'Updates run only in these hours, when few people visit. Same hour twice = any time.', 'semrs-core' ); ?>
					<?php
					if ( $next ) {
						/* translators: %s: date and time */
						echo ' ' . esc_html( sprintf( __( 'Next run: %s.', 'semrs-core' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) ) );
					}
					?>
					</p></td></tr>
				<tr><th><?php esc_html_e( 'Health check', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="semrs_autoupd[health]" value="1" <?php checked( '1', $s['health'] ); ?>> <?php esc_html_e( 'After updates, check that the home page and log-in page load; roll back what was just updated if they do not', 'semrs-core' ); ?></label>
					<p class="description"><?php echo $pro ? esc_html__( 'Rollback uses the copies SEMRS Pro Backups saves before each update (SEMRS → Backups → "Save a copy before updates").', 'semrs-core' ) : esc_html__( 'Automatic rollback needs SEMRS Pro Backups, which saves a copy before each update. WordPress itself still rolls back a plugin that crashes during its update.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Email report', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="semrs_autoupd[email]" value="1" <?php checked( '1', $s['email'] ); ?>> <?php esc_html_e( 'Email the site admin one short report after each update run', 'semrs-core' ); ?></label></td></tr>
			</table>
			<?php submit_button( __( 'Save', 'semrs-core' ), 'primary', 'submit', false ); ?>
			<?php submit_button( __( 'Save and check for updates now', 'semrs-core' ), 'secondary', 'run_now', false ); ?>
		</form>
		<h2><?php esc_html_e( 'Update log', 'semrs-core' ); ?></h2>
		<?php if ( $log ) : ?>
			<table class="widefat striped" style="max-width:1100px"><thead><tr><th><?php esc_html_e( 'When', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Updated', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Health', 'semrs-core' ); ?></th></tr></thead><tbody>
			<?php foreach ( $log as $e ) : ?>
				<tr><td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $e['time'] ) ); ?></td>
				<td><?php echo esc_html( implode( ', ', (array) $e['updated'] ) ); ?><?php echo $e['failed'] ? '<br><strong>' . esc_html__( 'Failed:', 'semrs-core' ) . '</strong> ' . esc_html( implode( ', ', (array) $e['failed'] ) ) : ''; ?></td>
				<td><?php echo $e['ok'] ? '<span style="color:#00813a">' . esc_html__( 'OK', 'semrs-core' ) . '</span>' : '<span style="color:#b32d2e">' . esc_html( (string) $e['note'] ) . '</span>'; ?><?php echo ! empty( $e['rolledback'] ) ? '<br>' . esc_html__( 'Rolled back:', 'semrs-core' ) . ' ' . esc_html( implode( ', ', (array) $e['rolledback'] ) ) : ''; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?>
			<p><?php esc_html_e( 'No automatic updates yet.', 'semrs-core' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
