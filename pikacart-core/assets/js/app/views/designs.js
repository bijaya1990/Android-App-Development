/**
 * My Designs: own uploaded designs and Design on Demand results, with request
 * status. "Use my own design" turns front/back artwork into a private
 * template; placeholders are placed in the editor (designs/{id}).
 */

import { api } from '../api.js';
import { esc, icon, toast, confirmDialog, withLoading, emptyState } from '../ui.js';
import { catalog, findSubtype, orgVars } from '../catalog.js';
import { thumb } from '../../card/gallery.js';
import { createEditor } from '../../card/editor-core.js';
import { layoutFor } from '../../card/families.js';
import { demoVars, kindOf } from '../../card/data.js';

const { __, _n, sprintf } = window.wp.i18n;

export default function designs( el, ctx, args ) {
	if ( args[ 0 ] && /^\d+$/.test( args[ 0 ] ) ) {
		return designer( el, ctx, Number( args[ 0 ] ) );
	}
	return list( el, ctx, args[ 0 ] === 'request' ? 'request' : args[ 0 ] === 'new' ? 'new' : '' );
}

/* ---------- Helpers ---------- */

function sizeOf( cat, sizeId, orient ) {
	const s = cat.sizes.find( ( x ) => x.id === sizeId ) || cat.sizes[ 0 ] || { w: 54, h: 86 };
	const short = Math.min( s.w, s.h );
	const long = Math.max( s.w, s.h );
	return orient === 'landscape' ? { w: long, h: short } : { w: short, h: long };
}

function metaOf( t ) {
	const l = t.layout || {};
	const m = l.meta || {};
	return { orientation: m.orientation || ( l.artwork && l.artwork.orientation ) || ( l.landscape && ! l.portrait ? 'landscape' : 'portrait' ), sizeId: m.size_id || 0 };
}

function subtypeOptions( cat, selected ) {
	return cat.tree.map( ( c ) => `<optgroup label="${ esc( c.name ) }">${ c.subtypes.map( ( s ) => `<option value="${ s.id }" ${ s.id === selected ? 'selected' : '' }>${ esc( s.name ) }</option>` ).join( '' ) }</optgroup>` ).join( '' );
}

function sizeOptions( cat, selected ) {
	return cat.sizes.map( ( s ) => `<option value="${ s.id }" ${ s.id === selected ? 'selected' : '' }>${ esc( s.name ) } (${ s.w } × ${ s.h } mm)</option>` ).join( '' );
}

function modal( html, cls = '' ) {
	const root = document.getElementById( 'modal-root' );
	const wrap = document.createElement( 'div' );
	wrap.className = 'modal-wrap';
	wrap.innerHTML = `<div class="modal ${ cls }" role="dialog" aria-modal="true">${ html }</div>`;
	root.appendChild( wrap );
	const close = () => {
		wrap.remove();
		document.removeEventListener( 'keydown', onKey );
	};
	const onKey = ( e ) => e.key === 'Escape' && close();
	document.addEventListener( 'keydown', onKey );
	wrap.addEventListener( 'click', ( e ) => {
		if ( e.target === wrap || e.target.closest( '[data-close]' ) ) {
			close();
		}
	} );
	return { wrap, close };
}

