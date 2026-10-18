<?php
/**
 * Local SEO (multi-location): Locations post type with NAP, hours, map
 * link and service areas; LocalBusiness schema per location (joined to the
 * SEMRS Core schema graph); [semrs_locations] and [semrs_location] shortcodes.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Business types offered.
 *
 * @return string[]
 */
function semrs_local_types() {
	return array( 'LocalBusiness', 'ProfessionalService', 'HomeAndConstructionBusiness', 'Plumber', 'Electrician', 'HVACBusiness', 'RoofingContractor', 'GeneralContractor', 'Locksmith', 'MovingCompany', 'Dentist', 'MedicalClinic', 'Physician', 'LegalService', 'Attorney', 'AccountingService', 'RealEstateAgent', 'InsuranceAgency', 'AutoRepair', 'BeautySalon', 'HairSalon', 'DaySpa', 'Restaurant', 'CafeOrCoffeeShop', 'Store', 'ExerciseGym', 'ChildCare', 'VeterinaryCare', 'CleaningService' );
}

/**
 * Fields: key => [label, type].
 *
 * @return array<string, array<int, string>>
 */
function semrs_local_fields() {
	return array(
		'type'    => array( __( 'Business type', 'semrs-pro' ), 'select' ),
		'street'  => array( __( 'Street address', 'semrs-pro' ), 'text' ),
		'city'    => array( __( 'City', 'semrs-pro' ), 'text' ),
		'region'  => array( __( 'State / province / county', 'semrs-pro' ), 'text' ),
		'postal'  => array( __( 'ZIP / postal code', 'semrs-pro' ), 'text' ),
		'country' => array( __( 'Country code (US, GB, CA, AU, NZ, IE…)', 'semrs-pro' ), 'text' ),
		'phone'   => array( __( 'Phone (international format)', 'semrs-pro' ), 'text' ),
		'email'   => array( __( 'Email', 'semrs-pro' ), 'text' ),
		'lat'     => array( __( 'Latitude', 'semrs-pro' ), 'text' ),
		'lng'     => array( __( 'Longitude', 'semrs-pro' ), 'text' ),
		'areas'   => array( __( 'Areas served (comma-separated)', 'semrs-pro' ), 'text' ),
		'gbp'     => array( __( 'Google Business Profile URL', 'semrs-pro' ), 'url' ),
		'price'   => array( __( 'Price range (e.g. $$)', 'semrs-pro' ), 'text' ),
	);
}

/**
 * Days for opening hours.
 *
 * @return string[]
 */
function semrs_local_days() {
	return array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
}

/**
 * Post type.
 */
function semrs_local_register() {
	register_post_type(
		'semrs_location',
		array(
			'labels'       => array(
				'name'          => __( 'Locations', 'semrs-pro' ),
				'singular_name' => __( 'Location', 'semrs-pro' ),
				'add_new_item'  => __( 'Add location', 'semrs-pro' ),
			),
			'public'       => true,
			'has_archive'  => 'locations',
			'rewrite'      => array( 'slug' => 'locations', 'with_front' => false ),
			'show_in_menu' => 'semrs',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		)
	);
}
add_action( 'init', 'semrs_local_register' );

/**
 * Location data array.
 *
 * @param int $id Post ID.
 * @return array
 */
function semrs_local_get( $id ) {
	$d = array();
	foreach ( array_keys( semrs_local_fields() ) as $k ) {
		$d[ $k ] = (string) get_post_meta( $id, '_semrs_loc_' . $k, true );
	}
	$hours      = get_post_meta( $id, '_semrs_loc_hours', true );
	$d['hours'] = is_array( $hours ) ? $hours : array();
	$d['name']  = get_the_title( $id );
	$d['url']   = get_permalink( $id );
	return $d;
}

/**
 * Meta box.
 */
