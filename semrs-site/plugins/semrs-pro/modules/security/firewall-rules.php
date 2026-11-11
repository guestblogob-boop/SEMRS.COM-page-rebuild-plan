<?php
/**
 * SEMRS Firewall: request rules.
 *
 * Self-contained so it can run from the must-use loader
 * (wp-content/mu-plugins/semrs-firewall.php) before any normal plugin loads,
 * or from the SEMRS Pro module when the loader cannot be written.
 *
 * What it does, in order:
 * 1. Allow list → always let through. Block list and temporary blocks → 403.
 * 2. Requests from signed-in editors and administrators are checked after
 *    WordPress confirms who they are (a cookie alone is never trusted).
 * 3. Rules on the address, query string, user agent, uploaded file names
 *    and (for visitors and subscribers) the posted data: SQL injection,
 *    cross-site scripting, path traversal and file inclusion, code
 *    injection, Log4Shell-style lookups, sensitive files, PHP in uploads,
 *    XML-RPC brute force and known attack tools.
 * 4. An address that breaks rules again and again, or floods the site with
 *    404s, is blocked for a while.
 *
 * Log-only mode records matches without blocking.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'semrs_fw_boot' ) ) {
	return;
}

/**
 * Firewall settings (shared with the Security Pro screen).
 *
 * @return array
 */
function semrs_fw_settings() {
	return wp_parse_args(
		(array) get_option( 'semrs_secpro', array() ),
		array(
			'fw_on'        => '1',
			'fw_mode'      => 'block',
			'fw_bots'      => '1',
			'fw_flood'     => '1',
			'fw_repeat'    => '1',
			'fw_allow'     => '',
			'fw_block'     => '',
			'fw_wordfence' => '',
		)
	);
}

/**
 * Is Wordfence's own firewall on this site? Then SEMRS pauses its firewall
 * (unless the owner chose to run both), so requests are not inspected twice.
 *
 * @return bool
 */
function semrs_fw_wordfence() {
	return defined( 'WFWAF_VERSION' ) || defined( 'WORDFENCE_VERSION' ) || in_array( 'wordfence/wordfence.php', (array) get_option( 'active_plugins', array() ), true );
}

/**
 * Should the firewall run on this request?
 *
 * @return bool
 */
function semrs_fw_enabled() {
	$s = semrs_fw_settings();
	if ( '1' !== $s['fw_on'] || '1' !== get_option( 'semrs_fw_live' ) ) {
		return false;
	}
	if ( defined( 'SEMRS_FIREWALL_OFF' ) && SEMRS_FIREWALL_OFF ) {
		return false;
	}
	return ! ( semrs_fw_wordfence() && '1' !== $s['fw_wordfence'] );
}

/**
 * Visitor IP: REMOTE_ADDR, or CF-Connecting-IP when the request came from a
 * Cloudflare address.
 *
 * @return string
 */
