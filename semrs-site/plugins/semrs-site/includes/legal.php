<?php
/**
 * Legal pages for semrs.com (Tools → semrs.com pages → Legal pages): Terms and Conditions,
 * Privacy Policy, Refund Policy, Disclaimer and Cookie Policy, written to meet the Paddle
 * seller minimums (Paddle named as reseller and Merchant of Record, a 30-day refund window,
 * refunds requested at paddle.net, no "all sales final").
 *
 * The business name, address, email and registration number are not written into the pages:
 * [semrs_seller] reads them from SEMRS Updates → Sales & prices, so they stay in one place.
 * [semrs_business] prints the "What SEMRS sells" summary used on the legal and About pages.
 *
 * @package semrs-site
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seller details (SEMRS Updates → Sales & prices).
 *
 * @return array{name:string,address:string,email:string,tax:string}
 */
function semrs_legal_seller() {
	$s = (array) get_option( 'semrs_us_sales', array() );
	// Public pages show only an address on the site's own domain; a personal sales email stays private.
	$email = (string) ( $s['seller_email'] ?? '' );
	if ( ! $email || '@' . wp_parse_url( home_url(), PHP_URL_HOST ) !== strtolower( strstr( $email, '@' ) ) ) {
		$email = 'admin@' . wp_parse_url( home_url(), PHP_URL_HOST );
	}
	return array(
		'name'    => (string) ( ! empty( $s['seller_name'] ) ? $s['seller_name'] : 'SEMRS' ),
		'address' => trim( (string) ( $s['seller_address'] ?? '' ) ),
		'email'   => $email,
		'tax'     => trim( (string) ( $s['seller_tax'] ?? '' ) ),
	);
}

/**
 * [semrs_seller field="name|address|email|tax|block"]
 *
 * @param array $atts Attributes.
 * @return string
 */
function semrs_legal_seller_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'field' => 'name' ), $atts );
	$s    = semrs_legal_seller();
	switch ( $atts['field'] ) {
		case 'email':
			return '<a href="mailto:' . esc_attr( antispambot( $s['email'] ) ) . '">' . esc_html( antispambot( $s['email'] ) ) . '</a>';
		case 'address':
			return $s['address'] ? nl2br( esc_html( $s['address'] ) ) : '';
		case 'tax':
			return esc_html( $s['tax'] );
		case 'block':
			$lines = array( '<strong>' . esc_html( $s['name'] ) . '</strong> ' . ( 'SEMRS' === $s['name'] ? esc_html__( '(Search Engine Marketing & Ranking Solutions)', 'semrs-site' ) : esc_html__( '(trading as SEMRS, Search Engine Marketing & Ranking Solutions)', 'semrs-site' ) ) );
			if ( $s['address'] ) {
				$lines[] = nl2br( esc_html( $s['address'] ) );
			}
			if ( $s['tax'] ) {
				/* translators: %s: registration or tax number */
				$lines[] = esc_html( sprintf( __( 'Registration / tax number: %s', 'semrs-site' ), $s['tax'] ) );
			}
			$lines[] = esc_html__( 'Email:', 'semrs-site' ) . ' <a href="mailto:' . esc_attr( antispambot( $s['email'] ) ) . '">' . esc_html( antispambot( $s['email'] ) ) . '</a>';
			return '<p class="semrs-seller">' . implode( '<br>', $lines ) . '</p>';
		default:
			return esc_html( $s['name'] );
	}
}
add_shortcode( 'semrs_seller', 'semrs_legal_seller_shortcode' );

/**
 * [semrs_business]: what semrs.com sells, and how each part is paid for.
 *
 * @return string
 */
