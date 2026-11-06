<?php
/**
 * Social images (Pro): a 1200 × 630 share image with the post title and
 * your colours for posts and pages that have no featured image or custom
 * share image. Made once on your own server with PHP GD and saved in
 * uploads/semrs-og/; remade when the title or design changes.
 *
 * Needs a TrueType font on the server: most hosts have DejaVu or Liberation
 * Sans; you can also enter the path or Media Library address of a .ttf file.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_og_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_og', array() ),
		array(
			'on'     => '1',
			'bg'     => '#0b1f3a',
			'text'   => '#ffffff',
			'accent' => '#f5b83d',
			'types'  => array( 'post', 'page' ),
			'font'   => '',
			'ver'    => 1,
		)
	);
}

/**
 * A usable TrueType font file, or ''.
 *
 * @return string
 */
function semrs_og_font() {
	$s    = semrs_og_settings();
	$list = array();
	if ( '' !== $s['font'] ) {
		$f = $s['font'];
		$u = wp_get_upload_dir();
		if ( 0 === strpos( $f, $u['baseurl'] ) ) {
			$f = $u['basedir'] . substr( $f, strlen( $u['baseurl'] ) );
		}
		$list[] = $f;
	}
	$list = array_merge(
		$list,
		array(
			'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
			'/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
			'/usr/share/fonts/liberation-sans/LiberationSans-Bold.ttf',
			'/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
			'/usr/share/fonts/gnu-free/FreeSansBold.ttf',
		)
	);
	foreach ( $list as $f ) {
		if ( preg_match( '/\.(ttf|otf)$/i', $f ) && @is_readable( $f ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- open_basedir may hide system paths.
			return $f;
		}
	}
	return '';
}

/**
 * Can this server make images?
 *
 * @return bool
 */
function semrs_og_ready() {
	return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagettftext' ) && '' !== semrs_og_font();
}

/**
 * Colour allocator from hex.
 *
 * @param resource|GdImage $im  Image.
 * @param string           $hex Colour.
 * @return int
 */
function semrs_og_color( $im, $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$n = hexdec( substr( str_pad( $hex, 6, '0' ), 0, 6 ) );
	return imagecolorallocate( $im, ( $n >> 16 ) & 255, ( $n >> 8 ) & 255, $n & 255 );
}

/**
 * Wrap text into lines that fit a width.
 *
 * @param string $text  Text.
 * @param string $font  Font file.
 * @param int    $size  Size.
 * @param int    $width Max width.
 * @return string[]
 */
function semrs_og_wrap( $text, $font, $size, $width ) {
	$lines = array();
	$line  = '';
	foreach ( preg_split( '/\s+/u', trim( $text ) ) as $word ) {
		$try = '' === $line ? $word : $line . ' ' . $word;
		$box = imagettfbbox( $size, 0, $font, $try );
		if ( abs( $box[2] - $box[0] ) > $width && '' !== $line ) {
			$lines[] = $line;
			$line    = $word;
		} else {
			$line = $try;
		}
	}
	if ( '' !== $line ) {
		$lines[] = $line;
	}
	return $lines;
}

/**
 * Make (or reuse) the image for a post. Returns its URL or ''.
 *
 * @param int $post_id Post.
 * @return string
 */
function semrs_og_image( $post_id ) {
	$s     = semrs_og_settings();
	$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' );
	if ( '' === $title || ! semrs_og_ready() ) {
		return '';
	}
	$u    = wp_get_upload_dir();
	$dir  = trailingslashit( $u['basedir'] ) . 'semrs-og/';
	$hash = substr( md5( $title . '|' . $s['bg'] . $s['text'] . $s['accent'] . $s['ver'] . get_bloginfo( 'name' ) ), 0, 10 );
	$name = $post_id . '-' . $hash . '.png';
	$url  = trailingslashit( $u['baseurl'] ) . 'semrs-og/' . $name;
	if ( file_exists( $dir . $name ) ) {
		return $url;
	}
	if ( ! wp_mkdir_p( $dir ) ) {
		return '';
	}
	foreach ( (array) glob( $dir . $post_id . '-*.png' ) as $old ) {
		wp_delete_file( $old );
	}
	$font = semrs_og_font();
	$w    = 1200;
	$h    = 630;
	$im   = imagecreatetruecolor( $w, $h );
	imagefill( $im, 0, 0, semrs_og_color( $im, $s['bg'] ) );
	$text   = semrs_og_color( $im, $s['text'] );
	$accent = semrs_og_color( $im, $s['accent'] );
	imagefilledrectangle( $im, 0, $h - 14, $w, $h, $accent );
	imagefilledrectangle( $im, 80, 150, 200, 158, $accent );
	// Site name, top left.
	imagettftext( $im, 26, 0, 80, 110, $accent, $font, get_bloginfo( 'name' ) );
	// Title: largest size that fits in 3 lines.
	$size  = 64;
	$lines = semrs_og_wrap( $title, $font, $size, $w - 160 );
	while ( count( $lines ) > 3 && $size > 34 ) {
		$size -= 4;
		$lines = semrs_og_wrap( $title, $font, $size, $w - 160 );
	}
	if ( count( $lines ) > 3 ) {
		$lines    = array_slice( $lines, 0, 3 );
		$lines[2] = rtrim( mb_substr( $lines[2], 0, max( 1, mb_strlen( $lines[2] ) - 2 ) ) ) . '…';
	}
	$y = 230 + $size;
	foreach ( $lines as $l ) {
		imagettftext( $im, $size, 0, 80, $y, $text, $font, $l );
		$y += (int) round( $size * 1.35 );
	}
	// Domain, bottom left.
	imagettftext( $im, 22, 0, 80, $h - 50, $text, $font, (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$ok = imagepng( $im, $dir . $name, 7 );
	imagedestroy( $im );
	return $ok ? $url : '';
}

/**
 * Hook into SEMRS SEO: use the image when the post has none.
 *
 * @param string $url Current.
 * @param int    $id  Post.
 * @return string
 */
function semrs_og_filter( $url, $id ) {
	$s = semrs_og_settings();
	if ( '' !== $url || '1' !== $s['on'] || ! in_array( get_post_type( $id ), (array) $s['types'], true ) || post_password_required( $id ) ) {
		return $url;
	}
	return semrs_og_image( (int) $id );
}
add_filter( 'semrs_seo_auto_image', 'semrs_og_filter', 10, 2 );

/*
 * Settings.
 */
if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_submenu_page( 'semrs', __( 'Social images', 'semrs-pro' ), __( 'Social images', 'semrs-pro' ), 'manage_options', 'semrs-og-images', 'semrs_og_page' );
		},
		36
	);
	add_action(
		'admin_init',
		function () {
			register_setting(
				'semrs_og',
				'semrs_og',
				array(
					'type'              => 'array',
					'sanitize_callback' => function ( $in ) {
						$in  = (array) $in;
						$old = semrs_og_settings();
						$hex = function ( $v, $d ) {
							return preg_match( '/^#[0-9a-fA-F]{6}$/', (string) $v ) ? $v : $d;
						};
						return array(
							'on'     => empty( $in['on'] ) ? '' : '1',
							'bg'     => $hex( $in['bg'] ?? '', '#0b1f3a' ),
							'text'   => $hex( $in['text'] ?? '', '#ffffff' ),
							'accent' => $hex( $in['accent'] ?? '', '#f5b83d' ),
							'types'  => array_values( array_filter( array_map( 'sanitize_key', (array) ( $in['types'] ?? array() ) ), 'post_type_exists' ) ),
							'font'   => sanitize_text_field( $in['font'] ?? '' ),
							'ver'    => (int) $old['ver'] + 1,
						);
					},
				)
			);
		}
	);
}

