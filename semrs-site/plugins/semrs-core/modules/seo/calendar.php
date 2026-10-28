<?php
/**
 * SEMRS → Content Calendar: scheduled and published posts on a month calendar, with "Add to
 * Google Calendar" and .ics buttons, and one-click sharing to social networks.
 *
 * Sharing opens each network's own share window, already filled in, using the accounts you are
 * signed in to in your browser: no app keys needed. Automatic posting on publish (Pro) sends the
 * post to a Zapier, Make or n8n webhook, which can post to any account you connect there.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Share links for a post: network => [label, url].
 *
 * @param int|WP_Post $post Post.
 * @return array<string, array{0:string,1:string}>
 */
function semrs_share_links( $post ) {
	$post  = get_post( $post );
	$url   = rawurlencode( get_permalink( $post ) );
	$title = rawurlencode( html_entity_decode( get_the_title( $post ), ENT_QUOTES ) );
	$img   = has_post_thumbnail( $post ) ? rawurlencode( (string) get_the_post_thumbnail_url( $post, 'large' ) ) : '';
	return array(
		'facebook'  => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		'x'         => array( 'X', 'https://x.com/intent/post?url=' . $url . '&text=' . $title ),
		'linkedin'  => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
		'pinterest' => array( 'Pinterest', 'https://www.pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title . ( $img ? '&media=' . $img : '' ) ),
		'reddit'    => array( 'Reddit', 'https://www.reddit.com/submit?url=' . $url . '&title=' . $title ),
		'whatsapp'  => array( 'WhatsApp', 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url ),
		'telegram'  => array( 'Telegram', 'https://t.me/share/url?url=' . $url . '&text=' . $title ),
		'email'     => array( __( 'Email', 'semrs-core' ), 'mailto:?subject=' . $title . '&body=' . $url ),
	);
}

/**
 * Share buttons HTML (opens small windows).
 *
 * @param int|WP_Post $post Post.
 * @return string
 */
function semrs_share_buttons( $post ) {
	$out = '<span class="semrs-share">';
	foreach ( semrs_share_links( $post ) as $key => $link ) {
		$out .= '<a class="button button-small" href="' . esc_url( $link[1] ) . '" target="_blank" rel="noopener" onclick="' . ( 'email' === $key ? '' : 'window.open(this.href,\'semrsshare\',\'width=640,height=640\');return false;' ) . '">' . esc_html( $link[0] ) . '</a> ';
	}
	return $out . '<button type="button" class="button button-small" onclick="navigator.clipboard&&navigator.clipboard.writeText(' . esc_attr( wp_json_encode( get_permalink( $post ) ) ) . ');this.textContent=' . esc_attr( wp_json_encode( __( 'Copied', 'semrs-core' ) ) ) . '">' . esc_html__( 'Copy link', 'semrs-core' ) . '</button></span>';
}

/* ---------------------------------------------------------------------------
 * Automatic posting on publish (Pro): webhook to Zapier / Make / n8n
 * ------------------------------------------------------------------------- */

add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		$hook = (string) get_option( 'semrs_share_webhook', '' );
		if ( 'publish' !== $new || 'publish' === $old || 'post' !== $post->post_type || '' === $hook || ! ( function_exists( 'semrs_seo_pro' ) && semrs_seo_pro() ) ) {
			return;
		}
		wp_schedule_single_event( time() + 60, 'semrs_share_webhook_send', array( (int) $post->ID ) ); // After caches and images are ready.
	},
	10,
	3
);
add_action(
	'semrs_share_webhook_send',
	function ( $id ) {
		$post = get_post( $id );
		$hook = (string) get_option( 'semrs_share_webhook', '' );
		if ( ! $post || 'publish' !== $post->post_status || '' === $hook ) {
			return;
		}
		$desc = function_exists( 'semrs_seo_meta' ) ? (string) semrs_seo_meta( $id, 'og_description' ) : '';
		$desc = '' !== $desc ? $desc : ( function_exists( 'semrs_seo_meta' ) ? (string) semrs_seo_meta( $id, 'description' ) : '' );
		wp_remote_post(
			$hook,
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'title'       => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
						'url'         => get_permalink( $post ),
						'description' => '' !== $desc ? $desc : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ),
						'image'       => (string) get_the_post_thumbnail_url( $post, 'large' ),
						'published'   => get_the_date( 'c', $post ),
						'categories'  => wp_list_pluck( get_the_category( $id ), 'name' ),
						'site'        => get_bloginfo( 'name' ),
					)
				),
			)
		);
	}
);

