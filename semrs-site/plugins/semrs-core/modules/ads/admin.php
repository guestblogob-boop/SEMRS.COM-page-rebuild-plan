<?php
/**
 * Settings → SEMRS Ads, per-page switch, admin-bar status.
 *
 * @package semrs-ads
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu + setting.
 */
function semrs_ads_admin_menu() {
	add_submenu_page( 'semrs', __( 'SEMRS Ads', 'semrs-core' ), __( 'Ads', 'semrs-core' ), 'manage_options', 'semrs-ads', 'semrs_ads_render_settings' );
}
add_action( 'admin_menu', 'semrs_ads_admin_menu', 20 );

/**
 * Register the option with sanitizing.
 */
function semrs_ads_register_setting() {
	register_setting( 'semrs_ads', 'semrs_ads', array( 'sanitize_callback' => 'semrs_ads_sanitize' ) );
}
add_action( 'admin_init', 'semrs_ads_register_setting' );

/**
 * Raw partner code is kept only for users allowed unfiltered HTML.
 *
 * @param string $code Code.
 * @return string
 */
function semrs_ads_clean_code( $code ) {
	$code = (string) $code;
	return current_user_can( 'unfiltered_html' ) ? trim( $code ) : wp_kses_post( $code );
}

/**
 * Sanitize the whole settings array.
 *
 * @param array $in Raw input.
 * @return array
 */
function semrs_ads_sanitize( $in ) {
	$d   = semrs_ads_defaults();
	$in  = is_array( $in ) ? $in : array();
	$out = array(
		'enabled'         => ! empty( $in['enabled'] ),
		'publisher_id'    => preg_match( '/^ca-pub-\d{10,20}$/', trim( (string) ( $in['publisher_id'] ?? '' ) ) ) ? trim( $in['publisher_id'] ) : '',
		'load_adsense'    => ! empty( $in['load_adsense'] ),
		'head_code'       => semrs_ads_clean_code( $in['head_code'] ?? '' ),
		'lazy'            => ! empty( $in['lazy'] ),
		'hide_for_admins' => ! empty( $in['hide_for_admins'] ),
		'label'           => sanitize_text_field( $in['label'] ?? $d['label'] ),
		'paragraph_n'     => max( 1, min( 10, absint( $in['paragraph_n'] ?? 2 ) ) ),
		'min_words'       => max( 0, absint( $in['min_words'] ?? 300 ) ),
		'max_incontent'   => max( 0, min( 5, absint( $in['max_incontent'] ?? 3 ) ) ),
		'min_gap'         => max( 1, min( 10, absint( $in['min_gap'] ?? 3 ) ) ),
		'exclude_front'   => ! empty( $in['exclude_front'] ),
		'exclude_search'  => ! empty( $in['exclude_search'] ),
		'exclude_paths'   => sanitize_textarea_field( $in['exclude_paths'] ?? '' ),
		'exclude_ids'     => implode( ',', array_filter( array_map( 'absint', explode( ',', (string) ( $in['exclude_ids'] ?? '' ) ) ) ) ),
		'post_types'      => array_values( array_intersect( (array) ( $in['post_types'] ?? array() ), get_post_types( array( 'public' => true ) ) ) ),
		'manage_adstxt'   => ! empty( $in['manage_adstxt'] ),
		'adstxt'          => sanitize_textarea_field( $in['adstxt'] ?? '' ),
		'slots'           => array(),
	);
	foreach ( array_keys( semrs_ads_positions() ) as $key ) {
		$slot                  = isset( $in['slots'][ $key ] ) ? (array) $in['slots'][ $key ] : array();
		$out['slots'][ $key ] = array(
			'enabled' => ! empty( $slot['enabled'] ),
			'network' => 'custom' === ( $slot['network'] ?? '' ) ? 'custom' : 'adsense',
			'ad_slot' => preg_replace( '/\D/', '', (string) ( $slot['ad_slot'] ?? '' ) ),
			'format'  => 'in-article' === ( $slot['format'] ?? '' ) ? 'in-article' : 'auto',
			'code'    => semrs_ads_clean_code( $slot['code'] ?? '' ),
			'device'  => in_array( $slot['device'] ?? '', array( 'all', 'mobile', 'desktop', 'wide' ), true ) ? $slot['device'] : 'all',
		);
	}
	return $out;
}

