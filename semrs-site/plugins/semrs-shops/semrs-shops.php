<?php
/**
 * Plugin Name:       SEMRS Shops
 * Plugin URI:        https://semrs.com/
 * Description:       Ready-made online shops for SEMRS Blocks: a Digital Downloads Shop (Christmas SVGs, printables, gift tags, cards, planners, workbooks, coloring pages, shirt, hat, cup, wall and car decal designs, pet and animal designs, custom family and pet portraits, personalised prints, Canva and Notion templates) and a Crochet Pattern Shop (amigurumi animals and birds, pet accessories, blankets, wearables, home decor, seasonal, bags and car accessories, with a crochet help page). One-click starter sites and editor patterns with colour, size, format and licence options, personalisation and bundles.
 * Version:           1.0.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  semrs-blocks
 * Update URI:        https://semrs.com/updates/semrs-shops
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-shops
 *
 * @package semrs-shops
 */

defined( 'ABSPATH' ) || exit;

// WP Consent API: stores only functional data (preferences, saved inputs) and loads no trackers itself.
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

define( 'SEMRS_SHOPS_VERSION', '1.0.2' );
define( 'SEMRS_SHOPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_SHOPS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load once SEMRS Blocks (Product block and templates library) is available.
 */
function semrs_shops_load() {
	if ( ! function_exists( 'semrs_blocks_template_fill' ) || ! function_exists( 'semrs_blocks_product_engine' ) ) {
		add_action(
			'admin_notices',
			function () {
				if ( current_user_can( 'activate_plugins' ) ) {
					echo '<div class="notice notice-warning"><p>' . esc_html__( 'SEMRS Shops needs the free SEMRS Blocks plugin (version 1.1 or newer). Please install and activate it.', 'semrs-shops' ) . '</p></div>';
				}
			}
		);
		return;
	}
	require SEMRS_SHOPS_DIR . 'includes/shops.php';
	if ( is_admin() ) {
		require SEMRS_SHOPS_DIR . 'includes/exports.php';
	}
}
add_action( 'plugins_loaded', 'semrs_shops_load', 20 );

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( __DIR__ . '/includes/semrs-updater.php' ) ) {
	require_once __DIR__ . '/includes/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'plugin',
			'slug'    => 'semrs-shops',
			'file'    => __FILE__,
			'version' => SEMRS_SHOPS_VERSION,
			'name'    => 'SEMRS Shops',
		)
	);
}
