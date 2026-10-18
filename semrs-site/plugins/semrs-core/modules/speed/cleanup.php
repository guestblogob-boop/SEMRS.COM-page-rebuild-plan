<?php
/**
 * Database cleanup (SEMRS → Speed → Database): old revisions, old
 * auto-drafts, trashed posts, spam and trashed comments, expired
 * transients and orphaned post meta. Each item shows a count and is cleaned
 * only when you click its button, in batches, using WordPress functions.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cleanup items: key => [label, help].
 *
 * @return array
 */
function semrs_speed_db_items() {
	return array(
		'revisions'  => array( __( 'Revisions older than 30 days', 'semrs-core' ), __( 'Earlier versions of posts. The last 30 days are kept so you can still undo recent changes.', 'semrs-core' ) ),
		'autodrafts' => array( __( 'Auto-drafts older than 7 days', 'semrs-core' ), __( 'Empty drafts WordPress creates when you open "Add New".', 'semrs-core' ) ),
		'trash'      => array( __( 'Posts and pages in the trash', 'semrs-core' ), __( 'Permanently deletes them. Check the trash first if you are unsure.', 'semrs-core' ) ),
		'spam'       => array( __( 'Spam comments', 'semrs-core' ), '' ),
		'ctrash'     => array( __( 'Comments in the trash', 'semrs-core' ), '' ),
		'transients' => array( __( 'Expired transients', 'semrs-core' ), __( 'Temporary cached data that has expired. WordPress rebuilds anything still needed.', 'semrs-core' ) ),
		'orphans'    => array( __( 'Orphaned post meta', 'semrs-core' ), __( 'Settings left behind by posts that no longer exist.', 'semrs-core' ) ),
	);
}

/**
 * IDs (or a count) for an item.
 *
 * @param string $key   Item.
 * @param bool   $count Return a count instead of IDs.
 * @return int|int[]
 */
function semrs_speed_db_query( $key, $count = false ) {
	global $wpdb;
	$month = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	$week  = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
	$like  = $wpdb->esc_like( '_transient_timeout_' ) . '%';
	$state = 'spam' === $key ? 'spam' : 'trash';
	// Fully prepared queries; %i is a table identifier (WordPress 6.2+). Admin-only, on demand.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	switch ( $key ) {
		case 'revisions':
			return $count
				? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE post_type = 'revision' AND post_modified_gmt < %s", $wpdb->posts, $month ) )
				: $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM %i WHERE post_type = 'revision' AND post_modified_gmt < %s LIMIT 500", $wpdb->posts, $month ) );
		case 'autodrafts':
			return $count
				? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE post_status = 'auto-draft' AND post_modified_gmt < %s", $wpdb->posts, $week ) )
				: $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM %i WHERE post_status = 'auto-draft' AND post_modified_gmt < %s LIMIT 500", $wpdb->posts, $week ) );
		case 'trash':
			return $count
				? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE post_status = 'trash'", $wpdb->posts ) )
				: $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM %i WHERE post_status = 'trash' LIMIT 500", $wpdb->posts ) );
		case 'spam':
		case 'ctrash':
			return $count
				? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE comment_approved = %s', $wpdb->comments, $state ) )
				: $wpdb->get_col( $wpdb->prepare( 'SELECT comment_ID FROM %i WHERE comment_approved = %s LIMIT 500', $wpdb->comments, $state ) );
		case 'transients':
			return $count
				? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE option_name LIKE %s AND option_value < %d', $wpdb->options, $like, time() ) )
				: $wpdb->get_col( $wpdb->prepare( 'SELECT option_name FROM %i WHERE option_name LIKE %s AND option_value < %d LIMIT 500', $wpdb->options, $like, time() ) );
		case 'orphans':
			return $count
				? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE post_id NOT IN (SELECT ID FROM %i)', $wpdb->postmeta, $wpdb->posts ) )
				: $wpdb->get_col( $wpdb->prepare( 'SELECT meta_id FROM %i WHERE post_id NOT IN (SELECT ID FROM %i) LIMIT 500', $wpdb->postmeta, $wpdb->posts ) );
	}
	// phpcs:enable
	return $count ? 0 : array();
}

/**
 * Clean one batch of an item.
 *
 * @param string $key Item.
 * @return int Rows removed.
 */
function semrs_speed_db_clean( $key ) {
	global $wpdb;
	if ( 'transients' === $key ) {
		$before = semrs_speed_db_query( 'transients', true );
		delete_expired_transients( true );
		return $before;
	}
	$ids = semrs_speed_db_query( $key );
	foreach ( $ids as $id ) {
		switch ( $key ) {
			case 'revisions':
				wp_delete_post_revision( (int) $id );
				break;
			case 'autodrafts':
			case 'trash':
				wp_delete_post( (int) $id, true );
				break;
			case 'spam':
			case 'ctrash':
				wp_delete_comment( (int) $id, true );
				break;
			case 'orphans':
				$wpdb->delete( $wpdb->postmeta, array( 'meta_id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- orphaned rows have no API.
				break;
		}
	}
	return count( $ids );
}

add_action(
	'admin_post_semrs_db_clean',
	function () {
		check_admin_referer( 'semrs_db_clean' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'semrs-core' ) );
		}
		$key = isset( $_POST['item'] ) ? sanitize_key( wp_unslash( $_POST['item'] ) ) : '';
		$n   = array_key_exists( $key, semrs_speed_db_items() ) ? semrs_speed_db_clean( $key ) : 0;
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-speed&tab=database&cleaned=' . $n ) );
		exit;
	}
);

/**
 * Database tab.
 */
function semrs_speed_db_tab() {
	if ( isset( $_GET['cleaned'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only.
		/* translators: %d: rows */
		echo '<div class="notice notice-success inline"><p>' . esc_html( sprintf( __( 'Removed %d items.', 'semrs-core' ), absint( $_GET['cleaned'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
	}
	?>
	<p><?php esc_html_e( 'A smaller database makes the admin and uncached pages faster. Make a backup first if your host does not do it for you. Each click removes up to 500 items; click again for more.', 'semrs-core' ); ?></p>
	<table class="widefat striped" style="max-width:860px">
		<thead><tr><th><?php esc_html_e( 'Item', 'semrs-core' ); ?></th><th style="width:90px"><?php esc_html_e( 'Count', 'semrs-core' ); ?></th><th style="width:120px"></th></tr></thead>
		<tbody>
		<?php foreach ( semrs_speed_db_items() as $key => $item ) : ?>
			<?php $n = semrs_speed_db_query( $key, true ); ?>
			<tr>
				<td><strong><?php echo esc_html( $item[0] ); ?></strong><?php echo $item[1] ? '<br><span class="description">' . esc_html( $item[1] ) . '</span>' : ''; ?></td>
				<td><?php echo esc_html( number_format_i18n( $n ) ); ?></td>
				<td>
					<?php if ( $n ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Permanently remove these items?', 'semrs-core' ) ) ); ?>)">
							<?php wp_nonce_field( 'semrs_db_clean' ); ?>
							<input type="hidden" name="action" value="semrs_db_clean"><input type="hidden" name="item" value="<?php echo esc_attr( $key ); ?>">
							<button class="button"><?php esc_html_e( 'Clean', 'semrs-core' ); ?></button>
						</form>
					<?php else : ?>
						<span class="description"><?php esc_html_e( 'Nothing to clean', 'semrs-core' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
