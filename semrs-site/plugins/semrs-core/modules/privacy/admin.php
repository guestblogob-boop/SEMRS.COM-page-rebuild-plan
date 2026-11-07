<?php
/**
 * SEMRS → Privacy settings screen and consent log.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Privacy & consent', 'semrs-core' ), __( 'Privacy', 'semrs-core' ), 'manage_options', 'semrs-privacy', 'semrs_privacy_page' );
	},
	30
);

add_action(
	'admin_init',
	function () {
		register_setting( 'semrs_privacy', 'semrs_privacy', array( 'type' => 'array', 'sanitize_callback' => 'semrs_privacy_sanitize' ) );
	}
);

/**
 * Sanitize settings.
 *
 * @param array $in Input.
 * @return array
 */
function semrs_privacy_sanitize( $in ) {
	$in  = (array) $in;
	$old = semrs_privacy_settings();
	$out = array(
		'on'          => empty( $in['on'] ) ? '' : '1',
		'type'        => isset( $in['type'] ) && 'optout' === $in['type'] ? 'optout' : 'optin',
		'position'    => isset( $in['position'] ) && in_array( $in['position'], array( 'bar', 'box-left', 'box-right' ), true ) ? $in['position'] : 'bar',
		'cmode'       => empty( $in['cmode'] ) ? '' : '1',
		'passthrough' => empty( $in['passthrough'] ) ? '' : '1',
		'redact'      => empty( $in['redact'] ) ? '' : '1',
		'reopen'      => empty( $in['reopen'] ) ? '' : '1',
		'log'         => empty( $in['log'] ) ? '' : '1',
		'days'        => max( 30, min( 395, absint( $in['days'] ?? 182 ) ) ),
		'log_days'    => max( 30, min( 3650, absint( $in['log_days'] ?? 365 ) ) ),
		'version'     => max( 1, absint( $old['version'] ) + ( empty( $in['renew'] ) ? 0 : 1 ) ),
		'accent'      => semrs_privacy_color( $in['accent'] ?? '' ),
		'cats'        => array(),
		'head'        => array(),
		'foot'        => array(),
	);
	foreach ( array( 'title', 'accept', 'reject', 'prefs', 'save' ) as $k ) {
		$out[ $k ] = sanitize_text_field( $in[ $k ] ?? $old[ $k ] );
	}
	$out['message'] = sanitize_textarea_field( $in['message'] ?? $old['message'] );
	foreach ( array( 'preferences', 'statistics', 'marketing' ) as $k ) {
		$out['cats'][ $k ] = empty( $in['cats'][ $k ] ) ? '' : '1';
	}
	foreach ( array( 'head', 'foot' ) as $where ) {
		foreach ( array( 'functional', 'statistics', 'marketing' ) as $k ) {
			$code = (string) ( $in[ $where ][ $k ] ?? '' );
			// Only users allowed to post raw HTML may save script code.
			$out[ $where ][ $k ] = current_user_can( 'unfiltered_html' ) ? trim( wp_unslash( $code ) ) : $old[ $where ][ $k ];
		}
	}
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
	return $out;
}

/**
 * CSV export of the consent log.
 */
add_action(
	'admin_post_semrs_consent_csv',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_consent_csv' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'semrs-core' ) );
		}
		global $wpdb;
		$rows = '1' === get_option( 'semrs_privacy_db' ) ? $wpdb->get_results( 'SELECT created, visitor, choices, version, ip_hash FROM ' . semrs_privacy_table() . ' ORDER BY id DESC LIMIT 50000', ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consent-log-' . gmdate( 'Y-m-d' ) . '.csv' );
		$f = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $f, array( 'time_utc', 'visitor_id', 'choices', 'policy_version', 'ip_hash' ) );
		foreach ( $rows as $r ) {
			fputcsv( $f, $r );
		}
		exit;
	}
);

/**
 * Settings page.
 */
