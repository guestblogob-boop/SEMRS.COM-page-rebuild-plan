<?php
/**
 * Header & footer builder (free).
 *
 * Customizer → SEMRS Theme → Header builder / Footer builder: drag elements
 * into the left, centre and right zones of each row. The layout is stored as
 * JSON in two theme mods; everything renders in PHP with no extra requests.
 *
 * Add-ons can add elements with the `semrs_theme_header_elements` and
 * `semrs_theme_footer_elements` filters (label) and the
 * `semrs_theme_render_element_{id}` action (output).
 *
 * @package semrs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Header elements: id => label.
 *
 * @return array
 */
function semrs_theme_header_elements() {
	return apply_filters(
		'semrs_theme_header_elements',
		array(
			'logo'    => __( 'Logo / site title', 'semrs' ),
			'menu'    => __( 'Primary menu', 'semrs' ),
			'menu2'   => __( 'Secondary menu', 'semrs' ),
			'button'  => __( 'Button', 'semrs' ),
			'button2' => __( 'Second button', 'semrs' ),
			'search'  => __( 'Search', 'semrs' ),
			'social'  => __( 'Social icons', 'semrs' ),
			'contact' => __( 'Phone & email', 'semrs' ),
			'html1'   => __( 'Text / HTML 1', 'semrs' ),
			'html2'   => __( 'Text / HTML 2', 'semrs' ),
		)
	);
}

/**
 * Footer bottom-bar elements: id => label.
 *
 * @return array
 */
function semrs_theme_footer_elements() {
	return apply_filters(
		'semrs_theme_footer_elements',
		array(
			'copyright'   => __( 'Copyright', 'semrs' ),
			'social'      => __( 'Social icons', 'semrs' ),
			'footer_menu' => __( 'Footer menu', 'semrs' ),
			'contact'     => __( 'Phone & email', 'semrs' ),
			'html3'       => __( 'Footer Text / HTML', 'semrs' ),
		)
	);
}

/**
 * Default layouts (match the original theme header and footer).
 *
 * @param string $which header|footer.
 * @return array
 */
function semrs_theme_builder_default( $which ) {
	if ( 'footer' === $which ) {
		return array( 'bottom' => array( 'left' => array( 'copyright' ), 'center' => array( 'social' ), 'right' => array( 'footer_menu' ) ) );
	}
	return array(
		'top'    => array( 'left' => array(), 'center' => array(), 'right' => array() ),
		'main'   => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'menu', 'button' ) ),
		'bottom' => array( 'left' => array(), 'center' => array(), 'right' => array() ),
	);
}

/**
 * Clean a layout: known rows/zones/elements only, each element once.
 *
 * @param mixed  $value JSON string or array.
 * @param string $which header|footer.
 * @return array
 */
function semrs_theme_builder_clean( $value, $which ) {
	$data     = is_array( $value ) ? $value : json_decode( (string) $value, true );
	$default  = semrs_theme_builder_default( $which );
	$elements = 'footer' === $which ? semrs_theme_footer_elements() : semrs_theme_header_elements();
	if ( ! is_array( $data ) ) {
		return $default;
	}
	$used = array();
	$out  = array();
	foreach ( $default as $row => $zones ) {
		foreach ( array( 'left', 'center', 'right' ) as $zone ) {
			$out[ $row ][ $zone ] = array();
			foreach ( (array) ( $data[ $row ][ $zone ] ?? array() ) as $el ) {
				$el = sanitize_key( $el );
				if ( isset( $elements[ $el ] ) && ! isset( $used[ $el ] ) ) {
					$out[ $row ][ $zone ][] = $el;
					$used[ $el ]            = true;
				}
			}
		}
	}
	return $out;
}

/**
 * Current layout.
 *
 * @param string $which header|footer.
 * @return array
 */
