<?php
/**
 * Customizer → "SEMRS" panel and the inline CSS it produces.
 *
 * SEMRS Pro (plugin) adds its own settings through the
 * `semrs_theme_settings` and `semrs_theme_sections` filters.
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Social networks: key => [label, brand colour, SVG path (24×24 viewBox)].
 * Simplified marks drawn on a 24px grid, shown in each network's brand colour.
 *
 * @return array<string, array<int, string>>
 */
function semrs_theme_social_networks() {
	return apply_filters(
		'semrs_theme_social_networks',
		array(
			'facebook'  => array( 'Facebook', '#1877F2', 'M13.4 21v-7.3h2.5l.4-2.9h-2.9V9c0-.8.2-1.4 1.4-1.4h1.5V5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.1H8v2.9h2.4V21h3z' ),
			'x'         => array( 'X', '#000000', 'M17.2 4h2.9l-6.3 7.2L21.2 20h-5.8l-4.5-5.9L5.7 20H2.8l6.7-7.7L2.4 4h5.9l4.1 5.4L17.2 4zm-1 14.3h1.6L7.9 5.6H6.2l10 12.7z' ),
			'instagram' => array( 'Instagram', '#E4405F', 'M12 7.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2zM17 5.9a1.1 1.1 0 1 0 0 2.2 1.1 1.1 0 0 0 0-2.2zM12 3.6c2.7 0 3.1 0 4.1.1 2.8.1 4.1 1.4 4.2 4.2.1 1.1.1 1.4.1 4.1s0 3.1-.1 4.1c-.1 2.8-1.4 4.1-4.2 4.2-1 .1-1.4.1-4.1.1s-3.1 0-4.1-.1c-2.8-.1-4.1-1.4-4.2-4.2-.1-1-.1-1.4-.1-4.1s0-3.1.1-4.1C3.8 5.1 5.1 3.8 7.9 3.7c1-.1 1.4-.1 4.1-.1z' ),
			'linkedin'  => array( 'LinkedIn', '#0A66C2', 'M5 8.6h3V19H5V8.6zm1.5-4.8a1.7 1.7 0 1 1 0 3.5 1.7 1.7 0 0 1 0-3.5zM9.9 8.6h2.9V10h.1c.4-.8 1.4-1.6 2.9-1.6 3.1 0 3.7 2 3.7 4.7V19h-3v-5.2c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V19h-3V8.6z' ),
			'youtube'   => array( 'YouTube', '#FF0000', 'M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8c1.6.4 7.8.4 7.8.4s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15V9l5.2 3L10 15z' ),
			'tiktok'    => array( 'TikTok', '#000000', 'M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.7a5.8 5.8 0 1 0 5 5.7V9.1a7.4 7.4 0 0 0 4.3 1.4V7.4a4.3 4.3 0 0 1-3.3-1.6z' ),
			'pinterest' => array( 'Pinterest', '#BD081C', 'M12 2.5a9.5 9.5 0 0 0-3.5 18.3c-.1-.8-.1-2 0-2.8l1.2-5.2s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.6 2.2-.8 3.4-.2 1 .5 1.8 1.5 1.8 1.8 0 3.2-1.9 3.2-4.7 0-2.4-1.7-4.1-4.2-4.1-2.9 0-4.6 2.2-4.6 4.4 0 .9.3 1.8.8 2.3.1.1.1.2.1.3l-.3 1.2c0 .2-.2.3-.4.2-1.3-.6-2.1-2.5-2.1-4 0-3.3 2.4-6.3 6.9-6.3 3.6 0 6.4 2.6 6.4 6 0 3.6-2.3 6.4-5.4 6.4-1 0-2-.5-2.4-1.2l-.6 2.5c-.2.9-.9 2.1-1.3 2.8A9.5 9.5 0 1 0 12 2.5z' ),
			'whatsapp'  => array( 'WhatsApp', '#25D366', 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2z' ),
			'threads'   => array( 'Threads', '#000000', 'M16.7 11.3c-.1 0-.2-.1-.3-.1-.2-2.8-1.7-4.4-4.3-4.4-1.5 0-2.8.6-3.6 1.8l1.4 1c.6-.9 1.5-1.1 2.2-1.1 1.2 0 2.2.4 2.4 2.2-.6-.1-1.2-.1-1.8-.1-2.7 0-4.4 1.4-4.3 3.4.1 1.8 1.6 3 3.6 3 1.4 0 2.6-.6 3.2-1.8.4-.6.6-1.4.7-2.3.6.4 1 .9 1.3 1.5.4 1 .5 2.6-.8 3.9-1.1 1.1-2.4 1.6-4.4 1.6-2.2 0-3.9-.7-5-2.1-1-1.3-1.6-3.2-1.6-5.6s.5-4.3 1.6-5.6C7.2 4.7 8.9 4 11.1 4s4 .7 5.1 2.1c.6.7 1 1.5 1.3 2.5l1.7-.4c-.3-1.3-.9-2.4-1.6-3.3C16.2 3 13.9 2.2 11.1 2.2 8.3 2.2 6.1 3.1 4.6 5 3.3 6.6 2.6 9 2.6 11.9s.7 5.3 2 6.9c1.5 1.9 3.7 2.8 6.5 2.8 2.5 0 4.3-.7 5.7-2.1 1.9-1.9 1.9-4.3 1.2-5.9-.4-1.1-1.3-1.9-2.3-2.3zm-4.6 4.3c-1 .1-2-.4-2.1-1.3 0-.7.5-1.4 2.1-1.5h.6c.5 0 1 .1 1.5.2-.2 1.9-1.2 2.5-2.1 2.6z' ),
		)
	);
}

/**
 * All settings: key => [section, type, label, default, extra].
 *
 * @return array
 */
function semrs_theme_settings_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array(
		// Logo & header.
		'logo_width'       => array( 'header', 'number', __( 'Logo width on desktop (px)', 'semrs' ), 160, array( 'min' => 40, 'max' => 400, 'step' => 2 ) ),
		'logo_width_mobile' => array( 'header', 'number', __( 'Logo width on mobile (px)', 'semrs' ), 120, array( 'min' => 32, 'max' => 260, 'step' => 2 ) ),
		'header_height'    => array( 'header', 'number', __( 'Header height (px)', 'semrs' ), 76, array( 'min' => 56, 'max' => 160, 'step' => 2 ) ),
		'show_tagline'     => array( 'header', 'checkbox', __( 'Show tagline under the site title', 'semrs' ), false ),
		'cta_text'         => array( 'header', 'text', __( 'Header button text', 'semrs' ), '' ),
		'cta_url'          => array( 'header', 'url', __( 'Header button link', 'semrs' ), '' ),
		// Header builder (free): rows, sticky, transparent.
		'sticky_header'    => array( 'hbuilder', 'checkbox', __( 'Sticky header (stays at the top when scrolling)', 'semrs' ), true ),
		'sticky_shrink'    => array( 'hbuilder', 'checkbox', __( 'Shrink the sticky header after scrolling', 'semrs' ), true ),
		'transparent'      => array( 'hbuilder', 'select', __( 'Transparent header (over the first section)', 'semrs' ), 'off', array( 'off' => __( 'Off', 'semrs' ), 'front' => __( 'Front page only', 'semrs' ), 'all' => __( 'All pages', 'semrs' ) ) ),
		'transparent_light' => array( 'hbuilder', 'checkbox', __( 'White text while the header is transparent (for dark hero images)', 'semrs' ), true ),
		'header_bg'        => array( 'hbuilder', 'color', __( 'Header background', 'semrs' ), '#ffffff' ),
		'top_bg'           => array( 'hbuilder', 'color', __( 'Top bar background', 'semrs' ), '#0b1f3a' ),
		'top_text'         => array( 'hbuilder', 'color', __( 'Top bar text', 'semrs' ), '#ffffff' ),
		'top_mobile'       => array( 'hbuilder', 'checkbox', __( 'Show the top bar on phones', 'semrs' ), false ),
		'mobile_button'    => array( 'hbuilder', 'checkbox', __( 'Show the header button next to the menu icon on phones', 'semrs' ), false ),
		'phone'            => array( 'hbuilder', 'text', __( 'Phone (Contact element)', 'semrs' ), '' ),
		'action_bar'       => array( 'hbuilder', 'checkbox', __( 'Quick contact: Call / WhatsApp / header button bar on phones and a WhatsApp button on larger screens (uses Phone, the WhatsApp profile URL and the header button)', 'semrs' ), false ),
		'email'            => array( 'hbuilder', 'text', __( 'Email (Contact element)', 'semrs' ), '' ),
		'btn2_text'        => array( 'hbuilder', 'text', __( 'Second button text', 'semrs' ), '' ),
		'btn2_url'         => array( 'hbuilder', 'url', __( 'Second button link', 'semrs' ), '' ),
		'html1'            => array( 'hbuilder', 'html', __( 'Text / HTML 1 (e.g. "Free shipping over $50")', 'semrs' ), '' ),
		'html2'            => array( 'hbuilder', 'html', __( 'Text / HTML 2', 'semrs' ), '' ),
		'html3'            => array( 'fbuilder', 'html', __( 'Footer Text / HTML', 'semrs' ), '' ),
		// Colours.
		'color_primary'    => array( 'colors', 'color', __( 'Primary (headings, header text, footer)', 'semrs' ), '#0b1f3a' ),
		'color_accent'     => array( 'colors', 'color', __( 'Accent (buttons, highlights)', 'semrs' ), '#d4a72c' ),
		'color_on_accent'  => array( 'colors', 'color', __( 'Text on accent buttons', 'semrs' ), '#0b1f3a' ),
		'color_text'       => array( 'colors', 'color', __( 'Body text', 'semrs' ), '#1f2937' ),
		'color_link'       => array( 'colors', 'color', __( 'Links', 'semrs' ), '#1e3a66' ),
		'color_link_hover' => array( 'colors', 'color', __( 'Links on hover', 'semrs' ), '#8c6a0e' ),
		'color_btn_hover'  => array( 'colors', 'color', __( 'Button background on hover', 'semrs' ), '#0b1f3a' ),
		'color_btn_hover_text' => array( 'colors', 'color', __( 'Button text on hover', 'semrs' ), '#ffffff' ),
		'color_btn_outline' => array( 'colors', 'color', __( 'Outline button text and border', 'semrs' ), '#0b1f3a' ),
		'color_bg'         => array( 'colors', 'color', __( 'Background', 'semrs' ), '#ffffff' ),
		// Typography & size.
		'font'             => array( 'type', 'select', __( 'Font', 'semrs' ), 'system', array( 'system' => __( 'System fonts (fastest)', 'semrs' ), 'inter' => __( 'Inter (self-hosted, add fonts/InterVariable.woff2)', 'semrs' ) ) ),
		'font_size'        => array( 'type', 'number', __( 'Base font size (px)', 'semrs' ), 17, array( 'min' => 14, 'max' => 21 ) ),
		'container'        => array( 'type', 'number', __( 'Container width (px)', 'semrs' ), 1200, array( 'min' => 960, 'max' => 1600 ) ),
		// Layout.
		'layout_post'      => array( 'layout', 'select', __( 'Single posts', 'semrs' ), 'right', 'layouts' ),
		'layout_page'      => array( 'layout', 'select', __( 'Pages', 'semrs' ), 'none', 'layouts' ),
		'layout_archive'   => array( 'layout', 'select', __( 'Blog & archives', 'semrs' ), 'right', 'layouts' ),
		'archive_style'    => array( 'layout', 'select', __( 'Archive style', 'semrs' ), 'grid', array( 'grid' => __( 'Card grid', 'semrs' ), 'list' => __( 'List', 'semrs' ) ) ),
		'excerpt_length'   => array( 'layout', 'number', __( 'Excerpt length (words)', 'semrs' ), 24, array( 'min' => 10, 'max' => 80 ) ),
		'sticky_sidebar'   => array( 'layout', 'checkbox', __( 'Sticky sidebar (last widget stays in view)', 'semrs' ), true ),
		// Blog.
		'single_thumb'     => array( 'blog', 'checkbox', __( 'Featured image on single posts', 'semrs' ), true ),
		'post_nav'         => array( 'blog', 'checkbox', __( 'Previous / next post links', 'semrs' ), true ),
		'progress'         => array( 'blog', 'checkbox', __( 'Reading progress bar on posts', 'semrs' ), true ),
		'updated_date'     => array( 'blog', 'checkbox', __( 'Show the "Updated on" date when a post was changed', 'semrs' ), true ),
		'author_box'       => array( 'blog', 'checkbox', __( 'Author box after posts', 'semrs' ), true ),
		'related'          => array( 'blog', 'checkbox', __( 'Related posts (same category)', 'semrs' ), true ),
		// Footer.
		'footer_cols'      => array( 'footer', 'select', __( 'Footer widget columns', 'semrs' ), '4', array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ) ),
		'copyright'        => array( 'footer', 'text', __( 'Copyright text ({year}, {site})', 'semrs' ), '© {year} {site}. All rights reserved.' ),
		'footer_note'      => array( 'footer', 'text', __( 'Note line under the footer (disclaimer)', 'semrs' ), '' ),
		'social_size'      => array( 'social', 'number', __( 'Social icon size (px)', 'semrs' ), 40, array( 'min' => 28, 'max' => 64, 'step' => 2 ) ),
		'social_style'     => array( 'social', 'select', __( 'Social icon style', 'semrs' ), 'brand', array( 'brand' => __( 'Brand colours (filled)', 'semrs' ), 'mono' => __( 'Single colour', 'semrs' ) ) ),
		'to_top'           => array( 'footer', 'checkbox', __( 'Back-to-top button', 'semrs' ), true ),
	);
	foreach ( semrs_theme_social_networks() as $key => $net ) {
		/* translators: %s: social network name */
		$map[ 'social_' . $key ] = array( 'social', 'url', sprintf( __( '%s profile URL', 'semrs' ), $net[0] ), '' );
	}
	/**
	 * Filter the theme settings (SEMRS Pro adds its own).
	 *
	 * @param array $map key => [section, type, label, default, extra].
	 */
	$map = apply_filters( 'semrs_theme_settings', $map );
	return $map;
}

