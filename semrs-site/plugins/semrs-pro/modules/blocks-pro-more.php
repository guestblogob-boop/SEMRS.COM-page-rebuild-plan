<?php
/**
 * More Pro blocks: popup, pricing switcher, sticky bar, before/after,
 * image hotspots, Lottie animation, logo marquee, video popup, content
 * switcher, data table, animated timeline, reviews slider and trust badges.
 * One small shared script (pro-blocks-more.js) loads only on pages that
 * use an interactive block; each block prints its own CSS once.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_blocks_registry' ) || ! function_exists( 'semrs_blocks_css_part' ) || ! function_exists( 'semrs_blocks_rows' ) || ! function_exists( 'semrs_blocks_stars' ) ) {
	return;
}

/**
 * Block definitions.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_pro_blocks_more( $blocks ) {
	$p = function ( $def ) {
		return array_merge( array( 'tier' => 'pro' ), $def );
	};

	$blocks['semrs/modal'] = $p(
		array(
			'title'       => __( 'Popup / Modal (Pro)', 'semrs-pro' ),
			'description' => __( 'A button that opens a popup with any blocks inside, or a popup that opens by itself after a delay.', 'semrs-pro' ),
			'icon'        => 'external',
			'keywords'    => array( 'popup', 'modal', 'lightbox', 'dialog', 'overlay' ),
			'render'      => 'semrs_pro_render_modal',
			'inner'       => true,
			'template'    => array( array( 'core/heading', array( 'level' => 3, 'content' => 'Popup title' ) ), array( 'core/paragraph', array( 'placeholder' => 'Popup content…' ) ) ),
			'controls'    => array(
				'trigger' => array( 'type' => 'text', 'label' => __( 'Button text', 'semrs-pro' ), 'default' => 'Open' ),
				'style'   => array( 'type' => 'select', 'label' => __( 'Button style', 'semrs-pro' ), 'default' => 'button', 'options' => array( 'button' => __( 'Button', 'semrs-pro' ), 'link' => __( 'Text link', 'semrs-pro' ), 'hidden' => __( 'No button (opens by itself)', 'semrs-pro' ) ) ),
				'delay'   => array( 'type' => 'range', 'label' => __( 'Open by itself after (seconds, 0 = never)', 'semrs-pro' ), 'default' => 0, 'min' => 0, 'max' => 120 ),
				'once'    => array( 'type' => 'toggle', 'label' => __( 'Open by itself only once per visitor', 'semrs-pro' ), 'default' => true ),
				'width'   => array( 'type' => 'range', 'label' => __( 'Popup width (px)', 'semrs-pro' ), 'default' => 560, 'min' => 300, 'max' => 1000 ),
			),
		)
	);

	$blocks['semrs/pricing-switch'] = $p(
		array(
			'title'       => __( 'Pricing Table with Switch (Pro)', 'semrs-pro' ),
			'description' => __( 'Several plans side by side with a monthly / yearly switch.', 'semrs-pro' ),
			'icon'        => 'money-alt',
			'keywords'    => array( 'pricing', 'plans', 'monthly', 'yearly', 'annual', 'table' ),
			'render'      => 'semrs_pro_render_pricing_switch',
			'controls'    => array(
				'plans'    => array( 'type' => 'textarea', 'label' => __( 'One plan per line: Name | Monthly | Yearly | features; separated; by semicolons | Button | Link | featured', 'semrs-pro' ), 'default' => "Starter | 19 | 190 | 1 site; Core SEO checks; Email support | Choose Starter | /pricing/\nGrowth | 49 | 490 | 5 sites; All SEO tools; Priority support | Choose Growth | /pricing/ | featured\nAgency | 99 | 990 | Unlimited sites; White label; Phone support | Choose Agency | /pricing/", 'help' => __( 'Write "featured" at the end of a line to highlight that plan. Start a feature with - to show it as not included.', 'semrs-pro' ) ),
				'currency' => array( 'type' => 'text', 'label' => __( 'Currency sign', 'semrs-pro' ), 'default' => '$' ),
				'monthly'  => array( 'type' => 'text', 'label' => __( 'Monthly label', 'semrs-pro' ), 'default' => 'Monthly' ),
				'yearly'   => array( 'type' => 'text', 'label' => __( 'Yearly label', 'semrs-pro' ), 'default' => 'Yearly' ),
				'save'     => array( 'type' => 'text', 'label' => __( 'Yearly note (optional)', 'semrs-pro' ), 'default' => '2 months free' ),
				'start'    => array( 'type' => 'select', 'label' => __( 'Show first', 'semrs-pro' ), 'default' => 'yearly', 'options' => array( 'monthly' => __( 'Monthly', 'semrs-pro' ), 'yearly' => __( 'Yearly', 'semrs-pro' ) ) ),
				'accent'   => array( 'type' => 'color', 'label' => __( 'Accent color', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/sticky-bar'] = $p(
		array(
			'title'       => __( 'Sticky Bar (Pro)', 'semrs-pro' ),
			'description' => __( 'A slim bar fixed to the top or bottom of the screen with a message and a button.', 'semrs-pro' ),
			'icon'        => 'align-full-width',
			'keywords'    => array( 'sticky', 'bar', 'announcement', 'notice', 'cta', 'banner' ),
			'render'      => 'semrs_pro_render_sticky_bar',
			'controls'    => array(
				'text'     => array( 'type' => 'text', 'label' => __( 'Message', 'semrs-pro' ), 'default' => 'Free SEO review this week only.' ),
				'button'   => array( 'type' => 'text', 'label' => __( 'Button text', 'semrs-pro' ), 'default' => 'Book now' ),
				'url'      => array( 'type' => 'url', 'label' => __( 'Button link', 'semrs-pro' ), 'default' => '' ),
				'position' => array( 'type' => 'select', 'label' => __( 'Position', 'semrs-pro' ), 'default' => 'bottom', 'options' => array( 'bottom' => __( 'Bottom', 'semrs-pro' ), 'top' => __( 'Top', 'semrs-pro' ) ) ),
				'after'    => array( 'type' => 'range', 'label' => __( 'Show after scrolling (px, 0 = at once)', 'semrs-pro' ), 'default' => 300, 'min' => 0, 'max' => 3000 ),
				'close'    => array( 'type' => 'toggle', 'label' => __( 'Visitors can close it (remembered for 7 days)', 'semrs-pro' ), 'default' => true ),
				'bg'       => array( 'type' => 'color', 'label' => __( 'Background', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/before-after'] = $p(
		array(
			'title'       => __( 'Before / After (Pro)', 'semrs-pro' ),
			'description' => __( 'Drag a handle to compare two images.', 'semrs-pro' ),
			'icon'        => 'image-flip-horizontal',
			'keywords'    => array( 'before', 'after', 'compare', 'comparison', 'slider', 'image' ),
			'render'      => 'semrs_pro_render_before_after',
			'controls'    => array(
				'before'      => array( 'type' => 'media', 'label' => __( 'Before image', 'semrs-pro' ), 'default' => 0 ),
				'after'       => array( 'type' => 'media', 'label' => __( 'After image (same size)', 'semrs-pro' ), 'default' => 0 ),
				'beforeLabel' => array( 'type' => 'text', 'label' => __( 'Before label', 'semrs-pro' ), 'default' => 'Before' ),
				'afterLabel'  => array( 'type' => 'text', 'label' => __( 'After label', 'semrs-pro' ), 'default' => 'After' ),
				'start'       => array( 'type' => 'range', 'label' => __( 'Start position (%)', 'semrs-pro' ), 'default' => 50, 'min' => 5, 'max' => 95 ),
			),
		)
	);

	$blocks['semrs/hotspots'] = $p(
		array(
			'title'       => __( 'Image Hotspots (Pro)', 'semrs-pro' ),
			'description' => __( 'Clickable points on an image that open a short note.', 'semrs-pro' ),
			'icon'        => 'location',
			'keywords'    => array( 'hotspot', 'image', 'points', 'tooltip', 'map', 'product tour' ),
			'render'      => 'semrs_pro_render_hotspots',
			'controls'    => array(
				'image' => array( 'type' => 'media', 'label' => __( 'Image', 'semrs-pro' ), 'default' => 0 ),
				'spots' => array( 'type' => 'textarea', 'label' => __( 'One point per line: left % | top % | Title | Text', 'semrs-pro' ), 'default' => "25 | 30 | Fast pages | Every page loads in under a second.\n70 | 60 | Clear calls to action | Visitors always know the next step.", 'help' => __( 'Left and top are percentages from the image\'s top-left corner.', 'semrs-pro' ) ),
				'color' => array( 'type' => 'color', 'label' => __( 'Point color', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/lottie'] = $p(
		array(
			'title'       => __( 'Lottie Animation (Pro)', 'semrs-pro' ),
			'description' => __( 'Lightweight vector animation from a Lottie .json file.', 'semrs-pro' ),
			'icon'        => 'controls-play',
			'keywords'    => array( 'lottie', 'animation', 'json', 'motion', 'vector' ),
			'render'      => 'semrs_pro_render_lottie',
			'controls'    => array(
				'src'      => array( 'type' => 'url', 'label' => __( 'Lottie file URL (.json)', 'semrs-pro' ), 'default' => '', 'help' => __( 'Upload the .json to the Media Library (allow JSON uploads) or use a free file from LottieFiles.', 'semrs-pro' ) ),
				'height'   => array( 'type' => 'range', 'label' => __( 'Height (px)', 'semrs-pro' ), 'default' => 320, 'min' => 80, 'max' => 900 ),
				'loop'     => array( 'type' => 'toggle', 'label' => __( 'Loop', 'semrs-pro' ), 'default' => true ),
				'hover'    => array( 'type' => 'toggle', 'label' => __( 'Play only on hover', 'semrs-pro' ), 'default' => false ),
				'label'    => array( 'type' => 'text', 'label' => __( 'Description (for screen readers)', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/marquee'] = $p(
		array(
			'title'       => __( 'Logo / Text Marquee (Pro)', 'semrs-pro' ),
			'description' => __( 'Logos or short phrases that scroll sideways in an endless loop.', 'semrs-pro' ),
			'icon'        => 'leftright',
			'keywords'    => array( 'marquee', 'scrolling', 'logos', 'ticker', 'carousel', 'clients' ),
			'render'      => 'semrs_pro_render_marquee',
			'controls'    => array(
				'images'    => array( 'type' => 'gallery', 'label' => __( 'Logos (optional)', 'semrs-pro' ) ),
				'items'     => array( 'type' => 'textarea', 'label' => __( 'Or text items, one per line', 'semrs-pro' ), 'default' => "Technical SEO\nContent\nLocal SEO\nSpeed\nLink building" ),
				'speed'     => array( 'type' => 'range', 'label' => __( 'Seconds per loop', 'semrs-pro' ), 'default' => 30, 'min' => 8, 'max' => 120 ),
				'reverse'   => array( 'type' => 'toggle', 'label' => __( 'Scroll left to right', 'semrs-pro' ), 'default' => false ),
				'height'    => array( 'type' => 'range', 'label' => __( 'Logo height (px)', 'semrs-pro' ), 'default' => 40, 'min' => 20, 'max' => 120 ),
				'grayscale' => array( 'type' => 'toggle', 'label' => __( 'Gray logos', 'semrs-pro' ), 'default' => true ),
			),
		)
	);

	$blocks['semrs/video-popup'] = $p(
		array(
			'title'       => __( 'Video Popup (Pro)', 'semrs-pro' ),
			'description' => __( 'A thumbnail with a play button; the YouTube or Vimeo video opens in a popup only when clicked.', 'semrs-pro' ),
			'icon'        => 'video-alt3',
			'keywords'    => array( 'video', 'youtube', 'vimeo', 'popup', 'lightbox', 'play' ),
			'render'      => 'semrs_pro_render_video_popup',
			'controls'    => array(
				'url'   => array( 'type' => 'url', 'label' => __( 'YouTube or Vimeo link', 'semrs-pro' ), 'default' => '' ),
				'thumb' => array( 'type' => 'media', 'label' => __( 'Thumbnail (empty = YouTube\'s own)', 'semrs-pro' ), 'default' => 0 ),
				'label' => array( 'type' => 'text', 'label' => __( 'Caption (optional)', 'semrs-pro' ), 'default' => '' ),
				'ratio' => array( 'type' => 'select', 'label' => __( 'Shape', 'semrs-pro' ), 'default' => '16/9', 'options' => array( '16/9' => '16:9', '4/3' => '4:3', '1/1' => '1:1', '9/16' => __( '9:16 (Shorts)', 'semrs-pro' ) ) ),
			),
		)
	);

	$blocks['semrs/switcher'] = $p(
		array(
			'title'       => __( 'Content Switcher (Pro)', 'semrs-pro' ),
			'description' => __( 'A switch that toggles between two contents, e.g. "For shops / For agencies".', 'semrs-pro' ),
			'icon'        => 'randomize',
			'keywords'    => array( 'switch', 'toggle', 'content', 'switcher', 'two' ),
			'render'      => 'semrs_pro_render_switcher',
			'inner'       => true,
			'allowed'     => array( 'semrs/switch-panel' ),
			'template'    => array( array( 'semrs/switch-panel', array( 'title' => 'Option one' ) ), array( 'semrs/switch-panel', array( 'title' => 'Option two' ) ) ),
			'controls'    => array(
				'accent' => array( 'type' => 'color', 'label' => __( 'Switch color', 'semrs-pro' ), 'default' => '' ),
				'position' => array( 'type' => 'select', 'label' => __( 'Switch alignment', 'semrs-pro' ), 'default' => 'center', 'options' => array( 'left' => __( 'Left', 'semrs-pro' ), 'center' => __( 'Center', 'semrs-pro' ) ) ),
			),
		)
	);
	$blocks['semrs/switch-panel'] = $p(
		array(
			'title'    => __( 'Switcher Panel', 'semrs-pro' ),
			'icon'     => 'randomize',
			'render'   => 'semrs_pro_render_switch_panel',
			'inner'    => true,
			'parent'   => array( 'semrs/switcher' ),
			'controls' => array(
				'title' => array( 'type' => 'text', 'label' => __( 'Switch label', 'semrs-pro' ), 'default' => 'Option' ),
			),
		)
	);

	$blocks['semrs/data-table'] = $p(
		array(
			'title'       => __( 'Data Table (Pro)', 'semrs-pro' ),
			'description' => __( 'Sortable, searchable table from pasted rows or CSV.', 'semrs-pro' ),
			'icon'        => 'database',
			'keywords'    => array( 'table', 'data', 'csv', 'sort', 'search', 'spreadsheet' ),
			'render'      => 'semrs_pro_render_data_table',
			'controls'    => array(
				'rows'   => array( 'type' => 'textarea', 'label' => __( 'Rows: first line is the header; cells separated by | or commas', 'semrs-pro' ), 'default' => "Plugin | Version | Size (KB)\nSEMRS Core | 1.3 | 170\nSEMRS Blocks | 1.3 | 7000\nSEMRS Tools | 1.3 | 300" ),
				'search' => array( 'type' => 'toggle', 'label' => __( 'Search box', 'semrs-pro' ), 'default' => true ),
				'sort'   => array( 'type' => 'toggle', 'label' => __( 'Sort by clicking a column', 'semrs-pro' ), 'default' => true ),
				'caption' => array( 'type' => 'text', 'label' => __( 'Caption (optional)', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/timeline-pro'] = $p(
		array(
			'title'       => __( 'Animated Timeline (Pro)', 'semrs-pro' ),
			'description' => __( 'Timeline with icons; each event slides in as it scrolls into view.', 'semrs-pro' ),
			'icon'        => 'backup',
			'keywords'    => array( 'timeline', 'animated', 'history', 'roadmap', 'milestones' ),
			'render'      => 'semrs_pro_render_timeline',
			'controls'    => array(
				'items'  => array( 'type' => 'textarea', 'label' => __( 'One per line: Date | Title | Text | icon', 'semrs-pro' ), 'default' => "2024 | Started | Where it began. | rocket\n2025 | Grew | What changed. | trending-up\n2026 | Today | Where we are now. | award" ),
				'accent' => array( 'type' => 'color', 'label' => __( 'Accent color', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/reviews'] = $p(
		array(
			'title'       => __( 'Reviews Slider (Pro)', 'semrs-pro' ),
			'description' => __( 'Real customer reviews in a swipeable slider with stars and links to the originals.', 'semrs-pro' ),
			'icon'        => 'format-quote',
			'keywords'    => array( 'reviews', 'testimonials', 'slider', 'carousel', 'stars' ),
			'render'      => 'semrs_pro_render_reviews',
			'controls'    => array(
				'items'    => array( 'type' => 'textarea', 'label' => __( 'One review per line: Name | Role or company | Stars (1–5) | Review text | Link to the original', 'semrs-pro' ), 'default' => '', 'help' => __( 'Use only real reviews and link to where they were posted (Google, Trustpilot…).', 'semrs-pro' ) ),
				'perView'  => array( 'type' => 'range', 'label' => __( 'Reviews visible (desktop)', 'semrs-pro' ), 'default' => 3, 'min' => 1, 'max' => 4 ),
				'autoplay' => array( 'type' => 'toggle', 'label' => __( 'Autoplay (pauses on hover)', 'semrs-pro' ), 'default' => false ),
				'schema'   => array( 'type' => 'toggle', 'label' => __( 'Add Review schema', 'semrs-pro' ), 'default' => false, 'help' => __( 'Only for reviews of a product. Google does not show stars for reviews a business shows about itself.', 'semrs-pro' ) ),
				'product'  => array( 'type' => 'text', 'label' => __( 'Product name (for schema)', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);

	$blocks['semrs/badges'] = $p(
		array(
			'title'       => __( 'Trust Badges (Pro)', 'semrs-pro' ),
			'description' => __( 'A row of guarantees: secure checkout, money-back, support, and more.', 'semrs-pro' ),
			'icon'        => 'shield',
			'keywords'    => array( 'trust', 'badges', 'guarantee', 'secure', 'money back' ),
			'render'      => 'semrs_pro_render_badges',
			'controls'    => array(
				'items'   => array( 'type' => 'textarea', 'label' => __( 'One per line: icon | Title | Short text', 'semrs-pro' ), 'default' => "lock | Secure checkout | Encrypted payments\nshield | 14-day refund | No questions asked\nchat | Real support | Answers within a day" ),
				'style'   => array( 'type' => 'select', 'label' => __( 'Style', 'semrs-pro' ), 'default' => 'cards', 'options' => array( 'cards' => __( 'Cards', 'semrs-pro' ), 'inline' => __( 'Inline row', 'semrs-pro' ) ) ),
				'color'   => array( 'type' => 'color', 'label' => __( 'Icon color', 'semrs-pro' ), 'default' => '' ),
			),
		)
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_pro_blocks_more', 21 );

/**
 * Shared front-end script for the interactive Pro blocks.
 */
