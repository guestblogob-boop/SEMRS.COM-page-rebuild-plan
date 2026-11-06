<?php
/**
 * Login security (Pro): two-factor log-in with any authenticator app
 * (TOTP, RFC 6238) plus one-time backup codes, a reminder for administrators
 * who have not set it up, and an optional custom log-in address.
 *
 * Each person turns two-factor on in their own profile, so nobody is locked
 * out by surprise. Emergency switches for wp-config.php:
 * define( 'SEMRS_2FA_OFF', true );        – skip two-factor checks.
 * define( 'SEMRS_LOGIN_SLUG_OFF', true ); – use wp-login.php again.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_2fa_settings() {
	return wp_parse_args( (array) get_option( 'semrs_login_security', array() ), array( 'remind' => '1', 'slug' => '' ) );
}

/*
 * TOTP.
 */

/**
 * Base32 encode (RFC 4648, no padding).
 *
 * @param string $bin Bytes.
 * @return string
 */
function semrs_2fa_b32( $bin ) {
	$abc  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$bits = '';
	foreach ( str_split( $bin ) as $c ) {
		$bits .= str_pad( decbin( ord( $c ) ), 8, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 5 ) as $chunk ) {
		$out .= $abc[ bindec( str_pad( $chunk, 5, '0' ) ) ];
	}
	return $out;
}

/**
 * Base32 decode.
 *
 * @param string $s Base32.
 * @return string
 */
function semrs_2fa_b32_decode( $s ) {
	$abc  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$s    = strtoupper( preg_replace( '/[^A-Za-z2-7]/', '', $s ) );
	$bits = '';
	foreach ( str_split( $s ) as $c ) {
		$bits .= str_pad( decbin( strpos( $abc, $c ) ), 5, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 8 ) as $byte ) {
		if ( 8 === strlen( $byte ) ) {
			$out .= chr( bindec( $byte ) );
		}
	}
	return $out;
}

/**
 * The 6-digit code for a time step.
 *
 * @param string $secret Base32 secret.
 * @param int    $step   Time step.
 * @return string
 */
function semrs_2fa_code( $secret, $step ) {
	$hash = hash_hmac( 'sha1', pack( 'N*', 0 ) . pack( 'N*', $step ), semrs_2fa_b32_decode( $secret ), true );
	$off  = ord( $hash[19] ) & 0xf;
	$num  = ( ( ord( $hash[ $off ] ) & 0x7f ) << 24 ) | ( ord( $hash[ $off + 1 ] ) << 16 ) | ( ord( $hash[ $off + 2 ] ) << 8 ) | ord( $hash[ $off + 3 ] );
	return str_pad( (string) ( $num % 1000000 ), 6, '0', STR_PAD_LEFT );
}

/**
 * Check a code (±30 seconds) and stop it being used twice.
 *
 * @param int    $user_id User.
 * @param string $code    Code.
 * @return bool
 */
function semrs_2fa_verify( $user_id, $code ) {
	$code   = preg_replace( '/\D/', '', (string) $code );
	$secret = (string) get_user_meta( $user_id, '_semrs_2fa_secret', true );
	if ( 6 !== strlen( $code ) || '' === $secret ) {
		return false;
	}
	$now  = (int) floor( time() / 30 );
	$last = (int) get_user_meta( $user_id, '_semrs_2fa_last', true );
	for ( $i = -1; $i <= 1; $i++ ) {
		if ( $now + $i > $last && hash_equals( semrs_2fa_code( $secret, $now + $i ), $code ) ) {
			update_user_meta( $user_id, '_semrs_2fa_last', $now + $i );
			return true;
		}
	}
	return false;
}

/**
 * Use a one-time backup code.
 *
 * @param int    $user_id User.
 * @param string $code    Code.
 * @return bool
 */
function semrs_2fa_backup( $user_id, $code ) {
	$code  = strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $code ) );
	$codes = (array) get_user_meta( $user_id, '_semrs_2fa_backup', true );
	if ( strlen( $code ) < 8 ) {
		return false;
	}
	foreach ( $codes as $i => $hash ) {
		if ( wp_check_password( $code, $hash ) ) {
			unset( $codes[ $i ] );
			update_user_meta( $user_id, '_semrs_2fa_backup', array_values( $codes ) );
			return true;
		}
	}
	return false;
}