if ( ! is_admin() ) {
	return;
}

// "Share" row action on post lists (published posts and pages).
foreach ( array( 'post_row_actions', 'page_row_actions' ) as $semrs_hook ) {
	add_filter(
		$semrs_hook,
		function ( $actions, $post ) {
			if ( 'publish' === $post->post_status && is_post_type_viewable( $post->post_type ) ) {
				$actions['semrs_share'] = '<a href="#" onclick="this.nextElementSibling.hidden=!this.nextElementSibling.hidden;return false;">' . esc_html__( 'Share', 'semrs-core' ) . '</a><span hidden style="display:block;margin-top:4px">' . semrs_share_buttons( $post ) . '</span>';
			}
			return $actions;
		},
		10,
		2
	);
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Content Calendar', 'semrs-core' ), __( 'Content Calendar', 'semrs-core' ), 'edit_posts', 'semrs-calendar', 'semrs_calendar_page' );
	},
	19
);

/**
 * Google Calendar "add event" link for a scheduled post.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function semrs_calendar_gcal( $post ) {
	$start = get_post_time( 'Ymd\THis\Z', true, $post );
	$end   = gmdate( 'Ymd\THis\Z', (int) get_post_time( 'U', true, $post ) + 30 * MINUTE_IN_SECONDS );
	/* translators: %s: post title */
	$text = sprintf( __( 'Publishes: %s', 'semrs-core' ), html_entity_decode( get_the_title( $post ), ENT_QUOTES ) );
	return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode( $text ) . '&dates=' . $start . '/' . $end . '&details=' . rawurlencode( get_edit_post_link( $post->ID, 'raw' ) );
}

// .ics download: one post or all scheduled posts.
add_action(
	'admin_post_semrs_calendar_ics',
	function () {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-core' ) );
		}
		check_admin_referer( 'semrs_calendar_ics' );
		$id    = absint( $_GET['id'] ?? 0 );
		$posts = $id ? array( get_post( $id ) ) : get_posts( array( 'post_type' => 'any', 'post_status' => 'future', 'posts_per_page' => 200, 'orderby' => 'date', 'order' => 'ASC' ) );
		$esc   = function ( $s ) { return preg_replace( '/([,;\\\\])/', '\\\\$1', str_replace( "\n", '\\n', (string) $s ) ); };
		$ics   = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//SEMRS//Content Calendar//EN\r\nCALSCALE:GREGORIAN\r\n";
		foreach ( array_filter( $posts ) as $p ) {
			$t    = (int) get_post_time( 'U', true, $p );
			$ics .= "BEGIN:VEVENT\r\nUID:semrs-" . $p->ID . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) . "\r\nDTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\nDTSTART:" . gmdate( 'Ymd\THis\Z', $t ) . "\r\nDTEND:" . gmdate( 'Ymd\THis\Z', $t + 1800 ) . "\r\nSUMMARY:" . $esc( 'Publishes: ' . html_entity_decode( get_the_title( $p ), ENT_QUOTES ) ) . "\r\nURL:" . $esc( get_permalink( $p ) ) . "\r\nEND:VEVENT\r\n";
		}
		$ics .= "END:VCALENDAR\r\n";
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . ( $id ? 'post-' . $id : 'scheduled-posts' ) . '.ics"' );
		echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput -- calendar file, escaped above.
		exit;
	}
);

// Auto-share webhook setting.
add_action(
	'admin_post_semrs_share_settings',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-core' ) );
		}
		check_admin_referer( 'semrs_share_settings' );
		update_option( 'semrs_share_webhook', esc_url_raw( wp_unslash( $_POST['webhook'] ?? '' ) ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-calendar&saved=1' ) );
		exit;
	}
);

/**
 * Screen: month grid, upcoming scheduled posts, sharing settings.
 */
