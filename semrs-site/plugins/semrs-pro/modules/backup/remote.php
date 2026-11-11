<?php
/**
 * SEMRS Backups: storage destinations.
 *
 * Amazon S3 and S3-compatible storage (Wasabi, Backblaze B2, DigitalOcean
 * Spaces, Cloudflare R2, MinIO…), Dropbox, Google Drive, FTP / FTPS, SFTP,
 * WebDAV (Nextcloud, ownCloud, pCloud…) and email.
 *
 * Every upload and download works in chunks that resume where they stopped,
 * so large backups never hit a server time limit.
 *
 * semrs_bk_remote_put() returns true when the file is complete, false when
 * more chunks are left, or a WP_Error.
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Destinations: key => label.
 *
 * @return array
 */
function semrs_bk_dests() {
	return array(
		's3'      => __( 'Amazon S3 / S3-compatible', 'semrs-pro' ),
		'dropbox' => __( 'Dropbox', 'semrs-pro' ),
		'gdrive'  => __( 'Google Drive', 'semrs-pro' ),
		'ftp'     => __( 'FTP / FTPS', 'semrs-pro' ),
		'sftp'    => __( 'SFTP (SSH)', 'semrs-pro' ),
		'webdav'  => __( 'WebDAV (Nextcloud, ownCloud…)', 'semrs-pro' ),
		'email'   => __( 'Email', 'semrs-pro' ),
	);
}

/**
 * Label.
 *
 * @param string $d Key.
 * @return string
 */
function semrs_bk_dest_label( $d ) {
	return semrs_bk_dests()[ $d ] ?? $d;
}

/**
 * Saved settings for one destination.
 *
 * @param string $d Key.
 * @return array
 */
function semrs_bk_dest_opts( $d ) {
	$all = (array) get_option( 'semrs_bk_dest', array() );
	return (array) ( $all[ $d ] ?? array() );
}

/**
 * Save settings for one destination (merged).
 *
 * @param string $d    Key.
 * @param array  $vals Values.
 */
function semrs_bk_dest_save( $d, $vals ) {
	$all       = (array) get_option( 'semrs_bk_dest', array() );
	$all[ $d ] = array_merge( (array) ( $all[ $d ] ?? array() ), $vals );
	update_option( 'semrs_bk_dest', $all, false );
}

/**
 * Remote folder for this site's backups.
 *
 * @param array $o Options.
 * @return string No leading or trailing slash.
 */
