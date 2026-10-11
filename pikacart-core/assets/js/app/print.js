/**
 * Print sheet maker: arranges many cards on paper ready to print and cut.
 * Paper A4, A5, A6, A3, 12x18", 13x19" or custom; portrait or landscape.
 * Front and back side by side (with optional fold line), or fronts/backs only.
 * Auto-fit, automatic pages, crop marks with scissor symbols outside the cards,
 * margins, gap, borders; Print and Download PDF at exact millimetre sizes.
 * Route: print/{projectId}
 */

import { api } from './api.js';
import { esc, icon, toast, withLoading, confirmDialog } from './ui.js';
import { loadProject } from './views/project.js';
import { allMembers } from './download.js';
import { renderPrint } from './exporter.js';

const { __, _n, sprintf } = window.wp.i18n;

export const PAPERS = () => [
	[ 'a4', 'A4 (210 × 297 mm)', 210, 297 ],
	[ 'a5', 'A5 (148 × 210 mm)', 148, 210 ],
	[ 'a6', 'A6 (105 × 148 mm)', 105, 148 ],
	[ 'a3', 'A3 (297 × 420 mm)', 297, 420 ],
	[ '12x18', '12 × 18 inch (305 × 457 mm)', 304.8, 457.2 ],
	[ '13x19', '13 × 19 inch (330 × 483 mm)', 330.2, 482.6 ],
	[ 'custom', __( 'Custom size', 'pikacart' ), 0, 0 ],
];

/** Work out how many cards fit and where each one goes (all in mm). */
export function computeSheet( paper, card, o ) {
	const pair = o.mode === 'pair';
	const unitW = pair ? card.w * 2 + ( o.fold ? 0 : o.gap ) : card.w;
	const unitH = card.h;
	const usableW = paper.w - o.margin * 2;
	const usableH = paper.h - o.margin * 2;
	const cols = Math.max( 0, Math.floor( ( usableW + o.gap ) / ( unitW + o.gap ) ) );
	const rows = Math.max( 0, Math.floor( ( usableH + o.gap ) / ( unitH + o.gap ) ) );
	const gridW = cols * unitW + ( cols - 1 ) * o.gap;
	const gridH = rows * unitH + ( rows - 1 ) * o.gap;
	const ox = ( paper.w - gridW ) / 2;
	const oy = ( paper.h - gridH ) / 2;
	const slots = [];
	for ( let r = 0; r < rows; r++ ) {
		for ( let c = 0; c < cols; c++ ) {
			slots.push( { x: ox + c * ( unitW + o.gap ), y: oy + r * ( unitH + o.gap ) } );
		}
	}
	return { cols, rows, perPage: cols * rows, unitW, unitH, slots, pair };
}

