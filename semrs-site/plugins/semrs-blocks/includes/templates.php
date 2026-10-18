<?php
/**
 * Templates library: starter sites (one-click import), full-page templates
 * and sections. Everything ships with the plugin; nothing is downloaded.
 *
 * - Sections: templates/sections/*.html (block markup with {{tokens}}).
 * - Sites:    templates/sites.php (pages = lists of sections + copy).
 * - Images:   templates/images/*.png (original artwork, GPL).
 * - Previews: templates/previews/{site}-desktop.jpg / -mobile.jpg.
 *
 * Pages and sections are also registered as block patterns, so they can be
 * inserted from the editor (Patterns → SEMRS full pages / SEMRS sections).
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Starter sites (free here; SEMRS Pro adds more with the filter).
 *
 * @return array
 */
function semrs_blocks_template_sites() {
	static $sites = null;
	if ( null === $sites ) {
		$sites = include SEMRS_BLOCKS_DIR . 'templates/sites.php';
		foreach ( (array) include SEMRS_BLOCKS_DIR . 'templates/niches.php' as $niche ) {
			$sites[ $niche['id'] ] = semrs_blocks_niche_site( $niche );
		}
		$sites = apply_filters( 'semrs_blocks_template_sites', $sites );
	}
	return $sites;
}

/**
 * Build a full starter site from a short niche definition.
 *
 * Niche keys: id, title, tier, category, description, kind (services|local|
 * blog|education), art (image file), hero_eyebrow, hero_title, hero_text,
 * cta_text, cta2_text, services (6 × [icon, title, text]), about_title,
 * about_text, about_points, steps (3 × [icon, title, text], optional),
 * faq (4 × [q, a]), form_services, plus any copy key to override.
 *
 * @param array $n Niche.
 * @return array Site.
 */
function semrs_blocks_niche_site( $n ) {
	$kind = $n['kind'] ?? 'services';
	$copy = array(
		'hero_eyebrow'     => $n['hero_eyebrow'] ?? '{{name}}',
		'hero_title'       => $n['hero_title'],
		'hero_text'        => $n['hero_text'],
		'hero_alt'         => $n['title'] . ' illustration',
		'cta_text'         => $n['cta_text'] ?? 'Get in touch',
		'cta_url'          => $n['cta_url'] ?? '/contact/',
		'cta2_text'        => $n['cta2_text'] ?? 'Our services',
		'cta2_url'         => '/' . ( 'education' === $kind ? 'programs' : 'services' ) . '/',
		'features_eyebrow' => $n['features_eyebrow'] ?? ( 'education' === $kind ? 'Programs' : 'What we offer' ),
		'features_title'   => $n['features_title'] ?? ( 'education' === $kind ? 'Choose your program' : 'Our services' ),
		'features_text'    => $n['features_text'] ?? '',
		'f_url'            => '/' . ( 'education' === $kind ? 'programs' : 'services' ) . '/',
		'about_eyebrow'    => 'About {{name}}',
		'about_title'      => $n['about_title'],
		'about_text'       => $n['about_text'],
		'about_alt'        => 'Dashboard with a rising chart',
		'about_points'     => implode( "\n", (array) $n['about_points'] ),
		'steps_eyebrow'    => 'How it works',
		'steps_title'      => $n['steps_title'] ?? 'Simple from start to finish',
		'faq_eyebrow'      => 'Questions',
		'faq_title'        => 'Frequently asked questions',
		'cta_title'        => $n['cta_title'] ?? 'Ready to get started?',
		'cta_band_text'    => $n['cta_band_text'] ?? 'Send us a message and we will reply within one business day.',
		'contact_title'    => $n['contact_title'] ?? 'Get in touch',
		'contact_text'     => $n['contact_text'] ?? 'Tell us what you need and we will get back to you quickly.',
		'contact_points'   => implode( "\n", (array) ( $n['contact_points'] ?? array( 'Quick, friendly reply', 'Clear prices before we start', 'Your details stay private' ) ) ),
		'form_services'    => implode( "\n", (array) ( $n['form_services'] ?? array() ) ),
		'text_title'       => 'Our story',
		'text_p1'          => $n['story_1'] ?? '{{name}} was started to offer honest, high-quality service with clear prices and friendly people.',
		'text_p2'          => $n['story_2'] ?? 'Today we help customers in {{city}} and beyond, and most new customers come from recommendations.',
		'posts_title'      => $n['posts_title'] ?? 'Latest articles',
		'hours_title'      => 'Opening hours',
		'hours_week'       => $n['hours_week'] ?? '8:00 – 18:00',
		'hours_sat'        => $n['hours_sat'] ?? '9:00 – 14:00',
		'hours_sun'        => $n['hours_sun'] ?? 'Closed',
		'areas_title'      => 'Areas we serve',
		'areas'            => "{{city}}\nCity centre\nNorth district\nSouth district\nEast district\nWest district",
		'page_title'       => 'blog' === $kind ? $n['hero_title'] : '{{name}}',
		'page_intro'       => $n['hero_text'],
	);
	foreach ( array_values( (array) $n['services'] ) as $i => $s ) {
		$copy[ 'f' . ( $i + 1 ) . '_icon' ]  = $s[0];
		$copy[ 'f' . ( $i + 1 ) . '_title' ] = $s[1];
		$copy[ 'f' . ( $i + 1 ) . '_text' ]  = $s[2];
	}
	$steps = $n['steps'] ?? array( array( 'chat', 'Tell us what you need', 'A quick message or call is all it takes.' ), array( 'calendar', 'Pick a time', 'We agree the details and a clear price first.' ), array( 'thumbs-up', 'Job done', 'We deliver on time and check you are happy.' ) );
	foreach ( array_values( $steps ) as $i => $s ) {
		$copy[ 's' . ( $i + 1 ) . '_icon' ]  = $s[0];
		$copy[ 's' . ( $i + 1 ) . '_title' ] = $s[1];
		$copy[ 's' . ( $i + 1 ) . '_text' ]  = $s[2];
	}
	foreach ( array_values( (array) $n['faq'] ) as $i => $f ) {
		$copy[ 'q' . ( $i + 1 ) ] = $f[0];
		$copy[ 'a' . ( $i + 1 ) ] = $f[1];
	}
	$copy = array_merge( $copy, (array) ( $n['copy'] ?? array() ) );

	$svc = 'education' === $kind ? 'programs' : 'services';
	if ( 'blog' === $kind ) {
		$pages = array(
			'home'    => array( 'title' => 'Home', 'front' => true, 'sections' => array( 'page-header', 'posts', 'about', 'cta' ) ),
			'about'   => array( 'title' => 'About', 'sections' => array( 'page-header', 'about', 'text' ), 'vars' => array( 'page_title' => 'About {{name}}', 'page_intro' => $n['about_text'] ) ),
			'contact' => array( 'title' => 'Contact', 'sections' => array( 'page-header', 'contact' ), 'vars' => array( 'page_title' => 'Contact', 'page_intro' => 'Questions, ideas or collaborations: get in touch.' ) ),
		);
	} else {
		$home = array( 'hero', 'features', 'about', 'steps', 'faq', 'cta' );
		if ( 'local' === $kind ) {
			$home = array( 'hero', 'features', 'about', 'hours', 'faq', 'cta' );
		}
		$pages = array(
			'home'  => array( 'title' => 'Home', 'front' => true, 'sections' => $home ),
			$svc    => array( 'title' => 'education' === $kind ? 'Programs' : 'Services', 'sections' => array( 'page-header', 'features6', 'faq', 'cta' ), 'vars' => array( 'page_title' => $copy['features_title'], 'page_intro' => $copy['features_text'] ? $copy['features_text'] : $n['hero_text'] ) ),
			'about' => array( 'title' => 'About', 'sections' => array( 'page-header', 'text', 'about', 'cta' ), 'vars' => array( 'page_title' => 'About {{name}}', 'page_intro' => $n['about_title'] ) ),
		);
		if ( 'education' === $kind ) {
			$pages['admissions'] = array( 'title' => 'Admissions', 'sections' => array( 'page-header', 'steps', 'contact' ), 'vars' => array( 'page_title' => $n['admissions_title'] ?? 'Admissions & registration', 'page_intro' => $n['admissions_intro'] ?? 'Register your interest and we will guide you through the next steps.', 'contact_title' => 'Registration form', 'contact_text' => 'Fill in the form and choose your program. We will contact you with dates, fees and the documents you need.' ) );
		}
		$pages['blog']    = array( 'title' => $n['blog_title'] ?? 'Blog', 'posts_page' => true );
		$pages['contact'] = array( 'title' => 'Contact', 'sections' => 'local' === $kind ? array( 'page-header', 'contact', 'hours' ) : array( 'page-header', 'contact' ), 'vars' => array( 'page_title' => 'Contact {{name}}', 'page_intro' => $copy['contact_text'] ) );
	}

	return array(
		'title'       => $n['title'],
		'tier'        => $n['tier'] ?? 'free',
		'category'    => $n['category'],
		'description' => $n['description'],
		'images'      => array( 'hero' => $n['art'] ?? 'hero-woman.png', 'about' => 'about-dashboard.png' ),
		'image_dir'   => $n['image_dir'] ?? SEMRS_BLOCKS_DIR . 'templates/images/',
		'image_base'  => $n['image_base'] ?? SEMRS_BLOCKS_URL . 'templates/images/',
		'preview_dir' => $n['preview_dir'] ?? null,
		'copy'        => $copy,
		'pages'       => $pages,
	);
}

