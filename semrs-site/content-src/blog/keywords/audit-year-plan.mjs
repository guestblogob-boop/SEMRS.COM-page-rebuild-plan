// Audits the 12-month plan against the keyword pool: volume, CPC (the best AdSense-earnings
// signal in the data), ad competition and niche fit. Prints weak topics and the strongest
// unused keyword clusters per category, as candidates for swaps.
//
//   node content-src/blog/keywords/audit-year-plan.mjs [--candidates]   (from the semrs-site folder)
import fs from 'fs';
import path from 'path';

const DIR = path.join('content-src', 'blog', 'keywords');
const USD = 280; // PKR per USD, approximate
const plan = JSON.parse(fs.readFileSync(path.join(DIR, 'plan-12-month.json'), 'utf8'));
const done = JSON.parse(fs.readFileSync(path.join(DIR, 'plan.json'), 'utf8'));

const parse = (l) => { const o = []; let c = '', q = false; for (const ch of l) { if (ch === '"') { q = !q; continue; } if (ch === ',' && !q) { o.push(c); c = ''; continue; } c += ch; } o.push(c); return o; };
const raw = fs.readFileSync(path.join(DIR, 'semrs-keywords-all.csv'), 'utf8').replace(/^﻿/, '').split(/\r?\n/).filter(Boolean);
const head = parse(raw[0]); const col = (n) => head.indexOf(n);
const STOP = new Set('a an and the for of to in on with by at or your you is are what how why when which can do does my our best top 2024 2025 2026 2027'.split(' '));
const stem = (w) => w.replace(/ies$/, 'y').replace(/([^s])s$/, '$1');
const toks = (k) => [...new Set(k.split(/[^a-z0-9]+/).filter((w) => w && !STOP.has(w)).map(stem))];
const pool = raw.slice(1).map(parse).map((r) => { const kw = r[col('Keyword')].trim().toLowerCase(); return { cat: r[col('Category')], kw, t: toks(kw), vol: +r[col('Monthly searches')] || 0, ci: +r[col('Competition index')] || 0, cpc: (+r[col('Avg CPC (PKR)')] || 0) / USD, fit: r[col('Niche fit')] }; });
const jac = (a, b) => { const B = new Set(b); const i = a.filter((x) => B.has(x)).length; return i / (a.length + b.length - i); };

// Best data for a topic: the pool keyword that contains all the topic's words (or is very close), highest volume.
const matchOf = (kw) => {
	const t = toks(kw);
	const c = pool.filter((p) => p.kw === kw || t.every((x) => p.t.includes(x)) && p.t.length <= t.length + 2 || jac(t, p.t) >= 0.75);
	if (!c.length) return null;
	const exact = c.find((p) => p.kw === kw);
	const top = c.sort((a, b) => b.vol - a.vol)[0];
	return { kw: (exact || top).kw, vol: Math.max(exact ? exact.vol : 0, top.vol), cpc: Math.max(...c.map((p) => p.cpc)), ci: (exact || top).ci, family: c.reduce((s, p) => s + p.vol, 0) };
};
// Score: monthly ad value (volume x CPC), with a bonus for lower ad competition.
const score = (m) => m ? m.vol * m.cpc * (m.ci <= 33 ? 1.3 : m.ci <= 66 ? 1 : 0.8) : 0;

const rows = plan.map((p) => { const m = matchOf(p.kw); return { ...p, m, s: score(m) }; });
const weak = rows.filter((r) => !r.m || r.m.vol < 100 || r.m.cpc < 0.5);
const median = (a) => { const s = [...a].sort((x, y) => x - y); return s[Math.floor(s.length / 2)] || 0; };
console.log('Topics:', rows.length, '| with search data:', rows.filter((r) => r.m).length, '| weak (no data, <100 searches or CPC < $0.50):', weak.length);
console.log('Median volume', median(rows.filter((r) => r.m).map((r) => r.m.vol)), '| median CPC $' + median(rows.filter((r) => r.m).map((r) => r.m.cpc)).toFixed(2));
const byCat = {};
for (const r of rows) { const c = (byCat[r.cat] = byCat[r.cat] || { n: 0, weak: 0 }); c.n++; if (weak.includes(r)) c.weak++; }
console.table(byCat);
fs.writeFileSync(path.join(DIR, 'plan-audit.json'), JSON.stringify(rows.map((r) => ({ n: r.n, cat: r.cat, kw: r.kw, data: r.m, score: Math.round(r.s), weak: weak.includes(r) })), null, 1));

if (process.argv.includes('--candidates')) {
	// Strong unused clusters: niche fit, 500+ searches, CPC $1+, not overlapping any planned or published topic.
	const used = [...plan.map((p) => toks(p.kw)), ...done.flatMap((d) => [d.kw, ...(d.supporting || [])]).map(toks)];
	const BLOCK = /\b(semrush|ahrefs|moz|wix|squarespace|shopify|godaddy|hubspot|mailchimp|hootsuite|zoho|bluehost|siteground|hostgator|ionos|namecheap|canva|chatgpt|openai|jasper|grammarly|amazon|ebay|etsy|walmart|paypal|stripe|square|clover|chase|amex|american express|capital one|wells fargo|citi|geico|state farm|progressive|allstate|liberty mutual|zillow|redfin|yelp|angi|upwork|fiverr|login|sign in|near me|jobs?|salary|course|courses|certification|degree|meaning|definition|pdf|template free download|reddit|youtube to|free download|app download|in hindi|in urdu|para|de|para|en)\b/;
	const cand = pool.filter((p) => p.fit === 'Yes' && p.vol >= 500 && p.cpc >= 1 && p.t.length >= 2 && !BLOCK.test(p.kw) && !used.some((u) => jac(p.t, u) >= 0.5 || (u.length >= 2 && u.every((x) => p.t.includes(x)))));
	cand.sort((a, b) => b.vol * b.cpc - a.vol * a.cpc);
	const picked = [];
	for (const c of cand) if (!picked.some((p) => jac(p.t, c.t) >= 0.5 || p.t.every((x) => c.t.includes(x)) || c.t.every((x) => p.t.includes(x)))) picked.push(c);
	const out = {};
	for (const p of picked) (out[p.cat] = out[p.cat] || []).push(`${p.kw} | ${p.vol} | $${p.cpc.toFixed(2)} | ci ${p.ci}`);
	for (const [c, a] of Object.entries(out)) { console.log('\n## ' + c + ' (' + a.length + ')'); console.log(a.slice(0, 40).join('\n')); }
}
