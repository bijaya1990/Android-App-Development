/**
 * People step / Members screen: the heart of the Admin panel.
 * Search, filters, sorting, pagination, select all, bulk delete and status,
 * add/edit with photo crop, Excel/CSV import with column mapping and error
 * report, sample file, export, bulk photos matched by ID number, self-fill
 * link with approvals, recycle bin, and row actions (preview, edit, download,
 * print, duplicate, delete).
 */

import { api } from './api.js';
import { esc, icon, toast, confirmDialog, withLoading, emptyState, formData } from './ui.js';
import { cropPhoto, compressPhoto } from './image.js';
import { renderSide } from '../card/render.js';
import { exportCards, printCards } from './exporter.js';
import { sheetImages, photoFiles, pickPhoto as findPhoto } from './xlsx-photos.js';

const { __, _n, sprintf } = window.wp.i18n;

const PER_PAGE = 25;

const LABELS = () => ( {
	name: __( 'Full name', 'pikacart' ),
	id_no: __( 'Roll / ID number', 'pikacart' ),
	class: __( 'Class / Course', 'pikacart' ),
	section: __( 'Section', 'pikacart' ),
	designation: __( 'Designation', 'pikacart' ),
	department: __( 'Department', 'pikacart' ),
	mobile: __( 'Mobile', 'pikacart' ),
	email: __( 'Email', 'pikacart' ),
	dob: __( 'Date of birth', 'pikacart' ),
	blood_group: __( 'Blood group', 'pikacart' ),
	guardian: __( 'Father / guardian name', 'pikacart' ),
	address: __( 'Address', 'pikacart' ),
	emergency: __( 'Emergency contact', 'pikacart' ),
	valid_from: __( 'Valid from', 'pikacart' ),
	valid_until: __( 'Valid until', 'pikacart' ),
	session: __( 'Session', 'pikacart' ),
} );

const STATUS = () => ( {
	draft: __( 'Draft', 'pikacart' ),
	ready: __( 'Ready', 'pikacart' ),
	printed: __( 'Printed', 'pikacart' ),
	expired: __( 'Expired', 'pikacart' ),
	cancelled: __( 'Cancelled', 'pikacart' ),
	pending: __( 'Pending', 'pikacart' ),
} );

/** Fields used by this project, in a sensible order. */
export function projectFields( P ) {
	const labels = LABELS();
	const on = new Set( P.project.fields.on || [] );
	const sub = ( P.data.subtype ? P.data.subtype.fields : [] ).filter( ( f ) => f.key !== 'photo' );
	const out = [];
	sub.forEach( ( f ) => {
		if ( f.key === 'name' || f.key === 'id_no' || on.has( f.key ) ) {
			out.push( { key: f.key, label: f.label || labels[ f.key ] || f.key, required: !! f.required } );
		}
	} );
	( P.project.fields.custom || [] ).forEach( ( c ) => {
		if ( on.has( c.key ) ) {
			out.push( { key: c.key, label: c.label, required: false } );
		}
	} );
	return out;
}

const HINTS = () => ( {
	name: [ __( 'Full name as it should print on the card', 'pikacart' ), 'Aarav Mehta' ],
	id_no: [ __( 'Unique number for each person (roll no., employee ID…). Also the photo file name.', 'pikacart' ), '1001' ],
	photo: [ __( 'Paste the photo into this cell, or type the photo file name. Leave empty to match a photo named by the ID number.', 'pikacart' ), '1001.jpg' ],
	class: [ __( 'Class or course', 'pikacart' ), 'VIII' ],
	section: [ __( 'Section or division', 'pikacart' ), 'A' ],
	designation: [ __( 'Designation or pass type', 'pikacart' ), 'Teacher' ],
	department: [ __( 'Department or area', 'pikacart' ), 'Science' ],
	mobile: [ __( '10-digit mobile number', 'pikacart' ), '9876543210' ],
	email: [ __( 'Email address', 'pikacart' ), 'aarav@example.com' ],
	dob: [ __( 'Date of birth as DD-MM-YYYY', 'pikacart' ), '12-05-2012' ],
	blood_group: [ __( 'Blood group', 'pikacart' ), 'B+' ],
	guardian: [ __( 'Father or guardian name', 'pikacart' ), 'Mr. Rakesh Mehta' ],
	address: [ __( 'Home address', 'pikacart' ), '12 MG Road, Bhubaneswar' ],
	emergency: [ __( 'Emergency contact number', 'pikacart' ), '9876500000' ],
	valid_from: [ __( 'Card valid from, DD-MM-YYYY', 'pikacart' ), '01-04-2026' ],
	valid_until: [ __( 'Card valid until, DD-MM-YYYY', 'pikacart' ), '31-03-2027' ],
} );

const TEXT_KEYS = [ 'id_no', 'mobile', 'emergency', 'dob', 'valid_from', 'valid_until' ];

/**
 * A blank Excel made for this project's design: one column per field the
 * design uses (required ones marked *), a "How to fill" sheet, and a hidden
 * sheet that lets the import match every column automatically.
 */
export function blankExcel( P ) {
	const X = window.XLSX;
	const photoField = ( P.data.subtype ? P.data.subtype.fields : [] ).find( ( f ) => f.key === 'photo' );
	const fields = [ ...projectFields( P ), { key: 'photo', label: __( 'Photo', 'pikacart' ), required: !! ( photoField && photoField.required ) } ];
	const head = fields.map( ( f ) => f.label + ( f.required ? ' *' : '' ) );
	const ws = X.utils.aoa_to_sheet( [ head ] );
	// Number-like columns are text, so IDs like 0012 keep their zeros.
	const ROWS = 1000;
	fields.forEach( ( f, c ) => {
		if ( TEXT_KEYS.includes( f.key ) ) {
			for ( let r = 1; r <= ROWS; r++ ) {
				ws[ X.utils.encode_cell( { r, c } ) ] = { t: 's', v: '', z: '@' };
			}
		}
	} );
	ws[ '!ref' ] = X.utils.encode_range( { s: { r: 0, c: 0 }, e: { r: ROWS, c: Math.max( 0, fields.length - 1 ) } } );
	ws[ '!cols' ] = fields.map( ( f, i ) => ( { wch: f.key === 'photo' ? 16 : Math.max( 14, head[ i ].length + 4 ) } ) );
	// Taller rows so a pasted photo fits in its cell.
	ws[ '!rows' ] = [ { hpt: 22 }, ...Array.from( { length: 300 }, () => ( { hpt: 60 } ) ) ];

	const hints = HINTS();
	const site = window.PKC.site || 'Pikacart';
	const help = X.utils.aoa_to_sheet( [
		[ sprintf( __( '%1$s · %2$s', 'pikacart' ), site, P.project.name ) ],
		[ __( 'Fill one person per row on the "People" sheet, then upload this same file in People → Import Excel / CSV. Do not change or move the headings.', 'pikacart' ) ],
		[ __( 'Columns marked * must be filled. Organisation details (name, logo, address, signature) come from your profile and are not needed here.', 'pikacart' ) ],
		[ __( 'Photo: paste the picture into the Photo cell of that person (Insert → Pictures → Place in Cell, or a picture placed on the cell). Or type the photo file name (e.g. aarav.jpg) and choose the photos or a ZIP when you upload. Or leave it empty and name each photo by the ID number (1001.jpg).', 'pikacart' ) ],
		[],
		[ __( 'Column', 'pikacart' ), __( 'Required', 'pikacart' ), __( 'What to write', 'pikacart' ), __( 'Example', 'pikacart' ) ],
		...fields.map( ( f ) => [ f.label, f.required ? __( 'Yes', 'pikacart' ) : __( 'No', 'pikacart' ), ( hints[ f.key ] || [ __( 'Your own field', 'pikacart' ) ] )[ 0 ], ( hints[ f.key ] || [ '', '' ] )[ 1 ] ] ),
	] );
	help[ '!cols' ] = [ { wch: 26 }, { wch: 10 }, { wch: 70 }, { wch: 24 } ];
	const meta = X.utils.aoa_to_sheet( [ [ 'pikacart', 1 ], [ 'project', P.project.id ], [ 'keys', ...fields.map( ( f ) => f.key ) ] ] );

	const wb = X.utils.book_new();
	X.utils.book_append_sheet( wb, ws, 'People' );
	X.utils.book_append_sheet( wb, help, 'How to fill' );
	X.utils.book_append_sheet( wb, meta, '_pikacart' );
	wb.Workbook = { Sheets: [ { Hidden: 0 }, { Hidden: 0 }, { Hidden: 1 } ] };
	const name = ( P.project.name || 'people' ).replace( /[^\w\- ]+/g, '' ).trim().replace( /\s+/g, '-' ) || 'people';
	X.writeFile( wb, `${ name }-blank.xlsx` );
}

