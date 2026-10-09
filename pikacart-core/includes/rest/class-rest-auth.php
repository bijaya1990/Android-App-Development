<?php
/**
 * Public account endpoints: register, login, forgot and reset password.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Auth {

	public static function routes() {
		$map = array(
			'auth/register' => 'register',
			'auth/login'    => 'login',
			'auth/forgot'   => 'forgot',
			'auth/reset'    => 'reset',
		);
		foreach ( $map as $route => $method ) {
			register_rest_route(
				PKC_REST::NS,
				'/' . $route,
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, $method ),
					'permission_callback' => array( 'PKC_REST', 'public_nonce' ),
				)
			);
		}
	}

	private static function respond( $result ) {
		return is_wp_error( $result ) ? $result : PKC_REST::ok( $result );
	}

	public static function register( WP_REST_Request $r ) {
		return self::respond( PKC_Accounts::register( $r->get_params() ) );
	}

	public static function login( WP_REST_Request $r ) {
		$result = PKC_Accounts::login( $r->get_params() );
		if ( ! is_wp_error( $result ) ) {
			$redirect = (string) $r->get_param( 'redirect' );
			$redirect = $redirect ? wp_validate_redirect( esc_url_raw( $redirect ), '' ) : '';
			if ( $redirect && 0 === strpos( $redirect, pkc_url( 'app' ) ) ) {
				$result['redirect'] = $redirect;
			}
		}
		return self::respond( $result );
	}

	public static function forgot( WP_REST_Request $r ) {
		return self::respond( PKC_Accounts::forgot( $r->get_params() ) );
	}

	public static function reset( WP_REST_Request $r ) {
		return self::respond( PKC_Accounts::reset( $r->get_params() ) );
	}
}
