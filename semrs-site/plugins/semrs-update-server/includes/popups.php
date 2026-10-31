<?php
/**
 * Sales popups on semrs.com: one small inline engine (about 4 KB, no files,
 * no libraries) for the campaign popup, the sale bar with its countdown, and
 * the welcome, agency, switch, lifetime, social-proof and affiliate slide-ins.
 *
 * Rules it keeps (Google, AdSense and PageSpeed):
 * - Nothing opens on page load. The script starts when the browser is idle,
 *   popups wait for a delay, a scroll depth or exit intent.
 * - Phones never get a full-screen popup: a modal opens as a short bottom
 *   sheet, and only from the second page of a visit.
 * - Pages with ads only get the sale popup on exit intent (desktop), never a
 *   bar or slide-in that could sit on an ad. Ads are never put in popups.
 * - Popups are fixed-position and added after load: no layout shift.
 * - One popup per visit (the sale bar can show next to it), Esc and a close
 *   button on every one, and a dismissed popup stays away for its set days.
 * - Popup text lives in <template> tags, so it is not read as page content.
 * - Countdowns run to the real end date. No fake timers or "only 3 left".
 *
 * Page caching: each item carries its own start and end time and the browser
 * checks them, so a cached page switches on time; the cache is also cleared
 * when a sale starts or ends (see campaigns.php).
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * The popups and their editable settings (SEMRS Updates → Campaigns & popups
 * → Popups). Empty title or text on the sale, welcome, agency and switch
 * popups uses the campaign's headline and text. Delay and scroll 0 = off;
 * days = how long a closed popup stays away.
 *
 * @return array<string, array>
 */
function semrs_us_popup_defaults() {
	return array(
		'sale'      => array( 'name' => __( 'Sale popup', 'semrs-update-server' ), 'title' => '', 'text' => '', 'cta' => __( 'See sale prices', 'semrs-update-server' ), 'delay' => 12, 'scroll' => 35, 'days' => 2 ),
		'bar'       => array( 'name' => __( 'Sale bar', 'semrs-update-server' ), 'title' => '', 'text' => '', 'cta' => __( 'Shop the sale', 'semrs-update-server' ), 'delay' => 2, 'scroll' => 0, 'days' => 0 ),
		'welcome'   => array( 'name' => __( 'Welcome offer', 'semrs-update-server' ), 'title' => '', 'text' => '', 'cta' => __( 'See SEMRS Pro plans', 'semrs-update-server' ), 'delay' => 45, 'scroll' => 0, 'days' => 30 ),
		'agency'    => array( 'name' => __( 'Agency deal', 'semrs-update-server' ), 'title' => '', 'text' => '', 'cta' => __( 'See agency prices', 'semrs-update-server' ), 'delay' => 0, 'scroll' => 60, 'days' => 14 ),
		'switch'    => array( 'name' => __( 'Switch deal', 'semrs-update-server' ), 'title' => '', 'text' => '', 'cta' => __( 'Compare plans', 'semrs-update-server' ), 'delay' => 25, 'scroll' => 0, 'days' => 21 ),
		'lifetime'  => array( 'name' => __( 'Lifetime nudge', 'semrs-update-server' ), 'title' => __( 'Pay once, never renew', 'semrs-update-server' ), 'text' => __( 'All Pro for unlimited websites with lifetime updates: %s once.', 'semrs-update-server' ), 'cta' => __( 'Get lifetime access', 'semrs-update-server' ), 'delay' => 0, 'scroll' => 70, 'days' => 14 ),
		'proof'     => array( 'name' => __( 'Social proof', 'semrs-update-server' ), 'title' => '', 'text' => __( '%s websites ran SEMRS in the last 30 days.', 'semrs-update-server' ), 'cta' => '', 'delay' => 6, 'scroll' => 0, 'days' => 7 ),
		'affiliate' => array( 'name' => __( 'Affiliate invite', 'semrs-update-server' ), 'title' => __( 'Earn %d%% recommending SEMRS', 'semrs-update-server' ), 'text' => __( 'Plus %1$d%% of every renewal for life, with a %2$d-day cookie.', 'semrs-update-server' ), 'cta' => __( 'Join the affiliate program', 'semrs-update-server' ), 'delay' => 20, 'scroll' => 0, 'days' => 30 ),
	);
}

/**
 * Default photos per popup (files in assets/popups/). Each popup shows one
 * of its photos at random; they load only when the popup opens.
 *
 * @return array<string, string> popup ID => space-separated files or URLs.
 */
