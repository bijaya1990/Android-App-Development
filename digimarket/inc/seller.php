<?php
/**
 * Seller onboarding, shop settings, KYC, product management, orders, refunds.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Onboarding
 * ---------------------------------------------------------------------- */

/**
 * Which onboarding step the current user is on (1–4, or 5 = done).
 */
function dm_onboarding_step( $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	if ( ! $uid ) {
		return 1;
	}
	if ( ! get_user_meta( $uid, 'dm_shop_slug', true ) ) {
		return 2;
	}
	if ( ! get_user_meta( $uid, 'dm_kyc_submitted', true ) ) {
		return 3;
	}
	if ( in_array( dm_seller_status( $uid ), array( '', 'draft' ), true ) ) {
		return 4;
	}
	return 5;
}

add_action( 'wp_ajax_dm_check_slug', 'dm_ajax_check_slug' );
add_action( 'wp_ajax_nopriv_dm_check_slug', 'dm_ajax_check_slug' );
function dm_ajax_check_slug() {
	dm_ajax_check();
	$raw  = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
	$slug = sanitize_title( $raw );
	wp_send_json_success(
		array(
			'slug'      => $slug,
			'available' => dm_slug_available( $slug, get_current_user_id() ),
			'url'       => dm_pretty_permalinks() ? home_url( '/store/' . $slug . '/' ) : add_query_arg( 'dm_store', $slug, home_url( '/' ) ),
		)
	);
}

function dm_do_seller_shop() {
	dm_require_login();
	$uid      = get_current_user_id();
	$is_new   = ! get_user_meta( $uid, 'dm_shop_slug', true );
	$name     = sanitize_text_field( wp_unslash( $_POST['shop_name'] ?? '' ) );
	$slug     = sanitize_title( wp_unslash( $_POST['shop_slug'] ?? '' ) );
	$slug     = $slug ? $slug : sanitize_title( $name );
	$old_slug = get_user_meta( $uid, 'dm_shop_slug', true );
	if ( '' === $name || mb_strlen( $name ) > 60 ) {
		dm_flash( 'error', __( 'Shop name is required (max 60 characters).', 'digimarket' ) );
		dm_back();
	}
	if ( $slug !== $old_slug ) {
		if ( ! dm_slug_available( $slug, $uid ) ) {
			dm_flash( 'error', __( 'That shop URL is taken or reserved. Try another.', 'digimarket' ) );
			dm_back();
		}
		$created = (int) get_user_meta( $uid, 'dm_shop_created', true );
		if ( $old_slug && $created && $created < time() - 7 * DAY_IN_SECONDS ) {
			// After the 7-day grace period we keep the old slug as a redirect.
			$old = (array) get_user_meta( $uid, 'dm_old_slugs', true );
			$old[] = $old_slug;
			update_user_meta( $uid, 'dm_old_slugs', array_values( array_unique( array_filter( $old ) ) ) );
		}
		update_user_meta( $uid, 'dm_shop_slug', $slug );
	}
	update_user_meta( $uid, 'dm_shop_name', $name );
	update_user_meta( $uid, 'dm_shop_bio', sanitize_textarea_field( wp_unslash( $_POST['shop_bio'] ?? '' ) ) );
	update_user_meta( $uid, 'dm_shop_category', absint( $_POST['shop_category'] ?? 0 ) );
	if ( isset( $_POST['shop_accent'] ) ) {
		update_user_meta( $uid, 'dm_shop_accent', sanitize_hex_color( wp_unslash( $_POST['shop_accent'] ) ) );
	}
	foreach ( array( 'instagram', 'youtube', 'website', 'twitter' ) as $net ) {
		if ( isset( $_POST[ 'social_' . $net ] ) ) {
			update_user_meta( $uid, 'dm_social_' . $net, esc_url_raw( wp_unslash( $_POST[ 'social_' . $net ] ) ) );
		}
	}
	foreach ( array( 'policy_refund', 'policy_delivery' ) as $p ) {
		if ( isset( $_POST[ $p ] ) ) {
			update_user_meta( $uid, 'dm_' . $p, sanitize_textarea_field( wp_unslash( $_POST[ $p ] ) ) );
		}
	}
	foreach ( array( 'shop_logo', 'shop_banner' ) as $img_field ) {
		if ( ! empty( $_FILES[ $img_field ]['name'] ) ) {
			$id = dm_upload_image( $img_field, $uid );
			if ( is_wp_error( $id ) ) {
				dm_flash( 'error', $id->get_error_message() );
			} elseif ( $id ) {
				update_user_meta( $uid, 'dm_' . $img_field, $id );
			}
		}
		if ( ! empty( $_POST[ 'remove_' . $img_field ] ) ) {
			delete_user_meta( $uid, 'dm_' . $img_field );
		}
	}
	if ( $is_new ) {
		update_user_meta( $uid, 'dm_shop_created', time() );
		if ( ! dm_seller_status( $uid ) ) {
			update_user_meta( $uid, 'dm_seller_status', 'draft' );
		}
		dm_flash( 'success', __( 'Shop created! Next, add your payout details.', 'digimarket' ) );
		dm_redirect( dm_url( 'sell' ) );
	}
	dm_flash( 'success', __( 'Shop settings saved.', 'digimarket' ) );
	dm_back( dm_url( 'dashboard', 'settings' ) );
}

