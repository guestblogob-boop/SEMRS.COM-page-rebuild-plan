<?php
/**
 * SEMRS → Backups screen: backups, restore, schedule, storage, migration.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Backups', 'semrs-pro' ), __( 'Backups', 'semrs-pro' ), 'manage_options', 'semrs-backups', 'semrs_bk_page' );
	},
	34
);

/**
 * Google sends people back here after "Allow".
 */
add_action(
	'admin_init',
	function () {
		// phpcs:disable WordPress.Security.NonceVerification -- checked with the state value.
		if ( 'semrs-backups' !== ( $_GET['page'] ?? '' ) || empty( $_GET['code'] ) || empty( $_GET['state'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'semrs_bk_gdrive' ) ) {
			return;
		}
		$o   = semrs_bk_dest_opts( 'gdrive' );
		$res = wp_remote_post( 'https://oauth2.googleapis.com/token', array( 'body' => array( 'grant_type' => 'authorization_code', 'code' => sanitize_text_field( wp_unslash( $_GET['code'] ) ), 'client_id' => $o['client_id'] ?? '', 'client_secret' => $o['client_secret'] ?? '', 'redirect_uri' => admin_url( 'admin.php?page=semrs-backups' ) ), 'timeout' => 20 ) );
		// phpcs:enable
		$j   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! empty( $j['refresh_token'] ) ) {
			semrs_bk_dest_save( 'gdrive', array( 'refresh' => $j['refresh_token'], 'access' => $j['access_token'], 'access_exp' => time() + (int) $j['expires_in'], 'folder_id' => '' ) );
			set_transient( 'semrs_bk_msg_' . get_current_user_id(), __( 'Google Drive is connected.', 'semrs-pro' ), 60 );
		} else {
			set_transient( 'semrs_bk_err_' . get_current_user_id(), semrs_bk_http_error( $res, 'Google Drive' )->get_error_message(), 60 );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-backups&tab=storage' ) );
		exit;
	}
);

/**
 * Back to the screen with a message.
 *
 * @param string          $tab Tab.
 * @param true|WP_Error|string $res Result or message.
 */
function semrs_bk_back( $tab, $res = true ) {
	if ( is_wp_error( $res ) ) {
		set_transient( 'semrs_bk_err_' . get_current_user_id(), $res->get_error_message(), 60 );
	} elseif ( is_string( $res ) ) {
		set_transient( 'semrs_bk_msg_' . get_current_user_id(), $res, 60 );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=semrs-backups&tab=' . $tab ) );
	exit;
}

/**
 * All form actions.
 */
add_action(
	'admin_post_semrs_bk',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_bk' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
		}
		$do  = sanitize_key( wp_unslash( $_REQUEST['do'] ?? '' ) );
		$id  = preg_replace( '/[^0-9a-z-]/', '', strtolower( (string) wp_unslash( $_REQUEST['id'] ?? '' ) ) );
		$in  = (array) wp_unslash( $_POST['bk'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per key.
		$all = array( 'db', 'plugins', 'themes', 'uploads', 'mu', 'others' );
		switch ( $do ) {
			case 'backup':
				$presets = array( 'site' => $all, 'db' => array( 'db' ), 'plugins' => array( 'plugins' ), 'themes' => array( 'themes' ), 'uploads' => array( 'uploads' ) );
				$parts   = isset( $presets[ $in['preset'] ?? '' ] ) ? $presets[ $in['preset'] ] : array_values( array_intersect( $all, array_keys( array_filter( (array) ( $in['parts'] ?? array() ) ) ) ) );
				$res   = $parts ? semrs_bk_start( 'manual', $parts ) : new WP_Error( 'semrs', __( 'Choose at least one thing to back up.', 'semrs-pro' ) );
				semrs_bk_back( 'backups', $res );
				break;
			case 'restore':
				$parts = array_values( array_intersect( $all, array_keys( array_filter( (array) ( $in['parts'] ?? array() ) ) ) ) );
				if ( empty( $in['sure'] ) || ! $parts ) {
					semrs_bk_back( 'backups', new WP_Error( 'semrs', __( 'Choose what to restore and tick the confirmation box.', 'semrs-pro' ) ) );
				}
				semrs_bk_back( 'backups', semrs_bk_restore_start( $id, $parts, false, (string) ( $in['pass'] ?? '' ) ) );
				break;
			case 'verify':
				$rep = semrs_bk_verify( $id, (string) ( $in['pass'] ?? '' ) );
				semrs_bk_back( 'backups', is_wp_error( $rep ) ? $rep : implode( ' | ', $rep ) );
				break;
			case 'rollback':
				$file = basename( (string) ( $in['file'] ?? '' ) );
				$res  = semrs_bk_rollback( $file );
				semrs_bk_back( 'backups', is_wp_error( $res ) ? $res : __( 'Rolled back. The version you replaced was kept as a copy too.', 'semrs-pro' ) );
				break;
			case 'import':
				if ( empty( $in['sure'] ) ) {
					semrs_bk_back( 'backups', new WP_Error( 'semrs', __( 'Tick the box to confirm that this site will be replaced.', 'semrs-pro' ) ) );
				}
				semrs_bk_back( 'backups', semrs_bk_import_start( (string) ( $in['key'] ?? '' ) ) );
				break;
			case 'rescan':
				$n = semrs_bk_rescan( sanitize_key( $in['dest'] ?? '' ) );
				/* translators: %d: number */
				semrs_bk_back( 'backups', is_wp_error( $n ) ? $n : sprintf( _n( '%d backup found in storage.', '%d backups found in storage.', $n, 'semrs-pro' ), $n ) );
				break;
			case 'fetch':
				semrs_bk_back( 'backups', semrs_bk_fetch_start( $id, sanitize_key( $in['dest'] ?? '' ) ) );
				break;
			case 'delete':
				$hist = (array) get_option( 'semrs_bk_history', array() );
				$h    = $hist[ $id ] ?? array( 'files' => array_map( 'basename', (array) glob( semrs_bk_dir() . $id . '-*' ) ), 'dests' => array() );
				semrs_bk_delete_set( $id, $h );
				unset( $hist[ $id ] );
				update_option( 'semrs_bk_history', $hist, false );
				semrs_bk_back( 'backups', __( 'Backup deleted.', 'semrs-pro' ) );
				break;
			case 'cancel':
				semrs_bk_save_job( null );
				semrs_bk_back( 'backups', __( 'Stopped.', 'semrs-pro' ) );
				break;
			case 'settings':
				$s = (array) get_option( 'semrs_bk', array() );
				foreach ( $all as $p ) {
					$s[ $p ] = empty( $in[ $p ] ) ? '' : '1';
				}
				$s['schedule']       = in_array( $in['schedule'] ?? '', array( 'off', 'twicedaily', 'daily', 'weekly', 'monthly' ), true ) ? $in['schedule'] : 'daily';
				$s['hour']           = max( 0, min( 23, absint( $in['hour'] ?? 3 ) ) );
				$s['keep']           = max( 1, min( 100, absint( $in['keep'] ?? 7 ) ) );
				$s['split']          = max( 10, min( 2000, absint( $in['split'] ?? 100 ) ) );
				$s['maxfile']        = min( 100000, absint( $in['maxfile'] ?? 0 ) );
				$s['exclude']        = implode( "\n", array_filter( array_map( 'sanitize_text_field', preg_split( '/[\r\n]+/', (string) ( $in['exclude'] ?? '' ) ) ) ) );
				$s['notify']         = in_array( $in['notify'] ?? '', array( 'never', 'fail', 'always' ), true ) ? $in['notify'] : 'fail';
				$s['others_plugins'] = empty( $in['others_plugins'] ) ? '' : '1';
				$s['migrate_max']    = max( 100, min( 1048576, absint( $in['migrate_max'] ?? 10240 ) ) );
				$s['diff']           = empty( $in['diff'] ) ? '' : '1';
				$s['full_every']     = max( 1, min( 90, absint( $in['full_every'] ?? 7 ) ) );
				$s['preupdate']      = empty( $in['preupdate'] ) ? '' : '1';
				$s['encrypt']        = empty( $in['encrypt'] ) ? '' : '1';
				if ( isset( $in['pass'] ) && '' !== $in['pass'] ) {
					$s['pass'] = (string) $in['pass']; // Kept as typed: it is the encryption key.
				}
				global $wpdb;
				$s['skip_tables'] = array_values( array_filter( array_map( 'sanitize_text_field', array_keys( array_filter( (array) ( $in['skip_tables'] ?? array() ) ) ) ), function ( $t ) use ( $wpdb ) { return 0 === strpos( $t, $wpdb->prefix ) && ! in_array( $t, array( $wpdb->options, $wpdb->users, $wpdb->usermeta ), true ); } ) );
				update_option( 'semrs_bk', $s );
				wp_clear_scheduled_hook( 'semrs_bk_scheduled' );
				semrs_bk_back( 'settings', __( 'Settings saved.', 'semrs-pro' ) );
				break;
			case 'storage':
				$d = sanitize_key( $in['dest'] ?? '' );
				if ( ! isset( semrs_bk_dests()[ $d ] ) ) {
					semrs_bk_back( 'storage' );
				}
				$fields = array( 'key', 'secret', 'bucket', 'region', 'endpoint', 'path', 'app_key', 'client_id', 'client_secret', 'host', 'port', 'user', 'pass', 'url', 'to', 'max' );
				$vals   = array();
				foreach ( $fields as $f ) {
					if ( isset( $in[ $f ] ) && ! ( in_array( $f, array( 'secret', 'pass', 'client_secret' ), true ) && '' === $in[ $f ] ) ) {
						$vals[ $f ] = 'url' === $f ? esc_url_raw( $in[ $f ] ) : sanitize_text_field( $in[ $f ] );
					}
				}
				$vals['ssl']    = empty( $in['ssl'] ) ? '' : '1';
				$vals['active'] = empty( $in['active'] ) ? '' : '1';
				semrs_bk_dest_save( $d, $vals );
				$s     = (array) get_option( 'semrs_bk', array() );
				$dests = array_diff( (array) ( $s['dests'] ?? array() ), array( $d ) );
				if ( ! empty( $in['on'] ) ) {
					$dests[] = $d;
				}
				$s['dests'] = array_values( $dests );
				update_option( 'semrs_bk', $s );
				// Dropbox: swap the pasted code for a lasting sign-in.
				if ( 'dropbox' === $d && ! empty( $in['code'] ) ) {
					$o   = semrs_bk_dest_opts( 'dropbox' );
					$res = wp_remote_post( 'https://api.dropboxapi.com/oauth2/token', array( 'body' => array( 'grant_type' => 'authorization_code', 'code' => sanitize_text_field( $in['code'] ), 'code_verifier' => $o['pkce'] ?? '', 'client_id' => $o['app_key'] ?? '' ), 'timeout' => 20 ) );
					$j   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
					if ( empty( $j['refresh_token'] ) ) {
						semrs_bk_back( 'storage', semrs_bk_http_error( $res, 'Dropbox' ) );
					}
					semrs_bk_dest_save( 'dropbox', array( 'refresh' => $j['refresh_token'], 'access' => $j['access_token'], 'access_exp' => time() + (int) $j['expires_in'], 'pkce' => '' ) );
				}
				if ( ! empty( $in['test'] ) ) {
					$t = semrs_bk_remote_test( $d );
					semrs_bk_back( 'storage', is_wp_error( $t ) ? $t : __( 'Connection works: a test file was saved and removed.', 'semrs-pro' ) );
				}
				semrs_bk_back( 'storage', __( 'Storage saved.', 'semrs-pro' ) );
				break;
			case 'migrate_make':
				semrs_bk_back( 'migrate', semrs_bk_start( 'migrate', $all ) );
				break;
			case 'migrate_pull':
				if ( empty( $in['sure'] ) ) {
					semrs_bk_back( 'migrate', new WP_Error( 'semrs', __( 'Tick the box to confirm that this site will be replaced.', 'semrs-pro' ) ) );
				}
				semrs_bk_back( 'backups', semrs_bk_pull_start( (string) ( $in['key'] ?? '' ) ) );
				break;
		}
		semrs_bk_back( 'backups' );
	}
);

/**
 * Download one backup file.
 */
add_action(
	'admin_post_semrs_bk_download',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_bk_download' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
		}
		$f    = basename( (string) wp_unslash( $_GET['f'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = semrs_bk_dir() . $f;
		if ( ! preg_match( '/^[0-9a-z][0-9a-z._-]*\.(?:zip|json|enc)$/i', $f ) || ! is_file( $path ) ) {
			wp_die( esc_html__( 'File not found.', 'semrs-pro' ) );
		}
		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $f . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/**
 * Upload backup files from your computer (in 4 MB pieces, any size).
 */
add_action(
	'wp_ajax_semrs_bk_upload',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'semrs_bk_upload', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'semrs-pro' ) ), 403 );
		}
		$name = strtolower( basename( (string) wp_unslash( $_POST['name'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$off  = absint( $_POST['offset'] ?? 0 );
		$ok = preg_match( '/^\d{4}-\d{2}-\d{2}-\d{4}-[0-9a-z]{4}-(?:manifest\.json|db\.zip(?:\.enc)?|(?:plugins|themes|uploads|mu|others)-\d+\.zip)$/', $name ) // SEMRS.
			|| preg_match( '/^[0-9a-z][0-9a-z._ -]*\.wpress$/', $name ) // All-in-One WP Migration.
			|| preg_match( '/^backup_[0-9-]+_[0-9a-z._-]+-(?:db\d*\.gz|(?:plugins|themes|uploads|others|mu-plugins)\d*\.zip)$/', $name ); // UpdraftPlus.
		if ( ! $ok || empty( $_FILES['chunk']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Only SEMRS, UpdraftPlus and All-in-One WP Migration (.wpress) backup files can be uploaded here.', 'semrs-pro' ) ) );
		}
		$name = str_replace( ' ', '-', $name );
		$path = semrs_bk_dir() . $name;
		$data = (string) file_get_contents( $_FILES['chunk']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions,WordPress.Security.ValidatedSanitizedInput
		file_put_contents( $path, $data, $off ? FILE_APPEND : 0 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		wp_send_json_success( array( 'size' => filesize( $path ) ) );
	}
);

/**
 * Part labels.
 *
 * @return array
 */
function semrs_bk_part_labels() {
	return array(
		'db'      => __( 'Database', 'semrs-pro' ),
		'plugins' => __( 'Plugins', 'semrs-pro' ),
		'themes'  => __( 'Themes', 'semrs-pro' ),
		'uploads' => __( 'Media uploads', 'semrs-pro' ),
		'mu'      => __( 'Must-use plugins', 'semrs-pro' ),
		'others'  => __( 'Other wp-content files', 'semrs-pro' ),
	);
}

/**
 * The screen.
 */
function semrs_bk_page() {
	$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? 'backups' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'backups'  => __( 'Backups', 'semrs-pro' ),
		'settings' => __( 'Schedule & contents', 'semrs-pro' ),
		'storage'  => __( 'Storage', 'semrs-pro' ),
		'migrate'  => __( 'Migrate', 'semrs-pro' ),
	);
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'backups';
	$uid  = get_current_user_id();
	$msg  = get_transient( 'semrs_bk_msg_' . $uid );
	$err  = get_transient( 'semrs_bk_err_' . $uid );
	delete_transient( 'semrs_bk_msg_' . $uid );
	delete_transient( 'semrs_bk_err_' . $uid );
	$job  = semrs_bk_job();
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Backups', 'semrs-pro' ); ?></h1>
		<?php if ( $msg ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>
		<?php if ( $err ) : ?><div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div><?php endif; ?>
		<?php if ( semrs_bk_other_plugin() && '1' !== semrs_bk_settings()['others_plugins'] ) : ?>
			<?php /* translators: %s: plugin */ ?>
			<div class="notice notice-info inline"><p><?php echo esc_html( sprintf( __( '%s is active, so the SEMRS backup schedule is paused and two backups never run at once. Manual backups, restore and migration still work. To use SEMRS instead, deactivate %s.', 'semrs-pro' ), semrs_bk_other_plugin(), semrs_bk_other_plugin() ) ); ?></p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $l ) : ?>
				<a class="nav-tab <?php echo $k === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-backups&tab=' . $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		if ( $job && 'done' !== $job['phase'] ) {
			semrs_bk_running( $job );
		} elseif ( $job && 'done' === $job['phase'] ) {
			semrs_bk_last_job( $job );
		}
		call_user_func( 'semrs_bk_tab_' . $tab );
		?>
	</div>
	<?php
}

/**
 * Form opening.
 *
 * @param string $do Action.
 */
function semrs_bk_form( $do ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="semrs_bk"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	wp_nonce_field( 'semrs_bk' );
}

/**
 * Progress box for a running job (drives the steps from the browser).
 *
 * @param array $job Job.
 */
function semrs_bk_running( $job ) {
	$what = array( 'backup' => __( 'Backing up…', 'semrs-pro' ), 'restore' => __( 'Restoring… Keep this page open.', 'semrs-pro' ), 'pull' => __( 'Copying the site from the old address… Keep this page open.', 'semrs-pro' ), 'fetch' => __( 'Fetching the backup from storage…', 'semrs-pro' ) );
	?>
	<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 18px;margin:16px 0;max-width:820px">
		<h2 style="margin-top:0"><?php echo esc_html( $what[ $job['type'] ] ?? '' ); ?></h2>
		<div style="height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden"><span id="semrs-bk-bar" style="display:block;height:100%;width:3%;background:#2563eb;transition:width .3s"></span></div>
		<pre id="semrs-bk-log" style="white-space:pre-wrap;font-size:12px;max-height:180px;overflow:auto;margin:10px 0 0"><?php echo esc_html( implode( "\n", array_slice( (array) $job['log'], -8 ) ) ); ?></pre>
		<?php semrs_bk_form( 'cancel' ); ?><button class="button-link" style="color:#b32d2e"><?php esc_html_e( 'Stop', 'semrs-pro' ); ?></button></form>
	</div>
	<script>
	(function(){var tok='<?php echo esc_js( $job['token'] ); ?>',bar=document.getElementById('semrs-bk-bar'),log=document.getElementById('semrs-bk-log'),fails=0;
	function step(){var d=new FormData();d.append('action','semrs_bk_step');d.append('token',tok);
	fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:d}).then(function(r){return r.json()}).then(function(j){fails=0;if(!j.success){log.textContent+='\n'+((j.data&&j.data.message)||'');return}bar.style.width=Math.max(3,j.data.pct)+'%';log.textContent=j.data.log.join('\n');log.scrollTop=1e6;if(j.data.phase==='done'){setTimeout(function(){location.reload()},1200)}else{step()}}).catch(function(){if(++fails<20){setTimeout(step,4000)}else{log.textContent+='\n<?php echo esc_js( __( 'The server stopped answering. Reload this page to continue.', 'semrs-pro' ) ); ?>'}})}
	step()})();
	</script>
	<?php
}

/**
 * Result of the last job.
 *
 * @param array $job Job.
 */
function semrs_bk_last_job( $job ) {
	$bad = ! empty( $job['error'] ) || ! empty( $job['warn'] );
	echo '<div class="notice ' . ( $bad ? 'notice-warning' : 'notice-success' ) . ' inline" style="max-width:820px"><p><strong>' . esc_html( $bad ? __( 'The last job finished with problems:', 'semrs-pro' ) : __( 'The last job finished.', 'semrs-pro' ) ) . '</strong></p><pre style="white-space:pre-wrap;font-size:12px">' . esc_html( implode( "\n", array_slice( (array) $job['log'], -10 ) ) ) . '</pre>';
	if ( 'backup' === $job['type'] && 'migrate' === ( $job['by'] ?? '' ) && empty( $job['error'] ) ) {
		$key = semrs_bk_make_key( $job['id'] );
		echo '<p><strong>' . esc_html__( 'Migration key (valid for 24 hours):', 'semrs-pro' ) . '</strong></p><textarea readonly rows="3" class="large-text code" onclick="this.select()">' . esc_textarea( $key ) . '</textarea><p>' . esc_html__( 'On the new site: install WordPress, SEMRS Core and SEMRS Pro, open SEMRS → Backups → Migrate, and paste this key.', 'semrs-pro' ) . '</p>';
	}
	echo '</div>';
	semrs_bk_save_job( null );
}

/**
 * Backups tab.
 */
function semrs_bk_tab_backups() {
	$set    = semrs_bk_settings();
	$labels = semrs_bk_part_labels();
	$sets   = semrs_bk_sets();
	$dests  = semrs_bk_dests();
	?>
	<h2><?php esc_html_e( 'Back up now', 'semrs-pro' ); ?></h2>
	<?php semrs_bk_form( 'backup' ); ?>
		<p>
			<button class="button button-primary" name="bk[preset]" value="site"><?php esc_html_e( 'Whole site', 'semrs-pro' ); ?></button>
			<button class="button" name="bk[preset]" value="db"><?php esc_html_e( 'Database', 'semrs-pro' ); ?></button>
			<button class="button" name="bk[preset]" value="plugins"><?php esc_html_e( 'Plugins', 'semrs-pro' ); ?></button>
			<button class="button" name="bk[preset]" value="themes"><?php esc_html_e( 'Themes', 'semrs-pro' ); ?></button>
			<button class="button" name="bk[preset]" value="uploads"><?php esc_html_e( 'Media', 'semrs-pro' ); ?></button>
		</p>
	</form>
	<details><summary style="cursor:pointer"><?php esc_html_e( 'Or choose exactly what to include', 'semrs-pro' ); ?></summary>
	<?php semrs_bk_form( 'backup' ); ?>
		<p><?php foreach ( $labels as $k => $l ) : ?><label style="margin-right:14px"><input type="checkbox" name="bk[parts][<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( '1', $set[ $k ] ); ?>> <?php echo esc_html( $l ); ?></label><?php endforeach; ?></p>
		<?php /* translators: %s: destinations */ ?>
		<p class="description"><?php echo esc_html( sprintf( __( 'Saved on this server%s.', 'semrs-pro' ), $set['dests'] ? ' ' . __( 'and copied to', 'semrs-pro' ) . ' ' . implode( ', ', array_map( 'semrs_bk_dest_label', (array) $set['dests'] ) ) : '' ) ); ?></p>
		<?php submit_button( __( 'Back up the chosen parts', 'semrs-pro' ), 'secondary', 'submit', false ); ?>
	</form>
	</details>

	<h2 style="margin-top:28px"><?php esc_html_e( 'Your backups', 'semrs-pro' ); ?></h2>
	<?php if ( ! $sets ) : ?>
		<p><?php esc_html_e( 'No backups yet.', 'semrs-pro' ); ?></p>
	<?php else : ?>
	<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Date', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Contents', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Size', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Stored', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Actions', 'semrs-pro' ); ?></th></tr></thead><tbody>
	<?php foreach ( $sets as $id => $h ) : ?>
		<tr>
			<td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $h['time'] ) ); ?><br><span class="description"><?php echo esc_html( array( 'manual' => __( 'manual', 'semrs-pro' ), 'schedule' => __( 'scheduled', 'semrs-pro' ), 'migrate' => __( 'migration package', 'semrs-pro' ), 'uploaded' => __( 'uploaded', 'semrs-pro' ) )[ $h['by'] ] ?? '' ); ?></span></td>
			<td><?php echo esc_html( implode( ', ', array_map( function ( $p ) use ( $labels ) { return $labels[ $p ] ?? $p; }, (array) $h['parts'] ) ) ); ?><?php echo ! empty( $h['home'] ) && untrailingslashit( $h['home'] ) !== untrailingslashit( home_url() ) ? '<br><span class="description">' . esc_html__( 'from', 'semrs-pro' ) . ' ' . esc_html( $h['home'] ) . '</span>' : ''; ?></td>
			<td><?php echo esc_html( size_format( (int) $h['size'] ) ); ?></td>
			<td><?php echo esc_html( implode( ', ', array_merge( $h['local'] ? array( __( 'This server', 'semrs-pro' ) ) : array(), array_map( 'semrs_bk_dest_label', (array) ( $h['dests'] ?? array() ) ) ) ) ); ?><?php echo ! empty( $h['warn'] ) ? '<br><span style="color:#b32d2e">' . esc_html( implode( '; ', (array) $h['warn'] ) ) . '</span>' : ''; ?></td>
			<td>
				<?php if ( $h['local'] ) : ?>
					<details><summary class="button button-small"><?php esc_html_e( 'Restore', 'semrs-pro' ); ?></summary>
						<?php semrs_bk_form( 'restore' ); ?><input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
						<p><?php foreach ( (array) $h['parts'] as $p ) : ?><label style="display:block"><input type="checkbox" name="bk[parts][<?php echo esc_attr( $p ); ?>]" value="1" checked> <?php echo esc_html( $labels[ $p ] ?? $p ); ?></label><?php endforeach; ?></p>
						<?php if ( preg_grep( '/\.enc$/', (array) $h['files'] ) ) : ?>
							<p><label><?php esc_html_e( 'Encryption password', 'semrs-pro' ); ?> <input type="password" name="bk[pass]" autocomplete="new-password" placeholder="<?php esc_attr_e( '(the saved one is used if empty)', 'semrs-pro' ); ?>"></label></p>
						<?php endif; ?>
						<?php if ( ! empty( $h['base'] ) ) : ?>
							<?php /* translators: %s: backup id */ ?>
							<p class="description"><?php echo esc_html( sprintf( __( 'Holds only changed files: the full backup %s is restored first.', 'semrs-pro' ), $h['base'] ) ); ?></p>
						<?php endif; ?>
						<p><label><input type="checkbox" name="bk[sure]" value="1"> <?php esc_html_e( 'I understand the chosen parts of this site will be replaced with this backup.', 'semrs-pro' ); ?></label></p>
						<?php submit_button( __( 'Restore', 'semrs-pro' ), 'secondary', 'submit', false ); ?></form>
					</details>
					<details><summary class="button button-small"><?php esc_html_e( 'Download', 'semrs-pro' ); ?></summary>
						<ul style="margin:6px 0">
						<?php foreach ( (array) $h['files'] as $f ) : ?>
							<?php if ( is_file( semrs_bk_dir() . $f ) ) : ?><li><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=semrs_bk_download&f=' . rawurlencode( $f ) ), 'semrs_bk_download' ) ); ?>"><?php echo esc_html( $f ); ?></a> <span class="description"><?php echo esc_html( size_format( (int) filesize( semrs_bk_dir() . $f ) ) ); ?></span></li><?php endif; ?>
						<?php endforeach; ?>
						</ul>
					</details>
				<?php else : ?>
					<?php foreach ( (array) ( $h['dests'] ?? array() ) as $d ) : ?>
						<?php semrs_bk_form( 'fetch' ); ?><input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>"><input type="hidden" name="bk[dest]" value="<?php echo esc_attr( $d ); ?>">
						<?php /* translators: %s: storage */ ?>
						<button class="button button-small"><?php echo esc_html( sprintf( __( 'Fetch from %s', 'semrs-pro' ), $dests[ $d ] ?? $d ) ); ?></button></form>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php if ( $h['local'] ) : ?>
					<?php semrs_bk_form( 'verify' ); ?><input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>"><button class="button button-small"><?php esc_html_e( 'Check', 'semrs-pro' ); ?></button></form>
				<?php endif; ?>
				<?php semrs_bk_form( 'delete' ); ?><input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>"><button class="button-link" style="color:#b32d2e" onclick="return confirm('<?php echo esc_js( __( 'Delete this backup from this server and from your storage? This cannot be undone.', 'semrs-pro' ) ); ?>')"><?php esc_html_e( 'Delete', 'semrs-pro' ); ?></button></form>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody></table>
	<?php endif; ?>

	<?php
	$set_d = (array) $set['dests'];
	if ( $set_d ) :
		?>
		<p style="margin-top:10px"><?php esc_html_e( 'Backups made elsewhere (for example before this server was rebuilt)?', 'semrs-pro' ); ?>
		<?php foreach ( $set_d as $d ) : ?>
			<?php semrs_bk_form( 'rescan' ); ?><input type="hidden" name="bk[dest]" value="<?php echo esc_attr( $d ); ?>"><?php /* translators: %s: storage */ ?><button class="button button-small" style="margin:2px"><?php echo esc_html( sprintf( __( 'Find backups in %s', 'semrs-pro' ), semrs_bk_dest_label( $d ) ) ); ?></button></form>
		<?php endforeach; ?>
		</p>
	<?php endif; ?>

	<?php $rb = (array) get_option( 'semrs_bk_rollbacks', array() ); ?>
	<h2 style="margin-top:28px"><?php esc_html_e( 'Roll back a plugin or theme', 'semrs-pro' ); ?></h2>
	<p><?php esc_html_e( 'Before WordPress updates a plugin or theme, SEMRS keeps a copy of the old version (the newest 3 per plugin or theme). If an update breaks something, roll back in one click.', 'semrs-pro' ); ?></p>
	<?php if ( ! $rb ) : ?>
		<p class="description"><?php esc_html_e( 'No copies yet: they appear after the next update.', 'semrs-pro' ); ?></p>
	<?php else : ?>
		<?php uasort( $rb, function ( $a, $b ) { return $b['time'] <=> $a['time']; } ); ?>
		<table class="widefat striped" style="max-width:820px"><thead><tr><th><?php esc_html_e( 'Plugin or theme', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Version kept', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Saved', 'semrs-pro' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( array_slice( $rb, 0, 40, true ) as $file => $r ) : ?>
			<tr><td><?php echo esc_html( $r['slug'] . ' (' . ( 'plugin' === $r['type'] ? __( 'plugin', 'semrs-pro' ) : __( 'theme', 'semrs-pro' ) ) . ')' ); ?></td><td><?php echo esc_html( $r['ver'] ); ?></td><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $r['time'] ) ); ?><?php echo 'before-rollback' === $r['why'] ? ' <span class="description">(' . esc_html__( 'before a rollback', 'semrs-pro' ) . ')</span>' : ''; ?></td>
			<td><?php semrs_bk_form( 'rollback' ); ?><input type="hidden" name="bk[file]" value="<?php echo esc_attr( $file ); ?>"><?php /* translators: %s: version */ ?><button class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Replace the current version with this copy?', 'semrs-pro' ) ); ?>')"><?php echo esc_html( sprintf( __( 'Roll back to %s', 'semrs-pro' ), $r['ver'] ) ); ?></button></form></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php endif; ?>

	<?php $imports = semrs_bk_imports(); ?>
	<?php if ( $imports ) : ?>
		<h2 style="margin-top:28px"><?php esc_html_e( 'Backups from other plugins', 'semrs-pro' ); ?></h2>
		<table class="widefat striped" style="max-width:820px"><tbody>
		<?php foreach ( $imports as $k => $it ) : ?>
			<tr><td><?php echo esc_html( $it[1] ); ?><br><span class="description"><?php echo esc_html( count( $it[2] ) . ' ' . __( 'files', 'semrs-pro' ) . ', ' . size_format( (int) $it[3] ) ); ?></span></td>
			<td><?php semrs_bk_form( 'import' ); ?><input type="hidden" name="bk[key]" value="<?php echo esc_attr( $k ); ?>"><label><input type="checkbox" name="bk[sure]" value="1"> <?php esc_html_e( 'Replace this site with it', 'semrs-pro' ); ?></label> <button class="button button-small"><?php esc_html_e( 'Restore', 'semrs-pro' ); ?></button></form></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php endif; ?>

	<h2 style="margin-top:28px"><?php esc_html_e( 'Upload backup files', 'semrs-pro' ); ?></h2>
	<p><?php esc_html_e( 'Restore a backup you downloaded before (for example on a fresh WordPress): choose all its files, including the one ending in -manifest.json. Backups from UpdraftPlus (all files of one backup) and All-in-One WP Migration (.wpress) work too. Any size works; files are sent in small pieces.', 'semrs-pro' ); ?></p>
	<p><input type="file" id="semrs-bk-files" multiple accept=".zip,.json,.enc,.wpress,.gz"> <button type="button" class="button" id="semrs-bk-up"><?php esc_html_e( 'Upload', 'semrs-pro' ); ?></button> <span id="semrs-bk-up-msg"></span></p>
	<script>
	(function(){var b=document.getElementById('semrs-bk-up'),inp=document.getElementById('semrs-bk-files'),m=document.getElementById('semrs-bk-up-msg'),CH=4*1024*1024;
	b.addEventListener('click',async function(){var fs=[...inp.files];if(!fs.length)return;b.disabled=true;
	for(const f of fs){for(let o=0;o<f.size||o===0;o+=CH){var d=new FormData();d.append('action','semrs_bk_upload');d.append('nonce','<?php echo esc_js( wp_create_nonce( 'semrs_bk_upload' ) ); ?>');d.append('name',f.name);d.append('offset',o);d.append('chunk',f.slice(o,o+CH),'chunk');
	m.textContent=f.name+' '+Math.min(100,Math.round(100*(o+CH)/Math.max(1,f.size)))+'%';
	var r=await fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:d}).then(function(x){return x.json()}).catch(function(){return {success:false,data:{message:'Upload failed'}}});
	if(!r.success){m.textContent=(r.data&&r.data.message)||'Upload failed';b.disabled=false;return}if(f.size===0)break}}
	m.textContent='<?php echo esc_js( __( 'Uploaded. Reloading…', 'semrs-pro' ) ); ?>';location.reload()})})();
	</script>
	<?php
}

