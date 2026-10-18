<?php
/**
 * Site Layouts: write any blocks and show them at an Astra hook with
 * display rules (free equivalent of Astra Pro "Custom Layouts").
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hooks offered in the UI.
 *
 * @return array<string, string>
 */
function semrs_core_layout_hooks() {
	return array(
		'body_top'        => __( 'Top of page (before header)', 'semrs-pro' ),
		'header_after'    => __( 'After header', 'semrs-pro' ),
		'content_top'     => __( 'Top of content area', 'semrs-pro' ),
		'entry_before'    => __( 'Before post/page content', 'semrs-pro' ),
		'entry_after'     => __( 'After post/page content', 'semrs-pro' ),
		'content_bottom'  => __( 'Bottom of content area', 'semrs-pro' ),
		'footer_before'   => __( 'Before footer', 'semrs-pro' ),
		'footer_after'    => __( 'After footer', 'semrs-pro' ),
	);
}

/**
 * Real hook name for a position in the active theme (SEMRS Pro or Astra).
 *
 * @param string $position Position key.
 * @return string
 */
function semrs_core_layout_hook_name( $position ) {
	$map = 'astra' === get_template()
		? array(
			'body_top'       => 'astra_body_top',
			'header_after'   => 'astra_header_after',
			'content_top'    => 'astra_primary_content_top',
			'entry_before'   => 'astra_entry_content_before',
			'entry_after'    => 'astra_entry_content_after',
			'content_bottom' => 'astra_primary_content_bottom',
			'footer_before'  => 'astra_footer_before',
			'footer_after'   => 'astra_footer_after',
		)
		: array(
			'body_top'       => 'wp_body_open',
			'header_after'   => 'semrs_after_header',
			'content_top'    => 'semrs_content_top',
			'entry_before'   => 'semrs_before_entry_content',
			'entry_after'    => 'semrs_after_entry_content',
			'content_bottom' => 'semrs_content_bottom',
			'footer_before'  => 'semrs_before_footer',
			'footer_after'   => 'semrs_after_footer',
		);
	return isset( $map[ $position ] ) ? $map[ $position ] : '';
}

/**
 * Display rules offered in the UI.
 *
 * @return array<string, string>
 */
function semrs_core_layout_rules() {
	return array(
		'everywhere' => __( 'Entire site', 'semrs-pro' ),
		'front'      => __( 'Front page only', 'semrs-pro' ),
		'posts'      => __( 'All blog posts', 'semrs-pro' ),
		'pages'      => __( 'All pages', 'semrs-pro' ),
		'archives'   => __( 'Blog, archives and search', 'semrs-pro' ),
		'tools'      => __( 'Pages under /tools/', 'semrs-pro' ),
		'services'   => __( 'Pages under /services/', 'semrs-pro' ),
		'ids'        => __( 'Only the IDs listed below', 'semrs-pro' ),
	);
}

/**
 * Register the post type (admin only, never public).
 */
