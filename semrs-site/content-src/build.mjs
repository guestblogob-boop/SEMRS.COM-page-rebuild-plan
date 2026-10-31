// Converts content-src/tools/*.md into WordPress block markup.
// Outputs:
//   plugins/semrs-tools/content/<slug>.html   (used by "Create draft pages")
//   plugins/semrs-tools/content/pages.json    (title, SEO, schema, parent per page)
//   pages/tools/<slug>/page.html + seo.md     (paste-ready package)
// Run: node content-src/build.mjs   (from semrs-site/)
import fs from 'fs';
import path from 'path';

const SRC = 'content-src/tools';
const OUT_PLUGIN = 'plugins/semrs-tools/content';
const OUT_PAGES = 'pages/tools';
fs.mkdirSync(OUT_PLUGIN, { recursive: true });
// Pages with 'site: semrs' are semrs.com-only (never shipped in SEMRS Tools).
const OUT_SITE = 'plugins/semrs-site/content';
fs.mkdirSync(OUT_SITE, { recursive: true });
const sitePages = {};

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const inline = (s) => esc(s)
	.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
	.replace(/`([^`]+)`/g, '<code>$1</code>')
	.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (m, t, u) => `<a href="${u.replace(/"/g, '&quot;')}">${t}</a>`);

const P = (t, cls) => cls
	? `<!-- wp:paragraph {"className":"${cls}"} -->\n<p class="${cls}">${inline(t)}</p>\n<!-- /wp:paragraph -->`
	: `<!-- wp:paragraph -->\n<p>${inline(t)}</p>\n<!-- /wp:paragraph -->`;
const H = (t, lvl) => lvl === 2
	? `<!-- wp:heading -->\n<h2 class="wp-block-heading">${inline(t)}</h2>\n<!-- /wp:heading -->`
	: `<!-- wp:heading {"level":${lvl}} -->\n<h${lvl} class="wp-block-heading">${inline(t)}</h${lvl}>\n<!-- /wp:heading -->`;
const LIST = (items, ordered) => {
	const tag = ordered ? 'ol' : 'ul';
	const open = ordered ? '<!-- wp:list {"ordered":true} -->' : '<!-- wp:list -->';
	return `${open}\n<${tag} class="wp-block-list">${items.map((i) => `<!-- wp:list-item -->\n<li>${inline(i)}</li>\n<!-- /wp:list-item -->`).join('')}</${tag}>\n<!-- /wp:list -->`;
};
const SC = (code) => `<!-- wp:shortcode -->\n${code}\n<!-- /wp:shortcode -->`;
const FAQ = (pairs) => `<!-- wp:group {"className":"semrs-faq","layout":{"type":"default"}} -->\n<div class="wp-block-group semrs-faq">${pairs.map(([q, a]) =>
	`<!-- wp:details -->\n<details class="wp-block-details"><summary>${inline(q)}</summary>${P(a)}</details>\n<!-- /wp:details -->`).join('\n\n')}</div>\n<!-- /wp:group -->`;
const CTA = (t) => `<!-- wp:group {"className":"semrs-service-cta","layout":{"type":"default"}} -->\n<div class="wp-block-group semrs-service-cta">${P(t)}</div>\n<!-- /wp:group -->`;

// Light inline-SVG picture of the "How to use" steps (no image file, theme colors, dark mode safe).
const plain = (s) => s.replace(/\*\*|`/g, '').replace(/\[([^\]]+)\]\([^)]*\)/g, '$1');
const short = (s) => { const t = plain(s).split(/[.,;:(]/)[0].trim(); const w = t.split(/\s+/); return w.length > 7 ? w.slice(0, 7).join(' ') + '…' : t; };
const wrapText = (t, max) => { const out = []; let line = ''; for (const w of t.split(' ')) { if ((line + ' ' + w).trim().length > max) { out.push(line.trim()); line = w; } else line += ' ' + w; } if (line.trim()) out.push(line.trim()); return out.slice(0, 3); };
const STEPS_SVG = (focus, items, works = false) => {
	const steps = items.slice(0, 5).map(short);
	const n = steps.length, w = 640, colW = w / n;
	const label = `${works ? `How the ${focus} works` : `How to use the ${focus}`} in ${n} steps: ${steps.map((s, i) => `${i + 1}. ${s}`).join('; ')}`;
	const cols = steps.map((s, i) => {
		const cx = colW * i + colW / 2;
		const lines = wrapText(s, Math.floor(colW / 9)).map((l, j) => `<text x="${cx}" y="${108 + j * 20}" text-anchor="middle" font-size="15" fill="currentColor">${esc(l)}</text>`).join('');
		const arrow = i < n - 1 ? `<path d="M${cx + 34} 52 H${cx + colW - 34}" stroke="var(--sp-accent,#f7941d)" stroke-width="3" stroke-dasharray="6 6"/>` : '';
		return `${arrow}<circle cx="${cx}" cy="52" r="26" fill="var(--sp-primary,#0b2fd1)"/><text x="${cx}" y="60" text-anchor="middle" font-size="22" font-weight="700" fill="#fff">${i + 1}</text>${lines}`;
	}).join('');
	return `<!-- wp:html -->\n<figure class="semrs-steps-figure"><svg role="img" aria-label="${esc(label).replace(/"/g, '&quot;')}" viewBox="0 0 ${w} 180" width="100%" xmlns="http://www.w3.org/2000/svg" style="max-width:${w}px;height:auto">${cols}</svg></figure>\n<!-- /wp:html -->`;
};
const TOC = '<!-- wp:semrs/toc {"levels":"2","collapsed":true} /-->';

