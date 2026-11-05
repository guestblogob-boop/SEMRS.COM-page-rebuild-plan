<?php
/**
 * More free blocks: call to action, buttons, notice, divider, star rating,
 * team member, business hours, map, social icons, logo grid, steps,
 * timeline, progress bars, comparison table, share buttons, how-to,
 * review box and breadcrumbs. Each block prints its own small CSS once,
 * only on pages that use it.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Split "a | b | c" lines into trimmed parts.
 *
 * @param string $text Textarea value.
 * @return array<int, string[]>
 */
function semrs_blocks_rows( $text ) {
	$rows = array();
	foreach ( semrs_blocks_lines( $text ) as $line ) {
		$rows[] = array_map( 'trim', explode( '|', $line ) );
	}
	return $rows;
}

/**
 * Social networks: key => [label, icon, share URL pattern or ''].
 *
 * @return array
 */
function semrs_blocks_networks() {
	return array(
		'facebook'  => array( 'Facebook', 'facebook', 'https://www.facebook.com/sharer/sharer.php?u=%1$s' ),
		'x'         => array( 'X', 'x-social', 'https://x.com/intent/post?url=%1$s&text=%2$s' ),
		'linkedin'  => array( 'LinkedIn', 'linkedin', 'https://www.linkedin.com/sharing/share-offsite/?url=%1$s' ),
		'whatsapp'  => array( 'WhatsApp', 'whatsapp', 'https://wa.me/?text=%2$s%%20%1$s' ),
		'pinterest' => array( 'Pinterest', 'pinterest', 'https://pinterest.com/pin/create/button/?url=%1$s&description=%2$s' ),
		'telegram'  => array( 'Telegram', 'telegram', 'https://t.me/share/url?url=%1$s&text=%2$s' ),
		'email'     => array( 'Email', 'mail', 'mailto:?subject=%2$s&body=%1$s' ),
		'instagram' => array( 'Instagram', 'instagram', '' ),
		'youtube'   => array( 'YouTube', 'youtube', '' ),
		'tiktok'    => array( 'TikTok', 'tiktok', '' ),
		'github'    => array( 'GitHub', 'github', '' ),
		'website'   => array( 'Website', 'globe', '' ),
		'phone'     => array( 'Phone', 'phone', '' ),
	);
}