/** Column keys written by blankExcel(), or null for any other file. */
function pikacartKeys( wb ) {
	const meta = wb.Sheets._pikacart;
	if ( ! meta ) {
		return null;
	}
	const rows = window.XLSX.utils.sheet_to_json( meta, { header: 1, defval: '' } );
	const keys = ( rows.find( ( r ) => r[ 0 ] === 'keys' ) || [] ).slice( 1 ).map( String );
	return keys.length ? keys : null;
}

/** Width/height ratio of the design's photo frame. */
export function photoAspect( P ) {
	const size = P.size();
	const lay = P.layout();
	const ph = ( lay.front.els || [] ).find( ( e ) => e.t === 'img' && /photo/.test( e.src || '' ) );
	if ( ! ph ) {
		return 0.8;
	}
	if ( ph.shape === 'circle' ) {
		return 1;
	}
	const r = ( ph.w * size.w ) / ( ph.h * size.h );
	return r > 0.3 && r < 3 ? r : 0.8;
}

export default function peopleStep( body, P ) {
	const st = { page: 1, search: '', status: '', class: '', section: '', department: '', session: '', sort: 'created', order: 'asc', trash: false, selected: new Set(), rows: [], total: 0, facets: null, counts: {} };
	let alive = true;
	const fields = projectFields( P );
	const pid = P.project.id;

	body.innerHTML = `<div class="people">
		<div class="people-actions">
			<button type="button" class="btn btn-primary" data-do="add">${ icon( 'plus' ) }${ esc( __( 'Add person', 'pikacart' ) ) }</button>
			<button type="button" class="btn" data-do="blank">${ icon( 'download' ) }${ esc( __( 'Blank Excel for this design', 'pikacart' ) ) }</button>
			<button type="button" class="btn" data-do="import">${ icon( 'upload' ) }${ esc( __( 'Import Excel / CSV', 'pikacart' ) ) }</button>
			<label class="btn">${ icon( 'image' ) }${ esc( __( 'Bulk photos', 'pikacart' ) ) }<input type="file" accept="image/png,image/jpeg,image/webp" multiple hidden data-bulkphotos></label>
			<button type="button" class="btn" data-do="selffill">${ icon( 'external' ) }${ esc( __( 'Self-fill link', 'pikacart' ) ) }</button>
			<div class="dropdown"><button type="button" class="btn" data-do="exportmenu">${ icon( 'download' ) }${ esc( __( 'Export list', 'pikacart' ) ) }</button>
				<div class="dropdown-menu" hidden data-exportmenu><button type="button" data-export="xlsx">Excel (.xlsx)</button><button type="button" data-export="csv">CSV</button><button type="button" data-export="sample">${ esc( __( 'Blank Excel for this design', 'pikacart' ) ) }</button></div></div>
		</div>
		<div class="status-tabs" data-tabs></div>
		<div class="people-filters card">
			<input type="search" data-f="search" placeholder="${ esc( __( 'Search name, ID or any detail', 'pikacart' ) ) }" aria-label="${ esc( __( 'Search', 'pikacart' ) ) }">
			<select data-f="class" aria-label="${ esc( __( 'Class', 'pikacart' ) ) }"></select>
			<select data-f="section" aria-label="${ esc( __( 'Section', 'pikacart' ) ) }"></select>
			<select data-f="department" aria-label="${ esc( __( 'Department', 'pikacart' ) ) }"></select>
			<select data-f="session" aria-label="${ esc( __( 'Session', 'pikacart' ) ) }"></select>
			<select data-f="sort" aria-label="${ esc( __( 'Sort', 'pikacart' ) ) }">
				<option value="created">${ esc( __( 'Newest last', 'pikacart' ) ) }</option>
				<option value="name">${ esc( __( 'Name A–Z', 'pikacart' ) ) }</option>
				<option value="id_no">${ esc( __( 'ID number', 'pikacart' ) ) }</option>
				<option value="status">${ esc( __( 'Status', 'pikacart' ) ) }</option>
				<option value="updated">${ esc( __( 'Recently edited', 'pikacart' ) ) }</option>
			</select>
		</div>
		<div class="bulk-bar" data-bulk hidden>
			<span data-bulkcount></span>
			<button type="button" class="btn btn-sm" data-bulk-act="download">${ icon( 'download' ) }${ esc( __( 'Download', 'pikacart' ) ) }</button>
			<button type="button" class="btn btn-sm" data-bulk-act="print">${ icon( 'printer' ) }${ esc( __( 'Print', 'pikacart' ) ) }</button>
			<button type="button" class="btn btn-sm" data-bulk-act="approve">${ esc( __( 'Approve', 'pikacart' ) ) }</button>
			<select data-bulk-status aria-label="${ esc( __( 'Change status', 'pikacart' ) ) }"><option value="">${ esc( __( 'Set status…', 'pikacart' ) ) }</option>${ [ 'draft', 'ready', 'printed', 'cancelled' ].map( ( s ) => `<option value="${ s }">${ esc( STATUS()[ s ] ) }</option>` ).join( '' ) }</select>
			<button type="button" class="btn btn-sm btn-danger" data-bulk-act="delete">${ icon( 'trash' ) }${ esc( __( 'Delete', 'pikacart' ) ) }</button>
			<button type="button" class="btn btn-sm" data-bulk-act="restore" hidden>${ esc( __( 'Restore', 'pikacart' ) ) }</button>
		</div>
		<div class="card people-list" data-list></div>
		<div class="pager" data-pager></div>
	</div>`;

	const $ = ( s ) => body.querySelector( s );

	async function load( withFacets = false ) {
		const q = new URLSearchParams( { page: st.page, per_page: PER_PAGE, sort: st.sort, order: st.order } );
		[ 'search', 'status', 'class', 'section', 'department', 'session' ].forEach( ( k ) => st[ k ] && q.set( k, st[ k ] ) );
		if ( st.trash ) {
			q.set( 'trash', 1 );
		}
		if ( withFacets || ! st.facets ) {
			q.set( 'facets', 1 );
		}
		$( '[data-list]' ).classList.add( 'is-loading' );
		try {
			const res = await api( `projects/${ pid }/members?${ q }` );
			if ( ! alive ) {
				return;
			}
			st.rows = res.members;
			st.total = res.total;
			st.counts = res.counts;
			if ( res.facets ) {
				st.facets = res.facets;
				fillFacets();
			}
			draw();
		} catch ( e ) {
			toast( e.message, 'error' );
		} finally {
			$( '[data-list]' ).classList.remove( 'is-loading' );
		}
	}

	function fillFacets() {
		const opt = ( key, label ) => {
			const sel = $( `[data-f="${ key }"]` );
			const values = st.facets[ key ] || [];
			sel.hidden = ! values.length;
			sel.innerHTML = `<option value="">${ esc( label ) }</option>` + values.map( ( v ) => `<option ${ v === st[ key ] ? 'selected' : '' }>${ esc( v ) }</option>` ).join( '' );
		};
		opt( 'class', __( 'All classes', 'pikacart' ) );
		opt( 'section', __( 'All sections', 'pikacart' ) );
		opt( 'department', __( 'All departments', 'pikacart' ) );
		opt( 'session', __( 'All sessions', 'pikacart' ) );
	}

	function draw() {
		const S = STATUS();
		const c = st.counts;
		const all = [ 'draft', 'ready', 'printed', 'expired', 'cancelled', 'pending' ].reduce( ( n, k ) => n + ( c[ k ] || 0 ), 0 );
		$( '[data-tabs]' ).innerHTML = [ [ '', __( 'All', 'pikacart' ), all ], ...[ 'draft', 'ready', 'printed', 'expired', 'cancelled', 'pending' ].filter( ( k ) => k !== 'pending' || c.pending ).map( ( k ) => [ k, S[ k ], c[ k ] || 0 ] ), [ 'trash', __( 'Recycle bin', 'pikacart' ), c.trash || 0 ] ]
			.map( ( [ k, l, n ] ) => `<button type="button" class="stab ${ ( k === 'trash' ? st.trash : ! st.trash && st.status === k ) ? 'is-on' : '' }" data-tab="${ k }">${ esc( l ) } <span>${ n }</span></button>` ).join( '' );

		const list = $( '[data-list]' );
		if ( ! st.rows.length ) {
			list.innerHTML = st.trash
				? `<p class="muted center pad">${ esc( __( 'The recycle bin is empty. Deleted people stay here for 30 days.', 'pikacart' ) ) }</p>`
				: ( st.search || st.status ? emptyState( { art: 'people', title: __( 'Nobody matches these filters', 'pikacart' ), text: __( 'Try another search or status.', 'pikacart' ) } ) : `<div class="xl-guide">
					<h3>${ esc( __( 'Make all cards at once with Excel', 'pikacart' ) ) }</h3>
					<ol>
						<li><strong>${ esc( __( 'Download the blank Excel', 'pikacart' ) ) }</strong><span>${ esc( __( 'It already has the right column headings for this design.', 'pikacart' ) ) }</span><button type="button" class="btn btn-primary btn-sm" data-do="blank">${ icon( 'download' ) }${ esc( __( 'Blank Excel', 'pikacart' ) ) }</button></li>
						<li><strong>${ esc( __( 'Fill one person per row', 'pikacart' ) ) }</strong><span>${ esc( __( 'Just type the values. Columns marked * are required.', 'pikacart' ) ) }</span></li>
						<li><strong>${ esc( __( 'Upload it', 'pikacart' ) ) }</strong><span>${ esc( __( 'Every column is matched automatically and the cards are ready.', 'pikacart' ) ) }</span><button type="button" class="btn btn-sm" data-do="import">${ icon( 'upload' ) }${ esc( __( 'Upload Excel', 'pikacart' ) ) }</button></li>
					</ol>
					<p class="muted small">${ esc( __( 'Or add people one by one, or share a self-fill link.', 'pikacart' ) ) }</p>
				</div>` );
			$( '[data-pager]' ).innerHTML = '';
			syncBulk();
			return;
		}
		const sub = ( m ) => [ m.data.class && `${ m.data.class }${ m.data.section ? ' - ' + m.data.section : '' }`, m.data.designation, m.data.department ].filter( Boolean ).join( ' · ' );
		list.innerHTML = `<table class="ptable">
			<thead><tr>
				<th class="col-check"><input type="checkbox" data-all aria-label="${ esc( __( 'Select all on this page', 'pikacart' ) ) }"></th>
				<th>${ esc( __( 'Person', 'pikacart' ) ) }</th>
				<th>${ esc( __( 'ID number', 'pikacart' ) ) }</th>
				<th>${ esc( __( 'Status', 'pikacart' ) ) }</th>
				<th class="col-actions"><span class="sr-only">${ esc( __( 'Actions', 'pikacart' ) ) }</span></th>
			</tr></thead>
			<tbody>${ st.rows.map( ( m ) => `<tr data-mid="${ m.id }">
				<td class="col-check"><input type="checkbox" data-sel="${ m.id }" ${ st.selected.has( m.id ) ? 'checked' : '' } aria-label="${ esc( sprintf( __( 'Select %s', 'pikacart' ), m.name ) ) }"></td>
				<td><div class="pcell">
					<button type="button" class="pphoto" data-row="photo" title="${ esc( __( 'Change photo', 'pikacart' ) ) }">${ m.photo ? `<img src="${ esc( m.photo ) }" alt="" loading="lazy">` : icon( 'user' ) }</button>
					<div><strong>${ esc( m.name || '—' ) }</strong><span class="muted small">${ esc( sub( m ) ) }</span>${ m.source === 'selffill' ? `<span class="muted small">${ esc( __( 'Self-filled', 'pikacart' ) ) }</span>` : '' }</div>
				</div></td>
				<td data-label="${ esc( __( 'ID', 'pikacart' ) ) }">${ esc( m.id_no ) }</td>
				<td data-label="${ esc( __( 'Status', 'pikacart' ) ) }"><span class="badge st-${ esc( m.status ) }">${ esc( S[ m.status ] || m.status ) }</span>${ st.trash ? `<br><small class="muted">${ esc( sprintf( __( 'deleted %s ago', 'pikacart' ), m.deleted ) ) }</small>` : '' }</td>
				<td class="col-actions">
					${ st.trash ? `<button type="button" class="btn btn-sm" data-row="restore">${ esc( __( 'Restore', 'pikacart' ) ) }</button>` : `
					<button type="button" class="icon-btn" data-row="preview" title="${ esc( __( 'Preview', 'pikacart' ) ) }" aria-label="${ esc( __( 'Preview', 'pikacart' ) ) }">${ icon( 'eye' ) }</button>
					<button type="button" class="icon-btn" data-row="edit" title="${ esc( __( 'Edit', 'pikacart' ) ) }" aria-label="${ esc( __( 'Edit', 'pikacart' ) ) }">${ icon( 'edit' ) }</button>
					<div class="dropdown"><button type="button" class="icon-btn" data-row="more" aria-label="${ esc( __( 'More actions', 'pikacart' ) ) }">${ icon( 'menu' ) }</button>
						<div class="dropdown-menu" hidden>
							<button type="button" data-row="pdf">${ icon( 'download' ) }PDF</button>
							<button type="button" data-row="jpg">${ icon( 'download' ) }JPG</button>
							<button type="button" data-row="png">${ icon( 'download' ) }PNG</button>
							<button type="button" data-row="print">${ icon( 'printer' ) }${ esc( __( 'Print', 'pikacart' ) ) }</button>
							${ m.status === 'pending' ? `<button type="button" data-row="approve">${ icon( 'check' ) }${ esc( __( 'Approve', 'pikacart' ) ) }</button>` : '' }
							<button type="button" data-row="dup">${ esc( __( 'Duplicate', 'pikacart' ) ) }</button>
							<button type="button" data-row="delete" class="is-danger">${ icon( 'trash' ) }${ esc( m.status === 'pending' ? __( 'Reject', 'pikacart' ) : __( 'Delete', 'pikacart' ) ) }</button>
						</div></div>` }
				</td>
			</tr>` ).join( '' ) }</tbody></table>`;

		const pages = Math.ceil( st.total / PER_PAGE );
		$( '[data-pager]' ).innerHTML = pages > 1 ? `<button type="button" class="btn btn-sm" data-page="${ st.page - 1 }" ${ st.page <= 1 ? 'disabled' : '' }>${ icon( 'chevron-left' ) }</button>
			<span>${ esc( sprintf( __( 'Page %1$d of %2$d · %3$d people', 'pikacart' ), st.page, pages, st.total ) ) }</span>
			<button type="button" class="btn btn-sm" data-page="${ st.page + 1 }" ${ st.page >= pages ? 'disabled' : '' }>${ icon( 'chevron-right' ) }</button>` : `<span class="muted small">${ esc( sprintf( _n( '%d person', '%d people', st.total, 'pikacart' ), st.total ) ) }</span>`;
		syncBulk();
	}

	function syncBulk() {
		const n = st.selected.size;
		$( '[data-bulk]' ).hidden = ! n;
		$( '[data-bulkcount]' ).textContent = sprintf( _n( '%d selected', '%d selected', n, 'pikacart' ), n );
		body.querySelectorAll( '[data-bulk-act]' ).forEach( ( b ) => {
			const restore = b.dataset.bulkAct === 'restore';
			b.hidden = st.trash ? ! restore : restore;
		} );
		$( '[data-bulk-status]' ).hidden = st.trash;
		const all = $( '[data-all]' );
		if ( all ) {
			all.checked = st.rows.length && st.rows.every( ( m ) => st.selected.has( m.id ) );
		}
	}

	/* ---------- Events ---------- */

	let searchT;
	body.addEventListener( 'input', ( e ) => {
		const f = e.target.dataset && e.target.dataset.f;
		if ( f === 'search' ) {
			clearTimeout( searchT );
			searchT = setTimeout( () => {
				st.search = e.target.value.trim();
				st.page = 1;
				load();
			}, 350 );
		}
	} );
	body.addEventListener( 'change', async ( e ) => {
		const t = e.target;
		if ( t.dataset.f && t.dataset.f !== 'search' ) {
			st[ t.dataset.f ] = t.value;
			st.page = 1;
			load();
		} else if ( t.dataset.sel ) {
			const id = Number( t.dataset.sel );
			t.checked ? st.selected.add( id ) : st.selected.delete( id );
			syncBulk();
		} else if ( t.dataset.all !== undefined && t.matches( '[data-all]' ) ) {
			st.rows.forEach( ( m ) => ( t.checked ? st.selected.add( m.id ) : st.selected.delete( m.id ) ) );
			draw();
		} else if ( t.matches( '[data-bulk-status]' ) && t.value ) {
			await bulk( 'status', { status: t.value } );
			t.value = '';
		} else if ( t.matches( '[data-bulkphotos]' ) ) {
			bulkPhotos( [ ...t.files ] );
			t.value = '';
		}
	} );

	body.addEventListener( 'click', async ( e ) => {
		const tab = e.target.closest( '[data-tab]' );
		if ( tab ) {
			st.trash = tab.dataset.tab === 'trash';
			st.status = st.trash ? '' : tab.dataset.tab;
			st.page = 1;
			st.selected.clear();
			load();
			return;
		}
		const pg = e.target.closest( '[data-page]' );
		if ( pg && ! pg.disabled ) {
			st.page = Number( pg.dataset.page );
			load();
			return;
		}
		const d = e.target.closest( '[data-do]' );
		if ( d ) {
			const a = d.dataset.do;
			if ( a === 'add' ) {
				editPerson( null );
			} else if ( a === 'import' ) {
				importWizard();
			} else if ( a === 'blank' ) {
				blankExcel( P );
				toast( __( 'Blank Excel downloaded. Fill one person per row, then use Import Excel / CSV.', 'pikacart' ) );
			} else if ( a === 'selffill' ) {
				selfFill();
			} else if ( a === 'exportmenu' ) {
				e.stopPropagation();
				const m = $( '[data-exportmenu]' );
				m.hidden = ! m.hidden;
			}
			return;
		}
		const ex = e.target.closest( '[data-export]' );
		if ( ex ) {
			$( '[data-exportmenu]' ).hidden = true;
			exportList( ex.dataset.export );
			return;
		}
		const ba = e.target.closest( '[data-bulk-act]' );
		if ( ba ) {
			const act = ba.dataset.bulkAct;
			if ( act === 'delete' ) {
				if ( await confirmDialog( { title: sprintf( _n( 'Delete %d person?', 'Delete %d people?', st.selected.size, 'pikacart' ), st.selected.size ), message: __( 'They move to the recycle bin and can be restored for 30 days.', 'pikacart' ), confirm: __( 'Delete', 'pikacart' ), danger: true } ) ) {
					bulk( 'delete' );
				}
			} else if ( act === 'restore' || act === 'approve' ) {
				bulk( act );
			} else if ( act === 'download' || act === 'print' ) {
				const members = ( await api( `projects/${ pid }/members/fetch`, { method: 'POST', body: { ids: [ ...st.selected ] } } ) ).members;
				if ( act === 'download' ) {
					downloadDialog( members );
				} else {
					printCards( P, members, 'both' );
				}
			}
			return;
		}
		const row = e.target.closest( '[data-row]' );
		if ( row ) {
			const tr = row.closest( 'tr' );
			const m = st.rows.find( ( x ) => x.id === Number( tr.dataset.mid ) );
			rowAction( row.dataset.row, m, row, e );
			return;
		}
		if ( ! e.target.closest( '.dropdown' ) ) {
			body.querySelectorAll( '.dropdown-menu' ).forEach( ( x ) => {
				x.hidden = true;
			} );
		}
	} );

	async function rowAction( a, m, btn, e ) {
		if ( a === 'more' ) {
			e.stopPropagation();
			const menu = btn.parentNode.querySelector( '.dropdown-menu' );
			body.querySelectorAll( '.dropdown-menu' ).forEach( ( x ) => {
				if ( x !== menu ) {
					x.hidden = true;
				}
			} );
			menu.hidden = ! menu.hidden;
			return;
		}
		body.querySelectorAll( '.dropdown-menu' ).forEach( ( x ) => {
			x.hidden = true;
		} );
		if ( a === 'edit' ) {
			editPerson( m );
		} else if ( a === 'photo' ) {
			pickPhoto( m );
		} else if ( a === 'preview' ) {
			previewPerson( m );
		} else if ( a === 'pdf' || a === 'jpg' || a === 'png' ) {
			exportCards( P, [ m ], { format: a, sides: 'both' } );
		} else if ( a === 'print' ) {
			printCards( P, [ m ], 'both' );
		} else if ( a === 'approve' ) {
			st.selected = new Set( [ m.id ] );
			bulk( 'approve' );
		} else if ( a === 'dup' ) {
			try {
				const res = await api( `members/${ m.id }/duplicate`, { method: 'POST' } );
				toast( res.message );
				load();
				editPerson( res.member );
			} catch ( err ) {
				toast( err.message, 'error' );
			}
		} else if ( a === 'delete' ) {
			if ( await confirmDialog( { title: sprintf( __( 'Delete %s?', 'pikacart' ), m.name ), message: __( 'You can restore this person from the recycle bin for 30 days.', 'pikacart' ), confirm: m.status === 'pending' ? __( 'Reject', 'pikacart' ) : __( 'Delete', 'pikacart' ), danger: true } ) ) {
				st.selected = new Set( [ m.id ] );
				bulk( 'delete' );
			}
		} else if ( a === 'restore' ) {
			st.selected = new Set( [ m.id ] );
			bulk( 'restore' );
		}
	}

	async function bulk( action, extra = {} ) {
		try {
			const res = await api( `projects/${ pid }/members/bulk`, { method: 'POST', body: Object.assign( { action, ids: [ ...st.selected ] }, extra ) } );
			toast( res.message );
			st.selected.clear();
			load( true );
		} catch ( e ) {
			toast( e.message, 'error' );
		}
	}

	/* ---------- Add / edit ---------- */

	function editPerson( m ) {
		const root = document.getElementById( 'modal-root' );
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		let blob = null;
		const val = ( k ) => {
			if ( ! m ) {
				return k === 'valid_until' ? '' : '';
			}
			if ( k === 'name' || k === 'id_no' ) {
				return m[ k ];
			}
			if ( k === 'valid_from' || k === 'valid_until' ) {
				return m[ k ];
			}
			return m.data[ k ] || '';
		};
		wrap.innerHTML = `<div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="pf-title">
			<div class="modal-top"><h2 id="pf-title">${ esc( m ? __( 'Edit person', 'pikacart' ) : __( 'Add person', 'pikacart' ) ) }</h2><button type="button" class="icon-btn" data-close aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button></div>
			<form class="person-form" novalidate>
				<div class="pf-photo">
					<div class="pf-photo-box" data-photobox>${ m && m.photo ? `<img src="${ esc( m.photo ) }" alt="">` : icon( 'user' ) }</div>
					<label class="btn btn-sm">${ icon( 'upload' ) }${ esc( __( 'Photo', 'pikacart' ) ) }<input type="file" accept="image/png,image/jpeg,image/webp" hidden data-pf-photo></label>
					<small class="muted">${ esc( __( 'You can crop after choosing.', 'pikacart' ) ) }</small>
				</div>
				<div class="pf-fields">
					${ fields.map( ( f ) => {
						const type = f.key === 'email' ? 'email' : [ 'mobile', 'emergency' ].includes( f.key ) ? 'tel' : 'text';
						const ph = [ 'dob', 'valid_from', 'valid_until' ].includes( f.key ) ? 'DD-MM-YYYY' : '';
						const input = f.key === 'address' ? `<textarea name="${ f.key }" rows="2">${ esc( val( f.key ) ) }</textarea>` : `<input type="${ type }" name="${ f.key }" value="${ esc( val( f.key ) ) }" placeholder="${ ph }" ${ f.required ? 'required' : '' }>`;
						return `<div class="field ${ f.key === 'address' ? 'span2' : '' }"><label>${ esc( f.label ) }${ f.required ? ' *' : '' }</label>${ input }</div>`;
					} ).join( '' ) }
					${ fields.some( ( f ) => f.key === 'valid_until' ) ? '' : `<div class="field"><label>${ esc( LABELS().valid_until ) }</label><input name="valid_until" value="${ esc( m ? m.valid_until : '' ) }" placeholder="DD-MM-YYYY"></div>` }
					${ m ? `<div class="field"><label>${ esc( __( 'Status', 'pikacart' ) ) }</label><select name="status">${ [ 'draft', 'ready', 'printed', 'cancelled' ].map( ( s ) => `<option value="${ s }" ${ m.status === s ? 'selected' : '' }>${ esc( STATUS()[ s ] ) }</option>` ).join( '' ) }</select></div>` : '' }
				</div>
			</form>
			<div class="modal-actions">
				<button type="button" class="btn" data-close>${ esc( __( 'Cancel', 'pikacart' ) ) }</button>
				${ m ? '' : `<button type="button" class="btn" data-save="again">${ esc( __( 'Save and add another', 'pikacart' ) ) }</button>` }
				<button type="button" class="btn btn-primary" data-save="close">${ icon( 'check' ) }${ esc( __( 'Save', 'pikacart' ) ) }</button>
			</div>
		</div>`;
		root.appendChild( wrap );
		const form = wrap.querySelector( 'form' );
		form.querySelector( 'input' )?.focus();
		const close = () => wrap.remove();
		wrap.addEventListener( 'click', ( e ) => {
			if ( e.target === wrap || e.target.closest( '[data-close]' ) ) {
				close();
			}
		} );
		wrap.querySelector( '[data-pf-photo]' ).addEventListener( 'change', async ( e ) => {
			const file = e.target.files[ 0 ];
			e.target.value = '';
			if ( ! file ) {
				return;
			}
			const b = await cropPhoto( file, photoAspect( P ) );
			if ( b ) {
				blob = b;
				wrap.querySelector( '[data-photobox]' ).innerHTML = `<img src="${ URL.createObjectURL( b ) }" alt="">`;
			}
		} );
		wrap.querySelectorAll( '[data-save]' ).forEach( ( btn ) => btn.addEventListener( 'click', () => withLoading( btn, async () => {
			const person = formData( form );
			try {
				const res = m
					? await api( `members/${ m.id }`, { method: 'POST', body: { person } } )
					: await api( `projects/${ pid }/members`, { method: 'POST', body: { person } } );
				if ( blob ) {
					const fd = new FormData();
					fd.append( 'file', blob, 'photo.jpg' );
					await api( `members/${ res.member.id }/photo`, { method: 'POST', form: fd } );
				}
				toast( res.message );
				if ( ! P.sample ) {
					P.sample = res.member;
				}
				load( true );
				if ( btn.dataset.save === 'again' ) {
					form.reset();
					blob = null;
					wrap.querySelector( '[data-photobox]' ).innerHTML = icon( 'user' );
					form.querySelector( 'input' ).focus();
				} else {
					close();
				}
			} catch ( err ) {
				form.querySelectorAll( '.has-error' ).forEach( ( x ) => x.classList.remove( 'has-error' ) );
				const f = err.data && err.data.field && form.querySelector( `[name="${ err.data.field }"]` );
				if ( f ) {
					f.closest( '.field' ).classList.add( 'has-error' );
					f.focus();
				}
				toast( err.message, 'error' );
			}
		} ) ) );
	}

	function pickPhoto( m ) {
		const input = document.createElement( 'input' );
		input.type = 'file';
		input.accept = 'image/png,image/jpeg,image/webp';
		input.onchange = async () => {
			const file = input.files[ 0 ];
			if ( ! file ) {
				return;
			}
			const blob = await cropPhoto( file, photoAspect( P ) );
			if ( ! blob ) {
				return;
			}
			const fd = new FormData();
			fd.append( 'file', blob, 'photo.jpg' );
			try {
				await api( `members/${ m.id }/photo`, { method: 'POST', form: fd } );
				toast( __( 'Photo updated.', 'pikacart' ) );
				load();
			} catch ( e ) {
				toast( e.message, 'error' );
			}
		};
		input.click();
	}

	function previewPerson( m ) {
		const root = document.getElementById( 'modal-root' );
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		const size = P.size();
		wrap.innerHTML = `<div class="modal modal-lg" role="dialog" aria-modal="true" aria-label="${ esc( m.name ) }">
			<div class="modal-top"><h2>${ esc( m.name ) }</h2><button type="button" class="icon-btn" data-close aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button></div>
			<div class="pv ${ size.w > size.h ? 'is-landscape' : '' }"><figure><canvas data-side="front"></canvas><figcaption>${ esc( __( 'Front', 'pikacart' ) ) }</figcaption></figure><figure><canvas data-side="back"></canvas><figcaption>${ esc( __( 'Back', 'pikacart' ) ) }</figcaption></figure></div>
			<div class="modal-actions">
				<button type="button" class="btn" data-a="print">${ icon( 'printer' ) }${ esc( __( 'Print', 'pikacart' ) ) }</button>
				<button type="button" class="btn" data-a="png">PNG</button>
				<button type="button" class="btn" data-a="jpg">JPG</button>
				<button type="button" class="btn btn-primary" data-a="pdf">${ icon( 'download' ) }PDF</button>
			</div>
		</div>`;
		root.appendChild( wrap );
		const lay = P.layout();
		wrap.querySelectorAll( 'canvas' ).forEach( ( c ) => renderSide( c, lay[ c.dataset.side ], Object.assign( P.renderOpts( c.dataset.side, m ), { width: ( size.w > size.h ? 420 : 300 ) * Math.min( 2, devicePixelRatio || 1 ), watermark: P.ctx.me.state.watermark ? { text: P.ctx.me.watermark.text, brand: getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-brand' ).trim() } : null } ) ) );
		wrap.addEventListener( 'click', ( e ) => {
			if ( e.target === wrap || e.target.closest( '[data-close]' ) ) {
				wrap.remove();
			}
			const a = e.target.closest( '[data-a]' );
			if ( a ) {
				a.dataset.a === 'print' ? printCards( P, [ m ], 'both' ) : exportCards( P, [ m ], { format: a.dataset.a, sides: 'both' } );
			}
		} );
	}

	function downloadDialog( members ) {
		confirmDialog( {
			title: sprintf( _n( 'Download %d card', 'Download %d cards', members.length, 'pikacart' ), members.length ),
			body: `<div class="field"><label>${ esc( __( 'Format', 'pikacart' ) ) }</label><select id="dl-f"><option value="pdf">${ esc( __( 'One PDF (all cards)', 'pikacart' ) ) }</option><option value="png">${ esc( __( 'PNG images in a ZIP', 'pikacart' ) ) }</option><option value="jpg">${ esc( __( 'JPG images in a ZIP', 'pikacart' ) ) }</option></select></div>
				<div class="field"><label>${ esc( __( 'Sides', 'pikacart' ) ) }</label><select id="dl-s"><option value="both">${ esc( __( 'Front and back', 'pikacart' ) ) }</option><option value="front">${ esc( __( 'Front only', 'pikacart' ) ) }</option><option value="back">${ esc( __( 'Back only', 'pikacart' ) ) }</option></select></div>`,
			confirm: __( 'Download', 'pikacart' ),
		} ).then( ( wrap ) => {
			if ( wrap ) {
				exportCards( P, members, { format: wrap.querySelector( '#dl-f' ).value, sides: wrap.querySelector( '#dl-s' ).value } );
			}
		} );
	}

	/* ---------- Import ---------- */

	function importWizard() {
		const root = document.getElementById( 'modal-root' );
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		wrap.innerHTML = `<div class="modal modal-xl" role="dialog" aria-modal="true" aria-label="${ esc( __( 'Import from Excel or CSV', 'pikacart' ) ) }">
			<div class="modal-top"><h2>${ esc( __( 'Import from Excel or CSV', 'pikacart' ) ) }</h2><button type="button" class="icon-btn" data-close aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button></div>
			<div data-stage></div>
		</div>`;
		root.appendChild( wrap );
		const stage = wrap.querySelector( '[data-stage]' );
		const close = () => wrap.remove();
		wrap.addEventListener( 'click', ( e ) => {
			if ( e.target === wrap || e.target.closest( '[data-close]' ) ) {
				close();
			}
		} );

		stage.innerHTML = `<div class="import-drop">
			${ icon( 'upload' ) }
			<p><strong>${ esc( __( 'Choose your Excel (.xlsx) or CSV file', 'pikacart' ) ) }</strong></p>
			<p class="muted small">${ esc( __( 'The first row must have column names. Photos can be added later with Bulk photos.', 'pikacart' ) ) }</p>
			<label class="btn btn-primary">${ esc( __( 'Choose file', 'pikacart' ) ) }<input type="file" accept=".xlsx,.xls,.csv" hidden data-file></label>
			<button type="button" class="btn btn-ghost" data-sample>${ icon( 'download' ) }${ esc( __( 'Download blank Excel for this design', 'pikacart' ) ) }</button>
		</div>`;
		stage.querySelector( '[data-sample]' ).addEventListener( 'click', () => blankExcel( P ) );
		stage.querySelector( '[data-file]' ).addEventListener( 'change', async ( e ) => {
			const file = e.target.files[ 0 ];
			if ( ! file ) {
				return;
			}
			try {
				const buf = await file.arrayBuffer();
				// CSV: keep every value exactly as typed (no US date guessing).
				const isCsv = /\.(csv|txt)$/i.test( file.name );
				const wb = window.XLSX.read( buf, { type: 'array', cellDates: false, cellNF: true, raw: isCsv } );
				const keys = pikacartKeys( wb );
				const sheet = wb.Sheets[ keys && wb.Sheets.People ? 'People' : wb.SheetNames[ 0 ] ];
				// Excel: show real date cells as DD-MM-YYYY (Indian format).
				Object.keys( sheet ).forEach( ( k ) => {
					const c = sheet[ k ];
					if ( k[ 0 ] !== '!' && c && c.t === 'n' && c.z && /[dy]/i.test( String( c.z ) ) && ! /[#0?]/.test( String( c.z ).replace( /\[[^\]]*\]/g, '' ) ) ) {
						c.w = window.XLSX.SSF.format( 'dd-mm-yyyy', c.v );
					}
				} );
				// Keep each row's sheet row number so pasted photos can be matched to it.
				const startRow = sheet[ '!ref' ] ? window.XLSX.utils.decode_range( sheet[ '!ref' ] ).s.r : 0;
				const rows = window.XLSX.utils.sheet_to_json( sheet, { header: 1, defval: '', raw: false, blankrows: true } )
					.map( ( r, i ) => {
						r.sheetRow = startRow + i;
						return r;
					} )
					.filter( ( r, i ) => i === 0 || r.some( ( v ) => String( v ).trim() !== '' ) );
				src.buf = isCsv ? null : buf;
				src.sheetName = keys && wb.Sheets.People ? 'People' : wb.SheetNames[ 0 ];
				src.files = new Map();
				if ( rows.length < 2 ) {
					toast( __( 'This file has no rows to import.', 'pikacart' ), 'error' );
					return;
				}
				if ( keys ) {
					ready( rows, keys );
				} else {
					mapping( rows );
				}
			} catch ( err ) {
				toast( __( 'Could not read this file. Please save it as .xlsx or .csv and try again.', 'pikacart' ), 'error' );
			}
		} );

		const src = { buf: null, sheetName: '', files: new Map() };

		/** "Add photos" input shown before importing: photo files or a ZIP. */
		function photoPicker() {
			return `<div class="xl-photos"><label class="btn btn-sm">${ icon( 'image' ) }${ esc( __( 'Add photos or a ZIP (optional)', 'pikacart' ) ) }<input type="file" accept="image/png,image/jpeg,image/webp,.zip" multiple hidden data-photos></label><span class="muted small" data-photo-msg>${ esc( __( 'Photos pasted inside the Excel are picked up automatically.', 'pikacart' ) ) }</span></div>`;
		}
		function bindPhotoPicker() {
			const input = stage.querySelector( '[data-photos]' );
			input?.addEventListener( 'change', async () => {
				src.files = await photoFiles( [ ...input.files ] );
				stage.querySelector( '[data-photo-msg]' ).textContent = sprintf( _n( '%d photo ready to match', '%d photos ready to match', src.files.size, 'pikacart' ), src.files.size );
			} );
		}

		function guess( header ) {
			const h = String( header ).toLowerCase().replace( /[^a-z]/g, '' );
			const map = {
				name: [ 'name', 'fullname', 'studentname', 'employeename', 'staffname', 'membername' ],
				id_no: [ 'id', 'idno', 'idnumber', 'rollno', 'rollnumber', 'roll', 'admissionno', 'empid', 'employeeid', 'regno', 'registrationno' ],
				class: [ 'class', 'course', 'grade', 'std', 'standard' ],
				section: [ 'section', 'sec', 'division' ],
				designation: [ 'designation', 'role', 'post', 'position', 'passtype' ],
				department: [ 'department', 'dept', 'area' ],
				mobile: [ 'mobile', 'phone', 'mobileno', 'contact', 'contactno', 'phoneno' ],
				email: [ 'email', 'emailid', 'mail' ],
				dob: [ 'dob', 'dateofbirth', 'birthdate' ],
				blood_group: [ 'bloodgroup', 'blood', 'bg' ],
				guardian: [ 'father', 'fathername', 'fathersname', 'guardian', 'guardianname', 'parent', 'parentname' ],
				address: [ 'address', 'residence' ],
				emergency: [ 'emergency', 'emergencycontact', 'emergencyno' ],
				valid_from: [ 'validfrom', 'issuedate', 'from' ],
				valid_until: [ 'validuntil', 'validtill', 'validupto', 'expiry', 'expirydate', 'till' ],
				photo: [ 'photo', 'photofile', 'photoname', 'image', 'picture', 'pic' ],
				session: [ 'session', 'year', 'academicyear' ],
			};
			for ( const [ k, list ] of Object.entries( map ) ) {
				if ( list.includes( h ) ) {
					return k;
				}
			}
			const f = fields.find( ( x ) => x.label.toLowerCase().replace( /[^a-z]/g, '' ) === h );
			return f ? f.key : '';
		}

		/** Our own blank Excel: every column is already known, no matching needed. */
		function ready( rows, keys ) {
			const allowed = new Set( [ ...fields.map( ( f ) => f.key ), 'valid_until', 'session', 'photo' ] );
			const map = {};
			keys.forEach( ( k, i ) => {
				if ( allowed.has( k ) ) {
					map[ k ] = i;
				}
			} );
			if ( map.name === undefined || map.id_no === undefined ) {
				mapping( rows );
				return;
			}
			const head = rows[ 0 ];
			stage.innerHTML = `<div class="xl-ready">${ icon( 'check-circle' ) }<div><strong>${ esc( sprintf( _n( '%d person found in your Pikacart Excel', '%d people found in your Pikacart Excel', rows.length - 1, 'pikacart' ), rows.length - 1 ) ) }</strong><span>${ esc( __( 'All columns match this design. Check the first rows and import.', 'pikacart' ) ) }</span></div></div>
				<div class="table-wrap"><table class="table small-table"><thead><tr>${ head.map( ( h ) => `<th>${ esc( h ) }</th>` ).join( '' ) }</tr></thead><tbody>${ rows.slice( 1, 6 ).map( ( r ) => `<tr>${ head.map( ( h, i ) => `<td>${ esc( r[ i ] ) }</td>` ).join( '' ) }</tr>` ).join( '' ) }</tbody></table></div>
				${ photoPicker() }
				<div class="field"><label>${ esc( __( 'If an ID number already exists', 'pikacart' ) ) }</label><select data-mode><option value="skip">${ esc( __( 'Skip that row and report it', 'pikacart' ) ) }</option><option value="update">${ esc( __( 'Update the existing person', 'pikacart' ) ) }</option></select></div>
				<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Cancel', 'pikacart' ) ) }</button><button type="button" class="btn btn-primary" data-go>${ esc( sprintf( __( 'Import %d people', 'pikacart' ), rows.length - 1 ) ) }</button></div>`;
			bindPhotoPicker();
			stage.querySelector( '[data-go]' ).addEventListener( 'click', () => run( rows, map, stage.querySelector( '[data-mode]' ).value ) );
		}

		function mapping( rows ) {
			const head = rows[ 0 ];
			const targets = [ ...fields, ...( fields.some( ( f ) => f.key === 'valid_until' ) ? [] : [ { key: 'valid_until', label: LABELS().valid_until } ] ), { key: 'session', label: LABELS().session }, { key: 'photo', label: __( 'Photo (file name)', 'pikacart' ) } ];
			const opts = ( sel ) => `<option value="">${ esc( __( '— Do not import —', 'pikacart' ) ) }</option>` + targets.map( ( t ) => `<option value="${ t.key }" ${ t.key === sel ? 'selected' : '' }>${ esc( t.label ) }</option>` ).join( '' );
			stage.innerHTML = `<p>${ esc( sprintf( __( '%d rows found. Match your columns to Pikacart fields:', 'pikacart' ), rows.length - 1 ) ) }</p>
				<div class="map-grid">${ head.map( ( h, i ) => `<div class="map-row"><span class="map-col">${ esc( h || sprintf( __( 'Column %d', 'pikacart' ), i + 1 ) ) }</span>${ icon( 'chevron-right' ) }<select data-col="${ i }">${ opts( guess( h ) ) }</select></div>` ).join( '' ) }</div>
				<h4 class="sub-h">${ esc( __( 'Preview of the first rows', 'pikacart' ) ) }</h4>
				<div class="table-wrap"><table class="table small-table"><thead><tr>${ head.map( ( h ) => `<th>${ esc( h ) }</th>` ).join( '' ) }</tr></thead><tbody>${ rows.slice( 1, 6 ).map( ( r ) => `<tr>${ head.map( ( h, i ) => `<td>${ esc( r[ i ] ) }</td>` ).join( '' ) }</tr>` ).join( '' ) }</tbody></table></div>
				${ photoPicker() }
				<div class="field"><label>${ esc( __( 'If an ID number already exists', 'pikacart' ) ) }</label><select data-mode><option value="skip">${ esc( __( 'Skip that row and report it', 'pikacart' ) ) }</option><option value="update">${ esc( __( 'Update the existing person', 'pikacart' ) ) }</option></select></div>
				<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Cancel', 'pikacart' ) ) }</button><button type="button" class="btn btn-primary" data-go>${ esc( sprintf( __( 'Import %d rows', 'pikacart' ), rows.length - 1 ) ) }</button></div>`;
			bindPhotoPicker();
			stage.querySelector( '[data-go]' ).addEventListener( 'click', () => {
				const map = {};
				stage.querySelectorAll( '[data-col]' ).forEach( ( s ) => {
					if ( s.value ) {
						map[ s.value ] = Number( s.dataset.col );
					}
				} );
				if ( map.name === undefined || map.id_no === undefined ) {
					toast( __( 'Please match the Name and ID number columns.', 'pikacart' ), 'error' );
					return;
				}
				run( rows, map, stage.querySelector( '[data-mode]' ).value );
			} );
		}

		async function run( rows, map, mode ) {
			const photoCol = map.photo;
			const data = rows.slice( 1 ).map( ( r ) => {
				const o = {};
				Object.entries( map ).forEach( ( [ k, i ] ) => {
					if ( k !== 'photo' ) {
						o[ k ] = String( r[ i ] ?? '' ).trim();
					}
				} );
				return o;
			} );
			stage.innerHTML = `<p data-msg>${ esc( __( 'Importing…', 'pikacart' ) ) }</p><div class="progress big"><span data-bar style="width:0%"></span></div>`;
			const results = [];
			const B = 100;
			for ( let i = 0; i < data.length; i += B ) {
				try {
					const res = await api( `projects/${ pid }/members/import`, { method: 'POST', body: { rows: data.slice( i, i + B ), mode, start: i + 2 } } );
					results.push( ...res.results );
				} catch ( err ) {
					toast( err.message, 'error' );
					for ( let k = i; k < Math.min( i + B, data.length ); k++ ) {
						results.push( { row: k + 2, ok: false, error: err.message } );
					}
				}
				stage.querySelector( '[data-bar]' ).style.width = Math.round( ( Math.min( i + B, data.length ) / data.length ) * 100 ) + '%';
				stage.querySelector( '[data-msg]' ).textContent = sprintf( __( 'Imported %1$d of %2$d rows…', 'pikacart' ), Math.min( i + B, data.length ), data.length );
			}
			const ok = results.filter( ( r ) => r.ok ).length;
			const bad = results.filter( ( r ) => ! r.ok );

			// Photos: pasted in the Excel, chosen as files/ZIP, or named by ID number.
			let photoNote = '';
			const okRows = new Set( results.filter( ( r ) => r.ok ).map( ( r ) => r.row ) );
			const pasted = src.buf ? await sheetImages( src.buf, src.sheetName, photoCol === undefined ? -1 : photoCol ) : new Map();
			const items = [];
			rows.slice( 1 ).forEach( ( r, k ) => {
				if ( ! okRows.has( k + 2 ) ) {
					return;
				}
				const id = data[ k ].id_no;
				const blob = pasted.get( r.sheetRow ) || findPhoto( src.files, photoCol === undefined ? '' : r[ photoCol ], id );
				if ( blob ) {
					items.push( { id, blob, label: `${ data[ k ].name || id } (${ __( 'row', 'pikacart' ) } ${ k + 2 })` } );
				}
			} );
			if ( items.length ) {
				stage.querySelector( '[data-msg]' ).textContent = __( 'Adding photos…', 'pikacart' );
				const up = await uploadPhotos( items, ( done, total ) => {
					stage.querySelector( '[data-bar]' ).style.width = Math.round( ( done / total ) * 100 ) + '%';
					stage.querySelector( '[data-msg]' ).textContent = sprintf( __( '%1$d of %2$d photos added…', 'pikacart' ), done, total );
				} );
				photoNote = `<p class="big-num ok">${ icon( 'image' ) } ${ esc( sprintf( _n( '%d photo added', '%d photos added', up.ok, 'pikacart' ), up.ok ) ) }</p>${ up.unmatched.length ? `<ul class="small muted unmatched">${ up.unmatched.slice( 0, 50 ).map( ( n ) => `<li>${ esc( n ) }</li>` ).join( '' ) }</ul>` : '' }`;
			}
			const missing = ok - items.length;
			// Report the row numbers as they appear in the Excel file.
			const realRow = ( n ) => ( rows[ n - 1 ] && rows[ n - 1 ].sheetRow !== undefined ? rows[ n - 1 ].sheetRow + 1 : n );
			stage.innerHTML = `<div class="import-result">
				<p class="big-num ok">${ icon( 'check-circle' ) } ${ esc( sprintf( _n( '%d person imported', '%d people imported', ok, 'pikacart' ), ok ) ) }</p>
				${ bad.length ? `<p class="big-num bad">${ icon( 'alert' ) } ${ esc( sprintf( _n( '%d row needs attention', '%d rows need attention', bad.length, 'pikacart' ), bad.length ) ) }</p>
				<div class="table-wrap"><table class="table small-table"><thead><tr><th>${ esc( __( 'Row', 'pikacart' ) ) }</th><th>${ esc( __( 'Reason', 'pikacart' ) ) }</th></tr></thead><tbody>${ bad.slice( 0, 200 ).map( ( r ) => `<tr><td>${ realRow( r.row ) }</td><td>${ esc( r.error ) }</td></tr>` ).join( '' ) }</tbody></table></div>
				<button type="button" class="btn btn-sm" data-errcsv>${ icon( 'download' ) }${ esc( __( 'Download error report', 'pikacart' ) ) }</button>` : '' }
				${ photoNote }
				${ missing > 0 ? `<p class="muted small">${ esc( sprintf( _n( 'No photo was found in the file for %d person. Add it with "Bulk photos" (name each photo by ID number, e.g. 1001.jpg) or by editing the person.', 'No photo was found in the file for %d people. Add them with "Bulk photos" (name each photo by ID number, e.g. 1001.jpg) or by editing the person.', missing, 'pikacart' ), missing ) ) }</p>` : '' }
				<div class="modal-actions"><button type="button" class="btn" data-close>${ esc( __( 'Done', 'pikacart' ) ) }</button>${ ok ? `<a class="btn btn-primary" href="${ esc( P.ctx.url( `projects/${ pid }/download` ) ) }" data-link data-close>${ icon( 'download' ) }${ esc( __( 'Download the cards', 'pikacart' ) ) }</a>` : '' }</div>
			</div>`;
			stage.querySelector( '[data-errcsv]' )?.addEventListener( 'click', () => {
				const ws = window.XLSX.utils.aoa_to_sheet( [ [ 'Row', 'Reason' ], ...bad.map( ( r ) => [ realRow( r.row ), r.error ] ) ] );
				const wb = window.XLSX.utils.book_new();
				window.XLSX.utils.book_append_sheet( wb, ws, 'Errors' );
				window.XLSX.writeFile( wb, 'import-errors.csv', { bookType: 'csv' } );
			} );
			load( true );
		}
	}

	/* ---------- Export ---------- */

	async function exportList( kind ) {
		if ( kind === 'sample' ) {
			blankExcel( P );
			return;
		}
		toast( __( 'Preparing your file…', 'pikacart' ), 'info' );
		const all = [];
		for ( let page = 1; page < 200; page++ ) {
			const res = await api( `projects/${ pid }/members?per_page=500&page=${ page }&sort=id_no` );
			all.push( ...res.members );
			if ( all.length >= res.total || ! res.members.length ) {
				break;
			}
		}
		const head = [ ...fields.map( ( f ) => f.label ), LABELS().session, __( 'Status', 'pikacart' ), __( 'Photo', 'pikacart' ), __( 'Verification link', 'pikacart' ) ];
		const rows = all.map( ( m ) => [ ...fields.map( ( f ) => ( f.key === 'name' || f.key === 'id_no' ? m[ f.key ] : f.key === 'valid_until' || f.key === 'valid_from' ? m[ f.key ] : m.data[ f.key ] || '' ) ), m.session, STATUS()[ m.status ] || m.status, m.photo, `${ window.PKC.home }verify/${ m.verify_token }` ] );
		const ws = window.XLSX.utils.aoa_to_sheet( [ head, ...rows ] );
		const wb = window.XLSX.utils.book_new();
		window.XLSX.utils.book_append_sheet( wb, ws, 'People' );
		const name = ( P.project.name || 'people' ).replace( /[^\w\- ]+/g, '' ).trim().replace( /\s+/g, '-' ) || 'people';
		window.XLSX.writeFile( wb, `${ name }.${ kind }`, { bookType: kind } );
	}

	/* ---------- Bulk photos ---------- */

	/** ID number (lower case) → person, for this project. */
	async function peopleById() {
		const byId = new Map();
		for ( let page = 1; page < 200; page++ ) {
			const res = await api( `projects/${ pid }/members?per_page=500&page=${ page }` );
			res.members.forEach( ( m ) => byId.set( String( m.id_no ).trim().toLowerCase(), m ) );
			if ( byId.size >= res.total || ! res.members.length ) {
				break;
			}
		}
		return byId;
	}

	/**
	 * Compress and upload photos for people.
	 * @param {Array} items [{ id: ID number, blob, label }]
	 */
	async function uploadPhotos( items, onProgress ) {
		const byId = await peopleById();
		const aspect = photoAspect( P );
		const unmatched = [];
		let ok = 0;
		let done = 0;
		for ( const it of items ) {
			const m = byId.get( String( it.id ).trim().toLowerCase() );
			if ( ! m ) {
				unmatched.push( it.label );
			} else {
				try {
					const blob = await compressPhoto( it.blob, 600, Math.round( 600 / aspect ) );
					const fd = new FormData();
					fd.append( 'file', blob, 'photo.jpg' );
					await api( `members/${ m.id }/photo`, { method: 'POST', form: fd } );
					ok++;
				} catch ( e ) {
					unmatched.push( `${ it.label } (${ e.message })` );
				}
			}
			done++;
			onProgress( done, items.length );
		}
		return { ok, unmatched };
	}

	async function bulkPhotos( files ) {
		if ( ! files.length ) {
			return;
		}
		const root = document.getElementById( 'modal-root' );
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		wrap.innerHTML = `<div class="modal" role="dialog" aria-modal="true" aria-label="${ esc( __( 'Bulk photos', 'pikacart' ) ) }"><h2>${ esc( __( 'Matching photos…', 'pikacart' ) ) }</h2><p data-msg class="muted"></p><div class="progress big"><span data-bar style="width:0%"></span></div><div data-out></div></div>`;
		root.appendChild( wrap );
		const msg = wrap.querySelector( '[data-msg]' );
		const { ok, unmatched } = await uploadPhotos(
			[ ...files ].map( ( f ) => ( { id: f.name.replace( /\.[^.]+$/, '' ), blob: f, label: f.name } ) ),
			( done, total ) => {
				wrap.querySelector( '[data-bar]' ).style.width = Math.round( ( done / total ) * 100 ) + '%';
				msg.textContent = sprintf( __( '%1$d of %2$d photos processed', 'pikacart' ), done, total );
			}
		);
		wrap.querySelector( 'h2' ).textContent = __( 'Bulk photos finished', 'pikacart' );
		wrap.querySelector( '[data-out]' ).innerHTML = `<p class="big-num ok">${ icon( 'check-circle' ) } ${ esc( sprintf( _n( '%d photo matched', '%d photos matched', ok, 'pikacart' ), ok ) ) }</p>
			${ unmatched.length ? `<p class="big-num bad">${ icon( 'alert' ) } ${ esc( sprintf( _n( '%d photo did not match an ID number', '%d photos did not match an ID number', unmatched.length, 'pikacart' ), unmatched.length ) ) }</p><ul class="small muted unmatched">${ unmatched.slice( 0, 100 ).map( ( n ) => `<li>${ esc( n ) }</li>` ).join( '' ) }</ul>` : '' }
			<p class="muted small">${ esc( __( 'Tip: name each photo exactly by the person\'s ID number, e.g. 1001.jpg.', 'pikacart' ) ) }</p>
			<div class="modal-actions"><button type="button" class="btn btn-primary" data-ok>${ esc( __( 'Done', 'pikacart' ) ) }</button></div>`;
		wrap.querySelector( '[data-ok]' ).addEventListener( 'click', () => wrap.remove() );
		load( true );
	}

	/* ---------- Self-fill link ---------- */

	async function selfFill() {
		let link = null;
		try {
			link = ( await api( `projects/${ pid }/selffill` ) ).link;
		} catch ( e ) {
			toast( e.message, 'error' );
			return;
		}
		const root = document.getElementById( 'modal-root' );
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		const draw = () => {
			wrap.innerHTML = `<div class="modal" role="dialog" aria-modal="true" aria-label="${ esc( __( 'Self-fill link', 'pikacart' ) ) }">
				<div class="modal-top"><h2>${ esc( __( 'Self-fill link', 'pikacart' ) ) }</h2><button type="button" class="icon-btn" data-close aria-label="${ esc( __( 'Close', 'pikacart' ) ) }">${ icon( 'x' ) }</button></div>
				<p class="muted small">${ esc( __( 'Share this link with students or staff. They fill their own details and photo; entries arrive as "Pending" for you to approve.', 'pikacart' ) ) }</p>
				${ link ? `<div class="copy-row"><input readonly value="${ esc( link.url ) }" data-url><button type="button" class="btn btn-sm" data-copy>${ esc( __( 'Copy', 'pikacart' ) ) }</button></div>
					<div class="share-row"><a class="btn btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text=${ encodeURIComponent( __( 'Please fill your ID card details here:', 'pikacart' ) + ' ' + link.url ) }">WhatsApp</a><a class="btn btn-sm" href="${ esc( link.url ) }" target="_blank" rel="noopener">${ esc( __( 'Open form', 'pikacart' ) ) }</a></div>
					<label class="toggle"><input type="checkbox" data-active ${ link.active ? 'checked' : '' }><span class="toggle-ui"></span><span>${ esc( __( 'Link is open', 'pikacart' ) ) }</span></label>
					<div class="field"><label>${ esc( __( 'Close automatically on (optional)', 'pikacart' ) ) }</label><input type="date" data-exp value="${ esc( link.expires ) }"></div>` : `<p>${ esc( __( 'No link yet.', 'pikacart' ) ) }</p>` }
				<div class="modal-actions">
					<button type="button" class="btn" data-regen>${ esc( link ? __( 'Create a new link', 'pikacart' ) : __( 'Create link', 'pikacart' ) ) }</button>
					${ link ? `<button type="button" class="btn btn-primary" data-save>${ esc( __( 'Save', 'pikacart' ) ) }</button>` : '' }
				</div>
			</div>`;
		};
		draw();
		root.appendChild( wrap );
		wrap.addEventListener( 'click', async ( e ) => {
			if ( e.target === wrap || e.target.closest( '[data-close]' ) ) {
				wrap.remove();
				return;
			}
			if ( e.target.closest( '[data-copy]' ) ) {
				const i = wrap.querySelector( '[data-url]' );
				i.select();
				navigator.clipboard ? navigator.clipboard.writeText( i.value ) : document.execCommand( 'copy' );
				toast( __( 'Link copied.', 'pikacart' ) );
			}
			const regen = e.target.closest( '[data-regen]' );
			const save = e.target.closest( '[data-save]' );
			if ( regen || save ) {
				try {
					const res = await api( `projects/${ pid }/selffill`, {
						method: 'POST',
						body: { regenerate: !! regen, active: regen ? 1 : wrap.querySelector( '[data-active]' ).checked ? 1 : 0, expires: regen ? '' : wrap.querySelector( '[data-exp]' ).value },
					} );
					link = res.link;
					toast( res.message );
					draw();
				} catch ( err ) {
					toast( err.message, 'error' );
				}
			}
		} );
	}

	load( true );
	return () => {
		alive = false;
	};
}