function semrs_fw_ip() {
	static $ip = null;
	if ( null !== $ip ) {
		return $ip;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	$cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? (string) $_SERVER['HTTP_CF_CONNECTING_IP'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( $ip && $cf && filter_var( $cf, FILTER_VALIDATE_IP ) ) {
		$ranges = array( '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32' );
		foreach ( $ranges as $r ) {
			if ( semrs_fw_ip_in( $ip, $r ) ) {
				$ip = $cf;
				break;
			}
		}
	}
	return $ip;
}

/**
 * IP inside a CIDR range or equal to a single IP?
 *
 * @param string $ip    IP.
 * @param string $range Range.
 * @return bool
 */
function semrs_fw_ip_in( $ip, $range ) {
	$range = trim( $range );
	if ( '' === $range || '' === $ip ) {
		return false;
	}
	if ( false === strpos( $range, '/' ) ) {
		return $ip === $range;
	}
	list( $net, $bits ) = explode( '/', $range, 2 );
	$a = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$b = @inet_pton( $net ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( false === $a || false === $b || strlen( $a ) !== strlen( $b ) ) {
		return false;
	}
	$bits  = max( 0, min( strlen( $a ) * 8, (int) $bits ) );
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
 * Did an administrator log in from this address in the last 30 days? Such
 * addresses are never blocked automatically (attack rules still apply), so
 * owners cannot lock themselves out.
 *
 * @param string $ip IP.
 * @return bool
 */
function semrs_fw_admin_ip( $ip ) {
	$list = (array) get_option( 'semrs_fw_admin_ips', array() );
	return isset( $list[ $ip ] ) && (int) $list[ $ip ] > time() - 30 * DAY_IN_SECONDS;
}

/**
 * Remember where administrators log in from.
 *
 * @param string  $login Login.
 * @param WP_User $user  User.
 */
function semrs_fw_remember_admin( $login, $user ) {
	if ( ! $user instanceof WP_User || ! $user->has_cap( 'manage_options' ) ) {
		return;
	}
	$ip = semrs_fw_ip();
	if ( '' === $ip ) {
		return;
	}
	$list        = (array) get_option( 'semrs_fw_admin_ips', array() );
	$list[ $ip ] = time();
	arsort( $list );
	update_option( 'semrs_fw_admin_ips', array_slice( $list, 0, 10, true ), true );
	// Lift any block on this address.
	$temp = (array) get_option( 'semrs_fw_blocks', array() );
	if ( isset( $temp[ $ip ] ) ) {
		unset( $temp[ $ip ] );
		update_option( 'semrs_fw_blocks', $temp, true );
	}
}
add_action( 'wp_login', 'semrs_fw_remember_admin', 10, 2 );

/**
 * Is the IP in a list (one IP or CIDR per line)?
 *
 * @param string $ip   IP.
 * @param string $list List.
 * @return bool
 */
function semrs_fw_ip_listed( $ip, $list ) {
	foreach ( preg_split( '/[\s,]+/', (string) $list ) as $r ) {
		if ( semrs_fw_ip_in( $ip, $r ) ) {
			return true;
		}
	}
	return false;
}

/**
 * The rules: id => [label, where, pattern]. "where": uri, query, body, ua.
 * Inputs are URL-decoded twice, lower-cased, and SQL comments removed first.
 *
 * @return array
 */
function semrs_fw_rules() {
	return array(
		'sqli_union'   => array( 'SQL injection (UNION SELECT)', 'query,body', '/\bunion\b[\s()]*(?:all\b|distinct\b)?[\s()]*\bselect\b/' ),
		'sqli_time'    => array( 'SQL injection (time delay)', 'query', '/\b(?:sleep|benchmark|pg_sleep)\s*\(\s*\d|\bwaitfor\s+delay\s+[\'"]\d/' ),
		'sqli_time_b'  => array( 'SQL injection (time delay)', 'body', '/(?:\b(?:and|or|xor|select|union|if|case\s+when)\b|[\'")])\s*\(?\s*(?:sleep|benchmark|pg_sleep)\s*\(\s*\d|\bwaitfor\s+delay\s+[\'"]\d/' ),
		'sqli_schema'  => array( 'SQL injection (database probe)', 'query,body', '/\binformation_schema\b|\bload_file\s*\(|\binto\s+(?:out|dump)file\b|\b(?:updatexml|extractvalue)\s*\(/' ),
		'sqli_tauto'   => array( 'SQL injection (always-true test)', 'query', '/[\'"]\s*(?:or|and|\|\||&&)\s*[\'"]?(\w+)[\'"]?\s*=\s*[\'"]?\1\b/' ),
		'sqli_stack'   => array( 'SQL injection (stacked query)', 'query,body', '/;\s*(?:drop|truncate|alter)\s+table\b/' ),
		'xss'          => array( 'Cross-site scripting', 'query,body', '/<script[\s>\/]|<(?:img|svg|body|iframe|video|audio|input|details|a|div|object|embed)\b[^>]*\son[a-z]+\s*=|<iframe[\s>]|(?:href|src|action|formaction)\s*=\s*[\'"]?\s*javascript:/' ),
		'xss_q'        => array( 'Cross-site scripting', 'query', '/=\s*[\'"]?\s*javascript\s*:|\bdocument\.cookie\b|\balert\s*\(\s*(?:1|document|origin)/' ),
		'traversal'    => array( 'Path traversal / file inclusion', 'uri,query,body', '/(?:\.\.[\/\\\\]){2,}|\/etc\/(?:passwd|shadow)\b|\/proc\/self\/|\b(?:php|phar|zip|expect|glob):\/\//' ),
		'lfi_q'        => array( 'File inclusion', 'query', '/\bwp-config\.php\b|\bfile:\/\/|\bdata:text\/html/' ),
		'code'         => array( 'Code injection', 'query,body', '/\b(?:eval|assert|system|shell_exec|passthru|proc_open|popen|pcntl_exec|create_function)\s*\(\s*(?:\$_|@?\$\w+\s*\(|[\'"]?\s*(?:base64_decode|gzinflate|str_rot13))|\b(?:eval|assert)\s*\(\s*(?:base64_decode|gzinflate|gzuncompress|str_rot13)\s*\(|<\?php\s+(?:eval|system|@?\$_)/' ),
		'code_q'       => array( 'Code injection', 'query', '/\b(?:eval|assert|system|shell_exec|passthru|proc_open|popen)\s*\(|`[^`]*\b(?:wget|curl|bash|nc|chmod)\s|<\?php/' ),
		'jndi'         => array( 'Log4Shell / template injection', 'uri,query,body,ua', '/\$\{(?:jndi|env|sys|lower|upper|::-)[:}]|\{\{\s*7\s*\*\s*7\s*\}\}/' ),
		'files'        => array( 'Sensitive file request', 'uri', '/(?:^|\/)(?:wp-config\.php[^\/]+|\.env(?:\.[\w-]+)?$|\.git\/|\.svn\/|\.hg\/|\.ht(?:access|passwd)$|\.ds_store$|phpinfo\.php$|debug\.log$|[\w.-]+\.(?:sql|sql\.gz|bak|old|swp)$|\.aws\/|\.ssh\/|id_rsa)/' ),
		'upload_php'   => array( 'PHP file inside uploads', 'uri', '/\/wp-content\/uploads\/.*\.(?:php\d?|phtml|phar|pht|shtml)(?:$|\/)/' ),
		'scanner'      => array( 'Attack tool', 'ua', '/(?:sqlmap|nikto|wpscan|masscan|nuclei|zgrab|acunetix|netsparker|dirbuster|gobuster|ffuf|feroxbuster|nmap scripting|havij|w3af|joomscan|commix|xsstrike|jaeles|wfuzz|morfeus|zmeu|fimap|openvas|nessus|arachni|skipfish|whatweb|burpcollaborator|interactsh)/' ),
	);
}

/**
 * Normalize a value for matching.
 *
 * @param string $v Value.
 * @return string
 */
function semrs_fw_norm( $v ) {
	$v = (string) $v;
	for ( $i = 0; $i < 2; $i++ ) {
		$d = rawurldecode( $v );
		if ( $d === $v ) {
			break;
		}
		$v = $d;
	}
	$v = html_entity_decode( $v, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$v = strtolower( str_replace( "\0", '', $v ) );
	$v = preg_replace( '#/\*.*?\*/#s', ' ', $v );
	return (string) preg_replace( '/\s+/', ' ', $v );
}

/**
 * Flatten request data to "key=value" strings (keys are checked too).
 *
 * @param mixed $data Data.
 * @param int   $depth Depth.
 * @return string[]
 */
function semrs_fw_flatten( $data, $depth = 0 ) {
	$out = array();
	if ( $depth > 6 ) {
		return $out;
	}
	foreach ( (array) $data as $k => $v ) {
		if ( is_array( $v ) ) {
			$out   = array_merge( $out, array( (string) $k ), semrs_fw_flatten( $v, $depth + 1 ) );
		} elseif ( is_scalar( $v ) ) {
			$out[] = $k . '=' . substr( (string) $v, 0, 65536 );
		}
		if ( count( $out ) > 2000 ) {
			break;
		}
	}
	return $out;
}

/**
 * Check one target against the rules.
 *
 * @param string   $where  uri|query|body|ua.
 * @param string[] $values Values.
 * @return array|null [rule id, label] of the first match.
 */
function semrs_fw_match( $where, $values ) {
	$s = semrs_fw_settings();
	foreach ( semrs_fw_rules() as $id => $r ) {
		if ( false === strpos( $r[1], $where ) || ( 'scanner' === $id && '1' !== $s['fw_bots'] ) ) {
			continue;
		}
		foreach ( $values as $v ) {
			if ( '' !== $v && preg_match( $r[2], semrs_fw_norm( $v ) ) ) {
				return array( $id, $r[0], $v );
			}
		}
	}
	return null;
}

/**
 * Run the checks. $stage: early (must-use loader), late (module) or user
 * (after WordPress confirmed the signed-in user).
 *
 * @param string $stage Stage.
 */
function semrs_fw_boot( $stage = 'late' ) {
	static $done = false;
	if ( $done || ! semrs_fw_enabled() ) {
		return;
	}
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || 'cli' === PHP_SAPI ) {
		$done = true;
		return;
	}
	$s  = semrs_fw_settings();
	$ip = semrs_fw_ip();
	if ( semrs_fw_ip_listed( $ip, $s['fw_allow'] ) ) {
		$done = true;
		return;
	}
	if ( semrs_fw_ip_listed( $ip, $s['fw_block'] ) ) {
		$done = true;
		semrs_fw_deny( 'blocklist', 'Blocked address', '' );
	}
	$temp = (array) get_option( 'semrs_fw_blocks', array() );
	if ( isset( $temp[ $ip ] ) && (int) $temp[ $ip ][0] > time() && ! semrs_fw_admin_ip( $ip ) ) {
		$done = true;
		semrs_fw_deny( 'temp', 'Temporarily blocked: ' . (string) $temp[ $ip ][1], '' );
	}
	// A login cookie is only a claim until WordPress checks it: wait for that.
	if ( 'early' === $stage && preg_grep( '/^wordpress_logged_in_/', array_keys( $_COOKIE ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		add_action( 'plugins_loaded', 'semrs_fw_boot_user', -9999 );
		return;
	}
	$done = true;
	semrs_fw_inspect( 'user' === $stage );
}

/**
 * Checks for requests with a login cookie, once the user is known.
 */
function semrs_fw_boot_user() {
	semrs_fw_boot( 'user' );
}

/**
 * Inspect the request.
 *
 * @param bool $user_known WordPress already checked the login cookie.
 */
function semrs_fw_inspect( $user_known ) {
	// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	$trusted = false;
	if ( ( $user_known || did_action( 'plugins_loaded' ) ) && function_exists( 'current_user_can' ) ) {
		$trusted = current_user_can( 'edit_posts' );
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = (string) strtok( $uri, '?' );
	$ua   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

	$hit = semrs_fw_match( 'uri', array( $path ) );
	if ( ! $hit && ! $trusted ) {
		$hit = semrs_fw_match( 'ua', array( $ua ) );
	}
	if ( ! $hit && ! $trusted ) {
		$hit = semrs_fw_match( 'query', semrs_fw_flatten( $_GET ) );
	}
	if ( ! $hit && ! $trusted && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		$body = semrs_fw_flatten( $_POST );
		$ct   = strtolower( (string) ( $_SERVER['CONTENT_TYPE'] ?? '' ) );
		if ( false !== strpos( $ct, 'json' ) || false !== strpos( $ct, 'xml' ) ) {
			$raw = (string) file_get_contents( 'php://input', false, null, 0, 262144 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( false !== strpos( $path, 'xmlrpc.php' ) && false !== stripos( $raw, 'system.multicall' ) ) {
				$hit = array( 'xmlrpc_multi', 'XML-RPC brute force (multicall)', 'system.multicall' );
			}
			$json = json_decode( $raw, true );
			$body = array_merge( $body, is_array( $json ) ? semrs_fw_flatten( $json ) : array( $raw ) );
		}
		if ( ! $hit ) {
			$hit = semrs_fw_match( 'body', $body );
		}
	}
	if ( ! $hit && ! $trusted && ! empty( $_FILES ) ) {
		foreach ( semrs_fw_flatten( $_FILES ) as $f ) {
			if ( preg_match( '/^name=.*\.(?:php\d?|phtml|phar|pht|shtml|htaccess)(?:\.|$)/i', $f ) ) {
				$hit = array( 'upload', 'PHP file upload', $f );
				break;
			}
		}
	}
	// phpcs:enable
	if ( $hit ) {
		semrs_fw_deny( $hit[0], $hit[1], $hit[2] );
	}
}

/**
 * Record a block and, in block mode, stop the request with 403.
 *
 * @param string $rule  Rule id.
 * @param string $label Label.
 * @param string $value Matched value.
 */
function semrs_fw_deny( $rule, $label, $value ) {
	$s    = semrs_fw_settings();
	$ip   = semrs_fw_ip();
	$log  = 'log' === $s['fw_mode'] && ! in_array( $rule, array( 'blocklist', 'temp' ), true );
	semrs_fw_record( $rule, $label, $value, $log );
	if ( ! $log && '1' === $s['fw_repeat'] && ! in_array( $rule, array( 'blocklist', 'temp' ), true ) ) {
		semrs_fw_strike( $ip, 'repeat', 5, 10 * MINUTE_IN_SECONDS, HOUR_IN_SECONDS, 'Repeated attacks' );
	}
	if ( $log ) {
		return;
	}
	if ( ! headers_sent() ) {
		status_header( 403 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-SEMRS-Firewall: blocked' );
	}
	echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>403 Forbidden</title><body style="font:16px/1.5 system-ui,sans-serif;max-width:560px;margin:15vh auto;padding:0 20px;color:#1e293b"><h1 style="font-size:24px">Request blocked</h1><p>This request looked unsafe, so the site firewall stopped it. If you believe this is a mistake, contact the site owner and mention the time of your visit.</p></body>';
	exit;
}

/**
 * Save a log line (at most 60 writes a minute, newest 200 kept) and a daily count.
 *
 * @param string $rule  Rule.
 * @param string $label Label.
 * @param string $value Value.
 * @param bool   $logonly Logged in log-only mode.
 */
function semrs_fw_record( $rule, $label, $value, $logonly ) {
	$k = 'semrs_fw_w_' . gmdate( 'YmdHi' );
	$n = (int) get_transient( $k );
	set_transient( $k, $n + 1, 2 * MINUTE_IN_SECONDS );
	$stats = (array) get_option( 'semrs_fw_stats', array() );
	$day   = gmdate( 'Y-m-d' );
	$stats[ $day ] = (int) ( $stats[ $day ] ?? 0 ) + 1;
	if ( $n > 60 ) {
		return;
	}
	$stats = array_slice( $stats, -30, null, true );
	update_option( 'semrs_fw_stats', $stats, false );
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$log = (array) get_option( 'semrs_fw_log', array() );
	array_unshift(
		$log,
		array(
			't' => time(),
			'ip' => semrs_fw_ip(),
			'r' => $rule,
			'l' => $label . ( $logonly ? ' (log only)' : '' ),
			'u' => substr( preg_replace( '/[^\x20-\x7e]/', '?', $uri ), 0, 200 ),
			'v' => substr( preg_replace( '/[^\x20-\x7e]/', '?', (string) $value ), 0, 160 ),
		)
	);
	update_option( 'semrs_fw_log', array_slice( $log, 0, 200 ), false );
}

/**
 * Count a strike for an IP; block it for a while after too many.
 *
 * @param string $ip     IP.
 * @param string $kind   repeat|flood.
 * @param int    $max    Strikes allowed.
 * @param int    $window Window (seconds).
 * @param int    $block  Block length (seconds).
 * @param string $why    Reason.
 */
function semrs_fw_strike( $ip, $kind, $max, $window, $block, $why ) {
	if ( '' === $ip || semrs_fw_admin_ip( $ip ) ) {
		return;
	}
	$k = 'semrs_fw_' . $kind . '_' . substr( md5( $ip ), 0, 16 );
	$n = (int) get_transient( $k ) + 1;
	set_transient( $k, $n, $window );
	if ( $n < $max ) {
		return;
	}
	delete_transient( $k );
	$temp = (array) get_option( 'semrs_fw_blocks', array() );
	foreach ( $temp as $a => $b ) {
		if ( (int) $b[0] < time() ) {
			unset( $temp[ $a ] );
		}
	}
	$temp[ $ip ] = array( time() + $block, $why );
	if ( count( $temp ) > 1000 ) {
		$temp = array_slice( $temp, -1000, null, true );
	}
	update_option( 'semrs_fw_blocks', $temp, true ); // Autoloaded: read on every request.
}
