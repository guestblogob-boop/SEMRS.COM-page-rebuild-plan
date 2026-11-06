/* Robots.txt Tester: fetches the live robots.txt (server), then matches paths in the browser
   using Google's rules: the most specific user-agent group wins, the longest matching rule wins,
   Allow wins a tie, * matches anything and $ ends the URL. You can also paste your own draft. */
SemrsTools.register('robots-txt-tester', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var BOTS = ['Googlebot', 'Googlebot-Image', 'Bingbot', 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'PerplexityBot', 'Google-Extended', 'Applebot-Extended', 'CCBot'];
	var s = api.load(api.id) || {};
	var url = h('input', { type: 'url', value: s.url || '', placeholder: 'https://example.com/blog/my-post/', inputmode: 'url' });
	var bot = h('select', null, BOTS.map(function (b) { return h('option', { value: b, text: b }); }));
	bot.value = s.bot || 'Googlebot';
	var txt = h('textarea', { rows: 10, placeholder: 'Fetched robots.txt appears here. You can also paste or edit rules to test a draft.' });
	var fetchBtn = h('button', { type: 'submit', class: 'wp-element-button semrs-btn', text: 'Fetch robots.txt & test' });
	var form = h('form', { class: 'semrs-tool__panel', novalidate: true },
		h('h2', { class: 'semrs-tool__title', text: 'URL and crawler' }),
		api.field('Page URL to test', url),
		api.field('Crawler (user-agent)', bot),
		fetchBtn,
		h('div', { style: 'height:1rem' }),
		api.field('robots.txt', txt, 'Edit the text to try changes before you upload them. Results update as you type.'));
	var out = h('div', { class: 'semrs-tool__panel', 'aria-live': 'polite' }, h('h2', { class: 'semrs-tool__title', text: 'Result' }), h('p', { class: 'semrs-tool__hint', text: 'Enter a URL and click Fetch.' }));
	root.append(form, out);
	var meta = { status: 0, robots: '' };

	/** Parse into groups [{agents:[], rules:[[allow, path]]}] and sitemaps. */
	function parse(text) {
		var groups = [], cur = null, lastWasAgent = false, maps = [], bad = [];
		text.split(/\r?\n/).forEach(function (line, n) {
			var l = line.replace(/#.*$/, '').trim();
			if (!l) { return; }
			var m = l.match(/^([A-Za-z-]+)\s*:\s*(.*)$/);
			if (!m) { bad.push(n + 1); return; }
			var k = m[1].toLowerCase(), v = m[2].trim();
			if (k === 'user-agent') {
				if (!lastWasAgent || !cur) { cur = { agents: [], rules: [] }; groups.push(cur); }
				cur.agents.push(v.toLowerCase());
				lastWasAgent = true;
				return;
			}
			lastWasAgent = false;
			if ((k === 'allow' || k === 'disallow') && cur) { if (v || k === 'allow') { cur.rules.push([k === 'allow', v]); } }
			else if (k === 'sitemap') { maps.push(v); }
			else if (['crawl-delay', 'host', 'clean-param'].indexOf(k) < 0) { bad.push(n + 1); }
		});
		return { groups: groups, maps: maps, bad: bad };
	}
	function toRe(p) {
		var end = p.slice(-1) === '$';
		var body = (end ? p.slice(0, -1) : p).split('*').map(function (x) { return x.replace(/[.+?^${}()|[\]\\]/g, '\\$&'); }).join('.*');
		return new RegExp('^' + body + (end ? '$' : ''));
	}
	function test(parsed, agent, path) {
		agent = agent.toLowerCase();
		var best = null, bestLen = -1;
		parsed.groups.forEach(function (g) {
			g.agents.forEach(function (a) {
				var l = a === '*' ? 0 : (agent.indexOf(a) === 0 ? a.length : -1);
				if (l > bestLen) { bestLen = l; best = [g]; } else if (l === bestLen && l > -1 && best.indexOf(g) < 0) { best.push(g); }
			});
		});
		if (!best) { return { allowed: true, rule: null, group: 'none' }; }
		var win = null;
		best.forEach(function (g) {
			g.rules.forEach(function (r) {
				if (!r[1] || !toRe(r[1]).test(path)) { return; }
				if (!win || r[1].length > win[1].length || (r[1].length === win[1].length && r[0] && !win[0])) { win = r; }
			});
		});
		return { allowed: !win || win[0], rule: win, group: bestLen === 0 ? '*' : best[0].agents.filter(function (a) { return agent.indexOf(a) === 0; })[0] };
	}
	function path() {
		try { var u = new URL(url.value.trim()); return u.pathname + u.search; } catch (e) { return url.value.trim() || '/'; }
	}

	function render() {
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Result' }));
		if (meta.robots && meta.status !== 200) {
			out.append(api.checks([[meta.status >= 500 || !meta.status ? 'bad' : 'warn', meta.status === 404 || meta.status === 410 ? 'No robots.txt (' + meta.status + '): crawlers may open every page. That is fine if you want everything crawled.' : 'robots.txt answered ' + (meta.status || 'with an error') + '. Google treats server errors as “do not crawl anything” for a while.']]));
		}
		var p = parse(txt.value), pth = path();
		var r = test(p, bot.value, pth);
		out.append(h('div', { class: 'semrs-tool__verdict ' + (r.allowed ? 'is-ok' : 'is-bad') }, h('b', { text: r.allowed ? 'Allowed' : 'Blocked' }), ' · ' + bot.value + ' · ' + pth));
		out.append(h('p', { class: 'semrs-tool__hint', text: r.rule ? 'Matched rule: ' + (r.rule[0] ? 'Allow: ' : 'Disallow: ') + r.rule[1] + ' (group User-agent: ' + r.group + ')' : (r.group === 'none' ? 'No group applies to this crawler, so everything is allowed.' : 'No rule matches this path in the User-agent: ' + r.group + ' group, so it is allowed.') }));
		var t = h('table', { class: 'semrs-tool__table' }, h('thead', null, h('tr', null, h('th', { text: 'Crawler' }), h('th', { text: 'This URL' }))));
		var b = h('tbody');
		BOTS.forEach(function (x) { var y = test(p, x, pth); b.append(h('tr', null, h('td', { text: x }), h('td', null, h('b', { style: 'color:var(' + (y.allowed ? '--st-ok' : '--st-bad') + ')', text: y.allowed ? 'Allowed' : 'Blocked' })))); });
		t.append(b);
		out.append(h('p', { class: 'semrs-serp__label', text: 'All common crawlers' }), h('div', { style: 'overflow-x:auto' }, t));
		var notes = [];
		if (!p.maps.length && txt.value.trim()) { notes.push(['warn', 'No Sitemap line. Add Sitemap: https://yourdomain.com/sitemap.xml']); }
		if (p.bad.length) { notes.push(['warn', 'Lines Google ignores: ' + p.bad.slice(0, 8).join(', ') + '.']); }
		if (/disallow:\s*\/\s*$/im.test(txt.value) && test(p, 'Googlebot', '/').allowed === false) { notes.push(['bad', 'Googlebot is blocked from the whole site.']); }
		if (/\.(css|js)/i.test(txt.value) && /disallow/i.test(txt.value)) { notes.push(['warn', 'Rules mention CSS or JS files. Google needs them to render your pages.']); }
		if (notes.length) { out.append(api.checks(notes)); }
		out.append(h('p', { class: 'semrs-tool__hint', text: 'robots.txt controls crawling, not indexing. A blocked page can still appear in Google if other sites link to it; use noindex to keep it out.' }));
		api.store(api.id, { url: url.value, bot: bot.value });
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		if (!url.value.trim()) { url.focus(); return; }
		fetchBtn.disabled = true;
		fetchBtn.textContent = 'Fetching…';
		api.track('tool_run', { tool_id: api.id });
		api.get('robots', { url: url.value.trim() }).then(function (res) {
			if (!res.ok) { out.innerHTML = ''; out.append(h('p', { class: 'semrs-tool__count is-over', text: res.j && res.j.message ? res.j.message : 'Something went wrong.' })); return; }
			meta = res.j;
			txt.value = res.j.text || '';
			render();
		}).catch(function () { out.innerHTML = ''; out.append(h('p', { class: 'semrs-tool__count is-over', text: 'Could not reach the tester. Please try again.' })); })
			.then(function () { fetchBtn.disabled = false; fetchBtn.textContent = 'Fetch robots.txt & test'; });
	});
	txt.addEventListener('input', api.debounce(render, 150));
	bot.addEventListener('change', render);
	url.addEventListener('input', api.debounce(function () { if (txt.value.trim()) { render(); } }, 200));
});
