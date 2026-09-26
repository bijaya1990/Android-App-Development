<?php
/**
 * Payments: order creation, Razorpay Checkout, signature verification,
 * automatic commission split (Razorpay Route transfers), webhooks, refunds.
 *
 * Gateway modes (Marketplace → Settings):
 *  - demo:     no real money; "Pay" instantly marks the order paid (for testing).
 *  - razorpay: real Razorpay (use rzp_test_ keys for sandbox, rzp_live_ for production).
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Razorpay API client
 * ---------------------------------------------------------------------- */

function dm_rzp_ready() {
	return 'razorpay' === dm_opt( 'gateway' ) && dm_opt( 'rzp_key_id' ) && dm_opt( 'rzp_key_secret' );
}

/**
 * @return array|WP_Error Decoded body.
 */
function dm_rzp_request( $method, $path, $body = null, $idempotency = '' ) {
	$args = array(
		'method'  => $method,
		'timeout' => 30,
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( dm_opt( 'rzp_key_id' ) . ':' . dm_opt( 'rzp_key_secret' ) ),
			'Content-Type'  => 'application/json',
		),
	);
	if ( $idempotency ) {
		$args['headers']['X-Razorpay-Idempotency'] = $idempotency;
	}
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$res = wp_remote_request( 'https://api.razorpay.com' . $path, $args );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code >= 400 ) {
		$msg = isset( $data['error']['description'] ) ? $data['error']['description'] : 'HTTP ' . $code;
		return new WP_Error( 'rzp_' . $code, 'Razorpay: ' . $msg, $data );
	}
	return is_array( $data ) ? $data : array();
}

/* -------------------------------------------------------------------------
 * Linked accounts (Route) for sellers
 * ---------------------------------------------------------------------- */

/**
 * Create (or continue creating) the seller's Razorpay Route linked account.
 */