/**
 * Pro sites shown locked when SEMRS Pro is not installed.
 *
 * @return array id => [title, category, description]
 */
function semrs_blocks_template_pro_teasers() {
	return array(
		'consultant' => array( 'Consultant', 'Business', 'Coach or consultant: personal brand, services in tabs, booking and latest articles.' ),
		'clinic'     => array( 'Clinic', 'Health', 'Dental or medical clinic: treatments, opening hours, areas served and appointment requests.' ),
		'travel-agency'  => array( 'Travel Agency', 'Travel', 'Travel agency or tour operator: tours, destinations, enquiry form and travel blog.' ),
		'camping'        => array( 'Camping & Outdoors', 'Travel', 'Campsite, glamping or outdoor adventures: pitches, activities and booking requests.' ),
		'school'         => array( 'School & Admissions', 'Education', 'School, college or academy: programs, admissions process and registration form.' ),
		'online-courses' => array( 'Online Courses', 'Education', 'Online academy: courses, enrolment and student registration.' ),
		'ai-llm'         => array( 'AI & LLM Services', 'Technology', 'AI consultancy: chatbots, LLM integration, automation and AI search optimisation.' ),
	);
}

/**
 * Folders that hold section files (SEMRS Pro adds its own).
 *
 * @return string[]
 */
function semrs_blocks_template_section_dirs() {
	return apply_filters( 'semrs_blocks_template_section_dirs', array( SEMRS_BLOCKS_DIR . 'templates/sections/' ) );
}

/**
 * Raw section markup.
 *
 * @param string $id Section id.
 * @return string
 */
