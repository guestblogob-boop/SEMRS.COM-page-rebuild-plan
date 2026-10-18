<?php
/**
 * Store installer (Tools → semrs.com pages): the six SEMRS products and the
 * SEMRS Pro sales page, all created as drafts. Existing items are skipped.
 * Every claim matches the product readmes; no reviews, ratings or sales
 * numbers are added. Prices are suggestions to change before publishing.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product definitions.
 *
 * @return array slug => data.
 */
function semrs_store_definitions() {
	return array(
		'semrs-pro'       => array(
			'title'   => 'SEMRS Pro',
			'cat'     => 'pro',
			'order'   => 0,
			'excerpt' => 'One add-on that unlocks Pro in the SEMRS theme and every SEMRS plugin: Ads Pro, Speed Pro, bulk SEO, redirects, local SEO, languages, dark mode, Pro blocks, popups and more starter sites.',
			'content' => '<!-- wp:paragraph --><p>SEMRS Pro is a single add-on plugin. Install it next to the free SEMRS Core plugin, enter your license key, and the locked Pro features in SEMRS → Features, the block inserter and the theme switch on.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Who it is for</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Site owners who earn from AdSense or other ad networks, agencies that build many client sites, and businesses that need local SEO, more than one language or faster pages without adding five separate plugins.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">How the license works</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Activate your key under SEMRS → License. Each plan covers a number of live websites; local and staging sites (localhost, .local, .test, staging. and dev. addresses) do not use a slot. Move a key by deactivating it on the old site first. If a license expires, every Pro feature keeps working: the license controls one-click updates and support.</p><!-- /wp:paragraph -->',
			'meta'    => array(
				'kind'     => 'addon',
				'badge'    => 'Pro',
				'currency' => 'USD',
				'period'   => 'year',
				'tiers'    => array(
					array( 'Personal: 1 site', 1, 49, '', '' ),
					array( 'Business: 5 sites', 5, 99, '', '' ),
					array( 'Agency: unlimited sites', 0, 199, '', '' ),
				),
				'needs'    => 'Needs the free SEMRS Core plugin (Pro blocks also need SEMRS Blocks)',
				'features' => array(
					'Ads Pro: repeat in-content ads, ads between posts, category / post-age / date targeting, A/B rotation',
					'Click protection that pauses ads for visitors who click too often',
					'Polite ad-blocker message that never blocks content',
					'Speed Pro: delay analytics, pixel and chat scripts until interaction',
					'Cache preload and CDN support for the SEMRS page cache',
					'Bulk SEO editor for titles, descriptions, keywords and noindex',
					'Redirects with a 404 log and Rank Math import',
					'Multi-location local SEO with LocalBusiness schema',
					'IndexNow instant indexing and automatic image alt text',
					'Language switcher with hreflang and optional Google Translate links',
					'Dark mode switch, mega menu and automatic table of contents',
					'Slider, animated counter, 3D model viewer and scroll animations',
					'Display conditions and block-built popups',
					'7 extra starter sites, including school admissions and online courses',
				),
				'faq'      => array(
					'Do I need the free plugins? | Yes. SEMRS Pro adds to SEMRS Core (and SEMRS Blocks for Pro blocks). Both are free.',
					'What happens when my license expires? | Pro features keep working. You stop receiving one-click updates and support until you renew.',
					'Do staging sites count? | No. Local and staging addresses do not use a site slot.',
					'Can I move my key to another site? | Yes. Click Deactivate license on the old site, then activate it on the new one.',
					'Does it work with other themes? | Most modules work with any theme. Theme Pro features (dark mode, mega menu, table of contents) need the SEMRS theme.',
				),
			),
		),
		'semrs'           => array(
			'title'   => 'SEMRS Theme',
			'cat'     => 'themes',
			'order'   => 1,
			'excerpt' => 'A lightweight, SEO-first WordPress theme for businesses, agencies and blogs, with a free drag-and-drop header and footer builder.',
			'content' => '<!-- wp:paragraph --><p>SEMRS is fast by design: one stylesheet, one small script, no jQuery and no icon or web fonts by default. Everything that other themes keep for their paid versions, such as the header builder and sticky header, is free here.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Built for search and accessibility</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Semantic HTML5 with one H1 per page, landmarks, a skip link and visible focus styles, plus high-priority loading for the first featured image.</p><!-- /wp:paragraph -->',
			'meta'    => array(
				'kind'     => 'theme',
				'badge'    => 'Free',
				'features' => array(
					'Drag-and-drop header builder with three rows',
					'Sticky header with shrink and a transparent header',
					'Footer builder with up to 4 widget columns',
					'Per-page options: hide title, header or footer, choose the sidebar',
					'Card or list archives, reading progress bar and author box',
					'Related posts, back-to-top button and numbered pagination',
					'7 section patterns and Full width / Blank canvas templates',
					'Footer social icons for 9 networks',
				),
				'faq'      => array(
					'Do I need any plugins? | No. SEMRS Core (SEO, speed, ads) and SEMRS Blocks are optional and free.',
					'Does it work with the block editor? | Yes. Core block styles load only for the blocks a page uses.',
				),
			),
		),
		'semrs-core'      => array(
			'title'   => 'SEMRS Core',
			'cat'     => 'plugins',
			'order'   => 2,
			'excerpt' => 'Free SEO with a 0–100 score and site audit, a page cache and speed tools, and a policy-safe AdSense manager, for any WordPress theme.',
			'content' => '<!-- wp:paragraph --><p>SEMRS Core replaces three plugins with one lightweight one. Switch each module on or off under SEMRS → Features; a switched-off module loads no code.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Switching is safe</h2><!-- /wp:heading --><!-- wp:paragraph --><p>While another SEO plugin is active, SEMRS pauses its output, and it reads the titles, descriptions, focus keywords, canonicals and noindex settings saved by Rank Math, Yoast SEO and All in One SEO, so nothing is lost when you switch.</p><!-- /wp:paragraph -->',
			'meta'    => array(
				'kind'     => 'plugin',
				'badge'    => 'Free',
				'features' => array(
					'Titles, meta descriptions, canonical and robots settings',
					'0–100 SEO score with readability checks and internal link ideas',
					'SEO Audit: site checks plus missing, duplicate, thin and orphan pages',
					'JSON-LD schema, XML sitemap, breadcrumbs and llms.txt',
					'Page cache with automatic clearing and separate phone copies',
					'Per-block CSS, deferred scripts and prefetch on hover',
					'Database cleanup and a built-in speed check',
					'Ads manager: AdSense, Ad Manager or any network, ads.txt',
				),
				'faq'      => array(
					'Does it work with my theme? | Yes. SEMRS Core works with any theme.',
					'Can I keep my current cache plugin? | Yes. The SEMRS page cache pauses itself when another page-cache plugin is active.',
				),
			),
		),
		'semrs-blocks'    => array(
			'title'   => 'SEMRS Blocks',
			'cat'     => 'plugins',
			'order'   => 3,
			'excerpt' => 'Fast server-rendered blocks plus 16 free one-click starter sites, with header and footer presets for the SEMRS theme.',
			'content' => '<!-- wp:paragraph --><p>Every block is rendered on the server, so pages stay fast and search engines see the real content. CSS and JavaScript load only on pages that use a block.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Starter sites</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Import a complete website in one click with your business name, phone, email and city filled in: Agency, SEO Services, Digital Marketing, Content Marketing, Social Media, Guest Blogging, Local Business, Cleaning, Handyman, Repair Shop, Gardening, Pet Care, Dentist, Blog, Top 10 Lists and Travel Blog. Everything is bundled; nothing is downloaded.</p><!-- /wp:paragraph -->',
			'meta'    => array(
				'kind'     => 'plugin',
				'badge'    => 'Free',
				'features' => array(
					'Icon, icon list, fancy heading and posts blocks',
					'Pricing card, service card and countdown timer',
					'Contact form with country picker, spam protection and lead list',
					'Container, tabs, accordion / FAQ, gallery and lightbox',
					'Testimonial, table of contents and website showcase',
					'Hide any block on phones, tablets or desktops',
					'16 free starter sites with 12 sections and header / footer presets',
				),
				'faq'      => array(
					'Does it work with other themes? | Yes: block themes and classic themes such as Astra, GeneratePress and Kadence. Header and footer presets need the SEMRS theme.',
				),
			),
		),
		'semrs-tools'     => array(
			'title'   => 'SEMRS Tools',
			'cat'     => 'plugins',
			'order'   => 4,
			'excerpt' => '12 free SEO tools your visitors can use on your own site, from a SERP preview to a schema generator. Each loads only its own small script.',
			'content' => '<!-- wp:paragraph --><p>Add useful tools to your site with a shortcode and earn links and returning visitors. Tools run in the visitor\'s browser where possible, so nothing they type is stored.</p><!-- /wp:paragraph -->',
			'meta'    => array(
				'kind'     => 'plugin',
				'badge'    => 'Free',
				'features' => array(
					'SERP snippet preview and meta title & description checker',
					'Robots.txt generator with AI crawler rules',
					'Redirect checker (rate-limited, private addresses blocked)',
					'Schema markup and local business schema generators',
					'Word counter, Google review link and llms.txt generators',
					'UTM builder with bulk mode and CSV export',
					'Image compressor & WebP converter (files never upload)',
					'SEO ROI calculator',
				),
			),
		),
		'semrs-child-seo' => array(
			'title'   => 'SEMRS Child SEO',
			'cat'     => 'themes',
			'order'   => 5,
			'excerpt' => 'A brand layer for the SEMRS theme: navy and gold colours, a sticky call and WhatsApp bar on phones, and a WhatsApp button.',
			'content' => '<!-- wp:paragraph --><p>SEMRS Child SEO keeps your changes safe from theme updates and adds conversion features for service businesses. Rebrand it by editing the colour tokens at the top of its stylesheet.</p><!-- /wp:paragraph -->',
			'meta'    => array(
				'kind'     => 'theme',
				'badge'    => 'Free',
				'needs'    => 'Needs the free SEMRS theme',
				'features' => array(
					'Navy and gold brand colours (WCAG AA checked)',
					'Sticky call and WhatsApp bar on phones',
					'Floating WhatsApp button',
					'Phone and WhatsApp numbers in the Customizer',
				),
			),
		),
	);
}

