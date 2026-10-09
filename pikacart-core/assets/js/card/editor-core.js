/**
 * Pikacart card editor (used by organisations and by the Super Admin
 * template builder). Canva-style: tools on the left, card in the middle with a
 * front/back switch, properties on the right, zoom below.
 *
 * Move, resize and rotate elements, alignment guides and snapping, undo/redo,
 * duplicate, delete, lock, layer order, fonts, colours, images, QR, barcode,
 * background, and "Reset to original".
 *
 * The editor draws with the same renderer used for downloads, so the editor
 * shows exactly what will print.
 */

import { prepare, drawSide, isVisible, boxPx } from './render.js';
import { tokens } from './color.js';
import { FONTS } from './fonts.js';
import { rows as detailRows } from './families.js';

const { __, sprintf } = window.wp.i18n;

const HANDLE = 9;
const SNAP = 0.9; // % of card

function esc( s ) {
	return String( s ?? '' ).replace( /[&<>"']/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ] ) );
}
function icon( name ) {
	return `<svg class="pkc-i" aria-hidden="true"><use href="#i-${ name }"></use></svg>`;
}
const clone = ( o ) => JSON.parse( JSON.stringify( o ) );
const uid = () => 'n' + Math.random().toString( 36 ).slice( 2, 9 );
const round = ( v ) => Math.round( v * 100 ) / 100;

export const FIELD_TOKENS = () => [
	[ '{{name}}', __( 'Name', 'pikacart' ) ],
	[ '{{id_no}}', __( 'ID number', 'pikacart' ) ],
	[ '{{class_section}}', __( 'Class & section', 'pikacart' ) ],
	[ '{{designation}}', __( 'Designation', 'pikacart' ) ],
	[ '{{department}}', __( 'Department', 'pikacart' ) ],
	[ '{{mobile}}', __( 'Mobile', 'pikacart' ) ],
	[ '{{email}}', __( 'Email', 'pikacart' ) ],
	[ '{{dob}}', __( 'Date of birth', 'pikacart' ) ],
	[ '{{blood_group}}', __( 'Blood group', 'pikacart' ) ],
	[ '{{guardian}}', __( 'Father / guardian', 'pikacart' ) ],
	[ '{{address}}', __( 'Address', 'pikacart' ) ],
	[ '{{emergency}}', __( 'Emergency contact', 'pikacart' ) ],
	[ '{{valid_until}}', __( 'Valid till', 'pikacart' ) ],
	[ '{{session}}', __( 'Session', 'pikacart' ) ],
	[ '{{card_title}}', __( 'Card title', 'pikacart' ) ],
	[ '{{org_name}}', __( 'Organisation name', 'pikacart' ) ],
	[ '{{org_tagline}}', __( 'Tagline', 'pikacart' ) ],
	[ '{{org_address}}', __( 'Organisation address', 'pikacart' ) ],
	[ '{{org_phone}}', __( 'Organisation phone', 'pikacart' ) ],
	[ '{{org_website}}', __( 'Website', 'pikacart' ) ],
	[ '{{signatory_title}}', __( 'Signatory title', 'pikacart' ) ],
	[ '{{cf1}}', __( 'Custom field 1', 'pikacart' ) ],
	[ '{{cf2}}', __( 'Custom field 2', 'pikacart' ) ],
	[ '{{cf3}}', __( 'Custom field 3', 'pikacart' ) ],
];

const COLOR_TOKENS = [ '@p', '@s', '@a', '@t', '@w', '@k', '@lt', '@dk', '@g', '@m' ];

/**
 * @param {HTMLElement} root
 * @param {object} cfg
 *   doc        {front, back}  layout to edit (cloned)
 *   original   () => {front, back}   for "Reset to original"
 *   size       () => {w,h} mm
 *   options    (side) => render options (palette, vars, fields, flags, demo)
 *   onChange   (doc) => void   called after every change
 *   upload     (file) => Promise<url>
 *   people     [{id, name}] optional list for live preview
 *   onPerson   (id) => void
 */
export function createEditor( root, cfg ) {
	let doc = clone( cfg.doc );
	let side = 'front';
	let sel = null; // element id
	let zoom = 1;
	let fit = 1;
	const undo = [];
	const redo = [];
	let drag = null;
	let guides = [];
	let raf = 0;
	let destroyed = false;

	root.innerHTML = `<div class="ed">
		<aside class="ed-tools" aria-label="${ esc( __( 'Add to card', 'pikacart' ) ) }">
			<p class="ed-label">${ esc( __( 'Add', 'pikacart' ) ) }</p>
			<button type="button" data-add="text">${ icon( 'edit' ) }<span>${ esc( __( 'Text', 'pikacart' ) ) }</span></button>
			<div class="ed-field-add"><button type="button" data-add-field>${ icon( 'user' ) }<span>${ esc( __( 'Person field', 'pikacart' ) ) }</span></button>
				<div class="ed-menu" data-field-menu hidden>${ FIELD_TOKENS().map( ( [ t, l ] ) => `<button type="button" data-token="${ esc( t ) }">${ esc( l ) }</button>` ).join( '' ) }</div></div>
			<button type="button" data-add="rect"><span class="ed-shape sq"></span><span>${ esc( __( 'Rectangle', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="ellipse"><span class="ed-shape ci"></span><span>${ esc( __( 'Circle', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="line"><span class="ed-shape ln"></span><span>${ esc( __( 'Line', 'pikacart' ) ) }</span></button>
			<label class="ed-upload">${ icon( 'image' ) }<span>${ esc( __( 'Image', 'pikacart' ) ) }</span><input type="file" accept="image/png,image/jpeg,image/webp" data-add-image hidden></label>
			<button type="button" data-add="photo">${ icon( 'user' ) }<span>${ esc( __( 'Photo frame', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="logo">${ icon( 'building' ) }<span>${ esc( __( 'Logo', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="sign">${ icon( 'edit' ) }<span>${ esc( __( 'Signature', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="seal">${ icon( 'shield' ) }<span>${ esc( __( 'Seal', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="qr">${ icon( 'idcard' ) }<span>${ esc( __( 'QR code', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="bar">${ icon( 'receipt' ) }<span>${ esc( __( 'Barcode', 'pikacart' ) ) }</span></button>
			<button type="button" data-add="stack">${ icon( 'menu' ) }<span>${ esc( __( 'Details block', 'pikacart' ) ) }</span></button>
		</aside>
		<section class="ed-stage">
			<div class="ed-topbar">
				<div class="seg" role="group"><button type="button" data-side="front" class="is-on">${ esc( __( 'Front', 'pikacart' ) ) }</button><button type="button" data-side="back">${ esc( __( 'Back', 'pikacart' ) ) }</button></div>
				<div class="ed-actions">
					<button type="button" class="icon-btn" data-undo title="${ esc( __( 'Undo (Ctrl+Z)', 'pikacart' ) ) }" aria-label="${ esc( __( 'Undo', 'pikacart' ) ) }">${ icon( 'chevron-left' ) }</button>
					<button type="button" class="icon-btn" data-redo title="${ esc( __( 'Redo (Ctrl+Y)', 'pikacart' ) ) }" aria-label="${ esc( __( 'Redo', 'pikacart' ) ) }">${ icon( 'chevron-right' ) }</button>
					<button type="button" class="btn btn-sm" data-reset>${ icon( 'refresh' ) }${ esc( __( 'Reset to original', 'pikacart' ) ) }</button>
				</div>
				${ cfg.people && cfg.people.length ? `<label class="ed-person">${ esc( __( 'Preview with', 'pikacart' ) ) } <select data-person><option value="">${ esc( __( 'Sample person', 'pikacart' ) ) }</option>${ cfg.people.map( ( p ) => `<option value="${ p.id }">${ esc( p.name ) }</option>` ).join( '' ) }</select></label>` : '' }
			</div>
			<div class="ed-canvas-wrap" data-wrap>
				<div class="ed-canvas-box" data-box>
					<canvas class="ed-card" data-card></canvas>
					<canvas class="ed-overlay" data-overlay tabindex="0" aria-label="${ esc( __( 'Card canvas. Click an element to select it; use arrow keys to move it.', 'pikacart' ) ) }"></canvas>
				</div>
			</div>
			<div class="ed-zoom">
				<button type="button" class="icon-btn" data-zoom="-" aria-label="${ esc( __( 'Zoom out', 'pikacart' ) ) }">−</button>
				<span data-zoom-label>100%</span>
				<button type="button" class="icon-btn" data-zoom="+" aria-label="${ esc( __( 'Zoom in', 'pikacart' ) ) }">+</button>
				<button type="button" class="btn btn-sm" data-zoom="fit">${ esc( __( 'Fit', 'pikacart' ) ) }</button>
				<span class="muted small ed-hint">${ esc( __( 'Tip: drag to move, corners to resize, the round handle to rotate. Hold Shift to keep proportions.', 'pikacart' ) ) }</span>
			</div>
		</section>
		<aside class="ed-props" data-props aria-label="${ esc( __( 'Properties', 'pikacart' ) ) }"></aside>
	</div>`;

	const $ = ( s ) => root.querySelector( s );
	const card = $( '[data-card]' );
	const overlay = $( '[data-overlay]' );
	const wrap = $( '[data-wrap]' );
	const box = $( '[data-box]' );
	const props = $( '[data-props]' );
	const cctx = card.getContext( '2d' );
	const octx = overlay.getContext( '2d' );

	const els = () => doc[ side ].els;
	const find = ( id ) => els().find( ( e ) => e.id === id );
	const opts = () => Object.assign( { side, editor: true }, cfg.options( side ) );

	/* ---------- Size and drawing ---------- */

	let W = 0;
	let H = 0;
	let dpr = 1;
	function layoutCanvas() {
		const size = cfg.size();
		const availW = Math.max( 200, wrap.clientWidth - 32 );
		const availH = Math.max( 260, Math.min( window.innerHeight * 0.68, 760 ) );
		fit = Math.min( availW / size.w, availH / size.h );
		const scale = fit * zoom;
		dpr = Math.min( 2, window.devicePixelRatio || 1 );
		W = Math.round( size.w * scale );
		H = Math.round( size.h * scale );
		[ card, overlay ].forEach( ( c ) => {
			c.width = W * dpr;
			c.height = H * dpr;
			c.style.width = W + 'px';
			c.style.height = H + 'px';
		} );
		box.style.width = W + 'px';
		box.style.height = H + 'px';
		$( '[data-zoom-label]' ).textContent = Math.round( zoom * 100 ) + '%';
	}

	function drawCard() {
		cctx.setTransform( 1, 0, 0, 1, 0, 0 );
		cctx.clearRect( 0, 0, card.width, card.height );
		drawSide( cctx, doc[ side ], Object.assign( opts(), { W: W * dpr, H: H * dpr, bleed: 0 } ) );
	}

	function elBox( el ) {
		const b = boxPx( el, W, H );
		return { ...b, cx: b.x + b.w / 2, cy: b.y + b.h / 2, a: ( ( el.rot || 0 ) * Math.PI ) / 180 };
	}

	function handlePoints( b ) {
		const pts = [];
		for ( const sy of [ -1, 0, 1 ] ) {
			for ( const sx of [ -1, 0, 1 ] ) {
				if ( sx || sy ) {
					pts.push( { sx, sy, ...toWorld( b, ( sx * b.w ) / 2, ( sy * b.h ) / 2 ) } );
				}
			}
		}
		pts.push( { rot: true, ...toWorld( b, 0, -b.h / 2 - 22 ) } );
		return pts;
	}

	function toWorld( b, lx, ly ) {
		return { x: b.cx + lx * Math.cos( b.a ) - ly * Math.sin( b.a ), y: b.cy + lx * Math.sin( b.a ) + ly * Math.cos( b.a ) };
	}
	function toLocal( b, x, y ) {
		const dx = x - b.cx;
		const dy = y - b.cy;
		return { x: dx * Math.cos( -b.a ) - dy * Math.sin( -b.a ), y: dx * Math.sin( -b.a ) + dy * Math.cos( -b.a ) };
	}

	function drawOverlay() {
		octx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		octx.clearRect( 0, 0, W, H );
		// Lanyard punch zone hint (portrait cards).
		if ( H > W ) {
			octx.strokeStyle = 'rgba(220,38,69,0.35)';
			octx.setLineDash( [ 3, 3 ] );
			octx.strokeRect( W * 0.38, 1, W * 0.24, H * 0.045 );
			octx.setLineDash( [] );
		}
		guides.forEach( ( g ) => {
			octx.strokeStyle = '#ec4899';
			octx.lineWidth = 1;
			octx.beginPath();
			if ( g.x !== undefined ) {
				octx.moveTo( g.x, 0 );
				octx.lineTo( g.x, H );
			} else {
				octx.moveTo( 0, g.y );
				octx.lineTo( W, g.y );
			}
			octx.stroke();
		} );
		const el = sel && find( sel );
		if ( ! el ) {
			return;
		}
		const b = elBox( el );
		octx.save();
		octx.translate( b.cx, b.cy );
		octx.rotate( b.a );
		octx.strokeStyle = el.lock ? '#9aa1b5' : '#4F46E5';
		octx.lineWidth = 1.5;
		octx.setLineDash( el.lock ? [ 4, 3 ] : [] );
		octx.strokeRect( -b.w / 2, -b.h / 2, b.w, b.h );
		octx.restore();
		if ( el.lock ) {
			return;
		}
		handlePoints( b ).forEach( ( p ) => {
			octx.beginPath();
			if ( p.rot ) {
				octx.arc( p.x, p.y, 6, 0, Math.PI * 2 );
			} else {
				octx.rect( p.x - HANDLE / 2, p.y - HANDLE / 2, HANDLE, HANDLE );
			}
			octx.fillStyle = '#ffffff';
			octx.fill();
			octx.strokeStyle = '#4F46E5';
			octx.lineWidth = 1.5;
			octx.stroke();
		} );
	}

	function redraw() {
		cancelAnimationFrame( raf );
		raf = requestAnimationFrame( () => {
			if ( destroyed ) {
				return;
			}
			drawCard();
			drawOverlay();
		} );
	}

	async function full() {
		layoutCanvas();
		await prepare( doc[ side ], opts() );
		if ( ! destroyed ) {
			drawCard();
			drawOverlay();
		}
	}

	/* ---------- History ---------- */

	function snapshot() {
		undo.push( JSON.stringify( doc ) );
		if ( undo.length > 60 ) {
			undo.shift();
		}
		redo.length = 0;
	}

	function commit() {
		cfg.onChange( clone( doc ) );
	}

	function change( fn, keepHistory = true ) {
		if ( keepHistory ) {
			snapshot();
		}
		fn();
		commit();
		redraw();
	}

	/* ---------- Hit testing ---------- */

	function pointer( e ) {
		const r = overlay.getBoundingClientRect();
		return { x: e.clientX - r.left, y: e.clientY - r.top };
	}

	function hit( p ) {
		const o = opts();
		const list = els();
		for ( let i = list.length - 1; i >= 0; i-- ) {
			const el = list[ i ];
			if ( ! isVisible( el, o ) || el.w <= 0 || el.h <= 0 ) {
				continue;
			}
			const b = elBox( el );
			const l = toLocal( b, p.x, p.y );
			if ( Math.abs( l.x ) <= b.w / 2 + 2 && Math.abs( l.y ) <= b.h / 2 + 2 ) {
				// Skip big full-card backgrounds when something smaller is underneath the pointer.
				if ( el.w >= 99 && el.h >= 99 && ! el.lock ) {
					continue;
				}
				return el;
			}
		}
		return null;
	}

	function hitHandle( p ) {
		const el = sel && find( sel );
		if ( ! el || el.lock ) {
			return null;
		}
		return handlePoints( elBox( el ) ).find( ( h ) => Math.hypot( h.x - p.x, h.y - p.y ) <= 9 ) || null;
	}

	/* ---------- Snapping ---------- */

	function snapMove( el ) {
		guides = [];
		const targetsX = [ 0, 50, 100 ];
		const targetsY = [ 0, 50, 100 ];
		els().forEach( ( o ) => {
			if ( o.id !== el.id && ! o.hide && o.w < 99 ) {
				targetsX.push( o.x, o.x + o.w / 2, o.x + o.w );
				targetsY.push( o.y, o.y + o.h / 2, o.y + o.h );
			}
		} );
		const snapAxis = ( pos, size, targets, isX ) => {
			const cands = [ pos, pos + size / 2, pos + size ];
			let best = null;
			cands.forEach( ( c, i ) => targets.forEach( ( t ) => {
				const d = Math.abs( c - t );
				if ( d < SNAP && ( ! best || d < best.d ) ) {
					best = { d, shift: t - c, t };
				}
			} ) );
			if ( best ) {
				guides.push( isX ? { x: ( best.t / 100 ) * W } : { y: ( best.t / 100 ) * H } );
				return pos + best.shift;
			}
			return pos;
		};
		el.x = round( snapAxis( el.x, el.w, targetsX, true ) );
		el.y = round( snapAxis( el.y, el.h, targetsY, false ) );
	}

	/* ---------- Pointer interaction ---------- */

	overlay.addEventListener( 'pointerdown', ( e ) => {
		overlay.focus();
		const p = pointer( e );
		const h = hitHandle( p );
		const el = sel && find( sel );
		if ( h && el ) {
			snapshot();
			drag = { mode: h.rot ? 'rotate' : 'resize', h, start: p, orig: clone( el ), b: elBox( el ) };
		} else {
			const target = hit( p );
			select( target ? target.id : null );
			if ( target && ! target.lock ) {
				snapshot();
				drag = { mode: 'move', start: p, orig: clone( target ) };
			}
		}
		if ( drag ) {
			overlay.setPointerCapture( e.pointerId );
		}
	} );

	overlay.addEventListener( 'pointermove', ( e ) => {
		const p = pointer( e );
		if ( ! drag ) {
			const h = hitHandle( p );
			overlay.style.cursor = h ? ( h.rot ? 'grab' : 'nwse-resize' ) : hit( p ) ? 'move' : 'default';
			return;
		}
		const el = find( sel );
		if ( ! el ) {
			return;
		}
		const o = drag.orig;
		if ( drag.mode === 'move' ) {
			el.x = round( o.x + ( ( p.x - drag.start.x ) / W ) * 100 );
			el.y = round( o.y + ( ( p.y - drag.start.y ) / H ) * 100 );
			if ( ! e.altKey ) {
				snapMove( el );
			}
		} else if ( drag.mode === 'resize' ) {
			const b = drag.b;
			const d = { x: p.x - drag.start.x, y: p.y - drag.start.y };
			const l = { x: d.x * Math.cos( -b.a ) - d.y * Math.sin( -b.a ), y: d.x * Math.sin( -b.a ) + d.y * Math.cos( -b.a ) };
			let nw = Math.max( 4, b.w + drag.h.sx * l.x );
			let nh = Math.max( 4, b.h + drag.h.sy * l.y );
			const keep = e.shiftKey || ( el.t === 'img' && /photo/.test( el.src || '' ) && drag.h.sx && drag.h.sy ) || el.t === 'qr';
			if ( keep && drag.h.sx && drag.h.sy ) {
				const k = Math.max( nw / b.w, nh / b.h );
				nw = b.w * k;
				nh = b.h * k;
			}
			const shift = toWorld( { ...b, cx: 0, cy: 0 }, ( drag.h.sx * ( nw - b.w ) ) / 2, ( drag.h.sy * ( nh - b.h ) ) / 2 );
			const cx = b.cx + shift.x;
			const cy = b.cy + shift.y;
			el.w = round( ( nw / W ) * 100 );
			el.h = round( ( nh / H ) * 100 );
			el.x = round( ( ( cx - nw / 2 ) / W ) * 100 );
			el.y = round( ( ( cy - nh / 2 ) / H ) * 100 );
		} else if ( drag.mode === 'rotate' ) {
			const b = drag.b;
			let deg = ( Math.atan2( p.y - b.cy, p.x - b.cx ) * 180 ) / Math.PI + 90;
			if ( ! e.shiftKey ) {
				const snapTo = Math.round( deg / 15 ) * 15;
				if ( Math.abs( deg - snapTo ) < 4 ) {
					deg = snapTo;
				}
			}
			el.rot = round( ( ( deg % 360 ) + 360 ) % 360 );
			if ( el.rot === 0 || el.rot === 360 ) {
				delete el.rot;
			}
		}
		redraw();
		syncGeometryInputs();
	} );

	const endDrag = () => {
		if ( drag ) {
			const el = find( sel );
			const changed = el && JSON.stringify( el ) !== JSON.stringify( drag.orig );
			if ( changed ) {
				commit();
			} else {
				undo.pop();
			}
		}
		drag = null;
		guides = [];
		redraw();
	};
	overlay.addEventListener( 'pointerup', endDrag );
	overlay.addEventListener( 'pointercancel', endDrag );

	overlay.addEventListener( 'dblclick', () => {
		const el = sel && find( sel );
		if ( el && el.t === 'text' ) {
			const ta = props.querySelector( '[data-p="text"]' );
			if ( ta ) {
				ta.focus();
				ta.select();
			}
		}
	} );

	/* ---------- Keyboard ---------- */

	function onKey( e ) {
		if ( destroyed || ! root.contains( document.activeElement ) && document.activeElement !== document.body ) {
			return;
		}
		const typing = /INPUT|TEXTAREA|SELECT/.test( document.activeElement.tagName );
		const mod = e.ctrlKey || e.metaKey;
		if ( mod && e.key.toLowerCase() === 'z' && ! typing ) {
			e.preventDefault();
			e.shiftKey ? doRedo() : doUndo();
			return;
		}
		if ( mod && e.key.toLowerCase() === 'y' && ! typing ) {
			e.preventDefault();
			doRedo();
			return;
		}
		const el = sel && find( sel );
		if ( ! el || typing ) {
			return;
		}
		if ( mod && e.key.toLowerCase() === 'd' ) {
			e.preventDefault();
			duplicate();
			return;
		}
		if ( e.key === 'Delete' || e.key === 'Backspace' ) {
			e.preventDefault();
			remove();
			return;
		}
		const step = e.shiftKey ? 2 : 0.5;
		const moves = { ArrowLeft: [ -step, 0 ], ArrowRight: [ step, 0 ], ArrowUp: [ 0, -step ], ArrowDown: [ 0, step ] };
		if ( moves[ e.key ] && ! el.lock ) {
			e.preventDefault();
			change( () => {
				el.x = round( el.x + moves[ e.key ][ 0 ] );
				el.y = round( el.y + moves[ e.key ][ 1 ] );
			} );
			syncGeometryInputs();
		}
		if ( e.key === 'Escape' ) {
			select( null );
		}
	}
	document.addEventListener( 'keydown', onKey );

	/* ---------- Actions ---------- */

	function select( id ) {
		sel = id;
		renderProps();
		redraw();
	}

	function doUndo() {
		if ( ! undo.length ) {
			return;
		}
		redo.push( JSON.stringify( doc ) );
		doc = JSON.parse( undo.pop() );
		if ( sel && ! find( sel ) ) {
			sel = null;
		}
		commit();
		full();
		renderProps();
	}

	function doRedo() {
		if ( ! redo.length ) {
			return;
		}
		undo.push( JSON.stringify( doc ) );
		doc = JSON.parse( redo.pop() );
		commit();
		full();
		renderProps();
	}

	function duplicate() {
		const el = find( sel );
		if ( ! el ) {
			return;
		}
		const copy = clone( el );
		copy.id = uid();
		copy.x = round( el.x + 2 );
		copy.y = round( el.y + 2 );
		delete copy.lock;
		change( () => els().splice( els().indexOf( el ) + 1, 0, copy ) );
		select( copy.id );
	}

	function remove() {
		const el = find( sel );
		if ( ! el ) {
			return;
		}
		change( () => els().splice( els().indexOf( el ), 1 ) );
		select( null );
	}

	function layer( dir ) {
		const list = els();
		const el = find( sel );
		const i = list.indexOf( el );
		const j = dir === 'top' ? list.length - 1 : dir === 'bottom' ? 0 : i + ( dir === 'up' ? 1 : -1 );
		if ( j < 0 || j >= list.length || j === i ) {
			return;
		}
		change( () => {
			list.splice( i, 1 );
			list.splice( j, 0, el );
		} );
	}

	function add( el ) {
		el.id = uid();
		change( () => els().push( el ) );
		full().then( () => select( el.id ) );
	}

	function addPreset( kind ) {
		const ar = W / H;
		const sq = ( w ) => w * ar;
		const presets = {
			text: { t: 'text', x: 20, y: 45, w: 60, h: 6, text: __( 'Your text', 'pikacart' ), size: 4.5, wt: 600, al: 'center', c: '@t', font: 'Poppins' },
			rect: { t: 'rect', x: 30, y: 40, w: 40, h: 12, f: '@p', r: 1.5 },
			ellipse: { t: 'ellipse', x: 35, y: 40, w: 30, h: sq( 30 ), f: '@a', circ: true },
			line: { t: 'path', x: 15, y: 50, w: 70, h: 1, d: 'M0 50 L100 50', f: '', s: '@p', sw: 0.5 },
			photo: { t: 'img', x: 32, y: 25, w: 36, h: ( 36 * ar ) / 0.8, src: '{{photo}}', shape: 'round', r: 3, field: 'photo', bc: '@p', bw: 0.8 },
			logo: { t: 'img', x: 40, y: 8, w: 20, h: sq( 20 ), src: '{{logo}}', fit: 'contain' },
			sign: { t: 'img', x: 55, y: 82, w: 36, h: 7, src: '{{sign}}', fit: 'contain' },
			seal: { t: 'img', x: 10, y: 78, w: 22, h: sq( 22 ), src: '{{seal}}', fit: 'contain' },
			qr: { t: 'qr', x: 38, y: 70, w: 24, h: sq( 24 ), v: '{{verify_url}}' },
			bar: { t: 'bar', x: 20, y: 85, w: 60, h: 7, v: '{{id_no}}' },
			stack: { t: 'stack', x: 10, y: 60, w: 80, h: 22, rows: detailRows( cfg.kind || 'student', true ), mode: 'table', size: 3.4, font: 'Inter' },
		};
		add( clone( presets[ kind ] ) );
	}

	/* ---------- Properties panel ---------- */

	function colorPicker( key, value, label ) {
		const map = tokens( cfg.options( side ).palette || {} );
		const isHex = typeof value === 'string' && value[ 0 ] === '#';
		return `<div class="ed-prop"><span class="ed-prop-label">${ esc( label ) }</span>
			<div class="ed-colors">
				<button type="button" class="ed-col ed-col-none ${ ! value ? 'is-on' : '' }" data-col="${ key }" data-v="" title="${ esc( __( 'None', 'pikacart' ) ) }"></button>
				${ COLOR_TOKENS.map( ( t ) => `<button type="button" class="ed-col ${ value === t ? 'is-on' : '' }" data-col="${ key }" data-v="${ t }" style="background:${ map[ t.slice( 1 ) ] }" title="${ esc( t ) }"></button>` ).join( '' ) }
				<input type="color" data-colcustom="${ key }" value="${ isHex ? esc( value ) : '#000000' }" aria-label="${ esc( __( 'Custom colour', 'pikacart' ) ) }">
			</div></div>`;
	}

	function num( key, label, value, step = 0.5, min = -50, max = 200 ) {
		return `<label class="ed-prop ed-num"><span class="ed-prop-label">${ esc( label ) }</span><input type="number" data-p="${ key }" value="${ esc( value ?? '' ) }" step="${ step }" min="${ min }" max="${ max }"></label>`;
	}

	function syncGeometryInputs() {
		const el = sel && find( sel );
		if ( ! el ) {
			return;
		}
		[ 'x', 'y', 'w', 'h', 'rot' ].forEach( ( k ) => {
			const i = props.querySelector( `[data-p="${ k }"]` );
			if ( i && document.activeElement !== i ) {
				i.value = el[ k ] ?? 0;
			}
		} );
	}

	function renderProps() {
		const el = sel && find( sel );
		if ( ! el ) {
			const bg = doc[ side ].bg;
			const bgIsImg = bg && typeof bg === 'object' && bg.img;
			props.innerHTML = `<h3>${ esc( side === 'front' ? __( 'Front side', 'pikacart' ) : __( 'Back side', 'pikacart' ) ) }</h3>
				<p class="muted small">${ esc( __( 'Click any element on the card to change it.', 'pikacart' ) ) }</p>
				${ colorPicker( 'bg', bgIsImg ? '' : ( typeof bg === 'string' ? bg : '' ), __( 'Background colour', 'pikacart' ) ) }
				<label class="btn btn-sm ed-bgimg">${ icon( 'image' ) }${ esc( __( 'Background image', 'pikacart' ) ) }<input type="file" accept="image/png,image/jpeg,image/webp" data-bgimg hidden></label>
				${ bgIsImg ? `<button type="button" class="btn btn-sm btn-ghost" data-bgclear>${ esc( __( 'Remove background image', 'pikacart' ) ) }</button>` : '' }
				<h4 class="ed-sub">${ esc( __( 'Layers', 'pikacart' ) ) }</h4>
				<ol class="ed-layers">${ [ ...els() ].reverse().map( ( e ) => `<li><button type="button" data-pick="${ e.id }">${ esc( layerName( e ) ) }${ e.lock ? ' 🔒' : '' }${ e.hide ? ' (' + esc( __( 'hidden', 'pikacart' ) ) + ')' : '' }</button></li>` ).join( '' ) }</ol>`;
			bindProps();
			return;
		}
		let extra = '';
		if ( el.t === 'text' ) {
			extra = `<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Text', 'pikacart' ) ) }</span><textarea data-p="text" rows="2">${ esc( el.text ) }</textarea></label>
				<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Insert field', 'pikacart' ) ) }</span><select data-insert><option value="">—</option>${ FIELD_TOKENS().map( ( [ t, l ] ) => `<option value="${ esc( t ) }">${ esc( l ) }</option>` ).join( '' ) }</select></label>
				<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Font', 'pikacart' ) ) }</span><select data-p="font">${ FONTS.map( ( f ) => `<option value="${ esc( f.name ) }" ${ ( el.font || 'Inter' ) === f.name ? 'selected' : '' } style="font-family:'${ esc( f.name ) }'">${ esc( f.label ) }</option>` ).join( '' ) }</select></label>
				<div class="ed-row">${ num( 'size', __( 'Size', 'pikacart' ), el.size, 0.1, 1, 30 ) }${ num( 'lines', __( 'Max lines', 'pikacart' ), el.lines || 1, 1, 1, 12 ) }</div>
				<div class="ed-toggles">
					<button type="button" class="ed-tg ${ ( el.wt || 400 ) >= 600 ? 'is-on' : '' }" data-tg="wt"><b>B</b></button>
					<button type="button" class="ed-tg ${ el.it ? 'is-on' : '' }" data-tg="it"><i>I</i></button>
					<button type="button" class="ed-tg ${ el.up ? 'is-on' : '' }" data-tg="up">AA</button>
					<button type="button" class="ed-tg ${ ( el.al || 'left' ) === 'left' ? 'is-on' : '' }" data-al="left">⟸</button>
					<button type="button" class="ed-tg ${ el.al === 'center' ? 'is-on' : '' }" data-al="center">≡</button>
					<button type="button" class="ed-tg ${ el.al === 'right' ? 'is-on' : '' }" data-al="right">⟹</button>
				</div>
				${ num( 'ls', __( 'Letter spacing', 'pikacart' ), el.ls || 0, 0.01, 0, 1 ) }
				${ colorPicker( 'c', el.c || '@t', __( 'Colour', 'pikacart' ) ) }`;
		} else if ( el.t === 'rect' || el.t === 'ellipse' || el.t === 'path' ) {
			extra = `${ colorPicker( 'f', typeof el.f === 'string' ? el.f : '', __( 'Fill', 'pikacart' ) ) }
				${ colorPicker( 's', el.s || '', __( 'Border', 'pikacart' ) ) }
				<div class="ed-row">${ num( 'sw', __( 'Border width', 'pikacart' ), el.sw || 0, 0.1, 0, 10 ) }${ el.t === 'rect' ? num( 'r', __( 'Corner radius', 'pikacart' ), Array.isArray( el.r ) ? el.r[ 0 ] : el.r || 0, 0.5, 0, 50 ) : '' }</div>`;
		} else if ( el.t === 'img' ) {
			const isPerson = /\{\{\s*photo/.test( el.src || '' );
			extra = `${ isPerson || ! /\{\{/.test( el.src || '' ) ? `<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Frame shape', 'pikacart' ) ) }</span><select data-p="shape">${ [ [ 'rect', __( 'Square corners', 'pikacart' ) ], [ 'round', __( 'Rounded', 'pikacart' ) ], [ 'circle', __( 'Circle', 'pikacart' ) ], [ 'squircle', __( 'Soft square', 'pikacart' ) ], [ 'arch', __( 'Arch', 'pikacart' ) ], [ 'hex', __( 'Hexagon', 'pikacart' ) ], [ 'cut', __( 'Cut corners', 'pikacart' ) ], [ 'shield', __( 'Shield', 'pikacart' ) ] ].map( ( [ v, l ] ) => `<option value="${ v }" ${ ( el.shape || 'rect' ) === v ? 'selected' : '' }>${ esc( l ) }</option>` ).join( '' ) }</select></label>` : '' }
				${ colorPicker( 'bc', el.bc || '', __( 'Frame colour', 'pikacart' ) ) }
				${ num( 'bw', __( 'Frame width', 'pikacart' ), el.bw || 0, 0.1, 0, 6 ) }
				${ ! /\{\{/.test( el.src || '' ) ? `<label class="btn btn-sm">${ icon( 'upload' ) }${ esc( __( 'Replace image', 'pikacart' ) ) }<input type="file" accept="image/png,image/jpeg,image/webp" data-replace hidden></label>` : `<p class="muted small">${ esc( isPerson ? __( 'Each person\'s photo appears here.', 'pikacart' ) : __( 'Uses the image from your Organisation profile.', 'pikacart' ) ) }</p>` }`;
		} else if ( el.t === 'qr' || el.t === 'bar' ) {
			extra = `${ colorPicker( 'c', el.c || '@k', __( 'Colour', 'pikacart' ) ) }<p class="muted small">${ esc( el.t === 'qr' ? __( 'Opens this person\'s verification page.', 'pikacart' ) : __( 'Code 128 barcode of the ID number.', 'pikacart' ) ) }</p>`;
		} else if ( el.t === 'stack' ) {
			extra = `<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Style', 'pikacart' ) ) }</span><select data-p="mode">${ [ [ 'table', __( 'Label : Value', 'pikacart' ) ], [ 'inline', __( 'Label: Value (one line)', 'pikacart' ) ], [ 'stacked', __( 'Label above value', 'pikacart' ) ], [ 'value', __( 'Values only', 'pikacart' ) ] ].map( ( [ v, l ] ) => `<option value="${ v }" ${ ( el.mode || 'table' ) === v ? 'selected' : '' }>${ esc( l ) }</option>` ).join( '' ) }</select></label>
				<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Font', 'pikacart' ) ) }</span><select data-p="font">${ FONTS.map( ( f ) => `<option value="${ esc( f.name ) }" ${ ( el.font || 'Inter' ) === f.name ? 'selected' : '' }>${ esc( f.label ) }</option>` ).join( '' ) }</select></label>
				${ num( 'size', __( 'Text size', 'pikacart' ), el.size || 3.4, 0.1, 1, 12 ) }
				${ colorPicker( 'lc', el.lc || '@m', __( 'Label colour', 'pikacart' ) ) }
				${ colorPicker( 'vc', el.vc || '@t', __( 'Value colour', 'pikacart' ) ) }
				<p class="muted small">${ esc( __( 'Rows follow the fields you switch on in Details. Empty rows disappear.', 'pikacart' ) ) }</p>`;
		} else if ( el.t === 'pattern' ) {
			extra = `<label class="ed-prop"><span class="ed-prop-label">${ esc( __( 'Pattern', 'pikacart' ) ) }</span><select data-p="kind">${ [ 'dots', 'lines', 'grid', 'rings', 'chevron', 'tri' ].map( ( v ) => `<option ${ el.kind === v ? 'selected' : '' }>${ v }</option>` ).join( '' ) }</select></label>${ colorPicker( 'c', el.c || '@w/25', __( 'Colour', 'pikacart' ) ) }`;
		}
		props.innerHTML = `<div class="ed-props-head"><h3>${ esc( layerName( el ) ) }</h3><button type="button" class="icon-btn" data-deselect aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button></div>
			${ extra }
			<h4 class="ed-sub">${ esc( __( 'Position (%)', 'pikacart' ) ) }</h4>
			<div class="ed-grid4">${ num( 'x', 'X', el.x ) }${ num( 'y', 'Y', el.y ) }${ num( 'w', __( 'W', 'pikacart' ), el.w, 0.5, 0.5 ) }${ num( 'h', __( 'H', 'pikacart' ), el.h, 0.5, 0.5 ) }</div>
			<div class="ed-row">${ num( 'rot', __( 'Rotate°', 'pikacart' ), el.rot || 0, 1, 0, 360 ) }${ num( 'op', __( 'Opacity', 'pikacart' ), el.op ?? 1, 0.05, 0, 1 ) }</div>
			<div class="ed-btns">
				<button type="button" class="btn btn-sm" data-act="up" title="${ esc( __( 'Bring forward', 'pikacart' ) ) }">${ esc( __( 'Forward', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-act="down" title="${ esc( __( 'Send backward', 'pikacart' ) ) }">${ esc( __( 'Backward', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-act="top">${ esc( __( 'To front', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-act="bottom">${ esc( __( 'To back', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-act="center">${ esc( __( 'Centre', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-act="lock">${ el.lock ? esc( __( 'Unlock', 'pikacart' ) ) : esc( __( 'Lock', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-act="dup">${ esc( __( 'Duplicate', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm btn-danger" data-act="del">${ esc( __( 'Delete', 'pikacart' ) ) }</button>
			</div>`;
		bindProps();
	}

	function layerName( e ) {
		const names = {
			text: __( 'Text', 'pikacart' ),
			rect: __( 'Rectangle', 'pikacart' ),
			ellipse: __( 'Circle', 'pikacart' ),
			path: __( 'Shape', 'pikacart' ),
			img: __( 'Image', 'pikacart' ),
			qr: __( 'QR code', 'pikacart' ),
			bar: __( 'Barcode', 'pikacart' ),
			stack: __( 'Details block', 'pikacart' ),
			pattern: __( 'Pattern', 'pikacart' ),
			table: __( 'Renewal box', 'pikacart' ),
		};
		if ( e.t === 'text' ) {
			return `${ names.text }: ${ String( e.text || '' ).slice( 0, 22 ) }`;
		}
		if ( e.t === 'img' ) {
			const m = /\{\{\s*(\w+)/.exec( e.src || '' );
			const map = { photo: __( 'Photo', 'pikacart' ), logo: __( 'Logo', 'pikacart' ), sign: __( 'Signature', 'pikacart' ), seal: __( 'Seal', 'pikacart' ) };
			return m ? map[ m[ 1 ] ] || names.img : names.img;
		}
		return names[ e.t ] || e.t;
	}

	function bindProps() {
		const el = sel && find( sel );
		props.querySelectorAll( '[data-pick]' ).forEach( ( b ) => b.addEventListener( 'click', () => select( b.dataset.pick ) ) );
		props.querySelector( '[data-deselect]' )?.addEventListener( 'click', () => select( null ) );

		props.querySelectorAll( '[data-col]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			const k = b.dataset.col;
			const v = b.dataset.v;
			if ( k === 'bg' ) {
				change( () => {
					doc[ side ].bg = v || '@w';
				} );
			} else {
				change( () => {
					if ( v ) {
						el[ k ] = v;
					} else {
						delete el[ k ];
					}
				} );
			}
			renderProps();
		} ) );
		props.querySelectorAll( '[data-colcustom]' ).forEach( ( i ) => {
			i.addEventListener( 'focus', snapshot );
			i.addEventListener( 'input', () => {
				const k = i.dataset.colcustom;
				if ( k === 'bg' ) {
					doc[ side ].bg = i.value;
				} else {
					el[ k ] = i.value;
				}
				commit();
				redraw();
			} );
		} );

		props.querySelectorAll( '[data-p]' ).forEach( ( i ) => {
			i.addEventListener( 'focus', snapshot );
			i.addEventListener( 'input', () => {
				const k = i.dataset.p;
				let v = i.value;
				if ( i.type === 'number' ) {
					v = v === '' ? 0 : Number( v );
				}
				if ( k === 'r' ) {
					el.r = v;
				} else if ( k === 'rot' && ! v ) {
					delete el.rot;
				} else {
					el[ k ] = v;
				}
				commit();
				if ( k === 'font' ) {
					full();
				} else {
					redraw();
				}
			} );
		} );
		props.querySelector( '[data-insert]' )?.addEventListener( 'change', ( e ) => {
			if ( ! e.target.value ) {
				return;
			}
			change( () => {
				el.text = ( el.text ? el.text + ' ' : '' ) + e.target.value;
			} );
			renderProps();
		} );
		props.querySelectorAll( '[data-tg]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			const k = b.dataset.tg;
			change( () => {
				if ( k === 'wt' ) {
					el.wt = ( el.wt || 400 ) >= 600 ? 400 : 700;
				} else {
					el[ k ] = ! el[ k ];
				}
			} );
			k === 'wt' ? full() : null;
			renderProps();
		} ) );
		props.querySelectorAll( '[data-al]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			change( () => {
				el.al = b.dataset.al;
			} );
			renderProps();
		} ) );
		props.querySelectorAll( '[data-act]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			const a = b.dataset.act;
			if ( a === 'del' ) {
				remove();
			} else if ( a === 'dup' ) {
				duplicate();
			} else if ( a === 'lock' ) {
				change( () => {
					el.lock = ! el.lock;
				} );
				renderProps();
			} else if ( a === 'center' ) {
				change( () => {
					el.x = round( 50 - el.w / 2 );
				} );
				syncGeometryInputs();
			} else {
				layer( a );
			}
		} ) );
		const replace = props.querySelector( '[data-replace]' );
		replace?.addEventListener( 'change', () => uploadThen( replace.files[ 0 ], ( url ) => change( () => {
			el.src = url;
		} ) ) );
		const bgimg = props.querySelector( '[data-bgimg]' );
		bgimg?.addEventListener( 'change', () => uploadThen( bgimg.files[ 0 ], ( url ) => change( () => {
			doc[ side ].bg = { img: url, fit: 'cover' };
		} ) ) );
		props.querySelector( '[data-bgclear]' )?.addEventListener( 'click', () => {
			change( () => {
				doc[ side ].bg = '@w';
			} );
			renderProps();
		} );
	}

	async function uploadThen( file, fn ) {
		if ( ! file || ! cfg.upload ) {
			return;
		}
		try {
			const url = await cfg.upload( file );
			fn( url );
			await full();
			renderProps();
		} catch ( e ) {
			cfg.onError && cfg.onError( e );
		}
	}

	/* ---------- Toolbar ---------- */

	root.querySelectorAll( '[data-add]' ).forEach( ( b ) => b.addEventListener( 'click', () => addPreset( b.dataset.add ) ) );
	const fieldMenu = $( '[data-field-menu]' );
	$( '[data-add-field]' ).addEventListener( 'click', ( e ) => {
		e.stopPropagation();
		fieldMenu.hidden = ! fieldMenu.hidden;
	} );
	fieldMenu.querySelectorAll( '[data-token]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		fieldMenu.hidden = true;
		add( { t: 'text', x: 15, y: 50, w: 70, h: 5, text: b.dataset.token, size: 4, wt: 600, al: 'center', c: '@t', font: 'Inter' } );
	} ) );
	document.addEventListener( 'click', ( e ) => {
		if ( ! e.target.closest( '.ed-field-add' ) ) {
			fieldMenu.hidden = true;
		}
	} );
	$( '[data-add-image]' ).addEventListener( 'change', ( e ) => uploadThen( e.target.files[ 0 ], ( url ) => {
		const ar = W / H;
		add( { t: 'img', x: 30, y: 35, w: 40, h: 40 * ar, src: url, fit: 'contain' } );
	} ) );
	root.querySelectorAll( '[data-side]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		side = b.dataset.side;
		root.querySelectorAll( '[data-side]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		sel = null;
		full();
		renderProps();
	} ) );
	$( '[data-undo]' ).addEventListener( 'click', doUndo );
	$( '[data-redo]' ).addEventListener( 'click', doRedo );
	$( '[data-reset]' ).addEventListener( 'click', async () => {
		if ( ! cfg.confirmReset || ( await cfg.confirmReset() ) ) {
			snapshot();
			doc = clone( cfg.original() );
			sel = null;
			cfg.onChange( null );
			full();
			renderProps();
		}
	} );
	root.querySelectorAll( '[data-zoom]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		const z = b.dataset.zoom;
		zoom = z === 'fit' ? 1 : Math.max( 0.5, Math.min( 3, zoom + ( z === '+' ? 0.25 : -0.25 ) ) );
		full();
	} ) );
	$( '[data-person]' )?.addEventListener( 'change', async ( e ) => {
		await cfg.onPerson( e.target.value );
		full();
	} );

	const onResize = () => full();
	window.addEventListener( 'resize', onResize );

	full();
	renderProps();

	return {
		refresh: full,
		get doc() {
			return doc;
		},
		destroy() {
			destroyed = true;
			document.removeEventListener( 'keydown', onKey );
			window.removeEventListener( 'resize', onResize );
		},
	};
}