function semrs_pro_more_js() {
	semrs_pro_blocks_script( 'semrs-pro-blocks-more', 'js/pro-blocks-more.js' );
}

/**
 * Popup / modal.
 *
 * @param array  $a       Attributes.
 * @param mixed  $block   Block.
 * @param string $content Inner HTML.
 * @return string
 */
function semrs_pro_render_modal( $a, $block = null, $content = '' ) {
	static $n = 0;
	$n++;
	semrs_pro_more_js();
	$css   = semrs_blocks_css_part( 'modal', '.semrs-modal{width:min(var(--mw,560px),calc(100vw - 2rem));max-height:calc(100vh - 2rem);padding:clamp(1.25rem,4vw,2rem);border:0;border-radius:var(--b-radius);background:var(--sp-bg,#fff);color:var(--b-text);box-shadow:0 20px 60px rgba(0,0,0,.3)}.semrs-modal::backdrop{background:rgba(6,15,30,.6)}.semrs-modal__x{position:absolute;top:.5rem;right:.5rem;width:40px;height:40px;border:0;background:none;color:inherit;font-size:1.6rem;line-height:1;cursor:pointer}.semrs-modal-open--link{padding:0;border:0;background:none;color:var(--b-primary);font:inherit;text-decoration:underline;cursor:pointer}' );
	$id    = 'semrs-modal-' . $n;
	$style = semrs_blocks_a( $a, 'style', 'button' );
	$btn   = 'hidden' === $style ? '' : '<button type="button" class="' . ( 'link' === $style ? 'semrs-modal-open--link' : 'semrs-btn' ) . '" data-semrs-open="' . esc_attr( $id ) . '" aria-haspopup="dialog">' . esc_html( semrs_blocks_a( $a, 'trigger', 'Open' ) ) . '</button>';
	$delay = (int) semrs_blocks_a( $a, 'delay', 0 );
	return $css . $btn . '<dialog class="semrs-modal" id="' . esc_attr( $id ) . '"' . ( $delay ? ' data-delay="' . $delay . '"' . ( ! empty( $a['once'] ) ? ' data-once="1"' : '' ) : '' ) . semrs_blocks_style( array( '--mw' => max( 300, min( 1000, (int) semrs_blocks_a( $a, 'width', 560 ) ) ) . 'px' ) ) . '><button type="button" class="semrs-modal__x" data-semrs-close aria-label="' . esc_attr__( 'Close', 'semrs-pro' ) . '">&times;</button>' . $content . '</dialog>';
}