function semrs_local_metabox() {
	add_meta_box( 'semrs_location_details', __( 'Location details (NAP, hours, map)', 'semrs-pro' ), 'semrs_local_render_metabox', 'semrs_location', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'semrs_local_metabox' );

/**
 * Render the meta box.
 *
 * @param WP_Post $post Post.
 */
function semrs_local_render_metabox( $post ) {
	wp_nonce_field( 'semrs_location', 'semrs_location_nonce' );
	$d = semrs_local_get( $post->ID );
	echo '<p class="description">' . esc_html__( 'Use the exact name, address and phone number shown on this location’s Google Business Profile. Real addresses only.', 'semrs-pro' ) . '</p><table class="form-table" role="presentation">';
	foreach ( semrs_local_fields() as $k => $f ) {
		echo '<tr><th><label for="semrs_loc_' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'select' === $f[1] ) {
			echo '<select id="semrs_loc_' . esc_attr( $k ) . '" name="semrs_loc[' . esc_attr( $k ) . ']">';
			foreach ( semrs_local_types() as $t ) {
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $t ), selected( $d[ $k ] ? $d[ $k ] : 'LocalBusiness', $t, false ), esc_html( preg_replace( '/([a-z])([A-Z])/', '$1 $2', $t ) ) );
			}
			echo '</select>';
		} else {
			printf( '<input type="%1$s" class="regular-text" id="semrs_loc_%2$s" name="semrs_loc[%2$s]" value="%3$s">', 'url' === $f[1] ? 'url' : 'text', esc_attr( $k ), esc_attr( $d[ $k ] ) );
		}
		echo '</td></tr>';
	}
	echo '<tr><th>' . esc_html__( 'Opening hours', 'semrs-pro' ) . '</th><td>';
	foreach ( semrs_local_days() as $day ) {
		$h = isset( $d['hours'][ $day ] ) ? $d['hours'][ $day ] : array( 'open' => '', 'close' => '' );
		printf(
			'<div style="margin-bottom:4px"><span style="display:inline-block;width:100px">%1$s</span><input type="time" name="semrs_loc_hours[%2$s][open]" value="%3$s"> – <input type="time" name="semrs_loc_hours[%2$s][close]" value="%4$s"></div>',
			esc_html( $day ),
			esc_attr( $day ),
			esc_attr( $h['open'] ),
			esc_attr( $h['close'] )
		);
	}
	echo '<p class="description">' . esc_html__( 'Leave both times empty for closed days.', 'semrs-pro' ) . '</p></td></tr></table>';
}

/**
 * Save.
 *
 * @param int $post_id Post ID.
 */
function semrs_local_save( $post_id ) {
	if ( ! isset( $_POST['semrs_location_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['semrs_location_nonce'] ) ), 'semrs_location' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['semrs_loc'] ) ? (array) wp_unslash( $_POST['semrs_loc'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
	foreach ( semrs_local_fields() as $k => $f ) {
		$v = isset( $in[ $k ] ) ? (string) $in[ $k ] : '';
		if ( 'url' === $f[1] ) {
			$v = esc_url_raw( $v );
		} elseif ( 'select' === $f[1] ) {
			$v = in_array( $v, semrs_local_types(), true ) ? $v : 'LocalBusiness';
		} elseif ( in_array( $k, array( 'lat', 'lng' ), true ) ) {
			$v = is_numeric( $v ) ? (string) (float) $v : '';
		} elseif ( 'country' === $k ) {
			$v = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $v ), 0, 2 ) );
		} else {
			$v = sanitize_text_field( $v );
		}
		update_post_meta( $post_id, '_semrs_loc_' . $k, $v );
	}
	$hours = array();
	$raw   = isset( $_POST['semrs_loc_hours'] ) ? (array) wp_unslash( $_POST['semrs_loc_hours'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below.
	foreach ( semrs_local_days() as $day ) {
		$o = isset( $raw[ $day ]['open'] ) && preg_match( '/^\d{2}:\d{2}$/', $raw[ $day ]['open'] ) ? $raw[ $day ]['open'] : '';
		$c = isset( $raw[ $day ]['close'] ) && preg_match( '/^\d{2}:\d{2}$/', $raw[ $day ]['close'] ) ? $raw[ $day ]['close'] : '';
		$hours[ $day ] = array( 'open' => $o, 'close' => $c );
	}
	update_post_meta( $post_id, '_semrs_loc_hours', $hours );
}
add_action( 'save_post_semrs_location', 'semrs_local_save' );

/**
 * Address in one line.
 *
 * @param array $d Location data.
 * @return string
 */
function semrs_local_address_line( $d ) {
	return implode( ', ', array_filter( array( $d['street'], $d['city'], trim( $d['region'] . ' ' . $d['postal'] ), $d['country'] ) ) );
}

/**
 * Location card HTML (visible NAP that matches the schema).
 *
 * @param int $id Location ID.
 * @return string
 */
function semrs_local_card( $id ) {
	$d    = semrs_local_get( $id );
	$addr = semrs_local_address_line( $d );
	$map  = $d['lat'] && $d['lng'] ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $d['lat'] . ',' . $d['lng'] ) : ( $addr ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $d['name'] . ' ' . $addr ) : '' );
	$out  = '<div class="semrs-location-card semrs-card"><h3><a href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['name'] ) . '</a></h3>';
	if ( $addr ) {
		$out .= '<p class="semrs-location-card__addr">' . esc_html( $addr ) . '</p>';
	}
	if ( $d['phone'] ) {
		$out .= '<p><a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $d['phone'] ) ) . '">' . esc_html( $d['phone'] ) . '</a></p>';
	}
	$rows = '';
	foreach ( semrs_local_days() as $day ) {
		$h     = isset( $d['hours'][ $day ] ) ? $d['hours'][ $day ] : array();
		$rows .= '<tr><th>' . esc_html( $day ) . '</th><td>' . ( ! empty( $h['open'] ) && ! empty( $h['close'] ) ? esc_html( $h['open'] . ' – ' . $h['close'] ) : esc_html__( 'Closed', 'semrs-pro' ) ) . '</td></tr>';
	}
	$out .= '<table class="semrs-location-card__hours"><tbody>' . $rows . '</tbody></table>';
	$links = array();
	if ( $map ) {
		$links[] = '<a href="' . esc_url( $map ) . '" target="_blank" rel="noopener">' . esc_html__( 'Directions', 'semrs-pro' ) . '</a>';
	}
	if ( $d['gbp'] ) {
		$links[] = '<a href="' . esc_url( $d['gbp'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Google reviews', 'semrs-pro' ) . '</a>';
	}
	if ( $links ) {
		$out .= '<p class="semrs-location-card__links">' . implode( ' · ', $links ) . '</p>';
	}
	if ( $d['areas'] ) {
		$out .= '<p class="semrs-location-card__areas"><strong>' . esc_html__( 'Areas served:', 'semrs-pro' ) . '</strong> ' . esc_html( $d['areas'] ) . '</p>';
	}
	return $out . '</div>';
}

/**
 * [semrs_location id="123"]
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_local_location_sc( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'semrs_location' );
	$id   = absint( $atts['id'] );
	return $id && 'semrs_location' === get_post_type( $id ) ? semrs_local_card( $id ) : '';
}
add_shortcode( 'semrs_location', 'semrs_local_location_sc' );

/**
 * [semrs_locations] directory.
 *
 * @return string
 */
function semrs_local_locations_sc() {
	$ids = get_posts( array( 'post_type' => 'semrs_location', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true ) );
	if ( ! $ids ) {
		return '';
	}
	return '<div class="semrs-locations">' . implode( '', array_map( 'semrs_local_card', $ids ) ) . '</div>';
}
add_shortcode( 'semrs_locations', 'semrs_local_locations_sc' );

/**
 * Show the card on single location pages automatically.
 *
 * @param string $content Content.
 * @return string
 */
function semrs_local_single_content( $content ) {
	if ( is_singular( 'semrs_location' ) && in_the_loop() && is_main_query() && false === strpos( $content, 'semrs-location-card' ) ) {
		$content = semrs_local_card( get_the_ID() ) . $content;
	}
	return $content;
}
add_filter( 'the_content', 'semrs_local_single_content', 9 );

/**
 * Card styles (small, only where used).
 */
function semrs_local_styles() {
	if ( is_singular( 'semrs_location' ) || is_post_type_archive( 'semrs_location' ) || ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'semrs_location' ) ) || ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'semrs_locations' ) ) ) {
		wp_register_style( 'semrs-local', false, array(), SEMRS_PRO_VERSION );
		wp_enqueue_style( 'semrs-local' );
		wp_add_inline_style( 'semrs-local', '.semrs-locations{display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}.semrs-location-card h3{margin-top:0}.semrs-location-card__hours{font-size:.9rem}.semrs-location-card__hours th,.semrs-location-card__hours td{padding:.25rem .5rem;border:0;border-bottom:1px solid var(--sp-line,#e3e7ee);background:none}' );
	}
}
add_action( 'wp_enqueue_scripts', 'semrs_local_styles' );

