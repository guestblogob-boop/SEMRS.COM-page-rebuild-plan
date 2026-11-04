// Build the SEMRS promo kit: affiliate-program banners (for semrs.com and
// social), SEMRS Pro banners partners can place, add-on feature cards, a
// partner badge and an email header. Original vector art, the plain "SEMRS"
// wordmark only, and no figures except real prices and program terms.
//
// Writes PNG + WebP to assets/promo/<set>/ and a review gallery to
// assets/promo/index.html.
//
// Run: node build-promo-kit.mjs   (from semrs-site/, needs sharp + Segoe UI)

import fs from 'fs';
import path from 'path';
import sharp from 'sharp';

const OUT = 'assets/promo';
const FONT = "Segoe UI, Arial, sans-serif";
const C = { navy: '#0b1f3a', navy2: '#13336e', blue: '#2f6bff', gold: '#f5b83d', ink: '#0b1f3a', soft: '#c9d6f2', white: '#ffffff', green: '#22c55e' };

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
// Rough Segoe UI widths: bold ~0.58em, regular ~0.52em per character.
const tw = (s, fs, bold = true) => s.length * fs * (bold ? 0.58 : 0.52);
const fit = (lines, maxW, maxFs, bold = true) => Math.min(maxFs, ...lines.map((l) => maxW / (l.length * (bold ? 0.58 : 0.52))));
function wrap(text, fs, maxW, bold = true) {
	const out = [];
	let cur = '';
	for (const w of text.split(' ')) {
		const t = cur ? cur + ' ' + w : w;
		if (cur && tw(t, fs, bold) > maxW) {
			out.push(cur);
			cur = w;
		} else cur = t;
	}
	return cur ? [...out, cur] : out;
}
// Largest size (<= maxFs) at which the text wraps into at most maxLines inside maxW.
function block(text, maxW, maxFs, maxLines, bold = true) {
	for (let fs = maxFs; fs > 8; fs -= 1) {
		const lines = wrap(text, fs, maxW, bold);
		if (lines.length <= maxLines && lines.every((l) => tw(l, fs, bold) <= maxW)) return { fs, lines };
	}
	return { fs: 8, lines: wrap(text, 8, maxW, bold) };
}

const text = (x, y, s, fs, fill, weight = 700, anchor = 'start') => `<text x="${x}" y="${y}" font-family="${FONT}" font-size="${fs.toFixed(1)}" font-weight="${weight}" fill="${fill}" text-anchor="${anchor}">${esc(s)}</text>`;
const lines = (x, y, b, fill, weight = 700, lh = 1.18, anchor = 'start') => b.lines.map((l, i) => text(x, y + i * b.fs * lh, l, b.fs, fill, weight, anchor)).join('');
const linesH = (b, lh = 1.18) => b.fs * (1 + (b.lines.length - 1) * lh);

function brand(x, y, fs, anchor = 'start') {
	// Wordmark only: the name in text with a gold full stop.
	const w = tw('SEMRS', fs);
	const sx = anchor === 'middle' ? x - w / 2 : x;
	return `<text x="${sx}" y="${y}" font-family="${FONT}" font-size="${fs.toFixed(1)}" font-weight="800" letter-spacing="${(fs * 0.04).toFixed(1)}" fill="${C.white}">SEMRS<tspan fill="${C.gold}">.</tspan></text>`;
}
function pill(x, y, s, fs, anchor = 'start') {
	const w = tw(s, fs) + fs * 1.6 + s.length * fs * 0.08;
	const h = fs * 2;
	const px = anchor === 'middle' ? x - w / 2 : x;
	return { h, w, svg: `<rect x="${px}" y="${y}" width="${w}" height="${h}" rx="${h / 2}" fill="${C.gold}"/><text x="${px + w / 2}" y="${y + h * 0.66}" font-family="${FONT}" font-size="${fs}" font-weight="800" letter-spacing="${(fs * 0.08).toFixed(1)}" fill="${C.ink}" text-anchor="middle">${esc(s)}</text>` };
}
function button(x, y, s, fs, anchor = 'start') {
	const w = tw(s, fs) + fs * 2.4;
	const h = fs * 2.5;
	const px = anchor === 'middle' ? x - w / 2 : anchor === 'end' ? x - w : x;
	return { w, h, svg: `<rect x="${px}" y="${y}" width="${w}" height="${h}" rx="${h * 0.3}" fill="${C.gold}"/><text x="${px + w / 2}" y="${y + h * 0.64}" font-family="${FONT}" font-size="${fs.toFixed(1)}" font-weight="800" fill="${C.ink}" text-anchor="middle">${esc(s)} →</text>` };
}
const check = (cx, cy, r, color = C.green) => `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${color}"/><path d="M${cx - r * 0.45} ${cy + r * 0.02} l${r * 0.32} ${r * 0.32} l${r * 0.6} ${-r * 0.62}" fill="none" stroke="#fff" stroke-width="${r * 0.28}" stroke-linecap="round" stroke-linejoin="round"/>`;

