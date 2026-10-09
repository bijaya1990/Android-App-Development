/**
 * Login, register, forgot and reset password forms.
 * Sends the form to the Pikacart REST API and shows clear messages.
 */
( function () {
	'use strict';

	var __ = window.wp && wp.i18n ? wp.i18n.__ : function ( s ) { return s; };
	var cfg = window.PKC || {};

	function setLoading( btn, on ) {
		btn.disabled = on;
		btn.classList.toggle( 'is-loading', on );
	}

	function clearErrors( form ) {
		form.querySelectorAll( '.has-error' ).forEach( function ( el ) { el.classList.remove( 'has-error' ); } );
		form.querySelectorAll( '.field-error' ).forEach( function ( el ) { el.remove(); } );
		var msg = form.querySelector( '.form-msg' );
		msg.textContent = '';
		msg.className = 'form-msg';
	}

	function showMessage( form, text, type ) {
		var msg = form.querySelector( '.form-msg' );
		msg.textContent = text;
		msg.className = 'form-msg is-' + type;
	}

	function markField( form, name, text ) {
		var input = form.querySelector( '[name="' + name + '"]' );
		if ( ! input ) {
			return;
		}
		var field = input.closest( '.field' ) || input.closest( '.check' );
		if ( field ) {
			field.classList.add( 'has-error' );
			if ( text && field.classList.contains( 'field' ) ) {
				var err = document.createElement( 'span' );
				err.className = 'field-error';
				err.textContent = text;
				field.appendChild( err );
			}
		}
		input.focus();
	}

	/* Quick checks in the browser; the server checks everything again. */
	function validate( form ) {
		var ok = true;
		form.querySelectorAll( 'input[required]' ).forEach( function ( input ) {
			if ( ! ok ) {
				return;
			}
			var empty = input.type === 'checkbox' ? ! input.checked : ! input.value.trim();
			if ( empty ) {
				ok = false;
				var text = input.type === 'checkbox'
					? __( 'Please tick this box to continue.', 'pikacart' )
					: __( 'Please fill in this field.', 'pikacart' );
				markField( form, input.name, text );
				showMessage( form, text, 'error' );
			} else if ( input.type === 'email' && ! /^\S+@\S+\.\S+$/.test( input.value.trim() ) ) {
				ok = false;
				markField( form, input.name, __( 'Please enter a valid email address.', 'pikacart' ) );
			} else if ( input.minLength > 0 && input.value.length < input.minLength ) {
				ok = false;
				markField( form, input.name, __( 'Password must be at least 8 characters.', 'pikacart' ) );
			}
		} );
		return ok;
	}

	function submit( e ) {
		e.preventDefault();
		var form = e.currentTarget;
		var btn = form.querySelector( 'button[type="submit"]' );
		clearErrors( form );
		if ( ! validate( form ) ) {
			return;
		}

		var data = {};
		new FormData( form ).forEach( function ( value, key ) { data[ key ] = value; } );
		form.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( c ) { data[ c.name ] = c.checked ? 1 : 0; } );
		if ( cfg.redirect ) {
			data.redirect = cfg.redirect;
		}

		setLoading( btn, true );
		fetch( cfg.rest + form.dataset.endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify( data )
		} )
			.then( function ( res ) {
				return res.json().catch( function () { return {}; } ).then( function ( body ) {
					return { ok: res.ok, body: body };
				} );
			} )
			.then( function ( r ) {
				if ( ! r.ok ) {
					var field = r.body && r.body.data && r.body.data.field;
					if ( field ) {
						markField( form, field, '' );
					}
					showMessage( form, ( r.body && r.body.message ) || __( 'Something went wrong. Please try again.', 'pikacart' ), 'error' );
					setLoading( btn, false );
					return;
				}
				if ( r.body.message ) {
					showMessage( form, r.body.message, 'success' );
				}
				if ( r.body.redirect ) {
					window.location.href = r.body.redirect;
				} else {
					setLoading( btn, false );
					form.reset();
				}
			} )
			.catch( function () {
				showMessage( form, __( 'No internet connection. Please check your network and try again.', 'pikacart' ), 'error' );
				setLoading( btn, false );
			} );
	}

	document.querySelectorAll( '.pkc-form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', submit );
	} );

	document.querySelectorAll( '.pw-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var input = btn.parentNode.querySelector( 'input' );
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			btn.setAttribute( 'aria-label', show ? __( 'Hide password', 'pikacart' ) : __( 'Show password', 'pikacart' ) );
			btn.querySelector( 'use' ).setAttribute( 'href', show ? '#i-eye-off' : '#i-eye' );
		} );
	} );
}() );
