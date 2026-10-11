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
import create from './views/create.js';
import projects from './views/projects.js';
import designs from './views/designs.js';
import organisations from './views/organisations.js';
import { orgMark, orgName, orgMenuList, switchOrg, activeOrg } from './orgs.js';
import { initLive, closeBell } from './live.js';

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
	organisations: { title: __( 'Organisations', 'pikacart' ), view: organisations },
	create: { title: __( 'Create ID card', 'pikacart' ), view: create },
	projects: { title: __( 'My Projects', 'pikacart' ), view: projects( 'projects' ) },
	members: { title: __( 'Members', 'pikacart' ), view: projects( 'members' ) },
	print: { title: __( 'Print Sheets', 'pikacart' ), view: projects( 'print' ) },
	designs: { title: __( 'My Designs', 'pikacart' ), view: designs },
	organisation: { title: __( 'Organisation details', 'pikacart' ), view: organisation },
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

	// A design picked on the public website before logging in or registering.
	if ( key !== 'welcome' && pendingDesign() ) {
		return;
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
	closeOrgMenu();
	window.scrollTo( 0, 0 );
}

function pendingDesign() {
	if ( ! store.me.client ) {
		return false; // Keep it until the first organisation is added.
	}
	let use = null;
	try {
		use = JSON.parse( window.localStorage.getItem( 'pkc_use' ) || 'null' );
		window.localStorage.removeItem( 'pkc_use' );
	} catch ( e ) {
		return false;
	}
	if ( ! use || ! use.tpl || ! use.sub || Date.now() - ( use.at || 0 ) > 86400000 ) {
		return false;
	}
	const view = document.getElementById( 'view' );
	view.innerHTML = `<div class="card center"><p>${ esc( __( 'Setting up the design you chose…', 'pikacart' ) ) }</p></div>`;
	( async () => {
		try {
			const res = await api( 'projects', { method: 'POST', body: { subtype_id: use.sub, template_id: use.tpl } } );
			const body = { orientation: use.o === 'landscape' ? 'landscape' : 'portrait' };
			if ( use.pal ) {
				body.palette = { id: use.pal };
			}
			await api( `projects/${ res.project.id }`, { method: 'POST', body } );
			toast( __( 'Your chosen design is ready. Now choose the size and colours.', 'pikacart' ) );
			navigate( `projects/${ res.project.id }/setup` );
		} catch ( err ) {
			toast( err.message, 'error' );
			navigate( 'create' );
		}
	} )();
	return true;
}

/* ---------- Shell: profile, status pill, banners ---------- */

function initials( name ) {
	return ( name || '?' ).split( /\s+/ ).filter( Boolean ).slice( 0, 2 ).map( ( w ) => w[ 0 ].toUpperCase() ).join( '' );
}

function renderShell() {
	const me = store.me;
	const accountName = ( me.account && me.account.name ) || me.org.name;
	document.getElementById( 'avatar' ).textContent = initials( accountName );
	document.getElementById( 'profile-name' ).textContent = accountName;
	renderOrgSwitch();
	updatePill();
	renderBanners();
}

/* ---------- Organisation switcher (top bar) ---------- */

function renderOrgSwitch() {
	const box = document.getElementById( 'org-switch' );
	if ( ! box ) {
		return;
	}
	const me = store.me;
	const cur = activeOrg( me );
	const open = box.classList.contains( 'is-open' );
	if ( ! cur ) {
		box.innerHTML = `<a class="org-switch-btn is-empty" href="${ esc( ctx.url( 'organisations/new' ) ) }" data-link>${ icon( 'plus' ) }<span class="org-switch-t"><small>${ esc( __( 'Making cards for', 'pikacart' ) ) }</small><b>${ esc( __( 'Add organisation', 'pikacart' ) ) }</b></span></a>`;
		return;
	}
	box.innerHTML = `<button type="button" class="org-switch-btn" data-action="toggle-org" aria-haspopup="true" aria-expanded="${ open }">
			${ orgMark( cur, 'sm' ) }
			<span class="org-switch-t"><small>${ esc( __( 'Working for', 'pikacart' ) ) }</small><b>${ esc( orgName( cur ) ) }</b></span>
			${ icon( 'chevron-down' ) }
		</button>
		<div class="org-menu" ${ open ? '' : 'hidden' } role="menu">
			${ me.clients.length > 6 ? `<div class="org-menu-search">${ icon( 'search' ) }<input type="search" data-org-q placeholder="${ esc( __( 'Search organisations…', 'pikacart' ) ) }" aria-label="${ esc( __( 'Search organisations', 'pikacart' ) ) }"></div>` : '' }
			<div class="org-menu-list" data-org-list>${ orgMenuList( me ) }</div>
			<div class="org-menu-foot">
				<a href="${ esc( ctx.url( 'organisations/new' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'Add organisation', 'pikacart' ) ) }</a>
				<a href="${ esc( ctx.url( 'organisations' ) ) }" data-link>${ icon( 'building' ) }${ esc( __( 'All organisations', 'pikacart' ) ) }</a>
			</div>
		</div>`;
}

