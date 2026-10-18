<?php
/**
 * /llms.txt: a short Markdown map of the site for AI assistants
 * (llmstxt.org format). Lists the homepage summary, main pages and recent
 * posts with their meta descriptions. Noindexed content is never listed.
 * A real llms.txt file in the site root always wins.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the llms.txt body.
 *
 * @return string
 */
function semrs_seo_llms_body() {
	$cached = get_transient( 'semrs_llms' );
	if ( false !== $cached ) {
		return $cached;
	}
	$s     = semrs_seo_settings();
	$clean = function ( $t ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( html_entity_decode( (string) $t, ENT_QUOTES, 'UTF-8' ) ) ) );
	};
	$line = function ( $post ) use ( $clean ) {
		$desc = (string) semrs_seo_meta( $post->ID, 'description' );
		if ( '' === $desc ) {
			$desc = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( strip_shortcodes( $post->post_content ), 25, '…' );
		}
		$desc = $clean( $desc );
		return '- [' . $clean( get_the_title( $post ) ) . '](' . get_permalink( $post ) . ')' . ( '' !== $desc ? ': ' . $desc : '' ) . "\n";
	};
	$visible = function ( $posts ) {
		return array_filter(
			$posts,
			function ( $p ) {
				return ! semrs_seo_is_noindex( $p->ID ) && ! post_password_required( $p );
			}
		);
	};

	$summary = $s['home_description'] ? $s['home_description'] : get_bloginfo( 'description' );
	$out     = '# ' . $clean( get_bloginfo( 'name' ) ) . "\n\n";
	if ( '' !== $clean( $summary ) ) {
		$out .= '> ' . $clean( $summary ) . "\n\n";
	}
	$out .= '- [' . __( 'Home', 'semrs-core' ) . '](' . home_url( '/' ) . ")\n\n";

	$front = (int) get_option( 'page_on_front' );
	$pages = $visible(
		get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 40,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'has_password'   => false,
			)
		)
	);
	$pages = array_filter(
		$pages,
		function ( $p ) use ( $front ) {
			return (int) $p->ID !== $front;
		}
	);
	if ( $pages ) {
		$out .= '## ' . __( 'Pages', 'semrs-core' ) . "\n\n";
		foreach ( $pages as $p ) {
			$out .= $line( $p );
		}
		$out .= "\n";
	}

	$types = array_diff( semrs_seo_public_types(), array( 'page' ) );
	foreach ( $types as $type ) {
		$obj   = get_post_type_object( $type );
		$posts = $visible(
			get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'publish',
					'posts_per_page' => 'post' === $type ? 30 : 20,
					'has_password'   => false,
				)
			)
		);
		if ( ! $posts ) {
			continue;
		}
		$out .= '## ' . ( 'post' === $type ? __( 'Latest articles', 'semrs-core' ) : $obj->labels->name ) . "\n\n";
		foreach ( $posts as $p ) {
			$out .= $line( $p );
		}
		$out .= "\n";
	}
	if ( $s['sitemap'] ) {
		$out .= '## ' . __( 'Optional', 'semrs-core' ) . "\n\n- [" . __( 'XML sitemap (every page)', 'semrs-core' ) . '](' . home_url( '/wp-sitemap.xml' ) . ")\n";
	}
	$out = apply_filters( 'semrs_llms_txt', $out );
	set_transient( 'semrs_llms', $out, DAY_IN_SECONDS );
	return $out;
}

add_action(
	'parse_request',
	function ( $wp ) {
		if ( 'llms.txt' !== $wp->request || ! semrs_seo_settings()['llms'] || ! semrs_seo_active() || ! get_option( 'blog_public' ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo semrs_seo_llms_body(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text built from cleaned strings.
		exit;
	},
	1
);

foreach ( array( 'save_post', 'deleted_post', 'update_option_semrs_seo', 'update_option_blogdescription', 'update_option_blogname' ) as $semrs_hook ) {
	add_action(
		$semrs_hook,
		function () {
			delete_transient( 'semrs_llms' );
		}
	);
}
unset( $semrs_hook );
