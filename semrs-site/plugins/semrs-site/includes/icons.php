<?php
/**
 * Site icons: two-tone line icons (brand blue with an orange detail) for
 * blog categories, services, free tools and the main pages. They appear
 * before the name in dropdown menus, category lists and tool cards, and
 * before the heading of the category or page itself. A category's icon
 * can be changed under Posts → Categories → Edit → "Icon".
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon key => [label, inner SVG] (24×24, stroke 2). Paths with class "a" take the accent colour.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function semrs_site_icons() {
	return array(
		'megaphone'  => array( __( 'Megaphone', 'semrs-site' ), '<path d="M3 10v4a1 1 0 0 0 1 1h3l6 4V5L7 9H4a1 1 0 0 0-1 1z"/><path class="a" d="M17 9a4 4 0 0 1 0 6M19.5 6.5a7.5 7.5 0 0 1 0 11"/>' ),
		'search'     => array( __( 'Search', 'semrs-site' ), '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/><path class="a" d="M10.5 7.2l.9 2.4 2.4.9-2.4.9-.9 2.4-.9-2.4-2.4-.9 2.4-.9z"/>' ),
		'map-pin'    => array( __( 'Map pin', 'semrs-site' ), '<path d="M12 21s-6.5-6.2-6.5-11a6.5 6.5 0 0 1 13 0c0 4.8-6.5 11-6.5 11z"/><circle class="a" cx="12" cy="10" r="2.3"/>' ),
		'click'      => array( __( 'Click', 'semrs-site' ), '<path d="M9.5 9.5l10.5 4-4.6 1.4-1.4 4.6z"/><path class="a" d="M4 4l2 2M9 2.5V5M2.5 9H5M4.5 13.5L6 12"/>' ),
		'browser'    => array( __( 'Browser', 'semrs-site' ), '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path class="a" d="M10 12.5l-2.5 2.5 2.5 2.5M14 12.5l2.5 2.5-2.5 2.5"/>' ),
		'chat'       => array( __( 'Chat', 'semrs-site' ), '<path d="M4 4h10a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-4 3.5V14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path class="a" d="M19 8h1a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2v2.5L17 18h-4"/>' ),
		'chip'       => array( __( 'AI chip', 'semrs-site' ), '<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/><path class="a" d="M12 8.8l.8 2.4 2.4.8-2.4.8-.8 2.4-.8-2.4-2.4-.8 2.4-.8z"/>' ),
		'growth'     => array( __( 'Growth', 'semrs-site' ), '<path d="M3 20h18M5 16l5-5 3 3 6-6"/><path class="a" d="M14.5 8H19v4.5"/>' ),
		'bank'       => array( __( 'Bank', 'semrs-site' ), '<path d="M4 20h16M6 11v6M10 11v6M14 11v6M18 11v6"/><path class="a" d="M3 9L12 4l9 5z"/>' ),
		'scales'     => array( __( 'Scales', 'semrs-site' ), '<path d="M12 4v16M8 20h8M5 7h14"/><path class="a" d="M5 7l-2.5 6a2.5 2.5 0 0 0 5 0zM19 7l-2.5 6a2.5 2.5 0 0 0 5 0z"/>' ),
		'wallet'     => array( __( 'Wallet', 'semrs-site' ), '<path d="M17 4H6a2 2 0 0 0-2 2v13a1 1 0 0 0 1 1h13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H4"/><path class="a" d="M20 11h-4a2 2 0 0 0 0 4h4"/>' ),
		'shield'     => array( __( 'Shield', 'semrs-site' ), '<path d="M12 3l7.5 3v6c0 4.5-3.2 7.8-7.5 9-4.3-1.2-7.5-4.5-7.5-9V6z"/><path class="a" d="M9 12l2 2 4-4"/>' ),
		'briefcase'  => array( __( 'Briefcase', 'semrs-site' ), '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path class="a" d="M3 12.5h18"/>' ),
		'pen'        => array( __( 'Pen', 'semrs-site' ), '<path d="M4 20l1-4L16 5a2.1 2.1 0 0 1 3 3L8 19z"/><path class="a" d="M14 7l3 3M13 20h7"/>' ),
		'document'   => array( __( 'Document', 'semrs-site' ), '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path class="a" d="M9 13h6M9 17h4"/>' ),
		'users'      => array( __( 'People', 'semrs-site' ), '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path class="a" d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14a6.5 6.5 0 0 1 3.5 6"/>' ),
		'layers'     => array( __( 'Layers', 'semrs-site' ), '<path d="M12 3l9 5-9 5-9-5z"/><path class="a" d="M3 12.5l9 5 9-5M3 16.5l9 5 9-5"/>' ),
		'target'     => array( __( 'Target', 'semrs-site' ), '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle class="a" cx="12" cy="12" r="1.5"/>' ),
		'grid'       => array( __( 'Tools', 'semrs-site' ), '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect class="a" x="13.5" y="13.5" width="7" height="7" rx="1.5"/>' ),
		'dashboard'  => array( __( 'Dashboard', 'semrs-site' ), '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/><path class="a" d="M12.5 16l2-2.5 2 1.5 2-3"/>' ),
		'lightbulb'  => array( __( 'Idea', 'semrs-site' ), '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2v.1h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"/><path class="a" d="M12 7.5v4"/>' ),
		'serp'       => array( __( 'Search result', 'semrs-site' ), '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h7"/><path class="a" d="M7 13h10M7 16.5h7"/>' ),
		'type'       => array( __( 'Text', 'semrs-site' ), '<path d="M4 6V4h12v2M10 4v16M7.5 20h5"/><path class="a" d="M15 12h6M15 16h6M15 20h4"/>' ),
		'code'       => array( __( 'Code', 'semrs-site' ), '<path d="M8 7l-5 5 5 5M16 7l5 5-5 5"/><path class="a" d="M13.5 5l-3 14"/>' ),
		'link'       => array( __( 'Link', 'semrs-site' ), '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path class="a" d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>' ),
		'image'      => array( __( 'Image', 'semrs-site' ), '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 17l5-5 4 4 3-3 6 6"/><circle class="a" cx="15.5" cy="8.5" r="1.8"/>' ),
		'tag'        => array( __( 'Tag', 'semrs-site' ), '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle class="a" cx="7.5" cy="7.5" r="1.6"/>' ),
		'gear'       => array( __( 'Settings', 'semrs-site' ), '<path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/><circle class="a" cx="12" cy="12" r="3.5"/>' ),
		'robot'      => array( __( 'Robot', 'semrs-site' ), '<rect x="4" y="8" width="16" height="12" rx="2"/><path d="M12 4v4M2 13v3M22 13v3"/><path class="a" d="M9 13h.01M15 13h.01M12 4h.01M10 17h4"/>' ),
		'redirect'   => array( __( 'Redirect', 'semrs-site' ), '<path d="M4 20v-7a4 4 0 0 1 4-4h11"/><path class="a" d="M15 5l4 4-4 4"/>' ),
		'star'       => array( __( 'Star', 'semrs-site' ), '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/><path class="a" d="M12 8.5v5"/>' ),
		'calculator' => array( __( 'Calculator', 'semrs-site' ), '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8.5 7h7"/><path class="a" d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01M8.5 15h.01M12 15h.01M15.5 15h.01M8.5 18h.01M12 18h.01M15.5 18h.01"/>' ),
		'puzzle'     => array( __( 'Plugin', 'semrs-site' ), '<path d="M10 6V4a2 2 0 0 1 4 0v2h4a1 1 0 0 1 1 1v4h-2a2 2 0 0 0 0 4h2v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-4h2a2 2 0 0 0 0-4H5V7a1 1 0 0 1 1-1z"/><path class="a" d="M10 6V4a2 2 0 0 1 4 0v2"/>' ),
		'route'      => array( __( 'Process', 'semrs-site' ), '<circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path class="a" d="M8.5 18H15a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h6.5"/>' ),
		'user-plus'  => array( __( 'Sign up', 'semrs-site' ), '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path class="a" d="M19 8v6M16 11h6"/>' ),
		'mail'       => array( __( 'Mail', 'semrs-site' ), '<rect x="3" y="5" width="18" height="14" rx="2"/><path class="a" d="M3.5 6.5L12 13l8.5-6.5"/>' ),
		'clipboard'  => array( __( 'Review', 'semrs-site' ), '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path class="a" d="M9 13l2 2 4-4"/>' ),
		'book'       => array( __( 'Book', 'semrs-site' ), '<path d="M4 19V5a2 2 0 0 1 2-2h14v15H6a2 2 0 0 0-2 2 2 2 0 0 0 2 2h14"/><path class="a" d="M9 7.5h7"/>' ),
		'lock'       => array( __( 'Lock', 'semrs-site' ), '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><circle class="a" cx="12" cy="15.5" r="1.5"/>' ),
		'folder'     => array( __( 'Folder', 'semrs-site' ), '<path d="M3 6a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path class="a" d="M3 10h18"/>' ),
	);
}

/**
 * Site path => icon key for services, tools and pages.
 *
 * @return array<string, string>
 */
