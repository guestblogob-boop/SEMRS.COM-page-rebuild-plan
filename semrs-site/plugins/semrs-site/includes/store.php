<?php
/**
 * SEMRS Store (semrs.com only): a storefront for SEMRS themes and plugins in
 * the style of Shopify, Etsy and eBay stores.
 *
 * - Products (semrs_product) with categories, a desktop + mobile gallery,
 *   price tiers (e.g. 1 / 5 / unlimited sites), features, FAQ and live
 *   version data from the SEMRS Update Server.
 * - /store/: collection page with search, category chips, free/paid filter
 *   and sorting; cards swap to the phone screenshot on hover.
 * - Product page: gallery with thumbnails, buy box with tier picker,
 *   trust details, accordions, related products, sticky buy bar on phones.
 * - Shortcodes: [semrs_products] (any product grid) and [semrs_pricing].
 * - SoftwareApplication schema with real offers only (no ratings).
 *
 * Checkout is not built in: each paid tier links to your checkout URL
 * (WooCommerce, Lemon Squeezy, Paddle, Stripe Payment Links…). With no URL
 * the button says checkout is opening soon and links to the contact page.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Content types
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		// Categories first, so /store/category/… wins over product addresses.
		register_taxonomy(
			'semrs_product_cat',
			'semrs_product',
			array(
				'labels'            => array(
					'name'          => __( 'Product categories', 'semrs-site' ),
					'singular_name' => __( 'Product category', 'semrs-site' ),
				),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'store/category', 'with_front' => false ),
			)
		);
		register_post_type(
			'semrs_product',
			array(
				'labels'       => array(
					'name'          => __( 'Products', 'semrs-site' ),
					'singular_name' => __( 'Product', 'semrs-site' ),
					'add_new_item'  => __( 'Add product', 'semrs-site' ),
					'edit_item'     => __( 'Edit product', 'semrs-site' ),
					'all_items'     => __( 'All products', 'semrs-site' ),
					'menu_name'     => __( 'Store', 'semrs-site' ),
				),
				'description'  => __( 'WordPress themes and plugins by SEMRS: free downloads and SEMRS Pro.', 'semrs-site' ),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-cart',
				'has_archive'  => 'store',
				'rewrite'      => array( 'slug' => 'store', 'with_front' => false ),
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			)
		);
	}
);

// Refresh rewrite rules once when the store's or portfolio's address rules change.
add_action(
	'init',
	function () {
		if ( '3' !== get_option( 'semrs_store_rewrite' ) ) {
			update_option( 'semrs_store_rewrite', '3' );
			flush_rewrite_rules();
		}
	},
	100
);

add_filter(
	'post_type_archive_title',
	function ( $title, $type ) {
		return 'semrs_product' === $type ? __( 'WordPress Themes & Plugins Store', 'semrs-site' ) : $title;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------------- */

/**
 * Normalised product data.
 *
 * @param int|WP_Post $post Product.
 * @return array
 */
function semrs_store_product( $post ) {
	$post = get_post( $post );
	$m    = get_post_meta( $post->ID, '_semrs_product', true );
	$m    = is_array( $m ) ? $m : array();
	$d    = array_merge(
		array(
			'kind'         => 'plugin',
			'slug'         => $post->post_name,
			'badge'        => '',
			'currency'     => 'USD',
			'period'       => 'year',
			'tiers'        => array(),
			'download'     => '',
			'wporg'        => '',
			'demo'         => '',
			'features'     => array(),
			'faq'          => array(),
			'needs'        => '',
			'requires_wp'  => '6.5',
			'requires_php' => '7.4',
			'version'      => '',
			'gallery'      => array(),
			'options'      => '',
			'bulk'         => '',
			'bundles'      => '',
		),
		$m
	);
	// Live release data from the SEMRS Update Server on this site.
	$d['updated'] = '';
	$d['tested']  = '';
	if ( function_exists( 'semrs_us_latest' ) ) {
		$rel = semrs_us_latest( $d['slug'] );
		if ( $rel ) {
			$d['version']      = $rel['version'];
			$d['updated']      = substr( (string) ( $rel['date'] ?? '' ), 0, 10 );
			$d['requires_wp']  = $rel['requires'] ?? $d['requires_wp'];
			$d['requires_php'] = $rel['requires_php'] ?? $d['requires_php'];
			$d['tested']       = $rel['tested'] ?? '';
		}
		if ( '' === $d['download'] && $rel && ! semrs_store_is_paid( $d ) ) {
			$d['download'] = rest_url( 'semrs-updates/v1/free' ) . '?slug=' . rawurlencode( $d['slug'] );
		}
	}
	// SEMRS Pro is sold as add-ons; prices come from SEMRS Updates → Sales & prices.
	$d['addons'] = 'semrs-pro' === $d['slug'] && function_exists( 'semrs_us_plans' );
	if ( $d['addons'] ) {
		$d['currency'] = semrs_us_sales()['currency'];
		$d['period']   = 'year';
		$d['tiers']    = array();
		foreach ( semrs_us_plans() as $p ) {
			if ( 'year' === $p['term'] ) {
				$d['tiers'][] = array( 'label' => semrs_us_plan_label( $p ), 'price' => $p['price'], 'compare' => 0, 'sites' => $p['sites'], 'url' => semrs_us_buy_url( $p ) );
			}
		}
	}
	$prices     = array_filter( wp_list_pluck( $d['tiers'], 'price' ), 'is_numeric' );
	$d['from']  = $prices ? min( $prices ) : 0;
	$d['paid']  = semrs_store_is_paid( $d );
	$d['short'] = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
	$terms      = get_the_terms( $post, 'semrs_product_cat' );
	$d['cats']  = is_array( $terms ) ? wp_list_pluck( $terms, 'slug' ) : array();
	$d['cat']   = is_array( $terms ) && $terms ? $terms[0] : null;
	return $d;
}

/**
 * Does any tier cost money?
 *
 * @param array $d Product data.
 * @return bool
 */
