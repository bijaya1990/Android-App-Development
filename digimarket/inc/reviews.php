<?php
/**
 * Reviews (verified purchasers only) and product reports.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_get_reviews( $pid, $limit = 20 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . " WHERE product_id = %d AND status = 'approved' ORDER BY id DESC LIMIT %d", $pid, $limit ) );
}

function dm_user_review( $uid, $pid ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . ' WHERE buyer_id = %d AND product_id = %d', $uid, $pid ) );
}

function dm_do_submit_review() {
	dm_require_login();
	global $wpdb;
	$uid    = get_current_user_id();
	$pid    = absint( $_POST['product_id'] ?? 0 );
	$rating = max( 1, min( 5, absint( $_POST['rating'] ?? 5 ) ) );
	$text   = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) );
	if ( ! dm_user_purchase( $uid, $pid ) ) {
		dm_flash( 'error', __( 'Only verified buyers of this product can leave a review.', 'digimarket' ) );
		dm_back();
	}
	$existing = dm_user_review( $uid, $pid );
	if ( $existing ) {
		$wpdb->update( dm_table( 'reviews' ), array( 'rating' => $rating, 'comment' => $text, 'updated_at' => dm_now() ), array( 'id' => $existing->id ) );
		dm_flash( 'success', __( 'Your review was updated.', 'digimarket' ) );
	} else {
		$wpdb->insert(
			dm_table( 'reviews' ),
			array(
				'product_id' => $pid,
				'seller_id'  => (int) get_post_field( 'post_author', $pid ),
				'buyer_id'   => $uid,
				'rating'     => $rating,
				'comment'    => $text,
				'status'     => 'approved',
				'created_at' => dm_now(),
			)
		);
		/* translators: %s product */
		dm_notify( (int) get_post_field( 'post_author', $pid ), sprintf( __( 'New %1$d★ review on %2$s', 'digimarket' ), $rating, get_the_title( $pid ) ), dm_url( 'dashboard', 'reviews' ) );
		dm_flash( 'success', __( 'Thanks for your review!', 'digimarket' ) );
	}
	dm_refresh_product_rating( $pid );
	dm_redirect( get_permalink( $pid ) . '#reviews' );
}

function dm_do_delete_review() {
	dm_require_login();
	global $wpdb;
	$id  = absint( $_POST['review_id'] ?? 0 );
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . ' WHERE id = %d', $id ) );
	if ( $row && (int) $row->buyer_id === get_current_user_id() ) {
		$wpdb->delete( dm_table( 'reviews' ), array( 'id' => $id ) );
		dm_refresh_product_rating( $row->product_id );
		dm_flash( 'success', __( 'Review deleted.', 'digimarket' ) );
	}
	dm_back( dm_url( 'account', 'reviews' ) );
}

function dm_do_report_product() {
	dm_require_login();
	global $wpdb;
	$pid    = absint( $_POST['product_id'] ?? 0 );
	$reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
	if ( ! $pid || '' === $reason ) {
		dm_flash( 'error', __( 'Please describe the problem.', 'digimarket' ) );
		dm_back();
	}
	$wpdb->insert( dm_table( 'reports' ), array( 'product_id' => $pid, 'user_id' => get_current_user_id(), 'reason' => $reason, 'status' => 'open', 'created_at' => dm_now() ) );
	/* translators: %s product */
	dm_notify_admins( sprintf( __( 'Product reported: %s', 'digimarket' ), get_the_title( $pid ) ), admin_url( 'admin.php?page=dm-moderation' ) );
	dm_flash( 'success', __( 'Thanks — our team will review this product.', 'digimarket' ) );
	dm_back();
}
