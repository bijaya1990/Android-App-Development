/**
 * Lazy thumbnails: draws a card preview into a canvas only when it scrolls
 * into view, in small batches so long galleries stay smooth.
 */

import { renderSide } from './render.js';
import { layoutFor } from './families.js';
import { demoVars, kindOf } from './data.js';

const queue = [];
let busy = false;
let observer = null;

async function pump() {
	if ( busy ) {
		return;
	}
	busy = true;
	while ( queue.length ) {
		const job = queue.shift();
		try {
			await job();
		} catch ( e ) {
			// Skip a broken preview, keep the rest.
		}
		// Let the page breathe between cards.
		await new Promise( ( r ) => setTimeout( r, 0 ) );
	}
	busy = false;
}

function io() {
	if ( ! observer ) {
		observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( e ) => {
					if ( e.isIntersecting && e.target._draw ) {
						const fn = e.target._draw;
						e.target._draw = null;
						observer.unobserve( e.target );
						queue.push( fn );
						pump();
					}
				} );
			},
			{ rootMargin: '300px' }
		);
	}
	return observer;
}

/**
 * Schedule a thumbnail.
 * @param {HTMLCanvasElement} canvas
 * @param {object} o  { template, subtype, orientation, palette, size {w,h}, side, width, org, index, vars, lazy }
 */
export function thumb( canvas, o ) {
	const draw = async () => {
		const size = o.size || ( o.orientation === 'landscape' ? { w: 86, h: 54 } : { w: 54, h: 86 } );
		const kind = kindOf( o.subtype );
		const layout = o.layout || layoutFor( o.template, o.orientation || 'portrait', size.w / size.h, kind );
		const vars = o.vars || demoVars( kind, o.index || 0, Object.assign( { subtype_name: o.subtype ? o.subtype.name : '' }, o.org || {} ) );
		const dpr = Math.min( 2, window.devicePixelRatio || 1 );
		await renderSide( canvas, layout[ o.side || 'front' ], {
			sizeMm: size,
			width: ( o.width || 180 ) * dpr,
			palette: o.palette,
			vars,
			demo: ! o.real,
			side: o.side || 'front',
			fields: o.fields || null,
			flags: o.flags,
			watermark: o.watermark || null,
		} );
		canvas.style.aspectRatio = `${ size.w } / ${ size.h }`;
		canvas.classList.add( 'is-drawn' );
	};
	if ( o.lazy === false || typeof IntersectionObserver === 'undefined' ) {
		queue.push( draw );
		pump();
		return;
	}
	canvas._draw = draw;
	io().observe( canvas );
}