function semrs_us_popup_default_images() {
	return array(
		'sale'      => 'sale.webp sale-2.webp',
		'welcome'   => 'welcome.webp welcome-2.webp',
		'agency'    => 'agency.webp agency-2.webp',
		'switch'    => 'switch.webp switch-2.webp',
		'lifetime'  => 'lifetime.webp lifetime-2.webp',
		'affiliate' => 'affiliate.webp affiliate-2.webp',
	);
}

/**
 * Photo URLs for a popup: its saved list, else the defaults that exist.
 *
 * @param string $id Popup ID.
 * @return string[]
 */
function semrs_us_popup_images( $id ) {
	$out = array();
	foreach ( preg_split( '/[\s,|]+/', (string) semrs_us_popup_conf( $id )['img'], -1, PREG_SPLIT_NO_EMPTY ) as $f ) {
		if ( preg_match( '#^https?://#', $f ) ) {
			$out[] = esc_url_raw( $f );
		} elseif ( file_exists( SEMRS_US_DIR . 'assets/popups/' . basename( $f ) ) ) {
			$out[] = SEMRS_US_URL . 'assets/popups/' . rawurlencode( basename( $f ) );
		}
	}
	return $out;
}

/**
 * The photo tag for a popup ('' without photos). The browser picks one of
 * the photos when the popup opens (src is set then, so nothing loads before).
 *
 * @param string $id Popup ID.
 * @return string
 */
function semrs_us_popup_img_tag( $id ) {
	$imgs = semrs_us_popup_images( $id );
	$list = esc_attr( implode( '|', $imgs ) );
	// A blurred copy fills the whole popup behind the text on desktop (same photo).
	return $imgs ? '<img class="semrs-pop__bg" alt="" aria-hidden="true" decoding="async" data-imgs="' . $list . '"><img class="semrs-pop__img" alt="" width="400" height="500" decoding="async" data-imgs="' . $list . '">' : '';
}

/**
 * One popup's settings: defaults with the saved changes (empty = default).
 *
 * @param string $id Popup ID.
 * @return array name, on, title, text, cta, delay, scroll, days.
 */
function semrs_us_popup_conf( $id ) {
	$saved = get_option( 'semrs_us_popups', array() );
	$saved = is_array( $saved ) && isset( $saved[ $id ] ) && is_array( $saved[ $id ] ) ? $saved[ $id ] : array();
	$conf  = array_merge( array( 'on' => 1, 'img' => semrs_us_popup_default_images()[ $id ] ?? '' ), semrs_us_popup_defaults()[ $id ], array_filter( $saved, 'semrs_us_campaign_keep' ) );
	foreach ( array( 'delay', 'scroll', 'days' ) as $n ) {
		$conf[ $n ] = (int) $conf[ $n ];
	}
	return $conf;
}

/**
 * Page slugs that never get popups (checkout, licenses, forms, legal).
 *
 * @return string[]
 */
function semrs_us_popup_excluded_slugs() {
	return apply_filters( 'semrs_us_popup_excluded_slugs', array( 'my-licenses', 'get-a-quote', 'checkout', 'cart', 'thank-you', 'privacy-policy', 'terms-and-conditions', 'cookie-policy', 'disclosure', 'refund-policy', 'contact-us', 'registration-form', 'guest-post-submission-form', 'affiliate-dashboard' ) );
}

/**
 * Is this a sales page (home, store, pricing, services)?
 *
 * @return bool
 */
function semrs_us_popup_sales_page() {
	$pages = apply_filters( 'semrs_us_popup_sales_slugs', array( 'semrs-pro', 'wordpress-themes-plugins', 'semrs-assets', 'services', 'pricing', 'web-design', 'web-design-portfolio', 'how-we-work' ) );
	return is_front_page() || is_singular( 'semrs_product' ) || is_post_type_archive( 'semrs_product' ) || is_tax( 'semrs_product_cat' ) || is_page( $pages );
}

/**
 * Is this the SEMRS Pro pricing page?
 *
 * @return bool
 */
function semrs_us_popup_pricing_page() {
	return is_page( 'semrs-pro' ) || ( is_singular( 'semrs_product' ) && 'semrs-pro' === get_post_field( 'post_name', get_queried_object_id() ) );
}

/**
 * Websites running SEMRS Pro, Core or the theme in the last 30 days (real
 * update-check data, cached for a day).
 *
 * @return int
 */
