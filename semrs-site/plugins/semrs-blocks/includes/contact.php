<?php
/**
 * Contact Form block: name, email, phone with country picker, WhatsApp
 * preference, company, website, service, budget, requirements, consent.
 * Submissions are saved as Leads and emailed. Spam protection without
 * CAPTCHA: honeypot, minimum fill time, per-IP rate limit, link limit.
 *
 * @package semrs-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block definition.
 *
 * @param array $blocks Blocks.
 * @return array
 */
function semrs_blocks_contact_def( $blocks ) {
	$blocks['semrs/contact-form'] = array(
		'title'    => __( 'Contact Form', 'semrs-blocks' ),
		'icon'     => 'email-alt',
		'keywords' => array( 'contact', 'form', 'lead', 'quote', 'whatsapp', 'phone' ),
		'render'   => 'semrs_blocks_render_contact',
		'panels'   => array(
			__( 'Fields', 'semrs-blocks' )          => array( 'showPhone', 'defaultCountry', 'showWhatsapp', 'showCompany', 'showWebsite', 'services', 'budgets', 'messageLabel' ),
			__( 'After sending', 'semrs-blocks' )   => array( 'recipient', 'subject', 'success', 'redirect' ),
			__( 'Text & contact', 'semrs-blocks' )  => array( 'button', 'consent', 'whatsappNumber', 'email', 'phone' ),
		),
		'controls' => array(
			'showPhone'      => array( 'type' => 'toggle', 'label' => __( 'Phone with country picker', 'semrs-blocks' ), 'default' => true ),
			'defaultCountry' => array( 'type' => 'text', 'label' => __( 'Default country (2-letter code, e.g. US, GB, PK)', 'semrs-blocks' ), 'default' => 'US' ),
			'showWhatsapp'   => array( 'type' => 'toggle', 'label' => __( '"Contact me on WhatsApp" option', 'semrs-blocks' ), 'default' => true ),
			'showCompany'    => array( 'type' => 'toggle', 'label' => __( 'Company field', 'semrs-blocks' ), 'default' => false ),
			'showWebsite'    => array( 'type' => 'toggle', 'label' => __( 'Website field', 'semrs-blocks' ), 'default' => true ),
			'services'       => array( 'type' => 'textarea', 'label' => __( 'Services (one per line; empty = hide)', 'semrs-blocks' ), 'default' => "SEO\nWeb design\nContent management\nPaid ads\nSomething else" ),
			'budgets'        => array( 'type' => 'textarea', 'label' => __( 'Budget options (one per line; empty = hide)', 'semrs-blocks' ), 'default' => "Under $500/month\n$500–$1,500/month\n$1,500–$5,000/month\nNot sure yet" ),
			'messageLabel'   => array( 'type' => 'text', 'label' => __( 'Requirements field label', 'semrs-blocks' ), 'default' => 'Tell us about your project' ),
			'recipient'      => array( 'type' => 'text', 'label' => __( 'Send to email (empty = site admin email)', 'semrs-blocks' ), 'default' => '' ),
			'subject'        => array( 'type' => 'text', 'label' => __( 'Email subject', 'semrs-blocks' ), 'default' => 'New enquiry from your website' ),
			'success'        => array( 'type' => 'textarea', 'label' => __( 'Success message', 'semrs-blocks' ), 'default' => 'Thank you! We reply within one business day.' ),
			'redirect'       => array( 'type' => 'url', 'label' => __( 'Redirect after sending (optional, e.g. /thank-you/)', 'semrs-blocks' ), 'default' => '' ),
			'button'         => array( 'type' => 'text', 'label' => __( 'Button text', 'semrs-blocks' ), 'default' => 'Send my request' ),
			'consent'        => array( 'type' => 'textarea', 'label' => __( 'Consent checkbox text (empty = no checkbox)', 'semrs-blocks' ), 'default' => 'I agree that my details are used to reply to this enquiry, as described in the Privacy Policy.' ),
			'whatsappNumber' => array( 'type' => 'text', 'label' => __( 'Your WhatsApp number to show (digits with country code)', 'semrs-blocks' ), 'default' => '' ),
			'email'          => array( 'type' => 'text', 'label' => __( 'Your email to show', 'semrs-blocks' ), 'default' => '' ),
			'phone'          => array( 'type' => 'text', 'label' => __( 'Your phone to show', 'semrs-blocks' ), 'default' => '' ),
		),
	);
	return $blocks;
}
add_filter( 'semrs_blocks', 'semrs_blocks_contact_def', 6 );

