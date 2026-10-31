<?php
/**
 * Legal pages for semrs.com (Tools → semrs.com pages → Legal pages): Terms and Conditions,
 * Privacy Policy, Refund Policy, Disclaimer and Cookie Policy, in plain words with a
 * 30-day refund window and the seller named on every page.
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
			esc_html__( 'Confirmed by invoice. Your license key and download link arrive by email.', 'semrs-site' )
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
			esc_html__( 'Agreed by written quote and paid by invoice directly to SEMRS.', 'semrs-site' )
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
 * "At a glance" picture for a legal page: a light inline SVG (no image file, theme
 * colors, dark mode safe) with a text label for screen readers and search engines.
 *
 * @param string   $label  Accessible label.
 * @param string[] $points 3 or 4 short points (under about 26 characters each).
 * @return string
 */
function semrs_legal_glance( $label, $points ) {
	$n    = count( $points );
	$w    = 640;
	$col  = $w / $n;
	$body = '';
	foreach ( array_values( $points ) as $i => $pt ) {
		$x     = $col * $i + 8;
		$lines = explode( "\n", wordwrap( $pt, 16, "\n", true ) );
		$body .= '<rect x="' . $x . '" y="8" width="' . ( $col - 16 ) . '" height="124" rx="14" fill="currentColor" fill-opacity=".06" stroke="var(--sp-accent,#f7941d)" stroke-width="2"/>';
		$body .= '<circle cx="' . ( $x + 26 ) . '" cy="36" r="14" fill="var(--sp-primary,#0b2fd1)"/><text x="' . ( $x + 26 ) . '" y="41" text-anchor="middle" font-size="15" font-weight="700" fill="#fff">' . ( $i + 1 ) . '</text>';
		foreach ( array_slice( $lines, 0, 3 ) as $j => $line ) {
			$body .= '<text x="' . ( $x + 14 ) . '" y="' . ( 76 + $j * 20 ) . '" font-size="15" fill="currentColor">' . esc_html( $line ) . '</text>';
		}
	}
	return '<!-- wp:html --><figure class="semrs-glance"><svg role="img" aria-label="' . esc_attr( $label . ': ' . implode( '; ', $points ) ) . '" viewBox="0 0 ' . $w . ' 140" width="100%" style="max-width:' . $w . 'px;height:auto" xmlns="http://www.w3.org/2000/svg">' . $body . '</svg></figure><!-- /wp:html -->';
}

/**
 * The legal pages: slug => title, SEO title, description, content.
 *
 * @return array
 */
