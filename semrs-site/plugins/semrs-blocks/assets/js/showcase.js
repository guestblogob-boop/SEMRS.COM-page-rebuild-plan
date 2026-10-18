/* SEMRS Website Showcase: Desktop / Mobile preview switch per card. */
(function () {
	'use strict';
	document.querySelectorAll('.semrs-show').forEach(function (card) {
		var desk = card.querySelector('.semrs-show__desk');
		var phone = card.querySelector('.semrs-show__phone');
		var btns = card.querySelectorAll('.semrs-show__switch button');
		if (!phone || !btns.length) { return; }
		btns.forEach(function (b) {
			b.addEventListener('click', function () {
				var mobile = b.getAttribute('data-view') === 'mobile';
				phone.hidden = !mobile;
				desk.hidden = mobile;
				btns.forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
			});
		});
	});
})();
