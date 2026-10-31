// Diagrams for the SEMRS user manual, drawn as SVG in the brand colors.
// Big text and few words, so they stay readable on phones.
// The render script turns them into 1200×675 WebP files in pages/support/images/.
const W = 1200, H = 675;
const C = { blue: '#0b2fd1', navy: '#0a1650', orange: '#f7941d', orangeDk: '#b45309', ink: '#151b33', mute: '#4a5373', soft: '#eef1ff', warm: '#fff3e3', line: '#d5dbee', bg: '#f8f9ff', green: '#0f7b4d', greenBg: '#e6f6ee', red: '#b42318', redBg: '#fdecea', amber: '#b26200', amberBg: '#fff4e0' };
const F = 'Segoe UI, Arial, Helvetica, sans-serif';
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

const frame = (title, body) => `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
<defs><marker id="ar" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0 0L10 5L0 10z" fill="${C.orange}"/></marker></defs>
<rect width="${W}" height="${H}" fill="${C.bg}"/>
<rect width="${W}" height="12" fill="${C.blue}"/><rect width="280" height="12" fill="${C.orange}"/>
<text x="60" y="92" font-family="${F}" font-size="44" font-weight="700" fill="${C.ink}">${esc(title)}</text>
${body}
<text x="${W - 60}" y="${H - 28}" text-anchor="end" font-family="${F}" font-size="22" font-weight="700" fill="${C.blue}">SEMRS</text>
</svg>`;

const wrap = (text, max) => { const out = []; let line = ''; for (const w of String(text).split(' ')) { if ((line + ' ' + w).trim().length > max) { out.push(line.trim()); line = w; } else line += ' ' + w; } if (line.trim()) out.push(line.trim()); return out; };
const lines = (x, y, arr, size, color, weight = 400, anchor = 'start', lh = 1.25) => arr.map((l, i) => `<text x="${x}" y="${y + i * size * lh}" text-anchor="${anchor}" font-family="${F}" font-size="${size}" font-weight="${weight}" fill="${color}">${esc(l)}</text>`).join('');
const box = (x, y, w, h, fill = '#fff', stroke = C.line, r = 18) => `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="3"/>`;
const arrow = (x1, y1, x2, y2) => `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${C.orange}" stroke-width="6" marker-end="url(#ar)"/>`;
const num = (cx, cy, n, fill = C.blue) => `<circle cx="${cx}" cy="${cy}" r="28" fill="${fill}"/><text x="${cx}" y="${cy + 10}" text-anchor="middle" font-family="${F}" font-size="28" font-weight="700" fill="#fff">${n}</text>`;

// Steps in a row joined by arrows: [[heading, short text], …] (3 to 5 steps).
function flow(title, items) {
	const n = items.length, gap = 46, x0 = 60, y = 170, h = 400;
	const w = (W - 120 - gap * (n - 1)) / n;
	const fs = n > 4 ? 25 : 28;
	return frame(title, items.map(([head, text], i) => {
		const x = x0 + i * (w + gap), last = i === n - 1;
		return box(x, y, w, h, last ? C.warm : '#fff', last ? C.orange : C.line) + num(x + w / 2, y + 60, i + 1, last ? C.orange : C.blue) +
			lines(x + w / 2, y + 140, wrap(head, Math.floor(w / 16)), fs + 2, C.ink, 700, 'middle') +
			lines(x + w / 2, y + 250, wrap(text, Math.floor(w / 13.5)), fs - 4, C.mute, 400, 'middle') +
			(last ? '' : arrow(x + w + 4, y + h / 2, x + w + gap - 6, y + h / 2));
	}).join(''));
}

