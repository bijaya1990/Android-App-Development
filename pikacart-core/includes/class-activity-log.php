<?php
/**
 * Audit trail: who did what and when.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Activity_Log {

	/**
	 * Record one action.
	 *
	 * @param string       $action  Short key, e.g. payment.success.
	 * @param int          $org_id  Organisation ID or 0.
	 * @param string|array $details Human readable text or data.
	 */
	public static function add( $action, $org_id = 0, $details = '' ) {
		global $wpdb;
		$wpdb->insert(
			pkc_table( 'activity_log' ),
			array(
				'org_id'     => (int) $org_id,
				'user_id'    => get_current_user_id(),
				'action'     => substr( sanitize_key( str_replace( '.', '_', $action ) ), 0, 64 ),
				'details'    => is_array( $details ) ? wp_json_encode( $details ) : (string) $details,
				'ip'         => pkc_ip(),
				'created_at' => pkc_now(),
			)
		);
	}

	public static function for_org( $org_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'activity_log' ) . ' WHERE org_id = %d ORDER BY id DESC LIMIT %d', $org_id, $limit )
		);
	}

	/**
	 * Readable label for an action key.
	 */
	public static function label( $action ) {
		$labels = array(
			'account_registered'   => __( 'Account registered', 'pikacart' ),
			'account_login'        => __( 'Logged in', 'pikacart' ),
			'email_verified'       => __( 'Email verified', 'pikacart' ),
			'password_changed'     => __( 'Password changed', 'pikacart' ),
			'password_reset'       => __( 'Password reset', 'pikacart' ),
			'profile_updated'      => __( 'Profile updated', 'pikacart' ),
			'org_updated'          => __( 'Organisation details updated', 'pikacart' ),
			'onboarding_completed' => __( 'Onboarding completed', 'pikacart' ),
			'payment_success'      => __( 'Payment successful', 'pikacart' ),
			'payment_failed'       => __( 'Payment failed', 'pikacart' ),
			'subscription_created' => __( 'Autopay started', 'pikacart' ),
			'subscription_status'  => __( 'Autopay status changed', 'pikacart' ),
			'subscription_cancel'  => __( 'Autopay cancelled', 'pikacart' ),
			'status_changed'       => __( 'Account status changed', 'pikacart' ),
			'account_suspended'    => __( 'Account suspended', 'pikacart' ),
			'account_reactivated'  => __( 'Account reactivated', 'pikacart' ),
			'trial_extended'       => __( 'Trial extended', 'pikacart' ),
			'free_days'            => __( 'Free days granted', 'pikacart' ),
			'plan_end_changed'     => __( 'Plan end date changed', 'pikacart' ),
			'deletion_requested'   => __( 'Account deletion requested', 'pikacart' ),
			'account_deleted'      => __( 'Account deleted', 'pikacart' ),
			'settings_saved'       => __( 'Settings saved', 'pikacart' ),
			'plans_saved'          => __( 'Plans saved', 'pikacart' ),
			'reset_link_sent'      => __( 'Password reset link sent', 'pikacart' ),
		);
		return isset( $labels[ $action ] ) ? $labels[ $action ] : ucfirst( str_replace( '_', ' ', $action ) );
	}
}
