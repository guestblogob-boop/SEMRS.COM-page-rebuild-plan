<?php
/**
 * Privacy & consent: a cookie banner with categories, Google Consent Mode
 * v2, the WP Consent API (built in, so the separate plugin is not needed),
 * header and footer scripts that wait for consent, and a consent log.
 *
 * Off until the site owner switches the banner on (SEMRS → Privacy).
 *
 * Note: Google AdSense / Ad Manager in the EEA, UK and Switzerland need a
 * Google-certified CMP (IAB TCF). Use Google's free "Privacy & messaging"
 * for ad consent there; this banner then handles the other categories.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_privacy_settings() {
	$d = array(
		'on'          => '',
		'type'        => 'optin',
		'position'    => 'bar',
		'title'       => __( 'We value your privacy', 'semrs-core' ),
		'message'     => __( 'We use cookies to run this site, and with your permission to measure visits and show relevant ads. You can change your choice at any time.', 'semrs-core' ),
		'accept'      => __( 'Accept all', 'semrs-core' ),
		'reject'      => __( 'Reject all', 'semrs-core' ),
		'prefs'       => __( 'Preferences', 'semrs-core' ),
		'save'        => __( 'Save choices', 'semrs-core' ),
		'cats'        => array( 'preferences' => '1', 'statistics' => '1', 'marketing' => '1' ),
		'cmode'       => '1',
		'passthrough' => '',
		'redact'      => '1',
		'days'        => 182,
		'version'     => 1,
		'reopen'      => '1',
		'log'         => '1',
		'log_days'    => 365,
		'head'        => array( 'functional' => '', 'statistics' => '', 'marketing' => '' ),
		'foot'        => array( 'functional' => '', 'statistics' => '', 'marketing' => '' ),
		'accent'      => '',
	);
	$s = wp_parse_args( (array) get_option( 'semrs_privacy', array() ), $d );
	foreach ( array( 'cats', 'head', 'foot' ) as $k ) {
		$s[ $k ] = wp_parse_args( (array) $s[ $k ], $d[ $k ] );
	}
	return $s;
}

/**
 * Consent categories shown in the banner: key => [label, description].
 *
 * @return array
 */
function semrs_privacy_categories() {
	return array(
		'functional'  => array( __( 'Necessary', 'semrs-core' ), __( 'Needed for the site to work: security, log-in, your cookie choice. Always on.', 'semrs-core' ) ),
		'preferences' => array( __( 'Preferences', 'semrs-core' ), __( 'Remember settings such as language or dark mode.', 'semrs-core' ) ),
		'statistics'  => array( __( 'Statistics', 'semrs-core' ), __( 'Count visits and see which pages are used, so we can improve the site.', 'semrs-core' ) ),
		'marketing'   => array( __( 'Marketing', 'semrs-core' ), __( 'Personalised ads and measuring ad results.', 'semrs-core' ) ),
	);
}

/**
 * Is the banner on (and not inside the editor, Customizer or an admin page)?
 *
 * @return bool
 */
