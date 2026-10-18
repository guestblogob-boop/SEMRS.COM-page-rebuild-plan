// Builds WordPress.org-style copies (no updater, no Update URI) for Plugin Check / Theme Check.
// Usage (from semrs-site/): node dist/mkcheck.cjs <checkers dir>
const fs = require('fs');
const path = require('path');
const out = 'dist/.check';
const pk = [
	['theme/semrs', 'themes', 'inc/semrs-updater.php', 'style.css'],
	['theme/semrs-child-seo', 'themes', 'inc/semrs-updater.php', 'style.css'],
	['plugins/semrs-core', 'plugins', 'includes/semrs-updater.php', 'semrs-core.php'],
	['plugins/semrs-blocks', 'plugins', 'includes/semrs-updater.php', 'semrs-blocks.php'],
	['plugins/semrs-tools', 'plugins', 'includes/semrs-updater.php', 'semrs-tools.php'],
	['plugins/semrs-shops', 'plugins', 'includes/semrs-updater.php', 'semrs-shops.php'],
	['plugins/semrs-pro', 'plugins', null, null], // Not for WordPress.org: checked for code quality only.
];
fs.rmSync(out, { recursive: true, force: true });
for (const [dir, kind, updater, main] of pk) {
	const dest = path.join(out, kind, path.basename(dir));
	fs.cpSync(dir, dest, { recursive: true });
	if (updater) {
		fs.rmSync(path.join(dest, updater), { force: true });
		const m = path.join(dest, main);
		fs.writeFileSync(m, fs.readFileSync(m, 'utf8').replace(/^[ \t*]*Update URI:.*\n/m, ''));
	}
}
const src = process.argv[2];
if (process.argv[2]) for (const p of ['plugin-check', 'theme-check']) {
	fs.cpSync(path.join(src, 'x-' + p, p), path.join(out, 'checkers', p), { recursive: true });
}
// The checks site installs Plugin Check and Theme Check from WordPress.org (see check-blueprint.json).
fs.copyFileSync('dist/check-blueprint.json', path.join(out, 'blueprint.json'));
console.log('ready:', fs.readdirSync(path.join(out, 'plugins')).join(', '), '|', fs.readdirSync(path.join(out, 'themes')).join(', '));