/** An image picker that uploads right away and keeps the URL. */
function artPicker( box, label, required ) {
	box.innerHTML = `<span class="art-label">${ esc( label ) }${ required ? ' *' : '' }</span>
		<label class="art-drop">
			<input type="file" accept="image/png,image/jpeg,image/webp" hidden>
			<span class="art-empty">${ icon( 'upload' ) }<span>${ esc( __( 'Choose image', 'pikacart' ) ) }</span><small>${ esc( __( 'JPG or PNG, 300 DPI recommended', 'pikacart' ) ) }</small></span>
			<img alt="" hidden>
		</label>
		<small class="art-msg muted"></small>`;
	const input = box.querySelector( 'input' );
	const img = box.querySelector( 'img' );
	const msg = box.querySelector( '.art-msg' );
	const state = { url: '', busy: false };
	input.addEventListener( 'change', async () => {
		const f = input.files[ 0 ];
		if ( ! f ) {
			return;
		}
		if ( f.size > window.PKC.uploadMb * 1024 * 1024 ) {
			msg.textContent = sprintf( __( 'This image is too large. The limit is %s MB.', 'pikacart' ), window.PKC.uploadMb );
			return;
		}
		state.busy = true;
		msg.textContent = __( 'Uploading…', 'pikacart' );
		const local = URL.createObjectURL( f );
		const probe = new Image();
		probe.onload = () => {
			box.dataset.ratio = probe.naturalWidth / probe.naturalHeight;
			if ( probe.naturalWidth < 600 && probe.naturalHeight < 600 ) {
				msg.textContent = __( 'This image is small and may print blurry. A 300 DPI image is best.', 'pikacart' );
			}
		};
		probe.src = local;
		try {
			const form = new FormData();
			form.append( 'file', f );
			const res = await api( 'uploads/image', { method: 'POST', form } );
			state.url = res.url;
			img.src = local;
			img.hidden = false;
			box.querySelector( '.art-empty' ).hidden = true;
			if ( msg.textContent === __( 'Uploading…', 'pikacart' ) ) {
				msg.textContent = __( 'Uploaded.', 'pikacart' );
			}
		} catch ( e ) {
			msg.textContent = e.message;
		} finally {
			state.busy = false;
		}
	} );
	return state;
}

/* ---------- List ---------- */

