<?php
/**
 * SEMRS → Index Status: is every page live, and is Google showing it?
 *
 * Live: each published page is fetched (in small batches) for its HTTP status.
 * Indexed: a page that received Google impressions in the last 90 days is indexed. The data comes
 * from Search Console through Site Kit by Google. Google has no public API for a site to request
 * indexing of normal pages, so pending pages become indexing tasks with a one-click link to
 * Search Console's URL Inspection ("Request indexing"), and new or updated pages are submitted
 * automatically to Bing, Yandex and other IndexNow engines.
 *
 * Also here: the robots.txt editor (physical file or SEMRS virtual file).
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published, viewable posts and pages to check.
 *
 * @return int[]
 */
function semrs_index_ids() {
	return get_posts( array( 'post_type' => semrs_seo_public_types(), 'post_status' => 'publish', 'posts_per_page' => 2000, 'fields' => 'ids', 'has_password' => false, 'no_found_rows' => true, 'orderby' => 'date', 'order' => 'DESC' ) );
}

/**
 * Saved live checks: post ID => [code, final URL, checked time].
 *
 * @return array
 */
function semrs_index_live() {
	$v = get_option( 'semrs_index_live', array() );
	return is_array( $v ) ? $v : array();
}

/**
 * Fetch one page's HTTP status (no redirects followed, so they show as redirects).
 *
 * @param int $id Post ID.
 * @return array{0:int,1:string,2:int}
 */
function semrs_index_check( $id ) {
	$url  = get_permalink( $id );
	$res  = wp_remote_get( $url, array( 'timeout' => 15, 'redirection' => 0, 'limit_response_size' => 65536, 'user-agent' => 'Mozilla/5.0 (compatible; SEMRS Index Status; +' . home_url( '/' ) . ')' ) );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	$loc  = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_header( $res, 'location' );
	return array( $code, $loc, time() );
}

/**
 * Search Console property (from Site Kit), e.g. sc-domain:example.com or https://example.com/.
 *
 * @return string
 */
function semrs_index_property() {
	$gsc = (array) get_option( 'googlesitekit_search-console_settings', array() );
	return (string) ( $gsc['propertyID'] ?? '' );
}

/**
 * Pages with Google impressions in the last 90 days: path => [clicks, impressions, position].
 *
 * @return array|WP_Error
 */
