/**
 * Support: live chat with the Pikacart team, one conversation per topic.
 * Routes: support, support/new, support/{id}.
 * New messages arrive by polling every few seconds (works on shared hosting).
 */

import { api } from '../api.js';
import { esc, icon, toast, withLoading, illustration, confirmDialog } from '../ui.js';
import { onPulse, refreshLive } from '../live.js';

const { __, sprintf } = window.wp.i18n;

function linkify( text ) {
	return esc( text )
		.replace( /(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>' )
		.replace( /\n/g, '<br>' );
}

function faqHTML( ctx ) {
	const price = ctx.me.plan ? ctx.me.plan.price_text : '₹59';
	const faq = [
		[ __( 'Is Pikacart really free?', 'pikacart' ), sprintf( __( 'Yes. The Free plan is free forever with every feature. Downloaded cards carry a "%1$s" watermark. Upgrade for %2$s per month for clean cards.', 'pikacart' ), ctx.me.watermark.text, price ) ],
		[ __( 'My bank does not support autopay. Can I still pay?', 'pikacart' ), __( 'Yes. On the Subscription page choose "Pay once". You pay for one period by UPI, card or net banking, with no automatic renewal.', 'pikacart' ) ],
		[ __( 'How do I cancel autopay?', 'pikacart' ), __( 'Open Subscription and click "Cancel autopay". You keep access until the end of the period you already paid for.', 'pikacart' ) ],
		[ __( 'Money was deducted but my plan is not active.', 'pikacart' ), __( 'Payments are usually confirmed within a few minutes. If not, start a chat here with your payment ID from the bank SMS.', 'pikacart' ) ],
		[ __( 'Can I make Aadhaar, PAN or other government ID cards?', 'pikacart' ), __( 'No. Pikacart is only for cards your organisation issues to its own people. Imitating government-issued IDs is not allowed.', 'pikacart' ) ],
	];
	return `<div class="faq">${ faq.map( ( q ) => `<details><summary>${ esc( q[ 0 ] ) }${ icon( 'chevron-down' ) }</summary><p>${ esc( q[ 1 ] ) }</p></details>` ).join( '' ) }</div>`;
}

export default function support( el, ctx, params ) {
	const cfg = window.PKC;
	const mode = params[ 0 ] === 'new' ? 'new' : ( /^\d+$/.test( params[ 0 ] || '' ) ? 'thread' : 'list' );
	const ticketId = mode === 'thread' ? Number( params[ 0 ] ) : 0;
	const st = { tickets: [], lastId: 0, lastDay: '', timer: null, alive: true, sending: false, lastTyping: 0, online: false };

	el.innerHTML = `<div class="chat-app ${ mode !== 'list' ? 'show-thread' : '' }">
		<aside class="chat-list">
			<div class="chat-list-head">
				<div><h2>${ esc( __( 'Your conversations', 'pikacart' ) ) }</h2><span class="team-status" data-team></span></div>
				<a class="btn btn-primary btn-sm" href="${ esc( ctx.url( 'support/new' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'New chat', 'pikacart' ) ) }</a>
			</div>
			<div class="chat-items" data-items><div class="skeleton" style="height:64px;margin:12px"></div><div class="skeleton" style="height:64px;margin:12px"></div></div>
			<div class="chat-contact">
				<a href="mailto:${ esc( cfg.support ) }">${ icon( 'mail' ) }${ esc( cfg.support ) }</a>
				${ cfg.phone ? `<a href="tel:${ esc( cfg.phone.replace( /[^0-9+]/g, '' ) ) }">${ icon( 'phone' ) }${ esc( cfg.phone ) }</a>` : '' }
			</div>
		</aside>
		<section class="chat-thread" data-thread></section>
	</div>`;

	const items = el.querySelector( '[data-items]' );
	const thread = el.querySelector( '[data-thread]' );

	function teamStatus() {
		const t = el.querySelector( '[data-team]' );
		t.className = `team-status ${ st.online ? 'is-online' : '' }`;
		t.textContent = st.online ? __( 'Support team is online', 'pikacart' ) : __( 'We usually reply within a few hours', 'pikacart' );
	}

	async function loadList() {
		try {
			const d = await api( 'support/tickets' );
			if ( ! st.alive ) {
				return;
			}
			st.tickets = d.tickets;
			st.online = d.online;
			teamStatus();
			drawList();
		} catch ( e ) {
			items.innerHTML = `<p class="chat-empty">${ esc( e.message ) }</p>`;
		}
	}

	function drawList() {
		if ( ! st.tickets.length ) {
			items.innerHTML = `<div class="chat-empty">${ illustration( 'support' ) }<p>${ esc( __( 'No conversations yet. Start a chat and our team will help you.', 'pikacart' ) ) }</p></div>`;
			return;
		}
		items.innerHTML = st.tickets.map( ( t ) => `<a class="chat-item ${ t.id === ticketId ? 'is-active' : '' } ${ t.unread ? 'is-unread' : '' }" href="${ esc( ctx.url( 'support/' + t.id ) ) }" data-link>
			<span class="chat-item-row"><strong>${ esc( t.subject ) }</strong><small>${ esc( t.ago ) }</small></span>
			<span class="chat-item-last">${ t.lastBy === 'org' ? esc( __( 'You:', 'pikacart' ) ) + ' ' : '' }${ esc( t.last ) }</span>
			<span class="chat-item-foot"><span class="chat-tag ${ t.status === 'closed' ? 'is-closed' : '' }">${ esc( t.status === 'closed' ? __( 'Solved', 'pikacart' ) : __( 'Open', 'pikacart' ) ) }</span>${ t.unread ? `<span class="chat-new">${ esc( __( 'New reply', 'pikacart' ) ) }</span>` : '' }</span>
		</a>` ).join( '' );
	}

	/* ---------- New conversation ---------- */
	function drawNew() {
		thread.innerHTML = `<div class="chat-new-form">
			<a class="btn btn-ghost btn-sm chat-back" href="${ esc( ctx.url( 'support' ) ) }" data-link>${ icon( 'chevron-left' ) }${ esc( __( 'Back', 'pikacart' ) ) }</a>
			<h2>${ esc( __( 'Start a new chat', 'pikacart' ) ) }</h2>
			<p class="muted">${ esc( __( 'Tell us what you need. You can keep chatting in the same conversation.', 'pikacart' ) ) }</p>
			<form novalidate>
				<div class="field"><label for="s-subject">${ esc( __( 'Subject', 'pikacart' ) ) }</label><input id="s-subject" name="subject" type="text" maxlength="150" placeholder="${ esc( __( 'e.g. Help with payment', 'pikacart' ) ) }" required></div>
				<div class="topic-chips">
					${ [ __( 'Payment or plan', 'pikacart' ), __( 'Card design help', 'pikacart' ), __( 'Import / Excel', 'pikacart' ), __( 'Printing', 'pikacart' ), __( 'Something else', 'pikacart' ) ].map( ( t ) => `<button type="button" class="chip" data-topic="${ esc( t ) }">${ esc( t ) }</button>` ).join( '' ) }
				</div>
				<div class="field"><label for="s-message">${ esc( __( 'Message', 'pikacart' ) ) }</label><textarea id="s-message" name="message" rows="5" maxlength="4000" required></textarea></div>
				<button type="submit" class="btn btn-primary">${ icon( 'chevron-right' ) }${ esc( __( 'Start chat', 'pikacart' ) ) }</button>
			</form>
			<h3 class="section-title">${ esc( __( 'Quick answers', 'pikacart' ) ) }</h3>
			${ faqHTML( ctx ) }
		</div>`;
		const form = thread.querySelector( 'form' );
		thread.querySelectorAll( '[data-topic]' ).forEach( ( b ) => b.addEventListener( 'click', () => {
			form.subject.value = b.dataset.topic;
			form.message.focus();
		} ) );
		form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			withLoading( form.querySelector( '[type=submit]' ), async () => {
				try {
					const d = await api( 'support/tickets', { method: 'POST', body: { subject: form.subject.value, message: form.message.value } } );
					toast( __( 'Chat started. We will reply here soon.', 'pikacart' ) );
					ctx.navigate( 'support/' + d.ticket.id );
				} catch ( err ) {
					toast( err.message, 'error' );
				}
			} );
		} );
	}

	/* ---------- Placeholder (desktop, no chat selected) ---------- */
	function drawIntro() {
		thread.innerHTML = `<div class="chat-intro">
			${ illustration( 'support' ) }
			<h2>${ esc( __( 'How can we help?', 'pikacart' ) ) }</h2>
			<p class="muted">${ esc( __( 'Chat with the Pikacart team. Replies appear here instantly and you get a notification.', 'pikacart' ) ) }</p>
			<a class="btn btn-primary" href="${ esc( ctx.url( 'support/new' ) ) }" data-link>${ icon( 'plus' ) }${ esc( __( 'Start a new chat', 'pikacart' ) ) }</a>
			<h3 class="section-title">${ esc( __( 'Quick answers', 'pikacart' ) ) }</h3>
			${ faqHTML( ctx ) }
		</div>`;
	}

	/* ---------- Thread ---------- */
	function drawThreadShell( t ) {
		thread.innerHTML = `<header class="thread-head">
				<a class="icon-btn chat-back" href="${ esc( ctx.url( 'support' ) ) }" data-link aria-label="${ esc( __( 'Back to conversations', 'pikacart' ) ) }">${ icon( 'chevron-left' ) }</a>
				<span class="thread-avatar">${ icon( 'help' ) }</span>
				<div class="thread-title"><strong>${ esc( t.subject ) }</strong><small data-presence></small></div>
				<button type="button" class="btn btn-sm" data-status></button>
			</header>
			<div class="thread-body" data-body aria-live="polite"></div>
			<div class="typing" data-typing hidden><span></span><span></span><span></span> ${ esc( __( 'Support is typing…', 'pikacart' ) ) }</div>
			<div class="closed-note" data-closed hidden>${ icon( 'check-circle' ) } ${ esc( __( 'This conversation is solved. Send a message to reopen it.', 'pikacart' ) ) }</div>
			<form class="composer" data-composer>
				<textarea rows="1" maxlength="4000" placeholder="${ esc( __( 'Type a message…', 'pikacart' ) ) }" aria-label="${ esc( __( 'Message', 'pikacart' ) ) }"></textarea>
				<button type="submit" class="btn btn-primary composer-send" aria-label="${ esc( __( 'Send', 'pikacart' ) ) }">${ icon( 'chevron-right' ) }</button>
			</form>`;
		const ta = thread.querySelector( 'textarea' );
		ta.addEventListener( 'input', () => {
			ta.style.height = 'auto';
			ta.style.height = Math.min( ta.scrollHeight, 140 ) + 'px';
			const now = Date.now();
			if ( ta.value.trim() && now - st.lastTyping > 3000 ) {
				st.lastTyping = now;
				api( `support/tickets/${ ticketId }/typing`, { method: 'POST', body: {} } ).catch( () => {} );
			}
		} );
		ta.addEventListener( 'keydown', ( e ) => {
			// Enter sends on computers; on phones Enter adds a new line.
			if ( e.key === 'Enter' && ! e.shiftKey && window.matchMedia( '(pointer: fine)' ).matches ) {
				e.preventDefault();
				send();
			}
		} );
		thread.querySelector( '[data-composer]' ).addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			send();
		} );
		thread.querySelector( '[data-status]' ).addEventListener( 'click', async ( e ) => {
			const btn = e.currentTarget;
			const next = btn.dataset.next;
			if ( next === 'closed' ) {
				const ok = await confirmDialog( { title: __( 'Mark this conversation as solved?', 'pikacart' ), message: __( 'You can reopen it anytime by sending a new message.', 'pikacart' ), confirm: __( 'Yes, solved', 'pikacart' ) } );
				if ( ! ok ) {
					return;
				}
			}
			withLoading( btn, async () => {
				try {
					apply( await api( `support/tickets/${ ticketId }/status`, { method: 'POST', body: { status: next, after: st.lastId } } ) );
					loadList();
				} catch ( err ) {
					toast( err.message, 'error' );
				}
			} );
		} );
		if ( window.matchMedia( '(pointer: fine)' ).matches ) {
			ta.focus();
		}
	}

	function bubble( m ) {
		let out = '';
		if ( m.day !== st.lastDay ) {
			st.lastDay = m.day;
			out += `<div class="day"><span>${ esc( m.dayText ) }</span></div>`;
		}
		if ( m.author === 'system' ) {
			return out + `<div class="sys">${ esc( m.text ) }</div>`;
		}
		return out + `<div class="msg ${ m.mine ? 'is-mine' : 'is-theirs' }" data-ts="${ m.ts }">
			${ m.mine ? '' : `<span class="msg-name">${ esc( m.name ) }</span>` }
			<div class="msg-bubble">${ linkify( m.text ) }</div>
			<span class="msg-time">${ esc( m.time ) }</span>
		</div>`;
	}

	function apply( d ) {
		const body = thread.querySelector( '[data-body]' );
		if ( ! body ) {
			return;
		}
		if ( d.messages.length ) {
			const nearBottom = st.lastId === 0 || body.scrollHeight - body.scrollTop - body.clientHeight < 140;
			body.insertAdjacentHTML( 'beforeend', d.messages.map( bubble ).join( '' ) );
			st.lastId = d.messages[ d.messages.length - 1 ].id;
			if ( nearBottom ) {
				body.scrollTop = body.scrollHeight;
			}
		}
		body.querySelectorAll( '.seen' ).forEach( ( x ) => x.remove() );
		const mine = body.querySelectorAll( '.msg.is-mine' );
		const last = mine[ mine.length - 1 ];
		if ( last && d.seenTs && Number( last.dataset.ts ) <= d.seenTs ) {
			last.insertAdjacentHTML( 'beforeend', `<span class="seen">${ icon( 'check' ) }${ esc( __( 'Seen', 'pikacart' ) ) }</span>` );
		}
		thread.querySelector( '[data-typing]' ).hidden = ! d.typing;
		thread.querySelector( '[data-closed]' ).hidden = d.ticket.status !== 'closed';
		const pres = thread.querySelector( '[data-presence]' );
		pres.textContent = d.online ? __( 'Support team is online', 'pikacart' ) : __( 'We usually reply within a few hours', 'pikacart' );
		pres.classList.toggle( 'is-online', !! d.online );
		const sb = thread.querySelector( '[data-status]' );
		sb.dataset.next = d.ticket.status === 'closed' ? 'open' : 'closed';
		sb.textContent = d.ticket.status === 'closed' ? __( 'Reopen', 'pikacart' ) : __( 'Mark solved', 'pikacart' );
	}

	async function poll() {
		if ( ! st.alive ) {
			return;
		}
		try {
			const d = await api( `support/tickets/${ ticketId }?after=${ st.lastId }` );
			if ( st.alive ) {
				apply( d );
			}
		} catch ( e ) {
			// Retry on the next tick.
		}
		if ( st.alive ) {
			st.timer = setTimeout( poll, document.visibilityState === 'visible' ? 3500 : 15000 );
		}
	}

	async function send() {
		const ta = thread.querySelector( 'textarea' );
		const text = ta.value.trim();
		if ( ! text || st.sending ) {
			return;
		}
		st.sending = true;
		const btn = thread.querySelector( '.composer-send' );
		// Show the message straight away; confirm when the server answers.
		const body = thread.querySelector( '[data-body]' );
		const temp = document.createElement( 'div' );
		temp.className = 'msg is-mine is-pending';
		temp.innerHTML = `<div class="msg-bubble">${ linkify( text ) }</div><span class="msg-time">${ esc( __( 'Sending…', 'pikacart' ) ) }</span>`;
		body.appendChild( temp );
		body.scrollTop = body.scrollHeight;
		ta.value = '';
		ta.style.height = 'auto';
		btn.disabled = true;
		try {
			const d = await api( `support/tickets/${ ticketId }/messages`, { method: 'POST', body: { message: text, after: st.lastId } } );
			temp.remove();
			apply( d );
			loadList();
		} catch ( err ) {
			temp.classList.add( 'is-failed' );
			temp.querySelector( '.msg-time' ).textContent = __( 'Not sent. Tap to retry.', 'pikacart' );
			temp.addEventListener( 'click', () => {
				temp.remove();
				ta.value = text;
				send();
			}, { once: true } );
			toast( err.message, 'error' );
		} finally {
			st.sending = false;
			btn.disabled = false;
		}
	}

	async function openThread() {
		thread.innerHTML = '<div class="chat-intro"><div class="skeleton" style="height:220px;width:100%"></div></div>';
		try {
			const d = await api( `support/tickets/${ ticketId }` );
			if ( ! st.alive ) {
				return;
			}
			drawThreadShell( d.ticket );
			apply( d );
			refreshLive();
			st.timer = setTimeout( poll, 3500 );
		} catch ( e ) {
			thread.innerHTML = `<div class="chat-intro"><p>${ esc( e.message ) }</p><a class="btn" href="${ esc( ctx.url( 'support' ) ) }" data-link>${ esc( __( 'Back to conversations', 'pikacart' ) ) }</a></div>`;
		}
	}

	if ( mode === 'new' ) {
		drawNew();
	} else if ( mode === 'thread' ) {
		openThread();
	} else {
		drawIntro();
	}
	loadList();
	const off = onPulse( () => loadList() );
	const onVisible = () => {
		if ( document.visibilityState === 'visible' && mode === 'thread' ) {
			clearTimeout( st.timer );
			poll();
		}
	};
	document.addEventListener( 'visibilitychange', onVisible );

	return () => {
		st.alive = false;
		clearTimeout( st.timer );
		off();
		document.removeEventListener( 'visibilitychange', onVisible );
	};
}