/**
 * Does the user have two-factor on?
 *
 * @param int $user_id User.
 * @return bool
 */
function semrs_2fa_enabled( $user_id ) {
	return '1' === get_user_meta( $user_id, '_semrs_2fa_on', true ) && '' !== (string) get_user_meta( $user_id, '_semrs_2fa_secret', true );
}

/*
 * Log-in.
 */

/**
 * Code field on the log-in form.
 */
function semrs_2fa_login_field() {
	echo '<p><label for="semrs-2fa-code">' . esc_html__( 'Authentication code (if two-factor is on)', 'semrs-pro' ) . '</label><input type="text" name="semrs_2fa_code" id="semrs-2fa-code" class="input" value="" size="20" autocomplete="one-time-code" inputmode="numeric" spellcheck="false"></p>';
}
add_action( 'login_form', 'semrs_2fa_login_field' );

/**
 * Require the code after the password is correct.
 *
 * @param WP_User|WP_Error|null $user User.
 * @return WP_User|WP_Error|null
 */
function semrs_2fa_authenticate( $user ) {
	if ( ! $user instanceof WP_User || ( defined( 'SEMRS_2FA_OFF' ) && SEMRS_2FA_OFF ) || ! semrs_2fa_enabled( $user->ID ) ) {
		return $user;
	}
	// Application passwords and XML-RPC use their own credentials.
	if ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || did_action( 'application_password_did_authenticate' ) ) {
		return $user;
	}
	$code = isset( $_POST['semrs_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['semrs_2fa_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- log-in form.
	if ( '' !== $code && ( semrs_2fa_verify( $user->ID, $code ) || semrs_2fa_backup( $user->ID, $code ) ) ) {
		return $user;
	}
	do_action( 'wp_login_failed', $user->user_login, new WP_Error( 'semrs_2fa' ) );
	return new WP_Error( 'semrs_2fa', '<strong>' . esc_html__( 'Error:', 'semrs-pro' ) . '</strong> ' . esc_html__( 'Enter the 6-digit code from your authenticator app, or a backup code.', 'semrs-pro' ) );
}
add_filter( 'authenticate', 'semrs_2fa_authenticate', 50 );

/*
 * Profile: set up, confirm, turn off, backup codes.
 */

/**
 * Profile section.
 *
 * @param WP_User $user User.
 */
function semrs_2fa_profile( $user ) {
	if ( get_current_user_id() !== $user->ID ) {
		if ( current_user_can( 'edit_users' ) && semrs_2fa_enabled( $user->ID ) ) {
			echo '<h2>' . esc_html__( 'Two-factor log-in', 'semrs-pro' ) . '</h2><p><label><input type="checkbox" name="semrs_2fa_reset" value="1"> ' . esc_html__( 'Turn off two-factor for this user (for example after a lost phone)', 'semrs-pro' ) . '</label></p>';
		}
		return;
	}
	$on     = semrs_2fa_enabled( $user->ID );
	$secret = (string) get_user_meta( $user->ID, '_semrs_2fa_pending', true );
	if ( ! $on && '' === $secret ) {
		$secret = semrs_2fa_b32( random_bytes( 20 ) );
		update_user_meta( $user->ID, '_semrs_2fa_pending', $secret );
	}
	$issuer = rawurlencode( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$uri    = 'otpauth://totp/' . $issuer . ':' . rawurlencode( $user->user_login ) . '?secret=' . $secret . '&issuer=' . $issuer . '&digits=6&period=30';
	$left   = count( (array) get_user_meta( $user->ID, '_semrs_2fa_backup', true ) );
	wp_nonce_field( 'semrs_2fa_profile', 'semrs_2fa_nonce' );
	echo '<h2 id="semrs-2fa">' . esc_html__( 'Two-factor log-in', 'semrs-pro' ) . '</h2><table class="form-table" role="presentation">';
	if ( $on ) {
		/* translators: %d: number of codes. */
		echo '<tr><th>' . esc_html__( 'Status', 'semrs-pro' ) . '</th><td><strong style="color:#16a34a">' . esc_html__( 'On', 'semrs-pro' ) . '</strong> – ' . esc_html( sprintf( _n( '%d backup code left.', '%d backup codes left.', $left, 'semrs-pro' ), $left ) ) . '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Backup codes', 'semrs-pro' ) . '</th><td><label><input type="checkbox" name="semrs_2fa_newcodes" value="1"> ' . esc_html__( 'Make 10 new backup codes (the old ones stop working)', 'semrs-pro' ) . '</label></td></tr>';
		echo '<tr><th>' . esc_html__( 'Turn off', 'semrs-pro' ) . '</th><td><label><input type="checkbox" name="semrs_2fa_off" value="1"> ' . esc_html__( 'Turn two-factor off for my account', 'semrs-pro' ) . '</label></td></tr>';
	} else {
		echo '<tr><th>' . esc_html__( '1. Add to your app', 'semrs-pro' ) . '</th><td><p>' . esc_html__( 'In Google Authenticator, Microsoft Authenticator, 1Password, Authy or a similar app, choose "Enter a setup key" and type this key (time based):', 'semrs-pro' ) . '</p><p><code style="font-size:16px;letter-spacing:2px">' . esc_html( trim( chunk_split( $secret, 4, ' ' ) ) ) . '</code></p><p><a href="' . esc_attr( $uri ) . '">' . esc_html__( 'Or open this link on the phone that has the app', 'semrs-pro' ) . '</a></p></td></tr>';
		echo '<tr><th><label for="semrs-2fa-confirm">' . esc_html__( '2. Confirm', 'semrs-pro' ) . '</label></th><td><input type="text" id="semrs-2fa-confirm" name="semrs_2fa_confirm" class="regular-text" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"><p class="description">' . esc_html__( 'Enter the 6-digit code the app shows, then click Update Profile. You will then see your backup codes once.', 'semrs-pro' ) . '</p></td></tr>';
	}
	echo '</table>';
	$codes = get_transient( 'semrs_2fa_show_' . $user->ID );
	if ( $codes ) {
		delete_transient( 'semrs_2fa_show_' . $user->ID );
		echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'Your backup codes – save them somewhere safe now. Each works once. They will not be shown again.', 'semrs-pro' ) . '</strong></p><p><code style="font-size:15px;line-height:2">' . implode( '</code> <code style="font-size:15px;line-height:2">', array_map( 'esc_html', (array) $codes ) ) . '</code></p></div>';
	}
}
add_action( 'show_user_profile', 'semrs_2fa_profile' );
add_action( 'edit_user_profile', 'semrs_2fa_profile' );

