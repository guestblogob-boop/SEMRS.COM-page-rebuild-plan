<?php
/**
 * Sitemaps Pro: image, video and Google News sitemaps, and VideoObject schema for pages with
 * YouTube, Vimeo or self-hosted videos.
 *
 *   /sitemap-images.xml  pages and the images on them (featured image + content images)
 *   /sitemap-videos.xml  pages with a video: title, description, thumbnail, player / file
 *   /sitemap-news.xml    posts from the last two days (only for news publishers; off by default)
 *
 * Video details (title, thumbnail) come from oEmbed when a page is saved and are stored with the
 * page, so visitors never wait on YouTube or Vimeo.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array{images:bool,videos:bool,news:bool,news_name:string,schema:bool}
 */
function semrs_smp_settings() {
	$s = get_option( 'semrs_sitemaps_pro', array() );
	return array_merge(
		array(
			'images'    => true,
			'videos'    => true,
			'news'      => false,
			'news_name' => get_bloginfo( 'name' ),
			'schema'    => true,
		),
		is_array( $s ) ? $s : array()
	);
}

/**
 * Pro license present?
 *
 * @return bool
 */
function semrs_smp_pro() {
	return function_exists( 'semrs_seo_pro' ) && semrs_seo_pro();
}

/**
 * Post types that appear in the sitemaps.
 *
 * @return string[]
 */
function semrs_smp_types() {
	return function_exists( 'semrs_seo_post_types' ) ? semrs_seo_post_types() : array( 'post', 'page' );
}

/* ---------------------------------------------------------------------------
 * Video detection (on save)
 * ------------------------------------------------------------------------- */

/**
 * Videos in a post's content: YouTube, Vimeo and <video> files.
 *
 * @param WP_Post $post Post.
 * @return array<int, array{player:string,content:string,title:string,thumb:string}>
 */
function semrs_smp_find_videos( $post ) {
	$html   = (string) $post->post_content;
	$videos = array();
	if ( preg_match_all( '#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $html, $m ) ) {
		foreach ( array_unique( $m[1] ) as $id ) {
			$videos[ 'yt' . $id ] = array( 'url' => 'https://www.youtube.com/watch?v=' . $id, 'player' => 'https://www.youtube.com/embed/' . $id, 'content' => '', 'thumb' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg' );
		}
	}
	if ( preg_match_all( '#vimeo\.com/(?:video/)?(\d{6,12})#', $html, $m ) ) {
		foreach ( array_unique( $m[1] ) as $id ) {
			$videos[ 'vm' . $id ] = array( 'url' => 'https://vimeo.com/' . $id, 'player' => 'https://player.vimeo.com/video/' . $id, 'content' => '', 'thumb' => '' );
		}
	}
	if ( preg_match_all( '#<video[^>]*\ssrc=["\']([^"\']+)["\']|<source[^>]*\ssrc=["\']([^"\']+\.(?:mp4|webm|mov))["\']#i', $html, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $hit ) {
			$src                   = $hit[1] ? $hit[1] : $hit[2];
			$videos[ 'f' . md5( $src ) ] = array( 'url' => '', 'player' => '', 'content' => esc_url_raw( $src ), 'thumb' => '' );
		}
	}
	$out = array();
	foreach ( $videos as $v ) {
		$title = '';
		if ( $v['url'] && function_exists( '_wp_oembed_get_object' ) ) {
			$data = _wp_oembed_get_object()->get_data( $v['url'], array() );
			if ( $data ) {
				$title      = isset( $data->title ) ? (string) $data->title : '';
				$v['thumb'] = ! empty( $data->thumbnail_url ) ? (string) $data->thumbnail_url : $v['thumb'];
			}
		}
		if ( '' === $v['thumb'] && has_post_thumbnail( $post ) ) {
			$v['thumb'] = (string) get_the_post_thumbnail_url( $post, 'large' ); // Google requires a thumbnail.
		}
		if ( '' === $v['thumb'] ) {
			continue;
		}
		$out[] = array( 'player' => $v['player'], 'content' => $v['content'], 'title' => '' !== $title ? $title : get_the_title( $post ), 'thumb' => $v['thumb'] );
	}
	return $out;
}

add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status || ! in_array( $post->post_type, semrs_smp_types(), true ) ) {
			return;
		}
		$videos = semrs_smp_find_videos( $post );
		if ( $videos ) {
			update_post_meta( $post_id, '_semrs_videos', $videos );
		} else {
			delete_post_meta( $post_id, '_semrs_videos' );
		}
	},
	40,
	2
);

