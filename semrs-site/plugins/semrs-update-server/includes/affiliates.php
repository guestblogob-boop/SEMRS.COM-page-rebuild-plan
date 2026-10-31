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
 *   themselves. Payouts are made by hand (PayPal, Wise, bank) and marked paid.
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
			'first'   => 50,
			'renewal' => 30,
			'cookie'  => 90,
			'min'     => 50,
			'hold'    => 30,
			'page'    => 0,
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
	wp_safe_redirect( remove_query_arg( 'ref' ), 302 );
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
	$rows  = semrs_us_referrals();
	$first = ! array_filter(
		$rows,
		function ( $r ) use ( $key ) {
			return $r['key'] === semrs_us_kid( $key ) && 'rejected' !== $r['status'];
		}
	);
	$s     = semrs_us_aff_settings();
	$rate  = $first ? (int) $s['first'] : (int) $s['renewal'];
	array_unshift(
		$rows,
		array(
			'id'     => substr( md5( $ref . microtime() ), 0, 10 ),
			'aff'    => $code,
			'ref'    => $ref,
			'key'    => semrs_us_kid( $key ),
			'plan'   => (string) ( $src['plan'] ?? '' ),
			'amount' => round( $amount, 2 ),
			'rate'   => $rate,
			'com'    => round( $amount * $rate / 100, 2 ),
			'kind'   => $first ? 'first' : 'renewal',
			'status' => 'pending',
			'date'   => gmdate( 'Y-m-d' ),
			'paid'   => '',
		)
	);
	semrs_us_referrals_save( array_slice( $rows, 0, 5000 ) );
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
	foreach ( $rows as &$r ) {
		if ( $r['ref'] === $ref && 'paid' !== $r['status'] ) {
			$r['status'] = 'rejected';
		}
	}
	unset( $r );
	semrs_us_referrals_save( $rows );
}

/**
 * Daily: commissions past the refund window become approved.
 */
