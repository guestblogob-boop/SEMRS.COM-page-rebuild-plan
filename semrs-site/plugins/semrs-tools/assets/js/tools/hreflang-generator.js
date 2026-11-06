/* Hreflang Tag Generator: HTML link tags, XML sitemap entries or HTTP headers, with validation. */
SemrsTools.register('hreflang-generator', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var LANGS = 'af am ar az be bg bn bs ca cs cy da de el en es et eu fa fi fil fr ga gl gu he hi hr hu hy id is it ja ka kk km kn ko lo lt lv mk ml mn mr ms mt my nb ne nl no pa pl pt ro ru si sk sl sq sr sv sw ta te th tl tr uk ur uz vi zh zu'.split(' ');
	var s = api.load(api.id) || { rows: [{ code: 'en-us', url: '' }, { code: 'en-gb', url: '' }, { code: 'es', url: '' }], xd: '', format: 'html' };
	var input = api.panel('Page versions');
	var out = api.panel('Your tags');
	root.append(input, out);
	var format = h('select', null, h('option', { value: 'html', text: 'HTML <link> tags (in <head>)' }), h('option', { value: 'xml', text: 'XML sitemap entries' }), h('option', { value: 'header', text: 'HTTP Link header (PDFs and files)' }));
	format.value = s.format || 'html';
	var xd = h('input', { type: 'url', value: s.xd || '', placeholder: 'https://example.com/ (language picker or main version)' });
	format.addEventListener('change', function () { s.format = format.value; render(); });
	xd.addEventListener('input', function () { s.xd = xd.value; render(); });

	function build() {
		input.innerHTML = '';
		input.append(h('h2', { class: 'semrs-tool__title', text: 'Page versions' }),
			h('p', { class: 'semrs-tool__hint', text: 'Add one row per language or country version of the same page. Code = language (ISO 639-1), optionally plus country (ISO 3166-1), e.g. en, en-gb, es-mx, zh-tw.' }));
		s.rows.forEach(function (r, i) {
			var code = h('input', { type: 'text', value: r.code, placeholder: 'en-us', list: 'semrs-hl-codes', 'aria-label': 'Language code ' + (i + 1) });
			var url = h('input', { type: 'url', value: r.url, placeholder: 'https://example.com/en-us/page/', 'aria-label': 'URL ' + (i + 1) });
			code.addEventListener('input', function () { r.code = code.value; render(); });
			url.addEventListener('input', function () { r.url = url.value; render(); });
			input.append(h('div', { class: 'semrs-tool__row semrs-tool__row--hl' }, code, url,
				h('button', { type: 'button', class: 'semrs-btn semrs-btn--ghost semrs-btn--small', 'aria-label': 'Remove row ' + (i + 1), text: '✕', onclick: function () { s.rows.splice(i, 1); build(); render(); } })));
		});
		var dl = h('datalist', { id: 'semrs-hl-codes' });
		['en', 'en-us', 'en-gb', 'en-au', 'en-ca', 'es', 'es-es', 'es-mx', 'fr', 'fr-ca', 'de', 'de-at', 'de-ch', 'it', 'pt-br', 'pt-pt', 'nl', 'ja', 'zh-cn', 'zh-tw', 'ar', 'hi', 'ur'].forEach(function (c) { dl.append(h('option', { value: c })); });
		input.append(dl, h('button', { type: 'button', class: 'semrs-btn semrs-btn--small', text: '+ Add version', onclick: function () { s.rows.push({ code: '', url: '' }); build(); render(); } }),
			h('div', { style: 'height:1rem' }), api.field('x-default URL (optional, recommended)', xd), api.field('Output', format));
	}

	function norm(c) { var p = c.trim().replace('_', '-').split('-'); return p[0].toLowerCase() + (p[1] ? '-' + p[1].toUpperCase() : ''); }

	function render() {
		var rows = s.rows.filter(function (r) { return r.code.trim() && r.url.trim(); });
		var warn = [], seen = {};
		rows.forEach(function (r) {
			var c = norm(r.code), p = c.split('-');
			if (LANGS.indexOf(p[0].toLowerCase()) < 0) { warn.push(['bad', '“' + r.code + '” does not start with a known language code. Use e.g. en, es, fr.']); }
			if (p[1] && !/^[A-Z]{2}$/.test(p[1]) && !/^\d{3}$/.test(p[1])) { warn.push(['bad', '“' + r.code + '”: the country part must be two letters (e.g. GB, not UK-English).']); }
			if (p[1] === 'UK') { warn.push(['bad', 'Use en-GB, not en-UK, for the United Kingdom.']); }
			if (seen[c]) { warn.push(['bad', c + ' is listed twice.']); }
			seen[c] = 1;
			if (!/^https?:\/\//.test(r.url.trim())) { warn.push(['bad', r.url + ' must be a full URL starting with https://']); }
		});
		if (rows.length < 2) { warn.push(['warn', 'Add at least two versions. Hreflang links pages that are translations of each other.']); }
		if (!s.xd) { warn.push(['warn', 'Add an x-default URL for visitors whose language you do not cover.']); }
		var all = rows.map(function (r) { return [norm(r.code), r.url.trim()]; });
		if (s.xd) { all.push(['x-default', s.xd.trim()]); }
		var e = api.esc, text;
		if (s.format === 'xml') {
			text = rows.map(function (r) {
				return '<url>\n  <loc>' + e(r.url.trim()) + '</loc>\n' + all.map(function (a) { return '  <xhtml:link rel="alternate" hreflang="' + a[0] + '" href="' + e(a[1]) + '"/>'; }).join('\n') + '\n</url>';
			}).join('\n') + '\n\n<!-- The <urlset> tag needs xmlns:xhtml="http://www.w3.org/1999/xhtml" -->';
		} else if (s.format === 'header') {
			text = 'Link: ' + all.map(function (a) { return '<' + a[1] + '>; rel="alternate"; hreflang="' + a[0] + '"'; }).join(', ');
		} else {
			text = all.map(function (a) { return '<link rel="alternate" hreflang="' + a[0] + '" href="' + e(a[1]) + '">'; }).join('\n');
		}
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Your tags' }), h('pre', { class: 'semrs-tool__output', text: all.length ? text : 'Add page versions to see the tags.' }));
		if (all.length) { out.append(h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () { return text; }, 'Copy', api.id))); }
		if (warn.length) { out.append(api.checks(warn)); }
		out.append(h('p', { class: 'semrs-tool__hint', text: 'Put the same full set on every version, including a link to the page itself. Each version should also have a self-referencing canonical. Links must be return links: if A points to B, B must point to A.' }));
		api.store(api.id, s);
	}
	build();
	render();
});
