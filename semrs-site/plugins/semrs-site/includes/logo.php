<?php
/**
 * semrs.com logo: the original SEMRS emblem (SE·MR·S lettering, rocket,
 * gears, search lens and swooshes) redrawn as inline SVG. It replaces the
 * uploaded logo image wherever the theme prints the custom logo: no extra
 * request, no web fonts, sharp at every size. A 3D depth is drawn with
 * five <use> references to the same shapes (a few hundred bytes, not five
 * copies), with separate colours for light and dark mode.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

/**
 * The logo SVG (shapes live in assets/images/semrs-logo-inner.svg).
 *
 * @param string $class Extra class.
 * @return string
 */
function semrs_site_logo_svg( $class = '' ) {
	static $inner = null;
	if ( null === $inner ) {
		$inner = (string) file_get_contents( SEMRS_SITE_DIR . 'assets/images/semrs-logo-inner.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- bundled SVG.
	}
	static $n = 0;
	$id    = 'semrs-lg' . ( ++$n );
	$depth = '';
	foreach ( array( 20, 15, 10, 5 ) as $d ) {
		$depth .= '<use href="#' . $id . '" class="x" transform="translate(' . ( $d * 0.8 ) . ' ' . $d . ')"/>';
	}
	return '<svg class="custom-logo semrs-logo ' . esc_attr( $class ) . '" viewBox="-10 -10 1650 1110" width="1650" height="1110" role="img" aria-label="' . esc_attr( get_bloginfo( 'name' ) . ' – Search Engine Marketing & Ranking Solutions' ) . '" xmlns="http://www.w3.org/2000/svg"><defs><g id="' . $id . '" class="semrs-art">' . $inner . '</g></defs>' . $depth . '<use href="#' . $id . '" class="f"/></svg>';
}

/**
 * Replace the theme's custom logo output.
 *
 * @return string
 */
function semrs_site_custom_logo() {
	$current = is_front_page() && ! is_paged() ? ' aria-current="page"' : '';
	return '<a href="' . esc_url( home_url( '/' ) ) . '" class="custom-logo-link" rel="home"' . $current . '>' . semrs_site_logo_svg() . '</a>';
}
add_filter( 'get_custom_logo', 'semrs_site_custom_logo', 20 );

/**
 * The theme only prints the logo when one is set: count the SVG as set.
 */
add_filter( 'has_custom_logo', '__return_true' );

/**
 * Colours, size and dark-mode colours.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$css = '.semrs-art .sb{fill:var(--semrs-b,#0b2fd1)}.semrs-art .so{fill:var(--semrs-o,#f7941d)}.semrs-art .sos{fill:none;stroke:var(--semrs-o,#f7941d);stroke-width:46}.semrs-art .sw{fill:none;stroke:var(--semrs-w,#fff);stroke-width:7;stroke-linecap:round;stroke-linejoin:round}.semrs-art .swf{fill:var(--semrs-w,#fff);stroke:var(--semrs-b,#0b2fd1);stroke-width:10}.semrs-art .sl{fill:none;stroke:var(--semrs-b,#0b2fd1);stroke-width:3.2}'
			. '.semrs-logo .x{--semrs-b:var(--semrs-xb,#06176b);--semrs-o:var(--semrs-xo,#9a4d00);--semrs-w:var(--semrs-xb,#06176b)}.semrs-logo{filter:drop-shadow(0 2px 2px rgba(6,23,107,.22))}.site-branding .custom-logo-link .semrs-logo{width:var(--sp-logo-w,160px);max-width:100%;height:auto;aspect-ratio:1650/1110}'
			. '@media(max-width:921px){.site-branding .custom-logo-link .semrs-logo{width:var(--sp-logo-w-m,120px)}}'
			. 'html[data-theme="dark"] .semrs-logo,.site-footer .semrs-logo{--semrs-b:#4f7dff;--semrs-o:#ffa63d;--semrs-w:#ffffff;--semrs-xb:#000c45;--semrs-xo:#6e3700;filter:drop-shadow(0 0 6px rgba(79,125,255,.35))}';
		wp_register_style( 'semrs-site-logo', false, array(), SEMRS_SITE_VERSION );
		wp_enqueue_style( 'semrs-site-logo' );
		wp_add_inline_style( 'semrs-site-logo', $css );
	}
);