// Numbered cards in a grid: [[heading, short text], …].
function cards(title, items, cols) {
	const rows = Math.ceil(items.length / cols), gap = 24, x0 = 60, y0 = 140;
	const w = (W - 120 - gap * (cols - 1)) / cols, h = (H - y0 - 70 - gap * (rows - 1)) / rows;
	return frame(title, items.map(([head, text, tag], i) => {
		const x = x0 + (i % cols) * (w + gap), y = y0 + Math.floor(i / cols) * (h + gap);
		const pro = tag === 'pro';
		return box(x, y, w, h, pro ? C.warm : '#fff', pro ? C.orange : C.line) +
			(tag ? `<rect x="${x + w - 92}" y="${y + 18}" width="72" height="32" rx="16" fill="${pro ? C.orange : C.green}"/><text x="${x + w - 56}" y="${y + 41}" text-anchor="middle" font-family="${F}" font-size="18" font-weight="700" fill="${pro ? C.navy : '#fff'}">${pro ? 'PRO' : 'FREE'}</text>` : '') +
			lines(x + 26, y + 50, wrap(head, Math.floor((w - 110) / 15)), 28, C.ink, 700) +
			lines(x + 26, y + (h > 180 ? 110 : 96), wrap(text, Math.floor((w - 40) / 12.5)), 23, C.mute);
	}).join(''));
}

// Two columns: do and don't.
function doDont(title, dos, donts) {
	const col = (x, head, items, ok) => box(x, 140, 525, 465, ok ? C.greenBg : C.redBg, ok ? C.green : C.red) +
		`<text x="${x + 30}" y="195" font-family="${F}" font-size="32" font-weight="700" fill="${ok ? C.green : C.red}">${esc(head)}</text>` +
		items.map((t, i) => {
			const y = 250 + i * 70;
			const mark = ok
				? `<path d="M${x + 32} ${y - 8} l12 12 l22 -24" stroke="${C.green}" stroke-width="7" fill="none" stroke-linecap="round"/>`
				: `<path d="M${x + 34} ${y - 22} l26 26 M${x + 60} ${y - 22} l-26 26" stroke="${C.red}" stroke-width="7" stroke-linecap="round"/>`;
			return mark + lines(x + 84, y + 2, wrap(t, 30), 25, C.ink).replace(/<\/text><text/, '</text><text');
		}).join('');
	return frame(title, col(60, 'Do', dos, true) + col(615, 'Don\'t', donts, false));
}

// The SEMRS products stacked: Pro on top, plugins in the middle, theme at the base.
function productStack() {
	const layer = (y, h, fill, stroke, items) => {
		const w = (W - 120 - 20 * (items.length - 1)) / items.length;
		return items.map(([head, text, dark], i) => {
			const x = 60 + i * (w + 20);
			return box(x, y, w, h, fill, stroke) + lines(x + w / 2, y + 50, [head], 30, dark ? '#fff' : C.ink, 700, 'middle') + lines(x + w / 2, y + 88, wrap(text, Math.floor(w / 12)), 22, dark ? '#dfe5ff' : C.mute, 400, 'middle');
		}).join('');
	};
	return frame('How the SEMRS products fit together', [
		layer(130, 130, C.warm, C.orange, [['SEMRS Pro (paid add-on)', 'Unlocks Pro features in everything below']]),
		layer(285, 150, '#fff', C.line, [['SEMRS Core', 'SEO, speed and ads'], ['SEMRS Blocks', 'Extra blocks and templates'], ['SEMRS Tools', 'Free SEO tools for visitors']]),
		layer(460, 140, C.blue, C.blue, [['SEMRS theme', 'The look of your site: header, footer, colors, layout. Free.', true]]),
	].join(''));
}

