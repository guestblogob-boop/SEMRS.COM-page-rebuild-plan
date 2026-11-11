<?php
/**
 * SEMRS top-level admin menu and the Features screen: every SEMRS feature
 * (free and Pro) on one page, with search, filters, on/off switches and a
 * settings link. Pro features show a PRO badge and stay locked until SEMRS
 * Pro is installed (the Pro code lives only in the Pro plugin).
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where "Unlock with Pro" links go.
 *
 * @return string
 */
function semrs_core_pro_url() {
	return apply_filters( 'semrs_pro_url', 'https://semrs.com/semrs-pro/' );
}

/**
 * Top-level menu.
 */
function semrs_core_admin_menu() {
	add_menu_page( __( 'SEMRS', 'semrs-core' ), __( 'SEMRS', 'semrs-core' ), 'manage_options', 'semrs', 'semrs_core_render_dashboard', 'dashicons-chart-area', 58 );
	add_submenu_page( 'semrs', __( 'SEMRS Features', 'semrs-core' ), __( 'Features', 'semrs-core' ), 'manage_options', 'semrs', 'semrs_core_render_dashboard' );
}
add_action( 'admin_menu', 'semrs_core_admin_menu', 9 );

/**
 * Register the modules option.
 */
function semrs_core_register_modules_setting() {
	register_setting(
		'semrs_core_modules',
		'semrs_core_modules',
		array(
			'sanitize_callback' => function ( $in ) {
				$out = array();
				foreach ( array_keys( semrs_core_modules() ) as $key ) {
					// Locked modules have no switch: keep their setting for when they unlock.
					$out[ $key ] = apply_filters( 'semrs_core_module_locked', false, $key ) ? semrs_core_module_on( $key ) : ! empty( $in[ $key ] );
				}
				return $out;
			},
		)
	);
}
add_action( 'admin_init', 'semrs_core_register_modules_setting' );

/**
 * Re-flush rewrites after modules change (sitemaps, post types).
 */
add_action(
	'update_option_semrs_core_modules',
	function () {
		update_option( 'semrs_core_flush', 1 );
	}
);

/**
 * Switching off an SEO or cache plugin can leave its sitemap rules and
 * cached pages behind: rebuild the rules and clear the SEMRS cache.
 *
 * @param string $plugin Plugin basename.
 */
add_action(
	'deactivated_plugin',
	function ( $plugin ) {
		if ( preg_match( '#^(seo-by-rank-math|wordpress-seo|all-in-one-seo-pack|wp-seopress|autodescription|wp-rocket|litespeed-cache|w3-total-cache|wp-super-cache|wp-fastest-cache)/#', $plugin ) ) {
			update_option( 'semrs_core_flush', 1 );
			if ( function_exists( 'semrs_cache_purge_all' ) ) {
				semrs_cache_purge_all();
			}
		}
	}
);
add_action(
	'init',
	function () {
		if ( get_option( 'semrs_core_flush' ) ) {
			delete_option( 'semrs_core_flush' );
			flush_rewrite_rules();
		}
	},
	99
);

/**
 * Card details per module: key => [group, dashicon, settings URL].
 *
 * @return array
 */
