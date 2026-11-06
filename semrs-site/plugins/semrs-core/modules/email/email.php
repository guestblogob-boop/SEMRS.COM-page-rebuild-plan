<?php
/**
 * Email (SMTP): send WordPress mail through any SMTP server (your host,
 * Gmail / Google Workspace, Microsoft 365, Zoho, Brevo, Mailgun, SendGrid,
 * Amazon SES…), so contact-form and account emails arrive instead of
 * landing in spam. Includes a test email and a log of the last 50 sends
 * (recipient, subject, result; never the message body).
 *
 * Off until the site owner fills in a server (SEMRS → Email).
 * The password can live in wp-config.php: define( 'SEMRS_SMTP_PASSWORD', '…' );
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_email_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_email', array() ),
		array(
			'on'         => '',
			'host'       => '',
			'port'       => 587,
			'secure'     => 'tls',
			'auth'       => '1',
			'user'       => '',
			'pass'       => '',
			'from'       => '',
			'from_name'  => '',
			'force_from' => '1',
			'log'        => '1',
		)
	);
}

/**
 * The SMTP password (wp-config.php constant first).
 *
 * @return string
 */
function semrs_email_password() {
	if ( defined( 'SEMRS_SMTP_PASSWORD' ) ) {
		return (string) SEMRS_SMTP_PASSWORD;
	}
	$stored = (string) semrs_email_settings()['pass'];
	if ( '' === $stored ) {
		return '';
	}
	$raw = base64_decode( $stored, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- light obfuscation, not encryption.
	if ( false === $raw || strlen( $raw ) < 17 || ! function_exists( 'openssl_decrypt' ) ) {
		return $stored;
	}
	$plain = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', hash( 'sha256', wp_salt( 'secure_auth' ), true ), OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
	return false === $plain ? '' : $plain;
}

/**
 * Encrypt the password with the site's secret keys before saving.
 *
 * @param string $plain Password.
 * @return string
 */
function semrs_email_encrypt( $plain ) {
	if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) {
		return $plain;
	}
	$iv = random_bytes( 16 );
	return base64_encode( $iv . openssl_encrypt( $plain, 'aes-256-cbc', hash( 'sha256', wp_salt( 'secure_auth' ), true ), OPENSSL_RAW_DATA, $iv ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/**
 * Configure PHPMailer.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mail Mailer.
 */
function semrs_email_phpmailer( $mail ) {
	$s = semrs_email_settings();
	if ( '1' !== $s['on'] || '' === $s['host'] ) {
		return;
	}
	$mail->isSMTP();
	$mail->Host       = $s['host']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	$mail->Port       = (int) $s['port']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	$mail->SMTPSecure = in_array( $s['secure'], array( 'ssl', 'tls' ), true ) ? $s['secure'] : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	$mail->SMTPAutoTLS = 'none' !== $s['secure']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	$mail->SMTPAuth   = '1' === $s['auth']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	if ( '1' === $s['auth'] ) {
		$mail->Username = $s['user']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$mail->Password = semrs_email_password(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	}
	$mail->Timeout = 15; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
}
add_action( 'phpmailer_init', 'semrs_email_phpmailer', 99 );

/**
 * From address and name (keeps a plugin's own From unless "force" is on).
 *
 * @param string $from Address.
 * @return string
 */
function semrs_email_from( $from ) {
	$s = semrs_email_settings();
	if ( '1' !== $s['on'] || ! is_email( $s['from'] ) ) {
		return $from;
	}
	return '1' === $s['force_from'] || 0 === strpos( $from, 'wordpress@' ) ? $s['from'] : $from;
}
add_filter( 'wp_mail_from', 'semrs_email_from', 99 );

/**
 * From name.
 *
 * @param string $name Name.
 * @return string
 */
function semrs_email_from_name( $name ) {
	$s = semrs_email_settings();
	if ( '1' !== $s['on'] || '' === $s['from_name'] ) {
		return $name;
	}
	return '1' === $s['force_from'] || 'WordPress' === $name ? $s['from_name'] : $name;
}
add_filter( 'wp_mail_from_name', 'semrs_email_from_name', 99 );

/**
 * Add a line to the mail log.
 *
 * @param array  $mail   wp_mail data.
 * @param string $result ok or the error.
 */
function semrs_email_log_add( $mail, $result ) {
	if ( '1' !== semrs_email_settings()['log'] ) {
		return;
	}
	$to  = is_array( $mail['to'] ?? '' ) ? implode( ', ', $mail['to'] ) : (string) ( $mail['to'] ?? '' );
	$log = (array) get_option( 'semrs_email_log', array() );
	array_unshift( $log, array( time(), substr( sanitize_text_field( $to ), 0, 190 ), substr( sanitize_text_field( (string) ( $mail['subject'] ?? '' ) ), 0, 190 ), substr( sanitize_text_field( $result ), 0, 300 ) ) );
	update_option( 'semrs_email_log', array_slice( $log, 0, 50 ), false );
}
add_action( 'wp_mail_succeeded', function ( $mail ) { semrs_email_log_add( $mail, 'ok' ); } );
add_action(
	'wp_mail_failed',
	function ( $err ) {
		$data = $err instanceof WP_Error ? (array) $err->get_error_data() : array();
		semrs_email_log_add( $data, $err instanceof WP_Error ? $err->get_error_message() : 'failed' );
	}
);

if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs', __( 'Email (SMTP)', 'semrs-core' ), __( 'Email', 'semrs-core' ), 'manage_options', 'semrs-email', 'semrs_email_page' );
		},
		31
	);
	add_action(
		'admin_init',
		function () {
			register_setting( 'semrs_email', 'semrs_email', array( 'type' => 'array', 'sanitize_callback' => 'semrs_email_sanitize' ) );
		}
	);
	add_action( 'admin_post_semrs_email_test', 'semrs_email_send_test' );
}

/**
 * Sanitize settings.
 *
 * @param array $in Input.
 * @return array
 */
function semrs_email_sanitize( $in ) {
	$in  = (array) $in;
	$old = semrs_email_settings();
	$pw  = (string) ( $in['pass'] ?? '' );
	return array(
		'on'         => empty( $in['on'] ) ? '' : '1',
		'host'       => preg_replace( '/[^a-zA-Z0-9.\-]/', '', (string) ( $in['host'] ?? '' ) ),
		'port'       => max( 1, min( 65535, absint( $in['port'] ?? 587 ) ) ),
		'secure'     => in_array( $in['secure'] ?? '', array( 'tls', 'ssl', 'none' ), true ) ? $in['secure'] : 'tls',
		'auth'       => empty( $in['auth'] ) ? '' : '1',
		'user'       => sanitize_text_field( $in['user'] ?? '' ),
		// Empty field keeps the saved password.
		'pass'       => '' === $pw ? $old['pass'] : semrs_email_encrypt( wp_unslash( $pw ) ),
		'from'       => sanitize_email( $in['from'] ?? '' ),
		'from_name'  => sanitize_text_field( $in['from_name'] ?? '' ),
		'force_from' => empty( $in['force_from'] ) ? '' : '1',
		'log'        => empty( $in['log'] ) ? '' : '1',
	);
}

/**
 * Send a test email.
 */
function semrs_email_send_test() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_email_test' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-core' ) );
	}
	$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
	$ok = is_email( $to ) && wp_mail( $to, __( 'SEMRS test email', 'semrs-core' ), __( 'This test email was sent from your WordPress site through SEMRS Email. If you can read it, sending works.', 'semrs-core' ) );
	wp_safe_redirect( add_query_arg( 'semrs_test', $ok ? 'ok' : 'fail', admin_url( 'admin.php?page=semrs-email' ) ) );
	exit;
}