// Graphic: commission bars 40 / 45 / 50 / 60 (the real tiers).
function gBars(x, y, w, h) {
	const tiers = [['1–10', 40], ['11–30', 45], ['31–60', 50], ['61+', 60]];
	const gap = w * 0.06;
	const bw = (w - gap * 3) / 4;
	const lab = Math.max(9, Math.min(bw * 0.3, h * 0.08));
	const top = lab * 1.6;
	const cap = h > 160 && w > 200;
	const base = y + h - lab * (cap ? 3 : 1.5);
	const maxH = base - y - top;
	let s = '';
	tiers.forEach(([t, p], i) => {
		const bh = maxH * (p / 60);
		const bx = x + i * (bw + gap);
		const by = base - bh;
		const fill = i === 3 ? C.gold : i === 2 ? '#8fb0ff' : i === 1 ? '#5f8dff' : C.blue;
		s += `<rect x="${bx}" y="${by}" width="${bw}" height="${bh}" rx="${Math.min(bw * 0.18, 14)}" fill="${fill}"/>`;
		s += text(bx + bw / 2, by - lab * 0.45, p + '%', lab * 1.15, C.white, 800, 'middle');
		s += text(bx + bw / 2, base + lab * 1.25, t, lab * 0.85, C.soft, 600, 'middle');
	});
	if (cap) s += text(x + w / 2, base + lab * 2.7, 'Sales of each add-on per month', lab * 0.85, C.soft, 400, 'middle');
	return s;
}
// Graphic: a plugin card listing the four Pro add-ons with checks.
function gStack(x, y, w, h) {
	const narrow = w < 200;
	const rows = [['SEO Pro', C.blue], ['Speed Pro', '#14b8a6'], ['Ads Pro', '#f97316'], [narrow ? 'Theme Pro' : 'Theme & Blocks Pro', '#8b5cf6']];
	const pad = w * 0.07;
	const head = Math.min(h * 0.16, w * 0.2);
	const rh = Math.min((h - head - pad) / rows.length, w * 0.26);
	h = head + pad + rh * rows.length;
	const longest = Math.max(...rows.map((r) => r[0].length));
	const fs = Math.max(8, Math.min(rh * 0.36, (w - pad * 2 - rh * 1.6) / (longest * 0.58)));
	let s = `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${Math.min(w, h) * 0.06}" fill="#fff"/>`;
	s += `<rect x="${x}" y="${y}" width="${w}" height="${head}" rx="${Math.min(w, h) * 0.06}" fill="#e8eefc"/><rect x="${x}" y="${y + head * 0.6}" width="${w}" height="${head * 0.4}" fill="#e8eefc"/>`;
	[0, 1, 2].forEach((i) => (s += `<circle cx="${x + pad + i * head * 0.42}" cy="${y + head / 2}" r="${head * 0.13}" fill="${['#f87171', '#fbbf24', '#34d399'][i]}"/>`));
	s += text(x + w - pad, y + head * 0.64, 'One key', Math.min(head * 0.38, fs), C.ink, 700, 'end');
	rows.forEach(([n, col], i) => {
		const ry = y + head + pad * 0.4 + i * rh;
		s += `<rect x="${x + pad}" y="${ry + rh * 0.18}" width="${rh * 0.64}" height="${rh * 0.64}" rx="${rh * 0.16}" fill="${col}"/>`;
		s += text(x + pad + rh * 0.9, ry + rh * 0.62, n, fs, C.ink, 700);
		s += check(x + w - pad - rh * 0.3, ry + rh * 0.5, rh * 0.26);
	});
	return s;
}

