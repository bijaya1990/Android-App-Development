/**
 * Super Admin template manager: grid with live thumbnails, filters, publish,
 * feature, duplicate, delete, import and export JSON.
 */

import { api } from '../app/api.js';
import { esc, toast, confirmDialog } from '../app/ui.js';
import { thumb } from '../card/gallery.js';

const { __, _n, sprintf } = window.wp.i18n;
const cfg = window.PKC;
const cat = cfg.catalog;

const root = document.querySelector( '.pkc-admin' );
const $ = ( s ) => root.querySelector( s );
const st = { page: 1, category: '', subtype: '', level: '', status: '', source: '', featured: '', q: '' };
let rows = [];

function subtypeOf( id ) {
	for ( const c of cat.tree ) {
		const s = c.subtypes.find( ( x ) => x.id === id );
		if ( s ) {
			return s;
		}
	}
	return null;
}

function paletteOf( t ) {
	const id = ( t.palettes || [] )[ 0 ];
	return cat.palettes.find( ( p ) => p.id === id ) || cat.palettes[ 0 ];
}

function orientationOf( t ) {
	const l = t.layout || {};
	if ( l.meta && l.meta.orientation ) {
		return l.meta.orientation;
	}
	return ! l.recipe && ! l.portrait && l.landscape ? 'landscape' : 'portrait';
}

function filtersHTML() {
	const subs = st.category ? ( cat.tree.find( ( c ) => String( c.id ) === String( st.category ) ) || { subtypes: [] } ).subtypes : [];
	return `<input type="search" data-f="q" value="${ esc( st.q ) }" placeholder="${ esc( __( 'Search by name', 'pikacart' ) ) }">
		<select data-f="category"><option value="">${ esc( __( 'All categories', 'pikacart' ) ) }</option>${ cat.tree.map( ( c ) => `<option value="${ c.id }" ${ String( c.id ) === String( st.category ) ? 'selected' : '' }>${ esc( c.name ) }</option>` ).join( '' ) }</select>
		<select data-f="subtype" ${ st.category ? '' : 'disabled' }><option value="">${ esc( __( 'All sub-types', 'pikacart' ) ) }</option>${ subs.map( ( s ) => `<option value="${ s.id }" ${ String( s.id ) === String( st.subtype ) ? 'selected' : '' }>${ esc( s.name ) }</option>` ).join( '' ) }</select>
		<select data-f="level"><option value="">${ esc( __( 'All style levels', 'pikacart' ) ) }</option>${ Object.entries( cat.levels ).map( ( [ k, v ] ) => `<option value="${ k }" ${ k === st.level ? 'selected' : '' }>${ esc( v ) }</option>` ).join( '' ) }</select>
		<select data-f="status"><option value="">${ esc( __( 'Published and drafts', 'pikacart' ) ) }</option><option value="published" ${ st.status === 'published' ? 'selected' : '' }>${ esc( __( 'Published', 'pikacart' ) ) }</option><option value="draft" ${ st.status === 'draft' ? 'selected' : '' }>${ esc( __( 'Drafts', 'pikacart' ) ) }</option></select>
		<select data-f="source"><option value="">${ esc( __( 'Built-in and custom', 'pikacart' ) ) }</option><option value="builtin" ${ st.source === 'builtin' ? 'selected' : '' }>${ esc( __( 'Built-in', 'pikacart' ) ) }</option><option value="custom" ${ st.source === 'custom' ? 'selected' : '' }>${ esc( __( 'Custom and imported', 'pikacart' ) ) }</option></select>
		<label class="pkc-check"><input type="checkbox" data-f="featured" ${ st.featured ? 'checked' : '' }> ${ esc( __( 'Featured only', 'pikacart' ) ) }</label>`;
}

