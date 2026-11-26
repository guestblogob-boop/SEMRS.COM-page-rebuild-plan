// Builds the 12-month SEMRS content plan from the editorial topic lists.
//
//   node content-src/blog/keywords/build-year-plan.mjs   (run from the semrs-site folder)
//
// Topics: year-topics.txt and year-topics-2.txt ("focus keyword | working title" under "## Category").
// Schedule: one article a day for six months from 2026-12-27, then two a day for six months.
// Each focus keyword is matched to semrs-keywords-all.csv for volume, competition and CPC, and gets
// up to six supporting keywords from the same cluster. Writes plan-12-month.json and
// semrs-12-month-content-plan.csv.
import fs from 'fs';
import path from 'path';

const DIR = path.join('content-src', 'blog', 'keywords');
const START = Date.UTC(2026, 11, 27); // the day after the last scheduled December post
const ONE_A_DAY = 182; // days at one article a day (about six months)
const TWO_A_DAY = 183; // then days at two a day
const TIMES = { one: ['14:00'], two: ['14:00', '21:00'] }; // UTC (9 am and 4 pm US Eastern)

const CAT_ID = { 'SEO & AI Search': 293, 'Local SEO': 26, 'PPC & Paid Ads': 27, 'Web Design & WordPress': 24, 'Content & Social Media': 294, 'AI Marketing': 296, 'Digital Marketing': 1, 'Marketing Costs & ROI': 292, 'Industry Marketing (Finance & Insurance)': 25, 'Industry Marketing (Legal & Health)': 298, 'Business Finance': 297 };

