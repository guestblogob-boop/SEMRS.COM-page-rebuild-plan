<?php
/**
 * Image optimization (free): new JPEG and PNG uploads are resized to a
 * maximum size, compressed and saved as WebP (or AVIF) on your own server
 * with WordPress's own image editor (Imagick or GD). No outside service,
 * no account and no quota. Thumbnails are made from the converted file.
 *
 * Settings: SEMRS → Images. SEMRS Pro adds converting the existing media
 * library in batches, with the original files kept.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array{format:string,quality:int,max:int}
 */
function semrs_images_settings() {
	$saved = get_option( 'semrs_images', array() );
	$s     = array_merge(
		array(
			'format'  => 'webp',
			'quality' => 82,
			'max'     => 2560,
		),
		is_array( $saved ) ? $saved : array()
	);
	$s['quality'] = min( 100, max( 40, (int) $s['quality'] ) );
	$s['max']     = (int) $s['max'];
	return $s;
}

/**
 * Target mime type for conversions, or '' when off or unsupported here.
 *
 * @return string
 */
function semrs_images_target_mime() {
	static $mime = null;
	if ( null === $mime ) {
		$format = semrs_images_settings()['format'];
		$mime   = in_array( $format, array( 'webp', 'avif' ), true ) ? 'image/' . $format : '';
		if ( $mime && ! wp_image_editor_supports( array( 'mime_type' => $mime ) ) ) {
			$mime = 'image/avif' === $mime && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? 'image/webp' : '';
		}
	}
	return $mime;
}

/**
 * Compression level for every size WordPress saves.
 *
 * @return int
 */
add_filter(
	'wp_editor_set_quality',
	function () {
		return semrs_images_settings()['quality'];
	}
);

/**
 * Largest side of the stored original (0 = WordPress default of 2560).
 */
add_filter(
	'big_image_size_threshold',
	function ( $threshold ) {
		$max = semrs_images_settings()['max'];
		return $max > 0 ? $max : $threshold;
	}
);

/**
 * Thumbnails of JPEG/PNG files are written in the target format too.
 *
 * @param array $formats Source mime => output mime.
 * @return array
 */
add_filter(
	'image_editor_output_format',
	function ( $formats ) {
		$mime = semrs_images_target_mime();
		if ( $mime ) {
			$formats['image/jpeg'] = $mime;
			$formats['image/png']  = $mime;
		}
		return $formats;
	}
);

/**
 * Convert a new upload before WordPress creates the attachment.
 *
 * @param array $upload { file, url, type }.
 * @return array
 */
function semrs_images_convert_upload( $upload ) {
	$mime = semrs_images_target_mime();
	if ( ! $mime || empty( $upload['file'] ) || ! in_array( $upload['type'] ?? '', array( 'image/jpeg', 'image/png' ), true ) || ! empty( $upload['error'] ) ) {
		return $upload;
	}
	$converted = semrs_images_convert_file( $upload['file'], $mime );
	if ( ! $converted ) {
		return $upload;
	}
	wp_delete_file( $upload['file'] );
	$upload['url']  = dirname( $upload['url'] ) . '/' . wp_basename( $converted );
	$upload['file'] = $converted;
	$upload['type'] = $mime;
	return $upload;
}
add_filter( 'wp_handle_upload', 'semrs_images_convert_upload', 20 );

/**
 * Write a converted copy next to a JPEG/PNG file (same name, new extension),
 * resized to the maximum side. Returns the new path, or '' when the copy
 * would not be smaller or the editor fails.
 *
 * @param string $file Absolute path.
 * @param string $mime Target mime type.
 * @param string $dest Optional destination path.
 * @return string
 */
