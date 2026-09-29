<?php
/**
 * Cashfree Payments (PG) — an alternative to Razorpay, chosen in
 * Marketplace → Settings → Gateway mode.
 *
 * Flow: place order → create a Cashfree order (payment_session_id) → Cashfree
 * checkout → buyer returns to /checkout/cf-return/{order} where we verify the
 * order with Cashfree's API → fulfil. A signed webhook confirms the same payment
 * server-to-server, so a closed browser never loses an order. Refunds go through
 * the gateway that took the payment, whichever gateway is currently selected.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

define( 'DM_CF_API_VERSION', '2025-01-01' );

/** Cashfree selected and keys present (new checkouts). */
function dm_cf_ready() {
	return 'cashfree' === dm_opt( 'gateway' ) && dm_cf_configured();
}

/** Keys present (refunds/webhooks for older Cashfree orders keep working after a switch). */
function dm_cf_configured() {
	return '' !== (string) dm_opt( 'cf_app_id' ) && '' !== (string) dm_opt( 'cf_secret' );
}

function dm_cf_base() {
	return 'production' === dm_opt( 'cf_env' ) ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
}

function dm_cf_mode() {
	return 'production' === dm_opt( 'cf_env' ) ? 'production' : 'sandbox';
}

/**
 * Call the Cashfree PG API. Returns the decoded body or WP_Error.
 */
function dm_cf_request( $method, $path, $body = null, $idempotency = '' ) {
	$headers = array(
		'x-client-id'     => (string) dm_opt( 'cf_app_id' ),
		'x-client-secret' => (string) dm_opt( 'cf_secret' ),
		'x-api-version'   => DM_CF_API_VERSION,
		'Content-Type'    => 'application/json',
		'Accept'          => 'application/json',
	);
	if ( $idempotency ) {
		$headers['x-idempotency-key'] = $idempotency;
	}
	$args = array(
		'method'  => $method,
		'headers' => $headers,
		'timeout' => 30,
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$res = wp_remote_request( dm_cf_base() . $path, $args );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code < 200 || $code >= 300 ) {
		$msg = is_array( $data ) && ! empty( $data['message'] ) ? $data['message'] : 'Cashfree API error (' . $code . ')';
		return new WP_Error( 'cashfree', $msg, array( 'status' => $code ) );
	}
	return is_array( $data ) ? $data : array();
}

/**
 * The buyer's 10-digit Indian mobile number, or '' (Cashfree requires one).
 */
function dm_cf_phone( $uid ) {
	$p = preg_replace( '/\D/', '', (string) get_user_meta( $uid, 'dm_phone', true ) );
	if ( 12 === strlen( $p ) && 0 === strpos( $p, '91' ) ) {
		$p = substr( $p, 2 );
	} elseif ( 11 === strlen( $p ) && '0' === $p[0] ) {
		$p = substr( $p, 1 );
	}
	return preg_match( '/^[6-9][0-9]{9}$/', $p ) ? $p : '';
}

/**
 * Create a Cashfree order + payment session for one of our orders.
 * Stores the Cashfree order reference in razorpay_order_id (gateway order ref)
 * and the session in pay_session. Returns true or WP_Error.
 */