/**
 * Block definitions.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_blocks_more( $blocks ) {
	$align = array( 'left' => __( 'Left', 'semrs-blocks' ), 'center' => __( 'Centre', 'semrs-blocks' ), 'right' => __( 'Right', 'semrs-blocks' ) );

	$blocks['semrs/cta'] = array(
		'title'       => __( 'Call to Action', 'semrs-blocks' ),
		'description' => __( 'Headline, short text and one or two buttons in a coloured box.', 'semrs-blocks' ),
		'icon'        => 'megaphone',
		'keywords'    => array( 'cta', 'call to action', 'banner', 'box', 'button' ),
		'render'      => 'semrs_blocks_render_cta',
		'panels'      => array(
			__( 'Content', 'semrs-blocks' ) => array( 'title', 'text', 'button', 'url', 'button2', 'url2' ),
			__( 'Design', 'semrs-blocks' )  => array( 'layout', 'bg', 'textColor' ),
		),
		'controls'    => array(
			'title'     => array( 'type' => 'text', 'label' => __( 'Headline', 'semrs-blocks' ), 'default' => 'Ready to grow your traffic?' ),
			'text'      => array( 'type' => 'textarea', 'label' => __( 'Text', 'semrs-blocks' ), 'default' => 'Get a free review of your site and a clear plan for the next 90 days.' ),
			'button'    => array( 'type' => 'text', 'label' => __( 'Button text', 'semrs-blocks' ), 'default' => 'Get my free review' ),
			'url'       => array( 'type' => 'url', 'label' => __( 'Button link', 'semrs-blocks' ), 'default' => '' ),
			'button2'   => array( 'type' => 'text', 'label' => __( 'Second button text (optional)', 'semrs-blocks' ), 'default' => '' ),
			'url2'      => array( 'type' => 'url', 'label' => __( 'Second button link', 'semrs-blocks' ), 'default' => '' ),
			'layout'    => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'center', 'options' => array( 'center' => __( 'Centred', 'semrs-blocks' ), 'split' => __( 'Text left, buttons right', 'semrs-blocks' ) ) ),
			'bg'        => array( 'type' => 'color', 'label' => __( 'Background', 'semrs-blocks' ), 'default' => '' ),
			'textColor' => array( 'type' => 'color', 'label' => __( 'Text colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/buttons'] = array(
		'title'       => __( 'Buttons', 'semrs-blocks' ),
		'description' => __( 'A row of buttons with icons and three styles.', 'semrs-blocks' ),
		'icon'        => 'button',
		'keywords'    => array( 'button', 'buttons', 'link', 'cta' ),
		'render'      => 'semrs_blocks_render_buttons',
		'controls'    => array(
			'items'  => array( 'type' => 'textarea', 'label' => __( 'Buttons, one per line: Text | Link | style | icon', 'semrs-blocks' ), 'default' => "Get started | /contact/ | primary | arrow-right\nSee pricing | /pricing/ | outline", 'help' => __( 'Styles: primary, outline, ghost. Icon: any icon name from the Icon block (optional).', 'semrs-blocks' ) ),
			'position' => array( 'type' => 'select', 'label' => __( 'Alignment', 'semrs-blocks' ), 'default' => 'left', 'options' => $align ),
			'size'   => array( 'type' => 'select', 'label' => __( 'Size', 'semrs-blocks' ), 'default' => 'md', 'options' => array( 'sm' => __( 'Small', 'semrs-blocks' ), 'md' => __( 'Medium', 'semrs-blocks' ), 'lg' => __( 'Large', 'semrs-blocks' ) ) ),
			'newTab' => array( 'type' => 'toggle', 'label' => __( 'Open links in a new tab', 'semrs-blocks' ), 'default' => false ),
			'accent' => array( 'type' => 'color', 'label' => __( 'Button colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/alert'] = array(
		'title'       => __( 'Notice / Alert', 'semrs-blocks' ),
		'description' => __( 'Info, success, warning or error box, optionally dismissible.', 'semrs-blocks' ),
		'icon'        => 'warning',
		'keywords'    => array( 'alert', 'notice', 'info', 'warning', 'callout', 'tip' ),
		'render'      => 'semrs_blocks_render_alert',
		'controls'    => array(
			'type'    => array( 'type' => 'select', 'label' => __( 'Type', 'semrs-blocks' ), 'default' => 'info', 'options' => array( 'info' => __( 'Info', 'semrs-blocks' ), 'success' => __( 'Success / tip', 'semrs-blocks' ), 'warning' => __( 'Warning', 'semrs-blocks' ), 'error' => __( 'Error / important', 'semrs-blocks' ) ) ),
			'title'   => array( 'type' => 'text', 'label' => __( 'Title (optional)', 'semrs-blocks' ), 'default' => 'Good to know' ),
			'text'    => array( 'type' => 'textarea', 'label' => __( 'Text', 'semrs-blocks' ), 'default' => 'Write the message here.' ),
			'dismiss' => array( 'type' => 'toggle', 'label' => __( 'Visitors can close it', 'semrs-blocks' ), 'default' => false ),
		),
	);

	$blocks['semrs/divider'] = array(
		'title'       => __( 'Divider', 'semrs-blocks' ),
		'description' => __( 'Separator line with optional text or icon in the middle.', 'semrs-blocks' ),
		'icon'        => 'minus',
		'keywords'    => array( 'divider', 'separator', 'line', 'hr' ),
		'render'      => 'semrs_blocks_render_divider',
		'controls'    => array(
			'style'     => array( 'type' => 'select', 'label' => __( 'Line style', 'semrs-blocks' ), 'default' => 'solid', 'options' => array( 'solid' => __( 'Solid', 'semrs-blocks' ), 'dashed' => __( 'Dashed', 'semrs-blocks' ), 'dotted' => __( 'Dotted', 'semrs-blocks' ), 'double' => __( 'Double', 'semrs-blocks' ) ) ),
			'text'      => array( 'type' => 'text', 'label' => __( 'Text in the middle (optional)', 'semrs-blocks' ), 'default' => '' ),
			'icon'      => array( 'type' => 'icon', 'label' => __( 'Icon in the middle (optional)', 'semrs-blocks' ), 'default' => '' ),
			'width'     => array( 'type' => 'range', 'label' => __( 'Width (%)', 'semrs-blocks' ), 'default' => 100, 'min' => 10, 'max' => 100 ),
			'thickness' => array( 'type' => 'range', 'label' => __( 'Thickness (px)', 'semrs-blocks' ), 'default' => 1, 'min' => 1, 'max' => 10 ),
			'color'     => array( 'type' => 'color', 'label' => __( 'Colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/rating'] = array(
		'title'       => __( 'Star Rating', 'semrs-blocks' ),
		'description' => __( 'Show a star rating with half stars and a label.', 'semrs-blocks' ),
		'icon'        => 'star-half',
		'keywords'    => array( 'rating', 'stars', 'score', 'review' ),
		'render'      => 'semrs_blocks_render_rating',
		'controls'    => array(
			'rating' => array( 'type' => 'number', 'label' => __( 'Rating (e.g. 4.5)', 'semrs-blocks' ), 'default' => 4.5 ),
			'max'    => array( 'type' => 'range', 'label' => __( 'Out of', 'semrs-blocks' ), 'default' => 5, 'min' => 3, 'max' => 10 ),
			'label'  => array( 'type' => 'text', 'label' => __( 'Label (optional)', 'semrs-blocks' ), 'default' => '' ),
			'size'   => array( 'type' => 'range', 'label' => __( 'Star size (px)', 'semrs-blocks' ), 'default' => 22, 'min' => 12, 'max' => 60 ),
			'color'  => array( 'type' => 'color', 'label' => __( 'Star colour', 'semrs-blocks' ), 'default' => '' ),
			'position' => array( 'type' => 'select', 'label' => __( 'Alignment', 'semrs-blocks' ), 'default' => 'left', 'options' => $align ),
		),
	);

	$blocks['semrs/team'] = array(
		'title'       => __( 'Team Member', 'semrs-blocks' ),
		'description' => __( 'Photo, name, role, short bio and profile links.', 'semrs-blocks' ),
		'icon'        => 'admin-users',
		'keywords'    => array( 'team', 'member', 'person', 'staff', 'author', 'profile' ),
		'render'      => 'semrs_blocks_render_team',
		'controls'    => array(
			'photo' => array( 'type' => 'media', 'label' => __( 'Photo', 'semrs-blocks' ), 'default' => 0 ),
			'name'  => array( 'type' => 'text', 'label' => __( 'Name', 'semrs-blocks' ), 'default' => 'Your name' ),
			'role'  => array( 'type' => 'text', 'label' => __( 'Role', 'semrs-blocks' ), 'default' => 'Role or job title' ),
			'bio'   => array( 'type' => 'textarea', 'label' => __( 'Short bio', 'semrs-blocks' ), 'default' => '' ),
			'links' => array( 'type' => 'textarea', 'label' => __( 'Profile links, one per line: network | URL', 'semrs-blocks' ), 'default' => '', 'help' => __( 'Networks: facebook, x, linkedin, instagram, youtube, tiktok, github, website, email, phone.', 'semrs-blocks' ) ),
			'style' => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'card', 'options' => array( 'card' => __( 'Card', 'semrs-blocks' ), 'side' => __( 'Photo on the side', 'semrs-blocks' ), 'round' => __( 'Round photo, centred', 'semrs-blocks' ) ) ),
		),
	);

	$blocks['semrs/hours'] = array(
		'title'       => __( 'Business Hours', 'semrs-blocks' ),
		'description' => __( 'Opening hours table. Lines with "Closed" are shown muted.', 'semrs-blocks' ),
		'icon'        => 'clock',
		'keywords'    => array( 'hours', 'opening', 'times', 'schedule', 'business' ),
		'render'      => 'semrs_blocks_render_hours',
		'controls'    => array(
			'title' => array( 'type' => 'text', 'label' => __( 'Title (optional)', 'semrs-blocks' ), 'default' => 'Opening hours' ),
			'rows'  => array( 'type' => 'textarea', 'label' => __( 'One per line: Day(s) | Hours', 'semrs-blocks' ), 'default' => "Monday – Friday | 9:00 – 18:00\nSaturday | 10:00 – 14:00\nSunday | Closed" ),
			'note'  => array( 'type' => 'text', 'label' => __( 'Note under the table (optional)', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/map'] = array(
		'title'       => __( 'Map', 'semrs-blocks' ),
		'description' => __( 'Google Map of an address. No API key; loads only when a visitor clicks, so the page stays fast and private.', 'semrs-blocks' ),
		'icon'        => 'location-alt',
		'keywords'    => array( 'map', 'google maps', 'location', 'address', 'directions' ),
		'render'      => 'semrs_blocks_render_map',
		'controls'    => array(
			'address' => array( 'type' => 'text', 'label' => __( 'Address or place name', 'semrs-blocks' ), 'default' => '' ),
			'zoom'    => array( 'type' => 'range', 'label' => __( 'Zoom', 'semrs-blocks' ), 'default' => 15, 'min' => 3, 'max' => 20 ),
			'height'  => array( 'type' => 'range', 'label' => __( 'Height (px)', 'semrs-blocks' ), 'default' => 380, 'min' => 200, 'max' => 800 ),
			'onClick' => array( 'type' => 'toggle', 'label' => __( 'Load the map only when clicked (faster, no Google cookies until then)', 'semrs-blocks' ), 'default' => true ),
		),
	);

	$blocks['semrs/social'] = array(
		'title'       => __( 'Social Icons', 'semrs-blocks' ),
		'description' => __( 'Follow links to your social profiles.', 'semrs-blocks' ),
		'icon'        => 'share',
		'keywords'    => array( 'social', 'icons', 'follow', 'facebook', 'instagram', 'linkedin' ),
		'render'      => 'semrs_blocks_render_social',
		'controls'    => array(
			'links' => array( 'type' => 'textarea', 'label' => __( 'One per line: network | URL', 'semrs-blocks' ), 'default' => '', 'help' => __( 'Networks: facebook, x, linkedin, instagram, youtube, tiktok, pinterest, telegram, whatsapp, github, website, email, phone.', 'semrs-blocks' ) ),
			'style' => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'round', 'options' => array( 'plain' => __( 'Icon only', 'semrs-blocks' ), 'round' => __( 'Round', 'semrs-blocks' ), 'square' => __( 'Rounded square', 'semrs-blocks' ), 'label' => __( 'Icon and name', 'semrs-blocks' ) ) ),
			'size'  => array( 'type' => 'range', 'label' => __( 'Icon size (px)', 'semrs-blocks' ), 'default' => 20, 'min' => 14, 'max' => 48 ),
			'color' => array( 'type' => 'color', 'label' => __( 'Colour', 'semrs-blocks' ), 'default' => '' ),
			'position' => array( 'type' => 'select', 'label' => __( 'Alignment', 'semrs-blocks' ), 'default' => 'left', 'options' => $align ),
		),
	);

	$blocks['semrs/logos'] = array(
		'title'       => __( 'Logo Grid', 'semrs-blocks' ),
		'description' => __( 'Client, partner or "as seen in" logos in a tidy grid.', 'semrs-blocks' ),
		'icon'        => 'grid-view',
		'keywords'    => array( 'logos', 'clients', 'partners', 'brands', 'trust' ),
		'render'      => 'semrs_blocks_render_logos',
		'controls'    => array(
			'images'    => array( 'type' => 'gallery', 'label' => __( 'Logos', 'semrs-blocks' ) ),
			'columns'   => array( 'type' => 'range', 'label' => __( 'Columns (desktop)', 'semrs-blocks' ), 'default' => 5, 'min' => 2, 'max' => 8 ),
			'height'    => array( 'type' => 'range', 'label' => __( 'Logo height (px)', 'semrs-blocks' ), 'default' => 48, 'min' => 20, 'max' => 120 ),
			'grayscale' => array( 'type' => 'toggle', 'label' => __( 'Grey until hovered', 'semrs-blocks' ), 'default' => true ),
		),
	);

	$blocks['semrs/steps'] = array(
		'title'       => __( 'Steps / Process', 'semrs-blocks' ),
		'description' => __( 'Numbered steps that explain how something works.', 'semrs-blocks' ),
		'icon'        => 'editor-ol',
		'keywords'    => array( 'steps', 'process', 'how it works', 'numbered' ),
		'render'      => 'semrs_blocks_render_steps',
		'controls'    => array(
			'items'  => array( 'type' => 'textarea', 'label' => __( 'One step per line: Title | Text', 'semrs-blocks' ), 'default' => "Tell us your goal | A short call about your site and customers.\nGet your plan | A clear list of fixes and content, with costs.\nSee results | We do the work and report every month." ),
			'layout' => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'row', 'options' => array( 'row' => __( 'Side by side', 'semrs-blocks' ), 'column' => __( 'Stacked', 'semrs-blocks' ) ) ),
			'accent' => array( 'type' => 'color', 'label' => __( 'Number colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/timeline'] = array(
		'title'       => __( 'Timeline', 'semrs-blocks' ),
		'description' => __( 'Dates and events on a vertical line: history, roadmap or changelog.', 'semrs-blocks' ),
		'icon'        => 'backup',
		'keywords'    => array( 'timeline', 'history', 'roadmap', 'milestones', 'changelog' ),
		'render'      => 'semrs_blocks_render_timeline',
		'controls'    => array(
			'items'  => array( 'type' => 'textarea', 'label' => __( 'One per line: Date | Title | Text', 'semrs-blocks' ), 'default' => "2024 | Started | Where it began.\n2025 | Grew | What changed.\n2026 | Today | Where we are now." ),
			'layout' => array( 'type' => 'select', 'label' => __( 'Layout', 'semrs-blocks' ), 'default' => 'left', 'options' => array( 'left' => __( 'Line on the left', 'semrs-blocks' ), 'alternate' => __( 'Alternating sides (desktop)', 'semrs-blocks' ) ) ),
			'accent' => array( 'type' => 'color', 'label' => __( 'Dot and line colour', 'semrs-blocks' ), 'default' => '' ),
		),
	);

	$blocks['semrs/progress'] = array(
		'title'       => __( 'Progress Bars', 'semrs-blocks' ),
		'description' => __( 'Labelled bars for skills, goals or survey results.', 'semrs-blocks' ),
		'icon'        => 'chart-bar',
		'keywords'    => array( 'progress', 'bar', 'skills', 'percent', 'goal' ),
		'render'      => 'semrs_blocks_render_progress',
		'controls'    => array(
			'items'   => array( 'type' => 'textarea', 'label' => __( 'One per line: Label | Percent', 'semrs-blocks' ), 'default' => "Technical SEO | 90\nContent | 80\nLink building | 70" ),
			'height'  => array( 'type' => 'range', 'label' => __( 'Bar height (px)', 'semrs-blocks' ), 'default' => 10, 'min' => 4, 'max' => 30 ),
			'color'   => array( 'type' => 'color', 'label' => __( 'Bar colour', 'semrs-blocks' ), 'default' => '' ),
			'percent' => array( 'type' => 'toggle', 'label' => __( 'Show the percentage', 'semrs-blocks' ), 'default' => true ),
		),
	);

	$blocks['semrs/table'] = array(
		'title'       => __( 'Comparison Table', 'semrs-blocks' ),
		'description' => __( 'Feature comparison with yes/no ticks and a highlighted column.', 'semrs-blocks' ),
		'icon'        => 'editor-table',
		'keywords'    => array( 'table', 'comparison', 'compare', 'features', 'versus' ),
		'render'      => 'semrs_blocks_render_table',
		'controls'    => array(
			'rows'      => array( 'type' => 'textarea', 'label' => __( 'Rows, cells separated by |', 'semrs-blocks' ), 'default' => "Feature | Free | Pro\nSEO checks | yes | yes\nSpeed tools | yes | yes\nPriority support | no | yes", 'help' => __( 'The first line is the header. Write yes or no to show a tick or a cross.', 'semrs-blocks' ) ),
			'header'    => array( 'type' => 'toggle', 'label' => __( 'First line is the header', 'semrs-blocks' ), 'default' => true ),
			'ticks'     => array( 'type' => 'toggle', 'label' => __( 'Show yes / no as ticks and crosses', 'semrs-blocks' ), 'default' => true ),
			'striped'   => array( 'type' => 'toggle', 'label' => __( 'Striped rows', 'semrs-blocks' ), 'default' => true ),
			'highlight' => array( 'type' => 'range', 'label' => __( 'Highlight column (0 = none)', 'semrs-blocks' ), 'default' => 0, 'min' => 0, 'max' => 8 ),
		),
	);

	$blocks['semrs/share'] = array(
		'title'       => __( 'Share Buttons', 'semrs-blocks' ),
		'description' => __( 'Share the current page. Plain links: no tracking scripts, no slowdown.', 'semrs-blocks' ),
		'icon'        => 'share-alt2',
		'keywords'    => array( 'share', 'social', 'facebook', 'whatsapp', 'linkedin', 'copy link' ),
		'render'      => 'semrs_blocks_render_share',
		'controls'    => array(
			'networks' => array( 'type' => 'textarea', 'label' => __( 'Networks, one per line', 'semrs-blocks' ), 'default' => "facebook\nx\nlinkedin\nwhatsapp\nemail\ncopy", 'help' => __( 'facebook, x, linkedin, whatsapp, pinterest, telegram, email, copy (copy link).', 'semrs-blocks' ) ),
			'label'    => array( 'type' => 'text', 'label' => __( 'Label (optional)', 'semrs-blocks' ), 'default' => 'Share:' ),
			'style'    => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-blocks' ), 'default' => 'round', 'options' => array( 'round' => __( 'Round icons', 'semrs-blocks' ), 'label' => __( 'Icon and name', 'semrs-blocks' ) ) ),
			'position' => array( 'type' => 'select', 'label' => __( 'Alignment', 'semrs-blocks' ), 'default' => 'left', 'options' => $align ),
		),
	);

	$blocks['semrs/howto'] = array(
		'title'       => __( 'How-To Guide', 'semrs-blocks' ),
		'description' => __( 'Step-by-step instructions with time, tools and HowTo schema.', 'semrs-blocks' ),
		'icon'        => 'welcome-learn-more',
		'keywords'    => array( 'how to', 'howto', 'guide', 'tutorial', 'steps', 'schema' ),
		'render'      => 'semrs_blocks_render_howto',
		'panels'      => array(
			__( 'Guide', 'semrs-blocks' )  => array( 'name', 'description', 'time', 'steps' ),
			__( 'Extras', 'semrs-blocks' ) => array( 'tools', 'supplies', 'schema' ),
		),
		'controls'    => array(
			'name'        => array( 'type' => 'text', 'label' => __( 'Guide title', 'semrs-blocks' ), 'default' => 'How to add a sitemap to Google' ),
			'description' => array( 'type' => 'textarea', 'label' => __( 'Short description', 'semrs-blocks' ), 'default' => '' ),
			'time'        => array( 'type' => 'number', 'label' => __( 'Total time (minutes, 0 = hide)', 'semrs-blocks' ), 'default' => 10 ),
			'steps'       => array( 'type' => 'textarea', 'label' => __( 'One step per line: Step title | Instructions', 'semrs-blocks' ), 'default' => "Open Search Console | Sign in and choose your site.\nGo to Sitemaps | Find Sitemaps in the left menu.\nSubmit | Enter sitemap.xml and press Submit." ),
			'tools'       => array( 'type' => 'textarea', 'label' => __( 'Tools needed, one per line (optional)', 'semrs-blocks' ), 'default' => '' ),
			'supplies'    => array( 'type' => 'textarea', 'label' => __( 'Materials needed, one per line (optional)', 'semrs-blocks' ), 'default' => '' ),
			'schema'      => array( 'type' => 'toggle', 'label' => __( 'Add HowTo schema', 'semrs-blocks' ), 'default' => true, 'help' => __( 'Structured data for search engines and AI answers. Google no longer shows HowTo rich results, but the markup still describes the steps.', 'semrs-blocks' ) ),
		),
	);

	$blocks['semrs/review'] = array(
		'title'       => __( 'Review Box', 'semrs-blocks' ),
		'description' => __( 'Your review of a product or service: score, pros, cons and verdict, with Review schema.', 'semrs-blocks' ),
		'icon'        => 'thumbs-up',
		'keywords'    => array( 'review', 'rating', 'pros', 'cons', 'verdict', 'schema' ),
		'render'      => 'semrs_blocks_render_review',
		'controls'    => array(
			'item'     => array( 'type' => 'text', 'label' => __( 'What is reviewed', 'semrs-blocks' ), 'default' => 'Product name' ),
			'itemType' => array( 'type' => 'select', 'label' => __( 'Type', 'semrs-blocks' ), 'default' => 'Product', 'options' => array( 'Product' => __( 'Product', 'semrs-blocks' ), 'SoftwareApplication' => __( 'Software / app', 'semrs-blocks' ), 'Book' => __( 'Book', 'semrs-blocks' ), 'Course' => __( 'Course', 'semrs-blocks' ), 'Game' => __( 'Game', 'semrs-blocks' ), 'Movie' => __( 'Movie', 'semrs-blocks' ) ) ),
			'rating'   => array( 'type' => 'number', 'label' => __( 'Score out of 5 (e.g. 4.5)', 'semrs-blocks' ), 'default' => 4 ),
			'summary'  => array( 'type' => 'textarea', 'label' => __( 'Verdict', 'semrs-blocks' ), 'default' => '' ),
			'pros'     => array( 'type' => 'textarea', 'label' => __( 'Pros, one per line', 'semrs-blocks' ), 'default' => '' ),
			'cons'     => array( 'type' => 'textarea', 'label' => __( 'Cons, one per line', 'semrs-blocks' ), 'default' => '' ),
			'author'   => array( 'type' => 'text', 'label' => __( 'Reviewer name (empty = post author)', 'semrs-blocks' ), 'default' => '' ),
			'schema'   => array( 'type' => 'toggle', 'label' => __( 'Add Review schema', 'semrs-blocks' ), 'default' => true, 'help' => __( 'Only for honest reviews of other companies\' products. Google ignores reviews a business writes about itself.', 'semrs-blocks' ) ),
		),
	);

	$blocks['semrs/breadcrumbs'] = array(
		'title'       => __( 'Breadcrumbs', 'semrs-blocks' ),
		'description' => __( 'Home › Category › Page trail for the current page.', 'semrs-blocks' ),
		'icon'        => 'arrow-right-alt2',
		'keywords'    => array( 'breadcrumbs', 'navigation', 'trail', 'path' ),
		'render'      => 'semrs_blocks_render_breadcrumbs',
		'controls'    => array(
			'separator' => array( 'type' => 'select', 'label' => __( 'Separator', 'semrs-blocks' ), 'default' => 'chevron', 'options' => array( 'chevron' => '›', 'slash' => '/', 'arrow' => '→', 'dot' => '·' ) ),
			'home'      => array( 'type' => 'text', 'label' => __( 'Home label', 'semrs-blocks' ), 'default' => 'Home' ),
			'schema'    => array( 'type' => 'toggle', 'label' => __( 'Add BreadcrumbList schema', 'semrs-blocks' ), 'default' => false, 'help' => __( 'Leave off when SEMRS Core SEO is active: it already adds breadcrumb schema to every page.', 'semrs-blocks' ) ),
		),
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_blocks_more', 6 );

/**
 * Link attributes for a URL (new tab for other sites).
 *
 * @param string $url    URL.
 * @param bool   $newtab Force new tab.
 * @return string
 */
