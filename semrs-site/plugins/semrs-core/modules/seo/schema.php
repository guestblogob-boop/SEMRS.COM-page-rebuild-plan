<?php
/**
 * JSON-LD graph: Organization/LocalBusiness, WebSite, WebPage, BreadcrumbList,
 * Article/BlogPosting, Person, Service, WebApplication, and FAQPage built
 * from visible Details blocks. Only what is visible on the page.
 *
 * @package semrs-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schema types offered per post.
 *
 * @return array<string, string>
 */
function semrs_seo_schema_types() {
	return array(
		''               => __( 'Default (BlogPosting for posts, WebPage for others)', 'semrs-core' ),
		'Article'        => 'Article',
		'BlogPosting'    => 'BlogPosting',
		'NewsArticle'    => 'NewsArticle',
		'Service'        => __( 'Service (service pages)', 'semrs-core' ),
		'WebApplication' => __( 'WebApplication (free tool pages)', 'semrs-core' ),
		'AboutPage'      => 'AboutPage',
		'ContactPage'    => 'ContactPage',
		'CollectionPage' => 'CollectionPage',
		'none'           => __( 'WebPage only (no extra type)', 'semrs-core' ),
	);
}

/**
 * Question/answer pairs from core Details blocks in the content.
 *
 * @param string $content Raw post content.
 * @return array<int, array{0:string,1:string}>
 */
function semrs_seo_faq_pairs( $content ) {
	$pairs = array();
	$walk  = function ( $blocks ) use ( &$walk, &$pairs ) {
		foreach ( $blocks as $block ) {
			// SEMRS Blocks accordion: skip it when "Use as FAQ schema" is off.
			if ( 'semrs/accordion' === $block['blockName'] && isset( $block['attrs']['faqSchema'] ) && false === $block['attrs']['faqSchema'] ) {
				continue;
			}
			if ( in_array( $block['blockName'], array( 'core/details', 'semrs/accordion-item' ), true ) && preg_match( '/<summary[^>]*>(.*?)<\/summary>(.*)<\/details>/is', render_block( $block ), $m ) ) {
				$q = trim( wp_strip_all_tags( $m[1] ) );
				$a = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[2] ) ) );
				if ( $q && $a ) {
					$pairs[] = array( $q, $a );
				}
			} elseif ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $content ) );
	return $pairs;
}

/**
 * Build the graph for the current request.
 *
 * @return array
 */
