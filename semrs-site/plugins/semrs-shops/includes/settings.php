<?php
/**
 * Shop settings (option semrs_shops): currency, checkout link and what the
 * buy button does before a checkout is connected. Applied when shop pages are
 * shown, so imported shops work at once and every product follows one setting.
 *
 * @package semrs-shops
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_shops_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_shops', array() ),
		array(
			'currency' => 'USD',
			'checkout' => '',   // Link pattern; {product} = product page slug, {title} = product name.
			'fallback' => '1',  // No checkout yet: "Ask about this item" goes to the Contact page.
			'contact'  => '/contact/',
		)
	);
}

/**
 * Currencies the Product block shows with a symbol.
 *
 * @return string[]
 */
function semrs_shops_currencies() {
	return array( 'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'NZD', 'INR', 'PKR', 'AED', 'JPY' );
}

/**
 * Is this page built from a SEMRS shop (storefront class in its content)?
 *
 * @return bool
 */
function semrs_shops_is_shop_page() {
	static $is = null;
	if ( null === $is ) {
		$post = get_queried_object();
		$is   = is_singular() && $post instanceof WP_Post && (bool) preg_match( '/semrs-sf-(shopify|etsy|ebay|amazon)/', $post->post_content );
	}
	return $is;
}

/**
 * Product blocks on shop pages: shop currency when the block has none, the
 * shop checkout link when the block has none, and a contact link instead of a
 * dead "Not available yet" button. A link or currency set on the block wins.
 *
 * @param array $block Parsed block.
 * @return array
 */
function semrs_shops_product_defaults( $block ) {
	if ( 'semrs/product' !== ( $block['blockName'] ?? '' ) || is_admin() || ! semrs_shops_is_shop_page() ) {
		return $block;
	}
	$s = semrs_shops_settings();
	$a = (array) ( $block['attrs'] ?? array() );
	if ( empty( $a['currency'] ) && in_array( $s['currency'], semrs_shops_currencies(), true ) ) {
		$a['currency'] = $s['currency'];
	}
	if ( 'full' === ( $a['layout'] ?? '' ) && empty( $a['url'] ) ) {
		$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
		if ( '' !== trim( $s['checkout'] ) ) {
			$a['url'] = str_replace( array( '{product}', '{title}' ), array( rawurlencode( $slug ), rawurlencode( (string) ( $a['title'] ?? '' ) ) ), $s['checkout'] );
		} elseif ( '1' === $s['fallback'] && empty( $a['fallback'] ) ) {
			$a['fallback']      = add_query_arg( 'item', rawurlencode( (string) ( $a['title'] ?? $slug ) ), home_url( $s['contact'] ) );
			$a['fallback_text'] = __( 'Ask about this item', 'semrs-shops' );
		}
	}
	$block['attrs'] = $a;
	return $block;
}
add_filter( 'render_block_data', 'semrs_shops_product_defaults' );

/**
 * Register the setting.
 */
function semrs_shops_register_setting() {
	register_setting(
		'semrs_shops',
		'semrs_shops',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => function ( $in ) {
				$in  = is_array( $in ) ? $in : array();
				$cur = strtoupper( sanitize_key( $in['currency'] ?? 'USD' ) );
				$chk = trim( (string) ( $in['checkout'] ?? '' ) );
				// Keep the {product} / {title} placeholders through URL cleaning.
				$chk = str_replace( array( '{product}', '{title}' ), array( 'SEMRSPRODUCTSLUG', 'SEMRSPRODUCTTITLE' ), $chk );
				$chk = '' === $chk ? '' : str_replace( array( 'SEMRSPRODUCTSLUG', 'SEMRSPRODUCTTITLE' ), array( '{product}', '{title}' ), esc_url_raw( $chk, array( 'https', 'http' ) ) );
				return array(
					'currency' => in_array( $cur, semrs_shops_currencies(), true ) ? $cur : 'USD',
					'checkout' => $chk,
					'fallback' => empty( $in['fallback'] ) ? '' : '1',
					'contact'  => '/' . trim( sanitize_text_field( $in['contact'] ?? '/contact/' ), '/' ) . '/',
				);
			},
		)
	);
}
add_action( 'admin_init', 'semrs_shops_register_setting' );

/**
 * Settings tab.
 */
function semrs_shops_settings_tab() {
	$s = semrs_shops_settings();
	$n = 'semrs_shops';
	settings_errors();
	?>
	<form method="post" action="options.php">
		<?php settings_fields( 'semrs_shops' ); ?>
		<p><?php esc_html_e( 'These settings apply to every product page of an imported SEMRS shop. A currency or checkout link set on a single Product block always wins.', 'semrs-shops' ); ?></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="semrs-shops-currency"><?php esc_html_e( 'Currency', 'semrs-shops' ); ?></label></th><td>
				<select id="semrs-shops-currency" name="<?php echo esc_attr( $n ); ?>[currency]">
					<?php foreach ( semrs_shops_currencies() as $c ) : ?>
						<option value="<?php echo esc_attr( $c ); ?>"<?php selected( $s['currency'], $c ); ?>><?php echo esc_html( $c ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'The example prices are in US dollars. If you change the currency, change the prices on the product pages too.', 'semrs-shops' ); ?></p>
			</td></tr>
			<tr><th scope="row"><label for="semrs-shops-checkout"><?php esc_html_e( 'Checkout link', 'semrs-shops' ); ?></label></th><td>
				<input id="semrs-shops-checkout" type="text" class="large-text code" name="<?php echo esc_attr( $n ); ?>[checkout]" value="<?php echo esc_attr( $s['checkout'] ); ?>" placeholder="https://yourshop.gumroad.com/l/{product}">
				<p class="description"><?php esc_html_e( 'Where the buy button goes: your WooCommerce, Shopify, Stripe Payment Link, Lemon Squeezy or Gumroad checkout. {product} is replaced with the product page slug and {title} with the product name; the chosen options are added to the link. Leave empty if each product has its own link.', 'semrs-shops' ); ?></p>
			</td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Before checkout is connected', 'semrs-shops' ); ?></th><td>
				<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[fallback]" value="1"<?php checked( '1', $s['fallback'] ); ?>> <?php esc_html_e( 'Show "Ask about this item" linking to the contact page instead of a disabled button', 'semrs-shops' ); ?></label>
				<p><label for="semrs-shops-contact"><?php esc_html_e( 'Contact page', 'semrs-shops' ); ?></label> <input id="semrs-shops-contact" type="text" class="regular-text code" name="<?php echo esc_attr( $n ); ?>[contact]" value="<?php echo esc_attr( $s['contact'] ); ?>"></p>
				<p class="description"><?php esc_html_e( 'The product name is passed along so you know which item the visitor asks about.', 'semrs-shops' ); ?></p>
			</td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<?php
}
