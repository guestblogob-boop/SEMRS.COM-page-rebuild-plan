<?php
/**
 * SEMRS Pro — theme setup.
 *
 * Action hooks for child themes and plugins (in page order):
 *   wp_body_open, semrs_before_header, semrs_header_actions, semrs_after_header,
 *   semrs_before_content, semrs_content_top, semrs_before_entry_content,
 *   semrs_after_entry_content, semrs_after_entry, semrs_content_bottom,
 *   semrs_sidebar_top, semrs_sidebar_bottom, semrs_after_content,
 *   semrs_before_footer, semrs_footer_bottom, semrs_after_footer.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

define( 'SEMRS_PRO_VERSION', '1.0.0' );
define( 'SEMRS_PRO_DIR', get_template_directory() );
define( 'SEMRS_PRO_URI', get_template_directory_uri() );

if ( ! isset( $content_width ) ) {
	$content_width = 760; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}

/**
 * Theme supports, menus, image sizes.
 */
function semrs_pro_setup() {
	load_theme_textdomain( 'semrs-pro', SEMRS_PRO_DIR . '/languages' );
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
			'height'      => 96,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_editor_style( 'style.css' );
	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'semrs-pro' ),
			'footer'  => __( 'Footer menu', 'semrs-pro' ),
		)
	);
	add_image_size( 'semrs-card', 768, 432, true );
}
add_action( 'after_setup_theme', 'semrs_pro_setup' );

/**
 * Widget areas.
 */
function semrs_pro_widgets() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	);
	register_sidebar( array_merge( $common, array( 'name' => __( 'Sidebar', 'semrs-pro' ), 'id' => 'sidebar-1', 'description' => __( 'Shown when a sidebar layout is chosen.', 'semrs-pro' ) ) ) );
	for ( $i = 1; $i <= 4; $i++ ) {
		/* translators: %d: column number */
		register_sidebar( array_merge( $common, array( 'name' => sprintf( __( 'Footer column %d', 'semrs-pro' ), $i ), 'id' => 'footer-' . $i ) ) );
	}
}
add_action( 'widgets_init', 'semrs_pro_widgets' );

/**
 * One stylesheet, one deferred script. Comment script only where needed.
 */
function semrs_pro_assets() {
	wp_enqueue_style( 'semrs-pro', SEMRS_PRO_URI . '/style.css', array(), SEMRS_PRO_VERSION );
	$inline = semrs_pro_customizer_css();
	if ( $inline ) {
		wp_add_inline_style( 'semrs-pro', $inline );
	}
	wp_enqueue_script( 'semrs-pro', SEMRS_PRO_URI . '/assets/js/theme.js', array(), SEMRS_PRO_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'semrs_pro_assets' );

require SEMRS_PRO_DIR . '/inc/customizer.php';
require SEMRS_PRO_DIR . '/inc/template-tags.php';
require SEMRS_PRO_DIR . '/inc/features.php';
require SEMRS_PRO_DIR . '/inc/performance.php';
require SEMRS_PRO_DIR . '/inc/page-options.php';
