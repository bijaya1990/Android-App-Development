/**
 * Original illustrated avatars for demo cards (gallery, homepage, previews),
 * drawn with simple vector shapes. Seeded, so the same person always looks the same.
 */

const SKIN = [ '#F2C9A0', '#E8B48A', '#D59C6E', '#C08457', '#9E6A43', '#7E5232' ];
const HAIR = [ '#1B1512', '#2B1F17', '#3A2A1F', '#141414', '#4A3426' ];
const SHIRT = [ '#1E3A8A', '#0F766E', '#7C2D12', '#374151', '#9D174D', '#1D4ED8', '#065F46', '#6D28D9', '#B45309', '#0E7490' ];
const BG = [ '#E8EEF9', '#E9F5F1', '#F7EFE7', '#EEF0F4', '#F6ECF2', '#EDEBFA' ];

function rng( seed ) {
	let s = 0;
	const str = String( seed );
	for ( let i = 0; i < str.length; i++ ) {
		s = ( s * 31 + str.charCodeAt( i ) ) >>> 0;
	}
	return () => {
		s = ( s * 1664525 + 1013904223 ) >>> 0;
		return s / 4294967296;
	};
}

/**
 * @param {CanvasRenderingContext2D} ctx
 * @param {number} x
 * @param {number} y
 * @param {number} w
 * @param {number} h
 * @param {string} seed    Any text, e.g. the person's name.
 * @param {object} opt     { female: bool }
 */
