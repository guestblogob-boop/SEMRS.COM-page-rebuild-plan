/* SEMRS Fancy Heading: typing effect with rotating words. Respects reduced motion. */
(function () {
	'use strict';
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	document.querySelectorAll('.semrs-fancy--typing[data-words]').forEach(function (el) {
		var words;
		try { words = JSON.parse(el.getAttribute('data-words')); } catch (e) { return; }
		if (!words || words.length < 2) { return; }
		var i = Math.max(0, words.indexOf(el.textContent)), text = words[i], deleting = true;
		// Reserve the height of the longest word so the heading (and anything centred beside it)
		// never jumps when a word wraps onto another line (Cumulative Layout Shift).
		var box = el.closest('.semrs-fancy-heading') || el.parentElement;
		function reserve() {
			var shown = el.textContent, max = 0;
			box.style.minHeight = '';
			words.forEach(function (w) { el.textContent = w; max = Math.max(max, box.offsetHeight); });
			el.textContent = shown;
			box.style.minHeight = max + 'px';
		}
		reserve();
		var resizing;
		window.addEventListener('resize', function () { clearTimeout(resizing); resizing = setTimeout(reserve, 150); });
		if (reduce) {
			setInterval(function () { i = (i + 1) % words.length; el.textContent = words[i]; }, 2500);
			return;
		}
		function tick() {
			var word = words[i];
			if (deleting) {
				text = word.slice(0, text.length - 1);
				el.textContent = text;
				if (!text) { deleting = false; i = (i + 1) % words.length; return setTimeout(tick, 250); }
				return setTimeout(tick, 45);
			}
			word = words[i];
			text = word.slice(0, text.length + 1);
			el.textContent = text;
			if (text === word) { deleting = true; return setTimeout(tick, 1800); }
			setTimeout(tick, 85);
		}
		setTimeout(tick, 1800);
	});
})();