function semrs_blocks_link_attrs( $url, $newtab = false ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$ext  = $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host;
	return ( $newtab || $ext ) && 0 !== strpos( $url, 'mailto:' ) && 0 !== strpos( $url, 'tel:' ) ? ' target="_blank" rel="noopener"' : '';
}

/**
 * Call to action.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_cta( $a ) {
	$css  = semrs_blocks_css_part( 'cta', '.semrs-cta{padding:clamp(1.5rem,4vw,3rem);border-radius:var(--b-radius);background:var(--cta-bg,var(--b-primary));color:var(--cta-fg,#fff);text-align:center}.semrs-cta h2{margin:0 0 .5rem;color:inherit;font-size:clamp(1.4rem,3vw,2rem)}.semrs-cta p{margin:0 auto 1.25rem;max-width:42rem;opacity:.9}.semrs-cta__btns{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center}.semrs-cta .semrs-btn--line{background:transparent;color:inherit;border-color:currentColor}@media(min-width:782px){.semrs-cta--split{display:flex;align-items:center;justify-content:space-between;gap:2rem;text-align:left}.semrs-cta--split p{margin:0}.semrs-cta--split .semrs-cta__btns{flex:0 0 auto}}' );
	$btns = '';
	foreach ( array( array( 'button', 'url', '' ), array( 'button2', 'url2', ' semrs-btn--line' ) ) as $b ) {
		if ( semrs_blocks_a( $a, $b[0] ) ) {
			$url   = semrs_blocks_a( $a, $b[1], '#' );
			$btns .= '<a class="semrs-btn' . $b[2] . '" href="' . esc_url( $url ) . '"' . semrs_blocks_link_attrs( $url ) . '>' . esc_html( $a[ $b[0] ] ) . '</a>';
		}
	}
	return $css . sprintf(
		'<div class="semrs-cta semrs-cta--%1$s"%2$s><div><h2>%3$s</h2>%4$s</div>%5$s</div>',
		'split' === semrs_blocks_a( $a, 'layout' ) ? 'split' : 'center',
		semrs_blocks_style( array( '--cta-bg' => semrs_blocks_color( semrs_blocks_a( $a, 'bg' ) ), '--cta-fg' => semrs_blocks_color( semrs_blocks_a( $a, 'textColor' ) ) ) ),
		esc_html( semrs_blocks_a( $a, 'title' ) ),
		semrs_blocks_a( $a, 'text' ) ? '<p>' . esc_html( $a['text'] ) . '</p>' : '',
		$btns ? '<div class="semrs-cta__btns">' . $btns . '</div>' : ''
	);
}

/**
 * Buttons.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_buttons( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add buttons in the block settings.', 'semrs-blocks' ) . '</p>' : '';
	}
	$css   = semrs_blocks_css_part( 'buttons', '.semrs-btns{display:flex;flex-wrap:wrap;gap:.75rem}.semrs-btns--center{justify-content:center}.semrs-btns--right{justify-content:flex-end}.semrs-btns .semrs-btn{gap:.5rem}.semrs-btns .semrs-btn--outline{background:transparent;color:var(--b-primary);border-color:var(--b-accent)}.semrs-btns .semrs-btn--ghost{background:transparent;border-color:transparent;color:var(--b-primary);text-decoration:underline}.semrs-btns--sm .semrs-btn{min-height:38px;padding:.4rem .9rem;font-size:.9em}.semrs-btns--lg .semrs-btn{min-height:56px;padding:.8rem 1.8rem;font-size:1.1em}' );
	$size  = in_array( semrs_blocks_a( $a, 'size', 'md' ), array( 'sm', 'md', 'lg' ), true ) ? $a['size'] : 'md';
	$align = in_array( semrs_blocks_a( $a, 'position', 'left' ), array( 'left', 'center', 'right' ), true ) ? $a['position'] : 'left';
	$out   = '';
	foreach ( $rows as $r ) {
		$url   = isset( $r[1] ) && '' !== $r[1] ? $r[1] : '#';
		$style = isset( $r[2] ) && in_array( $r[2], array( 'outline', 'ghost' ), true ) ? ' semrs-btn--' . $r[2] : '';
		$icon  = ! empty( $r[3] ) && isset( semrs_blocks_icons()[ $r[3] ] ) ? semrs_blocks_icon_svg( $r[3], 18 ) : '';
		$out  .= '<a class="semrs-btn' . $style . '" href="' . esc_url( $url ) . '"' . semrs_blocks_link_attrs( $url, ! empty( $a['newTab'] ) ) . '>' . esc_html( $r[0] ) . $icon . '</a>';
	}
	return $css . '<div class="semrs-btns semrs-btns--' . esc_attr( $align ) . ' semrs-btns--' . esc_attr( $size ) . '"' . semrs_blocks_style( array( '--b-accent' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ) . '>' . $out . '</div>';
}

/**
 * Notice / alert.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_alert( $a ) {
	$types = array( 'info' => 'info', 'success' => 'check-circle', 'warning' => 'alert', 'error' => 'alert' );
	$type  = isset( $types[ semrs_blocks_a( $a, 'type', 'info' ) ] ) ? $a['type'] : 'info';
	$css   = semrs_blocks_css_part( 'alert', '.semrs-alert{position:relative;display:flex;gap:.75rem;padding:1rem 1.25rem;border-radius:var(--b-radius);border-left:4px solid var(--al);background:color-mix(in srgb,var(--al) 10%,transparent);color:var(--b-text)}.semrs-alert--info{--al:#2563eb}.semrs-alert--success{--al:#16a34a}.semrs-alert--warning{--al:#d97706}.semrs-alert--error{--al:#dc2626}.semrs-alert>.semrs-icon{color:var(--al);margin-top:.15rem}.semrs-alert b{display:block;margin-bottom:.2rem}.semrs-alert p{margin:0}.semrs-alert__x{position:absolute;top:.5rem;right:.5rem;width:32px;height:32px;border:0;background:none;color:inherit;font-size:1.25rem;line-height:1;cursor:pointer}' );
	$x     = ! empty( $a['dismiss'] ) ? '<button type="button" class="semrs-alert__x" aria-label="' . esc_attr__( 'Close', 'semrs-blocks' ) . '" onclick="this.parentNode.remove()">&times;</button>' : '';
	return $css . '<div class="semrs-alert semrs-alert--' . esc_attr( $type ) . '" role="' . ( 'error' === $type || 'warning' === $type ? 'alert' : 'note' ) . '">' . semrs_blocks_icon_svg( $types[ $type ], 22 ) . '<div>' . ( semrs_blocks_a( $a, 'title' ) ? '<b>' . esc_html( $a['title'] ) . '</b>' : '' ) . '<p>' . nl2br( esc_html( semrs_blocks_a( $a, 'text' ) ) ) . '</p></div>' . $x . '</div>';
}

/**
 * Divider.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_divider( $a ) {
	$css   = semrs_blocks_css_part( 'divider', '.semrs-divider{display:flex;align-items:center;gap:1rem;margin-inline:auto;color:var(--dv,var(--b-line))}.semrs-divider::before,.semrs-divider::after{content:"";flex:1;border-top:var(--dt,1px) var(--ds,solid) currentColor}.semrs-divider span{color:var(--b-muted);font-weight:600}.semrs-divider .semrs-icon{color:var(--b-accent)}' );
	$style = in_array( semrs_blocks_a( $a, 'style', 'solid' ), array( 'solid', 'dashed', 'dotted', 'double' ), true ) ? $a['style'] : 'solid';
	$mid   = semrs_blocks_a( $a, 'icon' ) ? semrs_blocks_icon_svg( $a['icon'], 22 ) : ( semrs_blocks_a( $a, 'text' ) ? '<span>' . esc_html( $a['text'] ) . '</span>' : '' );
	$thick = max( 1, min( 10, (int) semrs_blocks_a( $a, 'thickness', 1 ) ) );
	return $css . '<div class="semrs-divider" role="separator"' . semrs_blocks_style(
		array(
			'width' => max( 10, min( 100, (int) semrs_blocks_a( $a, 'width', 100 ) ) ) . '%',
			'--dt'  => ( 'double' === $style ? max( 3, $thick ) : $thick ) . 'px',
			'--ds'  => $style,
			'--dv'  => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ),
			'gap'   => $mid ? '' : '0',
		)
	) . '>' . $mid . '</div>';
}

/**
 * Stars as SVG (supports halves).
 *
 * @param float $rating Rating.
 * @param int   $max    Max.
 * @param int   $size   Size px.
 * @return string
 */
