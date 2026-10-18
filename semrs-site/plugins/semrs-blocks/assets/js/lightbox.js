/* SEMRS Gallery lightbox: keyboard (←, →, Esc), swipe, focus kept inside,
 * focus returned to the clicked image on close. Links still work without JS. */
(function () {
	'use strict';
	var box, img, cap, list = [], at = 0, opener = null;
	function build() {
		box = document.createElement('div');
		box.className = 'semrs-lightbox';
		box.hidden = true;
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-label', 'Image viewer');
		box.innerHTML = '<img alt=""><p></p><button type="button" class="lb-prev" aria-label="Previous image">‹</button><button type="button" class="lb-next" aria-label="Next image">›</button><button type="button" class="lb-close" aria-label="Close">×</button>';
		document.body.appendChild(box);
		img = box.querySelector('img');
		cap = box.querySelector('p');
		box.querySelector('.lb-close').addEventListener('click', close);
		box.querySelector('.lb-prev').addEventListener('click', function () { show(at - 1); });
		box.querySelector('.lb-next').addEventListener('click', function () { show(at + 1); });
		box.addEventListener('click', function (e) { if (e.target === box) { close(); } });
		box.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowLeft') { show(at - 1); }
			if (e.key === 'ArrowRight') { show(at + 1); }
			if (e.key === 'Tab') {
				var f = [].slice.call(box.querySelectorAll('button')).filter(function (b) { return !b.hidden; });
				var i = f.indexOf(document.activeElement);
				if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); } else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
			}
		});
		var x0 = null;
		box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
		box.addEventListener('touchend', function (e) { if (x0 === null) { return; } var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) { show(at + (dx < 0 ? 1 : -1)); } x0 = null; });
	}
	function show(i) {
		at = (i + list.length) % list.length;
		var a = list[at];
		img.src = a.href;
		img.alt = (a.querySelector('img') || {}).alt || '';
		cap.textContent = a.getAttribute('data-caption') || '';
		var many = list.length > 1;
		box.querySelector('.lb-prev').hidden = !many;
		box.querySelector('.lb-next').hidden = !many;
	}
	function close() {
		box.hidden = true;
		document.documentElement.style.overflow = '';
		if (opener) { opener.focus(); }
	}
	document.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('.semrs-gallery__link');
		if (!a || e.ctrlKey || e.metaKey || e.shiftKey) { return; }
		e.preventDefault();
		if (!box) { build(); }
		list = [].slice.call(a.closest('.semrs-gallery').querySelectorAll('.semrs-gallery__link'));
		opener = a;
		show(list.indexOf(a));
		box.hidden = false;
		document.documentElement.style.overflow = 'hidden';
		box.querySelector('.lb-close').focus();
	});
})();
