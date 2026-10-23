<?php
/**
 * Copy Rank Math SEO data into SEMRS's own fields, so Rank Math can be
 * removed without losing anything: post and term titles, descriptions,
 * focus keywords, canonicals, social images, noindex/nofollow, the primary
 * category and the homepage title and description.
 *
 * Safe to run any number of times: a SEMRS value that is already set is
 * never overwritten, and Rank Math's own data is never changed.
 * Rank Math redirects are imported on SEMRS → Redirects.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * IDs of posts (any type) with Rank Math data.
 *
 * @param int $offset Offset.
 * @param int $limit  Limit.
 * @return int[]
 */
function semrs_seo_rm_post_ids( $offset = 0, $limit = 100 ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off import scan.
	return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key IN ('rank_math_title','rank_math_description','rank_math_focus_keyword','rank_math_canonical_url','rank_math_facebook_image','rank_math_robots','rank_math_primary_category') AND pm.meta_value <> '' AND p.post_type <> 'revision' ORDER BY pm.post_id LIMIT %d, %d", $offset, $limit ) ) );
}

/**
 * Number of posts with Rank Math data.
 *
 * @return int
 */
function semrs_seo_rm_post_count() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off import scan.
	return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key IN ('rank_math_title','rank_math_description','rank_math_focus_keyword','rank_math_canonical_url','rank_math_facebook_image','rank_math_robots','rank_math_primary_category') AND pm.meta_value <> '' AND p.post_type <> 'revision'" );
}

/**
 * Copy one post's Rank Math values into empty SEMRS fields.
 *
 * @param int $id Post ID.
 * @return int Fields copied.
 */
function semrs_seo_rm_import_post( $id ) {
	$copied = 0;
	$clean  = array(
		'title'       => 'sanitize_text_field',
		'description' => 'sanitize_textarea_field',
		'focus'       => 'sanitize_text_field',
		'canonical'   => 'esc_url_raw',
		'og_image'    => 'esc_url_raw',
	);
	foreach ( $clean as $field => $fn ) {
		if ( '' !== (string) get_post_meta( $id, '_semrs_' . $field, true ) ) {
			continue;
		}
		$value = call_user_func( $fn, (string) semrs_seo_rank_math_meta( $id, $field ) ); // Variables such as %title% are filled in.
		if ( '' !== trim( $value ) ) {
			update_post_meta( $id, '_semrs_' . $field, $value );
			++$copied;
		}
	}
	foreach ( array( 'noindex', 'nofollow' ) as $flag ) {
		if ( ! metadata_exists( 'post', $id, '_semrs_' . $flag ) && semrs_seo_rank_math_meta( $id, $flag ) ) {
			update_post_meta( $id, '_semrs_' . $flag, '1' );
			++$copied;
		}
	}
	$primary = (int) get_post_meta( $id, 'rank_math_primary_category', true );
	if ( $primary && ! get_post_meta( $id, '_semrs_primary_category', true ) ) {
		update_post_meta( $id, '_semrs_primary_category', $primary );
		++$copied;
	}
	return $copied;
}

/**
 * Copy term titles, descriptions and focus keywords, and the homepage
 * title and description from Rank Math's settings.
 *
 * @return int Fields copied.
 */