function semrs_site_page_icon_map() {
	return array(
		'/services/'                               => 'briefcase',
		'/services/seo/'                           => 'search',
		'/services/seo-content-writing/'           => 'pen',
		'/services/content-marketing/'             => 'document',
		'/services/social-media-management/'       => 'users',
		'/services/content-management/'            => 'layers',
		'/services/web-design/'                    => 'browser',
		'/services/paid-ads-lead-generation/'      => 'target',
		'/tools/'                                  => 'grid',
		'/tools/keyword-ideas-generator/'          => 'lightbulb',
		'/tools/serp-snippet-preview/'             => 'serp',
		'/tools/word-counter/'                     => 'type',
		'/tools/schema-markup-generator/'          => 'code',
		'/tools/utm-builder/'                      => 'link',
		'/tools/image-compressor/'                 => 'image',
		'/tools/meta-serp/'                        => 'serp',
		'/tools/meta-description-checker/'         => 'tag',
		'/tools/technical-seo/'                    => 'gear',
		'/tools/robots-txt-generator/'             => 'robot',
		'/tools/redirect-checker/'                 => 'redirect',
		'/tools/schema/'                           => 'code',
		'/tools/local-business-schema-generator/'  => 'map-pin',
		'/tools/content-keywords/'                 => 'document',
		'/tools/local-seo/'                        => 'map-pin',
		'/tools/google-review-link-generator/'     => 'star',
		'/tools/ai-search/'                        => 'chip',
		'/tools/llms-txt-generator/'               => 'chip',
		'/tools/marketing/'                        => 'megaphone',
		'/tools/website/'                          => 'browser',
		'/tools/calculators/'                      => 'calculator',
		'/tools/seo-roi-calculator/'               => 'growth',
		'/semrs-os/'                               => 'dashboard',
		'/semrs-assets/'                           => 'puzzle',
		'/pricing/'                                => 'tag',
		'/blog/'                                   => 'book',
		'/about/'                                  => 'users',
		'/portfolio/'                              => 'image',
		'/how-we-work/'                            => 'route',
		'/registration-form/'                      => 'user-plus',
		'/feed-back-form/'                         => 'chat',
		'/contact-us/'                             => 'mail',
		'/get-a-quote/'                            => 'clipboard',
		'/privacy-policy/'                         => 'lock',
		'/terms-and-conditions/'                   => 'document',
	);
}

