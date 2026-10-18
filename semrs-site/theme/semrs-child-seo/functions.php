<?php
/**
 * SEMRS Child SEO — brand layer for the SEMRS theme.
 *
 * @package semrs-child-seo
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_CHILD_VERSION', '1.2.4' );

/**
 * Read a child setting (Customizer → SEMRS Theme → Contact & conversion).
 *
 * @param string $key     Key without prefix.
 * @param mixed  $default Default.
 * @return mixed
 */
function semrs_opt( $key, $default = null ) {
	return get_theme_mod( 'semrs_' . $key, $default );
}

/**
 * True on /tools/ and below.
 *
 * @return bool
 */
function semrs_is_tools_page() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	return is_string( $path ) && 0 === strpos( trailingslashit( $path ), '/tools/' );
}

/**
 * Child stylesheet after the parent's.
 */
function semrs_child_assets() {
	wp_enqueue_style( 'semrs-child-seo', get_stylesheet_uri(), array( 'semrs' ), SEMRS_CHILD_VERSION );
}
add_action( 'wp_enqueue_scripts', 'semrs_child_assets', 20 );

require get_stylesheet_directory() . '/inc/customizer.php';
require get_stylesheet_directory() . '/inc/conversion.php';

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( get_stylesheet_directory() . '/inc/semrs-updater.php' ) ) {
	require_once get_stylesheet_directory() . '/inc/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'theme',
			'slug'    => 'semrs-child-seo',
			'file'    => get_stylesheet_directory() . '/style.css',
			'version' => SEMRS_CHILD_VERSION,
			'name'    => 'SEMRS Child SEO',
		)
	);
}
