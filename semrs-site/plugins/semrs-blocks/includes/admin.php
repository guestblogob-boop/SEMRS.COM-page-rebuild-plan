<?php
/**
 * SEMRS → Blocks: switch blocks on or off, see what each one does, and see
 * which blocks SEMRS Pro adds. A switched-off block is not registered, so it
 * adds nothing to the editor or the site.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu: under SEMRS when SEMRS Core is active, otherwise its own item.
 */
function semrs_blocks_admin_menu() {
	$title = __( 'SEMRS Blocks', 'semrs-blocks' );
	if ( defined( 'SEMRS_CORE_VERSION' ) ) {
		add_submenu_page( 'semrs', $title, __( 'Blocks', 'semrs-blocks' ), 'manage_options', 'semrs-blocks', 'semrs_blocks_admin_page' );
	} else {
		add_menu_page( $title, $title, 'manage_options', 'semrs-blocks', 'semrs_blocks_admin_page', 'dashicons-block-default', 59 );
	}
}
add_action( 'admin_menu', 'semrs_blocks_admin_menu', 20 );

/**
 * Settings link on the Plugins screen.
 *
 * @param array $links Links.
 * @return array
 */
function semrs_blocks_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=semrs-templates' ) ) . '">' . esc_html__( 'Templates', 'semrs-blocks' ) . '</a>' );
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=semrs-blocks' ) ) . '">' . esc_html__( 'Settings', 'semrs-blocks' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_semrs-blocks/semrs-blocks.php', 'semrs_blocks_action_links' );

/**
 * Card details: name => [group, short description].
 *
 * @return array
 */