/**
 * Icon key for a URL on this site, or ''.
 *
 * @param string $url URL or path.
 * @return string
 */
function semrs_site_url_icon_key( $url ) {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) {
		return '';
	}
	$home = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}
	$path = '/' . trim( $path, '/' ) . '/';
	return semrs_site_page_icon_map()[ $path ] ?? '';
}

/**
 * The icon key for a category: its own choice, else the default for its slug.
 *
 * @param int $term_id Category ID.
 * @return string
 */
function semrs_site_category_icon_key( $term_id ) {
	$key = (string) get_term_meta( $term_id, 'semrs_icon', true );
	if ( $key && isset( semrs_site_icons()[ $key ] ) ) {
		return $key;
	}
	$term     = get_term( $term_id, 'category' );
	$defaults = array(
		'digital-marketing'           => 'megaphone',
		'seo'                         => 'search',
		'local-seo'                   => 'map-pin',
		'ppc'                         => 'click',
		'web-design'                  => 'browser',
		'content-social'              => 'chat',
		'ai-marketing'                => 'chip',
		'marketing-costs-roi'         => 'growth',
		'finance-insurance-marketing' => 'bank',
		'legal-healthcare-marketing'  => 'scales',
		'business-finance'            => 'wallet',
		'business-insurance'          => 'shield',
	);
	return ( $term && ! is_wp_error( $term ) && isset( $defaults[ $term->slug ] ) ) ? $defaults[ $term->slug ] : 'folder';
}

