<?php
/**
 * Paddle Billing: checkout page and webhook.
 *
 * Checkout: /?semrs_checkout=<plan id> opens Paddle's overlay checkout for that plan's price
 * (Paddle.js with the client-side token). The same address is the "default payment link" Paddle
 * needs, so payment links in Paddle emails (?_ptxn=…) open there too.
 *
 * Webhook: POST /wp-json/semrs-updates/v1/paddle, signed with the notification secret.
 * - customer.created / customer.updated: remember the customer's email (transactions only carry the ID).
 * - transaction.completed: the plan of each item (price custom data "plan", or the price ID map)
 *   goes on the key. Yearly plans run to the end of the billing period plus 7 days; every renewal
 *   is another completed transaction that extends it. Lifetime prices have no end date.
 * - subscription.updated / subscription.canceled: a cancelled subscription ends on its last paid day.
 * - adjustment.created / adjustment.updated: an approved refund takes that order's add-ons off the key.
 *
 * Custom data "kid" on the checkout adds the purchase to that customer's existing key.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Live price IDs created in the Paddle catalog (Catalog → Products), by plan.
 * A price ID saved under Sales & prices replaces the one here.
 *
 * @return array plan id => price id.
 */
function semrs_us_paddle_default_prices() {
	return array(
		'seo-1'      => 'pri_01m6481sxrdqb4eyz4fj0tj0n0',
		'seo-5'      => 'pri_01m6482x6dgehb7bw3jvmphh4x',
		'seo-u'      => 'pri_01m6483bh1qcayy3mc8aj3gdmc',
		'speed-1'    => 'pri_01m6485ppbxfy207pjjwc76153',
		'speed-5'    => 'pri_01m64861gnd4q0m81d0y4v71sc',
		'speed-u'    => 'pri_01m6486bj3dt1frfhh2gchk52w',
		'ads-1'      => 'pri_01m6488a16565z7ntkfyygrdk5',
		'ads-5'      => 'pri_01m6488jg95ycmtv6x6eawmxa3',
		'ads-u'      => 'pri_01m6488w1s1cwv8y30j9nss94e',
		'theme-1'    => 'pri_01m648axrhwsjhhb0dkw07tgyz',
		'theme-5'    => 'pri_01m648b590sn3dwfrq7cw3xmjv',
		'theme-u'    => 'pri_01m648be19zpzvpy1zw00m5pp2',
		'all-1'      => 'pri_01m648dhdb5p5md8t5cbzgh3yh',
		'all-5'      => 'pri_01m648dt0qvy7m2c2j5rqy80k6',
		'all-u'      => 'pri_01m648e2q7x8b0zw21khg12c9y',
		'all-life-1' => 'pri_01m648f410m7c3mr5btb1rkps5',
		'all-life-u' => 'pri_01m648ftp628d54yev23hp1bc1',
	);
}

/**
 * Is Paddle checkout switched on (client-side token saved)?
 *
 * @return bool
 */
function semrs_us_paddle_on() {
	return '' !== (string) semrs_us_sales()['paddle_token'];
}

/**
 * Plan for a Paddle price (custom data first, then the price ID), or null.
 *
 * @param array $price Price object from the webhook.
 * @return array|null
 */
function semrs_us_plan_by_paddle_price( $price ) {
	$plans = semrs_us_plans();
	$id    = (string) ( $price['custom_data']['plan'] ?? '' );
	if ( isset( $plans[ $id ] ) ) {
		return $plans[ $id ];
	}
	foreach ( $plans as $p ) {
		if ( '' !== $p['paddle'] && $p['paddle'] === (string) ( $price['id'] ?? '' ) ) {
			return $p;
		}
	}
	return null;
}

/**
 * Checkout address for a plan.
 *
 * @param array  $p   Plan.
 * @param string $kid Existing key ID (optional).
 * @return string
 */
function semrs_us_paddle_checkout_url( $p, $kid = '' ) {
	return add_query_arg( array_filter( array( 'semrs_checkout' => $p['id'], 'kid' => $kid ) ), home_url( '/' ) );
}

/**
 * The checkout page: a small page that opens Paddle's overlay.
 */
