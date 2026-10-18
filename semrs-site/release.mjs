// SEMRS release builder.
//
//   node release.mjs --bump=minor --notes="Logo size controls|Footer social icons"
//   node release.mjs --only=semrs-core --bump=patch --notes="Fix canonical on page 2"
//   node release.mjs --set=semrs-pro:2.0.0 --notes="..."
//
// For each package: bumps Version headers, *_VERSION constants and readme
// "Stable tag", prepends a changelog entry, copies shared/semrs-updater.php
// into the package, then builds:
//   dist/releases/<slug>-<version>.zip   upload in SEMRS Updates → Releases (semrs.com)
//   dist/wporg/<slug>.zip                WordPress.org build: no updater, no Update URI (free packages only)
//   dist/releases/releases.json          manifest of this release
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';

const PACKAGES = [
	{ slug: 'semrs', type: 'theme', dir: 'theme/semrs', main: 'style.css', updater: 'inc/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_THEME_VERSION', constFile: 'functions.php', free: true },
	{ slug: 'semrs-child-seo', type: 'theme', dir: 'theme/semrs-child-seo', main: 'style.css', updater: 'inc/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_CHILD_VERSION', constFile: 'functions.php', free: true },
	{ slug: 'semrs-core', type: 'plugin', dir: 'plugins/semrs-core', main: 'semrs-core.php', updater: 'includes/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_CORE_VERSION', constFile: 'semrs-core.php', free: true },
	{ slug: 'semrs-tools', type: 'plugin', dir: 'plugins/semrs-tools', main: 'semrs-tools.php', updater: 'includes/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_TOOLS_VERSION', constFile: 'semrs-tools.php', free: true },
	{ slug: 'semrs-blocks', type: 'plugin', dir: 'plugins/semrs-blocks', main: 'semrs-blocks.php', updater: 'includes/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_BLOCKS_VERSION', constFile: 'semrs-blocks.php', free: true },
	{ slug: 'semrs-shops', type: 'plugin', dir: 'plugins/semrs-shops', main: 'semrs-shops.php', updater: 'includes/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_SHOPS_VERSION', constFile: 'semrs-shops.php', free: true },
	{ slug: 'semrs-pro', type: 'plugin', dir: 'plugins/semrs-pro', main: 'semrs-pro.php', updater: 'includes/semrs-updater.php', readme: 'readme.txt', constant: 'SEMRS_PRO_VERSION', constFile: 'semrs-pro.php', free: false },
];
const TAR = 'C:/Windows/System32/tar.exe'; // Writes real zip files (Git Bash tar does not).

const args = Object.fromEntries(process.argv.slice(2).map((a) => { const [k, ...v] = a.replace(/^--/, '').split('='); return [k, v.join('=') || true]; }));
const bump = args.bump || 'patch';
const only = args.only ? String(args.only).split(',') : null;
const sets = Object.fromEntries(String(args.set || '').split(',').filter(Boolean).map((s) => s.split(':')));
const notes = String(args.notes || 'Maintenance release.').split('|').map((n) => n.trim()).filter(Boolean);
const today = new Date().toISOString().slice(0, 10);

const next = (v, how) => {
	const [a, b, c] = v.split('.').map((x) => parseInt(x, 10) || 0);
	return how === 'major' ? `${a + 1}.0.0` : how === 'minor' ? `${a}.${b + 1}.0` : `${a}.${b}.${c + 1}`;
};
const read = (f) => fs.readFileSync(f, 'utf8');
const write = (f, s) => fs.writeFileSync(f, s);
const zip = (cwd, out, folder) => { fs.mkdirSync(path.dirname(out), { recursive: true }); if (fs.existsSync(out)) { fs.unlinkSync(out); } execFileSync(TAR, ['-a', '-cf', path.resolve(out), folder], { cwd }); };

fs.mkdirSync('dist/releases', { recursive: true });
fs.mkdirSync('dist/wporg', { recursive: true });
const manifest = { date: today, notes, packages: {} };

for (const p of PACKAGES) {
	if (only && !only.includes(p.slug)) { continue; }
	const mainPath = path.join(p.dir, p.main);
	let main = read(mainPath);
	const cur = (main.match(/^[ \t\/*#@]*Version:\s*([0-9.]+)/m) || [])[1];
	if (!cur) { throw new Error('No Version header in ' + mainPath); }
	const ver = sets[p.slug] || next(cur, bump);

	// 1. Version header (+ constant when in the same file).
	main = main.replace(/^([ \t\/*#@]*Version:\s*)[0-9.]+/m, `$1${ver}`);
	write(mainPath, main);
	const cPath = path.join(p.dir, p.constFile);
	write(cPath, read(cPath).replace(new RegExp(`(define\\(\\s*'${p.constant}',\\s*')[0-9.]+(')`), `$1${ver}$2`));

	// 2. Readme: Stable tag + changelog entry.
	if (p.readme && fs.existsSync(path.join(p.dir, p.readme))) {
		const rPath = path.join(p.dir, p.readme);
		let r = read(rPath).replace(/^Stable tag:\s*[0-9.]+/m, `Stable tag: ${ver}`);
		const entry = `= ${ver} =\n${notes.map((n) => '* ' + n).join('\n')}\n\n`;
		r = /== Changelog ==\n+/.test(r) ? r.replace(/== Changelog ==\n+/, `== Changelog ==\n\n${entry}`) : r.trimEnd() + `\n\n== Changelog ==\n\n${entry}`;
		write(rPath, r);
	}

	// 3. Updater file (self-hosted builds).
	fs.mkdirSync(path.dirname(path.join(p.dir, p.updater)), { recursive: true });
	fs.copyFileSync('shared/semrs-updater.php', path.join(p.dir, p.updater));

	// 4. Self-hosted zip.
	const parent = path.dirname(p.dir);
	const folder = path.basename(p.dir);
	const relZip = `dist/releases/${p.slug}-${ver}.zip`;
	zip(parent, relZip, folder);

	// 5. WordPress.org build (free packages): strip updater + Update URI in a temp copy.
	if (p.free) {
		const tmp = path.join('dist', '.wporg-tmp');
		fs.rmSync(tmp, { recursive: true, force: true });
		fs.cpSync(p.dir, path.join(tmp, folder), { recursive: true });
		fs.rmSync(path.join(tmp, folder, p.updater), { force: true });
		const tMain = path.join(tmp, folder, p.main);
		write(tMain, read(tMain).replace(/^[ \t*]*Update URI:.*\n/m, ''));
		zip(tmp, `dist/wporg/${p.slug}.zip`, folder);
		fs.rmSync(tmp, { recursive: true, force: true });
	}

	manifest.packages[p.slug] = { type: p.type, from: cur, version: ver, zip: relZip, wporg: p.free ? `dist/wporg/${p.slug}.zip` : null, bytes: fs.statSync(relZip).size };
	console.log(`✔ ${p.slug.padEnd(16)} ${cur} → ${ver}   ${relZip}`);
}
write('dist/releases/releases.json', JSON.stringify(manifest, null, '\t'));
write('dist/releases/CHANGELOG.md', `# SEMRS release ${today}\n\n${notes.map((n) => '- ' + n).join('\n')}\n\n| Package | From | To |\n| --- | --- | --- |\n${Object.entries(manifest.packages).map(([s, m]) => `| ${s} | ${m.from} | ${m.version} |`).join('\n')}\n`);
console.log('\nManifest: dist/releases/releases.json');
