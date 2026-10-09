/**
 * Built-in Pikacart designs.
 *
 * 17 original layout families, each in 3 clearly different variants
 * (photo frame, typography, decoration, header alignment), for portrait and
 * landscape, front and back. A built-in template is stored in the database as
 * a tiny recipe { f: family, v: variant }; expand() turns it into full layout
 * JSON at the card's real proportions. Saved custom designs are plain JSON.
 *
 * Coordinates: % of card width (x, w) and height (y, h). Sizes in "u" = 1% of
 * the short side. Colours are palette tokens (see color.js).
 */

export const LEVELS = [ 'simple', 'modern', 'professional', 'premium' ];

const FP = [
	{ h: 'Poppins', b: 'Inter' },
	{ h: 'Montserrat', b: 'Lato' },
	{ h: 'Playfair Display', b: 'Lato' },
	{ h: 'Oswald', b: 'Inter' },
	{ h: 'Raleway', b: 'Nunito' },
	{ h: 'Bebas Neue', b: 'Montserrat' },
	{ h: 'DM Serif Display', b: 'Nunito' },
	{ h: 'Merriweather', b: 'Lato' },
	{ h: 'Plus Jakarta Sans', b: 'Inter' },
	{ h: 'Roboto Slab', b: 'Raleway' },
];

/* ---------- Element helpers ---------- */

const R = ( x, y, w, h, f, o = {} ) => ( { t: 'rect', x, y, w, h, f, ...o } );
const E = ( x, y, w, h, f, o = {} ) => ( { t: 'ellipse', x, y, w, h, f, ...o } );
const P = ( x, y, w, h, d, f, o = {} ) => ( { t: 'path', x, y, w, h, d, f, ...o } );
const T = ( x, y, w, h, text, size, o = {} ) => ( { t: 'text', x, y, w, h, text, size, ...o } );
const I = ( x, y, w, h, src, o = {} ) => ( { t: 'img', x, y, w, h, src, ...o } );
const Q = ( x, y, w, h, o = {} ) => ( { t: 'qr', x, y, w, h, v: '{{verify_url}}', ...o } );
const B = ( x, y, w, h, o = {} ) => ( { t: 'bar', x, y, w, h, v: '{{id_no}}', ...o } );
const PAT = ( x, y, w, h, kind, o = {} ) => ( { t: 'pattern', x, y, w, h, kind, ...o } );

/** Detail rows per kind of card. Empty or switched-off rows disappear. */
export function rows( kind, withId = false ) {
	const id = [ withId ? [ 'ID No.', '{{id_no}}', 'id_no' ] : null ];
	const set = {
		student: [ [ 'Class', '{{class_section}}', 'class' ], [ "Father's Name", '{{guardian}}', 'guardian' ], [ 'D.O.B.', '{{dob}}', 'dob' ], [ 'Blood Group', '{{blood_group}}', 'blood_group' ], [ 'Mobile', '{{mobile}}', 'mobile' ] ],
		staff: [ [ 'Designation', '{{designation}}', 'designation' ], [ 'Department', '{{department}}', 'department' ], [ 'Blood Group', '{{blood_group}}', 'blood_group' ], [ 'D.O.B.', '{{dob}}', 'dob' ], [ 'Mobile', '{{mobile}}', 'mobile' ], [ 'Email', '{{email}}', 'email' ] ],
		other: [ [ 'Pass Type', '{{designation}}', 'designation' ], [ 'Area', '{{department}}', 'department' ], [ 'Mobile', '{{mobile}}', 'mobile' ] ],
	}[ kind ] || [];
	const tail = [ [ 'Address', '{{address}}', 'address' ], [ 'Emergency', '{{emergency}}', 'emergency' ], [ 'Valid From', '{{valid_from}}', 'valid_from' ], [ 'Valid Till', '{{valid_until}}', 'valid_until' ] ];
	const custom = [ 1, 2, 3, 4, 5 ].map( ( n ) => [ `{{cf${ n }_label}}`, `{{cf${ n }}}`, `cf${ n }` ] );
	return [ ...id, ...set, ...tail, ...custom ].filter( Boolean ).map( ( r ) => ( { l: r[ 0 ], v: r[ 1 ], f: r[ 2 ] } ) );
}

const stack = ( x, y, w, h, kind, o = {} ) => ( { t: 'stack', x, y, w, h, rows: rows( kind, o.withId ), mode: 'table', size: 3.4, ...o, withId: undefined } );

/** Secondary line under the name: class for students, designation for staff. */
function roleText( kind ) {
	return kind === 'student' ? '{{class_section}}' : '{{designation}}';
}
function roleField( kind ) {
	return kind === 'student' ? 'class' : 'designation';
}

/* ---------- Shared blocks ---------- */

function photoH( w, ar ) {
	return ( w * ar ) / 0.8;
}
function sq( w, ar ) {
	return w * ar;
}

/** Photo block centred at cx. Returns element (frame varies by variant). */
function photo( cx, y, w, ar, frame, o = {} ) {
	const h = frame === 'circle' || frame === 'squircle' || frame === 'hex' ? sq( w, ar ) * ( frame === 'hex' ? 1.12 : 1 ) : photoH( w, ar );
	return I( cx - w / 2, y, w, h, '{{photo}}', { id: 'photo', shape: frame, r: frame === 'round' ? 3 : 0, field: 'photo', ...o } );
}

function photoBottom( el ) {
	return el.y + el.h;
}

function signBlock( x, y, w, o = {} ) {
	const c = o.c || '@t';
	return [
		I( x, y, w, 6, '{{sign}}', { id: 'sign', fit: 'contain' } ),
		T( x, y + 6, w, 3.2, '{{signatory_title}}', 2.6, { al: 'center', c, wt: 600, font: o.font, keep: false } ),
	];
}

function cardTitle( x, y, w, h, o ) {
	return T( x, y, w, h, '{{card_title}}', o.size || 3, { id: 'title', al: o.al || 'center', wt: 700, up: true, ls: 0.12, c: o.c || '@w', font: o.font } );
}

/* ---------- Backs (shared by families, tinted by palette) ---------- */

function backPortrait( o, style ) {
	const { ar, fp } = o;
	const head = style === 1 ? '@dk' : '@p';
	const els = [];
	if ( style === 2 ) {
		els.push( R( 0, 0, 100, 100, '@w' ), R( 0, 0, 3, 100, '@p' ), R( 97, 0, 3, 100, '@a' ) );
	} else {
		els.push( P( 0, 0, 100, 17, 'M0 0 L100 0 L100 78 Q50 108 0 78 Z', head, { id: 'bhead' } ) );
		els.push( R( 42, 1.6, 16, 1.4, '@w/55', { r: 2 } ) );
	}
	const onHead = style === 2 ? '@p' : '@w';
	els.push(
		I( 8, 5, 12, sq( 12, ar ), '{{logo}}', { fit: 'contain' } ),
		T( 22, 4.5, 72, 5, '{{org_name}}', 4.4, { wt: 700, c: onHead, font: fp.h, lines: 2, lh: 1.05 } ),
		T( 22, 9.6, 72, 3, '{{org_tagline}}', 2.6, { c: style === 2 ? '@m' : '@w/85', font: fp.b } ),
		T( 7, 19.5, 86, 3, 'TERMS & CONDITIONS', 2.8, { wt: 700, c: '@p', ls: 0.1, font: fp.b } ),
		T( 7, 23, 86, 18, '{{terms}}', 3.1, { c: '@t', lines: 8, lh: 1.32, va: 'top', font: fp.b, min: 0.6 } ),
		{ t: 'table', x: 7, y: 42, w: 86, h: 12, cols: [ 'Session', 'Signature' ], n: 3, renewal: true, font: fp.b },
		T( 7, 56, 86, 3, '{{return_line}}', 2.7, { wt: 700, c: '@p', font: fp.b } ),
		T( 7, 59.4, 86, 7.5, '{{org_address}}', 2.6, { c: '@t', lines: 3, lh: 1.25, va: 'top', font: fp.b } ),
		T( 7, 66.8, 86, 3, '{{org_phone}}  {{org_email}}', 2.5, { c: '@m', font: fp.b } ),
		...signBlock( 60, 72, 33, { font: fp.b } ),
		I( 10, 71.5, 20, sq( 20, ar ), '{{seal}}', { id: 'seal', fit: 'contain', op: 0.95 } ),
		Q( 7, 84, 22, 14, { id: 'qr' } ),
		B( 33, 86.5, 60, 7, { id: 'bar' } ),
		T( 33, 93.8, 60, 3, '{{org_website}}', 2.5, { al: 'center', c: '@m', font: fp.b } )
	);
	if ( style !== 2 ) {
		els.push( R( 0, 99, 100, 1, '@a' ) );
	}
	return { bg: '@w', els };
}