function semrs_bk_remote_folder( $o ) {
	$f = trim( (string) ( $o['path'] ?? '' ), '/ ' );
	return '' !== $f ? $f : 'semrs-backups/' . preg_replace( '/[^a-z0-9.-]+/', '-', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
}

/**
 * Read part of a local file.
 *
 * @param string $path   File.
 * @param int    $offset Offset.
 * @param int    $len    Length.
 * @return string
 */
function semrs_bk_read( $path, $offset, $len ) {
	$fh = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fseek( $fh, $offset );
	$data = (string) fread( $fh, $len ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $data;
}

/**
 * Upload one file (resumable).
 *
 * @param string $d     Destination.
 * @param string $path  Local file.
 * @param string $name  Remote name.
 * @param array  $st    State (by reference).
 * @param float  $end   Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_remote_put( $d, $path, $name, &$st, $end ) {
	$o  = semrs_bk_dest_opts( $d );
	$fn = 'semrs_bk_' . $d . '_put';
	if ( ! function_exists( $fn ) ) {
		return new WP_Error( 'semrs', __( 'Unknown storage.', 'semrs-pro' ) );
	}
	return $fn( $o, $path, $name, $st, $end );
}

/**
 * Download one file (resumable) into $path.
 *
 * @param string $d    Destination.
 * @param string $name Remote name.
 * @param string $rid  Remote id (Google Drive).
 * @param string $path Local file.
 * @param array  $st   State (by reference).
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_remote_get( $d, $name, $rid, $path, &$st, $end ) {
	$o  = semrs_bk_dest_opts( $d );
	$fn = 'semrs_bk_' . $d . '_get';
	if ( ! function_exists( $fn ) ) {
		return new WP_Error( 'semrs', __( 'Files cannot be fetched back from this storage automatically. Download them yourself and upload them on the Backups screen.', 'semrs-pro' ) );
	}
	return $fn( $o, $name, $rid, $path, $st, $end );
}

/**
 * Delete one remote file (old backups).
 *
 * @param string $d    Destination.
 * @param string $name Remote name.
 * @param string $rid  Remote id.
 */
function semrs_bk_remote_delete( $d, $name, $rid = '' ) {
	$fn = 'semrs_bk_' . $d . '_delete';
	if ( function_exists( $fn ) ) {
		$fn( semrs_bk_dest_opts( $d ), $name, $rid );
	}
}

/**
 * Test a destination with a tiny file.
 *
 * @param string $d Destination.
 * @return true|WP_Error
 */
function semrs_bk_remote_test( $d ) {
	$tmp = wp_tempnam( 'semrs-test.txt' );
	file_put_contents( $tmp, 'SEMRS backup connection test ' . gmdate( 'c' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$st  = array();
	$res = false;
	$end = microtime( true ) + 20;
	for ( $i = 0; $i < 5 && false === $res; $i++ ) {
		$res = semrs_bk_remote_put( $d, $tmp, 'semrs-connection-test.txt', $st, $end );
	}
	@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
	if ( true === $res && 'email' !== $d ) {
		semrs_bk_remote_delete( $d, 'semrs-connection-test.txt', $st['remote_id'] ?? '' );
	}
	return true === $res ? true : ( is_wp_error( $res ) ? $res : new WP_Error( 'semrs', __( 'The test did not finish.', 'semrs-pro' ) ) );
}

/**
 * Error from an HTTP answer.
 *
 * @param array|WP_Error $res  Response.
 * @param string         $what What failed.
 * @return WP_Error
 */
function semrs_bk_http_error( $res, $what ) {
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'semrs', $what . ': ' . $res->get_error_message() );
	}
	$body = wp_strip_all_tags( (string) wp_remote_retrieve_body( $res ) );
	return new WP_Error( 'semrs', $what . ' (' . wp_remote_retrieve_response_code( $res ) . '): ' . mb_substr( $body, 0, 200 ) );
}

/*
 * Amazon S3 and S3-compatible (Signature Version 4).
 */

/**
 * Signed S3 request.
 *
 * @param array  $o       Options: key, secret, bucket, region, endpoint, path.
 * @param string $method  Method.
 * @param string $object  Object key ('' for the bucket).
 * @param array  $query   Query.
 * @param string $body    Body.
 * @param array  $headers Extra headers.
 * @return array|WP_Error
 */
function semrs_bk_s3_call( $o, $method, $object, $query = array(), $body = '', $headers = array() ) {
	$region   = '' !== (string) ( $o['region'] ?? '' ) ? $o['region'] : 'us-east-1';
	$endpoint = preg_replace( '#^https?://#', '', trim( (string) ( $o['endpoint'] ?? '' ), '/ ' ) );
	$bucket   = (string) ( $o['bucket'] ?? '' );
	if ( '' === $endpoint ) {
		$host = $bucket . '.s3.' . $region . '.amazonaws.com';
		$uri  = '/' . $object;
	} else {
		$host = $endpoint;
		$uri  = '/' . $bucket . '/' . $object;
	}
	$uri  = implode( '/', array_map( 'rawurlencode', explode( '/', $uri ) ) );
	ksort( $query );
	$qs = array();
	foreach ( $query as $k => $v ) {
		$qs[] = rawurlencode( $k ) . '=' . rawurlencode( (string) $v );
	}
	$qs      = implode( '&', $qs );
	$amzdate = gmdate( 'Ymd\THis\Z' );
	$date    = substr( $amzdate, 0, 8 );
	$hash    = hash( 'sha256', $body );
	$hdr     = array_change_key_case( $headers, CASE_LOWER ) + array(
		'host'                 => $host,
		'x-amz-content-sha256' => $hash,
		'x-amz-date'           => $amzdate,
	);
	ksort( $hdr );
	$canon_h = '';
	foreach ( $hdr as $k => $v ) {
		$canon_h .= $k . ':' . trim( (string) $v ) . "\n";
	}
	$signed = implode( ';', array_keys( $hdr ) );
	$creq   = implode( "\n", array( $method, $uri, $qs, $canon_h, $signed, $hash ) );
	$scope  = $date . '/' . $region . '/s3/aws4_request';
	$sts    = "AWS4-HMAC-SHA256\n" . $amzdate . "\n" . $scope . "\n" . hash( 'sha256', $creq );
	$k      = hash_hmac( 'sha256', $date, 'AWS4' . (string) ( $o['secret'] ?? '' ), true );
	$k      = hash_hmac( 'sha256', $region, $k, true );
	$k      = hash_hmac( 'sha256', 's3', $k, true );
	$k      = hash_hmac( 'sha256', 'aws4_request', $k, true );
	$sig    = hash_hmac( 'sha256', $sts, $k );
	$hdr['Authorization'] = 'AWS4-HMAC-SHA256 Credential=' . (string) ( $o['key'] ?? '' ) . '/' . $scope . ', SignedHeaders=' . $signed . ', Signature=' . $sig;
	unset( $hdr['host'] );
	return wp_remote_request(
		'https://' . $host . $uri . ( $qs ? '?' . $qs : '' ),
		array(
			'method'  => $method,
			'headers' => $hdr,
			'body'    => $body,
			'timeout' => 90,
		)
	);
}

/**
 * S3 upload: single PUT for small files, multipart (16 MB parts) for the rest.
 *
 * @param array  $o    Options.
 * @param string $path File.
 * @param string $name Name.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_s3_put( $o, $path, $name, &$st, $end ) {
	$obj  = semrs_bk_remote_folder( $o ) . '/' . $name;
	$size = (int) filesize( $path );
	$psz  = 16 * MB_IN_BYTES;
	if ( $size <= $psz ) {
		$res = semrs_bk_s3_call( $o, 'PUT', $obj, array(), (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return 200 === (int) wp_remote_retrieve_response_code( $res ) ? true : semrs_bk_http_error( $res, 'S3' );
	}
	if ( empty( $st['uid'] ) ) {
		$res = semrs_bk_s3_call( $o, 'POST', $obj, array( 'uploads' => '' ) );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) || ! preg_match( '#<UploadId>([^<]+)</UploadId>#', (string) wp_remote_retrieve_body( $res ), $m ) ) {
			return semrs_bk_http_error( $res, 'S3' );
		}
		$st = array( 'uid' => $m[1], 'n' => 1, 'etags' => array() );
	}
	while ( microtime( true ) < $end ) {
		$off = ( $st['n'] - 1 ) * $psz;
		if ( $off >= $size ) {
			$xml = '<CompleteMultipartUpload>';
			foreach ( $st['etags'] as $n => $e ) {
				$xml .= '<Part><PartNumber>' . (int) $n . '</PartNumber><ETag>' . esc_html( $e ) . '</ETag></Part>';
			}
			$xml .= '</CompleteMultipartUpload>';
			$res  = semrs_bk_s3_call( $o, 'POST', $obj, array( 'uploadId' => $st['uid'] ), $xml, array( 'content-type' => 'application/xml' ) );
			return 200 === (int) wp_remote_retrieve_response_code( $res ) && false === strpos( (string) wp_remote_retrieve_body( $res ), '<Error>' ) ? true : semrs_bk_http_error( $res, 'S3' );
		}
		$res = semrs_bk_s3_call( $o, 'PUT', $obj, array( 'partNumber' => $st['n'], 'uploadId' => $st['uid'] ), semrs_bk_read( $path, $off, $psz ) );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return semrs_bk_http_error( $res, 'S3' );
		}
		$st['etags'][ $st['n'] ] = (string) wp_remote_retrieve_header( $res, 'etag' );
		++$st['n'];
	}
	return false;
}

/**
 * S3 download in 16 MB ranges.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  Unused.
 * @param string $path Local file.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_s3_get( $o, $name, $rid, $path, &$st, $end ) {
	$obj = semrs_bk_remote_folder( $o ) . '/' . $name;
	while ( microtime( true ) < $end ) {
		$off = (int) ( $st['off'] ?? 0 );
		$res = semrs_bk_s3_call( $o, 'GET', $obj, array(), '', array( 'range' => 'bytes=' . $off . '-' . ( $off + 16 * MB_IN_BYTES - 1 ) ) );
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 416 === $code ) {
			return true;
		}
		if ( ! in_array( $code, array( 200, 206 ), true ) ) {
			return semrs_bk_http_error( $res, 'S3' );
		}
		$data = (string) wp_remote_retrieve_body( $res );
		file_put_contents( $path, $data, $off ? FILE_APPEND : 0 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$st['off'] = $off + strlen( $data );
		if ( 200 === $code || strlen( $data ) < 16 * MB_IN_BYTES ) {
			return true;
		}
	}
	return false;
}

/**
 * S3 delete.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 */
function semrs_bk_s3_delete( $o, $name ) {
	semrs_bk_s3_call( $o, 'DELETE', semrs_bk_remote_folder( $o ) . '/' . $name );
}

/*
 * Dropbox (PKCE sign-in: only the app key is needed, no secret).
 */

/**
 * Fresh Dropbox access token.
 *
 * @param array $o Options.
 * @return string|WP_Error
 */
function semrs_bk_dropbox_token( $o ) {
	if ( ! empty( $o['access'] ) && (int) ( $o['access_exp'] ?? 0 ) > time() + 60 ) {
		return $o['access'];
	}
	if ( empty( $o['refresh'] ) ) {
		return new WP_Error( 'semrs', __( 'Dropbox is not connected yet.', 'semrs-pro' ) );
	}
	$res  = wp_remote_post( 'https://api.dropboxapi.com/oauth2/token', array( 'body' => array( 'grant_type' => 'refresh_token', 'refresh_token' => $o['refresh'], 'client_id' => $o['app_key'] ), 'timeout' => 20 ) );
	$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( empty( $data['access_token'] ) ) {
		return semrs_bk_http_error( $res, 'Dropbox' );
	}
	semrs_bk_dest_save( 'dropbox', array( 'access' => $data['access_token'], 'access_exp' => time() + (int) $data['expires_in'] ) );
	return $data['access_token'];
}

/**
 * Dropbox upload session (8 MB chunks).
 *
 * @param array  $o    Options.
 * @param string $path File.
 * @param string $name Name.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_dropbox_put( $o, $path, $name, &$st, $end ) {
	$tok = semrs_bk_dropbox_token( $o );
	if ( is_wp_error( $tok ) ) {
		return $tok;
	}
	$size = (int) filesize( $path );
	$csz  = 8 * MB_IN_BYTES;
	$h    = array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/octet-stream' );
	$api  = 'https://content.dropboxapi.com/2/files/upload_session/';
	while ( microtime( true ) < $end ) {
		$off  = (int) ( $st['off'] ?? 0 );
		$data = semrs_bk_read( $path, $off, $csz );
		if ( empty( $st['sid'] ) ) {
			$res = wp_remote_post( $api . 'start', array( 'headers' => $h + array( 'Dropbox-API-Arg' => wp_json_encode( array( 'close' => false ) ) ), 'body' => $data, 'timeout' => 90 ) );
			$j   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
			if ( empty( $j['session_id'] ) ) {
				return semrs_bk_http_error( $res, 'Dropbox' );
			}
			$st = array( 'sid' => $j['session_id'], 'off' => strlen( $data ) );
		} elseif ( $off < $size ) {
			$res = wp_remote_post( $api . 'append_v2', array( 'headers' => $h + array( 'Dropbox-API-Arg' => wp_json_encode( array( 'cursor' => array( 'session_id' => $st['sid'], 'offset' => $off ) ) ) ), 'body' => $data, 'timeout' => 90 ) );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
				return semrs_bk_http_error( $res, 'Dropbox' );
			}
			$st['off'] = $off + strlen( $data );
		}
		if ( (int) $st['off'] >= $size ) {
			$arg = array(
				'cursor' => array( 'session_id' => $st['sid'], 'offset' => (int) $st['off'] ),
				'commit' => array( 'path' => '/' . semrs_bk_remote_folder( $o ) . '/' . $name, 'mode' => 'overwrite', 'mute' => true ),
			);
			$res = wp_remote_post( $api . 'finish', array( 'headers' => $h + array( 'Dropbox-API-Arg' => wp_json_encode( $arg ) ), 'body' => '', 'timeout' => 60 ) );
			return 200 === (int) wp_remote_retrieve_response_code( $res ) ? true : semrs_bk_http_error( $res, 'Dropbox' );
		}
	}
	return false;
}

/**
 * Dropbox download (8 MB ranges).
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  Unused.
 * @param string $path Local file.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_dropbox_get( $o, $name, $rid, $path, &$st, $end ) {
	$tok = semrs_bk_dropbox_token( $o );
	if ( is_wp_error( $tok ) ) {
		return $tok;
	}
	while ( microtime( true ) < $end ) {
		$off = (int) ( $st['off'] ?? 0 );
		$res = wp_remote_post(
			'https://content.dropboxapi.com/2/files/download',
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Dropbox-API-Arg' => wp_json_encode( array( 'path' => '/' . semrs_bk_remote_folder( $o ) . '/' . $name ) ), 'Range' => 'bytes=' . $off . '-' . ( $off + 8 * MB_IN_BYTES - 1 ) ),
				'timeout' => 90,
			)
		);
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 416 === $code ) {
			return true;
		}
		if ( ! in_array( $code, array( 200, 206 ), true ) ) {
			return semrs_bk_http_error( $res, 'Dropbox' );
		}
		$data = (string) wp_remote_retrieve_body( $res );
		file_put_contents( $path, $data, $off ? FILE_APPEND : 0 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$st['off'] = $off + strlen( $data );
		if ( 200 === $code || strlen( $data ) < 8 * MB_IN_BYTES ) {
			return true;
		}
	}
	return false;
}

/**
 * Dropbox delete.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 */
function semrs_bk_dropbox_delete( $o, $name ) {
	$tok = semrs_bk_dropbox_token( $o );
	if ( ! is_wp_error( $tok ) ) {
		wp_remote_post( 'https://api.dropboxapi.com/2/files/delete_v2', array( 'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'path' => '/' . semrs_bk_remote_folder( $o ) . '/' . $name ) ), 'timeout' => 20 ) );
	}
}

/*
 * Google Drive (your own OAuth client; files only in a "SEMRS Backups" folder).
 */

/**
 * Fresh Google access token.
 *
 * @param array $o Options.
 * @return string|WP_Error
 */
function semrs_bk_gdrive_token( $o ) {
	if ( ! empty( $o['access'] ) && (int) ( $o['access_exp'] ?? 0 ) > time() + 60 ) {
		return $o['access'];
	}
	if ( empty( $o['refresh'] ) ) {
		return new WP_Error( 'semrs', __( 'Google Drive is not connected yet.', 'semrs-pro' ) );
	}
	$res  = wp_remote_post( 'https://oauth2.googleapis.com/token', array( 'body' => array( 'grant_type' => 'refresh_token', 'refresh_token' => $o['refresh'], 'client_id' => $o['client_id'], 'client_secret' => $o['client_secret'] ), 'timeout' => 20 ) );
	$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( empty( $data['access_token'] ) ) {
		return semrs_bk_http_error( $res, 'Google Drive' );
	}
	semrs_bk_dest_save( 'gdrive', array( 'access' => $data['access_token'], 'access_exp' => time() + (int) $data['expires_in'] ) );
	return $data['access_token'];
}

/**
 * The backups folder in Drive (made once).
 *
 * @param array  $o   Options.
 * @param string $tok Token.
 * @return string|WP_Error
 */
function semrs_bk_gdrive_folder( $o, $tok ) {
	if ( ! empty( $o['folder_id'] ) ) {
		return $o['folder_id'];
	}
	$res = wp_remote_post(
		'https://www.googleapis.com/drive/v3/files',
		array(
			'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'name' => str_replace( '/', ' - ', semrs_bk_remote_folder( $o ) ), 'mimeType' => 'application/vnd.google-apps.folder' ) ),
			'timeout' => 20,
		)
	);
	$j   = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( empty( $j['id'] ) ) {
		return semrs_bk_http_error( $res, 'Google Drive' );
	}
	semrs_bk_dest_save( 'gdrive', array( 'folder_id' => $j['id'] ) );
	return $j['id'];
}

/**
 * Google Drive resumable upload (8 MB chunks).
 *
 * @param array  $o    Options.
 * @param string $path File.
 * @param string $name Name.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_gdrive_put( $o, $path, $name, &$st, $end ) {
	$tok = semrs_bk_gdrive_token( $o );
	if ( is_wp_error( $tok ) ) {
		return $tok;
	}
	$size = (int) filesize( $path );
	if ( empty( $st['url'] ) ) {
		$folder = semrs_bk_gdrive_folder( semrs_bk_dest_opts( 'gdrive' ), $tok );
		if ( is_wp_error( $folder ) ) {
			return $folder;
		}
		$res = wp_remote_post(
			'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable',
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json; charset=UTF-8', 'X-Upload-Content-Length' => (string) $size, 'X-Upload-Content-Type' => 'application/octet-stream' ),
				'body'    => wp_json_encode( array( 'name' => $name, 'parents' => array( $folder ) ) ),
				'timeout' => 30,
			)
		);
		$url = (string) wp_remote_retrieve_header( $res, 'location' );
		if ( '' === $url ) {
			return semrs_bk_http_error( $res, 'Google Drive' );
		}
		$st = array( 'url' => $url, 'off' => 0 );
	}
	$csz = 8 * MB_IN_BYTES;
	while ( microtime( true ) < $end ) {
		$off  = (int) $st['off'];
		$data = $size ? semrs_bk_read( $path, $off, $csz ) : '';
		$last = $off + strlen( $data ) - 1;
		$res  = wp_remote_request(
			$st['url'],
			array(
				'method'  => 'PUT',
				'headers' => array( 'Content-Length' => (string) strlen( $data ), 'Content-Range' => $size ? 'bytes ' . $off . '-' . $last . '/' . $size : 'bytes */0' ),
				'body'    => $data,
				'timeout' => 90,
			)
		);
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 200 === $code || 201 === $code ) {
			$j               = json_decode( (string) wp_remote_retrieve_body( $res ), true );
			$st['remote_id'] = (string) ( $j['id'] ?? '' );
			return true;
		}
		if ( 308 !== $code ) {
			return semrs_bk_http_error( $res, 'Google Drive' );
		}
		$range     = (string) wp_remote_retrieve_header( $res, 'range' );
		$st['off'] = preg_match( '/-(\d+)$/', $range, $m ) ? (int) $m[1] + 1 : $last + 1;
	}
	return false;
}

