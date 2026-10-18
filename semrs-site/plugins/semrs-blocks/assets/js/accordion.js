/* SEMRS Accordion "only one open at a time". Items are native <details>,
 * so they open and close without this script. */
(function () {
	'use strict';
	document.querySelectorAll('.semrs-acc[data-single]').forEach(function (acc) {
		var items = [].slice.call(acc.querySelectorAll('details.semrs-acc__item'));
		items.forEach(function (d) {
			d.addEventListener('toggle', function () {
				if (d.open) { items.forEach(function (o) { if (o !== d && o.open && o.closest('.semrs-acc') === acc) { o.open = false; } }); }
			});
		});
	});
})();
