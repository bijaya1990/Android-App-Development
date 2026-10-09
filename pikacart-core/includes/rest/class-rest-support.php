<?php
/**
 * Support chat and notification endpoints.
 * Customers: pkc/v1/support/*, pkc/v1/notifications, pkc/v1/pulse
 * Super Admin: pkc/v1/admin/support/*
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Support {

	public static function routes() {
		$org = array(
			array( 'pulse', 'GET', 'pulse' ),
			array( 'notifications', 'GET', 'notifications' ),
			array( 'notifications/read', 'POST', 'notifications_read' ),
			array( 'support/tickets', 'GET', 'tickets' ),
			array( 'support/tickets', 'POST', 'create' ),
			array( 'support/tickets/(?P<id>\d+)', 'GET', 'thread' ),
			array( 'support/tickets/(?P<id>\d+)/messages', 'POST', 'send' ),
			array( 'support/tickets/(?P<id>\d+)/typing', 'POST', 'typing' ),
			array( 'support/tickets/(?P<id>\d+)/status', 'POST', 'status' ),
		);
		foreach ( $org as $r ) {
			register_rest_route(
				PKC_REST::NS,
				'/' . $r[0],
				array(
					'methods'             => $r[1],
					'callback'            => array( __CLASS__, $r[2] ),
					'permission_callback' => array( 'PKC_REST', 'can_org' ),
				)
			);
		}

		$admin = array(
			array( 'admin/support/tickets', 'GET', 'admin_tickets' ),
			array( 'admin/support/tickets/(?P<id>\d+)', 'GET', 'admin_thread' ),
			array( 'admin/support/tickets/(?P<id>\d+)/messages', 'POST', 'admin_send' ),
			array( 'admin/support/tickets/(?P<id>\d+)/typing', 'POST', 'admin_typing' ),
			array( 'admin/support/tickets/(?P<id>\d+)/status', 'POST', 'admin_status' ),
		);
		foreach ( $admin as $r ) {
			register_rest_route(
				PKC_REST::NS,
				'/' . $r[0],
				array(
					'methods'             => $r[1],
					'callback'            => array( __CLASS__, $r[2] ),
					'permission_callback' => array( __CLASS__, 'can_admin' ),
				)
			);
		}
	}

	public static function can_admin() {
		return current_user_can( PKC_Roles::ADMIN_CAP )
			? true
			: new WP_Error( 'pkc_auth', __( 'You are not allowed to do this.', 'pikacart' ), array( 'status' => 403 ) );
	}

	private static function not_found() {
		return new WP_Error( 'pkc_not_found', __( 'Conversation not found.', 'pikacart' ), array( 'status' => 404 ) );
	}

	/* ---------- Customer ---------- */

	/**
	 * Light check the app calls every few seconds: unread counts and new notifications.
	 */
	public static function pulse( WP_REST_Request $r ) {
		$org   = PKC_Organisations::current();
		$after = absint( $r->get_param( 'after' ) );
		return PKC_REST::ok(
			array(
				'notifications' => PKC_Notifications::unread_count( $org->id ),
				'support'       => PKC_Support::org_unread_count( $org->id ),
				'latest'        => PKC_Notifications::latest_id( $org->id ),
				'new'           => $after ? PKC_Notifications::newer_than( $org->id, $after ) : array(),
				'online'        => PKC_Support::admin_online(),
			)
		);
	}

	public static function notifications() {
		$org = PKC_Organisations::current();
		return PKC_REST::ok(
			array(
				'items'  => PKC_Notifications::list_for( $org->id, 30 ),
				'unread' => PKC_Notifications::unread_count( $org->id ),
			)
		);
	}

	public static function notifications_read( WP_REST_Request $r ) {
		$org = PKC_Organisations::current();
		$ids = $r->get_param( 'ids' );
		PKC_Notifications::mark_read( $org->id, is_array( $ids ) ? $ids : array() );
		return PKC_REST::ok( array( 'unread' => PKC_Notifications::unread_count( $org->id ) ) );
	}

	public static function tickets() {
		$org  = PKC_Organisations::current();
		$list = array_map(
			function ( $t ) {
				return PKC_Support::ticket_to_app( $t, 'org' );
			},
			PKC_Support::list_for_org( $org->id )
		);
		return PKC_REST::ok(
			array(
				'tickets' => $list,
				'online'  => PKC_Support::admin_online(),
			)
		);
	}

	public static function create( WP_REST_Request $r ) {
		$org = PKC_Organisations::current();
		if ( ! PKC_Rate_Limit::hit( 'support_new', (string) $org->id, 5, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$ticket = PKC_Support::create( $org, $r->get_param( 'subject' ), $r->get_param( 'message' ) );
		if ( is_wp_error( $ticket ) ) {
			return $ticket;
		}
		return PKC_REST::ok( array( 'ticket' => PKC_Support::ticket_to_app( $ticket, 'org' ) ) );
	}

	public static function thread( WP_REST_Request $r ) {
		$org    = PKC_Organisations::current();
		$ticket = PKC_Support::get_for_org( $org->id, absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		$payload = PKC_Support::thread_payload( $ticket, 'org', absint( $r->get_param( 'after' ) ) );
		// Mark as seen (also shows "online" to support). Throttled to limit writes.
		if ( ! $ticket->is_read_user || ( time() - pkc_ts( $ticket->user_seen_at ) ) > 20 ) {
			PKC_Support::mark_seen( $ticket, 'org' );
		}
		return PKC_REST::ok( $payload );
	}

	public static function send( WP_REST_Request $r ) {
		$org    = PKC_Organisations::current();
		$ticket = PKC_Support::get_for_org( $org->id, absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		if ( ! PKC_Rate_Limit::hit( 'support_msg', (string) $org->id, 30, 5 * MINUTE_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$id = PKC_Support::post( $ticket, 'org', get_current_user_id(), $r->get_param( 'message' ) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return PKC_REST::ok( PKC_Support::thread_payload( PKC_Support::get( $ticket->id ), 'org', absint( $r->get_param( 'after' ) ) ) );
	}

	public static function typing( WP_REST_Request $r ) {
		$org    = PKC_Organisations::current();
		$ticket = PKC_Support::get_for_org( $org->id, absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		PKC_Support::set_typing( $ticket->id, 'org', true );
		return PKC_REST::ok();
	}

	public static function status( WP_REST_Request $r ) {
		$org    = PKC_Organisations::current();
		$ticket = PKC_Support::get_for_org( $org->id, absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		PKC_Support::set_status( $ticket, (string) $r->get_param( 'status' ), 'org' );
		return PKC_REST::ok( PKC_Support::thread_payload( PKC_Support::get( $ticket->id ), 'org', absint( $r->get_param( 'after' ) ) ) );
	}

	/* ---------- Super Admin ---------- */

	public static function admin_tickets( WP_REST_Request $r ) {
		PKC_Support::touch_admin_online();
		$status = sanitize_key( (string) $r->get_param( 'status' ) );
		$search = sanitize_text_field( (string) $r->get_param( 'search' ) );
		$list   = array_map(
			function ( $t ) {
				return PKC_Support::ticket_to_app( $t, 'admin' );
			},
			PKC_Support::list_admin( $status ? $status : 'open', $search, 60 )
		);
		return PKC_REST::ok(
			array(
				'tickets' => $list,
				'unread'  => PKC_Support::admin_unread_count(),
			)
		);
	}

	private static function admin_ticket( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT t.*, o.name AS org_name, o.email AS org_email, o.mobile AS org_mobile, o.logo AS org_logo, o.status AS org_status FROM ' . pkc_table( 'tickets' ) . ' t LEFT JOIN ' . pkc_table( 'organisations' ) . ' o ON o.id = t.org_id WHERE t.id = %d',
				$id
			)
		);
	}

	public static function admin_thread( WP_REST_Request $r ) {
		PKC_Support::touch_admin_online();
		$ticket = self::admin_ticket( absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		$payload = PKC_Support::thread_payload( $ticket, 'admin', absint( $r->get_param( 'after' ) ) );
		if ( ! $ticket->is_read_admin || ( time() - pkc_ts( $ticket->admin_seen_at ) ) > 20 ) {
			PKC_Support::mark_seen( $ticket, 'admin' );
		}
		$payload['unread'] = PKC_Support::admin_unread_count();
		return PKC_REST::ok( $payload );
	}

	public static function admin_send( WP_REST_Request $r ) {
		$ticket = self::admin_ticket( absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		$id = PKC_Support::post( $ticket, 'admin', get_current_user_id(), $r->get_param( 'message' ) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return PKC_REST::ok( PKC_Support::thread_payload( self::admin_ticket( $ticket->id ), 'admin', absint( $r->get_param( 'after' ) ) ) );
	}

	public static function admin_typing( WP_REST_Request $r ) {
		PKC_Support::set_typing( absint( $r['id'] ), 'admin', true );
		return PKC_REST::ok();
	}

	public static function admin_status( WP_REST_Request $r ) {
		$ticket = self::admin_ticket( absint( $r['id'] ) );
		if ( ! $ticket ) {
			return self::not_found();
		}
		PKC_Support::set_status( $ticket, (string) $r->get_param( 'status' ), 'admin' );
		return PKC_REST::ok( PKC_Support::thread_payload( self::admin_ticket( $ticket->id ), 'admin', absint( $r->get_param( 'after' ) ) ) );
	}
}
