<?php
/**
 * SEMRS Scheduler: publishes scheduled posts on time, even on cached sites.
 *
 * WordPress publishes a scheduled post when its own scheduler (WP-Cron) runs, and WP-Cron only runs
 * on a page load that reaches WordPress. With page caching, most visits are answered from the cache,
 * so posts can go out late and show "Missed schedule". The SEMRS Scheduler:
 * - keeps the time of the next scheduled post in one autoloaded option;
 * - once that time has passed, the SEMRS page cache lets the next visit through to WordPress;
 * - every full page load (and every WP-Cron or server cron run) publishes posts that are due,
 *   at most once a minute, oldest first, ten at a time.
 * For exact timing on sites behind a server or CDN cache, add a server cron job that opens
 * wp-cron.php every minute (shown on SEMRS → Content Calendar).
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Store the GMT timestamp of the next scheduled post (0 when nothing is scheduled).
 */
function semrs_scheduler_refresh_next() {
	global $wpdb;
	$next = $wpdb->get_var( "SELECT post_date_gmt FROM {$wpdb->posts} WHERE post_status = 'future' AND post_date_gmt <> '0000-00-00 00:00:00' ORDER BY post_date_gmt ASC LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one indexed lookup, cached in an option.
	update_option( 'semrs_next_scheduled', $next ? (int) strtotime( $next . ' UTC' ) : 0, true );
}

/**
 * Publish scheduled posts whose time has passed.
 *
 * @param bool $force Skip the once-a-minute lock (the "Publish due posts now" button).
 * @return int Posts published.
 */
function semrs_scheduler_publish_due( $force = false ) {
	if ( ! $force && get_transient( 'semrs_scheduler_lock' ) ) {
		return 0;
	}
	set_transient( 'semrs_scheduler_lock', 1, MINUTE_IN_SECONDS );
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'future' AND post_date_gmt <> '0000-00-00 00:00:00' AND post_date_gmt <= %s ORDER BY post_date_gmt ASC LIMIT 10", gmdate( 'Y-m-d H:i:s' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- small indexed query, at most once a minute.
	foreach ( $ids as $id ) {
		wp_publish_post( (int) $id );
	}
	update_option( 'semrs_scheduler_last', array( 'time' => time(), 'count' => count( $ids ) ), false );
	semrs_scheduler_refresh_next();
	return count( $ids );
}

// Keep the next-due time current whenever a post enters or leaves "scheduled".
add_action(
	'transition_post_status',
	function ( $new, $old ) {
		if ( 'future' === $new || 'future' === $old ) {
			semrs_scheduler_refresh_next();
		}
	},
	20,
	2
);

/**
 * To-the-second publishing on cron runs: when the next post is due within the next 60 seconds, the
 * cron request waits until that second and publishes it. With a server cron every minute (for
 * example hPanel → Cron Jobs: wget … wp-cron.php every minute), posts go live on their exact time.
 */
add_action(
	'wp_loaded',
	function () {
		if ( ! wp_doing_cron() && ! isset( $_GET['doing_wp_cron'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- read-only flag WordPress itself uses.
			return;
		}
		$due  = (int) get_option( 'semrs_next_scheduled', 0 );
		$wait = $due - time();
		if ( $due && $wait > 0 && $wait <= 60 && ! get_transient( 'semrs_scheduler_waiting' ) ) {
			set_transient( 'semrs_scheduler_waiting', 1, 90 ); // One waiting run at a time.
			ignore_user_abort( true );
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- not allowed on every host; the wait is short anyway.
			}
			sleep( $wait );
			delete_transient( 'semrs_scheduler_lock' );
			semrs_scheduler_publish_due( true );
			delete_transient( 'semrs_scheduler_waiting' );
		}
	},
	5
);

// Every full load: publish what is due (cheap check against one autoloaded option).
add_action(
	'wp_loaded',
	function () {
		$due = get_option( 'semrs_next_scheduled', false );
		if ( false === $due ) {
			semrs_scheduler_refresh_next();
			$due = get_option( 'semrs_next_scheduled', 0 );
		}
		if ( $due && (int) $due <= time() ) {
			semrs_scheduler_publish_due();
		}
	}
);

/**
 * Hourly recheck, forever: re-publish any post that missed its time, then make sure the site's own
 * scheduled tasks (backups, index checks, sitemap resubmits, cleanups and other daily jobs) are not
 * stuck. Runs from WP-Cron every hour and, as a fallback when WP-Cron is not firing (cached sites),
 * from the first full page load after an hour has passed.
 *
 * @return array Result: time, posts published, overdue tasks found.
 */
function semrs_scheduler_hourly() {
	if ( get_transient( 'semrs_scheduler_hourly_lock' ) ) {
		return array();
	}
	set_transient( 'semrs_scheduler_hourly_lock', 1, 5 * MINUTE_IN_SECONDS );
	$published = semrs_scheduler_publish_due( true );
	// Site tasks that should have run more than 15 minutes ago.
	$overdue = 0;
	foreach ( (array) _get_cron_array() as $ts => $hooks ) {
		if ( (int) $ts > time() - 15 * MINUTE_IN_SECONDS ) {
			break; // The cron array is sorted by time.
		}
		$overdue += count( (array) $hooks );
	}
	if ( $overdue && ! wp_doing_cron() && function_exists( 'spawn_cron' ) ) {
		spawn_cron(); // Runs every due task in a background request.
	}
	$result = array( 'time' => time(), 'published' => $published, 'overdue' => $overdue );
	update_option( 'semrs_scheduler_hourly', $result, false );
	return $result;
}
add_action( 'semrs_scheduler_hourly', 'semrs_scheduler_hourly' );

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'semrs_scheduler_hourly' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', 'semrs_scheduler_hourly' );
		}
	}
);

