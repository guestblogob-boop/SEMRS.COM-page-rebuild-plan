<?php
/**
 * Campaigns: the yearly sales calendar (Black Friday, Cyber Monday,
 * Christmas, New Year and the smaller seasons), the always-on deals (welcome,
 * agency, switch), and the offer each Buy button carries.
 *
 * Dates repeat every year by themselves: Black Friday is the day after the
 * fourth Thursday of November, Cyber Monday the Monday after it, the others
 * are fixed days. Times are in the site timezone (Settings → General).
 *
 * A campaign's discount code must exist in the online checkout (Paddle or
 * Lemon Squeezy) before it is used there: tick "Code created" on the
 * Campaigns screen. Until then the campaign stays hidden whenever an online
 * checkout is on, so nobody sees a price the checkout would not honor. With
 * invoices only, the percent goes on the invoice.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Built-in campaigns. 'when': [ 'bf'|'cm', start offset, end offset ] in days
 * from Black Friday / Cyber Monday, or [ 'md', 'MM-DD', 'MM-DD' ] (an end
 * before the start runs into the next year). Evergreen deals have no 'when'.
 * 'auto' puts the code on Buy buttons by itself; the others are shown in a
 * popup for the visitor to use. 'plans' limits a deal to some plan IDs.
 * 'nosale' hides an evergreen deal while a seasonal sale runs. 'life' and
 * 'life_code' give lifetime plans their own percent and code during that sale.
 *
 * @return array<string, array>
 */
function semrs_us_campaign_defaults() {
	return array(
		'black-friday'   => array( 'name' => 'Black Friday', 'when' => array( 'bf', -7, 2 ), 'percent' => 45, 'code' => 'BLACKFRIDAY45', 'life' => 50, 'life_code' => 'BFLIFE50', 'auto' => true, 'priority' => 100, 'headline' => 'Black Friday: %d%% off SEMRS Pro', 'text' => 'Every add-on and the lifetime deal at our lowest price of the year.' ),
		'cyber-monday'   => array( 'name' => 'Cyber Monday', 'when' => array( 'cm', 0, 2 ), 'percent' => 45, 'code' => 'CYBERMONDAY45', 'life' => 50, 'life_code' => 'CMLIFE50', 'auto' => true, 'priority' => 100, 'headline' => 'Cyber Monday: %d%% off SEMRS Pro', 'text' => 'Last chance for our lowest price of the year, on every plan.' ),
		'christmas'      => array( 'name' => 'Christmas sale', 'when' => array( 'md', '12-18', '12-26' ), 'percent' => 35, 'code' => 'XMAS35', 'auto' => true, 'priority' => 90, 'headline' => 'Christmas sale: %d%% off SEMRS Pro', 'text' => 'A gift for your websites: SEO, speed, ads and theme Pro for less.' ),
		'new-year'       => array( 'name' => 'New Year sale', 'when' => array( 'md', '12-27', '01-07' ), 'percent' => 30, 'code' => 'NEWYEAR30', 'auto' => true, 'priority' => 90, 'headline' => 'New Year sale: %d%% off SEMRS Pro', 'text' => 'Start the year with faster, better-ranking websites.' ),
		'spring'         => array( 'name' => 'Spring sale', 'when' => array( 'md', '03-20', '03-27' ), 'percent' => 25, 'code' => 'SPRING25', 'auto' => true, 'priority' => 80, 'headline' => 'Spring sale: %d%% off SEMRS Pro', 'text' => 'Spring-clean your site: one plugin stack instead of eight.' ),
		'wp-birthday'    => array( 'name' => 'WordPress birthday sale', 'when' => array( 'md', '05-24', '05-31' ), 'percent' => 27, 'code' => 'WPBDAY27', 'auto' => true, 'priority' => 80, 'headline' => 'WordPress turns another year: %d%% off', 'text' => 'WordPress was born on May 27. We celebrate with 27% off every plan.' ),
		'summer'         => array( 'name' => 'Summer sale', 'when' => array( 'md', '06-21', '06-28' ), 'percent' => 25, 'code' => 'SUMMER25', 'auto' => true, 'priority' => 80, 'headline' => 'Summer sale: %d%% off SEMRS Pro', 'text' => 'Get your site ready for the busy season.' ),
		'back-to-school' => array( 'name' => 'Back to school sale', 'when' => array( 'md', '08-24', '08-31' ), 'percent' => 25, 'code' => 'SCHOOL25', 'auto' => true, 'priority' => 80, 'headline' => 'Back to school: %d%% off SEMRS Pro', 'text' => 'Learn SEO the easy way, with tools that do the heavy lifting.' ),
		'halloween'      => array( 'name' => 'Halloween sale', 'when' => array( 'md', '10-25', '10-31' ), 'percent' => 30, 'code' => 'HALLOWEEN30', 'auto' => true, 'priority' => 80, 'headline' => 'Halloween: %d%% off SEMRS Pro', 'text' => 'No tricks, just a treat for your websites.' ),
		'agency'         => array( 'name' => 'Agency deal', 'percent' => 25, 'code' => 'AGENCY25', 'auto' => true, 'plans' => array( 'all-u', 'all-life-u' ), 'priority' => 50, 'headline' => 'Agency deal: %d%% off unlimited websites', 'text' => 'All Pro for every client site, yearly or lifetime. Applied at checkout.' ),
		'switch'         => array( 'name' => 'Switch deal', 'percent' => 20, 'code' => 'SWITCH20', 'auto' => false, 'nosale' => true, 'priority' => 40, 'headline' => 'Moving from another SEO or speed plugin?', 'text' => 'SEMRS brings over your titles, descriptions and settings from other popular SEO plugins. Take %d%% off your first year with this code.' ),
		'welcome'        => array( 'name' => 'Welcome offer', 'percent' => 15, 'code' => 'WELCOME15', 'auto' => false, 'nosale' => true, 'priority' => 30, 'headline' => 'Before you go: %d%% off your first order', 'text' => 'Use this code at checkout on any SEMRS Pro plan.' ),
	);
}