function semrs_privacy_active() {
	return '1' === semrs_privacy_settings()['on'] && ! is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

/*
 * ---------------------------------------------------------------------
 * WP Consent API (built in). When the WP Consent API plugin is active it
 * owns these functions and SEMRS only tells it the consent type and sets
 * the visitor's choices through its JavaScript. Without the plugin, SEMRS
 * provides the same functions, cookies and events, so plugins that support
 * the API (Site Kit, WooCommerce, …) work without it.
 * ---------------------------------------------------------------------
 */

add_filter( 'wp_consent_api_registered_' . plugin_basename( SEMRS_CORE_DIR . 'semrs-core.php' ), '__return_true' );

/**
 * Consent type for the WP Consent API plugin.
 *
 * @param string $type Type.
 * @return string
 */
function semrs_privacy_consent_type( $type ) {
	return '1' === semrs_privacy_settings()['on'] ? semrs_privacy_settings()['type'] : $type;
}
add_filter( 'wp_get_consent_type', 'semrs_privacy_consent_type' );

/**
 * PHP side of the API when the plugin is not active (defined late, so the
 * plugin always wins if it is switched on later).
 */
function semrs_privacy_api_shim() {
	if ( function_exists( 'wp_has_consent' ) || '1' !== semrs_privacy_settings()['on'] ) {
		return;
	}
	// Stay out of the way while a plugin is being activated (the WP Consent API
	// plugin defines the same functions).
	$act = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( is_admin() && in_array( $act, array( 'activate', 'activate-selected' ), true ) ) {
		return;
	}
	require __DIR__ . '/consent-api.php';
}
add_action( 'plugins_loaded', 'semrs_privacy_api_shim', 999 );

/*
 * ---------------------------------------------------------------------
 * Front end.
 * ---------------------------------------------------------------------
 */

/**
 * Google Consent Mode v2 defaults, as early as possible in <head>.
 */
function semrs_privacy_consent_mode() {
	if ( ! semrs_privacy_active() ) {
		return;
	}
	$s = semrs_privacy_settings();
	if ( '1' !== $s['cmode'] ) {
		return;
	}
	$deny = 'optin' === $s['type'] ? 'denied' : 'granted';
	$js   = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
		. "gtag('consent','default',{ad_storage:'" . $deny . "',ad_user_data:'" . $deny . "',ad_personalization:'" . $deny . "',analytics_storage:'" . $deny . "',functionality_storage:'granted',personalization_storage:'" . $deny . "',security_storage:'granted',wait_for_update:500});"
		. ( '1' === $s['redact'] ? "gtag('set','ads_data_redaction',true);" : '' )
		. ( '1' === $s['passthrough'] ? "gtag('set','url_passthrough',true);" : '' )
		// Returning visitors: apply the saved choice before any tag runs.
		. '(function(){var m=document.cookie.match(/(?:^|; )semrs_consent=([^;]+)/);if(!m)return;try{var c=JSON.parse(decodeURIComponent(m[1]));var g=function(k){return c[k]?"granted":"denied"};gtag("consent","update",{ad_storage:g("marketing"),ad_user_data:g("marketing"),ad_personalization:g("marketing"),analytics_storage:g("statistics"),personalization_storage:g("preferences")});}catch(e){}})();';
	echo '<script id="semrs-consent-mode" data-no-delay>' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- built from fixed strings.
}
add_action( 'wp_head', 'semrs_privacy_consent_mode', -1000 );

/**
 * Turn <script> tags in owner-supplied code into inert placeholders that
 * the banner script activates after consent for that category.
 *
 * @param string $code     HTML.
 * @param string $category Category.
 * @return string
 */
function semrs_privacy_wrap_scripts( $code, $category ) {
	if ( 'functional' === $category || '' === trim( $code ) ) {
		return $code;
	}
	return preg_replace_callback(
		'#<script\b([^>]*)>#i',
		function ( $m ) use ( $category ) {
			$attrs = preg_replace( '#\stype\s*=\s*(["\']).*?\1#i', '', $m[1] );
			return '<script type="text/plain" data-semrs-consent="' . esc_attr( $category ) . '"' . $attrs . '>';
		},
		$code
	);
}

/**
 * Header scripts (owner-supplied, saved by an administrator).
 */
function semrs_privacy_head_scripts() {
	$s = semrs_privacy_settings();
	if ( is_admin() ) {
		return;
	}
	$banner = semrs_privacy_active();
	foreach ( $s['head'] as $cat => $code ) {
		echo $banner ? semrs_privacy_wrap_scripts( $code, $cat ) : $code; // phpcs:ignore WordPress.Security.EscapeOutput -- code saved by a user with unfiltered_html.
	}
}
add_action( 'wp_head', 'semrs_privacy_head_scripts', 20 );

/**
 * Footer scripts, banner HTML, CSS and JS.
 */
function semrs_privacy_footer() {
	if ( is_admin() ) {
		return;
	}
	$s      = semrs_privacy_settings();
	$banner = semrs_privacy_active();
	foreach ( $s['foot'] as $cat => $code ) {
		echo $banner ? semrs_privacy_wrap_scripts( $code, $cat ) : $code; // phpcs:ignore WordPress.Security.EscapeOutput -- code saved by a user with unfiltered_html.
	}
	if ( ! $banner ) {
		return;
	}
	$cats   = semrs_privacy_categories();
	$rows   = '';
	foreach ( $cats as $key => $c ) {
		if ( 'functional' !== $key && empty( $s['cats'][ $key ] ) ) {
			continue;
		}
		$fixed = 'functional' === $key;
		$rows .= '<label class="semrs-cc__cat"><input type="checkbox" data-cat="' . esc_attr( $key ) . '"' . ( $fixed ? ' checked disabled' : '' ) . '><span><b>' . esc_html( $c[0] ) . '</b><small>' . esc_html( $c[1] ) . '</small></span></label>';
	}
	$policy = get_privacy_policy_url();
	$conf   = array(
		'days'    => max( 30, min( 395, (int) $s['days'] ) ),
		'version' => (int) $s['version'],
		'type'    => $s['type'],
		'cats'    => array_values( array_filter( array_keys( $cats ), function ( $k ) use ( $s ) { return 'functional' === $k || ! empty( $s['cats'][ $k ] ); } ) ),
		'log'     => '1' === $s['log'] ? esc_url_raw( rest_url( 'semrs-core/v1/consent' ) ) : '',
		'api'     => function_exists( 'wp_has_consent' ),
	);
	$css = '.semrs-cc{position:fixed;z-index:99999;left:16px;right:16px;bottom:16px;max-width:980px;margin:0 auto;padding:18px 20px;border-radius:12px;background:#fff;color:#1f2937;box-shadow:0 10px 40px rgba(0,0,0,.25);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}.semrs-cc[hidden]{display:none}.semrs-cc--box-left{right:auto;max-width:400px}.semrs-cc--box-right{left:auto;max-width:400px}.semrs-cc h2{margin:0 0 6px;font-size:17px;color:inherit}.semrs-cc p{margin:0 0 12px}.semrs-cc a{color:inherit;text-decoration:underline}.semrs-cc__btns{display:flex;flex-wrap:wrap;gap:8px}.semrs-cc__btns button{flex:1 1 auto;min-height:44px;padding:8px 16px;border:2px solid var(--cc,#0b1f3a);border-radius:8px;background:var(--cc,#0b1f3a);color:#fff;font:inherit;font-weight:700;cursor:pointer}.semrs-cc__btns .is-alt{background:transparent;color:var(--cc,#0b1f3a)}.semrs-cc__cats{display:grid;gap:8px;margin:0 0 12px}.semrs-cc__cats[hidden]{display:none}.semrs-cc__cat{display:flex;gap:10px;align-items:flex-start;padding:8px 10px;border:1px solid #e3e7ee;border-radius:8px;cursor:pointer}.semrs-cc__cat input{margin-top:4px;width:18px;height:18px}.semrs-cc__cat b{display:block}.semrs-cc__cat small{color:#5b6575}.semrs-cc-open{position:fixed;z-index:9998;left:12px;bottom:12px;width:40px;height:40px;border:0;border-radius:50%;background:var(--cc,#0b1f3a);color:#fff;font-size:20px;line-height:40px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.25)}html[data-theme=dark] .semrs-cc{background:#111827;color:#e5e7eb}html[data-theme=dark] .semrs-cc__cat{border-color:#374151}html[data-theme=dark] .semrs-cc__cat small{color:#9ca3af}';
	$accent = semrs_privacy_color( $s['accent'] );
	?>
	<style id="semrs-cc-css"><?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed CSS. ?></style>
	<div class="semrs-cc semrs-cc--<?php echo esc_attr( $s['position'] ); ?>" id="semrs-cc" role="dialog" aria-modal="false" aria-labelledby="semrs-cc-title" hidden<?php echo $accent ? ' style="--cc:' . esc_attr( $accent ) . '"' : ''; ?>>
		<h2 id="semrs-cc-title"><?php echo esc_html( $s['title'] ); ?></h2>
		<p><?php echo esc_html( $s['message'] ); ?><?php echo $policy ? ' <a href="' . esc_url( $policy ) . '">' . esc_html__( 'Privacy policy', 'semrs-core' ) . '</a>' : ''; ?></p>
		<div class="semrs-cc__cats" hidden><?php echo $rows; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></div>
		<div class="semrs-cc__btns">
			<button type="button" data-cc="reject" class="is-alt"><?php echo esc_html( $s['reject'] ); ?></button>
			<button type="button" data-cc="prefs" class="is-alt"><?php echo esc_html( $s['prefs'] ); ?></button>
			<button type="button" data-cc="save" class="is-alt" hidden><?php echo esc_html( $s['save'] ); ?></button>
			<button type="button" data-cc="accept"><?php echo esc_html( $s['accept'] ); ?></button>
		</div>
	</div>
	<?php if ( '1' === $s['reopen'] ) : ?>
		<button type="button" class="semrs-cc-open" data-cc="open" aria-label="<?php esc_attr_e( 'Cookie settings', 'semrs-core' ); ?>" hidden<?php echo $accent ? ' style="--cc:' . esc_attr( $accent ) . '"' : ''; ?>>&#9881;</button>
	<?php endif; ?>
	<script id="semrs-cc-js" data-no-delay>window.semrsConsentConf=<?php echo wp_json_encode( $conf ); ?>;<?php echo file_get_contents( __DIR__ . '/consent.js' ); // phpcs:ignore WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions -- bundled script. ?></script>
	<?php
}
add_action( 'wp_footer', 'semrs_privacy_footer', 5 );

/**
 * Safe colour value.
 *
 * @param string $c Colour.
 * @return string
 */
function semrs_privacy_color( $c ) {
	return preg_match( '/^#[0-9a-fA-F]{3,8}$/', (string) $c ) ? $c : '';
}

/**
 * [semrs_cookie_settings] – a link that reopens the banner.
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_privacy_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'text' => __( 'Cookie settings', 'semrs-core' ) ), $atts );
	return '<a href="#cookie-settings" class="semrs-cc-link">' . esc_html( $atts['text'] ) . '</a>';
}
add_shortcode( 'semrs_cookie_settings', 'semrs_privacy_shortcode' );

/*
 * ---------------------------------------------------------------------
 * Consent log (proof of consent, GDPR art. 7(1)). Stores a random visitor
 * id, the choices, the policy version and a shortened, salted hash of the
 * IP address. No names, no full IP addresses.
 * ---------------------------------------------------------------------
 */

/**
 * Table name.
 *
 * @return string
 */
function semrs_privacy_table() {
	global $wpdb;
	return $wpdb->prefix . 'semrs_consent_log';
}

/**
 * Create the table once.
 */
function semrs_privacy_install() {
	if ( '1' === get_option( 'semrs_privacy_db' ) ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta(
		'CREATE TABLE ' . semrs_privacy_table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created datetime NOT NULL,
			visitor char(32) NOT NULL DEFAULT '',
			choices varchar(191) NOT NULL DEFAULT '',
			version smallint(5) unsigned NOT NULL DEFAULT 1,
			ip_hash char(16) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY created (created),
			KEY visitor (visitor)
		) " . $wpdb->get_charset_collate() . ';'
	);
	update_option( 'semrs_privacy_db', '1', false );
}
add_action( 'admin_init', 'semrs_privacy_install' );

/**
 * REST endpoint the banner posts a choice to.
 */
function semrs_privacy_rest() {
	register_rest_route(
		'semrs-core/v1',
		'/consent',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'semrs_privacy_log_choice',
		)
	);
}
add_action( 'rest_api_init', 'semrs_privacy_rest' );

