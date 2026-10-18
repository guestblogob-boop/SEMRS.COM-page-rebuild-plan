<?php
/**
 * Languages (Pro): a light, SEO-correct language switcher.
 *
 * How it works:
 * - SEMRS → Languages: list the site's languages (first = main language).
 * - Each page or post gets a language and is linked to its translations in
 *   the "Language & translations" box (a translation is a normal page, so it
 *   has its own URL, title and SEO fields, and is indexed on its own).
 * - Linked pages print hreflang alternate links (+ x-default) and the right
 *   <html lang>, which is how Google serves the right language.
 * - The switcher (header element "Language switcher" or [semrs_languages])
 *   links to the translation of the current page. For a language with no
 *   translation it can offer Google Translate (marked as machine
 *   translation, nofollow). No JavaScript, no external scripts.
 * - When Polylang or WPML is active, the switcher uses their languages and
 *   SEMRS leaves hreflang to them.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_pro_lang_settings() {
	$s = get_option( 'semrs_languages', array() );
	return wp_parse_args(
		is_array( $s ) ? $s : array(),
		array(
			'languages' => substr( get_locale(), 0, 2 ) . '|' . ( 'en' === substr( get_locale(), 0, 2 ) ? 'English' : strtoupper( substr( get_locale(), 0, 2 ) ) ),
			'machine'   => true,
			'style'     => 'dropdown',
			'label'     => 'code',
		)
	);
}

/**
 * Languages: code => name. The first one is the main language.
 *
 * @return array
 */
function semrs_pro_languages() {
	$out = array();
	foreach ( preg_split( '/\R/', (string) semrs_pro_lang_settings()['languages'] ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$code  = strtolower( preg_replace( '/[^a-zA-Z-]/', '', $parts[0] ) );
		if ( $code ) {
			$out[ $code ] = ! empty( $parts[1] ) ? $parts[1] : strtoupper( $code );
		}
	}
	return $out;
}

/**
 * Is another multilingual plugin in charge?
 *
 * @return string ''|polylang|wpml
 */
function semrs_pro_lang_external() {
	if ( function_exists( 'pll_the_languages' ) ) {
		return 'polylang';
	}
	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		return 'wpml';
	}
	return '';
}

/**
 * Language of a post (main language when not set).
 *
 * @param int $id Post ID.
 * @return string
 */
function semrs_pro_post_lang( $id ) {
	$langs = semrs_pro_languages();
	$l     = (string) get_post_meta( $id, '_semrs_lang', true );
	return isset( $langs[ $l ] ) ? $l : (string) key( $langs );
}

/**
 * Translation group of a post: lang => post ID (published posts only).
 *
 * @param int $id Post ID.
 * @return array
 */
function semrs_pro_translations( $id ) {
	$group = get_post_meta( $id, '_semrs_tr', true );
	$group = is_array( $group ) ? $group : array();
	$group[ semrs_pro_post_lang( $id ) ] = $id;
	$langs = semrs_pro_languages();
	$out   = array();
	foreach ( $group as $l => $pid ) {
		if ( isset( $langs[ $l ] ) && 'publish' === get_post_status( (int) $pid ) ) {
			$out[ $l ] = (int) $pid;
		}
	}
	return $out;
}

/* ---------- Settings screen ---------- */

/**
 * Menu.
 */
function semrs_pro_lang_menu() {
	add_submenu_page( defined( 'SEMRS_CORE_VERSION' ) ? 'semrs' : 'options-general.php', __( 'Languages', 'semrs-pro' ), __( 'Languages', 'semrs-pro' ), 'manage_options', 'semrs-languages', 'semrs_pro_lang_page' );
}
add_action( 'admin_menu', 'semrs_pro_lang_menu', 30 );

/**
 * Register the option.
 */
