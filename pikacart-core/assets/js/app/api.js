/**
 * Talks to the Pikacart REST API with the WordPress nonce.
 * Errors are thrown as { message, code, data } with plain-language messages.
 */

const { __ } = window.wp.i18n;
const cfg = window.PKC;

export function setNonce( nonce ) {
	cfg.nonce = nonce;
}

export async function api( path, { method = 'GET', body = null, form = null } = {} ) {
	const headers = { 'X-WP-Nonce': cfg.nonce };
	let payload;
	if ( form ) {
		payload = form; // FormData for file uploads; the browser sets the content type.
	} else if ( body ) {
		headers[ 'Content-Type' ] = 'application/json';
		payload = JSON.stringify( body );
	}

	let res;
	try {
		// GET requests carry a unique value so no browser, server or plugin cache can serve old data.
		const url = method === 'GET' ? cfg.rest + path + ( path.includes( '?' ) ? '&' : '?' ) + '_=' + Date.now() : cfg.rest + path;
		res = await fetch( url, { method, headers, body: payload, credentials: 'same-origin', cache: 'no-store' } );
	} catch ( e ) {
		throw { message: __( 'No internet connection. Please check your network and try again.', 'pikacart' ), code: 'offline' };
	}

	let data = {};
	try {
		data = await res.json();
	} catch ( e ) {
		data = {};
	}

	if ( ! res.ok ) {
		if ( res.status === 401 || data.code === 'rest_cookie_invalid_nonce' ) {
			throw { message: __( 'Your session has ended. Please log in again.', 'pikacart' ), code: 'auth', data: data.data };
		}
		throw {
			message: data.message || __( 'Something went wrong. Please try again.', 'pikacart' ),
			code: data.code || 'error',
			data: data.data || {},
		};
	}
	return data;
}
