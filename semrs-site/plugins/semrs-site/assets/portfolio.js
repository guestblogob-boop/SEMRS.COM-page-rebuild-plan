/* SEMRS portfolio: filter designs by industry. */
( function () {
	'use strict';
	document.querySelectorAll( '.semrs-pf' ).forEach( function ( pf ) {
		var buttons = pf.querySelectorAll( '.semrs-pf__filters button' );
		buttons.forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var cat = b.getAttribute( 'data-cat' );
				buttons.forEach( function ( x ) {
					x.classList.toggle( 'is-active', x === b );
					x.setAttribute( 'aria-pressed', x === b ? 'true' : 'false' );
				} );
				pf.querySelectorAll( '.semrs-pf__card' ).forEach( function ( card ) {
					card.hidden = !! cat && card.getAttribute( 'data-cat' ) !== cat;
				} );
			} );
		} );
	} );
} )();
