/**
 * "Fine-tune" step: the card editor connected to a project.
 * Edits are saved as the organisation's own copy; the master template never changes.
 */

import { api } from './api.js';
import { toast, confirmDialog, esc, icon } from './ui.js';
import { createEditor } from '../card/editor-core.js';
import { layoutFor } from '../card/families.js';

const { __ } = window.wp.i18n;

export default function editorStep( body, P ) {
	const key = P.project.orientation === 'landscape' ? 'landscape' : 'portrait';
	let person = null;
	let editor = null;
	let alive = true;

	body.innerHTML = `<div class="ed-mobile-note banner banner-info">${ icon( 'info' ) }<span>${ esc( __( 'Design editing works best on a computer or tablet. You can still make small changes here.', 'pikacart' ) ) }</span></div><div data-ed></div>`;

	( async () => {
		let people = [];
		try {
			const res = await api( `projects/${ P.project.id }/members?per_page=30&sort=name` );
			people = res.members;
		} catch ( e ) {
			people = [];
		}
		if ( ! alive ) {
			return;
		}
		const original = () => {
			const size = P.size();
			return P.data.template ? layoutFor( P.data.template, key, size.w / size.h, P.kind() ) : { front: { bg: '@w', els: [] }, back: { bg: '@w', els: [] } };
		};
		editor = createEditor( body.querySelector( '[data-ed]' ), {
			doc: P.layout(),
			original,
			kind: P.kind(),
			size: () => P.size(),
			options: ( side ) => P.renderOpts( side, person ),
			onChange: ( doc ) => {
				const design = Object.assign( {}, P.project.design || {} );
				if ( doc ) {
					design[ key ] = doc;
				} else {
					delete design[ key ];
				}
				P.change( { design: Object.keys( design ).length ? design : null } );
			},
			upload: async ( file ) => {
				if ( file.size > window.PKC.uploadMb * 1024 * 1024 ) {
					throw { message: __( 'This image is too large. Please choose a smaller file.', 'pikacart' ) };
				}
				const form = new FormData();
				form.append( 'file', file );
				const res = await api( 'uploads/image', { method: 'POST', form } );
				return res.url;
			},
			onError: ( e ) => toast( e.message, 'error' ),
			people,
			onPerson: async ( id ) => {
				person = people.find( ( p ) => String( p.id ) === String( id ) ) || null;
			},
			confirmReset: () => confirmDialog( { title: __( 'Reset to the original design?', 'pikacart' ), message: __( 'All your changes on this design will be removed.', 'pikacart' ), confirm: __( 'Reset', 'pikacart' ), danger: true } ),
		} );
	} )();

	return () => {
		alive = false;
		if ( editor ) {
			editor.destroy();
		}
	};
}
