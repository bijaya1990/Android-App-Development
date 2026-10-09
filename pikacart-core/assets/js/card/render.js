/**
 * Pikacart card renderer.
 *
 * Draws a card layout (JSON) onto a canvas at any size and resolution.
 * The same code draws gallery thumbnails, the editor, 300 DPI downloads,
 * print sheets and the homepage showcase, so what you see is what prints.
 *
 * Layout format (one side):
 *   { bg: <colour|gradient|{img,fit}>, els: [ element, ... ] }
 * Every element has a box in % of the card: x, y, w, h (0-100).
 * Sizes (font, radius, stroke) use "u" = 1% of the card's short side.
 *
 * Element types: rect, ellipse, path, text, stack, img, qr, bar, pattern, table.
 * Common keys: id, hide, rot (degrees), op (opacity 0-1), field (hide when the
 * field is switched off or empty), lock (editor only).
 */

import { tokens, color, paint } from './color.js';
import { fontCss, ensureFonts } from './fonts.js';
import { drawQR, drawBarcode } from './codes.js';
import { drawAvatar, drawPhotoPlaceholder } from './avatar.js';
import { drawWatermark } from './watermark.js';

export const MM_PER_INCH = 25.4;
export const PRINT_DPI = 300;

/* ---------- Images ---------- */

const images = new Map();

export function loadImage( url ) {
	if ( ! url ) {
		return Promise.resolve( null );
	}
	if ( images.has( url ) ) {
		return images.get( url );
	}
	const p = new Promise( ( resolve ) => {
		const img = new Image();
		img.decoding = 'async';
		img.onload = () => resolve( img );
		img.onerror = () => resolve( null );
		img.src = url;
	} );
	images.set( url, p );
	return p;
}

/** Synchronous lookup once loadImage() has resolved. */
const ready = new Map();
async function preloadImage( url ) {
	const img = await loadImage( url );
	ready.set( url, img );
	return img;
}

/* ---------- Placeholders ---------- */

const TOKEN_RE = /\{\{\s*([a-z0-9_]+)\s*\}\}/gi;

export function fill( text, vars ) {
	return String( text ?? '' ).replace( TOKEN_RE, ( m, k ) => {
		const v = vars[ k.toLowerCase() ];
		return v === undefined || v === null ? '' : String( v );
	} );
}