function semrs_store_is_paid( $d ) {
	foreach ( (array) $d['tiers'] as $t ) {
		if ( (float) $t['price'] > 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * Money format.
 *
 * @param float  $amount   Amount.
 * @param string $currency ISO code.
 * @return string
 */
function semrs_store_money( $amount, $currency = 'USD' ) {
	$symbols = array( 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AUD' => 'A$', 'CAD' => 'C$', 'NZD' => 'NZ$', 'PKR' => 'Rs ' );
	$num     = floor( (float) $amount ) == (float) $amount ? number_format_i18n( (float) $amount ) : number_format_i18n( (float) $amount, 2 ); // phpcs:ignore Universal.Operators.StrictComparisons -- whole-number check.
	return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] . $num : $num . ' ' . $currency;
}

/**
 * "per year" suffix.
 *
 * @param array $d Product data.
 * @return string
 */
function semrs_store_period( $d ) {
	$map = array(
		'year'  => __( '/ year', 'semrs-site' ),
		'month' => __( '/ month', 'semrs-site' ),
		''      => '',
	);
	return $map[ $d['period'] ] ?? '';
}

/**
 * Price line for cards: "Free", or "From $49 / year" with compare-at price.
 *
 * @param array $d Product data.
 * @return string HTML.
 */
function semrs_store_price_html( $d ) {
	if ( ! $d['paid'] ) {
		return '<span class="semrs-price semrs-price--free">' . esc_html__( 'Free', 'semrs-site' ) . '</span>';
	}
	$tier = null;
	foreach ( $d['tiers'] as $t ) {
		if ( (float) $t['price'] === (float) $d['from'] ) {
			$tier = $t;
			break;
		}
	}
	$out = '<span class="semrs-price">';
	if ( count( $d['tiers'] ) > 1 ) {
		$out .= '<span class="semrs-price__from">' . esc_html__( 'From', 'semrs-site' ) . '</span> ';
	}
	$out .= '<strong>' . esc_html( semrs_store_money( $d['from'], $d['currency'] ) ) . '</strong>';
	if ( $tier && ! empty( $tier['compare'] ) && (float) $tier['compare'] > (float) $tier['price'] ) {
		$out .= ' <s aria-label="' . esc_attr__( 'Regular price', 'semrs-site' ) . '">' . esc_html( semrs_store_money( $tier['compare'], $d['currency'] ) ) . '</s>';
	}
	$out .= ' <span class="semrs-price__per">' . esc_html( semrs_store_period( $d ) ) . '</span></span>';
	return $out;
}

/**
 * Gallery image IDs (desktop first), falling back to the featured image.
 *
 * @param WP_Post $post Product.
 * @param array   $d    Product data.
 * @return int[]
 */
function semrs_store_gallery( $post, $d ) {
	$ids = array_values( array_filter( array_map( 'absint', (array) $d['gallery'] ) ) );
	if ( ! $ids && has_post_thumbnail( $post ) ) {
		$ids = array( get_post_thumbnail_id( $post ) );
	}
	return $ids;
}

/**
 * Kind label.
 *
 * @param array $d Product data.
 * @return string
 */
function semrs_store_kind( $d ) {
	$k = array(
		'theme'  => __( 'Theme', 'semrs-site' ),
		'plugin' => __( 'Plugin', 'semrs-site' ),
		'addon'  => __( 'Add-on', 'semrs-site' ),
	);
	return $k[ $d['kind'] ] ?? $k['plugin'];
}

/* -------------------------------------------------------------------------
 * Front end: cards, grid, pricing
 * ---------------------------------------------------------------------- */

/**
 * Load store assets.
 */
function semrs_store_assets() {
	wp_enqueue_style( 'semrs-store', SEMRS_SITE_URL . 'assets/store.css', array(), SEMRS_SITE_VERSION );
	if ( function_exists( 'semrs_blocks_product_enqueue' ) ) {
		semrs_blocks_product_enqueue(); // Variant pickers on product pages.
	}
	wp_enqueue_script( 'semrs-store', SEMRS_SITE_URL . 'assets/store.js', array(), SEMRS_SITE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}

/**
 * Product card.
 *
 * @param WP_Post $post  Product.
 * @param int     $order Featured order (for sorting).
 * @return string
 */
function semrs_store_card( $post, $order = 0 ) {
	$d   = semrs_store_product( $post );
	$img = semrs_store_gallery( $post, $d );
	$url = get_permalink( $post );
	$out = sprintf(
		'<article class="semrs-card" data-title="%1$s" data-price="%2$s" data-date="%3$s" data-order="%4$d" data-cats="%5$s" data-paid="%6$d">',
		esc_attr( mb_strtolower( get_the_title( $post ) . ' ' . $d['short'] ) ),
		esc_attr( $d['paid'] ? $d['from'] : 0 ),
		esc_attr( get_post_time( 'U', true, $post ) ),
		(int) $order,
		esc_attr( implode( ' ', $d['cats'] ) ),
		$d['paid'] ? 1 : 0
	);
	$out .= '<a class="semrs-card__media" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">';
	if ( $img ) {
		$out .= wp_get_attachment_image( $img[0], 'large', false, array( 'class' => 'semrs-card__img', 'loading' => 'lazy', 'sizes' => '(max-width: 640px) 100vw, 400px' ) );
		if ( isset( $img[1] ) ) {
			$out .= wp_get_attachment_image( $img[1], 'medium_large', false, array( 'class' => 'semrs-card__img semrs-card__img--alt', 'loading' => 'lazy', 'alt' => '' ) );
		}
	}
	if ( $d['badge'] ) {
		$out .= '<span class="semrs-badge' . ( $d['paid'] ? ' semrs-badge--pro' : '' ) . '">' . esc_html( $d['badge'] ) . '</span>';
	}
	$out .= '</a><div class="semrs-card__body">';
	$out .= '<p class="semrs-card__kind">' . esc_html( semrs_store_kind( $d ) ) . ( $d['version'] ? ' · v' . esc_html( $d['version'] ) : '' ) . '</p>';
	$out .= '<h3 class="semrs-card__title"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
	$out .= '<p class="semrs-card__desc">' . esc_html( $d['short'] ) . '</p>';
	$out .= '<div class="semrs-card__foot">' . semrs_store_price_html( $d ) . '<a class="semrs-btn semrs-btn--sm" href="' . esc_url( $url ) . '">' . ( $d['paid'] ? esc_html__( 'View plans', 'semrs-site' ) : esc_html__( 'View & download', 'semrs-site' ) ) . '</a></div>';
	$out .= '<p class="semrs-card__trust">' . ( $d['paid'] ? esc_html__( 'Instant download · Updates while licensed', 'semrs-site' ) : esc_html__( 'Free forever · GPL · No sign-up', 'semrs-site' ) ) . '</p>';
	$out .= '</div></article>';
	return $out;
}

/**
 * [semrs_products filters="yes" category="" limit="" columns="3" exclude=""]
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_store_grid( $atts ) {
	$a = shortcode_atts(
		array(
			'filters'  => 'no',
			'category' => '',
			'limit'    => -1,
			'columns'  => 3,
			'exclude'  => '',
		),
		$atts,
		'semrs_products'
	);
	semrs_store_assets();
	$args = array(
		'post_type'      => 'semrs_product',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $a['limit'] > 0 ? (int) $a['limit'] : 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	);
	if ( $a['category'] ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'semrs_product_cat', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', explode( ',', $a['category'] ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery -- small catalogue.
	}
	$exclude = array_filter( array_map( 'absint', explode( ',', (string) $a['exclude'] ) ) );
	$posts   = array_filter(
		get_posts( $args ),
		function ( $p ) use ( $exclude ) {
			return ! in_array( (int) $p->ID, $exclude, true );
		}
	);
	if ( ! $posts ) {
		return current_user_can( 'edit_posts' ) ? '<p>' . esc_html__( 'No published products yet.', 'semrs-site' ) . '</p>' : '';
	}
	$cols = max( 2, min( 4, (int) $a['columns'] ) );
	$out  = '<div class="semrs-store" data-store>';
	if ( 'yes' === $a['filters'] ) {
		$cats = get_terms( array( 'taxonomy' => 'semrs_product_cat', 'hide_empty' => true ) );
		$out .= '<div class="semrs-store__bar" role="search">';
		$out .= '<label class="screen-reader-text" for="semrs-store-q">' . esc_html__( 'Search products', 'semrs-site' ) . '</label><input id="semrs-store-q" class="semrs-store__search" type="search" placeholder="' . esc_attr__( 'Search themes and plugins', 'semrs-site' ) . '" data-store-search>';
		$out .= '<div class="semrs-chips" role="group" aria-label="' . esc_attr__( 'Filter', 'semrs-site' ) . '"><button type="button" class="semrs-chip is-active" data-filter="all" aria-pressed="true">' . esc_html__( 'All', 'semrs-site' ) . '</button>';
		foreach ( is_array( $cats ) ? $cats : array() as $c ) {
			$out .= '<button type="button" class="semrs-chip" data-filter="cat:' . esc_attr( $c->slug ) . '" aria-pressed="false">' . esc_html( $c->name ) . '</button>';
		}
		$out .= '<button type="button" class="semrs-chip" data-filter="free" aria-pressed="false">' . esc_html__( 'Free', 'semrs-site' ) . '</button><button type="button" class="semrs-chip" data-filter="paid" aria-pressed="false">' . esc_html__( 'Paid', 'semrs-site' ) . '</button></div>';
		$out .= '<label class="semrs-store__sort"><span>' . esc_html__( 'Sort by', 'semrs-site' ) . '</span><select data-store-sort><option value="order">' . esc_html__( 'Featured', 'semrs-site' ) . '</option><option value="price-asc">' . esc_html__( 'Price: low to high', 'semrs-site' ) . '</option><option value="price-desc">' . esc_html__( 'Price: high to low', 'semrs-site' ) . '</option><option value="name">' . esc_html__( 'Name: A to Z', 'semrs-site' ) . '</option><option value="new">' . esc_html__( 'Newest', 'semrs-site' ) . '</option></select></label>';
		$out .= '</div><p class="semrs-store__count" aria-live="polite" data-store-count data-one="' . esc_attr__( '%d product', 'semrs-site' ) . '" data-many="' . esc_attr__( '%d products', 'semrs-site' ) . '">' . esc_html( sprintf( /* translators: %d: products */ _n( '%d product', '%d products', count( $posts ), 'semrs-site' ), count( $posts ) ) ) . '</p>';
	}
	$out .= '<div class="semrs-grid semrs-grid--' . $cols . '" data-store-grid>';
	$i    = 0;
	foreach ( $posts as $p ) {
		$out .= semrs_store_card( $p, $i++ );
	}
	$out .= '</div><p class="semrs-store__empty" hidden data-store-empty>' . esc_html__( 'No products match. Try another filter.', 'semrs-site' ) . '</p></div>';
	return $out;
}
add_shortcode( 'semrs_products', 'semrs_store_grid' );

