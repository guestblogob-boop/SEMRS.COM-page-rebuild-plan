<?php
/**
 * Content analysis: a weighted 0–100 SEO score with basic, content and
 * readability checks, internal link suggestions, the score column on post
 * lists and a "Re-check" that analyses unsaved editor content.
 *
 * Everything runs on your own server. Nothing is sent anywhere.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Analysis input for a saved post.
 *
 * @param WP_Post $post Post.
 * @param array   $over Unsaved values from the editor (content, post_title, slug, focus, title, description, thumb).
 * @return array
 */
function semrs_seo_analysis_input( $post, $over = array() ) {
	$d = array(
		'id'          => (int) $post->ID,
		'type'        => $post->post_type,
		'content'     => $post->post_content,
		'post_title'  => $post->post_title,
		'slug'        => $post->post_name ? $post->post_name : sanitize_title( $post->post_title ),
		'focus'       => (string) semrs_seo_meta( $post->ID, 'focus' ),
		'title'       => (string) semrs_seo_meta( $post->ID, 'title' ),
		'description' => (string) semrs_seo_meta( $post->ID, 'description' ),
		'thumb'       => has_post_thumbnail( $post ),
	);
	foreach ( $over as $k => $v ) {
		if ( array_key_exists( $k, $d ) && null !== $v ) {
			$d[ $k ] = $v;
		}
	}
	return $d;
}

/**
 * Rendered HTML for analysis (blocks and shortcodes expanded).
 *
 * @param array $d Input.
 * @return string
 */
