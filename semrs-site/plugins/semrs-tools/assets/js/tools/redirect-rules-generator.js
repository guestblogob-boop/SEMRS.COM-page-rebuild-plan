/* Redirect Rules Generator: old → new URL pairs to Apache .htaccess, Nginx or CSV (for redirect plugins). */
SemrsTools.register('redirect-rules-generator', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var s = api.load(api.id) || {};
	var pairs = h('textarea', { rows: 8, placeholder: '/old-page/ /new-page/\n/2019/05/post-name/ https://example.com/blog/post-name/\nhttps://example.com/services.html, /services/' });
	pairs.value = s.pairs || '';
	var server = h('select', null, h('option', { value: 'apache', text: 'Apache (.htaccess)' }), h('option', { value: 'nginx', text: 'Nginx' }), h('option', { value: 'csv', text: 'CSV (import into a redirect plugin)' }));
	server.value = s.server || 'apache';
	var code = h('select', null, h('option', { value: '301', text: '301 Permanent (recommended)' }), h('option', { value: '302', text: '302 Temporary' }), h('option', { value: '308', text: '308 Permanent (keeps method)' }), h('option', { value: '410', text: '410 Gone (deleted, no replacement)' }));
	code.value = s.code || '301';
	var input = api.panel('Old and new URLs');
	input.append(api.field('One redirect per line: old URL, then new URL', pairs, 'Separate them with a space, tab, comma or arrow (→, ->). For 410 only the old URL is needed. Paste straight from a spreadsheet.'),
		h('div', { class: 'semrs-tool__row' }, api.field('Server', server), api.field('Redirect type', code)));
	var out = api.panel('Your rules');
	root.append(input, out);

	function pathOf(u) {
		if (/^https?:\/\//i.test(u)) { try { var x = new URL(u); return { path: x.pathname, query: x.search.slice(1), host: x.hostname }; } catch (e) { return null; } }
		var q = u.indexOf('?');
		var p = q > -1 ? u.slice(0, q) : u;
		return { path: p.charAt(0) === '/' ? p : '/' + p, query: q > -1 ? u.slice(q + 1) : '', host: '' };
	}
	function reEsc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

	function parse() {
		var rows = [], warn = [], seen = {}, gone = code.value === '410';
		pairs.value.split(/\r?\n/).forEach(function (line, n) {
			var l = line.trim();
			if (!l || l.charAt(0) === '#') { return; }
			var parts = l.split(/\s*(?:→|->|=>|,|\t|\s)\s*/).filter(Boolean);
			if (!gone && parts.length < 2) { warn.push(['bad', 'Line ' + (n + 1) + ' has no new URL.']); return; }
			var from = pathOf(parts[0]);
			if (!from) { warn.push(['bad', 'Line ' + (n + 1) + ': “' + parts[0] + '” is not a valid URL.']); return; }
			var to = gone ? '' : parts[1];
			if (seen[from.path + '?' + from.query]) { warn.push(['warn', from.path + ' is listed twice; only the first rule will work.']); }
			seen[from.path + '?' + from.query] = 1;
			if (!gone && (pathOf(to) || {}).path === from.path && !/^https?:/i.test(to)) { warn.push(['bad', 'Line ' + (n + 1) + ' redirects to itself (loop).']); }
			rows.push({ from: from, to: to, line: n + 1 });
		});
		var targets = {};
		rows.forEach(function (r) { targets[r.from.path] = r; });
		rows.forEach(function (r) { var t = r.to && targets[(pathOf(r.to) || {}).path]; if (t && t !== r) { warn.push(['warn', 'Chain: ' + r.from.path + ' → ' + r.to + ' → ' + t.to + '. Point it straight to the final URL.']); } });
		return { rows: rows, warn: warn };
	}

	function rules(p) {
		var c = code.value;
		if (server.value === 'csv') {
			return 'source,target,code\n' + p.rows.map(function (r) { return '"' + (r.from.path + (r.from.query ? '?' + r.from.query : '')).replace(/"/g, '""') + '","' + (r.to || '').replace(/"/g, '""') + '",' + c; }).join('\n') + '\n';
		}
		if (server.value === 'nginx') {
			return '# Inside your server { } block\n' + p.rows.map(function (r) {
				if (r.from.query) { return 'if ($request_uri = "' + r.from.path + '?' + r.from.query + '") { return ' + c + (r.to ? ' ' + r.to : '') + '; }'; }
				return 'location = ' + r.from.path + ' { return ' + c + (r.to ? ' ' + r.to : '') + '; }';
			}).join('\n') + '\n';
		}
		var needRewrite = p.rows.some(function (r) { return r.from.query; });
		var lines = ['# SEMRS redirects – put above the WordPress block in .htaccess'];
		if (needRewrite) { lines.push('RewriteEngine On'); }
		p.rows.forEach(function (r) {
			if (r.from.query) {
				lines.push('RewriteCond %{QUERY_STRING} ^' + reEsc(r.from.query) + '$');
				lines.push('RewriteRule ^' + reEsc(r.from.path.slice(1)) + '$ ' + (c === '410' ? '- [G,L]' : r.to + '? [R=' + c + ',L]'));
			} else if (c === '410') {
				lines.push('Redirect gone ' + r.from.path.replace(/ /g, '%20'));
			} else {
				lines.push('Redirect ' + c + ' ' + r.from.path.replace(/ /g, '%20') + ' ' + r.to);
			}
		});
		return lines.join('\n') + '\n';
	}

	function render() {
		var p = parse(), text = rules(p);
		var ext = { apache: 'htaccess-redirects.txt', nginx: 'redirects.conf', csv: 'redirects.csv' }[server.value];
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Your rules' }),
			h('div', { class: 'semrs-tool__stats' }, api.stat(p.rows.length, 'redirects'), api.stat(p.warn.length, 'warnings')),
			h('pre', { class: 'semrs-tool__output', text: p.rows.length ? text : 'Paste your old and new URLs.' }));
		if (p.rows.length) {
			out.append(h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () { return rules(parse()); }, 'Copy', api.id), api.downloadBtn(function () { return rules(parse()); }, ext, server.value === 'csv' ? 'text/csv' : 'text/plain', 'Download', api.id)));
		}
		if (p.warn.length) { out.append(api.checks(p.warn.slice(0, 12))); }
		out.append(h('p', { class: 'semrs-tool__hint', text: 'Back up your .htaccess before editing; one typo can take the site down. On WordPress you can import the CSV into SEMRS Core → Redirects instead of editing server files. Test a few old URLs with the Redirect Checker afterwards.' }));
		api.store(api.id, { pairs: pairs.value.length < 50000 ? pairs.value : '', server: server.value, code: code.value });
	}
	root.addEventListener('input', api.debounce(render, 150));
	root.addEventListener('change', render);
	render();
});
