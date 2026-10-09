<?php
/**
 * Small Razorpay API client using the WordPress HTTP API (no Composer).
 * Secrets stay on the server and are never sent to the browser.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Razorpay {

	const API = 'https://api.razorpay.com/v1/';

	public static function mode() {
		return 'live' === pkc_setting( 'rzp_mode', 'test' ) ? 'live' : 'test';
	}

	public static function key_id( $mode = null ) {
		$mode = $mode ? $mode : self::mode();
		return trim( (string) pkc_setting( 'rzp_key_' . $mode, '' ) );
	}

	private static function secret( $mode = null ) {
		$mode = $mode ? $mode : self::mode();
		return trim( (string) pkc_setting( 'rzp_secret_' . $mode, '' ) );
	}

	public static function configured( $mode = null ) {
		return '' !== self::key_id( $mode ) && '' !== self::secret( $mode );
	}

	public static function webhook_url() {
		return rest_url( 'pkc/v1/razorpay/webhook' );
	}

	/**
	 * Call the Razorpay API.
	 *
	 * @return array|WP_Error Decoded response.
	 */
	public static function request( $method, $path, $body = array(), $mode = null ) {
		$mode = $mode ? $mode : self::mode();
		if ( ! self::configured( $mode ) ) {
			return new WP_Error( 'pkc_rzp_setup', __( 'Online payments are not set up yet. Please contact support.', 'pikacart' ), array( 'status' => 503 ) );
		}
		$args = array(
			'method'  => $method,
			'timeout' => 25,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( self::key_id( $mode ) . ':' . self::secret( $mode ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				'Content-Type'  => 'application/json',
			),
		);
		if ( 'GET' !== $method && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}
		$url = self::API . ltrim( $path, '/' );
		if ( 'GET' === $method && ! empty( $body ) ) {
			$url = add_query_arg( $body, $url );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'pkc_rzp_http', __( 'Could not reach Razorpay. Please try again in a minute.', 'pikacart' ), array( 'status' => 502 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$msg = isset( $data['error']['description'] ) ? $data['error']['description'] : __( 'Razorpay returned an error.', 'pikacart' );
			return new WP_Error( 'pkc_rzp_api', $msg, array( 'status' => 502 ) );
		}
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Check the signature Razorpay Checkout returns to the browser.
	 */
	public static function verify_signature( $payload, $signature, $mode = null ) {
		$secret = self::secret( $mode );
		if ( '' === $secret || '' === (string) $signature ) {
			return false;
		}
		return hash_equals( hash_hmac( 'sha256', $payload, $secret ), (string) $signature );
	}

	/**
	 * Check a webhook's X-Razorpay-Signature header against the raw body.
	 */
	public static function verify_webhook( $raw_body, $signature ) {
		$secret = trim( (string) pkc_setting( 'rzp_webhook_secret', '' ) );
		if ( '' === $secret || '' === (string) $signature ) {
			return false;
		}
		return hash_equals( hash_hmac( 'sha256', $raw_body, $secret ), (string) $signature );
	}
}
