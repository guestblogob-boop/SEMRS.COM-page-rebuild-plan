<?php
/**
 * SEO settings page + per-post SEO box with snippet preview and checks.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function semrs_seo_menu() {
	add_submenu_page( 'semrs', __( 'SEO', 'semrs-core' ), __( 'SEO', 'semrs-core' ), 'manage_options', 'semrs-seo', 'semrs_seo_settings_page' );
}
add_action( 'admin_menu', 'semrs_seo_menu', 19 );

/**
 * Register the option.
 */
function semrs_seo_register_setting() {
	register_setting( 'semrs_seo', 'semrs_seo', array( 'sanitize_callback' => 'semrs_seo_sanitize' ) );
}
add_action( 'admin_init', 'semrs_seo_register_setting' );

/**
 * Field definitions: key => [label, type, section, help].
 *
 * @return array
 */
function semrs_seo_fields() {
	return array(
		'sep'              => array( __( 'Title separator', 'semrs-core' ), 'text', 'titles', '' ),
		'home_title'       => array( __( 'Homepage title', 'semrs-core' ), 'text', 'titles', __( 'Empty = "Site name | Tagline" (or the front page\'s own SEO title).', 'semrs-core' ) ),
		'home_description' => array( __( 'Homepage meta description', 'semrs-core' ), 'textarea', 'titles', '' ),
		'tpl_singular'     => array( __( 'Posts & pages title template', 'semrs-core' ), 'text', 'titles', __( 'Variables:', 'semrs-core' ) . ' %title% %site% %sep% %tagline%' ),
		'tpl_term'         => array( __( 'Category / tag title template', 'semrs-core' ), 'text', 'titles', '%term%' ),
		'tpl_author'       => array( __( 'Author title template', 'semrs-core' ), 'text', 'titles', '%author%' ),
		'tpl_search'       => array( __( 'Search title template', 'semrs-core' ), 'text', 'titles', '%search%' ),
		'tpl_404'          => array( __( '404 title template', 'semrs-core' ), 'text', 'titles', '' ),
		'tpl_date'         => array( __( 'Date archive title template', 'semrs-core' ), 'text', 'titles', '%date%' ),
		'noindex_tags'     => array( __( 'Noindex tag archives', 'semrs-core' ), 'checkbox', 'indexing', __( 'Recommended: tag pages are usually thin duplicates.', 'semrs-core' ) ),
		'noindex_authors'  => array( __( 'Noindex author archives', 'semrs-core' ), 'checkbox', 'indexing', __( 'Tick for single-author sites.', 'semrs-core' ) ),
		'noindex_dates'    => array( __( 'Noindex date archives', 'semrs-core' ), 'checkbox', 'indexing', '' ),
		'attachment_redirect' => array( __( 'Redirect attachment pages to their post', 'semrs-core' ), 'checkbox', 'indexing', '' ),
		'sitemap'          => array( __( 'XML sitemap (/wp-sitemap.xml)', 'semrs-core' ), 'checkbox', 'indexing', __( 'Old /sitemap_index.xml and /sitemap.xml URLs redirect here. Submit /wp-sitemap.xml in Search Console.', 'semrs-core' ) ),
		'llms'             => array( __( 'llms.txt for AI assistants (/llms.txt)', 'semrs-core' ), 'checkbox', 'indexing', __( 'A plain-text map of your important pages for ChatGPT, Perplexity, Claude and other AI tools. Noindexed pages are left out.', 'semrs-core' ) ),
		'robots_extra'     => array( __( 'Extra robots.txt lines', 'semrs-core' ), 'textarea', 'indexing', __( 'Only for the virtual robots.txt (no physical file in the site root).', 'semrs-core' ) ),
		'org_type'         => array( __( 'Entity type', 'semrs-core' ), 'select', 'entity', '' ),
		'org_name'         => array( __( 'Name', 'semrs-core' ), 'text', 'entity', '' ),
		'org_logo'         => array( __( 'Logo URL', 'semrs-core' ), 'url', 'entity', __( 'Empty = the site logo from the Customizer.', 'semrs-core' ) ),
		'org_email'        => array( __( 'Email', 'semrs-core' ), 'text', 'entity', '' ),
		'org_phone'        => array( __( 'Phone', 'semrs-core' ), 'text', 'entity', '' ),
		'org_street'       => array( __( 'Street address', 'semrs-core' ), 'text', 'entity', __( 'Real address only. Never a virtual office.', 'semrs-core' ) ),
		'org_city'         => array( __( 'City', 'semrs-core' ), 'text', 'entity', '' ),
		'org_region'       => array( __( 'State / region', 'semrs-core' ), 'text', 'entity', '' ),
		'org_postal'       => array( __( 'Postal code', 'semrs-core' ), 'text', 'entity', '' ),
		'org_country'      => array( __( 'Country code (e.g. US, GB, PK)', 'semrs-core' ), 'text', 'entity', '' ),
		'org_area'         => array( __( 'Areas served (comma-separated)', 'semrs-core' ), 'text', 'entity', 'US, GB, CA, AU, NZ, IE' ),
		'org_same_as'      => array( __( 'Social profiles (one URL per line)', 'semrs-core' ), 'textarea', 'entity', '' ),
		'og_image'         => array( __( 'Default share image URL (1200×630)', 'semrs-core' ), 'url', 'social', '' ),
		'twitter_site'     => array( __( 'X (Twitter) username', 'semrs-core' ), 'text', 'social', '' ),
		'verify_google'    => array( __( 'Google verification code', 'semrs-core' ), 'text', 'social', __( 'Only the content="…" value. Skip if Site Kit already verified the site.', 'semrs-core' ) ),
		'verify_bing'      => array( __( 'Bing verification code', 'semrs-core' ), 'text', 'social', '' ),
		'breadcrumbs'      => array( __( 'Show breadcrumbs (SEMRS Pro theme)', 'semrs-core' ), 'checkbox', 'social', '' ),
		'breadcrumb_home'  => array( __( 'Breadcrumb home label', 'semrs-core' ), 'text', 'social', '' ),
	);
}