/**
 * Google Drive download (8 MB ranges).
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  File id.
 * @param string $path Local file.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_gdrive_get( $o, $name, $rid, $path, &$st, $end ) {
	$tok = semrs_bk_gdrive_token( $o );
	if ( is_wp_error( $tok ) ) {
		return $tok;
	}
	if ( '' === $rid ) {
		return new WP_Error( 'semrs', __( 'This Google Drive file is not known on this site. Download it from Drive and upload it here.', 'semrs-pro' ) );
	}
	while ( microtime( true ) < $end ) {
		$off  = (int) ( $st['off'] ?? 0 );
		$res  = wp_remote_get( 'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $rid ) . '?alt=media', array( 'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Range' => 'bytes=' . $off . '-' . ( $off + 8 * MB_IN_BYTES - 1 ) ), 'timeout' => 90 ) );
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 416 === $code ) {
			return true;
		}
		if ( ! in_array( $code, array( 200, 206 ), true ) ) {
			return semrs_bk_http_error( $res, 'Google Drive' );
		}
		$data = (string) wp_remote_retrieve_body( $res );
		file_put_contents( $path, $data, $off ? FILE_APPEND : 0 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$st['off'] = $off + strlen( $data );
		if ( 200 === $code || strlen( $data ) < 8 * MB_IN_BYTES ) {
			return true;
		}
	}
	return false;
}

/**
 * Google Drive delete.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  File id.
 */