/* ---------------------------------------------------------------------------
 * VideoObject schema
 * ------------------------------------------------------------------------- */

add_filter(
	'semrs_schema_graph',
	function ( $graph ) {
		if ( ! is_singular() || ! semrs_smp_settings()['schema'] || ! semrs_smp_pro() ) {
			return $graph;
		}
		foreach ( (array) $graph as $node ) {
			if ( 'VideoObject' === ( $node['@type'] ?? '' ) ) {
				return $graph; // The page already has video schema (custom schema).
			}
		}
		$post   = get_queried_object();
		$videos = (array) get_post_meta( $post->ID, '_semrs_videos', true );
		$desc   = function_exists( 'semrs_seo_description' ) ? semrs_seo_description() : '';
		$url    = get_permalink( $post );
		foreach ( array_filter( $videos ) as $i => $v ) {
			$node = array(
				'@type'        => 'VideoObject',
				'@id'          => $url . '#video-' . ( $i + 1 ),
				'name'         => $v['title'],
				'description'  => '' !== $desc ? $desc : $v['title'],
				'thumbnailUrl' => $v['thumb'],
				'uploadDate'   => get_the_date( 'c', $post ),
				'isPartOf'     => array( '@id' => $url . '#webpage' ),
			);
			if ( $v['player'] ) {
				$node['embedUrl'] = $v['player'];
			}
			if ( $v['content'] ) {
				$node['contentUrl'] = $v['content'];
			}
			$graph[] = $node;
		}
		return $graph;
	}
);

/* ---------------------------------------------------------------------------
 * Sitemaps
 * ------------------------------------------------------------------------- */

/**
 * Serve /sitemap-images.xml, /sitemap-videos.xml and /sitemap-news.xml.
 */
add_action(
	'parse_request',
	function () {
		$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH );
		$map  = array( '/sitemap-images.xml' => 'images', '/sitemap-videos.xml' => 'videos', '/sitemap-news.xml' => 'news' );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$path = '/' . ltrim( substr( $path, strlen( rtrim( $home, '/' ) ) ), '/' );
		if ( ! isset( $map[ $path ] ) ) {
			return;
		}
		$kind = $map[ $path ];
		if ( ! semrs_smp_pro() || empty( semrs_smp_settings()[ $kind ] ) ) {
			return; // Falls through to the normal 404.
		}
		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );
		echo semrs_smp_xml( $kind ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped while building.
		exit;
	}
);

/**
 * Build one sitemap.
 *
 * @param string $kind images|videos|news.
 * @return string
 */
