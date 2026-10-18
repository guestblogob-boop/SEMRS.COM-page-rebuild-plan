// Builds languages/<domain>.pot for every SEMRS package from PHP sources
// (and theme/plugin headers). No WP-CLI needed.
//
//   node build-pot.mjs
import fs from 'fs';
import path from 'path';

const PACKAGES = [
	{ dir: 'theme/semrs', domain: 'semrs', name: 'SEMRS', header: 'style.css' },
	{ dir: 'theme/semrs-child-seo', domain: 'semrs-child-seo', name: 'SEMRS Child SEO', header: 'style.css' },
	{ dir: 'plugins/semrs-core', domain: 'semrs-core', name: 'SEMRS Core', header: 'semrs-core.php' },
	{ dir: 'plugins/semrs-blocks', domain: 'semrs-blocks', name: 'SEMRS Blocks', header: 'semrs-blocks.php' },
	{ dir: 'plugins/semrs-tools', domain: 'semrs-tools', name: 'SEMRS Tools', header: 'semrs-tools.php' },
	{ dir: 'plugins/semrs-pro', domain: 'semrs-pro', name: 'SEMRS Pro', header: 'semrs-pro.php' },
];

// fn => argument roles: s = singular, p = plural, c = context, d = domain, n = number (ignored).
const FNS = {
	__: 'sd', _e: 'sd', esc_html__: 'sd', esc_html_e: 'sd', esc_attr__: 'sd', esc_attr_e: 'sd',
	_x: 'scd', _ex: 'scd', esc_html_x: 'scd', esc_attr_x: 'scd',
	_n: 'spnd', _nx: 'spncd', _n_noop: 'spd', _nx_noop: 'spcd',
};

const walk = (d, out = []) => {
	for (const f of fs.readdirSync(d)) {
		const p = path.join(d, f);
		if (fs.statSync(p).isDirectory()) {
			if (!['node_modules', 'vendor', 'languages', '.git'].includes(f)) walk(p, out);
		} else if (p.endsWith('.php')) out.push(p);
	}
	return out;
};

// Read one PHP string literal at s[i] ('...' or "..."); returns [value, nextIndex] or null.
function readString(s, i) {
	const q = s[i];
	if (q !== "'" && q !== '"') return null;
	let v = '';
	for (let j = i + 1; j < s.length; j++) {
		const ch = s[j];
		if (ch === '\\') {
			const nx = s[j + 1];
			if (q === "'") { v += (nx === "'" || nx === '\\') ? nx : '\\' + nx; }
			else { v += ({ n: '\n', t: '\t', '"': '"', '\\': '\\', $: '$' })[nx] ?? '\\' + nx; }
			j++;
		} else if (ch === q) {
			return [v, j + 1];
		} else v += ch;
	}
	return null;
}

// Parse call arguments starting after "(": returns array of string|null (null = not a literal).
function readArgs(s, i) {
	const args = [];
	let depth = 0, cur = null, literal = true, started = false;
	for (let j = i; j < s.length; j++) {
		const ch = s[j];
		if (/\s/.test(ch)) continue;
		if ((ch === "'" || ch === '"')) {
			const r = readString(s, j);
			if (!r) return args;
			if (depth === 0) { if (started && cur !== null) literal = false; cur = (cur ?? '') + r[0]; started = true; }
			j = r[1] - 1;
			continue;
		}
		if (depth === 0 && ch === '.') continue; // concatenated literals
		if (ch === '(' || ch === '[') { depth++; literal = false; started = true; continue; }
		if (ch === ')' || ch === ']') {
			if (depth === 0) { args.push(literal ? cur : null); return args; }
			depth--; continue;
		}
		if (depth === 0 && ch === ',') { args.push(literal ? cur : null); cur = null; literal = true; started = false; continue; }
		if (depth === 0) { literal = false; started = true; }
	}
	return args;
}

const esc = (t) => t.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\t/g, '\\t').replace(/\n/g, '\\n"\n"');
const po = (t) => (t.includes('\n') ? '""\n"' + esc(t) + '"' : '"' + esc(t) + '"');

