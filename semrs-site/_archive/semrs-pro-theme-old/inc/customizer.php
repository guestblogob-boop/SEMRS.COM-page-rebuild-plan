<?php
/**
 * Customizer → "SEMRS Pro" panel and the inline CSS it produces.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * All settings: key => [section, type, label, default, extra].
 *
 * @return array
 */
function semrs_pro_settings_map() {
	return array(
		// Colours.
		'color_primary'    => array( 'colors', 'color', __( 'Primary (headings, header text, footer)', 'semrs-pro' ), '#0b1f3a' ),
		'color_accent'     => array( 'colors', 'color', __( 'Accent (buttons, highlights)', 'semrs-pro' ), '#d4a72c' ),
		'color_on_accent'  => array( 'colors', 'color', __( 'Text on accent buttons', 'semrs-pro' ), '#0b1f3a' ),
		'color_text'       => array( 'colors', 'color', __( 'Body text', 'semrs-pro' ), '#1f2937' ),
		'color_link'       => array( 'colors', 'color', __( 'Links', 'semrs-pro' ), '#1e3a66' ),
		'color_bg'         => array( 'colors', 'color', __( 'Background', 'semrs-pro' ), '#ffffff' ),
		// Typography & size.
		'font'             => array( 'type', 'select', __( 'Font', 'semrs-pro' ), 'system', array( 'system' => __( 'System fonts (fastest)', 'semrs-pro' ), 'inter' => __( 'Inter (self-hosted, add fonts/InterVariable.woff2)', 'semrs-pro' ) ) ),
		'font_size'        => array( 'type', 'number', __( 'Base font size (px)', 'semrs-pro' ), 17, array( 'min' => 14, 'max' => 21 ) ),
		'container'        => array( 'type', 'number', __( 'Container width (px)', 'semrs-pro' ), 1200, array( 'min' => 960, 'max' => 1600 ) ),
		// Layout.
		'layout_post'      => array( 'layout', 'select', __( 'Single posts', 'semrs-pro' ), 'right', 'layouts' ),
		'layout_page'      => array( 'layout', 'select', __( 'Pages', 'semrs-pro' ), 'none', 'layouts' ),
		'layout_archive'   => array( 'layout', 'select', __( 'Blog & archives', 'semrs-pro' ), 'right', 'layouts' ),
		'archive_style'    => array( 'layout', 'select', __( 'Archive style', 'semrs-pro' ), 'grid', array( 'grid' => __( 'Card grid', 'semrs-pro' ), 'list' => __( 'List', 'semrs-pro' ) ) ),
		'excerpt_length'   => array( 'layout', 'number', __( 'Excerpt length (words)', 'semrs-pro' ), 24, array( 'min' => 10, 'max' => 80 ) ),
		'sticky_sidebar'   => array( 'layout', 'checkbox', __( 'Sticky sidebar (last widget stays in view)', 'semrs-pro' ), true ),
		// Header.
		'sticky_header'    => array( 'header', 'checkbox', __( 'Sticky header', 'semrs-pro' ), true ),
		'transparent_front' => array( 'header', 'checkbox', __( 'Transparent header on the front page', 'semrs-pro' ), false ),
		'show_tagline'     => array( 'header', 'checkbox', __( 'Show tagline under the site title', 'semrs-pro' ), false ),
		'cta_text'         => array( 'header', 'text', __( 'Header button text', 'semrs-pro' ), '' ),
		'cta_url'          => array( 'header', 'url', __( 'Header button link', 'semrs-pro' ), '' ),
		// Blog.
		'toc'              => array( 'blog', 'checkbox', __( 'Table of contents on posts (3+ headings)', 'semrs-pro' ), true ),
		'progress'         => array( 'blog', 'checkbox', __( 'Reading progress bar', 'semrs-pro' ), true ),
		'single_thumb'     => array( 'blog', 'checkbox', __( 'Featured image on single posts', 'semrs-pro' ), true ),
		'updated_date'     => array( 'blog', 'checkbox', __( 'Show "Updated on" date', 'semrs-pro' ), true ),
		'author_box'       => array( 'blog', 'checkbox', __( 'Author box', 'semrs-pro' ), true ),
		'related'          => array( 'blog', 'checkbox', __( 'Related posts', 'semrs-pro' ), true ),
		'post_nav'         => array( 'blog', 'checkbox', __( 'Previous / next post links', 'semrs-pro' ), true ),
		// Footer.
		'footer_cols'      => array( 'footer', 'select', __( 'Footer widget columns', 'semrs-pro' ), '4', array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ) ),
		'copyright'        => array( 'footer', 'text', __( 'Copyright text ({year}, {site})', 'semrs-pro' ), '© {year} {site}. All rights reserved.' ),
		'footer_note'      => array( 'footer', 'text', __( 'Note line under the footer (disclaimer)', 'semrs-pro' ), '' ),
		'to_top'           => array( 'footer', 'checkbox', __( 'Back-to-top button', 'semrs-pro' ), true ),
	);
}

/**
 * Read a theme option.
 *
 * @param string $key Key without prefix.
 * @return mixed
 */
function semrs_pro_opt( $key ) {
	$map = semrs_pro_settings_map();
	return get_theme_mod( 'semrs_pro_' . $key, isset( $map[ $key ] ) ? $map[ $key ][3] : null );
}

/**
 * Register panel, sections, settings, controls.
 *
 * @param WP_Customize_Manager $wpc Customizer.
 */