function semrs_pro_lang_register() {
	register_setting(
		'semrs_languages',
		'semrs_languages',
		array(
			'sanitize_callback' => function ( $in ) {
				$lines = array();
				foreach ( preg_split( '/\R/', (string) ( $in['languages'] ?? '' ) ) as $line ) {
					$parts = array_map( 'trim', explode( '|', $line, 2 ) );
					$code  = strtolower( preg_replace( '/[^a-zA-Z-]/', '', $parts[0] ) );
					if ( $code ) {
						$lines[] = $code . '|' . sanitize_text_field( $parts[1] ?? strtoupper( $code ) );
					}
				}
				return array(
					'languages' => implode( "\n", array_slice( array_unique( $lines ), 0, 40 ) ),
					'machine'   => ! empty( $in['machine'] ),
					'style'     => in_array( $in['style'] ?? '', array( 'dropdown', 'inline' ), true ) ? $in['style'] : 'dropdown',
					'label'     => in_array( $in['label'] ?? '', array( 'code', 'name' ), true ) ? $in['label'] : 'code',
				);
			},
		)
	);
}
add_action( 'admin_init', 'semrs_pro_lang_register' );

/**
 * Settings page.
 */
function semrs_pro_lang_page() {
	$s = semrs_pro_lang_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Languages', 'semrs-pro' ); ?></h1>
		<?php if ( semrs_pro_lang_external() ) : ?>
			<div class="notice notice-info inline"><p>
				<?php
				/* translators: %s: plugin name */
				printf( esc_html__( '%s is active: the SEMRS language switcher shows its languages and SEMRS does not add hreflang tags.', 'semrs-pro' ), esc_html( 'polylang' === semrs_pro_lang_external() ? 'Polylang' : 'WPML' ) );
				?>
			</p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'semrs_languages' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><label for="semrs-langs"><?php esc_html_e( 'Site languages', 'semrs-pro' ); ?></label></th><td>
					<textarea id="semrs-langs" name="semrs_languages[languages]" rows="6" class="large-text code"><?php echo esc_textarea( $s['languages'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One per line: language code | name, e.g. "fr | Français". Use ISO codes (en, fr, de, es, ar, ur…); add a region when needed (en-GB). The first line is your main language.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th><?php esc_html_e( 'Switcher', 'semrs-pro' ); ?></th><td>
					<select name="semrs_languages[style]"><option value="dropdown" <?php selected( $s['style'], 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'semrs-pro' ); ?></option><option value="inline" <?php selected( $s['style'], 'inline' ); ?>><?php esc_html_e( 'Inline list', 'semrs-pro' ); ?></option></select>
					<select name="semrs_languages[label]"><option value="code" <?php selected( $s['label'], 'code' ); ?>><?php esc_html_e( 'Show codes (EN, FR)', 'semrs-pro' ); ?></option><option value="name" <?php selected( $s['label'], 'name' ); ?>><?php esc_html_e( 'Show names (English, Français)', 'semrs-pro' ); ?></option></select>
					<p class="description"><?php esc_html_e( 'Add it with Customize → SEMRS Theme → Header builder → "Language switcher", or anywhere with the [semrs_languages] shortcode.', 'semrs-pro' ); ?></p>
				</td></tr>
				<tr><th><?php esc_html_e( 'Machine translation', 'semrs-pro' ); ?></th><td>
					<label><input type="checkbox" name="semrs_languages[machine]" value="1" <?php checked( $s['machine'] ); ?>> <?php esc_html_e( 'For a language without a translation of the current page, link to Google Translate', 'semrs-pro' ); ?></label>
					<p class="description"><?php esc_html_e( 'Shown with a "machine translation" note and nofollow, so search engines only index your real translations. Works on public sites (not on localhost).', 'semrs-pro' ); ?></p>
				</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'How to add a translation', 'semrs-pro' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Create a new page (or post) and write the translated text, title and SEO description.', 'semrs-pro' ); ?></li>
			<li><?php esc_html_e( 'In "Language & translations" choose its language and pick the original page; save. Both pages are now linked both ways.', 'semrs-pro' ); ?></li>
			<li><?php esc_html_e( 'Tip: give translations a clear address, e.g. a parent page "fr" so the URL becomes /fr/your-page/.', 'semrs-pro' ); ?></li>
		</ol>
	</div>
	<?php
}

/* ---------- Editor box ---------- */

/**
 * Box on every public post type.
 */
function semrs_pro_lang_box() {
	if ( semrs_pro_lang_external() || count( semrs_pro_languages() ) < 2 ) {
		return;
	}
	foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
		if ( 'attachment' !== $type ) {
			add_meta_box( 'semrs_lang', __( 'Language & translations', 'semrs-pro' ), 'semrs_pro_lang_box_render', $type, 'side' );
		}
	}
}
add_action( 'add_meta_boxes', 'semrs_pro_lang_box' );

/**
 * Render the box.
 *
 * @param WP_Post $post Post.
 */
function semrs_pro_lang_box_render( $post ) {
	wp_nonce_field( 'semrs_lang_save', 'semrs_lang_nonce' );
	$langs = semrs_pro_languages();
	$mine  = semrs_pro_post_lang( $post->ID );
	$group = get_post_meta( $post->ID, '_semrs_tr', true );
	$group = is_array( $group ) ? $group : array();
	$posts = get_posts( array( 'post_type' => $post->post_type, 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC', 'exclude' => array( $post->ID ), 'fields' => 'ids' ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- admin box: every page except this one.
	echo '<p><label for="semrs-lang"><strong>' . esc_html__( 'Language of this page', 'semrs-pro' ) . '</strong></label><br><select id="semrs-lang" name="semrs_lang" style="width:100%">';
	foreach ( $langs as $code => $name ) {
		printf( '<option value="%1$s" %2$s>%3$s (%1$s)</option>', esc_attr( $code ), selected( $mine, $code, false ), esc_html( $name ) );
	}
	echo '</select></p><p><strong>' . esc_html__( 'Translations', 'semrs-pro' ) . '</strong></p>';
	foreach ( $langs as $code => $name ) {
		printf( '<p class="semrs-tr-row" data-lang="%1$s"%4$s><label for="semrs-tr-%1$s">%2$s</label><br><select id="semrs-tr-%1$s" name="semrs_tr[%1$s]" style="width:100%%"><option value="">%3$s</option>', esc_attr( $code ), esc_html( $name ), esc_html__( '— none —', 'semrs-pro' ), $code === $mine ? ' hidden' : '' );
		foreach ( $posts as $pid ) {
			printf( '<option value="%1$d" %2$s>%3$s</option>', (int) $pid, selected( (int) ( $group[ $code ] ?? 0 ), (int) $pid, false ), esc_html( get_the_title( $pid ) . ' (#' . $pid . ')' ) );
		}
		echo '</select></p>';
	}
	// Hide the row for the page's own language as soon as it changes.
	echo "<script>(function(){var s=document.getElementById('semrs-lang');if(!s){return;}s.addEventListener('change',function(){document.querySelectorAll('#semrs_lang .semrs-tr-row').forEach(function(r){r.hidden=r.getAttribute('data-lang')===s.value;});});})();</script>";
}

/**
 * Save and keep the whole group in sync (links work both ways).
 *
 * @param int $post_id Post ID.
 */
function semrs_pro_lang_save( $post_id ) {
	if ( ! isset( $_POST['semrs_lang_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_lang_nonce'] ) ), 'semrs_lang_save' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$langs = semrs_pro_languages();
	$mine  = sanitize_key( wp_unslash( $_POST['semrs_lang'] ?? '' ) );
	$mine  = isset( $langs[ $mine ] ) ? $mine : (string) key( $langs );
	update_post_meta( $post_id, '_semrs_lang', $mine );

	$old   = get_post_meta( $post_id, '_semrs_tr', true );
	$old   = is_array( $old ) ? $old : array();
	$group = array( $mine => $post_id );
	foreach ( (array) wp_unslash( $_POST['semrs_tr'] ?? array() ) as $code => $pid ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- keys/values sanitized here.
		$code = sanitize_key( $code );
		$pid  = absint( $pid );
		if ( $pid && isset( $langs[ $code ] ) && $code !== $mine && current_user_can( 'edit_post', $pid ) ) {
			$group[ $code ] = $pid;
			update_post_meta( $pid, '_semrs_lang', $code );
		}
	}
	// Unlink posts that were removed from the group.
	foreach ( array_diff( array_map( 'intval', $old ), array_values( $group ) ) as $gone ) {
		if ( $gone && $gone !== $post_id ) {
			delete_post_meta( $gone, '_semrs_tr' );
		}
	}
	foreach ( $group as $pid ) {
		update_post_meta( $pid, '_semrs_tr', $group );
	}
}
add_action( 'save_post', 'semrs_pro_lang_save' );

/* ---------- Front end ---------- */

/**
 * <html lang> for pages in another language.
 *
 * @param string $output Attributes.
 * @return string
 */
function semrs_pro_lang_html_attr( $output ) {
	if ( semrs_pro_lang_external() || ! is_singular() || count( semrs_pro_languages() ) < 2 ) {
		return $output;
	}
	$lang = semrs_pro_post_lang( get_queried_object_id() );
	$rtl  = in_array( substr( $lang, 0, 2 ), array( 'ar', 'he', 'fa', 'ur', 'ps', 'sd', 'yi' ), true );
	$output = preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $lang ) . '"', $output );
	if ( $rtl && false === strpos( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	}
	return $output;
}
add_filter( 'language_attributes', 'semrs_pro_lang_html_attr', 20 );

/**
 * hreflang alternates for linked translations (+ x-default = main language).
 */
function semrs_pro_lang_hreflang() {
	if ( semrs_pro_lang_external() || ! is_singular() ) {
		return;
	}
	$group = semrs_pro_translations( get_queried_object_id() );
	if ( count( $group ) < 2 ) {
		return;
	}
	foreach ( $group as $code => $pid ) {
		printf( '<link rel="alternate" hreflang="%s" href="%s">' . "\n", esc_attr( $code ), esc_url( get_permalink( $pid ) ) );
	}
	$main = (string) key( semrs_pro_languages() );
	if ( isset( $group[ $main ] ) ) {
		printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( get_permalink( $group[ $main ] ) ) );
	}
}
add_action( 'wp_head', 'semrs_pro_lang_hreflang', 2 );

/**
 * Google Translate link for the current page.
 *
 * @param string $lang Target language.
 * @return string
 */
function semrs_pro_machine_url( $lang ) {
	$url  = wp_parse_url( home_url( add_query_arg( array() ) ) );
	$host = $url['host'] ?? '';
	if ( ! $host || preg_match( '/^(localhost|127\.|\[::1\])|\.(test|local|localhost)$/', $host ) ) {
		return '';
	}
	$proxy = str_replace( '.', '-', str_replace( '-', '--', $host ) ) . '.translate.goog';
	$path  = isset( $_SERVER['REQUEST_URI'] ) ? strtok( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' ) : '/';
	return 'https://' . $proxy . $path . '?_x_tr_sl=auto&_x_tr_tl=' . rawurlencode( $lang ) . '&_x_tr_hl=' . rawurlencode( $lang );
}

/**
 * Switcher items: [code, name, url, current, machine].
 *
 * @return array
 */
function semrs_pro_lang_items() {
	$ext = semrs_pro_lang_external();
	if ( 'polylang' === $ext ) {
		$items = array();
		foreach ( (array) pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) ) as $l ) {
			$items[] = array( $l['slug'], $l['name'], $l['url'], ! empty( $l['current_lang'] ), false );
		}
		return $items;
	}
	if ( 'wpml' === $ext ) {
		$items = array();
		foreach ( (array) apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ) as $l ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's public API.
			$items[] = array( $l['code'], $l['native_name'], $l['url'], ! empty( $l['active'] ), false );
		}
		return $items;
	}
	$langs   = semrs_pro_languages();
	$id      = is_singular() ? get_queried_object_id() : 0;
	$current = $id ? semrs_pro_post_lang( $id ) : (string) key( $langs );
	$group   = $id ? semrs_pro_translations( $id ) : array();
	$front   = (int) get_option( 'page_on_front' );
	$fgroup  = $front ? semrs_pro_translations( $front ) : array();
	$machine = semrs_pro_lang_settings()['machine'];
	$items   = array();
	foreach ( $langs as $code => $name ) {
		if ( $code === $current ) {
			$items[] = array( $code, $name, '', true, false );
		} elseif ( isset( $group[ $code ] ) ) {
			$items[] = array( $code, $name, get_permalink( $group[ $code ] ), false, false );
		} elseif ( $machine && semrs_pro_machine_url( $code ) ) {
			$items[] = array( $code, $name, semrs_pro_machine_url( $code ), false, true );
		} elseif ( isset( $fgroup[ $code ] ) ) {
			$items[] = array( $code, $name, get_permalink( $fgroup[ $code ] ), false, false );
		}
	}
	return $items;
}

