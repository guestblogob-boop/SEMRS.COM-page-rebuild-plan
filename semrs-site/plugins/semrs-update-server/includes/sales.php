<?php
/**
 * Sales: Pro add-ons, the plan catalogue and prices, settings, attaching
 * purchases to keys, and the customer emails.
 *
 * One SEMRS Pro plugin, one key per customer. The key lists the add-ons the
 * customer paid for (SEO Pro, Speed Pro, Ads Pro, Theme & Blocks Pro, or All
 * Pro). Buying another add-on later adds it to the same key.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pro add-ons: slug => name.
 *
 * @return array<string, string>
 */
function semrs_us_addons() {
	return array(
		'seo'   => 'SEO Pro',
		'speed' => 'Speed Pro',
		'ads'   => 'Ads Pro',
		'theme' => 'Theme & Blocks Pro',
	);
}

/**
 * Keep known add-on slugs ('all' stays as is).
 *
 * @param array $list Slugs.
 * @return string[]
 */
function semrs_us_clean_addons( $list ) {
	$list = array_map( 'sanitize_key', (array) $list );
	if ( in_array( 'all', $list, true ) ) {
		return array( 'all' );
	}
	$known = array_keys( semrs_us_addons() );
	$list  = array_values( array_unique( array_intersect( $list, $known ) ) );
	return count( $list ) === count( $known ) ? array( 'all' ) : $list;
}

/**
 * Expand 'all' into every add-on slug.
 *
 * @param array $list Slugs.
 * @return string[]
 */
function semrs_us_expand_addons( $list ) {
	$known = array_keys( semrs_us_addons() );
	return in_array( 'all', (array) $list, true ) ? $known : array_values( array_intersect( $known, (array) $list ) );
}

/**
 * Add-on names for a list, e.g. "SEO Pro, Ads Pro" or "All Pro".
 *
 * @param array $list Slugs.
 * @return string
 */
function semrs_us_addon_names( $list ) {
	$list = semrs_us_clean_addons( $list );
	if ( array( 'all' ) === $list ) {
		return 'All Pro (' . implode( ', ', semrs_us_addons() ) . ')';
	}
	return $list ? implode( ', ', array_intersect_key( semrs_us_addons(), array_flip( $list ) ) ) : '—';
}

/**
 * Site tiers: slug => sites (0 = unlimited).
 *
 * @return array<string, int>
 */
function semrs_us_tiers() {
	return array( '1' => 1, '5' => 5, 'u' => 0 );
}

/**
 * Default prices in USD. Yearly plans renew each year; lifetime plans are one payment.
 * Set on 2026-10-28 below the public list prices of the main paid plugins and
 * themes each add-on replaces (see AUDIT_FREE_PRO.md).
 *
 * @return array<string, float>
 */
function semrs_us_default_prices() {
	return array(
		'seo-1'      => 39,
		'seo-5'      => 69,
		'seo-u'      => 129,
		'speed-1'    => 29,
		'speed-5'    => 59,
		'speed-u'    => 119,
		'ads-1'      => 15,
		'ads-5'      => 25,
		'ads-u'      => 49,
		'theme-1'    => 29,
		'theme-5'    => 49,
		'theme-u'    => 99,
		'all-1'      => 69,
		'all-5'      => 119,
		'all-u'      => 199,
		'all-life-1' => 199,
		'all-life-u' => 499,
	);
}

/**
 * Sales settings merged with defaults.
 *
 * @return array
 */
function semrs_us_sales() {
	$s = get_option( 'semrs_us_sales', array() );
	return array_merge(
		array(
			'ls_secret'        => '',
			'paddle_token'     => '',
			'paddle_secret'    => '',
			'paddle_sandbox'   => 0,
			'plans'            => array(),
			'currency'         => 'USD',
			'offer_code'       => '',
			'offer_percent'    => 0,
			'offer_ends'       => '',
			'offer_label'      => '',
			'refund_days'      => 0,
			'seller_name'      => 'SEMRS',
			'seller_address'   => '',
			'seller_email'     => get_option( 'admin_email' ),
			'seller_tax'       => '',
			'pay_instructions' => '',
			'account_page'     => 0,
			'quote_page'       => 0,
		),
		is_array( $s ) ? $s : array()
	);
}