function semrs_blocks_card_info() {
	return apply_filters(
		'semrs_blocks_card_info',
		array(
			'semrs/icon'          => array( 'design', __( '48 SVG icons with colour, size, background shape and link.', 'semrs-blocks' ) ),
			'semrs/icon-list'     => array( 'design', __( 'Feature lists and checklists in 1 to 4 columns.', 'semrs-blocks' ) ),
			'semrs/fancy-heading' => array( 'design', __( 'Typing, gradient, highlight, underline and outline headings.', 'semrs-blocks' ) ),
			'semrs/posts'         => array( 'posts', __( 'Grid, list, masonry, overlay and compact post layouts.', 'semrs-blocks' ) ),
			'semrs/countdown'     => array( 'marketing', __( 'Countdown to a date, with a message when it ends.', 'semrs-blocks' ) ),
			'semrs/pricing'       => array( 'marketing', __( 'Pricing card with included / not included features and a badge.', 'semrs-blocks' ) ),
			'semrs/service-card'  => array( 'marketing', __( 'Service or feature box with icon or image.', 'semrs-blocks' ) ),
			'semrs/media-sources' => array( 'media', __( 'Directory of free photo, video, icon, animation and 3D sites with licence notes.', 'semrs-blocks' ) ),
			'semrs/contact-form'  => array( 'forms', __( 'Contact form with country picker, WhatsApp opt-in, budget and requirements. Saves leads.', 'semrs-blocks' ) ),
			'semrs/container'     => array( 'layout', __( 'Sections, rows and grids with background colour, gradient or image, spacing and width.', 'semrs-blocks' ) ),
			'semrs/tabs'          => array( 'layout', __( 'Tabbed content; each tab holds any blocks. Keyboard accessible, all content visible to Google.', 'semrs-blocks' ) ),
			'semrs/accordion'     => array( 'layout', __( 'FAQ and toggles with native open/close; builds FAQ schema with SEMRS Core.', 'semrs-blocks' ) ),
			'semrs/gallery'       => array( 'media', __( 'Grid or masonry gallery with an accessible lightbox and swipe.', 'semrs-blocks' ) ),
			'semrs/testimonial'   => array( 'marketing', __( 'Real customer quote with name, role, photo, stars and a link to the original review.', 'semrs-blocks' ) ),
			'semrs/showcase'      => array( 'media', __( 'Theme, template or portfolio cards with desktop and mobile previews, like the WordPress.org theme directory.', 'semrs-blocks' ) ),
			'semrs/toc'           => array( 'posts', __( 'Automatic table of contents from the headings on the page.', 'semrs-blocks' ) ),
			'semrs/slider'        => array( 'media', __( 'Image slider or post carousel with arrows, dots, swipe and autoplay.', 'semrs-blocks' ) ),
			'semrs/counter'       => array( 'marketing', __( 'Numbers that count up when they scroll into view.', 'semrs-blocks' ) ),
			'semrs/cta'         => array( 'marketing', __( 'Headline, text and one or two buttons in a coloured box.', 'semrs-blocks' ) ),
			'semrs/buttons'     => array( 'design', __( 'A row of buttons with icons in three styles.', 'semrs-blocks' ) ),
			'semrs/alert'       => array( 'design', __( 'Info, success, warning or error notice, optionally dismissible.', 'semrs-blocks' ) ),
			'semrs/divider'     => array( 'design', __( 'Separator line with optional text or icon.', 'semrs-blocks' ) ),
			'semrs/rating'      => array( 'marketing', __( 'Star rating with half stars and a label.', 'semrs-blocks' ) ),
			'semrs/team'        => array( 'marketing', __( 'Team member with photo, role, bio and profile links.', 'semrs-blocks' ) ),
			'semrs/hours'       => array( 'marketing', __( 'Opening hours table.', 'semrs-blocks' ) ),
			'semrs/map'         => array( 'media', __( 'Google Map of an address, loaded only on click. No API key.', 'semrs-blocks' ) ),
			'semrs/social'      => array( 'marketing', __( 'Follow links to your social profiles.', 'semrs-blocks' ) ),
			'semrs/logos'       => array( 'media', __( 'Client and partner logos in a grid.', 'semrs-blocks' ) ),
			'semrs/steps'       => array( 'posts', __( 'Numbered steps that explain a process.', 'semrs-blocks' ) ),
			'semrs/timeline'    => array( 'posts', __( 'Dates and events on a vertical line.', 'semrs-blocks' ) ),
			'semrs/progress'    => array( 'design', __( 'Labelled progress bars.', 'semrs-blocks' ) ),
			'semrs/table'       => array( 'posts', __( 'Comparison table with ticks and a highlighted column.', 'semrs-blocks' ) ),
			'semrs/share'       => array( 'marketing', __( 'Share buttons for the current page, no tracking scripts.', 'semrs-blocks' ) ),
			'semrs/howto'       => array( 'posts', __( 'Step-by-step guide with HowTo schema.', 'semrs-blocks' ) ),
			'semrs/review'      => array( 'posts', __( 'Review box with score, pros, cons and Review schema.', 'semrs-blocks' ) ),
			'semrs/breadcrumbs' => array( 'posts', __( 'Breadcrumb trail for the current page.', 'semrs-blocks' ) ),
			'semrs/modal'       => array( 'marketing', __( 'Popup with any blocks inside, opened by a button or after a delay.', 'semrs-blocks' ) ),
			'semrs/pricing-switch' => array( 'marketing', __( 'Plans with a monthly / yearly switch.', 'semrs-blocks' ) ),
			'semrs/sticky-bar'  => array( 'marketing', __( 'Sticky announcement bar with a button.', 'semrs-blocks' ) ),
			'semrs/before-after' => array( 'media', __( 'Drag to compare two images.', 'semrs-blocks' ) ),
			'semrs/hotspots'    => array( 'media', __( 'Points on an image with notes.', 'semrs-blocks' ) ),
			'semrs/lottie'      => array( 'media', __( 'Lottie vector animations.', 'semrs-blocks' ) ),
			'semrs/marquee'     => array( 'media', __( 'Endless scrolling logos or phrases.', 'semrs-blocks' ) ),
			'semrs/video-popup' => array( 'media', __( 'Video that opens in a popup on click.', 'semrs-blocks' ) ),
			'semrs/switcher'    => array( 'layout', __( 'Toggle between two contents.', 'semrs-blocks' ) ),
			'semrs/data-table'  => array( 'posts', __( 'Sortable, searchable data table.', 'semrs-blocks' ) ),
			'semrs/timeline-pro' => array( 'posts', __( 'Timeline with icons and scroll animation.', 'semrs-blocks' ) ),
			'semrs/reviews'     => array( 'marketing', __( 'Slider of real reviews with stars.', 'semrs-blocks' ) ),
			'semrs/badges'      => array( 'marketing', __( 'Trust badges: secure checkout, refunds, support.', 'semrs-blocks' ) ),
			'semrs/product'       => array( 'marketing', __( 'Product with price, options, swatches, bundles and a buy box.', 'semrs-blocks' ) ),
			'semrs/model-3d'      => array( 'media', __( '3D models with rotate, zoom and AR on phones.', 'semrs-blocks' ) ),
		)
	);
}