/**
 * Buy button for a tier (or the "checkout opening soon" fallback).
 *
 * @param array  $tier  Tier.
 * @param string $class Extra class.
 * @return string
 */
function semrs_store_buy_button( $tier, $class = '' ) {
	if ( ! empty( $tier['url'] ) ) {
		return '<a class="semrs-btn semrs-btn--buy ' . esc_attr( $class ) . '" href="' . esc_url( $tier['url'] ) . '" rel="nofollow">' . esc_html__( 'Buy now', 'semrs-site' ) . '</a>';
	}
	$contact = get_page_by_path( 'contact-us' ) ? get_permalink( get_page_by_path( 'contact-us' ) ) : home_url( '/contact/' );
	return '<a class="semrs-btn semrs-btn--buy is-soon ' . esc_attr( $class ) . '" href="' . esc_url( $contact ) . '">' . esc_html__( 'Checkout opens soon: ask us', 'semrs-site' ) . '</a>';
}

/**
 * Buy box through the SEMRS Blocks engine: plans as plan cards plus any
 * extra options, bulk discounts and bundles. Returns '' without SEMRS Blocks.
 *
 * @param array $d Product data.
 * @return string
 */
function semrs_store_engine( $d ) {
	if ( ! function_exists( 'semrs_blocks_product_engine' ) || ! $d['tiers'] ) {
		return '';
	}
	$best   = semrs_store_best_value( $d['tiers'] );
	$values = array();
	foreach ( $d['tiers'] as $i => $t ) {
		$values[] = array(
			'label'   => $t['label'],
			'value'   => sanitize_title( $t['label'] ),
			'add'     => 0,
			'price'   => (float) $t['price'],
			'compare' => (float) $t['compare'],
			'url'     => $t['url'],
			'swatch'  => '',
			'image'   => 0,
			'soldout' => false,
			'flag'    => $i === $best ? __( 'Best value', 'semrs-site' ) : '',
		);
	}
	// Preselect the best-value plan.
	if ( $best > 0 ) {
		$values = array_merge( array( $values[ $best ] ), array_diff_key( $values, array( $best => 1 ) ) );
	}
	$groups  = array_merge(
		array(
			array(
				'key'    => 'plan',
				'label'  => __( 'Choose your plan', 'semrs-site' ),
				'type'   => 'plan',
				'values' => array_values( $values ),
			),
		),
		semrs_blocks_product_groups( $d['options'] )
	);
	$bulk    = semrs_blocks_product_bulk( $d['bulk'] );
	$contact = get_page_by_path( 'contact-us' ) ? get_permalink( get_page_by_path( 'contact-us' ) ) : home_url( '/contact/' );
	return semrs_blocks_product_engine(
		array(
			'currency'      => $d['currency'],
			'price'         => (float) $d['tiers'][0]['price'],
			'period'        => semrs_store_period( $d ),
			'groups'        => $groups,
			'qty'           => (bool) $bulk,
			'bulk'          => $bulk,
			'bundles'       => semrs_blocks_product_bundles( $d['bundles'] ),
			'url'           => '',
			'button'        => __( 'Buy now', 'semrs-site' ),
			'fallback'      => $contact,
			'fallback_text' => __( 'Checkout opens soon: ask us', 'semrs-site' ),
		)
	);
}

