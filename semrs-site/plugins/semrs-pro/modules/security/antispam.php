<?php
/**
 * SEMRS Anti-spam: stops spam in comments, product reviews, registrations
 * and contact forms (Contact Form 7, WPForms, Gravity Forms, Elementor Pro
 * forms) on your own server. No outside service, no visitor data sent away,
 * no CAPTCHA puzzles.
 *
 * Checks, in order:
 * 1. A hidden trap field people never see (bots fill it).
 * 2. A tiny script proves a real browser filled the form, and how long it
 *    took (bots post straight to the server, or in under two seconds).
 * 3. Content: too many links, link markup in plain text, your blocked
 *    words, a web address as the name, throwaway email domains, and
 *    (optional) text mostly in another alphabet.
 * 4. Addresses that already sent spam several times today.
 * 5. Optional: StopForumSpam's free list of known spammers.
 *
 * Comments are never thrown away: spam goes to the Spam folder, like
 * Akismet, and is emptied after the days you choose. Works side by side with
 * Akismet: SEMRS stops the bots, Akismet still checks what passes.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings.
 *
 * @return array
 */
function semrs_spam_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_secpro', array() ),
		array(
			'spam_on'       => '1',
			'spam_comments' => '1',
			'spam_forms'    => '1',
			'spam_register' => '1',
			'spam_links'    => 2,
			'spam_words'    => '',
			'spam_script'   => '',
			'spam_sfs'      => '',
			'spam_days'     => 30,
		)
	);
}

/**
 * Per-site token the browser script sends back.
 *
 * @return string
 */
function semrs_spam_token() {
	return substr( hash_hmac( 'sha256', 'semrs-antispam', wp_salt( 'nonce' ) ), 0, 16 );
}

/*
 * Front end: trap field and browser proof.
 */

/**
 * Small script: adds the proof field to every POST form when the visitor
 * focuses or sends it, with the time spent on the page. About 0.5 KB, loads
 * nothing else.
 */
function semrs_spam_script() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$k = semrs_spam_token();
	echo "<script id=\"semrs-as\">(function(){var t=Date.now(),k='" . esc_js( $k ) . "';function a(f){if(!f||!f.method||f.method.toLowerCase()!=='post')return;var i=f.querySelector('input[name=semrs_as]');if(!i){i=document.createElement('input');i.type='hidden';i.name='semrs_as';f.appendChild(i)}i.value=k+'.'+(Date.now()-t)}document.addEventListener('focusin',function(e){a(e.target&&e.target.form)},true);document.addEventListener('submit',function(e){a(e.target)},true);document.addEventListener('click',function(e){var b=e.target&&e.target.closest&&e.target.closest('button,input[type=submit]');if(b)a(b.form)},true)})();</script>\n";
}

/**
 * The hidden trap field.
 *
 * @return string
 */
function semrs_spam_trap() {
	return '<span class="semrs-hp" aria-hidden="true" style="position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden"><label>' . esc_html__( 'Leave this field empty', 'semrs-pro' ) . '<input type="text" name="semrs_hp_site" value="" tabindex="-1" autocomplete="off"></label></span>';
}

/*
 * The check.
 */

/**
 * Is this submission spam? Returns the reason, or '' when it looks fine.
 *
 * @param array $f Fields: text, name, email, url, js (bool: needs browser proof).
 * @return string
 */
