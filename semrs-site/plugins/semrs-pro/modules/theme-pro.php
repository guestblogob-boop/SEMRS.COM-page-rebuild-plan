<?php
/**
 * Theme Pro: premium features for the SEMRS theme: mega menu, table of
 * contents, extra patterns and white label. Runs only when the SEMRS theme
 * (or a child) is active. Header/footer builder, sticky and transparent
 * header, progress bar, author box and related posts are free in the theme.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( 'semrs' !== get_template() ) {
	return;
}

/**
 * Pro settings inside the theme's Customizer panel.
 *
 * @param array $map Theme settings.
 * @return array
 */
function semrs_pro_theme_settings( $map ) {
	return array_merge(
		$map,
		array(
			'toc'               => array( 'blog', 'checkbox', __( 'Table of contents on posts, 3+ headings (Pro)', 'semrs-pro' ), true ),
			'white_label'       => array( 'footer', 'text', __( 'White label: theme name shown in Appearance → Themes (Pro)', 'semrs-pro' ), '' ),
		)
	);
}
add_filter( 'semrs_theme_settings', 'semrs_pro_theme_settings' );

/**
 * Read a theme option (works before the theme's helper exists).
 *
 * @param string $key Key.
 * @return mixed
 */
function semrs_pro_opt( $key ) {
	return function_exists( 'semrs_theme_opt' ) ? semrs_theme_opt( $key ) : get_theme_mod( 'semrs_theme_' . $key );
}

/**
 * Styles for the Pro modules.
 */
function semrs_pro_theme_assets() {
	wp_enqueue_style( 'semrs-pro', SEMRS_PRO_URL . 'assets/pro.css', array( 'semrs' ), SEMRS_PRO_VERSION );
}
add_action( 'wp_enqueue_scripts', 'semrs_pro_theme_assets', 15 );

/**
 * Mega menu: top item with class "semrs-mega" gets 2–4 columns.
 *
 * @param WP_Post[] $items Menu items.
 * @return WP_Post[]
 */
function semrs_pro_mega_classes( $items ) {
	$children = array();
	foreach ( $items as $item ) {
		$p              = (int) $item->menu_item_parent;
		$children[ $p ] = isset( $children[ $p ] ) ? $children[ $p ] + 1 : 1;
	}
	foreach ( $items as $item ) {
		if ( 0 === (int) $item->menu_item_parent && in_array( 'semrs-mega', (array) $item->classes, true ) ) {
			$n               = isset( $children[ (int) $item->ID ] ) ? $children[ (int) $item->ID ] : 0;
			$item->classes[] = 'semrs-mega-cols-' . max( 2, min( 4, (int) ceil( $n / 4 ) ) );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'semrs_pro_mega_classes' );

/**
 * Main single-post content being rendered?
 *
 * @return bool
 */
function semrs_pro_is_post_content() {
	return is_singular( 'post' ) && in_the_loop() && is_main_query();
}

/**
 * Table of contents from H2/H3, with ids added to headings.
 *
 * @param string $content Rendered content.
 * @return string
 */
function semrs_pro_toc( $content ) {
	if ( ! semrs_pro_is_post_content() || ! semrs_pro_opt( 'toc' ) || false !== strpos( $content, 'rank-math-toc' ) || false !== strpos( $content, 'sp-toc' ) || false !== strpos( $content, 'semrs-toc' ) ) {
		return $content;
	}
	if ( ! preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) || count( $matches ) < 3 ) {
		return $content;
	}
	$used  = array();
	$items = '';
	foreach ( $matches as $m ) {
		$level = (int) $m[1];
		$text  = wp_strip_all_tags( $m[3] );
		if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $m[2], $idm ) ) {
			$id = $idm[1];
		} else {
			$base = sanitize_title( $text );
			$base = '' === $base ? 'section' : $base;
			$id   = $base;
			for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
				$id = $base . '-' . $n;
			}
			$new     = '<h' . $level . $m[2] . ' id="' . esc_attr( $id ) . '">' . $m[3] . '</h' . $level . '>';
			$at      = strpos( $content, $m[0] );
			$content = false === $at ? $content : substr_replace( $content, $new, $at, strlen( $m[0] ) );
		}
		$used[ $id ] = true;
		$items      .= sprintf( '<li class="sp-toc-l%d"><a href="#%s">%s</a></li>', $level, esc_attr( $id ), esc_html( $text ) );
	}
	$toc = '<details class="sp-toc" open><summary>' . esc_html__( 'On this page', 'semrs-pro' ) . '</summary><ol>' . $items . '</ol></details>';
	$pos = strpos( $content, '</p>' );
	return false === $pos ? $toc . $content : substr_replace( $content, '</p>' . $toc, $pos, 4 );
}
add_filter( 'the_content', 'semrs_pro_toc', 20 );

/**
 * White label: rename the theme in Appearance → Themes.
 *
 * @param array $themes Prepared themes for JS.
 * @return array
 */
function semrs_pro_white_label( $themes ) {
	$name = trim( (string) semrs_pro_opt( 'white_label' ) );
	if ( $name && isset( $themes['semrs'] ) ) {
		$themes['semrs']['name']        = $name;
		$themes['semrs']['author']      = $name;
		$themes['semrs']['authorAndUri'] = esc_html( $name );
	}
	return $themes;
}
add_filter( 'wp_prepare_themes_for_js', 'semrs_pro_white_label' );

require SEMRS_PRO_DIR . 'modules/patterns.php';
require SEMRS_PRO_DIR . 'modules/dark-mode.php';
