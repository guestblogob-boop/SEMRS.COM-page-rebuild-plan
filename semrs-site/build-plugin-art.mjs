// Build the plugin icons and banners (WordPress.org sizes) for the SEMRS
// plugins: icon-128x128, icon-256x256, banner-772x250, banner-1544x500.
// Original vector art in the promo-kit style; the update server serves them
// from plugins/semrs-update-server/assets/art/<slug>/.
//
// Run: node build-plugin-art.mjs   (from semrs-site/, needs sharp + Segoe UI)

import fs from 'fs';
import path from 'path';
import sharp from 'sharp';

const OUT = 'plugins/semrs-update-server/assets/art';
const FONT = 'Segoe UI, Arial, sans-serif';
const C = { navy: '#0b1f3a', navy2: '#16397a', gold: '#f5b83d', soft: '#c9d6f2', white: '#ffffff', ink: '#0b1f3a', green: '#22c55e', line: '#e6ecf7' };
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const tw = (s, fs) => s.length * fs * 0.58;
const t = (x, y, s, fs, fill, w = 700, a = 'start') => `<text x="${x}" y="${y}" font-family="${FONT}" font-size="${fs}" font-weight="${w}" fill="${fill}" text-anchor="${a}">${esc(s)}</text>`;
const check = (cx, cy, r) => `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${C.green}"/><path d="M${cx - r * 0.45} ${cy} l${r * 0.32} ${r * 0.32} l${r * 0.6} ${-r * 0.62}" fill="none" stroke="#fff" stroke-width="${r * 0.28}" stroke-linecap="round" stroke-linejoin="round"/>`;

// Glyphs drawn on a 100x100 grid.
const GLYPH = {
	core: `<circle cx="42" cy="42" r="24" fill="none" stroke="#fff" stroke-width="9"/><path d="M60 60 L80 80" stroke="#fff" stroke-width="10" stroke-linecap="round"/><path d="M45 26 L34 45 H44 L39 58 L52 38 H42 Z" fill="${C.gold}"/>`,
	blocks: `<rect x="18" y="18" width="28" height="28" rx="6" fill="#fff"/><rect x="54" y="18" width="28" height="28" rx="6" fill="${C.gold}"/><rect x="18" y="54" width="28" height="28" rx="6" fill="#9fb3d9"/><rect x="54" y="54" width="28" height="28" rx="6" fill="#fff"/>`,
	tools: `<path d="M18 70 A34 34 0 1 1 82 70" fill="none" stroke="#fff" stroke-width="9" stroke-linecap="round"/><path d="M50 66 L66 36" stroke="${C.gold}" stroke-width="9" stroke-linecap="round"/><circle cx="50" cy="66" r="8" fill="#fff"/>`,
	shops: `<path d="M24 36 H76 L72 84 H28 Z" fill="#fff"/><path d="M38 40 V30 a12 12 0 0 1 24 0 V40" fill="none" stroke="${C.gold}" stroke-width="7" stroke-linecap="round"/><circle cx="38" cy="44" r="4" fill="${C.gold}"/><circle cx="62" cy="44" r="4" fill="${C.gold}"/>`,
	pro: `<path d="M18 68 L22 30 L38 46 L50 24 L62 46 L78 30 L82 68 Z" fill="${C.gold}"/><rect x="18" y="72" width="64" height="9" rx="4.5" fill="#fff"/>`,
};

