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
		add_meta_box( 'semrs_seo_box', __( 'SEMRS SEO', 'semrs-core' ), 'semrs_seo_render_box', $type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'semrs_seo_add_box' );

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
		.semrs-links{list-style:none;margin:6px 0 0;padding:0}.semrs-links li{display:flex;gap:6px;align-items:center;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f0f0f1}.semrs-links a{text-decoration:none}
	</style>
	<div class="semrs-seo-grid">
		<div>
			<label for="semrs_focus"><?php esc_html_e( 'Focus keyword', 'semrs-core' ); ?></label>
			<input class="widefat" id="semrs_focus" name="semrs_seo[focus]" value="<?php echo esc_attr( $v( 'focus' ) ); ?>" placeholder="<?php echo esc_attr( $ph_kw ); ?>">
			<label for="semrs_title"><?php esc_html_e( 'SEO title', 'semrs-core' ); ?></label>
			<input class="widefat" id="semrs_title" name="semrs_seo[title]" value="<?php echo esc_attr( $v( 'title' ) ); ?>" placeholder="<?php echo esc_attr( $ph_title ? $ph_title : $def ); ?>" data-max="60">
			<span class="semrs-seo-count" data-for="semrs_title"></span>
			<label for="semrs_description"><?php esc_html_e( 'Meta description', 'semrs-core' ); ?></label>
			<textarea class="widefat" rows="3" id="semrs_description" name="semrs_seo[description]" placeholder="<?php echo esc_attr( $ph_desc ); ?>" data-max="155"><?php echo esc_textarea( $v( 'description' ) ); ?></textarea>
			<span class="semrs-seo-count" data-for="semrs_description"></span>
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
				</p>
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
		['semrs_title', 'semrs_description'].forEach(function (id) { document.getElementById(id).addEventListener('input', update); });
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
		'focus'       => 'sanitize_text_field',
		'title'       => 'sanitize_text_field',
		'description' => 'sanitize_textarea_field',
		'canonical'   => 'esc_url_raw',
		'og_image'    => 'esc_url_raw',
	);
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
	foreach ( array( 'noindex', 'nofollow' ) as $flag ) {
		update_post_meta( $post_id, '_semrs_' . $flag, empty( $in[ $flag ] ) ? '0' : '1' );
	}
	if ( empty( $in['faq_off'] ) ) {
		delete_post_meta( $post_id, '_semrs_faq_off' );
	} else {
		update_post_meta( $post_id, '_semrs_faq_off', '1' );
	}
}
add_action( 'save_post', 'semrs_seo_save_box' );
