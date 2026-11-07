<?php
/**
 * Author profiles for E-E-A-T (free): Users → Profile → "SEO author profile".
 *
 * Fields: job title, areas of expertise, credentials, education, profile links (LinkedIn,
 * X, a personal site…). They are added to the author's Person node in the JSON-LD graph, which
 * now also says the author works for the site's organization and shows their photo. Author
 * archive pages get Google's ProfilePage schema. Posts can name a reviewer ("Reviewed by").
 *
 * Only what the person really has: every field is optional and nothing is filled in for you.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Profile fields: key => [label, type, help].
 *
 * @return array
 */
function semrs_author_fields() {
	return array(
		'job_title'   => array( __( 'Job title', 'semrs-core' ), 'text', __( 'For example: SEO consultant, Registered nurse.', 'semrs-core' ) ),
		'knows_about' => array( __( 'Areas of expertise', 'semrs-core' ), 'text', __( 'Comma separated, for example: technical SEO, local SEO, WordPress.', 'semrs-core' ) ),
		'credentials' => array( __( 'Credentials and certificates', 'semrs-core' ), 'textarea', __( 'One per line, for example: Google Ads certification.', 'semrs-core' ) ),
		'alumni_of'   => array( __( 'Education', 'semrs-core' ), 'text', __( 'School or university (comma separated).', 'semrs-core' ) ),
		'same_as'     => array( __( 'Profile links', 'semrs-core' ), 'textarea', __( 'One address per line: LinkedIn, X, a personal site, a professional directory.', 'semrs-core' ) ),
	);
}

/**
 * Lines of a multi-line or comma field.
 *
 * @param string $text  Text.
 * @param string $split Split pattern.
 * @return string[]
 */
function semrs_author_list( $text, $split = '/\R/' ) {
	return array_values( array_filter( array_map( 'trim', preg_split( $split, (string) $text ) ) ) );
}

/**
 * Schema properties for a user.
 *
 * @param int $user_id User ID.
 * @return array
 */
function semrs_author_props( $user_id ) {
	$m     = function ( $k ) use ( $user_id ) {
		return (string) get_user_meta( $user_id, '_semrs_author_' . $k, true );
	};
	$props = array();
	if ( '' !== $m( 'job_title' ) ) {
		$props['jobTitle'] = $m( 'job_title' );
	}
	$knows = semrs_author_list( $m( 'knows_about' ), '/[,\n]/' );
	if ( $knows ) {
		$props['knowsAbout'] = $knows;
	}
	$creds = semrs_author_list( $m( 'credentials' ) );
	if ( $creds ) {
		$props['hasCredential'] = array_map(
			function ( $c ) {
				return array( '@type' => 'EducationalOccupationalCredential', 'name' => $c );
			},
			$creds
		);
	}
	$schools = semrs_author_list( $m( 'alumni_of' ), '/,/' );
	if ( $schools ) {
		$props['alumniOf'] = array_map(
			function ( $s ) {
				return array( '@type' => 'EducationalOrganization', 'name' => $s );
			},
			$schools
		);
	}
	$links = array_values( array_filter( array_map( 'esc_url_raw', semrs_author_list( $m( 'same_as' ) ) ) ) );
	$site  = (string) get_the_author_meta( 'user_url', $user_id );
	if ( $site ) {
		$links[] = esc_url_raw( $site );
	}
	if ( $links ) {
		$props['sameAs'] = array_values( array_unique( $links ) );
	}
	$avatar = get_avatar_url( $user_id, array( 'size' => 256 ) );
	if ( $avatar ) {
		$props['image'] = array( '@type' => 'ImageObject', 'url' => $avatar );
	}
	$props['worksFor'] = array( '@id' => home_url( '/' ) . '#organization' );
	return $props;
}

/**
 * Add the profile to Person nodes, the reviewer to articles, and ProfilePage to author archives.
 *
 * @param array $graph Graph.
 * @return array
 */
