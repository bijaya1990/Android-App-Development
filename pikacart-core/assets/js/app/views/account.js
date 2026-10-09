/**
 * Account: name and mobile, change password, delete account request.
 */

import { api, setNonce } from '../api.js';
import { esc, icon, toast, formData, markError, withLoading, confirmDialog } from '../ui.js';

const { __ } = window.wp.i18n;

export default function account( el, ctx ) {
	const me = ctx.me;
	el.innerHTML = `<div class="narrow">
		<section class="card">
			<div class="card-head"><h3>${ esc( __( 'Your profile', 'pikacart' ) ) }</h3></div>
			<form class="profile-form" novalidate>
				<div class="field"><label for="a-name">${ esc( __( 'Contact person', 'pikacart' ) ) }</label><input id="a-name" name="contact_name" type="text" value="${ esc( me.org.contact_name ) }" autocomplete="name" required></div>
				<div class="grid-2">
					<div class="field"><label for="a-email">${ esc( __( 'Login email', 'pikacart' ) ) }</label><input id="a-email" type="email" value="${ esc( me.user.email ) }" disabled>
						<small class="hint">${ me.user.verified ? `${ icon( 'check-circle' ) } ${ esc( __( 'Verified', 'pikacart' ) ) }` : esc( __( 'Not verified yet', 'pikacart' ) ) }</small></div>
					<div class="field"><label for="a-mobile">${ esc( __( 'Mobile number', 'pikacart' ) ) }</label><input id="a-mobile" name="mobile" type="tel" value="${ esc( me.org.mobile ) }" autocomplete="tel" required></div>
				</div>
				<p class="hint">${ esc( __( 'To change your login email, please contact support.', 'pikacart' ) ) }</p>
				<button type="submit" class="btn btn-primary">${ esc( __( 'Save profile', 'pikacart' ) ) }</button>
			</form>
		</section>

		<section class="card">
			<div class="card-head"><h3>${ icon( 'lock' ) } ${ esc( __( 'Change password', 'pikacart' ) ) }</h3></div>
			<form class="password-form" novalidate>
				<div class="field"><label for="a-cur">${ esc( __( 'Current password', 'pikacart' ) ) }</label><input id="a-cur" name="current_password" type="password" autocomplete="current-password" required></div>
				<div class="field"><label for="a-new">${ esc( __( 'New password', 'pikacart' ) ) }</label><input id="a-new" name="new_password" type="password" autocomplete="new-password" minlength="8" required><small class="hint">${ esc( __( 'At least 8 characters.', 'pikacart' ) ) }</small></div>
				<button type="submit" class="btn btn-primary">${ esc( __( 'Change password', 'pikacart' ) ) }</button>
			</form>
		</section>

		<section class="card danger-zone">
			<div class="card-head"><h3>${ esc( __( 'Delete account', 'pikacart' ) ) }</h3></div>
			<p class="muted">${ esc( __( 'Ask us to permanently delete your organisation, its cards, people and photos. This cannot be undone.', 'pikacart' ) ) }</p>
			<button type="button" class="btn btn-danger" data-delete>${ icon( 'trash' ) }${ esc( __( 'Request account deletion', 'pikacart' ) ) }</button>
		</section>
	</div>`;

	const pf = el.querySelector( '.profile-form' );
	pf.addEventListener( 'submit', ( e ) => {
		e.preventDefault();
		withLoading( pf.querySelector( '[type=submit]' ), async () => {
			try {
				const res = await api( 'me/profile', { method: 'POST', body: formData( pf ) } );
				ctx.setMe( res.me );
				toast( res.message );
			} catch ( err ) {
				markError( pf, err );
				toast( err.message, 'error' );
			}
		} );
	} );

	const pw = el.querySelector( '.password-form' );
	pw.addEventListener( 'submit', ( e ) => {
		e.preventDefault();
		const data = formData( pw );
		if ( ( data.new_password || '' ).length < 8 ) {
			toast( __( 'New password must be at least 8 characters.', 'pikacart' ), 'error' );
			return;
		}
		withLoading( pw.querySelector( '[type=submit]' ), async () => {
			try {
				const res = await api( 'me/password', { method: 'POST', body: data } );
				if ( res.nonce ) {
					setNonce( res.nonce );
				}
				pw.reset();
				toast( res.message );
			} catch ( err ) {
				markError( pw, err );
				toast( err.message, 'error' );
			}
		} );
	} );

	el.querySelector( '[data-delete]' ).addEventListener( 'click', async ( e ) => {
		const btn = e.currentTarget;
		const wrap = await confirmDialog( {
			title: __( 'Request account deletion?', 'pikacart' ),
			message: __( 'We will delete your organisation, all cards, people and photos, and confirm by email. Payment records are kept as tax law requires.', 'pikacart' ),
			body: `<div class="field"><label for="del-reason">${ esc( __( 'Reason (optional)', 'pikacart' ) ) }</label><textarea id="del-reason" rows="2"></textarea></div>`,
			confirm: __( 'Send deletion request', 'pikacart' ),
			danger: true,
		} );
		if ( ! wrap ) {
			return;
		}
		const reason = wrap.querySelector( '#del-reason' ).value;
		withLoading( btn, async () => {
			try {
				const res = await api( 'me/delete-request', { method: 'POST', body: { reason } } );
				toast( res.message );
			} catch ( err ) {
				toast( err.message, 'error' );
			}
		} );
	} );
}
