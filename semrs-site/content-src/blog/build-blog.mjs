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
// "[glance: Label | point | point | point | point]": a light inline-SVG summary (theme colors, no image file).
// The label names the topic (so it holds the focus keyword naturally); points stay under about 30 characters.
const wrapText = (t, max) => { const o = []; let line = ''; for (const w of t.split(' ')) { if ((line + ' ' + w).trim().length > max) { o.push(line.trim()); line = w; } else line += ' ' + w; } if (line.trim()) o.push(line.trim()); return o.slice(0, 3); };
const glance = (label, pts) => {
	const w = 640, col = w / pts.length;
	const body = pts.map((p, i) => {
		const x = col * i + 8;
		const lines = wrapText(p, 16).map((l, j) => `<text x="${x + 14}" y="${76 + j * 20}" font-size="15" fill="currentColor">${esc(l)}</text>`).join('');
		return `<rect x="${x}" y="8" width="${col - 16}" height="124" rx="14" fill="currentColor" fill-opacity=".06" stroke="var(--sp-accent,#f7941d)" stroke-width="2"/><circle cx="${x + 26}" cy="36" r="14" fill="var(--sp-primary,#0b2fd1)"/><text x="${x + 26}" y="41" text-anchor="middle" font-size="15" font-weight="700" fill="#fff">${i + 1}</text>${lines}`;
	}).join('');
	return `<!-- wp:html -->\n<figure class="semrs-glance"><svg role="img" aria-label="${esc(label + ': ' + pts.join('; ')).replace(/"/g, '&quot;')}" viewBox="0 0 ${w} 140" width="100%" style="max-width:${w}px;height:auto" xmlns="http://www.w3.org/2000/svg">${body}</svg></figure>\n<!-- /wp:html -->`;
};
const TOC = '<!-- wp:semrs/toc {"levels":"2","collapsed":true} /-->';

function build(md) {
	const lines = md.split(/\r?\n/);
	const out = [];
	let i = 0;
	while (i < lines.length) {
		const l = lines[i];
		if (!l.trim()) { i++; continue; }
		if (/^\[glance: .+\]$/.test(l.trim())) { const [label, ...pts] = l.trim().slice(9, -1).split('|').map((x) => x.trim()); out.push(glance(label, pts)); i++; continue; }
		if (l.startsWith('### ')) { out.push(head(l.slice(4), 3)); i++; continue; }
		if (l.startsWith('## ')) { out.push(head(l.slice(3), 2)); i++; continue; }
		if (l.startsWith('@img ')) { out.push(`<!-- semrs-img:${l.slice(5).trim()} -->`); i++; continue; }
		if (l.startsWith('! ')) { out.push(para(l.slice(2), 'semrs-service-cta')); i++; continue; }
		if (/^- /.test(l)) { const it = []; while (i < lines.length && /^- /.test(lines[i])) it.push(lines[i++].slice(2)); out.push(list(it, false)); continue; }
		if (/^\d+\. /.test(l)) { const it = []; while (i < lines.length && /^\d+\. /.test(lines[i])) it.push(lines[i++].replace(/^\d+\. /, '')); out.push(list(it, true)); continue; }
		if (l.startsWith('|')) { const r = []; while (i < lines.length && lines[i].startsWith('|')) r.push(lines[i++]); out.push(table(r)); continue; }
		if (l.startsWith('? ')) { const q = l.slice(2); const a = []; i++; while (i < lines.length && lines[i].trim() && !lines[i].startsWith('? ')) a.push(lines[i++]); out.push(faq(q, a)); continue; }
		out.push(para(l)); i++;
	}
	// Table of contents (collapsed) after the summary graphic, or before the second H2.
	if (out.filter((b) => b.startsWith('<!-- wp:heading -->')).length >= 3) {
		const g = out.findIndex((b) => b.includes('semrs-glance'));
		const h = out.map((b, k) => (b.startsWith('<!-- wp:heading -->') ? k : -1)).filter((k) => k >= 0);
		out.splice(g >= 0 ? g + 1 : h[1], 0, TOC);
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
	return { slug: meta.slug, title: meta.title, seoTitle: meta.seo_title, focus: meta.focus, excerpt: meta.description, category: +meta.category, keyword: meta.keyword, pillar: meta.pillar === 'yes', words, content };
});

// Checks that mirror the SEMRS SEO score (Core modules/seo/analysis.php) and WRITING_GUIDE.md.
// Every article must print "ok" before upload; the live score should then be 100.
const SENTIMENT = 'best better easy easiest great top perfect smart simple free happy love winning success successful effective helpful useful worst bad avoid mistakes mistake never stop wrong fail fails failing problems risk risks warning costly hidden myths truth honest real'.split(' ');
const POWER = 'proven complete ultimate essential simple easy quick fast step-by-step guide checklist blueprint expert powerful practical secret secrets tips tricks strategies hacks instantly effortless surprising critical crucial definitive insider actionable affordable smart best top new free boost grow save win'.split(' ');
const IMAGES = (() => { try { return JSON.parse(fs.readFileSync(path.join('content-src', 'blog', 'images', 'images.json'), 'utf8')); } catch (e) { return {}; } })();
const syllables = (w) => { w = w.toLowerCase().replace(/[^a-z]/g, ''); if (w.length <= 3) return 1; w = w.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, ''); return Math.max(1, (w.match(/[aeiouy]{1,2}/g) || []).length); };
// Tags become spaces and block ends become line breaks, exactly like Core.
const strip = (h) => h.replace(/<!--[\s\S]*?-->/g, ' ').replace(/<svg[\s\S]*?<\/svg>/g, ' ').replace(/<[^>]+>/g, ' ').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim();
const sentencesOf = (h) => h.replace(/<!--[\s\S]*?-->/g, ' ').replace(/<svg[\s\S]*?<\/svg>/g, ' ').replace(/<\/(?:p|li|h[1-6]|td|th|summary|figcaption|div)>/g, '\n').replace(/<[^>]+>/g, ' ').replace(/&amp;/g, '&')
	.split(/\n+/).flatMap((c) => c.trim().replace(/\s+/g, ' ').split(/(?<=[.!?])\s+/)).filter((s) => s.trim());
