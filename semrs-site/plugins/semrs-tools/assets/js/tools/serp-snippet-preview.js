/* SERP Snippet Preview: desktop + mobile Google result with pixel-based truncation (approximate). */
SemrsTools.register('serp-snippet-preview', function (root, api) {
	var h = api.h;
	var saved = api.load(api.id) || {};
	var LIMITS = { dTitle: 600, dDesc: 920, mTitle: 660, mDesc: 680 };

	var title = h('input', { type: 'text', value: saved.title || 'Free SERP Snippet Preview Tool – See Your Google Result', maxlength: 200 });
	var desc = h('textarea', { rows: 4, maxlength: 400 });
	desc.value = saved.desc || 'Preview how your page title and meta description look in Google on desktop and mobile. Free, instant and no signup needed.';
	var url = h('input', { type: 'url', value: saved.url || 'https://semrs.com/tools/serp-snippet-preview/' });
	var kw = h('input', { type: 'text', value: saved.kw || 'serp preview', placeholder: 'e.g. serp preview' });
	var date = h('input', { type: 'checkbox' });
	date.checked = !!saved.date;

	var titleInfo = h('div'), descInfo = h('div');
	var input = api.panel('Your page');
	input.append(
		api.field('Page title', title), titleInfo,
		api.field('Meta description', desc), descInfo,
		api.field('Page URL', url),
		api.field('Search keyword (bolded like Google does)', kw),
		h('label', { class: 'semrs-tool__hint' }, date, ' Show a date before the description')
	);
	var out = api.panel('Google preview');
	root.append(input, out);

	function truncate(text, font, limit) {
		if (api.textWidth(text, font) <= limit) { return { text: text, cut: false }; }
		var words = text.split(' ');
		while (words.length > 1 && api.textWidth(words.join(' ') + ' …', font) > limit) { words.pop(); }
		return { text: words.join(' ') + ' …', cut: true };
	}
	function crumbs(u) {
		try {
			var p = new URL(u);
			var parts = p.pathname.split('/').filter(Boolean).map(decodeURIComponent);
			return { host: p.hostname.replace(/^www\./, ''), path: [p.protocol + '//' + p.hostname].concat(parts).join(' › ') };
		} catch (e) { return { host: 'example.com', path: 'https://example.com' }; }
	}
	function bolded(text, keyword) {
		var span = h('span');
		if (!keyword.trim()) { span.textContent = text; return span; }
		var words = keyword.trim().split(/\s+/).map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
		var re = new RegExp('(' + words.join('|') + ')', 'gi');
		text.split(re).forEach(function (part, i) { span.append(i % 2 ? h('b', { text: part }) : part); });
		return span;
	}
	function snippet(mobile) {
		var c = crumbs(url.value);
		var tf = (mobile ? 18 : 20) + 'px Arial';
		var t = truncate(title.value, tf, mobile ? LIMITS.mTitle : LIMITS.dTitle);
		var dText = (date.checked ? '' : '') + desc.value;
		var d = truncate(dText, '14px Arial', mobile ? LIMITS.mDesc : LIMITS.dDesc);
		var dateText = date.checked ? new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' — ' : '';
		return h('div', { class: 'semrs-serp' + (mobile ? ' semrs-serp--mobile' : '') },
			h('div', { class: 'semrs-serp__site' },
				h('span', { class: 'semrs-serp__fav', 'aria-hidden': 'true', text: c.host.charAt(0).toUpperCase() }),
				h('span', null, h('span', { class: 'semrs-serp__name', text: api.cfg.site && url.value.indexOf(location.hostname) > -1 ? api.cfg.site : c.host }), h('br'), h('span', { class: 'semrs-serp__url', text: c.path }))),
			h('div', { class: 'semrs-serp__title', text: t.text }),
			h('div', { class: 'semrs-serp__desc' }, dateText ? h('span', { class: 'semrs-serp__date', text: dateText }) : null, bolded(d.text, kw.value)));
	}
	function info(el, chars, px, maxChars, maxPx, minChars) {
		el.innerHTML = '';
		el.append(api.counter(chars, maxChars, 'characters', minChars), ' · ', api.counter(px, maxPx, 'px'));
	}
	function render() {
		var tpx = api.textWidth(title.value, '20px Arial');
		var dpx = api.textWidth(desc.value, '14px Arial');
		info(titleInfo, title.value.length, tpx, 60, LIMITS.dTitle, 30);
		info(descInfo, desc.value.length, dpx, 155, LIMITS.dDesc, 120);
		out.innerHTML = '';
		out.append(h('h3', { class: 'semrs-tool__title', text: 'Google preview' }),
			h('p', { class: 'semrs-serp__label', text: 'Desktop' }), snippet(false),
			h('p', { class: 'semrs-serp__label', text: 'Mobile' }), snippet(true),
			h('p', { class: 'semrs-tool__hint', text: 'Pixel limits are approximate. Google may also rewrite titles and descriptions.' }),
			h('div', { class: 'semrs-tool__actions' },
				api.copyBtn(function () { return '<title>' + title.value + '</title>\n<meta name="description" content="' + api.esc(desc.value) + '">'; }, 'Copy HTML tags', api.id)));
		api.store(api.id, { title: title.value, desc: desc.value, url: url.value, kw: kw.value, date: date.checked });
	}
	root.addEventListener('input', api.debounce(render, 80));
	root.addEventListener('change', render);
	render();
});