/**
 * The plan catalogue: id => addons, sites, term (year|life), price, variant, checkout.
 *
 * @return array<string, array>
 */
function semrs_us_plans() {
	$saved = semrs_us_sales()['plans'];
	$out   = array();
	foreach ( semrs_us_default_prices() as $id => $price ) {
		$life  = 0 === strpos( $id, 'all-life-' );
		$addon = $life ? 'all' : strtok( $id, '-' );
		$tier  = substr( $id, strrpos( $id, '-' ) + 1 );
		$s     = isset( $saved[ $id ] ) && is_array( $saved[ $id ] ) ? $saved[ $id ] : array();
		$out[ $id ] = array(
			'id'       => $id,
			'addons'   => array( $addon ),
			'addon'    => $addon,
			'tier'     => $tier,
			'sites'    => semrs_us_tiers()[ $tier ],
			'term'     => $life ? 'life' : 'year',
			'price'    => isset( $s['price'] ) && '' !== $s['price'] ? (float) $s['price'] : (float) $price,
			'variant'  => isset( $s['variant'] ) ? (string) $s['variant'] : '',
			'paddle'   => ! empty( $s['paddle'] ) ? (string) $s['paddle'] : ( semrs_us_paddle_default_prices()[ $id ] ?? '' ),
			'checkout' => isset( $s['checkout'] ) ? (string) $s['checkout'] : '',
		);
	}
	return $out;
}

/**
 * Plan name, e.g. "SEO Pro · 5 websites · yearly".
 *
 * @param array $p Plan.
 * @return string
 */
function semrs_us_plan_label( $p ) {
	$name  = 'all' === $p['addon'] ? 'All Pro' : semrs_us_addons()[ $p['addon'] ];
	$sites = $p['sites'] ? sprintf( _n( '%d website', '%d websites', $p['sites'], 'semrs-update-server' ), $p['sites'] ) : __( 'Unlimited websites', 'semrs-update-server' );
	return $name . ' · ' . $sites . ' · ' . ( 'life' === $p['term'] ? __( 'lifetime', 'semrs-update-server' ) : __( 'yearly', 'semrs-update-server' ) );
}

/**
 * Plan for a Lemon Squeezy variant ID, or null.
 *
 * @param string|int $variant Variant ID.
 * @return array|null
 */
function semrs_us_plan_by_variant( $variant ) {
	foreach ( semrs_us_plans() as $p ) {
		if ( '' !== $p['variant'] && (string) $variant === $p['variant'] ) {
			return $p;
		}
	}
	return null;
}

/**
 * Money, e.g. "$39".
 *
 * @param float $amount Amount.
 * @return string
 */
function semrs_us_money( $amount ) {
	$cur = semrs_us_sales()['currency'];
	$num = floor( $amount ) == $amount ? number_format( $amount ) : number_format( $amount, 2 ); // phpcs:ignore Universal.Operators.StrictComparisons
	return 'USD' === $cur ? '$' . $num : $num . ' ' . $cur;
}

/**
 * Is the launch offer running?
 *
 * @return bool
 */
function semrs_us_offer_on() {
	$s = semrs_us_sales();
	return '' !== $s['offer_code'] && ( '' === $s['offer_ends'] || $s['offer_ends'] >= gmdate( 'Y-m-d' ) );
}

/**
 * Where a plan's button goes: its Lemon Squeezy checkout (with the launch
 * code and, for an existing customer, their key ID), or the quote page when
 * no checkout link is set yet (we send an invoice instead).
 *
 * @param array  $p   Plan.
 * @param string $kid Key ID to add the purchase to ('' for a new key).
 * @return string
 */
function semrs_us_buy_url( $p, $kid = '' ) {
	if ( semrs_us_paddle_on() && '' !== $p['paddle'] ) {
		return semrs_us_paddle_checkout_url( $p, $kid );
	}
	if ( '' === $p['checkout'] ) {
		$s    = semrs_us_sales();
		$page = $s['quote_page'] ? get_permalink( (int) $s['quote_page'] ) : '';
		return add_query_arg( 'plan', $p['id'], $page ? $page : home_url( '/get-a-quote/' ) );
	}
	$args = array();
	if ( semrs_us_offer_on() ) {
		$args['checkout[discount_code]'] = semrs_us_sales()['offer_code'];
	}
	if ( $kid ) {
		$args['checkout[custom][kid]'] = $kid;
	}
	return $args ? add_query_arg( array_map( 'rawurlencode', $args ), $p['checkout'] ) : $p['checkout'];
}

