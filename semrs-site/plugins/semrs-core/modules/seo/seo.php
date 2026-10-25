<?php
/**
 * SEO module bootstrap: settings, conflict guard, data helpers.
 *
 * Output pauses automatically while Rank Math, Yoast, AIOSEO, SEOPress or
 * The SEO Framework is active (no duplicate tags). Existing Rank Math
 * titles, descriptions, focus keywords, canonicals and robots are read as
 * fallbacks, so switching loses no SEO data.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Name of an active SEO plugin, or '' when none.
 *
 * @return string
 */
function semrs_seo_conflict() {
	if ( defined( 'RANK_MATH_VERSION' ) ) {
		return 'Rank Math';
	}
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'Yoast SEO';
	}
	if ( defined( 'AIOSEO_VERSION' ) || defined( 'AIOSEO_PHP_VERSION_DIR' ) ) {
		return 'All in One SEO';
	}
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		return 'SEOPress';
	}
	if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
		return 'The SEO Framework';
	}
	return '';
}

/**
 * Should SEMRS SEO print front-end output?
 *
 * @return bool
 */
function semrs_seo_active() {
	return '' === semrs_seo_conflict();
}

/**
 * Defaults.
 *
 * @return array
 */
function semrs_seo_defaults() {
	return array(
		'sep'              => '|',
		'home_title'       => '',
		'home_description' => '',
		'tpl_singular'     => '%title% %sep% %site%',
		'tpl_term'         => '%term% %sep% %site%',
		'tpl_author'       => '%author% %sep% %site%',
		'tpl_search'       => 'Search results for "%search%" %sep% %site%',
		'tpl_404'          => 'Page not found %sep% %site%',
		'tpl_date'         => '%date% %sep% %site%',
		'noindex_tags'     => true,
		'noindex_authors'  => false,
		'noindex_dates'    => true,
		'attachment_redirect' => true,
		'org_type'         => 'Organization',
		'org_name'         => get_bloginfo( 'name' ),
		'org_logo'         => '',
		'org_email'        => '',
		'org_phone'        => '',
		'org_street'       => '',
		'org_city'         => '',
		'org_region'       => '',
		'org_postal'       => '',
		'org_country'      => '',
		'org_area'         => '',
		'org_same_as'      => '',
		'og_image'         => '',
		'twitter_site'     => '',
		'verify_google'    => '',
		'verify_bing'      => '',
		'sitemap'          => true,
		'robots_extra'     => '',
		'breadcrumb_home'  => 'Home',
		'breadcrumbs'      => true,
		'llms'             => true,
	);
}

/**
 * Settings merged with defaults.
 *
 * @return array
 */
function semrs_seo_settings() {
	static $s = null;
	if ( null === $s ) {
		$saved = get_option( 'semrs_seo', array() );
		$s     = array_merge( semrs_seo_defaults(), is_array( $saved ) ? $saved : array() );
	}
	return $s;
}

/**
 * Are the SEMRS SEO Pro items unlocked? Yes once SEMRS Pro is installed with a valid license.
 *
 * @return bool
 */
function semrs_seo_pro() {
	$ok = defined( 'SEMRS_PRO_VERSION' ) && function_exists( 'semrs_pro_license_status' ) && 'valid' === semrs_pro_license_status();
	return (bool) apply_filters( 'semrs_seo_pro_unlocked', $ok );
}

/**
 * Per-post fields that need SEMRS Pro: extra focus keywords, advanced robots and separate X details.
 *
 * @return string[]
 */
function semrs_seo_pro_fields() {
	return array( 'focus_more', 'max_snippet', 'max_video', 'max_image', 'tw_custom', 'tw_card', 'tw_title', 'tw_description', 'tw_image' );
}

/**
 * Per-post SEO value with Rank Math, Yoast and AIOSEO fallbacks.
 *
 * @param int    $post_id Post ID.
 * @param string $field   title|description|focus|canonical|og_image|noindex|nofollow|schema.
 * @return mixed
 */
