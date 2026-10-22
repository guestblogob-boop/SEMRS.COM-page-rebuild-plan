<?php
/**
 * Image library conversion (Pro): makes WebP (or AVIF) copies of every
 * existing JPEG and PNG in the media library, all sizes, in small batches,
 * and serves the copies on the front end. Originals are never changed, so
 * "Remove copies" puts everything back. Needs the SEMRS Core Images module
 * (format and quality come from its settings).
 *
 * Copies sit next to the original as photo.jpg.webp, so two files with the
 * same name in different formats never collide.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_images_settings' ) ) {
	return; // Core Images module is off.
}

/**
 * Extension of the copies ('webp' or 'avif'), or '' when unsupported.
 *
 * @return string
 */
function semrs_imglib_ext() {
	$format = semrs_images_settings()['format'];
	$format = in_array( $format, array( 'webp', 'avif' ), true ) ? $format : 'webp';
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/' . $format ) ) ) {
		return $format;
	}
	return 'avif' === $format && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? 'webp' : '';
}

/**
 * Absolute paths of an attachment's original and every size.
 *
 * @param int $id Attachment ID.
 * @return string[]
 */
function semrs_imglib_files( $id ) {
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) ) {
		return array();
	}
	$dir   = dirname( $file );
	$files = array( $file );
	$meta  = wp_get_attachment_metadata( $id );
	if ( ! empty( $meta['original_image'] ) ) {
		$files[] = $dir . '/' . $meta['original_image'];
	}
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$files[] = $dir . '/' . $size['file'];
		}
	}
	return array_values( array_unique( array_filter( $files, 'file_exists' ) ) );
}

/**
 * Convert one attachment. Returns bytes saved (can be 0).
 *
 * @param int $id Attachment ID.
 * @return int
 */
function semrs_imglib_convert( $id ) {
	$ext = semrs_imglib_ext();
	if ( ! $ext || ! in_array( get_post_mime_type( $id ), array( 'image/jpeg', 'image/png' ), true ) ) {
		update_post_meta( $id, '_semrs_imglib', 'skip' );
		return 0;
	}
	$saved = 0;
	foreach ( semrs_imglib_files( $id ) as $file ) {
		$copy = $file . '.' . $ext;
		if ( file_exists( $copy ) ) {
			continue;
		}
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			continue;
		}
		$editor->set_quality( semrs_images_settings()['quality'] );
		$out = $editor->save( $copy, 'image/' . $ext );
		if ( is_wp_error( $out ) || ! file_exists( $copy ) ) {
			continue;
		}
		clearstatcache();
		if ( filesize( $copy ) >= filesize( $file ) ) {
			wp_delete_file( $copy ); // Not smaller: keep serving the original.
			continue;
		}
		$saved += filesize( $file ) - filesize( $copy );
	}
	update_post_meta( $id, '_semrs_imglib', $ext );
	return $saved;
}

/**
 * Remove an attachment's copies.
 *
 * @param int $id Attachment ID.
 */
function semrs_imglib_restore( $id ) {
	foreach ( semrs_imglib_files( $id ) as $file ) {
		foreach ( array( 'webp', 'avif' ) as $ext ) {
			if ( file_exists( $file . '.' . $ext ) ) {
				wp_delete_file( $file . '.' . $ext );
			}
		}
	}
	delete_post_meta( $id, '_semrs_imglib' );
}

/**
 * Copies are deleted with their attachment.
 */
add_action( 'delete_attachment', 'semrs_imglib_restore' );

/**
 * Point upload URLs at their copies in the final HTML (src, srcset, CSS).
 *
 * @param string $html Page HTML.
 * @return string
 */
function semrs_imglib_html( $html ) {
	if ( is_admin() || ! get_option( 'semrs_imglib_on' ) ) {
		return $html;
	}
	$up   = wp_get_upload_dir();
	$base = preg_quote( set_url_scheme( $up['baseurl'], 'https' ), '#' );
	$base = str_replace( 'https', 'https?', $base );
	$dir  = $up['basedir'];
	$seen = array();
	return preg_replace_callback(
		'#(' . $base . ')(/[^"\'\s(),]+?\.(?:jpe?g|png))(?=["\'\s),])#i',
		function ( $m ) use ( $dir, &$seen ) {
			$rel = rawurldecode( $m[2] );
			if ( ! isset( $seen[ $rel ] ) ) {
				$seen[ $rel ] = '';
				foreach ( array( 'avif', 'webp' ) as $ext ) {
					if ( false === strpos( $rel, '..' ) && file_exists( $dir . $rel . '.' . $ext ) ) {
						$seen[ $rel ] = '.' . $ext;
						break;
					}
				}
			}
			return $m[1] . $m[2] . $seen[ $rel ];
		},
		$html
	);
}
add_filter( 'semrs_speed_html', 'semrs_imglib_html', 30 );

