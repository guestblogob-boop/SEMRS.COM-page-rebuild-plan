<?php
/**
 * WP Consent API functions, loaded only when the WP Consent API plugin is
 * not active. Same cookie names (wp_consent_{category} = allow|deny) and
 * the same function names, so plugins that support the API read SEMRS
 * choices without the separate plugin.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_get_consent_type' ) ) {
	/**
	 * Consent type: optin (nothing before consent) or optout.
	 *
	 * @return string
	 */
	function wp_get_consent_type() {
		return (string) apply_filters( 'wp_get_consent_type', semrs_privacy_settings()['type'] );
	}
}

if ( ! function_exists( 'wp_validate_consent_category' ) ) {
	/**
	 * Known categories (statistics-anonymous counts as statistics).
	 *
	 * @param string $category Category.
	 * @return string
	 */
	function wp_validate_consent_category( $category ) {
		$category = 'statistics-anonymous' === $category ? 'statistics' : $category;
		return in_array( $category, array( 'functional', 'preferences', 'statistics', 'marketing' ), true ) ? $category : 'marketing';
	}
}

if ( ! function_exists( 'wp_has_consent' ) ) {
	/**
	 * Has the visitor agreed to a category?
	 *
	 * @param string $category Category.
	 * @return bool
	 */
	function wp_has_consent( $category ) {
		$category = wp_validate_consent_category( $category );
		if ( 'functional' === $category ) {
			return true;
		}
		$cookie = isset( $_COOKIE[ 'wp_consent_' . $category ] ) ? sanitize_key( wp_unslash( $_COOKIE[ 'wp_consent_' . $category ] ) ) : '';
		if ( 'allow' === $cookie ) {
			$ok = true;
		} elseif ( 'deny' === $cookie ) {
			$ok = false;
		} else {
			$ok = 'optout' === wp_get_consent_type();
		}
		return (bool) apply_filters( 'wp_has_consent', $ok, $category, $cookie );
	}
}

if ( ! function_exists( 'wp_set_consent' ) ) {
	/**
	 * Set a choice from PHP (rarely needed; the banner sets it in the browser).
	 *
	 * @param string $category Category.
	 * @param string $value    allow|deny.
	 */
	function wp_set_consent( $category, $value ) {
		if ( headers_sent() ) {
			return;
		}
		$value = 'allow' === $value ? 'allow' : 'deny';
		setcookie( 'wp_consent_' . wp_validate_consent_category( $category ), $value, time() + 182 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	}
}

if ( ! function_exists( 'consent_api_registered' ) ) {
	/**
	 * Has a plugin declared that it follows the API?
	 *
	 * @param string $plugin Plugin basename.
	 * @return bool
	 */
	function consent_api_registered( $plugin ) {
		return (bool) apply_filters( "wp_consent_api_registered_{$plugin}", false );
	}
}