function semrs_privacy_page() {
	$s    = semrs_privacy_settings();
	$cats = semrs_privacy_categories();
	$n    = 'semrs_privacy';
	$api  = function_exists( 'is_plugin_active' ) && is_plugin_active( 'wp-consent-api/wp-consent-api.php' );
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Privacy & consent', 'semrs-core' ); ?></h1>
		<p><?php esc_html_e( 'A cookie banner that asks before statistics and marketing tools run. It sends the choice to Google (Consent Mode v2) and to every plugin that supports the WP Consent API, and keeps a log as proof of consent.', 'semrs-core' ); ?></p>
		<div class="notice notice-info inline"><p>
			<?php if ( $api ) : ?>
				<?php esc_html_e( 'The WP Consent API plugin is active. SEMRS works with it, but you no longer need it: SEMRS has the same functions built in. You can deactivate and delete it after switching the banner on.', 'semrs-core' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'The WP Consent API is built in: plugins that support it (Site Kit, WooCommerce and others) read the visitor\'s choice from SEMRS. No extra plugin is needed.', 'semrs-core' ); ?>
			<?php endif; ?>
		</p></div>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'AdSense or Ad Manager visitors from the EEA, UK and Switzerland must see a Google-certified consent message (IAB TCF). Turn on Google\'s free "Privacy & messaging" European regulations message in AdSense for ads there; this banner then covers statistics and your other tools. This is guidance, not legal advice.', 'semrs-core' ); ?></p></div>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_privacy' ); ?>
			<h2><?php esc_html_e( 'Banner', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Show the banner', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[on]" value="1" <?php checked( $s['on'], '1' ); ?>> <?php esc_html_e( 'Ask visitors for consent', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Consent type', 'semrs-core' ); ?></th><td><select name="<?php echo esc_attr( $n ); ?>[type]"><option value="optin" <?php selected( $s['type'], 'optin' ); ?>><?php esc_html_e( 'Opt-in: nothing runs until the visitor agrees (GDPR, UK, Brazil)', 'semrs-core' ); ?></option><option value="optout" <?php selected( $s['type'], 'optout' ); ?>><?php esc_html_e( 'Opt-out: tools run until the visitor says no (US states such as California)', 'semrs-core' ); ?></option></select><p class="description"><?php esc_html_e( 'Opt-in is the safe choice when you have visitors from Europe.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Position', 'semrs-core' ); ?></th><td><select name="<?php echo esc_attr( $n ); ?>[position]"><option value="bar" <?php selected( $s['position'], 'bar' ); ?>><?php esc_html_e( 'Bar at the bottom', 'semrs-core' ); ?></option><option value="box-left" <?php selected( $s['position'], 'box-left' ); ?>><?php esc_html_e( 'Box, bottom left', 'semrs-core' ); ?></option><option value="box-right" <?php selected( $s['position'], 'box-right' ); ?>><?php esc_html_e( 'Box, bottom right', 'semrs-core' ); ?></option></select></td></tr>
				<tr><th><?php esc_html_e( 'Title', 'semrs-core' ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $n ); ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Message', 'semrs-core' ); ?></th><td><textarea class="large-text" rows="3" name="<?php echo esc_attr( $n ); ?>[message]"><?php echo esc_textarea( $s['message'] ); ?></textarea><p class="description"><?php esc_html_e( 'A link to your privacy policy (Settings → Privacy) is added automatically.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Button texts', 'semrs-core' ); ?></th><td>
					<?php foreach ( array( 'accept' => __( 'Accept', 'semrs-core' ), 'reject' => __( 'Reject', 'semrs-core' ), 'prefs' => __( 'Preferences', 'semrs-core' ), 'save' => __( 'Save', 'semrs-core' ) ) as $k => $l ) : ?>
						<label style="display:inline-block;margin:0 12px 6px 0"><?php echo esc_html( $l ); ?><br><input type="text" name="<?php echo esc_attr( $n . '[' . $k . ']' ); ?>" value="<?php echo esc_attr( $s[ $k ] ); ?>"></label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Accept and Reject are shown side by side, equally easy to click, as European regulators require.', 'semrs-core' ); ?></p>
				</td></tr>
				<tr><th><?php esc_html_e( 'Categories', 'semrs-core' ); ?></th><td>
					<?php foreach ( $cats as $k => $c ) : ?>
						<label style="display:block;margin-bottom:4px"><input type="checkbox" name="<?php echo esc_attr( $n . '[cats][' . $k . ']' ); ?>" value="1" <?php echo 'functional' === $k ? 'checked disabled' : checked( $s['cats'][ $k ] ?? '', '1', false ); ?>> <b><?php echo esc_html( $c[0] ); ?></b> – <?php echo esc_html( $c[1] ); ?></label>
					<?php endforeach; ?>
				</td></tr>
				<tr><th><?php esc_html_e( 'Remember the choice for', 'semrs-core' ); ?></th><td><input type="number" min="30" max="395" name="<?php echo esc_attr( $n ); ?>[days]" value="<?php echo esc_attr( $s['days'] ); ?>"> <?php esc_html_e( 'days (most regulators accept up to 13 months)', 'semrs-core' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Cookie settings button', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[reopen]" value="1" <?php checked( $s['reopen'], '1' ); ?>> <?php esc_html_e( 'Small round button in the corner so visitors can change their choice', 'semrs-core' ); ?></label><p class="description"><?php esc_html_e( 'You can also add the shortcode [semrs_cookie_settings] or a link to #cookie-settings, for example in the footer.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Button color', 'semrs-core' ); ?></th><td><input type="text" name="<?php echo esc_attr( $n ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" placeholder="#0b1f3a"></td></tr>
				<tr><th><?php esc_html_e( 'Ask everyone again', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[renew]" value="1"> <?php esc_html_e( 'I changed my cookies or privacy policy: show the banner to everyone again', 'semrs-core' ); ?></label> <span class="description"><?php /* translators: %d: version number. */ echo esc_html( sprintf( __( '(policy version %d)', 'semrs-core' ), $s['version'] ) ); ?></span></td></tr>
			</table>

			<h2><?php esc_html_e( 'Google Consent Mode v2', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Consent Mode', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[cmode]" value="1" <?php checked( $s['cmode'], '1' ); ?>> <?php esc_html_e( 'Tell Google tags (Analytics, Ads, Tag Manager, Site Kit) what the visitor allowed', 'semrs-core' ); ?></label><p class="description"><?php esc_html_e( 'Required by Google for ads personalisation and measurement of visitors from the EEA and UK.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Ads data redaction', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[redact]" value="1" <?php checked( $s['redact'], '1' ); ?>> <?php esc_html_e( 'Remove ad click identifiers when marketing is not allowed', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'URL passthrough', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[passthrough]" value="1" <?php checked( $s['passthrough'], '1' ); ?>> <?php esc_html_e( 'Keep ad click information in links when cookies are refused (improves conversion measurement)', 'semrs-core' ); ?></label></td></tr>
			</table>

			<h2><?php esc_html_e( 'Header and footer scripts', 'semrs-core' ); ?></h2>
			<p><?php esc_html_e( 'Paste tracking or chat codes here instead of in the theme. Statistics and marketing codes wait until the visitor agrees to that category. Do not paste the same Google tag that Site Kit already adds.', 'semrs-core' ); ?></p>
			<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?><p><em><?php esc_html_e( 'Your account cannot save script code.', 'semrs-core' ); ?></em></p><?php endif; ?>
			<table class="form-table" role="presentation">
				<?php foreach ( array( 'head' => __( 'In <head>', 'semrs-core' ), 'foot' => __( 'Before </body>', 'semrs-core' ) ) as $where => $wl ) : ?>
					<?php foreach ( array( 'functional' => __( 'Necessary (always)', 'semrs-core' ), 'statistics' => __( 'Statistics (e.g. Google Analytics, Clarity)', 'semrs-core' ), 'marketing' => __( 'Marketing (e.g. Meta pixel, Google Ads, TikTok)', 'semrs-core' ) ) as $k => $l ) : ?>
						<tr><th><?php echo esc_html( $wl . ' – ' . $l ); ?></th><td><textarea class="large-text code" rows="3" name="<?php echo esc_attr( $n . '[' . $where . '][' . $k . ']' ); ?>" <?php disabled( ! current_user_can( 'unfiltered_html' ) ); ?>><?php echo esc_textarea( $s[ $where ][ $k ] ); ?></textarea></td></tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</table>

			<h2><?php esc_html_e( 'Consent log', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Keep a log', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[log]" value="1" <?php checked( $s['log'], '1' ); ?>> <?php esc_html_e( 'Save each choice as proof of consent: a random visitor ID, the choices, the policy version and a shortened hash of the IP address (no names, no full IP addresses)', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Delete records after', 'semrs-core' ); ?></th><td><input type="number" min="30" max="3650" name="<?php echo esc_attr( $n ); ?>[log_days]" value="<?php echo esc_attr( $s['log_days'] ); ?>"> <?php esc_html_e( 'days', 'semrs-core' ); ?></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php semrs_privacy_log_table(); ?>
	</div>
	<?php
}

/**
 * Latest consent records with totals.
 */
function semrs_privacy_log_table() {
	if ( '1' !== get_option( 'semrs_privacy_db' ) ) {
		return;
	}
	global $wpdb;
	$t     = semrs_privacy_table();
	$rows  = $wpdb->get_results( "SELECT created, visitor, choices, version FROM {$t} ORDER BY id DESC LIMIT 20" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$all   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE choices LIKE '%statistics:1%' AND choices LIKE '%marketing:1%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	echo '<h2>' . esc_html__( 'Latest choices', 'semrs-core' ) . '</h2>';
	/* translators: 1: total records, 2: accepted all. */
	echo '<p>' . esc_html( sprintf( __( '%1$d records; %2$d accepted everything.', 'semrs-core' ), $total, $all ) ) . ' <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=semrs_consent_csv' ), 'semrs_consent_csv' ) ) . '">' . esc_html__( 'Download CSV', 'semrs-core' ) . '</a></p>';
	if ( ! $rows ) {
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Time (UTC)', 'semrs-core' ) . '</th><th>' . esc_html__( 'Visitor ID', 'semrs-core' ) . '</th><th>' . esc_html__( 'Choices', 'semrs-core' ) . '</th><th>' . esc_html__( 'Policy version', 'semrs-core' ) . '</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr><td>' . esc_html( $r->created ) . '</td><td><code>' . esc_html( substr( $r->visitor, 0, 12 ) ) . '…</code></td><td>' . esc_html( str_replace( array( ':1', ':0', ',' ), array( ' ✓', ' ✗', ', ' ), $r->choices ) ) . '</td><td>' . esc_html( $r->version ) . '</td></tr>';
	}
	echo '</tbody></table>';
}
