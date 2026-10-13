/**
 * Fonts available on ID cards and certificates. All are bundled locally
 * (assets/fonts/card) under the SIL Open Font License.
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
	// Certificate classics and handwriting (English).
	{ name: 'Cinzel', label: 'Cinzel' },
	{ name: 'Cinzel Decorative', label: 'Cinzel Decorative' },
	{ name: 'Cormorant Garamond', label: 'Cormorant Garamond' },
	{ name: 'EB Garamond', label: 'EB Garamond' },
	{ name: 'Libre Baskerville', label: 'Libre Baskerville' },
	{ name: 'Great Vibes', label: 'Great Vibes (script)' },
	{ name: 'Pinyon Script', label: 'Pinyon Script (script)' },
	{ name: 'Allura', label: 'Allura (script)' },
	{ name: 'Alex Brush', label: 'Alex Brush (script)' },
	{ name: 'Parisienne', label: 'Parisienne (script)' },
	{ name: 'Italianno', label: 'Italianno (script)' },
	{ name: 'Tangerine', label: 'Tangerine (script)' },
	{ name: 'Sacramento', label: 'Sacramento (script)' },
	{ name: 'Dancing Script', label: 'Dancing Script (script)' },
	// Odia.
	{ name: 'Noto Sans Oriya', label: 'Noto Sans Odia (ଓଡ଼ିଆ)' },
	{ name: 'Noto Serif Oriya', label: 'Noto Serif Odia (ଓଡ଼ିଆ)' },
	{ name: 'Anek Odia', label: 'Anek Odia (ଓଡ଼ିଆ)' },
	{ name: 'Baloo Bhaina 2', label: 'Baloo Bhaina 2 (ଓଡ଼ିଆ)' },
	// Hindi (Devanagari).
	{ name: 'Mukta', label: 'Mukta (हिन्दी)' },
	{ name: 'Noto Sans Devanagari', label: 'Noto Sans Devanagari (हिन्दी)' },
	{ name: 'Noto Serif Devanagari', label: 'Noto Serif Devanagari (हिन्दी)' },
	{ name: 'Tiro Devanagari Hindi', label: 'Tiro Devanagari (हिन्दी)' },
	{ name: 'Rozha One', label: 'Rozha One (हिन्दी)' },
	{ name: 'Yatra One', label: 'Yatra One (हिन्दी)' },
	{ name: 'Martel', label: 'Martel (हिन्दी)' },
	{ name: 'Hind', label: 'Hind (हिन्दी)' },
	{ name: 'Laila', label: 'Laila (हिन्दी)' },
	{ name: 'Kalam', label: 'Kalam (हिन्दी, handwriting)' },
	{ name: 'Amita', label: 'Amita (हिन्दी, calligraphy)' },
	// Urdu / Arabic.
	{ name: 'Noto Nastaliq Urdu', label: 'Noto Nastaliq Urdu (اردو)' },
	{ name: 'Gulzar', label: 'Gulzar (اردو)' },
	{ name: 'Noto Naskh Arabic', label: 'Noto Naskh Arabic (عربي)' },
];

const SERIF = [ 'Playfair Display', 'DM Serif Display', 'Merriweather', 'Roboto Slab', 'Cinzel', 'Cinzel Decorative', 'Cormorant Garamond', 'EB Garamond', 'Libre Baskerville', 'Noto Serif Oriya', 'Noto Serif Devanagari', 'Tiro Devanagari Hindi', 'Martel' ];

/*
 * Every stack falls back to Hindi, Odia and Urdu fonts, so a name typed in
 * any of these scripts prints correctly even in an English design.
 */
export function stack( name ) {
	const n = name || 'Inter';
	const generic = SERIF.includes( n ) ? 'Georgia, serif' : 'Arial, sans-serif';
	return `"${ n }", "Noto Sans Devanagari", "Noto Sans Oriya", "Noto Naskh Arabic", "Mukta", ${ generic }`;
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
		// Sample text in every script we use, so each needed font file is fetched.
		jobs.push( document.fonts.load( `${ w } 20px "${ family }"`, 'AaअଅاBb1' ).catch( () => null ) );
	} );
	if ( ! jobs.length ) {
		return;
	}
	await Promise.race( [ Promise.all( jobs ), new Promise( ( r ) => setTimeout( r, 3000 ) ) ] );
}
