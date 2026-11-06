<?php
/**
 * Activity log (Pro): who published, changed or deleted content, changed
 * settings, plugins, themes or users, and who logged in or failed to.
 * Filter by person or event, export to CSV, auto-delete after N days.
 * IP addresses are stored shortened (last part removed).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Table name.
 *
 * @return string
 */
function semrs_act_table() {
	global $wpdb;
	return $wpdb->prefix . 'semrs_activity';
}

/**
 * Create the table once.
 */
function semrs_act_install() {
	if ( '1' === get_option( 'semrs_activity_db' ) ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta(
		'CREATE TABLE ' . semrs_act_table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created datetime NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_name varchar(100) NOT NULL DEFAULT '',
			event varchar(40) NOT NULL DEFAULT '',
			object_type varchar(40) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			title varchar(191) NOT NULL DEFAULT '',
			ip varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY created (created),
			KEY user_id (user_id),
			KEY event (event)
		) " . $wpdb->get_charset_collate() . ';'
	);
	update_option( 'semrs_activity_db', '1', false );
}
add_action( 'admin_init', 'semrs_act_install' );

/**
 * Shortened IP (IPv4 last octet / IPv6 last 80 bits removed).
 *
 * @return string
 */
function semrs_act_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return function_exists( 'wp_privacy_anonymize_ip' ) ? wp_privacy_anonymize_ip( $ip ) : '';
}

/**
 * Write one entry.
 *
 * @param string $event       Event key.
 * @param string $object_type Type.
 * @param int    $object_id   ID.
 * @param string $title       Title or detail.
 * @param int    $user_id     User (current user when 0).
 * @param string $user_name   Name (for failed logins).
 */
function semrs_act_add( $event, $object_type = '', $object_id = 0, $title = '', $user_id = 0, $user_name = '' ) {
	if ( '1' !== get_option( 'semrs_activity_db' ) ) {
		return;
	}
	$user_id = $user_id ? $user_id : get_current_user_id();
	if ( '' === $user_name && $user_id ) {
		$u         = get_userdata( $user_id );
		$user_name = $u ? $u->user_login : '';
	}
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		semrs_act_table(),
		array(
			'created'     => current_time( 'mysql', true ),
			'user_id'     => (int) $user_id,
			'user_name'   => substr( sanitize_user( $user_name ), 0, 100 ),
			'event'       => substr( sanitize_key( $event ), 0, 40 ),
			'object_type' => substr( sanitize_key( $object_type ), 0, 40 ),
			'object_id'   => (int) $object_id,
			'title'       => substr( wp_strip_all_tags( (string) $title ), 0, 191 ),
			'ip'          => semrs_act_ip(),
		)
	);
}

/*
 * Events.
 */

