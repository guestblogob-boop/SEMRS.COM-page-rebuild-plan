<?php
/**
 * CSS delivery and navigation: inline small stylesheets, load chosen
 * stylesheets without blocking rendering, prefetch pages on hover.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Local file path for a stylesheet URL on this site, or '' when not local.
 *
 * @param string $href Stylesheet URL.
 * @return string
 */
function semrs_speed_local_path( $href ) {
	$url = strtok( (string) $href, '?#' );
	if ( 0 === strpos( $url, '//' ) ) {
		$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
	}
	$map = array(
		content_url( '/' )  => trailingslashit( WP_CONTENT_DIR ),
		includes_url( '/' ) => ABSPATH . WPINC . '/',
		site_url( '/' )     => ABSPATH,
	);
	$scheme = (string) wp_parse_url( $url, PHP_URL_SCHEME );
	foreach ( $map as $base => $dir ) {
		$base = set_url_scheme( $base, $scheme ? $scheme : null );
		if ( 0 !== strpos( $url, $base ) ) {
			continue;
		}
		$file = realpath( $dir . rawurldecode( substr( $url, strlen( $base ) ) ) );
		if ( ! $file || '.css' !== strtolower( substr( $file, -4 ) ) ) {
			return '';
		}
		$norm = wp_normalize_path( $file );
		$ok   = 0 === strpos( $norm, wp_normalize_path( realpath( ABSPATH ) ) ) || 0 === strpos( $norm, wp_normalize_path( realpath( WP_CONTENT_DIR ) ) );
		return $ok ? $file : '';
	}
	return '';
}

/**
 * Inline small stylesheets and load chosen ones without blocking rendering.
 *
 * @param string $tag    Link tag.
 * @param string $handle Style handle.
 * @param string $href   URL.
 * @param string $media  Media attribute.
 * @return string
 */
function semrs_speed_style_tag( $tag, $handle, $href, $media ) {
	$s = semrs_speed_settings();
	if ( is_customize_preview() || false !== strpos( $tag, 'alternate' ) ) {
		return $tag;
	}
	$media = $media ? $media : 'all';
	if ( $s['async_css'] ) {
		$async = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['async_handles'] ) ) );
		if ( in_array( $handle, $async, true ) ) {
			$lazy = preg_replace( '/\smedia=([\'"])[^\'"]*\1/', '', $tag );
			$lazy = str_replace( '<link ', '<link media="print" onload="this.media=\'' . esc_js( $media ) . '\';this.onload=null" ', $lazy );
			return $lazy . '<noscript>' . trim( $tag ) . "</noscript>\n";
		}
	}
	if ( $s['inline_css'] && in_array( $media, array( 'all', 'screen' ), true ) ) {
		$file = semrs_speed_local_path( $href );
		if ( $file && filesize( $file ) <= 10240 ) {
			$css  = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local stylesheet.
			$base = trailingslashit( dirname( strtok( $href, '?#' ) ) );
			// Relative url(...) paths must keep pointing at the original folder.
			$css = preg_replace_callback(
				'/url\(\s*([\'"]?)(?!data:|https?:|\/\/|\/|#)([^\'")]+)\1\s*\)/i',
				function ( $m ) use ( $base ) {
					return 'url(' . $m[1] . $base . $m[2] . $m[1] . ')';
				},
				$css
			);
			$css = preg_replace( '#/\*\# sourceMappingURL=.*?\*/#', '', $css );
			return sprintf( "<style id=\"%s-css\" media=\"%s\">%s</style>\n", esc_attr( $handle ), esc_attr( $media ), str_ireplace( '</style', '<\/style', $css ) );
		}
	}
	return $tag;
}

/**
 * Prefetch same-site pages on hover (Speculation Rules, moderate eagerness).
 */
function semrs_speed_prefetch() {
	$skip = array( '/wp-admin/*', '/wp-login.php*', '/wp-content/*', '/cart/*', '/checkout/*', '/my-account/*', '/*\\?*' );
	if ( function_exists( 'wp_get_speculation_rules_configuration' ) ) {
		// WordPress 6.8+: tune the built-in rules instead of adding a second set.
		add_filter(
			'wp_speculation_rules_configuration',
			function ( $config ) {
				return is_array( $config ) ? array( 'mode' => 'prefetch', 'eagerness' => 'moderate' ) : $config;
			}
		);
		add_filter(
			'wp_speculation_rules_href_exclude_paths',
			function ( $paths ) {
				return array_merge( (array) $paths, array( '/cart/*', '/checkout/*', '/my-account/*' ) );
			}
		);
		return;
	}
	add_action(
		'wp_footer',
		function () use ( $skip ) {
			if ( is_user_logged_in() ) {
				return;
			}
			$prefix = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
			$rules  = array(
				'prefetch' => array(
					array(
						'source'    => 'document',
						'where'     => array(
							'and' => array(
								array( 'href_matches' => $prefix . '/*' ),
								array(
									'not' => array(
										'href_matches' => array_map(
											function ( $p ) use ( $prefix ) {
												return $prefix . $p;
											},
											$skip
										),
									),
								),
								array( 'not' => array( 'selector_matches' => '[rel~=nofollow], .no-prefetch, [download]' ) ),
							),
						),
						'eagerness' => 'moderate',
					),
				),
			);
			echo '<script type="speculationrules">' . wp_json_encode( $rules, JSON_UNESCAPED_SLASHES ) . "</script>\n";
		}
	);
}
