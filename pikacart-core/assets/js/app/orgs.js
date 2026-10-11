/**
 * Organisations of an account (schools, colleges, offices...): shared helpers
 * for the top-bar switcher, the Organisations page and the create flow.
 */

import { api } from './api.js';
import { esc, icon, toast } from './ui.js';
import { waitForAllSaves } from './autosave.js';

const { __, _n, sprintf } = window.wp.i18n;

export function initials( name ) {
	return ( name || '?' ).split( /\s+/ ).filter( Boolean ).slice( 0, 2 ).map( ( w ) => w[ 0 ].toUpperCase() ).join( '' ) || '?';
}

/** Logo, or coloured initials when there is no logo yet. */
export function orgMark( o, cls = '' ) {
	if ( o && o.logo ) {
		return `<span class="org-mark ${ cls }"><img src="${ esc( o.logo ) }" alt=""></span>`;
	}
	const hue = ( ( o && o.id ) || 1 ) * 47 % 360;
	return `<span class="org-mark is-text ${ cls }" style="--h:${ hue }">${ esc( initials( o && o.name ) ) }</span>`;
}

export function orgName( o ) {
	return o && o.name ? o.name : __( 'New organisation', 'pikacart' );
}

export function orgStats( o ) {
	return [
		sprintf( _n( '%d project', '%d projects', o.projects, 'pikacart' ), o.projects ),
		sprintf( _n( '%d card', '%d cards', o.cards, 'pikacart' ), o.cards ),
	].join( ' · ' );
}

export function activeOrg( me ) {
	return me.clients.find( ( c ) => c.id === me.client ) || null;
}

/** Close the current organisation's work and open another one. */
export async function switchOrg( ctx, id, quiet = false ) {
	if ( id === ctx.me.client ) {
		return true;
	}
	await waitForAllSaves();
	try {
		const res = await api( 'clients/switch', { method: 'POST', body: { id } } );
		ctx.setMe( res.me );
		if ( ! quiet ) {
			toast( res.message );
		}
		return true;
	} catch ( e ) {
		toast( e.message, 'error' );
		return false;
	}
}

/** Searchable list of organisations used by the switcher menu. */
export function orgMenuList( me, q = '' ) {
	const term = q.trim().toLowerCase();
	const list = me.clients.filter( ( c ) => ! term || orgName( c ).toLowerCase().includes( term ) );
	if ( ! list.length ) {
		return `<p class="org-menu-empty">${ esc( __( 'No organisation matches.', 'pikacart' ) ) }</p>`;
	}
	return list.map( ( c ) => `<button type="button" class="org-menu-item${ c.id === me.client ? ' is-on' : '' }" data-org="${ c.id }" role="menuitemradio" aria-checked="${ c.id === me.client }">
			${ orgMark( c, 'sm' ) }
			<span class="org-menu-t"><b>${ esc( orgName( c ) ) }</b><small>${ esc( orgStats( c ) ) }</small></span>
			${ c.id === me.client ? icon( 'check' ) : '' }
		</button>` ).join( '' );
}