add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) || in_array( $post->post_type, array( 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'wp_global_styles', 'revision' ), true ) || 'auto-draft' === $new ) {
			return;
		}
		if ( 'publish' === $new && 'publish' !== $old ) {
			$e = 'published';
		} elseif ( 'trash' === $new ) {
			$e = 'trashed';
		} elseif ( 'trash' === $old ) {
			$e = 'restored';
		} elseif ( $new === $old ) {
			$e = 'updated';
		} elseif ( 'publish' === $old ) {
			$e = 'unpublished';
		} else {
			$e = 'status_' . $new;
		}
		// One "updated" entry per post per minute (editors save often).
		if ( 'updated' === $e ) {
			$k = 'semrs_act_' . $post->ID . '_' . get_current_user_id();
			if ( get_transient( $k ) ) {
				return;
			}
			set_transient( $k, 1, MINUTE_IN_SECONDS );
		}
		semrs_act_add( $e, $post->post_type, $post->ID, $post->post_title );
	},
	10,
	3
);
add_action( 'before_delete_post', function ( $id ) { $p = get_post( $id ); if ( $p && 'revision' !== $p->post_type && 'nav_menu_item' !== $p->post_type ) { semrs_act_add( 'deleted', $p->post_type, $id, $p->post_title ); } } );
add_action( 'delete_attachment', function ( $id ) { semrs_act_add( 'deleted', 'attachment', $id, get_the_title( $id ) ); } );
add_action( 'add_attachment', function ( $id ) { semrs_act_add( 'uploaded', 'attachment', $id, get_the_title( $id ) ); } );
add_action( 'wp_login', function ( $login, $user ) { semrs_act_add( 'login', 'user', $user->ID, $login, $user->ID ); }, 10, 2 );
add_action( 'wp_login_failed', function ( $login ) { semrs_act_add( 'login_failed', 'user', 0, $login, 0, $login ); } );
add_action( 'user_register', function ( $id ) { $u = get_userdata( $id ); semrs_act_add( 'user_created', 'user', $id, $u ? $u->user_login . ' (' . implode( ', ', $u->roles ) . ')' : '' ); } );
add_action( 'delete_user', function ( $id ) { $u = get_userdata( $id ); semrs_act_add( 'user_deleted', 'user', $id, $u ? $u->user_login : '' ); } );
add_action( 'set_user_role', function ( $id, $role, $old ) { $u = get_userdata( $id ); semrs_act_add( 'role_changed', 'user', $id, ( $u ? $u->user_login : '' ) . ': ' . implode( ', ', (array) $old ) . ' → ' . $role ); }, 10, 3 );
add_action( 'profile_update', function ( $id ) { $u = get_userdata( $id ); semrs_act_add( 'profile_updated', 'user', $id, $u ? $u->user_login : '' ); } );
add_action( 'activated_plugin', function ( $p ) { semrs_act_add( 'plugin_activated', 'plugin', 0, $p ); } );
add_action( 'deactivated_plugin', function ( $p ) { semrs_act_add( 'plugin_deactivated', 'plugin', 0, $p ); } );
add_action( 'deleted_plugin', function ( $p ) { semrs_act_add( 'plugin_deleted', 'plugin', 0, $p ); } );
add_action( 'switch_theme', function ( $name ) { semrs_act_add( 'theme_switched', 'theme', 0, $name ); } );
add_action(
	'upgrader_process_complete',
	function ( $up, $data ) {
		$type  = $data['type'] ?? '';
		$items = array_merge( (array) ( $data['plugins'] ?? array() ), (array) ( $data['themes'] ?? array() ) );
		if ( 'core' === $type ) {
			$items = array( 'WordPress ' . get_bloginfo( 'version' ) );
		}
		if ( ! $items && $up instanceof Plugin_Upgrader && ! empty( $up->result['destination_name'] ) ) {
			$items = array( $up->result['destination_name'] );
		}
		$verbs = array( 'install' => 'installed', 'update' => 'updated' );
		$act   = (string) ( $data['action'] ?? 'update' );
		semrs_act_add( $verbs[ $act ] ?? $act, $type, 0, implode( ', ', $items ) );
	},
	10,
	2
);
add_action(
	'updated_option',
	function ( $option ) {
		$watch = array( 'blogname', 'blogdescription', 'siteurl', 'home', 'admin_email', 'users_can_register', 'default_role', 'permalink_structure', 'show_on_front', 'page_on_front', 'page_for_posts', 'blog_public', 'WPLANG', 'timezone_string' );
		if ( ! in_array( $option, $watch, true ) && 0 !== strpos( $option, 'semrs_' ) ) {
			return;
		}
		if ( preg_match( '/(_log|_lockouts|_cache|_db|_queue|_stats|_last|_count|_transient|_score|_rank|_index)/', $option ) || ! is_admin() ) {
			return;
		}
		$k = 'semrs_act_opt_' . md5( $option );
		if ( get_transient( $k ) ) {
			return;
		}
		set_transient( $k, 1, MINUTE_IN_SECONDS );
		semrs_act_add( 'setting_changed', 'option', 0, $option );
	}
);