function dm_rzp_sync_linked_account( $uid ) {
	if ( ! dm_rzp_ready() ) {
		return new WP_Error( 'rzp_off', __( 'Razorpay is not configured.', 'digimarket' ) );
	}
	$user    = get_userdata( $uid );
	$acc_id  = get_user_meta( $uid, 'dm_rzp_account_id', true );
	$addr    = (array) get_user_meta( $uid, 'dm_address', true );
	$legal   = get_user_meta( $uid, 'dm_legal_name', true );
	$bank    = dm_decrypt( get_user_meta( $uid, 'dm_bank_account', true ) );
	$ifsc    = get_user_meta( $uid, 'dm_ifsc', true );
	$phone   = preg_replace( '/\D/', '', (string) get_user_meta( $uid, 'dm_phone', true ) );
	$phone   = strlen( $phone ) > 10 ? substr( $phone, -10 ) : $phone;

	if ( ! $acc_id ) {
		$res = dm_rzp_request(
			'POST',
			'/v2/accounts',
			array(
				'email'               => $user->user_email,
				'phone'               => $phone,
				'type'                => 'route',
				'reference_id'        => 'seller_' . $uid,
				'legal_business_name' => $legal ? $legal : dm_shop_name( $uid ),
				'business_type'       => get_user_meta( $uid, 'dm_business_type', true ) ? get_user_meta( $uid, 'dm_business_type', true ) : 'individual',
				'contact_name'        => $legal ? $legal : $user->display_name,
				'profile'             => array(
					'category'    => dm_opt( 'rzp_profile_category', 'ecommerce' ),
					'subcategory' => dm_opt( 'rzp_profile_subcategory', 'digital_goods' ),
					'addresses'   => array(
						'registered' => array(
							'street1'     => $addr['street'] ?? '',
							'street2'     => $addr['street'] ?? '',
							'city'        => $addr['city'] ?? '',
							'state'       => strtoupper( $addr['state'] ?? '' ),
							'postal_code' => $addr['postal_code'] ?? '',
							'country'     => 'IN',
						),
					),
				),
				'legal_info'          => array( 'pan' => dm_decrypt( get_user_meta( $uid, 'dm_pan', true ) ) ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$acc_id = $res['id'];
		update_user_meta( $uid, 'dm_rzp_account_id', $acc_id );
		dm_rzp_request( 'POST', '/v2/accounts/' . $acc_id . '/stakeholders', array( 'name' => $legal ? $legal : $user->display_name, 'email' => $user->user_email ) );
	}

	$product_id = get_user_meta( $uid, 'dm_rzp_product_id', true );
	if ( ! $product_id ) {
		$res = dm_rzp_request( 'POST', '/v2/accounts/' . $acc_id . '/products', array( 'product_name' => 'route', 'tnc_accepted' => true ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$product_id = $res['id'];
		update_user_meta( $uid, 'dm_rzp_product_id', $product_id );
	}

	if ( $bank && $ifsc ) {
		$res = dm_rzp_request(
			'PATCH',
			'/v2/accounts/' . $acc_id . '/products/' . $product_id,
			array(
				'settlements'  => array(
					'account_number'   => $bank,
					'ifsc_code'        => $ifsc,
					'beneficiary_name' => $legal ? $legal : $user->display_name,
				),
				'tnc_accepted' => true,
			)
		);
	} else {
		$res = dm_rzp_request( 'GET', '/v2/accounts/' . $acc_id . '/products/' . $product_id );
	}
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$status = isset( $res['activation_status'] ) ? $res['activation_status'] : 'under_review';
	update_user_meta( $uid, 'dm_rzp_activation', $status );
	update_user_meta( $uid, 'dm_kyc_status', 'activated' === $status ? 'verified' : ( 'rejected' === $status ? 'rejected' : 'pending' ) );
	delete_user_meta( $uid, 'dm_kyc_error' );
	return $status;
}

/* -------------------------------------------------------------------------
 * Checkout: create order
 * ---------------------------------------------------------------------- */

function dm_do_place_order() {
	dm_require_login();
	$uid  = get_current_user_id();
	$user = wp_get_current_user();
	if ( ! dm_email_verified( $uid ) ) {
		dm_flash( 'error', __( 'Please verify your email address before your first purchase. Check your inbox or resend the link from My Account.', 'digimarket' ) );
		dm_redirect( dm_url( 'checkout' ) );
	}
	if ( empty( $_POST['agree'] ) ) {
		dm_flash( 'error', __( 'Please accept the Terms of Service and Refund Policy.', 'digimarket' ) );
		dm_redirect( dm_url( 'checkout' ) );
	}
	$totals = dm_cart_totals();
	// Drop items already owned or own products.
	foreach ( $totals['lines'] as $pid => $l ) {
		if ( dm_user_purchase( $uid, $pid ) || $l['seller'] === $uid ) {
			dm_cart_remove( $pid );
			dm_flash( 'info', sprintf( /* translators: %s */ __( '“%s” was removed — you already own it or it is your product.', 'digimarket' ), $l['title'] ) );
			dm_redirect( dm_url( 'cart' ) );
		}
	}
	if ( ! $totals['lines'] ) {
		dm_flash( 'error', __( 'Your cart is empty.', 'digimarket' ) );
		dm_redirect( dm_url( 'cart' ) );
	}
	if ( $totals['invalid'] ) {
		dm_flash( 'error', __( 'Some items are no longer available and were removed.', 'digimarket' ) );
		dm_cart_set( array_keys( $totals['lines'] ) );
		dm_redirect( dm_url( 'cart' ) );
	}
	if ( $totals['total'] > 0 && 'razorpay' === dm_opt( 'gateway' ) && ! dm_rzp_ready() ) {
		dm_flash( 'error', __( 'Payments are not configured yet. Please try again later.', 'digimarket' ) );
		dm_redirect( dm_url( 'checkout' ) );
	}

	$order_id = dm_create_order( $uid, $user, $totals );
	if ( ! $order_id ) {
		dm_flash( 'error', __( 'Could not create your order. Please try again.', 'digimarket' ) );
		dm_redirect( dm_url( 'checkout' ) );
	}

	if ( $totals['total'] <= 0 ) {
		dm_fulfill_order( $order_id, 'free' );
		dm_cart_set( array() );
		dm_cart_set_coupon( '' );
		dm_redirect( dm_url( 'order-received', $order_id ) );
	}

	if ( 'razorpay' === dm_opt( 'gateway' ) ) {
		$rzp = dm_rzp_request(
			'POST',
			'/v1/orders',
			array(
				'amount'          => dm_to_paise( $totals['total'] ),
				'currency'        => dm_opt( 'currency_code', 'INR' ),
				'receipt'         => dm_order_number( $order_id ),
				'payment_capture' => 1,
				'notes'           => array( 'dm_order_id' => (string) $order_id, 'buyer' => $user->user_email ),
			),
			'dm-order-' . $order_id
		);
		if ( is_wp_error( $rzp ) ) {
			global $wpdb;
			$wpdb->update( dm_table( 'orders' ), array( 'payment_status' => 'failed', 'note' => $rzp->get_error_message() ), array( 'id' => $order_id ) );
			dm_flash( 'error', __( 'The payment gateway is unavailable right now. Please try again in a moment.', 'digimarket' ) );
			dm_redirect( dm_url( 'checkout' ) );
		}
		global $wpdb;
		$wpdb->update( dm_table( 'orders' ), array( 'razorpay_order_id' => $rzp['id'] ), array( 'id' => $order_id ) );
	}
	dm_redirect( dm_url( 'checkout', 'pay', $order_id ) );
}

function dm_create_order( $uid, $user, $totals ) {
	global $wpdb;
	$ok = $wpdb->insert(
		dm_table( 'orders' ),
		array(
			'buyer_id'       => $uid,
			'buyer_email'    => $user->user_email,
			'buyer_name'     => $user->display_name,
			'subtotal'       => $totals['subtotal'],
			'discount'       => $totals['discount'],
			'order_total'    => $totals['total'],
			'coupon_code'    => $totals['coupon'] ? $totals['coupon']->code : '',
			'payment_status' => 'pending',
			'gateway'        => $totals['total'] > 0 ? dm_opt( 'gateway' ) : 'free',
			'ip'             => dm_client_ip(),
			'created_at'     => dm_now(),
		)
	);
	if ( ! $ok ) {
		return 0;
	}
	$order_id = (int) $wpdb->insert_id;
	foreach ( $totals['lines'] as $pid => $l ) {
		// Commission is snapshotted at the time of the transaction.
		$rate       = dm_commission_rate( $l['seller'], $pid );
		$commission = dm_round( $l['final'] * $rate / 100 );
		$wpdb->insert(
			dm_table( 'order_items' ),
			array(
				'order_id'                   => $order_id,
				'product_id'                 => $pid,
				'seller_id'                  => $l['seller'],
				'product_title'              => $l['title'],
				'list_price'                 => $l['price'],
				'price_at_purchase'          => $l['final'],
				'commission_percent_applied' => $rate,
				'commission_amount'          => $commission,
				'seller_net_amount'          => dm_round( $l['final'] - $commission ),
				'item_status'                => 'pending',
				'transfer_status'            => 'pending',
				'created_at'                 => dm_now(),
			)
		);
	}
	return $order_id;
}

/* -------------------------------------------------------------------------
 * Payment confirmation
 * ---------------------------------------------------------------------- */

/** Demo gateway: simulate a successful payment. */
function dm_do_demo_pay() {
	dm_require_login();
	$order = dm_get_order( absint( $_POST['order_id'] ?? 0 ) );
	if ( ! $order || (int) $order->buyer_id !== get_current_user_id() || 'demo' !== $order->gateway ) {
		dm_redirect( dm_url( 'account', 'orders' ) );
	}
	if ( ! empty( $_POST['simulate_fail'] ) ) {
		dm_mark_order_failed( $order->id, 'Demo payment failure' );
		dm_flash( 'error', __( 'Payment failed (simulated). You can retry.', 'digimarket' ) );
		dm_redirect( dm_url( 'checkout', 'pay', $order->id ) );
	}
	dm_fulfill_order( $order->id, 'demo_' . wp_generate_password( 14, false ) );
	dm_cart_set( array() );
	dm_cart_set_coupon( '' );
	dm_redirect( dm_url( 'order-received', $order->id ) );
}

/** Razorpay Checkout success handler (AJAX). */
add_action( 'wp_ajax_dm_rzp_verify', 'dm_ajax_rzp_verify' );
function dm_ajax_rzp_verify() {
	dm_ajax_check();
	$order = dm_get_order( absint( $_POST['order_id'] ?? 0 ) );
	if ( ! $order || (int) $order->buyer_id !== get_current_user_id() ) {
		wp_send_json_error( array( 'message' => __( 'Order not found.', 'digimarket' ) ), 404 );
	}
	$payment_id = sanitize_text_field( wp_unslash( $_POST['razorpay_payment_id'] ?? '' ) );
	$rzp_order  = sanitize_text_field( wp_unslash( $_POST['razorpay_order_id'] ?? '' ) );
	$signature  = sanitize_text_field( wp_unslash( $_POST['razorpay_signature'] ?? '' ) );
	$expected   = hash_hmac( 'sha256', $order->razorpay_order_id . '|' . $payment_id, dm_opt( 'rzp_key_secret' ) );
	if ( ! $payment_id || $rzp_order !== $order->razorpay_order_id || ! hash_equals( $expected, $signature ) ) {
		dm_mark_order_failed( $order->id, 'Signature verification failed' );
		wp_send_json_error( array( 'message' => __( 'Payment verification failed. If money was deducted it will be refunded automatically.', 'digimarket' ) ) );
	}
	// Ensure captured (payment_capture=1 normally auto-captures).
	$payment = dm_rzp_request( 'GET', '/v1/payments/' . rawurlencode( $payment_id ) );
	if ( ! is_wp_error( $payment ) && 'authorized' === ( $payment['status'] ?? '' ) ) {
		dm_rzp_request( 'POST', '/v1/payments/' . rawurlencode( $payment_id ) . '/capture', array( 'amount' => dm_to_paise( $order->order_total ), 'currency' => dm_opt( 'currency_code', 'INR' ) ) );
	}
	dm_fulfill_order( $order->id, $payment_id );
	dm_cart_set( array() );
	dm_cart_set_coupon( '' );
	wp_send_json_success( array( 'redirect' => dm_url( 'order-received', $order->id ) ) );
}

add_action( 'wp_ajax_dm_rzp_failed', 'dm_ajax_rzp_failed' );
function dm_ajax_rzp_failed() {
	dm_ajax_check();
	$order = dm_get_order( absint( $_POST['order_id'] ?? 0 ) );
	if ( $order && (int) $order->buyer_id === get_current_user_id() && 'pending' === $order->payment_status ) {
		dm_mark_order_failed( $order->id, sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Payment failed' ) ) );
	}
	wp_send_json_success();
}

function dm_mark_order_failed( $order_id, $note = '' ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'orders' ) . " SET payment_status = 'failed', note = %s WHERE id = %d AND payment_status = 'pending'", $note, $order_id ) );
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'order_items' ) . " SET item_status = 'failed' WHERE order_id = %d AND item_status = 'pending'", $order_id ) );
}

