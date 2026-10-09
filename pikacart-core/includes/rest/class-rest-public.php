<?php
/**
 * Public endpoints: Razorpay webhook and the contact form.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Public {

	public static function routes() {
		// Razorpay calls this; it is protected by the webhook signature instead of a login.
		register_rest_route(
			PKC_REST::NS,
			'/razorpay/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'PKC_Webhooks', 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			PKC_REST::NS,
			'/contact',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'contact' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Contact form: honeypot, time check and rate limit against spam.
	 */
	public static function contact( WP_REST_Request $r ) {
		$p = $r->get_params();
		if ( ! empty( $p['website'] ) ) {
			return PKC_REST::ok( array( 'message' => __( 'Thank you! We will reply soon.', 'pikacart' ) ) );
		}
		$stamp = explode( '.', (string) ( $p['stamp'] ?? '' ) );
		$ok    = 2 === count( $stamp ) && hash_equals( hash_hmac( 'sha256', $stamp[0], wp_salt( 'nonce' ) ), $stamp[1] ) && ( time() - (int) $stamp[0] ) >= 3;
		if ( ! $ok ) {
			return new WP_Error( 'pkc_invalid', __( 'Please take a moment to fill the form, then try again.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( ! PKC_Rate_Limit::hit( 'contact', pkc_ip(), 5, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$name    = sanitize_text_field( (string) ( $p['name'] ?? '' ) );
		$email   = sanitize_email( (string) ( $p['email'] ?? '' ) );
		$phone   = sanitize_text_field( (string) ( $p['phone'] ?? '' ) );
		$message = sanitize_textarea_field( (string) ( $p['message'] ?? '' ) );

		if ( mb_strlen( $name ) < 2 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter your name.', 'pikacart' ), array( 'status' => 400, 'field' => 'name' ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter a valid email address.', 'pikacart' ), array( 'status' => 400, 'field' => 'email' ) );
		}
		if ( mb_strlen( $message ) < 5 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please write your message.', 'pikacart' ), array( 'status' => 400, 'field' => 'message' ) );
		}

		$sent = wp_mail(
			pkc_support_email(),
			/* translators: %s: sender name */
			sprintf( __( 'Website enquiry from %s', 'pikacart' ), $name ),
			sprintf( "%s: %s\n%s: %s\n%s: %s\n\n%s", __( 'Name', 'pikacart' ), $name, __( 'Email', 'pikacart' ), $email, __( 'Phone', 'pikacart' ), $phone, $message ),
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);
		if ( ! $sent ) {
			return new WP_Error(
				'pkc_mail',
				sprintf(
					/* translators: %s: support email */
					__( 'Sorry, the message could not be sent. Please email us at %s.', 'pikacart' ),
					pkc_support_email()
				),
				array( 'status' => 500 )
			);
		}
		return PKC_REST::ok( array( 'message' => __( 'Thank you! We will reply soon.', 'pikacart' ) ) );
	}
}
