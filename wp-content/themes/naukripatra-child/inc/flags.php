<?php
/**
 * Feature flags.
 *
 * Every phase of the redesign is switchable. To turn a feature OFF on the live
 * site without touching theme code, add a constant to wp-config.php, e.g.:
 *
 *     define( 'NP_ENABLE_JOBS_CPT', false );
 *
 * This is the primary rollback lever: flip the flag, reload, done. No data is
 * deleted when a flag is off — the Jobs posts and their meta stay in the
 * database, they are simply not registered/rendered.
 *
 * @package Naukripatra
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a feature flag.
 *
 * @param string $flag    Flag name, e.g. 'JOBS_CPT'.
 * @param bool   $default Default when the constant is not defined.
 * @return bool
 */
function np_flag( $flag, $default = true ) {
	$constant = 'NP_ENABLE_' . strtoupper( $flag );
	$value    = defined( $constant ) ? constant( $constant ) : $default;

	/**
	 * Filter a Naukripatra feature flag.
	 *
	 * @param bool   $value Flag value.
	 * @param string $flag  Flag name.
	 */
	return (bool) apply_filters( 'np_flag', $value, $flag );
}