function hasToken( text ) {
	return /\{\{/.test( String( text || '' ) );
}

function fieldOn( key, opts ) {
	if ( ! key || ! opts.fields ) {
		return true;
	}
	return opts.fields.includes( key );
}

/** Is this element drawn with these options? */
export function isVisible( el, opts ) {
	if ( el.hide ) {
		return false;
	}
	if ( el.field && ! fieldOn( el.field, opts ) ) {
		return false;
	}
	const flags = opts.flags || {};
	const side = opts.side || 'front';
	if ( el.t === 'qr' && flags[ 'qr_' + side ] === false ) {
		return false;
	}
	if ( el.t === 'bar' && flags[ 'bar_' + side ] === false ) {
		return false;
	}
	if ( el.t === 'table' && el.renewal && flags.renewal === false ) {
		return false;
	}
	if ( el.t === 'text' && hasToken( el.text ) && ! el.keep ) {
		return fill( el.text, opts.vars || {} ).trim() !== '';
	}
	if ( el.field && el.t === 'text' ) {
		return fill( `{{${ el.field }}}`, opts.vars || {} ).trim() !== '';
	}
	if ( el.t === 'img' && /^\{\{\s*(logo|sign|seal)\s*\}\}$/.test( el.src || '' ) ) {
		return !! fill( el.src, opts.vars || {} );
	}
	return true;
}

/* ---------- Geometry ---------- */

export function boxPx( el, W, H ) {
	return { x: ( el.x / 100 ) * W, y: ( el.y / 100 ) * H, w: ( el.w / 100 ) * W, h: ( el.h / 100 ) * H };
}

function rotate( ctx, el, b ) {
	if ( el.rot ) {
		const cx = b.x + b.w / 2;
		const cy = b.y + b.h / 2;
		ctx.translate( cx, cy );
		ctx.rotate( ( el.rot * Math.PI ) / 180 );
		ctx.translate( -cx, -cy );
	}
}

function roundRectPath( ctx, x, y, w, h, r ) {
	const rr = Array.isArray( r ) ? r : [ r, r, r, r ];
	const m = Math.min( w, h ) / 2;
	const [ tl, tr, br, bl ] = rr.map( ( v ) => Math.max( 0, Math.min( v || 0, m ) ) );
	ctx.moveTo( x + tl, y );
	ctx.lineTo( x + w - tr, y );
	ctx.arcTo( x + w, y, x + w, y + tr, tr );
	ctx.lineTo( x + w, y + h - br );
	ctx.arcTo( x + w, y + h, x + w - br, y + h, br );
	ctx.lineTo( x + bl, y + h );
	ctx.arcTo( x, y + h, x, y + h - bl, bl );
	ctx.lineTo( x, y + tl );
	ctx.arcTo( x, y, x + tl, y, tl );
	ctx.closePath();
}

/** Build a clip/frame path for an image or shape. */
function shapePath( ctx, shape, b, radius ) {
	ctx.beginPath();
	const { x, y, w, h } = b;
	switch ( shape ) {
		case 'circle':
			ctx.ellipse( x + w / 2, y + h / 2, w / 2, h / 2, 0, 0, Math.PI * 2 );
			break;
		case 'arch':
			ctx.moveTo( x, y + h );
			ctx.lineTo( x, y + w / 2 );
			ctx.arc( x + w / 2, y + w / 2, w / 2, Math.PI, 0 );
			ctx.lineTo( x + w, y + h );
			ctx.closePath();
			break;
		case 'hex': {
			const cx = x + w / 2;
			ctx.moveTo( cx, y );
			ctx.lineTo( x + w, y + h * 0.25 );
			ctx.lineTo( x + w, y + h * 0.75 );
			ctx.lineTo( cx, y + h );
			ctx.lineTo( x, y + h * 0.75 );
			ctx.lineTo( x, y + h * 0.25 );
			ctx.closePath();
			break;
		}
		case 'cut': {
			const c = Math.min( w, h ) * 0.18;
			ctx.moveTo( x + c, y );
			ctx.lineTo( x + w, y );
			ctx.lineTo( x + w, y + h - c );
			ctx.lineTo( x + w - c, y + h );
			ctx.lineTo( x, y + h );
			ctx.lineTo( x, y + c );
			ctx.closePath();
			break;
		}
		case 'shield':
			ctx.moveTo( x, y );
			ctx.lineTo( x + w, y );
			ctx.lineTo( x + w, y + h * 0.55 );
			ctx.quadraticCurveTo( x + w, y + h * 0.85, x + w / 2, y + h );
			ctx.quadraticCurveTo( x, y + h * 0.85, x, y + h * 0.55 );
			ctx.closePath();
			break;
		case 'squircle':
			roundRectPath( ctx, x, y, w, h, Math.min( w, h ) * 0.32 );
			break;
		case 'leaf':
			roundRectPath( ctx, x, y, w, h, [ Math.min( w, h ) * 0.45, 0, Math.min( w, h ) * 0.45, 0 ] );
			break;
		default:
			roundRectPath( ctx, x, y, w, h, radius || 0 );
	}
}

/** Parse a path string whose numbers are % of the element box. */
function tracePath( ctx, d, b, mapX, mapY ) {
	const parts = String( d ).match( /[MLHVCQSZmlhvcqsz]|-?\d*\.?\d+(?:e-?\d+)?/g ) || [];
	let i = 0;
	let cmd = '';
	let lx = 0;
	let ly = 0;
	const X = ( v ) => mapX( b.x + ( v / 100 ) * b.w );
	const Y = ( v ) => mapY( b.y + ( v / 100 ) * b.h );
	const num = () => parseFloat( parts[ i++ ] );
	ctx.beginPath();
	while ( i < parts.length ) {
		if ( /[A-Za-z]/.test( parts[ i ] ) ) {
			cmd = parts[ i++ ].toUpperCase();
			if ( cmd === 'Z' ) {
				ctx.closePath();
				continue;
			}
		}
		switch ( cmd ) {
			case 'M':
				lx = num();
				ly = num();
				ctx.moveTo( X( lx ), Y( ly ) );
				cmd = 'L';
				break;
			case 'L':
				lx = num();
				ly = num();
				ctx.lineTo( X( lx ), Y( ly ) );
				break;
			case 'H':
				lx = num();
				ctx.lineTo( X( lx ), Y( ly ) );
				break;
			case 'V':
				ly = num();
				ctx.lineTo( X( lx ), Y( ly ) );
				break;
			case 'C': {
				const a = num();
				const b2 = num();
				const c = num();
				const d2 = num();
				lx = num();
				ly = num();
				ctx.bezierCurveTo( X( a ), Y( b2 ), X( c ), Y( d2 ), X( lx ), Y( ly ) );
				break;
			}
			case 'Q': {
				const a = num();
				const b2 = num();
				lx = num();
				ly = num();
				ctx.quadraticCurveTo( X( a ), Y( b2 ), X( lx ), Y( ly ) );
				break;
			}
			default:
				i++;
		}
	}
}

/* ---------- Text ---------- */

function measure( ctx, text, ls ) {
	return ctx.measureText( text ).width + ( ls ? ls * Math.max( 0, [ ...text ].length - 1 ) : 0 );
}

function drawSpaced( ctx, text, x, y, ls, align ) {
	if ( ! ls ) {
		ctx.textAlign = align;
		ctx.fillText( text, x, y );
		return;
	}
	const total = measure( ctx, text, ls );
	let cx = align === 'center' ? x - total / 2 : align === 'right' ? x - total : x;
	ctx.textAlign = 'left';
	for ( const ch of text ) {
		ctx.fillText( ch, cx, y );
		cx += ctx.measureText( ch ).width + ls;
	}
}

function wrap( ctx, text, maxW, maxLines, ls ) {
	const out = [];
	const paragraphs = String( text ).split( /\n/ );
	for ( const para of paragraphs ) {
		const words = para.split( /\s+/ ).filter( Boolean );
		let line = '';
		for ( const word of words ) {
			const test = line ? line + ' ' + word : word;
			if ( measure( ctx, test, ls ) <= maxW || ! line ) {
				line = test;
			} else {
				out.push( line );
				line = word;
			}
		}
		out.push( line );
	}
	return { lines: out.slice( 0, maxLines ), overflow: out.length > maxLines };
}

/**
 * Find a font size at which the text fits the box (auto-shrink), wrapping
 * up to maxLines; long text that still does not fit ends with "…".
 */
export function fitText( ctx, text, o ) {
	const min = o.size * ( o.minScale || 0.45 );
	let size = o.size;
	let res;
	for ( let k = 0; k < 30; k++ ) {
		ctx.font = fontCss( { weight: o.weight, size, family: o.family, italic: o.italic } );
		const ls = ( o.ls || 0 ) * size;
		res = wrap( ctx, text, o.maxW, o.maxLines, ls );
		const widest = Math.max( ...res.lines.map( ( l ) => measure( ctx, l, ls ) ) );
		const height = res.lines.length * size * o.lh;
		if ( ( ! res.overflow && widest <= o.maxW + 0.5 && height <= o.maxH + 0.5 ) || ! o.shrink || size <= min ) {
			break;
		}
		size = Math.max( min, size * 0.93 );
	}
	ctx.font = fontCss( { weight: o.weight, size, family: o.family, italic: o.italic } );
	const ls = ( o.ls || 0 ) * size;
	// Ellipsize anything still too long.
	const lines = res.lines.map( ( l, idx ) => {
		const last = idx === res.lines.length - 1;
		if ( measure( ctx, l, ls ) <= o.maxW && ! ( last && res.overflow ) ) {
			return l;
		}
		let s = l;
		while ( s.length > 1 && measure( ctx, s + '…', ls ) > o.maxW ) {
			s = s.slice( 0, -1 );
		}
		return s.trimEnd() + '…';
	} );
	return { size, lines, ls };
}

function drawTextBlock( ctx, el, b, u, map, text ) {
	const o = {
		family: el.font || 'Inter',
		weight: el.wt || 400,
		italic: !! el.it,
		size: ( el.size || 4 ) * u,
		maxW: b.w,
		maxH: b.h,
		maxLines: el.lines || 1,
		lh: el.lh || 1.2,
		ls: el.ls || 0,
		shrink: el.shrink !== false,
		minScale: el.min || 0.45,
	};
	const str = el.up ? text.toUpperCase() : text;
	const r = fitText( ctx, str, o );
	const lineH = r.size * o.lh;
	const total = r.lines.length * lineH;
	let y = b.y + ( lineH - r.size ) / 2;
	if ( el.va === 'middle' || el.va === undefined ) {
		y = b.y + ( b.h - total ) / 2 + ( lineH - r.size ) / 2;
	} else if ( el.va === 'bottom' ) {
		y = b.y + b.h - total + ( lineH - r.size ) / 2;
	}
	const align = el.al || 'left';
	const x = align === 'center' ? b.x + b.w / 2 : align === 'right' ? b.x + b.w : b.x;
	ctx.fillStyle = paint( ctx, el.c || '@t', map, b );
	ctx.textBaseline = 'top';
	r.lines.forEach( ( line, i ) => drawSpaced( ctx, line, x, y + i * lineH, r.ls, align ) );
}

/* ---------- Stack of label/value rows (collapses empty rows) ---------- */

export function visibleRows( el, opts ) {
	return ( el.rows || [] ).filter( ( row ) => {
		if ( row.f && ! fieldOn( row.f, opts ) ) {
			return false;
		}
		return fill( row.v, opts.vars || {} ).trim() !== '';
	} );
}

function drawStack( ctx, el, b, u, map, opts ) {
	const rows = visibleRows( el, opts );
	if ( ! rows.length ) {
		return;
	}
	const mode = el.mode || 'table';
	const family = el.font || 'Inter';
	const lh = el.lh || ( mode === 'stacked' ? 2.25 : 1.55 );
	let size = ( el.size || 3.6 ) * u;
	const need = ( s ) => rows.length * s * lh;
	while ( need( size ) > b.h && size > ( el.size || 3.6 ) * u * 0.55 ) {
		size *= 0.94;
	}
	const rowH = Math.min( size * lh, b.h / rows.length );
	const labW = mode === 'table' ? b.w * ( ( el.lw || 38 ) / 100 ) : 0;
	const align = el.al || 'left';
	ctx.textBaseline = 'top';
	let y = b.y + ( el.va === 'middle' ? ( b.h - rowH * rows.length ) / 2 : 0 );
	rows.forEach( ( row ) => {
		const value = fill( row.v, opts.vars || {} );
		const rawLabel = fill( row.l || '', opts.vars || {} );
		const label = el.up ? rawLabel.toUpperCase() : rawLabel;
		if ( mode === 'table' ) {
			ctx.font = fontCss( { weight: el.lwt || 500, size: size * 0.92, family } );
			ctx.fillStyle = color( el.lc || '@m', map );
			ctx.textAlign = 'left';
			ctx.fillText( fitLine( ctx, label, labW - size * 0.6 ), b.x, y + size * 0.06 );
			ctx.fillText( ':', b.x + labW - size * 0.45, y + size * 0.06 );
			ctx.font = fontCss( { weight: el.vwt || 600, size, family } );
			ctx.fillStyle = color( el.vc || '@t', map );
			ctx.fillText( fitLine( ctx, value, b.w - labW ), b.x + labW, y );
		} else if ( mode === 'stacked' ) {
			ctx.font = fontCss( { weight: el.lwt || 600, size: size * 0.7, family } );
			ctx.fillStyle = color( el.lc || '@m', map );
			const x = align === 'center' ? b.x + b.w / 2 : b.x;
			ctx.textAlign = align === 'center' ? 'center' : 'left';
			ctx.fillText( fitLine( ctx, label.toUpperCase(), b.w ), x, y );
			ctx.font = fontCss( { weight: el.vwt || 600, size, family } );
			ctx.fillStyle = color( el.vc || '@t', map );
			ctx.fillText( fitLine( ctx, value, b.w ), x, y + size * 0.85 );
		} else {
			// inline: "Label: Value" or values only.
			const text = mode === 'value' ? value : `${ label }: ${ value }`;
			ctx.font = fontCss( { weight: el.vwt || 500, size, family } );
			ctx.fillStyle = color( el.vc || '@t', map );
			const x = align === 'center' ? b.x + b.w / 2 : align === 'right' ? b.x + b.w : b.x;
			ctx.textAlign = align;
			ctx.fillText( fitLine( ctx, text, b.w ), x, y );
		}
		y += rowH;
	} );
}

function fitLine( ctx, text, maxW ) {
	if ( ctx.measureText( text ).width <= maxW ) {
		return text;
	}
	let s = text;
	while ( s.length > 1 && ctx.measureText( s + '…' ).width > maxW ) {
		s = s.slice( 0, -1 );
	}
	return s + '…';
}

/* ---------- Main ---------- */

/**
 * Load fonts and images a side needs. Call before drawSide().
 */
export async function prepare( layoutSide, opts ) {
	const vars = opts.vars || {};
	const fonts = [ [ 'Inter', 400 ], [ 'Inter', 700 ] ];
	const urls = [];
	const els = ( layoutSide && layoutSide.els ) || [];
	els.forEach( ( el ) => {
		if ( el.font ) {
			fonts.push( [ el.font, el.wt || el.vwt || 400 ] );
			fonts.push( [ el.font, 700 ] );
		}
		if ( el.t === 'img' ) {
			const src = fill( el.src, vars );
			if ( src ) {
				urls.push( src );
			}
		}
	} );
	const bg = layoutSide && layoutSide.bg;
	if ( bg && typeof bg === 'object' && bg.img ) {
		urls.push( fill( bg.img, vars ) );
	}
	await Promise.all( [ ensureFonts( fonts ), ...urls.map( preloadImage ) ] );
}

/**
 * Draw one side synchronously (fonts and images must be prepared).
 *
 * @param {CanvasRenderingContext2D} ctx
 * @param {object} side     { bg, els }
 * @param {object} opts     { W, H (px), bleed (px), palette, vars, fields, flags,
 *                            side, demo, watermark, skipIds }
 */
export function drawSide( ctx, sideLayout, opts ) {
	const W = opts.W;
	const H = opts.H;
	const bleed = opts.bleed || 0;
	const u = Math.min( W, H ) / 100;
	const map = tokens( opts.palette || {} );
	const vars = opts.vars || {};

	ctx.save();
	ctx.translate( bleed, bleed );
	const edgeX = ( v ) => ( v <= 0.01 ? v - bleed : v >= W - 0.01 ? v + bleed : v );
	const edgeY = ( v ) => ( v <= 0.01 ? v - bleed : v >= H - 0.01 ? v + bleed : v );
	const full = { x: -bleed, y: -bleed, w: W + bleed * 2, h: H + bleed * 2 };

	// Background.
	const bg = sideLayout.bg || '@bg';
	if ( bg && typeof bg === 'object' && bg.img ) {
		ctx.fillStyle = '#ffffff';
		ctx.fillRect( full.x, full.y, full.w, full.h );
		const img = ready.get( fill( bg.img, vars ) );
		if ( img ) {
			drawImageFit( ctx, img, full, 'cover' );
		}
	} else {
		ctx.fillStyle = paint( ctx, bg, map, full );
		ctx.fillRect( full.x, full.y, full.w, full.h );
	}

	for ( const el of sideLayout.els || [] ) {
		if ( opts.skipIds && opts.skipIds.has( el.id ) ) {
			continue;
		}
		if ( ! isVisible( el, opts ) ) {
			continue;
		}
		const b = boxPx( el, W, H );
		ctx.save();
		ctx.globalAlpha = el.op ?? 1;
		rotate( ctx, el, b );
		try {
			drawElement( ctx, el, b, u, map, vars, opts, edgeX, edgeY );
		} catch ( e ) {
			// One broken element must never stop the card from drawing.
		}
		ctx.restore();
	}
	ctx.restore();

	if ( opts.watermark ) {
		ctx.save();
		ctx.translate( bleed, bleed );
		drawWatermark( ctx, W, H, opts.watermark );
		// Keep the QR code scannable on free cards so verification can be tried.
		for ( const el of sideLayout.els || [] ) {
			if ( el.t !== 'qr' || ! isVisible( el, opts ) ) {
				continue;
			}
			const b = boxPx( el, W, H );
			ctx.save();
			rotate( ctx, el, b );
			try {
				drawElement( ctx, el, b, u, map, vars, opts, edgeX, edgeY );
			} catch ( e ) {
				// Ignore.
			}
			ctx.restore();
		}
		ctx.restore();
	}
}

function drawImageFit( ctx, img, b, fit ) {
	const ir = img.width / img.height;
	const br = b.w / b.h;
	let w;
	let h;
	if ( ( fit === 'contain' ) === ( ir > br ) ) {
		w = b.w;
		h = b.w / ir;
	} else {
		h = b.h;
		w = b.h * ir;
	}
	ctx.drawImage( img, b.x + ( b.w - w ) / 2, b.y + ( b.h - h ) / 2, w, h );
}

function shadowOn( ctx, el, u ) {
	if ( el.sh ) {
		ctx.shadowColor = 'rgba(15,20,40,0.22)';
		ctx.shadowBlur = el.sh * u;
		ctx.shadowOffsetY = el.sh * u * 0.35;
	}
}

function drawElement( ctx, el, b, u, map, vars, opts, edgeX, edgeY ) {
	switch ( el.t ) {
		case 'rect': {
			const x0 = edgeX( b.x );
			const y0 = edgeY( b.y );
			const x1 = edgeX( b.x + b.w );
			const y1 = edgeY( b.y + b.h );
			const r = Array.isArray( el.r ) ? el.r.map( ( v ) => v * u ) : ( el.r || 0 ) * u;
			ctx.beginPath();
			roundRectPath( ctx, x0, y0, x1 - x0, y1 - y0, r );
			shadowOn( ctx, el, u );
			if ( el.f ) {
				ctx.fillStyle = paint( ctx, el.f, map, b );
				ctx.fill();
			}
			ctx.shadowColor = 'transparent';
			if ( el.s ) {
				ctx.strokeStyle = color( el.s, map );
				ctx.lineWidth = ( el.sw || 0.4 ) * u;
				if ( el.dash ) {
					ctx.setLineDash( el.dash.map( ( v ) => v * u ) );
				}
				ctx.stroke();
			}
			break;
		}
		case 'ellipse': {
			const rx = el.circ ? Math.min( b.w, b.h ) / 2 : Math.abs( b.w / 2 );
			const ry = el.circ ? rx : Math.abs( b.h / 2 );
			ctx.beginPath();
			ctx.ellipse( b.x + b.w / 2, b.y + b.h / 2, rx, ry, 0, 0, Math.PI * 2 );
			shadowOn( ctx, el, u );
			if ( el.f ) {
				ctx.fillStyle = paint( ctx, el.f, map, b );
				ctx.fill();
			}
			ctx.shadowColor = 'transparent';
			if ( el.s ) {
				ctx.strokeStyle = color( el.s, map );
				ctx.lineWidth = ( el.sw || 0.4 ) * u;
				ctx.stroke();
			}
			break;
		}
		case 'path':
			tracePath( ctx, el.d, b, el.s && ! el.f ? ( v ) => v : edgeX, el.s && ! el.f ? ( v ) => v : edgeY );
			shadowOn( ctx, el, u );
			if ( el.f ) {
				ctx.fillStyle = paint( ctx, el.f, map, b );
				ctx.fill();
			}
			ctx.shadowColor = 'transparent';
			if ( el.s ) {
				ctx.strokeStyle = color( el.s, map );
				ctx.lineWidth = ( el.sw || 0.4 ) * u;
				ctx.lineCap = 'round';
				if ( el.dash ) {
					ctx.setLineDash( el.dash.map( ( v ) => v * u ) );
				}
				ctx.stroke();
			}
			break;
		case 'text':
			drawTextBlock( ctx, el, b, u, map, fill( el.text, vars ) );
			break;
		case 'stack':
			drawStack( ctx, el, b, u, map, opts );
			break;
		case 'img':
			drawImg( ctx, el, b, u, map, vars, opts );
			break;
		case 'qr': {
			const s = Math.min( b.w, b.h );
			const value = fill( el.v || '{{verify_url}}', vars );
			drawQR( ctx, value, b.x + ( b.w - s ) / 2, b.y + ( b.h - s ) / 2, s, color( el.c || '@k', map ), el.bg === '' ? '' : color( el.bg || '@w', map ) );
			break;
		}
		case 'bar': {
			if ( el.bg ) {
				ctx.fillStyle = color( el.bg, map );
				ctx.fillRect( b.x, b.y, b.w, b.h );
			}
			drawBarcode( ctx, fill( el.v || '{{id_no}}', vars ), b.x, b.y, b.w, b.h, color( el.c || '@k', map ), !! el.txt, ( el.ts || 0 ) * u );
			break;
		}
		case 'pattern':
			drawPattern( ctx, el, b, u, map );
			break;
		case 'table':
			drawTable( ctx, el, b, u, map );
			break;
	}
}

function drawImg( ctx, el, b0, u, map, vars, opts ) {
	// Round frames stay round on every card size.
	let b = b0;
	if ( el.shape === 'circle' ) {
		const s = Math.min( b0.w, b0.h );
		b = { x: b0.x + ( b0.w - s ) / 2, y: b0.y + ( b0.h - s ) / 2, w: s, h: s };
	}
	const src = fill( el.src, vars );
	const isPhoto = /\{\{\s*photo\s*\}\}/.test( el.src || '' );
	const img = src ? ready.get( src ) : null;
	const r = ( el.r || 0 ) * u;

	// Outer ring (frame with a gap).
	if ( el.ring ) {
		const g = ( el.rg || 1.2 ) * u;
		ctx.save();
		shapePath( ctx, el.shape, { x: b.x - g, y: b.y - g, w: b.w + g * 2, h: b.h + g * 2 }, r + g );
		ctx.strokeStyle = color( el.ring, map );
		ctx.lineWidth = ( el.rw || 0.6 ) * u;
		ctx.stroke();
		ctx.restore();
	}

	ctx.save();
	shapePath( ctx, el.shape, b, r );
	if ( el.sh ) {
		ctx.save();
		shadowOn( ctx, el, u );
		ctx.fillStyle = '#ffffff';
		ctx.fill();
		ctx.restore();
	}
	ctx.clip();
	if ( el.bgc ) {
		ctx.fillStyle = color( el.bgc, map );
		ctx.fillRect( b.x, b.y, b.w, b.h );
	}
	if ( img ) {
		drawImageFit( ctx, img, b, el.fit || ( isPhoto ? 'cover' : 'contain' ) );
	} else if ( isPhoto ) {
		if ( opts.demo ) {
			drawAvatar( ctx, b.x, b.y, b.w, b.h, vars.name || 'demo', { female: vars.gender === 'f' } );
		} else {
			drawPhotoPlaceholder( ctx, b.x, b.y, b.w, b.h );
		}
	} else if ( opts.editor ) {
		ctx.fillStyle = 'rgba(100,116,139,0.12)';
		ctx.fillRect( b.x, b.y, b.w, b.h );
	}
	ctx.restore();

	if ( el.bc && el.bw ) {
		ctx.save();
		shapePath( ctx, el.shape, b, r );
		ctx.strokeStyle = color( el.bc, map );
		ctx.lineWidth = el.bw * u;
		ctx.stroke();
		ctx.restore();
	}
}

function drawPattern( ctx, el, b, u, map ) {
	ctx.save();
	ctx.beginPath();
	if ( el.shape === 'circle' ) {
		ctx.ellipse( b.x + b.w / 2, b.y + b.h / 2, b.w / 2, b.h / 2, 0, 0, Math.PI * 2 );
	} else {
		ctx.rect( b.x, b.y, b.w, b.h );
	}
	ctx.clip();
	const gap = ( el.gap || 3 ) * u;
	const size = ( el.size || 0.6 ) * u;
	ctx.fillStyle = color( el.c || '@w/25', map );
	ctx.strokeStyle = color( el.c || '@w/25', map );
	ctx.lineWidth = size;
	switch ( el.kind ) {
		case 'lines':
			for ( let x = b.x - b.h; x < b.x + b.w + b.h; x += gap ) {
				ctx.beginPath();
				ctx.moveTo( x, b.y + b.h );
				ctx.lineTo( x + b.h, b.y );
				ctx.stroke();
			}
			break;
		case 'grid':
			for ( let x = b.x; x <= b.x + b.w; x += gap ) {
				ctx.beginPath();
				ctx.moveTo( x, b.y );
				ctx.lineTo( x, b.y + b.h );
				ctx.stroke();
			}
			for ( let y = b.y; y <= b.y + b.h; y += gap ) {
				ctx.beginPath();
				ctx.moveTo( b.x, y );
				ctx.lineTo( b.x + b.w, y );
				ctx.stroke();
			}
			break;
		case 'rings': {
			const cx = b.x + ( el.cx ?? 100 ) / 100 * b.w;
			const cy = b.y + ( el.cy ?? 0 ) / 100 * b.h;
			for ( let rr = gap; rr < Math.max( b.w, b.h ) * 1.6; rr += gap ) {
				ctx.beginPath();
				ctx.arc( cx, cy, rr, 0, Math.PI * 2 );
				ctx.stroke();
			}
			break;
		}
		case 'chevron':
			for ( let y = b.y - gap; y < b.y + b.h + gap; y += gap ) {
				ctx.beginPath();
				for ( let x = b.x - gap; x < b.x + b.w + gap; x += gap ) {
					ctx.lineTo( x, y );
					ctx.lineTo( x + gap / 2, y - gap / 2 );
				}
				ctx.stroke();
			}
			break;
		case 'tri':
			for ( let y = b.y; y < b.y + b.h + gap; y += gap ) {
				for ( let x = b.x + ( ( ( y - b.y ) / gap ) % 2 ? gap / 2 : 0 ); x < b.x + b.w + gap; x += gap ) {
					ctx.beginPath();
					ctx.moveTo( x, y - size * 1.4 );
					ctx.lineTo( x + size * 1.3, y + size );
					ctx.lineTo( x - size * 1.3, y + size );
					ctx.closePath();
					ctx.fill();
				}
			}
			break;
		default:
			// dots
			for ( let y = b.y + gap / 2; y < b.y + b.h; y += gap ) {
				for ( let x = b.x + gap / 2; x < b.x + b.w; x += gap ) {
					ctx.beginPath();
					ctx.arc( x, y, size, 0, Math.PI * 2 );
					ctx.fill();
				}
			}
	}
	ctx.restore();
}

function drawTable( ctx, el, b, u, map ) {
	const cols = el.cols || [ 'Session', 'Signature' ];
	const rows = el.n || 3;
	const rh = b.h / ( rows + 1 );
	const size = Math.min( ( el.size || 2.6 ) * u, rh * 0.62 );
	ctx.fillStyle = color( el.hf || '@lt', map );
	ctx.fillRect( b.x, b.y, b.w, rh );
	ctx.strokeStyle = color( el.s || '@p/60', map );
	ctx.lineWidth = Math.max( 1, 0.25 * u );
	ctx.strokeRect( b.x, b.y, b.w, b.h );
	for ( let i = 1; i <= rows; i++ ) {
		ctx.beginPath();
		ctx.moveTo( b.x, b.y + rh * i );
		ctx.lineTo( b.x + b.w, b.y + rh * i );
		ctx.stroke();
	}
	const cw = b.w / cols.length;
	for ( let c = 1; c < cols.length; c++ ) {
		ctx.beginPath();
		ctx.moveTo( b.x + cw * c, b.y );
		ctx.lineTo( b.x + cw * c, b.y + b.h );
		ctx.stroke();
	}
	ctx.font = fontCss( { weight: 700, size, family: el.font || 'Inter' } );
	ctx.fillStyle = color( el.c || '@t', map );
	ctx.textAlign = 'center';
	ctx.textBaseline = 'middle';
	cols.forEach( ( label, c ) => ctx.fillText( fitLine( ctx, label, cw - size ), b.x + cw * c + cw / 2, b.y + rh / 2 ) );
}

/**
 * Convenience: prepare and draw a side onto a canvas.
 *
 * @param {HTMLCanvasElement} canvas
 * @param {object} side    { bg, els }
 * @param {object} opts    sizeMm {w,h}, and either pxPerMm or width (px);
 *                         bleedMm, palette, vars, fields, flags, side, demo, watermark.
 * @return {Promise<{W:number,H:number,scale:number}>}
 */
export async function renderSide( canvas, side, opts ) {
	const size = opts.sizeMm || { w: 54, h: 86 };
	const scale = opts.pxPerMm || ( opts.width ? opts.width / size.w : PRINT_DPI / MM_PER_INCH );
	const W = Math.round( size.w * scale );
	const H = Math.round( size.h * scale );
	const bleed = Math.round( ( opts.bleedMm || 0 ) * scale );
	await prepare( side, opts );
	canvas.width = W + bleed * 2;
	canvas.height = H + bleed * 2;
	const ctx = canvas.getContext( '2d' );
	ctx.imageSmoothingQuality = 'high';
	drawSide( ctx, side, Object.assign( {}, opts, { W, H, bleed } ) );
	return { W, H, scale, bleed };
}

/** Pixels per millimetre for a given DPI. */
export function pxPerMm( dpi = PRINT_DPI ) {
	return dpi / MM_PER_INCH;
}