function semrs_legal_business_shortcode() {
	$store = get_post_type_archive_link( 'semrs_product' );
	$pro   = get_page_by_path( 'semrs-pro' );
	$quote = get_page_by_path( 'get-a-quote' );
	$card  = function ( $title, $text, $items, $foot ) {
		$li = '';
		foreach ( $items as $i ) {
			$li .= '<li>' . $i . '</li>';
		}
		return '<div class="semrs-biz__card"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $text ) . '</p><ul>' . $li . '</ul><p class="semrs-biz__foot">' . $foot . '</p></div>';
	};
	return '<div class="semrs-biz">'
		. $card(
			__( 'WordPress themes and plugins', 'semrs-site' ),
			__( 'Digital products you download and install on your own WordPress website.', 'semrs-site' ),
			array(
				esc_html__( 'Free: the SEMRS theme, SEMRS Child SEO theme and the SEMRS Core, Blocks and Tools plugins (GPL).', 'semrs-site' ),
				esc_html__( 'Paid: SEMRS Pro add-ons (SEO Pro, Speed Pro, Ads Pro, Theme Pro or All Pro), sold as yearly or lifetime licence keys for 1, 5 or unlimited websites.', 'semrs-site' ),
				esc_html__( 'Delivered instantly: download link and licence key by email, updates inside WordPress.', 'semrs-site' ),
			),
			esc_html__( 'Bought online. Our order process is conducted by our online reseller Paddle.com. Paddle.com is the Merchant of Record for all our orders.', 'semrs-site' )
			. ( $pro ? ' <a href="' . esc_url( get_permalink( $pro ) ) . '">' . esc_html__( 'SEMRS Pro plans and prices', 'semrs-site' ) . '</a>' : '' )
			. ( $store ? ' · <a href="' . esc_url( $store ) . '">' . esc_html__( 'All products', 'semrs-site' ) . '</a>' : '' )
		)
		. $card(
			__( 'Digital marketing services', 'semrs-site' ),
			__( 'Work our team does for your business, quoted in writing before it starts.', 'semrs-site' ),
			array(
				esc_html__( 'SEO, SEO content writing and content management.', 'semrs-site' ),
				esc_html__( 'Website design on WordPress.', 'semrs-site' ),
				esc_html__( 'Social media management and paid ads management.', 'semrs-site' ),
			),
			esc_html__( 'Agreed by written quote and paid by invoice directly to SEMRS. Services are not sold through Paddle.', 'semrs-site' )
			. ( $quote ? ' <a href="' . esc_url( get_permalink( $quote ) ) . '">' . esc_html__( 'Get a quote', 'semrs-site' ) . '</a>' : '' )
		)
		. '</div>';
}
add_shortcode( 'semrs_business', 'semrs_legal_business_shortcode' );

/**
 * Styles for the summary and seller block (only where they are used).
 */
function semrs_legal_styles() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post || ( ! has_shortcode( $post->post_content, 'semrs_business' ) && ! has_shortcode( $post->post_content, 'semrs_seller' ) ) ) {
		return;
	}
	wp_register_style( 'semrs-legal', false, array(), SEMRS_SITE_VERSION );
	wp_enqueue_style( 'semrs-legal' );
	wp_add_inline_style( 'semrs-legal', '.semrs-biz{display:grid;gap:1rem;margin:1.5rem 0}@media(min-width:782px){.semrs-biz{grid-template-columns:1fr 1fr}}.semrs-biz__card{padding:1.25rem 1.4rem;border:1px solid var(--sp-line,#e3e7ee);border-radius:var(--sp-radius,12px);background:var(--sp-surface,#f6f7f9)}.semrs-biz__card h3{margin-top:0}.semrs-biz__card ul{padding-left:1.2rem}.semrs-biz__foot{margin-bottom:0;font-size:.95em}.semrs-seller{padding:1rem 1.2rem;border-left:4px solid var(--sp-accent,#d4a72c);background:var(--sp-surface,#f6f7f9)}' );
}
add_action( 'wp_enqueue_scripts', 'semrs_legal_styles', 20 );

/**
 * Block markup helpers.
 *
 * @param string $t Text (HTML allowed: links, strong).
 * @return string
 */
function semrs_legal_p( $t ) {
	return '<!-- wp:paragraph --><p>' . $t . '</p><!-- /wp:paragraph -->';
}

/**
 * Heading.
 *
 * @param string $t Text.
 * @param int    $l Level.
 * @return string
 */
function semrs_legal_h( $t, $l = 2 ) {
	return '<!-- wp:heading' . ( 2 === $l ? '' : ' {"level":' . $l . '}' ) . ' --><h' . $l . ' class="wp-block-heading">' . $t . '</h' . $l . '><!-- /wp:heading -->';
}

/**
 * List.
 *
 * @param string[] $items Items (HTML allowed).
 * @return string
 */
function semrs_legal_ul( $items ) {
	$li = '';
	foreach ( $items as $i ) {
		$li .= '<!-- wp:list-item --><li>' . $i . '</li><!-- /wp:list-item -->';
	}
	return '<!-- wp:list --><ul class="wp-block-list">' . $li . '</ul><!-- /wp:list -->';
}

/**
 * Shortcode block.
 *
 * @param string $s Shortcode.
 * @return string
 */
function semrs_legal_sc( $s ) {
	return '<!-- wp:shortcode -->' . $s . '<!-- /wp:shortcode -->';
}

