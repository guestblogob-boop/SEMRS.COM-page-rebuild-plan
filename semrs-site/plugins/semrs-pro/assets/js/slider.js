/* SEMRS Pro Slider: native scroll-snap track with arrows, dots, keyboard,
 * swipe (native) and autoplay that pauses on hover/focus and reduced motion. */
(function () {
	'use strict';
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	document.querySelectorAll('.semrs-slider').forEach(function (box) {
		var track = box.querySelector('.semrs-slider__track');
		var slides = [].slice.call(track.children);
		var prev = box.querySelector('.semrs-slider__prev');
		var next = box.querySelector('.semrs-slider__next');
		var dotsBox = box.querySelector('.semrs-slider__dots');
		var loop = box.getAttribute('data-loop') === '1';
		var dots = [];

		function perView() { return Math.max(1, Math.round(track.clientWidth / slides[0].getBoundingClientRect().width)); }
		function pages() { return Math.max(1, slides.length - perView() + 1); }
		function current() { return Math.round(track.scrollLeft / (slides[0].getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || 0))); }
		function go(i) {
			var n = pages();
			if (i < 0) { i = loop ? n - 1 : 0; }
			if (i >= n) { i = loop ? 0 : n - 1; }
			track.scrollTo({ left: slides[i].offsetLeft - track.offsetLeft, behavior: reduce ? 'auto' : 'smooth' });
		}
		function update() {
			var i = current(), n = pages();
			dots.forEach(function (d, k) { d.setAttribute('aria-selected', k === i ? 'true' : 'false'); d.tabIndex = k === i ? 0 : -1; d.hidden = k >= n; });
			if (!loop) { if (prev) { prev.disabled = i <= 0; } if (next) { next.disabled = i >= n - 1; } }
		}

		if (dotsBox) {
			slides.forEach(function (s, k) {
				var d = document.createElement('button');
				d.type = 'button';
				d.setAttribute('role', 'tab');
				d.setAttribute('aria-label', 'Slide ' + (k + 1));
				d.addEventListener('click', function () { go(k); });
				dotsBox.appendChild(d);
				dots.push(d);
			});
		}
		if (prev) { prev.addEventListener('click', function () { go(current() - 1); }); }
		if (next) { next.addEventListener('click', function () { go(current() + 1); }); }
		box.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { e.preventDefault(); go(current() - 1); }
			if (e.key === 'ArrowRight') { e.preventDefault(); go(current() + 1); }
		});
		var t;
		track.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(update, 80); }, { passive: true });
		window.addEventListener('resize', update);
		update();

		if (box.getAttribute('data-autoplay') === '1' && !reduce && slides.length > 1) {
			var ms = parseInt(box.getAttribute('data-interval'), 10) || 5000, paused = false, timer;
			var stop = function () { paused = true; }, start = function () { paused = false; };
			box.addEventListener('mouseenter', stop);
			box.addEventListener('mouseleave', start);
			box.addEventListener('focusin', stop);
			box.addEventListener('focusout', start);
			document.addEventListener('visibilitychange', function () { paused = document.hidden; });
			timer = setInterval(function () {
				if (paused) { return; }
				var i = current();
				if (!loop && i >= pages() - 1) { clearInterval(timer); return; }
				go(i + 1);
			}, ms);
		}
	});
})();
