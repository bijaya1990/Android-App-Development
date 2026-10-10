/**
 * My Projects: every card project with a thumbnail, category, size, people
 * count and last saved time; open, duplicate, rename, delete, new session.
 * Also used by Members and Print Sheets to pick a project.
 * Routes: projects, projects/{id}/{step}, members, members/{id}, print, print/{id}
 */

import { api } from '../api.js';
import { esc, icon, toast, confirmDialog, emptyState, withLoading } from '../ui.js';
import { catalog, orgVars } from '../catalog.js';
import { thumb } from '../../card/gallery.js';
import { cardSize, paletteOf, flagsOf } from '../../card/scene.js';
import project from './project.js';
import printView from '../print.js';
import { blankExcel } from '../people.js';

const { __, sprintf } = window.wp.i18n;

/** Pick screen shared by projects, members and print. */
export default function projects( mode ) {
	return ( el, ctx, params ) => {
		const id = Number( params[ 0 ] || 0 );
		if ( id && mode === 'projects' ) {
			return project( el, ctx, params );
		}
		if ( id && mode === 'members' ) {
			ctx.navigate( `projects/${ id }/people`, true );
			return null;
		}
		if ( id && mode === 'print' ) {
			return printView( el, ctx, id );
		}
		return list( el, ctx, mode );
	};
}

