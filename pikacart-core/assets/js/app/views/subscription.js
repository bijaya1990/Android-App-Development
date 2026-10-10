/**
 * Subscription: plan, status, next billing date, pay (autopay or one-time),
 * payment history, invoices and cancel autopay.
 * Access is activated only by the server after it verifies Razorpay's signature.
 */

import { api } from '../api.js';
import { esc, icon, toast, confirmDialog, withLoading } from '../ui.js';
import { downloadInvoice } from '../invoice.js';
import { drawSampleCard } from '../../card/watermark.js';

const { __, sprintf } = window.wp.i18n;

let checkoutLoading = null;

/** Razorpay requires Checkout to be loaded from its own server. */
function loadCheckout() {
	if ( window.Razorpay ) {
		return Promise.resolve();
	}
	if ( ! checkoutLoading ) {
		checkoutLoading = new Promise( ( resolve, reject ) => {
			const s = document.createElement( 'script' );
			s.src = 'https://checkout.razorpay.com/v1/checkout.js';
			s.onload = resolve;
			s.onerror = () => {
				checkoutLoading = null;
				reject( { message: __( 'Could not open the payment window. Please check your internet connection and try again.', 'pikacart' ) } );
			};
			document.head.appendChild( s );
		} );
	}
	return checkoutLoading;
}

function openCheckout( options ) {
	return new Promise( ( resolve, reject ) => {
		const rzp = new window.Razorpay( Object.assign( {}, options, {
			handler: resolve,
			modal: {
				ondismiss: () => reject( { message: __( 'Payment window closed. No money was taken.', 'pikacart' ), dismissed: true } ),
			},
		} ) );
		rzp.on( 'payment.failed', ( resp ) => {
			const reason = resp && resp.error && resp.error.description;
			toast( reason || __( 'The payment failed. Please try again or use another method.', 'pikacart' ), 'error' );
		} );
		rzp.open();
	} );
}

const STATUS_TEXT = () => ( {
	free: __( 'You are on the Free plan. Free forever; downloaded cards carry the Pikacart watermark.', 'pikacart' ),
	trial: __( 'You are on the free trial.', 'pikacart' ),
	active: __( 'Your plan is active.', 'pikacart' ),
	cancelled: __( 'Autopay is cancelled. You keep access until the end date.', 'pikacart' ),
	expired: __( 'Your plan is not active. Subscribe to continue.', 'pikacart' ),
} );