function semrs_seo_meta( $post_id, $field ) {
	if ( in_array( $field, semrs_seo_pro_fields(), true ) && ! semrs_seo_pro() ) {
		return ''; // Pro field: ignored until SEMRS Pro is licensed.
	}
	$own = get_post_meta( $post_id, '_semrs_' . $field, true );
	if ( '' !== $own && null !== $own && false !== $own ) {
		// Our own titles and descriptions may use %title%, %sep%, %sitename%, %excerpt%, %focuskw%…
		return in_array( $field, array( 'title', 'description', 'og_title', 'og_description', 'tw_title', 'tw_description', 'breadcrumb_title' ), true ) ? semrs_seo_rm_vars( (string) $own, $post_id ) : $own;
	}
	$rm = semrs_seo_rank_math_meta( $post_id, $field );
	if ( '' !== $rm && false !== $rm ) {
		return $rm;
	}
	return semrs_seo_other_meta( $post_id, $field );
}

/**
 * Yoast SEO and All in One SEO (post meta) values, so switching from
 * either plugin keeps titles, descriptions, keywords and robots.
 *
 * @param int    $post_id Post ID.
 * @param string $field   Field.
 * @return mixed
 */
function semrs_seo_other_meta( $post_id, $field ) {
	$keys = array(
		'title'       => array( '_yoast_wpseo_title', '_aioseo_title' ),
		'description' => array( '_yoast_wpseo_metadesc', '_aioseo_description' ),
		'focus'       => array( '_yoast_wpseo_focuskw', '_aioseo_keywords' ),
		'canonical'   => array( '_yoast_wpseo_canonical' ),
		'og_image'    => array( '_yoast_wpseo_opengraph-image' ),
		'noindex'     => array( '_yoast_wpseo_meta-robots-noindex' ),
		'nofollow'    => array( '_yoast_wpseo_meta-robots-nofollow' ),
	);
	foreach ( $keys[ $field ] ?? array() as $key ) {
		$v = (string) get_post_meta( $post_id, $key, true );
		if ( '' === $v ) {
			continue;
		}
		if ( 'noindex' === $field || 'nofollow' === $field ) {
			return '1' === $v; // Yoast: 1 = yes, 2 = no, empty = default.
		}
		if ( 'focus' === $field ) {
			return trim( strtok( $v, ',' ) ?: '' );
		}
		if ( 'title' === $field || 'description' === $field ) {
			// Yoast (%%title%%) and AIOSEO (#post_title) variables to Rank Math style.
			$v = preg_replace( '/%%([a-z_]+)%%/i', '%$1%', $v );
			$v = strtr( $v, array( '#post_title' => '%title%', '#site_title' => '%sitename%', '#separator_sa' => '%sep%', '#tagline' => '%sitedesc%', '#post_excerpt' => '%excerpt%' ) );
			return semrs_seo_rm_vars( $v, $post_id );
		}
		return $v;
	}
	return '';
}

/**
 * Rank Math value for a field.
 *
 * @param int    $post_id Post ID.
 * @param string $field   Field.
 * @return mixed
 */
function semrs_seo_rank_math_meta( $post_id, $field ) {
	switch ( $field ) {
		case 'title':
			return semrs_seo_rm_vars( (string) get_post_meta( $post_id, 'rank_math_title', true ), $post_id );
		case 'description':
			return semrs_seo_rm_vars( (string) get_post_meta( $post_id, 'rank_math_description', true ), $post_id );
		case 'focus':
			$kw = (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true );
			return trim( strtok( $kw, ',' ) ?: '' );
		case 'focus_more':
			$kw = array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) ) );
			return implode( ', ', array_filter( array_slice( $kw, 1 ) ) );
		case 'canonical':
			return (string) get_post_meta( $post_id, 'rank_math_canonical_url', true );
		case 'og_image':
			return (string) get_post_meta( $post_id, 'rank_math_facebook_image', true );
		case 'og_title':
		case 'og_description':
		case 'tw_title':
		case 'tw_description':
			$rm_key = array( 'og_title' => 'rank_math_facebook_title', 'og_description' => 'rank_math_facebook_description', 'tw_title' => 'rank_math_twitter_title', 'tw_description' => 'rank_math_twitter_description' );
			return semrs_seo_rm_vars( (string) get_post_meta( $post_id, $rm_key[ $field ], true ), $post_id );
		case 'tw_image':
			return (string) get_post_meta( $post_id, 'rank_math_twitter_image', true );
		case 'tw_card':
			return (string) get_post_meta( $post_id, 'rank_math_twitter_card_type', true );
		case 'tw_custom':
			return 'off' === get_post_meta( $post_id, 'rank_math_twitter_use_facebook', true ) ? '1' : '';
		case 'breadcrumb_title':
			return (string) get_post_meta( $post_id, 'rank_math_breadcrumb_title', true );
		case 'pillar':
			return 'on' === get_post_meta( $post_id, 'rank_math_pillar_content', true ) ? '1' : '';
		case 'noindex':
		case 'nofollow':
		case 'noarchive':
		case 'nosnippet':
		case 'noimageindex':
			$robots = get_post_meta( $post_id, 'rank_math_robots', true );
			return is_array( $robots ) && in_array( $field, $robots, true );
		case 'max_snippet':
		case 'max_video':
		case 'max_image':
			$adv = get_post_meta( $post_id, 'rank_math_advanced_robots', true );
			$key = array( 'max_snippet' => 'max-snippet', 'max_video' => 'max-video-preview', 'max_image' => 'max-image-preview' )[ $field ];
			return is_array( $adv ) && isset( $adv[ $key ] ) ? (string) $adv[ $key ] : '';
	}
	return '';
}

