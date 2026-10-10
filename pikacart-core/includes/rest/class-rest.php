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

	/**
	 * Pikacart API answers are private and change all the time: tell every cache
	 * (browser, LiteSpeed server cache, plugins) never to store them.
	 */
	public static function no_cache( $response, $server, $request ) {
		if ( 0 === strpos( $request->get_route(), '/' . self::NS . '/' ) && 0 !== strpos( $request->get_route(), '/' . self::NS . '/public/' ) && $response instanceof WP_HTTP_Response ) {
			$response->header( 'Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private' );
			$response->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
			$response->header( 'Pragma', 'no-cache' );
		}
		return $response;
	}

	public static function register_routes() {
		PKC_REST_Auth::routes();
		PKC_REST_Account::routes();
		PKC_REST_Billing::routes();
		PKC_REST_Public::routes();
		PKC_REST_Support::routes();
		PKC_REST_Cards::routes();
		PKC_REST_Members::routes();
		PKC_REST_Designs::routes();
	}

	/**
	 * Permission: a logged-in organisation that is not suspended.
	 * Data is always looked up from the logged-in user, never from an ID in the request.
	 */
	public static function can_org( $request = null ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'pkc_auth', __( 'Please log in again.', 'pikacart' ), array( 'status' => 401 ) );
		}
		$org = PKC_Organisations::current();
		if ( ! $org ) {
			return new WP_Error( 'pkc_auth', __( 'No organisation is linked to this login.', 'pikacart' ), array( 'status' => 403 ) );
		}
		if ( PKC_Organisations::viewing_as() ) {
			// Support view is read-only, except for making preview files.
			$route = $request instanceof WP_REST_Request ? $request->get_route() : '';
			$safe  = (bool) preg_match( '#/(downloads/count|projects/\d+/members/fetch)$#', $route );
			if ( $request instanceof WP_REST_Request && 'GET' !== $request->get_method() && ! $safe ) {
				return new WP_Error( 'pkc_view_only', __( 'You are viewing this account as support. Changes are switched off.', 'pikacart' ), array( 'status' => 403 ) );
			}
			return true;
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
