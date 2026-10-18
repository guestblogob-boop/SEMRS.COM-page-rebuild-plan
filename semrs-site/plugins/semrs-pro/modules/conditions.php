<?php
/**
 * Display conditions (Pro): show a block only to logged-in or logged-out
 * visitors, to certain roles, between two dates, or on certain kinds of
 * page. Block sidebar → "Display conditions". Popups use the same rules.
 *
 * Note for cached sites: login, role and date rules are decided when the
 * page is built. If a page cache serves the same HTML to everyone, exclude
 * those pages from the cache or use device visibility (free) instead.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page types a rule can target.
 *
 * @return array
 */
function semrs_pro_page_types() {
	return array(
		'front'    => __( 'Front page', 'semrs-pro' ),
		'posts'    => __( 'Single posts', 'semrs-pro' ),
		'pages'    => __( 'Pages', 'semrs-pro' ),
		'archives' => __( 'Blog, category and tag archives', 'semrs-pro' ),
		'search'   => __( 'Search results', 'semrs-pro' ),
		'404'      => __( '404 page', 'semrs-pro' ),
	);
}

/**
 * Does the current request match these rules?
 *
 * @param array $c Rules: showTo (''|in|out), roles (string[]), from, until
 *                 (Y-m-d\TH:i, site time), pages (string[]), ids (int[]),
 *                 exclude (int[]).
 * @return bool
 */
function semrs_pro_conditions_pass( $c ) {
	$show = $c['showTo'] ?? '';
	if ( 'in' === $show && ! is_user_logged_in() ) {
		return false;
	}
	if ( 'out' === $show && is_user_logged_in() ) {
		return false;
	}
	$roles = array_filter( (array) ( $c['roles'] ?? array() ) );
	if ( $roles ) {
		$user = wp_get_current_user();
		if ( ! $user->exists() || ! array_intersect( $roles, (array) $user->roles ) ) {
			return false;
		}
	}
	$now = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- compared with site-time inputs.
	if ( ! empty( $c['from'] ) && strtotime( $c['from'] ) && $now < strtotime( $c['from'] ) ) {
		return false;
	}
	if ( ! empty( $c['until'] ) && strtotime( $c['until'] ) && $now > strtotime( $c['until'] ) ) {
		return false;
	}
	$id = is_singular() ? get_queried_object_id() : 0;
	if ( $id && in_array( $id, array_map( 'intval', (array) ( $c['exclude'] ?? array() ) ), true ) ) {
		return false;
	}
	$pages = array_filter( (array) ( $c['pages'] ?? array() ) );
	$ids   = array_filter( array_map( 'intval', (array) ( $c['ids'] ?? array() ) ) );
	if ( $pages || $ids ) {
		$match = $id && in_array( $id, $ids, true );
		$map   = array(
			'front'    => is_front_page(),
			'posts'    => is_singular( 'post' ),
			'pages'    => is_page() && ! is_front_page(),
			'archives' => is_home() || is_archive(),
			'search'   => is_search(),
			'404'      => is_404(),
		);
		foreach ( $pages as $p ) {
			$match = $match || ! empty( $map[ $p ] );
		}
		if ( ! $match ) {
			return false;
		}
	}
	return true;
}

/**
 * Block attributes on the server (all blocks).
 *
 * @param array $args Block type args.
 * @return array
 */
function semrs_pro_conditions_attrs( $args ) {
	$args['attributes'] = array_merge(
		$args['attributes'] ?? array(),
		array(
			'semrsShowTo' => array( 'type' => 'string', 'default' => '' ),
			'semrsRoles'  => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'string' ) ),
			'semrsFrom'   => array( 'type' => 'string', 'default' => '' ),
			'semrsUntil'  => array( 'type' => 'string', 'default' => '' ),
			'semrsPages'  => array( 'type' => 'array', 'default' => array(), 'items' => array( 'type' => 'string' ) ),
		)
	);
	return $args;
}
add_filter( 'register_block_type_args', 'semrs_pro_conditions_attrs' );

/**
 * Remove blocks whose conditions do not match (front end only).
 *
 * @param string $html  Block HTML.
 * @param array  $block Block.
 * @return string
 */
function semrs_pro_conditions_render( $html, $block ) {
	$a = $block['attrs'] ?? array();
	if ( empty( $a['semrsShowTo'] ) && empty( $a['semrsRoles'] ) && empty( $a['semrsFrom'] ) && empty( $a['semrsUntil'] ) && empty( $a['semrsPages'] ) ) {
		return $html;
	}
	if ( is_admin() || wp_is_json_request() ) {
		return $html;
	}
	$pass = semrs_pro_conditions_pass(
		array(
			'showTo' => $a['semrsShowTo'] ?? '',
			'roles'  => $a['semrsRoles'] ?? array(),
			'from'   => $a['semrsFrom'] ?? '',
			'until'  => $a['semrsUntil'] ?? '',
			'pages'  => $a['semrsPages'] ?? array(),
		)
	);
	return $pass ? $html : '';
}
add_filter( 'render_block', 'semrs_pro_conditions_render', 5, 2 );

/**
 * Editor panel.
 */
function semrs_pro_conditions_editor() {
	wp_enqueue_script( 'semrs-pro-conditions', SEMRS_PRO_URL . 'assets/js/conditions-editor.js', array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ), SEMRS_PRO_VERSION, true );
	$roles = array();
	foreach ( wp_roles()->get_names() as $key => $name ) {
		$roles[] = array( 'value' => $key, 'label' => translate_user_role( $name ) );
	}
	$pages = array();
	foreach ( semrs_pro_page_types() as $key => $label ) {
		$pages[] = array( 'value' => $key, 'label' => $label );
	}
	wp_localize_script( 'semrs-pro-conditions', 'SEMRS_COND', array( 'roles' => $roles, 'pages' => $pages ) );
}
add_action( 'enqueue_block_editor_assets', 'semrs_pro_conditions_editor' );
