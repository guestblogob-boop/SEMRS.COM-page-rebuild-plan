/* ROAS & Break-Even Calculator: ROAS, ROI, cost per sale and break-even ROAS from margin. */
SemrsTools.register('roas-calculator', function (root, api) {
	var h = api.h;
	var s = api.load(api.id) || {};
	var F = [
		['spend', 'Ad spend ($)', 1500, 'Total cost for the period, including management fees if you want true profit.'],
		['revenue', 'Revenue from ads ($)', 5200, 'Sales tracked to the ads (from Google Ads, Meta or your shop).'],
		['orders', 'Number of sales or leads', 40, ''],
		['margin', 'Gross margin (%)', 45, 'What is left of each $1 of revenue after product and delivery costs.'],
		['clicks', 'Clicks (optional)', 2100, 'Adds cost per click and conversion rate.']
	];
	var el = {};
	var input = api.panel('Your campaign');
	F.forEach(function (f) {
		el[f[0]] = h('input', { type: 'number', min: 0, step: 'any', inputmode: 'decimal', value: s[f[0]] !== undefined ? s[f[0]] : f[2] });
		input.append(api.field(f[1], el[f[0]], f[3]));
	});
	var out = api.panel('Results');
	root.append(input, out);

	function n(k) { var v = parseFloat(el[k].value); return isNaN(v) || v < 0 ? 0 : v; }
	function money(v) { return '$' + (Math.abs(v) < 100 ? v.toFixed(2) : Math.round(v).toLocaleString('en-US')); }

	function render() {
		var spend = n('spend'), rev = n('revenue'), orders = n('orders'), margin = n('margin') / 100, clicks = n('clicks');
		var roas = spend ? rev / spend : 0;
		var be = margin ? 1 / margin : 0;
		var profit = rev * margin - spend;
		var c = [];
		if (spend && margin) {
			c.push([roas >= be ? 'ok' : 'bad', roas >= be
				? 'ROAS ' + roas.toFixed(2) + 'x is above your break-even of ' + be.toFixed(2) + 'x: the ads make ' + money(profit) + ' gross profit.'
				: 'ROAS ' + roas.toFixed(2) + 'x is below your break-even of ' + be.toFixed(2) + 'x: the ads lose ' + money(-profit) + ' on these numbers.']);
			c.push(['ok', 'To break even you need ' + money(spend * be) + ' revenue from this spend.']);
			if (orders) { c.push(['ok', 'Highest cost per sale you can pay and still break even: ' + money(rev / orders * margin) + '.']); }
		}
		if (margin <= 0) { c.push(['warn', 'Enter your gross margin to see break-even ROAS.']); }
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Results' }),
			h('div', { class: 'semrs-tool__stats' },
				api.stat(roas.toFixed(2) + 'x', 'ROAS'),
				api.stat(be ? be.toFixed(2) + 'x' : '—', 'break-even ROAS'),
				api.stat(spend ? Math.round(profit / spend * 100) + '%' : '—', 'ROI (on gross profit)'),
				api.stat(money(profit), 'gross profit after ads'),
				api.stat(orders ? money(spend / orders) : '—', 'cost per sale'),
				api.stat(orders ? money(rev / orders) : '—', 'average order'),
				clicks ? api.stat(money(spend / clicks), 'cost per click') : null,
				clicks ? api.stat((orders / clicks * 100).toFixed(2) + '%', 'conversion rate') : null),
			api.checks(c),
			h('p', { class: 'semrs-tool__hint', text: 'ROAS = revenue ÷ ad spend. Break-even ROAS = 1 ÷ gross margin. Estimates only: repeat purchases (lifetime value) can make a lower ROAS worth it.' }),
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () {
				return 'Ad spend ' + money(spend) + ', revenue ' + money(rev) + ', ROAS ' + roas.toFixed(2) + 'x, break-even ' + (be ? be.toFixed(2) + 'x' : '—') + ', gross profit after ads ' + money(profit);
			}, 'Copy summary', api.id)));
		var save = {};
		Object.keys(el).forEach(function (k) { save[k] = el[k].value; });
		api.store(api.id, save);
	}
	root.addEventListener('input', api.debounce(render, 120));
	render();
});
