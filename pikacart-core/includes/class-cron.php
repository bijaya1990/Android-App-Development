<?php
/**
 * Timed jobs: trial reminders, trial end, plan expiry reminders.
 * WordPress cron runs when someone visits the site; for exact timing add a
 * cPanel cron job as explained in the README.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Cron {

	const HOOK  = 'pkc_cron_tick';
	const BATCH = 50;

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'ensure_scheduled' ) );
	}

	public static function schedules( $schedules ) {
		$schedules['pkc_five_minutes'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 5 minutes (Pikacart)', 'pikacart' ),
		);
		return $schedules;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 60, 'pkc_five_minutes', self::HOOK );
		}
	}

	public static function ensure_scheduled() {
		self::schedule();
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	public static function run() {
		if ( ! PKC_Access::free_forever() ) {
			self::trial_warnings();
		}
		self::refresh_due();
		self::expiry_warnings();
	}

	/** "Trial ends in 30 minutes" email, once per account. */
	private static function trial_warnings() {
		global $wpdb;
		$minutes = max( 1, (int) pkc_setting( 'trial_warn_minutes', 30 ) );
		$rows    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'organisations' ) . " WHERE status = 'trial' AND trial_end > %s AND trial_end <= %s AND (meta IS NULL OR meta NOT LIKE %s) LIMIT %d",
				pkc_now(),
				pkc_now( $minutes * 60 ),
				'%"trial_warned":1%',
				self::BATCH
			)
		);
		foreach ( $rows as $org ) {
			$left = max( 1, (int) ceil( ( pkc_ts( $org->trial_end ) - time() ) / 60 ) );
			PKC_Emails::send_to_org(
				$org,
				'trial_ending',
				array(
					'minutes' => $left,
					'link'    => pkc_url( 'app', 'subscription' ),
				)
			);
			PKC_Organisations::set_meta( $org->id, 'trial_warned', 1 );
		}
	}

	/** Move ended trials and ended plans to their new state (sends "trial ended"). */
	private static function refresh_due() {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'organisations' ) . " WHERE (status = 'trial' AND trial_end <= %s) OR (status IN ('active','cancelled') AND period_end <= %s) LIMIT %d",
				pkc_now(),
				pkc_now(),
				self::BATCH
			)
		);
		foreach ( $rows as $org ) {
			PKC_Access::refresh( $org );
		}
	}

	/** "Plan ends in 3 days" email for accounts without working autopay. */
	private static function expiry_warnings() {
		global $wpdb;
		$days = max( 1, (int) pkc_setting( 'expiry_warn_days', 3 ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'organisations' ) . " WHERE status IN ('active','cancelled') AND period_end > %s AND period_end <= %s LIMIT %d",
				pkc_now(),
				pkc_now( $days * DAY_IN_SECONDS ),
				self::BATCH * 4
			)
		);
		foreach ( $rows as $org ) {
			if ( PKC_Organisations::meta( $org, 'expiry_warned_for' ) === $org->period_end ) {
				continue;
			}
			$sub = PKC_Billing::active_subscription( $org->id );
			if ( ! $sub ) {
				PKC_Notifications::add(
					$org->id,
					'plan',
					__( 'Your plan ends soon', 'pikacart' ),
					/* translators: %s: date */
					sprintf( __( 'Your paid plan ends on %s. Renew to keep downloading without the watermark.', 'pikacart' ), pkc_date( $org->period_end, get_option( 'date_format', 'j M Y' ) ) ),
					'subscription'
				);
				PKC_Emails::send_to_org(
					$org,
					'expiring_soon',
					array(
						'date' => pkc_date( $org->period_end, get_option( 'date_format', 'j M Y' ) ),
						'link' => pkc_url( 'app', 'subscription' ),
					)
				);
			}
			PKC_Organisations::set_meta( $org->id, 'expiry_warned_for', $org->period_end );
		}
	}
}