/**
 * Pricing table with monthly / yearly switch.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_pricing_switch( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'plans' ) );
	if ( ! $rows ) {
		return '';
	}
	semrs_pro_more_js();
	$css   = semrs_blocks_css_part( 'pswitch', '.semrs-ps{--pa:var(--b-accent)}.semrs-ps__toggle{display:flex;justify-content:center;align-items:center;gap:.75rem;margin-bottom:1.5rem;font-weight:700}.semrs-ps__sw{position:relative;width:56px;height:30px;border:0;border-radius:999px;background:var(--b-line);cursor:pointer}.semrs-ps__sw::after{content:"";position:absolute;top:3px;left:3px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform .2s}.semrs-ps__sw[aria-checked=true]{background:var(--pa)}.semrs-ps__sw[aria-checked=true]::after{transform:translateX(26px)}.semrs-ps__save{padding:.15rem .6rem;border-radius:999px;background:color-mix(in srgb,var(--pa) 20%,transparent);font-size:.85em}.semrs-ps__grid{display:grid;gap:1.25rem}@media(min-width:782px){.semrs-ps__grid{grid-template-columns:repeat(auto-fit,minmax(15rem,1fr))}}.semrs-ps__plan{display:flex;flex-direction:column;padding:1.5rem;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-surface);color:var(--b-text)}.semrs-ps__plan.is-featured{border:2px solid var(--pa);box-shadow:0 10px 30px rgba(0,0,0,.08)}.semrs-ps__plan h3{margin:0 0 .5rem}.semrs-ps__price{margin:0 0 1rem;font-size:2.2rem;font-weight:800;color:var(--b-primary)}.semrs-ps__price small{font-size:.4em;font-weight:600;color:var(--b-muted)}.semrs-ps__plan ul{flex:1;list-style:none;margin:0 0 1.25rem;padding:0}.semrs-ps__plan li{display:flex;gap:.4rem;margin-bottom:.45rem}.semrs-ps__plan li .semrs-icon{color:#16a34a}.semrs-ps__plan li.is-no{color:var(--b-muted)}.semrs-ps__plan li.is-no .semrs-icon{color:#dc2626}.semrs-ps[data-period=monthly] .semrs-ps__y,.semrs-ps[data-period=yearly] .semrs-ps__m{display:none}' );
	$cur   = semrs_blocks_a( $a, 'currency', '$' );
	$start = 'monthly' === semrs_blocks_a( $a, 'start' ) ? 'monthly' : 'yearly';
	$plans = '';
	foreach ( $rows as $r ) {
		$feat = '';
		foreach ( array_filter( array_map( 'trim', explode( ';', $r[3] ?? '' ) ) ) as $f ) {
			$no    = 0 === strpos( $f, '-' );
			$feat .= '<li' . ( $no ? ' class="is-no"' : '' ) . '>' . semrs_blocks_icon_svg( $no ? 'x' : 'check', 18 ) . '<span>' . esc_html( ltrim( $f, '- ' ) ) . '</span></li>';
		}
		$featured = isset( $r[6] ) && 'featured' === strtolower( $r[6] );
		$plans   .= '<div class="semrs-ps__plan' . ( $featured ? ' is-featured' : '' ) . '"><h3>' . esc_html( $r[0] ) . '</h3><p class="semrs-ps__price"><span class="semrs-ps__m">' . esc_html( $cur . ( $r[1] ?? '' ) ) . '<small> / ' . esc_html__( 'month', 'semrs-pro' ) . '</small></span><span class="semrs-ps__y">' . esc_html( $cur . ( $r[2] ?? '' ) ) . '<small> / ' . esc_html__( 'year', 'semrs-pro' ) . '</small></span></p><ul>' . $feat . '</ul>' . ( ! empty( $r[4] ) ? '<a class="semrs-btn" href="' . esc_url( $r[5] ?? '#' ) . '">' . esc_html( $r[4] ) . '</a>' : '' ) . '</div>';
	}
	return $css . '<div class="semrs-ps" data-period="' . $start . '"' . semrs_blocks_style( array( '--pa' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ) . '><div class="semrs-ps__toggle"><span>' . esc_html( semrs_blocks_a( $a, 'monthly', 'Monthly' ) ) . '</span><button type="button" class="semrs-ps__sw" role="switch" aria-checked="' . ( 'yearly' === $start ? 'true' : 'false' ) . '" aria-label="' . esc_attr( semrs_blocks_a( $a, 'yearly', 'Yearly' ) ) . '"></button><span>' . esc_html( semrs_blocks_a( $a, 'yearly', 'Yearly' ) ) . '</span>' . ( semrs_blocks_a( $a, 'save' ) ? '<span class="semrs-ps__save">' . esc_html( $a['save'] ) . '</span>' : '' ) . '</div><div class="semrs-ps__grid">' . $plans . '</div></div>';
}

/**
 * Sticky bar.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_sticky_bar( $a ) {
	$editor = wp_is_json_request();
	if ( ! $editor ) {
		semrs_pro_more_js();
	}
	$css = semrs_blocks_css_part( 'sbar', '.semrs-sbar{position:fixed;left:0;right:0;z-index:9990;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.5rem 1rem;padding:.6rem 3rem .6rem 1rem;background:var(--sb,var(--b-primary));color:#fff;box-shadow:0 -2px 12px rgba(0,0,0,.15);transition:transform .3s}.semrs-sbar--bottom{bottom:0}.semrs-sbar--top{top:0}.semrs-sbar[hidden]{display:none}.semrs-sbar.is-off.semrs-sbar--bottom{transform:translateY(110%)}.semrs-sbar.is-off.semrs-sbar--top{transform:translateY(-110%)}.semrs-sbar .semrs-btn{min-height:38px;padding:.35rem 1rem}.semrs-sbar__x{position:absolute;right:.5rem;top:50%;transform:translateY(-50%);width:36px;height:36px;border:0;background:none;color:inherit;font-size:1.4rem;cursor:pointer}.semrs-sbar--editor{position:relative}' );
	$pos = 'top' === semrs_blocks_a( $a, 'position' ) ? 'top' : 'bottom';
	$url = semrs_blocks_a( $a, 'url', '#' );
	return $css . '<div class="semrs-sbar semrs-sbar--' . $pos . ( $editor ? ' semrs-sbar--editor' : ' is-off' ) . '" data-after="' . (int) semrs_blocks_a( $a, 'after', 0 ) . '"' . semrs_blocks_style( array( '--sb' => semrs_blocks_color( semrs_blocks_a( $a, 'bg' ) ) ) ) . ' role="region" aria-label="' . esc_attr__( 'Announcement', 'semrs-pro' ) . '"><span>' . esc_html( semrs_blocks_a( $a, 'text' ) ) . '</span>' . ( semrs_blocks_a( $a, 'button' ) ? '<a class="semrs-btn" href="' . esc_url( $url ) . '">' . esc_html( $a['button'] ) . '</a>' : '' ) . ( ! empty( $a['close'] ) ? '<button type="button" class="semrs-sbar__x" data-semrs-sbar-close aria-label="' . esc_attr__( 'Close', 'semrs-pro' ) . '">&times;</button>' : '' ) . '</div>';
}

/**
 * Before / after comparison.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_before_after( $a ) {
	$b = (int) semrs_blocks_a( $a, 'before', 0 );
	$f = (int) semrs_blocks_a( $a, 'after', 0 );
	if ( ! $b || ! $f ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Choose a before and an after image in the block settings.', 'semrs-pro' ) . '</p>' : '';
	}
	semrs_pro_more_js();
	$css   = semrs_blocks_css_part( 'ba', '.semrs-ba{position:relative;overflow:hidden;border-radius:var(--b-radius);--pos:50%;user-select:none}.semrs-ba img{display:block;width:100%;height:auto}.semrs-ba__after{position:absolute;inset:0;clip-path:inset(0 0 0 var(--pos))}.semrs-ba__after img{height:100%;object-fit:cover}.semrs-ba__line{position:absolute;top:0;bottom:0;left:var(--pos);width:3px;margin-left:-1.5px;background:#fff;pointer-events:none}.semrs-ba__line::after{content:"\2194";position:absolute;top:50%;left:50%;display:grid;place-items:center;width:42px;height:42px;margin:-21px 0 0 -21px;border-radius:50%;background:#fff;color:#111;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,.3)}.semrs-ba__range{position:absolute;inset:0;width:100%;height:100%;margin:0;opacity:0;cursor:ew-resize}.semrs-ba__lab{position:absolute;bottom:.75rem;padding:.2rem .6rem;border-radius:4px;background:rgba(0,0,0,.6);color:#fff;font-size:.85em;pointer-events:none}.semrs-ba__lab--b{left:.75rem}.semrs-ba__lab--a{right:.75rem}' );
	$start = max( 5, min( 95, (int) semrs_blocks_a( $a, 'start', 50 ) ) );
	return $css . '<div class="semrs-ba" style="--pos:' . $start . '%">' . wp_get_attachment_image( $b, 'large' ) . '<div class="semrs-ba__after">' . wp_get_attachment_image( $f, 'large' ) . '</div><span class="semrs-ba__line" aria-hidden="true"></span>' . ( semrs_blocks_a( $a, 'beforeLabel' ) ? '<span class="semrs-ba__lab semrs-ba__lab--b">' . esc_html( $a['beforeLabel'] ) . '</span>' : '' ) . ( semrs_blocks_a( $a, 'afterLabel' ) ? '<span class="semrs-ba__lab semrs-ba__lab--a">' . esc_html( $a['afterLabel'] ) . '</span>' : '' ) . '<input class="semrs-ba__range" type="range" min="0" max="100" value="' . $start . '" aria-label="' . esc_attr__( 'Move to compare the before and after images', 'semrs-pro' ) . '"></div>';
}

/**
 * Image hotspots (works without JavaScript: native <details>).
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_hotspots( $a ) {
	$img = (int) semrs_blocks_a( $a, 'image', 0 );
	if ( ! $img ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Choose an image in the block settings.', 'semrs-pro' ) . '</p>' : '';
	}
	$css   = semrs_blocks_css_part( 'hotspots', '.semrs-hs{position:relative}.semrs-hs>img{display:block;width:100%;height:auto;border-radius:var(--b-radius)}.semrs-hs details{position:absolute;transform:translate(-50%,-50%)}.semrs-hs summary{display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:var(--hc,var(--b-accent));color:var(--b-on-accent);font-weight:800;list-style:none;cursor:pointer;box-shadow:0 0 0 6px color-mix(in srgb,var(--hc,var(--b-accent)) 35%,transparent)}.semrs-hs summary::-webkit-details-marker{display:none}.semrs-hs details[open] summary{transform:rotate(45deg)}.semrs-hs__tip{position:absolute;left:50%;top:calc(100% + 10px);z-index:5;width:min(16rem,70vw);transform:translateX(-50%);padding:.8rem 1rem;border-radius:8px;background:var(--sp-bg,#fff);color:var(--b-text);box-shadow:0 8px 24px rgba(0,0,0,.2)}.semrs-hs__tip b{display:block;margin-bottom:.2rem}' );
	$spots = '';
	foreach ( semrs_blocks_rows( semrs_blocks_a( $a, 'spots' ) ) as $s ) {
		$x      = max( 0, min( 100, (float) ( $s[0] ?? 50 ) ) );
		$y      = max( 0, min( 100, (float) ( $s[1] ?? 50 ) ) );
		$spots .= '<details style="left:' . $x . '%;top:' . $y . '%"><summary aria-label="' . esc_attr( $s[2] ?? '' ) . '">+</summary><div class="semrs-hs__tip"><b>' . esc_html( $s[2] ?? '' ) . '</b>' . esc_html( $s[3] ?? '' ) . '</div></details>';
	}
	return $css . '<div class="semrs-hs"' . semrs_blocks_style( array( '--hc' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ) ) ) . '>' . wp_get_attachment_image( $img, 'large' ) . $spots . '</div>';
}

/**
 * Lottie animation (lottie-web from cdnjs, loaded only on pages with this block).
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_lottie( $a ) {
	$src = esc_url( semrs_blocks_a( $a, 'src' ) );
	if ( ! $src || ! preg_match( '/\.json(\?|$)/i', $src ) ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add the URL of a Lottie .json file.', 'semrs-pro' ) . '</p>' : '';
	}
	if ( ! wp_script_is( 'semrs-lottie', 'registered' ) ) {
		wp_register_script( 'semrs-lottie', 'https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie_light.min.js', array(), '5.12.2', array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	wp_enqueue_script( 'semrs-lottie' );
	semrs_pro_more_js();
	$label = semrs_blocks_a( $a, 'label' );
	return '<div class="semrs-lottie" data-src="' . $src . '" data-loop="' . ( ! empty( $a['loop'] ) ? '1' : '0' ) . '" data-hover="' . ( ! empty( $a['hover'] ) ? '1' : '0' ) . '" style="height:' . max( 80, min( 900, (int) semrs_blocks_a( $a, 'height', 320 ) ) ) . 'px"' . ( $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"' ) . '></div>';
}

/**
 * Marquee.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_marquee( $a ) {
	$ids   = array_filter( array_map( 'intval', (array) semrs_blocks_a( $a, 'images', array() ) ) );
	$items = '';
	if ( $ids ) {
		foreach ( $ids as $id ) {
			$items .= '<li>' . wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy' ) ) . '</li>';
		}
	} else {
		foreach ( semrs_blocks_lines( semrs_blocks_a( $a, 'items' ) ) as $t ) {
			$items .= '<li><span>' . esc_html( $t ) . '</span></li>';
		}
	}
	if ( '' === $items ) {
		return '';
	}
	$css = semrs_blocks_css_part( 'marquee', '.semrs-mq{overflow:hidden;-webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent)}.semrs-mq__track{display:flex;width:max-content;animation:semrs-mq var(--mqs,30s) linear infinite}.semrs-mq--rev .semrs-mq__track{animation-direction:reverse}.semrs-mq:hover .semrs-mq__track{animation-play-state:paused}.semrs-mq ul{display:flex;align-items:center;gap:3rem;margin:0;padding:0 1.5rem;list-style:none}.semrs-mq img{display:block;width:auto;height:var(--mqh,40px);object-fit:contain}.semrs-mq--gray img{filter:grayscale(1);opacity:.7}.semrs-mq span{font-size:1.25rem;font-weight:700;white-space:nowrap;color:var(--b-primary)}@keyframes semrs-mq{to{transform:translateX(-50%)}}@media(prefers-reduced-motion:reduce){.semrs-mq__track{animation:none;flex-wrap:wrap;width:auto}.semrs-mq ul[aria-hidden]{display:none}}' );
	return $css . '<div class="semrs-mq' . ( ! empty( $a['reverse'] ) ? ' semrs-mq--rev' : '' ) . ( ! empty( $a['grayscale'] ) ? ' semrs-mq--gray' : '' ) . '"' . semrs_blocks_style( array( '--mqs' => max( 8, min( 120, (int) semrs_blocks_a( $a, 'speed', 30 ) ) ) . 's', '--mqh' => (int) semrs_blocks_a( $a, 'height', 40 ) . 'px' ) ) . '><div class="semrs-mq__track"><ul>' . $items . '</ul><ul aria-hidden="true">' . $items . '</ul></div></div>';
}

/**
 * Embed URL for a YouTube or Vimeo link.
 *
 * @param string $url Link.
 * @return array{0:string,1:string} [embed URL, YouTube id].
 */
