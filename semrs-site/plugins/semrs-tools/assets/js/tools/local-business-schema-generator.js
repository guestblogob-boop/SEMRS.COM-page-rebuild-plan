/* Local Business Schema Generator: address, geo, opening hours, areas served. */
SemrsTools.register('local-business-schema-generator', function (root, api) {
	var h = api.h;
	var TYPES = ['LocalBusiness', 'ProfessionalService', 'HomeAndConstructionBusiness', 'Plumber', 'Electrician', 'HVACBusiness', 'RoofingContractor', 'GeneralContractor', 'Locksmith', 'MovingCompany', 'Dentist', 'MedicalClinic', 'Physician', 'LegalService', 'Attorney', 'AccountingService', 'RealEstateAgent', 'InsuranceAgency', 'AutoRepair', 'BeautySalon', 'HairSalon', 'DaySpa', 'Restaurant', 'CafeOrCoffeeShop', 'Store', 'ExerciseGym', 'ChildCare', 'VeterinaryCare', 'CleaningService'];
	var DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
	var s = api.load(api.id) || { type: 'LocalBusiness', hours: {} };
	s.hours = s.hours || {};
	var F = [['name', 'Business name'], ['url', 'Website URL', 'url'], ['telephone', 'Phone (with country code)', 'tel'], ['email', 'Email', 'email'], ['image', 'Photo URL (storefront or team)', 'url'], ['logo', 'Logo URL', 'url'], ['priceRange', 'Price range (e.g. $$)'], ['street', 'Street address'], ['city', 'City'], ['region', 'State / region'], ['postal', 'ZIP / postal code'], ['country', 'Country code (US, GB, CA, AU…)'], ['lat', 'Latitude (optional)'], ['lng', 'Longitude (optional)'], ['area', 'Areas served (comma-separated, optional)'], ['sameAs', 'Profiles: Google Business, Yelp, Facebook… (one per line)', 'lines']];

	var input = api.panel('Business details');
	var typeSel = h('select');
	TYPES.forEach(function (t) { typeSel.append(h('option', { value: t, text: t.replace(/([a-z])([A-Z])/g, '$1 $2') })); });
	typeSel.value = s.type;
	typeSel.addEventListener('change', function () { s.type = typeSel.value; render(); });
	input.append(api.field('Business type', typeSel, 'Pick the most specific type that fits.'));
	F.forEach(function (f) {
		var el = f[2] === 'lines' ? h('textarea', { rows: 3 }) : h('input', { type: f[2] || 'text' });
		el.value = s[f[0]] || '';
		el.addEventListener('input', function () { s[f[0]] = el.value; render(); });
		input.append(api.field(f[1], el));
	});
	var hoursBox = h('div', { class: 'semrs-tool__repeat' }, h('strong', { text: 'Opening hours' }));
	DAYS.forEach(function (d) {
		var cur = s.hours[d] || { open: d === 'Saturday' || d === 'Sunday' ? '' : '09:00', close: d === 'Saturday' || d === 'Sunday' ? '' : '17:00' };
		s.hours[d] = cur;
		var o = h('input', { type: 'time', value: cur.open, 'aria-label': d + ' opens' });
		var c = h('input', { type: 'time', value: cur.close, 'aria-label': d + ' closes' });
		o.addEventListener('input', function () { cur.open = o.value; render(); });
		c.addEventListener('input', function () { cur.close = c.value; render(); });
		hoursBox.append(h('div', { class: 'semrs-tool__row', style: 'align-items:center;margin:.4rem 0' }, h('span', { text: d }), o, c));
	});
	hoursBox.append(h('p', { class: 'semrs-tool__hint', text: 'Leave both times empty for closed days. 00:00–23:59 = open 24 hours.' }));
	input.append(hoursBox);
	var out = api.panel('JSON-LD');
	root.append(input, out);

	function json() {
		var o = { '@context': 'https://schema.org', '@type': s.type, name: s.name, url: s.url, telephone: s.telephone, email: s.email, image: s.image, logo: s.logo, priceRange: s.priceRange };
		if (s.street || s.city) {
			o.address = { '@type': 'PostalAddress', streetAddress: s.street, addressLocality: s.city, addressRegion: s.region, postalCode: s.postal, addressCountry: s.country };
		}
		if (s.lat && s.lng) { o.geo = { '@type': 'GeoCoordinates', latitude: parseFloat(s.lat), longitude: parseFloat(s.lng) }; }
		var groups = {};
		DAYS.forEach(function (d) { var x = s.hours[d]; if (x && x.open && x.close) { var k = x.open + '-' + x.close; (groups[k] = groups[k] || []).push(d); } });
		var spec = Object.keys(groups).map(function (k) { var p = k.split('-'); return { '@type': 'OpeningHoursSpecification', dayOfWeek: groups[k], opens: p[0], closes: p[1] }; });
		if (spec.length) { o.openingHoursSpecification = spec; }
		var area = (s.area || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
		if (area.length) { o.areaServed = area; }
		var same = (s.sameAs || '').split(/\n/).map(function (x) { return x.trim(); }).filter(Boolean);
		if (same.length) { o.sameAs = same; }
		(function clean(x) { Object.keys(x).forEach(function (k) { if (x[k] && typeof x[k] === 'object' && !Array.isArray(x[k])) { clean(x[k]); } if (x[k] === '' || x[k] === undefined || (typeof x[k] === 'number' && isNaN(x[k]))) { delete x[k]; } }); })(o);
		if (o.address && Object.keys(o.address).length <= 1) { delete o.address; }
		return JSON.stringify(o, null, 2);
	}
	function code() { return '<script type="application/ld+json">\n' + json() + '\n</' + 'script>'; }
	function render() {
		var warn = [];
		if (!s.name) { warn.push('Add your business name exactly as on your Google Business Profile.'); }
		if (!s.street || !s.city) { warn.push('Add your full address. Use the same name, address and phone (NAP) everywhere online.'); }
		if (!s.telephone) { warn.push('Add a phone number with country code, e.g. +1 555 123 4567.'); }
		out.innerHTML = '';
		out.append(h('h2', { class: 'semrs-tool__title', text: s.type + ' JSON-LD' }), h('pre', { class: 'semrs-tool__output', text: code() }));
		if (warn.length) { var ul = h('ul', { class: 'semrs-tool__checks' }); warn.forEach(function (w) { ul.append(h('li', { class: 'warn', text: w })); }); out.append(ul); }
		out.append(h('div', { class: 'semrs-tool__actions' }, api.copyBtn(code, 'Copy code', api.id), h('a', { class: 'semrs-btn semrs-btn--ghost', href: 'https://search.google.com/test/rich-results', target: '_blank', rel: 'noopener', text: 'Test in Google ↗' })),
			h('p', { class: 'semrs-tool__hint', text: 'Add it once, on your homepage or contact page. Use only your real business address.' }));
		api.store(api.id, s);
	}
	render();
});
