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

	// Tools without a bundled guide yet: always a draft with a reminder to write the guide.
	foreach ( semrs_tools_list() as $tid => $t ) {
		if ( isset( $pages[ $tid ] ) ) {
			continue;
		}
		$content = semrs_tools_p( $t[2] )
			. semrs_tools_sc( '[semrs_tool id="' . $tid . '"]' )
			. semrs_tools_sc( '[semrs_ad slot="tool"]' )
			. semrs_tools_h2( __( 'How to use this tool', 'semrs-tools' ) )
			. semrs_tools_p( __( 'DRAFT: write the guide (800–1,500 words: how to use it, a worked example, best practices, common mistakes, FAQ) before publishing. Pages without a real guide are thin content for Google and AdSense.', 'semrs-tools' ) );
		/* translators: %s: tool name */
		$r     = semrs_tools_make_page( sprintf( __( 'Free %s', 'semrs-tools' ), $t[0] ), $tid, $content, $hub_id, 'tools/' . $tid, 'WebApplication' );
		$made += $r[1] ? 1 : 0;
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
 * Admin page with tabs: Tools, Settings, Usage report (Pro).
 */
function semrs_tools_admin_page() {
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'tools'; // phpcs:ignore WordPress.Security.NonceVerification
	$base = menu_page_url( 'semrs-tools', false );
	$tabs = array(
		'tools'    => __( 'Tools', 'semrs-tools' ),
		'settings' => __( 'Settings', 'semrs-tools' ),
		'usage'    => __( 'Usage report', 'semrs-tools' ) . ( semrs_tools_is_pro() ? '' : ' (Pro)' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Free Tools', 'semrs-tools' ); ?></h1>
		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Free Tools sections', 'semrs-tools' ); ?>">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $k, $base ) ); ?>" class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>"<?php echo $k === $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		if ( 'settings' === $tab ) {
			semrs_tools_admin_settings();
		} elseif ( 'usage' === $tab ) {
			semrs_tools_admin_usage();
		} else {
			semrs_tools_admin_tools();
		}
		?>
	</div>
	<?php
}

/**
 * Tools tab: every tool, its status, shortcode and page.
 */
function semrs_tools_admin_tools() {
	$made  = isset( $_GET['made'] ) ? absint( $_GET['made'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification
	$cats  = semrs_tools_categories();
	$tools = semrs_tools_list();
	$list  = semrs_tools_listed_ids();
	if ( null !== $made ) {
		/* translators: %d: number of pages */
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( '%d draft pages created. Existing pages were left untouched.', 'semrs-tools' ), $made ) ) . '</p></div>';
	}
	?>
	<p><?php esc_html_e( 'Put a tool on any page with its shortcode. Each page loads only that tool\'s script. The directory ([semrs_tools]) lists a tool once its page at /tools/tool-id/ is published, so visitors never reach a missing page.', 'semrs-tools' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'semrs_tools_pages' ); ?>
		<input type="hidden" name="action" value="semrs_tools_pages">
		<p><button class="button button-primary">
			<?php
			/* translators: 1: categories, 2: tools */
			echo esc_html( sprintf( __( 'Create the missing tool pages as drafts (hub, %1$d categories, %2$d tools)', 'semrs-tools' ), count( $cats ), count( $tools ) ) );
			?>
		</button>
		<span class="description"><?php esc_html_e( 'Pages with a bundled guide get the full text, SEO title, description, focus keyword and schema. Existing pages are never overwritten. Review, then publish.', 'semrs-tools' ); ?></span></p>
	</form>
	<table class="widefat striped" style="max-width:1200px">
		<thead><tr><th><?php esc_html_e( 'Tool', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Category', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Runs', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Shortcode', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Page', 'semrs-tools' ); ?></th></tr></thead>
		<tbody>
		<?php
		foreach ( $tools as $id => $t ) :
			$page = get_page_by_path( 'tools/' . $id );
			?>
			<tr>
				<td><strong><?php echo esc_html( $t[0] ); ?></strong><br><span class="description"><?php echo esc_html( $t[2] ); ?></span></td>
				<td><?php echo esc_html( isset( $cats[ $t[1] ] ) ? $cats[ $t[1] ][0] : $t[1] ); ?></td>
				<td><?php echo 'server' === $t[3] ? esc_html__( 'Server (rate-limited)', 'semrs-tools' ) : esc_html__( 'Browser', 'semrs-tools' ); ?></td>
				<td>
					<?php
					if ( ! semrs_tools_enabled( $id ) ) {
						esc_html_e( 'Off', 'semrs-tools' );
					} elseif ( in_array( $id, $list, true ) ) {
						esc_html_e( 'On, in directory', 'semrs-tools' );
					} else {
						esc_html_e( 'On, not listed yet (page not published)', 'semrs-tools' );
					}
					?>
				</td>
				<td><code>[semrs_tool id="<?php echo esc_html( $id ); ?>"]</code></td>
				<td><?php echo $page ? '<a href="' . esc_url( (string) get_edit_post_link( $page->ID ) ) . '">' . esc_html( get_post_status( $page ) ) . '</a>' : '—'; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p><?php esc_html_e( 'Directory shortcodes:', 'semrs-tools' ); ?> <code>[semrs_tools]</code> · <code>[semrs_tools category="technical-seo"]</code> · <code>[semrs_tools exclude="word-counter,utm-builder"]</code></p>
	<?php
}

/**
 * One checkbox row of the settings form.
 *
 * @param string $key   Option key.
 * @param string $label Label.
 * @param string $desc  Description.
 */
function semrs_tools_admin_box( $key, $label, $desc = '' ) {
	$o = semrs_tools_options();
	printf(
		'<label><input type="checkbox" name="semrs_tools_options[%1$s]" value="1"%2$s> %3$s</label>%4$s',
		esc_attr( $key ),
		checked( '1', $o[ $key ], false ),
		esc_html( $label ),
		$desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : ''
	);
}

/**
 * Settings tab.
 */
function semrs_tools_admin_settings() {
	$o    = semrs_tools_options();
	$pro  = semrs_tools_is_pro();
	$n    = 'semrs_tools_options';
	$cats = semrs_tools_categories();
	?>
	<form method="post" action="options.php">
		<?php settings_errors(); ?>
		<?php settings_fields( 'semrs_tools_options' ); ?>
		<h2><?php esc_html_e( 'Tools on this site', 'semrs-tools' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Switched-off tools show nothing to visitors, leave the directory and stop answering server requests.', 'semrs-tools' ); ?></p>
		<table class="form-table" role="presentation">
		<?php
		foreach ( $cats as $slug => $cat ) :
			$in = array_filter(
				semrs_tools_list(),
				function ( $t ) use ( $slug ) {
					return $t[1] === $slug;
				}
			);
			if ( ! $in ) {
				continue;
			}
			?>
			<tr><th scope="row"><?php echo esc_html( $cat[0] ); ?></th><td><fieldset><legend class="screen-reader-text"><?php echo esc_html( $cat[0] ); ?></legend>
			<?php foreach ( $in as $id => $t ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[on][]" value="<?php echo esc_attr( $id ); ?>"<?php checked( semrs_tools_enabled( $id ) ); ?>> <?php echo esc_html( $t[0] ); ?></label><br>
			<?php endforeach; ?>
			</fieldset></td></tr>
		<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Visitors', 'semrs-tools' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><?php esc_html_e( 'Expert-help box', 'semrs-tools' ); ?></th><td><?php semrs_tools_admin_box( 'cta', __( 'Show a short "Want an expert to handle this?" box under each tool', 'semrs-tools' ), __( 'Links to the service page of the tool\'s category.', 'semrs-tools' ) ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Remember inputs', 'semrs-tools' ); ?></th><td><?php semrs_tools_admin_box( 'remember', __( 'Keep what visitors typed for their next visit', 'semrs-tools' ), __( 'Stored only in their own browser (functional storage). Nothing is sent to your server.', 'semrs-tools' ) ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Analytics events', 'semrs-tools' ); ?></th><td><?php semrs_tools_admin_box( 'track', __( 'Send tool_used, tool_run, tool_copy, tool_download and cta_click to Google Analytics 4', 'semrs-tools' ), __( 'Only when your GA4 tag is on the page and allowed by your cookie banner.', 'semrs-tools' ) ); ?></td></tr>
		</table>

		<h2><?php esc_html_e( 'Server tools', 'semrs-tools' ); ?></h2>
		<p class="description"><?php esc_html_e( 'The Redirect Checker, On-Page SEO Checker and Robots.txt Tester ask your server to read another website. Private and internal addresses are always blocked.', 'semrs-tools' ); ?></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="semrs-tools-limit"><?php esc_html_e( 'Checks per visitor per hour', 'semrs-tools' ); ?></label></th><td><input id="semrs-tools-limit" type="number" min="1" max="<?php echo $pro ? 500 : 60; ?>" name="<?php echo esc_attr( $n ); ?>[limit]" value="<?php echo (int) $o['limit']; ?>" class="small-text"> <span class="description"><?php echo $pro ? esc_html__( 'Up to 500 with Pro.', 'semrs-tools' ) : esc_html__( 'Up to 60 (500 with Free Tools Pro). 30 suits most sites.', 'semrs-tools' ); ?></span></td></tr>
			<tr><th scope="row"><label for="semrs-tools-cache"><?php esc_html_e( 'Reuse a result for (minutes)', 'semrs-tools' ); ?></label></th><td><input id="semrs-tools-cache" type="number" min="0" max="1440" name="<?php echo esc_attr( $n ); ?>[cache]" value="<?php echo (int) $o['cache']; ?>" class="small-text"> <span class="description"><?php esc_html_e( 'The same URL checked again within this time is answered from memory: faster, and kinder to the other site. 0 = always fetch.', 'semrs-tools' ); ?></span></td></tr>
		</table>

		<h2 id="pro"><?php esc_html_e( 'Free Tools Pro', 'semrs-tools' ); ?></h2>
		<?php if ( ! $pro ) : ?>
			<p class="description"><?php esc_html_e( 'With SEMRS Pro (SEO Pro add-on), switch on "Free Tools Pro" under SEMRS → Features for bulk checks of up to 25 URLs with CSV in the Redirect and On-Page checkers, your own expert-help box per category, a usage report and higher hourly limits.', 'semrs-tools' ); ?></p>
		<?php else : ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Bulk checks', 'semrs-tools' ); ?></th><td><?php semrs_tools_admin_box( 'bulk', __( 'Let visitors check up to 25 URLs at once in the Redirect and On-Page checkers', 'semrs-tools' ), __( 'Each URL counts toward the hourly limit.', 'semrs-tools' ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Usage report', 'semrs-tools' ); ?></th><td><?php semrs_tools_admin_box( 'usage', __( 'Count tool use, runs, copies, downloads and expert-box clicks', 'semrs-tools' ), __( 'Totals per tool and day only; no IP addresses or personal data.', 'semrs-tools' ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Your own expert-help box', 'semrs-tools' ); ?></th><td>
					<p class="description"><?php esc_html_e( 'Leave the link empty to keep the default box for that category.', 'semrs-tools' ); ?></p>
					<table class="widefat striped" style="max-width:1000px"><thead><tr><th><?php esc_html_e( 'Category', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Text', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Link text', 'semrs-tools' ); ?></th><th><?php esc_html_e( 'Link', 'semrs-tools' ); ?></th></tr></thead><tbody>
					<?php
					foreach ( $cats as $slug => $cat ) :
						$c = $o['cta_custom'][ $slug ] ?? array();
						$f = $n . '[cta_custom][' . $slug . ']';
						?>
						<tr><td><?php echo esc_html( $cat[0] ); ?></td>
						<td><input type="text" class="widefat" name="<?php echo esc_attr( $f . '[text]' ); ?>" value="<?php echo esc_attr( $c['text'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Need help with this?', 'semrs-tools' ); ?>" aria-label="<?php echo esc_attr( $cat[0] . ': ' . __( 'Text', 'semrs-tools' ) ); ?>"></td>
						<td><input type="text" class="widefat" name="<?php echo esc_attr( $f . '[label]' ); ?>" value="<?php echo esc_attr( $c['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Book a call', 'semrs-tools' ); ?>" aria-label="<?php echo esc_attr( $cat[0] . ': ' . __( 'Link text', 'semrs-tools' ) ); ?>"></td>
						<td><input type="url" class="widefat" name="<?php echo esc_attr( $f . '[url]' ); ?>" value="<?php echo esc_attr( $c['url'] ?? '' ); ?>" placeholder="https://" aria-label="<?php echo esc_attr( $cat[0] . ': ' . __( 'Link', 'semrs-tools' ) ); ?>"></td></tr>
					<?php endforeach; ?>
					</tbody></table>
				</td></tr>
			</table>
		<?php endif; ?>
		<?php submit_button(); ?>
	</form>
	<?php
}

/**
 * Usage report tab (Pro): last 7 and 30 days per tool.
 */
function semrs_tools_admin_usage() {
	if ( ! semrs_tools_is_pro() ) {
		echo '<p>' . esc_html__( 'The usage report shows which tools your visitors use, how often they run them, copy or download results and click your expert-help box. It is part of Free Tools Pro (SEMRS Pro, SEO Pro add-on).', 'semrs-tools' ) . '</p>';
		return;
	}
	if ( '1' !== semrs_tools_options()['usage'] ) {
		echo '<p>' . esc_html__( 'Counting is switched off on the Settings tab.', 'semrs-tools' ) . '</p>';
		return;
	}
	$data  = (array) get_option( 'semrs_tools_usage', array() );
	$tools = semrs_tools_list();
	$sum   = array();
	$week  = gmdate( 'Y-m-d', time() - 6 * DAY_IN_SECONDS );
	foreach ( $data as $day => $per ) {
		foreach ( (array) $per as $id => $ev ) {
			foreach ( (array) $ev as $k => $v ) {
				$sum[ $id ][ $k ] = ( $sum[ $id ][ $k ] ?? 0 ) + (int) $v;
				if ( 'use' === $k && $day >= $week ) {
					$sum[ $id ]['week'] = ( $sum[ $id ]['week'] ?? 0 ) + (int) $v;
				}
			}
		}
	}
	uasort(
		$sum,
		function ( $a, $b ) {
			return ( $b['use'] ?? 0 ) - ( $a['use'] ?? 0 );
		}
	);
	echo '<p>' . esc_html__( 'Last 30 days. Each action counts once per page view, so refreshing results does not add up. No IP addresses or personal data are stored.', 'semrs-tools' ) . '</p>';
	echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>' . esc_html__( 'Tool', 'semrs-tools' ) . '</th><th>' . esc_html__( 'Used (7 days)', 'semrs-tools' ) . '</th><th>' . esc_html__( 'Used (30 days)', 'semrs-tools' ) . '</th><th>' . esc_html__( 'Runs', 'semrs-tools' ) . '</th><th>' . esc_html__( 'Copies', 'semrs-tools' ) . '</th><th>' . esc_html__( 'Downloads', 'semrs-tools' ) . '</th><th>' . esc_html__( 'Expert-box clicks', 'semrs-tools' ) . '</th></tr></thead><tbody>';
	if ( ! $sum ) {
		echo '<tr><td colspan="7">' . esc_html__( 'No tool use counted yet.', 'semrs-tools' ) . '</td></tr>';
	}
	foreach ( $sum as $id => $s ) {
		if ( ! isset( $tools[ $id ] ) ) {
			continue;
		}
		echo '<tr><td>' . esc_html( $tools[ $id ][0] ) . '</td>';
		foreach ( array( 'week', 'use', 'run', 'copy', 'download', 'cta' ) as $k ) {
			echo '<td>' . (int) ( $s[ $k ] ?? 0 ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
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
		array_unshift( $links, '<a href="' . esc_url( add_query_arg( 'tab', 'settings', $url ) ) . '">' . esc_html__( 'Settings', 'semrs-tools' ) . '</a>' );
	}
	return $links;
}
add_filter( 'plugin_action_links_semrs-tools/semrs-tools.php', 'semrs_tools_action_links' );