/**
 * Buyer retries a failed order: reset it to pending (new Razorpay order if needed).
 */
function dm_do_retry_payment() {
	dm_require_login();
	global $wpdb;
	$order = dm_get_order( absint( $_POST['order_id'] ?? 0 ) );
	if ( ! $order || (int) $order->buyer_id !== get_current_user_id() || ! in_array( $order->payment_status, array( 'failed', 'pending' ), true ) ) {
		dm_redirect( dm_url( 'account', 'orders' ) );
	}
	$wpdb->update( dm_table( 'orders' ), array( 'payment_status' => 'pending' ), array( 'id' => $order->id ) );
	$wpdb->update( dm_table( 'order_items' ), array( 'item_status' => 'pending' ), array( 'order_id' => $order->id ) );
	if ( 'razorpay' === $order->gateway && dm_rzp_ready() ) {
		$rzp = dm_rzp_request(
			'POST',
			'/v1/orders',
			array(
				'amount'          => dm_to_paise( $order->order_total ),
				'currency'        => dm_opt( 'currency_code', 'INR' ),
				'receipt'         => dm_order_number( $order->id ) . '-R' . time(),
				'payment_capture' => 1,
				'notes'           => array( 'dm_order_id' => (string) $order->id ),
			)
		);
		if ( ! is_wp_error( $rzp ) ) {
			$wpdb->update( dm_table( 'orders' ), array( 'razorpay_order_id' => $rzp['id'] ), array( 'id' => $order->id ) );
		}
	}
	dm_redirect( dm_url( 'checkout', 'pay', $order->id ) );
}

