<?php
/**
 * Links, URLs and feeds (SEMRS SEO → Links, URLs & feeds):
 *
 * - External links in post content: optional nofollow and/or new tab, with an exception list.
 *   Only the HTML sent to visitors changes; the saved content stays as written.
 * - Link counter: internal and external links in each post and how many other posts link to
 *   it ("Links" column on post lists). Counted on save and, for older posts, in the background.
 * - Strip the category base: /category/news/ becomes /news/ (old addresses 301 to the new).
 * - RSS footer: a line with a link back to the post, so copies of the feed credit the site.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * External links
 * ---------------------------------------------------------------------- */

/**
 * Add nofollow / target="_blank" to external links in the content.
 *
 * @param string $html Content.
 * @return string
 */
function semrs_links_external( $html ) {
	$s = semrs_seo_settings();
	if ( ! semrs_seo_active() || ( empty( $s['ext_nofollow'] ) && empty( $s['ext_blank'] ) ) || false === stripos( $html, '<a ' ) || is_admin() || is_feed() ) {
		return $html;
	}
	$own  = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$skip = array_filter( array_map( 'strtolower', array_map( 'trim', preg_split( '/[\s,]+/', (string) $s['ext_exclude'] ) ) ) );
	return preg_replace_callback(
		'#<a\s[^>]*href=(["\'])(https?://[^"\']+)\1[^>]*>#i',
		function ( $m ) use ( $s, $own, $skip ) {
			$tag  = $m[0];
			$host = strtolower( (string) wp_parse_url( $m[2], PHP_URL_HOST ) );
			$host = preg_replace( '/^www\./', '', $host );
			if ( '' === $host || preg_replace( '/^www\./', '', $own ) === $host ) {
				return $tag;
			}
			foreach ( $skip as $d ) {
				$d = preg_replace( '#^(https?://)?(www\.)?#', '', $d );
				if ( $host === $d || ( '.' . $d ) === substr( $host, -strlen( $d ) - 1 ) ) {
					return $tag;
				}
			}
			$rel = array();
			if ( preg_match( '/\srel=(["\'])(.*?)\1/i', $tag, $r ) ) {
				$rel = preg_split( '/\s+/', trim( strtolower( $r[2] ) ) );
				$tag = str_replace( $r[0], '', $tag );
			}
			if ( ! empty( $s['ext_nofollow'] ) && ! array_intersect( $rel, array( 'sponsored', 'ugc', 'dofollow', 'follow' ) ) ) {
				$rel[] = 'nofollow';
			}
			$rel = array_diff( $rel, array( 'dofollow', 'follow' ) );
			if ( ! empty( $s['ext_blank'] ) && ! preg_match( '/\starget=/i', $tag ) ) {
				$tag   = preg_replace( '/^<a\s/i', '<a target="_blank" ', $tag );
				$rel[] = 'noopener';
			}
			$rel = array_unique( array_filter( $rel ) );
			return $rel ? preg_replace( '/^<a\s/i', '<a rel="' . esc_attr( implode( ' ', $rel ) ) . '" ', $tag ) : $tag;
		},
		$html
	);
}
add_filter( 'the_content', 'semrs_links_external', 999 );

/* -------------------------------------------------------------------------
 * Link counter
 * ---------------------------------------------------------------------- */

/**
 * Count a post's links and remember which posts it links to.
 *
 * @param int $post_id Post ID.
 */
function semrs_links_count( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, semrs_seo_public_types(), true ) ) {
		return;
	}
	$home  = preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
	$int   = 0;
	$ext   = 0;
	$to    = array();
	$cache = array();
	preg_match_all( '#<a\s[^>]*href=(["\'])([^"\']+)\1#i', (string) $post->post_content, $m );
	foreach ( array_slice( $m[2], 0, 300 ) as $href ) {
		$href = html_entity_decode( trim( $href ) );
		if ( '' === $href || '#' === $href[0] || preg_match( '#^(mailto|tel|javascript|sms):#i', $href ) ) {
			continue;
		}
		$host = preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) ) );
		if ( '' !== $host && $host !== $home ) {
			$ext++;
			continue;
		}
		$int++;
		$path = (string) wp_parse_url( $href, PHP_URL_PATH );
		if ( ! isset( $cache[ $path ] ) && count( $cache ) < 100 ) {
			$cache[ $path ] = url_to_postid( home_url( $path ) );
		}
		if ( ! empty( $cache[ $path ] ) && (int) $cache[ $path ] !== (int) $post_id ) {
			$to[ (int) $cache[ $path ] ] = true;
		}
	}
	update_post_meta( $post_id, '_semrs_links', array( 'int' => $int, 'ext' => $ext ) );
	update_post_meta( $post_id, '_semrs_links_to', $to ? ',' . implode( ',', array_keys( $to ) ) . ',' : '' );
}
add_action(
	'save_post',
	function ( $id, $post ) {
		if ( 'publish' === $post->post_status || 'private' === $post->post_status ) {
			semrs_links_count( $id );
		}
	},
	40,
	2
);

