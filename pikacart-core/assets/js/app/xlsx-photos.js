/**
 * Photos that come with an Excel import, read in the browser:
 * - pictures pasted into the sheet (Insert → Pictures, anchored on a row),
 * - Excel 365 "Place in cell" pictures,
 * - photo files or a ZIP of photos chosen next to the Excel file.
 */

const IMG = /\.(jpe?g|png|webp)$/i;

function xml( text ) {
	return new window.DOMParser().parseFromString( text, 'application/xml' );
}
function tags( doc, name ) {
	return [ ...doc.getElementsByTagNameNS( '*', name ) ];
}
function attr( el, name ) {
	if ( ! el ) {
		return '';
	}
	if ( el.hasAttribute( name ) ) {
		return el.getAttribute( name );
	}
	// r:id / r:embed style attributes.
	for ( const a of el.attributes ) {
		if ( a.localName === name ) {
			return a.value;
		}
	}
	return '';
}

/** Resolve a relationship target against the folder of the part that owns it. */
function resolve( base, target ) {
	if ( target.startsWith( '/' ) ) {
		return target.slice( 1 );
	}
	const parts = base.split( '/' );
	parts.pop();
	for ( const seg of target.split( '/' ) ) {
		if ( seg === '..' ) {
			parts.pop();
		} else if ( seg !== '.' ) {
			parts.push( seg );
		}
	}
	return parts.join( '/' );
}

async function rels( zip, part ) {
	const i = part.lastIndexOf( '/' );
	const path = `${ part.slice( 0, i ) }/_rels/${ part.slice( i + 1 ) }.rels`;
	const f = zip.file( path );
	const map = {};
	if ( f ) {
		tags( xml( await f.async( 'string' ) ), 'Relationship' ).forEach( ( r ) => {
			map[ r.getAttribute( 'Id' ) ] = resolve( part, r.getAttribute( 'Target' ) );
		} );
	}
	return map;
}

function cellRow( ref ) {
	const m = /\d+$/.exec( ref || '' );
	return m ? Number( m[ 0 ] ) - 1 : -1;
}
function cellCol( ref ) {
	const letters = ( /^[A-Z]+/i.exec( ref || '' ) || [ '' ] )[ 0 ].toUpperCase();
	let n = 0;
	for ( const ch of letters ) {
		n = n * 26 + ( ch.charCodeAt( 0 ) - 64 );
	}
	return n - 1;
}

/**
 * Pictures in one sheet of an .xlsx file.
 * @param {ArrayBuffer} buf
 * @param {string} sheetName
 * @param {number} col  Only pictures in this column (0-based), or -1 for any column.
 * @return {Promise<Map<number, Blob>>} 0-based sheet row → image
 */
