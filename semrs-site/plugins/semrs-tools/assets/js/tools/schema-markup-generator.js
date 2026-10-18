/* Schema Markup Generator: spec-driven forms → JSON-LD. No rating/review fields (never fake ratings). */
SemrsTools.register('schema-markup-generator', function (root, api) {
	var h = api.h;
	var SPECS = {
		Organization: {
			note: 'Use on your homepage or About page.',
			fields: [['name', 'Organization name'], ['url', 'Website URL', 'url'], ['logo', 'Logo URL', 'url'], ['description', 'Short description', 'textarea'], ['email', 'Email'], ['telephone', 'Phone'], ['sameAs', 'Social profile URLs (one per line)', 'lines']]
		},
		Article: {
			note: 'Use on blog posts and news articles.',
			fields: [['headline', 'Headline'], ['description', 'Summary', 'textarea'], ['image', 'Image URL', 'url'], ['author', 'Author name'], ['authorUrl', 'Author profile URL', 'url'], ['publisher', 'Publisher name'], ['publisherLogo', 'Publisher logo URL', 'url'], ['datePublished', 'Date published', 'date'], ['dateModified', 'Date modified', 'date'], ['url', 'Article URL', 'url']],
			types: ['Article', 'BlogPosting', 'NewsArticle']
		},
		FAQPage: {
			note: 'The questions and answers must be visible on the page. Google shows FAQ rich results only for some sites, but the markup still helps search and AI understand your page.',
			repeat: { key: 'faq', label: 'Question', fields: [['q', 'Question'], ['a', 'Answer', 'textarea']] }
		},
		BreadcrumbList: {
			note: 'List the path from your homepage to this page.',
			repeat: { key: 'crumbs', label: 'Breadcrumb', fields: [['name', 'Name'], ['item', 'URL', 'url']] }
		},
		Product: {
			note: 'For product pages with a visible price. Add reviews only if real customer reviews are shown on the page.',
			fields: [['name', 'Product name'], ['description', 'Description', 'textarea'], ['image', 'Image URL', 'url'], ['brand', 'Brand'], ['sku', 'SKU'], ['price', 'Price (number)'], ['currency', 'Currency (e.g. USD)'], ['availability', 'Availability', 'select', ['InStock', 'OutOfStock', 'PreOrder', 'BackOrder']], ['url', 'Product URL', 'url']]
		},
		Event: {
			note: 'For events with a date and place (or online).',
			fields: [['name', 'Event name'], ['description', 'Description', 'textarea'], ['startDate', 'Start date & time', 'datetime-local'], ['endDate', 'End date & time', 'datetime-local'], ['mode', 'Attendance', 'select', ['OfflineEventAttendanceMode', 'OnlineEventAttendanceMode', 'MixedEventAttendanceMode']], ['venue', 'Venue name (or online URL)'], ['address', 'Venue address'], ['image', 'Image URL', 'url'], ['organizer', 'Organizer name'], ['price', 'Ticket price (0 for free)'], ['currency', 'Currency'], ['ticketUrl', 'Ticket URL', 'url']]
		},
		Service: {
			note: 'For a service page.',
			fields: [['name', 'Service name'], ['serviceType', 'Service type (e.g. SEO)'], ['description', 'Description', 'textarea'], ['provider', 'Provider (business name)'], ['providerUrl', 'Provider URL', 'url'], ['areaServed', 'Areas served (comma-separated)'], ['url', 'Service page URL', 'url'], ['price', 'From price (number, optional)'], ['currency', 'Currency']]
		}
	};
	var state = api.load(api.id) || { type: 'Organization', data: {} };
	var tabs = h('div', { class: 'semrs-tool__tabs', role: 'group', 'aria-label': 'Schema type' });
	var form = h('div');
	var input = api.panel('Choose a type');
	input.append(tabs, form);
	var out = api.panel('JSON-LD');
	root.append(input, out);

	Object.keys(SPECS).forEach(function (t) {
		tabs.append(h('button', { type: 'button', 'aria-pressed': String(t === state.type), text: t, onclick: function () { state.type = t; buildForm(); render(); tabs.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.textContent === t)); }); } }));
	});

	function data() { state.data[state.type] = state.data[state.type] || {}; return state.data[state.type]; }

	function control(f, obj, key) {
		var el;
		if (f[2] === 'textarea' || f[2] === 'lines') { el = h('textarea', { rows: f[2] === 'lines' ? 3 : 3 }); el.value = obj[key] || ''; }
		else if (f[2] === 'select') { el = h('select'); f[3].forEach(function (o) { el.append(h('option', { value: o, text: o.replace(/([a-z])([A-Z])/g, '$1 $2') })); }); el.value = obj[key] || f[3][0]; }
		else { el = h('input', { type: f[2] || 'text', value: obj[key] || '' }); }
		el.addEventListener('input', function () { obj[key] = el.value; render(); });
		el.addEventListener('change', function () { obj[key] = el.value; render(); });
		return api.field(f[1], el);
	}

	function buildForm() {
		var spec = SPECS[state.type], d = data();
		form.innerHTML = '';
		form.append(h('p', { class: 'semrs-tool__hint', text: spec.note }));
		if (spec.types) { form.append(control(['@type', 'Type', 'select', spec.types], d, '@type')); }
		(spec.fields || []).forEach(function (f) { form.append(control(f, d, f[0])); });
		if (spec.repeat) {
			var r = spec.repeat;
			d[r.key] = d[r.key] && d[r.key].length ? d[r.key] : [{}, {}];
			d[r.key].forEach(function (item, i) {
				var box = h('div', { class: 'semrs-tool__repeat' }, h('strong', { text: r.label + ' ' + (i + 1) }));
				r.fields.forEach(function (f) { box.append(control(f, item, f[0])); });
				box.append(h('button', { type: 'button', class: 'semrs-btn semrs-btn--ghost semrs-btn--small', text: 'Remove', onclick: function () { d[r.key].splice(i, 1); buildForm(); render(); } }));
				form.append(box);
			});
			form.append(h('button', { type: 'button', class: 'semrs-btn semrs-btn--small', text: '+ Add ' + r.label.toLowerCase(), onclick: function () { d[r.key].push({}); buildForm(); render(); } }));
		}
	}

	function clean(o) {
		Object.keys(o).forEach(function (k) {
			var v = o[k];
			if (v && typeof v === 'object' && !Array.isArray(v)) { clean(v); if (Object.keys(v).length <= 1 && v['@type']) { delete o[k]; } }
			else if (v === '' || v === undefined || (Array.isArray(v) && !v.length)) { delete o[k]; }
		});
		return o;
	}

	function json() {
		var d = data(), t = state.type, o = { '@context': 'https://schema.org', '@type': t };
		if (t === 'Organization') {
			o = Object.assign(o, { name: d.name, url: d.url, logo: d.logo, description: d.description, email: d.email, telephone: d.telephone, sameAs: (d.sameAs || '').split(/\n/).map(function (s) { return s.trim(); }).filter(Boolean) });
		} else if (t === 'Article') {
			o['@type'] = d['@type'] || 'Article';
			o = Object.assign(o, { headline: d.headline, description: d.description, image: d.image, datePublished: d.datePublished, dateModified: d.dateModified || d.datePublished, mainEntityOfPage: d.url,
				author: { '@type': 'Person', name: d.author, url: d.authorUrl },
				publisher: { '@type': 'Organization', name: d.publisher, logo: d.publisherLogo ? { '@type': 'ImageObject', url: d.publisherLogo } : '' } });
		} else if (t === 'FAQPage') {
			o.mainEntity = (d.faq || []).filter(function (x) { return x.q && x.a; }).map(function (x) { return { '@type': 'Question', name: x.q, acceptedAnswer: { '@type': 'Answer', text: x.a } }; });
		} else if (t === 'BreadcrumbList') {
			o.itemListElement = (d.crumbs || []).filter(function (x) { return x.name; }).map(function (x, i) { return clean({ '@type': 'ListItem', position: i + 1, name: x.name, item: x.item }); });
		} else if (t === 'Product') {
			o = Object.assign(o, { name: d.name, description: d.description, image: d.image, sku: d.sku, brand: d.brand ? { '@type': 'Brand', name: d.brand } : '',
				offers: d.price ? { '@type': 'Offer', price: d.price, priceCurrency: d.currency || 'USD', availability: 'https://schema.org/' + (d.availability || 'InStock'), url: d.url } : '' });
		} else if (t === 'Event') {
			var online = d.mode === 'OnlineEventAttendanceMode';
			o = Object.assign(o, { name: d.name, description: d.description, startDate: d.startDate, endDate: d.endDate, image: d.image, eventStatus: 'https://schema.org/EventScheduled',
				eventAttendanceMode: 'https://schema.org/' + (d.mode || 'OfflineEventAttendanceMode'),
				location: online ? { '@type': 'VirtualLocation', url: d.venue } : { '@type': 'Place', name: d.venue, address: d.address },
				organizer: d.organizer ? { '@type': 'Organization', name: d.organizer } : '',
				offers: d.price !== undefined && d.price !== '' ? { '@type': 'Offer', price: d.price, priceCurrency: d.currency || 'USD', url: d.ticketUrl, availability: 'https://schema.org/InStock' } : '' });
		} else if (t === 'Service') {
			o = Object.assign(o, { name: d.name, serviceType: d.serviceType, description: d.description, url: d.url,
				provider: d.provider ? { '@type': 'Organization', name: d.provider, url: d.providerUrl } : '',
				areaServed: (d.areaServed || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean),
				offers: d.price ? { '@type': 'Offer', price: d.price, priceCurrency: d.currency || 'USD' } : '' });
		}
		return JSON.stringify(clean(o), null, 2);
	}
	function code() { return '<script type="application/ld+json">\n' + json() + '\n</' + 'script>'; }

	function render() {
		out.innerHTML = '';
		out.append(
			h('h3', { class: 'semrs-tool__title', text: state.type + ' JSON-LD' }),
			h('pre', { class: 'semrs-tool__output', text: code() }),
			h('div', { class: 'semrs-tool__actions' },
				api.copyBtn(code, 'Copy code', api.id),
				h('a', { class: 'semrs-btn semrs-btn--ghost', href: 'https://search.google.com/test/rich-results', target: '_blank', rel: 'noopener', text: 'Test in Google ↗' })),
			h('p', { class: 'semrs-tool__hint', text: 'Paste into a Custom HTML block or your SEO plugin. Only mark up information that is visible on the page.' }));
		api.store(api.id, state);
	}
	buildForm();
	render();
});
