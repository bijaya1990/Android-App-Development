<?php
/**
 * Cart (multi-seller) and coupons.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_cart_get() {
	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), 'dm_cart', true );
		$ids = is_array( $ids ) ? $ids : array();
	} else {
		$raw = isset( $_COOKIE['dm_cart'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dm_cart'] ) ) : '';
		$ids = $raw ? explode( '-', $raw ) : array();
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

function dm_cart_set( $ids ) {
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	$ids = array_slice( $ids, 0, 50 );
	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'dm_cart', $ids );
	} elseif ( ! headers_sent() ) {
		setcookie( 'dm_cart', implode( '-', $ids ), time() + 30 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE['dm_cart'] = implode( '-', $ids );
	}
}

function dm_cart_count() {
	return count( dm_cart_get() );
}

function dm_cart_coupon() {
	if ( is_user_logged_in() ) {
		return (string) get_user_meta( get_current_user_id(), 'dm_cart_coupon', true );
	}
	return isset( $_COOKIE['dm_coupon'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dm_coupon'] ) ) : '';
}

function dm_cart_set_coupon( $code ) {
	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), 'dm_cart_coupon', $code );
	} elseif ( ! headers_sent() ) {
		setcookie( 'dm_coupon', $code, $code ? time() + DAY_IN_SECONDS : time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE['dm_coupon'] = $code;
	}
}

/* Merge guest cart into the account on login. */
add_action( 'wp_login', 'dm_cart_merge_on_login', 20, 2 );
function dm_cart_merge_on_login( $login, $user ) {
	$raw = isset( $_COOKIE['dm_cart'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dm_cart'] ) ) : '';
	if ( ! $raw ) {
		return;
	}
	$guest = array_filter( array_map( 'absint', explode( '-', $raw ) ) );
	$saved = get_user_meta( $user->ID, 'dm_cart', true );
	$saved = is_array( $saved ) ? $saved : array();
	update_user_meta( $user->ID, 'dm_cart', array_values( array_unique( array_merge( $saved, $guest ) ) ) );
	setcookie( 'dm_cart', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
}

/**
 * Add to cart with validation. Returns true or WP_Error.
 */
function dm_cart_add( $pid ) {
	if ( dm_is_service( $pid ) ) {
		return new WP_Error( 'service', __( 'This is a service — please enquire on WhatsApp instead of adding it to the cart.', 'digimarket' ) );
	}
	list( $ok, $reason ) = dm_can_purchase( $pid );
	if ( ! $ok ) {
		return new WP_Error( 'unavailable', $reason );
	}
	if ( is_user_logged_in() ) {
		if ( (int) get_post_field( 'post_author', $pid ) === get_current_user_id() ) {
			return new WP_Error( 'own', __( 'You cannot buy your own product.', 'digimarket' ) );
		}
		if ( dm_user_purchase( get_current_user_id(), $pid ) ) {
			return new WP_Error( 'owned', __( 'You already own this product — find it in My Purchases.', 'digimarket' ) );
		}
	}
	$cart   = dm_cart_get();
	$cart[] = (int) $pid;
	dm_cart_set( $cart );
	return true;
}

function dm_cart_remove( $pid ) {
	dm_cart_set( array_diff( dm_cart_get(), array( (int) $pid ) ) );
}

/**
 * Validate a coupon against cart lines. Returns coupon row or WP_Error.
 */
function dm_validate_coupon( $code, $lines ) {
	global $wpdb;
	$code = strtoupper( trim( (string) $code ) );
	if ( '' === $code ) {
		return new WP_Error( 'empty', __( 'Enter a coupon code.', 'digimarket' ) );
	}
	$c = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'coupons' ) . ' WHERE UPPER(code) = %s AND active = 1', $code ) );
	if ( ! $c ) {
		return new WP_Error( 'invalid', __( 'This coupon code is not valid.', 'digimarket' ) );
	}
	$today = current_time( 'Y-m-d' );
	if ( ( $c->valid_from && $c->valid_from > $today ) || ( $c->valid_until && $c->valid_until < $today ) ) {
		return new WP_Error( 'expired', __( 'This coupon has expired or is not active yet.', 'digimarket' ) );
	}
	if ( $c->usage_limit > 0 && $c->times_used >= $c->usage_limit ) {
		return new WP_Error( 'used', __( 'This coupon has reached its usage limit.', 'digimarket' ) );
	}
	if ( is_user_logged_in() ) {
		$uid = get_current_user_id();
		if ( ! empty( $c->first_order ) && (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dm_table( 'orders' ) . " WHERE buyer_id = %d AND payment_status IN ('paid','partially_refunded','refunded')", $uid ) ) > 0 ) {
			return new WP_Error( 'first', __( 'This coupon is only for your first order.', 'digimarket' ) );
		}
		if ( ! empty( $c->per_user_limit ) && (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dm_table( 'orders' ) . " WHERE buyer_id = %d AND UPPER(coupon_code) = %s AND payment_status IN ('paid','partially_refunded')", $uid, $code ) ) >= (int) $c->per_user_limit ) {
			return new WP_Error( 'peruser', __( 'You have already used this coupon.', 'digimarket' ) );
		}
	}
	$eligible = 0;
	foreach ( $lines as $l ) {
		if ( dm_coupon_applies_to( $c, $l ) ) {
			$eligible += $l['price'];
		}
	}
	if ( $eligible <= 0 ) {
		return new WP_Error( 'none', __( 'This coupon does not apply to items in your cart.', 'digimarket' ) );
	}
	if ( $c->min_order > 0 && $eligible < $c->min_order ) {
		/* translators: %s amount */
		return new WP_Error( 'min', sprintf( __( 'Minimum order for this coupon is %s.', 'digimarket' ), dm_money( $c->min_order ) ) );
	}
	return $c;
}

