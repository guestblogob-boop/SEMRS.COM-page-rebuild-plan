<?php
/**
 * SEMRS → Speed screen: Optimize, Page cache, Database and Speed check tabs.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Speed', 'semrs-core' ), __( 'Speed', 'semrs-core' ), 'manage_options', 'semrs-speed', 'semrs_speed_page' );
	},
	22
);

add_action(
	'admin_init',
	function () {
		register_setting( 'semrs_speed', 'semrs_speed', array( 'sanitize_callback' => 'semrs_speed_sanitize' ) );
	}
);

/**
 * Sanitize. Each tab saves only its own options.
 *
 * @param array $in Raw input.
 * @return array
 */
function semrs_speed_sanitize( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$out = semrs_speed_settings();
	if ( ! isset( $in['_tab'] ) ) {
		// Already sanitized (WordPress runs this twice when the option is first added) or saved by code.
		foreach ( $out as $k => $v ) {
			if ( array_key_exists( $k, $in ) ) {
				$out[ $k ] = is_bool( $v ) ? ! empty( $in[ $k ] ) : ( is_int( $v ) ? absint( $in[ $k ] ) : sanitize_textarea_field( (string) $in[ $k ] ) );
			}
		}
		return $out;
	}
	$in  = wp_unslash( $in );
	$tab = sanitize_key( $in['_tab'] );
	if ( 'cache' === $tab ) {
		$out['cache']         = ! empty( $in['cache'] );
		$out['cache_ttl']     = max( 1, min( 10, absint( $in['cache_ttl'] ?? 10 ) ) );
		$out['cache_exclude'] = sanitize_textarea_field( $in['cache_exclude'] ?? '' );
	} else {
		foreach ( array_keys( semrs_speed_fields() ) as $k ) {
			$out[ $k ] = ! empty( $in[ $k ] );
		}
		$out['defer_exclude'] = sanitize_textarea_field( $in['defer_exclude'] ?? '' );
		$out['async_handles'] = sanitize_textarea_field( $in['async_handles'] ?? '' );
	}
	unset( $out['_tab'] );
	return $out;
}

/**
 * Settings page.
 */