function semrs_index_google() {
	if ( ! defined( 'GOOGLESITEKIT_VERSION' ) || '' === semrs_index_property() ) {
		return new WP_Error( 'semrs_index_sitekit', __( 'Connect Site Kit by Google (free) to Search Console to see which pages Google shows.', 'semrs-core' ) );
	}
	$cached = get_transient( 'semrs_index_google' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$switched = false;
	if ( ! get_current_user_id() ) {
		$owner = (int) ( ( (array) get_option( 'googlesitekit_search-console_settings', array() ) )['ownerID'] ?? 0 );
		if ( $owner ) {
			wp_set_current_user( $owner );
			$switched = true;
		}
	}
	$req = new WP_REST_Request( 'GET', '/google-site-kit/v1/modules/search-console/data/searchanalytics' );
	foreach ( array( 'startDate' => gmdate( 'Y-m-d', strtotime( '-92 days' ) ), 'endDate' => gmdate( 'Y-m-d', strtotime( '-2 days' ) ), 'dimensions' => 'page', 'limit' => 5000 ) as $k => $v ) {
		$req->set_param( $k, $v );
	}
	$res = rest_do_request( $req );
	if ( $switched ) {
		wp_set_current_user( 0 );
	}
	$data = $res->get_data();
	if ( $res->is_error() || ! is_array( $data ) || isset( $data['code'] ) ) {
		return new WP_Error( 'semrs_index_gsc', is_array( $data ) && isset( $data['message'] ) ? $data['message'] : __( 'Site Kit could not load Search Console data.', 'semrs-core' ) );
	}
	$out = array();
	foreach ( $data as $row ) {
		$path         = trailingslashit( strtolower( (string) wp_parse_url( (string) ( $row['keys'][0] ?? '' ), PHP_URL_PATH ) ) );
		$out[ $path ] = array( (int) $row['clicks'], (int) $row['impressions'], round( (float) $row['position'], 1 ) );
	}
	set_transient( 'semrs_index_google', $out, 6 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * Status of one page: [key, label, colour, detail].
 *
 * @param int         $id     Post ID.
 * @param array       $live   Saved live checks.
 * @param array|false $google Google pages (false when unknown).
 * @return array
 */
function semrs_index_status( $id, $live, $google ) {
	if ( semrs_seo_is_noindex( $id ) ) {
		return array( 'excluded', __( 'Excluded: noindex', 'semrs-core' ), '#646970', __( 'Hidden from search engines on purpose (thank-you, form and private pages).', 'semrs-core' ) );
	}
	$check = $live[ $id ] ?? null;
	if ( ! $check ) {
		return array( 'unchecked', __( 'Not checked yet', 'semrs-core' ), '#646970', '' );
	}
	list( $code, $loc ) = $check;
	if ( $code >= 300 && $code < 400 ) {
		/* translators: %s: address */
		return array( 'redirect', __( 'Redirects', 'semrs-core' ), '#b26200', sprintf( __( 'Goes to %s. Link to the final address, or remove the redirect.', 'semrs-core' ), $loc ) );
	}
	if ( 200 !== $code ) {
		/* translators: %s: HTTP status */
		return array( 'down', __( 'Not live', 'semrs-core' ), '#b32d2e', $code ? sprintf( __( 'The page answers with HTTP %s.', 'semrs-core' ), $code ) : __( 'The page did not answer.', 'semrs-core' ) );
	}
	if ( false === $google ) {
		return array( 'live', __( 'Live', 'semrs-core' ), '#00813a', __( 'Connect Site Kit to see whether Google shows it.', 'semrs-core' ) );
	}
	$path = trailingslashit( strtolower( (string) wp_parse_url( get_permalink( $id ), PHP_URL_PATH ) ) );
	if ( isset( $google[ $path ] ) ) {
		list( $clicks, $impr, $pos ) = $google[ $path ];
		/* translators: 1: impressions, 2: clicks, 3: position */
		return array( 'indexed', __( 'Live & indexed', 'semrs-core' ), '#00813a', sprintf( __( '%1$s impressions, %2$s clicks, average position %3$s (last 90 days).', 'semrs-core' ), number_format_i18n( $impr ), number_format_i18n( $clicks ), $pos ) );
	}
	$age = time() - (int) get_post_time( 'U', true, $id );
	if ( $age < 7 * DAY_IN_SECONDS ) {
		return array( 'waiting', __( 'Live: waiting for Google', 'semrs-core' ), '#2271b1', __( 'Published in the last 7 days. Google usually needs a few days.', 'semrs-core' ) );
	}
	return array( 'pending', __( 'Live, not indexed yet', 'semrs-core' ), '#b26200', __( 'No Google impressions in 90 days. Request indexing, add internal links to it, and check it in URL Inspection.', 'semrs-core' ) );
}

/**
 * Daily background re-check of the 25 oldest results.
 */
function semrs_index_cron() {
	$live = semrs_index_live();
	$ids  = semrs_index_ids();
	usort( $ids, function ( $a, $b ) use ( $live ) { return ( $live[ $a ][2] ?? 0 ) - ( $live[ $b ][2] ?? 0 ); } );
	foreach ( array_slice( $ids, 0, 25 ) as $id ) {
		$live[ $id ] = semrs_index_check( $id );
	}
	$live = array_intersect_key( $live, array_flip( $ids ) );
	update_option( 'semrs_index_live', $live, false );
}
add_action( 'semrs_index_cron', 'semrs_index_cron' );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'semrs_index_cron' ) ) {
			wp_schedule_event( time() + 900, 'daily', 'semrs_index_cron' );
		}
	}
);
// A page that changes is checked again soon.
add_action(
	'save_post',
	function ( $post_id ) {
		$live = semrs_index_live();
		if ( isset( $live[ $post_id ] ) ) {
			unset( $live[ $post_id ] );
			update_option( 'semrs_index_live', $live, false );
		}
	}
);

/* ---------------------------------------------------------------------------
 * robots.txt
 * ------------------------------------------------------------------------- */

/**
 * The robots.txt the site serves now.
 *
 * @return array{0:string,1:bool} Content, whether it is a physical file.
 */
