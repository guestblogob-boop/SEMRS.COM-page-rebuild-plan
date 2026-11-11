<?php
/**
 * SEMRS → Speed → Cloudflare tab.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Back to the tab with a message.
 *
 * @param true|WP_Error|string|array $res Result.
 */
function semrs_cf_back( $res ) {
	$uid = get_current_user_id();
	if ( is_wp_error( $res ) ) {
		set_transient( 'semrs_cf_err_' . $uid, $res->get_error_message(), 120 );
	} elseif ( is_array( $res ) ) {
		set_transient( 'semrs_cf_msg_' . $uid, implode( "\n", $res ), 120 );
	} elseif ( is_string( $res ) ) {
		set_transient( 'semrs_cf_msg_' . $uid, $res, 120 );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=semrs-speed&tab=cloudflare' ) );
	exit;
}

add_action(
	'admin_post_semrs_cf',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_cf' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-core' ) );
		}
		$do = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		$in = (array) wp_unslash( $_POST['cf'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per key.
		$s  = semrs_cf_settings();
		switch ( $do ) {
			case 'connect':
				$auth = array(
					'token' => trim( sanitize_text_field( $in['token'] ?? '' ) ),
					'email' => sanitize_email( $in['email'] ?? '' ),
					'key'   => trim( sanitize_text_field( $in['key'] ?? '' ) ),
				);
				if ( '' === $auth['token'] && '' === $auth['key'] ) {
					// Keep the saved credentials when the fields are left empty.
					$auth = array( 'token' => $s['token'], 'email' => $s['email'], 'key' => $s['key'] );
				}
				if ( '' === $auth['token'] && ( '' === $auth['email'] || '' === $auth['key'] ) ) {
					semrs_cf_back( new WP_Error( 'semrs_cf', __( 'Paste an API token (recommended), or your Cloudflare email and Global API key.', 'semrs-core' ) ) );
				}
				// Never send anything that is not shaped like a Cloudflare credential (for example a
				// password the browser filled in by itself): tokens are 40 characters, Global API keys 37 hex.
				if ( '' !== $auth['token'] ? ! preg_match( '/^[A-Za-z0-9_-]{40}$/', $auth['token'] ) : ! preg_match( '/^[a-f0-9]{37}$/i', $auth['key'] ) ) {
					semrs_cf_back( new WP_Error( 'semrs_cf', __( 'That does not look like a Cloudflare API token (40 letters, numbers, - and _) or Global API key (37 characters). Nothing was sent. If your browser filled in a saved password, clear the field and paste the token.', 'semrs-core' ) ) );
				}
				$zone = semrs_cf_find_zone( $auth );
				if ( is_wp_error( $zone ) ) {
					semrs_cf_back( $zone );
				}
				update_option( 'semrs_cf', array_merge( $s, $auth, array( 'zone' => $zone[0], 'zone_name' => $zone[1] ) ), false );
				/* translators: %s: domain */
				semrs_cf_back( sprintf( __( 'Connected to the Cloudflare zone %s.', 'semrs-core' ), $zone[1] ) );
				break;
			case 'disconnect':
				if ( '1' === $s['edge'] ) {
					semrs_cf_edge_set( false );
				}
				delete_option( 'semrs_cf' );
				semrs_cf_back( __( 'Disconnected. Your Cloudflare settings were not changed.', 'semrs-core' ) );
				break;
			case 'options':
				$s['purge']   = empty( $in['purge'] ) ? '' : '1';
				$s['real_ip'] = empty( $in['real_ip'] ) ? '' : '1';
				update_option( 'semrs_cf', $s, false );
				semrs_cf_back( __( 'Settings saved.', 'semrs-core' ) );
				break;
			case 'purge':
				$r = semrs_cf_purge_all();
				semrs_cf_back( is_wp_error( $r ) ? $r : __( 'Everything was cleared from Cloudflare’s cache.', 'semrs-core' ) );
				break;
			case 'dev_on':
			case 'dev_off':
				$r = semrs_cf_api( 'PATCH', '/zones/' . rawurlencode( $s['zone'] ) . '/settings/development_mode', array( 'value' => 'dev_on' === $do ? 'on' : 'off' ) );
				semrs_cf_back( is_wp_error( $r ) ? $r : ( 'dev_on' === $do ? __( 'Development mode is on for 3 hours: Cloudflare sends every request straight to your server.', 'semrs-core' ) : __( 'Development mode is off.', 'semrs-core' ) ) );
				break;
			case 'recommend':
				semrs_cf_back( semrs_cf_apply_recommended() );
				break;
			case 'restore':
				$r = semrs_cf_restore_previous();
				semrs_cf_back( is_wp_error( $r ) ? $r : __( 'Your previous Cloudflare settings are back.', 'semrs-core' ) );
				break;
			case 'edge_on':
			case 'edge_off':
				$r = semrs_cf_edge_set( 'edge_on' === $do );
				if ( is_wp_error( $r ) && false !== stripos( $r->get_error_message(), 'auth' ) ) {
					$r = new WP_Error( 'semrs_cf', $r->get_error_message() . ' — ' . __( 'Give the API token the “Cache Rules: Edit” permission too.', 'semrs-core' ) );
				}
				semrs_cf_back( is_wp_error( $r ) ? $r : ( 'edge_on' === $do ? __( 'Pages are now cached in Cloudflare’s data centers for logged-out visitors. They are cleared automatically when you change content.', 'semrs-core' ) : __( 'Edge page cache is off. Your other Cloudflare cache rules were kept.', 'semrs-core' ) ) );
				break;
		}
		semrs_cf_back( true );
	}
);

