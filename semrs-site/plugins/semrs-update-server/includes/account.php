<?php
/**
 * [semrs_my_licenses]: customers manage their keys without an account.
 *
 * They enter the email they bought with and get a sign-in link (valid 24
 * hours). The page then lists each key with its add-ons, sites and expiry,
 * and lets them free a site slot, download SEMRS Pro, add add-ons to the
 * same key, open Lemon Squeezy billing, or ask for a renewal invoice.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email for a valid sign-in token, or ''.
 *
 * @param string $token Token.
 * @return string
 */
function semrs_us_ml_email( $token ) {
	$token = preg_replace( '/[^a-f0-9]/', '', (string) $token );
	if ( 40 !== strlen( $token ) ) {
		return '';
	}
	return (string) get_transient( 'semrs_ml_' . hash( 'sha256', $token ) );
}

/**
 * Send a sign-in link (silently does nothing for unknown emails, so the
 * form never reveals who is a customer). Limited to one per email per
 * 2 minutes and 10 per visitor per hour.
 *
 * @param string $email Email.
 */
function semrs_us_ml_send( $email ) {
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate = 'semrs_ml_ip_' . md5( $ip );
	$hits = (int) get_transient( $rate );
	if ( $hits >= 10 || get_transient( 'semrs_ml_em_' . md5( strtolower( $email ) ) ) ) {
		return;
	}
	set_transient( $rate, $hits + 1, HOUR_IN_SECONDS );
	if ( ! semrs_us_keys_for_email( $email ) ) {
		return;
	}
	set_transient( 'semrs_ml_em_' . md5( strtolower( $email ) ), 1, 2 * MINUTE_IN_SECONDS );
	$token = bin2hex( random_bytes( 20 ) );
	set_transient( 'semrs_ml_' . hash( 'sha256', $token ), strtolower( $email ), DAY_IN_SECONDS );
	semrs_us_mail(
		$email,
		__( 'Your SEMRS licenses sign-in link', 'semrs-update-server' ),
		implode(
			"\n",
			array(
				__( 'Open this link to see and manage your SEMRS Pro licenses. It works for 24 hours.', 'semrs-update-server' ),
				'',
				add_query_arg( 'ml', $token, semrs_us_account_url() ),
				'',
				__( 'If you did not ask for this, you can ignore this email.', 'semrs-update-server' ),
				'',
				semrs_us_sales()['seller_name'],
			)
		)
	);
}

/**
 * Handle the page's forms before output (redirects and downloads).
 */
