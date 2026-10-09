<?php
/**
 * Support chat: one or more conversations (tickets) per organisation,
 * live-updated by short polling (works on any shared hosting).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Support {

	const MAX_LENGTH    = 4000;
	const ONLINE_WINDOW = 120; // Seconds a side counts as "online" after its last poll.

	/* ---------- Reading ---------- */

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'tickets' ) . ' WHERE id = %d', $id ) );
	}

	/** A ticket only if it belongs to this organisation. */
	public static function get_for_org( $org_id, $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'tickets' ) . ' WHERE id = %d AND org_id = %d', $id, $org_id ) );
	}

	public static function list_for_org( $org_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'tickets' ) . ' WHERE org_id = %d ORDER BY COALESCE(last_message_at, created_at) DESC LIMIT 100', $org_id )
		);
	}

	/**
	 * Inbox for the Super Admin.
	 *
	 * @param string $status open|closed|unread|all.
	 */
	public static function list_admin( $status = 'open', $search = '', $limit = 50, $offset = 0 ) {
		global $wpdb;
		$where = array( '1=1' );
		$args  = array();
		if ( 'open' === $status || 'closed' === $status ) {
			$where[] = 't.status = %s';
			$args[]  = $status;
		} elseif ( 'unread' === $status ) {
			$where[] = 't.is_read_admin = 0';
		}
		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(t.subject LIKE %s OR o.name LIKE %s OR o.email LIKE %s OR o.mobile LIKE %s)';
			array_push( $args, $like, $like, $like, $like );
		}
		$args[] = $limit;
		$args[] = $offset;
		$sql    = 'SELECT t.*, o.name AS org_name, o.email AS org_email, o.mobile AS org_mobile, o.logo AS org_logo, o.status AS org_status
			FROM ' . pkc_table( 'tickets' ) . ' t LEFT JOIN ' . pkc_table( 'organisations' ) . ' o ON o.id = t.org_id
			WHERE ' . implode( ' AND ', $where ) . ' ORDER BY t.is_read_admin ASC, COALESCE(t.last_message_at, t.created_at) DESC LIMIT %d OFFSET %d';
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	public static function messages( $ticket_id, $after_id = 0, $limit = 300 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'ticket_replies' ) . ' WHERE ticket_id = %d AND id > %d ORDER BY id ASC LIMIT %d',
				$ticket_id,
				$after_id,
				$limit
			)
		);
	}

	public static function org_unread_count( $org_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'tickets' ) . ' WHERE org_id = %d AND is_read_user = 0', $org_id ) );
	}

	public static function admin_unread_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'tickets' ) . ' WHERE is_read_admin = 0' );
	}

	/* ---------- Writing ---------- */

	/**
	 * Clean a chat message. Returns '' when empty.
	 */
	public static function clean( $text ) {
		$text = sanitize_textarea_field( (string) $text );
		$text = trim( preg_replace( "/\n{3,}/", "\n\n", $text ) );
		return mb_substr( $text, 0, self::MAX_LENGTH );
	}

	/**
	 * Start a new conversation.
	 *
	 * @return object|WP_Error Ticket row.
	 */
	public static function create( $org, $subject, $message ) {
		global $wpdb;
		$subject = mb_substr( sanitize_text_field( (string) $subject ), 0, 150 );
		$message = self::clean( $message );
		if ( mb_strlen( $subject ) < 3 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please write a short subject (what do you need help with?).', 'pikacart' ), array( 'status' => 400, 'field' => 'subject' ) );
		}
		if ( '' === $message ) {
			return new WP_Error( 'pkc_invalid', __( 'Please type your message.', 'pikacart' ), array( 'status' => 400, 'field' => 'message' ) );
		}
		$open = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'tickets' ) . " WHERE org_id = %d AND status = 'open'", $org->id ) );
		if ( $open >= 10 ) {
			return new WP_Error( 'pkc_invalid', __( 'You already have 10 open conversations. Please continue in one of them.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$now = pkc_now();
		$wpdb->insert(
			pkc_table( 'tickets' ),
			array(
				'org_id'        => $org->id,
				'subject'       => $subject,
				'status'        => 'open',
				'is_read_admin' => 0,
				'is_read_user'  => 1,
				'user_seen_at'  => $now,
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);
		$ticket = self::get( (int) $wpdb->insert_id );
		self::post( $ticket, 'org', get_current_user_id(), $message, true );
		PKC_Activity_Log::add( 'support.ticket', $org->id, '#' . $ticket->id . ' ' . $subject );

		// Tell the owner about every new conversation.
		wp_mail(
			pkc_support_email(),
			/* translators: 1: ticket number, 2: organisation, 3: subject */
			sprintf( __( 'New support chat #%1$d from %2$s: %3$s', 'pikacart' ), $ticket->id, $org->name, $subject ),
			$message . "\n\n" . admin_url( 'admin.php?page=pikacart-support&ticket=' . (int) $ticket->id )
		);
		return self::get( $ticket->id );
	}

	/**
	 * Add a message. $author is "org", "admin" or "system".
	 *
	 * @return int|WP_Error New message ID.
	 */
	public static function post( $ticket, $author, $user_id, $message, $is_first = false ) {
		global $wpdb;
		$message = 'system' === $author ? (string) $message : self::clean( $message );
		if ( '' === $message ) {
			return new WP_Error( 'pkc_invalid', __( 'Please type your message.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$now = pkc_now();
		$wpdb->insert(
			pkc_table( 'ticket_replies' ),
			array(
				'ticket_id'   => $ticket->id,
				'author_type' => $author,
				'user_id'     => (int) $user_id,
				'message'     => $message,
				'created_at'  => $now,
			)
		);
		$msg_id = (int) $wpdb->insert_id;

		$update = array(
			'last_author'     => $author,
			'last_message'    => mb_substr( $message, 0, 160 ),
			'last_message_at' => $now,
			'updated_at'      => $now,
		);
		if ( 'org' === $author ) {
			$update['is_read_admin'] = 0;
			$update['user_seen_at']  = $now;
			$update['status']        = 'open'; // A customer message reopens a closed chat.
		} elseif ( 'admin' === $author ) {
			$update['is_read_user']  = 0;
			$update['is_read_admin'] = 1;
			$update['admin_seen_at'] = $now;
		}
		$wpdb->update( pkc_table( 'tickets' ), $update, array( 'id' => $ticket->id ) );
		self::set_typing( $ticket->id, 'org' === $author ? 'org' : 'admin', false );

		if ( 'admin' === $author ) {
			self::after_admin_reply( self::get( $ticket->id ), $message );
		} elseif ( 'org' === $author && ! $is_first ) {
			self::after_customer_message( self::get( $ticket->id ), $message );
		}
		return $msg_id;
	}

	/** Notify the customer: bell notification always, email only when they are away. */
	private static function after_admin_reply( $ticket, $message ) {
		global $wpdb;
		$org = PKC_Organisations::get( $ticket->org_id );
		if ( ! $org ) {
			return;
		}
		PKC_Notifications::add(
			$org->id,
			'support',
			/* translators: %s: conversation subject */
			sprintf( __( 'Support replied: %s', 'pikacart' ), $ticket->subject ),
			mb_substr( $message, 0, 140 ),
			'support/' . $ticket->id
		);

		$customer_online = ( time() - pkc_ts( $ticket->user_seen_at ) ) < self::ONLINE_WINDOW;
		$recently_mailed = ( time() - pkc_ts( $ticket->last_emailed_at ) ) < 10 * MINUTE_IN_SECONDS;
		if ( pkc_setting( 'email_support_reply', 1 ) && ! $customer_online && ! $recently_mailed ) {
			PKC_Emails::send_to_org(
				$org,
				'support_reply',
				array(
					'subject' => $ticket->subject,
					'message' => $message,
					'link'    => pkc_url( 'app', 'support/' . $ticket->id ),
				)
			);
			$wpdb->update( pkc_table( 'tickets' ), array( 'last_emailed_at' => pkc_now() ), array( 'id' => $ticket->id ) );
		}
	}

	/** Email the owner about a new customer message when no admin is online (max every 15 minutes per chat). */
	private static function after_customer_message( $ticket, $message ) {
		if ( self::admin_online() ) {
			return;
		}
		$key = 'pkc_support_mail_' . $ticket->id;
		if ( get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, 15 * MINUTE_IN_SECONDS );
		$org = PKC_Organisations::get( $ticket->org_id );
		wp_mail(
			pkc_support_email(),
			/* translators: 1: ticket number, 2: organisation */
			sprintf( __( 'New message in support chat #%1$d (%2$s)', 'pikacart' ), $ticket->id, $org ? $org->name : '' ),
			$message . "\n\n" . admin_url( 'admin.php?page=pikacart-support&ticket=' . (int) $ticket->id )
		);
	}

	public static function mark_seen( $ticket, $side ) {
		global $wpdb;
		$data = 'admin' === $side
			? array( 'is_read_admin' => 1, 'admin_seen_at' => pkc_now() )
			: array( 'is_read_user' => 1, 'user_seen_at' => pkc_now() );
		$wpdb->update( pkc_table( 'tickets' ), $data, array( 'id' => $ticket->id ) );
		if ( 'org' === $side ) {
			PKC_Notifications::mark_link_read( $ticket->org_id, 'support/' . $ticket->id );
		}
	}

	public static function set_status( $ticket, $status, $by ) {
		global $wpdb;
		$status = 'closed' === $status ? 'closed' : 'open';
		if ( $status === $ticket->status ) {
			return;
		}
		$wpdb->update( pkc_table( 'tickets' ), array( 'status' => $status, 'updated_at' => pkc_now() ), array( 'id' => $ticket->id ) );
		$text = 'closed' === $status
			? ( 'admin' === $by ? __( 'Support marked this conversation as solved.', 'pikacart' ) : __( 'You closed this conversation.', 'pikacart' ) )
			: __( 'Conversation reopened.', 'pikacart' );
		$wpdb->insert(
			pkc_table( 'ticket_replies' ),
			array(
				'ticket_id'   => $ticket->id,
				'author_type' => 'system',
				'user_id'     => get_current_user_id(),
				'message'     => $text,
				'created_at'  => pkc_now(),
			)
		);
		if ( 'admin' === $by && 'closed' === $status ) {
			PKC_Notifications::add(
				$ticket->org_id,
				'support',
				/* translators: %s: conversation subject */
				sprintf( __( 'Conversation solved: %s', 'pikacart' ), $ticket->subject ),
				__( 'Need more help? Just send a new message in the same chat to reopen it.', 'pikacart' ),
				'support/' . $ticket->id
			);
		}
	}

	/* ---------- Live presence (transients, no extra tables) ---------- */

	public static function set_typing( $ticket_id, $side, $on = true ) {
		$key = 'pkc_typing_' . (int) $ticket_id . '_' . $side;
		if ( $on ) {
			set_transient( $key, 1, 8 );
		} else {
			delete_transient( $key );
		}
	}

	public static function is_typing( $ticket_id, $side ) {
		return (bool) get_transient( 'pkc_typing_' . (int) $ticket_id . '_' . $side );
	}

	/** Called on every admin inbox poll. */
	public static function touch_admin_online() {
		set_transient( 'pkc_admin_online', time(), self::ONLINE_WINDOW );
	}

	public static function admin_online() {
		$t = (int) get_transient( 'pkc_admin_online' );
		return $t && ( time() - $t ) < self::ONLINE_WINDOW;
	}

	/* ---------- Output ---------- */

	/**
	 * One message for the browser. $side is who is looking: "org" or "admin".
	 */
	public static function message_to_app( $m, $side ) {
		$ts = pkc_ts( $m->created_at );
		if ( 'admin' === $m->author_type ) {
			$name = __( 'Pikacart Support', 'pikacart' );
			if ( 'admin' === $side ) {
				$u    = get_userdata( (int) $m->user_id );
				$name = $u ? $u->display_name : $name;
			}
		} elseif ( 'org' === $m->author_type ) {
			$u    = get_userdata( (int) $m->user_id );
			$name = $u ? $u->display_name : __( 'Customer', 'pikacart' );
		} else {
			$name = '';
		}
		return array(
			'id'     => (int) $m->id,
			'author' => $m->author_type,
			'mine'   => $m->author_type === $side,
			'name'   => $name,
			'text'   => (string) $m->message,
			'ts'     => $ts,
			'time'   => wp_date( get_option( 'time_format', 'g:i a' ), $ts ),
			'day'    => wp_date( 'Y-m-d', $ts ),
			'dayText' => wp_date( 'j M Y', $ts ),
		);
	}

	public static function ticket_to_app( $t, $side ) {
		$unread = 'admin' === $side ? ! $t->is_read_admin : ! $t->is_read_user;
		$out    = array(
			'id'       => (int) $t->id,
			'subject'  => $t->subject,
			'status'   => $t->status,
			'unread'   => (bool) $unread,
			'last'     => (string) $t->last_message,
			'lastBy'   => $t->last_author,
			'ago'      => human_time_diff( pkc_ts( $t->last_message_at ? $t->last_message_at : $t->created_at ), time() ),
			'ts'       => pkc_ts( $t->last_message_at ? $t->last_message_at : $t->created_at ),
		);
		if ( 'admin' === $side && isset( $t->org_name ) ) {
			$out['org'] = array(
				'id'     => (int) $t->org_id,
				'name'   => (string) $t->org_name,
				'email'  => (string) $t->org_email,
				'mobile' => (string) $t->org_mobile,
				'logo'   => (string) $t->org_logo,
				'status' => PKC_Access::label( (string) $t->org_status ),
				'url'    => admin_url( 'admin.php?page=pikacart-accounts&org=' . (int) $t->org_id ),
			);
		}
		return $out;
	}

	/**
	 * Thread payload for polling: new messages, status, typing, seen marker.
	 */
	public static function thread_payload( $ticket, $side, $after_id ) {
		$other = 'admin' === $side ? 'org' : 'admin';
		$msgs  = array_map(
			function ( $m ) use ( $side ) {
				return self::message_to_app( $m, $side );
			},
			self::messages( $ticket->id, $after_id )
		);
		$seen_by_other = 'admin' === $side ? pkc_ts( $ticket->user_seen_at ) : pkc_ts( $ticket->admin_seen_at );
		return array(
			'ticket'   => self::ticket_to_app( $ticket, $side ),
			'messages' => $msgs,
			'typing'   => self::is_typing( $ticket->id, $other ),
			'seenTs'   => $seen_by_other,
			'online'   => 'org' === $side ? self::admin_online() : ( time() - pkc_ts( $ticket->user_seen_at ) ) < self::ONLINE_WINDOW,
		);
	}
}
