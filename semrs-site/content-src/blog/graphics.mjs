// Data graphics for the SEMRS blog articles, drawn as SVG (brand colors,
// cited figures only). build-images.mjs turns them into WebP files.
const W = 1200, H = 675;
const C = { blue: '#0b2fd1', navy: '#0a1650', orange: '#f7941d', orangeDk: '#b45309', ink: '#151b33', mute: '#5a6383', soft: '#eef1ff', warm: '#fff3e3', line: '#dfe3ef', bg: '#f8f9ff', green: '#0f7b4d' };
const F = "Segoe UI, Arial, Helvetica, sans-serif";
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

const frame = (title, sub, body, source) => `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
<rect width="${W}" height="${H}" fill="${C.bg}"/>
<rect width="${W}" height="10" fill="${C.blue}"/><rect width="260" height="10" fill="${C.orange}"/>
<text x="60" y="88" font-family="${F}" font-size="40" font-weight="700" fill="${C.ink}">${esc(title)}</text>
${sub ? `<text x="60" y="128" font-family="${F}" font-size="22" fill="${C.mute}">${esc(sub)}</text>` : ''}
${body}
<line x1="60" y1="${H - 52}" x2="${W - 60}" y2="${H - 52}" stroke="${C.line}" stroke-width="2"/>
<text x="60" y="${H - 22}" font-family="${F}" font-size="17" fill="${C.mute}">${esc(source)}</text>
<text x="${W - 60}" y="${H - 22}" text-anchor="end" font-family="${F}" font-size="18" font-weight="700" fill="${C.blue}">semrs.com</text>
</svg>`;

const wrap = (text, max) => { const out = []; let line = ''; for (const w of text.split(' ')) { if ((line + ' ' + w).trim().length > max) { out.push(line.trim()); line = w; } else line += ' ' + w; } if (line.trim()) out.push(line.trim()); return out; };
const tspans = (x, y, lines, size, color, weight = 400, lh = 1.3) => lines.map((l, i) => `<text x="${x}" y="${y + i * size * lh}" font-family="${F}" font-size="${size}" font-weight="${weight}" fill="${color}">${esc(l)}</text>`).join('');

// Numbered step cards in a grid.
function steps(title, sub, items, cols, source, accentLast) {
	const rows = Math.ceil(items.length / cols);
	const gx = 60, gy = 160, gw = W - 120, gh = H - 160 - 80, gap = 20;
	const cw = (gw - gap * (cols - 1)) / cols, ch = (gh - gap * (rows - 1)) / rows;
	const body = items.map(([h, d], i) => {
		const x = gx + (i % cols) * (cw + gap), y = gy + Math.floor(i / cols) * (ch + gap);
		const hot = accentLast && i === items.length - 1;
		return `<rect x="${x}" y="${y}" width="${cw}" height="${ch}" rx="16" fill="${hot ? C.warm : '#fff'}" stroke="${hot ? C.orange : C.line}" stroke-width="2"/>
<circle cx="${x + 42}" cy="${y + 44}" r="24" fill="${hot ? C.orange : C.blue}"/>
<text x="${x + 42}" y="${y + 52}" text-anchor="middle" font-family="${F}" font-size="22" font-weight="700" fill="#fff">${i + 1}</text>
${tspans(x + 80, y + 52, wrap(h, Math.floor(cw / 14.5)), 24, C.ink, 700)}
${tspans(x + 24, y + (ch > 200 ? 118 : 106), wrap(d, Math.floor(cw / 11.5)), ch > 200 ? 22 : 20, C.mute, 400, 1.35)}`;
	}).join('');
	return frame(title, sub, body, source);
}

// Horizontal bars.
function hbars(title, sub, rows, max, fmt, source, note) {
	const x0 = 380, x1 = W - 140, y0 = 175, bh = 64, gap = 30;
	const ticks = [0, 0.25, 0.5, 0.75, 1].map((t) => `<line x1="${x0 + (x1 - x0) * t}" y1="${y0 - 14}" x2="${x0 + (x1 - x0) * t}" y2="${y0 + rows.length * (bh + gap) - gap + 8}" stroke="${C.line}" stroke-width="1.5"/>
<text x="${x0 + (x1 - x0) * t}" y="${y0 + rows.length * (bh + gap) - gap + 34}" text-anchor="middle" font-family="${F}" font-size="16" fill="${C.mute}">${fmt(max * t)}</text>`).join('');
	const bars = rows.map(([label, v, hot], i) => {
		const y = y0 + i * (bh + gap), w = (x1 - x0) * (v / max);
		return `<text x="${x0 - 20}" y="${y + bh / 2 + 8}" text-anchor="end" font-family="${F}" font-size="22" font-weight="600" fill="${C.ink}">${esc(label)}</text>
<rect x="${x0}" y="${y}" width="${w}" height="${bh}" rx="8" fill="${hot ? C.orange : C.blue}"/>
<text x="${x0 + w + 12}" y="${y + bh / 2 + 8}" font-family="${F}" font-size="24" font-weight="700" fill="${hot ? C.orangeDk : C.blue}">${fmt(v)}</text>`;
	}).join('');
	const n = note ? `<text x="60" y="${H - 72}" font-family="${F}" font-size="19" fill="${C.ink}">${esc(note)}</text>` : '';
	return frame(title, sub, ticks + bars + n, source);
}

