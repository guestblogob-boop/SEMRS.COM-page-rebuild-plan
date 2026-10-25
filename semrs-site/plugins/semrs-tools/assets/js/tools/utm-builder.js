/* UTM Builder: single link with presets + bulk mode with CSV export. */
SemrsTools.register('utm-builder', function (root, api) {
	var h = api.h;
	var PRESETS = {
		'': ['', ''],
		'Google Ads': ['google', 'cpc'], 'Microsoft Ads': ['bing', 'cpc'], 'Facebook ads': ['facebook', 'paid_social'],
		'Instagram ads': ['instagram', 'paid_social'], 'LinkedIn ads': ['linkedin', 'paid_social'], 'TikTok ads': ['tiktok', 'paid_social'],
		'Facebook post': ['facebook', 'social'], 'LinkedIn post': ['linkedin', 'social'], 'Newsletter': ['newsletter', 'email'],
		'Google Business Profile': ['google', 'organic_gbp'], 'QR code / print': ['qr', 'offline']
	};
	var s = api.load(api.id) || {};
	var mk = function (key, ph) { var el = h('input', { type: 'text', value: s[key] || '', placeholder: ph }); el.dataset.key = key; return el; };
	var url = h('input', { type: 'url', value: s.url || '', placeholder: 'https://example.com/landing-page/' });
	var preset = h('select');
	Object.keys(PRESETS).forEach(function (p) { preset.append(h('option', { value: p, text: p || 'Choose a preset (optional)' })); });
	var f = { source: mk('source', 'google, newsletter'), medium: mk('medium', 'cpc, email, social'), campaign: mk('campaign', 'spring_sale'), term: mk('term', 'optional: paid keyword'), content: mk('content', 'optional: ad or link variant'), id: mk('id', 'optional: campaign ID') };
	var lower = h('input', { type: 'checkbox', checked: s.lower !== false });
	var bulk = h('textarea', { rows: 4, placeholder: 'Bulk mode (optional): one URL per line. The same UTM values are added to each.' });
	bulk.value = s.bulk || '';

	preset.addEventListener('change', function () { var p = PRESETS[preset.value]; if (p[0]) { f.source.value = p[0]; f.medium.value = p[1]; render(); } });

	var input = api.panel('Campaign details');
	input.append(api.field('Website URL', url), api.field('Preset', preset),
		h('div', { class: 'semrs-tool__row' }, api.field('Source (utm_source) *', f.source), api.field('Medium (utm_medium) *', f.medium)),
		api.field('Campaign name (utm_campaign) *', f.campaign),
		h('div', { class: 'semrs-tool__row' }, api.field('Term (utm_term)', f.term), api.field('Content (utm_content)', f.content)),
		api.field('Campaign ID (utm_id)', f.id),
		h('label', { class: 'semrs-tool__hint' }, lower, ' Lowercase and replace spaces with underscores (keeps reports clean)'),
		h('div', { style: 'height:1rem' }), api.field('Bulk URLs', bulk));
	var out = api.panel('Your tracked link');
	root.append(input, out);

	function clean(v) { v = v.trim(); return lower.checked ? v.toLowerCase().replace(/\s+/g, '_') : v; }
	function tag(base) {
		try {
			var u = new URL(base.trim());
			[['utm_source', 'source'], ['utm_medium', 'medium'], ['utm_campaign', 'campaign'], ['utm_term', 'term'], ['utm_content', 'content'], ['utm_id', 'id']].forEach(function (p) {
				var v = clean(f[p[1]].value);
				if (v) { u.searchParams.set(p[0], v); } else { u.searchParams.delete(p[0]); }
			});
			return u.toString();
		} catch (e) { return ''; }
	}
	function bulkRows() { return bulk.value.split(/\n/).map(function (l) { return l.trim(); }).filter(Boolean).map(function (l) { return [l, tag(l)]; }); }
	function csv() { return 'original_url,tagged_url\n' + bulkRows().map(function (r) { return '"' + r[0].replace(/"/g, '""') + '","' + r[1].replace(/"/g, '""') + '"'; }).join('\n') + '\n'; }

	function render() {
		var warn = [];
		var link = url.value.trim() ? tag(url.value) : '';
		if (url.value.trim() && !link) { warn.push(['bad', 'Enter a full URL starting with https://']); }
		['source', 'medium', 'campaign'].forEach(function (k) { if (!f[k].value.trim()) { warn.push(['warn', 'utm_' + k + ' is required for clean reports in Google Analytics.']); } });
		if (/^(cpc|email|social|organic)$/i.test(f.source.value.trim())) { warn.push(['warn', 'That looks like a medium. Source is where traffic comes from (google, facebook, newsletter).']); }
		if (link && link.indexOf(location.hostname) > -1 && f.source.value) { warn.push(['warn', 'Don’t use UTM tags on links between pages of your own site; they overwrite the real traffic source.']); }
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Your tracked link' }), h('pre', { class: 'semrs-tool__output', text: link || 'Fill in the URL and campaign details.' }));
		if (link) { out.append(h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () { return tag(url.value); }, 'Copy link', api.id))); }
		if (warn.length) { var ul = h('ul', { class: 'semrs-tool__checks' }); warn.forEach(function (w) { ul.append(h('li', { class: w[0], text: w[1] })); }); out.append(ul); }
		var rows = bulkRows();
		if (rows.length) {
			out.append(h('p', { class: 'semrs-serp__label', text: 'Bulk links (' + rows.length + ')' }),
				h('pre', { class: 'semrs-tool__output', text: rows.map(function (r) { return r[1] || '✖ invalid: ' + r[0]; }).join('\n') }),
				h('div', { class: 'semrs-tool__actions' },
					api.copyBtn(function () { return bulkRows().map(function (r) { return r[1]; }).filter(Boolean).join('\n'); }, 'Copy all', api.id),
					api.downloadBtn(csv, 'utm-links.csv', 'text/csv', 'Download CSV', api.id)));
		}
		var save = { url: url.value, bulk: bulk.value, lower: lower.checked };
		Object.keys(f).forEach(function (k) { save[k] = f[k].value; });
		api.store(api.id, save);
	}
	root.addEventListener('input', api.debounce(render, 100));
	root.addEventListener('change', render);
	render();
});