/**
 * SEMRS SEO title, description and focus keyword.
 *
 * @param int    $id    Post ID.
 * @param string $title SEO title.
 * @param string $desc  Meta description.
 * @param string $focus Focus keyword.
 */
function semrs_store_set_seo( $id, $title, $desc, $focus ) {
	update_post_meta( $id, '_semrs_title', sanitize_text_field( $title ) );
	update_post_meta( $id, '_semrs_description', sanitize_text_field( wp_html_excerpt( $desc, 155, '' ) ) );
	update_post_meta( $id, '_semrs_focus', sanitize_text_field( $focus ) );
}

/**
 * Copy a bundled product image into the Media Library once.
 *
 * @param string $file File name in assets/products/.
 * @param string $alt  Alt text.
 * @return int Attachment ID or 0.
 */
function semrs_store_media( $file, $alt ) {
	$done = (array) get_option( 'semrs_store_media', array() );
	if ( ! empty( $done[ $file ] ) && get_post( $done[ $file ] ) ) {
		return (int) $done[ $file ];
	}
	$src = SEMRS_SITE_DIR . 'assets/products/' . $file;
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $file );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0 );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$done[ $file ] = $id;
	update_option( 'semrs_store_media', $done, false );
	return (int) $id;
}

/**
 * Create categories, products and the Pro sales page (drafts).
 *
 * @return array{products:int, pages:int}
 */
