/**
 * Public self-fill form: loads the project's fields, crops and compresses the
 * photo in the browser, then sends everything for the institution to approve.
 */
( function () {
	'use strict';

	var __ = window.wp && wp.i18n ? wp.i18n.__ : function ( s ) { return s; };
	var cfg = window.PKC;
	var form = document.getElementById( 'fill-form' );
	var box = document.getElementById( 'fill-fields' );
	var msg = form.querySelector( '.form-msg' );
	var cropper = null;

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ]; } );
	}

	function show( text, ok ) {
		msg.textContent = text;
		msg.className = 'form-msg ' + ( ok ? 'is-success' : 'is-error' );
	}

	fetch( cfg.rest + 'public/fill/' + cfg.token )
		.then( function ( r ) { return r.json().then( function ( d ) { return { ok: r.ok, d: d }; } ); } )
		.then( function ( x ) {
			if ( ! x.ok ) {
				box.innerHTML = '<p class="form-msg is-error">' + esc( x.d.message ) + '</p>';
				form.querySelector( 'button[type=submit]' ).disabled = true;
				return;
			}
			var html = '<div class="field"><label for="f-name">' + esc( __( 'Full name', 'pikacart' ) ) + ' *</label><input id="f-name" name="name" required autocomplete="name"></div>' +
				'<div class="field"><label for="f-id">' + esc( __( 'Roll number / ID number', 'pikacart' ) ) + ' *</label><input id="f-id" name="id_no" required></div>';
			x.d.fields.forEach( function ( f ) {
				if ( [ 'photo', 'name', 'id_no' ].indexOf( f.key ) !== -1 ) {
					return;
				}
				var type = f.key === 'email' ? 'email' : ( f.key === 'mobile' || f.key === 'emergency' ? 'tel' : 'text' );
				var input = f.key === 'address'
					? '<textarea id="f-' + esc( f.key ) + '" name="' + esc( f.key ) + '" rows="2"' + ( f.required ? ' required' : '' ) + '></textarea>'
					: '<input id="f-' + esc( f.key ) + '" type="' + type + '" name="' + esc( f.key ) + '"' + ( f.required ? ' required' : '' ) + ( f.key === 'dob' ? ' placeholder="DD-MM-YYYY"' : '' ) + '>';
				html += '<div class="field"><label for="f-' + esc( f.key ) + '">' + esc( f.label ) + ( f.required ? ' *' : '' ) + '</label>' + input + '</div>';
			} );
			box.innerHTML = html;
		} );

	document.getElementById( 'fill-photo' ).addEventListener( 'change', function ( e ) {
		var file = e.target.files[ 0 ];
		if ( ! file ) {
			return;
		}
		if ( [ 'image/png', 'image/jpeg', 'image/webp' ].indexOf( file.type ) === -1 ) {
			show( __( 'Only JPG, PNG and WebP images are allowed.', 'pikacart' ), false );
			return;
		}
		var img = document.getElementById( 'fill-crop-img' );
		img.src = URL.createObjectURL( file );
		document.getElementById( 'fill-crop' ).hidden = false;
		if ( cropper ) {
			cropper.destroy();
		}
		img.onload = function () {
			cropper = new window.Cropper( img, { aspectRatio: 0.8, viewMode: 1, autoCropArea: 0.9, background: false } );
		};
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var btn = form.querySelector( 'button[type=submit]' );
		var fd = new FormData( form );
		if ( ! form.name.value.trim() || ! form.id_no.value.trim() ) {
			show( __( 'Please fill your name and ID number.', 'pikacart' ), false );
			return;
		}
		if ( ! cropper ) {
			show( __( 'Please add your photo.', 'pikacart' ), false );
			return;
		}
		if ( ! form.consent.checked ) {
			show( __( 'Please agree to the consent notice.', 'pikacart' ), false );
			return;
		}
		btn.disabled = true;
		btn.classList.add( 'is-loading' );
		cropper.getCroppedCanvas( { width: 600, height: 750, imageSmoothingQuality: 'high', fillColor: '#ffffff' } ).toBlob( function ( blob ) {
			fd.append( 'photo', blob, 'photo.jpg' );
			fd.set( 'consent', '1' );
			fetch( cfg.rest + 'public/fill/' + cfg.token, { method: 'POST', body: fd } )
				.then( function ( r ) { return r.json().then( function ( d ) { return { ok: r.ok, d: d }; } ); } )
				.then( function ( x ) {
					show( x.d.message || __( 'Something went wrong. Please try again.', 'pikacart' ), x.ok );
					if ( x.ok ) {
						form.reset();
						cropper.destroy();
						cropper = null;
						document.getElementById( 'fill-crop' ).hidden = true;
						box.innerHTML = '';
						btn.hidden = true;
					}
				} )
				.catch( function () { show( __( 'No internet connection. Please try again.', 'pikacart' ), false ); } )
				.finally( function () {
					btn.disabled = false;
					btn.classList.remove( 'is-loading' );
				} );
		}, 'image/jpeg', 0.88 );
	} );
}() );