// Grouped vertical bars (two series).
function gbars(title, sub, groups, series, fmt, source) {
	const x0 = 140, x1 = W - 80, yb = H - 120, top = 190;
	const max = Math.max(...groups.flatMap((g) => g[1])) * 1.15;
	const gw = (x1 - x0) / groups.length;
	const bw = 120;
	let body = `<line x1="${x0}" y1="${yb}" x2="${x1}" y2="${yb}" stroke="${C.ink}" stroke-width="2"/>`;
	groups.forEach(([label, vals], gi) => {
		const cx = x0 + gw * gi + gw / 2;
		const gmax = Math.max(...vals) * 1.15;
		vals.forEach((v, si) => {
			const h = (yb - top) * (v / gmax), x = cx - bw - 10 + si * (bw + 20);
			body += `<rect x="${x}" y="${yb - h}" width="${bw}" height="${h}" rx="8" fill="${si ? C.orange : C.blue}"/>
<text x="${x + bw / 2}" y="${yb - h - 12}" text-anchor="middle" font-family="${F}" font-size="26" font-weight="700" fill="${si ? C.orangeDk : C.blue}">${fmt(v)}</text>`;
		});
		body += `<text x="${cx}" y="${yb + 36}" text-anchor="middle" font-family="${F}" font-size="22" font-weight="700" fill="${C.ink}">${esc(label)}</text>`;
	});
	body += series.map((s, i) => `<rect x="${W - 420 + i * 200}" y="150" width="22" height="22" rx="4" fill="${i ? C.orange : C.blue}"/><text x="${W - 390 + i * 200}" y="168" font-family="${F}" font-size="19" fill="${C.ink}">${esc(s)}</text>`).join('');
	return frame(title, sub, body, source);
}

// Two-column comparison table.
function compare(title, sub, heads, rows, source) {
	const x = 60, y = 160, w = W - 120, c0 = 300, rh = (H - 160 - 90) / (rows.length + 1);
	const cw = (w - c0) / 2;
	let b = `<rect x="${x}" y="${y}" width="${w}" height="${rh * (rows.length + 1)}" rx="14" fill="#fff" stroke="${C.line}" stroke-width="2"/>`;
	b += `<rect x="${x + c0}" y="${y}" width="${cw}" height="${rh}" fill="${C.blue}"/><rect x="${x + c0 + cw}" y="${y}" width="${cw}" height="${rh}" rx="0" fill="${C.orange}"/>`;
	b += heads.map((h, i) => `<text x="${x + c0 + cw * i + cw / 2}" y="${y + rh / 2 + 9}" text-anchor="middle" font-family="${F}" font-size="24" font-weight="700" fill="#fff">${esc(h)}</text>`).join('');
	rows.forEach(([k, a, bb], i) => {
		const yy = y + rh * (i + 1);
		if (i % 2 === 0) b += `<rect x="${x + 1}" y="${yy}" width="${w - 2}" height="${rh}" fill="${C.soft}"/>`;
		b += `<text x="${x + 24}" y="${yy + rh / 2 + 8}" font-family="${F}" font-size="21" font-weight="700" fill="${C.ink}">${esc(k)}</text>`;
		[a, bb].forEach((t, j) => { b += `<text x="${x + c0 + cw * j + cw / 2}" y="${yy + rh / 2 + 8}" text-anchor="middle" font-family="${F}" font-size="20" fill="${C.ink}">${esc(t)}</text>`; });
	});
	return frame(title, sub, b, source);
}

// Funnel / stages.
function funnel(title, sub, stages, source, note) {
	const y0 = 165, sh = 72, gap = 14, maxw = 1000, minw = 600;
	const b = stages.map(([label, val], i) => {
		const w = maxw - (maxw - minw) * (i / (stages.length - 1 || 1)), x = (W - w) / 2, y = y0 + i * (sh + gap);
		const hot = i === stages.length - 1;
		return `<rect x="${x}" y="${y}" width="${w}" height="${sh}" rx="12" fill="${hot ? C.orange : C.blue}" opacity="${hot ? 1 : 1 - i * 0.12}"/>
<text x="${x + 28}" y="${y + sh / 2 + 9}" font-family="${F}" font-size="24" font-weight="700" fill="#fff">${esc(label)}</text>
<text x="${x + w - 28}" y="${y + sh / 2 + 9}" text-anchor="end" font-family="${F}" font-size="24" font-weight="700" fill="#fff">${esc(val)}</text>`;
	}).join('');
	const n = note ? `<text x="${W / 2}" y="${H - 72}" text-anchor="middle" font-family="${F}" font-size="19" fill="${C.ink}">${esc(note)}</text>` : '';
	return frame(title, sub, b + n, source);
}

