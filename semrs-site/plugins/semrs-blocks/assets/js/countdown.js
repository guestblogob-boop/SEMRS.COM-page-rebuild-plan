/* SEMRS Countdown: updates every second; shows the finished message at zero. */
(function () {
	'use strict';
	var pad = function (n) { return n < 10 ? '0' + n : String(n); };
	document.querySelectorAll('.semrs-cd[data-end]').forEach(function (box) {
		var end = parseInt(box.getAttribute('data-end'), 10);
		var cells = {};
		box.querySelectorAll('[data-u]').forEach(function (b) { cells[b.getAttribute('data-u')] = b; });
		var hasDays = !!cells.d;
		function tick() {
			var left = Math.max(0, Math.floor((end - Date.now()) / 1000));
			if (left <= 0) {
				var msg = box.getAttribute('data-expired');
				if (msg) { box.innerHTML = ''; var p = document.createElement('p'); p.className = 'semrs-cd__done'; p.textContent = msg; box.appendChild(p); }
				return;
			}
			var d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
			if (!hasDays) { h += d * 24; }
			if (cells.d) { cells.d.textContent = pad(d); }
			if (cells.h) { cells.h.textContent = pad(h); }
			if (cells.m) { cells.m.textContent = pad(m); }
			if (cells.s) { cells.s.textContent = pad(s); }
			setTimeout(tick, 1000 - (Date.now() % 1000));
		}
		tick();
	});
})();
