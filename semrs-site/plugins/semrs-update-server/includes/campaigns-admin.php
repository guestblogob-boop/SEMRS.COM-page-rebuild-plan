<?php
/**
 * SEMRS Updates → Campaigns & popups (sales calendar, deal settings and the
 * popup guide) and SEMRS Updates → Affiliates (partners, commissions,
 * payouts and program rates).
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu items.
 */
function semrs_us_campaign_menu() {
	add_submenu_page( 'semrs-updates', __( 'Campaigns & popups', 'semrs-update-server' ), __( 'Campaigns & popups', 'semrs-update-server' ), 'manage_options', 'semrs-updates-campaigns', 'semrs_us_page_campaigns' );
	add_submenu_page( 'semrs-updates', __( 'Affiliates', 'semrs-update-server' ), __( 'Affiliates', 'semrs-update-server' ), 'manage_options', 'semrs-updates-affiliates', 'semrs_us_page_affiliates' );
}
add_action( 'admin_menu', 'semrs_us_campaign_menu', 20 );

/**
 * Save campaign settings.
 */
function semrs_us_campaigns_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-update-server' ) );
	}
	check_admin_referer( 'semrs_us_campaigns' );
	$in  = (array) wp_unslash( $_POST['c'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field sanitized below.
	$out = array( 'on' => empty( $_POST['on'] ) ? 0 : 1, 'items' => array() );
	$md  = function ( $v ) {
		return preg_match( '/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', (string) $v ) ? $v : '';
	};
	foreach ( semrs_us_campaign_defaults() as $id => $def ) {
		$c                   = (array) ( $in[ $id ] ?? array() );
		$out['items'][ $id ] = array(
			'enabled'  => empty( $c['enabled'] ) ? 0 : 1,
			'ready'    => empty( $c['ready'] ) ? 0 : 1,
			'percent'  => '' === ( $c['percent'] ?? '' ) ? '' : min( 90, absint( $c['percent'] ) ),
			'code'     => preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $c['code'] ?? '' ) ),
			'headline' => sanitize_text_field( (string) ( $c['headline'] ?? '' ) ),
			'text'     => sanitize_text_field( (string) ( $c['text'] ?? '' ) ),
			'start'    => $md( $c['start'] ?? '' ),
			'end'      => $md( $c['end'] ?? '' ),
			'life'      => '' === ( $c['life'] ?? '' ) ? '' : min( 90, absint( $c['life'] ) ),
			'life_code' => preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $c['life_code'] ?? '' ) ),
		);
	}
	update_option( 'semrs_us_campaigns', $out, false );
	$pin  = (array) wp_unslash( $_POST['p'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field sanitized below.
	$pops = array();
	foreach ( array_keys( semrs_us_popup_defaults() ) as $id ) {
		$p           = (array) ( $pin[ $id ] ?? array() );
		$num         = function ( $k ) use ( $p ) {
			return '' === trim( (string) ( $p[ $k ] ?? '' ) ) ? '' : min( 3650, absint( $p[ $k ] ) );
		};
		$pops[ $id ] = array(
			'on'     => empty( $p['on'] ) ? 0 : 1,
			'title'  => sanitize_text_field( (string) ( $p['title'] ?? '' ) ),
			'text'   => sanitize_text_field( (string) ( $p['text'] ?? '' ) ),
			'cta'    => sanitize_text_field( (string) ( $p['cta'] ?? '' ) ),
			'img'    => sanitize_text_field( (string) ( $p['img'] ?? '' ) ),
			'delay'  => $num( 'delay' ),
			'scroll' => '' === $num( 'scroll' ) ? '' : min( 100, $num( 'scroll' ) ),
			'days'   => $num( 'days' ),
		);
	}
	update_option( 'semrs_us_popups', $pops, false );
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-updates-campaigns', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_us_campaigns', 'semrs_us_campaigns_save' );

/**
 * Campaigns screen.
 */
function semrs_us_page_campaigns() {
	$set    = semrs_us_campaign_settings();
	$all    = semrs_us_campaigns();
	$online = semrs_us_online_checkout();
	$now    = time();
	$fmt    = function ( $ts ) {
		return wp_date( 'D, M j, Y', $ts );
	};
	$live   = semrs_us_campaigns_live();
	$sale   = semrs_us_sale_now();
	?>
	<div class="wrap semrs-camp">
		<h1><?php esc_html_e( 'Campaigns & popups', 'semrs-update-server' ); ?></h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Saved. Cached pages were cleared so the change shows right away.', 'semrs-update-server' ); ?></p></div>
		<?php endif; ?>
		<style>.semrs-camp .card{max-width:1100px}.semrs-camp .st{display:inline-block;padding:1px 8px;border-radius:99px;font-size:12px;font-weight:600}.semrs-camp .st-live{background:#d1fae5;color:#065f46}.semrs-camp .st-next{background:#e0e7ff;color:#3730a3}.semrs-camp .st-warn{background:#fef3c7;color:#92400e}.semrs-camp .st-off{background:#f3f4f6;color:#4b5563}.semrs-camp td input[type=text]{width:100%}.semrs-camp h2{margin-top:2em}.semrs-camp .guide li{margin-bottom:6px}</style>

		<div class="card">
			<h2 style="margin-top:.5em"><?php esc_html_e( 'Today', 'semrs-update-server' ); ?></h2>
			<p>
				<?php if ( ! $set['on'] ) : ?>
					<span class="st st-off"><?php esc_html_e( 'All campaigns and popups are paused', 'semrs-update-server' ); ?></span>
				<?php elseif ( $sale ) : ?>
					<span class="st st-live"><?php esc_html_e( 'Live', 'semrs-update-server' ); ?></span> <strong><?php echo esc_html( $sale['name'] . ' · ' . $sale['percent'] . '% · ' . $sale['code'] ); ?></strong> — <?php echo esc_html( sprintf( /* translators: %s: date */ __( 'ends %s', 'semrs-update-server' ), $fmt( $sale['window'][1] ) ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'No seasonal sale is running. Always-on deals:', 'semrs-update-server' ); ?> <strong><?php echo esc_html( implode( ', ', wp_list_pluck( $live, 'name' ) ) ?: '—' ); ?></strong>
				<?php endif; ?>
			</p>
			<p><?php echo esc_html( sprintf( /* translators: %s: timezone */ __( 'Dates use the site timezone (%s). Checkout:', 'semrs-update-server' ), wp_timezone_string() ) ); ?> <strong><?php echo $online ? esc_html__( 'online (Paddle or Lemon Squeezy): every code must be created there first', 'semrs-update-server' ) : esc_html__( 'invoices only: the percent goes on the invoice', 'semrs-update-server' ); ?></strong></p>
		</div>

		<h2><?php esc_html_e( 'Sales calendar (next 12 months)', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped" style="max-width:1100px"><thead><tr><th><?php esc_html_e( 'Campaign', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Starts', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Ends', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Discount', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Code', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Popups', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php foreach ( semrs_us_campaign_schedule( 366 ) as $c ) : ?>
			<?php
			$running = $c['window'][0] <= $now;
			if ( empty( $c['enabled'] ) ) {
				$st = array( 'off', __( 'Off', 'semrs-update-server' ) );
			} elseif ( ! semrs_us_campaign_usable( $c ) ) {
				$st = array( 'warn', __( 'Create the code, then tick "Code created"', 'semrs-update-server' ) );
			} else {
				$st = $running ? array( 'live', __( 'Live now', 'semrs-update-server' ) ) : array( 'next', __( 'Scheduled', 'semrs-update-server' ) );
			}
			?>
			<tr>
				<td><strong><?php echo esc_html( $c['name'] ); ?></strong></td>
				<td><?php echo esc_html( $fmt( $c['window'][0] ) ); ?></td>
				<td><?php echo esc_html( $fmt( $c['window'][1] ) ); ?></td>
				<td><?php echo esc_html( $c['percent'] . '%' ); ?></td>
				<td><code><?php echo esc_html( $c['code'] ); ?></code></td>
				<td><span class="st st-<?php echo esc_attr( $st[0] ); ?>"><?php echo esc_html( $st[1] ); ?></span></td>
				<td><a href="<?php echo esc_url( add_query_arg( 'semrs_preview', $c['id'], strtok( semrs_us_pricing_url(), '#' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview', 'semrs-update-server' ); ?></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
		<p class="description"><?php esc_html_e( 'Preview opens the pricing page with that campaign\'s popup and sale bar (only you see it). Black Friday and Cyber Monday move with the calendar; the others keep their dates every year.', 'semrs-update-server' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_us_campaigns' ); ?>
			<input type="hidden" name="action" value="semrs_us_campaigns">
			<h2><?php esc_html_e( 'Campaign settings', 'semrs-update-server' ); ?></h2>
			<p><label><input type="checkbox" name="on" value="1" <?php checked( $set['on'] ); ?>> <strong><?php esc_html_e( 'Run campaigns and popups', 'semrs-update-server' ); ?></strong></label> — <?php esc_html_e( 'untick to pause everything at once.', 'semrs-update-server' ); ?></p>
			<table class="widefat striped" style="max-width:1400px"><thead><tr><th><?php esc_html_e( 'On', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Campaign', 'semrs-update-server' ); ?></th><th><?php esc_html_e( '% off', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Code', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Code created', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Lifetime % / code', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Dates (MM-DD, empty = default)', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Headline (%d = percent)', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Text', 'semrs-update-server' ); ?></th></tr></thead><tbody>
			<?php foreach ( $all as $id => $c ) : ?>
				<?php $n = 'c[' . $id . ']'; $sv = $set['items'][ $id ] ?? array(); ?>
				<tr>
					<td><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[enabled]" value="1" <?php checked( ! empty( $c['enabled'] ) ); ?> aria-label="<?php echo esc_attr( $c['name'] ); ?>"></td>
					<td><strong><?php echo esc_html( $c['name'] ); ?></strong><br><small><?php echo esc_html( $c['season'] ? __( 'Seasonal: code goes on every Buy button', 'semrs-update-server' ) : ( $c['auto'] ? __( 'Always on: code goes on its plans\' Buy buttons', 'semrs-update-server' ) : __( 'Always on: code shown in a popup', 'semrs-update-server' ) ) ); ?></small></td>
					<td><input type="number" min="0" max="90" name="<?php echo esc_attr( $n ); ?>[percent]" value="<?php echo esc_attr( $sv['percent'] ?? '' ); ?>" placeholder="<?php echo esc_attr( semrs_us_campaign_defaults()[ $id ]['percent'] ); ?>" style="width:70px"></td>
					<td><input type="text" name="<?php echo esc_attr( $n ); ?>[code]" value="<?php echo esc_attr( $sv['code'] ?? '' ); ?>" placeholder="<?php echo esc_attr( semrs_us_campaign_defaults()[ $id ]['code'] ); ?>" style="width:150px"></td>
					<td><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[ready]" value="1" <?php checked( ! empty( $c['ready'] ) ); ?> aria-label="<?php esc_attr_e( 'Code created in checkout', 'semrs-update-server' ); ?>"></td>
					<td><?php if ( $c['season'] ) : ?><input type="number" min="0" max="90" name="<?php echo esc_attr( $n ); ?>[life]" value="<?php echo esc_attr( $sv['life'] ?? '' ); ?>" placeholder="<?php echo esc_attr( semrs_us_campaign_defaults()[ $id ]['life'] ?? '' ); ?>" style="width:60px" aria-label="<?php esc_attr_e( 'Lifetime percent', 'semrs-update-server' ); ?>"> <input type="text" name="<?php echo esc_attr( $n ); ?>[life_code]" value="<?php echo esc_attr( $sv['life_code'] ?? '' ); ?>" placeholder="<?php echo esc_attr( semrs_us_campaign_defaults()[ $id ]['life_code'] ?? '' ); ?>" style="width:110px" aria-label="<?php esc_attr_e( 'Lifetime code', 'semrs-update-server' ); ?>"><?php else : ?>—<?php endif; ?></td>
					<td><?php if ( $c['season'] ) : ?><input type="text" name="<?php echo esc_attr( $n ); ?>[start]" value="<?php echo esc_attr( $sv['start'] ?? '' ); ?>" placeholder="MM-DD" style="width:70px"> – <input type="text" name="<?php echo esc_attr( $n ); ?>[end]" value="<?php echo esc_attr( $sv['end'] ?? '' ); ?>" placeholder="MM-DD" style="width:70px"><?php else : ?>—<?php endif; ?></td>
					<td><input type="text" name="<?php echo esc_attr( $n ); ?>[headline]" value="<?php echo esc_attr( $sv['headline'] ?? '' ); ?>" placeholder="<?php echo esc_attr( semrs_us_campaign_defaults()[ $id ]['headline'] ); ?>"></td>
					<td><input type="text" name="<?php echo esc_attr( $n ); ?>[text]" value="<?php echo esc_attr( $sv['text'] ?? '' ); ?>" placeholder="<?php echo esc_attr( semrs_us_campaign_defaults()[ $id ]['text'] ); ?>"></td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>

			<h2 id="popups"><?php esc_html_e( 'Popups', 'semrs-update-server' ); ?></h2>
			<p><?php esc_html_e( 'Every popup switches on and off by itself with the calendar above. Change any wording, button, timing or frequency here; leave a box empty to keep the default (shown in grey). In the sale popup and bar, %d is the percent; empty title and text use the headline and text of each campaign, so one setting covers every sale.', 'semrs-update-server' ); ?></p>
			<table class="widefat striped" style="max-width:1400px"><thead><tr><th><?php esc_html_e( 'On', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Popup', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Title', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Text', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Button', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Photos (files or URLs, space-separated)', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Delay (s)', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Scroll %', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Hide for (days)', 'semrs-update-server' ); ?></th></tr></thead><tbody>
			<?php $psaved = get_option( 'semrs_us_popups', array() ); ?>
			<?php foreach ( semrs_us_popup_defaults() as $id => $def ) : ?>
				<?php $pc = semrs_us_popup_conf( $id ); $pv = is_array( $psaved ) && isset( $psaved[ $id ] ) ? $psaved[ $id ] : array(); $pn = 'p[' . $id . ']'; ?>
				<tr>
					<td><input type="checkbox" name="<?php echo esc_attr( $pn ); ?>[on]" value="1" <?php checked( ! empty( $pc['on'] ) ); ?> aria-label="<?php echo esc_attr( $def['name'] ); ?>"></td>
					<td><strong><?php echo esc_html( $def['name'] ); ?></strong></td>
					<?php $def['img'] = semrs_us_popup_default_images()[ $id ] ?? ''; ?>
					<?php foreach ( array( 'title', 'text', 'cta', 'img' ) as $k ) : ?>
						<td><input type="text" name="<?php echo esc_attr( $pn . '[' . $k . ']' ); ?>" value="<?php echo esc_attr( $pv[ $k ] ?? '' ); ?>" placeholder="<?php echo esc_attr( '' !== $def[ $k ] ? $def[ $k ] : ( in_array( $id, array( 'sale', 'bar', 'welcome', 'agency', 'switch' ), true ) && ! in_array( $k, array( 'cta', 'img' ), true ) ? __( '(campaign text)', 'semrs-update-server' ) : '' ) ); ?>"></td>
					<?php endforeach; ?>
					<?php foreach ( array( 'delay', 'scroll', 'days' ) as $k ) : ?>
						<td><input type="number" min="0" name="<?php echo esc_attr( $pn . '[' . $k . ']' ); ?>" value="<?php echo esc_attr( $pv[ $k ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $def[ $k ] ); ?>" style="width:70px"></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<p class="description"><?php esc_html_e( 'Delay or scroll: whichever comes first opens the popup (0 = not used). The sale bar always waits for its delay and stays hidden for the rest of a sale once closed. Lifetime text: %s is the price. Social proof: %s is the number of sites. Affiliate: %d is the first-order rate, then %1$d renewal rate and %2$d cookie days.', 'semrs-update-server' ); ?></p>
			<?php submit_button(); ?>
		</form>

		<?php semrs_us_campaign_guide(); ?>
	</div>
	<?php
}

/**
 * The popup guide (for the CEO and anyone running sales).
 */
function semrs_us_campaign_guide() {
	$aff = semrs_us_aff_settings();
	$pop = array(
		array( __( 'Sale popup', 'semrs-update-server' ), __( 'During each seasonal sale', 'semrs-update-server' ), __( 'Pages without ads: after 12 seconds or 35% scroll. Pages with ads: only when a desktop visitor moves to leave. Phones: from the 2nd page, as a short bottom sheet.', 'semrs-update-server' ), __( 'Once every 2 days per sale', 'semrs-update-server' ) ),
		array( __( 'Sale bar with countdown', 'semrs-update-server' ), __( 'During each seasonal sale', 'semrs-update-server' ), __( 'Bottom of every page without ads, after 2 seconds. Counts down to the real end time.', 'semrs-update-server' ), __( 'Until closed, then hidden for that sale', 'semrs-update-server' ) ),
		array( __( 'Welcome offer (15%)', 'semrs-update-server' ), __( 'When no sale runs', 'semrs-update-server' ), __( 'Sales pages: exit intent on desktop, or after 45 seconds from the 2nd page.', 'semrs-update-server' ), __( 'Once every 30 days', 'semrs-update-server' ) ),
		array( __( 'Agency deal (25% on unlimited)', 'semrs-update-server' ), __( 'Always', 'semrs-update-server' ), __( 'Corner slide-in on sales pages at 60% scroll.', 'semrs-update-server' ), __( 'Once every 14 days', 'semrs-update-server' ) ),
		array( __( 'Switch deal (20%)', 'semrs-update-server' ), __( 'When no sale runs', 'semrs-update-server' ), __( 'Corner slide-in on sales pages (not pricing) after 25 seconds.', 'semrs-update-server' ), __( 'Once every 21 days', 'semrs-update-server' ) ),
		array( __( 'Lifetime nudge', 'semrs-update-server' ), __( 'When no sale runs', 'semrs-update-server' ), __( 'Pricing page at 70% scroll.', 'semrs-update-server' ), __( 'Once every 14 days', 'semrs-update-server' ) ),
		array( __( 'Social proof', 'semrs-update-server' ), __( 'Always (20+ live sites)', 'semrs-update-server' ), __( 'Pricing page after 6 seconds: the real number of sites that ran SEMRS in the last 30 days. Hides itself after 9 seconds.', 'semrs-update-server' ), __( 'Once every 7 days', 'semrs-update-server' ) ),
		array( __( 'Affiliate invite', 'semrs-update-server' ), __( 'Always (needs the Affiliates page)', 'semrs-update-server' ), __( 'Blog home and info pages without ads, from the 3rd page, after 20 seconds.', 'semrs-update-server' ), __( 'Once every 30 days', 'semrs-update-server' ) ),
	);
	?>
	<div class="guide" style="max-width:1100px">
		<h2 id="guide"><?php esc_html_e( 'Popup guide', 'semrs-update-server' ); ?></h2>
		<p><?php esc_html_e( 'Everything below runs by itself. This guide explains what visitors see, when, and the few things to do before each sale.', 'semrs-update-server' ); ?></p>

		<h3><?php esc_html_e( '1. The popups', 'semrs-update-server' ); ?></h3>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Popup', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'When', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Where and how it opens', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'How often', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php foreach ( $pop as $r ) : ?>
			<tr><td><strong><?php echo esc_html( $r[0] ); ?></strong></td><td><?php echo esc_html( $r[1] ); ?></td><td><?php echo esc_html( $r[2] ); ?></td><td><?php echo esc_html( $r[3] ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<p><?php esc_html_e( 'Times and texts above are the defaults: the Popups table on this screen shows what is set now, and every change there takes effect right away.', 'semrs-update-server' ); ?></p>
		<p><?php esc_html_e( 'A visitor sees at most one popup per visit (the sale bar can show alongside it). Logged-in users, checkout, My Licenses, the quote form, contact and legal pages never get popups.', 'semrs-update-server' ); ?></p>

		<h3><?php esc_html_e( '2. Before each sale (10 minutes)', 'semrs-update-server' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Check the sale in the calendar above: dates, percent and code.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Paddle → Catalog → Discounts → New discount: code exactly as shown, percentage, "Apply to all SEMRS prices", recurring OFF (first payment only), expires on the sale\'s end date. Lemon Squeezy: Store → Discounts with the same settings.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Tick "Code created" for that campaign and save. Until then the sale stays hidden while an online checkout is on, so no visitor sees a price the checkout would refuse.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Click Preview and check the popup and bar on desktop and phone.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Nothing else: the sale starts and ends on time, Buy buttons carry the code, the pricing page shows sale prices, and cached pages are cleared at the start and end.', 'semrs-update-server' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'Invoices only (no online checkout): nothing to create. The quote page tells customers the discount, and the Invoices screen fills the percent in for you.', 'semrs-update-server' ); ?></p>

		<h3><?php esc_html_e( '3. Prices and deals at a glance', 'semrs-update-server' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Regular prices are set 10–20% below the cheapest competitor at each tier (regular prices, not their sales) (checked October 31, 2026). Change them under Sales & prices; re-check competitors twice a year.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Black Friday and Cyber Monday: 45% on yearly plans and 50% on lifetime plans (codes BFLIFE50 / CMLIFE50), our lowest prices of the year. Christmas 35%, New Year 30%, Halloween 30%, WordPress birthday 27%, Spring, Summer and Back to school 25%.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Agency deal: 25% off All Pro unlimited (yearly and lifetime), every day, applied by itself. A seasonal sale replaces it while it runs (codes do not stack).', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Welcome 15% and Switch 20% are codes the visitor types at checkout. They pause during seasonal sales.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Discounts apply to the first payment. Every renewal then gets the loyalty discount (20% by default, Sales & prices), while most competitors renew at full price.', 'semrs-update-server' ); ?></li>
		</ul>

		<h3><?php esc_html_e( '4. Affiliate program', 'semrs-update-server' ); ?></h3>
		<ul>
			<li><?php echo esc_html( sprintf( /* translators: 1: first percent, 2: renewal percent, 3: cookie days */ __( 'Partners earn %1$d%% of the first order and %2$d%% of every renewal and add-on for life, with a %3$d-day cookie. Most WordPress programs pay 20–30% with a 30–60 day cookie.', 'semrs-update-server' ), $aff['first'], $aff['renewal'], $aff['cookie'] ) ); ?></li>
			<li><?php esc_html_e( 'Approve applications under SEMRS Updates → Affiliates. Approved partners get their link and private dashboard by email.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'Commissions approve themselves after the refund window. Pay monthly (PayPal, Wise or bank) and click "Mark paid".', 'semrs-update-server' ); ?></li>
		</ul>

		<h3><?php esc_html_e( '5. Rules we never break', 'semrs-update-server' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'No fake countdowns, fake "only 3 left" or made-up buyer notifications. The social-proof number is real update-check data.', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'No full-screen popups on phones and nothing on page load (Google\'s intrusive interstitial rule).', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'No ads inside popups and no bar or slide-in on pages with ads (AdSense policy).', 'semrs-update-server' ); ?></li>
			<li><?php esc_html_e( 'PageSpeed: about 4 KB inline, no extra files, starts when the browser is idle, no layout shift. Scores stay at 100.', 'semrs-update-server' ); ?></li>
		</ul>
	</div>
	<?php
}

/**
 * Affiliate admin actions (settings, partners, commissions).
 */
function semrs_us_aff_admin_post() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-update-server' ) );
	}
	check_admin_referer( 'semrs_us_aff' );
	$do   = sanitize_key( wp_unslash( $_POST['semrs_do'] ?? '' ) );
	$code = sanitize_key( wp_unslash( $_POST['code'] ?? '' ) );
	$all  = semrs_us_affiliates();
	if ( 'settings' === $do ) {
		update_option(
			'semrs_us_aff_settings',
			array(
				'on'      => empty( $_POST['on'] ) ? 0 : 1,
				'first'   => min( 90, absint( $_POST['first'] ?? 50 ) ),
				'renewal' => min( 90, absint( $_POST['renewal'] ?? 30 ) ),
				'cookie'  => max( 1, min( 365, absint( $_POST['cookie'] ?? 90 ) ) ),
				'min'     => absint( $_POST['min'] ?? 50 ),
				'hold'    => min( 90, absint( $_POST['hold'] ?? 30 ) ),
				'page'    => absint( $_POST['page'] ?? 0 ),
			),
			false
		);
	} elseif ( 'add' === $do ) {
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		if ( is_email( $email ) ) {
			$code = $code ? $code : semrs_us_aff_new_code( $name ? $name : strtok( $email, '@' ) );
			if ( ! isset( $all[ $code ] ) ) {
				$a = array( 'name' => $name, 'email' => $email, 'site' => '', 'how' => '', 'payout' => sanitize_text_field( wp_unslash( $_POST['payout'] ?? '' ) ), 'status' => 'active', 'token' => bin2hex( random_bytes( 16 ) ), 'created' => gmdate( 'Y-m-d' ) );
				semrs_us_aff_save( $code, $a );
				semrs_us_aff_mail_welcome( $code, $a );
			}
		}
	} elseif ( isset( $all[ $code ] ) && in_array( $do, array( 'approve', 'block', 'resend' ), true ) ) {
		if ( 'resend' !== $do ) {
			$all[ $code ]['status'] = 'approve' === $do ? 'active' : 'blocked';
			semrs_us_aff_save( $code, $all[ $code ] );
		}
		if ( 'block' !== $do ) {
			semrs_us_aff_mail_welcome( $code, $all[ $code ] );
		}
	} elseif ( isset( $all[ $code ] ) && 'paid' === $do ) {
		$rows = semrs_us_referrals();
		foreach ( $rows as &$r ) {
			if ( $r['aff'] === $code && 'approved' === $r['status'] ) {
				$r['status'] = 'paid';
				$r['paid']   = gmdate( 'Y-m-d' );
			}
		}
		unset( $r );
		semrs_us_referrals_save( $rows );
	} elseif ( in_array( $do, array( 'r-approve', 'r-reject' ), true ) ) {
		$rid  = sanitize_key( wp_unslash( $_POST['rid'] ?? '' ) );
		$rows = semrs_us_referrals();
		foreach ( $rows as &$r ) {
			if ( $r['id'] === $rid && 'paid' !== $r['status'] ) {
				$r['status'] = 'r-approve' === $do ? 'approved' : 'rejected';
			}
		}
		unset( $r );
		semrs_us_referrals_save( $rows );
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-updates-affiliates', 'done' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_us_aff', 'semrs_us_aff_admin_post' );

/**
 * Affiliates screen.
 */
function semrs_us_page_affiliates() {
	$s    = semrs_us_aff_settings();
	$all  = semrs_us_affiliates();
	$form = function ( $do, $label, $fields = '', $class = 'button button-small' ) {
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">' . wp_nonce_field( 'semrs_us_aff', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="semrs_us_aff"><input type="hidden" name="semrs_do" value="' . esc_attr( $do ) . '">' . $fields . '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Affiliates', 'semrs-update-server' ); ?></h1>
		<?php if ( isset( $_GET['done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Done.', 'semrs-update-server' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! semrs_us_aff_page_url() ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Publish a page at /affiliates/ with the [semrs_affiliates] shortcode (or pick one below) so partners can apply and see their dashboard.', 'semrs-update-server' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Partners', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped" style="max-width:1300px"><thead><tr><th><?php esc_html_e( 'Code', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Name / email', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Website and plan', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Payout to', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Clicks / sales', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Pending / to pay / paid', 'semrs-update-server' ); ?></th><th></th></tr></thead><tbody>
		<?php if ( ! $all ) : ?><tr><td colspan="8"><?php esc_html_e( 'No partners yet.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( $all as $code => $a ) : ?>
			<?php $t = semrs_us_aff_totals( $code ); $hid = '<input type="hidden" name="code" value="' . esc_attr( $code ) . '">'; ?>
			<tr>
				<td><code><?php echo esc_html( $code ); ?></code></td>
				<td><?php echo esc_html( $a['name'] ); ?><br><small><?php echo esc_html( $a['email'] ); ?></small></td>
				<td><?php echo $a['site'] ? '<a href="' . esc_url( $a['site'] ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $a['site'] ) . '</a>' : '—'; ?><?php echo $a['how'] ? '<br><small>' . esc_html( $a['how'] ) . '</small>' : ''; ?></td>
				<td><?php echo esc_html( $a['payout'] ? $a['payout'] : '—' ); ?></td>
				<td><?php echo esc_html( ucfirst( $a['status'] ) ); ?></td>
				<td><?php echo esc_html( $t['clicks'] . ' / ' . $t['sales'] ); ?></td>
				<td><?php echo esc_html( semrs_us_money( $t['pending'] ) . ' / ' . semrs_us_money( $t['approved'] ) . ' / ' . semrs_us_money( $t['paid'] ) ); ?></td>
				<td>
					<?php
					if ( 'active' !== $a['status'] ) {
						echo $form( 'approve', __( 'Approve', 'semrs-update-server' ), $hid, 'button button-small button-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} else {
						echo $form( 'resend', __( 'Email links', 'semrs-update-server' ), $hid ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo $form( 'block', __( 'Block', 'semrs-update-server' ), $hid ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					if ( $t['approved'] > 0 ) {
						echo $form( 'paid', sprintf( /* translators: %s: amount */ __( 'Mark %s paid', 'semrs-update-server' ), semrs_us_money( $t['approved'] ) ), $hid ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Add a partner by hand', 'semrs-update-server' ); ?></h3>
		<?php echo $form( 'add', __( 'Add and email their links', 'semrs-update-server' ), '<input name="name" placeholder="' . esc_attr__( 'Name', 'semrs-update-server' ) . '"> <input type="email" name="email" required placeholder="' . esc_attr__( 'Email', 'semrs-update-server' ) . '"> <input name="code" placeholder="' . esc_attr__( 'Code (optional)', 'semrs-update-server' ) . '" style="width:130px"> <input name="payout" placeholder="' . esc_attr__( 'PayPal / Wise email', 'semrs-update-server' ) . '"> ', 'button' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<h2><?php esc_html_e( 'Commissions (latest 200)', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped" style="max-width:1300px"><thead><tr><th><?php esc_html_e( 'Date', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Partner', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Order', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Plan', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Sale', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Commission', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-update-server' ); ?></th><th></th></tr></thead><tbody>
		<?php $rows = array_slice( semrs_us_referrals(), 0, 200 ); ?>
		<?php if ( ! $rows ) : ?><tr><td colspan="8"><?php esc_html_e( 'No commissions yet.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( $rows as $r ) : ?>
			<tr>
				<td><?php echo esc_html( $r['date'] ); ?></td>
				<td><code><?php echo esc_html( $r['aff'] ); ?></code></td>
				<td><small><?php echo esc_html( $r['ref'] ); ?></small></td>
				<td><?php echo esc_html( $r['plan'] . ' · ' . ( 'first' === $r['kind'] ? __( 'first order', 'semrs-update-server' ) : __( 'renewal / add-on', 'semrs-update-server' ) ) ); ?></td>
				<td><?php echo esc_html( semrs_us_money( $r['amount'] ) ); ?></td>
				<td><?php echo esc_html( semrs_us_money( $r['com'] ) . ' (' . $r['rate'] . '%)' ); ?></td>
				<td><?php echo esc_html( ucfirst( $r['status'] ) . ( $r['paid'] ? ' ' . $r['paid'] : '' ) ); ?></td>
				<td>
					<?php
					$hid = '<input type="hidden" name="rid" value="' . esc_attr( $r['id'] ) . '">';
					if ( 'pending' === $r['status'] || 'rejected' === $r['status'] ) {
						echo $form( 'r-approve', __( 'Approve', 'semrs-update-server' ), $hid ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					if ( 'pending' === $r['status'] || 'approved' === $r['status'] ) {
						echo $form( 'r-reject', __( 'Reject', 'semrs-update-server' ), $hid ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>

		<h2><?php esc_html_e( 'Program settings', 'semrs-update-server' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_us_aff' ); ?>
			<input type="hidden" name="action" value="semrs_us_aff"><input type="hidden" name="semrs_do" value="settings">
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Program', 'semrs-update-server' ); ?></th><td><label><input type="checkbox" name="on" value="1" <?php checked( $s['on'] ); ?>> <?php esc_html_e( 'Open (tracking, applications and commissions)', 'semrs-update-server' ); ?></label></td></tr>
				<tr><th><label for="aff-first"><?php esc_html_e( 'First order commission', 'semrs-update-server' ); ?></label></th><td><input id="aff-first" type="number" min="0" max="90" name="first" value="<?php echo esc_attr( $s['first'] ); ?>" style="width:70px"> % <span class="description"><?php esc_html_e( 'Market: 20–30% for most WordPress plugins and themes.', 'semrs-update-server' ); ?></span></td></tr>
				<tr><th><label for="aff-ren"><?php esc_html_e( 'Renewals and add-ons, for life', 'semrs-update-server' ); ?></label></th><td><input id="aff-ren" type="number" min="0" max="90" name="renewal" value="<?php echo esc_attr( $s['renewal'] ); ?>" style="width:70px"> % <span class="description"><?php esc_html_e( 'Most programs pay nothing on renewals.', 'semrs-update-server' ); ?></span></td></tr>
				<tr><th><label for="aff-cookie"><?php esc_html_e( 'Cookie length (days)', 'semrs-update-server' ); ?></label></th><td><input id="aff-cookie" type="number" min="1" max="365" name="cookie" value="<?php echo esc_attr( $s['cookie'] ); ?>" style="width:70px"> <span class="description"><?php esc_html_e( 'Market: 30–60 days.', 'semrs-update-server' ); ?></span></td></tr>
				<tr><th><label for="aff-hold"><?php esc_html_e( 'Approve after (days)', 'semrs-update-server' ); ?></label></th><td><input id="aff-hold" type="number" min="0" max="90" name="hold" value="<?php echo esc_attr( $s['hold'] ); ?>" style="width:70px"> <span class="description"><?php esc_html_e( 'Match the refund window.', 'semrs-update-server' ); ?></span></td></tr>
				<tr><th><label for="aff-min"><?php esc_html_e( 'Minimum payout', 'semrs-update-server' ); ?></label></th><td><input id="aff-min" type="number" min="0" name="min" value="<?php echo esc_attr( $s['min'] ); ?>" style="width:90px"></td></tr>
				<tr><th><label for="aff-page"><?php esc_html_e( 'Affiliates page', 'semrs-update-server' ); ?></label></th><td><?php wp_dropdown_pages( array( 'name' => 'page', 'id' => 'aff-page', 'selected' => (int) $s['page'], 'show_option_none' => __( '— /affiliates/ —', 'semrs-update-server' ), 'option_none_value' => 0 ) ); ?></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
