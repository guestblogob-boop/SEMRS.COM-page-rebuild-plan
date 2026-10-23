<?php
/**
 * Remove unused CSS (SEMRS Pro speed): each local stylesheet is replaced by
 * an inline copy holding only the rules this page uses. The full file still
 * loads at the lowest priority and switches on at the first scroll, tap,
 * mouse move or key press, so menus, sliders and anything JavaScript adds
 * later keep their styles.
 *
 * A selector is kept when every class and ID in it appears in the page or
 * matches the keep list. Tags, attribute selectors and :is()/:not()/:has()
 * contents are treated as present, so the cut never removes too much.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default keep list: classes added by JavaScript in SEMRS themes and plugins.
 *
 * @return string One pattern per line, * = any characters.
 */
function semrs_pro_ucss_default_safelist() {
	return implode( "\n", array( 'is-*', 'has-*', 'sp-scrolled', 'sp-submenu*', 'semrs-pfv*', 'semrs-lightbox*', 'semrs-cf*', 'semrs-cd*', 'semrs-anim*', 'semrs-ad*', 'adsbygoogle', 'active', 'open', 'show', 'visible', 'toggled', 'current*', 'focus*', 'expanded', 'selected', 'checked', 'loaded', 'js', 'no-js' ) );
}

/**
 * Default skipped stylesheets: tool interfaces are built by JavaScript.
 *
 * @return string One pattern per line.
 */
function semrs_pro_ucss_default_exclude() {
	return 'semrs-tools';
}

/**
 * Lines of a pattern setting.
 *
 * @param string $text Setting.
 * @return string[]
 */
function semrs_pro_ucss_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $text ) ) ) );
}

/**
 * Classes and IDs used in the page, plus the keep-list pattern.
 *
 * @param string $html     Page HTML.
 * @param string $safelist Keep list.
 * @return array class, id (name => true), safe (regex or '')
 */
