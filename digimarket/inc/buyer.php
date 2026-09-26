<?php
/**
 * Buyer account: purchases, wishlist, follows, profile, support, invoices, privacy.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_account_early_actions() {
	$tab = dm_tab();
	if ( 'download' === $tab ) {
		dm_account_download_redirect( absint( dm_route_id() ) );
	}
	if ( 'notifications' === $tab && isset( $_GET['read'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dm_read' ) ) {
		global $wpdb;
		$wpdb->update( dm_table( 'notifications' ), array( 'is_read' => 1 ), array( 'user_id' => get_current_user_id() ) );
		dm_redirect( dm_url( 'account', 'notifications' ) );
	}
	if ( 'export' === $tab && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dm_export_me' ) ) {
		dm_export_my_data();
	}
}

/* Open a notification: mark read and go. */
add_action( 'template_redirect', function () {
	if ( 'account' !== dm_route() || 'notification' !== dm_tab() || ! is_user_logged_in() ) {
		return;
	}
	global $wpdb;
	$n = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'notifications' ) . ' WHERE id = %d AND user_id = %d', absint( dm_route_id() ), get_current_user_id() ) );
	if ( $n ) {
		$wpdb->update( dm_table( 'notifications' ), array( 'is_read' => 1 ), array( 'id' => $n->id ) );
		wp_safe_redirect( $n->link ? $n->link : dm_url( 'account', 'notifications' ) );
		exit;
	}
	dm_redirect( dm_url( 'account', 'notifications' ) );
}, 6 );

/* -------------------------------------------------------------------------
 * Wishlist & follows
 * ---------------------------------------------------------------------- */

function dm_in_wishlist( $pid, $uid = 0 ) {
	global $wpdb;
	$uid = $uid ? $uid : get_current_user_id();
	return $uid && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'wishlists' ) . ' WHERE buyer_id = %d AND product_id = %d', $uid, $pid ) );
}

function dm_toggle_wishlist( $pid ) {
	global $wpdb;
	$uid = get_current_user_id();
	if ( dm_in_wishlist( $pid, $uid ) ) {
		$wpdb->delete( dm_table( 'wishlists' ), array( 'buyer_id' => $uid, 'product_id' => $pid ) );
		return false;
	}
	$wpdb->insert( dm_table( 'wishlists' ), array( 'buyer_id' => $uid, 'product_id' => $pid, 'created_at' => dm_now() ) );
	return true;
}

add_action( 'wp_ajax_dm_wishlist', function () {
	dm_ajax_check();
	$on = dm_toggle_wishlist( absint( $_POST['product_id'] ?? 0 ) );
	wp_send_json_success( array( 'on' => $on ) );
} );
add_action( 'wp_ajax_nopriv_dm_wishlist', function () {
	wp_send_json_error( array( 'login' => true, 'message' => __( 'Log in to save products.', 'digimarket' ) ) );
} );

function dm_do_wishlist() {
	dm_require_login();
	$on = dm_toggle_wishlist( absint( $_POST['product_id'] ?? 0 ) );
	dm_flash( 'success', $on ? __( 'Saved to wishlist.', 'digimarket' ) : __( 'Removed from wishlist.', 'digimarket' ) );
	dm_back();
}

function dm_is_following( $seller_id, $uid = 0 ) {
	global $wpdb;
	$uid = $uid ? $uid : get_current_user_id();
	return $uid && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'follows' ) . ' WHERE user_id = %d AND seller_id = %d', $uid, $seller_id ) );
}

function dm_follower_count( $seller_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dm_table( 'follows' ) . ' WHERE seller_id = %d', $seller_id ) );
}

function dm_do_follow() {
	dm_require_login();
	global $wpdb;
	$sid = absint( $_POST['seller_id'] ?? 0 );
	$uid = get_current_user_id();
	if ( ! $sid || $sid === $uid ) {
		dm_back();
	}
	if ( dm_is_following( $sid, $uid ) ) {
		$wpdb->delete( dm_table( 'follows' ), array( 'user_id' => $uid, 'seller_id' => $sid ) );
		dm_flash( 'info', __( 'You unfollowed this shop.', 'digimarket' ) );
	} else {
		$wpdb->insert( dm_table( 'follows' ), array( 'user_id' => $uid, 'seller_id' => $sid, 'created_at' => dm_now() ) );
		dm_flash( 'success', __( 'Following! You’ll be notified about new products.', 'digimarket' ) );
	}
	dm_back();
}