function semrs_spam_reason( $f ) {
	$s  = semrs_spam_settings();
	$ip = function_exists( 'semrs_fw_ip' ) ? semrs_fw_ip() : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	// phpcs:disable WordPress.Security.NonceVerification -- the forms carry their own nonces; this only reads.
	if ( '' !== trim( (string) wp_unslash( $_POST['semrs_hp_site'] ?? '' ) ) ) {
		return 'trap';
	}
	if ( ! empty( $f['js'] ) ) {
		$proof = (string) wp_unslash( $_POST['semrs_as'] ?? '' );
		if ( '' === $proof || 0 !== strpos( $proof, semrs_spam_token() . '.' ) ) {
			return 'no_browser';
		}
		if ( (int) substr( $proof, strlen( semrs_spam_token() ) + 1 ) < 2000 ) {
			return 'too_fast';
		}
	}
	// phpcs:enable
	$text  = (string) ( $f['text'] ?? '' );
	$name  = (string) ( $f['name'] ?? '' );
	$email = strtolower( (string) ( $f['email'] ?? '' ) );
	$all   = $text . ' ' . $name;
	if ( preg_match_all( '#https?://|www\.#i', $text ) > max( 0, (int) $s['spam_links'] ) ) {
		return 'links';
	}
	if ( preg_match( '#\[url=|\[link=|<a\s+href=#i', $all ) ) {
		return 'link_markup';
	}
	if ( preg_match( '#https?://|www\.|\.(?:com|net|xyz|top|ru|info)\b#i', $name ) ) {
		return 'url_name';
	}
	foreach ( preg_split( '/[\r\n,]+/', (string) $s['spam_words'] ) as $w ) {
		$w = trim( $w );
		if ( '' !== $w && false !== mb_stripos( $all . ' ' . $email, $w ) ) {
			return 'blocked_word';
		}
	}
	if ( $email && preg_match( '/@(?:mailinator|guerrillamail|sharklasers|10minutemail|tempmail|temp-mail|yopmail|trashmail|getnada|dispostable|maildrop|throwawaymail|fakeinbox|mintemail|spamgourmet|mailnesia|emailondeck|moakt|tempr)\./', $email ) ) {
		return 'throwaway_email';
	}
	if ( '1' === $s['spam_script'] && mb_strlen( $text ) > 20 ) {
		$latin = preg_match_all( '/[a-z]/i', $text );
		$other = preg_match_all( '/[\p{Cyrillic}\p{Han}\p{Hangul}\p{Thai}\p{Arabic}\p{Hebrew}]/u', $text );
		if ( $other > 10 && $other > $latin ) {
			return 'other_alphabet';
		}
	}
	if ( $ip && (int) get_transient( 'semrs_spam_ip_' . substr( md5( $ip ), 0, 16 ) ) >= 3 ) {
		return 'repeat_spammer';
	}
	if ( '1' === $s['spam_sfs'] && ( $ip || $email ) && semrs_spam_sfs( $ip, $email ) ) {
		return 'known_spammer';
	}
	return '';
}

/**
 * StopForumSpam lookup (opt-in; cached a day).
 *
 * @param string $ip    IP.
 * @param string $email Email.
 * @return bool
 */
function semrs_spam_sfs( $ip, $email ) {
	$key = 'semrs_sfs_' . md5( $ip . '|' . $email );
	$hit = get_transient( $key );
	if ( false !== $hit ) {
		return '1' === $hit;
	}
	$args = array( 'json' => '', 'confidence' => '' );
	if ( $ip ) {
		$args['ip'] = $ip;
	}
	if ( $email ) {
		$args['email'] = $email;
	}
	$res  = wp_remote_get( add_query_arg( $args, 'https://api.stopforumspam.org/api' ), array( 'timeout' => 4 ) );
	$data = is_wp_error( $res ) ? null : json_decode( (string) wp_remote_retrieve_body( $res ), true );
	$bad  = false;
	foreach ( array( 'ip', 'email' ) as $t ) {
		if ( ! empty( $data[ $t ]['appears'] ) && (float) ( $data[ $t ]['confidence'] ?? 0 ) >= 60 ) {
			$bad = true;
		}
	}
	set_transient( $key, $bad ? '1' : '0', DAY_IN_SECONDS );
	return $bad;
}

/**
 * Reason labels.
 *
 * @return array
 */
function semrs_spam_reasons() {
	return array(
		'trap'            => __( 'Filled the hidden trap field', 'semrs-pro' ),
		'no_browser'      => __( 'Sent without a real browser', 'semrs-pro' ),
		'too_fast'        => __( 'Sent in under 2 seconds', 'semrs-pro' ),
		'links'           => __( 'Too many links', 'semrs-pro' ),
		'link_markup'     => __( 'Link code in the text', 'semrs-pro' ),
		'url_name'        => __( 'Web address as the name', 'semrs-pro' ),
		'blocked_word'    => __( 'Contains a blocked word', 'semrs-pro' ),
		'throwaway_email' => __( 'Throwaway email address', 'semrs-pro' ),
		'other_alphabet'  => __( 'Mostly in another alphabet', 'semrs-pro' ),
		'repeat_spammer'  => __( 'Address sent spam earlier today', 'semrs-pro' ),
		'known_spammer'   => __( 'Listed by StopForumSpam', 'semrs-pro' ),
	);
}