function dm_do_seller_kyc() {
	dm_require_login();
	$uid   = get_current_user_id();
	$legal = sanitize_text_field( wp_unslash( $_POST['legal_name'] ?? '' ) );
	$pan   = strtoupper( preg_replace( '/\s+/', '', (string) wp_unslash( $_POST['pan'] ?? '' ) ) );
	$phone = preg_replace( '/[^0-9+]/', '', (string) wp_unslash( $_POST['phone'] ?? '' ) );
	$acct  = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['bank_account'] ?? '' ) );
	$ifsc  = strtoupper( preg_replace( '/\s+/', '', (string) wp_unslash( $_POST['ifsc'] ?? '' ) ) );
	$upi   = sanitize_text_field( wp_unslash( $_POST['upi'] ?? '' ) );
	$btype = sanitize_key( wp_unslash( $_POST['business_type'] ?? 'individual' ) );
	$addr  = array();
	foreach ( array( 'street', 'city', 'state', 'postal_code' ) as $a ) {
		$addr[ $a ] = sanitize_text_field( wp_unslash( $_POST[ 'addr_' . $a ] ?? '' ) );
	}
	$errors        = array();
	$existing_acct = dm_decrypt( get_user_meta( $uid, 'dm_bank_account', true ) );
	$keep_bank     = '' === $acct && $existing_acct;
	if ( '' === $legal ) {
		$errors[] = __( 'Full legal name is required.', 'digimarket' );
	}
	if ( ! preg_match( '/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan ) && ! ( '' === $pan && get_user_meta( $uid, 'dm_pan', true ) ) ) {
		$errors[] = __( 'Enter a valid 10-character PAN (e.g. ABCDE1234F).', 'digimarket' );
	}
	if ( ! preg_match( '/^\+?[0-9]{10,13}$/', $phone ) ) {
		$errors[] = __( 'Enter a valid phone number.', 'digimarket' );
	}
	$has_bank = ( $acct && $ifsc ) || $keep_bank;
	if ( $acct && ( strlen( $acct ) < 9 || strlen( $acct ) > 18 ) ) {
		$errors[] = __( 'Bank account number should be 9–18 digits.', 'digimarket' );
	}
	if ( $acct && ! preg_match( '/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc ) ) {
		$errors[] = __( 'Enter a valid IFSC code (e.g. HDFC0001234).', 'digimarket' );
	}
	if ( $upi && ! preg_match( '/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/', $upi ) ) {
		$errors[] = __( 'Enter a valid UPI ID (e.g. name@bank).', 'digimarket' );
	}
	if ( ! $has_bank && ! $upi ) {
		$errors[] = __( 'Add a bank account + IFSC, or a UPI ID.', 'digimarket' );
	}
	if ( ! in_array( $btype, array( 'individual', 'proprietorship', 'partnership', 'private_limited', 'public_limited', 'llp' ), true ) ) {
		$btype = 'individual';
	}
	if ( $errors ) {
		foreach ( $errors as $e ) {
			dm_flash( 'error', $e );
		}
		dm_back();
	}

	$changed = false;
	if ( $pan ) {
		$changed = $changed || dm_decrypt( get_user_meta( $uid, 'dm_pan', true ) ) !== $pan;
		update_user_meta( $uid, 'dm_pan', dm_encrypt( $pan ) );
	}
	if ( $acct ) {
		$changed = $changed || $existing_acct !== $acct || get_user_meta( $uid, 'dm_ifsc', true ) !== $ifsc;
		update_user_meta( $uid, 'dm_bank_account', dm_encrypt( $acct ) );
		update_user_meta( $uid, 'dm_bank_last4', substr( $acct, -4 ) );
		update_user_meta( $uid, 'dm_ifsc', $ifsc );
	}
	$changed = $changed || get_user_meta( $uid, 'dm_upi', true ) !== $upi;
	update_user_meta( $uid, 'dm_upi', $upi );
	update_user_meta( $uid, 'dm_legal_name', $legal );
	update_user_meta( $uid, 'dm_phone', $phone );
	update_user_meta( $uid, 'dm_business_type', $btype );
	update_user_meta( $uid, 'dm_address', $addr );

	$first = ! get_user_meta( $uid, 'dm_kyc_submitted', true );
	update_user_meta( $uid, 'dm_kyc_submitted', time() );
	if ( $first || $changed || ! get_user_meta( $uid, 'dm_kyc_status', true ) ) {
		update_user_meta( $uid, 'dm_kyc_status', 'pending' );
		if ( 'razorpay' === dm_opt( 'gateway' ) ) {
			$res = dm_rzp_sync_linked_account( $uid );
			if ( is_wp_error( $res ) ) {
				update_user_meta( $uid, 'dm_kyc_error', $res->get_error_message() );
			}
		}
		if ( ! $first ) {
			dm_notify_admins( sprintf( /* translators: %s shop */ __( '%s updated payout details — re-verification needed.', 'digimarket' ), dm_shop_name( $uid ) ) );
		}
	}
	dm_flash( 'success', $first ? __( 'Payout details saved.', 'digimarket' ) : __( 'Payout details updated. They will be re-verified before your next payout.', 'digimarket' ) );
	dm_redirect( $first ? dm_url( 'sell' ) : dm_url( 'dashboard', 'payouts' ) );
}

function dm_do_seller_submit() {
	dm_require_login();
	$uid = get_current_user_id();
	if ( dm_onboarding_step( $uid ) < 4 ) {
		dm_redirect( dm_url( 'sell' ) );
	}
	if ( empty( $_POST['agree'] ) || empty( $_POST['own_rights'] ) ) {
		dm_flash( 'error', __( 'Please accept the Seller Agreement and confirm you own the rights to what you sell.', 'digimarket' ) );
		dm_back();
	}
	$status = dm_opt( 'auto_approve_sellers' ) ? 'active' : 'pending';
	update_user_meta( $uid, 'dm_seller_status', $status );
	update_user_meta( $uid, 'dm_seller_since', time() );
	/* translators: %s shop */
	dm_notify_admins( sprintf( __( 'New seller: %s', 'digimarket' ), dm_shop_name( $uid ) ), admin_url( 'admin.php?page=dm-sellers&seller=' . $uid ) );
	if ( 'active' === $status ) {
		dm_flash( 'success', __( 'Your shop is live! Add your first product.', 'digimarket' ) );
	} else {
		dm_flash( 'info', __( 'Thanks! Your shop is under review. You can add products as drafts meanwhile.', 'digimarket' ) );
	}
	dm_redirect( dm_url( 'dashboard' ) );
}

function dm_notify_admins( $message, $link = '' ) {
	$admins = get_users( array( 'capability' => 'dm_manage_marketplace', 'fields' => 'ID' ) );
	foreach ( $admins as $a ) {
		dm_notify( $a, $message, $link ? $link : admin_url( 'admin.php?page=dm-marketplace' ) );
	}
}

/* -------------------------------------------------------------------------
 * Dashboard — products
 * ---------------------------------------------------------------------- */

function dm_seller_owns( $pid, $uid = 0 ) {
	$uid  = $uid ? $uid : get_current_user_id();
	$post = get_post( $pid );
	return $post && 'dm_product' === $post->post_type && (int) $post->post_author === (int) $uid && 'dm_deleted' !== $post->post_status;
}

function dm_seller_guard() {
	dm_require_login();
	if ( ! dm_is_seller() ) {
		dm_redirect( dm_url( 'sell' ) );
	}
	if ( 'suspended' === dm_seller_status( get_current_user_id() ) ) {
		dm_flash( 'error', __( 'Your shop is suspended. Contact support.', 'digimarket' ) );
		dm_redirect( dm_url( 'dashboard' ) );
	}
}

function dm_do_seller_product_save() {
	dm_seller_guard();
	$pid = absint( $_POST['product_id'] ?? 0 );
	if ( $pid && ! dm_seller_owns( $pid ) ) {
		dm_flash( 'error', __( 'You can only edit your own products.', 'digimarket' ) );
		dm_redirect( dm_url( 'dashboard', 'products' ) );
	}
	$data   = wp_unslash( $_POST );
	$result = dm_save_product( $pid, get_current_user_id(), $data );
	if ( is_wp_error( $result ) ) {
		foreach ( $result->get_error_messages() as $m ) {
			dm_flash( 'error', $m );
		}
		dm_back( dm_url( 'dashboard', 'products' ) );
	}
	dm_flash( 'success', __( 'Product saved.', 'digimarket' ) );
	dm_redirect( dm_url( 'dashboard', 'edit', $result ) );
}

function dm_do_seller_product_action() {
	dm_seller_guard();
	$do  = sanitize_key( $_POST['do'] ?? '' );
	$ids = array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) );
	if ( ! empty( $_POST['row'] ) ) {
		// Single-row button: "action:id".
		list( $do, $one ) = array_pad( explode( ':', sanitize_text_field( wp_unslash( $_POST['row'] ) ) ), 2, 0 );
		$do  = sanitize_key( $do );
		$ids = array( absint( $one ) );
	} elseif ( ! empty( $_POST['product_id'] ) ) {
		$ids[] = absint( $_POST['product_id'] );
	}
	$ids = array_unique( array_filter( $ids ) );
	$n   = 0;
	foreach ( $ids as $pid ) {
		if ( ! dm_seller_owns( $pid ) ) {
			continue;
		}
		switch ( $do ) {
			case 'publish':
				if ( get_post_meta( $pid, '_dm_forced', true ) ) {
					dm_flash( 'warning', __( 'A product locked by moderation was skipped.', 'digimarket' ) );
					break;
				}
				if ( ! dm_is_active_seller() ) {
					dm_flash( 'warning', __( 'Your shop must be approved before publishing.', 'digimarket' ) );
					break 2;
				}
				$ok = has_post_thumbnail( $pid );
				$dl = get_post_meta( $pid, '_dm_delivery', true );
				if ( 'file' === $dl && ! get_post_meta( $pid, '_dm_file', true ) ) {
					$ok = false;
				}
				if ( 'external_link' === $dl && ! get_post_meta( $pid, '_dm_external_url', true ) ) {
					$ok = false;
				}
				if ( 'license_key' === $dl && dm_license_keys_available( $pid ) < 1 ) {
					$ok = false;
				}
				if ( ! $ok ) {
					/* translators: %s title */
					dm_flash( 'warning', sprintf( __( '“%s” needs a thumbnail and its delivery file/link/keys before it can be published.', 'digimarket' ), get_the_title( $pid ) ) );
					break;
				}
				wp_update_post( array( 'ID' => $pid, 'post_status' => 'publish' ) );
				if ( ! get_post_meta( $pid, '_dm_published_once', true ) ) {
					update_post_meta( $pid, '_dm_published_once', 1 );
					do_action( 'dm_product_first_published', $pid, get_current_user_id() );
				}
				++$n;
				break;
			case 'unpublish':
				wp_update_post( array( 'ID' => $pid, 'post_status' => 'dm_unpublished' ) );
				++$n;
				break;
			case 'delete':
				// Soft delete: buyers keep access to what they bought.
				wp_update_post( array( 'ID' => $pid, 'post_status' => 'dm_deleted' ) );
				update_post_meta( $pid, '_dm_deleted_at', dm_now() );
				++$n;
				break;
			case 'duplicate':
				$new = dm_duplicate_product( $pid, get_current_user_id() );
				if ( $new ) {
					++$n;
					if ( 1 === count( $ids ) ) {
						dm_flash( 'success', __( 'Product duplicated as a draft.', 'digimarket' ) );
						dm_redirect( dm_url( 'dashboard', 'edit', $new ) );
					}
				}
				break;
		}
	}
	if ( $n ) {
		/* translators: %d count */
		dm_flash( 'success', sprintf( _n( '%d product updated.', '%d products updated.', $n, 'digimarket' ), $n ) );
	}
	dm_redirect( dm_url( 'dashboard', 'products' ) );
}

