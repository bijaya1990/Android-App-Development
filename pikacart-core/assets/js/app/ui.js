/**
 * Small UI helpers: escaping, icons, toasts, confirm dialogs, loading buttons.
 */

const { __ } = window.wp.i18n;

/** Escape text for safe use inside HTML strings. */
export function esc( value ) {
	return String( value ?? '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#39;' );
}

export function icon( name, cls = '' ) {
	return `<svg class="pkc-i ${ esc( cls ) }" aria-hidden="true"><use href="#i-${ esc( name ) }"></use></svg>`;
}

/** Show a short success or error message in the corner. */
export function toast( message, type = 'success' ) {
	const root = document.getElementById( 'toasts' );
	const el = document.createElement( 'div' );
	el.className = `toast toast-${ type }`;
	el.setAttribute( 'role', type === 'error' ? 'alert' : 'status' );
	el.innerHTML = `${ icon( type === 'error' ? 'alert' : 'check-circle' ) }<span>${ esc( message ) }</span><button type="button" class="toast-x" aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button>`;
	root.appendChild( el );
	const close = () => {
		el.classList.add( 'is-leaving' );
		setTimeout( () => el.remove(), 220 );
	};
	el.querySelector( '.toast-x' ).addEventListener( 'click', close );
	setTimeout( close, type === 'error' ? 7000 : 4000 );
}

/** Modal dialog. Resolves true when confirmed. */
export function confirmDialog( { title, message, confirm = __( 'Confirm', 'pikacart' ), danger = false, body = '' } ) {
	return new Promise( ( resolve ) => {
		const root = document.getElementById( 'modal-root' );
		const last = document.activeElement;
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		wrap.innerHTML = `
			<div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
				<h2 id="modal-title">${ esc( title ) }</h2>
				${ message ? `<p>${ esc( message ) }</p>` : '' }
				${ body }
				<div class="modal-actions">
					<button type="button" class="btn" data-x="0">${ esc( __( 'Cancel', 'pikacart' ) ) }</button>
					<button type="button" class="btn ${ danger ? 'btn-danger' : 'btn-primary' }" data-x="1">${ esc( confirm ) }</button>
				</div>
			</div>`;
		const done = ( value ) => {
			wrap.classList.add( 'is-leaving' );
			document.removeEventListener( 'keydown', onKey );
			setTimeout( () => wrap.remove(), 180 );
			if ( last && last.focus ) {
				last.focus();
			}
			resolve( value ? wrap : false );
		};
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				done( false );
			}
		};
		wrap.addEventListener( 'click', ( e ) => {
			if ( e.target === wrap ) {
				done( false );
			}
			const b = e.target.closest( '[data-x]' );
			if ( b ) {
				done( b.dataset.x === '1' );
			}
		} );
		document.addEventListener( 'keydown', onKey );
		root.appendChild( wrap );
		wrap.querySelector( '[data-x="1"]' ).focus();
	} );
}

/** Put a button into a loading state while a promise runs. */
export async function withLoading( btn, fn ) {
	if ( ! btn ) {
		return fn();
	}
	btn.disabled = true;
	btn.classList.add( 'is-loading' );
	try {
		return await fn();
	} finally {
		btn.disabled = false;
		btn.classList.remove( 'is-loading' );
	}
}

/** Collect a form's values into a plain object (checkboxes become true/false). */
export function formData( form ) {
	const out = {};
	new FormData( form ).forEach( ( value, key ) => {
		out[ key ] = value;
	} );
	form.querySelectorAll( 'input[type="checkbox"]' ).forEach( ( c ) => {
		if ( c.name && ! c.name.includes( '[' ) ) {
			out[ c.name ] = c.checked;
		}
	} );
	return out;
}

/** Highlight the field the server complained about. */
export function markError( form, error ) {
	form.querySelectorAll( '.has-error' ).forEach( ( el ) => el.classList.remove( 'has-error' ) );
	const field = error && error.data && error.data.field;
	if ( field ) {
		const input = form.querySelector( `[name="${ field }"]` );
		if ( input ) {
			input.closest( '.field' )?.classList.add( 'has-error' );
			input.focus();
		}
	}
}

