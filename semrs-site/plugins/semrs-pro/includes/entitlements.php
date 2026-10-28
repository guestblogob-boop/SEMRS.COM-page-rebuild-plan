<?php
/**
 * Add-ons: which Pro modules this site's license unlocks.
 *
 * One SEMRS Pro plugin for everyone. The license key lists the add-ons the
 * customer bought (SEO Pro, Speed Pro, Ads Pro, Theme & Blocks Pro, or all),
 * and only those modules load. Add-ons bought later switch on at the next
 * daily check (or with "Check again" on the License screen). Paid add-ons
 * keep working after the license expires; refunds and revoked keys remove them.
 *
 * Sites that ran SEMRS Pro before add-ons existed keep every module.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pro modules in each add-on.
 *
 * @return array<string, string[]>
 */
function semrs_pro_addon_modules() {
	return array(
		'seo'   => array( 'local', 'image_seo', 'seo_bulk', 'schema_pro', 'ai_seo', 'links_pro', 'sitemaps_pro', 'rank_tracker' ),
		'speed' => array( 'speed_pro', 'image_library' ),
		'ads'   => array( 'ads_pro' ),
		'theme' => array( 'theme_pro', 'layouts', 'proof', 'leads', 'blocks_pro', 'conditions', 'popups', 'languages', 'templates_pro' ),
	);
}

/**
 * Add-on names (call after init, for translations).
 *
 * @return array<string, string>
 */
function semrs_pro_addon_labels() {
	return array(
		'seo'   => __( 'SEO Pro', 'semrs-pro' ),
		'speed' => __( 'Speed Pro', 'semrs-pro' ),
		'ads'   => __( 'Ads Pro', 'semrs-pro' ),
		'theme' => __( 'Theme & Blocks Pro', 'semrs-pro' ),
	);
}

/**
 * Add-ons unlocked on this site.
 *
 * @return string[] e.g. array( 'seo', 'ads' ).
 */
function semrs_pro_addons() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$saved = get_option( 'semrs_pro_addons', null );
	if ( null === $saved ) {
		// First run of this version. A fresh install starts locked until a key is
		// activated; a site that already ran SEMRS Pro, or has a key, keeps everything.
		$legacy = ! get_option( 'semrs_pro_fresh' ) || '' !== (string) get_option( 'semrs_pro_license_key', '' ) || function_exists( 'semrs_us_products' );
		$saved  = $legacy ? array( 'all' ) : array();
		update_option( 'semrs_pro_addons', $saved, false );
		delete_option( 'semrs_pro_fresh' );
	}
	$saved = is_array( $saved ) ? $saved : array();
	$known = array_keys( semrs_pro_addon_modules() );
	$list  = in_array( 'all', $saved, true ) ? $known : array_values( array_intersect( $known, $saved ) );
	$cache = (array) apply_filters( 'semrs_pro_addons', $list );
	return $cache;
}

/**
 * Is an add-on unlocked?
 *
 * @param string $addon seo|speed|ads|theme.
 * @return bool
 */
function semrs_pro_has( $addon ) {
	return in_array( $addon, semrs_pro_addons(), true );
}

/**
 * Add-on a module belongs to ('' when none).
 *
 * @param string $module Module key.
 * @return string
 */
function semrs_pro_module_addon( $module ) {
	foreach ( semrs_pro_addon_modules() as $addon => $mods ) {
		if ( in_array( $module, $mods, true ) ) {
			return $addon;
		}
	}
	return '';
}

/**
 * License server base URL (same as the updater).
 *
 * @return string
 */
function semrs_pro_license_server() {
	if ( class_exists( 'SEMRS_Updater_1' ) ) {
		return SEMRS_Updater_1::server();
	}
	return trailingslashit( apply_filters( 'semrs_update_server', defined( 'SEMRS_UPDATE_SERVER' ) ? SEMRS_UPDATE_SERVER : 'https://semrs.com/wp-json/semrs-updates/v1/' ) );
}

/**
 * Call the license server.
 *
 * @param string $action activate|deactivate|license.
 * @param string $key    License key.
 * @return array{ok:bool,data:array,error:string}
 */
