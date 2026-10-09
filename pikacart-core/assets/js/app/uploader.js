/**
 * Image box for logo, signature and seal: preview on a checkerboard
 * (so transparency is visible), upload, replace and remove.
 */

import { api } from './api.js';
import { esc, icon, toast } from './ui.js';

const { __ } = window.wp.i18n;

const LABELS = {
	logo: () => __( 'Logo', 'pikacart' ),
	sign: () => __( 'Signature', 'pikacart' ),
	seal: () => __( 'Official seal', 'pikacart' ),
};

const HINTS = {
	logo: () => __( 'PNG with transparent background works best.', 'pikacart' ),
	sign: () => __( 'Sign on white paper, photograph it, upload as PNG or JPG.', 'pikacart' ),
	seal: () => __( 'Round stamp or seal, PNG preferred.', 'pikacart' ),
};

export function uploaderHTML( type, url ) {
	return `<div class="uploader" data-type="${ esc( type ) }">
		<div class="uploader-preview ${ url ? 'has-image' : '' }">
			${ url ? `<img src="${ esc( url ) }" alt="${ esc( LABELS[ type ]() ) }">` : icon( 'image' ) }
		</div>
		<div class="uploader-body">
			<strong>${ esc( LABELS[ type ]() ) }</strong>
			<small>${ esc( HINTS[ type ]() ) }</small>
			<div class="uploader-actions">
				<label class="btn btn-sm">
					${ icon( 'upload' ) }<span>${ esc( url ? __( 'Replace', 'pikacart' ) : __( 'Upload', 'pikacart' ) ) }</span>
					<input type="file" accept="image/png,image/jpeg,image/webp" hidden>
				</label>
				${ url ? `<button type="button" class="btn btn-sm btn-ghost" data-remove>${ esc( __( 'Remove', 'pikacart' ) ) }</button>` : '' }
			</div>
		</div>
	</div>`;
}

/**
 * Wire every .uploader inside root. onDone(me) receives the fresh account data.
 */
export function bindUploaders( root, maxMb, onDone ) {
	root.querySelectorAll( '.uploader' ).forEach( ( box ) => {
		const type = box.dataset.type;
		const input = box.querySelector( 'input[type=file]' );

		input.addEventListener( 'change', async () => {
			const file = input.files[ 0 ];
			if ( ! file ) {
				return;
			}
			if ( ! [ 'image/png', 'image/jpeg', 'image/webp' ].includes( file.type ) ) {
				toast( __( 'Only JPG, PNG and WebP images are allowed.', 'pikacart' ), 'error' );
				return;
			}
			if ( file.size > maxMb * 1024 * 1024 ) {
				toast( __( 'This image is too large. Please choose a smaller file.', 'pikacart' ), 'error' );
				return;
			}
			const form = new FormData();
			form.append( 'type', type );
			form.append( 'file', file );
			box.classList.add( 'is-busy' );
			try {
				const res = await api( 'org/upload', { method: 'POST', form } );
				toast( res.message );
				onDone( res.me );
			} catch ( err ) {
				toast( err.message, 'error' );
			} finally {
				box.classList.remove( 'is-busy' );
				input.value = '';
			}
		} );

		const remove = box.querySelector( '[data-remove]' );
		if ( remove ) {
			remove.addEventListener( 'click', async () => {
				box.classList.add( 'is-busy' );
				try {
					const res = await api( 'org/remove-image', { method: 'POST', body: { type } } );
					toast( res.message );
					onDone( res.me );
				} catch ( err ) {
					toast( err.message, 'error' );
				} finally {
					box.classList.remove( 'is-busy' );
				}
			} );
		}
	} );
}