// Header builder: three rows, three zones each.
function headerRows() {
	const rows = [['Top bar', ['Phone & email', '', 'Social icons']], ['Main header', ['Logo', 'Primary menu', 'Button']], ['Below header', ['', 'Secondary menu', '']]];
	const zw = 270, x0 = 290;
	let body = ['Left', 'Center', 'Right'].map((z, i) => lines(x0 + i * (zw + 20) + zw / 2, 160, [z], 26, C.mute, 700, 'middle')).join('');
	body += rows.map(([name, zones], r) => {
		const y = 185 + r * 140;
		return lines(60, y + 70, [name], 28, C.ink, 700) + zones.map((el, i) => {
			const x = x0 + i * (zw + 20);
			return `<rect x="${x}" y="${y}" width="${zw}" height="110" rx="16" fill="${el ? '#fff' : 'none'}" stroke="${el ? C.blue : C.line}" stroke-width="3" stroke-dasharray="${el ? '' : '10 8'}"/>` +
				(el ? `<rect x="${x + 18}" y="${y + 30}" width="${zw - 36}" height="50" rx="25" fill="${C.soft}"/>` + lines(x + zw / 2, y + 64, [el], 25, C.blue, 700, 'middle') : lines(x + zw / 2, y + 64, ['empty'], 22, C.mute, 400, 'middle'));
		}).join('');
	}).join('');
	body += lines(60, 630, ['Drag an item to any zone. An empty row is not shown on your site.'], 24, C.mute);
	return frame('The header builder: 3 rows × 3 zones', body);
}

// Article mock-up with the ad positions.
function adPositions() {
	const x = 300, w = 600;
	const para = (y) => [0, 1, 2].map((i) => `<rect x="${x + 30}" y="${y + i * 22}" width="${i === 2 ? w - 200 : w - 60}" height="12" rx="6" fill="${C.line}"/>`).join('');
	const ad = (y, label, n) => `<rect x="${x + 30}" y="${y}" width="${w - 60}" height="44" rx="10" fill="${C.warm}" stroke="${C.orange}" stroke-width="3" stroke-dasharray="8 6"/>` + lines(x + w / 2, y + 30, ['Ad: ' + label], 22, C.orangeDk, 700, 'middle') + num(x + w + 60, y + 22, n, C.orange);
	let body = box(x, 130, w, 490, '#fff', C.line) + `<rect x="${x + 30}" y="160" width="380" height="26" rx="8" fill="${C.ink}"/>`;
	body += ad(205, 'before content', 1) + para(270) + ad(345, 'after paragraph 2', 2) + para(410) + ad(480, 'middle of the article', 3) + para(540) + ad(600 - 20, 'end of content', 4);
	body += lines(60, 220, ['Positions', 'you can', 'switch on'], 28, C.ink, 700) + lines(60, 340, ['Ads go only', 'between normal', 'paragraphs.'], 23, C.mute);
	body += lines(1040, 220, ['Never on'], 26, C.red, 700) + lines(1040, 258, ['contact', 'pricing', 'thank-you', 'privacy', 'terms'], 22, C.mute, 400, 'start', 1.35);
	return frame('Where SEMRS can place your ads', body);
}

// Page cache: first visit builds the page, later visits get the saved copy.
function cacheFlow() {
	const node = (x, y, w, head, text, fill, stroke) => box(x, y, w, 120, fill, stroke) + lines(x + w / 2, y + 50, [head], 28, C.ink, 700, 'middle') + lines(x + w / 2, y + 88, [text], 22, C.mute, 400, 'middle');
	let body = lines(60, 175, ['First visit'], 28, C.blue, 700) + lines(60, 425, ['Next visits'], 28, C.green, 700);
	body += node(60, 195, 280, 'Visitor', 'opens a page', '#fff', C.line) + arrow(345, 255, 420, 255) +
		node(430, 195, 330, 'WordPress', 'builds the page (slow)', '#fff', C.line) + arrow(765, 255, 840, 255) +
		node(850, 195, 290, 'Saved copy', 'kept in the cache', C.warm, C.orange);
	body += node(60, 445, 280, 'Visitor', 'opens a page', '#fff', C.line) + arrow(345, 505, 840, 505) +
		lines(592, 485, ['skips WordPress'], 24, C.green, 700, 'middle') +
		node(850, 445, 290, 'Saved copy', 'sent at once (fast)', C.greenBg, C.green);
	body += lines(60, 630, ['The cache clears itself when you publish or change your site.'], 24, C.mute);
	return frame('How the page cache makes pages fast', body);
}