function semrs_us_ml_actions() {
	if ( empty( $_POST['semrs_ml'] ) ) {
		return;
	}
	$do   = sanitize_key( wp_unslash( $_POST['semrs_ml'] ) );
	$back = semrs_us_account_url();
	// The email form needs no nonce (a cached page could hold an old one): it is
	// rate limited and only ever emails the address's owner.
	if ( 'link' !== $do && ( ! isset( $_POST['_semrs_ml'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_semrs_ml'] ) ), 'semrs_ml' ) ) ) {
		return;
	}
	if ( 'link' === $do ) {
		semrs_us_ml_send( isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '' );
		wp_safe_redirect( add_query_arg( 'sent', 1, $back ) );
		exit;
	}
	$token = isset( $_POST['ml'] ) ? sanitize_text_field( wp_unslash( $_POST['ml'] ) ) : '';
	$email = semrs_us_ml_email( $token );
	$kid   = isset( $_POST['kid'] ) ? sanitize_text_field( wp_unslash( $_POST['kid'] ) ) : '';
	$key   = semrs_us_key_by_kid( $kid );
	$keys  = semrs_us_keys_for_email( $email );
	if ( ! $email || ! $key || ! isset( $keys[ $key ] ) ) {
		wp_safe_redirect( $back );
		exit;
	}
	$back = add_query_arg( 'ml', $token, $back );
	if ( 'download' === $do ) {
		$url = semrs_us_pro_download_url( $key );
		wp_redirect( $url ? $url : $back ); // phpcs:ignore WordPress.Security.SafeRedirect -- same site REST URL.
		exit;
	}
	if ( 'free' === $do && isset( $_POST['host'] ) ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}semrs_sites SET license_key = '' WHERE slug = 'semrs-pro' AND host = %s AND license_key = %s", sanitize_text_field( wp_unslash( $_POST['host'] ) ), $key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_safe_redirect( add_query_arg( 'done', 'free', $back ) );
		exit;
	}
	if ( 'renew' === $do ) {
		$k = $keys[ $key ];
		semrs_us_mail(
			semrs_us_sales()['seller_email'],
			__( 'Renewal invoice requested', 'semrs-update-server' ),
			implode(
				"\n",
				array(
					/* translators: %s: email */
					sprintf( __( '%s asked for a renewal invoice.', 'semrs-update-server' ), $k['email'] ),
					__( 'Add-ons:', 'semrs-update-server' ) . ' ' . semrs_us_addon_names( $k['addons'] ),
					__( 'Expires:', 'semrs-update-server' ) . ' ' . ( '' === $k['expires'] ? 'never' : $k['expires'] ),
					'',
					__( 'Create it here (key filled in):', 'semrs-update-server' ),
					add_query_arg( array( 'page' => 'semrs-updates-invoices', 'key' => $key ), admin_url( 'admin.php' ) ),
				)
			)
		);
		wp_safe_redirect( add_query_arg( 'done', 'renew', $back ) );
		exit;
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'semrs_us_ml_actions', 1 );

/**
 * Shortcode output.
 *
 * @return string
 */
function semrs_us_my_licenses() {
	$token = isset( $_GET['ml'] ) ? sanitize_text_field( wp_unslash( $_GET['ml'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$email = semrs_us_ml_email( $token );
	$url   = esc_url( semrs_us_account_url() );
	$out   = semrs_us_ml_css() . '<div class="semrs-ml">';
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // Page caches skip this page.
	}

	if ( ! $email ) {
		$nonce = '';
		if ( isset( $_GET['sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$out .= '<p class="semrs-ml__note" role="status">' . esc_html__( 'If that email has SEMRS licenses, a sign-in link is on its way. Check your inbox (and spam folder).', 'semrs-update-server' ) . '</p>';
		} elseif ( $token ) {
			$out .= '<p class="semrs-ml__note" role="status">' . esc_html__( 'That sign-in link has expired. Ask for a new one below.', 'semrs-update-server' ) . '</p>';
		}
		$out .= '<form method="post" action="' . $url . '" class="semrs-ml__login">' . $nonce . '<input type="hidden" name="semrs_ml" value="link"><label for="semrs-ml-email">' . esc_html__( 'Email you used to buy', 'semrs-update-server' ) . '</label><div class="semrs-ml__row"><input id="semrs-ml-email" type="email" name="email" required autocomplete="email"><button class="semrs-btn" type="submit">' . esc_html__( 'Email me a sign-in link', 'semrs-update-server' ) . '</button></div><p class="semrs-ml__muted">' . esc_html__( 'No password needed. The link works for 24 hours.', 'semrs-update-server' ) . '</p></form></div>';
		return $out;
	}

	$done = isset( $_GET['done'] ) ? sanitize_key( $_GET['done'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'free' === $done ) {
		$out .= '<p class="semrs-ml__note" role="status">' . esc_html__( 'Site slot freed. You can now activate the key on another site.', 'semrs-update-server' ) . '</p>';
	} elseif ( 'renew' === $done ) {
		$out .= '<p class="semrs-ml__note" role="status">' . esc_html( __( 'Thanks! We will email you a renewal invoice within one business day.', 'semrs-update-server' ) . ( semrs_us_renew_offer() ? ' ' . sprintf( /* translators: %d: percent */ __( 'Your %d%% loyalty discount is included.', 'semrs-update-server' ), semrs_us_renew_offer()['percent'] ) : '' ) ) . '</p>';
	}

	global $wpdb;
	$nonce = wp_nonce_field( 'semrs_ml', '_semrs_ml', false, false );
	$plans = semrs_us_plans();
	$names = semrs_us_addons();
	$hid   = function ( $do, $kid, $extra = '' ) use ( $nonce, $token ) {
		return $nonce . '<input type="hidden" name="semrs_ml" value="' . esc_attr( $do ) . '"><input type="hidden" name="ml" value="' . esc_attr( $token ) . '"><input type="hidden" name="kid" value="' . esc_attr( $kid ) . '">' . $extra;
	};
	foreach ( semrs_us_keys_for_email( $email ) as $key => $k ) {
		$kid    = semrs_us_kid( $key );
		$owned  = semrs_us_key_addons( $k );
		$tier   = 0 === (int) $k['sites'] ? 'u' : ( (int) $k['sites'] > 3 ? '5' : ( (int) $k['sites'] > 1 ? '3' : '1' ) );
		$hosts  = $wpdb->get_col( $wpdb->prepare( "SELECT host FROM {$wpdb->prefix}semrs_sites WHERE slug = 'semrs-pro' AND license_key = %s AND last_seen > %s ORDER BY last_seen DESC", $key, gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$expired = '' !== $k['expires'] && $k['expires'] < gmdate( 'Y-m-d' );
		$portal  = '';
		foreach ( (array) ( $k['sources'] ?? array() ) as $src ) {
			$portal = ! empty( $src['portal'] ) ? $src['portal'] : $portal;
		}

		$out .= '<section class="semrs-ml__key"><div class="semrs-ml__head"><code class="semrs-ml__code">' . esc_html( $key ) . '</code>';
		$out .= 'active' !== $k['status'] ? '<span class="semrs-ml__tag is-off">' . esc_html__( 'Revoked', 'semrs-update-server' ) . '</span>' : ( $expired ? '<span class="semrs-ml__tag is-warn">' . esc_html__( 'Updates expired', 'semrs-update-server' ) . '</span>' : '<span class="semrs-ml__tag">' . esc_html__( 'Active', 'semrs-update-server' ) . '</span>' );
		$out .= '</div><dl class="semrs-ml__facts">';
		$out .= '<dt>' . esc_html__( 'Websites', 'semrs-update-server' ) . '</dt><dd>' . esc_html( count( $hosts ) . ' / ' . ( $k['sites'] ? $k['sites'] : '∞' ) ) . ' <span class="semrs-ml__muted">' . esc_html__( '(local and staging sites are free)', 'semrs-update-server' ) . '</span></dd>';
		$out .= '<dt>' . esc_html__( 'Updates and support', 'semrs-update-server' ) . '</dt><dd>' . esc_html( '' === $k['expires'] ? __( 'Lifetime', 'semrs-update-server' ) : sprintf( /* translators: %s: date */ __( 'until %s', 'semrs-update-server' ), $k['expires'] ) ) . '</dd></dl>';

		$out .= '<ul class="semrs-ml__addons">';
		foreach ( $names as $slug => $label ) {
			if ( in_array( $slug, $owned, true ) ) {
				$out .= '<li class="is-on"><span aria-hidden="true">✔</span> ' . esc_html( $label ) . '</li>';
			} else {
				$p    = $plans[ $slug . '-' . $tier ];
				$out .= '<li><span aria-hidden="true">🔒</span> ' . esc_html( $label ) . ' <a class="semrs-ml__add" href="' . esc_url( semrs_us_buy_url( $p, $kid ) ) . '" rel="nofollow">' . esc_html( sprintf( /* translators: %s: price */ __( 'Add for %s/yr', 'semrs-update-server' ), semrs_us_money( $p['price'] ) ) ) . '</a></li>';
			}
		}
		$out .= '</ul>';
		if ( count( $owned ) > 1 && count( $owned ) < count( $names ) ) {
			$out .= '<p class="semrs-ml__muted">' . esc_html__( 'Need two or more add-ons? All Pro costs less than three separate add-ons. With a Lemon Squeezy subscription, switch plans under Manage billing and pay only the difference.', 'semrs-update-server' ) . '</p>';
		}

		if ( $hosts ) {
			$out .= '<h3 class="semrs-ml__h">' . esc_html__( 'Sites using this key', 'semrs-update-server' ) . '</h3><ul class="semrs-ml__sites">';
			foreach ( $hosts as $h ) {
				$out .= '<li><span>' . esc_html( $h ) . '</span><form method="post" action="' . $url . '">' . $hid( 'free', $kid, '<input type="hidden" name="host" value="' . esc_attr( $h ) . '">' ) . '<button class="semrs-ml__link" type="submit">' . esc_html__( 'Free this slot', 'semrs-update-server' ) . '</button></form></li>';
			}
			$out .= '</ul>';
		}

		$out .= '<div class="semrs-ml__actions">';
		if ( 'active' === $k['status'] ) {
			$out .= '<form method="post" action="' . $url . '">' . $hid( 'download', $kid ) . '<button class="semrs-btn" type="submit">' . esc_html__( 'Download SEMRS Pro', 'semrs-update-server' ) . '</button></form>';
		}
		if ( $portal ) {
			$out .= '<a class="semrs-btn semrs-btn--ghost" href="' . esc_url( $portal ) . '" rel="nofollow noopener" target="_blank">' . esc_html__( 'Manage billing', 'semrs-update-server' ) . '</a>';
		} elseif ( '' !== $k['expires'] ) {
			// One plan covers the key: offer it as an online renewal (it extends this key).
			$renew = count( $owned ) === count( $names ) ? 'all-' . $tier : ( 1 === count( $owned ) ? $owned[0] . '-' . $tier : '' );
			if ( $renew && '' !== $plans[ $renew ]['checkout'] ) {
				$out .= '<a class="semrs-btn semrs-btn--ghost" href="' . esc_url( semrs_us_buy_url( $plans[ $renew ], $kid ) ) . '" rel="nofollow">' . esc_html( sprintf( /* translators: %s: price */ __( 'Renew online (%s/yr)', 'semrs-update-server' ), semrs_us_money( semrs_us_renew_offer() ? round( $plans[ $renew ]['price'] * ( 100 - semrs_us_renew_offer()['percent'] ) / 100, 2 ) : $plans[ $renew ]['price'] ) ) ) . '</a>';
			}
			$out .= '<form method="post" action="' . $url . '">' . $hid( 'renew', $kid ) . '<button class="semrs-btn semrs-btn--ghost" type="submit">' . esc_html__( 'Request a renewal invoice', 'semrs-update-server' ) . '</button></form>';
		}
		$out .= '</div></section>';
	}
	$out .= '<p class="semrs-ml__muted">' . esc_html__( 'Activate a key under SEMRS → License in your WordPress admin. Add-ons you buy later switch on by themselves within 24 hours, or right away with Check again.', 'semrs-update-server' ) . '</p></div>';
	return $out;
}
add_shortcode( 'semrs_my_licenses', 'semrs_us_my_licenses' );

/**
 * Page styles (only where the shortcode is used; theme tokens with fallbacks).
 *
 * @return string
 */
function semrs_us_ml_css() {
	return '<style>.semrs-ml{max-width:760px;margin:0 auto}.semrs-ml__note{background:var(--sp-accent-soft,#eef3ff);border-radius:10px;padding:12px 16px}.semrs-ml__muted{color:var(--sp-muted,#5b6575);font-size:.92rem}.semrs-ml__login label{display:block;font-weight:600;margin-bottom:6px}.semrs-ml__row{display:flex;flex-wrap:wrap;gap:10px}.semrs-ml__row input{flex:1 1 240px;min-height:48px;padding:0 14px;border:1px solid var(--sp-line,#d0d6e0);border-radius:10px;font:inherit;background:var(--sp-surface,#fff);color:inherit}.semrs-ml__key{border:1px solid var(--sp-line,#e3e7ee);border-radius:14px;padding:20px;margin:0 0 18px;background:var(--sp-surface,#fff)}.semrs-ml__head{display:flex;flex-wrap:wrap;align-items:center;gap:10px;justify-content:space-between}.semrs-ml__code{font-size:1.05rem;word-break:break-all}.semrs-ml__tag{font-size:.8rem;font-weight:700;padding:3px 10px;border-radius:999px;background:#0f7b4d;color:#fff}.semrs-ml__tag.is-warn{background:#8a5a00}.semrs-ml__tag.is-off{background:#b42318}.semrs-ml__facts{display:grid;grid-template-columns:auto 1fr;gap:4px 14px;margin:14px 0}.semrs-ml__facts dt{font-weight:600}.semrs-ml__facts dd{margin:0}.semrs-ml__addons{list-style:none;padding:0;margin:0 0 12px;display:grid;gap:6px}.semrs-ml__addons li.is-on{font-weight:600}.semrs-ml__add{margin-left:6px;font-weight:600}.semrs-ml__h{font-size:1rem;margin:16px 0 6px}.semrs-ml__sites{list-style:none;padding:0;margin:0}.semrs-ml__sites li{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--sp-line,#e3e7ee)}.semrs-ml__link{background:none;border:0;padding:0;color:var(--sp-link,var(--sp-primary,#1f4fd1));text-decoration:underline;cursor:pointer;font:inherit}.semrs-ml__actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}.semrs-ml__actions form{margin:0}.semrs-ml .semrs-btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 20px;border-radius:10px;border:2px solid var(--sp-primary,#0b1f3a);background:var(--sp-primary,#0b1f3a);color:#fff;font:inherit;font-weight:600;text-decoration:none;cursor:pointer}.semrs-ml .semrs-btn--ghost{background:transparent;color:inherit;border-color:var(--sp-line,#d0d6e0)}.semrs-ml .semrs-btn:focus-visible,.semrs-ml__link:focus-visible{outline:3px solid var(--sp-accent,#d4a72c);outline-offset:2px}</style>';
}