/* -------------------------------------------------------------------------
 * Profile & security
 * ---------------------------------------------------------------------- */

function dm_do_update_profile() {
	dm_require_login();
	$uid   = get_current_user_id();
	$user  = get_userdata( $uid );
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$phone = preg_replace( '/[^0-9+]/', '', (string) wp_unslash( $_POST['phone'] ?? '' ) );
	if ( '' === $name || ! is_email( $email ) ) {
		dm_flash( 'error', __( 'Name and a valid email are required.', 'digimarket' ) );
		dm_back();
	}
	$update = array( 'ID' => $uid, 'display_name' => $name, 'first_name' => $name );
	if ( strtolower( $email ) !== strtolower( $user->user_email ) ) {
		if ( email_exists( $email ) ) {
			dm_flash( 'error', __( 'That email is used by another account.', 'digimarket' ) );
			dm_back();
		}
		if ( ! wp_check_password( (string) wp_unslash( $_POST['current_password'] ?? '' ), $user->user_pass, $uid ) ) {
			dm_flash( 'error', __( 'Enter your current password to change your email.', 'digimarket' ) );
			dm_back();
		}
		$update['user_email'] = $email;
	}
	$res = wp_update_user( $update );
	if ( is_wp_error( $res ) ) {
		dm_flash( 'error', $res->get_error_message() );
		dm_back();
	}
	update_user_meta( $uid, 'dm_phone', $phone );
	if ( isset( $update['user_email'] ) ) {
		update_user_meta( $uid, 'dm_email_verified', 0 );
		dm_send_verification( $uid );
		dm_flash( 'info', __( 'We sent a verification link to your new email.', 'digimarket' ) );
	}
	dm_flash( 'success', __( 'Profile updated.', 'digimarket' ) );
	dm_back( dm_url( 'account', 'profile' ) );
}

function dm_do_change_password() {
	dm_require_login();
	$user = wp_get_current_user();
	$cur  = (string) wp_unslash( $_POST['current_password'] ?? '' );
	$new  = (string) wp_unslash( $_POST['new_password'] ?? '' );
	if ( ! wp_check_password( $cur, $user->user_pass, $user->ID ) ) {
		dm_flash( 'error', __( 'Your current password is incorrect.', 'digimarket' ) );
		dm_back();
	}
	if ( strlen( $new ) < 8 || $new !== (string) wp_unslash( $_POST['new_password2'] ?? '' ) ) {
		dm_flash( 'error', __( 'New passwords must match and be at least 8 characters.', 'digimarket' ) );
		dm_back();
	}
	wp_set_password( $new, $user->ID );
	wp_set_auth_cookie( $user->ID, true, is_ssl() );
	dm_flash( 'success', __( 'Password changed.', 'digimarket' ) );
	dm_redirect( dm_url( 'account', 'profile' ) );
}

function dm_do_delete_account() {
	dm_require_login();
	$user = wp_get_current_user();
	if ( user_can( $user, 'manage_options' ) ) {
		dm_back();
	}
	if ( ! wp_check_password( (string) wp_unslash( $_POST['current_password'] ?? '' ), $user->user_pass, $user->ID ) ) {
		dm_flash( 'error', __( 'Password incorrect — account not deleted.', 'digimarket' ) );
		dm_back();
	}
	// Anonymise rather than hard delete so financial records stay consistent.
	global $wpdb;
	$anon = 'deleted-' . $user->ID . '@invalid.local';
	$wpdb->update( dm_table( 'orders' ), array( 'buyer_email' => $anon, 'buyer_name' => __( 'Deleted user', 'digimarket' ) ), array( 'buyer_id' => $user->ID ) );
	$wpdb->delete( dm_table( 'wishlists' ), array( 'buyer_id' => $user->ID ) );
	$wpdb->delete( dm_table( 'follows' ), array( 'user_id' => $user->ID ) );
	foreach ( array( 'dm_pan', 'dm_bank_account', 'dm_upi', 'dm_phone', 'dm_address', 'dm_legal_name' ) as $k ) {
		delete_user_meta( $user->ID, $k );
	}
	wp_update_user( array( 'ID' => $user->ID, 'user_email' => $anon, 'display_name' => __( 'Deleted user', 'digimarket' ), 'first_name' => '', 'last_name' => '' ) );
	update_user_meta( $user->ID, 'dm_suspended', 1 );
	if ( dm_is_seller( $user->ID ) ) {
		update_user_meta( $user->ID, 'dm_seller_status', 'suspended' );
		dm_unpublish_seller_products( $user->ID );
	}
	wp_logout();
	dm_flash( 'success', __( 'Your account has been deleted.', 'digimarket' ) );
	dm_redirect( home_url( '/' ) );
}