const SETS = {
	affiliate: {
		eyebrow: 'SEMRS AFFILIATE PROGRAM',
		short: 'Earn up to 60%',
		long: 'Earn up to 60% recommending SEMRS',
		sub: '15% on first-year renewals · $50 monthly bonus · Paid worldwide',
		subShort: 'Plus 15% renewals · Paid worldwide',
		cta: 'Join free',
		url: 'semrs.com/affiliate-program',
		graphic: gBars,
	},
	product: {
		eyebrow: 'SEMRS PRO FOR WORDPRESS',
		short: 'SEO, speed & ads in one key',
		long: 'SEO, speed and ads for WordPress in one key',
		sub: 'Replaces a premium SEO plugin, a caching plugin, an image optimizer and an ad plugin',
		subShort: 'One plugin, one key, one place for updates',
		cta: 'Get SEMRS Pro',
		squareShort: true,
		url: 'semrs.com/semrs-pro',
		graphic: gStack,
	},
};

function bg(w, h, seed = 0) {
	const r = Math.max(w, h);
	return `<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.navy}"/><stop offset="1" stop-color="${C.navy2}"/></linearGradient></defs>
<rect width="${w}" height="${h}" fill="url(#g)"/>
<circle cx="${w * (seed ? 0.08 : 0.92)}" cy="${h * 0.1}" r="${r * 0.32}" fill="${C.blue}" opacity=".16"/>
<circle cx="${w * (seed ? 0.95 : 0.05)}" cy="${h * 0.98}" r="${r * 0.24}" fill="${C.gold}" opacity=".10"/>
<rect y="${h - Math.max(3, h * 0.012)}" width="${w}" height="${Math.max(3, h * 0.012)}" fill="${C.gold}"/>`;
}

