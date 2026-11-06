/* SEMRS admin folding: long tables show 10 rows with a "Show all" button, and
 * settings screens with many sections get fold buttons on each heading plus
 * Fold all / Unfold all. Folded settings still save (they are only hidden).
 * The choice is remembered per screen in this browser. No dependencies. */
(function () {
	'use strict';
	var wrap = document.getElementById('wpbody-content');
	if (!wrap) { return; }
	var L = window.semrsFoldText || {};
	var key = 'semrs-fold:' + location.search;
	var saved = {};
	try { saved = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) {}
	function remember() { try { localStorage.setItem(key, JSON.stringify(saved)); } catch (e) {} }

	// 1. Long tables.
	var SHOW = 10;
	[].slice.call(wrap.querySelectorAll('table.widefat, table.wp-list-table, table.striped')).forEach(function (t, ti) {
		if (t.hasAttribute('data-no-fold') || t.closest('.semrs-ui')) { return; }
		var body = t.tBodies[0];
		if (!body) { return; }
		var rows = [].slice.call(body.rows);
		if (rows.length <= SHOW + 2) { return; }
		var id = 't' + ti;
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'button semrs-fold-more';
		btn.setAttribute('aria-expanded', 'false');
		function set(open) {
			rows.forEach(function (r, i) { if (i >= SHOW) { r.hidden = !open; } });
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			btn.textContent = open ? (L.less || 'Show fewer') : (L.all || 'Show all %d rows').replace('%d', rows.length);
		}
		btn.addEventListener('click', function () {
			var open = btn.getAttribute('aria-expanded') !== 'true';
			set(open);
			saved[id] = open; remember();
			if (!open) { t.scrollIntoView({ block: 'nearest' }); }
		});
		set(!!saved[id]);
		var box = t.closest('.table-responsive, .semrs-dt__wrap') || t;
		box.parentNode.insertBefore(btn, box.nextSibling);
	});

	// 2. Settings screens with many sections: fold buttons on h2 headings.
	var heads = [].slice.call(wrap.querySelectorAll('.wrap > h2, .wrap form > h2, .semrs-wrap h2')).filter(function (h) {
		var n = h.nextElementSibling;
		return n && !/^H[12]$/.test(n.tagName) && !h.closest('.semrs-card');
	});
	if (heads.length < 4) { return; }
	var sections = heads.map(function (h, i) {
		var els = [];
		var n = h.nextElementSibling;
		while (n && heads.indexOf(n) === -1 && !n.matches('.submit, p.submit, .semrs-savebar, h1')) {
			els.push(n);
			n = n.nextElementSibling;
		}
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'semrs-fold-head';
		b.innerHTML = '<span class="semrs-fold-ico" aria-hidden="true"></span>';
		b.appendChild(document.createTextNode(h.textContent));
		h.textContent = '';
		h.appendChild(b);
		var s = { h: h, b: b, els: els, id: 's' + i };
		b.addEventListener('click', function () { fold(s, b.getAttribute('aria-expanded') === 'true'); saved[s.id] = b.getAttribute('aria-expanded') !== 'true'; remember(); });
		return s;
	});
	function fold(s, closed) {
		s.els.forEach(function (e) { e.classList.toggle('semrs-folded', closed); });
		s.b.setAttribute('aria-expanded', closed ? 'false' : 'true');
	}
	sections.forEach(function (s) { fold(s, !!saved[s.id]); });
	var bar = document.createElement('p');
	bar.className = 'semrs-fold-bar';
	[['fold', L.foldAll || 'Fold all', true], ['open', L.openAll || 'Unfold all', false]].forEach(function (x) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'button button-small';
		b.textContent = x[1];
		b.addEventListener('click', function () { sections.forEach(function (s) { fold(s, x[2]); saved[s.id] = x[2]; }); remember(); });
		bar.appendChild(b);
	});
	heads[0].parentNode.insertBefore(bar, heads[0]);
})();