/**
 * Inline SVG for an icon key (decorative: the name is always next to it).
 *
 * @param string $key Icon key.
 * @return string
 */
function semrs_site_icon( $key ) {
	$icons = semrs_site_icons();
	if ( ! isset( $icons[ $key ] ) ) {
		return '';
	}
	return '<svg class="semrs-cat-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $key ][1] . '</svg>';
}

/**
 * Inline SVG for a category.
 *
 * @param int $term_id Category ID.
 * @return string
 */
function semrs_site_category_icon( $term_id ) {
	return semrs_site_icon( semrs_site_category_icon_key( $term_id ) );
}

/**
 * Icon key for the page being viewed (categories and mapped pages), or ''.
 *
 * @return string
 */
function semrs_site_current_icon_key() {
	if ( is_category() ) {
		return semrs_site_category_icon_key( get_queried_object_id() );
	}
	if ( is_page() && ! is_front_page() ) {
		return semrs_site_url_icon_key( get_permalink( get_queried_object_id() ) );
	}
	return '';
}

add_action(
	'init',
	function () {
		register_term_meta(
			'category',
			'semrs_icon',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => function ( $allowed, $key, $term_id ) {
					return current_user_can( 'edit_term', $term_id );
				},
			)
		);
	}
);

// Menus: categories anywhere, services, tools and pages in dropdowns (top bar and footer stay text only).
add_filter(
	'nav_menu_item_title',
	function ( $title, $item, $args, $depth ) {
		if ( 'taxonomy' === $item->type && 'category' === $item->object ) {
			$icon = semrs_site_category_icon( (int) $item->object_id );
		} else {
			$icon = $depth > 0 ? semrs_site_icon( semrs_site_url_icon_key( $item->url ) ) : '';
		}
		return $icon ? $icon . '<span class="semrs-cat-name">' . $title . '</span>' : $title;
	},
	10,
	4
);

// Category lists: the Categories widget and block (not the dropdown version).
add_filter(
	'wp_list_categories',
	function ( $html ) {
		return preg_replace_callback(
			'#(<li class="cat-item cat-item-(\d+)[^"]*">\s*<a[^>]*>)#',
			function ( $m ) {
				return $m[1] . semrs_site_category_icon( (int) $m[2] );
			},
			$html
		);
	}
);

// Category page heading.
add_filter(
	'get_the_archive_title',
	function ( $title ) {
		return ( is_category() && ! in_the_loop() && did_action( 'wp_body_open' ) ) ? semrs_site_category_icon( get_queried_object_id() ) . '<span class="semrs-cat-name">' . $title . '</span>' : $title;
	}
);

/**
 * Whether a page's own content holds its H1 (hero sections, full-width
 * templates); the icon then goes there instead of the theme title.
 *
 * @param int $id Page ID.
 * @return bool
 */
function semrs_site_content_has_h1( $id ) {
	$raw = (string) get_post_field( 'post_content', $id );
	return false !== strpos( $raw, '<h1' ) || false !== strpos( $raw, 'wp:semrs/fancy-heading' );
}

// Page heading printed by the theme (pages that show their title). Only the
// first title call in the loop is the heading; later ones (share links etc.) stay plain.
add_filter(
	'the_title',
	function ( $title, $id = 0 ) {
		static $done = false;
		if ( $done || ! is_page() || ! in_the_loop() || ! is_main_query() || (int) $id !== get_queried_object_id() || ! did_action( 'wp_body_open' ) || semrs_site_content_has_h1( $id ) ) {
			return $title;
		}
		$done = true;
		$icon = semrs_site_icon( semrs_site_current_icon_key() );
		return $icon ? $icon . '<span class="semrs-cat-name">' . $title . '</span>' : $title;
	},
	10,
	2
);

