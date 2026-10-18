<?php
/**
 * SEMRS icon set: original 24×24 line icons (stroke 2, round caps), drawn
 * for SEMRS Blocks and licensed GPL with the plugin. Brand marks are
 * simplified glyphs for linking to profiles.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon name => inner SVG markup.
 *
 * @return array<string, string>
 */
function semrs_blocks_icons() {
	return apply_filters(
		'semrs_blocks_icons',
		array(
			'check'         => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
			'check-circle'  => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>',
			'x'             => '<path d="M6 6l12 12M18 6L6 18"/>',
			'plus'          => '<path d="M12 5v14M5 12h14"/>',
			'arrow-right'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
			'star'          => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
			'heart'         => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
			'phone'         => '<path d="M5 4h3.5l1.5 4-2 1.5a11 11 0 0 0 6.5 6.5l1.5-2 4 1.5V19a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
			'mail'          => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5L12 13l8.5-6.5"/>',
			'map-pin'       => '<path d="M12 21s-6.5-6.2-6.5-11a6.5 6.5 0 0 1 13 0c0 4.8-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
			'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
			'calendar'      => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
			'search'        => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
			'chart'         => '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
			'trending-up'   => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
			'target'        => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>',
			'rocket'        => '<path d="M14 4.5c3-1.5 5.5-1 5.5-1s.5 2.5-1 5.5L13 14.5 9.5 11z"/><path d="M9.5 11l-4 .5L4 13.5l4 1.5M13 14.5l-.5 4-2 1.5-1.5-4"/><path d="M6.5 17.5c-1 1-1.5 3-1.5 3s2-.5 3-1.5"/>',
			'bolt'          => '<path d="M13 3L5 13.5h6L10 21l8-10.5h-6z"/>',
			'shield'        => '<path d="M12 3l7.5 3v6c0 4.5-3.2 7.8-7.5 9-4.3-1.2-7.5-4.5-7.5-9V6z"/><path d="M9 12l2 2 4-4"/>',
			'lock'          => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
			'globe'         => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.5 5.7 3.5 9S14.5 18.3 12 21c-2.5-2.7-3.5-5.7-3.5-9S9.5 5.7 12 3z"/>',
			'users'         => '<circle cx="9" cy="8.5" r="3.5"/><path d="M2.5 20c.5-3.5 3.2-5.5 6.5-5.5s6 2 6.5 5.5"/><path d="M16 5.2a3.5 3.5 0 0 1 0 6.6M18 14.8c2 .7 3.2 2.5 3.5 5.2"/>',
			'user'          => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c.6-4 3.6-6.5 7.5-6.5s6.9 2.5 7.5 6.5"/>',
			'home'          => '<path d="M3.5 11L12 4l8.5 7"/><path d="M5.5 9.5V20h13V9.5M10 20v-6h4v6"/>',
			'briefcase'     => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8.5 7V5a1.5 1.5 0 0 1 1.5-1.5h4A1.5 1.5 0 0 1 15.5 5v2M3 12.5h18"/>',
			'award'         => '<circle cx="12" cy="9" r="5.5"/><path d="M8.5 13.5L7 21l5-2.5 5 2.5-1.5-7.5"/>',
			'layers'        => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
			'link'          => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
			'code'          => '<path d="M8.5 7L3.5 12l5 5M15.5 7l5 5-5 5M13.5 4.5l-3 15"/>',
			'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
			'cart'          => '<path d="M3 4h2.5l2.2 11h11l2-8H6.5"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/>',
			'tag'           => '<path d="M3.5 12.5V4h8.5l8.5 8.5-8.5 8.5z"/><circle cx="8" cy="8.5" r="1.4"/>',
			'chat'          => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9.5h8M8 12.5h5"/>',
			'thumbs-up'     => '<path d="M7.5 10.5V20H4v-9.5zM7.5 10.5L11 4c1.7 0 2.5 1 2.5 2.5V9.5H19a1.5 1.5 0 0 1 1.5 1.8l-1.4 7.2A2 2 0 0 1 17.2 20H7.5"/>',
			'play'          => '<circle cx="12" cy="12" r="9"/><path d="M10 8.5v7l6-3.5z"/>',
			'camera'        => '<path d="M4 7.5h3.5L9 5h6l1.5 2.5H20a1 1 0 0 1 1 1V19H3V8.5a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/>',
			'image'         => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.7"/><path d="M21 16l-5-5-8 8"/>',
			'download'      => '<path d="M12 4v11M7 10.5l5 5 5-5M4.5 20h15"/>',
			'cloud'         => '<path d="M7 18.5a4.5 4.5 0 0 1-.8-8.9A6 6 0 0 1 17.8 9a4.8 4.8 0 0 1-.8 9.5z"/>',
			'pen'           => '<path d="M4 20l1-4.5L16 4.5l3.5 3.5L8.5 19z"/><path d="M14 7l3 3"/>',
			'lightbulb'     => '<path d="M9 17.5h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.7.6 1 1.4 1 2.1v.5h5V16c0-.7.3-1.5 1-2.1A6 6 0 0 0 12 3z"/>',
			'gift'          => '<rect x="3.5" y="8.5" width="17" height="4" rx="1"/><path d="M5 12.5V20h14v-7.5M12 8.5V20M12 8.5S10.5 4 8 4.5 6.5 8.5 12 8.5zM12 8.5S13.5 4 16 4.5s1.5 4-4 4z"/>',
			'whatsapp'      => '<path d="M4 20l1.2-4A8 8 0 1 1 8 19z"/><path d="M9 8.5c0 3 3 6 6 6l1.3-1.3-2-1-1 .8a4 4 0 0 1-2.3-2.3l.8-1-1-2z"/>',
			'facebook'      => '<path d="M14 21v-7.5h2.5l.5-3h-3V8.8c0-.9.3-1.5 1.5-1.5H17V4.6c-.3 0-1.2-.1-2.3-.1-2.3 0-3.7 1.4-3.7 3.9v2.1H8.5v3H11V21"/>',
			'linkedin'      => '<rect x="3.5" y="3.5" width="17" height="17" rx="2"/><path d="M8 10.5V16.5M8 7.5v.01M11.5 16.5v-6M11.5 13c0-1.6 1-2.5 2.3-2.5S16 11.3 16 13v3.5"/>',
			'instagram'     => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17 7v.01"/>',
			'youtube'       => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9v6l5-3z"/>',
		)
	);
}

/**
 * SVG markup for an icon.
 *
 * @param string $name  Icon name.
 * @param int    $size  Pixel size.
 * @param string $color CSS colour ('' = currentColor).
 * @param string $label Accessible label ('' = decorative).
 * @return string
 */
function semrs_blocks_icon_svg( $name, $size = 24, $color = '', $label = '' ) {
	$icons = semrs_blocks_icons();
	if ( empty( $icons[ $name ] ) ) {
		$name = 'star';
	}
	$color = function_exists( 'semrs_blocks_color' ) ? semrs_blocks_color( $color ) : '';
	$aria  = $label ? 'role="img" aria-label="' . esc_attr( $label ) . '"' : 'aria-hidden="true" focusable="false"';
	return sprintf(
		'<svg class="semrs-icon semrs-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" %3$s%4$s>%5$s</svg>',
		esc_attr( $name ),
		max( 8, min( 512, (int) $size ) ),
		$aria,
		$color ? ' style="color:' . esc_attr( $color ) . '"' : '',
		$icons[ $name ] // Static, trusted markup.
	);
}
