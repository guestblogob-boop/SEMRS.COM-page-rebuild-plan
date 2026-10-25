/* SEO ROI Calculator: estimates value of extra organic traffic with a linear ramp-up. Estimates only. */
SemrsTools.register('seo-roi-calculator', function (root, api) {
	var h = api.h;
	var s = api.load(api.id) || {};
	var F = [
		['visits', 'Current monthly organic visits', 2000, 'From Google Search Console or Analytics.'],
		['growth', 'Expected increase in organic visits (%)', 50, 'Be conservative. Results vary by market and competition.'],
		['months', 'Months to reach that increase', 9, 'SEO usually takes 4–12 months to show full results.'],
		['conv', 'Visitor → lead conversion rate (%)', 2.5, 'Typical service sites see 1–5%.'],
		['close', 'Lead → customer close rate (%)', 25, ''],
		['value', 'Average customer value ($)', 1200, 'Use profit per customer for a stricter estimate.'],
		['cost', 'Monthly SEO investment ($)', 1000, '']
	];
	var inputs = {};
	var input = api.panel('Your numbers');
	F.forEach(function (f) {
		var el = h('input', { type: 'number', min: 0, step: 'any', inputmode: 'decimal', value: s[f[0]] !== undefined ? s[f[0]] : f[2] });
		inputs[f[0]] = el;
		input.append(api.field(f[1], el, f[3]));
	});
	var out = api.panel('Estimated return');
	root.append(input, out);

	function n(k) { var v = parseFloat(inputs[k].value); return isNaN(v) || v < 0 ? 0 : v; }
	function money(v) { return '$' + Math.round(v).toLocaleString('en-US'); }
	function stat(v, l) { return h('div', { class: 'semrs-tool__stat' }, h('b', { text: v }), h('span', { text: l })); }

	function render() {
		var visits = n('visits'), growth = n('growth') / 100, months = Math.max(1, Math.round(n('months'))), conv = n('conv') / 100, close = n('close') / 100, value = n('value'), cost = n('cost');
		var horizon = 12, cumRev = 0, cumCost = 0, breakEven = 0;
		var rows = [];
		for (var m = 1; m <= horizon; m++) {
			var extra = visits * growth * Math.min(1, m / months);
			var rev = extra * conv * close * value;
			cumRev += rev;
			cumCost += cost;
			if (!breakEven && cumRev >= cumCost && cumCost > 0) { breakEven = m; }
			rows.push([m, Math.round(extra), rev, cumRev - cumCost]);
		}
		var fullExtra = visits * growth, leads = fullExtra * conv, customers = leads * close, monthly = customers * value;
		var roi = cumCost ? (cumRev - cumCost) / cumCost * 100 : 0;
		var table = h('table', { class: 'semrs-tool__table' }, h('thead', null, h('tr', null, h('th', { text: 'Month' }), h('th', { text: 'Extra visits' }), h('th', { text: 'Extra revenue' }), h('th', { text: 'Running total vs. cost' }))));
		var tb = h('tbody');
		rows.forEach(function (r) { tb.append(h('tr', null, h('td', { text: r[0] }), h('td', { text: r[1].toLocaleString('en-US') }), h('td', { text: money(r[2]) }), h('td', { style: 'color:' + (r[3] >= 0 ? 'var(--st-ok)' : 'var(--st-bad)'), text: (r[3] < 0 ? '−' : '') + money(Math.abs(r[3])) }))); });
		table.append(tb);
		out.innerHTML = '';
		out.append(
			h('h2', { class: 'semrs-tool__title', text: 'Estimated return (first 12 months)' }),
			h('div', { class: 'semrs-tool__stats' },
				stat(Math.round(fullExtra).toLocaleString('en-US'), 'extra visits / month at full growth'),
				stat(leads.toFixed(1), 'extra leads / month'),
				stat(customers.toFixed(1), 'extra customers / month'),
				stat(money(monthly), 'extra revenue / month'),
				stat(money(cumRev - cumCost), '12-month net'),
				stat(Math.round(roi) + '%', '12-month ROI'),
				stat(breakEven ? 'Month ' + breakEven : 'Not in 12 mo.', 'break-even')),
			h('div', { style: 'overflow-x:auto' }, table),
			h('p', { class: 'semrs-tool__hint', text: 'This is an estimate from your inputs, not a promise of results. Growth is assumed to ramp up evenly; real SEO results depend on your market, competition and website.' }),
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () {
				return 'SEO ROI estimate (12 months)\nExtra visits/month: ' + Math.round(fullExtra) + '\nExtra leads/month: ' + leads.toFixed(1) + '\nExtra customers/month: ' + customers.toFixed(1) + '\nExtra revenue/month: ' + money(monthly) + '\n12-month net: ' + money(cumRev - cumCost) + '\n12-month ROI: ' + Math.round(roi) + '%\nBreak-even: ' + (breakEven ? 'month ' + breakEven : 'not within 12 months');
			}, 'Copy summary', api.id)));
		var save = {};
		Object.keys(inputs).forEach(function (k) { save[k] = inputs[k].value; });
		api.store(api.id, save);
	}
	root.addEventListener('input', api.debounce(render, 120));
	render();
});
