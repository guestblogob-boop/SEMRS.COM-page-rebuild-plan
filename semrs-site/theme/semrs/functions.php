<?php
/**
 * SEMRS theme setup.
 *
 * Action hooks for child themes and plugins (in page order):
 *   wp_body_open, semrs_before_header, semrs_header_actions, semrs_after_header,
 *   semrs_before_content, semrs_content_top, semrs_before_entry_content,
 *   semrs_after_entry_content, semrs_after_entry, semrs_content_bottom,
 *   semrs_sidebar_top, semrs_sidebar_bottom, semrs_after_content,
 *   semrs_before_footer, semrs_footer_bottom, semrs_after_footer.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_THEME_VERSION', '1.3.16' );
define( 'SEMRS_THEME_DIR', get_template_directory() );
define( 'SEMRS_THEME_URI', get_template_directory_uri() );

if ( ! isset( $content_width ) ) {
	$content_width = 760; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}

/**
 * Theme supports, menus, image sizes.
 */
function semrs_theme_setup() {
	load_theme_textdomain( 'semrs', SEMRS_THEME_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 512,
			'width'       => 512,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_editor_style( 'style.css' );
	register_nav_menus(
		array(
			'primary'   => __( 'Primary menu', 'semrs' ),
			'secondary' => __( 'Secondary menu (top bar)', 'semrs' ),
			'footer'  => __( 'Footer menu', 'semrs' ),
		)
	);
	add_image_size( 'semrs-card', 768, 432, true );
}
add_action( 'after_setup_theme', 'semrs_theme_setup' );

/**
 * "SEMRS" pattern category. Theme patterns live in /patterns (auto-registered);
 * SEMRS Pro adds more.
 */
function semrs_theme_pattern_category() {
	register_block_pattern_category( 'semrs', array( 'label' => __( 'SEMRS sections', 'semrs' ) ) );
}
add_action( 'init', 'semrs_theme_pattern_category', 9 );

/**
 * Widget areas.
 */
function semrs_theme_widgets() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	);
	register_sidebar( array_merge( $common, array( 'name' => __( 'Sidebar', 'semrs' ), 'id' => 'sidebar-1', 'description' => __( 'Shown when a sidebar layout is chosen.', 'semrs' ) ) ) );
	for ( $i = 1; $i <= 4; $i++ ) {
		/* translators: %d: column number */
		register_sidebar( array_merge( $common, array( 'name' => sprintf( __( 'Footer column %d', 'semrs' ), $i ), 'id' => 'footer-' . $i ) ) );
	}
}
add_action( 'widgets_init', 'semrs_theme_widgets' );

/**
 * One stylesheet, one deferred script. Comment script only where needed.
 */
function semrs_theme_assets() {
	wp_enqueue_style( 'semrs', SEMRS_THEME_URI . '/style.css', array(), SEMRS_THEME_VERSION );
	$inline = semrs_theme_customizer_css();
	if ( $inline ) {
		wp_add_inline_style( 'semrs', $inline );
	}
	wp_enqueue_script( 'semrs', SEMRS_THEME_URI . '/assets/js/theme.js', array(), SEMRS_THEME_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'semrs_theme_assets' );

require SEMRS_THEME_DIR . '/inc/customizer.php';
require SEMRS_THEME_DIR . '/inc/template-tags.php';
require SEMRS_THEME_DIR . '/inc/header-builder.php';
require SEMRS_THEME_DIR . '/inc/features.php';
require SEMRS_THEME_DIR . '/inc/performance.php';
require SEMRS_THEME_DIR . '/inc/page-options.php';
require SEMRS_THEME_DIR . '/inc/action-bar.php';

// Updates from semrs.com (WordPress "Update available" notices). Not present in WordPress.org builds.
if ( file_exists( SEMRS_THEME_DIR . '/inc/semrs-updater.php' ) ) {
	require_once SEMRS_THEME_DIR . '/inc/semrs-updater.php';
	SEMRS_Updater_1::register(
		array(
			'type'    => 'theme',
			'slug'    => 'semrs',
			'file'    => SEMRS_THEME_DIR . '/style.css',
			'version' => SEMRS_THEME_VERSION,
			'name'    => 'SEMRS',
		)
	);
}