export async function sheetImages( buf, sheetName, col = -1 ) {
	const out = new Map();
	let zip;
	try {
		zip = await window.JSZip.loadAsync( buf );
	} catch ( e ) {
		return out; // CSV or old .xls: no pictures.
	}
	const wbFile = zip.file( 'xl/workbook.xml' );
	if ( ! wbFile ) {
		return out;
	}
	const wbRels = await rels( zip, 'xl/workbook.xml' );
	const sheets = tags( xml( await wbFile.async( 'string' ) ), 'sheet' );
	const sheet = sheets.find( ( s ) => s.getAttribute( 'name' ) === sheetName ) || sheets[ 0 ];
	if ( ! sheet ) {
		return out;
	}
	const sheetPath = wbRels[ attr( sheet, 'id' ) ];
	if ( ! sheetPath || ! zip.file( sheetPath ) ) {
		return out;
	}
	const blobOf = async ( path ) => {
		const f = zip.file( path );
		if ( ! f || ! IMG.test( path ) ) {
			return null;
		}
		const ext = path.split( '.' ).pop().toLowerCase();
		const type = ext === 'png' ? 'image/png' : ext === 'webp' ? 'image/webp' : 'image/jpeg';
		return new Blob( [ await f.async( 'uint8array' ) ], { type } );
	};

	// 1. Floating pictures anchored on cells.
	const sRels = await rels( zip, sheetPath );
	const sheetDoc = xml( await zip.file( sheetPath ).async( 'string' ) );
	for ( const d of tags( sheetDoc, 'drawing' ) ) {
		const drawingPath = sRels[ attr( d, 'id' ) ];
		if ( ! drawingPath || ! zip.file( drawingPath ) ) {
			continue;
		}
		const dRels = await rels( zip, drawingPath );
		const dDoc = xml( await zip.file( drawingPath ).async( 'string' ) );
		const anchors = [ ...tags( dDoc, 'twoCellAnchor' ), ...tags( dDoc, 'oneCellAnchor' ) ];
		for ( const a of anchors ) {
			const from = tags( a, 'from' )[ 0 ];
			const blip = tags( a, 'blip' )[ 0 ];
			if ( ! from || ! blip ) {
				continue;
			}
			const row = Number( tags( from, 'row' )[ 0 ]?.textContent );
			const c = Number( tags( from, 'col' )[ 0 ]?.textContent );
			if ( Number.isNaN( row ) || ( col >= 0 && c !== col ) || out.has( row ) ) {
				continue;
			}
			const blob = await blobOf( dRels[ attr( blip, 'embed' ) ] || '' );
			if ( blob ) {
				out.set( row, blob );
			}
		}
	}

	// 2. Excel 365 "Place in cell" pictures (rich values).
	const rvRelFile = 'xl/richData/richValueRel.xml';
	const rvFile = zip.file( 'xl/richData/rdrichvalue.xml' );
	if ( rvFile && zip.file( rvRelFile ) ) {
		const relTargets = await rels( zip, rvRelFile );
		const relIds = tags( xml( await zip.file( rvRelFile ).async( 'string' ) ), 'rel' ).map( ( r ) => attr( r, 'id' ) );
		const values = tags( xml( await rvFile.async( 'string' ) ), 'rv' ).map( ( rv ) => Number( ( tags( rv, 'v' )[ 0 ] || {} ).textContent ) );
		// Cell vm (1-based) → rich value index, through metadata.xml when present.
		let vmToRv = ( vm ) => vm - 1;
		const metaFile = zip.file( 'xl/metadata.xml' );
		if ( metaFile ) {
			const meta = xml( await metaFile.async( 'string' ) );
			const rvb = tags( meta, 'rvb' ).map( ( b ) => Number( b.getAttribute( 'i' ) ) );
			const valueMeta = tags( meta, 'valueMetadata' )[ 0 ];
			const bks = valueMeta ? tags( valueMeta, 'bk' ).map( ( bk ) => Number( ( tags( bk, 'rc' )[ 0 ] || { getAttribute: () => 0 } ).getAttribute( 'v' ) ) ) : [];
			if ( rvb.length && bks.length ) {
				vmToRv = ( vm ) => ( bks[ vm - 1 ] !== undefined && rvb[ bks[ vm - 1 ] ] !== undefined ? rvb[ bks[ vm - 1 ] ] : vm - 1 );
			}
		}
		for ( const c of tags( sheetDoc, 'c' ) ) {
			const vm = Number( c.getAttribute( 'vm' ) );
			if ( ! vm ) {
				continue;
			}
			const ref = c.getAttribute( 'r' );
			const row = cellRow( ref );
			if ( row < 0 || ( col >= 0 && cellCol( ref ) !== col ) || out.has( row ) ) {
				continue;
			}
			const relIndex = values[ vmToRv( vm ) ];
			const blob = Number.isNaN( relIndex ) ? null : await blobOf( relTargets[ relIds[ relIndex ] ] || '' );
			if ( blob ) {
				out.set( row, blob );
			}
		}
	}
	return out;
}

/**
 * Photo files chosen next to the Excel file; ZIP files are opened.
 * @return {Promise<Map<string, Blob>>} lower-case file name (without folder) → image
 */
export async function photoFiles( files ) {
	const out = new Map();
	for ( const f of files ) {
		if ( /\.zip$/i.test( f.name ) ) {
			try {
				const zip = await window.JSZip.loadAsync( f );
				for ( const entry of Object.values( zip.files ) ) {
					const base = entry.name.split( '/' ).pop();
					if ( ! entry.dir && IMG.test( base ) && ! base.startsWith( '.' ) && ! entry.name.includes( '__MACOSX' ) ) {
						out.set( base.toLowerCase(), await entry.async( 'blob' ) );
					}
				}
			} catch ( e ) {
				// Not a readable ZIP: skip it.
			}
		} else if ( IMG.test( f.name ) ) {
			out.set( f.name.toLowerCase(), f );
		}
	}
	return out;
}

/** Find a person's photo among chosen files: the Photo column value, else the ID number. */
export function pickPhoto( files, photoName, idNo ) {
	const name = String( photoName || '' ).trim().split( /[\\/]/ ).pop().toLowerCase();
	if ( name ) {
		if ( files.has( name ) ) {
			return files.get( name );
		}
		for ( const ext of [ '.jpg', '.jpeg', '.png', '.webp' ] ) {
			if ( files.has( name + ext ) ) {
				return files.get( name + ext );
			}
		}
	}
	const id = String( idNo || '' ).trim().toLowerCase();
	for ( const ext of [ '.jpg', '.jpeg', '.png', '.webp' ] ) {
		if ( id && files.has( id + ext ) ) {
			return files.get( id + ext );
		}
	}
	return null;
}
