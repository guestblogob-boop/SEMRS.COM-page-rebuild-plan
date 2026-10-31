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
		'pillar'      => (bool) semrs_seo_meta( $post->ID, 'pillar' ),
		'focus_more'  => (string) semrs_seo_meta( $post->ID, 'focus_more' ),
	);
	foreach ( $over as $k => $v ) {
		if ( array_key_exists( $k, $d ) && null !== $v ) {
			$d[ $k ] = $v;
		}
	}
	return $d;
}

/**
 * Whether the theme prints the post title as the page H1. Templates that leave
 * the title out (title-less landing templates, a hidden title) need one H1 in
 * the content instead.
 *
 * @param int $id Post ID.
 * @return bool
 */
function semrs_seo_title_printed( $id ) {
	$template = (string) get_page_template_slug( $id );
	$printed  = ! get_post_meta( $id, 'semrs_hide_title', true ) && ! preg_match( '#(?:^|/)(?:full-width|canvas|blank)[^/]*\.php$#', $template );
	return (bool) apply_filters( 'semrs_seo_title_printed', $printed, $id, $template );
}

/**
 * Rendered HTML for analysis (blocks and shortcodes expanded).
 *
 * @param array $d Input.
 * @return string
 */
function semrs_seo_analysis_html( $d ) {
	global $post;
	if ( (int) $d['id'] && (int) $d['id'] === (int) get_option( 'page_for_posts' ) ) {
		// The posts page shows its title, its own text as an intro (SEMRS theme) and the latest posts,
		// so score what visitors see.
		$intro = (bool) apply_filters( 'semrs_seo_posts_page_intro', 'semrs' === get_template() ) ? trim( (string) $d['content'] ) : '';
		$html  = '' !== trim( wp_strip_all_tags( $intro ) ) || false !== strpos( $intro, '<svg' ) ? (string) apply_filters( 'the_content', $intro ) : '<p>' . esc_html( (string) $d['post_title'] ) . '</p>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- core filter.
		foreach ( get_posts( array( 'posts_per_page' => (int) get_option( 'posts_per_page', 10 ), 'post_status' => 'publish', 'no_found_rows' => true ) ) as $p ) {
			$html .= '<h2><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></h2><p>' . esc_html( get_the_excerpt( $p ) ) . '</p>';
		}
		return $html;
	}
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
	// Tags become spaces, so text in neighbouring elements (buttons, cards, cells) is not glued into one long word.
	$text  = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( preg_replace( array( '/<(script|style)[^>]*>.*?<\/\1>/is', '/<[^>]+>/' ), array( '', ' ' ), $html ) ) ) );
	$lower = mb_strtolower( $text );
	$wlist = '' === $text ? array() : preg_split( '/\s+/u', $text );
	$words = count( $wlist );
	// "Beginning of the content": the first 10% of the words (at least the first 50).
	$first = mb_strtolower( implode( ' ', array_slice( $wlist, 0, max( 50, (int) ceil( $words * 0.1 ) ) ) ) );

	// Links.
	$internal = array();
	$outbound = 0;
	$dofollow = 0;
	if ( preg_match_all( '/<a\s([^>]*)>/i', $html, $m ) ) {
		foreach ( $m[1] as $attrs ) {
			if ( ! preg_match( '/href=["\']([^"\']+)["\']/i', $attrs, $hm ) ) {
				continue;
			}
			$path = semrs_seo_link_path( $hm[1] );
			if ( '' !== $path ) {
				$internal[] = $path;
			} elseif ( preg_match( '#^(?:https?:)?//#i', $hm[1] ) ) {
				$outbound++;
				if ( ! preg_match( '/rel=["\'][^"\']*\b(?:nofollow|sponsored|ugc)\b/i', $attrs ) ) {
					$dofollow++;
				}
			}
		}
	}
	$internal = array_values( array_unique( $internal ) );

	// Images and media.
	preg_match_all( '/<img\s[^>]*>/i', $html, $img_tags );
	$imgs   = count( $img_tags[0] );
	$no_alt = 0;
	$kw_alt = false;
	foreach ( $img_tags[0] as $tag ) {
		if ( ! preg_match( '/\salt=["\']([^"\']+)["\']/', $tag, $alt ) ) {
			$no_alt++;
		} elseif ( '' !== $kw && false !== mb_strpos( mb_strtolower( html_entity_decode( $alt[1] ) ), $kw ) ) {
			$kw_alt = true;
		}
	}
	// Inline SVG pictures with role="img" and a label are real images for visitors and screen readers.
	$svgs = 0;
	if ( preg_match_all( '/<svg\s[^>]*>/i', $html, $svg_tags ) ) {
		foreach ( $svg_tags[0] as $tag ) {
			if ( preg_match( '/\srole=["\']img["\']/i', $tag ) && preg_match( '/\saria-label=["\']([^"\']+)["\']/i', $tag, $label ) ) {
				$svgs++;
				if ( '' !== $kw && false !== mb_strpos( mb_strtolower( html_entity_decode( $label[1] ) ), $kw ) ) {
					$kw_alt = true;
				}
			}
		}
	}
	$media = $imgs + $svgs + preg_match_all( '/<video[\s>]|<iframe\s[^>]*(?:youtube|vimeo|wistia)/i', $html );

	// Headings.
	$h1s = preg_match_all( '/<h1[\s>]/i', $html );
	preg_match_all( '/<h[2-6][^>]*>(.*?)<\/h[2-6]>/is', $html, $hm );
	$subs    = count( $hm[1] );
	$sub_txt = mb_strtolower( wp_strip_all_tags( implode( ' | ', $hm[1] ) ) );

	// Sentences and paragraphs.
	// Headings, list items, table cells, buttons and other blocks end a sentence even without a full stop,
	// so a list of prices or features is not read as one very long sentence.
	$blocky    = preg_replace( '/<(script|style|svg)[^>]*>.*?<\/\1>/is', '', $html );
	$blocky    = preg_replace( '/<\/(?:p|li|h[1-6]|td|th|dt|dd|div|figcaption|summary|button|blockquote|section|article)>|<br\s*\/?>/i', "\n", $blocky );
	$sentences = array();
	foreach ( preg_split( '/\n+/', wp_strip_all_tags( preg_replace( '/<[^>]+>/', ' ', $blocky ) ) ) as $chunk ) {
		foreach ( preg_split( '/(?<=[.!?。])\s+/u', trim( preg_replace( '/\s+/u', ' ', $chunk ) ) ) as $x ) {
			if ( '' !== trim( $x ) ) {
				$sentences[] = $x;
			}
		}
	}
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
			if ( str_word_count( wp_strip_all_tags( $para ) ) > 120 ) {
				$long_para++;
			}
		}
	}

	$c   = array();
	// $fix: the field AI can rewrite to pass this check (shown as "Fix with AI" in the sidebar).
	$add = function ( $ok, $label, $group, $weight, $fix = '' ) use ( &$c ) {
		$c[] = array(
			'ok'     => (bool) $ok,
			'label'  => $label,
			'group'  => $group,
			'weight' => $weight,
			'fix'    => $fix,
		);
	};

	// Basic SEO.
	$is_post = 'page' !== $d['type'];
	if ( '' !== $kw ) {
		$add( false !== mb_strpos( mb_strtolower( $title ), $kw ), __( 'Focus keyword in the SEO title', 'semrs-core' ), 'basic', 8, 'title' );
		$add( '' !== $desc && false !== mb_strpos( mb_strtolower( $desc ), $kw ), __( 'Focus keyword in the meta description', 'semrs-core' ), 'basic', 5, 'description' );
		$add( semrs_seo_kw_in_url( $kw, $d ), __( 'Focus keyword in the URL', 'semrs-core' ), 'basic', 4 );
		$add( false !== mb_strpos( $first, $kw ), __( 'Focus keyword at the beginning of the content (first 10%)', 'semrs-core' ), 'basic', 5 );
		$add( false !== mb_strpos( $lower, $kw ), __( 'Focus keyword in the content', 'semrs-core' ), 'basic', 5 );
	} else {
		$add( false, __( 'Set a focus keyword for this content', 'semrs-core' ), 'basic', 20, 'focus' );
	}
	if ( $is_post ) {
		$long = ! empty( $d['pillar'] ) ? 1500 : 600;
		/* translators: 1: minimum words, 2: words now */
		$add( $words >= $long, sprintf( __( 'Content is at least %1$d words long; 2,500+ is best (now %2$d)', 'semrs-core' ), $long, $words ), 'basic', 8 );
	} else {
		/* translators: %d: words now */
		$add( $words >= 300, sprintf( __( 'At least 300 words on the page (now %d)', 'semrs-core' ), $words ), 'basic', 6 );
	}
	$tl = mb_strlen( $title );
	$dl = mb_strlen( $desc );
	/* translators: %d: characters */
	$add( $tl >= 30 && $tl <= 60, sprintf( __( 'SEO title 30–60 characters (now %d)', 'semrs-core' ), $tl ), 'basic', 6, 'title' );
	/* translators: %d: characters */
	$add( $dl >= 120 && $dl <= 160, sprintf( __( 'Meta description 120–160 characters (now %d)', 'semrs-core' ), $dl ), 'basic', 6, 'description' );

	// Additional.
	if ( '' !== $kw ) {
		$add( $subs > 0 && false !== mb_strpos( $sub_txt, $kw ), __( 'Focus keyword in a subheading (H2, H3, H4…)', 'semrs-core' ), 'additional', 4 );
		$add( $kw_alt, __( 'An image has the focus keyword in its alt text', 'semrs-core' ), 'additional', 3 );
		$hits    = '' === $lower ? 0 : substr_count( $lower, $kw );
		$density = $words ? round( $hits * max( 1, count( preg_split( '/\s+/u', $kw ) ) ) / $words * 100, 2 ) : 0;
		$add(
			$density >= 0.5 && $density <= 2.5,
			$density > 2.5
				/* translators: 1: density percent, 2: times used */
				? sprintf( __( 'Keyword density is too high: %1$s%% (used %2$d times). Use the keyword less, or synonyms, to avoid keyword stuffing', 'semrs-core' ), $density, $hits )
				/* translators: 1: density percent, 2: times used */
				: sprintf( __( 'Keyword density about 1%% (now %1$s%%, used %2$d times)', 'semrs-core' ), $density, $hits ),
			'additional',
			4
		);
		// Secondary keywords (Pro): each should appear at least once. Never asked for more, so no stuffing.
		$more = semrs_seo_pro() ? array_filter( array_map( 'trim', explode( ',', mb_strtolower( (string) ( $d['focus_more'] ?? '' ) ) ) ) ) : array();
		if ( $more ) {
			$missing = array_values( array_filter( $more, function ( $k ) use ( $lower ) { return false === mb_strpos( $lower, $k ); } ) );
			/* translators: %s: keywords */
			$add( ! $missing, $missing ? sprintf( __( 'Use each secondary keyword once in the content (missing: %s)', 'semrs-core' ), implode( ', ', $missing ) ) : __( 'Every secondary keyword appears in the content', 'semrs-core' ), 'additional', 2 );
		}
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
			'additional',
			4
		);
	}
	/* translators: %d: characters */
	$add( strlen( (string) $d['slug'] ) <= 75, sprintf( __( 'Short URL (%d characters, 75 max)', 'semrs-core' ), strlen( (string) $d['slug'] ) ), 'additional', 2 );
	/* translators: %d: links */
	$add( $outbound > 0, sprintf( __( 'Links out to useful outside sources (%d)', 'semrs-core' ), $outbound ), 'additional', 2 );
	/* translators: %d: links */
	$add( $dofollow > 0, sprintf( __( 'At least one DoFollow link to an outside source (%d)', 'semrs-core' ), $dofollow ), 'additional', 1 );
	/* translators: %d: links */
	$add( count( $internal ) > 0, sprintf( __( 'Internal links to your other pages (%d)', 'semrs-core' ), count( $internal ) ), 'additional', 6 );
	$add( ! empty( $d['thumb'] ), __( 'Featured image set (used for share cards and Google Discover)', 'semrs-core' ), 'additional', 3 );
	$add( 0 === $no_alt, $imgs ? sprintf( /* translators: %d: images */ __( 'All images have alt text (%d missing)', 'semrs-core' ), $no_alt ) : __( 'Images: none in the content yet', 'semrs-core' ), 'additional', $imgs ? 4 : 1 );
	if ( semrs_seo_title_printed( $d['id'] ) ) {
		$add( 0 === $h1s, __( 'No extra H1 in the content (the theme prints the title as H1)', 'semrs-core' ), 'additional', 3 );
	} else {
		/* translators: %d: H1 headings */
		$add( 1 === $h1s, sprintf( __( 'Exactly one H1 in the content, because this page hides its title (now %d)', 'semrs-core' ), $h1s ), 'additional', 3 );
	}

	// Title readability.
	$tlow = mb_strtolower( $title );
	if ( '' !== $kw ) {
		$pos = mb_strpos( $tlow, $kw );
		$add( false !== $pos && $pos <= max( 10, (int) ( mb_strlen( $tlow ) / 2 ) ), __( 'Focus keyword near the beginning of the SEO title', 'semrs-core' ), 'title', 4, 'title' );
	}
	$twords = preg_split( '/[^a-z0-9\'-]+/', $tlow );
	$add( (bool) array_intersect( $twords, semrs_seo_sentiment_words() ), __( 'Title has a positive or negative sentiment word (e.g. best, easy, mistakes, avoid)', 'semrs-core' ), 'title', 2, 'title' );
	$add( (bool) array_intersect( $twords, semrs_seo_power_words() ), __( 'Title has a power word (e.g. proven, complete, simple, essential)', 'semrs-core' ), 'title', 2, 'title' );
	$add( (bool) preg_match( '/\d/', $title ), __( 'Title contains a number (e.g. "7 ways", "2026")', 'semrs-core' ), 'title', 2, 'title' );

	// Content readability.
	if ( $words >= 50 ) {
		if ( $words > 600 ) {
			$add( (bool) preg_match( '/class=["\'][^"\']*(?:semrs-toc|rank-math-toc|wp-block-rank-math-toc|ez-toc|lwptoc)/i', $html ), __( 'Table of contents breaks down the text (SEMRS Table of contents block)', 'semrs-core' ), 'readability', 3 );
		}
		$add( 0 === $long_para, 0 === $long_para ? __( 'Short, concise paragraphs (120 words max)', 'semrs-core' ) : sprintf( /* translators: %d: paragraphs */ _n( '%d paragraph is over 120 words', '%d paragraphs are over 120 words', $long_para, 'semrs-core' ), $long_para ), 'readability', 3 );
		/* translators: %d: images and videos */
		$add( $media >= ( $is_post ? 2 : 1 ), sprintf( __( 'A few images or videos make the content appealing (%d)', 'semrs-core' ), $media ), 'readability', 3 );
		if ( $words > 300 ) {
			$need = (int) floor( $words / 350 );
			/* translators: 1: subheadings, 2: recommended */
			$add( $subs >= $need, sprintf( __( 'Subheadings break up the text (%1$d, at least %2$d)', 'semrs-core' ), $subs, $need ), 'readability', 4 );
		}
		$pct = round( $long_sent / $n_sent * 100 );
		/* translators: %d: percent */
		$add( $pct <= 25, sprintf( __( 'Short sentences: %d%% are over 25 words (keep under 25%%)', 'semrs-core' ), $pct ), 'readability', 4 );
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

	semrs_seo_speed_checks( $html, $img_tags[0], $add );

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
 * Is the focus keyword in the page address? Every meaningful word of the keyword must appear in
 * the full path (parent pages included), in any order; small words and simple plurals don't
 * matter, so /services/seo/ matches "SEO services".
 *
 * @param string $kw Focus keyword (lower case).
 * @param array  $d  Analysis input.
 * @return bool
 */
function semrs_seo_kw_in_url( $kw, $d ) {
	if ( (int) $d['id'] === (int) get_option( 'page_on_front' ) ) {
		return true; // The homepage address is the domain itself.
	}
	$path = (string) wp_parse_url( (string) get_permalink( $d['id'] ), PHP_URL_PATH );
	$segs = array_values( array_filter( explode( '/', $path ) ) );
	if ( $segs && ! empty( $d['slug'] ) ) {
		$segs[ count( $segs ) - 1 ] = (string) $d['slug']; // Unsaved slug from the editor.
	}
	$have  = array_filter( preg_split( '/[-\/]+/', strtolower( implode( '/', $segs ) ) ) );
	$small = array( 'a', 'an', 'and', 'the', 'for', 'of', 'to', 'in', 'on', 'with', 'by', 'at', 'or', 'how', 'your', 'you', 'is' );
	$small = array_merge( $small, explode( '-', sanitize_title( get_bloginfo( 'name' ) ) ) ); // The brand name is rarely in addresses.
	$need  = array_diff( array_filter( explode( '-', sanitize_title( $kw ) ) ), $small );
	if ( ! $need ) {
		return false;
	}
	foreach ( $need as $w ) {
		$stem = preg_replace( '/(ies|es|s)$/', '', $w );
		$hit  = false;
		foreach ( $have as $h ) {
			if ( $h === $w || preg_replace( '/(ies|es|s)$/', '', $h ) === $stem ) {
				$hit = true;
				break;
			}
		}
		if ( ! $hit ) {
			return false;
		}
	}
	return true;
}

/**
 * Words that give a title a clear positive or negative sentiment.
 *
 * @return string[]
 */
function semrs_seo_sentiment_words() {
	return array( 'best', 'better', 'easy', 'easiest', 'great', 'top', 'perfect', 'smart', 'simple', 'free', 'happy', 'love', 'winning', 'success', 'successful', 'effective', 'helpful', 'useful', 'worst', 'bad', 'avoid', 'mistakes', 'mistake', 'never', 'stop', 'wrong', 'fail', 'fails', 'failing', 'problems', 'risk', 'risks', 'warning', 'costly', 'hidden', 'myths', 'truth', 'honest', 'real' );
}

/**
 * Power words for titles.
 *
 * @return string[]
 */
function semrs_seo_power_words() {
	return array( 'proven', 'complete', 'ultimate', 'essential', 'simple', 'easy', 'quick', 'fast', 'step-by-step', 'guide', 'checklist', 'blueprint', 'expert', 'powerful', 'practical', 'secret', 'secrets', 'tips', 'tricks', 'strategies', 'hacks', 'instantly', 'effortless', 'surprising', 'critical', 'crucial', 'definitive', 'insider', 'actionable', 'affordable', 'smart', 'best', 'top', 'new', 'free', 'boost', 'grow', 'save', 'win' );
}

/**
 * Speed and accessibility checks on the rendered content: the rules that keep a page at
 * 100 in PageSpeed Insights (performance, accessibility, best practices, agentic browsing).
 *
 * @param string   $html Rendered content.
 * @param string[] $imgs <img> tags in the content.
 * @param callable $add  Adds a check: ( ok, label, group, weight ).
 */
function semrs_seo_speed_checks( $html, $imgs, $add ) {
	// Images: fixed size (no layout shift), light formats, small files.
	$no_size = 0;
	$old_fmt = 0;
	$gifs    = 0;
	$heavy   = 0;
	foreach ( $imgs as $tag ) {
		if ( ! preg_match( '/\swidth=["\']?\d/i', $tag ) || ! preg_match( '/\sheight=["\']?\d/i', $tag ) ) {
			$no_size++;
		}
		$src = preg_match( '/\ssrc=["\']([^"\']+)["\']/i', $tag, $m ) ? strtolower( strtok( $m[1], '?' ) ) : '';
		if ( preg_match( '/\.gif$/', $src ) ) {
			$gifs++;
		} elseif ( preg_match( '/\.(?:jpe?g|png|bmp|tiff?)$/', $src ) ) {
			$old_fmt++;
		}
		if ( preg_match( '/wp-image-(\d+)/', $tag, $id ) ) {
			$file = get_attached_file( (int) $id[1] );
			if ( $file && file_exists( $file ) && filesize( $file ) > 150 * KB_IN_BYTES ) {
				$heavy++;
			}
		}
	}
	if ( $imgs ) {
		/* translators: %d: images */
		$add( 0 === $no_size, sprintf( __( 'Images have a width and height, so the page does not jump while loading (%d missing)', 'semrs-core' ), $no_size ), 'speed', 4 );
		/* translators: %d: images */
		$add( 0 === $old_fmt, sprintf( __( 'Images are WebP, AVIF or SVG (%d JPEG/PNG to convert)', 'semrs-core' ), $old_fmt ), 'speed', 3 );
		/* translators: %d: images */
		$add( 0 === $heavy, sprintf( __( 'Image files are under 150 KB (%d larger)', 'semrs-core' ), $heavy ), 'speed', 3 );
		/* translators: %d: images */
		$add( 0 === $gifs, sprintf( __( 'No GIF images: use a still image or a short video (%d found)', 'semrs-core' ), $gifs ), 'speed', 2 );
	}

	// Embeds load only when scrolled to.
	if ( preg_match_all( '/<iframe\s[^>]*>/i', $html, $frames ) ) {
		$eager = count( array_filter( $frames[0], function ( $f ) { return ! preg_match( '/loading=["\']lazy/i', $f ); } ) );
		/* translators: %d: embeds */
		$add( 0 === $eager, sprintf( __( 'Embedded videos and maps load lazily (%d load at once)', 'semrs-core' ), $eager ), 'speed', 2 );
	}

	// Valid structure for screen readers and AI agents.
	$bad_list = 0;
	if ( preg_match_all( '/<(ul|ol)\b[^>]*>(.*?)<\/\1>/is', $html, $lists ) ) {
		foreach ( $lists[2] as $inner ) {
			$inner = preg_replace( '/<(ul|ol)\b.*?<\/\1>/is', '', $inner ); // Nested lists are checked on their own.
			$depth = 0;
			if ( preg_match_all( '/<(\/?)([a-z][a-z0-9-]*)\b[^>]*?(\/?)>/i', $inner, $tags, PREG_SET_ORDER ) ) {
				foreach ( $tags as $t ) {
					$name = strtolower( $t[2] );
					if ( in_array( $name, array( 'br', 'img', 'input', 'hr', 'wbr', 'source', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'use', 'stop' ), true ) ) {
						$bad_list += ( 0 === $depth && ! $t[1] ) ? 1 : 0;
						continue;
					}
					if ( $t[1] ) {
						$depth--;
						continue;
					}
					if ( 0 === $depth && ! in_array( $name, array( 'li', 'script', 'template' ), true ) ) {
						$bad_list++;
					}
					if ( ! $t[3] ) {
						$depth++;
					}
				}
			}
		}
	}
	$bad_role = preg_match_all( '/<li\b[^>]*\srole=/i', $html ) + preg_match_all( '/<figure\b[^>]*\srole=["\']img["\'][^>]*>(?:(?!<\/figure>).)*<figcaption/is', $html );
	$add( 0 === $bad_list, __( 'Lists contain only list items (<li>)', 'semrs-core' ), 'speed', 3 );
	$add( 0 === $bad_role, __( 'ARIA roles fit their elements (no role on <li>, no role="img" on a captioned figure)', 'semrs-core' ), 'speed', 3 );
	$empty_links = 0;
	if ( preg_match_all( '/<a\s([^>]*)>(.*?)<\/a>/is', $html, $links, PREG_SET_ORDER ) ) {
		foreach ( $links as $l ) {
			if ( '' === trim( wp_strip_all_tags( $l[2] ) ) && ! preg_match( '/aria-label=["\'][^"\']+|title=["\'][^"\']+/i', $l[1] ) && ! preg_match( '/<img[^>]*alt=["\'][^"\']+/i', $l[2] ) ) {
				$empty_links++;
			}
		}
	}
	/* translators: %d: links */
	$add( 0 === $empty_links, sprintf( __( 'Every link has readable text (%d empty)', 'semrs-core' ), $empty_links ), 'speed', 2 );
	$skips = 0;
	if ( preg_match_all( '/<h([2-6])[\s>]/i', $html, $hs ) ) {
		$prev = 1;
		foreach ( $hs[1] as $lvl ) {
			$skips += ( (int) $lvl > $prev + 1 ) ? 1 : 0;
			$prev   = (int) $lvl;
		}
	}
	/* translators: %d: headings */
	$add( 0 === $skips, sprintf( __( 'Headings go in order without skipping a level (%d skipped)', 'semrs-core' ), $skips ), 'speed', 2 );

	// Colours that follow the theme: fixed light backgrounds become unreadable in dark mode.
	$fixed = 0;
	if ( preg_match_all( '/style=["\'][^"\']*background(?:-color)?:\s*(#[0-9a-f]{3,6}\b|white\b)/i', $html, $bgs ) ) {
		foreach ( $bgs[1] as $hex ) {
			$hex = 'white' === strtolower( $hex ) ? 'ffffff' : ltrim( $hex, '#' );
			$hex = 3 === strlen( $hex ) ? $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] : str_pad( $hex, 6, '0' );
			$lum = ( 0.2126 * hexdec( substr( $hex, 0, 2 ) ) + 0.7152 * hexdec( substr( $hex, 2, 2 ) ) + 0.0722 * hexdec( substr( $hex, 4, 2 ) ) ) / 255;
			$fixed += $lum > 0.85 ? 1 : 0;
		}
	}
	/* translators: %d: elements */
	$add( 0 === $fixed, sprintf( __( 'Background colours come from the theme, not fixed light colours (%d fixed; they break dark mode)', 'semrs-core' ), $fixed ), 'speed', 2 );
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
			foreach ( array( 'content', 'post_title', 'slug', 'focus', 'focus_more', 'title', 'description' ) as $k ) {
				if ( isset( $_POST[ $k ] ) ) {
					$raw        = wp_unslash( $_POST[ $k ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized below.
					$over[ $k ] = 'content' === $k ? wp_kses_post( $raw ) : sanitize_text_field( $raw );
				}
			}
			if ( isset( $over['slug'] ) ) {
				$over['slug'] = sanitize_title( $over['slug'] );
			}
			if ( isset( $over['focus'] ) ) {
				$over['focus'] = trim( (string) strtok( $over['focus'], ',' ) ); // The first keyword is the primary one.
			}
			foreach ( array( 'title', 'description' ) as $k ) {
				if ( ! empty( $over[ $k ] ) ) {
					$over[ $k ] = semrs_seo_rm_vars( $over[ $k ], $id );
				}
			}
			if ( isset( $_POST['pillar'] ) ) {
				$over['pillar'] = ! empty( $_POST['pillar'] );
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
					'html'   => semrs_seo_checks_html( $r ),
					'links'  => semrs_seo_link_suggestions( $d, $r['links'] ),
					'score'  => $r['score'],
					'checks' => $r['checks'],
					'groups' => semrs_seo_check_groups(),
				)
			);
		}
	);
}

/**
 * Check groups in display order (Rank Math's groups plus Speed & accessibility).
 *
 * @return array<string, string>
 */
function semrs_seo_check_groups() {
	return array(
		'basic'       => __( 'Basic SEO', 'semrs-core' ),
		'additional'  => __( 'Additional', 'semrs-core' ),
		'title'       => __( 'Title readability', 'semrs-core' ),
		'readability' => __( 'Content readability', 'semrs-core' ),
		'speed'       => __( 'Speed & accessibility', 'semrs-core' ),
	);
}

/**
 * Checks list HTML, grouped, with the score.
 *
 * @param array $r Result of semrs_seo_analyze().
 * @return string
 */
function semrs_seo_checks_html( $r ) {
	$groups = semrs_seo_check_groups();
	$out    = sprintf(
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