function semrs_blocks_stars( $rating, $max = 5, $size = 20 ) {
	$path = 'M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9z';
	$out  = '';
	for ( $i = 1; $i <= $max; $i++ ) {
		$fill = $rating >= $i ? 1 : ( $rating > $i - 1 ? $rating - ( $i - 1 ) : 0 );
		$pct  = (int) round( $fill * 100 );
		$out .= '<svg class="semrs-star" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="semrs-st-' . $pct . '"><stop offset="' . $pct . '%" stop-color="currentColor"/><stop offset="' . $pct . '%" stop-color="currentColor" stop-opacity=".2"/></linearGradient></defs><path d="' . $path . '" fill="url(#semrs-st-' . $pct . ')"/></svg>';
	}
	return $out;
}

/**
 * Star rating.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_rating( $a ) {
	$max    = max( 3, min( 10, (int) semrs_blocks_a( $a, 'max', 5 ) ) );
	$rating = max( 0, min( $max, (float) semrs_blocks_a( $a, 'rating', 0 ) ) );
	$css    = semrs_blocks_css_part( 'rating', '.semrs-rating{display:flex;align-items:center;flex-wrap:wrap;gap:.5rem;color:var(--b-text)}.semrs-rating__stars{display:inline-flex;gap:2px;color:var(--st,#f5a623)}.semrs-rating--center{justify-content:center}.semrs-rating--right{justify-content:flex-end}' );
	$align  = in_array( semrs_blocks_a( $a, 'position', 'left' ), array( 'left', 'center', 'right' ), true ) ? $a['position'] : 'left';
	/* translators: 1: rating, 2: maximum. */
	$text = sprintf( __( 'Rated %1$s out of %2$d', 'semrs-blocks' ), number_format_i18n( $rating, 1 ), $max );
	return $css . '<div class="semrs-rating semrs-rating--' . esc_attr( $align ) . '"' . semrs_blocks_style( array( '--st' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ) ) ) . '><span class="semrs-rating__stars" role="img" aria-label="' . esc_attr( $text ) . '">' . semrs_blocks_stars( $rating, $max, (int) semrs_blocks_a( $a, 'size', 22 ) ) . '</span><strong>' . esc_html( number_format_i18n( $rating, 1 ) ) . '</strong>' . ( semrs_blocks_a( $a, 'label' ) ? '<span>' . esc_html( $a['label'] ) . '</span>' : '' ) . '</div>';
}

