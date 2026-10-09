/**
 * Super Admin visual template builder. The same editor organisations use,
 * plus design settings: name, sub-type, style level, palettes, orientations,
 * status and featured. Opened from a Design on Demand request it shows the
 * customer's artwork and a "Publish to this organisation" button.
 */

import { api } from '../app/api.js';
import { esc, icon, toast, confirmDialog, withLoading } from '../app/ui.js';
import { createEditor } from '../card/editor-core.js';
import { layoutFor } from '../card/families.js';
import { demoVars, kindOf } from '../card/data.js';
import { orgVars } from '../app/catalog.js';

const { __, sprintf } = window.wp.i18n;
const cfg = window.PKC;
const cat = cfg.catalog;
const req = cfg.request;
const T = cfg.template;
const root = document.getElementById( 'builder' );

const clone = ( o ) => JSON.parse( JSON.stringify( o ) );
const blank = () => ( { front: { bg: '@w', els: [] }, back: { bg: '@w', els: [] } } );

const state = {
	name: T.name,
	subtype: T.subtype_id,
	level: T.level,
	status: T.status,
	featured: T.featured,
	palettes: T.palettes.length ? T.palettes.slice() : [ cat.palettes[ 0 ].id ],
	layout: clone( T.layout || {} ),
	preview: null, // palette id used on the canvas
	orient: 'portrait',
	sizeId: 0,
};
const meta = state.layout.meta || {};
state.orient = meta.orientation || ( ! state.layout.recipe && ! state.layout.portrait && state.layout.landscape ? 'landscape' : 'portrait' );
state.preview = state.palettes[ 0 ];

function subtype() {
	for ( const c of cat.tree ) {
		const s = c.subtypes.find( ( x ) => x.id === state.subtype );
		if ( s ) {
			return s;
		}
	}
	return null;
}

function sizesFor( orient ) {
	return cat.sizes.map( ( s ) => {
		const short = Math.min( s.w, s.h );
		const long = Math.max( s.w, s.h );
		return Object.assign( {}, s, orient === 'landscape' ? { w: long, h: short } : { w: short, h: long } );
	} );
}

function size() {
	const list = sizesFor( state.orient );
	return list.find( ( s ) => s.id === state.sizeId ) || list.find( ( s ) => s.id === meta.size_id ) || list[ 0 ] || ( state.orient === 'landscape' ? { w: 86, h: 54 } : { w: 54, h: 86 } );
}

/** Make sure the current orientation has an editable layout (expand recipes or artwork). */
function ensureOrientation() {
	const l = state.layout;
	if ( l[ state.orient ] ) {
		return true;
	}
	if ( l.recipe || l.artwork ) {
		const s = size();
		l[ state.orient ] = layoutFor( { layout: l }, state.orient, s.w / s.h, kindOf( subtype() ) );
		return true;
	}
	return false;
}

function savedLayout() {
	const out = {};
	[ 'portrait', 'landscape', 'artwork', 'meta' ].forEach( ( k ) => {
		if ( state.layout[ k ] ) {
			out[ k ] = state.layout[ k ];
		}
	} );
	if ( state.layout.recipe ) {
		if ( ! out.portrait && ! out.landscape ) {
			out.recipe = state.layout.recipe;
		} else {
			// Once a built-in recipe is edited it is stored as a full custom layout,
			// so the orientation that was not opened is expanded too.
			[ 'portrait', 'landscape' ].forEach( ( o ) => {
				if ( ! out[ o ] ) {
					const s = sizesFor( o )[ 0 ] || ( o === 'landscape' ? { w: 86, h: 54 } : { w: 54, h: 86 } );
					out[ o ] = layoutFor( { layout: { recipe: state.layout.recipe } }, o, s.w / s.h, kindOf( subtype() ) );
				}
			} );
		}
	}
	return out;
}

/* ---------- Saving ---------- */

