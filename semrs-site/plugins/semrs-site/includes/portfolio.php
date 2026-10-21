<?php
/**
 * Portfolio hub (portfolio.semrs.com): every SEMRS website design with a
 * full-page desktop and phone preview, a live-demo link once its demo
 * subdomain is ready, and a "Get this website" button.
 *
 * Shortcode: [semrs_portfolio]. Designs come from SEMRS Blocks (and SEMRS Pro /
 * SEMRS Shops when active). Settings: Tools → semrs.com pages → Portfolio.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo subdomain name for each design (portfolio plan, 2026-10-21).
 *
 * @return array design id => subdomain label
 */
function semrs_portfolio_subdomains() {
	return array(
		'agency'            => 'agency',
		'local'             => 'local',
		'blog'              => 'blog',
		'seo-services'      => 'seo',
		'digital-marketing' => 'marketing',
		'content-marketing' => 'content',
		'social-media'      => 'social',
		'guest-posting'     => 'outreach',
		'cleaning'          => 'cleaning',
		'maintenance'       => 'handyman',
		'repairs'           => 'repair',
		'gardening'         => 'garden',
		'pets'              => 'pets',
		'dentist'           => 'dental',
		'top-ten'           => 'top10',
		'travel-blog'       => 'travel-blog',
		'consultant'        => 'consultant',
		'clinic'            => 'clinic',
		'travel-agency'     => 'travel-agency',
		'camping'           => 'camping',
		'school'            => 'school',
		'online-courses'    => 'courses',
		'ai-llm'            => 'ai',
	);
}

/**
 * Settings with defaults.
 *
 * @return array pattern ({sub} = subdomain label), contact URL, live designs.
 */
function semrs_portfolio_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_portfolio', array() ),
		array(
			'pattern' => 'https://{sub}.demo.semrs.com/',
			'contact' => 'https://semrs.com/contact/',
			'live'    => array(),
		)
	);
}

/**
 * Designs with their previews, newest data from the templates library.
 *
 * @return array id => title, category, description, tier, desktop, mobile
 */
function semrs_portfolio_designs() {
	if ( ! function_exists( 'semrs_blocks_template_sites' ) ) {
		return array();
	}
	$out   = array();
	$sites = semrs_blocks_template_sites();
	foreach ( $sites as $id => $site ) {
		$out[ $id ] = array( $site['title'], $site['category'] ?? '', $site['description'] ?? '', $site['tier'] ?? 'free', $site );
	}
	foreach ( semrs_blocks_template_pro_teasers() as $id => $t ) {
		if ( ! isset( $out[ $id ] ) ) {
			$out[ $id ] = array( $t[0], $t[1], $t[2], 'pro', array() );
		}
	}
	$designs = array();
	foreach ( $out as $id => $d ) {
		$desk = semrs_blocks_template_preview( $id, 'desktop', $d[4] );
		if ( ! $desk ) {
			continue;
		}
		$designs[ $id ] = array(
			'title'       => $d[0],
			'category'    => $d[1],
			'description' => $d[2],
			'tier'        => $d[3],
			'desktop'     => $desk,
			'mobile'      => semrs_blocks_template_preview( $id, 'mobile', $d[4] ),
		);
	}
	return $designs;
}

/**
 * Live demo address of a design, or '' while its demo is not ready.
 *
 * @param string $id Design id.
 * @return string
 */
function semrs_portfolio_demo_url( $id ) {
	$s    = semrs_portfolio_settings();
	$subs = semrs_portfolio_subdomains();
	if ( empty( $subs[ $id ] ) || ! in_array( $id, (array) $s['live'], true ) ) {
		return '';
	}
	return str_replace( '{sub}', $subs[ $id ], $s['pattern'] );
}

/**
 * [semrs_portfolio]: filter buttons and the design grid.
 *
 * @return string
 */
