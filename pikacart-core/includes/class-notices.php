<?php
/**
 * Announcements from Pikacart: to every account or to one account.
 * Shown on the dashboard while active and sent to the notification bell.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Notices {

	public static function all( $limit = 100 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'notices' ) . ' ORDER BY id DESC LIMIT %d', $limit ) );
	}

	private static function to_utc( $date, $end = false ) {
		$date = sanitize_text_field( (string) $date );
		if ( '' === $date ) {
			return null;
		}
		$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $date . ( $end ? ' 23:59:59' : ' 00:00:00' ), wp_timezone() );
		if ( ! $dt ) {
			return null;
		}
		$dt->setTimezone( new DateTimeZone( 'UTC' ) );
		return $dt->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Post a notice.
	 *
	 * @param array $in title, body, org_id (0 = everyone), starts, ends, bell.
	 * @return int|WP_Error
	 */
	public static function create( $in ) {
		global $wpdb;
		$title = mb_substr( sanitize_text_field( (string) ( $in['title'] ?? '' ) ), 0, 200 );
		$body  = mb_substr( sanitize_textarea_field( (string) ( $in['body'] ?? '' ) ), 0, 3000 );
		if ( '' === $title ) {
			return new WP_Error( 'pkc_invalid', __( 'Please write a title.', 'pikacart' ) );
		}
		$org_id = absint( $in['org_id'] ?? 0 );
		if ( $org_id && ! PKC_Organisations::get( $org_id ) ) {
			return new WP_Error( 'pkc_invalid', __( 'That account was not found.', 'pikacart' ) );
		}
		$wpdb->insert(
			pkc_table( 'notices' ),
			array(
				'org_id'     => $org_id,
				'title'      => $title,
				'body'       => $body,
				'starts_at'  => self::to_utc( $in['starts'] ?? '' ),
				'ends_at'    => self::to_utc( $in['ends'] ?? '', true ),
				'created_at' => pkc_now(),
			)
		);
		$id = (int) $wpdb->insert_id;

		if ( ! empty( $in['bell'] ) ) {
			$short = wp_html_excerpt( $body, 180, '…' );
			if ( $org_id ) {
				PKC_Notifications::add( $org_id, 'notice', $title, $short );
			} else {
				// One row per account in a single query.
				$wpdb->query(
					$wpdb->prepare(
						'INSERT INTO ' . pkc_table( 'notifications' ) . " (org_id, type, title, body, link, is_read, created_at) SELECT id, 'notice', %s, %s, '', 0, %s FROM " . pkc_table( 'organisations' ) . " WHERE status <> 'suspended'",
						$title,
						$short,
						pkc_now()
					)
				);
			}
		}
		PKC_Activity_Log::add( 'notice.posted', $org_id, $title );
		return $id;
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( pkc_table( 'notices' ), array( 'id' => absint( $id ) ) );
		PKC_Activity_Log::add( 'notice.deleted', 0, '#' . absint( $id ) );
	}
}