let saveTimer = 0;
let saving = null;
let dirty = false;
function setSaveState( text, cls ) {
	const el = root.querySelector( '[data-save]' );
	if ( el ) {
		el.textContent = text;
		el.className = 'save-state ' + cls;
	}
}
function queueSave( body ) {
	dirty = true;
	setSaveState( __( 'Saving…', 'pikacart' ), 'is-saving' );
	clearTimeout( saveTimer );
	saveTimer = setTimeout( () => save( body ), 900 );
}
async function save( extra = {} ) {
	clearTimeout( saveTimer );
	if ( saving ) {
		await saving;
	}
	const body = Object.assign( { name: state.name, layout: savedLayout() }, extra );
	if ( ! T.own ) {
		Object.assign( body, { subtype_id: state.subtype, level: state.level, palettes: state.palettes } );
	}
	saving = api( `admin/templates/${ T.id }`, { method: 'POST', body } )
		.then( ( res ) => {
			dirty = false;
			setSaveState( __( 'All changes saved', 'pikacart' ), 'is-saved' );
			return res;
		} )
		.catch( ( e ) => {
			setSaveState( __( 'Not saved', 'pikacart' ), 'is-error' );
			toast( e.message, 'error' );
			throw e;
		} )
		.finally( () => {
			saving = null;
		} );
	return saving;
}
window.addEventListener( 'beforeunload', ( e ) => {
	if ( dirty ) {
		e.preventDefault();
		e.returnValue = '';
	}
} );

/* ---------- Layout ---------- */

function settingsHTML() {
	const sub = subtype();
	const orientTabs = [ 'portrait', 'landscape' ].map( ( o ) => `<button type="button" class="${ state.orient === o ? 'is-on' : '' }" data-orient="${ o }">${ esc( o === 'portrait' ? __( 'Portrait', 'pikacart' ) : __( 'Landscape', 'pikacart' ) ) }${ state.layout[ o ] || state.layout.recipe ? '' : ' <small>(' + esc( __( 'none', 'pikacart' ) ) + ')</small>' }</button>` ).join( '' );
	return `
		<div class="bd-field"><label for="bd-name">${ esc( __( 'Design name', 'pikacart' ) ) }</label><input id="bd-name" type="text" value="${ esc( state.name ) }" maxlength="120"></div>
		${ T.own ? `<p class="bd-note">${ esc( sprintf( __( 'Private design for %s.', 'pikacart' ), ( cfg.org && cfg.org.name ) || __( 'one organisation', 'pikacart' ) ) ) }</p>` : `
		<div class="bd-field"><label for="bd-sub">${ esc( __( 'Category and sub-type', 'pikacart' ) ) }</label><select id="bd-sub">${ cat.tree.map( ( c ) => `<optgroup label="${ esc( c.name ) }">${ c.subtypes.map( ( s ) => `<option value="${ s.id }" ${ s.id === state.subtype ? 'selected' : '' }>${ esc( s.name ) }</option>` ).join( '' ) }</optgroup>` ).join( '' ) }</select></div>
		<div class="bd-field"><label for="bd-level">${ esc( __( 'Style level', 'pikacart' ) ) }</label><select id="bd-level">${ Object.entries( cat.levels ).map( ( [ k, v ] ) => `<option value="${ k }" ${ k === state.level ? 'selected' : '' }>${ esc( v ) }</option>` ).join( '' ) }</select></div>
		<div class="bd-field"><span class="bd-label">${ esc( __( 'Colour palettes offered', 'pikacart' ) ) }</span>
			<div class="bd-pals">${ cat.palettes.filter( ( p ) => p.active || state.palettes.includes( p.id ) ).map( ( p ) => `<label class="bd-pal ${ state.palettes.includes( p.id ) ? 'is-on' : '' }" title="${ esc( p.name ) }"><input type="checkbox" value="${ p.id }" ${ state.palettes.includes( p.id ) ? 'checked' : '' }><span style="background:linear-gradient(135deg, ${ esc( p.p ) } 50%, ${ esc( p.a ) } 50%)"></span></label>` ).join( '' ) }</div>
			<small>${ esc( __( 'The first ticked palette is the default.', 'pikacart' ) ) }</small>
		</div>
		<label class="bd-check"><input type="checkbox" id="bd-feat" ${ state.featured ? 'checked' : '' }> ${ esc( __( 'Featured (shown first and on the homepage)', 'pikacart' ) ) }</label>` }
		<div class="bd-field"><span class="bd-label">${ esc( __( 'Orientation', 'pikacart' ) ) }</span><div class="seg">${ orientTabs }</div></div>
		<div class="bd-field"><label for="bd-size">${ esc( __( 'Preview size', 'pikacart' ) ) }</label><select id="bd-size">${ sizesFor( state.orient ).map( ( s ) => `<option value="${ s.id }" ${ s.id === size().id ? 'selected' : '' }>${ esc( s.name ) }</option>` ).join( '' ) }</select></div>
		${ sub ? '' : `<p class="bd-note bd-warn">${ esc( __( 'Choose a sub-type.', 'pikacart' ) ) }</p>` }
		${ req ? requestHTML() : '' }`;
}

