<?php
/**
 * SEMRS → Security settings screen.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Security', 'semrs-core' ), __( 'Security', 'semrs-core' ), 'manage_options', 'semrs-security', 'semrs_security_page' );
	},
	32
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'semrs_security',
			'semrs_security',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $in ) {
					$in  = (array) $in;
					$out = array();
					foreach ( array( 'limit', 'headers', 'hsts', 'svg', 'enum', 'version', 'login_msg', 'editor' ) as $k ) {
						$out[ $k ] = empty( $in[ $k ] ) ? '' : '1';
					}
					$out['tries']     = max( 3, min( 20, absint( $in['tries'] ?? 5 ) ) );
					$out['minutes']   = max( 5, min( 1440, absint( $in['minutes'] ?? 15 ) ) );
					$ips              = array_filter( preg_split( '/[\s,]+/', (string) ( $in['allowlist'] ?? '' ) ), function ( $ip ) { return (bool) filter_var( $ip, FILTER_VALIDATE_IP ); } );
					$out['allowlist'] = implode( "\n", $ips );
					return $out;
				},
			)
		);
	}
);

/**
 * Settings page.
 */
function semrs_security_page() {
	$s  = semrs_security_settings();
	$n  = 'semrs_security';
	$ip = semrs_security_ip();
	$rows = array(
		'limit'     => array( __( 'Limit log-in attempts', 'semrs-core' ), __( 'Lock an IP address out after too many wrong passwords. Stops password-guessing bots.', 'semrs-core' ) ),
		'login_msg' => array( __( 'Same error for wrong user or password', 'semrs-core' ), __( 'Bots cannot find out which usernames exist.', 'semrs-core' ) ),
		'enum'      => array( __( 'Hide usernames', 'semrs-core' ), __( 'Blocks ?author=1 scans, the public users list in the REST API and the users sitemap.', 'semrs-core' ) ),
		'version'   => array( __( 'Hide the WordPress version', 'semrs-core' ), __( 'Removes the version number from the page code and feeds.', 'semrs-core' ) ),
		'headers'   => array( __( 'Security headers', 'semrs-core' ), __( 'nosniff, SAMEORIGIN framing, a strict Referrer-Policy and a Permissions-Policy that turns off camera, microphone and payment access for embedded content.', 'semrs-core' ) ),
		'hsts'      => array( __( 'HSTS (HTTPS only)', 'semrs-core' ), __( 'Browsers always use HTTPS for a year. Turn on only when every page and subdomain works on HTTPS.', 'semrs-core' ) ),
		'svg'       => array( __( 'Safe SVG uploads', 'semrs-core' ), __( 'Administrators can upload SVG images; each file is cleaned of scripts and outside links first.', 'semrs-core' ) ),
		'editor'    => array( __( 'Turn off the file editor', 'semrs-core' ), __( 'Removes Appearance → Theme File Editor and Plugins → Plugin File Editor, a common target after a stolen password.', 'semrs-core' ) ),
	);
	$locks = (array) get_option( 'semrs_security_lockouts', array() );
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Security', 'semrs-core' ); ?></h1>
		<p><?php esc_html_e( 'Light protections that do not slow your site. If you ever lock yourself out, add define( \'SEMRS_SECURITY_OFF\', true ); to wp-config.php.', 'semrs-core' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_security' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $rows as $k => $r ) : ?>
					<tr><th><?php echo esc_html( $r[0] ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n . '[' . $k . ']' ); ?>" value="1" <?php checked( $s[ $k ], '1' ); ?>> <?php echo esc_html( $r[1] ); ?></label>
					<?php if ( 'limit' === $k && semrs_security_other_login_guard() ) : ?>
						<?php /* translators: %s: plugin name, e.g. Wordfence. */ ?>
						<p class="description"><strong><?php echo esc_html( sprintf( __( 'Paused: %s already limits log-in attempts on this site, so SEMRS steps aside.', 'semrs-core' ), semrs_security_other_login_guard() ) ); ?></strong></p>
					<?php endif; ?>
					<?php if ( 'limit' === $k ) : ?>
						<p><?php esc_html_e( 'Lock after', 'semrs-core' ); ?> <input type="number" min="3" max="20" name="<?php echo esc_attr( $n ); ?>[tries]" value="<?php echo esc_attr( $s['tries'] ); ?>" style="width:70px"> <?php esc_html_e( 'failed tries, for', 'semrs-core' ); ?> <input type="number" min="5" max="1440" name="<?php echo esc_attr( $n ); ?>[minutes]" value="<?php echo esc_attr( $s['minutes'] ); ?>" style="width:80px"> <?php esc_html_e( 'minutes', 'semrs-core' ); ?></p>
						<p><label><?php esc_html_e( 'Never lock these IP addresses (one per line):', 'semrs-core' ); ?><br><textarea name="<?php echo esc_attr( $n ); ?>[allowlist]" rows="2" class="regular-text code"><?php echo esc_textarea( $s['allowlist'] ); ?></textarea></label><br><span class="description"><?php /* translators: %s: IP address. */ echo esc_html( sprintf( __( 'Your IP address now: %s', 'semrs-core' ), $ip ) ); ?></span></p>
					<?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php if ( $locks ) : ?>
			<h2><?php esc_html_e( 'Recent lockouts', 'semrs-core' ); ?></h2>
			<table class="widefat striped" style="max-width:520px"><thead><tr><th><?php esc_html_e( 'Time', 'semrs-core' ); ?></th><th><?php esc_html_e( 'IP address', 'semrs-core' ); ?></th></tr></thead><tbody>
			<?php foreach ( $locks as $l ) : ?>
				<tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $l[0] ) ); ?></td><td><code><?php echo esc_html( $l[1] ); ?></code></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
		<p><?php esc_html_e( 'SEMRS Pro adds a firewall, a malware scanner, spam protection for comments and forms, two-factor log-in, a custom log-in address and an activity log.', 'semrs-core' ); ?></p>
	</div>
	<?php
}
