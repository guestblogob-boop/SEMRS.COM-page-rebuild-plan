<?php
/**
 * PREVIEW ONLY (never ship): /wp-json/semrs-test/v1/tpl-demo creates demo
 * pages /tpl-{site}-{page}/ for every starter site so previews can be taken
 * without the real slugs clashing.
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'semrs-test/v1',
			'/tpl-demo',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					if ( ! function_exists( 'semrs_blocks_template_sites' ) ) {
						return array( 'error' => 'SEMRS Blocks not active' );
					}
					$owner = array( 'name' => 'Northwind', 'phone' => '+1 555 010 0200', 'email' => 'hello@example.com', 'city' => 'Austin', 'country' => 'US' );
					$out   = array();
					foreach ( semrs_blocks_template_sites() as $sid => $site ) {
						$images = array();
						$dir    = $site['image_dir'] ?? SEMRS_BLOCKS_DIR . 'templates/images/';
						foreach ( (array) $site['images'] as $key => $file ) {
							$id             = semrs_blocks_template_media( $dir . $file, 'Demo ' . $key );
							$images[ $key ] = $id ? wp_get_attachment_url( $id ) : '';
						}
						foreach ( $site['pages'] as $pid => $page ) {
							if ( empty( $page['sections'] ) ) {
								continue;
							}
							$slug = 'tpl-' . $sid . '-' . $pid;
							$old  = get_page_by_path( $slug );
							$data = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $site['title'] . ' ' . $page['title'], 'post_name' => $slug, 'post_content' => wp_slash( semrs_blocks_template_page_html( $site, $page, $owner, $images ) ) );
							if ( $old ) {
								$data['ID'] = $old->ID;
							}
							$id = wp_insert_post( $data );
							update_post_meta( $id, '_wp_page_template', 'page-templates/full-width.php' );
							update_post_meta( $id, 'semrs_hide_title', '1' );
							$out[] = get_permalink( $id );
						}
					}
					return $out;
				},
			)
		);
	}
);

// Template live previews open logged out so headless Chrome can capture them.
add_filter( 'semrs_blocks_template_preview_allowed', '__return_true' );
