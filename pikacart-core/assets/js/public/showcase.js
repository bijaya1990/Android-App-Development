/**
 * Public website: real card designs drawn in the browser.
 * - Homepage: hero fan, category cards and an auto-playing showcase that
 *   slides through the designs of every category, premium first.
 * - /id-card/ pages: gallery with orientation, style and colour filters, and
 *   a large live preview with colour swatches and "Use this design".
 */

import { renderSide } from '../card/render.js';
import { layoutFor } from '../card/families.js';
import { demoVars, kindOf } from '../card/data.js';

const { __, sprintf } = window.wp.i18n;
const D = window.PKC_PUB || {};
const reduceMotion = window.matchMedia && window.matchMedia( '( prefers-reduced-motion: reduce )' ).matches;

const esc = ( s ) => String( s ?? '' ).replace( /[&<>"']/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ] ) );

/* Invented organisations so every category looks real. */
const ORGS = {
	school: [ 'Sunrise Public School', 'Affiliated to CBSE, New Delhi', 'Principal' ],
	college: [ 'Northstar College of Science', 'Affiliated to Utkal University', 'Principal' ],
	coaching: [ 'Brilliant Career Academy', 'JEE · NEET · Foundation', 'Director' ],
	company: [ 'Orbit Technologies Pvt. Ltd.', 'Software & Cloud Services', 'HR Manager' ],
	hospital: [ 'CityCare Multispeciality Hospital', 'NABH Accredited', 'Medical Superintendent' ],
	ngo: [ 'Helping Hands Foundation', 'Registered Charitable Trust', 'Secretary' ],
	event: [ 'TechSummit India 2026', 'Bhubaneswar · 12–14 December', 'Event Director' ],
	security: [ 'ShieldForce Security Services', 'PSARA Licensed Agency', 'Operations Head' ],
	club: [ 'FitLife Gym & Sports Club', 'Members Only', 'Club Manager' ],
	press: [ 'Daily Bharat News', 'Truth · Every Day', 'Editor' ],
};

const subIndex = {};
( D.tree || [] ).forEach( ( c ) => c.subtypes.forEach( ( s ) => {
	subIndex[ s.id ] = { cat: c, sub: s };
} ) );

function palette( id ) {
	return ( D.palettes || [] ).find( ( p ) => p.id === id ) || ( D.palettes || [] )[ 0 ];
}

function sizeFor( orient ) {
	return orient === 'landscape' ? { w: 86, h: 54 } : { w: 54, h: 86 };
}

function varsFor( sub, catSlug, index ) {
	const o = ORGS[ catSlug ];
	const org = o ? { org_name: o[ 0 ], org_tagline: o[ 1 ], signatory_title: o[ 2 ], org_website: 'www.' + o[ 0 ].toLowerCase().replace( /[^a-z]+/g, '' ).slice( 0, 14 ) + '.in' } : {};
	return demoVars( kindOf( sub ), index, Object.assign( { subtype_name: sub ? sub.name : '' }, org ) );
}

/* ---------- Rendering queue (one card at a time keeps phones smooth) ---------- */

const queue = [];
let busy = false;
async function pump() {
	if ( busy ) {
		return;
	}
	busy = true;
	while ( queue.length ) {
		const job = queue.shift();
		try {
			await job();
		} catch ( e ) {
			// Skip a broken preview.
		}
		await new Promise( ( r ) => setTimeout( r, 0 ) );
	}
	busy = false;
}

/**
 * Draw one side of a template into a canvas.
 * @param {object} o { template, orientation, pal, side, width, index, priority }
 */
function draw( canvas, o ) {
	return new Promise( ( resolve ) => {
		const job = async () => {
			const info = subIndex[ o.template.subtype_id ] || {};
			const size = sizeFor( o.orientation );
			const kind = kindOf( info.sub );
			const layout = layoutFor( o.template, o.orientation, size.w / size.h, kind );
			const dpr = Math.min( 2, window.devicePixelRatio || 1 );
			await renderSide( canvas, layout[ o.side || 'front' ], {
				sizeMm: size,
				width: o.width * dpr,
				palette: o.pal || palette( ( o.template.palettes || [] )[ 0 ] ),
				vars: varsFor( info.sub, info.cat ? info.cat.slug : '', o.index || 0 ),
				demo: true,
				side: o.side || 'front',
			} );
			canvas.style.aspectRatio = `${ size.w } / ${ size.h }`;
			canvas.classList.add( 'is-drawn' );
			resolve( canvas );
		};
		if ( o.priority ) {
			queue.unshift( job );
		} else {
			queue.push( job );
		}
		pump();
	} );
}

let io = null;
function lazy( canvas, o ) {
	if ( typeof IntersectionObserver === 'undefined' ) {
		return draw( canvas, o );
	}
	if ( ! io ) {
		io = new IntersectionObserver( ( entries ) => entries.forEach( ( e ) => {
			if ( e.isIntersecting && e.target._job ) {
				const job = e.target._job;
				e.target._job = null;
				io.unobserve( e.target );
				job();
			}
		} ), { rootMargin: '400px' } );
	}
	return new Promise( ( resolve ) => {
		canvas._job = () => draw( canvas, o ).then( resolve );
		io.observe( canvas );
	} );
}

function copyCanvas( from, to ) {
	to.width = from.width;
	to.height = from.height;
	to.getContext( '2d' ).drawImage( from, 0, 0 );
	to.style.aspectRatio = from.style.aspectRatio;
	to.classList.add( 'is-drawn' );
}

function galleryUrl( t ) {
	const info = subIndex[ t.subtype_id ];
	return info ? `${ D.idcard }${ info.cat.slug }/${ info.sub.slug }/#t-${ t.id }` : D.idcard;
}

/** Remember the chosen design, then go to the app (or registration first). */
function useDesign( t, orientation, pal ) {
	try {
		window.localStorage.setItem( 'pkc_use', JSON.stringify( { tpl: t.id, sub: t.subtype_id, o: orientation, pal: pal ? pal.id : 0, at: Date.now() } ) );
	} catch ( e ) {
		// Private mode: the user simply picks the design again in the app.
	}
	window.location.href = D.loggedIn ? D.app : D.register;
}

function badge( level ) {
	if ( level === 'premium' ) {
		return `<span class="sw-badge sw-badge-premium"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18h18l-1.5-9-4.5 4-3-7-3 7-4.5-4z" fill="currentColor"/></svg>${ esc( __( 'Premium', 'pikacart' ) ) }</span>`;
	}
	if ( level === 'professional' ) {
		return `<span class="sw-badge sw-badge-pro">${ esc( __( 'Professional', 'pikacart' ) ) }</span>`;
	}
	return '';
}

/* ---------- Homepage showcase ---------- */

function showcase( root ) {
	const rows = ( D.showcase || [] ).filter( ( r ) => r.templates.length );
	if ( ! rows.length ) {
		root.hidden = true;
		return;
	}
	const ROTATE = 9000;
	let active = 0;
	let timer = 0;
	let paused = false;
	let userPicked = false;
	const built = {};

	root.innerHTML = `
		<div class="sw-tabs" role="tablist" aria-label="${ esc( __( 'Categories', 'pikacart' ) ) }">
			${ rows.map( ( r, i ) => `<button type="button" role="tab" class="sw-tab" data-i="${ i }" aria-selected="false"><span>${ esc( r.category.name ) }</span><i class="sw-progress" aria-hidden="true"></i></button>` ).join( '' ) }
		</div>
		<div class="sw-stage" aria-live="polite"></div>`;
	const stage = root.querySelector( '.sw-stage' );

	function item( t, orientation, i, r ) {
		return `<a class="sw-item lvl-${ esc( t.level ) } ${ orientation === 'landscape' ? 'is-land' : '' }" href="${ esc( galleryUrl( t ) ) }" data-t="${ t.id }" data-o="${ orientation }" data-i="${ i }" title="${ esc( t.name + ' · ' + ( D.levels[ t.level ] || t.level ) ) }">
			<canvas aria-label="${ esc( sprintf( __( '%1$s ID card design: %2$s', 'pikacart' ), r.category.name, t.name ) ) }"></canvas>${ badge( t.level ) }
		</a>`;
	}

	function build( i ) {
		if ( built[ i ] ) {
			return built[ i ];
		}
		const r = rows[ i ];
		const half = Math.ceil( r.templates.length / 2 );
		const a = r.templates.slice( 0, half );
		const b = r.templates.slice( half );
		const panel = document.createElement( 'div' );
		panel.className = 'sw-panel';
		panel.dataset.i = i;
		panel.hidden = true;
		panel.setAttribute( 'role', 'tabpanel' );
		const row = ( list, orient, cls ) => `<div class="sw-row ${ cls }"><div class="sw-track" style="--n:${ list.length }">${ list.map( ( t, k ) => item( t, orient, k, r ) ).join( '' ) }${ list.map( ( t, k ) => item( t, orient, k, r ) ).join( '' ).replace( /<a /g, '<a aria-hidden="true" tabindex="-1" data-clone="1" ' ) }</div></div>`;
		panel.innerHTML = `${ row( a, 'portrait', '' ) }${ b.length ? row( b, 'landscape', 'sw-row-rev' ) : '' }
			<div class="sw-foot">
				<p><strong>${ esc( sprintf( __( '%1$d designs for %2$s', 'pikacart' ), r.count || r.templates.length, r.category.name ) ) }</strong> · ${ esc( r.subtypes.map( ( s ) => s.name ).join( ', ' ) ) }</p>
				<div class="sw-foot-actions"><a class="sw-link" href="${ esc( r.url ) }">${ esc( sprintf( __( 'Explore all %s designs', 'pikacart' ), r.category.name ) ) } →</a><a class="sw-cta" href="${ esc( D.register ) }">${ esc( __( 'Start free', 'pikacart' ) ) }</a></div>
			</div>`;
		stage.appendChild( panel );

		// Draw each card once; the looping copy reuses the pixels.
		const jobs = [];
		panel.querySelectorAll( '.sw-item:not([data-clone])' ).forEach( ( el ) => {
			const t = r.templates.find( ( x ) => String( x.id ) === el.dataset.t );
			const k = Number( el.dataset.i );
			const pals = t.palettes || [];
			const pal = palette( pals[ k % Math.max( 1, pals.length ) ] );
			jobs.push( draw( el.querySelector( 'canvas' ), { template: t, orientation: el.dataset.o, pal, width: el.dataset.o === 'landscape' ? 230 : 150, index: t.id + k } ).then( ( c ) => {
				panel.querySelectorAll( `.sw-item[data-clone][data-t="${ t.id }"][data-o="${ el.dataset.o }"] canvas` ).forEach( ( clone ) => copyCanvas( c, clone ) );
			} ) );
		} );
		built[ i ] = Promise.all( jobs ).then( () => panel.classList.add( 'is-ready' ) );
		return built[ i ];
	}

	function show( i ) {
		active = ( i + rows.length ) % rows.length;
		root.querySelectorAll( '.sw-tab' ).forEach( ( b, k ) => {
			b.classList.toggle( 'is-on', k === active );
			b.setAttribute( 'aria-selected', k === active ? 'true' : 'false' );
			const bar = b.querySelector( '.sw-progress' );
			bar.style.animation = 'none';
			void bar.offsetWidth;
			bar.style.animation = k === active && ! userPicked && ! reduceMotion ? `sw-progress ${ ROTATE }ms linear forwards` : 'none';
		} );
		build( active ).then( () => build( ( active + 1 ) % rows.length ) );
		stage.querySelectorAll( '.sw-panel' ).forEach( ( p ) => {
			p.hidden = Number( p.dataset.i ) !== active;
		} );
		const tab = root.querySelector( `.sw-tab[data-i="${ active }"]` );
		// Scroll only the tab strip sideways, never the page.
		const strip = root.querySelector( '.sw-tabs' );
		if ( strip.scrollWidth > strip.clientWidth ) {
			strip.scrollTo( { left: tab.offsetLeft - ( strip.clientWidth - tab.offsetWidth ) / 2, behavior: reduceMotion ? 'auto' : 'smooth' } );
		}
		schedule();
	}

	function schedule() {
		clearTimeout( timer );
		if ( userPicked || reduceMotion ) {
			return;
		}
		timer = setTimeout( () => {
			if ( paused || document.hidden ) {
				schedule();
				return;
			}
			show( active + 1 );
		}, ROTATE );
	}

	root.querySelector( '.sw-tabs' ).addEventListener( 'click', ( e ) => {
		const b = e.target.closest( '.sw-tab' );
		if ( b ) {
			userPicked = true;
			show( Number( b.dataset.i ) );
		}
	} );
	root.querySelector( '.sw-tabs' ).addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'ArrowRight' || e.key === 'ArrowLeft' ) {
			e.preventDefault();
			userPicked = true;
			show( active + ( e.key === 'ArrowRight' ? 1 : -1 ) );
			root.querySelector( '.sw-tab.is-on' ).focus();
		}
	} );
	stage.addEventListener( 'mouseenter', () => {
		paused = true;
	} );
	stage.addEventListener( 'mouseleave', () => {
		paused = false;
	} );

	// Start when the section comes near the screen.
	const start = () => show( 0 );
	if ( typeof IntersectionObserver !== 'undefined' ) {
		const o = new IntersectionObserver( ( en ) => {
			if ( en.some( ( x ) => x.isIntersecting ) ) {
				o.disconnect();
				start();
			}
		}, { rootMargin: '300px' } );
		o.observe( root );
	} else {
		start();
	}
}

