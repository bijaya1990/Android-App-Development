/**
 * Free-plan watermark, drawn into the exported image pixels (not a CSS overlay),
 * so it cannot be switched off in the browser.
 *
 * Design: a fine diagonal pattern of "MADE WITH PIKACART.IN" across the whole card
 * (photo included, so a free card cannot pass as an official card) plus a neat
 * branded ribbon at the bottom. Subtle enough to look professional.
 *
 * Every export in the card designer (Phase 2/3) calls drawWatermark() when the
 * server says the account needs one.
 */

/**
 * @param {CanvasRenderingContext2D} ctx
 * @param {number} w     Card width in pixels.
 * @param {number} h     Card height in pixels.
 * @param {object} opts  { text, brand }
 */
export function drawWatermark( ctx, w, h, opts = {} ) {
	const text = ( opts.text || 'Made with www.pikacart.in' ).trim();
	const tile = text.replace( /^made with\s+/i, 'MADE WITH ' ).replace( /www\./i, '' ).toUpperCase();
	const brand = opts.brand || '#4F46E5';
	const base = Math.min( w, h );

	ctx.save();

	// 1. Diagonal pattern over the whole card.
	const size = Math.max( 9, Math.round( base * 0.05 ) );
	ctx.font = `700 ${ size }px Inter, "Segoe UI", Arial, sans-serif`;
	ctx.textAlign = 'center';
	ctx.textBaseline = 'middle';
	const stepX = ctx.measureText( tile ).width + size * 2.2;
	const stepY = size * 3.4;
	const diag = Math.sqrt( w * w + h * h );
	ctx.translate( w / 2, h / 2 );
	ctx.rotate( -Math.PI / 6 );
	let row = 0;
	for ( let y = -diag / 2; y < diag / 2; y += stepY ) {
		const shift = row % 2 ? stepX / 2 : 0;
		for ( let x = -diag / 2 - stepX; x < diag / 2 + stepX; x += stepX ) {
			// Dark text with a light edge stays readable on light and dark designs.
			ctx.lineWidth = Math.max( 1, size * 0.12 );
			ctx.strokeStyle = 'rgba(255,255,255,0.22)';
			ctx.strokeText( tile, x + shift, y );
			ctx.fillStyle = 'rgba(17,20,39,0.13)';
			ctx.fillText( tile, x + shift, y );
		}
		row++;
	}
	ctx.setTransform( 1, 0, 0, 1, 0, 0 );
	ctx.restore();

	// 2. Branded ribbon at the bottom.
	ctx.save();
	const rh = Math.max( 14, Math.round( h * 0.072 ) );
	const y0 = h - rh;
	ctx.fillStyle = 'rgba(17,20,39,0.90)';
	ctx.fillRect( 0, y0, w, rh );
	ctx.fillStyle = brand;
	ctx.fillRect( 0, y0, w, Math.max( 1.5, rh * 0.08 ) );

	// Small card-shaped logo mark.
	const m = rh * 0.56;
	const label = text;
	const fs = Math.max( 7, Math.round( rh * 0.42 ) );
	ctx.font = `600 ${ fs }px Inter, "Segoe UI", Arial, sans-serif`;
	const tw = ctx.measureText( label ).width;
	const total = m * 0.72 + rh * 0.25 + tw;
	let x = ( w - total ) / 2;
	const my = y0 + ( rh - m ) / 2 + rh * 0.04;
	roundRect( ctx, x, my, m * 0.72, m, m * 0.18 );
	ctx.fillStyle = brand;
	ctx.fill();
	ctx.fillStyle = '#fff';
	ctx.beginPath();
	ctx.arc( x + m * 0.36, my + m * 0.38, m * 0.13, 0, Math.PI * 2 );
	ctx.fill();
	ctx.fillRect( x + m * 0.18, my + m * 0.64, m * 0.36, m * 0.08 );
	x += m * 0.72 + rh * 0.25;
	ctx.fillStyle = '#fff';
	ctx.textAlign = 'left';
	ctx.textBaseline = 'middle';
	ctx.fillText( label, x, y0 + rh / 2 + rh * 0.04 );
	ctx.restore();
}

function roundRect( ctx, x, y, w, h, r ) {
	ctx.beginPath();
	ctx.moveTo( x + r, y );
	ctx.arcTo( x + w, y, x + w, y + h, r );
	ctx.arcTo( x + w, y + h, x, y + h, r );
	ctx.arcTo( x, y + h, x, y, r );
	ctx.arcTo( x, y, x + w, y, r );
	ctx.closePath();
}