// SEO score colors and the three check groups.
function seoScore() {
	const band = (x, w, fill, label, range) => `<rect x="${x}" y="160" width="${w}" height="90" fill="${fill}"/>` + lines(x + w / 2, 205, [label], 30, '#fff', 700, 'middle') + lines(x + w / 2, 238, [range], 22, '#fff', 400, 'middle');
	let body = `<g>${band(60, 540, C.red, 'Needs work', '0 to 49')}${band(600, 330, C.amber, 'OK', '50 to 79')}${band(930, 210, C.green, 'Good', '80 to 100')}</g>`;
	const groups = [['Basic SEO', 'Keyword in the title, description, address and first lines'], ['Content', 'Enough words, links, image alt text, subheadings'], ['Readability', 'Short sentences and short paragraphs']];
	body += groups.map(([h, t], i) => { const x = 60 + i * 370; return box(x, 300, 350, 250, '#fff', C.line) + num(x + 50, 355, i + 1) + lines(x + 95, 366, [h], 28, C.ink, 700) + lines(x + 30, 430, wrap(t, 24), 24, C.mute); }).join('');
	body += lines(60, 610, ['Aim for green. Click Re-check to see the score before you publish.'], 24, C.mute);
	return frame('The SEO score in the editor', body);
}

// Policies: four areas.
function fourPillars(title, items) {
	const w = 255, gap = 20;
	return frame(title, items.map(([head, text, color], i) => {
		const x = 60 + i * (w + gap);
		return box(x, 140, w, 460, '#fff', color) + `<rect x="${x}" y="140" width="${w}" height="90" rx="18" fill="${color}"/><rect x="${x}" y="200" width="${w}" height="30" fill="${color}"/>` +
			lines(x + w / 2, 197, [head], 28, '#fff', 700, 'middle') + lines(x + 24, 285, wrap(text, 16), 27, C.ink, 400, 'start', 1.45);
	}).join(''));
}

// A short loop: change → test → keep or undo.
function testLoop() {
	const cx = 600, cy = 380, r = 190;
	const pts = [['1. Change', 'one setting'], ['2. Test', 'in PageSpeed'], ['3. Keep it', 'if faster'], ['4. Undo it', 'if something breaks']];
	let body = `<ellipse cx="${cx}" cy="${cy}" rx="330" ry="${r}" fill="none" stroke="${C.orange}" stroke-width="6" stroke-dasharray="18 12"/>`;
	body += pts.map(([h, t], i) => {
		const a = -Math.PI / 2 + i * Math.PI / 2, x = cx + Math.cos(a) * 330, y = cy + Math.sin(a) * r;
		return box(x - 150, y - 55, 300, 110, i === 3 ? C.redBg : i === 2 ? C.greenBg : '#fff', i === 3 ? C.red : i === 2 ? C.green : C.blue) +
			lines(x, y - 6, [h], 28, C.ink, 700, 'middle') + lines(x, y + 30, [t], 22, C.mute, 400, 'middle');
	}).join('');
	body += lines(cx, cy + 10, ['Repeat'], 30, C.orangeDk, 700, 'middle');
	return frame('Test speed settings one at a time', body);
}