function semrs_us_live_site_count() {
	$n = get_transient( 'semrs_us_live_count' );
	if ( false === $n ) {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT host) FROM {$wpdb->prefix}semrs_sites WHERE last_seen > %s", gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		set_transient( 'semrs_us_live_count', $n, DAY_IN_SECONDS );
	}
	return (int) $n;
}

/**
 * Example sale price line: "All Pro, 1 website: $69 → $37.95 first year".
 *
 * @param int    $pct  Percent off.
 * @param string $plan Plan ID.
 * @return string HTML.
 */
function semrs_us_popup_price_line( $pct, $plan = 'all-1' ) {
	$p = semrs_us_plans()[ $plan ] ?? null;
	if ( ! $p || ! $p['price'] ) {
		return '';
	}
	$new  = round( $p['price'] * ( 100 - $pct ) / 100, 2 );
	$term = 'life' === $p['term'] ? __( 'once', 'semrs-update-server' ) : __( 'first year', 'semrs-update-server' );
	return '<p class="semrs-pop__price">' . esc_html( semrs_us_plan_label( $p ) ) . ': <s>' . esc_html( semrs_us_money( $p['price'] ) ) . '</s> <strong>' . esc_html( semrs_us_money( $new ) ) . '</strong> ' . esc_html( $term ) . '</p>';
}

/**
 * Popup items for this page: [ items, templates HTML ].
 *
 * @return array
 */