function list( el, ctx, open ) {
	let alive = true;
	let timer = 0;
	el.innerHTML = '<div class="skeleton-grid"><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div></div>';

	async function load() {
		try {
			const [ cat, data ] = await Promise.all( [ catalog(), api( 'designs' ) ] );
			if ( alive ) {
				draw( cat, data );
			}
		} catch ( e ) {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p></div>`;
		}
	}

	function requestRow( r ) {
		const left = r.due_ts - Math.floor( Date.now() / 1000 );
		let when = '';
		if ( r.status === 'new' || r.status === 'progress' ) {
			when = left > 0 ? sprintf( __( 'Expected within %1$dh %2$dm', 'pikacart' ), Math.floor( left / 3600 ), Math.floor( ( left % 3600 ) / 60 ) ) : __( 'Almost ready. Our team is finishing it.', 'pikacart' );
		}
		return `<li class="req-item">
			<div class="req-thumbs">${ r.front ? `<img src="${ esc( r.front ) }" alt="${ esc( __( 'Front', 'pikacart' ) ) }" loading="lazy">` : '' }${ r.back ? `<img src="${ esc( r.back ) }" alt="${ esc( __( 'Back', 'pikacart' ) ) }" loading="lazy">` : '' }</div>
			<div class="req-info">
				<strong>${ esc( sprintf( __( 'Request #%d', 'pikacart' ), r.id ) ) }${ r.subtype ? ' · ' + esc( r.subtype ) : '' }</strong>
				<span class="muted small">${ esc( sprintf( __( 'Sent %s', 'pikacart' ), r.created ) ) }</span>
				${ when ? `<span class="small req-when">${ esc( when ) }</span>` : '' }
				${ r.status === 'rejected' && r.reason ? `<span class="small req-reason">${ esc( r.reason ) }</span>` : '' }
			</div>
			<span class="badge req-${ esc( r.status ) }">${ esc( r.label ) }</span>
		</li>`;
	}

	function draw( cat, data ) {
		const own = data.templates;
		el.innerHTML = `
			<div class="design-ctas">
				<section class="card design-cta">
					<span class="design-cta-icon">${ icon( 'upload' ) }</span>
					<div><h3>${ esc( __( 'Use my own design', 'pikacart' ) ) }</h3><p class="muted">${ esc( __( 'Upload the front and back of your existing card. Then drag the photo, name, QR code and other fields onto the right spots. Ready in minutes.', 'pikacart' ) ) }</p></div>
					<button type="button" class="btn btn-primary" data-new>${ icon( 'plus' ) }${ esc( __( 'Upload my design', 'pikacart' ) ) }</button>
				</section>
				<section class="card design-cta design-cta-dod">
					<span class="design-cta-icon">${ icon( 'sparkles' ) }</span>
					<div><h3>${ esc( __( 'Design on Demand', 'pikacart' ) ) }</h3><p class="muted">${ esc( sprintf( _n( 'Send us your design and our team sets it up for you. It will be live in your account within %d hour.', 'Send us your design and our team sets it up for you. It will be live in your account within %d hours.', data.hours, 'pikacart' ), data.hours ) ) }</p></div>
					<button type="button" class="btn btn-accent" data-dod>${ icon( 'sparkles' ) }${ esc( __( 'Send my design', 'pikacart' ) ) }</button>
				</section>
			</div>
			<section class="card">
				<div class="card-head"><h3>${ icon( 'palette' ) } ${ esc( __( 'My designs', 'pikacart' ) ) }</h3><a class="btn btn-sm btn-ghost" href="${ esc( ctx.url( 'create' ) ) }" data-link>${ esc( __( 'Or choose a Pikacart design', 'pikacart' ) ) }</a></div>
				${ own.length ? `<div class="my-designs">${ own.map( ( t ) => `<article class="my-design" data-id="${ t.id }">
					<div class="my-design-thumb"><canvas aria-label="${ esc( t.name ) }"></canvas></div>
					<div class="my-design-body">
						<strong>${ esc( t.name ) }</strong>
						<span class="muted small">${ esc( ( findSubtype( cat, t.subtype_id ).subtype || {} ).name || '' ) }</span>
						<span class="badge ${ t.source === 'dod' ? 'badge-active' : '' }">${ esc( t.source === 'dod' ? __( 'Made by Pikacart team', 'pikacart' ) : __( 'Your upload', 'pikacart' ) ) }</span>
					</div>
					<div class="my-design-actions">
						<button type="button" class="btn btn-primary btn-sm" data-act="use">${ esc( __( 'Use this design', 'pikacart' ) ) }</button>
						<a class="btn btn-sm" href="${ esc( ctx.url( 'designs/' + t.id ) ) }" data-link>${ icon( 'edit' ) }${ esc( __( 'Place fields', 'pikacart' ) ) }</a>
						<button type="button" class="btn btn-sm btn-ghost" data-act="rename">${ esc( __( 'Rename', 'pikacart' ) ) }</button>
						<button type="button" class="btn btn-sm btn-ghost is-danger" data-act="delete">${ esc( __( 'Delete', 'pikacart' ) ) }</button>
					</div>
				</article>` ).join( '' ) }</div>` : emptyState( { art: 'cards', title: __( 'No designs of your own yet', 'pikacart' ), text: __( 'Upload your card design, or send it to our Design on Demand team. Your designs appear here, next to the Pikacart gallery.', 'pikacart' ) } ) }
			</section>
			${ data.requests.length ? `<section class="card"><div class="card-head"><h3>${ icon( 'sparkles' ) } ${ esc( __( 'Design on Demand requests', 'pikacart' ) ) }</h3></div><ul class="req-list">${ data.requests.map( requestRow ).join( '' ) }</ul></section>` : '' }`;

		own.forEach( ( t ) => {
			const m = metaOf( t );
			const st = findSubtype( cat, t.subtype_id ).subtype;
			const pal = cat.palettes.find( ( p ) => p.id === ( t.palettes || [] )[ 0 ] ) || cat.palettes[ 0 ];
			thumb( el.querySelector( `.my-design[data-id="${ t.id }"] canvas` ), { template: t, subtype: st, orientation: m.orientation, palette: pal, size: sizeOf( cat, m.sizeId, m.orientation ), width: 160, org: orgVars( ctx.me.org ) } );
		} );

		el.querySelector( '[data-new]' ).addEventListener( 'click', () => newDesign( cat ) );
		el.querySelector( '[data-dod]' ).addEventListener( 'click', () => dodForm( cat, data.hours ) );
		el.querySelectorAll( '.my-design [data-act]' ).forEach( ( b ) => b.addEventListener( 'click', () => act( cat, own.find( ( t ) => t.id === Number( b.closest( '[data-id]' ).dataset.id ) ), b ) ) );

		if ( open === 'request' ) {
			open = '';
			dodForm( cat, data.hours );
		} else if ( open === 'new' ) {
			open = '';
			newDesign( cat );
		}
		clearInterval( timer );
		if ( data.requests.some( ( r ) => r.status === 'new' || r.status === 'progress' ) ) {
			timer = setInterval( () => alive && load(), 60000 );
		}
	}

	async function act( cat, t, b ) {
		if ( b.dataset.act === 'use' ) {
			await withLoading( b, async () => {
				try {
					const res = await api( 'projects', { method: 'POST', body: { subtype_id: t.subtype_id, template_id: t.id, name: t.name } } );
					toast( __( 'Design selected. Now check the size and details.', 'pikacart' ) );
					ctx.navigate( `projects/${ res.project.id }/setup` );
				} catch ( e ) {
					toast( e.message, 'error' );
				}
			} );
		} else if ( b.dataset.act === 'rename' ) {
			const { wrap, close } = modal( `<h2>${ esc( __( 'Rename design', 'pikacart' ) ) }</h2>
				<div class="field"><label for="dn">${ esc( __( 'Name', 'pikacart' ) ) }</label><input id="dn" type="text" maxlength="120" value="${ esc( t.name ) }"></div>
				<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Cancel', 'pikacart' ) ) }</button><button type="button" class="btn btn-primary" data-ok>${ esc( __( 'Save', 'pikacart' ) ) }</button></div>` );
			wrap.querySelector( '#dn' ).focus();
			wrap.querySelector( '[data-ok]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
				try {
					await api( `designs/${ t.id }`, { method: 'POST', body: { name: wrap.querySelector( '#dn' ).value } } );
					close();
					toast( __( 'Design renamed.', 'pikacart' ) );
					load();
				} catch ( err ) {
					toast( err.message, 'error' );
				}
			} ) );
		} else if ( b.dataset.act === 'delete' ) {
			if ( await confirmDialog( { title: sprintf( __( 'Delete "%s"?', 'pikacart' ), t.name ), message: __( 'Projects that use this design must be deleted or changed first.', 'pikacart' ), confirm: __( 'Delete', 'pikacart' ), danger: true } ) ) {
				try {
					const res = await api( `designs/${ t.id }/delete`, { method: 'POST' } );
					toast( res.message );
					load();
				} catch ( e ) {
					toast( e.message, 'error' );
				}
			}
		}
	}

	function newDesign( cat ) {
		const first = ( cat.tree[ 0 ] && cat.tree[ 0 ].subtypes[ 0 ] ) || {};
		const { wrap, close } = modal( `<h2>${ esc( __( 'Use my own design', 'pikacart' ) ) }</h2>
			<p>${ esc( __( 'Upload the front and back of your card as images. They become the card background; you then place the photo, name and other fields on top.', 'pikacart' ) ) }</p>
			<form class="form-grid" novalidate>
				<div class="field"><label for="nd-name">${ esc( __( 'Design name', 'pikacart' ) ) }</label><input id="nd-name" name="name" type="text" maxlength="120" placeholder="${ esc( __( 'e.g. Our school card 2026', 'pikacart' ) ) }"></div>
				<div class="grid-2">
					<div class="field"><label for="nd-sub">${ esc( __( 'Card type', 'pikacart' ) ) }</label><select id="nd-sub" name="subtype_id">${ subtypeOptions( cat, first.id ) }</select></div>
					<div class="field"><label for="nd-size">${ esc( __( 'Card size', 'pikacart' ) ) }</label><select id="nd-size" name="size_id">${ sizeOptions( cat, ( cat.sizes[ 0 ] || {} ).id ) }</select></div>
				</div>
				<div class="field"><span class="label">${ esc( __( 'Orientation', 'pikacart' ) ) }</span><div class="seg" data-orient><button type="button" class="is-on" data-o="portrait">${ esc( __( 'Portrait', 'pikacart' ) ) }</button><button type="button" data-o="landscape">${ esc( __( 'Landscape', 'pikacart' ) ) }</button></div></div>
				<div class="grid-2 art-grid"><div class="art-box" data-front></div><div class="art-box" data-back></div></div>
			</form>
			<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Cancel', 'pikacart' ) ) }</button><button type="button" class="btn btn-primary" data-ok>${ esc( __( 'Continue to place fields', 'pikacart' ) ) }${ icon( 'chevron-right' ) }</button></div>`, 'modal-lg' );
		let orient = 'portrait';
		const front = artPicker( wrap.querySelector( '[data-front]' ), __( 'Front side', 'pikacart' ), true );
		const back = artPicker( wrap.querySelector( '[data-back]' ), __( 'Back side', 'pikacart' ), false );
		wrap.querySelectorAll( '[data-o]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			orient = b.dataset.o;
			wrap.querySelectorAll( '[data-o]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		} ) );
		// Pick the orientation from the uploaded image shape.
		wrap.querySelector( '[data-front] input' ).addEventListener( 'change', () => setTimeout( () => {
			const r = Number( wrap.querySelector( '[data-front]' ).dataset.ratio || 0 );
			if ( r ) {
				wrap.querySelector( `[data-o="${ r > 1 ? 'landscape' : 'portrait' }"]` ).click();
			}
		}, 400 ) );
		wrap.querySelector( '[data-ok]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
			if ( front.busy || back.busy ) {
				toast( __( 'Please wait until the images finish uploading.', 'pikacart' ), 'error' );
				return;
			}
			if ( ! front.url ) {
				toast( __( 'Please upload the front image.', 'pikacart' ), 'error' );
				return;
			}
			try {
				const res = await api( 'designs', { method: 'POST', body: {
					name: wrap.querySelector( '#nd-name' ).value,
					subtype_id: Number( wrap.querySelector( '#nd-sub' ).value ),
					size_id: Number( wrap.querySelector( '#nd-size' ).value ),
					orientation: orient,
					front: front.url,
					back: back.url,
				} } );
				close();
				toast( __( 'Design uploaded. Now drag each field to the right spot.', 'pikacart' ) );
				ctx.navigate( `designs/${ res.template.id }` );
			} catch ( err ) {
				toast( err.message, 'error' );
			}
		} ) );
	}

	function dodForm( cat, hours ) {
		const { wrap, close } = modal( `<h2>${ esc( __( 'Design on Demand', 'pikacart' ) ) }</h2>
			<p>${ esc( __( 'Upload your design. Front and back separately. Our team places the photo, name, QR code and other fields for you.', 'pikacart' ) ) }</p>
			<form class="form-grid" novalidate>
				<div class="grid-2 art-grid"><div class="art-box" data-front></div><div class="art-box" data-back></div></div>
				<div class="grid-2">
					<div class="field"><label for="dd-sub">${ esc( __( 'Card type', 'pikacart' ) ) }</label><select id="dd-sub">${ subtypeOptions( cat, ( ( cat.tree[ 0 ] || { subtypes: [] } ).subtypes[ 0 ] || {} ).id ) }</select></div>
					<div class="field"><label for="dd-size">${ esc( __( 'Card size', 'pikacart' ) ) }</label><select id="dd-size">${ sizeOptions( cat, ( cat.sizes[ 0 ] || {} ).id ) }</select></div>
				</div>
				<div class="field"><span class="label">${ esc( __( 'Orientation', 'pikacart' ) ) }</span><div class="seg"><button type="button" class="is-on" data-o="portrait">${ esc( __( 'Portrait', 'pikacart' ) ) }</button><button type="button" data-o="landscape">${ esc( __( 'Landscape', 'pikacart' ) ) }</button></div></div>
				<div class="field"><label for="dd-notes">${ esc( __( 'Notes for our team', 'pikacart' ) ) }</label><textarea id="dd-notes" rows="3" maxlength="2000" placeholder="${ esc( __( 'e.g. Put the photo on the left, show blood group and bus route.', 'pikacart' ) ) }"></textarea></div>
				<label class="check"><input type="checkbox" id="dd-public"> <span>${ esc( __( 'Pikacart may also offer this design to other customers (without our logo and details).', 'pikacart' ) ) }</span></label>
				<label class="check"><input type="checkbox" id="dd-confirm"> <span>${ esc( __( 'This is our own organisation card design. It does not copy Aadhaar, PAN, a driving licence or any other government ID.', 'pikacart' ) ) }</span></label>
			</form>
			<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Cancel', 'pikacart' ) ) }</button><button type="button" class="btn btn-accent" data-ok>${ esc( __( 'Send to Pikacart team', 'pikacart' ) ) }</button></div>`, 'modal-lg' );
		let orient = 'portrait';
		const front = artPicker( wrap.querySelector( '[data-front]' ), __( 'Front of your design', 'pikacart' ), true );
		const back = artPicker( wrap.querySelector( '[data-back]' ), __( 'Back of your design', 'pikacart' ), false );
		wrap.querySelectorAll( '[data-o]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			orient = b.dataset.o;
			wrap.querySelectorAll( '[data-o]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		} ) );
		wrap.querySelector( '[data-ok]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
			if ( front.busy || back.busy ) {
				toast( __( 'Please wait until the images finish uploading.', 'pikacart' ), 'error' );
				return;
			}
			if ( ! front.url ) {
				toast( __( 'Please upload the front of your design.', 'pikacart' ), 'error' );
				return;
			}
			if ( ! wrap.querySelector( '#dd-confirm' ).checked ) {
				toast( __( 'Please confirm that this is your own design and not a copy of a government ID.', 'pikacart' ), 'error' );
				return;
			}
			try {
				const res = await api( 'designs/request', { method: 'POST', body: {
					front: front.url,
					back: back.url,
					subtype_id: Number( wrap.querySelector( '#dd-sub' ).value ),
					size_id: Number( wrap.querySelector( '#dd-size' ).value ),
					orientation: orient,
					notes: wrap.querySelector( '#dd-notes' ).value,
					public_ok: wrap.querySelector( '#dd-public' ).checked,
					confirm: true,
				} } );
				close();
				const done = modal( `<div class="center"><span class="done-icon">${ icon( 'check-circle' ) }</span><h2>${ esc( __( 'Request sent', 'pikacart' ) ) }</h2><p>${ esc( res.message ) }</p><p class="muted small">${ esc( __( 'We will email you and show a notification when it is ready.', 'pikacart' ) ) }</p></div><div class="modal-actions"><button type="button" class="btn btn-primary" data-close>${ esc( __( 'OK', 'pikacart' ) ) }</button></div>` );
				done.wrap.querySelector( '[data-close]' ).focus();
				load();
			} catch ( err ) {
				toast( err.message, 'error' );
			}
		} ) );
		void hours;
	}

	load();
	return () => {
		alive = false;
		clearInterval( timer );
	};
}