function semrs_core_module_meta() {
	return apply_filters(
		'semrs_module_meta',
		array(
			'seo'        => array( 'seo', 'search', admin_url( 'admin.php?page=semrs-seo' ) ),
			'ads'        => array( 'ads', 'money-alt', admin_url( 'admin.php?page=semrs-ads' ) ),
			'speed'      => array( 'speed', 'performance', admin_url( 'admin.php?page=semrs-speed' ) ),
			'redirects'  => array( 'seo', 'randomize', admin_url( 'admin.php?page=semrs-redirects' ) ),
			'local'      => array( 'seo', 'location', admin_url( 'edit.php?post_type=semrs_location' ) ),
			'indexnow'   => array( 'seo', 'update', admin_url( 'admin.php?page=semrs-indexnow' ) ),
			'image_seo'  => array( 'seo', 'format-image', admin_url( 'upload.php' ), __( 'Media Library', 'semrs-core' ) ),
			'layouts'    => array( 'design', 'layout', admin_url( 'edit.php?post_type=semrs_layout' ), __( 'Site Layouts', 'semrs-core' ) ),
			'proof'      => array( 'design', 'awards', admin_url( 'edit.php?post_type=semrs_case_study' ), __( 'Case studies', 'semrs-core' ) ),
			'leads'      => array( 'leads', 'email-alt', admin_url( 'options-general.php#semrs_thank_you_path' ), __( 'Thank-you page', 'semrs-core' ) ),
			'blocks_pro' => array( 'design', 'block-default', admin_url( 'admin.php?page=semrs-blocks' ) ),
			'theme_pro'  => array( 'design', 'admin-appearance', admin_url( 'customize.php?autofocus[section]=semrs_theme_pro' ) ),
			'conditions' => array( 'design', 'visibility', admin_url( 'edit.php?post_type=page' ), __( 'Pages', 'semrs-core' ) ),
			'popups'     => array( 'leads', 'feedback', admin_url( 'edit.php?post_type=semrs_popup' ) ),
			'languages'  => array( 'seo', 'translation', admin_url( 'admin.php?page=semrs-languages' ) ),
			'templates_pro' => array( 'design', 'layout', admin_url( 'admin.php?page=semrs-templates' ) ),
			'seo_bulk'   => array( 'seo', 'editor-table', admin_url( 'admin.php?page=semrs-seo-bulk' ) ),
			'speed_pro'  => array( 'speed', 'dashboard', admin_url( 'admin.php?page=semrs-speed' ) ),
			'ads_pro'    => array( 'ads', 'chart-bar', admin_url( 'admin.php?page=semrs-ads' ) ),
			'images'     => array( 'speed', 'format-gallery', admin_url( 'admin.php?page=semrs-images' ) ),
			'roles'      => array( 'design', 'groups', admin_url( 'users.php?page=semrs-roles' ) ),
			'reviews'    => array( 'leads', 'star-filled', admin_url( 'admin.php?page=semrs-reviews' ) ),
			'privacy'    => array( 'privacy', 'privacy', admin_url( 'admin.php?page=semrs-privacy' ) ),
			'email'      => array( 'privacy', 'email', admin_url( 'admin.php?page=semrs-email' ) ),
			'security'   => array( 'privacy', 'shield', admin_url( 'admin.php?page=semrs-security' ) ),
			'tools'      => array( 'design', 'admin-tools', admin_url( 'admin.php?page=semrs-site-tools' ) ),
			'two_factor' => array( 'privacy', 'lock', admin_url( 'admin.php?page=semrs-login-security' ) ),
			'activity'   => array( 'privacy', 'list-view', admin_url( 'admin.php?page=semrs-activity' ) ),
			'search_replace' => array( 'design', 'search', admin_url( 'tools.php?page=semrs-search-replace' ) ),
			'security_pro' => array( 'privacy', 'shield-alt', admin_url( 'admin.php?page=semrs-protect' ) ),
			'backup'     => array( 'privacy', 'backup', admin_url( 'admin.php?page=semrs-backups' ) ),
			'og_images'  => array( 'seo', 'format-image', admin_url( 'admin.php?page=semrs-og-images' ) ),
			'tools_pro'  => array( 'seo', 'admin-tools', admin_url( 'admin.php?page=semrs-tools&tab=settings' ), __( 'Free Tools', 'semrs-core' ) ),
			'image_library' => array( 'speed', 'images-alt2', admin_url( 'admin.php?page=semrs-images' ) ),
			'schema_pro' => array( 'seo', 'editor-code', admin_url( 'edit.php?post_type=page' ), __( 'Pages', 'semrs-core' ) ),
			'ai_seo'     => array( 'seo', 'superhero-alt', admin_url( 'admin.php?page=semrs-ai' ) ),
			'links_pro'  => array( 'seo', 'admin-links', admin_url( 'admin.php?page=semrs-links' ) ),
			'sitemaps_pro' => array( 'seo', 'networking', admin_url( 'admin.php?page=semrs-sitemaps-pro' ) ),
			'rank_tracker' => array( 'seo', 'chart-line', admin_url( 'admin.php?page=semrs-performance' ) ),
		)
	);
}

/**
 * Manual section for each feature card.
 *
 * @param string $key Module key.
 * @return string
 */