/**
 * Profile / social links from "network | URL" lines.
 *
 * @param string $text  Lines.
 * @param int    $size  Icon size.
 * @param bool   $label Show names.
 * @return string
 */
function semrs_blocks_profile_links( $text, $size = 20, $label = false ) {
	$nets = semrs_blocks_networks();
	$out  = '';
	foreach ( semrs_blocks_rows( $text ) as $r ) {
		$key = strtolower( $r[0] );
		$url = $r[1] ?? '';
		if ( ! isset( $nets[ $key ] ) || '' === $url ) {
			continue;
		}
		if ( 'email' === $key && false === strpos( $url, ':' ) ) {
			$url = 'mailto:' . $url;
		} elseif ( 'phone' === $key && false === strpos( $url, ':' ) ) {
			$url = 'tel:' . preg_replace( '/[^0-9+]/', '', $url );
		}
		$out .= '<a href="' . esc_url( $url ) . '"' . semrs_blocks_link_attrs( $url ) . ( $label ? '' : ' aria-label="' . esc_attr( $nets[ $key ][0] ) . '"' ) . '>' . semrs_blocks_icon_svg( $nets[ $key ][1], $size ) . ( $label ? '<span>' . esc_html( $nets[ $key ][0] ) . '</span>' : '' ) . '</a>';
	}
	return $out;
}

