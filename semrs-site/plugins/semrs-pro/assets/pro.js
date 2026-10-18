/* SEMRS Pro: sticky header state and reading progress bar. No dependencies. */
(function () {
	'use strict';
	var body = document.body;
	var sticky = body.classList.contains('sp-sticky-header');
	var bar = document.querySelector('.sp-progress span');
	var article = document.querySelector('.single-post .entry-content');
	if (!sticky && !bar) { return; }
	var ticking = false;
	function update() {
		var y = window.pageYOffset;
		if (sticky) { body.classList.toggle('sp-scrolled', y > 40); }
		if (bar && article) {
			var span = Math.max(article.offsetHeight - window.innerHeight, 1);
			bar.style.transform = 'scaleX(' + Math.min(Math.max((y - article.offsetTop) / span, 0), 1) + ')';
		}
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
	}, { passive: true });
	update();
})();