/* ---------- Hero fan and category cards ---------- */

function firstPremium( row ) {
	return row.templates.find( ( t ) => t.level === 'premium' ) || row.templates[ 0 ];
}

function heroFan( root ) {
	const rows = ( D.showcase || [] ).filter( ( r ) => r.templates.length );
	if ( ! rows.length ) {
		return;
	}
	const slots = root.querySelectorAll( '.sc' );
	const picks = [ rows[ 0 ], rows[ 1 ] || rows[ 0 ], rows[ 3 ] || rows[ 2 ] || rows[ 0 ] ];
	slots.forEach( ( slot, i ) => {
		const row = picks[ i ];
		if ( ! row ) {
			return;
		}
		const t = row.templates.filter( ( x ) => x.level === 'premium' )[ i % 2 ] || firstPremium( row );
		const c = document.createElement( 'canvas' );
		c.className = 'hero-card-canvas ' + slot.className.replace( /\bsc\b/, '' ).trim();
		c.setAttribute( 'aria-hidden', 'true' );
		draw( c, { template: t, orientation: 'portrait', width: 260, index: i * 3, priority: true } ).then( () => {
			slot.replaceWith( c );
			requestAnimationFrame( () => c.classList.add( 'is-in' ) );
		} );
	} );
}

function catCards() {
	const map = {};
	( D.showcase || [] ).forEach( ( r ) => {
		map[ r.category.slug ] = r;
	} );
	document.querySelectorAll( '[data-cat-card]' ).forEach( ( c, i ) => {
		const row = map[ c.dataset.catCard ];
		if ( ! row || ! row.templates.length ) {
			return;
		}
		const t = firstPremium( row );
		lazy( c, { template: t, orientation: 'portrait', width: 150, index: i * 5 } ).then( () => {
			const fallback = c.parentElement.querySelector( '.sc' );
			if ( fallback ) {
				fallback.remove();
			}
		} );
	} );
}