// One banner of a set at a size, laid out by its shape.
function banner(set, w, h) {
	const s = SETS[set];
	const a = w / h;
	const p = Math.round(Math.min(w, h) * (h <= 110 ? 0.16 : 0.08));
	let body = '';
	if (h <= 110) {
		// Thin strips (728x90, 320x100): wordmark, headline, button in a row.
		const bfs = Math.min(h * 0.3, w * 0.05);
		const btn = button(w - p, 0, s.cta, Math.min(h * 0.2, w * 0.03), 'end');
		const by = (h - btn.h) / 2;
		const btn2 = button(w - p, by, s.cta, Math.min(h * 0.2, w * 0.03), 'end');
		const left = p + tw('SEMRS.', bfs) + p;
		const room = w - p - btn.w - p - left;
		const hd = block(w >= 600 ? s.long : s.short, room, h * 0.3, 2);
		body = brand(p, h / 2 + bfs * 0.36, bfs) + lines(left, h / 2 - linesH(hd) / 2 + hd.fs * 0.8, hd, C.white) + btn2.svg;
	} else if (a >= 2.2) {
		// Wide (970x250, 600x200, 1500x500): text left, graphic right.
		const gw = Math.min(w * 0.36, h * 1.5);
		const tx = p;
		const tWidth = w - gw - p * 3;
		const bfs = h * 0.09;
		const btY = h - p - button(0, 0, s.cta, h * 0.06).h;
		// Shrink the headline (then drop the sub line) until the text clears the button.
		for (let scale = 1; scale > 0.45; scale -= 0.05) {
			let y = p + bfs;
			let part = brand(tx, y, bfs);
			y += bfs * 0.7;
			const pl = pill(tx, y, s.eyebrow, h * 0.045);
			part += pl.svg;
			y += pl.h + h * 0.05;
			const hd = block(s.long, tWidth, h * 0.16 * scale, 2);
			part += lines(tx, y + hd.fs * 0.8, hd, C.white);
			y += linesH(hd) + h * 0.04;
			const sb = block(s.subShort, tWidth, h * 0.06, 1, false);
			const withSub = y + linesH(sb) + h * 0.05 <= btY;
			if (withSub || scale <= 0.5) {
				if (withSub) part += lines(tx, y + sb.fs * 0.8, sb, C.soft, 400);
				body = part;
				break;
			}
		}
		body += button(tx, btY, s.cta, h * 0.06).svg;
		body += s.graphic(w - gw - p, p * 1.2, gw, h - p * 2.4);
	} else if (a >= 1.15) {
		// Social and rectangles (1200x628, 1280x720, 300x250, 336x280).
		const small = w < 500;
		const gw = small ? 0 : w * 0.38;
		const tWidth = w - gw - p * (small ? 2 : 3);
		const bfs = h * 0.075;
		const bt = button(p, 0, s.cta, h * 0.05);
		const btY = h - p - bt.h;
		// Shrink the headline until brand, pill, headline and sub all sit above the button.
		for (let scale = 1; scale > 0.5; scale -= 0.04) {
			let y = p + bfs;
			let part = brand(p, y, bfs);
			y += bfs * 0.75;
			const pl = pill(p, y, s.eyebrow, Math.min(h * 0.036, tWidth / (s.eyebrow.length * 0.72)));
			part += pl.svg;
			y += pl.h + h * 0.06 * scale;
			const hd = block(s.long, tWidth, h * (small ? 0.12 : 0.115) * scale, 3);
			part += lines(p, y + hd.fs * 0.8, hd, C.white);
			y += linesH(hd) + h * 0.045 * scale;
			const sb = block(small ? s.subShort : s.sub, tWidth, h * 0.045, 2, false);
			part += lines(p, y + sb.fs * 0.8, sb, C.soft, 400, 1.25);
			y += linesH(sb, 1.25) + h * 0.05;
			body = part;
			if (y <= btY) break;
		}
		body += button(p, btY, s.cta, h * 0.05).svg;
		if (!small) body += text(w - p, h - p - bt.h * 0.3, s.url, h * 0.032, C.soft, 600, 'end');
		if (gw) body += s.graphic(w - gw - p, p * 1.6, gw, h - p * 3.6 - bt.h * 0.2);
	} else {
		// Square, tall and story (1080x1080, 160x600, 300x600, 1080x1920).
		const cx = w / 2;
		const inner = w - p * 2;
		const bfs = Math.min(w * 0.1, h * 0.05);
		let y = p + bfs;
		body += brand(cx, y, bfs, 'middle');
		y += bfs * 0.8;
		const pfs = Math.min(w * 0.032, inner / (s.eyebrow.length * 0.72));
		const pl = pill(cx, y, s.eyebrow, pfs, 'middle');
		body += pl.svg;
		y += pl.h + h * 0.035;
		const hd = block(a > 0.9 && s.squareShort ? s.short : s.long, inner, Math.min(w * (a > 0.9 ? 0.075 : 0.11), h * 0.07), w < 200 ? 5 : 3);
		body += lines(cx, y + hd.fs * 0.8, hd, C.white, 700, 1.15, 'middle');
		y += linesH(hd, 1.15) + h * 0.03;
		const sb = block(w < 200 || a > 0.9 ? s.subShort : s.sub, inner, Math.min(w * 0.045, h * 0.028), 3, false);
		body += lines(cx, y + sb.fs * 0.8, sb, C.soft, 400, 1.25, 'middle');
		y += linesH(sb, 1.25) + h * 0.04;
		const bfs2 = Math.min(w * 0.05, h * 0.03, inner / ((s.cta.length + 4) * 0.58));
		const bt = button(cx, 0, s.cta, bfs2, 'middle');
		const by = h - p - bt.h - (w >= 600 ? h * 0.04 : 0);
		const gh = by - y - h * 0.04;
		const gw = Math.min(inner * 0.86, gh * 1.7);
		if (gh > h * 0.12) body += s.graphic(cx - gw / 2, y, gw, gh);
		body += button(cx, by, s.cta, bfs2, 'middle').svg;
		if (w >= 600) body += text(cx, h - p * 0.7, s.url, h * 0.022, C.soft, 600, 'middle');
	}
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${bg(w, h, set === 'product' ? 1 : 0)}${body}</svg>`;
}

// Add-on feature card, 1080x1080. Features and prices from the store.
const CARDS = {
	'seo-pro': ['SEO Pro', 'Rank and get indexed', ['AI titles, descriptions and alt text', 'Automatic internal links and a broken link checker', 'Image, video and news sitemaps', 'Search Console stats and rank tracking'], 'From $42 a year', C.blue],
	'speed-pro': ['Speed Pro', 'Faster pages, fewer plugins', ['Delay analytics, pixel and chat scripts', 'Cache preload and CDN', 'Minify and remove unused CSS', 'Convert images to WebP or AVIF, no quota'], 'From $49 a year', '#14b8a6'],
	'ads-pro': ['Ads Pro', 'More placements, more control', ['More placements in and between posts', 'Targeting by category, post age and dates', 'A/B rotation', 'Click protection and a polite ad-blocker message'], 'From $19 a year', '#f97316'],
	'theme-blocks-pro': ['Theme & Blocks Pro', 'Pro design tools', ['Mega menu, dark mode and table of contents', 'Popups and display conditions', 'Sliders, counters and scroll animations', 'Custom layouts and Pro starter sites'], 'From $39 a year', '#8b5cf6'],
	'all-pro': ['All Pro', 'Every Pro feature, one key', ['SEO Pro, Speed Pro, Ads Pro and Theme & Blocks Pro', 'One plugin, one key, one place for updates', 'Every new Pro feature we add, included', 'Lifetime plans available'], 'From $145 a year', C.gold],
};
function card(slug) {
	const [name, tag, feats, price, col] = CARDS[slug];
	const w = 1080;
	const h = 1080;
	const p = 88;
	let s = bg(w, h, 1);
	s += brand(p, p + 40, 46);
	s += pill(p, p + 76, 'SEMRS PRO FOR WORDPRESS', 22).svg;
	s += `<rect x="${p}" y="${p + 160}" width="96" height="96" rx="24" fill="${col}"/>`;
	s += check(p + 48, p + 208, 26, 'rgba(255,255,255,.25)');
	const hd = block(name, w - p * 2 - 130, 92, 1);
	s += lines(p + 124, p + 236, hd, C.white, 800);
	s += text(p, p + 330, tag, 44, C.soft, 600);
	let y = p + 420;
	for (const f of feats) {
		const b = block(f, w - p * 2 - 80, 40, 2, false);
		s += check(p + 24, y - 12, 22);
		s += lines(p + 70, y, b, C.white, 400, 1.25);
		y += linesH(b, 1.25) + 34;
	}
	s += text(p, h - p - 18, price, 52, C.gold, 800);
	s += text(w - p, h - p - 22, 'semrs.com/semrs-pro', 30, C.soft, 600, 'end');
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${s}</svg>`;
}

