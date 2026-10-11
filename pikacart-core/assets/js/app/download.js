/**
 * "Download & print" step. Three plain choices by how the cards will be used:
 * A4 paper (print sheet maker), a PVC card printer (one card per page at the
 * real card size, one person at a time or all), or images for WhatsApp.
 */

import { api } from './api.js';
import { esc, icon, toast, withLoading, confirmDialog } from './ui.js';
import { exportCards, printPvc } from './exporter.js';

const { __, _n, sprintf } = window.wp.i18n;

/** Fetch all people of a project (paged, never 500 at once in one request). */
export async function allMembers( pid, filter = {} ) {
	const out = [];
	for ( let page = 1; page < 400; page++ ) {
		const q = new URLSearchParams( Object.assign( { per_page: 200, page, sort: 'id_no' }, filter ) );
		const res = await api( `projects/${ pid }/members?${ q }` );
		out.push( ...res.members );
		if ( out.length >= res.total || ! res.members.length ) {
			break;
		}
	}
	return out;
}

const PVC_SIZES = () => [
	{ id: 'cr80', name: __( 'CR80 standard', 'pikacart' ), hint: __( '85.6 × 54 mm (ATM card size)', 'pikacart' ), w: 85.6, h: 54 },
	{ id: 'cr79', name: 'CR79', hint: '84 × 52 mm', w: 84, h: 52 },
	{ id: 'custom', name: __( 'Custom', 'pikacart' ), hint: __( 'enter mm', 'pikacart' ) },
];
const PVC_KEY = 'pkc_pvc';

function pvcPrefs() {
	try {
		return Object.assign( { size: 'cr80', cw: 85.6, ch: 54, duplex: true }, JSON.parse( window.localStorage.getItem( PVC_KEY ) || '{}' ) );
	} catch ( e ) {
		return { size: 'cr80', cw: 85.6, ch: 54, duplex: true };
	}
}

