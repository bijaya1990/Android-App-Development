/**
 * Super Admin support inbox: live chat with customers.
 * Uses short polling (every few seconds) so it works on shared hosting.
 */
( function () {
	'use strict';

	var __ = window.wp && wp.i18n ? wp.i18n.__ : function ( s ) { return s; };
	var cfg = window.PKC_SUPPORT;
	var app = document.getElementById( 'pkc-chat-app' );
	if ( ! cfg || ! app ) {
		return;
	}

	var listEl = document.getElementById( 'pkc-chat-items' );
	var threadEl = document.getElementById( 'pkc-chat-thread' );
	var state = { filter: 'open', search: '', tickets: [], current: 0, lastId: 0, lastDay: '', seenTs: 0, unread: 0, sending: false, lastTyping: 0 };
	var listTimer = null;
	var threadTimer = null;
	var baseTitle = document.title;

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}
	function linkify( text ) {
		return esc( text ).replace( /(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>' ).replace( /\n/g, '<br>' );
	}
	function api( path, opts ) {
		opts = opts || {};
		var headers = { 'X-WP-Nonce': cfg.nonce };
		if ( opts.body ) {
			headers[ 'Content-Type' ] = 'application/json';
		}
		return fetch( cfg.rest + path, { method: opts.method || 'GET', headers: headers, credentials: 'same-origin', body: opts.body ? JSON.stringify( opts.body ) : undefined } )
			.then( function ( r ) {
				return r.json().catch( function () { return {}; } ).then( function ( d ) {
					if ( ! r.ok ) {
						throw new Error( d.message || __( 'Something went wrong. Please try again.', 'pikacart' ) );
					}
					return d;
				} );
			} );
	}
	function visible() {
		return document.visibilityState === 'visible';
	}

	/* ---------- Desktop alerts ---------- */
	function notifyDesktop( title, body ) {
		if ( ! ( 'Notification' in window ) || Notification.permission !== 'granted' || visible() ) {
			return;
		}
		try {
			new Notification( title, { body: body } );
		} catch ( e ) {}
	}

	/* ---------- List ---------- */
	function loadList() {
		var q = '?status=' + encodeURIComponent( state.filter ) + '&search=' + encodeURIComponent( state.search );
		return api( 'tickets' + q ).then( function ( d ) {
			if ( d.unread > state.unread && state.unread !== -1 ) {
				var fresh = d.tickets.filter( function ( t ) { return t.unread; } )[ 0 ];
				if ( fresh ) {
					notifyDesktop( __( 'New support message', 'pikacart' ), ( fresh.org ? fresh.org.name + ': ' : '' ) + fresh.last );
				}
			}
			state.unread = d.unread;
			state.tickets = d.tickets;
			document.title = ( d.unread ? '(' + d.unread + ') ' : '' ) + baseTitle;
			drawList();
		} ).catch( function () {} );
	}

	function drawList() {
		if ( ! state.tickets.length ) {
			listEl.innerHTML = '<p class="pkc-empty">' + esc( __( 'No conversations here.', 'pikacart' ) ) + '</p>';
			return;
		}
		listEl.innerHTML = state.tickets.map( function ( t ) {
			var org = t.org || {};
			var initials = ( org.name || '?' ).split( /\s+/ ).slice( 0, 2 ).map( function ( w ) { return w.charAt( 0 ).toUpperCase(); } ).join( '' );
			return '<button type="button" class="pkc-chat-item' + ( t.id === state.current ? ' is-active' : '' ) + ( t.unread ? ' is-unread' : '' ) + '" data-id="' + t.id + '">' +
				'<span class="pkc-chat-avatar">' + ( org.logo ? '<img src="' + esc( org.logo ) + '" alt="">' : esc( initials ) ) + '</span>' +
				'<span class="pkc-chat-meta"><span class="pkc-chat-row"><strong>' + esc( org.name || __( '(deleted account)', 'pikacart' ) ) + '</strong><small>' + esc( t.ago ) + '</small></span>' +
				'<span class="pkc-chat-subject">#' + t.id + ' · ' + esc( t.subject ) + ( t.status === 'closed' ? ' <em>' + esc( __( 'Solved', 'pikacart' ) ) + '</em>' : '' ) + '</span>' +
				'<span class="pkc-chat-last">' + ( t.lastBy === 'admin' ? esc( __( 'You:', 'pikacart' ) ) + ' ' : '' ) + esc( t.last ) + '</span></span>' +
				( t.unread ? '<span class="pkc-chat-dot" aria-label="' + esc( __( 'Unread', 'pikacart' ) ) + '"></span>' : '' ) +
				'</button>';
		} ).join( '' );
	}

	/* ---------- Thread ---------- */
	function openThread( id ) {
		state.current = id;
		state.lastId = 0;
		state.lastDay = '';
		app.classList.add( 'show-thread' );
		history.replaceState( {}, '', updateQuery( 'ticket', id ) );
		threadEl.innerHTML = '<div class="pkc-chat-placeholder"><p>' + esc( __( 'Loading…', 'pikacart' ) ) + '</p></div>';
		drawList();
		api( 'tickets/' + id ).then( function ( d ) {
			drawThreadShell( d.ticket );
			applyPayload( d );
			loadList();
		} ).catch( function ( e ) {
			threadEl.innerHTML = '<div class="pkc-chat-placeholder"><p>' + esc( e.message ) + '</p></div>';
		} );
		scheduleThread();
	}

	function updateQuery( key, value ) {
		var url = new URL( window.location.href );
		if ( value ) {
			url.searchParams.set( key, value );
		} else {
			url.searchParams.delete( key );
		}
		return url.toString();
	}

	function drawThreadShell( t ) {
		var org = t.org || {};
		threadEl.innerHTML =
			'<header class="pkc-thread-head">' +
				'<button type="button" class="pkc-btn pkc-thread-back" data-back>← ' + esc( __( 'Inbox', 'pikacart' ) ) + '</button>' +
				'<div class="pkc-thread-title"><strong>' + esc( org.name || '' ) + '</strong><span>#' + t.id + ' · ' + esc( t.subject ) + '</span>' +
				'<small class="pkc-thread-contact">' + esc( org.email || '' ) + ( org.mobile ? ' · ' + esc( org.mobile ) : '' ) + ' · ' + esc( org.status || '' ) + ' · <a href="' + esc( org.url || '#' ) + '">' + esc( __( 'Open account', 'pikacart' ) ) + '</a></small>' +
				'<small class="pkc-thread-online" data-online></small></div>' +
				'<button type="button" class="pkc-btn" data-status></button>' +
			'</header>' +
			'<div class="pkc-thread-body" data-body aria-live="polite"></div>' +
			'<div class="pkc-typing" data-typing hidden><span></span><span></span><span></span> ' + esc( __( 'Customer is typing…', 'pikacart' ) ) + '</div>' +
			'<form class="pkc-composer" data-composer>' +
				'<textarea rows="1" placeholder="' + esc( __( 'Type your reply… (Enter to send, Shift+Enter for a new line)', 'pikacart' ) ) + '" maxlength="4000" aria-label="' + esc( __( 'Reply', 'pikacart' ) ) + '"></textarea>' +
				'<button type="submit" class="pkc-btn pkc-btn-primary">' + esc( __( 'Send', 'pikacart' ) ) + '</button>' +
			'</form>';
		setStatusButton( t.status );
		var ta = threadEl.querySelector( 'textarea' );
		ta.addEventListener( 'input', function () {
			ta.style.height = 'auto';
			ta.style.height = Math.min( ta.scrollHeight, 160 ) + 'px';
			var now = Date.now();
			if ( now - state.lastTyping > 3000 && ta.value.trim() ) {
				state.lastTyping = now;
				api( 'tickets/' + state.current + '/typing', { method: 'POST', body: {} } ).catch( function () {} );
			}
		} );
		ta.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' && ! e.shiftKey ) {
				e.preventDefault();
				send();
			}
		} );
		threadEl.querySelector( '[data-composer]' ).addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			send();
		} );
		ta.focus();
	}

	function setStatusButton( status ) {
		var b = threadEl.querySelector( '[data-status]' );
		if ( ! b ) {
			return;
		}
		b.dataset.next = status === 'closed' ? 'open' : 'closed';
		b.textContent = status === 'closed' ? __( 'Reopen', 'pikacart' ) : __( 'Mark as solved', 'pikacart' );
	}

	function bubble( m ) {
		var out = '';
		if ( m.day !== state.lastDay ) {
			state.lastDay = m.day;
			out += '<div class="pkc-day"><span>' + esc( m.dayText ) + '</span></div>';
		}
		if ( m.author === 'system' ) {
			return out + '<div class="pkc-system">' + esc( m.text ) + '</div>';
		}
		return out + '<div class="pkc-msg ' + ( m.mine ? 'is-mine' : 'is-theirs' ) + '" data-ts="' + m.ts + '">' +
			'<div class="pkc-msg-bubble">' + linkify( m.text ) + '</div>' +
			'<div class="pkc-msg-meta">' + esc( m.name ) + ' · ' + esc( m.time ) + '</div></div>';
	}

	function applyPayload( d ) {
		var body = threadEl.querySelector( '[data-body]' );
		if ( ! body || d.ticket.id !== state.current ) {
			return;
		}
		if ( d.messages.length ) {
			var nearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 120 || state.lastId === 0;
			var hadMessages = state.lastId !== 0;
			body.insertAdjacentHTML( 'beforeend', d.messages.map( bubble ).join( '' ) );
			state.lastId = d.messages[ d.messages.length - 1 ].id;
			if ( nearBottom ) {
				body.scrollTop = body.scrollHeight;
			}
			var theirs = d.messages.filter( function ( m ) { return ! m.mine && m.author !== 'system'; } );
			if ( hadMessages && theirs.length ) {
				notifyDesktop( ( d.ticket.org ? d.ticket.org.name : '' ), theirs[ theirs.length - 1 ].text );
			}
		}
		if ( ! body.children.length ) {
			body.innerHTML = '<p class="pkc-empty">' + esc( __( 'No messages yet.', 'pikacart' ) ) + '</p>';
		}
		// "Seen" under my last message.
		body.querySelectorAll( '.pkc-seen' ).forEach( function ( el ) { el.remove(); } );
		var mine = body.querySelectorAll( '.pkc-msg.is-mine' );
		var last = mine[ mine.length - 1 ];
		if ( last && d.seenTs && Number( last.dataset.ts ) <= d.seenTs ) {
			last.insertAdjacentHTML( 'beforeend', '<div class="pkc-seen">✓✓ ' + esc( __( 'Seen', 'pikacart' ) ) + '</div>' );
		}
		threadEl.querySelector( '[data-typing]' ).hidden = ! d.typing;
		threadEl.querySelector( '[data-online]' ).textContent = d.online ? '● ' + __( 'Customer is online now', 'pikacart' ) : '';
		setStatusButton( d.ticket.status );
	}

	function pollThread() {
		if ( ! state.current ) {
			return;
		}
		var id = state.current;
		api( 'tickets/' + id + '?after=' + state.lastId ).then( function ( d ) {
			if ( id === state.current ) {
				applyPayload( d );
			}
		} ).catch( function () {} ).finally( scheduleThread );
	}

	function scheduleThread() {
		clearTimeout( threadTimer );
		threadTimer = setTimeout( pollThread, visible() ? 3500 : 15000 );
	}

	function send() {
		var ta = threadEl.querySelector( 'textarea' );
		var text = ta.value.trim();
		if ( ! text || state.sending ) {
			return;
		}
		state.sending = true;
		var btn = threadEl.querySelector( '[data-composer] button' );
		btn.disabled = true;
		api( 'tickets/' + state.current + '/messages', { method: 'POST', body: { message: text, after: state.lastId } } )
			.then( function ( d ) {
				ta.value = '';
				ta.style.height = 'auto';
				applyPayload( d );
				loadList();
			} )
			.catch( function ( e ) {
				window.alert( e.message );
			} )
			.finally( function () {
				state.sending = false;
				btn.disabled = false;
				ta.focus();
			} );
	}

	/* ---------- Events ---------- */
	app.addEventListener( 'click', function ( e ) {
		var item = e.target.closest( '.pkc-chat-item' );
		if ( item ) {
			openThread( Number( item.dataset.id ) );
			return;
		}
		var f = e.target.closest( '[data-filter]' );
		if ( f ) {
			app.querySelectorAll( '[data-filter]' ).forEach( function ( b ) { b.classList.toggle( 'is-active', b === f ); } );
			state.filter = f.dataset.filter;
			loadList();
			return;
		}
		if ( e.target.closest( '[data-back]' ) ) {
			app.classList.remove( 'show-thread' );
			state.current = 0;
			history.replaceState( {}, '', updateQuery( 'ticket', 0 ) );
			drawList();
			return;
		}
		var st = e.target.closest( '[data-status]' );
		if ( st ) {
			st.disabled = true;
			api( 'tickets/' + state.current + '/status', { method: 'POST', body: { status: st.dataset.next, after: state.lastId } } )
				.then( function ( d ) {
					applyPayload( d );
					loadList();
				} )
				.catch( function ( err ) { window.alert( err.message ); } )
				.finally( function () { st.disabled = false; } );
		}
	} );

	var searchTimer;
	document.getElementById( 'pkc-chat-search' ).addEventListener( 'input', function ( e ) {
		clearTimeout( searchTimer );
		searchTimer = setTimeout( function () {
			state.search = e.target.value.trim();
			loadList();
		}, 350 );
	} );

	document.addEventListener( 'visibilitychange', function () {
		if ( visible() ) {
			loadList();
			pollThread();
		}
	} );

	// Offer desktop alerts once.
	if ( 'Notification' in window && Notification.permission === 'default' ) {
		var tools = app.querySelector( '.pkc-chat-tools' );
		var ask = document.createElement( 'button' );
		ask.type = 'button';
		ask.className = 'pkc-btn pkc-chat-alerts';
		ask.textContent = __( 'Turn on desktop alerts for new messages', 'pikacart' );
		ask.addEventListener( 'click', function () {
			Notification.requestPermission().then( function () { ask.remove(); } );
		} );
		tools.appendChild( ask );
	}

	state.unread = -1;
	loadList().then( function () {
		state.unread = state.unread === -1 ? 0 : state.unread;
	} );
	listTimer = setInterval( function () {
		if ( visible() ) {
			loadList();
		}
	}, 8000 );
	if ( cfg.ticket ) {
		openThread( cfg.ticket );
	}
}() );