// Round partner badge for affiliates' sites, 400x400.
function badge() {
	const w = 400;
	const c = w / 2;
	let s = `<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.navy}"/><stop offset="1" stop-color="${C.navy2}"/></linearGradient></defs>`;
	s += `<circle cx="${c}" cy="${c}" r="196" fill="${C.gold}"/><circle cx="${c}" cy="${c}" r="182" fill="url(#g)"/><circle cx="${c}" cy="${c}" r="164" fill="none" stroke="${C.gold}" stroke-width="2" stroke-dasharray="4 8"/>`;
	s += brand(c, 196, 64, 'middle');
	s += text(c, 244, 'AFFILIATE', 30, C.gold, 800, 'middle');
	s += text(c, 280, 'PARTNER', 30, C.gold, 800, 'middle');
	s += check(c, 128, 22);
	return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${w}" viewBox="0 0 ${w} ${w}">${s}</svg>`;
}

const SIZES = [
	['leaderboard', 728, 90], ['billboard', 970, 250], ['mobile-banner', 320, 100], ['email-header', 600, 200], ['x-header', 1500, 500],
	['medium-rectangle', 300, 250], ['large-rectangle', 336, 280], ['social-post', 1200, 628], ['video-thumbnail', 1280, 720],
	['skyscraper', 160, 600], ['half-page', 300, 600], ['square', 1080, 1080], ['story', 1080, 1920],
];

