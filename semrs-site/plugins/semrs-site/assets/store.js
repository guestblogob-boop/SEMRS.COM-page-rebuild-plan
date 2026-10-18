/* SEMRS Store: filters + sorting, gallery, plan picker, sticky buy bar. No dependencies. */
(function () {
	'use strict';

	// Collection: search, chips, sort.
	document.querySelectorAll('[data-store]').forEach(function (store) {
		var grid = store.querySelector('[data-store-grid]');
		var search = store.querySelector('[data-store-search]');
		var sort = store.querySelector('[data-store-sort]');
		var count = store.querySelector('[data-store-count]');
		var empty = store.querySelector('[data-store-empty]');
		var chips = store.querySelectorAll('[data-filter]');
		if (!grid || !chips.length) { return; }
		var cards = [].slice.call(grid.children);
		var filter = 'all';
		var label = count ? count.textContent.replace(/^\d+/, '') : '';

		function apply() {
			var q = search ? search.value.trim().toLowerCase() : '';
			var shown = 0;
			cards.forEach(function (c) {
				var ok = true;
				if (filter === 'free') { ok = c.dataset.paid === '0'; }
				else if (filter === 'paid') { ok = c.dataset.paid === '1'; }
				else if (filter.indexOf('cat:') === 0) { ok = (' ' + c.dataset.cats + ' ').indexOf(' ' + filter.slice(4) + ' ') > -1; }
				if (ok && q) { ok = c.dataset.title.indexOf(q) > -1; }
				c.hidden = !ok;
				shown += ok ? 1 : 0;
			});
			if (count) { count.textContent = (shown === 1 ? count.dataset.one : count.dataset.many).replace('%d', shown); }
			if (empty) { empty.hidden = shown > 0; }
		}
		function order() {
			var v = sort ? sort.value : 'order';
			var by = {
				'order': function (a, b) { return a.dataset.order - b.dataset.order; },
				'price-asc': function (a, b) { return a.dataset.price - b.dataset.price || a.dataset.order - b.dataset.order; },
				'price-desc': function (a, b) { return b.dataset.price - a.dataset.price || a.dataset.order - b.dataset.order; },
				'name': function (a, b) { return a.dataset.title.localeCompare(b.dataset.title); },
				'new': function (a, b) { return b.dataset.date - a.dataset.date; }
			}[v];
			cards.slice().sort(by).forEach(function (c) { grid.appendChild(c); });
		}
		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				filter = chip.dataset.filter;
				chips.forEach(function (x) { var on = x === chip; x.classList.toggle('is-active', on); x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
				apply();
			});
		});
		if (search) { search.addEventListener('input', apply); }
		if (sort) { sort.addEventListener('change', order); }
	});

	// Product gallery thumbnails.
	document.querySelectorAll('[data-gallery]').forEach(function (g) {
		var imgs = g.querySelectorAll('[data-slide]');
		var thumbs = g.querySelectorAll('[data-to]');
		thumbs.forEach(function (t) {
			t.addEventListener('click', function () {
				imgs.forEach(function (im) { im.hidden = im.dataset.slide !== t.dataset.to; });
				thumbs.forEach(function (x) { var on = x === t; x.classList.toggle('is-active', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
			});
		});
	});

	// Plan picker updates the price and the Buy button.
	var buybox = document.querySelector('[data-buybox]');
	if (buybox) {
		buybox.querySelectorAll('input[name="semrs-tier"]').forEach(function (r) {
			r.addEventListener('change', function () {
				document.querySelectorAll('[data-price-out]').forEach(function (p) {
					var per = p.querySelector('span');
					p.textContent = r.dataset.price + ' ';
					if (per) { p.appendChild(per); }
				});
				buybox.querySelectorAll('[data-tier-buy]').forEach(function (b) { b.hidden = b.dataset.tierBuy !== r.value; });
			});
		});
	}

	// Sticky buy bar once the buy box scrolls away (phones).
	var bar = document.querySelector('[data-stickybar]');
	if (bar && buybox && 'IntersectionObserver' in window) {
		new IntersectionObserver(function (e) { bar.hidden = e[0].isIntersecting || e[0].boundingClientRect.top > 0; }).observe(buybox);
		var go = bar.querySelector('[data-scroll-buy]');
		if (go) {
			go.addEventListener('click', function (ev) {
				ev.preventDefault();
				buybox.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		}
	}
})();