// Right-side illustration for each banner, drawn into a box (x, y, w, h).
const SCENE = {
	core: (x, y, w, h) => panel(x, y, w, h, 'Site health', [['SEO titles and schema', '#2f6bff'], ['Sitemaps and IndexNow', '#14b8a6'], ['Speed and caching', '#f97316'], ['Ad placements', '#8b5cf6']]),
	pro: (x, y, w, h) => panel(x, y, w, h, 'One Pro key', [['SEO Pro', '#2f6bff'], ['Speed Pro', '#14b8a6'], ['Ads Pro', '#f97316'], ['Theme & Blocks Pro', '#8b5cf6']]),
	tools: (x, y, w, h) => panel(x, y, w, h, 'Free SEO tools', [['SERP snippet preview', '#2f6bff'], ['Schema markup generator', '#14b8a6'], ['Word counter', '#f97316'], ['UTM builder', '#8b5cf6']]),
	blocks: (x, y, w, h) => {
		// A page being built from blocks.
		let s = card(x, y, w, h);
		const p = w * 0.06;
		const top = y + h * 0.16;
		s += `<rect x="${x + p}" y="${top + p * 0.4}" width="${w - p * 2}" height="${h * 0.2}" rx="10" fill="#2f6bff" opacity=".9"/>`;
		s += `<rect x="${x + p * 1.6}" y="${top + p * 0.4 + h * 0.06}" width="${w * 0.4}" height="${h * 0.04}" rx="4" fill="#fff"/><rect x="${x + p * 1.6}" y="${top + p * 0.4 + h * 0.12}" width="${w * 0.25}" height="${h * 0.03}" rx="4" fill="#cfe0ff"/>`;
		const gy = top + p * 0.4 + h * 0.24;
		const cw = (w - p * 2 - p) / 3;
		['#f5b83d', '#14b8a6', '#8b5cf6'].forEach((c, i) => {
			const cx = x + p + i * (cw + p / 2);
			s += `<rect x="${cx}" y="${gy}" width="${cw}" height="${h * 0.3}" rx="10" fill="#f3f6fd" stroke="${C.line}"/><circle cx="${cx + cw / 2}" cy="${gy + h * 0.1}" r="${h * 0.05}" fill="${c}"/><rect x="${cx + cw * 0.2}" y="${gy + h * 0.19}" width="${cw * 0.6}" height="${h * 0.03}" rx="3" fill="#c3cde0"/>`;
		});
		s += `<rect x="${x + p}" y="${gy + h * 0.34}" width="${w * 0.36}" height="${h * 0.075}" rx="10" fill="${C.gold}"/>`;
		return s;
	},
	shops: (x, y, w, h) => {
		// A small storefront: product cards with price tags (no prices shown).
		let s = card(x, y, w, h);
		const p = w * 0.06;
		const top = y + h * 0.16;
		const cw = (w - p * 2 - p) / 3;
		['#f9a8d4', '#a5f3fc', '#fde68a', '#c4b5fd', '#bbf7d0', '#fecaca'].forEach((c, i) => {
			const cx = x + p + (i % 3) * (cw + p / 2);
			const cy = top + p * 0.4 + Math.floor(i / 3) * h * 0.38;
			s += `<rect x="${cx}" y="${cy}" width="${cw}" height="${h * 0.34}" rx="10" fill="#f3f6fd" stroke="${C.line}"/><rect x="${cx + 6}" y="${cy + 6}" width="${cw - 12}" height="${h * 0.18}" rx="7" fill="${c}"/><rect x="${cx + 8}" y="${cy + h * 0.22}" width="${cw * 0.6}" height="${h * 0.03}" rx="3" fill="#c3cde0"/><rect x="${cx + 8}" y="${cy + h * 0.27}" width="${cw * 0.3}" height="${h * 0.035}" rx="4" fill="${C.gold}"/>`;
		});
		return s;
	},
};
function card(x, y, w, h) {
	const head = h * 0.13;
	let s = `<rect x="${x + 6}" y="${y + 10}" width="${w}" height="${h}" rx="18" fill="#000" opacity=".22"/><rect x="${x}" y="${y}" width="${w}" height="${h}" rx="18" fill="#fff"/>`;
	s += `<path d="M${x} ${y + head} V${y + 18} a18 18 0 0 1 18 -18 H${x + w - 18} a18 18 0 0 1 18 18 V${y + head} Z" fill="#e8eefc"/>`;
	['#f87171', '#fbbf24', '#34d399'].forEach((c, i) => (s += `<circle cx="${x + 22 + i * head * 0.42}" cy="${y + head / 2}" r="${head * 0.14}" fill="${c}"/>`));
	return s;
}
function panel(x, y, w, h, title, rows) {
	let s = card(x, y, w, h);
	const head = h * 0.13;
	s += t(x + w - 20, y + head * 0.66, title, head * 0.42, C.ink, 700, 'end');
	const p = w * 0.06;
	const rh = (h - head - p) / rows.length;
	rows.forEach(([n, col], i) => {
		const ry = y + head + p * 0.5 + i * rh;
		s += `<rect x="${x + p}" y="${ry + rh * 0.2}" width="${rh * 0.6}" height="${rh * 0.6}" rx="${rh * 0.15}" fill="${col}"/>`;
		s += t(x + p + rh * 0.85, ry + rh * 0.62, n, Math.min(rh * 0.34, (w - p * 2 - rh * 1.8) / (n.length * 0.58)), C.ink, 700);
		s += check(x + w - p - rh * 0.3, ry + rh * 0.5, rh * 0.25);
		if (i < rows.length - 1) s += `<rect x="${x + p}" y="${ry + rh - 1}" width="${w - p * 2}" height="1.5" fill="${C.line}"/>`;
	});
	return s;
}

