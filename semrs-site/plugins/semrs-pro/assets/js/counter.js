/* SEMRS Pro Counter: counts up when scrolled into view. The real number is
 * already in the HTML, so no-JS visitors and search engines see it. */
(function () {
	'use strict';
	var els = document.querySelectorAll('.semrs-counter__value[data-to]');
	if (!els.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) { return; }
	var fmt = function (n, dec) { return n.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec }); };
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) {
			if (!en.isIntersecting) { return; }
			io.unobserve(en.target);
			var el = en.target, to = parseFloat(el.getAttribute('data-to')) || 0;
			var dec = Math.floor(to) === to ? 0 : 1, dur = parseInt(el.getAttribute('data-duration'), 10) || 2000, t0 = null;
			function step(ts) {
				if (!t0) { t0 = ts; }
				var p = Math.min(1, (ts - t0) / dur), eased = 1 - Math.pow(1 - p, 3);
				el.textContent = fmt(to * eased, dec);
				if (p < 1) { requestAnimationFrame(step); }
			}
			el.textContent = fmt(0, dec);
			requestAnimationFrame(step);
		});
	}, { threshold: 0.4 });
	els.forEach(function (el) { io.observe(el); });
})();
