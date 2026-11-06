/* Color Contrast Checker: WCAG 2.2 contrast ratio for text, large text and UI parts, with the nearest passing color. */
SemrsTools.register('color-contrast-checker', function (root, api) {
	var h = api.h;
	var s = api.load(api.id) || {};
	function pair(key, def, label, hint) {
		var txt = h('input', { type: 'text', value: s[key] || def, maxlength: 30, spellcheck: 'false', autocomplete: 'off' });
		var pick = h('input', { type: 'color', value: def, 'aria-label': label + ' picker' });
		pick.addEventListener('input', function () { txt.value = pick.value; render(); });
		var f = api.field(label, txt, hint);
		var ref = txt.nextSibling;
		f.insertBefore(h('div', { class: 'semrs-color' }, pick, txt), ref);
		return { txt: txt, pick: pick, row: f };
	}
	var fg = pair('fg', '#5b6575', 'Text color', 'Hex (#1f2937), rgb(31, 41, 55) or a color name.'), bg = pair('bg', '#ffffff', 'Background color');
	var input = api.panel('Colors');
	input.append(fg.row, bg.row,
		h('button', { type: 'button', class: 'semrs-btn semrs-btn--ghost semrs-btn--small', text: '⇄ Swap', onclick: function () { var t = fg.txt.value; fg.txt.value = bg.txt.value; bg.txt.value = t; render(); } }));
	var out = api.panel('Result');
	root.append(input, out);

	var probe = document.createElement('canvas').getContext('2d');
	function parse(c) {
		c = (c || '').trim();
		if (!c) { return null; }
		if (/^[0-9a-f]{3}([0-9a-f]{3})?$/i.test(c)) { c = '#' + c; }
		probe.fillStyle = '#010203';
		probe.fillStyle = c;
		var v = probe.fillStyle;
		if (v === '#010203' && c.toLowerCase().replace(/\s/g, '') !== '#010203' && c.toLowerCase() !== 'rgb(1,2,3)') { return null; }
		if (v.charAt(0) === '#') { return [parseInt(v.substr(1, 2), 16), parseInt(v.substr(3, 2), 16), parseInt(v.substr(5, 2), 16)]; }
		var m = v.match(/[\d.]+/g);
		return m ? [+m[0], +m[1], +m[2]] : null;
	}
	function lum(rgb) { return rgb.map(function (v) { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }).reduce(function (a, v, i) { return a + v * [0.2126, 0.7152, 0.0722][i]; }, 0); }
	function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
	function hex(rgb) { return '#' + rgb.map(function (v) { return ('0' + Math.round(v).toString(16)).slice(-2); }).join(''); }
	/** Darken or lighten the text color step by step until it reaches the target ratio. */
	function fix(f, b, target) {
		// Mix the text color toward black (light background) or white (dark background); binary search for the smallest change that passes.
		var to = lum(b) > 0.18 ? [0, 0, 0] : [255, 255, 255];
		var mix = function (t) { return f.map(function (v, i) { return Math.round(v + (to[i] - v) * t); }); };
		if (ratio(mix(1), b) < target) { return null; }
		var lo = 0, hi = 1;
		for (var i = 0; i < 24; i++) { var mid = (lo + hi) / 2; if (ratio(mix(mid), b) >= target) { hi = mid; } else { lo = mid; } }
		return hex(mix(hi));
	}

	function render() {
		var f = parse(fg.txt.value), b = parse(bg.txt.value);
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Result' }));
		if (!f || !b) { out.append(api.checks([['bad', 'Enter two valid colors, e.g. #333333 and #ffffff.']])); return; }
		fg.pick.value = hex(f);
		bg.pick.value = hex(b);
		var r = ratio(f, b), rr = Math.floor(r * 100) / 100;
		var rows = [
			['Normal text', 'AA', 4.5], ['Normal text', 'AAA', 7], ['Large text (24 px, or 19 px bold)', 'AA', 3], ['Large text', 'AAA', 4.5], ['Buttons, icons, form borders', 'AA', 3]
		];
		var t = h('table', { class: 'semrs-tool__table' }, h('thead', null, h('tr', null, h('th', { text: 'Use' }), h('th', { text: 'Level' }), h('th', { text: 'Needs' }), h('th', { text: 'Result' }))));
		var tb = h('tbody');
		rows.forEach(function (x) { var ok = r >= x[2]; tb.append(h('tr', null, h('td', { text: x[0] }), h('td', { text: x[1] }), h('td', { text: x[2] + ':1' }), h('td', null, h('b', { style: 'color:var(' + (ok ? '--st-ok' : '--st-bad') + ')', text: ok ? 'Pass' : 'Fail' })))); });
		t.append(tb);
		var sample = h('div', { class: 'semrs-cc__sample', style: 'color:' + hex(f) + ';background:' + hex(b) },
			h('p', { style: 'font-size:1.5rem;font-weight:700;margin:0 0 .25rem', text: 'Large heading text' }),
			h('p', { style: 'margin:0', text: 'Normal body text at 16 px. The quick brown fox jumps over the lazy dog.' }),
			h('span', { class: 'semrs-cc__btn', style: 'border-color:' + hex(f), text: 'Button' }));
		var c = [];
		if (r < 4.5) {
			var good = fix(f, b, 4.5);
			c.push(['warn', good ? 'Nearest text color that passes AA for normal text: ' + good + ' (' + (Math.floor(ratio(parse(good), b) * 100) / 100) + ':1).' : 'No shade of this text color passes on this background. Change the background color.']);
		} else if (r < 7) {
			c.push(['ok', 'Passes AA.' + (fix(f, b, 7) ? ' For AAA, try ' + fix(f, b, 7) + '.' : '')]);
		} else { c.push(['ok', 'Passes every level.']); }
		out.append(h('div', { class: 'semrs-tool__stats' }, api.stat(rr + ':1', 'contrast ratio'), api.stat(r >= 7 ? 'AAA' : (r >= 4.5 ? 'AA' : (r >= 3 ? 'AA large only' : 'Fail')), 'normal-text level')),
			sample, h('div', { style: 'overflow-x:auto' }, t), api.checks(c),
			h('p', { class: 'semrs-tool__hint', text: 'Uses the WCAG 2.2 formula. Low contrast also costs points in Lighthouse and PageSpeed accessibility scores.' }));
		api.store(api.id, { fg: fg.txt.value, bg: bg.txt.value });
	}
	[fg.txt, bg.txt].forEach(function (x) { x.addEventListener('input', api.debounce(render, 150)); });
	render();
});
