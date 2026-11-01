// Builds the SEMRS affiliate program page (block HTML) into page.html.
// Publish with the REST API: slug affiliate-program, SEO fields in seo.json.
import fs from 'fs';
import { fileURLToPath } from 'url';
const dir = fileURLToPath(new URL('.', import.meta.url));

const p = (h) => `<!-- wp:paragraph -->\n<p>${h}</p>\n<!-- /wp:paragraph -->`;
const h2 = (t) => `<!-- wp:heading -->\n<h2 class="wp-block-heading">${t}</h2>\n<!-- /wp:heading -->`;
const h3 = (t) => `<!-- wp:heading {"level":3} -->\n<h3 class="wp-block-heading">${t}</h3>\n<!-- /wp:heading -->`;
const ul = (items) => `<!-- wp:list -->\n<ul class="wp-block-list">${items.map((i) => `<!-- wp:list-item --><li>${i}</li><!-- /wp:list-item -->`).join('')}</ul>\n<!-- /wp:list -->`;
const ol = (items) => `<!-- wp:list {"ordered":true} -->\n<ol class="wp-block-list">${items.map((i) => `<!-- wp:list-item --><li>${i}</li><!-- /wp:list-item -->`).join('')}</ol>\n<!-- /wp:list -->`;
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

// Same "at a glance" graphic as the legal pages (semrs_legal_glance): light inline SVG.
const glance = (label, points) => {
	const w = 640, col = w / points.length;
	let body = '';
	points.forEach((pt, i) => {
		const x = col * i + 8;
		const words = pt.split(' ');
		const lines = [];
		let cur = '';
		for (const wd of words) { if ((cur + ' ' + wd).trim().length > 16) { lines.push(cur.trim()); cur = wd; } else cur += ' ' + wd; }
		lines.push(cur.trim());
		body += `<rect x="${x}" y="8" width="${col - 16}" height="124" rx="14" fill="currentColor" fill-opacity=".06" stroke="var(--sp-accent,#f7941d)" stroke-width="2"/>`;
		body += `<circle cx="${x + 26}" cy="36" r="14" fill="var(--sp-primary,#0b2fd1)"/><text x="${x + 26}" y="41" text-anchor="middle" font-size="15" font-weight="700" fill="#fff">${i + 1}</text>`;
		lines.slice(0, 3).forEach((l, j) => { body += `<text x="${x + 14}" y="${76 + j * 20}" font-size="15" fill="currentColor">${esc(l)}</text>`; });
	});
	return `<!-- wp:html --><figure class="semrs-glance"><svg role="img" aria-label="${esc(label + ': ' + points.join('; '))}" viewBox="0 0 ${w} 140" width="100%" style="max-width:${w}px;height:auto" xmlns="http://www.w3.org/2000/svg">${body}</svg></figure><!-- /wp:html -->`;
};

