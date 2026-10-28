<?php
/**
 * Link Assistant (Pro): automatic internal links and a broken link checker.
 *
 * Automatic links are added when a page is shown (the saved content is never changed): each
 * keyword links once per page to its target, never to the page itself, never inside headings,
 * buttons or existing links, and never more than the per-page limit, so pages don't get
 * stuffed with links. Keywords come from your own rules and, optionally, from each post's
 * focus keyword.
 *
 * The broken link checker reads the links in published content, checks them in small
 * batches in the background (WP-Cron) and lists the broken ones with the pages they're on.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_links_settings() {
	$s = get_option( 'semrs_links', array() );
	return array_merge(
		array(
			'auto'      => false,   // Automatic internal links on.
			'focus'     => true,    // Use each post's focus keyword as a link to that post.
			'max'       => 3,       // Automatic links per page.
			'rules'     => array(), // [ [keywords, url], … ].
			'checker'   => true,    // Broken link checker on.
			'externals' => true,    // Check links to other sites too.
		),
		is_array( $s ) ? $s : array()
	);
}

/**
 * Pro license present?
 *
 * @return bool
 */
function semrs_links_pro() {
	return function_exists( 'semrs_seo_pro' ) && semrs_seo_pro();
}

/* ---------------------------------------------------------------------------
 * Automatic internal links
 * ------------------------------------------------------------------------- */

/**
 * Keyword → URL map (longest keywords first, so "local seo services" wins over "local seo").
 *
 * @return array<string, string>
 */
function semrs_links_map() {
	$map = get_transient( 'semrs_links_map' );
	if ( is_array( $map ) ) {
		return $map;
	}
	$s   = semrs_links_settings();
	$map = array();
	foreach ( (array) $s['rules'] as $rule ) {
		foreach ( array_filter( array_map( 'trim', explode( ',', (string) ( $rule[0] ?? '' ) ) ) ) as $kw ) {
			if ( mb_strlen( $kw ) >= 3 && ! empty( $rule[1] ) ) {
				$map[ mb_strtolower( $kw ) ] = esc_url_raw( $rule[1] );
			}
		}
	}
	if ( $s['focus'] && function_exists( 'semrs_seo_post_types' ) ) {
		$ids = get_posts(
			array(
				'post_type'      => semrs_seo_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'meta_key'       => '_semrs_focus', // phpcs:ignore WordPress.DB.SlowDBQuery -- cached for a day.
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$kw = mb_strtolower( trim( (string) get_post_meta( $id, '_semrs_focus', true ) ) );
			if ( mb_strlen( $kw ) >= 4 && ! isset( $map[ $kw ] ) && ! ( function_exists( 'semrs_seo_is_noindex' ) && semrs_seo_is_noindex( $id ) ) ) {
				$map[ $kw ] = get_permalink( $id ); // Your own rules win over focus keywords.
			}
		}
	}
	uksort( $map, function ( $a, $b ) { return mb_strlen( $b ) - mb_strlen( $a ); } );
	set_transient( 'semrs_links_map', $map, DAY_IN_SECONDS );
	return $map;
}

/**
 * Add the links to a page's HTML.
 *
 * @param string $html    Content HTML.
 * @param int    $post_id Current post.
 * @return string
 */
function semrs_links_apply( $html, $post_id ) {
	$map = semrs_links_map();
	if ( ! $map || '' === $html ) {
		return $html;
	}
	$self = untrailingslashit( (string) get_permalink( $post_id ) );
	// Targets the page already links to are skipped.
	preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $m );
	$linked = array_map( function ( $u ) { return untrailingslashit( strtok( html_entity_decode( $u ), '#?' ) ); }, $m[1] );
	$max    = max( 0, min( 10, (int) semrs_links_settings()['max'] ) );
	$used   = array();
	$added  = 0;
	$skip   = 0; // Depth inside tags we never link in.
	$parts  = preg_split( '/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( $added >= $max ) {
			break;
		}
		if ( '' === $part ) {
			continue;
		}
		if ( '<' === $part[0] ) {
			if ( preg_match( '#^<(/?)(a|h[1-6]|button|script|style|code|pre|figcaption|nav|select|textarea|label|summary)\b#i', $part, $t ) ) {
				$skip += $t[1] ? -1 : ( '/>' === substr( $part, -2 ) ? 0 : 1 );
				$skip  = max( 0, $skip );
			}
			continue;
		}
		if ( $skip > 0 || mb_strlen( trim( $part ) ) < 4 ) {
			continue;
		}
		// New links become placeholders first, so a shorter keyword can't match inside one.
		$made = array();
		foreach ( $map as $kw => $url ) {
			if ( $added >= $max ) {
				break;
			}
			$target = untrailingslashit( strtok( $url, '#?' ) );
			if ( isset( $used[ $target ] ) || $target === $self || in_array( $target, $linked, true ) ) {
				continue;
			}
			$re = '/(?<![\p{L}\p{N}])(' . preg_quote( $kw, '/' ) . ')(?![\p{L}\p{N}])/iu';
			if ( preg_match( $re, $part, $hit ) ) {
				$token           = "\x01" . count( $made ) . "\x01";
				$made[ $token ]  = '<a href="' . esc_url( $url ) . '" class="semrs-autolink">' . $hit[1] . '</a>';
				$part            = preg_replace( $re, $token, $part, 1 );
				$used[ $target ] = true;
				$added++;
			}
		}
		$parts[ $i ] = strtr( $part, $made );
	}
	return implode( '', $parts );
}

