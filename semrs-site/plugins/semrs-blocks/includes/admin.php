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
			'semrs/model-3d'      => array( 'media', __( '3D models with rotate, zoom and AR on phones.', 'semrs-blocks' ) ),
		)
	);
}

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
	$has_pro = defined( 'SEMRS_PRO_VERSION' );
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
					}
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