for (const it of items) {
	const kw = (it.focus || '').toLowerCase();
	const text = strip(it.content); const lower = text.toLowerCase(); const wl = text.split(' ');
	const first = wl.slice(0, Math.max(50, Math.ceil(wl.length * 0.1))).join(' ').toLowerCase();
	const subs = [...it.content.matchAll(/<h[2-6][^>]*>([\s\S]*?)<\/h[2-6]>/g)].map((m) => strip(m[1]).toLowerCase());
	const h2 = (it.content.match(/<!-- wp:heading -->/g) || []).length;
	const toc = it.content.includes('wp:semrs/toc');
	// The table of contents repeats every H2, so headings count twice for density, as on the live page.
	const tocText = toc ? subs.join(' ') : '';
	const hits = (lower + ' ' + tocText).split(kw).length - 1; const density = Math.round(hits * kw.split(' ').length / wl.length * 1000) / 10;
	const imgs = [...it.content.matchAll(/<!-- semrs-img:([^ ]+) -->/g)].map((m) => m[1]);
	const labels = [...it.content.matchAll(/aria-label="([^"]*)"/g)].map((m) => m[1].toLowerCase());
	const alts = imgs.map((k) => ((IMAGES[k] || IMAGES[it.slug] || {}).alt || '').toLowerCase());
	const media = imgs.length + labels.length;
	const links = [...it.content.matchAll(/href="([^"]+)"/g)].map((m) => m[1]);
	const outbound = links.filter((u) => /^https?:/.test(u) && !/semrs\.com/.test(u)).length;
	const internal = links.filter((u) => u.startsWith('/') || /semrs\.com/.test(u)).length;
	const sents = sentencesOf(it.content); const longPct = Math.round(sents.filter((s) => s.split(' ').length > 25).length / Math.max(1, sents.length) * 100);
	const flesch = Math.round(206.835 - 1.015 * (wl.length / Math.max(1, sents.length)) - 84.6 * (wl.reduce((a, w) => a + syllables(w), 0) / wl.length));
	const tw = (it.seoTitle || '').toLowerCase().split(/[^a-z0-9'-]+/);
	const fails = [];
	if (!kw) fails.push('no focus');
	if (!(it.seoTitle || '').toLowerCase().includes(kw)) fails.push('kw not in SEO title');
	else if ((it.seoTitle || '').toLowerCase().indexOf(kw) > Math.max(10, (it.seoTitle || '').length / 2)) fails.push('kw not near the start of the SEO title');
	if (!tw.some((w) => SENTIMENT.includes(w))) fails.push('title needs a sentiment word');
	if (!tw.some((w) => POWER.includes(w))) fails.push('title needs a power word');
	if (!/\d/.test(it.seoTitle || '')) fails.push('title needs a number');
	if (!it.excerpt.toLowerCase().includes(kw)) fails.push('kw not in description');
	if (!it.slug.includes(kw.replace(/ /g, '-'))) fails.push('kw not in slug');
	if (!first.includes(kw)) fails.push('kw not in the first 10% of words');
	if (!subs.some((s) => s.includes(kw))) fails.push('kw not in a subheading');
	if (density < 0.5 || density > 2.5) fails.push('density ' + density + '% (' + hits + ' uses; 0.5–2.5% allowed)');
	const minWords = it.pillar ? 1500 : 600;
	if (wl.length < minWords) fails.push('only ' + wl.length + ' words (' + minWords + '+)');
	const tl = (it.seoTitle || '').length, dl = it.excerpt.length;
	if (tl < 30 || tl > 60) fails.push('SEO title ' + tl + ' chars (30–60)');
	if (dl < 120 || dl > 160) fails.push('description ' + dl + ' chars (120–160)');
	if (media < 2) fails.push('needs 2+ images or graphics (now ' + media + ')');
	if (![...labels, ...alts].some((a) => a.includes(kw))) fails.push('no image alt or graphic label with the kw');
	if (!outbound) fails.push('needs a link to a useful outside source');
	if (!internal) fails.push('needs internal links');
	if (!toc && wl.length > 600) fails.push('needs a table of contents (3+ H2)');
	if (h2 < Math.floor(wl.length / 350)) fails.push('more subheadings needed');
	if (longPct > 25) fails.push(longPct + '% long sentences (25% max)');
	if (flesch < 50) fails.push('Flesch ' + flesch + ' (50+)');
	if (/<h1[\s>]/.test(it.content)) fails.push('remove the H1 from the content');
	it.check = fails;
	console.log((fails.length ? 'FIX ' : 'ok  ') + it.slug + (fails.length ? ' → ' + fails.join('; ') : '') + ' [density ' + density + '%, ' + wl.length + ' words, media ' + media + ', Flesch ' + flesch + ']');
}
fs.mkdirSync(OUT, { recursive: true });
fs.writeFileSync(path.join(OUT, 'bundle.json'), JSON.stringify(items));
for (const it of items) console.log(String(it.words).padStart(5), it.category, it.slug, `(${it.excerpt.length} char description)`);
