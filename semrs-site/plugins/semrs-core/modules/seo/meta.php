<?php
/**
 * Head output: title, description, robots, canonical, Open Graph, X cards,
 * site verification. Attachment pages redirect to their parent.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page number suffix for paginated archives/posts.
 *
 * @return string
 */
function semrs_seo_page_suffix() {
	$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	/* translators: %d: page number */
	return $paged > 1 ? ' ' . semrs_seo_settings()['sep'] . ' ' . sprintf( __( 'Page %d', 'semrs-core' ), $paged ) : '';
}

/**
 * The SEO title for the current request.
 *
 * @return string
 */
function semrs_seo_title() {
	$s = semrs_seo_settings();

	if ( is_front_page() ) {
		$id     = (int) get_option( 'page_on_front' );
		$custom = $id ? semrs_seo_meta( $id, 'title' ) : '';
		$title  = $custom ? $custom : ( $s['home_title'] ? $s['home_title'] : semrs_seo_fill( '%site% %sep% %tagline%' ) );
		return $title . semrs_seo_page_suffix();
	}
	if ( is_home() ) {
		$id     = (int) get_option( 'page_for_posts' );
		$custom = $id ? semrs_seo_meta( $id, 'title' ) : '';
		return ( $custom ? $custom : semrs_seo_fill( $s['tpl_singular'], array( 'title' => $id ? get_the_title( $id ) : __( 'Blog', 'semrs-core' ) ) ) ) . semrs_seo_page_suffix();
	}
	if ( is_singular() ) {
		$id     = get_queried_object_id();
		$custom = semrs_seo_meta( $id, 'title' );
		return ( $custom ? $custom : semrs_seo_fill( $s['tpl_singular'], array( 'title' => single_post_title( '', false ) ) ) ) . semrs_seo_page_suffix();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return semrs_seo_fill( $s['tpl_term'], array( 'term' => single_term_title( '', false ) ) ) . semrs_seo_page_suffix();
	}
	if ( is_post_type_archive() ) {
		return semrs_seo_fill( $s['tpl_term'], array( 'term' => post_type_archive_title( '', false ) ) ) . semrs_seo_page_suffix();
	}
	if ( is_author() ) {
		return semrs_seo_fill( $s['tpl_author'], array( 'author' => get_the_author_meta( 'display_name', get_queried_object_id() ) ) ) . semrs_seo_page_suffix();
	}
	if ( is_search() ) {
		return semrs_seo_fill( $s['tpl_search'], array( 'search' => get_search_query() ) );
	}
	if ( is_404() ) {
		return semrs_seo_fill( $s['tpl_404'] );
	}
	if ( is_date() ) {
		return semrs_seo_fill( $s['tpl_date'], array( 'date' => wp_strip_all_tags( get_the_archive_title() ) ) ) . semrs_seo_page_suffix();
	}
	return '';
}

/**
 * Meta description for the current request.
 *
 * @return string
 */
function semrs_seo_description() {
	$s    = semrs_seo_settings();
	$desc = '';
	if ( is_front_page() ) {
		$id   = (int) get_option( 'page_on_front' );
		$desc = $id ? semrs_seo_meta( $id, 'description' ) : '';
		$desc = $desc ? $desc : ( $s['home_description'] ? $s['home_description'] : get_bloginfo( 'description' ) );
	} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
		$desc = semrs_seo_meta( (int) get_option( 'page_for_posts' ), 'description' );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$desc = semrs_seo_meta( $post->ID, 'description' );
		if ( ! $desc ) {
			$desc = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( excerpt_remove_blocks( $post->post_content ) );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	} elseif ( is_author() ) {
		$desc = get_the_author_meta( 'description', get_queried_object_id() );
	}
	// Tags become spaces first, so headings don't run into the previous sentence.
	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( preg_replace( '/<[^>]+>/', ' ', (string) $desc ) ) ) );
	$desc = preg_replace( '/\s+([.,;:!?])/', '$1', $desc );
	if ( mb_strlen( $desc ) > 160 ) {
		$desc = rtrim( mb_substr( $desc, 0, 155 ), " ,.;:-" ) . '…';
	}
	return $desc;
}

/**
 * Best share image for the current request.
 *
 * @return string
 */
function semrs_seo_image() {
	if ( is_singular() ) {
		$id  = get_queried_object_id();
		$img = semrs_seo_meta( $id, 'og_image' );
		if ( $img ) {
			return $img;
		}
		if ( has_post_thumbnail( $id ) ) {
			return (string) get_the_post_thumbnail_url( $id, 'full' );
		}
	}
	return (string) semrs_seo_settings()['og_image'];
}

/**
 * Canonical URL for the current request ('' = none).
 *
 * @return string
 */
