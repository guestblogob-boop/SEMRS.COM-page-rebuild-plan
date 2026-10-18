<?php
/**
 * PREVIEW ONLY (never ship): the Playground preview has no internet, and
 * WordPress core stops its update check when api.wordpress.org fails, before
 * it reaches "Update URI" packages. This answers WordPress.org's update
 * checks with an empty result so core continues to the SEMRS updater, and
 * exposes the updater's cached responses for testing.
 */

add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		// The sandbox cannot make HTTP requests to itself: hand update-server
		// calls straight to its REST route (same code path as a real request).
		if ( defined( 'SEMRS_UPDATE_SERVER' ) && 0 === strpos( $url, SEMRS_UPDATE_SERVER . 'check' ) ) {
			$query = array();
			wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
			$req = new WP_REST_Request( 'GET', '/semrs-updates/v1/check' );
			$req->set_query_params( $query );
			$res = rest_do_request( $req );
			return array(
				'headers'  => array(),
				'body'     => wp_json_encode( $res->get_data() ),
				'response' => array( 'code' => $res->get_status(), 'message' => 'OK' ),
				'cookies'  => array(),
				'filename' => null,
			);
		}
		if ( false === strpos( $url, 'api.wordpress.org' ) ) {
			return $pre;
		}
		$body = null;
		if ( false !== strpos( $url, '/plugins/update-check' ) ) {
			$body = array( 'plugins' => array(), 'translations' => array(), 'no_update' => array() );
		} elseif ( false !== strpos( $url, '/themes/update-check' ) ) {
			$body = array( 'themes' => array(), 'translations' => array(), 'no_update' => array() );
		}
		if ( null === $body ) {
			return new WP_Error( 'preview_offline', 'Preview is offline.' );
		}
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( $body ),
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	10,
	3
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'semrs-test/v1',
			'/run',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					foreach ( array( 'semrs', 'semrs-child-seo', 'semrs-core', 'semrs-tools', 'semrs-pro' ) as $s ) {
						delete_site_transient( 'semrs_upd_' . md5( $s ) );
					}
					delete_site_transient( 'update_plugins' );
					delete_site_transient( 'update_themes' );
					wp_update_plugins();
					wp_update_themes();
					return array( 'ran' => true );
				},
			)
		);
		register_rest_route(
			'semrs-test/v1',
			'/updates',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					$out = array();
					foreach ( array( 'semrs', 'semrs-child-seo', 'semrs-core', 'semrs-tools', 'semrs-pro' ) as $s ) {
						$out[ $s ] = get_site_transient( 'semrs_upd_' . md5( $s ) );
					}
					$p = get_site_transient( 'update_plugins' );
					$t = get_site_transient( 'update_themes' );
					return array(
						'updater_cache'   => $out,
						'plugin_updates'  => $p && isset( $p->response ) ? array_map( function ( $r ) { return is_object( $r ) ? $r->new_version : $r['new_version']; }, (array) $p->response ) : array(),
						'theme_updates'   => $t && isset( $t->response ) ? array_map( function ( $r ) { return $r['new_version']; }, (array) $t->response ) : array(),
						'license_remote'  => get_option( 'semrs_pro_license_remote' ),
					);
				},
			)
		);
	}
);