// Editorial topics.
const topics = [];
for (const f of ['year-topics.txt', 'year-topics-2.txt']) {
	let cat = '';
	for (const line of fs.readFileSync(path.join(DIR, f), 'utf8').split(/\r?\n/)) {
		const h = line.match(/^## (.+)$/);
		if (h) { cat = h[1].trim(); continue; }
		if (!line.trim() || line.startsWith('#')) continue;
		const [kw, title] = line.split('|').map((s) => s.trim());
		if (!CAT_ID[cat]) throw new Error('Unknown category: ' + cat);
		topics.push({ cat, kw: kw.toLowerCase(), title });
	}
}
const seen = new Set();
for (const t of topics) { if (seen.has(t.kw)) throw new Error('Duplicate focus keyword: ' + t.kw); seen.add(t.kw); }
const done = JSON.parse(fs.readFileSync(path.join(DIR, 'plan.json'), 'utf8'));
for (const d of done) if (seen.has(d.kw)) throw new Error('Already covered: ' + d.kw);
const slots = ONE_A_DAY + TWO_A_DAY * 2;
if (topics.length !== slots) throw new Error(`Need ${slots} topics, have ${topics.length}`);

// Keyword pool.
const parse = (l) => { const o = []; let c = '', q = false; for (const ch of l) { if (ch === '"') { q = !q; continue; } if (ch === ',' && !q) { o.push(c); c = ''; continue; } c += ch; } o.push(c); return o; };
const raw = fs.readFileSync(path.join(DIR, 'semrs-keywords-all.csv'), 'utf8').replace(/^﻿/, '').split(/\r?\n/).filter(Boolean);
const head = parse(raw[0]); const col = (n) => head.indexOf(n);
const STOP = new Set('a an and the for of to in on with by at or your you is are what how why when which can do does my our'.split(' '));
const stem = (w) => w.replace(/ies$/, 'y').replace(/([^s])s$/, '$1');
const toks = (k) => [...new Set(k.split(/[^a-z0-9]+/).filter((w) => w && !STOP.has(w)).map(stem))];
const pool = raw.slice(1).map(parse).map((r) => { const kw = r[col('Keyword')].trim().toLowerCase(); return { cat: r[col('Category')], kw, t: toks(kw), vol: +r[col('Monthly searches')] || 0, comp: r[col('Competition')] + ' ' + r[col('Competition index')], ci: +r[col('Competition index')] || 0, cpc: +r[col('Avg CPC (PKR)')] || 0, fit: r[col('Niche fit')] }; });
const jac = (a, b) => { const B = new Set(b); const i = a.filter((x) => B.has(x)).length; return i / (a.length + b.length - i); };
const BRAND = /\b(semrush|ahrefs|moz|wix|squarespace|shopify|godaddy|hubspot|mailchimp|hootsuite|zoho|bluehost|siteground|accuranker|neil patel)\b/;
const byKw = new Map(pool.map((p) => [p.kw, p]));
const focusSet = new Set([...topics.map((t) => t.kw), ...done.map((d) => d.kw)]);

// Data for a topic: the exact keyword, else the closest variant that keeps all its words (at most one
// extra word, or the same words in another order). No match means the topic is not in the export.
for (const t of topics) {
	const tt = toks(t.kw);
	let m = byKw.get(t.kw);
	if (!m) m = pool.filter((p) => tt.every((x) => p.t.includes(x)) && p.t.length <= tt.length + 1).sort((a, b) => b.vol - a.vol)[0] || null;
	t.vol = m ? m.vol : ''; t.comp = m ? m.comp : ''; t.cpcPkr = m ? m.cpc : ''; t.matched = m ? m.kw : '';
	// Value for ordering: monthly ad value (volume x CPC), lifted for low ad competition so winnable topics come first.
	t.value = m ? m.vol * m.cpc * (m.ci <= 33 ? 1.3 : m.ci <= 66 ? 1 : 0.8) : 0;
	t.supporting = pool.filter((p) => p.fit === 'Yes' && !focusSet.has(p.kw) && !BRAND.test(p.kw) && p.kw !== t.matched && (jac(tt, p.t) >= 0.5 || tt.every((x) => p.t.includes(x))))
		.sort((a, b) => b.vol - a.vol).map((p) => p.kw).filter((k, i, a) => a.indexOf(k) === i).slice(0, 6);
}

// Spread categories across the year in proportion to their size, never the same category twice in a row.
const groups = {};
for (const t of topics) (groups[t.cat] = groups[t.cat] || []).push(t);
// Within each category: proven high-value topics first, topics without data after (check them in Keyword Planner).
for (const a of Object.values(groups)) a.sort((x, y) => y.value - x.value);
const size = Object.fromEntries(Object.entries(groups).map(([c, a]) => [c, a.length]));
const order = []; let last = '';
for (let i = 0; i < topics.length; i++) {
	const live = Object.keys(groups).filter((c) => groups[c].length);
	const cands = live.filter((c) => c !== last);
	const pick = (cands.length ? cands : live).sort((a, b) => groups[b].length / size[b] - groups[a].length / size[a] || groups[b].length - groups[a].length)[0];
	order.push(groups[pick].shift()); last = pick;
}

const slugify = (k) => k.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 70);
const plan = []; let n = 0;
for (let d = 0; d < ONE_A_DAY + TWO_A_DAY; d++) {
	const date = new Date(START + d * 86400000).toISOString().slice(0, 10);
	for (const time of d < ONE_A_DAY ? TIMES.one : TIMES.two) {
		const t = order[n++];
		const catId = t.cat === 'Business Finance' && /insurance/.test(t.kw) ? 299 : CAT_ID[t.cat];
		plan.push({ n, date, time, month: date.slice(0, 7), cat: t.cat, catId, title: t.title, slug: slugify(t.kw), kw: t.kw, vol: t.vol, comp: t.comp, cpcPkr: t.cpcPkr, cpcUsd: t.cpcPkr === '' ? '' : Math.round(t.cpcPkr / 280 * 100) / 100, poolMatch: t.matched, supporting: t.supporting, st: 'Planned' });
	}
}
fs.writeFileSync(path.join(DIR, 'plan-12-month.json'), JSON.stringify(plan, null, 1) + '\n');
const q = (v) => '"' + String(v).replace(/"/g, '""') + '"';
const csv = ['No.,Date,Time (UTC),Category,Working title,URL slug,Focus keyword,Monthly searches,Competition,Avg CPC (PKR),Avg CPC (USD approx.),Supporting / LSI keywords,Status']
	.concat(plan.map((p) => [p.n, p.date, p.time, p.cat, p.title, '/' + p.slug + '/', p.kw, p.vol, p.comp, p.cpcPkr, p.cpcUsd, p.supporting.join('; '), p.st].map(q).join(',')));
fs.writeFileSync(path.join(DIR, 'semrs-12-month-content-plan.csv'), '﻿' + csv.join('\r\n') + '\r\n');
const months = {}; plan.forEach((p) => { months[p.month] = (months[p.month] || 0) + 1; });
// Keywords with no data in the export, one per line, to paste into Google Keyword Planner
// ("Get search volume and forecasts"); add the download to semrs-keywords-all.csv and rebuild.
fs.writeFileSync(path.join(DIR, 'keywords-to-check.txt'), plan.filter((p) => p.vol === '').map((p) => p.kw).join('\n') + '\n');
console.log(plan.length, 'articles,', plan[0].date, '→', plan[plan.length - 1].date, '| matched in pool:', plan.filter((p) => p.vol !== '').length);
console.log(months);
