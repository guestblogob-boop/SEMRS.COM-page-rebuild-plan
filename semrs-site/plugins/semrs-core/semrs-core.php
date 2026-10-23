<?php
/**
 * Plugin Name:       SEMRS Core
 * Plugin URI:        https://semrs.com/
 * Description:       Free SEO, speed, images, roles and ads for any WordPress site: titles, meta, schema, sitemaps, SEO score and site audit, llms.txt, redirects and 404 log, IndexNow (reads Rank Math, Yoast and AIOSEO data); WebP/AVIF image optimization, user role editor, review widgets; page cache, CSS and script optimization, prefetch, database cleanup, speed check; AdSense/AdX/ad network code with policy-safe placement and ads.txt. SEMRS Pro adds Pro modules.
 * Version:           1.3.10
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Update URI:        https://semrs.com/updates/semrs-core
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-core
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

// WP Consent API: stores only functional data (preferences, saved inputs) and loads no trackers itself.
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

define( 'SEMRS_CORE_VERSION', '1.3.10' );
define( 'SEMRS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_CORE_FILE', __FILE__ );
define( 'SEMRS_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Module files by key (absolute paths). SEMRS Pro adds its modules here.
 *
 * @return array<string, string>
 */
function semrs_core_module_files() {
	return apply_filters(
		'semrs_module_files',
		array(
			'seo'   => SEMRS_CORE_DIR . 'modules/seo/seo.php',
			'ads'   => SEMRS_CORE_DIR . 'modules/ads/ads.php',
			'speed'     => SEMRS_CORE_DIR . 'modules/speed/speed.php',
			'redirects' => SEMRS_CORE_DIR . 'modules/redirects/redirects.php',
			'indexnow'  => SEMRS_CORE_DIR . 'modules/indexnow/indexnow.php',
			'images'    => SEMRS_CORE_DIR . 'modules/images/images.php',
			'roles'     => SEMRS_CORE_DIR . 'modules/roles/roles.php',
			'reviews'   => SEMRS_CORE_DIR . 'modules/reviews/reviews.php',
		)
	);
}

/**
 * Module labels for the Modules screen: key => [label, description, tier].
 * Called only on admin screens (translations are ready by then).
 *
 * @return array<string, array<int, string>>
 */
function semrs_core_modules() {
	return apply_filters(
		'semrs_modules',
		array(
			'seo'   => array( __( 'SEO', 'semrs-core' ), __( 'Titles, meta descriptions, robots, canonical, Open Graph, JSON-LD schema, XML sitemaps, breadcrumbs, llms.txt, a 0–100 SEO score with readability checks and link ideas, and a site-wide SEO audit. Reads existing Rank Math, Yoast and AIOSEO data.', 'semrs-core' ), 'free' ),
			'ads'   => array( __( 'Ads Manager', 'semrs-core' ), __( 'Insert AdSense, Google Ad Manager (AdX) or any ad network code: in-content positions, header/footer code, shortcodes, exclusions, density caps, device targeting, lazy loading, ads.txt.', 'semrs-core' ), 'free' ),
			'speed' => array( __( 'Speed Optimization', 'semrs-core' ), __( 'Page cache, per-block CSS, inline or non-blocking CSS, deferred scripts, hover prefetch, YouTube click-to-play, image preload and preconnect hints; removes bloat (emoji, embeds, dashicons, jQuery Migrate, XML-RPC); database cleanup and a speed check.', 'semrs-core' ), 'free' ),
			'redirects' => array( __( 'Redirects & 404 log', 'semrs-core' ), __( '301/302/307/410 redirects with wildcards, loop protection, a 404 log with one-click redirect, and Rank Math redirect import.', 'semrs-core' ), 'free' ),
			'indexnow'  => array( __( 'IndexNow', 'semrs-core' ), __( 'Once you switch it on, tells Bing, Yandex, Seznam and other IndexNow search engines when you publish, update or delete content.', 'semrs-core' ), 'free' ),
			'images'    => array( __( 'Image Optimization', 'semrs-core' ), __( 'New uploads are compressed, resized to a maximum size and saved as WebP (or AVIF) on your own server. No outside service and no quota.', 'semrs-core' ), 'free' ),
			'roles'     => array( __( 'User Roles', 'semrs-core' ), __( 'Edit what each role can do, add roles copied from an existing one, delete custom roles and give a user extra roles.', 'semrs-core' ), 'free' ),
			'reviews'   => array( __( 'Review Widgets', 'semrs-core' ), __( 'Show your real Trustpilot or Google review widget with a shortcode. Its script loads only on pages that use it.', 'semrs-core' ), 'free' ),
		)
	);
}

/**
 * Is a module switched on? All on by default.
 *
 * @param string $key Module key.
 * @return bool
 */
function semrs_core_module_on( $key ) {
	$on = get_option( 'semrs_core_modules', null );
	return null === $on || ! isset( $on[ $key ] ) ? true : ! empty( $on[ $key ] );
}

/**
 * Load every switched-on module once all plugins are loaded, so SEMRS Pro
 * can register its modules first. No translations run at this point.
 */
function semrs_core_load_modules() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	foreach ( semrs_core_module_files() as $key => $file ) {
		if ( semrs_core_module_on( $key ) && file_exists( $file ) ) {
			require_once $file;
		}
	}
	do_action( 'semrs_modules_loaded' );
}
if ( did_action( 'plugins_loaded' ) ) {
	semrs_core_load_modules(); // Activated mid-request.
} else {
	add_action( 'plugins_loaded', 'semrs_core_load_modules', 1 );
}

if ( is_admin() ) {
	require SEMRS_CORE_DIR . 'admin/dashboard.php';
}

/**
 * Activation: flush rewrites (sitemaps) on the next request.
 */
function semrs_core_activate() {
	update_option( 'semrs_core_flush', 1 );
}
register_activation_hook( __FILE__, 'semrs_core_activate' );

/**
 * Deactivation: flush rewrites and delete cached pages.
 */
function semrs_core_deactivate() {
	flush_rewrite_rules();
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
}
register_deactivation_hook( __FILE__, 'semrs_core_deactivate' );

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( __DIR__ . '/includes/semrs-updater.php' ) ) {
	require_once __DIR__ . '/includes/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'plugin',
			'slug'    => 'semrs-core',
			'file'    => __FILE__,
			'version' => SEMRS_CORE_VERSION,
			'name'    => 'SEMRS Core',
		)
	);
}
