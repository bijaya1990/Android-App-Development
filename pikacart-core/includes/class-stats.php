<?php
/**
 * Numbers and chart data for the Super Admin dashboard.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Stats {

	/**
	 * UTC MySQL start of "today", "this month", "this year" in the site time zone.
	 */
	private static function local_start( $format ) {
		$tz    = wp_timezone();
		$local = new DateTime( 'now', $tz );
		switch ( $format ) {
			case 'day':
				$local->setTime( 0, 0 );
				break;
			case 'month':
				$local->setDate( (int) $local->format( 'Y' ), (int) $local->format( 'n' ), 1 )->setTime( 0, 0 );
				break;
			case 'year':
				$local->setDate( (int) $local->format( 'Y' ), 1, 1 )->setTime( 0, 0 );
				break;
		}
		$local->setTimezone( new DateTimeZone( 'UTC' ) );
		return $local->format( 'Y-m-d H:i:s' );
	}

	public static function summary() {
		global $wpdb;
		$pay  = pkc_table( 'payments' );
		$orgs = pkc_table( 'organisations' );

		$money = function ( $since = null ) use ( $wpdb, $pay ) {
			if ( $since ) {
				return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount_paise),0) FROM $pay WHERE status = 'captured' AND created_at >= %s", $since ) );
			}
			return (int) $wpdb->get_var( "SELECT COALESCE(SUM(amount_paise),0) FROM $pay WHERE status = 'captured'" );
		};

		$status_counts = array_fill_keys( array_keys( PKC_Access::labels() ), 0 );
		foreach ( $wpdb->get_results( "SELECT status, COUNT(*) AS n FROM $orgs GROUP BY status" ) as $row ) {
			$status_counts[ $row->status ] = (int) $row->n;
		}
		$total = array_sum( $status_counts );

		$ended_trials = PKC_Access::free_forever() ? $total : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $orgs WHERE trial_end <= %s", pkc_now() ) );
		$paid_orgs    = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT org_id) FROM $pay WHERE status = 'captured'" );
		$conversion   = $ended_trials ? round( $paid_orgs * 100 / $ended_trials, 1 ) : 0;

		// Monthly recurring revenue from accounts with a running paid plan, normalised to 30 days.
		$mrr_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.price_paise, p.period_days FROM $orgs o JOIN " . pkc_table( 'plans' ) . " p ON p.id = o.plan_id WHERE o.status IN ('active','cancelled') AND o.period_end > %s",
				pkc_now()
			)
		);
		$mrr = 0;
		foreach ( $mrr_rows as $r ) {
			$mrr += (int) round( $r->price_paise * 30 / max( 1, (int) $r->period_days ) );
		}

		return array(
			'money_today'     => $money( self::local_start( 'day' ) ),
			'money_month'     => $money( self::local_start( 'month' ) ),
			'money_year'      => $money( self::local_start( 'year' ) ),
			'money_all'       => $money(),
			'accounts_total'  => $total,
			'new_today'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $orgs WHERE created_at >= %s", self::local_start( 'day' ) ) ),
			'new_month'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $orgs WHERE created_at >= %s", self::local_start( 'month' ) ) ),
			'status'          => $status_counts,
			'conversion'      => $conversion,
			'mrr'             => $mrr,
			'failed_month'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $pay WHERE status = 'failed' AND created_at >= %s", self::local_start( 'month' ) ) ),
			'renewals_7d'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $orgs WHERE status IN ('active','cancelled') AND period_end > %s AND period_end <= %s", pkc_now(), pkc_now( 7 * DAY_IN_SECONDS ) ) ),
			'cards'           => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'members' ) . ' WHERE deleted_at IS NULL' ),
			'downloads'       => (int) get_option( 'pkc_download_count', 0 ),
			'design_requests' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'design_requests' ) . " WHERE status IN ('new','in_progress')" ),
			'unread_tickets'  => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'tickets' ) . ' WHERE is_read_admin = 0' ),
		);
	}

	/**
	 * Series of the last N days in the site time zone: revenue and sign-ups.
	 */
	public static function daily( $days = 30 ) {
		global $wpdb;
		$tz     = wp_timezone();
		$offset = $tz->getOffset( new DateTime( 'now', $tz ) );
		$since  = gmdate( 'Y-m-d H:i:s', strtotime( wp_date( 'Y-m-d 00:00:00', time() - ( $days - 1 ) * DAY_IN_SECONDS ) ) - $offset );

		$labels  = array();
		$revenue = array();
		$signups = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$key             = wp_date( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
			$labels[ $key ]  = wp_date( 'j M', time() - $i * DAY_IN_SECONDS );
			$revenue[ $key ] = 0;
			$signups[ $key ] = 0;
		}

		$pays = $wpdb->get_results( $wpdb->prepare( 'SELECT amount_paise, created_at FROM ' . pkc_table( 'payments' ) . " WHERE status = 'captured' AND created_at >= %s", $since ) );
		foreach ( $pays as $p ) {
			$key = wp_date( 'Y-m-d', pkc_ts( $p->created_at ) );
			if ( isset( $revenue[ $key ] ) ) {
				$revenue[ $key ] += (int) $p->amount_paise / 100;
			}
		}
		$orgs = $wpdb->get_results( $wpdb->prepare( 'SELECT created_at FROM ' . pkc_table( 'organisations' ) . ' WHERE created_at >= %s', $since ) );
		foreach ( $orgs as $o ) {
			$key = wp_date( 'Y-m-d', pkc_ts( $o->created_at ) );
			if ( isset( $signups[ $key ] ) ) {
				++$signups[ $key ];
			}
		}
		return array(
			'labels'  => array_values( $labels ),
			'revenue' => array_values( $revenue ),
			'signups' => array_values( $signups ),
		);
	}

	/** Revenue for the last 12 months. */
	public static function monthly() {
		global $wpdb;
		$labels = array();
		$values = array();
		$base   = new DateTime( 'first day of this month', wp_timezone() );
		for ( $i = 11; $i >= 0; $i-- ) {
			$d                        = ( clone $base )->modify( "-$i months" );
			$labels[ $d->format( 'Y-m' ) ] = wp_date( 'M y', $d->getTimestamp() );
			$values[ $d->format( 'Y-m' ) ] = 0;
		}
		$since = ( clone $base )->modify( '-11 months' )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		$pays  = $wpdb->get_results( $wpdb->prepare( 'SELECT amount_paise, created_at FROM ' . pkc_table( 'payments' ) . " WHERE status = 'captured' AND created_at >= %s", $since ) );
		foreach ( $pays as $p ) {
			$key = wp_date( 'Y-m', pkc_ts( $p->created_at ) );
			if ( isset( $values[ $key ] ) ) {
				$values[ $key ] += (int) $p->amount_paise / 100;
			}
		}
		return array(
			'labels' => array_values( $labels ),
			'values' => array_values( $values ),
		);
	}

	public static function by_category() {
		global $wpdb;
		$rows   = $wpdb->get_results(
			'SELECT COALESCE(c.name, \'\') AS name, COUNT(o.id) AS n FROM ' . pkc_table( 'organisations' ) . ' o LEFT JOIN ' . pkc_table( 'categories' ) . ' c ON c.id = o.category_id GROUP BY o.category_id, c.name ORDER BY n DESC'
		);
		$labels = array();
		$values = array();
		foreach ( $rows as $r ) {
			$labels[] = '' !== $r->name ? $r->name : __( 'Not chosen yet', 'pikacart' );
			$values[] = (int) $r->n;
		}
		return array(
			'labels' => $labels,
			'values' => $values,
		);
	}
}
