/**
 * Create ID card: first choose the organisation (school, college, office...)
 * the card is for, then the category and card type, then one of 50 styles
 * with filters (orientation, style level, colour) and a live preview.
 * Routes: create (organisation), create/types, create/{subtypeId}
 */

import { api } from '../api.js';
import { esc, icon, toast, withLoading } from '../ui.js';
import { catalog, findSubtype, orgVars } from '../catalog.js';
import { thumb } from '../../card/gallery.js';
import { renderSide } from '../../card/render.js';
import { layoutFor } from '../../card/families.js';
import { demoVars, kindOf } from '../../card/data.js';
import { supportsOrientation } from '../../card/scene.js';
import { orgMark, orgName, orgStats, switchOrg, activeOrg } from '../orgs.js';
import { journeyBar, journeyGuide, guideBanner } from '../journey.js';

const { __, _n, sprintf } = window.wp.i18n;

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
			if ( ! ctx.me.client ) {
				orgPicker( el, ctx );
			} else if ( sid ) {
				gallery( el, ctx, cat, sid, () => alive );
			} else if ( params[ 0 ] === 'types' ) {
				chooser( el, ctx, cat );
			} else {
				orgPicker( el, ctx );
			}
		} )
		.catch( ( e ) => {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p></div>`;
		} );

	return () => {
		alive = false;
	};
}

/** Links for the step bar while choosing: step 1 and (with an organisation) step 2. */
function createHref( ctx ) {
	return ( n ) => ( n === 1 ? ctx.url( 'create' ) : n === 2 && ctx.me.client ? ctx.url( 'create/types' ) : '' );
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

/** Step 0: which organisation is this ID card for? */
function orgPicker( el, ctx ) {
	let query = '';
	const draw = () => {
		const me = ctx.me;
		const term = query.trim().toLowerCase();
		const list = [ ...me.clients ].sort( ( a, b ) => ( b.id === me.client ) - ( a.id === me.client ) );
		const shown = list.filter( ( o ) => ! term || orgName( o ).toLowerCase().includes( term ) );
		el.innerHTML = `${ guideBanner() }${ journeyBar( 1, createHref( ctx ) ) }<div class="org-pick">
			${ journeyGuide( 1 ) }
			${ list.length > 6 ? `<div class="org-search"><span>${ icon( 'search' ) }</span><input type="search" data-q value="${ esc( query ) }" placeholder="${ esc( __( 'Search organisations…', 'pikacart' ) ) }" aria-label="${ esc( __( 'Search organisations', 'pikacart' ) ) }"></div>` : '' }
			<div class="org-pick-grid">
				<a class="org-pick-item org-pick-add" href="${ esc( ctx.url( 'organisations/new/card' ) ) }" data-link>
					<span class="org-mark lg is-add">${ icon( 'plus' ) }</span>
					<span class="org-pick-t"><b>${ esc( __( 'Add a new organisation', 'pikacart' ) ) }</b><small>${ esc( __( 'School, college, office, hospital…', 'pikacart' ) ) }</small></span>
				</a>
				${ shown.map( ( o ) => `<button type="button" class="org-pick-item${ o.id === me.client ? ' is-last' : '' }" data-org="${ o.id }">
					${ orgMark( o, 'lg' ) }
					<span class="org-pick-t"><b>${ esc( orgName( o ) ) }</b><small>${ esc( orgStats( o ) ) }</small>${ o.id === me.client ? `<em>${ esc( __( 'Last used', 'pikacart' ) ) }</em>` : '' }</span>
					${ icon( 'chevron-right' ) }
				</button>` ).join( '' ) }
			</div>
			${ term && ! shown.length ? `<p class="muted center">${ esc( __( 'No organisation matches your search.', 'pikacart' ) ) }</p>` : '' }
		</div>`;
		const q = el.querySelector( '[data-q]' );
		if ( q ) {
			q.addEventListener( 'input', () => {
				query = q.value;
				const pos = q.selectionStart;
				draw();
				const n = el.querySelector( '[data-q]' );
				n.focus();
				n.setSelectionRange( pos, pos );
			} );
		}
		el.querySelectorAll( '[data-org]' ).forEach( ( b ) => b.addEventListener( 'click', () => withLoading( b, async () => {
			if ( await switchOrg( ctx, Number( b.dataset.org ), true ) ) {
				ctx.navigate( 'create/types' );
			}
		} ) ) );
	};
	draw();
}

function chooser( el, ctx, cat ) {
	const mine = ctx.me.org.category_id;
	const last = lastCreate();
	const lastSub = last && last.org === ctx.me.client ? findSubtype( cat, last.sid ) : {};
	const forOrg = activeOrg( ctx.me );
	const ordered = [ ...cat.tree ].sort( ( a, b ) => ( b.id === mine ) - ( a.id === mine ) );
	el.innerHTML = `${ journeyBar( 2, createHref( ctx ) ) }
		${ journeyGuide( 2, `<a class="btn btn-sm journey-extra" href="${ esc( ctx.url( 'designs' ) ) }" data-link>${ icon( 'sparkles' ) }${ esc( __( 'Use my own design', 'pikacart' ) ) }</a>` ) }
		${ forOrg ? `<div class="card-for">${ orgMark( forOrg, 'sm' ) }<span>${ esc( __( 'Card for', 'pikacart' ) ) } <b>${ esc( orgName( forOrg ) ) }</b></span><a href="${ esc( ctx.url( 'create' ) ) }" data-link>${ esc( __( 'Change', 'pikacart' ) ) }</a></div>` : '' }
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
		ctx.navigate( 'create/types', true );
		return;
	}
	const saved = lastCreate();
	const st = Object.assign( { orientation: 'portrait', level: '', palette: 0, own: false }, saved && saved.sid === sid && saved.org === ctx.me.client ? saved.st : {} );
	const remember = () => rememberCreate( { org: ctx.me.client, sid, st } );
	remember();
	el.innerHTML = `${ journeyBar( 3, createHref( ctx ) ) }
		${ journeyGuide( 3, `<a class="btn btn-sm journey-extra" href="${ esc( ctx.url( 'designs/request' ) ) }" data-link>${ icon( 'sparkles' ) }${ esc( __( 'Design on Demand', 'pikacart' ) ) }</a>` ) }
		<div class="section-head-row">
			<div><a class="back-link" href="${ esc( ctx.url( 'create/types' ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( __( 'Change card type', 'pikacart' ) ) }</a><h2 class="h2">${ esc( category.name + ' · ' + subtype.name ) }</h2></div>
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
