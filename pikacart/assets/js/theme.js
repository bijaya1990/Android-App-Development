/**
 * Mobile menu toggle.
 */
( function () {
	'use strict';
	var btn = document.querySelector( '.nav-toggle' );
	if ( ! btn ) {
		return;
	}
	btn.addEventListener( 'click', function () {
		var open = document.body.classList.toggle( 'nav-open' );
		btn.setAttribute( 'aria-expanded', String( open ) );
	} );
	document.querySelectorAll( '.site-nav a' ).forEach( function ( a ) {
		a.addEventListener( 'click', function () {
			document.body.classList.remove( 'nav-open' );
			btn.setAttribute( 'aria-expanded', 'false' );
		} );
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			document.body.classList.remove( 'nav-open' );
			btn.setAttribute( 'aria-expanded', 'false' );
		}
	} );
}() );