/* ---------- Designer: place fields on own artwork ---------- */

function designer( el, ctx, id ) {
	let editor = null;
	let alive = true;
	let saveTimer = 0;
	let pending = null;
	let flush = null;

	el.innerHTML = '<div class="skeleton sk-wide"></div>';

	( async () => {
		let cat;
		let t;
		try {
			[ cat, t ] = await Promise.all( [ catalog(), api( `designs/${ id }` ).then( ( r ) => r.template ) ] );
		} catch ( e ) {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p><a class="btn" href="${ esc( ctx.url( 'designs' ) ) }" data-link>${ esc( __( 'Back to My Designs', 'pikacart' ) ) }</a></div>`;
			return;
		}
		if ( ! alive ) {
			return;
		}
		const m = metaOf( t );
		const subtype = findSubtype( cat, t.subtype_id ).subtype;
		const kind = kindOf( subtype );
		const size = sizeOf( cat, m.sizeId, m.orientation );
		const pal = cat.palettes.find( ( p ) => p.id === ( t.palettes || [] )[ 0 ] ) || cat.palettes[ 0 ];
		const layout = Object.assign( {}, t.layout );
		const key = m.orientation;

		el.innerHTML = `<div class="designer-head">
				<a class="btn btn-ghost btn-sm" href="${ esc( ctx.url( 'designs' ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( __( 'My Designs', 'pikacart' ) ) }</a>
				<h2>${ esc( t.name ) }</h2>
				<span class="save-state is-saved" data-save>${ icon( 'check-circle' ) }${ esc( __( 'Saved', 'pikacart' ) ) }</span>
				<button type="button" class="btn btn-primary" data-use>${ esc( __( 'Done, use this design', 'pikacart' ) ) }${ icon( 'chevron-right' ) }</button>
			</div>
			<p class="banner banner-info inline-banner">${ icon( 'info' ) }<span>${ esc( __( 'Drag each field to the right spot on your artwork and resize it. Fields show sample data here; your people’s real details appear on their cards. Remove anything you do not need.', 'pikacart' ) ) }</span></p>
			<div class="ed-mobile-note banner banner-info">${ icon( 'info' ) }<span>${ esc( __( 'Placing fields works best on a computer or tablet.', 'pikacart' ) ) }</span></div>
			<div data-ed></div>`;

		const saveState = ( text, cls ) => {
			const s = el.querySelector( '[data-save]' );
			if ( s ) {
				s.className = 'save-state ' + cls;
				s.innerHTML = `${ icon( cls === 'is-saved' ? 'check-circle' : cls === 'is-error' ? 'alert' : 'refresh' ) }${ esc( text ) }`;
			}
		};
		const save = async () => {
			clearTimeout( saveTimer );
			const body = { layout: { [ key ]: layout[ key ], artwork: layout.artwork, meta: layout.meta } };
			pending = api( `designs/${ id }`, { method: 'POST', body } )
				.then( () => saveState( __( 'Saved', 'pikacart' ), 'is-saved' ) )
				.catch( ( e ) => {
					saveState( __( 'Not saved', 'pikacart' ), 'is-error' );
					toast( e.message, 'error' );
				} );
			await pending;
			pending = null;
		};

		flush = save;
		if ( ! layout[ key ] ) {
			layout[ key ] = layoutFor( { layout }, key, size.w / size.h, kind );
			save();
		}

		editor = createEditor( el.querySelector( '[data-ed]' ), {
			doc: layout[ key ],
			original: () => layoutFor( { layout: { artwork: layout.artwork } }, key, size.w / size.h, kind ),
			kind,
			size: () => size,
			options: ( side ) => ( {
				sizeMm: size,
				palette: pal,
				vars: demoVars( kind, 0, Object.assign( { subtype_name: subtype ? subtype.name : '' }, orgVars( ctx.me.org ) ) ),
				fields: null,
				flags: { qr_front: true, qr_back: true, bar_front: true, bar_back: true, renewal: true },
				side,
				demo: true,
			} ),
			onChange: ( doc ) => {
				layout[ key ] = doc || layoutFor( { layout: { artwork: layout.artwork } }, key, size.w / size.h, kind );
				saveState( __( 'Saving…', 'pikacart' ), 'is-saving' );
				clearTimeout( saveTimer );
				saveTimer = setTimeout( save, 900 );
			},
			upload: async ( file ) => {
				if ( file.size > window.PKC.uploadMb * 1024 * 1024 ) {
					throw { message: __( 'This image is too large. Please choose a smaller file.', 'pikacart' ) };
				}
				const form = new FormData();
				form.append( 'file', file );
				return ( await api( 'uploads/image', { method: 'POST', form } ) ).url;
			},
			onError: ( e ) => toast( e.message, 'error' ),
			people: [],
			onPerson: () => {},
			confirmReset: () => confirmDialog( { title: __( 'Start again from your artwork?', 'pikacart' ), message: __( 'All fields go back to their starting positions.', 'pikacart' ), confirm: __( 'Reset', 'pikacart' ), danger: true } ),
		} );

		el.querySelector( '[data-use]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
			try {
				if ( saveTimer ) {
					await save();
				}
				if ( pending ) {
					await pending;
				}
				const res = await api( 'projects', { method: 'POST', body: { subtype_id: t.subtype_id, template_id: t.id, name: t.name } } );
				toast( __( 'Project created with your design.', 'pikacart' ) );
				ctx.navigate( `projects/${ res.project.id }/setup` );
			} catch ( err ) {
				toast( err.message, 'error' );
			}
		} ) );
	} )();

	return () => {
		alive = false;
		if ( saveTimer && flush ) {
			flush(); // Keep the last change when leaving the page.
		}
		if ( editor ) {
			editor.destroy();
		}
	};
}
