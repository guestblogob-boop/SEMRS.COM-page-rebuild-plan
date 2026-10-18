/* SEMRS Tabs: ARIA tabs with arrow keys, Home/End and #hash links.
 * Without JavaScript every panel stays visible with its own title. */
(function () {
	'use strict';
	document.querySelectorAll('.semrs-tabs').forEach(function (box) {
		var tabs = [].slice.call(box.querySelectorAll(':scope > .semrs-tabs__list > [role=tab]'));
		var panels = [].slice.call(box.querySelectorAll(':scope > .semrs-tabs__panels > .semrs-block--tab > .semrs-tab, :scope > .semrs-tabs__panels > .semrs-tab'));
		if (!tabs.length || tabs.length !== panels.length) { return; }
		panels.forEach(function (p, i) {
			p.id = tabs[i].getAttribute('aria-controls');
			p.setAttribute('aria-labelledby', tabs[i].id);
			p.tabIndex = 0;
		});
		function select(i, focus) {
			tabs.forEach(function (t, k) {
				var on = k === i;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				panels[k].hidden = !on;
			});
			if (focus) { tabs[i].focus(); }
		}
		tabs.forEach(function (t, i) {
			t.addEventListener('click', function () { select(i, false); });
			t.addEventListener('keydown', function (e) {
				var n = tabs.length, k = null;
				if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { k = (i + 1) % n; }
				if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { k = (i - 1 + n) % n; }
				if (e.key === 'Home') { k = 0; }
				if (e.key === 'End') { k = n - 1; }
				if (k !== null) { e.preventDefault(); select(k, true); }
			});
		});
		var start = 0;
		panels.forEach(function (p, i) { if (location.hash && p.querySelector(location.hash.replace(/[^#\w-]/g, ''))) { start = i; } });
		select(start, false);
		box.classList.add('is-ready');
	});
})();
