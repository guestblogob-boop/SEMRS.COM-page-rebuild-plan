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
					$out[ $key ] = ! empty( $in[ $key ] );
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
			'image_seo'  => array( 'seo', 'format-image', '' ),
			'layouts'    => array( 'design', 'layout', '' ),
			'proof'      => array( 'design', 'awards', '' ),
			'leads'      => array( 'leads', 'email-alt', '' ),
			'blocks_pro' => array( 'design', 'block-default', admin_url( 'admin.php?page=semrs-blocks' ) ),
			'theme_pro'  => array( 'design', 'admin-appearance', admin_url( 'customize.php' ) ),
			'conditions' => array( 'design', 'visibility', '' ),
			'popups'     => array( 'leads', 'feedback', admin_url( 'edit.php?post_type=semrs_popup' ) ),
			'languages'  => array( 'seo', 'translation', admin_url( 'admin.php?page=semrs-languages' ) ),
			'templates_pro' => array( 'design', 'layout', admin_url( 'admin.php?page=semrs-templates' ) ),
			'seo_bulk'   => array( 'seo', 'editor-table', admin_url( 'admin.php?page=semrs-seo-bulk' ) ),
			'speed_pro'  => array( 'speed', 'dashboard', admin_url( 'admin.php?page=semrs-speed' ) ),
			'ads_pro'    => array( 'ads', 'chart-bar', admin_url( 'admin.php?page=semrs-ads' ) ),
		)
	);
}

/**
 * Pro features shown (locked) when SEMRS Pro is not installed:
 * key => [label, description].
 *
 * @return array
 */
function semrs_core_pro_teasers() {
	return array(
		'redirects'  => array( __( 'Redirects & 404 monitor', 'semrs-core' ), __( '301/302/307/410 redirects with wildcards, a 404 log and one-click fixes. Imports Rank Math redirects.', 'semrs-core' ) ),
		'local'      => array( __( 'Multi-location Local SEO', 'semrs-core' ), __( 'Locations with address, hours, map link and service areas; LocalBusiness schema for each one.', 'semrs-core' ) ),
		'indexnow'   => array( __( 'IndexNow instant indexing', 'semrs-core' ), __( 'Tells Bing, Yandex and others about new and updated pages the moment you publish.', 'semrs-core' ) ),
		'seo_bulk'   => array( __( 'Bulk SEO editor', 'semrs-core' ), __( 'Edit SEO titles, meta descriptions, focus keywords and noindex for many posts on one screen, with length counters.', 'semrs-core' ) ),
		'speed_pro'  => array( __( 'Speed Pro', 'semrs-core' ), __( 'Delay analytics, pixel and chat scripts until the visitor interacts, preload the page cache in the background, and serve files from a CDN.', 'semrs-core' ) ),
		'ads_pro'    => array( __( 'Ads Pro', 'semrs-core' ), __( 'More placements, targeting by category, post age and dates, A/B rotation, click protection and a polite ad-blocker message.', 'semrs-core' ) ),
		'image_seo'  => array( __( 'Image SEO', 'semrs-core' ), __( 'Fills missing alt text and titles automatically from clean file names.', 'semrs-core' ) ),
		'blocks_pro' => array( __( 'Pro Blocks', 'semrs-core' ), __( 'Slider and carousel, animated counter, 3D model viewer and scroll animations on every block.', 'semrs-core' ) ),
		'theme_pro'  => array( __( 'Theme Pro', 'semrs-core' ), __( 'Dark mode switch, mega menu, automatic table of contents, extra section patterns and white label for the SEMRS theme.', 'semrs-core' ) ),
		'layouts'    => array( __( 'Site Layouts (custom hooks)', 'semrs-core' ), __( 'Show any block content at a theme position with display rules.', 'semrs-core' ) ),
		'proof'      => array( __( 'Case studies & portfolio', 'semrs-core' ), __( 'Case Studies, Portfolio and Testimonials with permission checks.', 'semrs-core' ) ),
		'templates_pro' => array( __( 'Pro starter sites', 'semrs-core' ), __( 'Extra one-click starter sites (Consultant, Clinic) with Pro sections.', 'semrs-core' ) ),
		'languages'  => array( __( 'Languages & switcher', 'semrs-core' ), __( 'Language switcher with linked translations and hreflang tags, plus optional Google Translate for untranslated pages.', 'semrs-core' ) ),
		'popups'     => array( __( 'Popups', 'semrs-core' ), __( 'Popups built with the block editor: centred, bottom bar or slide-in, with delay, scroll, exit-intent or click triggers and page targeting.', 'semrs-core' ) ),
		'conditions' => array( __( 'Display conditions', 'semrs-core' ), __( 'Show any block only to logged-in visitors, certain roles, between dates or on certain pages.', 'semrs-core' ) ),
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
 * @param bool   $locked Pro not installed.
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
	if ( $locked ) {
		echo '<span class="semrs-lock dashicons dashicons-lock" title="' . esc_attr__( 'Included in SEMRS Pro', 'semrs-core' ) . '"></span>';
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
		echo '<a class="button button-primary" href="' . esc_url( semrs_core_pro_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Unlock with Pro', 'semrs-core' ) . '</a>';
	} elseif ( $meta[2] && $on ) {
		echo '<a class="button" href="' . esc_url( $meta[2] ) . '">' . esc_html__( 'Settings', 'semrs-core' ) . '</a>';
	}
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
					semrs_core_card( $key, $m[0], $m[1], $m[2] ?? 'free', false );
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
