<?php
/**
 * Plans, subscriptions, payments and invoices.
 *
 * Access is only extended from a verified Razorpay callback or webhook.
 * Each Razorpay payment ID can extend an account only once.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Billing {

	/* ---------- Plans ---------- */

	public static function plans( $active_only = true ) {
		global $wpdb;
		$where = $active_only ? 'WHERE is_active = 1' : '';
		return $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'plans' ) . " $where ORDER BY sort_order ASC, id ASC" );
	}

	public static function plan( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'plans' ) . ' WHERE id = %d', $id ) );
	}

	public static function default_plan() {
		$plans = self::plans();
		return $plans ? $plans[0] : null;
	}

	public static function default_plan_price() {
		$plan = self::default_plan();
		return $plan ? (int) $plan->price_paise : 5900;
	}

	public static function plan_to_app( $plan ) {
		$mode = PKC_Razorpay::mode();
		return array(
			'id'          => (int) $plan->id,
			'name'        => $plan->name,
			'price'       => (int) $plan->price_paise,
			'price_text'  => pkc_money( $plan->price_paise ),
			'period_days' => (int) $plan->period_days,
			'description' => (string) $plan->description,
			'autopay'     => '' !== ( 'live' === $mode ? $plan->rzp_plan_live : $plan->rzp_plan_test ),
		);
	}

	/* ---------- Subscriptions ---------- */

	public static function latest_subscription( $org_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'subscriptions' ) . " WHERE org_id = %d AND status <> 'created' ORDER BY id DESC LIMIT 1",
				$org_id
			)
		);
	}

	/** Autopay that can still charge the customer. */
	public static function active_subscription( $org_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'subscriptions' ) . " WHERE org_id = %d AND status IN ('authenticated','active','pending') ORDER BY id DESC LIMIT 1",
				$org_id
			)
		);
	}

	public static function subscription_by_rzp( $rzp_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'subscriptions' ) . ' WHERE rzp_subscription_id = %s', $rzp_id ) );
	}

	public static function set_subscription_status( $sub, $status, $current_end = 0 ) {
		global $wpdb;
		$data = array(
			'status'     => sanitize_key( $status ),
			'updated_at' => pkc_now(),
		);
		if ( $current_end ) {
			$data['current_end'] = gmdate( 'Y-m-d H:i:s', (int) $current_end );
		}
		$wpdb->update( pkc_table( 'subscriptions' ), $data, array( 'id' => (int) $sub->id ) );
		if ( $sub->status !== $status ) {
			PKC_Activity_Log::add( 'subscription.status', $sub->org_id, $sub->rzp_subscription_id . ': ' . $sub->status . ' → ' . $status );
		}
	}

	private static function checkout_base( $org, $plan ) {
		$brand = sanitize_hex_color( (string) pkc_setting( 'brand_color', '#4F46E5' ) );
		return array(
			'key'         => PKC_Razorpay::key_id(),
			'name'        => pkc_setting( 'site_name', 'Pikacart' ),
			'description' => sprintf( /* translators: %s: plan name */ __( '%s plan', 'pikacart' ), $plan->name ),
			'prefill'     => array(
				'name'    => $org->contact_name,
				'email'   => $org->email,
				'contact' => $org->mobile,
			),
			'notes'       => array(
				'org_id'  => (string) $org->id,
				'plan_id' => (string) $plan->id,
			),
			'theme'       => array( 'color' => $brand ? $brand : '#4F46E5' ),
		);
	}

	/**
	 * Create a Razorpay subscription (autopay) and return Checkout options.
	 */
	public static function start_subscription( $org, $plan_id ) {
		global $wpdb;
		$plan = self::plan( $plan_id );
		if ( ! $plan || ! $plan->is_active ) {
			return new WP_Error( 'pkc_plan', __( 'This plan is not available.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( self::active_subscription( $org->id ) ) {
			return new WP_Error( 'pkc_plan', __( 'Autopay is already active on this account.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$mode        = PKC_Razorpay::mode();
		$rzp_plan_id = 'live' === $mode ? $plan->rzp_plan_live : $plan->rzp_plan_test;
		if ( '' === $rzp_plan_id ) {
			return new WP_Error( 'pkc_plan', __( 'Autopay is not set up for this plan yet. Please use the one-time payment.', 'pikacart' ), array( 'status' => 400 ) );
		}

		$period = max( 1, (int) $plan->period_days );
		$sub    = PKC_Razorpay::request(
			'POST',
			'subscriptions',
			array(
				'plan_id'         => $rzp_plan_id,
				'total_count'     => max( 1, (int) floor( 3650 / $period ) ),
				'customer_notify' => 1,
				'notes'           => array(
					'org_id'  => (string) $org->id,
					'plan_id' => (string) $plan->id,
				),
			)
		);
		if ( is_wp_error( $sub ) ) {
			return $sub;
		}

		$wpdb->insert(
			pkc_table( 'subscriptions' ),
			array(
				'org_id'              => $org->id,
				'plan_id'             => $plan->id,
				'rzp_subscription_id' => sanitize_text_field( $sub['id'] ),
				'mode'                => $mode,
				'status'              => 'created',
				'created_at'          => pkc_now(),
				'updated_at'          => pkc_now(),
			)
		);

		$options                    = self::checkout_base( $org, $plan );
		$options['subscription_id'] = $sub['id'];
		return array( 'checkout' => $options );
	}

	/**
	 * Browser callback after the first autopay payment.
	 */
	public static function verify_subscription( $org, $payment_id, $subscription_id, $signature ) {
		$local = self::subscription_by_rzp( $subscription_id );
		if ( ! $local || (int) $local->org_id !== (int) $org->id ) {
			return new WP_Error( 'pkc_pay', __( 'Payment could not be matched to your account.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( ! PKC_Razorpay::verify_signature( $payment_id . '|' . $subscription_id, $signature, $local->mode ) ) {
			return new WP_Error( 'pkc_pay', __( 'Payment verification failed. If money was deducted, it will be confirmed automatically in a few minutes.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$payment = PKC_Razorpay::request( 'GET', 'payments/' . rawurlencode( $payment_id ), array(), $local->mode );
		if ( is_wp_error( $payment ) ) {
			return $payment;
		}
		$plan = self::plan( $local->plan_id );
		self::set_subscription_status( $local, 'active' );
		$result = self::record_payment( $org->id, $plan, $payment, 'subscription', $subscription_id, '', $local->mode );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		PKC_Activity_Log::add( 'subscription.created', $org->id, $subscription_id );
		return array( 'message' => __( 'Payment successful. Autopay is active.', 'pikacart' ) );
	}

	/**
	 * One-time payment for one period (fallback for banks without autopay).
	 */
	public static function create_order( $org, $plan_id ) {
		global $wpdb;
		if ( ! pkc_setting( 'rzp_onetime', 1 ) ) {
			return new WP_Error( 'pkc_plan', __( 'One-time payment is not available.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$plan = self::plan( $plan_id );
		if ( ! $plan || ! $plan->is_active ) {
			return new WP_Error( 'pkc_plan', __( 'This plan is not available.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$mode    = PKC_Razorpay::mode();
		$receipt = 'pkc_' . $org->id . '_' . time();
		$order   = PKC_Razorpay::request(
			'POST',
			'orders',
			array(
				'amount'          => (int) $plan->price_paise,
				'currency'        => 'INR',
				'receipt'         => $receipt,
				'payment_capture' => 1,
				'notes'           => array(
					'org_id'  => (string) $org->id,
					'plan_id' => (string) $plan->id,
				),
			)
		);
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$wpdb->insert(
			pkc_table( 'payments' ),
			array(
				'org_id'       => $org->id,
				'plan_id'      => $plan->id,
				'kind'         => 'one_time',
				'rzp_order_id' => sanitize_text_field( $order['id'] ),
				'amount_paise' => (int) $plan->price_paise,
				'currency'     => 'INR',
				'status'       => 'created',
				'mode'         => $mode,
				'created_at'   => pkc_now(),
			)
		);

		$options             = self::checkout_base( $org, $plan );
		$options['order_id'] = $order['id'];
		$options['amount']   = (int) $plan->price_paise;
		$options['currency'] = 'INR';
		return array( 'checkout' => $options );
	}

	public static function verify_order( $org, $order_id, $payment_id, $signature ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'payments' ) . ' WHERE rzp_order_id = %s AND org_id = %d', $order_id, $org->id )
		);
		if ( ! $row ) {
			return new WP_Error( 'pkc_pay', __( 'Payment could not be matched to your account.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( ! PKC_Razorpay::verify_signature( $order_id . '|' . $payment_id, $signature, $row->mode ) ) {
			return new WP_Error( 'pkc_pay', __( 'Payment verification failed. If money was deducted, it will be confirmed automatically in a few minutes.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$payment = PKC_Razorpay::request( 'GET', 'payments/' . rawurlencode( $payment_id ), array(), $row->mode );
		if ( is_wp_error( $payment ) ) {
			return $payment;
		}
		if ( 'authorized' === ( $payment['status'] ?? '' ) ) {
			$captured = PKC_Razorpay::request(
				'POST',
				'payments/' . rawurlencode( $payment_id ) . '/capture',
				array(
					'amount'   => (int) $payment['amount'],
					'currency' => 'INR',
				),
				$row->mode
			);
			if ( ! is_wp_error( $captured ) ) {
				$payment = $captured;
			}
		}
		$result = self::record_payment( $org->id, self::plan( $row->plan_id ), $payment, 'one_time', '', $order_id, $row->mode );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return array( 'message' => __( 'Payment successful. Your plan is active.', 'pikacart' ) );
	}

	/**
	 * Record a captured payment and extend the plan. Safe to call many times
	 * with the same payment: only the first call extends the account.
	 *
	 * @param array $payment Razorpay payment entity.
	 * @return array|WP_Error
	 */
	public static function record_payment( $org_id, $plan, $payment, $kind, $subscription_id = '', $order_id = '', $mode = '' ) {
		global $wpdb;
		$table      = pkc_table( 'payments' );
		$payment_id = sanitize_text_field( $payment['id'] ?? '' );
		$status     = $payment['status'] ?? '';

		if ( '' === $payment_id ) {
			return new WP_Error( 'pkc_pay', __( 'Invalid payment.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( 'captured' !== $status ) {
			return new WP_Error( 'pkc_pay', __( 'The payment is not complete yet. It will be confirmed automatically once your bank approves it.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$org = PKC_Organisations::get( $org_id );
		if ( ! $org ) {
			return new WP_Error( 'pkc_pay', __( 'Account not found.', 'pikacart' ), array( 'status' => 404 ) );
		}
		if ( ! $plan ) {
			$plan = self::default_plan();
		}
		if ( ! $plan ) {
			return new WP_Error( 'pkc_pay', __( 'No plan is configured.', 'pikacart' ), array( 'status' => 500 ) );
		}

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE rzp_payment_id = %s", $payment_id ) );
		if ( $existing && 'captured' === $existing->status ) {
			return array( 'duplicate' => true );
		}

		$now        = time();
		$start      = max( $now, pkc_ts( $org->period_end ) );
		$end        = $start + max( 1, (int) $plan->period_days ) * DAY_IN_SECONDS;
		$mode       = $mode ? $mode : PKC_Razorpay::mode();
		$common     = array(
			'org_id'              => $org_id,
			'plan_id'             => (int) $plan->id,
			'kind'                => $kind,
			'rzp_payment_id'      => $payment_id,
			'rzp_subscription_id' => sanitize_text_field( $subscription_id ),
			'amount_paise'        => (int) ( $payment['amount'] ?? 0 ),
			'currency'            => sanitize_text_field( $payment['currency'] ?? 'INR' ),
			'method'              => sanitize_text_field( $payment['method'] ?? '' ),
			'status'              => 'captured',
			'mode'                => $mode,
			'period_start'        => gmdate( 'Y-m-d H:i:s', $start ),
			'period_end'          => gmdate( 'Y-m-d H:i:s', $end ),
			'error'               => '',
		);

		$won = false;
		if ( $existing ) {
			// A failed attempt with the same ID was later captured.
			$won = (bool) $wpdb->query(
				$wpdb->prepare( "UPDATE $table SET status = 'captured', period_start = %s, period_end = %s, amount_paise = %d, error = '' WHERE id = %d AND status <> 'captured'", $common['period_start'], $common['period_end'], $common['amount_paise'], $existing->id )
			);
			$row_id = (int) $existing->id;
		} elseif ( $order_id ) {
			$order_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE rzp_order_id = %s AND org_id = %d ORDER BY id ASC LIMIT 1", $order_id, $org_id ) );
			if ( $order_row ) {
				// Conditional update: only one request can move it from "created".
				$won    = (bool) $wpdb->query(
					$wpdb->prepare(
						"UPDATE $table SET rzp_payment_id = %s, amount_paise = %d, method = %s, status = 'captured', period_start = %s, period_end = %s WHERE id = %d AND status = 'created'",
						$payment_id,
						$common['amount_paise'],
						$common['method'],
						$common['period_start'],
						$common['period_end'],
						$order_row->id
					)
				);
				$row_id = (int) $order_row->id;
			}
		}
		if ( ! $won && ! $existing && empty( $row_id ) ) {
			$common['rzp_order_id'] = sanitize_text_field( $order_id );
			$common['created_at']   = pkc_now();
			// The unique key on rzp_payment_id makes a second insert fail.
			$won    = (bool) $wpdb->insert( $table, $common );
			$row_id = (int) $wpdb->insert_id;
		}
		if ( ! $won ) {
			return array( 'duplicate' => true );
		}

		$invoice = self::next_invoice_no();
		$wpdb->update( $table, array( 'invoice_no' => $invoice ), array( 'id' => $row_id ) );

		PKC_Organisations::update(
			$org_id,
			array(
				'period_end' => gmdate( 'Y-m-d H:i:s', $end ),
				'plan_id'    => (int) $plan->id,
			)
		);
		PKC_Organisations::set_meta( $org_id, 'expiry_warned_for', '' );
		$org = PKC_Access::refresh( PKC_Organisations::get( $org_id ) );

		PKC_Activity_Log::add( 'payment.success', $org_id, pkc_money( $common['amount_paise'] ) . ' · ' . $payment_id . ' · ' . $invoice );
		PKC_Emails::send_to_org(
			$org,
			'payment_success',
			array(
				'amount'     => pkc_money( $common['amount_paise'] ),
				'invoice_no' => $invoice,
				'date'       => pkc_date( gmdate( 'Y-m-d H:i:s', $end ), get_option( 'date_format', 'j M Y' ) ),
				'link'       => pkc_url( 'app', 'subscription' ),
			)
		);
		return array(
			'payment_row' => $row_id,
			'invoice_no'  => $invoice,
		);
	}

	/**
	 * Record a failed payment (once per payment ID) and email the customer.
	 */
	public static function record_failed( $org_id, $payment, $kind = 'subscription', $subscription_id = '' ) {
		global $wpdb;
		$payment_id = sanitize_text_field( $payment['id'] ?? '' );
		if ( '' === $payment_id ) {
			return;
		}
		$table = pkc_table( 'payments' );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE rzp_payment_id = %s", $payment_id ) ) ) {
			return;
		}
		$order_id = sanitize_text_field( $payment['order_id'] ?? '' );
		$reason   = sanitize_text_field( $payment['error_description'] ?? '' );
		$inserted = $wpdb->insert(
			$table,
			array(
				'org_id'              => $org_id,
				'plan_id'             => 0,
				'kind'                => $kind,
				'rzp_payment_id'      => $payment_id,
				'rzp_order_id'        => $order_id,
				'rzp_subscription_id' => sanitize_text_field( $subscription_id ),
				'amount_paise'        => (int) ( $payment['amount'] ?? 0 ),
				'currency'            => 'INR',
				'method'              => sanitize_text_field( $payment['method'] ?? '' ),
				'status'              => 'failed',
				'mode'                => PKC_Razorpay::mode(),
				'error'               => $reason,
				'created_at'          => pkc_now(),
			)
		);
		if ( ! $inserted ) {
			return;
		}
		PKC_Activity_Log::add( 'payment.failed', $org_id, $payment_id . ( $reason ? ' · ' . $reason : '' ) );
		PKC_Emails::send_to_org(
			PKC_Organisations::get( $org_id ),
			'payment_failed',
			array(
				'reason' => $reason,
				'link'   => pkc_url( 'app', 'subscription' ),
			)
		);
	}

	/**
	 * Cancel autopay. Access continues until the paid period ends.
	 */
	public static function cancel_autopay( $org ) {
		$sub = self::active_subscription( $org->id );
		if ( ! $sub ) {
			return new WP_Error( 'pkc_plan', __( 'There is no active autopay to cancel.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$res = PKC_Razorpay::request( 'POST', 'subscriptions/' . rawurlencode( $sub->rzp_subscription_id ) . '/cancel', array( 'cancel_at_cycle_end' => 0 ), $sub->mode );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		self::set_subscription_status( $sub, 'cancelled' );
		PKC_Activity_Log::add( 'subscription.cancel', $org->id, $sub->rzp_subscription_id );
		PKC_Access::refresh( PKC_Organisations::get( $org->id ) );
		return array( 'message' => __( 'Autopay cancelled. You keep access until the end of the period you paid for.', 'pikacart' ) );
	}

	/* ---------- Invoices ---------- */

	/**
	 * Sequential invoice number per Indian financial year, e.g. PKC-2026-27-0001.
	 */
	public static function next_invoice_no() {
		global $wpdb;
		$year  = (int) wp_date( 'Y' );
		$fy    = ( (int) wp_date( 'n' ) >= 4 ) ? $year : $year - 1;
		$label = $fy . '-' . substr( (string) ( $fy + 1 ), 2 );
		$name  = 'pkc_invoice_seq_' . $fy;
		add_option( $name, 0, '', 'no' );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s", $name ) );
		wp_cache_delete( $name, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		$seq = (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
		return pkc_setting( 'invoice_prefix', 'PKC-' ) . $label . '-' . str_pad( (string) $seq, 4, '0', STR_PAD_LEFT );
	}

	public static function payments_for_org( $org_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'payments' ) . " WHERE org_id = %d AND status IN ('captured','failed','refunded') ORDER BY id DESC LIMIT %d", $org_id, $limit )
		);
	}

	public static function method_label( $method ) {
		$map = array(
			'upi'        => 'UPI',
			'card'       => __( 'Card', 'pikacart' ),
			'netbanking' => __( 'Net banking', 'pikacart' ),
			'wallet'     => __( 'Wallet', 'pikacart' ),
			'emandate'   => __( 'eMandate', 'pikacart' ),
			'manual'     => __( 'Manual', 'pikacart' ),
		);
		return isset( $map[ $method ] ) ? $map[ $method ] : ucfirst( (string) $method );
	}

	public static function payment_to_app( $p ) {
		return array(
			'id'         => (int) $p->id,
			'date'       => pkc_date( $p->created_at ),
			'amount'     => pkc_money( $p->amount_paise ),
			'status'     => $p->status,
			'method'     => self::method_label( $p->method ),
			'kind'       => 'subscription' === $p->kind ? __( 'Autopay', 'pikacart' ) : __( 'One-time', 'pikacart' ),
			'invoice_no' => $p->invoice_no,
			'payment_id' => (string) $p->rzp_payment_id,
			'error'      => (string) $p->error,
		);
	}

	/**
	 * Everything the browser needs to draw the invoice PDF.
	 */
	public static function invoice_data( $p, $org ) {
		$plan     = self::plan( $p->plan_id );
		$amount   = (int) $p->amount_paise;
		$gstin    = trim( (string) pkc_setting( 'gstin', '' ) );
		$rate     = (float) pkc_setting( 'gst_rate', 18 );
		$taxable  = $gstin && $rate > 0 ? (int) round( $amount * 100 / ( 100 + $rate ) ) : $amount;
		$tax      = $amount - $taxable;
		$period   = ( $p->period_start && $p->period_end ) ? pkc_date( $p->period_start, 'j M Y' ) . ' – ' . pkc_date( $p->period_end, 'j M Y' ) : '';

		return array(
			'seller'   => array(
				'name'    => pkc_setting( 'site_name', 'Pikacart' ),
				'address' => (string) pkc_setting( 'business_address', '' ),
				'email'   => pkc_support_email(),
				'phone'   => (string) pkc_setting( 'contact_phone', '' ),
				'gstin'   => $gstin,
			),
			'buyer'    => array(
				'name'    => $org->name,
				'contact' => $org->contact_name,
				'email'   => $org->email,
				'mobile'  => $org->mobile,
				'address' => (string) $org->address,
			),
			'invoice'  => array(
				'number'     => $p->invoice_no,
				'date'       => pkc_date( $p->created_at, 'j M Y' ),
				'payment_id' => (string) $p->rzp_payment_id,
				'method'     => self::method_label( $p->method ),
			),
			'item'     => array(
				'name'   => sprintf( /* translators: %s: plan name */ __( 'Pikacart %s subscription', 'pikacart' ), $plan ? $plan->name : '' ),
				'period' => $period,
			),
			'amounts'  => array(
				'taxable'  => pkc_money( $taxable ),
				'tax'      => pkc_money( $tax ),
				'tax_rate' => $rate,
				'total'    => pkc_money( $amount ),
				'show_tax' => '' !== $gstin,
			),
		);
	}
}
