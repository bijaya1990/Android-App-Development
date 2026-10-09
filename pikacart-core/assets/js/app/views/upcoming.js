/**
 * Screens whose tools arrive in the next updates of Pikacart
 * (card designer, members, print sheets, own designs). They show a clear
 * empty state so the dashboard is complete and easy to understand today.
 */

import { esc, icon, emptyState } from '../ui.js';

const { __ } = window.wp.i18n;

const CAT_ICONS = { school: 'school', college: 'college', book: 'book', briefcase: 'briefcase', health: 'health', heart: 'heart', ticket: 'ticket', shield: 'shield', users: 'users', mic: 'mic' };

const COPY = () => ( {
	create: {
		art: 'cards',
		title: __( 'The design gallery opens in the next update', 'pikacart' ),
		text: __( 'Soon you will pick from 50 professional styles for each card type below, choose size and colours, and fine-tune in the editor. Your organisation details are already saved and ready.', 'pikacart' ),
	},
	projects: {
		art: 'cards',
		title: __( 'No card projects yet', 'pikacart' ),
		text: __( 'Each card project (for example "Class 10 students 2026-27") will appear here with its design, size and number of people.', 'pikacart' ),
	},
	members: {
		art: 'people',
		title: __( 'Your people will appear here', 'pikacart' ),
		text: __( 'Add students or staff one by one, import them from Excel, upload photos in bulk, or share a self-fill link. This arrives together with the card designer.', 'pikacart' ),
	},
	print: {
		art: 'cards',
		title: __( 'Print sheets are coming', 'pikacart' ),
		text: __( 'Arrange many cards on A4 or larger paper with cut marks, front and back side by side, ready to print and cut.', 'pikacart' ),
	},
	designs: {
		art: 'cards',
		title: __( 'Your own designs will live here', 'pikacart' ),
		text: __( 'Upload your existing card design, or send it to our Design on Demand team and we will set it up in your account within hours.', 'pikacart' ),
	},
} );

export default function upcoming( key ) {
	return ( el, ctx ) => {
		const c = COPY()[ key ];
		let extra = '';
		if ( key === 'create' ) {
			extra = `<h3 class="section-title">${ esc( __( 'Card types you will be able to create', 'pikacart' ) ) }</h3>
				<div class="cat-grid cat-grid-static">
					${ ctx.me.catalog.map( ( cat ) => `<div class="cat-tile ${ ctx.me.org.category_id === cat.id ? 'is-selected' : '' }">
						<span class="cat-icon">${ icon( CAT_ICONS[ cat.icon ] || 'idcard' ) }</span>
						<strong>${ esc( cat.name ) }</strong>
						<small>${ esc( cat.subtypes.map( ( s ) => s.name ).join( ' · ' ) ) }</small>
					</div>` ).join( '' ) }
				</div>`;
		}
		el.innerHTML = `<section class="card">${ emptyState( {
			art: c.art,
			title: c.title,
			text: c.text,
			button: __( 'Complete your organisation profile', 'pikacart' ),
			href: ctx.url( 'organisation' ),
		} ) }</section>${ extra }`;
	};
}
