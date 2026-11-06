<?php
/**
 * Site tools: maintenance / coming-soon mode (503 + Retry-After, so search
 * engines keep your rankings), duplicate any post, page or custom post, and
 * turn comments off everywhere. SEMRS → Site tools.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_sitetools_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_site_tools', get_option( 'semrs_tools', array() ) ), // Saved as 'semrs_tools' before Core 1.3.45.
		array(
			'maint'     => '',
			'mode'      => 'maintenance',
			'headline'  => __( 'We will be back soon', 'semrs-core' ),
			'message'   => __( 'We are making a few improvements. Please check back shortly.', 'semrs-core' ),
			'until'     => '',
			'duplicate' => '1',
			'comments'  => '',
		)
	);
}

/*
 * Maintenance / coming soon.
 */

/**
 * Show the maintenance page to visitors who cannot edit.
 */
function semrs_sitetools_maintenance() {
	$s = semrs_sitetools_settings();
	if ( '1' !== $s['maint'] || current_user_can( 'edit_posts' ) || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	if ( preg_match( '#(wp-login\.php|/wp-admin|robots\.txt|ads\.txt|\.well-known)#', $path ) || ( function_exists( 'is_login' ) && is_login() ) ) {
		return;
	}
	$until = $s['until'] ? strtotime( $s['until'] . ' ' . wp_timezone_string() ) : 0;
	if ( $until && $until <= time() ) {
		return; // The end date has passed: the site opens by itself.
	}
	$soon = 'soon' === $s['mode'];
	if ( ! $soon ) {
		status_header( 503 );
		header( 'Retry-After: ' . ( $until ? max( 60, $until - time() ) : 3600 ) );
	}
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	$name = get_bloginfo( 'name' );
	$logo = get_custom_logo();
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="<?php echo $soon ? 'noindex' : 'noindex,nofollow'; ?>">
<title><?php echo esc_html( $s['headline'] . ' – ' . $name ); ?></title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0b1f3a;color:#fff;font:17px/1.6 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;text-align:center}main{max-width:36rem;padding:2rem}h1{margin:.5rem 0;font-size:clamp(1.8rem,5vw,2.6rem)}p{opacity:.85}.l svg,.l img{max-width:180px;height:auto}.d{margin-top:1rem;font-weight:700;color:#f5b83d}</style>
</head>
<body><main>
<div class="l"><?php echo $logo ? wp_kses_post( $logo ) : '<strong>' . esc_html( $name ) . '</strong>'; ?></div>
<h1><?php echo esc_html( $s['headline'] ); ?></h1>
<p><?php echo nl2br( esc_html( $s['message'] ) ); ?></p>
<?php if ( $until ) : ?><p class="d"><?php /* translators: %s: date. */ echo esc_html( sprintf( __( 'Back on %s', 'semrs-core' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $until ) ) ); ?></p><?php endif; ?>
</main></body></html>
	<?php
	exit;
}
add_action( 'template_redirect', 'semrs_sitetools_maintenance', -100 );

/**
 * Admin bar reminder while maintenance is on.
 *
 * @param WP_Admin_Bar $bar Bar.
 */
function semrs_sitetools_admin_bar( $bar ) {
	if ( '1' === semrs_sitetools_settings()['maint'] && current_user_can( 'manage_options' ) ) {
		$bar->add_node( array( 'id' => 'semrs-maint', 'title' => '<span style="color:#f5b83d">' . esc_html__( 'Maintenance mode is ON', 'semrs-core' ) . '</span>', 'href' => admin_url( 'admin.php?page=semrs-site-tools' ) ) );
	}
}
add_action( 'admin_bar_menu', 'semrs_sitetools_admin_bar', 999 );

/*
 * Duplicate.
 */

/**
 * "Duplicate" link in post lists.
 *
 * @param array   $actions Actions.
 * @param WP_Post $post    Post.
 * @return array
 */
function semrs_sitetools_row_action( $actions, $post ) {
	if ( '1' === semrs_sitetools_settings()['duplicate'] && current_user_can( 'edit_post', $post->ID ) && 'trash' !== $post->post_status ) {
		$url                      = wp_nonce_url( admin_url( 'admin-post.php?action=semrs_duplicate&post=' . $post->ID ), 'semrs_duplicate_' . $post->ID );
		$actions['semrs_duplicate'] = '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( /* translators: %s: title. */ __( 'Duplicate “%s”', 'semrs-core' ), get_the_title( $post ) ) ) . '">' . esc_html__( 'Duplicate', 'semrs-core' ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'semrs_sitetools_row_action', 10, 2 );
add_filter( 'page_row_actions', 'semrs_sitetools_row_action', 10, 2 );

/**
 * Create the copy as a draft, with terms and custom fields.
 */
function semrs_sitetools_duplicate() {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- checked below.
	check_admin_referer( 'semrs_duplicate_' . $id );
	$post = get_post( $id );
	if ( ! $post || ! current_user_can( 'edit_post', $id ) || '1' !== semrs_sitetools_settings()['duplicate'] ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-core' ) );
	}
	$new = wp_insert_post(
		wp_slash(
			array(
				'post_type'      => $post->post_type,
				'post_title'     => $post->post_title . ' ' . __( '(copy)', 'semrs-core' ),
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_status'    => 'draft',
				'post_author'    => get_current_user_id(),
				'post_parent'    => $post->post_parent,
				'menu_order'     => $post->menu_order,
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_password'  => $post->post_password,
			)
		),
		true
	);
	if ( is_wp_error( $new ) ) {
		wp_die( esc_html( $new->get_error_message() ) );
	}
	foreach ( get_object_taxonomies( $post->post_type ) as $tax ) {
		wp_set_object_terms( $new, wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) ), $tax );
	}
	$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_semrs_score' );
	foreach ( get_post_meta( $id ) as $key => $values ) {
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		foreach ( $values as $v ) {
			add_post_meta( $new, $key, wp_slash( maybe_unserialize( $v ) ) );
		}
	}
	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new ) );
	exit;
}
add_action( 'admin_post_semrs_duplicate', 'semrs_sitetools_duplicate' );

