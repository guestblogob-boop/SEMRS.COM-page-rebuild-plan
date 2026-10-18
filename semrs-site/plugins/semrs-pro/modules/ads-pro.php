<?php
/**
 * Ads Pro: extra placements (repeat in content, between posts in lists),
 * per-position targeting (categories, post age, dates, logged-in), A/B
 * rotation, click protection, a polite ad-blocker message and per-page
 * position switches. Settings appear on SEMRS → Ads. Needs the SEMRS Core
 * Ads module.
 *
 * Policy notes: units are never hidden after loading (only not loaded),
 * nothing refreshes ads, and content is never blocked for ad-blocker users.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_ads_settings' ) ) {
	return; // Core Ads module is off.
}

/**
 * Settings.
 *
 * @return array
 */
function semrs_pro_ads_settings() {
	static $s = null;
	if ( null === $s ) {
		$saved = get_option( 'semrs_ads_pro', array() );
		$s     = array_merge(
			array(
				'repeat_every'   => 5,
				'archive_every'  => 4,
				'archive_max'    => 3,
				'click_protect'  => true,
				'click_limit'    => 3,
				'click_hours'    => 24,
				'block_days'     => 7,
				'adblock_notice' => false,
				'adblock_text'   => '',
				'rules'          => array(),
			),
			is_array( $saved ) ? $saved : array()
		);
	}
	return $s;
}

/**
 * Rules for one position.
 *
 * @param string $key Position key.
 * @return array
 */
function semrs_pro_ads_rule( $key ) {
	$rules = (array) semrs_pro_ads_settings()['rules'];
	return array_merge(
		array(
			'cats_in'     => array(),
			'cats_ex'     => array(),
			'min_age'     => 0,
			'start'       => '',
			'end'         => '',
			'hide_logged' => false,
			'b_slot'      => '',
			'b_code'      => '',
		),
		isset( $rules[ $key ] ) ? (array) $rules[ $key ] : array()
	);
}

/* -------------------------------------------------------------------------
 * Positions
 * ---------------------------------------------------------------------- */

add_filter(
	'semrs_ads_positions',
	function ( $positions ) {
		$positions['repeat']  = __( 'Pro: repeat in content every N paragraphs', 'semrs-pro' );
		$positions['archive'] = __( 'Pro: between posts on blog and category pages', 'semrs-pro' );
		return $positions;
	}
);

// Repeat in content (core keeps the density cap and minimum gap).
add_filter(
	'semrs_ads_incontent_extra',
	function ( $extra, $total ) {
		$every = max( 2, (int) semrs_pro_ads_settings()['repeat_every'] );
		$first = max( 0, (int) semrs_ads_settings()['paragraph_n'] - 1 );
		for ( $i = $first + $every; $i < $total - 1; $i += $every ) {
			$extra[] = array( $i, 'repeat' );
		}
		return $extra;
	},
	10,
	2
);

// Between posts in the main blog/archive loop.
add_action(
	'the_post',
	function ( $post, $query ) {
		static $n = 0, $shown = 0;
		if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_home() || $query->is_archive() ) || is_feed() ) {
			return;
		}
		$s = semrs_pro_ads_settings();
		if ( $n > 0 && 0 === $n % max( 1, (int) $s['archive_every'] ) && $shown < (int) $s['archive_max'] && semrs_ads_allowed() ) {
			$html = semrs_ads_unit( 'archive' );
			if ( $html ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- built by semrs_ads_unit().
				$shown++;
			}
		}
		$n++;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Targeting, per-page switches, rotation
 * ---------------------------------------------------------------------- */

add_filter(
	'semrs_ads_slot',
	function ( $slot, $key ) {
		$r   = semrs_pro_ads_rule( $key );
		$now = current_time( 'Y-m-d' );
		if ( $r['hide_logged'] && is_user_logged_in() ) {
			return null;
		}
		if ( ( $r['start'] && $now < $r['start'] ) || ( $r['end'] && $now > $r['end'] ) ) {
			return null;
		}
		if ( is_singular() ) {
			$id  = get_queried_object_id();
			$off = (array) get_post_meta( $id, '_semrs_ads_off_slots', true );
			if ( in_array( $key, $off, true ) ) {
				return null;
			}
			if ( $r['min_age'] && ( time() - get_post_time( 'U', true, $id ) ) < DAY_IN_SECONDS * (int) $r['min_age'] ) {
				return null;
			}
			if ( $r['cats_in'] || $r['cats_ex'] ) {
				$cats = wp_get_post_categories( $id );
				if ( $r['cats_in'] && ! array_intersect( $cats, array_map( 'intval', $r['cats_in'] ) ) ) {
					return null;
				}
				if ( $r['cats_ex'] && array_intersect( $cats, array_map( 'intval', $r['cats_ex'] ) ) ) {
					return null;
				}
			}
		} elseif ( $r['cats_in'] || $r['cats_ex'] ) {
			if ( is_category() ) {
				$cat = get_queried_object_id();
				if ( ( $r['cats_in'] && ! in_array( $cat, array_map( 'intval', $r['cats_in'] ), true ) ) || in_array( $cat, array_map( 'intval', $r['cats_ex'] ), true ) ) {
					return null;
				}
			} elseif ( $r['cats_in'] ) {
				return null; // "Only in these categories" applies outside category pages too.
			}
		}
		return $slot;
	},
	10,
	2
);