export default function subscription( el, ctx ) {
	let alive = true;
	let coupon = '';
	el.innerHTML = '<div class="skeleton-grid"><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div><div class="skeleton sk-wide"></div></div>';

	async function load() {
		try {
			const data = await api( 'billing' );
			if ( alive ) {
				draw( data );
			}
		} catch ( err ) {
			el.innerHTML = `<div class="card"><p>${ esc( err.message ) }</p><button class="btn" type="button" data-retry>${ esc( __( 'Try again', 'pikacart' ) ) }</button></div>`;
			el.querySelector( '[data-retry]' ).addEventListener( 'click', load );
		}
	}

	async function pay( kind, planId, btn ) {
		await withLoading( btn, async () => {
			try {
				const start = await api( kind === 'autopay' ? 'billing/subscribe' : 'billing/order', { method: 'POST', body: kind === 'autopay' ? { plan_id: planId } : { plan_id: planId, coupon } } );
				await loadCheckout();
				const result = await openCheckout( start.checkout );
				const res = await api( kind === 'autopay' ? 'billing/verify-subscription' : 'billing/verify-order', { method: 'POST', body: result } );
				ctx.setMe( res.me );
				if ( window.fbq && start.checkout && start.checkout.amount ) {
					window.fbq( 'track', 'Purchase', { value: start.checkout.amount / 100, currency: start.checkout.currency || 'INR' } );
				}
				toast( res.message );
				load();
			} catch ( err ) {
				toast( err.message, err.dismissed ? 'info' : 'error' );
			}
		} );
	}

	function draw( d ) {
		const me = ctx.me;
		const statusText = STATUS_TEXT()[ d.status ] || '';
		const planRows = d.plans.map( ( p ) => {
			const period = p.period_days === 30 ? __( 'per month', 'pikacart' ) : sprintf( __( 'per %d days', 'pikacart' ), p.period_days );
			const canAutopay = p.autopay && ! d.autopay;
			return `<div class="card price-card">
				<div class="price-head"><h3>${ esc( p.name ) }</h3>${ d.current === p.name && ( d.status === 'active' || d.status === 'cancelled' ) ? `<span class="badge badge-active">${ esc( __( 'Current plan', 'pikacart' ) ) }</span>` : '' }</div>
				<div class="price">${ esc( p.price_text ) }<span>${ esc( period ) }</span></div>
				<p class="muted">${ esc( p.description ) }</p>
				<div class="price-actions">
					${ canAutopay ? `<button type="button" class="btn btn-primary btn-block" data-pay="autopay" data-plan="${ p.id }">${ icon( 'refresh' ) }${ esc( __( 'Subscribe with autopay', 'pikacart' ) ) }</button><small class="muted">${ esc( __( 'UPI autopay or card. Renews automatically, cancel anytime.', 'pikacart' ) ) }</small>` : '' }
					${ d.onetime ? `<button type="button" class="btn ${ canAutopay ? '' : 'btn-primary' } btn-block" data-pay="once" data-plan="${ p.id }">${ icon( 'card' ) }${ esc( sprintf( __( 'Pay once for %d days', 'pikacart' ), p.period_days ) ) }</button><small class="muted">${ esc( __( 'For banks that do not support autopay. No renewal.', 'pikacart' ) ) }</small>
					<details class="coupon-box"><summary>${ esc( __( 'Have a coupon code?', 'pikacart' ) ) }</summary>
						<div class="coupon-row"><input type="text" data-coupon-input maxlength="40" autocomplete="off" aria-label="${ esc( __( 'Coupon code', 'pikacart' ) ) }" placeholder="${ esc( __( 'Coupon code', 'pikacart' ) ) }"><button type="button" class="btn btn-sm" data-coupon-apply data-plan="${ p.id }">${ esc( __( 'Apply', 'pikacart' ) ) }</button></div>
						<small class="muted" data-coupon-msg>${ esc( __( 'Coupons work with "Pay once".', 'pikacart' ) ) }</small>
					</details>` : '' }
				</div>
			</div>`;
		} ).join( '' );

		const history = d.payments.length
			? `<div class="table-wrap"><table class="table">
				<thead><tr><th>${ esc( __( 'Date', 'pikacart' ) ) }</th><th>${ esc( __( 'Amount', 'pikacart' ) ) }</th><th>${ esc( __( 'Type', 'pikacart' ) ) }</th><th>${ esc( __( 'Method', 'pikacart' ) ) }</th><th>${ esc( __( 'Status', 'pikacart' ) ) }</th><th>${ esc( __( 'Invoice', 'pikacart' ) ) }</th></tr></thead>
				<tbody>${ d.payments.map( ( p ) => `<tr>
					<td data-label="${ esc( __( 'Date', 'pikacart' ) ) }">${ esc( p.date ) }</td>
					<td data-label="${ esc( __( 'Amount', 'pikacart' ) ) }"><strong>${ esc( p.amount ) }</strong></td>
					<td data-label="${ esc( __( 'Type', 'pikacart' ) ) }">${ esc( p.kind ) }</td>
					<td data-label="${ esc( __( 'Method', 'pikacart' ) ) }">${ esc( p.method ) }</td>
					<td data-label="${ esc( __( 'Status', 'pikacart' ) ) }"><span class="badge badge-${ esc( p.status ) }">${ esc( p.status === 'captured' ? __( 'Paid', 'pikacart' ) : ( p.status === 'failed' ? __( 'Failed', 'pikacart' ) : __( 'Refunded', 'pikacart' ) ) ) }</span>${ p.error ? `<br><small class="muted">${ esc( p.error ) }</small>` : '' }</td>
					<td data-label="${ esc( __( 'Invoice', 'pikacart' ) ) }">${ p.status === 'captured' && p.invoice_no ? `<button type="button" class="btn btn-sm" data-invoice="${ p.id }">${ icon( 'download' ) }${ esc( p.invoice_no ) }</button>` : '—' }</td>
				</tr>` ).join( '' ) }</tbody></table></div>`
			: `<p class="muted">${ esc( __( 'No payments yet.', 'pikacart' ) ) }</p>`;

		el.innerHTML = `
			${ d.test_mode && d.configured ? `<div class="banner banner-info inline-banner">${ icon( 'info' ) }<div><span>${ esc( __( 'Payments are in TEST mode. No real money is taken.', 'pikacart' ) ) }</span></div></div>` : '' }
			${ ! d.configured ? `<div class="banner banner-lock inline-banner">${ icon( 'alert' ) }<div><span>${ esc( sprintf( __( 'Online payment is being set up. Please contact %s to subscribe.', 'pikacart' ), window.PKC.support ) ) }</span></div></div>` : '' }
			<div class="sub-grid">
				<section class="card sub-status">
					<div class="card-head"><h3>${ esc( __( 'Your plan', 'pikacart' ) ) }</h3><span class="badge badge-${ esc( d.status ) }">${ esc( d.label ) }</span></div>
					<p>${ esc( statusText ) }</p>
					<dl class="dl">
						<dt>${ esc( __( 'Plan', 'pikacart' ) ) }</dt><dd>${ esc( d.status === 'free' ? __( 'Free (with watermark)', 'pikacart' ) : ( d.current || ( d.status === 'trial' ? __( 'Free trial', 'pikacart' ) : '—' ) ) ) }</dd>
						${ d.status === 'trial' ? `<dt>${ esc( __( 'Trial ends in', 'pikacart' ) ) }</dt><dd><strong data-countdown></strong></dd>` : '' }
						${ d.period_end ? `<dt>${ esc( __( 'Paid until', 'pikacart' ) ) }</dt><dd>${ esc( d.period_end ) }</dd>` : '' }
						<dt>${ esc( __( 'Autopay', 'pikacart' ) ) }</dt><dd>${ esc( d.autopay ? __( 'On', 'pikacart' ) : __( 'Off', 'pikacart' ) ) }</dd>
						${ d.autopay && d.next_bill ? `<dt>${ esc( __( 'Next billing date', 'pikacart' ) ) }</dt><dd>${ esc( d.next_bill ) }</dd>` : '' }
					</dl>
					${ d.autopay ? `<button type="button" class="btn btn-danger btn-sm" data-cancel>${ esc( __( 'Cancel autopay', 'pikacart' ) ) }</button>` : '' }
				</section>
				<div class="plans">${ planRows }</div>
			</div>
			<section class="card compare">
				<div class="card-head"><h3>${ icon( 'sparkles' ) } ${ esc( __( 'Free vs Pro: how your cards look', 'pikacart' ) ) }</h3></div>
				<div class="compare-grid">
					<figure class="compare-item"><canvas width="324" height="516" data-sample="free" aria-label="${ esc( __( 'Free plan card with watermark', 'pikacart' ) ) }"></canvas><figcaption><strong>${ esc( __( 'Free forever', 'pikacart' ) ) }</strong><span>${ esc( sprintf( __( 'All features. Cards carry "%s" across the card.', 'pikacart' ), me.watermark.text ) ) }</span></figcaption></figure>
					<figure class="compare-item is-pro"><canvas width="324" height="516" data-sample="pro" aria-label="${ esc( __( 'Pro plan card without watermark', 'pikacart' ) ) }"></canvas><figcaption><strong>${ esc( sprintf( __( 'Pro · %s/month', 'pikacart' ), d.plans[ 0 ] ? d.plans[ 0 ].price_text : '₹59' ) ) }</strong><span>${ esc( __( 'Clean, professional cards ready for official use.', 'pikacart' ) ) }</span></figcaption></figure>
				</div>
			</section>
			<section class="card">
				<div class="card-head"><h3>${ icon( 'receipt' ) } ${ esc( __( 'Payment history', 'pikacart' ) ) }</h3></div>
				${ history }
			</section>
			<p class="muted small center">${ icon( 'shield' ) } ${ esc( __( 'Secure payment by Razorpay. We never see your card or UPI PIN.', 'pikacart' ) ) }
				${ me.links.refund ? ` · <a href="${ esc( me.links.refund ) }" target="_blank" rel="noopener">${ esc( __( 'Refund and cancellation policy', 'pikacart' ) ) }</a>` : '' }</p>`;

		const brand = getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-brand' ).trim();
		const accent = getComputedStyle( document.documentElement ).getPropertyValue( '--pkc-accent' ).trim();
		const drawSamples = () => el.querySelectorAll( '[data-sample]' ).forEach( ( c ) => drawSampleCard( c, { org: me.org.name, watermark: c.dataset.sample === 'free', text: me.watermark.text, brand, accent } ) );
		drawSamples();
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( drawSamples );
		}

		el.querySelectorAll( '[data-countdown]' ).forEach( ( x ) => {
			x.textContent = '…';
		} );

		el.querySelectorAll( '[data-coupon-apply]' ).forEach( ( b ) => b.addEventListener( 'click', () => withLoading( b, async () => {
			const box = b.closest( '.coupon-box' );
			const msg = box.querySelector( '[data-coupon-msg]' );
			const code = box.querySelector( '[data-coupon-input]' ).value.trim();
			try {
				const res = await api( 'billing/coupon', { method: 'POST', body: { plan_id: b.dataset.plan, coupon: code } } );
				coupon = res.code;
				msg.textContent = res.message;
				msg.className = 'coupon-ok';
			} catch ( err ) {
				coupon = '';
				msg.textContent = err.message;
				msg.className = 'coupon-bad';
			}
		} ) ) );
		el.querySelectorAll( '[data-pay]' ).forEach( ( b ) => {
			b.addEventListener( 'click', () => pay( b.dataset.pay, Number( b.dataset.plan ), b ) );
		} );

		el.querySelector( '[data-cancel]' )?.addEventListener( 'click', async ( e ) => {
			const btn = e.currentTarget;
			const ok = await confirmDialog( {
				title: __( 'Cancel autopay?', 'pikacart' ),
				message: __( 'You will not be charged again. You keep full access until the end of the period you already paid for.', 'pikacart' ),
				confirm: __( 'Yes, cancel autopay', 'pikacart' ),
				danger: true,
			} );
			if ( ! ok ) {
				return;
			}
			withLoading( btn, async () => {
				try {
					const res = await api( 'billing/cancel', { method: 'POST' } );
					ctx.setMe( res.me );
					toast( res.message );
					load();
				} catch ( err ) {
					toast( err.message, 'error' );
				}
			} );
		} );

		el.querySelectorAll( '[data-invoice]' ).forEach( ( b ) => {
			b.addEventListener( 'click', () => withLoading( b, async () => {
				try {
					const res = await api( `billing/invoice/${ b.dataset.invoice }` );
					downloadInvoice( res.invoice );
				} catch ( err ) {
					toast( err.message, 'error' );
				}
			} ) );
		} );
	}

	load();
	return () => {
		alive = false;
	};
}