function semrs_speed_page() {
	$tabs = array(
		'optimize' => __( 'Optimize', 'semrs-core' ),
		'cache'    => __( 'Page cache', 'semrs-core' ),
		'database' => __( 'Database', 'semrs-core' ),
		'check'    => __( 'Speed check', 'semrs-core' ),
	);
	$tab  = isset( $_GET['tab'] ) && isset( $tabs[ sanitize_key( $_GET['tab'] ) ] ) ? sanitize_key( $_GET['tab'] ) : 'optimize'; // phpcs:ignore WordPress.Security.NonceVerification -- navigation.
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEMRS Speed', 'semrs-core' ); ?></h1>
		<style>
			.semrs-site-checks{list-style:none;margin:0;padding:0}.semrs-site-checks li{display:grid;grid-template-columns:22px 1fr;gap:4px 8px;padding:8px 0;border-bottom:1px solid #f0f0f1}
			.semrs-site-checks .dot{width:12px;height:12px;border-radius:50%;margin-top:4px}.dot.good{background:#00813a}.dot.warn{background:#dba617}.dot.bad{background:#b32d2e}
			.semrs-site-checks p{grid-column:2;margin:0;color:#50575e}
			.semrs-pro-row th{color:#50575e}.semrs-pro-badge{display:inline-block;background:#f0b849;color:#1d2327;font-size:11px;font-weight:700;border-radius:3px;padding:1px 6px;margin-left:6px;vertical-align:middle}
			.semrs-cache-status{display:flex;gap:10px;align-items:center;background:#fff;border:1px solid #dcdcde;border-left-width:4px;padding:10px 14px;max-width:820px;margin:12px 0}
			.semrs-cache-status.on{border-left-color:#00813a}.semrs-cache-status.off{border-left-color:#8c8f94}.semrs-cache-status.paused{border-left-color:#dba617}
		</style>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a class="nav-tab <?php echo $k === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-speed&tab=' . $k ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		if ( 'database' === $tab ) {
			semrs_speed_db_tab();
		} elseif ( 'check' === $tab ) {
			semrs_speed_check_tab();
		} elseif ( 'cache' === $tab ) {
			semrs_speed_cache_tab();
		} else {
			semrs_speed_optimize_tab();
		}
		?>
	</div>
	<?php
}

/**
 * Optimize tab.
 */
function semrs_speed_optimize_tab() {
	$s = semrs_speed_settings();
	?>
	<p><?php esc_html_e( 'Each switch removes work from every page view. Test your site after changing the options marked as off by default.', 'semrs-core' ); ?></p>
	<form method="post" action="options.php">
		<?php settings_fields( 'semrs_speed' ); ?>
		<input type="hidden" name="semrs_speed[_tab]" value="optimize">
		<table class="form-table" role="presentation">
		<?php foreach ( semrs_speed_fields() as $k => $f ) : ?>
			<tr><th><?php echo esc_html( $f[0] ); ?><?php echo $f[1] ? '' : ' <em style="font-weight:400">(' . esc_html__( 'off by default', 'semrs-core' ) . ')</em>'; ?></th>
			<td><label><input type="checkbox" name="semrs_speed[<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( ! empty( $s[ $k ] ) ); ?>> <?php esc_html_e( 'On', 'semrs-core' ); ?></label><?php echo $f[2] ? '<p class="description">' . esc_html( $f[2] ) . '</p>' : ''; ?></td></tr>
		<?php endforeach; ?>
			<tr><th><?php esc_html_e( 'Never defer these script handles', 'semrs-core' ); ?></th><td><textarea name="semrs_speed[defer_exclude]" rows="3" class="large-text code"><?php echo esc_textarea( $s['defer_exclude'] ); ?></textarea><p class="description"><?php esc_html_e( 'One handle per line (e.g. jquery).', 'semrs-core' ); ?></p></td></tr>
			<tr><th><?php esc_html_e( 'Stylesheets to load without blocking', 'semrs-core' ); ?></th><td><textarea name="semrs_speed[async_handles]" rows="3" class="large-text code" placeholder="contact-form-7"><?php echo esc_textarea( $s['async_handles'] ); ?></textarea><p class="description"><?php esc_html_e( 'One style handle per line (the id of the <link> tag without "-css"). Never list the theme\'s main stylesheet, or the page will flash unstyled.', 'semrs-core' ); ?></p></td></tr>
			<?php do_action( 'semrs_speed_optimize_fields' ); ?>
		</table>
		<?php submit_button(); ?>
	</form>
	<?php
	if ( ! has_action( 'semrs_speed_optimize_fields' ) ) {
		$pro = array(
			__( 'Delay JavaScript until the visitor interacts', 'semrs-core' ) => __( 'Analytics, pixels, chat and heatmap scripts wait until the first scroll, tap or key press, which raises mobile PageSpeed scores.', 'semrs-core' ),
			__( 'Cache preload', 'semrs-core' )                                 => __( 'Rebuilds the page cache in the background after it clears, so the first visitor never waits.', 'semrs-core' ),
			__( 'CDN', 'semrs-core' )                                           => __( 'Serves images, CSS and JavaScript from your CDN address.', 'semrs-core' ),
		);
		echo '<h2>' . esc_html__( 'More with SEMRS Pro', 'semrs-core' ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $pro as $label => $help ) {
			echo '<tr class="semrs-pro-row"><th><span class="dashicons dashicons-lock" aria-hidden="true"></span> ' . esc_html( $label ) . '<span class="semrs-pro-badge">PRO</span></th><td><p class="description">' . esc_html( $help ) . '</p></td></tr>';
		}
		echo '</table><p><a class="button" href="' . esc_url( semrs_core_pro_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Unlock with Pro', 'semrs-core' ) . '</a></p>';
	}
}

/**
 * Page cache tab.
 */
function semrs_speed_cache_tab() {
	$s        = semrs_speed_settings();
	$conflict = semrs_cache_conflict();
	$writable = wp_mkdir_p( semrs_cache_dir() ) && wp_is_writable( semrs_cache_dir() );
	list( $files, $bytes ) = semrs_cache_stats();
	if ( $conflict ) {
		/* translators: %s: plugin name */
		$status = array( 'paused', sprintf( __( 'Paused: %s already handles page caching. Use one page cache only.', 'semrs-core' ), $conflict ) );
	} elseif ( ! $writable ) {
		$status = array( 'paused', __( 'The folder wp-content/cache/semrs/ cannot be created or written. Ask your host to make wp-content writable.', 'semrs-core' ) );
	} elseif ( semrs_cache_enabled() ) {
		/* translators: 1: number of pages, 2: size */
		$status = array( 'on', sprintf( __( 'On. %1$d cached pages (%2$s).', 'semrs-core' ), $files, size_format( $bytes ) ) );
	} else {
		$status = array( 'off', __( 'Off.', 'semrs-core' ) );
	}
	?>
	<div class="semrs-cache-status <?php echo esc_attr( $status[0] ); ?>">
		<strong><?php esc_html_e( 'Page cache:', 'semrs-core' ); ?></strong> <span><?php echo esc_html( $status[1] ); ?></span>
		<?php if ( $files ) : ?>
			<a class="button" style="margin-left:auto" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=semrs_cache_clear' ), 'semrs_cache_clear' ) ); ?>"><?php esc_html_e( 'Clear cache', 'semrs-core' ); ?></a>
		<?php endif; ?>
	</div>
	<p style="max-width:820px"><?php esc_html_e( 'The page cache saves each finished page as a file and sends that file to the next visitors, so WordPress does not build the page again. Pages typically load several times faster from the cache.', 'semrs-core' ); ?></p>
	<form method="post" action="options.php">
		<?php settings_fields( 'semrs_speed' ); ?>
		<input type="hidden" name="semrs_speed[_tab]" value="cache">
		<table class="form-table" role="presentation">
			<tr><th><?php esc_html_e( 'Page cache', 'semrs-core' ); ?></th><td><label><input type="checkbox" name="semrs_speed[cache]" value="1" <?php checked( ! empty( $s['cache'] ) ); ?> <?php disabled( (bool) $conflict ); ?>> <?php esc_html_e( 'Cache pages for visitors who are not logged in', 'semrs-core' ); ?></label></td></tr>
			<tr><th><label for="semrs-cache-ttl"><?php esc_html_e( 'Keep cached pages for', 'semrs-core' ); ?></label></th><td><input id="semrs-cache-ttl" type="number" min="1" max="10" name="semrs_speed[cache_ttl]" value="<?php echo esc_attr( $s['cache_ttl'] ); ?>" class="small-text"> <?php esc_html_e( 'hours', 'semrs-core' ); ?><p class="description"><?php esc_html_e( 'At most 10 hours, so forms on cached pages keep working. The cache also clears whenever you publish or update content, menus, widgets or settings.', 'semrs-core' ); ?></p></td></tr>
			<tr><th><label for="semrs-cache-ex"><?php esc_html_e( 'Never cache these URLs', 'semrs-core' ); ?></label></th><td><textarea id="semrs-cache-ex" name="semrs_speed[cache_exclude]" rows="4" class="large-text code" placeholder="/members/*"><?php echo esc_textarea( $s['cache_exclude'] ); ?></textarea><p class="description"><?php esc_html_e( 'One path per line, for example /quote/ or /members/* (a * at the end matches everything below).', 'semrs-core' ); ?></p></td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<h2><?php esc_html_e( 'Never cached', 'semrs-core' ); ?></h2>
	<ul style="list-style:disc;margin-left:20px;max-width:820px">
		<li><?php esc_html_e( 'Logged-in users, visitors who left a comment, and shoppers with items in a WooCommerce or Easy Digital Downloads cart', 'semrs-core' ); ?></li>
		<li><?php esc_html_e( 'Search results, 404 pages, previews, feeds, sitemaps, password-protected pages, cart, checkout and account pages', 'semrs-core' ); ?></li>
		<li><?php esc_html_e( 'URLs with a query string (except tracking tags such as utm_source, gclid and fbclid)', 'semrs-core' ); ?></li>
		<li><?php esc_html_e( 'Form submissions and every admin, REST and AJAX request', 'semrs-core' ); ?></li>
	</ul>
	<p class="description" style="max-width:820px"><?php esc_html_e( 'Phones and computers get separate copies. Developers can stop a page from being cached with define( \'DONOTCACHEPAGE\', true ), or switch the cache off with define( \'SEMRS_CACHE_OFF\', true ) in wp-config.php.', 'semrs-core' ); ?></p>
	<?php
}
