<?php
/**
 * Image SEO: alt text and titles from clean file names on upload, and
 * missing alt attributes filled from the media library on output.
 * Decorative images (alt="") are left alone. Camera names (IMG_1234,
 * DSC0001, Screenshot…) are never turned into alt text.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Human text from a file name, or '' if it is not meaningful.
 *
 * @param string $file File name or path.
 * @return string
 */
function semrs_image_seo_text( $file ) {
	$name = strtolower( pathinfo( (string) $file, PATHINFO_FILENAME ) );
	$name = preg_replace( '/-(scaled|rotated|e\d+|\d+x\d+)$/', '', $name );
	// Camera / phone / screenshot names: a generic word followed only by numbers.
	if ( preg_match( '/^(img|dsc|dscn|dcim|pxl|mvimg|photo|image|picture|unnamed|untitled|download)([\s_\-]*\d[\d_\-\s]*)?$/', $name ) || preg_match( '/^(screenshot|screen-shot|screen_shot|whatsapp-image|whatsapp image)/', $name ) || preg_match( '/^[\d_\-\s]+$/', $name ) ) {
		return '';
	}
	$text = trim( preg_replace( '/\s+/', ' ', preg_replace( '/[_\-.]+/', ' ', $name ) ) );
	$text = preg_replace( '/\b\d{6,}\b/', '', $text ); // Long numeric IDs.
	$text = trim( preg_replace( '/\s+/', ' ', $text ) );
	return strlen( $text ) >= 3 ? ucfirst( $text ) : '';
}

/**
 * On upload: set title and alt when they are empty or just the file name.
 *
 * @param int $id Attachment ID.
 */
function semrs_image_seo_on_upload( $id ) {
	if ( ! wp_attachment_is_image( $id ) ) {
		return;
	}
	$text = semrs_image_seo_text( get_attached_file( $id ) );
	if ( '' === $text ) {
		return;
	}
	if ( '' === (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) {
		update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $text ) );
	}
	$post = get_post( $id );
	if ( $post && ( '' === $post->post_title || sanitize_title( $post->post_title ) === sanitize_title( pathinfo( (string) get_attached_file( $id ), PATHINFO_FILENAME ) ) ) ) {
		wp_update_post( array( 'ID' => $id, 'post_title' => sanitize_text_field( $text ) ) );
	}
}
add_action( 'add_attachment', 'semrs_image_seo_on_upload' );

/**
 * On output: images with no alt attribute at all get the media library alt.
 *
 * @param string $content Content.
 * @return string
 */
function semrs_image_seo_content( $content ) {
	if ( false === strpos( $content, '<img' ) ) {
		return $content;
	}
	return preg_replace_callback(
		'/<img\b(?![^>]*\balt=)[^>]*>/i',
		function ( $m ) {
			$tag = $m[0];
			if ( ! preg_match( '/wp-image-(\d+)/', $tag, $id ) ) {
				return $tag;
			}
			$alt = (string) get_post_meta( (int) $id[1], '_wp_attachment_image_alt', true );
			return '' === $alt ? $tag : preg_replace( '/<img\b/i', '<img alt="' . esc_attr( $alt ) . '"', $tag, 1 );
		},
		$content
	);
}
add_filter( 'the_content', 'semrs_image_seo_content', 30 );