function dm_cf_create_session( $order_id ) {
	global $wpdb;
	$order = dm_get_order( $order_id );
	if ( ! $order ) {
		return new WP_Error( 'order', __( 'Order not found.', 'digimarket' ) );
	}
	$phone = dm_cf_phone( $order->buyer_id );
	if ( ! $phone ) {
		return new WP_Error( 'phone', __( 'Please add a valid 10-digit mobile number to pay.', 'digimarket' ) );
	}
	$ref  = substr( preg_replace( '/[^A-Za-z0-9_-]/', '', dm_order_number( $order ) ), 0, 30 ) . '_' . base_convert( (string) time(), 10, 36 );
	$name = trim( (string) $order->buyer_name );
	$meta = array( 'return_url' => dm_url( 'checkout', 'cf-return', $order->id ) );
	$hook = rest_url( 'dm/v1/cashfree-webhook' );
	if ( 0 === strpos( $hook, 'https://' ) ) {
		$meta['notify_url'] = $hook; // Cashfree only accepts HTTPS notify URLs.
	}
	$res = dm_cf_request(
		'POST',
		'/orders',
		array(
			'order_id'         => $ref,
			'order_amount'     => round( (float) $order->order_total, 2 ),
			'order_currency'   => dm_opt( 'currency_code', 'INR' ),
			'customer_details' => array_filter(
				array(
					'customer_id'    => 'user_' . (int) $order->buyer_id,
					'customer_phone' => $phone,
					'customer_email' => $order->buyer_email,
					'customer_name'  => mb_strlen( $name ) >= 3 ? mb_substr( $name, 0, 100 ) : null,
				)
			),
			'order_meta'       => $meta,
			'order_note'       => mb_substr( dm_order_number( $order ), 0, 200 ),
		),
		'dm-cf-' . $ref
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	if ( empty( $res['payment_session_id'] ) ) {
		return new WP_Error( 'cashfree', __( 'Cashfree did not return a payment session.', 'digimarket' ) );
	}
	$wpdb->update(
		dm_table( 'orders' ),
		array(
			'razorpay_order_id' => $ref,
			'pay_session'       => sanitize_text_field( $res['payment_session_id'] ),
		),
		array( 'id' => $order->id )
	);
	return true;
}

/**
 * Ask Cashfree for the real status of an order and fulfil it when paid.
 * Returns 'paid', 'pending' or 'failed'.
 */
function dm_cf_confirm( $order ) {
	if ( ! $order || ! $order->razorpay_order_id || ! dm_cf_configured() ) {
		return 'pending';
	}
	if ( 'paid' === $order->payment_status ) {
		return 'paid';
	}
	$cf = dm_cf_request( 'GET', '/orders/' . rawurlencode( $order->razorpay_order_id ) );
	if ( is_wp_error( $cf ) ) {
		return 'pending';
	}
	$status = strtoupper( (string) ( $cf['order_status'] ?? '' ) );
	if ( 'PAID' === $status ) {
		if ( abs( (float) ( $cf['order_amount'] ?? 0 ) - (float) $order->order_total ) > 0.009 ) {
			dm_mark_order_failed( $order->id, 'Cashfree amount mismatch' );
			return 'failed';
		}
		$payment_id = 'cf_order_' . ( $cf['cf_order_id'] ?? $order->razorpay_order_id );
		$pays       = dm_cf_request( 'GET', '/orders/' . rawurlencode( $order->razorpay_order_id ) . '/payments' );
		if ( ! is_wp_error( $pays ) ) {
			foreach ( (array) $pays as $p ) {
				if ( is_array( $p ) && 'SUCCESS' === strtoupper( (string) ( $p['payment_status'] ?? '' ) ) && ! empty( $p['cf_payment_id'] ) ) {
					$payment_id = (string) $p['cf_payment_id'];
					break;
				}
			}
		}
		dm_fulfill_order( $order->id, sanitize_text_field( $payment_id ) );
		return 'paid';
	}
	if ( in_array( $status, array( 'EXPIRED', 'TERMINATED' ), true ) ) {
		dm_mark_order_failed( $order->id, 'Cashfree order ' . strtolower( $status ) );
		return 'failed';
	}
	return 'pending';
}

/**
 * /checkout/cf-return/{order} — Cashfree sends the buyer back here.
 */
function dm_cf_handle_return( $order_id ) {
	$order = dm_get_order( $order_id );
	if ( ! $order || (int) $order->buyer_id !== get_current_user_id() || 'cashfree' !== $order->gateway ) {
		dm_redirect( dm_url( 'account', 'orders' ) );
	}
	$state = dm_cf_confirm( $order );
	if ( 'paid' === $state ) {
		dm_cart_set( array() );
		dm_cart_set_coupon( '' );
		dm_redirect( dm_url( 'order-received', $order->id ) );
	}
	if ( 'failed' === $state ) {
		dm_flash( 'error', __( 'The payment did not go through. No money was taken — you can safely retry.', 'digimarket' ) );
	} else {
		dm_flash( 'info', __( 'Payment not completed yet. If money was deducted, it will be confirmed automatically in a few minutes; otherwise please try again.', 'digimarket' ) );
	}
	dm_redirect( dm_url( 'checkout', 'pay', $order->id ) );
}

/**
 * Refund one item on Cashfree. Returns true or WP_Error.
 */
function dm_cf_refund_item( $item, $order, $reason = '' ) {
	if ( ! dm_cf_configured() ) {
		return new WP_Error( 'cashfree', __( 'Cashfree keys are missing in Settings — add them to refund this order.', 'digimarket' ) );
	}
	$note = trim( 'Refund ' . $item->product_title );
	$res  = dm_cf_request(
		'POST',
		'/orders/' . rawurlencode( $order->razorpay_order_id ) . '/refunds',
		array(
			'refund_amount' => round( (float) $item->price_at_purchase, 2 ),
			'refund_id'     => 'dmref' . (int) $item->id,
			'refund_note'   => mb_substr( mb_strlen( $note ) >= 3 ? $note : 'Refund', 0, 100 ),
		),
		'dm-cf-ref-' . (int) $item->id
	);
	return is_wp_error( $res ) ? $res : true;
}

/* -------------------------------------------------------------------------
 * Webhook: POST /wp-json/dm/v1/cashfree-webhook
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route(
		'dm/v1',
		'/cashfree-webhook',
		array(
			'methods'             => 'POST',
			'callback'            => 'dm_cf_webhook',
			'permission_callback' => '__return_true',
		)
	);
} );

/**
 * Signature = base64( HMAC-SHA256( timestamp . raw_body, client_secret ) ).
 */
function dm_cf_valid_signature( $raw, $timestamp, $signature ) {
	$secret = (string) dm_opt( 'cf_secret' );
	if ( '' === $secret || '' === $timestamp || '' === $signature ) {
		return false;
	}
	$expected = base64_encode( hash_hmac( 'sha256', $timestamp . $raw, $secret, true ) ); // phpcs:ignore
	return hash_equals( $expected, $signature );
}

function dm_cf_webhook( WP_REST_Request $request ) {
	global $wpdb;
	$raw = $request->get_body();
	if ( ! dm_cf_valid_signature( $raw, (string) $request->get_header( 'x_webhook_timestamp' ), (string) $request->get_header( 'x_webhook_signature' ) ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid signature' ), 400 );
	}
	$data = json_decode( $raw, true );
	$type = isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : '';
	$d    = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();

	// Cashfree retries deliveries: process each distinct payload once.
	$event_id = 'cf_' . md5( $raw );
	if ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'webhook_events' ) . ' WHERE event_id = %s', $event_id ) ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
	}
	$wpdb->insert( dm_table( 'webhook_events' ), array( 'event_id' => $event_id, 'event' => $type, 'created_at' => dm_now() ) );

	$ref   = (string) ( $d['order']['order_id'] ?? '' );
	$order = $ref ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . " WHERE razorpay_order_id = %s AND gateway = 'cashfree'", $ref ) ) : null;

	switch ( $type ) {
		case 'PAYMENT_SUCCESS_WEBHOOK':
			$p = $d['payment'] ?? array();
			if ( $order && 'SUCCESS' === strtoupper( (string) ( $p['payment_status'] ?? '' ) ) ) {
				$amount = (float) ( $p['payment_amount'] ?? ( $d['order']['order_amount'] ?? 0 ) );
				if ( abs( $amount - (float) $order->order_total ) <= 0.009 ) {
					dm_fulfill_order( $order->id, sanitize_text_field( (string) ( $p['cf_payment_id'] ?? $ref ) ) );
				} else {
					$wpdb->update( dm_table( 'orders' ), array( 'note' => 'Cashfree webhook amount mismatch' ), array( 'id' => $order->id ) );
				}
			}
			break;

		case 'PAYMENT_FAILED_WEBHOOK':
			if ( $order && 'pending' === $order->payment_status ) {
				dm_mark_order_failed( $order->id, sanitize_text_field( (string) ( $d['payment']['payment_message'] ?? 'Payment failed' ) ) );
				dm_notify( $order->buyer_id, sprintf( /* translators: %s order */ __( 'Payment for %s failed. You can retry from Order History.', 'digimarket' ), dm_order_number( $order->id ) ), dm_url( 'account', 'orders' ) );
			}
			break;

		case 'REFUND_STATUS_WEBHOOK':
			$r = $d['refund'] ?? array();
			if ( 'SUCCESS' === strtoupper( (string) ( $r['refund_status'] ?? '' ) ) && preg_match( '/^dmref(\d+)$/', (string) ( $r['refund_id'] ?? '' ), $m ) ) {
				$it = dm_get_order_item( (int) $m[1] );
				if ( $it && 'paid' === $it->item_status ) {
					dm_mark_item_refunded( $it, 'Cashfree refund ' . sanitize_text_field( (string) ( $r['cf_refund_id'] ?? '' ) ) );
				}
			}
			break;
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
