/* Social Share Preview & Open Graph Generator: Facebook, X and LinkedIn previews (approximate) + tags. */
SemrsTools.register('open-graph-generator', function (root, api) {
	var h = api.h;
	root.classList.add('semrs-tool--wide');
	var s = api.load(api.id) || {};
	function inp(key, ph, type) { var el = h('input', { type: type || 'text', value: s[key] || '', placeholder: ph }); el.dataset.k = key; return el; }
	var f = {
		title: inp('title', 'Free SEO Tools for Small Business'),
		desc: h('textarea', { rows: 3, placeholder: 'One or two sentences that make people want to click.' }),
		url: inp('url', 'https://example.com/page/', 'url'),
		image: inp('image', 'https://example.com/share-image.jpg (1200 × 630)', 'url'),
		alt: inp('alt', 'What the image shows'),
		site: inp('site', 'Brand name'),
		x: inp('x', '@yourhandle (optional)')
	};
	f.desc.value = s.desc || '';
	var type = h('select', null, ['website', 'article', 'product', 'profile', 'video.other'].map(function (t) { return h('option', { value: t, text: t }); }));
	type.value = s.type || 'website';
	var card = h('select', null, h('option', { value: 'summary_large_image', text: 'Large image (recommended)' }), h('option', { value: 'summary', text: 'Small square image' }));
	card.value = s.card || 'summary_large_image';

	var input = api.panel('Your page');
	input.append(api.field('Title', f.title), api.field('Description', f.desc), api.field('Page URL', f.url),
		api.field('Image URL', f.image, 'Use 1200 × 630 px (1.91:1), under 5 MB, JPG or PNG. Keep text away from the edges.'),
		api.field('Image alt text', f.alt), h('div', { class: 'semrs-tool__row' }, api.field('Site name', f.site), api.field('X (Twitter) handle', f.x)),
		h('div', { class: 'semrs-tool__row' }, api.field('Content type', type), api.field('X card', card)));
	var out = api.panel('Previews');
	root.append(input, out);

	function host(u) { try { return new URL(u).hostname.replace(/^www\./, ''); } catch (e) { return 'example.com'; } }
	function cut(t, n) { t = t || ''; return t.length > n ? t.slice(0, n - 1).trim() + '…' : t; }
	function img(u, cls) {
		var box = h('div', { class: 'semrs-og__img ' + (cls || '') });
		if (u && /^https?:\/\//.test(u)) {
			var im = h('img', { src: u, alt: '', loading: 'lazy', decoding: 'async', referrerpolicy: 'no-referrer' });
			im.addEventListener('load', function () { size.textContent = im.naturalWidth + ' × ' + im.naturalHeight + ' px' + (im.naturalWidth < 1200 ? ' (smaller than 1200 px wide, may look blurry)' : ''); });
			im.addEventListener('error', function () { box.textContent = 'Image could not be loaded'; size.textContent = 'The image URL did not load. Check that it is public.'; });
			box.append(im);
		} else { box.append(h('span', { text: 'No image' })); }
		return box;
	}
	var size = h('p', { class: 'semrs-tool__hint' });

	function tags() {
		var t = [], e = api.esc;
		function m(p, v, name) { if (v) { t.push('<meta ' + (name ? 'name' : 'property') + '="' + p + '" content="' + e(v) + '">'); } }
		m('og:type', type.value); m('og:title', f.title.value.trim()); m('og:description', f.desc.value.trim()); m('og:url', f.url.value.trim());
		m('og:site_name', f.site.value.trim()); m('og:image', f.image.value.trim()); m('og:image:alt', f.alt.value.trim());
		if (f.image.value.trim() && card.value === 'summary_large_image') { m('og:image:width', '1200'); m('og:image:height', '630'); }
		m('twitter:card', card.value, true); m('twitter:title', f.title.value.trim(), true); m('twitter:description', f.desc.value.trim(), true);
		m('twitter:image', f.image.value.trim(), true);
		var x = f.x.value.trim().replace(/^@?/, '@');
		if (x.length > 1) { m('twitter:site', x, true); }
		return t.join('\n');
	}

	function render() {
		var title = f.title.value.trim() || 'Your page title', desc = f.desc.value.trim(), u = f.url.value.trim(), im = f.image.value.trim();
		var warn = [];
		if (!f.title.value.trim()) { warn.push(['bad', 'Add a title.']); } else if (f.title.value.length > 60) { warn.push(['warn', 'Titles over ~60 characters get cut on most networks.']); }
		if (!desc) { warn.push(['warn', 'Add a description. Facebook and LinkedIn show it under the title.']); } else if (desc.length > 160) { warn.push(['warn', 'Descriptions over ~160 characters get cut.']); }
		if (!im) { warn.push(['bad', 'Add an image. Posts with an image get far more clicks.']); } else if (!/^https:\/\//.test(im)) { warn.push(['warn', 'Use an https:// image URL. Some networks ignore http images.']); }
		if (im && !f.alt.value.trim()) { warn.push(['warn', 'Add image alt text for screen-reader users.']); }
		if (u && !/^https?:\/\//.test(u)) { warn.push(['bad', 'The page URL must start with https://']); }
		size.textContent = '';
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: 'Previews' }),
			h('p', { class: 'semrs-serp__label', text: 'Facebook' }),
			h('div', { class: 'semrs-og semrs-og--fb' }, img(im), h('div', { class: 'semrs-og__body' }, h('span', { class: 'semrs-og__host', text: host(u).toUpperCase() }), h('b', { text: cut(title, 88) }), h('span', { text: cut(desc, 110) }))),
			h('p', { class: 'semrs-serp__label', text: 'X (Twitter)' }),
			card.value === 'summary'
				? h('div', { class: 'semrs-og semrs-og--x semrs-og--small' }, img(im, 'is-square'), h('div', { class: 'semrs-og__body' }, h('span', { class: 'semrs-og__host', text: host(u) }), h('b', { text: cut(title, 70) }), h('span', { text: cut(desc, 120) })))
				: h('div', { class: 'semrs-og semrs-og--x' }, img(im), h('div', { class: 'semrs-og__cap', text: host(u) + ' · ' + cut(title, 70) })),
			h('p', { class: 'semrs-serp__label', text: 'LinkedIn' }),
			h('div', { class: 'semrs-og semrs-og--li' }, img(im), h('div', { class: 'semrs-og__body' }, h('b', { text: cut(title, 100) }), h('span', { class: 'semrs-og__host', text: host(u) }))),
			size,
			warn.length ? api.checks(warn) : null,
			h('p', { class: 'semrs-serp__label', text: 'Your tags (paste into <head>)' }),
			h('pre', { class: 'semrs-tool__output', text: tags() }),
			h('div', { class: 'semrs-tool__actions' }, api.copyBtn(tags, 'Copy tags', api.id)),
			h('p', { class: 'semrs-tool__hint', text: 'Previews are close approximations; each network changes its design now and then. After changing tags, ask the network to re-read the page (Facebook Sharing Debugger, LinkedIn Post Inspector).' }));
		var save = { desc: f.desc.value, type: type.value, card: card.value };
		Object.keys(f).forEach(function (k) { if (k !== 'desc') { save[k] = f[k].value; } });
		api.store(api.id, save);
	}
	root.addEventListener('input', api.debounce(render, 250));
	root.addEventListener('change', render);
	render();
});