/**
 * Draw a sample ID card (for the Free vs Pro preview).
 */
export function drawSampleCard( canvas, { org = 'Your Organisation', watermark = false, text, brand = '#4F46E5', accent = '#F97316' } = {} ) {
	const ctx = canvas.getContext( '2d' );
	const w = canvas.width;
	const h = canvas.height;
	ctx.clearRect( 0, 0, w, h );
	ctx.save();
	roundRect( ctx, 0, 0, w, h, w * 0.06 );
	ctx.clip();
	ctx.fillStyle = '#fff';
	ctx.fillRect( 0, 0, w, h );

	// Header shape.
	ctx.fillStyle = brand;
	ctx.beginPath();
	ctx.moveTo( 0, 0 );
	ctx.lineTo( w, 0 );
	ctx.lineTo( w, h * 0.2 );
	ctx.lineTo( 0, h * 0.28 );
	ctx.closePath();
	ctx.fill();
	ctx.fillStyle = 'rgba(255,255,255,.55)';
	roundRect( ctx, w / 2 - w * 0.08, h * 0.025, w * 0.16, h * 0.018, h * 0.009 );
	ctx.fill();
	ctx.fillStyle = '#fff';
	ctx.beginPath();
	ctx.arc( w * 0.14, h * 0.105, w * 0.055, 0, Math.PI * 2 );
	ctx.fill();
	ctx.font = `700 ${ Math.round( w * 0.062 ) }px "Plus Jakarta Sans", Inter, Arial, sans-serif`;
	ctx.textBaseline = 'middle';
	ctx.fillText( org.toUpperCase().slice( 0, 22 ), w * 0.23, h * 0.105 );

	// Photo.
	const pw = w * 0.4;
	const ph = pw * 1.18;
	const px = ( w - pw ) / 2;
	const py = h * 0.24;
	ctx.fillStyle = brand;
	roundRect( ctx, px - 4, py - 4, pw + 8, ph + 8, w * 0.05 );
	ctx.fill();
	ctx.fillStyle = '#eef1f7';
	roundRect( ctx, px, py, pw, ph, w * 0.04 );
	ctx.fill();
	ctx.fillStyle = '#c3cadb';
	ctx.beginPath();
	ctx.arc( w / 2, py + ph * 0.38, pw * 0.2, 0, Math.PI * 2 );
	ctx.fill();
	ctx.beginPath();
	ctx.ellipse( w / 2, py + ph * 0.95, pw * 0.36, ph * 0.3, 0, Math.PI, 0 );
	ctx.fill();

	// Name and details.
	ctx.textAlign = 'center';
	ctx.fillStyle = '#141827';
	ctx.font = `800 ${ Math.round( w * 0.075 ) }px "Plus Jakarta Sans", Inter, Arial, sans-serif`;
	ctx.fillText( 'Ananya Sharma', w / 2, py + ph + h * 0.06 );
	ctx.fillStyle = accent;
	ctx.font = `600 ${ Math.round( w * 0.05 ) }px Inter, Arial, sans-serif`;
	ctx.fillText( 'Class X · Section A', w / 2, py + ph + h * 0.105 );
	ctx.fillStyle = '#4a5068';
	ctx.font = `500 ${ Math.round( w * 0.045 ) }px Inter, Arial, sans-serif`;
	ctx.fillText( 'ID: 2026-1042   ·   Blood: B+', w / 2, py + ph + h * 0.145 );

	// QR + barcode placeholders.
	const q = w * 0.17;
	const qy = h - q - h * ( watermark ? 0.1 : 0.04 );
	ctx.fillStyle = '#141827';
	for ( let i = 0; i < 7; i++ ) {
		for ( let j = 0; j < 7; j++ ) {
			if ( ( i * 7 + j * 3 + i * j ) % 3 !== 0 || i === 0 || j === 0 || i === 6 || j === 6 ) {
				ctx.fillRect( w * 0.08 + ( i * q ) / 7, qy + ( j * q ) / 7, q / 7.4, q / 7.4 );
			}
		}
	}
	for ( let k = 0; k < 34; k++ ) {
		const bw = k % 3 === 0 ? 2.2 : 1.1;
		ctx.fillRect( w * 0.36 + k * ( w * 0.017 ), qy + q * 0.25, bw, q * 0.6 );
	}
	ctx.restore();

	if ( watermark ) {
		drawWatermark( ctx, w, h, { text, brand } );
	}
}
