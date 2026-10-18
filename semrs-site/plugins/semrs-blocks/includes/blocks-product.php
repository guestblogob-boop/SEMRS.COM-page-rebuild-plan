<?php
/**
 * Product block and the shared buy-box engine: colour swatches, size
 * buttons, dropdowns and plan cards; quantity with bulk (volume) discounts;
 * bundle add-ons; live price, savings and total; sold-out values; variant
 * deep links (?color=navy); and a checkout link that receives the choices.
 *
 * There is no cart here: the button opens your checkout (WooCommerce,
 * Shopify, Stripe Payment Links, Lemon Squeezy, Gumroad…) with the chosen
 * options as URL parameters. Product schema is added with real offers only.
 *
 * Option syntax (one group per line):
 *   Color: Navy #0b1f3a, Gold #d4a72c, White #ffffff (sold out)
 *   Size: S, M, L +5, XL +10
 *   select Material: Cotton, Linen +12
 *   plan License: 1 site =49, 5 sites =99
 *   text* Name to print: 30     (personalization box, 30 characters, * = required)
 * "+N" adds to the price, "=N" sets it, "(sold out)" disables a value,
 * "#hex" makes a colour swatch, "img:123" swaps the product image.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parse the options text into groups.
 *
 * @param string $text Options text.
 * @return array
 */
function semrs_blocks_product_groups( $text ) {
	$groups = array();
	foreach ( semrs_blocks_lines( $text ) as $line ) {
		if ( false === strpos( $line, ':' ) ) {
			continue;
		}
		list( $head, $list ) = array_map( 'trim', explode( ':', $line, 2 ) );
		if ( preg_match( '/^text(\*?)\s+(.+)$/i', $head, $m ) ) {
			$groups[] = array(
				'key'      => sanitize_key( str_replace( '-', '_', sanitize_title( $m[2] ) ) ),
				'label'    => sanitize_text_field( $m[2] ),
				'type'     => 'text',
				'required' => '*' === $m[1],
				'max'      => max( 1, min( 500, (int) $list ? (int) $list : 60 ) ),
				'values'   => array(),
			);
			continue;
		}
		$type = '';
		if ( preg_match( '/^(color|colour|size|select|plan)\s+(.+)$/i', $head, $m ) ) {
			$type = strtolower( 'colour' === strtolower( $m[1] ) ? 'color' : $m[1] );
			$head = $m[2];
		}
		$values = array();
		foreach ( array_filter( array_map( 'trim', explode( ',', $list ) ) ) as $raw ) {
			$v = array(
				'label'   => '',
				'value'   => '',
				'add'     => 0,
				'price'   => null,
				'swatch'  => '',
				'image'   => 0,
				'soldout' => false,
			);
			if ( preg_match( '/\((sold out|out of stock)\)/i', $raw ) ) {
				$v['soldout'] = true;
				$raw          = trim( preg_replace( '/\((sold out|out of stock)\)/i', '', $raw ) );
			}
			if ( preg_match( '/#([0-9a-f]{3}|[0-9a-f]{6})\b/i', $raw, $m ) ) {
				$v['swatch'] = '#' . strtolower( $m[1] );
				$raw         = trim( str_replace( $m[0], '', $raw ) );
			}
			if ( preg_match( '/\bimg:(\d+)\b/i', $raw, $m ) ) {
				$v['image'] = (int) $m[1];
				$raw        = trim( str_replace( $m[0], '', $raw ) );
			}
			if ( preg_match( '/(?:^|\s)=\s*([0-9]+(?:\.[0-9]+)?)\s*$/', $raw, $m ) ) {
				$v['price'] = (float) $m[1];
				$raw        = trim( substr( $raw, 0, -strlen( $m[0] ) ) );
			} elseif ( preg_match( '/(?:^|\s)([+-])\s*([0-9]+(?:\.[0-9]+)?)\s*$/', $raw, $m ) ) {
				$v['add'] = ( '-' === $m[1] ? -1 : 1 ) * (float) $m[2];
				$raw      = trim( substr( $raw, 0, -strlen( $m[0] ) ) );
			}
			if ( '' === $raw ) {
				continue;
			}
			$v['label'] = sanitize_text_field( $raw );
			$v['value'] = sanitize_title( $raw );
			$values[]   = $v;
		}
		if ( ! $values ) {
			continue;
		}
		if ( '' === $type ) {
			$type = array_filter( wp_list_pluck( $values, 'swatch' ) ) ? 'color' : ( null !== $values[0]['price'] ? 'plan' : 'size' );
		}
		$groups[] = array(
			'key'    => sanitize_key( str_replace( '-', '_', sanitize_title( $head ) ) ),
			'label'  => sanitize_text_field( $head ),
			'type'   => $type,
			'values' => $values,
		);
	}
	return $groups;
}

/**
 * Bulk rules: "5 | 10" = 5 or more, 10% off.
 *
 * @param string $text Text.
 * @return array
 */
