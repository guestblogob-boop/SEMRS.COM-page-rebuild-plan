<?php
/**
 * Plugin Name:       SEMRS Pro
 * Plugin URI:        https://semrs.com/
 * Description:       Premium add-on for the SEMRS theme and SEMRS Core: sticky/transparent header, mega menu, table of contents, author box, related posts, extra section patterns, white label; redirects + 404 log, multi-location local SEO, IndexNow, image SEO; ads manager; site layouts; case studies, portfolio and testimonials; lead tracking.
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Update URI:        https://semrs.com/updates/semrs-pro
 * Requires Plugins:  semrs-core
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-pro
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_PRO_VERSION', '1.2.0' );
define( 'SEMRS_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_PRO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register Pro module files with SEMRS Core's loader.
 *
 * @param array $files key => absolute path.
 * @return array
 */
function semrs_pro_module_files( $files ) {
	return array_merge(
		$files,
		array(
			'theme_pro' => SEMRS_PRO_DIR . 'modules/theme-pro.php',
			'redirects' => SEMRS_PRO_DIR . 'modules/redirects.php',
			'local'     => SEMRS_PRO_DIR . 'modules/local-seo.php',
			'indexnow'  => SEMRS_PRO_DIR . 'modules/indexnow.php',
			'image_seo' => SEMRS_PRO_DIR . 'modules/image-seo.php',
			'layouts'   => SEMRS_PRO_DIR . 'modules/layouts.php',
			'proof'     => SEMRS_PRO_DIR . 'modules/proof.php',
			'leads'     => SEMRS_PRO_DIR . 'modules/leads.php',
			'blocks_pro' => SEMRS_PRO_DIR . 'modules/blocks-pro.php',
			'conditions' => SEMRS_PRO_DIR . 'modules/conditions.php',
			'popups'     => SEMRS_PRO_DIR . 'modules/popups.php',
			'languages'  => SEMRS_PRO_DIR . 'modules/languages.php',
			'templates_pro' => SEMRS_PRO_DIR . 'modules/templates-pro.php',
			'seo_bulk'   => SEMRS_PRO_DIR . 'modules/seo-bulk.php',
			'speed_pro'  => SEMRS_PRO_DIR . 'modules/speed-pro.php',
			'ads_pro'    => SEMRS_PRO_DIR . 'modules/ads-pro.php',
		)
	);
}
add_filter( 'semrs_module_files', 'semrs_pro_module_files' );

/**
 * Labels for the Modules screen.
 *
 * @param array $mods Modules.
 * @return array
 */