function semrs_seo_rm_import_terms_and_home() {
	$copied = 0;
	$terms  = get_terms(
		array(
			'hide_empty' => false,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off import.
				'relation' => 'OR',
				array( 'key' => 'rank_math_title', 'compare' => 'EXISTS' ),
				array( 'key' => 'rank_math_description', 'compare' => 'EXISTS' ),
				array( 'key' => 'rank_math_focus_keyword', 'compare' => 'EXISTS' ),
			),
		)
	);
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		foreach ( array( 'title' => 'rank_math_title', 'description' => 'rank_math_description', 'focus' => 'rank_math_focus_keyword' ) as $field => $key ) {
			if ( '' !== (string) get_term_meta( $term->term_id, '_semrs_' . $field, true ) ) {
				continue;
			}
			$value = (string) get_term_meta( $term->term_id, $key, true );
			$value = 'focus' === $field ? trim( strtok( $value, ',' ) ?: '' ) : semrs_seo_rm_vars( strtr( $value, array( '%term%' => $term->name, '%term_description%' => wp_strip_all_tags( $term->description ) ) ) );
			$value = 'description' === $field ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
			if ( '' !== trim( $value ) ) {
				update_term_meta( $term->term_id, '_semrs_' . $field, $value );
				++$copied;
			}
		}
	}

	$rm  = (array) get_option( 'rank-math-options-titles', array() );
	$seo = (array) get_option( 'semrs_seo', array() );
	foreach ( array( 'home_title' => 'homepage_title', 'home_description' => 'homepage_description' ) as $mine => $theirs ) {
		$value = sanitize_text_field( semrs_seo_rm_vars( (string) ( $rm[ $theirs ] ?? '' ) ) );
		if ( '' === trim( (string) ( $seo[ $mine ] ?? '' ) ) && '' !== $value ) {
			$seo[ $mine ] = $value;
			++$copied;
		}
	}
	if ( $copied ) {
		update_option( 'semrs_seo', $seo );
	}
	return $copied;
}

/**
 * AJAX: one batch of 100 posts; the terms and homepage run with the last batch.
 */
function semrs_seo_rm_import_step() {
	check_ajax_referer( 'semrs_seo_rm_import', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}
	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$ids    = semrs_seo_rm_post_ids( $offset, 100 );
	$copied = 0;
	foreach ( $ids as $id ) {
		$copied += semrs_seo_rm_import_post( $id );
	}
	$done = count( $ids ) < 100;
	if ( $done ) {
		$copied += semrs_seo_rm_import_terms_and_home();
	}
	wp_send_json_success(
		array(
			'next'   => $offset + count( $ids ),
			'copied' => $copied,
			'done'   => $done,
		)
	);
}
add_action( 'wp_ajax_semrs_seo_rm_import', 'semrs_seo_rm_import_step' );

/**
 * Import box on SEMRS → SEO (only when Rank Math data exists).
 */
function semrs_seo_rm_import_box() {
	$total = semrs_seo_rm_post_count();
	if ( ! $total && ! get_option( 'rank-math-options-titles' ) ) {
		return;
	}
	?>
	<div class="card" style="max-width:820px">
		<h2><?php esc_html_e( 'Copy Rank Math data into SEMRS', 'semrs-core' ); ?></h2>
		<p><?php echo esc_html( sprintf( /* translators: %d: number of posts */ __( '%d posts and pages have Rank Math SEO data. Copy their titles, descriptions, focus keywords, canonicals, social images, noindex settings and primary categories, plus category titles and the homepage title, into SEMRS fields. Values you already set in SEMRS are kept, and Rank Math\'s data is not changed. After this, Rank Math can be deleted without losing SEO data.', 'semrs-core' ), $total ) ); ?></p>
		<p><button type="button" class="button button-primary" id="semrs-rm-import"><?php esc_html_e( 'Copy Rank Math data', 'semrs-core' ); ?></button> <span id="semrs-rm-status" aria-live="polite"></span></p>
	</div>
	<script>
	(function () {
		var btn = document.getElementById('semrs-rm-import'), out = document.getElementById('semrs-rm-status'), total = <?php echo (int) $total; ?>, copied = 0;
		btn.addEventListener('click', function () {
			btn.disabled = true;
			copied = 0;
			(function step(offset) {
				var f = new FormData();
				f.append('action', 'semrs_seo_rm_import');
				f.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'semrs_seo_rm_import' ) ); ?>);
				f.append('offset', offset);
				fetch(ajaxurl, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
					if (!r.success) { throw new Error(); }
					copied += r.data.copied;
					out.textContent = r.data.done
						? <?php echo wp_json_encode( __( 'Done: %d fields copied.', 'semrs-core' ) ); ?>.replace('%d', copied)
						: Math.min(r.data.next, total) + ' / ' + total;
					if (!r.data.done) { step(r.data.next); } else { btn.disabled = false; }
				}).catch(function () { out.textContent = <?php echo wp_json_encode( __( 'Something went wrong. Try again: finished posts are skipped.', 'semrs-core' ) ); ?>; btn.disabled = false; });
			})(0);
		});
	})();
	</script>
	<?php
}
