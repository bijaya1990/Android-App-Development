/**
 * Autosave for projects: merges changes, saves after a short pause, shows
 * "Saving… / Saved / Offline", keeps a copy in the browser if the network is
 * down and retries, and warns before leaving with unsaved changes.
 */

import { api } from './api.js';

const { __ } = window.wp.i18n;

/** Saves still running for a project after its screen was closed. */
const inflight = new Map();

/** Wait until all projects' changes have been saved (lists, dashboard). */
export async function waitForAllSaves() {
	await Promise.all( [ ...inflight.values() ].map( ( p ) => p.catch( () => {} ) ) );
}

/** Wait until every change of a project has been saved (used before reloading it). */
export async function waitForSave( projectId ) {
	const p = inflight.get( Number( projectId ) );
	if ( p ) {
		try {
			await p;
		} catch ( e ) {
			// A failed save keeps its backup in the browser and is restored on load.
		}
	}
}

export function createAutosave( projectId, onSaved ) {
	let pending = {};
	let timer = null;
	let saving = false;
	let current = null; // the save request in progress
	let retry = 0;
	const key = `pkc_draft_${ projectId }`;
	const listeners = new Set();
	let state = 'saved';

	function setState( s ) {
		state = s;
		listeners.forEach( ( fn ) => fn( s ) );
	}

	function backup() {
		try {
			localStorage.setItem( key, JSON.stringify( pending ) );
		} catch ( e ) {}
	}

	function clearBackup() {
		try {
			localStorage.removeItem( key );
		} catch ( e ) {}
	}

	function flush() {
		clearTimeout( timer );
		if ( saving || ! Object.keys( pending ).length ) {
			return current || Promise.resolve();
		}
		current = send().finally( () => {
			current = null;
		} );
		return current;
	}

	async function send() {
		const body = pending;
		pending = {};
		saving = true;
		setState( 'saving' );
		try {
			const res = await api( `projects/${ projectId }`, { method: 'POST', body } );
			retry = 0;
			saving = false;
			if ( Object.keys( pending ).length ) {
				schedule( 300 );
			} else {
				clearBackup();
				setState( 'saved' );
			}
			onSaved && onSaved( res );
		} catch ( e ) {
			saving = false;
			// Put the changes back (newer edits win) and try again later.
			pending = Object.assign( {}, body, pending );
			backup();
			if ( e.code === 'offline' || e.code === 'error' ) {
				setState( 'offline' );
				retry = Math.min( retry + 1, 6 );
				schedule( 2000 * Math.pow( 2, retry - 1 ) );
			} else {
				setState( 'error' );
				throw e;
			}
		}
	}

	/** Save everything, including changes made while a save was running. */
	async function flushAll() {
		// Wait for the request in progress, then send anything left. A failed save
		// stops here: its changes stay in the browser backup and are retried.
		for ( let i = 0; i < 20; i++ ) {
			if ( current ) {
				await current;
				if ( state === 'offline' || state === 'error' ) {
					return; // Retried later; the backup keeps the changes safe.
				}
				continue;
			}
			if ( ! Object.keys( pending ).length ) {
				return;
			}
			await flush();
			if ( state === 'offline' || state === 'error' ) {
				return;
			}
		}
	}

	function schedule( ms = 1200 ) {
		clearTimeout( timer );
		timer = setTimeout( () => flush().catch( () => {} ), ms );
	}

	const beforeUnload = ( e ) => {
		if ( Object.keys( pending ).length || saving ) {
			e.preventDefault();
			e.returnValue = __( 'You have unsaved changes.', 'pikacart' );
		}
	};
	window.addEventListener( 'beforeunload', beforeUnload );
	window.addEventListener( 'online', () => schedule( 200 ) );

	return {
		/** Queue a change, e.g. { palette: {...} }. */
		set( patch ) {
			Object.assign( pending, patch );
			backup();
			setState( 'dirty' );
			schedule();
		},
		flush: flushAll,
		get state() {
			return state;
		},
		onState( fn ) {
			listeners.add( fn );
			fn( state );
			return () => listeners.delete( fn );
		},
		/** Changes left in the browser from an earlier failed save. */
		restore() {
			try {
				const saved = JSON.parse( localStorage.getItem( key ) || 'null' );
				return saved && Object.keys( saved ).length ? saved : null;
			} catch ( e ) {
				return null;
			}
		},
		dispose() {
			clearTimeout( timer );
			const p = flushAll();
			const id = Number( projectId );
			inflight.set( id, p );
			p.catch( () => {} ).finally( () => {
				if ( inflight.get( id ) === p ) {
					inflight.delete( id );
				}
			} );
			window.removeEventListener( 'beforeunload', beforeUnload );
		},
	};
}
