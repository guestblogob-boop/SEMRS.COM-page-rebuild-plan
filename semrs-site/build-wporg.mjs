// WordPress.org builds of the free packages at their CURRENT versions (no version bump):
//   node build-wporg.mjs            → dist/wporg/<slug>.zip
// Same steps as release.mjs step 5: copy, remove the semrs.com updater, drop the Update URI
// header, zip. Then checks each zip: no updater file, no Update URI, no semrs.com update calls.
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';

const TAR = 'C:/Windows/System32/tar.exe';
const PKGS = [
	['semrs', 'theme/semrs', 'style.css', 'inc/semrs-updater.php'],
	['semrs-child-seo', 'theme/semrs-child-seo', 'style.css', 'inc/semrs-updater.php'],
	['semrs-core', 'plugins/semrs-core', 'semrs-core.php', 'includes/semrs-updater.php'],
	['semrs-tools', 'plugins/semrs-tools', 'semrs-tools.php', 'includes/semrs-updater.php'],
	['semrs-blocks', 'plugins/semrs-blocks', 'semrs-blocks.php', 'includes/semrs-updater.php'],
	['semrs-shops', 'plugins/semrs-shops', 'semrs-shops.php', 'includes/semrs-updater.php'],
];
const walk = (d) => fs.readdirSync(d).flatMap((f) => { const p = path.join(d, f); return fs.statSync(p).isDirectory() ? walk(p) : [p]; });
fs.mkdirSync('dist/wporg', { recursive: true });
for (const [slug, dir, main, updater] of PKGS) {
	const tmp = path.join('dist', '.wporg-tmp');
	const folder = path.basename(dir);
	fs.rmSync(tmp, { recursive: true, force: true });
	fs.cpSync(dir, path.join(tmp, folder), { recursive: true });
	fs.rmSync(path.join(tmp, folder, updater), { force: true });
	const m = path.join(tmp, folder, main);
	fs.writeFileSync(m, fs.readFileSync(m, 'utf8').replace(/^[ \t*]*Update URI:.*\n/m, ''));
	// Checks.
	const files = walk(path.join(tmp, folder));
	const problems = [];
	for (const f of files.filter((f) => /\.(php|js)$/.test(f))) {
		const s = fs.readFileSync(f, 'utf8');
		if (/semrs-updates\/v1/.test(s)) problems.push('update call in ' + path.relative(tmp, f));
		if (/^[ \t*]*Update URI:/m.test(s)) problems.push('Update URI in ' + path.relative(tmp, f));
	}
	if (files.some((f) => /semrs-updater\.php$/.test(f))) problems.push('updater file left');
	const zip = `dist/wporg/${slug}.zip`;
	fs.rmSync(zip, { force: true });
	execFileSync(TAR, ['-a', '-cf', path.resolve(zip), folder], { cwd: tmp });
	fs.rmSync(tmp, { recursive: true, force: true });
	const ver = (fs.readFileSync(path.join(dir, main), 'utf8').match(/Version:\s*([0-9.]+)/) || [])[1];
	console.log(`${problems.length ? '✖' : '✔'} ${slug.padEnd(16)} ${ver.padEnd(8)} ${(fs.statSync(zip).size / 1024).toFixed(0).padStart(6)} KB ${files.length} files${problems.length ? '  ' + problems.join('; ') : ''}`);
}
