<?php
/**
 * WooCommerce SEO (free): runs only while WooCommerce is active.
 *
 * - Product schema in the SEMRS JSON-LD graph: name, images, description, SKU, GTIN, MPN, brand,
 *   price (Offer, or AggregateOffer for variable products), currency, stock, the seller and
 *   ratings from real, approved customer reviews only (never invented).
 * - WooCommerce's own structured data is switched off, so Google sees one Product, not two.
 * - Cart, checkout and account pages stay out of the XML sitemap (WooCommerce already marks
 *   them noindex).
 * - Breadcrumbs on products: Home › Shop › Category › Product.
 *
 * Brand: the WooCommerce Brands taxonomy, a "Brand" attribute, or the SEMRS "Brand" field on the
 * product (Inventory tab). GTIN: WooCommerce's own GTIN/UPC/EAN/ISBN field or the SEMRS field.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is WooCommerce running?
 *
 * @return bool
 */
function semrs_woo_active() {
	return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
}

// One Product in the page: switch off WooCommerce's own JSON-LD while SEMRS SEO prints the graph.
add_action(
	'init',
	function () {
		if ( semrs_woo_active() && semrs_seo_active() && function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
			remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
		}
	},
	20
);

/**
 * Brand of a product ('' when none).
 *
 * @param WC_Product $product Product.
 * @return string
 */
function semrs_woo_brand( $product ) {
	$id    = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	$brand = (string) get_post_meta( $id, '_semrs_brand', true );
	if ( '' === $brand && taxonomy_exists( 'product_brand' ) ) {
		$terms = get_the_terms( $id, 'product_brand' );
		$brand = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
	}
	if ( '' === $brand ) {
		$brand = (string) $product->get_attribute( 'brand' );
	}
	return trim( explode( ',', $brand )[0] );
}

/**
 * schema.org availability for a stock status.
 *
 * @param string $status WooCommerce stock status.
 * @return string
 */
function semrs_woo_availability( $status ) {
	$map = array( 'instock' => 'InStock', 'outofstock' => 'OutOfStock', 'onbackorder' => 'BackOrder' );
	return 'https://schema.org/' . ( $map[ $status ] ?? 'InStock' );
}

/**
 * Product node for the current product page.
 *
 * @param array $graph Graph.
 * @return array
 */
function semrs_woo_schema( $graph ) {
	if ( ! semrs_woo_active() || ! is_singular( 'product' ) ) {
		return $graph;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return $graph;
	}
	$id    = $product->get_id();
	$url   = get_permalink( $id );
	$org   = home_url( '/' ) . '#organization';
	$desc  = wp_strip_all_tags( do_shortcode( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ) );
	$imgs  = array_filter( array_map( 'wp_get_attachment_url', array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
	$node  = array(
		'@type'            => 'Product',
		'@id'              => $url . '#product',
		'name'             => wp_strip_all_tags( $product->get_name() ),
		'url'              => $url,
		'description'      => $desc ? wp_html_excerpt( $desc, 5000 ) : null,
		'image'            => $imgs ? array_values( $imgs ) : null,
		'sku'              => $product->get_sku() ? $product->get_sku() : null,
		'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
	);
	$gtin = method_exists( $product, 'get_global_unique_id' ) ? (string) $product->get_global_unique_id() : '';
	if ( '' === $gtin ) {
		$gtin = (string) get_post_meta( $id, '_semrs_gtin', true );
	}
	if ( preg_match( '/^\d{8,14}$/', $gtin ) ) {
		$node['gtin'] = $gtin;
	}
	$mpn = (string) get_post_meta( $id, '_semrs_mpn', true );
	if ( '' !== $mpn ) {
		$node['mpn'] = $mpn;
	}
	$brand = semrs_woo_brand( $product );
	if ( '' !== $brand ) {
		$node['brand'] = array( '@type' => 'Brand', 'name' => $brand );
	}
	$currency = get_woocommerce_currency();
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		$list   = array_filter( array_map( 'floatval', (array) ( $prices['price'] ?? array() ) ) );
		if ( $list ) {
			$node['offers'] = array(
				'@type'         => 'AggregateOffer',
				'lowPrice'      => wc_format_decimal( min( $list ), wc_get_price_decimals() ),
				'highPrice'     => wc_format_decimal( max( $list ), wc_get_price_decimals() ),
				'offerCount'    => count( $list ),
				'priceCurrency' => $currency,
				'availability'  => semrs_woo_availability( $product->get_stock_status() ),
				'url'           => $url,
				'seller'        => array( '@id' => $org ),
			);
		}
	} elseif ( '' !== $product->get_price() ) {
		$offer = array(
			'@type'         => 'Offer',
			'price'         => wc_format_decimal( wc_get_price_to_display( $product ), wc_get_price_decimals() ),
			'priceCurrency' => $currency,
			'availability'  => semrs_woo_availability( $product->get_stock_status() ),
			'url'           => $url,
			'seller'        => array( '@id' => $org ),
		);
		$until = $product->get_date_on_sale_to();
		if ( $product->is_on_sale() && $until ) {
			$offer['priceValidUntil'] = $until->date( 'Y-m-d' );
		}
		$node['offers'] = $offer;
	}
	// Ratings only from real, approved reviews.
	if ( wc_review_ratings_enabled() && $product->get_review_count() > 0 && $product->get_average_rating() > 0 ) {
		$node['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) $product->get_average_rating(),
			'reviewCount' => (int) $product->get_review_count(),
			'bestRating'  => '5',
			'worstRating' => '1',
		);
		$reviews = get_comments( array( 'post_id' => $id, 'status' => 'approve', 'type' => 'review', 'number' => 5, 'parent' => 0 ) );
		foreach ( $reviews as $r ) {
			$stars = (int) get_comment_meta( $r->comment_ID, 'rating', true );
			if ( $stars < 1 ) {
				continue;
			}
			$node['review'][] = array(
				'@type'         => 'Review',
				'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => (string) $stars, 'bestRating' => '5', 'worstRating' => '1' ),
				'author'        => array( '@type' => 'Person', 'name' => $r->comment_author ),
				'datePublished' => mysql2date( 'c', $r->comment_date_gmt ),
				'reviewBody'    => wp_html_excerpt( wp_strip_all_tags( $r->comment_content ), 500 ),
			);
		}
	}
	$graph[] = array_filter( $node, function ( $v ) { return null !== $v && '' !== $v; } );
	return $graph;
}
add_filter( 'semrs_schema_graph', 'semrs_woo_schema' );

// Cart, checkout and account pages never belong in the sitemap.
add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $post_type ) {
		if ( 'page' === $post_type && semrs_woo_active() && function_exists( 'wc_get_page_id' ) ) {
			$skip                   = array_filter( array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'myaccount' ) ), function ( $id ) { return $id > 0; } );
			$args['post__not_in']   = array_merge( (array) ( $args['post__not_in'] ?? array() ), $skip );
		}
		return $args;
	},
	10,
	2
);