function list( el, ctx, mode ) {
	let alive = true;
	el.innerHTML = '<div class="skeleton-grid"><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div></div>';

	const intro = {
		projects: [ __( 'Your card projects', 'pikacart' ), __( 'Each project is one design with its list of people.', 'pikacart' ) ],
		members: [ __( 'Choose a project', 'pikacart' ), __( 'Pick the project whose people you want to manage.', 'pikacart' ) ],
		print: [ __( 'Print sheets', 'pikacart' ), __( 'Pick a project to arrange its cards on paper with cut marks.', 'pikacart' ) ],
	}[ mode ];

	( async () => {
		let cat;
		let data;
		try {
			[ cat, data ] = await Promise.all( [ catalog(), api( 'projects' ) ] );
		} catch ( e ) {
			el.innerHTML = `<div class="card"><p>${ esc( e.message ) }</p></div>`;
			return;
		}
		if ( ! alive ) {
			return;
		}
		const items = data.projects.filter( ( p ) => mode === 'projects' || p.status !== 'archived' || true );
		if ( ! items.length ) {
			el.innerHTML = `<section class="card">${ emptyState( { art: 'cards', title: __( 'No card projects yet', 'pikacart' ), text: __( 'Create your first project by choosing a design for your students or staff.', 'pikacart' ), button: __( 'Create ID card', 'pikacart' ), href: ctx.url( 'create' ) } ) }</section>`;
			return;
		}
		const target = ( p ) => ( mode === 'projects' ? `projects/${ p.id }/${ [ 'design', 'design', 'design', 'setup', 'details', 'editor', 'people', 'download' ][ p.step ] || 'setup' }` : mode === 'members' ? `projects/${ p.id }/people` : `print/${ p.id }` );
		el.innerHTML = `<div class="section-head-row"><div><h2 class="h2">${ esc( intro[ 0 ] ) }</h2><p class="muted">${ esc( intro[ 1 ] ) }</p></div>
			<a class="btn btn-primary" href="${ esc( ctx.url( 'create' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'New project', 'pikacart' ) ) }</a></div>
			<div class="proj-grid">
			${ items.map( ( p ) => {
				const size = cardSize( p, cat.sizes );
				return `<article class="card proj-card ${ p.status === 'archived' ? 'is-archived' : '' }" data-pid="${ p.id }">
					<a class="proj-thumb ${ size.w > size.h ? 'is-landscape' : '' }" href="${ esc( ctx.url( target( p ) ) ) }" data-link><canvas aria-hidden="true"></canvas></a>
					<div class="proj-info">
						<a class="proj-name-link" href="${ esc( ctx.url( target( p ) ) ) }" data-link><strong>${ esc( p.name ) }</strong></a>
						<span class="muted small">${ esc( ( p.subtype ? p.subtype.name : '' ) + ' · ' + size.w + '×' + size.h + ' mm' ) }</span>
						<span class="muted small">${ esc( sprintf( _n( '%d person', '%d people', p.people || 0 ), p.people || 0 ) ) } · ${ esc( sprintf( __( 'saved %s ago', 'pikacart' ), p.updated ) ) }</span>
						${ p.status === 'archived' ? `<span class="badge">${ esc( __( 'Archived', 'pikacart' ) ) }</span>` : '' }
					</div>
					${ mode === 'projects' ? `<div class="proj-actions">
						<a class="btn btn-sm" href="${ esc( ctx.url( `projects/${ p.id }/design` ) ) }" data-link>${ icon( 'palette' ) }${ esc( __( 'Change design', 'pikacart' ) ) }</a>
						<button type="button" class="btn btn-sm" data-act="blank">${ icon( 'download' ) }${ esc( __( 'Blank Excel', 'pikacart' ) ) }</button>
						<button type="button" class="btn btn-sm btn-ghost" data-act="rename">${ esc( __( 'Rename', 'pikacart' ) ) }</button>
						<button type="button" class="btn btn-sm btn-ghost" data-act="dup">${ esc( __( 'Duplicate', 'pikacart' ) ) }</button>
						<button type="button" class="btn btn-sm btn-ghost" data-act="session">${ esc( __( 'New session', 'pikacart' ) ) }</button>
						<button type="button" class="btn btn-sm btn-ghost" data-act="del">${ esc( __( 'Delete', 'pikacart' ) ) }</button>
					</div>` : '' }
				</article>`;
			} ).join( '' ) }
			</div>`;

		el.querySelectorAll( '.proj-card' ).forEach( ( card, i ) => {
			const p = items[ i ];
			if ( p.template ) {
				const size = cardSize( p, cat.sizes );
				const key = size.w > size.h ? 'landscape' : 'portrait';
				thumb( card.querySelector( 'canvas' ), {
					template: p.template,
					layout: p.design && p.design[ key ] ? p.design[ key ] : null,
					subtype: p.subtype,
					orientation: p.orientation,
					size,
					palette: paletteOf( p, cat.palettes ),
					org: orgVars( ctx.me.org ),
					flags: flagsOf( p ),
					width: size.w > size.h ? 220 : 140,
					index: i,
				} );
			}
			card.querySelectorAll( '[data-act]' ).forEach( ( b ) => b.addEventListener( 'click', () => act( b, p ) ) );
		} );
	} )();

	async function act( b, p ) {
		const a = b.dataset.act;
		if ( a === 'blank' ) {
			blankExcel( { project: p, data: { subtype: p.subtype } } );
			toast( __( 'Blank Excel downloaded with the columns of this design.', 'pikacart' ) );
			return;
		}
		if ( a === 'rename' ) {
			const wrap = await confirmDialog( { title: __( 'Rename project', 'pikacart' ), body: `<div class="field"><label for="rn">${ esc( __( 'Name', 'pikacart' ) ) }</label><input id="rn" value="${ esc( p.name ) }" maxlength="120"></div>`, confirm: __( 'Save', 'pikacart' ) } );
			if ( wrap ) {
				const name = wrap.querySelector( '#rn' ).value.trim();
				if ( name ) {
					await api( `projects/${ p.id }`, { method: 'POST', body: { name } } ).then( () => toast( __( 'Renamed.', 'pikacart' ) ) ).catch( ( e ) => toast( e.message, 'error' ) );
					ctx.navigate( 'projects', true );
				}
			}
		} else if ( a === 'dup' ) {
			await withLoading( b, () => api( `projects/${ p.id }/duplicate`, { method: 'POST' } ).then( () => toast( __( 'Project duplicated (people are not copied).', 'pikacart' ) ) ).catch( ( e ) => toast( e.message, 'error' ) ) );
			ctx.navigate( 'projects', true );
		} else if ( a === 'session' ) {
			const wrap = await confirmDialog( {
				title: __( 'Start a new session', 'pikacart' ),
				message: __( 'Creates a copy of this project for the new year with all people and photos. Last year\'s project is archived.', 'pikacart' ),
				body: `<div class="field"><label for="ns">${ esc( __( 'New session', 'pikacart' ) ) }</label><input id="ns" placeholder="2027-28"></div><label class="check"><input type="checkbox" id="np" checked> ${ esc( __( 'Promote students to the next class', 'pikacart' ) ) }</label>`,
				confirm: __( 'Create new session', 'pikacart' ),
			} );
			if ( wrap ) {
				try {
					const res = await api( `projects/${ p.id }/new-session`, { method: 'POST', body: { session: wrap.querySelector( '#ns' ).value, promote: wrap.querySelector( '#np' ).checked } } );
					toast( res.message );
					ctx.navigate( `projects/${ res.project.id }/people` );
				} catch ( e ) {
					toast( e.message, 'error' );
				}
			}
		} else if ( a === 'del' ) {
			if ( await confirmDialog( { title: __( 'Delete this project?', 'pikacart' ), message: __( 'Its people move to the recycle bin for 30 days.', 'pikacart' ), confirm: __( 'Delete', 'pikacart' ), danger: true } ) ) {
				await api( `projects/${ p.id }/delete`, { method: 'POST' } ).then( ( r ) => toast( r.message ) ).catch( ( e ) => toast( e.message, 'error' ) );
				ctx.navigate( 'projects', true );
			}
		}
	}

	return () => {
		alive = false;
	};
}

function _n( single, plural, n ) {
	return window.wp.i18n._n( single, plural, n, 'pikacart' );
}