function semrs_author_schema( $graph ) {
	$base = home_url( '/' ) . '#/schema/person/';
	foreach ( $graph as $i => $node ) {
		if ( isset( $node['@type'], $node['@id'] ) && 'Person' === $node['@type'] && 0 === strpos( $node['@id'], $base ) ) {
			$graph[ $i ] = array_merge( $node, semrs_author_props( (int) substr( $node['@id'], strlen( $base ) ) ) );
		}
	}
	// "Reviewed by": an expert who checked the post (Advanced tab of the SEO box).
	if ( is_singular() ) {
		$reviewer = (int) get_post_meta( get_queried_object_id(), '_semrs_reviewed_by', true );
		if ( $reviewer && get_userdata( $reviewer ) ) {
			$graph[] = array_merge(
				array( '@type' => 'Person', '@id' => $base . $reviewer, 'name' => get_the_author_meta( 'display_name', $reviewer ), 'url' => get_author_posts_url( $reviewer ) ),
				semrs_author_props( $reviewer )
			);
			foreach ( $graph as $i => $node ) {
				if ( isset( $node['@id'] ) && preg_match( '/#webpage$/', (string) $node['@id'] ) ) {
					$graph[ $i ]['reviewedBy'] = array( '@id' => $base . $reviewer );
					$graph[ $i ]['lastReviewed'] = get_the_modified_date( 'c' );
				}
			}
		}
	}
	// Author archive: ProfilePage about the person.
	if ( is_author() ) {
		$uid    = (int) get_queried_object_id();
		$url    = get_author_posts_url( $uid );
		$person = array_merge(
			array( '@type' => 'Person', '@id' => $base . $uid, 'name' => get_the_author_meta( 'display_name', $uid ), 'url' => $url, 'description' => get_the_author_meta( 'description', $uid ) ),
			semrs_author_props( $uid )
		);
		$graph[] = array_filter( $person );
		foreach ( $graph as $i => $node ) {
			if ( isset( $node['@id'] ) && preg_match( '/#webpage$/', (string) $node['@id'] ) ) {
				$graph[ $i ]['@type']      = 'ProfilePage';
				$graph[ $i ]['mainEntity'] = array( '@id' => $base . $uid );
			}
		}
	}
	return $graph;
}
add_filter( 'semrs_schema_graph', 'semrs_author_schema', 20 );

// Users → Profile: the fields.
foreach ( array( 'show_user_profile', 'edit_user_profile' ) as $semrs_hook ) {
	add_action(
		$semrs_hook,
		function ( $user ) {
			echo '<h2>' . esc_html__( 'SEO author profile', 'semrs-core' ) . '</h2><p class="description" style="max-width:720px">' . esc_html__( 'Shown to Google with your posts (author schema), so it can see who wrote them and why they know the subject. Fill in only what is true; everything is optional.', 'semrs-core' ) . '</p><table class="form-table" role="presentation">';
			wp_nonce_field( 'semrs_author_profile', 'semrs_author_nonce' );
			foreach ( semrs_author_fields() as $key => $f ) {
				$val = (string) get_user_meta( $user->ID, '_semrs_author_' . $key, true );
				echo '<tr><th><label for="semrs-author-' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
				if ( 'textarea' === $f[1] ) {
					echo '<textarea id="semrs-author-' . esc_attr( $key ) . '" name="semrs_author[' . esc_attr( $key ) . ']" rows="3" class="large-text">' . esc_textarea( $val ) . '</textarea>';
				} else {
					echo '<input type="text" id="semrs-author-' . esc_attr( $key ) . '" name="semrs_author[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
				}
				echo '<p class="description">' . esc_html( $f[2] ) . '</p></td></tr>';
			}
			echo '</table>';
		}
	);
}
unset( $semrs_hook );
foreach ( array( 'personal_options_update', 'edit_user_profile_update' ) as $semrs_hook ) {
	add_action(
		$semrs_hook,
		function ( $user_id ) {
			if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['semrs_author_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['semrs_author_nonce'] ) ), 'semrs_author_profile' ) ) {
				return;
			}
			$in = isset( $_POST['semrs_author'] ) ? (array) wp_unslash( $_POST['semrs_author'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field.
			foreach ( semrs_author_fields() as $key => $f ) {
				$v = (string) ( $in[ $key ] ?? '' );
				if ( 'same_as' === $key ) {
					$v = implode( "\n", array_filter( array_map( 'esc_url_raw', semrs_author_list( $v ) ) ) );
				} else {
					$v = 'textarea' === $f[1] ? sanitize_textarea_field( $v ) : sanitize_text_field( $v );
				}
				'' === $v ? delete_user_meta( $user_id, '_semrs_author_' . $key ) : update_user_meta( $user_id, '_semrs_author_' . $key, $v );
			}
		}
	);
}
unset( $semrs_hook );

// "Reviewed by" (post meta, editable in the block editor's SEMRS SEO → Advanced and over REST).
add_action(
	'init',
	function () {
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			register_post_meta(
				$type,
				'_semrs_reviewed_by',
				array(
					'type'              => 'integer',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
);