const html = [
	p('The SEMRS affiliate program pays you <strong>50% of every first order</strong> you send us, then <strong>30% of every renewal and add-on</strong> for as long as that customer stays. You share a link, people buy SEMRS Pro, and you earn. It is free to join.'),
	p('Most WordPress plugin and theme programs pay 20% to 30% once, with a 30 to 60 day cookie. We pay more, for longer, because a happy customer who renews is worth sharing with the person who found them.'),
	glance('SEMRS affiliate program at a glance', ['50% of the first order', '30% of every renewal', '90-day cookie', 'Monthly payouts']),
	'<!-- wp:semrs/toc {"levels":"2","collapsed":true} /-->',
	h2('How the SEMRS affiliate program works'),
	ol([
		'<strong>Apply</strong> with the form below. We read every application by hand, usually within two business days.',
		'<strong>Get your link</strong> by email, plus a private dashboard link. Add <code>?ref=yourcode</code> to any semrs.com address to link straight to that page.',
		'<strong>Share it</strong> in your posts, videos, newsletters or client notes. A visit sets a 90-day cookie, and the last link clicked wins.',
		'<strong>Earn on every order</strong> from that visitor, and on their renewals and add-ons later. The customer stays linked to you for good.',
	]),
	p('Clicks, sales and commissions show in your dashboard as they happen. Nothing to install and nothing to log in to: your private link opens it.'),
	h2('What you earn'),
	p('Commissions in the affiliate program use the price the customer really pays, after any discount. Here is what that looks like at today\'s regular prices:'),
	ul([
		'All Pro for 1 website ($145 a year): you earn <strong>$72.50</strong>, then <strong>$43.50</strong> each year it renews.',
		'All Pro for 5 websites ($299 a year): you earn <strong>$149.50</strong>, then <strong>$89.70</strong> a year.',
		'All Pro for unlimited websites ($499 a year): you earn <strong>$249.50</strong>, then <strong>$149.70</strong> a year.',
		'Lifetime, unlimited websites ($695 once): you earn <strong>$347.50</strong>.',
		'A single add-on such as SEO Pro ($42 a year): you earn <strong>$21</strong>, then <strong>$12.60</strong> a year.',
	]),
	p('See every plan on the <a href="/semrs-pro/">SEMRS Pro pricing page</a>. Seasonal sales such as Black Friday bring more buyers, and you still earn your share of each sale.'),
	h2('Who it suits'),
	p('The affiliate program suits people who already talk to WordPress users:'),
	ul([
		'Bloggers and YouTubers who teach WordPress, SEO or site speed.',
		'Web designers and agencies who set up sites for clients.',
		'Course creators and community owners with a WordPress audience.',
		'Anyone already using SEMRS who wants to recommend it.',
	]),
	p('Our <a href="/tools/">free SEO tools</a> and the <a href="/wordpress-themes-plugins/">free themes and plugins</a> are easy first things to share. People who try them often upgrade to Pro later, and the 90-day cookie gives them time.'),
	h2('Rules and payouts'),
	p('A few simple rules keep the affiliate program fair for partners and customers:'),
	ul([
		'Say that you are an affiliate wherever you share your link. The <a href="https://www.ftc.gov/business-guidance/resources/ftcs-endorsement-guides-what-people-are-asking" rel="noopener" target="_blank">FTC endorsement guides</a> explain how.',
		'Do not bid on "SEMRS" or similar brand terms in paid search ads.',
		'No spam, no fake reviews, no fake or expired coupon codes, and no misleading claims.',
		'Buying through your own link does not earn a commission.',
		'Commissions are approved 30 days after the sale, once the refund window has passed. Refunded orders earn nothing.',
		'We pay monthly by PayPal, Wise or bank transfer once your approved balance reaches $50.',
	]),
	p('We can pause an account that breaks these rules. If anything is unclear, ask us first through the <a href="/contact-us/">contact page</a>.'),
	h2('Apply to join'),
	p('Fill in the short form to join the affiliate program. Tell us where you plan to share SEMRS, and the email for your payouts. Already a partner? Use the second form to get your links and dashboard again.'),
	'<!-- wp:shortcode -->[semrs_affiliates]<!-- /wp:shortcode -->',
	h2('Frequently asked questions'),
	h3('Does it cost anything to join the affiliate program?'),
	p('No. Joining is free, and you do not need to buy SEMRS Pro first.'),
	h3('How long does the cookie last?'),
	p('Ninety days. If someone clicks your link and buys within 90 days, the sale is yours, unless they click another partner\'s link later.'),
	h3('Do I earn on renewals?'),
	p('Yes. You earn 30% of every renewal and every add-on that customer buys, for as long as they stay. Most programs pay nothing after the first order.'),
	h3('When do I get paid?'),
	p('Each commission is approved 30 days after the sale. We pay approved commissions monthly, once your balance reaches $50.'),
	h3('Can I share discount codes?'),
	p('Yes, share the codes we publish, such as seasonal sale codes. Please do not make up codes or list expired ones.'),
].join('\n\n');

fs.writeFileSync(dir + 'page.html', html + '\n');
const words = html.replace(/<svg[\s\S]*?<\/svg>/g, ' ').replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length;
const kw = (html.replace(/<[^>]+>/g, ' ').toLowerCase().match(/affiliate program/g) || []).length;
console.log('words', words, 'keyword', kw);