function backLandscape( o, style ) {
	const { ar, fp } = o;
	const els = [ R( 0, 0, 100, 100, '@w' ) ];
	if ( style === 2 ) {
		els.push( R( 0, 0, 100, 3, '@p' ), R( 0, 97, 100, 3, '@a' ) );
	} else {
		els.push( R( 0, 0, 100, 19, style === 1 ? '@dk' : '@p' ), R( 0, 19, 100, 1.2, '@a' ) );
	}
	const onHead = style === 2 ? '@p' : '@w';
	els.push(
		I( 3.5, style === 2 ? 5 : 3, 9, 13, '{{logo}}', { fit: 'contain' } ),
		T( 14, style === 2 ? 5 : 3, 60, 8, '{{org_name}}', 6.2, { wt: 700, c: onHead, font: fp.h } ),
		T( 14, style === 2 ? 13 : 11, 60, 5, '{{org_tagline}}', 3.8, { c: style === 2 ? '@m' : '@w/85', font: fp.b } ),
		T( 4, 25, 58, 5, 'TERMS & CONDITIONS', 3.8, { wt: 700, c: '@p', ls: 0.1, font: fp.b } ),
		T( 4, 31, 58, 27, '{{terms}}', 3.6, { lines: 6, lh: 1.3, va: 'top', font: fp.b, min: 0.6 } ),
		{ t: 'table', x: 4, y: 60, w: 40, h: 20, cols: [ 'Session', 'Signature' ], n: 3, renewal: true, font: fp.b },
		T( 4, 83, 58, 5, '{{return_line}}', 3.6, { wt: 700, c: '@p', font: fp.b } ),
		T( 4, 88, 58, 9, '{{org_address}}  {{org_phone}}', 3.3, { lines: 2, lh: 1.2, va: 'top', font: fp.b } ),
		Q( 74, 23, 22, 22 * ar, { id: 'qr' } ),
		I( 46, 60, 16, 16 * ar, '{{seal}}', { id: 'seal', fit: 'contain' } ),
		I( 66, 61, 30, 11, '{{sign}}', { id: 'sign', fit: 'contain' } ),
		T( 66, 72, 30, 5, '{{signatory_title}}', 3.4, { al: 'center', wt: 600, font: fp.b } ),
		B( 64, 82, 32, 11, { id: 'bar' } ),
		T( 64, 93, 32, 4.5, '{{org_website}}', 3, { al: 'center', c: '@m', font: fp.b } )
	);
	return { bg: '@w', els };
}

/* ---------- Header helper ---------- */

function orgHeader( o, y, align, c, sub ) {
	const { ar, fp } = o;
	if ( align === 'center' ) {
		return [
			I( 43, y, 14, sq( 14, ar ), '{{logo}}', { id: 'logo', fit: 'contain' } ),
			T( 6, y + sq( 14, ar ) + 0.5, 88, 4.2, '{{org_name}}', 4.4, { id: 'org', al: 'center', wt: 700, c, font: fp.h, lines: 2, lh: 1.05 } ),
			T( 8, y + sq( 14, ar ) + 4.8, 84, 2.6, '{{org_tagline}}', 2.5, { id: 'tag', al: 'center', c: sub, font: fp.b } ),
		];
	}
	return [
		I( 6, y, 14, sq( 14, ar ), '{{logo}}', { id: 'logo', fit: 'contain' } ),
		T( 23, y - 0.3, 71, 5.2, '{{org_name}}', 4.6, { id: 'org', wt: 700, c, font: fp.h, lines: 2, lh: 1.05 } ),
		T( 23, y + 5.2, 71, 3, '{{org_tagline}}', 2.6, { id: 'tag', c: sub, font: fp.b } ),
	];
}

function slot( c = '@w/60' ) {
	return R( 42, 1.6, 16, 1.4, c, { id: 'slot', r: 2 } );
}

/* =================================================================
 * Families. Each: { name, level, back, frames[3], p(o), l(o) }
 * o = { ar, kind, v, fp, frame, deco, align }
 * ================================================================= */

const F = [];

/* 0. Classic Band — simple, solid header band. */
F.push( {
	name: 'Classic Band',
	level: 'simple',
	back: 0,
	frames: [ 'round', 'rect', 'circle' ],
	p( o ) {
		const { ar, kind, fp, frame, align } = o;
		const ph = photo( 50, 23, 36, ar, frame, { bc: '@p', bw: 0.9 } );
		const y = photoBottom( ph ) + 1.8;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 19, '@p', { id: 'band' } ),
				slot(),
				...orgHeader( o, 5.2, align === 'center' ? 'left' : 'left', '@on', '@w/80' ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@t' } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.4, { al: 'center', wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 10, y + 10.5, 80, 84 - ( y + 10.5 ), kind, { font: fp.b, withId: true } ),
				...signBlock( 64, 84.5, 30, { font: fp.b } ),
				R( 0, 94, 100, 6, '@p' ),
				cardTitle( 4, 94.6, 92, 4.8, { font: fp.b, size: 3.2 } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 24, '@p' ),
				I( 3, 3.5, 11, 17, '{{logo}}', { fit: 'contain' } ),
				T( 16, 3.5, 80, 9, '{{org_name}}', 6.4, { wt: 700, c: '@on', font: fp.h } ),
				T( 16, 13, 80, 6, '{{org_tagline}}', 3.8, { c: '@w/80', font: fp.b } ),
				I( 5, 30, 24, ( 24 * ar ) / ( frame === 'circle' ? 1 : 0.8 ) * 0.86, '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@p', bw: 0.9, field: 'photo' } ),
				T( 34, 29, 62, 10, '{{name}}', 8, { wt: 700, font: fp.h } ),
				T( 34, 39, 62, 6, roleText( kind ), 4.6, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 34, 47, 60, 38, kind, { font: fp.b, size: 4.4, withId: true } ),
				R( 0, 90, 100, 10, '@p' ),
				cardTitle( 3, 91, 60, 8, { font: fp.b, size: 4.4, al: 'left' } ),
				I( 70, 76, 26, 9, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
} );

/* 1. Angled — slanted header with photo overlapping. */
F.push( {
	name: 'Angled',
	level: 'modern',
	back: 0,
	frames: [ 'circle', 'squircle', 'round' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 50, 22, 40, ar, frame, { ring: '@a', rw: 0.8, rg: 1.4, bc: '@w', bw: 1.6, sh: 2 } );
		const y = photoBottom( ph ) + 2.4;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 100, 36, 'M0 0 L100 0 L100 62 L0 100 Z', { g: 'lin', a: 120, s: [ [ 0, '@p' ], [ 1, '@s' ] ] }, { id: 'head' } ),
				P( 0, 0, 100, 38, 'M0 100 L100 62 L100 66 L0 104 Z', '@a', { id: 'accent' } ),
				deco === 1 ? PAT( 55, 0, 45, 22, 'dots', { c: '@w/25', gap: 3.4, size: 0.5 } ) : deco === 2 ? PAT( 0, 0, 100, 25, 'lines', { c: '@w/12', gap: 4, size: 0.4 } ) : R( 0, 0, 0, 0, '' ),
				slot(),
				...orgHeader( o, 5.4, 'left', '@w', '@w/80' ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.6, { id: 'name', al: 'center', wt: 700, font: fp.h, up: deco === 2 } ),
				R( 30, y + 6.6, 40, 4, '@p', { r: 2 } ),
				cardTitle( 30, y + 6.6, 40, 4, { size: 2.5, font: fp.b } ),
				stack( 10, y + 12.5, 80, 87 - ( y + 12.5 ), kind, { font: fp.b, withId: true } ),
				...signBlock( 64, 86, 30, { font: fp.b } ),
				Q( 7, 86.5, 12, 7.5, { id: 'qrf' } ),
				P( 0, 95, 100, 5, 'M0 100 L0 40 L100 0 L100 100 Z', '@p' ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 42, 100, 'M0 0 L100 0 L70 100 L0 100 Z', { g: 'lin', a: 160, s: [ [ 0, '@p' ], [ 1, '@s' ] ] } ),
				P( 0, 0, 44, 100, 'M100 0 L104 0 L74 100 L70 100 Z', '@a' ),
				I( 4, 5, 9, 14, '{{logo}}', { fit: 'contain' } ),
				I( 7, 26, 22, ( 22 * ar ) / ( frame === 'circle' || frame === 'squircle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@w', bw: 1.6, sh: 2, field: 'photo' } ),
				T( 14, 5, 30, 14, '{{org_name}}', 4.6, { wt: 700, c: '@w', font: fp.h, lines: 2, lh: 1.05 } ),
				T( 46, 8, 50, 10, '{{name}}', 8.4, { wt: 700, font: fp.h } ),
				T( 46, 18, 50, 6, roleText( kind ), 4.6, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 44, 28, 52, 46, kind, { font: fp.b, size: 4.3, withId: true } ),
				R( 44, 80, 30, 8, '@p', { r: 4 } ),
				cardTitle( 44, 80, 30, 8, { size: 3.4, font: fp.b } ),
				I( 76, 77, 21, 10, '{{sign}}', { fit: 'contain' } ),
				T( 76, 87, 21, 5, '{{signatory_title}}', 3.2, { al: 'center', wt: 600 } ),
				T( 4, 88, 32, 6, '{{org_tagline}}', 3.2, { c: '@w/85', font: fp.b } ),
			],
		};
	},
} );

