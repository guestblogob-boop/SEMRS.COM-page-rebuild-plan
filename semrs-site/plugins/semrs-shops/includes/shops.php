<?php
/**
 * Ready-made shops built from catalogue files (catalogues/{id}.json):
 *
 * - digital: Digital Downloads Shop (Christmas SVGs, printables, gift tags,
 *   cards, planners, workbooks, coloring pages, shirt, hat, cup, wall and car
 *   decal designs, pet and animal designs, custom portraits, personalised
 *   prints, Canva / Notion templates…).
 * - crochet: Crochet Pattern Shop (amigurumi animals and birds, pet
 *   accessories, blankets, wearables, home decor, seasonal, bags, car
 *   accessories, plus a crochet help page).
 *
 * Every catalogue is offered in four storefront styles, each a complete
 * starter site in SEMRS → Templates (home, shop, categories, every product,
 * about, policies, FAQ, contact):
 *
 * - shopify: brand storefront (hero, collections, featured grid).
 * - etsy:    maker shop (shop header, announcement, sections sidebar, about, policies).
 * - ebay:    store (store categories, listing rows, store info).
 * - amazon:  brand store (category bar, product rows, comparison chart, Q&A).
 *
 * Section IDs: "{cid}-{part}~{style}", e.g. "crochet-home~etsy",
 * "digital-product-pet-portrait~amazon".
 *
 * Prices are 2026 marketplace-median estimates to review; policies and
 * delivery times are placeholders for the shop owner. Product images are
 * original SEMRS artwork (GPL). More catalogues: `semrs_blocks_shop_catalogs`.
 *
 * @package semrs-shops
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catalogue IDs => JSON files.
 *
 * @return array
 */
function semrs_blocks_shop_catalogs() {
	return apply_filters(
		'semrs_blocks_shop_catalogs',
		array(
			'digital' => SEMRS_SHOPS_DIR . 'catalogues/digital.json',
			'crochet' => SEMRS_SHOPS_DIR . 'catalogues/crochet.json',
		)
	);
}

/**
 * Storefront styles: key => [label, description, buy button text].
 *
 * @return array
 */
function semrs_shops_styles() {
	return array(
		'shopify' => array( 'Shopify style', 'Brand storefront: big hero, collection tiles and a featured grid.', 'Add to cart' ),
		'etsy'    => array( 'Etsy style', 'Maker shop: shop header with announcement, sections sidebar beside all listings, about and shop policies.', 'Buy it now' ),
		'ebay'    => array( 'eBay style', 'Store: store categories beside listing rows, and store information.', 'Buy It Now' ),
		'amazon'  => array( 'Amazon style', 'Brand store: category bar, product rows per category, comparison chart and Q&A.', 'Buy Now' ),
	);
}

/**
 * One catalogue.
 *
 * @param string $cid Catalogue ID.
 * @return array{categories: array, products: array, site: array}
 */
