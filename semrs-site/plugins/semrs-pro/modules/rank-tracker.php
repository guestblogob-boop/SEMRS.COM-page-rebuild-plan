<?php
/**
 * SEO Performance (Pro): Google Search Console clicks, impressions, CTR and positions, top
 * queries and pages, and keyword rank tracking with daily history and a weekly email report.
 *
 * The data comes from Site Kit by Google (free plugin, connected to your Search Console), so
 * there is no separate Google sign-in. Search Console reports with a 2-3 day delay; positions
 * are Google's average position for the keyword on that day.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array{keywords:string[],focus:bool,email:bool,to:string}
 */
function semrs_rank_settings() {
	$s = get_option( 'semrs_rank', array() );
	return array_merge(
		array(
			'keywords' => array(),
			'focus'    => true,  // Track every post's focus keyword too.
			'email'    => false, // Weekly report.
			'to'       => get_option( 'admin_email' ),
		),
		is_array( $s ) ? $s : array()
	);
}

/**
 * Pro license present?
 *
 * @return bool
 */
function semrs_rank_pro() {
	return function_exists( 'semrs_seo_pro' ) && semrs_seo_pro();
}

/**
 * Search Console rows from Site Kit.
 *
 * @param array $args startDate, endDate (Y-m-d), dimensions (query|page|date), limit.
 * @return array|WP_Error Rows: keys[], clicks, impressions, ctr, position.
 */