function semrs_us_paddle_checkout_page() {
	if ( ! isset( $_GET['semrs_checkout'] ) && ! isset( $_GET['_ptxn'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$s = semrs_us_sales();
	if ( '' === $s['paddle_token'] ) {
		if ( isset( $_GET['semrs_checkout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$plan = semrs_us_plans()[ sanitize_key( wp_unslash( $_GET['semrs_checkout'] ) ) ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( $plan ? semrs_us_buy_url( $plan ) : home_url( '/' ) );
			exit;
		}
		return;
	}
	$plan  = isset( $_GET['semrs_checkout'] ) ? ( semrs_us_plans()[ sanitize_key( wp_unslash( $_GET['semrs_checkout'] ) ) ] ?? null ) : null; // phpcs:ignore WordPress.Security.NonceVerification
	$kid   = isset( $_GET['kid'] ) ? preg_replace( '/[^a-f0-9]/', '', (string) wp_unslash( $_GET['kid'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$back  = wp_get_referer() ? wp_get_referer() : home_url( '/semrs-pro/' );
	$open  = $plan && '' !== $plan['paddle'] ? array_filter(
		array(
			'items'        => array( array( 'priceId' => $plan['paddle'], 'quantity' => 1 ) ),
			'customData'   => array_filter( array( 'plan' => $plan['id'], 'kid' => $kid, 'aff' => function_exists( 'semrs_us_aff_cookie' ) ? semrs_us_aff_cookie() : '' ) ),
			'discountCode' => semrs_us_offer_for( $plan['id'], $kid ) ? semrs_us_offer_for( $plan['id'], $kid )['code'] : '',
		)
	) : null;
	$init  = array(
		'token'    => $s['paddle_token'],
		'checkout' => array(
			'settings' => array(
				'displayMode' => 'overlay',
				'theme'       => 'light',
				'locale'      => 'en',
				'successUrl'  => add_query_arg( 'paid', 1, semrs_us_account_url() ),
			),
		),
	);
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?php esc_html_e( 'Checkout', 'semrs-update-server' ); ?> – <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;color:#1f2937;background:#f6f7f9}main{max-width:32rem;padding:2rem 1rem;text-align:center}a{color:#0b1f3a}.muted{color:#5b6575;font-size:.9rem}</style>
</head><body><main>
<h1><?php esc_html_e( 'Opening secure checkout…', 'semrs-update-server' ); ?></h1>
<p class="muted"><?php esc_html_e( 'Our order process is conducted by our online reseller Paddle.com. Paddle.com is the Merchant of Record for all our orders.', 'semrs-update-server' ); ?></p>
<p><a href="<?php echo esc_url( $back ); ?>"><?php esc_html_e( '← Back to plans', 'semrs-update-server' ); ?></a></p>
</main>
<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script>
(function () {
	var init = <?php echo wp_json_encode( $init ); ?>, open = <?php echo wp_json_encode( $open ); ?>, back = <?php echo wp_json_encode( $back ); ?>, done = false;
	<?php if ( ! empty( $s['paddle_sandbox'] ) ) : ?>Paddle.Environment.set('sandbox');<?php endif; ?>
	init.eventCallback = function (e) {
		if (e.name === 'checkout.completed') { done = true; }
		if (e.name === 'checkout.closed' && !done) { location.href = back; }
	};
	Paddle.Initialize(init);
	if (open && !/[?&]_ptxn=/.test(location.search)) { Paddle.Checkout.open(open); }
})();
</script>
</body></html>
	<?php
	exit;
}
add_action( 'template_redirect', 'semrs_us_paddle_checkout_page', 0 );

/**
 * Check the Paddle-Signature header ("ts=…;h1=…").
 *
 * @param string $raw    Raw body.
 * @param string $header Header.
 * @param string $secret Notification secret.
 * @return bool
 */
function semrs_us_paddle_verify( $raw, $header, $secret ) {
	$ts = '';
	$h1 = array();
	foreach ( explode( ';', $header ) as $part ) {
		$kv = explode( '=', trim( $part ), 2 );
		if ( 2 !== count( $kv ) ) {
			continue;
		}
		if ( 'ts' === $kv[0] ) {
			$ts = $kv[1];
		} elseif ( 'h1' === $kv[0] ) {
			$h1[] = $kv[1];
		}
	}
	if ( ! ctype_digit( $ts ) || ! $h1 || abs( time() - (int) $ts ) > 300 ) {
		return false;
	}
	$want = hash_hmac( 'sha256', $ts . ':' . $raw, $secret );
	foreach ( $h1 as $h ) {
		if ( hash_equals( $want, $h ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Handle one webhook call.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_us_paddle_webhook( $req ) {
	$secret = (string) semrs_us_sales()['paddle_secret'];
	$raw    = (string) $req->get_body();
	if ( '' === $secret ) {
		return new WP_Error( 'semrs_paddle_off', 'Webhook secret not set.', array( 'status' => 503 ) );
	}
	if ( ! semrs_us_paddle_verify( $raw, (string) $req->get_header( 'paddle_signature' ), $secret ) ) {
		return new WP_Error( 'semrs_paddle_sig', 'Invalid signature.', array( 'status' => 401 ) );
	}
	$p      = json_decode( $raw, true );
	$event  = (string) ( $p['event_type'] ?? '' );
	$d      = (array) ( $p['data'] ?? array() );
	$test   = ! empty( semrs_us_sales()['paddle_sandbox'] );
	$result = semrs_us_paddle_handle( $event, $d, $test );
	semrs_us_ls_log( 'paddle ' . $event, (string) ( $d['id'] ?? '' ), $result, $test );
	return rest_ensure_response( array( 'ok' => true, 'result' => $result ) );
}

/**
 * Apply an event. Returns a short note for the log.
 *
 * @param string $event Event type.
 * @param array  $d     Event data.
 * @param bool   $test  Sandbox.
 * @return string
 */
function semrs_us_paddle_handle( $event, $d, $test ) {
	$customers = (array) get_option( 'semrs_us_paddle_customers', array() );

	if ( in_array( $event, array( 'customer.created', 'customer.updated' ), true ) ) {
		$cid               = sanitize_text_field( (string) ( $d['id'] ?? '' ) );
		$customers[ $cid ] = array( sanitize_email( (string) ( $d['email'] ?? '' ) ), sanitize_text_field( (string) ( $d['name'] ?? '' ) ) );
		update_option( 'semrs_us_paddle_customers', array_slice( $customers, -2000, null, true ), false );
		// Orders that arrived before this customer's email.
		$pending = (array) get_option( 'semrs_us_paddle_pending', array() );
		$notes   = array();
		foreach ( $pending as $i => $txn ) {
			if ( (string) ( $txn['customer_id'] ?? '' ) === $cid ) {
				unset( $pending[ $i ] );
				update_option( 'semrs_us_paddle_pending', $pending, false );
				$notes[] = semrs_us_paddle_handle( 'transaction.completed', $txn, $test );
			}
		}
		return $notes ? 'customer saved; ' . implode( '; ', $notes ) : 'customer saved';
	}

	if ( 'transaction.completed' === $event ) {
		$cid = (string) ( $d['customer_id'] ?? '' );
		if ( empty( $customers[ $cid ][0] ) ) {
			$pending   = (array) get_option( 'semrs_us_paddle_pending', array() );
			$pending[] = $d;
			update_option( 'semrs_us_paddle_pending', array_slice( $pending, -100 ), false );
			return 'waiting for customer email';
		}
		list( $email, $name ) = $customers[ $cid ];
		$custom = (array) ( $d['custom_data'] ?? array() );
		$kid    = (string) ( $custom['kid'] ?? '' );
		$txn    = sanitize_text_field( (string) ( $d['id'] ?? '' ) );
		$sub    = sanitize_text_field( (string) ( $d['subscription_id'] ?? '' ) );
		$ends   = (string) ( $d['billing_period']['ends_at'] ?? '' );
		$items  = (array) ( $d['items'] ?? array() );
		$lines  = (array) ( $d['details']['line_items'] ?? array() );
		$notes  = array();
		foreach ( array_values( $items ) as $i => $item ) {
			$plan = semrs_us_plan_by_paddle_price( (array) ( $item['price'] ?? array() ) );
			if ( ! $plan ) {
				$notes[] = 'unmapped price ' . sanitize_text_field( (string) ( $item['price']['id'] ?? '' ) );
				continue;
			}
			$life = 'life' === $plan['term'];
			$ref  = ( $life || '' === $sub ? 'pd-txn:' . $txn : 'pd-sub:' . $sub ) . ( count( $items ) > 1 ? ':' . $i : '' );
			$key  = semrs_us_grant(
				$ref,
				array(
					'plan'    => $plan['id'],
					'addons'  => $plan['addons'],
					'sites'   => $plan['sites'],
					'expires' => $life ? '' : gmdate( 'Y-m-d', ( $ends ? strtotime( $ends ) : strtotime( '+1 year' ) ) + 7 * DAY_IN_SECONDS ),
					'order'   => $txn,
					'test'    => $test,
					'aff'     => sanitize_key( (string) ( $custom['aff'] ?? '' ) ),
					'amount'  => isset( $lines[ $i ]['totals']['total'] ) ? (int) $lines[ $i ]['totals']['total'] / 100 : null,
				),
				$email,
				$name,
				$kid
			);
			$notes[] = $plan['id'] . ' → ' . substr( $key, 0, 4 ) . '…' . substr( $key, -4 );
		}
		return $notes ? implode( '; ', $notes ) : 'no items';
	}

	if ( in_array( $event, array( 'subscription.updated', 'subscription.canceled' ), true ) ) {
		if ( 'canceled' !== ( $d['status'] ?? '' ) ) {
			return 'subscription ' . sanitize_key( (string) ( $d['status'] ?? '' ) );
		}
		$ref = 'pd-sub:' . sanitize_text_field( (string) ( $d['id'] ?? '' ) );
		$key = semrs_us_key_by_source( $ref );
		if ( ! $key ) {
			return 'canceled: no key found';
		}
		$k = semrs_us_keys()[ $key ];
		// Paid time is kept: the key runs to the end of the last paid period.
		$k['sources'][ $ref ]['status'] = 'canceled';
		semrs_us_save_key( $key, $k );
		return 'canceled';
	}

	if ( in_array( $event, array( 'adjustment.created', 'adjustment.updated' ), true ) ) {
		if ( 'refund' !== ( $d['action'] ?? '' ) || 'approved' !== ( $d['status'] ?? '' ) ) {
			return 'adjustment ' . sanitize_key( (string) ( $d['status'] ?? '' ) );
		}
		$txn  = (string) ( $d['transaction_id'] ?? '' );
		$done = false;
		foreach ( semrs_us_keys() as $k ) {
			foreach ( (array) ( $k['sources'] ?? array() ) as $ref => $src ) {
				if ( 0 === strpos( $ref, 'pd-' ) && (string) ( $src['order'] ?? '' ) === $txn ) {
					$done = semrs_us_refund( $ref ) || $done;
				}
			}
		}
		return $done ? 'refunded' : 'refund: no key found';
	}

	return 'ignored';
}