function semrs_blocks_shop( $cid ) {
	static $cache = array();
	if ( ! isset( $cache[ $cid ] ) ) {
		$files = semrs_blocks_shop_catalogs();
		$data  = isset( $files[ $cid ] ) && file_exists( $files[ $cid ] ) ? json_decode( (string) file_get_contents( $files[ $cid ] ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data  = is_array( $data ) ? $data : array();
		$cache[ $cid ] = array_merge( array( 'categories' => array(), 'products' => array(), 'site' => array() ), $data );
	}
	return $cache[ $cid ];
}

/* -------------------------------------------------------------------------
 * Block markup helpers
 * ---------------------------------------------------------------------- */

/**
 * Product block markup for a catalogue item.
 *
 * @param string $cid    Catalogue ID.
 * @param array  $p      Product.
 * @param string $layout full|card.
 * @param string $style  Storefront style.
 * @return string
 */
function semrs_blocks_shop_block( $cid, $p, $layout, $style = 'shopify' ) {
	$styles = semrs_shops_styles();
	$attrs  = array(
		'layout'   => $layout,
		'title'    => $p['title'],
		'imageUrl' => '{{img:' . $cid . '-' . $p['key'] . '}}',
		'badge'    => $p['badge'] ?? '',
		'price'    => $p['price'],
		'compare'  => $p['compare'] ?? '',
		'options'  => $p['options'] ?? '',
	);
	if ( 'card' === $layout ) {
		$attrs['link'] = '/' . $p['key'] . '/';
	} else {
		$attrs += array(
			'text'    => $p['text'],
			'bundles' => $p['bundles'] ?? '',
			'bulk'    => $p['bulk'] ?? '',
			'qty'     => ! empty( $p['qty'] ),
			'max'     => 100,
			'button'  => $styles[ $style ][2] ?? 'Buy now',
			'url'     => '',
			'schema'  => true,
		);
	}
	return '<!-- wp:semrs/product ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Grid of product cards.
 *
 * @param string $cid      Catalogue ID.
 * @param array  $products Products.
 * @param string $style    Storefront style.
 * @param int    $cols     Columns.
 * @return string
 */
function semrs_blocks_shop_grid( $cid, $products, $style = 'shopify', $cols = 4 ) {
	$list  = 'ebay' === $style;
	$attrs = array(
		'layout'        => 'grid',
		'columns'       => $list ? 1 : $cols,
		'columnsTablet' => $list ? 1 : 2,
		'gap'           => $list ? 12 : 24,
	);
	if ( $list ) {
		$attrs['className'] = 'semrs-sf-list';
	}
	$out = '<!-- wp:semrs/container ' . wp_json_encode( $attrs ) . ' -->';
	foreach ( $products as $p ) {
		$out .= semrs_blocks_shop_block( $cid, $p, 'card', $style );
	}
	return $out . '<!-- /wp:semrs/container -->';
}

/**
 * Paragraph block.
 *
 * @param string $text  Text.
 * @param string $class Extra class.
 * @return string
 */
function semrs_blocks_shop_p( $text, $class = '' ) {
	return '<!-- wp:paragraph' . ( $class ? ' {"className":"' . esc_attr( $class ) . '"}' : '' ) . ' --><p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
}

/**
 * List block (plain items or [text, url] links).
 *
 * @param array  $items Items.
 * @param string $class Extra class.
 * @return string
 */
function semrs_blocks_shop_list( $items, $class = '' ) {
	$li = '';
	foreach ( $items as $i ) {
		$li .= '<!-- wp:list-item --><li>' . ( is_array( $i ) ? '<a href="' . esc_url( $i[1] ) . '">' . esc_html( $i[0] ) . '</a>' : esc_html( $i ) ) . '</li><!-- /wp:list-item -->';
	}
	return '<!-- wp:list' . ( $class ? ' {"className":"' . esc_attr( $class ) . '"}' : '' ) . ' --><ul class="wp-block-list' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . $li . '</ul><!-- /wp:list -->';
}

/**
 * Accordion item.
 *
 * @param string $title Title.
 * @param string $inner Inner blocks.
 * @param bool   $open  Open on load.
 * @return string
 */
function semrs_blocks_shop_acc( $title, $inner, $open = false ) {
	return '<!-- wp:semrs/accordion-item ' . wp_json_encode( array( 'title' => $title, 'open' => $open ), JSON_UNESCAPED_UNICODE ) . ' -->' . $inner . '<!-- /wp:semrs/accordion-item -->';
}

/**
 * Heading with optional intro and anchor.
 *
 * @param string $title  Title.
 * @param string $text   Intro.
 * @param string $anchor HTML anchor.
 * @param int    $level  Heading level.
 * @return string
 */
function semrs_blocks_shop_head( $title, $text = '', $anchor = '', $level = 2 ) {
	$attrs = array( 'level' => $level );
	if ( $anchor ) {
		$attrs['anchor'] = $anchor;
	}
	return '<!-- wp:heading ' . wp_json_encode( $attrs ) . ' --><h' . (int) $level . ' class="wp-block-heading"' . ( $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '' ) . '>' . esc_html( $title ) . '</h' . (int) $level . '><!-- /wp:heading -->' . ( $text ? semrs_blocks_shop_p( $text ) : '' );
}

/**
 * Full-width section carrying the storefront style class.
 *
 * @param string $inner Inner blocks.
 * @param string $style Storefront style.
 * @param array  $extra Extra container attributes.
 * @return string
 */
function semrs_blocks_shop_section( $inner, $style = 'shopify', $extra = array() ) {
	$attrs = array_merge(
		array(
			'align'     => 'full',
			'padTop'    => 48,
			'padBottom' => 48,
			'tag'       => 'section',
			'className' => 'semrs-sf semrs-sf-' . $style,
		),
		$extra
	);
	return '<!-- wp:semrs/container ' . wp_json_encode( $attrs ) . ' -->' . $inner . '<!-- /wp:semrs/container -->';
}

/**
 * Row with a sidebar list and a main column.
 *
 * @param string $side Sidebar blocks.
 * @param string $main Main blocks.
 * @return string
 */
function semrs_blocks_shop_sidebar( $side, $main ) {
	return '<!-- wp:columns {"className":"semrs-sf-cols"} --><div class="wp-block-columns semrs-sf-cols"><!-- wp:column {"width":"240px","className":"semrs-sf-side"} --><div class="wp-block-column semrs-sf-side" style="flex-basis:240px">' . $side . '</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">' . $main . '</div><!-- /wp:column --></div><!-- /wp:columns -->';
}

/**
 * Table block.
 *
 * @param string[] $head Header cells.
 * @param array    $rows Rows of cells.
 * @return string
 */
function semrs_blocks_shop_table( $head, $rows ) {
	$h = '';
	foreach ( $head as $c ) {
		$h .= '<th>' . esc_html( $c ) . '</th>';
	}
	$b = '';
	foreach ( $rows as $r ) {
		$b .= '<tr>';
		foreach ( $r as $c ) {
			$b .= '<td>' . esc_html( $c ) . '</td>';
		}
		$b .= '</tr>';
	}
	return '<!-- wp:table {"className":"is-style-stripes"} --><figure class="wp-block-table is-style-stripes"><table><thead><tr>' . $h . '</tr></thead><tbody>' . $b . '</tbody></table></figure><!-- /wp:table -->';
}

/**
 * Buttons block from [text, url] pairs.
 *
 * @param array $buttons Buttons.
 * @return string
 */
function semrs_blocks_shop_buttons( $buttons ) {
	$out = '';
	foreach ( $buttons as $i => $b ) {
		$out .= '<!-- wp:button' . ( $i ? ' {"className":"is-style-outline"}' : '' ) . ' --><div class="wp-block-button' . ( $i ? ' is-style-outline' : '' ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $b[1] ) . '">' . esc_html( $b[0] ) . '</a></div><!-- /wp:button -->';
	}
	return '<!-- wp:buttons --><div class="wp-block-buttons">' . $out . '</div><!-- /wp:buttons -->';
}

/* -------------------------------------------------------------------------
 * Sections for the template engine
 * ---------------------------------------------------------------------- */

/**
 * Category links for sidebars: [label (count), url].
 *
 * @param array $d  Catalogue.
 * @param array $by Products by category.
 * @return array
 */
function semrs_blocks_shop_cat_links( $d, $by ) {
	$links = array( array( sprintf( 'All items (%d)', count( $d['products'] ) ), '/shop/' ) );
	foreach ( $d['categories'] as $slug => $label ) {
		if ( ! empty( $by[ $slug ] ) ) {
			$links[] = array( $label . ' (' . count( $by[ $slug ] ) . ')', isset( $d['site']['cat_pages'][ $slug ] ) ? '/' . $slug . '/' : '/shop/#' . $slug );
		}
	}
	return $links;
}

/**
 * Build a shop section.
 *
 * @param string $html Existing HTML ('' when not handled yet).
 * @param string $id   Section ID.
 * @return string
 */
function semrs_blocks_shop_section_html( $html, $id ) {
	if ( '' !== $html ) {
		return $html;
	}
	$style = 'shopify';
	if ( false !== strpos( $id, '~' ) ) {
		list( $id, $style ) = explode( '~', $id, 2 );
	}
	if ( ! isset( semrs_shops_styles()[ $style ] ) ) {
		$style = 'shopify';
	}
	$cid = '';
	foreach ( array_keys( semrs_blocks_shop_catalogs() ) as $c ) {
		if ( 0 === strpos( $id, $c . '-' ) ) {
			$cid = $c;
			break;
		}
	}
	if ( '' === $cid ) {
		return '';
	}
	$part  = substr( $id, strlen( $cid ) + 1 );
	$d     = semrs_blocks_shop( $cid );
	$site  = $d['site'];
	$prods = $d['products'];
	$by    = array();
	foreach ( $prods as $p ) {
		$by[ $p['cat'] ][] = $p;
	}
	$pick = function ( $keys ) use ( $prods ) {
		$out = array();
		foreach ( (array) $keys as $k ) {
			foreach ( $prods as $p ) {
				if ( $p['key'] === $k ) {
					$out[] = $p;
				}
			}
		}
		return $out;
	};

	// Product page.
	if ( 0 === strpos( $part, 'product-' ) ) {
		$key = substr( $part, 8 );
		$p   = $pick( array( $key ) );
		if ( ! $p ) {
			return '';
		}
		$p      = $p[0];
		$custom = ! empty( $p['made'] );
		$how    = $custom
			? array( 'Choose your options and add your personalisation.', 'Check out securely.', 'Send your photos or details by email (for portraits).', 'Receive your proof, ask for one revision, then get your final files.' )
			: array( 'Choose your options and check out.', 'Download your files from the link on the order page and in your email.', 'Print, open in your app or start making.' );
		$acc    = semrs_blocks_shop_acc( 'amazon' === $style ? 'Product details' : ( 'ebay' === $style ? 'Item description' : 'What you get' ), semrs_blocks_shop_list( $p['included'] ), true )
			. semrs_blocks_shop_acc( 'How it works', semrs_blocks_shop_list( $how ) )
			. semrs_blocks_shop_acc( 'Licence', semrs_blocks_shop_p( $p['license'] ) )
			. ( $custom ? semrs_blocks_shop_acc( 'How it is made', semrs_blocks_shop_p( 'Describe your process here, for example hand-drawn in Procreate. If you use AI tools, say so: marketplaces such as Etsy require it.' ) ) : '' )
			. semrs_blocks_shop_acc( 'ebay' === $style ? 'Delivery & returns' : 'Refunds', semrs_blocks_shop_p( 'Digital items are delivered by email after checkout. Write your refund policy for digital files and custom orders here.' ) );
		$more   = array();
		foreach ( array_merge( $by[ $p['cat'] ], $prods ) as $x ) {
			if ( count( $more ) < 4 && $x['key'] !== $key && ! isset( $more[ $x['key'] ] ) ) {
				$more[ $x['key'] ] = $x;
			}
		}
		$more_title = array(
			'shopify' => 'You may also like',
			'etsy'    => 'More from this shop',
			'ebay'    => 'Similar items in this store',
			'amazon'  => 'Related products',
		);
		$crumbs     = 'shopify' === $style ? '' : semrs_blocks_shop_p( 'Shop › ' . ( $d['categories'][ $p['cat'] ] ?? '' ), 'semrs-sf-crumbs' );
		return semrs_blocks_shop_section( $crumbs . semrs_blocks_shop_block( $cid, $p, 'full', $style ) . '<!-- wp:semrs/accordion -->' . $acc . '<!-- /wp:semrs/accordion -->' . semrs_blocks_shop_head( $more_title[ $style ] ) . semrs_blocks_shop_grid( $cid, array_values( $more ), 'ebay' === $style ? 'shopify' : $style ), $style );
	}

	// Category page.
	if ( 0 === strpos( $part, 'cat-' ) ) {
		$cat = substr( $part, 4 );
		if ( empty( $by[ $cat ] ) ) {
			return '';
		}
		$grid = semrs_blocks_shop_grid( $cid, $by[ $cat ], $style );
		if ( 'etsy' === $style || 'ebay' === $style ) {
			$grid = semrs_blocks_shop_sidebar( semrs_blocks_shop_head( 'etsy' === $style ? 'Sections' : 'Store categories', '', '', 3 ) . semrs_blocks_shop_list( semrs_blocks_shop_cat_links( $d, $by ), 'semrs-sf-nav' ), $grid );
		}
		return semrs_blocks_shop_section( $grid, $style );
	}

	$hero_img = $site['hero_image'] ?? '';
	$img_tag  = $hero_img ? '<!-- wp:image {"sizeSlug":"large","className":"is-style-rounded semrs-sf-hero-img"} --><figure class="wp-block-image size-large is-style-rounded semrs-sf-hero-img"><img src="{{img:' . esc_attr( $cid . '-' . $hero_img ) . '}}" alt="' . esc_attr( $site['hero_title'] ?? '' ) . '"/></figure><!-- /wp:image -->' : '';

	switch ( $part ) {
		case 'shop':
			$groups = '';
			foreach ( $d['categories'] as $slug => $label ) {
				if ( ! empty( $by[ $slug ] ) ) {
					$groups .= semrs_blocks_shop_head( $label, '', $slug ) . semrs_blocks_shop_grid( $cid, $by[ $slug ], $style );
				}
			}
			if ( 'etsy' === $style || 'ebay' === $style ) {
				$groups = semrs_blocks_shop_sidebar( semrs_blocks_shop_head( 'etsy' === $style ? 'Sections' : 'Store categories', '', '', 3 ) . semrs_blocks_shop_list( semrs_blocks_shop_cat_links( $d, $by ), 'semrs-sf-nav' ), $groups );
			} elseif ( 'amazon' === $style ) {
				$groups = semrs_blocks_shop_section_html( '', $cid . '-catbar~amazon' ) . $groups;
			}
			return semrs_blocks_shop_section( $groups, $style );

		case 'catbar':
			$links = array();
			foreach ( $d['categories'] as $slug => $label ) {
				if ( ! empty( $by[ $slug ] ) ) {
					$links[] = array( $label, '#' . $slug );
				}
			}
			return semrs_blocks_shop_list( $links, 'semrs-sf-catbar' );

		case 'promises':
			$out = '<!-- wp:semrs/container {"layout":"grid","columns":4,"columnsTablet":2,"gap":16} -->';
			foreach ( (array) ( $site['promises'] ?? array() ) as $i ) {
				$out .= '<!-- wp:semrs/service-card ' . wp_json_encode( array( 'icon' => $i[0], 'title' => $i[1], 'text' => $i[2], 'style' => 'plain' ), JSON_UNESCAPED_UNICODE ) . ' /-->';
			}
			return semrs_blocks_shop_section( $out . '<!-- /wp:semrs/container -->', $style, array( 'bg' => '#f6f7f9' ) );

		case 'faq':
			$acc = '';
			foreach ( (array) ( $site['faq'] ?? array() ) as $q => $a ) {
				$acc .= semrs_blocks_shop_acc( $q, semrs_blocks_shop_p( $a ) );
			}
			return semrs_blocks_shop_section( ( 'amazon' === $style ? semrs_blocks_shop_head( 'Questions & answers' ) : '' ) . '<!-- wp:semrs/accordion {"faqSchema":true} -->' . $acc . '<!-- /wp:semrs/accordion -->', $style );

		case 'about':
			return semrs_blocks_shop_section(
				'<!-- wp:columns {"verticalAlignment":"center"} --><div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center">'
				. semrs_blocks_shop_head( 'The story of {{name}}' )
				. semrs_blocks_shop_p( 'Tell customers who you are, how you started and what you love making. Real stories build trust: write in your own words.' )
				. semrs_blocks_shop_head( 'How we work', '', '', 3 )
				. semrs_blocks_shop_list( array( 'Every design is original and tested before it is listed.', 'Files are delivered instantly, with clear instructions.', 'Questions get a friendly, personal reply.' ) )
				. '</div><!-- /wp:column --><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center">' . $img_tag . '</div><!-- /wp:column --></div><!-- /wp:columns -->',
				$style
			);

		case 'policies':
			$items = array(
				'Digital delivery' => 'Downloads are available right after payment, by email and on the order page. Write how your checkout delivers files.',
				'Custom orders'    => 'Personalised and custom items are made after purchase. Write your real turnaround time and proof process.',
				'Refunds'          => 'Write your refund policy for digital files and custom orders. Many shops offer replacements for faulty files.',
				'Licences'         => 'Each listing explains personal and commercial use. Reselling or sharing files is never allowed.',
				'Payments'         => 'List the payment methods your checkout accepts.',
				'Privacy'          => 'Explain what customer data you keep and why, and link to your privacy policy.',
			);
			$out   = '';
			foreach ( $items as $t => $text ) {
				$out .= semrs_blocks_shop_head( $t, '', '', 3 ) . semrs_blocks_shop_p( $text );
			}
			return semrs_blocks_shop_section( $out, $style );

		case 'help':
			$h = $site['help'] ?? array();
			if ( ! $h ) {
				return '';
			}
			$levels = '';
			foreach ( (array) ( $h['levels'] ?? array() ) as $l ) {
				$levels .= semrs_blocks_shop_head( $l[0], '', '', 3 ) . semrs_blocks_shop_p( $l[1] );
			}
			return semrs_blocks_shop_section(
				semrs_blocks_shop_head( 'US and UK crochet terms', 'The same stitch has a different name in US and UK patterns. Check which terms a pattern uses before you start.', 'terms' )
				. semrs_blocks_shop_table( array( 'US term', 'UK term' ), $h['terms'] ?? array() )
				. semrs_blocks_shop_head( 'Hook sizes', 'US letter and number sizes with their metric size. Always check the gauge in your pattern.', 'hooks' )
				. semrs_blocks_shop_table( array( 'US size', 'Metric' ), $h['hooks'] ?? array() )
				. semrs_blocks_shop_head( 'Skill levels', 'Every pattern in this shop shows one of these levels.', 'levels' )
				. $levels,
				$style
			);

		case 'home':
			$featured = $pick( $site['featured'] ?? array() );
			$cats     = '';
			foreach ( $d['categories'] as $slug => $label ) {
				if ( empty( $by[ $slug ] ) ) {
					continue;
				}
				$url   = isset( $site['cat_pages'][ $slug ] ) ? '/' . $slug . '/' : '/shop/#' . $slug;
				/* translators: %d: number of products */
				$cats .= '<!-- wp:semrs/service-card ' . wp_json_encode( array( 'icon' => 'tag', 'title' => $label, 'text' => sprintf( _n( '%d design', '%d designs', count( $by[ $slug ] ), 'semrs-shops' ), count( $by[ $slug ] ) ), 'link' => __( 'Browse', 'semrs-shops' ), 'url' => $url, 'style' => 'card' ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
			}
			$tiles = '<!-- wp:semrs/container {"layout":"grid","columns":4,"columnsTablet":2,"gap":16} -->' . $cats . '<!-- /wp:semrs/container -->';

			if ( 'etsy' === $style ) {
				$header = '<!-- wp:semrs/container {"align":"full","tag":"section","className":"semrs-sf semrs-sf-etsy semrs-sf-banner","gradient":"sunrise","minHeight":160,"padTop":24,"padBottom":24} --><!-- wp:paragraph --><p> </p><!-- /wp:paragraph --><!-- /wp:semrs/container -->'
					. semrs_blocks_shop_section(
						'<!-- wp:columns {"className":"semrs-sf-shophead"} --><div class="wp-block-columns semrs-sf-shophead"><!-- wp:column {"width":"140px"} --><div class="wp-block-column" style="flex-basis:140px">' . str_replace( 'is-style-rounded semrs-sf-hero-img', 'semrs-sf-avatar', $img_tag ) . '</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">'
						. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">{{name}}</h1><!-- /wp:heading -->'
						. semrs_blocks_shop_p( $site['hero_text'] ?? '' )
						. semrs_blocks_shop_p( 'Instant digital downloads · Original designs · Friendly support', 'semrs-sf-meta' )
						. semrs_blocks_shop_buttons( array( array( 'Contact shop owner', '/contact/' ), array( 'Shop policies', '/policies/' ) ) )
						. '</div><!-- /wp:column --></div><!-- /wp:columns -->'
						. '<!-- wp:group {"className":"semrs-sf-announce","layout":{"type":"default"}} --><div class="wp-block-group semrs-sf-announce">' . semrs_blocks_shop_head( 'Announcement', '', '', 3 ) . semrs_blocks_shop_p( 'Write your shop announcement here: new releases, holiday cut-off dates or a welcome note.' ) . '</div><!-- /wp:group -->',
						'etsy',
						array( 'padTop' => 8 )
					);
				$items  = semrs_blocks_shop_section( semrs_blocks_shop_sidebar( semrs_blocks_shop_head( 'Sections', '', '', 3 ) . semrs_blocks_shop_list( semrs_blocks_shop_cat_links( $d, $by ), 'semrs-sf-nav' ), semrs_blocks_shop_head( 'All items' ) . semrs_blocks_shop_grid( $cid, $prods, 'etsy', 3 ) ), 'etsy' );
				return $header . $items . semrs_blocks_shop_section_html( '', $cid . '-about~etsy' ) . semrs_blocks_shop_section_html( '', $cid . '-policies~etsy' );
			}

			if ( 'ebay' === $style ) {
				$header = semrs_blocks_shop_section(
					'<!-- wp:columns {"verticalAlignment":"center","className":"semrs-sf-storehead"} --><div class="wp-block-columns are-vertically-aligned-center semrs-sf-storehead"><!-- wp:column {"width":"110px"} --><div class="wp-block-column" style="flex-basis:110px">' . str_replace( 'is-style-rounded semrs-sf-hero-img', 'semrs-sf-avatar', $img_tag ) . '</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">'
					. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">{{name}}</h1><!-- /wp:heading -->'
					. semrs_blocks_shop_p( $site['hero_text'] ?? '' )
					. '</div><!-- /wp:column --><!-- wp:column {"width":"220px"} --><div class="wp-block-column" style="flex-basis:220px">' . semrs_blocks_shop_buttons( array( array( 'Contact', '/contact/' ), array( 'Store policies', '/policies/' ) ) ) . '</div><!-- /wp:column --></div><!-- /wp:columns -->',
					'ebay',
					array( 'bg' => '#f7f7f7', 'padTop' => 28, 'padBottom' => 28 )
				);
				$items  = semrs_blocks_shop_section( semrs_blocks_shop_sidebar( semrs_blocks_shop_head( 'Store categories', '', '', 3 ) . semrs_blocks_shop_list( semrs_blocks_shop_cat_links( $d, $by ), 'semrs-sf-nav' ), semrs_blocks_shop_head( 'Featured items' ) . semrs_blocks_shop_grid( $cid, $featured, 'ebay' ) . semrs_blocks_shop_buttons( array( array( 'See all items', '/shop/' ) ) ) ), 'ebay' );
				return $header . $items . semrs_blocks_shop_section( semrs_blocks_shop_head( 'About this store' ) . semrs_blocks_shop_p( 'Tell buyers who you are and what you sell. Digital items are delivered by email after payment.' ), 'ebay', array( 'bg' => '#f7f7f7' ) );
			}

			if ( 'amazon' === $style ) {
				$hero = '<!-- wp:semrs/container {"align":"full","tag":"section","className":"semrs-sf semrs-sf-amazon semrs-sf-brandhero","layout":"row","gap":40,"padTop":56,"padBottom":56,"gradient":"soft","valign":"center"} -->'
					. '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html( $site['hero_title'] ?? '' ) . '</h1><!-- /wp:heading -->' . semrs_blocks_shop_p( $site['hero_text'] ?? '' ) . semrs_blocks_shop_buttons( array( array( 'Shop all', '/shop/' ) ) ) . '</div><!-- /wp:group -->'
					. $img_tag . '<!-- /wp:semrs/container -->';
				$rows = semrs_blocks_shop_section_html( '', $cid . '-catbar~amazon' );
				foreach ( $d['categories'] as $slug => $label ) {
					if ( ! empty( $by[ $slug ] ) ) {
						$rows .= semrs_blocks_shop_head( $label, '', $slug ) . semrs_blocks_shop_grid( $cid, array_slice( $by[ $slug ], 0, 4 ), 'amazon' );
					}
				}
				$compare = array();
				foreach ( array_slice( $featured, 0, 5 ) as $f ) {
					$compare[] = array( $f['title'], '$' . $f['price'], $d['categories'][ $f['cat'] ] ?? '', $f['included'][0] ?? '' );
				}
				return $hero
					. semrs_blocks_shop_section( $rows, 'amazon' )
					. semrs_blocks_shop_section( semrs_blocks_shop_head( 'Compare popular picks' ) . semrs_blocks_shop_table( array( 'Product', 'From', 'Category', 'What you get' ), $compare ), 'amazon', array( 'bg' => '#f6f7f9' ) )
					. semrs_blocks_shop_section_html( '', $cid . '-faq~amazon' );
			}

			// Shopify style.
			$hero = '<!-- wp:semrs/container {"align":"full","tag":"section","className":"semrs-sf semrs-sf-shopify","layout":"row","gap":40,"padTop":72,"padBottom":72,"gradient":"sunrise","valign":"center"} -->'
				. '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:paragraph {"className":"semrs-eyebrow"} --><p class="semrs-eyebrow">{{name}}</p><!-- /wp:paragraph -->'
				. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html( $site['hero_title'] ?? '' ) . '</h1><!-- /wp:heading -->'
				. semrs_blocks_shop_p( $site['hero_text'] ?? '' ) . semrs_blocks_shop_buttons( (array) ( $site['buttons'] ?? array() ) ) . '</div><!-- /wp:group -->'
				. $img_tag . '<!-- /wp:semrs/container -->';
			return $hero
				. semrs_blocks_shop_section( semrs_blocks_shop_head( __( 'Shop by category', 'semrs-shops' ) ) . $tiles, 'shopify' )
				. semrs_blocks_shop_section( semrs_blocks_shop_head( $site['trending_title'] ?? '', $site['trending_text'] ?? '' ) . semrs_blocks_shop_grid( $cid, $featured, 'shopify' ), 'shopify', array( 'bg' => '#f6f7f9' ) )
				. semrs_blocks_shop_section_html( '', $cid . '-promises~shopify' );
	}
	return '';
}
add_filter( 'semrs_blocks_template_section_html', 'semrs_blocks_shop_section_html', 10, 2 );

/**
 * Image map: "{cid}-{key}" => file in images/.
 *
 * @param string $cid Catalogue ID.
 * @return array
 */
function semrs_blocks_shop_images( $cid ) {
	$out = array();
	foreach ( semrs_blocks_shop( $cid )['products'] as $p ) {
		$out[ $cid . '-' . $p['key'] ] = $cid . '-' . $p['key'] . '.jpg';
	}
	return $out;
}

/**
 * Starter sites: every catalogue in every storefront style.
 *
 * @param array $sites Sites.
 * @return array
 */
function semrs_blocks_shop_sites( $sites ) {
	foreach ( array_keys( semrs_blocks_shop_catalogs() ) as $cid ) {
		$d    = semrs_blocks_shop( $cid );
		$site = $d['site'];
		if ( empty( $site['id'] ) ) {
			continue;
		}
		foreach ( semrs_shops_styles() as $style => $info ) {
			$s     = '~' . $style;
			$pages = array(
				'home' => array( 'title' => 'Home', 'front' => true, 'sections' => array( $cid . '-home' . $s ) ),
				'shop' => array( 'title' => 'Shop', 'sections' => array( 'page-header', $cid . '-shop' . $s ), 'vars' => array( 'page_title' => $site['shop_title'] ?? 'Shop', 'page_intro' => $site['shop_intro'] ?? '' ) ),
			);
			if ( 'shopify' === $style ) {
				$pages['shop']['sections'][] = $cid . '-promises' . $s;
			}
			foreach ( (array) ( $site['cat_pages'] ?? array() ) as $slug => $c ) {
				$pages[ $slug ] = array( 'title' => $d['categories'][ $slug ] ?? $c[0], 'sections' => array( 'page-header', $cid . '-cat-' . $slug . $s ), 'vars' => array( 'page_title' => $c[0], 'page_intro' => $c[1] ) );
			}
			foreach ( $d['products'] as $p ) {
				$pages[ $p['key'] ] = array( 'title' => $p['title'], 'sections' => array( $cid . '-product-' . $p['key'] . $s ) );
			}
			if ( ! empty( $site['help_page'] ) ) {
				$hp              = $site['help_page'];
				$pages[ $hp[0] ] = array( 'title' => $hp[1], 'sections' => array( 'page-header', $cid . '-help' . $s ), 'vars' => array( 'page_title' => $hp[1], 'page_intro' => $hp[2] ) );
			}
			$pages += array(
				'about'    => array( 'title' => 'About', 'sections' => array( 'page-header', $cid . '-about' . $s ), 'vars' => array( 'page_title' => 'About {{name}}', 'page_intro' => 'Who we are and how we make every design.' ) ),
				'policies' => array( 'title' => 'Policies', 'sections' => array( 'page-header', $cid . '-policies' . $s ), 'vars' => array( 'page_title' => 'Shop policies', 'page_intro' => 'Delivery, custom orders, refunds, licences, payments and privacy.' ) ),
				'faq'      => array( 'title' => 'FAQ', 'sections' => array( 'page-header', $cid . '-faq' . $s ), 'vars' => array( 'page_title' => 'Questions & answers', 'page_intro' => 'Downloads, licences, orders and refunds.' ) ),
				'blog'     => array( 'title' => 'Blog', 'posts_page' => true ),
				'contact'  => array( 'title' => 'Contact', 'sections' => array( 'page-header', 'contact' ), 'vars' => array( 'page_title' => 'Contact {{name}}', 'page_intro' => 'Questions about an order or a custom request? Send a message.' ) ),
			);
			$menu = (array) ( $site['menu'] ?? array( 'shop', 'faq', 'contact' ) );
			if ( 'shopify' !== $style ) {
				$menu = array_values( array_unique( array_merge( array_slice( $menu, 0, 4 ), array( 'about', 'policies', 'contact' ) ) ) );
			}
			$sid           = 'shopify' === $style ? $site['id'] : $site['id'] . '-' . $style;
			$sites[ $sid ] = array(
				'image_dir'   => SEMRS_SHOPS_DIR . 'images/',
				'image_base'  => SEMRS_SHOPS_URL . 'images/',
				'preview_dir' => array( SEMRS_SHOPS_DIR . 'previews/', SEMRS_SHOPS_URL . 'previews/' ),
				'title'       => $site['title'] . ( 'shopify' === $style ? '' : ' (' . $info[0] . ')' ),
				'tier'        => 'free',
				'category'    => 'Shop',
				'description' => $info[1] . ' ' . $site['description'],
				'images'      => semrs_blocks_shop_images( $cid ),
				'menu'        => $menu,
				'copy'        => array(
					'cta_text'       => 'Shop now',
					'cta_url'        => '/shop/',
					'contact_title'  => 'Send us a message',
					'contact_text'   => 'We reply to order questions and custom requests by email.',
					'contact_points' => "Order and download help\nCustom requests\nWholesale and classroom licences",
					'form_services'  => "Order or download question\nCustom request\nPattern help\nOther",
				),
				'pages'       => $pages,
			);
		}
	}
	return $sites;
}
add_filter( 'semrs_blocks_template_sites', 'semrs_blocks_shop_sites', 5 );

/**
 * Storefront styles stylesheet on pages built with a storefront style.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$post = get_queried_object();
		// Block attributes are saved with '--' escaped, so style classes use single hyphens.
		if ( is_singular() && $post instanceof WP_Post && preg_match( '/semrs-sf-(shopify|etsy|ebay|amazon)/', $post->post_content ) ) {
			wp_enqueue_style( 'semrs-shops-storefronts', SEMRS_SHOPS_URL . 'assets/storefronts.css', array(), SEMRS_SHOPS_VERSION );
		}
	}
);

/**
 * Editor patterns for every catalogue and style.
 */
add_action(
	'init',
	function () {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_admin() && false === strpos( $uri, '/wp-json/' ) && false === strpos( $uri, 'rest_route=' ) ) {
			return;
		}
		$vars = array( 'name' => get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Your Shop' );
		foreach ( array_keys( semrs_blocks_shop_catalogs() ) as $cid ) {
			$d = semrs_blocks_shop( $cid );
			if ( empty( $d['site']['title'] ) ) {
				continue;
			}
			register_block_pattern_category( 'semrs-shop-' . $cid, array( 'label' => 'SEMRS ' . $d['site']['title'] ) );
			$images = array();
			foreach ( semrs_blocks_shop_images( $cid ) as $k => $file ) {
				$images[ $k ] = SEMRS_SHOPS_URL . 'images/' . $file;
			}
			$add = function ( $slug, $title, $section ) use ( $cid, $images, $vars ) {
				$content = semrs_blocks_shop_section_html( '', $section );
				if ( '' === $content ) {
					return;
				}
				register_block_pattern(
					'semrs-shops/' . $cid . '-' . $slug,
					array(
						'title'      => $title,
						'categories' => array( 'semrs-shop-' . $cid ),
						'keywords'   => array( 'shop', 'store', 'product', 'digital', 'download', $cid ),
						'content'    => semrs_blocks_template_fill( $content, $vars, $images ),
					)
				);
			};
			foreach ( semrs_shops_styles() as $style => $info ) {
				/* translators: 1: shop name, 2: storefront style */
				$add( 'home-' . $style, sprintf( __( '%1$s: home page (%2$s)', 'semrs-shops' ), $d['site']['title'], $info[0] ), $cid . '-home~' . $style );
				/* translators: 1: shop name, 2: storefront style */
				$add( 'shop-' . $style, sprintf( __( '%1$s: all products (%2$s)', 'semrs-shops' ), $d['site']['title'], $info[0] ), $cid . '-shop~' . $style );
			}
			/* translators: %s: shop name */
			$add( 'faq', sprintf( __( '%s: FAQ', 'semrs-shops' ), $d['site']['title'] ), $cid . '-faq' );
			/* translators: %s: shop name */
			$add( 'policies', sprintf( __( '%s: shop policies', 'semrs-shops' ), $d['site']['title'] ), $cid . '-policies' );
			/* translators: %s: shop name */
			$add( 'about', sprintf( __( '%s: about page', 'semrs-shops' ), $d['site']['title'] ), $cid . '-about' );
			/* translators: %s: shop name */
			$add( 'help', sprintf( __( '%s: help page', 'semrs-shops' ), $d['site']['title'] ), $cid . '-help' );
			foreach ( $d['products'] as $p ) {
				$add( 'product-' . $p['key'], $p['title'], $cid . '-product-' . $p['key'] );
			}
		}
	},
	30
);
