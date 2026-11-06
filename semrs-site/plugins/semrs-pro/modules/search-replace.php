<?php
/**
 * Search & replace (Pro): find and replace text or links in post content,
 * titles and excerpts, custom fields, comments and settings. Serialized
 * data is unpacked and packed again, so it stays valid. A dry run shows
 * what would change; every real run keeps the old values so it can be
 * undone with one click. Site address, active plugins, roles and cron are
 * never touched. Tools → Search & replace.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Undo table.
 *
 * @return string
 */
function semrs_sr_table() {
	global $wpdb;
	return $wpdb->prefix . 'semrs_sr_undo';
}

/**
 * Create the undo table once.
 */
function semrs_sr_install() {
	if ( '1' === get_option( 'semrs_sr_db' ) ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta(
		'CREATE TABLE ' . semrs_sr_table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			run varchar(20) NOT NULL DEFAULT '',
			tbl varchar(64) NOT NULL DEFAULT '',
			pk varchar(64) NOT NULL DEFAULT '',
			pk_value bigint(20) unsigned NOT NULL DEFAULT 0,
			col varchar(64) NOT NULL DEFAULT '',
			old_value longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY run (run)
		) " . $wpdb->get_charset_collate() . ';'
	);
	update_option( 'semrs_sr_db', '1', false );
}
add_action( 'admin_init', 'semrs_sr_install' );

/**
 * Where to search: key => [label, table, primary key, columns, extra WHERE].
 *
 * @return array
 */
function semrs_sr_scopes() {
	global $wpdb;
	$skip_opts = "option_name NOT IN ('siteurl','home','active_plugins','cron','rewrite_rules','" . esc_sql( $wpdb->prefix ) . "user_roles','semrs_sr_db','auth_key','secure_auth_key','logged_in_key','nonce_key','auth_salt','secure_auth_salt','logged_in_salt','nonce_salt') AND option_name NOT LIKE '\\_transient%' AND option_name NOT LIKE '\\_site\\_transient%'";
	return array(
		'posts'    => array( __( 'Post and page content, titles and excerpts', 'semrs-pro' ), $wpdb->posts, 'ID', array( 'post_content', 'post_title', 'post_excerpt' ), "post_type NOT IN ('revision')" ),
		'postmeta' => array( __( 'Custom fields (SEO titles, page builder data, block settings…)', 'semrs-pro' ), $wpdb->postmeta, 'meta_id', array( 'meta_value' ), "meta_key NOT IN ('_edit_lock','_edit_last')" ),
		'comments' => array( __( 'Comments', 'semrs-pro' ), $wpdb->comments, 'comment_ID', array( 'comment_content', 'comment_author_url' ), '1=1' ),
		'options'  => array( __( 'Settings (widgets, menus, theme and plugin options)', 'semrs-pro' ), $wpdb->options, 'option_id', array( 'option_value' ), $skip_opts ),
	);
}

/**
 * Replace inside a value, unpacking serialized data safely.
 *
 * @param mixed  $data    Value.
 * @param string $from    Search.
 * @param string $to      Replace.
 * @param bool   $case    Case-sensitive.
 * @param int    $count   Replacements made (by reference).
 * @return mixed
 */