function dm_export_my_data() {
	global $wpdb;
	$uid    = get_current_user_id();
	$user   = get_userdata( $uid );
	$orders = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . ' WHERE buyer_id = %d', $uid ), ARRAY_A );
	foreach ( $orders as &$o ) {
		$o['items'] = $wpdb->get_results( $wpdb->prepare( 'SELECT product_title, price_at_purchase, item_status, license_key, download_count FROM ' . dm_table( 'order_items' ) . ' WHERE order_id = %d', $o['id'] ), ARRAY_A );
	}
	$data = array(
		'profile'   => array( 'name' => $user->display_name, 'email' => $user->user_email, 'phone' => get_user_meta( $uid, 'dm_phone', true ), 'registered' => $user->user_registered ),
		'orders'    => $orders,
		'reviews'   => $wpdb->get_results( $wpdb->prepare( 'SELECT product_id, rating, comment, created_at FROM ' . dm_table( 'reviews' ) . ' WHERE buyer_id = %d', $uid ), ARRAY_A ),
		'wishlist'  => $wpdb->get_col( $wpdb->prepare( 'SELECT product_id FROM ' . dm_table( 'wishlists' ) . ' WHERE buyer_id = %d', $uid ) ),
		'downloads' => $wpdb->get_results( $wpdb->prepare( 'SELECT product_id, ip, created_at FROM ' . dm_table( 'downloads' ) . ' WHERE user_id = %d', $uid ), ARRAY_A ),
	);
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="my-data.json"' );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT );
	exit;
}

/* -------------------------------------------------------------------------
 * Support tickets
 * ---------------------------------------------------------------------- */

function dm_do_open_ticket() {
	dm_require_login();
	global $wpdb;
	$uid      = get_current_user_id();
	$order_id = absint( $_POST['order_id'] ?? 0 );
	$subject  = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
	$message  = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
	if ( '' === $subject || '' === $message ) {
		dm_flash( 'error', __( 'Please choose a topic and describe the issue.', 'digimarket' ) );
		dm_back();
	}
	$seller = 0;
	if ( $order_id ) {
		$order = dm_get_order( $order_id );
		if ( ! $order || (int) $order->buyer_id !== $uid ) {
			dm_back();
		}
		$items  = dm_get_order_items( $order_id );
		$seller = $items ? (int) $items[0]->seller_id : 0;
	}
	$wpdb->insert(
		dm_table( 'tickets' ),
		array(
			'user_id'    => $uid,
			'order_id'   => $order_id,
			'seller_id'  => $seller,
			'subject'    => $subject,
			'message'    => $message,
			'status'     => 'open',
			'created_at' => dm_now(),
		)
	);
	if ( $seller ) {
		/* translators: %s subject */
		dm_notify( $seller, sprintf( __( 'New support request: %s', 'digimarket' ), $subject ), dm_url( 'dashboard', 'support' ) );
	}
	/* translators: %s subject */
	dm_notify_admins( sprintf( __( 'New support ticket: %s', 'digimarket' ), $subject ), admin_url( 'admin.php?page=dm-tickets' ) );
	dm_flash( 'success', __( 'Your request was sent. We’ll get back to you soon.', 'digimarket' ) );
	dm_redirect( dm_url( 'account', 'support' ) );
}

/* -------------------------------------------------------------------------
 * Invoice (printable → Save as PDF)
 * ---------------------------------------------------------------------- */