function semrs_index_robots() {
	$file = ABSPATH . 'robots.txt';
	if ( file_exists( $file ) ) {
		return array( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local file.
	}
	ob_start();
	do_action( 'do_robots' );
	return array( (string) ob_get_clean(), false );
}

/**
 * Sitemap lines every robots.txt should list.
 *
 * @return string[]
 */
function semrs_index_sitemap_lines() {
	$lines = array( 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) );
	foreach ( array( 'sitemap-images.xml', 'sitemap-videos.xml', 'sitemap-news.xml' ) as $f ) {
		$s = (array) get_option( 'semrs_sitemaps_pro', array( 'images' => true, 'videos' => true ) );
		$k = str_replace( array( 'sitemap-', '.xml' ), '', $f );
		if ( function_exists( 'semrs_smp_pro' ) && semrs_smp_pro() && ( $s[ $k ] ?? ( 'news' !== $k ) ) ) {
			$lines[] = 'Sitemap: ' . home_url( '/' . $f );
		}
	}
	return $lines;
}

/* ---------------------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------------------- */

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Index Status', 'semrs-core' ), __( 'Index Status', 'semrs-core' ), 'manage_options', 'semrs-index', 'semrs_index_page' );
	},
	19
);

// Check pages in batches from the screen (AJAX).
add_action(
	'wp_ajax_semrs_index_step',
	function () {
		check_ajax_referer( 'semrs_index', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$ids    = semrs_index_ids();
		$offset = absint( $_POST['offset'] ?? 0 );
		$live   = 0 === $offset ? array() : semrs_index_live();
		foreach ( array_slice( $ids, $offset, 8 ) as $id ) {
			$live[ $id ] = semrs_index_check( $id );
		}
		update_option( 'semrs_index_live', $live, false );
		if ( 0 === $offset ) {
			delete_transient( 'semrs_index_google' );
		}
		$next = $offset + 8;
		wp_send_json_success( $next < count( $ids ) ? array( 'next' => $next, 'total' => count( $ids ) ) : array( 'done' => true, 'total' => count( $ids ) ) );
	}
);

// Actions: submit to IndexNow, mark task done, auto-index switch, robots.txt.
add_action(
	'admin_post_semrs_index',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-core' ) );
		}
		check_admin_referer( 'semrs_index' );
		$do  = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		$msg = 'saved';
		if ( 'submit' === $do || 'submit_all' === $do ) {
			$ids = 'submit' === $do ? array( absint( $_POST['id'] ?? 0 ) ) : array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) );
			foreach ( array_filter( $ids ) as $id ) {
				$GLOBALS['semrs_indexnow_urls'][ get_permalink( $id ) ] = true;
				$tasks        = (array) get_option( 'semrs_index_tasks', array() );
				$tasks[ $id ] = time();
				update_option( 'semrs_index_tasks', $tasks, false );
			}
			if ( function_exists( 'semrs_indexnow_send' ) ) {
				semrs_indexnow_send();
			}
			$msg = 'submitted';
		} elseif ( 'done' === $do ) {
			$done                                 = (array) get_option( 'semrs_index_done', array() );
			$done[ absint( $_POST['id'] ?? 0 ) ] = time();
			update_option( 'semrs_index_done', $done, false );
		} elseif ( 'auto' === $do ) {
			update_option( 'semrs_indexnow_on', empty( $_POST['on'] ) ? '' : '1', true );
		} elseif ( 'robots' === $do ) {
			$body = str_replace( "\r\n", "\n", sanitize_textarea_field( wp_unslash( $_POST['robots'] ?? '' ) ) );
			$file = ABSPATH . 'robots.txt';
			if ( ! empty( $_POST['virtual'] ) ) {
				// Use the SEMRS virtual robots.txt: keep the old file as a backup.
				if ( file_exists( $file ) ) {
					rename( $file, ABSPATH . 'robots.txt.bak-' . gmdate( 'Ymd-His' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- site root file.
				}
				$s                 = (array) get_option( 'semrs_seo', array() );
				$s['robots_extra'] = $body;
				update_option( 'semrs_seo', $s );
			} else {
				if ( file_exists( $file ) ) {
					copy( $file, ABSPATH . 'robots.txt.bak-' . gmdate( 'Ymd-His' ) );
				}
				$ok  = false !== file_put_contents( $file, rtrim( $body ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- site root file.
				$msg = $ok ? 'robots' : 'robots-failed';
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-index&msg=' . $msg ) );
		exit;
	}
);

/**
 * Small action button.
 *
 * @param string $do     Action.
 * @param string $label  Label.
 * @param array  $fields Hidden fields.
 * @param string $class  Class.
 */
function semrs_index_button( $do, $label, $fields = array(), $class = 'button button-small' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
	wp_nonce_field( 'semrs_index' );
	echo '<input type="hidden" name="action" value="semrs_index"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	foreach ( $fields as $k => $v ) {
		foreach ( (array) $v as $one ) {
			echo '<input type="hidden" name="' . esc_attr( is_array( $v ) ? $k . '[]' : $k ) . '" value="' . esc_attr( (string) $one ) . '">';
		}
	}
	echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
}

/**
 * Screen.
 */
function semrs_index_page() {
	$ids     = semrs_index_ids();
	$live    = semrs_index_live();
	$google  = semrs_index_google();
	$gerr    = is_wp_error( $google ) ? $google->get_error_message() : '';
	$google  = is_wp_error( $google ) ? false : $google;
	$prop    = semrs_index_property();
	$done    = (array) get_option( 'semrs_index_done', array() );
	$sent    = (array) get_option( 'semrs_index_tasks', array() );
	$rows    = array();
	$counts  = array();
	foreach ( $ids as $id ) {
		$st              = semrs_index_status( $id, $live, $google );
		$rows[ $id ]     = $st;
		$counts[ $st[0] ] = ( $counts[ $st[0] ] ?? 0 ) + 1;
	}
	$tasks = array_keys( array_filter( $rows, function ( $st, $id ) use ( $done ) { return 'pending' === $st[0] && empty( $done[ $id ] ); }, ARRAY_FILTER_USE_BOTH ) );
	$msgs  = array(
		'submitted'     => __( 'Sent to IndexNow (Bing, Yandex, Seznam, Naver). For Google, use Request indexing in Search Console.', 'semrs-core' ),
		'robots'        => __( 'robots.txt saved. The old file was kept as a backup.', 'semrs-core' ),
		'robots-failed' => __( 'robots.txt could not be written. Check the file permissions with your host.', 'semrs-core' ),
		'saved'         => __( 'Saved.', 'semrs-core' ),
	);
	$inspect = function ( $url ) use ( $prop ) {
		return 'https://search.google.com/search-console/inspect?resource_id=' . rawurlencode( $prop ) . '&id=' . rawurlencode( $url );
	};
	list( $robots, $physical ) = semrs_index_robots();
	$card = function ( $key, $label, $colour ) use ( $counts ) {
		echo '<div style="background:#fff;border:1px solid #dcdcde;border-left:4px solid ' . esc_attr( $colour ) . ';border-radius:6px;padding:10px 14px;min-width:150px"><div style="font-size:22px;font-weight:600">' . (int) ( $counts[ $key ] ?? 0 ) . '</div><div style="color:#50575e">' . esc_html( $label ) . '</div></div>';
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Index Status', 'semrs-core' ); ?></h1>
		<?php if ( isset( $_GET['msg'], $msgs[ $_GET['msg'] ] ) ) : // phpcs:ignore WordPress.Security.NonceVerification -- display only. ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msgs[ sanitize_key( $_GET['msg'] ) ] ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Is every page live, and is Google showing it? "Indexed" means the page had Google impressions in the last 90 days (Search Console data via Site Kit).', 'semrs-core' ); ?></p>
		<?php if ( $gerr ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $gerr ); ?></p></div>
		<?php endif; ?>
		<div style="display:flex;flex-wrap:wrap;gap:10px;margin:12px 0">
			<?php
			$card( 'indexed', __( 'Live & indexed', 'semrs-core' ), '#00813a' );
			$card( 'pending', __( 'Live, not indexed yet', 'semrs-core' ), '#b26200' );
			$card( 'waiting', __( 'Waiting for Google (new)', 'semrs-core' ), '#2271b1' );
			$card( 'redirect', __( 'Redirects', 'semrs-core' ), '#b26200' );
			$card( 'down', __( 'Not live', 'semrs-core' ), '#b32d2e' );
			$card( 'excluded', __( 'Excluded (noindex)', 'semrs-core' ), '#8c8f94' );
			$card( 'unchecked', __( 'Not checked yet', 'semrs-core' ), '#c3c4c7' );
			?>
		</div>
		<p>
			<button type="button" class="button button-primary" id="semrs-index-run"><?php esc_html_e( 'Check all pages now', 'semrs-core' ); ?></button>
			<span id="semrs-index-progress" class="description" aria-live="polite"><?php esc_html_e( 'Pages are also re-checked in the background every day.', 'semrs-core' ); ?></span>
		</p>

		<h2><?php esc_html_e( 'Indexing tasks', 'semrs-core' ); ?> (<?php echo count( $tasks ); ?>)</h2>
		<p class="description"><?php esc_html_e( 'Live pages Google has not shown yet. For each: open URL Inspection, click "Request indexing" in Search Console, then mark it done. Google offers no API for this, so it takes one click per page; Bing and other IndexNow engines are notified automatically.', 'semrs-core' ); ?></p>
		<p>
			<?php
			$on = function_exists( 'semrs_indexnow_enabled' ) && semrs_indexnow_enabled();
			semrs_index_button( 'auto', $on ? __( 'Auto-indexing is on (turn off)', 'semrs-core' ) : __( 'Turn on auto-indexing', 'semrs-core' ), array( 'on' => $on ? '' : '1' ), $on ? 'button' : 'button button-primary' );
			?>
			<span class="description"><?php esc_html_e( 'Auto-indexing submits every new, updated or removed page to IndexNow straight away and keeps your sitemap current for Google.', 'semrs-core' ); ?></span>
		</p>
		<?php if ( $tasks ) : ?>
			<p><?php semrs_index_button( 'submit_all', __( 'Submit all tasks to IndexNow', 'semrs-core' ), array( 'ids' => $tasks ), 'button' ); ?></p>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr><th><?php esc_html_e( 'Page', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Published', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Steps', 'semrs-core' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $tasks as $id ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $id ) ); ?></a><br><small><?php echo esc_html( str_replace( home_url(), '', get_permalink( $id ) ) ); ?></small></td>
						<td><?php echo esc_html( get_the_date( '', $id ) ); ?></td>
						<td>
							<?php if ( $prop ) : ?><a class="button button-small button-primary" href="<?php echo esc_url( $inspect( get_permalink( $id ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( '1. Request indexing in Google', 'semrs-core' ); ?></a> <?php endif; ?>
							<?php semrs_index_button( 'submit', ! empty( $sent[ $id ] ) ? __( '2. Sent to IndexNow ✓', 'semrs-core' ) : __( '2. Submit to IndexNow', 'semrs-core' ), array( 'id' => $id ) ); ?>
							<?php semrs_index_button( 'done', __( '3. Mark done', 'semrs-core' ), array( 'id' => $id ), 'button-link' ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No indexing tasks. Every live page is indexed, new, or already handled.', 'semrs-core' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'All pages', 'semrs-core' ); ?> (<?php echo count( $ids ); ?>)</h2>
		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><th><?php esc_html_e( 'Page', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Remarks', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Checked', 'semrs-core' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $id => $st ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a> · <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'semrs-core' ); ?></a></td>
					<td><span style="display:inline-block;padding:2px 8px;border-radius:999px;color:#fff;font-size:12px;background:<?php echo esc_attr( $st[2] ); ?>"><?php echo esc_html( $st[1] ); ?></span></td>
					<td><?php echo esc_html( $st[3] ); ?><?php if ( $prop && in_array( $st[0], array( 'pending', 'waiting', 'indexed' ), true ) ) : ?> <a href="<?php echo esc_url( $inspect( get_permalink( $id ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Inspect', 'semrs-core' ); ?></a><?php endif; ?></td>
					<td><?php echo ! empty( $live[ $id ][2] ) ? esc_html( human_time_diff( $live[ $id ][2] ) ) : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2 id="robots"><?php esc_html_e( 'robots.txt', 'semrs-core' ); ?></h2>
		<p class="description">
			<?php echo $physical ? esc_html__( 'Your site serves a robots.txt file from the site root. Edit it here; the previous version is kept as a backup.', 'semrs-core' ) : esc_html__( 'Your site uses the robots.txt that WordPress and SEMRS build. Lines you add here are appended.', 'semrs-core' ); ?>
			<a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View live robots.txt', 'semrs-core' ); ?></a>
		</p>
		<?php
		$missing = array_filter( semrs_index_sitemap_lines(), function ( $l ) use ( $robots ) { return false === stripos( $robots, trim( substr( $l, 8 ) ) ); } );
		if ( $missing ) :
			?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Add these sitemap lines so search engines find every sitemap:', 'semrs-core' ); ?><br><code><?php echo esc_html( implode( "\n", $missing ) ); ?></code></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_index' ); ?>
			<input type="hidden" name="action" value="semrs_index"><input type="hidden" name="do" value="robots">
			<textarea name="robots" rows="12" class="large-text code" spellcheck="false"><?php echo esc_textarea( $physical ? $robots : (string) ( semrs_seo_settings()['robots_extra'] ?? '' ) ); ?></textarea>
			<?php if ( $missing ) : ?>
				<p><button type="button" class="button" onclick="var t=this.form.robots;t.value=t.value.replace(/\s*$/,'')+'\n'+<?php echo esc_attr( wp_json_encode( implode( "\n", $missing ) ) ); ?>;"><?php esc_html_e( 'Add the missing sitemap lines', 'semrs-core' ); ?></button></p>
			<?php endif; ?>
			<?php if ( $physical ) : ?>
				<p><label><input type="checkbox" name="virtual" value="1"> <?php esc_html_e( 'Stop using the file and let SEMRS build robots.txt (the file is renamed to a backup; the text above becomes extra lines)', 'semrs-core' ); ?></label></p>
			<?php endif; ?>
			<?php submit_button( __( 'Save robots.txt', 'semrs-core' ), 'secondary' ); ?>
		</form>
	</div>
	<script>
	(function () {
		var btn = document.getElementById('semrs-index-run'), out = document.getElementById('semrs-index-progress');
		btn.addEventListener('click', function () {
			btn.disabled = true;
			(function step(offset) {
				var f = new FormData(); f.append('action', 'semrs_index_step'); f.append('offset', offset); f.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'semrs_index' ) ); ?>);
				fetch(ajaxurl, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
					if (!r.success) { out.textContent = 'Error'; btn.disabled = false; return; }
					if (r.data.done) { out.textContent = <?php echo wp_json_encode( __( 'Done. Reloading…', 'semrs-core' ) ); ?>; location.reload(); return; }
					out.textContent = r.data.next + ' / ' + r.data.total; step(r.data.next);
				}).catch(function () { out.textContent = 'Error'; btn.disabled = false; });
			})(0);
		});
	})();
	</script>
	<?php
}