for (const pkg of PACKAGES) {
	const entries = new Map(); // key => {s,p,c,refs:Set,comments:Set}
	const add = (e, ref, comment) => {
		const key = (e.c ?? '') + '\u0004' + e.s;
		const cur = entries.get(key) || { ...e, refs: new Set(), comments: new Set() };
		cur.refs.add(ref);
		if (comment) cur.comments.add(comment);
		if (e.p && !cur.p) cur.p = e.p;
		entries.set(key, cur);
	};
	// Header strings.
	const head = fs.readFileSync(path.join(pkg.dir, pkg.header), 'utf8');
	const kind = pkg.header === 'style.css' ? 'Theme' : 'Plugin';
	for (const [field, label] of [[`${kind} Name`, `Name of the ${kind.toLowerCase()}`], ['Description', `Description of the ${kind.toLowerCase()}`], [`${kind} URI`, `URI of the ${kind.toLowerCase()}`], ['Author', `Author of the ${kind.toLowerCase()}`]]) {
		const m = head.match(new RegExp('^[ \\t\\/*#@]*' + field + ':\\s*(.+)$', 'm'));
		if (m) add({ s: m[1].trim() }, pkg.header, label);
	}
	const files = walk(pkg.dir);
	let calls = 0, wrongDomain = [];
	for (const file of files) {
		const src = fs.readFileSync(file, 'utf8');
		const rel = path.relative(pkg.dir, file).replace(/\\/g, '/');
		const re = /(?<![\w$>:])(__|_e|_ex|_x|_n|_nx|_n_noop|_nx_noop|esc_html__|esc_html_e|esc_attr__|esc_attr_e|esc_html_x|esc_attr_x)\s*\(/g;
		let m;
		while ((m = re.exec(src))) {
			const args = readArgs(src, m.index + m[0].length);
			const roles = FNS[m[1]];
			const e = {};
			roles.split('').forEach((r, k) => { if (r !== 'n') e[r] = args[k]; });
			if (typeof e.s !== 'string' || typeof e.d !== 'string') continue;
			if (e.d !== pkg.domain) { wrongDomain.push(`${rel}: ${e.d}`); continue; }
			const line = src.slice(0, m.index).split('\n').length;
			// Translator comment within the previous 3 lines.
			const before = src.slice(Math.max(0, m.index - 400), m.index);
			const tc = before.match(/\/\*\s*translators:([\s\S]*?)\*\/(?![\s\S]*\/\*\s*translators:)[^\n]*\n?[^\n]*$|\/\/\s*translators:([^\n]*)\n[^\n]*$/i);
			const comment = tc ? 'translators:' + (tc[1] ?? tc[2]).replace(/\s+/g, ' ').trimEnd() : '';
			add({ s: e.s, p: typeof e.p === 'string' ? e.p : undefined, c: typeof e.c === 'string' ? e.c : undefined }, `${rel}:${line}`, comment);
			calls++;
		}
	}
	const now = new Date().toISOString().replace('T', ' ').slice(0, 16) + '+0000';
	let out = `# Copyright (C) ${new Date().getFullYear()} SEMRS\n# This file is distributed under the GPL-2.0-or-later.\nmsgid ""\nmsgstr ""\n"Project-Id-Version: ${pkg.name}\\n"\n"Report-Msgid-Bugs-To: https://semrs.com/support/\\n"\n"POT-Creation-Date: ${now}\\n"\n"MIME-Version: 1.0\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Content-Transfer-Encoding: 8bit\\n"\n"Language-Team: LANGUAGE <LL@li.org>\\n"\n"X-Domain: ${pkg.domain}\\n"\n\n`;
	for (const e of entries.values()) {
		for (const c of e.comments) out += `#. ${c}\n`;
		out += `#: ${[...e.refs].slice(0, 12).join(' ')}\n`;
		if (/%(\d+\$)?[sd]/.test(e.s)) out += '#, php-format\n';
		if (e.c !== undefined) out += `msgctxt ${po(e.c)}\n`;
		out += `msgid ${po(e.s)}\n`;
		if (e.p) out += `msgid_plural ${po(e.p)}\nmsgstr[0] ""\nmsgstr[1] ""\n\n`;
		else out += 'msgstr ""\n\n';
	}
	fs.mkdirSync(path.join(pkg.dir, 'languages'), { recursive: true });
	fs.writeFileSync(path.join(pkg.dir, 'languages', pkg.domain + '.pot'), out);
	console.log(`${pkg.domain.padEnd(16)} ${String(entries.size).padStart(4)} strings (${calls} calls)${wrongDomain.length ? '  ⚠ other text domains: ' + [...new Set(wrongDomain)].slice(0, 6).join(', ') : ''}`);
}
