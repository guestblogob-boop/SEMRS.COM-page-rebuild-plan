/* SEMRS Fancy Heading: typing effect with rotating words. Respects reduced motion. */
(function () {
	'use strict';
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	document.querySelectorAll('.semrs-fancy--typing[data-words]').forEach(function (el) {
		var words;
		try { words = JSON.parse(el.getAttribute('data-words')); } catch (e) { return; }
		if (!words || words.length < 2) { return; }
		var i = 0, text = words[0], deleting = true;
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