// Breadcrumbs on product pages: Home › Shop › Category › Product.
add_filter(
	'semrs_breadcrumb_trail',
	function ( $trail ) {
		if ( ! semrs_woo_active() || ! is_singular( 'product' ) || ! function_exists( 'wc_get_page_id' ) ) {
			return $trail;
		}
		$shop = wc_get_page_id( 'shop' );
		$out  = array( $trail[0] ?? array( semrs_seo_settings()['breadcrumb_home'], home_url( '/' ) ) );
		if ( $shop > 0 ) {
			$out[] = array( get_the_title( $shop ), get_permalink( $shop ) );
		}
		$terms = wc_get_product_terms( get_queried_object_id(), 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
		if ( $terms ) {
			$out[] = array( $terms[0]->name, get_term_link( $terms[0] ) );
		}
		$out[] = array( wp_strip_all_tags( get_the_title() ), get_permalink() );
		return $out;
	}
);

// Product fields: Brand and MPN (Inventory tab), and GTIN when WooCommerce has no field of its own.
add_action(
	'woocommerce_product_options_sku',
	function () {
		if ( ! function_exists( 'woocommerce_wp_text_input' ) ) {
			return;
		}
		woocommerce_wp_text_input( array( 'id' => '_semrs_brand', 'label' => __( 'Brand (SEO)', 'semrs-core' ), 'desc_tip' => true, 'description' => __( 'Shown to Google in the product schema. Empty = the Brands taxonomy or a "Brand" attribute.', 'semrs-core' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_semrs_mpn', 'label' => __( 'MPN (SEO)', 'semrs-core' ), 'desc_tip' => true, 'description' => __( 'Manufacturer part number, if the product has one.', 'semrs-core' ) ) );
		if ( ! method_exists( 'WC_Product', 'get_global_unique_id' ) ) {
			woocommerce_wp_text_input( array( 'id' => '_semrs_gtin', 'label' => __( 'GTIN / EAN / UPC (SEO)', 'semrs-core' ), 'desc_tip' => true, 'description' => __( 'The 8 to 14 digit barcode number.', 'semrs-core' ) ) );
		}
	}
);
add_action(
	'woocommerce_admin_process_product_object',
	function ( $product ) {
		foreach ( array( '_semrs_brand', '_semrs_mpn', '_semrs_gtin' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the product form nonce before this action.
				$product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		}
	}
);
