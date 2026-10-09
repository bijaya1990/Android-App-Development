/**
 * Downloads and printing, done in the browser so shared hosting is never loaded.
 * - 300 DPI at the true card size, optional 2 mm bleed.
 * - PDF (one page per side), JPG and PNG; many cards → ZIP named by ID and name.
 * - Small batches with a progress bar; very large PDFs are split into parts.
 * - Free-plan watermark drawn into the image when the server says so.
 */

import { api } from './api.js';
import { esc, icon, toast } from './ui.js';
import { renderSide, PRINT_DPI, MM_PER_INCH } from '../card/render.js';

const { __, sprintf } = window.wp.i18n;

const PDF_PART = 100; // cards per PDF file

function brand() {
	return getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-brand' ).trim() || '#4F46E5';
}

function fileName( m, side, ext ) {
	const clean = ( s ) => String( s || '' ).replace( /[^\w\-]+/g, '-' ).replace( /-+/g, '-' ).replace( /^-|-$/g, '' );
	return `${ clean( m.id_no ) || 'card' }-${ clean( m.name ) || 'person' }${ side ? '-' + side : '' }.${ ext }`;
}

/** Ask the server whether exports need the watermark (and count the download). */
async function watermarkFor( P, n ) {
	try {
		const res = await api( 'downloads/count', { method: 'POST', body: { n } } );
		return res.watermark ? { text: P.ctx.me.watermark.text, brand: brand() } : null;
	} catch ( e ) {
		// If the server cannot be reached, stay on the safe side.
		return P.ctx.me.state.watermark ? { text: P.ctx.me.watermark.text, brand: brand() } : null;
	}
}

/** Render one side of one person at print resolution. */
export async function renderPrint( P, member, side, watermark, dpi = PRINT_DPI ) {
	const canvas = document.createElement( 'canvas' );
	const bleedMm = P.project.fields.bleed ? 2 : 0;
	const res = await renderSide( canvas, P.layout()[ side ], Object.assign( P.renderOpts( side, member ), {
		pxPerMm: dpi / MM_PER_INCH,
		bleedMm,
		watermark,
		demo: false,
	} ) );
	return { canvas, bleedMm, size: P.size(), res };
}

function progress( title ) {
	const root = document.getElementById( 'modal-root' );
	const wrap = document.createElement( 'div' );
	wrap.className = 'modal-wrap';
	wrap.innerHTML = `<div class="modal" role="dialog" aria-modal="true" aria-label="${ esc( title ) }">
		<h2>${ esc( title ) }</h2>
		<p class="muted" data-msg>${ esc( __( 'Preparing…', 'pikacart' ) ) }</p>
		<div class="progress big"><span data-bar style="width:0%"></span></div>
		<p class="muted small">${ esc( __( 'Please keep this tab open. Large batches can take a minute.', 'pikacart' ) ) }</p>
		<div class="modal-actions"><button type="button" class="btn" data-cancel>${ esc( __( 'Cancel', 'pikacart' ) ) }</button></div>
	</div>`;
	root.appendChild( wrap );
	const state = { cancelled: false };
	wrap.querySelector( '[data-cancel]' ).addEventListener( 'click', () => {
		state.cancelled = true;
	} );
	return {
		state,
		set( done, total, text ) {
			wrap.querySelector( '[data-bar]' ).style.width = Math.round( ( done / Math.max( 1, total ) ) * 100 ) + '%';
			wrap.querySelector( '[data-msg]' ).textContent = text || sprintf( __( '%1$d of %2$d done', 'pikacart' ), done, total );
		},
		close() {
			wrap.remove();
		},
	};
}

const tick = () => new Promise( ( r ) => setTimeout( r, 0 ) );

function saveBlob( blob, name ) {
	const a = document.createElement( 'a' );
	a.href = URL.createObjectURL( blob );
	a.download = name;
	document.body.appendChild( a );
	a.click();
	setTimeout( () => {
		URL.revokeObjectURL( a.href );
		a.remove();
	}, 1500 );
}

/**
 * Download cards.
 * @param {object} P        Project context.
 * @param {Array}  members
 * @param {object} o        { format: 'pdf'|'png'|'jpg', sides: 'both'|'front'|'back' }
 */
