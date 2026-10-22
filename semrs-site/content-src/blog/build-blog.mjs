// Builds the SEMRS blog articles (content-src/blog/articles/*.md) into
// WordPress block markup: pages/blog/bundle.json for upload.
//
// Article format: front matter (--- key: value ---), then a small Markdown
// subset: ## / ### headings, paragraphs, - lists, 1. lists, | tables |,
// "? Question" + answer lines (FAQ details blocks, which SEMRS turns into
// FAQPage schema), "! text" callouts, **bold** and [links](url).
//
// node content-src/blog/build-blog.mjs   (run from the semrs-site folder)
import fs from 'fs';
import path from 'path';

const SRC = path.join('content-src', 'blog', 'articles');
const OUT = path.join('pages', 'blog');

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const inline = (s) => esc(s)
	.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
	.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (m, t, u) => `<a href="${u}"${/^https?:/.test(u) && !/semrs\.com/.test(u) ? ' target="_blank" rel="noopener"' : ''}>${t}</a>`);

const para = (s, cls = '') => `<!-- wp:paragraph${cls ? ` {"className":"${cls}"}` : ''} -->\n<p${cls ? ` class="${cls}"` : ''}>${inline(s)}</p>\n<!-- /wp:paragraph -->`;
const head = (s, l) => `<!-- wp:heading${l === 2 ? '' : ` {"level":${l}}`} -->\n<h${l} class="wp-block-heading">${inline(s)}</h${l}>\n<!-- /wp:heading -->`;
const list = (items, ordered) => `<!-- wp:list${ordered ? ' {"ordered":true}' : ''} -->\n<${ordered ? 'ol' : 'ul'} class="wp-block-list">${items.map((i) => `<!-- wp:list-item -->\n<li>${inline(i)}</li>\n<!-- /wp:list-item -->`).join('')}</${ordered ? 'ol' : 'ul'}>\n<!-- /wp:list -->`;
const table = (rows) => {
	const cells = rows.map((r) => r.replace(/^\||\|$/g, '').split('|').map((c) => c.trim())).filter((r) => !r.every((c) => /^-+$/.test(c)));
	const [h, ...b] = cells;
	return `<!-- wp:table -->\n<figure class="wp-block-table"><table><thead><tr>${h.map((c) => `<th>${inline(c)}</th>`).join('')}</tr></thead><tbody>${b.map((r) => `<tr>${r.map((c) => `<td>${inline(c)}</td>`).join('')}</tr>`).join('')}</tbody></table></figure>\n<!-- /wp:table -->`;
};
const faq = (q, a) => `<!-- wp:details -->\n<details class="wp-block-details"><summary>${inline(q)}</summary>${a.map((p) => para(p)).join('\n')}</details>\n<!-- /wp:details -->`;

function build(md) {
	const lines = md.split(/\r?\n/);
	const out = [];
	let i = 0;
	while (i < lines.length) {
		const l = lines[i];
		if (!l.trim()) { i++; continue; }
		if (l.startsWith('### ')) { out.push(head(l.slice(4), 3)); i++; continue; }
		if (l.startsWith('## ')) { out.push(head(l.slice(3), 2)); i++; continue; }
		if (l.startsWith('! ')) { out.push(para(l.slice(2), 'semrs-service-cta')); i++; continue; }
		if (/^- /.test(l)) { const it = []; while (i < lines.length && /^- /.test(lines[i])) it.push(lines[i++].slice(2)); out.push(list(it, false)); continue; }
		if (/^\d+\. /.test(l)) { const it = []; while (i < lines.length && /^\d+\. /.test(lines[i])) it.push(lines[i++].replace(/^\d+\. /, '')); out.push(list(it, true)); continue; }
		if (l.startsWith('|')) { const r = []; while (i < lines.length && lines[i].startsWith('|')) r.push(lines[i++]); out.push(table(r)); continue; }
		if (l.startsWith('? ')) { const q = l.slice(2); const a = []; i++; while (i < lines.length && lines[i].trim() && !lines[i].startsWith('? ')) a.push(lines[i++]); out.push(faq(q, a)); continue; }
		out.push(para(l)); i++;
	}
	return out.join('\n\n');
}

const files = fs.readdirSync(SRC).filter((f) => f.endsWith('.md')).sort();
const items = files.map((f) => {
	const raw = fs.readFileSync(path.join(SRC, f), 'utf8');
	const m = raw.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
	const meta = Object.fromEntries(m[1].split('\n').map((l) => { const k = l.indexOf(':'); return [l.slice(0, k).trim(), l.slice(k + 1).trim()]; }));
	const content = build(m[2]);
	const words = m[2].replace(/[#*|!?\-\[\]()]/g, ' ').split(/\s+/).filter(Boolean).length;
	return { slug: meta.slug, title: meta.title, excerpt: meta.description, category: +meta.category, keyword: meta.keyword, words, content };
});
fs.mkdirSync(OUT, { recursive: true });
fs.writeFileSync(path.join(OUT, 'bundle.json'), JSON.stringify(items));
for (const it of items) console.log(String(it.words).padStart(5), it.category, it.slug, `(${it.excerpt.length} char description)`);