// Pages that hide the theme title and start with their own H1 (hero blocks); tool cards.
add_filter(
	'the_content',
	function ( $html ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $html;
		}
		if ( is_page() && semrs_site_content_has_h1( get_the_ID() ) ) {
			$icon = semrs_site_icon( semrs_site_current_icon_key() );
			if ( $icon ) {
				$html = preg_replace( '#(<h1\b[^>]*>)#', '$1' . str_replace( 'semrs-cat-icon', 'semrs-cat-icon semrs-page-icon', $icon ), $html, 1 );
			}
		}
		if ( false !== strpos( $html, 'semrs-tools-card' ) ) {
			$html = preg_replace_callback(
				'#(<a class="semrs-tools-card" href="([^"]+)">)#',
				function ( $m ) {
					return $m[1] . semrs_site_icon( semrs_site_url_icon_key( html_entity_decode( $m[2] ) ) );
				},
				$html
			);
		}
		return $html;
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$css = '.semrs-cat-icon{flex:none;width:1.15em;height:1.15em;margin-right:.5em;vertical-align:-.2em;color:var(--sp-primary,#0b2fd1)}.semrs-cat-icon .a{stroke:var(--sp-accent,#f7941d)}'
			. '.main-navigation a:has(>.semrs-cat-icon){display:flex;align-items:center}'
			. '.wp-block-categories .cat-item>a,.widget_categories .cat-item>a{display:inline-flex;align-items:center}'
			. '.page-title:has(>.semrs-cat-icon),.entry-title:has(>.semrs-cat-icon){display:flex;align-items:center;gap:.15em}'
			. '.page-title>.semrs-cat-icon,.entry-title>.semrs-cat-icon,.semrs-page-icon{width:1.5em;height:1.5em;padding:.28em;background:var(--sp-accent-soft,#fef0dd);border-radius:var(--sp-radius-sm,8px);box-sizing:border-box;margin-right:.35em}'
			. '.semrs-page-icon{width:1.15em;height:1.15em;padding:.2em;vertical-align:-.15em;margin-right:.3em}'
			. '.semrs-tools-card>.semrs-cat-icon{display:block;width:44px;height:44px;padding:10px;margin:0 0 .75rem;background:var(--sp-accent-soft,#fef0dd);border-radius:var(--sp-radius-sm,8px);box-sizing:border-box}'
			. 'html[data-theme="dark"] .semrs-cat-icon{color:#8fb0ff}html[data-theme="dark"] .page-title>.semrs-cat-icon,html[data-theme="dark"] .entry-title>.semrs-cat-icon,html[data-theme="dark"] .semrs-page-icon,html[data-theme="dark"] .semrs-tools-card>.semrs-cat-icon{background:rgba(143,176,255,.14)}';
		wp_register_style( 'semrs-site-icons', false, array(), SEMRS_SITE_VERSION );
		wp_enqueue_style( 'semrs-site-icons' );
		wp_add_inline_style( 'semrs-site-icons', $css );
	}
);

if ( ! is_admin() ) {
	return;
}

/**
 * Icon picker on the category edit screen.
 *
 * @param WP_Term $term Category.
 */
function semrs_site_category_icon_field( $term ) {
	$current = semrs_site_category_icon_key( $term->term_id );
	wp_nonce_field( 'semrs_cat_icon', 'semrs_cat_icon_nonce' );
	echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Icon', 'semrs-site' ) . '</th><td><fieldset style="display:flex;flex-wrap:wrap;gap:8px">';
	foreach ( semrs_site_icons() as $key => $icon ) {
		printf(
			'<label title="%1$s" style="display:inline-flex;align-items:center;gap:4px;padding:6px 8px;border:1px solid #c3c4c7;border-radius:6px;cursor:pointer"><input type="radio" name="semrs_icon" value="%2$s"%3$s> <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0b2fd1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><style>.a{stroke:#f7941d}</style>%4$s</svg></label>',
			esc_attr( $icon[0] ),
			esc_attr( $key ),
			checked( $current, $key, false ),
			$icon[1] // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
		);
	}
	echo '</fieldset><p class="description">' . esc_html__( 'Shown next to the category name in menus, category lists and on the category page.', 'semrs-site' ) . '</p></td></tr>';
}
add_action( 'category_edit_form_fields', 'semrs_site_category_icon_field' );

/**
 * Save the icon.
 *
 * @param int $term_id Category ID.
 */
function semrs_site_category_icon_save( $term_id ) {
	if ( ! isset( $_POST['semrs_cat_icon_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_cat_icon_nonce'] ) ), 'semrs_cat_icon' ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	$key = isset( $_POST['semrs_icon'] ) ? sanitize_key( wp_unslash( $_POST['semrs_icon'] ) ) : '';
	if ( isset( semrs_site_icons()[ $key ] ) ) {
		update_term_meta( $term_id, 'semrs_icon', $key );
	}
}
add_action( 'edited_category', 'semrs_site_category_icon_save' );