/**
 * Read a theme option.
 *
 * @param string $key Key without prefix.
 * @return mixed
 */
function semrs_theme_opt( $key ) {
	$map = semrs_theme_settings_map();
	return get_theme_mod( 'semrs_theme_' . $key, isset( $map[ $key ] ) ? $map[ $key ][3] : null );
}

/**
 * Register panel, sections, settings, controls.
 *
 * @param WP_Customize_Manager $wpc Customizer.
 */
function semrs_theme_customize_register( $wpc ) {
	$wpc->add_panel( 'semrs_theme', array( 'title' => __( 'SEMRS Theme', 'semrs' ), 'priority' => 1 ) );
	$sections = apply_filters(
		'semrs_theme_sections',
		array(
			'hbuilder' => __( 'Header builder', 'semrs' ),
			'header' => __( 'Logo & header button', 'semrs' ),
			'colors' => __( 'Colours', 'semrs' ),
			'type'   => __( 'Typography & width', 'semrs' ),
			'layout' => __( 'Layout & sidebar', 'semrs' ),
			'blog'   => __( 'Blog & posts', 'semrs' ),
			'footer' => __( 'Footer', 'semrs' ),
			'fbuilder' => __( 'Footer builder', 'semrs' ),
			'social' => __( 'Social profiles', 'semrs' ),
		)
	);
	foreach ( $sections as $id => $title ) {
		$wpc->add_section( 'semrs_theme_' . $id, array( 'title' => $title, 'panel' => 'semrs_theme' ) );
	}
	// Recommend a square 512×512 logo in the core Site Identity section.
	$logo = $wpc->get_control( 'custom_logo' );
	if ( $logo ) {
		$logo->description = __( 'Recommended: 512 × 512 px (square) or a wide logo at least 320 px wide, PNG, SVG-converted PNG or WebP. Set the display size under SEMRS Theme → Logo & header.', 'semrs' );
	}
	$layouts = array(
		'right' => __( 'Right sidebar', 'semrs' ),
		'left'  => __( 'Left sidebar', 'semrs' ),
		'none'  => __( 'No sidebar', 'semrs' ),
	);

	foreach ( semrs_theme_settings_map() as $key => $cfg ) {
		list( $section, $type, $label, $default ) = $cfg;
		$extra    = isset( $cfg[4] ) ? $cfg[4] : array();
		$sanitize = 'sanitize_text_field';
		if ( 'color' === $type ) {
			$sanitize = 'sanitize_hex_color';
		} elseif ( 'checkbox' === $type ) {
			$sanitize = 'rest_sanitize_boolean';
		} elseif ( 'number' === $type ) {
			$min      = isset( $extra['min'] ) ? (int) $extra['min'] : 0;
			$max      = isset( $extra['max'] ) ? (int) $extra['max'] : PHP_INT_MAX;
			$sanitize = function ( $v ) use ( $min, $max, $default ) {
				return '' === $v ? $default : max( $min, min( $max, absint( $v ) ) );
			};
		} elseif ( 'url' === $type ) {
			$sanitize = 'esc_url_raw';
		} elseif ( 'html' === $type ) {
			$sanitize = 'wp_kses_post';
		} elseif ( 'select' === $type ) {
			$choices  = 'layouts' === $extra ? $layouts : $extra;
			$sanitize = function ( $v ) use ( $choices, $default ) {
				return array_key_exists( $v, $choices ) ? $v : $default;
			};
		}
		$wpc->add_setting(
			'semrs_theme_' . $key,
			array(
				'default'           => $default,
				'sanitize_callback' => $sanitize,
				'transport'         => in_array( $key, array( 'logo_width', 'logo_width_mobile', 'header_height', 'social_size' ), true ) ? 'postMessage' : 'refresh',
			)
		);

		$args = array( 'label' => $label, 'section' => 'semrs_theme_' . $section );
		if ( 'color' === $type ) {
			$wpc->add_control( new WP_Customize_Color_Control( $wpc, 'semrs_theme_' . $key, $args ) );
			continue;
		}
		$args['type'] = 'number' === $type && in_array( $key, array( 'logo_width', 'logo_width_mobile', 'header_height', 'social_size' ), true ) ? 'range' : ( 'url' === $type ? 'url' : ( 'html' === $type ? 'textarea' : $type ) );
		if ( 'select' === $type ) {
			$args['choices'] = 'layouts' === $extra ? $layouts : $extra;
		}
		if ( 'number' === $type ) {
			$args['input_attrs'] = $extra;
		}
		$wpc->add_control( 'semrs_theme_' . $key, $args );
	}
}
add_action( 'customize_register', 'semrs_theme_customize_register' );