/* -------------------------------------------------------------------------
 * Dashboard — orders, refunds, reviews, tickets
 * ---------------------------------------------------------------------- */

function dm_do_seller_refund() {
	dm_seller_guard();
	$item = dm_get_order_item( absint( $_POST['item_id'] ?? 0 ) );
	if ( ! $item || (int) $item->seller_id !== get_current_user_id() ) {
		dm_flash( 'error', __( 'Order not found.', 'digimarket' ) );
		dm_redirect( dm_url( 'dashboard', 'orders' ) );
	}
	if ( ! dm_opt( 'sellers_can_refund' ) ) {
		dm_flash( 'error', __( 'Refunds are handled by the marketplace team. Please contact support.', 'digimarket' ) );
		dm_back();
	}
	$res = dm_refund_item( $item->id, sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) ) );
	if ( is_wp_error( $res ) ) {
		dm_flash( 'error', $res->get_error_message() );
	} else {
		dm_flash( 'success', __( 'Refund issued. The buyer’s access has been revoked and the commission reversed.', 'digimarket' ) );
	}
	dm_back( dm_url( 'dashboard', 'orders' ) );
}

function dm_do_seller_review_reply() {
	dm_seller_guard();
	global $wpdb;
	$id  = absint( $_POST['review_id'] ?? 0 );
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . ' WHERE id = %d', $id ) );
	if ( ! $row || (int) $row->seller_id !== get_current_user_id() ) {
		dm_back();
	}
	if ( isset( $_POST['flag'] ) ) {
		$wpdb->update( dm_table( 'reviews' ), array( 'flagged' => 1, 'flag_reason' => sanitize_text_field( wp_unslash( $_POST['flag_reason'] ?? '' ) ) ), array( 'id' => $id ) );
		/* translators: %s shop */
		dm_notify_admins( sprintf( __( '%s flagged a review for moderation.', 'digimarket' ), dm_shop_name( get_current_user_id() ) ), admin_url( 'admin.php?page=dm-reviews' ) );
		dm_flash( 'success', __( 'Review flagged for the moderation team.', 'digimarket' ) );
	} else {
		$wpdb->update( dm_table( 'reviews' ), array( 'seller_reply' => sanitize_textarea_field( wp_unslash( $_POST['reply'] ?? '' ) ), 'updated_at' => dm_now() ), array( 'id' => $id ) );
		dm_notify( $row->buyer_id, __( 'The seller replied to your review.', 'digimarket' ), get_permalink( $row->product_id ) . '#reviews' );
		dm_flash( 'success', __( 'Reply posted.', 'digimarket' ) );
	}
	dm_back( dm_url( 'dashboard', 'reviews' ) );
}