/**
 * Sanitize settings.
 *
 * @param array $in Raw input.
 * @return array
 */
function semrs_seo_sanitize( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$out = array();
	foreach ( semrs_seo_fields() as $key => $f ) {
		$v = isset( $in[ $key ] ) ? wp_unslash( $in[ $key ] ) : '';
		switch ( $f[1] ) {
			case 'checkbox':
				$out[ $key ] = ! empty( $v );
				break;
			case 'textarea':
				$out[ $key ] = sanitize_textarea_field( $v );
				break;
			case 'url':
				$out[ $key ] = esc_url_raw( $v );
				break;
			case 'select':
				$out[ $key ] = in_array( $v, array( 'Organization', 'LocalBusiness', 'ProfessionalService', 'Person' ), true ) ? $v : 'Organization';
				break;
			default:
				$out[ $key ] = sanitize_text_field( $v );
		}
	}
	return $out;
}

/**
 * Settings page.
 */
function semrs_seo_settings_page() {
	$s        = semrs_seo_settings();
	$sections = array(
		'titles'   => __( 'Titles & meta', 'semrs-core' ),
		'indexing' => __( 'Indexing & sitemap', 'semrs-core' ),
		'entity'   => __( 'Business / organization (schema)', 'semrs-core' ),
		'social'   => __( 'Social, verification & breadcrumbs', 'semrs-core' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS SEO', 'semrs-core' ); ?></h1>
		<?php if ( ! semrs_seo_active() ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( sprintf( /* translators: %s: plugin */ __( '%s is active, so SEMRS SEO output is paused. You can set everything up now; it takes over when you deactivate it. Rank Math titles, descriptions, focus keywords, canonicals and noindex settings are read automatically.', 'semrs-core' ), semrs_seo_conflict() ) ); ?></p></div>
		<?php endif; ?>
		<?php semrs_seo_rm_import_box(); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_seo' ); ?>
			<?php foreach ( $sections as $sec => $title ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
				<table class="form-table" role="presentation">
				<?php
				foreach ( semrs_seo_fields() as $key => $f ) {
					if ( $f[2] !== $sec ) {
						continue;
					}
					$name = 'semrs_seo[' . $key . ']';
					echo '<tr><th><label for="semrs-' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
					if ( 'checkbox' === $f[1] ) {
						printf( '<input type="checkbox" id="semrs-%1$s" name="%2$s" value="1" %3$s>', esc_attr( $key ), esc_attr( $name ), checked( ! empty( $s[ $key ] ), true, false ) );
					} elseif ( 'textarea' === $f[1] ) {
						printf( '<textarea id="semrs-%1$s" class="large-text" rows="3" name="%2$s">%3$s</textarea>', esc_attr( $key ), esc_attr( $name ), esc_textarea( $s[ $key ] ) );
					} elseif ( 'select' === $f[1] ) {
						echo '<select id="semrs-' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">';
						foreach ( array( 'Organization', 'LocalBusiness', 'ProfessionalService', 'Person' ) as $opt ) {
							printf( '<option %s>%s</option>', selected( $s[ $key ], $opt, false ), esc_html( $opt ) );
						}
						echo '</select>';
					} else {
						printf( '<input type="%1$s" id="semrs-%2$s" class="regular-text" name="%3$s" value="%4$s">', 'url' === $f[1] ? 'url' : 'text', esc_attr( $key ), esc_attr( $name ), esc_attr( $s[ $key ] ) );
					}
					if ( $f[3] ) {
						echo '<p class="description">' . esc_html( $f[3] ) . '</p>';
					}
					echo '</td></tr>';
				}
				?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Post types that get the SEO box.
 *
 * @return string[]
 */
function semrs_seo_post_types() {
	return array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
}

/**
 * Register the box.
 */
function semrs_seo_add_box() {
	foreach ( semrs_seo_post_types() as $type ) {
		// Classic editor only: the block editor gets the SEMRS SEO sidebar (same fields, no duplicate box).
		add_meta_box( 'semrs_seo_box', __( 'SEMRS SEO', 'semrs-core' ), 'semrs_seo_render_box', $type, 'normal', 'high', array( '__back_compat_meta_box' => true ) );
	}
}
add_action( 'add_meta_boxes', 'semrs_seo_add_box' );

/**
 * Block editor: the SEMRS SEO sidebar (score in the toolbar; General, Advanced, Schema, Social tabs).
 */
function semrs_seo_sidebar_assets() {
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, semrs_seo_post_types(), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}
	wp_enqueue_script( 'semrs-seo-sidebar', SEMRS_CORE_URL . 'admin/seo-sidebar.js', array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-block-editor' ), SEMRS_CORE_VERSION, true );
	wp_enqueue_style( 'semrs-seo-sidebar', SEMRS_CORE_URL . 'admin/seo-sidebar.css', array( 'wp-components' ), SEMRS_CORE_VERSION );
	wp_set_script_translations( 'semrs-seo-sidebar', 'semrs-core', SEMRS_CORE_DIR . 'languages' );
	$s     = semrs_seo_settings();
	$types = array();
	foreach ( semrs_seo_schema_types() as $val => $label ) {
		$types[] = array( 'value' => $val, 'label' => $label );
	}
	$cats = get_the_category( $post->ID );
	$ph   = array();
	foreach ( array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ) as $flag ) {
		$ph[ $flag ] = '' === (string) get_post_meta( $post->ID, '_semrs_' . $flag, true ) && semrs_seo_meta( $post->ID, $flag ); // Imported value shown until changed.
	}
	wp_localize_script(
		'semrs-seo-sidebar',
		'semrsSeoSidebar',
		(array) apply_filters( 'semrs_seo_sidebar_config', array(
			'postId'      => $post->ID,
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'semrs_seo_box' ),
			'home'        => home_url( '/' ),
			'site'        => get_bloginfo( 'name' ),
			'tagline'     => get_bloginfo( 'description' ),
			'sep'         => $s['sep'],
			'tpl'         => str_replace( '%site%', '%sitename%', $s['tpl_singular'] ),
			'category'    => $cats ? $cats[0]->name : '',
			'phTitle'     => '' === (string) get_post_meta( $post->ID, '_semrs_title', true ) ? (string) semrs_seo_meta( $post->ID, 'title' ) : '',
			'phDesc'      => '' === (string) get_post_meta( $post->ID, '_semrs_description', true ) ? (string) semrs_seo_meta( $post->ID, 'description' ) : '',
			'phFocus'     => '' === (string) get_post_meta( $post->ID, '_semrs_focus', true ) ? (string) semrs_seo_meta( $post->ID, 'focus' ) : '',
			'ph'          => $ph,
			'schemaTypes' => $types,
			'pro'         => semrs_seo_pro(),
			'proUrl'      => function_exists( 'semrs_core_pro_url' ) ? semrs_core_pro_url() : 'https://semrs.com/semrs-pro/',
			'ai'          => null, // Filled by SEMRS Pro (AI SEO module).
		), $post )
	);
}
add_action( 'enqueue_block_editor_assets', 'semrs_seo_sidebar_assets' );

/**
 * Render the box.
 *
 * @param WP_Post $post Post.
 */
function semrs_seo_render_box( $post ) {
	wp_nonce_field( 'semrs_seo_box', 'semrs_seo_nonce' );
	$v = function ( $key ) use ( $post ) {
		return (string) get_post_meta( $post->ID, '_semrs_' . $key, true );
	};
	// Prefill from Rank Math the first time (shown as placeholders, saved only if edited).
	$ph_title = semrs_seo_meta( $post->ID, 'title' );
	$ph_desc  = semrs_seo_meta( $post->ID, 'description' );
	$ph_kw    = semrs_seo_meta( $post->ID, 'focus' );
	$def      = semrs_seo_fill( semrs_seo_settings()['tpl_singular'], array( 'title' => $post->post_title ) );
	if ( ! semrs_seo_active() ) {
		/* translators: %s: plugin name */
		echo '<p><em>' . esc_html( sprintf( __( '%s is active and controls SEO output. Values here take effect after you switch.', 'semrs-core' ), semrs_seo_conflict() ) ) . '</em></p>';
	}
	?>
	<style>
		.semrs-seo-grid{display:grid;gap:12px;grid-template-columns:1fr}
		@media(min-width:1100px){.semrs-seo-grid{grid-template-columns:3fr 2fr}}
		.semrs-seo-grid label{font-weight:600;display:block;margin:10px 0 4px}
		.semrs-seo-count{font-size:12px;color:#646970}.semrs-seo-count.bad{color:#b32d2e;font-weight:600}.semrs-seo-count.good{color:#007017}
		.semrs-serp{font-family:Arial,sans-serif;border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff;max-width:600px}
		.semrs-serp-url{font-size:13px;color:#202124}.semrs-serp-title{font-size:19px;color:#1a0dab;line-height:1.3;margin:4px 0}.semrs-serp-desc{font-size:13px;color:#4d5156;line-height:1.5}
		.semrs-checks{list-style:none;margin:0 0 8px;padding:0}.semrs-checks li{padding:3px 0 3px 20px;text-indent:-20px}.semrs-checks .ok:before{content:"✔ ";color:#007017}.semrs-checks .no:before{content:"✖ ";color:#b32d2e}
		.semrs-score-big{display:inline-flex;align-items:baseline;gap:2px;padding:6px 14px;border-radius:999px;color:#fff;background:#8c8f94;margin-bottom:8px}.semrs-score-big strong{font-size:22px}.semrs-score--good{background:#00813a}.semrs-score--ok{background:#b26200}.semrs-score--bad{background:#b32d2e}
		.semrs-check-group{border-top:1px solid #f0f0f1;padding:6px 0}.semrs-check-group summary{cursor:pointer;font-weight:600}.semrs-check-count{font-weight:400;color:#646970;font-size:12px}
		.semrs-pro-badge{display:inline-block;background:#f0b849;color:#1d2327;font-size:11px;font-weight:700;border-radius:3px;padding:1px 6px;margin-left:6px;vertical-align:middle}
		.semrs-links{list-style:none;margin:6px 0 0;padding:0}.semrs-links li{display:flex;gap:6px;align-items:center;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f0f0f1}.semrs-links a{text-decoration:none}
	</style>
	<div class="semrs-seo-grid">
		<div>
			<?php
			$pro  = semrs_seo_pro();
			$lock = $pro ? '' : ' disabled';
			$badge = $pro ? '' : ' <span class="semrs-pro-badge" title="' . esc_attr__( 'Unlock with SEMRS Pro', 'semrs-core' ) . '">PRO</span>';
			$more = (string) semrs_seo_meta( $post->ID, 'focus_more' );
			?>
			<label for="semrs_focus"><?php esc_html_e( 'Focus keyword', 'semrs-core' ); ?></label>
			<input class="widefat" id="semrs_focus" name="semrs_seo[focus]" value="<?php echo esc_attr( $v( 'focus' ) ); ?>" placeholder="<?php echo esc_attr( $ph_kw ); ?>">
			<label for="semrs_focus_more"><?php esc_html_e( 'More focus keywords (comma separated, up to 4)', 'semrs-core' ); ?><?php echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput -- built above. ?></label>
			<input class="widefat" id="semrs_focus_more" name="semrs_seo[focus_more]" value="<?php echo esc_attr( $more ); ?>"<?php echo esc_attr( $lock ); ?>>
			<label style="font-weight:400"><input type="checkbox" name="semrs_seo[pillar]" value="1" <?php checked( (bool) semrs_seo_meta( $post->ID, 'pillar' ) ); ?>> <?php esc_html_e( 'This post is Pillar Content', 'semrs-core' ); ?></label>
			<label for="semrs_title"><?php esc_html_e( 'SEO title', 'semrs-core' ); ?></label>
			<input class="widefat" id="semrs_title" name="semrs_seo[title]" value="<?php echo esc_attr( $v( 'title' ) ); ?>" placeholder="<?php echo esc_attr( $ph_title ? $ph_title : $def ); ?>" data-max="60">
			<span class="semrs-seo-count" data-for="semrs_title"></span>
			<p class="description"><?php esc_html_e( 'This is what will appear in the first line when this post shows up in the search results. Use the focus keyword at the beginning. Variables: %title% %sep% %sitename% %focuskw% %currentyear%', 'semrs-core' ); ?></p>
			<label for="semrs_slug"><?php esc_html_e( 'Permalink', 'semrs-core' ); ?></label>
			<input class="widefat" id="semrs_slug" name="semrs_seo[slug]" value="<?php echo esc_attr( $post->post_name ); ?>" data-max="75">
			<span class="semrs-seo-count" data-for="semrs_slug"></span>
			<p class="description"><?php esc_html_e( 'This is the unique URL of this page, displayed below the post title in the search results.', 'semrs-core' ); ?></p>
			<label for="semrs_description"><?php esc_html_e( 'Meta description', 'semrs-core' ); ?></label>
			<textarea class="widefat" rows="3" id="semrs_description" name="semrs_seo[description]" placeholder="<?php echo esc_attr( $ph_desc ); ?>" data-max="160"><?php echo esc_textarea( $v( 'description' ) ); ?></textarea>
			<span class="semrs-seo-count" data-for="semrs_description"></span>
			<p class="description"><?php esc_html_e( 'This is what will appear as the description when this post shows up in the search results. Use the focus keyword at the beginning. Variables: %excerpt% %focuskw%', 'semrs-core' ); ?></p>
			<label><?php esc_html_e( 'Google preview', 'semrs-core' ); ?></label>
			<div class="semrs-serp">
				<div class="semrs-serp-url"><?php echo esc_html( preg_replace( '#^https?://#', '', get_permalink( $post ) ) ); ?></div>
				<div class="semrs-serp-title" id="semrs_prev_title"></div>
				<div class="semrs-serp-desc" id="semrs_prev_desc"></div>
			</div>
			<details style="margin-top:12px"><summary><strong><?php esc_html_e( 'Advanced', 'semrs-core' ); ?></strong></summary>
				<label for="semrs_canonical"><?php esc_html_e( 'Canonical URL (leave empty for this page)', 'semrs-core' ); ?></label>
				<input class="widefat" type="url" id="semrs_canonical" name="semrs_seo[canonical]" value="<?php echo esc_attr( $v( 'canonical' ) ); ?>">
				<label for="semrs_og_image"><?php esc_html_e( 'Share image URL (empty = featured image)', 'semrs-core' ); ?></label>
				<input class="widefat" type="url" id="semrs_og_image" name="semrs_seo[og_image]" value="<?php echo esc_attr( $v( 'og_image' ) ); ?>">
				<label for="semrs_schema"><?php esc_html_e( 'Schema type', 'semrs-core' ); ?></label>
				<select id="semrs_schema" name="semrs_seo[schema]">
					<?php foreach ( semrs_seo_schema_types() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $v( 'schema' ), $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p>
					<label style="font-weight:400"><input type="checkbox" name="semrs_seo[noindex]" value="1" <?php checked( (bool) semrs_seo_is_noindex( $post->ID ) ); ?>> <?php esc_html_e( 'Noindex (hide from Google; also removes it from the sitemap and turns ads off)', 'semrs-core' ); ?></label>
					<label style="font-weight:400"><input type="checkbox" name="semrs_seo[nofollow]" value="1" <?php checked( (bool) semrs_seo_meta( $post->ID, 'nofollow' ) ); ?>> <?php esc_html_e( 'Nofollow links on this page', 'semrs-core' ); ?></label>
					<label style="font-weight:400"><input type="checkbox" name="semrs_seo[faq_off]" value="1" <?php checked( (bool) $v( 'faq_off' ) ); ?>> <?php esc_html_e( 'Don\'t build FAQ schema from Details blocks', 'semrs-core' ); ?></label>
					<?php foreach ( array( 'noarchive' => __( 'No archive (no cached copy in search results)', 'semrs-core' ), 'nosnippet' => __( 'No snippet (no text preview in search results)', 'semrs-core' ), 'noimageindex' => __( 'No image index (images on this page are not indexed)', 'semrs-core' ) ) as $flag => $flabel ) : ?>
						<label style="font-weight:400"><input type="checkbox" name="semrs_seo[<?php echo esc_attr( $flag ); ?>]" value="1" <?php checked( (bool) semrs_seo_meta( $post->ID, $flag ) ); ?>> <?php echo esc_html( $flabel ); ?></label>
					<?php endforeach; ?>
				</p>
				<label for="semrs_breadcrumb_title"><?php esc_html_e( 'Breadcrumb title (empty = post title)', 'semrs-core' ); ?></label>
				<input class="widefat" id="semrs_breadcrumb_title" name="semrs_seo[breadcrumb_title]" value="<?php echo esc_attr( $v( 'breadcrumb_title' ) ); ?>">
				<p><strong><?php esc_html_e( 'Advanced robots', 'semrs-core' ); ?></strong><?php echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput -- built above. ?></p>
				<label for="semrs_max_snippet"><?php esc_html_e( 'Max snippet (characters; empty or -1 = no limit)', 'semrs-core' ); ?></label>
				<input type="number" min="-1" id="semrs_max_snippet" name="semrs_seo[max_snippet]" value="<?php echo esc_attr( (string) semrs_seo_meta( $post->ID, 'max_snippet' ) ); ?>"<?php echo esc_attr( $lock ); ?>>
				<label for="semrs_max_video"><?php esc_html_e( 'Max video preview (seconds; empty or -1 = no limit)', 'semrs-core' ); ?></label>
				<input type="number" min="-1" id="semrs_max_video" name="semrs_seo[max_video]" value="<?php echo esc_attr( (string) semrs_seo_meta( $post->ID, 'max_video' ) ); ?>"<?php echo esc_attr( $lock ); ?>>
				<label for="semrs_max_image"><?php esc_html_e( 'Max image preview', 'semrs-core' ); ?></label>
				<select id="semrs_max_image" name="semrs_seo[max_image]"<?php echo esc_attr( $lock ); ?>>
					<?php foreach ( array( '' => __( 'Large (default)', 'semrs-core' ), 'standard' => __( 'Standard', 'semrs-core' ), 'none' => __( 'None', 'semrs-core' ) ) as $mv => $ml ) : ?>
						<option value="<?php echo esc_attr( $mv ); ?>" <?php selected( (string) semrs_seo_meta( $post->ID, 'max_image' ), $mv ); ?>><?php echo esc_html( $ml ); ?></option>
					<?php endforeach; ?>
				</select>
			</details>
			<details style="margin-top:12px"><summary><strong><?php esc_html_e( 'Social (Facebook and X)', 'semrs-core' ); ?></strong></summary>
				<label for="semrs_og_title"><?php esc_html_e( 'Facebook title (empty = SEO title)', 'semrs-core' ); ?></label>
				<input class="widefat" id="semrs_og_title" name="semrs_seo[og_title]" value="<?php echo esc_attr( $v( 'og_title' ) ); ?>">
				<label for="semrs_og_description"><?php esc_html_e( 'Facebook description (empty = meta description)', 'semrs-core' ); ?></label>
				<textarea class="widefat" rows="2" id="semrs_og_description" name="semrs_seo[og_description]"><?php echo esc_textarea( $v( 'og_description' ) ); ?></textarea>
				<p><label style="font-weight:400"><input type="checkbox" name="semrs_seo[tw_custom]" value="1" <?php checked( (bool) semrs_seo_meta( $post->ID, 'tw_custom' ) ); ?><?php echo esc_attr( $lock ); ?>> <?php esc_html_e( 'Use different details on X (otherwise X shows the Facebook details)', 'semrs-core' ); ?></label><?php echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput -- built above. ?></p>
				<label for="semrs_tw_card"><?php esc_html_e( 'X card type', 'semrs-core' ); ?></label>
				<select id="semrs_tw_card" name="semrs_seo[tw_card]"<?php echo esc_attr( $lock ); ?>>
					<?php foreach ( array( '' => __( 'Automatic', 'semrs-core' ), 'summary_large_image' => __( 'Summary with large image', 'semrs-core' ), 'summary' => __( 'Summary', 'semrs-core' ) ) as $cv => $cl ) : ?>
						<option value="<?php echo esc_attr( $cv ); ?>" <?php selected( (string) semrs_seo_meta( $post->ID, 'tw_card' ), $cv ); ?>><?php echo esc_html( $cl ); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="semrs_tw_title"><?php esc_html_e( 'X title', 'semrs-core' ); ?></label>
				<input class="widefat" id="semrs_tw_title" name="semrs_seo[tw_title]" value="<?php echo esc_attr( (string) semrs_seo_meta( $post->ID, 'tw_title' ) ); ?>"<?php echo esc_attr( $lock ); ?>>
				<label for="semrs_tw_description"><?php esc_html_e( 'X description', 'semrs-core' ); ?></label>
				<textarea class="widefat" rows="2" id="semrs_tw_description" name="semrs_seo[tw_description]"<?php echo esc_attr( $lock ); ?>><?php echo esc_textarea( (string) semrs_seo_meta( $post->ID, 'tw_description' ) ); ?></textarea>
				<label for="semrs_tw_image"><?php esc_html_e( 'X image URL (empty = share image)', 'semrs-core' ); ?></label>
				<input class="widefat" type="url" id="semrs_tw_image" name="semrs_seo[tw_image]" value="<?php echo esc_attr( (string) semrs_seo_meta( $post->ID, 'tw_image' ) ); ?>"<?php echo esc_attr( $lock ); ?>>
				<?php if ( ! $pro ) : ?>
					<p><a class="button" href="<?php echo esc_url( semrs_core_pro_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Unlock with SEMRS Pro', 'semrs-core' ); ?></a></p>
				<?php endif; ?>
			</details>
		</div>
		<div>
			<label><?php esc_html_e( 'SEO score and checks', 'semrs-core' ); ?></label>
			<div id="semrs-seo-checks"><?php echo semrs_seo_checks_html( semrs_seo_analyze( semrs_seo_analysis_input( $post ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the function. ?></div>
			<p><button type="button" class="button" id="semrs-seo-recheck"><?php esc_html_e( 'Re-check now', 'semrs-core' ); ?></button> <span class="description"><?php esc_html_e( 'Checks your unsaved changes too.', 'semrs-core' ); ?></span></p>
			<label><?php esc_html_e( 'Internal link ideas', 'semrs-core' ); ?></label>
			<p class="description" id="semrs-links-help"><?php esc_html_e( 'Click Re-check to find your pages about this topic that are not linked yet.', 'semrs-core' ); ?></p>
			<ul class="semrs-links" id="semrs-links"></ul>
		</div>
	</div>
	<script>
	(function () {
		function val(id) { var el = document.getElementById(id); return el.value || el.placeholder || ''; }
		function update() {
			document.querySelectorAll('.semrs-seo-count').forEach(function (c) {
				var el = document.getElementById(c.dataset.for), n = val(c.dataset.for).length, max = +el.dataset.max;
				c.textContent = n + ' / ' + max;
				c.className = 'semrs-seo-count ' + (n > max ? 'bad' : (n >= max * 0.5 ? 'good' : ''));
			});
			var t = val('semrs_title'), d = val('semrs_description');
			document.getElementById('semrs_prev_title').textContent = t.length > 60 ? t.slice(0, 57) + '…' : t;
			document.getElementById('semrs_prev_desc').textContent = d.length > 158 ? d.slice(0, 155) + '…' : d;
		}
		['semrs_title', 'semrs_description', 'semrs_slug'].forEach(function (id) { document.getElementById(id).addEventListener('input', update); });
		update();

		var btn = document.getElementById('semrs-seo-recheck');
		btn.addEventListener('click', function () {
			var f = new FormData(), ed = window.wp && wp.data && wp.data.select('core/editor');
			f.append('action', 'semrs_seo_analyze');
			f.append('nonce', document.getElementById('semrs_seo_nonce').value);
			f.append('post_id', <?php echo (int) $post->ID; ?>);
			if (ed && ed.getEditedPostContent) {
				f.append('content', ed.getEditedPostContent());
				f.append('post_title', ed.getEditedPostAttribute('title') || '');
				f.append('slug', ed.getEditedPostAttribute('slug') || '');
				f.append('thumb', ed.getEditedPostAttribute('featured_media') ? 1 : 0);
			} else {
				var mce = window.tinymce && tinymce.get('content'), ta = document.getElementById('content');
				if (mce && !mce.isHidden()) { f.append('content', mce.getContent()); } else if (ta) { f.append('content', ta.value); }
				if (document.getElementById('title')) { f.append('post_title', document.getElementById('title').value); }
				if (document.getElementById('post_name')) { f.append('slug', document.getElementById('post_name').value); }
			}
			['focus', 'title', 'description'].forEach(function (k) { f.append(k, document.getElementById('semrs_' + k).value); });
			btn.disabled = true;
			fetch(ajaxurl, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
				btn.disabled = false;
				if (!r.success) { return; }
				document.getElementById('semrs-seo-checks').innerHTML = r.data.html;
				var ul = document.getElementById('semrs-links'), help = document.getElementById('semrs-links-help');
				ul.textContent = '';
				help.textContent = r.data.links.length ? <?php echo wp_json_encode( __( 'Link to these pages where it helps the reader:', 'semrs-core' ) ); ?> : <?php echo wp_json_encode( __( 'No unlinked pages found on this topic.', 'semrs-core' ) ); ?>;
				r.data.links.forEach(function (l) {
					var li = document.createElement('li'), a = document.createElement('a'), b = document.createElement('button');
					a.href = l.url; a.target = '_blank'; a.textContent = l.title;
					b.type = 'button'; b.className = 'button button-small'; b.textContent = <?php echo wp_json_encode( __( 'Copy link', 'semrs-core' ) ); ?>;
					b.addEventListener('click', function () { navigator.clipboard && navigator.clipboard.writeText(l.url); b.textContent = <?php echo wp_json_encode( __( 'Copied', 'semrs-core' ) ); ?>; });
					li.appendChild(a); li.appendChild(b); ul.appendChild(li);
				});
			}).catch(function () { btn.disabled = false; });
		});
	})();
	</script>
	<?php
}

/**
 * Save the box.
 *
 * @param int $post_id Post ID.
 */
function semrs_seo_save_box( $post_id ) {
	if ( ! isset( $_POST['semrs_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_seo_nonce'] ) ), 'semrs_seo_box' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$in   = isset( $_POST['semrs_seo'] ) ? (array) wp_unslash( $_POST['semrs_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
	$text = array(
		'focus'            => 'sanitize_text_field',
		'title'            => 'sanitize_text_field',
		'description'      => 'sanitize_textarea_field',
		'canonical'        => 'esc_url_raw',
		'og_image'         => 'esc_url_raw',
		'og_title'         => 'sanitize_text_field',
		'og_description'   => 'sanitize_textarea_field',
		'breadcrumb_title' => 'sanitize_text_field',
	);
	if ( semrs_seo_pro() ) {
		$text += array(
			'focus_more'     => 'sanitize_text_field',
			'tw_title'       => 'sanitize_text_field',
			'tw_description' => 'sanitize_textarea_field',
			'tw_image'       => 'esc_url_raw',
			'tw_card'        => function ( $c ) { return in_array( $c, array( 'summary', 'summary_large_image' ), true ) ? $c : ''; },
			'max_image'      => function ( $c ) { return in_array( $c, array( 'standard', 'none', 'large' ), true ) ? $c : ''; },
			'max_snippet'    => function ( $c ) { return '' === trim( (string) $c ) ? '' : (string) max( -1, (int) $c ); },
			'max_video'      => function ( $c ) { return '' === trim( (string) $c ) ? '' : (string) max( -1, (int) $c ); },
		);
	}
	foreach ( $text as $key => $fn ) {
		$value = isset( $in[ $key ] ) ? call_user_func( $fn, $in[ $key ] ) : '';
		if ( '' === $value ) {
			delete_post_meta( $post_id, '_semrs_' . $key );
		} else {
			update_post_meta( $post_id, '_semrs_' . $key, $value );
		}
	}
	$schema = isset( $in['schema'] ) && array_key_exists( $in['schema'], semrs_seo_schema_types() ) ? $in['schema'] : '';
	if ( '' === $schema ) {
		delete_post_meta( $post_id, '_semrs_schema' );
	} else {
		update_post_meta( $post_id, '_semrs_schema', $schema );
	}
	// Explicit '0' so unticking overrides an inherited Rank Math noindex/nofollow.
	$flags = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex', 'pillar' );
	if ( semrs_seo_pro() ) {
		$flags[] = 'tw_custom';
	}
	foreach ( $flags as $flag ) {
		update_post_meta( $post_id, '_semrs_' . $flag, empty( $in[ $flag ] ) ? '0' : '1' );
	}
	if ( empty( $in['faq_off'] ) ) {
		delete_post_meta( $post_id, '_semrs_faq_off' );
	} else {
		update_post_meta( $post_id, '_semrs_faq_off', '1' );
	}
}
add_action( 'save_post', 'semrs_seo_save_box' );

/**
 * Permalink field of the classic editor box: becomes the post slug on save.
 *
 * @param array $data    Post data about to be saved.
 * @param array $postarr Raw post array.
 * @return array
 */
function semrs_seo_box_slug( $data, $postarr ) {
	if ( empty( $_POST['semrs_seo']['slug'] ) || ! isset( $_POST['semrs_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_seo_nonce'] ) ), 'semrs_seo_box' ) || 'auto-draft' === $data['post_status'] ) {
		return $data;
	}
	$slug = sanitize_title( wp_unslash( $_POST['semrs_seo']['slug'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize_title.
	if ( '' !== $slug && $slug !== $data['post_name'] ) {
		$data['post_name'] = wp_unique_post_slug( $slug, (int) ( $postarr['ID'] ?? 0 ), $data['post_status'], $data['post_type'], (int) $data['post_parent'] );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'semrs_seo_box_slug', 10, 2 );