/**
 * Live preview for logo width, header height and icon size (no reload).
 */
function semrs_theme_customize_preview_js() {
	wp_add_inline_script(
		'customize-preview',
		"(function(api){var r=document.documentElement.style;function bind(id,v){api('semrs_theme_'+id,function(s){s.bind(function(n){r.setProperty(v,parseInt(n,10)+'px');});});}bind('logo_width','--sp-logo-w');bind('logo_width_mobile','--sp-logo-w-m');bind('header_height','--sp-header-h');bind('social_size','--sp-social');})(wp.customize);"
	);
}
add_action( 'customize_preview_init', 'semrs_theme_customize_preview_js' );

/**
 * CSS variables for settings that differ from the defaults (nothing extra
 * is printed for an unchanged theme).
 *
 * @return string
 */
function semrs_theme_customizer_css() {
	$map  = semrs_theme_settings_map();
	$vars = array(
		'color_primary'   => '--sp-primary',
		'color_accent'    => '--sp-accent',
		'color_on_accent' => '--sp-on-accent',
		'color_text'      => '--sp-text',
		'color_link'      => '--sp-link',
		'color_link_hover' => '--sp-link-hover',
		'color_btn_hover' => '--sp-btn-hover',
		'color_btn_hover_text' => '--sp-btn-hover-text',
		'color_btn_outline' => '--sp-btn-outline',
		'color_bg'        => '--sp-bg',
		'header_bg'       => '--sp-header-bg',
		'top_bg'          => '--sp-top-bg',
		'top_text'        => '--sp-top-text',
	);
	$css = '';
	foreach ( $vars as $key => $var ) {
		$val = semrs_theme_opt( $key );
		if ( $val && strtolower( $val ) !== $map[ $key ][3] ) {
			$css .= $var . ':' . $val . ';';
		}
	}
	$px = array(
		'font_size'         => '--sp-font-size',
		'container'         => '--sp-container',
		'logo_width'        => '--sp-logo-w',
		'logo_width_mobile' => '--sp-logo-w-m',
		'header_height'     => '--sp-header-h',
		'social_size'       => '--sp-social',
	);
	foreach ( $px as $key => $var ) {
		if ( (int) semrs_theme_opt( $key ) !== (int) $map[ $key ][3] ) {
			$css .= $var . ':' . (int) semrs_theme_opt( $key ) . 'px;';
		}
	}
	// Square logos (e.g. 512×512) get compact defaults until the width is changed.
	$logo = wp_get_attachment_image_src( (int) get_theme_mod( 'custom_logo' ), 'full' );
	if ( $logo && $logo[2] > 0 && $logo[1] / $logo[2] < 1.4 ) {
		if ( (int) semrs_theme_opt( 'logo_width' ) === (int) $map['logo_width'][3] ) {
			$css .= '--sp-logo-w:64px;';
		}
		if ( (int) semrs_theme_opt( 'logo_width_mobile' ) === (int) $map['logo_width_mobile'][3] ) {
			$css .= '--sp-logo-w-m:52px;';
		}
	}
	if ( 'inter' === semrs_theme_opt( 'font' ) ) {
		$css .= '--sp-font:"Inter",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;';
	}
	if ( '4' !== (string) semrs_theme_opt( 'footer_cols' ) ) {
		$css .= '--sp-footer-cols:' . (int) semrs_theme_opt( 'footer_cols' ) . ';';
	}
	$out = $css ? ':root{' . $css . '}' : '';

	if ( 'inter' === semrs_theme_opt( 'font' ) ) {
		$font = semrs_theme_font_url();
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
function semrs_theme_font_url() {
	foreach ( array( array( get_stylesheet_directory(), get_stylesheet_directory_uri() ), array( SEMRS_THEME_DIR, SEMRS_THEME_URI ) ) as $loc ) {
		if ( file_exists( $loc[0] . '/fonts/InterVariable.woff2' ) ) {
			return $loc[1] . '/fonts/InterVariable.woff2';
		}
	}
	return '';
}

/**
 * Preload the font when Inter is chosen.
 */
function semrs_theme_preload_font() {
	if ( 'inter' === semrs_theme_opt( 'font' ) && semrs_theme_font_url() ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( semrs_theme_font_url() ) );
	}
}
add_action( 'wp_head', 'semrs_theme_preload_font', 1 );