export async function exportCards( P, members, o ) {
	if ( ! members.length ) {
		toast( __( 'There are no cards to download.', 'pikacart' ), 'error' );
		return;
	}
	if ( P.ctx.me.state.can_work === false ) {
		toast( __( 'Please subscribe to download cards.', 'pikacart' ), 'error' );
		return;
	}
	const sides = o.sides === 'both' ? [ 'front', 'back' ] : [ o.sides ];
	const total = members.length * sides.length;
	const ui = progress( __( 'Creating your cards', 'pikacart' ) );
	const wm = await watermarkFor( P, members.length );
	const base = ( P.project.name || 'cards' ).replace( /[^\w\- ]+/g, '' ).trim().replace( /\s+/g, '-' ) || 'cards';
	let done = 0;

	try {
		if ( o.format === 'pdf' ) {
			const { jsPDF } = window.jspdf;
			const parts = [];
			for ( let start = 0; start < members.length; start += PDF_PART ) {
				let pdf = null;
				for ( const m of members.slice( start, start + PDF_PART ) ) {
					for ( const side of sides ) {
						if ( ui.state.cancelled ) {
							throw { cancelled: true };
						}
						const { canvas, bleedMm, size } = await renderPrint( P, m, side, wm );
						const w = size.w + bleedMm * 2;
						const h = size.h + bleedMm * 2;
						const orient = w > h ? 'landscape' : 'portrait';
						if ( ! pdf ) {
							pdf = new jsPDF( { unit: 'mm', format: [ w, h ], orientation: orient, compress: true } );
						} else {
							pdf.addPage( [ w, h ], orient );
						}
						pdf.addImage( canvas.toDataURL( 'image/jpeg', 0.93 ), 'JPEG', 0, 0, w, h, undefined, 'FAST' );
						done++;
						ui.set( done, total );
						await tick();
					}
				}
				parts.push( pdf.output( 'blob' ) );
			}
			if ( parts.length === 1 ) {
				saveBlob( parts[ 0 ], members.length === 1 ? fileName( members[ 0 ], '', 'pdf' ) : `${ base }.pdf` );
			} else {
				ui.set( done, total, __( 'Packing the files…', 'pikacart' ) );
				const zip = new window.JSZip();
				parts.forEach( ( b, i ) => zip.file( `${ base }-part-${ i + 1 }.pdf`, b ) );
				saveBlob( await zip.generateAsync( { type: 'blob' } ), `${ base }-pdf.zip` );
			}
		} else {
			const ext = o.format === 'png' ? 'png' : 'jpg';
			const mime = ext === 'png' ? 'image/png' : 'image/jpeg';
			const single = members.length === 1 && sides.length === 1;
			const zip = single ? null : new window.JSZip();
			for ( const m of members ) {
				for ( const side of sides ) {
					if ( ui.state.cancelled ) {
						throw { cancelled: true };
					}
					const { canvas } = await renderPrint( P, m, side, wm );
					const blob = await new Promise( ( r ) => canvas.toBlob( r, mime, 0.95 ) );
					const name = fileName( m, sides.length > 1 ? side : side, ext );
					if ( single ) {
						saveBlob( blob, name );
					} else {
						zip.file( name, blob );
					}
					done++;
					ui.set( done, total );
					await tick();
				}
			}
			if ( zip ) {
				ui.set( done, total, __( 'Packing the ZIP…', 'pikacart' ) );
				saveBlob( await zip.generateAsync( { type: 'blob' } ), `${ base }-${ ext }.zip` );
			}
		}
		toast( wm ? __( 'Download ready. Free plan cards include the Pikacart watermark.', 'pikacart' ) : __( 'Download ready.', 'pikacart' ) );
	} catch ( e ) {
		if ( ! e.cancelled ) {
			toast( e.message || __( 'Could not create the files. Please try again with fewer cards.', 'pikacart' ), 'error' );
		}
	} finally {
		ui.close();
	}
}

/**
 * Print cards at exact size in a clean print view (one card per page).
 */
export async function printCards( P, members, sidesOpt = 'both' ) {
	if ( ! members.length ) {
		return;
	}
	// Open the window first so pop-up blockers allow it.
	const win = window.open( '', '_blank' );
	if ( ! win ) {
		toast( __( 'Please allow pop-ups to print.', 'pikacart' ), 'error' );
		return;
	}
	win.document.write( `<p style="font-family:sans-serif;padding:24px">${ esc( __( 'Preparing cards for printing…', 'pikacart' ) ) }</p>` );
	const sides = sidesOpt === 'both' ? [ 'front', 'back' ] : [ sidesOpt ];
	const wm = await watermarkFor( P, members.length );
	const imgs = [];
	let size = P.size();
	let bleed = 0;
	for ( const m of members ) {
		for ( const side of sides ) {
			const r = await renderPrint( P, m, side, wm );
			size = r.size;
			bleed = r.bleedMm;
			imgs.push( r.canvas.toDataURL( 'image/jpeg', 0.93 ) );
			await tick();
		}
	}
	const w = size.w + bleed * 2;
	const h = size.h + bleed * 2;
	win.document.open();
	win.document.write( `<!doctype html><html><head><title>${ esc( P.project.name ) }</title>
		<style>@page { size: ${ w }mm ${ h }mm; margin: 0; } html,body{margin:0;padding:0;background:#fff}
		img{display:block;width:${ w }mm;height:${ h }mm;page-break-after:always;break-after:page}
		img:last-child{page-break-after:auto;break-after:auto}
		.note{font:14px sans-serif;padding:12px;background:#fff6e0;color:#7a4b00} @media print{.note{display:none}}</style></head>
		<body><div class="note">${ esc( __( 'In the print dialog choose "Actual size" (100%) and no margins. Turn off headers and footers.', 'pikacart' ) ) }</div>
		${ imgs.map( ( src ) => `<img src="${ src }" alt="">` ).join( '' ) }
		<script>window.onload=function(){setTimeout(function(){window.print();},300);};<\/script></body></html>` );
	win.document.close();
}
