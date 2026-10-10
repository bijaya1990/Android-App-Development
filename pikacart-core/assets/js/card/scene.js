/**
 * Helpers that turn a project (size, orientation, palette, design) into what
 * the renderer needs. Shared by the app, the admin builder and the website.
 */

import { layoutFor } from './families.js';
import { kindOf } from './data.js';

/** Card size in mm for this orientation. */
export function cardSize( project, sizes ) {
	let w = 54;
	let h = 86;
	if ( project.custom_w && project.custom_h ) {
		w = project.custom_w;
		h = project.custom_h;
	} else {
		const s = ( sizes || [] ).find( ( x ) => x.id === project.size_id ) || project.size;
		if ( s ) {
			w = s.w;
			h = s.h;
		}
	}
	const short = Math.min( w, h );
	const long = Math.max( w, h );
	return project.orientation === 'landscape' ? { w: long, h: short } : { w: short, h: long };
}

/** Palette colours {p,s,a,t} for a project. */
export function paletteOf( project, palettes ) {
	const pal = project.palette || {};
	if ( pal.id ) {
		const found = ( palettes || [] ).find( ( p ) => p.id === pal.id );
		if ( found ) {
			return found;
		}
	}
	if ( pal.p ) {
		return pal;
	}
	return ( palettes && palettes[ 0 ] ) || { p: '#1E3A8A', s: '#3B82F6', a: '#F59E0B', t: '#0F172A' };
}

/**
 * The layout {front, back} to draw: the organisation's own edited copy if it
 * exists for this orientation, otherwise the template's original.
 */
export function layoutOf( project, template, subtype, size ) {
	const ar = size.w / size.h;
	const key = project.orientation === 'landscape' ? 'landscape' : 'portrait';
	if ( project.design && project.design[ key ] ) {
		return project.design[ key ];
	}
	if ( ! template ) {
		return { front: { bg: '@w', els: [] }, back: { bg: '@w', els: [] } };
	}
	return layoutFor( template, key, ar, kindOf( subtype ) );
}

/** Field keys switched on, including custom fields. */
export function enabledFields( project ) {
	const f = project.fields || {};
	return [ ...( f.on || [] ), 'name' ];
}

export function flagsOf( project ) {
	return Object.assign( { qr_front: true, qr_back: true, bar_front: true, bar_back: true, renewal: true }, ( project.fields || {} ).flags || {} );
}

/** Project-level values used by memberVars(). */
export function projectInfo( project, subtype ) {
	const f = project.fields || {};
	return {
		card_title: f.card_title || '',
		terms: f.terms || ( subtype && subtype.terms ) || '',
		custom: f.custom || [],
		session: f.session || '',
		subtype_name: subtype ? subtype.name : '',
		valid_from: f.valid_from || '',
		valid_until: f.valid_until || '',
	};
}

/** Does a template support this orientation? Built-ins support both. */
export function supportsOrientation( template, orientation ) {
	const l = template.layout || {};
	if ( l.recipe ) {
		return true;
	}
	if ( l.artwork && ! l.portrait && ! l.landscape ) {
		return ( l.artwork.orientation || 'portrait' ) === orientation;
	}
	return !! l[ orientation ] || ( ! l.portrait && ! l.landscape );
}
