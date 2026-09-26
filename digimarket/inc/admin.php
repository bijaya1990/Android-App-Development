<?php
/**
 * Admin super dashboard (wp-admin → Marketplace).
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'dm_admin_menu' );
function dm_admin_menu() {
	$cap = 'dm_view_marketplace';
	add_menu_page( __( 'Marketplace', 'digimarket' ), __( 'Marketplace', 'digimarket' ), $cap, 'dm-marketplace', 'dm_admin_overview', 'dashicons-store', 3 );
	add_submenu_page( 'dm-marketplace', __( 'Overview', 'digimarket' ), __( 'Overview', 'digimarket' ), $cap, 'dm-marketplace', 'dm_admin_overview' );
	$pending = dm_admin_pending_count();
	add_submenu_page( 'dm-marketplace', __( 'Sellers', 'digimarket' ), __( 'Sellers', 'digimarket' ) . ( $pending ? ' <span class="awaiting-mod">' . (int) $pending . '</span>' : '' ), $cap, 'dm-sellers', 'dm_admin_sellers' );
	add_submenu_page( 'dm-marketplace', __( 'Buyers', 'digimarket' ), __( 'Buyers', 'digimarket' ), $cap, 'dm-buyers', 'dm_admin_buyers' );
	add_submenu_page( 'dm-marketplace', __( 'Moderation', 'digimarket' ), __( 'Moderation', 'digimarket' ), $cap, 'dm-moderation', 'dm_admin_moderation' );
	add_submenu_page( 'dm-marketplace', __( 'Transactions', 'digimarket' ), __( 'Transactions', 'digimarket' ), $cap, 'dm-transactions', 'dm_admin_transactions' );
	add_submenu_page( 'dm-marketplace', __( 'Payouts', 'digimarket' ), __( 'Payouts', 'digimarket' ), $cap, 'dm-payouts', 'dm_admin_payouts' );
	add_submenu_page( 'dm-marketplace', __( 'Reviews', 'digimarket' ), __( 'Reviews', 'digimarket' ), $cap, 'dm-reviews', 'dm_admin_reviews' );
	add_submenu_page( 'dm-marketplace', __( 'Coupons', 'digimarket' ), __( 'Coupons', 'digimarket' ), $cap, 'dm-coupons', 'dm_admin_coupons' );
	add_submenu_page( 'dm-marketplace', __( 'Support', 'digimarket' ), __( 'Support tickets', 'digimarket' ), $cap, 'dm-tickets', 'dm_admin_tickets' );
	add_submenu_page( 'dm-marketplace', __( 'Categories', 'digimarket' ), __( 'Categories', 'digimarket' ), 'manage_categories', 'edit-tags.php?taxonomy=dm_category&post_type=dm_product' );
	add_submenu_page( 'dm-marketplace', __( 'Audit log', 'digimarket' ), __( 'Audit & downloads', 'digimarket' ), $cap, 'dm-audit', 'dm_admin_audit' );
	add_submenu_page( 'dm-marketplace', __( 'Settings', 'digimarket' ), __( 'Settings', 'digimarket' ), 'dm_manage_marketplace', 'dm-settings', 'dm_admin_settings' );
}

/* Keep the Marketplace menu highlighted on product category screens. */
add_filter( 'parent_file', function ( $parent ) {
	global $current_screen;
	if ( $current_screen && 'dm_category' === $current_screen->taxonomy ) {
		return 'dm-marketplace';
	}
	return $parent;
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	wp_enqueue_style( 'dm-admin', DM_URI . '/assets/css/admin.css', array(), DM_VERSION );
} );

function dm_admin_pending_count() {
	return count( get_users( array( 'meta_key' => 'dm_seller_status', 'meta_value' => 'pending', 'fields' => 'ID', 'number' => 99 ) ) );
}

/* Admin notice when in demo gateway mode. */
add_action( 'admin_notices', function () {
	if ( 'demo' === dm_opt( 'gateway' ) && current_user_can( 'dm_manage_marketplace' ) ) {
		echo '<div class="notice notice-warning"><p><strong>DigiMarket:</strong> ' . esc_html__( 'Payments are in DEMO mode — orders are marked paid without real money. Configure Razorpay in Marketplace → Settings before launch.', 'digimarket' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=dm-settings' ) ) . '">' . esc_html__( 'Open settings', 'digimarket' ) . '</a></p></div>';
	}
} );

function dm_admin_url( $page, $args = array() ) {
	return add_query_arg( $args, admin_url( 'admin.php?page=' . $page ) );
}

function dm_admin_action_url( $do, $args = array() ) {
	$args = array_merge( array( 'action' => 'dm_admin', 'do' => $do ), $args );
	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'dm_admin_' . $do );
}

function dm_admin_form_open( $do ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="dm_admin"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	wp_nonce_field( 'dm_admin_' . $do );
}

function dm_admin_header( $title, $actions = '' ) {
	echo '<div class="wrap dm-admin"><h1 class="wp-heading-inline">' . esc_html( $title ) . '</h1>' . $actions . '<hr class="wp-header-end">'; // phpcs:ignore
	if ( ! empty( $_GET['dm_msg'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['dm_msg'] ) ) ) . '</p></div>';
	}
	if ( ! empty( $_GET['dm_err'] ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['dm_err'] ) ) ) . '</p></div>';
	}
}

function dm_admin_back( $msg = '', $err = '' ) {
	$ref = wp_get_referer();
	$ref = $ref ? remove_query_arg( array( 'dm_msg', 'dm_err' ), $ref ) : admin_url( 'admin.php?page=dm-marketplace' );
	if ( $msg ) {
		$ref = add_query_arg( 'dm_msg', rawurlencode( $msg ), $ref );
	}
	if ( $err ) {
		$ref = add_query_arg( 'dm_err', rawurlencode( $err ), $ref );
	}
	wp_safe_redirect( $ref );
	exit;
}

function dm_stat_card( $label, $value, $tone = '' ) {
	echo '<div class="dm-stat ' . esc_attr( $tone ) . '"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $value ) . '</strong></div>';
}

/* -------------------------------------------------------------------------
 * Overview
 * ---------------------------------------------------------------------- */

