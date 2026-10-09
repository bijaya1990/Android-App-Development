<?php
/**
 * Subscription and payment endpoints for the organisation app.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Billing {

	public static function routes() {
		$routes = array(
			array( 'billing', 'GET', 'summary' ),
			array( 'billing/subscribe', 'POST', 'subscribe' ),
			array( 'billing/verify-subscription', 'POST', 'verify_subscription' ),
			array( 'billing/order', 'POST', 'order' ),
			array( 'billing/coupon', 'POST', 'coupon' ),
			array( 'billing/verify-order', 'POST', 'verify_order' ),
			array( 'billing/cancel', 'POST', 'cancel' ),
			array( 'billing/invoice/(?P<id>\d+)', 'GET', 'invoice' ),
		);
		foreach ( $routes as $r ) {
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
	}

	public static function summary() {
		$org      = PKC_REST::org();
		$sub      = PKC_Billing::active_subscription( $org->id );
		$plan     = $org->plan_id ? PKC_Billing::plan( $org->plan_id ) : null;
		$payments = array_map( array( 'PKC_Billing', 'payment_to_app' ), PKC_Billing::payments_for_org( $org->id ) );

		return PKC_REST::ok(
			array(
				'plans'       => array_map( array( 'PKC_Billing', 'plan_to_app' ), PKC_Billing::plans() ),
				'current'     => $plan ? $plan->name : '',
				'status'      => $org->status,
				'label'       => PKC_Access::label( $org->status ),
				'period_end'  => $org->period_end ? pkc_date( $org->period_end, get_option( 'date_format', 'j M Y' ) ) : '',
				'next_bill'   => ( $sub && $sub->current_end ) ? pkc_date( $sub->current_end, get_option( 'date_format', 'j M Y' ) ) : '',
				'autopay'     => $sub ? array( 'status' => $sub->status ) : null,
				'onetime'     => (bool) pkc_setting( 'rzp_onetime', 1 ),
				'configured'  => PKC_Razorpay::configured(),
				'test_mode'   => 'test' === PKC_Razorpay::mode(),
				'payments'    => $payments,
			)
		);
	}

	private static function work_rate_limit( $org ) {
		return PKC_Rate_Limit::hit( 'billing', (string) $org->id, 20, HOUR_IN_SECONDS );
	}

	public static function subscribe( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		if ( ! self::work_rate_limit( $org ) ) {
			return PKC_Rate_Limit::error();
		}
		$result = PKC_Billing::start_subscription( $org, absint( $r->get_param( 'plan_id' ) ) );
		return is_wp_error( $result ) ? $result : PKC_REST::ok( $result );
	}

	public static function verify_subscription( WP_REST_Request $r ) {
		$org    = PKC_REST::org();
		$result = PKC_Billing::verify_subscription(
			$org,
			sanitize_text_field( (string) $r->get_param( 'razorpay_payment_id' ) ),
			sanitize_text_field( (string) $r->get_param( 'razorpay_subscription_id' ) ),
			sanitize_text_field( (string) $r->get_param( 'razorpay_signature' ) )
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result['me'] = PKC_REST_Account::me_payload( PKC_Organisations::get( $org->id ) );
		return PKC_REST::ok( $result );
	}

	public static function order( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		if ( ! self::work_rate_limit( $org ) ) {
			return PKC_Rate_Limit::error();
		}
		$result = PKC_Billing::create_order( $org, absint( $r->get_param( 'plan_id' ) ), (string) $r->get_param( 'coupon' ) );
		return is_wp_error( $result ) ? $result : PKC_REST::ok( $result );
	}

	/** Check a coupon code before paying once. */
	public static function coupon( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		if ( ! PKC_Rate_Limit::hit( 'coupon', (string) $org->id, 30, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$plan = PKC_Billing::plan( absint( $r->get_param( 'plan_id' ) ) );
		if ( ! $plan || ! $plan->is_active ) {
			return new WP_Error( 'pkc_plan', __( 'This plan is not available.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$check = PKC_Coupons::check( (string) $r->get_param( 'coupon' ), (int) $plan->price_paise );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		return PKC_REST::ok(
			array(
				'code'     => $check['coupon']->code,
				'label'    => PKC_Coupons::label( $check['coupon'] ),
				'discount' => pkc_money( $check['discount'] ),
				'amount'   => pkc_money( $check['amount'] ),
				/* translators: 1: coupon label, 2: amount to pay */
				'message'  => sprintf( __( 'Coupon applied: %1$s. You pay %2$s.', 'pikacart' ), PKC_Coupons::label( $check['coupon'] ), pkc_money( $check['amount'] ) ),
			)
		);
	}

	public static function verify_order( WP_REST_Request $r ) {
		$org    = PKC_REST::org();
		$result = PKC_Billing::verify_order(
			$org,
			sanitize_text_field( (string) $r->get_param( 'razorpay_order_id' ) ),
			sanitize_text_field( (string) $r->get_param( 'razorpay_payment_id' ) ),
			sanitize_text_field( (string) $r->get_param( 'razorpay_signature' ) )
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result['me'] = PKC_REST_Account::me_payload( PKC_Organisations::get( $org->id ) );
		return PKC_REST::ok( $result );
	}

	public static function cancel() {
		$org    = PKC_REST::org();
		$result = PKC_Billing::cancel_autopay( $org );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result['me'] = PKC_REST_Account::me_payload( PKC_Organisations::get( $org->id ) );
		return PKC_REST::ok( $result );
	}

	/**
	 * Invoice data for one of this account's own payments.
	 */
	public static function invoice( WP_REST_Request $r ) {
		global $wpdb;
		$org = PKC_REST::org();
		$p   = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'payments' ) . " WHERE id = %d AND org_id = %d AND status = 'captured'", absint( $r['id'] ), $org->id )
		);
		if ( ! $p ) {
			return new WP_Error( 'pkc_not_found', __( 'Invoice not found.', 'pikacart' ), array( 'status' => 404 ) );
		}
		return PKC_REST::ok( array( 'invoice' => PKC_Billing::invoice_data( $p, $org ) ) );
	}
}