function semrs_seo_analysis_html( $d ) {
	global $post;
	$saved = $post;
	$post  = get_post( $d['id'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- restored below.
	if ( $post ) {
		setup_postdata( $post );
	}
	$html = apply_filters( 'the_content', $d['content'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- core filter.
	$post = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	if ( $saved ) {
		setup_postdata( $saved );
	}
	return (string) $html;
}

/**
 * Rough English syllable count (for the Flesch reading-ease score).
 *
 * @param string $word Word.
 * @return int
 */
function semrs_seo_syllables( $word ) {
	$word = preg_replace( '/[^a-z]/', '', strtolower( $word ) );
	if ( strlen( $word ) <= 3 ) {
		return 1;
	}
	$word = preg_replace( '/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word );
	$word = preg_replace( '/^y/', '', $word );
	return max( 1, preg_match_all( '/[aeiouy]{1,2}/', $word ) );
}

/**
 * Normalise an internal URL to a path for link matching.
 *
 * @param string $url URL or root-relative path.
 * @return string Path with trailing slash, or '' if external.
 */
function semrs_seo_link_path( $url ) {
	$url  = html_entity_decode( trim( $url ) );
	$home = wp_parse_url( home_url( '/' ) );
	$p    = wp_parse_url( $url );
	if ( ! $p || ( isset( $p['host'] ) && strtolower( $p['host'] ) !== strtolower( $home['host'] ?? '' ) ) ) {
		return '';
	}
	if ( ! isset( $p['host'] ) && ( ! isset( $p['path'] ) || '/' !== substr( $p['path'], 0, 1 ) ) ) {
		return ''; // Anchors, mailto:, tel: and relative links.
	}
	$path = isset( $p['path'] ) ? $p['path'] : '/';
	if ( preg_match( '/\.(?:jpe?g|png|gif|webp|avif|svg|pdf|zip|mp4|mp3)$/i', $path ) ) {
		return '';
	}
	return trailingslashit( strtolower( $path ) );
}

/**
 * Analyse a post. Returns the score, the checks and the internal links found.
 *
 * @param array $d Input from semrs_seo_analysis_input().
 * @return array{score:int, checks:array, links:array, words:int}
 */
function semrs_seo_analyze( $d ) {
	$s     = semrs_seo_settings();
	$kw    = trim( mb_strtolower( (string) $d['focus'] ) );
	$title = '' !== (string) $d['title'] ? (string) $d['title'] : semrs_seo_fill( $s['tpl_singular'], array( 'title' => $d['post_title'] ) );
	$desc  = (string) $d['description'];
	$html  = semrs_seo_analysis_html( $d );
	$text  = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( preg_replace( '/<(script|style)[^>]*>.*?<\/\1>/is', '', $html ) ) ) );
	$lower = mb_strtolower( $text );
	$wlist = '' === $text ? array() : preg_split( '/\s+/u', $text );
	$words = count( $wlist );
	$first = mb_strtolower( implode( ' ', array_slice( $wlist, 0, 100 ) ) );

	// Links.
	$internal = array();
	$outbound = 0;
	if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $m ) ) {
		foreach ( $m[1] as $href ) {
			$path = semrs_seo_link_path( $href );
			if ( '' !== $path ) {
				$internal[] = $path;
			} elseif ( preg_match( '#^(?:https?:)?//#i', $href ) ) {
				$outbound++;
			}
		}
	}
	$internal = array_values( array_unique( $internal ) );

	// Images.
	preg_match_all( '/<img\s[^>]*>/i', $html, $img_tags );
	$imgs   = count( $img_tags[0] );
	$no_alt = 0;
	foreach ( $img_tags[0] as $tag ) {
		if ( ! preg_match( '/\salt=["\'][^"\']+["\']/', $tag ) ) {
			$no_alt++;
		}
	}

	// Headings.
	$h1s = preg_match_all( '/<h1[\s>]/i', $html );
	preg_match_all( '/<h[2-6][^>]*>(.*?)<\/h[2-6]>/is', $html, $hm );
	$subs    = count( $hm[1] );
	$sub_txt = mb_strtolower( wp_strip_all_tags( implode( ' | ', $hm[1] ) ) );

	// Sentences and paragraphs.
	$sentences = array_values( array_filter( preg_split( '/(?<=[.!?。])\s+/u', $text ), function ( $x ) { return '' !== trim( $x ); } ) );
	$n_sent    = max( 1, count( $sentences ) );
	$long_sent = 0;
	foreach ( $sentences as $sent ) {
		if ( count( preg_split( '/\s+/u', trim( $sent ) ) ) > 25 ) {
			$long_sent++;
		}
	}
	$long_para = 0;
	if ( preg_match_all( '/<p[\s>].*?<\/p>/is', $html, $pm ) ) {
		foreach ( $pm[0] as $para ) {
			if ( str_word_count( wp_strip_all_tags( $para ) ) > 150 ) {
				$long_para++;
			}
		}
	}

	$c   = array();
	$add = function ( $ok, $label, $group, $weight ) use ( &$c ) {
		$c[] = array(
			'ok'     => (bool) $ok,
			'label'  => $label,
			'group'  => $group,
			'weight' => $weight,
		);
	};

	// Basic.
	if ( '' !== $kw ) {
		$add( false !== mb_strpos( mb_strtolower( $title ), $kw ), __( 'Focus keyword in SEO title', 'semrs-core' ), 'basic', 8 );
		$add( '' !== $desc && false !== mb_strpos( mb_strtolower( $desc ), $kw ), __( 'Focus keyword in meta description', 'semrs-core' ), 'basic', 5 );
		$add( false !== strpos( (string) $d['slug'], sanitize_title( $kw ) ), __( 'Focus keyword in URL slug', 'semrs-core' ), 'basic', 4 );
		$add( false !== mb_strpos( $first, $kw ), __( 'Focus keyword in the first 100 words', 'semrs-core' ), 'basic', 5 );
		$others = array_values(
			array_diff(
				get_posts(
					array(
						'post_type'      => 'any',
						'post_status'    => 'publish',
						'posts_per_page' => 2,
						'fields'         => 'ids',
						'meta_key'       => '_semrs_focus', // phpcs:ignore WordPress.DB.SlowDBQuery -- one indexed lookup.
						'meta_value'     => $d['focus'], // phpcs:ignore WordPress.DB.SlowDBQuery
						'no_found_rows'  => true,
					)
				),
				array( (int) $d['id'] )
			)
		);
		$add(
			empty( $others ),
			empty( $others ) ? __( 'Focus keyword not used on another page', 'semrs-core' ) : sprintf( /* translators: %s: page title */ __( 'Focus keyword is also used on "%s" (pages compete with each other)', 'semrs-core' ), get_the_title( $others[0] ) ),
			'basic',
			4
		);
	} else {
		$add( false, __( 'Set a focus keyword', 'semrs-core' ), 'basic', 20 );
	}
	$tl = mb_strlen( $title );
	$dl = mb_strlen( $desc );
	/* translators: %d: characters */
	$add( $tl >= 30 && $tl <= 60, sprintf( __( 'SEO title 30–60 characters (now %d)', 'semrs-core' ), $tl ), 'basic', 6 );
	/* translators: %d: characters */
	$add( $dl >= 120 && $dl <= 155, sprintf( __( 'Meta description 120–155 characters (now %d)', 'semrs-core' ), $dl ), 'basic', 6 );
	/* translators: %d: characters */
	$add( strlen( (string) $d['slug'] ) <= 75, sprintf( __( 'Short URL slug (%d characters, 75 max)', 'semrs-core' ), strlen( (string) $d['slug'] ) ), 'basic', 2 );

	// Content.
	$min = 'page' === $d['type'] ? 200 : 300;
	/* translators: 1: minimum words, 2: words now */
	$add( $words >= $min, sprintf( __( 'At least %1$d words (now %2$d)', 'semrs-core' ), $min, $words ), 'content', 8 );
	/* translators: %d: links */
	$add( count( $internal ) > 0, sprintf( __( 'Internal links to your other pages (%d)', 'semrs-core' ), count( $internal ) ), 'content', 6 );
	/* translators: %d: links */
	$add( $outbound > 0, sprintf( __( 'Link to a useful outside source (%d)', 'semrs-core' ), $outbound ), 'content', 2 );
	$add( 0 === $no_alt, $imgs ? sprintf( /* translators: %d: images */ __( 'All images have alt text (%d missing)', 'semrs-core' ), $no_alt ) : __( 'Images: none in the content (add one if it helps the reader)', 'semrs-core' ), 'content', $imgs ? 4 : 1 );
	$add( ! empty( $d['thumb'] ), __( 'Featured image set (used for share cards and Google Discover)', 'semrs-core' ), 'content', 3 );
	$add( 0 === $h1s, __( 'No extra H1 in the content (the theme prints the title as H1)', 'semrs-core' ), 'content', 3 );
	if ( $words > 300 ) {
		$need = (int) floor( $words / 350 );
		/* translators: 1: subheadings, 2: recommended */
		$add( $subs >= $need, sprintf( __( 'Subheadings break up the text (%1$d, at least %2$d)', 'semrs-core' ), $subs, $need ), 'content', 4 );
	}
	if ( '' !== $kw ) {
		$add( $subs > 0 && false !== mb_strpos( $sub_txt, $kw ), __( 'Focus keyword in a subheading (H2–H3)', 'semrs-core' ), 'content', 4 );
		$hits    = '' === $lower ? 0 : substr_count( $lower, $kw );
		$density = $words ? round( $hits * max( 1, count( preg_split( '/\s+/u', $kw ) ) ) / $words * 100, 1 ) : 0;
		/* translators: 1: density percent, 2: times used */
		$add( $density >= 0.5 && $density <= 3, sprintf( __( 'Keyword density 0.5–3%% (now %1$s%%, used %2$d times)', 'semrs-core' ), $density, $hits ), 'content', 4 );
	}

	// Readability.
	if ( $words >= 50 ) {
		$pct = round( $long_sent / $n_sent * 100 );
		/* translators: %d: percent */
		$add( $pct <= 25, sprintf( __( 'Short sentences: %d%% are over 25 words (keep under 25%%)', 'semrs-core' ), $pct ), 'readability', 4 );
		$add( 0 === $long_para, 0 === $long_para ? __( 'Paragraphs are a comfortable length', 'semrs-core' ) : sprintf( /* translators: %d: paragraphs */ _n( '%d paragraph is over 150 words', '%d paragraphs are over 150 words', $long_para, 'semrs-core' ), $long_para ), 'readability', 3 );
		if ( 0 === strpos( get_locale(), 'en' ) ) {
			$syll = 0;
			foreach ( $wlist as $w ) {
				$syll += semrs_seo_syllables( $w );
			}
			$flesch = round( 206.835 - 1.015 * ( $words / $n_sent ) - 84.6 * ( $syll / max( 1, $words ) ) );
			/* translators: %d: score */
			$add( $flesch >= 50, sprintf( __( 'Easy to read: Flesch score %d (50+ is fairly easy)', 'semrs-core' ), $flesch ), 'readability', 3 );
		}
	}

	$total = 0;
	$got   = 0;
	foreach ( $c as $check ) {
		$total += $check['weight'];
		$got   += $check['ok'] ? $check['weight'] : 0;
	}
	return array(
		'score'  => $total ? (int) round( $got / $total * 100 ) : 0,
		'checks' => $c,
		'links'  => $internal,
		'words'  => $words,
	);
}

/**
 * Pages worth linking to from this post, not already linked.
 *
 * @param array $d      Input.
 * @param array $linked Internal paths already linked.
 * @return array<int, array{title:string,url:string}>
 */
function semrs_seo_link_suggestions( $d, $linked ) {
	$terms = '' !== trim( (string) $d['focus'] ) ? array( $d['focus'] ) : array();
	// Fall back to the longest words of the title.
	$words = array_filter( preg_split( '/\s+/u', mb_strtolower( wp_strip_all_tags( $d['post_title'] ) ) ), function ( $w ) { return mb_strlen( $w ) > 4; } );
	usort( $words, function ( $a, $b ) { return mb_strlen( $b ) - mb_strlen( $a ); } );
	$terms = array_merge( $terms, array_slice( $words, 0, 2 ) );
	$out   = array();
	foreach ( $terms as $term ) {
		$q = new WP_Query(
			array(
				's'                   => $term,
				'post_type'           => semrs_seo_public_types(),
				'post_status'         => 'publish',
				'posts_per_page'      => 7,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);
		foreach ( $q->posts as $p ) {
			$url = get_permalink( $p );
			if ( (int) $p->ID === (int) $d['id'] || isset( $out[ $p->ID ] ) || in_array( semrs_seo_link_path( $url ), $linked, true ) || semrs_seo_is_noindex( $p->ID ) ) {
				continue;
			}
			$out[ $p->ID ] = array(
				'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
				'url'   => $url,
			);
		}
		if ( count( $out ) >= 5 ) {
			break;
		}
	}
	return array_slice( array_values( $out ), 0, 5 );
}

/**
 * Public post types analysed by SEMRS SEO.
 *
 * @return string[]
 */
function semrs_seo_public_types() {
	return array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
}

/**
 * Score colour class.
 *
 * @param int|string $score Score or ''.
 * @return string
 */
function semrs_seo_score_class( $score ) {
	if ( '' === $score || null === $score ) {
		return 'none';
	}
	return $score >= 80 ? 'good' : ( $score >= 50 ? 'ok' : 'bad' );
}

/**
 * Store the score after a save (the SEO box saves first, at priority 10).
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function semrs_seo_store_score( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status || 'trash' === $post->post_status || ! in_array( $post->post_type, semrs_seo_public_types(), true ) ) {
		return;
	}
	$r = semrs_seo_analyze( semrs_seo_analysis_input( $post ) );
	update_post_meta( $post_id, '_semrs_score', $r['score'] );
}
add_action( 'save_post', 'semrs_seo_store_score', 30, 2 );

if ( is_admin() ) {
	/**
	 * "SEO" column on post lists.
	 */
	add_action(
		'admin_init',
		function () {
			foreach ( semrs_seo_public_types() as $type ) {
				add_filter(
					"manage_{$type}_posts_columns",
					function ( $cols ) {
						$cols['semrs_seo'] = __( 'SEO', 'semrs-core' );
						return $cols;
					}
				);
				add_filter(
					"manage_edit-{$type}_sortable_columns",
					function ( $cols ) {
						$cols['semrs_seo'] = 'semrs_score';
						return $cols;
					}
				);
				add_action(
					"manage_{$type}_posts_custom_column",
					function ( $col, $id ) {
						if ( 'semrs_seo' !== $col ) {
							return;
						}
						$score = get_post_meta( $id, '_semrs_score', true );
						$kw    = semrs_seo_meta( $id, 'focus' );
						printf(
							'<span class="semrs-score semrs-score--%1$s" title="%2$s">%3$s</span>',
							esc_attr( semrs_seo_score_class( $score ) ),
							esc_attr__( 'SEMRS SEO score (updated when the post is saved or an SEO audit runs)', 'semrs-core' ),
							'' === $score ? '–' : esc_html( $score )
						);
						if ( $kw ) {
							echo '<br><small>' . esc_html( $kw ) . '</small>';
						}
						if ( semrs_seo_is_noindex( $id ) ) {
							echo '<br><small class="semrs-noindex">' . esc_html__( 'noindex', 'semrs-core' ) . '</small>';
						}
					},
					10,
					2
				);
			}
		}
	);

	add_action(
		'pre_get_posts',
		function ( $q ) {
			if ( $q->is_main_query() && 'semrs_score' === $q->get( 'orderby' ) ) {
				$q->set( 'meta_key', '_semrs_score' );
				$q->set( 'orderby', 'meta_value_num' );
			}
		}
	);

	add_action(
		'admin_head-edit.php',
		function () {
			echo '<style>.column-semrs_seo{width:84px}.semrs-score{display:inline-block;min-width:30px;padding:2px 6px;border-radius:12px;text-align:center;font-weight:600;font-size:12px;color:#fff;background:#8c8f94}.semrs-score--good{background:#00813a}.semrs-score--ok{background:#b26200}.semrs-score--bad{background:#b32d2e}.semrs-noindex{color:#b32d2e}</style>';
		}
	);

	/**
	 * Re-check unsaved editor content (the SEO box "Re-check" button).
	 */
	add_action(
		'wp_ajax_semrs_seo_analyze',
		function () {
			check_ajax_referer( 'semrs_seo_box', 'nonce' );
			$id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$post = get_post( $id );
			if ( ! $post || ! current_user_can( 'edit_post', $id ) ) {
				wp_send_json_error( null, 403 );
			}
			$over = array();
			foreach ( array( 'content', 'post_title', 'slug', 'focus', 'title', 'description' ) as $k ) {
				if ( isset( $_POST[ $k ] ) ) {
					$raw        = wp_unslash( $_POST[ $k ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below.
					$over[ $k ] = 'content' === $k ? wp_kses_post( $raw ) : sanitize_text_field( $raw );
				}
			}
			if ( isset( $over['slug'] ) ) {
				$over['slug'] = sanitize_title( $over['slug'] );
			}
			if ( isset( $_POST['thumb'] ) ) {
				$over['thumb'] = ! empty( $_POST['thumb'] );
			}
			// Empty SEO title/description/focus fields fall back to the saved/imported values.
			foreach ( array( 'focus', 'title', 'description' ) as $k ) {
				if ( isset( $over[ $k ] ) && '' === $over[ $k ] ) {
					unset( $over[ $k ] );
				}
			}
			$d = semrs_seo_analysis_input( $post, $over );
			$r = semrs_seo_analyze( $d );
			wp_send_json_success(
				array(
					'html'  => semrs_seo_checks_html( $r ),
					'links' => semrs_seo_link_suggestions( $d, $r['links'] ),
				)
			);
		}
	);
}

/**
 * Checks list HTML, grouped, with the score.
 *
 * @param array $r Result of semrs_seo_analyze().
 * @return string
 */
function semrs_seo_checks_html( $r ) {
	$groups = array(
		'basic'       => __( 'Basic SEO', 'semrs-core' ),
		'content'     => __( 'Content', 'semrs-core' ),
		'readability' => __( 'Readability', 'semrs-core' ),
	);
	$out = sprintf(
		'<div class="semrs-score-big semrs-score--%1$s"><strong>%2$d</strong><span>/100</span></div>',
		esc_attr( semrs_seo_score_class( $r['score'] ) ),
		(int) $r['score']
	);
	foreach ( $groups as $g => $label ) {
		$items = array_filter( $r['checks'], function ( $c ) use ( $g ) { return $c['group'] === $g; } );
		if ( ! $items ) {
			continue;
		}
		$fails = count( array_filter( $items, function ( $c ) { return ! $c['ok']; } ) );
		$out  .= '<details class="semrs-check-group"' . ( $fails ? ' open' : '' ) . '><summary>' . esc_html( $label ) . ' <span class="semrs-check-count">' . ( $fails ? sprintf( /* translators: %d: issues */ esc_html( _n( '%d to fix', '%d to fix', $fails, 'semrs-core' ) ), $fails ) : esc_html__( 'all good', 'semrs-core' ) ) . '</span></summary><ul class="semrs-checks">';
		foreach ( $items as $c ) {
			$out .= '<li class="' . ( $c['ok'] ? 'ok' : 'no' ) . '">' . esc_html( $c['label'] ) . '</li>';
		}
		$out .= '</ul></details>';
	}
	return $out;
}