function closeOrgMenu() {
	const box = document.getElementById( 'org-switch' );
	if ( box && box.classList.contains( 'is-open' ) ) {
		box.classList.remove( 'is-open' );
		renderOrgSwitch();
	}
}

/** After switching, show the same kind of screen for the other organisation. */
function afterSwitch() {
	const key = currentPath().split( '/' )[ 0 ];
	const home = { projects: 'projects', members: 'members', print: 'print', create: 'create/types', organisation: 'organisation', organisations: 'organisations' };
	navigate( key in home ? home[ key ] : '', false );
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
	} else if ( s.status === 'free' ) {
		pill.innerHTML = `${ icon( 'sparkles' ) }<span class="pill-label">${ esc( __( 'Free plan', 'pikacart' ) ) } ·</span> <strong>${ esc( __( 'Upgrade', 'pikacart' ) ) }</strong>`;
		pill.setAttribute( 'aria-label', __( 'Free plan. Upgrade to remove the watermark.', 'pikacart' ) );
	} else {
		const price = store.me.plan ? store.me.plan.price_text : '₹59';
		pill.innerHTML = `${ icon( 'lock' ) }<span>${ esc( sprintf( __( 'Subscribe %s/month', 'pikacart' ), price ) ) }</span>`;
	}
}

function renderBanners() {
	const me = store.me;
	const root = document.getElementById( 'banners' );
	const out = [];

	if ( me.view_as ) {
		out.push( `<div class="banner banner-view">${ icon( 'shield' ) }<div><strong>${ esc( sprintf( __( 'Support view: %s', 'pikacart' ), me.org.name ) ) }</strong><span>${ esc( __( 'You are seeing this account as the customer sees it. Changes are switched off.', 'pikacart' ) ) }</span></div><a class="btn btn-sm" href="${ esc( me.view_as.exit ) }">${ esc( __( 'Exit support view', 'pikacart' ) ) }</a></div>` );
	}
	if ( me.state.status === 'expired' ) {
		const price = me.plan ? me.plan.price_text : '₹59';
		out.push( `<div class="banner banner-lock">${ icon( 'lock' ) }<div><strong>${ esc( __( 'Your free trial has ended', 'pikacart' ) ) }</strong><span>${ esc( sprintf( __( 'Your designs and data are saved. Subscribe for %s per month to keep creating, downloading and printing cards.', 'pikacart' ), price ) ) }</span></div><a class="btn btn-accent btn-sm" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( sprintf( __( 'Subscribe for %s per month', 'pikacart' ), price ) ) }</a></div>` );
	} else if ( me.state.status === 'free' ) {
		const price = me.plan ? me.plan.price_text : '₹59';
		out.push( `<div class="banner banner-trial">${ icon( 'sparkles' ) }<div><span>${ esc( sprintf( __( 'Free plan: everything works, free forever. Downloaded cards carry a "%1$s" watermark. Upgrade for %2$s/month for clean, professional cards.', 'pikacart' ), me.watermark.text, price ) ) }</span></div><a class="btn btn-sm" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( __( 'Remove watermark', 'pikacart' ) ) }</a></div>` );
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
	closeBell();
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
		} else if ( a === 'toggle-org' ) {
			const box = document.getElementById( 'org-switch' );
			box.classList.toggle( 'is-open' );
			renderOrgSwitch();
			const q = box.querySelector( '[data-org-q]' );
			if ( q ) {
				q.focus();
			}
			if ( box.classList.contains( 'is-open' ) ) {
				// Fresh project and card counts for the list.
				api( 'me' ).then( ( me ) => {
					store.me = me;
					const list = document.querySelector( '[data-org-list]' );
					const term = box.querySelector( '[data-org-q]' );
					if ( list ) {
						list.innerHTML = orgMenuList( me, term ? term.value : '' );
					}
				} ).catch( () => {} );
			}
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

	const pick = e.target.closest( '.org-menu [data-org]' );
	if ( pick ) {
		const id = Number( pick.dataset.org );
		closeOrgMenu();
		if ( id !== store.me.client && await switchOrg( ctx, id ) ) {
			afterSwitch();
		}
		return;
	}
	if ( ! e.target.closest( '.org-switch' ) ) {
		closeOrgMenu();
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
		closeOrgMenu();
	}
} );

document.addEventListener( 'input', ( e ) => {
	if ( e.target.matches( '[data-org-q]' ) ) {
		const list = document.querySelector( '[data-org-list]' );
		if ( list ) {
			list.innerHTML = orgMenuList( store.me, e.target.value );
		}
	}
} );

window.addEventListener( 'popstate', render );

renderShell();
initLive( ctx );
render();
setInterval( tick, 1000 );
