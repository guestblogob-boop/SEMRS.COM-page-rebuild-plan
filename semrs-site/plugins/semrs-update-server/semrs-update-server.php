<?php
/**
 * Plugin Name:       SEMRS Update Server
 * Description:       Runs on semrs.com only. Publishes theme and plugin releases, sells SEMRS Pro add-ons (Lemon Squeezy and manual invoices), manages license keys and the My Licenses page, runs the yearly sales calendar, sales popups and the affiliate program, answers update checks from every site using SEMRS products, and lists the live sites with their versions.
 * Version:           1.4.13
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-update-server
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

// WP Consent API: stores only functional data (preferences, saved inputs) and loads no trackers itself.
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

define( 'SEMRS_US_VERSION', '1.4.13' );
define( 'SEMRS_US_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_US_URL', plugin_dir_url( __FILE__ ) );

/**
 * Products served: slug => [name, type, needs license].
 *
 * @return array<string, array>
 */
function semrs_us_products() {
	return apply_filters(
		'semrs_us_products',
		array(
			'semrs'           => array( 'SEMRS (theme)', 'theme', false ),
			'semrs-child-seo' => array( 'SEMRS Child SEO (theme)', 'theme', false ),
			'semrs-core'      => array( 'SEMRS Core', 'plugin', false ),
			'semrs-tools'     => array( 'SEMRS Tools', 'plugin', false ),
			'semrs-blocks'    => array( 'SEMRS Blocks', 'plugin', false ),
			'semrs-shops'     => array( 'SEMRS Shops', 'plugin', false ),
			'semrs-pro'       => array( 'SEMRS Pro', 'plugin', true ),
		)
	);
}

/**
 * Private folder for release zips (direct web access blocked).
 *
 * @return string
 */
function semrs_us_dir() {
	// Random folder name so release files cannot be guessed even on servers
	// that ignore .htaccess (e.g. Nginx). Downloads go through the signed API.
	$suffix = (string) get_option( 'semrs_us_dir_key', '' );
	if ( ! preg_match( '/^[a-f0-9]{24}$/', $suffix ) ) {
		$suffix = bin2hex( random_bytes( 12 ) );
		update_option( 'semrs_us_dir_key', $suffix, false );
	}
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'semrs-releases-' . $suffix . '/';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '.htaccess' ) ) {
		file_put_contents( $dir . '.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $dir . 'index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/**
 * Tables: live sites.
 */
function semrs_us_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$wpdb->prefix}semrs_sites (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(64) NOT NULL,
			host varchar(191) NOT NULL,
			version varchar(32) NOT NULL DEFAULT '',
			wp varchar(16) NOT NULL DEFAULT '',
			php varchar(16) NOT NULL DEFAULT '',
			license_key varchar(64) NOT NULL DEFAULT '',
			first_seen datetime NOT NULL,
			last_seen datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_host (slug, host),
			KEY last_seen (last_seen)
		) $charset;"
	);
	semrs_us_dir();
	update_option( 'semrs_us_db', SEMRS_US_VERSION );
}
register_activation_hook( __FILE__, 'semrs_us_install' );

require SEMRS_US_DIR . 'includes/data.php';
require SEMRS_US_DIR . 'includes/api.php';
require SEMRS_US_DIR . 'includes/sales.php';
require SEMRS_US_DIR . 'includes/lemonsqueezy.php';
require SEMRS_US_DIR . 'includes/paddle.php';
require SEMRS_US_DIR . 'includes/invoices.php';
require SEMRS_US_DIR . 'includes/account.php';
require SEMRS_US_DIR . 'includes/campaigns.php';
require SEMRS_US_DIR . 'includes/affiliates.php';
require SEMRS_US_DIR . 'includes/popups.php';
if ( is_admin() ) {
	require SEMRS_US_DIR . 'includes/admin.php';
	require SEMRS_US_DIR . 'includes/support-pages.php';
	require SEMRS_US_DIR . 'includes/sales-admin.php';
	require SEMRS_US_DIR . 'includes/campaigns-admin.php';
}

/**
 * The site that runs the update server checks updates and licences against
 * itself (e.g. the staging server), unless SEMRS_UPDATE_SERVER is defined.
 *
 * @param string $base Server URL.
 * @return string
 */
function semrs_us_self_server( $base ) {
	return defined( 'SEMRS_UPDATE_SERVER' ) ? $base : rest_url( 'semrs-updates/v1/' );
}
add_filter( 'semrs_update_server', 'semrs_us_self_server' );
