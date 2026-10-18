<?php
/**
 * Virtual /ads.txt (only when enabled AND no physical ads.txt exists;
 * a physical file is always served by the web server first).
 *
 * @package semrs-ads
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ads.txt body: Google line from the publisher ID + custom lines.
 *
 * @return string
 */
function semrs_ads_adstxt_body() {
	$s     = semrs_ads_settings();
	$lines = array();
	if ( preg_match( '/^ca-pub-(\d{10,20})$/', $s['publisher_id'], $m ) ) {
		$lines[] = 'google.com, pub-' . $m[1] . ', DIRECT, f08c47fec0942fa0';
	}
	foreach ( preg_split( '/\R/', (string) $s['adstxt'] ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line && ! in_array( $line, $lines, true ) ) {
			$lines[] = $line;
		}
	}
	return implode( "\n", $lines ) . "\n";
}

/**
 * Serve it.
 */
function semrs_ads_serve_adstxt() {
	if ( ! semrs_ads_settings()['manage_adstxt'] || '/ads.txt/' !== semrs_ads_request_path() || file_exists( ABSPATH . 'ads.txt' ) ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo semrs_ads_adstxt_body(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text.
	exit;
}
add_action( 'init', 'semrs_ads_serve_adstxt', 0 );