function semrs_smp_xml( $kind ) {
	$cache = get_transient( 'semrs_smp_' . $kind );
	if ( is_string( $cache ) ) {
		return $cache;
	}
	$x    = fn( $s ) => esc_xml( (string) $s );
	$ns   = array( 'images' => 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', 'videos' => 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"', 'news' => 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' );
	$out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' . $ns[ $kind ] . ">\n";
	$args = array( 'post_type' => semrs_smp_types(), 'post_status' => 'publish', 'posts_per_page' => 1000, 'no_found_rows' => true, 'orderby' => 'modified', 'order' => 'DESC' );
	if ( 'videos' === $kind ) {
		$args['meta_key'] = '_semrs_videos'; // phpcs:ignore WordPress.DB.SlowDBQuery -- cached sitemap.
	} elseif ( 'news' === $kind ) {
		$args['post_type']  = 'post';
		$args['date_query'] = array( array( 'after' => '2 days ago' ) );
		$args['orderby']    = 'date';
	}
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	foreach ( get_posts( $args ) as $post ) {
		if ( function_exists( 'semrs_seo_is_noindex' ) && semrs_seo_is_noindex( $post->ID ) ) {
			continue;
		}
		$loc  = get_permalink( $post );
		$item = '';
		if ( 'images' === $kind ) {
			$imgs = array();
			if ( has_post_thumbnail( $post ) ) {
				$imgs[] = (string) get_the_post_thumbnail_url( $post, 'full' );
			}
			if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $post->post_content, $m ) ) {
				foreach ( $m[1] as $src ) {
					$src = 0 === strpos( $src, '/' ) && 0 !== strpos( $src, '//' ) ? home_url( $src ) : $src;
					if ( wp_parse_url( $src, PHP_URL_HOST ) === $home ) {
						$imgs[] = preg_replace( '/-\d+x\d+(\.[a-z]+)$/i', '$1', strtok( $src, '?' ) ); // The full-size file.
					}
				}
			}
			foreach ( array_slice( array_unique( array_filter( $imgs ) ), 0, 1000 ) as $img ) {
				$item .= "\t\t<image:image><image:loc>" . $x( $img ) . "</image:loc></image:image>\n";
			}
		} elseif ( 'videos' === $kind ) {
			$desc = function_exists( 'semrs_seo_meta' ) ? (string) semrs_seo_meta( $post->ID, 'description' ) : '';
			$desc = '' !== $desc ? $desc : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 30, '' );
			foreach ( (array) get_post_meta( $post->ID, '_semrs_videos', true ) as $v ) {
				if ( empty( $v['thumb'] ) ) {
					continue;
				}
				$item .= "\t\t<video:video>\n\t\t\t<video:thumbnail_loc>" . $x( $v['thumb'] ) . "</video:thumbnail_loc>\n\t\t\t<video:title>" . $x( $v['title'] ) . "</video:title>\n\t\t\t<video:description>" . $x( mb_substr( $desc, 0, 2048 ) ) . "</video:description>\n"
					. ( $v['content'] ? "\t\t\t<video:content_loc>" . $x( $v['content'] ) . "</video:content_loc>\n" : '' )
					. ( $v['player'] ? "\t\t\t<video:player_loc>" . $x( $v['player'] ) . "</video:player_loc>\n" : '' )
					. "\t\t\t<video:publication_date>" . $x( get_the_date( 'c', $post ) ) . "</video:publication_date>\n\t\t</video:video>\n";
			}
		} else {
			$item = "\t\t<news:news>\n\t\t\t<news:publication><news:name>" . $x( semrs_smp_settings()['news_name'] ) . '</news:name><news:language>' . $x( substr( get_locale(), 0, 2 ) ) . "</news:language></news:publication>\n\t\t\t<news:publication_date>" . $x( get_the_date( 'c', $post ) ) . "</news:publication_date>\n\t\t\t<news:title>" . $x( get_the_title( $post ) ) . "</news:title>\n\t\t</news:news>\n";
		}
		if ( '' !== $item ) {
			$out .= "\t<url>\n\t\t<loc>" . $x( $loc ) . "</loc>\n" . ( 'news' !== $kind ? "\t\t<lastmod>" . $x( get_post_modified_time( 'c', true, $post ) ) . "</lastmod>\n" : '' ) . $item . "\t</url>\n";
		}
	}
	$out .= "</urlset>\n";
	set_transient( 'semrs_smp_' . $kind, $out, 'news' === $kind ? 10 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS );
	return $out;
}

// Rebuild after content changes.
add_action(
	'save_post',
	function () {
		foreach ( array( 'images', 'videos', 'news' ) as $k ) {
			delete_transient( 'semrs_smp_' . $k );
		}
	},
	50
);

/**
 * Sitemap lines in the generated robots.txt (a static robots.txt file must list them by hand).
 */
add_filter(
	'robots_txt',
	function ( $out, $public ) {
		if ( ! $public || ! semrs_smp_pro() ) {
			return $out;
		}
		$s = semrs_smp_settings();
		foreach ( array( 'images' => 'sitemap-images.xml', 'videos' => 'sitemap-videos.xml', 'news' => 'sitemap-news.xml' ) as $k => $file ) {
			if ( ! empty( $s[ $k ] ) ) {
				$out .= "\nSitemap: " . home_url( '/' . $file );
			}
		}
		return $out . "\n";
	},
	20,
	2
);

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Sitemaps Pro', 'semrs-pro' ), __( 'Sitemaps Pro', 'semrs-pro' ), 'manage_options', 'semrs-sitemaps-pro', 'semrs_smp_page' );
	},
	19
);