function semrs_core_module_docs( $key ) {
	$map = array(
		'seo'           => 'seo/',
		'redirects'     => 'seo/#moving-from-another-seo-plugin',
		'indexnow'      => 'seo/#schema-sitemap-and-llms-txt',
		'ads'           => 'ads/',
		'ads_pro'       => 'ads/#6-extra-ad-features-in-semrs-pro',
		'speed'         => 'speed/',
		'speed_pro'     => 'speed/#extra-speed-in-semrs-pro',
		'images'        => 'speed/',
		'image_library' => 'speed/#extra-speed-in-semrs-pro',
		'theme_pro'     => 'theme/',
		'templates_pro' => 'templates/',
		'languages'     => 'pro-license/#add-more-languages',
		'blocks_pro'    => 'blocks/#pro-blocks-need-semrs-pro',
		'conditions'    => 'blocks/#pro-blocks-need-semrs-pro',
		'popups'        => 'blocks/#pro-blocks-need-semrs-pro',
		'privacy'       => 'privacy-security/#cookie-consent',
		'email'         => 'privacy-security/#email-smtp',
		'security'      => 'privacy-security/#security',
		'tools'         => 'privacy-security/#site-tools',
		'two_factor'    => 'privacy-security/#login-security-pro',
		'activity'      => 'privacy-security/#activity-log-pro',
		'search_replace' => 'privacy-security/#search-and-replace-pro',
		'security_pro'  => 'privacy-security/#semrs-assets-protector-pro',
		'backup'        => 'privacy-security/#backups-and-migration-pro',
		'og_images'     => 'privacy-security/#social-images-pro',
		'tools_pro'     => 'free-tools/#free-tools-pro',
	);
	if ( isset( $map[ $key ] ) ) {
		$path = $map[ $key ];
	} elseif ( in_array( $key, array( 'local', 'rank_tracker', 'sitemaps_pro', 'links_pro', 'ai_seo', 'schema_pro', 'seo_bulk', 'image_seo' ), true ) ) {
		$path = 'seo/#extra-seo-in-semrs-pro';
	} elseif ( in_array( $key, array( 'layouts', 'proof', 'leads' ), true ) ) {
		$path = 'pro-license/#what-pro-unlocks';
	} else {
		$path = '#what-each-semrs-product-does';
	}
	return apply_filters( 'semrs_docs_url', 'https://semrs.com/support/' ) . $path;
}

/**
 * Pro features shown (locked) when SEMRS Pro is not installed:
 * key => [label, description].
 *
 * @return array
 */