function semrs_theme_builder_layout( $which ) {
	static $cache = array();
	if ( ! isset( $cache[ $which ] ) ) {
		$raw             = get_theme_mod( 'semrs_theme_' . ( 'footer' === $which ? 'fb' : 'hb' ) . '_layout', '' );
		$cache[ $which ] = '' === $raw ? semrs_theme_builder_default( $which ) : semrs_theme_builder_clean( $raw, $which );
	}
	return $cache[ $which ];
}

/**
 * Is an element placed anywhere in the header?
 *
 * @param string $el Element id.
 * @return bool
 */
function semrs_theme_header_has( $el ) {
	foreach ( semrs_theme_builder_layout( 'header' ) as $zones ) {
		foreach ( $zones as $items ) {
			if ( in_array( $el, $items, true ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Inline SVG used by elements.
 *
 * @param string $name search|phone|mail|close.
 * @return string
 */
function semrs_theme_ui_icon( $name ) {
	$paths = array(
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'close'  => '<path d="M6 6l12 12M18 6 6 18"/>',
	);
	return '<svg class="sp-ui-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/**
 * Render one element.
 *
 * @param string $el   Element id.
 * @param string $area header|footer.
 */
function semrs_theme_render_element( $el, $area ) {
	switch ( $el ) {
		case 'logo':
			echo '<div class="site-branding">';
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				$tag = is_front_page() ? 'h1' : 'p';
				printf( '<%1$s class="site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>', tag_escape( $tag ), esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
			}
			if ( semrs_theme_opt( 'show_tagline' ) && get_bloginfo( 'description' ) ) {
				echo '<p class="site-description">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
			}
			echo '</div>';
			break;

		case 'menu':
			if ( ! has_nav_menu( 'primary' ) ) {
				break;
			}
			echo '<nav id="site-navigation" class="main-navigation" aria-label="' . esc_attr__( 'Primary', 'semrs' ) . '">';
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_id'        => 'primary-menu',
					'fallback_cb'    => false,
				)
			);
			$cta_text = semrs_theme_opt( 'cta_text' );
			$cta_url  = semrs_theme_opt( 'cta_url' );
			if ( $cta_text && $cta_url ) {
				printf( '<div class="sp-mobile-cta"><a class="sp-button" href="%s">%s</a></div>', esc_url( $cta_url ), esc_html( $cta_text ) );
			}
			echo '</nav>';
			break;

		case 'menu2':
			if ( has_nav_menu( 'secondary' ) ) {
				wp_nav_menu(
					array(
						'theme_location'       => 'secondary',
						'container'            => 'nav',
						'container_class'      => 'sp-menu2',
						'container_aria_label' => __( 'Secondary', 'semrs' ),
						'depth'                => 1,
						'fallback_cb'          => false,
					)
				);
			}
			break;

		case 'button':
		case 'button2':
			$text = semrs_theme_opt( 'button' === $el ? 'cta_text' : 'btn2_text' );
			$url  = semrs_theme_opt( 'button' === $el ? 'cta_url' : 'btn2_url' );
			if ( $text && $url ) {
				printf( '<a class="sp-button %1$s" href="%2$s">%3$s</a>', 'button' === $el ? 'header-cta' : 'sp-button--outline header-cta2', esc_url( $url ), esc_html( $text ) );
			}
			break;

		case 'search':
			$id = 'sp-search-' . $area;
			printf(
				'<div class="sp-search"><button type="button" class="sp-search-toggle" aria-expanded="false" aria-controls="%1$s"><span class="screen-reader-text">%2$s</span>%3$s</button><div class="sp-search-panel" id="%1$s" hidden>%4$s</div></div>',
				esc_attr( $id ),
				esc_html__( 'Search', 'semrs' ),
				semrs_theme_ui_icon( 'search' ), // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
				get_search_form( array( 'echo' => false ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- core markup.
			);
			break;

		case 'social':
			echo semrs_theme_social_icons(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
			break;

		case 'contact':
			$phone = trim( (string) semrs_theme_opt( 'phone' ) );
			$email = sanitize_email( (string) semrs_theme_opt( 'email' ) );
			if ( ! $phone && ! $email ) {
				break;
			}
			echo '<div class="sp-contact">';
			if ( $phone ) {
				printf( '<a href="tel:%1$s">%2$s<span>%3$s</span></a>', esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ), semrs_theme_ui_icon( 'phone' ), esc_html( $phone ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( $email ) {
				printf( '<a href="mailto:%1$s">%2$s<span>%1$s</span></a>', esc_attr( antispambot( $email ) ), semrs_theme_ui_icon( 'mail' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div>';
			break;

		case 'html1':
		case 'html2':
		case 'html3':
			$html = semrs_theme_opt( $el );
			if ( $html ) {
				echo '<div class="sp-html">' . wp_kses_post( do_shortcode( $html ) ) . '</div>';
			}
			break;

		case 'copyright':
			echo '<p class="site-info">' . esc_html( semrs_theme_copyright() ) . '</p>';
			break;

		case 'footer_menu':
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location'       => 'footer',
						'container'            => 'nav',
						'container_aria_label' => __( 'Footer', 'semrs' ),
						'menu_class'           => 'footer-menu',
						'depth'                => 1,
						'fallback_cb'          => false,
					)
				);
			}
			break;

		default:
			do_action( 'semrs_theme_render_element_' . $el, $area );
	}
}

/**
 * Render a builder row.
 *
 * @param string $which header|footer.
 * @param string $row   Row id.
 */
function semrs_theme_render_row( $which, $row ) {
	$layout = semrs_theme_builder_layout( $which );
	$zones  = $layout[ $row ] ?? array();
	$any    = false;
	foreach ( $zones as $items ) {
		$any = $any || ! empty( $items );
	}
	$is_main = 'header' === $which && 'main' === $row;
	if ( ! $any && ! $is_main ) {
		return;
	}
	if ( $is_main ) {
		echo '<div class="sp-hb-row sp-hb-main"><div class="sp-container header-inner">';
	} else {
		printf( '<div class="sp-hb-row sp-hb-%1$s"><div class="sp-container sp-hb-inner">', esc_attr( $row ) );
	}
	foreach ( array( 'left', 'center', 'right' ) as $zone ) {
		$items = $zones[ $zone ] ?? array();
		if ( ! $items && 'center' !== $zone ) {
			echo '<div class="sp-hb-zone sp-hb-' . esc_attr( $zone ) . ' is-empty"></div>';
			continue;
		}
		echo '<div class="sp-hb-zone sp-hb-' . esc_attr( $zone ) . ( $items ? '' : ' is-empty' ) . '">';
		foreach ( $items as $el ) {
			semrs_theme_render_element( $el, $which );
		}
		echo '</div>';
	}
	if ( $is_main ) {
		do_action( 'semrs_header_actions' );
		if ( semrs_theme_header_has( 'menu' ) && has_nav_menu( 'primary' ) ) {
			echo '<button class="menu-toggle" aria-controls="site-navigation" aria-expanded="false"><span class="menu-toggle-icon" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Menu', 'semrs' ) . '</span></button>';
		}
	}
	echo '</div></div>';
}

/**
 * Header/footer body classes: sticky, transparent, per-page switches.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function semrs_theme_builder_body_classes( $classes ) {
	$id          = is_singular() ? get_queried_object_id() : 0;
	$transparent = semrs_theme_opt( 'transparent' );
	$own         = $id ? get_post_meta( $id, 'semrs_transparent', true ) : '';
	$is_trans    = 'on' === $own || ( 'off' !== $own && ( 'all' === $transparent || ( 'front' === $transparent && is_front_page() ) ) );
	if ( semrs_theme_opt( 'sticky_header' ) ) {
		$classes[] = 'sp-sticky-header';
		if ( semrs_theme_opt( 'sticky_shrink' ) ) {
			$classes[] = 'sp-sticky-shrink';
		}
	}
	if ( $is_trans ) {
		$classes[] = 'sp-transparent-header';
		if ( semrs_theme_opt( 'transparent_light' ) ) {
			$classes[] = 'sp-transparent-light';
		}
	}
	if ( semrs_theme_opt( 'top_mobile' ) ) {
		$classes[] = 'sp-top-mobile';
	}
	if ( semrs_theme_opt( 'mobile_button' ) ) {
		$classes[] = 'sp-mobile-button';
	}
	return $classes;
}
add_filter( 'body_class', 'semrs_theme_builder_body_classes' );

/**
 * Header/footer hidden on this page (Page options)?
 *
 * @param string $part header|footer.
 * @return bool
 */
function semrs_theme_part_hidden( $part ) {
	return is_singular() && get_post_meta( get_queried_object_id(), 'semrs_hide_' . $part, true );
}

/**
 * Customizer: the drag-and-drop builder control.
 *
 * @param WP_Customize_Manager $wpc Customizer.
 */
function semrs_theme_builder_customize( $wpc ) {
	require_once SEMRS_THEME_DIR . '/inc/class-semrs-builder-control.php';
	$builders = array(
		'hb' => array( 'header', 'semrs_theme_hbuilder', __( 'Header layout', 'semrs' ), array( 'top' => __( 'Top bar', 'semrs' ), 'main' => __( 'Main header', 'semrs' ), 'bottom' => __( 'Below header', 'semrs' ) ), semrs_theme_header_elements() ),
		'fb' => array( 'footer', 'semrs_theme_fbuilder', __( 'Footer bottom bar', 'semrs' ), array( 'bottom' => __( 'Bottom bar', 'semrs' ) ), semrs_theme_footer_elements() ),
	);
	foreach ( $builders as $key => $b ) {
		$which = $b[0];
		$wpc->add_setting(
			'semrs_theme_' . $key . '_layout',
			array(
				'default'           => wp_json_encode( semrs_theme_builder_default( $which ) ),
				'sanitize_callback' => function ( $v ) use ( $which ) {
					return wp_json_encode( semrs_theme_builder_clean( $v, $which ) );
				},
				'transport'         => 'refresh',
			)
		);
		$wpc->add_control(
			new Semrs_Builder_Control(
				$wpc,
				'semrs_theme_' . $key . '_layout',
				array(
					'label'       => $b[2],
					'description' => __( 'Drag elements between zones, or use + to add and × to remove. Each element can be used once. On phones the header shows the logo and the menu icon; the menu opens as a panel.', 'semrs' ),
					'section'     => $b[1],
					'priority'    => 1,
					'rows'        => $b[3],
					'elements'    => $b[4],
				)
			)
		);
	}
}
add_action( 'customize_register', 'semrs_theme_builder_customize', 20 );

/**
 * Builder control assets.
 */
function semrs_theme_builder_customize_assets() {
	wp_enqueue_style( 'semrs-builder', SEMRS_THEME_URI . '/assets/css/customizer-builder.css', array(), SEMRS_THEME_VERSION );
	wp_enqueue_script( 'semrs-builder', SEMRS_THEME_URI . '/assets/js/customizer-builder.js', array( 'customize-controls' ), SEMRS_THEME_VERSION, true );
	wp_localize_script(
		'semrs-builder',
		'SEMRS_BUILDER_I18N',
		array(
			'add'    => __( 'Add element', 'semrs' ),
			'remove' => __( 'Remove', 'semrs' ),
			'none'   => __( 'All elements are in use', 'semrs' ),
			'left'   => __( 'Left', 'semrs' ),
			'center' => __( 'Centre', 'semrs' ),
			'right'  => __( 'Right', 'semrs' ),
		)
	);
}
add_action( 'customize_controls_enqueue_scripts', 'semrs_theme_builder_customize_assets' );