function semrs_seo_canonical() {
	if ( is_search() || is_404() ) {
		return '';
	}
	if ( is_singular() ) {
		$custom = semrs_seo_meta( get_queried_object_id(), 'canonical' );
		return $custom ? $custom : (string) wp_get_canonical_url();
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	if ( is_front_page() || is_home() ) {
		$base = is_home() && get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$base = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$base = get_post_type_archive_link( get_query_var( 'post_type' ) );
	} elseif ( is_author() ) {
		$base = get_author_posts_url( get_queried_object_id() );
	} else {
		return '';
	}
	if ( is_wp_error( $base ) || ! $base ) {
		return '';
	}
	return $paged > 1 ? trailingslashit( $base ) . user_trailingslashit( 'page/' . $paged, 'paged' ) : $base;
}

/**
 * Register front-end output after all plugins load, so the conflict check
 * sees every SEO plugin regardless of load order.
 */
function semrs_seo_register_output() {
	if ( ! semrs_seo_active() ) {
		return; // Another SEO plugin handles output.
	}

	add_filter(
		'pre_get_document_title',
		function ( $title ) {
			$ours = semrs_seo_title();
			return '' !== $ours ? $ours : $title;
		},
		15
	);

	/**
	 * Robots directives (core wp_robots API).
	 */
	add_filter(
		'wp_robots',
		function ( $robots ) {
			$s       = semrs_seo_settings();
			$noindex = false;
			if ( is_singular() ) {
				$id      = get_queried_object_id();
				$noindex = semrs_seo_is_noindex( $id );
				if ( semrs_seo_meta( $id, 'nofollow' ) ) {
					$robots['nofollow'] = true;
				}
			} elseif ( is_search() || is_404() ) {
				$noindex = true;
			} elseif ( is_tag() && $s['noindex_tags'] ) {
				$noindex = true;
			} elseif ( is_author() && $s['noindex_authors'] ) {
				$noindex = true;
			} elseif ( is_date() && $s['noindex_dates'] ) {
				$noindex = true;
			}
			if ( $noindex ) {
				$robots['noindex'] = true;
				$robots['follow']  = empty( $robots['nofollow'] );
			} else {
				$robots['max-snippet']       = '-1';
				$robots['max-video-preview'] = '-1';
			}
			$robots['max-image-preview'] = 'large';
			return $robots;
		}
	);

	// Our canonical covers singular and archive pages.
	remove_action( 'wp_head', 'rel_canonical' );

	/**
	 * Print description, canonical, social and verification tags.
	 */
	function semrs_seo_head() {
		$s     = semrs_seo_settings();
		$desc  = semrs_seo_description();
		$canon = semrs_seo_canonical();
		$title = semrs_seo_title();
		$title = $title ? $title : wp_get_document_title();
		$image = semrs_seo_image();
		$type  = is_singular( 'post' ) ? 'article' : 'website';
		$url   = $canon ? $canon : home_url( add_query_arg( array() ) );

		echo "\n<!-- SEMRS SEO -->\n";
		if ( $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		if ( $canon && ! ( is_singular() && semrs_seo_is_noindex( get_queried_object_id() ) ) ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canon ) );
		}
		$og = array(
			'og:locale'    => get_locale(),
			'og:type'      => $type,
			'og:title'     => $title,
			'og:description' => $desc,
			'og:url'       => $url,
			'og:site_name' => get_bloginfo( 'name' ),
			'og:image'     => $image,
		);
		foreach ( $og as $prop => $val ) {
			if ( '' !== (string) $val ) {
				printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $prop ), 'og:url' === $prop || 'og:image' === $prop ? esc_url( $val ) : esc_attr( $val ) );
			}
		}
		if ( 'article' === $type ) {
			printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c' ) ) );
			printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c' ) ) );
		}
		printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
		if ( $s['twitter_site'] ) {
			printf( '<meta name="twitter:site" content="@%s">' . "\n", esc_attr( ltrim( $s['twitter_site'], '@' ) ) );
		}
		if ( is_front_page() ) {
			if ( $s['verify_google'] ) {
				printf( '<meta name="google-site-verification" content="%s">' . "\n", esc_attr( $s['verify_google'] ) );
			}
			if ( $s['verify_bing'] ) {
				printf( '<meta name="msvalidate.01" content="%s">' . "\n", esc_attr( $s['verify_bing'] ) );
			}
		}
		echo "<!-- /SEMRS SEO -->\n";
	}
	add_action( 'wp_head', 'semrs_seo_head', 1 );

	/**
	 * Attachment pages are thin: send them to the parent post (or the file).
	 */
	add_action(
		'template_redirect',
		function () {
			if ( ! is_attachment() || ! semrs_seo_settings()['attachment_redirect'] ) {
				return;
			}
			$post   = get_queried_object();
			$target = $post->post_parent ? get_permalink( $post->post_parent ) : wp_get_attachment_url( $post->ID );
			if ( $target ) {
				wp_safe_redirect( $target, 301 );
				exit;
			}
		}
	);
}
add_action( 'plugins_loaded', 'semrs_seo_register_output', 20 );