function semrs_us_popup_items() {
	$ads     = function_exists( 'semrs_ads_allowed' ) && semrs_ads_allowed();
	$sales   = semrs_us_popup_sales_page();
	$pricing = semrs_us_popup_pricing_page();
	$url     = semrs_us_pricing_url();
	$preview = semrs_us_campaign_preview();
	$refund  = (int) semrs_us_sales()['refund_days'];
	$items   = array();
	$tpl     = '';
	$close   = '<button type="button" class="semrs-pop__x" data-close aria-label="' . esc_attr__( 'Close', 'semrs-update-server' ) . '">&times;</button>';
	$add     = function ( $item, $html ) use ( &$items, &$tpl ) {
		$items[] = $item;
		$tpl    .= '<template id="semrs-pop-' . esc_attr( $item['id'] ) . '">' . $html . '</template>';
	};

	// Seasonal sales: live now or starting within 2 days (for cached pages).
	$soon = array();
	foreach ( semrs_us_campaigns() as $c ) {
		if ( ! $c['season'] || ( $c['id'] !== $preview && ( ! semrs_us_campaign_settings()['on'] || ! semrs_us_campaign_usable( $c ) ) ) ) {
			continue;
		}
		$win = semrs_us_campaign_window( $c );
		if ( $c['id'] === $preview || $win[0] <= time() + 2 * DAY_IN_SECONDS ) {
			$soon[] = array( $c, $win );
		}
	}
	$ps   = semrs_us_popup_conf( 'sale' );
	$simg = semrs_us_popup_img_tag( 'sale' );
	$pb = semrs_us_popup_conf( 'bar' );
	foreach ( $soon as $row ) {
		list( $c, $win ) = $row;
		$pv    = $c['id'] === $preview;
		$s     = $pv ? 0 : $win[0] * 1000;
		$e     = $pv ? 0 : $win[1] * 1000;
		$title = sprintf( $ps['title'] ? $ps['title'] : $c['headline'], (int) $c['percent'] );
		$c['text'] = $ps['text'] ? $ps['text'] : $c['text'];
		$ends  = sprintf( /* translators: %s: date */ __( 'Ends %s', 'semrs-update-server' ), wp_date( 'F j', $win[1] ) );
		$code  = $c['auto'] ? sprintf( /* translators: %s: code */ __( 'Code %s is applied at checkout.', 'semrs-update-server' ), $c['code'] ) : '';
		$fine  = trim( $code . ( $refund ? ' ' . sprintf( /* translators: %d: days */ __( '%d-day refunds.', 'semrs-update-server' ), $refund ) : '' ) );
		if ( $ps['on'] ) {
		$add(
			array( 'id' => $c['id'] . '-modal', 'k' => $c['id'] . '-' . $win[0], 'kind' => 'modal', 's' => $s, 'e' => $e, 'season' => 1, 'delay' => $ads ? 0 : $ps['delay'], 'scroll' => $ads ? 0 : $ps['scroll'], 'exit' => 1, 'dt' => $ads ? 1 : 0, 'days' => $ps['days'] ),
			'<div class="semrs-pop__box' . ( $simg ? ' has-img' : '' ) . '" role="dialog" aria-modal="true" aria-labelledby="semrs-pop-t-' . esc_attr( $c['id'] ) . '">' . $close . $simg
			. '<p class="semrs-pop__tag">' . esc_html( $c['name'] ) . '</p><h2 class="semrs-pop__h" id="semrs-pop-t-' . esc_attr( $c['id'] ) . '">' . esc_html( $title ) . '</h2>'
			. '<p>' . esc_html( sprintf( $c['text'], (int) $c['percent'] ) ) . '</p>' . semrs_us_popup_price_line( (int) $c['percent'] ) . semrs_us_popup_price_line( (int) $c['life'] > 0 ? (int) $c['life'] : (int) $c['percent'], 'all-life-u' )
			. '<p class="semrs-pop__ends">' . esc_html( $ends ) . ' · <span data-ends="' . esc_attr( $e ) . '"></span></p>'
			. '<a class="semrs-pop__cta" href="' . esc_url( $url ) . '" data-close>' . esc_html( $ps['cta'] ) . '</a>'
			. ( $fine ? '<p class="semrs-pop__fine">' . esc_html( $fine ) . '</p>' : '' ) . '</div>'
		);
		}
		if ( ! $ads && $pb['on'] ) {
			$btitle = $pb['title'] ? sprintf( $pb['title'], (int) $c['percent'] ) : $title;
			$add(
				array( 'id' => $c['id'] . '-bar', 'k' => $c['id'] . '-bar-' . $win[0], 'kind' => 'bar', 's' => $s, 'e' => $e, 'season' => 1, 'delay' => max( 1, $pb['delay'] ), 'days' => 0 ),
				'<div class="semrs-pop__bar" role="region" aria-label="' . esc_attr( $c['name'] ) . '"><strong>' . esc_html( $btitle ) . '</strong>' . ( $pb['text'] ? ' <span>' . esc_html( $pb['text'] ) . '</span>' : '' ) . ' <span class="semrs-pop__cd" data-ends="' . esc_attr( $e ) . '"></span> <a class="semrs-pop__cta semrs-pop__cta--sm" href="' . esc_url( $url ) . '">' . esc_html( $pb['cta'] ) . '</a>' . $close . '</div>'
			);
		}
	}

	// Always-on deals. They hide in the browser while a seasonal sale runs ('off').
	$live = array();
	foreach ( semrs_us_campaigns_live() as $c ) {
		$live[ $c['id'] ] = $c;
	}
	$code_box = function ( $code ) {
		return '<p><button type="button" class="semrs-pop__code" data-copy="' . esc_attr( $code ) . '"><span>' . esc_html( $code ) . '</span> <small>' . esc_html__( 'Copy code', 'semrs-update-server' ) . '</small></button></p>';
	};
	$deal = function ( $id, $kind, $trigger ) use ( &$add, $live, $close, $code_box, $url ) {
		$c = $live[ $id ];
		$p = semrs_us_popup_conf( $id );
		if ( ! $p['on'] ) {
			return;
		}
		$title = sprintf( $p['title'] ? $p['title'] : $c['headline'], (int) $c['percent'] );
		$text  = sprintf( $p['text'] ? $p['text'] : $c['text'], (int) $c['percent'] );
		$img   = semrs_us_popup_img_tag( $id );
		$cls   = $img ? ' has-img' : '';
		$body  = 'modal' === $kind ? '<div class="semrs-pop__box' . $cls . '" role="dialog" aria-modal="true"' : '<div class="semrs-pop__slide' . $cls . '" role="dialog"';
		$body .= ' aria-labelledby="semrs-pop-t-' . esc_attr( $id ) . '">' . $close . $img;
		if ( 'agency' === $id ) {
			$body .= '<p class="semrs-pop__tag">' . esc_html__( 'For agencies and freelancers', 'semrs-update-server' ) . '</p>';
		}
		$body .= '<h2 class="semrs-pop__h" id="semrs-pop-t-' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h2><p>' . esc_html( $text ) . '</p>';
		$body .= $c['auto'] ? semrs_us_popup_price_line( (int) $c['percent'], $c['plans'] ? $c['plans'][0] : 'all-1' ) : $code_box( $c['code'] );
		$body .= '<a class="semrs-pop__cta' . ( 'modal' === $kind ? '' : ' semrs-pop__cta--sm' ) . '" href="' . esc_url( $url ) . '" data-close>' . esc_html( $p['cta'] ) . '</a>';
		if ( 'welcome' === $id ) {
			$body .= '<p class="semrs-pop__fine">' . esc_html__( 'First order only. Free plugins and the theme stay free.', 'semrs-update-server' ) . '</p>';
		}
		$add( array_merge( array( 'id' => $id, 'k' => $id, 'kind' => $kind, 'delay' => $p['delay'], 'scroll' => $p['scroll'], 'days' => $p['days'] ), $trigger ), $body . '</div>' );
	};
	if ( ! $ads && isset( $live['welcome'] ) && ( $sales || $preview ) ) {
		$deal( 'welcome', 'modal', array( 'off' => 1, 'exit' => 1, 'views' => 2 ) );
	}
	if ( ! $ads && isset( $live['agency'] ) && ( $sales || $preview ) ) {
		$deal( 'agency', 'slide', array() );
	}
	if ( ! $ads && isset( $live['switch'] ) && ( $sales || $preview ) && ! $pricing ) {
		$deal( 'switch', 'slide', array( 'off' => 1 ) );
	}
	$pl = semrs_us_popup_conf( 'lifetime' );
	if ( ( $pricing || $preview ) && $pl['on'] ) {
		$life = semrs_us_plans()['all-life-u'];
		$add(
			array( 'id' => 'lifetime', 'k' => 'lifetime', 'kind' => 'slide', 'off' => 1, 'delay' => $pl['delay'], 'scroll' => $pl['scroll'], 'days' => $pl['days'] ),
			'<div class="semrs-pop__slide' . ( semrs_us_popup_images( 'lifetime' ) ? ' has-img' : '' ) . '" role="dialog" aria-labelledby="semrs-pop-t-life">' . $close . semrs_us_popup_img_tag( 'lifetime' ) . '<h2 class="semrs-pop__h" id="semrs-pop-t-life">' . esc_html( $pl['title'] ) . '</h2><p>' . esc_html( sprintf( $pl['text'], semrs_us_money( $life['price'] ) ) ) . '</p><a class="semrs-pop__cta semrs-pop__cta--sm" href="' . esc_url( semrs_us_buy_url( $life ) ) . '" rel="nofollow" data-close>' . esc_html( $pl['cta'] ) . '</a></div>'
		);
	}
	$pp = semrs_us_popup_conf( 'proof' );
	$n  = ( $pricing || $preview ) && $pp['on'] ? semrs_us_live_site_count() : 0;
	if ( $n >= 20 ) {
		$add(
			array( 'id' => 'proof', 'k' => 'proof', 'kind' => 'toast', 'delay' => max( 1, $pp['delay'] ), 'days' => $pp['days'], 'hide' => 9 ),
			'<div class="semrs-pop__toast" role="status">' . esc_html( sprintf( $pp['text'], number_format_i18n( $n ) ) ) . $close . '</div>'
		);
	}
	$pa  = semrs_us_popup_conf( 'affiliate' );
	$aff = function_exists( 'semrs_us_aff_page_url' ) ? semrs_us_aff_page_url() : '';
	if ( $aff && $pa['on'] && ! $ads && ! $sales && ( is_home() || is_page() || $preview ) ) {
		$rate = semrs_us_aff_settings();
		$add(
			array( 'id' => 'affiliate', 'k' => 'affiliate', 'kind' => 'slide', 'views' => 3, 'delay' => $pa['delay'], 'scroll' => $pa['scroll'], 'days' => $pa['days'] ),
			'<div class="semrs-pop__slide' . ( semrs_us_popup_images( 'affiliate' ) ? ' has-img' : '' ) . '" role="dialog" aria-labelledby="semrs-pop-t-aff">' . $close . semrs_us_popup_img_tag( 'affiliate' ) . '<h2 class="semrs-pop__h" id="semrs-pop-t-aff">' . esc_html( sprintf( $pa['title'], (int) $rate['first'] ) ) . '</h2><p>' . esc_html( sprintf( $pa['text'], (int) $rate['renewal'], (int) $rate['cookie'] ) ) . '</p><a class="semrs-pop__cta semrs-pop__cta--sm" href="' . esc_url( $aff ) . '" data-close>' . esc_html( $pa['cta'] ) . '</a></div>'
		);
	}
	return array( $items, $tpl );
}