function semrs_pro_license_call( $action, $key ) {
	$res = wp_remote_post(
		semrs_pro_license_server() . $action,
		array(
			'timeout' => 15,
			'body'    => array(
				'key'     => $key,
				'site'    => home_url( '/' ),
				'version' => defined( 'SEMRS_PRO_VERSION' ) ? SEMRS_PRO_VERSION : '',
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return array( 'ok' => false, 'data' => array(), 'error' => $res->get_error_message() );
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) || ! is_array( $data ) ) {
		return array( 'ok' => false, 'data' => array(), 'error' => __( 'The license server did not answer correctly. Try again in a few minutes.', 'semrs-pro' ) );
	}
	return array( 'ok' => true, 'data' => $data, 'error' => '' );
}

/**
 * Save what the license server said.
 *
 * @param array $data       Response (license, expires, addons, manage).
 * @param bool  $activating True for the Activate button: add-ons unlock only for a valid key.
 */
function semrs_pro_license_apply( $data, $activating = false ) {
	$status = sanitize_key( (string) ( $data['license'] ?? '' ) );
	if ( ! in_array( $status, array( 'valid', 'expired', 'invalid', 'limit' ), true ) ) {
		return;
	}
	update_option( 'semrs_pro_license_remote', $status, false );
	update_option( 'semrs_pro_license_checked', time(), false );
	if ( isset( $data['expires'] ) ) {
		update_option( 'semrs_pro_license_expires', sanitize_text_field( (string) $data['expires'] ), false );
	}
	if ( ! empty( $data['manage'] ) ) {
		update_option( 'semrs_pro_license_manage', esc_url_raw( (string) $data['manage'] ), false );
	}
	if ( isset( $data['addons'] ) && is_array( $data['addons'] ) && ( 'valid' === $status || ( ! $activating && 'expired' === $status ) ) ) {
		update_option( 'semrs_pro_addons', array_map( 'sanitize_key', $data['addons'] ), false );
	} elseif ( 'invalid' === $status && ! $activating ) {
		update_option( 'semrs_pro_addons', array(), false ); // Refunded or revoked.
	}
}

/**
 * Ask the server for this site's add-ons (daily, and from "Check again").
 *
 * @return array Call result.
 */
function semrs_pro_license_refresh() {
	$key = (string) get_option( 'semrs_pro_license_key', '' );
	if ( '' === $key ) {
		return array( 'ok' => false, 'data' => array(), 'error' => '' );
	}
	$call = semrs_pro_license_call( 'license', $key );
	if ( $call['ok'] ) {
		semrs_pro_license_apply( $call['data'] );
	}
	return $call;
}
add_action( 'semrs_pro_license_daily', 'semrs_pro_license_refresh' );

/**
 * Schedule the daily check while a key is saved.
 */
function semrs_pro_license_schedule() {
	$has = '' !== (string) get_option( 'semrs_pro_license_key', '' );
	$next = wp_next_scheduled( 'semrs_pro_license_daily' );
	if ( $has && ! $next ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'semrs_pro_license_daily' );
	} elseif ( ! $has && $next ) {
		wp_clear_scheduled_hook( 'semrs_pro_license_daily' );
	}
}
add_action( 'init', 'semrs_pro_license_schedule' );

/**
 * Where to get an add-on: the customer's licenses page when a key is saved,
 * otherwise the plans.
 *
 * @return string
 */
function semrs_pro_buy_url() {
	$manage = (string) get_option( 'semrs_pro_license_manage', '' );
	if ( '' !== (string) get_option( 'semrs_pro_license_key', '' ) ) {
		return $manage ? $manage : 'https://semrs.com/my-licenses/';
	}
	return 'https://semrs.com/semrs-pro/#plans';
}

// SEMRS SEO's Pro items follow the SEO Pro add-on.
add_filter(
	'semrs_seo_pro_unlocked',
	function () {
		return semrs_pro_has( 'seo' );
	}
);

// Features screen (SEMRS Core 1.3.21+): lock modules the license does not include.
add_filter(
	'semrs_core_module_locked',
	function ( $locked, $key ) {
		$addon = semrs_pro_module_addon( $key );
		return $addon ? ! semrs_pro_has( $addon ) : $locked;
	},
	10,
	2
);
add_filter(
	'semrs_core_unlock',
	function ( $unlock, $key ) {
		$addon = semrs_pro_module_addon( $key );
		if ( ! $addon ) {
			return $unlock;
		}
		/* translators: %s: add-on name, e.g. Ads Pro */
		return array( semrs_pro_buy_url(), sprintf( __( 'Add %s', 'semrs-pro' ), semrs_pro_addon_labels()[ $addon ] ), sprintf( __( 'Part of %s', 'semrs-pro' ), semrs_pro_addon_labels()[ $addon ] ) );
	},
	10,
	2
);