/**
 * Schedule & contents tab.
 */
function semrs_bk_tab_settings() {
	$s = semrs_bk_settings();
	semrs_bk_form( 'settings' );
	?>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><?php esc_html_e( 'Automatic backups', 'semrs-pro' ); ?></th><td>
			<select name="bk[schedule]">
				<?php foreach ( array( 'off' => __( 'Off', 'semrs-pro' ), 'twicedaily' => __( 'Twice a day', 'semrs-pro' ), 'daily' => __( 'Every day', 'semrs-pro' ), 'weekly' => __( 'Every week', 'semrs-pro' ), 'monthly' => __( 'Every month', 'semrs-pro' ) ) as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['schedule'], $k ); ?>><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php esc_html_e( 'around', 'semrs-pro' ); ?> <input type="number" min="0" max="23" name="bk[hour]" value="<?php echo esc_attr( $s['hour'] ); ?>" style="width:64px">:00
			<p class="description"><?php esc_html_e( 'Pick a quiet hour. Backups run in the background in short steps and do not slow your visitors down.', 'semrs-pro' ); ?></p>
		</td></tr>
		<tr><th scope="row"><?php esc_html_e( 'What to back up', 'semrs-pro' ); ?></th><td>
			<?php foreach ( semrs_bk_part_labels() as $k => $l ) : ?><label style="display:block"><input type="checkbox" name="bk[<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( '1', $s[ $k ] ); ?>> <?php echo esc_html( $l ); ?></label><?php endforeach; ?>
		</td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Keep', 'semrs-pro' ); ?></th><td><input type="number" min="1" max="100" name="bk[keep]" value="<?php echo esc_attr( $s['keep'] ); ?>" style="width:70px"> <?php esc_html_e( 'newest backups (older ones are removed here and from your storage)', 'semrs-pro' ); ?></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Split into parts of', 'semrs-pro' ); ?></th><td><input type="number" min="10" max="2000" name="bk[split]" value="<?php echo esc_attr( $s['split'] ); ?>" style="width:80px"> MB <p class="description"><?php esc_html_e( 'Smaller parts upload more reliably on shared hosting. For email storage use 20 MB or less.', 'semrs-pro' ); ?></p></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Leave out files larger than', 'semrs-pro' ); ?></th><td><input type="number" min="0" max="100000" name="bk[maxfile]" value="<?php echo esc_attr( $s['maxfile'] ); ?>" style="width:90px"> MB <span class="description"><?php esc_html_e( '(0 = keep every file)', 'semrs-pro' ); ?></span></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Leave out folders and files', 'semrs-pro' ); ?></th><td><textarea name="bk[exclude]" rows="4" class="large-text code" placeholder="uploads/videos&#10;plugins/huge-plugin&#10;*.log"><?php echo esc_textarea( $s['exclude'] ); ?></textarea><p class="description"><?php esc_html_e( 'One per line, relative to wp-content. Caches and other plugins’ backup folders are always left out.', 'semrs-pro' ); ?></p></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Faster backups', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="bk[diff]" value="1" <?php checked( '1', $s['diff'] ); ?>> <?php esc_html_e( 'Between full backups, save only files that changed (the database is always saved in full)', 'semrs-pro' ); ?></label><br><?php esc_html_e( 'Full backup every', 'semrs-pro' ); ?> <input type="number" min="1" max="90" name="bk[full_every]" value="<?php echo esc_attr( $s['full_every'] ); ?>" style="width:64px"> <?php esc_html_e( 'days', 'semrs-pro' ); ?></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Encryption', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="bk[encrypt]" value="1" <?php checked( '1', $s['encrypt'] ); ?>> <?php esc_html_e( 'Encrypt database backups (AES-256)', 'semrs-pro' ); ?></label><br><input type="password" name="bk[pass]" autocomplete="new-password" class="regular-text" placeholder="<?php echo esc_attr( '' !== (string) $s['pass'] ? __( '(saved, leave empty to keep)', 'semrs-pro' ) : __( 'Encryption password', 'semrs-pro' ) ); ?>"><p class="description"><?php esc_html_e( 'Write the password down somewhere safe: without it an encrypted backup cannot be restored on another site.', 'semrs-pro' ); ?></p></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Rollback copies', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="bk[preupdate]" value="1" <?php checked( '1', $s['preupdate'] ); ?>> <?php esc_html_e( 'Keep a copy of each plugin and theme before WordPress updates it (one-click roll back)', 'semrs-pro' ); ?></label></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Largest migration', 'semrs-pro' ); ?></th><td><input type="number" min="100" max="1048576" name="bk[migrate_max]" value="<?php echo esc_attr( $s['migrate_max'] ); ?>" style="width:110px"> MB <span class="description"><?php esc_html_e( '(10240 MB = 10 GB). Bigger sites stop early with a clear message instead of filling the disk.', 'semrs-pro' ); ?></span></td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Leave out database tables', 'semrs-pro' ); ?></th><td>
			<details><summary style="cursor:pointer"><?php esc_html_e( 'Choose tables (for example large log or statistics tables)', 'semrs-pro' ); ?></summary>
			<?php
			global $wpdb;
			$tbls = $wpdb->get_results( $wpdb->prepare( 'SELECT table_name AS t, (data_length + index_length) AS s FROM information_schema.tables WHERE table_schema = %s AND table_name LIKE %s ORDER BY s DESC', DB_NAME, $wpdb->esc_like( $wpdb->prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			foreach ( (array) $tbls as $tb ) {
				if ( in_array( $tb->t, array( $wpdb->options, $wpdb->users, $wpdb->usermeta ), true ) ) {
					continue;
				}
				printf( '<label style="display:block"><input type="checkbox" name="bk[skip_tables][%1$s]" value="1" %2$s> %1$s <span class="description">%3$s</span></label>', esc_attr( $tb->t ), checked( in_array( $tb->t, (array) $s['skip_tables'], true ), true, false ), esc_html( size_format( (int) $tb->s ) ) );
			}
			?>
			</details>
		</td></tr>
		<tr><th scope="row"><?php esc_html_e( 'Email me', 'semrs-pro' ); ?></th><td><select name="bk[notify]"><option value="fail" <?php selected( $s['notify'], 'fail' ); ?>><?php esc_html_e( 'Only when something goes wrong', 'semrs-pro' ); ?></option><option value="always" <?php selected( $s['notify'], 'always' ); ?>><?php esc_html_e( 'After every backup', 'semrs-pro' ); ?></option><option value="never" <?php selected( $s['notify'], 'never' ); ?>><?php esc_html_e( 'Never', 'semrs-pro' ); ?></option></select></td></tr>
		<?php if ( semrs_bk_other_plugin() ) : ?>
			<tr><th scope="row"><?php esc_html_e( 'Other backup plugin', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="bk[others_plugins]" value="1" <?php checked( '1', $s['others_plugins'] ); ?>> <?php /* translators: %s: plugin */ echo esc_html( sprintf( __( 'Run the SEMRS schedule even though %s is active', 'semrs-pro' ), semrs_bk_other_plugin() ) ); ?></label></td></tr>
		<?php endif; ?>
	</table>
	<?php submit_button(); ?>
	</form>
	<?php
}

/**
 * One storage form.
 *
 * @param string $d      Destination.
 * @param array  $fields name => [label, type, placeholder, help].
 * @param string $extra  Extra HTML (already escaped).
 */
function semrs_bk_dest_form( $d, $fields, $extra = '' ) {
	$o  = semrs_bk_dest_opts( $d );
	$on = in_array( $d, (array) semrs_bk_settings()['dests'], true );
	?>
	<details style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 16px;margin:10px 0;max-width:820px" <?php echo $on ? 'open' : ''; ?>>
		<summary style="cursor:pointer;font-weight:600"><?php echo esc_html( semrs_bk_dest_label( $d ) ); ?> <?php echo $on ? '<span style="color:#15803d">● ' . esc_html__( 'in use', 'semrs-pro' ) . '</span>' : ''; ?></summary>
		<?php semrs_bk_form( 'storage' ); ?><input type="hidden" name="bk[dest]" value="<?php echo esc_attr( $d ); ?>">
		<table class="form-table" role="presentation">
			<tr><th scope="row"><?php esc_html_e( 'Use', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="bk[on]" value="1" <?php checked( $on ); ?>> <?php esc_html_e( 'Copy every backup here', 'semrs-pro' ); ?></label></td></tr>
			<?php foreach ( $fields as $f => $x ) : ?>
				<tr><th scope="row"><?php echo esc_html( $x[0] ); ?></th><td>
				<?php if ( 'checkbox' === $x[1] ) : ?>
					<label><input type="checkbox" name="bk[<?php echo esc_attr( $f ); ?>]" value="1" <?php checked( '1', (string) ( $o[ $f ] ?? '' ) ); ?>> <?php echo esc_html( $x[3] ?? '' ); ?></label>
				<?php else : ?>
					<input type="<?php echo esc_attr( $x[1] ); ?>" name="bk[<?php echo esc_attr( $f ); ?>]" value="<?php echo 'password' === $x[1] ? '' : esc_attr( (string) ( $o[ $f ] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( 'password' === $x[1] && ! empty( $o[ $f ] ) ? __( '(saved, leave empty to keep)', 'semrs-pro' ) : ( $x[2] ?? '' ) ); ?>" class="regular-text" autocomplete="<?php echo 'password' === $x[1] ? 'new-password' : 'off'; ?>" data-lpignore="true" data-1p-ignore>
					<?php if ( ! empty( $x[3] ) ) : ?><p class="description"><?php echo esc_html( $x[3] ); ?></p><?php endif; ?>
				<?php endif; ?>
				</td></tr>
			<?php endforeach; ?>
		</table>
		<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?>
		<p><?php submit_button( __( 'Save', 'semrs-pro' ), 'primary', 'submit', false ); ?> <button class="button" name="bk[test]" value="1"><?php esc_html_e( 'Save and test', 'semrs-pro' ); ?></button></p>
		</form>
	</details>
	<?php
}

/**
 * Storage tab.
 */
function semrs_bk_tab_storage() {
	echo '<p style="max-width:820px">' . esc_html__( 'Every backup is kept on this server. Add one or more places below to keep copies off the server too (recommended: if the server is lost, a copy elsewhere is the only way back). Files go to a folder named after this site.', 'semrs-pro' ) . '</p>';
	$folder = array( 'path' => array( __( 'Folder', 'semrs-pro' ), 'text', semrs_bk_remote_folder( array() ) ) );
	semrs_bk_dest_form(
		's3',
		array(
			'key'      => array( __( 'Access key', 'semrs-pro' ), 'text' ),
			'secret'   => array( __( 'Secret key', 'semrs-pro' ), 'password' ),
			'bucket'   => array( __( 'Bucket', 'semrs-pro' ), 'text' ),
			'region'   => array( __( 'Region', 'semrs-pro' ), 'text', 'us-east-1', __( 'For Cloudflare R2 use “auto”.', 'semrs-pro' ) ),
			'endpoint' => array( __( 'Endpoint (S3-compatible only)', 'semrs-pro' ), 'text', 's3.wasabisys.com', __( 'Leave empty for Amazon S3. Wasabi, Backblaze B2, DigitalOcean Spaces, Cloudflare R2, MinIO: enter their endpoint host.', 'semrs-pro' ) ),
		) + $folder
	);
	$dbx    = semrs_bk_dest_opts( 'dropbox' );
	$extra  = '';
	if ( ! empty( $dbx['app_key'] ) ) {
		$ver = (string) ( $dbx['pkce'] ?? '' );
		if ( strlen( $ver ) < 43 ) {
			$ver = wp_generate_password( 64, false );
			semrs_bk_dest_save( 'dropbox', array( 'pkce' => $ver ) );
		}
		$url    = add_query_arg( array( 'client_id' => $dbx['app_key'], 'response_type' => 'code', 'code_challenge' => rtrim( strtr( base64_encode( hash( 'sha256', $ver, true ) ), '+/', '-_' ), '=' ), 'code_challenge_method' => 'S256', 'token_access_type' => 'offline' ), 'https://www.dropbox.com/oauth2/authorize' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$extra  = '<p>' . ( ! empty( $dbx['refresh'] ) ? '<strong style="color:#15803d">' . esc_html__( 'Connected.', 'semrs-pro' ) . '</strong> ' : '' ) . '<a class="button" target="_blank" rel="noopener" href="' . esc_url( $url ) . '">' . esc_html__( 'Connect to Dropbox', 'semrs-pro' ) . '</a> ' . esc_html__( 'then paste the code Dropbox shows:', 'semrs-pro' ) . ' <input type="text" name="bk[code]" class="regular-text" autocomplete="off"></p>';
	}
	semrs_bk_dest_form( 'dropbox', array( 'app_key' => array( __( 'App key', 'semrs-pro' ), 'text', '', __( 'Create an app at dropbox.com/developers (Scoped access, App folder), allow files.content.write and files.content.read, and paste its App key. Save, then connect.', 'semrs-pro' ) ) ) + $folder, $extra );
	$gd     = semrs_bk_dest_opts( 'gdrive' );
	$extra  = '<p class="description">' . esc_html__( 'In Google Cloud Console create an OAuth client (Web application), enable the Google Drive API, and add this exact redirect address:', 'semrs-pro' ) . ' <code>' . esc_html( admin_url( 'admin.php?page=semrs-backups' ) ) . '</code></p>';
	if ( ! empty( $gd['client_id'] ) ) {
		$url    = add_query_arg( array( 'client_id' => $gd['client_id'], 'redirect_uri' => admin_url( 'admin.php?page=semrs-backups' ), 'response_type' => 'code', 'scope' => 'https://www.googleapis.com/auth/drive.file', 'access_type' => 'offline', 'prompt' => 'consent', 'state' => wp_create_nonce( 'semrs_bk_gdrive' ) ), 'https://accounts.google.com/o/oauth2/v2/auth' );
		$extra .= '<p>' . ( ! empty( $gd['refresh'] ) ? '<strong style="color:#15803d">' . esc_html__( 'Connected.', 'semrs-pro' ) . '</strong> ' : '' ) . '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Connect to Google Drive', 'semrs-pro' ) . '</a></p>';
	}
	semrs_bk_dest_form( 'gdrive', array( 'client_id' => array( __( 'Client ID', 'semrs-pro' ), 'text' ), 'client_secret' => array( __( 'Client secret', 'semrs-pro' ), 'password' ) ) + $folder, $extra );
	semrs_bk_dest_form(
		'ftp',
		array(
			'host'   => array( __( 'Host', 'semrs-pro' ), 'text', 'ftp.example.com' ),
			'port'   => array( __( 'Port', 'semrs-pro' ), 'number', '21' ),
			'user'   => array( __( 'Username', 'semrs-pro' ), 'text' ),
			'pass'   => array( __( 'Password', 'semrs-pro' ), 'password' ),
			'ssl'    => array( __( 'Encryption', 'semrs-pro' ), 'checkbox', '', __( 'Use FTPS (FTP over TLS), recommended', 'semrs-pro' ) ),
			'active' => array( __( 'Active mode', 'semrs-pro' ), 'checkbox', '', __( 'Only if passive mode does not work', 'semrs-pro' ) ),
		) + $folder
	);
	semrs_bk_dest_form(
		'sftp',
		array(
			'host' => array( __( 'Host', 'semrs-pro' ), 'text', 'sftp.example.com' ),
			'port' => array( __( 'Port', 'semrs-pro' ), 'number', '22' ),
			'user' => array( __( 'Username', 'semrs-pro' ), 'text' ),
			'pass' => array( __( 'Password', 'semrs-pro' ), 'password', '', function_exists( 'ssh2_connect' ) ? '' : __( 'This server lacks the PHP ssh2 extension, so SFTP will not work here. Use FTPS, S3, Dropbox or Google Drive.', 'semrs-pro' ) ),
		) + $folder
	);
	semrs_bk_dest_form(
		'webdav',
		array(
			'url'  => array( __( 'WebDAV address', 'semrs-pro' ), 'url', 'https://cloud.example.com/remote.php/dav/files/me/' ),
			'user' => array( __( 'Username', 'semrs-pro' ), 'text' ),
			'pass' => array( __( 'Password or app password', 'semrs-pro' ), 'password' ),
		) + $folder
	);
	semrs_bk_dest_form(
		'email',
		array(
			'to'  => array( __( 'Send to', 'semrs-pro' ), 'email', get_option( 'admin_email' ) ),
			'max' => array( __( 'Largest attachment (MB)', 'semrs-pro' ), 'number', '20', __( 'Parts bigger than this are not emailed. Best for small sites only.', 'semrs-pro' ) ),
		)
	);
}

/**
 * Migrate tab.
 */
function semrs_bk_tab_migrate() {
	$s = semrs_bk_settings();
	?>
	<div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:16px">
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 18px;flex:1 1 360px">
			<h2 style="margin-top:0"><?php esc_html_e( '1. On the OLD site: make a package', 'semrs-pro' ); ?></h2>
			<p><?php esc_html_e( 'Packs the whole site (database, plugins, themes, media and other files) and gives you a one-day migration key.', 'semrs-pro' ); ?></p>
			<?php if ( $s['maxfile'] || $s['exclude'] ) : ?>
				<?php /* translators: %s: link */ ?>
				<p class="description"><?php esc_html_e( 'Your size limits from “Schedule & contents” apply: large files and the folders you left out are not moved.', 'semrs-pro' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Moving a very large site? Set “Leave out files larger than” and “Leave out folders” under Schedule & contents first.', 'semrs-pro' ); ?></p>
			<?php endif; ?>
			<?php semrs_bk_form( 'migrate_make' ); ?><?php submit_button( __( 'Make a migration package', 'semrs-pro' ), 'primary', 'submit', false ); ?></form>
		</div>
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 18px;flex:1 1 360px">
			<h2 style="margin-top:0"><?php esc_html_e( '2. On the NEW site: paste the key', 'semrs-pro' ); ?></h2>
			<p><?php esc_html_e( 'The new site copies the package straight from the old one, restores it and changes every address to this one, also inside page builder data.', 'semrs-pro' ); ?></p>
			<?php semrs_bk_form( 'migrate_pull' ); ?>
				<textarea name="bk[key]" rows="3" class="large-text code" placeholder="<?php esc_attr_e( 'Migration key', 'semrs-pro' ); ?>"></textarea>
				<p><label><input type="checkbox" name="bk[sure]" value="1"> <?php esc_html_e( 'I understand everything on THIS site will be replaced by the old site.', 'semrs-pro' ); ?></label></p>
				<?php submit_button( __( 'Copy the site here', 'semrs-pro' ), 'secondary', 'submit', false ); ?>
			</form>
			<p class="description"><?php esc_html_e( 'Afterwards, log in with the username and password from the old site.', 'semrs-pro' ); ?></p>
		</div>
	</div>
	<?php
}
