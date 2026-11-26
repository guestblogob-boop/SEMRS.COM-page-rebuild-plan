// Default share image for semrs.com (SEO → Default share image URL, 1200×630).
// Used when a page has no featured image of its own (for example category pages).
//   node content-src/brand/share-image.mjs   (from the semrs-site folder; needs sharp)
import fs from 'fs';
import path from 'path';
import { createRequire } from 'module';
const sharp = createRequire(path.resolve('x.js'))('sharp');

const W = 1200, H = 630, F = 'Segoe UI, Arial, Helvetica, sans-serif';
const ICON = {
	search: '<circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/>',
	pin: '<path d="M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
	megaphone: '<path d="M3 10v4h4l8 5V5L7 10z"/><path d="M19 9a4 4 0 0 1 0 6"/>',
	doc: '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h8M9 17h6"/>',
	star: '<path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z"/>',
};
const icon = (n, x, y, s, c) => `<g transform="translate(${x},${y}) scale(${s / 24})" fill="none" stroke="${c}" stroke-width="${2.2 * 24 / s}" stroke-linecap="round" stroke-linejoin="round">${ICON[n]}</g>`;

// Browser window with a rising chart (illustration, no data).
const bx = 650, by = 120, bw = 480, bh = 330;
const bars = [70, 105, 92, 140, 175, 215];
const browser = `
<g>
<rect x="${bx + 10}" y="${by + 14}" width="${bw}" height="${bh}" rx="18" fill="#000" fill-opacity=".25"/>
<rect x="${bx}" y="${by}" width="${bw}" height="${bh}" rx="18" fill="#ffffff"/>
<rect x="${bx}" y="${by}" width="${bw}" height="44" rx="18" fill="#eef1ff"/><rect x="${bx}" y="${by + 26}" width="${bw}" height="18" fill="#eef1ff"/>
<circle cx="${bx + 24}" cy="${by + 22}" r="6" fill="#f7941d"/><circle cx="${bx + 44}" cy="${by + 22}" r="6" fill="#f6c343"/><circle cx="${bx + 64}" cy="${by + 22}" r="6" fill="#0f7b4d"/>
<rect x="${bx + 90}" y="${by + 12}" width="300" height="20" rx="10" fill="#ffffff"/>${icon('search', bx + 98, by + 14, 16, '#5a6383')}
<text x="${bx + 122}" y="${by + 27}" font-family="${F}" font-size="13" fill="#5a6383">semrs.com</text>
${bars.map((h, i) => `<rect x="${bx + 46 + i * 66}" y="${by + bh - 34 - h}" width="40" height="${h}" rx="8" fill="${i === bars.length - 1 ? '#f7941d' : '#0b2fd1'}" fill-opacity="${i === bars.length - 1 ? 1 : 0.35 + i * 0.12}"/>`).join('')}
<path d="M ${bx + 66} ${by + bh - 120} C ${bx + 160} ${by + bh - 150}, ${bx + 240} ${by + bh - 170}, ${bx + 316} ${by + bh - 205} S ${bx + 400} ${by + bh - 270}, ${bx + 426} ${by + bh - 272}" fill="none" stroke="#f7941d" stroke-width="5" stroke-linecap="round"/>
<path d="M ${bx + 410} ${by + bh - 286} l 20 12 l -22 8" fill="none" stroke="#f7941d" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
<line x1="${bx + 30}" y1="${by + bh - 34}" x2="${bx + bw - 30}" y2="${by + bh - 34}" stroke="#dfe3ef" stroke-width="3"/>
</g>`;

// Floating badges around the window.
const badge = (x, y, n, label, c) => `<g><rect x="${x}" y="${y}" width="${label.length * 11 + 70}" height="50" rx="25" fill="#ffffff"/><circle cx="${x + 25}" cy="${y + 25}" r="17" fill="${c}"/>${icon(n, x + 15, y + 15, 20, '#ffffff')}<text x="${x + 52}" y="${y + 32}" font-family="${F}" font-size="19" font-weight="800" fill="#151b33">${label}</text></g>`;
const badges = [
	badge(590, 92, 'search', 'SEO', '#0b2fd1'),
	badge(1010, 74, 'star', 'Reviews', '#f7941d'),
	badge(600, 470, 'pin', 'Local', '#0f7b4d'),
	badge(800, 488, 'megaphone', 'Ads', '#f7941d'),
	badge(960, 470, 'doc', 'Websites', '#0b2fd1'),
].join('');

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0a1650"/><stop offset="1" stop-color="#0b2fd1"/></linearGradient>
<pattern id="dots" width="26" height="26" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.6" fill="#ffffff" fill-opacity=".09"/></pattern></defs>
<rect width="${W}" height="${H}" fill="url(#g)"/><rect width="${W}" height="${H}" fill="url(#dots)"/>
<circle cx="900" cy="300" r="300" fill="#ffffff" fill-opacity=".04"/><circle cx="1150" cy="600" r="150" fill="#f7941d" fill-opacity=".12"/>
${browser}${badges}
<rect x="70" y="110" width="110" height="10" rx="5" fill="#f7941d"/>
<text x="70" y="236" font-family="${F}" font-size="112" font-weight="900" fill="#ffffff" letter-spacing="5">SEMRS</text>
<text x="70" y="290" font-family="${F}" font-size="22" font-weight="600" fill="#c9d2f5">Search Engine Marketing &amp; Ranking Solutions</text>
<text x="70" y="378" font-family="${F}" font-size="38" font-weight="800" fill="#ffffff">SEO, websites and</text>
<text x="70" y="426" font-family="${F}" font-size="38" font-weight="800" fill="#ffffff">marketing for small</text>
<text x="70" y="474" font-family="${F}" font-size="38" font-weight="800" fill="#ffffff">businesses</text>
<rect x="70" y="512" width="230" height="54" rx="27" fill="#f7941d"/><text x="185" y="548" text-anchor="middle" font-family="${F}" font-size="25" font-weight="800" fill="#0a1650">semrs.com</text>
</svg>`;
const out = path.join('content-src', 'brand', 'semrs-share-1200x630.jpg');
fs.mkdirSync(path.dirname(out), { recursive: true });
await sharp(Buffer.from(svg)).jpeg({ quality: 86, mozjpeg: true }).toFile(out);
console.log(out, Math.round(fs.statSync(out).size / 1024) + ' KB');
