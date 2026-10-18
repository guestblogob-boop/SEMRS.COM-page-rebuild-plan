<?php
/**
 * Tool and category registry. Add a tool = add an entry + assets/js/tools/<id>.js.
 *
 * @package semrs-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Categories: slug => [name, description, service CTA label, service URL].
 *
 * @return array<string, array<int, string>>
 */
function semrs_tools_categories() {
	return apply_filters(
		'semrs_tools_categories',
		array(
			'meta-serp'        => array( __( 'Meta Tags & SERP', 'semrs-tools' ), __( 'Preview and perfect how your pages look in Google.', 'semrs-tools' ), __( 'SEO Services', 'semrs-tools' ), '/services/seo/' ),
			'technical-seo'    => array( __( 'Technical SEO', 'semrs-tools' ), __( 'Crawling, indexing and redirect tools.', 'semrs-tools' ), __( 'Technical SEO', 'semrs-tools' ), '/services/technical-seo/' ),
			'schema'           => array( __( 'Schema Markup', 'semrs-tools' ), __( 'Generate structured data Google understands.', 'semrs-tools' ), __( 'Technical SEO', 'semrs-tools' ), '/services/technical-seo/' ),
			'content-keywords' => array( __( 'Content & Keywords', 'semrs-tools' ), __( 'Write and measure content that ranks.', 'semrs-tools' ), __( 'SEO Content Writing', 'semrs-tools' ), '/services/seo-content-writing/' ),
			'local-seo'        => array( __( 'Local SEO', 'semrs-tools' ), __( 'Tools for local businesses and Google Business Profile.', 'semrs-tools' ), __( 'Local SEO', 'semrs-tools' ), '/services/seo/' ),
			'ai-search'        => array( __( 'AI Search (GEO)', 'semrs-tools' ), __( 'Get found and cited by AI answer engines.', 'semrs-tools' ), __( 'AI Search Optimization', 'semrs-tools' ), '/services/ai-search-optimization/' ),
			'marketing'        => array( __( 'Ads & Marketing', 'semrs-tools' ), __( 'Campaign tracking and ad tools.', 'semrs-tools' ), __( 'Paid Ads Management', 'semrs-tools' ), '/services/paid-ads/' ),
			'website'          => array( __( 'Website & Design', 'semrs-tools' ), __( 'Faster, more accessible websites.', 'semrs-tools' ), __( 'Web Design', 'semrs-tools' ), '/services/web-design/' ),
			'calculators'      => array( __( 'Business Calculators', 'semrs-tools' ), __( 'Put numbers on your marketing decisions.', 'semrs-tools' ), __( 'Get a Quote', 'semrs-tools' ), '/get-a-quote/' ),
		)
	);
}

/**
 * Tools: id => [name, category, short description, runs (browser|server)].
 * The id is also the recommended page slug: /tools/<id>/.
 *
 * @return array<string, array<int, string>>
 */
function semrs_tools_list() {
	return apply_filters(
		'semrs_tools_list',
		array(
			'serp-snippet-preview'            => array( __( 'SERP Snippet Preview', 'semrs-tools' ), 'meta-serp', __( 'See your Google result on desktop and mobile before you publish.', 'semrs-tools' ), 'browser' ),
			'meta-description-checker'        => array( __( 'Meta Title & Description Checker', 'semrs-tools' ), 'meta-serp', __( 'Check length, pixel width and keyword use, then copy the tags.', 'semrs-tools' ), 'browser' ),
			'robots-txt-generator'            => array( __( 'Robots.txt Generator', 'semrs-tools' ), 'technical-seo', __( 'Build a correct robots.txt with sitemap and AI-crawler rules.', 'semrs-tools' ), 'browser' ),
			'redirect-checker'                => array( __( 'Redirect Checker', 'semrs-tools' ), 'technical-seo', __( 'Trace every redirect hop and status code for any URL.', 'semrs-tools' ), 'server' ),
			'schema-markup-generator'         => array( __( 'Schema Markup Generator', 'semrs-tools' ), 'schema', __( 'JSON-LD for Organization, Article, FAQ, Breadcrumb, Product, Event and Service.', 'semrs-tools' ), 'browser' ),
			'local-business-schema-generator' => array( __( 'Local Business Schema Generator', 'semrs-tools' ), 'schema', __( 'LocalBusiness JSON-LD with address, hours and geo.', 'semrs-tools' ), 'browser' ),
			'word-counter'                    => array( __( 'Word Counter', 'semrs-tools' ), 'content-keywords', __( 'Words, characters, sentences, reading time and top keywords.', 'semrs-tools' ), 'browser' ),
			'google-review-link-generator'    => array( __( 'Google Review Link Generator', 'semrs-tools' ), 'local-seo', __( 'Create a direct "leave a review" link from your Place ID.', 'semrs-tools' ), 'browser' ),
			'llms-txt-generator'              => array( __( 'llms.txt Generator', 'semrs-tools' ), 'ai-search', __( 'Create an llms.txt file that guides AI tools to your best pages.', 'semrs-tools' ), 'browser' ),
			'utm-builder'                     => array( __( 'UTM Builder', 'semrs-tools' ), 'marketing', __( 'Build tracked campaign links, one at a time or in bulk.', 'semrs-tools' ), 'browser' ),
			'image-compressor'                => array( __( 'Image Compressor & WebP Converter', 'semrs-tools' ), 'website', __( 'Compress and convert images in your browser. Files never leave your device.', 'semrs-tools' ), 'browser' ),
			'seo-roi-calculator'              => array( __( 'SEO ROI Calculator', 'semrs-tools' ), 'calculators', __( 'Estimate what extra organic traffic could be worth.', 'semrs-tools' ), 'browser' ),
		)
	);
}

/**
 * URL of a tool page (filterable for sites with other structures).
 *
 * @param string $id Tool id.
 * @return string
 */
function semrs_tools_url( $id ) {
	return apply_filters( 'semrs_tools_url', home_url( '/tools/' . $id . '/' ), $id );
}
