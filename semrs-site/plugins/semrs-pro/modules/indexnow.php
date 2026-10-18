<?php
/**
 * IndexNow: tells IndexNow search engines (Bing, Yandex, Seznam, Naver…)
 * about new, updated and deleted URLs immediately. Google does not use
 * IndexNow; it reads your sitemap.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * The site key (created once).
 *
 * @return string
 */
function semrs_indexnow_key() {
	$key = (string) get_option( 'semrs_indexnow_key', '' );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $key ) ) {
		$key = md5( wp_generate_password( 32, false ) . home_url() );
		update_option( 'semrs_indexnow_key', $key, true );
	}
	return $key;
}

/**
 * Serve the key file at /<key>.txt (required by IndexNow to prove ownership).
 */
function semrs_indexnow_serve_key() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$key  = semrs_indexnow_key();
	if ( '/' . $key . '.txt' === $path ) {
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo $key; // phpcs:ignore WordPress.Security.EscapeOutput -- 32 hex characters.
		exit;
	}
}
add_action( 'init', 'semrs_indexnow_serve_key', 0 );

/**
 * True on local/staging hosts where pings are pointless.
 *
 * @return bool
 */
function semrs_indexnow_is_local() {
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || (bool) preg_match( '/\.(test|local|localhost|invalid|example)$|^(test|staging|dev)\./', $host ) || 'production' !== wp_get_environment_type() && 'production' !== (string) apply_filters( 'semrs_indexnow_environment', wp_get_environment_type() );
}

/**
 * Queue a post's URL when it is published, updated or unpublished.
 *
 * @param string  $new  New status.
 * @param string  $old  Old status.
 * @param WP_Post $post Post.
 */
function semrs_indexnow_queue( $new, $old, $post ) {
	if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) || ! is_post_type_viewable( $post->post_type ) ) {
		return;
	}
	if ( 'publish' !== $new && 'publish' !== $old ) {
		return; // Drafts never existed publicly.
	}
	if ( 'publish' === $new && function_exists( 'semrs_seo_is_noindex' ) && semrs_seo_is_noindex( $post->ID ) ) {
		return;
	}
	$url = get_permalink( $post );
	if ( $url ) {
		$GLOBALS['semrs_indexnow_urls'][ $url ] = true;
	}
}
add_action( 'transition_post_status', 'semrs_indexnow_queue', 10, 3 );

/**
 * Send queued URLs once at the end of the request (non-blocking).
 */
function semrs_indexnow_send() {
	if ( empty( $GLOBALS['semrs_indexnow_urls'] ) || semrs_indexnow_is_local() ) {
		return;
	}
	$urls = array_slice( array_keys( $GLOBALS['semrs_indexnow_urls'] ), 0, 100 );
	$key  = semrs_indexnow_key();
	wp_remote_post(
		'https://api.indexnow.org/indexnow',
		array(
			'blocking' => false,
			'timeout'  => 5,
			'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'     => wp_json_encode(
				array(
					'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
					'key'         => $key,
					'keyLocation' => home_url( '/' . $key . '.txt' ),
					'urlList'     => $urls,
				)
			),
		)
	);
	$log = (array) get_option( 'semrs_indexnow_log', array() );
	array_unshift( $log, array( 'time' => time(), 'urls' => $urls ) );
	update_option( 'semrs_indexnow_log', array_slice( $log, 0, 20 ), false );
}
add_action( 'shutdown', 'semrs_indexnow_send' );

if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs', __( 'IndexNow', 'semrs-pro' ), __( 'IndexNow', 'semrs-pro' ), 'manage_options', 'semrs-indexnow', 'semrs_indexnow_page' );
		},
		30
	);
}

/**
 * Status page.
 */
function semrs_indexnow_page() {
	$key = semrs_indexnow_key();
	$log = (array) get_option( 'semrs_indexnow_log', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'IndexNow', 'semrs-pro' ); ?></h1>
		<p><?php esc_html_e( 'New, updated and deleted posts and pages are submitted automatically to IndexNow search engines (Bing, Yandex, Seznam, Naver and others). Google does not use IndexNow; it reads your XML sitemap.', 'semrs-pro' ); ?></p>
		<p><strong><?php esc_html_e( 'Key file:', 'semrs-pro' ); ?></strong> <a href="<?php echo esc_url( home_url( '/' . $key . '.txt' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/' . $key . '.txt' ) ); ?></a></p>
		<?php if ( semrs_indexnow_is_local() ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'This looks like a local or staging site, so submissions are paused. They start automatically on your live site.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>
		<h2><?php esc_html_e( 'Recent submissions', 'semrs-pro' ); ?></h2>
		<table class="widefat striped" style="max-width:900px"><tbody>
		<?php if ( ! $log ) : ?>
			<tr><td><?php esc_html_e( 'Nothing submitted yet.', 'semrs-pro' ); ?></td></tr>
		<?php endif; ?>
		<?php foreach ( $log as $row ) : ?>
			<tr><td style="width:180px"><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $row['time'] ) ); ?></td><td><?php echo esc_html( implode( ', ', (array) $row['urls'] ) ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}
