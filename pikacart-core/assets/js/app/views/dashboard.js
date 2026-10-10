/**
 * Dashboard: welcome, plan status, card counts, quick actions, setup checklist, notices.
 */

import { esc, icon, clock, emptyState } from '../ui.js';
import { api } from '../api.js';
import { waitForAllSaves } from '../autosave.js';
import { savedStep, stepLabel } from './project.js';

const { __, _n, sprintf } = window.wp.i18n;

function greeting() {
	const h = new Date().getHours();
	if ( h < 12 ) {
		return __( 'Good morning', 'pikacart' );
	}
	if ( h < 17 ) {
		return __( 'Good afternoon', 'pikacart' );
	}
	return __( 'Good evening', 'pikacart' );
}

export default function dashboard( el, ctx ) {
	const me = ctx.me;
	const s = me.state;
	const first = ( me.user.name || me.org.contact_name || '' ).split( ' ' )[ 0 ];
	const price = me.plan ? me.plan.price_text : '₹59';

	let planCard = '';
	if ( s.status === 'free' ) {
		planCard = `<div class="card plan-card plan-trial">
			<div class="plan-card-top">${ icon( 'sparkles' ) }<span>${ esc( __( 'Free plan', 'pikacart' ) ) }</span></div>
			<div class="big-date plan-free-title">${ esc( __( 'Free forever', 'pikacart' ) ) }</div>
			<p>${ esc( sprintf( __( 'Every feature works. Downloaded cards carry "%s". Upgrade for clean, professional cards.', 'pikacart' ), me.watermark.text ) ) }</p>
			<a class="btn btn-primary" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( sprintf( __( 'Remove watermark · %s/month', 'pikacart' ), price ) ) }</a>
		</div>`;
	} else if ( s.status === 'trial' ) {
		planCard = `<div class="card plan-card plan-trial">
			<div class="plan-card-top">${ icon( 'clock' ) }<span>${ esc( __( 'Free trial', 'pikacart' ) ) }</span></div>
			<div class="big-timer" data-countdown>${ esc( clock( s.trial_end - ctx.now() ) ) }</div>
			<p>${ esc( __( 'left in your free trial. Every feature is open; exports carry a watermark.', 'pikacart' ) ) }</p>
			<a class="btn btn-primary" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( sprintf( __( 'Subscribe for %s per month', 'pikacart' ), price ) ) }</a>
		</div>`;
	} else if ( s.status === 'active' || s.status === 'cancelled' ) {
		planCard = `<div class="card plan-card plan-active">
			<div class="plan-card-top">${ icon( 'check-circle' ) }<span>${ esc( s.status === 'active' ? __( 'Plan active', 'pikacart' ) : __( 'Autopay cancelled', 'pikacart' ) ) }</span></div>
			<div class="big-date">${ esc( s.period_end_text ) }</div>
			<p>${ esc( s.status === 'active' ? ( s.autopay ? __( 'Renews automatically on this date.', 'pikacart' ) : __( 'Your plan is paid until this date.', 'pikacart' ) ) : __( 'You keep full access until this date.', 'pikacart' ) ) }</p>
			<a class="btn" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( __( 'Manage subscription', 'pikacart' ) ) }</a>
		</div>`;
	} else {
		planCard = `<div class="card plan-card plan-expired">
			<div class="plan-card-top">${ icon( 'lock' ) }<span>${ esc( __( 'Trial ended', 'pikacart' ) ) }</span></div>
			<div class="big-date">${ esc( price ) }<small>${ esc( __( '/ month', 'pikacart' ) ) }</small></div>
			<p>${ esc( __( 'Your work is saved. Subscribe to continue creating, downloading and printing.', 'pikacart' ) ) }</p>
			<a class="btn btn-accent" href="${ esc( ctx.url( 'subscription' ) ) }" data-link>${ esc( __( 'Subscribe now', 'pikacart' ) ) }</a>
		</div>`;
	}

	const by = me.stats.by_status;
	const stats = [
		[ __( 'Total cards', 'pikacart' ), me.stats.cards, 'idcard', 'brand' ],
		[ __( 'Projects', 'pikacart' ), me.stats.projects, 'folder', 'brand' ],
		[ __( 'Ready', 'pikacart' ), by.ready, 'check-circle', 'green' ],
		[ __( 'Printed', 'pikacart' ), by.printed, 'printer', 'blue' ],
		[ __( 'Drafts', 'pikacart' ), by.draft, 'edit', 'grey' ],
		[ __( 'Expired', 'pikacart' ), by.expired + by.cancelled, 'alert', 'amber' ],
	];

	const actions = [
		[ 'create', 'plus', __( 'New ID card', 'pikacart' ), __( 'Pick a design', 'pikacart' ) ],
		[ 'members', 'upload', __( 'Import from Excel', 'pikacart' ), __( 'Add many people', 'pikacart' ) ],
		[ 'print', 'printer', __( 'Print sheet', 'pikacart' ), __( 'Cards with cut marks', 'pikacart' ) ],
		[ 'designs/request', 'sparkles', __( 'Design on Demand', 'pikacart' ), __( 'We set up your design', 'pikacart' ) ],
	];

	const org = me.org;
	const steps = [
		[ !! org.category_id, __( 'Choose your category', 'pikacart' ), 'welcome' ],
		[ !! ( org.address && org.phone ), __( 'Add address and phone', 'pikacart' ), 'organisation' ],
		[ !! org.logo, __( 'Upload your logo', 'pikacart' ), 'organisation' ],
		[ !! org.sign, __( 'Upload the signature', 'pikacart' ), 'organisation' ],
		[ !! org.seal, __( 'Upload the official seal', 'pikacart' ), 'organisation' ],
		[ !! me.user.verified, __( 'Verify your email', 'pikacart' ), 'account' ],
		[ me.state.status === 'active' || me.state.status === 'cancelled', __( 'Upgrade to remove the watermark', 'pikacart' ), 'subscription' ],
	];
	const doneCount = steps.filter( ( x ) => x[ 0 ] ).length;

	const notices = me.notices.length
		? me.notices.map( ( n ) => `<li class="notice-item"><strong>${ esc( n.title ) }</strong><span class="muted">${ esc( n.date ) }</span><div>${ n.body }</div></li>` ).join( '' )
		: `<li class="muted">${ esc( __( 'No announcements right now.', 'pikacart' ) ) }</li>`;

	el.innerHTML = `
		<section class="hello">
			<div>
				<h2>${ esc( greeting() ) }${ first ? ', ' + esc( first ) : '' } 👋</h2>
				<p class="muted">${ esc( org.name ) }</p>
			</div>
			<a class="btn btn-primary" href="${ esc( ctx.url( 'create' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'New ID card', 'pikacart' ) ) }</a>
		</section>

		<div class="dash-top">
			${ planCard }
			<div class="stat-grid">
				${ stats.map( ( x ) => `<div class="card stat tone-${ x[ 3 ] }"><span class="stat-icon">${ icon( x[ 2 ] ) }</span><div><div class="stat-value">${ esc( x[ 1 ] ) }</div><div class="stat-label">${ esc( x[ 0 ] ) }</div></div></div>` ).join( '' ) }
			</div>
		</div>

		<div data-continue></div>
		<h3 class="section-title">${ esc( __( 'Quick actions', 'pikacart' ) ) }</h3>
		<div class="quick">
			${ actions.map( ( a ) => `<a class="card quick-item" href="${ esc( ctx.url( a[ 0 ] ) ) }" data-link><span class="quick-icon">${ icon( a[ 1 ] ) }</span><span><strong>${ esc( a[ 2 ] ) }</strong><small>${ esc( a[ 3 ] ) }</small></span>${ icon( 'chevron-right', 'quick-arrow' ) }</a>` ).join( '' ) }
		</div>

		<div class="dash-cols">
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Recent projects', 'pikacart' ) ) }</h3><a class="btn btn-sm btn-ghost" href="${ esc( ctx.url( 'projects' ) ) }" data-link>${ esc( __( 'All projects', 'pikacart' ) ) }</a></div>
				<div data-recent><div class="skeleton" style="height:120px"></div></div>
			</section>
			<div class="dash-side">
				${ doneCount < steps.length ? `<section class="card">
					<div class="card-head"><h3>${ esc( __( 'Finish your setup', 'pikacart' ) ) }</h3><span class="muted">${ doneCount }/${ steps.length }</span></div>
					<div class="progress"><span style="width:${ Math.round( ( doneCount / steps.length ) * 100 ) }%"></span></div>
					<ul class="checklist">
						${ steps.map( ( x ) => `<li class="${ x[ 0 ] ? 'is-done' : '' }">${ icon( x[ 0 ] ? 'check-circle' : 'chevron-right' ) }${ x[ 0 ] ? `<span>${ esc( x[ 1 ] ) }</span>` : `<a href="${ esc( ctx.url( x[ 2 ] ) ) }" data-link>${ esc( x[ 1 ] ) }</a>` }</li>` ).join( '' ) }
					</ul>
				</section>` : '' }
				<section class="card">
					<div class="card-head"><h3>${ icon( 'bell' ) } ${ esc( __( 'From Pikacart', 'pikacart' ) ) }</h3></div>
					<ul class="notices">${ notices }</ul>
				</section>
			</div>
		</div>`;

	// Recent projects, and a shortcut back to where the user left off.
	waitForAllSaves().then( () => api( 'projects' ) ).then( ( res ) => {
		const box = el.querySelector( '[data-recent]' );
		if ( ! box ) {
			return;
		}
		const list = res.projects.filter( ( p ) => p.status !== 'archived' );
		if ( ! list.length ) {
			box.innerHTML = emptyState( { art: 'cards', title: __( 'No card projects yet', 'pikacart' ), text: __( 'Start your first project by choosing a design for your students or staff.', 'pikacart' ), button: __( 'Create your first ID card', 'pikacart' ), href: ctx.url( 'create' ) } );
			return;
		}
		box.innerHTML = `<ul class="recent-list">${ list.slice( 0, 5 ).map( ( p ) => `<li>
			<a href="${ esc( ctx.url( savedStep( p ) ) ) }" data-link>
				<span class="recent-info"><strong>${ esc( p.name ) }</strong><small class="muted">${ esc( [ p.subtype && p.subtype.name, p.template && p.template.name ].filter( Boolean ).join( ' · ' ) ) } · ${ esc( sprintf( _n( '%d person', '%d people', p.people || 0, 'pikacart' ), p.people || 0 ) ) }</small></span>
				<span class="recent-step">${ esc( stepLabel( p ) ) } ${ icon( 'chevron-right' ) }</span>
			</a></li>` ).join( '' ) }</ul>`;
		const last = list[ 0 ];
		el.querySelector( '[data-continue]' ).innerHTML = `<a class="card continue-card" href="${ esc( ctx.url( savedStep( last ) ) ) }" data-link>${ icon( 'refresh' ) }<span><strong>${ esc( __( 'Continue where you left off', 'pikacart' ) ) }</strong><small>${ esc( sprintf( __( '%1$s · %2$s · saved %3$s ago', 'pikacart' ), last.name, stepLabel( last ), last.updated ) ) }</small></span><span class="btn btn-primary btn-sm">${ esc( __( 'Continue', 'pikacart' ) ) }</span></a>`;
	} ).catch( () => {
		const box = el.querySelector( '[data-recent]' );
		if ( box ) {
			box.innerHTML = '';
		}
	} );
}