/**
 * Record a stopped submission.
 *
 * @param string $where  comment|register|cf7|wpforms|gravity|elementor.
 * @param string $reason Reason key.
 * @param string $text   Text sample.
 */
function semrs_spam_record( $where, $reason, $text ) {
	$ip = function_exists( 'semrs_fw_ip' ) ? semrs_fw_ip() : '';
	if ( $ip ) {
		$k = 'semrs_spam_ip_' . substr( md5( $ip ), 0, 16 );
		set_transient( $k, (int) get_transient( $k ) + 1, DAY_IN_SECONDS );
	}
	$stats         = (array) get_option( 'semrs_spam_stats', array() );
	$day           = gmdate( 'Y-m-d' );
	$stats[ $day ] = (int) ( $stats[ $day ] ?? 0 ) + 1;
	update_option( 'semrs_spam_stats', array_slice( $stats, -30, null, true ), false );
	$log = (array) get_option( 'semrs_spam_log', array() );
	array_unshift(
		$log,
		array(
			't'  => time(),
			'w'  => $where,
			'r'  => $reason,
			'ip' => function_exists( 'wp_privacy_anonymize_ip' ) ? wp_privacy_anonymize_ip( $ip ) : '',
			'x'  => mb_substr( wp_strip_all_tags( $text ), 0, 120 ),
		)
	);
	update_option( 'semrs_spam_log', array_slice( $log, 0, 100 ), false );
}

/**
 * Should checks run for this visitor? Editors and moderators are trusted.
 *
 * @return bool
 */
function semrs_spam_applies() {
	return ! current_user_can( 'moderate_comments' );
}

/*
 * Hooks.
 */