function semrs_portfolio_shortcode() {
	$designs = semrs_portfolio_designs();
	if ( ! $designs ) {
		return current_user_can( 'edit_posts' ) ? '<p>' . esc_html__( 'Activate SEMRS Blocks to show the designs.', 'semrs-site' ) . '</p>' : '';
	}
	$s    = semrs_portfolio_settings();
	$cats = array_unique( array_filter( wp_list_pluck( $designs, 'category' ) ) );
	sort( $cats );
	wp_enqueue_script( 'semrs-portfolio', SEMRS_SITE_URL . 'assets/portfolio.js', array(), SEMRS_SITE_VERSION, true );

	$out  = '<div class="semrs-pf" id="designs">';
	$out .= '<div class="semrs-pf__filters" role="group" aria-label="' . esc_attr__( 'Filter designs', 'semrs-site' ) . '"><button type="button" class="is-active" aria-pressed="true" data-cat="">' . esc_html__( 'All designs', 'semrs-site' ) . '</button>';
	foreach ( $cats as $cat ) {
		$out .= '<button type="button" aria-pressed="false" data-cat="' . esc_attr( sanitize_title( $cat ) ) . '">' . esc_html( $cat ) . '</button>';
	}
	$out .= '</div><div class="semrs-pf__grid">';
	foreach ( $designs as $id => $d ) {
		$demo  = semrs_portfolio_demo_url( $id );
		$quote = add_query_arg( 'design', $id, $s['contact'] );
		$out  .= '<article class="semrs-pf__card" data-cat="' . esc_attr( sanitize_title( $d['category'] ) ) . '">';
		$out  .= '<div class="semrs-pf__media"><a class="semrs-pf__desk" href="' . esc_url( $d['desktop'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( /* translators: %s: design name */ __( '%s: full desktop page', 'semrs-site' ), $d['title'] ) ) . '"><img src="' . esc_url( $d['desktop'] ) . '" alt="" loading="lazy" width="540" height="405"></a>';
		if ( $d['mobile'] ) {
			$out .= '<a class="semrs-pf__phone" href="' . esc_url( $d['mobile'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( /* translators: %s: design name */ __( '%s: full phone page', 'semrs-site' ), $d['title'] ) ) . '"><img src="' . esc_url( $d['mobile'] ) . '" alt="" loading="lazy" width="270" height="526"></a>';
		}
		$out .= '</div><div class="semrs-pf__body"><p class="semrs-pf__cat">' . esc_html( $d['category'] ) . '</p><h3 class="semrs-pf__title">' . esc_html( $d['title'] ) . '</h3><p class="semrs-pf__text">' . esc_html( $d['description'] ) . '</p><div class="semrs-pf__btns">';
		$out .= $demo ? '<a class="semrs-pf__btn" href="' . esc_url( $demo ) . '" target="_blank" rel="noopener">' . esc_html__( 'Live demo', 'semrs-site' ) . '</a>' : '<a class="semrs-pf__btn" href="' . esc_url( $d['desktop'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'View full design', 'semrs-site' ) . '</a>';
		$out .= '<a class="semrs-pf__btn semrs-pf__btn--ghost" href="' . esc_url( $quote ) . '">' . esc_html__( 'Get this website', 'semrs-site' ) . '</a></div></div></article>';
	}
	$out .= '</div><p class="semrs-pf__note">' . esc_html__( 'Demo websites use example business names, prices and content. Your website is built with your own brand, text and photos.', 'semrs-site' ) . '</p></div>';
	return $out;
}
add_shortcode( 'semrs_portfolio', 'semrs_portfolio_shortcode' );

/**
 * Portfolio page content: hero, design grid, process and call to action.
 *
 * @return string Block markup.
 */
function semrs_portfolio_page_content() {
	$s     = semrs_portfolio_settings();
	$count = count( semrs_portfolio_designs() );
	$cats  = count( array_unique( array_filter( wp_list_pluck( semrs_portfolio_designs(), 'category' ) ) ) );
	/* translators: 1: number of designs, 2: number of industries */
	$stat  = sprintf( __( '%1$d ready-made designs for %2$d industries, each a complete website: home, services, about, blog, contact and legal pages.', 'semrs-site' ), $count, $cats );
	$p     = function ( $text, $class = '' ) {
		return '<!-- wp:paragraph' . ( $class ? ' {"className":"' . $class . '"}' : '' ) . " -->\n<p" . ( $class ? ' class="' . $class . '"' : '' ) . '>' . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$h     = function ( $text, $level = 2, $center = false ) {
		$attrs = array();
		if ( 2 !== $level ) {
			$attrs['level'] = $level;
		}
		if ( $center ) {
			$attrs['textAlign'] = 'center';
		}
		return '<!-- wp:heading' . ( $attrs ? ' ' . wp_json_encode( $attrs ) : '' ) . " -->\n<h" . $level . ' class="wp-block-heading' . ( $center ? ' has-text-align-center' : '' ) . '">' . esc_html( $text ) . '</h' . $level . ">\n<!-- /wp:heading -->\n\n";
	};
	$btns  = function ( $items, $center = false ) {
		$out = '<!-- wp:buttons' . ( $center ? ' {"layout":{"type":"flex","justifyContent":"center"}}' : '' ) . " -->\n<div class=\"wp-block-buttons\">";
		foreach ( $items as $b ) {
			$out .= '<!-- wp:button' . ( ! empty( $b[2] ) ? ' {"className":"is-style-outline"}' : '' ) . " -->\n<div class=\"wp-block-button" . ( ! empty( $b[2] ) ? ' is-style-outline' : '' ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $b[1] ) . '">' . esc_html( $b[0] ) . "</a></div>\n<!-- /wp:button -->";
		}
		return $out . "</div>\n<!-- /wp:buttons -->\n\n";
	};
	$c = '<!-- wp:semrs/container {"align":"full","className":"semrs-pf-hero","padTop":80,"padBottom":64,"tag":"section"} -->' . "\n";
	$c .= $p( __( 'SEMRS web design portfolio', 'semrs-site' ), 'semrs-eyebrow' ) . $h( __( 'Professional websites, designed to bring you customers', 'semrs-site' ), 1 );
	$c .= $p( $stat . ' ' . __( 'Pick a design you like and we build it with your brand, or start from scratch.', 'semrs-site' ), 'semrs-lead' );
	$c .= $btns( array( array( __( 'Get a free website quote', 'semrs-site' ), $s['contact'] ), array( __( 'Browse designs', 'semrs-site' ), '#designs', true ) ) );
	$c .= "<!-- /wp:semrs/container -->\n\n";
	$c .= '<!-- wp:semrs/container {"align":"full","padTop":48,"padBottom":64,"tag":"section"} -->' . "\n" . $h( __( 'Website designs', 'semrs-site' ) ) . $p( __( 'Hover a design to scroll through the whole home page. Open any design full size, or visit its live demo.', 'semrs-site' ) ) . "<!-- wp:shortcode -->\n[semrs_portfolio]\n<!-- /wp:shortcode -->\n<!-- /wp:semrs/container -->\n\n";
	$c .= '<!-- wp:semrs/container {"align":"full","bg":"#f6f7f9","padTop":64,"padBottom":64,"tag":"section"} -->' . "\n" . $h( __( 'How we build your website', 'semrs-site' ), 2, true );
	$c .= '<!-- wp:semrs/container {"layout":"grid","columns":3,"columnsTablet":3,"width":"full","gap":32} -->' . "\n";
	foreach ( array( array( 'search', __( '1. Choose a design', 'semrs-site' ), __( 'Pick a design from the portfolio, or tell us what you have in mind.', 'semrs-site' ) ), array( 'pen', __( '2. We make it yours', 'semrs-site' ), __( 'Your logo, colours, text and photos, with SEO and speed set up from day one.', 'semrs-site' ) ), array( 'rocket', __( '3. Launch and grow', 'semrs-site' ), __( 'We launch on your domain and can keep improving it with SEO and content.', 'semrs-site' ) ) ) as $step ) {
		$c .= '<!-- wp:group {"layout":{"type":"default"}} -->' . "\n<div class=\"wp-block-group\"><!-- wp:semrs/icon {\"icon\":\"" . $step[0] . "\",\"size\":44,\"shape\":\"circle\",\"bg\":\"#fbf4e0\"} /-->\n\n" . $h( $step[1], 3 ) . $p( $step[2] ) . "</div>\n<!-- /wp:group -->\n\n";
	}
	$c .= "<!-- /wp:semrs/container -->\n<!-- /wp:semrs/container -->\n\n";
	$c .= '<!-- wp:semrs/container {"align":"full","gradient":"navy","textColor":"#ffffff","padTop":64,"padBottom":64,"tag":"section"} -->' . "\n" . $h( __( 'Ready for a website that works for your business?', 'semrs-site' ), 2, true );
	$c .= '<!-- wp:paragraph {"align":"center"} -->' . "\n<p class=\"has-text-align-center\">" . esc_html__( 'Tell us about your business and we will send a clear quote. No obligation.', 'semrs-site' ) . "</p>\n<!-- /wp:paragraph -->\n\n" . $btns( array( array( __( 'Get a free website quote', 'semrs-site' ), $s['contact'] ) ), true ) . '<!-- /wp:semrs/container -->';
	return $c;
}

/**
 * Create the portfolio page as a draft (existing page is never changed).
 *
 * @return int Page ID, 0 when it already exists.
 */
function semrs_portfolio_install() {
	$found = get_posts( array( 'name' => 'portfolio', 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => 1 ) );
	if ( $found ) {
		return 0;
	}
	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => __( 'Web Design Portfolio', 'semrs-site' ),
				'post_name'    => 'portfolio',
				'post_content' => semrs_portfolio_page_content(),
			)
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, 'semrs_hide_title', 1 );
	update_post_meta( $id, '_wp_page_template', 'page-templates/full-width.php' );
	update_post_meta( $id, '_semrs_ads_off', true ); // Sales page: no ads.
	if ( function_exists( 'semrs_store_set_seo' ) ) {
		semrs_store_set_seo( $id, __( 'Web Design Portfolio: Ready-Made Website Designs | SEMRS', 'semrs-site' ), __( 'Browse SEMRS website designs for agencies, local services, clinics, schools, travel and blogs. See every page on desktop and phone, then get your own website built.', 'semrs-site' ), 'web design portfolio' );
	}
	return (int) $id;
}

/**
 * Save portfolio settings from the semrs.com pages screen.
 */
function semrs_portfolio_save() {
	if ( ! isset( $_POST['semrs_portfolio_save'] ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_site_install' ) ) {
		return;
	}
	$in   = wp_unslash( $_POST );
	$live = array_intersect( array_map( 'sanitize_key', (array) ( $in['semrs_pf_live'] ?? array() ) ), array_keys( semrs_portfolio_subdomains() ) );
	$pattern = trim( sanitize_text_field( $in['semrs_pf_pattern'] ?? '' ) );
	update_option(
		'semrs_portfolio',
		array(
			'pattern' => preg_match( '#^https?://#', $pattern ) && false !== strpos( $pattern, '{sub}' ) ? $pattern : 'https://{sub}.demo.semrs.com/',
			'contact' => esc_url_raw( $in['semrs_pf_contact'] ?? '' ),
			'live'    => array_values( $live ),
		),
		false
	);
}
add_action( 'admin_init', 'semrs_portfolio_save' );

/**
 * Settings and installer box (printed inside the semrs.com pages form).
 */
function semrs_portfolio_admin_box() {
	$s = semrs_portfolio_settings();
	echo '<h2>' . esc_html__( 'Portfolio hub (portfolio.semrs.com)', 'semrs-site' ) . '</h2><p>' . esc_html__( 'Creates the Web Design Portfolio page at /portfolio/ as a draft: hero, every design with full-page desktop and phone previews, how it works and a quote button. Tick a design once its demo subdomain is live to show its "Live demo" button.', 'semrs-site' ) . '</p>';
	printf( '<p><label><strong>%s</strong><br><input class="regular-text" name="semrs_pf_pattern" value="%s"></label><br><span class="description">%s</span></p>', esc_html__( 'Demo address pattern', 'semrs-site' ), esc_attr( $s['pattern'] ), esc_html__( '{sub} is replaced with the demo name below, e.g. https://pets.demo.semrs.com/', 'semrs-site' ) );
	printf( '<p><label><strong>%s</strong><br><input class="regular-text" type="url" name="semrs_pf_contact" value="%s"></label></p>', esc_html__( 'Quote / contact page', 'semrs-site' ), esc_attr( $s['contact'] ) );
	echo '<fieldset><legend><strong>' . esc_html__( 'Live demos', 'semrs-site' ) . '</strong></legend><p style="columns:3;max-width:900px">';
	foreach ( semrs_portfolio_subdomains() as $id => $sub ) {
		printf( '<label style="display:block"><input type="checkbox" name="semrs_pf_live[]" value="%s"%s> %s</label>', esc_attr( $id ), checked( in_array( $id, (array) $s['live'], true ), true, false ), esc_html( untrailingslashit( preg_replace( '#^https?://#', '', str_replace( '{sub}', $sub, $s['pattern'] ) ) ) ) );
	}
	echo '</p></fieldset>';
	submit_button( __( 'Save portfolio settings', 'semrs-site' ), 'secondary', 'semrs_portfolio_save', false );
	echo ' ';
	submit_button( __( 'Create portfolio page as draft', 'semrs-site' ), 'secondary', 'semrs_portfolio_install', false );
}