function parse(file) {
	const raw = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
	const m = raw.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
	if (!m) { throw new Error('No front matter: ' + file); }
	const meta = {};
	m[1].split('\n').forEach((l) => { const i = l.indexOf(':'); if (i > 0) { meta[l.slice(0, i).trim()] = l.slice(i + 1).trim(); } });
	const slug = path.basename(file, '.md');
	const lines = m[2].split('\n');
	const blocks = [];
	let words = 0;
	let lastH2 = '';
	let figured = false;
	for (let i = 0; i < lines.length; i++) {
		const l = lines[i].trim();
		if (!l) { continue; }
		const count = (t) => { words += t.replace(/\[[^\]]*\]\([^)]*\)/g, (x) => x.replace(/\(.*\)/, '')).split(/\s+/).filter(Boolean).length; };
		if (l === '[tool]') { blocks.push(SC(`[semrs_tool id="${slug}"]`)); continue; }
		if (l === '[ad]') { blocks.push(SC('[semrs_ad slot="tool"]')); continue; }
		if (/^\[semrs_[a-z_]+.*\]$/.test(l)) { blocks.push(SC(l)); continue; }
		if (l.startsWith('### ')) { blocks.push(H(l.slice(4), 3)); count(l.slice(4)); continue; }
		if (l.startsWith('## ')) { lastH2 = l.slice(3); blocks.push(H(lastH2, 2)); count(lastH2); continue; }
		if (l.startsWith('> ')) { blocks.push(CTA(l.slice(2))); count(l.slice(2)); continue; }
		if (/^(- |\d+\. )/.test(l)) {
			const ordered = /^\d+\. /.test(l);
			const items = [];
			while (i < lines.length && /^(- |\d+\. )/.test(lines[i].trim())) { const t = lines[i].trim().replace(/^(- |\d+\. )/, ''); items.push(t); count(t); i++; }
			i--;
			if (ordered && !figured && meta.focus && items.length >= 3 && /^how (to (use|find)|the .+ works)/i.test(lastH2)) { blocks.push(STEPS_SVG(meta.focus, items, /works$/i.test(lastH2))); figured = true; }
			blocks.push(LIST(items, ordered));
			continue;
		}
		if (l.startsWith('Q: ')) {
			const pairs = [];
			while (i < lines.length && lines[i].trim().startsWith('Q: ')) {
				const q = lines[i].trim().slice(3);
				const a = (lines[i + 1] || '').trim().replace(/^A: /, '');
				pairs.push([q, a]); count(q); count(a);
				i += 2;
				while (i < lines.length && !lines[i].trim()) { i++; }
			}
			i--;
			blocks.push(FAQ(pairs));
			continue;
		}
		blocks.push(P(l)); count(l);
	}
	const h2s = blocks.filter((b) => b.startsWith('<!-- wp:heading -->')).length;
	if (h2s >= 3 && words > 600) {
		blocks.splice(blocks.findIndex((b) => b.startsWith('<!-- wp:heading -->')), 0, TOC);
	}
	return { slug, meta, html: blocks.join('\n\n') + '\n', words };
}

const files = fs.readdirSync(SRC).filter((f) => f.endsWith('.md')).sort();
const pages = {};
const report = [];
for (const f of files) {
	const { slug, meta, html, words } = parse(path.join(SRC, f));
	const issues = [];
	if (!meta.title || meta.title.length > 60) { issues.push(`title ${meta.title ? meta.title.length : 0} chars`); }
	if (!meta.description || meta.description.length < 120 || meta.description.length > 158) { issues.push(`description ${meta.description ? meta.description.length : 0} chars`); }
	if (meta.focus && !meta.title.toLowerCase().includes(meta.focus.toLowerCase().split(' ')[0])) { issues.push('focus word not in title'); }
	const min = meta.kind === 'tool' ? 800 : 400;
	if (words < min) { issues.push(`only ${words} words (min ${min})`); }
	const entry = { h1: meta.h1, title: meta.title, description: meta.description, focus: meta.focus, schema: meta.schema || 'WebApplication', kind: meta.kind, parent: meta.parent || '', order: Number(meta.order || 0) };
	if (meta.site === 'semrs') {
		fs.writeFileSync(path.join(OUT_SITE, slug + '.html'), html);
		sitePages[slug] = entry;
	} else {
		fs.writeFileSync(path.join(OUT_PLUGIN, slug + '.html'), html);
		pages[slug] = entry;
	}
	const dir = path.join(OUT_PAGES, slug);
	fs.mkdirSync(dir, { recursive: true });
	fs.writeFileSync(path.join(dir, 'page.html'), html);
	fs.writeFileSync(path.join(dir, 'seo.md'),
		`# ${meta.h1}\n\n| Field | Value |\n| --- | --- |\n| URL | /tools/${slug === 'tools' ? '' : slug + '/'} |\n| Page title (H1) | ${meta.h1} |\n| SEO title (${meta.title.length} chars) | ${meta.title} |\n| Meta description (${meta.description.length} chars) | ${meta.description} |\n| Focus keyword | ${meta.focus} |\n| Schema type | ${entry.schema} (+ FAQPage from the FAQ blocks, BreadcrumbList) |\n| Template | Default (no sidebar) |\n| Parent page | ${meta.parent ? '/tools/' : '(none)'} |\n| Words | ${words} |\n`);
	report.push(`${(issues.length ? '✖' : '✔')} ${slug.padEnd(34)} ${String(words).padStart(5)} words  title ${meta.title.length}  desc ${meta.description.length}${issues.length ? '  ← ' + issues.join(', ') : ''}`);
}
fs.writeFileSync(path.join(OUT_PLUGIN, 'pages.json'), JSON.stringify(pages, null, '\t'));
fs.writeFileSync(path.join(OUT_SITE, 'pages.json'), JSON.stringify(sitePages, null, '\t'));
console.log(report.join('\n'));
console.log(`\n${files.length} pages built.`);