/*
 * Comments off everywhere.
 */
if ( '1' === semrs_sitetools_settings()['comments'] ) {
	add_filter( 'comments_open', '__return_false', 99 );
	add_filter( 'pings_open', '__return_false', 99 );
	add_filter( 'comments_array', '__return_empty_array', 99 );
	add_filter( 'feed_links_show_comments_feed', '__return_false' );
	add_action(
		'init',
		function () {
			foreach ( get_post_types() as $type ) {
				if ( post_type_supports( $type, 'comments' ) ) {
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
		},
		100
	);
	add_action(
		'admin_menu',
		function () {
			remove_menu_page( 'edit-comments.php' );
			remove_submenu_page( 'options-general.php', 'options-discussion.php' );
		},
		999
	);
	add_action(
		'admin_bar_menu',
		function ( $bar ) {
			$bar->remove_node( 'comments' );
		},
		999
	);
	add_filter(
		'rest_endpoints',
		function ( $e ) {
			unset( $e['/wp/v2/comments'], $e['/wp/v2/comments/(?P<id>[\d]+)'] );
			return $e;
		}
	);
	add_filter( 'xmlrpc_methods', function ( $m ) { unset( $m['wp.newComment'] ); return $m; } );
}

if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs', __( 'Site tools', 'semrs-core' ), __( 'Site tools', 'semrs-core' ), 'manage_options', 'semrs-site-tools', 'semrs_sitetools_page' );
		},
		33
	);
	add_action(
		'admin_init',
		function () {
			register_setting(
				'semrs_site_tools',
				'semrs_site_tools',
				array(
					'type'              => 'array',
					'sanitize_callback' => function ( $in ) {
						$in = (array) $in;
						if ( function_exists( 'semrs_cache_purge_all' ) ) {
							semrs_cache_purge_all();
						}
						return array(
							'maint'     => empty( $in['maint'] ) ? '' : '1',
							'mode'      => isset( $in['mode'] ) && 'soon' === $in['mode'] ? 'soon' : 'maintenance',
							'headline'  => sanitize_text_field( $in['headline'] ?? '' ),
							'message'   => sanitize_textarea_field( $in['message'] ?? '' ),
							'until'     => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) ( $in['until'] ?? '' ) ) ? $in['until'] : '',
							'duplicate' => empty( $in['duplicate'] ) ? '' : '1',
							'comments'  => empty( $in['comments'] ) ? '' : '1',
						);
					},
				)
			);
		}
	);
}

/**
 * Settings page.
 */
function semrs_sitetools_page() {
	$s = semrs_sitetools_settings();
	$n = 'semrs_site_tools';
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Site tools', 'semrs-core' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_site_tools' ); ?>
			<h2><?php esc_html_e( 'Maintenance or coming-soon page', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Turn on', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[maint]" value="1" <?php checked( $s['maint'], '1' ); ?>> <?php esc_html_e( 'Visitors see the page below; you and your editors see the normal site when logged in', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Type', 'semrs-core' ); ?></th><td>
					<label><input type="radio" name="<?php echo esc_attr( $n ); ?>[mode]" value="maintenance" <?php checked( $s['mode'], 'maintenance' ); ?>> <?php esc_html_e( 'Maintenance (short work on a live site): tells Google "temporarily unavailable" (503), so rankings are kept', 'semrs-core' ); ?></label><br>
					<label><input type="radio" name="<?php echo esc_attr( $n ); ?>[mode]" value="soon" <?php checked( $s['mode'], 'soon' ); ?>> <?php esc_html_e( 'Coming soon (new site, not launched yet)', 'semrs-core' ); ?></label>
				</td></tr>
				<tr><th><?php esc_html_e( 'Headline', 'semrs-core' ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $n ); ?>[headline]" value="<?php echo esc_attr( $s['headline'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Message', 'semrs-core' ); ?></th><td><textarea class="large-text" rows="3" name="<?php echo esc_attr( $n ); ?>[message]"><?php echo esc_textarea( $s['message'] ); ?></textarea></td></tr>
				<tr><th><?php esc_html_e( 'Open again automatically on', 'semrs-core' ); ?></th><td><input type="datetime-local" name="<?php echo esc_attr( $n ); ?>[until]" value="<?php echo esc_attr( $s['until'] ); ?>"> <span class="description"><?php esc_html_e( 'Optional. Your site\'s time zone.', 'semrs-core' ); ?></span></td></tr>
			</table>
			<h2><?php esc_html_e( 'Content', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Duplicate', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[duplicate]" value="1" <?php checked( $s['duplicate'], '1' ); ?>> <?php esc_html_e( 'Show a "Duplicate" link under every post, page and custom post: makes a draft copy with categories, tags, featured image and SEO settings', 'semrs-core' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Comments', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[comments]" value="1" <?php checked( $s['comments'], '1' ); ?>> <?php esc_html_e( 'Turn comments off everywhere: hides existing comments, the comment forms, menus and feeds, and stops comment spam', 'semrs-core' ); ?></label></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