function semrs_pro_customize_register( $wpc ) {
	$wpc->add_panel( 'semrs_pro', array( 'title' => __( 'SEMRS Pro', 'semrs-pro' ), 'priority' => 1 ) );
	$sections = array(
		'colors' => __( 'Colours', 'semrs-pro' ),
		'type'   => __( 'Typography & width', 'semrs-pro' ),
		'layout' => __( 'Layout & sidebar', 'semrs-pro' ),
		'header' => __( 'Header', 'semrs-pro' ),
		'blog'   => __( 'Blog & posts', 'semrs-pro' ),
		'footer' => __( 'Footer', 'semrs-pro' ),
	);
	foreach ( $sections as $id => $title ) {
		$wpc->add_section( 'semrs_pro_' . $id, array( 'title' => $title, 'panel' => 'semrs_pro' ) );
	}
	$layouts = array(
		'right' => __( 'Right sidebar', 'semrs-pro' ),
		'left'  => __( 'Left sidebar', 'semrs-pro' ),
		'none'  => __( 'No sidebar', 'semrs-pro' ),
	);

	foreach ( semrs_pro_settings_map() as $key => $cfg ) {
		list( $section, $type, $label, $default ) = $cfg;
		$extra    = isset( $cfg[4] ) ? $cfg[4] : array();
		$sanitize = 'sanitize_text_field';
		if ( 'color' === $type ) {
			$sanitize = 'sanitize_hex_color';
		} elseif ( 'checkbox' === $type ) {
			$sanitize = 'rest_sanitize_boolean';
		} elseif ( 'number' === $type ) {
			$sanitize = 'absint';
		} elseif ( 'url' === $type ) {
			$sanitize = 'esc_url_raw';
		} elseif ( 'select' === $type ) {
			$choices  = 'layouts' === $extra ? $layouts : $extra;
			$sanitize = function ( $v ) use ( $choices, $default ) {
				return array_key_exists( $v, $choices ) ? $v : $default;
			};
		}
		$wpc->add_setting( 'semrs_pro_' . $key, array( 'default' => $default, 'sanitize_callback' => $sanitize ) );

		$args = array( 'label' => $label, 'section' => 'semrs_pro_' . $section );
		if ( 'color' === $type ) {
			$wpc->add_control( new WP_Customize_Color_Control( $wpc, 'semrs_pro_' . $key, $args ) );
			continue;
		}
		$args['type'] = 'url' === $type ? 'url' : $type;
		if ( 'select' === $type ) {
			$args['choices'] = 'layouts' === $extra ? $layouts : $extra;
		}
		if ( 'number' === $type ) {
			$args['input_attrs'] = $extra;
		}
		$wpc->add_control( 'semrs_pro_' . $key, $args );
	}
}
add_action( 'customize_register', 'semrs_pro_customize_register' );

/**
 * CSS variables for settings that differ from the defaults (nothing extra
 * is printed for an unchanged theme).
 *
 * @return string
 */
function semrs_pro_customizer_css() {
	$map  = semrs_pro_settings_map();
	$vars = array(
		'color_primary'   => '--sp-primary',
		'color_accent'    => '--sp-accent',
		'color_on_accent' => '--sp-on-accent',
		'color_text'      => '--sp-text',
		'color_link'      => '--sp-link',
		'color_bg'        => '--sp-bg',
	);
	$css = '';
	foreach ( $vars as $key => $var ) {
		$val = semrs_pro_opt( $key );
		if ( $val && strtolower( $val ) !== $map[ $key ][3] ) {
			$css .= $var . ':' . $val . ';';
		}
	}
	if ( (int) semrs_pro_opt( 'font_size' ) !== 17 ) {
		$css .= '--sp-font-size:' . (int) semrs_pro_opt( 'font_size' ) . 'px;';
	}
	if ( (int) semrs_pro_opt( 'container' ) !== 1200 ) {
		$css .= '--sp-container:' . (int) semrs_pro_opt( 'container' ) . 'px;';
	}
	if ( 'inter' === semrs_pro_opt( 'font' ) ) {
		$css .= '--sp-font:"Inter",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;';
	}
	if ( '4' !== (string) semrs_pro_opt( 'footer_cols' ) ) {
		$css .= '--sp-footer-cols:' . (int) semrs_pro_opt( 'footer_cols' ) . ';';
	}
	$out = $css ? ':root{' . $css . '}' : '';

	if ( 'inter' === semrs_pro_opt( 'font' ) ) {
		$font = semrs_pro_font_url();
		if ( $font ) {
			$out = "@font-face{font-family:'Inter';font-style:normal;font-weight:100 900;font-display:swap;src:url('" . esc_url( $font ) . "') format('woff2');}" . $out;
		}
	}
	return $out;
}

/**
 * Inter font file URL (child theme first, then parent), or ''.
 *
 * @return string
 */
function semrs_pro_font_url() {
	foreach ( array( array( get_stylesheet_directory(), get_stylesheet_directory_uri() ), array( SEMRS_PRO_DIR, SEMRS_PRO_URI ) ) as $loc ) {
		if ( file_exists( $loc[0] . '/fonts/InterVariable.woff2' ) ) {
			return $loc[1] . '/fonts/InterVariable.woff2';
		}
	}
	return '';
}

/**
 * Preload the font when Inter is chosen.
 */
function semrs_pro_preload_font() {
	if ( 'inter' === semrs_pro_opt( 'font' ) && semrs_pro_font_url() ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( semrs_pro_font_url() ) );
	}
}
add_action( 'wp_head', 'semrs_pro_preload_font', 1 );