// slug: [glyph, name, tagline, chips, label, signature colour]
const PLUGINS = {
	'semrs-core': ['core', 'Core', 'SEO, speed and ad tools in one light plugin', ['SEO and schema', 'Speed', 'Ad placements'], 'Free', '#3b7bff'],
	'semrs-blocks': ['blocks', 'Blocks', 'Icons, headings, post grids, pricing and forms', ['Blocks', 'Templates', 'Forms'], 'Free', '#8b5cf6'],
	'semrs-tools': ['tools', 'Tools', 'Free SEO tools your visitors can use', ['SERP preview', 'Schema', 'Word counter'], 'Free', '#14b8a6'],
	'semrs-shops': ['shops', 'Shops', 'Ready-made digital download shops', ['Starter shops', 'Product pages', 'Exports'], 'Free', '#f4607a'],
	'semrs-pro': ['pro', 'Pro', 'Pro SEO, speed, ads and design on one key', ['SEO', 'Speed', 'Ads', 'Theme'], 'Pro', '#f5b83d'],
};

const mix = (hex, to, a) => {
	const n = parseInt(hex.slice(1), 16);
	const m = parseInt(to.slice(1), 16);
	const c = (sh) => Math.round(((n >> sh) & 255) * (1 - a) + ((m >> sh) & 255) * a);
	return `rgb(${c(16)},${c(8)},${c(0)})`;
};
let uid = 0;

function iconBody(g, size, acc) {
	const id = 'i' + uid++;
	const r = size * 0.24;
	const glyph = g === 'pro' ? GLYPH.pro.replace(`fill="${C.gold}"`, 'fill="#ffffff"').replace('fill="#fff"', 'fill="#0b1f3a" fill-opacity=".55"') : GLYPH[g].split(C.gold).join('#ffd166');
	return `<defs><linearGradient id="${id}a" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${mix(acc, '#ffffff', 0.12)}"/><stop offset=".55" stop-color="${mix(acc, '#0b1f3a', 0.45)}"/><stop offset="1" stop-color="${C.navy}"/></linearGradient><radialGradient id="${id}b" cx=".25" cy=".12" r=".75"><stop offset="0" stop-color="#fff" stop-opacity=".38"/><stop offset=".6" stop-color="#fff" stop-opacity="0"/></radialGradient><filter id="${id}c" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="${size * 0.02}" stdDeviation="${size * 0.025}" flood-color="#000" flood-opacity=".35"/></filter></defs>
<rect width="${size}" height="${size}" rx="${r}" fill="url(#${id}a)"/><rect width="${size}" height="${size}" rx="${r}" fill="url(#${id}b)"/><rect x="${size * 0.012}" y="${size * 0.012}" width="${size * 0.976}" height="${size * 0.976}" rx="${r * 0.95}" fill="none" stroke="#fff" stroke-opacity=".22" stroke-width="${size * 0.012}"/>
<g filter="url(#${id}c)" transform="translate(${size * 0.13} ${size * 0.13}) scale(${size * 0.0074})">${glyph}</g>`;
}
function icon(g, size, acc) {
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">${iconBody(g, size, acc)}</svg>`;
}