export const GRAPHICS = {
	'product-stack': { alt: 'Diagram: the SEMRS theme at the base, SEMRS Core, Blocks and Tools plugins in the middle, SEMRS Pro on top', svg: productStack },
	'install-steps': { alt: 'SEMRS support steps: install the theme, add Core and Blocks, switch on features, add Pro if you need it', svg: () => flow('Set up SEMRS in 4 steps', [['Install the theme', 'Appearance → Themes → search SEMRS'], ['Add the plugins', 'SEMRS Core, then SEMRS Blocks'], ['Pick features', 'SEMRS → Features: switch on what you need'], ['Add Pro (optional)', 'Upload SEMRS Pro and paste your key']]) },
	'free-vs-pro': { alt: 'Free or Pro: free features never expire, Pro features show a gold PRO badge until SEMRS Pro is active', svg: () => cards('Free or Pro: how to tell', [['Free features', 'Work forever. No key, no time limit.', 'free'], ['Pro features', 'Show a gold PRO badge and a lock until SEMRS Pro is active.', 'pro'], ['Try before you buy', 'You can insert Pro blocks. They show nothing on your site yet.'], ['Hide Pro items', 'Untick "Show SEMRS Pro blocks" in SEMRS → Blocks.']], 2) },
	'theme-customizer': { alt: 'SEMRS theme settings path: Appearance, Customize, SEMRS Theme, change a setting, Publish', svg: () => flow('Where SEMRS theme settings live', [['Appearance', 'In the dashboard menu'], ['Customize', 'Opens a live preview'], ['SEMRS Theme', 'All theme options'], ['Publish', 'Nothing goes live until you click it']]) },
	'theme-header-rows': { alt: 'SEMRS theme header builder: top bar, main header and below header rows, each with left, center and right zones', svg: headerRows },
	'theme-page-options': { alt: 'SEMRS theme page options for one page: hide title, hide header or footer, transparent header, sidebar', svg: () => cards('Page options: change one page only', [['Hide the title', 'Your content then needs its own main heading.'], ['Hide header or footer', 'Good for landing pages.'], ['Transparent header', 'Default, On or Off for this page.'], ['Sidebar', 'Default, right, left or none.']], 2) },
	'blocks-map': { alt: 'SEMRS Blocks map: free blocks such as container, tabs, accordion, contact form, and Pro blocks such as slider and popups', svg: () => cards('SEMRS Blocks at a glance', [['Layout', 'Container, tabs, accordion, gallery', 'free'], ['Content', 'Icon, icon list, fancy heading, cards', 'free'], ['Sell', 'Pricing card, countdown, contact form', 'free'], ['Show posts', 'Posts grid, table of contents', 'free'], ['Move', 'Slider, counter, scroll animation', 'pro'], ['Control', 'Display conditions, popups, 3D', 'pro']], 3) },
	'blocks-contact-flow': { alt: 'SEMRS Blocks contact form flow: visitor sends the form, spam checks run, the message is saved in Leads and emailed to you', svg: () => flow('What happens when someone sends your form', [['Visitor sends it', 'Name, email, phone, message'], ['Spam check', 'Hidden field, time check, limits'], ['Saved in Leads', 'In your dashboard, always'], ['Email to you', 'Reply goes straight to the visitor']]) },
	'templates-import': { alt: 'SEMRS templates import in 5 steps: choose a site, add your details, drafts or publish, keep the boxes ticked, import', svg: () => flow('Import a starter site in 5 steps', [['Choose a site', 'SEMRS → Templates'], ['Add your details', 'Name, phone, email, city'], ['Drafts or live', 'Drafts are safest'], ['Keep boxes ticked', 'Menu, home page, header'], ['Import', 'You get a list of new pages']]) },
	'templates-what-you-get': { alt: 'What one SEMRS templates import creates: pages, menu, home page, header, contact form and sample posts', svg: () => cards('What one import gives you', [['Pages', 'Home, About, Services, Blog, Contact'], ['Menu', 'Main menu and footer links'], ['Home page', 'Set as your front page'], ['Header', 'Layout and your contact details'], ['Contact form', 'Messages go to your email'], ['Safe', 'Your old pages are never deleted']], 3) },
	'seo-score': { alt: 'SEMRS SEO score colors: red under 50, orange 50 to 79, green 80 and up, with basic, content and readability checks', svg: seoScore },
	'seo-setup': { alt: 'SEMRS SEO setup in 4 steps: switch on SEO, fill in site details, write each page title and description, check the score', svg: () => flow('Set up SEMRS SEO in 4 steps', [['Switch it on', 'SEMRS → Features → SEO'], ['Site details', 'Name, logo, address, profiles'], ['Each page', 'Keyword, title, description'], ['Check the score', 'Aim for green (80+)']]) },
	'seo-switch': { alt: 'Switch to SEMRS SEO from another SEO plugin: install Core, check pages, turn off the old plugin, resubmit the sitemap', svg: () => flow('Moving from another SEO plugin', [['Install Core', 'Your old titles are read for you'], ['Check pages', 'Look at the SEMRS SEO box'], ['Turn off old plugin', 'Plugins → Deactivate'], ['Resubmit sitemap', 'In Google Search Console']]) },
	'ads-positions': { alt: 'SEMRS ads positions in an article: before content, after paragraph 2, middle and end, never on contact or legal pages', svg: adPositions },
	'ads-do-dont': { alt: 'SEMRS ads and AdSense rules: do label ads and keep ads.txt; do not click your own ads or place ads that look like menus', svg: () => doDont('AdSense rules in one picture', ['Label every ad', 'Keep ads.txt up to date', 'Have a privacy policy', 'Use a consent tool in the EU and UK'], ['Click your own ads', 'Ask people to click ads', 'Make ads look like menus', 'Put ads on empty pages']) },
	'speed-cache': { alt: 'SEMRS speed page cache: the first visit builds the page, next visits get the saved copy at once', svg: cacheFlow },
	'speed-test-loop': { alt: 'SEMRS speed testing loop: change one setting, test in PageSpeed, keep it if faster, undo it if something breaks', svg: testLoop },
	'speed-tabs': { alt: 'The four SEMRS speed tabs: Optimize, Page cache, Database and Speed check', svg: () => cards('The 4 tabs in SEMRS → Speed', [['Optimize', 'Removes extra code. Safe options are already on.'], ['Page cache', 'Saves pages so they open fast. Off until you tick it.'], ['Database', 'Cleans old drafts, spam and leftovers.'], ['Speed check', 'Tests your home page and lists simple fixes.']], 2) },
	'pro-license-steps': { alt: 'SEMRS Pro license steps: buy, download the zip, upload the plugin, paste the key, features unlock', svg: () => flow('Activate SEMRS Pro in 5 steps', [['Buy', 'Choose a plan'], ['Download', 'semrs-pro.zip from your email'], ['Upload', 'Plugins → Upload Plugin'], ['Paste the key', 'SEMRS → License'], ['Done', 'Pro features unlock at once']]) },
	'pro-license-move': { alt: 'Move a SEMRS Pro license key: deactivate on the old site, then activate on the new site; test sites are free', svg: () => flow('Move your key to a new site', [['Old site', 'SEMRS → License → Deactivate'], ['Slot is free', 'The key can be used again'], ['New site', 'Paste the key → Activate']]) },
	'policies-pillars': { alt: 'SEMRS policies in four areas: Google Search, Google AdSense, WordPress.org and privacy', svg: () => fourPillars('The 4 rule books SEMRS follows', [['Google Search', 'Clean pages, honest schema, fast loading, no hidden text', C.blue], ['AdSense', 'Ads labeled, kept off empty, legal and contact pages', C.orangeDk], ['WordPress.org', 'GPL code, no locked code, no tracking in free versions', C.navy], ['Privacy', 'Store little, say what you store, delete what you don\'t need', C.green]]) },
	'policies-your-part': { alt: 'SEMRS policies: your part as site owner, write helpful content, update the privacy policy, keep consent on, never click your ads', svg: () => doDont('Your part as the site owner', ['Write helpful, original content', 'Explain forms and ads in your privacy policy', 'Keep the consent box on forms', 'Delete old leads you don\'t need'], ['Click your own ads', 'Add fake reviews or ratings', 'Copy other people\'s content', 'Hide text or links']) },
};