function semrs_pro_video_embed( $url ) {
	if ( preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return array( 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0', $m[1] );
	}
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
		return array( 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1', '' );
	}
	return array( '', '' );
}

/**
 * Video popup.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_video_popup( $a ) {
	list( $embed, $yt ) = semrs_pro_video_embed( (string) semrs_blocks_a( $a, 'url' ) );
	if ( ! $embed ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Paste a YouTube or Vimeo link in the block settings.', 'semrs-pro' ) . '</p>' : '';
	}
	semrs_pro_more_js();
	$css   = semrs_blocks_css_part( 'vpop', '.semrs-vp{position:relative;display:block;width:100%;padding:0;border:0;border-radius:var(--b-radius);overflow:hidden;background:#000;cursor:pointer;aspect-ratio:var(--vr,16/9)}.semrs-vp img{display:block;width:100%;height:100%;object-fit:cover;opacity:.85;transition:opacity .2s}.semrs-vp:hover img{opacity:1}.semrs-vp__play{position:absolute;top:50%;left:50%;display:grid;place-items:center;width:72px;height:72px;margin:-36px 0 0 -36px;border-radius:50%;background:var(--b-accent);color:var(--b-on-accent)}.semrs-vp__cap{margin:.5rem 0 0;color:var(--b-muted);font-size:.92em}.semrs-vdlg{width:min(1000px,calc(100vw - 2rem));padding:0;border:0;background:#000}.semrs-vdlg::backdrop{background:rgba(0,0,0,.85)}.semrs-vdlg iframe{display:block;width:100%;aspect-ratio:var(--vr,16/9);border:0}.semrs-vdlg .semrs-modal__x{top:-44px;right:0;color:#fff}' );
	$thumb = (int) semrs_blocks_a( $a, 'thumb', 0 ) ? wp_get_attachment_image( (int) $a['thumb'], 'large', false, array( 'alt' => '' ) ) : ( $yt ? '<img src="https://i.ytimg.com/vi/' . esc_attr( $yt ) . '/hqdefault.jpg" alt="" loading="lazy" width="480" height="360">' : '' );
	$ratio = in_array( semrs_blocks_a( $a, 'ratio', '16/9' ), array( '16/9', '4/3', '1/1', '9/16' ), true ) ? $a['ratio'] : '16/9';
	$label = semrs_blocks_a( $a, 'label' );
	return $css . '<button type="button" class="semrs-vp" data-semrs-video="' . esc_url( $embed ) . '" style="--vr:' . esc_attr( $ratio ) . '" aria-label="' . esc_attr( $label ? $label : __( 'Play video', 'semrs-pro' ) ) . '">' . $thumb . '<span class="semrs-vp__play">' . semrs_blocks_icon_svg( 'play', 30 ) . '</span></button>' . ( $label ? '<p class="semrs-vp__cap">' . esc_html( $label ) . '</p>' : '' );
}

/**
 * Content switcher.
 *
 * @param array  $a       Attributes.
 * @param mixed  $block   Block.
 * @param string $content Inner HTML.
 * @return string
 */
function semrs_pro_render_switcher( $a, $block = null, $content = '' ) {
	$kids = $block instanceof WP_Block ? $block->parsed_block['innerBlocks'] : array();
	if ( count( $kids ) < 2 ) {
		return $content;
	}
	semrs_pro_more_js();
	$css = semrs_blocks_css_part( 'switcher', '.semrs-sw__bar{display:flex;justify-content:center;margin-bottom:1.25rem}.semrs-sw--left .semrs-sw__bar{justify-content:flex-start}.semrs-sw__bar div{display:inline-flex;padding:4px;border-radius:999px;background:var(--b-surface);border:1px solid var(--b-line)}.semrs-sw__bar button{padding:.55rem 1.25rem;border:0;border-radius:999px;background:none;color:var(--b-text);font:inherit;font-weight:700;cursor:pointer}.semrs-sw__bar button[aria-pressed=true]{background:var(--swa,var(--b-primary));color:#fff}.semrs-sw.is-ready .semrs-swp__title{display:none}' );
	$btns = '';
	foreach ( array_slice( $kids, 0, 2 ) as $i => $k ) {
		$btns .= '<button type="button" aria-pressed="' . ( 0 === $i ? 'true' : 'false' ) . '">' . esc_html( $k['attrs']['title'] ?? 'Option' ) . '</button>';
	}
	return $css . '<div class="semrs-sw semrs-sw--' . ( 'left' === semrs_blocks_a( $a, 'position' ) ? 'left' : 'center' ) . '"' . semrs_blocks_style( array( '--swa' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ) . '><div class="semrs-sw__bar"><div role="group">' . $btns . '</div></div>' . $content . '</div>';
}

/**
 * One switcher panel.
 *
 * @param array  $a       Attributes.
 * @param mixed  $block   Block.
 * @param string $content Inner HTML.
 * @return string
 */
function semrs_pro_render_switch_panel( $a, $block = null, $content = '' ) {
	return '<div class="semrs-swp"><p class="semrs-swp__title"><strong>' . esc_html( semrs_blocks_a( $a, 'title', 'Option' ) ) . '</strong></p>' . $content . '</div>';
}

/**
 * Data table.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_data_table( $a ) {
	$lines = semrs_blocks_lines( semrs_blocks_a( $a, 'rows' ) );
	if ( count( $lines ) < 2 ) {
		return '';
	}
	$sep  = false !== strpos( $lines[0], '|' ) ? '|' : ',';
	$rows = array();
	foreach ( $lines as $l ) {
		$rows[] = '|' === $sep ? array_map( 'trim', explode( '|', $l ) ) : array_map( 'trim', str_getcsv( $l ) );
	}
	if ( ! empty( $a['search'] ) || ! empty( $a['sort'] ) ) {
		semrs_pro_more_js();
	}
	$css  = semrs_blocks_css_part( 'dtable', '.semrs-dt__search{width:100%;max-width:20rem;margin-bottom:.75rem;padding:.55rem .8rem;border:1px solid var(--b-line);border-radius:8px;font:inherit}.semrs-dt__wrap{overflow-x:auto;border:1px solid var(--b-line);border-radius:var(--b-radius)}.semrs-dt table{width:100%;border-collapse:collapse;color:var(--b-text)}.semrs-dt caption{padding:.6rem;text-align:left;font-weight:700}.semrs-dt th,.semrs-dt td{padding:.65rem .9rem;border-bottom:1px solid var(--b-line);text-align:left}.semrs-dt thead th{background:var(--b-surface)}.semrs-dt th button{all:unset;cursor:pointer;font-weight:700}.semrs-dt th button::after{content:" \2195";opacity:.4}.semrs-dt th[aria-sort=ascending] button::after{content:" \2191";opacity:1}.semrs-dt th[aria-sort=descending] button::after{content:" \2193";opacity:1}' );
	$head = array_shift( $rows );
	$th   = '';
	foreach ( $head as $h ) {
		$th .= '<th scope="col">' . ( ! empty( $a['sort'] ) ? '<button type="button">' . esc_html( $h ) . '</button>' : esc_html( $h ) ) . '</th>';
	}
	$tb = '';
	foreach ( $rows as $r ) {
		$tb .= '<tr>';
		foreach ( $r as $c ) {
			$tb .= '<td>' . esc_html( $c ) . '</td>';
		}
		$tb .= '</tr>';
	}
	return $css . '<div class="semrs-dt"' . ( ! empty( $a['sort'] ) ? ' data-sort="1"' : '' ) . '>' . ( ! empty( $a['search'] ) ? '<input type="search" class="semrs-dt__search" placeholder="' . esc_attr__( 'Search the table…', 'semrs-pro' ) . '" aria-label="' . esc_attr__( 'Search the table', 'semrs-pro' ) . '">' : '' ) . '<div class="semrs-dt__wrap"><table>' . ( semrs_blocks_a( $a, 'caption' ) ? '<caption>' . esc_html( $a['caption'] ) . '</caption>' : '' ) . '<thead><tr>' . $th . '</tr></thead><tbody>' . $tb . '</tbody></table></div></div>';
}

/**
 * Animated timeline.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_timeline( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return '';
	}
	semrs_pro_more_js();
	$css = semrs_blocks_css_part( 'tlpro', '.semrs-tlp{list-style:none;margin:0;padding:0;position:relative}.semrs-tlp::before{content:"";position:absolute;left:23px;top:0;bottom:0;width:2px;background:linear-gradient(var(--tla,var(--b-accent)),var(--b-line))}.semrs-tlp li{position:relative;display:grid;grid-template-columns:48px 1fr;gap:1rem;padding-bottom:1.75rem}.semrs-tlp__ic{position:relative;display:grid;place-items:center;width:48px;height:48px;border-radius:50%;background:var(--tla,var(--b-accent));color:var(--b-on-accent);box-shadow:0 0 0 6px var(--sp-bg,#fff)}.semrs-tlp__box{padding:1rem 1.25rem;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-surface)}.semrs-tlp time{font-weight:800;color:var(--b-primary)}.semrs-tlp h3{margin:.15rem 0 .3rem;font-size:1.1rem}.semrs-tlp p{margin:0;color:var(--b-muted)}.semrs-tlp.is-js li{opacity:0;transform:translateY(24px);transition:opacity .6s,transform .6s}.semrs-tlp.is-js li.is-in{opacity:1;transform:none}@media(prefers-reduced-motion:reduce){.semrs-tlp.is-js li{opacity:1;transform:none}}' );
	$out = '';
	foreach ( $rows as $r ) {
		$icon = ! empty( $r[3] ) && isset( semrs_blocks_icons()[ $r[3] ] ) ? $r[3] : 'check';
		$out .= '<li><span class="semrs-tlp__ic">' . semrs_blocks_icon_svg( $icon, 22 ) . '</span><div class="semrs-tlp__box"><time>' . esc_html( $r[0] ) . '</time>' . ( ! empty( $r[1] ) ? '<h3>' . esc_html( $r[1] ) . '</h3>' : '' ) . ( ! empty( $r[2] ) ? '<p>' . esc_html( $r[2] ) . '</p>' : '' ) . '</div></li>';
	}
	return $css . '<ol class="semrs-tlp"' . semrs_blocks_style( array( '--tla' => semrs_blocks_color( semrs_blocks_a( $a, 'accent' ) ) ) ) . '>' . $out . '</ol>';
}

/**
 * Reviews slider.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_reviews( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return current_user_can( 'edit_posts' ) ? '<p class="semrs-empty">' . esc_html__( 'Add real reviews in the block settings, one per line: Name | Role | Stars | Text | Link.', 'semrs-pro' ) . '</p>' : '';
	}
	semrs_pro_more_js();
	$css   = semrs_blocks_css_part( 'reviews', '.semrs-rv{position:relative}.semrs-rv__track{display:grid;grid-auto-flow:column;grid-auto-columns:100%;gap:1.25rem;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding-bottom:.25rem}.semrs-rv__track::-webkit-scrollbar{display:none}@media(min-width:782px){.semrs-rv__track{grid-auto-columns:calc((100% - (var(--rv,3) - 1) * 1.25rem) / var(--rv,3))}}.semrs-rv__card{scroll-snap-align:start;display:flex;flex-direction:column;gap:.6rem;margin:0;padding:1.25rem;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-surface);color:var(--b-text)}.semrs-rv__stars{color:#f5a623}.semrs-rv__card blockquote{flex:1;margin:0;padding:0;border:0;background:none;font-style:normal;font-size:1rem;text-align:left}.semrs-rv__card blockquote::before,.semrs-rv__card blockquote::after{content:none}.semrs-rv__card figcaption{text-align:left}.semrs-rv__card figcaption{font-weight:700}.semrs-rv__card figcaption small{display:block;font-weight:400;color:var(--b-muted)}.semrs-rv__card a{font-size:.85em}.semrs-rv__nav{display:flex;justify-content:flex-end;gap:.5rem;margin-top:.75rem}.semrs-rv__nav button{width:40px;height:40px;border:1px solid var(--b-line);border-radius:50%;background:var(--b-surface);color:var(--b-primary);cursor:pointer}' );
	$cards = '';
	$ld    = array();
	foreach ( $rows as $r ) {
		$stars  = max( 1, min( 5, (int) ( $r[2] ?? 5 ) ) );
		$link   = $r[4] ?? '';
		$cards .= '<figure class="semrs-rv__card"><span class="semrs-rv__stars" role="img" aria-label="' . esc_attr( sprintf( /* translators: %d: stars. */ __( '%d out of 5 stars', 'semrs-pro' ), $stars ) ) . '">' . semrs_blocks_stars( $stars, 5, 18 ) . '</span><blockquote>' . esc_html( $r[3] ?? '' ) . '</blockquote><figcaption>' . esc_html( $r[0] ) . ( ! empty( $r[1] ) ? '<small>' . esc_html( $r[1] ) . '</small>' : '' ) . '</figcaption>' . ( $link ? '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow">' . esc_html__( 'See the original review', 'semrs-pro' ) . '</a>' : '' ) . '</figure>';
		$ld[]   = array( '@type' => 'Review', 'author' => array( '@type' => 'Person', 'name' => $r[0] ), 'reviewRating' => array( '@type' => 'Rating', 'ratingValue' => $stars, 'bestRating' => 5 ), 'reviewBody' => $r[3] ?? '' );
	}
	$schema = '';
	if ( ! empty( $a['schema'] ) && semrs_blocks_a( $a, 'product' ) ) {
		$schema = '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $a['product'], 'review' => $ld ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>';
	}
	return $css . '<div class="semrs-rv"' . ( ! empty( $a['autoplay'] ) ? ' data-autoplay="1"' : '' ) . ' style="--rv:' . max( 1, min( 4, (int) semrs_blocks_a( $a, 'perView', 3 ) ) ) . '"><div class="semrs-rv__track" tabindex="0" aria-label="' . esc_attr__( 'Customer reviews', 'semrs-pro' ) . '">' . $cards . '</div><div class="semrs-rv__nav"><button type="button" data-dir="-1" aria-label="' . esc_attr__( 'Previous reviews', 'semrs-pro' ) . '">&larr;</button><button type="button" data-dir="1" aria-label="' . esc_attr__( 'Next reviews', 'semrs-pro' ) . '">&rarr;</button></div></div>' . $schema;
}

