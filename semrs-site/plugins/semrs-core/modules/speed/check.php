<?php
/**
 * Speed check (SEMRS → Speed → Speed check): loads your homepage twice from
 * your own server and reports response time, page cache, compression, page
 * weight, render-blocking files, images without sizes and server settings,
 * each with a plain-language fix. No outside service is used.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the check.
 *
 * @return array
 */
function semrs_speed_run_check() {
	$url  = home_url( '/' );
	$args = array(
		'timeout'    => 20,
		'sslverify'  => false,
		'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SEMRS-Speed-Check',
	);
	$r     = array( 'time' => time() );
	$times = array();
	$res   = null;
	for ( $i = 0; $i < 2; $i++ ) {
		$t0  = microtime( true );
		$res = wp_remote_get( $url, $args );
		if ( is_wp_error( $res ) ) {
			$r['error'] = $res->get_error_message();
			return $r;
		}
		$times[] = (int) round( ( microtime( true ) - $t0 ) * 1000 );
	}
	$html = (string) wp_remote_retrieve_body( $res );
	$head = stristr( $html, '</head>', true );
	$head = false === $head ? '' : $head;
	$host = wp_parse_url( $url, PHP_URL_HOST );

	preg_match_all( '/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/i', $head, $css );
	$blocking_css = 0;
	foreach ( $css[0] as $tag ) {
		if ( ! preg_match( '/media=["\']print["\']/i', $tag ) ) {
			$blocking_css++;
		}
	}
	preg_match_all( '/<script\b[^>]*\ssrc=["\']([^"\']+)["\'][^>]*>/i', $html, $js );
	$blocking_js = 0;
	$third       = array();
	foreach ( $js[0] as $n => $tag ) {
		if ( false !== strpos( $head, $tag ) && ! preg_match( '/\s(defer|async)\b|type=["\']module["\']/i', $tag ) ) {
			$blocking_js++;
		}
		$h = wp_parse_url( $js[1][ $n ], PHP_URL_HOST );
		if ( $h && strtolower( $h ) !== strtolower( $host ) ) {
			$third[ $h ] = true;
		}
	}
	preg_match_all( '/<img\b[^>]*>/i', $html, $imgs );
	$nosize = 0;
	foreach ( $imgs[0] as $tag ) {
		if ( ! preg_match( '/\swidth=/i', $tag ) || ! preg_match( '/\sheight=/i', $tag ) ) {
			$nosize++;
		}
	}

	global $wpdb;
	$autoload = (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto-on','auto')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$big      = 0;
	$images   = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'posts_per_page' => 300,
			'fields'         => 'ids',
			'post_status'    => 'inherit',
		)
	);
	foreach ( $images as $id ) {
		$f = get_attached_file( $id );
		if ( $f && file_exists( $f ) && filesize( $f ) > 300 * KB_IN_BYTES ) {
			$big++;
		}
	}

	$r += array(
		'first'        => $times[0],
		'second'       => $times[1],
		'cache'        => (string) wp_remote_retrieve_header( $res, 'x-semrs-cache' ),
		'encoding'     => (string) wp_remote_retrieve_header( $res, 'content-encoding' ),
		'kb'           => (int) round( strlen( $html ) / KB_IN_BYTES ),
		'css'          => count( $css[0] ),
		'blocking_css' => $blocking_css,
		'js'           => count( $js[0] ),
		'blocking_js'  => $blocking_js,
		'third'        => array_keys( $third ),
		'imgs'         => count( $imgs[0] ),
		'nosize'       => $nosize,
		'autoload'     => (int) round( $autoload / KB_IN_BYTES ),
		'big_images'   => $big,
		'webp'         => wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ),
	);
	update_option( 'semrs_speed_check', $r, false );
	return $r;
}

/**
 * Findings: [status, label, help].
 *
 * @param array $r Check result.
 * @return array
 */
