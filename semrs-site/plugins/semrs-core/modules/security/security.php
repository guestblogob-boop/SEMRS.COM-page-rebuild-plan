<?php
/**
 * Security: limit login attempts, security headers, safe SVG uploads,
 * block user enumeration, hide the WordPress version, generic login errors
 * and turn off the theme / plugin file editor. Every option can be switched
 * on or off (SEMRS → Security). Best-practice defaults: log-in limits,
 * hidden usernames and security headers are on; HSTS, SVG uploads and the
 * file editor switch wait for you. Log-in limits step aside when Wordfence
 * or another log-in guard is active, so attempts are never counted twice.
 *
 * Emergency: define( 'SEMRS_SECURITY_OFF', true ); in wp-config.php turns
 * the whole module off.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'SEMRS_SECURITY_OFF' ) && SEMRS_SECURITY_OFF ) {
	return;
}

/**
 * Settings with defaults.
 *
 * @return array
 */
function semrs_security_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_security', array() ),
		array(
			'limit'      => '1',
			'tries'      => 5,
			'minutes'    => 15,
			'headers'    => '1',
			'hsts'       => '',
			'svg'        => '',
			'enum'       => '1',
			'version'    => '1',
			'login_msg'  => '1',
			'editor'     => '',
			'allowlist'  => '',
		)
	);
}

/*
 * One-time upgrade (Core 1.3.57): sites that saved the Security screen before
 * these became defaults get log-in limits, hidden usernames and security
 * headers switched on once. Later choices are kept.
 */
if ( '2' !== get_option( 'semrs_security_defaults' ) ) {
	$semrs_saved = get_option( 'semrs_security', null );
	if ( is_array( $semrs_saved ) ) {
		foreach ( array( 'limit', 'enum', 'headers' ) as $semrs_k ) {
			$semrs_saved[ $semrs_k ] = '1';
		}
		update_option( 'semrs_security', $semrs_saved );
	}
	update_option( 'semrs_security_defaults', '2' );
	unset( $semrs_saved, $semrs_k );
}

/**
 * Another plugin that already limits log-in attempts (its name), or ''.
 * SEMRS then steps aside so one wrong password is never counted twice.
 *
 * @return string
 */
function semrs_security_other_login_guard() {
	$guards = array(
		'Wordfence'                    => defined( 'WORDFENCE_VERSION' ) || class_exists( 'wordfence', false ),
		'Solid Security'               => class_exists( 'ITSEC_Core', false ),
		'Limit Login Attempts Reloaded' => class_exists( 'LimitLoginAttempts', false ) || defined( 'LLA_PLUGIN_URL' ),
		'All-In-One Security'          => class_exists( 'AIO_WP_Security', false ),
		'Loginizer'                    => defined( 'LOGINIZER_VERSION' ),
		'Jetpack Protect'              => class_exists( 'Jetpack_Protect_Module', false ) || class_exists( 'Brute_Force_Protection', false ),
	);
	foreach ( $guards as $name => $on ) {
		if ( $on ) {
			return $name;
		}
	}
	return '';
}

/**
 * Cloudflare's published address ranges (cloudflare.com/ips).
 *
 * @return string[]
 */
function semrs_security_cloudflare_ranges() {
	return array( '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32' );
}

/**
 * Is an IP inside a CIDR range (IPv4 or IPv6)?
 *
 * @param string $ip    IP.
 * @param string $range CIDR or single IP.
 * @return bool
 */
