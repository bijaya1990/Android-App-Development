/**
 * Pikacart organisation dashboard: shell, router and live trial timer.
 */

import { api } from './api.js';
import { esc, icon, toast, clock } from './ui.js';
import dashboard from './views/dashboard.js';
import onboarding from './views/onboarding.js';
import organisation from './views/organisation.js';
import subscription from './views/subscription.js';
import account from './views/account.js';
import support from './views/support.js';
import upcoming from './views/upcoming.js';

const { __, sprintf } = window.wp.i18n;
const cfg = window.PKC;

const store = {
	me: cfg.boot,
	offset: cfg.boot.state.now - Date.now() / 1000, // server time minus browser time
	cleanup: null,
	skippedWelcome: false,
};

const routes = {
	'': { title: __( 'Dashboard', 'pikacart' ), view: dashboard },
	welcome: { title: __( 'Welcome', 'pikacart' ), view: onboarding },
	create: { title: __( 'Create ID card', 'pikacart' ), view: upcoming( 'create' ) },
	projects: { title: __( 'My Projects', 'pikacart' ), view: upcoming( 'projects' ) },
	members: { title: __( 'Members', 'pikacart' ), view: upcoming( 'members' ) },
	print: { title: __( 'Print Sheets', 'pikacart' ), view: upcoming( 'print' ) },
	designs: { title: __( 'My Designs', 'pikacart' ), view: upcoming( 'designs' ) },
	organisation: { title: __( 'Organisation', 'pikacart' ), view: organisation },
	subscription: { title: __( 'Subscription', 'pikacart' ), view: subscription },
	support: { title: __( 'Support', 'pikacart' ), view: support },
	account: { title: __( 'Account', 'pikacart' ), view: account },
};

/* ---------- Shared context passed to every view ---------- */

const ctx = {
	get me() {
		return store.me;
	},
	setMe( me ) {
		store.me = me;
		store.offset = me.state.now - Date.now() / 1000;
		renderShell();
	},
	async reloadMe() {
		const res = await api( 'me' );
		ctx.setMe( res );
		return res;
	},
	navigate,
	url( path = '' ) {
		return cfg.app + ( path ? path.replace( /^\/+|\/+$/g, '' ) + '/' : '' );
	},
	now() {
		return Date.now() / 1000 + store.offset;
	},
	skipWelcome() {
		store.skippedWelcome = true;
	},
};

/* ---------- Router ---------- */

function currentPath() {
	const base = new URL( cfg.app ).pathname;
	let path = window.location.pathname;
	if ( path.indexOf( base ) === 0 ) {
		path = path.slice( base.length );
	}
	return path.replace( /^\/+|\/+$/g, '' );
}

function navigate( path, replace = false ) {
	const url = ctx.url( path );
	if ( replace ) {
		history.replaceState( {}, '', url );
	} else {
		history.pushState( {}, '', url );
	}
	render();
}

function render() {
	let path = currentPath();
	let key = path.split( '/' )[ 0 ];
	if ( ! routes[ key ] ) {
		key = '';
		path = '';
		history.replaceState( {}, '', ctx.url() );
	}

	// New accounts see the short onboarding first (they can skip it).
	if ( key === '' && ! store.me.org.onboarded && ! store.skippedWelcome ) {
		key = 'welcome';
		history.replaceState( {}, '', ctx.url( 'welcome' ) );
	}

	const route = routes[ key ];
	document.getElementById( 'page-title' ).textContent = route.title;
	document.title = `${ route.title } · ${ cfg.site }`;

	document.querySelectorAll( '.nav-link[data-route]' ).forEach( ( a ) => {
		const active = a.dataset.route === key;
		a.classList.toggle( 'is-active', active );
		if ( active ) {
			a.setAttribute( 'aria-current', 'page' );
		} else {
			a.removeAttribute( 'aria-current' );
		}
	} );

	if ( typeof store.cleanup === 'function' ) {
		store.cleanup();
	}
	const view = document.getElementById( 'view' );
	view.innerHTML = '';
	view.classList.remove( 'view-enter' );
	void view.offsetWidth; // restart the enter animation
	view.classList.add( 'view-enter' );
	store.cleanup = route.view( view, ctx, path.split( '/' ).slice( 1 ) ) || null;

	closeMenu();
	window.scrollTo( 0, 0 );
}

/* ---------- Shell: profile, status pill, banners ---------- */

function initials( name ) {
	return ( name || '?' ).split( /\s+/ ).filter( Boolean ).slice( 0, 2 ).map( ( w ) => w[ 0 ].toUpperCase() ).join( '' );
}

function renderShell() {
	const me = store.me;
	const avatar = document.getElementById( 'avatar' );
	if ( me.org.logo ) {
		avatar.innerHTML = `<img src="${ esc( me.org.logo ) }" alt="">`;
	} else {
		avatar.textContent = initials( me.org.name );
	}
	document.getElementById( 'profile-name' ).textContent = me.org.name;
	updatePill();
	renderBanners();
}