/**
 * Index of the tier with the lowest price per site ("Best value").
 *
 * @param array $tiers Tiers.
 * @return int
 */
function semrs_store_best_value( $tiers ) {
	$best = -1;
	$low  = PHP_INT_MAX;
	foreach ( $tiers as $i => $t ) {
		$sites = (int) $t['sites'] > 0 ? (int) $t['sites'] : 100; // Unlimited counts as many sites.
		$per   = (float) $t['price'] / $sites;
		if ( count( $tiers ) > 1 && $per < $low ) {
			$low  = $per;
			$best = $i;
		}
	}
	return $best;
}

/**
 * [semrs_pricing product="semrs-pro"]: tier cards.
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_store_pricing( $atts ) {
	$a    = shortcode_atts( array( 'product' => 'semrs-pro' ), $atts, 'semrs_pricing' );
	$post = get_page_by_path( sanitize_title( $a['product'] ), OBJECT, 'semrs_product' );
	if ( ! $post || ( 'publish' !== $post->post_status && ! current_user_can( 'edit_posts' ) ) ) {
		return '';
	}
	semrs_store_assets();
	$d    = semrs_store_product( $post );
	if ( $d['addons'] ) {
		return semrs_store_pro_plans();
	}
	$best = semrs_store_best_value( $d['tiers'] );
	$out  = '<div class="semrs-plans">';
	foreach ( $d['tiers'] as $i => $t ) {
		$out .= '<div class="semrs-plan' . ( $i === $best ? ' is-best' : '' ) . '">';
		$out .= $i === $best ? '<span class="semrs-plan__flag">' . esc_html__( 'Best value', 'semrs-site' ) . '</span>' : '';
		$out .= '<h3 class="semrs-plan__name">' . esc_html( $t['label'] ) . '</h3>';
		$out .= '<p class="semrs-plan__price"><strong>' . esc_html( semrs_store_money( $t['price'], $d['currency'] ) ) . '</strong> <span>' . esc_html( semrs_store_period( $d ) ) . '</span>';
		if ( ! empty( $t['compare'] ) && (float) $t['compare'] > (float) $t['price'] ) {
			$out .= ' <s>' . esc_html( semrs_store_money( $t['compare'], $d['currency'] ) ) . '</s>';
		}
		$out .= '</p>';
		if ( (int) $t['sites'] > 1 ) {
			/* translators: %s: price per site */
			$out .= '<p class="semrs-plan__per">' . esc_html( sprintf( __( '%s per site', 'semrs-site' ), semrs_store_money( round( $t['price'] / (int) $t['sites'], 2 ), $d['currency'] ) ) ) . '</p>';
		}
		$out .= '<ul class="semrs-plan__list"><li>' . esc_html( (int) $t['sites'] > 0 ? sprintf( /* translators: %d: sites */ _n( '%d website', '%d websites', (int) $t['sites'], 'semrs-site' ), (int) $t['sites'] ) : __( 'Unlimited websites', 'semrs-site' ) ) . '</li><li>' . esc_html__( 'Every Pro feature', 'semrs-site' ) . '</li><li>' . esc_html__( 'One-click updates and support while licensed', 'semrs-site' ) . '</li><li>' . esc_html__( 'Staging and local sites do not use a slot', 'semrs-site' ) . '</li></ul>';
		$out .= semrs_store_buy_button( $t ) . '</div>';
	}
	$out .= '</div><p class="semrs-plans__note">' . esc_html__( 'Prices in', 'semrs-site' ) . ' ' . esc_html( $d['currency'] ) . '. ' . esc_html__( 'Features keep working if a license expires; the license controls updates and support.', 'semrs-site' ) . '</p>';
	return $out;
}
add_shortcode( 'semrs_pricing', 'semrs_store_pricing' );