function card( t ) {
	const levels = cat.levels;
	return `<article class="pkc-tpl" data-id="${ t.id }">
		<div class="pkc-tpl-thumb"><canvas aria-label="${ esc( t.name ) }"></canvas>${ t.featured ? `<span class="pkc-tpl-star" title="${ esc( __( 'Featured', 'pikacart' ) ) }">★</span>` : '' }</div>
		<div class="pkc-tpl-body">
			<strong class="pkc-tpl-name">${ esc( t.name ) }</strong>
			<span class="pkc-muted pkc-small">${ esc( [ t.category && t.category.name, t.subtype && t.subtype.name ].filter( Boolean ).join( ' · ' ) ) }</span>
			<div class="pkc-tpl-badges">
				<span class="pkc-badge ${ t.status === 'published' ? 'pkc-badge-active' : 'pkc-badge-cancelled' }">${ esc( t.status === 'published' ? __( 'Published', 'pikacart' ) : __( 'Draft', 'pikacart' ) ) }</span>
				<span class="pkc-badge">${ esc( levels[ t.level ] || t.level ) }</span>
				${ t.source === 'builtin' ? `<span class="pkc-badge">${ esc( __( 'Built-in', 'pikacart' ) ) }</span>` : '' }
				${ t.used ? `<span class="pkc-badge" title="${ esc( __( 'Projects using it', 'pikacart' ) ) }">${ esc( sprintf( _n( '%d project', '%d projects', t.used, 'pikacart' ), t.used ) ) }</span>` : '' }
			</div>
		</div>
		<div class="pkc-tpl-actions">
			<a class="pkc-btn pkc-btn-primary" href="${ esc( cfg.builder.replace( 'id=0', 'id=' + t.id ) ) }">${ esc( __( 'Edit', 'pikacart' ) ) }</a>
			<button type="button" class="pkc-btn" data-act="publish">${ esc( t.status === 'published' ? __( 'Unpublish', 'pikacart' ) : __( 'Publish', 'pikacart' ) ) }</button>
			<button type="button" class="pkc-btn" data-act="feature">${ esc( t.featured ? __( 'Unfeature', 'pikacart' ) : __( 'Feature', 'pikacart' ) ) }</button>
			<button type="button" class="pkc-btn" data-act="duplicate">${ esc( __( 'Duplicate', 'pikacart' ) ) }</button>
			<button type="button" class="pkc-btn" data-act="export">${ esc( __( 'Export', 'pikacart' ) ) }</button>
			<button type="button" class="pkc-btn pkc-btn-danger" data-act="delete">${ esc( __( 'Delete', 'pikacart' ) ) }</button>
		</div>
	</article>`;
}

async function load() {
	const q = new URLSearchParams();
	Object.entries( st ).forEach( ( [ k, v ] ) => v && q.set( k, v ) );
	$( '[data-grid]' ).innerHTML = `<p class="pkc-muted">${ esc( __( 'Loading designs…', 'pikacart' ) ) }</p>`;
	try {
		const res = await api( 'admin/templates?' + q );
		rows = res.templates;
		$( '[data-grid]' ).innerHTML = rows.length ? rows.map( card ).join( '' ) : `<div class="pkc-empty">${ esc( __( 'No designs match these filters.', 'pikacart' ) ) }</div>`;
		rows.forEach( ( t ) => {
			const c = $( `.pkc-tpl[data-id="${ t.id }"] canvas` );
			thumb( c, { template: t, subtype: subtypeOf( t.subtype_id ), orientation: orientationOf( t ), palette: paletteOf( t ), width: 170, index: t.id } );
		} );
		$( '[data-pager]' ).innerHTML = res.pages > 1
			? `<span class="pkc-muted">${ esc( sprintf( __( '%1$d designs · page %2$d of %3$d', 'pikacart' ), res.total, res.page, res.pages ) ) }</span>
				<button type="button" class="pkc-btn" data-page="${ res.page - 1 }" ${ res.page <= 1 ? 'disabled' : '' }>‹ ${ esc( __( 'Previous', 'pikacart' ) ) }</button>
				<button type="button" class="pkc-btn" data-page="${ res.page + 1 }" ${ res.page >= res.pages ? 'disabled' : '' }>${ esc( __( 'Next', 'pikacart' ) ) } ›</button>`
			: `<span class="pkc-muted">${ esc( sprintf( _n( '%d design', '%d designs', res.total, 'pikacart' ), res.total ) ) }</span>`;
	} catch ( e ) {
		$( '[data-grid]' ).innerHTML = `<div class="pkc-empty">${ esc( e.message ) }</div>`;
	}
}

function download( name, data ) {
	const a = document.createElement( 'a' );
	a.href = URL.createObjectURL( new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } ) );
	a.download = name;
	document.body.appendChild( a );
	a.click();
	setTimeout( () => {
		URL.revokeObjectURL( a.href );
		a.remove();
	}, 1000 );
}

