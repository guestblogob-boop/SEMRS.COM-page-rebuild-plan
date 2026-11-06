/* SEMRS consent banner: categories, Google Consent Mode v2 updates, WP Consent API
 * cookies and events, scripts that wait for consent, and an optional consent log. */
(function () {
	'use strict';
	var C = window.semrsConsentConf || {};
	var box = document.getElementById('semrs-cc');
	if (!box) { return; }
	var reopen = document.querySelector('.semrs-cc-open');
	var cats = C.cats || ['functional'];
	var maxAge = (C.days || 182) * 86400;

	function readCookie(n) { var m = document.cookie.match(new RegExp('(?:^|; )' + n + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
	function writeCookie(n, v) { document.cookie = n + '=' + encodeURIComponent(v) + ';path=/;max-age=' + maxAge + ';SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : ''); }
	function saved() { try { var c = JSON.parse(readCookie('semrs_consent') || 'null'); return c && c.v === C.version ? c : null; } catch (e) { return null; } }
	function vid() { var a = new Uint8Array(16); (window.crypto || window.msCrypto).getRandomValues(a); return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join(''); }

	// WP Consent API, browser side (the plugin's own functions when it is active).
	window.wp_consent_type = C.type || 'optin';
	try { document.dispatchEvent(new CustomEvent('wp_consent_type_defined')); } catch (e) {}
	if (typeof window.wp_set_consent !== 'function') {
		window.wp_set_consent = function (cat, val) {
			writeCookie('wp_consent_' + cat, val);
			var d = {}; d[cat] = val;
			try { document.dispatchEvent(new CustomEvent('wp_listen_for_consent_change', { detail: d })); } catch (e) {}
		};
	}
	if (typeof window.wp_has_consent !== 'function') {
		window.wp_has_consent = function (cat) {
			if (cat === 'functional') { return true; }
			if (cat === 'statistics-anonymous') { cat = 'statistics'; }
			var v = readCookie('wp_consent_' + cat);
			return v ? v === 'allow' : window.wp_consent_type === 'optout';
		};
	}

	function activate(c) {
		[].slice.call(document.querySelectorAll('script[type="text/plain"][data-semrs-consent]')).forEach(function (old) {
			if (!c[old.getAttribute('data-semrs-consent')]) { return; }
			var s = document.createElement('script');
			[].slice.call(old.attributes).forEach(function (a) { if (a.name !== 'type' && a.name !== 'data-semrs-consent') { s.setAttribute(a.name, a.value); } });
			s.text = old.text;
			old.parentNode.replaceChild(s, old);
		});
	}

	function apply(c, fresh) {
		var g = function (k) { return c[k] ? 'granted' : 'denied'; };
		if (typeof window.gtag === 'function') {
			window.gtag('consent', 'update', { ad_storage: g('marketing'), ad_user_data: g('marketing'), ad_personalization: g('marketing'), analytics_storage: g('statistics'), personalization_storage: g('preferences') });
		}
		['preferences', 'statistics', 'marketing'].forEach(function (k) {
			if (fresh || readCookie('wp_consent_' + k) !== (c[k] ? 'allow' : 'deny')) { window.wp_set_consent(k, c[k] ? 'allow' : 'deny'); }
			if (k === 'statistics' && (fresh || readCookie('wp_consent_statistics-anonymous') !== (c[k] ? 'allow' : 'deny'))) { window.wp_set_consent('statistics-anonymous', c[k] ? 'allow' : 'deny'); }
		});
		activate(c);
		try { document.dispatchEvent(new CustomEvent('semrs_consent', { detail: c })); } catch (e) {}
	}

	function store(c) {
		var old = saved();
		c.v = C.version; c.id = (old && old.id) || vid(); c.t = Date.now();
		writeCookie('semrs_consent', JSON.stringify(c));
		apply(c, true);
		if (C.log && window.fetch) {
			var body = { id: c.id };
			cats.forEach(function (k) { body[k] = c[k] ? 1 : 0; });
			fetch(C.log, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body), keepalive: true, credentials: 'omit' }).catch(function () {});
		}
		hide();
	}

	var panel = box.querySelector('.semrs-cc__cats');
	var btn = function (n) { return box.querySelector('[data-cc="' + n + '"]'); };
	function show(withPrefs) {
		var c = saved() || {};
		[].slice.call(panel.querySelectorAll('input[data-cat]')).forEach(function (i) { if (!i.disabled) { i.checked = !!c[i.getAttribute('data-cat')]; } });
		panel.hidden = !withPrefs; btn('save').hidden = !withPrefs; btn('prefs').hidden = !!withPrefs;
		box.hidden = false;
		if (reopen) { reopen.hidden = true; }
		var first = box.querySelector('button:not([hidden])'); if (first && withPrefs !== undefined) { first.focus(); }
	}
	function hide() { box.hidden = true; if (reopen) { reopen.hidden = false; } }
	function all(v) { var c = {}; cats.forEach(function (k) { c[k] = k === 'functional' ? true : v; }); return c; }

	btn('accept').addEventListener('click', function () { store(all(true)); });
	btn('reject').addEventListener('click', function () { store(all(false)); });
	btn('prefs').addEventListener('click', function () { show(true); });
	btn('save').addEventListener('click', function () {
		var c = all(false);
		[].slice.call(panel.querySelectorAll('input[data-cat]')).forEach(function (i) { c[i.getAttribute('data-cat')] = i.checked; });
		c.functional = true;
		store(c);
	});
	box.addEventListener('keydown', function (e) { if (e.key === 'Escape' && saved()) { hide(); } });
	if (reopen) { reopen.addEventListener('click', function () { show(true); }); }
	document.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('a[href="#cookie-settings"], .semrs-cc-link');
		if (a) { e.preventDefault(); show(true); }
	});

	var c = saved();
	if (c) { apply(c, false); hide(); } else if (C.type === 'optout') { apply(all(true), false); show(); } else { show(); }
})();