function semrs_core_register_layouts() {
	register_post_type(
		'semrs_layout',
		array(
			'labels'       => array(
				'name'          => __( 'Site Layouts', 'semrs-pro' ),
				'singular_name' => __( 'Site Layout', 'semrs-pro' ),
				'add_new_item'  => __( 'Add site layout', 'semrs-pro' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'semrs',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'revisions' ),
		)
	);
}
add_action( 'init', 'semrs_core_register_layouts' );

/**
 * Settings meta box.
 */
function semrs_core_layout_metabox() {
	add_meta_box( 'semrs_layout_settings', __( 'Where to show', 'semrs-pro' ), 'semrs_core_render_layout_metabox', 'semrs_layout', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'semrs_core_layout_metabox' );

/**
 * Render a <select>.
 *
 * @param string $name    Field name.
 * @param array  $options Options.
 * @param string $current Current value.
 */
function semrs_core_select( $name, $options, $current ) {
	echo '<select class="widefat" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
	foreach ( $options as $value => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
	}
	echo '</select>';
}

/**
 * Render the meta box.
 *
 * @param WP_Post $post Post.
 */
function semrs_core_render_layout_metabox( $post ) {
	wp_nonce_field( 'semrs_layout_save', 'semrs_layout_nonce' );
	$hook     = get_post_meta( $post->ID, 'semrs_hook', true );
	$rule     = get_post_meta( $post->ID, 'semrs_rule', true );
	$ids      = get_post_meta( $post->ID, 'semrs_ids', true );
	$exclude  = get_post_meta( $post->ID, 'semrs_exclude', true );
	$priority = get_post_meta( $post->ID, 'semrs_priority', true );

	echo '<p><label for="semrs_hook"><strong>' . esc_html__( 'Position', 'semrs-pro' ) . '</strong></label>';
	semrs_core_select( 'semrs_hook', semrs_core_layout_hooks(), $hook ? $hook : 'footer_before' );
	echo '</p><p><label for="semrs_rule"><strong>' . esc_html__( 'Show on', 'semrs-pro' ) . '</strong></label>';
	semrs_core_select( 'semrs_rule', semrs_core_layout_rules(), $rule ? $rule : 'everywhere' );
	printf(
		'</p><p><label for="semrs_ids"><strong>%1$s</strong></label><input class="widefat" id="semrs_ids" name="semrs_ids" value="%2$s" placeholder="12, 34"></p>' .
		'<p><label for="semrs_exclude"><strong>%3$s</strong></label><input class="widefat" id="semrs_exclude" name="semrs_exclude" value="%4$s" placeholder="56, 78"></p>' .
		'<p><label for="semrs_priority"><strong>%5$s</strong></label><input type="number" class="widefat" id="semrs_priority" name="semrs_priority" value="%6$s"></p>',
		esc_html__( 'Page/post IDs (for "Only the IDs")', 'semrs-pro' ),
		esc_attr( $ids ),
		esc_html__( 'Never show on IDs', 'semrs-pro' ),
		esc_attr( $exclude ),
		esc_html__( 'Order (lower runs first)', 'semrs-pro' ),
		esc_attr( '' === $priority ? '10' : $priority )
	);
}

/**
 * Save settings.
 *
 * @param int $post_id Post ID.
 */
function semrs_core_save_layout( $post_id ) {
	if ( ! isset( $_POST['semrs_layout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_layout_nonce'] ) ), 'semrs_layout_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$hook = isset( $_POST['semrs_hook'] ) ? sanitize_key( $_POST['semrs_hook'] ) : '';
	$rule = isset( $_POST['semrs_rule'] ) ? sanitize_key( $_POST['semrs_rule'] ) : '';
	update_post_meta( $post_id, 'semrs_hook', array_key_exists( $hook, semrs_core_layout_hooks() ) ? $hook : 'footer_before' );
	update_post_meta( $post_id, 'semrs_rule', array_key_exists( $rule, semrs_core_layout_rules() ) ? $rule : 'everywhere' );
	foreach ( array( 'semrs_ids', 'semrs_exclude' ) as $key ) {
		$raw = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, implode( ',', array_filter( array_map( 'absint', explode( ',', $raw ) ) ) ) );
	}
	update_post_meta( $post_id, 'semrs_priority', isset( $_POST['semrs_priority'] ) ? (int) $_POST['semrs_priority'] : 10 );
	delete_transient( 'semrs_layouts' );
}
add_action( 'save_post_semrs_layout', 'semrs_core_save_layout' );

/**
 * Clear the cache when a layout is trashed or deleted.
 *
 * @param int $post_id Post ID.
 */
function semrs_core_flush_layouts( $post_id ) {
	if ( 'semrs_layout' === get_post_type( $post_id ) ) {
		delete_transient( 'semrs_layouts' );
	}
}
add_action( 'trashed_post', 'semrs_core_flush_layouts' );
add_action( 'deleted_post', 'semrs_core_flush_layouts' );
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'semrs_layout' === $post->post_type ) {
		delete_transient( 'semrs_layouts' );
	}
}, 10, 3 );

/**
 * Does a layout's rule match the current request?
 *
 * @param array $layout Layout data.
 * @return bool
 */
function semrs_core_layout_matches( $layout ) {
	$current = is_singular() ? get_queried_object_id() : 0;
	if ( $current && in_array( $current, $layout['exclude'], true ) ) {
		return false;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? trailingslashit( (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) ) : '/';

	switch ( $layout['rule'] ) {
		case 'everywhere':
			return true;
		case 'front':
			return is_front_page();
		case 'posts':
			return is_singular( 'post' );
		case 'pages':
			return is_page();
		case 'archives':
			return is_home() || is_archive() || is_search();
		case 'tools':
			return 0 === strpos( $path, '/tools/' );
		case 'services':
			return 0 === strpos( $path, '/services/' );
		case 'ids':
			return $current && in_array( $current, $layout['ids'], true );
	}
	return false;
}

/**
 * Attach published layouts to their hooks (cached in a transient).
 */
function semrs_core_attach_layouts() {
	if ( is_admin() ) {
		return;
	}
	$layouts = get_transient( 'semrs_layouts' );
	if ( false === $layouts ) {
		$layouts = array();
		$posts   = get_posts(
			array(
				'post_type'      => 'semrs_layout',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
			)
		);
		foreach ( $posts as $p ) {
			$to_ints   = function ( $csv ) {
				return array_values( array_filter( array_map( 'absint', explode( ',', (string) $csv ) ) ) );
			};
			$layouts[] = array(
				'id'       => $p->ID,
				'hook'     => get_post_meta( $p->ID, 'semrs_hook', true ),
				'rule'     => get_post_meta( $p->ID, 'semrs_rule', true ),
				'ids'      => $to_ints( get_post_meta( $p->ID, 'semrs_ids', true ) ),
				'exclude'  => $to_ints( get_post_meta( $p->ID, 'semrs_exclude', true ) ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- a display rule, not a query.
				'priority' => (int) get_post_meta( $p->ID, 'semrs_priority', true ),
				'content'  => $p->post_content,
			);
		}
		set_transient( 'semrs_layouts', $layouts, DAY_IN_SECONDS );
	}

	foreach ( $layouts as $layout ) {
		if ( ! semrs_core_layout_hook_name( $layout['hook'] ) ) {
			continue;
		}
		add_action(
			semrs_core_layout_hook_name( $layout['hook'] ),
			function () use ( $layout ) {
				if ( semrs_core_layout_matches( $layout ) ) {
					// do_blocks + shortcodes only: avoids the_content filters (TOC, author box) running on layouts.
					echo '<div class="semrs-layout semrs-layout-' . (int) $layout['id'] . '">' . do_shortcode( do_blocks( $layout['content'] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- editor-authored blocks.
				}
			},
			$layout['priority'] ? $layout['priority'] : 10
		);
	}
}
add_action( 'wp', 'semrs_core_attach_layouts' );