/**
 * Mark an order paid, unlock products, record payouts and split money.
 * Idempotent: safe to call from the browser callback AND the webhook.
 */
function dm_fulfill_order( $order_id, $payment_id ) {
	global $wpdb;
	$claimed = $wpdb->query(
		$wpdb->prepare(
			'UPDATE ' . dm_table( 'orders' ) . " SET payment_status = 'paid', razorpay_payment_id = %s, paid_at = %s WHERE id = %d AND payment_status IN ('pending','failed')",
			$payment_id,
			dm_now(),
			$order_id
		)
	);
	if ( ! $claimed ) {
		return false; // Already processed.
	}
	$order = dm_get_order( $order_id );
	$items = dm_get_order_items( $order_id );
	foreach ( $items as $item ) {
		$update = array( 'item_status' => 'paid' );
		$days   = (int) get_post_meta( $item->product_id, '_dm_access_days', true );
		if ( $days > 0 ) {
			$update['access_expires'] = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $days * DAY_IN_SECONDS );
		}
		if ( 'license_key' === get_post_meta( $item->product_id, '_dm_delivery', true ) ) {
			$key = dm_assign_license_key( $item );
			if ( $key ) {
				$update['license_key'] = $key;
			}
		}
		if ( $item->seller_net_amount <= 0 ) {
			$update['transfer_status'] = 'not_required';
		}
		$wpdb->update( dm_table( 'order_items' ), $update, array( 'id' => $item->id ) );
		update_post_meta( $item->product_id, '_dm_sales', (int) get_post_meta( $item->product_id, '_dm_sales', true ) + 1 );
		if ( $item->seller_net_amount > 0 ) {
			$wpdb->insert(
				dm_table( 'payouts' ),
				array(
					'seller_id'     => $item->seller_id,
					'order_item_id' => $item->id,
					'amount'        => $item->seller_net_amount,
					'status'        => 'pending',
					'created_at'    => dm_now(),
				)
			);
		}
		/* translators: 1 product 2 amount */
		dm_notify( $item->seller_id, sprintf( __( 'New sale: %1$s — you earn %2$s', 'digimarket' ), $item->product_title, dm_money( $item->seller_net_amount ) ), dm_url( 'dashboard', 'orders' ) );
	}
	if ( $order->coupon_code ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'coupons' ) . ' SET times_used = times_used + 1 WHERE code = %s', $order->coupon_code ) );
	}
	dm_process_transfers( $order_id );
	dm_notify( $order->buyer_id, sprintf( /* translators: %s order */ __( 'Order %s confirmed — your downloads are ready.', 'digimarket' ), dm_order_number( $order_id ) ), dm_url( 'account', 'purchases' ) );
	dm_email_order_confirmation( $order_id );
	do_action( 'dm_order_paid', $order_id );
	return true;
}