function semrs_speed_findings( $r ) {
	$f    = array();
	$fast = min( $r['first'], $r['second'] );
	/* translators: %d: milliseconds */
	$f[] = $fast <= 600 ? array( 'good', sprintf( __( 'Server response: %d ms', 'semrs-core' ), $fast ), '' ) : array( $fast <= 1500 ? 'warn' : 'bad', sprintf( __( 'Server response: %d ms', 'semrs-core' ), $fast ), __( 'Aim for under 600 ms. Turn on the page cache (or your host\'s cache); if it stays slow, ask your host about PHP 8.2+ and server resources.', 'semrs-core' ) );
	if ( 'HIT' === $r['cache'] ) {
		$f[] = array( 'good', __( 'Page cache is working (served from cache)', 'semrs-core' ), '' );
	} elseif ( semrs_cache_enabled() ) {
		$f[] = array( 'warn', __( 'Page cache is on, but the homepage was not served from it', 'semrs-core' ), __( 'Run the check again. If it still says so, the homepage may be excluded or a plugin sets a cookie for every visitor.', 'semrs-core' ) );
	} else {
		$conf = semrs_cache_conflict();
		/* translators: %s: plugin name */
		$f[]  = $conf ? array( 'good', sprintf( __( 'Page cache handled by %s', 'semrs-core' ), $conf ), '' ) : array( 'warn', __( 'No page cache', 'semrs-core' ), __( 'Turn on the SEMRS page cache on the Page cache tab (or use your host\'s cache). It is the biggest speed win for most sites.', 'semrs-core' ) );
	}
	$f[] = $r['encoding'] ? array( 'good', sprintf( /* translators: %s: gzip or br */ __( 'Compression is on (%s)', 'semrs-core' ), $r['encoding'] ), '' ) : array( 'warn', __( 'No compression detected', 'semrs-core' ), __( 'Ask your host to turn on Gzip or Brotli; it makes pages about 70% smaller to download. (Some local test servers never compress.)', 'semrs-core' ) );
	/* translators: %d: kilobytes */
	$f[] = $r['kb'] <= 150 ? array( 'good', sprintf( __( 'Homepage HTML: %d KB', 'semrs-core' ), $r['kb'] ), '' ) : array( 'warn', sprintf( __( 'Homepage HTML: %d KB', 'semrs-core' ), $r['kb'] ), __( 'Large HTML is slow on phones. Show fewer posts on the homepage or split long pages.', 'semrs-core' ) );
	/* translators: %d: files */
	$f[] = $r['blocking_js'] <= 1 ? array( 'good', sprintf( __( 'Render-blocking scripts in the head: %d', 'semrs-core' ), $r['blocking_js'] ), '' ) : array( 'warn', sprintf( __( 'Render-blocking scripts in the head: %d', 'semrs-core' ), $r['blocking_js'] ), __( 'Turn on "Defer front-end scripts" on the Optimize tab and test your site.', 'semrs-core' ) );
	/* translators: %d: files */
	$f[] = $r['blocking_css'] <= 6 ? array( 'good', sprintf( __( 'Stylesheets in the head: %d', 'semrs-core' ), $r['blocking_css'] ), '' ) : array( 'warn', sprintf( __( 'Stylesheets in the head: %d', 'semrs-core' ), $r['blocking_css'] ), __( 'Turn on "Inline small stylesheets", or load plugin CSS that is not needed at the top of the page without blocking.', 'semrs-core' ) );
	/* translators: 1: images, 2: images without sizes */
	$f[] = 0 === $r['nosize'] ? array( 'good', sprintf( __( 'All %d homepage images have width and height', 'semrs-core' ), $r['imgs'] ), '' ) : array( 'warn', sprintf( __( '%2$d of %1$d homepage images have no width or height', 'semrs-core' ), $r['imgs'], $r['nosize'] ), __( 'Images without sizes make the page jump while loading (layout shift). Insert images from the Media Library so WordPress adds sizes.', 'semrs-core' ) );
	if ( $r['third'] ) {
		/* translators: %s: host names */
		$f[] = array( count( $r['third'] ) > 4 ? 'warn' : 'good', sprintf( __( 'Scripts from other sites: %s', 'semrs-core' ), implode( ', ', array_slice( $r['third'], 0, 8 ) ) ), count( $r['third'] ) > 4 ? __( 'Each outside script costs time. Remove tags you no longer use. SEMRS Pro can delay analytics and chat scripts until the visitor interacts.', 'semrs-core' ) : '' );
	}
	/* translators: %d: images */
	$f[] = 0 === $r['big_images'] ? array( 'good', __( 'No oversized JPEG or PNG uploads found', 'semrs-core' ), '' ) : array( 'warn', sprintf( __( '%d uploaded images are over 300 KB', 'semrs-core' ), $r['big_images'] ), $r['webp'] ? __( 'Compress them before uploading, or upload WebP versions (your server supports WebP).', 'semrs-core' ) : __( 'Compress them before uploading (for example with Squoosh).', 'semrs-core' ) );
	/* translators: %s: PHP version */
	$f[] = version_compare( PHP_VERSION, '8.1', '>=' ) ? array( 'good', sprintf( __( 'PHP %s', 'semrs-core' ), PHP_VERSION ), '' ) : array( 'warn', sprintf( __( 'PHP %s', 'semrs-core' ), PHP_VERSION ), __( 'PHP 8.1 or newer is much faster. Your host can switch it in the control panel.', 'semrs-core' ) );
	$f[] = wp_using_ext_object_cache() ? array( 'good', __( 'Persistent object cache is active', 'semrs-core' ), '' ) : array( 'warn', __( 'No persistent object cache', 'semrs-core' ), __( 'Optional: if your host offers Redis or Memcached, turning it on speeds up the admin and uncached pages.', 'semrs-core' ) );
	/* translators: %d: kilobytes */
	$f[] = $r['autoload'] <= 800 ? array( 'good', sprintf( __( 'Autoloaded options: %d KB', 'semrs-core' ), $r['autoload'] ), '' ) : array( 'warn', sprintf( __( 'Autoloaded options: %d KB', 'semrs-core' ), $r['autoload'] ), __( 'Over 800 KB is loaded on every request. Old plugins often leave settings behind; remove plugins you do not use.', 'semrs-core' ) );
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$f[] = array( 'warn', __( 'Debug mode is on', 'semrs-core' ), __( 'Set WP_DEBUG to false in wp-config.php on a live site.', 'semrs-core' ) );
	}
	return $f;
}