function dm_do_ticket_reply() {
	dm_require_login();
	global $wpdb;
	$id  = absint( $_POST['ticket_id'] ?? 0 );
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'tickets' ) . ' WHERE id = %d', $id ) );
	if ( ! $row || ( (int) $row->seller_id !== get_current_user_id() && ! current_user_can( 'dm_manage_marketplace' ) ) ) {
		dm_back();
	}
	$status = in_array( $_POST['status'] ?? '', array( 'open', 'closed' ), true ) ? $_POST['status'] : 'open';
	$wpdb->update(
		dm_table( 'tickets' ),
		array(
			'reply'      => sanitize_textarea_field( wp_unslash( $_POST['reply'] ?? '' ) ),
			'status'     => $status,
			'updated_at' => dm_now(),
		),
		array( 'id' => $id )
	);
	/* translators: %s subject */
	dm_notify( $row->user_id, sprintf( __( 'Update on your support request: %s', 'digimarket' ), $row->subject ), dm_url( 'account', 'support' ) );
	dm_flash( 'success', __( 'Reply sent.', 'digimarket' ) );
	dm_back();
}

/**
 * GET actions on the dashboard (CSV export, notifications).
 */
function dm_dashboard_early_actions() {
	if ( isset( $_GET['export'] ) && 'orders' === $_GET['export'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dm_export' ) ) {
		$rows = dm_seller_orders_query( get_current_user_id(), 5000, 0 );
		$out  = array();
		foreach ( $rows['items'] as $r ) {
			$out[] = array( dm_order_number( $r->order_id ), $r->created_at, $r->buyer_name, $r->buyer_email, $r->product_title, $r->price_at_purchase, $r->commission_percent_applied, $r->commission_amount, $r->seller_net_amount, $r->item_status, $r->transfer_status, $r->transfer_id );
		}
		dm_output_csv( 'orders-' . gmdate( 'Y-m-d' ) . '.csv', array( 'Order', 'Date', 'Buyer', 'Buyer email', 'Product', 'Amount paid', 'Commission %', 'Commission', 'Net credited', 'Payment status', 'Transfer status', 'Transfer ID' ), $out );
	}
	if ( 'file' === dm_tab() ) {
		$pid = absint( dm_route_id() );
		if ( dm_seller_owns( $pid ) ) {
			dm_stream_product_file( $pid, 0 );
		}
		wp_die( esc_html__( 'File not found.', 'digimarket' ), 404 );
	}
}

/**
 * Seller order items with filters.
 */
function dm_seller_orders_query( $seller_id, $limit = 20, $offset = 0 ) {
	global $wpdb;
	$where  = array( $wpdb->prepare( 'i.seller_id = %d', $seller_id ), "i.item_status <> 'pending'" );
	$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	if ( $status ) {
		$where[] = $wpdb->prepare( 'i.item_status = %s', $status );
	}
	if ( ! empty( $_GET['product'] ) ) {
		$where[] = $wpdb->prepare( 'i.product_id = %d', absint( $_GET['product'] ) );
	}
	if ( ! empty( $_GET['from'] ) ) {
		$where[] = $wpdb->prepare( 'o.created_at >= %s', sanitize_text_field( wp_unslash( $_GET['from'] ) ) . ' 00:00:00' );
	}
	if ( ! empty( $_GET['to'] ) ) {
		$where[] = $wpdb->prepare( 'o.created_at <= %s', sanitize_text_field( wp_unslash( $_GET['to'] ) ) . ' 23:59:59' );
	}
	$w     = implode( ' AND ', $where );
	$from  = dm_table( 'order_items' ) . ' i INNER JOIN ' . dm_table( 'orders' ) . ' o ON o.id = i.order_id';
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $from WHERE $w" ); // phpcs:ignore
	$items = $wpdb->get_results( $wpdb->prepare( "SELECT i.*, o.buyer_name, o.buyer_email, o.created_at, o.razorpay_payment_id, o.payment_status FROM $from WHERE $w ORDER BY i.id DESC LIMIT %d OFFSET %d", $limit, $offset ) ); // phpcs:ignore
	return array( 'total' => $total, 'items' => $items );
}

/**
 * Seller statistics for the overview page.
 */
function dm_seller_stats( $uid, $days = 30 ) {
	global $wpdb;
	$items = dm_table( 'order_items' );
	$ord   = dm_table( 'orders' );
	$tot   = $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(price_at_purchase),0) revenue, COALESCE(SUM(seller_net_amount),0) net, COUNT(*) orders FROM $items WHERE seller_id = %d AND item_status = 'paid'", $uid ) ); // phpcs:ignore
	$pend  = (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . dm_table( 'payouts' ) . " WHERE seller_id = %d AND status = 'pending'", $uid ) );
	$from  = gmdate( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days', current_time( 'timestamp' ) ) );
	if ( ! empty( $_GET['from'] ) && ! empty( $_GET['to'] ) && 'custom' === ( $_GET['range'] ?? '' ) ) {
		$from = sanitize_text_field( wp_unslash( $_GET['from'] ) );
		$days = max( 1, min( 366, (int) ( ( strtotime( sanitize_text_field( wp_unslash( $_GET['to'] ) ) ) - strtotime( $from ) ) / DAY_IN_SECONDS ) + 1 ) );
	}
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(o.paid_at) d, SUM(i.price_at_purchase) v FROM $items i INNER JOIN $ord o ON o.id = i.order_id WHERE i.seller_id = %d AND i.item_status = 'paid' AND o.paid_at >= %s GROUP BY DATE(o.paid_at)", $uid, $from . ' 00:00:00' ) ); // phpcs:ignore
	$top  = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, product_title, COUNT(*) c, SUM(price_at_purchase) r FROM $items WHERE seller_id = %d AND item_status = 'paid' GROUP BY product_id, product_title ORDER BY c DESC LIMIT 5", $uid ) ); // phpcs:ignore
	$prod = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'dm_product' AND post_author = %d AND post_status IN ('publish','draft','dm_unpublished')", $uid ) );
	return array(
		'revenue'  => (float) $tot->revenue,
		'net'      => (float) $tot->net,
		'orders'   => (int) $tot->orders,
		'products' => $prod,
		'pending'  => $pend,
		'series'   => dm_series( $rows, $days, $from ),
		'top'      => $top,
	);
}