/**
 * Posts and Pages lists: three columns from the same data as SEMRS → Index Status.
 * - Status: WordPress status plus whether the page loads (live, redirect, not live).
 * - Index status: live & indexed, waiting for Google, not indexed yet, noindex.
 * - Traffic: Google clicks, impressions and average position, last 90 days (Search Console via Site Kit).
 */
function semrs_index_columns_init() {
	foreach ( semrs_seo_public_types() as $type ) {
		add_filter( "manage_{$type}_posts_columns", 'semrs_index_column' );
		add_action( "manage_{$type}_posts_custom_column", 'semrs_index_column_cell', 10, 2 );
	}
}
add_action( 'admin_init', 'semrs_index_columns_init' );

/**
 * Add the columns.
 *
 * @param array $cols Columns.
 * @return array
 */
function semrs_index_column( $cols ) {
	$cols['semrs_status']  = __( 'Status', 'semrs-core' );
	$cols['semrs_index']   = __( 'Index status', 'semrs-core' );
	$cols['semrs_traffic'] = __( 'Traffic (90 days)', 'semrs-core' );
	return $cols;
}

/**
 * Data for the columns, loaded once per screen.
 *
 * @return array{live:array,google:array|false,prop:string}
 */
function semrs_index_column_data() {
	static $d = null;
	if ( null === $d ) {
		$g = semrs_index_google(); // Cached for 6 hours.
		$d = array(
			'live'   => semrs_index_live(),
			'google' => is_wp_error( $g ) ? false : $g,
			'prop'   => semrs_index_property(),
		);
	}
	return $d;
}