function dm_assign_license_key( $item ) {
	global $wpdb;
	$table = dm_table( 'license_keys' );
	for ( $try = 0; $try < 5; $try++ ) {
		$key = $wpdb->get_row( $wpdb->prepare( "SELECT id, key_value FROM $table WHERE product_id = %d AND is_used = 0 ORDER BY id ASC LIMIT 1", $item->product_id ) ); // phpcs:ignore
		if ( ! $key ) {
			return '';
		}
		$ok = $wpdb->query( $wpdb->prepare( "UPDATE $table SET is_used = 1, assigned_order_id = %d, assigned_item_id = %d WHERE id = %d AND is_used = 0", $item->order_id, $item->id, $key->id ) ); // phpcs:ignore
		if ( $ok ) {
			return $key->key_value;
		}
	}
	return '';
}

/**
 * Split: one transfer per seller from the captured payment.
 * Commission stays in the platform account automatically.
 */
function dm_process_transfers( $order_id ) {
	global $wpdb;
	$order = dm_get_order( $order_id );
	if ( ! $order || 'paid' !== $order->payment_status ) {
		return;
	}
	$items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'order_items' ) . " WHERE order_id = %d AND item_status = 'paid' AND transfer_status IN ('pending','failed') AND transfer_id = '' AND seller_net_amount > 0", $order_id ) );
	if ( ! $items ) {
		return;
	}
	$by_seller = array();
	foreach ( $items as $it ) {
		$by_seller[ $it->seller_id ][] = $it;
	}

	// Demo / non-Razorpay: simulate an instant transfer so dashboards reflect the split.
	if ( ! in_array( $order->gateway, array( 'razorpay' ), true ) ) {
		foreach ( $items as $it ) {
			$ref = 'demo_trf_' . $it->id;
			$wpdb->update( dm_table( 'order_items' ), array( 'transfer_status' => 'processed', 'transfer_id' => $ref, 'transfer_note' => 'Simulated (demo gateway)' ), array( 'id' => $it->id ) );
			$wpdb->update( dm_table( 'payouts' ), array( 'status' => 'settled', 'reference' => $ref, 'settled_at' => dm_now(), 'note' => 'Demo mode' ), array( 'order_item_id' => $it->id ) );
		}
		return;
	}

	$transfers = array();
	foreach ( $by_seller as $seller_id => $rows ) {
		$account = get_user_meta( $seller_id, 'dm_rzp_account_id', true );
		if ( ! $account ) {
			foreach ( $rows as $it ) {
				dm_transfer_failed( $it, __( 'Seller has no Razorpay linked account yet.', 'digimarket' ) );
			}
			continue;
		}
		$amount      = array_sum( wp_list_pluck( $rows, 'seller_net_amount' ) );
		$transfers[] = array(
			'account'  => $account,
			'amount'   => dm_to_paise( $amount ),
			'currency' => dm_opt( 'currency_code', 'INR' ),
			'notes'    => array(
				'dm_order_id' => (string) $order_id,
				'dm_seller'   => (string) $seller_id,
				'dm_items'    => implode( ',', wp_list_pluck( $rows, 'id' ) ),
			),
			'on_hold'  => 0,
		);
	}
	if ( ! $transfers ) {
		return;
	}
	$res = dm_rzp_request( 'POST', '/v1/payments/' . rawurlencode( $order->razorpay_payment_id ) . '/transfers', array( 'transfers' => $transfers ), 'dm-trf-' . $order_id . '-' . md5( wp_json_encode( $transfers ) ) );
	if ( is_wp_error( $res ) ) {
		foreach ( $items as $it ) {
			if ( get_user_meta( $it->seller_id, 'dm_rzp_account_id', true ) ) {
				dm_transfer_failed( $it, $res->get_error_message() );
			}
		}
		return;
	}
	$list = isset( $res['items'] ) ? $res['items'] : ( isset( $res['id'] ) ? array( $res ) : array() );
	foreach ( $list as $t ) {
		$ids = isset( $t['notes']['dm_items'] ) ? array_map( 'absint', explode( ',', $t['notes']['dm_items'] ) ) : array();
		foreach ( $ids as $iid ) {
			$status = isset( $t['status'] ) && 'processed' === $t['status'] ? 'processed' : 'pending';
			$wpdb->update( dm_table( 'order_items' ), array( 'transfer_id' => $t['id'], 'transfer_status' => $status, 'transfer_note' => '' ), array( 'id' => $iid ) );
			$wpdb->update( dm_table( 'payouts' ), array( 'reference' => $t['id'], 'status' => 'processed' === $status ? 'settled' : 'pending', 'settled_at' => 'processed' === $status ? dm_now() : null ), array( 'order_item_id' => $iid ) );
		}
	}
}