function semrs_blocks_template_section( $id ) {
	/**
	 * Built-in dynamic sections (e.g. the digital shop). Return markup to handle an ID.
	 *
	 * @param string $html '' unless handled.
	 * @param string $id   Section ID.
	 */
	$dynamic = (string) apply_filters( 'semrs_blocks_template_section_html', '', $id );
	if ( '' !== $dynamic ) {
		return $dynamic;
	}
	foreach ( array_reverse( semrs_blocks_template_section_dirs() ) as $dir ) {
		$file = $dir . sanitize_file_name( $id ) . '.html';
		if ( file_exists( $file ) ) {
			return (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	return '';
}

/**
 * Fill {{tokens}}. Inside block comments values are JSON-escaped, elsewhere
 * HTML-escaped. {{img:key}} becomes an image URL. Unknown tokens are removed.
 *
 * @param string $html   Markup.
 * @param array  $vars   key => plain text.
 * @param array  $images key => URL.
 * @return string
 */
function semrs_blocks_template_fill( $html, $vars, $images ) {
	$html = preg_replace_callback(
		'/\{\{img:([a-z0-9_-]+)\}\}/',
		function ( $m ) use ( $images ) {
			return esc_url( $images[ $m[1] ] ?? '' );
		},
		$html
	);
	// Block comment attributes (JSON).
	$html = preg_replace_callback(
		'/<!-- wp:[^>]*?-->/s',
		function ( $m ) use ( $vars ) {
			return preg_replace_callback(
				'/\{\{([a-z0-9_]+)\}\}/',
				function ( $t ) use ( $vars ) {
					$json = wp_json_encode( (string) ( $vars[ $t[1] ] ?? '' ) );
					return str_replace( '--', '--', substr( $json, 1, -1 ) );
				},
				$m[0]
			);
		},
		$html
	);
	// Everything else (HTML).
	return preg_replace_callback(
		'/\{\{([a-z0-9_]+)\}\}/',
		function ( $t ) use ( $vars ) {
			return esc_html( (string) ( $vars[ $t[1] ] ?? '' ) );
		},
		$html
	);
}

/**
 * Copy of a site page with the owner's details filled in.
 *
 * @param array $site  Site definition.
 * @param array $page  Page definition.
 * @param array $owner name, phone, email, city, country.
 * @return array key => text
 */
function semrs_blocks_template_vars( $site, $page, $owner ) {
	$base = array(
		'name'       => $owner['name'],
		'phone'      => $owner['phone'],
		'phone_link' => preg_replace( '/[^0-9+]/', '', $owner['phone'] ),
		'email'      => $owner['email'],
		'city'       => $owner['city'],
		'country'    => $owner['country'],
		'year'       => gmdate( 'Y' ),
	);
	$vars = array_merge( $base, (array) ( $site['copy'] ?? array() ), (array) ( $page['vars'] ?? array() ) );
	foreach ( $vars as $k => $v ) {
		$vars[ $k ] = strtr( (string) $v, array( '{{name}}' => $base['name'], '{{phone}}' => $base['phone'], '{{phone_link}}' => $base['phone_link'], '{{email}}' => $base['email'], '{{city}}' => $base['city'], '{{year}}' => $base['year'] ) );
	}
	return $vars;
}

/**
 * Full block markup of one page.
 *
 * @param array $site   Site.
 * @param array $page   Page.
 * @param array $owner  Owner details.
 * @param array $images key => URL.
 * @return string
 */
function semrs_blocks_template_page_html( $site, $page, $owner, $images ) {
	$vars = semrs_blocks_template_vars( $site, $page, $owner );
	$out  = array();
	foreach ( (array) ( $page['sections'] ?? array() ) as $section ) {
		$out[] = semrs_blocks_template_fill( semrs_blocks_template_section( $section ), $vars, $images );
	}
	return implode( "\n\n", $out );
}

/**
 * Example details used for editor patterns and previews.
 *
 * @return array
 */
function semrs_blocks_template_example_owner() {
	return array(
		'name'    => get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Your Business',
		'phone'   => '+1 555 010 0200',
		'email'   => 'hello@example.com',
		'city'    => 'Your City',
		'country' => 'US',
	);
}

/**
 * Plugin URLs of a site's images (for patterns, before import).
 *
 * @param array $site Site.
 * @return array
 */
function semrs_blocks_template_image_urls( $site ) {
	$urls = array();
	foreach ( (array) ( $site['images'] ?? array() ) as $key => $file ) {
		$urls[ $key ] = ( $site['image_base'] ?? SEMRS_BLOCKS_URL . 'templates/images/' ) . $file;
	}
	return $urls;
}

/**
 * Register full pages and sections as block patterns.
 */
function semrs_blocks_template_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}
	// Patterns are only needed in the admin and the editor (REST); skip the work on normal page views.
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_admin() && false === strpos( $uri, '/wp-json/' ) && false === strpos( $uri, 'rest_route=' ) ) {
		return;
	}
	register_block_pattern_category( 'semrs-pages', array( 'label' => __( 'SEMRS full pages', 'semrs-blocks' ) ) );
	register_block_pattern_category( 'semrs', array( 'label' => __( 'SEMRS sections', 'semrs-blocks' ) ) );
	$owner = semrs_blocks_template_example_owner();
	foreach ( semrs_blocks_template_sites() as $sid => $site ) {
		if ( 'pro' === ( $site['tier'] ?? 'free' ) && ! defined( 'SEMRS_PRO_VERSION' ) ) {
			continue;
		}
		$images = semrs_blocks_template_image_urls( $site );
		foreach ( $site['pages'] as $pid => $page ) {
			if ( empty( $page['sections'] ) ) {
				continue;
			}
			register_block_pattern(
				'semrs-blocks/' . $sid . '-' . $pid,
				array(
					/* translators: 1: starter site, 2: page */
					'title'      => sprintf( __( '%1$s: %2$s page', 'semrs-blocks' ), $site['title'], $page['title'] ),
					'categories' => array( 'semrs-pages' ),
					'keywords'   => array( 'page', 'template', strtolower( $site['title'] ), strtolower( $page['title'] ) ),
					'content'    => semrs_blocks_template_page_html( $site, $page, $owner, $images ),
				)
			);
		}
	}
	// Sections (with the Agency copy as example text).
	$sites  = semrs_blocks_template_sites();
	$agency = $sites['agency'] ?? reset( $sites );
	$labels = array(
		'hero'      => __( 'Hero with image', 'semrs-blocks' ),
		'features'  => __( 'Services (3 cards)', 'semrs-blocks' ),
		'features6' => __( 'Services (6 cards)', 'semrs-blocks' ),
		'about'     => __( 'About with image and checklist', 'semrs-blocks' ),
		'steps'     => __( 'How it works (3 steps)', 'semrs-blocks' ),
		'pricing'   => __( 'Pricing (3 plans)', 'semrs-blocks' ),
		'faq'       => __( 'FAQ accordion', 'semrs-blocks' ),
		'cta'       => __( 'Call to action band', 'semrs-blocks' ),
		'contact'   => __( 'Contact details and form', 'semrs-blocks' ),
		'hours'     => __( 'Opening hours and areas', 'semrs-blocks' ),
		'posts'     => __( 'Latest posts grid', 'semrs-blocks' ),
		'page-header' => __( 'Page header', 'semrs-blocks' ),
	);
	$local = $sites['local'] ?? $agency;
	foreach ( $labels as $id => $label ) {
		$src = 'hours' === $id ? $local : $agency;
		register_block_pattern(
			'semrs-blocks/section-' . $id,
			array(
				/* translators: %s: section name */
				'title'      => sprintf( __( 'SEMRS: %s', 'semrs-blocks' ), $label ),
				'categories' => array( 'semrs' ),
				'content'    => semrs_blocks_template_page_html( $src, array( 'sections' => array( $id ), 'vars' => array( 'page_title' => 'Page title', 'page_intro' => 'A short introduction to this page.' ) ), $owner, semrs_blocks_template_image_urls( $src ) ),
			)
		);
	}
}
add_action( 'init', 'semrs_blocks_template_patterns', 30 );

/* ---------------------------------------------------------------------------
 * Import
 * ------------------------------------------------------------------------- */

/**
 * Copy a bundled image into the Media Library once (reused on later imports).
 *
 * @param string $path Absolute file path.
 * @param string $alt  Alt text.
 * @return int Attachment ID.
 */