/**
 * A coloured dot and a label.
 *
 * @param string $label Label.
 * @param string $color Colour.
 * @param string $title Tooltip.
 * @return string
 */
function semrs_index_dot( $label, $color, $title = '' ) {
	return sprintf(
		'<span title="%3$s" style="display:inline-flex;align-items:center;gap:6px"><span aria-hidden="true" style="flex:none;width:9px;height:9px;border-radius:50%%;background:%2$s"></span>%1$s</span>',
		esc_html( $label ),
		esc_attr( $color ),
		esc_attr( $title )
	);
}

/**
 * Print one cell.
 *
 * @param string $col Column key.
 * @param int    $id  Post ID.
 */
function semrs_index_column_cell( $col, $id ) {
	if ( ! in_array( $col, array( 'semrs_status', 'semrs_index', 'semrs_traffic' ), true ) ) {
		return;
	}
	$status = get_post_status( $id );
	$muted  = '<span style="color:#646970">%s</span>';

	if ( 'semrs_status' === $col ) {
		if ( 'publish' !== $status ) {
			$names = array(
				'future'  => __( 'Scheduled', 'semrs-core' ),
				'draft'   => __( 'Draft', 'semrs-core' ),
				'pending' => __( 'Pending review', 'semrs-core' ),
				'private' => __( 'Private', 'semrs-core' ),
			);
			$label = $names[ $status ] ?? ucfirst( (string) $status );
			if ( 'future' === $status ) {
				$label .= ' · ' . get_the_date( '', $id );
			}
			printf( $muted, esc_html( $label ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup, escaped label.
			return;
		}
		$check = semrs_index_column_data()['live'][ $id ] ?? null;
		if ( ! $check ) {
			echo semrs_index_dot( __( 'Published · not checked yet', 'semrs-core' ), '#646970' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
		} elseif ( 200 === (int) $check[0] ) {
			echo semrs_index_dot( __( 'Published · Live', 'semrs-core' ), '#00813a', __( 'The page answers with HTTP 200.', 'semrs-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		} elseif ( $check[0] >= 300 && $check[0] < 400 ) {
			/* translators: %s: address */
			echo semrs_index_dot( __( 'Published · Redirects', 'semrs-core' ), '#b26200', sprintf( __( 'Goes to %s', 'semrs-core' ), $check[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			/* translators: %d: HTTP status */
			echo semrs_index_dot( sprintf( __( 'Published · Not live (%d)', 'semrs-core' ), (int) $check[0] ), '#b32d2e' ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		return;
	}

	if ( 'publish' !== $status ) {
		printf( $muted, '—' ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup.
		return;
	}
	$d = semrs_index_column_data();

	if ( 'semrs_index' === $col ) {
		$st = semrs_index_status( $id, $d['live'], $d['google'] );
		echo semrs_index_dot( $st[1], $st[2], $st[3] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
		if ( $d['prop'] && in_array( $st[0], array( 'pending', 'waiting' ), true ) ) {
			$url = 'https://search.google.com/search-console/inspect?resource_id=' . rawurlencode( $d['prop'] ) . '&id=' . rawurlencode( get_permalink( $id ) );
			echo '<br><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Request indexing', 'semrs-core' ) . '</a>';
		}
		return;
	}

	// Traffic.
	if ( false === $d['google'] ) {
		printf( $muted, esc_html__( 'Connect Site Kit', 'semrs-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup, escaped text.
		return;
	}
	$path = trailingslashit( strtolower( (string) wp_parse_url( get_permalink( $id ), PHP_URL_PATH ) ) );
	if ( empty( $d['google'][ $path ] ) ) {
		printf( $muted, esc_html__( 'No Google traffic yet', 'semrs-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	list( $clicks, $impr, $pos ) = $d['google'][ $path ];
	printf(
		'<strong>%1$s</strong> %2$s<br><span style="color:#646970">%3$s · %4$s</span>',
		esc_html( number_format_i18n( $clicks ) ),
		esc_html( _n( 'click', 'clicks', $clicks, 'semrs-core' ) ),
		/* translators: %s: impressions */
		esc_html( sprintf( __( '%s impressions', 'semrs-core' ), number_format_i18n( $impr ) ) ),
		/* translators: %s: average position */
		esc_html( sprintf( __( 'position %s', 'semrs-core' ), $pos ) )
	);
}