function semrs_pro_ucss_used_tokens( $html, $safelist ) {
	$used = array( 'class' => array(), 'id' => array(), 'safe' => '' );
	if ( preg_match_all( '/\sclass\s*=\s*(["\'])(.*?)\1/is', $html, $m ) ) {
		foreach ( $m[2] as $list ) {
			foreach ( preg_split( '/\s+/', html_entity_decode( $list ), -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
				$used['class'][ $c ] = true;
			}
		}
	}
	if ( preg_match_all( '/\sid\s*=\s*(["\'])(.*?)\1/is', $html, $m ) ) {
		foreach ( $m[2] as $id ) {
			$used['id'][ trim( html_entity_decode( $id ) ) ] = true;
		}
	}
	$pats = array();
	foreach ( semrs_pro_ucss_lines( $safelist ) as $p ) {
		$pats[] = str_replace( '\*', '.*', preg_quote( $p, '/' ) );
	}
	$used['safe'] = $pats ? '/^(?:' . implode( '|', $pats ) . ')$/' : '';
	return $used;
}

/**
 * Parse CSS into rules, at-rule groups (@media, @supports …) and raw
 * at-rules (@font-face, @keyframes …), which are kept as they are.
 *
 * @param string $css CSS without comments.
 * @param int    $i   Position (internal).
 * @return array
 */
function semrs_pro_ucss_parse( $css, &$i = 0 ) {
	$out = array();
	$len = strlen( $css );
	$buf = '';
	while ( $i < $len ) {
		$c = $css[ $i ];
		if ( '"' === $c || "'" === $c ) {
			$j = $i + 1;
			while ( $j < $len && $css[ $j ] !== $c ) {
				$j += '\\' === $css[ $j ] ? 2 : 1;
			}
			$buf .= substr( $css, $i, $j - $i + 1 );
			$i    = $j + 1;
			continue;
		}
		if ( ';' === $c ) {
			if ( '' !== trim( $buf ) ) {
				$out[] = array( 'at', trim( $buf ) . ';' );
			}
			$buf = '';
			++$i;
			continue;
		}
		if ( '}' === $c ) {
			++$i;
			return $out;
		}
		if ( '{' === $c ) {
			$sel = trim( $buf );
			$buf = '';
			++$i;
			if ( preg_match( '/^@(media|supports|layer|container|document)\b/i', $sel ) ) {
				$out[] = array( 'group', $sel, semrs_pro_ucss_parse( $css, $i ) );
				continue;
			}
			$depth = 1;
			$j     = $i;
			while ( $j < $len && $depth ) {
				$ch = $css[ $j ];
				if ( '"' === $ch || "'" === $ch ) {
					$k = $j + 1;
					while ( $k < $len && $css[ $k ] !== $ch ) {
						$k += '\\' === $css[ $k ] ? 2 : 1;
					}
					$j = $k + 1;
					continue;
				}
				$depth += '{' === $ch ? 1 : ( '}' === $ch ? -1 : 0 );
				++$j;
			}
			$body  = substr( $css, $i, $j - $i - 1 );
			$i     = $j;
			$out[] = '' !== $sel && '@' === $sel[0] ? array( 'at', $sel . '{' . $body . '}' ) : array( 'rule', $sel, $body );
			continue;
		}
		$buf .= $c;
		++$i;
	}
	return $out;
}

/**
 * Split a selector list at top-level commas.
 *
 * @param string $list Selector list.
 * @return string[]
 */
function semrs_pro_ucss_split( $list ) {
	$parts = array();
	$depth = 0;
	$cur   = '';
	$len   = strlen( $list );
	for ( $i = 0; $i < $len; $i++ ) {
		$c = $list[ $i ];
		if ( '(' === $c || '[' === $c ) {
			++$depth;
		} elseif ( ')' === $c || ']' === $c ) {
			--$depth;
		} elseif ( ',' === $c && 0 === $depth ) {
			$parts[] = $cur;
			$cur     = '';
			continue;
		}
		$cur .= $c;
	}
	$parts[] = $cur;
	return $parts;
}

/**
 * Does the page use this selector?
 *
 * @param string $sel  One selector.
 * @param array  $used Tokens from semrs_pro_ucss_used_tokens().
 * @return bool
 */
function semrs_pro_ucss_selector_used( $sel, $used ) {
	do {
		$sel = preg_replace( '/:[a-z-]+\([^()]*\)/i', '', $sel, -1, $n ); // :is(), :not(), :has(), :nth-child() …
	} while ( $n );
	$sel = preg_replace( '/\[[^\]]*\]/', '', $sel );
	if ( ! preg_match_all( '/([.#])((?:\\\\.|[a-zA-Z0-9_-]|[^\x00-\x7F])+)/', $sel, $m, PREG_SET_ORDER ) ) {
		return true; // Tags, *, :root.
	}
	foreach ( $m as $t ) {
		$name = stripslashes( $t[2] );
		if ( isset( $used[ '.' === $t[1] ? 'class' : 'id' ][ $name ] ) ) {
			continue;
		}
		if ( '.' === $t[1] && $used['safe'] && preg_match( $used['safe'], $name ) ) {
			continue;
		}
		return false;
	}
	return true;
}

/**
 * CSS text of the used rules.
 *
 * @param array $nodes Parsed CSS.
 * @param array $used  Tokens.
 * @return string
 */
function semrs_pro_ucss_reduce( $nodes, $used ) {
	$css = '';
	foreach ( $nodes as $n ) {
		if ( 'at' === $n[0] ) {
			$css .= 0 === stripos( $n[1], '@charset' ) ? '' : $n[1];
		} elseif ( 'group' === $n[0] ) {
			$inner = semrs_pro_ucss_reduce( $n[2], $used );
			$css  .= '' === $inner ? '' : $n[1] . '{' . $inner . '}';
		} else {
			$keep = array();
			foreach ( semrs_pro_ucss_split( $n[1] ) as $sel ) {
				if ( semrs_pro_ucss_selector_used( $sel, $used ) ) {
					$keep[] = trim( $sel );
				}
			}
			$css .= $keep ? implode( ',', $keep ) . '{' . $n[2] . '}' : '';
		}
	}
	return $css;
}

/**
 * Parsed copy of a local stylesheet (cached until the file changes), with
 * relative url() paths made absolute. Null when the file can't be used.
 *
 * @param string $file Local path.
 * @param string $url  Stylesheet address without query.
 * @return array|null
 */
function semrs_pro_ucss_sheet( $file, $url ) {
	static $mem = array();
	$key = md5( $file . filemtime( $file ) );
	if ( isset( $mem[ $key ] ) ) {
		return $mem[ $key ];
	}
	$dir   = function_exists( 'semrs_cache_dir' ) ? semrs_cache_dir() . 'ucss/' : '';
	$cache = $dir ? $dir . $key . '.json' : '';
	if ( $cache && is_readable( $cache ) ) {
		$mem[ $key ] = json_decode( (string) file_get_contents( $cache ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local cache file.
		return $mem[ $key ];
	}
	$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local stylesheet.
	if ( '' === $css || strlen( $css ) > 1048576 || false !== stripos( $css, '@import' ) ) {
		$mem[ $key ] = null;
		return null;
	}
	$base = dirname( $url ) . '/';
	$css  = preg_replace( '#/\*.*?\*/#s', '', $css );
	$css  = preg_replace_callback(
		'/url\(\s*(["\']?)(?!data:|https?:|\/|#)([^"\')]+)\1\s*\)/i',
		function ( $u ) use ( $base ) {
			return 'url(' . $u[1] . $base . $u[2] . $u[1] . ')';
		},
		$css
	);
	$nodes       = semrs_pro_ucss_parse( $css );
	$mem[ $key ] = $nodes;
	if ( $cache && wp_mkdir_p( $dir ) ) {
		file_put_contents( $cache, wp_json_encode( $nodes ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $nodes;
}

/**
 * Replace local stylesheets with their used rules for visitors.
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_ucss( $html ) {
	if ( is_user_logged_in() || is_feed() ) {
		return $html;
	}
	$p       = semrs_pro_speed_settings();
	$used    = semrs_pro_ucss_used_tokens( $html, $p['ucss_safelist'] );
	$exclude = semrs_pro_ucss_lines( $p['ucss_exclude'] );
	$site    = set_url_scheme( site_url( '/' ), 'https' );
	$count   = 0;
	$html    = preg_replace_callback(
		'#<link\b[^>]*rel=["\']stylesheet["\'][^>]*>#i',
		function ( $m ) use ( $used, $exclude, $site, &$count ) {
			$tag = $m[0];
			if ( ! preg_match( '/\shref=["\']([^"\']+)["\']/i', $tag, $h ) || preg_match( '/\smedia=["\']print["\']/i', $tag ) ) {
				return $tag;
			}
			foreach ( $exclude as $x ) {
				if ( false !== stripos( $tag, $x ) ) {
					return $tag;
				}
			}
			$href = html_entity_decode( $h[1] );
			$path = strtok( set_url_scheme( $href, 'https' ), '?' );
			if ( 0 !== strpos( $path, $site ) || '.css' !== strtolower( substr( $path, -4 ) ) ) {
				return $tag;
			}
			$file = ABSPATH . substr( $path, strlen( $site ) );
			if ( false !== strpos( $file, '..' ) || ! is_readable( $file ) ) {
				return $tag;
			}
			$nodes = semrs_pro_ucss_sheet( $file, $path );
			if ( null === $nodes ) {
				return $tag;
			}
			$media = preg_match( '/\smedia=["\']([^"\']*)["\']/i', $tag, $md ) ? $md[1] : 'all';
			$css   = semrs_pro_ucss_reduce( $nodes, $used );
			$full  = preg_replace( '/\smedia=["\'][^"\']*["\']/i', '', $tag );
			$lazy  = str_replace( '<link', '<link media="not all" data-semrs-ucss="' . esc_attr( $media ) . '"', $full );
			++$count;
			$style = '' === $css ? '' : '<style data-semrs-ucss-for="' . esc_attr( basename( $path ) ) . '"' . ( 'all' !== $media ? ' media="' . esc_attr( $media ) . '"' : '' ) . '>' . str_replace( '</style', '<\/style', $css ) . '</style>';
			return $style . $lazy . '<noscript>' . $tag . '</noscript>';
		},
		$html
	);
	if ( $count ) {
		$loader = '<script>(function(){var d=0,e=["keydown","mousedown","mousemove","touchstart","scroll","wheel"];function go(){if(d)return;d=1;e.forEach(function(n){removeEventListener(n,go,{passive:true})});document.querySelectorAll("link[data-semrs-ucss]").forEach(function(l){l.media=l.getAttribute("data-semrs-ucss")})}e.forEach(function(n){addEventListener(n,go,{passive:true})})})();</script>';
		$html   = preg_replace( '#</body>#i', $loader . '</body>', $html, 1 );
	}
	return $html;
}