function requestHTML() {
	return `<div class="bd-req">
		<h3>${ esc( sprintf( __( 'Request #%d', 'pikacart' ), req.id ) ) }</h3>
		<p><strong>${ esc( req.org_name ) }</strong>${ req.subtype ? ' · ' + esc( req.subtype ) : '' }</p>
		${ req.notes ? `<p class="bd-notes">${ esc( req.notes ) }</p>` : '' }
		<p class="bd-art">${ req.front ? `<a href="${ esc( req.front ) }" target="_blank" rel="noopener">${ esc( __( 'Front artwork', 'pikacart' ) ) } ↗</a>` : '' } ${ req.back ? `<a href="${ esc( req.back ) }" target="_blank" rel="noopener">${ esc( __( 'Back artwork', 'pikacart' ) ) } ↗</a>` : '' }</p>
		<p class="bd-due" data-due></p>
		${ req.status === 'live' ? `<p class="bd-note">${ esc( __( 'This request is live. Changes you save are seen by the customer.', 'pikacart' ) ) }</p>` : '' }
		<label class="bd-check"><input type="checkbox" id="bd-public" ${ req.public_ok ? '' : 'disabled' }> ${ esc( req.public_ok ? __( 'Also publish to everyone as a public design (the organisation agreed)', 'pikacart' ) : __( 'Public publishing not allowed by the organisation', 'pikacart' ) ) }</label>
		<button type="button" class="btn btn-primary btn-block" data-publish>${ icon( 'check' ) }${ esc( req.status === 'live' ? __( 'Publish again', 'pikacart' ) : __( 'Publish to this organisation', 'pikacart' ) ) }</button>
	</div>`;
}

root.innerHTML = `
	<header class="bd-top">
		<a class="btn btn-ghost" href="${ esc( cfg.back ) }">${ icon( 'chevron-left' ) }${ esc( req ? __( 'Design requests', 'pikacart' ) : __( 'Templates', 'pikacart' ) ) }</a>
		<strong class="bd-title" data-title>${ esc( state.name ) }</strong>
		<span class="save-state is-saved" data-save>${ esc( __( 'All changes saved', 'pikacart' ) ) }</span>
		<div class="bd-top-actions">
			<label class="bd-prev">${ esc( __( 'Preview colours', 'pikacart' ) ) } <select data-prevpal></select></label>
			${ T.own ? '' : `<button type="button" class="btn ${ state.status === 'published' ? '' : 'btn-primary' }" data-status>${ esc( state.status === 'published' ? __( 'Unpublish', 'pikacart' ) : __( 'Publish', 'pikacart' ) ) }</button>` }
		</div>
	</header>
	<div class="bd-body">
		<aside class="bd-side" data-settings></aside>
		<main class="bd-main" data-main></main>
	</div>`;

const settingsEl = root.querySelector( '[data-settings]' );
const mainEl = root.querySelector( '[data-main]' );
let editor = null;