function semrs_core_pro_teasers() {
	return array(
		'local'      => array( __( 'Multi-location Local SEO', 'semrs-core' ), __( 'Locations with address, hours, map link and service areas; LocalBusiness schema for each one.', 'semrs-core' ) ),
		'rank_tracker' => array( __( 'SEO Performance & rank tracking', 'semrs-core' ), __( 'Search Console data, keyword positions with history and a weekly email report.', 'semrs-core' ) ),
		'sitemaps_pro' => array( __( 'Sitemaps Pro', 'semrs-core' ), __( 'Image, video and news sitemaps, and video schema.', 'semrs-core' ) ),
		'links_pro'  => array( __( 'Link Assistant', 'semrs-core' ), __( 'Automatic internal links from your keywords and a broken link checker.', 'semrs-core' ) ),
		'ai_seo'     => array( __( 'AI SEO', 'semrs-core' ), __( 'AI writes SEO titles, meta descriptions, focus keywords, social text and image alt text, with your own Claude, OpenAI or Gemini key.', 'semrs-core' ) ),
		'schema_pro' => array( __( 'Custom schema', 'semrs-core' ), __( 'Add your own JSON-LD (Event, Product, Course, HowTo, Recipe, Video) to any page, with starters and a JSON check.', 'semrs-core' ) ),
		'image_library' => array( __( 'Image library conversion', 'semrs-core' ), __( 'WebP or AVIF copies of every image already uploaded, all sizes, with originals kept.', 'semrs-core' ) ),
		'seo_bulk'   => array( __( 'Bulk SEO editor', 'semrs-core' ), __( 'Edit SEO titles, meta descriptions, focus keywords and noindex for many posts on one screen, with length counters.', 'semrs-core' ) ),
		'speed_pro'  => array( __( 'Speed Pro', 'semrs-core' ), __( 'Delay analytics, pixel and chat scripts until the visitor interacts, load form plugins only where used, minify stylesheets, add image sizes, lazy-load iframes, preload the page cache and serve files from a CDN.', 'semrs-core' ) ),
		'ads_pro'    => array( __( 'Ads Pro', 'semrs-core' ), __( 'More placements, targeting by category, post age and dates, A/B rotation, click protection and a polite ad-blocker message.', 'semrs-core' ) ),
		'image_seo'  => array( __( 'Image SEO', 'semrs-core' ), __( 'Fills missing alt text and titles automatically from clean file names.', 'semrs-core' ) ),
		'blocks_pro' => array( __( 'Pro Blocks', 'semrs-core' ), __( '16 Pro blocks: slider, counter, 3D model, popup, pricing switch, sticky bar, before / after, hotspots, Lottie, marquee, video popup, switcher, data table, animated timeline, reviews slider and trust badges, plus scroll animations.', 'semrs-core' ) ),
		'theme_pro'  => array( __( 'Theme Pro', 'semrs-core' ), __( 'Dark mode switch, mega menu, automatic table of contents, extra section patterns and white label for the SEMRS theme.', 'semrs-core' ) ),
		'layouts'    => array( __( 'Site Layouts (custom hooks)', 'semrs-core' ), __( 'Show any block content at a theme position with display rules.', 'semrs-core' ) ),
		'proof'      => array( __( 'Case studies & portfolio', 'semrs-core' ), __( 'Case Studies, Portfolio and Testimonials with permission checks.', 'semrs-core' ) ),
		'templates_pro' => array( __( 'Pro starter sites', 'semrs-core' ), __( 'Extra one-click starter sites (Consultant, Clinic) with Pro sections.', 'semrs-core' ) ),
		'languages'  => array( __( 'Languages & switcher', 'semrs-core' ), __( 'Language switcher with linked translations and hreflang tags, plus optional Google Translate for untranslated pages.', 'semrs-core' ) ),
		'popups'     => array( __( 'Popups', 'semrs-core' ), __( 'Popups built with the block editor: centered, bottom bar or slide-in, with delay, scroll, exit-intent or click triggers and page targeting.', 'semrs-core' ) ),
		'conditions' => array( __( 'Display conditions', 'semrs-core' ), __( 'Show any block only to logged-in visitors, certain roles, between dates or on certain pages.', 'semrs-core' ) ),
		'two_factor' => array( __( 'Login security', 'semrs-core' ), __( 'Two-factor log-in with an authenticator app and backup codes, and a custom log-in address.', 'semrs-core' ) ),
		'activity'   => array( __( 'Activity log', 'semrs-core' ), __( 'See who changed which post, page, setting, plugin or user, and when. Filter and export.', 'semrs-core' ) ),
		'search_replace' => array( __( 'Search & replace', 'semrs-core' ), __( 'Find and replace text or links across posts, custom fields and settings, with a preview and undo.', 'semrs-core' ) ),
		'security_pro' => array( __( 'SEMRS Assets Protector', 'semrs-core' ), __( 'Firewall that runs before other plugins, malware scanner with one-click repair, daily scans with email alerts, and spam protection for comments and forms without CAPTCHAs.', 'semrs-core' ) ),
		'backup'     => array( __( 'Backups & migration', 'semrs-core' ), __( 'Scheduled whole-site or partial backups to S3, Dropbox, Google Drive, FTP, SFTP, WebDAV or email; one-click restore, rollback before updates and one-click migration of large sites.', 'semrs-core' ) ),
		'og_images'  => array( __( 'Social images', 'semrs-core' ), __( 'Automatic share images with the post title for posts without a featured image.', 'semrs-core' ) ),
		'tools_pro'  => array( __( 'Free Tools Pro', 'semrs-core' ), __( 'Bulk checks in the server tools, your own expert-help box per category, a tool usage report and higher limits (needs the free SEMRS Tools plugin).', 'semrs-core' ) ),
		'leads'      => array( __( 'Lead tracking', 'semrs-core' ), __( 'Form submissions sent to Google Analytics 4 as generate_lead, then a thank-you page.', 'semrs-core' ) ),
	);
}

/**
 * Filter groups.
 *
 * @return array
 */
function semrs_core_groups() {
	return array(
		'seo'    => __( 'SEO', 'semrs-core' ),
		'speed'  => __( 'Speed', 'semrs-core' ),
		'ads'    => __( 'Ads', 'semrs-core' ),
		'design' => __( 'Design', 'semrs-core' ),
		'leads'  => __( 'Leads', 'semrs-core' ),
		'privacy' => __( 'Privacy & security', 'semrs-core' ),
	);
}

/**
 * Admin screen assets (SEMRS screens only).
 *
 * @param string $hook Screen hook.
 */
