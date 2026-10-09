<?php
/**
 * Account states and the server-side lock.
 *
 * States: free, trial, active, expired, suspended, cancelled.
 * - free:      lifetime free plan. Everything works; exports carry the
 *              "Made with www.pikacart.in" watermark. (Default mode.)
 * - trial:     time-limited trial (only when Settings > Free plan mode = trial).
 * - expired:   trial ended (trial mode only).
 * - active:    paid plan running, no watermark.
 * - cancelled: autopay cancelled but the paid period is still running.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Access {

	public static function labels() {
		return array(
			'free'      => __( 'Free', 'pikacart' ),
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

	/** Lifetime free plan (default) or the old time-limited trial. */
	public static function free_forever() {
		return 'trial' !== pkc_setting( 'free_mode', 'forever' );
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

		if ( self::free_forever() ) {
			return 'free';
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
			if ( in_array( $old, array( 'active', 'cancelled' ), true ) && 'free' === $state ) {
				PKC_Notifications::add(
					$org->id,
					'plan',
					__( 'Your paid plan has ended', 'pikacart' ),
					__( 'You are back on the Free plan. Downloads now carry the Pikacart watermark. Renew anytime to remove it.', 'pikacart' ),
					'subscription'
				);
			}
			$org->status = $state;
		}
		return $org;
	}

	/**
	 * Update every account after the free plan mode changes (or on upgrade).
	 */
	public static function recompute_all() {
		global $wpdb;
		$ids = $wpdb->get_col( 'SELECT id FROM ' . pkc_table( 'organisations' ) . " WHERE status IN ('free','trial','expired')" );
		foreach ( $ids as $id ) {
			$org = PKC_Organisations::get( (int) $id );
			if ( ! $org ) {
				continue;
			}
			$state = self::compute( $org );
			if ( $state !== $org->status ) {
				// Quiet update: no "trial ended" emails for a settings change.
				PKC_Organisations::update( $org->id, array( 'status' => $state ) );
			}
		}
	}

	/** Can create cards, download and print. */
	public static function can_work( $org ) {
		return $org && in_array( $org->status, array( 'free', 'trial', 'active', 'cancelled' ), true );
	}

	/** Exports carry the watermark on the free plan and during a trial. */
	public static function needs_watermark( $org ) {
		return $org && in_array( $org->status, array( 'free', 'trial' ), true );
	}

	public static function is_paid( $org ) {
		return $org && in_array( $org->status, array( 'active', 'cancelled' ), true );
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

	/** Watermark text drawn on free exports. */
	public static function watermark_text() {
		$text = trim( (string) pkc_setting( 'watermark_text', 'Made with www.pikacart.in' ) );
		return '' !== $text ? $text : 'Made with www.pikacart.in';
	}
}