function semrs_seo_graph() {
	$s     = semrs_seo_settings();
	$home  = home_url( '/' );
	$org   = $home . '#organization';
	$site  = $home . '#website';
	$graph = array();

	// Organization / LocalBusiness / Person.
	$entity = array(
		'@type' => $s['org_type'],
		'@id'   => $org,
		'name'  => $s['org_name'] ? $s['org_name'] : get_bloginfo( 'name' ),
		'url'   => $home,
	);
	$logo = $s['org_logo'];
	if ( ! $logo && has_custom_logo() ) {
		$logo = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
	}
	if ( $logo ) {
		$entity['logo']  = array( '@type' => 'ImageObject', 'url' => $logo );
		$entity['image'] = $logo;
	}
	foreach ( array( 'org_email' => 'email', 'org_phone' => 'telephone' ) as $k => $prop ) {
		if ( $s[ $k ] ) {
			$entity[ $prop ] = $s[ $k ];
		}
	}
	if ( $s['org_city'] || $s['org_country'] ) {
		$entity['address'] = array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $s['org_street'],
				'addressLocality' => $s['org_city'],
				'addressRegion'   => $s['org_region'],
				'postalCode'      => $s['org_postal'],
				'addressCountry'  => $s['org_country'],
			)
		);
	}
	$area = array_filter( array_map( 'trim', explode( ',', (string) $s['org_area'] ) ) );
	if ( $area && 'Person' !== $s['org_type'] ) {
		$entity['areaServed'] = array_values( $area );
	}
	$same = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['org_same_as'] ) ) );
	if ( $same ) {
		$entity['sameAs'] = array_values( $same );
	}
	$graph[] = $entity;

	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $site,
		'url'             => $home,
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'publisher'       => array( '@id' => $org ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => $home . '?s={search_term_string}' ),
			'query-input' => 'required name=search_term_string',
		),
	);

	if ( is_404() || is_search() ) {
		return $graph;
	}

	$url  = semrs_seo_canonical();
	$url  = $url ? $url : home_url( add_query_arg( array() ) );
	$page = array(
		'@type'      => 'WebPage',
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => semrs_seo_title() ? semrs_seo_title() : wp_get_document_title(),
		'isPartOf'   => array( '@id' => $site ),
		'inLanguage' => get_bloginfo( 'language' ),
	);
	$desc = semrs_seo_description();
	if ( $desc ) {
		$page['description'] = $desc;
	}
	if ( is_front_page() ) {
		$page['about'] = array( '@id' => $org );
	}

	// Breadcrumbs.
	if ( ! is_front_page() ) {
		$items = array();
		foreach ( semrs_seo_breadcrumb_trail() as $i => $crumb ) {
			$item = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0] );
			if ( $crumb[1] ) {
				$item['item'] = $crumb[1];
			}
			$items[] = $item;
		}
		$graph[]            = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items );
		$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}

	if ( is_singular() ) {
		$post  = get_queried_object();
		$type  = (string) get_post_meta( $post->ID, '_semrs_schema', true );
		$image = semrs_seo_image();
		$page['datePublished'] = get_the_date( 'c', $post );
		$page['dateModified']  = get_the_modified_date( 'c', $post );
		if ( $image ) {
			$page['primaryImageOfPage'] = array( '@type' => 'ImageObject', 'url' => $image );
		}
		if ( in_array( $type, array( 'AboutPage', 'ContactPage', 'CollectionPage' ), true ) ) {
			$page['@type'] = array( 'WebPage', $type );
		}

		if ( '' === $type && 'post' === $post->post_type ) {
			$type = 'BlogPosting';
		}
		if ( in_array( $type, array( 'Article', 'BlogPosting', 'NewsArticle' ), true ) ) {
			$author_id   = (int) $post->post_author;
			$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
			$person      = $home . '#/schema/person/' . $author_id;
			if ( $author_name ) {
				$graph[] = array_filter(
					array(
						'@type'       => 'Person',
						'@id'         => $person,
						'name'        => $author_name,
						'url'         => get_author_posts_url( $author_id ),
						'description' => get_the_author_meta( 'description', $author_id ),
					)
				);
			}
			$article = array(
				'@type'            => $type,
				'@id'              => $url . '#article',
				'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
				'datePublished'    => get_the_date( 'c', $post ),
				'dateModified'     => get_the_modified_date( 'c', $post ),
				'author'           => $author_name ? array( '@id' => $person ) : array( '@id' => $org ),
				'publisher'        => array( '@id' => $org ),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'isPartOf'         => array( '@id' => $url . '#webpage' ),
				'inLanguage'       => get_bloginfo( 'language' ),
				'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
			);
			if ( $image ) {
				$article['image'] = array( '@type' => 'ImageObject', 'url' => $image );
			}
			if ( $desc ) {
				$article['description'] = $desc;
			}
			$cats = get_the_category( $post->ID );
			if ( $cats ) {
				$article['articleSection'] = $cats[0]->name;
			}
			$graph[] = $article;
		} elseif ( 'Service' === $type ) {
			$graph[] = array_filter(
				array(
					'@type'       => 'Service',
					'@id'         => $url . '#service',
					'name'        => wp_strip_all_tags( get_the_title( $post ) ),
					'description' => $desc,
					'url'         => $url,
					'provider'    => array( '@id' => $org ),
					'areaServed'  => $area ? array_values( $area ) : null,
				)
			);
		} elseif ( 'WebApplication' === $type ) {
			$graph[] = array(
				'@type'               => 'WebApplication',
				'@id'                 => $url . '#app',
				'name'                => wp_strip_all_tags( get_the_title( $post ) ),
				'description'         => $desc,
				'url'                 => $url,
				'applicationCategory' => 'BusinessApplication',
				'operatingSystem'     => 'Any',
				'browserRequirements' => 'Requires JavaScript',
				'isAccessibleForFree' => true,
				'offers'              => array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ),
				'provider'            => array( '@id' => $org ),
			);
		}

		// FAQPage from visible Details blocks (2+ questions), unless switched off.
		if ( ! get_post_meta( $post->ID, '_semrs_faq_off', true ) ) {
			$pairs = semrs_seo_faq_pairs( $post->post_content );
			if ( count( $pairs ) >= 2 ) {
				$questions = array();
				foreach ( $pairs as $pair ) {
					$questions[] = array(
						'@type'          => 'Question',
						'name'           => $pair[0],
						'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $pair[1] ),
					);
				}
				$graph[] = array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => $questions );
			}
		}
	} elseif ( is_home() || is_archive() ) {
		$page['@type'] = 'CollectionPage';
	}

	$graph[] = $page;

	/**
	 * Filter the JSON-LD graph.
	 *
	 * @param array $graph Graph nodes.
	 */
	return apply_filters( 'semrs_schema_graph', $graph );
}