/**
 * Team member.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_team( $a ) {
	$css   = semrs_blocks_css_part( 'team', '.semrs-team{max-width:24rem;padding:1.25rem;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-surface);color:var(--b-text)}.semrs-team img{display:block;width:100%;height:auto;aspect-ratio:1;object-fit:cover;border-radius:calc(var(--b-radius) - 4px)}.semrs-team h3{margin:.9rem 0 .15rem;font-size:1.15rem}.semrs-team__role{margin:0;color:var(--b-muted);font-size:.95em}.semrs-team__bio{margin:.6rem 0 0}.semrs-team__links,.semrs-social{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.8rem}.semrs-team__links a{display:inline-grid;place-items:center;width:36px;height:36px;border-radius:50%;background:var(--b-primary);color:#fff}.semrs-team--round{text-align:center}.semrs-team--round img{width:140px;margin:0 auto;border-radius:50%}.semrs-team--round .semrs-team__links{justify-content:center}.semrs-team--side{max-width:none}@media(min-width:600px){.semrs-team--side{display:grid;grid-template-columns:160px 1fr;gap:1.25rem;align-items:start}.semrs-team--side h3{margin-top:0}}' );
	$photo = (int) semrs_blocks_a( $a, 'photo', 0 ) ? wp_get_attachment_image( (int) $a['photo'], 'medium', false, array( 'alt' => semrs_blocks_a( $a, 'name' ) ) ) : '';
	$style = in_array( semrs_blocks_a( $a, 'style', 'card' ), array( 'card', 'side', 'round' ), true ) ? $a['style'] : 'card';
	$links = semrs_blocks_profile_links( semrs_blocks_a( $a, 'links' ), 18 );
	return $css . '<div class="semrs-team semrs-team--' . esc_attr( $style ) . '">' . $photo . '<div><h3>' . esc_html( semrs_blocks_a( $a, 'name' ) ) . '</h3>' . ( semrs_blocks_a( $a, 'role' ) ? '<p class="semrs-team__role">' . esc_html( $a['role'] ) . '</p>' : '' ) . ( semrs_blocks_a( $a, 'bio' ) ? '<p class="semrs-team__bio">' . esc_html( $a['bio'] ) . '</p>' : '' ) . ( $links ? '<div class="semrs-team__links">' . $links . '</div>' : '' ) . '</div></div>';
}

/**
 * Business hours.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_hours( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'rows' ) );
	if ( ! $rows ) {
		return '';
	}
	$css = semrs_blocks_css_part( 'hours', '.semrs-hours{max-width:32rem;padding:1.25rem;border:1px solid var(--b-line);border-radius:var(--b-radius);color:var(--b-text)}.semrs-hours h3{display:flex;align-items:center;gap:.5rem;margin:0 0 .75rem;font-size:1.1rem}.semrs-hours dl{display:grid;grid-template-columns:1fr auto;gap:.45rem 1rem;margin:0}.semrs-hours dt{font-weight:600}.semrs-hours dd{margin:0;text-align:right}.semrs-hours .is-closed{color:var(--b-muted)}.semrs-hours p{margin:.75rem 0 0;color:var(--b-muted);font-size:.9em}' );
	$dl  = '';
	foreach ( $rows as $r ) {
		$closed = isset( $r[1] ) && preg_match( '/closed/i', $r[1] );
		$dl    .= '<dt' . ( $closed ? ' class="is-closed"' : '' ) . '>' . esc_html( $r[0] ) . '</dt><dd' . ( $closed ? ' class="is-closed"' : '' ) . '>' . esc_html( $r[1] ?? '' ) . '</dd>';
	}
	return $css . '<div class="semrs-hours">' . ( semrs_blocks_a( $a, 'title' ) ? '<h3>' . semrs_blocks_icon_svg( 'clock', 20 ) . esc_html( $a['title'] ) . '</h3>' : '' ) . '<dl>' . $dl . '</dl>' . ( semrs_blocks_a( $a, 'note' ) ? '<p>' . esc_html( $a['note'] ) . '</p>' : '' ) . '</div>';
}

/**
 * Map (Google Maps embed, no API key).
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_map( $a ) {
	$addr = trim( (string) semrs_blocks_a( $a, 'address' ) );
	if ( '' === $addr ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Enter an address in the block settings.', 'semrs-blocks' ) . '</p>' : '';
	}
	$src = 'https://maps.google.com/maps?q=' . rawurlencode( $addr ) . '&z=' . max( 3, min( 20, (int) semrs_blocks_a( $a, 'zoom', 15 ) ) ) . '&output=embed';
	$h   = max( 200, min( 800, (int) semrs_blocks_a( $a, 'height', 380 ) ) );
	$css = semrs_blocks_css_part( 'map', '.semrs-map{position:relative;overflow:hidden;border-radius:var(--b-radius);background:var(--b-surface)}.semrs-map iframe{display:block;width:100%;height:100%;border:0}.semrs-map__load{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;width:100%;border:0;background:repeating-linear-gradient(45deg,var(--b-surface),var(--b-surface) 12px,transparent 12px,transparent 24px);color:var(--b-primary);font:inherit;font-weight:700;cursor:pointer}.semrs-map__load small{font-weight:400;color:var(--b-muted)}' );
	$ifr = '<iframe src="' . esc_url( $src ) . '" title="' . esc_attr( $addr ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
	if ( empty( $a['onClick'] ) ) {
		return $css . '<div class="semrs-map" style="height:' . $h . 'px">' . $ifr . '</div>';
	}
	return $css . '<div class="semrs-map" style="height:' . $h . 'px"><button type="button" class="semrs-map__load" data-src="' . esc_url( $src ) . '" onclick="var f=document.createElement(\'iframe\');f.src=this.dataset.src;f.title=this.dataset.title;f.loading=\'lazy\';f.allowFullscreen=true;this.replaceWith(f)" data-title="' . esc_attr( $addr ) . '">' . semrs_blocks_icon_svg( 'map-pin', 32 ) . '<span>' . esc_html( $addr ) . '</span><small>' . esc_html__( 'Show map (loads Google Maps)', 'semrs-blocks' ) . '</small></button></div>';
}

/**
 * Social icons.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_social( $a ) {
	$style = in_array( semrs_blocks_a( $a, 'style', 'round' ), array( 'plain', 'round', 'square', 'label' ), true ) ? $a['style'] : 'round';
	$links = semrs_blocks_profile_links( semrs_blocks_a( $a, 'links' ), (int) semrs_blocks_a( $a, 'size', 20 ), 'label' === $style );
	if ( ! $links ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add your profiles in the block settings, one per line: facebook | https://…', 'semrs-blocks' ) . '</p>' : '';
	}
	$css   = semrs_blocks_css_part( 'social', '.semrs-social{display:flex;flex-wrap:wrap;gap:.5rem;color:var(--sc,var(--b-primary))}.semrs-social--center{justify-content:center}.semrs-social--right{justify-content:flex-end}.semrs-social a{display:inline-flex;align-items:center;gap:.4rem;color:inherit;text-decoration:none}.semrs-social--round a,.semrs-social--square a{justify-content:center;width:calc(var(--ss,20px) * 2);height:calc(var(--ss,20px) * 2);background:var(--sc,var(--b-primary));color:#fff;border-radius:50%}.semrs-social--square a{border-radius:22%}.semrs-social--label a{padding:.45rem .8rem;border:1px solid var(--b-line);border-radius:999px}.semrs-social a:hover{opacity:.85}' );
	$align = in_array( semrs_blocks_a( $a, 'position', 'left' ), array( 'left', 'center', 'right' ), true ) ? $a['position'] : 'left';
	return $css . '<div class="semrs-social semrs-social--' . esc_attr( $style ) . ' semrs-social--' . esc_attr( $align ) . '"' . semrs_blocks_style( array( '--sc' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ), '--ss' => (int) semrs_blocks_a( $a, 'size', 20 ) . 'px' ) ) . '>' . $links . '</div>';
}

/**
 * Logo grid.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_logos( $a ) {
	$ids = array_filter( array_map( 'intval', (array) semrs_blocks_a( $a, 'images', array() ) ) );
	if ( ! $ids ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Choose logos in the block settings.', 'semrs-blocks' ) . '</p>' : '';
	}
	$css = semrs_blocks_css_part( 'logos', '.semrs-logos{display:grid;grid-template-columns:repeat(2,1fr);gap:1.5rem 2rem;align-items:center;justify-items:center}@media(min-width:782px){.semrs-logos{grid-template-columns:repeat(var(--lc,5),1fr)}}.semrs-logos img{display:block;width:auto;max-width:100%;height:var(--lh,48px);object-fit:contain}.semrs-logos--gray img{filter:grayscale(1);opacity:.7;transition:filter .2s,opacity .2s}.semrs-logos--gray img:hover{filter:none;opacity:1}' );
	$out = '';
	foreach ( $ids as $id ) {
		$out .= wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy' ) );
	}
	return $css . '<div class="semrs-logos' . ( ! empty( $a['grayscale'] ) ? ' semrs-logos--gray' : '' ) . '"' . semrs_blocks_style( array( '--lc' => max( 2, min( 8, (int) semrs_blocks_a( $a, 'columns', 5 ) ) ), '--lh' => (int) semrs_blocks_a( $a, 'height', 48 ) . 'px' ) ) . '>' . $out . '</div>';
}

/**
 * Steps.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_steps( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return '';
	}
	$css = semrs_blocks_css_part( 'steps', '.semrs-steps{list-style:none;margin:0;padding:0;display:grid;gap:1.5rem;counter-reset:st}.semrs-steps li{position:relative;padding-left:3.5rem;counter-increment:st}.semrs-steps li::before{content:counter(st);position:absolute;left:0;top:0;display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:var(--sa,var(--b-accent));color:var(--b-on-accent);font-weight:800}.semrs-steps h3{margin:.3rem 0 .3rem;font-size:1.1rem}.semrs-steps p{margin:0;color:var(--b-muted)}@media(min-width:782px){.semrs-steps--row{grid-template-columns:repeat(auto-fit,minmax(12rem,1fr))}.semrs-steps--row li{padding:3.5rem 0 0}}' );
	$out = '';
	foreach ( $rows as $r ) {
		$out .= '<li><h3>' . esc_html( $r[0] ) . '</h3>' . ( ! empty( $r[1] ) ? '<p>' . esc_html( $r[1] ) . '</p>' : '' ) . '</li>';
	}
	return $css . '<ol class="semrs-steps semrs-steps--' . ( 'column' === semrs_blocks_a( $a, 'layout' ) ? 'column' : 'row' ) . '"' . semrs_blocks_style( array( '--sa' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ) . '>' . $out . '</ol>';
}

/**
 * Timeline.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_timeline( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return '';
	}
	$css = semrs_blocks_css_part( 'timeline', '.semrs-tl{list-style:none;margin:0;padding:0 0 0 1.75rem;position:relative}.semrs-tl::before{content:"";position:absolute;left:.45rem;top:.3rem;bottom:.3rem;width:2px;background:var(--ta,var(--b-accent))}.semrs-tl li{position:relative;padding-bottom:1.5rem}.semrs-tl li::before{content:"";position:absolute;left:-1.6rem;top:.3rem;width:.9rem;height:.9rem;border-radius:50%;background:var(--ta,var(--b-accent));box-shadow:0 0 0 4px var(--b-surface)}.semrs-tl time{display:block;font-weight:800;color:var(--b-primary)}.semrs-tl h3{margin:.15rem 0 .25rem;font-size:1.1rem}.semrs-tl p{margin:0;color:var(--b-muted)}@media(min-width:782px){.semrs-tl--alternate{padding:0}.semrs-tl--alternate::before{left:50%}.semrs-tl--alternate li{width:50%;padding-right:2rem;text-align:right}.semrs-tl--alternate li::before{left:auto;right:-.45rem}.semrs-tl--alternate li:nth-child(even){margin-left:50%;padding:0 0 1.5rem 2rem;text-align:left}.semrs-tl--alternate li:nth-child(even)::before{right:auto;left:-.45rem}}' );
	$out = '';
	foreach ( $rows as $r ) {
		$out .= '<li><time>' . esc_html( $r[0] ) . '</time>' . ( ! empty( $r[1] ) ? '<h3>' . esc_html( $r[1] ) . '</h3>' : '' ) . ( ! empty( $r[2] ) ? '<p>' . esc_html( $r[2] ) . '</p>' : '' ) . '</li>';
	}
	return $css . '<ol class="semrs-tl semrs-tl--' . ( 'alternate' === semrs_blocks_a( $a, 'layout' ) ? 'alternate' : 'left' ) . '"' . semrs_blocks_style( array( '--ta' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ) . '>' . $out . '</ol>';
}

/**
 * Progress bars.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_progress( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return '';
	}
	$css = semrs_blocks_css_part( 'progress', '.semrs-progress{display:grid;gap:1rem}.semrs-progress__top{display:flex;justify-content:space-between;margin-bottom:.35rem;font-weight:600}.semrs-progress__bar{height:var(--ph,10px);border-radius:999px;background:var(--b-line);overflow:hidden}.semrs-progress__bar span{display:block;height:100%;border-radius:inherit;background:var(--pc,var(--b-accent))}' );
	$out = '';
	foreach ( $rows as $r ) {
		$pct  = max( 0, min( 100, (int) ( $r[1] ?? 0 ) ) );
		$out .= '<div><div class="semrs-progress__top"><span>' . esc_html( $r[0] ) . '</span>' . ( ! empty( $a['percent'] ) ? '<span>' . $pct . '%</span>' : '' ) . '</div><div class="semrs-progress__bar" role="progressbar" aria-label="' . esc_attr( $r[0] ) . '" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100"><span style="width:' . $pct . '%"></span></div></div>';
	}
	return $css . '<div class="semrs-progress"' . semrs_blocks_style( array( '--ph' => max( 4, min( 30, (int) semrs_blocks_a( $a, 'height', 10 ) ) ) . 'px', '--pc' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ) ) ) . '>' . $out . '</div>';
}

/**
 * Comparison table.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_table( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'rows' ) );
	if ( ! $rows ) {
		return '';
	}
	$css   = semrs_blocks_css_part( 'table', '.semrs-table-wrap{overflow-x:auto;border:1px solid var(--b-line);border-radius:var(--b-radius)}.semrs-table{width:100%;border-collapse:collapse;color:var(--b-text)}.semrs-table th,.semrs-table td{padding:.75rem 1rem;border-bottom:1px solid var(--b-line);text-align:center}.semrs-table th:first-child,.semrs-table td:first-child{text-align:left}.semrs-table thead th{background:var(--b-primary);color:#fff}.semrs-table--striped tbody tr:nth-child(even){background:var(--b-surface)}.semrs-table .is-hl{background:color-mix(in srgb,var(--b-accent) 15%,transparent)}.semrs-table thead .is-hl{background:var(--b-accent);color:var(--b-on-accent)}.semrs-table .is-yes{color:#16a34a}.semrs-table .is-no{color:#dc2626}' );
	$hl    = (int) semrs_blocks_a( $a, 'highlight', 0 );
	$ticks = ! empty( $a['ticks'] );
	$cell  = function ( $v, $tag, $i ) use ( $hl, $ticks ) {
		$cls = $hl && $i + 1 === $hl ? ' class="is-hl"' : '';
		if ( $ticks && 'td' === $tag && preg_match( '/^(yes|no|✓|✗|x)$/iu', $v ) ) {
			$yes = preg_match( '/^(yes|✓)$/iu', $v );
			$v   = '<span class="' . ( $yes ? 'is-yes' : 'is-no' ) . '">' . semrs_blocks_icon_svg( $yes ? 'check' : 'x', 20 ) . '<span class="screen-reader-text">' . ( $yes ? esc_html__( 'Yes', 'semrs-blocks' ) : esc_html__( 'No', 'semrs-blocks' ) ) . '</span></span>';
		} else {
			$v = esc_html( $v );
		}
		$tag = 'td' === $tag && 0 === $i ? 'th scope="row"' : $tag;
		return '<' . $tag . $cls . '>' . $v . '</' . strtok( $tag, ' ' ) . '>';
	};
	$head = '';
	if ( ! empty( $a['header'] ) ) {
		$first = array_shift( $rows );
		foreach ( $first as $i => $v ) {
			$head .= $cell( $v, 'th scope="col"', $i );
		}
		$head = '<thead><tr>' . $head . '</tr></thead>';
	}
	$body = '';
	foreach ( $rows as $r ) {
		$body .= '<tr>';
		foreach ( $r as $i => $v ) {
			$body .= $cell( $v, 'td', $i );
		}
		$body .= '</tr>';
	}
	return $css . '<div class="semrs-table-wrap"><table class="semrs-table' . ( ! empty( $a['striped'] ) ? ' semrs-table--striped' : '' ) . '">' . $head . '<tbody>' . $body . '</tbody></table></div>';
}

/**
 * Share buttons for the current page.
 *
 * @param array $a     Attributes.
 * @param mixed $block Block.
 * @return string
 */
