/* Word Counter: words, characters, sentences, paragraphs, reading/speaking time, keyword density. */
SemrsTools.register('word-counter', function (root, api) {
	var h = api.h;
	var STOP = ('a an and are as at be been but by for from has have he her his i if in into is it its me my no not of on or our she so than that the their them then there these they this to too up us was we were what when which who will with you your can do does did just about more also any all one would could should our out over how get got very').split(' ');
	var stop = {};
	STOP.forEach(function (w) { stop[w] = 1; });
	var ta = h('textarea', { rows: 14, placeholder: 'Paste or type your text here…' });
	ta.value = (api.load(api.id) || {}).text || '';
	var input = api.panel('Your text');
	input.append(api.field('Text', ta, 'Your text stays in your browser. Nothing is uploaded.'), h('div', { class: 'semrs-tool__actions' }, h('button', { type: 'button', class: 'semrs-btn semrs-btn--ghost', text: 'Clear', onclick: function () { ta.value = ''; render(); ta.focus(); } })));
	var out = api.panel('Statistics');
	root.append(input, out);

	function fmt(min) { if (min < 1) { return Math.max(1, Math.round(min * 60)) + ' sec'; } var m = Math.floor(min), sec = Math.round((min - m) * 60); return m + ' min' + (sec ? ' ' + sec + ' sec' : ''); }
	function stat(v, label) { return h('div', { class: 'semrs-tool__stat' }, h('b', { text: v }), h('span', { text: label })); }

	function render() {
		var t = ta.value;
		var words = (t.match(/[\p{L}\p{N}][\p{L}\p{N}'’-]*/gu) || []);
		var wc = words.length;
		var sentences = (t.match(/[^.!?…]+[.!?…]+(\s|$)/g) || []).length || (wc ? 1 : 0);
		var paras = t.split(/\n\s*\n/).filter(function (p) { return p.trim(); }).length;
		var freq = {}, bi = {};
		var lower = words.map(function (w) { return w.toLowerCase(); });
		lower.forEach(function (w, i) {
			if (w.length > 2 && !stop[w] && !/^\d+$/.test(w)) { freq[w] = (freq[w] || 0) + 1; }
			var n = lower[i + 1];
			if (n && !stop[w] && !stop[n] && w.length > 2 && n.length > 2) { bi[w + ' ' + n] = (bi[w + ' ' + n] || 0) + 1; }
		});
		function top(o, n) { return Object.keys(o).filter(function (k) { return o[k] > 1; }).sort(function (a, b) { return o[b] - o[a]; }).slice(0, n); }
		var table = h('table', { class: 'semrs-tool__table' }, h('thead', null, h('tr', null, h('th', { text: 'Keyword' }), h('th', { text: 'Count' }), h('th', { text: 'Density' }))));
		var tb = h('tbody');
		top(bi, 5).concat(top(freq, 10)).forEach(function (k) {
			var c = (bi[k] || freq[k]);
			var d = wc ? (c * k.split(' ').length / wc * 100) : 0;
			tb.append(h('tr', null, h('td', { text: k }), h('td', { text: c }), h('td', { text: d.toFixed(1) + '%' })));
		});
		table.append(tb);
		out.innerHTML = '';
		out.append(
			h('h2', { class: 'semrs-tool__title', text: 'Statistics' }),
			h('div', { class: 'semrs-tool__stats' },
				stat(wc.toLocaleString(), 'words'), stat(t.length.toLocaleString(), 'characters'), stat(t.replace(/\s/g, '').length.toLocaleString(), 'without spaces'),
				stat(sentences, 'sentences'), stat(paras, 'paragraphs'), stat(fmt(wc / 238), 'reading time'),
				stat(fmt(wc / 150), 'speaking time'), stat(sentences ? (wc / sentences).toFixed(1) : 0, 'words / sentence')),
			wc && sentences && wc / sentences > 20 ? h('ul', { class: 'semrs-tool__checks' }, h('li', { class: 'warn', text: 'Average sentence is over 20 words. Shorter sentences are easier to read.' })) : null,
			tb.children.length ? h('p', { class: 'semrs-serp__label', text: 'Top keywords' }) : null,
			tb.children.length ? table : null,
			tb.children.length ? h('p', { class: 'semrs-tool__hint', text: 'There is no ideal keyword density. Write naturally; repeated phrases above ~3% can read as stuffing.' }) : null);
		api.store(api.id, { text: t.length < 20000 ? t : '' });
	}
	ta.addEventListener('input', api.debounce(render, 120));
	render();
});