function semrs_core_admin_assets( $hook ) {
	if ( 'toplevel_page_semrs' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'semrs-admin', SEMRS_CORE_URL . 'admin/admin.css', array(), SEMRS_CORE_VERSION );
	wp_enqueue_script( 'semrs-admin', SEMRS_CORE_URL . 'admin/admin.js', array(), SEMRS_CORE_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'semrs_core_admin_assets' );

/**
 * One feature card.
 *
 * @param string $key    Module key.
 * @param string $label  Label.
 * @param string $desc   Description.
 * @param string $tier   free|pro.
 * @param bool   $locked Pro not installed, or not in this site's add-ons.
 */
function semrs_core_card( $key, $label, $desc, $tier, $locked ) {
	$meta  = semrs_core_module_meta()[ $key ] ?? array( 'seo', 'admin-generic', '' );
	$on    = ! $locked && semrs_core_module_on( $key );
	$group = $meta[0];
	printf(
		'<div class="semrs-card%1$s" data-tier="%2$s" data-group="%3$s" data-search="%4$s">',
		$locked ? ' is-locked' : ( $on ? ' is-on' : '' ),
		esc_attr( $tier ),
		esc_attr( $group ),
		esc_attr( strtolower( $label . ' ' . $desc ) )
	);
	echo '<div class="semrs-card__top"><span class="dashicons dashicons-' . esc_attr( $meta[1] ) . '" aria-hidden="true"></span>';
	echo '<span class="semrs-badge semrs-badge--' . esc_attr( $tier ) . '">' . ( 'pro' === $tier ? esc_html__( 'PRO', 'semrs-core' ) : esc_html__( 'FREE', 'semrs-core' ) ) . '</span>';
	// [url, button text, lock title]; SEMRS Pro names the add-on a locked module belongs to.
	$unlock = (array) apply_filters( 'semrs_core_unlock', array( semrs_core_pro_url(), __( 'Unlock with Pro', 'semrs-core' ), __( 'Included in SEMRS Pro', 'semrs-core' ) ), $key );
	if ( $locked ) {
		echo '<span class="semrs-lock dashicons dashicons-lock" title="' . esc_attr( $unlock[2] ) . '"></span>';
	} else {
		printf(
			'<label class="semrs-switch"><input type="checkbox" name="semrs_core_modules[%1$s]" value="1" %2$s><span class="semrs-switch__ui" aria-hidden="true"></span><span class="screen-reader-text">%3$s</span></label>',
			esc_attr( $key ),
			checked( $on, true, false ),
			/* translators: %s: feature name */
			esc_html( sprintf( __( 'Turn %s on or off', 'semrs-core' ), $label ) )
		);
	}
	echo '</div>';
	echo '<h3>' . esc_html( $label ) . '</h3><p>' . esc_html( $desc ) . '</p>';
	echo '<div class="semrs-card__foot">';
	if ( $locked ) {
		echo '<a class="button button-primary" href="' . esc_url( $unlock[0] ) . '" target="_blank" rel="noopener">' . esc_html( $unlock[1] ) . '</a>';
	} elseif ( $meta[2] && $on ) {
		echo '<a class="button button-primary" href="' . esc_url( $meta[2] ) . '">' . esc_html( $meta[3] ?? __( 'Settings', 'semrs-core' ) ) . '</a>';
	} elseif ( ! $on ) {
		echo '<span class="semrs-card__off">' . esc_html__( 'Switched off', 'semrs-core' ) . '</span>';
	}
	/* translators: %s: feature name */
	echo '<a class="button" href="' . esc_url( semrs_core_module_docs( $key ) ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( __( 'How to use %s (opens in a new tab)', 'semrs-core' ), $label ) ) . '">' . esc_html__( 'How to use', 'semrs-core' ) . '</a>';
	echo '</div></div>';
}

/**
 * Features page.
 */
function semrs_core_render_dashboard() {
	$modules = semrs_core_modules();
	$has_pro = defined( 'SEMRS_PRO_VERSION' );
	?>
	<div class="wrap semrs-ui">
		<div class="semrs-head">
			<div>
				<h1><?php esc_html_e( 'SEMRS', 'semrs-core' ); ?> <span class="semrs-ver">Core <?php echo esc_html( SEMRS_CORE_VERSION ); ?><?php echo $has_pro ? ' · Pro ' . esc_html( SEMRS_PRO_VERSION ) : ''; ?></span></h1>
				<p><?php esc_html_e( 'Every SEMRS feature in one place. Switch on only what you use: a switched-off feature loads no code at all.', 'semrs-core' ); ?></p>
			</div>
			<div class="semrs-head__links">
				<a class="button" href="<?php echo esc_url( apply_filters( 'semrs_docs_url', 'https://semrs.com/support/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'User manual', 'semrs-core' ); ?></a>
				<?php if ( ! $has_pro ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( semrs_core_pro_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get SEMRS Pro', 'semrs-core' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<hr class="wp-header-end">

		<?php if ( function_exists( 'semrs_seo_conflict' ) && semrs_seo_conflict() ) : ?>
			<div class="notice notice-warning inline"><p>
				<?php
				/* translators: %s: plugin name */
				printf( esc_html__( '%s is active, so SEMRS SEO output is paused to avoid duplicate tags. Your existing titles and descriptions are read automatically. When ready: deactivate it, then check a few pages with View Source.', 'semrs-core' ), esc_html( semrs_seo_conflict() ) );
				?>
			</p></div>
		<?php endif; ?>

		<div class="semrs-toolbar" role="search">
			<label class="screen-reader-text" for="semrs-search"><?php esc_html_e( 'Search features', 'semrs-core' ); ?></label>
			<input type="search" id="semrs-search" placeholder="<?php esc_attr_e( 'Search features…', 'semrs-core' ); ?>">
			<div class="semrs-chips" role="group" aria-label="<?php esc_attr_e( 'Filter', 'semrs-core' ); ?>">
				<button type="button" class="semrs-chip is-active" data-filter="all"><?php esc_html_e( 'All', 'semrs-core' ); ?></button>
				<button type="button" class="semrs-chip" data-filter="tier:free"><?php esc_html_e( 'Free', 'semrs-core' ); ?></button>
				<button type="button" class="semrs-chip" data-filter="tier:pro"><?php esc_html_e( 'Pro', 'semrs-core' ); ?></button>
				<?php foreach ( semrs_core_groups() as $g => $label ) : ?>
					<button type="button" class="semrs-chip" data-filter="group:<?php echo esc_attr( $g ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_core_modules' ); ?>
			<div class="semrs-grid">
				<?php
				foreach ( $modules as $key => $m ) {
					// SEMRS Pro locks modules that are not in this site's add-ons.
					semrs_core_card( $key, $m[0], $m[1], $m[2] ?? 'free', (bool) apply_filters( 'semrs_core_module_locked', false, $key ) );
				}
				foreach ( semrs_core_pro_teasers() as $key => $t ) {
					if ( ! isset( $modules[ $key ] ) ) {
						semrs_core_card( $key, $t[0], $t[1], 'pro', true );
					}
				}
				?>
			</div>
			<p class="semrs-empty-msg" hidden><?php esc_html_e( 'No features match your search.', 'semrs-core' ); ?></p>
			<div class="semrs-savebar"><?php submit_button( __( 'Save changes', 'semrs-core' ), 'primary', 'submit', false ); ?></div>
		</form>
	</div>
	<?php
}

/**
 * Folding for long tables and long settings screens on every SEMRS screen
 * (Core, Pro and add-on pages, Tools → Search & replace, Users → Roles).
 */
function semrs_core_fold_assets() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- only picks which screens get the script.
	if ( 0 !== strpos( $page, 'semrs' ) ) {
		return;
	}
	wp_enqueue_script( 'semrs-fold', SEMRS_CORE_URL . 'admin/fold.js', array(), SEMRS_CORE_VERSION, true );
	wp_localize_script(
		'semrs-fold',
		'semrsFoldText',
		array(
			/* translators: %d: number of rows. */
			'all'     => __( 'Show all %d rows', 'semrs-core' ),
			'less'    => __( 'Show fewer', 'semrs-core' ),
			'foldAll' => __( 'Fold all', 'semrs-core' ),
			'openAll' => __( 'Unfold all', 'semrs-core' ),
		)
	);
	wp_register_style( 'semrs-fold', false, array(), SEMRS_CORE_VERSION );
	wp_enqueue_style( 'semrs-fold' );
	wp_add_inline_style( 'semrs-fold', '.semrs-fold-more{margin:8px 0 16px}.semrs-folded{display:none!important}.semrs-fold-bar{display:flex;gap:6px;margin:8px 0}.semrs-fold-head{display:inline-flex;align-items:center;gap:8px;padding:0;border:0;background:none;color:inherit;font:inherit;cursor:pointer;text-align:left}.semrs-fold-head:hover{color:#2271b1}.semrs-fold-head:focus-visible{outline:2px solid #2271b1;outline-offset:2px}.semrs-fold-ico{display:inline-block;width:0;height:0;border-left:6px solid currentColor;border-top:5px solid transparent;border-bottom:5px solid transparent;transform:rotate(90deg);transition:transform .15s}.semrs-fold-head[aria-expanded=false] .semrs-fold-ico{transform:none}' );
}
add_action( 'admin_enqueue_scripts', 'semrs_core_fold_assets' );