/**
 * The legal pages: slug => title, SEO title, description, content.
 *
 * @return array
 */
function semrs_legal_pages() {
	$date  = 'October 29, 2026';
	$intro = semrs_legal_p( '<em>Last updated: ' . $date . '</em>' );
	$mor   = 'Our order process is conducted by our online reseller Paddle.com. Paddle.com is the Merchant of Record for all our orders. Paddle provides all customer service inquiries and handles returns.';
	$link  = function ( $slug, $text ) {
		return '<a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . $text . '</a>';
	};

	$pages = array();

	$pages['terms-and-conditions'] = array(
		'title' => 'Terms and Conditions',
		'seo'   => 'Terms and Conditions | SEMRS',
		'desc'  => 'Terms for using semrs.com, buying SEMRS WordPress themes, plugins and Pro licences through Paddle, and hiring SEMRS for digital marketing services.',
		'body'  => $intro
			. semrs_legal_p( 'These terms apply when you use semrs.com, download or buy SEMRS WordPress products, or hire SEMRS for digital marketing services. The website and products are provided by the business named below. When you buy from us or use the website, your agreement is with this business:' )
			. semrs_legal_sc( '[semrs_seller field="block"]' )
			. semrs_legal_p( 'By using the website, downloading a product or placing an order, you agree to these terms. If you continue to use the website after we update them, you agree to the updated terms. If you do not agree, please do not use the website or our products.' )
			. semrs_legal_h( '1. What we sell' )
			. semrs_legal_sc( '[semrs_business]' )
			. semrs_legal_h( '2. Orders, payment and Paddle' )
			. semrs_legal_p( '<strong>' . $mor . '</strong>' )
			. semrs_legal_p( 'When you buy a SEMRS WordPress product or licence online, you buy it from Paddle, who resells it to you, and the purchase is also covered by the <a href="https://www.paddle.com/legal/checkout-buyer-terms" rel="noopener">Paddle Buyer Terms</a>. Paddle collects payment, issues your receipt and invoice, and charges any sales tax or VAT that applies in your country. Prices are shown in US dollars on our website; the price you pay is confirmed at checkout before you pay.' )
			. semrs_legal_ul(
				array(
					'<strong>Yearly licences</strong> renew automatically every 12 months at the price shown at checkout unless you cancel before the renewal date. We email you before each renewal. You can cancel at any time from the link in your Paddle receipt, at <a href="https://www.paddle.net" rel="noopener">paddle.net</a>, or by emailing us; your licence then runs until the end of the period you paid for.',
					'<strong>Lifetime licences</strong> are a single payment with no renewal.',
					'<strong>Upgrades</strong> (more websites or more add-ons) are charged at the difference shown at checkout.',
					'<strong>Failed payments:</strong> if a renewal payment fails, updates and support pause until the payment is completed.',
				)
			)
			. semrs_legal_p( 'Digital marketing services are not sold through Paddle. They are agreed in a written quote and paid by invoice directly to us (see section 5).' )
			. semrs_legal_h( '3. Licences for themes and plugins' )
			. semrs_legal_ul(
				array(
					'<strong>Open-source code:</strong> SEMRS themes and plugins, free and paid, are released under the GNU General Public License version 2 or later (GPL), like WordPress itself. Nothing in these terms limits your rights under the GPL.',
					'<strong>What a paid licence gives you:</strong> a licence key that activates the Pro add-ons you bought on the number of live websites in your plan (1, 5 or unlimited), one-click updates and email support for as long as the licence is active. Local and staging addresses do not use a website slot.',
					'<strong>When a yearly licence ends:</strong> the add-ons you paid for keep working on your sites; updates and support stop until you renew.',
					'<strong>Licence keys are personal to the buyer.</strong> Do not publish, share or resell your key. We may deactivate a key that is shared publicly, used on more sites than the plan allows, or bought with a fraudulent payment.',
					'<strong>Names and logos:</strong> the SEMRS name and logo are not covered by the GPL. You may not use them to brand your own products or services.',
				)
			)
			. semrs_legal_h( '4. Support and updates' )
			. semrs_legal_p( 'We provide support by email for active licences, usually within two business days, for questions about installing, setting up and using SEMRS products. Support does not include custom development or problems caused by other themes, plugins or hosting. We aim to keep products compatible with current versions of WordPress and PHP, but we do not promise that the website or the products will always be available, uninterrupted or free of errors. Always keep a backup of your website before installing updates.' )
			. semrs_legal_h( '5. Digital marketing services' )
			. semrs_legal_ul(
				array(
					'<strong>Quotes:</strong> every service starts with a written quote or proposal that sets out the work, price, timing and payment terms. Work starts once you accept it and any first payment is made.',
					'<strong>Monthly services</strong> renew each month until you cancel with 30 days’ notice. Annual billing covers the full 12 months.',
					'<strong>Your materials:</strong> you confirm that you have the rights to the logos, text, images and accounts you give us. You keep ownership of them.',
					'<strong>Ownership of finished work:</strong> once paid in full, you own the website content and design files we create for you, except third-party items (such as stock photos, fonts and plugins) that stay under their own licences.',
					'<strong>Access:</strong> where you give us access to your website, hosting, Google or social media accounts, we use it only for the agreed work. You can remove our access at any time.',
					'<strong>Results:</strong> search engines and ad platforms decide rankings, traffic and ad performance. We do not guarantee a particular position, number of visitors, leads or sales.',
				)
			)
			. semrs_legal_h( '6. Free tools and content' )
			. semrs_legal_p( 'The free SEO tools, templates, guides and blog articles on semrs.com are provided for general information. Please read our ' . $link( 'disclaimer', 'Disclaimer' ) . ' before relying on them.' )
			. semrs_legal_h( '7. Acceptable use' )
			. semrs_legal_p( 'You agree not to:' )
			. semrs_legal_ul(
				array(
					'use the website, tools or products for anything unlawful, fraudulent or harmful, or to send spam;',
					'infringe anyone’s intellectual property or privacy;',
					'upload or spread malware, or try to break, probe or overload the website, its security or the licence server;',
					'scrape or copy the website or its tools by automated means without our written permission;',
					'remove licence checks in order to share Pro features with people who have not paid for them.',
				)
			)
			. semrs_legal_h( '8. Intellectual property' )
			. semrs_legal_p( 'Apart from code released under the GPL, we keep all rights in the website, its text, design, graphics, tools and the SEMRS name and logo. Using the website does not give you any right to use them except as these terms allow.' )
			. semrs_legal_h( '9. Suspension and termination' )
			. semrs_legal_p( 'We may suspend or end your access to the website, your licence key, updates or services if you seriously or repeatedly break these terms, do not pay, or use them in a way that creates a security or fraud risk. If we end a paid licence for a reason that is not your fault, we will refund the unused part of the period you paid for.' )
			. semrs_legal_h( '10. Liability' )
			. semrs_legal_p( 'Our products and website are provided “as is”. To the extent the law allows, we are not liable for indirect or consequential losses, lost profits, lost data or lost rankings, and our total liability for any claim is limited to the amount you paid us in the 12 months before the claim. Nothing in these terms limits liability that cannot be limited by law, or your statutory rights as a consumer.' )
			. semrs_legal_h( '11. Refunds and privacy' )
			. semrs_legal_p( 'Refunds are explained in our ' . $link( 'refund-policy', 'Refund Policy' ) . '. How we handle personal data is explained in our ' . $link( 'privacy-policy', 'Privacy Policy' ) . ' and ' . $link( 'cookie-policy', 'Cookie Policy' ) . '.' )
			. semrs_legal_h( '12. Changes and law' )
			. semrs_legal_p( 'We may update these terms when our products, services or the law change. The date at the top shows the latest version. These terms are governed by the law of the country where the business named above is registered, without removing any rights you have under the consumer law of the country you live in.' )
			. semrs_legal_h( '13. Contact' )
			. semrs_legal_p( 'Questions about these terms: [semrs_seller field="email"] or our ' . $link( 'contact-us', 'contact page' ) . '. For help with a payment, receipt or refund for an online order, you can also contact Paddle at <a href="https://www.paddle.net" rel="noopener">paddle.net</a>.' ),
	);

	$pages['refund-policy'] = array(
		'title' => 'Refund Policy',
		'seo'   => 'Refund Policy: 30-Day Money-Back Guarantee | SEMRS',
		'desc'  => 'SEMRS offers a 30-day money-back guarantee on WordPress theme, plugin and Pro licence orders, processed by Paddle, plus refund terms for marketing services.',
		'body'  => $intro
			. semrs_legal_h( 'WordPress products and licences: 30-day money-back guarantee' )
			. semrs_legal_p( 'If you are not happy with a SEMRS theme, plugin or Pro licence you bought online, you can ask for a full refund within <strong>30 days</strong> of the purchase date, for any reason. The same 30 days apply to the first payment of a yearly licence, to each yearly renewal and to lifetime licences.' )
			. semrs_legal_p( '<strong>' . $mor . '</strong>' )
			. semrs_legal_h( 'How to ask for a refund', 3 )
			. semrs_legal_ul(
				array(
					'Go to <a href="https://www.paddle.net" rel="noopener">paddle.net</a> and look up your order with the email address you used at checkout, or use the link in your Paddle receipt; or',
					'Email us at [semrs_seller field="email"] with your order number and we will arrange the refund with Paddle.',
				)
			)
			. semrs_legal_p( 'Refunds are paid by Paddle to the original payment method. Banks usually show them within 5 to 10 business days. When a licence is refunded, its key is deactivated.' )
			. semrs_legal_h( 'After 30 days', 3 )
			. semrs_legal_p( 'After 30 days we may still refund in fair cases, for example if a product does not work as described and we cannot fix it, or you were charged twice. To stop a future renewal, cancel at any time before the renewal date; your licence keeps working until the end of the period you paid for.' )
			. semrs_legal_p( 'This policy does not limit any refund rights you have under the <a href="https://www.paddle.com/legal/checkout-buyer-terms" rel="noopener">Paddle Buyer Terms</a> or the consumer law of your country.' )
			. semrs_legal_h( 'Digital marketing services' )
			. semrs_legal_p( 'Services are paid by invoice directly to SEMRS, not through Paddle. For services:' )
			. semrs_legal_ul(
				array(
					'If you cancel before work starts, we refund everything you paid for that work.',
					'If you cancel after work starts, we refund any amount paid for work not yet done; work already completed is not refundable.',
					'Monthly services can be cancelled with 30 days’ notice; there is no charge after the notice period ends.',
					'If we cannot deliver what the quote promised, we will put it right or refund the part that was not delivered.',
				)
			)
			. semrs_legal_p( 'To ask for a service refund, email [semrs_seller field="email"] with your invoice number. We reply within two business days and pay approved refunds within 14 days by the method you paid with.' ),
	);

	$pages['privacy-policy'] = array(
		'title' => 'Privacy Policy',
		'seo'   => 'Privacy Policy | SEMRS',
		'desc'  => 'How SEMRS collects, uses and protects personal data on semrs.com, for product orders processed by Paddle, licence keys, quotes and marketing services.',
		'body'  => $intro
			. semrs_legal_p( 'This policy explains what personal data we collect when you use semrs.com, buy or use SEMRS WordPress products, or ask us about our marketing services, and what we do with it.' )
			. semrs_legal_h( '1. Who we are' )
			. semrs_legal_p( 'The business below is the controller of your personal data and decides how it is used. For any privacy question or request, email [semrs_seller field="email"].' )
			. semrs_legal_sc( '[semrs_seller field="block"]' )
			. semrs_legal_h( '2. What we collect and why' )
			. semrs_legal_ul(
				array(
					'<strong>Contact and quote forms:</strong> your name, email, phone (optional), business website and message, to reply and prepare a quote. Legal basis: steps you ask for before a contract, and our legitimate interest in answering enquiries.',
					'<strong>Product orders:</strong> Paddle, as Merchant of Record, collects your name, email, billing country, tax details and payment details. Paddle shares with us your name, email, country, the product bought and the order status, so we can issue your licence key and download. We never see your full card details. Legal basis: performance of a contract.',
					'<strong>Licence keys and updates:</strong> when a SEMRS product downloaded from semrs.com checks your licence or looks for updates, it sends the licence key, the website address, and the WordPress, PHP and plugin versions. We use this to activate the key, count website slots and deliver updates. Legal basis: performance of a contract.',
					'<strong>Service clients:</strong> billing name and address, invoices, and the access and materials needed for the agreed work. Legal basis: performance of a contract and our legal duty to keep accounting records.',
					'<strong>Emails:</strong> licence, renewal and receipt emails are part of your order. We only send marketing emails if you agree, and every one has an unsubscribe link. Legal basis: contract, or your consent.',
					'<strong>Website use:</strong> pages viewed, device and browser type, approximate location from your IP address, and how you arrived, through Google Analytics, plus server logs kept for security. Legal basis: your consent where required, and our legitimate interest in keeping the site secure and useful.',
					'<strong>Advertising:</strong> some free content pages show Google AdSense ads. Google may use cookies to show and measure ads, as explained in our ' . $link( 'cookie-policy', 'Cookie Policy' ) . '. Legal basis: your consent where required.',
					'<strong>Free tools:</strong> most tools run in your browser and what you type is not sent to us. Tools that must fetch a web page (such as the redirect checker) send only the address you enter, which is not stored.',
				)
			)
			. semrs_legal_p( 'We do not sell your personal data, and we do not collect sensitive data such as health or religious beliefs.' )
			. semrs_legal_h( '3. Who we share it with' )
			. semrs_legal_ul(
				array(
					'<strong>Paddle</strong> (Paddle.com Market Limited), our reseller and Merchant of Record, for checkout, payment, tax, invoices and refunds. See the <a href="https://www.paddle.com/legal/privacy" rel="noopener">Paddle privacy notice</a>.',
					'<strong>Service providers</strong> who help us run the business: web hosting and email delivery, Google (Analytics, AdSense, Search Console and Workspace) and backup storage. They may use the data only to provide their service to us.',
					'<strong>Professional advisers</strong> such as accountants and lawyers, when needed.',
					'<strong>Authorities</strong>, when the law requires it, or to protect our rights or prevent fraud.',
				)
			)
			. semrs_legal_h( '4. International transfers' )
			. semrs_legal_p( 'Some providers process data outside your country, including in the United States. Where data from the UK, EEA or Switzerland is transferred, we rely on adequacy decisions or on the Standard Contractual Clauses (or the UK International Data Transfer Addendum) that these providers offer.' )
			. semrs_legal_h( '5. How long we keep it' )
			. semrs_legal_ul(
				array(
					'Enquiries that do not become work: up to 2 years.',
					'Orders, invoices and licence records: for as long as the licence is active and then 7 years, for tax and accounting.',
					'Service client files: for the length of the contract and then 7 years for accounting records; access to your accounts is removed when the work ends.',
					'Analytics data: 14 months. Security logs: up to 90 days.',
				)
			)
			. semrs_legal_h( '6. Your rights' )
			. semrs_legal_p( 'Depending on where you live, you can ask to access, correct or delete your data, to restrict or object to how we use it, to receive it in a portable format, and to withdraw consent at any time (this does not affect anything done before). Email [semrs_seller field="email"]; we reply within one month. If you are in the UK or EEA, you can also complain to your data protection authority. If you are in California, you have the right to know, delete and correct your data, and we do not sell or share it for cross-context behavioural advertising except through Google ads that you can refuse in the cookie settings.' )
			. semrs_legal_h( '7. Security' )
			. semrs_legal_p( 'We use HTTPS across the site, limit access to personal data to the people who need it, keep software up to date and use strong passwords with two-factor sign-in. No website is completely secure, but we work to protect your data and will tell you and the authorities about a serious breach as the law requires.' )
			. semrs_legal_h( '8. Children' )
			. semrs_legal_p( 'Our products and services are for businesses and adults. We do not knowingly collect data from children under 16.' )
			. semrs_legal_h( '9. Changes' )
			. semrs_legal_p( 'We may update this policy; the date at the top shows the latest version. If a change is important, we will tell customers by email.' ),
	);

	$pages['cookie-policy'] = array(
		'title' => 'Cookie Policy',
		'seo'   => 'Cookie Policy | SEMRS',
		'desc'  => 'Which cookies semrs.com uses: essential, preference, Google Analytics and Google AdSense cookies, and how to accept, refuse or remove them.',
		'body'  => $intro
			. semrs_legal_p( 'Cookies are small files a website stores in your browser. This page explains the cookies and similar storage semrs.com uses and how you can control them. It is part of our ' . $link( 'privacy-policy', 'Privacy Policy' ) . '.' )
			. semrs_legal_h( 'Types of cookies we use' )
			. semrs_legal_ul(
				array(
					'<strong>Essential:</strong> keep the site secure, remember your cookie choice and keep you signed in if you have an account. These cannot be switched off.',
					'<strong>Preferences:</strong> remember settings and the inputs you saved in our free tools, stored only in your own browser.',
					'<strong>Analytics (Google Analytics):</strong> count visits and show which pages are useful, so we can improve them. Set only if you allow them where consent is required.',
					'<strong>Advertising (Google AdSense):</strong> some free content pages show Google ads. Google and its partners may use cookies to show ads based on your visits to this and other websites and to measure them. Set only if you allow them where consent is required.',
				)
			)
			. semrs_legal_p( 'We use Google Consent Mode: if you refuse analytics or advertising cookies, Google tags run without setting those cookies. Product orders are handled on Paddle’s checkout, which sets its own essential cookies to process payment securely.' )
			. semrs_legal_h( 'Your choices' )
			. semrs_legal_ul(
				array(
					'Use the cookie banner or settings link to accept or refuse analytics and advertising cookies, and change your mind at any time.',
					'Manage personalised Google ads at <a href="https://adssettings.google.com" rel="noopener">Google Ads Settings</a>, and read <a href="https://policies.google.com/technologies/partner-sites" rel="noopener">how Google uses information from sites that use its services</a>.',
					'Block or delete cookies in your browser settings. Some parts of the site may not work without essential cookies.',
				)
			)
			. semrs_legal_p( 'Questions: [semrs_seller field="email"].' ),
	);

	$pages['disclaimer'] = array(
		'title' => 'Disclaimer',
		'seo'   => 'Disclaimer | SEMRS',
		'desc'  => 'The SEMRS disclaimer for free SEO tools, blog articles, templates, results, affiliate and advertising content, and third-party links on semrs.com.',
		'body'  => $intro
			. semrs_legal_h( 'General information only' )
			. semrs_legal_p( 'The articles, guides, templates, checklists and free SEO tools on semrs.com are general information about digital marketing and WordPress. They are not legal, financial, tax or other professional advice for your situation. Search engines and ad platforms change often, so check important decisions against official sources such as Google Search Central.' )
			. semrs_legal_h( 'No guaranteed results' )
			. semrs_legal_p( 'Rankings, traffic, leads, sales and ad earnings depend on many things outside our control, including search engine algorithms, competition and your own website and business. Examples on this site show what is possible, not a promise. SEMRS does not guarantee any particular result from our services, products, tools or content.' )
			. semrs_legal_h( 'Free tools' )
			. semrs_legal_p( 'Our free tools give estimates and suggestions. They may contain errors and are provided as they are. Check any code (such as robots.txt rules or schema markup) before you use it on a live website.' )
			. semrs_legal_h( 'Products' )
			. semrs_legal_p( 'SEMRS themes and plugins are provided under the GPL and the ' . $link( 'terms-and-conditions', 'Terms and Conditions' ) . '. Back up your website before installing or updating any theme or plugin.' )
			. semrs_legal_h( 'Advertising and links' )
			. semrs_legal_p( 'Some pages show Google AdSense ads, which are labelled. We do not control which ads Google shows, and an ad is not an endorsement. Links to other websites are for convenience; we are not responsible for their content or privacy practices. If we ever earn a commission from a link, we will say so next to it.' )
			. semrs_legal_h( 'Trademarks' )
			. semrs_legal_p( 'WordPress, Google, Bing and other names mentioned on this site are trademarks of their owners. SEMRS is not affiliated with or endorsed by them.' )
			. semrs_legal_h( 'Questions' )
			. semrs_legal_p( 'Email [semrs_seller field="email"].' ),
	);

	return $pages;
}