/**
 * Speed check tab.
 */
function semrs_speed_check_tab() {
	$r = null;
	if ( isset( $_GET['run'] ) && check_admin_referer( 'semrs_speed_check' ) ) {
		$r = semrs_speed_run_check();
	} else {
		$r = get_option( 'semrs_speed_check' );
	}
	$run = wp_nonce_url( admin_url( 'admin.php?page=semrs-speed&tab=check&run=1' ), 'semrs_speed_check' );
	?>
	<p><?php esc_html_e( 'Loads your homepage twice from your own server and checks the things that make the biggest difference. For real-world phone scores, also test with Google PageSpeed Insights.', 'semrs-core' ); ?></p>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( $run ); ?>"><?php esc_html_e( 'Run speed check', 'semrs-core' ); ?></a>
		<a class="button" href="<?php echo esc_url( 'https://pagespeed.web.dev/analysis?url=' . rawurlencode( home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open PageSpeed Insights', 'semrs-core' ); ?></a>
	</p>
	<?php
	if ( ! $r ) {
		return;
	}
	if ( ! empty( $r['error'] ) ) {
		/* translators: %s: error */
		echo '<div class="notice notice-warning inline"><p>' . esc_html( sprintf( __( 'Your server could not load its own homepage: %s. Some hosts block these "loopback" requests; use PageSpeed Insights instead.', 'semrs-core' ), $r['error'] ) ) . '</p></div>';
		return;
	}
	?>
	<p class="description">
	<?php
	/* translators: 1: time ago, 2: first load ms, 3: second load ms */
	echo esc_html( sprintf( __( 'Checked %1$s ago. First load %2$d ms, second load %3$d ms.', 'semrs-core' ), human_time_diff( $r['time'] ), $r['first'], $r['second'] ) );
	?>
	</p>
	<ul class="semrs-site-checks" style="max-width:860px">
		<?php foreach ( semrs_speed_findings( $r ) as $c ) : ?>
			<li><span class="dot <?php echo esc_attr( $c[0] ); ?>"></span><strong><?php echo esc_html( $c[1] ); ?></strong><?php echo $c[2] ? '<p>' . esc_html( $c[2] ) . '</p>' : ''; ?></li>
		<?php endforeach; ?>
	</ul>
	<?php
}