function semrs_rank_gsc( $args ) {
	if ( ! defined( 'GOOGLESITEKIT_VERSION' ) ) {
		return new WP_Error( 'semrs_rank_sitekit', __( 'Install and connect Site Kit by Google (free) to see Search Console data here.', 'semrs-pro' ) );
	}
	$key  = 'semrs_gsc_' . md5( wp_json_encode( $args ) );
	$rows = get_transient( $key );
	if ( is_array( $rows ) ) {
		return $rows;
	}
	// Background runs have no signed-in user: act as the person who connected Search Console.
	$switched = false;
	if ( ! get_current_user_id() ) {
		$gsc   = (array) get_option( 'googlesitekit_search-console_settings', array() );
		$owner = (int) ( $gsc['ownerID'] ?? 0 );
		if ( $owner ) {
			wp_set_current_user( $owner );
			$switched = true;
		}
	}
	$req = new WP_REST_Request( 'GET', '/google-site-kit/v1/modules/search-console/data/searchanalytics' );
	foreach ( $args as $k => $v ) {
		$req->set_param( $k, $v );
	}
	$res = rest_do_request( $req );
	if ( $switched ) {
		wp_set_current_user( 0 );
	}
	$data = $res->get_data();
	if ( $res->is_error() || ! is_array( $data ) || isset( $data['code'] ) ) {
		$msg = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : __( 'Site Kit could not load Search Console data. Check that Search Console is connected in Site Kit.', 'semrs-pro' );
		return new WP_Error( 'semrs_rank_gsc', $msg );
	}
	set_transient( $key, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

/**
 * Keywords being tracked (your list plus focus keywords), lower case, unique.
 *
 * @return string[]
 */
function semrs_rank_keywords() {
	$s   = semrs_rank_settings();
	$kws = array_map( 'mb_strtolower', array_map( 'trim', (array) $s['keywords'] ) );
	if ( $s['focus'] ) {
		$ids = get_posts( array( 'post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => 300, 'fields' => 'ids', 'meta_key' => '_semrs_focus', 'no_found_rows' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery -- daily run.
		foreach ( $ids as $id ) {
			$kws[] = mb_strtolower( trim( (string) get_post_meta( $id, '_semrs_focus', true ) ) );
		}
	}
	return array_values( array_unique( array_filter( $kws ) ) );
}

/**
 * History: keyword => [ 'Y-m-d' => [position, clicks, impressions], … ] (180 days kept).
 *
 * @return array
 */
function semrs_rank_history() {
	$h = get_option( 'semrs_rank_history', array() );
	return is_array( $h ) ? $h : array();
}

/**
 * Daily run: record the latest day Search Console has for every tracked keyword.
 */
function semrs_rank_daily() {
	if ( ! semrs_rank_pro() ) {
		return;
	}
	$hist = semrs_rank_history();
	$kws  = semrs_rank_keywords();
	$new  = array_diff( $kws, array_keys( $hist ) );
	// A newly tracked keyword gets 30 days of history; after that the last 5 days are refreshed
	// each run, which also picks up Search Console's late-arriving data.
	$end   = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
	$start = gmdate( 'Y-m-d', strtotime( $new ? '-31 days' : '-6 days' ) );
	$rows  = semrs_rank_gsc( array( 'startDate' => $start, 'endDate' => $end, 'dimensions' => 'date,query', 'limit' => 25000 ) );
	if ( is_wp_error( $rows ) ) {
		return;
	}
	$by = array();
	foreach ( $rows as $r ) {
		$by[ mb_strtolower( (string) ( $r['keys'][1] ?? '' ) ) ][ (string) ( $r['keys'][0] ?? '' ) ] = array( round( (float) $r['position'], 1 ), (int) $r['clicks'], (int) $r['impressions'] );
	}
	foreach ( $kws as $kw ) {
		$hist[ $kw ] = array_merge( $hist[ $kw ] ?? array(), $by[ $kw ] ?? array() );
		if ( ! $hist[ $kw ] ) {
			$hist[ $kw ][ $end ] = array( null, 0, 0 ); // Tracked, not ranking yet.
		}
		ksort( $hist[ $kw ] );
		$hist[ $kw ] = array_slice( $hist[ $kw ], -180, null, true );
	}
	update_option( 'semrs_rank_history', $hist, false );
}
add_action( 'semrs_rank_daily', 'semrs_rank_daily' );

/**
 * Weekly report email.
 */
function semrs_rank_weekly() {
	$s = semrs_rank_settings();
	if ( ! $s['email'] || ! semrs_rank_pro() || ! is_email( $s['to'] ) ) {
		return;
	}
	$end   = gmdate( 'Y-m-d', strtotime( '-3 days' ) );
	$start = gmdate( 'Y-m-d', strtotime( '-9 days' ) );
	$rows  = semrs_rank_gsc( array( 'startDate' => $start, 'endDate' => $end, 'dimensions' => 'date', 'limit' => 10 ) );
	if ( is_wp_error( $rows ) ) {
		return;
	}
	$clicks = array_sum( wp_list_pluck( $rows, 'clicks' ) );
	$impr   = array_sum( wp_list_pluck( $rows, 'impressions' ) );
	$lines  = array();
	foreach ( semrs_rank_history() as $kw => $days ) {
		$pos = array_values( array_filter( wp_list_pluck( $days, 0 ), 'is_numeric' ) );
		if ( ! $pos ) {
			continue;
		}
		$now  = end( $pos );
		$prev = count( $pos ) > 7 ? $pos[ count( $pos ) - 8 ] : $pos[0];
		/* translators: 1: keyword, 2: position, 3: change */
		$lines[] = sprintf( __( '%1$s: position %2$s (%3$s)', 'semrs-pro' ), $kw, $now, $prev > $now ? '▲ ' . round( $prev - $now, 1 ) : ( $prev < $now ? '▼ ' . round( $now - $prev, 1 ) : '=' ) );
	}
	/* translators: 1: site name, 2: start date, 3: end date */
	$subject = sprintf( __( '%1$s SEO report: %2$s to %3$s', 'semrs-pro' ), get_bloginfo( 'name' ), $start, $end );
	/* translators: 1: clicks, 2: impressions */
	$body = sprintf( __( "Google Search, last 7 days of data:\nClicks: %1\$d\nImpressions: %2\$d\n\nTracked keywords:\n", 'semrs-pro' ), $clicks, $impr ) . ( $lines ? implode( "\n", array_slice( $lines, 0, 50 ) ) : __( 'No tracked keyword had impressions yet.', 'semrs-pro' ) ) . "\n\n" . admin_url( 'admin.php?page=semrs-performance' );
	wp_mail( $s['to'], $subject, $body );
}
add_action( 'semrs_rank_weekly', 'semrs_rank_weekly' );

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'semrs_rank_daily' ) ) {
			wp_schedule_event( time() + 600, 'daily', 'semrs_rank_daily' );
		}
		if ( ! wp_next_scheduled( 'semrs_rank_weekly' ) ) {
			wp_schedule_event( strtotime( 'next monday 07:00' ), 'weekly', 'semrs_rank_weekly' );
		}
	}
);

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'SEO Performance', 'semrs-pro' ), __( 'SEO Performance', 'semrs-pro' ), 'edit_others_posts', 'semrs-performance', 'semrs_rank_page' );
	},
	19
);

