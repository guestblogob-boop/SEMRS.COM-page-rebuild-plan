/* Keyword Ideas Generator: 50+ keyword ideas from a seed, grouped by search
 * intent (questions, informational, commercial, transactional, local).
 * Runs entirely in the browser; reads ?seed=&city= so other pages can link
 * straight to results. No search volumes are invented. */
SemrsTools.register('keyword-ideas-generator', function (root, api) {
	var h = api.h;
	var GROUPS = [
		{ key: 'questions', title: 'Questions people ask', intent: 'Informational', note: 'Great for FAQ sections, blog posts and AI answers.', list: [
			'what is {k}', 'how does {k} work', 'how much does {k} cost', 'how to choose {k}', 'is {k} worth it', 'why is {k} important',
			'when do you need {k}', 'how long does {k} take', 'what to ask before hiring {k}', 'can I do {k} myself'] },
		{ key: 'info', title: 'Learning & guides', intent: 'Informational', note: 'Topics for guides and helpful articles.', list: [
			'{k} guide', '{k} tips', '{k} for beginners', '{k} checklist', '{k} examples', '{k} mistakes to avoid', '{k} ideas', '{k} trends {y}', '{k} explained', 'benefits of {k}'] },
		{ key: 'commercial', title: 'Comparing options', intent: 'Commercial investigation', note: 'People researching before they buy: comparison and review pages.', list: [
			'best {k}', 'top {k} companies', '{k} reviews', '{k} vs diy', 'affordable {k}', 'professional {k}', '{k} packages', '{k} pricing', '{k} alternatives', 'cheap {k}'] },
		{ key: 'transactional', title: 'Ready to buy or hire', intent: 'Transactional', note: 'High-intent searches: service and pricing pages.', list: [
			'{k} services', 'hire {k}', '{k} company', '{k} agency', '{k} quote', 'book {k}', '{k} cost', '{k} price list', '{k} consultation', 'get {k} today'] },
		{ key: 'local', title: 'Local searches', intent: 'Transactional (local)', note: 'For local landing pages and your Google Business Profile.', local: true, list: [
			'{k} near me', '{k} in {c}', 'best {k} in {c}', '{k} {c}', '{k} company in {c}', 'affordable {k} {c}', '{k} services {c}', 'top rated {k} near me', 'local {k}', '{k} open now'] }
	];

	var params = new URLSearchParams(location.search);
	var saved = api.load(api.id) || {};
	var seed = h('input', { type: 'text', placeholder: 'e.g. dentist, web design, seo', maxlength: 60, autocomplete: 'off' });
	var city = h('input', { type: 'text', placeholder: 'e.g. Lahore', maxlength: 60, autocomplete: 'off' });
	seed.value = params.get('seed') || saved.seed || '';
	city.value = params.get('city') || saved.city || '';
	var go = h('button', { type: 'submit', class: 'wp-element-button semrs-btn', text: 'Get keyword ideas' });
	var form = h('form', { class: 'semrs-kw__form' },
		api.field('Seed keyword', seed, 'What you sell or do, in 1–3 words.'),
		api.field('City or area (optional)', city, 'Adds local "in [city]" ideas.'),
		h('div', { class: 'semrs-tool__actions' }, go));
	var input = api.panel('Your topic');
	input.append(form, h('p', { class: 'semrs-tool__hint', text: 'Free, no sign-up. Runs in your browser: nothing you type is sent to SEMRS.' }));
	var out = api.panel('Keyword ideas');
	root.append(input, out);

	function clean(s) { return s.toLowerCase().replace(/[^\p{L}\p{N}\s&'-]/gu, ' ').replace(/\s+/g, ' ').trim(); }
	function build(k, c) {
		var y = String(new Date().getFullYear());
		var seen = {}, groups = [];
		GROUPS.forEach(function (g) {
			var items = [];
			g.list.forEach(function (t) {
				if (!c && t.indexOf('{c}') > -1) { return; }
				var kw = t.replace('{k}', k).replace('{c}', c).replace('{y}', y).replace(/\s+/g, ' ').trim();
				if (!seen[kw]) { seen[kw] = 1; items.push(kw); }
			});
			groups.push({ g: g, items: items });
		});
		return groups;
	}
	var last = [];
	function render() {
		var k = clean(seed.value), c = clean(city.value);
		out.innerHTML = '';
		out.append(h('h3', { class: 'semrs-tool__title', text: 'Keyword ideas' }));
		if (!k) {
			out.append(h('p', { class: 'semrs-tool__hint', text: 'Enter a seed keyword to see ideas grouped by what the searcher wants.' }));
			last = [];
			return;
		}
		var groups = build(k, c);
		last = [];
		groups.forEach(function (x) { x.items.forEach(function (kw) { last.push([kw, x.g.intent, x.g.title]); }); });
		out.append(h('p', { class: 'semrs-kw__total', text: last.length + ' ideas for "' + k + '"' + (c ? ' in ' + city.value.trim() : '') }));
		groups.forEach(function (x) {
			if (!x.items.length) { return; }
			var ul = h('ul', { class: 'semrs-kw__list' });
			x.items.forEach(function (kw) { ul.append(h('li', { text: kw })); });
			out.append(h('section', { class: 'semrs-kw__group' },
				h('h4', null, x.g.title + ' ', h('span', { class: 'semrs-kw__intent', text: x.g.intent })),
				h('p', { class: 'semrs-tool__hint', text: x.g.note }), ul));
		});
		out.append(h('div', { class: 'semrs-tool__actions' },
			api.copyBtn(function () { return last.map(function (r) { return r[0]; }).join('\n'); }, 'Copy all', api.id),
			api.downloadBtn(function () { return 'keyword,intent,group\n' + last.map(function (r) { return r.map(function (v) { return '"' + v.replace(/"/g, '""') + '"'; }).join(','); }).join('\n'); }, function () { return 'keyword-ideas-' + k.replace(/\s+/g, '-') + '.csv'; }, 'text/csv', 'Download CSV', api.id)),
			h('p', { class: 'semrs-tool__hint', text: 'Next step: check which ideas have search demand in Google Search Console, Google Keyword Planner or Google Trends before you write. These are ideas, not volumes.' }));
		api.store(api.id, { seed: seed.value, city: city.value });
	}
	form.addEventListener('submit', function (e) { e.preventDefault(); render(); api.track('tool_run', { tool_id: api.id }); });
	render();
});
