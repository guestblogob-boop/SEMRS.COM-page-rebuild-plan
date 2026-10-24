<?php
/**
 * Redirect manager (301/302/307/410), 404 log, Rank Math redirect import.
 *
 * Works even while another SEO plugin is active, but redirects only run when
 * SEMRS SEO output is active (so two plugins never redirect the same URL).
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_seo_active' ) ) {
	return; // Needs the SEO module.
}

/**
 * Normalise a path for matching: leading slash, no query, trailing slash,
 * lowercase.
 *
 * @param string $path Path or URL.
 * @return string
 */
function semrs_redirect_norm( $path ) {
	$path = (string) wp_parse_url( trim( $path ), PHP_URL_PATH );
	$path = '/' . ltrim( $path, '/' );
	return strtolower( '/' === $path ? $path : trailingslashit( $path ) );
}

/**
 * Saved rules: list of [from, to, code].
 *
 * @return array<int, array{from:string,to:string,code:int}>
 */
function semrs_redirect_rules() {
	$rules = get_option( 'semrs_redirects', array() );
	return is_array( $rules ) ? $rules : array();
}

/**
 * Run a matching redirect. "/old-folder/*" matches everything under it;
 * a target ending in "*" receives the rest of the path.
 */
function semrs_redirect_run() {
	if ( ! semrs_seo_active() || is_admin() ) {
		return;
	}
	$req = isset( $_SERVER['REQUEST_URI'] ) ? semrs_redirect_norm( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '/';
	foreach ( semrs_redirect_rules() as $rule ) {
		$from = $rule['from'];
		$rest = null;
		if ( '*' === substr( $from, -1 ) ) {
			$prefix = strtolower( rtrim( $from, '*' ) );
			if ( 0 === strpos( $req, $prefix ) ) {
				$rest = substr( $req, strlen( $prefix ) );
			}
		} elseif ( semrs_redirect_norm( $from ) === $req ) {
			$rest = '';
		}
		if ( null === $rest ) {
			continue;
		}
		if ( 410 === (int) $rule['code'] ) {
			status_header( 410 );
			nocache_headers();
			wp_die( esc_html__( 'This page has been permanently removed.', 'semrs-core' ), esc_html__( 'Gone', 'semrs-core' ), array( 'response' => 410 ) );
		}
		$to = $rule['to'];
		if ( '*' === substr( $to, -1 ) ) {
			$to = rtrim( $to, '*' ) . ltrim( $rest, '/' );
		}
		if ( 0 === strpos( $to, '/' ) ) {
			$to = home_url( $to );
		}
		if ( semrs_redirect_norm( $to ) === $req && wp_parse_url( $to, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			continue; // Never loop.
		}
		wp_redirect( esc_url_raw( $to ), in_array( (int) $rule['code'], array( 301, 302, 307 ), true ) ? (int) $rule['code'] : 301, 'SEMRS' ); // phpcs:ignore WordPress.Security.SafeRedirect -- admin-defined targets may be external.
		exit;
	}
}
add_action( 'template_redirect', 'semrs_redirect_run', 1 );

/**
 * Log 404s (path, hits, last seen), capped at 200 entries. Obvious
 * scanner noise (.php, .env, wp-login probes) is skipped.
 */
function semrs_redirect_log_404() {
	if ( ! is_404() || ! semrs_seo_active() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	if ( '' === $path || preg_match( '/\.(php|env|asp|aspx|cgi|sql|bak|ini|log)$|wp-login|xmlrpc|\/\.git/i', $path ) || empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return;
	}
	$log = get_option( 'semrs_404_log', array() );
	$log = is_array( $log ) ? $log : array();
	$key = substr( $path, 0, 300 );
	$log[ $key ] = array(
		'hits' => isset( $log[ $key ] ) ? $log[ $key ]['hits'] + 1 : 1,
		'last' => time(),
	);
	if ( count( $log ) > 200 ) {
		uasort(
			$log,
			function ( $a, $b ) {
				return $b['last'] - $a['last'];
			}
		);
		$log = array_slice( $log, 0, 200, true );
	}
	update_option( 'semrs_404_log', $log, false );
}
add_action( 'template_redirect', 'semrs_redirect_log_404', 99 );

if ( ! is_admin() ) {
	return;
}

/**
 * Admin menu.
 */
function semrs_redirect_menu() {
	add_submenu_page( 'semrs', __( 'Redirects', 'semrs-core' ), __( 'Redirects', 'semrs-core' ), 'manage_options', 'semrs-redirects', 'semrs_redirect_page' );
}
add_action( 'admin_menu', 'semrs_redirect_menu', 21 );

/**
 * Handle add / delete / import / clear actions.
 */
function semrs_redirect_actions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'semrs-core' ) );
	}
	check_admin_referer( 'semrs_redirects' );
	$rules = semrs_redirect_rules();
	$do    = isset( $_POST['semrs_do'] ) ? sanitize_key( $_POST['semrs_do'] ) : '';
	$msg   = '';

	if ( 'add' === $do ) {
		// Keep percent-encoded octets (non-English slugs); sanitize_text_field() would strip them.
		$from = isset( $_POST['from'] ) ? trim( (string) wp_unslash( $_POST['from'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- reduced to URL-path characters below.
		$from = preg_replace_callback(
			'/[^\x20-\x7e]+/',
			function ( $m ) {
				return rawurlencode( $m[0] );
			},
			$from
		);
		$from = preg_replace( '#[^A-Za-z0-9\-._~%/*]#', '', $from );
		$to   = isset( $_POST['to'] ) ? esc_url_raw( wp_unslash( $_POST['to'] ) ) : '';
		$code = isset( $_POST['code'] ) ? absint( $_POST['code'] ) : 301;
		$from = '/' . ltrim( (string) wp_parse_url( $from, PHP_URL_PATH ), '/' );
		if ( '/' !== $from && ( $to || 410 === $code ) ) {
			$rules[] = array( 'from' => $from, 'to' => $to, 'code' => in_array( $code, array( 301, 302, 307, 410 ), true ) ? $code : 301 );
			$msg     = 'added';
		}
	} elseif ( 'delete' === $do && isset( $_POST['index'] ) ) {
		unset( $rules[ absint( $_POST['index'] ) ] );
		$rules = array_values( $rules );
		$msg   = 'deleted';
	} elseif ( 'clear404' === $do ) {
		delete_option( 'semrs_404_log' );
		$msg = 'cleared';
	} elseif ( 'import_rm' === $do ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rank_math_redirections';
		$count = 0;
		if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT sources, url_to, header_code FROM %i WHERE status = 'active'", $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-time import from Rank Math's table.
			foreach ( (array) $rows as $row ) {
				foreach ( (array) maybe_unserialize( $row->sources ) as $src ) {
					if ( empty( $src['pattern'] ) || ( isset( $src['comparison'] ) && 'exact' !== $src['comparison'] ) ) {
						continue; // Only exact matches import safely.
					}
					$rules[] = array(
						'from' => '/' . ltrim( (string) $src['pattern'], '/' ),
						'to'   => esc_url_raw( $row->url_to ),
						'code' => in_array( (int) $row->header_code, array( 301, 302, 307, 410 ), true ) ? (int) $row->header_code : 301,
					);
					$count++;
				}
			}
		}
		$msg = 'imported-' . $count;
	}

	update_option( 'semrs_redirects', $rules, true );
	wp_safe_redirect( add_query_arg( array( 'page' => 'semrs-redirects', 'msg' => $msg ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_semrs_redirects', 'semrs_redirect_actions' );

/**
 * Small POST form helper.
 *
 * @param string $do     Action.
 * @param string $label  Button label.
 * @param array  $fields Hidden fields.
 * @param string $class  Button class.
 */
function semrs_redirect_button( $do, $label, $fields = array(), $class = 'button' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
	wp_nonce_field( 'semrs_redirects' );
	echo '<input type="hidden" name="action" value="semrs_redirects"><input type="hidden" name="semrs_do" value="' . esc_attr( $do ) . '">';
	foreach ( $fields as $k => $v ) {
		echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
	}
	echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
}

/**
 * Redirects page.
 */
function semrs_redirect_page() {
	$rules = semrs_redirect_rules();
	$log   = get_option( 'semrs_404_log', array() );
	$msg   = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$from  = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Redirects', 'semrs-core' ); ?></h1>
		<?php if ( $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( 0 === strpos( $msg, 'imported-' ) ? sprintf( /* translators: %d: count */ __( 'Imported %d redirects from Rank Math.', 'semrs-core' ), (int) substr( $msg, 9 ) ) : __( 'Saved.', 'semrs-core' ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! semrs_seo_active() ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( sprintf( /* translators: %s: plugin */ __( '%s is active, so these redirects are paused. Import its redirects now; they start working when you deactivate it.', 'semrs-core' ), semrs_seo_conflict() ) ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Add redirect', 'semrs-core' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_redirects' ); ?>
			<input type="hidden" name="action" value="semrs_redirects"><input type="hidden" name="semrs_do" value="add">
			<input name="from" class="regular-text" placeholder="/old-page/  or  /old-folder/*" value="<?php echo esc_attr( $from ); ?>" required>
			→ <input name="to" class="regular-text" placeholder="/new-page/  or  https://…">
			<select name="code"><option value="301">301 Permanent</option><option value="302">302 Temporary</option><option value="307">307 Temporary</option><option value="410">410 Gone</option></select>
			<button class="button button-primary"><?php esc_html_e( 'Add', 'semrs-core' ); ?></button>
		</form>

		<?php // Long lists start folded; click the heading to show or hide them. ?>
		<details class="semrs-redirect-list"<?php echo count( $rules ) > 20 ? '' : ' open'; ?>>
		<summary style="cursor:pointer"><h2 style="display:inline-block"><?php esc_html_e( 'Active redirects', 'semrs-core' ); ?> (<?php echo count( $rules ); ?>)</h2> <span class="description"><?php esc_html_e( 'Click to show or hide', 'semrs-core' ); ?></span></summary>
		<table class="widefat striped" style="max-width:1000px"><thead><tr><th><?php esc_html_e( 'From', 'semrs-core' ); ?></th><th><?php esc_html_e( 'To', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Type', 'semrs-core' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( $rules as $i => $r ) : ?>
			<tr><td><code><?php echo esc_html( $r['from'] ); ?></code></td><td><?php echo esc_html( $r['to'] ); ?></td><td><?php echo (int) $r['code']; ?></td><td><?php semrs_redirect_button( 'delete', __( 'Delete', 'semrs-core' ), array( 'index' => $i ), 'button-link-delete' ); ?></td></tr>
		<?php endforeach; ?>
		<?php if ( ! $rules ) : ?><tr><td colspan="4"><?php esc_html_e( 'No redirects yet.', 'semrs-core' ); ?></td></tr><?php endif; ?>
		</tbody></table>
		</details>
		<p><?php semrs_redirect_button( 'import_rm', __( 'Import active redirects from Rank Math', 'semrs-core' ) ); ?></p>

		<h2><?php esc_html_e( '404 log', 'semrs-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Recent "not found" URLs. Redirect the ones that had real content or real links; ignore random junk.', 'semrs-core' ); ?></p>
		<table class="widefat striped" style="max-width:1000px"><thead><tr><th><?php esc_html_e( 'URL', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Hits', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Last seen', 'semrs-core' ); ?></th><th></th></tr></thead><tbody>
		<?php
		if ( is_array( $log ) && $log ) {
			uasort(
				$log,
				function ( $a, $b ) {
					return $b['hits'] - $a['hits'];
				}
			);
			foreach ( array_slice( $log, 0, 100, true ) as $path => $row ) {
				printf(
					'<tr><td><code>%s</code></td><td>%d</td><td>%s</td><td><a class="button button-small" href="%s">%s</a></td></tr>',
					esc_html( $path ),
					(int) $row['hits'],
					esc_html( human_time_diff( (int) $row['last'] ) . ' ' . __( 'ago', 'semrs-core' ) ),
					esc_url( add_query_arg( array( 'page' => 'semrs-redirects', 'from' => $path ), admin_url( 'admin.php' ) ) ),
					esc_html__( 'Redirect', 'semrs-core' )
				);
			}
		} else {
			echo '<tr><td colspan="4">' . esc_html__( 'No 404s logged.', 'semrs-core' ) . '</td></tr>';
		}
		?>
		</tbody></table>
		<p><?php semrs_redirect_button( 'clear404', __( 'Clear 404 log', 'semrs-core' ) ); ?></p>
	</div>
	<?php
}