/* ---------- Gallery pages ---------- */

function gallery() {
	const grid = document.querySelector( '[data-grid]' );
	const bar = document.querySelector( '[data-filters]' );
	if ( ! grid || ! bar ) {
		return;
	}
	const st = { orientation: 'portrait', level: '', pal: 0 };
	const all = D.templates || [];
	const levels = D.levels || {};

	bar.innerHTML = `
		<div class="pg-seg" role="group" aria-label="${ esc( __( 'Orientation', 'pikacart' ) ) }">
			<button type="button" class="is-on" data-o="portrait">${ esc( __( 'Portrait', 'pikacart' ) ) }</button>
			<button type="button" data-o="landscape">${ esc( __( 'Landscape', 'pikacart' ) ) }</button>
		</div>
		<div class="pg-chips" role="group" aria-label="${ esc( __( 'Style level', 'pikacart' ) ) }">
			<button type="button" class="is-on" data-l="">${ esc( __( 'All styles', 'pikacart' ) ) }</button>
			${ Object.entries( levels ).map( ( [ k, v ] ) => `<button type="button" data-l="${ esc( k ) }">${ k === 'premium' ? '★ ' : '' }${ esc( v ) }</button>` ).join( '' ) }
		</div>
		<div class="pg-swatches" role="group" aria-label="${ esc( __( 'Colour', 'pikacart' ) ) }">
			<button type="button" class="pg-sw pg-sw-auto is-on" data-p="0" title="${ esc( __( 'Original colours', 'pikacart' ) ) }"><span>${ esc( __( 'Original', 'pikacart' ) ) }</span></button>
			${ ( D.palettes || [] ).map( ( p ) => `<button type="button" class="pg-sw" data-p="${ p.id }" title="${ esc( p.name ) }" style="--a:${ esc( p.p ) };--b:${ esc( p.a ) }"></button>` ).join( '' ) }
		</div>
		<span class="pg-count" data-count></span>`;

	function render() {
		const list = all.filter( ( t ) => ! st.level || t.level === st.level );
		bar.querySelector( '[data-count]' ).textContent = sprintf( __( '%d designs', 'pikacart' ), list.length );
		grid.classList.toggle( 'is-land', st.orientation === 'landscape' );
		grid.innerHTML = list.map( ( t, i ) => `<article class="pg-item lvl-${ esc( t.level ) }" id="t-${ t.id }">
			<button type="button" class="pg-thumb" data-open="${ t.id }" aria-label="${ esc( sprintf( __( 'Preview %s', 'pikacart' ), t.name ) ) }">
				<canvas class="pg-back" data-side="back" aria-hidden="true"></canvas>
				<canvas class="pg-front" data-side="front" aria-hidden="true"></canvas>
				${ badge( t.level ) }
			</button>
			<div class="pg-meta"><strong>${ esc( t.name ) }</strong><span>${ esc( levels[ t.level ] || '' ) }</span></div>
			<button type="button" class="pg-use" data-use="${ t.id }">${ esc( __( 'Use this design', 'pikacart' ) ) }</button>
		</article>` ).join( '' ) || `<p class="pg-empty">${ esc( __( 'No designs match. Try another style.', 'pikacart' ) ) }</p>`;
		list.forEach( ( t, i ) => {
			const item = grid.querySelector( `#t-${ t.id }` );
			const pal = st.pal ? palette( st.pal ) : null;
			const w = st.orientation === 'landscape' ? 250 : 170;
			lazy( item.querySelector( '.pg-front' ), { template: t, orientation: st.orientation, pal, width: w, index: i } ).then( () => lazy( item.querySelector( '.pg-back' ), { template: t, orientation: st.orientation, pal, width: w * 0.8, index: i, side: 'back' } ) );
		} );
	}

	bar.addEventListener( 'click', ( e ) => {
		const b = e.target.closest( 'button' );
		if ( ! b ) {
			return;
		}
		if ( b.dataset.o ) {
			st.orientation = b.dataset.o;
		} else if ( b.dataset.l !== undefined ) {
			st.level = b.dataset.l;
		} else if ( b.dataset.p !== undefined ) {
			st.pal = Number( b.dataset.p );
		} else {
			return;
		}
		b.parentElement.querySelectorAll( 'button' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === b ) );
		render();
	} );

	grid.addEventListener( 'click', ( e ) => {
		const open = e.target.closest( '[data-open]' );
		const use = e.target.closest( '[data-use]' );
		if ( open ) {
			preview( all.find( ( t ) => String( t.id ) === open.dataset.open ) );
		} else if ( use ) {
			const t = all.find( ( x ) => String( x.id ) === use.dataset.use );
			useDesign( t, st.orientation, st.pal ? palette( st.pal ) : palette( ( t.palettes || [] )[ 0 ] ) );
		}
	} );

	function preview( t ) {
		if ( ! t ) {
			return;
		}
		let orient = st.orientation;
		let pal = st.pal ? palette( st.pal ) : palette( ( t.palettes || [] )[ 0 ] );
		const root = document.getElementById( 'pkc-modal-root' ) || document.body;
		const wrap = document.createElement( 'div' );
		wrap.className = 'pg-modal';
		const own = ( t.palettes || [] ).map( palette ).filter( Boolean );
		const others = ( D.palettes || [] ).filter( ( p ) => ! ( t.palettes || [] ).includes( p.id ) );
		wrap.innerHTML = `<div class="pg-dialog" role="dialog" aria-modal="true" aria-label="${ esc( t.name ) }">
			<button type="button" class="pg-x" data-close aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">×</button>
			<div class="pg-dialog-head"><h2>${ esc( t.name ) }</h2>${ badge( t.level ) }</div>
			<div class="pg-big"><canvas data-side="front"></canvas><canvas data-side="back"></canvas></div>
			<div class="pg-dialog-tools">
				<div class="pg-seg"><button type="button" data-o="portrait" class="${ orient === 'portrait' ? 'is-on' : '' }">${ esc( __( 'Portrait', 'pikacart' ) ) }</button><button type="button" data-o="landscape" class="${ orient === 'landscape' ? 'is-on' : '' }">${ esc( __( 'Landscape', 'pikacart' ) ) }</button></div>
				<div class="pg-swatches">${ [ ...own, ...others ].map( ( p ) => `<button type="button" class="pg-sw ${ p.id === pal.id ? 'is-on' : '' }" data-p="${ p.id }" title="${ esc( p.name ) }" style="--a:${ esc( p.p ) };--b:${ esc( p.a ) }"></button>` ).join( '' ) }</div>
			</div>
			<div class="pg-dialog-foot">
				<p>${ esc( __( 'Your logo, signature and people are added in the next step. Sizes from CR80 PVC to 4 × 6 inch.', 'pikacart' ) ) }</p>
				<button type="button" class="pkc-btn pkc-btn-primary" data-go>${ esc( __( 'Use this design', 'pikacart' ) ) } →</button>
			</div>
		</div>`;
		root.appendChild( wrap );
		document.body.classList.add( 'pg-lock' );
		const paint = () => wrap.querySelectorAll( '.pg-big canvas' ).forEach( ( c, i ) => draw( c, { template: t, orientation: orient, pal, width: orient === 'landscape' ? 420 : 300, index: 2, side: c.dataset.side, priority: true } ) );
		wrap.querySelector( '.pg-big' ).classList.toggle( 'is-land', orient === 'landscape' );
		paint();
		const close = () => {
			wrap.remove();
			document.body.classList.remove( 'pg-lock' );
			document.removeEventListener( 'keydown', onKey );
			if ( window.location.hash ) {
				history.replaceState( null, '', window.location.pathname );
			}
		};
		const onKey = ( e ) => e.key === 'Escape' && close();
		document.addEventListener( 'keydown', onKey );
		wrap.addEventListener( 'click', ( e ) => {
			if ( e.target === wrap || e.target.closest( '[data-close]' ) ) {
				close();
				return;
			}
			const o = e.target.closest( '[data-o]' );
			const p = e.target.closest( '[data-p]' );
			if ( o ) {
				orient = o.dataset.o;
				o.parentElement.querySelectorAll( 'button' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === o ) );
				wrap.querySelector( '.pg-big' ).classList.toggle( 'is-land', orient === 'landscape' );
				paint();
			} else if ( p ) {
				pal = palette( Number( p.dataset.p ) );
				p.parentElement.querySelectorAll( 'button' ).forEach( ( x ) => x.classList.toggle( 'is-on', x === p ) );
				paint();
			} else if ( e.target.closest( '[data-go]' ) ) {
				useDesign( t, orient, pal );
			}
		} );
		wrap.querySelector( '[data-go]' ).focus();
	}

	render();
	const m = window.location.hash.match( /^#t-(\d+)$/ );
	if ( m ) {
		preview( all.find( ( t ) => String( t.id ) === m[ 1 ] ) );
	}
}

/* ---------- Start ---------- */

const sw = document.querySelector( '[data-pkc-showcase]' );
if ( sw ) {
	showcase( sw );
}
const hero = document.querySelector( '[data-pkc-hero]' );
if ( hero ) {
	heroFan( hero );
}
catCards();
if ( D.mode === 'gallery' ) {
	gallery();
}
