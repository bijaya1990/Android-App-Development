<?php
/**
 * Test-only: fake Cashfree sandbox API for the e2e suite. Copy into
 * wp-content/mu-plugins of the test site. Never install on a live site.
 */
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	if ( 0 !== strpos( $url, 'https://sandbox.cashfree.com/pg' ) ) {
		return $pre;
	}
	$path   = substr( $url, strlen( 'https://sandbox.cashfree.com/pg' ) );
	$method = strtoupper( $args['method'] ?? 'GET' );
	$body   = isset( $args['body'] ) ? json_decode( $args['body'], true ) : null;
	$log    = (array) get_option( 'dm_cf_mock_log', array() );
	$log[]  = array( 'method' => $method, 'path' => $path, 'body' => $body, 'headers' => $args['headers'] ?? array() );
	update_option( 'dm_cf_mock_log', $log );
	$reply = function ( $code, $data ) {
		return array( 'headers' => array(), 'body' => wp_json_encode( $data ), 'response' => array( 'code' => $code, 'message' => 'OK' ), 'cookies' => array(), 'filename' => null );
	};
	if ( 'POST' === $method && '/orders' === $path ) {
		update_option( 'dm_cf_mock_amount_' . $body['order_id'], $body['order_amount'] );
		return $reply( 200, array( 'cf_order_id' => 9001, 'order_id' => $body['order_id'], 'order_status' => 'ACTIVE', 'payment_session_id' => 'session_test_' . $body['order_id'] ) );
	}
	if ( 'GET' === $method && preg_match( '#^/orders/([^/]+)/payments$#', $path ) ) {
		return $reply( 200, array( array( 'cf_payment_id' => 555001, 'payment_status' => 'SUCCESS' ) ) );
	}
	if ( 'GET' === $method && preg_match( '#^/orders/([^/]+)$#', $path, $m ) ) {
		$amount = get_option( 'dm_cf_mock_amount_override', '' );
		return $reply( 200, array( 'cf_order_id' => 9001, 'order_id' => $m[1], 'order_status' => get_option( 'dm_cf_mock_status', 'ACTIVE' ), 'order_amount' => '' !== $amount ? (float) $amount : (float) get_option( 'dm_cf_mock_amount_' . $m[1], 0 ) ) );
	}
	if ( 'POST' === $method && preg_match( '#^/orders/([^/]+)/refunds$#', $path ) ) {
		return $reply( 200, array( 'cf_refund_id' => 'cfr_1', 'refund_id' => $body['refund_id'], 'refund_status' => 'PENDING', 'refund_amount' => $body['refund_amount'] ) );
	}
	return $reply( 404, array( 'message' => 'mock: unknown ' . $method . ' ' . $path ) );
}, 10, 3 );