/**
 * New backup codes (plain codes are shown once, hashes are stored).
 *
 * @param int $user_id User.
 */
function semrs_2fa_new_codes( $user_id ) {
	$plain = array();
	$hash  = array();
	for ( $i = 0; $i < 10; $i++ ) {
		$c       = strtolower( wp_generate_password( 10, false, false ) );
		$plain[] = $c;
		$hash[]  = wp_hash_password( $c );
	}
	update_user_meta( $user_id, '_semrs_2fa_backup', $hash );
	set_transient( 'semrs_2fa_show_' . $user_id, $plain, 10 * MINUTE_IN_SECONDS );
}

/**
 * Save the profile section.
 *
 * @param int $user_id User.
 */
function semrs_2fa_save( $user_id ) {
	if ( get_current_user_id() !== (int) $user_id ) {
		if ( current_user_can( 'edit_users' ) && ! empty( $_POST['semrs_2fa_reset'] ) && check_admin_referer( 'update-user_' . $user_id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			delete_user_meta( $user_id, '_semrs_2fa_on' );
			delete_user_meta( $user_id, '_semrs_2fa_secret' );
			delete_user_meta( $user_id, '_semrs_2fa_backup' );
		}
		return;
	}
	if ( ! isset( $_POST['semrs_2fa_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_2fa_nonce'] ) ), 'semrs_2fa_profile' ) ) {
		return;
	}
	if ( semrs_2fa_enabled( $user_id ) ) {
		if ( ! empty( $_POST['semrs_2fa_off'] ) ) {
			delete_user_meta( $user_id, '_semrs_2fa_on' );
			delete_user_meta( $user_id, '_semrs_2fa_secret' );
			delete_user_meta( $user_id, '_semrs_2fa_backup' );
		} elseif ( ! empty( $_POST['semrs_2fa_newcodes'] ) ) {
			semrs_2fa_new_codes( $user_id );
		}
		return;
	}
	$code    = isset( $_POST['semrs_2fa_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['semrs_2fa_confirm'] ) ) : '';
	$pending = (string) get_user_meta( $user_id, '_semrs_2fa_pending', true );
	if ( '' === $code || '' === $pending ) {
		return;
	}
	update_user_meta( $user_id, '_semrs_2fa_secret', $pending );
	if ( semrs_2fa_verify( $user_id, $code ) ) {
		update_user_meta( $user_id, '_semrs_2fa_on', '1' );
		delete_user_meta( $user_id, '_semrs_2fa_pending' );
		semrs_2fa_new_codes( $user_id );
	} else {
		delete_user_meta( $user_id, '_semrs_2fa_secret' );
		add_action( 'user_profile_update_errors', function ( $errors ) { $errors->add( 'semrs_2fa', __( 'The two-factor code was not correct. Check the time on your phone and try again.', 'semrs-pro' ) ); } );
	}
}
add_action( 'personal_options_update', 'semrs_2fa_save' );
add_action( 'edit_user_profile_update', 'semrs_2fa_save' );

/**
 * Remind administrators without two-factor.
 */
function semrs_2fa_notice() {
	if ( '1' !== semrs_2fa_settings()['remind'] || ! current_user_can( 'manage_options' ) || semrs_2fa_enabled( get_current_user_id() ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && ! in_array( $screen->id, array( 'dashboard', 'toplevel_page_semrs', 'profile' ), true ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p>' . esc_html__( 'Protect your administrator account: turn on two-factor log-in.', 'semrs-pro' ) . ' <a href="' . esc_url( admin_url( 'profile.php#semrs-2fa' ) ) . '">' . esc_html__( 'Set it up (2 minutes)', 'semrs-pro' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'semrs_2fa_notice' );

/*
 * Custom log-in address.
 */

/**
 * The custom slug, or '' when off.
 *
 * @return string
 */
function semrs_login_slug() {
	if ( defined( 'SEMRS_LOGIN_SLUG_OFF' ) && SEMRS_LOGIN_SLUG_OFF ) {
		return '';
	}
	return sanitize_title( semrs_2fa_settings()['slug'] );
}

/**
 * Serve wp-login.php at the custom address; hide the default one from visitors.
 */
function semrs_login_route() {
	$slug = semrs_login_slug();
	if ( '' === $slug ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? trim( (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' ) : '';
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$rel  = trim( '' !== $home ? preg_replace( '#^' . preg_quote( $home, '#' ) . '#', '', $path ) : $path, '/' );
	if ( $rel === $slug ) {
		global $error, $interim_login, $action, $user_login, $user, $redirect_to; // phpcs:ignore -- wp-login.php expects these.
		require_once ABSPATH . 'wp-login.php';
		exit;
	}
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'wp-login.php' === basename( $path ) && ! is_user_logged_in() && ! in_array( $action, array( 'postpass', 'logout', 'confirmaction' ), true ) && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		status_header( 404 );
		nocache_headers();
		wp_die( esc_html__( 'Page not found.', 'semrs-pro' ), '', array( 'response' => 404 ) );
	}
}
add_action( 'wp_loaded', 'semrs_login_route', 1 );

/**
 * Links to wp-login.php point to the custom address.
 *
 * @param string $url URL.
 * @return string
 */
function semrs_login_url_filter( $url ) {
	$slug = semrs_login_slug();
	return '' === $slug || false === strpos( $url, 'wp-login.php' ) ? $url : str_replace( 'wp-login.php', $slug, $url );
}
add_filter( 'site_url', 'semrs_login_url_filter', 99 );
add_filter( 'network_site_url', 'semrs_login_url_filter', 99 );
add_filter( 'wp_redirect', 'semrs_login_url_filter', 99 );
add_filter( 'login_url', 'semrs_login_url_filter', 99 );
add_filter( 'logout_url', 'semrs_login_url_filter', 99 );
add_filter( 'lostpassword_url', 'semrs_login_url_filter', 99 );
add_filter( 'register_url', 'semrs_login_url_filter', 99 );

/*
 * Settings screen.
 */
if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs', __( 'Login security', 'semrs-pro' ), __( 'Login security', 'semrs-pro' ), 'manage_options', 'semrs-login-security', 'semrs_2fa_page' );
		},
		34
	);
	add_action(
		'admin_init',
		function () {
			register_setting(
				'semrs_login_security',
				'semrs_login_security',
				array(
					'type'              => 'array',
					'sanitize_callback' => function ( $in ) {
						$in   = (array) $in;
						$slug = sanitize_title( $in['slug'] ?? '' );
						if ( in_array( $slug, array( 'wp-admin', 'admin', 'login', 'wp-login', 'dashboard', 'wp-json' ), true ) || ( '' !== $slug && get_page_by_path( $slug ) ) ) {
							add_settings_error( 'semrs_login_security', 'slug', __( 'That address is already used or too easy to guess. Choose another one.', 'semrs-pro' ) );
							$slug = semrs_2fa_settings()['slug'];
						}
						return array( 'remind' => empty( $in['remind'] ) ? '' : '1', 'slug' => $slug );
					},
				)
			);
		}
	);
}

/**
 * Settings page.
 */
function semrs_2fa_page() {
	$s     = semrs_2fa_settings();
	$users = get_users( array( 'role__in' => array( 'administrator', 'editor' ), 'fields' => array( 'ID', 'display_name' ) ) );
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Login security', 'semrs-pro' ); ?></h1>
		<?php settings_errors( 'semrs_login_security' ); ?>
		<p><?php esc_html_e( 'Two-factor log-in asks for a 6-digit code from a phone app after the password, so a stolen password alone is not enough. Each person turns it on in their own profile.', 'semrs-pro' ); ?> <a class="button button-primary" href="<?php echo esc_url( admin_url( 'profile.php#semrs-2fa' ) ); ?>"><?php esc_html_e( 'Set up two-factor for my account', 'semrs-pro' ); ?></a></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_login_security' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Reminder', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="semrs_login_security[remind]" value="1" <?php checked( $s['remind'], '1' ); ?>> <?php esc_html_e( 'Remind administrators who have not turned on two-factor', 'semrs-pro' ); ?></label></td></tr>
				<tr><th><label for="semrs-login-slug"><?php esc_html_e( 'Custom log-in address', 'semrs-pro' ); ?></label></th><td><code><?php echo esc_html( trailingslashit( home_url() ) ); ?></code><input id="semrs-login-slug" type="text" name="semrs_login_security[slug]" value="<?php echo esc_attr( $s['slug'] ); ?>" placeholder="<?php esc_attr_e( 'leave empty to keep wp-login.php', 'semrs-pro' ); ?>">
					<p class="description"><?php esc_html_e( 'Bots that look for wp-login.php get a "not found" page. Bookmark the new address before you save. If you forget it, add define( \'SEMRS_LOGIN_SLUG_OFF\', true ); to wp-config.php.', 'semrs-pro' ); ?></p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Who has two-factor on', 'semrs-pro' ); ?></h2>
		<table class="widefat striped" style="max-width:520px"><tbody>
		<?php foreach ( $users as $u ) : ?>
			<tr><td><?php echo esc_html( $u->display_name ); ?></td><td><?php echo semrs_2fa_enabled( $u->ID ) ? '<span style="color:#16a34a">✓ ' . esc_html__( 'On', 'semrs-pro' ) . '</span>' : '<span style="color:#b45309">' . esc_html__( 'Off', 'semrs-pro' ) . '</span>'; ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="description"><?php esc_html_e( 'If someone loses their phone and backup codes, an administrator can turn two-factor off on that user\'s profile. If you are locked out yourself, add define( \'SEMRS_2FA_OFF\', true ); to wp-config.php.', 'semrs-pro' ); ?></p>
	</div>
	<?php
}
