/* SEMRS Pro scroll animations: elements only hide once this script runs
 * (class on <html>), so content is never lost without JS. */
(function () {
	'use strict';
	if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
	var els = document.querySelectorAll('.semrs-anim');
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
	}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
	// Anything already on screen shows immediately (no flash above the fold).
	els.forEach(function (el) {
		var r = el.getBoundingClientRect();
		if (r.top < window.innerHeight && r.bottom > 0) { el.classList.add('is-in'); } else { io.observe(el); }
	});
	document.documentElement.classList.add('semrs-anim-ready');
})();
