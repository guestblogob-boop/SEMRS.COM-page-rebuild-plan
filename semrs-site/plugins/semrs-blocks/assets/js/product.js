/* SEMRS Blocks: buy box engine (variants, quantity, bulk discounts, bundles, live price, checkout link). */
(function () {
	'use strict';

	function engine(box) {
		var cfg;
		try { cfg = JSON.parse(box.getAttribute('data-semrs-buy')); } catch (e) { return; }
		var mk = function (d) { try { return new Intl.NumberFormat(cfg.locale || undefined, { style: 'currency', currency: cfg.currency || 'USD', minimumFractionDigits: d, maximumFractionDigits: d }); } catch (e) { return { format: function (n) { return n.toFixed(d); } }; } };
		var whole = mk(0), cents = mk(2);
		// Whole amounts without decimals ($24), others with two ($16.20).
		var money = function (n) { n = Math.round(n * 100) / 100; return (Math.abs(n - Math.round(n)) < 0.005 ? whole : cents).format(n); };
		var $ = function (s) { return box.querySelector(s); };
		var qtyInput = $('[data-b-qty]');
		var groupsByKey = {};
		var texts = [].slice.call(box.querySelectorAll('[data-text-group]'));
		(cfg.groups || []).forEach(function (g) { if (g.type !== 'text') { groupsByKey[g.key] = g; } });
		var main = (box.closest('.semrs-product, .semrs-product-page, main') || document).querySelector('[data-b-main], .semrs-gallery__img');

		// Variant deep links: ?color=navy&size=m preselects values.
		var params = new URLSearchParams(location.search);
		Object.keys(groupsByKey).forEach(function (k) {
			var want = params.get(k);
			if (!want) { return; }
			var el = box.querySelector('[data-group="' + k + '"][value="' + CSS.escape(want) + '"]:not(:disabled)') ||
				box.querySelector('select[data-group="' + k + '"]');
			if (el && el.tagName === 'SELECT') { if ([].some.call(el.options, function (o) { return o.value === want && !o.disabled; })) { el.value = want; } }
			else if (el) { el.checked = true; }
		});

		function picked(key) {
			var g = groupsByKey[key];
			var el = box.querySelector('select[data-group="' + key + '"]') || box.querySelector('input[data-group="' + key + '"]:checked');
			var val = el ? el.value : null;
			for (var i = 0; i < g.values.length; i++) { if (g.values[i].value === val) { return g.values[i]; } }
			return null;
		}

		function update(fromUser) {
			var unit = +cfg.price || 0, compare = +cfg.compare || 0, url = cfg.url || '', choice = {}, image = null;
			Object.keys(groupsByKey).forEach(function (k) {
				var v = picked(k);
				if (!v) { return; }
				choice[k] = v.value;
				if (v.price !== null && v.price !== undefined) { unit = +v.price; compare = +(v.compare || 0); }
				if (v.url) { url = v.url; }
				if (v.image_src) { image = v; }
				var chosen = box.querySelector('[name$="-' + k + '"]') && box.querySelector('[name$="-' + k + '"]').closest('fieldset');
				var label = chosen && chosen.querySelector('[data-b-chosen]');
				if (label) { label.textContent = v.label; }
			});
			Object.keys(groupsByKey).forEach(function (k) { var v = picked(k); if (v && v.add) { unit += +v.add; if (compare) { compare += +v.add; } } });

			var qty = 1;
			if (qtyInput) {
				qty = Math.max(1, Math.min(cfg.max || 99, parseInt(qtyInput.value, 10) || 1));
				if (fromUser !== 'typing') { qtyInput.value = qty; }
			}
			var off = 0;
			(cfg.bulk || []).forEach(function (b) { if (qty >= b.min) { off = Math.max(off, +b.off); } });
			box.querySelectorAll('.semrs-bulk tr[data-min]').forEach(function (tr) {
				var rows = (cfg.bulk || []).filter(function (b) { return qty >= b.min; });
				tr.classList.toggle('is-active', rows.length > 0 && +tr.dataset.min === rows[rows.length - 1].min);
			});
			var each = unit * (1 - off / 100);
			var bundles = 0, bundleSave = 0, picks = [];
			box.querySelectorAll('[data-b-bundle]:checked').forEach(function (c) {
				(cfg.bundles || []).forEach(function (b) {
					if (b.value === c.value) { bundles += +b.price; bundleSave += b.compare > b.price ? b.compare - b.price : 0; picks.push(b.value); }
				});
			});
			var total = each * qty + bundles;
			var save = (compare > unit ? (compare - unit) * qty : 0) + (unit - each) * qty + bundleSave;

			$('[data-b-price]').textContent = qty > 1 || off ? cfg.i18n.each.replace('%s', money(each)) : money(each);
			var was = $('[data-b-compare]');
			if (was) { was.hidden = !(compare > unit) && !off; was.textContent = money(compare > unit ? compare : unit); }
			var saveEl = $('[data-b-save]');
			if (saveEl) { saveEl.hidden = save <= 0.004; saveEl.textContent = cfg.i18n.save.replace('%s', money(save)); }
			var totalRow = $('[data-b-total-row]');
			if (totalRow) { totalRow.hidden = qty < 2 && !bundles; $('[data-b-total]').textContent = money(total); }

			var btn = $('[data-b-buy]');
			if (btn && url) {
				try {
					var u = new URL(url, location.href);
					Object.keys(choice).forEach(function (k) { u.searchParams.set(k, choice[k]); });
					if (qtyInput) { u.searchParams.set('quantity', qty); }
					if (picks.length) { u.searchParams.set('bundle', picks.join(',')); } else { u.searchParams.delete('bundle'); }
					texts.forEach(function (t) { if (t.value.trim()) { u.searchParams.set(t.dataset.textGroup, t.value.trim()); } else { u.searchParams.delete(t.dataset.textGroup); } });
					btn.href = u.toString();
				} catch (e) { btn.href = url; }
				btn.textContent = btn.dataset.label;
				btn.rel = 'nofollow';
				btn.classList.remove('is-soon');
			} else if (btn) {
				btn.href = btn.dataset.fallback || '#';
				btn.textContent = btn.dataset.fallbackText;
				btn.removeAttribute('rel');
				btn.classList.add('is-soon');
			}
			if (image && main && fromUser) {
				main.src = image.image_src;
				if (image.image_srcset) { main.srcset = image.image_srcset; }
				main.hidden = false;
			}
			if (fromUser && history.replaceState && Object.keys(choice).length) {
				var here = new URL(location.href);
				Object.keys(choice).forEach(function (k) { here.searchParams.set(k, choice[k]); });
				history.replaceState(null, '', here.toString());
			}
		}

		box.addEventListener('change', function () { update(true); });
		texts.forEach(function (t) {
			var counter = t.parentNode.querySelector('.semrs-count');
			t.addEventListener('input', function () {
				if (counter) { counter.textContent = t.value.length + ' / ' + t.maxLength; }
				update('typing');
			});
		});
		var buy = box.querySelector('[data-b-buy]');
		if (buy) {
			buy.addEventListener('click', function (ev) {
				var missing = texts.filter(function (t) { return t.required && !t.value.trim(); });
				var msg = box.querySelector('[data-b-msg]');
				if (missing.length && !buy.classList.contains('is-soon')) {
					ev.preventDefault();
					if (msg) { msg.hidden = false; }
					missing[0].focus();
				} else if (msg) { msg.hidden = true; }
			});
		}
		if (qtyInput) {
			qtyInput.addEventListener('input', function () { update('typing'); });
			box.querySelectorAll('[data-q]').forEach(function (b) {
				b.addEventListener('click', function () { qtyInput.value = (parseInt(qtyInput.value, 10) || 1) + (+b.dataset.q); update(true); });
			});
		}
		update(false);
	}

	document.querySelectorAll('[data-semrs-buy]').forEach(engine);
})();