function semrs_blocks_template_media( $path, $alt ) {
	$done = (array) get_option( 'semrs_blocks_template_media', array() );
	$key  = md5( $path );
	if ( ! empty( $done[ $key ] ) && get_post( $done[ $key ] ) ) {
		return (int) $done[ $key ];
	}
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$up   = wp_upload_dir();
	$name = wp_unique_filename( $up['path'], 'semrs-' . basename( $path ) );
	$dest = trailingslashit( $up['path'] ) . $name;
	if ( ! copy( $path, $dest ) ) {
		return 0;
	}
	$type = wp_check_filetype( $dest );
	$id   = wp_insert_attachment( array( 'post_mime_type' => $type['type'], 'post_title' => $alt, 'post_status' => 'inherit' ), $dest );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$done[ $key ] = $id;
	update_option( 'semrs_blocks_template_media', $done, false );
	return (int) $id;
}

/**
 * Import a starter site.
 *
 * @param string $sid  Site id.
 * @param array  $opts owner (name, phone, email, city, country), status,
 *                     home (bool), menu (bool), theme (bool).
 * @return array|WP_Error Report.
 */
function semrs_blocks_template_import( $sid, $opts ) {
	$sites = semrs_blocks_template_sites();
	if ( empty( $sites[ $sid ] ) ) {
		return new WP_Error( 'semrs_tpl', __( 'Unknown starter site.', 'semrs-blocks' ) );
	}
	$site = $sites[ $sid ];
	if ( 'pro' === ( $site['tier'] ?? 'free' ) && ! defined( 'SEMRS_PRO_VERSION' ) ) {
		return new WP_Error( 'semrs_tpl', __( 'This starter site is part of SEMRS Pro.', 'semrs-blocks' ) );
	}
	$owner  = $opts['owner'];
	$status = 'publish' === $opts['status'] ? 'publish' : 'draft';
	$report = array( 'created' => array(), 'skipped' => array(), 'site' => $sid );

	// Images into the Media Library, then their URLs.
	$images = array();
	$dir    = $site['image_dir'] ?? SEMRS_BLOCKS_DIR . 'templates/images/';
	foreach ( (array) ( $site['images'] ?? array() ) as $key => $file ) {
		$id             = semrs_blocks_template_media( $dir . $file, $owner['name'] . ' – ' . $key );
		$images[ $key ] = $id ? wp_get_attachment_url( $id ) : '';
	}

	$ids      = array();
	$is_semrs = 'semrs' === get_template();
	foreach ( $site['pages'] as $pid => $page ) {
		$existing = get_page_by_path( $pid );
		if ( $existing ) {
			$report['skipped'][ $pid ] = (int) $existing->ID;
			$ids[ $pid ]               = (int) $existing->ID;
			continue;
		}
		$content = empty( $page['sections'] ) ? '' : semrs_blocks_template_page_html( $site, $page, $owner, $images );
		$id      = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => $status,
				'post_title'   => strtr( $page['title'], array( '{{name}}' => $owner['name'] ) ),
				'post_name'    => $pid,
				'post_content' => wp_slash( $content ), // wp_insert_post() unslashes; this keeps the escapes inside block attributes.
				'menu_order'   => count( $ids ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			continue;
		}
		$ids[ $pid ]               = (int) $id;
		$report['created'][ $pid ] = (int) $id;
		if ( ! empty( $page['sections'] ) ) {
			update_post_meta( $id, 'semrs_hide_title', '1' ); // Sections include the H1.
			if ( $is_semrs ) {
				update_post_meta( $id, '_wp_page_template', 'page-templates/full-width.php' );
			}
		}
		update_post_meta( $id, '_semrs_template', $sid );
	}

	// Home and blog pages.
	if ( ! empty( $opts['home'] ) ) {
		foreach ( $site['pages'] as $pid => $page ) {
			if ( ! empty( $page['front'] ) && ! empty( $ids[ $pid ] ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $ids[ $pid ] );
			}
			if ( ! empty( $page['posts_page'] ) && ! empty( $ids[ $pid ] ) ) {
				update_option( 'page_for_posts', $ids[ $pid ] );
			}
		}
	}

	// Menu.
	if ( ! empty( $opts['menu'] ) ) {
		$menu_name = $owner['name'] . ' menu';
		$menu      = wp_get_nav_menu_object( $menu_name );
		$menu_id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );
		if ( $menu_id && ! $menu ) {
			// Sites with many pages (shops) list their menu pages; others use every page.
			$order = isset( $site['menu'] ) ? array_intersect_key( $site['pages'], array_flip( (array) $site['menu'] ) ) : $site['pages'];
			if ( isset( $site['menu'] ) ) {
				$order = array_merge( array_flip( array_intersect( (array) $site['menu'], array_keys( $order ) ) ), $order ); // Menu order, not page order.
			}
			foreach ( $order as $pid => $page ) {
				if ( ! empty( $ids[ $pid ] ) && empty( $page['front'] ) ) {
					wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object-id' => $ids[ $pid ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
				}
			}
		}
		if ( $menu_id ) {
			$locations            = (array) get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			$report['menu'] = $menu_id;
		}
	}

	// SEMRS theme: header button, contact details, header layout.
	if ( ! empty( $opts['theme'] ) && $is_semrs ) {
		$copy = $site['copy'] ?? array();
		set_theme_mod( 'semrs_theme_phone', $owner['phone'] );
		set_theme_mod( 'semrs_theme_email', $owner['email'] );
		if ( ! empty( $copy['cta_text'] ) ) {
			set_theme_mod( 'semrs_theme_cta_text', strtr( $copy['cta_text'], array( '{{phone}}' => $owner['phone'] ) ) );
			set_theme_mod( 'semrs_theme_cta_url', strtr( $copy['cta_url'] ?? '/contact/', array( '{{phone_link}}' => preg_replace( '/[^0-9+]/', '', $owner['phone'] ) ) ) );
		}
		set_theme_mod( 'semrs_theme_hb_layout', wp_json_encode( $site['header'] ?? array( 'top' => array( 'left' => array( 'contact' ), 'center' => array(), 'right' => array( 'social' ) ), 'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'menu', 'button', 'search' ) ), 'bottom' => array( 'left' => array(), 'center' => array(), 'right' => array() ) ) ) );
	}

	$log         = (array) get_option( 'semrs_blocks_template_imports', array() );
	$log[]       = array( 'site' => $sid, 'time' => time(), 'pages' => $report['created'] );
	update_option( 'semrs_blocks_template_imports', array_slice( $log, -20 ), false );
	return $report;
}

/* ---------------------------------------------------------------------------
 * Admin screen: SEMRS → Templates
 * ------------------------------------------------------------------------- */

/**
 * Menu.
 */
function semrs_blocks_templates_menu() {
	$parent = defined( 'SEMRS_CORE_VERSION' ) ? 'semrs' : 'semrs-blocks';
	add_submenu_page( $parent, __( 'Templates & Starter Sites', 'semrs-blocks' ), __( 'Templates', 'semrs-blocks' ), 'manage_options', 'semrs-templates', 'semrs_blocks_templates_page' );
}
add_action( 'admin_menu', 'semrs_blocks_templates_menu', 21 );

/**
 * Assets.
 *
 * @param string $hook Screen.
 */
function semrs_blocks_templates_assets( $hook ) {
	if ( false === strpos( $hook, 'semrs-templates' ) ) {
		return;
	}
	wp_enqueue_style( 'semrs-blocks-admin', SEMRS_BLOCKS_URL . 'assets/admin/admin.css', array(), SEMRS_BLOCKS_VERSION );
	wp_enqueue_style( 'semrs-blocks-front' );
	wp_enqueue_script( 'semrs-blocks-admin', SEMRS_BLOCKS_URL . 'assets/admin/admin.js', array(), SEMRS_BLOCKS_VERSION, true );
	wp_enqueue_script( 'semrs-blocks-showcase-admin', SEMRS_BLOCKS_URL . 'assets/js/showcase.js', array(), SEMRS_BLOCKS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'semrs_blocks_templates_assets' );

/**
 * Preview image URL (falls back to the site's hero image).
 *
 * @param string $sid  Site id.
 * @param string $view desktop|mobile.
 * @param array  $site Site (optional).
 * @return string
 */
function semrs_blocks_template_preview( $sid, $view, $site = array() ) {
	$dirs = array( array( SEMRS_BLOCKS_DIR . 'templates/previews/', SEMRS_BLOCKS_URL . 'templates/previews/' ) );
	if ( ! empty( $site['preview_dir'] ) ) {
		array_unshift( $dirs, $site['preview_dir'] );
	}
	foreach ( $dirs as $d ) {
		if ( file_exists( $d[0] . $sid . '-' . $view . '.jpg' ) ) {
			return $d[1] . $sid . '-' . $view . '.jpg';
		}
	}
	return '';
}

/**
 * Handle the import form.
 */
function semrs_blocks_templates_import_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-blocks' ) );
	}
	check_admin_referer( 'semrs_tpl_import' );
	$in     = wp_unslash( $_POST );
	$sid    = sanitize_key( $in['site'] ?? '' );
	$report = semrs_blocks_template_import(
		$sid,
		array(
			'owner'  => array(
				'name'    => sanitize_text_field( $in['name'] ?? '' ) ? sanitize_text_field( $in['name'] ) : get_bloginfo( 'name' ),
				'phone'   => sanitize_text_field( $in['phone'] ?? '' ),
				'email'   => sanitize_email( $in['email'] ?? '' ),
				'city'    => sanitize_text_field( $in['city'] ?? '' ),
				'country' => strtoupper( substr( sanitize_key( $in['country'] ?? 'us' ), 0, 2 ) ),
			),
			'status' => sanitize_key( $in['status'] ?? 'draft' ),
			'home'   => ! empty( $in['home'] ),
			'menu'   => ! empty( $in['menu'] ),
			'theme'  => ! empty( $in['theme'] ),
		)
	);
	if ( is_wp_error( $report ) ) {
		wp_die( esc_html( $report->get_error_message() ) );
	}
	set_transient( 'semrs_tpl_report_' . get_current_user_id(), $report, HOUR_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=semrs-templates&done=1' ) );
	exit;
}
add_action( 'admin_post_semrs_tpl_import', 'semrs_blocks_templates_import_action' );

/**
 * One starter-site card.
 *
 * @param string $sid    Site id.
 * @param array  $site   title, tier, category, description.
 * @param bool   $locked Pro not installed.
 */
function semrs_blocks_template_card( $sid, $site, $locked, $buttons = true ) {
	$desk = semrs_blocks_template_preview( $sid, 'desktop', $site );
	$mob  = semrs_blocks_template_preview( $sid, 'mobile', $site );
	$tier = $site['tier'] ?? 'free';
	echo '<article class="semrs-show semrs-card" data-tier="' . esc_attr( $tier ) . '" data-group="' . esc_attr( sanitize_title( $site['category'] ?? '' ) ) . '" data-search="' . esc_attr( strtolower( $site['title'] . ' ' . ( $site['description'] ?? '' ) ) ) . '">';
	echo '<div class="semrs-show__media"><div class="semrs-show__desk">' . ( $desk ? '<img class="semrs-show__img" src="' . esc_url( $desk ) . '" alt="" loading="lazy">' : '<span class="semrs-show__placeholder">' . esc_html( $site['title'] ) . '</span>' ) . '</div>';
	if ( $mob ) {
		echo '<div class="semrs-show__phone" hidden><div class="semrs-show__frame"><img class="semrs-show__img" src="' . esc_url( $mob ) . '" alt="" loading="lazy"></div></div>';
		echo '<div class="semrs-show__switch" role="group" aria-label="' . esc_attr__( 'Preview size', 'semrs-blocks' ) . '"><button type="button" aria-pressed="true" data-view="desktop">' . esc_html__( 'Desktop', 'semrs-blocks' ) . '</button><button type="button" aria-pressed="false" data-view="mobile">' . esc_html__( 'Mobile', 'semrs-blocks' ) . '</button></div>';
	}
	echo '</div><div class="semrs-show__bar"><h3 class="semrs-show__title">' . esc_html( $site['title'] ) . '</h3><span class="semrs-show__badge semrs-show__badge--' . esc_attr( $tier ) . '">' . ( 'pro' === $tier ? esc_html__( 'Pro', 'semrs-blocks' ) : esc_html__( 'Free', 'semrs-blocks' ) ) . '</span></div>';
	echo '<p class="semrs-show__text">' . esc_html( $site['description'] ?? '' ) . '</p>';
	if ( ! $buttons ) {
		echo '</article>';
		return;
	}
	echo '<div class="semrs-show__btns">';
	if ( $locked ) {
		echo '<a class="button button-primary" href="' . esc_url( semrs_blocks_pro_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Unlock with Pro', 'semrs-blocks' ) . '</a>';
	} else {
		echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=semrs-templates&site=' . $sid ) ) . '">' . esc_html__( 'Import', 'semrs-blocks' ) . '</a>';
		if ( $desk ) {
			echo '<a class="button" href="' . esc_url( $desk ) . '" target="_blank" rel="noopener">' . esc_html__( 'Full preview', 'semrs-blocks' ) . '</a>';
		}
	}
	echo '</div></article>';
}

/**
 * Screen.
 */
function semrs_blocks_templates_page() {
	$sites   = semrs_blocks_template_sites();
	$sid     = isset( $_GET['site'] ) ? sanitize_key( $_GET['site'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$report  = isset( $_GET['done'] ) ? get_transient( 'semrs_tpl_report_' . get_current_user_id() ) : false; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap semrs-ui"><div class="semrs-head"><div><h1>' . esc_html__( 'Templates & Starter Sites', 'semrs-blocks' ) . '</h1><p>' . esc_html__( 'Import a complete website in one click, or insert single pages and sections from the editor (Patterns → SEMRS full pages / SEMRS sections). Everything is included in the plugin; nothing is downloaded.', 'semrs-blocks' ) . '</p></div></div><hr class="wp-header-end">';

	if ( $report ) {
		echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Import finished.', 'semrs-blocks' ) . '</strong> ';
		/* translators: 1: created pages, 2: skipped pages */
		echo esc_html( sprintf( __( '%1$d pages created, %2$d skipped because a page with the same address already exists.', 'semrs-blocks' ), count( $report['created'] ), count( $report['skipped'] ) ) ) . '</p><ul style="list-style:disc;margin-left:20px">';
		foreach ( $report['created'] as $pid => $id ) {
			echo '<li><a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a> · <a href="' . esc_url( get_permalink( $id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'view', 'semrs-blocks' ) . '</a></li>';
		}
		echo '</ul><p>' . esc_html__( 'Next: replace the example prices, opening hours and areas with your own, add your real photos, and check each page before publishing.', 'semrs-blocks' ) . '</p></div>';
		delete_transient( 'semrs_tpl_report_' . get_current_user_id() );
	}

	if ( $sid && isset( $sites[ $sid ] ) ) {
		$site   = $sites[ $sid ];
		$locked = 'pro' === ( $site['tier'] ?? 'free' ) && ! defined( 'SEMRS_PRO_VERSION' );
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=semrs-templates' ) ) . '">&larr; ' . esc_html__( 'All templates', 'semrs-blocks' ) . '</a></p>';
		echo '<div style="display:grid;gap:24px;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);align-items:start">';
		echo '<div class="semrs-block semrs-showcase" style="--sc-cols:1;margin:0">';
		semrs_blocks_template_card( $sid, $site, $locked, false );
		echo '</div><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="semrs-card" style="padding:20px">';
		/* translators: %s: site name */
		echo '<h2 style="margin-top:0">' . esc_html( sprintf( __( 'Import "%s"', 'semrs-blocks' ), $site['title'] ) ) . '</h2>';
		echo '<input type="hidden" name="action" value="semrs_tpl_import"><input type="hidden" name="site" value="' . esc_attr( $sid ) . '">';
		wp_nonce_field( 'semrs_tpl_import' );
		$field = function ( $name, $label, $value, $type = 'text', $help = '' ) {
			printf( '<p><label for="tpl-%1$s"><strong>%2$s</strong></label><br><input class="regular-text" type="%3$s" id="tpl-%1$s" name="%1$s" value="%4$s">%5$s</p>', esc_attr( $name ), esc_html( $label ), esc_attr( $type ), esc_attr( $value ), $help ? '<br><span class="description">' . esc_html( $help ) . '</span>' : '' );
		};
		$field( 'name', __( 'Business or site name', 'semrs-blocks' ), get_bloginfo( 'name' ) );
		$field( 'phone', __( 'Phone', 'semrs-blocks' ), get_theme_mod( 'semrs_theme_phone', '' ), 'text' );
		$field( 'email', __( 'Email (also receives contact form messages)', 'semrs-blocks' ), get_option( 'admin_email' ), 'email' );
		$field( 'city', __( 'City or area', 'semrs-blocks' ), '' );
		$field( 'country', __( 'Country code for phone numbers (e.g. US, GB, PK)', 'semrs-blocks' ), 'US' );
		echo '<p><strong>' . esc_html__( 'Pages', 'semrs-blocks' ) . '</strong><br>';
		foreach ( $site['pages'] as $pid => $page ) {
			$exists = get_page_by_path( $pid );
			echo esc_html( $page['title'] ) . ' <code>/' . esc_html( $pid ) . '/</code>' . ( $exists ? ' <em>(' . esc_html__( 'exists, will be skipped', 'semrs-blocks' ) . ')</em>' : '' ) . '<br>';
		}
		echo '</p><p><label><input type="radio" name="status" value="draft" checked> ' . esc_html__( 'Create as drafts (recommended: review, then publish)', 'semrs-blocks' ) . '</label><br><label><input type="radio" name="status" value="publish"> ' . esc_html__( 'Publish now', 'semrs-blocks' ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="home" value="1" checked> ' . esc_html__( 'Set the home page (and blog page)', 'semrs-blocks' ) . '</label><br>';
		echo '<label><input type="checkbox" name="menu" value="1" checked> ' . esc_html__( 'Create the main menu', 'semrs-blocks' ) . '</label><br>';
		if ( 'semrs' === get_template() ) {
			echo '<label><input type="checkbox" name="theme" value="1" checked> ' . esc_html__( 'Apply the header layout, button and contact details (SEMRS theme)', 'semrs-blocks' ) . '</label>';
		}
		echo '</p>';
		if ( $locked ) {
			echo '<p><a class="button button-primary" href="' . esc_url( semrs_blocks_pro_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Unlock with Pro', 'semrs-blocks' ) . '</a></p>';
		} else {
			submit_button( __( 'Import starter site', 'semrs-blocks' ), 'primary', 'submit', false );
		}
		echo '<p class="description">' . esc_html__( 'Existing pages, posts and settings are never deleted. Example prices, hours and areas are placeholders: change them before publishing.', 'semrs-blocks' ) . '</p></form></div></div>';
		return;
	}

	$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'sites'; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<nav class="nav-tab-wrapper" style="margin-bottom:16px">';
	foreach ( array( 'sites' => __( 'Starter sites', 'semrs-blocks' ), 'hf' => __( 'Headers & footers', 'semrs-blocks' ), 'pages' => __( 'Pages & sections', 'semrs-blocks' ) ) as $t => $label ) {
		echo '<a class="nav-tab' . ( $tab === $t ? ' nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=semrs-templates&tab=' . $t ) ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</nav>';
	if ( 'hf' === $tab ) {
		semrs_blocks_hf_tab();
		echo '</div>';
		return;
	}
	if ( 'pages' === $tab ) {
		semrs_blocks_pages_tab();
		echo '</div>';
		return;
	}
	echo '<div class="semrs-toolbar" role="search"><label class="screen-reader-text" for="semrs-tpl-search">' . esc_html__( 'Search templates', 'semrs-blocks' ) . '</label><input type="search" id="semrs-tpl-search" placeholder="' . esc_attr__( 'Search starter sites…', 'semrs-blocks' ) . '"><div class="semrs-chips" role="group" aria-label="' . esc_attr__( 'Filter', 'semrs-blocks' ) . '">';
	foreach ( array( 'all' => __( 'All', 'semrs-blocks' ), 'tier:free' => __( 'Free', 'semrs-blocks' ), 'tier:pro' => __( 'Pro', 'semrs-blocks' ), 'group:business' => __( 'Business', 'semrs-blocks' ), 'group:marketing' => __( 'Marketing', 'semrs-blocks' ), 'group:home-services' => __( 'Home services', 'semrs-blocks' ), 'group:health' => __( 'Health', 'semrs-blocks' ), 'group:travel' => __( 'Travel', 'semrs-blocks' ), 'group:education' => __( 'Education', 'semrs-blocks' ), 'group:technology' => __( 'Technology', 'semrs-blocks' ), 'group:blog' => __( 'Blog', 'semrs-blocks' ) ) as $f => $label ) {
		echo '<button type="button" class="semrs-chip' . ( 'all' === $f ? ' is-active' : '' ) . '" data-filter="' . esc_attr( $f ) . '">' . esc_html( $label ) . '</button>';
	}
	echo '</div></div><div class="semrs-block semrs-showcase" style="--sc-cols:3;margin:0">';
	foreach ( $sites as $id => $site ) {
		semrs_blocks_template_card( $id, $site, 'pro' === ( $site['tier'] ?? 'free' ) && ! defined( 'SEMRS_PRO_VERSION' ) );
	}
	if ( ! defined( 'SEMRS_PRO_VERSION' ) ) {
		foreach ( semrs_blocks_template_pro_teasers() as $id => $t ) {
			if ( ! isset( $sites[ $id ] ) ) {
				semrs_blocks_template_card( $id, array( 'title' => $t[0], 'tier' => 'pro', 'category' => $t[1], 'description' => $t[2] ), true );
			}
		}
	}
	echo '</div><p class="semrs-empty-msg" hidden>' . esc_html__( 'No starter sites match your search.', 'semrs-blocks' ) . '</p></div>';
}

/* ---------------------------------------------------------------------------
 * Header & footer presets (SEMRS theme header/footer builder)
 * ------------------------------------------------------------------------- */

/**
 * Header presets: id => [title, description, layout, extra theme mods].
 *
 * @return array
 */
function semrs_blocks_header_presets() {
	$empty = array( 'left' => array(), 'center' => array(), 'right' => array() );
	return apply_filters(
		'semrs_blocks_header_presets',
		array(
			'classic'     => array( __( 'Classic', 'semrs-blocks' ), __( 'Logo left; menu, button and search right.', 'semrs-blocks' ), array( 'top' => $empty, 'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'menu', 'button', 'search' ) ), 'bottom' => $empty ), array() ),
			'topbar'      => array( __( 'Top bar + classic', 'semrs-blocks' ), __( 'Phone and email above the header, social icons on the right.', 'semrs-blocks' ), array( 'top' => array( 'left' => array( 'contact' ), 'center' => array(), 'right' => array( 'social' ) ), 'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'menu', 'button', 'search' ) ), 'bottom' => $empty ), array() ),
			'centered'    => array( __( 'Centred logo', 'semrs-blocks' ), __( 'Menu left, logo in the middle, button right.', 'semrs-blocks' ), array( 'top' => $empty, 'main' => array( 'left' => array( 'menu' ), 'center' => array( 'logo' ), 'right' => array( 'search', 'button' ) ), 'bottom' => $empty ), array() ),
			'two-row'     => array( __( 'Two rows', 'semrs-blocks' ), __( 'Logo and contact on top, full menu on a second row.', 'semrs-blocks' ), array( 'top' => $empty, 'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'contact', 'button' ) ), 'bottom' => array( 'left' => array( 'menu' ), 'center' => array(), 'right' => array( 'search' ) ) ), array() ),
			'minimal'     => array( __( 'Minimal', 'semrs-blocks' ), __( 'Just the logo and menu: ideal for blogs.', 'semrs-blocks' ), array( 'top' => $empty, 'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'menu', 'search' ) ), 'bottom' => $empty ), array() ),
			'transparent' => array( __( 'Transparent hero', 'semrs-blocks' ), __( 'Classic layout floating over the first section on the home page.', 'semrs-blocks' ), array( 'top' => $empty, 'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'menu', 'button' ) ), 'bottom' => $empty ), array( 'transparent' => 'front', 'transparent_light' => false ) ),
		)
	);
}

/**
 * Footer presets: id => [title, description, layout].
 *
 * @return array
 */
function semrs_blocks_footer_presets() {
	return apply_filters(
		'semrs_blocks_footer_presets',
		array(
			'simple'   => array( __( 'Simple', 'semrs-blocks' ), __( 'Copyright left, social icons centre, footer menu right.', 'semrs-blocks' ), array( 'bottom' => array( 'left' => array( 'copyright' ), 'center' => array( 'social' ), 'right' => array( 'footer_menu' ) ) ) ),
			'centered' => array( __( 'Centred', 'semrs-blocks' ), __( 'Everything stacked in the middle.', 'semrs-blocks' ), array( 'bottom' => array( 'left' => array(), 'center' => array( 'social', 'footer_menu', 'copyright' ), 'right' => array() ) ) ),
			'contact'  => array( __( 'Contact', 'semrs-blocks' ), __( 'Phone and email left, copyright centre, social right.', 'semrs-blocks' ), array( 'bottom' => array( 'left' => array( 'contact' ), 'center' => array( 'copyright' ), 'right' => array( 'social' ) ) ) ),
		)
	);
}

/**
 * Tiny wireframe of a builder layout (no images needed).
 *
 * @param array  $layout Rows => zones => elements.
 * @param string $kind   header|footer.
 * @return string
 */
function semrs_blocks_hf_wireframe( $layout, $kind ) {
	$names = array( 'logo' => 'Logo', 'menu' => 'Menu', 'menu2' => 'Links', 'button' => 'Button', 'button2' => 'Button 2', 'search' => '⌕', 'social' => '● ● ●', 'contact' => '☎ ✉', 'html1' => 'Text', 'html2' => 'Text', 'html3' => 'Text', 'copyright' => '© Year', 'footer_menu' => 'Links' );
	$out   = '<div class="semrs-wf semrs-wf--' . esc_attr( $kind ) . '">';
	foreach ( $layout as $row => $zones ) {
		if ( ! array_filter( $zones ) ) {
			continue;
		}
		$out .= '<div class="semrs-wf__row semrs-wf__row--' . esc_attr( $row ) . '">';
		foreach ( array( 'left', 'center', 'right' ) as $z ) {
			$out .= '<span class="semrs-wf__zone semrs-wf__zone--' . $z . '">';
			foreach ( (array) ( $zones[ $z ] ?? array() ) as $el ) {
				$out .= '<i class="semrs-wf__el semrs-wf__el--' . esc_attr( $el ) . '">' . esc_html( $names[ $el ] ?? $el ) . '</i>';
			}
			$out .= '</span>';
		}
		$out .= '</div>';
	}
	return $out . '</div>';
}

/**
 * Apply a preset.
 */
function semrs_blocks_hf_apply() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-blocks' ) );
	}
	check_admin_referer( 'semrs_hf_apply' );
	$kind = isset( $_POST['kind'] ) && 'footer' === $_POST['kind'] ? 'footer' : 'header';
	$id   = isset( $_POST['preset'] ) ? sanitize_key( $_POST['preset'] ) : '';
	$all  = 'footer' === $kind ? semrs_blocks_footer_presets() : semrs_blocks_header_presets();
	if ( isset( $all[ $id ] ) && 'semrs' === get_template() ) {
		set_theme_mod( 'semrs_theme_' . ( 'footer' === $kind ? 'fb' : 'hb' ) . '_layout', wp_json_encode( $all[ $id ][2] ) );
		if ( 'header' === $kind ) {
			set_theme_mod( 'semrs_theme_transparent', 'off' );
			foreach ( (array) ( $all[ $id ][3] ?? array() ) as $mod => $value ) {
				set_theme_mod( 'semrs_theme_' . $mod, $value );
			}
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=semrs-templates&tab=hf&applied=' . rawurlencode( $id ) ) );
	exit;
}
add_action( 'admin_post_semrs_hf_apply', 'semrs_blocks_hf_apply' );

/**
 * Headers & footers tab.
 */
function semrs_blocks_hf_tab() {
	if ( 'semrs' !== get_template() ) {
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Header and footer presets work with the free SEMRS theme and its header/footer builder. Activate the SEMRS theme to use them.', 'semrs-blocks' ) . '</p></div>';
		return;
	}
	if ( isset( $_GET['applied'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Preset applied. Fine-tune it in Appearance → Customize → SEMRS Theme → Header builder / Footer builder.', 'semrs-blocks' ) . ' <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View site', 'semrs-blocks' ) . '</a></p></div>';
	}
	$current = array(
		'header' => (string) get_theme_mod( 'semrs_theme_hb_layout', '' ),
		'footer' => (string) get_theme_mod( 'semrs_theme_fb_layout', '' ),
	);
	foreach ( array( 'header' => array( __( 'Headers', 'semrs-blocks' ), semrs_blocks_header_presets() ), 'footer' => array( __( 'Footers', 'semrs-blocks' ), semrs_blocks_footer_presets() ) ) as $kind => $set ) {
		echo '<h2>' . esc_html( $set[0] ) . '</h2><div class="semrs-grid">';
		foreach ( $set[1] as $id => $p ) {
			$active = wp_json_encode( $p[2] ) === $current[ $kind ];
			echo '<div class="semrs-card' . ( $active ? ' is-on' : '' ) . '">' . semrs_blocks_hf_wireframe( $p[2], $kind ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
			echo '<h3>' . esc_html( $p[0] ) . '</h3><p>' . esc_html( $p[1] ) . '</p><div class="semrs-card__foot">';
			if ( $active ) {
				echo '<span class="button disabled">' . esc_html__( 'In use', 'semrs-blocks' ) . '</span>';
			} else {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="semrs_hf_apply"><input type="hidden" name="kind" value="' . esc_attr( $kind ) . '"><input type="hidden" name="preset" value="' . esc_attr( $id ) . '">';
				wp_nonce_field( 'semrs_hf_apply' );
				echo '<button type="submit" class="button button-primary">' . esc_html__( 'Apply', 'semrs-blocks' ) . '</button></form>';
			}
			echo '</div></div>';
		}
		echo '</div>';
	}
}

/**
 * Pages & sections tab: what is available in the editor.
 */
function semrs_blocks_pages_tab() {
	$pages = 0;
	foreach ( semrs_blocks_template_sites() as $site ) {
		if ( 'pro' === ( $site['tier'] ?? 'free' ) && ! defined( 'SEMRS_PRO_VERSION' ) ) {
			continue;
		}
		foreach ( $site['pages'] as $page ) {
			$pages += empty( $page['sections'] ) ? 0 : 1;
		}
	}
	echo '<div class="semrs-card" style="max-width:820px"><h2 style="margin-top:0">' . esc_html__( 'Insert single pages and sections from the editor', 'semrs-blocks' ) . '</h2>';
	/* translators: %d: number of page templates */
	echo '<p>' . esc_html( sprintf( __( '%d full-page templates and 12 sections are ready in the block editor.', 'semrs-blocks' ), $pages ) ) . '</p><ol>';
	echo '<li>' . esc_html__( 'Open any page in the editor (or create a new one).', 'semrs-blocks' ) . '</li>';
	echo '<li>' . esc_html__( 'Click the + button (top left) → Patterns.', 'semrs-blocks' ) . '</li>';
	echo '<li>' . esc_html__( 'Choose "SEMRS full pages" for a complete page (home, about, services, contact, admissions…) or "SEMRS sections" for single blocks (hero, services, pricing, FAQ, contact, opening hours…).', 'semrs-blocks' ) . '</li>';
	echo '<li>' . esc_html__( 'Click a design to insert it, then replace the example text and images with your own.', 'semrs-blocks' ) . '</li></ol>';
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=page' ) ) . '">' . esc_html__( 'Create a page', 'semrs-blocks' ) . '</a></p></div>';
}