/**
 * Form opening.
 *
 * @param string $do Action.
 */
function semrs_cf_form( $do ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline"><input type="hidden" name="action" value="semrs_cf"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	wp_nonce_field( 'semrs_cf' );
}

/**
 * The tab.
 */
function semrs_speed_cloudflare_tab() {
	$s   = semrs_cf_settings();
	$uid = get_current_user_id();
	$msg = get_transient( 'semrs_cf_msg_' . $uid );
	$err = get_transient( 'semrs_cf_err_' . $uid );
	delete_transient( 'semrs_cf_msg_' . $uid );
	delete_transient( 'semrs_cf_err_' . $uid );
	$behind = ! empty( $_SERVER['HTTP_CF_RAY'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$box    = 'background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px 18px;margin:14px 0;max-width:820px';
	if ( $msg ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . nl2br( esc_html( $msg ) ) . '</p></div>';
	}
	if ( $err ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $err ) . '</p></div>';
	}
	if ( ! semrs_cf_connected() ) {
		?>
		<div style="<?php echo esc_attr( $box ); ?>">
			<h2 style="margin-top:0"><?php esc_html_e( 'Connect Cloudflare', 'semrs-core' ); ?></h2>
			<p><?php echo $behind ? esc_html__( 'This site is served through Cloudflare right now.', 'semrs-core' ) : esc_html__( 'Use this when your domain runs through Cloudflare (free or paid plan).', 'semrs-core' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'In Cloudflare, open My Profile → API Tokens → Create Token → “Create Custom Token”.', 'semrs-core' ); ?></li>
				<li><?php esc_html_e( 'Permissions: Zone → Zone → Read; Zone → Zone Settings → Edit; Zone → Cache Purge → Purge; Zone → Cache Rules → Edit. Zone resources: your domain.', 'semrs-core' ); ?></li>
				<li><?php esc_html_e( 'Create the token, copy it and paste it below. The zone is found automatically.', 'semrs-core' ); ?></li>
			</ol>
			<?php semrs_cf_form( 'connect' ); ?>
				<p><label><?php esc_html_e( 'API token', 'semrs-core' ); ?><br><input type="password" name="cf[token]" class="regular-text" autocomplete="new-password" data-lpignore="true" data-1p-ignore></label></p>
				<details><summary style="cursor:pointer"><?php esc_html_e( 'Use the Global API key instead (not recommended)', 'semrs-core' ); ?></summary>
					<p><label><?php esc_html_e( 'Cloudflare email', 'semrs-core' ); ?><br><input type="email" name="cf[email]" class="regular-text" autocomplete="off" data-lpignore="true" data-1p-ignore></label></p>
					<p><label><?php esc_html_e( 'Global API key', 'semrs-core' ); ?><br><input type="password" name="cf[key]" class="regular-text" autocomplete="new-password" data-lpignore="true" data-1p-ignore></label></p>
				</details>
				<?php submit_button( __( 'Connect', 'semrs-core' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
		return;
	}
	$zone = semrs_cf_api( 'GET', '/zones/' . rawurlencode( $s['zone'] ) );
	$dev  = semrs_cf_api( 'GET', '/zones/' . rawurlencode( $s['zone'] ) . '/settings/development_mode' );
	$ssl  = semrs_cf_api( 'GET', '/zones/' . rawurlencode( $s['zone'] ) . '/settings/ssl' );
	$other = semrs_cf_other();
	?>
	<div style="<?php echo esc_attr( $box ); ?>">
		<h2 style="margin-top:0"><?php esc_html_e( 'Cloudflare', 'semrs-core' ); ?></h2>
		<?php if ( is_wp_error( $zone ) ) : ?>
			<p style="color:#b32d2e"><?php echo esc_html( $zone->get_error_message() ); ?></p>
		<?php else : ?>
			<?php /* translators: 1: zone, 2: plan, 3: status */ ?>
			<p><strong><?php echo esc_html( sprintf( __( 'Connected: %1$s (%2$s plan, %3$s)', 'semrs-core' ), $zone['name'] ?? $s['zone_name'], $zone['plan']['name'] ?? '?', $zone['status'] ?? '?' ) ); ?></strong></p>
			<p><?php echo $behind ? esc_html__( '✓ Visitors reach this site through Cloudflare.', 'semrs-core' ) : esc_html__( '! This request did not come through Cloudflare: check that the domain’s DNS records are proxied (orange cloud).', 'semrs-core' ); ?>
			<?php if ( ! is_wp_error( $ssl ) && 'flexible' === ( $ssl['value'] ?? '' ) ) : ?><br><span style="color:#b32d2e"><?php esc_html_e( '! SSL/TLS is “Flexible”: set it to “Full (strict)” in Cloudflare to avoid redirect loops and keep traffic encrypted to your server.', 'semrs-core' ); ?></span><?php endif; ?></p>
		<?php endif; ?>
		<?php if ( $other ) : ?>
			<?php /* translators: %s: plugin */ ?>
			<p class="description"><?php echo esc_html( sprintf( __( '%s already clears Cloudflare when content changes, so SEMRS automatic purging steps aside. The buttons below still work.', 'semrs-core' ), $other ) ); ?></p>
		<?php endif; ?>
		<p>
			<?php semrs_cf_form( 'purge' ); ?><button class="button button-primary"><?php esc_html_e( 'Clear Cloudflare cache', 'semrs-core' ); ?></button></form>
			<?php if ( ! is_wp_error( $dev ) && 'on' === ( $dev['value'] ?? '' ) ) : ?>
				<?php semrs_cf_form( 'dev_off' ); ?><button class="button"><?php /* translators: %d: minutes */ echo esc_html( sprintf( __( 'Turn off development mode (%d min left)', 'semrs-core' ), (int) ceil( ( (int) ( $dev['time_remaining'] ?? 0 ) ) / 60 ) ) ); ?></button></form>
			<?php else : ?>
				<?php semrs_cf_form( 'dev_on' ); ?><button class="button"><?php esc_html_e( 'Development mode (3 hours)', 'semrs-core' ); ?></button></form>
			<?php endif; ?>
		</p>
		<p class="description"><?php esc_html_e( 'Development mode skips Cloudflare’s cache while you work on the design. It switches itself off after 3 hours.', 'semrs-core' ); ?></p>
	</div>

	<div style="<?php echo esc_attr( $box ); ?>">
		<h2 style="margin-top:0"><?php esc_html_e( 'Automatic', 'semrs-core' ); ?></h2>
		<?php semrs_cf_form( 'options' ); ?>
			<p><label><input type="checkbox" name="cf[purge]" value="1" <?php checked( '1', $s['purge'] ); ?>> <?php esc_html_e( 'Clear Cloudflare when content changes: an updated post clears only its own pages (post, home, categories, tags, author, feeds); theme, menu and settings changes clear everything.', 'semrs-core' ); ?></label></p>
			<p><label><input type="checkbox" name="cf[real_ip]" value="1" <?php checked( '1', $s['real_ip'] ); ?>> <?php esc_html_e( 'Use visitors’ real IP addresses (for comments, statistics and security), only for requests that really came from Cloudflare.', 'semrs-core' ); ?></label></p>
			<?php submit_button( __( 'Save', 'semrs-core' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<div style="<?php echo esc_attr( $box ); ?>">
		<h2 style="margin-top:0"><?php esc_html_e( 'Edge page cache', 'semrs-core' ); ?> <?php echo '1' === $s['edge'] ? '<span style="color:#15803d;font-size:13px">● ' . esc_html__( 'on', 'semrs-core' ) . '</span>' : ''; ?></h2>
		<p><?php esc_html_e( 'Cloudflare normally caches only images, CSS and scripts. This adds a Cache Rule so your pages themselves are served from Cloudflare’s data centers near each visitor, on any plan, like the paid APO. Logged-in users, commenters, carts, checkout, accounts, search, previews, feeds, the admin and the log-in page are never cached, and pages are cleared automatically when you change content.', 'semrs-core' ); ?></p>
		<?php if ( '1' === $s['edge'] ) : ?>
			<?php semrs_cf_form( 'edge_off' ); ?><button class="button"><?php esc_html_e( 'Turn off edge page cache', 'semrs-core' ); ?></button></form>
		<?php else : ?>
			<?php semrs_cf_form( 'edge_on' ); ?><button class="button button-primary"><?php esc_html_e( 'Turn on edge page cache', 'semrs-core' ); ?></button></form>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Your other Cloudflare cache rules are kept. Pages stay at most as long as the SEMRS page cache setting (up to 10 hours), so forms keep working.', 'semrs-core' ); ?></p>
	</div>

	<div style="<?php echo esc_attr( $box ); ?>">
		<h2 style="margin-top:0"><?php esc_html_e( 'Recommended Cloudflare settings', 'semrs-core' ); ?></h2>
		<ul style="list-style:disc;margin-left:20px">
			<?php foreach ( semrs_cf_recommended() as $x ) : ?><li><?php echo esc_html( $x[1] ); ?></li><?php endforeach; ?>
		</ul>
		<?php semrs_cf_form( 'recommend' ); ?><button class="button button-primary"><?php esc_html_e( 'Apply recommended settings', 'semrs-core' ); ?></button></form>
		<?php if ( $s['prev'] ) : ?>
			<?php semrs_cf_form( 'restore' ); ?><button class="button"><?php esc_html_e( 'Put my previous settings back', 'semrs-core' ); ?></button></form>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Your current values are saved before anything changes.', 'semrs-core' ); ?></p>
	</div>

	<p><?php semrs_cf_form( 'disconnect' ); ?><button class="button-link" style="color:#b32d2e"><?php esc_html_e( 'Disconnect Cloudflare', 'semrs-core' ); ?></button></form> <?php semrs_cf_form( 'connect' ); ?><button class="button-link"><?php esc_html_e( 'Find the zone again', 'semrs-core' ); ?></button></form></p>
	<?php
}