function dm_admin_overview() {
	global $wpdb;
	$i     = dm_table( 'order_items' );
	$o     = dm_table( 'orders' );
	$tot   = $wpdb->get_row( "SELECT COALESCE(SUM(price_at_purchase),0) gross, COALESCE(SUM(commission_amount),0) comm, COUNT(DISTINCT order_id) orders FROM $i WHERE item_status = 'paid'" ); // phpcs:ignore
	$sell  = count( get_users( array( 'meta_key' => 'dm_seller_status', 'meta_value' => 'active', 'fields' => 'ID' ) ) );
	$buy   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT buyer_id) FROM $o WHERE payment_status IN ('paid','partially_refunded')" ); // phpcs:ignore
	$users = count_users();
	$prods = (int) wp_count_posts( 'dm_product' )->publish;
	$days  = isset( $_GET['days'] ) ? max( 7, min( 365, absint( $_GET['days'] ) ) ) : 30;
	$from  = gmdate( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days', current_time( 'timestamp' ) ) ) . ' 00:00:00';
	$rev   = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(o.paid_at) d, SUM(i.price_at_purchase) v FROM $i i INNER JOIN $o o ON o.id = i.order_id WHERE i.item_status = 'paid' AND o.paid_at >= %s GROUP BY DATE(o.paid_at)", $from ) ); // phpcs:ignore
	$nb    = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(user_registered) d, COUNT(*) v FROM {$wpdb->users} WHERE user_registered >= %s GROUP BY DATE(user_registered)", $from ) );
	$ns    = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(FROM_UNIXTIME(meta_value)) d, COUNT(*) v FROM {$wpdb->usermeta} WHERE meta_key = 'dm_seller_since' AND meta_value >= %d GROUP BY DATE(FROM_UNIXTIME(meta_value))", strtotime( $from ) ) );
	if ( $wpdb->last_error ) {
		// FROM_UNIXTIME unsupported (e.g. SQLite) — compute in PHP.
		$ns  = array();
		$raw = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'dm_seller_since' AND meta_value >= %d", strtotime( $from ) ) );
		$agg = array();
		foreach ( $raw as $ts ) {
			$d         = gmdate( 'Y-m-d', (int) $ts );
			$agg[ $d ] = ( $agg[ $d ] ?? 0 ) + 1;
		}
		foreach ( $agg as $d => $v ) {
			$ns[] = (object) array( 'd' => $d, 'v' => $v );
		}
	}
	$top_sellers  = $wpdb->get_results( "SELECT seller_id, SUM(price_at_purchase) r, COUNT(*) c FROM $i WHERE item_status = 'paid' GROUP BY seller_id ORDER BY r DESC LIMIT 5" ); // phpcs:ignore
	$top_products = $wpdb->get_results( "SELECT product_id, product_title, COUNT(*) c, SUM(price_at_purchase) r FROM $i WHERE item_status = 'paid' GROUP BY product_id, product_title ORDER BY c DESC LIMIT 5" ); // phpcs:ignore
	$failed       = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . dm_table( 'payouts' ) . " WHERE status = 'failed'" ); // phpcs:ignore
	$reports      = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . dm_table( 'reports' ) . " WHERE status = 'open'" ); // phpcs:ignore
	$flagged      = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . dm_table( 'reviews' ) . ' WHERE flagged = 1' ); // phpcs:ignore
	$disputes     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $o WHERE disputed = 1" ); // phpcs:ignore
	$pending      = dm_admin_pending_count();

	dm_admin_header( __( 'Marketplace overview', 'digimarket' ) );
	echo '<div class="dm-alerts">';
	if ( $pending ) {
		echo '<a class="dm-alert warning" href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'status' => 'pending' ) ) ) . '">' . esc_html( sprintf( /* translators: %d */ _n( '%d seller awaiting approval', '%d sellers awaiting approval', $pending, 'digimarket' ), $pending ) ) . '</a>';
	}
	if ( $failed ) {
		echo '<a class="dm-alert danger" href="' . esc_url( dm_admin_url( 'dm-payouts', array( 'status' => 'failed' ) ) ) . '">' . esc_html( sprintf( /* translators: %d */ _n( '%d failed payout', '%d failed payouts', $failed, 'digimarket' ), $failed ) ) . '</a>';
	}
	if ( $reports || $flagged ) {
		echo '<a class="dm-alert warning" href="' . esc_url( dm_admin_url( 'dm-moderation' ) ) . '">' . esc_html( sprintf( /* translators: 1 reports 2 reviews */ __( '%1$d product reports · %2$d flagged reviews', 'digimarket' ), $reports, $flagged ) ) . '</a>';
	}
	if ( $disputes ) {
		echo '<a class="dm-alert danger" href="' . esc_url( dm_admin_url( 'dm-transactions', array( 'disputed' => 1 ) ) ) . '">' . esc_html( sprintf( /* translators: %d */ _n( '%d disputed payment', '%d disputed payments', $disputes, 'digimarket' ), $disputes ) ) . '</a>';
	}
	echo '</div><div class="dm-stats">';
	dm_stat_card( __( 'Gross revenue', 'digimarket' ), dm_money( $tot->gross ) );
	dm_stat_card( __( 'Commission earned', 'digimarket' ), dm_money( $tot->comm ), 'accent' );
	dm_stat_card( __( 'Active sellers', 'digimarket' ), number_format_i18n( $sell ) );
	dm_stat_card( __( 'Buyers (purchased)', 'digimarket' ), number_format_i18n( $buy ) );
	dm_stat_card( __( 'Registered users', 'digimarket' ), number_format_i18n( $users['total_users'] ) );
	dm_stat_card( __( 'Live products', 'digimarket' ), number_format_i18n( $prods ) );
	dm_stat_card( __( 'Paid orders', 'digimarket' ), number_format_i18n( $tot->orders ) );
	echo '</div>';

	echo '<p class="dm-range">';
	foreach ( array( 7, 30, 90, 365 ) as $d ) {
		echo '<a class="button ' . ( $d === $days ? 'button-primary' : '' ) . '" href="' . esc_url( dm_admin_url( 'dm-marketplace', array( 'days' => $d ) ) ) . '">' . esc_html( sprintf( /* translators: %d */ __( '%d days', 'digimarket' ), $d ) ) . '</a> ';
	}
	echo '</p><div class="dm-grid3">';
	list( $l, $v ) = dm_series( $rev, $days );
	echo '<div class="dm-panel"><h2>' . esc_html__( 'Revenue', 'digimarket' ) . '</h2>' . dm_svg_chart( $l, $v ) . '</div>'; // phpcs:ignore
	list( $l, $v ) = dm_series( $ns, $days );
	echo '<div class="dm-panel"><h2>' . esc_html__( 'New sellers', 'digimarket' ) . '</h2>' . dm_svg_chart( $l, $v, false ) . '</div>'; // phpcs:ignore
	list( $l, $v ) = dm_series( $nb, $days );
	echo '<div class="dm-panel"><h2>' . esc_html__( 'New sign-ups', 'digimarket' ) . '</h2>' . dm_svg_chart( $l, $v, false ) . '</div>'; // phpcs:ignore
	echo '</div><div class="dm-grid2"><div class="dm-panel"><h2>' . esc_html__( 'Top sellers', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Shop', 'digimarket' ) . '</th><th>' . esc_html__( 'Sales', 'digimarket' ) . '</th><th>' . esc_html__( 'Revenue', 'digimarket' ) . '</th></tr></thead><tbody>';
	foreach ( $top_sellers as $s ) {
		echo '<tr><td><a href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'seller' => $s->seller_id ) ) ) . '">' . esc_html( dm_shop_name( $s->seller_id ) ) . '</a></td><td>' . (int) $s->c . '</td><td>' . esc_html( dm_money( $s->r ) ) . '</td></tr>';
	}
	if ( ! $top_sellers ) {
		echo '<tr><td colspan="3">' . esc_html__( 'No sales yet.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div><div class="dm-panel"><h2>' . esc_html__( 'Top products', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Product', 'digimarket' ) . '</th><th>' . esc_html__( 'Sales', 'digimarket' ) . '</th><th>' . esc_html__( 'Revenue', 'digimarket' ) . '</th></tr></thead><tbody>';
	foreach ( $top_products as $p ) {
		echo '<tr><td><a href="' . esc_url( get_edit_post_link( $p->product_id ) ? get_edit_post_link( $p->product_id ) : '#' ) . '">' . esc_html( $p->product_title ) . '</a></td><td>' . (int) $p->c . '</td><td>' . esc_html( dm_money( $p->r ) ) . '</td></tr>';
	}
	if ( ! $top_products ) {
		echo '<tr><td colspan="3">' . esc_html__( 'No sales yet.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div></div></div>';
}

/* -------------------------------------------------------------------------
 * Sellers
 * ---------------------------------------------------------------------- */

function dm_admin_sellers() {
	if ( ! empty( $_GET['seller'] ) ) {
		dm_admin_seller_detail( absint( $_GET['seller'] ) );
		return;
	}
	$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$args   = array(
		'meta_query' => array( array( 'key' => 'dm_seller_status', 'value' => $status ? array( $status ) : array( 'draft', 'pending', 'active', 'suspended', 'rejected' ), 'compare' => 'IN' ) ),
		'number'     => 50,
		'paged'      => dm_get_page_num(),
		'orderby'    => 'registered',
		'order'      => 'DESC',
	);
	if ( $search ) {
		$args['search'] = '*' . $search . '*';
	}
	$q = new WP_User_Query( $args );
	dm_admin_header( __( 'Sellers', 'digimarket' ) );
	echo '<ul class="subsubsub">';
	foreach ( array( '' => __( 'All', 'digimarket' ), 'pending' => __( 'Pending approval', 'digimarket' ), 'active' => __( 'Active', 'digimarket' ), 'suspended' => __( 'Suspended', 'digimarket' ), 'draft' => __( 'Onboarding', 'digimarket' ), 'rejected' => __( 'Rejected', 'digimarket' ) ) as $k => $label ) {
		echo '<li><a class="' . ( $k === $status ? 'current' : '' ) . '" href="' . esc_url( dm_admin_url( 'dm-sellers', $k ? array( 'status' => $k ) : array() ) ) . '">' . esc_html( $label ) . '</a> | </li>';
	}
	echo '</ul><form class="search-box" method="get"><input type="hidden" name="page" value="dm-sellers"><input type="search" name="s" value="' . esc_attr( $search ) . '"><button class="button">' . esc_html__( 'Search', 'digimarket' ) . '</button></form>';
	echo '<table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Shop', 'digimarket' ) . '</th><th>' . esc_html__( 'Owner', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th>' . esc_html__( 'KYC', 'digimarket' ) . '</th><th>' . esc_html__( 'Commission', 'digimarket' ) . '</th><th>' . esc_html__( 'Products', 'digimarket' ) . '</th><th>' . esc_html__( 'Actions', 'digimarket' ) . '</th></tr></thead><tbody>';
	foreach ( $q->get_results() as $u ) {
		$st  = dm_seller_status( $u->ID );
		$ov  = get_user_meta( $u->ID, 'dm_commission_override', true );
		echo '<tr><td><strong><a href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'seller' => $u->ID ) ) ) . '">' . esc_html( dm_shop_name( $u->ID ) ) . '</a></strong><br><small>/store/' . esc_html( get_user_meta( $u->ID, 'dm_shop_slug', true ) ) . '</small></td>';
		echo '<td>' . esc_html( $u->display_name ) . '<br><small>' . esc_html( $u->user_email ) . '</small></td>';
		echo '<td>' . dm_status_badge( $st ) . '</td><td>' . dm_status_badge( get_user_meta( $u->ID, 'dm_kyc_status', true ) ? get_user_meta( $u->ID, 'dm_kyc_status', true ) : 'pending' ) . '</td>'; // phpcs:ignore
		echo '<td>' . ( '' !== $ov ? esc_html( $ov . '% ' . __( '(override)', 'digimarket' ) ) : esc_html__( 'Default', 'digimarket' ) ) . '</td>';
		echo '<td>' . (int) dm_seller_product_count( $u->ID ) . '</td><td class="dm-actions">';
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			if ( 'pending' === $st || 'rejected' === $st || 'draft' === $st ) {
				echo '<a class="button button-primary button-small" href="' . esc_url( dm_admin_action_url( 'seller_status', array( 'uid' => $u->ID, 'to' => 'active' ) ) ) . '">' . esc_html__( 'Approve', 'digimarket' ) . '</a> ';
			}
			if ( 'pending' === $st ) {
				echo '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'seller_status', array( 'uid' => $u->ID, 'to' => 'rejected' ) ) ) . '">' . esc_html__( 'Reject', 'digimarket' ) . '</a> ';
			}
			if ( 'active' === $st ) {
				echo '<a class="button button-small dm-danger" onclick="return confirm(\'' . esc_js( __( 'Suspend this seller? All products will be unpublished.', 'digimarket' ) ) . '\')" href="' . esc_url( dm_admin_action_url( 'seller_status', array( 'uid' => $u->ID, 'to' => 'suspended' ) ) ) . '">' . esc_html__( 'Suspend', 'digimarket' ) . '</a> ';
			}
			if ( 'suspended' === $st ) {
				echo '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'seller_status', array( 'uid' => $u->ID, 'to' => 'active' ) ) ) . '">' . esc_html__( 'Reactivate', 'digimarket' ) . '</a> ';
			}
		}
		echo '<a class="button button-small" href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'seller' => $u->ID ) ) ) . '">' . esc_html__( 'View', 'digimarket' ) . '</a></td></tr>';
	}
	if ( ! $q->get_results() ) {
		echo '<tr><td colspan="7">' . esc_html__( 'No sellers found.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table>' . dm_pagination( $q->get_total(), 50, dm_get_page_num(), remove_query_arg( 'pg' ) ) . '</div>'; // phpcs:ignore
}

function dm_admin_seller_detail( $uid ) {
	global $wpdb;
	$user = get_userdata( $uid );
	if ( ! $user ) {
		echo '<div class="wrap"><p>' . esc_html__( 'Seller not found.', 'digimarket' ) . '</p></div>';
		return;
	}
	$stats = dm_seller_stats( $uid );
	dm_admin_header( dm_shop_name( $uid ), ' <a class="page-title-action" href="' . esc_url( dm_store_url( $uid ) ) . '" target="_blank">' . esc_html__( 'View shop', 'digimarket' ) . '</a> <a class="page-title-action" href="' . esc_url( dm_admin_url( 'dm-sellers' ) ) . '">' . esc_html__( '← All sellers', 'digimarket' ) . '</a>' );
	echo '<div class="dm-stats">';
	dm_stat_card( __( 'Revenue', 'digimarket' ), dm_money( $stats['revenue'] ) );
	dm_stat_card( __( 'Seller net', 'digimarket' ), dm_money( $stats['net'] ) );
	dm_stat_card( __( 'Commission', 'digimarket' ), dm_money( $stats['revenue'] - $stats['net'] ), 'accent' );
	dm_stat_card( __( 'Orders', 'digimarket' ), number_format_i18n( $stats['orders'] ) );
	dm_stat_card( __( 'Products', 'digimarket' ), number_format_i18n( $stats['products'] ) );
	dm_stat_card( __( 'Pending payout', 'digimarket' ), dm_money( $stats['pending'] ) );
	echo '</div><div class="dm-grid2"><div class="dm-panel"><h2>' . esc_html__( 'Profile & status', 'digimarket' ) . '</h2><table class="form-table">';
	$addr = (array) get_user_meta( $uid, 'dm_address', true );
	$rows = array(
		__( 'Status', 'digimarket' )        => dm_status_badge( dm_seller_status( $uid ) ),
		__( 'Owner', 'digimarket' )         => esc_html( $user->display_name . ' <' . $user->user_email . '>' ),
		__( 'Phone', 'digimarket' )         => esc_html( get_user_meta( $uid, 'dm_phone', true ) ),
		__( 'Legal name', 'digimarket' )    => esc_html( get_user_meta( $uid, 'dm_legal_name', true ) ),
		__( 'Business type', 'digimarket' ) => esc_html( get_user_meta( $uid, 'dm_business_type', true ) ),
		__( 'PAN', 'digimarket' )           => esc_html( dm_mask( dm_decrypt( get_user_meta( $uid, 'dm_pan', true ) ), 4 ) ),
		__( 'Bank', 'digimarket' )          => esc_html( ( get_user_meta( $uid, 'dm_bank_last4', true ) ? '•••• ' . get_user_meta( $uid, 'dm_bank_last4', true ) . ' / ' . get_user_meta( $uid, 'dm_ifsc', true ) : '—' ) ),
		__( 'UPI', 'digimarket' )           => esc_html( get_user_meta( $uid, 'dm_upi', true ) ),
		__( 'Address', 'digimarket' )       => esc_html( implode( ', ', array_filter( $addr ) ) ),
		__( 'Razorpay account', 'digimarket' ) => esc_html( get_user_meta( $uid, 'dm_rzp_account_id', true ) ? get_user_meta( $uid, 'dm_rzp_account_id', true ) : '—' ) . ' ' . dm_status_badge( get_user_meta( $uid, 'dm_rzp_activation', true ) ? get_user_meta( $uid, 'dm_rzp_activation', true ) : 'pending' ),
		__( 'KYC error', 'digimarket' )     => esc_html( get_user_meta( $uid, 'dm_kyc_error', true ) ),
	);
	foreach ( $rows as $k => $v ) {
		echo '<tr><th>' . esc_html( $k ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore
	}
	echo '</table></div>';

	if ( current_user_can( 'dm_manage_marketplace' ) ) {
		echo '<div class="dm-panel"><h2>' . esc_html__( 'Admin controls', 'digimarket' ) . '</h2>';
		dm_admin_form_open( 'seller_update' );
		echo '<input type="hidden" name="uid" value="' . (int) $uid . '"><table class="form-table">';
		echo '<tr><th>' . esc_html__( 'Seller status', 'digimarket' ) . '</th><td><select name="status">';
		foreach ( array( 'draft', 'pending', 'active', 'suspended', 'rejected' ) as $s ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( dm_seller_status( $uid ), $s, false ) . '>' . esc_html( ucfirst( $s ) ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr><th>' . esc_html__( 'Commission override (%)', 'digimarket' ) . '</th><td><input type="number" step="0.01" min="0" max="100" name="commission" value="' . esc_attr( get_user_meta( $uid, 'dm_commission_override', true ) ) . '" placeholder="' . esc_attr__( 'Default', 'digimarket' ) . '"><p class="description">' . esc_html__( 'Blank = use category/global rate.', 'digimarket' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'KYC status', 'digimarket' ) . '</th><td><select name="kyc">';
		foreach ( array( 'pending', 'verified', 'rejected' ) as $s ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( get_user_meta( $uid, 'dm_kyc_status', true ), $s, false ) . '>' . esc_html( ucfirst( $s ) ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr><th>' . esc_html__( 'Razorpay linked account ID', 'digimarket' ) . '</th><td><input type="text" class="regular-text" name="rzp_account" value="' . esc_attr( get_user_meta( $uid, 'dm_rzp_account_id', true ) ) . '" placeholder="acc_XXXXXXXXXXXX"><p class="description">' . esc_html__( 'Created automatically; paste manually if you created it in the Razorpay dashboard.', 'digimarket' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'Featured shop', 'digimarket' ) . '</th><td><label><input type="checkbox" name="featured" value="1"' . checked( in_array( $uid, array_map( 'intval', (array) dm_opt( 'featured_shops', array() ) ), true ), true, false ) . '> ' . esc_html__( 'Promote on the homepage', 'digimarket' ) . '</label></td></tr>';
		echo '</table><p><button class="button button-primary">' . esc_html__( 'Save', 'digimarket' ) . '</button> ';
		if ( dm_rzp_ready() ) {
			echo '<a class="button" href="' . esc_url( dm_admin_action_url( 'seller_rzp_sync', array( 'uid' => $uid ) ) ) . '">' . esc_html__( 'Create / sync Razorpay linked account', 'digimarket' ) . '</a>';
		}
		echo '</p></form></div>';
	}
	echo '</div>';

	// Products.
	$products = get_posts( array( 'post_type' => 'dm_product', 'author' => $uid, 'post_status' => array( 'publish', 'draft', 'dm_unpublished', 'dm_deleted' ), 'numberposts' => 100 ) );
	echo '<div class="dm-panel"><h2>' . esc_html__( 'Products', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Title', 'digimarket' ) . '</th><th>' . esc_html__( 'Price', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th>' . esc_html__( 'Sales', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $products as $p ) {
		echo '<tr><td>' . esc_html( $p->post_title ) . '</td><td>' . esc_html( dm_money( dm_product_price( $p->ID ) ) ) . '</td><td>' . dm_status_badge( $p->post_status ) . '</td><td>' . (int) get_post_meta( $p->ID, '_dm_sales', true ) . '</td><td><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html__( 'Edit', 'digimarket' ) . '</a></td></tr>'; // phpcs:ignore
	}
	if ( ! $products ) {
		echo '<tr><td colspan="5">' . esc_html__( 'No products.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';

	// Orders.
	$orders = dm_seller_orders_query( $uid, 30, 0 );
	echo '<div class="dm-panel"><h2>' . esc_html__( 'Recent orders', 'digimarket' ) . '</h2>';
	dm_admin_items_table( $orders['items'] );
	echo '</div></div>';
}

function dm_admin_items_table( $items ) {
	echo '<table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Order', 'digimarket' ) . '</th><th>' . esc_html__( 'Date', 'digimarket' ) . '</th><th>' . esc_html__( 'Buyer', 'digimarket' ) . '</th><th>' . esc_html__( 'Product', 'digimarket' ) . '</th><th>' . esc_html__( 'Seller', 'digimarket' ) . '</th><th>' . esc_html__( 'Gross', 'digimarket' ) . '</th><th>' . esc_html__( 'Commission', 'digimarket' ) . '</th><th>' . esc_html__( 'Seller net', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th>' . esc_html__( 'Transfer', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $items as $r ) {
		echo '<tr><td><a href="' . esc_url( dm_admin_url( 'dm-transactions', array( 'order' => $r->order_id ) ) ) . '">' . esc_html( dm_order_number( $r->order_id ) ) . '</a></td>';
		echo '<td>' . esc_html( mysql2date( 'M j, Y H:i', $r->created_at ) ) . '</td><td>' . esc_html( $r->buyer_name ) . '</td><td>' . esc_html( $r->product_title ) . '</td><td>' . esc_html( dm_shop_name( $r->seller_id ) ) . '</td>';
		echo '<td>' . esc_html( dm_money( $r->price_at_purchase ) ) . '</td><td>' . esc_html( dm_money( $r->commission_amount ) ) . ' <small>(' . esc_html( (float) $r->commission_percent_applied ) . '%)</small></td><td>' . esc_html( dm_money( $r->seller_net_amount ) ) . '</td>';
		echo '<td>' . dm_status_badge( $r->item_status ) . '</td><td>' . dm_status_badge( $r->transfer_status ) . ( $r->transfer_id ? '<br><small>' . esc_html( $r->transfer_id ) . '</small>' : '' ) . '</td><td>'; // phpcs:ignore
		if ( 'paid' === $r->item_status && current_user_can( 'dm_manage_marketplace' ) ) {
			echo '<a class="button button-small" onclick="return confirm(\'' . esc_js( __( 'Refund this item? Both the seller share and the commission will be reversed.', 'digimarket' ) ) . '\')" href="' . esc_url( dm_admin_action_url( 'refund_item', array( 'item' => $r->id ) ) ) . '">' . esc_html__( 'Refund', 'digimarket' ) . '</a>';
		}
		echo '</td></tr>';
	}
	if ( ! $items ) {
		echo '<tr><td colspan="11">' . esc_html__( 'No transactions found.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

/* -------------------------------------------------------------------------
 * Buyers
 * ---------------------------------------------------------------------- */

function dm_admin_buyers() {
	global $wpdb;
	if ( ! empty( $_GET['buyer'] ) ) {
		$uid  = absint( $_GET['buyer'] );
		$user = get_userdata( $uid );
		dm_admin_header( $user ? $user->display_name : __( 'Buyer', 'digimarket' ), ' <a class="page-title-action" href="' . esc_url( dm_admin_url( 'dm-buyers' ) ) . '">' . esc_html__( '← All buyers', 'digimarket' ) . '</a>' );
		$items = $wpdb->get_results( $wpdb->prepare( 'SELECT i.*, o.buyer_name, o.buyer_email, o.created_at FROM ' . dm_table( 'order_items' ) . ' i INNER JOIN ' . dm_table( 'orders' ) . ' o ON o.id = i.order_id WHERE o.buyer_id = %d ORDER BY i.id DESC LIMIT 200', $uid ) );
		dm_admin_items_table( $items );
		echo '</div>';
		return;
	}
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$args   = array( 'number' => 50, 'paged' => dm_get_page_num(), 'orderby' => 'registered', 'order' => 'DESC', 'role__not_in' => array( 'administrator' ) );
	if ( $search ) {
		$args['search'] = '*' . $search . '*';
	}
	$q = new WP_User_Query( $args );
	dm_admin_header( __( 'Buyers', 'digimarket' ) );
	echo '<form class="search-box" method="get"><input type="hidden" name="page" value="dm-buyers"><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Name or email', 'digimarket' ) . '"><button class="button">' . esc_html__( 'Search', 'digimarket' ) . '</button></form>';
	echo '<table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Name', 'digimarket' ) . '</th><th>' . esc_html__( 'Email', 'digimarket' ) . '</th><th>' . esc_html__( 'Verified', 'digimarket' ) . '</th><th>' . esc_html__( 'Orders', 'digimarket' ) . '</th><th>' . esc_html__( 'Spent', 'digimarket' ) . '</th><th>' . esc_html__( 'Joined', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $q->get_results() as $u ) {
		$s = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(*) c, COALESCE(SUM(order_total),0) t FROM ' . dm_table( 'orders' ) . " WHERE buyer_id = %d AND payment_status IN ('paid','partially_refunded')", $u->ID ) );
		$susp = dm_user_suspended( $u->ID );
		echo '<tr><td><a href="' . esc_url( dm_admin_url( 'dm-buyers', array( 'buyer' => $u->ID ) ) ) . '">' . esc_html( $u->display_name ) . '</a>' . ( dm_is_seller( $u->ID ) ? ' <span class="dm-badge dm-badge-info">' . esc_html__( 'Seller', 'digimarket' ) . '</span>' : '' ) . '</td><td>' . esc_html( $u->user_email ) . '</td>';
		echo '<td>' . ( dm_email_verified( $u->ID ) ? '✓' : '<a href="' . esc_url( dm_admin_action_url( 'verify_email', array( 'uid' => $u->ID ) ) ) . '">' . esc_html__( 'Mark verified', 'digimarket' ) . '</a>' ) . '</td>';
		echo '<td>' . (int) $s->c . '</td><td>' . esc_html( dm_money( $s->t ) ) . '</td><td>' . esc_html( mysql2date( 'M j, Y', $u->user_registered ) ) . '</td><td>' . dm_status_badge( $susp ? 'suspended' : 'active' ) . '</td><td>'; // phpcs:ignore
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			echo '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'buyer_suspend', array( 'uid' => $u->ID, 'to' => $susp ? 0 : 1 ) ) ) . '">' . ( $susp ? esc_html__( 'Unsuspend', 'digimarket' ) : esc_html__( 'Suspend', 'digimarket' ) ) . '</a>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>' . dm_pagination( $q->get_total(), 50, dm_get_page_num(), remove_query_arg( 'pg' ) ) . '</div>'; // phpcs:ignore
}

/* -------------------------------------------------------------------------
 * Moderation
 * ---------------------------------------------------------------------- */

function dm_admin_moderation() {
	global $wpdb;
	dm_admin_header( __( 'Product moderation', 'digimarket' ) );
	$reports = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'reports' ) . " WHERE status = 'open' ORDER BY id DESC LIMIT 100" ); // phpcs:ignore
	echo '<div class="dm-panel"><h2>' . esc_html__( 'Reported products', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Product', 'digimarket' ) . '</th><th>' . esc_html__( 'Shop', 'digimarket' ) . '</th><th>' . esc_html__( 'Reason', 'digimarket' ) . '</th><th>' . esc_html__( 'Reported by', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $reports as $r ) {
		$u = get_userdata( $r->user_id );
		echo '<tr><td><a href="' . esc_url( get_permalink( $r->product_id ) ) . '" target="_blank">' . esc_html( get_the_title( $r->product_id ) ) . '</a></td><td>' . esc_html( dm_shop_name( get_post_field( 'post_author', $r->product_id ) ) ) . '</td><td>' . esc_html( $r->reason ) . '</td><td>' . esc_html( $u ? $u->user_email : '—' ) . '</td><td class="dm-actions">';
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			echo '<a class="button button-small dm-danger" href="' . esc_url( dm_admin_action_url( 'force_unpublish', array( 'pid' => $r->product_id, 'report' => $r->id ) ) ) . '">' . esc_html__( 'Force-unpublish', 'digimarket' ) . '</a> <a class="button button-small" href="' . esc_url( dm_admin_action_url( 'dismiss_report', array( 'report' => $r->id ) ) ) . '">' . esc_html__( 'Dismiss', 'digimarket' ) . '</a>';
		}
		echo '</td></tr>';
	}
	if ( ! $reports ) {
		echo '<tr><td colspan="5">' . esc_html__( 'No open reports. 🎉', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';

	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$q      = new WP_Query( array( 'post_type' => 'dm_product', 'post_status' => array( 'publish', 'draft', 'dm_unpublished' ), 's' => $search, 'posts_per_page' => 30, 'paged' => dm_get_page_num() ) );
	echo '<div class="dm-panel"><h2>' . esc_html__( 'All products across shops', 'digimarket' ) . '</h2><form method="get"><input type="hidden" name="page" value="dm-moderation"><input type="search" name="s" value="' . esc_attr( $search ) . '"><button class="button">' . esc_html__( 'Search', 'digimarket' ) . '</button></form><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Product', 'digimarket' ) . '</th><th>' . esc_html__( 'Shop', 'digimarket' ) . '</th><th>' . esc_html__( 'Price', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $q->posts as $p ) {
		$forced = get_post_meta( $p->ID, '_dm_forced', true );
		echo '<tr><td><a href="' . esc_url( get_permalink( $p ) ) . '" target="_blank">' . esc_html( $p->post_title ) . '</a></td><td>' . esc_html( dm_shop_name( $p->post_author ) ) . '</td><td>' . esc_html( dm_money( dm_product_price( $p->ID ) ) ) . '</td><td>' . dm_status_badge( $p->post_status ) . ( $forced ? ' <span class="dm-badge dm-badge-danger">' . esc_html__( 'Locked', 'digimarket' ) . '</span>' : '' ) . '</td><td>'; // phpcs:ignore
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			echo $forced ? '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'unlock_product', array( 'pid' => $p->ID ) ) ) . '">' . esc_html__( 'Unlock', 'digimarket' ) . '</a>' : '<a class="button button-small dm-danger" href="' . esc_url( dm_admin_action_url( 'force_unpublish', array( 'pid' => $p->ID ) ) ) . '">' . esc_html__( 'Force-unpublish', 'digimarket' ) . '</a>'; // phpcs:ignore
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>' . dm_pagination( $q->found_posts, 30, dm_get_page_num(), remove_query_arg( 'pg' ) ) . '</div></div>'; // phpcs:ignore
}

/* -------------------------------------------------------------------------
 * Transactions (ledger)
 * ---------------------------------------------------------------------- */

function dm_admin_ledger_query( $limit, $offset ) {
	global $wpdb;
	$where = array( "i.item_status <> 'pending'" );
	if ( ! empty( $_GET['s'] ) ) {
		$s       = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) . '%';
		$num     = absint( preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) );
		$where[] = $wpdb->prepare( '(o.buyer_email LIKE %s OR o.buyer_name LIKE %s OR i.product_title LIKE %s OR o.razorpay_payment_id LIKE %s OR o.razorpay_order_id LIKE %s OR i.transfer_id LIKE %s OR o.id = %d)', $s, $s, $s, $s, $s, $s, $num );
	}
	if ( ! empty( $_GET['status'] ) ) {
		$where[] = $wpdb->prepare( 'i.item_status = %s', sanitize_key( $_GET['status'] ) );
	}
	if ( ! empty( $_GET['disputed'] ) ) {
		$where[] = 'o.disputed = 1';
	}
	if ( ! empty( $_GET['order'] ) ) {
		$where[] = $wpdb->prepare( 'o.id = %d', absint( $_GET['order'] ) );
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
	$items = $wpdb->get_results( $wpdb->prepare( "SELECT i.*, o.buyer_name, o.buyer_email, o.created_at, o.razorpay_payment_id, o.razorpay_order_id, o.disputed, o.gateway FROM $from WHERE $w ORDER BY i.id DESC LIMIT %d OFFSET %d", $limit, $offset ) ); // phpcs:ignore
	return array( 'total' => $total, 'items' => $items );
}

function dm_admin_transactions() {
	$pg  = dm_get_page_num();
	$res = dm_admin_ledger_query( 50, ( $pg - 1 ) * 50 );
	dm_admin_header( __( 'Transaction ledger', 'digimarket' ), ' <a class="page-title-action" href="' . esc_url( wp_nonce_url( add_query_arg( array_merge( $_GET, array( 'action' => 'dm_admin', 'do' => 'export_ledger' ) ), admin_url( 'admin-post.php' ) ), 'dm_admin_export_ledger' ) ) . '">' . esc_html__( 'Export CSV', 'digimarket' ) . '</a>' ); // phpcs:ignore
	echo '<form method="get" class="dm-filters"><input type="hidden" name="page" value="dm-transactions">';
	echo '<input type="search" name="s" placeholder="' . esc_attr__( 'Order #, email, product, pay_/trf_ ID', 'digimarket' ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ) . '"> ';
	echo '<select name="status"><option value="">' . esc_html__( 'Any status', 'digimarket' ) . '</option>';
	foreach ( array( 'paid', 'failed', 'refunded' ) as $s ) {
		echo '<option value="' . esc_attr( $s ) . '"' . selected( $_GET['status'] ?? '', $s, false ) . '>' . esc_html( ucfirst( $s ) ) . '</option>';
	}
	echo '</select> <input type="date" name="from" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) ) ) . '"> <input type="date" name="to" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) ) ) . '"> <label><input type="checkbox" name="disputed" value="1"' . checked( ! empty( $_GET['disputed'] ), true, false ) . '> ' . esc_html__( 'Disputed only', 'digimarket' ) . '</label> <button class="button">' . esc_html__( 'Filter', 'digimarket' ) . '</button></form>';
	dm_admin_items_table( $res['items'] );
	if ( ! empty( $_GET['order'] ) ) {
		$order = dm_get_order( absint( $_GET['order'] ) );
		if ( $order ) {
			echo '<div class="dm-panel"><h2>' . esc_html( dm_order_number( $order ) ) . '</h2><p>' . esc_html__( 'Gateway:', 'digimarket' ) . ' <code>' . esc_html( $order->gateway ) . '</code> · Razorpay order <code>' . esc_html( $order->razorpay_order_id ) . '</code> · payment <code>' . esc_html( $order->razorpay_payment_id ) . '</code> · ' . esc_html__( 'IP', 'digimarket' ) . ' ' . esc_html( $order->ip ) . '</p><p>' . esc_html__( 'Subtotal', 'digimarket' ) . ' ' . esc_html( dm_money( $order->subtotal ) ) . ' · ' . esc_html__( 'Discount', 'digimarket' ) . ' ' . esc_html( dm_money( $order->discount ) ) . ' ' . esc_html( $order->coupon_code ) . ' · <strong>' . esc_html__( 'Total', 'digimarket' ) . ' ' . esc_html( dm_money( $order->order_total ) ) . '</strong> ' . dm_status_badge( $order->payment_status ) . ( $order->disputed ? ' <span class="dm-badge dm-badge-danger">' . esc_html__( 'Disputed', 'digimarket' ) . '</span>' : '' ) . '</p>'; // phpcs:ignore
			if ( $order->note ) {
				echo '<p><em>' . esc_html( $order->note ) . '</em></p>';
			}
			echo '<p><a class="button" href="' . esc_url( dm_url( 'invoice', $order->id ) ) . '" target="_blank">' . esc_html__( 'Invoice', 'digimarket' ) . '</a></p></div>';
		}
	}
	echo dm_pagination( $res['total'], 50, $pg, remove_query_arg( 'pg' ) ) . '</div>'; // phpcs:ignore
}

/* -------------------------------------------------------------------------
 * Payouts
 * ---------------------------------------------------------------------- */

function dm_admin_payouts() {
	global $wpdb;
	$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	$where  = $status ? $wpdb->prepare( 'WHERE p.status = %s', $status ) : '';
	$rows   = $wpdb->get_results( 'SELECT p.*, i.order_id, i.product_title, i.transfer_note FROM ' . dm_table( 'payouts' ) . ' p LEFT JOIN ' . dm_table( 'order_items' ) . " i ON i.id = p.order_item_id $where ORDER BY p.id DESC LIMIT 200" ); // phpcs:ignore
	dm_admin_header( __( 'Payout oversight', 'digimarket' ) );
	echo '<ul class="subsubsub">';
	foreach ( array( '' => __( 'All', 'digimarket' ), 'pending' => __( 'Pending', 'digimarket' ), 'settled' => __( 'Settled', 'digimarket' ), 'failed' => __( 'Failed', 'digimarket' ), 'reversed' => __( 'Reversed', 'digimarket' ) ) as $k => $l ) {
		echo '<li><a class="' . ( $k === $status ? 'current' : '' ) . '" href="' . esc_url( dm_admin_url( 'dm-payouts', $k ? array( 'status' => $k ) : array() ) ) . '">' . esc_html( $l ) . '</a> | </li>';
	}
	echo '</ul><table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Seller', 'digimarket' ) . '</th><th>' . esc_html__( 'KYC / linked account', 'digimarket' ) . '</th><th>' . esc_html__( 'Order', 'digimarket' ) . '</th><th>' . esc_html__( 'Amount', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th>' . esc_html__( 'Reference', 'digimarket' ) . '</th><th>' . esc_html__( 'Note', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		echo '<tr><td><a href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'seller' => $r->seller_id ) ) ) . '">' . esc_html( dm_shop_name( $r->seller_id ) ) . '</a></td>';
		echo '<td>' . dm_status_badge( get_user_meta( $r->seller_id, 'dm_kyc_status', true ) ? get_user_meta( $r->seller_id, 'dm_kyc_status', true ) : 'pending' ) . '<br><small>' . esc_html( get_user_meta( $r->seller_id, 'dm_rzp_account_id', true ) ) . '</small></td>'; // phpcs:ignore
		echo '<td>' . esc_html( dm_order_number( $r->order_id ) ) . '<br><small>' . esc_html( $r->product_title ) . '</small></td><td>' . esc_html( dm_money( $r->amount ) ) . '</td><td>' . dm_status_badge( $r->status ) . '</td><td><code>' . esc_html( $r->reference ) . '</code></td><td>' . esc_html( $r->note ? $r->note : $r->transfer_note ) . '</td><td>'; // phpcs:ignore
		if ( 'failed' === $r->status && current_user_can( 'dm_manage_marketplace' ) ) {
			echo '<a class="button button-small button-primary" href="' . esc_url( dm_admin_action_url( 'retry_transfer', array( 'order' => $r->order_id ) ) ) . '">' . esc_html__( 'Retry transfer', 'digimarket' ) . '</a> <a class="button button-small" href="' . esc_url( dm_admin_action_url( 'payout_resolved', array( 'payout' => $r->id ) ) ) . '">' . esc_html__( 'Mark paid manually', 'digimarket' ) . '</a>';
		}
		echo '</td></tr>';
	}
	if ( ! $rows ) {
		echo '<tr><td colspan="8">' . esc_html__( 'No payouts.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/* -------------------------------------------------------------------------
 * Reviews
 * ---------------------------------------------------------------------- */

function dm_admin_reviews() {
	global $wpdb;
	$flagged = ! empty( $_GET['flagged'] );
	$rows    = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'reviews' ) . ( $flagged ? ' WHERE flagged = 1' : '' ) . ' ORDER BY flagged DESC, id DESC LIMIT 200' ); // phpcs:ignore
	dm_admin_header( __( 'Reviews', 'digimarket' ) );
	echo '<ul class="subsubsub"><li><a class="' . ( $flagged ? '' : 'current' ) . '" href="' . esc_url( dm_admin_url( 'dm-reviews' ) ) . '">' . esc_html__( 'All', 'digimarket' ) . '</a> | </li><li><a class="' . ( $flagged ? 'current' : '' ) . '" href="' . esc_url( dm_admin_url( 'dm-reviews', array( 'flagged' => 1 ) ) ) . '">' . esc_html__( 'Flagged', 'digimarket' ) . '</a></li></ul>';
	echo '<table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Product', 'digimarket' ) . '</th><th>' . esc_html__( 'Buyer', 'digimarket' ) . '</th><th>' . esc_html__( 'Rating', 'digimarket' ) . '</th><th>' . esc_html__( 'Comment', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$u = get_userdata( $r->buyer_id );
		echo '<tr><td>' . esc_html( get_the_title( $r->product_id ) ) . '</td><td>' . esc_html( $u ? $u->display_name : '—' ) . '</td><td>' . str_repeat( '★', (int) $r->rating ) . '</td><td>' . esc_html( $r->comment ) . ( $r->flagged ? '<br><span class="dm-badge dm-badge-warning">' . esc_html__( 'Flagged:', 'digimarket' ) . ' ' . esc_html( $r->flag_reason ) . '</span>' : '' ) . '</td><td>' . dm_status_badge( $r->status ) . '</td><td class="dm-actions">'; // phpcs:ignore
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			echo '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'review_status', array( 'id' => $r->id, 'to' => 'approved' === $r->status ? 'hidden' : 'approved' ) ) ) . '">' . ( 'approved' === $r->status ? esc_html__( 'Hide', 'digimarket' ) : esc_html__( 'Approve', 'digimarket' ) ) . '</a> ';
			if ( $r->flagged ) {
				echo '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'review_unflag', array( 'id' => $r->id ) ) ) . '">' . esc_html__( 'Keep & unflag', 'digimarket' ) . '</a> ';
			}
			echo '<a class="button button-small dm-danger" onclick="return confirm(\'' . esc_js( __( 'Delete review?', 'digimarket' ) ) . '\')" href="' . esc_url( dm_admin_action_url( 'review_delete', array( 'id' => $r->id ) ) ) . '">' . esc_html__( 'Delete', 'digimarket' ) . '</a>';
		}
		echo '</td></tr>';
	}
	if ( ! $rows ) {
		echo '<tr><td colspan="6">' . esc_html__( 'No reviews.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/* -------------------------------------------------------------------------
 * Coupons
 * ---------------------------------------------------------------------- */

function dm_admin_coupons() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'coupons' ) . ' ORDER BY id DESC' ); // phpcs:ignore
	dm_admin_header( __( 'Coupons & promotions', 'digimarket' ) );
	if ( current_user_can( 'dm_manage_marketplace' ) ) {
		echo '<div class="dm-panel"><h2>' . esc_html__( 'Create coupon', 'digimarket' ) . '</h2>';
		dm_admin_form_open( 'coupon_create' );
		$sellers = get_users( array( 'meta_key' => 'dm_seller_status', 'meta_value' => 'active', 'fields' => array( 'ID' ) ) );
		echo '<div class="dm-form-row"><label>' . esc_html__( 'Code', 'digimarket' ) . '<input name="code" required pattern="[A-Za-z0-9_\-]+" placeholder="LAUNCH20"></label>';
		echo '<label>' . esc_html__( 'Type', 'digimarket' ) . '<select name="discount_type"><option value="percent">%</option><option value="flat">' . esc_html( dm_opt( 'currency_symbol' ) ) . ' ' . esc_html__( 'flat', 'digimarket' ) . '</option></select></label>';
		echo '<label>' . esc_html__( 'Value', 'digimarket' ) . '<input type="number" step="0.01" min="0" name="discount_value" required></label>';
		echo '<label>' . esc_html__( 'Seller (optional)', 'digimarket' ) . '<select name="seller_id"><option value="0">' . esc_html__( 'Platform-wide', 'digimarket' ) . '</option>';
		foreach ( $sellers as $s ) {
			echo '<option value="' . (int) $s->ID . '">' . esc_html( dm_shop_name( $s->ID ) ) . '</option>';
		}
		echo '</select></label><label>' . esc_html__( 'Min order', 'digimarket' ) . '<input type="number" step="0.01" min="0" name="min_order" value="0"></label>';
		echo '<label>' . esc_html__( 'Valid from', 'digimarket' ) . '<input type="date" name="valid_from"></label><label>' . esc_html__( 'Valid until', 'digimarket' ) . '<input type="date" name="valid_until"></label>';
		echo '<label>' . esc_html__( 'Usage limit (0 = ∞)', 'digimarket' ) . '<input type="number" min="0" name="usage_limit" value="0"></label></div><p><button class="button button-primary">' . esc_html__( 'Create coupon', 'digimarket' ) . '</button></p></form></div>';
	}
	echo '<table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Code', 'digimarket' ) . '</th><th>' . esc_html__( 'Discount', 'digimarket' ) . '</th><th>' . esc_html__( 'Scope', 'digimarket' ) . '</th><th>' . esc_html__( 'Min order', 'digimarket' ) . '</th><th>' . esc_html__( 'Validity', 'digimarket' ) . '</th><th>' . esc_html__( 'Used', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $c ) {
		echo '<tr><td><code>' . esc_html( $c->code ) . '</code></td><td>' . esc_html( 'percent' === $c->discount_type ? (float) $c->discount_value . '%' : dm_money( $c->discount_value ) ) . '</td><td>' . esc_html( $c->seller_id ? dm_shop_name( $c->seller_id ) : __( 'Platform-wide', 'digimarket' ) ) . '</td><td>' . esc_html( dm_money( $c->min_order ) ) . '</td><td>' . esc_html( ( $c->valid_from ? $c->valid_from : '…' ) . ' → ' . ( $c->valid_until ? $c->valid_until : '…' ) ) . '</td><td>' . (int) $c->times_used . ' / ' . ( $c->usage_limit ? (int) $c->usage_limit : '∞' ) . '</td><td>' . dm_status_badge( $c->active ? 'active' : 'closed' ) . '</td><td>'; // phpcs:ignore
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			echo '<a class="button button-small" href="' . esc_url( dm_admin_action_url( 'coupon_toggle', array( 'id' => $c->id ) ) ) . '">' . ( $c->active ? esc_html__( 'Disable', 'digimarket' ) : esc_html__( 'Enable', 'digimarket' ) ) . '</a> <a class="button button-small dm-danger" href="' . esc_url( dm_admin_action_url( 'coupon_delete', array( 'id' => $c->id ) ) ) . '">' . esc_html__( 'Delete', 'digimarket' ) . '</a>';
		}
		echo '</td></tr>';
	}
	if ( ! $rows ) {
		echo '<tr><td colspan="8">' . esc_html__( 'No coupons yet.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/* -------------------------------------------------------------------------
 * Tickets
 * ---------------------------------------------------------------------- */

function dm_admin_tickets() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'tickets' ) . " ORDER BY status = 'open' DESC, id DESC LIMIT 200" ); // phpcs:ignore
	dm_admin_header( __( 'Support tickets', 'digimarket' ) );
	foreach ( $rows as $t ) {
		$u = get_userdata( $t->user_id );
		echo '<div class="dm-panel"><h3 style="margin-top:0">' . esc_html( $t->subject ) . ' ' . dm_status_badge( $t->status ) . '</h3><p><small>' . esc_html( ( $u ? $u->display_name . ' <' . $u->user_email . '>' : '' ) . ' · ' . mysql2date( 'M j, Y H:i', $t->created_at ) . ( $t->order_id ? ' · ' . dm_order_number( $t->order_id ) : '' ) . ( $t->seller_id ? ' · ' . dm_shop_name( $t->seller_id ) : '' ) ) . '</small></p><p>' . nl2br( esc_html( $t->message ) ) . '</p>'; // phpcs:ignore
		if ( $t->reply ) {
			echo '<blockquote class="dm-reply"><strong>' . esc_html__( 'Reply:', 'digimarket' ) . '</strong> ' . nl2br( esc_html( $t->reply ) ) . '</blockquote>';
		}
		if ( current_user_can( 'dm_manage_marketplace' ) ) {
			dm_admin_form_open( 'ticket_reply' );
			echo '<input type="hidden" name="id" value="' . (int) $t->id . '"><textarea name="reply" rows="2" class="large-text" placeholder="' . esc_attr__( 'Write a reply…', 'digimarket' ) . '"></textarea><p><select name="status"><option value="open">' . esc_html__( 'Keep open', 'digimarket' ) . '</option><option value="closed">' . esc_html__( 'Close', 'digimarket' ) . '</option></select> <button class="button">' . esc_html__( 'Send', 'digimarket' ) . '</button>';
			if ( $t->order_id ) {
				echo ' <a class="button" href="' . esc_url( dm_admin_url( 'dm-transactions', array( 'order' => $t->order_id ) ) ) . '">' . esc_html__( 'Open order (refund)', 'digimarket' ) . '</a>';
			}
			echo '</p></form>';
		}
		echo '</div>';
	}
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'No support tickets.', 'digimarket' ) . '</p>';
	}
	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Audit & downloads
 * ---------------------------------------------------------------------- */

function dm_admin_audit() {
	global $wpdb;
	$logs = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'audit_logs' ) . ' ORDER BY id DESC LIMIT 200' ); // phpcs:ignore
	$dls  = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'downloads' ) . ' ORDER BY id DESC LIMIT 200' ); // phpcs:ignore
	dm_admin_header( __( 'Audit log & download log', 'digimarket' ) );
	echo '<div class="dm-grid2"><div class="dm-panel"><h2>' . esc_html__( 'Admin actions', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'When', 'digimarket' ) . '</th><th>' . esc_html__( 'Who', 'digimarket' ) . '</th><th>' . esc_html__( 'Action', 'digimarket' ) . '</th><th>' . esc_html__( 'Target', 'digimarket' ) . '</th><th>' . esc_html__( 'Details', 'digimarket' ) . '</th></tr></thead><tbody>';
	foreach ( $logs as $l ) {
		$u = get_userdata( $l->admin_id );
		echo '<tr><td>' . esc_html( mysql2date( 'M j H:i', $l->created_at ) ) . '</td><td>' . esc_html( $u ? $u->display_name : '#' . $l->admin_id ) . '</td><td><code>' . esc_html( $l->action ) . '</code></td><td>' . esc_html( $l->target_type . ' #' . $l->target_id ) . '</td><td><small>' . esc_html( $l->details ) . '</small></td></tr>';
	}
	echo '</tbody></table></div><div class="dm-panel"><h2>' . esc_html__( 'Downloads', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'When', 'digimarket' ) . '</th><th>' . esc_html__( 'User', 'digimarket' ) . '</th><th>' . esc_html__( 'Product', 'digimarket' ) . '</th><th>IP</th></tr></thead><tbody>';
	foreach ( $dls as $d ) {
		$u = get_userdata( $d->user_id );
		echo '<tr><td>' . esc_html( mysql2date( 'M j H:i', $d->created_at ) ) . '</td><td>' . esc_html( $u ? $u->user_email : '#' . $d->user_id ) . '</td><td>' . esc_html( get_the_title( $d->product_id ) ) . '</td><td>' . esc_html( $d->ip ) . '</td></tr>';
	}
	echo '</tbody></table></div></div></div>';
}

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

function dm_admin_settings() {
	$s = dm_settings();
	dm_admin_header( __( 'Marketplace settings', 'digimarket' ) );
	dm_admin_form_open( 'save_settings' );
	$field = function ( $key, $label, $type = 'text', $help = '', $attrs = '' ) use ( $s ) {
		echo '<tr><th><label for="dm_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="dm_' . esc_attr( $key ) . '" name="s[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '> ' . esc_html( $help ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="3" id="dm_' . esc_attr( $key ) . '" name="s[' . esc_attr( $key ) . ']">' . esc_textarea( $s[ $key ] ) . '</textarea>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' );
		} else {
			echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="dm_' . esc_attr( $key ) . '" name="s[' . esc_attr( $key ) . ']" value="' . esc_attr( is_array( $s[ $key ] ) ? '' : $s[ $key ] ) . '" ' . $attrs . '>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore
		}
		echo '</td></tr>';
	};
	echo '<h2>' . esc_html__( 'Commission engine', 'digimarket' ) . '</h2><table class="form-table">';
	$field( 'commission_global', __( 'Global commission (%)', 'digimarket' ), 'number', __( 'Applied to every sale unless a seller or category override exists.', 'digimarket' ), 'step="0.01" min="0" max="100"' );
	$field( 'commission_start_date', __( 'Commission starts on', 'digimarket' ), 'date', __( 'Launch promo: before this date commission is 0% for everyone. It switches to the normal rate automatically. Leave blank to charge commission now.', 'digimarket' ) );
	echo '<tr><th>' . esc_html__( 'Category overrides', 'digimarket' ) . '</th><td>';
	foreach ( dm_categories() as $t ) {
		echo '<label style="display:inline-block;margin:0 16px 8px 0">' . esc_html( $t->name ) . ' <input type="number" step="0.01" min="0" max="100" style="width:80px" name="cat_commission[' . (int) $t->term_id . ']" value="' . esc_attr( get_term_meta( $t->term_id, 'dm_commission', true ) ) . '" placeholder="—">%</label>';
	}
	echo '<p class="description">' . esc_html__( 'Blank = global rate. Per-seller overrides are set on each seller’s page.', 'digimarket' ) . '</p></td></tr></table>';

	echo '<h2>' . esc_html__( 'Payments (Razorpay Route)', 'digimarket' ) . '</h2><table class="form-table">';
	echo '<tr><th>' . esc_html__( 'Gateway mode', 'digimarket' ) . '</th><td><select name="s[gateway]"><option value="demo"' . selected( $s['gateway'], 'demo', false ) . '>' . esc_html__( 'Demo (no real money — for testing)', 'digimarket' ) . '</option><option value="razorpay"' . selected( $s['gateway'], 'razorpay', false ) . '>' . esc_html__( 'Razorpay (use test keys for sandbox)', 'digimarket' ) . '</option></select></td></tr>';
	$field( 'rzp_key_id', __( 'Key ID', 'digimarket' ), 'text', 'rzp_test_… / rzp_live_…' );
	$field( 'rzp_key_secret', __( 'Key secret', 'digimarket' ), 'password', '', 'autocomplete="new-password"' );
	$field( 'rzp_webhook_secret', __( 'Webhook secret', 'digimarket' ), 'password', sprintf( /* translators: %s url */ __( 'Webhook URL: %s — enable events payment.captured, payment.failed, transfer.processed, transfer.failed, refund.processed, payment.dispute.*', 'digimarket' ), rest_url( 'dm/v1/razorpay-webhook' ) ), 'autocomplete="new-password"' );
	$field( 'rzp_profile_category', __( 'Linked account business category', 'digimarket' ), 'text', __( 'Razorpay profile category for sellers (see Razorpay docs).', 'digimarket' ) );
	$field( 'rzp_profile_subcategory', __( 'Linked account sub-category', 'digimarket' ) );
	$field( 'allow_sales_without_kyc', __( 'Sales before KYC', 'digimarket' ), 'checkbox', __( 'Allow sales for sellers without a verified linked account (payouts are held as failed until fixed).', 'digimarket' ) );
	$field( 'currency_symbol', __( 'Currency symbol', 'digimarket' ) );
	$field( 'currency_code', __( 'Currency code', 'digimarket' ) );
	echo '</table>';

	echo '<h2>' . esc_html__( 'Sellers, buyers & refunds', 'digimarket' ) . '</h2><table class="form-table">';
	$field( 'auto_approve_sellers', __( 'Auto-approve sellers', 'digimarket' ), 'checkbox', __( 'New shops go live immediately (otherwise they wait for manual review).', 'digimarket' ) );
	$field( 'require_email_verify', __( 'Email verification', 'digimarket' ), 'checkbox', __( 'Buyers must verify their email before the first purchase.', 'digimarket' ) );
	$field( 'sellers_can_refund', __( 'Seller refunds', 'digimarket' ), 'checkbox', __( 'Allow sellers to issue refunds from their dashboard.', 'digimarket' ) );
	$field( 'refund_window_days', __( 'Refund window (days)', 'digimarket' ), 'number', '', 'min="0"' );
	echo '<tr><th>' . esc_html__( 'Featured shops', 'digimarket' ) . '</th><td><p class="description">' . esc_html__( 'Tick “Featured shop” on a seller’s page to promote it on the homepage. Tick “Featured” on a product (Products → Edit) to add it to the hero rotator.', 'digimarket' ) . '</p></td></tr>';
	echo '</table>';

	echo '<h2>' . esc_html__( 'Delivery & files', 'digimarket' ) . '</h2><table class="form-table">';
	$field( 'link_expiry_minutes', __( 'Download link lifetime (minutes)', 'digimarket' ), 'number', '', 'min="1"' );
	$field( 'max_upload_mb', __( 'Max file size (MB)', 'digimarket' ), 'number', sprintf( /* translators: %s */ __( 'Your server currently allows uploads up to %s.', 'digimarket' ), size_format( wp_max_upload_size() ) ), 'min="1"' );
	$field( 'allowed_extensions', __( 'Allowed file extensions', 'digimarket' ), 'textarea', __( 'Comma-separated.', 'digimarket' ) );
	echo '</table>';

	echo '<h2>' . esc_html__( 'Security', 'digimarket' ) . '</h2><table class="form-table">';
	$field( 'admin_2fa', __( 'Admin 2FA', 'digimarket' ), 'checkbox', __( 'Require an emailed 6-digit code for marketplace admins at login. Make sure your site can send email first.', 'digimarket' ) );
	$field( 'admin_idle_minutes', __( 'Admin idle logout (minutes)', 'digimarket' ), 'number', __( '0 disables.', 'digimarket' ), 'min="0"' );
	$field( 'login_max_attempts', __( 'Max failed logins', 'digimarket' ), 'number', '', 'min="1"' );
	$field( 'login_lockout_minutes', __( 'Lockout duration (minutes)', 'digimarket' ), 'number', '', 'min="1"' );
	echo '</table>';

	echo '<h2>' . esc_html__( 'Invoices & support', 'digimarket' ) . '</h2><table class="form-table">';
	$field( 'invoice_company', __( 'Company name', 'digimarket' ) );
	$field( 'invoice_address', __( 'Address', 'digimarket' ), 'textarea' );
	$field( 'invoice_gstin', __( 'GSTIN', 'digimarket' ) );
	$field( 'support_email', __( 'Support email', 'digimarket' ), 'email' );
	$field( 'order_prefix', __( 'Order number prefix', 'digimarket' ) );
	echo '</table><p><button class="button button-primary button-hero">' . esc_html__( 'Save settings', 'digimarket' ) . '</button></p></form></div>';
}

