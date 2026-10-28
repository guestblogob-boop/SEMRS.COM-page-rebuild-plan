<?php
/**
 * Lemon Squeezy webhook: POST /wp-json/semrs-updates/v1/lemonsqueezy
 *
 * Lemon Squeezy takes the payment; this turns it into a SEMRS Pro key.
 * Each Lemon Squeezy variant is mapped to a plan under SEMRS Updates → Sales.
 *
 * - subscription_created / _updated / _resumed / _cancelled / _expired:
 *   the subscription's add-ons, with updates until the next renewal date
 *   (plus 7 days for payment retries). Plan switches in the customer portal
 *   arrive as _updated with the new variant.
 * - order_created: lifetime plans only (yearly orders arrive as subscriptions).
 * - order_refunded: the order's add-ons leave the key.
 *
 * A checkout link with checkout[custom][kid] adds the purchase to that
 * customer's existing key instead of creating a new one.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle one webhook call.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_us_ls_webhook( $req ) {
	$secret = (string) semrs_us_sales()['ls_secret'];
	$raw    = (string) $req->get_body();
	$sig    = (string) $req->get_header( 'x_signature' );
	if ( '' === $secret ) {
		return new WP_Error( 'semrs_ls_off', 'Webhook secret not set.', array( 'status' => 503 ) );
	}
	if ( '' === $sig || ! hash_equals( hash_hmac( 'sha256', $raw, $secret ), $sig ) ) {
		return new WP_Error( 'semrs_ls_sig', 'Invalid signature.', array( 'status' => 401 ) );
	}
	$p      = json_decode( $raw, true );
	$event  = sanitize_key( $p['meta']['event_name'] ?? '' );
	$custom = (array) ( $p['meta']['custom_data'] ?? array() );
	$id     = sanitize_text_field( (string) ( $p['data']['id'] ?? '' ) );
	$a      = (array) ( $p['data']['attributes'] ?? array() );
	$test   = ! empty( $p['meta']['test_mode'] );
	$result = semrs_us_ls_handle( $event, $id, $a, $custom, $test );
	semrs_us_ls_log( $event, $id, $result, $test );
	return rest_ensure_response( array( 'ok' => true, 'result' => $result ) );
}

/**
 * Apply an event. Returns a short note for the log.
 *
 * @param string $event  Event name.
 * @param string $id     Object ID.
 * @param array  $a      Attributes.
 * @param array  $custom Custom checkout data.
 * @param bool   $test   Test mode.
 * @return string
 */
function semrs_us_ls_handle( $event, $id, $a, $custom, $test ) {
	$kid   = isset( $custom['kid'] ) ? (string) $custom['kid'] : '';
	$email = sanitize_email( (string) ( $a['user_email'] ?? '' ) );
	$name  = sanitize_text_field( (string) ( $a['user_name'] ?? '' ) );

	if ( in_array( $event, array( 'subscription_created', 'subscription_updated', 'subscription_resumed', 'subscription_cancelled', 'subscription_expired', 'subscription_unpaused' ), true ) ) {
		$plan = semrs_us_plan_by_variant( $a['variant_id'] ?? '' );
		if ( ! $plan ) {
			return 'unmapped variant ' . (int) ( $a['variant_id'] ?? 0 );
		}
		$ref = 'ls-sub:' . $id;
		if ( 'subscription_created' !== $event && ! semrs_us_key_by_source( $ref ) && ! semrs_us_key_by_kid( $kid ) ) {
			// Update for a subscription made before the webhook was set up: treat as new.
			$event = 'subscription_created';
		}
		$date    = in_array( $a['status'] ?? '', array( 'cancelled', 'expired' ), true ) && ! empty( $a['ends_at'] ) ? $a['ends_at'] : ( $a['renews_at'] ?? '' );
		$expires = $date ? gmdate( 'Y-m-d', strtotime( $date ) + 7 * DAY_IN_SECONDS ) : gmdate( 'Y-m-d', strtotime( '+1 year' ) );
		$key     = semrs_us_grant(
			$ref,
			array(
				'plan'    => $plan['id'],
				'addons'  => $plan['addons'],
				'sites'   => $plan['sites'],
				'expires' => $expires,
				'order'   => (string) ( $a['order_id'] ?? '' ),
				'portal'  => esc_url_raw( (string) ( $a['urls']['customer_portal'] ?? '' ) ),
				'test'    => $test,
			),
			$email,
			$name,
			$kid
		);
		return $plan['id'] . ' → ' . substr( $key, 0, 4 ) . '…' . substr( $key, -4 );
	}

	if ( 'order_created' === $event ) {
		if ( 'paid' !== ( $a['status'] ?? '' ) ) {
			return 'order not paid';
		}
		$plan = semrs_us_plan_by_variant( $a['first_order_item']['variant_id'] ?? '' );
		if ( ! $plan ) {
			return 'unmapped variant';
		}
		if ( 'life' !== $plan['term'] ) {
			return 'yearly plan: handled by its subscription';
		}
		$key = semrs_us_grant(
			'ls-order:' . $id,
			array(
				'plan'    => $plan['id'],
				'addons'  => $plan['addons'],
				'sites'   => $plan['sites'],
				'expires' => '',
				'order'   => $id,
				'test'    => $test,
			),
			$email,
			$name,
			$kid
		);
		return $plan['id'] . ' → ' . substr( $key, 0, 4 ) . '…' . substr( $key, -4 );
	}

	if ( 'order_refunded' === $event ) {
		$done = semrs_us_refund( 'ls-order:' . $id );
		foreach ( semrs_us_keys() as $k ) {
			foreach ( (array) ( $k['sources'] ?? array() ) as $ref => $src ) {
				if ( 0 === strpos( $ref, 'ls-sub:' ) && (string) ( $src['order'] ?? '' ) === $id ) {
					$done = semrs_us_refund( $ref ) || $done;
				}
			}
		}
		return $done ? 'refunded' : 'refund: no key found';
	}

	return 'ignored';
}

/**
 * Keep the last 50 webhook calls for the Sales screen.
 *
 * @param string $event  Event.
 * @param string $id     Object ID.
 * @param string $result Note.
 * @param bool   $test   Test mode.
 */
function semrs_us_ls_log( $event, $id, $result, $test ) {
	$log = get_option( 'semrs_us_ls_log', array() );
	$log = is_array( $log ) ? $log : array();
	array_unshift( $log, array( gmdate( 'Y-m-d H:i' ), $event, $id, $result, $test ) );
	update_option( 'semrs_us_ls_log', array_slice( $log, 0, 50 ), false );
}