/**
 * What each SEMRS Pro add-on includes (short list for the plan cards).
 *
 * @return array<string, array{0:string,1:string[]}> slug => [promise, features]
 */
function semrs_store_pro_addon_info() {
	return array(
		'all'   => array( __( 'Every Pro feature on one key: SEO, speed, ads, theme and blocks.', 'semrs-site' ), array( __( 'SEO Pro + Speed Pro + Ads Pro + Theme & Blocks Pro', 'semrs-site' ), __( 'Replaces a premium SEO plugin, a caching plugin, an image optimizer, an ad inserter and a theme Pro add-on', 'semrs-site' ), __( 'One plugin, one key, one place for updates', 'semrs-site' ), __( 'Every new Pro feature we add, included', 'semrs-site' ) ) ),
		'seo'   => array( __( 'Rank and get indexed: the SEO tools other plugins charge most for.', 'semrs-site' ), array( __( 'AI titles, descriptions and alt text with your own AI key (no credit packs)', 'semrs-site' ), __( 'Automatic internal links and a broken link checker', 'semrs-site' ), __( 'Image, video and news sitemaps', 'semrs-site' ), __( 'Search Console stats and keyword rank tracking', 'semrs-site' ), __( 'Bulk SEO editor and custom schema', 'semrs-site' ), __( 'Multi-location local SEO', 'semrs-site' ) ) ),
		'speed' => array( __( 'Faster pages without a separate caching or image plugin.', 'semrs-site' ), array( __( 'Delay analytics, pixel and chat scripts until the visitor interacts', 'semrs-site' ), __( 'Cache preload and CDN', 'semrs-site' ), __( 'Minify and remove unused CSS', 'semrs-site' ), __( 'Convert your whole media library to WebP or AVIF, no quota', 'semrs-site' ) ) ),
		'ads'   => array( __( 'Earn more from AdSense while staying policy-safe.', 'semrs-site' ), array( __( 'More placements: repeat in content, between posts', 'semrs-site' ), __( 'Targeting by category, post age and dates', 'semrs-site' ), __( 'A/B rotation', 'semrs-site' ), __( 'Click protection and a polite ad-blocker message', 'semrs-site' ) ) ),
		'theme' => array( __( 'Pro design tools for the SEMRS theme and any block theme.', 'semrs-site' ), array( __( 'Mega menu, dark mode switch and table of contents', 'semrs-site' ), __( 'Popups and display conditions', 'semrs-site' ), __( 'Sliders, counters, 3D models and scroll animations', 'semrs-site' ), __( 'Custom layouts, languages with hreflang, Pro starter sites, white label', 'semrs-site' ) ) ),
	);
}

/**
 * [semrs_pro_plans]: SEMRS Pro add-ons and bundles with a 1 / 5 / unlimited
 * websites switch (no JavaScript). Prices and checkout links come from
 * SEMRS Updates → Sales & prices.
 *
 * @return string
 */
