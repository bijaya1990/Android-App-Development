/**
 * Live updates for the dashboard: notification bell, unread support badge,
 * pop-up toasts for new notifications and the count in the browser tab.
 * Polls a tiny endpoint every 20 seconds (60 when the tab is hidden).
 */

import { api } from './api.js';
import { esc, icon, toast } from './ui.js';

const { __ } = window.wp.i18n;

const TYPE_ICON = { support: 'help', payment: 'receipt', plan: 'card', notice: 'bell', account: 'user' };

let ctxRef = null;
let latest = 0;
let timer = null;
const baseTitle = () => document.title.replace( /^\(\d+\)\s*/, '' );
const listeners = new Set();

/** Views can listen for pulses (the support screen refreshes its list). */
export function onPulse( fn ) {
	listeners.add( fn );
	return () => listeners.delete( fn );
}

function setCounts( notifications, support ) {
	const bell = document.getElementById( 'bell-count' );
	bell.hidden = ! notifications;
	bell.textContent = notifications > 9 ? '9+' : String( notifications );
	const badge = document.getElementById( 'support-badge' );
	badge.hidden = ! support;
	badge.textContent = String( support );
	const total = notifications;
	document.title = ( total ? `(${ total }) ` : '' ) + baseTitle();
}

async function pulse() {
	try {
		const d = await api( `pulse?after=${ latest }` );
		setCounts( d.notifications, d.support );
		( d.new || [] ).forEach( ( n ) => {
			// Do not pop a toast for the chat the customer is looking at right now.
			if ( n.link && window.location.pathname.endsWith( `/${ n.link }/` ) ) {
				return;
			}
			toast( n.title, 'info' );
		} );
		if ( d.latest ) {
			latest = d.latest;
		}
		listeners.forEach( ( fn ) => fn( d ) );
	} catch ( e ) {
		// Network blips are fine; try again on the next tick.
	}
}

function schedule() {
	clearTimeout( timer );
	timer = setTimeout( async () => {
		await pulse();
		schedule();
	}, document.visibilityState === 'visible' ? 20000 : 60000 );
}

async function openPanel() {
	const panel = document.getElementById( 'bell-panel' );
	const list = document.getElementById( 'bell-list' );
	list.innerHTML = `<p class="bell-empty">${ esc( __( 'Loading…', 'pikacart' ) ) }</p>`;
	try {
		const d = await api( 'notifications' );
		list.innerHTML = d.items.length
			? d.items.map( ( n ) => `<a class="bell-item ${ n.read ? '' : 'is-unread' }" href="${ esc( ctxRef.url( n.link ) ) }" data-link data-nid="${ n.id }">
				<span class="bell-icon bell-${ esc( n.type ) }">${ icon( TYPE_ICON[ n.type ] || 'bell' ) }</span>
				<span class="bell-text"><strong>${ esc( n.title ) }</strong>${ n.body ? `<span>${ esc( n.body ) }</span>` : '' }<small>${ esc( n.ago ) } ${ esc( __( 'ago', 'pikacart' ) ) }</small></span>
			</a>` ).join( '' )
			: `<p class="bell-empty">${ icon( 'bell' ) }<br>${ esc( __( 'You are all caught up.', 'pikacart' ) ) }</p>`;
	} catch ( e ) {
		list.innerHTML = `<p class="bell-empty">${ esc( e.message ) }</p>`;
	}
	panel.hidden = false;
}

export function closeBell() {
	const panel = document.getElementById( 'bell-panel' );
	if ( panel && ! panel.hidden ) {
		panel.hidden = true;
		document.querySelector( '.bell-btn' ).setAttribute( 'aria-expanded', 'false' );
	}
}

export function initLive( ctx ) {
	ctxRef = ctx;
	latest = ctx.me.live.latest;
	setCounts( ctx.me.live.notifications, ctx.me.live.support );

	document.addEventListener( 'click', async ( e ) => {
		const toggle = e.target.closest( '[data-action="toggle-bell"]' );
		if ( toggle ) {
			e.stopPropagation();
			const panel = document.getElementById( 'bell-panel' );
			if ( panel.hidden ) {
				toggle.setAttribute( 'aria-expanded', 'true' );
				await openPanel();
			} else {
				closeBell();
			}
			return;
		}
		if ( e.target.closest( '[data-action="read-all"]' ) ) {
			e.stopPropagation();
			const d = await api( 'notifications/read', { method: 'POST', body: {} } ).catch( () => null );
			if ( d ) {
				document.querySelectorAll( '.bell-item.is-unread' ).forEach( ( x ) => x.classList.remove( 'is-unread' ) );
				setCounts( 0, Number( document.getElementById( 'support-badge' ).textContent ) || 0 );
			}
			return;
		}
		const item = e.target.closest( '[data-nid]' );
		if ( item ) {
			api( 'notifications/read', { method: 'POST', body: { ids: [ Number( item.dataset.nid ) ] } } )
				.then( () => pulse() )
				.catch( () => {} );
			closeBell();
			return;
		}
		if ( ! e.target.closest( '.bell' ) ) {
			closeBell();
		}
	} );

	document.addEventListener( 'visibilitychange', () => {
		if ( document.visibilityState === 'visible' ) {
			pulse();
			schedule();
		}
	} );
	schedule();
}

/** Ask for an immediate refresh (e.g. after reading a chat). */
export function refreshLive() {
	return pulse();
}
