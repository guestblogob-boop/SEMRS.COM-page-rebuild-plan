// Build niche hero illustrations for the templates library.
// Original artwork made from the SEMRS icon set: writes SVGs to
// plugins/semrs-blocks/templates/art-src/ and renders transparent PNGs to
// plugins/semrs-blocks/templates/images/ with headless Chrome.
//
// Run: node build-art.mjs   (from semrs-site/, Windows + Chrome)

import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';

const ICONS = Object.fromEntries([...fs.readFileSync('plugins/semrs-blocks/includes/icons.php', 'utf8').matchAll(/'([a-z0-9-]+)'\s*=>\s*'([^']*)'/g)].map((m) => [m[1], m[2]]));
const CUSTOM = {
	leaf: '<path d="M5 19c0-8 6-14 14-14 0 8-6 14-14 14z"/><path d="M5 19l8-8"/>',
	paw: '<path d="M12 13c-3 0-5 2.5-5 4.5S9 20 12 20s5-.5 5-2.5S15 13 12 13z"/><circle cx="6.5" cy="10" r="1.8"/><circle cx="9.8" cy="6.4" r="1.8"/><circle cx="14.2" cy="6.4" r="1.8"/><circle cx="17.5" cy="10" r="1.8"/>',
	tooth: '<path d="M7 3c-2 0-3 2-3 4 0 3 1 5 2 8l1 5c.3 1 1.7 1 2 0l1-4c.3-1 1.7-1 2 0l1 4c.3 1 1.7 1 2 0l1-5c1-3 2-5 2-8 0-2-1-4-3-4-2 0-3 1-5 1S9 3 7 3z"/>',
	plane: '<path d="M2 13l20-8-6 16-4-6z"/><path d="M12 15l4-6"/>',
	tent: '<path d="M3 20L12 4l9 16z"/><path d="M8 20l4-7 4 7"/>',
	cap: '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/><path d="M22 9v6"/>',
	chip: '<rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M10 3v4M14 3v4M10 17v4M14 17v4M3 10h4M3 14h4M17 10h4M17 14h4"/><rect x="10" y="10" width="4" height="4"/>',
};
const icon = (n) => CUSTOM[n] || ICONS[n] || ICONS.star;

const NICHES = {
	'seo-services': ['search', '#d4a72c', [['settings', 'Technical'], ['map-pin', 'Local'], ['trending-up', 'Rankings']]],
	'digital-marketing': ['target', '#e8743b', [['chart', 'Ads'], ['mail', 'Email'], ['users', 'Social']]],
	'content-marketing': ['pen', '#7c5cff', [['layers', 'Pages'], ['lightbulb', 'Ideas'], ['search', 'SEO']]],
	'social-media': ['chat', '#e1306c', [['heart', 'Likes'], ['camera', 'Reels'], ['users', 'Community']]],
	'guest-posting': ['link', '#2563eb', [['globe', 'Outreach'], ['pen', 'Articles'], ['check-circle', 'Quality']]],
	cleaning: ['home', '#14b8a6', [['star', 'Spotless'], ['shield', 'Insured'], ['calendar', 'Flexible']]],
	maintenance: ['settings', '#f59e0b', [['home', 'Repairs'], ['pen', 'Painting'], ['calendar', 'Plans']]],
	repairs: ['phone', '#4f46e5', [['code', 'Laptops'], ['settings', 'Appliances'], ['clock', 'Same day']]],
	gardening: ['leaf', '#22a55e', [['calendar', 'Seasonal'], ['home', 'Landscaping'], ['heart', 'Planting']]],
	pets: ['paw', '#f97316', [['heart', 'Grooming'], ['clock', 'Walks'], ['home', 'Sitting']]],
	dentist: ['tooth', '#0ea5e9', [['heart', 'Check-ups'], ['star', 'Whitening'], ['users', 'Family']]],
	'top-ten': ['award', '#ef4444', [['star', 'Top 10'], ['check-circle', 'Tested'], ['thumbs-up', 'Best picks']]],
	'travel-blog': ['plane', '#06b6d4', [['map-pin', 'Places'], ['camera', 'Photos'], ['globe', 'Guides']]],
	'travel-agency': ['plane', '#3b82f6', [['globe', 'Tours'], ['home', 'Stays'], ['calendar', 'Trips']]],
	camping: ['tent', '#16a34a', [['heart', 'Campfire'], ['globe', 'Hikes'], ['star', 'Cabins']]],
	school: ['cap', '#d4a72c', [['award', 'Programs'], ['users', 'Teachers'], ['pen', 'Admissions']]],
	'online-courses': ['play', '#8b5cf6', [['award', 'Certificate'], ['code', 'Skills'], ['clock', 'Own pace']]],
	'ai-llm': ['chip', '#0891b2', [['chat', 'Chatbots'], ['bolt', 'Automation'], ['search', 'AI search']]],
};

const tint = (hex, a) => { const n = parseInt(hex.slice(1), 16); const r = n >> 16, g = (n >> 8) & 255, b = n & 255; const m = (c) => Math.round(c + (255 - c) * a); return `rgb(${m(r)},${m(g)},${m(b)})`; };

function card(x, y, w, ic, label, accent) {
	return `<g filter="url(#sh)"><rect x="${x}" y="${y}" width="${w}" height="64" rx="18" fill="#fff"/></g>
	<circle cx="${x + 34}" cy="${y + 32}" r="20" fill="${tint(accent, 0.82)}"/>
	<g transform="translate(${x + 22} ${y + 20})" fill="none" stroke="${accent}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">${icon(ic)}</g>
	<text x="${x + 64}" y="${y + 38}" font-family="system-ui, -apple-system, Segoe UI, sans-serif" font-size="17" font-weight="700" fill="#0b1f3a">${label}</text>`;
}

function svg(center, accent, cards) {
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 560">
<defs>
	<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${tint(accent, 0.9)}"/><stop offset="1" stop-color="${tint(accent, 0.7)}"/></linearGradient>
	<linearGradient id="tile" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="${tint(accent, 0.92)}"/></linearGradient>
	<filter id="sh" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#0b1f3a" flood-opacity=".16"/></filter>
</defs>
<circle cx="300" cy="285" r="222" fill="url(#bg)"/>
<circle cx="300" cy="285" r="256" fill="none" stroke="#0b1f3a" stroke-width="2.5" stroke-dasharray="2 13" stroke-linecap="round" opacity=".3"/>
<path d="M92 220 A214 214 0 0 1 290 72" fill="none" stroke="${accent}" stroke-width="14" stroke-linecap="round"/>
<circle cx="290" cy="72" r="8" fill="#0b1f3a"/>
<path d="M512 352 A214 214 0 0 1 404 470" fill="none" stroke="#0b1f3a" stroke-width="9" stroke-linecap="round"/>
<g filter="url(#sh)"><rect x="180" y="165" width="240" height="240" rx="48" fill="url(#tile)"/></g>
<circle cx="300" cy="285" r="78" fill="${tint(accent, 0.8)}"/>
<g transform="translate(228 213) scale(6)" fill="none" stroke="#0b1f3a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">${icon(center)}</g>
${card(392, 116, 186, cards[0][0], cards[0][1], accent)}
${card(14, 296, 186, cards[1][0], cards[1][1], accent)}
${card(380, 442, 196, cards[2][0], cards[2][1], accent)}
<path d="M540 66 l5 12 12 5 -12 5 -5 12 -5 -12 -12 -5 12 -5 z" fill="${accent}"/>
<path d="M86 470 l4 9 9 4 -9 4 -4 9 -4 -9 -9 -4 9 -4 z" fill="#0b1f3a" opacity=".45"/>
</svg>`;
}

const SRC = 'plugins/semrs-blocks/templates/art-src';
const OUT = 'plugins/semrs-blocks/templates/images';
fs.mkdirSync(SRC, { recursive: true });
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const prof = path.resolve(process.env.TEMP || '.', 'semrs-art-profile');
for (const [id, [center, accent, cards]] of Object.entries(NICHES)) {
	const file = path.resolve(SRC, `art-${id}.svg`);
	fs.writeFileSync(file, svg(center, accent, cards));
	execFileSync(chrome, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--default-background-color=00000000', '--force-device-scale-factor=1', '--window-size=900,840', `--user-data-dir=${prof}`, `--screenshot=${path.resolve(OUT, `art-${id}.png`)}`, 'file:///' + file.replace(/\\/g, '/')], { stdio: 'ignore', timeout: 60000 });
	console.log('art', id);
}
