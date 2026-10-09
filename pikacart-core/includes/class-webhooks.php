<?php
/**
 * Razorpay webhooks. Every event is signature-checked and processed once.
 *
 * Handled: subscription.activated, subscription.charged, subscription.pending,
 * subscription.halted, subscription.cancelled, subscription.completed,
 * payment.failed, payment.captured and order.paid (one-time payments).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Webhooks {

	public static function handle( WP_REST_Request $request ) {
		$raw       = $request->get_body();
		$signature = (string) $request->get_header( 'x_razorpay_signature' );

		if ( ! PKC_Razorpay::verify_webhook( $raw, $signature ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid signature' ), 400 );
		}

		$data  = json_decode( $raw, true );
		$event = is_array( $data ) ? (string) ( $data['event'] ?? '' ) : '';
		if ( '' === $event ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}

		// Idempotency: the same event ID is processed only once.
		$event_id = (string) $request->get_header( 'x_razorpay_event_id' );
		if ( '' === $event_id ) {
			$event_id = 'h_' . hash( 'sha256', $raw );
		}
		global $wpdb;
		$event_id = substr( sanitize_text_field( $event_id ), 0, 80 );
		if ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'webhook_events' ) . ' WHERE event_id = %s', $event_id ) ) ) {
			return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
		}
		// The unique key still protects against two deliveries at the same moment.
		$quiet = $wpdb->suppress_errors( true );
		$fresh = $wpdb->insert(
			pkc_table( 'webhook_events' ),
			array(
				'event_id'   => $event_id,
				'event'      => substr( sanitize_text_field( $event ), 0, 64 ),
				'created_at' => pkc_now(),
			)
		);
		$wpdb->suppress_errors( $quiet );
		if ( ! $fresh ) {
			return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
		}

		$payload = $data['payload'] ?? array();
		$sub     = $payload['subscription']['entity'] ?? null;
		$payment = $payload['payment']['entity'] ?? null;

		switch ( $event ) {
			case 'subscription.activated':
			case 'subscription.charged':
			case 'subscription.pending':
			case 'subscription.halted':
			case 'subscription.cancelled':
			case 'subscription.completed':
				self::subscription_event( $event, $sub, $payment );
				break;
			case 'payment.failed':
				self::payment_failed( $payment );
				break;
			case 'payment.captured':
			case 'order.paid':
				self::payment_captured( $payment );
				break;
			case 'refund.processed':
			case 'payment.refunded':
				self::refunded( $payment, $payload['refund']['entity'] ?? null );
				break;
		}

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private static function subscription_event( $event, $sub, $payment ) {
		if ( ! is_array( $sub ) || empty( $sub['id'] ) ) {
			return;
		}
		$local = PKC_Billing::subscription_by_rzp( $sub['id'] );
		if ( ! $local ) {
			return;
		}
		$status_map = array(
			'subscription.activated' => 'active',
			'subscription.charged'   => 'active',
			'subscription.pending'   => 'pending',
			'subscription.halted'    => 'halted',
			'subscription.cancelled' => 'cancelled',
			'subscription.completed' => 'completed',
		);
		PKC_Billing::set_subscription_status( $local, $status_map[ $event ], (int) ( $sub['current_end'] ?? 0 ) );

		if ( 'subscription.charged' === $event && is_array( $payment ) ) {
			PKC_Billing::record_payment( (int) $local->org_id, PKC_Billing::plan( $local->plan_id ), $payment, 'subscription', $sub['id'], '', $local->mode );
		}
		if ( in_array( $event, array( 'subscription.pending', 'subscription.halted' ), true ) ) {
			PKC_Emails::send_to_org(
				PKC_Organisations::get( (int) $local->org_id ),
				'payment_failed',
				array(
					'reason' => __( 'Your bank could not complete the autopay renewal.', 'pikacart' ),
					'link'   => pkc_url( 'app', 'subscription' ),
				)
			);
		}
		PKC_Access::refresh( PKC_Organisations::get( (int) $local->org_id ) );
	}

	/**
	 * Find which organisation a payment belongs to: notes first, then our records.
	 */
	private static function org_for_payment( $payment ) {
		global $wpdb;
		$org_id = (int) ( $payment['notes']['org_id'] ?? 0 );
		if ( $org_id && PKC_Organisations::get( $org_id ) ) {
			return $org_id;
		}
		if ( ! empty( $payment['order_id'] ) ) {
			$org_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT org_id FROM ' . pkc_table( 'payments' ) . ' WHERE rzp_order_id = %s LIMIT 1', $payment['order_id'] ) );
		}
		return $org_id;
	}

	private static function payment_failed( $payment ) {
		if ( ! is_array( $payment ) ) {
			return;
		}
		$org_id = self::org_for_payment( $payment );
		if ( $org_id ) {
			$kind = ! empty( $payment['order_id'] ) ? 'one_time' : 'subscription';
			PKC_Billing::record_failed( $org_id, $payment, $kind );
		}
	}

	/**
	 * One-time payments confirmed by webhook (e.g. the customer closed the browser).
	 * Subscription payments are handled by subscription.charged instead.
	 */
	private static function payment_captured( $payment ) {
		global $wpdb;
		if ( ! is_array( $payment ) || empty( $payment['order_id'] ) || ! empty( $payment['invoice_id'] ) ) {
			return;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'payments' ) . " WHERE rzp_order_id = %s AND kind = 'one_time' ORDER BY id ASC LIMIT 1", $payment['order_id'] ) );
		if ( ! $row ) {
			return;
		}
		PKC_Billing::record_payment( (int) $row->org_id, PKC_Billing::plan( $row->plan_id ), $payment, 'one_time', '', $payment['order_id'], $row->mode );
	}

	/**
	 * A refund made in the Razorpay dashboard: mark the payment refunded so the
	 * monthly statement shows it. The plan end date is not changed automatically.
	 */
	private static function refunded( $payment, $refund ) {
		global $wpdb;
		$payment_id = is_array( $refund ) && ! empty( $refund['payment_id'] ) ? $refund['payment_id'] : ( is_array( $payment ) ? ( $payment['id'] ?? '' ) : '' );
		if ( ! $payment_id ) {
			return;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'payments' ) . ' WHERE rzp_payment_id = %s', sanitize_text_field( $payment_id ) ) );
		if ( ! $row || 'refunded' === $row->status ) {
			return;
		}
		$wpdb->update( pkc_table( 'payments' ), array( 'status' => 'refunded' ), array( 'id' => $row->id ) );
		PKC_Activity_Log::add( 'payment.refunded', (int) $row->org_id, pkc_money( (int) $row->amount_paise ) . ' · ' . $payment_id );
	}
}