function dm_render_invoice( $order_id ) {
	$order = dm_get_order( $order_id );
	if ( ! $order || ( (int) $order->buyer_id !== get_current_user_id() && ! current_user_can( 'dm_view_marketplace' ) ) ) {
		wp_die( esc_html__( 'Invoice not found.', 'digimarket' ), '', array( 'response' => 404 ) );
	}
	if ( ! in_array( $order->payment_status, array( 'paid', 'refunded', 'partially_refunded' ), true ) ) {
		wp_die( esc_html__( 'Invoices are available for paid orders only.', 'digimarket' ), '', array( 'response' => 403 ) );
	}
	$items = dm_get_order_items( $order_id );
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( sprintf( /* translators: %s */ __( 'Invoice %s', 'digimarket' ), dm_order_number( $order ) ) ); ?></title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#111;margin:0;padding:32px;background:#f5f5f7}
.inv{max-width:800px;margin:0 auto;background:#fff;padding:40px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
h1{margin:0 0 4px;font-size:28px}.muted{color:#666;font-size:14px}.row{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;margin:24px 0}
table{width:100%;border-collapse:collapse;margin-top:16px}th,td{text-align:left;padding:10px 8px;border-bottom:1px solid #eee;font-size:14px}th{background:#fafafa}
.r{text-align:right}.tot td{font-weight:700;border-bottom:0}.btn{display:inline-block;background:#111;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;border:0;cursor:pointer;font-size:14px}
.status{display:inline-block;padding:2px 10px;border-radius:99px;background:#e8f7ee;color:#137a3c;font-size:12px;font-weight:600}
@media print{body{background:#fff;padding:0}.inv{box-shadow:none}.noprint{display:none}}
</style>
</head>
<body>
<div class="noprint" style="max-width:800px;margin:0 auto 16px;display:flex;gap:8px;justify-content:flex-end">
	<a class="btn" href="<?php echo esc_url( dm_url( 'account', 'orders' ) ); ?>" style="background:#666"><?php esc_html_e( 'Back', 'digimarket' ); ?></a>
	<button class="btn" onclick="window.print()"><?php esc_html_e( 'Download PDF / Print', 'digimarket' ); ?></button>
</div>
<div class="inv">
	<div class="row" style="margin-top:0">
		<div>
			<h1><?php esc_html_e( 'Tax Invoice', 'digimarket' ); ?></h1>
			<div class="muted"><?php echo esc_html( dm_order_number( $order ) ); ?> · <?php echo esc_html( mysql2date( get_option( 'date_format' ), $order->paid_at ? $order->paid_at : $order->created_at ) ); ?></div>
			<p><span class="status"><?php echo esc_html( ucwords( str_replace( '_', ' ', $order->payment_status ) ) ); ?></span></p>
		</div>
		<div class="muted" style="text-align:right">
			<strong style="color:#111"><?php echo esc_html( dm_opt( 'invoice_company' ) ); ?></strong><br>
			<?php echo nl2br( esc_html( dm_opt( 'invoice_address' ) ) ); ?>
			<?php if ( dm_opt( 'invoice_gstin' ) ) : ?><br>GSTIN: <?php echo esc_html( dm_opt( 'invoice_gstin' ) ); ?><?php endif; ?>
		</div>
	</div>
	<div class="muted"><strong style="color:#111"><?php esc_html_e( 'Billed to', 'digimarket' ); ?></strong><br><?php echo esc_html( $order->buyer_name ); ?><br><?php echo esc_html( $order->buyer_email ); ?></div>
	<table>
		<thead><tr><th><?php esc_html_e( 'Item', 'digimarket' ); ?></th><th><?php esc_html_e( 'Sold by', 'digimarket' ); ?></th><th><?php esc_html_e( 'Status', 'digimarket' ); ?></th><th class="r"><?php esc_html_e( 'Amount', 'digimarket' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $items as $it ) : ?>
			<tr><td><?php echo esc_html( $it->product_title ); ?></td><td><?php echo esc_html( dm_shop_name( $it->seller_id ) ); ?></td><td><?php echo esc_html( ucfirst( $it->item_status ) ); ?></td><td class="r"><?php echo esc_html( dm_money( $it->list_price ) ); ?></td></tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr><td colspan="3" class="r"><?php esc_html_e( 'Subtotal', 'digimarket' ); ?></td><td class="r"><?php echo esc_html( dm_money( $order->subtotal ) ); ?></td></tr>
			<?php if ( $order->discount > 0 ) : ?>
			<tr><td colspan="3" class="r"><?php echo esc_html( sprintf( /* translators: %s code */ __( 'Discount (%s)', 'digimarket' ), $order->coupon_code ) ); ?></td><td class="r">−<?php echo esc_html( dm_money( $order->discount ) ); ?></td></tr>
			<?php endif; ?>
			<tr class="tot"><td colspan="3" class="r"><?php esc_html_e( 'Total paid', 'digimarket' ); ?></td><td class="r"><?php echo esc_html( dm_money( $order->order_total ) ); ?></td></tr>
		</tfoot>
	</table>
	<p class="muted" style="margin-top:24px"><?php esc_html_e( 'Payment reference:', 'digimarket' ); ?> <?php echo esc_html( $order->razorpay_payment_id ); ?><br><?php esc_html_e( 'Digital goods — delivered electronically. Each seller is responsible for GST on their own sales unless stated otherwise.', 'digimarket' ); ?></p>
</div>
</body>
</html>
	<?php
	exit;
}