/* 2. Wave — curved header and footer waves. */
F.push( {
	name: 'Wave',
	level: 'modern',
	back: 1,
	frames: [ 'circle', 'round', 'hex' ],
	p( o ) {
		const { ar, kind, fp, frame, deco, align } = o;
		const ph = photo( 50, 24.5, 38, ar, frame, { bc: '@w', bw: 1.8, sh: 2.2 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 100, 34, 'M0 0 L100 0 L100 72 C78 98 60 62 40 82 C24 98 10 92 0 84 Z', '@p', { id: 'wave' } ),
				P( 0, 0, 100, 37, 'M0 84 C10 92 24 98 40 82 C60 62 78 98 100 72 L100 78 C78 104 60 70 40 90 C24 104 10 98 0 92 Z', '@a/85' ),
				deco === 1 ? PAT( 0, 0, 100, 24, 'rings', { c: '@w/10', gap: 5, size: 0.4, cx: 100, cy: 0 } ) : R( 0, 0, 0, 0, '' ),
				slot(),
				...orgHeader( o, 6, align, '@w', '@w/80' ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.5, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 10, y + 6, 80, 3.6, roleText( kind ), 3.5, { al: 'center', wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 12, y + 11, 76, 85 - ( y + 11 ), kind, { font: fp.b, withId: true, mode: deco === 2 ? 'inline' : 'table', al: deco === 2 ? 'center' : 'left' } ),
				P( 0, 88, 100, 12, 'M0 40 C20 10 40 60 60 30 C78 4 90 30 100 20 L100 100 L0 100 Z', '@p' ),
				cardTitle( 5, 93.4, 90, 4.8, { size: 3, font: fp.b } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 100, 34, 'M0 0 L100 0 L100 60 C80 90 62 50 44 74 C28 96 12 80 0 88 Z', '@p' ),
				P( 0, 0, 100, 38, 'M0 88 C12 80 28 96 44 74 C62 50 80 90 100 60 L100 66 C80 98 62 58 44 82 C28 104 12 88 0 96 Z', '@a/85' ),
				I( 3.5, 4, 9, 14, '{{logo}}', { fit: 'contain' } ),
				T( 14, 4, 70, 9, '{{org_name}}', 6.2, { wt: 700, c: '@w', font: fp.h } ),
				T( 14, 13, 70, 5, '{{org_tagline}}', 3.6, { c: '@w/80', font: fp.b } ),
				I( 6, 34, 24, ( 24 * ar ) / ( frame === 'circle' ? 1 : frame === 'hex' ? 0.9 : 0.8 ) * ( frame === 'hex' ? 1 : 1 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@w', bw: 1.6, sh: 2.2, field: 'photo' } ),
				T( 35, 37, 62, 10, '{{name}}', 8, { wt: 700, font: fp.h } ),
				T( 35, 47, 62, 6, roleText( kind ), 4.6, { wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 35, 55, 60, 33, kind, { font: fp.b, size: 4.2, withId: true } ),
				P( 0, 86, 100, 14, 'M0 50 C25 10 50 70 75 30 C88 10 95 30 100 25 L100 100 L0 100 Z', '@p' ),
				cardTitle( 50, 90, 46, 8, { size: 3.8, font: fp.b, al: 'right' } ),
			],
		};
	},
} );

/* 3. Side Stripe — bold vertical stripe with rotated title. */
F.push( {
	name: 'Side Stripe',
	level: 'professional',
	back: 2,
	frames: [ 'rect', 'round', 'cut' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 58, 24, 40, ar, frame, { bc: '@p', bw: 0.7 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 16, 100, '@p', { id: 'stripe' } ),
				R( 16, 0, 1.4, 100, '@a' ),
				deco === 1 ? PAT( 0, 0, 16, 100, 'chevron', { c: '@w/14', gap: 5, size: 0.5 } ) : deco === 2 ? PAT( 0, 0, 16, 100, 'dots', { c: '@w/20', gap: 3, size: 0.45 } ) : R( 0, 0, 0, 0, '' ),
				T( -34, 46, 84, 9, '{{card_title}}', 5.2, { rot: -90, al: 'center', wt: 700, c: '@w', up: true, ls: 0.18, font: fp.h } ),
				I( 22, 5.5, 13, sq( 13, ar ), '{{logo}}', { fit: 'contain' } ),
				T( 37, 5, 58, 5.2, '{{org_name}}', 4.4, { wt: 700, c: '@p', font: fp.h, lines: 2, lh: 1.05 } ),
				T( 37, 10.4, 58, 3, '{{org_tagline}}', 2.5, { c: '@m', font: fp.b } ),
				R( 22, 16.5, 72, 0.5, '@p/30' ),
				ph,
				T( 21, y, 74, 6, '{{name}}', 6.2, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 21, y + 6, 74, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 22, y + 10.5, 72, 86 - ( y + 10.5 ), kind, { font: fp.b, size: 3.2, withId: true, lw: 42 } ),
				...signBlock( 64, 86.5, 30, { font: fp.b } ),
				Q( 22, 87, 11, 7, { id: 'qrf' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 9, 100, '@p' ),
				R( 9, 0, 0.9, 100, '@a' ),
				T( -21, 44, 52, 12, '{{card_title}}', 5, { rot: -90, al: 'center', wt: 700, c: '@w', up: true, ls: 0.18, font: fp.h } ),
				I( 13, 6, 9, 14, '{{logo}}', { fit: 'contain' } ),
				T( 24, 6, 72, 8, '{{org_name}}', 6, { wt: 700, c: '@p', font: fp.h } ),
				T( 24, 14, 72, 5, '{{org_tagline}}', 3.4, { c: '@m', font: fp.b } ),
				R( 13, 22, 83, 0.6, '@p/30' ),
				I( 13, 28, 24, ( 24 * ar ) / 0.8, '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@p', bw: 0.7, field: 'photo' } ),
				T( 42, 28, 54, 10, '{{name}}', 7.6, { wt: 700, font: fp.h } ),
				T( 42, 38, 54, 6, roleText( kind ), 4.4, { wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 42, 46, 54, 36, kind, { font: fp.b, size: 4.1, withId: true } ),
				I( 70, 82, 26, 10, '{{sign}}', { fit: 'contain' } ),
				T( 70, 92, 26, 5, '{{signatory_title}}', 3.2, { al: 'center', wt: 600 } ),
			],
		};
	},
} );

/* 4. Corporate Split — coloured top half holding the photo. */
F.push( {
	name: 'Corporate Split',
	level: 'professional',
	back: 1,
	frames: [ 'round', 'circle', 'rect' ],
	p( o ) {
		const { ar, kind, fp, frame, align, deco } = o;
		const ph = photo( 50, 18, 38, ar, frame, { bc: '@w', bw: 1.3 } );
		const split = Math.max( 46, photoBottom( ph ) - 6 );
		const y = Math.max( photoBottom( ph ), split ) + 2;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, split, { g: 'lin', a: 180, s: [ [ 0, '@dk' ], [ 1, '@p' ] ] }, { id: 'top' } ),
				deco === 1 ? PAT( 0, 0, 100, split, 'grid', { c: '@w/6', gap: 6, size: 0.3 } ) : deco === 2 ? PAT( 0, 0, 100, split, 'lines', { c: '@w/7', gap: 3, size: 0.35 } ) : R( 0, 0, 0, 0, '' ),
				slot(),
				...orgHeader( o, 4.6, align === 'center' ? 'left' : 'left', '@w', '@w/75' ),
				ph,
				R( 0, split, 100, 1.1, '@a' ),
				T( 6, y, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@p', up: true, ls: 0.06, font: fp.b, field: roleField( kind ) } ),
				stack( 10, y + 10.5, 80, 90 - ( y + 10.5 ), kind, { font: fp.b, withId: true, size: 3.4 } ),
				R( 0, 92.5, 100, 7.5, '@lt' ),
				cardTitle( 5, 93.6, 55, 5, { size: 2.8, font: fp.b, c: '@p', al: 'left' } ),
				I( 64, 91.5, 30, 5.5, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 36, 100, { g: 'lin', a: 180, s: [ [ 0, '@dk' ], [ 1, '@p' ] ] } ),
				R( 36, 0, 0.9, 100, '@a' ),
				I( 4, 5, 8, 12, '{{logo}}', { fit: 'contain' } ),
				T( 13, 5, 22, 12, '{{org_name}}', 4, { wt: 700, c: '@w', font: fp.h, lines: 2, lh: 1.05 } ),
				I( 6, 24, 24, ( 24 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@w', bw: 1.3, field: 'photo' } ),
				T( 3, 86, 30, 8, '{{card_title}}', 3.4, { al: 'center', wt: 700, c: '@w', up: true, ls: 0.1, font: fp.b } ),
				T( 41, 8, 55, 10, '{{name}}', 8, { wt: 700, font: fp.h } ),
				T( 41, 18, 55, 6, roleText( kind ), 4.4, { wt: 600, c: '@p', up: true, font: fp.b, field: roleField( kind ) } ),
				stack( 41, 28, 55, 52, kind, { font: fp.b, size: 4.2, withId: true } ),
				I( 70, 80, 26, 10, '{{sign}}', { fit: 'contain' } ),
				T( 70, 90, 26, 5, '{{signatory_title}}', 3.2, { al: 'center', wt: 600 } ),
				T( 41, 88, 28, 6, '{{org_tagline}}', 3, { c: '@m', font: fp.b } ),
			],
		};
	},
} );

/* 5. Royal Arch — premium arch photo, gold lines, serif. */
F.push( {
	name: 'Royal Arch',
	level: 'premium',
	back: 1,
	frames: [ 'arch', 'arch', 'circle' ],
	p( o ) {
		const { ar, kind, fp, frame } = o;
		const ph = photo( 50, 27, 34, ar, frame, { bc: '@g', bw: 0.9, ring: '@g/70', rw: 0.4, rg: 1.6 } );
		const y = photoBottom( ph ) + 2.2;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 100, { g: 'lin', a: 180, s: [ [ 0, '@lt' ], [ 1, '@w' ] ] } ),
				P( 0, 0, 100, 30, 'M0 0 L100 0 L100 70 Q50 100 0 70 Z', { g: 'lin', a: 135, s: [ [ 0, '@dk' ], [ 0.6, '@p' ], [ 1, '@s' ] ] }, { id: 'head' } ),
				P( 0, 0, 100, 31.2, 'M0 70 Q50 100 100 70 L100 73 Q50 103 0 73 Z', '@g' ),
				slot( '@g/70' ),
				...orgHeader( o, 6, 'center', '@w', '@g' ).map( ( e ) => ( e.id === 'logo' ? { ...e, w: 14, x: 43, h: sq( 14, ar ) } : e.id === 'org' ? { ...e, y: 6 + sq( 14, ar ) + 0.6 } : e.id === 'tag' ? { ...e, y: 6 + sq( 14, ar ) + 5.4 } : e ) ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.6, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@dk' } ),
				P( 30, y + 6.5, 40, 1, 'M0 50 L40 50 M60 50 L100 50', '', { s: '@g', sw: 0.35 } ),
				E( 48.6, y + 6.2, 2.8, 1.8, '@g' ),
				T( 10, y + 8, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@p', it: true, font: fp.b, field: roleField( kind ) } ),
				stack( 12, y + 12.5, 76, 86 - ( y + 12.5 ), kind, { font: fp.b, withId: true, lc: '@p', size: 3.2 } ),
				...signBlock( 62, 85.5, 32, { font: fp.b } ),
				R( 4, 1.2, 92, 97.6, '', { s: '@g/60', sw: 0.3, r: 2.5 } ),
				R( 0, 96.5, 100, 3.5, '@dk' ),
				cardTitle( 5, 96.6, 90, 3.3, { size: 2.2, font: fp.b, c: '@g' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: { g: 'lin', a: 90, s: [ [ 0, '@w' ], [ 1, '@lt' ] ] },
			els: [
				R( 0, 0, 100, 22, { g: 'lin', a: 135, s: [ [ 0, '@dk' ], [ 0.6, '@p' ], [ 1, '@s' ] ] } ),
				R( 0, 22, 100, 1, '@g' ),
				I( 3, 3, 9, 15, '{{logo}}', { fit: 'contain' } ),
				T( 13, 3, 70, 9, '{{org_name}}', 6, { wt: 700, c: '@w', font: fp.h } ),
				T( 13, 12, 70, 6, '{{org_tagline}}', 3.6, { c: '@g', font: fp.b } ),
				I( 6, 29, 22, ( 22 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, bc: '@g', bw: 0.9, ring: '@g/70', rw: 0.4, rg: 1.6, field: 'photo' } ),
				T( 34, 29, 62, 10, '{{name}}', 8, { wt: 700, font: fp.h, c: '@dk' } ),
				T( 34, 39, 62, 6, roleText( kind ), 4.4, { wt: 600, c: '@p', it: true, font: fp.b, field: roleField( kind ) } ),
				stack( 34, 47, 60, 36, kind, { font: fp.b, size: 4.1, withId: true, lc: '@p' } ),
				I( 72, 80, 24, 10, '{{sign}}', { fit: 'contain' } ),
				R( 2, 3, 96, 94, '', { s: '@g/50', sw: 0.3, r: 2.5 } ),
				R( 0, 94, 100, 6, '@dk' ),
				cardTitle( 4, 94.4, 92, 5, { size: 3.2, font: fp.b, c: '@g' } ),
			],
		};
	},
} );

/* 6. Geometric — corner triangles and hexagon photo. */
F.push( {
	name: 'Geometric',
	level: 'modern',
	back: 0,
	frames: [ 'hex', 'circle', 'squircle' ],
	p( o ) {
		const { ar, kind, fp, frame, deco, align } = o;
		const ph = photo( 50, 23, 38, ar, frame, { bc: '@p', bw: 1.2 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 60, 24, 'M0 0 L100 0 L0 100 Z', '@p' ),
				P( 40, 0, 60, 18, 'M0 0 L100 0 L100 100 Z', '@s' ),
				P( 25, 0, 50, 14, 'M0 0 L100 0 L50 100 Z', '@a' ),
				P( 0, 86, 50, 14, 'M0 0 L100 100 L0 100 Z', '@s' ),
				P( 50, 82, 50, 18, 'M100 0 L100 100 L0 100 Z', '@p' ),
				deco === 1 ? PAT( 0, 0, 100, 100, 'tri', { c: '@p/6', gap: 7, size: 0.8 } ) : R( 0, 0, 0, 0, '' ),
				slot( '@w/70' ),
				I( 41, 13, 18, sq( 18, ar ), '{{logo}}', { id: 'logo', fit: 'contain', shape: 'circle', bgc: '@w', bc: '@w', bw: 1.4 } ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h, up: deco === 2 } ),
				T( 6, y + 6, 88, 4.4, '{{org_name}}', 3.4, { al: 'center', wt: 700, c: '@p', font: fp.h, lines: 2, lh: 1.05 } ),
				T( 10, y + 10.4, 80, 3, roleText( kind ), 3.1, { al: 'center', wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 12, y + 14, 76, 82 - ( y + 14 ), kind, { font: fp.b, withId: true, size: 3.2 } ),
				cardTitle( 52, 92.5, 44, 5, { size: 2.7, font: fp.b, al: 'right' } ),
				I( 6, 83, 26, 5.5, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 30, 45, 'M0 0 L100 0 L0 100 Z', '@p' ),
				P( 0, 0, 18, 30, 'M0 0 L100 0 L0 100 Z', '@a' ),
				P( 74, 60, 26, 40, 'M100 0 L100 100 L0 100 Z', '@p' ),
				P( 84, 72, 16, 28, 'M100 0 L100 100 L0 100 Z', '@s' ),
				I( 8, 30, 22, ( 22 * ar ) * 1.1, '{{photo}}', { id: 'photo', shape: frame, bc: '@p', bw: 1.2, field: 'photo' } ),
				I( 36, 7, 9, 14, '{{logo}}', { fit: 'contain' } ),
				T( 46, 6, 50, 9, '{{org_name}}', 5.6, { wt: 700, c: '@p', font: fp.h } ),
				T( 46, 15, 50, 5, '{{org_tagline}}', 3.2, { c: '@m', font: fp.b } ),
				T( 36, 27, 58, 10, '{{name}}', 7.8, { wt: 700, font: fp.h } ),
				T( 36, 37, 58, 6, roleText( kind ), 4.4, { wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 36, 45, 50, 38, kind, { font: fp.b, size: 4.1, withId: true } ),
				cardTitle( 36, 86, 40, 8, { size: 3.8, font: fp.b, c: '@p', al: 'left' } ),
			],
		};
	},
} );

/* 7. Minimal Line — clean white with thin lines. */
F.push( {
	name: 'Minimal Line',
	level: 'simple',
	back: 2,
	frames: [ 'rect', 'circle', 'round' ],
	p( o ) {
		const { ar, kind, fp, frame, align } = o;
		const ph = photo( 50, 22, 36, ar, frame, {} );
		const y = photoBottom( ph ) + 2.5;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 1.2, '@p' ),
				...orgHeader( o, 5, align, '@p', '@m' ),
				R( 6, 18.5, 88, 0.35, '@p/40' ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.2, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.2, { al: 'center', c: '@m', font: fp.b, field: roleField( kind ) } ),
				R( 40, y + 10.4, 20, 0.4, '@a' ),
				stack( 12, y + 12.5, 76, 87 - ( y + 12.5 ), kind, { font: fp.b, withId: true, size: 3.2, vwt: 500 } ),
				R( 6, 89, 88, 0.35, '@p/40' ),
				cardTitle( 6, 90.5, 50, 4.5, { size: 2.6, font: fp.b, c: '@p', al: 'left' } ),
				I( 62, 89.8, 32, 5, '{{sign}}', { fit: 'contain' } ),
				R( 0, 98.8, 100, 1.2, '@p' ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 2, '@p' ),
				I( 4, 7, 8, 12, '{{logo}}', { fit: 'contain' } ),
				T( 14, 7, 82, 8, '{{org_name}}', 5.8, { wt: 700, c: '@p', font: fp.h } ),
				T( 14, 15, 82, 5, '{{org_tagline}}', 3.2, { c: '@m', font: fp.b } ),
				R( 4, 23, 92, 0.5, '@p/40' ),
				I( 4, 30, 22, ( 22 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, field: 'photo' } ),
				T( 31, 30, 65, 10, '{{name}}', 7.6, { wt: 700, font: fp.h } ),
				T( 31, 40, 65, 6, roleText( kind ), 4.2, { c: '@m', font: fp.b, field: roleField( kind ) } ),
				stack( 31, 49, 60, 34, kind, { font: fp.b, size: 4, withId: true, vwt: 500 } ),
				R( 4, 86, 92, 0.5, '@p/40' ),
				cardTitle( 4, 88, 50, 7, { size: 3.6, font: fp.b, c: '@p', al: 'left' } ),
				I( 70, 87, 26, 9, '{{sign}}', { fit: 'contain' } ),
				R( 0, 98, 100, 2, '@p' ),
			],
		};
	},
} );

/* 8. Midnight — dark premium with accent glow. */
F.push( {
	name: 'Midnight',
	level: 'premium',
	back: 1,
	frames: [ 'circle', 'squircle', 'arch' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 50, 23, 40, ar, frame, { ring: '@a', rw: 0.7, rg: 1.5 } );
		const y = photoBottom( ph ) + 2.4;
		return {
			bg: { g: 'lin', a: 160, s: [ [ 0, '#151a2e' ], [ 1, '#0b0e1a' ] ] },
			els: [
				E( 40, -12, 90, sq( 90, ar ), '@p/45' ),
				E( -30, 70, 70, sq( 70, ar ), '@a/18' ),
				deco === 1 ? PAT( 0, 0, 100, 100, 'dots', { c: '@w/6', gap: 4, size: 0.35 } ) : deco === 2 ? PAT( 0, 0, 100, 100, 'grid', { c: '@w/5', gap: 7, size: 0.25 } ) : R( 0, 0, 0, 0, '' ),
				slot( '@w/40' ),
				...orgHeader( o, 5.5, 'left', '@w', '@a' ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.6, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@w' } ),
				T( 10, y + 6.2, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@a', up: true, ls: 0.08, font: fp.b, field: roleField( kind ) } ),
				stack( 12, y + 11.5, 76, 86 - ( y + 11.5 ), kind, { font: fp.b, withId: true, lc: '@w/60', vc: '@w', size: 3.2 } ),
				R( 6, 88, 88, 0.3, '@a/60' ),
				cardTitle( 6, 90, 52, 5, { size: 2.7, font: fp.b, c: '@a', al: 'left' } ),
				Q( 80, 89.2, 14, 9, { id: 'qrf', c: '@k', bg: '@w' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: { g: 'lin', a: 120, s: [ [ 0, '#151a2e' ], [ 1, '#0b0e1a' ] ] },
			els: [
				E( 60, -40, 60, 60 * ar, '@p/45' ),
				E( -10, 60, 40, 40 * ar, '@a/18' ),
				I( 4, 6, 8, 12, '{{logo}}', { fit: 'contain' } ),
				T( 14, 6, 70, 8, '{{org_name}}', 5.6, { wt: 700, c: '@w', font: fp.h } ),
				T( 14, 14, 70, 5, '{{org_tagline}}', 3.2, { c: '@a', font: fp.b } ),
				I( 6, 30, 22, ( 22 * ar ) / ( frame === 'circle' || frame === 'squircle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, ring: '@a', rw: 0.7, rg: 1.5, field: 'photo' } ),
				T( 33, 30, 63, 10, '{{name}}', 8, { wt: 700, font: fp.h, c: '@w' } ),
				T( 33, 40, 63, 6, roleText( kind ), 4.4, { wt: 600, c: '@a', up: true, font: fp.b, field: roleField( kind ) } ),
				stack( 33, 49, 46, 36, kind, { font: fp.b, size: 4, withId: true, lc: '@w/60', vc: '@w' } ),
				Q( 82, 62, 14, 14 * ar, { id: 'qrf', c: '@k', bg: '@w' } ),
				cardTitle( 33, 88, 50, 8, { size: 3.6, font: fp.b, c: '@a', al: 'left' } ),
			],
		};
	},
} );

/* 9. Gradient Panel — full gradient with a white rounded panel. */
F.push( {
	name: 'Gradient Panel',
	level: 'premium',
	back: 0,
	frames: [ 'circle', 'round', 'squircle' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 50, 19, 36, ar, frame, { bc: '@w', bw: 1.8, sh: 2.5 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: { g: 'lin', a: 150, s: [ [ 0, '@p' ], [ 1, '@s' ] ] },
			els: [
				deco === 1 ? PAT( 0, 0, 100, 100, 'rings', { c: '@w/8', gap: 6, size: 0.35, cx: 0, cy: 0 } ) : deco === 2 ? PAT( 0, 0, 100, 100, 'dots', { c: '@w/12', gap: 4, size: 0.4 } ) : R( 0, 0, 0, 0, '' ),
				slot(),
				...orgHeader( o, 4.5, 'left', '@w', '@w/80' ),
				R( 6, 28, 88, 66, '@w', { r: 5, sh: 3, id: 'panel' } ),
				ph,
				T( 9, y, 82, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 12, y + 6, 76, 3.4, roleText( kind ), 3.2, { al: 'center', wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 13, y + 10.5, 74, 86 - ( y + 10.5 ), kind, { font: fp.b, withId: true, size: 3.1 } ),
				I( 56, 85.5, 34, 5.5, '{{sign}}', { fit: 'contain' } ),
				cardTitle( 6, 95, 88, 4, { size: 2.7, font: fp.b, c: '@w' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: { g: 'lin', a: 120, s: [ [ 0, '@p' ], [ 1, '@s' ] ] },
			els: [
				I( 3.5, 5, 8, 12, '{{logo}}', { fit: 'contain' } ),
				T( 13, 5, 60, 8, '{{org_name}}', 5.6, { wt: 700, c: '@w', font: fp.h } ),
				T( 13, 13, 60, 5, '{{org_tagline}}', 3.2, { c: '@w/80', font: fp.b } ),
				cardTitle( 60, 6, 36, 8, { size: 3.6, font: fp.b, al: 'right' } ),
				R( 3, 23, 94, 72, '@w', { r: 4, sh: 3 } ),
				I( 7, 30, 22, ( 22 * ar ) / ( frame === 'circle' || frame === 'squircle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@lt2', bw: 1.2, field: 'photo' } ),
				T( 33, 29, 61, 10, '{{name}}', 7.8, { wt: 700, font: fp.h } ),
				T( 33, 39, 61, 6, roleText( kind ), 4.2, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 33, 47, 60, 34, kind, { font: fp.b, size: 4, withId: true } ),
				I( 70, 81, 24, 10, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
} );

/* 10. Ribbon Corner — diagonal ribbon with the card title. */
F.push( {
	name: 'Ribbon Corner',
	level: 'professional',
	back: 2,
	frames: [ 'rect', 'round', 'circle' ],
	p( o ) {
		const { ar, kind, fp, frame, align } = o;
		const ph = photo( 50, 24, 38, ar, frame, { bc: '@p', bw: 1, ring: '@p/35', rw: 0.3, rg: 1.4 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 20, '@lt' ),
				P( 62, 0, 38, 24, 'M20 0 L50 0 L100 60 L100 100 Z', '@p' ),
				P( 62, 0, 38, 24, 'M50 0 L60 0 L100 46 L100 60 Z', '@a' ),
				slot( '@p/40' ),
				...orgHeader( o, 5, align, '@p', '@m' ).map( ( e ) => ( e.id === 'org' || e.id === 'tag' ? { ...e, w: align === 'center' ? e.w : 52 } : e ) ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 10, y + 10.5, 80, 86 - ( y + 10.5 ), kind, { font: fp.b, withId: true } ),
				P( 0, 86, 46, 14, 'M0 0 L60 0 L100 100 L0 100 Z', '@p' ),
				cardTitle( 2, 92.5, 38, 5, { size: 2.6, font: fp.b, al: 'left' } ),
				I( 60, 87, 34, 5.5, '{{sign}}', { fit: 'contain' } ),
				T( 60, 92.6, 34, 3, '{{signatory_title}}', 2.5, { al: 'center', wt: 600 } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 24, '@lt' ),
				P( 76, 0, 24, 40, 'M10 0 L45 0 L100 55 L100 100 Z', '@p' ),
				P( 76, 0, 24, 40, 'M45 0 L58 0 L100 42 L100 55 Z', '@a' ),
				I( 4, 5, 9, 14, '{{logo}}', { fit: 'contain' } ),
				T( 15, 5, 60, 8, '{{org_name}}', 5.8, { wt: 700, c: '@p', font: fp.h } ),
				T( 15, 13, 60, 5, '{{org_tagline}}', 3.2, { c: '@m', font: fp.b } ),
				I( 5, 31, 22, ( 22 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@p', bw: 1, field: 'photo' } ),
				T( 32, 31, 64, 10, '{{name}}', 7.8, { wt: 700, font: fp.h } ),
				T( 32, 41, 64, 6, roleText( kind ), 4.4, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 32, 49, 60, 34, kind, { font: fp.b, size: 4, withId: true } ),
				P( 0, 84, 40, 16, 'M0 0 L80 0 L100 100 L0 100 Z', '@p' ),
				cardTitle( 2, 87, 32, 10, { size: 3.6, font: fp.b, al: 'left' } ),
				I( 72, 83, 24, 9, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
} );

/* 11. Halo Circle — big circle behind the photo, pill name tag. */
F.push( {
	name: 'Halo Circle',
	level: 'modern',
	back: 0,
	frames: [ 'circle', 'circle', 'squircle' ],
	p( o ) {
		const { ar, kind, fp, frame, deco, align } = o;
		const ph = photo( 50, 23, 38, ar, frame, { bc: '@p', bw: 1.2 } );
		const halo = 66;
		const y = photoBottom( ph ) + 3;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 6, '@p' ),
				E( 50 - halo / 2, photoBottom( ph ) - sq( halo, ar ) * 0.78, halo, sq( halo, ar ), deco === 1 ? { g: 'lin', a: 135, s: [ [ 0, '@lt2' ], [ 1, '@lt' ] ] } : '@lt2' ),
				E( 50 - halo / 2 + 6, photoBottom( ph ) - sq( halo, ar ) * 0.78 + sq( 6, ar ), halo - 12, sq( halo - 12, ar ), '', { s: '@p/35', sw: 0.35, dash: [ 1, 1.2 ] } ),
				slot(),
				...orgHeader( o, 7.5, 'left', '@p', '@m' ).map( ( e ) => ( e.id === 'logo' ? { ...e, x: 6, y: 7.5 } : e ) ),
				ph,
				R( 14, y, 72, 7.4, '@p', { r: 4, id: 'pill' } ),
				T( 16, y + 0.3, 68, 6.8, '{{name}}', 5.8, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@w' } ),
				T( 10, y + 8.4, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 12, y + 13, 76, 88 - ( y + 13 ), kind, { font: fp.b, withId: true, size: 3.1 } ),
				cardTitle( 6, 90, 54, 4.6, { size: 2.6, font: fp.b, c: '@p', al: 'left' } ),
				I( 62, 88.5, 32, 5.5, '{{sign}}', { fit: 'contain' } ),
				R( 0, 96, 100, 4, '@a' ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				E( -18, -10, 58, 58 * ar, '@lt2' ),
				E( -14, -4, 50, 50 * ar, '', { s: '@p/35', sw: 0.35, dash: [ 1, 1.2 ] } ),
				I( 5, 18, 22, 22 * ar, '{{photo}}', { id: 'photo', shape: frame, bc: '@p', bw: 1.2, field: 'photo' } ),
				I( 44, 7, 9, 14, '{{logo}}', { fit: 'contain' } ),
				T( 55, 7, 41, 8, '{{org_name}}', 5.2, { wt: 700, c: '@p', font: fp.h, lines: 2, lh: 1.05 } ),
				R( 44, 26, 52, 12, '@p', { r: 6 } ),
				T( 46, 27, 48, 10, '{{name}}', 7, { wt: 700, font: fp.h, c: '@w', al: 'center' } ),
				T( 44, 40, 52, 6, roleText( kind ), 4.2, { wt: 600, c: '@a', font: fp.b, field: roleField( kind ), al: 'center' } ),
				stack( 44, 48, 52, 34, kind, { font: fp.b, size: 3.9, withId: true } ),
				cardTitle( 4, 86, 36, 8, { size: 3.6, font: fp.b, c: '@p', al: 'left' } ),
				I( 72, 83, 24, 9, '{{sign}}', { fit: 'contain' } ),
				R( 0, 96, 100, 4, '@a' ),
			],
		};
	},
} );

/* 12. Bottom Block — white top, coloured lower block with name. */
F.push( {
	name: 'Bottom Block',
	level: 'simple',
	back: 2,
	frames: [ 'round', 'rect', 'circle' ],
	p( o ) {
		const { ar, kind, fp, frame } = o;
		const ph = photo( 50, 21, 36, ar, frame, { bc: '@lt2', bw: 1.2 } );
		const block = photoBottom( ph ) + 1;
		return {
			bg: '@w',
			els: [
				slot( '@p/30' ),
				...orgHeader( o, 4.5, 'left', '@p', '@m' ),
				ph,
				R( 0, block, 100, 100 - block, '@p', { id: 'block' } ),
				P( 0, block - 4, 100, 4.2, 'M0 100 L0 60 Q50 -20 100 60 L100 100 Z', '@p' ),
				T( 6, block + 1.2, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@w' } ),
				T( 10, block + 7.2, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 10, block + 11.5, 80, 90 - ( block + 11.5 ), kind, { font: fp.b, withId: true, lc: '@w/70', vc: '@w', size: 3.1 } ),
				R( 0, 92, 100, 8, '@dk' ),
				cardTitle( 5, 93.4, 90, 5, { size: 2.9, font: fp.b } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				I( 4, 6, 9, 14, '{{logo}}', { fit: 'contain' } ),
				T( 15, 6, 80, 8, '{{org_name}}', 5.8, { wt: 700, c: '@p', font: fp.h } ),
				T( 15, 14, 80, 5, '{{org_tagline}}', 3.2, { c: '@m', font: fp.b } ),
				R( 0, 26, 100, 74, '@p' ),
				I( 5, 20, 22, ( 22 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@w', bw: 1.4, field: 'photo' } ),
				T( 32, 30, 64, 10, '{{name}}', 7.8, { wt: 700, font: fp.h, c: '@w' } ),
				T( 32, 40, 64, 6, roleText( kind ), 4.4, { wt: 600, c: '@a', font: fp.b, field: roleField( kind ) } ),
				stack( 32, 48, 62, 36, kind, { font: fp.b, size: 4, withId: true, lc: '@w/70', vc: '@w' } ),
				R( 0, 88, 100, 12, '@dk' ),
				cardTitle( 4, 89.5, 92, 9, { size: 4, font: fp.b } ),
			],
		};
	},
} );

/* 13. Dotted Header — patterned header with offset photo. */
F.push( {
	name: 'Dotted Header',
	level: 'modern',
	back: 1,
	frames: [ 'squircle', 'round', 'leaf' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 50, 22, 38, ar, frame, { bc: '@w', bw: 1.5, sh: 2 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 30, '@p', { id: 'head' } ),
				PAT( 0, 0, 100, 30, [ 'dots', 'tri', 'chevron' ][ deco ] || 'dots', { c: '@w/16', gap: 3.2, size: 0.55 } ),
				R( 0, 30, 100, 2, '@a' ),
				slot(),
				...orgHeader( o, 5, 'left', '@w', '@w/80' ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.3, { al: 'center', wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 10, y + 10.5, 80, 87 - ( y + 10.5 ), kind, { font: fp.b, withId: true } ),
				R( 6, 89.5, 40, 5.5, '@lt', { r: 2.75 } ),
				cardTitle( 6, 89.5, 40, 5.5, { size: 2.4, font: fp.b, c: '@p' } ),
				I( 58, 88, 36, 6, '{{sign}}', { fit: 'contain' } ),
				PAT( 0, 97, 100, 3, 'dots', { c: '@p/40', gap: 2, size: 0.45 } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame, deco } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 30, 100, '@p' ),
				PAT( 0, 0, 30, 100, [ 'dots', 'tri', 'chevron' ][ deco ] || 'dots', { c: '@w/16', gap: 4, size: 0.6 } ),
				R( 30, 0, 1.2, 100, '@a' ),
				I( 4, 22, 22, ( 22 * ar ) / ( frame === 'squircle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@w', bw: 1.5, sh: 2, field: 'photo' } ),
				I( 9, 4, 12, 15, '{{logo}}', { fit: 'contain' } ),
				T( 35, 6, 61, 8, '{{org_name}}', 5.6, { wt: 700, c: '@p', font: fp.h } ),
				T( 35, 14, 61, 5, '{{org_tagline}}', 3.2, { c: '@m', font: fp.b } ),
				T( 35, 25, 61, 10, '{{name}}', 7.8, { wt: 700, font: fp.h } ),
				T( 35, 35, 61, 6, roleText( kind ), 4.4, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 35, 44, 61, 38, kind, { font: fp.b, size: 4, withId: true } ),
				T( 2, 86, 26, 8, '{{card_title}}', 3.2, { al: 'center', wt: 700, up: true, c: '@w', font: fp.b } ),
				I( 72, 84, 24, 9, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
} );

/* 14. Heritage Frame — inner border with corner ornaments, serif. */
F.push( {
	name: 'Heritage Frame',
	level: 'professional',
	back: 2,
	frames: [ 'rect', 'arch', 'circle' ],
	p( o ) {
		const { ar, kind, fp, frame } = o;
		const ph = photo( 50, 26, 34, ar, frame, { bc: '@p', bw: 0.8 } );
		const y = photoBottom( ph ) + 2;
		const corner = ( x, y2, d ) => P( x, y2, 10, 10 * ar, d, '', { s: '@a', sw: 0.6 } );
		return {
			bg: '@lt',
			els: [
				R( 3.5, 2.2, 93, 95.6, '@w', { s: '@p', sw: 0.6, r: 1.5 } ),
				R( 5.5, 3.5, 89, 93, '', { s: '@p/40', sw: 0.3, r: 1 } ),
				corner( 7, 5, 'M0 100 L0 0 L100 0 M20 100 L20 20 L100 20' ),
				corner( 83, 5, 'M100 100 L100 0 L0 0 M80 100 L80 20 L0 20' ),
				corner( 7, 95 - 10 * ar, 'M0 0 L0 100 L100 100 M20 0 L20 80 L100 80' ),
				corner( 83, 95 - 10 * ar, 'M100 0 L100 100 L0 100 M80 0 L80 80 L0 80' ),
				...orgHeader( o, 7.5, 'center', '@p', '@m' ).map( ( e ) => ( e.id === 'logo' ? { ...e, x: 43, w: 14, h: sq( 14, ar ) } : e.id === 'org' ? { ...e, y: 7.5 + sq( 14, ar ) + 0.5, x: 10, w: 80 } : e.id === 'tag' ? { ...e, y: 7.5 + sq( 14, ar ) + 5.3 } : e ) ),
				ph,
				T( 8, y, 84, 6, '{{name}}', 6.2, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@p' } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.2, { al: 'center', it: true, c: '@t', font: fp.b, field: roleField( kind ) } ),
				stack( 13, y + 10.5, 74, 86 - ( y + 10.5 ), kind, { font: fp.b, withId: true, size: 3.1 } ),
				cardTitle( 10, 86.5, 80, 3.6, { size: 2.6, font: fp.h, c: '@p' } ),
				I( 56, 90, 30, 4.5, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@lt',
			els: [
				R( 2, 3.5, 96, 93, '@w', { s: '@p', sw: 0.6, r: 1.5 } ),
				R( 3.3, 5.5, 93.4, 89, '', { s: '@p/40', sw: 0.3, r: 1 } ),
				I( 6, 9, 8, 12, '{{logo}}', { fit: 'contain' } ),
				T( 16, 9, 78, 8, '{{org_name}}', 5.6, { wt: 700, c: '@p', font: fp.h } ),
				T( 16, 17, 78, 5, '{{org_tagline}}', 3.2, { c: '@m', font: fp.b } ),
				I( 7, 30, 20, ( 20 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, bc: '@p', bw: 0.8, field: 'photo' } ),
				T( 31, 29, 63, 10, '{{name}}', 7.6, { wt: 700, font: fp.h, c: '@p' } ),
				T( 31, 39, 63, 6, roleText( kind ), 4.2, { it: true, font: fp.b, field: roleField( kind ) } ),
				stack( 31, 47, 58, 34, kind, { font: fp.b, size: 3.9, withId: true } ),
				cardTitle( 6, 84, 44, 7, { size: 3.6, font: fp.h, c: '@p', al: 'left' } ),
				I( 68, 80, 24, 10, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
} );

/* 15. Crest — shield crest behind the logo, curved header. */
F.push( {
	name: 'Crest',
	level: 'premium',
	back: 1,
	frames: [ 'round', 'circle', 'arch' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 50, 32, 30, ar, frame, { bc: '@p', bw: 1, ring: '@g/80', rw: 0.4, rg: 1.3 } );
		const y = photoBottom( ph ) + 2;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 100, 22, 'M0 0 L100 0 L100 80 C70 100 30 100 0 80 Z', { g: 'lin', a: 180, s: [ [ 0, '@dk' ], [ 1, '@p' ] ] } ),
				deco === 1 ? PAT( 0, 0, 100, 18, 'lines', { c: '@w/8', gap: 2.6, size: 0.3 } ) : R( 0, 0, 0, 0, '' ),
				slot( '@g/70' ),
				T( 6, 5, 88, 5, '{{org_name}}', 4.6, { id: 'org', al: 'center', wt: 700, c: '@w', font: fp.h, lines: 2, lh: 1.05 } ),
				T( 8, 10.5, 84, 3, '{{org_tagline}}', 2.6, { al: 'center', c: '@g', font: fp.b } ),
				I( 39, 14.5, 22, sq( 22, ar ) * 1.15, '', { shape: 'shield', bgc: '@w', bc: '@g', bw: 0.9, sh: 2 } ),
				I( 42.5, 16, 15, sq( 15, ar ), '{{logo}}', { id: 'logo', fit: 'contain' } ),
				ph,
				T( 6, y, 88, 6, '{{name}}', 6.4, { id: 'name', al: 'center', wt: 700, font: fp.h, c: '@dk' } ),
				T( 10, y + 6, 80, 3.4, roleText( kind ), 3.2, { al: 'center', wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 12, y + 10.5, 76, 88 - ( y + 10.5 ), kind, { font: fp.b, withId: true, size: 3.1, lc: '@p' } ),
				R( 0, 90.5, 100, 9.5, '@dk' ),
				R( 0, 90.5, 100, 0.6, '@g' ),
				cardTitle( 5, 91.8, 58, 6.6, { size: 2.8, font: fp.b, c: '@g', al: 'left' } ),
				I( 64, 91.6, 31, 5, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				R( 0, 0, 100, 26, { g: 'lin', a: 180, s: [ [ 0, '@dk' ], [ 1, '@p' ] ] } ),
				R( 0, 26, 100, 0.8, '@g' ),
				I( 4, 3, 10, 22, '', { shape: 'shield', bgc: '@w', bc: '@g', bw: 0.9 } ),
				I( 5.2, 5, 7.6, 13, '{{logo}}', { fit: 'contain' } ),
				T( 17, 4, 79, 10, '{{org_name}}', 6.4, { wt: 700, c: '@w', font: fp.h } ),
				T( 17, 14, 79, 6, '{{org_tagline}}', 3.6, { c: '@g', font: fp.b } ),
				I( 6, 33, 21, ( 21 * ar ) / ( frame === 'circle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, r: 3, bc: '@p', bw: 1, ring: '@g/80', rw: 0.4, rg: 1.3, field: 'photo' } ),
				T( 32, 32, 64, 10, '{{name}}', 7.8, { wt: 700, font: fp.h, c: '@dk' } ),
				T( 32, 42, 64, 6, roleText( kind ), 4.2, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 32, 50, 60, 32, kind, { font: fp.b, size: 3.9, withId: true, lc: '@p' } ),
				R( 0, 88, 100, 12, '@dk' ),
				cardTitle( 4, 89, 60, 10, { size: 4, font: fp.b, c: '@g', al: 'left' } ),
				I( 70, 76, 26, 10, '{{sign}}', { fit: 'contain' } ),
			],
		};
	},
} );

/* 16. Tech Edge — sharp cuts, left-aligned bold name. */
F.push( {
	name: 'Tech Edge',
	level: 'modern',
	back: 0,
	frames: [ 'cut', 'rect', 'squircle' ],
	p( o ) {
		const { ar, kind, fp, frame, deco } = o;
		const ph = photo( 30, 22, 40, ar, frame, { bc: '@a', bw: 1 } );
		const y = photoBottom( ph ) + 2.5;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 100, 18, 'M0 0 L100 0 L100 60 L70 100 L0 100 Z', '@k' ),
				P( 60, 0, 40, 20, 'M30 100 L55 70 L100 70 L100 76 L58 76 L36 100 Z', '@a' ),
				deco === 1 ? PAT( 0, 0, 100, 18, 'grid', { c: '@w/7', gap: 4, size: 0.25 } ) : R( 0, 0, 0, 0, '' ),
				slot( '@w/40' ),
				...orgHeader( o, 5, 'left', '@w', '@a' ),
				ph,
				P( 56, 22, 38, photoH( 40, ar ), 'M0 0 L100 0 L100 100 L0 100 Z', '@lt' ),
				T( 58, 23.5, 34, 3, 'ID NO.', 2.4, { wt: 700, c: '@m', ls: 0.12, font: fp.b, field: 'id_no' } ),
				T( 58, 26.5, 34, 4.5, '{{id_no}}', 4, { wt: 700, c: '@k', font: fp.h, field: 'id_no' } ),
				T( 58, 32.5, 34, 3, 'VALID TILL', 2.4, { wt: 700, c: '@m', ls: 0.12, font: fp.b, field: 'valid_until' } ),
				T( 58, 35.5, 34, 4, '{{valid_until}}', 3.4, { wt: 600, c: '@k', font: fp.b, field: 'valid_until' } ),
				Q( 64, 41, 22, 11, { id: 'qrf' } ),
				T( 8, y, 86, 6.5, '{{name}}', 7, { id: 'name', wt: 700, font: fp.h, up: deco !== 1 } ),
				R( 8, y + 7, 14, 0.8, '@a' ),
				T( 8, y + 8.5, 86, 3.4, roleText( kind ), 3.4, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 8, y + 13, 84, 88 - ( y + 13 ), kind, { font: fp.b, size: 3.2 } ),
				P( 0, 90, 100, 10, 'M0 100 L0 30 L30 30 L40 0 L100 0 L100 100 Z', '@k' ),
				cardTitle( 34, 92.5, 62, 5, { size: 2.8, font: fp.b, c: '@a', al: 'right' } ),
			],
		};
	},
	l( o ) {
		const { ar, kind, fp, frame } = o;
		return {
			bg: '@w',
			els: [
				P( 0, 0, 100, 24, 'M0 0 L100 0 L100 100 L40 100 L32 70 L0 70 Z', '@k' ),
				R( 60, 22, 40, 2, '@a' ),
				I( 4, 3, 7, 11, '{{logo}}', { fit: 'contain' } ),
				T( 13, 3, 60, 7, '{{org_name}}', 5, { wt: 700, c: '@w', font: fp.h } ),
				T( 13, 10, 60, 5, '{{org_tagline}}', 3, { c: '@a', font: fp.b } ),
				cardTitle( 50, 13, 46, 7, { size: 3.4, font: fp.b, c: '@a', al: 'right' } ),
				I( 4, 22, 22, ( 22 * ar ) / ( frame === 'squircle' ? 1 : 0.8 ), '{{photo}}', { id: 'photo', shape: frame, bc: '@a', bw: 1, field: 'photo' } ),
				T( 30, 30, 66, 10, '{{name}}', 8, { wt: 700, font: fp.h, up: true } ),
				R( 30, 41, 10, 1.2, '@a' ),
				T( 30, 43, 66, 6, roleText( kind ), 4.4, { wt: 600, c: '@p', font: fp.b, field: roleField( kind ) } ),
				stack( 30, 51, 46, 40, kind, { font: fp.b, size: 4, withId: true } ),
				Q( 79, 56, 17, 17 * ar, { id: 'qrf' } ),
			],
		};
	},
} );

export const FAMILIES = F;

/* ---------- Expansion ---------- */

/** Built-in template order: 17 families x 3 variants, first 50 used per sub-type. */
export function builtinRecipes() {
	const out = [];
	for ( let v = 0; v < 3; v++ ) {
		for ( let f = 0; f < F.length; f++ ) {
			out.push( { f, v } );
		}
	}
	return out.slice( 0, 50 );
}

export function recipeName( r ) {
	const fam = F[ r.f ];
	return fam ? `${ fam.name } ${ [ 'I', 'II', 'III' ][ r.v ] || '' }`.trim() : 'Design';
}

export function recipeLevel( r ) {
	return F[ r.f ] ? F[ r.f ].level : 'simple';
}

/** Round numbers to keep saved JSON compact. */
function tidy( obj ) {
	if ( Array.isArray( obj ) ) {
		return obj.map( tidy );
	}
	if ( obj && typeof obj === 'object' ) {
		const o = {};
		Object.keys( obj ).forEach( ( k ) => {
			const v = obj[ k ];
			if ( v === undefined || v === '' && k === 't' ) {
				return;
			}
			o[ k ] = tidy( v );
		} );
		return o;
	}
	return typeof obj === 'number' ? Math.round( obj * 100 ) / 100 : obj;
}

let counter = 0;

/** Give every element a stable id (needed by the editor). */
function ids( side ) {
	side.els = side.els.filter( ( e ) => ! ( e.t === 'rect' && e.w === 0 && e.h === 0 ) );
	side.els.forEach( ( e ) => {
		if ( ! e.id ) {
			e.id = 'e' + ( ++counter ).toString( 36 );
		}
	} );
	return side;
}

/**
 * Expand a recipe into a full layout for one orientation.
 *
 * @param {{f:number,v:number}} recipe
 * @param {'portrait'|'landscape'} orientation
 * @param {number} ar      Card width / height in this orientation.
 * @param {'student'|'staff'|'other'} kind
 * @return {{front:object, back:object}}
 */
export function expand( recipe, orientation, ar, kind = 'student' ) {
	const fam = F[ recipe.f ] || F[ 0 ];
	const v = recipe.v || 0;
	const o = {
		ar,
		kind,
		v,
		fp: FP[ ( recipe.f * 3 + v * 4 ) % FP.length ],
		frame: fam.frames[ v ] || 'round',
		deco: v,
		align: v === 1 ? 'center' : 'left',
	};
	counter = 0;
	const land = orientation === 'landscape';
	const front = land ? fam.l( o ) : fam.p( o );
	const back = land ? backLandscape( o, fam.back ) : backPortrait( o, fam.back );
	return tidy( { front: ids( front ), back: ids( back ) } );
}

/**
 * Resolve a template's layout for an orientation: built-in recipes are
 * expanded; custom JSON templates are returned as stored.
 */
export function layoutFor( template, orientation, ar, kind ) {
	const data = typeof template.layout === 'string' ? JSON.parse( template.layout || '{}' ) : template.layout || {};
	if ( data.recipe ) {
		return expand( data.recipe, orientation, ar, kind );
	}
	const key = orientation === 'landscape' ? 'landscape' : 'portrait';
	if ( data[ key ] ) {
		return data[ key ];
	}
	// Single-orientation custom design (e.g. uploaded artwork): use what exists.
	return data.portrait || data.landscape || { front: { bg: '@w', els: [] }, back: { bg: '@w', els: [] } };
}
