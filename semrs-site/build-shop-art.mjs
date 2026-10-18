// Build original listing images for the shop templates
// (plugins/semrs-shops/catalogues/{digital,crochet}.json): a themed motif on a
// paper card with a label and a ribbon. Writes SVG sources to
// art-src/ and 640×640 JPEGs to images/{catalogue}-{key}.jpg in SEMRS Shops,
// with headless Chrome.
//
// Run: node build-shop-art.mjs   (from semrs-site/, Windows + Chrome)

import fs from 'fs';
import os from 'os';
import path from 'path';
import { execFileSync } from 'child_process';

const BASE = 'plugins/semrs-shops';
const CATALOGUES = { digital: 'DIGITAL DOWNLOAD', crochet: 'PDF PATTERN' };
const ICONS = Object.fromEntries([...fs.readFileSync('plugins/semrs-blocks/includes/icons.php', 'utf8').matchAll(/'([a-z0-9-]+)'\s*=>\s*'([^']*)'/g)].map((m) => [m[1], m[2]]));

const MOTIFS = {
	tree: '<path d="M12 2l5 7h-3l4 6h-4l3 5H7l3-5H6l4-6H7z"/><path d="M12 20v2"/>',
	ornament: '<circle cx="12" cy="14" r="7"/><path d="M10 5h4v2h-4z"/><path d="M5.5 12.5c3 2 10 2 13 0"/><path d="M12 3v2"/>',
	tag: '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
	card: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
	frame: '<rect x="3" y="3" width="18" height="18" rx="1"/><rect x="6" y="6" width="12" height="12"/><path d="M6 15l4-4 3 3 2-2 3 3"/>',
	yarn: '<circle cx="11" cy="12" r="7"/><path d="M5 9c4 1 9 5 11 9"/><path d="M7 5c3 3 6 9 6 14"/><path d="M13 5c-1 3-1 9 2 13"/><path d="M17 17l5 3"/>',
	hanging: '<path d="M4 4h16"/><path d="M6 4v6l6 8 6-8V4"/><path d="M9 18v3M12 19v3M15 18v3"/>',
	portrait: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="10" r="2"/><circle cx="15" cy="10" r="2"/><path d="M5.5 18c0-2.5 2-4 3.5-4s3.5 1.5 3.5 4"/><path d="M11.5 18c0-2.5 2-4 3.5-4s3.5 1.5 3.5 4"/>',
	paw: '<path d="M12 13c-3 0-5 2.5-5 4.5S9 20 12 20s5-.5 5-2.5S15 13 12 13z"/><circle cx="6.5" cy="10" r="1.8"/><circle cx="9.8" cy="6.4" r="1.8"/><circle cx="14.2" cy="6.4" r="1.8"/><circle cx="17.5" cy="10" r="1.8"/>',
	letters: '<path d="M3 18L7 6l4 12"/><path d="M4.5 14h5"/><path d="M14 6v12h3.5a3 3 0 0 0 0-6H14h3a3 3 0 0 0 0-6z"/>',
	shirt: '<path d="M8 3l-5 3 2 4 2-1v12h10V9l2 1 2-4-5-3c-1 2-2.5 3-4 3s-3-1-4-3z"/>',
	mug: '<path d="M4 7h12v9a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M16 9h2a3 3 0 0 1 0 6h-2"/><path d="M8 3v2M12 3v2"/>',
	crayon: '<path d="M4 20l3-1 12-12-2-2L5 17z"/><path d="M15 5l4 4"/><path d="M4 20l1-3"/>',
	numbers: '<path d="M5 6l2-1v8"/><path d="M10 6.5a2 2 0 0 1 4 0c0 2-4 3-4 6.5h4"/><path d="M16 5h3l-2 3a2.5 2.5 0 1 1-1.5 4.5"/><path d="M4 18h16"/>',
	book: '<path d="M4 4h6a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H4z"/><path d="M20 4h-6a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h6z"/>',
	planner: '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3v18"/><path d="M12 8h4M12 12h4M12 16h3"/>',
	sheet: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M3 14h18M9 4v16M15 4v16"/>',
	calendar: ICONS.calendar,
	rainbow: '<path d="M3 18a9 9 0 0 1 18 0"/><path d="M6.5 18a5.5 5.5 0 0 1 11 0"/><path d="M10 18a2 2 0 0 1 4 0"/>',
	rings: '<circle cx="9" cy="14" r="5"/><circle cx="15" cy="14" r="5"/><path d="M12 3l1.5 2.5L12 8l-1.5-2.5z"/>',
	phone: '<rect x="7" y="2" width="10" height="20" rx="2"/><rect x="9" y="5" width="6" height="6"/><path d="M9 14h6M9 17h4"/>',
	blocks: '<rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><path d="M14 17h6M17 14v6"/>',
	sticker: '<path d="M4 4h16v10l-6 6H4z"/><path d="M14 20v-6h6"/><circle cx="9" cy="10" r="1"/><circle cx="15" cy="10" r="1"/><path d="M9 14c1.5 1 4.5 1 6 0"/>',
	bear: '<circle cx="12" cy="13" r="7"/><circle cx="6" cy="6.5" r="2.5"/><circle cx="18" cy="6.5" r="2.5"/><circle cx="9.5" cy="12" r=".8"/><circle cx="14.5" cy="12" r=".8"/><ellipse cx="12" cy="15.5" rx="2.6" ry="2"/><path d="M12 15v1"/>',
	bunny: '<circle cx="12" cy="15" r="6"/><path d="M9.5 9.5C8 6 8.5 2.5 10 2.5s2 3.5 1.5 7"/><path d="M14.5 9.5C16 6 15.5 2.5 14 2.5s-2 3.5-1.5 7"/><path d="M9.5 14.5h1M13.5 14.5h1"/><path d="M11 17c.6.5 1.4.5 2 0"/>',
	dino: '<path d="M3 17c0-4 3-7 7-7h3c2 0 3-2 3-4h3c1 0 2 1 2 2s-1 2-2 2h-1c0 4-2 7-6 8"/><path d="M6 17v3M11 18v2"/><path d="M8 10l1-3 2 3 1-3 2 3"/><path d="M3 17c-1 0-1.5 1-1 2"/>',
	blanket: '<path d="M4 5h14a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2z"/><path d="M4 9h16M4 13h16M4 17h16"/><path d="M6 19v2M10 19v2M14 19v2M18 19v2"/>',
	granny: '<rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="10" height="10" rx="1"/><rect x="10" y="10" width="4" height="4"/><path d="M3 12h4M17 12h4M12 3v4M12 17v4"/>',
	stripes: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 7h18M3 11h18M3 15h18M3 19h18"/>',
	beanie: '<path d="M4 16a8 8 0 0 1 16 0"/><rect x="3" y="16" width="18" height="4" rx="1.5"/><path d="M7 16v4M11 16v4M15 16v4M19 16v4"/><circle cx="12" cy="5" r="2"/>',
	sweater: '<path d="M8 3l-5 4 2 4 2-1v11h10V10l2 1 2-4-5-4"/><path d="M8 3c1 2 2.5 3 4 3s3-1 4-3"/><path d="M12 6v15"/><circle cx="10" cy="11" r=".6"/><circle cx="10" cy="15" r=".6"/>',
	scarf: '<path d="M6 4c3 2 9 2 12 0v5c-3 2-9 2-12 0z"/><path d="M14 9v10l3 2V9"/><path d="M14 19h3"/>',
	hat: '<path d="M7 13l1.5-7c.3-1.3 1.6-2 3.5-2s3.2.7 3.5 2L17 13"/><path d="M3 16c0-2 4-3 9-3s9 1 9 3-4 3-9 3-9-1-9-3z"/><path d="M8 9h8"/>',
	bootie: '<path d="M6 4h6v9l6 2c2 .7 2 4-1 4H7a2 2 0 0 1-2-2V5a1 1 0 0 1 1-1z"/><path d="M6 8h6"/><circle cx="9" cy="11" r="1"/>',
	basket: '<path d="M3 9h18l-2 11H5z"/><path d="M8 9a4 4 0 0 1 8 0"/><path d="M6 13h12M6.5 16.5h11"/>',
	flower: '<circle cx="12" cy="12" r="2.5"/><circle cx="12" cy="6" r="3"/><circle cx="12" cy="18" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="12" r="3"/>',
	stocking: '<path d="M8 3h8v3H8z"/><path d="M8 6v8l-3 3c-2 2 0 5 3 4l5-2c2-1 3-2 3-4V6"/>',
	pumpkin: '<path d="M12 7c-5 0-8 3-8 7s3 7 8 7 8-3 8-7-3-7-8-7z"/><path d="M12 7c-2 2-2 12 0 14M12 7c2 2 2 12 0 14"/><path d="M12 7V4c1-1 2-1 3 0"/>',
	dog: '<path d="M6 8c-2 0-3 3-3 6 0 2 1.5 2.5 2.5 1L7 12"/><path d="M18 8c2 0 3 3 3 6 0 2-1.5 2.5-2.5 1L17 12"/><path d="M7 9c0-3 2.2-5 5-5s5 2 5 5v6c0 3-2.2 5-5 5s-5-2-5-5z"/><circle cx="10" cy="11" r=".8"/><circle cx="14" cy="11" r=".8"/><path d="M11 15h2l-1 1.2z"/>',
	cat: '<path d="M5 20V9L4 3l5 3h6l5-3-1 6v11"/><path d="M5 20h14"/><circle cx="9.5" cy="12" r=".9"/><circle cx="14.5" cy="12" r=".9"/><path d="M11 15h2l-1 1z"/><path d="M8 15.5H5M16 15.5h3"/>',
	bird: '<path d="M4 15c0-5 4-9 9-9 2 0 4 1.5 4 4l4 1-4 2c0 4-3 7-7 7-3 0-6-2-6-5z"/><circle cx="14.5" cy="9.5" r=".9"/><path d="M8 13c2 1 4 1 6 0"/><path d="M9 20l-1 2M12 20l1 2"/>',
	owl: '<path d="M5 6l3 2h8l3-2v9c0 4-3 6-7 6s-7-2-7-6z"/><circle cx="9" cy="11" r="2.2"/><circle cx="15" cy="11" r="2.2"/><path d="M11.3 14h1.4l-.7 1.2z"/><path d="M9 18c2 1 4 1 6 0"/>',
	mouse: '<path d="M3 16c0-4 3-7 8-7s7 3 7 7H3z"/><circle cx="8" cy="8" r="2.5"/><path d="M18 16c2 0 3-1 3-3"/><circle cx="14" cy="12.5" r=".8"/>',
	bone: '<path d="M7.5 9.5l7-7"/><circle cx="5" cy="9" r="2.3"/><circle cx="8" cy="12" r="2.3"/><circle cx="16" cy="3.5" r="2.3"/><circle cx="19" cy="6.5" r="2.3"/><path d="M9.5 10.5l4.5-4.5"/><path d="M4 16l8 5 8-5"/>',
	petbed: '<path d="M2 14c0-2 2-3 4-3h12c2 0 4 1 4 3v3c0 2-2 3-4 3H6c-2 0-4-1-4-3z"/><path d="M5 14c0 1.5 3 2.5 7 2.5s7-1 7-2.5"/><circle cx="9" cy="8" r="1.2"/><circle cx="12" cy="6.5" r="1.2"/><circle cx="15" cy="8" r="1.2"/>',
	chicken: '<path d="M7 20h10c2 0 3-2 3-5 0-3-2-5-4-6V7c0-2-1.5-3-3-3s-3 1-3 3v1C6 9 4 12 4 15c0 3 1 5 3 5z"/><path d="M10 6.5l-1.5 1L10 8.5"/><path d="M12 4l1-2 1 2"/><circle cx="12" cy="6.5" r=".7"/><path d="M8 14c2 2 5 2 7 0"/>',
	heart2: '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/><path d="M10 12l2 2 2-2"/>',
	bag: '<path d="M4 8h16l-1.5 13h-13z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/><path d="M8 12h8M8 16h8"/>',
	cap2: '<path d="M4 15a8 8 0 0 1 16 0z"/><path d="M20 15h2.5c.5 0 .5 1.5 0 1.5H4"/><path d="M12 7v8M8.5 8.5l1.5 6.5M15.5 8.5L14 15"/><circle cx="12" cy="6.5" r=".8"/>',
	tumbler: '<path d="M7 6h10l-1.5 15h-7z"/><path d="M6 3.5h12V6H6z"/><path d="M14 3.5l1.5-2.5"/><path d="M8.5 11h7M9 15h6"/>',
	walldecal: '<rect x="3" y="3" width="18" height="18" rx="1"/><path d="M6 9c1-2 3-2 4 0s3 2 4 0 3-2 4 0"/><path d="M7 14h10M9 17h6"/><path d="M3 21h18"/>',
	car: '<path d="M3 15l1.5-5c.4-1.2 1.4-2 2.6-2h9.8c1.2 0 2.2.8 2.6 2L21 15v3H3z"/><path d="M6 10.5h12"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M14 12.5h3"/>',
	charm: '<path d="M12 2v7"/><circle cx="12" cy="4" r=".8"/><circle cx="12" cy="7" r=".8"/><circle cx="12" cy="15" r="2"/><circle cx="12" cy="11" r="2.2"/><circle cx="12" cy="19" r="2.2"/><circle cx="8" cy="15" r="2.2"/><circle cx="16" cy="15" r="2.2"/>',
	wheel: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M3.5 10.5l6 1M20.5 10.5l-6 1M12 14.5V21"/><path d="M5 6l1.5 1.5M19 6l-1.5 1.5"/>',
	belt: '<path d="M5 3l14 18"/><path d="M8 3l13 17"/><rect x="9" y="9" width="6" height="4" rx="1" transform="rotate(52 12 11)"/><path d="M6.5 6l2-1.5M14 15.5l2-1.5"/>',
};
const SNOW = '<path d="M12 2v20M3.3 7l17.4 10M3.3 17L20.7 7"/>';
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');

function svg(p, ribbon) {
	const a = p.art;
	const motif = MOTIFS[a.motif] || ICONS.star;
	const xmas = p.cat === 'christmas' || /christmas/i.test(p.title);
	const flakes = xmas
		? [[70, 90, 1.6], [560, 120, 1.2], [520, 540, 1.8], [90, 520, 1.3], [300, 50, 1], [600, 330, 1.1]].map(([x, y, s]) => `<g transform="translate(${x - 12 * s} ${y - 12 * s}) scale(${s})" fill="none" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" opacity=".9">${SNOW}</g>`).join('')
		: [[80, 100], [560, 110], [540, 540], [100, 530]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r="10" fill="${a.accent}" opacity=".18"/>`).join('');
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="640" height="640">
<defs>
	<pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="2" fill="${a.accent}" opacity=".12"/></pattern>
	<filter id="sh" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="14" stdDeviation="16" flood-color="#0b1f3a" flood-opacity=".18"/></filter>
</defs>
<rect width="640" height="640" fill="${a.bg}"/>
<rect width="640" height="640" fill="url(#dots)"/>
${flakes}
<g transform="rotate(-4 320 330)" filter="url(#sh)"><rect x="150" y="120" width="340" height="420" rx="18" fill="#ffffff"/></g>
<g transform="rotate(3 320 320)"><rect x="170" y="110" width="300" height="400" rx="16" fill="#ffffff" stroke="${a.accent}" stroke-opacity=".25" stroke-width="2"/>
	<rect x="200" y="140" width="240" height="220" rx="12" fill="${a.accent}" opacity=".1"/>
	<g transform="translate(248 178) scale(6)" fill="none" stroke="${a.accent}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">${motif}</g>
	<text x="320" y="410" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="30" font-weight="800" letter-spacing="2" fill="${a.accent}">${esc(a.label)}</text>
	<rect x="236" y="432" width="168" height="8" rx="4" fill="${a.accent}" opacity=".25"/>
	<rect x="262" y="452" width="116" height="8" rx="4" fill="${a.accent}" opacity=".15"/>
</g>
<g><rect x="402" y="560" width="210" height="44" rx="22" fill="#0b1f3a"/><text x="507" y="589" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="17" font-weight="700" fill="#ffffff">${ribbon}</text></g>
</svg>`;
}

const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'semrs-dart-'));
fs.mkdirSync(`${BASE}/art-src`, { recursive: true });
for (const [cid, ribbon] of Object.entries(CATALOGUES)) {
const data = JSON.parse(fs.readFileSync(`${BASE}/catalogues/${cid}.json`, 'utf8'));
for (const p of data.products) {
	const s = svg(p, ribbon);
	fs.writeFileSync(`${BASE}/art-src/${cid}-${p.key}.svg`, s);
	const html = path.join(tmp, `${cid}-${p.key}.html`);
	fs.writeFileSync(html, `<!doctype html><html><body style="margin:0">${s}</body></html>`);
	const out = path.resolve(`${BASE}/images/${cid}-${p.key}.jpg`);
	execFileSync(CHROME, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--force-device-scale-factor=1', '--virtual-time-budget=1500', '--window-size=640,640', `--user-data-dir=${path.join(tmp, 'prof')}`, `--screenshot=${out}`, 'file:///' + html.replace(/\\/g, '/')], { stdio: 'ignore', timeout: 60000 });
	console.log('✔', cid + '-' + p.key, Math.round(fs.statSync(out).size / 1024) + ' KB');
}
}
fs.rmSync(tmp, { recursive: true, force: true });
