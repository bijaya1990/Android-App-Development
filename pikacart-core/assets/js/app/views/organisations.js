/**
 * Organisations: every school, college or office the account makes cards for,
 * each as its own folder with its own profile, projects and people.
 * Routes: organisations, organisations/new (add one with the setup wizard).
 */

import { api } from '../api.js';
import { esc, icon, toast, confirmDialog, withLoading, illustration } from '../ui.js';
import { orgMark, orgName, orgStats, switchOrg } from '../orgs.js';
import onboarding from './onboarding.js';

const { __, _n, sprintf } = window.wp.i18n;

export default function organisations( el, ctx, params ) {
	if ( params[ 0 ] === 'new' ) {
		// organisations/new/card: added while creating an ID card, so go on to the card types.
		return onboarding( el, ctx, [], { mode: 'new', next: params[ 1 ] === 'card' ? 'create/types' : params[ 1 ] === 'cert' ? 'certificate/types' : '' } );
	}

	let query = '';

	function cardHTML( o ) {
		const on = o.id === ctx.me.client;
		return `<article class="card org-folder${ on ? ' is-on' : '' }" data-id="${ o.id }">
			<div class="org-folder-top">
				${ orgMark( o, 'lg' ) }
				${ on ? `<span class="badge badge-active">${ icon( 'check' ) }${ esc( __( 'Working now', 'pikacart' ) ) }</span>` : '' }
			</div>
			<h3 class="org-folder-name">${ esc( orgName( o ) ) }</h3>
			<p class="org-folder-addr">${ esc( ( o.address || '' ).split( '\n' )[ 0 ] || __( 'No address yet', 'pikacart' ) ) }</p>
			<p class="org-folder-stats">${ icon( 'folder' ) }${ esc( orgStats( o ) ) }</p>
			${ o.last_work ? `<p class="org-folder-when">${ esc( sprintf( __( 'Last worked %s ago', 'pikacart' ), o.last_work ) ) }</p>` : '' }
			<div class="org-folder-actions">
				<button type="button" class="btn ${ on ? '' : 'btn-primary' } btn-sm" data-open="${ o.id }">${ icon( 'folder' ) }${ esc( on ? __( 'Open projects', 'pikacart' ) : __( 'Open & work', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-new="${ o.id }">${ icon( 'plus' ) }${ esc( __( 'New card', 'pikacart' ) ) }</button>
				<button type="button" class="icon-btn" data-edit="${ o.id }" title="${ esc( __( 'Edit details', 'pikacart' ) ) }" aria-label="${ esc( __( 'Edit details', 'pikacart' ) ) }">${ icon( 'edit' ) }</button>
				<button type="button" class="icon-btn" data-del="${ o.id }" title="${ esc( __( 'Delete', 'pikacart' ) ) }" aria-label="${ esc( __( 'Delete', 'pikacart' ) ) }">${ icon( 'trash' ) }</button>
			</div>
		</article>`;
	}

	function draw() {
		const list = ctx.me.clients;
		const term = query.trim().toLowerCase();
		const shown = list.filter( ( o ) => ! term || orgName( o ).toLowerCase().includes( term ) || ( o.address || '' ).toLowerCase().includes( term ) );
		const totalCards = list.reduce( ( n, o ) => n + o.cards, 0 );
		el.innerHTML = `<div class="section-head-row">
				<div>
					<h2 class="h2">${ esc( __( 'Organisations', 'pikacart' ) ) }</h2>
					<p class="muted">${ esc( list.length ? [ sprintf( _n( '%d organisation', '%d organisations', list.length, 'pikacart' ), list.length ), sprintf( _n( '%s card in total', '%s cards in total', totalCards, 'pikacart' ), totalCards.toLocaleString() ) ].join( ' · ' ) + '. ' + __( 'Each keeps its own details, projects and people.', 'pikacart' ) : __( 'Add every school, college or office you make cards for. Each keeps its own details, projects and people.', 'pikacart' ) ) }</p>
				</div>
				<a class="btn btn-primary" href="${ esc( ctx.url( 'organisations/new' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'Add organisation', 'pikacart' ) ) }</a>
			</div>
			${ list.length > 6 ? `<div class="org-search"><span>${ icon( 'search' ) }</span><input type="search" data-q value="${ esc( query ) }" placeholder="${ esc( __( 'Search organisations…', 'pikacart' ) ) }" aria-label="${ esc( __( 'Search organisations', 'pikacart' ) ) }"></div>` : '' }
			${ list.length ? `<div class="org-grid">
				${ shown.map( cardHTML ).join( '' ) }
				${ ! term ? `<a class="org-folder org-add" href="${ esc( ctx.url( 'organisations/new' ) ) }" data-link>${ icon( 'plus' ) }<b>${ esc( __( 'Add organisation', 'pikacart' ) ) }</b><small>${ esc( __( 'School, college, office, hospital…', 'pikacart' ) ) }</small></a>` : '' }
			</div>
			${ term && ! shown.length ? `<p class="muted center">${ esc( __( 'No organisation matches your search.', 'pikacart' ) ) }</p>` : '' }` : `<div class="card org-empty">
				${ illustration( 'cards' ) }
				<h3>${ esc( __( 'Add your first organisation', 'pikacart' ) ) }</h3>
				<p>${ esc( __( 'Making cards for a school, college or office? Add it once with its name, logo, address and signature. Its cards, people and files stay in their own folder.', 'pikacart' ) ) }</p>
				<a class="btn btn-primary btn-lg" href="${ esc( ctx.url( 'organisations/new' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'Add organisation', 'pikacart' ) ) }</a>
			</div>` }`;
		bind();
	}

	function bind() {
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
		const go = ( sel, path ) => el.querySelectorAll( `[${ sel }]` ).forEach( ( b ) => b.addEventListener( 'click', () => withLoading( b, async () => {
			if ( await switchOrg( ctx, Number( b.getAttribute( sel ) ), true ) ) {
				ctx.navigate( path );
			}
		} ) ) );
		go( 'data-open', 'projects' );
		go( 'data-new', 'create/types' );
		go( 'data-edit', 'organisation' );
		el.querySelectorAll( '[data-del]' ).forEach( ( b ) => b.addEventListener( 'click', async () => {
			const o = ctx.me.clients.find( ( x ) => x.id === Number( b.dataset.del ) );
			const ok = await confirmDialog( {
				title: sprintf( __( 'Delete %s?', 'pikacart' ), orgName( o ) ),
				message: o.projects
					? sprintf( _n( 'Its %1$d project and %2$d cards will be deleted too. People stay in the recycle bin for 30 days.', 'Its %1$d projects and %2$d cards will be deleted too. People stay in the recycle bin for 30 days.', o.projects, 'pikacart' ), o.projects, o.cards )
					: __( 'This organisation has no projects yet.', 'pikacart' ),
				confirm: __( 'Delete organisation', 'pikacart' ),
				danger: true,
			} );
			if ( ! ok ) {
				return;
			}
			try {
				const res = await api( `clients/${ o.id }/delete`, { method: 'POST' } );
				ctx.setMe( res.me );
				toast( res.message );
				draw();
			} catch ( e ) {
				toast( e.message, 'error' );
			}
		} ) );
	}

	draw();
}