function dm_transfer_failed( $item, $reason ) {
	global $wpdb;
	$wpdb->update( dm_table( 'order_items' ), array( 'transfer_status' => 'failed', 'transfer_note' => mb_substr( $reason, 0, 250 ) ), array( 'id' => $item->id ) );
	$wpdb->update( dm_table( 'payouts' ), array( 'status' => 'failed', 'note' => mb_substr( $reason, 0, 250 ) ), array( 'order_item_id' => $item->id ) );
	/* translators: 1 order 2 reason */
	dm_notify_admins( sprintf( __( 'Payout transfer failed for %1$s: %2$s', 'digimarket' ), dm_order_number( $item->order_id ), $reason ), admin_url( 'admin.php?page=dm-payouts&status=failed' ) );
	dm_notify( $item->seller_id, __( 'A payout transfer failed. Please check your bank/KYC details in Payouts.', 'digimarket' ), dm_url( 'dashboard', 'payouts' ) );
}

/* -------------------------------------------------------------------------
 * Refunds (reverse both legs of the split)
 * ---------------------------------------------------------------------- */

function dm_refund_item( $item_id, $reason = '' ) {
	global $wpdb;
	$item = dm_get_order_item( $item_id );
	if ( ! $item || 'paid' !== $item->item_status ) {
		return new WP_Error( 'state', __( 'Only paid items can be refunded.', 'digimarket' ) );
	}
	$order = dm_get_order( $item->order_id );
	if ( 'razorpay' === $order->gateway && $item->price_at_purchase > 0 ) {
		if ( ! dm_rzp_ready() ) {
			return new WP_Error( 'rzp', __( 'Razorpay is not configured.', 'digimarket' ) );
		}
		// 1) Pull back the seller's share from their linked account.
		if ( $item->transfer_id && 'processed' === $item->transfer_status && $item->seller_net_amount > 0 ) {
			$rev = dm_rzp_request( 'POST', '/v1/transfers/' . rawurlencode( $item->transfer_id ) . '/reversals', array( 'amount' => dm_to_paise( $item->seller_net_amount ) ), 'dm-rev-' . $item->id );
			if ( is_wp_error( $rev ) ) {
				return $rev;
			}
		}
		// 2) Refund the buyer (commission portion comes back from the platform balance).
		$ref = dm_rzp_request(
			'POST',
			'/v1/payments/' . rawurlencode( $order->razorpay_payment_id ) . '/refund',
			array(
				'amount' => dm_to_paise( $item->price_at_purchase ),
				'notes'  => array( 'dm_item' => (string) $item->id, 'reason' => mb_substr( $reason, 0, 200 ) ),
			),
			'dm-ref-' . $item->id
		);
		if ( is_wp_error( $ref ) ) {
			return $ref;
		}
	}
	dm_mark_item_refunded( $item, $reason );
	return true;
}

