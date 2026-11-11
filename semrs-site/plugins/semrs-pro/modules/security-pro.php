<?php
/**
 * Security Pro: firewall, malware scanner and anti-spam (SEMRS → Security Pro).
 *
 * Works side by side with other security plugins:
 * - Wordfence active: the SEMRS firewall and the daily scan pause (unless you
 *   choose to run both), so requests and files are never checked twice.
 * - Akismet active: SEMRS anti-spam stops the bots first; Akismet still
 *   checks everything that passes. Both use the normal Spam folder.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/security/firewall-rules.php';
require_once __DIR__ . '/security/scanner.php';
require_once __DIR__ . '/security/antispam.php';

/*
 * Firewall: the early loader.
 */

/**
 * Must-use loader path.
 *
 * @return string
 */
function semrs_fw_loader_path() {
	return ( defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins' ) . '/semrs-firewall.php';
}

/**
 * Loader file contents.
 *
 * @return string
 */
function semrs_fw_loader_code() {
	return "<?php\n/**\n * Plugin Name: SEMRS Firewall (early loader)\n * Description: Runs the SEMRS Pro firewall before other plugins load. Added and removed automatically by SEMRS Pro (SEMRS → Security Pro). Emergency switch: add define( 'SEMRS_FIREWALL_OFF', true ); to wp-config.php.\n * Version: 1\n * Author: SEMRS\n */\n\ndefined( 'ABSPATH' ) || exit;\n\nif ( ! defined( 'SEMRS_FIREWALL_OFF' ) || ! SEMRS_FIREWALL_OFF ) {\n\t\$semrs_fw_file = WP_PLUGIN_DIR . '/semrs-pro/modules/security/firewall-rules.php';\n\tif ( is_file( \$semrs_fw_file ) && in_array( 'semrs-pro/semrs-pro.php', (array) get_option( 'active_plugins', array() ), true ) ) {\n\t\tdefine( 'SEMRS_FW_EARLY', true );\n\t\trequire_once \$semrs_fw_file;\n\t\tsemrs_fw_boot( 'early' );\n\t}\n\tunset( \$semrs_fw_file );\n}\n";
}

/**
 * Add or remove the early loader to match the settings.
 *
 * @return string early|standard|paused|off
 */
function semrs_fw_sync_loader() {
	$path = semrs_fw_loader_path();
	$want = semrs_fw_enabled();
	if ( ! $want ) {
		if ( is_file( $path ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
		}
		$s = semrs_fw_settings();
		return '1' === $s['fw_on'] ? 'paused' : 'off';
	}
	if ( is_file( $path ) && (string) file_get_contents( $path ) === semrs_fw_loader_code() ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return 'early';
	}
	wp_mkdir_p( dirname( $path ) );
	if ( wp_is_writable( dirname( $path ) ) && false !== file_put_contents( $path, semrs_fw_loader_code() ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return 'early';
	}
	return 'standard';
}

/**
 * Firewall status for the screens.
 *
 * @return string early|standard|paused|off
 */
function semrs_fw_status() {
	if ( ! semrs_fw_enabled() ) {
		return '1' === semrs_fw_settings()['fw_on'] ? 'paused' : 'off';
	}
	return is_file( semrs_fw_loader_path() ) ? 'early' : 'standard';
}

if ( '1' !== get_option( 'semrs_fw_live' ) ) {
	update_option( 'semrs_fw_live', '1', true );
}

// Without the early loader, the firewall runs now (SEMRS modules load first thing on plugins_loaded).
if ( ! defined( 'SEMRS_FW_EARLY' ) ) {
	semrs_fw_boot( 'late' );
}

// Floods of missing pages (vulnerability scanners) → short block. Search engines are never counted.
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() || is_user_logged_in() || ! semrs_fw_enabled() || '1' !== semrs_fw_settings()['fw_flood'] || 'log' === semrs_fw_settings()['fw_mode'] ) {
			return;
		}
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( preg_match( '/googlebot|google-inspectiontool|bingbot|duckduckbot|yandexbot|baiduspider|applebot|slurp|petalbot|ahrefsbot|semrushbot|mj12bot|facebookexternalhit|linkedinbot|twitterbot/i', $ua ) ) {
			return;
		}
		semrs_fw_strike( semrs_fw_ip(), 'flood', 40, 2 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS, 'Too many missing pages' );
	},
	1
);

if ( is_admin() ) {
	require __DIR__ . '/security/admin.php';
}