function exportData( t ) {
	return {
		pikacart_template: 1,
		name: t.name,
		level: t.level,
		category: t.category,
		subtype: t.subtype,
		palettes: t.palettes,
		layout: t.layout,
	};
}

$( '[data-filters]' ).innerHTML = filtersHTML();
let timer = 0;
$( '[data-filters]' ).addEventListener( 'input', ( e ) => {
	const k = e.target.dataset.f;
	if ( ! k ) {
		return;
	}
	st[ k ] = e.target.type === 'checkbox' ? ( e.target.checked ? '1' : '' ) : e.target.value;
	if ( k === 'category' ) {
		st.subtype = '';
		$( '[data-filters]' ).innerHTML = filtersHTML();
	}
	st.page = 1;
	clearTimeout( timer );
	timer = setTimeout( load, k === 'q' ? 350 : 0 );
} );

$( '[data-pager]' ).addEventListener( 'click', ( e ) => {
	const b = e.target.closest( '[data-page]' );
	if ( b && ! b.disabled ) {
		st.page = Number( b.dataset.page );
		load();
		window.scrollTo( { top: 0, behavior: 'smooth' } );
	}
} );

$( '[data-grid]' ).addEventListener( 'click', async ( e ) => {
	const b = e.target.closest( '[data-act]' );
	if ( ! b ) {
		return;
	}
	const id = Number( b.closest( '[data-id]' ).dataset.id );
	const t = rows.find( ( r ) => r.id === id );
	const act = b.dataset.act;
	try {
		if ( act === 'publish' ) {
			await api( `admin/templates/${ id }`, { method: 'POST', body: { status: t.status === 'published' ? 'draft' : 'published' } } );
			toast( t.status === 'published' ? __( 'Design unpublished. Customers no longer see it.', 'pikacart' ) : __( 'Design published.', 'pikacart' ) );
		} else if ( act === 'feature' ) {
			await api( `admin/templates/${ id }`, { method: 'POST', body: { featured: ! t.featured } } );
			toast( t.featured ? __( 'Removed from featured.', 'pikacart' ) : __( 'Marked as featured. It now appears first and on the homepage.', 'pikacart' ) );
		} else if ( act === 'duplicate' ) {
			const res = await api( `admin/templates/${ id }/duplicate`, { method: 'POST' } );
			toast( __( 'Copy created as a draft.', 'pikacart' ) );
			window.location.href = cfg.builder.replace( 'id=0', 'id=' + res.template.id );
			return;
		} else if ( act === 'export' ) {
			download( `pikacart-template-${ t.slug || t.id }.json`, exportData( t ) );
			return;
		} else if ( act === 'delete' ) {
			if ( ! ( await confirmDialog( { title: sprintf( __( 'Delete "%s"?', 'pikacart' ), t.name ), message: __( 'This cannot be undone. Export it first if you may need it again.', 'pikacart' ), confirm: __( 'Delete', 'pikacart' ), danger: true } ) ) ) {
				return;
			}
			const res = await api( `admin/templates/${ id }/delete`, { method: 'POST' } );
			toast( res.message );
		}
		load();
	} catch ( err ) {
		toast( err.message, 'error' );
	}
} );

document.querySelector( '[data-new]' ).addEventListener( 'click', async () => {
	try {
		const res = await api( 'admin/templates', { method: 'POST', body: { subtype_id: Number( st.subtype ) || undefined } } );
		window.location.href = cfg.builder.replace( 'id=0', 'id=' + res.template.id );
	} catch ( err ) {
		toast( err.message, 'error' );
	}
} );

const file = $( '[data-import-file]' );
document.querySelector( '[data-import]' ).addEventListener( 'click', () => file.click() );
file.addEventListener( 'change', async () => {
	const f = file.files[ 0 ];
	file.value = '';
	if ( ! f ) {
		return;
	}
	try {
		const data = JSON.parse( await f.text() );
		const res = await api( 'admin/templates', { method: 'POST', body: { import: data } } );
		toast( __( 'Template imported as a draft.', 'pikacart' ) );
		window.location.href = cfg.builder.replace( 'id=0', 'id=' + res.template.id );
	} catch ( err ) {
		toast( err.message || __( 'This file is not a Pikacart template.', 'pikacart' ), 'error' );
	}
} );

load();