/**
 * LocalBusiness node on single location pages (joined to SEMRS Core's graph).
 *
 * @param array $graph Graph.
 * @return array
 */
function semrs_local_schema( $graph ) {
	if ( ! is_singular( 'semrs_location' ) ) {
		return $graph;
	}
	$id = get_queried_object_id();
	$d  = semrs_local_get( $id );
	$o  = array(
		'@type'      => $d['type'] ? $d['type'] : 'LocalBusiness',
		'@id'        => $d['url'] . '#localbusiness',
		'name'       => $d['name'],
		'url'        => $d['url'],
		'telephone'  => $d['phone'],
		'email'      => $d['email'],
		'priceRange' => $d['price'],
		'image'      => has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'full' ) : '',
		'parentOrganization' => array( '@id' => home_url( '/' ) . '#organization' ),
	);
	if ( $d['street'] || $d['city'] ) {
		$o['address'] = array_filter( array( '@type' => 'PostalAddress', 'streetAddress' => $d['street'], 'addressLocality' => $d['city'], 'addressRegion' => $d['region'], 'postalCode' => $d['postal'], 'addressCountry' => $d['country'] ) );
	}
	if ( $d['lat'] && $d['lng'] ) {
		$o['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $d['lat'], 'longitude' => (float) $d['lng'] );
	}
	$groups = array();
	foreach ( $d['hours'] as $day => $h ) {
		if ( ! empty( $h['open'] ) && ! empty( $h['close'] ) ) {
			$groups[ $h['open'] . '-' . $h['close'] ][] = $day;
		}
	}
	foreach ( $groups as $span => $days ) {
		list( $open, $close ) = explode( '-', $span );
		$o['openingHoursSpecification'][] = array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => $open, 'closes' => $close );
	}
	$areas = array_filter( array_map( 'trim', explode( ',', $d['areas'] ) ) );
	if ( $areas ) {
		$o['areaServed'] = array_values( $areas );
	}
	if ( $d['gbp'] ) {
		$o['sameAs'] = array( $d['gbp'] );
	}
	$graph[] = array_filter( $o );
	return $graph;
}
add_filter( 'semrs_schema_graph', 'semrs_local_schema' );