function semrs_security_ip_in( $ip, $range ) {
	if ( false === strpos( $range, '/' ) ) {
		return $ip === $range;
	}
	list( $net, $bits ) = explode( '/', $range, 2 );
	$a = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$b = @inet_pton( $net ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( false === $a || false === $b || strlen( $a ) !== strlen( $b ) ) {
		return false;
	}
	$bits  = (int) $bits;
	$bytes = intdiv( $bits, 8 );
	if ( substr( $a, 0, $bytes ) !== substr( $b, 0, $bytes ) ) {
		return false;
	}
	$rest = $bits % 8;
	if ( ! $rest ) {
		return true;
	}
	$mask = chr( ( 0xff << ( 8 - $rest ) ) & 0xff );
	return ( $a[ $bytes ] & $mask ) === ( $b[ $bytes ] & $mask );
}

/**
 * Visitor IP. REMOTE_ADDR, except behind Cloudflare, where the visitor's
 * address is taken from CF-Connecting-IP only when the request really came
 * from a Cloudflare address (other proxy headers can be forged).
 *
 * @return string
 */
function semrs_security_ip() {
	static $ip = null;
	if ( null === $ip ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '';
		if ( '' !== $cf && filter_var( $cf, FILTER_VALIDATE_IP ) ) {
			foreach ( semrs_security_cloudflare_ranges() as $r ) {
				if ( semrs_security_ip_in( $ip, $r ) ) {
					$ip = $cf;
					break;
				}
			}
		}
	}
	return (string) apply_filters( 'semrs_security_ip', $ip );
}

/**
 * Is this IP on the allow list?
 *
 * @param string $ip IP.
 * @return bool
 */
function semrs_security_allowed( $ip ) {
	foreach ( preg_split( '/[\s,]+/', (string) semrs_security_settings()['allowlist'] ) as $a ) {
		if ( '' !== $a && $a === $ip ) {
			return true;
		}
	}
	return false;
}

/*
 * Limit login attempts.
 */

/**
 * Key for an IP.
 *
 * @param string $ip IP.
 * @return string
 */
function semrs_security_key( $ip ) {
	return 'semrs_login_' . substr( md5( $ip ), 0, 20 );
}

/**
 * Block logins from a locked IP before the password is checked.
 *
 * @param WP_User|WP_Error|null $user User.
 * @return WP_User|WP_Error|null
 */
function semrs_security_check_lock( $user ) {
	$s  = semrs_security_settings();
	$ip = semrs_security_ip();
	if ( '1' !== $s['limit'] || '' === $ip || semrs_security_allowed( $ip ) || semrs_security_other_login_guard() ) {
		return $user;
	}
	$d = get_transient( semrs_security_key( $ip ) );
	if ( is_array( $d ) && $d['n'] >= (int) $s['tries'] ) {
		$wait = max( 1, (int) ceil( ( $d['t'] + (int) $s['minutes'] * 60 - time() ) / 60 ) );
		/* translators: %d: minutes. */
		return new WP_Error( 'semrs_locked', sprintf( _n( 'Too many failed log-in attempts. Try again in %d minute.', 'Too many failed log-in attempts. Try again in %d minutes.', $wait, 'semrs-core' ), $wait ) );
	}
	return $user;
}
add_filter( 'authenticate', 'semrs_security_check_lock', 1 );

/**
 * Count a failed login.
 */
function semrs_security_failed() {
	$s  = semrs_security_settings();
	$ip = semrs_security_ip();
	if ( '1' !== $s['limit'] || '' === $ip || semrs_security_allowed( $ip ) || semrs_security_other_login_guard() ) {
		return;
	}
	$k = semrs_security_key( $ip );
	$d = get_transient( $k );
	$d = is_array( $d ) ? $d : array( 'n' => 0, 't' => time() );
	$d['n']++;
	$d['t'] = time();
	set_transient( $k, $d, (int) $s['minutes'] * 60 );
	if ( $d['n'] === (int) $s['tries'] ) {
		$log = (array) get_option( 'semrs_security_lockouts', array() );
		array_unshift( $log, array( time(), $ip ) );
		update_option( 'semrs_security_lockouts', array_slice( $log, 0, 30 ), false );
	}
}
add_action( 'wp_login_failed', 'semrs_security_failed' );

/**
 * A successful login clears the counter.
 */
add_action(
	'wp_login',
	function () {
		delete_transient( semrs_security_key( semrs_security_ip() ) );
	}
);

/**
 * One generic message for wrong user or wrong password.
 *
 * @param string $msg Message.
 * @return string
 */
function semrs_security_login_errors( $msg ) {
	if ( '1' !== semrs_security_settings()['login_msg'] || false !== strpos( (string) $msg, 'Too many' ) || false !== strpos( (string) $msg, __( 'Too many failed log-in attempts.', 'semrs-core' ) ) ) {
		return $msg;
	}
	global $errors;
	if ( $errors instanceof WP_Error && array_intersect( $errors->get_error_codes(), array( 'invalid_username', 'incorrect_password', 'invalid_email' ) ) ) {
		return '<strong>' . esc_html__( 'Error:', 'semrs-core' ) . '</strong> ' . esc_html__( 'The username, email or password is not correct.', 'semrs-core' );
	}
	return $msg;
}
add_filter( 'login_errors', 'semrs_security_login_errors' );

/*
 * Security headers.
 */

/**
 * Send headers on front-end and admin pages.
 */
function semrs_security_headers() {
	$s = semrs_security_settings();
	if ( '1' !== $s['headers'] || headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), payment=(), usb=(), interest-cohort=()' );
	if ( '1' === $s['hsts'] && is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}
add_action( 'send_headers', 'semrs_security_headers' );
add_action( 'admin_init', 'semrs_security_headers' );
add_action( 'login_init', 'semrs_security_headers' );

/*
 * Safe SVG uploads (administrators only, cleaned on upload).
 */

/**
 * Allow SVG for users who can manage options.
 *
 * @param array $mimes Types.
 * @return array
 */
function semrs_security_svg_mime( $mimes ) {
	if ( '1' === semrs_security_settings()['svg'] && current_user_can( 'manage_options' ) ) {
		$mimes['svg'] = 'image/svg+xml';
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'semrs_security_svg_mime' );

/**
 * WordPress checks the real file type; confirm SVGs.
 *
 * @param array  $data     Check result.
 * @param string $file     Path.
 * @param string $filename Name.
 * @return array
 */
function semrs_security_svg_check( $data, $file, $filename ) {
	if ( '1' === semrs_security_settings()['svg'] && current_user_can( 'manage_options' ) && preg_match( '/\.svg$/i', $filename ) ) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'semrs_security_svg_check', 10, 3 );

/**
 * Clean an uploaded SVG: remove scripts, event handlers, foreign objects,
 * external references and javascript: links. Refuse files that cannot be read.
 *
 * @param array $file Upload.
 * @return array
 */
function semrs_security_svg_clean( $file ) {
	if ( 'image/svg+xml' !== ( $file['type'] ?? '' ) && ! preg_match( '/\.svg$/i', $file['name'] ?? '' ) ) {
		return $file;
	}
	if ( '1' !== semrs_security_settings()['svg'] || ! current_user_can( 'manage_options' ) ) {
		$file['error'] = __( 'SVG uploads are switched off (SEMRS → Security).', 'semrs-core' );
		return $file;
	}
	$svg = (string) file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( '' === $svg || ! class_exists( 'DOMDocument' ) || preg_match( '/<!ENTITY/i', $svg ) ) {
		$file['error'] = __( 'This SVG could not be checked safely and was not uploaded.', 'semrs-core' );
		return $file;
	}
	$prev = libxml_use_internal_errors( true );
	$dom  = new DOMDocument();
	$ok   = $dom->loadXML( $svg, LIBXML_NONET );
	libxml_use_internal_errors( $prev );
	if ( ! $ok || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
		$file['error'] = __( 'This file is not a valid SVG image.', 'semrs-core' );
		return $file;
	}
	$bad = array( 'script', 'foreignobject', 'iframe', 'embed', 'object', 'audio', 'video', 'handler', 'listener' );
	foreach ( iterator_to_array( $dom->getElementsByTagName( '*' ) ) as $el ) {
		if ( in_array( strtolower( $el->localName ), $bad, true ) ) {
			$el->parentNode->removeChild( $el );
			continue;
		}
		foreach ( iterator_to_array( $el->attributes ) as $attr ) {
			$name = strtolower( $attr->nodeName );
			$val  = strtolower( preg_replace( '/\s+/', '', $attr->nodeValue ) );
			if ( 0 === strpos( $name, 'on' ) || ( in_array( $name, array( 'href', 'xlink:href', 'src' ), true ) && ( 0 === strpos( $val, 'javascript:' ) || 0 === strpos( $val, 'data:text' ) || ( '' !== $val && '#' !== $val[0] && 0 !== strpos( $val, 'data:image' ) ) ) ) || false !== strpos( $val, 'javascript:' ) ) {
				$el->removeAttributeNode( $attr );
			}
		}
	}
	file_put_contents( $file['tmp_name'], $dom->saveXML( $dom->documentElement ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'semrs_security_svg_clean' );

/*
 * User enumeration, version, file editor.
 */

/**
 * Stop ?author=N scans and hide the users list in the REST API from visitors.
 */
function semrs_security_enum() {
	if ( '1' !== semrs_security_settings()['enum'] || is_user_logged_in() ) {
		return;
	}
	if ( isset( $_GET['author'] ) && ! is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'semrs_security_enum', 0 );

add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( '1' === semrs_security_settings()['enum'] && ! is_user_logged_in() ) {
			unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}
		return $endpoints;
	}
);

add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return '1' === semrs_security_settings()['enum'] && 'users' === $name ? false : $provider;
	},
	10,
	2
);

if ( '1' === semrs_security_settings()['version'] ) {
	remove_action( 'wp_head', 'wp_generator' );
	add_filter( 'the_generator', '__return_empty_string' );
}

add_filter(
	'map_meta_cap',
	function ( $caps, $cap ) {
		if ( '1' === semrs_security_settings()['editor'] && in_array( $cap, array( 'edit_themes', 'edit_plugins' ), true ) ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	},
	10,
	2
);

if ( is_admin() ) {
	require __DIR__ . '/admin.php';
}