export function drawAvatar( ctx, x, y, w, h, seed = 'a', opt = {} ) {
	const r = rng( seed );
	const pick = ( arr ) => arr[ Math.floor( r() * arr.length ) ];
	const female = opt.female ?? r() > 0.5;
	const skin = pick( SKIN );
	const hair = pick( HAIR );
	const shirt = pick( SHIRT );
	const bg = pick( BG );
	const glasses = r() > 0.78;

	ctx.save();
	ctx.beginPath();
	ctx.rect( x, y, w, h );
	ctx.clip();

	// Background.
	const g = ctx.createLinearGradient( x, y, x, y + h );
	g.addColorStop( 0, bg );
	g.addColorStop( 1, shade( bg, -0.06 ) );
	ctx.fillStyle = g;
	ctx.fillRect( x, y, w, h );

	const s = Math.min( w, h * 0.86 );
	const cx = x + w / 2;
	const headR = s * 0.2;
	const headY = y + h * 0.42;

	// Long hair behind the head.
	if ( female ) {
		ctx.fillStyle = hair;
		ctx.beginPath();
		ctx.ellipse( cx, headY + headR * 0.55, headR * 1.22, headR * 1.65, 0, 0, Math.PI * 2 );
		ctx.fill();
	}

	// Shoulders and shirt.
	ctx.fillStyle = shirt;
	ctx.beginPath();
	ctx.moveTo( cx - s * 0.48, y + h );
	ctx.bezierCurveTo( cx - s * 0.46, headY + headR * 1.75, cx - s * 0.2, headY + headR * 1.45, cx, headY + headR * 1.45 );
	ctx.bezierCurveTo( cx + s * 0.2, headY + headR * 1.45, cx + s * 0.46, headY + headR * 1.75, cx + s * 0.48, y + h );
	ctx.closePath();
	ctx.fill();

	// Neck.
	ctx.fillStyle = shade( skin, -0.08 );
	ctx.beginPath();
	ctx.moveTo( cx - headR * 0.36, headY + headR * 0.6 );
	ctx.lineTo( cx + headR * 0.36, headY + headR * 0.6 );
	ctx.lineTo( cx + headR * 0.4, headY + headR * 1.5 );
	ctx.quadraticCurveTo( cx, headY + headR * 1.85, cx - headR * 0.4, headY + headR * 1.5 );
	ctx.closePath();
	ctx.fill();

	// Collar.
	ctx.fillStyle = '#ffffff';
	ctx.beginPath();
	ctx.moveTo( cx - headR * 0.62, headY + headR * 1.42 );
	ctx.lineTo( cx, headY + headR * 1.95 );
	ctx.lineTo( cx + headR * 0.62, headY + headR * 1.42 );
	ctx.lineTo( cx + headR * 0.32, headY + headR * 1.38 );
	ctx.lineTo( cx, headY + headR * 1.72 );
	ctx.lineTo( cx - headR * 0.32, headY + headR * 1.38 );
	ctx.closePath();
	ctx.fill();

	// Ears and head.
	ctx.fillStyle = skin;
	ctx.beginPath();
	ctx.ellipse( cx - headR * 0.92, headY + headR * 0.08, headR * 0.17, headR * 0.24, 0, 0, Math.PI * 2 );
	ctx.ellipse( cx + headR * 0.92, headY + headR * 0.08, headR * 0.17, headR * 0.24, 0, 0, Math.PI * 2 );
	ctx.fill();
	ctx.beginPath();
	ctx.ellipse( cx, headY, headR * 0.9, headR * 1.08, 0, 0, Math.PI * 2 );
	ctx.fill();

	// Hair on top.
	ctx.fillStyle = hair;
	const style = Math.floor( r() * 4 );
	ctx.beginPath();
	if ( female ) {
		ctx.ellipse( cx, headY - headR * 0.42, headR * 0.98, headR * 0.72, 0, Math.PI, Math.PI * 2 );
		ctx.quadraticCurveTo( cx + headR * 0.2, headY - headR * 0.5, cx - headR * 0.98, headY - headR * 0.3 );
		ctx.fill();
		if ( style % 2 ) {
			// Bun.
			ctx.beginPath();
			ctx.arc( cx, headY - headR * 1.15, headR * 0.34, 0, Math.PI * 2 );
			ctx.fill();
		}
	} else {
		const top = headY - headR * ( 0.55 + style * 0.04 );
		ctx.moveTo( cx - headR * 0.92, headY - headR * 0.05 );
		ctx.quadraticCurveTo( cx - headR * 1.0, top - headR * 0.55, cx - headR * 0.1, top - headR * 0.5 );
		ctx.quadraticCurveTo( cx + headR * 1.05, top - headR * 0.52, cx + headR * 0.92, headY - headR * 0.05 );
		ctx.quadraticCurveTo( cx + headR * 0.75, top + headR * 0.1, cx + ( style > 1 ? -0.2 : 0.3 ) * headR, top + headR * 0.08 );
		ctx.quadraticCurveTo( cx - headR * 0.6, top + headR * 0.2, cx - headR * 0.92, headY - headR * 0.05 );
		ctx.fill();
	}

	// Face.
	ctx.fillStyle = '#2A1E17';
	const ey = headY + headR * 0.08;
	ctx.beginPath();
	ctx.ellipse( cx - headR * 0.33, ey, headR * 0.075, headR * 0.1, 0, 0, Math.PI * 2 );
	ctx.ellipse( cx + headR * 0.33, ey, headR * 0.075, headR * 0.1, 0, 0, Math.PI * 2 );
	ctx.fill();
	ctx.strokeStyle = hair;
	ctx.lineWidth = Math.max( 1, headR * 0.07 );
	ctx.lineCap = 'round';
	ctx.beginPath();
	ctx.moveTo( cx - headR * 0.48, ey - headR * 0.22 );
	ctx.lineTo( cx - headR * 0.2, ey - headR * 0.26 );
	ctx.moveTo( cx + headR * 0.2, ey - headR * 0.26 );
	ctx.lineTo( cx + headR * 0.48, ey - headR * 0.22 );
	ctx.stroke();
	ctx.strokeStyle = shade( skin, -0.3 );
	ctx.lineWidth = Math.max( 1, headR * 0.06 );
	ctx.beginPath();
	ctx.arc( cx, headY + headR * 0.42, headR * 0.26, 0.15 * Math.PI, 0.85 * Math.PI );
	ctx.stroke();
	if ( glasses ) {
		ctx.strokeStyle = '#1f2937';
		ctx.lineWidth = Math.max( 1, headR * 0.055 );
		ctx.beginPath();
		ctx.arc( cx - headR * 0.33, ey, headR * 0.22, 0, Math.PI * 2 );
		ctx.moveTo( cx + headR * 0.55, ey );
		ctx.arc( cx + headR * 0.33, ey, headR * 0.22, 0, Math.PI * 2 );
		ctx.moveTo( cx - headR * 0.11, ey );
		ctx.lineTo( cx + headR * 0.11, ey );
		ctx.stroke();
	}
	ctx.restore();
}

/** Plain silhouette shown when a real person's photo is missing. */
export function drawPhotoPlaceholder( ctx, x, y, w, h ) {
	ctx.save();
	ctx.fillStyle = '#E5E8EF';
	ctx.fillRect( x, y, w, h );
	ctx.fillStyle = '#C3C9D6';
	const cx = x + w / 2;
	const r = Math.min( w, h ) * 0.2;
	ctx.beginPath();
	ctx.arc( cx, y + h * 0.4, r, 0, Math.PI * 2 );
	ctx.fill();
	ctx.beginPath();
	ctx.ellipse( cx, y + h * 1.02, w * 0.4, h * 0.36, 0, Math.PI, 0 );
	ctx.fill();
	ctx.restore();
}

function shade( hex, amt ) {
	const n = parseInt( hex.slice( 1 ), 16 );
	const f = ( v ) => Math.max( 0, Math.min( 255, Math.round( v + 255 * amt ) ) );
	const r = f( ( n >> 16 ) & 255 );
	const g = f( ( n >> 8 ) & 255 );
	const b = f( n & 255 );
	return '#' + ( ( 1 << 24 ) + ( r << 16 ) + ( g << 8 ) + b ).toString( 16 ).slice( 1 );
}
