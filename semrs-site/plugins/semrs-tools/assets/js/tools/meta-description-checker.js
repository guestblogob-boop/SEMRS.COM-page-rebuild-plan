/* Meta Title & Description Checker: length, pixel width, keyword checks, ready-to-copy tags. */
SemrsTools.register('meta-description-checker', function (root, api) {
	var h = api.h;
	var s = api.load(api.id) || {};
	var title = h('input', { type: 'text', value: s.title || '', placeholder: 'Monthly SEO Services for Small Business | Brand' });
	var desc = h('textarea', { rows: 4, placeholder: 'What the page offers, who it is for, and a reason to click.' });
	desc.value = s.desc || '';
	var kw = h('input', { type: 'text', value: s.kw || '', placeholder: 'Focus keyword, e.g. seo services' });
	var url = h('input', { type: 'url', value: s.url || '', placeholder: 'https://example.com/page/ (optional)' });

	var input = api.panel('Enter your tags');
	input.append(api.field('Focus keyword', kw), api.field('Title tag', title), api.field('Meta description', desc), api.field('Page URL', url, 'Used for the canonical and Open Graph tags.'));
	var out = api.panel('Results');
	root.append(input, out);

	function check(ok, text, warn) { return h('li', { class: ok ? 'ok' : (warn ? 'warn' : 'bad'), text: text }); }

	function render() {
		var t = title.value.trim(), d = desc.value.trim(), k = kw.value.trim().toLowerCase();
		var tl = t.length, dl = d.length;
		var tpx = api.textWidth(t, '20px Arial'), dpx = api.textWidth(d, '14px Arial');
		var list = h('ul', { class: 'semrs-tool__checks' });
		if (!t && !d) {
			out.innerHTML = '';
			out.append(h('h2', { class: 'semrs-tool__title', text: 'Results' }), h('p', { class: 'semrs-tool__hint', text: 'Type a title and description to see the checks.' }));
			return;
		}
		list.append(
			check(tl >= 30 && tl <= 60, 'Title length: ' + tl + ' characters (aim for 30–60)'),
			check(tpx <= 600, 'Title width: ~' + tpx + ' px (Google shows about 600 px on desktop)'),
			check(dl >= 120 && dl <= 155, 'Description length: ' + dl + ' characters (aim for 120–155)', dl > 70 && dl < 120),
			check(dpx <= 920, 'Description width: ~' + dpx + ' px (about 920 px fits on desktop)')
		);
		if (k) {
			var tlow = t.toLowerCase();
			var pos = tlow.indexOf(k);
			list.append(
				check(pos > -1, pos > -1 ? 'Keyword is in the title' : 'Keyword is missing from the title'),
				check(pos > -1 && pos <= 25, pos > -1 && pos <= 25 ? 'Keyword is near the start of the title' : 'Move the keyword closer to the start of the title', pos > 25),
				check(d.toLowerCase().indexOf(k) > -1, d.toLowerCase().indexOf(k) > -1 ? 'Keyword is in the description' : 'Add the keyword to the description (Google bolds matching words)')
			);
		}
		if (t && t === t.toUpperCase() && /[A-Z]/.test(t)) { list.append(check(false, 'Avoid ALL CAPS titles')); }
		if (/(\b\w+\b)(?:.*\b\1\b){2,}/i.test(t)) { list.append(check(false, 'A word repeats 3+ times in the title; it may look spammy', true)); }
		if (d && !/[.!?]$/.test(d)) { list.append(check(false, 'End the description with a full sentence', true)); }
		if (d && !/\b(get|try|see|learn|find|book|call|start|discover|compare|download|check|request)\b/i.test(d)) { list.append(check(false, 'Consider a call to action (e.g. "Get a free quote")', true)); }

		var tags = '<title>' + api.esc(t) + '</title>\n<meta name="description" content="' + api.esc(d) + '">';
		if (url.value) { tags += '\n<link rel="canonical" href="' + api.esc(url.value) + '">'; }
		tags += '\n<meta property="og:title" content="' + api.esc(t) + '">\n<meta property="og:description" content="' + api.esc(d) + '">';
		if (url.value) { tags += '\n<meta property="og:url" content="' + api.esc(url.value) + '">'; }
		tags += '\n<meta name="twitter:card" content="summary_large_image">';

		var passed = list.querySelectorAll('.ok').length, total = list.children.length;
		out.innerHTML = '';
		out.append(
			h('h2', { class: 'semrs-tool__title', text: 'Results' }),
			h('div', { class: 'semrs-tool__stats' },
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: passed + '/' + total }), h('span', { text: 'checks passed' })),
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: tl }), h('span', { text: 'title chars' })),
				h('div', { class: 'semrs-tool__stat' }, h('b', { text: dl }), h('span', { text: 'description chars' }))),
			list,
			h('p', { class: 'semrs-serp__label', text: 'Your tags' }),
			h('pre', { class: 'semrs-tool__output', text: tags }),
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () { return tags; }, 'Copy tags', api.id)));
		api.store(api.id, { title: title.value, desc: desc.value, kw: kw.value, url: url.value });
	}
	root.addEventListener('input', api.debounce(render, 120));
	render();
});
