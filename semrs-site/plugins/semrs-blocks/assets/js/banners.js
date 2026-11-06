/* SEMRS Banner Slider: transform-based track (exact, no layout shift), arrows, dots, swipe,
   keyboard, and autoplay that pauses on hover, focus, hidden tab and the pause button,
   and never starts for "reduce motion" users. */
(function () {
	'use strict';
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	function init(box) {
		if (box.dataset.ready) { return; }
		box.dataset.ready = '1';
		var track = box.querySelector('.semrs-banners__track');
		var slides = [].slice.call(track.children);
		var n = slides.length;
		var cur = 0;
		var bar = box.querySelector('.semrs-banners__bar');
		var dots = [];
		var timer = null;
		var paused = reduce;
		var hold = false;
		function show(i) {
			cur = (i + n) % n;
			track.style.transform = 'translateX(' + (-100 * cur) + '%)';
			dots.forEach(function (d, k) { d.setAttribute('aria-current', k === cur ? 'true' : 'false'); });
			slides.forEach(function (s, k) {
				s.setAttribute('aria-hidden', k === cur ? 'false' : 'true');
				s.inert = k !== cur;
				if (k === cur) { var im = s.querySelector('img[loading="lazy"]'); if (im) { im.loading = 'eager'; } }
			});
			// Warm the next image so it is ready when the slide arrives.
			var nx = slides[(cur + 1) % n].querySelector('img[loading="lazy"]');
			if (nx) { nx.loading = 'eager'; }
		}
		function stop() { clearInterval(timer); timer = null; }
		function start() {
			stop();
			if (box.dataset.autoplay === '1' && !paused && !hold && !document.hidden) {
				timer = setInterval(function () { show(cur + 1); }, +box.dataset.interval || 6000);
			}
		}
		function user(i) { show(i); start(); } // A manual move restarts the timer.
		if (bar) {
			if (!bar.hasAttribute('data-nodots')) {
				slides.forEach(function (s, k) {
					var d = document.createElement('button');
					d.type = 'button';
					d.className = 'semrs-banners__dot';
					d.setAttribute('aria-label', 'Go to slide ' + (k + 1));
					d.addEventListener('click', function () { user(k); });
					bar.appendChild(d);
					dots.push(d);
				});
			}
			if (box.dataset.autoplay === '1' && !reduce) {
				var p = document.createElement('button');
				p.type = 'button';
				p.className = 'semrs-banners__pause';
				p.setAttribute('aria-label', 'Pause slides');
				p.textContent = '❚❚';
				p.addEventListener('click', function () {
					paused = !paused;
					p.textContent = paused ? '▶' : '❚❚';
					p.setAttribute('aria-label', paused ? 'Play slides' : 'Pause slides');
					start();
				});
				bar.appendChild(p);
			}
		}
		var prev = box.querySelector('.semrs-banners__prev');
		var next = box.querySelector('.semrs-banners__next');
		if (prev) { prev.addEventListener('click', function () { user(cur - 1); }); }
		if (next) { next.addEventListener('click', function () { user(cur + 1); }); }
		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { e.preventDefault(); user(cur + 1); }
			if (e.key === 'ArrowLeft') { e.preventDefault(); user(cur - 1); }
		});
		// Swipe on touch screens.
		var x0 = null, y0 = null;
		track.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
		track.addEventListener('touchend', function (e) {
			if (x0 === null) { return; }
			var dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
			if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) { user(cur + (dx < 0 ? 1 : -1)); }
			x0 = null;
		}, { passive: true });
		box.addEventListener('mouseenter', function () { hold = true; stop(); });
		box.addEventListener('mouseleave', function () { hold = false; start(); });
		box.addEventListener('focusin', function () { hold = true; stop(); });
		box.addEventListener('focusout', function (e) { if (!box.contains(e.relatedTarget)) { hold = false; start(); } });
		document.addEventListener('visibilitychange', start);
		show(0);
		start();
	}
	function all() { document.querySelectorAll('.semrs-banners').forEach(init); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', all); } else { all(); }
})();
