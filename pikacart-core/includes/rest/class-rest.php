<?php
/**
 * REST API base: route registration and shared permission checks.
 * Namespace: pkc/v1. Logged-in calls are protected by the WordPress REST nonce.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST {

	const NS = 'pkc/v1';

	public static function register_routes() {
		PKC_REST_Auth::routes();
		PKC_REST_Account::routes();
		PKC_REST_Billing::routes();
		PKC_REST_Public::routes();
		PKC_REST_Support::routes();
	}

	/**
	 * Permission: a logged-in organisation that is not suspended.
	 * Data is always looked up from the logged-in user, never from an ID in the request.
	 */
	public static function can_org() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'pkc_auth', __( 'Please log in again.', 'pikacart' ), array( 'status' => 401 ) );
		}
		$org = PKC_Organisations::current();
		if ( ! $org ) {
			return new WP_Error( 'pkc_auth', __( 'No organisation is linked to this login.', 'pikacart' ), array( 'status' => 403 ) );
		}
		if ( 'suspended' === $org->status ) {
			return new WP_Error(
				'pkc_suspended',
				sprintf(
					/* translators: %s: support email */
					__( 'This account is suspended. Please contact %s.', 'pikacart' ),
					pkc_support_email()
				),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Public form endpoints must carry a valid REST nonce from our own page.
	 */
	public static function public_nonce( WP_REST_Request $request ) {
		$nonce = (string) $request->get_header( 'x_wp_nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'pkc_nonce', __( 'This page has expired. Please refresh and try again.', 'pikacart' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/** Current organisation, freshly refreshed. */
	public static function org() {
		return PKC_Access::refresh( PKC_Organisations::current() );
	}

	public static function ok( $data = array() ) {
		return rest_ensure_response( array_merge( array( 'ok' => true ), $data ) );
	}
}
