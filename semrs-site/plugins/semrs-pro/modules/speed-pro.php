<?php
/**
 * Speed Pro: delay analytics, pixel and chat scripts until the visitor
 * interacts; skip form and block-plugin files on pages that do not use
 * them; minify stylesheets, add missing image sizes, lazy-load iframes;
 * preload the SEMRS page cache in the background after it
 * clears; serve static files from a CDN. Settings live on SEMRS → Speed →
 * Optimize. Needs the SEMRS Core Speed module.
 *
 * AdSense and other ad scripts are never delayed (ads must load normally).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_speed_settings' ) ) {
	return; // Core Speed module is off.
}

/**
 * Default script patterns to delay.
 *
 * @return string
 */
function semrs_pro_speed_default_delay() {
	return implode(
		"\n",
		array( 'googletagmanager.com', 'google-analytics.com', 'gtag(', 'connect.facebook.net', 'fbq(', 'clarity.ms', 'static.hotjar.com', 'analytics.tiktok.com', 'snap.licdn.com', 'embed.tawk.to', 'client.crisp.chat', 'widget.intercom.io', 'js.hs-scripts.com' )
	);
}

/**
 * Settings.
 *
 * @return array
 */
function semrs_pro_speed_settings() {
	$saved = get_option( 'semrs_speed_pro', array() );
	return array_merge(
		array(
			'delay'         => false,
			'delay_list'    => semrs_pro_speed_default_delay(),
			'delay_timeout' => 0,
			'unload'        => false,
			'minify_css'    => false,
			'dims'          => false,
			'lazy_iframes'  => false,
			'preload'       => false,
			'cdn'           => '',
		),
		is_array( $saved ) ? $saved : array()
	);
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'semrs_speed',
			'semrs_speed_pro',
			array(
				'sanitize_callback' => function ( $in ) {
					// The Page cache tab posts the same settings group without these fields: keep them.
					if ( ! is_array( $in ) ) {
						return get_option( 'semrs_speed_pro', array() );
					}
					if ( empty( $in['_present'] ) && ! array_key_exists( 'delay', $in ) ) {
						return get_option( 'semrs_speed_pro', array() );
					}
					$in  = wp_unslash( $in );
					$cdn = esc_url_raw( trim( (string) ( $in['cdn'] ?? '' ) ), array( 'https', 'http' ) );
					return array(
						'delay'         => ! empty( $in['delay'] ),
						'delay_list'    => sanitize_textarea_field( $in['delay_list'] ?? '' ),
						'delay_timeout' => min( 30, absint( $in['delay_timeout'] ?? 0 ) ),
						'unload'        => ! empty( $in['unload'] ),
						'minify_css'    => ! empty( $in['minify_css'] ),
						'dims'          => ! empty( $in['dims'] ),
						'lazy_iframes'  => ! empty( $in['lazy_iframes'] ),
						'preload'       => ! empty( $in['preload'] ),
						'cdn'           => untrailingslashit( $cdn ),
					);
				},
			)
		);
	}
);

