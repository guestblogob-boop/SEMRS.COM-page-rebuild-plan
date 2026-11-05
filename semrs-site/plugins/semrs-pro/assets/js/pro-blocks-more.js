/* SEMRS Pro blocks: popup, pricing switch, sticky bar, before/after, video
 * popup, content switcher, data table, animated timeline, reviews slider and
 * Lottie. No dependencies; every part only runs when its block is on the page. */
(function () {
	'use strict';
	var $$ = function (sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); };
	var store = {
		get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
	};

	function openDialog(d) {
		if (!d) { return; }
		if (typeof d.showModal === 'function') { d.showModal(); } else { d.setAttribute('open', ''); }
	}
	function closeDialog(d) {
		if (!d) { return; }
		if (typeof d.close === 'function') { d.close(); } else { d.removeAttribute('open'); }
	}

	// Popup / modal.
	$$('[data-semrs-open]').forEach(function (b) {
		b.addEventListener('click', function () { openDialog(document.getElementById(b.getAttribute('data-semrs-open'))); });
	});
	$$('dialog.semrs-modal').forEach(function (d) {
		d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('[data-semrs-close]')) { closeDialog(d); } });
		var delay = parseInt(d.getAttribute('data-delay'), 10);
		if (delay > 0) {
			var key = 'semrs-modal-' + location.pathname + '-' + d.id;
			if (d.getAttribute('data-once') && store.get(key)) { return; }
			setTimeout(function () { openDialog(d); store.set(key, '1'); }, delay * 1000);
		}
	});

	// Pricing switch.
	$$('.semrs-ps').forEach(function (box) {
		var sw = box.querySelector('.semrs-ps__sw');
		if (!sw) { return; }
		sw.addEventListener('click', function () {
			var yearly = sw.getAttribute('aria-checked') !== 'true';
			sw.setAttribute('aria-checked', yearly ? 'true' : 'false');
			box.setAttribute('data-period', yearly ? 'yearly' : 'monthly');
		});
	});

	// Sticky bar.
	$$('.semrs-sbar').forEach(function (bar) {
		if (bar.classList.contains('semrs-sbar--editor')) { return; }
		var key = 'semrs-sbar-closed';
		var closed = parseInt(store.get(key), 10);
		if (closed && Date.now() - closed < 7 * 864e5) { bar.hidden = true; return; }
		var after = parseInt(bar.getAttribute('data-after'), 10) || 0;
		var check = function () { bar.classList.toggle('is-off', window.scrollY < after); };
		check();
		window.addEventListener('scroll', check, { passive: true });
		var x = bar.querySelector('[data-semrs-sbar-close]');
		if (x) {
			x.addEventListener('click', function () { bar.hidden = true; store.set(key, String(Date.now())); window.removeEventListener('scroll', check); });
		}
	});

	// Before / after.
	$$('.semrs-ba').forEach(function (box) {
		var r = box.querySelector('.semrs-ba__range');
		if (!r) { return; }
		var set = function () { box.style.setProperty('--pos', r.value + '%'); };
		r.addEventListener('input', set);
	});

	// Video popup: the iframe is created only on click.
	$$('[data-semrs-video]').forEach(function (b) {
		b.addEventListener('click', function () {
			var d = document.createElement('dialog');
			d.className = 'semrs-vdlg';
			d.style.setProperty('--vr', b.style.getPropertyValue('--vr') || '16/9');
			d.innerHTML = '<button type="button" class="semrs-modal__x" aria-label="Close">&times;</button>';
			var f = document.createElement('iframe');
			f.src = b.getAttribute('data-semrs-video');
			f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
			f.allowFullscreen = true;
			f.title = b.getAttribute('aria-label') || 'Video';
			d.appendChild(f);
			document.body.appendChild(d);
			var done = function () { d.remove(); b.focus(); };
			d.addEventListener('close', done);
			d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('.semrs-modal__x')) { closeDialog(d); if (!d.close) { done(); } } });
			openDialog(d);
		});
	});

	// Content switcher.
	$$('.semrs-sw').forEach(function (box) {
		var btns = $$(':scope > .semrs-sw__bar button', box);
		var panels = $$('.semrs-swp', box).filter(function (p) { return p.closest('.semrs-sw') === box; });
		if (btns.length < 2 || panels.length < 2) { return; }
		var show = function (i) {
			btns.forEach(function (b, k) { b.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
			panels.forEach(function (p, k) { p.hidden = k !== i; });
		};
		btns.forEach(function (b, i) { b.addEventListener('click', function () { show(i); }); });
		show(0);
		box.classList.add('is-ready');
	});

	// Data table: search and sort.
	$$('.semrs-dt').forEach(function (box) {
		var tbody = box.querySelector('tbody');
		var rows = $$('tr', tbody);
		var q = box.querySelector('.semrs-dt__search');
		if (q) {
			q.addEventListener('input', function () {
				var s = q.value.toLowerCase();
				rows.forEach(function (r) { r.hidden = s && r.textContent.toLowerCase().indexOf(s) === -1; });
			});
		}
		if (!box.hasAttribute('data-sort')) { return; }
		$$('thead th', box).forEach(function (th, col) {
			var b = th.querySelector('button');
			if (!b) { return; }
			b.addEventListener('click', function () {
				var asc = th.getAttribute('aria-sort') !== 'ascending';
				$$('thead th', box).forEach(function (t) { t.removeAttribute('aria-sort'); });
				th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');
				var val = function (r) { var t = (r.children[col] || {}).textContent || ''; var n = parseFloat(t.replace(/[^0-9.\-]/g, '')); return isNaN(n) || /[a-z]/i.test(t.replace(/[0-9.,\s%$€£-]/g, '')) ? t.toLowerCase() : n; };
				rows.sort(function (a, c) { var x = val(a), y = val(c); return (x > y ? 1 : x < y ? -1 : 0) * (asc ? 1 : -1); });
				rows.forEach(function (r) { tbody.appendChild(r); });
			});
		});
	});

	// Animated timeline.
	var tls = $$('.semrs-tlp');
	if (tls.length && 'IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (es) {
			es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
		}, { rootMargin: '0px 0px -10% 0px' });
		tls.forEach(function (tl) { tl.classList.add('is-js'); $$('li', tl).forEach(function (li) { io.observe(li); }); });
	}

	// Reviews slider.
	$$('.semrs-rv').forEach(function (box) {
		var track = box.querySelector('.semrs-rv__track');
		var step = function (dir) {
			var card = track.querySelector('.semrs-rv__card');
			var w = card ? card.getBoundingClientRect().width + 20 : track.clientWidth;
			var end = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
			if (dir > 0 && end) { track.scrollTo({ left: 0, behavior: 'smooth' }); return; }
			track.scrollBy({ left: dir * w, behavior: 'smooth' });
		};
		$$('.semrs-rv__nav button', box).forEach(function (b) {
			b.addEventListener('click', function () { step(parseInt(b.getAttribute('data-dir'), 10)); });
		});
		if (box.hasAttribute('data-autoplay') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			var paused = false;
			box.addEventListener('mouseenter', function () { paused = true; });
			box.addEventListener('mouseleave', function () { paused = false; });
			box.addEventListener('focusin', function () { paused = true; });
			box.addEventListener('focusout', function () { paused = false; });
			setInterval(function () { if (!paused && !document.hidden) { step(1); } }, 6000);
		}
	});

	// Lottie (lottie-web loads in the same deferred queue just before this file).
	var lotties = $$('.semrs-lottie');
	if (lotties.length) {
		var start = function () {
			if (!window.lottie) { return; }
			var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			lotties.forEach(function (el) {
				var hover = el.getAttribute('data-hover') === '1';
				var anim = window.lottie.loadAnimation({ container: el, renderer: 'svg', loop: el.getAttribute('data-loop') === '1', autoplay: !hover && !still, path: el.getAttribute('data-src') });
				if (hover) {
					el.addEventListener('mouseenter', function () { anim.play(); });
					el.addEventListener('mouseleave', function () { anim.pause(); });
				}
			});
		};
		if (window.lottie) { start(); } else { window.addEventListener('load', start); }
	}
})();
