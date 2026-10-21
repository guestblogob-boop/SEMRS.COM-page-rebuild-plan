<?php
/**
 * Admin: tool list with shortcodes + "Create draft pages".
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu under SEMRS (when SEMRS Core is active) or under Tools.
 */
function semrs_tools_admin_menu() {
	if ( function_exists( 'semrs_core_modules' ) ) {
		add_submenu_page( 'semrs', __( 'Free Tools', 'semrs-tools' ), __( 'Free Tools', 'semrs-tools' ), 'edit_pages', 'semrs-tools', 'semrs_tools_admin_page' );
	} else {
		add_management_page( __( 'SEMRS Free Tools', 'semrs-tools' ), __( 'SEMRS Free Tools', 'semrs-tools' ), 'edit_pages', 'semrs-tools', 'semrs_tools_admin_page' );
	}
}
add_action( 'admin_menu', 'semrs_tools_admin_menu', 25 );

/**
 * Create one draft page unless the path already exists.
 *
 * @param string $title   Title.
 * @param string $slug    Slug.
 * @param string $content Block content.
 * @param int    $parent  Parent ID.
 * @param string $path    Full path for the existence check.
 * @param string $schema  SEMRS SEO schema type.
 * @return array{0:int,1:bool} [page ID, created?]
 */
function semrs_tools_make_page( $title, $slug, $content, $parent, $path, $schema = '' ) {
	$existing = get_page_by_path( $path );
	if ( $existing ) {
		return array( (int) $existing->ID, false );
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_parent'  => $parent,
			'post_content' => $content,
		)
	);
	if ( $id && ! is_wp_error( $id ) && $schema ) {
		update_post_meta( $id, '_semrs_schema', $schema );
	}
	return array( is_wp_error( $id ) ? 0 : (int) $id, true );
}

/**
 * Block helpers.
 *
 * @param string $text Text.
 * @return string
 */
function semrs_tools_p( $text ) {
	return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->\n\n";
}

/**
 * Shortcode block.
 *
 * @param string $code Shortcode.
 * @return string
 */
function semrs_tools_sc( $code ) {
	return "<!-- wp:shortcode -->\n" . $code . "\n<!-- /wp:shortcode -->\n\n";
}

/**
 * Heading block.
 *
 * @param string $text Text.
 * @return string
 */
function semrs_tools_h2( $text ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $text ) . "</h2>\n<!-- /wp:heading -->\n\n";
}

/**
 * Store SEO fields for SEMRS Core SEO and Rank Math (whichever is active).
 *
 * @param int   $id   Page ID.
 * @param array $page Page definition from content/pages.json.
 */
function semrs_tools_set_seo( $id, $page ) {
	$map = array(
		'_semrs_title'            => $page['title'],
		'_semrs_description'      => $page['description'],
		'_semrs_focus'            => $page['focus'],
		'_semrs_schema'           => $page['schema'],
		'rank_math_title'         => $page['title'],
		'rank_math_description'   => $page['description'],
		'rank_math_focus_keyword' => $page['focus'],
	);
	foreach ( $map as $key => $value ) {
		if ( '' !== (string) $value ) {
			update_post_meta( $id, $key, sanitize_text_field( $value ) );
		}
	}
}

/**
 * Create the full tools section from the bundled content (content/*.html +
 * content/pages.json). Existing pages are never overwritten.
 *
 * @param string $status Post status for new pages (draft by default).
 * @return int Number of pages created.
 */
function semrs_tools_install_pages( $status = 'draft' ) {
	$dir   = SEMRS_TOOLS_DIR . 'content/';
	$pages = file_exists( $dir . 'pages.json' ) ? json_decode( (string) file_get_contents( $dir . 'pages.json' ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! is_array( $pages ) || empty( $pages['tools'] ) ) {
		return -1;
	}
	$made = 0;
	$read = function ( $slug ) use ( $dir ) {
		return file_exists( $dir . $slug . '.html' ) ? (string) file_get_contents( $dir . $slug . '.html' ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	};

	$hub      = $pages['tools'];
	$existing = get_page_by_path( 'tools' );
	if ( $existing ) {
		$hub_id = (int) $existing->ID;
	} else {
		$hub_id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => $status,
				'post_title'   => $hub['h1'],
				'post_name'    => 'tools',
				'post_content' => $read( 'tools' ),
			)
		);
		if ( $hub_id ) {
			semrs_tools_set_seo( $hub_id, $hub );
			$made++;
		}
	}
	if ( ! $hub_id ) {
		return $made;
	}

	unset( $pages['tools'] );
	uasort(
		$pages,
		function ( $a, $b ) {
			return (int) $a['order'] - (int) $b['order'];
		}
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( 'tools/' . $slug ) ) {
			continue;
		}
		$id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => $status,
				'post_title'   => $page['h1'],
				'post_name'    => $slug,
				'post_parent'  => $hub_id,
				'menu_order'   => (int) $page['order'],
				'post_content' => $read( $slug ),
			)
		);
		if ( $id ) {
			semrs_tools_set_seo( $id, $page );
			$made++;
		}
	}
	return $made;
}

/**
 * Handle "Create draft pages".
 */
