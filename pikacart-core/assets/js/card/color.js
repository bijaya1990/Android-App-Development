/**
 * Colours for card designs.
 *
 * Templates never store fixed brand colours; they use palette tokens so a
 * click on a swatch recolours the whole card instantly:
 *   @p primary, @s secondary, @a accent, @t text,
 *   @w white, @k near-black, @bg card background, @lt light tint of primary,
 *   @dk dark shade of primary, @g gold, @m muted grey.
 * Add "/NN" for opacity, e.g. "@p/40" = primary at 40%.
 * A fill can also be a gradient: { g: 'lin', a: 135, s: [[0,'@p'],[1,'@s']] }.
 */

export function hexToRgb( hex ) {
	let h = String( hex || '' ).replace( '#', '' ).trim();
	if ( h.length === 3 ) {
		h = h.split( '' ).map( ( c ) => c + c ).join( '' );
	}
	if ( ! /^[0-9a-f]{6}$/i.test( h ) ) {
		return [ 0, 0, 0 ];
	}
	return [ parseInt( h.slice( 0, 2 ), 16 ), parseInt( h.slice( 2, 4 ), 16 ), parseInt( h.slice( 4, 6 ), 16 ) ];
}

export function rgbToHex( [ r, g, b ] ) {
	return '#' + [ r, g, b ].map( ( v ) => Math.max( 0, Math.min( 255, Math.round( v ) ) ).toString( 16 ).padStart( 2, '0' ) ).join( '' );
}

export function mix( a, b, t ) {
	const x = hexToRgb( a );
	const y = hexToRgb( b );
	return rgbToHex( [ x[ 0 ] + ( y[ 0 ] - x[ 0 ] ) * t, x[ 1 ] + ( y[ 1 ] - x[ 1 ] ) * t, x[ 2 ] + ( y[ 2 ] - x[ 2 ] ) * t ] );
}

/** Relative luminance 0..1, used to pick readable text colours. */
export function luminance( hex ) {
	const c = hexToRgb( hex ).map( ( v ) => {
		const s = v / 255;
		return s <= 0.03928 ? s / 12.92 : Math.pow( ( s + 0.055 ) / 1.055, 2.4 );
	} );
	return 0.2126 * c[ 0 ] + 0.7152 * c[ 1 ] + 0.0722 * c[ 2 ];
}

/** Turn a palette { p, s, a, t } into the full token map. */
export function tokens( pal ) {
	const p = pal.p || '#1E3A8A';
	const s = pal.s || mix( p, '#ffffff', 0.35 );
	const a = pal.a || '#F59E0B';
	const t = pal.t || '#111827';
	return {
		p,
		s,
		a,
		t,
		w: '#ffffff',
		k: '#141827',
		bg: pal.bg || '#ffffff',
		lt: mix( p, '#ffffff', 0.9 ),
		lt2: mix( p, '#ffffff', 0.78 ),
		dk: mix( p, '#000000', 0.38 ),
		g: '#C9A227',
		m: '#6B7280',
		on: luminance( p ) > 0.45 ? '#141827' : '#ffffff', // readable text on primary
	};
}

/** Resolve a colour string (token, hex, rgba) to a CSS colour. */
export function color( value, map ) {
	if ( ! value ) {
		return 'transparent';
	}
	if ( typeof value !== 'string' ) {
		return value;
	}
	if ( value[ 0 ] !== '@' ) {
		return value;
	}
	const [ key, alpha ] = value.slice( 1 ).split( '/' );
	const hex = map[ key ] || '#000000';
	if ( alpha === undefined ) {
		return hex;
	}
	const [ r, g, b ] = hexToRgb( hex );
	return `rgba(${ r },${ g },${ b },${ Math.max( 0, Math.min( 100, Number( alpha ) ) ) / 100 })`;
}

/** Canvas fill style for a colour or gradient within a pixel box. */
export function paint( ctx, value, map, box ) {
	if ( value && typeof value === 'object' && value.g ) {
		const angle = ( ( value.a ?? 90 ) * Math.PI ) / 180;
		const cx = box.x + box.w / 2;
		const cy = box.y + box.h / 2;
		const len = ( Math.abs( box.w * Math.sin( angle ) ) + Math.abs( box.h * Math.cos( angle ) ) ) / 2;
		let grad;
		if ( value.g === 'rad' ) {
			grad = ctx.createRadialGradient( cx, cy, 0, cx, cy, Math.max( box.w, box.h ) * 0.75 );
		} else {
			grad = ctx.createLinearGradient( cx - Math.sin( angle ) * len, cy + Math.cos( angle ) * len, cx + Math.sin( angle ) * len, cy - Math.cos( angle ) * len );
		}
		( value.s || [] ).forEach( ( stop ) => grad.addColorStop( stop[ 0 ], color( stop[ 1 ], map ) ) );
		return grad;
	}
	return color( value, map );
}
