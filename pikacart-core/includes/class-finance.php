<?php
/**
 * Money screens: expenses and the monthly account statement.
 * Dates are grouped by the site's time zone.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Finance {

	public static function expense_categories() {
		return array(
			'hosting'   => __( 'Hosting', 'pikacart' ),
			'domain'    => __( 'Domain', 'pikacart' ),
			'designer'  => __( 'Designer', 'pikacart' ),
			'marketing' => __( 'Marketing', 'pikacart' ),
			'software'  => __( 'Software and tools', 'pikacart' ),
			'fees'      => __( 'Payment gateway fees', 'pikacart' ),
			'salary'    => __( 'Salary', 'pikacart' ),
			'other'     => __( 'Other', 'pikacart' ),
		);
	}

	public static function add_expense( $in ) {
		global $wpdb;
		$date   = sanitize_text_field( (string) ( $in['date'] ?? '' ) );
		$amount = (int) round( (float) ( $in['amount'] ?? 0 ) * 100 );
		$cat    = sanitize_key( (string) ( $in['category'] ?? 'other' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please choose a date.', 'pikacart' ) );
		}
		if ( $amount <= 0 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter an amount above zero.', 'pikacart' ) );
		}
		$wpdb->insert(
			pkc_table( 'expenses' ),
			array(
				'spent_on'     => $date,
				'category'     => array_key_exists( $cat, self::expense_categories() ) ? $cat : 'other',
				'amount_paise' => $amount,
				'note'         => mb_substr( sanitize_text_field( (string) ( $in['note'] ?? '' ) ), 0, 500 ),
				'created_by'   => get_current_user_id(),
				'created_at'   => pkc_now(),
			)
		);
		$id = (int) $wpdb->insert_id;
		PKC_Activity_Log::add( 'expense.added', 0, pkc_money( $amount ) . ' · ' . $cat );
		return $id;
	}

	public static function delete_expense( $id ) {
		global $wpdb;
		$wpdb->delete( pkc_table( 'expenses' ), array( 'id' => absint( $id ) ) );
		PKC_Activity_Log::add( 'expense.deleted', 0, '#' . absint( $id ) );
	}

	public static function expenses( $from, $to ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'expenses' ) . ' WHERE spent_on BETWEEN %s AND %s ORDER BY spent_on DESC, id DESC', $from, $to ) );
	}

	/**
	 * Statement for one month: income, refunds, expenses and net, per day.
	 *
	 * @param string $ym YYYY-MM.
	 */
	public static function statement( $ym ) {
		global $wpdb;
		if ( ! preg_match( '/^(\d{4})-(\d{2})$/', $ym, $m ) ) {
			$ym = wp_date( 'Y-m' );
			preg_match( '/^(\d{4})-(\d{2})$/', $ym, $m );
		}
		$tz    = wp_timezone();
		$start = new DateTime( $ym . '-01 00:00:00', $tz );
		$end   = ( clone $start )->modify( 'first day of next month' );
		$days  = (int) $start->format( 't' );
		$utc   = new DateTimeZone( 'UTC' );
		$from  = ( clone $start )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		$to    = ( clone $end )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );

		$rows = array();
		for ( $d = 1; $d <= $days; $d++ ) {
			$key          = sprintf( '%s-%02d', $ym, $d );
			$rows[ $key ] = array(
				'date'     => $key,
				'income'   => 0,
				'refunds'  => 0,
				'expenses' => 0,
				'count'    => 0,
			);
		}
		$pays = $wpdb->get_results( $wpdb->prepare( 'SELECT amount_paise, status, created_at FROM ' . pkc_table( 'payments' ) . " WHERE status IN ('captured','refunded') AND created_at >= %s AND created_at < %s", $from, $to ) );
		foreach ( $pays as $p ) {
			$key = wp_date( 'Y-m-d', pkc_ts( $p->created_at ) );
			if ( ! isset( $rows[ $key ] ) ) {
				continue;
			}
			// A refunded payment counts as income on the day it was paid and as a refund.
			$rows[ $key ]['income'] += (int) $p->amount_paise;
			++$rows[ $key ]['count'];
			if ( 'refunded' === $p->status ) {
				$rows[ $key ]['refunds'] += (int) $p->amount_paise;
			}
		}
		foreach ( self::expenses( $start->format( 'Y-m-d' ), ( clone $end )->modify( '-1 day' )->format( 'Y-m-d' ) ) as $e ) {
			if ( isset( $rows[ $e->spent_on ] ) ) {
				$rows[ $e->spent_on ]['expenses'] += (int) $e->amount_paise;
			}
		}
		$tot = array(
			'income'   => 0,
			'refunds'  => 0,
			'expenses' => 0,
			'count'    => 0,
		);
		foreach ( $rows as $k => $r ) {
			$rows[ $k ]['net'] = $r['income'] - $r['refunds'] - $r['expenses'];
			foreach ( $tot as $t => $v ) {
				$tot[ $t ] += $r[ $t ];
			}
		}
		$tot['net'] = $tot['income'] - $tot['refunds'] - $tot['expenses'];
		return array(
			'month'  => $ym,
			'label'  => wp_date( 'F Y', $start->getTimestamp() ),
			'days'   => array_values( $rows ),
			'totals' => $tot,
		);
	}
}
