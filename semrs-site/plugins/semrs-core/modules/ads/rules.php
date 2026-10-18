<?php
/**
 * Where ads may appear. Every rule here exists to protect AdSense approval:
 * no ads on conversion/legal/noindex/thin pages, none for admins (no self-clicks).
 *
 * @package semrs-ads
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per-page "Disable ads" switch.
 */
function semrs_ads_register_meta() {
	foreach ( (array) semrs_ads_settings()['post_types'] as $type ) {
		register_post_meta(
			$type,
			'_semrs_ads_off',
			array(
				'type'          => 'boolean',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'semrs_ads_register_meta' );

/**
 * Current request path with a trailing slash.
 *
 * @return string
 */
function semrs_ads_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$path = wp_parse_url( $uri, PHP_URL_PATH );
	return trailingslashit( is_string( $path ) ? $path : '/' );
}

/**
 * Why ads are blocked on this request (empty string = allowed).
 * Exposed for the admin bar check and for debugging.
 *
 * @return string
 */
function semrs_ads_block_reason() {
	static $reason = null;
	if ( null !== $reason ) {
		return $reason;
	}
	$s      = semrs_ads_settings();
	$reason = '';

	if ( ! $s['enabled'] ) {
		$reason = 'Ads are switched off in SEMRS → Ads';
	} elseif ( is_admin() || is_feed() || is_robots() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		$reason = 'Not a front-end page';
	} elseif ( is_404() ) {
		$reason = '404 page';
	} elseif ( is_attachment() ) {
		$reason = 'Attachment page';
	} elseif ( $s['exclude_front'] && is_front_page() ) {
		$reason = 'Front page excluded';
	} elseif ( $s['exclude_search'] && is_search() ) {
		$reason = 'Search results excluded';
	} elseif ( is_singular() && post_password_required() ) {
		$reason = 'Password-protected content';
	} else {
		$path = semrs_ads_request_path();
		foreach ( preg_split( '/\R/', (string) $s['exclude_paths'] ) as $prefix ) {
			$prefix = trim( $prefix );
			if ( '' !== $prefix && 0 === strpos( $path, trailingslashit( $prefix ) ) ) {
				$reason = 'Excluded path ' . $prefix;
				break;
			}
		}
	}

	if ( '' === $reason && is_singular() ) {
		$id  = get_queried_object_id();
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $s['exclude_ids'] ) ) );
		if ( in_array( $id, $ids, true ) ) {
			$reason = 'Excluded page ID';
		} elseif ( get_post_meta( $id, '_semrs_ads_off', true ) ) {
			$reason = 'Ads disabled on this page';
		} elseif ( ! in_array( get_post_type( $id ), (array) $s['post_types'], true ) ) {
			$reason = 'Post type not enabled';
		} else {
			$robots  = get_post_meta( $id, 'rank_math_robots', true );
			$noindex = function_exists( 'semrs_seo_is_noindex' ) ? semrs_seo_is_noindex( $id ) : ( is_array( $robots ) && in_array( 'noindex', $robots, true ) );
			if ( $noindex ) {
				$reason = 'Page is noindex';
			}
		}
	}

	// Checked last so the admin-bar status can show the real reason for visitors.
	if ( '' === $reason && $s['hide_for_admins'] && current_user_can( 'manage_options' ) ) {
		$reason = 'admin';
	}

	/**
	 * Filter the block reason. Return '' to allow ads.
	 *
	 * @param string $reason Reason ads are blocked.
	 */
	$reason = (string) apply_filters( 'semrs_ads_block_reason', $reason );
	return $reason;
}

/**
 * Ads allowed on this request?
 *
 * @return bool
 */
function semrs_ads_allowed() {
	return '' === semrs_ads_block_reason();
}

/**
 * Enough original content for ads? (Thin pages risk "low value content".)
 *
 * @param string $html Rendered content.
 * @return bool
 */
function semrs_ads_has_enough_content( $html ) {
	$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $html ) ), -1, PREG_SPLIT_NO_EMPTY );
	return count( $words ) >= (int) semrs_ads_settings()['min_words'];
}

/**
 * Byte offsets right after each top-level </p> that is NOT inside a table,
 * form, list, details, blockquote, figure, pre, a free tool, a CF7 form or
 * another ad. Ads go only at these safe points.
 *
 * @param string $html Rendered content.
 * @return int[]
 */
function semrs_ads_safe_points( $html ) {
	$void      = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' );
	$protected = array( 'table', 'form', 'ul', 'ol', 'details', 'blockquote', 'figure', 'pre', 'select', 'textarea', 'button', 'iframe' );
	$stack     = array();
	$lock      = null;
	$points    = array();

	if ( ! preg_match_all( '/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)([^>]*)>/', $html, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $points;
	}

	foreach ( $tags as $t ) {
		$closing = '/' === $t[1][0];
		$name    = strtolower( $t[2][0] );
		$attrs   = $t[3][0];
		$end     = $t[0][1] + strlen( $t[0][0] );

		if ( in_array( $name, $void, true ) || '/' === substr( rtrim( $attrs ), -1 ) ) {
			continue;
		}

		if ( ! $closing ) {
			$is_protected = in_array( $name, $protected, true )
				|| ( 'div' === $name && preg_match( '/class=["\'][^"\']*\b(semrs-tool|wpcf7|semrs-ad-slot|semrs-cta-band|semrs-service-cta)\b/', $attrs ) );
			if ( null === $lock && $is_protected ) {
				$lock = count( $stack );
			}
			$stack[] = $name;
			continue;
		}

		// Closing tag: pop back to its opener (tolerates sloppy HTML).
		$at = array_search( $name, array_reverse( $stack, true ), true );
		if ( false !== $at ) {
			$stack = array_slice( $stack, 0, $at );
		}
		if ( null !== $lock && count( $stack ) <= $lock ) {
			$lock = null;
			continue;
		}
		// Only paragraphs whose parents are plain layout containers (e.g. Group blocks).
		if ( null === $lock && 'p' === $name && ! array_diff( $stack, array( 'div', 'section', 'article', 'main' ) ) ) {
			$points[] = $end;
		}
	}
	return $points;
}