function semrs_store_install() {
	$cats = array(
		'themes'  => array( 'Themes', 'Fast, SEO-first WordPress themes with a free header and footer builder.' ),
		'plugins' => array( 'Plugins', 'Free SEO, speed, ads, blocks and tools plugins that work with any theme.' ),
		'pro'     => array( 'Pro', 'SEMRS Pro: one add-on that unlocks Pro across the theme and every plugin.' ),
	);
	foreach ( $cats as $slug => $c ) {
		if ( ! term_exists( $slug, 'semrs_product_cat' ) ) {
			wp_insert_term( $c[0], 'semrs_product_cat', array( 'slug' => $slug, 'description' => $c[1] ) );
		}
	}
	$made = array(
		'products' => 0,
		'pages'    => 0,
	);
	foreach ( semrs_store_definitions() as $slug => $p ) {
		if ( get_page_by_path( $slug, OBJECT, 'semrs_product' ) ) {
			continue;
		}
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'semrs_product',
					'post_status'  => 'draft',
					'post_title'   => $p['title'],
					'post_name'    => $slug,
					'post_excerpt' => $p['excerpt'],
					'post_content' => $p['content'],
					'menu_order'   => $p['order'],
				)
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $p['cat'], 'semrs_product_cat' );
		$desk = semrs_store_media( $slug . '-desktop.jpg', $p['title'] . ' on a computer' );
		$phone = semrs_store_media( $slug . '-mobile.jpg', $p['title'] . ' on a phone' );
		if ( $desk ) {
			set_post_thumbnail( $id, $desk );
		}
		$meta            = $p['meta'];
		$meta['slug']    = $slug;
		$meta['gallery'] = array_filter( array( $desk, $phone ) );
		update_post_meta( $id, '_semrs_product', semrs_store_sanitize( $meta ) );
		semrs_store_set_seo( $id, $p['title'] . ( 'pro' === $p['cat'] ? ' – Pro Add-on for WordPress' : ' – Free WordPress ' . ( 'themes' === $p['cat'] ? 'Theme' : 'Plugin' ) ), $p['excerpt'], mb_strtolower( $p['title'] ) );
		$made['products']++;
	}
	if ( ! get_page_by_path( 'semrs-pro' ) ) {
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => 'SEMRS Pro',
					'post_name'    => 'semrs-pro',
					'post_content' => semrs_store_pro_page( semrs_store_media( 'semrs-pro-desktop.jpg', 'SEMRS Pro on a computer' ) ),
				)
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'semrs_hide_title', 1 );
			update_post_meta( $id, '_wp_page_template', 'page-templates/full-width.php' );
			update_post_meta( $id, '_semrs_ads_off', true ); // Sales page: no ads.
			semrs_store_set_seo( $id, 'SEMRS Pro: Ads, Speed & SEO Pro Add-on for WordPress', 'Unlock Ads Pro with click protection, Speed Pro, bulk SEO, redirects, local SEO, languages, dark mode and Pro blocks in one WordPress add-on.', 'semrs pro' );
			$made['pages']++;
		}
	}
	flush_rewrite_rules();
	return $made;
}

