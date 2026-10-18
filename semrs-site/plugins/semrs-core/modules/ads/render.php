<?php
/**
 * Output: head script, ad units, in-content insertion, shortcode, loader.
 *
 * Every unit is printed inside a <template> and activated by a tiny loader:
 * - device targeting without hiding loaded ads (hidden ads break AdSense policy),
 * - optional lazy loading so units load just before they scroll into view.
 *
 * @package semrs-ads
 */

defined( 'ABSPATH' ) || exit;

/**
 * Head: partner header code, only on pages where ads are allowed. The
 * AdSense tag (which also powers Auto ads) is enqueued below, so excluded
 * pages stay ad-free even with Auto ads switched on in AdSense.
 */
function semrs_ads_head() {
	if ( ! semrs_ads_allowed() ) {
		return;
	}
	$s = semrs_ads_settings();
	if ( '' !== trim( $s['head_code'] ) ) {
		echo $s['head_code'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- admin-only raw partner code (unfiltered_html checked on save).
	}
}
add_action( 'wp_head', 'semrs_ads_head', 2 );

/**
 * The AdSense tag, enqueued (async, crossorigin) only where ads are allowed.
 */
function semrs_ads_enqueue_adsense() {
	$s = semrs_ads_settings();
	if ( ! $s['load_adsense'] || ! preg_match( '/^ca-pub-d{10,20}$/', $s['publisher_id'] ) || ! semrs_ads_allowed() ) {
		return;
	}
	wp_enqueue_script(
		'semrs-adsense',
		add_query_arg( 'client', $s['publisher_id'], 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js' ),
		array(),
		null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves its own versioned file.
		array( 'strategy' => 'async' )
	);
}
add_action( 'wp_enqueue_scripts', 'semrs_ads_enqueue_adsense' );

add_filter(
	'script_loader_tag',
	function ( $tag, $handle ) {
		return 'semrs-adsense' === $handle ? str_replace( ' src=', ' crossorigin="anonymous" src=', $tag ) : $tag;
	},
	10,
	2
);

/**
 * Markup for one unit, or '' when the slot is off or unconfigured.
 *
 * @param string $key   Position key.
 * @param string $extra Extra wrapper class.
 * @return string
 */
function semrs_ads_unit( $key, $extra = '' ) {
	$s    = semrs_ads_settings();
	$slot = isset( $s['slots'][ $key ] ) ? $s['slots'][ $key ] : null;
	if ( ! $slot || empty( $slot['enabled'] ) ) {
		return '';
	}
	/**
	 * Change or cancel (return null) a unit before it is printed. SEMRS Pro
	 * uses this for targeting rules and per-page position switches.
	 *
	 * @param array  $slot Slot settings.
	 * @param string $key  Position key.
	 */
	$slot = apply_filters( 'semrs_ads_slot', $slot, $key );
	if ( ! $slot ) {
		return '';
	}

	if ( 'custom' === $slot['network'] ) {
		$inner = (string) $slot['code'];
	} elseif ( preg_match( '/^ca-pub-\d{10,20}$/', $s['publisher_id'] ) && preg_match( '/^\d{6,20}$/', (string) $slot['ad_slot'] ) ) {
		$fmt   = 'in-article' === $slot['format']
			? 'style="display:block;text-align:center" data-ad-layout="in-article" data-ad-format="fluid"'
			: 'style="display:block" data-ad-format="auto" data-full-width-responsive="true"';
		$inner = sprintf(
			'<ins class="adsbygoogle" %s data-ad-client="%s" data-ad-slot="%s"></ins>',
			$fmt,
			esc_attr( $s['publisher_id'] ),
			esc_attr( $slot['ad_slot'] )
		);
	} else {
		return '';
	}
	if ( '' === trim( $inner ) ) {
		return '';
	}

	$classes = 'semrs-ad-slot semrs-ad-slot--' . sanitize_html_class( $key );
	if ( in_array( $key, array( 'before_content', 'after_paragraph', 'mid_content', 'after_content', 'repeat' ), true ) ) {
		$classes .= ' semrs-ad-slot--incontent';
	}
	if ( 'sidebar' === $key ) {
		$classes .= ' semrs-ad-sidebar';
	}
	if ( $extra ) {
		$classes .= ' ' . sanitize_html_class( $extra );
	}

	$GLOBALS['semrs_ads_printed'] = true;

	/**
	 * Unit content: one or more <template> elements. When there are several,
	 * the loader picks one at random for each page view (A/B rotation).
	 *
	 * @param string $templates Template markup.
	 * @param string $key       Position key.
	 * @param array  $slot      Slot settings.
	 */
	$templates = (string) apply_filters( 'semrs_ads_unit_templates', '<template>' . $inner . '</template>', $key, $slot );

	return sprintf(
		'<div class="%1$s" data-semrs-ad="%2$s" data-device="%3$s" data-label="%4$s" role="complementary" aria-label="%4$s">%5$s</div>',
		esc_attr( $classes ),
		esc_attr( $key ),
		esc_attr( $slot['device'] ),
		esc_attr( $s['label'] ),
		$templates // Ad markup built above or admin-only partner code.
	);
}

/**
 * Insert in-content units at safe points, with a density cap and a
 * minimum gap. Runs after the TOC (20) and before the author box (25).
 *
 * @param string $content Rendered content.
 * @return string
 */
function semrs_ads_insert( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || ! semrs_ads_allowed() || ! semrs_ads_has_enough_content( $content ) ) {
		return $content;
	}
	$s       = semrs_ads_settings();
	$points  = semrs_ads_safe_points( $content );
	$total   = count( $points );
	$gap     = max( 1, (int) $s['min_gap'] );
	$max     = max( 0, (int) $s['max_incontent'] );
	$inserts = array(); // offset => html.
	$used    = array(); // paragraph indexes used.

	$try = function ( $index, $key ) use ( &$inserts, &$used, $points, $total, $gap, $max ) {
		if ( count( $inserts ) >= $max || $index < 0 || $index >= $total - 1 ) {
			return; // Never after the final paragraph (end-of-content slot covers that).
		}
		foreach ( $used as $u ) {
			if ( abs( $u - $index ) < $gap ) {
				return;
			}
		}
		$html = semrs_ads_unit( $key );
		if ( $html ) {
			$inserts[ $points[ $index ] ] = $html;
			$used[]                       = $index;
		}
	};

	$try( max( 0, (int) $s['paragraph_n'] - 1 ), 'after_paragraph' );
	$try( (int) floor( $total / 2 ), 'mid_content' );
	/**
	 * Extra in-content placements: list of [paragraph index, position key].
	 * The density cap and minimum gap still apply.
	 *
	 * @param array $extra Placements.
	 * @param int   $total Safe paragraph count.
	 */
	foreach ( (array) apply_filters( 'semrs_ads_incontent_extra', array(), $total ) as $extra ) {
		$try( (int) $extra[0], (string) $extra[1] );
	}

	krsort( $inserts );
	foreach ( $inserts as $offset => $html ) {
		$content = substr_replace( $content, $html, $offset, 0 );
	}

	$count  = count( $inserts );
	$before = $count < $max ? semrs_ads_unit( 'before_content' ) : '';
	$count += $before ? 1 : 0;
	$after  = ( $count < $max && $total >= 4 ) ? semrs_ads_unit( 'after_content' ) : '';

	return $before . $content . $after;
}
add_filter( 'the_content', 'semrs_ads_insert', 22 );

/**
 * [semrs_ad slot="tool|sidebar|manual"]
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_ads_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'slot' => 'manual' ), $atts, 'semrs_ad' );
	$key  = sanitize_key( $atts['slot'] );
	if ( ! in_array( $key, array( 'tool', 'sidebar', 'manual' ), true ) || ! semrs_ads_allowed() ) {
		return '';
	}
	return semrs_ads_unit( $key );
}
add_shortcode( 'semrs_ad', 'semrs_ads_shortcode' );

/**
 * Admin bar: tells admins whether visitors get ads on this page and why not.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function semrs_ads_admin_bar( $bar ) {
	if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$reason = semrs_ads_block_reason();
	if ( '' === $reason || 'admin' === $reason ) {
		$title = __( 'Ads: ON for visitors', 'semrs-core' ) . ( 'admin' === $reason ? ' ' . __( '(hidden for you)', 'semrs-core' ) : '' );
	} else {
		/* translators: %s: reason */
		$title = sprintf( __( 'Ads: OFF (%s)', 'semrs-core' ), $reason );
	}
	$bar->add_node(
		array(
			'id'    => 'semrs-ads',
			'title' => esc_html( $title ),
			'href'  => admin_url( 'admin.php?page=semrs-ads' ),
		)
	);
}
add_action( 'admin_bar_menu', 'semrs_ads_admin_bar', 90 );