function semrs_sr_replace( $data, $from, $to, $case, &$count ) {
	if ( is_string( $data ) && is_serialized( $data ) ) {
		$un = @unserialize( $data, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.PHP.DiscouragedPHPFunctions
		if ( false !== $un || 'b:0;' === $data ) {
			$new = semrs_sr_replace( $un, $from, $to, $case, $count );
			return serialize( $new ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		}
		return $data; // Holds objects we cannot rebuild: leave it alone.
	}
	if ( is_array( $data ) ) {
		$out = array();
		foreach ( $data as $k => $v ) {
			$out[ $k ] = semrs_sr_replace( $v, $from, $to, $case, $count );
		}
		return $out;
	}
	if ( is_string( $data ) ) {
		$n    = 0;
		$data = $case ? str_replace( $from, $to, $data, $n ) : str_ireplace( $from, $to, $data, $n );
		$count += $n;
	}
	return $data;
}

/**
 * Run a search (and replace unless dry).
 *
 * @param string $from   Search.
 * @param string $to     Replace.
 * @param array  $scopes Scope keys.
 * @param bool   $case   Case-sensitive.
 * @param bool   $dry    Dry run.
 * @return array Report.
 */
function semrs_sr_run( $from, $to, $scopes, $case, $dry ) {
	global $wpdb;
	@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$limit  = 3000;
	$run    = gmdate( 'YmdHis' );
	$report = array( 'run' => $run, 'rows' => 0, 'hits' => 0, 'more' => false, 'samples' => array(), 'by' => array() );
	if ( ! $dry ) {
		// Only the latest change can be undone: drop older undo data.
		$wpdb->query( 'DELETE FROM ' . semrs_sr_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	foreach ( semrs_sr_scopes() as $key => $sc ) {
		if ( ! in_array( $key, $scopes, true ) ) {
			continue;
		}
		list( , $table, $pk, $cols, $extra ) = $sc;
		foreach ( $cols as $col ) {
			$like = '%' . $wpdb->esc_like( $from ) . '%';
			$cmp  = $case ? "BINARY `{$col}` LIKE BINARY %s" : "`{$col}` LIKE %s";
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT `{$pk}` AS id, `{$col}` AS val FROM `{$table}` WHERE {$cmp} AND {$extra} LIMIT %d", $like, $limit + 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( count( $rows ) > $limit ) {
				$report['more'] = true;
				$rows           = array_slice( $rows, 0, $limit );
			}
			foreach ( $rows as $r ) {
				$n   = 0;
				$new = semrs_sr_replace( $r->val, $from, $to, $case, $n );
				if ( ! $n || $new === $r->val ) {
					continue;
				}
				$report['rows']++;
				$report['hits'] += $n;
				$report['by'][ $key ] = ( $report['by'][ $key ] ?? 0 ) + $n;
				if ( count( $report['samples'] ) < 15 ) {
					$pos                 = $case ? strpos( (string) $r->val, $from ) : stripos( (string) $r->val, $from );
					$report['samples'][] = array( $key, $col, (int) $r->id, substr( (string) $r->val, max( 0, (int) $pos - 40 ), 120 ) );
				}
				if ( ! $dry ) {
					$wpdb->insert( semrs_sr_table(), array( 'run' => $run, 'tbl' => $table, 'pk' => $pk, 'pk_value' => (int) $r->id, 'col' => $col, 'old_value' => (string) $r->val ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->update( $table, array( $col => $new ), array( $pk => (int) $r->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}
			}
		}
	}
	if ( ! $dry && $report['rows'] ) {
		update_option( 'semrs_sr_last', array( 'run' => $run, 'from' => $from, 'to' => $to, 'rows' => $report['rows'], 'time' => time() ), false );
		wp_cache_flush();
		if ( function_exists( 'semrs_cache_purge_all' ) ) {
			semrs_cache_purge_all();
		}
		if ( function_exists( 'semrs_act_add' ) ) {
			semrs_act_add( 'search_replace', 'tool', 0, $from . ' → ' . $to . ' (' . $report['rows'] . ')' );
		}
	}
	return $report;
}

/**
 * Undo the last real run.
 *
 * @return int Rows restored.
 */
function semrs_sr_undo() {
	global $wpdb;
	$last = get_option( 'semrs_sr_last' );
	if ( ! is_array( $last ) ) {
		return 0;
	}
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . semrs_sr_table() . ' WHERE run = %s ORDER BY id DESC', $last['run'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$allowed = wp_list_pluck( semrs_sr_scopes(), 1 );
	$n       = 0;
	foreach ( $rows as $r ) {
		if ( ! in_array( $r->tbl, $allowed, true ) || ! preg_match( '/^[A-Za-z_]+$/', $r->pk . $r->col ) ) {
			continue;
		}
		$wpdb->update( $r->tbl, array( $r->col => $r->old_value ), array( $r->pk => (int) $r->pk_value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$n++;
	}
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . semrs_sr_table() . ' WHERE run = %s', $last['run'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	delete_option( 'semrs_sr_last' );
	wp_cache_flush();
	if ( function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
	return $n;
}

if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_management_page( __( 'Search & replace', 'semrs-pro' ), __( 'Search & replace', 'semrs-pro' ), 'manage_options', 'semrs-search-replace', 'semrs_sr_page' );
		}
	);
}

/**
 * Tool screen.
 */
function semrs_sr_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = null;
	$undone = null;
	$from   = '';
	$to     = '';
	$scopes = array( 'posts', 'postmeta' );
	$case   = true;
	$dry    = true;
	if ( isset( $_POST['semrs_sr'] ) && check_admin_referer( 'semrs_sr' ) && '1' === get_option( 'semrs_sr_db' ) ) {
		if ( isset( $_POST['undo'] ) ) {
			$undone = semrs_sr_undo();
		} else {
			$from   = (string) wp_unslash( $_POST['from'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- raw text is the point of the tool; only used in prepared queries.
			$to     = (string) wp_unslash( $_POST['to'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$scopes = array_intersect( array_keys( semrs_sr_scopes() ), array_map( 'sanitize_key', (array) ( $_POST['scopes'] ?? array() ) ) );
			$case   = ! empty( $_POST['case'] );
			$dry    = ! empty( $_POST['dry'] );
			if ( strlen( $from ) >= 3 && $scopes ) {
				$report = semrs_sr_run( $from, $to, $scopes, $case, $dry );
			}
		}
	}
	$last = get_option( 'semrs_sr_last' );
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Search & replace', 'semrs-pro' ); ?></h1>
		<p><?php esc_html_e( 'Change a word, a phone number or an old link everywhere at once, for example after moving from http to https or to a new domain. Run a dry run first, and make a backup before big changes.', 'semrs-pro' ); ?></p>
		<?php if ( null !== $undone ) : ?>
			<?php /* translators: %d: rows. */ ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Undone: %d values restored.', 'semrs-pro' ), $undone ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( $report ) : ?>
			<div class="notice notice-<?php echo $dry ? 'info' : 'success'; ?>"><p><strong>
			<?php
			if ( $dry ) {
				/* translators: 1: matches, 2: rows. */
				echo esc_html( sprintf( __( 'Dry run: %1$d matches in %2$d places would change. Nothing was saved.', 'semrs-pro' ), $report['hits'], $report['rows'] ) );
			} else {
				/* translators: 1: matches, 2: rows. */
				echo esc_html( sprintf( __( 'Done: %1$d replacements in %2$d places. You can undo this below.', 'semrs-pro' ), $report['hits'], $report['rows'] ) );
			}
			?>
			</strong>
			<?php if ( $report['more'] ) : ?><br><?php esc_html_e( 'There are more matches than one run handles (3,000 per column). Run it again to continue.', 'semrs-pro' ); ?><?php endif; ?></p></div>
			<?php if ( $report['samples'] ) : ?>
				<table class="widefat striped" style="margin-bottom:16px"><thead><tr><th><?php esc_html_e( 'Where', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'ID', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Text around the match', 'semrs-pro' ); ?></th></tr></thead><tbody>
				<?php foreach ( $report['samples'] as $s ) : ?>
					<tr><td><?php echo esc_html( $s[0] . ' / ' . $s[1] ); ?></td><td><?php echo 'posts' === $s[0] ? '<a href="' . esc_url( get_edit_post_link( $s[2] ) ) . '">' . esc_html( $s[2] ) . '</a>' : esc_html( $s[2] ); ?></td><td><code><?php echo esc_html( $s[3] ); ?></code></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			<?php endif; ?>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'semrs_sr' ); ?><input type="hidden" name="semrs_sr" value="1">
			<table class="form-table" role="presentation">
				<tr><th><label for="semrs-sr-from"><?php esc_html_e( 'Find', 'semrs-pro' ); ?></label></th><td><input id="semrs-sr-from" type="text" class="large-text" name="from" value="<?php echo esc_attr( $from ); ?>" required minlength="3"></td></tr>
				<tr><th><label for="semrs-sr-to"><?php esc_html_e( 'Replace with', 'semrs-pro' ); ?></label></th><td><input id="semrs-sr-to" type="text" class="large-text" name="to" value="<?php echo esc_attr( $to ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Look in', 'semrs-pro' ); ?></th><td>
					<?php foreach ( semrs_sr_scopes() as $k => $sc ) : ?>
						<label style="display:block"><input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $scopes, true ) ); ?>> <?php echo esc_html( $sc[0] ); ?></label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Never changed: site address, active plugins, user roles, scheduled tasks, security keys and cached data.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th><?php esc_html_e( 'Options', 'semrs-pro' ); ?></th><td>
					<label><input type="checkbox" name="case" value="1" <?php checked( $case ); ?>> <?php esc_html_e( 'Match upper and lower case exactly', 'semrs-pro' ); ?></label><br>
					<label><input type="checkbox" name="dry" value="1" <?php checked( $dry ); ?>> <strong><?php esc_html_e( 'Dry run: only count, do not change anything', 'semrs-pro' ); ?></strong></label>
				</td></tr>
			</table>
			<?php submit_button( __( 'Run', 'semrs-pro' ) ); ?>
		</form>
		<?php if ( is_array( $last ) ) : ?>
			<h2><?php esc_html_e( 'Undo', 'semrs-pro' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'semrs_sr' ); ?><input type="hidden" name="semrs_sr" value="1"><input type="hidden" name="undo" value="1">
				<?php /* translators: 1: search, 2: replace, 3: rows, 4: date. */ ?>
				<p><?php echo esc_html( sprintf( __( 'Last change: "%1$s" → "%2$s" in %3$d places on %4$s.', 'semrs-pro' ), $last['from'], $last['to'], $last['rows'], wp_date( 'Y-m-d H:i', $last['time'] ) ) ); ?></p>
				<?php submit_button( __( 'Undo last change', 'semrs-pro' ), 'secondary' ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