/**
 * Calculate cart lines and totals, including coupon distribution.
 */
function dm_cart_totals( $ids = null ) {
	$ids     = null === $ids ? dm_cart_get() : $ids;
	$lines   = array();
	$invalid = array();
	foreach ( $ids as $pid ) {
		list( $ok, $reason ) = dm_can_purchase( $pid );
		if ( ! $ok ) {
			$invalid[ $pid ] = $reason;
			continue;
		}
		$lines[ $pid ] = array(
			'pid'      => $pid,
			'title'    => get_the_title( $pid ),
			'seller'   => (int) get_post_field( 'post_author', $pid ),
			'list'     => dm_product_regular_price( $pid ),
			'price'    => dm_round( dm_product_price( $pid ) ),
			'discount' => 0,
			'final'    => dm_round( dm_product_price( $pid ) ),
		);
	}
	$subtotal = array_sum( wp_list_pluck( $lines, 'price' ) );
	$discount = 0;
	$coupon   = null;
	$code     = dm_cart_coupon();
	$coupon_error = '';
	if ( $code && $lines ) {
		$c = dm_validate_coupon( $code, $lines );
		if ( is_wp_error( $c ) ) {
			$coupon_error = $c->get_error_message();
		} else {
			$coupon   = $c;
			$eligible = array();
			foreach ( $lines as $pid => $l ) {
				if ( dm_coupon_applies_to( $c, $l ) ) {
					$eligible[ $pid ] = $l['price'];
				}
			}
			$base     = array_sum( $eligible );
			$discount = 'percent' === $c->discount_type ? $base * min( 100, (float) $c->discount_value ) / 100 : min( $base, (float) $c->discount_value );
			if ( ! empty( $c->max_discount ) && (float) $c->max_discount > 0 ) {
				$discount = min( $discount, (float) $c->max_discount );
			}
			$discount = dm_round( $discount );
			$left     = $discount;
			$keys     = array_keys( $eligible );
			foreach ( $keys as $n => $pid ) {
				$share = ( count( $keys ) - 1 === $n ) ? $left : dm_round( $discount * ( $eligible[ $pid ] / $base ) );
				$share = min( $share, $lines[ $pid ]['price'] );
				$left  = dm_round( $left - $share );
				$lines[ $pid ]['discount'] = $share;
				$lines[ $pid ]['final']    = dm_round( $lines[ $pid ]['price'] - $share );
			}
		}
	}
	return array(
		'lines'        => $lines,
		'invalid'      => $invalid,
		'subtotal'     => dm_round( $subtotal ),
		'discount'     => $discount,
		'total'        => dm_round( max( 0, $subtotal - $discount ) ),
		'coupon'       => $coupon,
		'coupon_error' => $coupon_error,
	);
}