function semrs_bk_gdrive_delete( $o, $name, $rid ) {
	$tok = semrs_bk_gdrive_token( $o );
	if ( $rid && ! is_wp_error( $tok ) ) {
		wp_remote_request( 'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $rid ), array( 'method' => 'DELETE', 'headers' => array( 'Authorization' => 'Bearer ' . $tok ), 'timeout' => 20 ) );
	}
}

/*
 * FTP / FTPS.
 */

/**
 * Connect.
 *
 * @param array $o Options.
 * @return resource|object|WP_Error
 */
function semrs_bk_ftp_connect( $o ) {
	if ( ! function_exists( 'ftp_connect' ) ) {
		return new WP_Error( 'semrs', __( 'This server has no FTP support (PHP ftp extension).', 'semrs-pro' ) );
	}
	$port = (int) ( $o['port'] ?? 21 ) ?: 21;
	$c    = ! empty( $o['ssl'] ) && function_exists( 'ftp_ssl_connect' ) ? @ftp_ssl_connect( $o['host'], $port, 30 ) : @ftp_connect( $o['host'], $port, 30 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $c || ! @ftp_login( $c, (string) $o['user'], (string) $o['pass'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return new WP_Error( 'semrs', __( 'Could not log in to the FTP server. Check the host, username and password.', 'semrs-pro' ) );
	}
	ftp_pasv( $c, empty( $o['active'] ) );
	$dir = '/' . semrs_bk_remote_folder( $o );
	if ( ! @ftp_chdir( $c, $dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$walk = '';
		foreach ( explode( '/', trim( $dir, '/' ) ) as $p ) {
			$walk .= '/' . $p;
			@ftp_mkdir( $c, $walk ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		if ( ! @ftp_chdir( $c, $dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'semrs', __( 'The FTP folder could not be opened or created.', 'semrs-pro' ) );
		}
	}
	return $c;
}

/**
 * FTP upload, resumed with APPE on the next step.
 *
 * @param array  $o    Options.
 * @param string $path File.
 * @param string $name Name.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_ftp_put( $o, $path, $name, &$st, $end ) {
	$c = semrs_bk_ftp_connect( $o );
	if ( is_wp_error( $c ) ) {
		return $c;
	}
	$start = empty( $st['started'] ) ? 0 : max( 0, (int) ftp_size( $c, $name ) );
	$st['started'] = 1;
	$r = ftp_nb_put( $c, $name, $path, FTP_BINARY, $start );
	while ( FTP_MOREDATA === $r && microtime( true ) < $end ) {
		$r = ftp_nb_continue( $c );
	}
	ftp_close( $c );
	if ( FTP_FAILED === $r ) {
		return new WP_Error( 'semrs', __( 'The FTP upload failed.', 'semrs-pro' ) );
	}
	return FTP_FINISHED === $r;
}

/**
 * FTP download.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  Unused.
 * @param string $path Local file.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_ftp_get( $o, $name, $rid, $path, &$st, $end ) {
	$c = semrs_bk_ftp_connect( $o );
	if ( is_wp_error( $c ) ) {
		return $c;
	}
	$start = is_file( $path ) && ! empty( $st['started'] ) ? (int) filesize( $path ) : 0;
	$st['started'] = 1;
	$fh = fopen( $path, $start ? 'ab' : 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$r  = ftp_nb_fget( $c, $fh, $name, FTP_BINARY, $start );
	while ( FTP_MOREDATA === $r && microtime( true ) < $end ) {
		$r = ftp_nb_continue( $c );
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	ftp_close( $c );
	if ( FTP_FAILED === $r ) {
		return new WP_Error( 'semrs', __( 'The FTP download failed.', 'semrs-pro' ) );
	}
	return FTP_FINISHED === $r;
}

/**
 * FTP delete.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 */
function semrs_bk_ftp_delete( $o, $name ) {
	$c = semrs_bk_ftp_connect( $o );
	if ( ! is_wp_error( $c ) ) {
		@ftp_delete( $c, $name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		ftp_close( $c );
	}
}

/*
 * SFTP (needs the PHP ssh2 extension).
 */

/**
 * Connect: [sftp handle, remote folder].
 *
 * @param array $o Options.
 * @return array|WP_Error
 */
function semrs_bk_sftp_connect( $o ) {
	if ( ! function_exists( 'ssh2_connect' ) ) {
		return new WP_Error( 'semrs', __( 'SFTP needs the PHP ssh2 extension, which this server does not have. Use FTPS, S3, Dropbox or Google Drive instead, or ask your host to add ssh2.', 'semrs-pro' ) );
	}
	$s = @ssh2_connect( $o['host'], (int) ( $o['port'] ?? 22 ) ?: 22 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $s || ! @ssh2_auth_password( $s, (string) $o['user'], (string) $o['pass'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return new WP_Error( 'semrs', __( 'Could not log in to the SFTP server.', 'semrs-pro' ) );
	}
	$sftp = ssh2_sftp( $s );
	$dir  = '/' . semrs_bk_remote_folder( $o );
	@ssh2_sftp_mkdir( $sftp, $dir, 0750, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	return array( $sftp, 'ssh2.sftp://' . intval( $sftp ) . $dir . '/' );
}

/**
 * SFTP upload in 8 MB chunks.
 *
 * @param array  $o    Options.
 * @param string $path File.
 * @param string $name Name.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_sftp_put( $o, $path, $name, &$st, $end ) {
	$c = semrs_bk_sftp_connect( $o );
	if ( is_wp_error( $c ) ) {
		return $c;
	}
	$size = (int) filesize( $path );
	$off  = (int) ( $st['off'] ?? 0 );
	$out  = fopen( $c[1] . $name, $off ? 'ab' : 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $out ) {
		return new WP_Error( 'semrs', __( 'The SFTP file could not be written.', 'semrs-pro' ) );
	}
	while ( $off < $size && microtime( true ) < $end ) {
		$data = semrs_bk_read( $path, $off, 8 * MB_IN_BYTES );
		fwrite( $out, $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$off += strlen( $data );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$st['off'] = $off;
	return $off >= $size;
}

/**
 * SFTP download.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  Unused.
 * @param string $path Local file.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_sftp_get( $o, $name, $rid, $path, &$st, $end ) {
	$c = semrs_bk_sftp_connect( $o );
	if ( is_wp_error( $c ) ) {
		return $c;
	}
	$in = fopen( $c[1] . $name, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $in ) {
		return new WP_Error( 'semrs', __( 'The SFTP file could not be read.', 'semrs-pro' ) );
	}
	$off = (int) ( $st['off'] ?? 0 );
	fseek( $in, $off );
	$out = fopen( $path, $off ? 'ab' : 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	while ( ! feof( $in ) && microtime( true ) < $end ) {
		$data = fread( $in, MB_IN_BYTES ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, (string) $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$off += strlen( (string) $data );
	}
	$done = feof( $in );
	fclose( $in ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$st['off'] = $off;
	return $done;
}

/**
 * SFTP delete.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 */
function semrs_bk_sftp_delete( $o, $name ) {
	$c = semrs_bk_sftp_connect( $o );
	if ( ! is_wp_error( $c ) ) {
		@ssh2_sftp_unlink( $c[0], '/' . semrs_bk_remote_folder( $o ) . '/' . $name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
}

/*
 * WebDAV.
 */

/**
 * File URL on the WebDAV server (folders made on the way).
 *
 * @param array  $o    Options: url, user, pass.
 * @param string $name Name.
 * @return string
 */
function semrs_bk_webdav_url( $o, $name ) {
	$base = trailingslashit( (string) ( $o['url'] ?? '' ) );
	$walk = $base;
	foreach ( explode( '/', semrs_bk_remote_folder( $o ) ) as $p ) {
		$walk .= rawurlencode( $p ) . '/';
		wp_remote_request( $walk, array( 'method' => 'MKCOL', 'headers' => semrs_bk_webdav_auth( $o ), 'timeout' => 15 ) );
	}
	return $walk . rawurlencode( $name );
}

/**
 * Basic auth header.
 *
 * @param array $o Options.
 * @return array
 */
function semrs_bk_webdav_auth( $o ) {
	return array( 'Authorization' => 'Basic ' . base64_encode( (string) ( $o['user'] ?? '' ) . ':' . (string) ( $o['pass'] ?? '' ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/**
 * WebDAV upload (streamed with cURL; one request per file part).
 *
 * @param array  $o    Options.
 * @param string $path File.
 * @param string $name Name.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_webdav_put( $o, $path, $name, &$st, $end ) {
	$url = semrs_bk_webdav_url( $o, $name );
	if ( function_exists( 'curl_init' ) ) {
		$fh = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$ch = curl_init( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		curl_setopt_array( // phpcs:ignore WordPress.WP.AlternativeFunctions
			$ch,
			array(
				CURLOPT_PUT            => true,
				CURLOPT_INFILE         => $fh,
				CURLOPT_INFILESIZE     => filesize( $path ),
				CURLOPT_USERPWD        => (string) ( $o['user'] ?? '' ) . ':' . (string) ( $o['pass'] ?? '' ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 600,
			)
		);
		curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return $code >= 200 && $code < 300 ? true : new WP_Error( 'semrs', sprintf( 'WebDAV (%d)', $code ) );
	}
	$res = wp_remote_request( $url, array( 'method' => 'PUT', 'headers' => semrs_bk_webdav_auth( $o ), 'body' => (string) file_get_contents( $path ), 'timeout' => 300 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$code = (int) wp_remote_retrieve_response_code( $res );
	return $code >= 200 && $code < 300 ? true : semrs_bk_http_error( $res, 'WebDAV' );
}

/**
 * WebDAV download (8 MB ranges).
 *
 * @param array  $o    Options.
 * @param string $name Name.
 * @param string $rid  Unused.
 * @param string $path Local file.
 * @param array  $st   State.
 * @param float  $end  Deadline.
 * @return bool|WP_Error
 */
function semrs_bk_webdav_get( $o, $name, $rid, $path, &$st, $end ) {
	$url = trailingslashit( (string) $o['url'] ) . implode( '/', array_map( 'rawurlencode', explode( '/', semrs_bk_remote_folder( $o ) ) ) ) . '/' . rawurlencode( $name );
	while ( microtime( true ) < $end ) {
		$off  = (int) ( $st['off'] ?? 0 );
		$res  = wp_remote_get( $url, array( 'headers' => semrs_bk_webdav_auth( $o ) + array( 'Range' => 'bytes=' . $off . '-' . ( $off + 8 * MB_IN_BYTES - 1 ) ), 'timeout' => 90 ) );
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 416 === $code ) {
			return true;
		}
		if ( ! in_array( $code, array( 200, 206 ), true ) ) {
			return semrs_bk_http_error( $res, 'WebDAV' );
		}
		$data = (string) wp_remote_retrieve_body( $res );
		file_put_contents( $path, $data, $off ? FILE_APPEND : 0 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$st['off'] = $off + strlen( $data );
		if ( 200 === $code || strlen( $data ) < 8 * MB_IN_BYTES ) {
			return true;
		}
	}
	return false;
}

/**
 * WebDAV delete.
 *
 * @param array  $o    Options.
 * @param string $name Name.
 */
function semrs_bk_webdav_delete( $o, $name ) {
	$url = trailingslashit( (string) $o['url'] ) . implode( '/', array_map( 'rawurlencode', explode( '/', semrs_bk_remote_folder( $o ) ) ) ) . '/' . rawurlencode( $name );
	wp_remote_request( $url, array( 'method' => 'DELETE', 'headers' => semrs_bk_webdav_auth( $o ), 'timeout' => 20 ) );
}

/*
 * Email (small sites: most mailboxes accept up to about 20 MB).
 */

/**
 * Email one file.
 *
 * @param array  $o    Options: to, max (MB).
 * @param string $path File.
 * @param string $name Name.
 * @return bool|WP_Error
 */
function semrs_bk_email_put( $o, $path, $name ) {
	$max = max( 1, (int) ( $o['max'] ?? 20 ) ) * MB_IN_BYTES;
	if ( filesize( $path ) > $max ) {
		/* translators: %s: file */
		return new WP_Error( 'semrs', sprintf( __( '%s is too big to email. Lower the part size, or use another storage for large sites.', 'semrs-pro' ), $name ) );
	}
	$to = is_email( (string) ( $o['to'] ?? '' ) ) ? $o['to'] : get_option( 'admin_email' );
	/* translators: 1: site, 2: file */
	$ok = wp_mail( $to, sprintf( __( '[%1$s] Backup file %2$s', 'semrs-pro' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $name ), __( 'A SEMRS backup file is attached. Keep it somewhere safe.', 'semrs-pro' ), '', array( $path ) );
	return $ok ? true : new WP_Error( 'semrs', __( 'The email could not be sent (check SEMRS → Email or your SMTP settings).', 'semrs-pro' ) );
}
