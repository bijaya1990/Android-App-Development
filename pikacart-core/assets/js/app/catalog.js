/**
 * Catalogue cache (categories, sub-types, sizes, palettes) and organisation
 * values used on previews.
 */

import { api } from './api.js';

let cache = null;

export async function catalog() {
	if ( ! cache ) {
		cache = api( 'catalog' ).catch( ( e ) => {
			cache = null;
			throw e;
		} );
	}
	return cache;
}

export function findSubtype( cat, id ) {
	for ( const c of cat.tree ) {
		const s = c.subtypes.find( ( x ) => x.id === id );
		if ( s ) {
			return { category: c, subtype: s };
		}
	}
	return { category: null, subtype: null };
}

/** Organisation values for previews so designs show the customer's own details. */
export function orgVars( org ) {
	const v = {};
	if ( org.name ) {
		v.org_name = org.name;
	}
	if ( org.tagline ) {
		v.org_tagline = org.tagline;
	}
	if ( org.address ) {
		v.org_address = org.address;
	}
	if ( org.phone ) {
		v.org_phone = org.phone;
	}
	if ( org.org_email ) {
		v.org_email = org.org_email;
	}
	if ( org.website ) {
		v.org_website = org.website;
	}
	if ( org.signatory_title ) {
		v.signatory_title = org.signatory_title;
	}
	if ( org.signatory_name ) {
		v.signatory_name = org.signatory_name;
	}
	if ( org.session_year ) {
		v.session = org.session_year;
	}
	if ( org.terms ) {
		v.terms = org.terms;
	}
	v.logo = org.logo || '';
	v.sign = org.sign || '';
	v.seal = org.seal || '';
	return v;
}
