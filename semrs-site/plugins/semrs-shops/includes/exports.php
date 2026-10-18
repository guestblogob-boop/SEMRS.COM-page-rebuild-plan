<?php
/**
 * Tools → Shop exports: download the shop catalogues as marketplace files.
 *
 * - Shopify product CSV (Shopify's product import format): one row per
 *   variant (up to three options), digital settings (no shipping, no stock
 *   tracking), SEO fields, tags, image URL, draft status.
 * - Etsy listing sheet (CSV for your own records or a listing tool): title,
 *   description, price, 13 tags of up to 20 characters, section, type,
 *   variations and personalisation instructions.
 *
 * eBay and Amazon files are not offered: both marketplaces restrict
 * downloadable products such as printables and patterns.
 *
 * Images: the image URLs point to this site's copy of SEMRS Shops, so they
 * work when the site is public. Upload the files from images/ otherwise.
 *
 * @package semrs-shops
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variants for a product: list of [option values[], price, compare].
 *
 * @param array $p Product.
 * @return array{names: string[], rows: array, text: array}
 */
function semrs_shops_variants( $p ) {
	$groups = function_exists( 'semrs_blocks_product_groups' ) ? semrs_blocks_product_groups( $p['options'] ?? '' ) : array();
	$text   = array_values( array_filter( $groups, function ( $g ) { return 'text' === $g['type']; } ) );
	$opts   = array_slice( array_values( array_filter( $groups, function ( $g ) { return 'text' !== $g['type']; } ) ), 0, 3 );
	$combos = array( array() );
	foreach ( $opts as $g ) {
		$next = array();
		foreach ( $combos as $c ) {
			foreach ( $g['values'] as $v ) {
				if ( empty( $v['soldout'] ) ) {
					$next[] = array_merge( $c, array( $v ) );
				}
			}
		}
		$combos = $next;
	}
	$rows = array();
	foreach ( array_slice( $combos, 0, 100 ) as $c ) {
		$price = (float) $p['price'];
		foreach ( $c as $v ) {
			if ( null !== $v['price'] ) {
				$price = (float) $v['price'];
			}
		}
		foreach ( $c as $v ) {
			$price += (float) $v['add'];
		}
		$rows[] = array( wp_list_pluck( $c, 'label' ), round( $price, 2 ), (float) ( $p['compare'] ?? 0 ) );
	}
	return array(
		'names' => wp_list_pluck( $opts, 'label' ),
		'rows'  => $rows,
		'text'  => $text,
	);
}

/**
 * Plain-text description.
 *
 * @param array  $p    Product.
 * @param array  $text Personalisation groups.
 * @param string $nl   Line break.
 * @return string
 */
function semrs_shops_description( $p, $text, $nl = "\n" ) {
	$out = $p['text'] . $nl . $nl . 'WHAT YOU GET' . $nl;
	foreach ( $p['included'] as $i ) {
		$out .= '• ' . $i . $nl;
	}
	if ( $text ) {
		$out .= $nl . 'PERSONALISATION' . $nl;
		foreach ( $text as $t ) {
			$out .= '• ' . $t['label'] . ( $t['required'] ? ' (required)' : '' ) . ', up to ' . $t['max'] . ' characters' . $nl;
		}
	}
	$out .= $nl . 'LICENCE' . $nl . $p['license'] . $nl . $nl . 'This is a digital product. No physical item will be shipped.';
	return $out;
}

/**
 * Up to 13 Etsy tags (20 characters max each).
 *
 * @param array  $p   Product.
 * @param string $cat Category label.
 * @param string $cid Catalogue ID.
 * @return string[]
 */
function semrs_shops_tags( $p, $cat, $cid ) {
	$stop  = array( 'and', 'the', 'for', 'with', 'pattern', 'patterns', 'pdf', 'png', 'svg', '–', '&', 'set', 'all', 'sizes', 'designs', 'design' );
	$words = array_values( array_filter( preg_split( '/[^a-z0-9]+/', strtolower( remove_accents( $p['title'] ) ) ), function ( $w ) use ( $stop ) { return strlen( $w ) > 2 && ! in_array( $w, $stop, true ); } ) );
	$base  = 'crochet' === $cid
		? array( 'crochet pattern', 'pdf pattern', 'crochet pdf', 'easy crochet', 'diy crochet', 'crochet gift idea', 'handmade gift', 'crochet tutorial', 'yarn project', 'beginner crochet' )
		: array( 'digital download', 'instant download', 'printable', 'digital file', 'gift idea', 'diy gift', 'print at home', 'digital design', 'download and print', 'last minute gift' );
	$tags  = array();
	if ( count( $words ) >= 2 ) {
		$tags[] = $words[0] . ' ' . $words[1];
	}
	if ( count( $words ) >= 3 ) {
		$tags[] = $words[1] . ' ' . $words[2];
	}
	$tags[] = str_replace( ' and ', ' ', strtolower( $cat ) );
	foreach ( $words as $w ) {
		$tags[] = ( 'crochet' === $cid && 'crochet' !== $w ? $w . ' crochet' : $w );
	}
	if ( ! empty( $p['made'] ) ) {
		$tags[] = 'personalized gift';
		$tags[] = 'custom gift';
	}
	$tags = array_merge( $tags, $base );
	$out  = array();
	foreach ( $tags as $t ) {
		$t = trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9 ]/', ' ', strtolower( $t ) ) ) );
		if ( '' !== $t && strlen( $t ) <= 20 && ! in_array( $t, $out, true ) ) {
			$out[] = $t;
		}
		if ( count( $out ) >= 13 ) {
			break;
		}
	}
	return $out;
}