add_filter(
	'the_content',
	function ( $html ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || ! semrs_links_settings()['auto'] || ! semrs_links_pro() ) {
			return $html;
		}
		return semrs_links_apply( $html, get_the_ID() );
	},
	20
);

// The keyword map changes when posts or settings change.
add_action( 'save_post', function () { delete_transient( 'semrs_links_map' ); } );
add_action( 'update_option_semrs_links', function () { delete_transient( 'semrs_links_map' ); } );

/* ---------------------------------------------------------------------------
 * Broken link checker
 * ------------------------------------------------------------------------- */

/**
 * The link index: url => [status, checked (time), posts (ids), note].
 *
 * @return array
 */
function semrs_links_index() {
	$idx = get_option( 'semrs_links_index', array() );
	return is_array( $idx ) ? $idx : array();
}

/**
 * Links found in one post's content.
 *
 * @param WP_Post $post Post.
 * @return string[]
 */
function semrs_links_in_post( $post ) {
	$out = array();
	if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', (string) $post->post_content, $m ) ) {
		foreach ( $m[1] as $href ) {
			$href = trim( html_entity_decode( $href ) );
			if ( 0 === strpos( $href, '/' ) && 0 !== strpos( $href, '//' ) ) {
				$href = home_url( $href );
			}
			if ( ! preg_match( '#^https?://#i', $href ) ) {
				continue; // mailto:, tel:, #anchors.
			}
			$href = strtok( $href, '#' );
			$out[ $href ] = true;
		}
	}
	return array_keys( $out );
}

/**
 * Re-index one post (after it is saved).
 *
 * @param int $post_id Post ID.
 */
function semrs_links_index_post( $post_id ) {
	$post = get_post( $post_id );
	$idx  = semrs_links_index();
	foreach ( $idx as $url => $row ) {
		$idx[ $url ]['posts'] = array_values( array_diff( (array) $row['posts'], array( (int) $post_id ) ) );
		if ( ! $idx[ $url ]['posts'] ) {
			unset( $idx[ $url ] );
		}
	}
	if ( $post && 'publish' === $post->post_status ) {
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		foreach ( semrs_links_in_post( $post ) as $url ) {
			if ( ! semrs_links_settings()['externals'] && wp_parse_url( $url, PHP_URL_HOST ) !== $home ) {
				continue;
			}
			$idx[ $url ]            = $idx[ $url ] ?? array( 'status' => 0, 'checked' => 0, 'posts' => array(), 'note' => '' );
			$idx[ $url ]['posts'][] = (int) $post_id;
			$idx[ $url ]['posts']   = array_values( array_unique( $idx[ $url ]['posts'] ) );
		}
	}
	update_option( 'semrs_links_index', $idx, false );
}
add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) && semrs_links_settings()['checker'] && semrs_links_pro() ) {
			semrs_links_index_post( $post_id );
		}
	},
	30
);

