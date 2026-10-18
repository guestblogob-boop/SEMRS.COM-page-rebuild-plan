<?php
/**
 * Speed Pro: delay analytics, pixel and chat scripts until the visitor
 * interacts; preload the SEMRS page cache in the background after it
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
if ( $semrs_pro_speed['delay'] || $semrs_pro_speed['cdn'] ) {
	add_filter( 'semrs_speed_html', 'semrs_pro_speed_html' );
}
unset( $semrs_pro_speed );

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