/* -------------------------------------------------------------------------
 * Handlers
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_dm_cart_add', 'dm_ajax_cart_add' );
add_action( 'wp_ajax_nopriv_dm_cart_add', 'dm_ajax_cart_add' );
function dm_ajax_cart_add() {
	dm_ajax_check();
	$res = dm_cart_add( absint( $_POST['product_id'] ?? 0 ) );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => $res->get_error_message() ) );
	}
	wp_send_json_success( array( 'count' => dm_cart_count(), 'message' => __( 'Added to cart', 'digimarket' ) ) );
}

function dm_do_cart_add() {
	$pid = absint( $_POST['product_id'] ?? 0 );
	$res = dm_cart_add( $pid );
	$buy = ! empty( $_POST['buy_now'] );
	if ( is_wp_error( $res ) ) {
		dm_flash( 'error', $res->get_error_message() );
		dm_back();
	}
	if ( $buy ) {
		dm_redirect( is_user_logged_in() ? dm_url( 'checkout' ) : dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_url( 'checkout' ) ) ) ) );
	}
	dm_flash( 'success', __( 'Added to cart.', 'digimarket' ) );
	dm_back( dm_url( 'cart' ) );
}

function dm_do_cart_remove() {
	dm_cart_remove( absint( $_POST['product_id'] ?? 0 ) );
	dm_flash( 'info', __( 'Item removed from cart.', 'digimarket' ) );
	dm_redirect( dm_url( 'cart' ) );
}

function dm_do_apply_coupon() {
	$code   = strtoupper( sanitize_text_field( wp_unslash( $_POST['coupon'] ?? '' ) ) );
	$totals = dm_cart_totals();
	$c      = dm_validate_coupon( $code, $totals['lines'] );
	if ( is_wp_error( $c ) ) {
		dm_flash( 'error', $c->get_error_message() );
	} else {
		dm_cart_set_coupon( $code );
		dm_flash( 'success', __( 'Coupon applied.', 'digimarket' ) );
	}
	dm_back( dm_url( 'cart' ) );
}

function dm_do_remove_coupon() {
	dm_cart_set_coupon( '' );
	dm_back( dm_url( 'cart' ) );
}

/**
 * Whether a coupon covers a cart line (seller, category and product scope).
 */
function dm_coupon_applies_to( $c, $line ) {
	if ( $c->seller_id && (int) $c->seller_id !== (int) $line['seller'] ) {
		return false;
	}
	$pids = array_filter( array_map( 'absint', explode( ',', (string) ( $c->product_ids ?? '' ) ) ) );
	$cats = array_filter( array_map( 'absint', explode( ',', (string) ( $c->category_ids ?? '' ) ) ) );
	if ( ! $pids && ! $cats ) {
		return true;
	}
	if ( $pids && in_array( (int) $line['pid'], $pids, true ) ) {
		return true;
	}
	if ( $cats ) {
		$terms = wp_get_object_terms( (int) $line['pid'], 'dm_category', array( 'fields' => 'ids' ) );
		foreach ( (array) $terms as $t ) {
			if ( in_array( (int) $t, $cats, true ) || array_intersect( $cats, get_ancestors( (int) $t, 'dm_category' ) ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Best public coupon for a single product: array( code, price_after, coupon ) or null.
 */
function dm_best_coupon_for( $pid ) {
	global $wpdb;
	static $coupons = null;
	if ( null === $coupons ) {
		$today   = current_time( 'Y-m-d' );
		$coupons = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'coupons' ) . ' WHERE active = 1 AND is_public = 1 AND (valid_from IS NULL OR valid_from <= %s) AND (valid_until IS NULL OR valid_until >= %s) AND (usage_limit = 0 OR times_used < usage_limit)', $today, $today ) ); // phpcs:ignore
	}
	$price = dm_product_price( $pid );
	if ( $price <= 0 || ! $coupons ) {
		return null;
	}
	$line = array( 'pid' => $pid, 'seller' => (int) get_post_field( 'post_author', $pid ), 'price' => $price );
	$best = null;
	foreach ( $coupons as $c ) {
		if ( ! dm_coupon_applies_to( $c, $line ) || ( $c->min_order > 0 && $price < $c->min_order ) ) {
			continue;
		}
		$off = 'percent' === $c->discount_type ? $price * min( 100, (float) $c->discount_value ) / 100 : min( $price, (float) $c->discount_value );
		if ( (float) $c->max_discount > 0 ) {
			$off = min( $off, (float) $c->max_discount );
		}
		$after = dm_round( $price - $off );
		if ( null === $best || $after < $best[1] ) {
			$best = array( $c->code, $after, $c );
		}
	}
	return $best;
}

/* Shareable coupon links: /?coupon=DIWALI30 saves the code, then drops the parameter. */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['coupon'] ) || is_admin() ) { // phpcs:ignore
		return;
	}
	global $wpdb;
	$code = strtoupper( sanitize_text_field( wp_unslash( $_GET['coupon'] ) ) ); // phpcs:ignore
	$ok   = $code && $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'coupons' ) . ' WHERE UPPER(code) = %s AND active = 1', $code ) );
	if ( $ok ) {
		dm_cart_set_coupon( $code );
		/* translators: %s code */
		dm_flash( 'success', sprintf( __( 'Coupon %s saved — it will apply automatically in your cart.', 'digimarket' ), $code ) );
	} else {
		dm_flash( 'warning', __( 'That coupon is not active right now.', 'digimarket' ) );
	}
	dm_redirect( remove_query_arg( 'coupon', dm_current_url() ) );
}, 2 );