/**
 * Stream a CSV and stop.
 *
 * @param string $name File name.
 * @param array  $head Header row.
 * @param array  $rows Rows.
 */
function semrs_shops_csv( $name, $head, $rows ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ) . '"' );
	$fh = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $fh, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- UTF-8 BOM for spreadsheets.
	fputcsv( $fh, $head );
	foreach ( $rows as $r ) {
		fputcsv( $fh, $r );
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

/**
 * Shopify CSV rows for a catalogue.
 *
 * @param string $cid Catalogue ID.
 * @return array{0: array, 1: array}
 */
function semrs_shops_shopify_rows( $cid ) {
	$d    = semrs_blocks_shop( $cid );
	$head = array( 'Handle', 'Title', 'Body (HTML)', 'Vendor', 'Type', 'Tags', 'Published', 'Option1 Name', 'Option1 Value', 'Option2 Name', 'Option2 Value', 'Option3 Name', 'Option3 Value', 'Variant SKU', 'Variant Grams', 'Variant Inventory Policy', 'Variant Fulfillment Service', 'Variant Price', 'Variant Compare At Price', 'Variant Requires Shipping', 'Variant Taxable', 'Image Src', 'Image Position', 'Image Alt Text', 'SEO Title', 'SEO Description', 'Status' );
	$rows = array();
	$vend = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'My Shop';
	foreach ( $d['products'] as $p ) {
		$v    = semrs_shops_variants( $p );
		$cat  = $d['categories'][ $p['cat'] ] ?? '';
		$body = wpautop( esc_html( semrs_shops_description( $p, $v['text'] ) ) );
		foreach ( $v['rows'] as $i => $r ) {
			$first  = 0 === $i;
			$sku    = strtoupper( substr( $cid, 0, 3 ) ) . '-' . strtoupper( substr( preg_replace( '/[^a-z0-9]/', '', $p['key'] ), 0, 14 ) ) . '-' . ( $i + 1 );
			$names  = $v['names'] ? $v['names'] : array( 'Title' );
			$vals   = $v['names'] ? $r[0] : array( 'Default Title' );
			$rows[] = array(
				$p['key'],
				$first ? $p['title'] : '',
				$first ? $body : '',
				$first ? $vend : '',
				$first ? $cat : '',
				$first ? implode( ', ', array_merge( array( $cat, 'crochet' === $cid ? 'crochet pattern' : 'digital download' ), semrs_shops_tags( $p, $cat, $cid ) ) ) : '',
				$first ? 'FALSE' : '',
				$first ? ( $names[0] ?? '' ) : '',
				$vals[0] ?? '',
				$first ? ( $names[1] ?? '' ) : '',
				$vals[1] ?? '',
				$first ? ( $names[2] ?? '' ) : '',
				$vals[2] ?? '',
				$sku,
				'0',
				'continue',
				'manual',
				number_format( $r[1], 2, '.', '' ),
				$r[2] > $r[1] ? number_format( $r[2], 2, '.', '' ) : '',
				'FALSE',
				'TRUE',
				$first ? SEMRS_SHOPS_URL . 'images/' . $cid . '-' . $p['key'] . '.jpg' : '',
				$first ? '1' : '',
				$first ? $p['title'] : '',
				$first ? wp_html_excerpt( $p['title'], 70, '' ) : '',
				$first ? wp_html_excerpt( $p['text'], 160, '' ) : '',
				$first ? 'draft' : '',
			);
		}
	}
	return array( $head, $rows );
}

/**
 * Etsy listing sheet rows for a catalogue.
 *
 * @param string $cid Catalogue ID.
 * @return array{0: array, 1: array}
 */
function semrs_shops_etsy_rows( $cid ) {
	$d    = semrs_blocks_shop( $cid );
	$head = array( 'Title', 'Description', 'Price (USD)', 'Quantity', 'Tags', 'Section', 'Listing type', 'Who made it', 'What is it', 'When was it made', 'Variations', 'Personalisation', 'Personalisation instructions', 'Image file', 'Licence' );
	$rows = array();
	foreach ( $d['products'] as $p ) {
		$v     = semrs_shops_variants( $p );
		$cat   = $d['categories'][ $p['cat'] ] ?? '';
		$vars  = array();
		foreach ( array_slice( $v['rows'], 0, 40 ) as $r ) {
			$vars[] = implode( ' / ', $r[0] ) . ' = $' . number_format( $r[1], 2 );
		}
		$instr = array();
		foreach ( $v['text'] as $t ) {
			$instr[] = $t['label'] . ( $t['required'] ? ' (required)' : '' ) . ', max ' . $t['max'] . ' characters';
		}
		$rows[] = array(
			mb_substr( $p['title'], 0, 140 ),
			semrs_shops_description( $p, $v['text'] ),
			number_format( (float) $p['price'], 2, '.', '' ),
			'999',
			implode( ', ', semrs_shops_tags( $p, $cat, $cid ) ),
			$cat,
			'Digital',
			'I did',
			'crochet' === $cid ? 'A supply or tool to make things' : 'A finished product',
			! empty( $p['made'] ) ? 'Made to order' : '2020 – 2026',
			implode( ' | ', $vars ),
			$v['text'] ? 'Yes' : 'No',
			implode( '; ', $instr ),
			$cid . '-' . $p['key'] . '.jpg',
			$p['license'],
		);
	}
	return array( $head, $rows );
}

add_action(
	'admin_menu',
	function () {
		add_management_page( __( 'Shop exports', 'semrs-shops' ), __( 'Shop exports', 'semrs-shops' ), 'manage_options', 'semrs-shops-exports', 'semrs_shops_exports_page' );
	}
);

add_action(
	'admin_post_semrs_shops_export',
	function () {
		check_admin_referer( 'semrs_shops_export' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'semrs-shops' ) );
		}
		$cid    = isset( $_GET['catalogue'] ) ? sanitize_key( wp_unslash( $_GET['catalogue'] ) ) : '';
		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : '';
		if ( ! isset( semrs_blocks_shop_catalogs()[ $cid ] ) ) {
			wp_die( esc_html__( 'Unknown catalogue.', 'semrs-shops' ) );
		}
		if ( 'shopify' === $format ) {
			list( $head, $rows ) = semrs_shops_shopify_rows( $cid );
			semrs_shops_csv( 'shopify-products-' . $cid . '.csv', $head, $rows );
		}
		if ( 'etsy' === $format ) {
			list( $head, $rows ) = semrs_shops_etsy_rows( $cid );
			semrs_shops_csv( 'etsy-listings-' . $cid . '.csv', $head, $rows );
		}
		wp_die( esc_html__( 'Unknown format.', 'semrs-shops' ) );
	}
);