/**
 * Create or update the legal pages (published), switch ads off on them and add any missing
 * ones to the menu that already links to the privacy policy. WordPress keeps the old text as
 * a revision.
 *
 * @return array{created:int,updated:int}
 */
function semrs_legal_install() {
	$done = array(
		'created' => 0,
		'updated' => 0,
	);
	$ids  = array();
	foreach ( semrs_legal_pages() as $slug => $p ) {
		$page = get_page_by_path( $slug );
		$data = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $p['title'],
			'post_name'    => $slug,
			'post_content' => $p['body'],
		);
		if ( $page ) {
			$data['ID'] = $page->ID;
			$id         = wp_update_post( wp_slash( $data ) );
			$key        = 'updated';
		} else {
			$id  = wp_insert_post( wp_slash( $data ) );
			$key = 'created';
		}
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$done[ $key ]++;
		$ids[ $slug ] = (int) $id;
		update_post_meta( $id, '_semrs_ads_off', true );
		if ( function_exists( 'semrs_store_set_seo' ) ) {
			semrs_store_set_seo( $id, $p['seo'], $p['desc'], mb_strtolower( $p['title'] ) );
		}
	}
	if ( ! empty( $ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
	}

	// The pricing box shows the same 30-day guarantee as the Refund Policy.
	$sales = (array) get_option( 'semrs_us_sales', array() );
	if ( empty( $sales['refund_days'] ) || 30 > (int) $sales['refund_days'] ) {
		$sales['refund_days'] = 30;
		update_option( 'semrs_us_sales', $sales, false );
	}

	// One clear description of the business, everywhere it is shown.
	update_option( 'blogdescription', 'Digital marketing services and WordPress themes & plugins' );
	$about = get_page_by_path( 'about' );
	if ( $about && ! has_shortcode( $about->post_content, 'semrs_business' ) ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $about->ID,
					'post_content' => $about->post_content . semrs_legal_h( 'What SEMRS sells' ) . semrs_legal_p( 'SEMRS has two parts: a digital marketing service for businesses, and a range of WordPress themes and plugins that anyone can download.' ) . semrs_legal_sc( '[semrs_business]' ),
				)
			)
		);
	}
	$assets = get_page_by_path( 'semrs-assets' );
	if ( $assets ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $assets->ID,
					'post_title'   => 'SEMRS WordPress Themes & Plugins',
					'post_content' => semrs_legal_p( 'SEMRS builds the WordPress theme and plugins that run semrs.com and makes them available to everyone. The theme and the Core, Blocks and Tools plugins are free under the GPL. SEMRS Pro add-ons unlock extra SEO, speed, ads and design features with a licence key for 1, 5 or unlimited websites, with prices shown below.' )
						. semrs_legal_sc( '[semrs_business]' )
						. semrs_legal_h( 'Themes and plugins' )
						. semrs_legal_sc( '[semrs_products]' )
						. semrs_legal_h( 'SEMRS Pro plans and prices' )
						. semrs_legal_sc( '[semrs_pro_plans]' )
						. semrs_legal_p( 'Every order includes a 30-day money-back guarantee. Read the <a href="' . esc_url( home_url( '/refund-policy/' ) ) . '">Refund Policy</a> and <a href="' . esc_url( home_url( '/terms-and-conditions/' ) ) . '">Terms and Conditions</a>.' ),
				)
			)
		);
		update_post_meta( $assets->ID, '_semrs_ads_off', true );
		if ( function_exists( 'semrs_store_set_seo' ) ) {
			semrs_store_set_seo( $assets->ID, 'SEMRS WordPress Themes & Plugins: Free and Pro', 'Free SEMRS WordPress theme and SEO, speed, ads and blocks plugins, plus SEMRS Pro add-ons from $15 a year with a 30-day money-back guarantee.', 'semrs wordpress themes and plugins' );
		}
	}

	// Footer: add missing legal pages to every menu that links to the privacy policy.
	foreach ( wp_get_nav_menus() as $menu ) {
		$items  = (array) wp_get_nav_menu_items( $menu->term_id );
		$linked = wp_list_pluck( wp_list_filter( $items, array( 'object' => 'page' ) ), 'object_id' );
		$linked = array_map( 'intval', $linked );
		if ( empty( $ids['privacy-policy'] ) || ! in_array( $ids['privacy-policy'], $linked, true ) ) {
			continue;
		}
		foreach ( array( 'refund-policy', 'disclaimer', 'cookie-policy' ) as $slug ) {
			if ( ! empty( $ids[ $slug ] ) && ! in_array( $ids[ $slug ], $linked, true ) ) {
				wp_update_nav_menu_item(
					$menu->term_id,
					0,
					array(
						'menu-item-object-id' => $ids[ $slug ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
		}
	}
	return $done;
}

/**
 * Installer box (printed inside the semrs.com pages form).
 */
function semrs_legal_admin_box() {
	$s = semrs_legal_seller();
	echo '<h2>' . esc_html__( 'Legal pages (Paddle-ready)', 'semrs-site' ) . '</h2><p>' . esc_html__( 'Creates or updates Terms and Conditions, Privacy Policy, Refund Policy (30 days), Disclaimer and Cookie Policy, published, with Paddle named as reseller and Merchant of Record. Existing pages at those addresses are replaced; the old text stays in the page revisions. Refund, Disclaimer and Cookie Policy are added to the footer menu next to the Privacy Policy. It also sets the site tagline, adds a What SEMRS sells section to the About page and rewrites the SEMRS assets page with current products and prices.', 'semrs-site' ) . '</p>';
	$missing = array();
	if ( ! $s['address'] ) {
		$missing[] = __( 'business address', 'semrs-site' );
	}
	if ( 'SEMRS' === $s['name'] ) {
		$missing[] = __( 'legal business name', 'semrs-site' );
	}
	if ( $missing ) {
		/* translators: %s: missing fields */
		echo '<div class="notice notice-warning inline"><p>' . esc_html( sprintf( __( 'Paddle checks that the terms and privacy policy name the legal business and its registered address. Add the %s under SEMRS Updates → Sales & prices; the pages update automatically.', 'semrs-site' ), implode( ' and ', $missing ) ) ) . '</p></div>';
	}
	submit_button( __( 'Create or update legal pages', 'semrs-site' ), 'secondary', 'semrs_legal_install' );
}