function semrs_store_pro_plans() {
	if ( ! function_exists( 'semrs_us_plans' ) ) {
		return '';
	}
	semrs_store_assets();
	$plans  = semrs_us_plans();
	$sales  = semrs_us_sales();
	$cur    = $sales['currency'];
	$tiers  = array(
		'1' => __( '1 website', 'semrs-site' ),
		'5' => __( '5 websites', 'semrs-site' ),
		'u' => __( 'Unlimited websites', 'semrs-site' ),
	);
	$offer  = semrs_us_offer_on() && $sales['offer_percent'] ? (int) $sales['offer_percent'] : 0;
	$out    = '<section class="semrs-pp" id="plans" aria-labelledby="semrs-pp-title"><h2 id="semrs-pp-title" class="semrs-pp__title">' . esc_html__( 'Pay only for what you use', 'semrs-site' ) . '</h2>';
	$out   .= '<p class="semrs-pp__lead">' . esc_html__( 'Pick one add-on or take All Pro for everything. One key, one plugin, and you can add more later.', 'semrs-site' ) . '</p>';
	if ( semrs_us_offer_on() ) {
		$label = $sales['offer_label'] ? $sales['offer_label'] : sprintf( /* translators: %d: percent */ __( 'Launch offer: %d%% off your first year', 'semrs-site' ), $offer );
		$out  .= '<p class="semrs-pp__offer"><strong>' . esc_html( $label ) . '</strong> ' . esc_html( sprintf( /* translators: %s: code */ __( 'Code %s is applied at checkout.', 'semrs-site' ), $sales['offer_code'] ) ) . ( $sales['offer_ends'] ? ' ' . esc_html( sprintf( /* translators: %s: date */ __( 'Ends %s.', 'semrs-site' ), mysql2date( get_option( 'date_format' ), $sales['offer_ends'] ) ) ) : '' ) . '</p>';
	}
	$out .= '<fieldset class="semrs-pp__tabs"><legend class="screen-reader-text">' . esc_html__( 'Number of websites', 'semrs-site' ) . '</legend>';
	foreach ( $tiers as $t => $label ) {
		$out .= '<input type="radio" class="semrs-pp__r" name="semrs-pp" id="semrs-pp-' . esc_attr( $t ) . '" value="' . esc_attr( $t ) . '"' . ( '1' === (string) $t ? ' checked' : '' ) . '><label for="semrs-pp-' . esc_attr( $t ) . '">' . esc_html( $label ) . '</label>';
	}
	$out .= '</fieldset><div class="semrs-plans semrs-pp__grid">';
	foreach ( semrs_store_pro_addon_info() as $addon => $info ) {
		$name  = 'all' === $addon ? __( 'All Pro', 'semrs-site' ) : semrs_us_addons()[ $addon ];
		$best  = 'all' === $addon;
		$out  .= '<div class="semrs-plan' . ( $best ? ' is-best' : '' ) . '">';
		if ( $best ) {
			$out .= '<span class="semrs-plan__flag">';
			foreach ( array_keys( $tiers ) as $t ) {
				$sum  = 0;
				foreach ( array_keys( semrs_us_addons() ) as $a ) {
					$sum += $plans[ $a . '-' . $t ]['price'];
				}
				$save = $sum > 0 ? (int) round( ( 1 - $plans[ 'all-' . $t ]['price'] / $sum ) * 100 ) : 0;
				/* translators: %d: percent */
				$out .= '<span class="semrs-pp__t semrs-pp__t--' . esc_attr( $t ) . '">' . esc_html( sprintf( __( 'Best value: save %d%%', 'semrs-site' ), $save ) ) . '</span>';
			}
			$out .= '</span>';
		}
		$out .= '<h3 class="semrs-plan__name">' . esc_html( $name ) . '</h3><p class="semrs-pp__promise">' . esc_html( $info[0] ) . '</p>';
		foreach ( array_keys( $tiers ) as $t ) {
			$p    = $plans[ $addon . '-' . $t ];
			$out .= '<div class="semrs-pp__t semrs-pp__t--' . esc_attr( $t ) . '"><p class="semrs-plan__price"><strong>' . esc_html( semrs_store_money( $p['price'], $cur ) ) . '</strong> <span>' . esc_html__( '/ year', 'semrs-site' ) . '</span></p>';
			if ( $offer ) {
				/* translators: %s: price */
				$out .= '<p class="semrs-plan__per">' . esc_html( sprintf( __( 'First year %s with the launch offer', 'semrs-site' ), semrs_store_money( round( $p['price'] * ( 100 - $offer ) / 100, 2 ), $cur ) ) ) . '</p>';
			} elseif ( $p['sites'] > 1 ) {
				/* translators: %s: price */
				$out .= '<p class="semrs-plan__per">' . esc_html( sprintf( __( '%s per website', 'semrs-site' ), semrs_store_money( round( $p['price'] / $p['sites'], 2 ), $cur ) ) ) . '</p>';
			} else {
				$out .= '<p class="semrs-plan__per">' . esc_html( $tiers[ $t ] ) . '</p>';
			}
			/* translators: %s: add-on name */
			$out .= '<a class="semrs-btn' . ( $best ? ' semrs-btn--buy' : ' semrs-btn--ghost' ) . '" href="' . esc_url( semrs_us_buy_url( $p ) ) . '" rel="nofollow">' . esc_html( sprintf( __( 'Get %s', 'semrs-site' ), $name ) ) . '</a></div>';
		}
		$out .= '<ul class="semrs-plan__list">';
		foreach ( $info[1] as $f ) {
			$out .= '<li>' . esc_html( $f ) . '</li>';
		}
		$out .= '</ul></div>';
	}
	$out .= '</div>';

	$l1   = $plans['all-life-1'];
	$lu   = $plans['all-life-u'];
	$out .= '<div class="semrs-pp__life"><div><h3>' . esc_html__( 'Pay once, keep it forever', 'semrs-site' ) . '</h3><p>' . esc_html__( 'All Pro with lifetime updates and support. No renewals.', 'semrs-site' ) . '</p></div><div class="semrs-pp__lifebtns">';
	/* translators: %s: price */
	$out .= '<a class="semrs-btn semrs-btn--ghost" href="' . esc_url( semrs_us_buy_url( $l1 ) ) . '" rel="nofollow">' . esc_html( sprintf( __( '1 website: %s once', 'semrs-site' ), semrs_store_money( $l1['price'], $cur ) ) ) . '</a>';
	/* translators: %s: price */
	$out .= '<a class="semrs-btn semrs-btn--buy" href="' . esc_url( semrs_us_buy_url( $lu ) ) . '" rel="nofollow">' . esc_html( sprintf( __( 'Unlimited websites: %s once', 'semrs-site' ), semrs_store_money( $lu['price'], $cur ) ) ) . '</a></div></div>';

	$perks = array(
		__( 'One key for every add-on. Buy another later and it switches on by itself, with nothing to reinstall.', 'semrs-site' ),
		__( 'Local and staging sites are free and never use a slot.', 'semrs-site' ),
		__( 'Add-ons you paid for keep working even if you stop renewing. Renewing keeps updates and support coming.', 'semrs-site' ),
		__( 'Pay by card or PayPal at checkout, or ask for an invoice and pay by bank transfer.', 'semrs-site' ),
		__( 'SEMRS Core, SEMRS Blocks, SEMRS Tools and the SEMRS theme stay free.', 'semrs-site' ),
	);
	if ( $sales['refund_days'] ) {
		/* translators: %d: days */
		array_unshift( $perks, sprintf( __( '%d-day money-back guarantee.', 'semrs-site' ), (int) $sales['refund_days'] ) );
	}
	$out .= '<ul class="semrs-pp__perks semrs-ticks">';
	foreach ( $perks as $perk ) {
		$out .= '<li>' . esc_html( $perk ) . '</li>';
	}
	$quote = $sales['quote_page'] ? get_permalink( (int) $sales['quote_page'] ) : home_url( '/get-a-quote/' );
	$out  .= '</ul><p class="semrs-plans__note">' . esc_html( sprintf( /* translators: %s: currency */ __( 'Prices in %s, plus tax where it applies.', 'semrs-site' ), $cur ) ) . ' <a href="' . esc_url( add_query_arg( 'plan', 'invoice', $quote ) ) . '">' . esc_html__( 'Prefer an invoice?', 'semrs-site' ) . '</a> · <a href="' . esc_url( semrs_us_account_url() ) . '">' . esc_html__( 'Already a customer? My licenses', 'semrs-site' ) . '</a></p></section>';
	return $out;
}
add_shortcode( 'semrs_pro_plans', 'semrs_store_pro_plans' );

/* -------------------------------------------------------------------------
 * Templates
 * ---------------------------------------------------------------------- */

/**
 * Is this a store page?
 *
 * @return bool
 */
