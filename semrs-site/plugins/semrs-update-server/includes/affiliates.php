<?php
/**
 * SEMRS affiliate program: partners earn a share of every SEMRS Pro sale
 * they send, plus a share of every renewal and add-on that customer buys
 * later, for as long as the customer stays.
 *
 * - Links: any semrs.com address with ?ref=CODE. The visit sets a first-party
 *   cookie (last click wins) and the address is cleaned (302) so search
 *   engines never see ?ref copies.
 * - Checkout: Paddle and Lemon Squeezy checkouts open on semrs.com, which
 *   adds the code to the order. Invoices take the code on the Invoices screen.
 * - The first paid order ties the customer's key to the partner for good:
 *   later renewals and add-ons pay the renewal rate.
 * - Commissions wait out the refund window, then turn "approved" by
 *   themselves. Payouts are made by hand (bank transfer or SadaPay) and marked paid.
 * - First-order rates rise with a partner's sales of each add-on in the
 *   calendar month (tiers, e.g. 40% for sales 1–10, 45% for 11–30, 50% for
 *   31–60, 60% from 61); every add-on has its own count, and each sale gets
 *   the rate of its place in that count. Renewals and
 *   add-ons pay the renewal rate during the first renewal year(s) only.
 * - Commission is worked out on the amount after payment fees (and before
 *   tax). All Pro and lifetime plans pay at most a set rate, capped per sale.
 * - Each calendar month is counted on its own and closed on the 1st of the
 *   next month: refunded sales drop out, the month's sales are renumbered
 *   and re-rated, and a bonus is added when the month reached N sales (all
 *   plans together).
 * - Partner code: a visitor who types a partner's code on the pricing page
 *   gets the partner discount at checkout, unless a bigger offer is running
 *   (best price wins, one discount per order); the partner gets the sale.
 * - Monthly bonus for reaching N sales in a month (paid after the refund window).
 * - The transfer fee for the partner's payout method comes off each payout.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Program settings. Defaults are set above the usual 20–30% that WordPress
 * plugin and theme programs pay.
 *
 * @return array
 */
function semrs_us_aff_settings() {
	$s = get_option( 'semrs_us_aff_settings', array() );
	return array_merge(
		array(
			'on'      => 1,
			'first'   => 40,
			'tiers'   => '1:40,11:45,31:50,61:60',
			'renewal' => 15,
			'ren_yrs' => 1,
			'cookie'  => 90,
			'min'     => 50,
			'hold'    => 30,
			'page'    => 0,
			'fee_pct'   => 10.5,
			'fee_flat'  => 0.5,
			'life_rate' => 40,
			'life_cap'  => 150,
			'pcode'     => 'PARTNER10',
			'pcode_pct' => 10,
			'bonus'     => 50,
			'bonus_n'   => 33,
			'min_intl'  => 100,
			'post_cta'  => 0,
			'send_intl' => 7,
			'send_pk'   => 7,
		),
		is_array( $s ) ? $s : array()
	);
}

/**
 * Partners: code => data.
 *
 * @return array<string, array>
 */
function semrs_us_affiliates() {
	$a = get_option( 'semrs_us_affiliates', array() );
	return is_array( $a ) ? $a : array();
}

/**
 * Save one partner.
 *
 * @param string $code Code.
 * @param array  $a    Data.
 */
function semrs_us_aff_save( $code, $a ) {
	$all          = semrs_us_affiliates();
	$all[ $code ] = $a;
	update_option( 'semrs_us_affiliates', $all, false );
}

/**
 * Commissions, newest first.
 *
 * @return array[]
 */
function semrs_us_referrals() {
	$r = get_option( 'semrs_us_referrals', array() );
	return is_array( $r ) ? $r : array();
}

/**
 * Save all commissions.
 *
 * @param array[] $rows Rows.
 */
function semrs_us_referrals_save( $rows ) {
	update_option( 'semrs_us_referrals', array_values( $rows ), false );
}

/**
 * Active partner for a code, or null.
 *
 * @param string $code Code.
 * @return array|null
 */
function semrs_us_aff_active( $code ) {
	$code = sanitize_key( (string) $code );
	$all  = semrs_us_affiliates();
	return '' !== $code && isset( $all[ $code ] ) && 'active' === $all[ $code ]['status'] ? $all[ $code ] : null;
}

/**
 * Page with the [semrs_affiliates] shortcode ('' when there is none).
 *
 * @return string
 */