/**
 * Switcher HTML. Native <details>, no JavaScript.
 *
 * @return string
 */
function semrs_pro_lang_switcher() {
	$items = semrs_pro_lang_items();
	if ( count( $items ) < 2 ) {
		return '';
	}
	$s       = semrs_pro_lang_settings();
	$label   = function ( $i ) use ( $s ) {
		return 'name' === $s['label'] ? $i[1] : strtoupper( $i[0] );
	};
	$current = $items[0];
	$list    = '';
	foreach ( $items as $i ) {
		if ( $i[3] ) {
			$current = $i;
			$list   .= sprintf( '<li><span aria-current="true" lang="%1$s">%2$s</span></li>', esc_attr( $i[0] ), esc_html( $i[1] ) );
			continue;
		}
		$list .= sprintf(
			'<li><a href="%1$s" hreflang="%2$s" lang="%2$s"%3$s>%4$s%5$s</a></li>',
			esc_url( $i[2] ),
			esc_attr( $i[0] ),
			$i[4] ? ' rel="nofollow noopener"' : '',
			esc_html( $i[1] ),
			$i[4] ? ' <small>' . esc_html__( '(machine translation)', 'semrs-pro' ) . '</small>' : ''
		);
	}
	$css = semrs_pro_lang_css();
	if ( 'inline' === $s['style'] ) {
		return $css . '<nav class="sp-lang sp-lang--inline" aria-label="' . esc_attr__( 'Language', 'semrs-pro' ) . '"><ul>' . $list . '</ul></nav>';
	}
	return $css . sprintf(
		'<nav class="sp-lang" aria-label="%1$s"><details><summary><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg><span>%2$s</span><span class="screen-reader-text">%3$s</span></summary><ul>%4$s</ul></details></nav>',
		esc_attr__( 'Language', 'semrs-pro' ),
		esc_html( $label( $current ) ),
		esc_html__( 'Change language', 'semrs-pro' ),
		$list
	);
}
add_shortcode( 'semrs_languages', 'semrs_pro_lang_switcher' );