/**
 * Campaign settings: master switch plus per-campaign overrides.
 *
 * @return array
 */
function semrs_us_campaign_settings() {
	$s = get_option( 'semrs_us_campaigns', array() );
	return array_merge( array( 'on' => 1, 'items' => array() ), is_array( $s ) ? $s : array() );
}

/**
 * Campaigns with saved overrides applied.
 *
 * @return array<string, array>
 */
function semrs_us_campaigns() {
	$saved = semrs_us_campaign_settings()['items'];
	$out   = array();
	foreach ( semrs_us_campaign_defaults() as $id => $c ) {
		$c = array_merge(
			array( 'when' => array(), 'plans' => array(), 'auto' => false, 'nosale' => false, 'enabled' => 1, 'ready' => 0, 'life' => 0, 'life_code' => '' ),
			$c,
			isset( $saved[ $id ] ) && is_array( $saved[ $id ] ) ? array_filter( $saved[ $id ], 'semrs_us_campaign_keep' ) : array()
		);
		if ( ! empty( $saved[ $id ]['start'] ) && ! empty( $saved[ $id ]['end'] ) && $c['when'] ) {
			$c['when'] = array( 'md', $saved[ $id ]['start'], $saved[ $id ]['end'] );
		}
		$c['id']     = $id;
		$c['season'] = (bool) $c['when'];
		$out[ $id ]  = $c;
	}
	return $out;
}

/**
 * Keep a saved override unless it is an empty string (empty = default).
 *
 * @param mixed $v Value.
 * @return bool
 */
function semrs_us_campaign_keep( $v ) {
	return '' !== $v && null !== $v;
}

/**
 * Black Friday of a year: the day after the fourth Thursday of November.
 *
 * @param int $year Year.
 * @return DateTimeImmutable Midnight, site timezone.
 */
function semrs_us_black_friday( $year ) {
	$d = new DateTimeImmutable( 'fourth thursday of november ' . (int) $year, wp_timezone() );
	return $d->setTime( 0, 0 )->modify( '+1 day' );
}

/**
 * Start and end (Unix time) of a campaign's run in a given year.
 *
 * @param array $c    Campaign.
 * @param int   $year Year it starts in.
 * @return int[] [ start, end ] (end is the last second of the last day).
 */
function semrs_us_campaign_window_in( $c, $year ) {
	$w = $c['when'];
	if ( 'md' === $w[0] ) {
		$tz    = wp_timezone();
		$start = new DateTimeImmutable( $year . '-' . $w[1] . ' 00:00:00', $tz );
		$end   = new DateTimeImmutable( $year . '-' . $w[2] . ' 23:59:59', $tz );
		if ( $end < $start ) {
			$end = $end->modify( '+1 year' );
		}
	} else {
		$base  = semrs_us_black_friday( $year )->modify( 'cm' === $w[0] ? '+3 days' : '+0 days' );
		$start = $base->modify( (int) $w[1] . ' days' );
		$end   = $base->modify( (int) $w[2] . ' days' )->setTime( 23, 59, 59 );
	}
	return array( $start->getTimestamp(), $end->getTimestamp() );
}