/**
 * Is a post noindex (SEMRS or Rank Math)?
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function semrs_seo_is_noindex( $post_id ) {
	return (bool) semrs_seo_meta( $post_id, 'noindex' );
}

/**
 * Replace Rank Math variables in imported titles/descriptions.
 *
 * @param string $text    Text with %vars%.
 * @param int    $post_id Post ID.
 * @return string
 */
function semrs_seo_rm_vars( $text, $post_id = 0 ) {
	if ( '' === $text || false === strpos( $text, '%' ) ) {
		return $text;
	}
	$cats = $post_id ? get_the_category( $post_id ) : array();
	$rm   = (array) get_option( 'rank-math-options-titles', array() );
	$map  = array(
		'%title%'            => $post_id ? get_the_title( $post_id ) : '',
		'%seo_title%'        => $post_id ? get_the_title( $post_id ) : '',
		'%sitename%'         => get_bloginfo( 'name' ),
		'%sitedesc%'         => get_bloginfo( 'description' ),
		'%sep%'              => ! empty( $rm['title_separator'] ) ? $rm['title_separator'] : semrs_seo_settings()['sep'], // Keep imported titles exactly as Rank Math showed them.
		'%excerpt%'          => $post_id ? wp_strip_all_tags( get_the_excerpt( $post_id ) ) : '',
		'%category%'         => $cats ? $cats[0]->name : '',
		'%primary_category%' => $cats ? $cats[0]->name : '',
		'%currentyear%'      => gmdate( 'Y' ),
		'%focuskw%'          => $post_id ? (string) semrs_seo_meta( $post_id, 'focus' ) : '',
		'%page%'             => '',
	);
	$text = strtr( $text, $map );
	$text = preg_replace( '/%[a-z_()]+%/i', '', $text ); // Unknown variables.
	return trim( preg_replace( '/\s{2,}/', ' ', $text ) );
}

/**
 * Fill SEMRS title template variables.
 *
 * @param string $tpl  Template.
 * @param array  $vars Extra variables (without % signs).
 * @return string
 */
function semrs_seo_fill( $tpl, $vars = array() ) {
	$s    = semrs_seo_settings();
	$base = array(
		'%site%'    => get_bloginfo( 'name' ),
		'%tagline%' => get_bloginfo( 'description' ),
		'%sep%'     => $s['sep'],
	);
	foreach ( $vars as $k => $v ) {
		$base[ '%' . $k . '%' ] = $v;
	}
	$out = trim( preg_replace( '/\s{2,}/', ' ', strtr( $tpl, $base ) ) );
	return trim( $out, ' ' . $s['sep'] );
}

require __DIR__ . '/breadcrumbs.php';
require __DIR__ . '/meta.php';
require __DIR__ . '/terms.php';
require __DIR__ . '/schema.php';
require __DIR__ . '/sitemap.php';
require __DIR__ . '/analysis.php';
require __DIR__ . '/llms.php';
if ( is_admin() ) {
	require __DIR__ . '/admin.php';
	require __DIR__ . '/audit.php';
	require __DIR__ . '/import.php';
}