function semrs_tools_create_pages() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-tools' ) );
	}
	check_admin_referer( 'semrs_tools_pages' );
	$made = semrs_tools_install_pages( 'draft' );
	if ( $made >= 0 ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-tools', 'made' => $made ), admin_url( function_exists( 'semrs_core_modules' ) ? 'admin.php' : 'tools.php' ) ) );
		exit;
	}
	// Fallback when the bundled content is missing: placeholder drafts.
	$made = 0;

	list( $hub, $new ) = semrs_tools_make_page(
		__( 'Free SEO Tools', 'semrs-tools' ),
		'tools',
		semrs_tools_p( __( 'Free SEO tools for business owners and marketers. No signup, no limits on browser tools.', 'semrs-tools' ) ) . semrs_tools_sc( '[semrs_tools]' ),
		0,
		'tools',
		'CollectionPage'
	);
	$made += $new ? 1 : 0;

	foreach ( semrs_tools_categories() as $slug => $cat ) {
		/* translators: %s: category name */
		$r     = semrs_tools_make_page( sprintf( __( 'Free %s Tools', 'semrs-tools' ), $cat[0] ), $slug, semrs_tools_p( $cat[1] ) . semrs_tools_sc( '[semrs_tools category="' . $slug . '"]' ), $hub, 'tools/' . $slug, 'CollectionPage' );
		$made += $r[1] ? 1 : 0;
	}

	foreach ( semrs_tools_list() as $id => $t ) {
		$content = semrs_tools_p( $t[2] )
			. semrs_tools_sc( '[semrs_tool id="' . $id . '"]' )
			. semrs_tools_sc( '[semrs_ad slot="tool"]' )
			. semrs_tools_h2( __( 'How to use this tool', 'semrs-tools' ) )
			. semrs_tools_p( __( 'DRAFT: write the guide (800–1,500 words: how to use it, a worked example, best practices, common mistakes, FAQ) before publishing. Pages without a real guide are thin content for Google and AdSense.', 'semrs-tools' ) );
		/* translators: %s: tool name */
		$r     = semrs_tools_make_page( sprintf( __( 'Free %s', 'semrs-tools' ), $t[0] ), $id, $content, $hub, 'tools/' . $id, 'WebApplication' );
		$made += $r[1] ? 1 : 0;
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-tools', 'made' => $made ), admin_url( function_exists( 'semrs_core_modules' ) ? 'admin.php' : 'tools.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_tools_pages', 'semrs_tools_create_pages' );

/**
 * Admin page.
 */
function semrs_tools_admin_page() {
	$made = isset( $_GET['made'] ) ? absint( $_GET['made'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification
	$cats = semrs_tools_categories();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Free Tools', 'semrs-tools' ); ?></h1>
		<?php if ( null !== $made ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				/* translators: %d: number of pages */
				echo esc_html( sprintf( __( '%d draft pages created. Existing pages were left untouched.', 'semrs-tools' ), $made ) );
				?>
			</p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Put a tool on any page with its shortcode. Each page loads only that tool\'s script.', 'semrs-tools' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_tools_pages' ); ?>
			<input type="hidden" name="action" value="semrs_tools_pages">
			<p><button class="button button-primary"><?php esc_html_e( 'Create all tool pages as drafts (hub, 9 categories, 12 tools, full content + SEO)', 'semrs-tools' ); ?></button>
			<span class="description"><?php esc_html_e( 'Drafts with complete guides, SEO titles, descriptions, focus keywords and schema. Existing pages are never overwritten. Review, then publish.', 'semrs-tools' ); ?></span></p>
		</form>
		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><th><?php esc_html_e( 'Tool', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Category', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Runs', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Shortcode', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Page', 'semrs-tools' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( semrs_tools_list() as $id => $t ) : $page = get_page_by_path( 'tools/' . $id ); ?>
				<tr>
					<td><strong><?php echo esc_html( $t[0] ); ?></strong><br><span class="description"><?php echo esc_html( $t[2] ); ?></span></td>
					<td><?php echo esc_html( isset( $cats[ $t[1] ] ) ? $cats[ $t[1] ][0] : $t[1] ); ?></td>
					<td><?php echo 'server' === $t[3] ? esc_html__( 'Server (rate-limited)', 'semrs-tools' ) : esc_html__( 'Browser', 'semrs-tools' ); ?></td>
					<td><code>[semrs_tool id="<?php echo esc_html( $id ); ?>"]</code></td>
					<td><?php echo $page ? '<a href="' . esc_url( get_edit_post_link( $page->ID ) ) . '">' . esc_html( get_post_status( $page ) ) . '</a>' : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><?php esc_html_e( 'Directory shortcodes:', 'semrs-tools' ); ?> <code>[semrs_tools]</code> · <code>[semrs_tools category="technical-seo"]</code></p>
	</div>
	<?php
}

/**
 * Plugins screen: Settings link.
 *
 * @param string[] $links Links.
 * @return string[]
 */
function semrs_tools_action_links( $links ) {
	$url = menu_page_url( 'semrs-tools', false );
	if ( $url ) {
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'semrs-tools' ) . '</a>' );
	}
	return $links;
}
add_filter( 'plugin_action_links_semrs-tools/semrs-tools.php', 'semrs_tools_action_links' );
