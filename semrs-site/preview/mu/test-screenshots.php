<?php
/**
 * PREVIEW ONLY (never ship): ?semrs_shot=child|parent renders a page as a
 * logged-out visitor (no admin bar) so theme screenshots can be captured.
 * "parent" shows the SEMRS parent theme instead of the active child theme.
 */

if ( empty( $_GET['semrs_shot'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	return;
}
add_filter( 'determine_current_user', '__return_zero', 99 );
add_filter( 'show_admin_bar', '__return_false' );
if ( 'parent' === $_GET['semrs_shot'] ) { // phpcs:ignore WordPress.Security.NonceVerification
	add_filter( 'pre_option_stylesheet', function () { return 'semrs'; } );
	add_filter( 'pre_option_template', function () { return 'semrs'; } );
}
// Same as ticking "Hide the page title" in Page options for the showcase page.
add_filter( 'get_post_metadata', function ( $v, $id, $key ) { return ( 'semrs_hide_title' === $key && 'theme-showcase' === get_post_field( 'post_name', $id ) ) ? array( '1' ) : $v; }, 10, 3 );

// Parent-theme shots: use the child theme settings (menus, logo) and fresh pattern files.
if ( 'parent' === $_GET['semrs_shot'] ) { // phpcs:ignore WordPress.Security.NonceVerification
	add_filter( 'pre_option_theme_mods_semrs', function () { return get_option( 'theme_mods_semrs-child-seo' ); } );
}
add_action( 'init', function () { wp_get_theme()->delete_pattern_cache(); wp_get_theme( 'semrs' )->delete_pattern_cache(); }, 1 );

// ?semrs_dark=1 shows the page in dark mode (Pro) for screenshots.
if ( ! empty( $_GET['semrs_dark'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	add_action( 'wp_head', function () { echo "<script>document.documentElement.setAttribute('data-theme','dark');</script>"; }, 1 );
}

// Free-product shots: hide Pro header elements (kept for ?semrs_dark=1 Pro shots).
if ( empty( $_GET['semrs_dark'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	add_filter( 'theme_mod_semrs_theme_hb_layout', function ( $v ) { return str_replace( array( '"languages",', ',"languages"', '"languages"', '"darkmode",', ',"darkmode"', '"darkmode"' ), '', (string) $v ); } );
}

// Pro shots (?semrs_dark=1): show the dark / light switch in the header.
if ( ! empty( $_GET['semrs_dark'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	add_filter(
		'theme_mod_semrs_theme_hb_layout',
		function ( $v ) {
			$l = json_decode( (string) $v, true );
			if ( is_array( $l ) && ! in_array( 'darkmode', $l['main']['right'], true ) ) {
				$l['main']['right'][] = 'darkmode';
			}
			return wp_json_encode( $l );
		}
	);
}

// Theme screenshots: show the parent theme's quick contact bar on phones (a Customizer setting).
add_filter( 'theme_mod_semrs_theme_action_bar', '__return_true' );