/**
 * Save one consent record.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function semrs_privacy_log_choice( $req ) {
	$s = semrs_privacy_settings();
	if ( '1' !== $s['on'] || '1' !== $s['log'] || '1' !== get_option( 'semrs_privacy_db' ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 200 );
	}
	$visitor = preg_replace( '/[^a-f0-9]/', '', (string) $req->get_param( 'id' ) );
	if ( 32 !== strlen( $visitor ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 400 );
	}
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$hash = substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 16 );
	// Rate limit: one record per IP hash per 10 seconds.
	if ( get_transient( 'semrs_cc_' . $hash ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	set_transient( 'semrs_cc_' . $hash, 1, 10 );
	$choices = array();
	foreach ( array_keys( semrs_privacy_categories() ) as $k ) {
		$choices[] = $k . ':' . ( 'functional' === $k || $req->get_param( $k ) ? '1' : '0' );
	}
	global $wpdb;
	$wpdb->insert( semrs_privacy_table(), array( 'created' => current_time( 'mysql', true ), 'visitor' => $visitor, 'choices' => implode( ',', $choices ), 'version' => (int) $s['version'], 'ip_hash' => $hash ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Delete old records daily.
 */
function semrs_privacy_cleanup() {
	if ( '1' !== get_option( 'semrs_privacy_db' ) ) {
		return;
	}
	global $wpdb;
	$days = max( 30, (int) semrs_privacy_settings()['log_days'] );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . semrs_privacy_table() . ' WHERE created < %s', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
add_action( 'semrs_privacy_daily', 'semrs_privacy_cleanup' );
if ( ! wp_next_scheduled( 'semrs_privacy_daily' ) ) {
	wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'semrs_privacy_daily' );
}

if ( is_admin() ) {
	require __DIR__ . '/admin.php';
}