function semrs_calendar_page() {
	$month = isset( $_GET['month'] ) && preg_match( '/^\d{4}-\d{2}$/', sanitize_text_field( wp_unslash( $_GET['month'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : wp_date( 'Y-m' ); // phpcs:ignore WordPress.Security.NonceVerification -- navigation only.
	$first = strtotime( $month . '-01 00:00:00' );
	$days  = (int) gmdate( 't', $first );
	$start = (int) get_option( 'start_of_week', 1 );
	$lead  = ( (int) gmdate( 'w', $first ) - $start + 7 ) % 7;
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending' ),
			'posts_per_page' => 300,
			'date_query'     => array( array( 'year' => (int) gmdate( 'Y', $first ), 'month' => (int) gmdate( 'n', $first ) ) ),
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	$by = array();
	foreach ( $posts as $p ) {
		if ( 'draft' === $p->post_status && '0000-00-00 00:00:00' === $p->post_date_gmt ) {
			continue; // Drafts without a chosen date don't belong on a day.
		}
		$by[ (int) get_post_time( 'j', false, $p ) ][] = $p;
	}
	$nav      = function ( $m ) { return admin_url( 'admin.php?page=semrs-calendar&month=' . $m ); };
	$prev     = gmdate( 'Y-m', strtotime( '-1 month', $first ) );
	$next     = gmdate( 'Y-m', strtotime( '+1 month', $first ) );
	$today    = wp_date( 'Y-m-d' );
	$upcoming = get_posts( array( 'post_type' => 'any', 'post_status' => 'future', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'ASC' ) );
	$ics_all  = wp_nonce_url( admin_url( 'admin-post.php?action=semrs_calendar_ics' ), 'semrs_calendar_ics' );
	$colours  = array( 'publish' => '#00813a', 'future' => '#2271b1', 'draft' => '#8c8f94', 'pending' => '#b26200' );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Content Calendar', 'semrs-core' ); ?></h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php esc_html_e( 'Schedule a new post', 'semrs-core' ); ?></a>
		<hr class="wp-header-end">
		<p style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
			<a class="button" href="<?php echo esc_url( $nav( $prev ) ); ?>">&larr; <?php esc_html_e( 'Previous', 'semrs-core' ); ?></a>
			<a class="button" href="<?php echo esc_url( $nav( wp_date( 'Y-m' ) ) ); ?>"><?php esc_html_e( 'Today', 'semrs-core' ); ?></a>
			<a class="button" href="<?php echo esc_url( $nav( $next ) ); ?>"><?php esc_html_e( 'Next', 'semrs-core' ); ?> &rarr;</a>
			<strong style="font-size:16px;margin-left:8px"><?php echo esc_html( wp_date( 'F Y', $first ) ); ?></strong>
			<span style="margin-left:auto">
				<?php foreach ( array( 'future' => __( 'Scheduled', 'semrs-core' ), 'publish' => __( 'Published', 'semrs-core' ), 'pending' => __( 'Pending review', 'semrs-core' ), 'draft' => __( 'Draft', 'semrs-core' ) ) as $st => $label ) : ?>
					<span style="display:inline-flex;align-items:center;gap:4px;margin-left:10px"><i style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $colours[ $st ] ); ?>"></i><?php echo esc_html( $label ); ?></span>
				<?php endforeach; ?>
			</span>
		</p>
		<table class="widefat" style="table-layout:fixed;max-width:1200px">
			<thead><tr>
				<?php for ( $i = 0; $i < 7; $i++ ) : ?>
					<th><?php echo esc_html( $GLOBALS['wp_locale']->get_weekday_abbrev( $GLOBALS['wp_locale']->get_weekday( ( $start + $i ) % 7 ) ) ); ?></th>
				<?php endfor; ?>
			</tr></thead>
			<tbody><tr>
			<?php
			for ( $i = 0; $i < $lead; $i++ ) {
				echo '<td style="background:#f6f7f7"></td>';
			}
			for ( $d = 1; $d <= $days; $d++ ) {
				$date   = $month . '-' . str_pad( (string) $d, 2, '0', STR_PAD_LEFT );
				$is_tod = $date === $today;
				echo '<td style="vertical-align:top;height:96px;' . ( $is_tod ? 'outline:2px solid #2271b1;outline-offset:-2px;' : '' ) . '"><div style="font-weight:600;color:' . ( $is_tod ? '#2271b1' : '#50575e' ) . '">' . (int) $d . '</div>';
				foreach ( $by[ $d ] ?? array() as $p ) {
					echo '<a href="' . esc_url( (string) get_edit_post_link( $p->ID ) ) . '" title="' . esc_attr( get_post_time( get_option( 'time_format' ), false, $p ) ) . '" style="display:block;margin:3px 0;padding:2px 6px;border-radius:4px;color:#fff;font-size:12px;line-height:1.4;text-decoration:none;background:' . esc_attr( $colours[ $p->post_status ] ?? '#8c8f94' ) . '">' . esc_html( wp_trim_words( get_the_title( $p ), 6 ) ) . '</a>';
				}
				echo '</td>';
				if ( 0 === ( $lead + $d ) % 7 && $d < $days ) {
					echo '</tr><tr>';
				}
			}
			for ( $i = ( $lead + $days ) % 7; $i > 0 && $i < 7; $i++ ) {
				echo '<td style="background:#f6f7f7"></td>';
			}
			?>
			</tr></tbody>
		</table>

		<h2><?php esc_html_e( 'Scheduled posts', 'semrs-core' ); ?> (<?php echo count( $upcoming ); ?>)</h2>
		<?php if ( $upcoming ) : ?>
			<p><a class="button" href="<?php echo esc_url( $ics_all ); ?>"><?php esc_html_e( 'Download all to your calendar (.ics)', 'semrs-core' ); ?></a></p>
			<table class="widefat striped" style="max-width:1200px">
				<thead><tr><th><?php esc_html_e( 'Post', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Publishes', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Calendar', 'semrs-core' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $upcoming as $p ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></td>
						<td><?php echo esc_html( get_post_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $p ) ); ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( semrs_calendar_gcal( $p ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Add to Google Calendar', 'semrs-core' ); ?></a>
							<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=semrs_calendar_ics&id=' . $p->ID ), 'semrs_calendar_ics' ) ); ?>"><?php esc_html_e( 'Apple / Outlook (.ics)', 'semrs-core' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing is scheduled. In the editor, set a future date under Publish to schedule a post.', 'semrs-core' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Share recent posts', 'semrs-core' ); ?></h2>
		<p class="description"><?php esc_html_e( 'One click opens the network\'s share window, already filled in, for the account you are signed in to. A "Share" link also appears under each post in Posts and Pages, and in the SEMRS SEO sidebar.', 'semrs-core' ); ?></p>
		<table class="widefat striped" style="max-width:1200px"><tbody>
			<?php foreach ( get_posts( array( 'post_status' => 'publish', 'posts_per_page' => 8 ) ) as $p ) : ?>
				<tr><td style="width:38%"><a href="<?php echo esc_url( get_permalink( $p ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $p ) ); ?></a></td><td><?php echo semrs_share_buttons( $p ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the function. ?></td></tr>
			<?php endforeach; ?>
		</tbody></table>

		<h2><?php esc_html_e( 'Post automatically when a post is published', 'semrs-core' ); ?><?php echo ( function_exists( 'semrs_seo_pro' ) && semrs_seo_pro() ) ? '' : ' <span style="background:#f0b849;color:#1d2327;font-size:11px;font-weight:700;border-radius:3px;padding:1px 6px;vertical-align:middle">PRO</span>'; ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_share_settings' ); ?>
			<input type="hidden" name="action" value="semrs_share_settings">
			<p><input type="url" name="webhook" class="large-text" value="<?php echo esc_attr( (string) get_option( 'semrs_share_webhook', '' ) ); ?>" placeholder="https://hooks.zapier.com/hooks/catch/…" <?php disabled( ! ( function_exists( 'semrs_seo_pro' ) && semrs_seo_pro() ) ); ?>></p>
			<p class="description"><?php esc_html_e( 'Paste a Zapier, Make or n8n webhook address. When a post is published, SEMRS sends its title, link, description, image and categories there; your Zap or scenario posts it to the Facebook page, LinkedIn, X, Instagram or Google Business Profile accounts you connect.', 'semrs-core' ); ?></p>
			<?php submit_button( __( 'Save', 'semrs-core' ), 'secondary' ); ?>
		</form>
	</div>
	<?php
}
