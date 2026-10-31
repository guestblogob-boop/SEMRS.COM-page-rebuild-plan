// Build the support / user-manual pages: pages/support/*.md → WordPress block
// markup in plugins/semrs-update-server/content/support/ (+ pages.json).
// The update-server plugin (semrs.com only) installs them as draft pages.
//
// Usage: node build-support.mjs
// Markdown subset: front matter, ## / ### headings, paragraphs, - and 1. lists,
// | tables |, **bold**, `code`, [links](url).
// Front matter: title, description, order, seo_title, focus. The cover image
// (pages/support/images/images.json, Media Library IDs on semrs.com) and a
// table of contents go after the first paragraph.

import fs from 'fs';
import path from 'path';

const SRC = 'pages/support';
const OUT = 'plugins/semrs-update-server/content/support';
fs.mkdirSync(OUT, { recursive: true });

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
function inline(s) {
	return esc(s)
		.replace(/`([^`]+)`/g, '<code>$1</code>')
		.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
		.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (m, t, u) => `<a href="${u.replace(/"/g, '&quot;')}"${/^https?:/.test(u) ? ' target="_blank" rel="noopener"' : ''}>${t}</a>`);
}
const block = (name, attrs, html) => `<!-- wp:${name}${attrs ? ' ' + JSON.stringify(attrs) : ''} -->\n${html}\n<!-- /wp:${name} -->`;

function convert(md, slug) {
	const lines = md.replace(/\r/g, '').split('\n');
	const out = [];
	let i = 0;
	while (i < lines.length) {
		const line = lines[i];
		if (!line.trim()) { i++; continue; }
		let m;
		if ((m = line.match(/^(#{2,3}) (.+)$/))) {
			const level = m[1].length;
			out.push(block('heading', level === 2 ? null : { level }, `<h${level} class="wp-block-heading">${inline(m[2])}</h${level}>`));
			i++;
		} else if ((m = line.match(/^!\[([^\]]*)\]\(([a-z0-9-]+)\)$/))) {
			// Diagram: ![alt](key), key from images/images.json.
			const img = IMAGES[m[2]];
			if (!img) throw new Error(`Unknown image "${m[2]}": upload it and add it to images.json`);
			out.push(block('image', { id: img.id, sizeSlug: 'full', linkDestination: 'none' }, `<figure class="wp-block-image size-full"><img src="https://semrs.com/wp-content/uploads/${img.file}" alt="${esc(m[1] || img.alt)}" class="wp-image-${img.id}"/></figure>`));
			i++;
		} else if (/^\|/.test(line)) {
			const rows = [];
			while (i < lines.length && /^\|/.test(lines[i])) { rows.push(lines[i]); i++; }
			const cells = (r) => r.replace(/^\||\|$/g, '').split('|').map((c) => c.trim());
			const head = cells(rows[0]);
			const body = rows.slice(2).map(cells);
			const html = '<figure class="wp-block-table"><table><thead><tr>' + head.map((c) => `<th>${inline(c)}</th>`).join('') + '</tr></thead><tbody>' +
				body.map((r) => '<tr>' + r.map((c) => `<td>${inline(c)}</td>`).join('') + '</tr>').join('') + '</tbody></table></figure>';
			out.push(block('table', null, html));
		} else if (/^(- |\d+\. )/.test(line)) {
			const ordered = /^\d+\. /.test(line);
			const items = [];
			while (i < lines.length && (ordered ? /^\d+\. /.test(lines[i]) : /^- /.test(lines[i]))) {
				items.push(lines[i].replace(/^(- |\d+\. )/, ''));
				i++;
			}
			const tag = ordered ? 'ol' : 'ul';
			const inner = items.map((t) => block('list-item', null, `<li>${inline(t)}</li>`)).join('\n\n');
			out.push(block('list', ordered ? { ordered: true } : null, `<${tag} class="wp-block-list">${inner}</${tag}>`));
		} else {
			const para = [];
			while (i < lines.length && lines[i].trim() && !/^(#{2,3} |\||- |\d+\. )/.test(lines[i])) { para.push(lines[i].trim()); i++; }
			out.push(block('paragraph', null, `<p>${inline(para.join(' '))}</p>`));
		}
	}
	const extra = [];
	const img = IMAGES[slug];
	if (img) {
		extra.push(block('image', { id: img.id, sizeSlug: 'full', linkDestination: 'none' }, `<figure class="wp-block-image size-full"><img src="https://semrs.com/wp-content/uploads/${img.file}" alt="${esc(img.alt)}" class="wp-image-${img.id}"/></figure>`));
	}
	if (out.filter((b) => b.startsWith('<!-- wp:heading')).length >= 3) {
		extra.push('<!-- wp:semrs/toc {"levels":"2"} /-->');
	}
	out.splice(1, 0, ...extra);
	return out.join('\n\n') + '\n';
}

const IMAGES = fs.existsSync(SRC + '/images/images.json') ? JSON.parse(fs.readFileSync(SRC + '/images/images.json', 'utf8')) : {};
const manifest = {};
for (const file of fs.readdirSync(SRC).filter((f) => f.endsWith('.md'))) {
	const raw = fs.readFileSync(path.join(SRC, file), 'utf8').replace(/\r/g, '');
	const fm = raw.match(/^---\n([\s\S]*?)\n---\n/);
	const meta = {};
	if (fm) {
		fm[1].split('\n').forEach((l) => { const k = l.indexOf(':'); if (k > 0) { meta[l.slice(0, k).trim()] = l.slice(k + 1).trim(); } });
	}
	const slug = file.replace(/\.md$/, '');
	fs.writeFileSync(path.join(OUT, slug + '.html'), convert(fm ? raw.slice(fm[0].length) : raw, slug));
	manifest[slug] = { title: meta.title || slug, description: meta.description || '', order: Number(meta.order || 99), seo_title: meta.seo_title || '', focus: meta.focus || '', image: IMAGES[slug] ? IMAGES[slug].id : 0 };
}
fs.writeFileSync(path.join(OUT, 'pages.json'), JSON.stringify(manifest, null, '\t') + '\n');
console.log('support pages:', Object.keys(manifest).length, '→', OUT);