function semrs_us_aff_approve_due() {
	$hold = gmdate( 'Y-m-d', strtotime( '-' . (int) semrs_us_aff_settings()['hold'] . ' days' ) );
	$rows = semrs_us_referrals();
	$done = false;
	foreach ( $rows as &$r ) {
		if ( 'pending' === $r['status'] && $r['date'] <= $hold ) {
			$r['status'] = 'approved';
			$done        = true;
		}
	}
	unset( $r );
	if ( $done ) {
		semrs_us_referrals_save( $rows );
	}
}
add_action( 'semrs_us_daily', 'semrs_us_aff_approve_due' );

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
		$t['sales']++;
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
				sprintf( __( 'You earn %1$d%% of the first order and %2$d%% of every renewal and add-on after it, for life. Cookie: %3$d days.', 'semrs-update-server' ), $s['first'], $s['renewal'], $s['cookie'] ),
				/* translators: 1: days, 2: minimum */
				sprintf( __( 'Commissions are approved %1$d days after the sale (the refund window) and paid monthly once you reach %2$s.', 'semrs-update-server' ), $s['hold'], semrs_us_money( $s['min'] ) ),
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
	$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$code = semrs_us_aff_new_code( $name ? $name : strtok( $email, '@' ) );
	semrs_us_aff_save(
		$code,
		array(
			'name'    => $name,
			'email'   => $email,
			'site'    => esc_url_raw( wp_unslash( $_POST['site'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'how'     => sanitize_textarea_field( wp_unslash( $_POST['how'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'payout'  => sanitize_text_field( wp_unslash( $_POST['payout'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification
			'status'  => 'pending',
			'token'   => bin2hex( random_bytes( 16 ) ),
			'created' => gmdate( 'Y-m-d' ),
		)
	);
	$admin = sanitize_email( semrs_us_sales()['seller_email'] );
	if ( $admin ) {
		/* translators: %s: name */
		semrs_us_mail( $admin, sprintf( __( 'New affiliate application: %s', 'semrs-update-server' ), $name ), $name . ' <' . $email . ">\n" . esc_url_raw( wp_unslash( $_POST['site'] ?? '' ) ) . "\n\n" . admin_url( 'admin.php?page=semrs-updates-affiliates' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	semrs_us_mail( $email, __( 'We got your SEMRS affiliate application', 'semrs-update-server' ), __( 'Thanks for applying. We review every application by hand, usually within 2 business days, and email you your link when you are approved.', 'semrs-update-server' ) . "\n\n" . semrs_us_sales()['seller_name'] );
	wp_safe_redirect( add_query_arg( 'aff_applied', 1, $back ) );
	exit;
}
add_action( 'init', 'semrs_us_aff_post' );

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
		$out .= '<p class="semrs-aff__note" role="alert">' . esc_html__( 'Please fill in your email and accept the terms.', 'semrs-update-server' ) . '</p>';
	}
	$rows = array(
		array( __( 'First order', 'semrs-update-server' ), $s['first'] . '%' ),
		array( __( 'Every renewal and add-on, for life', 'semrs-update-server' ), $s['renewal'] . '%' ),
		array( __( 'Cookie length', 'semrs-update-server' ), sprintf( /* translators: %d: days */ _n( '%d day', '%d days', (int) $s['cookie'], 'semrs-update-server' ), (int) $s['cookie'] ) ),
		array( __( 'Approval', 'semrs-update-server' ), sprintf( /* translators: %d: days */ __( '%d days after the sale (refund window)', 'semrs-update-server' ), (int) $s['hold'] ) ),
		array( __( 'Payouts', 'semrs-update-server' ), sprintf( /* translators: %s: amount */ __( 'Monthly by PayPal, Wise or bank, from %s', 'semrs-update-server' ), semrs_us_money( $s['min'] ) ) ),
	);
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
		. $f( 'site', __( 'Website or channel', 'semrs-update-server' ), '<input id="semrs-aff-site" type="url" name="site" placeholder="https://" autocomplete="url">' )
		. $f( 'how', __( 'How will you share SEMRS?', 'semrs-update-server' ), '<textarea id="semrs-aff-how" name="how" rows="3"></textarea>' )
		. $f( 'payout', __( 'PayPal or Wise email for payouts', 'semrs-update-server' ), '<input id="semrs-aff-payout" name="payout">' )
		. '<p class="semrs-aff__hp" aria-hidden="true"><label>Website URL <input name="website_url" tabindex="-1" autocomplete="off"></label></p>'
		. '<p><label><input type="checkbox" name="terms" value="1" required> ' . esc_html__( 'I will say I am an affiliate where I share my link, and I will not bid on SEMRS brand terms in ads or use spam.', 'semrs-update-server' ) . '</label></p>'
		. '<p><button class="semrs-btn semrs-btn--buy" type="submit">' . esc_html__( 'Send application', 'semrs-update-server' ) . '</button></p></form>';
	$out .= '<form method="post" action="' . $url . '" class="semrs-aff__form semrs-aff__form--small"><input type="hidden" name="semrs_aff" value="link">' . $f( 'link-email', __( 'Already an affiliate? Get your links and dashboard by email', 'semrs-update-server' ), '<span class="semrs-aff__row"><input id="semrs-aff-link-email" type="email" name="email" required autocomplete="email"> <button class="semrs-btn" type="submit">' . esc_html__( 'Email my links', 'semrs-update-server' ) . '</button></span>' ) . '</form>';
	$out .= '<style>.semrs-aff__rates{width:100%;border-collapse:collapse;margin:0 0 24px}.semrs-aff__rates caption{text-align:left;font-weight:700;padding:0 0 8px}.semrs-aff__rates th,.semrs-aff__rates td{padding:10px 12px;border-bottom:1px solid var(--sp-line,#e5e7eb);text-align:left}.semrs-aff__rates td{font-weight:700}.semrs-aff__form{max-width:36rem;margin:0 0 24px}.semrs-aff__form label{display:block;font-weight:600;margin:0 0 4px}.semrs-aff__form input:not([type=checkbox]),.semrs-aff__form textarea{width:100%;padding:10px 12px;border:1px solid var(--sp-line,#d1d5db);border-radius:8px;font:inherit}.semrs-aff__form input[type=checkbox]{margin-right:6px}.semrs-aff__row{display:flex;gap:8px}.semrs-aff__hp{position:absolute;left:-9999px}.semrs-aff__note{border:2px solid var(--sp-accent,#d4a72c);border-radius:12px;padding:12px 16px}.semrs-aff__stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));gap:12px;margin:0 0 24px;padding:0;list-style:none}.semrs-aff__stats li{border:1px solid var(--sp-line,#e5e7eb);border-radius:12px;padding:12px}.semrs-aff__stats strong{display:block;font-size:1.4rem}</style></div>';
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
	$out .= '<ul class="semrs-aff__stats">';
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
		$out .= '<tr><td>' . esc_html( $r['date'] ) . '</td><td>' . esc_html( $r['plan'] . ' (' . ( 'first' === $r['kind'] ? __( 'first order', 'semrs-update-server' ) : __( 'renewal', 'semrs-update-server' ) ) . ')' ) . '</td><td>' . esc_html( semrs_us_money( $r['com'] ) . ' (' . $r['rate'] . '%)' ) . '</td><td>' . esc_html( ucfirst( $r['status'] ) ) . '</td></tr>';
	}
	if ( ! $n ) {
		$out .= '<tr><td colspan="4">' . esc_html__( 'No sales yet. Share your link to get started.', 'semrs-update-server' ) . '</td></tr>';
	}
	return $out . '</tbody></table></div>';
}
