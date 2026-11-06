/* On-Page SEO Checker: the server reads the page, the browser scores it. Bulk mode (Pro): up to 25 URLs with CSV. */
SemrsTools.register('on-page-seo-checker', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var bulkMax = api.cfg.bulk || 0;
	var url = h('input', { type: 'url', placeholder: 'https://example.com/page/', inputmode: 'url' });
	var kw = h('input', { type: 'text', placeholder: 'optional, e.g. plumber austin' });
	var list = h('textarea', { rows: 5, placeholder: 'One URL per line (up to ' + bulkMax + ')' });
	var btn = h('button', { type: 'submit', class: 'wp-element-button semrs-btn', text: 'Check page' });
	var form = h('form', { class: 'semrs-tool__panel', novalidate: true },
		h('h2', { class: 'semrs-tool__title', text: 'Page to check' }),
		api.field('Page URL', url, 'Public pages only. The page is read once, like a search engine would.'),
		api.field('Focus keyword', kw),
		bulkMax ? api.field('Bulk check (optional)', list, 'Checks each URL in turn and gives a score table you can download as CSV.') : null,
		btn);
	var out = h('div', { class: 'semrs-tool__panel', 'aria-live': 'polite' }, h('p', { class: 'semrs-tool__hint', text: 'Results appear here.' }));
	root.append(form, out);

	function len(s) { return (s || '').length; }

	/** Turn the facts into checks: [state, text, points]. */
	function score(d, k) {
		var c = [];
		k = (k || '').trim().toLowerCase();
		function add(ok, good, bad, warn) { c.push([ok ? 'ok' : (warn ? 'warn' : 'bad'), ok ? good : bad]); }
		add(d.status === 200, 'Page answers 200 OK.', 'Page answers ' + d.status + '. Search engines only index pages that answer 200.');
		add(d.https, 'Served over HTTPS.', 'Page is not on HTTPS. Move the site to HTTPS.');
		if (d.hops) { add(false, '', d.hops + ' redirect(s) before the page loads. Link straight to ' + d.final, true); }
		var noindex = /noindex/i.test(d.robots || '') || /noindex/i.test(d.xrobots || '');
		add(!noindex, 'Page may be indexed.', 'The page says noindex, so Google will drop it from search.');
		add(len(d.title) >= 30 && len(d.title) <= 60, 'Title is ' + len(d.title) + ' characters.', d.title ? 'Title is ' + len(d.title) + ' characters (aim for 30–60).' : 'The page has no title tag.', d.title && len(d.title) > 10);
		if (d.titles > 1) { add(false, '', d.titles + ' title tags found. Keep one.'); }
		add(len(d.description) >= 120 && len(d.description) <= 160, 'Meta description is ' + len(d.description) + ' characters.', d.description ? 'Meta description is ' + len(d.description) + ' characters (aim for 120–160).' : 'No meta description. Google will pick text from the page.', !!d.description);
		if (d.descs > 1) { add(false, '', d.descs + ' meta descriptions found. Keep one.'); }
		var h1 = d.headings.filter(function (x) { return x[0] === 1; });
		add(h1.length === 1, 'One H1 heading: “' + (h1[0] ? h1[0][1] : '') + '”.', h1.length ? h1.length + ' H1 headings. Use one H1 for the main topic.' : 'No H1 heading. Add one that says what the page is about.', h1.length > 1);
		var skip = false;
		d.headings.reduce(function (p, x) { if (x[0] > p + 1) { skip = true; } return x[0]; }, 1);
		add(!skip, 'Headings follow in order.', 'A heading level is skipped (e.g. H2 → H4). Keep them in order for screen readers and AI.', true);
		add(!!d.canonical, 'Canonical tag: ' + d.canonical, 'No canonical tag. Add one that points to the preferred URL.', true);
		if (d.canonical && d.canonical.replace(/\/$/, '') !== d.final.replace(/[?#].*$/, '').replace(/\/$/, '')) { add(false, '', 'Canonical points to another URL (' + d.canonical + '). Fine for duplicates, wrong for the main page.', true); }
		add(!!d.viewport, 'Mobile viewport tag present.', 'No viewport tag. The page will not scale on phones.');
		add(!!d.lang, 'Language set (' + d.lang + ').', 'No lang attribute on <html>.', true);
		add(d.words >= 300, d.words + ' words of text.', 'Only ' + d.words + ' words. Thin pages rarely rank; add useful detail.', d.words >= 150);
		add(d.images.noalt === 0, d.images.total ? 'All ' + d.images.total + ' images have alt text.' : 'No images (consider one helpful image).', d.images.noalt + ' of ' + d.images.total + ' images have no alt attribute.', d.images.noalt < 3);
		if (d.images.nosize) { add(false, '', d.images.nosize + ' images have no width and height. That causes layout shift (CLS).', true); }
		add(d.links.internal >= 3, d.links.internal + ' internal links.', 'Only ' + d.links.internal + ' internal links. Link to related pages.', true);
		if (d.links.empty) { add(false, '', d.links.empty + ' links have no text or label. Screen readers and search engines cannot tell where they go.', true); }
		add(d.schema.length > 0 && d.schema.indexOf('!invalid') < 0, d.schema.length ? 'Structured data: ' + d.schema.filter(function (t) { return t !== '!invalid'; }).slice(0, 8).join(', ') + '.' : '', d.schema.indexOf('!invalid') > -1 ? 'A JSON-LD block is not valid JSON.' : 'No structured data (schema). Add Organization, Article or the type that fits.', d.schema.indexOf('!invalid') < 0);
		add(!!(d.og.title && d.og.image), 'Open Graph title and image set.', 'Missing Open Graph ' + (!d.og.title ? 'title' : 'image') + '. Shared links will look plain.', true);
		add(d.bytes < 1500000, 'HTML size ' + Math.round(d.bytes / 1024) + ' KB.', 'HTML is ' + Math.round(d.bytes / 1024) + ' KB. Large pages load slowly.', true);
		add(d.ms < 1500, 'Server answered in ' + d.ms + ' ms.', 'Server took ' + d.ms + ' ms. Aim for under 800 ms (use page caching).', d.ms < 3000);
		if (k) {
			var inT = (d.title || '').toLowerCase().indexOf(k) > -1, inD = (d.description || '').toLowerCase().indexOf(k) > -1;
			var inH = d.headings.some(function (x) { return x[0] <= 2 && x[1].toLowerCase().indexOf(k) > -1; });
			var inU = d.final.toLowerCase().indexOf(k.replace(/\s+/g, '-')) > -1;
			add(inT, 'Keyword in the title.', 'Keyword is not in the title.');
			add(inD, 'Keyword in the meta description.', 'Keyword is not in the meta description.', true);
			add(inH, 'Keyword in the H1 or an H2.', 'Keyword is not in the H1 or any H2.', true);
			add(inU, 'Keyword in the URL.', 'Keyword is not in the URL (fine for existing pages; do not change working URLs just for this).', true);
		}
		var pts = c.reduce(function (a, x) { return a + (x[0] === 'ok' ? 1 : (x[0] === 'warn' ? 0.5 : 0)); }, 0);
		return { checks: c, score: Math.round(pts / c.length * 100) };
	}

	function fail(msg) { out.innerHTML = ''; out.append(h('p', { class: 'semrs-tool__count is-over', text: msg })); }

	function single(v) {
		api.get('onpage', { url: v }).then(function (res) {
			if (!res.ok) { return fail(res.j && res.j.message ? res.j.message : 'Something went wrong.'); }
			var d = res.j;
			if (d.nothtml) { return fail('That address is not an HTML page (' + (d.type || 'unknown type') + ').'); }
			var r = score(d, kw.value);
			var order = { bad: 0, warn: 1, ok: 2 };
			r.checks.sort(function (a, b) { return order[a[0]] - order[b[0]]; });
			var heads = h('ol', { class: 'semrs-tool__outline' });
			d.headings.slice(0, 40).forEach(function (x) { heads.append(h('li', { style: 'margin-left:' + (x[0] - 1) + 'rem', text: 'H' + x[0] + ' · ' + (x[1] || '(empty)') })); });
			out.innerHTML = '';
			out.append(
				h('div', { class: 'semrs-tool__stats' },
					api.stat(r.score + '/100', 'on-page score'),
					api.stat(r.checks.filter(function (x) { return x[0] === 'bad'; }).length, 'problems'),
					api.stat(r.checks.filter(function (x) { return x[0] === 'warn'; }).length, 'improvements'),
					api.stat(d.words, 'words'),
					api.stat(d.links.internal + ' / ' + d.links.external, 'internal / external links')),
				api.checks(r.checks.map(function (x) { return [x[0], x[1]]; })),
				h('p', { class: 'semrs-serp__label', text: 'What Google reads' }),
				h('pre', { class: 'semrs-tool__output', text: 'Title: ' + (d.title || '—') + '\nDescription: ' + (d.description || '—') + '\nCanonical: ' + (d.canonical || '—') + '\nRobots: ' + (d.robots || 'index, follow (default)') + '\nFinal URL: ' + d.final }),
				d.headings.length ? h('details', null, h('summary', { text: 'Heading outline (' + d.headings.length + ')' }), heads) : null,
				h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () {
					return 'On-page SEO report: ' + d.final + '\nScore: ' + r.score + '/100\n\n' + r.checks.map(function (x) { return (x[0] === 'ok' ? '✓ ' : (x[0] === 'warn' ? '! ' : '✗ ')) + x[1]; }).join('\n');
				}, 'Copy report', api.id)),
				h('p', { class: 'semrs-tool__hint', text: 'Checks the HTML as served. Content added later by JavaScript is not seen here, and Google may render more.' }));
		}).catch(function () { fail('Could not reach the checker. Please try again.'); }).then(done);
	}

	function bulk(urls) {
		var rows = [];
		var table = h('table', { class: 'semrs-tool__table' }, h('thead', null, h('tr', null, ['URL', 'Status', 'Score', 'Title', 'Words', 'Problems'].map(function (t) { return h('th', { text: t }); }))));
		var body = h('tbody');
		table.append(body);
		var status = h('p', { class: 'semrs-tool__hint' });
		out.innerHTML = '';
		out.append(status, h('div', { style: 'overflow-x:auto' }, table));
		var i = 0;
		(function next() {
			if (i >= urls.length) {
				status.textContent = 'Checked ' + urls.length + ' URLs.';
				out.append(h('div', { class: 'semrs-tool__actions' }, api.downloadBtn(function () {
					return 'url,status,score,title,words,problems\n' + rows.map(function (r) { return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(','); }).join('\n') + '\n';
				}, 'on-page-seo.csv', 'text/csv', 'Download CSV', api.id)));
				return done();
			}
			var u = urls[i++];
			status.textContent = 'Checking ' + i + ' of ' + urls.length + '…';
			api.get('onpage', { url: u }).then(function (res) {
				var row;
				if (!res.ok || res.j.nothtml) { row = [u, res.j && res.j.status ? res.j.status : 'error', '', res.j && res.j.message ? res.j.message : 'not an HTML page', '', '']; }
				else { var r = score(res.j, kw.value); row = [res.j.final, res.j.status, r.score, res.j.title, res.j.words, r.checks.filter(function (x) { return x[0] === 'bad'; }).map(function (x) { return x[1]; }).join(' | ')]; }
				rows.push(row);
				var shown = row.slice(0, 5).concat([row[2] === '' ? '' : (row[5] ? row[5].split(' | ').length : 0)]);
				body.append(h('tr', null, shown.map(function (c) { return h('td', { text: String(c) }); })));
				if (res.j && res.j.code === 'semrs_rate') { i = urls.length; }
			}).catch(function () { rows.push([u, 'error', '', '', '', '']); }).then(next);
		})();
	}

	function done() { btn.disabled = false; btn.textContent = 'Check page'; }

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var many = bulkMax ? list.value.split(/\n/).map(function (l) { return l.trim(); }).filter(Boolean).slice(0, bulkMax) : [];
		var v = url.value.trim();
		if (!v && !many.length) { url.focus(); return; }
		btn.disabled = true;
		btn.textContent = 'Checking…';
		out.innerHTML = '';
		out.append(h('p', { class: 'semrs-tool__hint', text: 'Reading the page…' }));
		api.track('tool_run', { tool_id: api.id });
		if (many.length) { bulk(many); } else { single(v); }
	});
});
