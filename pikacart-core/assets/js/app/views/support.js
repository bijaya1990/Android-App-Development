/**
 * Support: contact details and answers to common questions.
 */

import { esc, icon, illustration } from '../ui.js';

const { __, sprintf } = window.wp.i18n;

export default function support( el, ctx ) {
	const cfg = window.PKC;
	const price = ctx.me.plan ? ctx.me.plan.price_text : '₹59';
	const faq = [
		[ __( 'How long is the free trial?', 'pikacart' ), __( 'The trial starts when you register. The timer at the top shows exactly how much time is left. Every feature works during the trial; downloads carry a watermark.', 'pikacart' ) ],
		[ __( 'What happens when the trial ends?', 'pikacart' ), sprintf( __( 'Nothing is deleted. Your designs and data stay saved. Subscribe for %s per month to keep creating, downloading and printing.', 'pikacart' ), price ) ],
		[ __( 'My bank does not support autopay. Can I still pay?', 'pikacart' ), __( 'Yes. On the Subscription page choose "Pay once". You pay for one period by UPI, card or net banking, with no automatic renewal.', 'pikacart' ) ],
		[ __( 'How do I cancel autopay?', 'pikacart' ), __( 'Open Subscription and click "Cancel autopay". You keep access until the end of the period you already paid for.', 'pikacart' ) ],
		[ __( 'Money was deducted but my plan is not active.', 'pikacart' ), __( 'Payments are usually confirmed within a few minutes. If it still shows inactive after 30 minutes, email us your payment ID from the bank SMS or Razorpay email.', 'pikacart' ) ],
		[ __( 'Can I use my own card design?', 'pikacart' ), __( 'Yes. Upload your front and back design and place the photo, name and QR code yourself, or use Design on Demand and our team will set it up for you.', 'pikacart' ) ],
		[ __( 'Can I make Aadhaar, PAN or other government ID cards?', 'pikacart' ), __( 'No. Pikacart is only for cards your organisation issues to its own people. Imitating government-issued IDs is not allowed and leads to suspension.', 'pikacart' ) ],
	];

	el.innerHTML = `<div class="page-grid">
		<div class="page-main">
			<section class="card">
				<div class="card-head"><h3>${ esc( __( 'Questions people often ask', 'pikacart' ) ) }</h3></div>
				<div class="faq">
					${ faq.map( ( q ) => `<details><summary>${ esc( q[ 0 ] ) }${ icon( 'chevron-down' ) }</summary><p>${ esc( q[ 1 ] ) }</p></details>` ).join( '' ) }
				</div>
			</section>
		</div>
		<aside class="page-side">
			<section class="card contact-card">
				${ illustration( 'support' ) }
				<h3>${ esc( __( 'Talk to us', 'pikacart' ) ) }</h3>
				<p class="muted">${ esc( __( 'We usually reply within one working day.', 'pikacart' ) ) }</p>
				<a class="contact-line" href="mailto:${ esc( cfg.support ) }?subject=${ encodeURIComponent( sprintf( __( 'Help with %s', 'pikacart' ), ctx.me.org.name ) ) }">${ icon( 'mail' ) }<span>${ esc( cfg.support ) }</span></a>
				${ cfg.phone ? `<a class="contact-line" href="tel:${ esc( cfg.phone.replace( /[^0-9+]/g, '' ) ) }">${ icon( 'phone' ) }<span>${ esc( cfg.phone ) }</span></a>` : '' }
				<p class="hint">${ esc( __( 'Please mention your organisation name and registered email.', 'pikacart' ) ) }</p>
			</section>
		</aside>
	</div>`;
}