/**
 * Settings page with a live preview.
 */
function semrs_og_page() {
	$s       = semrs_og_settings();
	$ready   = semrs_og_ready();
	$latest  = get_posts( array( 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'fields' => 'ids' ) );
	$preview = $ready && $latest ? semrs_og_image( (int) $latest[0] ) : '';
	$types   = get_post_types( array( 'public' => true ), 'objects' );
	?>
	<div class="wrap semrs-wrap">
		<h1><?php esc_html_e( 'Social images', 'semrs-pro' ); ?></h1>
		<p><?php esc_html_e( 'When a post or page has no featured image and no share image of its own, Facebook, LinkedIn, X, WhatsApp and Slack show this automatic image instead of a blank box.', 'semrs-pro' ); ?></p>
		<?php if ( ! function_exists( 'imagettftext' ) ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'This server\'s PHP has no GD image library with font support. Ask your host to enable GD with FreeType.', 'semrs-pro' ); ?></p></div>
		<?php elseif ( ! $ready ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No font file was found on this server. Enter the full path of a .ttf font below, or upload one to your site and paste its address.', 'semrs-pro' ); ?></p></div>
		<?php endif; ?>
		<?php if ( $preview ) : ?>
			<p><img src="<?php echo esc_url( $preview ); ?>" alt="<?php esc_attr_e( 'Preview of an automatic share image', 'semrs-pro' ); ?>" width="600" height="315" style="max-width:100%;height:auto;border:1px solid #dcdcde;border-radius:6px"></p>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_og' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Automatic images', 'semrs-pro' ); ?></th><td><label><input type="checkbox" name="semrs_og[on]" value="1" <?php checked( $s['on'], '1' ); ?>> <?php esc_html_e( 'Make share images for posts without one', 'semrs-pro' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Colours', 'semrs-pro' ); ?></th><td>
					<label><?php esc_html_e( 'Background', 'semrs-pro' ); ?> <input type="color" name="semrs_og[bg]" value="<?php echo esc_attr( $s['bg'] ); ?>"></label>&nbsp;
					<label><?php esc_html_e( 'Title', 'semrs-pro' ); ?> <input type="color" name="semrs_og[text]" value="<?php echo esc_attr( $s['text'] ); ?>"></label>&nbsp;
					<label><?php esc_html_e( 'Accent', 'semrs-pro' ); ?> <input type="color" name="semrs_og[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>"></label>
				</td></tr>
				<tr><th><?php esc_html_e( 'Use for', 'semrs-pro' ); ?></th><td>
					<?php foreach ( $types as $t ) : ?>
						<?php
						if ( 'attachment' === $t->name ) {
							continue;
						}
						?>
						<label style="margin-right:12px"><input type="checkbox" name="semrs_og[types][]" value="<?php echo esc_attr( $t->name ); ?>" <?php checked( in_array( $t->name, (array) $s['types'], true ) ); ?>> <?php echo esc_html( $t->labels->name ); ?></label>
					<?php endforeach; ?>
				</td></tr>
				<tr><th><label for="semrs-og-font"><?php esc_html_e( 'Font file (optional)', 'semrs-pro' ); ?></label></th><td><input id="semrs-og-font" type="text" class="large-text" name="semrs_og[font]" value="<?php echo esc_attr( $s['font'] ); ?>" placeholder="/usr/share/fonts/…/YourFont-Bold.ttf"><p class="description"><?php /* translators: %s: font path. */ echo esc_html( $ready ? sprintf( __( 'Now using: %s', 'semrs-pro' ), basename( semrs_og_font() ) ) : __( 'No font found yet.', 'semrs-pro' ) ); ?></p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