function semrs_legal_pages() {
	$date  = 'October 31, 2026';
	$intro = semrs_legal_p( '<em>Last updated: ' . $date . '</em>' );
	$pay   = 'Orders for SEMRS Pro are confirmed by invoice. You pay by bank transfer or by another method shown on the invoice. If we add an online checkout later, the checkout page will name the company that takes the payment before you pay, and its terms will apply too.';
	$link  = function ( $slug, $text ) {
		return '<a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . $text . '</a>';
	};

	$pages = array();

	$pages['terms-and-conditions'] = array(
		'title' => 'Terms and Conditions',
		'seo'   => 'Terms and Conditions 2026: Simple Guide | SEMRS',
		'desc'  => 'SEMRS terms and conditions for using semrs.com, buying WordPress themes, plugins and Pro licenses, and hiring SEMRS for digital marketing services.',
		'body'  => $intro
			. semrs_legal_p( 'These terms and conditions explain the rules for using semrs.com, our WordPress products and our marketing services. They are written in plain words. When you use the website or buy from us, your agreement is with this business:' )
			. semrs_legal_sc( '[semrs_seller field="block"]' )
			. semrs_legal_glance( 'Terms and conditions at a glance', array( 'GPL code: yours to use', 'Keys are for your sites', '30-day refunds', 'No promised rankings' ) )
			. semrs_legal_p( 'By using the website, downloading a product or placing an order, you agree to these terms. If you do not agree, please do not use the website or our products.' )
			. semrs_legal_h( '1. What we sell' )
			. semrs_legal_sc( '[semrs_business]' )
			. semrs_legal_h( '2. Orders and payment' )
			. semrs_legal_p( $pay )
			. semrs_legal_ul(
				array(
					'<strong>Yearly licenses</strong> last 12 months. We email you before a license ends, and you choose whether to renew.',
					'<strong>Lifetime licenses</strong> are one payment with no renewal.',
					'<strong>Upgrades</strong> to more websites or more add-ons cost the difference in price.',
					'<strong>Late payment:</strong> updates and support pause until an invoice is paid.',
				)
			)
			. semrs_legal_p( 'Marketing services are agreed in a written quote and paid by invoice (see section 5).' )
			. semrs_legal_h( '3. Licenses for themes and plugins' )
			. semrs_legal_ul(
				array(
					'<strong>Open-source code:</strong> all SEMRS themes and plugins, free and paid, use the <a href="https://www.gnu.org/licenses/gpl-2.0.html" rel="noopener">GNU General Public License</a> (version 2 or later), like WordPress. Nothing in these terms takes away your rights under the GPL.',
					'<strong>What a paid license gives you:</strong> a key that switches on the Pro add-ons you bought, on 1, 5 or unlimited live websites. It also gives you updates and email support while it is active. Local and staging sites do not use a slot.',
					'<strong>When a yearly license ends:</strong> the add-ons keep working. Updates and support stop until you renew.',
					'<strong>Keep your key private.</strong> Do not publish, share or resell it. We may switch off a key that is shared, used on too many sites or bought with a fake payment.',
					'<strong>Our name and logo</strong> are not part of the GPL. Do not use them to brand your own products.',
				)
			)
			. semrs_legal_h( '4. Support and updates' )
			. semrs_legal_p( 'We answer support emails for active licenses, usually within two business days. Support covers installing, setting up and using SEMRS products. It does not cover custom code or problems caused by other themes, plugins or hosting. We work to keep our products up to date with WordPress and PHP. We cannot promise the website or products will never have errors or downtime. Always back up your website before you update anything.' )
			. semrs_legal_h( '5. Digital marketing services' )
			. semrs_legal_ul(
				array(
					'<strong>Quotes:</strong> every service starts with a written quote. It sets out the work, price, timing and payment terms. Work starts when you accept it and pay any first invoice.',
					'<strong>Monthly services</strong> renew each month until you cancel with 30 days’ notice.',
					'<strong>Your materials:</strong> you confirm you have the rights to the logos, text, images and accounts you give us. You keep ownership of them.',
					'<strong>Finished work:</strong> once you have paid in full, you own the content and design files we make for you. Third-party items, such as stock photos, fonts and plugins, keep their own licenses.',
					'<strong>Access:</strong> we use access to your website or accounts only for the agreed work. You can remove our access at any time.',
					'<strong>Results:</strong> search engines and ad platforms decide rankings and ad performance. We do not guarantee a position, traffic, leads or sales.',
				)
			)
			. semrs_legal_h( '6. Free tools and content' )
			. semrs_legal_p( 'Our free SEO tools, templates, guides and articles are general information. Please read our ' . $link( 'disclaimer', 'Disclaimer' ) . ' before you rely on them.' )
			. semrs_legal_h( '7. Fair use' )
			. semrs_legal_p( 'Please do not:' )
			. semrs_legal_ul(
				array(
					'use the website, tools or products for anything illegal, harmful or spammy;',
					'break anyone’s copyright, trademark or privacy;',
					'upload malware, or try to break or overload the website or the license server;',
					'copy the website or its tools with bots without our written permission;',
					'remove license checks to share Pro features with people who have not paid.',
				)
			)
			. semrs_legal_h( '8. Our content' )
			. semrs_legal_p( 'Apart from GPL code, we keep all rights in the website, its text, design, graphics, tools and the SEMRS name and logo.' )
			. semrs_legal_h( '9. Ending access' )
			. semrs_legal_p( 'We may pause or end your access, license key, updates or services if you seriously or repeatedly break these terms, do not pay, or create a security or fraud risk. If we end a paid license for a reason that is not your fault, we refund the unused part.' )
			. semrs_legal_h( '10. Limits of liability' )
			. semrs_legal_p( 'Our products and website are provided “as is”. Where the law allows, we are not liable for indirect losses, lost profits, lost data or lost rankings. Our total liability for any claim is limited to what you paid us in the 12 months before the claim. Nothing here limits rights you have by law as a consumer.' )
			. semrs_legal_h( '11. Refunds and privacy' )
			. semrs_legal_p( 'Refunds are explained in our ' . $link( 'refund-policy', 'Refund Policy' ) . '. How we handle personal data is explained in our ' . $link( 'privacy-policy', 'Privacy Policy' ) . ' and ' . $link( 'cookie-policy', 'Cookie Policy' ) . '.' )
			. semrs_legal_h( '12. Changes to these terms and conditions' )
			. semrs_legal_p( 'We may update these terms when our products, services or the law change. The date at the top shows the latest version. These terms follow the law of the country where the business above is based. You keep any rights you have under the consumer law of the country where you live.' )
			. semrs_legal_h( '13. Contact' )
			. semrs_legal_p( 'Questions about these terms? Email [semrs_seller field="email"] or use our ' . $link( 'contact-us', 'contact page' ) . '.' ),
	);

	$pages['refund-policy'] = array(
		'title' => 'Refund Policy',
		'seo'   => 'Refund Policy 2026: Simple 30-Day Refunds | SEMRS',
		'desc'  => 'The SEMRS refund policy: 30-day refunds on WordPress theme, plugin and Pro license orders, plus clear refund rules for digital marketing services.',
		'body'  => $intro
			. semrs_legal_p( 'This refund policy explains how to get your money back from SEMRS. For WordPress products you have 30 days, and you do not need to give a reason. For marketing services, you only pay for work that is done.' )
			. semrs_legal_glance( 'Refund policy at a glance', array( '30 days, any reason', 'Full refund', 'Back in 5-10 days', 'Services: pay for work done' ) )
			. semrs_legal_h( 'Refund policy for WordPress products and licenses' )
			. semrs_legal_p( 'Not happy with a SEMRS theme, plugin or Pro license? Ask for a full refund within <strong>30 days</strong> of paying. This applies to a first yearly payment, to each yearly renewal and to lifetime licenses.' )
			. semrs_legal_h( 'How to ask for a refund', 3 )
			. semrs_legal_ul(
				array(
					'Email [semrs_seller field="email"] with your invoice or order number.',
					'Or send us a message through our ' . $link( 'contact-us', 'contact page' ) . '.',
				)
			)
			. semrs_legal_p( 'We send the money back by the method you paid with. Banks usually show it within 5 to 10 business days. When a license is refunded, its key is switched off.' )
			. semrs_legal_h( 'After 30 days', 3 )
			. semrs_legal_p( 'After 30 days we may still give a refund in fair cases. For example, if a product does not work as described and we cannot fix it, or if you were charged twice. To stop a renewal, just tell us before the renewal date. Your license keeps working until the end of the time you paid for.' )
			. semrs_legal_p( 'This policy does not take away any refund rights you have under the consumer law of your country. If you live in the EU, the European Commission explains your rights on its <a href="https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_en.htm" rel="noopener">guarantees and returns page</a>.' )
			. semrs_legal_h( 'Refund policy for digital marketing services' )
			. semrs_legal_p( 'Services are paid by invoice directly to SEMRS. For services:' )
			. semrs_legal_ul(
				array(
					'Cancel before work starts, and we refund everything you paid for that work.',
					'Cancel after work starts, and we refund what you paid for work not yet done. Finished work is not refundable.',
					'Monthly services can be cancelled with 30 days’ notice. Nothing is charged after the notice ends.',
					'If we cannot deliver what the quote said, we will put it right or refund the part we did not deliver.',
				)
			)
			. semrs_legal_p( 'To ask for a service refund, email [semrs_seller field="email"] with your invoice number. We reply within two business days and pay approved refunds within 14 days. Our ' . $link( 'terms-and-conditions', 'Terms and Conditions' ) . ' explain the rest.' ),
	);

	$pages['privacy-policy'] = array(
		'title' => 'Privacy Policy',
		'seo'   => 'Privacy Policy 2026: Simple Guide to Your Data | SEMRS',
		'desc'  => 'The SEMRS privacy policy: what personal data semrs.com collects for orders, license keys, quotes and services, why we use it, and how to use your rights.',
		'body'  => $intro
			. semrs_legal_p( 'This privacy policy explains what personal data we collect when you use semrs.com, buy or use SEMRS WordPress products, or ask about our marketing services. It also explains what we do with it and the choices you have.' )
			. semrs_legal_glance( 'Privacy policy at a glance', array( 'We collect little', 'We never sell data', 'Tools run in your browser', 'Ask us to delete it' ) )
			. semrs_legal_h( '1. Who we are' )
			. semrs_legal_p( 'The business below decides how your personal data is used. For any privacy question, email [semrs_seller field="email"].' )
			. semrs_legal_sc( '[semrs_seller field="block"]' )
			. semrs_legal_h( '2. What we collect and why' )
			. semrs_legal_ul(
				array(
					'<strong>Contact and quote forms:</strong> your name, email, phone (optional), website and message, so we can reply and prepare a quote. Legal basis: steps you ask for before a contract, and our interest in answering you.',
					'<strong>Orders and invoices:</strong> your name, email, billing address, country and what you bought, so we can invoice you and send your license key. We do not store card numbers. Legal basis: a contract with you, and our legal duty to keep accounts.',
					'<strong>License keys and updates:</strong> when a SEMRS product from semrs.com checks its license or looks for updates, it sends the key, the website address and the WordPress, PHP and plugin versions. Legal basis: a contract with you.',
					'<strong>Service clients:</strong> billing details, invoices, and the access and files needed for the work. Legal basis: a contract with you, and our legal duty to keep accounts.',
					'<strong>Emails:</strong> license and invoice emails are part of your order. We send marketing emails only if you agree, and each has an unsubscribe link.',
					'<strong>Website use:</strong> pages viewed, device, rough location from your IP address and how you found us, through Google Analytics, plus server logs for security. Legal basis: your consent where needed, and our interest in a safe, useful site.',
					'<strong>Advertising:</strong> some free content pages show Google AdSense ads. Google may use cookies to show and measure ads, as our ' . $link( 'cookie-policy', 'Cookie Policy' ) . ' explains. Legal basis: your consent where needed.',
					'<strong>Free tools:</strong> most tools run in your browser, so what you type is not sent to us. Tools that must fetch a web page, such as the redirect checker, send only the address you enter, and it is not stored.',
				)
			)
			. semrs_legal_p( 'We do not sell your personal data. We do not collect sensitive data, such as health or religion.' )
			. semrs_legal_h( '3. Who we share it with' )
			. semrs_legal_ul(
				array(
					'<strong>Service providers</strong> who help run the business: web hosting, email, Google (Analytics, AdSense, Search Console and Workspace), payment providers and backups. They may use the data only to provide their service to us.',
					'<strong>Advisers</strong> such as accountants and lawyers, when needed.',
					'<strong>Authorities</strong>, when the law requires it, or to stop fraud.',
				)
			)
			. semrs_legal_h( '4. Data sent abroad' )
			. semrs_legal_p( 'Some providers handle data outside your country, including in the United States. For data from the UK, EEA or Switzerland, we rely on adequacy decisions or the Standard Contractual Clauses these providers offer.' )
			. semrs_legal_h( '5. How long we keep it' )
			. semrs_legal_ul(
				array(
					'Enquiries that do not become work: up to 2 years.',
					'Orders, invoices and license records: while the license is active, then 7 years for tax.',
					'Service client files: during the contract, then 7 years for accounts. We remove our access to your accounts when the work ends.',
					'Analytics data: 14 months. Security logs: up to 90 days.',
				)
			)
			. semrs_legal_h( '6. Your privacy rights' )
			. semrs_legal_p( 'Depending on where you live, you can ask to see, correct or delete your data. You can also limit or object to how we use it, get a copy, or take back your consent at any time. Email [semrs_seller field="email"] and we reply within one month. In the UK or EEA you can also complain to your data protection authority; the UK authority explains your rights on the <a href="https://ico.org.uk/for-the-public/" rel="noopener">ICO website</a>. In California you can ask to know, delete and correct your data. We do not sell your data.' )
			. semrs_legal_h( '7. Security' )
			. semrs_legal_p( 'We use HTTPS on the whole site. Only people who need personal data can see it. We keep software up to date and use strong passwords with two-step sign-in. No website is perfectly secure, but we work hard to protect your data. We will report a serious breach as the law requires.' )
			. semrs_legal_h( '8. Children' )
			. semrs_legal_p( 'Our products and services are for businesses and adults. We do not knowingly collect data from children under 16.' )
			. semrs_legal_h( '9. Changes to this privacy policy' )
			. semrs_legal_p( 'We may update this policy. The date at the top shows the latest version. If a change is important, we will tell customers by email.' ),
	);

	$pages['cookie-policy'] = array(
		'title' => 'Cookie Policy',
		'seo'   => 'Cookie Policy 2026: Simple Guide to Our Cookies | SEMRS',
		'desc'  => 'The SEMRS cookie policy: which cookies semrs.com uses (essential, preference, analytics and advertising) and how to accept, refuse or remove them.',
		'body'  => $intro
			. semrs_legal_p( 'This cookie policy explains the cookies semrs.com uses and how you can control them. Cookies are small files a website saves in your browser. They help a site remember things, such as your choices. This page is part of our ' . $link( 'privacy-policy', 'Privacy Policy' ) . '.' )
			. semrs_legal_glance( 'Cookie policy at a glance', array( 'Essential: always on', 'Preferences: your browser', 'Analytics: you choose', 'Ads: you choose' ) )
			. semrs_legal_h( 'Cookies we use' )
			. semrs_legal_ul(
				array(
					'<strong>Essential:</strong> keep the site secure, remember your cookie choice and keep you signed in if you have an account. These cannot be switched off.',
					'<strong>Preferences:</strong> remember your settings and what you saved in our free tools. They stay in your own browser.',
					'<strong>Analytics (Google Analytics):</strong> count visits and show which pages help people, so we can improve them. Set only if you allow them where consent is needed.',
					'<strong>Advertising (Google AdSense):</strong> some free content pages show Google ads. Google and its partners may use cookies to choose and measure ads, based on your visits to this and other sites. Set only if you allow them where consent is needed.',
				)
			)
			. semrs_legal_p( 'We use Google Consent Mode. If you refuse analytics or advertising cookies, Google tags still run, but they do not set those cookies.' )
			. semrs_legal_h( 'How to control cookies' )
			. semrs_legal_ul(
				array(
					'Use the cookie banner or the cookie settings link to accept or refuse analytics and advertising cookies. You can change your mind at any time.',
					'Manage personalized Google ads at <a href="https://adssettings.google.com" rel="noopener">Google Ads Settings</a>, and read <a href="https://policies.google.com/technologies/partner-sites" rel="noopener">how Google uses information from sites that use its services</a>.',
					'Block or delete cookies in your browser settings. Some parts of the site may not work without essential cookies.',
				)
			)
			. semrs_legal_h( 'Changes to this cookie policy' )
			. semrs_legal_p( 'If we add a new type of cookie, we will update this page and ask for your consent again where the law requires it. The date at the top shows the latest version. Questions? Email [semrs_seller field="email"] or see our ' . $link( 'terms-and-conditions', 'Terms and Conditions' ) . '.' ),
	);

	$pages['disclaimer'] = array(
		'title' => 'Disclaimer',
		'seo'   => 'Disclaimer 2026: Simple Guide to Our Free Tools | SEMRS',
		'desc'  => 'The SEMRS disclaimer: what our free SEO tools, articles, templates and results mean, plus how we handle ads, links and trademarks on semrs.com.',
		'body'  => $intro
			. semrs_legal_p( 'This disclaimer explains what you can and cannot expect from the content, tools and services on semrs.com. Please read it before you act on anything you find here.' )
			. semrs_legal_glance( 'Disclaimer at a glance', array( 'General information', 'No promised results', 'Check tool output', 'Ads are labeled' ) )
			. semrs_legal_h( 'Disclaimer for our content: general information only' )
			. semrs_legal_p( 'Our articles, guides, templates, checklists and free SEO tools are general information about digital marketing and WordPress. They are not legal, financial, tax or other professional advice for your situation. Search engines and ad platforms change often. Check important decisions against official sources, such as <a href="https://developers.google.com/search/docs/essentials" rel="noopener">Google Search Essentials</a>.' )
			. semrs_legal_h( 'No guaranteed results' )
			. semrs_legal_p( 'Rankings, traffic, leads, sales and ad earnings depend on many things we do not control. These include search engine updates, your competitors and your own website and business. Examples on this site show what is possible, not a promise. SEMRS does not guarantee any result from our services, products, tools or content.' )
			. semrs_legal_h( 'Free tools' )
			. semrs_legal_p( 'Our free tools give estimates and suggestions. They may contain mistakes and are provided as they are. Check any code, such as robots.txt rules or schema markup, before you use it on a live website.' )
			. semrs_legal_h( 'Products' )
			. semrs_legal_p( 'SEMRS themes and plugins are provided under the GPL and our ' . $link( 'terms-and-conditions', 'Terms and Conditions' ) . '. Back up your website before you install or update any theme or plugin.' )
			. semrs_legal_h( 'Advertising and links' )
			. semrs_legal_p( 'Some pages show Google AdSense ads, and every ad is labeled. We do not choose which ads Google shows, and an ad is not an endorsement. Links to other websites are there to help you. We are not responsible for their content or privacy practices. If we ever earn a commission from a link, we will say so next to it.' )
			. semrs_legal_h( 'Trademarks' )
			. semrs_legal_p( 'WordPress, Google, Bing and other names on this site are trademarks of their owners. SEMRS is not part of, or endorsed by, any of them.' )
			. semrs_legal_h( 'Questions about this disclaimer' )
			. semrs_legal_p( 'Email [semrs_seller field="email"], or read our ' . $link( 'privacy-policy', 'Privacy Policy' ) . ' to see how we handle your data.' ),
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

	// The pricing box shows the same 30-day refund window as the Refund Policy.
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
	$assets = get_page_by_path( 'wordpress-themes-plugins' );
	$assets = $assets ? $assets : get_page_by_path( 'semrs-assets' ); // Address before 1.2.24.
	if ( $assets ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $assets->ID,
					'post_title'   => 'SEMRS WordPress Themes & Plugins',
					'post_content' => semrs_legal_p( 'SEMRS makes free WordPress themes and plugins for SEO, speed, ads and page building. They are the same tools that run semrs.com. The theme and the Core, Blocks and Tools plugins are free. SEMRS Pro add-ons unlock extra features with one license key for 1, 5 or unlimited websites.' )
						. semrs_legal_glance( 'SEMRS WordPress themes and plugins at a glance', array( '6 free themes & plugins', 'Pro from $19 a year', 'One key, all add-ons', '30-day refunds' ) )
						. '<!-- wp:semrs/toc {"levels":"2","collapsed":true} /-->'
						. semrs_legal_h( 'What SEMRS offers' )
						. semrs_legal_sc( '[semrs_business]' )
						. semrs_legal_h( 'Free WordPress themes and plugins' )
						. semrs_legal_p( 'Download them, install them on as many sites as you like and keep them forever. Like WordPress itself, all SEMRS code uses the GPL, and the WordPress project explains what that means on its <a href="https://wordpress.org/about/license/" rel="noopener">license page</a>.' )
						. semrs_legal_sc( '[semrs_products]' )
						. semrs_legal_h( 'SEMRS Pro plans and prices' )
						. semrs_legal_sc( '[semrs_pro_plans]' )
						. semrs_legal_h( 'Help with SEMRS themes and plugins' )
						. semrs_legal_p( 'Every guide is in the <a href="' . esc_url( home_url( '/support/' ) ) . '">SEMRS user manual</a>. Every order can be refunded within 30 days. Read the <a href="' . esc_url( home_url( '/refund-policy/' ) ) . '">Refund Policy</a> and <a href="' . esc_url( home_url( '/terms-and-conditions/' ) ) . '">Terms and Conditions</a>.' ),
				)
			)
		);
		update_post_meta( $assets->ID, '_semrs_ads_off', true );
		if ( function_exists( 'semrs_store_set_seo' ) ) {
			semrs_store_set_seo( $assets->ID, 'WordPress Themes and Plugins: 6 Free Tools + Pro | SEMRS', 'Free SEMRS WordPress themes and plugins for SEO, speed, ads and page building, plus Pro add-ons from $19 a year with 30-day refunds.', 'wordpress themes and plugins' );
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
	echo '<h2>' . esc_html__( 'Legal pages', 'semrs-site' ) . '</h2><p>' . esc_html__( 'Creates or updates Terms and Conditions, Privacy Policy, Refund Policy (30 days), Disclaimer and Cookie Policy, published, naming the seller from Sales & prices. Existing pages at those addresses are replaced; the old text stays in the page revisions. Refund, Disclaimer and Cookie Policy are added to the footer menu next to the Privacy Policy. It also sets the site tagline, adds a What SEMRS sells section to the About page and rewrites the SEMRS assets page with current products and prices.', 'semrs-site' ) . '</p>';
	$missing = array();
	if ( ! $s['address'] ) {
		$missing[] = __( 'business address', 'semrs-site' );
	}
	if ( 'SEMRS' === $s['name'] ) {
		$missing[] = __( 'legal business name', 'semrs-site' );
	}
	if ( $missing ) {
		/* translators: %s: missing fields */
		echo '<div class="notice notice-warning inline"><p>' . esc_html( sprintf( __( 'The terms and privacy policy should name the legal business and its address. Add the %s under SEMRS Updates → Sales & prices; the pages update automatically.', 'semrs-site' ), implode( ' and ', $missing ) ) ) . '</p></div>';
	}
	submit_button( __( 'Create or update legal pages', 'semrs-site' ), 'secondary', 'semrs_legal_install' );
}
