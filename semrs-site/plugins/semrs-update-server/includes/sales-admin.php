<?php
/**
 * SEMRS Updates → Sales & prices: Lemon Squeezy connection, the price and
 * checkout link of every plan, the launch offer, and invoice details.
 *
 * @package semrs-update-server
 */

defined( 'ABSPATH' ) || exit;

/**
 * Save the settings.
 */
function semrs_us_sales_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-update-server' ) );
	}
	check_admin_referer( 'semrs_us_sales' );
	$in  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field sanitized below.
	$old = semrs_us_sales();
	$s   = array(
		// An empty secret field keeps the saved secret (it is never shown again).
		'ls_secret'        => '' !== trim( (string) ( $in['ls_secret'] ?? '' ) ) ? sanitize_text_field( $in['ls_secret'] ) : $old['ls_secret'],
		'currency'         => preg_match( '/^[A-Z]{3}$/', strtoupper( (string) ( $in['currency'] ?? '' ) ) ) ? strtoupper( $in['currency'] ) : 'USD',
		'offer_code'       => preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $in['offer_code'] ?? '' ) ),
		'offer_percent'    => min( 90, absint( $in['offer_percent'] ?? 0 ) ),
		'offer_ends'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['offer_ends'] ?? '' ) ) ? $in['offer_ends'] : '',
		'offer_label'      => sanitize_text_field( (string) ( $in['offer_label'] ?? '' ) ),
		'refund_days'      => min( 60, absint( $in['refund_days'] ?? 0 ) ),
		'seller_name'      => sanitize_text_field( (string) ( $in['seller_name'] ?? '' ) ),
		'seller_address'   => sanitize_textarea_field( (string) ( $in['seller_address'] ?? '' ) ),
		'seller_email'     => sanitize_email( (string) ( $in['seller_email'] ?? '' ) ),
		'seller_tax'       => sanitize_text_field( (string) ( $in['seller_tax'] ?? '' ) ),
		'pay_instructions' => sanitize_textarea_field( (string) ( $in['pay_instructions'] ?? '' ) ),
		'account_page'     => absint( $in['account_page'] ?? 0 ),
		'quote_page'       => absint( $in['quote_page'] ?? 0 ),
		'plans'            => array(),
	);
	if ( ! empty( $in['ls_secret_clear'] ) ) {
		$s['ls_secret'] = '';
	}
	foreach ( array_keys( semrs_us_default_prices() ) as $id ) {
		$p                  = (array) ( $in['plans'][ $id ] ?? array() );
		$s['plans'][ $id ] = array(
			'price'    => isset( $p['price'] ) && '' !== $p['price'] ? (string) round( (float) $p['price'], 2 ) : '',
			'variant'  => preg_replace( '/[^0-9]/', '', (string) ( $p['variant'] ?? '' ) ),
			'checkout' => esc_url_raw( (string) ( $p['checkout'] ?? '' ), array( 'https' ) ),
		);
	}
	update_option( 'semrs_us_sales', $s, false );
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-updates-sales', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_us_sales', 'semrs_us_sales_save' );

/**
 * The screen.
 */