/*
 * Clean-up.
 */
add_action(
	'semrs_activity_daily',
	function () {
		if ( '1' !== get_option( 'semrs_activity_db' ) ) {
			return;
		}
		global $wpdb;
		$days = max( 7, (int) get_option( 'semrs_activity_days', 90 ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . semrs_act_table() . ' WHERE created < %s', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
);
if ( ! wp_next_scheduled( 'semrs_activity_daily' ) ) {
	wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'semrs_activity_daily' );
}

/*
 * Screen.
 */
if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs', __( 'Activity log', 'semrs-pro' ), __( 'Activity log', 'semrs-pro' ), 'manage_options', 'semrs-activity', 'semrs_act_page' );
		},
		35
	);
	add_action( 'admin_post_semrs_activity_csv', 'semrs_act_csv' );
	add_action(
		'admin_post_semrs_activity_days',
		function () {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_activity_days' ) ) {
				wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
			}
			update_option( 'semrs_activity_days', max( 7, min( 3650, absint( $_POST['days'] ?? 90 ) ) ), false );
			wp_safe_redirect( admin_url( 'admin.php?page=semrs-activity&saved=1' ) );
			exit;
		}
	);
}

/**
 * WHERE clause from the filters.
 *
 * @return array{0:string,1:array}
 */
function semrs_act_where() {
	$where = array( '1=1' );
	$args  = array();
	// phpcs:disable WordPress.Security.NonceVerification -- read-only filters.
	if ( ! empty( $_GET['user'] ) ) {
		$where[] = 'user_name = %s';
		$args[]  = sanitize_user( wp_unslash( $_GET['user'] ) );
	}
	if ( ! empty( $_GET['event'] ) ) {
		$where[] = 'event = %s';
		$args[]  = sanitize_key( wp_unslash( $_GET['event'] ) );
	}
	if ( ! empty( $_GET['s'] ) ) {
		global $wpdb;
		$where[] = 'title LIKE %s';
		$args[]  = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) . '%';
	}
	// phpcs:enable
	return array( implode( ' AND ', $where ), $args );
}

/**
 * CSV export.
 */
function semrs_act_csv() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'semrs_activity_csv' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-pro' ) );
	}
	global $wpdb;
	list( $w, $a ) = semrs_act_where();
	$sql           = 'SELECT created, user_name, event, object_type, object_id, title, ip FROM ' . semrs_act_table() . " WHERE {$w} ORDER BY id DESC LIMIT 50000";
	$rows          = $wpdb->get_results( $a ? $wpdb->prepare( $sql, $a ) : $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=activity-log-' . gmdate( 'Y-m-d' ) . '.csv' );
	$f = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $f, array( 'time_utc', 'user', 'event', 'type', 'id', 'detail', 'ip' ) );
	foreach ( (array) $rows as $r ) {
		fputcsv( $f, $r );
	}
	exit;
}

/**
 * Log screen.
 */