/**
 * The Blocks manual section that covers a block.
 *
 * @param string $name  Block name.
 * @param string $tier  free or pro.
 * @param string $group Card group.
 * @return string
 */
function semrs_blocks_manual_url( $name, $tier, $group ) {
	$base = apply_filters( 'semrs_docs_url', 'https://semrs.com/support/' ) . 'blocks/';
	if ( 'pro' === $tier ) {
		$anchor = 'pro-blocks-need-semrs-pro';
	} elseif ( 'semrs/contact-form' === $name ) {
		$anchor = 'set-up-the-contact-form';
	} elseif ( in_array( $name, array( 'semrs/container', 'semrs/tabs', 'semrs/accordion', 'semrs/gallery', 'semrs/testimonial', 'semrs/toc', 'semrs/showcase' ), true ) ) {
		$anchor = 'layout-blocks-free';
	} else {
		$anchor = 'content-blocks-free';
	}
	return $base . '#' . $anchor;
}

/**
 * "Try it" from the Blocks screen: a new draft page opens with the block
 * already inserted and its settings sidebar open.
 */
function semrs_blocks_try_script() {
	$name = isset( $_GET['semrs_try'] ) ? sanitize_text_field( wp_unslash( $_GET['semrs_try'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only: only inserts a block into the editor.
	if ( ! preg_match( '#^semrs/[a-z0-9-]+$#', $name ) ) {
		return;
	}
	$js = '(function(){var n=' . wp_json_encode( $name ) . ',done=false;var d=wp.data;var u=d.subscribe(function(){if(done||!d.select("core/editor").getCurrentPostId()||!wp.blocks.getBlockType(n))return;done=true;u();setTimeout(function(){var b=wp.blocks.createBlock(n);d.dispatch("core/block-editor").insertBlocks(b);d.dispatch("core/block-editor").selectBlock(b.clientId);try{d.dispatch("core/edit-post").openGeneralSidebar("edit-post/block");}catch(e){}},300);});})();';
	wp_add_inline_script( 'wp-edit-post', $js );
}
add_action( 'enqueue_block_editor_assets', 'semrs_blocks_try_script' );

/**
 * Save the Blocks screen.
 */
function semrs_blocks_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-blocks' ) );
	}
	check_admin_referer( 'semrs_blocks_save' );
	$raw = isset( $_POST['semrs_blocks_on'] ) ? (array) map_deep( wp_unslash( $_POST['semrs_blocks_on'] ), 'sanitize_text_field' ) : array();
	$on  = array_map( 'sanitize_text_field', array_keys( $raw ) );
	$top = array_keys( array_filter( semrs_blocks_all(), function ( $b ) { return empty( $b['parent'] ); } ) ); // Child blocks follow their parent.
	$off = array_values( array_diff( $top, $on ) );
	update_option( 'semrs_blocks_disabled', $off, false );
	update_option( 'semrs_blocks_show_pro', ! empty( $_POST['semrs_blocks_show_pro'] ), false );
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-blocks', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_blocks_save', 'semrs_blocks_admin_save' );

/**
 * Assets for the Blocks screen (same look as SEMRS → Features).
 *
 * @param string $hook Screen hook.
 */
function semrs_blocks_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'semrs-blocks' ) ) {
		return;
	}
	wp_enqueue_style( 'semrs-blocks-admin', SEMRS_BLOCKS_URL . 'assets/admin/admin.css', array(), SEMRS_BLOCKS_VERSION );
	wp_enqueue_script( 'semrs-blocks-admin', SEMRS_BLOCKS_URL . 'assets/admin/admin.js', array(), SEMRS_BLOCKS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'semrs_blocks_admin_assets' );

