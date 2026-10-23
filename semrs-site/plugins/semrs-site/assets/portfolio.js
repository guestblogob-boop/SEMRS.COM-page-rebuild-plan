/* SEMRS portfolio: filter designs by industry, and browse a whole design in the viewer. */
( function () {
	'use strict';
	var L = window.semrsPf || {};

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

	var dlg, title, tabs, frame, img, stage, newtab, demo, get, devices, opener;
	var widths = { desktop: '100%', tablet: '820px', phone: '390px' };

	function el( tag, cls, text ) {
		var e = document.createElement( tag );
		if ( cls ) {
			e.className = cls;
		}
		if ( text ) {
			e.textContent = text;
		}
		return e;
	}

	function build() {
		dlg = el( 'dialog', 'semrs-pfv' );
		var bar = el( 'div', 'semrs-pfv__bar' );
		title = el( 'strong', 'semrs-pfv__title' );
		tabs = el( 'nav', 'semrs-pfv__pages' );
		tabs.setAttribute( 'aria-label', L.pages || 'Pages' );
		devices = el( 'div', 'semrs-pfv__devices' );
		devices.setAttribute( 'role', 'group' );
		devices.setAttribute( 'aria-label', L.size || 'Preview size' );
		Object.keys( widths ).forEach( function ( d ) {
			var b = el( 'button', '', L[ d ] || d );
			b.type = 'button';
			b.setAttribute( 'data-device', d );
			b.addEventListener( 'click', function () {
				size( d );
			} );
			devices.appendChild( b );
		} );
		var actions = el( 'div', 'semrs-pfv__actions' );
		newtab = el( 'a', 'semrs-pfv__link', L.newtab || 'Open in new tab' );
		newtab.target = '_blank';
		newtab.rel = 'noopener';
		demo = el( 'a', 'semrs-pfv__link', L.demo || 'Live demo' );
		demo.target = '_blank';
		demo.rel = 'noopener';
		get = el( 'a', 'semrs-pfv__get', L.get || 'Get this website' );
		var close = el( 'button', 'semrs-pfv__close' );
		close.type = 'button';
		close.setAttribute( 'aria-label', L.close || 'Close' );
		close.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
		close.addEventListener( 'click', function () {
			dlg.close();
		} );
		actions.append( newtab, demo, get, close );
		bar.append( title, tabs, devices, actions );
		stage = el( 'div', 'semrs-pfv__stage' );
		frame = el( 'iframe', 'semrs-pfv__frame' );
		frame.addEventListener( 'load', sync );
		img = el( 'img', 'semrs-pfv__img' );
		img.alt = '';
		stage.append( frame, img );
		dlg.append( bar, stage, el( 'p', 'semrs-pfv__note', L.note || '' ) );
		dlg.addEventListener( 'close', function () {
			if ( dlg.open ) {
				return; // Reopened before the close event arrived.
			}
			frame.src = 'about:blank';
			document.documentElement.classList.remove( 'semrs-pfv-open' );
			if ( opener ) {
				opener.focus();
			}
		} );
		dlg.addEventListener( 'click', function ( e ) {
			if ( e.target === dlg ) {
				dlg.close();
			}
		} );
		document.body.appendChild( dlg );
	}

	function size( d ) {
		stage.style.setProperty( '--pfv-w', widths[ d ] );
		stage.setAttribute( 'data-device', d );
		devices.querySelectorAll( 'button' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-device' ) === d ? 'true' : 'false' );
		} );
	}

	function go( url ) {
		frame.src = url;
		newtab.href = url;
		mark( url );
	}

	function mark( url ) {
		tabs.querySelectorAll( 'button' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-src' ) === url;
			b.classList.toggle( 'is-active', on );
			if ( on ) {
				b.setAttribute( 'aria-current', 'page' );
			} else {
				b.removeAttribute( 'aria-current' );
			}
		} );
	}

	/* Links inside the design change the page: keep the tabs and new-tab link in step. */
	function sync() {
		var url;
		try {
			url = frame.contentWindow.location.href;
		} catch ( e ) {
			return;
		}
		if ( url && 'about:blank' !== url ) {
			newtab.href = url;
			mark( url );
		}
	}

	function open( data, device ) {
		if ( ! dlg ) {
			build();
		}
		title.textContent = data.t;
		get.href = data.q;
		demo.hidden = ! data.d;
		demo.href = data.d || '#';
		tabs.textContent = '';
		var pages = data.p || [];
		pages.forEach( function ( p ) {
			var b = el( 'button', '', p[ 0 ] );
			b.type = 'button';
			b.setAttribute( 'data-src', p[ 1 ] );
			b.addEventListener( 'click', function () {
				go( p[ 1 ] );
			} );
			tabs.appendChild( b );
		} );
		dlg.classList.toggle( 'is-image', ! pages.length );
		if ( pages.length ) {
			go( pages[ 0 ][ 1 ] );
		} else {
			img.src = data.i;
			newtab.href = data.i;
		}
		size( window.innerWidth < 900 ? 'desktop' : device );
		document.documentElement.classList.add( 'semrs-pfv-open' );
		dlg.showModal();
	}

	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest ? e.target.closest( '.semrs-pf-open' ) : null;
		var box = a && a.closest( '[data-pf]' );
		if ( ! box || 'function' !== typeof HTMLDialogElement || e.metaKey || e.ctrlKey || e.shiftKey || 0 !== e.button ) {
			return;
		}
		var data;
		try {
			data = JSON.parse( box.getAttribute( 'data-pf' ) );
		} catch ( err ) {
			return;
		}
		e.preventDefault();
		opener = a;
		open( data, a.getAttribute( 'data-device' ) || 'desktop' );
	} );
} )();