function semrs_act_page() {
	global $wpdb;
	$t = semrs_act_table();
	if ( '1' !== get_option( 'semrs_activity_db' ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Activity log', 'semrs-pro' ) . '</h1><p>' . esc_html__( 'Preparing the log table… reload this page.', 'semrs-pro' ) . '</p></div>';
		return;
	}
	list( $w, $a ) = semrs_act_where();
	$page          = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$per           = 50;
	$sql           = "SELECT * FROM {$t} WHERE {$w} ORDER BY id DESC LIMIT " . $per . ' OFFSET ' . ( ( $page - 1 ) * $per );
	$rows          = $wpdb->get_results( $a ? $wpdb->prepare( $sql, $a ) : $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	$csql          = "SELECT COUNT(*) FROM {$t} WHERE {$w}";
	$total         = (int) $wpdb->get_var( $a ? $wpdb->prepare( $csql, $a ) : $csql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
	$events        = $wpdb->get_col( "SELECT DISTINCT event FROM {$t} ORDER BY event" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$users         = $wpdb->get_col( "SELECT DISTINCT user_name FROM {$t} WHERE user_name <> '' ORDER BY user_name LIMIT 200" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// phpcs:disable WordPress.Security.NonceVerification
	$cur_e = sanitize_key( wp_unslash( $_GET['event'] ?? '' ) );
	$cur_u = sanitize_user( wp_unslash( $_GET['user'] ?? '' ) );
	$cur_s = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
	// phpcs:enable
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Activity log', 'semrs-pro' ); ?></h1>
		<form method="get" style="margin:12px 0">
			<input type="hidden" name="page" value="semrs-activity">
			<select name="user"><option value=""><?php esc_html_e( 'Everyone', 'semrs-pro' ); ?></option><?php foreach ( $users as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>" <?php selected( $cur_u, $u ); ?>><?php echo esc_html( $u ); ?></option><?php endforeach; ?></select>
			<select name="event"><option value=""><?php esc_html_e( 'All events', 'semrs-pro' ); ?></option><?php foreach ( $events as $e ) : ?><option value="<?php echo esc_attr( $e ); ?>" <?php selected( $cur_e, $e ); ?>><?php echo esc_html( str_replace( '_', ' ', $e ) ); ?></option><?php endforeach; ?></select>
			<input type="search" name="s" value="<?php echo esc_attr( $cur_s ); ?>" placeholder="<?php esc_attr_e( 'Search details', 'semrs-pro' ); ?>">
			<?php submit_button( __( 'Filter', 'semrs-pro' ), 'secondary', '', false ); ?>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'semrs_activity_csv', 'user' => $cur_u, 'event' => $cur_e, 's' => $cur_s ), admin_url( 'admin-post.php' ) ), 'semrs_activity_csv' ) ); ?>"><?php esc_html_e( 'Export CSV', 'semrs-pro' ); ?></a>
		</form>
		<?php /* translators: %d: number of entries. */ ?>
		<p><?php echo esc_html( sprintf( _n( '%d entry', '%d entries', $total, 'semrs-pro' ), $total ) ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'When', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Who', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'What', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Detail', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'IP (shortened)', 'semrs-pro' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'Nothing logged yet.', 'semrs-pro' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $r ) : ?>
				<?php
				$link = '';
				if ( $r->object_id && post_type_exists( $r->object_type ) && get_post( (int) $r->object_id ) ) {
					$link = get_edit_post_link( (int) $r->object_id );
				}
				?>
				<tr>
					<td><?php echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $r->created . ' UTC' ) ) ); ?></td>
					<td><?php echo esc_html( $r->user_name ? $r->user_name : '—' ); ?></td>
					<td><?php echo esc_html( str_replace( '_', ' ', $r->event ) ); ?><?php echo $r->object_type ? ' <span class="description">(' . esc_html( $r->object_type ) . ')</span>' : ''; ?></td>
					<td><?php echo $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $r->title ) . '</a>' : esc_html( $r->title ); ?></td>
					<td><code><?php echo esc_html( $r->ip ); ?></code></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<?php if ( $page > 1 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>">&larr; <?php esc_html_e( 'Newer', 'semrs-pro' ); ?></a><?php endif; ?>
			<?php if ( $page * $per < $total ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>"><?php esc_html_e( 'Older', 'semrs-pro' ); ?> &rarr;</a><?php endif; ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="semrs_activity_days"><?php wp_nonce_field( 'semrs_activity_days' ); ?>
			<label><?php esc_html_e( 'Delete entries older than', 'semrs-pro' ); ?> <input type="number" min="7" max="3650" name="days" value="<?php echo esc_attr( (int) get_option( 'semrs_activity_days', 90 ) ); ?>" style="width:90px"> <?php esc_html_e( 'days', 'semrs-pro' ); ?></label> <?php submit_button( __( 'Save', 'semrs-pro' ), 'secondary', '', false ); ?>
		</form>
	</div>
	<?php
}
