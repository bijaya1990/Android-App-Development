/**
 * Photos in the browser: crop to the card's photo shape (Cropper.js) and
 * resize/compress before upload, so uploads are small and fast.
 */

import { esc, icon } from './ui.js';

const { __ } = window.wp.i18n;

/** Load a File into an <img>. */
function fileToImage( file ) {
	return new Promise( ( resolve, reject ) => {
		const img = new Image();
		img.onload = () => resolve( img );
		img.onerror = () => reject( { message: __( 'This file is not a readable image.', 'pikacart' ) } );
		img.src = URL.createObjectURL( file );
	} );
}

/**
 * Resize to cover the target box (centre crop) and return a JPEG Blob.
 */
export async function compressPhoto( file, w = 600, h = 750, quality = 0.86 ) {
	const img = await fileToImage( file );
	const c = document.createElement( 'canvas' );
	c.width = w;
	c.height = h;
	const ctx = c.getContext( '2d' );
	ctx.fillStyle = '#ffffff';
	ctx.fillRect( 0, 0, w, h );
	const r = Math.max( w / img.width, h / img.height );
	const dw = img.width * r;
	const dh = img.height * r;
	ctx.imageSmoothingQuality = 'high';
	ctx.drawImage( img, ( w - dw ) / 2, ( h - dh ) / 2, dw, dh );
	URL.revokeObjectURL( img.src );
	return new Promise( ( resolve ) => c.toBlob( resolve, 'image/jpeg', quality ) );
}

/**
 * Show a crop dialog. Resolves with a JPEG Blob, or null if cancelled.
 * @param {File} file
 * @param {number} aspect  width / height (0.8 for passport photos)
 */
export function cropPhoto( file, aspect = 0.8 ) {
	return new Promise( ( resolve ) => {
		const root = document.getElementById( 'modal-root' );
		const wrap = document.createElement( 'div' );
		wrap.className = 'modal-wrap';
		wrap.innerHTML = `<div class="modal modal-crop" role="dialog" aria-modal="true" aria-label="${ esc( __( 'Crop photo', 'pikacart' ) ) }">
			<h2>${ esc( __( 'Crop photo', 'pikacart' ) ) }</h2>
			<div class="crop-area"><img alt=""></div>
			<div class="crop-tools">
				<button type="button" class="btn btn-sm" data-rot="-90">${ esc( __( 'Rotate left', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-rot="90">${ esc( __( 'Rotate right', 'pikacart' ) ) }</button>
				<button type="button" class="btn btn-sm" data-zoom="0.1">+</button>
				<button type="button" class="btn btn-sm" data-zoom="-0.1">−</button>
			</div>
			<div class="modal-actions"><button type="button" class="btn" data-x="0">${ esc( __( 'Cancel', 'pikacart' ) ) }</button><button type="button" class="btn btn-primary" data-x="1">${ icon( 'check' ) }${ esc( __( 'Use photo', 'pikacart' ) ) }</button></div>
		</div>`;
		root.appendChild( wrap );
		const img = wrap.querySelector( 'img' );
		let cropper = null;
		img.onload = () => {
			cropper = new window.Cropper( img, { aspectRatio: aspect, viewMode: 1, autoCropArea: 0.92, background: false, dragMode: 'move' } );
		};
		img.src = URL.createObjectURL( file );
		const done = ( blob ) => {
			if ( cropper ) {
				cropper.destroy();
			}
			URL.revokeObjectURL( img.src );
			wrap.remove();
			resolve( blob );
		};
		wrap.addEventListener( 'click', ( e ) => {
			const r = e.target.closest( '[data-rot]' );
			if ( r && cropper ) {
				cropper.rotate( Number( r.dataset.rot ) );
			}
			const z = e.target.closest( '[data-zoom]' );
			if ( z && cropper ) {
				cropper.zoom( Number( z.dataset.zoom ) );
			}
			const x = e.target.closest( '[data-x]' );
			if ( x ) {
				if ( x.dataset.x === '0' || ! cropper ) {
					done( null );
					return;
				}
				const w = 600;
				const h = Math.round( w / aspect );
				cropper.getCroppedCanvas( { width: w, height: h, fillColor: '#ffffff', imageSmoothingQuality: 'high' } ).toBlob( ( b ) => done( b ), 'image/jpeg', 0.86 );
			}
		} );
	} );
}
