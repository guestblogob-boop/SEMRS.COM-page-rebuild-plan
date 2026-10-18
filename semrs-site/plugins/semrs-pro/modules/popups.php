<?php
/**
 * Popups (Pro): build popups with the block editor (SEMRS → Popups) and
 * show them centred, as a bottom bar or as a corner slide-in.
 *
 * Triggers: after a delay, after scrolling, exit intent (desktop), or a click
 * on any link to #semrs-popup-ID. Frequency: every view, once per visit, or
 * once every N days. Targeting uses the Display conditions rules.
 *
 * Built to Google's rules: on phones a centred popup opens as a bottom sheet
 * (no full-screen interstitials), every popup has a visible close button and
 * closes with Esc, and ad shortcodes are never rendered inside popups
 * (AdSense does not allow ads in pop-ups).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Popup post type (not public; edited with the block editor).
 */
function semrs_pro_popup_type() {
	register_post_type(
		'semrs_popup',
		array(
			'labels'              => array(
				'name'          => __( 'Popups', 'semrs-pro' ),
				'singular_name' => __( 'Popup', 'semrs-pro' ),
				'add_new_item'  => __( 'Add popup', 'semrs-pro' ),
				'edit_item'     => __( 'Edit popup', 'semrs-pro' ),
				'menu_name'     => __( 'Popups', 'semrs-pro' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => defined( 'SEMRS_CORE_VERSION' ) ? 'semrs' : true,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'supports'            => array( 'title', 'editor', 'custom-fields' ),
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'semrs_pro_popup_type' );

/**
 * Popup settings with defaults.
 *
 * @param int $id Popup ID.
 * @return array
 */
function semrs_pro_popup_settings( $id ) {
	$s = get_post_meta( $id, '_semrs_popup', true );
	return wp_parse_args(
		is_array( $s ) ? $s : array(),
		array(
			'position' => 'center',
			'width'    => 560,
			'trigger'  => 'delay',
			'delay'    => 8,
			'scroll'   => 50,
			'freq'     => 'days',
			'days'     => 7,
			'devices'  => array( 'desktop', 'tablet', 'mobile' ),
			'overlay'  => true,
			'showTo'   => '',
			'pages'    => array(),
			'ids'      => '',
			'exclude'  => '', // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- a targeting rule, not a query.
		)
	);
}

/**
 * Settings box.
 */
function semrs_pro_popup_box() {
	add_meta_box( 'semrs_popup_settings', __( 'Popup settings', 'semrs-pro' ), 'semrs_pro_popup_box_render', 'semrs_popup', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'semrs_pro_popup_box' );

/**
 * Render the settings box.
 *
 * @param WP_Post $post Popup.
 */
function semrs_pro_popup_box_render( $post ) {
	$s = semrs_pro_popup_settings( $post->ID );
	wp_nonce_field( 'semrs_popup_save', 'semrs_popup_nonce' );
	$sel = function ( $name, $options, $value ) {
		echo '<select name="semrs_popup[' . esc_attr( $name ) . ']">';
		foreach ( $options as $k => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( (string) $value, (string) $k, false ), esc_html( $label ) );
		}
		echo '</select>';
	};
	?>
	<table class="form-table" role="presentation">
		<tr><th><?php esc_html_e( 'Style', 'semrs-pro' ); ?></th><td>
			<?php $sel( 'position', array( 'center' => __( 'Centred box (bottom sheet on phones)', 'semrs-pro' ), 'bar' => __( 'Bottom bar', 'semrs-pro' ), 'corner' => __( 'Slide-in, bottom right', 'semrs-pro' ) ), $s['position'] ); ?>
			<label style="margin-left:12px"><?php esc_html_e( 'Width (px)', 'semrs-pro' ); ?> <input type="number" min="280" max="1100" name="semrs_popup[width]" value="<?php echo esc_attr( $s['width'] ); ?>" style="width:90px"></label>
			<label style="margin-left:12px"><input type="checkbox" name="semrs_popup[overlay]" value="1" <?php checked( $s['overlay'] ); ?>> <?php esc_html_e( 'Dark overlay behind (centred only)', 'semrs-pro' ); ?></label>
		</td></tr>
		<tr><th><?php esc_html_e( 'Open', 'semrs-pro' ); ?></th><td>
			<?php $sel( 'trigger', array( 'delay' => __( 'After a delay', 'semrs-pro' ), 'scroll' => __( 'After scrolling', 'semrs-pro' ), 'exit' => __( 'When leaving (exit intent, desktop)', 'semrs-pro' ), 'click' => __( 'Only when a link is clicked', 'semrs-pro' ) ), $s['trigger'] ); ?>
			<label style="margin-left:12px"><?php esc_html_e( 'Delay (s)', 'semrs-pro' ); ?> <input type="number" min="0" max="600" name="semrs_popup[delay]" value="<?php echo esc_attr( $s['delay'] ); ?>" style="width:80px"></label>
			<label style="margin-left:12px"><?php esc_html_e( 'Scroll (%)', 'semrs-pro' ); ?> <input type="number" min="5" max="100" name="semrs_popup[scroll]" value="<?php echo esc_attr( $s['scroll'] ); ?>" style="width:80px"></label>
			<p class="description">
				<?php
				/* translators: %s: link example */
				printf( esc_html__( 'Any link to %s also opens this popup.', 'semrs-pro' ), '<code>#semrs-popup-' . (int) $post->ID . '</code>' );
				?>
			</p>
		</td></tr>
		<tr><th><?php esc_html_e( 'How often', 'semrs-pro' ); ?></th><td>
			<?php $sel( 'freq', array( 'always' => __( 'Every page view', 'semrs-pro' ), 'session' => __( 'Once per visit', 'semrs-pro' ), 'days' => __( 'Once every N days', 'semrs-pro' ) ), $s['freq'] ); ?>
			<label style="margin-left:12px">N = <input type="number" min="1" max="365" name="semrs_popup[days]" value="<?php echo esc_attr( $s['days'] ); ?>" style="width:80px"></label>
			<p class="description"><?php esc_html_e( 'Remembered in the visitor\'s browser (local storage); no cookie is set.', 'semrs-pro' ); ?></p>
		</td></tr>
		<tr><th><?php esc_html_e( 'Devices', 'semrs-pro' ); ?></th><td>
			<?php foreach ( array( 'desktop' => __( 'Desktop', 'semrs-pro' ), 'tablet' => __( 'Tablet', 'semrs-pro' ), 'mobile' => __( 'Phone', 'semrs-pro' ) ) as $k => $label ) : ?>
				<label style="margin-right:12px"><input type="checkbox" name="semrs_popup[devices][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $s['devices'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
			<p class="description"><?php esc_html_e( 'Google may rank pages lower on phones when a popup covers the content as soon as the page opens. Prefer the bottom bar, a scroll trigger or a click trigger on phones.', 'semrs-pro' ); ?></p>
		</td></tr>
		<tr><th><?php esc_html_e( 'Show to', 'semrs-pro' ); ?></th><td>
			<?php $sel( 'showTo', array( '' => __( 'Everyone', 'semrs-pro' ), 'out' => __( 'Logged-out visitors', 'semrs-pro' ), 'in' => __( 'Logged-in visitors', 'semrs-pro' ) ), $s['showTo'] ); ?>
		</td></tr>
		<tr><th><?php esc_html_e( 'Show on', 'semrs-pro' ); ?></th><td>
			<?php foreach ( semrs_pro_page_types() as $k => $label ) : ?>
				<label style="margin-right:12px;display:inline-block"><input type="checkbox" name="semrs_popup[pages][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $s['pages'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
			<p><label><?php esc_html_e( 'Or only these page/post IDs:', 'semrs-pro' ); ?> <input type="text" name="semrs_popup[ids]" value="<?php echo esc_attr( $s['ids'] ); ?>" placeholder="12, 45"></label>
			<label style="margin-left:12px"><?php esc_html_e( 'Never on IDs:', 'semrs-pro' ); ?> <input type="text" name="semrs_popup[exclude]" value="<?php echo esc_attr( $s['exclude'] ); ?>" placeholder="7"></label></p>
			<p class="description"><?php esc_html_e( 'Nothing ticked and no IDs = every page.', 'semrs-pro' ); ?></p>
		</td></tr>
	</table>
	<?php
}

/**
 * Save settings.
 *
 * @param int $post_id Popup ID.
 */
function semrs_pro_popup_save( $post_id ) {
	if ( ! isset( $_POST['semrs_popup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_popup_nonce'] ) ), 'semrs_popup_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in   = isset( $_POST['semrs_popup'] ) ? (array) wp_unslash( $_POST['semrs_popup'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per key below.
	$list = function ( $v, $allowed ) {
		return array_values( array_intersect( array_map( 'sanitize_key', (array) $v ), $allowed ) );
	};
	$ids  = function ( $v ) {
		return implode( ', ', array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $v ) ) ) );
	};
	update_post_meta(
		$post_id,
		'_semrs_popup',
		array(
			'position' => in_array( $in['position'] ?? '', array( 'center', 'bar', 'corner' ), true ) ? $in['position'] : 'center',
			'width'    => max( 280, min( 1100, absint( $in['width'] ?? 560 ) ) ),
			'trigger'  => in_array( $in['trigger'] ?? '', array( 'delay', 'scroll', 'exit', 'click' ), true ) ? $in['trigger'] : 'delay',
			'delay'    => min( 600, absint( $in['delay'] ?? 8 ) ),
			'scroll'   => max( 5, min( 100, absint( $in['scroll'] ?? 50 ) ) ),
			'freq'     => in_array( $in['freq'] ?? '', array( 'always', 'session', 'days' ), true ) ? $in['freq'] : 'days',
			'days'     => max( 1, min( 365, absint( $in['days'] ?? 7 ) ) ),
			'devices'  => $list( $in['devices'] ?? array(), array( 'desktop', 'tablet', 'mobile' ) ),
			'overlay'  => ! empty( $in['overlay'] ),
			'showTo'   => in_array( $in['showTo'] ?? '', array( '', 'in', 'out' ), true ) ? $in['showTo'] : '',
			'pages'    => $list( $in['pages'] ?? array(), array_keys( semrs_pro_page_types() ) ),
			'ids'      => $ids( $in['ids'] ?? '' ),
			'exclude'  => $ids( $in['exclude'] ?? '' ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- a targeting rule, not a query.
		)
	);
	delete_transient( 'semrs_popups' );
}
add_action( 'save_post_semrs_popup', 'semrs_pro_popup_save' );
add_action( 'trashed_post', function () { delete_transient( 'semrs_popups' ); } );

/**
 * Published popup IDs (cached).
 *
 * @return int[]
 */
function semrs_pro_popup_ids() {
	$ids = get_transient( 'semrs_popups' );
	if ( false === $ids ) {
		$ids = get_posts( array( 'post_type' => 'semrs_popup', 'post_status' => 'publish', 'numberposts' => 50, 'fields' => 'ids', 'no_found_rows' => true ) );
		set_transient( 'semrs_popups', $ids, DAY_IN_SECONDS );
	}
	return array_map( 'intval', (array) $ids );
}

/**
 * Print matching popups at the end of the page.
 */
function semrs_pro_popups_footer() {
	if ( is_admin() || is_feed() || is_embed() ) {
		return;
	}
	$out = '';
	foreach ( semrs_pro_popup_ids() as $id ) {
		$s = semrs_pro_popup_settings( $id );
		if ( empty( $s['devices'] ) ) {
			continue;
		}
		$rules = array(
			'showTo'  => $s['showTo'],
			'pages'   => $s['pages'],
			'ids'     => preg_split( '/[\s,]+/', (string) $s['ids'] ),
			'exclude' => preg_split( '/[\s,]+/', (string) $s['exclude'] ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- a targeting rule, not a query.
		);
		if ( ! semrs_pro_conditions_pass( $rules ) ) {
			continue;
		}
		$post = get_post( $id );
		// No ads inside popups (AdSense policy): drop ad shortcodes before rendering.
		$body = preg_replace( '/\[semrs_ad[^\]]*\]/', '', (string) $post->post_content );
		$body = do_shortcode( do_blocks( $body ) );
		$out .= sprintf(
			'<div class="semrs-popup semrs-popup--%1$s%2$s" id="semrs-popup-%3$d" tabindex="-1" role="dialog" aria-modal="%4$s" aria-label="%5$s" data-trigger="%6$s" data-delay="%7$d" data-scroll="%8$d" data-freq="%9$s" data-days="%10$d" data-devices="%11$s" hidden><div class="semrs-popup__box" style="--pop-w:%12$dpx"><button type="button" class="semrs-popup__close" aria-label="%13$s">×</button><div class="semrs-popup__body">%14$s</div></div></div>',
			esc_attr( $s['position'] ),
			'center' === $s['position'] && $s['overlay'] ? ' has-overlay' : '',
			$id,
			'center' === $s['position'] ? 'true' : 'false',
			esc_attr( get_the_title( $id ) ),
			esc_attr( $s['trigger'] ),
			(int) $s['delay'],
			(int) $s['scroll'],
			esc_attr( $s['freq'] ),
			(int) $s['days'],
			esc_attr( implode( ' ', (array) $s['devices'] ) ),
			(int) $s['width'],
			esc_attr__( 'Close', 'semrs-pro' ),
			$body
		);
	}
	if ( $out ) {
		echo '<style id="semrs-popup-css">' . wp_strip_all_tags( (string) file_get_contents( SEMRS_PRO_DIR . 'assets/popups.css' ) ) . '</style>' . $out; // phpcs:ignore WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions -- block HTML rendered by WordPress; static CSS file.
		wp_enqueue_script( 'semrs-pro-popups', SEMRS_PRO_URL . 'assets/js/popups.js', array(), SEMRS_PRO_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}
add_action( 'wp_footer', 'semrs_pro_popups_footer', 5 );
