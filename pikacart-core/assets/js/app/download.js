/**
 * "Download & print" step: choose which cards, the format and the sides,
 * then download (PDF, JPG, PNG) or print. Links to the print sheet maker.
 */

import { api } from './api.js';
import { esc, icon, toast, withLoading } from './ui.js';
import { exportCards, printCards } from './exporter.js';

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

export default function downloadStep( body, P ) {
	const pid = P.project.id;
	body.innerHTML = `<div class="split">
		<div class="split-main">
			<section class="card">
				<div class="card-head"><h3>${ icon( 'download' ) } ${ esc( __( 'Download cards', 'pikacart' ) ) }</h3></div>
				${ P.ctx.me.state.watermark ? `<p class="banner banner-trial">${ icon( 'sparkles' ) }<span>${ esc( sprintf( __( 'Free plan: files include the "%s" watermark.', 'pikacart' ), P.ctx.me.watermark.text ) ) } <a href="${ esc( P.ctx.url( 'subscription' ) ) }" data-link>${ esc( __( 'Remove watermark', 'pikacart' ) ) }</a></span></p>` : '' }
				<div class="field"><label>${ esc( __( 'Which cards', 'pikacart' ) ) }</label>
					<select data-which>
						<option value="all">${ esc( __( 'All people', 'pikacart' ) ) }</option>
						<option value="ready">${ esc( __( 'Only "Ready" (photo and details complete)', 'pikacart' ) ) }</option>
						<option value="draft">${ esc( __( 'Only "Draft"', 'pikacart' ) ) }</option>
						<option value="class">${ esc( __( 'One class / department', 'pikacart' ) ) }</option>
					</select>
				</div>
				<div class="field" data-classbox hidden><label>${ esc( __( 'Class or department', 'pikacart' ) ) }</label><select data-class></select></div>
				<div class="grid-2">
					<div class="field"><label>${ esc( __( 'Format', 'pikacart' ) ) }</label><select data-format><option value="pdf">${ esc( __( 'PDF (best for printing)', 'pikacart' ) ) }</option><option value="png">${ esc( __( 'PNG images (ZIP)', 'pikacart' ) ) }</option><option value="jpg">${ esc( __( 'JPG images (ZIP)', 'pikacart' ) ) }</option></select></div>
					<div class="field"><label>${ esc( __( 'Sides', 'pikacart' ) ) }</label><select data-sides><option value="both">${ esc( __( 'Front and back', 'pikacart' ) ) }</option><option value="front">${ esc( __( 'Front only', 'pikacart' ) ) }</option><option value="back">${ esc( __( 'Back only', 'pikacart' ) ) }</option></select></div>
				</div>
				<p class="muted small">${ esc( __( 'Files are made at 300 DPI at the exact card size. Bleed can be switched on in Size & colour.', 'pikacart' ) ) }</p>
				<p class="count-line" data-count></p>
				<div class="btn-row">
					<button type="button" class="btn btn-primary" data-go="download">${ icon( 'download' ) }${ esc( __( 'Download', 'pikacart' ) ) }</button>
					<button type="button" class="btn" data-go="print">${ icon( 'printer' ) }${ esc( __( 'Print one card per page', 'pikacart' ) ) }</button>
				</div>
			</section>
			<section class="card cta-card">
				<div><h3>${ icon( 'printer' ) } ${ esc( __( 'Print sheet maker', 'pikacart' ) ) }</h3><p class="muted">${ esc( __( 'Put many cards on A4 or larger paper with cut marks, front and back side by side, ready to cut.', 'pikacart' ) ) }</p></div>
				<a class="btn btn-accent" href="${ esc( P.ctx.url( `print/${ pid }` ) ) }" data-link>${ esc( __( 'Open print sheet maker', 'pikacart' ) ) }</a>
			</section>
		</div>
	</div>`;

	const $ = ( s ) => body.querySelector( s );
	let facets = null;

	async function count() {
		const f = filter();
		const q = new URLSearchParams( Object.assign( { per_page: 1, facets: facets ? 0 : 1 }, f ) );
		try {
			const res = await api( `projects/${ pid }/members?${ q }` );
			if ( res.facets && ! facets ) {
				facets = res.facets;
				const vals = [ ...facets.class.map( ( v ) => [ 'class', v ] ), ...facets.department.map( ( v ) => [ 'department', v ] ) ];
				$( '[data-class]' ).innerHTML = vals.map( ( [ k, v ] ) => `<option value="${ esc( k + '|' + v ) }">${ esc( v ) }</option>` ).join( '' );
			}
			$( '[data-count]' ).innerHTML = res.total ? esc( sprintf( _n( '%d card will be created.', '%d cards will be created.', res.total, 'pikacart' ), res.total ) ) : `${ esc( __( 'No people match. Add people first.', 'pikacart' ) ) } <a href="${ esc( P.ctx.url( `projects/${ pid }/people` ) ) }" data-link>${ esc( __( 'Go to People', 'pikacart' ) ) }</a>`;
		} catch ( e ) {
			toast( e.message, 'error' );
		}
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

	$( '[data-which]' ).addEventListener( 'change', ( e ) => {
		$( '[data-classbox]' ).hidden = e.target.value !== 'class';
		count();
	} );
	$( '[data-class]' ).addEventListener( 'change', count );
	body.querySelectorAll( '[data-go]' ).forEach( ( b ) => b.addEventListener( 'click', () => withLoading( b, async () => {
		const members = await allMembers( pid, filter() );
		if ( ! members.length ) {
			toast( __( 'No people match. Add people first.', 'pikacart' ), 'error' );
			return;
		}
		if ( b.dataset.go === 'download' ) {
			await exportCards( P, members, { format: $( '[data-format]' ).value, sides: $( '[data-sides]' ).value } );
		} else {
			await printCards( P, members, $( '[data-sides]' ).value );
		}
	} ) ) );
	count();
}