function semrs_store_is_store() {
	return is_singular( 'semrs_product' ) || is_post_type_archive( 'semrs_product' ) || is_tax( 'semrs_product_cat' );
}

add_filter(
	'template_include',
	function ( $template ) {
		if ( is_singular( 'semrs_product' ) ) {
			return SEMRS_SITE_DIR . 'templates/store-single.php';
		}
		if ( is_post_type_archive( 'semrs_product' ) || is_tax( 'semrs_product_cat' ) ) {
			return SEMRS_SITE_DIR . 'templates/store-archive.php';
		}
		return $template;
	}
);
add_filter(
	'semrs_theme_layout',
	function ( $layout ) {
		return semrs_store_is_store() ? 'none' : $layout;
	}
);
add_filter(
	'body_class',
	function ( $c ) {
		if ( semrs_store_is_store() ) {
			$c[] = 'sp-full-width';
			$c[] = 'semrs-store-page';
		}
		return $c;
	}
);
add_action(
	'wp_enqueue_scripts',
	function () {
		// Store pages, and any page using a store shortcode (styles in the head, so nothing shifts).
		$post = is_singular() ? get_post() : null;
		if ( semrs_store_is_store() || ( $post && preg_match( '/\[semrs_(pro_plans|pricing|products)\b/', (string) $post->post_content ) ) ) {
			semrs_store_assets();
		}
	}
);

/**
 * SoftwareApplication schema on product pages (offers only; no ratings).
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'semrs_product' ) ) {
			return;
		}
		$post   = get_queried_object();
		$d      = semrs_store_product( $post );
		$img    = semrs_store_gallery( $post, $d );
		$schema = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'SoftwareApplication',
			'name'                => get_the_title( $post ),
			'description'         => wp_strip_all_tags( $d['short'] ),
			'url'                 => get_permalink( $post ),
			'applicationCategory' => 'WebApplication',
			'operatingSystem'     => 'WordPress ' . $d['requires_wp'] . '+',
			'publisher'           => array( '@type' => 'Organization', 'name' => 'SEMRS', 'url' => home_url( '/' ) ),
		);
		if ( $d['version'] ) {
			$schema['softwareVersion'] = $d['version'];
		}
		if ( $img ) {
			$schema['image'] = wp_get_attachment_image_url( $img[0], 'large' );
		}
		if ( $d['paid'] ) {
			$prices          = wp_list_pluck( $d['tiers'], 'price' );
			$schema['offers'] = array(
				'@type'         => 'AggregateOffer',
				'priceCurrency' => $d['currency'],
				'lowPrice'      => (string) min( $prices ),
				'highPrice'     => (string) max( $prices ),
				'offerCount'    => count( $prices ),
				'availability'  => 'https://schema.org/InStock',
			);
		} else {
			$schema['offers'] = array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => $d['currency'] );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	},
	5
);

/* -------------------------------------------------------------------------
 * Admin: product details box
 * ---------------------------------------------------------------------- */

if ( is_admin() ) {
	add_action(
		'add_meta_boxes',
		function () {
			add_meta_box( 'semrs_product_box', __( 'Product details', 'semrs-site' ), 'semrs_store_box', 'semrs_product', 'normal', 'high' );
		}
	);
	add_action( 'save_post_semrs_product', 'semrs_store_save', 10, 1 );
}

/**
 * Tiers as editable lines.
 *
 * @param array $tiers Tiers.
 * @return string
 */
function semrs_store_tiers_text( $tiers ) {
	return implode(
		"\n",
		array_map(
			function ( $t ) {
				return implode( ' | ', array( $t['label'], $t['sites'], $t['price'], $t['compare'], $t['url'] ) );
			},
			(array) $tiers
		)
	);
}

/**
 * Product details box.
 *
 * @param WP_Post $post Product.
 */