/**
 * Settings page.
 */
function semrs_ads_render_settings() {
	$s = semrs_ads_settings();
	$n = 'semrs_ads';
	$check = function ( $key, $label ) use ( $s, $n ) {
		printf( '<label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label><br>', esc_attr( $n ), esc_attr( $key ), checked( ! empty( $s[ $key ] ), true, false ), esc_html( $label ) );
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Ads', 'semrs-core' ); ?></h1>
		<p><?php esc_html_e( 'Policy-safe ad placement. Conversion, legal, noindex and thin pages never show ads. Administrators don\'t see ads by default, which prevents accidental self-clicks.', 'semrs-core' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_ads' ); ?>
			<h2><?php esc_html_e( 'General', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Status', 'semrs-core' ); ?></th><td><?php $check( 'enabled', __( 'Show ads', 'semrs-core' ) ); ?></td></tr>
				<tr><th><label for="semrs-pub"><?php esc_html_e( 'AdSense publisher ID', 'semrs-core' ); ?></label></th><td><input id="semrs-pub" class="regular-text" name="<?php echo esc_attr( $n ); ?>[publisher_id]" value="<?php echo esc_attr( $s['publisher_id'] ); ?>" placeholder="ca-pub-1234567890123456"></td></tr>
				<tr><th><?php esc_html_e( 'AdSense tag', 'semrs-core' ); ?></th><td><?php $check( 'load_adsense', __( 'Load the AdSense tag on allowed pages (also runs Auto ads if they are on in AdSense)', 'semrs-core' ) ); ?><p class="description"><?php esc_html_e( 'Remove any other AdSense tag (Site Kit "Place AdSense code", Ad Inserter, header plugins) so the tag loads once.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><label for="semrs-head"><?php esc_html_e( 'Partner header code', 'semrs-core' ); ?></label></th><td><textarea id="semrs-head" class="large-text code" rows="4" name="<?php echo esc_attr( $n ); ?>[head_code]"><?php echo esc_textarea( $s['head_code'] ); ?></textarea><p class="description"><?php esc_html_e( 'Optional: Google Ad Manager / Ezoic / Raptive / Mediavine header script. Loaded only on pages where ads are allowed.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Behaviour', 'semrs-core' ); ?></th><td><?php $check( 'lazy', __( 'Lazy-load units just before they scroll into view (better viewability and speed)', 'semrs-core' ) ); $check( 'hide_for_admins', __( 'Hide ads for administrators', 'semrs-core' ) ); ?></td></tr>
				<tr><th><label for="semrs-label"><?php esc_html_e( 'Label above ads', 'semrs-core' ); ?></label></th><td><input id="semrs-label" class="regular-text" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( $s['label'] ); ?>"></td></tr>
			</table>

			<h2><?php esc_html_e( 'Density and content rules', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'First in-content ad after paragraph', 'semrs-core' ); ?></th><td><input type="number" min="1" max="10" name="<?php echo esc_attr( $n ); ?>[paragraph_n]" value="<?php echo esc_attr( $s['paragraph_n'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Minimum words for in-content ads', 'semrs-core' ); ?></th><td><input type="number" min="0" name="<?php echo esc_attr( $n ); ?>[min_words]" value="<?php echo esc_attr( $s['min_words'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Maximum in-content ads per page', 'semrs-core' ); ?></th><td><input type="number" min="0" max="5" name="<?php echo esc_attr( $n ); ?>[max_incontent]" value="<?php echo esc_attr( $s['max_incontent'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Minimum paragraphs between ads', 'semrs-core' ); ?></th><td><input type="number" min="1" max="10" name="<?php echo esc_attr( $n ); ?>[min_gap]" value="<?php echo esc_attr( $s['min_gap'] ); ?>"></td></tr>
			</table>

			<h2><?php esc_html_e( 'Where ads never show', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Pages', 'semrs-core' ); ?></th><td><?php $check( 'exclude_front', __( 'Front page', 'semrs-core' ) ); $check( 'exclude_search', __( 'Search results', 'semrs-core' ) ); ?><p class="description"><?php esc_html_e( 'Always excluded: 404, attachments, password-protected, noindex (Rank Math), pages with "Disable ads" ticked.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><label for="semrs-paths"><?php esc_html_e( 'Excluded paths (one per line)', 'semrs-core' ); ?></label></th><td><textarea id="semrs-paths" class="large-text code" rows="8" name="<?php echo esc_attr( $n ); ?>[exclude_paths]"><?php echo esc_textarea( $s['exclude_paths'] ); ?></textarea></td></tr>
				<tr><th><label for="semrs-ids"><?php esc_html_e( 'Excluded IDs (comma-separated)', 'semrs-core' ); ?></label></th><td><input id="semrs-ids" class="regular-text" name="<?php echo esc_attr( $n ); ?>[exclude_ids]" value="<?php echo esc_attr( $s['exclude_ids'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Post types with ads', 'semrs-core' ); ?></th><td>
					<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) : ?>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, (array) $s['post_types'], true ) ); ?>> <?php echo esc_html( $pt->labels->singular_name ); ?></label><br>
					<?php endforeach; ?>
				</td></tr>
			</table>

			<h2><?php esc_html_e( 'Ad positions', 'semrs-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'AdSense: create a Display (auto) or In-article ad unit in AdSense → Ads → By ad unit, then paste only its numeric data-ad-slot here. Other networks: choose "Custom code" and paste their unit code.', 'semrs-core' ); ?></p>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr><th><?php esc_html_e( 'Position', 'semrs-core' ); ?></th><th><?php esc_html_e( 'On', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Network', 'semrs-core' ); ?></th><th><?php esc_html_e( 'AdSense slot ID / format', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Custom code', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Device', 'semrs-core' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( semrs_ads_positions() as $key => $label ) : $sl = $s['slots'][ $key ]; $f = $n . '[slots][' . $key . ']'; ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><input type="checkbox" name="<?php echo esc_attr( $f ); ?>[enabled]" value="1" <?php checked( $sl['enabled'] ); ?>></td>
						<td><select name="<?php echo esc_attr( $f ); ?>[network]"><option value="adsense" <?php selected( $sl['network'], 'adsense' ); ?>>AdSense</option><option value="custom" <?php selected( $sl['network'], 'custom' ); ?>><?php esc_html_e( 'Custom code', 'semrs-core' ); ?></option></select></td>
						<td><input class="regular-text" style="width:140px" name="<?php echo esc_attr( $f ); ?>[ad_slot]" value="<?php echo esc_attr( $sl['ad_slot'] ); ?>" placeholder="1234567890"><br><select name="<?php echo esc_attr( $f ); ?>[format]"><option value="auto" <?php selected( $sl['format'], 'auto' ); ?>><?php esc_html_e( 'Display (responsive)', 'semrs-core' ); ?></option><option value="in-article" <?php selected( $sl['format'], 'in-article' ); ?>><?php esc_html_e( 'In-article', 'semrs-core' ); ?></option></select></td>
						<td><textarea class="code" rows="2" style="width:100%" name="<?php echo esc_attr( $f ); ?>[code]"><?php echo esc_textarea( $sl['code'] ); ?></textarea></td>
						<td><select name="<?php echo esc_attr( $f ); ?>[device]">
							<?php foreach ( array( 'all' => __( 'All', 'semrs-core' ), 'mobile' => __( 'Mobile + tablet (<922px)', 'semrs-core' ), 'desktop' => __( 'Desktop (≥922px)', 'semrs-core' ), 'wide' => __( 'Wide (≥1200px)', 'semrs-core' ) ) as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $sl['device'], $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php
			/**
			 * More settings inside the Ads form (SEMRS Pro).
			 */
			do_action( 'semrs_ads_settings_after' );
			?>

			<h2>ads.txt</h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Manage ads.txt', 'semrs-core' ); ?></th><td><?php $check( 'manage_adstxt', __( 'Serve /ads.txt from this plugin', 'semrs-core' ) ); ?>
					<p class="description"><?php echo file_exists( ABSPATH . 'ads.txt' ) ? esc_html__( 'A physical ads.txt file exists in the site root. It takes priority, so keep editing that file (or delete it to use this setting).', 'semrs-core' ) : esc_html__( 'No physical ads.txt found. The Google line is added automatically from your publisher ID.', 'semrs-core' ); ?></p></td></tr>
				<tr><th><label for="semrs-adstxt"><?php esc_html_e( 'Extra lines (other networks)', 'semrs-core' ); ?></label></th><td><textarea id="semrs-adstxt" class="large-text code" rows="5" name="<?php echo esc_attr( $n ); ?>[adstxt]"><?php echo esc_textarea( $s['adstxt'] ); ?></textarea></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php if ( ! has_action( 'semrs_ads_settings_after' ) ) : ?>
			<h2><?php esc_html_e( 'More with SEMRS Pro', 'semrs-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				$pro = array(
					__( 'More placements', 'semrs-core' )           => __( 'Repeat in-content ads every few paragraphs (within your density cap) and show ads between posts on blog and category pages.', 'semrs-core' ),
					__( 'Targeting', 'semrs-core' )                 => __( 'Show each position only in chosen categories, on posts older than N days, between two dates, or not to logged-in members.', 'semrs-core' ),
					__( 'A/B rotation', 'semrs-core' )              => __( 'Add a second ad code to a position; each page view shows one of them.', 'semrs-core' ),
					__( 'Click protection', 'semrs-core' )          => __( 'Stops showing ads to a visitor who clicks ads too often, which protects your AdSense account from invalid clicks.', 'semrs-core' ),
					__( 'Ad-blocker message', 'semrs-core' )        => __( 'A polite, dismissible note asking ad-blocker users to support the site. Content is never blocked.', 'semrs-core' ),
					__( 'Per-page position switches', 'semrs-core' ) => __( 'Turn single positions off on a page, for example keep the sidebar ad but drop in-content ads.', 'semrs-core' ),
				);
				foreach ( $pro as $label => $help ) {
					echo '<tr><th style="color:#50575e"><span class="dashicons dashicons-lock" aria-hidden="true"></span> ' . esc_html( $label ) . ' <span style="display:inline-block;background:#f0b849;color:#1d2327;font-size:11px;font-weight:700;border-radius:3px;padding:1px 6px">PRO</span></th><td><p class="description">' . esc_html( $help ) . '</p></td></tr>';
				}
				?>
			</table>
			<p><a class="button" href="<?php echo esc_url( semrs_core_pro_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Unlock with Pro', 'semrs-core' ); ?></a></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * "Disable ads" meta box on enabled post types.
 */
function semrs_ads_add_metabox() {
	foreach ( (array) semrs_ads_settings()['post_types'] as $type ) {
		add_meta_box( 'semrs_ads_off', __( 'Ads', 'semrs-core' ), 'semrs_ads_render_metabox', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'semrs_ads_add_metabox' );

/**
 * Render the meta box.
 *
 * @param WP_Post $post Post.
 */
function semrs_ads_render_metabox( $post ) {
	wp_nonce_field( 'semrs_ads_off', 'semrs_ads_off_nonce' );
	printf(
		'<label><input type="checkbox" name="semrs_ads_off" value="1" %s> %s</label>',
		checked( (bool) get_post_meta( $post->ID, '_semrs_ads_off', true ), true, false ),
		esc_html__( 'Disable ads on this page', 'semrs-core' )
	);
}

/**
 * Save the meta box.
 *
 * @param int $post_id Post ID.
 */
function semrs_ads_save_metabox( $post_id ) {
	if ( ! isset( $_POST['semrs_ads_off_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_ads_off_nonce'] ) ), 'semrs_ads_off' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( empty( $_POST['semrs_ads_off'] ) ) {
		delete_post_meta( $post_id, '_semrs_ads_off' );
	} else {
		update_post_meta( $post_id, '_semrs_ads_off', true );
	}
}
add_action( 'save_post', 'semrs_ads_save_metabox' );

/**
 * Plugin list "Settings" link.
 *
 * @param string[] $links Links.
 * @return string[]
 */
function semrs_ads_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=semrs-ads' ) ) . '">' . esc_html__( 'Settings', 'semrs-core' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_semrs-core/semrs-core.php', 'semrs_ads_action_links' );