add_action(
	'semrs_speed_optimize_fields',
	function () {
		$p = semrs_pro_speed_settings();
		?>
		<tr><th colspan="2"><h2 style="margin:12px 0 0"><?php esc_html_e( 'SEMRS Pro', 'semrs-pro' ); ?></h2><input type="hidden" name="semrs_speed_pro[_present]" value="1"></th></tr>
		<tr><th><?php esc_html_e( 'Delay JavaScript until the visitor interacts', 'semrs-pro' ); ?></th><td>
			<label><input type="checkbox" name="semrs_speed_pro[delay]" value="1" <?php checked( $p['delay'] ); ?>> <?php esc_html_e( 'On', 'semrs-pro' ); ?></label>
			<p class="description"><?php esc_html_e( 'Matching scripts wait until the first scroll, tap, mouse move or key press, then run in their original order. Ad scripts such as AdSense are never delayed.', 'semrs-pro' ); ?></p>
			<textarea name="semrs_speed_pro[delay_list]" rows="5" class="large-text code"><?php echo esc_textarea( $p['delay_list'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'One pattern per line, matched against the script address or its inline code.', 'semrs-pro' ); ?></p>
			<label><?php esc_html_e( 'Also run them after', 'semrs-pro' ); ?> <input type="number" min="0" max="30" class="small-text" name="semrs_speed_pro[delay_timeout]" value="<?php echo esc_attr( $p['delay_timeout'] ); ?>"> <?php esc_html_e( 'seconds without interaction (0 = only on interaction)', 'semrs-pro' ); ?></label>
		</td></tr>
		<tr><th><?php esc_html_e( 'Unused plugin files', 'semrs-pro' ); ?></th><td>
			<label><input type="checkbox" name="semrs_speed_pro[unload]" value="1" <?php checked( $p['unload'] ); ?>> <?php esc_html_e( 'Load form and block-plugin files only on pages that use them', 'semrs-pro' ); ?></label>
			<p class="description"><?php esc_html_e( 'Contact Form 7 (with its reCAPTCHA and Turnstile scripts) loads only where a form appears in the content or a widget; Spectra files load only where a Spectra block appears.', 'semrs-pro' ); ?></p>
		</td></tr>
		<tr><th><?php esc_html_e( 'Files and media', 'semrs-pro' ); ?></th><td>
			<label><input type="checkbox" name="semrs_speed_pro[minify_css]" value="1" <?php checked( $p['minify_css'] ); ?>> <?php esc_html_e( 'Minify stylesheets (smaller CSS files, cached until the file changes)', 'semrs-pro' ); ?></label><br>
			<label><input type="checkbox" name="semrs_speed_pro[dims]" value="1" <?php checked( $p['dims'] ); ?>> <?php esc_html_e( 'Add missing width and height to images (stops the page jumping while it loads)', 'semrs-pro' ); ?></label><br>
			<label><input type="checkbox" name="semrs_speed_pro[lazy_iframes]" value="1" <?php checked( $p['lazy_iframes'] ); ?>> <?php esc_html_e( 'Lazy-load iframes such as maps and embedded videos', 'semrs-pro' ); ?></label>
		</td></tr>
		<tr><th><?php esc_html_e( 'Cache preload', 'semrs-pro' ); ?></th><td>
			<label><input type="checkbox" name="semrs_speed_pro[preload]" value="1" <?php checked( $p['preload'] ); ?>> <?php esc_html_e( 'Rebuild the page cache in the background after it clears', 'semrs-pro' ); ?></label>
			<p class="description"><?php esc_html_e( 'Visits the homepage and your 200 newest pages, a few at a time, for phones and computers. Needs the page cache to be on.', 'semrs-pro' ); ?></p>
		</td></tr>
		<tr><th><label for="semrs-cdn"><?php esc_html_e( 'CDN address', 'semrs-pro' ); ?></label></th><td>
			<input id="semrs-cdn" type="url" class="regular-text code" name="semrs_speed_pro[cdn]" value="<?php echo esc_attr( $p['cdn'] ); ?>" placeholder="https://cdn.example.com">
			<p class="description"><?php esc_html_e( 'Images, CSS, JavaScript and fonts from wp-content and wp-includes are loaded from this address. Leave empty if you do not use a pull CDN (Cloudflare does not need this).', 'semrs-pro' ); ?></p>
		</td></tr>
		<?php
	}
);

/**
 * Rewrite matching scripts so they wait for interaction.
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_speed_delay( $html ) {
	$p    = semrs_pro_speed_settings();
	$pats = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $p['delay_list'] ) ) );
	if ( ! $pats ) {
		return $html;
	}
	$count = 0;
	$html  = preg_replace_callback(
		'#<script\b([^>]*)>(.*?)</script>#is',
		function ( $m ) use ( $pats, &$count ) {
			$attrs = $m[1];
			if ( preg_match( '/\stype=["\']?([^"\'\s>]+)/i', $attrs, $t ) && ! in_array( strtolower( $t[1] ), array( 'text/javascript', 'application/javascript', 'module' ), true ) ) {
				return $m[0]; // JSON-LD, templates, speculation rules…
			}
			if ( preg_match( '/adsbygoogle|googlesyndication|doubleclick|googletag\.(cmd|pubads)|data-no-delay/i', $m[0] ) ) {
				return $m[0];
			}
			$hit = false;
			foreach ( $pats as $pat ) {
				if ( false !== stripos( $m[0], $pat ) ) {
					$hit = true;
					break;
				}
			}
			if ( ! $hit ) {
				return $m[0];
			}
			$count++;
			$attrs = preg_replace( '/\stype=["\']?[^"\'\s>]+["\']?/i', '', $attrs );
			$attrs = preg_replace( '/\ssrc=(["\'])/i', ' data-semrs-src=$1', $attrs );
			return '<script type="semrs/delay"' . $attrs . '>' . $m[2] . '</script>';
		},
		$html
	);
	if ( ! $count ) {
		return $html;
	}
	$timeout = (int) $p['delay_timeout'];
	$loader  = '<script>(function(){var d=0,e=["keydown","mousedown","mousemove","touchstart","scroll","wheel"];function go(){if(d)return;d=1;e.forEach(function(n){removeEventListener(n,go,{passive:true})});var l=[].slice.call(document.querySelectorAll(\'script[type="semrs/delay"]\'));(function nx(){var o=l.shift();if(!o)return;var s=document.createElement("script");[].slice.call(o.attributes).forEach(function(a){if(a.name!=="type"&&a.name!=="data-semrs-src")s.setAttribute(a.name,a.value)});if(o.getAttribute("data-semrs-src")){s.src=o.getAttribute("data-semrs-src");s.async=false;s.onload=s.onerror=nx;o.parentNode.replaceChild(s,o)}else{s.text=o.text;o.parentNode.replaceChild(s,o);nx()}})()}e.forEach(function(n){addEventListener(n,go,{passive:true})});' . ( $timeout ? 'setTimeout(go,' . ( $timeout * 1000 ) . ');' : '' ) . '})();</script>';
	$pos     = strripos( $html, '</body>' );
	return false === $pos ? $html . $loader : substr_replace( $html, $loader, $pos, 0 );
}

/**
 * Add width and height to local images that have neither, so the page does
 * not jump while images load (Cumulative Layout Shift).
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_speed_dims( $html ) {
	$up   = wp_get_upload_dir();
	$urls = array( set_url_scheme( $up['baseurl'], 'https' ), set_url_scheme( $up['baseurl'], 'http' ) );
	return preg_replace_callback(
		'#<img\b[^>]*>#i',
		function ( $m ) use ( $up, $urls ) {
			$tag = $m[0];
			if ( preg_match( '/\s(width|height)=/i', $tag ) || ! preg_match( '/\ssrc=["\']([^"\']+)["\']/i', $tag, $src ) ) {
				return $tag;
			}
			$rel = str_replace( $urls, '', strtok( $src[1], '?' ) );
			if ( $rel === $src[1] || false !== strpos( $rel, '..' ) || ! preg_match( '/\.(jpe?g|png|gif|webp|avif)$/i', $rel ) ) {
				return $tag;
			}
			$size = @getimagesize( $up['basedir'] . rawurldecode( $rel ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- missing files are skipped.
			if ( empty( $size[0] ) ) {
				return $tag;
			}
			return preg_replace( '/^<img\b/i', '<img width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"', $tag );
		},
		$html
	);
}

/**
 * Lazy-load iframes (maps, videos, forms) that do not say otherwise.
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_speed_lazy_iframes( $html ) {
	return preg_replace( '#<iframe\b(?![^>]*\sloading=)#i', '<iframe loading="lazy"', $html );
}

/**
 * Minify one CSS string: comments (except /*! licences), extra spaces and
 * the last semicolon in each block.
 *
 * @param string $css CSS.
 * @return string
 */
function semrs_pro_speed_min_css( $css ) {
	$css = preg_replace( '#/\*(?!!).*?\*/#s', '', $css );
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = preg_replace( '/\s*([{};,])\s*/', '$1', $css );
	$css = preg_replace( '/:\s+/', ':', $css );
	return trim( str_replace( ';}', '}', $css ) );
}

/**
 * Serve minified copies of local stylesheets (cached until the file
 * changes). Relative url() paths are made absolute so fonts and images
 * still load. Files already named .min.css are left alone.
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_speed_minify( $html ) {
	if ( ! function_exists( 'semrs_cache_dir' ) ) {
		return $html;
	}
	$dir  = semrs_cache_dir() . 'min/';
	$url  = content_url( 'cache/semrs/min/' );
	$site = set_url_scheme( site_url( '/' ), 'https' );
	return preg_replace_callback(
		'#<link\b[^>]*rel=["\']stylesheet["\'][^>]*>#i',
		function ( $m ) use ( $dir, $url, $site ) {
			if ( ! preg_match( '/\shref=["\']([^"\']+)["\']/i', $m[0], $h ) ) {
				return $m[0];
			}
			$href = html_entity_decode( $h[1] );
			$path = strtok( set_url_scheme( $href, 'https' ), '?' );
			if ( 0 !== strpos( $path, $site ) || ! preg_match( '/(?<!\.min)\.css$/i', $path ) ) {
				return $m[0];
			}
			$file = ABSPATH . substr( $path, strlen( $site ) );
			if ( false !== strpos( $file, '..' ) || ! is_readable( $file ) ) {
				return $m[0];
			}
			$name = md5( $file . filemtime( $file ) ) . '.css';
			if ( ! file_exists( $dir . $name ) ) {
				$base = dirname( $path ) . '/';
				$css  = preg_replace_callback(
					'/url\(\s*(["\']?)(?!data:|https?:|\/|#)([^"\')]+)\1\s*\)/i',
					function ( $u ) use ( $base ) {
						return 'url(' . $u[1] . $base . $u[2] . $u[1] . ')';
					},
					(string) file_get_contents( $file ) // phpcs:ignore WordPress.WP.AlternativeFunctions -- local stylesheet.
				);
				if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $dir . $name, semrs_pro_speed_min_css( $css ), LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
					return $m[0];
				}
			}
			return str_replace( $h[1], esc_url( $url . $name ), $m[0] );
		},
		$html
	);
}

/**
 * Point static files at the CDN.
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_speed_cdn( $html ) {
	$cdn  = semrs_pro_speed_settings()['cdn'];
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$path = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	if ( ! $cdn || ! $host ) {
		return $html;
	}
	return preg_replace(
		'#(?:https?:)?//' . preg_quote( $host, '#' ) . '(' . preg_quote( $path, '#' ) . '/wp-(?:content|includes)/[^"\'\s)?]+\.(?:jpe?g|png|gif|webp|avif|svg|ico|css|js|woff2?|ttf|otf|mp4|webm))#i',
		$cdn . '$1',
		$html
	);
}

$semrs_pro_speed = semrs_pro_speed_settings();
if ( $semrs_pro_speed['delay'] || $semrs_pro_speed['cdn'] || $semrs_pro_speed['minify_css'] || $semrs_pro_speed['dims'] || $semrs_pro_speed['lazy_iframes'] ) {
	add_filter( 'semrs_speed_html', 'semrs_pro_speed_html' );
}
if ( $semrs_pro_speed['unload'] && ! is_admin() ) {
	add_action( 'wp_enqueue_scripts', 'semrs_pro_speed_unload', 999 );
	add_action( 'wp_print_scripts', 'semrs_pro_speed_unload', 1 );
	add_action( 'wp_print_footer_scripts', 'semrs_pro_speed_unload', 1 );
}
unset( $semrs_pro_speed );

/**
 * Content that can hold forms and blocks on this request: the post (with
 * any synced patterns it uses) and the block widgets.
 *
 * @return string
 */
function semrs_pro_speed_page_content() {
	static $text = null;
	if ( null !== $text ) {
		return $text;
	}
	$text = '';
	if ( is_singular() ) {
		$post = get_post();
		$text = $post ? (string) $post->post_content : '';
		if ( preg_match_all( '/<!-- wp:block \{"ref":(\d+)/', $text, $refs ) ) {
			foreach ( array_unique( $refs[1] ) as $ref ) {
				$text .= (string) get_post_field( 'post_content', (int) $ref );
			}
		}
	}
	// Widgets placed in a sidebar (inactive ones are never shown).
	$stores = array(
		'block' => array( (array) get_option( 'widget_block', array() ), 'content' ),
		'text'  => array( (array) get_option( 'widget_text', array() ), 'text' ),
	);
	foreach ( wp_get_sidebars_widgets() as $sidebar => $ids ) {
		if ( 'wp_inactive_widgets' === $sidebar || ! is_array( $ids ) ) {
			continue;
		}
		foreach ( $ids as $id ) {
			if ( preg_match( '/^(block|text)-(\d+)$/', (string) $id, $m ) ) {
				list( $store, $field ) = $stores[ $m[1] ];
				$text .= (string) ( $store[ (int) $m[2] ][ $field ] ?? '' );
			}
		}
	}
	return $text;
}

/**
 * Dequeue plugin files the current page does not use.
 */
function semrs_pro_speed_unload() {
	if ( is_customize_preview() || is_preview() ) {
		return;
	}
	$text = semrs_pro_speed_page_content();
	if ( false !== strpos( $text, '<!-- wp:pattern' ) ) {
		return; // Patterns are resolved at render time: keep everything.
	}
	$rules = array(
		// Contact Form 7 and the captcha scripts it adds.
		array(
			'needs'   => '#wp:contact-form-7/|\[contact-form(-7)?[\s\]]#',
			'scripts' => array( 'contact-form-7', 'swv', 'wpcf7-recaptcha', 'google-recaptcha', 'cloudflare-turnstile', 'googlesitekit-events-provider-contact-form-7' ),
			'styles'  => array( 'contact-form-7', 'contact-form-7-rtl' ),
			'prefix'  => '',
		),
		// Spectra (Ultimate Addons for Gutenberg).
		array(
			'needs'   => '#wp:uagb/#',
			'scripts' => array(),
			'styles'  => array(),
			'prefix'  => '/^(uagb-|uag-)/',
		),
	);
	$rules = apply_filters( 'semrs_pro_speed_unload_rules', $rules );
	foreach ( $rules as $rule ) {
		if ( 0 !== preg_match( $rule['needs'], $text ) ) {
			continue; // Used here, or a bad pattern from the filter: keep the files.
		}
		foreach ( array( 'scripts' => wp_scripts(), 'styles' => wp_styles() ) as $kind => $deps ) {
			foreach ( (array) $deps->queue as $handle ) {
				if ( in_array( $handle, $rule[ $kind ], true ) || ( $rule['prefix'] && preg_match( $rule['prefix'], $handle ) ) ) {
					'scripts' === $kind ? wp_dequeue_script( $handle ) : wp_dequeue_style( $handle );
				}
			}
		}
	}
}

/**
 * Final-HTML changes (through SEMRS Core's page buffer).
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_pro_speed_html( $html ) {
	if ( is_admin() || is_customize_preview() || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) ) {
		return $html;
	}
	$p = semrs_pro_speed_settings();
	if ( $p['minify_css'] ) {
		$html = semrs_pro_speed_minify( $html );
	}
	if ( $p['dims'] ) {
		$html = semrs_pro_speed_dims( $html );
	}
	if ( $p['lazy_iframes'] ) {
		$html = semrs_pro_speed_lazy_iframes( $html );
	}
	if ( $p['delay'] ) {
		$html = semrs_pro_speed_delay( $html );
	}
	if ( $p['cdn'] ) {
		$html = semrs_pro_speed_cdn( $html );
	}
	return $html;
}

/**
 * Queue a cache preload after the cache clears.
 */
add_action(
	'semrs_cache_purged',
	function () {
		if ( ! semrs_pro_speed_settings()['preload'] || ! function_exists( 'semrs_cache_enabled' ) || ! semrs_cache_enabled() ) {
			return;
		}
		$urls = array( home_url( '/' ) );
		$ids  = get_posts(
			array(
				'post_type'      => function_exists( 'semrs_seo_public_types' ) ? semrs_seo_public_types() : array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'has_password'   => false,
			)
		);
		foreach ( $ids as $id ) {
			$urls[] = get_permalink( $id );
		}
		update_option( 'semrs_cache_preload_queue', array_values( array_unique( $urls ) ), false );
		if ( ! wp_next_scheduled( 'semrs_cache_preload' ) ) {
			wp_schedule_single_event( time() + 30, 'semrs_cache_preload' );
		}
	}
);

/**
 * Preload a few URLs per run (computer and phone copies).
 */
add_action(
	'semrs_cache_preload',
	function () {
		$queue = (array) get_option( 'semrs_cache_preload_queue', array() );
		if ( ! $queue || ! semrs_cache_enabled() ) {
			delete_option( 'semrs_cache_preload_queue' );
			return;
		}
		$agents = array(
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SEMRS-Preload',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile SEMRS-Preload',
		);
		foreach ( array_splice( $queue, 0, 5 ) as $url ) {
			foreach ( $agents as $ua ) {
				wp_remote_get(
					$url,
					array(
						'timeout'    => 15,
						'sslverify'  => false,
						'user-agent' => $ua,
					)
				);
			}
		}
		if ( $queue ) {
			update_option( 'semrs_cache_preload_queue', $queue, false );
			wp_schedule_single_event( time() + 60, 'semrs_cache_preload' );
		} else {
			delete_option( 'semrs_cache_preload_queue' );
		}
	}
);