export default function downloadStep( body, P ) {
	const pid = P.project.id;
	const pref = pvcPrefs();
	body.innerHTML = `<section class="card dl-card">
		<div class="dl-head">
			<h3 data-count>${ esc( __( 'Your cards', 'pikacart' ) ) }</h3>
			<div class="dl-filters">
				<select data-which aria-label="${ esc( __( 'Which cards', 'pikacart' ) ) }">
					<option value="all">${ esc( __( 'All people', 'pikacart' ) ) }</option>
					<option value="ready">${ esc( __( 'Only "Ready"', 'pikacart' ) ) }</option>
					<option value="draft">${ esc( __( 'Only "Draft"', 'pikacart' ) ) }</option>
					<option value="class">${ esc( __( 'One class / department', 'pikacart' ) ) }</option>
				</select>
				<select data-class hidden aria-label="${ esc( __( 'Class or department', 'pikacart' ) ) }"></select>
				<select data-sides aria-label="${ esc( __( 'Sides', 'pikacart' ) ) }"><option value="both">${ esc( __( 'Front + back', 'pikacart' ) ) }</option><option value="front">${ esc( __( 'Front only', 'pikacart' ) ) }</option><option value="back">${ esc( __( 'Back only', 'pikacart' ) ) }</option></select>
			</div>
		</div>
		${ P.ctx.me.state.watermark ? `<p class="banner banner-trial">${ icon( 'sparkles' ) }<span>${ esc( sprintf( __( 'Free plan: files include the "%s" watermark.', 'pikacart' ), P.ctx.me.watermark.text ) ) } <a href="${ esc( P.ctx.url( 'subscription' ) ) }" data-link>${ esc( __( 'Remove watermark', 'pikacart' ) ) }</a></span></p>` : '' }
		<div class="dl-choices">
			<a class="dl-choice is-main" href="${ esc( P.ctx.url( `print/${ pid }` ) ) }" data-link>
				<span class="dl-ic">${ icon( 'printer' ) }</span>
				<span class="dl-t"><b>${ esc( __( 'Print on A4 paper', 'pikacart' ) ) }</b><span>${ esc( __( 'Many cards per page with cut lines · for any printer', 'pikacart' ) ) }</span></span>
				<span class="dl-ar">${ icon( 'chevron-right' ) }</span>
			</a>
			<button type="button" class="dl-choice" data-open="pvc" aria-expanded="false">
				<span class="dl-ic">${ icon( 'card' ) }</span>
				<span class="dl-t"><b>${ esc( __( 'PVC card printer', 'pikacart' ) ) }</b><span>${ esc( __( 'Print straight on PVC cards · Evolis, Zebra, Fargo, HiTi', 'pikacart' ) ) }</span></span>
				<span class="dl-ar">${ icon( 'chevron-down' ) }</span>
			</button>
			<div class="pvc" data-pvc hidden>
				<div class="pvc-step"><b>1. ${ esc( __( 'Card size', 'pikacart' ) ) }</b>
					<div class="pvc-opts" data-size>${ PVC_SIZES().map( ( z ) => `<button type="button" class="pvc-opt${ pref.size === z.id ? ' is-on' : '' }" data-v="${ z.id }"><b>${ esc( z.name ) }</b><small>${ esc( z.hint ) }</small></button>` ).join( '' ) }</div>
					<div class="pvc-custom" data-custom ${ pref.size === 'custom' ? '' : 'hidden' }>
						<label>${ esc( __( 'Width mm', 'pikacart' ) ) }<input type="number" data-cw min="20" max="200" step="0.1" value="${ esc( pref.cw ) }"></label>
						<label>${ esc( __( 'Height mm', 'pikacart' ) ) }<input type="number" data-ch min="20" max="200" step="0.1" value="${ esc( pref.ch ) }"></label>
					</div>
				</div>
				<div class="pvc-step"><b>2. ${ esc( __( 'Your printer prints', 'pikacart' ) ) }</b>
					<div class="pvc-opts" data-duplex>
						<button type="button" class="pvc-opt${ pref.duplex ? ' is-on' : '' }" data-v="1"><b>${ esc( __( 'Both sides together', 'pikacart' ) ) }</b><small>${ esc( __( 'dual-side printer, flips the card itself', 'pikacart' ) ) }</small></button>
						<button type="button" class="pvc-opt${ pref.duplex ? '' : ' is-on' }" data-v="0"><b>${ esc( __( 'One side only', 'pikacart' ) ) }</b><small>${ esc( __( 'fronts first, then I put the cards back for the backs', 'pikacart' ) ) }</small></button>
					</div>
				</div>
				<div class="pvc-step"><b>3. ${ esc( __( 'Print', 'pikacart' ) ) }</b>
					<div class="pvc-list" data-list><p class="muted small">${ esc( __( 'Loading people…', 'pikacart' ) ) }</p></div>
					<div class="pvc-actions">
						<button type="button" class="btn btn-primary" data-all>${ icon( 'printer' ) }<span data-all-label>${ esc( __( 'Print all', 'pikacart' ) ) }</span></button>
						<button type="button" class="btn" data-test>${ icon( 'card' ) }${ esc( __( 'Print 1 test card', 'pikacart' ) ) }</button>
					</div>
					<p class="tip-line">${ icon( 'info' ) }${ esc( __( 'In the print window choose your card printer and scale 100%. The card size is already set. Printed cards are marked "Printed".', 'pikacart' ) ) }</p>
					<p class="muted small pvc-pdf">${ esc( __( 'Print shop wants a file?', 'pikacart' ) ) } <button type="button" class="linklike" data-pdf>${ esc( __( 'Download card PDF (one card per page)', 'pikacart' ) ) }</button></p>
				</div>
			</div>
			<button type="button" class="dl-choice" data-img="png">
				<span class="dl-ic">${ icon( 'image' ) }</span>
				<span class="dl-t"><b>${ esc( __( 'Photos for WhatsApp', 'pikacart' ) ) }</b><span>${ esc( __( 'Each card as an image in a ZIP · share or email', 'pikacart' ) ) }</span></span>
				<span class="dl-ar">${ icon( 'download' ) }</span>
			</button>
			<p class="muted small dl-alt"><button type="button" class="linklike" data-img="jpg">${ esc( __( 'Smaller JPG images instead', 'pikacart' ) ) }</button></p>
		</div>
	</section>`;

	const $ = ( s ) => body.querySelector( s );
	let facets = null;
	let people = null;

	function save() {
		try {
			window.localStorage.setItem( PVC_KEY, JSON.stringify( pref ) );
		} catch ( e ) {}
	}

	function filter() {
		const w = $( '[data-which]' ).value;
		if ( w === 'ready' || w === 'draft' ) {
			return { status: w };
		}
		if ( w === 'class' ) {
			const v = $( '[data-class]' ).value;
			if ( v ) {
				const [ k, val ] = v.split( '|' );
				return { [ k ]: val };
			}
		}
		return {};
	}

	async function count() {
		const q = new URLSearchParams( Object.assign( { per_page: 1, facets: facets ? 0 : 1 }, filter() ) );
		try {
			const res = await api( `projects/${ pid }/members?${ q }` );
			if ( res.facets && ! facets ) {
				facets = res.facets;
				const vals = [ ...facets.class.map( ( v ) => [ 'class', v ] ), ...facets.department.map( ( v ) => [ 'department', v ] ) ];
				$( '[data-class]' ).innerHTML = vals.map( ( [ k, v ] ) => `<option value="${ esc( k + '|' + v ) }">${ esc( v ) }</option>` ).join( '' );
			}
			$( '[data-count]' ).innerHTML = res.total
				? `${ icon( 'check-circle' ) } ${ esc( sprintf( _n( 'Your %d card is ready', 'Your %d cards are ready', res.total, 'pikacart' ), res.total ) ) }`
				: `${ esc( __( 'No people match.', 'pikacart' ) ) } <a href="${ esc( P.ctx.url( `projects/${ pid }/people` ) ) }" data-link>${ esc( __( 'Add people', 'pikacart' ) ) }</a>`;
		} catch ( e ) {
			toast( e.message, 'error' );
		}
	}

	async function members() {
		const list = await allMembers( pid, filter() );
		if ( ! list.length ) {
			toast( __( 'No people match. Add people first.', 'pikacart' ), 'error' );
		}
		return list;
	}

	function size() {
		const z = PVC_SIZES().find( ( x ) => x.id === pref.size ) || PVC_SIZES()[ 0 ];
		if ( z.id === 'custom' ) {
			const w = Number( pref.cw );
			const h = Number( pref.ch );
			if ( ! ( w >= 20 && h >= 20 && w <= 200 && h <= 200 ) ) {
				toast( __( 'Enter a card size between 20 and 200 mm.', 'pikacart' ), 'error' );
				return null;
			}
			return { w, h };
		}
		return { w: z.w, h: z.h };
	}

	function drawList() {
		const box = $( '[data-list]' );
		if ( ! people.length ) {
			box.innerHTML = `<p class="muted small">${ esc( __( 'No people match. Add people first.', 'pikacart' ) ) }</p>`;
			return;
		}
		const shown = people.slice( 0, 300 );
		box.innerHTML = shown.map( ( m ) => `<div class="pvc-row">
				<span class="pvc-av">${ m.photo ? `<img src="${ esc( m.photo ) }" alt="" loading="lazy">` : icon( 'user' ) }</span>
				<span class="pvc-n"><b>${ esc( m.name || '—' ) }</b><small>${ esc( m.id_no || '' ) }</small></span>
				${ m.status === 'printed' ? `<span class="pvc-done">${ icon( 'check' ) }${ esc( __( 'Printed', 'pikacart' ) ) }</span>` : '' }
				<button type="button" class="btn btn-sm${ m.status === 'printed' ? '' : ' btn-outline' }" data-one="${ m.id }">${ icon( 'printer' ) }${ esc( m.status === 'printed' ? __( 'Again', 'pikacart' ) : __( 'Print', 'pikacart' ) ) }</button>
			</div>` ).join( '' ) + ( people.length > shown.length ? `<p class="muted small">${ esc( sprintf( __( '+ %d more. Use "Print all" or choose one class above.', 'pikacart' ), people.length - shown.length ) ) }</p>` : '' );
		$( '[data-all-label]' ).textContent = sprintf( _n( 'Print all %d card', 'Print all %d cards', people.length, 'pikacart' ), people.length );
	}

	async function loadPeople() {
		people = await allMembers( pid, filter() );
		drawList();
	}

	async function markPrinted( list ) {
		const ids = list.map( ( m ) => m.id );
		try {
			for ( let i = 0; i < ids.length; i += 1000 ) {
				await api( `projects/${ pid }/members/bulk`, { method: 'POST', body: { action: 'status', status: 'printed', ids: ids.slice( i, i + 1000 ) } } );
			}
			list.forEach( ( m ) => {
				m.status = 'printed';
			} );
			drawList();
		} catch ( e ) {
			toast( e.message, 'error' );
		}
	}

	const confirmFlip = () => confirmDialog( {
		title: __( 'Now print the backs', 'pikacart' ),
		message: __( 'Take the printed cards, turn them over and put them back in the card tray in the same order. Then print the backs.', 'pikacart' ),
		confirm: __( 'Print the backs', 'pikacart' ),
	} );

	async function pvc( list, mark ) {
		const z = size();
		if ( ! z || ! list.length ) {
			return;
		}
		const sides = $( '[data-sides]' ).value;
		const ok = await printPvc( P, list, { w: z.w, h: z.h, duplex: pref.duplex, sides, confirmFlip } );
		if ( ok && mark ) {
			await markPrinted( list );
		}
	}

	$( '[data-which]' ).addEventListener( 'change', ( e ) => {
		$( '[data-class]' ).hidden = e.target.value !== 'class';
		count();
		if ( people ) {
			loadPeople();
		}
	} );
	$( '[data-class]' ).addEventListener( 'change', () => {
		count();
		if ( people ) {
			loadPeople();
		}
	} );
	$( '[data-open="pvc"]' ).addEventListener( 'click', ( e ) => {
		const b = e.currentTarget;
		const open = $( '[data-pvc]' ).hidden;
		$( '[data-pvc]' ).hidden = ! open;
		b.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		b.classList.toggle( 'is-open', open );
		if ( open && ! people ) {
			loadPeople().catch( ( err ) => toast( err.message, 'error' ) );
		}
	} );
	$( '[data-size]' ).addEventListener( 'click', ( e ) => {
		const b = e.target.closest( '[data-v]' );
		if ( b ) {
			pref.size = b.dataset.v;
			body.querySelectorAll( '[data-size] .pvc-opt' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
			$( '[data-custom]' ).hidden = pref.size !== 'custom';
			save();
		}
	} );
	[ 'cw', 'ch' ].forEach( ( k ) => $( `[data-${ k }]` ).addEventListener( 'change', ( e ) => {
		pref[ k ] = Number( e.target.value );
		save();
	} ) );
	$( '[data-duplex]' ).addEventListener( 'click', ( e ) => {
		const b = e.target.closest( '[data-v]' );
		if ( b ) {
			pref.duplex = b.dataset.v === '1';
			body.querySelectorAll( '[data-duplex] .pvc-opt' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
			save();
		}
	} );
	$( '[data-list]' ).addEventListener( 'click', ( e ) => {
		const b = e.target.closest( '[data-one]' );
		if ( b ) {
			const m = people.find( ( x ) => String( x.id ) === b.dataset.one );
			withLoading( b, () => pvc( [ m ], true ) );
		}
	} );
	$( '[data-all]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
		const list = people || ( await members() );
		await pvc( list, true );
	} ) );
	$( '[data-test]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
		const list = people || ( await members() );
		await pvc( list.slice( 0, 1 ), false );
	} ) );
	$( '[data-pdf]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
		const list = await members();
		if ( list.length ) {
			await exportCards( P, list, { format: 'pdf', sides: $( '[data-sides]' ).value } );
		}
	} ) );
	body.querySelectorAll( '[data-img]' ).forEach( ( b ) => b.addEventListener( 'click', () => withLoading( b, async () => {
		const list = await members();
		if ( list.length ) {
			await exportCards( P, list, { format: b.dataset.img, sides: $( '[data-sides]' ).value } );
		}
	} ) ) );
	count();
}