function banner(g, name, tag, chips, label, acc, w, h) {
	const k = w / 1544;
	const p = 64 * k;
	const sceneW = w * 0.36;
	const leftMax = w - sceneW - p * 2.6;
	let s = `<defs>
<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#081631"/><stop offset=".6" stop-color="${C.navy}"/><stop offset="1" stop-color="${mix(acc, '#0b1f3a', 0.7)}"/></linearGradient>
<radialGradient id="glow1" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="${acc}" stop-opacity=".55"/><stop offset="1" stop-color="${acc}" stop-opacity="0"/></radialGradient>
<radialGradient id="glow2" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#3b7bff" stop-opacity=".35"/><stop offset="1" stop-color="#3b7bff" stop-opacity="0"/></radialGradient>
<linearGradient id="streak" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff" stop-opacity=".09"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
<pattern id="dots" width="${26 * k}" height="${26 * k}" patternUnits="userSpaceOnUse"><circle cx="${2 * k}" cy="${2 * k}" r="${1.3 * k}" fill="#fff" opacity=".08"/></pattern>
<linearGradient id="name" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${mix(acc, '#ffffff', 0.35)}"/><stop offset="1" stop-color="${acc}"/></linearGradient>
<filter id="soft" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="${18 * k}" stdDeviation="${22 * k}" flood-color="#000" flood-opacity=".45"/></filter>
</defs>`;
	s += `<rect width="${w}" height="${h}" fill="url(#bg)"/><rect width="${w}" height="${h}" fill="url(#dots)"/>`;
	s += `<ellipse cx="${w * 0.8}" cy="${h * 0.45}" rx="${w * 0.32}" ry="${h * 0.95}" fill="url(#glow1)"/><ellipse cx="${w * 0.1}" cy="${h * 1.05}" rx="${w * 0.3}" ry="${h * 0.7}" fill="url(#glow2)"/>`;
	s += `<polygon points="${w * 0.42},0 ${w * 0.52},0 ${w * 0.38},${h} ${w * 0.28},${h}" fill="url(#streak)"/>`;
	// Big faint watermark of the glyph behind the text.
	s += `<g opacity=".06" transform="translate(${w * 0.36} ${-h * 0.18}) scale(${h * 0.0135})">${GLYPH[g].replace(/fill="[^"]*"/g, 'fill="#fff"').replace(/stroke="[^"]*"/g, 'stroke="#fff"')}</g>`;
	s += `<rect y="${h - 5 * k}" width="${w}" height="${5 * k}" fill="${acc}"/>`;
	// Icon tile.
	const is = 132 * k;
	s += `<g transform="translate(${p} ${p + 6 * k})"><g filter="url(#soft)">${iconBody(g, is, acc)}</g></g>`;
	// Name: SEMRS + gradient product name, then the label pill.
	const tx = p + is + 36 * k;
	let nfs = 86 * k;
	const pillW = (lab, f) => tw(lab, f) + 34 * k;
	while (tw('SEMRS ' + name, nfs) + 24 * k + pillW(label, 24 * k) > leftMax - (tx - p) && nfs > 40 * k) nfs -= 2 * k;
	const ny = p + 74 * k;
	s += `<text x="${tx}" y="${ny}" font-family="${FONT}" font-size="${nfs}" font-weight="800" letter-spacing="${-1 * k}" fill="${C.white}">SEMRS <tspan fill="url(#name)">${esc(name)}</tspan></text>`;
	const px = tx + tw('SEMRS ' + name, nfs) + 22 * k;
	s += `<rect x="${px}" y="${ny - 42 * k}" width="${pillW(label, 24 * k)}" height="${42 * k}" rx="${21 * k}" fill="${label === 'Pro' ? C.gold : 'rgba(255,255,255,.12)'}" stroke="${label === 'Pro' ? 'none' : 'rgba(255,255,255,.3)'}"/>`;
	s += t(px + pillW(label, 24 * k) / 2, ny - 13 * k, label.toUpperCase(), 21 * k, label === 'Pro' ? C.ink : C.white, 800, 'middle');
	// Tagline: one line, sized to fit.
	let tfs = 36 * k;
	const tagW = leftMax - (tx - p);
	while (tag.length * tfs * 0.52 > tagW && tfs > 18 * k) tfs -= 1 * k;
	s += t(tx, ny + 58 * k, tag, tfs, C.soft, 400);
	// Chips under the tagline, inside the left column.
	let cx = tx;
	const cy = Math.min(h - p - 50 * k, ny + 118 * k);
	let cfs = 23 * k;
	const total = (f) => chips.reduce((a, c) => a + tw(c, f) + 58 * k + 12 * k, 0);
	while (total(cfs) > tagW && cfs > 14 * k) cfs -= 1 * k;
	for (const c of chips) {
		const cw = tw(c, cfs) + 58 * k;
		s += `<rect x="${cx}" y="${cy}" width="${cw}" height="${50 * k}" rx="${25 * k}" fill="rgba(255,255,255,.07)" stroke="rgba(255,255,255,.2)"/>`;
		s += `<circle cx="${cx + 25 * k}" cy="${cy + 25 * k}" r="${11 * k}" fill="${acc}"/><path d="M${cx + 20 * k} ${cy + 25 * k} l${3.5 * k} ${3.5 * k} l${6.5 * k} ${-7 * k}" fill="none" stroke="#fff" stroke-width="${3 * k}" stroke-linecap="round" stroke-linejoin="round"/>`;
		s += t(cx + 44 * k, cy + 33 * k, c, cfs, C.white, 600);
		cx += cw + 12 * k;
	}
	// Scene: two ghost windows behind, the main window tilted slightly, a glowing badge.
	const sx = w - sceneW - p;
	const sy = p * 0.85;
	const sh = h - p * 1.75;
	s += `<g transform="rotate(-4 ${sx + sceneW / 2} ${sy + sh / 2})">`;
	s += `<rect x="${sx + 34 * k}" y="${sy - 22 * k}" width="${sceneW}" height="${sh}" rx="${18 * k}" fill="#fff" opacity=".08"/>`;
	s += `<rect x="${sx + 17 * k}" y="${sy - 11 * k}" width="${sceneW}" height="${sh}" rx="${18 * k}" fill="#fff" opacity=".14"/>`;
	s += `<g filter="url(#soft)">${SCENE[g](sx, sy, sceneW, sh)}</g></g>`;
	const bx = sx - 6 * k;
	const by = sy + sh - 18 * k;
	s += `<circle cx="${bx}" cy="${by}" r="${46 * k}" fill="${acc}" opacity=".35"/><circle cx="${bx}" cy="${by}" r="${34 * k}" fill="${acc}"/><circle cx="${bx}" cy="${by}" r="${34 * k}" fill="none" stroke="#fff" stroke-opacity=".6" stroke-width="${3 * k}"/>`;
	s += `<path d="M${bx - 12 * k} ${by} l${8 * k} ${8 * k} l${16 * k} ${-17 * k}" fill="none" stroke="#fff" stroke-width="${6 * k}" stroke-linecap="round" stroke-linejoin="round"/>`;
	// A small sparkle for a premium touch.
	const spx = sx + sceneW + 6 * k;
	const spy = sy + 8 * k;
	s += `<path d="M${spx} ${spy - 18 * k} Q${spx + 3 * k} ${spy - 3 * k} ${spx + 18 * k} ${spy} Q${spx + 3 * k} ${spy + 3 * k} ${spx} ${spy + 18 * k} Q${spx - 3 * k} ${spy + 3 * k} ${spx - 18 * k} ${spy} Q${spx - 3 * k} ${spy - 3 * k} ${spx} ${spy - 18 * k} Z" fill="${C.gold}"/>`;
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${s}</svg>`;
}

const made = [];
for (const [slug, [g, name, tag, chips, label, acc]] of Object.entries(PLUGINS)) {
	const dir = path.join(OUT, slug);
	fs.mkdirSync(dir, { recursive: true });
	for (const sz of [128, 256]) await sharp(Buffer.from(icon(g, sz, acc))).png({ compressionLevel: 9 }).toFile(path.join(dir, `icon-${sz}x${sz}.png`));
	await sharp(Buffer.from(banner(g, name, tag, chips, label, acc, 1544, 500))).png({ compressionLevel: 9 }).toFile(path.join(dir, 'banner-1544x500.png'));
	await sharp(Buffer.from(banner(g, name, tag, chips, label, acc, 772, 250))).png({ compressionLevel: 9 }).toFile(path.join(dir, 'banner-772x250.png'));
	fs.writeFileSync(path.join(dir, 'icon.svg'), icon(g, 256, acc));
	made.push(slug);
}
console.log('art for', made.join(', '));