/**
 * Settings that must never come from the browser are stored server-side,
 * keyed by a hash of the block settings.
 *
 * @param array $a Attributes.
 * @return string Form key.
 */
function semrs_blocks_contact_store( $a ) {
	$keep  = array_intersect_key( $a, array_flip( array( 'recipient', 'subject', 'services', 'budgets', 'showPhone', 'showWhatsapp', 'showCompany', 'showWebsite', 'consent', 'success', 'redirect' ) ) );
	$key   = substr( md5( wp_json_encode( $keep ) ), 0, 16 );
	$forms = get_option( 'semrs_contact_forms', array() );
	if ( ! isset( $forms[ $key ] ) ) {
		$forms[ $key ] = $keep;
		update_option( 'semrs_contact_forms', array_slice( $forms, -50, null, true ), false );
	}
	return $key;
}

/**
 * Render.
 *
 * @param array $a Attributes.
 * @return string
 */
function semrs_blocks_render_contact( $a ) {
	$a = wp_parse_args( $a, array_map( function ( $c ) { return $c['default']; }, semrs_blocks_contact_def( array() )['semrs/contact-form']['controls'] ) );
	semrs_blocks_script( 'semrs-blocks-contact', 'contact.js' );
	// Same-origin path, so www / non-www and http / https mismatches never block the request.
	wp_localize_script( 'semrs-blocks-contact', 'SEMRS_CONTACT', array( 'endpoint' => wp_make_link_relative( esc_url_raw( rest_url( 'semrs-blocks/v1/contact' ) ) ) ) );
	$key = semrs_blocks_contact_store( $a );
	$uid = 'semrs-cf-' . $key . '-' . wp_rand( 100, 999 );
	$f   = function ( $name, $label, $input, $req = false ) use ( $uid ) {
		return '<p class="semrs-cf__field semrs-cf__field--' . esc_attr( $name ) . '"><label for="' . esc_attr( $uid . '-' . $name ) . '">' . esc_html( $label ) . ( $req ? ' <span aria-hidden="true">*</span>' : '' ) . '</label>' . $input . '</p>';
	};
	$opt = function ( $lines, $placeholder ) {
		$o = '<option value="">' . esc_html( $placeholder ) . '</option>';
		foreach ( semrs_blocks_lines( $lines ) as $l ) {
			$o .= '<option>' . esc_html( $l ) . '</option>';
		}
		return $o;
	};
	$id  = function ( $n ) use ( $uid ) {
		return esc_attr( $uid . '-' . $n );
	};

	$html  = '<form class="semrs-cf" novalidate data-form="' . esc_attr( $key ) . '" data-redirect="' . esc_url( semrs_blocks_a( $a, 'redirect' ) ) . '">';
	$html .= '<div class="semrs-cf__grid">';
	$html .= $f( 'name', __( 'Your name', 'semrs-blocks' ), '<input id="' . $id( 'name' ) . '" name="name" type="text" autocomplete="name" required maxlength="100">', true );
	$html .= $f( 'email', __( 'Email', 'semrs-blocks' ), '<input id="' . $id( 'email' ) . '" name="email" type="email" autocomplete="email" required maxlength="150">', true );
	if ( ! empty( $a['showPhone'] ) ) {
		$html .= $f(
			'phone',
			__( 'Phone', 'semrs-blocks' ),
			'<span class="semrs-cf__phone"><select name="country" aria-label="' . esc_attr__( 'Country code', 'semrs-blocks' ) . '" data-default="' . esc_attr( strtoupper( substr( semrs_blocks_a( $a, 'defaultCountry', 'US' ), 0, 2 ) ) ) . '"></select><input id="' . $id( 'phone' ) . '" name="phone" type="tel" autocomplete="tel-national" inputmode="tel" maxlength="20"></span>'
		);
	}
	if ( ! empty( $a['showCompany'] ) ) {
		$html .= $f( 'company', __( 'Company', 'semrs-blocks' ), '<input id="' . $id( 'company' ) . '" name="company" type="text" autocomplete="organization" maxlength="120">' );
	}
	if ( ! empty( $a['showWebsite'] ) ) {
		$html .= $f( 'website', __( 'Website', 'semrs-blocks' ), '<input id="' . $id( 'website' ) . '" name="website" type="url" placeholder="https://" maxlength="200">' );
	}
	if ( semrs_blocks_lines( semrs_blocks_a( $a, 'services' ) ) ) {
		$html .= $f( 'service', __( 'What do you need?', 'semrs-blocks' ), '<select id="' . $id( 'service' ) . '" name="service">' . $opt( $a['services'], __( 'Choose a service', 'semrs-blocks' ) ) . '</select>' );
	}
	if ( semrs_blocks_lines( semrs_blocks_a( $a, 'budgets' ) ) ) {
		$html .= $f( 'budget', __( 'Budget', 'semrs-blocks' ), '<select id="' . $id( 'budget' ) . '" name="budget">' . $opt( $a['budgets'], __( 'Choose a range', 'semrs-blocks' ) ) . '</select>' );
	}
	$html .= '</div>';
	$html .= $f( 'message', semrs_blocks_a( $a, 'messageLabel', __( 'Message', 'semrs-blocks' ) ), '<textarea id="' . $id( 'message' ) . '" name="message" rows="5" required maxlength="5000"></textarea>', true );
	if ( ! empty( $a['showWhatsapp'] ) ) {
		$html .= '<p class="semrs-cf__check"><label><input type="checkbox" name="whatsapp" value="1"> ' . esc_html__( 'You can contact me on WhatsApp', 'semrs-blocks' ) . '</label></p>';
	}
	if ( semrs_blocks_a( $a, 'consent' ) ) {
		$privacy = get_privacy_policy_url();
		$text    = esc_html( $a['consent'] );
		if ( $privacy ) {
			$text = str_replace( esc_html__( 'Privacy Policy', 'semrs-blocks' ), '<a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy Policy', 'semrs-blocks' ) . '</a>', $text );
		}
		$html .= '<p class="semrs-cf__check"><label><input type="checkbox" name="consent" value="1" required> ' . $text . '</label></p>';
	}
	// Honeypot + timer (bots fill hidden fields and submit instantly).
	$html .= '<p class="semrs-cf__hp" aria-hidden="true"><label>' . esc_html__( 'Leave this empty', 'semrs-blocks' ) . '<input type="text" name="fax_number" tabindex="-1" autocomplete="off"></label></p>';
	$now   = time();
	$html .= '<input type="hidden" name="t" value="' . esc_attr( $now . '.' . substr( wp_hash( 'semrs_cf_t' . $now ), 0, 12 ) ) . '">'; // Signed so bots cannot backdate it.
	$html .= '<p class="semrs-cf__actions"><button type="submit" class="semrs-btn">' . esc_html( semrs_blocks_a( $a, 'button', __( 'Send', 'semrs-blocks' ) ) ) . '</button></p>';
	$html .= '<div class="semrs-cf__status" role="status" aria-live="polite"></div>';
	$html .= '</form>';

	$direct = array();
	$wa     = preg_replace( '/\D+/', '', (string) semrs_blocks_a( $a, 'whatsappNumber' ) );
	if ( $wa ) {
		$direct[] = '<a class="semrs-cf__direct semrs-cf__direct--wa" href="' . esc_url( 'https://wa.me/' . $wa ) . '" target="_blank" rel="noopener">' . semrs_blocks_icon_svg( 'whatsapp', 20 ) . esc_html__( 'Chat on WhatsApp', 'semrs-blocks' ) . '</a>';
	}
	if ( is_email( semrs_blocks_a( $a, 'email' ) ) ) {
		$direct[] = '<a class="semrs-cf__direct" href="mailto:' . esc_attr( antispambot( $a['email'] ) ) . '">' . semrs_blocks_icon_svg( 'mail', 20 ) . esc_html( antispambot( $a['email'] ) ) . '</a>';
	}
	if ( semrs_blocks_a( $a, 'phone' ) ) {
		$direct[] = '<a class="semrs-cf__direct" href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $a['phone'] ) ) . '">' . semrs_blocks_icon_svg( 'phone', 20 ) . esc_html( $a['phone'] ) . '</a>';
	}
	if ( $direct ) {
		$html .= '<div class="semrs-cf__directs"><span>' . esc_html__( 'Prefer to talk directly?', 'semrs-blocks' ) . '</span>' . implode( '', $direct ) . '</div>';
	}
	return $html;
}

