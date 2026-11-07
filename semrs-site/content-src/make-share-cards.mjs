// Make 1200×630 blue share cards (same design as assets/share/share-*.webp).
// Usage: node content-src/make-share-cards.mjs  → writes assets/share/share-<slug>.webp
import sharp from 'sharp';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const dir = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'assets', 'share');

const cards = [
	['on-page-seo-checker', 'Free On-Page SEO Checker'],
	['robots-txt-tester', 'Free Robots.txt Tester'],
	['open-graph-generator', 'Free Open Graph Generator'],
	['hreflang-generator', 'Free Hreflang Tag Generator'],
	['redirect-rules-generator', 'Free Redirect Rules Generator'],
	['readability-checker', 'Free Readability Checker'],
	['roas-calculator', 'Free ROAS Calculator'],
	['color-contrast-checker', 'Free Color Contrast Checker'],
];

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');

// Two balanced lines when the title is longer than one line holds (~22 characters at 80px).
function lines(t) {
	if (t.length <= 22) return [t];
	const w = t.split(' ');
	let best = null;
	for (let i = 1; i < w.length; i++) {
		const a = w.slice(0, i).join(' '), b = w.slice(i).join(' ');
		const d = Math.max(a.length, b.length);
		if (!best || d < best[0]) best = [d, a, b];
	}
	return [best[1], best[2]];
}

function svg(title) {
	const l = lines(title);
	const top = l.length === 1 ? 300 : 252; // Baselines as in the existing cards.
	const bar = l.length === 1 ? 340 : 380;
	const sub = l.length === 1 ? 409 : 449;
	const t = l.map((s, i) => `<text x="80" y="${top + i * 86}" class="h">${esc(s)}</text>`).join('');
	return `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0d2ed0"/><stop offset=".5" stop-color="#0a1e82"/><stop offset="1" stop-color="#131736"/></linearGradient>
<style>.h{font:700 80px 'Segoe UI',Arial,sans-serif;fill:#fff}.s{font:400 33.5px 'Segoe UI',Arial,sans-serif;fill:#ecd8c8}.b{font:700 30px 'Segoe UI',Arial,sans-serif;fill:#fff}</style></defs>
<rect width="1200" height="630" fill="url(#g)"/>
<circle cx="1050" cy="150" r="190" fill="#f59e0b" fill-opacity=".16"/>
<circle cx="1160" cy="570" r="150" fill="#f59e0b" fill-opacity=".08"/>
${t}
<rect x="80" y="${bar}" width="110" height="10" rx="5" fill="#fa931d"/>
<text x="80" y="${sub}" class="s">Free SEO tool · No signup</text>
<text x="1130" y="573" class="b" text-anchor="end">SEMRS</text>
</svg>`;
}

for (const [slug, title] of cards) {
	const out = path.join(dir, `share-${slug}.webp`);
	await sharp(Buffer.from(svg(title))).webp({ quality: 86 }).toFile(out);
	console.log('wrote', path.basename(out));
}