function semrs_us_aff_page_url() {
	if ( ! semrs_us_aff_settings()['on'] ) {
		return '';
	}
	$id = (int) semrs_us_aff_settings()['page'];
	if ( ! $id ) {
		$p  = get_page_by_path( 'affiliates' );
		$id = $p && 'publish' === $p->post_status ? $p->ID : 0;
	}
	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Partner's link to any page.
 *
 * @param string $code Code.
 * @param string $url  Page (home by default).
 * @return string
 */
function semrs_us_aff_link( $code, $url = '' ) {
	return add_query_arg( 'ref', $code, $url ? $url : home_url( '/' ) );
}

/**
 * Code in the visitor's cookie, if it still belongs to an active partner.
 *
 * @return string
 */
function semrs_us_aff_cookie() {
	$code = isset( $_COOKIE['semrs_ref'] ) ? sanitize_key( wp_unslash( $_COOKIE['semrs_ref'] ) ) : '';
	return semrs_us_aff_active( $code ) ? $code : '';
}

/**
 * A visit with ?ref=CODE: count the click, set the cookie, clean the URL.
 */
function semrs_us_aff_track() {
	if ( empty( $_GET['ref'] ) || is_admin() || wp_doing_ajax() || ! semrs_us_aff_settings()['on'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- public tracking link.
		return;
	}
	$code = sanitize_key( wp_unslash( $_GET['ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( semrs_us_aff_active( $code ) ) {
		$days = (int) semrs_us_aff_settings()['cookie'];
		setcookie( 'semrs_ref', $code, array( 'expires' => time() + $days * DAY_IN_SECONDS, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'domain' => COOKIE_DOMAIN, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$seen = 'semrs_aff_c_' . md5( $code . $ip );
		if ( ! get_transient( $seen ) ) {
			set_transient( $seen, 1, DAY_IN_SECONDS );
			$clicks          = (array) get_option( 'semrs_us_aff_clicks', array() );
			$clicks[ $code ] = (int) ( $clicks[ $code ] ?? 0 ) + 1;
			update_option( 'semrs_us_aff_clicks', $clicks, false );
		}
	}
	nocache_headers();
	$to = remove_query_arg( array( 'ref', 'pcf' ) );
	if ( isset( $_GET['pcf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- partner code typed on the pricing page.
		$to .= semrs_us_aff_active( $code ) ? '#partner-ok' : '#partner-bad';
	}
	wp_safe_redirect( $to, 302 );
	exit;
}
add_action( 'init', 'semrs_us_aff_track', 1 );

/**
 * Called by semrs_us_grant() after a purchase lands on a key: tie the key to
 * its partner and record the commission for a new order or a renewal.
 *
 * @param string     $key  Key.
 * @param string     $ref  Source reference.
 * @param array      $src  Source as saved (may carry 'aff' and 'amount').
 * @param array|null $prev The same source before this purchase (null = new order).
 */
function semrs_us_aff_on_grant( $key, $ref, $src, $prev ) {
	if ( ! semrs_us_aff_settings()['on'] || ! empty( $src['test'] ) ) {
		return;
	}
	$all = semrs_us_keys();
	if ( ! isset( $all[ $key ] ) ) {
		return;
	}
	$k    = $all[ $key ];
	$code = ! empty( $k['aff'] ) ? (string) $k['aff'] : sanitize_key( (string) ( $src['aff'] ?? '' ) );
	$aff  = semrs_us_aff_active( $code );
	if ( ! $aff || strtolower( (string) $aff['email'] ) === strtolower( (string) $k['email'] ) ) {
		return; // No partner, or a partner buying for themselves.
	}
	if ( empty( $k['aff'] ) ) {
		$k['aff'] = $code;
		semrs_us_save_key( $key, $k );
	}
	$renew = $prev && '' !== (string) ( $src['expires'] ?? '' ) && (string) $src['expires'] > (string) ( $prev['expires'] ?? '' ) && 'refunded' !== ( $src['status'] ?? '' );
	if ( $prev && ! $renew ) {
		return; // A status change, not a payment.
	}
	$amount = isset( $src['amount'] ) ? (float) $src['amount'] : semrs_us_aff_guess_amount( (string) ( $src['plan'] ?? '' ) );
	if ( $amount <= 0 ) {
		return;
	}
	$s     = semrs_us_aff_settings();
	$base  = max( 0, $amount - ( $amount * (float) $s['fee_pct'] / 100 + (float) $s['fee_flat'] ) );
	$rows  = semrs_us_referrals();
	$first = ! array_filter(
		$rows,
		function ( $r ) use ( $key ) {
			return $r['key'] === semrs_us_kid( $key ) && 'rejected' !== $r['status'];
		}
	);
	if ( $first ) {
		$month = gmdate( 'Y-m' );
		$line  = semrs_us_aff_line( (string) ( $src['plan'] ?? '' ) );
		$n     = 1 + count(
			array_filter(
				$rows,
				function ( $r ) use ( $code, $month, $line ) {
					return $r['aff'] === $code && 'first' === $r['kind'] && 'rejected' !== $r['status'] && 0 === strpos( $r['date'], $month ) && semrs_us_aff_line( $r['plan'] ) === $line;
				}
			)
		);
		list( $rate, $com ) = semrs_us_aff_first_com( (string) ( $src['plan'] ?? '' ), $base, $n );
	} else {
		// Renewals and add-ons pay only in the first renewal year(s), plus 45 days for late payments.
		$start = '';
		foreach ( $rows as $r ) {
			if ( $r['key'] === semrs_us_kid( $key ) && 'first' === $r['kind'] && 'rejected' !== $r['status'] ) {
				$start = $r['date'];
			}
		}
		$yrs = (int) $s['ren_yrs'];
		if ( $yrs > 0 && ( '' === $start || strtotime( $start . ' +' . $yrs . ' year +45 days' ) < time() ) ) {
			return;
		}
		$rate = (int) $s['renewal'];
		$com  = round( $base * $rate / 100, 2 );
	}
	array_unshift(
		$rows,
		array(
			'id'     => substr( md5( $ref . microtime() ), 0, 10 ),
			'aff'    => $code,
			'ref'    => $ref,
			'key'    => semrs_us_kid( $key ),
			'plan'   => (string) ( $src['plan'] ?? '' ),
			'amount' => round( $amount, 2 ),
			'base'   => round( $base, 2 ),
			'rate'   => $rate,
			'com'    => $com,
			'kind'   => $first ? 'first' : 'renewal',
			'status' => 'pending',
			'date'   => gmdate( 'Y-m-d' ),
			'paid'   => '',
		)
	);
	semrs_us_referrals_save( array_slice( $rows, 0, 5000 ) );
}

/**
 * The count a plan belongs to: its add-on ("seo", "speed", …) or "all" for
 * All Pro and lifetime plans.
 *
 * @param string $plan Plan IDs, comma-separated (the first one decides).
 * @return string
 */
function semrs_us_aff_line( $plan ) {
	$id = (string) strtok( $plan, ',' );
	return 0 === strpos( $id, 'all-' ) ? 'all' : (string) strtok( $id, '-' );
}

/**
 * Rate and commission for a first order: the tier for its place in its
 * add-on's monthly count; All Pro and lifetime plans at most the set rate
 * and capped per sale.
 *
 * @param string $plan Plan IDs, comma-separated.
 * @param float  $base Amount after payment fees.
 * @param int    $n    Sale number in the month for this add-on.
 * @return array [ percent, commission ]
 */
function semrs_us_aff_first_com( $plan, $base, $n ) {
	$s    = semrs_us_aff_settings();
	$rate = semrs_us_aff_tier_rate( $n );
	$com  = round( $base * $rate / 100, 2 );
	if ( 'all' === semrs_us_aff_line( $plan ) ) {
		$rate = min( $rate, (int) $s['life_rate'] );
		$com  = round( $base * $rate / 100, 2 );
		$com  = (float) $s['life_cap'] > 0 ? min( (float) $s['life_cap'], $com ) : $com;
	}
	return array( $rate, $com );
}

/**
 * Renumber a partner's first orders in a month, per add-on (refunds left
 * out), and set each one's rate and commission again. Paid rows are left alone.
 *
 * @param array  $rows  Referrals (changed in place).
 * @param string $code  Partner code.
 * @param string $month Y-m.
 * @return int Sales counted in the month, all plans together.
 */
function semrs_us_aff_rerate( &$rows, $code, $month ) {
	$n   = 0;
	$per = array();
	for ( $i = count( $rows ) - 1; $i >= 0; $i-- ) { // Oldest first.
		$r = $rows[ $i ];
		if ( $r['aff'] !== $code || 'first' !== $r['kind'] || 'rejected' === $r['status'] || 0 !== strpos( $r['date'], $month ) ) {
			continue;
		}
		++$n;
		$line         = semrs_us_aff_line( $r['plan'] );
		$per[ $line ] = ( $per[ $line ] ?? 0 ) + 1;
		if ( 'paid' !== $r['status'] ) {
			list( $rows[ $i ]['rate'], $rows[ $i ]['com'] ) = semrs_us_aff_first_com( $r['plan'], (float) ( $r['base'] ?? $r['amount'] ), $per[ $line ] );
		}
	}
	return $n;
}

/**
 * A partner's month at a glance: sales counted, earnings, next rate, bonus.
 *
 * @param string $code  Partner code.
 * @param string $month Y-m.
 * @return array sales, earned, per (add-on => sales), bonus_left.
 */
function semrs_us_aff_month( $code, $month ) {
	$s   = semrs_us_aff_settings();
	$out = array( 'sales' => 0, 'earned' => 0, 'per' => array_fill_keys( array_keys( semrs_us_addons() ), 0 ) );
	foreach ( semrs_us_referrals() as $r ) {
		if ( $r['aff'] === $code && 'rejected' !== $r['status'] && 0 === strpos( $r['date'], $month ) ) {
			if ( 'first' === $r['kind'] ) {
				++$out['sales'];
				$line               = semrs_us_aff_line( $r['plan'] );
				$out['per'][ $line ] = ( $out['per'][ $line ] ?? 0 ) + 1;
			}
			$out['earned'] += $r['com'];
		}
	}
	$out['bonus_left'] = max( 0, (int) $s['bonus_n'] - $out['sales'] );
	return $out;
}

/**
 * Commission tiers: [ [ from sale number, percent ], … ] sorted by sale number.
 *
 * @return array[]
 */
function semrs_us_aff_tiers() {
	$out = array();
	foreach ( explode( ',', (string) semrs_us_aff_settings()['tiers'] ) as $pair ) {
		$p = array_map( 'absint', explode( ':', $pair ) );
		if ( 2 === count( $p ) && $p[0] > 0 ) {
			$out[] = array( $p[0], min( 90, $p[1] ) );
		}
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $a[0] <=> $b[0];
		}
	);
	return $out ? $out : array( array( 1, (int) semrs_us_aff_settings()['first'] ) );
}

/**
 * First-order rate for a partner's Nth sale this month.
 *
 * @param int $n Sale number in the month (1 = first).
 * @return int Percent.
 */
function semrs_us_aff_tier_rate( $n ) {
	$rate = (int) semrs_us_aff_settings()['first'];
	foreach ( semrs_us_aff_tiers() as $t ) {
		if ( $n >= $t[0] ) {
			$rate = $t[1];
		}
	}
	return $rate;
}

/**
 * Human list of the tiers, e.g. "40% (sales 1–10), 45% (11–30), …".
 *
 * @return string[] One line per tier.
 */
function semrs_us_aff_tier_lines() {
	$t   = semrs_us_aff_tiers();
	$out = array();
	foreach ( $t as $i => $row ) {
		$to    = isset( $t[ $i + 1 ] ) ? $t[ $i + 1 ][0] - 1 : 0;
		/* translators: 1: percent, 2: from, 3: to */
		$out[] = $to ? sprintf( __( '%1$d%% for sales %2$d–%3$d in a month', 'semrs-update-server' ), $row[1], $row[0], $to ) : sprintf( /* translators: 1: percent, 2: from */ __( '%1$d%% from sale %2$d in a month', 'semrs-update-server' ), $row[1], $row[0] );
	}
	return $out;
}

/**
 * Renewal terms in words, e.g. "15% in the first year".
 *
 * @return string
 */
function semrs_us_aff_renewal_text() {
	$s   = semrs_us_aff_settings();
	$yrs = (int) $s['ren_yrs'];
	/* translators: 1: percent, 2: years */
	return $yrs ? sprintf( _n( '%1$d%% on renewals and add-ons in the first year', '%1$d%% on renewals and add-ons in the first %2$d years', $yrs, 'semrs-update-server' ), (int) $s['renewal'], $yrs ) : sprintf( /* translators: %d: percent */ __( '%d%% on renewals and add-ons, for life', 'semrs-update-server' ), (int) $s['renewal'] );
}

/**
 * Highest first-order rate (for headlines such as "up to 60%").
 *
 * @return int
 */
function semrs_us_aff_top_rate() {
	return (int) max( wp_list_pluck( semrs_us_aff_tiers(), 1 ) );
}

/**
 * Amount paid when the payment service did not send it: the plan prices,
 * less the offer the Buy button carries today.
 *
 * @param string $plans Plan IDs, comma-separated.
 * @return float
 */
function semrs_us_aff_guess_amount( $plans ) {
	$all = semrs_us_plans();
	$sum = 0;
	foreach ( array_filter( explode( ',', $plans ) ) as $id ) {
		if ( isset( $all[ $id ] ) ) {
			$o    = semrs_us_offer_for( $id );
			$sum += $all[ $id ]['price'] * ( 100 - ( $o ? $o['percent'] : 0 ) ) / 100;
		}
	}
	return $sum;
}

/**
 * A refund cancels the commission for that order.
 *
 * @param string $ref Source reference.
 */
function semrs_us_aff_on_refund( $ref ) {
	$rows = semrs_us_referrals();
	$redo = array();
	foreach ( $rows as &$r ) {
		if ( $r['ref'] === $ref && 'paid' !== $r['status'] ) {
			$r['status'] = 'rejected';
			if ( 'first' === $r['kind'] ) {
				$redo[ $r['aff'] . '|' . substr( $r['date'], 0, 7 ) ] = 1;
			}
		}
	}
	unset( $r );
	// The refunded sale drops out of its month: later sales move up a place.
	foreach ( array_keys( $redo ) as $k ) {
		list( $code, $month ) = explode( '|', $k );
		semrs_us_aff_rerate( $rows, $code, $month );
	}
	semrs_us_referrals_save( $rows );
}

/**
 * Daily: commissions past the refund window become approved.
 */
function semrs_us_aff_approve_due() {
	$hold = gmdate( 'Y-m-d', strtotime( '-' . (int) semrs_us_aff_settings()['hold'] . ' days' ) );
	$rows = semrs_us_referrals();
	$done = false;
	$need = (int) semrs_us_aff_settings()['bonus_n'];
	foreach ( $rows as &$r ) {
		if ( 'pending' === $r['status'] && $r['date'] <= $hold ) {
			$r['status'] = 'approved';
			if ( 'bonus' === $r['kind'] ) {
				// Refunds after the month closed can still take it below the target.
				$month = substr( $r['date'], 0, 7 );
				$aff   = $r['aff'];
				$left  = count(
					array_filter(
						$rows,
						function ( $x ) use ( $aff, $month ) {
							return $x['aff'] === $aff && 'first' === $x['kind'] && 'rejected' !== $x['status'] && 0 === strpos( $x['date'], $month );
						}
					)
				);
				$r['status'] = $left >= $need ? 'approved' : 'rejected';
			}
			$done = true;
		}
	}
	unset( $r );
	if ( $done ) {
		semrs_us_referrals_save( $rows );
	}
}
add_action( 'semrs_us_daily', 'semrs_us_aff_approve_due' );

/**
 * Daily, acts once a month: close last month. Each partner's sales are
 * renumbered without refunds and re-rated, and the monthly bonus is added
 * (pending, so it waits out the refund window like any commission).
 */
function semrs_us_aff_close_month() {
	$month = gmdate( 'Y-m', strtotime( 'first day of last month' ) );
	if ( get_option( 'semrs_us_aff_closed' ) >= $month ) {
		return;
	}
	$s    = semrs_us_aff_settings();
	$rows = semrs_us_referrals();
	$aff  = array_unique( wp_list_pluck( $rows, 'aff' ) );
	foreach ( $aff as $code ) {
		$n = semrs_us_aff_rerate( $rows, $code, $month );
		if ( (float) $s['bonus'] > 0 && (int) $s['bonus_n'] > 0 && $n >= (int) $s['bonus_n'] ) {
			array_unshift( $rows, array( 'id' => substr( md5( $code . 'bonus' . $month ), 0, 10 ), 'aff' => $code, 'ref' => 'bonus-' . $month, 'key' => '', 'plan' => '', 'amount' => 0, 'base' => 0, 'rate' => 0, 'com' => round( (float) $s['bonus'], 2 ), 'kind' => 'bonus', 'status' => 'pending', 'date' => gmdate( 'Y-m-t', strtotime( $month . '-01' ) ), 'paid' => '' ) );
		}
	}
	semrs_us_referrals_save( $rows );
	update_option( 'semrs_us_aff_closed', $month, false );
}
add_action( 'semrs_us_daily', 'semrs_us_aff_close_month', 5 );

/**
 * All Pro terms in words, e.g. "40% on All Pro and lifetime plans, up to $150 a sale".
 *
 * @return string
 */
function semrs_us_aff_life_text() {
	$s = semrs_us_aff_settings();
	/* translators: 1: percent, 2: amount */
	return (float) $s['life_cap'] > 0 ? sprintf( __( '%1$d%% on All Pro and lifetime plans, up to %2$s a sale', 'semrs-update-server' ), (int) $s['life_rate'], semrs_us_money( $s['life_cap'] ) ) : sprintf( /* translators: %d: percent */ __( '%d%% on All Pro and lifetime plans', 'semrs-update-server' ), (int) $s['life_rate'] );
}

/**
 * Monthly bonus in words.
 *
 * @return string
 */
function semrs_us_aff_bonus_text() {
	$s = semrs_us_aff_settings();
	/* translators: 1: amount, 2: sales */
	return sprintf( __( '%1$s bonus every month you reach %2$d sales (the count restarts on the 1st)', 'semrs-update-server' ), semrs_us_money( $s['bonus'] ), (int) $s['bonus_n'] );
}

/**
 * Payout terms in words.
 *
 * @return string
 */
function semrs_us_aff_payout_text() {
	$s = semrs_us_aff_settings();
	/* translators: 1: minimum in Pakistan, 2: minimum abroad */
	$text = sprintf( __( 'Monthly in US dollars: SadaPay or bank transfer in Pakistan from %1$s, international bank transfer from %2$s.', 'semrs-update-server' ), semrs_us_money( $s['min'] ), semrs_us_money( $s['min_intl'] ) );
	if ( (float) $s['send_intl'] === (float) $s['send_pk'] ) {
		/* translators: %s: percent */
		return $text . ' ' . sprintf( __( 'A %s%% exchange and transfer fee comes off each payout.', 'semrs-update-server' ), (float) $s['send_intl'] );
	}
	/* translators: 1: percent abroad, 2: percent in Pakistan */
	return $text . ' ' . sprintf( __( 'An exchange and transfer fee comes off each payout: %1$s%% for international transfers, %2$s%% in Pakistan.', 'semrs-update-server' ), (float) $s['send_intl'], (float) $s['send_pk'] );
}

/**
 * Transfer fee percent for a partner's payout: international unless they
 * are paid by SadaPay or a bank in Pakistan.
 *
 * @param array $a Partner.
 * @return float
 */
function semrs_us_aff_send_pct( $a ) {
	$s     = semrs_us_aff_settings();
	$how   = strtolower( (string) ( $a['payout'] ?? '' ) );
	$local = false !== strpos( $how, 'sadapay' ) || false !== strpos( $how, 'pakistan' ) || ( '' === $how && false !== stripos( (string) ( $a['country'] ?? '' ), 'pakistan' ) );
	return (float) ( $local ? $s['send_pk'] : $s['send_intl'] );
}

/**
 * What the partner receives from an amount after the transfer fee.
 *
 * @param array $a      Partner.
 * @param float $amount Commission amount.
 * @return float
 */
function semrs_us_aff_receive( $a, $amount ) {
	return round( $amount * ( 100 - semrs_us_aff_send_pct( $a ) ) / 100, 2 );
}

/**
 * The partner discount for the visitor's partner code, if it beats the offer
 * running now (best price wins, one discount per order).
 *
 * @param array|null $offer Offer from semrs_us_offer_for().
 * @return array|null code, percent.
 */
function semrs_us_aff_code_offer( $offer ) {
	$s = semrs_us_aff_settings();
	if ( ! $s['on'] || '' === (string) $s['pcode'] || (int) $s['pcode_pct'] <= 0 || '' === semrs_us_aff_cookie() ) {
		return null;
	}
	return $offer && (int) $offer['percent'] >= (int) $s['pcode_pct'] ? null : array( 'code' => (string) $s['pcode'], 'percent' => (int) $s['pcode_pct'] );
}

/**
 * Totals for a partner: clicks, sales, pending, approved (unpaid), paid.
 *
 * @param string $code Code.
 * @return array
 */
function semrs_us_aff_totals( $code ) {
	$t = array( 'clicks' => (int) ( get_option( 'semrs_us_aff_clicks', array() )[ $code ] ?? 0 ), 'sales' => 0, 'pending' => 0, 'approved' => 0, 'paid' => 0 );
	foreach ( semrs_us_referrals() as $r ) {
		if ( $r['aff'] !== $code || 'rejected' === $r['status'] ) {
			continue;
		}
		$t['sales'] += 'bonus' === $r['kind'] ? 0 : 1;
		$t[ $r['status'] ] += $r['com'];
	}
	return $t;
}

/**
 * Partner dashboard link (secret token, no password).
 *
 * @param string $code Code.
 * @param array  $a    Partner.
 * @return string
 */
function semrs_us_aff_dash_url( $code, $a ) {
	$page = semrs_us_aff_page_url();
	return add_query_arg( array( 'aff' => $code, 't' => $a['token'] ), $page ? $page : home_url( '/affiliates/' ) );
}

/**
 * Email a partner their links and dashboard.
 *
 * @param string $code Code.
 * @param array  $a    Partner.
 * @return bool
 */
function semrs_us_aff_mail_welcome( $code, $a ) {
	$s = semrs_us_aff_settings();
	return semrs_us_mail(
		$a['email'],
		__( 'You are a SEMRS affiliate', 'semrs-update-server' ),
		implode(
			"\n",
			array(
				/* translators: %s: name */
				sprintf( __( 'Hi %s,', 'semrs-update-server' ), $a['name'] ),
				'',
				__( 'Welcome to the SEMRS affiliate program.', 'semrs-update-server' ),
				'',
				__( 'Your link:', 'semrs-update-server' ) . ' ' . semrs_us_aff_link( $code ),
				__( 'Link to SEMRS Pro pricing:', 'semrs-update-server' ) . ' ' . semrs_us_aff_link( $code, strtok( semrs_us_pricing_url(), '#' ) ),
				__( 'Add ?ref=', 'semrs-update-server' ) . $code . ' ' . __( 'to any semrs.com address to link there.', 'semrs-update-server' ),
				'',
				/* translators: 1: first percent, 2: renewal percent, 3: cookie days */
				__( 'First orders, each add-on counted on its own each month:', 'semrs-update-server' ) . ' ' . implode( '; ', semrs_us_aff_tier_lines() ) . '.',
				/* translators: 1: renewal terms, 2: cookie days */
				sprintf( __( 'Plus %1$s. Cookie: %2$d days.', 'semrs-update-server' ), semrs_us_aff_renewal_text(), $s['cookie'] ),
				ucfirst( semrs_us_aff_life_text() ) . '. ' . __( 'Commission is worked out on the amount paid after payment fees, before tax.', 'semrs-update-server' ),
				/* translators: 1: code, 2: percent */
				sprintf( __( 'Your partner code: %1$s. People who type it on the SEMRS Pro pricing page get %2$d%% off (or the sale price, if bigger), and the sale is yours.', 'semrs-update-server' ), strtoupper( $code ), (int) $s['pcode_pct'] ),
				(float) $s['bonus'] > 0 ? ucfirst( semrs_us_aff_bonus_text() ) . '.' : '',
				/* translators: %d: days */
				sprintf( __( 'Commissions are approved %d days after the sale (the refund window).', 'semrs-update-server' ), $s['hold'] ) . ' ' . semrs_us_aff_payout_text(),
				'',
				__( 'Your dashboard (keep this link private):', 'semrs-update-server' ) . ' ' . semrs_us_aff_dash_url( $code, $a ),
				'',
				__( 'Please say you are an affiliate wherever you share your link (FTC rules).', 'semrs-update-server' ),
				'',
				semrs_us_sales()['seller_name'],
			)
		)
	);
}

/**
 * Public form posts: apply, or email me my dashboard link. No nonce (cached
 * pages could hold an old one): a honeypot and rate limits keep bots out.
 */
function semrs_us_aff_post() {
	if ( empty( $_POST['semrs_aff'] ) || ! semrs_us_aff_settings()['on'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$do   = sanitize_key( wp_unslash( $_POST['semrs_aff'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$back = semrs_us_aff_page_url() ? semrs_us_aff_page_url() : home_url( '/' );
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate = 'semrs_aff_ip_' . md5( $ip );
	$hits = (int) get_transient( $rate );
	if ( ! empty( $_POST['website_url'] ) || $hits >= 5 ) { // phpcs:ignore WordPress.Security.NonceVerification -- honeypot.
		wp_safe_redirect( add_query_arg( 'aff_sent', 1, $back ) );
		exit;
	}
	set_transient( $rate, $hits + 1, HOUR_IN_SECONDS );
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'link' === $do ) {
		foreach ( semrs_us_affiliates() as $code => $a ) {
			if ( 'active' === $a['status'] && strtolower( $a['email'] ) === strtolower( $email ) ) {
				semrs_us_aff_mail_welcome( $code, $a );
			}
		}
		wp_safe_redirect( add_query_arg( 'aff_sent', 1, $back ) );
		exit;
	}
	if ( 'apply' !== $do || ! is_email( $email ) || empty( $_POST['terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( add_query_arg( 'aff_error', 1, $back ) );
		exit;
	}
	foreach ( semrs_us_affiliates() as $a ) {
		if ( strtolower( $a['email'] ) === strtolower( $email ) ) {
			wp_safe_redirect( add_query_arg( 'aff_applied', 1, $back ) );
			exit;
		}
	}
	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$wa      = semrs_us_aff_phone( wp_unslash( $_POST['whatsapp'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$country = sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$acct    = array(
		'holder' => sanitize_text_field( wp_unslash( $_POST['acct_holder'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
		'bank'   => sanitize_text_field( wp_unslash( $_POST['acct_bank'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
		'number' => preg_replace( '/[^A-Za-z0-9 +-]/', '', (string) wp_unslash( $_POST['acct_number'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
		'swift'  => strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['acct_swift'] ?? '' ) ) ), // phpcs:ignore WordPress.Security.NonceVerification
	);
	foreach ( array( 'holder' => 80, 'bank' => 80, 'number' => 40, 'swift' => 11 ) as $k => $len ) {
		$acct[ $k ] = trim( substr( $acct[ $k ], 0, $len ) );
	}
	if ( '' === $wa || '' === $country || '' === $acct['holder'] || '' === $acct['number'] ) {
		wp_safe_redirect( add_query_arg( 'aff_error', 1, $back ) );
		exit;
	}
	$code = semrs_us_aff_new_code( $name ? $name : strtok( $email, '@' ) );
	semrs_us_aff_save(
		$code,
		array(
			'name'    => $name,
			'email'   => $email,
			'site'    => esc_url_raw( wp_unslash( $_POST['site'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'how'     => sanitize_textarea_field( wp_unslash( $_POST['how'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'payout'  => sanitize_text_field( wp_unslash( $_POST['payout'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'wa'      => $wa,
			'country' => substr( $country, 0, 60 ),
			'acct'    => $acct,
			'status'  => 'pending',
			'token'   => bin2hex( random_bytes( 16 ) ),
			'created' => gmdate( 'Y-m-d' ),
		)
	);
	$admin = sanitize_email( semrs_us_sales()['seller_email'] );
	if ( $admin ) {
		/* translators: %s: name */
		semrs_us_mail( $admin, sprintf( __( 'New affiliate application: %s', 'semrs-update-server' ), $name ), $name . ' <' . $email . ">\n" . $country . ' · WhatsApp ' . $wa . "\n" . esc_url_raw( wp_unslash( $_POST['site'] ?? '' ) ) . "\n\n" . admin_url( 'admin.php?page=semrs-updates-affiliates' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	semrs_us_mail( $email, __( 'We got your SEMRS affiliate application', 'semrs-update-server' ), __( 'Thanks for applying. We review every application by hand, usually within 2 business days, and email you your link when you are approved.', 'semrs-update-server' ) . "\n\n" . semrs_us_sales()['seller_name'] );
	wp_safe_redirect( add_query_arg( 'aff_applied', 1, $back ) );
	exit;
}
add_action( 'init', 'semrs_us_aff_post' );

/**
 * A phone number kept to digits, spaces and + ( ) -, or ''.
 *
 * @param string $v Raw value.
 * @return string
 */
function semrs_us_aff_phone( $v ) {
	$v = trim( preg_replace( '/[^0-9+ ()-]/', '', (string) $v ) );
	return strlen( preg_replace( '/\D/', '', $v ) ) >= 7 ? substr( $v, 0, 20 ) : '';
}

/**
 * A free, readable partner code from a name ("Jane Doe" → "janedoe").
 *
 * @param string $base Name.
 * @return string
 */
function semrs_us_aff_new_code( $base ) {
	$base = substr( preg_replace( '/[^a-z0-9]/', '', strtolower( remove_accents( $base ) ) ), 0, 16 );
	$base = strlen( $base ) >= 3 ? $base : 'partner';
	$code = $base;
	$all  = semrs_us_affiliates();
	for ( $i = 2; isset( $all[ $code ] ); $i++ ) {
		$code = $base . $i;
	}
	return $code;
}

/**
 * Dashboard view: noindex.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function semrs_us_aff_robots( $robots ) {
	if ( isset( $_GET['aff'] ) || isset( $_GET['aff_sent'] ) || isset( $_GET['aff_applied'] ) || isset( $_GET['aff_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'semrs_us_aff_robots' );

/**
 * [semrs_affiliates]: the rates table and application form, or a partner's
 * dashboard when opened with their private link.
 *
 * @return string
 */
function semrs_us_aff_shortcode() {
	$s = semrs_us_aff_settings();
	if ( ! $s['on'] ) {
		return '<p>' . esc_html__( 'The affiliate program is paused. Please check back soon.', 'semrs-update-server' ) . '</p>';
	}
	$code = isset( $_GET['aff'] ) ? sanitize_key( wp_unslash( $_GET['aff'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$tok  = isset( $_GET['t'] ) ? sanitize_key( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$all  = semrs_us_affiliates();
	if ( $code && isset( $all[ $code ] ) && 'active' === $all[ $code ]['status'] && hash_equals( (string) $all[ $code ]['token'], $tok ) ) {
		return semrs_us_aff_dashboard( $code, $all[ $code ] );
	}
	$url  = esc_url( semrs_us_aff_page_url() ? semrs_us_aff_page_url() : home_url( '/affiliates/' ) );
	$out  = '<div class="semrs-aff">';
	if ( isset( $_GET['aff_applied'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$out .= '<p class="semrs-aff__note" role="status">' . esc_html__( 'Thanks! Your application is in. We will email you within 2 business days.', 'semrs-update-server' ) . '</p>';
	} elseif ( isset( $_GET['aff_sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$out .= '<p class="semrs-aff__note" role="status">' . esc_html__( 'If that email belongs to an approved affiliate, your links are on the way.', 'semrs-update-server' ) . '</p>';
	} elseif ( isset( $_GET['aff_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$out .= '<p class="semrs-aff__note" role="alert">' . esc_html__( 'Please fill in your email, WhatsApp number, country and receiving account, and accept the terms.', 'semrs-update-server' ) . '</p>';
	}
	$rows = array(
		array( __( 'Each add-on (its own monthly count)', 'semrs-update-server' ), implode( ' · ', semrs_us_aff_tier_lines() ) ),
		array( __( 'Renewals and add-ons', 'semrs-update-server' ), ucfirst( semrs_us_aff_renewal_text() ) ),
		array( __( 'All Pro and lifetime', 'semrs-update-server' ), ucfirst( semrs_us_aff_life_text() ) ),
		array( __( 'Your partner code', 'semrs-update-server' ), sprintf( /* translators: %d: percent */ __( 'Your audience saves %d%% (or gets the sale price, if bigger)', 'semrs-update-server' ), (int) $s['pcode_pct'] ) ),
		array( __( 'Cookie length', 'semrs-update-server' ), sprintf( /* translators: %d: days */ _n( '%d day', '%d days', (int) $s['cookie'], 'semrs-update-server' ), (int) $s['cookie'] ) ),
		array( __( 'Approval', 'semrs-update-server' ), sprintf( /* translators: %d: days */ __( '%d days after the sale (refund window)', 'semrs-update-server' ), (int) $s['hold'] ) ),
		array( __( 'Payouts', 'semrs-update-server' ), semrs_us_aff_payout_text() ),
	);
	if ( (float) $s['bonus'] > 0 ) {
		array_splice( $rows, 4, 0, array( array( __( 'Monthly bonus', 'semrs-update-server' ), ucfirst( semrs_us_aff_bonus_text() ) ) ) );
	}
	$out .= '<table class="semrs-aff__rates"><caption>' . esc_html__( 'SEMRS affiliate rates', 'semrs-update-server' ) . '</caption><tbody>';
	foreach ( $rows as $r ) {
		$out .= '<tr><th scope="row">' . esc_html( $r[0] ) . '</th><td>' . esc_html( $r[1] ) . '</td></tr>';
	}
	$out .= '</tbody></table>';
	$f    = function ( $id, $label, $field ) {
		return '<p><label for="semrs-aff-' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>' . $field . '</p>';
	};
	$out .= '<form method="post" action="' . $url . '" class="semrs-aff__form" id="apply"><h2>' . esc_html__( 'Apply to join', 'semrs-update-server' ) . '</h2><input type="hidden" name="semrs_aff" value="apply">'
		. $f( 'name', __( 'Your name', 'semrs-update-server' ), '<input id="semrs-aff-name" name="name" required autocomplete="name">' )
		. $f( 'email', __( 'Email', 'semrs-update-server' ), '<input id="semrs-aff-email" type="email" name="email" required autocomplete="email">' )
		. $f( 'whatsapp', __( 'WhatsApp number (with country code)', 'semrs-update-server' ), '<input id="semrs-aff-whatsapp" type="tel" name="whatsapp" required autocomplete="tel" placeholder="+92 300 1234567" pattern="[+0-9 ()-]{7,20}">' )
		. $f( 'country', __( 'Country', 'semrs-update-server' ), '<input id="semrs-aff-country" name="country" required autocomplete="country-name" maxlength="60">' )
		. $f( 'site', __( 'Website or channel', 'semrs-update-server' ), '<input id="semrs-aff-site" type="url" name="site" placeholder="https://" autocomplete="url">' )
		. $f( 'how', __( 'How will you share SEMRS?', 'semrs-update-server' ), '<textarea id="semrs-aff-how" name="how" rows="3"></textarea>' )
		. $f( 'payout', __( 'How should we pay you? (we confirm the details by email)', 'semrs-update-server' ), '<select id="semrs-aff-payout" name="payout"><option>' . esc_html__( 'International bank transfer', 'semrs-update-server' ) . '</option><option>' . esc_html__( 'Bank transfer (Pakistan)', 'semrs-update-server' ) . '</option><option>' . esc_html__( 'SadaPay (Pakistan)', 'semrs-update-server' ) . '</option></select>' )
		. '<fieldset class="semrs-aff__acct"><legend>' . esc_html__( 'Receiving account (where we send your payouts)', 'semrs-update-server' ) . '</legend>'
		. $f( 'acct-holder', __( 'Account holder name', 'semrs-update-server' ), '<input id="semrs-aff-acct-holder" name="acct_holder" required maxlength="80" autocomplete="off">' )
		. $f( 'acct-bank', __( 'Bank name (or SadaPay)', 'semrs-update-server' ), '<input id="semrs-aff-acct-bank" name="acct_bank" maxlength="80" autocomplete="off">' )
		. $f( 'acct-number', __( 'IBAN, account number or SadaPay number', 'semrs-update-server' ), '<input id="semrs-aff-acct-number" name="acct_number" required maxlength="40" autocomplete="off" spellcheck="false">' )
		. $f( 'acct-swift', __( 'SWIFT / BIC code (international transfers only)', 'semrs-update-server' ), '<input id="semrs-aff-acct-swift" name="acct_swift" maxlength="11" autocomplete="off" spellcheck="false">' )
		. '<p class="semrs-aff__small">' . esc_html__( 'Only the SEMRS team sees these details. We confirm them with you on WhatsApp or email before the first payout.', 'semrs-update-server' ) . '</p></fieldset>'
		. '<p class="semrs-aff__hp" aria-hidden="true"><label>Website URL <input name="website_url" tabindex="-1" autocomplete="off"></label></p>'
		. '<p><label><input type="checkbox" name="terms" value="1" required> ' . esc_html__( 'I will say I am an affiliate where I share my link, and I will not bid on SEMRS brand terms in ads or use spam.', 'semrs-update-server' ) . '</label></p>'
		. '<p><button class="semrs-btn semrs-btn--buy" type="submit">' . esc_html__( 'Send application', 'semrs-update-server' ) . '</button></p></form>';
	$out .= '<form method="post" action="' . $url . '" class="semrs-aff__form semrs-aff__form--small"><input type="hidden" name="semrs_aff" value="link">' . $f( 'link-email', __( 'Already an affiliate? Get your links and dashboard by email', 'semrs-update-server' ), '<span class="semrs-aff__row"><input id="semrs-aff-link-email" type="email" name="email" required autocomplete="email"> <button class="semrs-btn" type="submit">' . esc_html__( 'Email my links', 'semrs-update-server' ) . '</button></span>' ) . '</form>';
	$out .= '<style>.semrs-aff__rates{width:100%;border-collapse:collapse;margin:0 0 24px}.semrs-aff__rates caption{text-align:left;font-weight:700;padding:0 0 8px}.semrs-aff__rates th,.semrs-aff__rates td{padding:10px 12px;border-bottom:1px solid var(--sp-line,#e5e7eb);text-align:left}.semrs-aff__rates td{font-weight:700}.semrs-aff__form{max-width:36rem;margin:0 0 24px}.semrs-aff__form label{display:block;font-weight:600;margin:0 0 4px}.semrs-aff__form input:not([type=checkbox]),.semrs-aff__form textarea{width:100%;padding:10px 12px;border:1px solid var(--sp-line,#d1d5db);border-radius:8px;font:inherit}.semrs-aff__form input[type=checkbox]{margin-right:6px}.semrs-aff__row{display:flex;gap:8px}.semrs-aff__hp{position:absolute;left:-9999px}.semrs-aff__acct{margin:0 0 16px;padding:12px 16px;border:1px solid var(--sp-line,#d1d5db);border-radius:12px}.semrs-aff__acct legend{font-weight:700;padding:0 6px}.semrs-aff__small{font-size:.9em;margin:0}.semrs-aff__note{border:2px solid var(--sp-accent,#d4a72c);border-radius:12px;padding:12px 16px}.semrs-aff__stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));gap:12px;margin:0 0 24px;padding:0;list-style:none}.semrs-aff__stats li{border:1px solid var(--sp-line,#e5e7eb);border-radius:12px;padding:12px}.semrs-aff__stats strong{display:block;font-size:1.4rem}</style></div>';
	return $out;
}
add_shortcode( 'semrs_affiliates', 'semrs_us_aff_shortcode' );

/**
 * A partner's dashboard.
 *
 * @param string $code Code.
 * @param array  $a    Partner.
 * @return string
 */
function semrs_us_aff_dashboard( $code, $a ) {
	$t    = semrs_us_aff_totals( $code );
	$out  = '<div class="semrs-aff"><h2>' . esc_html( sprintf( /* translators: %s: name */ __( 'Affiliate dashboard: %s', 'semrs-update-server' ), $a['name'] ? $a['name'] : $code ) ) . '</h2>';
	$out .= '<p><strong>' . esc_html__( 'Your link:', 'semrs-update-server' ) . '</strong> <code>' . esc_html( semrs_us_aff_link( $code ) ) . '</code><br>' . esc_html__( 'Add', 'semrs-update-server' ) . ' <code>?ref=' . esc_html( $code ) . '</code> ' . esc_html__( 'to any semrs.com address, for example the pricing page:', 'semrs-update-server' ) . ' <code>' . esc_html( semrs_us_aff_link( $code, strtok( semrs_us_pricing_url(), '#' ) ) ) . '</code></p>';
	$s    = semrs_us_aff_settings();
	/* translators: 1: code, 2: percent */
	$out .= '<p><strong>' . esc_html__( 'Your partner code:', 'semrs-update-server' ) . '</strong> <code>' . esc_html( strtoupper( $code ) ) . '</code> ' . esc_html( sprintf( __( 'Share it in videos, podcasts and posts. Typed on the SEMRS Pro pricing page, it gives %d%% off (or the sale price, if bigger), and the sale is yours.', 'semrs-update-server' ), (int) $s['pcode_pct'] ) ) . '</p>';
	$out .= '<p>' . esc_html( semrs_us_aff_payout_text() ) . '</p>';
	$now  = semrs_us_aff_month( $code, gmdate( 'Y-m' ) );
	$last = semrs_us_aff_month( $code, gmdate( 'Y-m', strtotime( 'first day of last month' ) ) );
	$out .= '<h3>' . esc_html( sprintf( /* translators: %s: month */ __( 'This month (%s)', 'semrs-update-server' ), wp_date( 'F Y' ) ) ) . '</h3><ul class="semrs-aff__stats">';
	$out .= '<li><strong>' . esc_html( number_format_i18n( $now['sales'] ) ) . '</strong>' . esc_html__( 'Sales counted', 'semrs-update-server' ) . '</li>';
	$out .= '<li><strong>' . esc_html( semrs_us_money( $now['earned'] ) ) . '</strong>' . esc_html__( 'Earned so far', 'semrs-update-server' ) . '</li>';
	foreach ( semrs_us_addons() as $slug => $name ) {
		/* translators: 1: add-on, 2: sales, 3: percent */
		$out .= '<li><strong>' . esc_html( semrs_us_aff_tier_rate( $now['per'][ $slug ] + 1 ) . '%' ) . '</strong>' . esc_html( sprintf( __( '%1$s: %2$d sales, rate on the next one', 'semrs-update-server' ), $name, $now['per'][ $slug ] ) ) . '</li>';
	}
	/* translators: %d: sales */
	$out .= '<li><strong>' . esc_html( $now['bonus_left'] ? sprintf( _n( '%d to go', '%d to go', $now['bonus_left'], 'semrs-update-server' ), $now['bonus_left'] ) : __( 'Reached', 'semrs-update-server' ) ) . '</strong>' . esc_html( sprintf( /* translators: %s: amount */ __( '%s monthly bonus', 'semrs-update-server' ), semrs_us_money( $s['bonus'] ) ) ) . '</li>';
	/* translators: 1: month, 2: sales, 3: amount */
	$out .= '<li><strong>' . esc_html( semrs_us_money( $last['earned'] ) ) . '</strong>' . esc_html( sprintf( __( '%1$s: %2$d sales (closed)', 'semrs-update-server' ), wp_date( 'F', strtotime( 'first day of last month' ) ), $last['sales'] ) ) . '</li></ul>';
	$out .= '<p><small>' . esc_html__( 'Each month is counted from the 1st to the last day and closed on the 1st of the next month. Refunded sales earn nothing and leave the count.', 'semrs-update-server' ) . '</small></p>';
	$t    = semrs_us_aff_totals( $code );
	/* translators: %s: amount */
	$out .= '<p class="semrs-aff__note"><strong>' . esc_html( sprintf( __( 'You will receive: %s', 'semrs-update-server' ), semrs_us_money( semrs_us_aff_receive( $a, $t['approved'] ) ) ) ) . '</strong> '
		/* translators: 1: approved amount, 2: percent */
		. esc_html( sprintf( __( 'from your approved %1$s, after the exchange and transfer fee (%2$s%%). Pending commissions join it once approved.', 'semrs-update-server' ), semrs_us_money( $t['approved'] ), semrs_us_aff_send_pct( $a ) ) ) . '</p>';
	$out .= '<h3>' . esc_html__( 'All time', 'semrs-update-server' ) . '</h3><ul class="semrs-aff__stats">';
	foreach ( array( 'clicks' => __( 'Clicks', 'semrs-update-server' ), 'sales' => __( 'Sales', 'semrs-update-server' ), 'pending' => __( 'Pending', 'semrs-update-server' ), 'approved' => __( 'Approved, unpaid', 'semrs-update-server' ), 'paid' => __( 'Paid', 'semrs-update-server' ) ) as $k => $label ) {
		$val  = in_array( $k, array( 'clicks', 'sales' ), true ) ? number_format_i18n( $t[ $k ] ) : semrs_us_money( $t[ $k ] );
		$out .= '<li><strong>' . esc_html( $val ) . '</strong>' . esc_html( $label ) . '</li>';
	}
	$out .= '</ul><table class="semrs-aff__rates"><caption>' . esc_html__( 'Recent commissions', 'semrs-update-server' ) . '</caption><thead><tr><th>' . esc_html__( 'Date', 'semrs-update-server' ) . '</th><th>' . esc_html__( 'Plan', 'semrs-update-server' ) . '</th><th>' . esc_html__( 'Commission', 'semrs-update-server' ) . '</th><th>' . esc_html__( 'Status', 'semrs-update-server' ) . '</th></tr></thead><tbody>';
	$n = 0;
	foreach ( semrs_us_referrals() as $r ) {
		if ( $r['aff'] !== $code || ++$n > 50 ) {
			continue;
		}
		$out .= '<tr><td>' . esc_html( $r['date'] ) . '</td><td>' . esc_html( $r['plan'] . ' (' . ( 'first' === $r['kind'] ? __( 'first order', 'semrs-update-server' ) : ( 'bonus' === $r['kind'] ? __( 'monthly bonus', 'semrs-update-server' ) : __( 'renewal', 'semrs-update-server' ) ) ) . ')' ) . '</td><td>' . esc_html( semrs_us_money( $r['com'] ) . ' (' . $r['rate'] . '%)' ) . '</td><td>' . esc_html( ucfirst( $r['status'] ) ) . '</td></tr>';
	}
	if ( ! $n ) {
		$out .= '<tr><td colspan="4">' . esc_html__( 'No sales yet. Share your link to get started.', 'semrs-update-server' ) . '</td></tr>';
	}
	return $out . '</tbody></table>' . semrs_us_aff_creatives_html( $code ) . '</div>';
}

/**
 * Offers a plan can get, for the calculator: every enabled SEMRS sale and
 * deal (lifetime percent on lifetime plans, plan limits kept), the launch
 * offer and the partner code. One applies per order.
 *
 * @param string $plan Plan ID.
 * @return array[] id, name, percent.
 */
function semrs_us_aff_calc_offers( $plan ) {
	$life = 0 === strpos( $plan, 'all-life-' );
	$out  = array();
	foreach ( semrs_us_campaigns() as $c ) {
		if ( empty( $c['enabled'] ) || (int) $c['percent'] <= 0 || ( $c['plans'] && ! in_array( $plan, $c['plans'], true ) ) ) {
			continue;
		}
		$out[] = array( 'id' => $c['id'], 'name' => $c['name'], 'percent' => $life && (int) $c['life'] > 0 ? (int) $c['life'] : (int) $c['percent'] );
	}
	if ( semrs_us_offer_on() ) {
		$out[] = array( 'id' => 'launch', 'name' => __( 'Launch offer', 'semrs-update-server' ), 'percent' => (int) semrs_us_sales()['offer_percent'] );
	}
	$s = semrs_us_aff_settings();
	if ( '' !== (string) $s['pcode'] && (int) $s['pcode_pct'] > 0 ) {
		$out[] = array( 'id' => 'partner', 'name' => __( 'Your partner code', 'semrs-update-server' ), 'percent' => (int) $s['pcode_pct'] );
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $b['percent'] <=> $a['percent'];
		}
	);
	return $out;
}

/**
 * [semrs_aff_calc]: earnings calculator for the affiliate page. Uses the
 * live plan prices, SEMRS offers and program settings, and the same formula
 * as the commission engine: the customer pays the price less the chosen
 * offer; the payment fee comes off what is left; that amount × the rate for
 * the sale's place in its add-on's monthly count (All Pro and lifetime at
 * most the set rate, capped per sale); plus the monthly bonus.
 *
 * @return string
 */
function semrs_us_aff_calc_shortcode() {
	$s     = semrs_us_aff_settings();
	$plans = semrs_us_plans();
	$ids   = array( 'all-1', 'all-3', 'all-5', 'all-u', 'all-life-1', 'all-life-u', 'seo-1', 'speed-1', 'ads-1', 'theme-1' );
	$start = array( 'all-1' => 4, 'all-u' => 1, 'seo-1' => 5 );
	$data  = array(
		'fee'   => array( (float) $s['fee_pct'], (float) $s['fee_flat'] ),
		'tiers' => semrs_us_aff_tiers(),
		'names' => array_merge( array( 'all' => __( 'All Pro and lifetime', 'semrs-update-server' ) ), semrs_us_addons() ),
		't'     => array(
			/* translators: 1: product, 2: first sale, 3: last sale, 4: sales, 5: percent, 6: amount */
			'tier'  => __( '%1$s · sales %2$d–%3$d: %4$d × %5$d%% = %6$s', 'semrs-update-server' ),
			/* translators: 1: product, 2: sales, 3: percent, 4: cap, 5: amount */
			'big'   => __( '%1$s: %2$d sales × %3$d%%, up to %4$s each = %5$s', 'semrs-update-server' ),
			/* translators: 1: sales, 2: amount */
			'bonus' => __( 'Monthly bonus (%1$d sales this month) = %2$s', 'semrs-update-server' ),
			/* translators: %s: amount */
			'total' => __( 'Earned before the fee = %1$s', 'semrs-update-server' ),
			/* translators: 1: percent, 2: amount */
			'fee'   => __( 'Exchange and transfer fee (%1$s%%) = −%2$s', 'semrs-update-server' ),
			/* translators: %s: amount */
			'recv'  => __( 'Total earning this month = %1$s', 'semrs-update-server' ),
		),
		'big'   => array( (int) $s['life_rate'], (float) $s['life_cap'] ),
		'bonus' => array( (float) $s['bonus'], (int) $s['bonus_n'] ),
	);
	// One fee for every payout: a hidden value. Different fees: the partner picks how they are paid.
	if ( (float) $s['send_intl'] === (float) $s['send_pk'] ) {
		$paid = '<input type="hidden" id="semrs-calc-send" value="' . esc_attr( (float) $s['send_intl'] ) . '">';
	} else {
		/* translators: %s: percent */
		$intl = sprintf( __( 'International bank transfer (%s%% exchange and transfer fee)', 'semrs-update-server' ), (float) $s['send_intl'] );
		/* translators: %s: percent */
		$pk   = sprintf( __( 'SadaPay or bank in Pakistan (%s%% exchange and transfer fee)', 'semrs-update-server' ), (float) $s['send_pk'] );
		$paid = '<p class="semrs-calc__disc"><label for="semrs-calc-send">' . esc_html__( 'You are paid by:', 'semrs-update-server' ) . '</label> <select id="semrs-calc-send"><option value="' . esc_attr( (float) $s['send_intl'] ) . '">' . esc_html( $intl ) . '</option><option value="' . esc_attr( (float) $s['send_pk'] ) . '">' . esc_html( $pk ) . '</option></select></p>';
	}
	$all   = array();
	$rows  = '';
	foreach ( $ids as $id ) {
		if ( ! isset( $plans[ $id ] ) ) {
			continue;
		}
		$p    = $plans[ $id ];
		$live = semrs_us_offer_new( $id );
		$opts = '<option value="none" data-pct="0">' . esc_html__( 'No offer', 'semrs-update-server' ) . '</option>';
		foreach ( semrs_us_aff_calc_offers( $id ) as $o ) {
			$all[ $o['id'] ] = $o['name'];
			/* translators: 1: offer, 2: percent */
			$opts .= '<option value="' . esc_attr( $o['id'] ) . '" data-pct="' . (int) $o['percent'] . '"' . selected( $live && $live['id'] === $o['id'], true, false ) . '>' . esc_html( sprintf( __( '%1$s · %2$d%% off', 'semrs-update-server' ), $o['name'], $o['percent'] ) ) . '</option>';
		}
		$label = semrs_us_plan_label( $p );
		$rows .= '<tr data-price="' . esc_attr( $p['price'] ) . '" data-big="' . ( 'all' === $p['addon'] ? 1 : 0 ) . '" data-line="' . esc_attr( $p['addon'] ) . '"><th scope="row">' . esc_html( $label ) . '</th>'
			. '<td data-l="' . esc_attr__( 'Price', 'semrs-update-server' ) . '">' . esc_html( semrs_us_money( $p['price'] ) ) . '</td>'
			. '<td data-l="' . esc_attr__( 'SEMRS offer', 'semrs-update-server' ) . '"><select aria-label="' . esc_attr( sprintf( /* translators: %s: plan */ __( 'SEMRS offer for %s', 'semrs-update-server' ), $label ) ) . '">' . $opts . '</select></td>'
			. '<td data-l="' . esc_attr__( 'Customer pays', 'semrs-update-server' ) . '" data-o="pay"></td>'
			. '<td data-l="' . esc_attr__( 'Sales', 'semrs-update-server' ) . '"><input type="number" min="0" max="500" inputmode="numeric" value="' . (int) ( $start[ $id ] ?? 0 ) . '" aria-label="' . esc_attr( sprintf( /* translators: %s: plan */ __( 'Sales of %s', 'semrs-update-server' ), $label ) ) . '"></td>'
			. '<td data-l="' . esc_attr__( 'You earn', 'semrs-update-server' ) . '" data-o="earn"></td></tr>';
	}
	$every = '<option value="">' . esc_html__( 'Choose…', 'semrs-update-server' ) . '</option><option value="none">' . esc_html__( 'No offer', 'semrs-update-server' ) . '</option>';
	foreach ( $all as $oid => $name ) {
		$every .= '<option value="' . esc_attr( $oid ) . '">' . esc_html( $name ) . '</option>';
	}
	$out  = '<div class="semrs-calc" id="earnings-calculator"><p class="semrs-calc__disc"><label for="semrs-calc-all">' . esc_html__( 'Set every plan to the same SEMRS offer:', 'semrs-update-server' ) . '</label> <select id="semrs-calc-all">' . $every . '</select></p>' . $paid;
	$out .= '<table><caption>' . esc_html__( 'Affiliate earnings calculator: sales in one month', 'semrs-update-server' ) . '</caption><thead><tr><th scope="col">' . esc_html__( 'Plan', 'semrs-update-server' ) . '</th><th scope="col">' . esc_html__( 'Price', 'semrs-update-server' ) . '</th><th scope="col">' . esc_html__( 'SEMRS offer', 'semrs-update-server' ) . '</th><th scope="col">' . esc_html__( 'Customer pays', 'semrs-update-server' ) . '</th><th scope="col">' . esc_html__( 'Sales', 'semrs-update-server' ) . '</th><th scope="col">' . esc_html__( 'You earn', 'semrs-update-server' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table>';
	$out .= '<output class="semrs-calc__out" for="earnings-calculator" aria-live="polite"><span><strong data-k="n">0</strong>' . esc_html__( 'Sales this month', 'semrs-update-server' ) . '</span><span><strong data-k="c">$0</strong>' . esc_html__( 'Commissions', 'semrs-update-server' ) . '</span><span><strong data-k="b">$0</strong>' . esc_html__( 'Monthly bonus', 'semrs-update-server' ) . '</span><span><strong data-k="f">$0</strong>' . esc_html( sprintf( /* translators: %s: percent */ __( 'Exchange and transfer fee (%s%%)', 'semrs-update-server' ), (float) $s['send_intl'] ) ) . '</span><span class="is-total"><strong data-k="r">$0</strong>' . esc_html__( 'Total earning this month', 'semrs-update-server' ) . '</span></output><div class="semrs-calc__bd"><p class="semrs-calc__bdh">' . esc_html__( 'How your month is counted (each product has its own count)', 'semrs-update-server' ) . '</p><ul id="semrs-calc-bd" aria-live="polite"></ul></div>';
	$out .= '<p class="semrs-calc__note"><small>' . esc_html(
		sprintf(
			/* translators: 1: fee percent, 2: flat fee, 3: All Pro rate, 4: cap */
			__( 'Formula: the customer pays the price less the SEMRS offer (one per order). The payment fee (%1$s%% + %2$s) comes off what is left, and you earn your rate on the rest. Each add-on has its own monthly count, and the rate follows each sale\'s place in it; All Pro and lifetime pay at most %3$d%%, up to %4$s a sale. The bonus counts all sales together. The exchange and transfer fee comes off each payout, and the total is shown after it. An estimate: your dashboard shows the real totals.', 'semrs-update-server' ),
			$s['fee_pct'],
			semrs_us_money( $s['fee_flat'] ),
			(int) $s['life_rate'],
			semrs_us_money( $s['life_cap'] )
		)
	) . '</small></p>';
	$out .= '<script>(function(){var D=' . wp_json_encode( $data ) . ',w=document.getElementById("earnings-calculator");if(!w)return;var R=w.querySelectorAll("tbody tr"),A=document.getElementById("semrs-calc-all"),P=document.getElementById("semrs-calc-send");function m(x){return"$"+x.toFixed(2).replace(/\.00$/,"").replace(/\B(?=(\d{3})+(?!\d))/g,",")}function rate(n){var r=D.tiers[0][1];D.tiers.forEach(function(t){if(n>=t[0])r=t[1]});return r}function F(t,a){return t.replace(/%(\d)\$[sd]/g,function(_,i){return a[i-1]}).replace(/%%/g,"%")}function run(){var n=0,c=0,per={},bd={};R.forEach(function(t){var s=t.querySelector("select"),k=+s.options[s.selectedIndex].dataset.pct||0,x=Math.min(500,Math.max(0,Math.floor(+t.querySelector("input").value||0))),p=+t.dataset.price*(100-k)/100,b=Math.max(0,p-(p*D.fee[0]/100+D.fee[1])),big=t.dataset.big==="1",l=t.dataset.line,e=0;for(var j=0;j<x;j++){n++;per[l]=(per[l]||0)+1;var r=rate(per[l]);if(big)r=Math.min(r,D.big[0]);var v=Math.round(b*r)/100;if(big&&D.big[1]>0)v=Math.min(D.big[1],v);e+=v;var k=big?"big":String(r);bd[l]=bd[l]||{};var z=bd[l][k]=bd[l][k]||{n:0,v:0,r:r,a:per[l]};z.n++;z.v+=v;z.b=per[l]}c+=e;t.querySelector("[data-o=pay]").textContent=m(p);t.querySelector("[data-o=earn]").textContent=m(e)});var bo=D.bonus[0]>0&&n>=D.bonus[1]?D.bonus[0]:0,g=c+bo,rc=Math.round(g*(100-(+P.value||0)))/100,fe=Math.max(0,Math.round((g-rc)*100)/100),o={n:String(n),c:m(c),b:m(bo),t:m(g),f:(fe>0?"−":"")+m(fe),r:m(rc)};w.querySelectorAll("[data-k]").forEach(function(e){e.textContent=o[e.dataset.k]});var L=[];Object.keys(D.names).forEach(function(l){if(!bd[l])return;Object.keys(bd[l]).forEach(function(k){var z=bd[l][k];L.push(k==="big"?F(D.t.big,[D.names[l],z.n,z.r,m(D.big[1]),m(z.v)]):F(D.t.tier,[D.names[l],z.a,z.b,z.n,z.r,m(z.v)]))})});if(bo)L.push(F(D.t.bonus,[n,m(bo)]));L.push(F(D.t.total,[m(g)]));if(fe>0)L.push(F(D.t.fee,[+P.value,m(fe)]));L.push(F(D.t.recv,[m(rc)]));var U=document.getElementById("semrs-calc-bd");U.textContent="";L.forEach(function(x,i){var li=document.createElement("li");li.textContent=x;if(i===L.length-1)li.className="is-total";U.appendChild(li)})}A.addEventListener("change",function(){if(!A.value)return;R.forEach(function(t){var s=t.querySelector("select"),o=s.querySelector("option[value=\'"+A.value+"\']");s.value=o?A.value:"none"});run()});w.addEventListener("input",run);w.addEventListener("change",run);run()})();</script>';
	$out .= '<style>.semrs-calc{margin:16px 0 24px;padding:16px;border:2px solid var(--sp-accent,#f7941d);border-radius:16px}.semrs-calc table{width:100%;border-collapse:collapse}.semrs-calc caption{text-align:left;font-weight:700;padding:0 0 8px}.semrs-calc th,.semrs-calc td{padding:6px 8px;border-bottom:1px solid var(--sp-line,#e5e7eb);text-align:left;font-weight:400}.semrs-calc thead th,.semrs-calc [data-o=earn]{font-weight:700}.semrs-calc input,.semrs-calc select{padding:8px;border:1px solid var(--sp-line,#d1d5db);border-radius:8px;font:inherit;max-width:100%}.semrs-calc input{width:5rem}.semrs-calc__disc{margin:0 0 12px}.semrs-calc__disc label{font-weight:600;margin-right:8px}.semrs-calc__out{display:grid;grid-template-columns:repeat(auto-fit,minmax(8rem,1fr));gap:10px;margin:12px 0 8px}.semrs-calc__out span{border:1px solid var(--sp-line,#e5e7eb);border-radius:12px;padding:10px}.semrs-calc__out strong{display:block;font-size:1.35rem}.semrs-calc__bd{margin:8px 0;padding:12px 16px;border-radius:12px;background:var(--sp-accent-soft,#fbf3dc)}.semrs-calc__bdh{margin:0 0 6px;font-weight:700}.semrs-calc__bd ul{margin:0;padding-left:1.2em}.semrs-calc__bd li{margin:2px 0}.semrs-calc__bd li.is-total{font-weight:700}.semrs-calc__out .is-total{background:var(--sp-primary,#0b2fd1);color:#fff;border-color:transparent}@media (max-width:700px){.semrs-calc thead{display:none}.semrs-calc tr{display:grid;grid-template-columns:1fr 1fr;gap:6px 10px;padding:10px 0;border-bottom:1px solid var(--sp-line,#e5e7eb)}.semrs-calc th,.semrs-calc td{border:0;padding:0}.semrs-calc th[scope=row]{grid-column:1/-1;font-weight:700}.semrs-calc td::before{content:attr(data-l);display:block;font-size:.8em;opacity:.75}.semrs-calc td:nth-child(3){grid-column:1/-1}.semrs-calc select{width:100%}}</style></div>';
	return $out;
}
add_shortcode( 'semrs_aff_calc', 'semrs_us_aff_calc_shortcode' );

/**
 * Promo kit shipped with the plugin (assets/promo), built by
 * build-promo-kit.mjs: one entry per design with its size and file URLs.
 *
 * @param string $set Folder: product, cards or affiliate.
 * @return array[] name, w, h, png, webp.
 */
function semrs_us_aff_creatives( $set ) {
	$out = array();
	foreach ( (array) glob( SEMRS_US_DIR . 'assets/promo/' . $set . '/*.png' ) as $file ) {
		$name = basename( $file, '.png' );
		$size = preg_match( '/-(\d+)x(\d+)$/', $name, $m ) ? array( (int) $m[1], (int) $m[2] ) : ( 'semrs-affiliate-partner-badge' === $name ? array( 400, 400 ) : array( 1080, 1080 ) );
		$url  = SEMRS_US_URL . 'assets/promo/' . $set . '/' . $name;
		$out[] = array( 'name' => $name, 'w' => $size[0], 'h' => $size[1], 'png' => $url . '.png', 'webp' => $url . '.webp' );
	}
	return $out;
}

/**
 * Dashboard section: banners with ready-made HTML that carries the
 * partner's link, plus cards and images for social posts.
 *
 * @param string $code Partner code.
 * @return string
 */
function semrs_us_aff_creatives_html( $code ) {
	$pricing = semrs_us_aff_link( $code, strtok( semrs_us_pricing_url(), '#' ) );
	$home    = semrs_us_aff_link( $code );
	$alt     = __( 'SEMRS Pro: SEO, speed and ads for WordPress in one key', 'semrs-update-server' );
	$social  = array( 'social-post', 'square', 'story', 'video-thumbnail', 'x-header' );
	$groups  = array( 'web' => array(), 'social' => array(), 'cards' => semrs_us_aff_creatives( 'cards' ) );
	foreach ( semrs_us_aff_creatives( 'product' ) as $c ) {
		$is_social = (bool) array_filter( $social, function ( $k ) use ( $c ) { return false !== strpos( $c['name'], '-' . $k . '-' ); } );
		$groups[ $is_social ? 'social' : 'web' ][] = $c;
	}
	$item = function ( $c, $href, $alt, $code_box ) {
		$scale = min( 1, 280 / $c['w'], 220 / $c['h'] );
		$html  = '<a href="' . esc_url( $href ) . '" rel="sponsored noopener"><img src="' . esc_url( $c['png'] ) . '" width="' . $c['w'] . '" height="' . $c['h'] . '" alt="' . esc_attr( $alt ) . '"></a>';
		$out   = '<figure class="semrs-aff__cr"><img src="' . esc_url( $c['webp'] ) . '" width="' . round( $c['w'] * $scale ) . '" height="' . round( $c['h'] * $scale ) . '" alt="" loading="lazy" decoding="async"><figcaption>' . esc_html( $c['w'] . '×' . $c['h'] ) . ' · <a href="' . esc_url( $c['png'] ) . '" download>' . esc_html__( 'Download PNG', 'semrs-update-server' ) . '</a></figcaption>';
		if ( $code_box ) {
			$out .= '<textarea readonly rows="3" onclick="this.select()" aria-label="' . esc_attr__( 'HTML code with your link', 'semrs-update-server' ) . '">' . esc_textarea( $html ) . '</textarea>';
		}
		return $out . '</figure>';
	};
	$out  = '<h3 id="creatives">' . esc_html__( 'Banners and creatives', 'semrs-update-server' ) . '</h3>';
	$out .= '<p>' . esc_html__( 'Copy the code under a banner into your site: it already carries your link and is marked rel="sponsored". Images for social posts and videos are for download. Please say you are an affiliate wherever you use them.', 'semrs-update-server' ) . '</p>';
	$out .= '<details open><summary>' . esc_html__( 'Website banners', 'semrs-update-server' ) . '</summary><div class="semrs-aff__crs">';
	foreach ( $groups['web'] as $c ) {
		$out .= $item( $c, $pricing, $alt, true );
	}
	$out .= '</div></details><details><summary>' . esc_html__( 'Social posts, stories and video thumbnails', 'semrs-update-server' ) . '</summary><div class="semrs-aff__crs">';
	foreach ( $groups['social'] as $c ) {
		$out .= $item( $c, $pricing, $alt, false );
	}
	$out .= '</div></details><details><summary>' . esc_html__( 'Add-on cards and partner badge', 'semrs-update-server' ) . '</summary><div class="semrs-aff__crs">';
	foreach ( $groups['cards'] as $c ) {
		$badge = 'semrs-affiliate-partner-badge' === $c['name'];
		$out  .= $item( $c, $badge ? $home : $pricing, $badge ? __( 'SEMRS affiliate partner', 'semrs-update-server' ) : $alt, true );
	}
	$out .= '</div></details>';
	return $out . '<style>.semrs-aff__crs{display:flex;flex-wrap:wrap;gap:16px;margin:12px 0 20px}.semrs-aff__cr{margin:0;width:300px;max-width:100%}.semrs-aff__cr img{display:block;max-width:100%;height:auto;border-radius:8px}.semrs-aff__cr figcaption{font-size:.9em;margin:6px 0}.semrs-aff__cr textarea{width:100%;font:12px/1.4 ui-monospace,monospace;padding:6px;border:1px solid var(--sp-line,#d1d5db);border-radius:6px}.semrs-aff details summary{cursor:pointer;font-weight:700;margin:8px 0}</style>';
}

/**
 * A light affiliate banner under single blog posts (setting: post_cta).
 *
 * @param string $content Post content.
 * @return string
 */
function semrs_us_aff_post_cta( $content ) {
	$s = semrs_us_aff_settings();
	if ( ! $s['on'] || empty( $s['post_cta'] ) || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! semrs_us_aff_page_url() ) {
		return $content;
	}
	$base = SEMRS_US_URL . 'assets/promo/affiliate/semrs-affiliate-';
	/* translators: %d: top percent */
	$alt  = sprintf( __( 'SEMRS affiliate program: earn up to %d%% recommending SEMRS', 'semrs-update-server' ), semrs_us_aff_top_rate() );
	return $content . '<aside class="semrs-aff-cta" style="margin:32px 0 0"><a href="' . esc_url( semrs_us_aff_page_url() ) . '"><picture><source media="(min-width:768px)" srcset="' . esc_url( $base . 'leaderboard-728x90.webp' ) . '" width="728" height="90"><img src="' . esc_url( $base . 'mobile-banner-320x100.webp' ) . '" width="320" height="100" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" style="display:block;max-width:100%;height:auto;margin:0 auto;border-radius:10px"></picture></a></aside>';
}
add_filter( 'the_content', 'semrs_us_aff_post_cta', 20 );