async function save(dir, name, svg, w, h) {
	fs.mkdirSync(path.join(OUT, dir), { recursive: true });
	const base = path.join(OUT, dir, name);
	fs.writeFileSync(base + '.svg', svg);
	const img = sharp(Buffer.from(svg), { density: 72 });
	await img.clone().png({ compressionLevel: 9, palette: w * h <= 400000, quality: 90 }).toFile(base + '.png');
	await img.clone().webp({ quality: 88 }).toFile(base + '.webp');
	const kb = (f) => Math.round(fs.statSync(f).size / 1024);
	return { dir, name, w, h, png: kb(base + '.png'), webp: kb(base + '.webp') };
}

const made = [];
for (const set of Object.keys(SETS)) for (const [name, w, h] of SIZES) made.push(await save(set, `semrs-${set}-${name}-${w}x${h}`, banner(set, w, h), w, h));
for (const slug of Object.keys(CARDS)) made.push(await save('cards', `semrs-card-${slug}`, card(slug), 1080, 1080));
made.push(await save('cards', 'semrs-affiliate-partner-badge', badge(), 400, 400));

const groups = { affiliate: 'Affiliate program (for semrs.com and social)', product: 'SEMRS Pro banners (for partners to place)', cards: 'Feature cards and partner badge' };
let html = `<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SEMRS promo kit</title><style>body{font:16px/1.5 system-ui,sans-serif;margin:0;padding:24px;background:#f4f6fb;color:#0b1f3a}h1{margin:0 0 4px}h2{margin:32px 0 12px}.g{display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start}figure{margin:0;background:#fff;border-radius:12px;padding:12px;box-shadow:0 2px 8px rgba(11,31,58,.08)}img{display:block;max-width:100%;height:auto}figcaption{font-size:13px;margin-top:8px;color:#475569}</style><h1>SEMRS promo kit</h1><p>DRAFT for approval. ${made.length} designs, each as PNG and WebP.</p>`;
for (const [g, label] of Object.entries(groups)) {
	html += `<h2>${label}</h2><div class="g">`;
	for (const m of made.filter((x) => x.dir === g)) {
		const disp = Math.min(m.w, m.w > 700 ? 560 : m.w, m.h > 700 ? Math.round((700 * m.w) / m.h) : m.w);
		html += `<figure style="width:${disp}px"><img src="${m.dir}/${m.name}.webp" width="${m.w}" height="${m.h}" alt="${m.name}"><figcaption>${m.name.replace('semrs-', '')}<br>${m.w}×${m.h} · PNG ${m.png} KB · WebP ${m.webp} KB</figcaption></figure>`;
	}
	html += '</div>';
}
fs.writeFileSync(path.join(OUT, 'index.html'), html + '</html>');

// The update server serves the kit to partners (their dashboard) and to the
// site itself: copy the PNG and WebP files into the plugin.
const PLUGIN = 'plugins/semrs-update-server/assets/promo';
for (const m of made) {
	fs.mkdirSync(path.join(PLUGIN, m.dir), { recursive: true });
	for (const ext of ['png', 'webp']) fs.copyFileSync(path.join(OUT, m.dir, `${m.name}.${ext}`), path.join(PLUGIN, m.dir, `${m.name}.${ext}`));
}
console.log(made.length, 'designs →', OUT);
