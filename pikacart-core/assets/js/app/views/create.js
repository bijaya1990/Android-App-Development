/**
 * Create ID card: choose category and card type, then pick one of 50 styles
 * with filters (orientation, style level, colour) and a live preview.
 * Routes: create, create/{subtypeId}
 */

import { api } from '../api.js';
import { esc, icon, toast, withLoading } from '../ui.js';
import { catalog, findSubtype, orgVars } from '../catalog.js';
import { thumb } from '../../card/gallery.js';
import { renderSide } from '../../card/render.js';
import { layoutFor } from '../../card/families.js';
import { demoVars, kindOf } from '../../card/data.js';
import { supportsOrientation } from '../../card/scene.js';

const { __, sprintf } = window.wp.i18n;

const CAT_ICONS = { school: 'school', college: 'college', book: 'book', briefcase: 'briefcase', health: 'health', heart: 'heart', ticket: 'ticket', shield: 'shield', users: 'users', mic: 'mic' };

export default function create( el, ctx, params ) {
	let alive = true;
	el.innerHTML = '<div class="skeleton-grid"><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div></div>';

	catalog()
		.then( ( cat ) => {
			if ( ! alive ) {
				return;
			}
			const sid = Number( params[ 0 ] || 0 );
			if ( sid ) {
				gallery( el, ctx, cat, sid, () => alive );
			} else {
				chooser( el, ctx, cat );
			}
		} )
		.catch( ( e ) => {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p></div>`;
		} );

	return () => {
		alive = false;
	};
}

function stepBar( current ) {
	const steps = [ __( 'Card type', 'pikacart' ), __( 'Design', 'pikacart' ), __( 'Size & colour', 'pikacart' ), __( 'Details', 'pikacart' ), __( 'Fine-tune', 'pikacart' ), __( 'People', 'pikacart' ), __( 'Download', 'pikacart' ) ];
	return `<ol class="stepbar steps7">${ steps.map( ( s, i ) => `<li class="${ i + 1 < current ? 'is-done' : i + 1 === current ? 'is-current' : '' }"><span class="step-dot">${ i + 1 < current ? icon( 'check' ) : i + 1 }</span><span class="step-label">${ esc( s ) }</span></li>` ).join( '' ) }</ol>`;
}

const LAST = 'pkc_last_create';
function lastCreate() {
	try {
		return JSON.parse( window.localStorage.getItem( LAST ) || 'null' );
	} catch ( e ) {
		return null;
	}
}
function rememberCreate( v ) {
	try {
		window.localStorage.setItem( LAST, JSON.stringify( v ) );
	} catch ( e ) {
		// Private mode: nothing to remember.
	}
}

function chooser( el, ctx, cat ) {
	const mine = ctx.me.org.category_id;
	const last = lastCreate();
	const lastSub = last && last.org === ctx.me.org.id ? findSubtype( cat, last.sid ) : {};
	const ordered = [ ...cat.tree ].sort( ( a, b ) => ( b.id === mine ) - ( a.id === mine ) );
	el.innerHTML = `${ stepBar( 1 ) }
		<div class="section-head-row"><div><h2 class="h2">${ esc( __( 'What kind of ID card?', 'pikacart' ) ) }</h2><p class="muted">${ esc( __( 'Pick a category and a card type. Each has 50 professional styles.', 'pikacart' ) ) }</p></div>
		<a class="btn" href="${ esc( ctx.url( 'designs' ) ) }" data-link>${ icon( 'sparkles' ) }${ esc( __( 'Use my own design', 'pikacart' ) ) }</a></div>
		${ lastSub.subtype ? `<a class="card continue-card" href="${ esc( ctx.url( 'create/' + lastSub.subtype.id ) ) }" data-link>${ icon( 'refresh' ) }<span><strong>${ esc( __( 'Continue choosing a design', 'pikacart' ) ) }</strong><small>${ esc( lastSub.category.name + ' · ' + lastSub.subtype.name ) }</small></span>${ icon( 'chevron-right' ) }</a>` : '' }
		<div class="type-grid">
			${ ordered.map( ( c ) => `<section class="card type-card ${ c.id === mine ? 'is-mine' : '' }">
				<div class="type-head"><span class="cat-icon">${ icon( CAT_ICONS[ c.icon ] || 'idcard' ) }</span><div><h3>${ esc( c.name ) }</h3>${ c.id === mine ? `<span class="badge badge-free">${ esc( __( 'Your category', 'pikacart' ) ) }</span>` : '' }</div></div>
				<div class="type-subs">${ c.subtypes.map( ( s ) => `<a class="type-sub" href="${ esc( ctx.url( 'create/' + s.id ) ) }" data-link><span>${ esc( s.name ) }</span>${ icon( 'chevron-right' ) }</a>` ).join( '' ) }</div>
			</section>` ).join( '' ) }
		</div>`;
}

async function gallery( el, ctx, cat, sid, alive ) {
	const { category, subtype } = findSubtype( cat, sid );
	if ( ! subtype ) {
		ctx.navigate( 'create', true );
		return;
	}
	const saved = lastCreate();
	const st = Object.assign( { orientation: 'portrait', level: '', palette: 0, own: false }, saved && saved.sid === sid && saved.org === ctx.me.org.id ? saved.st : {} );
	const remember = () => rememberCreate( { org: ctx.me.org.id, sid, st } );
	remember();
	el.innerHTML = `${ stepBar( 2 ) }
		<div class="section-head-row">
			<div><a class="back-link" href="${ esc( ctx.url( 'create' ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( category.name ) }</a><h2 class="h2">${ esc( subtype.name ) }</h2></div>
			<a class="btn" href="${ esc( ctx.url( 'designs/request' ) ) }" data-link>${ icon( 'sparkles' ) }${ esc( __( 'Design on Demand', 'pikacart' ) ) }</a>
		</div>
		<div class="filters card">
			<div class="seg" role="group" aria-label="${ esc( __( 'Orientation', 'pikacart' ) ) }">
				<button type="button" class="${ st.orientation === 'portrait' ? 'is-on' : '' }" data-orient="portrait">${ esc( __( 'Portrait', 'pikacart' ) ) }</button>
				<button type="button" class="${ st.orientation === 'landscape' ? 'is-on' : '' }" data-orient="landscape">${ esc( __( 'Landscape', 'pikacart' ) ) }</button>
			</div>
			<div class="chips" role="group" aria-label="${ esc( __( 'Style', 'pikacart' ) ) }">
				<button type="button" class="chip ${ ! st.level ? 'is-on' : '' }" data-level="">${ esc( __( 'All styles', 'pikacart' ) ) }</button>
				${ Object.entries( cat.levels ).map( ( [ k, v ] ) => `<button type="button" class="chip ${ st.level === k ? 'is-on' : '' }" data-level="${ esc( k ) }">${ esc( v ) }</button>` ).join( '' ) }
			</div>
			<div class="swatches" role="group" aria-label="${ esc( __( 'Colour', 'pikacart' ) ) }">
				<button type="button" class="swatch swatch-auto ${ ! st.palette ? 'is-on' : '' }" data-pal="0" title="${ esc( __( 'Original colours', 'pikacart' ) ) }">${ icon( 'palette' ) }</button>
				${ cat.palettes.map( ( p ) => `<button type="button" class="swatch ${ st.palette === p.id ? 'is-on' : '' }" data-pal="${ p.id }" title="${ esc( p.name ) }" style="--sw1:${ esc( p.p ) };--sw2:${ esc( p.a ) }"></button>` ).join( '' ) }
			</div>
		</div>
		<div class="tpl-grid" data-grid><div class="skeleton" style="height:260px"></div><div class="skeleton" style="height:260px"></div><div class="skeleton" style="height:260px"></div></div>`;

	let templates = [];
	try {
		templates = ( await api( `templates?subtype=${ sid }` ) ).templates;
	} catch ( e ) {
		toast( e.message, 'error' );
		return;
	}
	if ( ! alive() ) {
		return;
	}
	const grid = el.querySelector( '[data-grid]' );
	const org = orgVars( ctx.me.org );
	const palById = ( id ) => cat.palettes.find( ( p ) => p.id === id );

	function draw() {
		remember();
		const list = templates.filter( ( t ) => ( ! st.level || t.level === st.level ) && supportsOrientation( t, st.orientation ) );
		if ( ! list.length ) {
			grid.innerHTML = `<p class="muted">${ esc( __( 'No designs match these filters.', 'pikacart' ) ) }</p>`;
			return;
		}
		grid.className = `tpl-grid ${ st.orientation === 'landscape' ? 'is-landscape' : '' }`;
		grid.innerHTML = list.map( ( t, i ) => `<button type="button" class="tpl" data-tid="${ t.id }">
			<canvas aria-label="${ esc( t.name ) }"></canvas>
			<span class="tpl-meta"><strong>${ esc( t.name ) }</strong><span class="lvl lvl-${ esc( t.level ) }">${ esc( cat.levels[ t.level ] || t.level ) }</span>${ t.own ? `<span class="badge badge-free">${ esc( __( 'My design', 'pikacart' ) ) }</span>` : '' }</span>
		</button>` ).join( '' );
		grid.querySelectorAll( '.tpl' ).forEach( ( b, i ) => {
			const t = list[ i ];
			const pal = palById( st.palette ) || palById( t.palettes[ 0 ] ) || cat.palettes[ 0 ];
			thumb( b.querySelector( 'canvas' ), { template: t, subtype, orientation: st.orientation, palette: pal, index: i, org, width: st.orientation === 'landscape' ? 260 : 170 } );
			b.addEventListener( 'click', () => preview( ctx, cat, subtype, t, st, org ) );
		} );
	}

	el.querySelectorAll( '[data-orient]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		st.orientation = b.dataset.orient;
		el.querySelectorAll( '[data-orient]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		draw();
	} ) );
	el.querySelectorAll( '[data-level]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		st.level = b.dataset.level;
		el.querySelectorAll( '[data-level]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		draw();
	} ) );
	el.querySelectorAll( '[data-pal]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
		st.palette = Number( b.dataset.pal );
		el.querySelectorAll( '[data-pal]' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		draw();
	} ) );
	draw();
}

/** Large preview with front/back and colour swatches that recolour live. */
function preview( ctx, cat, subtype, t, st, org ) {
	const root = document.getElementById( 'modal-root' );
	const wrap = document.createElement( 'div' );
	wrap.className = 'modal-wrap';
	const pals = t.palettes.map( ( id ) => cat.palettes.find( ( p ) => p.id === id ) ).filter( Boolean );
	let pal = cat.palettes.find( ( p ) => p.id === st.palette ) || pals[ 0 ] || cat.palettes[ 0 ];
	let custom = null;
	wrap.innerHTML = `<div class="modal modal-lg" role="dialog" aria-modal="true" aria-label="${ esc( t.name ) }">
		<div class="modal-top"><div><h2>${ esc( t.name ) }</h2><span class="lvl lvl-${ esc( t.level ) }">${ esc( cat.levels[ t.level ] || '' ) }</span></div><button type="button" class="icon-btn" data-close aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button></div>
		<div class="pv ${ st.orientation === 'landscape' ? 'is-landscape' : '' }"><figure><canvas data-side="front"></canvas><figcaption>${ esc( __( 'Front', 'pikacart' ) ) }</figcaption></figure><figure><canvas data-side="back"></canvas><figcaption>${ esc( __( 'Back', 'pikacart' ) ) }</figcaption></figure></div>
		<div class="pv-swatches">
			${ pals.map( ( p ) => `<button type="button" class="swatch ${ p.id === pal.id ? 'is-on' : '' }" data-p="${ p.id }" title="${ esc( p.name ) }" style="--sw1:${ esc( p.p ) };--sw2:${ esc( p.a ) }"></button>` ).join( '' ) }
			<label class="swatch swatch-custom" title="${ esc( __( 'Custom colour', 'pikacart' ) ) }">${ icon( 'plus' ) }<input type="color" value="${ esc( pal.p ) }" aria-label="${ esc( __( 'Custom brand colour', 'pikacart' ) ) }"></label>
		</div>
		<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Back to designs', 'pikacart' ) ) }</button><button type="button" class="btn btn-primary" data-use>${ esc( __( 'Use this design', 'pikacart' ) ) }${ icon( 'chevron-right' ) }</button></div>
	</div>`;
	root.appendChild( wrap );
	const size = st.orientation === 'landscape' ? { w: 86, h: 54 } : { w: 54, h: 86 };
	const kind = kindOf( subtype );
	const layout = layoutFor( t, st.orientation, size.w / size.h, kind );
	const vars = demoVars( kind, 1, Object.assign( { subtype_name: subtype.name }, org ) );
	const render = () => wrap.querySelectorAll( 'canvas[data-side]' ).forEach( ( c ) => renderSide( c, layout[ c.dataset.side ], { sizeMm: size, width: ( st.orientation === 'landscape' ? 420 : 300 ) * Math.min( 2, devicePixelRatio || 1 ), palette: custom || pal, vars, demo: true, side: c.dataset.side } ) );
	render();
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
		const sw = e.target.closest( '[data-p]' );
		if ( sw ) {
			pal = cat.palettes.find( ( p ) => p.id === Number( sw.dataset.p ) );
			custom = null;
			wrap.querySelectorAll( '[data-p], .swatch-custom' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === sw ) );
			render();
		}
	} );
	wrap.querySelector( 'input[type=color]' ).addEventListener( 'input', ( e ) => {
		custom = Object.assign( {}, pal, { p: e.target.value, s: e.target.value } );
		wrap.querySelectorAll( '[data-p]' ).forEach( ( x ) => x.classList.remove( 'is-on' ) );
		wrap.querySelector( '.swatch-custom' ).classList.add( 'is-on' );
		render();
	} );
	wrap.querySelector( '[data-use]' ).addEventListener( 'click', ( e ) => withLoading( e.currentTarget, async () => {
		try {
			const res = await api( 'projects', { method: 'POST', body: { subtype_id: subtype.id, template_id: t.id } } );
			const body = { orientation: st.orientation, palette: custom ? { p: custom.p, s: custom.s, a: custom.a, t: custom.t } : { id: pal.id } };
			await api( `projects/${ res.project.id }`, { method: 'POST', body } );
			close();
			toast( __( 'Design selected. Now choose the size and colours.', 'pikacart' ) );
			ctx.navigate( `projects/${ res.project.id }/setup` );
		} catch ( err ) {
			toast( err.message, 'error' );
		}
	} ) );
	wrap.querySelector( '[data-use]' ).focus();
}