function fillPreviewPalettes() {
	const sel = root.querySelector( '[data-prevpal]' );
	const ids = state.palettes.length ? state.palettes : [ cat.palettes[ 0 ].id ];
	if ( ! ids.includes( state.preview ) ) {
		state.preview = ids[ 0 ];
	}
	sel.innerHTML = ids.map( ( id ) => cat.palettes.find( ( p ) => p.id === id ) ).filter( Boolean ).map( ( p ) => `<option value="${ p.id }" ${ p.id === state.preview ? 'selected' : '' }>${ esc( p.name ) }</option>` ).join( '' );
}

function vars() {
	const sub = subtype();
	const kind = kindOf( sub );
	return demoVars( kind, 0, Object.assign( { subtype_name: sub ? sub.name : '' }, cfg.org ? orgVars( cfg.org ) : {} ) );
}

function mountEditor() {
	if ( editor ) {
		editor.destroy();
		editor = null;
	}
	if ( ! ensureOrientation() ) {
		const other = state.orient === 'landscape' ? 'portrait' : 'landscape';
		mainEl.innerHTML = `<div class="bd-empty">
			<h2>${ esc( state.orient === 'landscape' ? __( 'No landscape version yet', 'pikacart' ) : __( 'No portrait version yet', 'pikacart' ) ) }</h2>
			<p>${ esc( __( 'Designs can offer one or both orientations. Start this version from a blank card or copy the other one.', 'pikacart' ) ) }</p>
			<div class="btn-row"><button type="button" class="btn btn-primary" data-start="blank">${ esc( __( 'Start blank', 'pikacart' ) ) }</button>${ state.layout[ other ] ? `<button type="button" class="btn" data-start="copy">${ esc( __( 'Copy the other orientation', 'pikacart' ) ) }</button>` : '' }</div>
		</div>`;
		mainEl.querySelectorAll( '[data-start]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			state.layout[ state.orient ] = b.dataset.start === 'copy' ? clone( state.layout[ other ] ) : blank();
			queueSave();
			settingsEl.innerHTML = settingsHTML();
			bindSettings();
			mountEditor();
		} ) );
		return;
	}
	const key = state.orient;
	const sub = subtype();
	editor = createEditor( mainEl, {
		doc: state.layout[ key ],
		original: () => {
			const s = size();
			const src = clone( T.layout || {} );
			if ( src[ key ] ) {
				return src[ key ];
			}
			return src.recipe || src.artwork ? layoutFor( { layout: src }, key, s.w / s.h, kindOf( sub ) ) : blank();
		},
		kind: kindOf( sub ),
		size: () => size(),
		options: ( side ) => ( {
			sizeMm: size(),
			palette: cat.palettes.find( ( p ) => p.id === state.preview ) || cat.palettes[ 0 ],
			vars: vars(),
			fields: null,
			flags: { qr_front: true, qr_back: true, bar_front: true, bar_back: true, renewal: true },
			side,
			demo: true,
		} ),
		onChange: ( doc ) => {
			if ( doc ) {
				state.layout[ key ] = doc;
			} else {
				delete state.layout[ key ];
				ensureOrientation();
			}
			queueSave();
		},
		upload: async ( file ) => {
			if ( file.size > cfg.uploadMb * 1024 * 1024 ) {
				throw { message: __( 'This image is too large. Please choose a smaller file.', 'pikacart' ) };
			}
			const form = new FormData();
			form.append( 'file', file );
			const res = await api( 'admin/uploads/image', { method: 'POST', form } );
			return res.url;
		},
		onError: ( e ) => toast( e.message, 'error' ),
		people: [],
		onPerson: () => {},
		confirmReset: () => confirmDialog( { title: __( 'Reset this side to the last published version?', 'pikacart' ), message: __( 'Your unsaved and saved changes in this orientation will be replaced.', 'pikacart' ), confirm: __( 'Reset', 'pikacart' ), danger: true } ),
	} );
}