/* -------------------------------------------------------------------------
 * Admin action handler
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_dm_admin', 'dm_admin_handle' );
function dm_admin_handle() {
	$do = isset( $_REQUEST['do'] ) ? sanitize_key( $_REQUEST['do'] ) : '';
	check_admin_referer( 'dm_admin_' . $do );
	if ( 'export_ledger' === $do ) {
		if ( ! current_user_can( 'dm_view_marketplace' ) ) {
			wp_die( 'Forbidden', 403 );
		}
		$res = dm_admin_ledger_query( 100000, 0 );
		$out = array();
		foreach ( $res['items'] as $r ) {
			$out[] = array( dm_order_number( $r->order_id ), $r->created_at, $r->buyer_name, $r->buyer_email, dm_shop_name( $r->seller_id ), $r->product_title, $r->price_at_purchase, $r->commission_percent_applied, $r->commission_amount, $r->seller_net_amount, $r->item_status, $r->gateway, $r->razorpay_payment_id, $r->transfer_id, $r->transfer_status );
		}
		dm_output_csv( 'ledger-' . gmdate( 'Y-m-d' ) . '.csv', array( 'Order', 'Date', 'Buyer', 'Buyer email', 'Seller', 'Product', 'Gross', 'Commission %', 'Commission', 'Seller net', 'Status', 'Gateway', 'Payment ID', 'Transfer ID', 'Transfer status' ), $out );
	}
	if ( ! current_user_can( 'dm_manage_marketplace' ) ) {
		wp_die( esc_html__( 'You can view but not change marketplace settings.', 'digimarket' ), 403 );
	}
	global $wpdb;
	$r = wp_unslash( $_REQUEST );
	switch ( $do ) {
		case 'seller_status':
			$uid = absint( $r['uid'] );
			dm_admin_set_seller_status( $uid, sanitize_key( $r['to'] ?? '' ) );
			dm_admin_back( __( 'Seller updated.', 'digimarket' ) );
			break;

		case 'seller_update':
			$uid = absint( $r['uid'] );
			dm_admin_set_seller_status( $uid, sanitize_key( $r['status'] ?? '' ) );
			$old = get_user_meta( $uid, 'dm_commission_override', true );
			$new = '' === trim( (string) ( $r['commission'] ?? '' ) ) ? '' : max( 0, min( 100, (float) ( $r['commission'] ?? 0 ) ) );
			if ( (string) $old !== (string) $new ) {
				update_user_meta( $uid, 'dm_commission_override', $new );
				dm_audit( 'commission_seller_change', 'seller', $uid, array( 'from' => $old, 'to' => $new ) );
			}
			$kyc = sanitize_key( $r['kyc'] ?? 'pending' );
			if ( get_user_meta( $uid, 'dm_kyc_status', true ) !== $kyc ) {
				update_user_meta( $uid, 'dm_kyc_status', $kyc );
				dm_audit( 'kyc_status', 'seller', $uid, array( 'to' => $kyc ) );
				dm_notify( $uid, sprintf( /* translators: %s */ __( 'Your payout verification status is now: %s', 'digimarket' ), $kyc ), dm_url( 'dashboard', 'payouts' ) );
			}
			$acc = sanitize_text_field( $r['rzp_account'] ?? '' );
			if ( get_user_meta( $uid, 'dm_rzp_account_id', true ) !== $acc ) {
				update_user_meta( $uid, 'dm_rzp_account_id', $acc );
				dm_audit( 'linked_account_set', 'seller', $uid, array( 'account' => $acc ) );
			}
			$settings = get_option( 'dm_settings', array() );
			$featured = array_map( 'intval', (array) ( $settings['featured_shops'] ?? array() ) );
			$featured = ! empty( $r['featured'] ) ? array_unique( array_merge( $featured, array( $uid ) ) ) : array_diff( $featured, array( $uid ) );
			$settings['featured_shops'] = array_values( $featured );
			update_option( 'dm_settings', $settings );
			dm_admin_back( __( 'Seller saved.', 'digimarket' ) );
			break;

		case 'seller_rzp_sync':
			$res = dm_rzp_sync_linked_account( absint( $r['uid'] ) );
			if ( is_wp_error( $res ) ) {
				update_user_meta( absint( $r['uid'] ), 'dm_kyc_error', $res->get_error_message() );
				dm_admin_back( '', $res->get_error_message() );
			}
			dm_audit( 'linked_account_sync', 'seller', absint( $r['uid'] ), array( 'status' => $res ) );
			dm_admin_back( sprintf( /* translators: %s */ __( 'Linked account status: %s', 'digimarket' ), $res ) );
			break;

		case 'buyer_suspend':
			$uid = absint( $r['uid'] );
			if ( user_can( $uid, 'manage_options' ) ) {
				dm_admin_back( '', __( 'Administrators cannot be suspended.', 'digimarket' ) );
			}
			update_user_meta( $uid, 'dm_suspended', empty( $r['to'] ) ? 0 : 1 );
			if ( ! empty( $r['to'] ) ) {
				WP_Session_Tokens::get_instance( $uid )->destroy_all();
			}
			dm_audit( empty( $r['to'] ) ? 'user_unsuspend' : 'user_suspend', 'user', $uid );
			dm_admin_back( __( 'Account updated.', 'digimarket' ) );
			break;

		case 'verify_email':
			update_user_meta( absint( $r['uid'] ), 'dm_email_verified', 1 );
			dm_audit( 'email_verified_manually', 'user', absint( $r['uid'] ) );
			dm_admin_back( __( 'Email marked as verified.', 'digimarket' ) );
			break;

		case 'force_unpublish':
			$pid = absint( $r['pid'] );
			update_post_meta( $pid, '_dm_forced', 1 );
			wp_update_post( array( 'ID' => $pid, 'post_status' => 'dm_unpublished' ) );
			if ( ! empty( $r['report'] ) ) {
				$wpdb->update( dm_table( 'reports' ), array( 'status' => 'actioned' ), array( 'id' => absint( $r['report'] ) ) );
			}
			/* translators: %s */
			dm_notify( (int) get_post_field( 'post_author', $pid ), sprintf( __( '“%s” was unpublished by moderation for a policy violation.', 'digimarket' ), get_the_title( $pid ) ), dm_url( 'dashboard', 'products' ) );
			dm_audit( 'product_force_unpublish', 'product', $pid );
			dm_admin_back( __( 'Product unpublished and locked.', 'digimarket' ) );
			break;

		case 'unlock_product':
			update_post_meta( absint( $r['pid'] ), '_dm_forced', 0 );
			dm_audit( 'product_unlock', 'product', absint( $r['pid'] ) );
			dm_admin_back( __( 'Product unlocked — the seller can republish it.', 'digimarket' ) );
			break;

		case 'dismiss_report':
			$wpdb->update( dm_table( 'reports' ), array( 'status' => 'dismissed' ), array( 'id' => absint( $r['report'] ) ) );
			dm_admin_back( __( 'Report dismissed.', 'digimarket' ) );
			break;

		case 'refund_item':
			$res = dm_refund_item( absint( $r['item'] ), __( 'Refunded by marketplace admin', 'digimarket' ) );
			if ( is_wp_error( $res ) ) {
				dm_admin_back( '', $res->get_error_message() );
			}
			dm_admin_back( __( 'Refund processed and split reversed.', 'digimarket' ) );
			break;

		case 'retry_transfer':
			$oid = absint( $r['order'] );
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'order_items' ) . " SET transfer_id = '' WHERE order_id = %d AND transfer_status = 'failed'", $oid ) );
			dm_process_transfers( $oid );
			dm_audit( 'transfer_retry', 'order', $oid );
			dm_admin_back( __( 'Transfer retried — check the status below.', 'digimarket' ) );
			break;

		case 'payout_resolved':
			$pid = absint( $r['payout'] );
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'payouts' ) . ' WHERE id = %d', $pid ) );
			$wpdb->update( dm_table( 'payouts' ), array( 'status' => 'settled', 'note' => 'Resolved manually by admin', 'settled_at' => dm_now() ), array( 'id' => $pid ) );
			if ( $row ) {
				$wpdb->update( dm_table( 'order_items' ), array( 'transfer_status' => 'processed', 'transfer_note' => 'Paid manually' ), array( 'id' => $row->order_item_id ) );
			}
			dm_audit( 'payout_manual_resolve', 'payout', $pid );
			dm_admin_back( __( 'Payout marked as settled.', 'digimarket' ) );
			break;

		case 'review_status':
			$id = absint( $r['id'] );
			$wpdb->update( dm_table( 'reviews' ), array( 'status' => 'hidden' === ( $r['to'] ?? '' ) ? 'hidden' : 'approved', 'flagged' => 0 ), array( 'id' => $id ) );
			dm_refresh_product_rating( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT product_id FROM ' . dm_table( 'reviews' ) . ' WHERE id = %d', $id ) ) );
			dm_audit( 'review_' . sanitize_key( $r['to'] ?? '' ), 'review', $id );
			dm_admin_back( __( 'Review updated.', 'digimarket' ) );
			break;

		case 'review_unflag':
			$wpdb->update( dm_table( 'reviews' ), array( 'flagged' => 0 ), array( 'id' => absint( $r['id'] ) ) );
			dm_admin_back( __( 'Review kept.', 'digimarket' ) );
			break;

		case 'review_delete':
			$id  = absint( $r['id'] );
			$pid = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT product_id FROM ' . dm_table( 'reviews' ) . ' WHERE id = %d', $id ) );
			$wpdb->delete( dm_table( 'reviews' ), array( 'id' => $id ) );
			dm_refresh_product_rating( $pid );
			dm_audit( 'review_delete', 'review', $id );
			dm_admin_back( __( 'Review deleted.', 'digimarket' ) );
			break;

		case 'coupon_create':
			$code = strtoupper( preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) ( $r['code'] ?? '' ) ) );
			if ( ! $code || $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'coupons' ) . ' WHERE code = %s', $code ) ) ) {
				dm_admin_back( '', __( 'Coupon code is empty or already exists.', 'digimarket' ) );
			}
			$wpdb->insert(
				dm_table( 'coupons' ),
				array(
					'code'           => $code,
					'discount_type'  => 'flat' === ( $r['discount_type'] ?? '' ) ? 'flat' : 'percent',
					'discount_value' => max( 0, (float) ( $r['discount_value'] ?? 0 ) ),
					'seller_id'      => absint( $r['seller_id'] ?? 0 ),
					'min_order'      => max( 0, (float) ( $r['min_order'] ?? 0 ) ),
					'valid_from'     => ! empty( $r['valid_from'] ) ? sanitize_text_field( $r['valid_from'] ) : null,
					'valid_until'    => ! empty( $r['valid_until'] ) ? sanitize_text_field( $r['valid_until'] ) : null,
					'usage_limit'    => absint( $r['usage_limit'] ?? 0 ),
					'times_used'     => 0,
					'active'         => 1,
					'created_at'     => dm_now(),
				)
			);
			dm_audit( 'coupon_create', 'coupon', $wpdb->insert_id, array( 'code' => $code ) );
			dm_admin_back( __( 'Coupon created.', 'digimarket' ) );
			break;

		case 'coupon_toggle':
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'coupons' ) . ' SET active = 1 - active WHERE id = %d', absint( $r['id'] ) ) );
			dm_admin_back( __( 'Coupon updated.', 'digimarket' ) );
			break;

		case 'coupon_delete':
			$wpdb->delete( dm_table( 'coupons' ), array( 'id' => absint( $r['id'] ) ) );
			dm_audit( 'coupon_delete', 'coupon', absint( $r['id'] ) );
			dm_admin_back( __( 'Coupon deleted.', 'digimarket' ) );
			break;

		case 'ticket_reply':
			$id = absint( $r['id'] );
			$t  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'tickets' ) . ' WHERE id = %d', $id ) );
			if ( $t ) {
				$wpdb->update( dm_table( 'tickets' ), array( 'reply' => sanitize_textarea_field( $r['reply'] ?? '' ), 'status' => 'closed' === ( $r['status'] ?? '' ) ? 'closed' : 'open', 'updated_at' => dm_now() ), array( 'id' => $id ) );
				/* translators: %s */
				dm_notify( $t->user_id, sprintf( __( 'Update on your support request: %s', 'digimarket' ), $t->subject ), dm_url( 'account', 'support' ) );
			}
			dm_admin_back( __( 'Reply sent.', 'digimarket' ) );
			break;

		case 'save_settings':
			$in       = isset( $r['s'] ) ? (array) $r['s'] : array();
			$old      = dm_settings();
			$defaults = dm_default_settings();
			$new      = $old;
			$checks   = array( 'auto_approve_sellers', 'require_email_verify', 'allow_sales_without_kyc', 'admin_2fa', 'sellers_can_refund' );
			foreach ( $defaults as $k => $def ) {
				if ( in_array( $k, $checks, true ) ) {
					$new[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
				} elseif ( 'featured_shops' === $k ) {
					continue;
				} elseif ( isset( $in[ $k ] ) ) {
					$new[ $k ] = is_numeric( $def ) ? (float) $in[ $k ] : ( 'invoice_address' === $k || 'allowed_extensions' === $k ? sanitize_textarea_field( $in[ $k ] ) : sanitize_text_field( $in[ $k ] ) );
				}
			}
			$new['gateway']           = in_array( $new['gateway'], array( 'demo', 'razorpay' ), true ) ? $new['gateway'] : 'demo';
			$new['commission_global'] = max( 0, min( 100, (float) $new['commission_global'] ) );
			foreach ( array( 'rzp_key_secret', 'rzp_webhook_secret' ) as $secret ) {
				if ( '' === (string) ( $in[ $secret ] ?? '' ) ) {
					$new[ $secret ] = $old[ $secret ]; // Keep existing secret when left blank.
				}
			}
			foreach ( array( 'commission_global', 'commission_start_date', 'gateway' ) as $watch ) {
				if ( (string) $old[ $watch ] !== (string) $new[ $watch ] ) {
					dm_audit( 'setting_' . $watch, 'settings', 0, array( 'from' => $old[ $watch ], 'to' => $new[ $watch ] ) );
				}
			}
			update_option( 'dm_settings', $new );
			do_action( 'dm_settings_saved' );
			foreach ( (array) ( $r['cat_commission'] ?? array() ) as $tid => $v ) {
				$prev = get_term_meta( absint( $tid ), 'dm_commission', true );
				$v    = '' === trim( (string) $v ) ? '' : max( 0, min( 100, (float) $v ) );
				if ( (string) $prev !== (string) $v ) {
					update_term_meta( absint( $tid ), 'dm_commission', $v );
					dm_audit( 'commission_category_change', 'category', absint( $tid ), array( 'from' => $prev, 'to' => $v ) );
				}
			}
			dm_admin_back( __( 'Settings saved.', 'digimarket' ) );
			break;
	}
	dm_admin_back();
}