/**
 * Render the Blocks screen.
 */
function semrs_blocks_admin_page() {
	$all     = semrs_blocks_all();
	$off     = (array) get_option( 'semrs_blocks_disabled', array() );
	$info    = semrs_blocks_card_info();
	$has_pro = semrs_blocks_has_pro();
	$groups  = array(
		'layout'    => __( 'Layout', 'semrs-blocks' ),
		'design'    => __( 'Design', 'semrs-blocks' ),
		'posts'     => __( 'Posts', 'semrs-blocks' ),
		'marketing' => __( 'Marketing', 'semrs-blocks' ),
		'media'     => __( 'Media', 'semrs-blocks' ),
		'forms'     => __( 'Forms', 'semrs-blocks' ),
	);
	$cards   = array();
	foreach ( $all as $name => $b ) {
		if ( ! empty( $b['parent'] ) ) {
			continue; // Child blocks (Tab, Accordion item) live inside their parent.
		}
		$cards[ $name ] = array( preg_replace( '/\s*\(Pro\)$/', '', $b['title'] ), $b['icon'] ?? 'block-default', $b['tier'] ?? 'free', false );
	}
	if ( ! $has_pro ) {
		foreach ( semrs_blocks_pro_teasers() as $name => $t ) {
			if ( ! isset( $cards[ $name ] ) ) {
				$cards[ $name ] = array( $t[0], $t[1], 'pro', true );
			}
		}
	}
	?>
	<div class="wrap semrs-ui">
		<div class="semrs-head">
			<div>
				<h1><?php esc_html_e( 'SEMRS Blocks', 'semrs-blocks' ); ?> <span class="semrs-ver"><?php echo esc_html( SEMRS_BLOCKS_VERSION ); ?></span></h1>
				<p><?php esc_html_e( 'Find them in the editor under the SEMRS category, or type / and the block name. Switch off blocks you do not use to keep the editor tidy.', 'semrs-blocks' ); ?></p>
			</div>
			<div class="semrs-head__links">
				<a class="button" href="<?php echo esc_url( apply_filters( 'semrs_docs_url', 'https://semrs.com/support/' ) . 'blocks/' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Blocks manual', 'semrs-blocks' ); ?></a>
				<?php if ( ! $has_pro ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( semrs_blocks_pro_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get SEMRS Pro', 'semrs-blocks' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<hr class="wp-header-end">
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible inline"><p><?php esc_html_e( 'Saved.', 'semrs-blocks' ); ?></p></div>
		<?php endif; ?>

		<div class="semrs-toolbar" role="search">
			<label class="screen-reader-text" for="semrs-block-search"><?php esc_html_e( 'Search blocks', 'semrs-blocks' ); ?></label>
			<input type="search" id="semrs-block-search" placeholder="<?php esc_attr_e( 'Search blocks…', 'semrs-blocks' ); ?>">
			<div class="semrs-chips" role="group" aria-label="<?php esc_attr_e( 'Filter', 'semrs-blocks' ); ?>">
				<button type="button" class="semrs-chip is-active" data-filter="all"><?php esc_html_e( 'All', 'semrs-blocks' ); ?></button>
				<button type="button" class="semrs-chip" data-filter="tier:free"><?php esc_html_e( 'Free', 'semrs-blocks' ); ?></button>
				<button type="button" class="semrs-chip" data-filter="tier:pro"><?php esc_html_e( 'Pro', 'semrs-blocks' ); ?></button>
				<?php foreach ( $groups as $g => $label ) : ?>
					<button type="button" class="semrs-chip" data-filter="group:<?php echo esc_attr( $g ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="semrs_blocks_save">
			<?php wp_nonce_field( 'semrs_blocks_save' ); ?>
			<div class="semrs-grid">
				<?php
				foreach ( $cards as $name => $c ) {
					list( $title, $icon, $tier, $locked ) = $c;
					$group = $info[ $name ][0] ?? 'design';
					$desc  = $info[ $name ][1] ?? '';
					$on    = ! $locked && ! in_array( $name, $off, true );
					printf( '<div class="semrs-card%1$s" data-tier="%2$s" data-group="%3$s" data-search="%4$s">', $locked ? ' is-locked' : ( $on ? ' is-on' : '' ), esc_attr( $tier ), esc_attr( $group ), esc_attr( strtolower( $title . ' ' . $desc . ' ' . $name ) ) );
					echo '<div class="semrs-card__top"><span class="dashicons dashicons-' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
					echo '<span class="semrs-badge semrs-badge--' . esc_attr( $tier ) . '">' . ( 'pro' === $tier ? esc_html__( 'PRO', 'semrs-blocks' ) : esc_html__( 'FREE', 'semrs-blocks' ) ) . '</span>';
					if ( $locked ) {
						echo '<span class="semrs-lock dashicons dashicons-lock" title="' . esc_attr__( 'Included in SEMRS Pro', 'semrs-blocks' ) . '"></span>';
					} else {
						/* translators: %s: block name */
						printf( '<label class="semrs-switch"><input type="checkbox" name="semrs_blocks_on[%1$s]" value="1" %2$s><span class="semrs-switch__ui" aria-hidden="true"></span><span class="screen-reader-text">%3$s</span></label>', esc_attr( $name ), checked( $on, true, false ), esc_html( sprintf( __( 'Turn %s on or off', 'semrs-blocks' ), $title ) ) );
					}
					echo '</div><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $desc ) . '</p><div class="semrs-card__foot">';
					if ( $locked ) {
						echo '<a class="button button-primary" href="' . esc_url( semrs_blocks_pro_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Unlock with Pro', 'semrs-blocks' ) . '</a>';
					} elseif ( $on ) {
						/* translators: %s: block name */
						echo '<a class="button button-primary" href="' . esc_url( add_query_arg( array( 'post_type' => 'page', 'semrs_try' => $name ), admin_url( 'post-new.php' ) ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Try %s in a new draft page', 'semrs-blocks' ), $title ) ) . '">' . esc_html__( 'Try it', 'semrs-blocks' ) . '</a>';
					} else {
						echo '<span class="semrs-card__off">' . esc_html__( 'Switched off', 'semrs-blocks' ) . '</span>';
					}
					/* translators: %s: block name */
					echo '<a class="button" href="' . esc_url( semrs_blocks_manual_url( $name, $tier, $group ) ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( __( 'How to use %s (opens the manual)', 'semrs-blocks' ), $title ) ) . '">' . esc_html__( 'How to use', 'semrs-blocks' ) . '</a>';
					echo '</div></div>';
				}
				?>
			</div>
			<p class="semrs-empty-msg" hidden><?php esc_html_e( 'No blocks match your search.', 'semrs-blocks' ); ?></p>
			<?php if ( ! $has_pro ) : ?>
				<p><label><input type="checkbox" name="semrs_blocks_show_pro" value="1" <?php checked( get_option( 'semrs_blocks_show_pro', true ) ); ?>> <?php esc_html_e( 'Show SEMRS Pro blocks in the editor (marked PRO, they print nothing on your site)', 'semrs-blocks' ); ?></label></p>
			<?php else : ?>
				<input type="hidden" name="semrs_blocks_show_pro" value="<?php echo get_option( 'semrs_blocks_show_pro', true ) ? '1' : ''; ?>">
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'A switched-off block shows nothing where it was used, and the editor offers to remove it. Switch it back on to restore it with all its settings.', 'semrs-blocks' ); ?></p>
			<div class="semrs-savebar"><?php submit_button( __( 'Save changes', 'semrs-blocks' ), 'primary', 'submit', false ); ?></div>
		</form>
	</div>
	<?php
}
