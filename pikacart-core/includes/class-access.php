<?php
/**
 * Account states and the server-side lock.
 *
 * States: trial, active, expired, suspended, cancelled.
 * "cancelled" means autopay was cancelled but the paid period is still running.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Access {

	public static function labels() {
		return array(
			'trial'     => __( 'Trial', 'pikacart' ),
			'active'    => __( 'Active', 'pikacart' ),
			'expired'   => __( 'Expired', 'pikacart' ),
			'suspended' => __( 'Suspended', 'pikacart' ),
			'cancelled' => __( 'Cancelled', 'pikacart' ),
		);
	}

	public static function label( $status ) {
		$labels = self::labels();
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * Work out the correct state from dates and the subscription.
	 */
	public static function compute( $org ) {
		if ( 'suspended' === $org->status ) {
			return 'suspended';
		}
		$now        = time();
		$period_end = pkc_ts( $org->period_end );
		$sub        = PKC_Billing::latest_subscription( (int) $org->id );

		if ( $period_end > $now ) {
			return ( $sub && 'cancelled' === $sub->status ) ? 'cancelled' : 'active';
		}

		// Razorpay retries renewals; keep an autopay account active for a short grace period.
		if ( $period_end && $sub && in_array( $sub->status, array( 'active', 'pending' ), true ) ) {
			$grace = (int) pkc_setting( 'grace_hours', 48 ) * HOUR_IN_SECONDS;
			if ( $period_end + $grace > $now ) {
				return 'active';
			}
		}

		if ( pkc_ts( $org->trial_end ) > $now ) {
			return 'trial';
		}
		return 'expired';
	}

	/**
	 * Recalculate and save the state. Returns the fresh row.
	 */
	public static function refresh( $org ) {
		if ( ! $org ) {
			return $org;
		}
		$state = self::compute( $org );
		if ( $state !== $org->status ) {
			$old = $org->status;
			PKC_Organisations::update( $org->id, array( 'status' => $state ) );
			PKC_Activity_Log::add( 'status.changed', $org->id, $old . ' → ' . $state );
			if ( 'trial' === $old && 'expired' === $state ) {
				PKC_Emails::send_to_org( $org, 'trial_ended' );
			}
			$org->status = $state;
		}
		return $org;
	}

	/** Can create cards, download and print. */
	public static function can_work( $org ) {
		return $org && in_array( $org->status, array( 'trial', 'active', 'cancelled' ), true );
	}

	/** Exports carry a watermark during the trial. */
	public static function needs_watermark( $org ) {
		return $org && 'trial' === $org->status;
	}

	/**
	 * Use at the top of every protected REST action.
	 *
	 * @return true|WP_Error
	 */
	public static function require_work( $org ) {
		$org = self::refresh( $org );
		if ( self::can_work( $org ) ) {
			return true;
		}
		return new WP_Error(
			'pkc_locked',
			sprintf(
				/* translators: %s: price */
				__( 'Your free trial has ended. Subscribe for %s per month to continue creating, downloading and printing cards.', 'pikacart' ),
				pkc_money( PKC_Billing::default_plan_price() )
			),
			array( 'status' => 402 )
		);
	}
}