add_action(
	'admin_post_semrs_smp',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-pro' ) );
		}
		check_admin_referer( 'semrs_smp' );
		if ( isset( $_POST['rescan'] ) ) {
			foreach ( get_posts( array( 'post_type' => semrs_smp_types(), 'post_status' => 'publish', 'posts_per_page' => 500, 'no_found_rows' => true ) ) as $post ) {
				if ( preg_match( '#youtube\.com|youtu\.be|vimeo\.com|<video#i', (string) $post->post_content ) ) {
					$v = semrs_smp_find_videos( $post );
					$v ? update_post_meta( $post->ID, '_semrs_videos', $v ) : delete_post_meta( $post->ID, '_semrs_videos' );
				}
			}
		} else {
			$in = wp_unslash( $_POST['semrs_smp'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
			update_option(
				'semrs_sitemaps_pro',
				array(
					'images'    => ! empty( $in['images'] ),
					'videos'    => ! empty( $in['videos'] ),
					'news'      => ! empty( $in['news'] ),
					'news_name' => sanitize_text_field( $in['news_name'] ?? '' ),
					'schema'    => ! empty( $in['schema'] ),
				),
				false
			);
		}
		foreach ( array( 'images', 'videos', 'news' ) as $k ) {
			delete_transient( 'semrs_smp_' . $k );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-sitemaps-pro&saved=1' ) );
		exit;
	}
);

/**
 * Screen.
 */
function semrs_smp_page() {
	$s      = semrs_smp_settings();
	$videos = count( get_posts( array( 'post_type' => semrs_smp_types(), 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_semrs_videos', 'no_found_rows' => true ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery -- admin screen.
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sitemaps Pro', 'semrs-pro' ); ?></h1>
		<?php if ( ! semrs_smp_pro() ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Image, video and news sitemaps and video schema unlock with a SEMRS Pro license.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'These sitemaps add to your main sitemap. Submit each one in Google Search Console (Indexing → Sitemaps). If your site uses a robots.txt file, add their addresses to it too.', 'semrs-pro' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="semrs_smp">
			<?php wp_nonce_field( 'semrs_smp' ); ?>
			<table class="form-table" role="presentation">
				<?php
				foreach ( array(
					'images' => array( __( 'Image sitemap', 'semrs-pro' ), 'sitemap-images.xml', __( 'Helps your images appear in Google Images.', 'semrs-pro' ) ),
					/* translators: %d: pages with a video */
					'videos' => array( __( 'Video sitemap', 'semrs-pro' ), 'sitemap-videos.xml', sprintf( __( 'Pages with YouTube, Vimeo or uploaded videos (%d found).', 'semrs-pro' ), $videos ) ),
					'news'   => array( __( 'News sitemap', 'semrs-pro' ), 'sitemap-news.xml', __( 'Only for sites approved in Google News: posts from the last two days.', 'semrs-pro' ) ),
				) as $k => $row ) :
					?>
					<tr><th scope="row"><?php echo esc_html( $row[0] ); ?></th><td>
						<label><input type="checkbox" name="semrs_smp[<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( ! empty( $s[ $k ] ) ); ?>> <?php echo esc_html( $row[2] ); ?></label>
						<?php if ( ! empty( $s[ $k ] ) ) : ?><br><a href="<?php echo esc_url( home_url( '/' . $row[1] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/' . $row[1] ) ); ?></a><?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
				<tr><th scope="row"><label for="semrs_smp_news_name"><?php esc_html_e( 'News publication name', 'semrs-pro' ); ?></label></th><td><input class="regular-text" id="semrs_smp_news_name" name="semrs_smp[news_name]" value="<?php echo esc_attr( $s['news_name'] ); ?>"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Video schema', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="semrs_smp[schema]" value="1" <?php checked( $s['schema'] ); ?>> <?php esc_html_e( 'Add VideoObject schema to pages with videos (skipped when the page has its own video schema)', 'semrs-pro' ); ?></label></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="semrs_smp"><input type="hidden" name="rescan" value="1">
			<?php wp_nonce_field( 'semrs_smp' ); ?>
			<p><button class="button"><?php esc_html_e( 'Find videos in existing content', 'semrs-pro' ); ?></button> <span class="description"><?php esc_html_e( 'New and updated pages are found automatically.', 'semrs-pro' ); ?></span></p>
		</form>
	</div>
	<?php
}
