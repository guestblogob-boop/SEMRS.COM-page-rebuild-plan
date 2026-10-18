/* SEMRS theme: mobile menu, submenu toggles, header search, sticky header
 * state, reading progress and back to top. No dependencies. */
(function () {
	'use strict';
	var d = document, body = d.body;

	// Mobile menu.
	var toggle = d.querySelector('.menu-toggle');
	var nav = d.getElementById('site-navigation');
	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		d.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
				toggle.focus();
			}
		});
		// Submenu toggle buttons (used below 922px).
		nav.querySelectorAll('.menu-item-has-children > a').forEach(function (link) {
			var sub = link.parentNode.querySelector('.sub-menu');
			if (!sub) { return; }
			var btn = d.createElement('button');
			btn.type = 'button';
			btn.className = 'sp-submenu-toggle';
			btn.setAttribute('aria-expanded', 'false');
			btn.setAttribute('aria-label', (link.textContent || '').trim() + ' submenu');
			btn.addEventListener('click', function () {
				var open = sub.classList.toggle('is-open');
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
			link.insertAdjacentElement('afterend', btn);
		});
	}

	// Header search panels.
	d.querySelectorAll('.sp-search-toggle').forEach(function (btn) {
		var panel = d.getElementById(btn.getAttribute('aria-controls'));
		if (!panel) { return; }
		function close() { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
		btn.addEventListener('click', function () {
			var open = panel.hidden;
			panel.hidden = !open;
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) { var f = panel.querySelector('input[type=search]'); if (f) { f.focus(); } }
		});
		d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { close(); btn.focus(); } });
		d.addEventListener('click', function (e) { if (!panel.hidden && !btn.parentNode.contains(e.target)) { close(); } });
	});

	// One scroll handler: sticky header state, reading progress, back to top.
	var sticky = body.classList.contains('sp-sticky-header');
	var bar = d.querySelector('.sp-progress span');
	var article = d.querySelector('.single-post .entry-content');
	var top = d.querySelector('.sp-to-top');
	var ticking = false;
	function update() {
		var y = window.pageYOffset;
		if (sticky) { body.classList.toggle('sp-scrolled', y > 40); }
		if (bar && article) {
			var span = Math.max(article.offsetHeight - window.innerHeight, 1);
			bar.style.transform = 'scaleX(' + Math.min(Math.max((y - article.offsetTop) / span, 0), 1) + ')';
		}
		if (top) { top.hidden = y < 600; }
		ticking = false;
	}
	if (sticky || bar || top) {
		window.addEventListener('scroll', function () { if (!ticking) { ticking = true; window.requestAnimationFrame(update); } }, { passive: true });
		update();
	}
	if (top) {
		top.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
		});
	}
})();
