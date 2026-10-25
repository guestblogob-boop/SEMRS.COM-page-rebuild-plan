/* Redirect Checker: calls the plugin REST endpoint and explains every hop. */
SemrsTools.register('redirect-checker', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var url = h('input', { type: 'url', placeholder: 'https://example.com/old-page/', required: true, inputmode: 'url' });
	var btn = h('button', { type: 'submit', class: 'wp-element-button semrs-btn', text: 'Check redirects' });
	var form = h('form', { class: 'semrs-tool__panel', novalidate: true },
		h('h2', { class: 'semrs-tool__title', text: 'Enter a URL' }),
		api.field('URL to check', url, 'Checks up to 10 hops. Public websites only.'),
		btn);
	var out = h('div', { class: 'semrs-tool__panel', 'aria-live': 'polite' }, h('p', { class: 'semrs-tool__hint', text: 'Results appear here.' }));
	root.append(form, out);

	var LABELS = { 200: 'OK', 301: 'Moved permanently', 302: 'Found (temporary)', 303: 'See other', 307: 'Temporary redirect', 308: 'Permanent redirect', 404: 'Not found', 410: 'Gone', 403: 'Forbidden', 500: 'Server error', 503: 'Unavailable' };

	function badge(code) {
		var color = code >= 200 && code < 300 ? 'var(--st-ok)' : (code >= 300 && code < 400 ? 'var(--st-warn)' : 'var(--st-bad)');
		return h('b', { style: 'color:' + color, text: code ? code + ' ' + (LABELS[code] || '') : 'Error' });
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var v = url.value.trim();
		if (!v) { url.focus(); return; }
		btn.disabled = true;
		btn.textContent = 'Checking…';
		out.innerHTML = '';
		out.append(h('p', { class: 'semrs-tool__hint', text: 'Following redirects…' }));
		api.track('tool_run', { tool_id: api.id });
		fetch(api.cfg.rest + 'redirects?url=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } })
			.then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
			.then(function (res) { res.ok ? show(res.j) : fail(res.j && res.j.message ? res.j.message : 'Something went wrong.'); })
			.catch(function () { fail('Could not reach the checker. Please try again.'); })
			.then(function () { btn.disabled = false; btn.textContent = 'Check redirects'; });
	});

	function fail(msg) { out.innerHTML = ''; out.append(h('p', { class: 'semrs-tool__count is-over', text: msg })); }

	function show(data) {
		var hops = data.hops || [];
		var last = hops[hops.length - 1] || {};
		var redirects = hops.filter(function (x) { return x.status >= 300 && x.status < 400; }).length;
		var issues = [];
		if (redirects > 1) { issues.push(['bad', redirects + ' redirects in a chain. Point the first URL straight to the final one (one hop) to save crawl budget and speed.']); }
		if (redirects === 1) { issues.push(['ok', 'Single redirect: good.']); }
		if (redirects === 0 && last.status === 200) { issues.push(['ok', 'No redirect: the URL loads directly.']); }
		hops.forEach(function (x) {
			if (x.status === 302 || x.status === 307) { issues.push(['warn', 'A temporary (' + x.status + ') redirect is used. If the move is permanent, use 301 or 308.']); }
			if (x.note === 'loop') { issues.push(['bad', 'Redirect loop detected.']); }
			if (x.robots && /noindex/i.test(x.robots)) { issues.push(['bad', 'X-Robots-Tag: noindex on ' + x.url]); }
		});
		if (hops.length && /^http:/i.test(hops[0].url) && /^https:/i.test(last.url || '')) { issues.push(['ok', 'HTTP redirects to HTTPS.']); }
		if (last.status >= 400) { issues.push(['bad', 'The final URL returns ' + last.status + '. Fix or redirect it to a live page.']); }
		if (!last.status) { issues.push(['bad', 'The final request failed' + (last.note ? ': ' + last.note : '') + '.']); }

		var table = h('table', { class: 'semrs-tool__table' }, h('thead', null, h('tr', null, h('th', { text: '#' }), h('th', { text: 'URL' }), h('th', { text: 'Status' }), h('th', { text: 'Time' }))));
		var body = h('tbody');
		hops.forEach(function (x, i) { body.append(h('tr', null, h('td', { text: i + 1 }), h('td', { text: x.url }), h('td', null, badge(x.status)), h('td', { text: x.ms ? x.ms + ' ms' : '—' }))); });
		table.append(body);
		var ul = h('ul', { class: 'semrs-tool__checks' });
		issues.forEach(function (i) { ul.append(h('li', { class: i[0], text: i[1] })); });

		out.innerHTML = '';
		out.append(
			h('div', { class: 'semrs-tool__stats' },
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: redirects }), h('span', { text: 'redirects' })),
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: last.status || '—' }), h('span', { text: 'final status' })),
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: hops.reduce(function (a, x) { return a + (x.ms || 0); }, 0) + ' ms' }), h('span', { text: 'total time' }))),
			h('div', { style: 'overflow-x:auto' }, table), ul,
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () {
				return hops.map(function (x, i) { return (i + 1) + '. ' + x.status + ' ' + x.url; }).join('\n');
			}, 'Copy report', api.id)));
	}
});
