/**
 * A card project with a step bar:
 * Design → Size & colour → Details → Fine-tune (editor) → People → Download & print.
 * Every change autosaves; the user can move back and forth without losing anything.
 * Route: projects/{id}/{step}
 */

import { api } from '../api.js';
import { esc, icon, toast, confirmDialog } from '../ui.js';
import { catalog, orgVars } from '../catalog.js';
import { createAutosave, waitForSave } from '../autosave.js';
import { renderSide } from '../../card/render.js';
import { thumb } from '../../card/gallery.js';
import { demoVars, memberVars, kindOf } from '../../card/data.js';
import { cardSize, paletteOf, layoutOf, enabledFields, flagsOf, projectInfo, supportsOrientation } from '../../card/scene.js';
import editorStep from '../editor.js';
import peopleStep, { blankExcel } from '../people.js';
import downloadStep from '../download.js';

const { __, sprintf } = window.wp.i18n;

export const STEPS = [
	[ 'design', () => __( 'Design', 'pikacart' ) ],
	[ 'setup', () => __( 'Size & colour', 'pikacart' ) ],
	[ 'details', () => __( 'Details', 'pikacart' ) ],
	[ 'editor', () => __( 'Fine-tune', 'pikacart' ) ],
	[ 'people', () => __( 'People', 'pikacart' ) ],
	[ 'download', () => __( 'Download & print', 'pikacart' ) ],
];

/** Route of the step where the user left a project (project.step is 1-based). */
export function savedStep( p ) {
	const i = Math.max( 0, Math.min( STEPS.length - 1, ( Number( p.step ) || 2 ) - 1 ) );
	return `projects/${ p.id }/${ STEPS[ i ][ 0 ] }`;
}

export function stepLabel( p ) {
	const i = Math.max( 0, Math.min( STEPS.length - 1, ( Number( p.step ) || 2 ) - 1 ) );
	return STEPS[ i ][ 1 ]();
}

