<?php
/**
 * Plugin Name:       SEMRS Site (semrs.com only)
 * Description:       semrs.com-only additions that are never given to clients: the SEMRS Store (products, collection and product pages, pricing) and SEMRS Pro sales page installer, the Keyword Ideas Generator tool, the "Hero with photo and keyword tool" pattern and its photo, and the web design portfolio hub ([semrs_portfolio]). Needs SEMRS Tools for the tool.
 * Version:           1.2.23
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            SEMRS
 * License:           GPL-2.0-or-later
 * Text Domain:       semrs-site
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

// WP Consent API: stores only functional data (preferences, saved inputs) and loads no trackers itself.
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

// semrs.com issues the Pro licenses, so its own SEMRS SEO Pro items are always unlocked.
add_filter( 'semrs_seo_pro_unlocked', '__return_true' );

// House rule for AI-written SEO text on semrs.com.
add_filter(
	'semrs_ai_rules',
	function ( $rules ) {
		$rules[] = 'Never mention the brand Semrush.';
		return $rules;
	}
);

define( 'SEMRS_SITE_VERSION', '1.2.23' );
define( 'SEMRS_SITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEMRS_SITE_URL', plugin_dir_url( __FILE__ ) );

require SEMRS_SITE_DIR . 'includes/store.php';
require SEMRS_SITE_DIR . 'includes/portfolio.php';
require SEMRS_SITE_DIR . 'includes/logo.php';
require SEMRS_SITE_DIR . 'includes/avatar.php';
require SEMRS_SITE_DIR . 'includes/icons.php';
require SEMRS_SITE_DIR . 'includes/legal.php';
if ( is_admin() ) {
	require SEMRS_SITE_DIR . 'includes/store-install.php';
}

// Store addresses (/store/) need fresh rewrite rules after activation.
register_activation_hook(
	__FILE__,
	function () {
		update_option( 'semrs_site_flush', 1 );
	}
);
add_action(
	'init',
	function () {
		if ( get_option( 'semrs_site_flush' ) ) {
			delete_option( 'semrs_site_flush' );
			flush_rewrite_rules();
		}
	},
	99
);

/**
 * Older page content put role="img" on <figure> elements that have a caption, which ARIA does not
 * allow (Lighthouse: "ARIA roles on compatible elements", "accessibility tree not well-formed").
 * Move the role and label onto the SVG inside instead.
 *
 * @param string $html Post content.
 * @return string
 */
function semrs_site_figure_roles( $html ) {
	if ( false === strpos( $html, 'role="img"' ) ) {
		return $html;
	}
	return preg_replace( '#<figure class="(semrs-[^"]+)" role="img" aria-label="([^"]*)">(\s*)<svg #', '<figure class="$1">$3<svg role="img" aria-label="$2" ', $html );
}
add_filter( 'the_content', 'semrs_site_figure_roles', 20 );

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
			$made  = null;
			$store = null;
			if ( isset( $_POST['semrs_site_install'] ) && check_admin_referer( 'semrs_site_install' ) ) {
				$made = semrs_site_install_pages();
			}
			if ( isset( $_POST['semrs_store_install'] ) && check_admin_referer( 'semrs_site_install' ) ) {
				$store = semrs_store_install();
			}
			$legal = null;
			if ( isset( $_POST['semrs_legal_install'] ) && check_admin_referer( 'semrs_site_install' ) ) {
				$legal = semrs_legal_install();
			}
			$portfolio = null;
			if ( isset( $_POST['semrs_portfolio_install'] ) && check_admin_referer( 'semrs_site_install' ) ) {
				$portfolio = semrs_portfolio_install();
			}
			echo '<div class="wrap"><h1>' . esc_html__( 'semrs.com pages', 'semrs-site' ) . '</h1>';
			if ( null !== $made ) {
				/* translators: %d: number of pages */
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( '%d draft page(s) created. Review, then publish.', 'semrs-site' ), $made ) ) . '</p></div>';
			}
			if ( null !== $store ) {
				/* translators: 1: products, 2: pages */
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( '%1$d draft product(s) and %2$d draft page(s) created. Check the prices and add your checkout links under Store → All products before publishing.', 'semrs-site' ), $store['products'], $store['pages'] ) ) . '</p></div>';
			}
			if ( null !== $legal ) {
				/* translators: 1: pages created, 2: pages updated */
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Legal pages: %1$d created, %2$d updated and published. The About page, SEMRS assets page and site tagline now describe both services and products.', 'semrs-site' ), $legal['created'], $legal['updated'] ) ) . '</p></div>';
			}
			if ( null !== $portfolio ) {
				echo '<div class="notice notice-' . ( $portfolio ? 'success' : 'info' ) . '"><p>' . ( $portfolio ? esc_html__( 'Portfolio page created as a draft at /portfolio/. Review, then publish.', 'semrs-site' ) . ' <a href="' . esc_url( get_edit_post_link( $portfolio ) ) . '">' . esc_html__( 'Edit', 'semrs-site' ) . '</a>' : esc_html__( 'A page at /portfolio/ already exists, so nothing was changed.', 'semrs-site' ) ) . '</p></div>';
			}
			echo '<form method="post">';
			wp_nonce_field( 'semrs_site_install' );
			echo '<h2>' . esc_html__( 'Tools', 'semrs-site' ) . '</h2><p>' . esc_html__( 'Creates the Keyword Ideas Generator page as a draft under /tools/. Existing pages are never changed.', 'semrs-site' ) . '</p>';
			submit_button( __( 'Create missing pages as drafts', 'semrs-site' ), 'primary', 'semrs_site_install' );
			echo '<h2>' . esc_html__( 'Store and SEMRS Pro sales page', 'semrs-site' ) . '</h2><p>' . esc_html__( 'Creates the store categories, the six SEMRS products (with desktop and phone screenshots) and the SEMRS Pro sales page at /semrs-pro/, all as drafts. SEMRS Pro prices and checkout links come from SEMRS Updates → Sales & prices (add-ons and bundles); without a checkout link the buttons ask for an invoice.', 'semrs-site' ) . '</p>';
			submit_button( __( 'Create store as drafts', 'semrs-site' ), 'secondary', 'semrs_store_install' );
			semrs_legal_admin_box();
			semrs_portfolio_admin_box();
			echo '</form></div>';
		}
	);
}
add_action( 'admin_menu', 'semrs_site_menu' );

/**
 * Plugins screen: Settings link (semrs.com pages, store and portfolio).
 *
 * @param string[] $links Links.
 * @return string[]
 */
function semrs_site_action_links( $links ) {
	$url = menu_page_url( 'semrs-site', false );
	if ( $url ) {
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'semrs-site' ) . '</a>' );
	}
	return $links;
}
add_filter( 'plugin_action_links_semrs-site/semrs-site.php', 'semrs_site_action_links' );