function dm_mark_item_refunded( $item, $reason = '' ) {
	global $wpdb;
	$wpdb->update(
		dm_table( 'order_items' ),
		array(
			'item_status'     => 'refunded',
			'refunded_at'     => dm_now(),
			'transfer_status' => in_array( $item->transfer_status, array( 'processed' ), true ) ? 'reversed' : $item->transfer_status,
		),
		array( 'id' => $item->id )
	);
	$wpdb->update( dm_table( 'payouts' ), array( 'status' => 'reversed', 'note' => 'Refunded' ), array( 'order_item_id' => $item->id ) );
	// Release the license key back? No — keys may have been seen; mark used.
	update_post_meta( $item->product_id, '_dm_sales', max( 0, (int) get_post_meta( $item->product_id, '_dm_sales', true ) - 1 ) );
	dm_recalc_order_status( $item->order_id );
	$order = dm_get_order( $item->order_id );
	/* translators: 1 product 2 amount */
	dm_notify( $order->buyer_id, sprintf( __( 'Refund processed for %1$s (%2$s).', 'digimarket' ), $item->product_title, dm_money( $item->price_at_purchase ) ), dm_url( 'account', 'orders' ) );
	dm_email_refund( $item, $reason );
	if ( is_user_logged_in() ) {
		dm_audit( 'refund', 'order_item', $item->id, array( 'order' => $item->order_id, 'amount' => $item->price_at_purchase, 'reason' => $reason ) );
	}
}

function dm_recalc_order_status( $order_id ) {
	global $wpdb;
	$items    = dm_get_order_items( $order_id );
	$refunded = 0;
	foreach ( $items as $i ) {
		if ( 'refunded' === $i->item_status ) {
			++$refunded;
		}
	}
	if ( ! $refunded ) {
		return;
	}
	$status = count( $items ) === $refunded ? 'refunded' : 'partially_refunded';
	$wpdb->update( dm_table( 'orders' ), array( 'payment_status' => $status ), array( 'id' => $order_id ) );
}