add_filter(
	'semrs_ads_unit_templates',
	function ( $templates, $key, $slot ) {
		$r  = semrs_pro_ads_rule( $key );
		$s  = semrs_ads_settings();
		$b  = '';
		if ( '' !== trim( (string) $r['b_code'] ) ) {
			$b = (string) $r['b_code'];
		} elseif ( $r['b_slot'] && preg_match( '/^ca-pub-\d{10,20}$/', $s['publisher_id'] ) ) {
			$fmt = 'in-article' === $slot['format']
				? 'style="display:block;text-align:center" data-ad-layout="in-article" data-ad-format="fluid"'
				: 'style="display:block" data-ad-format="auto" data-full-width-responsive="true"';
			$b   = sprintf( '<ins class="adsbygoogle" %s data-ad-client="%s" data-ad-slot="%s"></ins>', $fmt, esc_attr( $s['publisher_id'] ), esc_attr( $r['b_slot'] ) );
		}
		return '' === $b ? $templates : $templates . '<template>' . $b . '</template>';
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Click protection and ad-blocker message (front end)
 * ---------------------------------------------------------------------- */

add_action(
	'wp_head',
	function () {
		$s = semrs_pro_ads_settings();
		if ( ! $s['click_protect'] || ! semrs_ads_allowed() ) {
			return;
		}
		// Runs before the AdSense tag (priority 2): a blocked visitor gets no ad requests at all, Auto ads included.
		printf(
			'<script>(function(){try{var b=+localStorage.getItem("semrs_ads_block")||0;if(b>Date.now()){window.semrsAdsBlocked=true;(window.adsbygoogle=window.adsbygoogle||[]).pauseAdRequests=1;}}catch(e){}window.semrsAdsClick={limit:%d,hours:%d,days:%d};})();</script>' . "\n",
			absint( max( 1, (int) $s['click_limit'] ) ),
			absint( max( 1, (int) $s['click_hours'] ) ),
			absint( max( 1, (int) $s['block_days'] ) )
		);
	},
	1
);

add_action(
	'wp_footer',
	function () {
		if ( ! semrs_ads_allowed() ) {
			return;
		}
		$s = semrs_pro_ads_settings();
		if ( $s['click_protect'] ) {
			// An ad click moves focus into the ad iframe and blurs the window.
			?>
			<script>
			(function () {
				var c = window.semrsAdsClick; if (!c || window.semrsAdsBlocked) { return; }
				window.addEventListener('blur', function () {
					setTimeout(function () {
						var f = document.activeElement;
						if (!f || f.tagName !== 'IFRAME' || !(f.closest('[data-semrs-ad]') || /aswift|google_ads/.test(f.id))) { return; }
						try {
							var now = Date.now(), list = JSON.parse(localStorage.getItem('semrs_ads_clicks') || '[]').filter(function (t) { return t > now - c.hours * 3600000; });
							list.push(now);
							localStorage.setItem('semrs_ads_clicks', JSON.stringify(list));
							if (list.length >= c.limit) { localStorage.setItem('semrs_ads_block', String(now + c.days * 86400000)); localStorage.removeItem('semrs_ads_clicks'); }
						} catch (e) {}
					}, 0);
				});
			})();
			</script>
			<?php
		}
		if ( $s['adblock_notice'] ) {
			$text = '' !== trim( $s['adblock_text'] ) ? $s['adblock_text'] : __( 'It looks like you use an ad blocker. Ads keep this site free; please consider allowing them here. Thank you!', 'semrs-pro' );
			?>
			<div class="semrs-adblock" role="status" hidden><p><?php echo esc_html( $text ); ?></p><button type="button" aria-label="<?php esc_attr_e( 'Close', 'semrs-pro' ); ?>">&times;</button></div>
			<style>.semrs-adblock{position:fixed;left:16px;right:16px;bottom:16px;z-index:9990;max-width:560px;margin:0 auto;display:flex;gap:12px;align-items:center;background:#0b1f3a;color:#fff;border-radius:10px;padding:12px 14px;box-shadow:0 8px 30px rgba(0,0,0,.25);font-size:15px}.semrs-adblock[hidden]{display:none}.semrs-adblock p{margin:0;flex:1}.semrs-adblock button{background:none;border:0;color:#fff;font-size:24px;line-height:1;cursor:pointer;padding:4px 8px}</style>
			<script>
			(function () {
				try { if (+localStorage.getItem('semrs_adblock_closed') > Date.now()) { return; } } catch (e) {}
				var bait = document.createElement('div');
				bait.className = 'adsbox ad-banner textads banner-ads';
				bait.style.cssText = 'position:absolute;left:-9999px;height:10px;width:10px';
				bait.innerHTML = '&nbsp;';
				document.body.appendChild(bait);
				window.addEventListener('load', function () {
					setTimeout(function () {
						var blocked = !bait.offsetHeight || getComputedStyle(bait).display === 'none';
						bait.remove();
						if (!blocked) { return; }
						var box = document.querySelector('.semrs-adblock');
						box.hidden = false;
						box.querySelector('button').addEventListener('click', function () {
							box.hidden = true;
							try { localStorage.setItem('semrs_adblock_closed', String(Date.now() + 7 * 86400000)); } catch (e) {}
						});
					}, 400);
				});
			})();
			</script>
			<?php
		}
		?>
		<style>.semrs-ad-slot--archive{grid-column:1/-1;margin:8px 0 24px}</style>
		<?php
	},
	60
);

/* -------------------------------------------------------------------------
 * Admin: settings on SEMRS → Ads, per-page switches
 * ---------------------------------------------------------------------- */

if ( ! is_admin() ) {
	return;
}

add_action(
	'admin_init',
	function () {
		register_setting( 'semrs_ads', 'semrs_ads_pro', array( 'sanitize_callback' => 'semrs_pro_ads_sanitize' ) );
	}
);

/**
 * Sanitize.
 *
 * @param mixed $in Raw input.
 * @return array
 */
function semrs_pro_ads_sanitize( $in ) {
	if ( ! is_array( $in ) || ( empty( $in['_present'] ) && ! array_key_exists( 'rules', $in ) ) ) {
		return get_option( 'semrs_ads_pro', array() );
	}
	$date  = function ( $v ) {
		$v = sanitize_text_field( (string) $v );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
	};
	$ids   = function ( $v ) {
		return array_values( array_filter( array_map( 'absint', (array) $v ) ) );
	};
	$out   = array(
		'repeat_every'   => max( 2, min( 20, absint( $in['repeat_every'] ?? 5 ) ) ),
		'archive_every'  => max( 1, min( 20, absint( $in['archive_every'] ?? 4 ) ) ),
		'archive_max'    => max( 1, min( 5, absint( $in['archive_max'] ?? 3 ) ) ),
		'click_protect'  => ! empty( $in['click_protect'] ),
		'click_limit'    => max( 1, min( 20, absint( $in['click_limit'] ?? 3 ) ) ),
		'click_hours'    => max( 1, min( 168, absint( $in['click_hours'] ?? 24 ) ) ),
		'block_days'     => max( 1, min( 90, absint( $in['block_days'] ?? 7 ) ) ),
		'adblock_notice' => ! empty( $in['adblock_notice'] ),
		'adblock_text'   => sanitize_text_field( wp_unslash( (string) ( $in['adblock_text'] ?? '' ) ) ),
		'rules'          => array(),
	);
	foreach ( array_keys( semrs_ads_positions() ) as $key ) {
		$r = isset( $in['rules'][ $key ] ) ? (array) $in['rules'][ $key ] : array();
		$out['rules'][ $key ] = array(
			'cats_in'     => $ids( $r['cats_in'] ?? array() ),
			'cats_ex'     => $ids( $r['cats_ex'] ?? array() ),
			'min_age'     => min( 3650, absint( $r['min_age'] ?? 0 ) ),
			'start'       => $date( $r['start'] ?? '' ),
			'end'         => $date( $r['end'] ?? '' ),
			'hide_logged' => ! empty( $r['hide_logged'] ),
			'b_slot'      => preg_replace( '/\D/', '', (string) ( $r['b_slot'] ?? '' ) ),
			'b_code'      => semrs_ads_clean_code( wp_unslash( (string) ( $r['b_code'] ?? '' ) ) ),
		);
	}
	return $out;
}

add_action(
	'semrs_ads_settings_after',
	function () {
		$s    = semrs_pro_ads_settings();
		$n    = 'semrs_ads_pro';
		$cats = get_categories( array( 'hide_empty' => false ) );
		$num  = function ( $key, $min, $max ) use ( $s, $n ) {
			printf( '<input type="number" class="small-text" min="%d" max="%d" name="%s[%s]" value="%s">', (int) $min, (int) $max, esc_attr( $n ), esc_attr( $key ), esc_attr( $s[ $key ] ) );
		};
		?>
		<h2><?php esc_html_e( 'SEMRS Pro', 'semrs-pro' ); ?></h2>
		<input type="hidden" name="<?php echo esc_attr( $n ); ?>[_present]" value="1">
		<table class="form-table" role="presentation">
			<tr><th><?php esc_html_e( 'Repeat in content', 'semrs-pro' ); ?></th><td><?php esc_html_e( 'Every', 'semrs-pro' ); ?> <?php $num( 'repeat_every', 2, 20 ); ?> <?php esc_html_e( 'paragraphs after the first ad', 'semrs-pro' ); ?><p class="description"><?php esc_html_e( 'Switch on "Pro: repeat in content" in the positions table. "Maximum in-content ads" and "Minimum paragraphs between ads" still apply.', 'semrs-pro' ); ?></p></td></tr>
			<tr><th><?php esc_html_e( 'Between posts in lists', 'semrs-pro' ); ?></th><td><?php esc_html_e( 'After every', 'semrs-pro' ); ?> <?php $num( 'archive_every', 1, 20 ); ?> <?php esc_html_e( 'posts, at most', 'semrs-pro' ); ?> <?php $num( 'archive_max', 1, 5 ); ?> <?php esc_html_e( 'per page', 'semrs-pro' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Click protection', 'semrs-pro' ); ?></th><td>
				<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[click_protect]" value="1" <?php checked( $s['click_protect'] ); ?>> <?php esc_html_e( 'Stop showing ads to a visitor who clicks ads too often', 'semrs-pro' ); ?></label><br>
				<?php $num( 'click_limit', 1, 20 ); ?> <?php esc_html_e( 'clicks within', 'semrs-pro' ); ?> <?php $num( 'click_hours', 1, 168 ); ?> <?php esc_html_e( 'hours → no ads for', 'semrs-pro' ); ?> <?php $num( 'block_days', 1, 90 ); ?> <?php esc_html_e( 'days', 'semrs-pro' ); ?>
				<p class="description"><?php esc_html_e( 'Protects your AdSense account from accidental or deliberate invalid clicks. Ad requests are paused for that visitor (Auto ads too). Stored only in the visitor\'s browser.', 'semrs-pro' ); ?></p>
			</td></tr>
			<tr><th><?php esc_html_e( 'Ad-blocker message', 'semrs-pro' ); ?></th><td>
				<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[adblock_notice]" value="1" <?php checked( $s['adblock_notice'] ); ?>> <?php esc_html_e( 'Show a polite, dismissible note to visitors with an ad blocker', 'semrs-pro' ); ?></label>
				<input type="text" class="large-text" name="<?php echo esc_attr( $n ); ?>[adblock_text]" value="<?php echo esc_attr( $s['adblock_text'] ); ?>" placeholder="<?php esc_attr_e( 'It looks like you use an ad blocker. Ads keep this site free; please consider allowing them here. Thank you!', 'semrs-pro' ); ?>">
				<p class="description"><?php esc_html_e( 'Content is never blocked. Once closed, it stays hidden for 7 days.', 'semrs-pro' ); ?></p>
			</td></tr>
		</table>

		<h3><?php esc_html_e( 'Targeting and A/B rotation per position', 'semrs-pro' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Leave everything empty to show a position everywhere ads are allowed. Variant B: a second AdSense slot ID or custom code; each page view shows A or B at random. Compare the two units in your AdSense reports.', 'semrs-pro' ); ?></p>
		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><th><?php esc_html_e( 'Position', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Only in categories', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Never in categories', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Post age and dates', 'semrs-pro' ); ?></th><th><?php esc_html_e( 'Variant B', 'semrs-pro' ); ?></th></tr></thead>
			<tbody>
			<?php
			foreach ( semrs_ads_positions() as $key => $label ) :
				$r = semrs_pro_ads_rule( $key );
				$f = $n . '[rules][' . $key . ']';
				?>
				<tr>
					<td><strong><?php echo esc_html( $label ); ?></strong><br><label><input type="checkbox" name="<?php echo esc_attr( $f ); ?>[hide_logged]" value="1" <?php checked( $r['hide_logged'] ); ?>> <?php esc_html_e( 'Hide for logged-in users', 'semrs-pro' ); ?></label></td>
					<?php foreach ( array( 'cats_in', 'cats_ex' ) as $field ) : ?>
						<td><select multiple size="3" style="min-width:150px" name="<?php echo esc_attr( $f ); ?>[<?php echo esc_attr( $field ); ?>][]" aria-label="<?php echo esc_attr( $label ); ?>">
							<?php foreach ( $cats as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( (int) $cat->term_id, array_map( 'intval', $r[ $field ] ), true ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select></td>
					<?php endforeach; ?>
					<td>
						<label><?php esc_html_e( 'Older than', 'semrs-pro' ); ?> <input type="number" class="small-text" min="0" name="<?php echo esc_attr( $f ); ?>[min_age]" value="<?php echo esc_attr( $r['min_age'] ); ?>"> <?php esc_html_e( 'days', 'semrs-pro' ); ?></label><br>
						<label><?php esc_html_e( 'From', 'semrs-pro' ); ?> <input type="date" name="<?php echo esc_attr( $f ); ?>[start]" value="<?php echo esc_attr( $r['start'] ); ?>"></label><br>
						<label><?php esc_html_e( 'Until', 'semrs-pro' ); ?> <input type="date" name="<?php echo esc_attr( $f ); ?>[end]" value="<?php echo esc_attr( $r['end'] ); ?>"></label>
					</td>
					<td><input type="text" style="width:140px" name="<?php echo esc_attr( $f ); ?>[b_slot]" value="<?php echo esc_attr( $r['b_slot'] ); ?>" placeholder="<?php esc_attr_e( 'AdSense slot ID', 'semrs-pro' ); ?>"><br><textarea class="code" rows="2" style="width:100%" name="<?php echo esc_attr( $f ); ?>[b_code]" placeholder="<?php esc_attr_e( 'or custom code', 'semrs-pro' ); ?>"><?php echo esc_textarea( $r['b_code'] ); ?></textarea></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
);

// Per-page position switches, added to the free "Ads" box.
add_action(
	'add_meta_boxes',
	function () {
		foreach ( (array) semrs_ads_settings()['post_types'] as $type ) {
			add_meta_box( 'semrs_ads_slots', __( 'Ad positions on this page', 'semrs-pro' ), 'semrs_pro_ads_slots_box', $type, 'side', 'low' );
		}
	}
);

/**
 * Render the positions box.
 *
 * @param WP_Post $post Post.
 */
function semrs_pro_ads_slots_box( $post ) {
	wp_nonce_field( 'semrs_ads_slots', 'semrs_ads_slots_nonce' );
	$off = (array) get_post_meta( $post->ID, '_semrs_ads_off_slots', true );
	$s   = semrs_ads_settings();
	echo '<p class="description">' . esc_html__( 'Untick a position to turn it off on this page only.', 'semrs-pro' ) . '</p>';
	foreach ( semrs_ads_positions() as $key => $label ) {
		if ( 'archive' === $key || empty( $s['slots'][ $key ]['enabled'] ) ) {
			continue;
		}
		printf( '<label style="display:block;margin:4px 0"><input type="checkbox" name="semrs_ads_slots_on[]" value="%s" %s> %s</label>', esc_attr( $key ), checked( ! in_array( $key, $off, true ), true, false ), esc_html( $label ) );
	}
	echo '<input type="hidden" name="semrs_ads_slots_present" value="1">';
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['semrs_ads_slots_nonce'], $_POST['semrs_ads_slots_present'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_ads_slots_nonce'] ) ), 'semrs_ads_slots' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$on  = array_map( 'sanitize_key', (array) wp_unslash( $_POST['semrs_ads_slots_on'] ?? array() ) );
		$s   = semrs_ads_settings();
		$off = array();
		foreach ( array_keys( semrs_ads_positions() ) as $key ) {
			if ( 'archive' !== $key && ! empty( $s['slots'][ $key ]['enabled'] ) && ! in_array( $key, $on, true ) ) {
				$off[] = $key;
			}
		}
		if ( $off ) {
			update_post_meta( $post_id, '_semrs_ads_off_slots', $off );
		} else {
			delete_post_meta( $post_id, '_semrs_ads_off_slots' );
		}
	}
);