/**
 * Trust badges.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_pro_render_badges( $a ) {
	$rows = semrs_blocks_rows( semrs_blocks_a( $a, 'items' ) );
	if ( ! $rows ) {
		return '';
	}
	$css = semrs_blocks_css_part( 'badges', '.semrs-bdg{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(13rem,1fr))}.semrs-bdg__i{display:flex;align-items:flex-start;gap:.75rem;padding:1rem;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-surface);color:var(--b-text)}.semrs-bdg__i .semrs-icon{flex:0 0 auto;color:var(--bc,var(--b-accent))}.semrs-bdg__i b{display:block}.semrs-bdg__i small{color:var(--b-muted)}.semrs-bdg--inline{display:flex;flex-wrap:wrap;justify-content:center;gap:.5rem 2rem}.semrs-bdg--inline .semrs-bdg__i{padding:0;border:0;background:none;align-items:center}.semrs-bdg--inline small{display:none}' );
	$out = '';
	foreach ( $rows as $r ) {
		$icon = isset( semrs_blocks_icons()[ $r[0] ] ) ? $r[0] : 'shield';
		$out .= '<div class="semrs-bdg__i">' . semrs_blocks_icon_svg( $icon, 28 ) . '<div><b>' . esc_html( $r[1] ?? '' ) . '</b>' . ( ! empty( $r[2] ) ? '<small>' . esc_html( $r[2] ) . '</small>' : '' ) . '</div></div>';
	}
	return $css . '<div class="semrs-bdg semrs-bdg--' . ( 'inline' === semrs_blocks_a( $a, 'style' ) ? 'inline' : 'cards' ) . '"' . semrs_blocks_style( array( '--bc' => semrs_blocks_color( semrs_blocks_a( $a, 'color' ) ) ) ) . '>' . $out . '</div>';
}