if ( ! is_admin() ) {
	return;
}

/**
 * Counts for the screen.
 *
 * @return array{total:int,done:int}
 */
function semrs_imglib_counts() {
	global $wpdb;
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg','image/png')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$done  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_semrs_imglib' WHERE p.post_type = 'attachment' AND p.post_mime_type IN ('image/jpeg','image/png')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return array( 'total' => $total, 'done' => $done );
}

/**
 * One batch of 5 attachments (convert or restore).
 */
function semrs_imglib_ajax() {
	check_ajax_referer( 'semrs_imglib' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}
	$restore = ! empty( $_POST['restore'] );
	$query   = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => array( 'image/jpeg', 'image/png' ),
		'posts_per_page' => 5,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_semrs_imglib',
				'compare' => $restore ? 'EXISTS' : 'NOT EXISTS',
			),
		),
	);
	$ids   = get_posts( $query );
	$saved = 0;
	foreach ( $ids as $id ) {
		if ( $restore ) {
			semrs_imglib_restore( $id );
		} else {
			$saved += semrs_imglib_convert( $id );
		}
	}
	if ( ! $restore ) {
		update_option( 'semrs_imglib_on', 1, true );
		update_option( 'semrs_imglib_saved', (int) get_option( 'semrs_imglib_saved', 0 ) + $saved, false );
	} elseif ( ! $ids ) {
		delete_option( 'semrs_imglib_on' );
		delete_option( 'semrs_imglib_saved' );
	}
	if ( function_exists( 'semrs_cache_purge_all' ) && ! $ids ) {
		semrs_cache_purge_all();
	}
	wp_send_json_success( array_merge( semrs_imglib_counts(), array( 'more' => (bool) $ids, 'saved' => size_format( (int) get_option( 'semrs_imglib_saved', 0 ) ) ) ) );
}
add_action( 'wp_ajax_semrs_imglib', 'semrs_imglib_ajax' );

/**
 * Section on SEMRS → Images.
 */
add_action(
	'semrs_images_page_after',
	function () {
		$c   = semrs_imglib_counts();
		$ext = semrs_imglib_ext();
		?>
		<h2><?php esc_html_e( 'Existing media library (SEMRS Pro)', 'semrs-pro' ); ?></h2>
		<?php if ( ! $ext ) : ?>
			<p><?php esc_html_e( 'This server cannot create WebP or AVIF files.', 'semrs-pro' ); ?></p>
			<?php return; ?>
		<?php endif; ?>
		<p><?php /* translators: %s: WEBP or AVIF */ printf( esc_html__( 'Make %s copies of every JPEG and PNG you uploaded before, in all sizes, and show them on your pages. Your original files stay untouched.', 'semrs-pro' ), esc_html( strtoupper( $ext ) ) ); ?></p>
		<p id="semrs-imglib-status"><?php /* translators: 1: converted, 2: total */ printf( esc_html__( '%1$d of %2$d images converted.', 'semrs-pro' ), (int) $c['done'], (int) $c['total'] ); ?> <?php echo get_option( 'semrs_imglib_saved' ) ? esc_html( sprintf( /* translators: %s: size */ __( 'Saved so far: %s.', 'semrs-pro' ), size_format( (int) get_option( 'semrs_imglib_saved' ) ) ) ) : ''; ?></p>
		<p>
			<button type="button" class="button button-primary" id="semrs-imglib-go"><?php esc_html_e( 'Convert library', 'semrs-pro' ); ?></button>
			<button type="button" class="button" id="semrs-imglib-undo"><?php esc_html_e( 'Remove copies', 'semrs-pro' ); ?></button>
		</p>
		<script>
		(function () {
			var st = document.getElementById('semrs-imglib-status'), busy = false;
			function run(restore) {
				if (busy) return; busy = true;
				var body = new FormData();
				body.append('action', 'semrs_imglib');
				body.append('_ajax_nonce', <?php echo wp_json_encode( wp_create_nonce( 'semrs_imglib' ) ); ?>);
				if (restore) body.append('restore', '1');
				(function step() {
					fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
						if (!r.success) { st.textContent = <?php echo wp_json_encode( __( 'Something went wrong. Reload the page and try again.', 'semrs-pro' ) ); ?>; busy = false; return; }
						var d = r.data;
						st.textContent = d.done + ' / ' + d.total + ' — ' + <?php echo wp_json_encode( __( 'saved', 'semrs-pro' ) ); ?> + ' ' + d.saved;
						if (d.more) { step(); } else { busy = false; }
					});
				})();
			}
			document.getElementById('semrs-imglib-go').addEventListener('click', function () { run(false); });
			document.getElementById('semrs-imglib-undo').addEventListener('click', function () { run(true); });
		})();
		</script>
		<?php
	}
);