/**
 * Print the graph.
 */
function semrs_seo_print_schema() {
	$json = wp_json_encode(
		array( '@context' => 'https://schema.org', '@graph' => semrs_seo_graph() ),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
	);
	if ( $json ) {
		echo '<script type="application/ld+json">' . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON with HEX_TAG.
	}
}
add_action(
	'plugins_loaded',
	function () {
		if ( semrs_seo_active() ) {
			add_action( 'wp_head', 'semrs_seo_print_schema', 2 );
		}
	},
	20
);

/**
 * Schema type and FAQ switch editable over the REST API (block editor and
 * site tools), for posts the user can edit.
 */
add_action(
	'init',
	function () {
		foreach ( array( '_semrs_schema', '_semrs_faq_off' ) as $key ) {
			register_post_meta(
				'',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => '_semrs_schema' === $key
						? function ( $v ) {
							return array_key_exists( (string) $v, semrs_seo_schema_types() ) ? (string) $v : '';
						}
						: function ( $v ) {
							return $v ? '1' : '';
						},
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
);

/**
 * Rank Math bridge: while Rank Math prints the JSON-LD, add the SEMRS
 * Service / WebApplication node and the FAQPage from visible accordions
 * when its graph has none, and supply the whole SEMRS graph on pages where
 * Rank Math prints nothing.
 *
 * @param array $data Rank Math graph nodes.
 * @return array
 */
function semrs_seo_rank_math_json_ld( $data ) {
	if ( ! is_singular() ) {
		return $data;
	}
	$graph = semrs_seo_graph();
	if ( empty( $data ) ) {
		return $graph;
	}
	$have = array();
	foreach ( (array) $data as $node ) {
		foreach ( (array) ( $node['@type'] ?? array() ) as $t ) {
			$have[ $t ] = true;
		}
	}
	foreach ( $graph as $node ) {
		$type = is_string( $node['@type'] ?? null ) ? $node['@type'] : '';
		if ( in_array( $type, array( 'Service', 'WebApplication', 'FAQPage' ), true ) && empty( $have[ $type ] ) ) {
			$data[ 'semrs-' . strtolower( $type ) ] = $node;
		}
	}
	return $data;
}
add_action(
	'plugins_loaded',
	function () {
		if ( 'Rank Math' === semrs_seo_conflict() ) {
			add_filter( 'rank_math/json_ld', 'semrs_seo_rank_math_json_ld', 999 );
		}
	},
	20
);