export default function project( el, ctx, params ) {
	const id = Number( params[ 0 ] );
	const step = STEPS.find( ( s ) => s[ 0 ] === params[ 1 ] ) ? params[ 1 ] : 'setup';
	let alive = true;
	let cleanupStep = null;
	let autosave = null;

	el.innerHTML = '<div class="skeleton-grid"><div class="skeleton sk-wide"></div></div>';

	( async () => {
		let cat;
		let data;
		let sample = null;
		try {
			// Changes from the step just left (font, size, colour…) are saved first.
			await waitForSave( id );
			[ cat, data ] = await Promise.all( [ catalog(), api( `projects/${ id }` ) ] );
			const first = await api( `projects/${ id }/members?per_page=1&sort=created` );
			sample = first.members[ 0 ] || null;
		} catch ( e ) {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p><a class="btn" href="${ esc( ctx.url( 'projects' ) ) }" data-link>${ esc( __( 'Back to projects', 'pikacart' ) ) }</a></div>`;
			return;
		}
		if ( ! alive ) {
			return;
		}

		const P = makeP( ctx, cat, data, sample, () => autosave );

		autosave = createAutosave( id, ( res ) => {
			if ( res && res.project ) {
				data.template = res.template;
			}
		} );

		// Offer to restore changes kept in the browser after a failed save.
		const leftover = autosave.restore();
		if ( leftover ) {
			Object.assign( data.project, leftover );
			autosave.set( leftover );
			toast( __( 'We restored changes that were not saved last time.', 'pikacart' ), 'info' );
		}

		el.innerHTML = `<div class="proj">
			<header class="proj-head">
				<a class="back-link" href="${ esc( ctx.url( 'projects' ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( __( 'My Projects', 'pikacart' ) ) }</a>
				<div class="proj-title">
					<input class="proj-name" value="${ esc( data.project.name ) }" maxlength="120" aria-label="${ esc( __( 'Project name', 'pikacart' ) ) }">
					<span class="save-state" data-save aria-live="polite"></span>
				</div>
				<div class="proj-sub">
					<p class="muted small">${ esc( ( data.category ? data.category.name + ' · ' : '' ) + ( data.subtype ? data.subtype.name : '' ) + ( data.template ? ' · ' + data.template.name : '' ) ) }</p>
					<div class="proj-tools">
						${ step !== 'design' ? `<a class="btn btn-sm" href="${ esc( ctx.url( `projects/${ id }/design` ) ) }" data-link>${ icon( 'palette' ) }${ esc( __( 'Change design', 'pikacart' ) ) }</a>` : '' }
						<button type="button" class="btn btn-sm" data-blank>${ icon( 'download' ) }${ esc( __( 'Blank Excel', 'pikacart' ) ) }</button>
					</div>
				</div>
			</header>
			<ol class="stepbar steps6" data-steps>
				${ STEPS.map( ( s, i ) => {
					const cur = STEPS.findIndex( ( x ) => x[ 0 ] === step );
					return `<li class="${ i < cur ? 'is-done' : i === cur ? 'is-current' : '' }"><a href="${ esc( ctx.url( `projects/${ id }/${ s[ 0 ] }` ) ) }" data-link><span class="step-dot">${ i < cur ? icon( 'check' ) : i + 1 }</span><span class="step-label">${ esc( s[ 1 ]() ) }</span></a></li>`;
				} ).join( '' ) }
			</ol>
			<div class="proj-body" data-body></div>
			<nav class="proj-nav" data-nav></nav>
		</div>`;

		const saveEl = el.querySelector( '[data-save]' );
		autosave.onState( ( s ) => {
			const map = {
				saved: [ 'check-circle', __( 'Saved', 'pikacart' ) ],
				dirty: [ 'edit', __( 'Unsaved changes', 'pikacart' ) ],
				saving: [ 'refresh', __( 'Saving…', 'pikacart' ) ],
				offline: [ 'alert', __( 'Offline: will retry', 'pikacart' ) ],
				error: [ 'alert', __( 'Not saved', 'pikacart' ) ],
			}[ s ];
			saveEl.className = `save-state is-${ s }`;
			saveEl.innerHTML = `${ icon( map[ 0 ] ) }${ esc( map[ 1 ] ) }`;
		} );

		el.querySelector( '[data-blank]' ).addEventListener( 'click', () => {
			blankExcel( P );
			toast( __( 'Blank Excel downloaded with the columns of this design. Fill it and upload it in People.', 'pikacart' ) );
		} );

		const nameInput = el.querySelector( '.proj-name' );
		nameInput.addEventListener( 'input', () => P.change( { name: nameInput.value } ) );

		const idx = STEPS.findIndex( ( s ) => s[ 0 ] === step );
		const prev = STEPS[ idx - 1 ];
		const next = STEPS[ idx + 1 ];
		el.querySelector( '[data-nav]' ).innerHTML = `${ prev ? `<a class="btn" href="${ esc( ctx.url( `projects/${ id }/${ prev[ 0 ] }` ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( prev[ 1 ]() ) }</a>` : '<span></span>' }
			${ next ? `<a class="btn btn-primary" href="${ esc( ctx.url( `projects/${ id }/${ next[ 0 ] }` ) ) }" data-link>${ esc( next[ 1 ]() ) }${ icon( 'chevron-right' ) }</a>` : '<span></span>' }`;

		// Save immediately on every step change.
		el.querySelectorAll( '[data-steps] a, [data-nav] a' ).forEach( ( a ) => a.addEventListener( 'click', () => {
			autosave.flush().catch( () => {} );
		} ) );
		if ( data.project.step !== idx + 1 ) {
			autosave.set( { step: idx + 1 } );
		}

		const body = el.querySelector( '[data-body]' );
		const steps = { design: designStep, setup: setupStep, details: detailsStep, editor: editorStep, people: peopleStep, download: downloadStep };
		cleanupStep = steps[ step ]( body, P );
	} )();

	return () => {
		alive = false;
		if ( typeof cleanupStep === 'function' ) {
			cleanupStep();
		}
		if ( autosave ) {
			autosave.dispose();
		}
	};
}

/**
 * Project context shared by the steps, the members screen and the print sheet maker.
 */
export function makeP( ctx, cat, data, sample, getAutosave ) {
	const P = {
		ctx,
		cat,
		data,
		sample,
		get project() {
			return data.project;
		},
		/** Change the project locally and autosave it. */
		change( patch ) {
			Object.assign( data.project, patch );
			const a = getAutosave && getAutosave();
			if ( a ) {
				a.set( patch );
			}
		},
		size() {
			return cardSize( data.project, cat.sizes );
		},
		palette() {
			return paletteOf( data.project, cat.palettes );
		},
		layout() {
			return layoutOf( data.project, data.template, data.subtype, P.size() );
		},
		kind() {
			return kindOf( data.subtype );
		},
		vars( member ) {
			const m = member || P.sample;
			if ( m ) {
				return memberVars( m, ctx.me.org, projectInfo( data.project, data.subtype ), P.kind() );
			}
			const info = projectInfo( data.project, data.subtype );
			const dates = {};
			if ( info.valid_until ) {
				dates.valid_until = info.valid_until;
			}
			if ( info.valid_from ) {
				dates.valid_from = info.valid_from;
			}
			return demoVars( P.kind(), 0, Object.assign( { subtype_name: data.subtype ? data.subtype.name : '' }, orgVars( ctx.me.org ), info ), dates );
		},
		fieldsOn() {
			return [ ...enabledFields( data.project ), ...( data.project.fields.custom || [] ).map( ( c ) => c.key ).filter( ( k ) => ( data.project.fields.on || [] ).includes( k ) ) ];
		},
		renderOpts( side, member ) {
			return {
				sizeMm: P.size(),
				palette: P.palette(),
				vars: P.vars( member ),
				fields: P.fieldsOn(),
				flags: flagsOf( data.project ),
				side,
				demo: ! ( member || P.sample ),
			};
		},
		async preview( root ) {
			const canvases = root.querySelectorAll( 'canvas[data-side]' );
			const lay = P.layout();
			const size = P.size();
			for ( const c of canvases ) {
				const w = Number( c.dataset.width || ( size.w > size.h ? 340 : 230 ) ) * Math.min( 2, devicePixelRatio || 1 );
				await renderSide( c, lay[ c.dataset.side ], Object.assign( P.renderOpts( c.dataset.side ), { width: w } ) );
				c.style.aspectRatio = `${ size.w } / ${ size.h }`;
			}
		},
	};
	return P;
}

/** Load a project and its context (no autosave) for other screens. */
export async function loadProject( ctx, id ) {
	await waitForSave( id );
	const [ cat, data ] = await Promise.all( [ catalog(), api( `projects/${ id }` ) ] );
	const first = await api( `projects/${ id }/members?per_page=1&sort=created` );
	return makeP( ctx, cat, data, first.members[ 0 ] || null, null );
}

/* ---------- Shared preview block ---------- */

function previewHTML( P ) {
	const land = P.size().w > P.size().h;
	return `<aside class="live-preview ${ land ? 'is-landscape' : '' }">
		<div class="lp-head"><strong>${ esc( __( 'Live preview', 'pikacart' ) ) }</strong><span class="muted small">${ esc( P.sample ? sprintf( __( 'Showing %s', 'pikacart' ), P.sample.name ) : __( 'Sample person', 'pikacart' ) ) }</span></div>
		<div class="lp-cards"><canvas data-side="front"></canvas><canvas data-side="back"></canvas></div>
	</aside>`;
}

/* ---------- Step 1: change design ---------- */

function designStep( body, P ) {
	const { cat, ctx } = P;
	body.innerHTML = `<div class="card">
		<div class="card-head"><h3>${ esc( __( 'Choose a design', 'pikacart' ) ) }</h3><a class="btn btn-sm" href="${ esc( ctx.url( 'designs/request' ) ) }" data-link>${ icon( 'sparkles' ) }${ esc( __( 'Design on Demand', 'pikacart' ) ) }</a></div>
		${ P.project.design ? `<p class="banner banner-info">${ icon( 'info' ) }<span>${ esc( __( 'You have customised this design. Choosing another design starts again from its original layout.', 'pikacart' ) ) }</span></p>` : '' }
		<p class="muted small">${ esc( __( 'Pick any design. Your people, details, size and colours stay the same, so you can change the design at any time.', 'pikacart' ) ) }</p>
		<div class="chips" data-levels></div>
		<div class="tpl-grid" data-grid></div>
	</div>`;
	let level = '';
	api( `templates?subtype=${ P.project.subtype_id }` ).then( ( res ) => {
		const grid = body.querySelector( '[data-grid]' );
		if ( ! grid ) {
			return;
		}
		const orient = P.project.orientation;
		const lv = body.querySelector( '[data-levels]' );
		lv.innerHTML = [ [ '', __( 'All styles', 'pikacart' ) ], ...Object.entries( cat.levels ).filter( ( [ k ] ) => res.templates.some( ( t ) => t.level === k ) ) ].map( ( [ k, l ] ) => `<button type="button" class="chip ${ k === level ? 'is-on' : '' }" data-level="${ esc( k ) }">${ esc( l ) }</button>` ).join( '' );
		lv.addEventListener( 'click', ( e ) => {
			const b = e.target.closest( '[data-level]' );
			if ( ! b ) {
				return;
			}
			level = b.dataset.level;
			lv.querySelectorAll( '[data-level]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
			grid.querySelectorAll( '.tpl' ).forEach( ( x ) => {
				x.hidden = !! level && x.dataset.level !== level;
			} );
		} );
		// Current design first.
		const list = res.templates.filter( ( t ) => supportsOrientation( t, orient ) ).sort( ( a, b ) => ( b.id === P.project.template_id ) - ( a.id === P.project.template_id ) );
		grid.className = `tpl-grid ${ orient === 'landscape' ? 'is-landscape' : '' }`;
		grid.innerHTML = list.map( ( t ) => `<button type="button" class="tpl ${ t.id === P.project.template_id ? 'is-current' : '' }" data-tid="${ t.id }" data-level="${ esc( t.level ) }"><canvas></canvas><span class="tpl-meta"><strong>${ esc( t.name ) }</strong><span class="lvl lvl-${ esc( t.level ) }">${ esc( cat.levels[ t.level ] || t.level ) }</span></span></button>` ).join( '' );
		grid.querySelectorAll( '.tpl' ).forEach( ( b, i ) => {
			const t = list[ i ];
			thumb( b.querySelector( 'canvas' ), { template: t, subtype: P.data.subtype, orientation: orient, palette: P.palette(), size: P.size(), org: orgVars( ctx.me.org ), index: 2, width: orient === 'landscape' ? 250 : 165 } );
			b.addEventListener( 'click', async () => {
				if ( t.id === P.project.template_id ) {
					return;
				}
				if ( P.project.design && ! ( await confirmDialog( { title: __( 'Use this design instead?', 'pikacart' ), message: __( 'Your fine-tuning on the current design will be replaced.', 'pikacart' ), confirm: __( 'Use this design', 'pikacart' ) } ) ) ) {
					return;
				}
				P.data.template = t;
				P.change( { template_id: t.id, design: null } );
				grid.querySelectorAll( '.tpl' ).forEach( ( x ) => x.classList.toggle( 'is-current', x === b ) );
				toast( __( 'Design changed. Your people and details are kept.', 'pikacart' ) );
			} );
		} );
	} ).catch( ( e ) => toast( e.message, 'error' ) );
}

/* ---------- Step 2: size, orientation, colour ---------- */

function setupStep( body, P ) {
	const { cat } = P;
	const p = P.project;
	const curPal = P.palette();
	const tplPals = ( P.data.template ? P.data.template.palettes : [] ).map( ( pid ) => cat.palettes.find( ( x ) => x.id === pid ) ).filter( Boolean );
	const others = cat.palettes.filter( ( x ) => ! tplPals.includes( x ) );
	const isCustomSize = !! ( p.custom_w && p.custom_h );

	body.innerHTML = `<div class="split">
		<div class="split-main">
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Orientation', 'pikacart' ) ) }</h3></div>
				<div class="seg seg-lg">
					<button type="button" data-orient="portrait" class="${ p.orientation === 'portrait' ? 'is-on' : '' }"><span class="or-icon or-p"></span>${ esc( __( 'Portrait', 'pikacart' ) ) }</button>
					<button type="button" data-orient="landscape" class="${ p.orientation === 'landscape' ? 'is-on' : '' }"><span class="or-icon or-l"></span>${ esc( __( 'Landscape', 'pikacart' ) ) }</button>
				</div>
			</section>
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Card size', 'pikacart' ) ) }</h3><span class="muted small">${ esc( __( 'Match the holder you use', 'pikacart' ) ) }</span></div>
				<div class="size-grid">
					${ cat.sizes.map( ( s ) => `<button type="button" class="size-opt ${ ! isCustomSize && p.size_id === s.id ? 'is-on' : '' }" data-size="${ s.id }"><strong>${ esc( s.w + ' × ' + s.h + ' mm' ) }</strong><small>${ esc( s.note ) }</small></button>` ).join( '' ) }
					<button type="button" class="size-opt ${ isCustomSize ? 'is-on' : '' }" data-size="custom"><strong>${ esc( __( 'Custom size', 'pikacart' ) ) }</strong><small>${ esc( __( 'Any size in millimetres', 'pikacart' ) ) }</small></button>
				</div>
				<div class="grid-2 custom-size" ${ isCustomSize ? '' : 'hidden' }>
					<div class="field"><label for="cw">${ esc( __( 'Width (mm)', 'pikacart' ) ) }</label><input id="cw" type="number" min="20" max="330" step="0.5" value="${ esc( p.custom_w || 54 ) }"></div>
					<div class="field"><label for="ch">${ esc( __( 'Height (mm)', 'pikacart' ) ) }</label><input id="ch" type="number" min="20" max="330" step="0.5" value="${ esc( p.custom_h || 86 ) }"></div>
				</div>
				<label class="check"><input type="checkbox" data-bleed ${ p.fields.bleed ? 'checked' : '' }> ${ esc( __( 'Add 2 mm bleed to downloads (for PVC card printers)', 'pikacart' ) ) }</label>
			</section>
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Colours', 'pikacart' ) ) }</h3></div>
				<p class="muted small">${ esc( __( 'Suggested for this design', 'pikacart' ) ) }</p>
				<div class="pal-row">${ tplPals.map( ( x ) => palBtn( x, curPal ) ).join( '' ) }</div>
				<p class="muted small">${ esc( __( 'More colours', 'pikacart' ) ) }</p>
				<div class="pal-row">${ others.map( ( x ) => palBtn( x, curPal ) ).join( '' ) }</div>
				<details class="custom-colours" ${ p.palette && p.palette.p ? 'open' : '' }>
					<summary>${ esc( __( 'Match my brand colours', 'pikacart' ) ) }</summary>
					<div class="colour-grid">
						${ [ [ 'p', __( 'Main colour', 'pikacart' ) ], [ 's', __( 'Second colour', 'pikacart' ) ], [ 'a', __( 'Accent', 'pikacart' ) ], [ 't', __( 'Text', 'pikacart' ) ] ].map( ( [ k, label ] ) => `<label class="colour-field"><input type="color" data-c="${ k }" value="${ esc( curPal[ k ] || '#000000' ) }"><span>${ esc( label ) }</span></label>` ).join( '' ) }
					</div>
				</details>
			</section>
		</div>
		${ previewHTML( P ) }
	</div>`;

	const refresh = () => P.preview( body );
	refresh();

	body.querySelectorAll( '[data-orient]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		P.change( { orientation: b.dataset.orient } );
		body.querySelectorAll( '[data-orient]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		body.querySelector( '.live-preview' ).classList.toggle( 'is-landscape', b.dataset.orient === 'landscape' );
		refresh();
	} ) );
	body.querySelectorAll( '[data-size]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		body.querySelectorAll( '[data-size]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		const custom = b.dataset.size === 'custom';
		body.querySelector( '.custom-size' ).hidden = ! custom;
		if ( custom ) {
			P.change( { custom_w: Number( body.querySelector( '#cw' ).value ), custom_h: Number( body.querySelector( '#ch' ).value ) } );
		} else {
			P.change( { size_id: Number( b.dataset.size ), custom_w: 0, custom_h: 0 } );
		}
		refresh();
	} ) );
	body.querySelectorAll( '#cw, #ch' ).forEach( ( i ) => i.addEventListener( 'change', () => {
		const w = Number( body.querySelector( '#cw' ).value );
		const h = Number( body.querySelector( '#ch' ).value );
		if ( w >= 20 && h >= 20 && w <= 330 && h <= 330 ) {
			P.change( { custom_w: w, custom_h: h } );
			refresh();
		} else {
			toast( __( 'Custom size must be between 20 and 330 mm.', 'pikacart' ), 'error' );
		}
	} ) );
	body.querySelector( '[data-bleed]' ).addEventListener( 'change', ( e ) => {
		P.change( { fields: Object.assign( {}, P.project.fields, { bleed: e.target.checked } ) } );
	} );
	body.querySelectorAll( '[data-pal]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		P.change( { palette: { id: Number( b.dataset.pal ) } } );
		body.querySelectorAll( '[data-pal]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		const pal = P.palette();
		body.querySelectorAll( '[data-c]' ).forEach( ( i ) => {
			i.value = pal[ i.dataset.c ];
		} );
		refresh();
	} ) );
	body.querySelectorAll( '[data-c]' ).forEach( ( i ) => i.addEventListener( 'input', () => {
		const pal = {};
		body.querySelectorAll( '[data-c]' ).forEach( ( x ) => {
			pal[ x.dataset.c ] = x.value;
		} );
		P.change( { palette: pal } );
		body.querySelectorAll( '[data-pal]' ).forEach( ( x ) => x.classList.remove( 'is-on' ) );
		refresh();
	} ) );
}

function palBtn( x, cur ) {
	return `<button type="button" class="pal ${ cur && cur.id === x.id ? 'is-on' : '' }" data-pal="${ x.id }" title="${ esc( x.name ) }"><span style="background:${ esc( x.p ) }"></span><span style="background:${ esc( x.s ) }"></span><span style="background:${ esc( x.a ) }"></span></button>`;
}

/* ---------- Step 3: details, fields and back side ---------- */

function detailsStep( body, P ) {
	const { ctx } = P;
	const p = P.project;
	const f = p.fields;
	const org = ctx.me.org;
	const subFields = ( P.data.subtype ? P.data.subtype.fields : [] );
	const locked = [ 'photo', 'name', 'id_no' ];
	const flags = flagsOf( p );
	const missing = [ ! org.logo && __( 'logo', 'pikacart' ), ! org.sign && __( 'signature', 'pikacart' ), ! org.address && __( 'address', 'pikacart' ) ].filter( Boolean );

	body.innerHTML = `<div class="split">
		<div class="split-main">
			<section class="card">
				<div class="card-head"><h3>${ icon( 'building' ) } ${ esc( __( 'Organisation details', 'pikacart' ) ) }</h3><a class="btn btn-sm" href="${ esc( ctx.url( 'organisation' ) ) }" data-link>${ esc( __( 'Edit', 'pikacart' ) ) }</a></div>
				<div class="org-summary">
					${ org.logo ? `<img src="${ esc( org.logo ) }" alt="">` : `<span class="org-nologo">${ icon( 'image' ) }</span>` }
					<div><strong>${ esc( org.name ) }</strong><span>${ esc( org.tagline ) }</span><span class="muted">${ esc( org.address ) }</span></div>
				</div>
				${ missing.length ? `<p class="banner banner-info">${ icon( 'info' ) }<span>${ esc( sprintf( __( 'Add your %s in Organisation to complete the cards.', 'pikacart' ), missing.join( ', ' ) ) ) }</span></p>` : '' }
				<div class="grid-2">
					<div class="field"><label for="d-title">${ esc( __( 'Card title', 'pikacart' ) ) }</label><input id="d-title" data-k="card_title" value="${ esc( f.card_title ) }" placeholder="${ esc( P.vars().card_title ) }"></div>
					<div class="field"><label for="d-session">${ esc( __( 'Session / year', 'pikacart' ) ) }</label><input id="d-session" data-k="session" value="${ esc( f.session ) }" placeholder="2026-27"></div>
				</div>
			</section>
			<section class="card">
				<div class="card-head"><h3>${ icon( 'calendar' ) } ${ esc( __( 'Card validity', 'pikacart' ) ) }</h3></div>
				<p class="muted small">${ esc( __( 'One date for all cards in this project. A person can still have their own date (in Add person or in the Excel); empty ones use this date.', 'pikacart' ) ) }</p>
				<div class="grid-2">
					<div class="field"><label for="d-vu">${ esc( __( 'Valid upto', 'pikacart' ) ) }</label><input id="d-vu" data-date="valid_until" value="${ esc( f.valid_until || '' ) }" placeholder="31-03-${ new Date().getFullYear() + 1 }" inputmode="numeric" maxlength="10"><small class="field-hint" data-date-msg="valid_until"></small></div>
					<div class="field"><label for="d-vf">${ esc( __( 'Valid from (optional)', 'pikacart' ) ) }</label><input id="d-vf" data-date="valid_from" value="${ esc( f.valid_from || '' ) }" placeholder="01-04-${ new Date().getFullYear() }" inputmode="numeric" maxlength="10"><small class="field-hint" data-date-msg="valid_from"></small></div>
				</div>
			</section>
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Fields on the card', 'pikacart' ) ) }</h3></div>
				<p class="muted small">${ esc( __( 'Switched-off or empty fields disappear from the card with no blank gap.', 'pikacart' ) ) }</p>
				<div class="toggle-list cols2">
					${ subFields.map( ( x ) => `<label class="toggle ${ locked.includes( x.key ) ? 'is-locked' : '' }"><input type="checkbox" data-field="${ esc( x.key ) }" ${ ( f.on || [] ).includes( x.key ) || locked.includes( x.key ) ? 'checked' : '' } ${ locked.includes( x.key ) ? 'disabled' : '' }><span class="toggle-ui"></span><span>${ esc( x.label ) }${ x.required ? ' *' : '' }</span></label>` ).join( '' ) }
				</div>
				<h4 class="sub-h">${ esc( __( 'Your own fields (up to 5)', 'pikacart' ) ) }</h4>
				<div class="custom-fields">
					${ [ 0, 1, 2, 3, 4 ].map( ( i ) => {
						const c = ( f.custom || [] )[ i ];
						const key = 'cf' + ( i + 1 );
						return `<div class="cf-row"><input data-cf="${ i }" value="${ esc( c ? c.label : '' ) }" placeholder="${ esc( sprintf( __( 'e.g. House, Bus route, Hostel room', 'pikacart' ) ) ) }" maxlength="40" aria-label="${ esc( sprintf( __( 'Custom field %d', 'pikacart' ), i + 1 ) ) }"><label class="toggle"><input type="checkbox" data-field="${ key }" ${ ( f.on || [] ).includes( key ) ? 'checked' : '' }><span class="toggle-ui"></span></label></div>`;
					} ).join( '' ) }
				</div>
			</section>
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Back side, QR code and barcode', 'pikacart' ) ) }</h3></div>
				<div class="toggle-list cols2">
					${ [ [ 'qr_front', __( 'QR code on front', 'pikacart' ) ], [ 'qr_back', __( 'QR code on back', 'pikacart' ) ], [ 'bar_front', __( 'Barcode on front', 'pikacart' ) ], [ 'bar_back', __( 'Barcode on back', 'pikacart' ) ], [ 'renewal', __( 'Renewal box (session & signature)', 'pikacart' ) ] ].map( ( [ k, l ] ) => `<label class="toggle"><input type="checkbox" data-flag="${ k }" ${ flags[ k ] ? 'checked' : '' }><span class="toggle-ui"></span><span>${ esc( l ) }</span></label>` ).join( '' ) }
				</div>
				<div class="field"><label for="d-terms">${ esc( __( 'Terms and conditions (back side)', 'pikacart' ) ) }</label><textarea id="d-terms" data-k="terms" rows="4" placeholder="${ esc( ( P.data.subtype && P.data.subtype.terms ) || '' ) }">${ esc( f.terms ) }</textarea></div>
				<p class="muted small">${ icon( 'shield' ) } ${ esc( __( 'The QR code opens a secure verification page. Choose what it shows in Organisation → Verification page privacy.', 'pikacart' ) ) }</p>
			</section>
		</div>
		${ previewHTML( P ) }
	</div>`;

	let t = null;
	const refresh = () => {
		clearTimeout( t );
		t = setTimeout( () => P.preview( body ), 150 );
	};
	refresh();
	const save = ( patch ) => {
		P.change( { fields: Object.assign( {}, P.project.fields, patch ) } );
		refresh();
	};
	body.querySelectorAll( '[data-k]' ).forEach( ( i ) => i.addEventListener( 'input', () => save( { [ i.dataset.k ]: i.value } ) ) );
	// Dates: DD-MM-YYYY. A valid date also switches the field on so it prints.
	body.querySelectorAll( '[data-date]' ).forEach( ( i ) => i.addEventListener( 'input', () => {
		const k = i.dataset.date;
		const v = i.value.trim().replace( /[./]/g, '-' );
		const msg = body.querySelector( `[data-date-msg="${ k }"]` );
		const m = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec( v );
		// A real calendar date (31-02 or 31-04 are rejected).
		const dt = m ? new Date( Number( m[ 3 ] ), Number( m[ 2 ] ) - 1, Number( m[ 1 ] ) ) : null;
		const real = !! dt && dt.getFullYear() === Number( m[ 3 ] ) && dt.getMonth() === Number( m[ 2 ] ) - 1 && dt.getDate() === Number( m[ 1 ] ) && Number( m[ 3 ] ) >= 2000;
		if ( v && ! real ) {
			msg.textContent = __( 'Write the date as DD-MM-YYYY, e.g. 31-03-2027.', 'pikacart' );
			msg.className = 'field-hint is-error';
			return;
		}
		msg.textContent = v ? __( 'Saved. Shown on every card that has no date of its own.', 'pikacart' ) : '';
		msg.className = 'field-hint';
		const date = real ? `${ m[ 1 ].padStart( 2, '0' ) }-${ m[ 2 ].padStart( 2, '0' ) }-${ m[ 3 ] }` : '';
		const on = new Set( P.project.fields.on || [] );
		const offered = subFields.some( ( x ) => x.key === k );
		if ( date && offered && ! on.has( k ) ) {
			on.add( k );
			const box = body.querySelector( `[data-field="${ k }"]` );
			if ( box ) {
				box.checked = true;
			}
		}
		save( { [ k ]: date, on: [ ...on ] } );
	} ) );
	body.querySelectorAll( '[data-field]' ).forEach( ( i ) => i.addEventListener( 'change', () => {
		const on = new Set( P.project.fields.on || [] );
		if ( i.checked ) {
			on.add( i.dataset.field );
		} else {
			on.delete( i.dataset.field );
		}
		locked.forEach( ( k ) => on.add( k ) );
		save( { on: [ ...on ] } );
	} ) );
	body.querySelectorAll( '[data-cf]' ).forEach( ( i ) => i.addEventListener( 'input', () => {
		const custom = [];
		body.querySelectorAll( '[data-cf]' ).forEach( ( x, n ) => {
			if ( x.value.trim() ) {
				custom.push( { key: 'cf' + ( n + 1 ), label: x.value.trim() } );
			}
		} );
		save( { custom } );
	} ) );
	body.querySelectorAll( '[data-flag]' ).forEach( ( i ) => i.addEventListener( 'change', () => {
		save( { flags: Object.assign( {}, flagsOf( P.project ), { [ i.dataset.flag ]: i.checked } ) } );
	} ) );
}