function semrs_blocks_render_share( $a, $block = null ) {
	$post_id = $block instanceof WP_Block && ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
	if ( ! $post_id && isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- editor preview only reads the id.
		$post_id = absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	$url   = $post_id ? get_permalink( $post_id ) : home_url( '/' );
	$title = $post_id ? wp_strip_all_tags( get_the_title( $post_id ) ) : get_bloginfo( 'name' );
	$nets  = semrs_blocks_networks();
	$label = 'label' === semrs_blocks_a( $a, 'style' );
	$out   = '';
	foreach ( semrs_blocks_lines( semrs_blocks_a( $a, 'networks' ) ) as $n ) {
		$n = strtolower( $n );
		if ( 'copy' === $n ) {
			$out .= '<button type="button" data-url="' . esc_url( $url ) . '" onclick="var b=this;navigator.clipboard&&navigator.clipboard.writeText(b.dataset.url).then(function(){b.classList.add(\'is-done\');setTimeout(function(){b.classList.remove(\'is-done\')},1500)})" aria-label="' . esc_attr__( 'Copy link', 'semrs-blocks' ) . '">' . semrs_blocks_icon_svg( 'copy', 18 ) . ( $label ? '<span>' . esc_html__( 'Copy link', 'semrs-blocks' ) . '</span>' : '' ) . '</button>';
			continue;
		}
		if ( empty( $nets[ $n ][2] ) ) {
			continue;
		}
		$href = sprintf( $nets[ $n ][2], rawurlencode( $url ), rawurlencode( $title ) );
		$out .= '<a href="' . esc_url( $href ) . '"' . ( 'email' === $n ? '' : ' target="_blank" rel="noopener nofollow"' ) . ( $label ? '' : ' aria-label="' . esc_attr( $nets[ $n ][0] ) . '"' ) . '>' . semrs_blocks_icon_svg( $nets[ $n ][1], 18 ) . ( $label ? '<span>' . esc_html( $nets[ $n ][0] ) . '</span>' : '' ) . '</a>';
	}
	if ( ! $out ) {
		return '';
	}
	$css   = semrs_blocks_css_part( 'share', '.semrs-share{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem}.semrs-share--center{justify-content:center}.semrs-share--right{justify-content:flex-end}.semrs-share__label{font-weight:700;margin-right:.25rem}.semrs-share a,.semrs-share button{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;min-width:40px;height:40px;padding:0 .6rem;border:1px solid var(--b-line);border-radius:999px;background:var(--b-surface);color:var(--b-primary);font:inherit;font-size:.9em;text-decoration:none;cursor:pointer}.semrs-share a:hover,.semrs-share button:hover{background:var(--b-primary);color:#fff}.semrs-share button.is-done{background:#16a34a;color:#fff}' );
	$align = in_array( semrs_blocks_a( $a, 'position', 'left' ), array( 'left', 'center', 'right' ), true ) ? $a['position'] : 'left';
	return $css . '<div class="semrs-share semrs-share--' . esc_attr( $align ) . '">' . ( semrs_blocks_a( $a, 'label' ) ? '<span class="semrs-share__label">' . esc_html( $a['label'] ) . '</span>' : '' ) . $out . '</div>';
}

/**
 * How-to guide with HowTo schema.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_howto( $a ) {
	$steps = semrs_blocks_rows( semrs_blocks_a( $a, 'steps' ) );
	if ( ! $steps ) {
		return '';
	}
	$css   = semrs_blocks_css_part( 'howto', '.semrs-howto{padding:clamp(1rem,3vw,1.75rem);border:1px solid var(--b-line);border-radius:var(--b-radius);color:var(--b-text)}.semrs-howto h2{margin:0 0 .5rem;font-size:1.4rem}.semrs-howto__meta{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin:.75rem 0;color:var(--b-muted)}.semrs-howto__meta span{display:inline-flex;align-items:center;gap:.35rem}.semrs-howto ol{margin:1rem 0 0;padding-left:1.25rem}.semrs-howto li{margin-bottom:.8rem}.semrs-howto li b{display:block}.semrs-howto ul{margin:.25rem 0 0;padding-left:1.25rem}' );
	$time  = max( 0, (int) semrs_blocks_a( $a, 'time', 0 ) );
	$tools = semrs_blocks_lines( semrs_blocks_a( $a, 'tools' ) );
	$sup   = semrs_blocks_lines( semrs_blocks_a( $a, 'supplies' ) );
	$meta  = '';
	if ( $time ) {
		/* translators: %d: minutes. */
		$meta .= '<span>' . semrs_blocks_icon_svg( 'clock', 18 ) . esc_html( sprintf( _n( '%d minute', '%d minutes', $time, 'semrs-blocks' ), $time ) ) . '</span>';
	}
	$lists = '';
	foreach ( array( array( $tools, __( 'Tools', 'semrs-blocks' ) ), array( $sup, __( 'Materials', 'semrs-blocks' ) ) ) as $l ) {
		if ( $l[0] ) {
			$lists .= '<p class="semrs-howto__meta"><strong>' . esc_html( $l[1] ) . ':</strong> ' . esc_html( implode( ', ', $l[0] ) ) . '</p>';
		}
	}
	$ol     = '';
	$schema = array();
	foreach ( $steps as $i => $s ) {
		$ol      .= '<li><b>' . esc_html( $s[0] ) . '</b>' . ( ! empty( $s[1] ) ? esc_html( $s[1] ) : '' ) . '</li>';
		$schema[] = array( '@type' => 'HowToStep', 'position' => $i + 1, 'name' => $s[0], 'text' => ! empty( $s[1] ) ? $s[1] : $s[0] );
	}
	$ld = '';
	if ( ! empty( $a['schema'] ) ) {
		$data = array( '@context' => 'https://schema.org', '@type' => 'HowTo', 'name' => semrs_blocks_a( $a, 'name' ), 'step' => $schema );
		if ( semrs_blocks_a( $a, 'description' ) ) {
			$data['description'] = $a['description'];
		}
		if ( $time ) {
			$data['totalTime'] = 'PT' . $time . 'M';
		}
		foreach ( $tools as $t ) {
			$data['tool'][] = array( '@type' => 'HowToTool', 'name' => $t );
		}
		foreach ( $sup as $t ) {
			$data['supply'][] = array( '@type' => 'HowToSupply', 'name' => $t );
		}
		$ld = '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>';
	}
	return $css . '<div class="semrs-howto"><h2>' . esc_html( semrs_blocks_a( $a, 'name' ) ) . '</h2>' . ( semrs_blocks_a( $a, 'description' ) ? '<p>' . esc_html( $a['description'] ) . '</p>' : '' ) . ( $meta ? '<p class="semrs-howto__meta">' . $meta . '</p>' : '' ) . $lists . '<ol>' . $ol . '</ol></div>' . $ld;
}

