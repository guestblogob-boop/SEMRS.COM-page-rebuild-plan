/* Robots.txt Generator: presets, disallow rules, sitemaps, AI crawler choices, warnings. */
SemrsTools.register('robots-txt-generator', function (root, api) {
	var h = api.h;
	var AI = [ // [user-agent, purpose, kind: search = AI search/browsing, train = model training]
		['GPTBot', 'OpenAI training', 'train'], ['OAI-SearchBot', 'ChatGPT search', 'search'], ['ChatGPT-User', 'ChatGPT browsing', 'search'],
		['ClaudeBot', 'Anthropic training', 'train'], ['Claude-SearchBot', 'Claude search', 'search'], ['Claude-User', 'Claude browsing', 'search'],
		['PerplexityBot', 'Perplexity search', 'search'], ['Perplexity-User', 'Perplexity browsing', 'search'], ['Google-Extended', 'Gemini training (not Google Search)', 'train'],
		['Applebot-Extended', 'Apple AI training', 'train'], ['CCBot', 'Common Crawl', 'train'], ['Bytespider', 'ByteDance', 'train'], ['meta-externalagent', 'Meta AI', 'train']
	];
	var policy = h('select', null, h('option', { value: 'allow', text: 'Allow all crawlers (recommended)' }), h('option', { value: 'block', text: 'Block all crawlers (staging / private sites only)' }));
	var wp = h('input', { type: 'checkbox', checked: true });
	var search = h('input', { type: 'checkbox', checked: true });
	var disallow = h('textarea', { rows: 4, placeholder: '/cart/\n/checkout/\n/my-account/' });
	var sitemaps = h('textarea', { rows: 2, placeholder: 'https://example.com/wp-sitemap.xml' });
	var aiMode = h('select', null,
		h('option', { value: 'allow', text: 'Allow all AI crawlers' }),
		h('option', { value: 'search', text: 'Allow AI search/browsing, block AI training' }),
		h('option', { value: 'block', text: 'Block all AI crawlers' }),
		h('option', { value: 'custom', text: 'Choose per crawler' }));
	var aiBoxes = h('div', { class: 'semrs-tool__row', hidden: true });
	var aiChecks = AI.map(function (a) {
		var c = h('input', { type: 'checkbox', checked: true, 'data-bot': a[0] });
		aiBoxes.append(h('label', { class: 'semrs-tool__hint' }, c, ' Allow ' + a[0], h('br'), h('small', { text: a[1] })));
		return c;
	});

	var input = api.panel('Settings');
	input.append(
		api.field('Default rule', policy),
		h('label', { class: 'semrs-tool__hint' }, wp, ' WordPress site (block /wp-admin/, allow admin-ajax.php)'), h('br'),
		h('label', { class: 'semrs-tool__hint' }, search, ' Block internal search result pages (/?s= and /search/)'),
		h('div', { style: 'height:1rem' }),
		api.field('Extra paths to block (one per line)', disallow, 'Start each path with /. Never block CSS or JS files Google needs to render pages.'),
		api.field('Sitemap URLs (one per line)', sitemaps),
		api.field('AI crawlers', aiMode, 'Blocking AI search bots can stop your site being cited in AI answers.'),
		aiBoxes);
	var out = api.panel('Your robots.txt');
	root.append(input, out);

	function build() {
		var lines = ['User-agent: *'];
		var warn = [];
		if (policy.value === 'block') {
			lines.push('Disallow: /');
			warn.push('This blocks every crawler, including Google. Use only on staging or private sites.');
		} else {
			if (wp.checked) { lines.push('Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php'); }
			if (search.checked) { lines.push('Disallow: /?s=', 'Disallow: /search/'); }
			disallow.value.split(/\n/).map(function (l) { return l.trim(); }).filter(Boolean).forEach(function (p) {
				if (p.charAt(0) !== '/' && p.charAt(0) !== '*') { p = '/' + p; warn.push('Added a leading slash to "' + p.slice(1) + '".'); }
				if (/\.(css|js)\b|wp-includes|wp-content\/(themes|plugins)/i.test(p)) { warn.push('"' + p + '" may block files Google needs to render your pages.'); }
				if (p === '/') { warn.push('"Disallow: /" blocks your whole site.'); }
				lines.push('Disallow: ' + p);
			});
			if (lines.length === 1) { lines.push('Disallow:'); }
		}
		var blocked = [];
		aiBoxes.hidden = aiMode.value !== 'custom';
		AI.forEach(function (a, i) {
			var allow = aiMode.value === 'allow' || (aiMode.value === 'search' && a[2] === 'search') || (aiMode.value === 'custom' && aiChecks[i].checked);
			if (!allow && policy.value !== 'block') { blocked.push(a[0]); }
		});
		if (blocked.length) {
			lines.push('');
			blocked.forEach(function (b) { lines.push('User-agent: ' + b); });
			lines.push('Disallow: /');
		}
		var maps = sitemaps.value.split(/\n/).map(function (l) { return l.trim(); }).filter(Boolean);
		maps.forEach(function (m) { if (!/^https?:\/\//i.test(m)) { warn.push('Sitemap URLs must be full addresses starting with https://'); } });
		if (maps.length) { lines.push(''); maps.forEach(function (m) { lines.push('Sitemap: ' + m); }); }
		else { warn.push('Add your sitemap URL so search engines find all your pages.'); }
		return { text: lines.join('\n') + '\n', warn: warn };
	}
	function render() {
		var r = build();
		out.innerHTML = '';
		out.append(h('h3', { class: 'semrs-tool__title', text: 'Your robots.txt' }), h('pre', { class: 'semrs-tool__output', text: r.text }));
		if (r.warn.length) {
			var ul = h('ul', { class: 'semrs-tool__checks' });
			r.warn.forEach(function (w) { ul.append(h('li', { class: 'warn', text: w })); });
			out.append(ul);
		}
		out.append(
			h('p', { class: 'semrs-tool__hint', text: 'Upload it to your site root so it opens at https://yourdomain.com/robots.txt. robots.txt controls crawling, not indexing: use noindex to keep a page out of Google.' }),
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () { return build().text; }, 'Copy', api.id), api.downloadBtn(function () { return build().text; }, 'robots.txt', 'text/plain', 'Download robots.txt', api.id)));
	}
	root.addEventListener('input', api.debounce(render, 100));
	root.addEventListener('change', render);
	render();
});
