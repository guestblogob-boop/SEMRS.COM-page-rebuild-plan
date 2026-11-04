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
const table = (cap, head, rows) => `<!-- wp:table -->
<figure class="wp-block-table"><table><caption>${cap}</caption><thead><tr>${head.map((h) => `<th scope="col">${h}</th>`).join("")}</tr></thead><tbody>${rows.map((r) => `<tr>${r.map((c, j) => (j ? `<td>${c}</td>` : `<th scope="row">${c}</th>`)).join("")}</tr>`).join("")}</tbody></table></figure>
<!-- /wp:table -->`;
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
	p('The SEMRS affiliate program pays you <strong>40% to 60% of every first order</strong> you send us, wherever you live. The more you sell of an add-on in a month, the higher your rate. You also earn <strong>15% on renewals and add-ons</strong> in the customer\'s first year, a <strong>$50 bonus</strong> every month you reach 33 sales, and your own partner code saves your audience 10%. It is free to join.'),
	p('Most WordPress plugin and theme programs pay 20% to 30% once, with a 30 to 60 day cookie. We pay more, rising with your sales, we pay on first-year renewals too, and we welcome partners from every country.'),
	glance('SEMRS affiliate program at a glance', ['Up to 60% by monthly sales', '15% first-year renewals', '$50 monthly bonus', 'Paid worldwide in USD']),
	'<!-- wp:semrs/toc {"levels":"2","collapsed":true} /-->',
	h2('How the SEMRS affiliate program works'),
	ol([
		'<strong>Apply</strong> with the form below. We read every application by hand, usually within two business days.',
		'<strong>Get your link and partner code</strong> by email, plus a private dashboard link. Add <code>?ref=yourcode</code> to any semrs.com address to link straight to that page.',
		'<strong>Share them</strong> in your posts, videos, podcasts, newsletters or client notes. A click sets a 90-day cookie, and the last link clicked wins. People who hear your code can type it on the pricing page instead.',
		'<strong>Earn on every order</strong> from that visitor, and on their renewals and add-ons during their first year.',
	]),
	p('Clicks, sales and commissions show in your dashboard as they happen. Nothing to install and nothing to log in to: your private link opens it.'),
	h2('What you earn'),
	p('Your rate on first orders in the affiliate program rises with your sales of <strong>each add-on</strong> in a calendar month, from the 1st to the last day. SEO Pro, Speed Pro, Ads Pro and Theme &amp; Blocks Pro each have their own count:'),
	ul([
		'<strong>40%</strong> for your 1st to 10th sale of the month.',
		'<strong>45%</strong> for your 11th to 30th sale.',
		'<strong>50%</strong> for your 31st to 60th sale.',
		'<strong>60%</strong> from your 61st sale onward.',
		'<strong>All Pro and lifetime plans:</strong> 40%, up to $150 a sale.',
		'<strong>Renewals and add-ons later on:</strong> 15% during the customer\'s first year.',
	]),
	p('For example, 40 SEO Pro sales in a month at $42 each. After the payment fee, $37.09 of each sale is left, and each sale earns the rate of its place in the SEO Pro count:'),
	table('How 40 SEO Pro sales in one month are counted', ['Sales', 'How many', 'Rate', 'You earn'], [
		['Sales 1–10', '10 sales', '40% of $37.09 = $14.84 each', '<strong>$148.40</strong>'],
		['Sales 11–30', '20 sales', '45% of $37.09 = $16.69 each', '<strong>$333.80</strong>'],
		['Sales 31–40', '10 sales', '50% of $37.09 = $18.55 each', '<strong>$185.50</strong>'],
		['Commissions', '40 sales', '', '<strong>$667.70</strong>'],
		['Monthly bonus', '40 sales (33 or more)', '', '<strong>$50</strong>'],
		['Earned before the fee', '', '', '<strong>$717.70</strong>'],
		['Exchange and transfer fee', '', '7%', '−$50.24'],
		['Total earning this month', '', '', '<strong>$667.46</strong>'],
	]),
	p('Speed Pro, Ads Pro and Theme &amp; Blocks Pro sales that month start their own count at 40%. All Pro and lifetime sales earn 40%, up to $150 each. The calculator below counts every product and every sale this way for you.'),
	p('Refunded sales earn nothing and leave the count, so later sales move up. Each month closes on its last day, and every count starts again on the 1st.'),
	p('Commission is always worked out on what the customer actually pays. First any SEMRS offer comes off the price: a seasonal sale such as Black Friday, Cyber Monday, Christmas, New Year or Halloween, a deal, or your partner code (one per order). Then the payment fee (10.5% + $0.50) comes off what is left, and you earn your rate on the rest. For example, All Pro for 1 website in a 30% sale: the customer pays $101.50, $90.34 is left after fees, and you earn 40% of that, $36.14.'),
	table('What you earn per sale: customer pays → you earn, and what you receive after the 7% exchange and transfer fee', ['Plan', 'No offer', '30% sale (e.g. New Year, Halloween)', 'Black Friday (45% off, lifetime 50%)'], [
		['All Pro, 1 website', '$145 → <strong>$51.71</strong><br><small>you receive $48.09</small>', '$101.50 → <strong>$36.14</strong><br><small>you receive $33.61</small>', '$79.75 → <strong>$28.35</strong><br><small>you receive $26.37</small>'],
		['All Pro, 3 websites', '$230 → <strong>$82.14</strong><br><small>you receive $76.39</small>', '$161 → <strong>$57.44</strong><br><small>you receive $53.42</small>', '$126.50 → <strong>$45.09</strong><br><small>you receive $41.93</small>'],
		['All Pro, 5 websites', '$299 → <strong>$106.84</strong><br><small>you receive $99.36</small>', '$209.30 → <strong>$74.73</strong><br><small>you receive $69.50</small>', '$164.45 → <strong>$58.67</strong><br><small>you receive $54.56</small>'],
		['All Pro, unlimited websites', '$499 → <strong>$150</strong><br><small>you receive $139.50</small>', '$349.30 → <strong>$124.85</strong><br><small>you receive $116.11</small>', '$274.45 → <strong>$98.05</strong><br><small>you receive $91.19</small>'],
		['Lifetime, 1 website', '$299 → <strong>$106.84</strong><br><small>you receive $99.36</small>', '$209.30 → <strong>$74.73</strong><br><small>you receive $69.50</small>', '$149.50 → <strong>$53.32</strong><br><small>you receive $49.59</small>'],
		['Lifetime, unlimited websites', '$695 → <strong>$150</strong><br><small>you receive $139.50</small>', '$486.50 → <strong>$150</strong><br><small>you receive $139.50</small>', '$347.50 → <strong>$124.21</strong><br><small>you receive $115.52</small>'],
		['SEO Pro, 1 website', '$42 → <strong>$14.84–$22.25</strong><br><small>you receive $13.80–$20.69</small>', '$29.40 → <strong>$10.33–$15.49</strong><br><small>you receive $9.61–$14.41</small>', '$23.10 → <strong>$8.07–$12.10</strong><br><small>you receive $7.51–$11.25</small>'],
		['Speed Pro, unlimited websites', '$229 → <strong>$81.78–$122.67</strong><br><small>you receive $76.06–$114.08</small>', '$160.30 → <strong>$57.19–$85.78</strong><br><small>you receive $53.19–$79.78</small>', '$125.95 → <strong>$44.89–$67.34</strong><br><small>you receive $41.75–$62.63</small>'],
	]),
	p('Add-on ranges run from 40% to 60%, by that add-on\'s monthly count. With the 25% agency deal on unlimited plans, All Pro unlimited costs the customer $374.25 and you earn $133.78. Renewals pay 15% of the renewal price the customer pays, after their 20% loyalty discount. "You receive" is after the 7% exchange and transfer fee, which comes off every payout.'),
	h2('Work out your earnings'),
	p('Type how many sales you expect in a month, and pick the SEMRS offer the customers get for each plan. The affiliate program calculator uses today\'s prices and the same formula as your dashboard, including the monthly bonus.'),
	'<!-- wp:shortcode -->[semrs_aff_calc]<!-- /wp:shortcode -->',
	p('Your private dashboard shows the real numbers: this month\'s sales, what you have earned so far, the count and next rate for each add-on, how many sales are left for the bonus, and last month\'s closed total.'),
	p('See every plan on the <a href="/semrs-pro/">SEMRS Pro pricing page</a>. Seasonal sales such as Black Friday bring more buyers, and you still earn your share of each sale.'),
	h2('Your partner code and monthly bonus'),
	p('Every partner gets a code, such as <code>JANEDOE</code>. It works without a click, so it suits videos, podcasts, live streams and chats. Your audience types it on the pricing page and saves 10%.'),
	ul([
		'<strong>Best price wins.</strong> During a sale, your code gives the sale price if it is bigger. One discount per order, and the sale is still yours.',
		'<strong>$50 bonus every month</strong> you reach 33 sales or more, counting every plan together. Paid once a month; months with fewer sales get no bonus. The count restarts on the 1st, and the bonus is paid once that month\'s refund window has passed.',
		'Your link and your code both show in your dashboard.',
	]),
	h2('Partners worldwide'),
	p('The affiliate program is open to partners in every country, except where sanctions apply. Share SEMRS in any language: the tools work on any WordPress site, and buyers pay in US dollars.'),
	ul([
		'<strong>International bank transfer</strong> to most countries, from $100.',
		'<strong>SadaPay or bank transfer in Pakistan</strong>, from $50.',
		'Payouts are in US dollars. Before each payout we deduct a 7% exchange and transfer fee.',
		'Your dashboard shows what you will receive after the fee.',
	]),
	h2('Who it suits'),
	p('The affiliate program suits people who already talk to WordPress users:'),
	ul([
		'Bloggers, YouTubers and podcasters who teach WordPress, SEO or site speed.',
		'Web designers and agencies who set up sites for clients.',
		'Course creators and community owners with a WordPress audience.',
		'Anyone already using SEMRS who wants to recommend it.',
	]),
	p('Our <a href="/tools/">free SEO tools</a> and the <a href="/wordpress-themes-plugins/">free themes and plugins</a> are easy first things to share. People who try them often upgrade to Pro later, and the 90-day cookie gives them time.'),
	h2('Rules and payouts'),
	p('A few simple rules keep the affiliate program fair for partners and customers:'),
	ul([
		'Say that you are an affiliate wherever you share your link or code. The <a href="https://www.ftc.gov/business-guidance/resources/ftcs-endorsement-guides-what-people-are-asking" rel="noopener" target="_blank">FTC endorsement guides</a> explain how.',
		'Do not bid on "SEMRS" or similar brand terms in paid search ads.',
		'No spam, no fake reviews, no coupon-listing sites, no fake or expired codes, and no misleading claims.',
		'Buying through your own link or code does not earn a commission.',
		'Commissions are approved 30 days after the sale, once the refund window has passed. Refunded orders earn nothing.',
		'We pay approved commissions monthly, by the methods above.',
	]),
	p('We can pause an account that breaks these rules. If anything is unclear, ask us first through the <a href="/contact-us/">contact page</a>.'),
	h2('Banners and creatives'),
	p('Every partner gets a ready-made kit in their dashboard: website banners in seven sizes, images for social posts, stories and video thumbnails, add-on cards and a partner badge. The code under each banner already carries your link, so you copy, paste and you are done.'),
	`<!-- wp:html --><figure class="semrs-aff-kit" style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;margin:16px 0"><img src="/wp-content/plugins/semrs-update-server/assets/promo/product/semrs-product-medium-rectangle-300x250.webp" width="300" height="250" alt="SEMRS Pro banner for partners, 300 by 250" loading="lazy" decoding="async" style="max-width:100%;height:auto;border-radius:10px"><img src="/wp-content/plugins/semrs-update-server/assets/promo/cards/semrs-card-all-pro.webp" width="250" height="250" alt="All Pro add-on card for social posts" loading="lazy" decoding="async" style="max-width:100%;height:auto;border-radius:10px"><img src="/wp-content/plugins/semrs-update-server/assets/promo/cards/semrs-affiliate-partner-badge.webp" width="160" height="160" alt="SEMRS affiliate partner badge" loading="lazy" decoding="async" style="max-width:100%;height:auto"></figure><!-- /wp:html -->`,
	h2('Apply to join'),
	p('Fill in the short form to join the affiliate program. Tell us where you plan to share SEMRS, your WhatsApp number, and the account where we should send your payouts. Already a partner? Use the second form to get your links and dashboard again.'),
	'<!-- wp:shortcode -->[semrs_affiliates]<!-- /wp:shortcode -->',
	h2('Frequently asked questions'),
	h3('Does it cost anything to join the affiliate program?'),
	p('No. Joining is free, and you do not need to buy SEMRS Pro first.'),
	h3('Can I join from any country?'),
	p('Yes, unless sanctions stop us from paying you. We pay by international bank transfer, or by SadaPay or bank transfer in Pakistan.'),
	h3('How long does the cookie last?'),
	p('Ninety days. If someone clicks your link and buys within 90 days, the sale is yours, unless they use another partner\'s link or code later.'),
	h3('What does my audience get?'),
	p('Ten percent off with your code, or the sale price when a sale gives more. Only one discount applies to each order.'),
	h3('Do I earn on renewals?'),
	p('Yes. You earn 15% of the renewal and of any add-on that customer buys during their first year. Renewal amounts already include the customer\'s 20% loyalty discount. Most programs pay nothing after the first order.'),
	h3('How is my commission worked out?'),
	p('On what SEMRS receives: the customer\'s payment after any discount, before tax and after the payment fee. That amount is multiplied by the rate for the sale\'s place in its add-on\'s monthly count. All Pro and lifetime plans pay 40%, up to $150 a sale.'),
	h3('When does a month close?'),
	p('On its last day. On the 1st we close the month: refunded sales drop out, each add-on\'s sales are counted again in order, and the $50 bonus is added if all your sales together reached 33. The new month starts from zero.'),
	h3('When and how do I get paid?'),
	p('Each commission is approved 30 days after the sale. We pay monthly in US dollars once you reach the minimum: $50 in Pakistan, $100 elsewhere. The 7% exchange and transfer fee comes off before sending, and we confirm your details by email before the first payout.'),
	h3('Can I share discount codes?'),
	p('Yes: your own partner code, and the seasonal codes we publish. Please do not make up codes or list expired ones.'),
].join('\n\n');

fs.writeFileSync(dir + 'page.html', html + '\n');
const words = html.replace(/<svg[\s\S]*?<\/svg>/g, ' ').replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length;
const kw = (html.replace(/<[^>]+>/g, ' ').toLowerCase().match(/affiliate program/g) || []).length;
console.log('words', words, 'keyword', kw);
