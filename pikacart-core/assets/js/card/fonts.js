/**
 * Fonts available on ID cards. All are bundled locally (assets/fonts/card).
 * Every stack falls back to Devanagari fonts, so Hindi names always render.
 */

export const FONTS = [
	{ name: 'Inter', label: 'Inter' },
	{ name: 'Plus Jakarta Sans', label: 'Plus Jakarta Sans' },
	{ name: 'Poppins', label: 'Poppins' },
	{ name: 'Montserrat', label: 'Montserrat' },
	{ name: 'Lato', label: 'Lato' },
	{ name: 'Raleway', label: 'Raleway' },
	{ name: 'Nunito', label: 'Nunito' },
	{ name: 'Oswald', label: 'Oswald' },
	{ name: 'Bebas Neue', label: 'Bebas Neue' },
	{ name: 'Playfair Display', label: 'Playfair Display' },
	{ name: 'DM Serif Display', label: 'DM Serif Display' },
	{ name: 'Merriweather', label: 'Merriweather' },
	{ name: 'Roboto Slab', label: 'Roboto Slab' },
	{ name: 'Mukta', label: 'Mukta (हिन्दी)' },
	{ name: 'Noto Sans Devanagari', label: 'Noto Sans Devanagari (हिन्दी)' },
];

const SERIF = [ 'Playfair Display', 'DM Serif Display', 'Merriweather', 'Roboto Slab' ];

export function stack( name ) {
	const n = name || 'Inter';
	const generic = SERIF.includes( n ) ? 'Georgia, serif' : 'Arial, sans-serif';
	return `"${ n }", "Noto Sans Devanagari", "Mukta", ${ generic }`;
}

export function fontCss( { weight = 400, size, family, italic = false } ) {
	return `${ italic ? 'italic ' : '' }${ weight } ${ size }px ${ stack( family ) }`;
}

const loaded = new Set();

/**
 * Make sure fonts are ready before drawing (canvas does not wait by itself).
 * @param {Array<[string, number]>} list  [family, weight] pairs.
 */
export async function ensureFonts( list ) {
	if ( ! document.fonts || ! document.fonts.load ) {
		return;
	}
	const jobs = [];
	list.forEach( ( [ family, weight ] ) => {
		const w = weight >= 600 ? 700 : 400;
		const key = family + w;
		if ( loaded.has( key ) ) {
			return;
		}
		loaded.add( key );
		jobs.push( document.fonts.load( `${ w } 20px "${ family }"`, 'AaअBb1' ).catch( () => null ) );
	} );
	if ( ! jobs.length ) {
		return;
	}
	await Promise.race( [ Promise.all( jobs ), new Promise( ( r ) => setTimeout( r, 3000 ) ) ] );
}