// Three overlapping circles.
function venn(title, sub, circles, center, source) {
	const cx = 600, r = 130;
	const pos = [[cx - 100, 320], [cx + 100, 320], [cx, 452]];
	const cols = [C.blue, C.orange, C.navy];
	let b = circles.map(([h], i) => `<circle cx="${pos[i][0]}" cy="${pos[i][1]}" r="${r}" fill="${cols[i]}" fill-opacity="0.16" stroke="${cols[i]}" stroke-width="3"/>`).join('');
	const lab = [[230, 280], [970, 280], [930, 480]];
	b += circles.map(([h, d], i) => `<text x="${lab[i][0]}" y="${lab[i][1]}" text-anchor="middle" font-family="${F}" font-size="28" font-weight="700" fill="${cols[i]}">${esc(h)}</text>${wrap(d, 22).map((l, k) => `<text x="${lab[i][0]}" y="${lab[i][1] + 30 + k * 24}" text-anchor="middle" font-family="${F}" font-size="18" fill="${C.ink}">${esc(l)}</text>`).join('')}`).join('');
	b += `<text x="${cx}" y="372" text-anchor="middle" font-family="${F}" font-size="20" font-weight="700" fill="${C.ink}">${esc(center)}</text>`;
	return frame(title, sub, b, source);
}

