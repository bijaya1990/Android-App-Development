<?php
/**
 * Coupons for one-time payments: percentage or flat discount, expiry date and
 * usage limit. A use is counted only when the payment is captured.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Coupons {

	/** Razorpay's smallest allowed amount. */
	const MIN_PAISE = 100;

	public static function normalize( $code ) {
		return strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $code ) );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'coupons' ) . ' WHERE id = %d', $id ) );
	}

	public static function find( $code ) {
		global $wpdb;
		$code = self::normalize( $code );
		if ( '' === $code ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'coupons' ) . ' WHERE code = %s', $code ) );
	}

	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'coupons' ) . ' ORDER BY id DESC' );
	}

	/** Discount in paise for a price. */
	public static function discount( $coupon, $price_paise ) {
		$price = (int) $price_paise;
		$off   = 'flat' === $coupon->type ? (int) $coupon->value : (int) floor( $price * min( 100, (int) $coupon->value ) / 100 );
		return max( 0, min( $off, $price - self::MIN_PAISE ) );
	}

	/**
	 * Check a code for a price.
	 *
	 * @return array|WP_Error [ 'coupon' => row, 'discount' => paise, 'amount' => paise ]
	 */
	public static function check( $code, $price_paise ) {
		$c = self::find( $code );
		if ( ! $c || ! $c->is_active ) {
			return new WP_Error( 'pkc_coupon', __( 'This coupon code is not valid.', 'pikacart' ), array( 'status' => 400, 'field' => 'coupon' ) );
		}
		if ( $c->expires_at && pkc_ts( $c->expires_at ) < time() ) {
			return new WP_Error( 'pkc_coupon', __( 'This coupon has expired.', 'pikacart' ), array( 'status' => 400, 'field' => 'coupon' ) );
		}
		if ( (int) $c->usage_limit && (int) $c->used_count >= (int) $c->usage_limit ) {
			return new WP_Error( 'pkc_coupon', __( 'This coupon has been fully used.', 'pikacart' ), array( 'status' => 400, 'field' => 'coupon' ) );
		}
		$off = self::discount( $c, $price_paise );
		return array(
			'coupon'   => $c,
			'discount' => $off,
			'amount'   => (int) $price_paise - $off,
		);
	}

	/** Count one use (called once per captured payment). */
	public static function redeem( $coupon_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . pkc_table( 'coupons' ) . ' SET used_count = used_count + 1 WHERE id = %d', $coupon_id ) );
	}

	public static function label( $c ) {
		return 'flat' === $c->type
			/* translators: %s: amount */
			? sprintf( __( '%s off', 'pikacart' ), pkc_money( (int) $c->value ) )
			/* translators: %d: percent */
			: sprintf( __( '%d%% off', 'pikacart' ), (int) $c->value );
	}

	/**
	 * Create or update from the admin form.
	 */
	public static function save( $in, $id = 0 ) {
		global $wpdb;
		$code = self::normalize( $in['code'] ?? '' );
		if ( strlen( $code ) < 3 || strlen( $code ) > 40 ) {
			return new WP_Error( 'pkc_invalid', __( 'The code must be 3 to 40 letters or numbers.', 'pikacart' ) );
		}
		$type  = 'flat' === ( $in['type'] ?? '' ) ? 'flat' : 'percent';
		$value = 'flat' === $type ? (int) round( (float) ( $in['value'] ?? 0 ) * 100 ) : (int) ( $in['value'] ?? 0 );
		if ( $value <= 0 || ( 'percent' === $type && $value > 100 ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Enter a discount between 1 and 100 percent, or a flat amount above zero.', 'pikacart' ) );
		}
		$other = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'coupons' ) . ' WHERE code = %s AND id <> %d', $code, $id ) );
		if ( $other ) {
			return new WP_Error( 'pkc_invalid', __( 'This code already exists.', 'pikacart' ) );
		}
		$expires = '';
		if ( ! empty( $in['expires'] ) ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', sanitize_text_field( $in['expires'] ) . ' 23:59:59', wp_timezone() );
			if ( $dt ) {
				$dt->setTimezone( new DateTimeZone( 'UTC' ) );
				$expires = $dt->format( 'Y-m-d H:i:s' );
			}
		}
		$data = array(
			'code'        => $code,
			'type'        => $type,
			'value'       => $value,
			'expires_at'  => $expires ? $expires : null,
			'usage_limit' => absint( $in['limit'] ?? 0 ),
			'is_active'   => empty( $in['active'] ) ? 0 : 1,
		);
		if ( $id ) {
			$wpdb->update( pkc_table( 'coupons' ), $data, array( 'id' => $id ) );
		} else {
			$data['created_at'] = pkc_now();
			$wpdb->insert( pkc_table( 'coupons' ), $data );
			$id = (int) $wpdb->insert_id;
		}
		PKC_Activity_Log::add( 'coupon.saved', 0, $code );
		return $id;
	}
}