// Fallback when WP-Cron is not firing: the first full load after an hour runs the recheck.
add_action(
	'wp_loaded',
	function () {
		$last = (array) get_option( 'semrs_scheduler_hourly', array() );
		if ( empty( $last['time'] ) || (int) $last['time'] < time() - HOUR_IN_SECONDS ) {
			semrs_scheduler_hourly();
		}
	},
	20
);

// "Publish due posts now" button on SEMRS → Content Calendar.
add_action(
	'admin_post_semrs_scheduler_run',
	function () {
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'semrs-core' ) );
		}
		check_admin_referer( 'semrs_scheduler_run' );
		$n = semrs_scheduler_publish_due( true );
		wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-calendar', 'semrs_published' => $n ), admin_url( 'admin.php' ) ) );
		exit;
	}
);

/**
 * Status box for the Content Calendar screen.
 */
function semrs_scheduler_status_box() {
	$next = (int) get_option( 'semrs_next_scheduled', 0 );
	$last = (array) get_option( 'semrs_scheduler_last', array() );
	$fmt  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$cron = 'wget -q -O - ' . site_url( 'wp-cron.php?doing_wp_cron' ) . ' >/dev/null 2>&1';
	?>
	<div class="notice notice-info inline" style="max-width:1200px;padding:12px 16px">
		<p><strong><?php esc_html_e( 'SEMRS Scheduler is on.', 'semrs-core' ); ?></strong>
		<?php esc_html_e( 'Scheduled posts are published on time even when pages are served from the cache: once a post is due, the next visit publishes it. Every hour it also rechecks for posts that missed their time and for overdue site tasks (backups, index checks and other daily jobs), and runs them.', 'semrs-core' ); ?></p>
		<p>
			<?php
			echo esc_html(
				$next
					/* translators: %s: date and time */
					? sprintf( __( 'Next post due: %s.', 'semrs-core' ), wp_date( $fmt, $next ) )
					: __( 'No posts are scheduled.', 'semrs-core' )
			);
			if ( ! empty( $last['time'] ) ) {
				/* translators: 1: date and time, 2: number of posts */
				echo ' ' . esc_html( sprintf( __( 'Last check: %1$s (%2$d published).', 'semrs-core' ), wp_date( $fmt, (int) $last['time'] ), (int) ( $last['count'] ?? 0 ) ) );
			}
			$hourly = (array) get_option( 'semrs_scheduler_hourly', array() );
			if ( ! empty( $hourly['time'] ) ) {
				/* translators: 1: date and time, 2: posts re-published, 3: overdue site tasks */
				echo ' ' . esc_html( sprintf( __( 'Hourly recheck: %1$s (%2$d missed posts published, %3$d overdue site tasks re-run).', 'semrs-core' ), wp_date( $fmt, (int) $hourly['time'] ), (int) ( $hourly['published'] ?? 0 ), (int) ( $hourly['overdue'] ?? 0 ) ) );
			}
			if ( isset( $_GET['semrs_published'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only.
				/* translators: %d: number of posts */
				echo ' <strong>' . esc_html( sprintf( _n( '%d post published just now.', '%d posts published just now.', absint( $_GET['semrs_published'] ), 'semrs-core' ), absint( $_GET['semrs_published'] ) ) ) . '</strong>'; // phpcs:ignore WordPress.Security.NonceVerification
			}
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0 0 8px">
			<?php wp_nonce_field( 'semrs_scheduler_run' ); ?>
			<input type="hidden" name="action" value="semrs_scheduler_run">
			<?php submit_button( __( 'Publish due posts now', 'semrs-core' ), 'secondary small', 'submit', false ); ?>
		</form>
		<details><summary><?php esc_html_e( 'Exact timing on sites with a server or CDN cache (recommended)', 'semrs-core' ); ?></summary>
			<p><?php esc_html_e( 'Some hosts answer cached pages without running WordPress at all. Add a cron job in your hosting panel (for example hPanel → Advanced → Cron Jobs) that runs every minute (* * * * *), so posts go live on their exact second:', 'semrs-core' ); ?></p>
			<p><code><?php echo esc_html( $cron ); ?></code></p>
		</details>
	</div>
	<?php
}