/**
 * SEMRS Pro sales page content (core blocks + SEMRS shortcodes).
 *
 * @param int $img Hero image attachment ID.
 * @return string
 */
function semrs_store_pro_page( $img ) {
	$src  = $img ? wp_get_attachment_image_url( $img, 'large' ) : '';
	$hero = $src ? '<!-- wp:image {"id":' . (int) $img . ',"sizeSlug":"large","className":"is-style-rounded-shadow"} --><figure class="wp-block-image size-large is-style-rounded-shadow"><img src="' . esc_url( $src ) . '" alt="SEMRS Pro on a computer" class="wp-image-' . (int) $img . '"/></figure><!-- /wp:image -->' : '';
	$card = function ( $title, $items ) {
		$li = '';
		foreach ( $items as $i ) {
			$li .= '<!-- wp:list-item --><li>' . esc_html( $i ) . '</li><!-- /wp:list-item -->';
		}
		return '<!-- wp:group {"className":"semrs-pro-card","style":{"border":{"radius":"14px","width":"1px","color":"#e3e7ee"},"spacing":{"padding":{"top":"22px","bottom":"22px","left":"22px","right":"22px"}}}} --><div class="wp-block-group semrs-pro-card has-border-color" style="border-color:#e3e7ee;border-width:1px;border-radius:14px;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html( $title ) . '</h3><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list">' . $li . '</ul><!-- /wp:list --></div><!-- /wp:group -->';
	};
	$rows = array(
		array( 'SEO: titles, schema, sitemaps, SEO score, audit, llms.txt', 'Yes', 'Yes' ),
		array( 'Bulk SEO editor, redirects + 404 log, local SEO, IndexNow, image alt text', '–', 'Yes' ),
		array( 'Page cache, CSS and script optimisation, database cleanup', 'Yes', 'Yes' ),
		array( 'Delay JavaScript until interaction, cache preload, CDN', '–', 'Yes' ),
		array( 'Ads manager with policy-safe placement and ads.txt', 'Yes', 'Yes' ),
		array( 'Repeat and between-posts ads, targeting, A/B rotation, click protection', '–', 'Yes' ),
		array( 'Header and footer builder, sticky header, patterns', 'Yes', 'Yes' ),
		array( 'Dark mode switch, mega menu, table of contents, white label', '–', 'Yes' ),
		array( 'Blocks: icons, posts, pricing, contact form, tabs, accordion, gallery…', 'Yes', 'Yes' ),
		array( 'Slider, counter, 3D viewer, scroll animations, display conditions, popups', '–', 'Yes' ),
		array( 'Language switcher with hreflang', '–', 'Yes' ),
		array( 'Starter sites', '16', '23' ),
	);
	$tr   = '';
	foreach ( $rows as $r ) {
		$tr .= '<tr><td>' . esc_html( $r[0] ) . '</td><td>' . esc_html( $r[1] ) . '</td><td><strong>' . esc_html( $r[2] ) . '</strong></td></tr>';
	}
	$faq = '';
	foreach ( semrs_store_definitions()['semrs-pro']['meta']['faq'] as $q ) {
		$qa   = array_map( 'trim', explode( '|', $q, 2 ) );
		$faq .= '<!-- wp:details --><details class="wp-block-details"><summary>' . esc_html( $qa[0] ) . '</summary><!-- wp:paragraph --><p>' . esc_html( $qa[1] ) . '</p><!-- /wp:paragraph --></details><!-- /wp:details -->';
	}
	$store = get_post_type_archive_link( 'semrs_product' );

	return '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"64px","bottom":"64px","left":"16px","right":"16px"}},"color":{"background":"#0b1f3a","text":"#ffffff"}}} --><div class="wp-block-group alignfull has-text-color has-background" style="color:#ffffff;background-color:#0b1f3a;padding-top:64px;padding-right:16px;padding-bottom:64px;padding-left:16px"><!-- wp:columns {"align":"wide","verticalAlignment":"center"} --><div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"style":{"color":{"text":"#d4a72c"},"typography":{"fontWeight":"700","textTransform":"uppercase","letterSpacing":"0.08em"}}} --><p class="has-text-color" style="color:#d4a72c;font-weight:700;letter-spacing:0.08em;text-transform:uppercase">SEMRS Pro</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"color":{"text":"#ffffff"}}} --><h1 class="wp-block-heading has-text-color" style="color:#ffffff">More ad revenue, faster pages and easier SEO from one add-on</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#e8eef7"}},"fontSize":"medium"} --><p class="has-text-color has-medium-font-size" style="color:#e8eef7">SEMRS Pro unlocks Pro features in the free SEMRS theme, SEMRS Core and SEMRS Blocks: Ads Pro with click protection, Speed Pro, bulk SEO, redirects, local SEO, languages, dark mode, Pro blocks and 7 more starter sites.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#d4a72c","text":"#0b1f3a"}}} --><div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="#pricing" style="color:#0b1f3a;background-color:#d4a72c">See plans and prices</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline","style":{"color":{"text":"#ffffff"}}} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color wp-element-button" href="#compare" style="color:#ffffff">Compare free and Pro</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column --><!-- wp:column {"verticalAlignment":"center"} --><div class="wp-block-column is-vertically-aligned-center">' . $hero . '</div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->'
		. '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"56px","bottom":"24px"}}}} --><div class="wp-block-group alignwide" style="padding-top:56px;padding-bottom:24px"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Everything Pro adds</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">One plugin, one license key, switched on module by module under SEMRS → Features.</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column">'
		. $card( 'Ads Pro', array( 'Repeat ads every few paragraphs, within your density cap', 'Ads between posts on blog and category pages', 'Targeting by category, post age and dates', 'A/B rotation of two ad codes', 'Click protection and a polite ad-blocker message' ) )
		. '</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">'
		. $card( 'Speed Pro', array( 'Delay analytics, pixel and chat scripts until interaction', 'Cache preload after every clear', 'CDN address for images, CSS, JS and fonts' ) )
		. $card( 'SEO Pro', array( 'Bulk SEO editor', 'Redirects and 404 log', 'Multi-location local SEO', 'IndexNow and automatic image alt text' ) )
		. '</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">'
		. $card( 'Design & growth', array( 'Dark mode switch, mega menu, table of contents', 'Slider, counter, 3D viewer, scroll animations', 'Display conditions and popups', 'Language switcher with hreflang', '7 more starter sites' ) )
		. '</div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->'
		. '<!-- wp:group {"align":"wide","anchor":"compare","style":{"spacing":{"padding":{"top":"40px","bottom":"24px"}}}} --><div id="compare" class="wp-block-group alignwide" style="padding-top:40px;padding-bottom:24px"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Free and Pro compared</h2><!-- /wp:heading --><!-- wp:table {"className":"is-style-stripes"} --><figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Feature</th><th>Free</th><th>Pro</th></tr></thead><tbody>' . $tr . '</tbody></table></figure><!-- /wp:table --></div><!-- /wp:group -->'
		. '<!-- wp:group {"align":"wide","anchor":"pricing","style":{"spacing":{"padding":{"top":"40px","bottom":"24px"}}}} --><div id="pricing" class="wp-block-group alignwide" style="padding-top:40px;padding-bottom:24px"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Plans and prices</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Every plan includes every Pro feature. Choose by the number of live websites.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[semrs_pricing product="semrs-pro"]<!-- /wp:shortcode --></div><!-- /wp:group -->'
		. '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"40px","bottom":"24px"}}}} --><div class="wp-block-group alignwide" style="padding-top:40px;padding-bottom:24px"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">How it works</h2><!-- /wp:heading --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">1. Choose a plan</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Pick the number of websites and complete checkout.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">2. Install SEMRS Pro</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Plugins → Add New Plugin → Upload Plugin, then activate it next to the free SEMRS Core.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">3. Activate your key</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Paste it under SEMRS → License. Pro features unlock at once and updates arrive in your dashboard.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->'
		. '<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"24px"}}}} --><div class="wp-block-group" style="padding-top:40px;padding-bottom:24px"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Questions</h2><!-- /wp:heading -->' . $faq . '</div><!-- /wp:group -->'
		. '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"16px","right":"16px"}},"color":{"background":"#f6f7f9"}}} --><div class="wp-block-group alignfull has-background" style="background-color:#f6f7f9;padding-top:48px;padding-right:16px;padding-bottom:48px;padding-left:16px"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Start with the free products</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">The theme, SEMRS Core, SEMRS Blocks and SEMRS Tools are free forever. Add Pro when you need it.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $store ) . '">Browse the store</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->';
}