/**
 * Leads post type (private, admin only).
 */
function semrs_blocks_leads_type() {
	register_post_type(
		'semrs_lead',
		array(
			'labels'          => array( 'name' => __( 'Leads', 'semrs-blocks' ), 'singular_name' => __( 'Lead', 'semrs-blocks' ) ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 26,
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
		)
	);
}
add_action( 'init', 'semrs_blocks_leads_type' );

/**
 * REST route.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'semrs-blocks/v1',
			'/contact',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'semrs_blocks_contact_submit',
			)
		);
	}
);

/**
 * Handle a submission.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function semrs_blocks_contact_submit( $req ) {
	$p     = $req->get_params();
	$forms = get_option( 'semrs_contact_forms', array() );
	$cfg   = $forms[ sanitize_key( $p['form'] ?? '' ) ] ?? null;
	if ( ! $cfg ) {
		return new WP_Error( 'semrs_form', __( 'This form has changed. Please refresh the page and try again.', 'semrs-blocks' ), array( 'status' => 400 ) );
	}
	// Spam checks: honeypot, minimum 3 seconds on page, links, rate limit.
	$t      = explode( '.', (string) ( $p['t'] ?? '' ) . '.' );
	$t_ok   = hash_equals( substr( wp_hash( 'semrs_cf_t' . (int) $t[0] ), 0, 12 ), $t[1] ) && ( time() - (int) $t[0] ) >= 3;
	if ( ! empty( $p['fax_number'] ) || ! $t_ok ) {
		return rest_ensure_response( array( 'ok' => true, 'message' => wp_strip_all_tags( $cfg['success'] ?? '' ) ) ); // Quietly accept, store nothing.
	}
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate = 'semrs_cf_' . md5( $ip );
	if ( (int) get_transient( $rate ) >= 5 ) {
		return new WP_Error( 'semrs_rate', __( 'Too many messages from your connection. Please try again in an hour or email us.', 'semrs-blocks' ), array( 'status' => 429 ) );
	}

	$name    = sanitize_text_field( $p['name'] ?? '' );
	$email   = sanitize_email( $p['email'] ?? '' );
	$message = sanitize_textarea_field( $p['message'] ?? '' );
	$errors  = array();
	if ( '' === $name ) {
		$errors['name'] = __( 'Please enter your name.', 'semrs-blocks' );
	}
	if ( ! is_email( $email ) ) {
		$errors['email'] = __( 'Please enter a valid email address.', 'semrs-blocks' );
	}
	if ( strlen( $message ) < 5 ) {
		$errors['message'] = __( 'Please tell us a little about your project.', 'semrs-blocks' );
	}
	if ( ! empty( $cfg['consent'] ) && empty( $p['consent'] ) ) {
		$errors['consent'] = __( 'Please tick the consent box.', 'semrs-blocks' );
	}
	if ( preg_match_all( '~https?://~i', $message ) > 3 ) {
		$errors['message'] = __( 'Please include no more than three links.', 'semrs-blocks' );
	}
	if ( $errors ) {
		return new WP_Error( 'semrs_invalid', __( 'Please check the highlighted fields.', 'semrs-blocks' ), array( 'status' => 422, 'fields' => $errors ) );
	}
	$allowed = function ( $value, $lines ) {
		return in_array( $value, semrs_blocks_lines( $lines ), true ) ? $value : '';
	};
	$country = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $p['country'] ?? '' ) ), 0, 2 ) );
	$dial    = preg_replace( '/[^\d]/', '', (string) ( $p['dial'] ?? '' ) );
	$phone   = preg_replace( '/[^\d\s\-()]/', '', (string) ( $p['phone'] ?? '' ) );
	$data    = array(
		'name'     => $name,
		'email'    => $email,
		'phone'    => $phone ? trim( ( $dial ? '+' . $dial . ' ' : '' ) . $phone ) : '',
		'country'  => $country,
		'whatsapp' => ! empty( $p['whatsapp'] ) ? __( 'Yes', 'semrs-blocks' ) : __( 'No', 'semrs-blocks' ),
		'company'  => sanitize_text_field( $p['company'] ?? '' ),
		'website'  => esc_url_raw( $p['website'] ?? '' ),
		'service'  => $allowed( sanitize_text_field( $p['service'] ?? '' ), $cfg['services'] ?? '' ),
		'budget'   => $allowed( sanitize_text_field( $p['budget'] ?? '' ), $cfg['budgets'] ?? '' ),
		'page'     => esc_url_raw( $p['page'] ?? '' ),
	);
	$labels = array(
		'name'     => __( 'Name', 'semrs-blocks' ),
		'email'    => __( 'Email', 'semrs-blocks' ),
		'phone'    => __( 'Phone', 'semrs-blocks' ),
		'country'  => __( 'Country', 'semrs-blocks' ),
		'whatsapp' => __( 'WhatsApp OK', 'semrs-blocks' ),
		'company'  => __( 'Company', 'semrs-blocks' ),
		'website'  => __( 'Website', 'semrs-blocks' ),
		'service'  => __( 'Service', 'semrs-blocks' ),
		'budget'   => __( 'Budget', 'semrs-blocks' ),
		'page'     => __( 'Sent from', 'semrs-blocks' ),
	);
	$lines = array();
	foreach ( $data as $k => $v ) {
		if ( '' !== (string) $v ) {
			$lines[] = $labels[ $k ] . ': ' . $v;
		}
	}
	$body = implode( "\n", $lines ) . "\n\n" . $message;

	$lead = wp_insert_post(
		array(
			'post_type'    => 'semrs_lead',
			'post_status'  => 'private',
			'post_title'   => $name . ( $data['service'] ? ' — ' . $data['service'] : '' ),
			'post_content' => $body,
		)
	);
	if ( $lead && ! is_wp_error( $lead ) ) {
		foreach ( $data as $k => $v ) {
			update_post_meta( $lead, '_lead_' . $k, $v );
		}
	}
	$to = is_email( $cfg['recipient'] ?? '' ) ? $cfg['recipient'] : get_option( 'admin_email' );
	wp_mail( $to, $cfg['subject'] ?? __( 'New enquiry', 'semrs-blocks' ), $body . "\n\n— " . admin_url( 'edit.php?post_type=semrs_lead' ), array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
	set_transient( $rate, (int) get_transient( $rate ) + 1, HOUR_IN_SECONDS );

	/**
	 * A lead was submitted (connect CRMs, SEMRS OS lead bridge, etc.).
	 *
	 * @param array  $data    Lead fields.
	 * @param string $message Message.
	 * @param int    $lead    Lead post ID.
	 */
	do_action( 'semrs_contact_submitted', $data, $message, (int) $lead );

	return rest_ensure_response(
		array(
			'ok'       => true,
			'message'  => wp_strip_all_tags( $cfg['success'] ?? __( 'Thank you!', 'semrs-blocks' ) ),
			'redirect' => esc_url_raw( $cfg['redirect'] ?? '' ),
		)
	);
}
