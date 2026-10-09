/**
 * Public site helpers: the contact form.
 */
( function () {
	'use strict';

	var __ = window.wp && wp.i18n ? wp.i18n.__ : function ( s ) { return s; };
	var cfg = window.PKC || {};

	document.querySelectorAll( '.pkc-contact-form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var btn = form.querySelector( 'button[type="submit"]' );
			var msg = form.querySelector( '.pkc-form-msg' );
			var data = {};
			new FormData( form ).forEach( function ( v, k ) { data[ k ] = v; } );

			msg.className = 'pkc-form-msg';
			msg.textContent = '';
			btn.disabled = true;
			btn.classList.add( 'is-loading' );

			fetch( cfg.rest + 'contact', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( data )
			} )
				.then( function ( res ) {
					return res.json().catch( function () { return {}; } ).then( function ( body ) { return { ok: res.ok, body: body }; } );
				} )
				.then( function ( r ) {
					msg.textContent = r.body.message || ( r.ok ? __( 'Thank you! We will reply soon.', 'pikacart' ) : __( 'Something went wrong. Please try again.', 'pikacart' ) );
					msg.className = 'pkc-form-msg ' + ( r.ok ? 'is-success' : 'is-error' );
					if ( r.ok ) {
						form.querySelectorAll( 'input:not([type=hidden]), textarea' ).forEach( function ( el ) { el.value = ''; } );
					}
				} )
				.catch( function () {
					msg.textContent = __( 'No internet connection. Please try again.', 'pikacart' );
					msg.className = 'pkc-form-msg is-error';
				} )
				.finally( function () {
					btn.disabled = false;
					btn.classList.remove( 'is-loading' );
				} );
		} );
	} );
}() );