/**
 * Exports screen.
 */
function semrs_shops_exports_page() {
	echo '<div class="wrap"><h1>' . esc_html__( 'Shop exports', 'semrs-shops' ) . '</h1>';
	echo '<p>' . esc_html__( 'Download the SEMRS Shops catalogues as marketplace files. Prices are 2026 marketplace estimates: check them against current listings before you publish.', 'semrs-shops' ) . '</p>';
	echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>' . esc_html__( 'Catalogue', 'semrs-shops' ) . '</th><th>' . esc_html__( 'Products', 'semrs-shops' ) . '</th><th>Shopify</th><th>Etsy</th></tr></thead><tbody>';
	foreach ( array_keys( semrs_blocks_shop_catalogs() ) as $cid ) {
		$d    = semrs_blocks_shop( $cid );
		$link = function ( $format ) use ( $cid ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=semrs_shops_export&catalogue=' . $cid . '&format=' . $format ), 'semrs_shops_export' );
		};
		echo '<tr><td><strong>' . esc_html( $d['site']['title'] ?? $cid ) . '</strong></td><td>' . count( $d['products'] ) . '</td><td><a class="button" href="' . esc_url( $link( 'shopify' ) ) . '">' . esc_html__( 'Product CSV', 'semrs-shops' ) . '</a></td><td><a class="button" href="' . esc_url( $link( 'etsy' ) ) . '">' . esc_html__( 'Listing sheet', 'semrs-shops' ) . '</a></td></tr>';
	}
	echo '</tbody></table>';
	echo '<h2>' . esc_html__( 'How to use the files', 'semrs-shops' ) . '</h2><ol>';
	echo '<li>' . esc_html__( 'Shopify: Products → Import → choose the CSV. Products arrive as drafts with "requires shipping" off. Attach each digital file with a digital-downloads app, and add a personalisation app if a product asks for names.', 'semrs-shops' ) . '</li>';
	echo '<li>' . esc_html__( 'Etsy: Etsy has no CSV upload of its own. Use the sheet to create listings in Shop Manager, or import it with a listing tool you trust. Set each listing to "Digital" and upload the file.', 'semrs-shops' ) . '</li>';
	echo '<li>' . esc_html__( 'Image URLs point to this site. If it is not public yet, upload the images from the plugin\'s images folder instead.', 'semrs-shops' ) . '</li>';
	echo '</ol><p class="description">' . esc_html__( 'eBay and Amazon files are not offered: both marketplaces restrict downloadable products such as printables and patterns.', 'semrs-shops' ) . '</p></div>';
}