/**
 * Settings page.
 */
function semrs_email_page() {
	$s   = semrs_email_settings();
	$n   = 'semrs_email';
	$log = (array) get_option( 'semrs_email_log', array() );
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Email (SMTP)', 'semrs-core' ); ?></h1>
		<p><?php esc_html_e( 'Many web hosts send WordPress mail without proper checks, so messages fail or land in spam. Send through your email provider\'s SMTP server instead. Use the SMTP details your provider gives you; for Gmail and Microsoft 365 create an app password.', 'semrs-core' ); ?></p>
		<?php if ( isset( $_GET['semrs_test'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-<?php echo 'ok' === $_GET['semrs_test'] ? 'success' : 'error'; // phpcs:ignore WordPress.Security.NonceVerification ?> is-dismissible"><p><?php echo 'ok' === $_GET['semrs_test'] ? esc_html__( 'Test email sent. Check the inbox (and the spam folder).', 'semrs-core' ) : esc_html__( 'Sending failed. See the error in the log below.', 'semrs-core' ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
		<?php endif; ?>
		<form method="post" action="options.php" autocomplete="off">
			<?php settings_fields( 'semrs_email' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Use SMTP', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[on]" value="1" <?php checked( $s['on'], '1' ); ?>> <?php esc_html_e( 'Send all WordPress email through this server', 'semrs-core' ); ?></label></td></tr>
				<tr><th><label for="semrs-smtp-host"><?php esc_html_e( 'SMTP server', 'semrs-core' ); ?></label></th><td><input id="semrs-smtp-host" type="text" class="regular-text" name="<?php echo esc_attr( $n ); ?>[host]" value="<?php echo esc_attr( $s['host'] ); ?>" placeholder="smtp.example.com"></td></tr>
				<tr><th><?php esc_html_e( 'Encryption and port', 'semrs-core' ); ?></th><td><select name="<?php echo esc_attr( $n ); ?>[secure]"><option value="tls" <?php selected( $s['secure'], 'tls' ); ?>>TLS (587)</option><option value="ssl" <?php selected( $s['secure'], 'ssl' ); ?>>SSL (465)</option><option value="none" <?php selected( $s['secure'], 'none' ); ?>><?php esc_html_e( 'None (not recommended)', 'semrs-core' ); ?></option></select> <input type="number" min="1" max="65535" name="<?php echo esc_attr( $n ); ?>[port]" value="<?php echo esc_attr( $s['port'] ); ?>" style="width:90px"></td></tr>
				<tr><th><?php esc_html_e( 'Log in to the server', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[auth]" value="1" <?php checked( $s['auth'], '1' ); ?>> <?php esc_html_e( 'Use a username and password', 'semrs-core' ); ?></label></td></tr>
				<tr><th><label for="semrs-smtp-user"><?php esc_html_e( 'Username', 'semrs-core' ); ?></label></th><td><input id="semrs-smtp-user" type="text" class="regular-text" name="<?php echo esc_attr( $n ); ?>[user]" value="<?php echo esc_attr( $s['user'] ); ?>" autocomplete="off"></td></tr>
				<tr><th><label for="semrs-smtp-pass"><?php esc_html_e( 'Password', 'semrs-core' ); ?></label></th><td>
					<?php if ( defined( 'SEMRS_SMTP_PASSWORD' ) ) : ?>
						<em><?php esc_html_e( 'Set in wp-config.php (SEMRS_SMTP_PASSWORD).', 'semrs-core' ); ?></em>
					<?php else : ?>
						<input id="semrs-smtp-pass" type="password" class="regular-text" name="<?php echo esc_attr( $n ); ?>[pass]" value="" autocomplete="new-password" placeholder="<?php echo $s['pass'] ? esc_attr__( 'Saved – leave empty to keep it', 'semrs-core' ) : ''; ?>">
						<p class="description"><?php esc_html_e( 'Saved encrypted with your site\'s secret keys. Safer still: add define( \'SEMRS_SMTP_PASSWORD\', \'…\' ); to wp-config.php.', 'semrs-core' ); ?></p>
					<?php endif; ?>
				</td></tr>
				<tr><th><label for="semrs-smtp-from"><?php esc_html_e( 'From email', 'semrs-core' ); ?></label></th><td><input id="semrs-smtp-from" type="email" class="regular-text" name="<?php echo esc_attr( $n ); ?>[from]" value="<?php echo esc_attr( $s['from'] ); ?>"><p class="description"><?php esc_html_e( 'Use an address on your own domain that your provider allows, so SPF and DKIM checks pass.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><label for="semrs-smtp-name"><?php esc_html_e( 'From name', 'semrs-core' ); ?></label></th><td><input id="semrs-smtp-name" type="text" class="regular-text" name="<?php echo esc_attr( $n ); ?>[from_name]" value="<?php echo esc_attr( $s['from_name'] ); ?>"> <label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[force_from]" value="1" <?php checked( $s['force_from'], '1' ); ?>> <?php esc_html_e( 'Use for all emails, even when a plugin sets its own', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Mail log', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[log]" value="1" <?php checked( $s['log'], '1' ); ?>> <?php esc_html_e( 'Keep the last 50 sends (recipient, subject, result; never the message)', 'semrs-core' ); ?></label></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Send a test email', 'semrs-core' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="semrs_email_test"><?php wp_nonce_field( 'semrs_email_test' ); ?>
			<input type="email" name="to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required> <?php submit_button( __( 'Send test', 'semrs-core' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php if ( $log ) : ?>
			<h2><?php esc_html_e( 'Recent emails', 'semrs-core' ); ?></h2>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'semrs-core' ); ?></th><th><?php esc_html_e( 'To', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Subject', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Result', 'semrs-core' ); ?></th></tr></thead><tbody>
			<?php foreach ( $log as $l ) : ?>
				<tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $l[0] ) ); ?></td><td><?php echo esc_html( $l[1] ); ?></td><td><?php echo esc_html( $l[2] ); ?></td><td><?php echo 'ok' === $l[3] ? '<span style="color:#16a34a">✓ ' . esc_html__( 'Sent', 'semrs-core' ) . '</span>' : '<span style="color:#dc2626">' . esc_html( $l[3] ) . '</span>'; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
	</div>
	<?php
}