/**
 * Short public ID for a key, safe to put in links (the key itself never is).
 *
 * @param string $key Key.
 * @return string
 */
function semrs_us_kid( $key ) {
	return substr( hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ), 0, 16 );
}

/**
 * Key for a key ID, or ''.
 *
 * @param string $kid Key ID.
 * @return string
 */
function semrs_us_key_by_kid( $kid ) {
	$kid = preg_replace( '/[^a-f0-9]/', '', (string) $kid );
	if ( 16 !== strlen( $kid ) ) {
		return '';
	}
	foreach ( array_keys( semrs_us_keys() ) as $key ) {
		if ( hash_equals( semrs_us_kid( $key ), $kid ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * Keys bought with an email address.
 *
 * @param string $email Email.
 * @return array key => data
 */
function semrs_us_keys_for_email( $email ) {
	$email = strtolower( sanitize_email( $email ) );
	return '' === $email ? array() : array_filter(
		semrs_us_keys(),
		function ( $k ) use ( $email ) {
			return strtolower( (string) $k['email'] ) === $email;
		}
	);
}

/**
 * Key that holds a source (e.g. 'ls-sub:123'), or ''.
 *
 * @param string $ref Source reference.
 * @return string
 */
function semrs_us_key_by_source( $ref ) {
	foreach ( semrs_us_keys() as $key => $k ) {
		if ( isset( $k['sources'][ $ref ] ) ) {
			return (string) $key;
		}
	}
	return '';
}

/**
 * Attach a purchase to a key: update the key that already holds this source,
 * else add it to the key named by $kid, else create a new key. Emails the
 * customer when a key is created or gains an add-on.
 *
 * @param string $ref    Source reference, e.g. 'ls-sub:123', 'ls-order:45', 'invoice:SEMRS-2026-0001'.
 * @param array  $src    addons, sites, expires, plus any extra fields (plan, portal, order).
 * @param string $email  Customer email.
 * @param string $name   Customer name.
 * @param string $kid    Key ID to add to ('' to look up or create).
 * @param string $key    Exact key to add to (used by invoices).
 * @return string The key.
 */
function semrs_us_grant( $ref, $src, $email, $name = '', $kid = '', $key = '' ) {
	$src = array_merge( array( 'addons' => array(), 'sites' => 1, 'expires' => '', 'status' => 'active', 'date' => gmdate( 'Y-m-d' ) ), $src );
	$key = $key ? $key : semrs_us_key_by_source( $ref );
	$key = $key ? $key : semrs_us_key_by_kid( $kid );
	$all = semrs_us_keys();
	if ( ! $key || ! isset( $all[ $key ] ) ) {
		$key            = semrs_us_create_key( $email, 0, '', array(), array( 'name' => $name, 'manual' => false ) );
		$k              = semrs_us_keys()[ $key ];
		$k['sources']   = array( $ref => $src );
		$k              = semrs_us_save_key( $key, $k );
		semrs_us_mail_key( $key, $k, 'new' );
		return $key;
	}
	$k      = $all[ $key ];
	$before = semrs_us_key_addons( $k );
	$k['sources'][ $ref ] = array_merge( $k['sources'][ $ref ] ?? array(), $src );
	$k = semrs_us_save_key( $key, $k );
	if ( array_diff( semrs_us_key_addons( $k ), $before ) ) {
		semrs_us_mail_key( $key, $k, 'added' );
	}
	return $key;
}

/**
 * Mark a source refunded (its add-ons leave the key).
 *
 * @param string $ref Source reference.
 * @return bool
 */
function semrs_us_refund( $ref ) {
	$key = semrs_us_key_by_source( $ref );
	if ( ! $key ) {
		return false;
	}
	$k                              = semrs_us_keys()[ $key ];
	$k['sources'][ $ref ]['status'] = 'refunded';
	semrs_us_save_key( $key, $k );
	return true;
}

/**
 * My Licenses page URL.
 *
 * @return string
 */
function semrs_us_account_url() {
	$id = (int) semrs_us_sales()['account_page'];
	return $id && get_permalink( $id ) ? get_permalink( $id ) : home_url( '/my-licenses/' );
}

/**
 * Signed download link for the latest SEMRS Pro release ('' when none).
 *
 * @param string $key   License key.
 * @param int    $hours Link lifetime.
 * @return string
 */
function semrs_us_pro_download_url( $key, $hours = 1 ) {
	$rel = semrs_us_latest( 'semrs-pro' );
	if ( ! $rel ) {
		return '';
	}
	$expires = time() + $hours * HOUR_IN_SECONDS;
	return add_query_arg(
		array(
			'slug'    => 'semrs-pro',
			'version' => $rel['version'],
			'key'     => $key,
			'host'    => 'customer',
			'expires' => $expires,
			'token'   => semrs_us_token( 'semrs-pro', $rel['version'], $key, 'customer', $expires ),
		),
		rest_url( 'semrs-updates/v1/download' )
	);
}

/**
 * Send a plain-text email from the seller address.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $body    Body.
 * @return bool
 */
function semrs_us_mail( $to, $subject, $body ) {
	$s       = semrs_us_sales();
	$from    = sanitize_email( $s['seller_email'] );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( $from ) {
		$headers[] = 'Reply-To: ' . $s['seller_name'] . ' <' . $from . '>';
	}
	return wp_mail( $to, $subject, $body, $headers );
}

/**
 * Email the customer their key ('new') or the add-ons it just gained ('added').
 *
 * @param string $key  Key.
 * @param array  $k    Key data.
 * @param string $kind new|added.
 * @return bool
 */
function semrs_us_mail_key( $key, $k, $kind ) {
	$name    = $k['name'] ? $k['name'] : __( 'there', 'semrs-update-server' );
	$sites   = $k['sites'] ? sprintf( _n( '%d website', '%d websites', $k['sites'], 'semrs-update-server' ), $k['sites'] ) : __( 'Unlimited websites', 'semrs-update-server' );
	$until   = '' === $k['expires'] ? __( 'lifetime', 'semrs-update-server' ) : $k['expires'];
	$lines   = array(
		/* translators: %s: customer name */
		sprintf( __( 'Hi %s,', 'semrs-update-server' ), $name ),
		'',
		'new' === $kind ? __( 'Thank you for buying SEMRS Pro. Here is your license key.', 'semrs-update-server' ) : __( 'Your SEMRS Pro key has new add-ons. They switch on by themselves within 24 hours, or right away under SEMRS → License → Check again. Nothing to reinstall.', 'semrs-update-server' ),
		'',
		__( 'License key:', 'semrs-update-server' ) . ' ' . $key,
		__( 'Add-ons:', 'semrs-update-server' ) . ' ' . semrs_us_addon_names( $k['addons'] ),
		__( 'Websites:', 'semrs-update-server' ) . ' ' . $sites . ' ' . __( '(local and staging sites are free)', 'semrs-update-server' ),
		__( 'Updates and support until:', 'semrs-update-server' ) . ' ' . $until,
		'',
	);
	if ( 'new' === $kind ) {
		$dl    = semrs_us_pro_download_url( $key, 24 * 7 );
		$lines = array_merge(
			$lines,
			array(
				__( 'Get started in 3 steps:', 'semrs-update-server' ),
				'1. ' . __( 'Install the free SEMRS Core plugin (Plugins → Add New → search "SEMRS"), or download it from semrs.com.', 'semrs-update-server' ),
				'2. ' . __( 'Download SEMRS Pro and upload it under Plugins → Add New → Upload Plugin:', 'semrs-update-server' ) . ( $dl ? "\n   " . $dl . "\n   " . __( '(link works for 7 days; you can always download again from My Licenses)', 'semrs-update-server' ) : ' ' . semrs_us_account_url() ),
				'3. ' . __( 'Go to SEMRS → License, paste your key and click Activate.', 'semrs-update-server' ),
				'',
			)
		);
	}
	$lines[] = __( 'Manage your sites, download SEMRS Pro, add add-ons or renew:', 'semrs-update-server' ) . ' ' . semrs_us_account_url();
	$lines[] = '';
	$lines[] = __( 'Features you paid for keep working even if you do not renew; renewing keeps updates and support coming.', 'semrs-update-server' );
	$lines[] = '';
	$lines[] = semrs_us_sales()['seller_name'];
	$subject = 'new' === $kind ? __( 'Your SEMRS Pro license key', 'semrs-update-server' ) : __( 'New add-ons on your SEMRS Pro key', 'semrs-update-server' );
	return semrs_us_mail( $k['email'], $subject, implode( "\n", $lines ) );
}

/**
 * Daily: remind customers 14 days before a key's updates end, unless a
 * Lemon Squeezy subscription renews it by itself.
 */
function semrs_us_renewal_reminders() {
	$soon = gmdate( 'Y-m-d', strtotime( '+14 days' ) );
	$all  = semrs_us_keys();
	$sent = false;
	foreach ( $all as $key => $k ) {
		if ( 'active' !== ( $k['status'] ?? '' ) || '' === ( $k['expires'] ?? '' ) || $k['expires'] > $soon || $k['expires'] < gmdate( 'Y-m-d' ) || ( $k['reminded'] ?? '' ) === $k['expires'] ) {
			continue;
		}
		foreach ( (array) ( $k['sources'] ?? array() ) as $ref => $src ) {
			if ( 0 === strpos( $ref, 'ls-sub:' ) && 'refunded' !== ( $src['status'] ?? '' ) && (string) $src['expires'] === $k['expires'] ) {
				continue 2; // Renews automatically.
			}
		}
		semrs_us_mail(
			$k['email'],
			__( 'Your SEMRS Pro updates end soon', 'semrs-update-server' ),
			implode(
				"\n",
				array(
					/* translators: %s: date */
					sprintf( __( 'Updates and support for your SEMRS Pro key end on %s.', 'semrs-update-server' ), $k['expires'] ),
					__( 'Add-ons:', 'semrs-update-server' ) . ' ' . semrs_us_addon_names( $k['addons'] ),
					'',
					__( 'Your Pro features keep working either way. To keep updates coming, renew on your licenses page (or ask for an invoice there):', 'semrs-update-server' ),
					semrs_us_account_url(),
					'',
					semrs_us_sales()['seller_name'],
				)
			)
		);
		$all[ $key ]['reminded'] = $k['expires'];
		$sent                    = true;
	}
	if ( $sent ) {
		update_option( 'semrs_us_keys', $all, false );
	}
}
add_action( 'semrs_us_daily', 'semrs_us_renewal_reminders' );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'semrs_us_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'semrs_us_daily' );
		}
	}
);

/**
 * Quote page opened from a Buy button without a checkout link (?plan=…):
 * show the chosen plan above the form, so the request says what to invoice.
 *
 * @param string $content Content.
 * @return string
 */
function semrs_us_quote_plan_note( $content ) {
	$id = (int) semrs_us_sales()['quote_page'];
	if ( empty( $_GET['plan'] ) || ! in_the_loop() || ! is_main_query() || ( $id ? ! is_page( $id ) : ! is_page( 'get-a-quote' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return $content;
	}
	$plan  = sanitize_key( wp_unslash( $_GET['plan'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$plans = semrs_us_plans();
	$what  = isset( $plans[ $plan ] ) ? 'SEMRS Pro: ' . semrs_us_plan_label( $plans[ $plan ] ) . ' (' . semrs_us_money( $plans[ $plan ]['price'] ) . ')' : __( 'SEMRS Pro by invoice', 'semrs-update-server' );
	$note  = '<p class="semrs-quote-plan" role="note" style="border:2px solid var(--sp-accent,#d4a72c);border-radius:12px;padding:12px 16px"><strong>' . esc_html( $what ) . '</strong><br>' . esc_html__( 'Send the form with your name and email and we will email you an invoice (bank transfer, PayPal or card). Your license key arrives as soon as it is paid.', 'semrs-update-server' ) . '</p>';
	return $note . $content;
}
add_filter( 'the_content', 'semrs_us_quote_plan_note', 5 );
