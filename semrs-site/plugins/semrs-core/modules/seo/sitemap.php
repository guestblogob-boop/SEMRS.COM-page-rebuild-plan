<?php
/**
 * XML sitemaps (WordPress core engine, tuned) and robots.txt additions.
 *
 * - Noindexed posts (SEMRS or Rank Math) are left out.
 * - Tag / author sitemaps follow the noindex settings.
 * - Old Rank Math/Yoast sitemap URLs (/sitemap_index.xml, /sitemap.xml)
 *   301 to /wp-sitemap.xml, so the URL in Search Console keeps working.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register sitemap behaviour once we know no other SEO plugin is active.
 */
function semrs_seo_register_sitemaps() {
	if ( ! semrs_seo_active() ) {
		return;
	}
	$s = semrs_seo_settings();

	add_filter( 'wp_sitemaps_enabled', $s['sitemap'] ? '__return_true' : '__return_false' );

	add_filter(
		'wp_sitemaps_add_provider',
		function ( $provider, $name ) use ( $s ) {
			return ( 'users' === $name && $s['noindex_authors'] ) ? false : $provider;
		},
		10,
		2
	);

	add_filter(
		'wp_sitemaps_taxonomies',
		function ( $taxonomies ) use ( $s ) {
			if ( $s['noindex_tags'] ) {
				unset( $taxonomies['post_tag'] );
			}
			unset( $taxonomies['post_format'] );
			return $taxonomies;
		}
	);

	add_filter(
		'wp_sitemaps_posts_query_args',
		function ( $args ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery -- sitemap generation, cached by page cache.
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array( 'key' => '_semrs_noindex', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_semrs_noindex', 'value' => '1', 'compare' => '!=' ),
				),
				array(
					'relation' => 'OR',
					array( 'key' => 'rank_math_robots', 'compare' => 'NOT EXISTS' ),
					array( 'key' => 'rank_math_robots', 'value' => '"noindex"', 'compare' => 'NOT LIKE' ),
				),
			);
			return $args;
		}
	);

	add_filter(
		'wp_sitemaps_posts_entry',
		function ( $entry, $post ) {
			$entry['lastmod'] = get_post_modified_time( 'c', true, $post );
			return $entry;
		},
		10,
		2
	);

	add_action(
		'template_redirect',
		function () use ( $s ) {
			if ( ! $s['sitemap'] ) {
				return;
			}
			$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
			if ( in_array( untrailingslashit( $path ), array( '/sitemap_index.xml', '/sitemap.xml' ), true ) ) {
				wp_safe_redirect( home_url( '/wp-sitemap.xml' ), 301 );
				exit;
			}
		},
		0
	);

	add_filter(
		'robots_txt',
		function ( $output, $public ) use ( $s ) {
			if ( $public && '' !== trim( $s['robots_extra'] ) ) {
				$output .= "\n" . trim( $s['robots_extra'] ) . "\n";
			}
			return $output;
		},
		20,
		2
	);
}
add_action( 'plugins_loaded', 'semrs_seo_register_sitemaps', 20 );