/**
 * Loader: activates templates for the matching device, lazily if enabled.
 */
function semrs_ads_loader() {
	if ( empty( $GLOBALS['semrs_ads_printed'] ) ) {
		return;
	}
	$lazy = semrs_ads_settings()['lazy'] ? 'true' : 'false';
	?>
	<script>
	(function () {
		var lazy = <?php echo $lazy; // phpcs:ignore WordPress.Security.EscapeOutput -- boolean literal. ?>;
		var mq = { all: '(min-width:0px)', mobile: '(max-width:921px)', desktop: '(min-width:922px)', wide: '(min-width:1200px)' };
		if (window.semrsAdsBlocked) { // Set by SEMRS Pro click protection.
			document.querySelectorAll('[data-semrs-ad]').forEach(function (b) { b.remove(); });
			return;
		}
		function activate(box) {
			if (box.dataset.semrsDone) { return; }
			box.dataset.semrsDone = '1';
			var tpls = box.querySelectorAll('template');
			if (!tpls.length) { return; }
			var tpl = tpls[Math.floor(Math.random() * tpls.length)]; // A/B rotation when there are several.
			box.dataset.variant = [].indexOf.call(tpls, tpl);
			var frag = tpl.content.cloneNode(true);
			frag.querySelectorAll('script').forEach(function (old) {
				var s = document.createElement('script');
				for (var i = 0; i < old.attributes.length; i++) { s.setAttribute(old.attributes[i].name, old.attributes[i].value); }
				s.text = old.text;
				old.parentNode.replaceChild(s, old);
			});
			box.appendChild(frag);
			document.dispatchEvent(new CustomEvent('semrs:ad', { detail: box }));
			box.querySelectorAll('ins.adsbygoogle').forEach(function () {
				try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (e) {}
			});
		}
		var boxes = [].slice.call(document.querySelectorAll('[data-semrs-ad]')).filter(function (b) {
			var q = mq[b.dataset.device] || mq.all;
			if (window.matchMedia(q).matches) { return true; }
			b.remove(); // Not for this device: remove the empty box so no space is reserved.
			return false;
		});
		if (lazy && 'IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (e) { if (e.isIntersecting) { io.unobserve(e.target); activate(e.target); } });
			}, { rootMargin: '600px 0px' });
			boxes.forEach(function (b) { io.observe(b); });
		} else {
			boxes.forEach(activate);
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'semrs_ads_loader', 50 );
