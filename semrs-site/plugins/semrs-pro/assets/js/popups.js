/* SEMRS Pro popups: delay / scroll / exit-intent / click triggers, frequency
 * memory in local or session storage, focus kept inside and returned on
 * close, Esc and overlay click to close. */
(function () {
	'use strict';
	var store = function (type) { try { var s = window[type]; s.setItem('semrs-t', '1'); s.removeItem('semrs-t'); return s; } catch (e) { return null; } };
	var local = store('localStorage'), session = store('sessionStorage');
	var w = window.innerWidth, device = w < 782 ? 'mobile' : (w < 1024 ? 'tablet' : 'desktop');
	var openNow = null, lastFocus = null;

	function key(p) { return 'semrs-popup-' + p.id; }
	function seen(p) {
		var f = p.getAttribute('data-freq');
		if (f === 'session') { return session && session.getItem(key(p)); }
		if (f === 'days' && local) {
			var t = parseInt(local.getItem(key(p)) || '0', 10);
			return t && (Date.now() - t) < parseInt(p.getAttribute('data-days'), 10) * 864e5;
		}
		return false;
	}
	function remember(p) {
		var f = p.getAttribute('data-freq');
		if (f === 'session' && session) { session.setItem(key(p), '1'); }
		if (f === 'days' && local) { local.setItem(key(p), String(Date.now())); }
	}
	function focusables(p) { return [].slice.call(p.querySelectorAll('a[href],button,input,select,textarea,[tabindex]:not([tabindex="-1"])')).filter(function (x) { return !x.disabled && x.offsetParent !== null; }); }
	function open(p, manual) {
		if (openNow || (!manual && seen(p))) { return; }
		openNow = p;
		lastFocus = document.activeElement;
		p.hidden = false;
		remember(p);
		var modal = p.getAttribute('aria-modal') === 'true';
		if (modal) { document.documentElement.style.overflow = 'hidden'; }
		var f = focusables(p);
		(f[0] || p).focus();
	}
	function close() {
		if (!openNow) { return; }
		openNow.hidden = true;
		document.documentElement.style.overflow = '';
		openNow = null;
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	document.querySelectorAll('.semrs-popup').forEach(function (p) {
		p.querySelector('.semrs-popup__close').addEventListener('click', close);
		p.addEventListener('click', function (e) { if (e.target === p && p.classList.contains('has-overlay')) { close(); } });
		var devices = (p.getAttribute('data-devices') || '').split(' ');
		var trig = p.getAttribute('data-trigger');
		if (devices.indexOf(device) === -1 || trig === 'click') { return; }
		if (trig === 'delay') { setTimeout(function () { open(p); }, parseInt(p.getAttribute('data-delay'), 10) * 1000); }
		if (trig === 'scroll') {
			var pct = parseInt(p.getAttribute('data-scroll'), 10) / 100;
			var onScroll = function () {
				var h = document.documentElement.scrollHeight - window.innerHeight;
				if (h > 0 && window.pageYOffset / h >= pct) { window.removeEventListener('scroll', onScroll); open(p); }
			};
			window.addEventListener('scroll', onScroll, { passive: true });
		}
		if (trig === 'exit' && device === 'desktop') {
			document.addEventListener('mouseout', function onOut(e) {
				if (!e.relatedTarget && e.clientY <= 0) { document.removeEventListener('mouseout', onOut); open(p); }
			});
		}
	});

	document.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('a[href*="#semrs-popup-"]');
		if (!a) { return; }
		var p = document.getElementById(a.getAttribute('href').split('#')[1]);
		if (p && p.classList.contains('semrs-popup')) { e.preventDefault(); open(p, true); }
	});
	document.addEventListener('keydown', function (e) {
		if (!openNow) { return; }
		if (e.key === 'Escape') { close(); return; }
		if (e.key === 'Tab' && openNow.getAttribute('aria-modal') === 'true') {
			var f = focusables(openNow), i = f.indexOf(document.activeElement);
			if (!f.length) { return; }
			if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); } else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
		}
	});
})();
