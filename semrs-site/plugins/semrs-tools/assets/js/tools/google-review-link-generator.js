/* Google Review Link Generator: Place ID → direct "write a review" link + ready-to-send messages. */
SemrsTools.register('google-review-link-generator', function (root, api) {
	var h = api.h;
	var s = api.load(api.id) || {};
	var pid = h('input', { type: 'text', value: s.pid || '', placeholder: 'ChIJN1t_tDeuEmsRUsoyG83frY4', autocomplete: 'off', spellcheck: 'false' });
	var name = h('input', { type: 'text', value: s.name || '', placeholder: 'Your business name' });
	var input = api.panel('Your business');
	input.append(
		api.field('Google Place ID', pid, 'Find it with Google’s free Place ID Finder (link below): search your business and copy the ID that starts with "ChIJ".'),
		h('p', null, h('a', { href: 'https://developers.google.com/maps/documentation/places/web-service/place-id#find-id', target: '_blank', rel: 'noopener', text: 'Open Google’s Place ID Finder ↗' })),
		api.field('Business name (for the messages)', name));
	var out = api.panel('Your review link');
	root.append(input, out);

	function link() { return 'https://search.google.com/local/writereview?placeid=' + encodeURIComponent(pid.value.trim()); }
	function biz() { return name.value.trim() || 'us'; }
	function msgs() {
		var l = link(), b = biz();
		return [
			['Email', 'Subject: How did we do?\n\nHi [Name],\n\nThank you for choosing ' + b + '. If you have a minute, we would really appreciate an honest review on Google. It helps other customers find us:\n\n' + l + '\n\nThank you!\n[Your name]'],
			['SMS / WhatsApp', 'Hi [Name], thanks for choosing ' + b + '! Could you leave us a quick Google review? ' + l],
			['Website / receipt', 'Happy with our service? Leave us a Google review: ' + l]
		];
	}
	function render() {
		var v = pid.value.trim();
		out.innerHTML = '';
		out.append(h('h3', { class: 'semrs-tool__title', text: 'Your review link' }));
		if (!v) {
			out.append(h('p', { class: 'semrs-tool__hint', text: 'Paste your Place ID to create the link.' }));
			return;
		}
		if (!/^[A-Za-z0-9_-]{20,}$/.test(v)) {
			out.append(h('ul', { class: 'semrs-tool__checks' }, h('li', { class: 'bad', text: 'That does not look like a Place ID. It is a long code, usually starting with "ChIJ", with no spaces.' })));
			return;
		}
		out.append(
			h('pre', { class: 'semrs-tool__output', text: link() }),
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(link, 'Copy link', api.id), h('a', { class: 'semrs-btn semrs-btn--ghost', href: link(), target: '_blank', rel: 'noopener', text: 'Test link ↗' })));
		msgs().forEach(function (m) {
			out.append(h('p', { class: 'semrs-serp__label', text: m[0] + ' template' }), h('pre', { class: 'semrs-tool__output', text: m[1] }), h('div', { class: 'semrs-tool__actions' }, api.copyBtn(function () { return m[1]; }, 'Copy ' + m[0].toLowerCase(), api.id)));
		});
		out.append(h('ul', { class: 'semrs-tool__checks' },
			h('li', { class: 'warn', text: 'Google’s rules: ask every customer, never offer discounts or gifts for reviews, and never ask only happy customers (review gating).' })));
		api.store(api.id, { pid: pid.value, name: name.value });
	}
	root.addEventListener('input', api.debounce(render, 150));
	render();
});