/** Friendly empty state with an illustration and one clear button. */
export function emptyState( { art = 'cards', title, text, button = '', href = '' } ) {
	const btn = button ? `<a class="btn btn-primary" href="${ esc( href ) }" data-link>${ esc( button ) }</a>` : '';
	return `<div class="empty">
		${ illustration( art ) }
		<h3>${ esc( title ) }</h3>
		<p>${ esc( text ) }</p>
		${ btn }
	</div>`;
}

/** Simple original SVG illustrations for empty states. */
export function illustration( kind ) {
	const cards = `<svg class="empty-art" viewBox="0 0 200 140" aria-hidden="true">
		<ellipse cx="100" cy="128" rx="70" ry="8" fill="var(--pkc-brand-50)"/>
		<g transform="rotate(-10 70 70)"><rect x="40" y="22" width="60" height="88" rx="9" fill="#fff" stroke="var(--pkc-line-strong)"/><rect x="40" y="22" width="60" height="22" rx="9" fill="var(--pkc-brand-200)"/><circle cx="70" cy="60" r="11" fill="var(--pkc-brand-100)"/><rect x="52" y="78" width="36" height="5" rx="2.5" fill="var(--pkc-line-strong)"/><rect x="58" y="88" width="24" height="4" rx="2" fill="var(--pkc-line)"/></g>
		<g transform="rotate(8 130 70)"><rect x="100" y="18" width="62" height="92" rx="9" fill="#fff" stroke="var(--pkc-line-strong)"/><path d="M100 27a9 9 0 0 1 9-9h44a9 9 0 0 1 9 9v13l-62 10z" fill="var(--pkc-brand)"/><circle cx="131" cy="62" r="12" fill="var(--pkc-brand-100)"/><rect x="112" y="81" width="38" height="5" rx="2.5" fill="var(--pkc-ink)" opacity=".75"/><rect x="118" y="91" width="26" height="4" rx="2" fill="var(--pkc-line-strong)"/><rect x="146" y="96" width="10" height="10" rx="1.5" fill="var(--pkc-accent)"/></g>
		<circle cx="166" cy="26" r="5" fill="var(--pkc-accent)" opacity=".7"/><circle cx="30" cy="40" r="3" fill="var(--pkc-brand)" opacity=".5"/>
	</svg>`;
	const people = `<svg class="empty-art" viewBox="0 0 200 140" aria-hidden="true">
		<ellipse cx="100" cy="128" rx="70" ry="8" fill="var(--pkc-brand-50)"/>
		<circle cx="70" cy="58" r="16" fill="var(--pkc-brand-100)"/><path d="M42 112a28 28 0 0 1 56 0z" fill="var(--pkc-brand-200)"/>
		<circle cx="130" cy="52" r="19" fill="var(--pkc-brand)"/><path d="M96 112a34 34 0 0 1 68 0z" fill="var(--pkc-brand)" opacity=".85"/>
		<circle cx="160" cy="30" r="5" fill="var(--pkc-accent)"/>
	</svg>`;
	const support = `<svg class="empty-art" viewBox="0 0 200 140" aria-hidden="true">
		<ellipse cx="100" cy="128" rx="70" ry="8" fill="var(--pkc-brand-50)"/>
		<rect x="40" y="28" width="90" height="60" rx="14" fill="var(--pkc-brand)"/><path d="M60 88l-6 18 22-18z" fill="var(--pkc-brand)"/>
		<rect x="56" y="46" width="58" height="6" rx="3" fill="#fff" opacity=".9"/><rect x="56" y="60" width="40" height="6" rx="3" fill="#fff" opacity=".6"/>
		<rect x="96" y="58" width="70" height="46" rx="12" fill="#fff" stroke="var(--pkc-line-strong)"/><circle cx="116" cy="81" r="4" fill="var(--pkc-accent)"/><circle cx="131" cy="81" r="4" fill="var(--pkc-accent)" opacity=".7"/><circle cx="146" cy="81" r="4" fill="var(--pkc-accent)" opacity=".4"/>
	</svg>`;
	return { cards, people, support }[ kind ] || cards;
}

/** Format seconds as 1:23:45 or 12:34. */
export function clock( seconds ) {
	const s = Math.max( 0, Math.floor( seconds ) );
	const h = Math.floor( s / 3600 );
	const m = Math.floor( ( s % 3600 ) / 60 );
	const sec = s % 60;
	const mm = String( m ).padStart( h ? 2 : 1, '0' );
	return ( h ? `${ h }:` : '' ) + `${ mm }:${ String( sec ).padStart( 2, '0' ) }`;
}
