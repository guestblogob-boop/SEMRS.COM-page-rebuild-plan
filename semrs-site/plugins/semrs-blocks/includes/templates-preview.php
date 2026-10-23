<?php
/**
 * Live preview of a starter site before it is imported: every page, with the
 * template's own header, menu, colours, example posts and footer columns.
 * Nothing is created or saved. Only users who can import (edit_theme_options)
 * see it.
 *
 * URL: /?semrs_tpl_preview={site}&tpl_page={page}
 * Viewer: SEMRS → Templates → Preview site (desktop / tablet / phone).
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preview address of one template page.
 *
 * @param string $sid Site id.
 * @param string $pid Page id.
 * @return string
 */
function semrs_blocks_template_preview_url( $sid, $pid = 'home' ) {
	return add_query_arg( array( 'semrs_tpl_preview' => $sid, 'tpl_page' => $pid ), home_url( '/' ) );
}

/**
 * The template being previewed in this request, or null.
 *
 * @return array|null sid, pid, site, owner, images
 */
function semrs_blocks_template_preview_context() {
	static $ctx = false;
	if ( false !== $ctx ) {
		return $ctx;
	}
	$ctx = null;
	if ( empty( $_GET['semrs_tpl_preview'] ) || ! did_action( 'init' ) && ! doing_action( 'init' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only preview.
		$ctx = false;
		return null;
	}
	/**
	 * Who may see template previews (default: users who can import them).
	 *
	 * @param bool $allowed Allowed.
	 */
	if ( ! apply_filters( 'semrs_blocks_template_preview_allowed', current_user_can( 'edit_theme_options' ) ) ) {
		return null;
	}
	$sid   = sanitize_key( wp_unslash( $_GET['semrs_tpl_preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$sites = semrs_blocks_template_sites();
	if ( empty( $sites[ $sid ] ) || ( 'pro' === ( $sites[ $sid ]['tier'] ?? 'free' ) && ! defined( 'SEMRS_PRO_VERSION' ) ) ) {
		return null;
	}
	$site = $sites[ $sid ];
	$pid  = isset( $_GET['tpl_page'] ) ? sanitize_key( wp_unslash( $_GET['tpl_page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( empty( $site['pages'][ $pid ] ) ) {
		$pid = (string) key( $site['pages'] );
	}
	$ctx = array(
		'sid'    => $sid,
		'pid'    => $pid,
		'site'   => $site,
		'owner'  => array(
			'name'    => $site['demo_name'] ?? $site['title'],
			'phone'   => '+1 555 010 0200',
			'email'   => 'hello@example.com',
			'city'    => 'Austin',
			'country' => 'US',
		),
		'images' => semrs_blocks_template_image_urls( $site ),
	);
	return $ctx;
}

/**
 * Point the template's own links (/contact/, /services/ …) at preview pages.
 *
 * @param string $html Markup.
 * @return string
 */
function semrs_blocks_template_preview_links( $html ) {
	$ctx = semrs_blocks_template_preview_context();
	if ( ! $ctx ) {
		return $html;
	}
	$map = array();
	foreach ( $ctx['site']['pages'] as $pid => $page ) {
		$map[ 'href="/' . $pid . '/"' ] = 'href="' . esc_url( semrs_blocks_template_preview_url( $ctx['sid'], $pid ) ) . '"';
		if ( ! empty( $page['front'] ) ) {
			$map['href="/"'] = 'href="' . esc_url( semrs_blocks_template_preview_url( $ctx['sid'], $pid ) ) . '"';
		}
	}
	return strtr( $html, $map );
}

/**
 * Example posts as cards (the posts block and the posts slider), so the
 * preview shows the photos and articles the import adds.
 *
 * @param string $html  Block output.
 * @param array  $block Parsed block.
 * @return string
 */
function semrs_blocks_template_preview_posts( $html, $block ) {
	$name = $block['blockName'] ?? '';
	if ( 'semrs/posts' !== $name && ! ( 'semrs/slider' === $name && 'posts' === ( $block['attrs']['source'] ?? '' ) ) ) {
		return $html;
	}
	$ctx = semrs_blocks_template_preview_context();
	if ( ! $ctx || empty( $ctx['site']['posts'] ) ) {
		return $html;
	}
	$blog = semrs_blocks_template_preview_url( $ctx['sid'], isset( $ctx['site']['pages']['blog'] ) ? 'blog' : $ctx['pid'] );
	$out  = '<div class="semrs-posts semrs-posts--grid" style="--semrs-cols:3;--semrs-ratio:3/2">';
	$i    = 0;
	foreach ( $ctx['site']['posts'] as $post ) {
		$date = wp_date( get_option( 'date_format' ), time() - ( ++$i * 6 * DAY_IN_SECONDS ) );
		$out .= '<article class="semrs-post"><a class="semrs-post__media" href="' . esc_url( $blog ) . '" tabindex="-1" aria-hidden="true"><img src="' . esc_url( $ctx['images'][ $post['image'] ] ?? '' ) . '" alt="" loading="lazy" width="720" height="480"></a><div class="semrs-post__body">';
		$out .= '<div class="semrs-post__meta"><span class="semrs-post__cat">' . esc_html( $post['cat'] ) . '</span> · <time>' . esc_html( $date ) . '</time></div>';
		$out .= '<h3 class="semrs-post__title"><a href="' . esc_url( $blog ) . '">' . esc_html( $post['title'] ) . '</a></h3>';
		$out .= '<p class="semrs-post__excerpt">' . esc_html( wp_trim_words( $post['excerpt'], 20, '…' ) ) . '</p>';
		$out .= '<a class="semrs-post__more" href="' . esc_url( $blog ) . '">' . esc_html__( 'Read more', 'semrs-blocks' ) . ' →</a></div></article>';
	}
	return $out . '</div>';
}

/**
 * The template's menu in the primary location; other menus are hidden.
 *
 * @param string|null $out  Menu HTML.
 * @param stdClass    $args wp_nav_menu() arguments.
 * @return string|null
 */
function semrs_blocks_template_preview_menu( $out, $args ) {
	$ctx = semrs_blocks_template_preview_context();
	if ( ! $ctx ) {
		return $out;
	}
	if ( ! in_array( $args->theme_location, array( 'primary', 'footer' ), true ) ) {
		return '';
	}
	$footer = 'footer' === $args->theme_location;
	$items  = '';
	$pages  = $ctx['site']['pages'];
	if ( ! $footer && isset( $ctx['site']['menu'] ) ) {
		$pages = array_intersect_key( $pages, array_flip( (array) $ctx['site']['menu'] ) ); // The template's own menu, in its order, as the import builds it.
		$pages = array_merge( array_intersect_key( array_flip( (array) $ctx['site']['menu'] ), $pages ), $pages );
	}
	foreach ( $pages as $pid => $page ) {
		if ( ! empty( $page['front'] ) || $footer !== ! empty( $page['legal'] ) ) {
			continue;
		}
		$current = $pid === $ctx['pid'];
		$items  .= '<li class="menu-item' . ( $current ? ' current-menu-item' : '' ) . '"><a href="' . esc_url( semrs_blocks_template_preview_url( $ctx['sid'], $pid ) ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>' . esc_html( strtr( $page['title'], array( '{{name}}' => $ctx['owner']['name'] ) ) ) . '</a></li>';
	}
	$menu = sprintf( $args->items_wrap ? $args->items_wrap : '<ul id="%1$s" class="%2$s">%3$s</ul>', esc_attr( $args->menu_id ), esc_attr( $args->menu_class ), $items );
	return $footer ? '<nav aria-label="' . esc_attr__( 'Footer', 'semrs-blocks' ) . '">' . $menu . '</nav>' : $menu;
}

/**
 * Footer columns: widget ids in the footer areas…
 *
 * @param array $sidebars Sidebar => widget ids.
 * @return array
 */
function semrs_blocks_template_preview_sidebars( $sidebars ) {
	if ( ! semrs_blocks_template_preview_context() ) {
		return $sidebars;
	}
	$sidebars['sidebar-1'] = array();
	for ( $i = 1; $i <= 4; $i++ ) {
		$sidebars[ 'footer-' . $i ] = array( 'block-' . ( 9900 + $i ) );
	}
	return $sidebars;
}

/**
 * …and their block content.
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function semrs_blocks_template_preview_widgets( $value ) {
	$ctx = semrs_blocks_template_preview_context();
	if ( ! $ctx ) {
		return $value;
	}
	$links = array();
	foreach ( $ctx['site']['pages'] as $pid => $page ) {
		if ( empty( $page['front'] ) && empty( $page['legal'] ) ) {
			$links[ $page['title'] ] = semrs_blocks_template_preview_url( $ctx['sid'], $pid );
		}
	}
	$blog  = semrs_blocks_template_preview_url( $ctx['sid'], 'blog' );
	$posts = array_fill_keys( wp_list_pluck( (array) ( $ctx['site']['posts'] ?? array() ), 'title' ), $blog );
	$svc   = isset( $ctx['site']['pages']['programs'] ) ? 'programs' : 'services';
	$out   = array( '_multiwidget' => 1 );
	foreach ( semrs_blocks_template_footer_blocks( $ctx['site'], $ctx['owner'], $links, $posts, semrs_blocks_template_preview_url( $ctx['sid'], $svc ) ) as $i => $html ) {
		$out[ 9901 + $i ] = array( 'content' => $html );
	}
	return $out;
}

/**
 * Widget filters must be in place before widgets are registered (init).
 */
function semrs_blocks_template_preview_early() {
	if ( empty( $_GET['semrs_tpl_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	add_filter( 'sidebars_widgets', 'semrs_blocks_template_preview_sidebars', 99 );
	add_filter( 'pre_option_widget_block', 'semrs_blocks_template_preview_widgets' );
}
add_action( 'after_setup_theme', 'semrs_blocks_template_preview_early', 99 );

/**
 * Render the preview page with the active theme's header and footer.
 */
function semrs_blocks_template_preview_render() {
	$ctx = semrs_blocks_template_preview_context();
	if ( ! $ctx ) {
		return;
	}
	$site = $ctx['site'];
	$page = $site['pages'][ $ctx['pid'] ];

	foreach ( semrs_blocks_template_theme_mods( $site, $ctx['owner'] ) as $mod => $value ) {
		if ( 'cta_url' === $mod ) {
			$value = preg_replace( '#^href="|"$#', '', semrs_blocks_template_preview_links( 'href="' . $value . '"' ) );
		}
		add_filter(
			'theme_mod_semrs_theme_' . $mod,
			function () use ( $value ) {
				return $value;
			}
		);
	}
	$name = $ctx['owner']['name'];
	add_filter( 'theme_mod_custom_logo', '__return_false' );
	add_filter(
		'theme_mod_semrs_theme_dark_mode',
		function () {
			return 'off'; // The template's own colours, not this site's dark mode.
		}
	);
	add_filter( 'theme_mod_semrs_theme_footer_note', '__return_empty_string' );
	add_filter( 'theme_mod_semrs_theme_html3', '__return_empty_string' );
	if ( function_exists( 'semrs_theme_social_networks' ) ) {
		foreach ( array_keys( semrs_theme_social_networks() ) as $net ) {
			add_filter( 'theme_mod_semrs_theme_social_' . $net, '__return_empty_string' ); // The site's own profiles are not part of the template.
		}
	}
	add_filter( 'theme_mod_semrs_theme_action_bar', 'local' === ( $site['kind'] ?? '' ) ? '__return_true' : '__return_false' );
	add_filter(
		'pre_option_blogname',
		function () use ( $name ) {
			return $name;
		}
	);
	add_filter( 'pre_option_blogdescription', '__return_empty_string' );
	$title = strtr( $page['title'], array( '{{name}}' => $name ) ) . ' – ' . $name;
	add_filter(
		'pre_get_document_title',
		function () use ( $title ) {
			return $title;
		},
		999
	);
	add_filter( 'show_admin_bar', '__return_false' );
	add_filter( 'pre_wp_nav_menu', 'semrs_blocks_template_preview_menu', 10, 2 );
	add_filter(
		'has_nav_menu',
		function ( $has, $location ) {
			return in_array( $location, array( 'primary', 'footer' ), true );
		},
		10,
		2
	);
	add_filter(
		'semrs_theme_layout',
		function () {
			return 'none';
		}
	);
	add_filter(
		'body_class',
		function ( $classes ) {
			$classes[] = 'sp-full-width';
			$classes[] = 'semrs-tpl-preview';
			return $classes;
		}
	);
	add_filter( 'render_block', 'semrs_blocks_template_preview_posts', 10, 2 );
	add_filter( 'wp_robots', 'wp_robots_no_robots' );
	remove_action( 'semrs_content_top', 'semrs_theme_breadcrumbs', 5 );
	remove_action( 'wp_footer', 'semrs_conversion_widgets', 6 ); // A child theme's own call / WhatsApp buttons belong to this site, not the template.

	if ( ! empty( $page['posts_page'] ) ) {
		$page['sections'] = array( 'page-header', 'posts-grid' );
	}
	status_header( 200 );
	nocache_headers();
	get_header();
	echo '<main id="primary" class="sp-main"><article class="entry page"><div class="entry-content">';
	echo semrs_blocks_template_preview_links( do_blocks( semrs_blocks_template_page_html( $site, $page, $ctx['owner'], $ctx['images'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- rendered blocks.
	echo '</div></article></main>';
	get_footer();
	exit;
}
add_action( 'template_redirect', 'semrs_blocks_template_preview_render', 0 );

/**
 * Admin viewer: page tabs, device sizes and the live preview in a frame.
 *
 * @param string $sid  Site id.
 * @param array  $site Site.
 */
function semrs_blocks_template_viewer( $sid, $site ) {
	$first = (string) key( $site['pages'] );
	echo '<div class="semrs-viewer"><div class="semrs-viewer__bar">';
	echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=semrs-templates' ) ) . '">&larr; ' . esc_html__( 'All templates', 'semrs-blocks' ) . '</a>';
	echo '<strong class="semrs-viewer__title">' . esc_html( $site['title'] ) . '</strong>';
	echo '<nav class="semrs-viewer__pages" aria-label="' . esc_attr__( 'Pages', 'semrs-blocks' ) . '">';
	foreach ( $site['pages'] as $pid => $page ) {
		echo '<button type="button" class="button' . ( $pid === $first ? ' button-primary' : '' ) . '" data-src="' . esc_url( semrs_blocks_template_preview_url( $sid, $pid ) ) . '">' . esc_html( strtr( $page['title'], array( '{{name}}' => $site['demo_name'] ?? $site['title'] ) ) ) . '</button>';
	}
	echo '</nav><span class="semrs-viewer__devices" role="group" aria-label="' . esc_attr__( 'Preview size', 'semrs-blocks' ) . '">';
	foreach ( array( '100%' => __( 'Desktop', 'semrs-blocks' ), '820px' => __( 'Tablet', 'semrs-blocks' ), '390px' => __( 'Phone', 'semrs-blocks' ) ) as $w => $label ) {
		echo '<button type="button" class="button" data-width="' . esc_attr( $w ) . '" aria-pressed="' . ( '100%' === $w ? 'true' : 'false' ) . '">' . esc_html( $label ) . '</button>';
	}
	echo '</span><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=semrs-templates&site=' . $sid ) ) . '">' . esc_html__( 'Import this site', 'semrs-blocks' ) . '</a></div>';
	echo '<div class="semrs-viewer__stage"><iframe class="semrs-viewer__frame" title="' . esc_attr( $site['title'] ) . '" src="' . esc_url( semrs_blocks_template_preview_url( $sid, $first ) ) . '"></iframe></div>';
	echo '<p class="description">' . esc_html__( 'Example business name, contact details, prices and posts: you replace them with your own during and after the import.', 'semrs-blocks' ) . '</p></div>';
}
