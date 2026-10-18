/* SEMRS header/footer builder control: drag and drop between zones, plus
 * keyboard-friendly "+" menus and "×" buttons. Writes JSON to the setting. */
(function () {
	'use strict';
	var t = window.SEMRS_BUILDER_I18N || {};
	var ZONES = ['left', 'center', 'right'];

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (text) { n.textContent = text; }
		return n;
	}

	function init(root) {
		var input = root.querySelector('.semrs-builder-value');
		var ui = root.querySelector('.semrs-builder-ui');
		var rows = JSON.parse(root.getAttribute('data-rows'));
		var elements = JSON.parse(root.getAttribute('data-elements'));
		var data;
		try { data = JSON.parse(input.value) || {}; } catch (e) { data = {}; }
		Object.keys(rows).forEach(function (r) {
			data[r] = data[r] || {};
			ZONES.forEach(function (z) { data[r][z] = (data[r][z] || []).filter(function (id) { return elements[id]; }); });
		});
		var dragging = null;

		function used() {
			var u = {};
			Object.keys(rows).forEach(function (r) { ZONES.forEach(function (z) { data[r][z].forEach(function (id) { u[id] = true; }); }); });
			return u;
		}
		function save() {
			input.value = JSON.stringify(data);
			input.dispatchEvent(new Event('change', { bubbles: true }));
			render();
		}
		function removeEverywhere(id) {
			Object.keys(rows).forEach(function (r) { ZONES.forEach(function (z) { data[r][z] = data[r][z].filter(function (x) { return x !== id; }); }); });
		}
		function place(id, row, zone, index) {
			removeEverywhere(id);
			var list = data[row][zone];
			list.splice(index == null ? list.length : Math.min(index, list.length), 0, id);
			save();
		}

		function render() {
			ui.innerHTML = '';
			var u = used();
			Object.keys(rows).forEach(function (r) {
				var rowBox = el('div', 'semrs-b-row');
				rowBox.appendChild(el('div', 'semrs-b-row-title', rows[r]));
				var zonesBox = el('div', 'semrs-b-zones');
				ZONES.forEach(function (z) {
					var zone = el('div', 'semrs-b-zone');
					zone.setAttribute('aria-label', rows[r] + ' – ' + (t[z] || z));
					zone.appendChild(el('span', 'semrs-b-zone-label', t[z] || z));
					data[r][z].forEach(function (id, i) {
						var chip = el('div', 'semrs-b-chip');
						chip.draggable = true;
						chip.appendChild(el('span', 'semrs-b-chip-label', elements[id]));
						var x = el('button', 'semrs-b-x', '×');
						x.type = 'button';
						x.setAttribute('aria-label', (t.remove || 'Remove') + ': ' + elements[id]);
						x.addEventListener('click', function () { removeEverywhere(id); save(); });
						chip.appendChild(x);
						chip.addEventListener('dragstart', function (e) { dragging = id; chip.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', id); });
						chip.addEventListener('dragend', function () { dragging = null; chip.classList.remove('is-dragging'); });
						chip.addEventListener('dragover', function (e) { e.preventDefault(); e.stopPropagation(); });
						chip.addEventListener('drop', function (e) { e.preventDefault(); e.stopPropagation(); if (dragging && dragging !== id) { place(dragging, r, z, data[r][z].indexOf(id)); } });
						zone.appendChild(chip);
					});
					zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('is-over'); });
					zone.addEventListener('dragleave', function () { zone.classList.remove('is-over'); });
					zone.addEventListener('drop', function (e) { e.preventDefault(); zone.classList.remove('is-over'); if (dragging) { place(dragging, r, z); } });

					var free = Object.keys(elements).filter(function (id) { return !u[id]; });
					var add = el('select', 'semrs-b-add');
					add.setAttribute('aria-label', (t.add || 'Add element') + ' – ' + rows[r] + ' ' + (t[z] || z));
					var first = el('option', '', free.length ? '+ ' + (t.add || 'Add element') : (t.none || 'All elements are in use'));
					first.value = '';
					add.appendChild(first);
					free.forEach(function (id) { var o = el('option', '', elements[id]); o.value = id; add.appendChild(o); });
					add.disabled = !free.length;
					add.addEventListener('change', function () { if (add.value) { place(add.value, r, z); } });
					zone.appendChild(add);
					zonesBox.appendChild(zone);
				});
				rowBox.appendChild(zonesBox);
				ui.appendChild(rowBox);
			});
		}
		render();
	}

	function boot() { document.querySelectorAll('.semrs-builder').forEach(function (b) { if (!b.dataset.ready) { b.dataset.ready = '1'; init(b); } }); }
	if (window.wp && wp.customize) {
		wp.customize.bind('ready', boot);
		wp.customize.control.bind('add', function () { setTimeout(boot, 0); });
	}
	document.addEventListener('DOMContentLoaded', boot);
})();