function semrs_store_box( $post ) {
	$d = semrs_store_product( $post );
	wp_nonce_field( 'semrs_store_save', 'semrs_store_nonce' );
	$f = function ( $key, $label, $help = '', $type = 'text' ) use ( $d ) {
		$val = is_array( $d[ $key ] ) ? implode( "\n", $d[ $key ] ) : (string) $d[ $key ];
		echo '<p><label for="semrs-p-' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		if ( 'textarea' === $type ) {
			echo '<textarea id="semrs-p-' . esc_attr( $key ) . '" class="large-text code" rows="5" name="semrs_p[' . esc_attr( $key ) . ']">' . esc_textarea( $val ) . '</textarea>';
		} else {
			echo '<input id="semrs-p-' . esc_attr( $key ) . '" class="large-text" type="' . esc_attr( $type ) . '" name="semrs_p[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '">';
		}
		echo $help ? '<span class="description">' . esc_html( $help ) . '</span>' : '';
		echo '</p>';
	};
	echo '<p><label><strong>' . esc_html__( 'Type', 'semrs-site' ) . '</strong> <select name="semrs_p[kind]">';
	foreach ( array( 'theme', 'plugin', 'addon' ) as $k ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $d['kind'], $k, false ) . '>' . esc_html( semrs_store_kind( array( 'kind' => $k ) ) ) . '</option>';
	}
	echo '</select></label> &nbsp; <label><strong>' . esc_html__( 'Currency', 'semrs-site' ) . '</strong> <input name="semrs_p[currency]" value="' . esc_attr( $d['currency'] ) . '" size="4"></label> &nbsp; <label><strong>' . esc_html__( 'Billing', 'semrs-site' ) . '</strong> <select name="semrs_p[period]">';
	foreach ( array( 'year' => __( 'per year', 'semrs-site' ), 'month' => __( 'per month', 'semrs-site' ), '' => __( 'one-time', 'semrs-site' ) ) as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $d['period'], $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><strong>' . esc_html__( 'Plans', 'semrs-site' ) . '</strong><br><textarea class="large-text code" rows="4" name="semrs_p[tiers]" placeholder="1 site | 1 | 49 | 69 | https://checkout.example.com/…">' . esc_textarea( semrs_store_tiers_text( $d['tiers'] ) ) . '</textarea><span class="description">' . esc_html__( 'One plan per line: name | sites (0 = unlimited) | price | regular price (optional, shown crossed out) | checkout URL. Leave empty for a free product.', 'semrs-site' ) . '</span></p>';
	$f( 'slug', __( 'Update-server slug', 'semrs-site' ), __( 'Version, date and requirements are read from SEMRS Updates for this slug. Free products get a direct download link from it.', 'semrs-site' ) );
	$f( 'badge', __( 'Badge', 'semrs-site' ), __( 'Short label on the card, e.g. Free, Pro, New.', 'semrs-site' ) );
	$f( 'download', __( 'Download URL (free products)', 'semrs-site' ), __( 'Empty = the latest release from SEMRS Updates.', 'semrs-site' ), 'url' );
	$f( 'wporg', __( 'WordPress.org URL', 'semrs-site' ), __( 'Add it once the product is approved on WordPress.org.', 'semrs-site' ), 'url' );
	$f( 'demo', __( 'Live demo URL', 'semrs-site' ), '', 'url' );
	$f( 'needs', __( 'Needs', 'semrs-site' ), __( 'e.g. "Needs the free SEMRS Core plugin".', 'semrs-site' ) );
	$f( 'features', __( 'Feature list', 'semrs-site' ), __( 'One per line.', 'semrs-site' ), 'textarea' );
	$f( 'faq', __( 'FAQ', 'semrs-site' ), __( 'One per line: Question | Answer', 'semrs-site' ), 'textarea' );
	$f( 'options', __( 'Extra options (variants)', 'semrs-site' ), __( 'One group per line, e.g. "Color: Navy #0b1f3a, Gold #d4a72c" or "Support: Standard, Priority +29". "+N" adds to the plan price, "(sold out)" disables a value. Needs SEMRS Blocks.', 'semrs-site' ), 'textarea' );
	$f( 'bulk', __( 'Bulk discounts', 'semrs-site' ), __( 'quantity | % off, one per line (e.g. 3 | 10). Shows a quantity picker.', 'semrs-site' ), 'textarea' );
	$f( 'bundles', __( 'Bundle add-ons', 'semrs-site' ), __( 'name | price | regular price (optional), one per line.', 'semrs-site' ), 'textarea' );
	echo '<p><strong>' . esc_html__( 'Gallery', 'semrs-site' ) . '</strong><br><input class="regular-text" name="semrs_p[gallery]" value="' . esc_attr( implode( ',', (array) $d['gallery'] ) ) . '"> <span class="description">' . esc_html__( 'Media IDs, comma-separated: computer screenshot first, then phone screenshot.', 'semrs-site' ) . '</span></p>';
}

/**
 * Save the box.
 *
 * @param int $post_id Product ID.
 */
function semrs_store_save( $post_id ) {
	if ( ! isset( $_POST['semrs_store_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_store_nonce'] ) ), 'semrs_store_save' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$in  = isset( $_POST['semrs_p'] ) ? map_deep( wp_unslash( $_POST['semrs_p'] ), 'sanitize_textarea_field' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
	$out = semrs_store_sanitize( $in );
	update_post_meta( $post_id, '_semrs_product', $out );
}

/**
 * Sanitize product data (box input or installer data).
 *
 * @param array $in Raw.
 * @return array
 */
function semrs_store_sanitize( $in ) {
	$lines = function ( $v ) {
		return array_values( array_filter( array_map( 'trim', is_array( $v ) ? $v : preg_split( '/\R/', (string) $v ) ) ) );
	};
	$tiers = array();
	foreach ( is_array( $in['tiers'] ?? null ) ? $in['tiers'] : $lines( $in['tiers'] ?? '' ) as $t ) {
		$p = is_array( $t ) ? array_values( $t ) : array_map( 'trim', explode( '|', $t ) );
		if ( '' === ( $p[0] ?? '' ) ) {
			continue;
		}
		$tiers[] = array(
			'label'   => sanitize_text_field( $p[0] ),
			'sites'   => absint( $p[1] ?? 1 ),
			'price'   => (float) ( $p[2] ?? 0 ),
			'compare' => isset( $p[3] ) && '' !== $p[3] ? (float) $p[3] : '',
			'url'     => esc_url_raw( $p[4] ?? '' ),
		);
	}
	return array(
		'kind'     => in_array( $in['kind'] ?? '', array( 'theme', 'plugin', 'addon' ), true ) ? $in['kind'] : 'plugin',
		'slug'     => sanitize_key( $in['slug'] ?? '' ),
		'badge'    => sanitize_text_field( $in['badge'] ?? '' ),
		'currency' => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $in['currency'] ?? 'USD' ) ), 0, 3 ) ) ?: 'USD',
		'period'   => in_array( $in['period'] ?? 'year', array( 'year', 'month', '' ), true ) ? ( $in['period'] ?? 'year' ) : 'year',
		'tiers'    => $tiers,
		'download' => esc_url_raw( $in['download'] ?? '' ),
		'wporg'    => esc_url_raw( $in['wporg'] ?? '' ),
		'demo'     => esc_url_raw( $in['demo'] ?? '' ),
		'needs'    => sanitize_text_field( $in['needs'] ?? '' ),
		'features' => array_map( 'sanitize_text_field', $lines( $in['features'] ?? '' ) ),
		'faq'      => array_map( 'sanitize_text_field', $lines( $in['faq'] ?? '' ) ),
		'gallery'  => array_values( array_filter( array_map( 'absint', is_array( $in['gallery'] ?? null ) ? $in['gallery'] : explode( ',', (string) ( $in['gallery'] ?? '' ) ) ) ) ),
		'options'  => sanitize_textarea_field( $in['options'] ?? '' ),
		'bulk'     => sanitize_textarea_field( $in['bulk'] ?? '' ),
		'bundles'  => sanitize_textarea_field( $in['bundles'] ?? '' ),
	);
}