function semrs_us_page_sales() {
	$s     = semrs_us_sales();
	$plans = semrs_us_plans();
	$log   = get_option( 'semrs_us_ls_log', array() );
	$pages = get_pages( array( 'post_status' => 'publish' ) );
	$page_select = function ( $name, $current ) use ( $pages ) {
		$out = '<select name="' . esc_attr( $name ) . '"><option value="0">—</option>';
		foreach ( $pages as $pg ) {
			$out .= '<option value="' . (int) $pg->ID . '" ' . selected( (int) $current, (int) $pg->ID, false ) . '>' . esc_html( $pg->post_title ) . '</option>';
		}
		return $out . '</select>';
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sales & prices', 'semrs-update-server' ); ?></h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Saved.', 'semrs-update-server' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_us_sales' ); ?>
			<input type="hidden" name="action" value="semrs_us_sales">

			<h2><?php esc_html_e( '1. Lemon Squeezy', 'semrs-update-server' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'In Lemon Squeezy, create a product "SEMRS Pro" with one variant per yearly plan below (subscription, yearly), and a product "SEMRS Pro Lifetime" with the two lifetime variants (single payment). Use the prices from the table.', 'semrs-update-server' ); ?></li>
				<li><?php esc_html_e( 'Settings → Webhooks → add this URL, pick a signing secret, and tick: order_created, order_refunded, subscription_created, subscription_updated, subscription_cancelled, subscription_resumed, subscription_expired.', 'semrs-update-server' ); ?><br><code><?php echo esc_html( rest_url( 'semrs-updates/v1/lemonsqueezy' ) ); ?></code></li>
				<li><?php esc_html_e( 'Paste the same signing secret here, and each variant\'s ID and checkout link (variant → Share) in the table.', 'semrs-update-server' ); ?></li>
				<li><?php esc_html_e( 'Turn off Lemon Squeezy\'s own license keys for these products: SEMRS makes and emails the keys.', 'semrs-update-server' ); ?></li>
			</ol>
			<table class="form-table" role="presentation">
				<tr><th><label for="ls-secret"><?php esc_html_e( 'Webhook signing secret', 'semrs-update-server' ); ?></label></th><td><input id="ls-secret" type="password" name="ls_secret" class="regular-text" autocomplete="new-password" placeholder="<?php echo $s['ls_secret'] ? esc_attr__( 'Saved. Type a new one to replace it.', 'semrs-update-server' ) : ''; ?>"> <?php if ( $s['ls_secret'] ) : ?><label><input type="checkbox" name="ls_secret_clear" value="1"> <?php esc_html_e( 'Remove', 'semrs-update-server' ); ?></label><?php endif; ?></td></tr>
				<tr><th><label for="currency"><?php esc_html_e( 'Currency', 'semrs-update-server' ); ?></label></th><td><input id="currency" name="currency" value="<?php echo esc_attr( $s['currency'] ); ?>" style="width:70px"></td></tr>
			</table>

			<h2><?php esc_html_e( '2. Plans and prices', 'semrs-update-server' ); ?></h2>
			<p><?php esc_html_e( 'Without a checkout link, the Buy button sends the customer to your quote page with the plan filled in, and you send an invoice.', 'semrs-update-server' ); ?></p>
			<table class="widefat striped" style="max-width:1200px"><thead><tr><th><?php esc_html_e( 'Plan', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Price', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Lemon Squeezy variant ID', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Checkout link', 'semrs-update-server' ); ?></th></tr></thead><tbody>
			<?php foreach ( $plans as $id => $p ) : ?>
				<tr>
					<td><?php echo esc_html( semrs_us_plan_label( $p ) ); ?></td>
					<td><input type="number" step="0.01" min="0" name="plans[<?php echo esc_attr( $id ); ?>][price]" value="<?php echo esc_attr( $p['price'] ); ?>" style="width:90px"></td>
					<td><input name="plans[<?php echo esc_attr( $id ); ?>][variant]" value="<?php echo esc_attr( $p['variant'] ); ?>" style="width:120px" inputmode="numeric"></td>
					<td><input type="url" name="plans[<?php echo esc_attr( $id ); ?>][checkout]" value="<?php echo esc_attr( $p['checkout'] ); ?>" class="large-text" placeholder="https://semrs.lemonsqueezy.com/buy/…"></td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>

			<h2><?php esc_html_e( '3. Launch offer (optional)', 'semrs-update-server' ); ?></h2>
			<p><?php esc_html_e( 'Create the discount code in Lemon Squeezy first (Store → Discounts). The pricing page then shows the offer and every Buy button applies the code automatically. It hides itself after the end date.', 'semrs-update-server' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th><label for="offer-code"><?php esc_html_e( 'Discount code', 'semrs-update-server' ); ?></label></th><td><input id="offer-code" name="offer_code" value="<?php echo esc_attr( $s['offer_code'] ); ?>" placeholder="LAUNCH30"></td></tr>
				<tr><th><label for="offer-pct"><?php esc_html_e( 'Percent off (for display)', 'semrs-update-server' ); ?></label></th><td><input id="offer-pct" type="number" min="0" max="90" name="offer_percent" value="<?php echo esc_attr( $s['offer_percent'] ); ?>" style="width:80px"> %</td></tr>
				<tr><th><label for="offer-ends"><?php esc_html_e( 'Ends', 'semrs-update-server' ); ?></label></th><td><input id="offer-ends" type="date" name="offer_ends" value="<?php echo esc_attr( $s['offer_ends'] ); ?>"></td></tr>
				<tr><th><label for="offer-label"><?php esc_html_e( 'Offer text', 'semrs-update-server' ); ?></label></th><td><input id="offer-label" name="offer_label" value="<?php echo esc_attr( $s['offer_label'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Launch offer: 30% off your first year', 'semrs-update-server' ); ?>"></td></tr>
				<tr><th><label for="refund"><?php esc_html_e( 'Money-back guarantee (days)', 'semrs-update-server' ); ?></label></th><td><input id="refund" type="number" min="0" max="60" name="refund_days" value="<?php echo esc_attr( $s['refund_days'] ); ?>" style="width:80px"> <span class="description"><?php esc_html_e( '0 hides it. Only set this if your refund policy says so.', 'semrs-update-server' ); ?></span></td></tr>
			</table>

			<h2><?php esc_html_e( '4. Invoices and pages', 'semrs-update-server' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="seller-name"><?php esc_html_e( 'Business name', 'semrs-update-server' ); ?></label></th><td><input id="seller-name" name="seller_name" value="<?php echo esc_attr( $s['seller_name'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="seller-address"><?php esc_html_e( 'Business address', 'semrs-update-server' ); ?></label></th><td><textarea id="seller-address" name="seller_address" rows="3" class="large-text"><?php echo esc_textarea( $s['seller_address'] ); ?></textarea></td></tr>
				<tr><th><label for="seller-email"><?php esc_html_e( 'Sales email (reply-to)', 'semrs-update-server' ); ?></label></th><td><input id="seller-email" type="email" name="seller_email" value="<?php echo esc_attr( $s['seller_email'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="seller-tax"><?php esc_html_e( 'Tax / registration number', 'semrs-update-server' ); ?></label></th><td><input id="seller-tax" name="seller_tax" value="<?php echo esc_attr( $s['seller_tax'] ); ?>" class="regular-text"></td></tr>
				<tr><th><label for="pay"><?php esc_html_e( 'How to pay (shown on unpaid invoices)', 'semrs-update-server' ); ?></label></th><td><textarea id="pay" name="pay_instructions" rows="5" class="large-text" placeholder="<?php esc_attr_e( "Bank transfer: …\nPayPal: …\nWise: …", 'semrs-update-server' ); ?>"><?php echo esc_textarea( $s['pay_instructions'] ); ?></textarea></td></tr>
				<tr><th><?php esc_html_e( 'My Licenses page', 'semrs-update-server' ); ?></th><td><?php echo $page_select( 'account_page', $s['account_page'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in $page_select. ?> <span class="description"><?php esc_html_e( 'A page with the [semrs_my_licenses] shortcode.', 'semrs-update-server' ); ?></span></td></tr>
				<tr><th><?php esc_html_e( 'Quote / invoice request page', 'semrs-update-server' ); ?></th><td><?php echo $page_select( 'quote_page', $s['quote_page'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Recent Lemon Squeezy events', 'semrs-update-server' ); ?></h2>
		<table class="widefat striped" style="max-width:1000px"><thead><tr><th><?php esc_html_e( 'Time (UTC)', 'semrs-update-server' ); ?></th><th><?php esc_html_e( 'Event', 'semrs-update-server' ); ?></th><th>ID</th><th><?php esc_html_e( 'Result', 'semrs-update-server' ); ?></th></tr></thead><tbody>
		<?php if ( ! $log ) : ?><tr><td colspan="4"><?php esc_html_e( 'None yet. Use "Send test" on the webhook in Lemon Squeezy (test mode) to check the connection.', 'semrs-update-server' ); ?></td></tr><?php endif; ?>
		<?php foreach ( (array) $log as $row ) : ?>
			<tr><td><?php echo esc_html( $row[0] ); ?></td><td><?php echo esc_html( $row[1] . ( ! empty( $row[4] ) ? ' (test)' : '' ) ); ?></td><td><?php echo esc_html( $row[2] ); ?></td><td><?php echo esc_html( $row[3] ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}
