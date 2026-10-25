<?php
/**
 * Plugin Name:       SEMRS Blocks
 * Plugin URI:        https://semrs.com/
 * Description:       Free blocks for any theme (SEMRS, Astra, GeneratePress, Kadence…): SVG icons, icon lists, fancy headings, post grids and lists, countdown timer, pricing and service cards, contact form with country picker, and a free-media sites directory. SEMRS Pro adds sliders, carousels, 3D models, counters, scroll animations and more.
 * Version:           1.3.6
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Update URI:        https://semrs.com/updates/semrs-blocks
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-blocks
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

// WP Consent API: stores only functional data (preferences, saved inputs) and loads no trackers itself.
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

define( 'SEMRS_BLOCKS_VERSION', '1.3.6' );
define( 'SEMRS_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require SEMRS_BLOCKS_DIR . 'includes/icons.php';
require SEMRS_BLOCKS_DIR . 'includes/framework.php';
require SEMRS_BLOCKS_DIR . 'includes/blocks-free.php';
require SEMRS_BLOCKS_DIR . 'includes/blocks-layout.php';
require SEMRS_BLOCKS_DIR . 'includes/blocks-showcase.php';
require SEMRS_BLOCKS_DIR . 'includes/blocks-product.php';
require SEMRS_BLOCKS_DIR . 'includes/visibility.php';
require SEMRS_BLOCKS_DIR . 'includes/contact.php';
require SEMRS_BLOCKS_DIR . 'includes/templates.php';
require SEMRS_BLOCKS_DIR . 'includes/templates-preview.php';
if ( is_admin() ) {
	require SEMRS_BLOCKS_DIR . 'includes/admin.php';
}

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( __DIR__ . '/includes/semrs-updater.php' ) ) {
	require_once __DIR__ . '/includes/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'plugin',
			'slug'    => 'semrs-blocks',
			'file'    => __FILE__,
			'version' => SEMRS_BLOCKS_VERSION,
			'name'    => 'SEMRS Blocks',
		)
	);
}