$semrs_as = semrs_spam_settings();
if ( '1' === $semrs_as['spam_on'] ) {
	add_action( 'wp_footer', 'semrs_spam_script', 99 );
	add_action( 'login_footer', 'semrs_spam_script', 99 );

	if ( '1' === $semrs_as['spam_comments'] ) {
		$semrs_trap = function () {
			echo semrs_spam_trap(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function.
		};
		add_action( 'comment_form_after_fields', $semrs_trap );
		add_action( 'comment_form_logged_in_after', $semrs_trap );
		add_filter(
			'pre_comment_approved',
			function ( $approved, $data ) {
				if ( 'spam' === $approved || 'trash' === $approved || is_wp_error( $approved ) || ! semrs_spam_applies() ) {
					return $approved;
				}
				$type = (string) ( $data['comment_type'] ?? '' );
				$ping = in_array( $type, array( 'pingback', 'trackback' ), true );
				$why  = semrs_spam_reason(
					array(
						'text'  => (string) ( $data['comment_content'] ?? '' ),
						'name'  => $ping ? '' : (string) ( $data['comment_author'] ?? '' ),
						'email' => (string) ( $data['comment_author_email'] ?? '' ),
						'js'    => ! $ping && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ),
					)
				);
				if ( '' === $why ) {
					return $approved;
				}
				semrs_spam_record( 'comment', $why, (string) ( $data['comment_content'] ?? '' ) );
				add_action(
					'comment_post',
					function ( $id ) use ( $why ) {
						update_comment_meta( $id, '_semrs_spam', $why );
					}
				);
				return 'spam';
			},
			5,
			2
		);
		// Empty the Spam folder after N days (Akismet does the same after 15).
		add_action(
			'semrs_spam_daily',
			function () {
				global $wpdb;
				$days = max( 7, (int) semrs_spam_settings()['spam_days'] );
				$ids  = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam' AND comment_date_gmt < %s LIMIT 500", gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				foreach ( $ids as $id ) {
					wp_delete_comment( (int) $id, true );
				}
			}
		);
		if ( ! wp_next_scheduled( 'semrs_spam_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'semrs_spam_daily' );
		}
	}

	if ( '1' === $semrs_as['spam_register'] ) {
		$semrs_reg_check = function ( $errors, $login, $email ) {
			if ( ! semrs_spam_applies() ) {
				return $errors;
			}
			$why = semrs_spam_reason( array( 'name' => (string) $login, 'email' => (string) $email, 'js' => true ) );
			if ( $why ) {
				semrs_spam_record( 'register', $why, (string) $login . ' ' . (string) $email );
				$errors->add( 'semrs_spam', '<strong>' . esc_html__( 'Error:', 'semrs-pro' ) . '</strong> ' . esc_html__( 'This sign-up looked automated. Please try again in a moment.', 'semrs-pro' ) );
			}
			return $errors;
		};
		add_action( 'register_form', function () { echo semrs_spam_trap(); } ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		add_filter( 'registration_errors', $semrs_reg_check, 10, 3 );
		add_action( 'woocommerce_register_form', function () { echo semrs_spam_trap(); } ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		add_filter(
			'woocommerce_registration_errors',
			function ( $errors, $login, $email ) use ( $semrs_reg_check ) {
				return $semrs_reg_check( $errors, $login, $email );
			},
			10,
			3
		);
	}

	if ( '1' === $semrs_as['spam_forms'] ) {
		// Contact Form 7: its own Spam flag (the message is not sent).
		add_filter( 'wpcf7_form_elements', function ( $html ) { return $html . semrs_spam_trap(); } );
		add_filter(
			'wpcf7_spam',
			function ( $spam, $submission = null ) {
				if ( $spam || ! semrs_spam_applies() ) {
					return $spam;
				}
				$data = $submission && method_exists( $submission, 'get_posted_data' ) ? (array) $submission->get_posted_data() : array();
				$text = implode( ' ', array_filter( array_map( function ( $v ) { return is_scalar( $v ) ? (string) $v : ''; }, $data ) ) );
				$why  = semrs_spam_reason( array( 'text' => $text, 'email' => (string) ( $data['your-email'] ?? '' ), 'name' => (string) ( $data['your-name'] ?? '' ), 'js' => true ) );
				if ( ! $why ) {
					return $spam;
				}
				semrs_spam_record( 'cf7', $why, $text );
				if ( $submission && method_exists( $submission, 'add_spam_log' ) ) {
					$submission->add_spam_log( array( 'agent' => 'semrs', 'reason' => semrs_spam_reasons()[ $why ] ?? $why ) );
				}
				return true;
			},
			9,
			2
		);

		// WPForms.
		add_action( 'wpforms_display_submit_before', function () { echo semrs_spam_trap(); } ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		add_action(
			'wpforms_process_before',
			function ( $entry, $form_data ) {
				if ( ! semrs_spam_applies() || ! function_exists( 'wpforms' ) ) {
					return;
				}
				$text = implode( ' ', array_filter( array_map( function ( $v ) { return is_scalar( $v ) ? (string) $v : ''; }, (array) ( $entry['fields'] ?? array() ) ) ) );
				$why  = semrs_spam_reason( array( 'text' => $text, 'js' => true ) );
				if ( $why ) {
					semrs_spam_record( 'wpforms', $why, $text );
					wpforms()->process->errors[ $form_data['id'] ]['header'] = esc_html__( 'Your message looked automated and was not sent. Please try again.', 'semrs-pro' );
				}
			},
			5,
			2
		);

		// Gravity Forms: its own Spam folder.
		add_filter(
			'gform_entry_is_spam',
			function ( $is_spam, $form, $entry ) {
				if ( $is_spam || ! semrs_spam_applies() ) {
					return $is_spam;
				}
				$text = implode( ' ', array_filter( array_map( function ( $v ) { return is_scalar( $v ) ? (string) $v : ''; }, (array) $entry ) ) );
				$why  = semrs_spam_reason( array( 'text' => $text, 'js' => true ) );
				if ( $why ) {
					semrs_spam_record( 'gravity', $why, $text );
					return true;
				}
				return $is_spam;
			},
			10,
			3
		);

		// Elementor Pro forms.
		add_action(
			'elementor_pro/forms/validation',
			function ( $record, $handler ) {
				if ( ! semrs_spam_applies() || ! is_object( $record ) || ! method_exists( $record, 'get' ) ) {
					return;
				}
				$fields = (array) $record->get( 'fields' );
				$text   = implode( ' ', array_map( function ( $f ) { return is_array( $f ) && isset( $f['value'] ) && is_scalar( $f['value'] ) ? (string) $f['value'] : ''; }, $fields ) );
				$why    = semrs_spam_reason( array( 'text' => $text, 'js' => true ) );
				if ( $why && method_exists( $handler, 'add_error' ) ) {
					semrs_spam_record( 'elementor', $why, $text );
					$keys = array_keys( $fields );
					$handler->add_error( (string) ( $keys[0] ?? 'form' ), esc_html__( 'Your message looked automated and was not sent. Please try again.', 'semrs-pro' ) );
				}
			},
			10,
			2
		);
	}
}
unset( $semrs_as );
