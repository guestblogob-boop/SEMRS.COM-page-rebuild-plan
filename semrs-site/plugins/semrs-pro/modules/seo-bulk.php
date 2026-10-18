<?php
/**
 * Bulk SEO editor (Pro): edit SEO titles, meta descriptions, focus keywords
 * and noindex for many posts on one screen. Needs the SEMRS Core SEO module.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Bulk SEO editor', 'semrs-pro' ), __( 'Bulk SEO', 'semrs-pro' ), 'edit_others_posts', 'semrs-seo-bulk', 'semrs_pro_seo_bulk_page' );
	},
	19
);

/**
 * Save the edited rows.
 */
function semrs_pro_seo_bulk_save() {
	check_admin_referer( 'semrs_seo_bulk' );
	if ( ! function_exists( 'semrs_seo_is_noindex' ) ) {
		wp_die( esc_html__( 'Switch on the SEO module in SEMRS → Features first.', 'semrs-pro' ) );
	}
	$rows  = isset( $_POST['rows'] ) && is_array( $_POST['rows'] ) ? wp_unslash( $_POST['rows'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
	$saved = 0;
	foreach ( $rows as $id => $row ) {
		$id = absint( $id );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! is_array( $row ) ) {
			continue;
		}
		$fields = array(
			'title'       => sanitize_text_field( $row['title'] ?? '' ),
			'description' => sanitize_textarea_field( $row['description'] ?? '' ),
			'focus'       => sanitize_text_field( $row['focus'] ?? '' ),
		);
		$changed = false;
		foreach ( $fields as $key => $value ) {
			$old = (string) get_post_meta( $id, '_semrs_' . $key, true );
			if ( $old === $value ) {
				continue;
			}
			$changed = true;
			if ( '' === $value ) {
				delete_post_meta( $id, '_semrs_' . $key );
			} else {
				update_post_meta( $id, '_semrs_' . $key, $value );
			}
		}
		$noindex = empty( $row['noindex'] ) ? '0' : '1';
		if ( (bool) semrs_seo_is_noindex( $id ) !== ( '1' === $noindex ) ) {
			update_post_meta( $id, '_semrs_noindex', $noindex );
			$changed = true;
		}
		if ( $changed ) {
			$saved++;
			if ( function_exists( 'semrs_seo_store_score' ) ) {
				semrs_seo_store_score( $id, get_post( $id ) );
			}
		}
	}
	if ( $saved && function_exists( 'semrs_cache_purge_all' ) ) {
		semrs_cache_purge_all();
	}
	delete_transient( 'semrs_llms' );
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : admin_url( 'admin.php?page=semrs-seo-bulk' );
	wp_safe_redirect( add_query_arg( 'saved', $saved, $back ) );
	exit;
}
add_action( 'admin_post_semrs_seo_bulk_save', 'semrs_pro_seo_bulk_save' );

/**
 * Screen.
 */
function semrs_pro_seo_bulk_page() {
	echo '<div class="wrap"><h1>' . esc_html__( 'Bulk SEO editor', 'semrs-pro' ) . '</h1>';
	if ( ! function_exists( 'semrs_seo_meta' ) ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Switch on the SEO module in SEMRS → Features first.', 'semrs-pro' ) . '</p></div></div>';
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- read-only filters.
	$types   = semrs_seo_public_types();
	$type    = isset( $_GET['type'] ) && in_array( $_GET['type'], $types, true ) ? sanitize_key( $_GET['type'] ) : 'post';
	$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$missing = ! empty( $_GET['missing'] );
	$paged   = max( 1, absint( $_GET['paged'] ?? 1 ) );
	// phpcs:enable
	$args = array(
		'post_type'      => $type,
		'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
		'posts_per_page' => 20,
		'paged'          => $paged,
		's'              => $search,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	if ( $missing ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery -- admin screen.
			array(
				'key'     => '_semrs_description',
				'compare' => 'NOT EXISTS',
			),
		);
	}
	$q = new WP_Query( $args );
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		/* translators: %d: posts */
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( '%d post updated.', '%d posts updated.', absint( $_GET['saved'] ), 'semrs-pro' ), absint( $_GET['saved'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
	}
	?>
	<style>
		.semrs-bulk td{vertical-align:top}.semrs-bulk input[type=text],.semrs-bulk textarea{width:100%}
		.semrs-bulk .n{font-size:11px;color:#646970}.semrs-bulk .n.bad{color:#b32d2e;font-weight:600}
		.semrs-bulk tr.is-changed{background:#fcf9e8!important}
		.semrs-score{display:inline-block;min-width:30px;padding:2px 6px;border-radius:12px;text-align:center;font-weight:600;font-size:12px;color:#fff;background:#8c8f94}.semrs-score--good{background:#00813a}.semrs-score--ok{background:#b26200}.semrs-score--bad{background:#b32d2e}
	</style>
	<form method="get" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
		<input type="hidden" name="page" value="semrs-seo-bulk">
		<select name="type">
			<?php foreach ( $types as $t ) : ?>
				<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $type, $t ); ?>><?php echo esc_html( get_post_type_object( $t )->labels->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search titles', 'semrs-pro' ); ?>">
		<label><input type="checkbox" name="missing" value="1" <?php checked( $missing ); ?>> <?php esc_html_e( 'Only posts without their own meta description', 'semrs-pro' ); ?></label>
		<button class="button"><?php esc_html_e( 'Filter', 'semrs-pro' ); ?></button>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'semrs_seo_bulk' ); ?>
		<input type="hidden" name="action" value="semrs_seo_bulk_save">
		<input type="hidden" name="back" value="<?php echo esc_attr( remove_query_arg( 'saved', isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ); ?>">
		<table class="widefat striped semrs-bulk">
			<thead><tr><th style="width:18%"><?php esc_html_e( 'Post', 'semrs-pro' ); ?></th><th style="width:14%"><?php esc_html_e( 'Focus keyword', 'semrs-pro' ); ?></th><th style="width:26%"><?php esc_html_e( 'SEO title', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Meta description', 'semrs-pro' ); ?></th><th style="width:70px"><?php esc_html_e( 'Noindex', 'semrs-pro' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $q->posts ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'Nothing found.', 'semrs-pro' ); ?></td></tr>
			<?php endif; ?>
			<?php
			foreach ( $q->posts as $p ) :
				$own   = function ( $k ) use ( $p ) {
					return (string) get_post_meta( $p->ID, '_semrs_' . $k, true );
				};
				$score = get_post_meta( $p->ID, '_semrs_score', true );
				$def   = semrs_seo_fill( semrs_seo_settings()['tpl_singular'], array( 'title' => $p->post_title ) );
				$name  = 'rows[' . $p->ID . ']';
				?>
				<tr>
					<td><strong><a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ? get_the_title( $p ) : __( '(no title)', 'semrs-pro' ) ); ?></a></strong><br>
						<span class="semrs-score semrs-score--<?php echo esc_attr( semrs_seo_score_class( $score ) ); ?>"><?php echo '' === $score ? '–' : (int) $score; ?></span>
						<?php echo 'publish' !== $p->post_status ? ' <em>' . esc_html( get_post_status_object( $p->post_status )->label ) . '</em>' : ''; ?></td>
					<td><input type="text" name="<?php echo esc_attr( $name ); ?>[focus]" value="<?php echo esc_attr( $own( 'focus' ) ); ?>" placeholder="<?php echo esc_attr( semrs_seo_meta( $p->ID, 'focus' ) ); ?>"></td>
					<td><input type="text" data-max="60" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $own( 'title' ) ); ?>" placeholder="<?php echo esc_attr( semrs_seo_meta( $p->ID, 'title' ) ? semrs_seo_meta( $p->ID, 'title' ) : $def ); ?>"><span class="n"></span></td>
					<td><textarea rows="2" data-max="155" name="<?php echo esc_attr( $name ); ?>[description]" placeholder="<?php echo esc_attr( semrs_seo_meta( $p->ID, 'description' ) ); ?>"><?php echo esc_textarea( $own( 'description' ) ); ?></textarea><span class="n"></span></td>
					<td><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[noindex]" value="1" <?php checked( semrs_seo_is_noindex( $p->ID ) ); ?> aria-label="<?php esc_attr_e( 'Noindex', 'semrs-pro' ); ?>"></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Grey text is what is used now (from the title template or data imported from Rank Math, Yoast or AIOSEO). Leave a field empty to keep using it. Scores update when you save.', 'semrs-pro' ); ?></p>
		<?php submit_button( __( 'Save changes', 'semrs-pro' ) ); ?>
	</form>
	<?php
	$links = paginate_links(
		array(
			'base'    => add_query_arg( 'paged', '%#%' ),
			'format'  => '',
			'current' => $paged,
			'total'   => max( 1, $q->max_num_pages ),
		)
	);
	if ( $links ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
	}
	?>
	<script>
	(function () {
		document.querySelectorAll('.semrs-bulk [data-max]').forEach(function (el) {
			var n = el.nextElementSibling, max = +el.dataset.max;
			function upd() { var len = (el.value || el.placeholder).length; n.textContent = len + ' / ' + max; n.className = 'n' + (len > max ? ' bad' : ''); }
			el.addEventListener('input', function () { upd(); el.closest('tr').classList.add('is-changed'); });
			upd();
		});
		document.querySelectorAll('.semrs-bulk input').forEach(function (el) { el.addEventListener('change', function () { el.closest('tr').classList.add('is-changed'); }); });
	})();
	</script>
	</div>
	<?php
}