function dm_admin_set_seller_status( $uid, $to ) {
	if ( ! in_array( $to, array( 'draft', 'pending', 'active', 'suspended', 'rejected' ), true ) ) {
		return;
	}
	$from = dm_seller_status( $uid );
	if ( $from === $to ) {
		return;
	}
	update_user_meta( $uid, 'dm_seller_status', $to );
	if ( 'suspended' === $to || 'rejected' === $to ) {
		dm_unpublish_seller_products( $uid );
	}
	if ( 'active' === $to && ! get_user_meta( $uid, 'dm_seller_since', true ) ) {
		update_user_meta( $uid, 'dm_seller_since', time() );
	}
	$msgs = array(
		'active'    => __( 'Your shop has been approved and is live!', 'digimarket' ),
		'suspended' => __( 'Your shop has been suspended. Contact support for details.', 'digimarket' ),
		'rejected'  => __( 'Your shop application was not approved. Contact support for details.', 'digimarket' ),
	);
	if ( isset( $msgs[ $to ] ) ) {
		dm_notify( $uid, $msgs[ $to ], dm_url( 'dashboard' ) );
		$u = get_userdata( $uid );
		if ( $u ) {
			dm_mail( $u->user_email, $msgs[ $to ], '<p>' . esc_html( $msgs[ $to ] ) . '</p>', dm_url( 'dashboard' ), __( 'Open dashboard', 'digimarket' ) );
		}
	}
	dm_audit( 'seller_status', 'seller', $uid, array( 'from' => $from, 'to' => $to ) );
}