/**
 * Index every published post (first run, or "Scan again").
 */
function semrs_links_full_index() {
	update_option( 'semrs_links_index', array(), false );
	$ids = get_posts(
		array(
			'post_type'      => function_exists( 'semrs_seo_post_types' ) ? semrs_seo_post_types() : array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		semrs_links_index_post( $id );
	}
	update_option( 'semrs_links_indexed', time(), false );
}

/**
 * Check one URL: HTTP status, or 0 with a note when the site can't be reached.
 *
 * @param string $url URL.
 * @return array{0:int,1:string}
 */
function semrs_links_check_url( $url ) {
	$args = array( 'timeout' => 10, 'redirection' => 5, 'user-agent' => 'Mozilla/5.0 (compatible; SEMRS Link Checker; +' . home_url( '/' ) . ')' );
	$res  = wp_remote_head( $url, $args );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	if ( is_wp_error( $res ) || in_array( $code, array( 403, 405, 501 ), true ) ) {
		$res  = wp_remote_get( $url, $args + array( 'limit_response_size' => 4096 ) ); // Some servers refuse HEAD.
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	}
	return array( $code, is_wp_error( $res ) ? $res->get_error_message() : '' );
}

/**
 * Is a stored result broken? (404/410, server errors, unreachable.)
 *
 * @param array $row Index row.
 * @return bool
 */
function semrs_links_is_broken( $row ) {
	return ! empty( $row['checked'] ) && empty( $row['ok'] ) && ( 0 === (int) $row['status'] || (int) $row['status'] >= 400 ) && 401 !== (int) $row['status'] && 403 !== (int) $row['status'] && 429 !== (int) $row['status'];
}

/**
 * Background batch: check the links not checked for a week (20 per run).
 */
function semrs_links_cron() {
	if ( ! semrs_links_settings()['checker'] || ! semrs_links_pro() ) {
		return;
	}
	if ( ! get_option( 'semrs_links_indexed' ) ) {
		semrs_links_full_index();
	}
	$idx  = semrs_links_index();
	$due  = array_filter( $idx, function ( $row ) { return (int) $row['checked'] < time() - WEEK_IN_SECONDS; } );
	$done = 0;
	foreach ( array_keys( $due ) as $url ) {
		list( $code, $note )     = semrs_links_check_url( $url );
		$idx[ $url ]['status']   = $code;
		$idx[ $url ]['note']     = $note;
		$idx[ $url ]['checked']  = time();
		if ( ++$done >= 20 ) {
			break;
		}
	}
	update_option( 'semrs_links_index', $idx, false );
}
add_action( 'semrs_links_cron', 'semrs_links_cron' );
add_action(
	'init',
	function () {
		if ( semrs_links_settings()['checker'] && ! wp_next_scheduled( 'semrs_links_cron' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'semrs_links_cron' );
		}
	}
);

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		$idx    = semrs_links_index();
		$broken = count( array_filter( $idx, 'semrs_links_is_broken' ) );
		$label  = __( 'Link Assistant', 'semrs-pro' ) . ( $broken ? ' <span class="awaiting-mod">' . (int) $broken . '</span>' : '' );
		add_submenu_page( 'semrs', __( 'Link Assistant', 'semrs-pro' ), $label, 'edit_others_posts', 'semrs-links', 'semrs_links_page' );
	},
	19
);

/**
 * Settings, rules and actions.
 */
