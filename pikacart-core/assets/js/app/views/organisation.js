/**
 * Organisation profile: details, logo/sign/seal, default terms and
 * verification page privacy settings.
 */

import { api } from '../api.js';
import { esc, icon, toast, formData, markError, withLoading } from '../ui.js';
import { uploaderHTML, bindUploaders } from '../uploader.js';

const { __ } = window.wp.i18n;

const VERIFY_LABELS = () => ( {
	photo: __( 'Photo', 'pikacart' ),
	name: __( 'Name', 'pikacart' ),
	id_no: __( 'ID number', 'pikacart' ),
	class: __( 'Class / course', 'pikacart' ),
	designation: __( 'Designation', 'pikacart' ),
	department: __( 'Department', 'pikacart' ),
	validity: __( 'Validity dates', 'pikacart' ),
	blood_group: __( 'Blood group', 'pikacart' ),
	mobile: __( 'Mobile number', 'pikacart' ),
	email: __( 'Email', 'pikacart' ),
	address: __( 'Address', 'pikacart' ),
	dob: __( 'Date of birth', 'pikacart' ),
} );

export default function organisation( el, ctx ) {
	function draw() {
		const org = ctx.me.org;
		const cats = ctx.me.catalog.map( ( c ) => `<option value="${ c.id }" ${ org.category_id === c.id ? 'selected' : '' }>${ esc( c.name ) }</option>` ).join( '' );
		const labels = VERIFY_LABELS();

		el.innerHTML = `<form class="org-form" novalidate>
			<div class="page-grid">
				<div class="page-main">
					<section class="card">
						<div class="card-head"><h3>${ esc( __( 'Organisation details', 'pikacart' ) ) }</h3></div>
						<div class="field"><label for="o-name">${ esc( __( 'Organisation name', 'pikacart' ) ) }</label><input id="o-name" name="name" type="text" value="${ esc( org.name ) }" required></div>
						<div class="field"><label for="o-tagline">${ esc( __( 'Tagline or affiliation', 'pikacart' ) ) }</label><input id="o-tagline" name="tagline" type="text" value="${ esc( org.tagline ) }"></div>
						<div class="field"><label for="o-cat">${ esc( __( 'Main category', 'pikacart' ) ) }</label><select id="o-cat" name="category_id"><option value="0">—</option>${ cats }</select></div>
						<div class="field"><label for="o-address">${ esc( __( 'Address', 'pikacart' ) ) }</label><textarea id="o-address" name="address" rows="2">${ esc( org.address ) }</textarea></div>
						<div class="grid-2">
							<div class="field"><label for="o-phone">${ esc( __( 'Phone', 'pikacart' ) ) }</label><input id="o-phone" name="phone" type="tel" value="${ esc( org.phone ) }"></div>
							<div class="field"><label for="o-email">${ esc( __( 'Email', 'pikacart' ) ) }</label><input id="o-email" name="org_email" type="email" value="${ esc( org.org_email ) }"></div>
						</div>
						<div class="grid-2">
							<div class="field"><label for="o-web">${ esc( __( 'Website', 'pikacart' ) ) }</label><input id="o-web" name="website" type="url" value="${ esc( org.website ) }" placeholder="https://"></div>
							<div class="field"><label for="o-session">${ esc( __( 'Session or academic year', 'pikacart' ) ) }</label><input id="o-session" name="session_year" type="text" value="${ esc( org.session_year ) }" placeholder="2026-27"></div>
						</div>
						<div class="field"><label for="o-event">${ esc( __( 'Event name (optional)', 'pikacart' ) ) }</label><input id="o-event" name="event_name" type="text" value="${ esc( org.event_name ) }"></div>
					</section>

					<section class="card">
						<div class="card-head"><h3>${ esc( __( 'Logo, signature and seal', 'pikacart' ) ) }</h3></div>
						<div class="uploader-grid">${ uploaderHTML( 'logo', org.logo ) }${ uploaderHTML( 'sign', org.sign ) }${ uploaderHTML( 'seal', org.seal ) }</div>
						<div class="grid-2">
							<div class="field"><label for="o-sname">${ esc( __( 'Signatory name', 'pikacart' ) ) }</label><input id="o-sname" name="signatory_name" type="text" value="${ esc( org.signatory_name ) }"></div>
							<div class="field"><label for="o-stitle">${ esc( __( 'Signatory title', 'pikacart' ) ) }</label><input id="o-stitle" name="signatory_title" type="text" value="${ esc( org.signatory_title ) }" placeholder="${ esc( __( 'Principal', 'pikacart' ) ) }"></div>
						</div>
					</section>

					<section class="card">
						<div class="card-head"><h3>${ esc( __( 'Default terms on the back of cards', 'pikacart' ) ) }</h3></div>
						<div class="field"><label for="o-terms" class="sr-only">${ esc( __( 'Terms and conditions', 'pikacart' ) ) }</label><textarea id="o-terms" name="terms" rows="4" placeholder="${ esc( __( 'Leave empty to use the standard text for your category.', 'pikacart' ) ) }">${ esc( org.terms ) }</textarea></div>
					</section>
				</div>

				<aside class="page-side">
					<section class="card">
						<div class="card-head"><h3>${ icon( 'shield' ) } ${ esc( __( 'Verification page privacy', 'pikacart' ) ) }</h3></div>
						<p class="muted small">${ esc( __( 'When someone scans a card\'s QR code, they see only the details you tick here.', 'pikacart' ) ) }</p>
						<div class="toggle-list">
							${ Object.keys( labels ).map( ( k ) => `<label class="toggle"><input type="checkbox" name="verify_fields[${ k }]" ${ org.verify_fields[ k ] ? 'checked' : '' }><span class="toggle-ui"></span><span>${ esc( labels[ k ] ) }</span></label>` ).join( '' ) }
						</div>
					</section>
				</aside>
			</div>
			<div class="sticky-save"><button type="submit" class="btn btn-primary">${ icon( 'check' ) }${ esc( __( 'Save changes', 'pikacart' ) ) }</button></div>
		</form>`;

		const form = el.querySelector( 'form' );
		form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			const data = formData( form );
			data.category_id = Number( data.category_id || 0 );
			data.verify_fields = {};
			Object.keys( labels ).forEach( ( k ) => {
				data.verify_fields[ k ] = form.querySelector( `[name="verify_fields[${ k }]"]` ).checked;
				delete data[ `verify_fields[${ k }]` ];
			} );
			withLoading( form.querySelector( '[type=submit]' ), async () => {
				try {
					const res = await api( 'org', { method: 'POST', body: data } );
					ctx.setMe( res.me );
					toast( res.message );
				} catch ( err ) {
					markError( form, err );
					toast( err.message, 'error' );
				}
			} );
		} );

		bindUploaders( el, window.PKC.uploadMb, ( me ) => {
			// Keep anything typed but not saved yet.
			const typed = {};
			form.querySelectorAll( 'input[type=text], input[type=email], input[type=tel], input[type=url], textarea, select' ).forEach( ( i ) => {
				typed[ i.name ] = i.value;
			} );
			ctx.setMe( me );
			draw();
			const f2 = el.querySelector( 'form' );
			Object.keys( typed ).forEach( ( k ) => {
				const input = f2.querySelector( `[name="${ k }"]` );
				if ( input ) {
					input.value = typed[ k ];
				}
			} );
		} );
	}

	draw();
}
