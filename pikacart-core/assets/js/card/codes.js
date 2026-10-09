/**
 * Real, scannable QR codes (qrcode-generator) and Code 128 barcodes (JsBarcode),
 * drawn crisply at any resolution. Both libraries are bundled locally.
 */

/**
 * Draw a QR code.
 * @param {CanvasRenderingContext2D} ctx
 * @param {string} value  Text or URL.
 * @param {number} x
 * @param {number} y
 * @param {number} size   Width = height in pixels (includes a small quiet zone).
 * @param {string} dark
 * @param {string} light  Background ('' for transparent).
 */
export function drawQR( ctx, value, x, y, size, dark = '#000000', light = '#ffffff' ) {
	if ( ! window.qrcode || ! value ) {
		return;
	}
	const qr = window.qrcode( 0, 'M' );
	qr.addData( String( value ) );
	qr.make();
	const n = qr.getModuleCount();
	const quiet = 2;
	const cell = size / ( n + quiet * 2 );
	if ( light ) {
		ctx.fillStyle = light;
		ctx.fillRect( x, y, size, size );
	}
	ctx.fillStyle = dark;
	for ( let r = 0; r < n; r++ ) {
		for ( let c = 0; c < n; c++ ) {
			if ( qr.isDark( r, c ) ) {
				// Slight overlap avoids hairline gaps between modules.
				ctx.fillRect( x + ( c + quiet ) * cell, y + ( r + quiet ) * cell, cell + 0.35, cell + 0.35 );
			}
		}
	}
}

const barCache = new Map();

/** Encode once at 1px per module, then scale by whole pixels so bars stay sharp. */
function barcodeModules( value ) {
	if ( barCache.has( value ) ) {
		return barCache.get( value );
	}
	const c = document.createElement( 'canvas' );
	try {
		window.JsBarcode( c, value, { format: 'CODE128', width: 1, height: 10, margin: 0, displayValue: false } );
	} catch ( e ) {
		barCache.set( value, null );
		return null;
	}
	const data = c.getContext( '2d' ).getImageData( 0, 0, c.width, 1 ).data;
	const bars = [];
	for ( let i = 0; i < c.width; i++ ) {
		bars.push( data[ i * 4 + 3 ] > 0 && data[ i * 4 ] < 128 );
	}
	barCache.set( value, bars );
	return bars;
}

/**
 * Draw a Code 128 barcode inside a box.
 * @return {boolean} false when the value cannot be encoded.
 */
export function drawBarcode( ctx, value, x, y, w, h, color = '#000000', showText = false, textSize = 0 ) {
	if ( ! window.JsBarcode || ! value ) {
		return false;
	}
	const bars = barcodeModules( String( value ) );
	if ( ! bars ) {
		return false;
	}
	const textH = showText ? Math.max( 6, textSize || h * 0.22 ) : 0;
	const barH = h - textH * 1.15;
	let unit = w / bars.length;
	if ( unit >= 1 ) {
		unit = Math.floor( unit ); // Whole pixels = sharp print.
	}
	const total = unit * bars.length;
	const ox = x + ( w - total ) / 2;
	ctx.fillStyle = color;
	let run = -1;
	for ( let i = 0; i <= bars.length; i++ ) {
		if ( bars[ i ] && run < 0 ) {
			run = i;
		} else if ( ! bars[ i ] && run >= 0 ) {
			ctx.fillRect( ox + run * unit, y, ( i - run ) * unit, barH );
			run = -1;
		}
	}
	if ( showText ) {
		ctx.font = `600 ${ textH }px "Inter", Arial, sans-serif`;
		ctx.textAlign = 'center';
		ctx.textBaseline = 'bottom';
		ctx.fillText( String( value ), x + w / 2, y + h );
	}
	return true;
}