function bindSettings() {
	const $ = ( s ) => settingsEl.querySelector( s );
	$( '#bd-name' ).addEventListener( 'input', ( e ) => {
		state.name = e.target.value;
		root.querySelector( '[data-title]' ).textContent = state.name || '—';
		queueSave();
	} );
	$( '#bd-sub' )?.addEventListener( 'change', ( e ) => {
		state.subtype = Number( e.target.value );
		queueSave();
		mountEditor();
	} );
	$( '#bd-level' )?.addEventListener( 'change', ( e ) => {
		state.level = e.target.value;
		queueSave();
	} );
	$( '#bd-feat' )?.addEventListener( 'change', ( e ) => {
		state.featured = e.target.checked;
		save( { featured: state.featured } ).then( () => toast( state.featured ? __( 'Marked as featured.', 'pikacart' ) : __( 'Removed from featured.', 'pikacart' ) ) ).catch( () => {} );
	} );
	settingsEl.querySelectorAll( '.bd-pal input' ).forEach( ( i ) => i.addEventListener( 'change', () => {
		const id = Number( i.value );
		state.palettes = i.checked ? [ ...state.palettes.filter( ( x ) => x !== id ), id ] : state.palettes.filter( ( x ) => x !== id );
		if ( ! state.palettes.length ) {
			state.palettes = [ id ];
			i.checked = true;
			toast( __( 'Keep at least one palette.', 'pikacart' ), 'error' );
		}
		i.closest( '.bd-pal' ).classList.toggle( 'is-on', i.checked );
		fillPreviewPalettes();
		queueSave();
	} ) );
	settingsEl.querySelectorAll( '[data-orient]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		state.orient = b.dataset.orient;
		settingsEl.innerHTML = settingsHTML();
		bindSettings();
		mountEditor();
	} ) );
	$( '#bd-size' ).addEventListener( 'change', ( e ) => {
		state.sizeId = Number( e.target.value );
		mountEditor();
	} );
	const pub = settingsEl.querySelector( '[data-publish]' );
	if ( pub ) {
		pub.addEventListener( 'click', () => withLoading( pub, async () => {
			try {
				await save();
				const res = await api( `admin/requests/${ req.id }/publish`, { method: 'POST', body: { public: settingsEl.querySelector( '#bd-public' )?.checked || false } } );
				req.status = 'live';
				toast( res.message );
				settingsEl.innerHTML = settingsHTML();
				bindSettings();
			} catch ( e ) {
				toast( e.message, 'error' );
			}
		} ) );
		const due = settingsEl.querySelector( '[data-due]' );
		const tick = () => {
			if ( ! due.isConnected ) {
				return;
			}
			const left = req.due_ts - Math.floor( Date.now() / 1000 );
			if ( req.status === 'live' || req.status === 'rejected' ) {
				due.textContent = '';
				return;
			}
			const h = Math.floor( Math.abs( left ) / 3600 );
			const m = Math.floor( ( Math.abs( left ) % 3600 ) / 60 );
			due.textContent = left >= 0 ? sprintf( __( 'Due in %1$dh %2$dm', 'pikacart' ), h, m ) : sprintf( __( 'Late by %1$dh %2$dm', 'pikacart' ), h, m );
			due.classList.toggle( 'is-late', left < 0 );
			setTimeout( tick, 30000 );
		};
		tick();
	}
}

root.querySelector( '[data-prevpal]' ).addEventListener( 'change', ( e ) => {
	state.preview = Number( e.target.value );
	mountEditor();
} );
const statusBtn = root.querySelector( '[data-status]' );
statusBtn?.addEventListener( 'click', () => withLoading( statusBtn, async () => {
	try {
		const next = state.status === 'published' ? 'draft' : 'published';
		await save( { status: next } );
		state.status = next;
		statusBtn.textContent = next === 'published' ? __( 'Unpublish', 'pikacart' ) : __( 'Publish', 'pikacart' );
		statusBtn.classList.toggle( 'btn-primary', next !== 'published' );
		toast( next === 'published' ? __( 'Published. Customers can now use this design.', 'pikacart' ) : __( 'Unpublished. Customers no longer see this design.', 'pikacart' ) );
	} catch ( e ) {
		// Shown by save().
	}
} ) );

settingsEl.innerHTML = settingsHTML();
bindSettings();
fillPreviewPalettes();
mountEditor();
// A request opened for the first time: store the generated placeholders right away.
if ( req && ! T.layout.portrait && ! T.layout.landscape ) {
	queueSave();
}