/**
 * Published posts that link to a post.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function semrs_links_incoming( $post_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_semrs_links_to' AND pm.meta_value LIKE %s AND p.post_status = 'publish'", '%,' . (int) $post_id . ',%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Background count for posts saved before the counter existed: 40 posts a run.
add_action(
	'semrs_links_backfill',
	function () {
		$ids = get_posts(
			array(
				'post_type'      => semrs_seo_public_types(),
				'post_status'    => 'publish',
				'posts_per_page' => 40,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_semrs_links', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		foreach ( $ids as $id ) {
			semrs_links_count( $id );
		}
		if ( count( $ids ) === 40 ) {
			wp_schedule_single_event( time() + 60, 'semrs_links_backfill' );
		} else {
			update_option( 'semrs_links_counted', 1, false );
		}
	}
);

if ( is_admin() ) {
	add_action(
		'admin_init',
		function () {
			if ( ! get_option( 'semrs_links_counted' ) && ! wp_next_scheduled( 'semrs_links_backfill' ) ) {
				wp_schedule_single_event( time() + 10, 'semrs_links_backfill' );
			}
			foreach ( semrs_seo_public_types() as $type ) {
				add_filter(
					"manage_{$type}_posts_columns",
					function ( $cols ) {
						$cols['semrs_links'] = __( 'Links', 'semrs-core' );
						return $cols;
					},
					20
				);
				add_action(
					"manage_{$type}_posts_custom_column",
					function ( $col, $id ) {
						if ( 'semrs_links' !== $col ) {
							return;
						}
						$l = get_post_meta( $id, '_semrs_links', true );
						if ( ! is_array( $l ) ) {
							echo '<span class="description">–</span>';
							return;
						}
						$in = semrs_links_incoming( $id );
						printf(
							'<span title="%1$s">%2$s <b>%3$d</b> &nbsp;%4$s <b>%5$d</b> &nbsp;%6$s <b%7$s>%8$d</b></span>',
							esc_attr__( 'Internal links in this post / external links in this post / other posts linking here', 'semrs-core' ),
							esc_html__( 'Int', 'semrs-core' ),
							(int) $l['int'],
							esc_html__( 'Ext', 'semrs-core' ),
							(int) $l['ext'],
							esc_html__( 'In', 'semrs-core' ),
							0 === $in ? ' style="color:#b32d2e" title="' . esc_attr__( 'No other post links here (orphan page)', 'semrs-core' ) . '"' : '',
							(int) $in
						);
					},
					10,
					2
				);
			}
		}
	);
	add_action(
		'admin_head-edit.php',
		function () {
			echo '<style>.column-semrs_links{width:150px;font-size:12px}</style>';
		}
	);
}

/* -------------------------------------------------------------------------
 * Strip the category base
 * ---------------------------------------------------------------------- */

/**
 * The category base ("category" unless changed in Settings → Permalinks).
 *
 * @return string
 */
function semrs_links_cat_base() {
	$base = trim( (string) get_option( 'category_base' ), '/' );
	return '' === $base ? 'category' : $base;
}

if ( semrs_seo_active() && ! empty( semrs_seo_settings()['strip_category_base'] ) && get_option( 'permalink_structure' ) ) {
	add_filter(
		'category_link',
		function ( $link ) {
			return preg_replace( '#/' . preg_quote( semrs_links_cat_base(), '#' ) . '/#', '/', $link, 1 );
		}
	);
	add_filter(
		'category_rewrite_rules',
		function () {
			$rules = array();
			foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) {
				$path = $cat->slug;
				if ( $cat->parent && ! is_wp_error( get_category_parents( $cat->parent, false, '/', true ) ) ) {
					$path = get_category_parents( $cat->parent, false, '/', true ) . $cat->slug;
				}
				$p = preg_quote( $path, '#' );
				$rules[ '(' . $p . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
				$rules[ '(' . $p . ')/page/?([0-9]{1,})/?$' ]                   = 'index.php?category_name=$matches[1]&paged=$matches[2]';
				$rules[ '(' . $p . ')/?$' ]                                     = 'index.php?category_name=$matches[1]';
			}
			// Old addresses with the base 301 to the new ones.
			$rules[ preg_quote( semrs_links_cat_base(), '#' ) . '/(.+)$' ] = 'index.php?semrs_cat_redirect=$matches[1]';
			return $rules;
		}
	);
	add_filter(
		'query_vars',
		function ( $vars ) {
			$vars[] = 'semrs_cat_redirect';
			return $vars;
		}
	);
	add_filter(
		'request',
		function ( $vars ) {
			if ( ! empty( $vars['semrs_cat_redirect'] ) ) {
				wp_safe_redirect( home_url( user_trailingslashit( trim( $vars['semrs_cat_redirect'], '/' ) ) ), 301, 'SEMRS' );
				exit;
			}
			return $vars;
		}
	);
}

// Category rules list every category: rebuild them when categories or the setting change.
foreach ( array( 'created_category', 'edited_category', 'delete_category' ) as $semrs_hook ) {
	add_action(
		$semrs_hook,
		function () {
			delete_option( 'rewrite_rules' );
		}
	);
}
unset( $semrs_hook );
add_action(
	'update_option_semrs_seo',
	function ( $old, $new ) {
		if ( ! empty( $old['strip_category_base'] ) !== ! empty( $new['strip_category_base'] ) ) {
			delete_option( 'rewrite_rules' );
		}
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * RSS footer
 * ---------------------------------------------------------------------- */

/**
 * Add the RSS footer line to feed items.
 *
 * @param string $content Feed content.
 * @return string
 */
function semrs_links_rss_footer( $content ) {
	$s = semrs_seo_settings();
	if ( ! semrs_seo_active() || empty( $s['rss_footer_on'] ) || '' === trim( (string) $s['rss_footer'] ) || ! is_feed() ) {
		return $content;
	}
	$line = strtr(
		esc_html( $s['rss_footer'] ),
		array(
			'%postlink%' => '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>',
			'%sitelink%' => '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>',
			'%title%'    => esc_html( get_the_title() ),
			'%site%'     => esc_html( get_bloginfo( 'name' ) ),
		)
	);
	return $content . "\n<p>" . $line . '</p>';
}
add_filter( 'the_content_feed', 'semrs_links_rss_footer', 20 );
add_filter( 'the_excerpt_rss', 'semrs_links_rss_footer', 20 );
