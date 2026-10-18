<?php
/**
 * Plugin Name:       SEMRS Site (semrs.com only)
 * Description:       semrs.com-only additions that are never given to clients: the Keyword Ideas Generator tool, the "Hero with photo and keyword tool" pattern and its photo. Needs SEMRS Tools for the tool.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-site
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_SITE_VERSION', '1.0.0' );
define( 'SEMRS_SITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_SITE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Add the Keyword Ideas Generator to SEMRS Tools (directory, shortcode).
 *
 * @param array $tools Tools.
 * @return array
 */
function semrs_site_tools( $tools ) {
	$tools['keyword-ideas-generator'] = array( __( 'Keyword Ideas Generator', 'semrs-site' ), 'content-keywords', __( '50+ keyword ideas grouped by search intent, with local ideas for your city.', 'semrs-site' ), 'browser' );
	return $tools;
}
add_filter( 'semrs_tools_list', 'semrs_site_tools' );

/**
 * The tool's script lives in this plugin, not in SEMRS Tools.
 */
function semrs_site_scripts() {
	if ( wp_script_is( 'semrs-tool-keyword-ideas-generator', 'registered' ) ) {
		wp_deregister_script( 'semrs-tool-keyword-ideas-generator' );
	}
	wp_register_script( 'semrs-tool-keyword-ideas-generator', SEMRS_SITE_URL . 'assets/keyword-ideas-generator.js', array( 'semrs-tools-core' ), SEMRS_SITE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_enqueue_style( 'semrs-site', SEMRS_SITE_URL . 'assets/site.css', array(), SEMRS_SITE_VERSION );
}
add_action( 'wp_enqueue_scripts', 'semrs_site_scripts', 6 );

/**
 * "Hero with photo and keyword tool" pattern.
 */
function semrs_site_patterns() {
	ob_start();
	include SEMRS_SITE_DIR . 'patterns/hero-photo.php';
	register_block_pattern(
		'semrs-site/hero-photo',
		array(
			'title'      => __( 'SEMRS: Hero with photo and keyword tool', 'semrs-site' ),
			'categories' => array( 'semrs', 'featured' ),
			'keywords'   => array( 'hero', 'photo', 'keyword', 'tool' ),
			'blockTypes' => array( 'core/group' ),
			'content'    => ob_get_clean(),
		)
	);
}
add_action( 'init', 'semrs_site_patterns', 20 );

/**
 * Tools → Install semrs.com pages: creates the Keyword Ideas Generator page
 * as a draft under /tools/ (skipped if it exists).
 *
 * @return int Pages created.
 */
function semrs_site_install_pages() {
	$pages = json_decode( (string) file_get_contents( SEMRS_SITE_DIR . 'content/pages.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$hub   = get_page_by_path( 'tools' );
	$made  = 0;
	foreach ( (array) $pages as $slug => $p ) {
		if ( get_page_by_path( 'tools/' . $slug ) || ( ! $hub && get_page_by_path( $slug ) ) ) {
			continue;
		}
		$id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $p['h1'],
				'post_name'    => $slug,
				'post_parent'  => $hub ? (int) $hub->ID : 0,
				'menu_order'   => (int) $p['order'],
				'post_content' => (string) file_get_contents( SEMRS_SITE_DIR . 'content/' . sanitize_file_name( $slug ) . '.html' ), // phpcs:ignore WordPress.WP.AlternativeFunctions
			)
		);
		if ( $id ) {
			$made++;
			if ( function_exists( 'semrs_tools_set_seo' ) ) {
				semrs_tools_set_seo( $id, $p );
			}
		}
	}
	return $made;
}

/**
 * Admin screen.
 */
function semrs_site_menu() {
	add_management_page(
		__( 'semrs.com pages', 'semrs-site' ),
		__( 'semrs.com pages', 'semrs-site' ),
		'manage_options',
		'semrs-site',
		function () {
			$made = null;
			if ( isset( $_POST['semrs_site_install'] ) && check_admin_referer( 'semrs_site_install' ) ) {
				$made = semrs_site_install_pages();
			}
			echo '<div class="wrap"><h1>' . esc_html__( 'semrs.com pages', 'semrs-site' ) . '</h1>';
			if ( null !== $made ) {
				/* translators: %d: number of pages */
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( '%d draft page(s) created. Review, then publish.', 'semrs-site' ), $made ) ) . '</p></div>';
			}
			echo '<p>' . esc_html__( 'Creates the Keyword Ideas Generator page as a draft under /tools/. Existing pages are never changed.', 'semrs-site' ) . '</p><form method="post">';
			wp_nonce_field( 'semrs_site_install' );
			submit_button( __( 'Create missing pages as drafts', 'semrs-site' ), 'primary', 'semrs_site_install' );
			echo '</form></div>';
		}
	);
}
add_action( 'admin_menu', 'semrs_site_menu' );
