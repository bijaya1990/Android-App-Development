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
	const st = { paper: 'a4', orient: 'portrait', cw: 210, ch: 297, mode: 'pair', fold: true, margin: 8, gap: 4, marks: true, border: false, which: 'all' };
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
				<label class="toggle"><input type="checkbox" data-k="fold" checked><span class="toggle-ui"></span><span>${ esc( __( 'Fold line between front and back', 'pikacart' ) ) }</span></label>
				<div class="grid-2">
					<div class="field"><label>${ esc( __( 'Page margin (mm)', 'pikacart' ) ) }</label><input type="number" data-k="margin" value="8" min="0" max="50" step="0.5"></div>
					<div class="field"><label>${ esc( __( 'Gap (mm)', 'pikacart' ) ) }</label><input type="number" data-k="gap" value="4" min="0" max="30" step="0.5"></div>
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
			for ( let i = 0; i < items.length; i++ ) {
				if ( token !== drawToken ) {
					return;
				}
				await drawUnit( {
					image: async ( m, side, x, y, w, h ) => g.drawImage( await lowImage( m, side ), x * scale, y * scale, w * scale, h * scale ),
					line: ( x1, y1, x2, y2, dash ) => {
						g.strokeStyle = '#111';
						g.lineWidth = Math.max( 0.6, 0.2 * scale );
						g.setLineDash( dash ? [ 1.5 * scale, 1.2 * scale ] : [] );
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
				}, items[ i ].m, sh.slots[ i ], sh );
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
		if ( st.marks ) {
			const L = 3;
			const off = 1;
			const x0 = slot.x;
			const x1 = slot.x + sh.unitW;
			const y0 = slot.y;
			const y1 = slot.y + card.h;
			// Corner crop marks, outside the card area.
			[ [ x0, y0, -1, -1 ], [ x1, y0, 1, -1 ], [ x0, y1, -1, 1 ], [ x1, y1, 1, 1 ] ].forEach( ( [ x, y, sx, sy ] ) => {
				d.line( x + sx * off, y, x + sx * ( off + L ), y );
				d.line( x, y + sy * off, x, y + sy * ( off + L ) );
			} );
			// Inner marks between front and back when not folding.
			if ( sh.pair && ! st.fold ) {
				const xm = slot.x + card.w;
				const xb = xm + Number( st.gap );
				[ xm, xb ].forEach( ( x ) => {
					d.line( x, y0 - off, x, y0 - off - L );
					d.line( x, y1 + off, x, y1 + off + L );
				} );
			}
			d.scissors( x0 - off - 2.2, y0 - off - 2.2, 2.6, 0 );
		}
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
				for ( let i = 0; i < items.length; i++ ) {
					await drawUnit( {
						image: async ( m, side, x, y, w, h ) => pdf.addImage( await getHi( m, side ), 'JPEG', x, y, w, h, undefined, 'FAST' ),
						line: ( x1, y1, x2, y2, dash ) => {
							pdf.setDrawColor( 17, 17, 17 );
							pdf.setLineWidth( 0.2 );
							if ( pdf.setLineDashPattern ) {
								pdf.setLineDashPattern( dash ? [ 1.5, 1.2 ] : [], 0 );
							}
							pdf.line( x1, y1, x2, y2 );
						},
						rect: ( x, y, w, h ) => {
							pdf.setDrawColor( 180, 180, 180 );
							pdf.setLineWidth( 0.15 );
							pdf.rect( x, y, w, h );
						},
						scissors: ( x, y, s ) => drawScissorsPdf( pdf, x, y, s ),
					}, items[ i ].m, sh.slots[ i ], sh );
				}
			}
			const name = ( P.project.name || 'cards' ).replace( /[^\w\- ]+/g, '' ).trim().replace( /\s+/g, '-' );
			pdf.save( `${ name }-print-sheets.pdf` );
		} else {
			// HTML print view with exact millimetre positions.
			let html = '';
			for ( let pg = 0; pg < pages; pg++ ) {
				let inner = '';
				const items = list.slice( pg * sh.perPage, ( pg + 1 ) * sh.perPage );
				for ( let i = 0; i < items.length; i++ ) {
					await drawUnit( {
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
						scissors: ( x, y ) => {
							inner += `<b class="sc" style="left:${ x - 1 }mm;top:${ y - 1.6 }mm">✂</b>`;
						},
					}, items[ i ].m, sh.slots[ i ], sh );
				}
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
				.ln.dash{border-style:dashed}
				.bd{position:absolute;outline:0.15mm solid #b4b4b4;box-sizing:border-box}
				.sc{position:absolute;font:3mm/1 Arial,sans-serif;color:#111}
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

/* Small scissor symbol drawn as vector shapes (no special font needed). */
function drawScissorsCanvas( g, x, y, s ) {
	g.save();
	g.strokeStyle = '#111';
	g.lineWidth = Math.max( 0.6, s * 0.12 );
	g.beginPath();
	g.arc( x + s * 0.22, y + s * 0.3, s * 0.18, 0, Math.PI * 2 );
	g.moveTo( x + s * 0.22 + s * 0.18, y + s * 0.7 );
	g.arc( x + s * 0.22, y + s * 0.7, s * 0.18, 0, Math.PI * 2 );
	g.moveTo( x + s * 0.36, y + s * 0.4 );
	g.lineTo( x + s, y + s * 0.85 );
	g.moveTo( x + s * 0.36, y + s * 0.6 );
	g.lineTo( x + s, y + s * 0.15 );
	g.stroke();
	g.restore();
}

function drawScissorsPdf( pdf, x, y, s ) {
	pdf.setDrawColor( 17, 17, 17 );
	pdf.setLineWidth( 0.18 );
	if ( pdf.setLineDashPattern ) {
		pdf.setLineDashPattern( [], 0 );
	}
	pdf.circle( x + s * 0.22, y + s * 0.3, s * 0.18 );
	pdf.circle( x + s * 0.22, y + s * 0.7, s * 0.18 );
	pdf.line( x + s * 0.36, y + s * 0.4, x + s, y + s * 0.85 );
	pdf.line( x + s * 0.36, y + s * 0.6, x + s, y + s * 0.15 );
}