/**
 * Review box with Review schema.
 *
 * @param array $a     Attributes.
 * @param mixed $block Block.
 * @return string
 */
function semrs_blocks_render_review( $a, $block = null ) {
	$rating = max( 0, min( 5, (float) semrs_blocks_a( $a, 'rating', 0 ) ) );
	$css    = semrs_blocks_css_part( 'review', '.semrs-review{border:1px solid var(--b-line);border-radius:var(--b-radius);overflow:hidden;color:var(--b-text)}.semrs-review__head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem;background:var(--b-primary);color:#fff}.semrs-review__head h3{margin:0;color:inherit;font-size:1.2rem}.semrs-review__score{display:flex;align-items:center;gap:.5rem;color:#f5a623}.semrs-review__score b{font-size:1.6rem;color:#fff}.semrs-review__body{padding:1.25rem}.semrs-review__pc{display:grid;gap:1rem}@media(min-width:600px){.semrs-review__pc{grid-template-columns:1fr 1fr}}.semrs-review__pc ul{list-style:none;margin:.4rem 0 0;padding:0}.semrs-review__pc li{display:flex;gap:.4rem;margin-bottom:.35rem}.semrs-review__pros .semrs-icon{color:#16a34a}.semrs-review__cons .semrs-icon{color:#dc2626}.semrs-review__verdict{margin:1rem 0 0;padding-top:1rem;border-top:1px solid var(--b-line)}' );
	$list   = function ( $key, $icon ) use ( $a ) {
		$out = '';
		foreach ( semrs_blocks_lines( semrs_blocks_a( $a, $key ) ) as $l ) {
			$out .= '<li>' . semrs_blocks_icon_svg( $icon, 18 ) . '<span>' . esc_html( $l ) . '</span></li>';
		}
		return $out;
	};
	$pros = $list( 'pros', 'check' );
	$cons = $list( 'cons', 'x' );
	$pc   = '';
	if ( $pros || $cons ) {
		$pc = '<div class="semrs-review__pc"><div class="semrs-review__pros"><strong>' . esc_html__( 'Pros', 'semrs-blocks' ) . '</strong><ul>' . $pros . '</ul></div><div class="semrs-review__cons"><strong>' . esc_html__( 'Cons', 'semrs-blocks' ) . '</strong><ul>' . $cons . '</ul></div></div>';
	}
	$author = semrs_blocks_a( $a, 'author' );
	if ( '' === $author ) {
		$pid    = $block instanceof WP_Block && ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
		$author = $pid ? get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $pid ) ) : get_bloginfo( 'name' );
	}
	$ld = '';
	if ( ! empty( $a['schema'] ) && semrs_blocks_a( $a, 'item' ) ) {
		$types = array( 'Product', 'SoftwareApplication', 'Book', 'Course', 'Game', 'Movie' );
		$data  = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'Review',
			'itemReviewed' => array( '@type' => in_array( semrs_blocks_a( $a, 'itemType', 'Product' ), $types, true ) ? $a['itemType'] : 'Product', 'name' => $a['item'] ),
			'reviewRating' => array( '@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5, 'worstRating' => 0 ),
			'author'       => array( '@type' => 'Person', 'name' => $author ? $author : get_bloginfo( 'name' ) ),
		);
		if ( semrs_blocks_a( $a, 'summary' ) ) {
			$data['reviewBody'] = $a['summary'];
		}
		$ld = '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>';
	}
	/* translators: %s: rating. */
	$label = sprintf( __( 'Rated %s out of 5', 'semrs-blocks' ), number_format_i18n( $rating, 1 ) );
	return $css . '<div class="semrs-review"><div class="semrs-review__head"><h3>' . esc_html( semrs_blocks_a( $a, 'item' ) ) . '</h3><div class="semrs-review__score"><span role="img" aria-label="' . esc_attr( $label ) . '">' . semrs_blocks_stars( $rating, 5, 20 ) . '</span><b>' . esc_html( number_format_i18n( $rating, 1 ) ) . '</b></div></div><div class="semrs-review__body">' . $pc . ( semrs_blocks_a( $a, 'summary' ) ? '<p class="semrs-review__verdict"><strong>' . esc_html__( 'Verdict:', 'semrs-blocks' ) . '</strong> ' . esc_html( $a['summary'] ) . '</p>' : '' ) . '</div></div>' . $ld;
}

/**
 * Breadcrumbs.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_breadcrumbs( $a ) {
	if ( function_exists( 'semrs_seo_breadcrumb_trail' ) && ! wp_is_json_request() ) {
		$trail = semrs_seo_breadcrumb_trail();
	} else {
		$trail = array( array( semrs_blocks_a( $a, 'home', 'Home' ), home_url( '/' ) ) );
		$pid   = get_the_ID();
		if ( ! $pid && isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- editor preview only reads the id.
			$pid = absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( $pid ) {
			foreach ( array_reverse( get_post_ancestors( $pid ) ) as $anc ) {
				$trail[] = array( get_the_title( $anc ), get_permalink( $anc ) );
			}
			if ( 'post' === get_post_type( $pid ) ) {
				$cats = get_the_category( $pid );
				if ( $cats ) {
					$trail[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
				}
			}
			$trail[] = array( wp_strip_all_tags( get_the_title( $pid ) ), '' );
		}
	}
	if ( isset( $trail[0] ) && semrs_blocks_a( $a, 'home' ) ) {
		$trail[0][0] = $a['home'];
	}
	$seps = array( 'chevron' => '›', 'slash' => '/', 'arrow' => '→', 'dot' => '·' );
	$sep  = $seps[ semrs_blocks_a( $a, 'separator', 'chevron' ) ] ?? '›';
	$css  = semrs_blocks_css_part( 'crumbs', '.semrs-crumbs ol{display:flex;flex-wrap:wrap;gap:.35rem;margin:0;padding:0;list-style:none;color:var(--b-muted);font-size:.92em}.semrs-crumbs li+li::before{content:var(--sep);margin-right:.35rem;opacity:.6}.semrs-crumbs a{color:var(--b-primary)}' );
	$last = count( $trail ) - 1;
	$li   = '';
	$ld   = array();
	foreach ( $trail as $i => $c ) {
		$li  .= $i === $last || '' === $c[1] ? '<li aria-current="page">' . esc_html( $c[0] ) . '</li>' : '<li><a href="' . esc_url( $c[1] ) . '">' . esc_html( $c[0] ) . '</a></li>';
		$item = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0] );
		if ( '' !== $c[1] ) {
			$item['item'] = $c[1];
		}
		$ld[] = $item;
	}
	$schema = ! empty( $a['schema'] ) ? '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $ld ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' : '';
	return $css . '<nav class="semrs-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'semrs-blocks' ) . '" style="--sep:\'' . esc_attr( $sep ) . '\'"><ol>' . $li . '</ol></nav>' . $schema;
}