/**
 * The current or next run of a seasonal campaign.
 *
 * @param array    $c   Campaign.
 * @param int|null $now Unix time.
 * @return int[] [ start, end ], or [] for evergreen deals.
 */
function semrs_us_campaign_window( $c, $now = null ) {
	if ( ! $c['when'] ) {
		return array();
	}
	$now  = null === $now ? time() : $now;
	$year = (int) wp_date( 'Y', $now );
	foreach ( array( $year - 1, $year, $year + 1 ) as $y ) {
		$win = semrs_us_campaign_window_in( $c, $y );
		if ( $win[1] >= $now ) {
			return $win;
		}
	}
	return semrs_us_campaign_window_in( $c, $year + 1 );
}

/**
 * Is an online checkout (Paddle or Lemon Squeezy) switched on?
 *
 * @return bool
 */
function semrs_us_online_checkout() {
	if ( semrs_us_paddle_on() ) {
		return true;
	}
	foreach ( semrs_us_plans() as $p ) {
		if ( '' !== $p['checkout'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Can a campaign be shown? It is switched on, and its code exists in the
 * online checkout (or sales go by invoice, where the percent is typed in).
 *
 * @param array $c Campaign.
 * @return bool
 */
function semrs_us_campaign_usable( $c ) {
	return ! empty( $c['enabled'] ) && (int) $c['percent'] > 0 && ( ! empty( $c['ready'] ) || ! semrs_us_online_checkout() );
}

/**
 * Campaigns running now, best first. Admins can preview one with
 * ?semrs_preview=<id> (it then counts as running, code or not).
 *
 * @param int|null $now Unix time.
 * @return array[]
 */
function semrs_us_campaigns_live( $now = null ) {
	if ( ! semrs_us_campaign_settings()['on'] ) {
		return array();
	}
	$now     = null === $now ? time() : $now;
	$preview = semrs_us_campaign_preview();
	$live    = array();
	foreach ( semrs_us_campaigns() as $id => $c ) {
		if ( $id === $preview ) {
			$c['window'] = $c['season'] ? semrs_us_campaign_window( $c, $now ) : array();
			$live[]      = $c;
			continue;
		}
		if ( ! semrs_us_campaign_usable( $c ) ) {
			continue;
		}
		$win = semrs_us_campaign_window( $c, $now );
		if ( $win && $win[0] > $now ) {
			continue;
		}
		$c['window'] = $win;
		$live[]      = $c;
	}
	$sale = (bool) array_filter( wp_list_pluck( $live, 'season' ) );
	$live = array_values(
		array_filter(
			$live,
			function ( $c ) use ( $sale ) {
				return ! ( $sale && $c['nosale'] );
			}
		)
	);
	usort(
		$live,
		function ( $a, $b ) {
			return (int) $b['priority'] <=> (int) $a['priority'] ?: (int) $b['percent'] <=> (int) $a['percent'];
		}
	);
	return $live;
}

/**
 * Campaign an admin is previewing, or ''.
 *
 * @return string
 */
function semrs_us_campaign_preview() {
	if ( empty( $_GET['semrs_preview'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only preview.
		return '';
	}
	return sanitize_key( wp_unslash( $_GET['semrs_preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * The live seasonal sale, or null.
 *
 * @return array|null
 */
function semrs_us_sale_now() {
	foreach ( semrs_us_campaigns_live() as $c ) {
		if ( $c['season'] ) {
			return $c;
		}
	}
	return null;
}

/**
 * Loyalty discount on renewals (Sales & prices), or null when off.
 *
 * @return array|null
 */
function semrs_us_renew_offer() {
	$s = semrs_us_sales();
	return (int) $s['renew_percent'] > 0 ? array(
		'id'      => 'renewal',
		'code'    => $s['renew_code'],
		'percent' => (int) $s['renew_percent'],
		/* translators: %d: percent */
		'label'   => sprintf( __( 'Loyalty renewal: %d%% off', 'semrs-update-server' ), (int) $s['renew_percent'] ),
		'name'    => __( 'loyalty renewal discount', 'semrs-update-server' ),
		'ends'    => 0,
	) : null;
}

/**
 * The offer a plan's Buy button carries: the best running campaign that puts
 * its code on buttons and covers the plan, else the launch offer. For an
 * existing customer's key ($kid) the loyalty renewal discount is used when
 * it is bigger.
 *
 * @param string $plan Plan ID.
 * @param string $kid  Existing key ID ('' for new customers).
 * @return array|null code, percent, label, ends (Unix time or 0), id.
 */
function semrs_us_offer_for( $plan, $kid = '' ) {
	$best = semrs_us_offer_new( $plan );
	$ren  = $kid ? semrs_us_renew_offer() : null;
	return $ren && ( ! $best || $ren['percent'] > $best['percent'] ) ? $ren : $best;
}

/**
 * The best offer for a new purchase of a plan (see semrs_us_offer_for()).
 *
 * @param string $plan Plan ID.
 * @return array|null
 */
function semrs_us_offer_new( $plan ) {
	$life = 0 === strpos( $plan, 'all-life-' );
	foreach ( semrs_us_campaigns_live() as $c ) {
		if ( $c['auto'] && ( ! $c['plans'] || in_array( $plan, $c['plans'], true ) ) ) {
			$pct = $life && (int) $c['life'] > 0 ? (int) $c['life'] : (int) $c['percent'];
			return array(
				'id'      => $c['id'],
				'code'    => $life && (int) $c['life'] > 0 && '' !== $c['life_code'] ? $c['life_code'] : $c['code'],
				'percent' => $pct,
				'label'   => sprintf( $c['headline'], $pct ),
				'name'    => $c['name'],
				'ends'    => $c['window'] ? $c['window'][1] : 0,
			);
		}
	}
	if ( semrs_us_offer_on() ) {
		$s = semrs_us_sales();
		return array(
			'id'      => 'launch',
			'code'    => $s['offer_code'],
			'percent' => (int) $s['offer_percent'],
			/* translators: %d: percent */
			'label'   => $s['offer_label'] ? $s['offer_label'] : sprintf( __( 'Launch offer: %d%% off your first year', 'semrs-update-server' ), (int) $s['offer_percent'] ),
			'name'    => __( 'launch offer', 'semrs-update-server' ),
			'ends'    => $s['offer_ends'] ? strtotime( $s['offer_ends'] . ' 23:59:59' ) : 0,
		);
	}
	return null;
}

/**
 * Upcoming runs of every seasonal campaign over the next N days, by date.
 *
 * @param int $days Days ahead.
 * @return array[] campaign + 'window'.
 */
function semrs_us_campaign_schedule( $days = 400 ) {
	$now  = time();
	$out  = array();
	$year = (int) wp_date( 'Y', $now );
	foreach ( semrs_us_campaigns() as $c ) {
		if ( ! $c['season'] ) {
			continue;
		}
		foreach ( array( $year - 1, $year, $year + 1, $year + 2 ) as $y ) {
			$win = semrs_us_campaign_window_in( $c, $y );
			if ( $win[1] >= $now && $win[0] <= $now + $days * DAY_IN_SECONDS ) {
				$out[] = array_merge( $c, array( 'window' => $win ) );
			}
		}
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $a['window'][0] <=> $b['window'][0];
		}
	);
	return $out;
}

/**
 * Prices change on the pricing page when a sale starts or ends: clear the
 * page cache at each start and end over the next 40 days (queued daily).
 */
function semrs_us_campaign_queue_switches() {
	foreach ( semrs_us_campaign_schedule( 40 ) as $c ) {
		foreach ( array( $c['window'][0], $c['window'][1] + 1 ) as $at ) {
			if ( $at > time() && ! wp_next_scheduled( 'semrs_us_campaign_switch', array( $at ) ) ) {
				wp_schedule_single_event( $at, 'semrs_us_campaign_switch', array( $at ) );
			}
		}
	}
}
add_action( 'semrs_us_daily', 'semrs_us_campaign_queue_switches' );
add_action( 'update_option_semrs_us_campaigns', 'semrs_us_campaign_queue_switches' );

/**
 * A sale started or ended: clear cached pages so prices and popups match.
 */
function semrs_us_campaign_switch() {
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
	do_action( 'semrs_us_campaign_switched' );
}
add_action( 'semrs_us_campaign_switch', 'semrs_us_campaign_switch' );

/**
 * SEMRS Pro pricing page URL.
 *
 * @return string
 */
function semrs_us_pricing_url() {
	$p = get_page_by_path( 'semrs-pro' );
	$p = $p && 'publish' === $p->post_status ? $p : get_page_by_path( 'semrs-pro', OBJECT, 'semrs_product' );
	return ( $p ? get_permalink( $p ) : home_url( '/semrs-pro/' ) ) . '#plans';
}