add_action(
	'admin_post_semrs_links',
	function () {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'semrs-pro' ) );
		}
		check_admin_referer( 'semrs_links' );
		$do  = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		$url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
		if ( 'save' === $do ) {
			$in    = wp_unslash( $_POST['semrs_links'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
			$rules = array();
			foreach ( preg_split( '/\r?\n/', (string) ( $in['rules'] ?? '' ) ) as $line ) {
				$bits = array_map( 'trim', explode( '|', $line ) );
				if ( 2 === count( $bits ) && '' !== $bits[0] && '' !== $bits[1] ) {
					$rules[] = array( sanitize_text_field( $bits[0] ), esc_url_raw( 0 === strpos( $bits[1], '/' ) ? home_url( $bits[1] ) : $bits[1] ) );
				}
			}
			update_option(
				'semrs_links',
				array(
					'auto'      => ! empty( $in['auto'] ),
					'focus'     => ! empty( $in['focus'] ),
					'max'       => max( 1, min( 10, absint( $in['max'] ?? 3 ) ) ),
					'rules'     => $rules,
					'checker'   => ! empty( $in['checker'] ),
					'externals' => ! empty( $in['externals'] ),
				),
				false
			);
		} elseif ( 'scan' === $do ) {
			semrs_links_full_index();
			semrs_links_cron();
		} elseif ( $url && in_array( $do, array( 'recheck', 'ignore' ), true ) ) {
			$idx = semrs_links_index();
			if ( isset( $idx[ $url ] ) ) {
				if ( 'ignore' === $do ) {
					$idx[ $url ]['ok'] = 1; // Marked as fine by a person.
				} else {
					list( $idx[ $url ]['status'], $idx[ $url ]['note'] ) = semrs_links_check_url( $url );
					$idx[ $url ]['checked']                             = time();
					unset( $idx[ $url ]['ok'] );
				}
				update_option( 'semrs_links_index', $idx, false );
			}
		} elseif ( $url && 'unlink' === $do ) {
			// Remove the link (keep its text) from every post it is on.
			$idx = semrs_links_index();
			foreach ( (array) ( $idx[ $url ]['posts'] ?? array() ) as $pid ) {
				if ( ! current_user_can( 'edit_post', $pid ) ) {
					continue;
				}
				$content = (string) get_post_field( 'post_content', $pid );
				$pattern = '#<a\s[^>]*href=["\']' . preg_quote( esc_attr( $url ), '#' ) . '(?:\#[^"\']*)?["\'][^>]*>(.*?)</a>#is';
				$new     = preg_replace( $pattern, '$1', $content );
				if ( $new !== $content ) {
					wp_update_post( array( 'ID' => $pid, 'post_content' => $new ) );
				}
			}
			unset( $idx[ $url ] );
			update_option( 'semrs_links_index', $idx, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=semrs-links&done=' . $do ) );
		exit;
	}
);

/**
 * One action button (small form).
 *
 * @param string $do    Action.
 * @param string $label Label.
 * @param string $url   Link URL.
 * @param string $class Button class.
 * @param string $warn  Confirm text.
 */
function semrs_links_button( $do, $label, $url = '', $class = 'button button-small', $warn = '' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline"' . ( $warn ? ' onsubmit="return confirm(' . esc_attr( wp_json_encode( $warn ) ) . ')"' : '' ) . '>';
	wp_nonce_field( 'semrs_links' );
	echo '<input type="hidden" name="action" value="semrs_links"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><input type="hidden" name="url" value="' . esc_attr( $url ) . '">';
	echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
}

/**
 * Screen.
 */
function semrs_links_page() {
	$s      = semrs_links_settings();
	$pro    = semrs_links_pro();
	$idx    = semrs_links_index();
	$broken = array_filter( $idx, 'semrs_links_is_broken' );
	$moved  = array_filter( $idx, function ( $r ) { return (int) $r['status'] >= 300 && (int) $r['status'] < 400; } );
	$todo   = count( array_filter( $idx, function ( $r ) { return ! $r['checked']; } ) );
	$rules  = implode( "\n", array_map( function ( $r ) { return $r[0] . ' | ' . str_replace( home_url(), '', $r[1] ); }, (array) $s['rules'] ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Link Assistant', 'semrs-pro' ); ?></h1>
		<?php if ( ! $pro ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Automatic internal links and the broken link checker unlock with a SEMRS Pro license.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Broken links', 'semrs-pro' ); ?> (<?php echo count( $broken ); ?>)</h2>
		<p class="description">
			<?php
			/* translators: 1: links found, 2: waiting to be checked */
			echo esc_html( sprintf( __( '%1$d links found in your published content; %2$d waiting for their first check. Links are checked in the background, 20 an hour, and again every week.', 'semrs-pro' ), count( $idx ), $todo ) );
			?>
		</p>
		<p><?php semrs_links_button( 'scan', __( 'Scan all content now', 'semrs-pro' ), '', 'button' ); ?></p>
		<?php if ( $broken ) : ?>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr><th><?php esc_html_e( 'Link', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Status', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Found on', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Actions', 'semrs-pro' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $broken as $url => $row ) : ?>
					<tr>
						<td style="word-break:break-all"><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $url ); ?></a></td>
						<td><?php echo $row['status'] ? (int) $row['status'] : esc_html__( 'No response', 'semrs-pro' ); ?><?php echo $row['note'] ? '<br><small>' . esc_html( mb_substr( $row['note'], 0, 80 ) ) . '</small>' : ''; ?></td>
						<td><?php foreach ( array_slice( (array) $row['posts'], 0, 5 ) as $pid ) : ?><a href="<?php echo esc_url( (string) get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a><br><?php endforeach; ?></td>
						<td>
							<?php semrs_links_button( 'recheck', __( 'Check again', 'semrs-pro' ), $url ); ?>
							<?php semrs_links_button( 'unlink', __( 'Remove link (keep text)', 'semrs-pro' ), $url, 'button button-small', __( 'Remove this link from every page it is on? The link text stays.', 'semrs-pro' ) ); ?>
							<?php semrs_links_button( 'ignore', __( 'Not broken', 'semrs-pro' ), $url, 'button-link' ); ?>
							<?php if ( wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) : ?>
								<br><a href="<?php echo esc_url( admin_url( 'admin.php?page=semrs-redirects&from=' . rawurlencode( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) ); ?>"><?php esc_html_e( 'Add a redirect', 'semrs-pro' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No broken links found.', 'semrs-pro' ); ?></p>
		<?php endif; ?>
		<?php if ( $moved ) : ?>
			<p class="description">
				<?php
				/* translators: %d: links */
				echo esc_html( sprintf( _n( '%d link redirects to another address. It works, but linking straight to the final address is a little faster.', '%d links redirect to another address. They work, but linking straight to the final address is a little faster.', count( $moved ), 'semrs-pro' ), count( $moved ) ) );
				?>
			</p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Settings', 'semrs-pro' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'semrs_links' ); ?>
			<input type="hidden" name="action" value="semrs_links"><input type="hidden" name="do" value="save">
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Automatic internal links', 'semrs-pro' ); ?></th><td>
					<label><input type="checkbox" name="semrs_links[auto]" value="1" <?php checked( $s['auto'] ); ?>> <?php esc_html_e( 'Link keywords in your content to the matching page', 'semrs-pro' ); ?></label><br>
					<label><input type="checkbox" name="semrs_links[focus]" value="1" <?php checked( $s['focus'] ); ?>> <?php esc_html_e( 'Use each post\'s focus keyword as a link to that post', 'semrs-pro' ); ?></label>
					<p class="description"><?php esc_html_e( 'Each keyword links once per page, never to the page itself, never in headings, buttons or existing links. Your saved content is not changed.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="semrs_links_max"><?php esc_html_e( 'Automatic links per page', 'semrs-pro' ); ?></label></th><td>
					<input type="number" min="1" max="10" id="semrs_links_max" name="semrs_links[max]" value="<?php echo esc_attr( (string) $s['max'] ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( '3 is a good maximum: enough to help readers and search engines without crowding the text.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="semrs_links_rules"><?php esc_html_e( 'Your keyword links', 'semrs-pro' ); ?></label></th><td>
					<textarea id="semrs_links_rules" name="semrs_links[rules]" rows="6" class="large-text code" placeholder="local seo, local search | /services/seo/&#10;website design | /services/web-design/"><?php echo esc_textarea( $rules ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One per line: keywords (comma separated) | page address. These win over focus keywords.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Broken link checker', 'semrs-pro' ); ?></th><td>
					<label><input type="checkbox" name="semrs_links[checker]" value="1" <?php checked( $s['checker'] ); ?>> <?php esc_html_e( 'Check the links in published content in the background', 'semrs-pro' ); ?></label><br>
					<label><input type="checkbox" name="semrs_links[externals]" value="1" <?php checked( $s['externals'] ); ?>> <?php esc_html_e( 'Check links to other websites too', 'semrs-pro' ); ?></label>
				</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
