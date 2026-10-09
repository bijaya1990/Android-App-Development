/**
 * Short onboarding after registration: category, organisation details,
 * logo, signature and seal. Saved once and reused on every card.
 */

import { api } from '../api.js';
import { esc, icon, toast, formData, markError, withLoading } from '../ui.js';
import { uploaderHTML, bindUploaders } from '../uploader.js';

const { __ } = window.wp.i18n;

const CAT_ICONS = { school: 'school', college: 'college', book: 'book', briefcase: 'briefcase', health: 'health', heart: 'heart', ticket: 'ticket', shield: 'shield', users: 'users', mic: 'mic' };

export default function onboarding( el, ctx ) {
	let step = ctx.me.org.category_id ? 2 : 1;

	const steps = [
		__( 'Category', 'pikacart' ),
		__( 'Details', 'pikacart' ),
		__( 'Logo, sign & seal', 'pikacart' ),
	];

	function stepBar() {
		return `<ol class="stepbar">${ steps.map( ( label, i ) => {
			const n = i + 1;
			const cls = n < step ? 'is-done' : ( n === step ? 'is-current' : '' );
			return `<li class="${ cls }"><button type="button" data-step="${ n }" ${ n > step && ! ctx.me.org.category_id ? 'disabled' : '' }><span class="step-dot">${ n < step ? icon( 'check' ) : n }</span><span class="step-label">${ esc( label ) }</span></button></li>`;
		} ).join( '' ) }</ol>`;
	}

	function draw() {
		const org = ctx.me.org;
		let body = '';

		if ( step === 1 ) {
			body = `<h2>${ esc( __( 'What kind of organisation are you?', 'pikacart' ) ) }</h2>
				<p class="muted">${ esc( __( 'This picks the best designs to start with. You can use every other category later too.', 'pikacart' ) ) }</p>
				<div class="cat-grid">
					${ ctx.me.catalog.map( ( c ) => `<button type="button" class="cat-tile ${ org.category_id === c.id ? 'is-selected' : '' }" data-cat="${ c.id }" aria-pressed="${ org.category_id === c.id }">
						<span class="cat-icon">${ icon( CAT_ICONS[ c.icon ] || 'idcard' ) }</span>
						<strong>${ esc( c.name ) }</strong>
						<small>${ esc( c.subtypes.map( ( s ) => s.name ).join( ' · ' ) ) }</small>
					</button>` ).join( '' ) }
				</div>`;
		} else if ( step === 2 ) {
			body = `<h2>${ esc( __( 'Your organisation details', 'pikacart' ) ) }</h2>
				<p class="muted">${ esc( __( 'These print on every card, so check the spelling carefully.', 'pikacart' ) ) }</p>
				<form class="ob-form" novalidate>
					<div class="field"><label for="ob-name">${ esc( __( 'Organisation name', 'pikacart' ) ) }</label><input id="ob-name" name="name" type="text" value="${ esc( org.name ) }" required></div>
					<div class="field"><label for="ob-tagline">${ esc( __( 'Tagline or affiliation (optional)', 'pikacart' ) ) }</label><input id="ob-tagline" name="tagline" type="text" value="${ esc( org.tagline ) }" placeholder="${ esc( __( 'e.g. Affiliated to CBSE, New Delhi', 'pikacart' ) ) }"></div>
					<div class="field"><label for="ob-address">${ esc( __( 'Address', 'pikacart' ) ) }</label><textarea id="ob-address" name="address" rows="2">${ esc( org.address ) }</textarea></div>
					<div class="grid-2">
						<div class="field"><label for="ob-phone">${ esc( __( 'Phone', 'pikacart' ) ) }</label><input id="ob-phone" name="phone" type="tel" value="${ esc( org.phone ) }"></div>
						<div class="field"><label for="ob-email">${ esc( __( 'Email', 'pikacart' ) ) }</label><input id="ob-email" name="org_email" type="email" value="${ esc( org.org_email ) }"></div>
					</div>
					<div class="grid-2">
						<div class="field"><label for="ob-web">${ esc( __( 'Website (optional)', 'pikacart' ) ) }</label><input id="ob-web" name="website" type="url" value="${ esc( org.website ) }" placeholder="https://"></div>
						<div class="field"><label for="ob-session">${ esc( __( 'Session or academic year', 'pikacart' ) ) }</label><input id="ob-session" name="session_year" type="text" value="${ esc( org.session_year ) }" placeholder="2026-27"></div>
					</div>
				</form>`;
		} else {
			body = `<h2>${ esc( __( 'Logo, signature and seal', 'pikacart' ) ) }</h2>
				<p class="muted">${ esc( __( 'Upload them once; every card uses them automatically. You can skip and add them later.', 'pikacart' ) ) }</p>
				<div class="uploader-grid">
					${ uploaderHTML( 'logo', org.logo ) }
					${ uploaderHTML( 'sign', org.sign ) }
					${ uploaderHTML( 'seal', org.seal ) }
				</div>
				<form class="ob-form" novalidate>
					<div class="grid-2">
						<div class="field"><label for="ob-sname">${ esc( __( 'Signatory name', 'pikacart' ) ) }</label><input id="ob-sname" name="signatory_name" type="text" value="${ esc( org.signatory_name ) }"></div>
						<div class="field"><label for="ob-stitle">${ esc( __( 'Signatory title', 'pikacart' ) ) }</label><input id="ob-stitle" name="signatory_title" type="text" value="${ esc( org.signatory_title ) }" placeholder="${ esc( __( 'Principal', 'pikacart' ) ) }"></div>
					</div>
				</form>`;
		}

		el.innerHTML = `<div class="onboarding">
			<div class="ob-head">
				<div><span class="eyebrow">${ esc( __( 'Quick setup', 'pikacart' ) ) }</span><h1 class="ob-title">${ esc( __( 'Let\'s set up your ID cards', 'pikacart' ) ) }</h1></div>
				<button type="button" class="btn btn-ghost" data-skip>${ esc( __( 'Skip for now', 'pikacart' ) ) }</button>
			</div>
			${ stepBar() }
			<div class="card ob-card">${ body }</div>
			<div class="ob-foot">
				${ step > 1 ? `<button type="button" class="btn" data-back>${ icon( 'chevron-left' ) }${ esc( __( 'Back', 'pikacart' ) ) }</button>` : '<span></span>' }
				${ step === 1 ? '' : `<button type="button" class="btn btn-primary" data-next>${ esc( step === 3 ? __( 'Finish setup', 'pikacart' ) : __( 'Save and continue', 'pikacart' ) ) }${ icon( 'chevron-right' ) }</button>` }
			</div>
		</div>`;

		bind();
	}

	async function save( extra, btn ) {
		const form = el.querySelector( '.ob-form' );
		const data = Object.assign( form ? formData( form ) : {}, extra );
		return withLoading( btn, async () => {
			try {
				const res = await api( 'onboarding', { method: 'POST', body: data } );
				ctx.setMe( res.me );
				return true;
			} catch ( err ) {
				if ( form ) {
					markError( form, err );
				}
				toast( err.message, 'error' );
				return false;
			}
		} );
	}

	function bind() {
		el.querySelectorAll( '[data-cat]' ).forEach( ( b ) => {
			b.addEventListener( 'click', async () => {
				el.querySelectorAll( '[data-cat]' ).forEach( ( x ) => x.classList.toggle( 'is-selected', x === b ) );
				if ( await save( { category_id: Number( b.dataset.cat ) }, null ) ) {
					step = 2;
					draw();
				}
			} );
		} );
		el.querySelectorAll( '[data-step]' ).forEach( ( b ) => {
			b.addEventListener( 'click', () => {
				step = Number( b.dataset.step );
				draw();
			} );
		} );
		el.querySelector( '[data-back]' )?.addEventListener( 'click', () => {
			step -= 1;
			draw();
		} );
		el.querySelector( '[data-next]' )?.addEventListener( 'click', async ( e ) => {
			const finish = step === 3;
			if ( await save( finish ? { finish: 1 } : {}, e.currentTarget ) ) {
				if ( finish ) {
					toast( __( 'All set! Your details will appear on every card.', 'pikacart' ) );
					ctx.navigate( '' );
				} else {
					step += 1;
					draw();
				}
			}
		} );
		el.querySelector( '[data-skip]' ).addEventListener( 'click', () => {
			ctx.skipWelcome();
			ctx.navigate( '' );
		} );
		bindUploaders( el, window.PKC.uploadMb, ( me ) => {
			// Keep typed signatory fields while refreshing the images.
			const form = el.querySelector( '.ob-form' );
			const typed = form ? formData( form ) : {};
			ctx.setMe( me );
			draw();
			const f2 = el.querySelector( '.ob-form' );
			if ( f2 ) {
				Object.keys( typed ).forEach( ( k ) => {
					const input = f2.querySelector( `[name="${ k }"]` );
					if ( input ) {
						input.value = typed[ k ];
					}
				} );
			}
		} );
	}

	draw();
}
