/* SEMRS Banner Slider: scroll-snap track, arrows, dots, autoplay that pauses on hover, focus,
   hidden tab and the pause button, and never starts for "reduce motion" users. */
(function () {
	'use strict';
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	function init(box) {
		if (box.dataset.ready) { return; }
		box.dataset.ready = '1';
		var track = box.querySelector('.semrs-banners__track');
		var slides = track.children;
		var n = slides.length;
		var bar = box.querySelector('.semrs-banners__bar');
		var dots = [];
		var timer = null;
		var paused = reduce;
		var hold = false;
		function index() { return Math.round(track.scrollLeft / Math.max(1, track.clientWidth)); }
		function go(i) {
			i = (i + n) % n;
			track.scrollTo({ left: i * track.clientWidth, behavior: reduce ? 'auto' : 'smooth' });
		}
		function mark() {
			var i = index();
			dots.forEach(function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
			[].forEach.call(slides, function (s, k) { s.setAttribute('aria-hidden', k === i ? 'false' : 'true'); s.inert = k !== i; });
		}
		function stop() { clearInterval(timer); timer = null; }
		function start() {
			stop();
			if (box.dataset.autoplay === '1' && !paused && !hold && !document.hidden) {
				timer = setInterval(function () { go(index() + 1); }, +box.dataset.interval || 6000);
			}
		}
		if (bar) {
			if (!bar.hasAttribute('data-nodots')) {
				for (var k = 0; k < n; k++) {
					(function (k) {
						var d = document.createElement('button');
						d.type = 'button';
						d.className = 'semrs-banners__dot';
						d.setAttribute('aria-label', 'Go to slide ' + (k + 1));
						d.addEventListener('click', function () { go(k); });
						bar.appendChild(d);
						dots.push(d);
					})(k);
				}
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
		if (prev) { prev.addEventListener('click', function () { go(index() - 1); }); }
		if (next) { next.addEventListener('click', function () { go(index() + 1); }); }
		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { e.preventDefault(); go(index() + 1); }
			if (e.key === 'ArrowLeft') { e.preventDefault(); go(index() - 1); }
		});
		var t;
		track.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(mark, 80); }, { passive: true });
		box.addEventListener('mouseenter', function () { hold = true; stop(); });
		box.addEventListener('mouseleave', function () { hold = false; start(); });
		box.addEventListener('focusin', function () { hold = true; stop(); });
		box.addEventListener('focusout', function (e) { if (!box.contains(e.relatedTarget)) { hold = false; start(); } });
		document.addEventListener('visibilitychange', start);
		mark();
		start();
	}
	function all() { document.querySelectorAll('.semrs-banners').forEach(init); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', all); } else { all(); }
})();
