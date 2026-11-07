// Render manual diagrams to 1200×675 WebP: node pages/support/render-graphics.mjs [key ...]
// (no keys = all). Output: pages/support/images/<key>.webp
import sharp from 'sharp';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { GRAPHICS } from './graphics.mjs';

const dir = path.join(path.dirname(fileURLToPath(import.meta.url)), 'images');
const keys = process.argv.slice(2).length ? process.argv.slice(2) : Object.keys(GRAPHICS);
for (const k of keys) {
	if (!GRAPHICS[k]) { console.log('unknown', k); continue; }
	await sharp(Buffer.from(GRAPHICS[k].svg())).webp({ quality: 88 }).toFile(path.join(dir, `${k}.webp`));
	console.log('wrote', `${k}.webp`);
}