function updatePill() {
	const pill = document.getElementById( 'status-pill' );
	const s = store.me.state;
	pill.hidden = false;
	pill.className = `status-pill pill-${ s.status }`;

	if ( s.status === 'trial' ) {
		const left = s.trial_end - ctx.now();
		pill.innerHTML = `${ icon( 'clock' ) }<span class="pill-label">${ esc( __( 'Trial ends in', 'pikacart' ) ) }</span> <strong class="pill-time">${ esc( clock( left ) ) }</strong>`;
		pill.setAttribute( 'aria-label', sprintf( __( 'Free trial ends in %s', 'pikacart' ), clock( left ) ) );
	} else if ( s.status === 'active' ) {
		pill.innerHTML = `${ icon( 'check-circle' ) }<span>${ esc( sprintf( __( 'Active till %s', 'pikacart' ), s.period_end_text ) ) }</span>`;
	} else if ( s.status === 'cancelled' ) {
		pill.innerHTML = `${ icon( 'info' ) }<span>${ esc( sprintf( __( 'Ends %s', 'pikacart' ), s.period_end_text ) ) }</span>`;
	} else {
		const price = store.me.plan ? store.me.plan.price_text : '₹59';
		pill.innerHTML = `${ icon( 'lock' ) }<span>${ esc( sprintf( __( 'Subscribe %s/month', 'pikacart' ), price ) ) }</span>`;
	}
}

function renderBanners() {
	const me = store.me;
	const root = document.getElementById( 'banners' );
	const out = [];

	if ( me.state.status === 'expired' ) {
		const price = me.plan ? me.plan.price_text : '₹59';
		out.push( `<div class="banner banner-lock">${ icon( 'lock' ) }<div><strong>${ esc( __( 'Your free trial has ended', 'pikacart' ) ) }</strong><span>${ esc( sprintf( __( 'Your designs and data are saved. Subscribe for %s per month to keep creating, downloading and printing cards.', 'pikacart' ), price ) ) }</span></div><a class="btn btn-accent btn-sm" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( sprintf( __( 'Subscribe for %s per month', 'pikacart' ), price ) ) }</a></div>` );
	} else if ( me.state.status === 'trial' ) {
		out.push( `<div class="banner banner-trial">${ icon( 'sparkles' ) }<div><span>${ esc( __( 'You are on the free trial. Everything works; downloads and prints carry a "PIKACART TRIAL" watermark.', 'pikacart' ) ) }</span></div><a class="btn btn-sm" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( __( 'Remove watermark', 'pikacart' ) ) }</a></div>` );
	}
	if ( ! me.user.verified ) {
		out.push( `<div class="banner banner-info">${ icon( 'mail' ) }<div><span>${ esc( sprintf( __( 'Please verify your email. We sent a link to %s.', 'pikacart' ), me.user.email ) ) }</span></div><button type="button" class="btn btn-sm" data-action="resend-verify">${ esc( __( 'Send again', 'pikacart' ) ) }</button></div>` );
	}
	root.innerHTML = out.join( '' );
}

/* ---------- Live trial countdown ---------- */

let refreshing = false;
function tick() {
	const s = store.me.state;
	if ( s.status === 'trial' ) {
		const left = s.trial_end - ctx.now();
		const time = document.querySelector( '#status-pill .pill-time' );
		if ( time ) {
			time.textContent = clock( left );
		}
		document.querySelectorAll( '[data-countdown]' ).forEach( ( el ) => {
			el.textContent = clock( left );
		} );
		if ( left <= 0 && ! refreshing ) {
			refreshing = true;
			ctx.reloadMe()
				.then( () => {
					if ( store.me.state.status !== 'trial' ) {
						toast( __( 'Your free trial has ended. Your work is saved.', 'pikacart' ), 'error' );
						render();
					}
				} )
				.catch( () => {} )
				.finally( () => {
					setTimeout( () => {
						refreshing = false;
					}, 30000 );
				} );
		}
	}
}

/* ---------- Menu and global clicks ---------- */

function openMenu() {
	document.body.classList.add( 'menu-open' );
}
function closeMenu() {
	document.body.classList.remove( 'menu-open' );
	const menu = document.getElementById( 'profile-menu' );
	menu.hidden = true;
	document.querySelector( '.profile-btn' ).setAttribute( 'aria-expanded', 'false' );
}

document.addEventListener( 'click', async ( e ) => {
	const action = e.target.closest( '[data-action]' );
	if ( action ) {
		const a = action.dataset.action;
		if ( a === 'open-menu' ) {
			openMenu();
		} else if ( a === 'close-menu' ) {
			closeMenu();
		} else if ( a === 'toggle-profile' ) {
			const menu = document.getElementById( 'profile-menu' );
			menu.hidden = ! menu.hidden;
			action.setAttribute( 'aria-expanded', String( ! menu.hidden ) );
			e.stopPropagation();
			return;
		} else if ( a === 'resend-verify' ) {
			action.disabled = true;
			try {
				const res = await api( 'me/resend-verification', { method: 'POST' } );
				toast( res.message );
			} catch ( err ) {
				toast( err.message, 'error' );
			}
			action.disabled = false;
		}
	}

	const link = e.target.closest( 'a[data-route], a[data-link]' );
	if ( link && link.origin === window.location.origin && link.pathname.indexOf( new URL( cfg.app ).pathname ) === 0 && ! e.metaKey && ! e.ctrlKey && ! e.shiftKey && link.target !== '_blank' ) {
		e.preventDefault();
		const base = new URL( cfg.app ).pathname;
		navigate( link.pathname.slice( base.length ) );
		return;
	}

	if ( ! e.target.closest( '.profile' ) ) {
		const menu = document.getElementById( 'profile-menu' );
		if ( ! menu.hidden ) {
			menu.hidden = true;
			document.querySelector( '.profile-btn' ).setAttribute( 'aria-expanded', 'false' );
		}
	}
} );

document.addEventListener( 'keydown', ( e ) => {
	if ( e.key === 'Escape' ) {
		closeMenu();
	}
} );

window.addEventListener( 'popstate', render );

renderShell();
render();
setInterval( tick, 1000 );
