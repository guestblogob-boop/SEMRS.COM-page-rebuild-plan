<?php
/**
 * Host Google Fonts locally (SEMRS → Speed → Optimize): any theme or plugin that loads
 * fonts.googleapis.com gets the same fonts from your own site instead.
 *
 * - Faster: no connection to two Google servers, and the most used font files are preloaded.
 * - Privacy: visitors' IP addresses are no longer sent to Google (a GDPR issue in the EU).
 *
 * The first time a page asks for a Google Fonts stylesheet, the stylesheet and its WOFF2 files
 * are downloaded in the background into wp-content/cache/semrs/fonts/ (the page keeps the Google
 * link until the copy is ready). Then the link is replaced by the local @font-face rules inline,
 * with font-display: swap, and preload hints for up to two Latin font files.
 * If a download fails, the Google link simply stays.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Local fonts folder and URL.
 *
 * @return array{0:string,1:string}
 */
function semrs_fonts_dir() {
	return array( trailingslashit( WP_CONTENT_DIR ) . 'cache/semrs/fonts/', content_url( 'cache/semrs/fonts/' ) );
}

/**
 * Normalise a Google Fonts stylesheet address.
 *
 * @param string $url URL from the page.
 * @return string
 */
function semrs_fonts_norm( $url ) {
	$url = html_entity_decode( trim( $url ) );
	return 0 === strpos( $url, '//' ) ? 'https:' . $url : preg_replace( '#^http:#', 'https:', $url );
}

/**
 * Rewrite the page: Google Fonts links → local inline @font-face (+ preload), when ready.
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_fonts_localize( $html ) {
	if ( false === stripos( $html, 'fonts.googleapis.com' ) ) {
		return $html;
	}
	list( $dir, $url ) = semrs_fonts_dir();
	$queue    = (array) get_option( 'semrs_fonts_queue', array() );
	$preloads = array();
	$done     = 0;
	$html     = preg_replace_callback(
		'#<link\b[^>]*href=["\']((?:https?:)?//fonts\.googleapis\.com/css2?\?[^"\']+)["\'][^>]*>#i',
		function ( $m ) use ( $dir, $url, &$queue, &$preloads, &$done ) {
			if ( ! preg_match( '/rel=["\']?(stylesheet|preload)/i', $m[0] ) ) {
				return $m[0];
			}
			$src  = semrs_fonts_norm( $m[1] );
			$key  = md5( $src );
			$file = $dir . $key . '.css';
			if ( ! is_readable( $file ) ) {
				$queue[ $key ] = $src;
				return $m[0];
			}
			if ( preg_match( '/rel=["\']?preload/i', $m[0] ) ) {
				return ''; // The local copy replaces the preload too.
			}
			$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local cache file.
			if ( preg_match_all( '#/\*\s*latin\s*\*/[^}]*?url\(([^)]+\.woff2)\)#i', $css, $f ) ) {
				foreach ( array_slice( $f[1], 0, 2 ) as $font ) {
					$preloads[ $font ] = true;
				}
			}
			$done++;
			return '<style id="semrs-local-fonts-' . esc_attr( substr( $key, 0, 8 ) ) . '">' . str_replace( '</style', '<\/style', $css ) . '</style>';
		},
		$html
	);
	if ( $queue !== (array) get_option( 'semrs_fonts_queue', array() ) ) {
		update_option( 'semrs_fonts_queue', array_slice( $queue, 0, 20, true ), false );
		if ( ! wp_next_scheduled( 'semrs_fonts_fetch' ) ) {
			wp_schedule_single_event( time() + 5, 'semrs_fonts_fetch' );
		}
	}
	if ( $done ) {
		// No longer needed: early connections to Google's font servers.
		$html = preg_replace( '#<link\b[^>]*rel=["\'](?:preconnect|dns-prefetch)["\'][^>]*fonts\.(?:googleapis|gstatic)\.com[^>]*>\s*#i', '', $html );
		$html = preg_replace( '#<link\b[^>]*fonts\.(?:googleapis|gstatic)\.com[^>]*rel=["\'](?:preconnect|dns-prefetch)["\'][^>]*>\s*#i', '', $html );
		if ( $preloads ) {
			$tags = '';
			foreach ( array_keys( $preloads ) as $font ) {
				$tags .= '<link rel="preload" href="' . esc_url( trim( $font, '\'" ' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
			}
			$html = preg_replace( '#<head\b[^>]*>#i', '$0' . "\n" . $tags, $html, 1 );
		}
	}
	return $html;
}

/**
 * Background job: download queued stylesheets and their WOFF2 files.
 */
function semrs_fonts_fetch() {
	list( $dir, $url ) = semrs_fonts_dir();
	if ( ! wp_mkdir_p( $dir ) ) {
		return;
	}
	$queue = (array) get_option( 'semrs_fonts_queue', array() );
	$ua    = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36'; // Google sends WOFF2 to modern browsers.
	foreach ( array_slice( $queue, 0, 5, true ) as $key => $src ) {
		unset( $queue[ $key ] );
		if ( ! preg_match( '#^https://fonts\.googleapis\.com/css2?\?#', $src ) ) {
			continue;
		}
		$res = wp_remote_get( $src, array( 'timeout' => 10, 'user-agent' => $ua ) );
		$css = 200 === wp_remote_retrieve_response_code( $res ) ? (string) wp_remote_retrieve_body( $res ) : '';
		if ( '' === $css || false === strpos( $css, '@font-face' ) ) {
			continue;
		}
		$ok  = true;
		$css = preg_replace_callback(
			'#url\((https://fonts\.gstatic\.com/[^)]+)\)#',
			function ( $m ) use ( $dir, $url, &$ok ) {
				$name = md5( $m[1] ) . '.' . ( pathinfo( wp_parse_url( $m[1], PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'woff2' );
				if ( ! is_readable( $dir . $name ) ) {
					$font = wp_remote_get( $m[1], array( 'timeout' => 15 ) );
					if ( 200 !== wp_remote_retrieve_response_code( $font ) || '' === wp_remote_retrieve_body( $font ) ) {
						$ok = false;
						return $m[0];
					}
					file_put_contents( $dir . $name, wp_remote_retrieve_body( $font ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				}
				return 'url(' . $url . $name . ')';
			},
			$css
		);
		if ( ! $ok ) {
			continue; // Try again next time the page is built.
		}
		if ( false === stripos( $css, 'font-display' ) ) {
			$css = str_replace( '@font-face {', '@font-face { font-display: swap;', $css );
		}
		file_put_contents( $dir . $key . '.css', preg_replace( '/\s*\n\s*/', '', $css ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	update_option( 'semrs_fonts_queue', $queue, false );
	if ( $queue ) {
		wp_schedule_single_event( time() + 30, 'semrs_fonts_fetch' );
	}
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all(); // Cached pages pick up the local fonts.
	}
}
add_action( 'semrs_fonts_fetch', 'semrs_fonts_fetch' );

if ( ! empty( semrs_speed_settings()['local_fonts'] ) && ! is_admin() ) {
	add_filter( 'semrs_speed_html', 'semrs_fonts_localize', 5 );
}
