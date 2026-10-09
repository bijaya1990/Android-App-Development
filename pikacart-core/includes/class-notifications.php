<?php
/**
 * In-app notifications for organisations (the bell in the dashboard).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Notifications {

	/**
	 * Add a notification.
	 *
	 * @param int    $org_id Organisation.
	 * @param string $type   support|payment|plan|notice|account.
	 * @param string $title  Short title.
	 * @param string $body   One or two sentences.
	 * @param string $link   App route such as "support/12" or "subscription".
	 */
	public static function add( $org_id, $type, $title, $body = '', $link = '' ) {
		global $wpdb;
		if ( ! $org_id ) {
			return 0;
		}
		$wpdb->insert(
			pkc_table( 'notifications' ),
			array(
				'org_id'     => (int) $org_id,
				'type'       => sanitize_key( $type ),
				'title'      => wp_strip_all_tags( $title ),
				'body'       => wp_strip_all_tags( (string) $body ),
				'link'       => trim( sanitize_text_field( $link ), '/' ),
				'is_read'    => 0,
				'created_at' => pkc_now(),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function unread_count( $org_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'notifications' ) . ' WHERE org_id = %d AND is_read = 0', $org_id ) );
	}

	public static function latest_id( $org_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(id) FROM ' . pkc_table( 'notifications' ) . ' WHERE org_id = %d', $org_id ) );
	}

	public static function list_for( $org_id, $limit = 20 ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'notifications' ) . ' WHERE org_id = %d ORDER BY id DESC LIMIT %d', $org_id, $limit )
		);
		return array_map( array( __CLASS__, 'to_app' ), $rows );
	}

	/** Notifications newer than an ID (used to pop a toast for new ones). */
	public static function newer_than( $org_id, $after_id ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'notifications' ) . ' WHERE org_id = %d AND id > %d ORDER BY id ASC LIMIT 10', $org_id, $after_id )
		);
		return array_map( array( __CLASS__, 'to_app' ), $rows );
	}

	public static function to_app( $n ) {
		return array(
			'id'      => (int) $n->id,
			'type'    => $n->type,
			'title'   => $n->title,
			'body'    => (string) $n->body,
			'link'    => $n->link,
			'read'    => (bool) $n->is_read,
			'ago'     => human_time_diff( pkc_ts( $n->created_at ), time() ),
		);
	}

	/**
	 * Mark notifications read: all of them, or the given IDs (own account only).
	 */
	public static function mark_read( $org_id, $ids = array() ) {
		global $wpdb;
		$table = pkc_table( 'notifications' );
		if ( empty( $ids ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE $table SET is_read = 1 WHERE org_id = %d AND is_read = 0", $org_id ) );
			return;
		}
		foreach ( array_slice( array_map( 'absint', (array) $ids ), 0, 100 ) as $id ) {
			$wpdb->update( $table, array( 'is_read' => 1 ), array( 'id' => $id, 'org_id' => (int) $org_id ) );
		}
	}

	/** Mark support notifications for one conversation read when the chat is opened. */
	public static function mark_link_read( $org_id, $link ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare( 'UPDATE ' . pkc_table( 'notifications' ) . ' SET is_read = 1 WHERE org_id = %d AND link = %s AND is_read = 0', $org_id, $link )
		);
	}
}
