/* SEMRS Contact Form: country picker with flags and dialling codes, inline
 * validation, AJAX submit, GA4 generate_lead event, optional redirect. */
(function () {
	'use strict';
	var C = 'AF:93:Afghanistan|AL:355:Albania|DZ:213:Algeria|AS:1:American Samoa|AD:376:Andorra|AO:244:Angola|AI:1:Anguilla|AG:1:Antigua and Barbuda|AR:54:Argentina|AM:374:Armenia|AW:297:Aruba|AU:61:Australia|AT:43:Austria|AZ:994:Azerbaijan|BS:1:Bahamas|BH:973:Bahrain|BD:880:Bangladesh|BB:1:Barbados|BY:375:Belarus|BE:32:Belgium|BZ:501:Belize|BJ:229:Benin|BM:1:Bermuda|BT:975:Bhutan|BO:591:Bolivia|BA:387:Bosnia and Herzegovina|BW:267:Botswana|BR:55:Brazil|BN:673:Brunei|BG:359:Bulgaria|BF:226:Burkina Faso|BI:257:Burundi|KH:855:Cambodia|CM:237:Cameroon|CA:1:Canada|CV:238:Cape Verde|KY:1:Cayman Islands|CF:236:Central African Republic|TD:235:Chad|CL:56:Chile|CN:86:China|CO:57:Colombia|KM:269:Comoros|CG:242:Congo|CD:243:Congo (DRC)|CK:682:Cook Islands|CR:506:Costa Rica|CI:225:Côte d’Ivoire|HR:385:Croatia|CU:53:Cuba|CW:599:Curaçao|CY:357:Cyprus|CZ:420:Czechia|DK:45:Denmark|DJ:253:Djibouti|DM:1:Dominica|DO:1:Dominican Republic|EC:593:Ecuador|EG:20:Egypt|SV:503:El Salvador|GQ:240:Equatorial Guinea|ER:291:Eritrea|EE:372:Estonia|SZ:268:Eswatini|ET:251:Ethiopia|FJ:679:Fiji|FI:358:Finland|FR:33:France|GF:594:French Guiana|PF:689:French Polynesia|GA:241:Gabon|GM:220:Gambia|GE:995:Georgia|DE:49:Germany|GH:233:Ghana|GI:350:Gibraltar|GR:30:Greece|GL:299:Greenland|GD:1:Grenada|GP:590:Guadeloupe|GU:1:Guam|GT:502:Guatemala|GG:44:Guernsey|GN:224:Guinea|GW:245:Guinea-Bissau|GY:592:Guyana|HT:509:Haiti|HN:504:Honduras|HK:852:Hong Kong|HU:36:Hungary|IS:354:Iceland|IN:91:India|ID:62:Indonesia|IR:98:Iran|IQ:964:Iraq|IE:353:Ireland|IM:44:Isle of Man|IL:972:Israel|IT:39:Italy|JM:1:Jamaica|JP:81:Japan|JE:44:Jersey|JO:962:Jordan|KZ:7:Kazakhstan|KE:254:Kenya|KI:686:Kiribati|XK:383:Kosovo|KW:965:Kuwait|KG:996:Kyrgyzstan|LA:856:Laos|LV:371:Latvia|LB:961:Lebanon|LS:266:Lesotho|LR:231:Liberia|LY:218:Libya|LI:423:Liechtenstein|LT:370:Lithuania|LU:352:Luxembourg|MO:853:Macao|MG:261:Madagascar|MW:265:Malawi|MY:60:Malaysia|MV:960:Maldives|ML:223:Mali|MT:356:Malta|MH:692:Marshall Islands|MQ:596:Martinique|MR:222:Mauritania|MU:230:Mauritius|YT:262:Mayotte|MX:52:Mexico|FM:691:Micronesia|MD:373:Moldova|MC:377:Monaco|MN:976:Mongolia|ME:382:Montenegro|MS:1:Montserrat|MA:212:Morocco|MZ:258:Mozambique|MM:95:Myanmar|NA:264:Namibia|NR:674:Nauru|NP:977:Nepal|NL:31:Netherlands|NC:687:New Caledonia|NZ:64:New Zealand|NI:505:Nicaragua|NE:227:Niger|NG:234:Nigeria|KP:850:North Korea|MK:389:North Macedonia|MP:1:Northern Mariana Islands|NO:47:Norway|OM:968:Oman|PK:92:Pakistan|PW:680:Palau|PS:970:Palestine|PA:507:Panama|PG:675:Papua New Guinea|PY:595:Paraguay|PE:51:Peru|PH:63:Philippines|PL:48:Poland|PT:351:Portugal|PR:1:Puerto Rico|QA:974:Qatar|RE:262:Réunion|RO:40:Romania|RU:7:Russia|RW:250:Rwanda|KN:1:Saint Kitts and Nevis|LC:1:Saint Lucia|VC:1:Saint Vincent and the Grenadines|WS:685:Samoa|SM:378:San Marino|ST:239:São Tomé and Príncipe|SA:966:Saudi Arabia|SN:221:Senegal|RS:381:Serbia|SC:248:Seychelles|SL:232:Sierra Leone|SG:65:Singapore|SX:1:Sint Maarten|SK:421:Slovakia|SI:386:Slovenia|SB:677:Solomon Islands|SO:252:Somalia|ZA:27:South Africa|KR:82:South Korea|SS:211:South Sudan|ES:34:Spain|LK:94:Sri Lanka|SD:249:Sudan|SR:597:Suriname|SE:46:Sweden|CH:41:Switzerland|SY:963:Syria|TW:886:Taiwan|TJ:992:Tajikistan|TZ:255:Tanzania|TH:66:Thailand|TL:670:Timor-Leste|TG:228:Togo|TO:676:Tonga|TT:1:Trinidad and Tobago|TN:216:Tunisia|TR:90:Türkiye|TM:993:Turkmenistan|TC:1:Turks and Caicos Islands|TV:688:Tuvalu|UG:256:Uganda|UA:380:Ukraine|AE:971:United Arab Emirates|GB:44:United Kingdom|US:1:United States|UY:598:Uruguay|UZ:998:Uzbekistan|VU:678:Vanuatu|VA:39:Vatican City|VE:58:Venezuela|VN:84:Vietnam|VG:1:British Virgin Islands|VI:1:U.S. Virgin Islands|YE:967:Yemen|ZM:260:Zambia|ZW:263:Zimbabwe';
	var countries = C.split('|').map(function (r) { var p = r.split(':'); return { iso: p[0], dial: p[1], name: p[2] }; });
	var flag = function (iso) { return iso.replace(/./g, function (ch) { return String.fromCodePoint(127397 + ch.charCodeAt(0)); }); };

	function fillCountries(sel) {
		var def = (sel.getAttribute('data-default') || 'US').toUpperCase();
		countries.forEach(function (c) {
			var o = document.createElement('option');
			o.value = c.iso;
			o.dataset.dial = c.dial;
			o.textContent = flag(c.iso) + ' ' + c.name + ' (+' + c.dial + ')';
			if (c.iso === def) { o.selected = true; o.defaultSelected = true; } // defaultSelected: form.reset() returns here.
			sel.appendChild(o);
		});
		// Compact label once chosen (flag + code) keeps the field short on phones.
		function compact() { [].forEach.call(sel.options, function (o) { var c = countries.find(function (x) { return x.iso === o.value; }); o.textContent = (o.selected ? flag(c.iso) + ' +' + c.dial : flag(c.iso) + ' ' + c.name + ' (+' + c.dial + ')'); }); }
		sel.addEventListener('focus', function () { [].forEach.call(sel.options, function (o) { var c = countries.find(function (x) { return x.iso === o.value; }); o.textContent = flag(c.iso) + ' ' + c.name + ' (+' + c.dial + ')'; }); });
		sel.addEventListener('blur', compact);
		sel.addEventListener('change', function () { sel.blur(); });
		compact();
	}

	function setError(form, name, msg) {
		var field = form.querySelector('[name="' + name + '"]');
		if (!field) { return; }
		field.classList.add('is-invalid');
		field.setAttribute('aria-invalid', 'true');
		var holder = field.closest('p');
		var e = holder.querySelector('.semrs-cf__error');
		if (!e) { e = document.createElement('span'); e.className = 'semrs-cf__error'; e.id = (field.id || name) + '-err'; holder.appendChild(e); field.setAttribute('aria-describedby', e.id); }
		e.textContent = msg;
	}
	function clearErrors(form) {
		form.querySelectorAll('.is-invalid').forEach(function (f) { f.classList.remove('is-invalid'); f.removeAttribute('aria-invalid'); });
		form.querySelectorAll('.semrs-cf__error').forEach(function (e) { e.remove(); });
	}

	document.querySelectorAll('form.semrs-cf').forEach(function (form) {
		var sel = form.querySelector('select[name="country"]');
		if (sel) { fillCountries(sel); }
		var status = form.querySelector('.semrs-cf__status');
		var btn = form.querySelector('button[type="submit"]');
		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			clearErrors(form);
			status.className = 'semrs-cf__status';
			status.textContent = '';
			var data = {};
			new FormData(form).forEach(function (v, k) { data[k] = v; });
			var bad = false;
			if (!data.name || !data.name.trim()) { setError(form, 'name', 'Please enter your name.'); bad = true; }
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email || '')) { setError(form, 'email', 'Please enter a valid email address.'); bad = true; }
			if (!data.message || data.message.trim().length < 5) { setError(form, 'message', 'Please tell us a little about your project.'); bad = true; }
			var consent = form.querySelector('[name="consent"]');
			if (consent && !consent.checked) { setError(form, 'consent', 'Please tick the consent box.'); bad = true; }
			if (bad) { var first = form.querySelector('.is-invalid'); if (first) { first.focus(); } return; }
			if (sel) { data.dial = sel.options[sel.selectedIndex].dataset.dial; }
			data.form = form.getAttribute('data-form');
			data.page = location.href;
			btn.disabled = true;
			var label = btn.textContent;
			btn.textContent = 'Sending…';
			fetch(window.SEMRS_CONTACT.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
				.then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
				.then(function (res) {
					if (res.ok && res.j.ok) {
						try { if (typeof window.gtag === 'function') { window.gtag('event', 'generate_lead', { form_id: data.form, service: data.service || '' }); } } catch (e) {}
						var go = res.j.redirect || form.getAttribute('data-redirect');
						if (go) { window.location.href = go; return; }
						form.reset();
						if (sel) { sel.dispatchEvent(new Event('blur')); }
						status.className = 'semrs-cf__status is-ok';
						status.textContent = res.j.message;
						return;
					}
					var fields = res.j && res.j.data && res.j.data.fields;
					if (fields) { Object.keys(fields).forEach(function (k) { setError(form, k, fields[k]); }); }
					status.className = 'semrs-cf__status is-error';
					status.textContent = (res.j && res.j.message) || 'Something went wrong. Please try again.';
				})
				.catch(function () { status.className = 'semrs-cf__status is-error'; status.textContent = 'Could not send. Please check your connection and try again.'; })
				.then(function () { btn.disabled = false; btn.textContent = label; });
		});
	});
})();