function semrs_images_convert_file( $file, $mime, $dest = '' ) {
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return '';
	}
	$max     = semrs_images_settings()['max'];
	$resized = false;
	if ( $max > 0 ) {
		$size = $editor->get_size();
		if ( $size['width'] > $max || $size['height'] > $max ) {
			$resized = ! is_wp_error( $editor->resize( $max, $max ) );
		}
	}
	$editor->set_quality( semrs_images_settings()['quality'] );
	if ( ! $dest ) {
		$info = pathinfo( $file );
		$dest = wp_unique_filename( $info['dirname'], $info['filename'] . '.' . substr( $mime, 6 ) );
		$dest = $info['dirname'] . '/' . $dest;
	}
	$saved = $editor->save( $dest, $mime );
	if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! file_exists( $saved['path'] ) ) {
		return '';
	}
	clearstatcache();
	if ( ! $resized && filesize( $saved['path'] ) >= filesize( $file ) ) {
		wp_delete_file( $saved['path'] );
		return ''; // Already well compressed: keep the original.
	}
	return $saved['path'];
}

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'semrs', __( 'Images', 'semrs-core' ), __( 'Images', 'semrs-core' ), 'manage_options', 'semrs-images', 'semrs_images_page' );
	},
	25
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'semrs_images',
			'semrs_images',
			array(
				'sanitize_callback' => function ( $in ) {
					$in = is_array( $in ) ? $in : array();
					return array(
						'format'  => in_array( $in['format'] ?? '', array( 'webp', 'avif', 'off' ), true ) ? $in['format'] : 'webp',
						'quality' => min( 100, max( 40, absint( $in['quality'] ?? 82 ) ) ),
						'max'     => min( 8000, absint( $in['max'] ?? 2560 ) ),
					);
				},
			)
		);
	}
);

/**
 * Settings screen.
 */
function semrs_images_page() {
	$s    = semrs_images_settings();
	$webp = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	$avif = wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Image Optimization', 'semrs-core' ); ?></h1>
		<p><?php esc_html_e( 'New JPEG and PNG uploads are resized, compressed and saved in a modern format on your own server, so pages load faster. Nothing is sent to an outside service.', 'semrs-core' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_images' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Save new uploads as', 'semrs-core' ); ?></th><td>
					<label><input type="radio" name="semrs_images[format]" value="webp" <?php checked( 'webp', $s['format'] ); ?> <?php disabled( ! $webp ); ?>> WebP <?php echo $webp ? '' : esc_html__( '(not supported by this server)', 'semrs-core' ); ?></label><br>
					<label><input type="radio" name="semrs_images[format]" value="avif" <?php checked( 'avif', $s['format'] ); ?> <?php disabled( ! $avif ); ?>> AVIF <?php echo $avif ? esc_html__( '(smallest files)', 'semrs-core' ) : esc_html__( '(not supported by this server)', 'semrs-core' ); ?></label><br>
					<label><input type="radio" name="semrs_images[format]" value="off" <?php checked( 'off', $s['format'] ); ?>> <?php esc_html_e( 'Keep the original format', 'semrs-core' ); ?></label>
					<p class="description"><?php esc_html_e( 'Every modern browser shows WebP and AVIF. GIFs and SVGs are never changed.', 'semrs-core' ); ?></p>
				</td></tr>
				<tr><th><label for="semrs-img-q"><?php esc_html_e( 'Compression quality', 'semrs-core' ); ?></label></th><td>
					<input id="semrs-img-q" type="number" min="40" max="100" class="small-text" name="semrs_images[quality]" value="<?php echo esc_attr( $s['quality'] ); ?>">
					<p class="description"><?php esc_html_e( '82 is a good balance; lower means smaller files. Applies to every size WordPress creates.', 'semrs-core' ); ?></p>
				</td></tr>
				<tr><th><label for="semrs-img-max"><?php esc_html_e( 'Largest side (pixels)', 'semrs-core' ); ?></label></th><td>
					<input id="semrs-img-max" type="number" min="0" max="8000" class="small-text" name="semrs_images[max]" value="<?php echo esc_attr( $s['max'] ); ?>">
					<p class="description"><?php esc_html_e( 'Bigger photos are scaled down when uploaded. 0 keeps the WordPress default (2560).', 'semrs-core' ); ?></p>
				</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php do_action( 'semrs_images_page_after' ); ?>
	</div>
	<?php
}
