// Recompress package images in place (same name, same format) to keep release zips small.
//   node compress-images.mjs            → all packages
// JPEG: mozjpeg q78 · WebP: q76 effort 6 · PNG: palette (quality 90), only for art/screenshots.
// A file is replaced only when the new copy is at least 8% smaller.
import fs from 'fs';
import path from 'path';
import sharp from 'sharp';

const ROOTS = ['plugins/semrs-blocks', 'plugins/semrs-pro', 'plugins/semrs-shops', 'plugins/semrs-update-server/assets', 'plugins/semrs-site/assets', 'plugins/semrs-core', 'plugins/semrs-tools', 'theme/semrs', 'theme/semrs-child-seo'];
const walk = (d) => fs.existsSync(d) ? fs.readdirSync(d).flatMap((f) => { const p = path.join(d, f); return fs.statSync(p).isDirectory() ? (f === 'node_modules' ? [] : walk(p)) : [p]; }) : [];
let before = 0, after = 0, changed = 0;
for (const root of ROOTS) {
	for (const f of walk(root)) {
		const ext = path.extname(f).toLowerCase();
		if (!['.jpg', '.jpeg', '.webp', '.png'].includes(ext)) continue;
		const src = fs.readFileSync(f);
		let out;
		try {
			const img = sharp(src, { animated: ext === '.webp' });
			if (ext === '.png') out = await img.png({ palette: true, quality: 90, effort: 10, compressionLevel: 9 }).toBuffer();
			else if (ext === '.webp') out = await img.webp({ quality: 76, effort: 6, smartSubsample: true }).toBuffer();
			else out = await img.jpeg({ quality: 78, mozjpeg: true, progressive: true }).toBuffer();
		} catch (e) { console.log('skip', f, e.message); continue; }
		before += src.length;
		if (out.length < src.length * 0.92) { fs.writeFileSync(f, out); after += out.length; changed++; } else after += src.length;
	}
}
console.log(`images: ${changed} recompressed, ${(before / 1048576).toFixed(2)} MB → ${(after / 1048576).toFixed(2)} MB`);
