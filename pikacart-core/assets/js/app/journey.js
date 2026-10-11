/**
 * The one guided path for making ID cards, shown the same way on every screen:
 * a step bar ("Step 3 of 8"), a short "what to do here" guide, and big
 * Back / Next buttons. From the organisation to printing, nothing is hidden.
 */

import { esc, icon } from './ui.js';

const { __, sprintf } = window.wp.i18n;

/** [ key, short label, guide title, guide text, optional? ] */
export const JOURNEY = () => [
	[ 'org', __( 'Organisation', 'pikacart' ), __( 'Which organisation is this ID card for?', 'pikacart' ), __( 'Pick the school, college or office. Its logo, address and signature are printed on the cards, and the cards are saved in its folder.', 'pikacart' ) ],
	[ 'type', __( 'Card type', 'pikacart' ), __( 'What kind of ID card?', 'pikacart' ), __( 'Pick the card type, for example Student, Teacher / Staff or Visitor. Each type has 50 ready designs.', 'pikacart' ) ],
	[ 'design', __( 'Design', 'pikacart' ), __( 'Choose a design', 'pikacart' ), __( 'Tap a design to see it bigger, then press "Use this design". Every design can be changed later.', 'pikacart' ) ],
	[ 'setup', __( 'Size & colour', 'pikacart' ), __( 'Choose the card size and colours', 'pikacart' ), __( 'Pick the size of your card holder (most PVC cards are 54 × 86 mm) and a colour. The preview changes at once.', 'pikacart' ) ],
	[ 'details', __( 'Details', 'pikacart' ), __( 'Choose what is printed on the card', 'pikacart' ), __( 'Tick the details you need, such as Class, Blood group or Mobile number, and set the "Valid upto" date for all cards.', 'pikacart' ) ],
	[ 'editor', __( 'Fine-tune', 'pikacart' ), __( 'Fine-tune the card (optional)', 'pikacart' ), __( 'Move, resize or restyle any text or picture if you want. If the card already looks right, simply press Next.', 'pikacart' ), true ],
	[ 'people', __( 'People', 'pikacart' ), __( 'Add the people', 'pikacart' ), __( 'Add one person, or press "Import from Excel" to add everyone with photos at once. Use "Blank Excel" at the top to get the right columns.', 'pikacart' ) ],
	[ 'download', __( 'Print', 'pikacart' ), __( 'Print or download the cards', 'pikacart' ), __( 'Choose how you will print: on A4 paper, on a PVC card printer, or as photos for WhatsApp.', 'pikacart' ) ],
];

export const TOTAL = 8;

/** Position (1-8) of a step key. */
export function stepNo( key ) {
	return JOURNEY().findIndex( ( s ) => s[ 0 ] === key ) + 1;
}

/**
 * Step bar. href( n ) returns the link for step n, or '' when it cannot be opened.
 */
export function journeyBar( current, href = () => '' ) {
	const steps = JOURNEY();
	return `<nav class="journey" aria-label="${ esc( __( 'Steps to make ID cards', 'pikacart' ) ) }">
		<p class="journey-now"><span>${ esc( sprintf( __( 'Step %1$d of %2$d', 'pikacart' ), current, TOTAL ) ) }</span><b>${ esc( steps[ current - 1 ][ 1 ] ) }</b></p>
		<div class="journey-track" aria-hidden="true"><span style="width:${ Math.round( ( ( current - 1 ) / ( TOTAL - 1 ) ) * 100 ) }%"></span></div>
		<ol class="journey-steps">
			${ steps.map( ( s, i ) => {
				const n = i + 1;
				const state = n < current ? 'is-done' : n === current ? 'is-current' : '';
				const inner = `<span class="step-dot">${ n < current ? icon( 'check' ) : n }</span><span class="step-label">${ esc( s[ 1 ] ) }</span>`;
				const link = n !== current ? href( n ) : '';
				return `<li class="${ state }"${ n === current ? ' aria-current="step"' : '' }>${ link ? `<a href="${ esc( link ) }" data-link>${ inner }</a>` : `<span class="journey-item">${ inner }</span>` }</li>`;
			} ).join( '' ) }
		</ol>
	</nav>`;
}

/** "Step 5 of 8 · Choose what is printed on the card" with one line of help. */
export function journeyGuide( current, extra = '' ) {
	const s = JOURNEY()[ current - 1 ];
	return `<div class="journey-guide">
		<span class="journey-num">${ current }</span>
		<div>
			<h2>${ esc( s[ 2 ] ) }${ s[ 4 ] ? ` <em>${ esc( __( 'Optional', 'pikacart' ) ) }</em>` : '' }</h2>
			<p>${ esc( s[ 3 ] ) }</p>
		</div>
		<div class="journey-guide-extra">
			${ extra }
			<a class="journey-help" href="${ esc( window.PKC.app + 'guide/' + s[ 0 ] + '/' ) }" data-link title="${ esc( __( 'Step-by-step help for this step, in Hindi and English', 'pikacart' ) ) }">${ icon( 'book' ) }<span>${ esc( __( 'Help', 'pikacart' ) ) } · मदद</span></a>
		</div>
	</div>`;
}

/** Colourful "read the guide" strip for the Dashboard and the start of Create ID card. */
export function guideBanner() {
	return `<a class="guide-banner" href="${ esc( window.PKC.app + 'guide/' ) }" data-link>
		<span class="guide-banner-icon">${ icon( 'book' ) }</span>
		<span class="guide-banner-t">
			<b>${ esc( __( 'New here? Read the step-by-step guide', 'pikacart' ) ) }</b>
			<small>पूरी गाइड हिंदी में भी · ${ esc( __( 'With pictures, from sign-up to printing', 'pikacart' ) ) }</small>
		</span>
		<span class="guide-banner-go">${ esc( __( 'Open guide', 'pikacart' ) ) }${ icon( 'chevron-right' ) }</span>
	</a>`;
}

/**
 * Big Back / Next buttons. back and next are { href, label } or null;
 * next.id lets a screen check something (e.g. people added) before moving on.
 */
export function journeyNav( back, next ) {
	return `<div class="journey-nav">
		${ back ? `<a class="btn btn-lg" href="${ esc( back.href ) }" data-link data-journey="back">${ icon( 'chevron-left' ) }<span>${ esc( back.label ) }</span></a>` : '<span></span>' }
		${ next ? `<a class="btn btn-primary btn-lg" href="${ esc( next.href ) }" data-link data-journey="next"><span><small>${ esc( next.small || '' ) }</small>${ esc( next.label ) }</span>${ icon( 'chevron-right' ) }</a>` : '<span></span>' }
	</div>`;
}