/**
 * Print the popups (footer).
 */
function semrs_us_popups_print() {
	$preview = semrs_us_campaign_preview();
	if ( is_admin() || is_feed() || is_404() || is_search() || is_embed() || is_customize_preview() || ( is_user_logged_in() && ! $preview ) || is_page( semrs_us_popup_excluded_slugs() ) || ( ! semrs_us_campaign_settings()['on'] && ! $preview ) ) {
		return;
	}
	list( $items, $tpl ) = semrs_us_popup_items();
	if ( ! $items ) {
		return;
	}
	$cfg = array(
		'items'   => $items,
		'preview' => $preview ? 1 : 0,
		'copied'  => __( 'Copied', 'semrs-update-server' ),
		'units'   => array( 'd', 'h', 'm', 's' ),
	);
	echo $tpl; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above.
	echo '<script type="application/json" id="semrs-pop-data">' . wp_json_encode( $cfg ) . '</script>';
	?>
<style id="semrs-pop-css">.semrs-pop{position:fixed;z-index:99990;font-size:1rem;line-height:1.5;color:var(--sp-text,#1f2937)}.semrs-pop *{box-sizing:border-box}.semrs-pop--modal{inset:0;display:grid;place-items:center;padding:16px;background:rgba(11,31,58,.55)}.semrs-pop__box,.semrs-pop__slide,.semrs-pop__toast{position:relative;background:var(--sp-surface,#fff);border:1px solid var(--sp-line,#e5e7eb);border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.25)}.semrs-pop__box{width:min(30rem,100%);max-height:calc(100vh - 32px);overflow:auto;padding:28px 24px 20px;text-align:center}.semrs-pop__h{margin:0 0 8px;font-size:1.45rem;line-height:1.25;color:var(--sp-primary,#0b1f3a)}.semrs-pop__box p,.semrs-pop__slide p{margin:0 0 10px}.semrs-pop__tag{display:inline-block;margin:0 0 8px!important;padding:2px 10px;border-radius:99px;background:var(--sp-accent-soft,#fbf3dc);color:var(--sp-accent-text,#6b5200);font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}.semrs-pop__price s{color:var(--sp-muted,#5b6575)}.semrs-pop__price strong{font-size:1.15em;color:var(--sp-primary,#0b1f3a)}.semrs-pop__ends,.semrs-pop__fine{font-size:.85rem;color:var(--sp-muted,#5b6575)}.semrs-pop__cta{display:inline-block;margin:6px 0 10px;padding:12px 22px;border-radius:10px;background:var(--sp-accent,#d4a72c);color:var(--sp-on-accent,#0b1f3a)!important;font-weight:700;text-decoration:none}.semrs-pop__cta:hover,.semrs-pop__cta:focus-visible{filter:brightness(1.07)}.semrs-pop__cta--sm{margin:0;padding:8px 16px;font-size:.9rem}.semrs-pop__x{position:absolute;top:6px;right:8px;width:40px;height:40px;border:0;background:none;color:inherit;font-size:1.6rem;line-height:1;cursor:pointer;border-radius:8px}.semrs-pop__x:focus-visible,.semrs-pop__code:focus-visible{outline:2px solid var(--sp-accent,#d4a72c);outline-offset:2px}.semrs-pop__code{display:inline-flex;gap:10px;align-items:center;padding:8px 14px;border:2px dashed var(--sp-accent,#d4a72c);border-radius:10px;background:none;color:inherit;font:inherit;cursor:pointer}.semrs-pop__code span{font-weight:800;letter-spacing:.08em}.semrs-pop--bar{left:0;right:0;bottom:0}.semrs-pop__bar{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;justify-content:center;padding:10px 52px 10px 16px;background:var(--sp-primary,#0b1f3a);color:#fff;position:relative}.semrs-pop__bar .semrs-pop__x{top:50%;transform:translateY(-50%);color:#fff}.semrs-pop__cd{font-variant-numeric:tabular-nums;opacity:.9}.semrs-pop--slide{right:16px;bottom:16px;width:min(21rem,calc(100% - 32px))}.semrs-pop__slide{padding:20px 18px 16px}.semrs-pop__slide .semrs-pop__h{font-size:1.1rem;padding-right:28px}.semrs-pop--toast{left:16px;bottom:16px;max-width:calc(100% - 32px)}.semrs-pop__toast{padding:12px 48px 12px 16px;font-size:.92rem}.semrs-pop__toast .semrs-pop__x{top:50%;transform:translateY(-50%);width:36px;height:36px}.semrs-pop>*{animation:semrs-pop-in .25s ease-out}.semrs-pop__x{z-index:2}.semrs-pop__bg{display:none}.semrs-pop__box.has-img{text-align:left}@media (min-width:769px){.semrs-pop__box.has-img .semrs-pop__bg{display:block;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(12px) brightness(.78) saturate(1.3);border-radius:16px;pointer-events:none}.semrs-pop__box.has-img{width:min(44rem,100%);padding:30px 32px 22px 360px;background:#0b1f3a;color:#e9eef6;border-color:rgba(212,167,44,.65)}.semrs-pop__box.has-img .semrs-pop__img{position:absolute;left:0;top:0;width:360px;height:100%;object-fit:cover;object-position:center top;border-radius:16px 0 0 16px;-webkit-mask-image:linear-gradient(90deg,#000 55%,transparent 92%);mask-image:linear-gradient(90deg,#000 55%,transparent 92%)}.semrs-pop__box.has-img>:not(.semrs-pop__img):not(.semrs-pop__x):not(.semrs-pop__bg){position:relative}.semrs-pop__box.has-img .semrs-pop__h{background:linear-gradient(100deg,#fff 0%,#ffe9a8 30%,#fff 45%,#f3cd5c 60%,#fff 80%);background-size:250% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;filter:drop-shadow(0 2px 6px rgba(0,0,0,.55));animation:semrs-pop-text 4s linear infinite}.semrs-pop__box.has-img p{text-shadow:0 1px 4px rgba(0,0,0,.65)}.semrs-pop__box.has-img .semrs-pop__price s{color:#d5deea}.semrs-pop__box.has-img .semrs-pop__price strong{color:#f3cd5c;text-shadow:0 0 12px rgba(243,205,92,.75),0 1px 3px rgba(0,0,0,.6)}.semrs-pop__box.has-img .semrs-pop__ends,.semrs-pop__box.has-img .semrs-pop__fine{color:#e3e9f2}.semrs-pop__box.has-img .semrs-pop__x{color:#fff}.semrs-pop__box.has-img .semrs-pop__code{color:#fff}}.semrs-pop__slide.has-img .semrs-pop__img{float:left;width:64px;height:64px;margin:0 12px 6px 0;border-radius:50%;object-fit:cover;object-position:top;border:2px solid var(--sp-accent,#d4a72c)}.semrs-pop__box,.semrs-pop__slide{box-shadow:0 20px 50px rgba(0,0,0,.25),0 0 0 2px rgba(212,167,44,.6),0 0 30px rgba(212,167,44,.35)}.semrs-pop__cta{position:relative;overflow:hidden;background:linear-gradient(135deg,#f6d36b,#d4a72c 55%,#b8891a);box-shadow:0 6px 18px rgba(212,167,44,.45)}.semrs-pop__cta::after{content:"";position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.7),transparent);transform:skewX(-20deg);animation:semrs-pop-shine 2.8s ease-in-out infinite}@keyframes semrs-pop-text{to{background-position:-250% 0}}@keyframes semrs-pop-shine{0%,55%{left:-60%}100%{left:130%}}.semrs-pop__bar strong{background:linear-gradient(90deg,#fff,#ffe39a,#fff);-webkit-background-clip:text;background-clip:text;color:transparent}@keyframes semrs-pop-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}@media (prefers-reduced-motion:reduce){.semrs-pop>*{animation:none}.semrs-pop__cta::after{animation:none;display:none}.semrs-pop__box.has-img .semrs-pop__h{animation:none}}@media (max-width:768px){.semrs-pop--modal{align-items:end;padding:0;background:transparent;pointer-events:none}.semrs-pop--modal .semrs-pop__box{pointer-events:auto;width:100%;max-height:45vh;overflow:auto;border-radius:16px 16px 0 0;padding:20px 16px 12px}.semrs-pop__h{font-size:1.2rem}.semrs-pop__box.has-img{padding:20px 16px 12px}.semrs-pop__box.has-img .semrs-pop__img{position:static;float:left;width:64px;height:64px;margin:0 12px 6px 0;border-radius:50%;border:2px solid var(--sp-accent,#d4a72c)}.semrs-pop--slide{right:0;bottom:0;width:100%}.semrs-pop__slide{border-radius:16px 16px 0 0}}</style>
<script id="semrs-pop-js">(function(){function run(){var d=document,w=window,c=JSON.parse(d.getElementById('semrs-pop-data').textContent),open=0,bar=0;
function st(s,k,v){try{if(v===undefined)return s.getItem(k);s.setItem(k,v)}catch(e){return null}}
function L(k,v){return st(w.localStorage,k,v)}function S(k,v){return st(w.sessionStorage,k,v)}
function cd(){var n=Date.now();d.querySelectorAll('.semrs-pop [data-ends]').forEach(function(el){var t=+el.getAttribute('data-ends')-n;if(!(+el.getAttribute('data-ends'))){el.textContent='';return}if(t<0)t=0;t=Math.floor(t/1e3);var u=c.units,x=[Math.floor(t/86400),Math.floor(t%86400/3600),Math.floor(t%3600/60),t%60];el.textContent=(x[0]?x[0]+u[0]+' ':'')+[x[1],x[2],x[3]].map(function(v,i){return(v<10?'0':'')+v+u[i+1]}).join(' ')})}
function show(i){if(i.kind!=='bar'){if(open)return;open=1;if(!c.preview)S('semrs-pop-shown','1')}else{if(bar)return;bar=1}
var t=d.getElementById('semrs-pop-'+i.id);if(!t)return;var wrap=d.createElement('div'),last=d.activeElement;wrap.className='semrs-pop semrs-pop--'+i.kind;wrap.appendChild(t.content.cloneNode(true));d.body.appendChild(wrap);var pk=-1;wrap.querySelectorAll('img[data-imgs]').forEach(function(m){var l=m.getAttribute('data-imgs').split('|');if(pk<0)pk=Math.floor(Math.random()*l.length);m.src=l[pk]||l[0]});cd();
function close(){wrap.remove();d.removeEventListener('keydown',key);if(!c.preview)L('semrs-pop-'+i.k,String(i.kind==='bar'&&i.e?i.e:Date.now()+(i.days||1)*864e5));if(last&&last.focus&&i.kind==='modal')last.focus()}
function key(e){if(e.key==='Escape')close();if(e.key==='Tab'&&i.kind==='modal'){var f=wrap.querySelectorAll('a,button'),a=f[0],z=f[f.length-1];if(e.shiftKey&&d.activeElement===a){e.preventDefault();z.focus()}else if(!e.shiftKey&&d.activeElement===z){e.preventDefault();a.focus()}}}
d.addEventListener('keydown',key);wrap.addEventListener('click',function(e){var b=e.target.closest('[data-copy]');if(b){e.preventDefault();var s=b.querySelector('small'),v=b.getAttribute('data-copy');(navigator.clipboard?navigator.clipboard.writeText(v):Promise.reject()).then(function(){s.textContent=c.copied},function(){});return}if(e.target===wrap&&i.kind==='modal'||e.target.closest('[data-close]'))close()});
if(i.kind==='modal'){var f=wrap.querySelector('.semrs-pop__cta,button');if(f)f.focus({preventScroll:true})}if(i.hide)setTimeout(close,i.hide*1e3)}
function arm(i){var fired=0;function go(){if(fired)return;fired=1;w.removeEventListener('scroll',sc);d.removeEventListener('mouseout',ex);show(i)}
function sc(){var h=d.documentElement;if((h.scrollTop+w.innerHeight)/h.scrollHeight*100>=i.scroll)go()}
function ex(e){if(!e.relatedTarget&&e.clientY<=0)go()}
if(c.preview){setTimeout(go,i.kind==='bar'?300:1200);return}
if(i.delay)setTimeout(go,i.delay*1e3);if(i.scroll)w.addEventListener('scroll',sc,{passive:true});if(i.exit&&!mob)d.addEventListener('mouseout',ex)}
var now=Date.now(),mob=w.matchMedia('(max-width:768px)').matches,pv=(+S('semrs-pv')||0)+1;S('semrs-pv',String(pv));
var live=c.items.filter(function(i){return(!i.s||now>=i.s)&&(!i.e||now<=i.e)}),sale=live.some(function(i){return i.season});
live=live.filter(function(i){if(c.preview)return true;if(i.off&&sale)return false;if((+L('semrs-pop-'+i.k)||0)>now)return false;if(i.dt&&mob)return false;if(i.views&&pv<i.views)return false;if(i.kind==='modal'&&mob&&pv<2)return false;if(i.kind!=='bar'&&S('semrs-pop-shown'))return false;return true});
if(c.preview){var b=live.filter(function(i){return i.kind==='bar'})[0],p=live.filter(function(i){return i.kind!=='bar'})[0];live=[b,p].filter(Boolean)}
live.forEach(arm);setInterval(function(){if(d.querySelector('.semrs-pop [data-ends]'))cd()},1e3)}
function init(){try{run()}catch(e){}}function idle(){if(window.requestIdleCallback)requestIdleCallback(init,{timeout:3000});else setTimeout(init,1)}if(document.readyState==='complete')idle();else addEventListener('load',idle)})();</script>
	<?php
}
add_action( 'wp_footer', 'semrs_us_popups_print', 50 );
