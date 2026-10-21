/* SEMRS admin: live search + filter chips for feature/block cards, and
 * instant on/off styling on switches. No dependencies. */
(function () {
	'use strict';
	document.querySelectorAll('.semrs-ui').forEach(function (ui) {
		var search = ui.querySelector('input[type=search]');
		var chips = ui.querySelectorAll('.semrs-chip');
		var cards = ui.querySelectorAll('.semrs-card');
		var empty = ui.querySelector('.semrs-empty-msg');
		var filter = 'all';
		function apply() {
			var q = (search && search.value || '').trim().toLowerCase();
			var shown = 0;
			cards.forEach(function (c) {
				var parts = filter.split(':');
				var okFilter = filter === 'all' || c.getAttribute('data-' + parts[0]) === parts[1];
				var okSearch = !q || (c.getAttribute('data-search') || '').indexOf(q) !== -1;
				c.hidden = !(okFilter && okSearch);
				if (!c.hidden) { shown++; }
			});
			if (empty) { empty.hidden = shown > 0; }
		}
		if (search) { search.addEventListener('input', apply); }
		chips.forEach(function (chip) {
			chip.setAttribute('aria-pressed', chip.classList.contains('is-active') ? 'true' : 'false');
			chip.addEventListener('click', function () {
				chips.forEach(function (c) { c.classList.remove('is-active'); c.setAttribute('aria-pressed', 'false'); });
				chip.classList.add('is-active');
				chip.setAttribute('aria-pressed', 'true');
				filter = chip.getAttribute('data-filter');
				apply();
			});
		});
		ui.querySelectorAll('.semrs-switch input').forEach(function (box) {
			box.addEventListener('change', function () { box.closest('.semrs-card').classList.toggle('is-on', box.checked); });
		});
	});
})();

/* Templates: live preview viewer (page tabs and device sizes). */
( function () {
	var frame = document.querySelector( '.semrs-viewer__frame' );
	if ( ! frame ) { return; }
	document.querySelectorAll( '.semrs-viewer__pages button' ).forEach( function ( b, i, all ) {
		b.addEventListener( 'click', function () {
			frame.src = b.getAttribute( 'data-src' );
			all.forEach( function ( x ) { x.classList.toggle( 'button-primary', x === b ); } );
		} );
	} );
	document.querySelectorAll( '.semrs-viewer__devices button' ).forEach( function ( b, i, all ) {
		b.addEventListener( 'click', function () {
			frame.style.width = b.getAttribute( 'data-width' );
			all.forEach( function ( x ) { x.setAttribute( 'aria-pressed', x === b ? 'true' : 'false' ); } );
		} );
	} );
} )();
	