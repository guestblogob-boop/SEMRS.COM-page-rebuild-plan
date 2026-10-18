/* SEMRS Pro dark mode switch. The mode is set before paint by an inline
 * script in <head>; this only handles clicks and remembers the choice. */
(function () {
	'use strict';
	var d = document.documentElement;
	var btns = document.querySelectorAll('.sp-dark-toggle');
	function sync() {
		var dark = d.getAttribute('data-theme') === 'dark';
		btns.forEach(function (b) { b.setAttribute('aria-pressed', dark ? 'true' : 'false'); });
	}
	btns.forEach(function (b) {
		b.addEventListener('click', function () {
			var dark = d.getAttribute('data-theme') !== 'dark';
			if (dark) { d.setAttribute('data-theme', 'dark'); } else { d.removeAttribute('data-theme'); }
			try { localStorage.setItem('semrs-theme', dark ? 'dark' : 'light'); } catch (e) {}
			sync();
		});
	});
	sync();
})();