export default function printView( el, ctx, pid ) {
	let alive = true;
	let P = null;
	let members = [];
	const st = { paper: 'a4', orient: 'portrait', cw: 210, ch: 297, mode: 'pair', fold: false, margin: 5, gap: 8, marks: true, border: false, which: 'all' };
	const lowCache = new Map();

	el.innerHTML = '<div class="skeleton-grid"><div class="skeleton sk-wide"></div></div>';

	( async () => {
		try {
			P = await loadProject( ctx, pid );
		} catch ( e ) {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p></div>`;
			return;
		}
		if ( ! alive ) {
			return;
		}
		const card = P.size();
		el.innerHTML = `<div class="section-head-row"><div><a class="back-link" href="${ esc( ctx.url( `projects/${ pid }/download` ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( P.project.name ) }</a><h2 class="h2">${ esc( __( 'Print sheet maker', 'pikacart' ) ) }</h2></div></div>
		<div class="split sheet-split">
			<aside class="card sheet-controls">
				<div class="field"><label>${ esc( __( 'Paper', 'pikacart' ) ) }</label><select data-k="paper">${ PAPERS().map( ( p ) => `<option value="${ p[ 0 ] }">${ esc( p[ 1 ] ) }</option>` ).join( '' ) }</select></div>
				<div class="grid-2 custom-paper" hidden>
					<div class="field"><label>${ esc( __( 'Width mm', 'pikacart' ) ) }</label><input type="number" data-k="cw" value="210" min="50" max="1000"></div>
					<div class="field"><label>${ esc( __( 'Height mm', 'pikacart' ) ) }</label><input type="number" data-k="ch" value="297" min="50" max="1000"></div>
				</div>
				<div class="seg"><button type="button" data-orient="portrait" class="is-on">${ esc( __( 'Portrait', 'pikacart' ) ) }</button><button type="button" data-orient="landscape">${ esc( __( 'Landscape', 'pikacart' ) ) }</button></div>
				<div class="field"><label>${ esc( __( 'Which cards', 'pikacart' ) ) }</label><select data-k="which"><option value="all">${ esc( __( 'All people', 'pikacart' ) ) }</option><option value="ready">${ esc( __( 'Ready only', 'pikacart' ) ) }</option><option value="draft">${ esc( __( 'Draft only', 'pikacart' ) ) }</option></select></div>
				<div class="field"><label>${ esc( __( 'Layout', 'pikacart' ) ) }</label><select data-k="mode"><option value="pair">${ esc( __( 'Front and back side by side', 'pikacart' ) ) }</option><option value="front">${ esc( __( 'Fronts only', 'pikacart' ) ) }</option><option value="back">${ esc( __( 'Backs only', 'pikacart' ) ) }</option></select></div>
				<label class="toggle"><input type="checkbox" data-k="fold"><span class="toggle-ui"></span><span>${ esc( __( 'Fold line between front and back', 'pikacart' ) ) }</span></label>
				<div class="grid-2">
					<div class="field"><label>${ esc( __( 'Page margin (mm)', 'pikacart' ) ) }</label><input type="number" data-k="margin" value="5" min="0" max="50" step="0.5"></div>
					<div class="field"><label>${ esc( __( 'Gap (mm)', 'pikacart' ) ) }</label><input type="number" data-k="gap" value="8" min="0" max="30" step="0.5"></div>
				</div>
				<label class="toggle"><input type="checkbox" data-k="marks" checked><span class="toggle-ui"></span><span>${ esc( __( 'Cut marks with scissors', 'pikacart' ) ) }</span></label>
				<label class="toggle"><input type="checkbox" data-k="border"><span class="toggle-ui"></span><span>${ esc( __( 'Thin border around each card', 'pikacart' ) ) }</span></label>
				<div class="sheet-summary" data-summary></div>
				<div class="btn-col">
					<button type="button" class="btn btn-primary" data-out="print">${ icon( 'printer' ) }${ esc( __( 'Print', 'pikacart' ) ) }</button>
					<button type="button" class="btn" data-out="pdf">${ icon( 'download' ) }${ esc( __( 'Download PDF', 'pikacart' ) ) }</button>
				</div>
				<p class="muted small">${ icon( 'info' ) } ${ esc( __( 'In the print dialog choose "Actual size" (100% scale) so cards come out at the exact size.', 'pikacart' ) ) }</p>
				<p class="muted small">${ esc( sprintf( __( 'Card size: %1$s × %2$s mm', 'pikacart' ), card.w, card.h ) ) }</p>
			</aside>
			<section class="sheet-pages" data-pages aria-live="polite"></section>
		</div>`;

		bind();
		await loadMembers();
	} )();

	async function loadMembers() {
		const f = st.which === 'all' ? {} : { status: st.which };
		try {
			members = await allMembers( pid, f );
		} catch ( e ) {
			toast( e.message, 'error' );
			members = [];
		}
		if ( alive ) {
			draw();
		}
	}

	function paper() {
		const p = PAPERS().find( ( x ) => x[ 0 ] === st.paper );
		let w = st.paper === 'custom' ? st.cw : p[ 2 ];
		let h = st.paper === 'custom' ? st.ch : p[ 3 ];
		if ( ( st.orient === 'landscape' ) !== ( w > h ) ) {
			[ w, h ] = [ h, w ];
		}
		return { w, h };
	}

	/** Units to place: one per person (pair) or one per side. */
	function units() {
		return members.map( ( m ) => ( { m } ) );
	}

	function sheet() {
		return computeSheet( paper(), P.size(), { mode: st.mode, fold: st.fold, margin: Number( st.margin ), gap: Number( st.gap ) } );
	}

	async function lowImage( m, side ) {
		const key = `${ m.id }-${ side }`;
		if ( ! lowCache.has( key ) ) {
			lowCache.set( key, renderPrint( P, m, side, P.ctx.me.state.watermark ? { text: P.ctx.me.watermark.text, brand: '#4F46E5' } : null, 72 ).then( ( r ) => r.canvas ) );
		}
		return lowCache.get( key );
	}

	let drawToken = 0;
	async function draw() {
		const token = ++drawToken;
		const pp = paper();
		const sh = sheet();
		const list = units();
		const pages = sh.perPage ? Math.ceil( list.length / sh.perPage ) : 0;
		el.querySelector( '[data-summary]' ).innerHTML = sh.perPage
			? `<strong>${ esc( sprintf( _n( '%d card per page', '%d cards per page', sh.perPage, 'pikacart' ), sh.perPage ) ) }</strong><span>${ esc( sprintf( _n( '%1$d person · %2$d page', '%1$d people · %2$d pages', list.length, 'pikacart' ), list.length, pages ) ) }</span>`
			: `<strong class="err">${ esc( __( 'The card does not fit on this paper. Choose a bigger paper or smaller margins.', 'pikacart' ) ) }</strong>`;
		const box = el.querySelector( '[data-pages]' );
		if ( ! list.length ) {
			box.innerHTML = `<div class="card"><p class="muted">${ esc( __( 'No people to print yet.', 'pikacart' ) ) } <a href="${ esc( ctx.url( `projects/${ pid }/people` ) ) }" data-link>${ esc( __( 'Add people', 'pikacart' ) ) }</a></p></div>`;
			return;
		}
		const scale = Math.min( 2.2, 520 / pp.w );
		box.innerHTML = Array.from( { length: Math.min( pages, 30 ) }, ( _, i ) => `<figure class="sheet-page"><canvas width="${ Math.round( pp.w * scale ) }" height="${ Math.round( pp.h * scale ) }" style="aspect-ratio:${ pp.w }/${ pp.h }"></canvas><figcaption>${ esc( sprintf( __( 'Page %1$d of %2$d', 'pikacart' ), i + 1, pages ) ) }</figcaption></figure>` ).join( '' ) + ( pages > 30 ? `<p class="muted">${ esc( sprintf( __( '+ %d more pages', 'pikacart' ), pages - 30 ) ) }</p>` : '' );
		const canvases = box.querySelectorAll( 'canvas' );
		for ( let pg = 0; pg < canvases.length; pg++ ) {
			const c = canvases[ pg ];
			const g = c.getContext( '2d' );
			g.fillStyle = '#fff';
			g.fillRect( 0, 0, c.width, c.height );
			const items = list.slice( pg * sh.perPage, ( pg + 1 ) * sh.perPage );
			const boxes = [];
			let pd = null;
			for ( let i = 0; i < items.length; i++ ) {
				if ( token !== drawToken ) {
					return;
				}
				pd = pd || {
					image: async ( m, side, x, y, w, h ) => g.drawImage( await lowImage( m, side ), x * scale, y * scale, w * scale, h * scale ),
					line: ( x1, y1, x2, y2, dash ) => {
						g.strokeStyle = dash ? '#b5b5b5' : '#111';
						g.lineWidth = Math.max( 0.5, ( dash ? 0.1 : 0.2 ) * scale );
						g.setLineDash( dash ? [ 1 * scale, 1 * scale ] : [] );
						g.beginPath();
						g.moveTo( x1 * scale, y1 * scale );
						g.lineTo( x2 * scale, y2 * scale );
						g.stroke();
						g.setLineDash( [] );
					},
					rect: ( x, y, w, h ) => {
						g.strokeStyle = '#bbb';
						g.lineWidth = 1;
						g.strokeRect( x * scale, y * scale, w * scale, h * scale );
					},
					scissors: ( x, y, s, rot ) => drawScissorsCanvas( g, x * scale, y * scale, s * scale, rot ),
				};
				boxes.push( ...await drawUnit( pd, items[ i ].m, sh.slots[ i ], sh ) );
			}
			if ( pd ) {
				drawGuides( pd, boxes );
			}
		}
	}

	/** Draw one unit (card or front+back pair) with marks. Coordinates in mm. */
	async function drawUnit( d, m, slot, sh ) {
		const card = P.size();
		const cards = sh.pair ? [ [ 'front', slot.x ], [ 'back', slot.x + card.w + ( st.fold ? 0 : Number( st.gap ) ) ] ] : [ [ st.mode, slot.x ] ];
		for ( const [ side, x ] of cards ) {
			await d.image( m, side, x, slot.y, card.w, card.h );
			if ( st.border ) {
				d.rect( x, slot.y, card.w, card.h );
			}
		}
		if ( sh.pair && st.fold ) {
			d.line( slot.x + card.w, slot.y - 2, slot.x + card.w, slot.y + card.h + 2, true );
		}
		// Boxes that get a cut line around them: each card, or the folded pair.
		return sh.pair && ! st.fold
			? cards.map( ( [ , x ] ) => [ x, slot.y, card.w, card.h ] )
			: [ [ slot.x, slot.y, sh.unitW, card.h ] ];
	}

	/**
	 * Dotted cut lines with scissors, drawn in the middle of the gap around every
	 * card, so each card keeps an equal white border and no cut touches a card.
	 */
	function drawGuides( d, boxes ) {
		if ( ! st.marks || ! boxes.length ) {
			return;
		}
		const h = Number( st.gap ) / 2;
		const segs = new Map();
		const add = ( x1, y1, x2, y2 ) => {
			const k = [ x1, y1, x2, y2 ].map( ( v ) => v.toFixed( 2 ) ).join( ',' );
			segs.set( k, [ x1, y1, x2, y2 ] );
		};
		boxes.forEach( ( [ x, y, w, hh ] ) => {
			const l = x - h;
			const r = x + w + h;
			const t = y - h;
			const b = y + hh + h;
			add( l, t, r, t );
			add( l, b, r, b );
			add( l, t, l, b );
			add( r, t, r, b );
		} );
		const sz = 3;
		segs.forEach( ( [ x1, y1, x2, y2 ] ) => {
			d.line( x1, y1, x2, y2, true );
		} );
		segs.forEach( ( [ x1, y1, x2, y2 ] ) => {
			d.scissors( ( x1 + x2 ) / 2, ( y1 + y2 ) / 2, sz, x1 === x2 ? 90 : 0 );
		} );
	}

	function bind() {
		el.querySelectorAll( '[data-k]' ).forEach( ( i ) => i.addEventListener( 'change', async () => {
			const k = i.dataset.k;
			st[ k ] = i.type === 'checkbox' ? i.checked : i.type === 'number' ? Number( i.value ) : i.value;
			if ( k === 'paper' ) {
				el.querySelector( '.custom-paper' ).hidden = st.paper !== 'custom';
			}
			if ( k === 'which' ) {
				await loadMembers();
				return;
			}
			draw();
		} ) );
		el.querySelectorAll( '[data-orient]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			st.orient = b.dataset.orient;
			el.querySelectorAll( '[data-orient]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
			draw();
		} ) );
		el.querySelectorAll( '[data-out]' ).forEach( ( b ) => b.addEventListener( 'click', () => withLoading( b, () => output( b.dataset.out ) ) ) );
	}

	async function output( kind ) {
		const sh = sheet();
		if ( ! sh.perPage || ! members.length ) {
			toast( __( 'Nothing to print with these settings.', 'pikacart' ), 'error' );
			return;
		}
		const win = kind === 'print' ? window.open( '', '_blank' ) : null;
		if ( kind === 'print' && ! win ) {
			toast( __( 'Please allow pop-ups to print.', 'pikacart' ), 'error' );
			return;
		}
		if ( win ) {
			win.document.write( `<p style="font-family:sans-serif;padding:24px">${ esc( __( 'Preparing print sheets…', 'pikacart' ) ) }</p>` );
		}
		let wm = null;
		try {
			const res = await api( 'downloads/count', { method: 'POST', body: { n: members.length } } );
			wm = res.watermark ? { text: P.ctx.me.watermark.text, brand: getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-brand' ).trim() } : null;
		} catch ( e ) {
			wm = P.ctx.me.state.watermark ? { text: P.ctx.me.watermark.text, brand: '#4F46E5' } : null;
		}
		const pp = paper();
		const list = units();
		const pages = Math.ceil( list.length / sh.perPage );
		const hi = new Map();
		const getHi = async ( m, side ) => {
			const key = `${ m.id }-${ side }`;
			if ( ! hi.has( key ) ) {
				const r = await renderPrint( P, m, side, wm );
				hi.set( key, r.canvas.toDataURL( 'image/jpeg', 0.93 ) );
			}
			return hi.get( key );
		};

		if ( kind === 'pdf' ) {
			const { jsPDF } = window.jspdf;
			const pdf = new jsPDF( { unit: 'mm', format: [ pp.w, pp.h ], orientation: pp.w > pp.h ? 'landscape' : 'portrait', compress: true } );
			for ( let pg = 0; pg < pages; pg++ ) {
				if ( pg ) {
					pdf.addPage( [ pp.w, pp.h ], pp.w > pp.h ? 'landscape' : 'portrait' );
				}
				const items = list.slice( pg * sh.perPage, ( pg + 1 ) * sh.perPage );
				const boxes = [];
				const pd = {
						image: async ( m, side, x, y, w, h ) => pdf.addImage( await getHi( m, side ), 'JPEG', x, y, w, h, undefined, 'FAST' ),
						line: ( x1, y1, x2, y2, dash ) => {
							if ( dash ) {
								pdf.setDrawColor( 181, 181, 181 );
								pdf.setLineWidth( 0.1 );
							} else {
								pdf.setDrawColor( 17, 17, 17 );
								pdf.setLineWidth( 0.2 );
							}
							if ( pdf.setLineDashPattern ) {
								pdf.setLineDashPattern( dash ? [ 1, 1 ] : [], 0 );
							}
							pdf.line( x1, y1, x2, y2 );
						},
						rect: ( x, y, w, h ) => {
							pdf.setDrawColor( 180, 180, 180 );
							pdf.setLineWidth( 0.15 );
							pdf.rect( x, y, w, h );
						},
						scissors: ( x, y, s, rot ) => drawScissorsPdf( pdf, x, y, s, rot ),
				};
				for ( let i = 0; i < items.length; i++ ) {
					boxes.push( ...await drawUnit( pd, items[ i ].m, sh.slots[ i ], sh ) );
				}
				drawGuides( pd, boxes );
			}
			const name = ( P.project.name || 'cards' ).replace( /[^\w\- ]+/g, '' ).trim().replace( /\s+/g, '-' );
			pdf.save( `${ name }-print-sheets.pdf` );
		} else {
			// HTML print view with exact millimetre positions.
			let html = '';
			for ( let pg = 0; pg < pages; pg++ ) {
				let inner = '';
				const items = list.slice( pg * sh.perPage, ( pg + 1 ) * sh.perPage );
				const boxes = [];
				const pd = {
						image: async ( m, side, x, y, w, h ) => {
							inner += `<img src="${ await getHi( m, side ) }" style="left:${ x }mm;top:${ y }mm;width:${ w }mm;height:${ h }mm">`;
						},
						line: ( x1, y1, x2, y2, dash ) => {
							const v = x1 === x2;
							inner += `<i class="ln${ dash ? ' dash' : '' }" style="left:${ Math.min( x1, x2 ) }mm;top:${ Math.min( y1, y2 ) }mm;${ v ? `height:${ Math.abs( y2 - y1 ) }mm;width:0` : `width:${ Math.abs( x2 - x1 ) }mm;height:0` }"></i>`;
						},
						rect: ( x, y, w, h ) => {
							inner += `<i class="bd" style="left:${ x }mm;top:${ y }mm;width:${ w }mm;height:${ h }mm"></i>`;
						},
						scissors: ( x, y, s, rot ) => {
							inner += `<b class="sc" style="left:${ x - s / 2 }mm;top:${ y - s / 2 }mm;width:${ s }mm;height:${ s }mm;transform:rotate(${ rot || 0 }deg)">✂</b>`;
						},
				};
				for ( let i = 0; i < items.length; i++ ) {
					boxes.push( ...await drawUnit( pd, items[ i ].m, sh.slots[ i ], sh ) );
				}
				drawGuides( pd, boxes );
				html += `<section class="pg">${ inner }</section>`;
			}
			win.document.open();
			win.document.write( `<!doctype html><html><head><title>${ esc( P.project.name ) }</title><style>
				@page { size: ${ pp.w }mm ${ pp.h }mm; margin: 0; }
				html,body{margin:0;padding:0;background:#fff}
				.pg{position:relative;width:${ pp.w }mm;height:${ pp.h }mm;overflow:hidden;page-break-after:always;break-after:page}
				.pg:last-child{page-break-after:auto;break-after:auto}
				.pg img{position:absolute;display:block}
				.ln{position:absolute;border-left:0.2mm solid #111;border-top:0.2mm solid #111}
				.ln.dash{border-style:dashed;border-width:0.1mm;border-color:#b5b5b5}
				.bd{position:absolute;outline:0.15mm solid #b4b4b4;box-sizing:border-box}
				.sc{position:absolute;font:2.6mm/1 Arial,sans-serif;color:#9a9a9a;background:#fff;display:flex;align-items:center;justify-content:center}
				.note{font:14px sans-serif;padding:12px;background:#fff6e0;color:#7a4b00} @media print{.note{display:none}}
				</style></head><body><div class="note">${ esc( __( 'Choose "Actual size" (100% scale), no margins, and turn off headers and footers.', 'pikacart' ) ) }</div>${ html }
				<script>window.onload=function(){setTimeout(function(){window.print();},400);};<\/script></body></html>` );
			win.document.close();
		}

		// Offer to mark the cards as Printed.
		const ok = await confirmDialog( { title: __( 'Mark these cards as Printed?', 'pikacart' ), message: sprintf( _n( 'Set %d card to "Printed" so you know it is done.', 'Set %d cards to "Printed" so you know they are done.', members.length, 'pikacart' ), members.length ), confirm: __( 'Mark as Printed', 'pikacart' ) } );
		if ( ok ) {
			try {
				const ids = members.map( ( m ) => m.id );
				for ( let i = 0; i < ids.length; i += 1000 ) {
					await api( `projects/${ pid }/members/bulk`, { method: 'POST', body: { action: 'status', status: 'printed', ids: ids.slice( i, i + 1000 ) } } );
				}
				toast( __( 'Marked as Printed.', 'pikacart' ) );
			} catch ( e ) {
				toast( e.message, 'error' );
			}
		}
	}

	return () => {
		alive = false;
	};
}

/* Small scissor symbol drawn as vector shapes (no special font needed).
   (x, y) is the centre; rot is 0 for a horizontal cut line, 90 for a vertical one.
   A white patch behind it keeps the dotted line from running through it. */
function scissorParts( x, y, s, rot ) {
	const a = ( ( rot || 0 ) * Math.PI ) / 180;
	const p = ( u, v ) => {
		const dx = ( u - 0.5 ) * s;
		const dy = ( v - 0.5 ) * s;
		return [ x + dx * Math.cos( a ) - dy * Math.sin( a ), y + dx * Math.sin( a ) + dy * Math.cos( a ) ];
	};
	return {
		rings: [ p( 0.22, 0.3 ), p( 0.22, 0.7 ) ],
		r: s * 0.18,
		blades: [ [ p( 0.36, 0.4 ), p( 1, 0.85 ) ], [ p( 0.36, 0.6 ), p( 1, 0.15 ) ] ],
	};
}

function drawScissorsCanvas( g, x, y, s, rot ) {
	const k = scissorParts( x, y, s, rot );
	g.save();
	g.setLineDash( [] );
	g.fillStyle = '#fff';
	g.fillRect( x - s * 0.6, y - s * 0.6, s * 1.2, s * 1.2 );
	g.strokeStyle = '#9a9a9a';
	g.lineWidth = Math.max( 0.5, s * 0.07 );
	g.beginPath();
	k.rings.forEach( ( [ cx, cy ] ) => {
		g.moveTo( cx + k.r, cy );
		g.arc( cx, cy, k.r, 0, Math.PI * 2 );
	} );
	k.blades.forEach( ( [ [ x1, y1 ], [ x2, y2 ] ] ) => {
		g.moveTo( x1, y1 );
		g.lineTo( x2, y2 );
	} );
	g.stroke();
	g.restore();
}

function drawScissorsPdf( pdf, x, y, s, rot ) {
	const k = scissorParts( x, y, s, rot );
	if ( pdf.setLineDashPattern ) {
		pdf.setLineDashPattern( [], 0 );
	}
	pdf.setFillColor( 255, 255, 255 );
	pdf.rect( x - s * 0.6, y - s * 0.6, s * 1.2, s * 1.2, 'F' );
	pdf.setDrawColor( 154, 154, 154 );
	pdf.setLineWidth( 0.12 );
	k.rings.forEach( ( [ cx, cy ] ) => pdf.circle( cx, cy, k.r ) );
	k.blades.forEach( ( [ [ x1, y1 ], [ x2, y2 ] ] ) => pdf.line( x1, y1, x2, y2 ) );
}
