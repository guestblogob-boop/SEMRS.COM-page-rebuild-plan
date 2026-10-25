/* llms.txt Generator: builds the Markdown file described at llmstxt.org. */
SemrsTools.register('llms-txt-generator', function (root, api) {
	var h = api.h;
	var s = api.load(api.id) || {
		name: '', summary: '', details: '',
		sections: [{ title: 'Services', links: '' }, { title: 'Guides', links: '' }, { title: 'Optional', links: '' }]
	};
	var input = api.panel('Your site');
	var out = api.panel('llms.txt');
	root.append(input, out);

	function bind(el, obj, key) { el.value = obj[key] || ''; el.addEventListener('input', function () { obj[key] = el.value; render(); }); return el; }

	function build() {
		input.innerHTML = '';
		input.append(h('h2', { class: 'semrs-tool__title', text: 'Your site' }),
			api.field('Site or company name', bind(h('input', { type: 'text', placeholder: 'Acme Plumbing' }), s, 'name')),
			api.field('One-sentence summary', bind(h('textarea', { rows: 2, placeholder: 'Licensed plumbers serving Austin, TX since 2012: repairs, water heaters and drain cleaning.' }), s, 'summary'), 'Shown as a quote. Say who you are, what you do and where.'),
			api.field('More details (optional)', bind(h('textarea', { rows: 3, placeholder: 'Key facts an AI should know: service area, hours, pricing approach, what you do not offer.' }), s, 'details')));
		s.sections.forEach(function (sec, i) {
			var box = h('div', { class: 'semrs-tool__repeat' });
			box.append(
				api.field('Section title', bind(h('input', { type: 'text' }), sec, 'title'), sec.title === 'Optional' ? '"Optional" is a special section: links AI tools can skip when short on space.' : null),
				api.field('Links: one per line as  Title | URL | short description', bind(h('textarea', { rows: 4, placeholder: 'Drain cleaning | https://example.com/drain-cleaning/ | Same-day service, prices from $99' }), sec, 'links')),
				h('button', { type: 'button', class: 'semrs-btn semrs-btn--ghost semrs-btn--small', text: 'Remove section', onclick: function () { s.sections.splice(i, 1); build(); render(); } }));
			input.append(box);
		});
		input.append(h('button', { type: 'button', class: 'semrs-btn semrs-btn--small', text: '+ Add section', onclick: function () { s.sections.push({ title: '', links: '' }); build(); render(); } }));
	}

	function text() {
		var o = ['# ' + (s.name.trim() || 'Your site name')];
		if (s.summary.trim()) { o.push('', '> ' + s.summary.trim().replace(/\n+/g, ' ')); }
		if (s.details.trim()) { o.push('', s.details.trim()); }
		s.sections.forEach(function (sec) {
			var lines = (sec.links || '').split(/\n/).map(function (l) { return l.trim(); }).filter(Boolean);
			if (!sec.title.trim() || !lines.length) { return; }
			o.push('', '## ' + sec.title.trim(), '');
			lines.forEach(function (l) {
				var p = l.split('|').map(function (x) { return x.trim(); });
				if (p.length >= 2 && p[1]) { o.push('- [' + p[0] + '](' + p[1] + ')' + (p[2] ? ': ' + p.slice(2).join(' | ') : '')); }
				else if (/^https?:\/\//.test(p[0])) { o.push('- [' + p[0] + '](' + p[0] + ')'); }
			});
		});
		return o.join('\n') + '\n';
	}

	function render() {
		var warn = [];
		if (!s.name.trim()) { warn.push('Add your site name (the only required line).'); }
		if (!s.summary.trim()) { warn.push('Add a one-sentence summary; it is what AI tools read first.'); }
		s.sections.forEach(function (sec) {
			(sec.links || '').split(/\n/).forEach(function (l) { if (l.trim() && !/\|\s*https?:\/\//.test(l) && !/^\s*https?:\/\//.test(l)) { warn.push('Line "' + l.trim().slice(0, 40) + '" needs a full URL (https://…).'); } });
		});
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'llms.txt' }), h('pre', { class: 'semrs-tool__output', text: text() }));
		if (warn.length) { var ul = h('ul', { class: 'semrs-tool__checks' }); warn.slice(0, 6).forEach(function (w) { ul.append(h('li', { class: 'warn', text: w })); }); out.append(ul); }
		out.append(
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(text, 'Copy', api.id), api.downloadBtn(text, 'llms.txt', 'text/markdown', 'Download llms.txt', api.id)),
			h('p', { class: 'semrs-tool__hint', text: 'Upload to your site root so it opens at https://yourdomain.com/llms.txt. It is a proposed standard: helpful, not a ranking guarantee.' }));
		api.store(api.id, s);
	}
	build();
	render();
});