function dm_unpublish_seller_products( $uid ) {
	$ids = get_posts( array( 'post_type' => 'dm_product', 'author' => $uid, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $pid ) {
		wp_update_post( array( 'ID' => $pid, 'post_status' => 'dm_unpublished' ) );
	}
}

/* Product list columns in wp-admin. */
add_filter( 'manage_dm_product_posts_columns', function ( $cols ) {
	$cols['dm_price'] = __( 'Price', 'digimarket' );
	$cols['dm_shop']  = __( 'Shop', 'digimarket' );
	$cols['dm_sales'] = __( 'Sales', 'digimarket' );
	return $cols;
} );
add_action( 'manage_dm_product_posts_custom_column', function ( $col, $pid ) {
	if ( 'dm_price' === $col ) {
		echo wp_kses_post( dm_price_html( $pid ) );
	} elseif ( 'dm_shop' === $col ) {
		echo esc_html( dm_shop_name( get_post_field( 'post_author', $pid ) ) );
	} elseif ( 'dm_sales' === $col ) {
		echo (int) get_post_meta( $pid, '_dm_sales', true );
	}
}, 10, 2 );

/* Let admins assign any user (sellers are subscribers) as product author. */
add_filter( 'wp_dropdown_users_args', function ( $args, $r ) {
	global $post;
	if ( $post && 'dm_product' === $post->post_type ) {
		unset( $args['who'], $args['capability'] );
	}
	return $args;
}, 10, 2 );
