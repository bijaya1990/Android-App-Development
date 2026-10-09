<?php
/**
 * Simple rate limiting with transients (works on any shared hosting).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Rate_Limit {

	/**
	 * Count one attempt and say whether the limit is passed.
	 *
	 * @param string $bucket  Name, e.g. login.
	 * @param string $subject IP, email or both.
	 * @param int    $max     Attempts allowed in the window.
	 * @param int    $window  Window in seconds.
	 * @return bool True when the attempt is allowed.
	 */
	public static function hit( $bucket, $subject, $max, $window ) {
		$key  = 'pkc_rl_' . md5( $bucket . '|' . strtolower( (string) $subject ) );
		$data = get_transient( $key );
		$now  = time();
		if ( ! is_array( $data ) || $data['reset'] <= $now ) {
			$data = array(
				'count' => 0,
				'reset' => $now + $window,
			);
		}
		++$data['count'];
		set_transient( $key, $data, max( 1, $data['reset'] - $now ) );
		return $data['count'] <= $max;
	}

	/**
	 * Clear a bucket, e.g. after a successful login.
	 */
	public static function clear( $bucket, $subject ) {
		delete_transient( 'pkc_rl_' . md5( $bucket . '|' . strtolower( (string) $subject ) ) );
	}

	/**
	 * WP_Error for a blocked request.
	 */
	public static function error() {
		return new WP_Error( 'pkc_rate_limited', __( 'Too many attempts. Please wait a few minutes and try again.', 'pikacart' ), array( 'status' => 429 ) );
	}
}