const SEMRS = 'Source: SEMRS guide';
export const GRAPHICS = {
	'small-business-loan-approval-by-lender': () => hbars('Where small businesses get fully approved', 'Share of loan applicants fully approved, by lender type', [['Small banks', 54, true], ['Large banks', 45], ['Online lenders', 30]], 60, (v) => Math.round(v) + '%', 'Source: Federal Reserve Small Business Credit Survey, 2025 Report on Employer Firms', 'Survey of 7,653 US employer firms, fielded September–November 2024.'),
	'email-marketing-platform-checklist': () => steps('How to choose an email marketing platform', 'Six checks before you commit to a platform', [
		['Set your goal', 'Newsletter, online store or service enquiries'], ['Count your list', 'Contacts and how often you send'], ['Check integrations', 'Website forms, CRM and online store'],
		['Test the editor', 'Build one real email and view it on a phone'], ['Price the next tier', 'What you pay at double your list size'], ['Plan your exit', 'Export contacts with their consent records'],
	], 3, SEMRS),
	'general-liability-insurance-cost-bands': () => hbars('What small businesses pay for general liability', 'Share of Insureon customers by monthly premium (median about $45 a month)', [['Under $30', 22], ['$30 to $60', 41, true], ['Over $60', 37]], 60, (v) => Math.round(v) + '%', 'Source: Insureon, general liability insurance cost, updated March 30, 2026', '"Over $60" is the remaining share. Your premium depends on your industry, location and limits.'),
	'financial-advisor-marketing-plan': () => steps('A 6-step financial advisor marketing plan', 'Each step builds on the one before; compliance review runs through all of them', [
		['Pick a niche', 'One audience you can describe in a sentence'], ['Answer-first website', 'Niche pages, fees, process and credentials'], ['Local SEO and content', 'Business Profile plus one helpful article a week'],
		['Google Ads and leads', 'Tight niche keywords and a matching landing page'], ['Referrals and LinkedIn', 'CPAs, estate attorneys and useful posts'], ['Review and archive', 'Compliance approval and records for every piece'],
	], 3, SEMRS + ' · SEC Marketing Rule 206(4)-1 and FINRA Rule 2210 apply', true),
	'insurance-agency-90-day-marketing-plan': () => steps('A 90-day insurance agency marketing plan', 'Quick wins first, then paid channels, then lead sources you own', [
		['Weeks 1–4: foundations', 'Business Profile, review requests, quote path on every page, call and form tracking'],
		['Weeks 5–8: paid search', 'Google Ads on two products, negative keywords, one niche landing page'],
		['Weeks 9–12: own your leads', 'Weekly coverage articles, referral partners, renewal and review emails'],
	], 3, SEMRS + ' · check state insurance rules and the TCPA before calling or texting'),
	'google-ads-legal-cpc-cpl-2025': () => gbars('Legal clicks and leads cost far more', 'Google Ads averages in the 2025 benchmarks (US campaigns)', [['Cost per click', [5.26, 8.58]], ['Cost per lead', [70.11, 131.63]]], ['All industries', 'Attorneys & legal'], (v) => '$' + v.toFixed(2), 'Source: WordStream by LocaliQ 2025 Google Ads benchmarks (Apr 2024–Mar 2025 data)'),
	'seo-retainer-cost-by-provider': () => hbars('Average monthly SEO retainer by provider', 'Self-reported averages from an industry survey of SEO providers', [['Freelancers', 1348], ['Local SEO', 1557], ['Agencies', 3209, true], ['Consultancies', 3250]], 3600, (v) => '$' + Math.round(v).toLocaleString('en-US'), 'Source: Ahrefs SEO pricing survey', 'Most common single price band: $501–$1,000 a month. 78% of providers charge a monthly retainer.'),
	'google-ads-budget-to-customers': () => funnel('How a Google Ads budget turns into customers', 'Worked example from this guide, not a forecast', [['Monthly budget', '$500'], ['Clicks at $5 each', '100'], ['Leads at a 5% conversion rate', '5'], ['Customers at a 25% close rate', '≈ 1']], 'Example only: your CPC, conversion rate and close rate will differ', 'If one customer is worth more than $500 to you, this campaign is profitable.'),
	'digital-marketing-pricing-models': () => steps('How digital marketing agencies charge', 'Compare quotes by what is delivered each month, not the headline price', [
		['Monthly retainer', 'Ongoing SEO, content or social work for a fixed fee'], ['Project fee', 'A defined deliverable: a website, an audit, a migration'],
		['Hourly', 'Consulting, training or ad-hoc tasks'], ['Percent of ad spend', 'Common for paid ads; check incentives to overspend'],
	], 2, SEMRS),
	'local-services-ads-vs-google-ads-comparison': () => compare('Local Services Ads vs Google Ads', 'Two ways to pay Google for local leads', ['Local Services Ads', 'Google Ads (search)'], [
		['You pay for', 'Leads (calls, messages)', 'Clicks'], ['Placement', 'Top of eligible searches', 'Top and bottom of results'], ['Badge', 'Google Guaranteed / Screened', 'None'],
		['Keyword control', 'Limited', 'Full'], ['Landing page', 'Your LSA profile', 'Any page on your site'], ['Best for', 'Phone-driven local services', 'Any business, precise targeting'],
	], SEMRS + ' · availability depends on category and location'),
	'wcag-pour-principles': () => steps('The four WCAG principles of accessibility', 'Most accessibility fixes map to one of these', [
		['Perceivable', 'Alt text, captions, enough color contrast'], ['Operable', 'Works with a keyboard, visible focus, no traps'],
		['Understandable', 'Clear language, labeled forms, helpful errors'], ['Robust', 'Clean code that works with screen readers'],
	], 2, 'Source: W3C Web Content Accessibility Guidelines (WCAG) 2.2'),
	'ai-content-workflow-human-approval': () => steps('A safe AI content workflow', 'AI drafts; people decide what is true and what gets published', [
		['Brief by a person', 'Audience, goal, keyword, must-have points'], ['AI outline and draft', 'Speed on structure and first wording'], ['Expert fact check', 'Sources, examples, removes filler'],
		['Editor review', 'Voice, links, claims, compliance'], ['Human approval', 'A person signs off before publishing'], ['Measure and update', 'Search Console data, refresh yearly'],
	], 3, SEMRS + ' · how SEMRS OS runs two human approval gates on every job', true),
	'crm-sales-pipeline-stages': () => funnel('A simple small business sales pipeline', 'Set your CRM stages to match how you really sell', [['New lead', 'from forms, calls, ads'], ['Contacted', 'within minutes'], ['Qualified', 'need, budget, timing'], ['Proposal sent', 'quote or estimate'], ['Won', 'customer']], SEMRS, 'Track the source of every lead so you can see which marketing brings revenue.'),
	'google-local-ranking-factors': () => venn('How Google ranks local businesses', 'Google names three main factors for local results', [['Relevance', 'Categories, services and content that match the search'], ['Distance', 'How close you are to the searcher; not something you control'], ['Prominence', 'Reviews, links, mentions and how well known you are']], 'Map pack', 'Source: Google Business Profile help, "How to improve your local ranking on Google"'),
	'seo-for-small-business-roadmap': () => steps('SEO for small business: a 9-step roadmap', 'Work through the steps in order; results compound over months', [
		['Find searches', 'Service, question, comparison'], ['Service pages', 'One topic per page'], ['On-page basics', 'Titles, headings, links'],
		['Local SEO', 'Profile, reviews, citations'], ['Technical SEO', 'Speed, mobile, sitemap'], ['Helpful content', 'Answer real questions'],
		['Earn links', 'Partners, press, resources'], ['AI search', 'Clear answers, schema'], ['Measure', 'Leads, not just rankings'],
	], 3, SEMRS, true),
};