function semrs_blocks_product_bulk( $text ) {
	$out = array();
	foreach ( semrs_blocks_lines( $text ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( (int) $p[0] > 1 && isset( $p[1] ) && (float) $p[1] > 0 ) {
			$out[] = array( 'min' => (int) $p[0], 'off' => min( 90, (float) $p[1] ) );
		}
	}
	usort( $out, function ( $a, $b ) { return $a['min'] - $b['min']; } );
	return $out;
}

/**
 * Bundles: "Name | price | regular price | image ID".
 *
 * @param string $text Text.
 * @return array
 */
function semrs_blocks_product_bundles( $text ) {
	$out = array();
	foreach ( semrs_blocks_lines( $text ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( '' === $p[0] || ! isset( $p[1] ) ) {
			continue;
		}
		$out[] = array(
			'label'   => sanitize_text_field( $p[0] ),
			'value'   => sanitize_title( $p[0] ),
			'price'   => (float) $p[1],
			'compare' => isset( $p[2] ) && '' !== $p[2] ? (float) $p[2] : 0,
			'image'   => isset( $p[3] ) ? absint( $p[3] ) : 0,
		);
	}
	return $out;
}

/**
 * Format money on the server (the browser uses Intl with the same settings).
 *
 * @param float  $amount   Amount.
 * @param string $currency ISO code.
 * @return string
 */
function semrs_blocks_money( $amount, $currency ) {
	$symbols = array( 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AUD' => 'A$', 'CAD' => 'CA$', 'NZD' => 'NZ$', 'INR' => '₹', 'PKR' => 'Rs ', 'AED' => 'AED ', 'JPY' => '¥' );
	$whole   = abs( $amount - round( $amount ) ) < 0.005;
	$num     = number_format_i18n( $amount, $whole ? 0 : 2 );
	return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] . $num : $num . ' ' . $currency;
}

/**
 * Product stylesheet: loaded in the <head> on pages that contain a Product
 * block (or call semrs_blocks_product_enqueue() early, like the semrs.com store).
 */
function semrs_blocks_product_enqueue() {
	if ( ! wp_style_is( 'semrs-blocks-product', 'registered' ) ) {
		wp_register_style( 'semrs-blocks-product', SEMRS_BLOCKS_URL . 'assets/product.css', array(), SEMRS_BLOCKS_VERSION );
	}
	wp_enqueue_style( 'semrs-blocks-product' );
}
add_action(
	'wp_enqueue_scripts',
	function () {
		$post = get_queried_object();
		if ( is_singular() && $post instanceof WP_Post && has_block( 'semrs/product', $post ) ) {
			semrs_blocks_product_enqueue();
		}
	}
);

/**
 * Fallback when the stylesheet was not loaded in the <head> (e.g. a Product
 * block in a widget or pattern): print it inline once.
 *
 * @return string
 */
function semrs_blocks_product_css() {
	static $done = false;
	if ( $done || wp_style_is( 'semrs-blocks-product', 'done' ) || wp_style_is( 'semrs-blocks-product', 'enqueued' ) || ! semrs_blocks_page_output() ) {
		return '';
	}
	$done = true;
	$file = SEMRS_BLOCKS_DIR . 'assets/product.css';
	return file_exists( $file ) ? '<style id="semrs-product-css">' . wp_strip_all_tags( (string) file_get_contents( $file ) ) . '</style>' : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * The buy box (shared by the Product block and the semrs.com store).
 *
 * @param array $cfg {
 *     @type string $currency ISO code.
 *     @type float  $price    Base price.
 *     @type float  $compare  Regular price (optional, crossed out).
 *     @type string $period   Text after the price (e.g. "/ year").
 *     @type array  $groups   From semrs_blocks_product_groups().
 *     @type bool   $qty      Show quantity.
 *     @type int    $max      Max quantity.
 *     @type array  $bulk     From semrs_blocks_product_bulk().
 *     @type array  $bundles  From semrs_blocks_product_bundles().
 *     @type string $url      Checkout URL.
 *     @type string $button   Button text.
 *     @type string $fallback Link when there is no checkout URL ('' = disabled button).
 *     @type string $fallback_text Text for that link.
 *     @type string $bundle_title Heading above bundles.
 * }
 * @return string
 */
function semrs_blocks_product_engine( $cfg ) {
	$cfg = array_merge(
		array(
			'currency'      => 'USD',
			'price'         => 0,
			'compare'       => 0,
			'period'        => '',
			'groups'        => array(),
			'qty'           => false,
			'max'           => 99,
			'bulk'          => array(),
			'bundles'       => array(),
			'url'           => '',
			'button'        => __( 'Buy now', 'semrs-blocks' ),
			'fallback'      => '',
			'fallback_text' => __( 'Not available yet', 'semrs-blocks' ),
			'bundle_title'  => __( 'Add to your order', 'semrs-blocks' ),
		),
		$cfg
	);
	semrs_blocks_script( 'semrs-blocks-product', 'product.js' );
	static $n = 0;
	$n++;
	$id  = 'sb' . $n;
	$cur = strtoupper( preg_replace( '/[^A-Za-z]/', '', $cfg['currency'] ) );
	$cur = $cur ? $cur : 'USD';

	// Initial selection: first available value in each group.
	$unit    = (float) $cfg['price'];
	$compare = (float) $cfg['compare'];
	$url     = $cfg['url'];
	foreach ( $cfg['groups'] as $gi => $g ) {
		foreach ( (array) $g['values'] as $vi => $v ) {
			if ( empty( $v['soldout'] ) ) {
				$cfg['groups'][ $gi ]['pick'] = $vi;
				if ( null !== $v['price'] ) {
					$unit    = (float) $v['price'];
					$compare = isset( $v['compare'] ) ? (float) $v['compare'] : 0;
				}
				$unit += (float) $v['add'];
				if ( ! empty( $v['url'] ) ) {
					$url = $v['url'];
				}
				break;
			}
		}
	}

	$json = array(
		'currency' => $cur,
		'locale'   => str_replace( '_', '-', get_locale() ),
		'price'    => (float) $cfg['price'],
		'compare'  => (float) $cfg['compare'],
		'url'      => $cfg['url'],
		'groups'   => $cfg['groups'],
		'bulk'     => $cfg['bulk'],
		'bundles'  => $cfg['bundles'],
		'max'      => max( 1, (int) $cfg['max'] ),
		'i18n'     => array(
			/* translators: %s: amount */
			'save'  => __( 'You save %s', 'semrs-blocks' ),
			/* translators: %s: amount */
			'each'  => __( '%s each', 'semrs-blocks' ),
			/* translators: %s: percent */
			'off'   => __( 'Save %s%%', 'semrs-blocks' ),
		),
	);
	foreach ( $json['groups'] as &$g ) {
		foreach ( $g['values'] as &$v ) {
			$v['image_src']    = $v['image'] ? (string) wp_get_attachment_image_url( $v['image'], 'large' ) : '';
			$v['image_srcset'] = $v['image'] ? (string) wp_get_attachment_image_srcset( $v['image'], 'large' ) : '';
		}
	}
	unset( $g, $v );

	$out  = semrs_blocks_product_css();
	$out .= '<div class="semrs-buy" id="' . esc_attr( $id ) . '" data-semrs-buy="' . esc_attr( wp_json_encode( $json ) ) . '">';
	$out .= '<p class="semrs-buy__price" aria-live="polite"><span class="semrs-buy__now" data-b-price>' . esc_html( semrs_blocks_money( $unit, $cur ) ) . '</span>';
	$out .= ' <s class="semrs-buy__was" data-b-compare' . ( $compare > $unit ? '>' . esc_html( semrs_blocks_money( $compare, $cur ) ) : ' hidden>' ) . '</s>';
	$out .= $cfg['period'] ? ' <span class="semrs-buy__per">' . esc_html( $cfg['period'] ) . '</span>' : '';
	$out .= '</p><p class="semrs-buy__save" data-b-save' . ( $compare > $unit ? '>' . esc_html( sprintf( $json['i18n']['save'], semrs_blocks_money( $compare - $unit, $cur ) ) ) : ' hidden>' ) . '</p>';

	foreach ( $cfg['groups'] as $g ) {
		$name = $id . '-' . $g['key'];
		$pick = $g['pick'] ?? 0;
		if ( 'text' === $g['type'] ) {
			$out .= '<div class="semrs-opt semrs-opt--text"><label class="semrs-opt__label" for="' . esc_attr( $name ) . '">' . esc_html( $g['label'] ) . ( $g['required'] ? ' <span class="semrs-req">' . esc_html__( '(required)', 'semrs-blocks' ) . '</span>' : '' ) . '</label><input type="text" id="' . esc_attr( $name ) . '" maxlength="' . (int) $g['max'] . '" data-text-group="' . esc_attr( $g['key'] ) . '"' . ( $g['required'] ? ' required aria-required="true"' : '' ) . ' autocomplete="off"><small class="semrs-count" aria-hidden="true">0 / ' . (int) $g['max'] . '</small></div>';
			continue;
		}
		if ( 'select' === $g['type'] ) {
			$out .= '<div class="semrs-opt semrs-opt--select"><label class="semrs-opt__label" for="' . esc_attr( $name ) . '">' . esc_html( $g['label'] ) . '</label><select id="' . esc_attr( $name ) . '" data-group="' . esc_attr( $g['key'] ) . '">';
			foreach ( $g['values'] as $vi => $v ) {
				$extra = $v['add'] ? ' (' . ( $v['add'] > 0 ? '+' : '−' ) . semrs_blocks_money( abs( $v['add'] ), $cur ) . ')' : '';
				$out  .= '<option value="' . esc_attr( $v['value'] ) . '"' . selected( $vi, $pick, false ) . disabled( ! empty( $v['soldout'] ), true, false ) . '>' . esc_html( $v['label'] . $extra . ( ! empty( $v['soldout'] ) ? ' – ' . __( 'sold out', 'semrs-blocks' ) : '' ) ) . '</option>';
			}
			$out .= '</select></div>';
			continue;
		}
		$out .= '<fieldset class="semrs-opt semrs-opt--' . esc_attr( $g['type'] ) . '"><legend class="semrs-opt__label">' . esc_html( $g['label'] ) . ( 'plan' === $g['type'] ? '' : ': <strong data-b-chosen>' . esc_html( $g['values'][ $pick ]['label'] ) . '</strong>' ) . '</legend><div class="semrs-opt__values">';
		foreach ( $g['values'] as $vi => $v ) {
			$input = '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v['value'] ) . '" data-group="' . esc_attr( $g['key'] ) . '"' . checked( $vi, $pick, false ) . disabled( ! empty( $v['soldout'] ), true, false ) . '>';
			$sold  = ! empty( $v['soldout'] ) ? ' is-soldout' : '';
			if ( 'color' === $g['type'] ) {
				$out .= '<label class="semrs-swatch' . $sold . '" title="' . esc_attr( $v['label'] ) . '">' . $input . '<span class="semrs-swatch__dot" style="--sw:' . esc_attr( $v['swatch'] ? $v['swatch'] : '#cccccc' ) . '"></span><span class="screen-reader-text">' . esc_html( $v['label'] . ( $sold ? ' – ' . __( 'sold out', 'semrs-blocks' ) : '' ) ) . '</span></label>';
			} elseif ( 'plan' === $g['type'] ) {
				$price = null !== $v['price'] ? semrs_blocks_money( $v['price'], $cur ) : '';
				$was   = ! empty( $v['compare'] ) && null !== $v['price'] && $v['compare'] > $v['price'] ? ' <s>' . esc_html( semrs_blocks_money( $v['compare'], $cur ) ) . '</s>' : '';
				$out  .= '<label class="semrs-plancard' . $sold . '">' . $input . '<span class="semrs-plancard__name">' . esc_html( $v['label'] ) . ( ! empty( $v['flag'] ) ? ' <em>' . esc_html( $v['flag'] ) . '</em>' : '' ) . '</span><span class="semrs-plancard__price">' . esc_html( $price ) . $was . '</span></label>';
			} else {
				$extra = $v['add'] ? '<small>' . esc_html( ( $v['add'] > 0 ? '+' : '−' ) . semrs_blocks_money( abs( $v['add'] ), $cur ) ) . '</small>' : '';
				$out  .= '<label class="semrs-pill' . $sold . '">' . $input . '<span>' . esc_html( $v['label'] ) . $extra . '</span></label>';
			}
		}
		$out .= '</div></fieldset>';
	}

	if ( $cfg['qty'] ) {
		$out .= '<div class="semrs-opt semrs-opt--qty"><label class="semrs-opt__label" for="' . esc_attr( $id ) . '-qty">' . esc_html__( 'Quantity', 'semrs-blocks' ) . '</label><div class="semrs-qty"><button type="button" data-q="-1" aria-label="' . esc_attr__( 'Decrease quantity', 'semrs-blocks' ) . '">−</button><input id="' . esc_attr( $id ) . '-qty" type="number" inputmode="numeric" min="1" max="' . (int) $json['max'] . '" value="1" data-b-qty><button type="button" data-q="1" aria-label="' . esc_attr__( 'Increase quantity', 'semrs-blocks' ) . '">+</button></div></div>';
		if ( $cfg['bulk'] ) {
			$out .= '<table class="semrs-bulk"><caption>' . esc_html__( 'Buy more, save more', 'semrs-blocks' ) . '</caption><tbody>';
			foreach ( $cfg['bulk'] as $b ) {
				/* translators: %d: quantity */
				$out .= '<tr data-min="' . (int) $b['min'] . '"><th scope="row">' . esc_html( sprintf( __( '%d or more', 'semrs-blocks' ), $b['min'] ) ) . '</th><td>' . esc_html( sprintf( $json['i18n']['off'], rtrim( rtrim( number_format( $b['off'], 1 ), '0' ), '.' ) ) ) . '</td></tr>';
			}
			$out .= '</tbody></table>';
		}
	}

	if ( $cfg['bundles'] ) {
		$out .= '<fieldset class="semrs-bundles"><legend class="semrs-opt__label">' . esc_html( $cfg['bundle_title'] ) . '</legend>';
		foreach ( $cfg['bundles'] as $b ) {
			$out .= '<label class="semrs-bundle"><input type="checkbox" value="' . esc_attr( $b['value'] ) . '" data-b-bundle>' . ( $b['image'] ? wp_get_attachment_image( $b['image'], 'thumbnail', false, array( 'alt' => '', 'class' => 'semrs-bundle__img' ) ) : '' ) . '<span class="semrs-bundle__name">' . esc_html( $b['label'] ) . '</span><span class="semrs-bundle__price">+' . esc_html( semrs_blocks_money( $b['price'], $cur ) ) . ( $b['compare'] > $b['price'] ? ' <s>' . esc_html( semrs_blocks_money( $b['compare'], $cur ) ) . '</s>' : '' ) . '</span></label>';
		}
		$out .= '</fieldset>';
	}

	$out .= '<p class="semrs-buy__msg" role="alert" data-b-msg hidden>' . esc_html__( 'Please fill in the required personalisation first.', 'semrs-blocks' ) . '</p>';
	$out .= '<p class="semrs-buy__total" data-b-total-row hidden>' . esc_html__( 'Total', 'semrs-blocks' ) . ': <strong data-b-total></strong></p>';
	$any_url = '' !== (string) $cfg['url'];
	foreach ( $cfg['groups'] as $g ) {
		foreach ( $g['values'] as $v ) {
			$any_url = $any_url || ! empty( $v['url'] );
		}
	}
	if ( $any_url || $cfg['fallback'] ) {
		$live  = '' !== (string) $url;
		$out  .= '<a class="semrs-buy__btn' . ( $live ? '' : ' is-soon' ) . '" href="' . esc_url( $live ? $url : $cfg['fallback'] ) . '" data-b-buy data-label="' . esc_attr( $cfg['button'] ) . '" data-fallback="' . esc_url( $cfg['fallback'] ) . '" data-fallback-text="' . esc_attr( $cfg['fallback_text'] ) . '"' . ( $live ? ' rel="nofollow"' : '' ) . '>' . esc_html( $live ? $cfg['button'] : $cfg['fallback_text'] ) . '</a>';
	} else {
		$out .= '<span class="semrs-buy__btn is-soon" aria-disabled="true">' . esc_html( $cfg['fallback_text'] ) . '</span>';
	}
	$out .= '</div>';
	return $out;
}

/* -------------------------------------------------------------------------
 * Product block
 * ---------------------------------------------------------------------- */

add_filter(
	'semrs_blocks',
	function ( $blocks ) {
		$blocks['semrs/product'] = array(
			'title'       => __( 'Product', 'semrs-blocks' ),
			'description' => __( 'Product with colour swatches, size buttons, quantity and bulk discounts, bundles, live price and a checkout link. Use "Card" in grids.', 'semrs-blocks' ),
			'icon'        => 'cart',
			'keywords'    => array( 'product', 'shop', 'store', 'buy', 'price', 'variant', 'color', 'size', 'bundle', 'bulk', 'quantity', 'ecommerce' ),
			'render'      => 'semrs_blocks_render_product',
			'controls'    => array(
				'layout'   => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'full', 'options' => array( 'full' => __( 'Product page (image and buy box)', 'semrs-blocks' ), 'buybox' => __( 'Buy box only', 'semrs-blocks' ), 'card' => __( 'Card (for shop grids)', 'semrs-blocks' ) ) ),
				'image'    => array( 'type' => 'media', 'label' => __( 'Image', 'semrs-blocks' ), 'default' => 0 ),
				'imageUrl' => array( 'type' => 'url', 'label' => __( 'Image URL (when not chosen from the Media Library)', 'semrs-blocks' ), 'default' => '' ),
				'title'    => array( 'type' => 'text', 'label' => __( 'Product name', 'semrs-blocks' ), 'default' => 'Classic Cotton T-Shirt' ),
				'badge'    => array( 'type' => 'text', 'label' => __( 'Badge (e.g. New, Sale)', 'semrs-blocks' ), 'default' => '' ),
				'text'     => array( 'type' => 'textarea', 'label' => __( 'Short description', 'semrs-blocks' ), 'default' => 'Soft, breathable everyday tee. Replace this text with your own product details.' ),
				'price'    => array( 'type' => 'text', 'label' => __( 'Price (number)', 'semrs-blocks' ), 'default' => '24' ),
				'compare'  => array( 'type' => 'text', 'label' => __( 'Regular price, crossed out (optional, only for a real reduction)', 'semrs-blocks' ), 'default' => '' ),
				'currency' => array( 'type' => 'text', 'label' => __( 'Currency (USD, EUR, GBP…)', 'semrs-blocks' ), 'default' => 'USD' ),
				'period'   => array( 'type' => 'text', 'label' => __( 'Text after price (e.g. / month)', 'semrs-blocks' ), 'default' => '' ),
				'options'  => array( 'type' => 'textarea', 'label' => __( 'Options, one group per line. Color: Navy #0b1f3a, White #ffffff · Size: S, M, L +2 · "(sold out)" disables a value', 'semrs-blocks' ), 'default' => "Color: Navy #1e3a66, Sand #d8c7a3, White #ffffff\nSize: S, M, L, XL +2, XXL +4 (sold out)" ),
				'qty'      => array( 'type' => 'toggle', 'label' => __( 'Show quantity', 'semrs-blocks' ), 'default' => true ),
				'max'      => array( 'type' => 'range', 'label' => __( 'Maximum quantity', 'semrs-blocks' ), 'default' => 20, 'min' => 1, 'max' => 500 ),
				'bulk'     => array( 'type' => 'textarea', 'label' => __( 'Bulk discounts: quantity | % off (one per line, e.g. 3 | 10)', 'semrs-blocks' ), 'default' => '' ),
				'bundles'  => array( 'type' => 'textarea', 'label' => __( 'Bundle add-ons: name | price | regular price (optional)', 'semrs-blocks' ), 'default' => '' ),
				'button'   => array( 'type' => 'text', 'label' => __( 'Button text', 'semrs-blocks' ), 'default' => 'Add to cart' ),
				'url'      => array( 'type' => 'url', 'label' => __( 'Checkout or product link (choices are added as ?color=…&size=…&quantity=…)', 'semrs-blocks' ), 'default' => '' ),
				'link'     => array( 'type' => 'url', 'label' => __( 'Card layout: product page link', 'semrs-blocks' ), 'default' => '' ),
				'stock'    => array( 'type' => 'select', 'label' => __( 'Availability (for search results)', 'semrs-blocks' ), 'default' => 'InStock', 'options' => array( 'InStock' => __( 'In stock', 'semrs-blocks' ), 'OutOfStock' => __( 'Out of stock', 'semrs-blocks' ), 'PreOrder' => __( 'Pre-order', 'semrs-blocks' ) ) ),
				'schema'   => array( 'type' => 'toggle', 'label' => __( 'Add Product schema (price and availability only)', 'semrs-blocks' ), 'default' => false ),
			),
		);
		return $blocks;
	}
);

/**
 * Render the Product block.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_product( $a ) {
	$layout = in_array( semrs_blocks_a( $a, 'layout', 'full' ), array( 'full', 'buybox', 'card' ), true ) ? $a['layout'] : 'full';
	$title  = semrs_blocks_a( $a, 'title', '' );
	$price  = (float) preg_replace( '/[^0-9.]/', '', (string) semrs_blocks_a( $a, 'price', '0' ) );
	$cmp    = (float) preg_replace( '/[^0-9.]/', '', (string) semrs_blocks_a( $a, 'compare', '0' ) );
	$cur    = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) semrs_blocks_a( $a, 'currency', 'USD' ) ) );
	$cur    = $cur ? $cur : 'USD';
	$groups = semrs_blocks_product_groups( semrs_blocks_a( $a, 'options', '' ) );
	$img    = (int) semrs_blocks_a( $a, 'image', 0 );
	$iurl   = semrs_blocks_a( $a, 'imageUrl', '' );
	if ( $img ) {
		$media = wp_get_attachment_image( $img, 'large', false, array( 'class' => 'semrs-product__img', 'data-b-main' => '1', 'sizes' => 'card' === $layout ? '(max-width: 640px) 100vw, 400px' : '(max-width: 900px) 100vw, 600px' ) );
	} elseif ( $iurl ) {
		$media = '<img class="semrs-product__img" data-b-main="1" src="' . esc_url( $iurl ) . '" alt="' . esc_attr( $title ) . '" width="640" height="640" loading="lazy" decoding="async">';
	} else {
		$media = '<span class="semrs-product__ph" aria-hidden="true"></span>';
	}
	$badge  = semrs_blocks_a( $a, 'badge' ) ? '<span class="semrs-product__badge">' . esc_html( $a['badge'] ) . '</span>' : '';
	$css    = semrs_blocks_product_css();

	if ( 'card' === $layout ) {
		$link = semrs_blocks_a( $a, 'link', semrs_blocks_a( $a, 'url', '' ) );
		$dots = '';
		foreach ( $groups as $g ) {
			if ( 'color' === $g['type'] ) {
				foreach ( array_slice( $g['values'], 0, 6 ) as $v ) {
					$dots .= '<span class="semrs-swatch__dot is-mini" style="--sw:' . esc_attr( $v['swatch'] ? $v['swatch'] : '#ccc' ) . '" title="' . esc_attr( $v['label'] ) . '"></span>';
				}
				break;
			}
		}
		$from = '';
		foreach ( $groups as $g ) {
			foreach ( $g['values'] as $v ) {
				if ( 0 !== (int) round( $v['add'] * 100 ) || null !== $v['price'] ) {
					$from = __( 'From', 'semrs-blocks' ) . ' ';
					break 2;
				}
			}
		}
		$name = $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>' : esc_html( $title );
		return $css . '<article class="semrs-product semrs-product--card"><div class="semrs-product__media">' . ( $link ? '<a href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . $media . '</a>' : $media ) . $badge . '</div><div class="semrs-product__body"><h3 class="semrs-product__title">' . $name . '</h3>'
			. ( $dots ? '<div class="semrs-product__dots">' . $dots . '</div>' : '' )
			. '<p class="semrs-product__price"><span>' . esc_html( $from ) . '</span><strong>' . esc_html( semrs_blocks_money( $price, $cur ) ) . '</strong>' . ( $cmp > $price ? ' <s>' . esc_html( semrs_blocks_money( $cmp, $cur ) ) . '</s>' : '' ) . '</p>'
			. ( $link ? '<a class="semrs-product__more" href="' . esc_url( $link ) . '">' . ( $groups ? esc_html__( 'Choose options', 'semrs-blocks' ) : esc_html__( 'View product', 'semrs-blocks' ) ) . '</a>' : '' )
			. '</div></article>';
	}

	$buy = semrs_blocks_product_engine(
		array(
			'currency' => $cur,
			'price'    => $price,
			'compare'  => $cmp,
			'period'   => semrs_blocks_a( $a, 'period', '' ),
			'groups'   => $groups,
			'qty'      => ! empty( $a['qty'] ),
			'max'      => (int) semrs_blocks_a( $a, 'max', 20 ),
			'bulk'     => semrs_blocks_product_bulk( semrs_blocks_a( $a, 'bulk', '' ) ),
			'bundles'  => semrs_blocks_product_bundles( semrs_blocks_a( $a, 'bundles', '' ) ),
			'url'      => semrs_blocks_a( $a, 'url', '' ),
			'button'   => semrs_blocks_a( $a, 'button', __( 'Add to cart', 'semrs-blocks' ) ),
		)
	);
	$head = '<h2 class="semrs-product__title">' . esc_html( $title ) . '</h2>' . ( semrs_blocks_a( $a, 'text' ) ? '<p class="semrs-product__text">' . esc_html( $a['text'] ) . '</p>' : '' );

	$schema = '';
	if ( ! empty( $a['schema'] ) && $title && $price > 0 ) {
		$data   = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => $title,
			'offers'   => array(
				'@type'         => 'Offer',
				'price'         => (string) $price,
				'priceCurrency' => $cur,
				'availability'  => 'https://schema.org/' . ( in_array( $a['stock'] ?? '', array( 'InStock', 'OutOfStock', 'PreOrder' ), true ) ? $a['stock'] : 'InStock' ),
			),
		);
		if ( semrs_blocks_a( $a, 'text' ) ) {
			$data['description'] = wp_strip_all_tags( $a['text'] );
		}
		if ( $img ) {
			$data['image'] = wp_get_attachment_image_url( $img, 'large' );
		} elseif ( $iurl ) {
			$data['image'] = $iurl;
		}
		if ( semrs_blocks_a( $a, 'url' ) ) {
			$data['offers']['url'] = $a['url'];
		}
		$schema = '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}

	if ( 'buybox' === $layout ) {
		return $css . '<div class="semrs-product semrs-product--buybox">' . $head . $buy . '</div>' . $schema;
	}
	return '<div class="semrs-product semrs-product--full"><div class="semrs-product__media">' . $media . $badge . '</div><div class="semrs-product__info">' . $head . $buy . '</div></div>' . $schema;
}

/* -------------------------------------------------------------------------
 * Store patterns (editor only)
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		// Editor only: the admin and REST requests (REST_REQUEST is not defined yet at init).
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_admin() && false === strpos( $uri, '/wp-json/' ) && false === strpos( $uri, 'rest_route=' ) ) {
			return;
		}
		register_block_pattern_category( 'semrs-store', array( 'label' => __( 'SEMRS store', 'semrs-blocks' ) ) );
		foreach ( semrs_blocks_store_patterns() as $slug => $p ) {
			register_block_pattern(
				'semrs-blocks/store-' . $slug,
				array(
					'title'       => $p[0],
					'description' => $p[1],
					'categories'  => array( 'semrs-store' ),
					'keywords'    => array( 'shop', 'store', 'product', 'ecommerce' ),
					'content'     => $p[2],
				)
			);
		}
	},
	30
);

/**
 * Store patterns: slug => [title, description, content].
 *
 * @return array
 */
function semrs_blocks_store_patterns() {
	$prod = function ( $attrs ) {
		return '<!-- wp:semrs/product ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
	};
	$card = function ( $title, $price, $opts, $badge = '', $cmp = '' ) use ( $prod ) {
		return $prod( array( 'layout' => 'card', 'title' => $title, 'price' => $price, 'compare' => $cmp, 'options' => $opts, 'badge' => $badge, 'link' => '#' ) );
	};
	$head = function ( $title, $text ) {
		return '<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">' . esc_html( $title ) . '</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
	};
	$grid = '<!-- wp:semrs/container {"layout":"grid","columns":4,"columnsTablet":2,"width":"container","gap":24} -->'
		. $card( 'Classic Cotton T-Shirt', '24', 'Color: Navy #1e3a66, Sand #d8c7a3, White #ffffff', 'New' )
		. $card( 'Canvas Tote Bag', '18', 'Color: Natural #e8dcc4, Black #1f2937' )
		. $card( 'Ceramic Mug', '14', 'Color: Cream #f4ede1, Sage #9bb39a, Charcoal #374151' )
		. $card( 'Everyday Hoodie', '48', "Color: Grey #9ca3af, Navy #1e3a66\nSize: S, M, L, XL +4", 'Sale', '58' )
		. '<!-- /wp:semrs/container -->';
	return array(
		'shop-grid'    => array(
			__( 'Shop grid', 'semrs-blocks' ),
			__( 'Section heading and four product cards with colour dots and prices.', 'semrs-blocks' ),
			'<!-- wp:semrs/container {"align":"full","tag":"section","padTop":48,"padBottom":48} -->' . $head( 'Shop the collection', 'Replace these sample products with your own. Each card links to its product page.' ) . $grid . '<!-- /wp:semrs/container -->',
		),
		'product-page' => array(
			__( 'Product page', 'semrs-blocks' ),
			__( 'Product image and buy box with colour, size and quantity, then details accordions and related products.', 'semrs-blocks' ),
			'<!-- wp:semrs/container {"align":"full","tag":"section","padTop":32,"padBottom":32} -->'
				. $prod( array( 'layout' => 'full', 'title' => 'Classic Cotton T-Shirt', 'text' => 'Soft, breathable everyday tee. Replace this text with your own product details.', 'price' => '24', 'options' => "Color: Navy #1e3a66, Sand #d8c7a3, White #ffffff\nSize: S, M, L, XL +2, XXL +4 (sold out)", 'qty' => true, 'max' => 20, 'button' => 'Add to cart', 'schema' => false ) )
				. '<!-- wp:semrs/accordion --><!-- wp:semrs/accordion-item {"title":"Details"} --><!-- wp:paragraph --><p>Describe materials, fit, care and what is included.</p><!-- /wp:paragraph --><!-- /wp:semrs/accordion-item --><!-- wp:semrs/accordion-item {"title":"Shipping"} --><!-- wp:paragraph --><p>Write your own shipping times, costs and regions.</p><!-- /wp:paragraph --><!-- /wp:semrs/accordion-item --><!-- wp:semrs/accordion-item {"title":"Returns"} --><!-- wp:paragraph --><p>Write your own returns and refund policy.</p><!-- /wp:paragraph --><!-- /wp:semrs/accordion-item --><!-- /wp:semrs/accordion -->'
				. '<!-- wp:heading --><h2 class="wp-block-heading">You may also like</h2><!-- /wp:heading -->' . $grid
				. '<!-- /wp:semrs/container -->',
		),
		'bundle'       => array(
			__( 'Bundle offer', 'semrs-blocks' ),
			__( 'Buy box with "Add to your order" bundle items and their regular prices.', 'semrs-blocks' ),
			'<!-- wp:semrs/container {"align":"full","tag":"section","padTop":48,"padBottom":48} -->' . $head( 'Complete the set', 'Add matching items to your order. Prices update as you choose.' )
				. $prod( array( 'layout' => 'full', 'title' => 'Ceramic Mug', 'text' => 'Replace with your product description.', 'price' => '14', 'options' => 'Color: Cream #f4ede1, Sage #9bb39a, Charcoal #374151', 'qty' => true, 'max' => 12, 'bundles' => "Matching coaster set | 8 | 10\nGift box | 4", 'button' => 'Add to cart' ) )
				. '<!-- /wp:semrs/container -->',
		),
		'bulk'         => array(
			__( 'Bulk and wholesale pricing', 'semrs-blocks' ),
			__( 'Buy box with quantity steps and a "Buy more, save more" table.', 'semrs-blocks' ),
			'<!-- wp:semrs/container {"align":"full","tag":"section","padTop":48,"padBottom":48} -->' . $head( 'Buy more, save more', 'Volume discounts apply automatically at checkout quantities.' )
				. $prod( array( 'layout' => 'full', 'title' => 'Canvas Tote Bag', 'text' => 'Replace with your product description.', 'price' => '18', 'options' => 'Color: Natural #e8dcc4, Black #1f2937', 'qty' => true, 'max' => 500, 'bulk' => "5 | 5\n10 | 10\n25 | 15\n50 | 20", 'button' => 'Add to cart' ) )
				. '<!-- /wp:semrs/container -->',
		),
		'store-trust'  => array(
			__( 'Store promises bar', 'semrs-blocks' ),
			__( 'Four short promises (shipping, returns, payment, support) to edit with your real policies.', 'semrs-blocks' ),
			'<!-- wp:semrs/container {"align":"full","tag":"section","layout":"grid","columns":4,"columnsTablet":2,"gap":16,"padTop":24,"padBottom":24} -->'
				. '<!-- wp:semrs/service-card {"icon":"rocket","title":"Shipping","text":"Write your delivery times and costs.","style":"plain"} /-->'
				. '<!-- wp:semrs/service-card {"icon":"gift","title":"Returns","text":"Write your returns policy.","style":"plain"} /-->'
				. '<!-- wp:semrs/service-card {"icon":"lock","title":"Payments","text":"List the payment methods you accept.","style":"plain"} /-->'
				. '<!-- wp:semrs/service-card {"icon":"chat","title":"Support","text":"Tell customers how to reach you.","style":"plain"} /-->'
				. '<!-- /wp:semrs/container -->',
		),
	);
}