add_action(
	'admin_post_semrs_rank',
	function () {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-pro' ) );
		}
		check_admin_referer( 'semrs_rank' );
		$s  = semrs_rank_settings();
		$do = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		if ( 'track' === $do ) {
			$s['keywords'][] = sanitize_text_field( wp_unslash( $_POST['kw'] ?? '' ) );
		} elseif ( 'untrack' === $do ) {
			$kw              = mb_strtolower( sanitize_text_field( wp_unslash( $_POST['kw'] ?? '' ) ) );
			$s['keywords']   = array_values( array_filter( (array) $s['keywords'], function ( $k ) use ( $kw ) { return mb_strtolower( $k ) !== $kw; } ) );
			$hist            = semrs_rank_history();
			unset( $hist[ $kw ] );
			update_option( 'semrs_rank_history', $hist, false );
		} elseif ( 'save' === $do ) {
			$in            = wp_unslash( $_POST['semrs_rank'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
			$s['keywords'] = array_filter( array_map( 'sanitize_text_field', preg_split( '/\r?\n/', (string) ( $in['keywords'] ?? '' ) ) ) );
			$s['focus']    = ! empty( $in['focus'] );
			$s['email']    = ! empty( $in['email'] );
			$s['to']       = sanitize_email( $in['to'] ?? '' );
		} elseif ( 'refresh' === $do ) {
			semrs_rank_daily();
		}
		$s['keywords'] = array_values( array_unique( array_filter( array_map( 'trim', (array) $s['keywords'] ) ) ) );
		update_option( 'semrs_rank', $s, false );
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-performance&saved=1' ) );
		exit;
	}
);

/**
 * Small inline line chart (SVG); lower is better for positions.
 *
 * @param array $values Numbers (nulls skipped).
 * @param bool  $invert Lower values drawn higher (positions).
 * @return string
 */
function semrs_rank_spark( $values, $invert = false ) {
	$vals = array_values( array_filter( $values, 'is_numeric' ) );
	if ( count( $vals ) < 2 ) {
		return '';
	}
	$w   = 120;
	$h   = 28;
	$min = min( $vals );
	$max = max( $vals );
	$rng = max( 0.0001, $max - $min );
	$pts = array();
	foreach ( $vals as $i => $v ) {
		$y     = ( $v - $min ) / $rng;
		$pts[] = round( $i / ( count( $vals ) - 1 ) * $w, 1 ) . ',' . round( ( $invert ? $y : 1 - $y ) * ( $h - 4 ) + 2, 1 );
	}
	return '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . esc_attr__( 'Trend', 'semrs-pro' ) . '"><polyline fill="none" stroke="#2271b1" stroke-width="2" points="' . esc_attr( implode( ' ', $pts ) ) . '"/></svg>';
}

/**
 * One small form button.
 *
 * @param string $do    Action.
 * @param string $label Label.
 * @param string $kw    Keyword.
 */
function semrs_rank_button( $do, $label, $kw = '' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
	wp_nonce_field( 'semrs_rank' );
	echo '<input type="hidden" name="action" value="semrs_rank"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><input type="hidden" name="kw" value="' . esc_attr( $kw ) . '"><button class="button button-small">' . esc_html( $label ) . '</button></form>';
}

/**
 * Screen.
 */
function semrs_rank_page() {
	$s     = semrs_rank_settings();
	$end   = gmdate( 'Y-m-d', strtotime( '-3 days' ) );
	$start = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
	$pro   = semrs_rank_pro();
	echo '<div class="wrap"><h1>' . esc_html__( 'SEO Performance', 'semrs-pro' ) . '</h1>';
	if ( ! $pro ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Search Console data and rank tracking unlock with a SEMRS Pro license.', 'semrs-pro' ) . '</p></div></div>';
		return;
	}
	$days = semrs_rank_gsc( array( 'startDate' => $start, 'endDate' => $end, 'dimensions' => 'date', 'limit' => 40 ) );
	if ( is_wp_error( $days ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html( $days->get_error_message() ) . '</p></div></div>';
		return;
	}
	$clicks = array_sum( wp_list_pluck( $days, 'clicks' ) );
	$impr   = array_sum( wp_list_pluck( $days, 'impressions' ) );
	$pos    = $impr ? array_sum( array_map( function ( $r ) { return $r['position'] * $r['impressions']; }, $days ) ) / $impr : 0;
	/* translators: 1: start date, 2: end date */
	echo '<p class="description">' . esc_html( sprintf( __( 'Google Search, %1$s to %2$s (Search Console data arrives 2-3 days late).', 'semrs-pro' ), $start, $end ) ) . '</p>';
	echo '<div style="display:flex;flex-wrap:wrap;gap:12px;margin:12px 0">';
	foreach ( array(
		array( __( 'Clicks', 'semrs-pro' ), number_format_i18n( $clicks ), wp_list_pluck( $days, 'clicks' ), false ),
		array( __( 'Impressions', 'semrs-pro' ), number_format_i18n( $impr ), wp_list_pluck( $days, 'impressions' ), false ),
		array( __( 'Average CTR', 'semrs-pro' ), $impr ? number_format_i18n( $clicks / $impr * 100, 1 ) . '%' : '0%', array(), false ),
		array( __( 'Average position', 'semrs-pro' ), number_format_i18n( $pos, 1 ), wp_list_pluck( $days, 'position' ), true ),
	) as $card ) {
		echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 16px;min-width:180px"><div style="color:#646970">' . esc_html( $card[0] ) . '</div><div style="font-size:24px;font-weight:600">' . esc_html( $card[1] ) . '</div>' . semrs_rank_spark( $card[2], $card[3] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers.
	}
	echo '</div>';

	// Tracked keywords.
	$hist    = semrs_rank_history();
	$tracked = semrs_rank_keywords();
	echo '<h2>' . esc_html__( 'Tracked keywords', 'semrs-pro' ) . ' (' . count( $tracked ) . ')</h2>';
	echo '<p>';
	semrs_rank_button( 'refresh', __( 'Update positions now', 'semrs-pro' ) );
	echo ' <span class="description">' . esc_html__( 'Positions update every day in the background.', 'semrs-pro' ) . '</span></p>';
	echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>' . esc_html__( 'Keyword', 'semrs-pro' ) . '</th><th>' . esc_html__( 'Position', 'semrs-pro' ) . '</th><th>' . esc_html__( '7-day change', 'semrs-pro' ) . '</th><th>' . esc_html__( 'Best', 'semrs-pro' ) . '</th><th>' . esc_html__( 'Trend', 'semrs-pro' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $tracked as $kw ) {
		$p    = array_values( array_filter( wp_list_pluck( (array) ( $hist[ $kw ] ?? array() ), 0 ), 'is_numeric' ) );
		$now  = $p ? end( $p ) : null;
		$prev = count( $p ) > 7 ? $p[ count( $p ) - 8 ] : ( $p ? $p[0] : null );
		$diff = null !== $now && null !== $prev ? round( $prev - $now, 1 ) : null;
		echo '<tr><td>' . esc_html( $kw ) . '</td><td>' . ( null !== $now ? esc_html( (string) $now ) : '<span class="description">' . esc_html__( 'Not ranking yet', 'semrs-pro' ) . '</span>' ) . '</td><td>' . ( $diff ? '<span style="color:' . ( $diff > 0 ? '#00813a' : '#b32d2e' ) . '">' . ( $diff > 0 ? '▲ ' : '▼ ' ) . esc_html( (string) abs( $diff ) ) . '</span>' : '—' ) . '</td><td>' . ( $p ? esc_html( (string) min( $p ) ) : '—' ) . '</td><td>' . semrs_rank_spark( $p, true ) . '</td><td>'; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers.
		if ( in_array( $kw, array_map( 'mb_strtolower', (array) $s['keywords'] ), true ) ) {
			semrs_rank_button( 'untrack', __( 'Stop tracking', 'semrs-pro' ), $kw );
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';

	// Top queries and pages.
	$queries = semrs_rank_gsc( array( 'startDate' => $start, 'endDate' => $end, 'dimensions' => 'query', 'limit' => 50 ) );
	$pages   = semrs_rank_gsc( array( 'startDate' => $start, 'endDate' => $end, 'dimensions' => 'page', 'limit' => 25 ) );
	echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:24px;max-width:1400px">';
	foreach ( array( array( __( 'Top search queries', 'semrs-pro' ), $queries, true ), array( __( 'Top pages', 'semrs-pro' ), $pages, false ) ) as $tbl ) {
		echo '<div><h2>' . esc_html( $tbl[0] ) . '</h2>';
		if ( is_wp_error( $tbl[1] ) || ! $tbl[1] ) {
			echo '<p>' . esc_html__( 'No data yet.', 'semrs-pro' ) . '</p></div>';
			continue;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html( $tbl[2] ? __( 'Query', 'semrs-pro' ) : __( 'Page', 'semrs-pro' ) ) . '</th><th>' . esc_html__( 'Clicks', 'semrs-pro' ) . '</th><th>' . esc_html__( 'Impr.', 'semrs-pro' ) . '</th><th>' . esc_html__( 'Pos.', 'semrs-pro' ) . '</th>' . ( $tbl[2] ? '<th></th>' : '' ) . '</tr></thead><tbody>';
		foreach ( $tbl[1] as $r ) {
			$k = (string) ( $r['keys'][0] ?? '' );
			echo '<tr><td style="word-break:break-word">' . ( $tbl[2] ? esc_html( $k ) : '<a href="' . esc_url( $k ) . '" target="_blank" rel="noopener">' . esc_html( str_replace( home_url(), '', $k ) ) . '</a>' ) . '</td><td>' . (int) $r['clicks'] . '</td><td>' . (int) $r['impressions'] . '</td><td>' . esc_html( number_format_i18n( (float) $r['position'], 1 ) ) . '</td>';
			if ( $tbl[2] ) {
				echo '<td>';
				if ( ! in_array( mb_strtolower( $k ), $tracked, true ) ) {
					semrs_rank_button( 'track', __( 'Track', 'semrs-pro' ), $k );
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}
	echo '</div>';

	// Settings.
	?>
	<h2><?php esc_html_e( 'Settings', 'semrs-pro' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'semrs_rank' ); ?>
		<input type="hidden" name="action" value="semrs_rank"><input type="hidden" name="do" value="save">
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="semrs_rank_kw"><?php esc_html_e( 'Keywords to track', 'semrs-pro' ); ?></label></th><td>
				<textarea id="semrs_rank_kw" name="semrs_rank[keywords]" rows="5" class="large-text"><?php echo esc_textarea( implode( "\n", (array) $s['keywords'] ) ); ?></textarea>
				<p class="description"><?php esc_html_e( 'One per line.', 'semrs-pro' ); ?></p>
				<label><input type="checkbox" name="semrs_rank[focus]" value="1" <?php checked( $s['focus'] ); ?>> <?php esc_html_e( 'Also track every published post\'s focus keyword', 'semrs-pro' ); ?></label>
			</td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Weekly email report', 'semrs-pro' ); ?></th><td>
				<label><input type="checkbox" name="semrs_rank[email]" value="1" <?php checked( $s['email'] ); ?>> <?php esc_html_e( 'Send clicks, impressions and keyword changes every Monday to', 'semrs-pro' ); ?></label>
				<input type="email" name="semrs_rank[to]" value="<?php echo esc_attr( $s['to'] ); ?>" class="regular-text">
			</td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
	</div>
	<?php
}