function semrs_pro_module_labels( $mods ) {
	return array_merge(
		$mods,
		array(
			'theme_pro' => array( __( 'Theme Pro', 'semrs-pro' ), __( 'Mega menu, dark mode switch, automatic table of contents, extra section patterns and white label (needs the SEMRS theme; the header builder, sticky header, author box and related posts are free in the theme).', 'semrs-pro' ), 'pro' ),
			'redirects' => array( __( 'Redirects & 404 log', 'semrs-pro' ), __( '301/302/307/410 redirects with wildcards, loop protection, 404 log with one-click redirect, Rank Math redirect import.', 'semrs-pro' ), 'pro' ),
			'local'     => array( __( 'Local SEO (multi-location)', 'semrs-pro' ), __( 'Locations with address, phone, hours, map and service areas; LocalBusiness schema per location; [semrs_locations] directory and [semrs_location] card shortcodes.', 'semrs-pro' ), 'pro' ),
			'indexnow'  => array( __( 'IndexNow', 'semrs-pro' ), __( 'Notifies Bing, Yandex, Seznam and other IndexNow search engines instantly when you publish, update or delete content.', 'semrs-pro' ), 'pro' ),
			'image_seo' => array( __( 'Image SEO', 'semrs-pro' ), __( 'Fills missing alt text and titles from clean file names on upload, and adds missing alt text to images in content.', 'semrs-pro' ), 'pro' ),
			'layouts'   => array( __( 'Site Layouts', 'semrs-pro' ), __( 'Show any block content at a theme position with display rules (custom hooks).', 'semrs-pro' ), 'pro' ),
			'proof'     => array( __( 'Proof', 'semrs-pro' ), __( 'Case Studies, Portfolio and Testimonials with permission gates.', 'semrs-pro' ), 'pro' ),
			'leads'     => array( __( 'Lead tracking', 'semrs-pro' ), __( 'Contact Form 7 → GA4 generate_lead → thank-you page.', 'semrs-pro' ), 'pro' ),
			'blocks_pro' => array( __( 'Pro Blocks', 'semrs-pro' ), __( 'Slider / carousel, animated counter, 3D model viewer and scroll animations on every block (needs the free SEMRS Blocks plugin).', 'semrs-pro' ), 'pro' ),
			'conditions' => array( __( 'Display conditions', 'semrs-pro' ), __( 'Show any block only to logged-in or logged-out visitors, certain roles, between dates or on certain pages.', 'semrs-pro' ), 'pro' ),
			'popups'     => array( __( 'Popups', 'semrs-pro' ), __( 'Popups built with the block editor: centred, bottom bar or slide-in; delay, scroll, exit-intent or click triggers; frequency and page targeting. Phone-friendly and ad-free by design.', 'semrs-pro' ), 'pro' ),
			'languages'  => array( __( 'Languages', 'semrs-pro' ), __( 'Language switcher with linked translations, hreflang tags and the right html lang; optional Google Translate for untranslated pages. Works with Polylang and WPML.', 'semrs-pro' ), 'pro' ),
			'templates_pro' => array( __( 'Pro starter sites', 'semrs-pro' ), __( 'Extra starter sites for the SEMRS templates library (Consultant, Clinic) with Pro sections: services in tabs and a latest-articles slider.', 'semrs-pro' ), 'pro' ),
			'seo_bulk'   => array( __( 'Bulk SEO editor', 'semrs-pro' ), __( 'Edit SEO titles, meta descriptions, focus keywords and noindex for many posts on one screen, with length counters and filters (needs the SEO module).', 'semrs-pro' ), 'pro' ),
			'speed_pro'  => array( __( 'Speed Pro', 'semrs-pro' ), __( 'Delay analytics, pixel and chat scripts until the visitor interacts, preload the page cache in the background, and serve files from a CDN (needs the Speed module).', 'semrs-pro' ), 'pro' ),
			'ads_pro'    => array( __( 'Ads Pro', 'semrs-pro' ), __( 'More placements (repeat in content, between posts in lists), targeting by category, post age and dates, A/B rotation, click protection, a polite ad-blocker message and per-page position switches (needs the Ads module).', 'semrs-pro' ), 'pro' ),
		)
	);
}
add_filter( 'semrs_modules', 'semrs_pro_module_labels' );

if ( is_admin() ) {
	require SEMRS_PRO_DIR . 'includes/license.php';
}

/**
 * Notice when SEMRS Core is missing (WordPress 6.5+ also enforces this).
 */
function semrs_pro_needs_core() {
	if ( ! function_exists( 'semrs_core_load_modules' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'SEMRS Pro needs the free SEMRS Core plugin. Please install and activate it.', 'semrs-pro' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'semrs_pro_needs_core' );

/**
 * Activation: flush rewrites on the next request (post types, IndexNow key URL).
 */
function semrs_pro_activate() {
	update_option( 'semrs_core_flush', 1 );
}
register_activation_hook( __FILE__, 'semrs_pro_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( __DIR__ . '/includes/semrs-updater.php' ) ) {
	require_once __DIR__ . '/includes/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'plugin',
			'slug'    => 'semrs-pro',
			'file'    => __FILE__,
			'version' => SEMRS_PRO_VERSION,
			'name'    => 'SEMRS Pro',
		)
	);
}