/* -------------------------------------------------------------------------
 * Webhooks: POST /wp-json/dm/v1/razorpay-webhook
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route(
		'dm/v1',
		'/razorpay-webhook',
		array(
			'methods'             => 'POST',
			'callback'            => 'dm_rzp_webhook',
			'permission_callback' => '__return_true',
		)
	);
} );

function dm_rzp_webhook( WP_REST_Request $request ) {
	global $wpdb;
	$secret    = dm_opt( 'rzp_webhook_secret' );
	$body      = $request->get_body();
	$signature = (string) $request->get_header( 'x_razorpay_signature' );
	if ( ! $secret || ! $signature || ! hash_equals( hash_hmac( 'sha256', $body, $secret ), $signature ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid signature' ), 400 );
	}
	$event_id = (string) $request->get_header( 'x_razorpay_event_id' );
	$data     = json_decode( $body, true );
	$event    = isset( $data['event'] ) ? sanitize_text_field( $data['event'] ) : '';
	if ( $event_id ) {
		$seen = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'webhook_events' ) . ' WHERE event_id = %s', $event_id ) );
		if ( $seen ) {
			return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
		}
		$wpdb->insert( dm_table( 'webhook_events' ), array( 'event_id' => $event_id, 'event' => $event, 'created_at' => dm_now() ) );
	}
	$payload = isset( $data['payload'] ) ? $data['payload'] : array();

	switch ( $event ) {
		case 'payment.captured':
		case 'order.paid':
			$p = $payload['payment']['entity'] ?? array();
			$o = ! empty( $p['order_id'] ) ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . ' WHERE razorpay_order_id = %s', $p['order_id'] ) ) : null;
			if ( $o ) {
				if ( dm_to_paise( $o->order_total ) === (int) ( $p['amount'] ?? 0 ) ) {
					dm_fulfill_order( $o->id, sanitize_text_field( $p['id'] ) );
				}
				dm_process_transfers( $o->id ); // Retry any pending split.
			}
			break;

		case 'payment.failed':
			$p = $payload['payment']['entity'] ?? array();
			$o = ! empty( $p['order_id'] ) ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . ' WHERE razorpay_order_id = %s', $p['order_id'] ) ) : null;
			if ( $o && 'pending' === $o->payment_status ) {
				dm_mark_order_failed( $o->id, sanitize_text_field( $p['error_description'] ?? 'Payment failed' ) );
				dm_notify( $o->buyer_id, sprintf( /* translators: %s order */ __( 'Payment for %s failed. You can retry from Order History.', 'digimarket' ), dm_order_number( $o->id ) ), dm_url( 'account', 'orders' ) );
			}
			break;

		case 'transfer.processed':
			$t = $payload['transfer']['entity'] ?? array();
			if ( ! empty( $t['id'] ) ) {
				$wpdb->update( dm_table( 'order_items' ), array( 'transfer_status' => 'processed', 'transfer_note' => '' ), array( 'transfer_id' => $t['id'] ) );
				$wpdb->update( dm_table( 'payouts' ), array( 'status' => 'settled', 'settled_at' => dm_now() ), array( 'reference' => $t['id'] ) );
			}
			break;

		case 'transfer.failed':
			$t = $payload['transfer']['entity'] ?? array();
			if ( ! empty( $t['id'] ) ) {
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'order_items' ) . ' WHERE transfer_id = %s', $t['id'] ) );
				foreach ( $rows as $r ) {
					dm_transfer_failed( $r, sanitize_text_field( $t['error']['description'] ?? 'Transfer failed' ) );
				}
			}
			break;

		case 'refund.processed':
			$r   = $payload['refund']['entity'] ?? array();
			$pid = $r['payment_id'] ?? '';
			$o   = $pid ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . ' WHERE razorpay_payment_id = %s', $pid ) ) : null;
			if ( $o ) {
				$item_id = isset( $r['notes']['dm_item'] ) ? absint( $r['notes']['dm_item'] ) : 0;
				if ( $item_id ) {
					$it = dm_get_order_item( $item_id );
					if ( $it && 'paid' === $it->item_status ) {
						dm_mark_item_refunded( $it, 'Razorpay refund ' . sanitize_text_field( $r['id'] ?? '' ) );
					}
				} elseif ( (int) ( $r['amount'] ?? 0 ) >= dm_to_paise( $o->order_total ) ) {
					// Full refund initiated from the Razorpay dashboard.
					foreach ( dm_get_order_items( $o->id ) as $it ) {
						if ( 'paid' === $it->item_status ) {
							dm_mark_item_refunded( $it, 'Razorpay dashboard refund' );
						}
					}
				} else {
					$wpdb->update( dm_table( 'orders' ), array( 'note' => 'Partial refund via Razorpay dashboard: ' . sanitize_text_field( $r['id'] ?? '' ) ), array( 'id' => $o->id ) );
				}
			}
			break;

		case 'payment.dispute.created':
		case 'payment.dispute.lost':
		case 'payment.dispute.won':
		case 'payment.dispute.closed':
			$d   = $payload['dispute']['entity'] ?? ( $payload['payment']['entity'] ?? array() );
			$pid = $d['payment_id'] ?? ( $d['id'] ?? '' );
			if ( $pid ) {
				$flag = in_array( $event, array( 'payment.dispute.created', 'payment.dispute.lost' ), true ) ? 1 : 0;
				$wpdb->update( dm_table( 'orders' ), array( 'disputed' => $flag, 'note' => $event ), array( 'razorpay_payment_id' => $pid ) );
				dm_notify_admins( sprintf( /* translators: %s event */ __( 'Dispute update: %s', 'digimarket' ), $event ), admin_url( 'admin.php?page=dm-transactions&disputed=1' ) );
			}
			break;
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