/**
 * Switcher styles, once.
 *
 * @return string
 */
function semrs_pro_lang_css() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="semrs-lang-css">.sp-lang{position:relative;font-size:.9rem}.sp-lang summary{display:inline-flex;align-items:center;gap:.35rem;min-height:40px;padding:0 .6rem;border-radius:8px;cursor:pointer;list-style:none;font-weight:600}.sp-lang summary::-webkit-details-marker{display:none}.sp-lang summary:hover{background:rgba(127,127,127,.14)}.sp-lang summary:focus-visible{outline:2px solid currentColor}.sp-lang ul{list-style:none;margin:0;padding:0}.sp-lang details ul{position:absolute;right:0;top:calc(100% + 6px);z-index:30;min-width:190px;padding:.4rem;background:var(--sp-bg,#fff);color:var(--sp-text,#1f2937);border:1px solid var(--sp-line,#e3e7ee);border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.15)}.sp-lang details li>*{display:block;padding:.5rem .7rem;border-radius:6px;text-decoration:none;color:inherit}.sp-lang details a:hover{background:var(--sp-surface,#f6f7f9)}.sp-lang [aria-current]{font-weight:700}.sp-lang small{opacity:.7;font-size:.75em}.sp-lang--inline ul{display:flex;flex-wrap:wrap;gap:.25rem .75rem}.sp-lang--inline a{color:inherit}@media (max-width:921px){.sp-hb-main .sp-hb-zone>.sp-lang{display:block!important}}</style>';
}

/**
 * Header builder element (SEMRS theme).
 *
 * @param array $els Elements.
 * @return array
 */
function semrs_pro_lang_element( $els ) {
	$els['languages'] = __( 'Language switcher (Pro)', 'semrs-pro' );
	return $els;
}
add_filter( 'semrs_theme_header_elements', 'semrs_pro_lang_element' );
add_filter( 'semrs_theme_footer_elements', 'semrs_pro_lang_element' );
add_action(
	'semrs_theme_render_element_languages',
	function () {
		echo semrs_pro_lang_switcher(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
	}
);
