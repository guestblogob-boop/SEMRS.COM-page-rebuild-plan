<?php
/**
 * SEMRS → SEO Audit: instant site checks (indexing, permalinks, HTTPS,
 * tagline, homepage description, sitemap…) and a content audit that scores
 * every published page in small batches and finds missing or duplicate
 * titles and descriptions, thin content and orphan pages.
 *
 * Everything runs on your own server.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'SEO Audit', 'semrs-core' ), __( 'SEO Audit', 'semrs-core' ), 'manage_options', 'semrs-seo-audit', 'semrs_seo_audit_page' );
	},
	19
);

/**
 * Site-wide checks: [status good|warn|bad, label, help, fix URL].
 *
 * @return array
 */
function semrs_seo_site_checks() {
	$s      = semrs_seo_settings();
	$c      = array();
	$front  = (int) get_option( 'page_on_front' );
	$hdesc  = $s['home_description'] || ( $front && semrs_seo_meta( $front, 'description' ) );
	$robots = ABSPATH . 'robots.txt';

	$c[] = get_option( 'blog_public' )
		? array( 'good', __( 'Search engines are allowed to index the site', 'semrs-core' ), '', '' )
		: array( 'bad', __( 'Search engines are blocked', 'semrs-core' ), __( 'Settings → Reading → "Discourage search engines" is ticked. Untick it when the site is ready.', 'semrs-core' ), admin_url( 'options-reading.php' ) );
	$c[] = get_option( 'permalink_structure' )
		? array( 'good', __( 'Readable URLs (permalinks) are on', 'semrs-core' ), '', '' )
		: array( 'bad', __( 'URLs look like ?p=123', 'semrs-core' ), __( 'Choose "Post name" under Settings → Permalinks.', 'semrs-core' ), admin_url( 'options-permalink.php' ) );
	$c[] = 0 === strpos( home_url(), 'https://' )
		? array( 'good', __( 'The site uses HTTPS', 'semrs-core' ), '', '' )
		: array( 'warn', __( 'The site address is not HTTPS', 'semrs-core' ), __( 'Ask your host for a free SSL certificate, then change both addresses under Settings → General to https://.', 'semrs-core' ), admin_url( 'options-general.php' ) );
	$tag = get_bloginfo( 'description' );
	$c[] = ( '' !== $tag && 'Just another WordPress site' !== $tag )
		? array( 'good', __( 'The site has its own tagline', 'semrs-core' ), '', '' )
		: array( 'warn', __( 'The tagline is empty or the WordPress default', 'semrs-core' ), __( 'Write a short line about what you do. It is used in titles and search results.', 'semrs-core' ), admin_url( 'options-general.php' ) );
	$c[] = $hdesc
		? array( 'good', __( 'The homepage has a meta description', 'semrs-core' ), '', '' )
		: array( 'warn', __( 'The homepage has no meta description', 'semrs-core' ), __( 'Write one under SEMRS → SEO → Titles & meta (120–155 characters).', 'semrs-core' ), admin_url( 'admin.php?page=semrs-seo' ) );
	$c[] = $s['sitemap']
		? array( 'good', __( 'XML sitemap is on (/wp-sitemap.xml)', 'semrs-core' ), __( 'Submit it once in Google Search Console and Bing Webmaster Tools.', 'semrs-core' ), home_url( '/wp-sitemap.xml' ) )
		: array( 'warn', __( 'XML sitemap is off', 'semrs-core' ), __( 'Search engines find new pages faster with a sitemap.', 'semrs-core' ), admin_url( 'admin.php?page=semrs-seo' ) );
	$c[] = ( $s['org_logo'] || get_theme_mod( 'custom_logo' ) )
		? array( 'good', __( 'Logo set for search results (schema)', 'semrs-core' ), '', '' )
		: array( 'warn', __( 'No logo for search results', 'semrs-core' ), __( 'Add a logo in the Customizer or under SEMRS → SEO → Business.', 'semrs-core' ), admin_url( 'admin.php?page=semrs-seo' ) );
	$c[] = get_option( 'site_icon' )
		? array( 'good', __( 'Site icon (favicon) set', 'semrs-core' ), '', '' )
		: array( 'warn', __( 'No site icon', 'semrs-core' ), __( 'Google shows it next to your results on phones. Add one under Customize → Site Identity.', 'semrs-core' ), admin_url( 'customize.php' ) );
	if ( file_exists( $robots ) ) {
		$body = (string) file_get_contents( $robots ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local file.
		$c[]  = preg_match( '/^\s*Disallow:\s*\/\s*$/mi', $body )
			? array( 'bad', __( 'robots.txt blocks the whole site', 'semrs-core' ), __( 'A robots.txt file in the site root has "Disallow: /". Remove that line (or the file) with your host\'s file manager.', 'semrs-core' ), home_url( '/robots.txt' ) )
			: array( 'warn', __( 'A physical robots.txt file is used', 'semrs-core' ), __( 'Extra robots.txt lines from SEMRS settings are ignored while that file exists.', 'semrs-core' ), home_url( '/robots.txt' ) );
	}
	if ( ! semrs_seo_active() ) {
		/* translators: %s: plugin name */
		$c[] = array( 'warn', sprintf( __( '%s is active, so SEMRS SEO output is paused', 'semrs-core' ), semrs_seo_conflict() ), __( 'Use one SEO plugin only, to avoid duplicate tags.', 'semrs-core' ), admin_url( 'plugins.php' ) );
	}
	$c[] = $s['verify_google']
		? array( 'good', __( 'Google Search Console verification code added', 'semrs-core' ), '', '' )
		: array( 'warn', __( 'No Google Search Console code', 'semrs-core' ), __( 'Skip this if you verified another way (DNS or Site Kit). Search Console shows how Google sees your site.', 'semrs-core' ), admin_url( 'admin.php?page=semrs-seo' ) );
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	$samp  = get_page_by_path( 'sample-page' );
	if ( ( $hello && 'publish' === $hello->post_status ) || ( $samp && 'publish' === $samp->post_status ) ) {
		$c[] = array( 'warn', __( 'WordPress sample content is still published', 'semrs-core' ), __( 'Delete or rewrite "Hello world!" and "Sample Page": thin pages hurt site quality (and AdSense approval).', 'semrs-core' ), admin_url( 'edit.php' ) );
	}
	return apply_filters( 'semrs_seo_site_checks', $c );
}

/**
 * IDs to audit, newest first (max 2000).
 *
 * @return int[]
 */
function semrs_seo_audit_ids() {
	return get_posts(
		array(
			'post_type'      => semrs_seo_public_types(),
			'post_status'    => 'publish',
			'posts_per_page' => 2000,
			'fields'         => 'ids',
			'has_password'   => false,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

/**
 * One audit batch (AJAX).
 */
function semrs_seo_audit_step() {
	check_ajax_referer( 'semrs_seo_audit', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( null, 403 );
	}
	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$ids    = semrs_seo_audit_ids();
	$batch  = array_slice( $ids, $offset, 10 );
	$rows   = 0 === $offset ? array() : (array) get_option( 'semrs_seo_audit_rows', array() );

	foreach ( $batch as $id ) {
		$post = get_post( $id );
		$d    = semrs_seo_analysis_input( $post );
		$r    = semrs_seo_analyze( $d );
		update_post_meta( $id, '_semrs_score', $r['score'] );
		$fails = array();
		foreach ( $r['checks'] as $c ) {
			if ( ! $c['ok'] && $c['weight'] >= 4 ) {
				$fails[] = $c['label'];
			}
		}
		$title       = '' !== $d['title'] ? $d['title'] : semrs_seo_fill( semrs_seo_settings()['tpl_singular'], array( 'title' => $post->post_title ) );
		$rows[ $id ] = array(
			't'  => get_the_title( $post ),
			'ty' => $post->post_type,
			's'  => $r['score'],
			'w'  => $r['words'],
			'st' => mb_strtolower( trim( $title ) ),
			'sd' => mb_strtolower( trim( $d['description'] ) ),
			'ni' => semrs_seo_is_noindex( $id ),
			'p'  => semrs_seo_link_path( get_permalink( $post ) ),
			'l'  => $r['links'],
			'f'  => $fails,
		);
	}
	update_option( 'semrs_seo_audit_rows', $rows, false );

	$next = $offset + count( $batch );
	if ( $next < count( $ids ) && $batch ) {
		wp_send_json_success( array( 'next' => $next, 'total' => count( $ids ) ) );
	}
	semrs_seo_audit_finish( $rows );
	wp_send_json_success( array( 'done' => true, 'total' => count( $ids ) ) );
}
add_action( 'wp_ajax_semrs_seo_audit_step', 'semrs_seo_audit_step' );

/**
 * Cross-page checks (duplicates, orphans) and the saved report.
 *
 * @param array $rows Rows keyed by post ID.
 */
function semrs_seo_audit_finish( $rows ) {
	$titles  = array();
	$descs   = array();
	$inbound = array();
	foreach ( $rows as $id => $r ) {
		$titles[ $r['st'] ][] = $id;
		if ( '' !== $r['sd'] ) {
			$descs[ $r['sd'] ][] = $id;
		}
		foreach ( $r['l'] as $path ) {
			if ( $path !== $r['p'] ) {
				$inbound[ $path ] = ( $inbound[ $path ] ?? 0 ) + 1;
			}
		}
	}
	$special = array_filter( array( (int) get_option( 'page_on_front' ), (int) get_option( 'page_for_posts' ) ) );
	$report  = array();
	$counts  = array_fill_keys( array( 'nodesc', 'duptitle', 'dupdesc', 'thin', 'orphan', 'low', 'noindex' ), 0 );
	$sum     = 0;
	foreach ( $rows as $id => $r ) {
		$tags = array();
		if ( $r['ni'] ) {
			$tags[] = 'noindex';
		} else {
			if ( '' === $r['sd'] ) {
				$tags[] = 'nodesc';
			}
			if ( count( $titles[ $r['st'] ] ) > 1 ) {
				$tags[] = 'duptitle';
			}
			if ( '' !== $r['sd'] && count( $descs[ $r['sd'] ] ) > 1 ) {
				$tags[] = 'dupdesc';
			}
			if ( $r['w'] < ( 'page' === $r['ty'] ? 200 : 300 ) ) {
				$tags[] = 'thin';
			}
			if ( ! in_array( (int) $id, $special, true ) && empty( $inbound[ $r['p'] ] ) ) {
				$tags[] = 'orphan';
			}
			if ( $r['s'] < 50 ) {
				$tags[] = 'low';
			}
		}
		foreach ( $tags as $t ) {
			$counts[ $t ]++;
		}
		$sum          += $r['s'];
		$report[ $id ] = array(
			't'    => $r['t'],
			's'    => $r['s'],
			'w'    => $r['w'],
			'tags' => $tags,
			'f'    => $r['f'],
			'in'   => $inbound[ $r['p'] ] ?? 0,
		);
	}
	uasort( $report, function ( $a, $b ) { return $a['s'] - $b['s']; } );
	update_option(
		'semrs_seo_audit',
		array(
			'time'   => time(),
			'total'  => count( $rows ),
			'avg'    => $rows ? (int) round( $sum / count( $rows ) ) : 0,
			'counts' => $counts,
			'rows'   => $report,
		),
		false
	);
	delete_option( 'semrs_seo_audit_rows' );
}

/**
 * Audit page.
 */
function semrs_seo_audit_page() {
	$checks = semrs_seo_site_checks();
	$audit  = get_option( 'semrs_seo_audit' );
	$labels = array(
		'nodesc'   => __( 'Missing meta description', 'semrs-core' ),
		'duptitle' => __( 'Duplicate SEO title', 'semrs-core' ),
		'dupdesc'  => __( 'Duplicate description', 'semrs-core' ),
		'thin'     => __( 'Thin content', 'semrs-core' ),
		'orphan'   => __( 'Orphan (no internal links in)', 'semrs-core' ),
		'low'      => __( 'Score under 50', 'semrs-core' ),
		'noindex'  => __( 'Noindex', 'semrs-core' ),
	);
	$bad  = count( array_filter( $checks, function ( $c ) { return 'bad' === $c[0]; } ) );
	$warn = count( array_filter( $checks, function ( $c ) { return 'warn' === $c[0]; } ) );
	?>
	<div class="wrap semrs-audit">
		<h1><?php esc_html_e( 'SEO Audit', 'semrs-core' ); ?></h1>
		<style>
			.semrs-audit .card{max-width:none}
			.semrs-site-checks{list-style:none;margin:0;padding:0}.semrs-site-checks li{display:grid;grid-template-columns:22px 1fr;gap:4px 8px;padding:8px 0;border-bottom:1px solid #f0f0f1}
			.semrs-site-checks .dot{width:12px;height:12px;border-radius:50%;margin-top:4px}.dot.good{background:#00813a}.dot.warn{background:#dba617}.dot.bad{background:#b32d2e}
			.semrs-site-checks p{grid-column:2;margin:0;color:#50575e}
			.semrs-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin:14px 0}
			.semrs-stat{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;text-align:left;cursor:pointer;font:inherit}
			.semrs-stat strong{display:block;font-size:24px;line-height:1.2}.semrs-stat.is-active{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}
			.semrs-score{display:inline-block;min-width:30px;padding:2px 6px;border-radius:12px;text-align:center;font-weight:600;font-size:12px;color:#fff;background:#8c8f94}.semrs-score--good{background:#00813a}.semrs-score--ok{background:#b26200}.semrs-score--bad{background:#b32d2e}
			.semrs-tag{display:inline-block;background:#f0f0f1;border-radius:4px;padding:1px 6px;margin:2px 2px 0 0;font-size:12px}
			.semrs-audit-table td ul{margin:4px 0 0 16px;list-style:disc;color:#50575e}
			.semrs-progress{height:8px;background:#f0f0f1;border-radius:8px;overflow:hidden;max-width:420px;margin:8px 0}.semrs-progress span{display:block;height:100%;width:0;background:#2271b1;transition:width .2s}
		</style>

		<div class="card">
			<h2><?php esc_html_e( 'Site checks', 'semrs-core' ); ?></h2>
			<p>
			<?php
			if ( ! $bad && ! $warn ) {
				esc_html_e( 'Everything looks good.', 'semrs-core' );
			} else {
				/* translators: 1: critical issues, 2: warnings */
				echo esc_html( sprintf( __( '%1$d critical, %2$d to improve.', 'semrs-core' ), $bad, $warn ) );
			}
			?>
			</p>
			<ul class="semrs-site-checks">
				<?php
				usort( $checks, function ( $a, $b ) { $o = array( 'bad' => 0, 'warn' => 1, 'good' => 2 ); return $o[ $a[0] ] - $o[ $b[0] ]; } );
				foreach ( $checks as $c ) :
					?>
					<li><span class="dot <?php echo esc_attr( $c[0] ); ?>"></span><strong><?php echo esc_html( $c[1] ); ?></strong>
						<?php if ( $c[2] ) : ?>
							<p><?php echo esc_html( $c[2] ); ?> <?php if ( $c[3] ) : ?><a href="<?php echo esc_url( $c[3] ); ?>"><?php echo 'good' === $c[0] ? esc_html__( 'Open', 'semrs-core' ) : esc_html__( 'Fix', 'semrs-core' ); ?></a><?php endif; ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="card">
			<h2><?php esc_html_e( 'Content audit', 'semrs-core' ); ?></h2>
			<p><?php esc_html_e( 'Scores every published post and page (up to 2,000, newest first) with the same checks as the SEO box in the editor, then looks across pages for duplicates and orphans. It runs in small steps; keep this tab open.', 'semrs-core' ); ?></p>
			<p><button type="button" class="button button-primary" id="semrs-audit-run"><?php echo $audit ? esc_html__( 'Run the audit again', 'semrs-core' ) : esc_html__( 'Run the content audit', 'semrs-core' ); ?></button>
			<?php if ( $audit ) : ?>
				<span class="description">
				<?php
				/* translators: %s: time ago */
				echo esc_html( sprintf( __( 'Last run %s ago.', 'semrs-core' ), human_time_diff( $audit['time'] ) ) );
				?>
				</span>
			<?php endif; ?></p>
			<div class="semrs-progress" hidden><span></span></div><p id="semrs-audit-status" class="description"></p>

			<?php if ( $audit ) : ?>
				<div class="semrs-stats">
					<button type="button" class="semrs-stat is-active" data-filter="all"><strong><span class="semrs-score semrs-score--<?php echo esc_attr( semrs_seo_score_class( $audit['avg'] ) ); ?>"><?php echo (int) $audit['avg']; ?></span></strong><?php echo esc_html( sprintf( /* translators: %d: pages */ __( 'Average score, %d pages', 'semrs-core' ), $audit['total'] ) ); ?></button>
					<?php foreach ( $labels as $k => $label ) : ?>
						<button type="button" class="semrs-stat" data-filter="<?php echo esc_attr( $k ); ?>"><strong><?php echo (int) $audit['counts'][ $k ]; ?></strong><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</div>
				<table class="widefat striped semrs-audit-table">
					<thead><tr><th><?php esc_html_e( 'Page', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Score', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Words', 'semrs-core' ); ?></th><th><?php esc_html_e( 'Links in', 'semrs-core' ); ?></th><th><?php esc_html_e( 'What to fix', 'semrs-core' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $audit['rows'] as $id => $r ) : ?>
						<tr data-tags="<?php echo esc_attr( implode( ' ', $r['tags'] ) ); ?>">
							<td><strong><a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><?php echo esc_html( '' !== $r['t'] ? $r['t'] : __( '(no title)', 'semrs-core' ) ); ?></a></strong> · <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'semrs-core' ); ?></a></td>
							<td><span class="semrs-score semrs-score--<?php echo esc_attr( semrs_seo_score_class( $r['s'] ) ); ?>"><?php echo (int) $r['s']; ?></span></td>
							<td><?php echo (int) $r['w']; ?></td>
							<td><?php echo (int) $r['in']; ?></td>
							<td>
								<?php foreach ( $r['tags'] as $t ) : ?><span class="semrs-tag"><?php echo esc_html( $labels[ $t ] ); ?></span><?php endforeach; ?>
								<?php if ( $r['f'] && ! in_array( 'noindex', $r['tags'], true ) ) : ?><ul><?php foreach ( array_slice( $r['f'], 0, 4 ) as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul><?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( 'Orphan pages get no links from your other posts and pages (menus are not counted). Link to them from related articles so visitors and Google can find them.', 'semrs-core' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<script>
	(function () {
		var run = document.getElementById('semrs-audit-run'), bar = document.querySelector('.semrs-progress'), status = document.getElementById('semrs-audit-status');
		function step(offset) {
			var f = new FormData();
			f.append('action', 'semrs_seo_audit_step');
			f.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'semrs_seo_audit' ) ); ?>);
			f.append('offset', offset);
			fetch(ajaxurl, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
				if (!r.success) { throw new Error(); }
				if (r.data.done) { bar.firstChild.style.width = '100%'; location.reload(); return; }
				bar.firstChild.style.width = Math.round(r.data.next / r.data.total * 100) + '%';
				status.textContent = r.data.next + ' / ' + r.data.total;
				step(r.data.next);
			}).catch(function () { run.disabled = false; status.textContent = <?php echo wp_json_encode( __( 'The audit stopped. Click the button to try again.', 'semrs-core' ) ); ?>; });
		}
		run.addEventListener('click', function () { run.disabled = true; bar.hidden = false; status.textContent = <?php echo wp_json_encode( __( 'Starting…', 'semrs-core' ) ); ?>; step(0); });
		document.querySelectorAll('.semrs-stat').forEach(function (b) {
			b.addEventListener('click', function () {
				document.querySelectorAll('.semrs-stat').forEach(function (x) { x.classList.toggle('is-active', x === b); });
				var f = b.dataset.filter;
				document.querySelectorAll('.semrs-audit-table tbody tr').forEach(function (tr) {
					tr.hidden = 'all' !== f && (' ' + tr.dataset.tags + ' ').indexOf(' ' + f + ' ') < 0;
				});
			});
		});
	})();
	</script>
	<?php
}
