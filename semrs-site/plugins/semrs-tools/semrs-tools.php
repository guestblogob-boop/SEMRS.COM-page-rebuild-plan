<?php
/**
 * Plugin Name:       SEMRS Tools
 * Plugin URI:        https://semrs.com/tools/
 * Description:       Free SEO tools for any WordPress site: SERP preview, meta checker, robots.txt, redirect checker, schema generators, word counter, Google review link, llms.txt, UTM builder, image compressor, SEO ROI calculator. Each tool loads only its own small script. Shortcode: [semrs_tool id="…"], directory: [semrs_tools].
 * Version:           1.3.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Update URI:        https://semrs.com/updates/semrs-tools
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-tools
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_TOOLS_VERSION', '1.3.0' );
define( 'SEMRS_TOOLS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_TOOLS_URL', plugin_dir_url( __FILE__ ) );

require SEMRS_TOOLS_DIR . 'includes/registry.php';
require SEMRS_TOOLS_DIR . 'includes/render.php';
require SEMRS_TOOLS_DIR . 'includes/rest.php';
if ( is_admin() ) {
	require SEMRS_TOOLS_DIR . 'includes/admin.php';
}

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( __DIR__ . '/includes/semrs-updater.php' ) ) {
	require_once __DIR__ . '/includes/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'plugin',
			'slug'    => 'semrs-tools',
			'file'    => __FILE__,
			'version' => SEMRS_TOOLS_VERSION,
			'name'    => 'SEMRS Tools',
		)
	);
}
