<?php
/**
 * Seller settlements: week-wise sales per seller, deductions (commission, GST on
 * commission, TDS, refunds) and manual payouts with a UTR reference. Also lets the
 * admin invite sellers by hand (works in single-seller mode too).
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/**
 * Monday 00:00 → Sunday 23:59:59 (site time) of the week containing $ts.
 */
function dm_week_range( $ts ) {
	$tz    = wp_timezone();
	$d     = ( new DateTimeImmutable( '@' . (int) $ts ) )->setTimezone( $tz );
	$start = $d->modify( 'monday this week' )->setTime( 0, 0, 0 );
	$end   = $start->modify( '+6 days' )->setTime( 23, 59, 59 );
	return array( $start->format( 'Y-m-d H:i:s' ), $end->format( 'Y-m-d H:i:s' ), $start );
}

function dm_week_label( $start_mysql ) {
	$s = strtotime( $start_mysql );
	return wp_date( 'j M', $s, new DateTimeZone( 'UTC' ) ) . ' – ' . wp_date( 'j M Y', $s + 6 * DAY_IN_SECONDS, new DateTimeZone( 'UTC' ) );
}

function dm_tds_rate() {
	return max( 0, min( 5, (float) dm_opt( 'payout_tds_percent', 0 ) ) );
}

/**
 * Settlement rows for sellers (site owner's own sales are excluded).
 * $from/$to limit by the order's paid date; empty = all time.
 * Returns one row per seller with gross, commission, GST, refunds, net, paid, due.
 */
function dm_settlement_rows( $from = '', $to = '', $seller_id = 0 ) {
	global $wpdb;
	$i     = dm_table( 'order_items' );
	$o     = dm_table( 'orders' );
	$p     = dm_table( 'payouts' );
	$where = array( "o.payment_status IN ('paid','partially_refunded','refunded')", "i.item_status IN ('paid','refunded')" );
	if ( $from ) {
		$where[] = $wpdb->prepare( 'COALESCE(o.paid_at, o.created_at) BETWEEN %s AND %s', $from, $to );
	}
	if ( $seller_id ) {
		$where[] = $wpdb->prepare( 'i.seller_id = %d', $seller_id );
	}
	$w    = implode( ' AND ', $where );
	$rows = $wpdb->get_results(
		"SELECT i.seller_id,
			COUNT(*) items,
			COUNT(DISTINCT i.order_id) orders,
			SUM(i.list_price) list_total,
			SUM(i.price_at_purchase) gross,
			SUM(CASE WHEN i.item_status = 'refunded' THEN i.price_at_purchase ELSE 0 END) refunded,
			SUM(CASE WHEN i.item_status = 'paid' THEN i.commission_amount ELSE 0 END) commission,
			SUM(CASE WHEN i.item_status = 'paid' THEN i.commission_gst ELSE 0 END) commission_gst,
			SUM(CASE WHEN i.item_status = 'paid' THEN i.seller_net_amount ELSE 0 END) net,
			SUM(CASE WHEN i.item_status = 'paid' AND p.status = 'settled' THEN p.amount - p.tds_amount ELSE 0 END) paid,
			SUM(CASE WHEN i.item_status = 'paid' AND p.status = 'settled' THEN p.tds_amount ELSE 0 END) tds_paid,
			SUM(CASE WHEN i.item_status = 'paid' AND p.status IN ('pending','failed') AND i.transfer_id = '' THEN p.amount ELSE 0 END) due_net,
			SUM(CASE WHEN i.item_status = 'paid' AND p.status IN ('pending','failed') AND i.transfer_id = '' THEN i.price_at_purchase ELSE 0 END) due_gross,
			SUM(CASE WHEN i.item_status = 'paid' AND p.status IN ('pending','failed') AND i.transfer_id = '' THEN 1 ELSE 0 END) due_items,
			MAX(CASE WHEN i.item_status = 'paid' AND p.status IN ('pending','failed') AND i.transfer_id = '' THEN COALESCE(o.paid_at, o.created_at) ELSE NULL END) last_due_at
		FROM $i i INNER JOIN $o o ON o.id = i.order_id LEFT JOIN $p p ON p.order_item_id = i.id
		WHERE $w GROUP BY i.seller_id ORDER BY gross DESC" // phpcs:ignore
	);
	$out = array();
	foreach ( $rows as $r ) {
		if ( user_can( (int) $r->seller_id, 'manage_options' ) ) {
			continue; // The store's own sales need no payout.
		}
		$r->tds     = dm_round( $r->due_gross * dm_tds_rate() / 100 );
		$r->recover = $from ? 0 : dm_seller_recoverable( $r->seller_id );
		$r->due     = max( 0, dm_round( $r->due_net - $r->tds - $r->recover ) );
		$out[]      = $r;
	}
	return $out;
}

/**
 * Money already paid out manually for items that were refunded later.
 */
function dm_seller_recoverable( $seller_id ) {
	global $wpdb;
	return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount - tds_amount),0) FROM ' . dm_table( 'payouts' ) . " WHERE seller_id = %d AND status = 'reversed' AND note LIKE %s", $seller_id, 'Refunded after manual payout%' ) );
}

/**
 * Weekly totals across all sellers for the overview table.
 */
function dm_settlement_weeks( $weeks = 12 ) {
	$out = array();
	for ( $n = 0; $n < $weeks; $n++ ) {
		list( $from, $to ) = dm_week_range( time() - $n * WEEK_IN_SECONDS );
		$t = array( 'from' => $from, 'to' => $to, 'orders' => 0, 'gross' => 0, 'refunded' => 0, 'commission' => 0, 'commission_gst' => 0, 'net' => 0, 'paid' => 0, 'due' => 0, 'sellers' => 0 );
		foreach ( dm_settlement_rows( $from, $to ) as $r ) {
			$t['orders']         += (int) $r->orders;
			$t['gross']          += (float) $r->gross;
			$t['refunded']       += (float) $r->refunded;
			$t['commission']     += (float) $r->commission;
			$t['commission_gst'] += (float) $r->commission_gst;
			$t['net']            += (float) $r->net;
			$t['paid']           += (float) $r->paid;
			$t['due']            += (float) $r->due;
			++$t['sellers'];
		}
		$out[] = $t;
	}
	return $out;
}

/**
 * Where to send a seller's money: legal name, bank and UPI.
 */
function dm_seller_pay_to( $uid, $full = false ) {
	$acct = $full ? dm_decrypt( get_user_meta( $uid, 'dm_bank_account', true ) ) : '';
	$l4   = get_user_meta( $uid, 'dm_bank_last4', true );
	return array(
		'name' => get_user_meta( $uid, 'dm_legal_name', true ),
		'acct' => $acct ? $acct : ( $l4 ? '•••• ' . $l4 : '' ),
		'ifsc' => get_user_meta( $uid, 'dm_ifsc', true ),
		'upi'  => get_user_meta( $uid, 'dm_upi', true ),
	);
}

function dm_upi_pay_link( $uid, $amount, $note ) {
	$to = dm_seller_pay_to( $uid );
	if ( ! $to['upi'] || $amount <= 0 ) {
		return '';
	}
	return 'upi://pay?' . http_build_query(
		array(
			'pa' => $to['upi'],
			'pn' => $to['name'] ? $to['name'] : dm_shop_name( $uid ),
			'am' => number_format( $amount, 2, '.', '' ),
			'cu' => 'INR',
			'tn' => mb_substr( $note, 0, 60 ),
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
}

/* -------------------------------------------------------------------------
 * Admin page: Payouts → Weekly settlement
 * ---------------------------------------------------------------------- */

function dm_admin_settlements() {
	$week = isset( $_GET['week'] ) ? sanitize_text_field( wp_unslash( $_GET['week'] ) ) : 'due';
	if ( 'due' === $week ) {
		$from  = '';
		$to    = '';
		$title = __( 'All unpaid sales (any week)', 'digimarket' );
	} else {
		$ts = strtotime( $week . ' 12:00:00' );
		list( $from, $to ) = dm_week_range( $ts ? $ts : time() );
		$title = sprintf( /* translators: %s week */ __( 'Week %s', 'digimarket' ), dm_week_label( $from ) );
	}
	$can  = current_user_can( 'dm_manage_marketplace' );
	$rows = dm_settlement_rows( $from, $to );
	if ( 'due' === $week ) {
		$rows = array_values( array_filter( $rows, function ( $r ) {
			return $r->due_items > 0 || $r->recover > 0;
		} ) );
	}

	$tot = array( 'gross' => 0, 'commission' => 0, 'commission_gst' => 0, 'refunded' => 0, 'net' => 0, 'paid' => 0, 'due' => 0 );
	foreach ( $rows as $r ) {
		foreach ( $tot as $k => $v ) {
			$tot[ $k ] += (float) $r->$k;
		}
	}
	echo '<div class="dm-stats">';
	dm_stat_card( __( 'Seller sales', 'digimarket' ), dm_money( $tot['gross'] ) );
	dm_stat_card( __( 'Your commission', 'digimarket' ), dm_money( $tot['commission'] ), 'accent' );
	dm_stat_card( __( 'GST on commission', 'digimarket' ), dm_money( $tot['commission_gst'] ) );
	dm_stat_card( __( 'Refunded', 'digimarket' ), dm_money( $tot['refunded'] ) );
	dm_stat_card( __( 'Already paid', 'digimarket' ), dm_money( $tot['paid'] ) );
	dm_stat_card( __( 'To pay now', 'digimarket' ), dm_money( $tot['due'] ) );
	echo '</div>';

	// Week picker.
	echo '<form method="get" class="dm-filters"><input type="hidden" name="page" value="dm-payouts"><label><strong>' . esc_html__( 'Show:', 'digimarket' ) . '</strong> <select name="week" onchange="this.form.submit()"><option value="due"' . selected( $week, 'due', false ) . '>' . esc_html__( 'All unpaid (any week)', 'digimarket' ) . '</option>';
	for ( $n = 0; $n < 26; $n++ ) {
		list( $wf ) = dm_week_range( time() - $n * WEEK_IN_SECONDS );
		$key        = substr( $wf, 0, 10 );
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $from ? substr( $from, 0, 10 ) : '', $key, false ) . '>' . esc_html( ( 0 === $n ? __( 'This week', 'digimarket' ) . ': ' : '' ) . dm_week_label( $wf ) ) . '</option>';
	}
	echo '</select></label> <noscript><button class="button">' . esc_html__( 'Go', 'digimarket' ) . '</button></noscript>';
	echo ' <a class="button" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'dm_admin', 'do' => 'settlement_csv', 'week' => $week ), admin_url( 'admin-post.php' ) ), 'dm_admin_settlement_csv' ) ) . '">' . esc_html__( 'Download CSV', 'digimarket' ) . '</a></form>';

	echo '<div class="dm-panel"><h2>' . esc_html( $title ) . '</h2>';
	echo '<p class="description">' . esc_html( sprintf( /* translators: %d days */ __( 'Net payable = sale price after discount − your commission − GST on commission − TDS (if set) − money to recover from earlier refunds. Pay after the %d-day refund window so a refund never has to be recovered from the seller.', 'digimarket' ), (int) dm_opt( 'refund_window_days', 7 ) ) ) . '</p>';
	echo '<table class="widefat striped dm-table dm-settle"><thead><tr><th>' . esc_html__( 'Seller', 'digimarket' ) . '</th><th>' . esc_html__( 'Orders / items', 'digimarket' ) . '</th><th>' . esc_html__( 'Sales', 'digimarket' ) . '</th><th>' . esc_html__( 'Refunded', 'digimarket' ) . '</th><th>' . esc_html__( 'Commission', 'digimarket' ) . '</th><th>' . esc_html__( 'GST on comm.', 'digimarket' ) . '</th><th>' . esc_html__( 'Seller net', 'digimarket' ) . '</th><th>' . esc_html__( 'Paid', 'digimarket' ) . '</th><th>' . esc_html__( 'Due now', 'digimarket' ) . '</th><th>' . esc_html__( 'Pay to', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	$window = (int) dm_opt( 'refund_window_days', 7 );
	foreach ( $rows as $r ) {
		$sid = (int) $r->seller_id;
		$pay = dm_seller_pay_to( $sid, $can );
		$u   = get_userdata( $sid );
		echo '<tr><td><strong><a href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'seller' => $sid ) ) ) . '">' . esc_html( dm_shop_name( $sid ) ) . '</a></strong><br><small>' . esc_html( $u ? $u->user_email : '' ) . '</small></td>';
		echo '<td>' . (int) $r->orders . ' / ' . (int) $r->items . '</td>';
		echo '<td>' . esc_html( dm_money( $r->gross ) ) . ( $r->list_total > $r->gross ? '<br><small>' . esc_html( sprintf( /* translators: %s */ __( 'after %s discount', 'digimarket' ), dm_money( $r->list_total - $r->gross ) ) ) . '</small>' : '' ) . '</td>';
		echo '<td>' . ( $r->refunded > 0 ? '−' . esc_html( dm_money( $r->refunded ) ) : '—' ) . '</td>';
		echo '<td>−' . esc_html( dm_money( $r->commission ) ) . '</td><td>' . ( $r->commission_gst > 0 ? '−' . esc_html( dm_money( $r->commission_gst ) ) : '—' ) . '</td>';
		echo '<td>' . esc_html( dm_money( $r->net ) ) . '</td><td>' . esc_html( dm_money( $r->paid ) ) . ( $r->tds_paid > 0 ? '<br><small>' . esc_html( sprintf( /* translators: %s */ __( '+ %s TDS', 'digimarket' ), dm_money( $r->tds_paid ) ) ) . '</small>' : '' ) . '</td>';
		echo '<td><strong class="dm-due">' . esc_html( dm_money( $r->due ) ) . '</strong>';
		if ( $r->tds > 0 ) {
			echo '<br><small>' . esc_html( sprintf( /* translators: %s */ __( 'after %s TDS', 'digimarket' ), dm_money( $r->tds ) ) ) . '</small>';
		}
		if ( $r->recover > 0 ) {
			echo '<br><small class="dm-warn">' . esc_html( sprintf( /* translators: %s */ __( '−%s recovered (refunds after payout)', 'digimarket' ), dm_money( $r->recover ) ) ) . '</small>';
		}
		if ( $r->last_due_at && strtotime( $r->last_due_at ) > current_time( 'timestamp' ) - $window * DAY_IN_SECONDS ) { // phpcs:ignore
			echo '<br><small class="dm-warn">' . esc_html( sprintf( /* translators: %s date */ __( 'Refund window open till %s', 'digimarket' ), wp_date( 'j M', strtotime( $r->last_due_at ) + $window * DAY_IN_SECONDS, new DateTimeZone( 'UTC' ) ) ) ) . '</small>';
		}
		echo '</td><td class="dm-payto">';
		if ( $pay['name'] ) {
			echo '<strong>' . esc_html( $pay['name'] ) . '</strong><br>';
		}
		if ( $pay['acct'] ) {
			echo esc_html__( 'A/c', 'digimarket' ) . ' <code>' . esc_html( $pay['acct'] ) . '</code><br>IFSC <code>' . esc_html( $pay['ifsc'] ) . '</code><br>';
		}
		if ( $pay['upi'] ) {
			echo 'UPI <code>' . esc_html( $pay['upi'] ) . '</code>';
		}
		if ( ! $pay['acct'] && ! $pay['upi'] ) {
			echo '<span class="dm-warn">' . esc_html__( 'No payout details yet', 'digimarket' ) . '</span>';
		}
		echo '</td><td class="dm-actions">';
		if ( $can && $r->due_items > 0 ) {
			$upi = dm_upi_pay_link( $sid, $r->due, get_bloginfo( 'name' ) . ' payout' );
			if ( $upi ) {
				echo '<a class="button button-small" href="' . esc_attr( $upi ) . '" title="' . esc_attr__( 'Opens your UPI app on a phone', 'digimarket' ) . '">' . esc_html__( 'Pay via UPI app', 'digimarket' ) . '</a><br>';
			}
			dm_admin_form_open( 'settle_seller' );
			echo '<input type="hidden" name="uid" value="' . (int) $sid . '"><input type="hidden" name="week" value="' . esc_attr( $week ) . '">';
			echo '<input type="text" name="reference" required maxlength="60" placeholder="' . esc_attr__( 'UTR / transaction ID', 'digimarket' ) . '" style="width:170px;margin:4px 0"><br>';
			echo '<select name="method"><option value="bank">' . esc_html__( 'Bank (IMPS/NEFT)', 'digimarket' ) . '</option><option value="upi">UPI</option><option value="other">' . esc_html__( 'Other', 'digimarket' ) . '</option></select> ';
			/* translators: 1 amount 2 shop */
			echo '<button class="button button-primary button-small" onclick="return confirm(\'' . esc_js( sprintf( __( 'Mark %1$s as paid to %2$s? Do this only after the money is sent.', 'digimarket' ), dm_money( $r->due ), dm_shop_name( $sid ) ) ) . '\')">' . esc_html( sprintf( /* translators: %s */ __( 'Mark %s paid', 'digimarket' ), dm_money( $r->due ) ) ) . '</button></form>';
		} elseif ( $r->due_items < 1 ) {
			echo dm_status_badge( 'settled' ); // phpcs:ignore
		}
		echo '</td></tr>';
	}
	if ( ! $rows ) {
		echo '<tr><td colspan="11">' . esc_html( 'due' === $week ? __( 'Nothing to pay — every seller is settled.', 'digimarket' ) : __( 'No seller sales in this week.', 'digimarket' ) ) . '</td></tr>';
	}
	echo '</tbody></table></div>';

	// Weekly overview.
	echo '<div class="dm-panel"><h2>' . esc_html__( 'Week-wise summary (last 12 weeks)', 'digimarket' ) . '</h2><table class="widefat striped dm-table"><thead><tr><th>' . esc_html__( 'Week (Mon–Sun)', 'digimarket' ) . '</th><th>' . esc_html__( 'Sellers', 'digimarket' ) . '</th><th>' . esc_html__( 'Orders', 'digimarket' ) . '</th><th>' . esc_html__( 'Sales', 'digimarket' ) . '</th><th>' . esc_html__( 'Refunded', 'digimarket' ) . '</th><th>' . esc_html__( 'Commission', 'digimarket' ) . '</th><th>' . esc_html__( 'GST on comm.', 'digimarket' ) . '</th><th>' . esc_html__( 'Seller net', 'digimarket' ) . '</th><th>' . esc_html__( 'Paid', 'digimarket' ) . '</th><th>' . esc_html__( 'Due', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( dm_settlement_weeks( 12 ) as $t ) {
		echo '<tr><td>' . esc_html( dm_week_label( $t['from'] ) ) . '</td><td>' . (int) $t['sellers'] . '</td><td>' . (int) $t['orders'] . '</td><td>' . esc_html( dm_money( $t['gross'] ) ) . '</td><td>' . esc_html( dm_money( $t['refunded'] ) ) . '</td><td>' . esc_html( dm_money( $t['commission'] ) ) . '</td><td>' . esc_html( dm_money( $t['commission_gst'] ) ) . '</td><td>' . esc_html( dm_money( $t['net'] ) ) . '</td><td>' . esc_html( dm_money( $t['paid'] ) ) . '</td><td><strong>' . esc_html( dm_money( $t['due'] ) ) . '</strong></td><td><a class="button button-small" href="' . esc_url( dm_admin_url( 'dm-payouts', array( 'week' => substr( $t['from'], 0, 10 ) ) ) ) . '">' . esc_html__( 'Open', 'digimarket' ) . '</a></td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Pending payout rows for one seller (optionally one week), manual only.
 */
function dm_settlement_pending_items( $uid, $week ) {
	global $wpdb;
	$where = '';
	if ( 'due' !== $week ) {
		$ts = strtotime( $week . ' 12:00:00' );
		list( $from, $to ) = dm_week_range( $ts ? $ts : time() );
		$where = $wpdb->prepare( ' AND COALESCE(o.paid_at, o.created_at) BETWEEN %s AND %s', $from, $to );
	}
	return $wpdb->get_results(
		$wpdb->prepare(
			'SELECT p.id payout_id, p.amount, i.id item_id, i.order_id, i.product_title, i.price_at_purchase, i.commission_amount, i.commission_gst FROM ' . dm_table( 'payouts' ) . ' p INNER JOIN ' . dm_table( 'order_items' ) . ' i ON i.id = p.order_item_id INNER JOIN ' . dm_table( 'orders' ) . " o ON o.id = i.order_id
			 WHERE p.seller_id = %d AND p.status IN ('pending','failed') AND i.item_status = 'paid' AND i.transfer_id = ''" . $where . ' ORDER BY i.id', // phpcs:ignore
			$uid
		)
	);
}

add_action( 'dm_admin_do_settle_seller', function ( $r ) {
	global $wpdb;
	$uid    = absint( $r['uid'] ?? 0 );
	$week   = sanitize_text_field( $r['week'] ?? 'due' );
	$ref    = trim( sanitize_text_field( $r['reference'] ?? '' ) );
	$method = in_array( $r['method'] ?? '', array( 'bank', 'upi', 'other' ), true ) ? $r['method'] : 'bank';
	if ( ! $uid || user_can( $uid, 'manage_options' ) ) {
		dm_admin_back( '', __( 'Unknown seller.', 'digimarket' ) );
	}
	if ( '' === $ref ) {
		dm_admin_back( '', __( 'Enter the UTR / transaction ID of the transfer.', 'digimarket' ) );
	}
	$items = dm_settlement_pending_items( $uid, $week );
	if ( ! $items ) {
		dm_admin_back( '', __( 'Nothing pending for this seller.', 'digimarket' ) );
	}
	$rate  = dm_tds_rate();
	$net   = 0;
	$tds   = 0;
	$gross = 0;
	$comm  = 0;
	$now   = dm_now();
	$label = array( 'bank' => 'bank transfer', 'upi' => 'UPI', 'other' => 'manual' );
	foreach ( $items as $it ) {
		$t = dm_round( $it->price_at_purchase * $rate / 100 );
		$wpdb->update( dm_table( 'payouts' ), array( 'status' => 'settled', 'reference' => $ref, 'tds_amount' => $t, 'settled_at' => $now, 'note' => 'Paid by ' . $label[ $method ] ), array( 'id' => $it->payout_id ) );
		$wpdb->update( dm_table( 'order_items' ), array( 'transfer_status' => 'processed', 'transfer_note' => 'Paid manually: ' . $ref ), array( 'id' => $it->item_id ) );
		$net   += (float) $it->amount;
		$tds   += $t;
		$gross += (float) $it->price_at_purchase;
		$comm  += (float) $it->commission_amount + (float) $it->commission_gst;
	}
	$recover = min( dm_seller_recoverable( $uid ), max( 0, $net - $tds ) );
	if ( $recover > 0 ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'payouts' ) . " SET note = %s WHERE seller_id = %d AND status = 'reversed' AND note LIKE %s", 'Recovered in payout ' . $ref, $uid, 'Refunded after manual payout%' ) );
	}
	$paid = dm_round( $net - $tds - $recover );
	dm_audit( 'payout_manual', 'seller', $uid, array( 'amount' => $paid, 'tds' => $tds, 'recovered' => $recover, 'items' => count( $items ), 'ref' => $ref, 'method' => $method ) );
	/* translators: 1 amount 2 reference */
	dm_notify( $uid, sprintf( __( 'Payout of %1$s sent (ref %2$s).', 'digimarket' ), dm_money( $paid ), $ref ), dm_url( 'dashboard', 'payouts' ) );
	dm_email_payout_statement( $uid, $items, compact( 'gross', 'comm', 'net', 'tds', 'recover', 'paid', 'ref', 'method' ) );
	/* translators: 1 amount 2 shop */
	dm_admin_back( sprintf( __( 'Marked %1$s as paid to %2$s. The seller got an email with the statement.', 'digimarket' ), dm_money( $paid ), dm_shop_name( $uid ) ) );
} );

function dm_email_payout_statement( $uid, $items, $t ) {
	$u = get_userdata( $uid );
	if ( ! $u ) {
		return;
	}
	$cell  = 'padding:8px 6px;border-bottom:1px solid #eee;font-size:13px';
	$rows  = '';
	foreach ( $items as $it ) {
		$rows .= '<tr><td style="' . $cell . '">' . esc_html( dm_order_number( $it->order_id ) ) . '<br><span style="color:#777">' . esc_html( $it->product_title ) . '</span></td><td style="' . $cell . ';text-align:right">' . esc_html( dm_money( $it->price_at_purchase ) ) . '</td><td style="' . $cell . ';text-align:right">−' . esc_html( dm_money( $it->commission_amount + $it->commission_gst ) ) . '</td><td style="' . $cell . ';text-align:right">' . esc_html( dm_money( $it->amount ) ) . '</td></tr>';
	}
	$line = function ( $label, $value, $bold = false ) {
		return '<tr><td style="padding:4px 6px;font-size:13px' . ( $bold ? ';font-weight:700;font-size:15px' : '' ) . '">' . esc_html( $label ) . '</td><td style="padding:4px 6px;text-align:right;font-size:13px' . ( $bold ? ';font-weight:700;font-size:15px' : '' ) . '">' . esc_html( $value ) . '</td></tr>';
	};
	$html  = '<p>' . sprintf( /* translators: %s name */ esc_html__( 'Hi %s,', 'digimarket' ), esc_html( $u->display_name ) ) . '</p>';
	$html .= '<p>' . sprintf( /* translators: 1 amount 2 ref */ esc_html__( 'We have sent your payout of %1$s. Transaction reference (UTR): %2$s.', 'digimarket' ), '<strong>' . esc_html( dm_money( $t['paid'] ) ) . '</strong>', '<strong>' . esc_html( $t['ref'] ) . '</strong>' ) . '</p>';
	$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:12px 0"><tr style="background:#f6f6f9"><th style="' . $cell . ';text-align:left">' . esc_html__( 'Order', 'digimarket' ) . '</th><th style="' . $cell . ';text-align:right">' . esc_html__( 'Sale', 'digimarket' ) . '</th><th style="' . $cell . ';text-align:right">' . esc_html__( 'Commission + GST', 'digimarket' ) . '</th><th style="' . $cell . ';text-align:right">' . esc_html__( 'Your share', 'digimarket' ) . '</th></tr>' . $rows . '</table>';
	$html .= '<table width="100%" cellpadding="0" cellspacing="0">' . $line( __( 'Total sales', 'digimarket' ), dm_money( $t['gross'] ) ) . $line( __( 'Commission + GST', 'digimarket' ), '−' . dm_money( $t['comm'] ) );
	if ( $t['tds'] > 0 ) {
		$html .= $line( __( 'TDS (Sec. 194-O)', 'digimarket' ), '−' . dm_money( $t['tds'] ) );
	}
	if ( $t['recover'] > 0 ) {
		$html .= $line( __( 'Adjusted for earlier refunds', 'digimarket' ), '−' . dm_money( $t['recover'] ) );
	}
	$html .= $line( __( 'Amount paid', 'digimarket' ), dm_money( $t['paid'] ), true ) . '</table>';
	/* translators: %s amount */
	dm_mail( $u->user_email, sprintf( __( 'Payout sent: %s', 'digimarket' ), dm_money( $t['paid'] ) ), $html, dm_url( 'dashboard', 'payouts' ), __( 'View payouts', 'digimarket' ) );
}

add_action( 'dm_admin_do_settlement_csv', function ( $r ) {
	$week = sanitize_text_field( $r['week'] ?? 'due' );
	$from = '';
	$to   = '';
	if ( 'due' !== $week ) {
		$ts = strtotime( $week . ' 12:00:00' );
		list( $from, $to ) = dm_week_range( $ts ? $ts : time() );
	}
	$out = array();
	foreach ( dm_settlement_rows( $from, $to ) as $row ) {
		$u    = get_userdata( $row->seller_id );
		$pay  = dm_seller_pay_to( $row->seller_id, true );
		$out[] = array( dm_shop_name( $row->seller_id ), $u ? $u->user_email : '', $pay['name'], $pay['acct'], $pay['ifsc'], $pay['upi'], $row->orders, $row->gross, $row->refunded, $row->commission, $row->commission_gst, $row->net, $row->paid, $row->tds, $row->recover, $row->due );
	}
	dm_output_csv( 'settlement-' . ( $from ? substr( $from, 0, 10 ) : 'unpaid' ) . '.csv', array( 'Shop', 'Email', 'Account holder', 'Account number', 'IFSC', 'UPI', 'Orders', 'Sales', 'Refunded', 'Commission', 'GST on commission', 'Seller net', 'Paid', 'TDS', 'Recovered', 'Due now' ), $out );
} );

/* -------------------------------------------------------------------------
 * Invite a seller by hand
 * ---------------------------------------------------------------------- */

function dm_admin_invite_seller_form() {
	if ( ! current_user_can( 'dm_manage_marketplace' ) ) {
		return;
	}
	echo '<details class="dm-panel" ' . ( dm_single_seller_mode() ? 'open' : '' ) . '><summary><strong>' . esc_html__( '+ Add a seller manually', 'digimarket' ) . '</strong></summary>';
	echo '<p class="description">' . esc_html( dm_single_seller_mode() ? __( 'Your store is in single-seller mode, so public seller signup stays closed. Sellers you add here can list products; you keep the commission and pay them by bank/UPI from Marketplace → Payouts.', 'digimarket' ) : __( 'Creates the seller account (or upgrades an existing customer) and emails them a link to finish setup: payout details and the Seller Agreement.', 'digimarket' ) ) . '</p>';
	dm_admin_form_open( 'seller_invite' );
	echo '<div class="dm-form-row"><label>' . esc_html__( 'Email', 'digimarket' ) . '<input type="email" name="email" required class="regular-text"></label><label>' . esc_html__( 'Full name', 'digimarket' ) . '<input type="text" name="name" required></label><label>' . esc_html__( 'Shop name', 'digimarket' ) . '<input type="text" name="shop" required maxlength="60"></label><label>' . esc_html__( 'Commission % (blank = default)', 'digimarket' ) . '<input type="number" name="commission" min="0" max="100" step="0.01" style="width:110px"></label></div>';
	echo '<p><button class="button button-primary">' . esc_html__( 'Add seller & send invite', 'digimarket' ) . '</button></p></form></details>';
}

add_action( 'dm_admin_do_seller_invite', function ( $r ) {
	$email = sanitize_email( $r['email'] ?? '' );
	$name  = sanitize_text_field( $r['name'] ?? '' );
	$shop  = sanitize_text_field( $r['shop'] ?? '' );
	if ( ! is_email( $email ) || '' === $name || '' === $shop || mb_strlen( $shop ) > 60 ) {
		dm_admin_back( '', __( 'Enter a valid email, name and shop name.', 'digimarket' ) );
	}
	$user = get_user_by( 'email', $email );
	$new  = false;
	if ( $user ) {
		if ( user_can( $user, 'manage_options' ) ) {
			dm_admin_back( '', __( 'That email belongs to an administrator.', 'digimarket' ) );
		}
		if ( dm_is_seller( $user->ID ) ) {
			dm_admin_back( '', __( 'That person is already a seller.', 'digimarket' ) );
		}
		$uid = $user->ID;
	} else {
		$login = sanitize_user( strtok( $email, '@' ), true );
		$login = $login ? $login : 'seller';
		$base  = $login;
		$n     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . ( ++$n );
		}
		$uid = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 24 ),
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => 'subscriber',
			)
		);
		if ( is_wp_error( $uid ) ) {
			dm_admin_back( '', $uid->get_error_message() );
		}
		update_user_meta( $uid, 'dm_email_verified', 1 );
		$new = true;
	}
	$slug = sanitize_title( $shop );
	$base = $slug;
	$n    = 1;
	while ( ! dm_slug_available( $slug, $uid ) && $n < 50 ) {
		$slug = $base . '-' . ( ++$n );
	}
	update_user_meta( $uid, 'dm_shop_name', $shop );
	update_user_meta( $uid, 'dm_shop_slug', $slug );
	update_user_meta( $uid, 'dm_shop_created', time() );
	update_user_meta( $uid, 'dm_seller_status', 'draft' );
	update_user_meta( $uid, 'dm_invited', time() );
	if ( '' !== trim( (string) ( $r['commission'] ?? '' ) ) ) {
		update_user_meta( $uid, 'dm_commission_override', max( 0, min( 100, (float) $r['commission'] ) ) );
	}
	$html = '<p>' . sprintf( /* translators: 1 name 2 site */ esc_html__( 'Hi %1$s, you have been added as a seller on %2$s.', 'digimarket' ), esc_html( $name ), esc_html( get_bloginfo( 'name' ) ) ) . '</p>';
	$html .= '<p>' . sprintf( /* translators: %s shop */ esc_html__( 'Your shop “%s” is ready. Two quick steps remain: add your payout details (bank account or UPI, where we send your earnings) and accept the Seller Agreement. After that you can list products.', 'digimarket' ), esc_html( $shop ) ) . '</p>';
	if ( $new ) {
		$token = wp_generate_password( 32, false );
		update_user_meta( $uid, 'dm_reset_token', wp_hash( $token ) );
		update_user_meta( $uid, 'dm_reset_expires', time() + 7 * DAY_IN_SECONDS );
		$html .= '<p>' . sprintf( /* translators: %s email */ esc_html__( 'First, set a password for your account (%s) with the button below — the link works for 7 days. Then log in and open “Seller dashboard” from the account menu.', 'digimarket' ), esc_html( $email ) ) . '</p>';
		$cta   = dm_url( 'reset', '', '', array( 'u' => $uid, 'k' => $token ) );
		$label = __( 'Set your password', 'digimarket' );
	} else {
		$cta   = dm_url( 'sell' );
		$label = __( 'Finish seller setup', 'digimarket' );
	}
	/* translators: %s site */
	dm_mail( $email, sprintf( __( 'You are now a seller on %s', 'digimarket' ), get_bloginfo( 'name' ) ), $html, $cta, $label );
	dm_audit( 'seller_invite', 'seller', $uid, array( 'email' => $email, 'new_user' => $new ) );
	/* translators: %s email */
	dm_admin_back( sprintf( __( 'Seller added and invite emailed to %s.', 'digimarket' ), $email ) );
} );

/* -------------------------------------------------------------------------
 * Seller wallet (seller dashboard)
 * ---------------------------------------------------------------------- */

/**
 * Every sold item of a seller with its payout state (paid date in site time).
 */
function dm_seller_wallet_items( $uid ) {
	global $wpdb;
	return $wpdb->get_results(
		$wpdb->prepare(
			'SELECT i.id, i.order_id, i.price_at_purchase, i.list_price, i.commission_amount, i.commission_gst, i.seller_net_amount, i.item_status, i.transfer_id, i.transfer_status,
				COALESCE(o.paid_at, o.created_at) sold_at, p.status pstatus, p.amount pamount, p.tds_amount, p.reference, p.settled_at
			 FROM ' . dm_table( 'order_items' ) . ' i INNER JOIN ' . dm_table( 'orders' ) . ' o ON o.id = i.order_id LEFT JOIN ' . dm_table( 'payouts' ) . " p ON p.order_item_id = i.id
			 WHERE i.seller_id = %d AND i.item_status IN ('paid','refunded') ORDER BY sold_at DESC",
			$uid
		)
	);
}

function dm_wallet_empty_row() {
	return array( 'orders' => array(), 'items' => 0, 'sales' => 0, 'refunded' => 0, 'commission' => 0, 'gst' => 0, 'tds' => 0, 'net' => 0, 'paid' => 0, 'pending' => 0 );
}

function dm_wallet_add( &$row, $it ) {
	$row['orders'][ $it->order_id ] = 1;
	++$row['items'];
	$row['sales'] += (float) $it->price_at_purchase;
	if ( 'refunded' === $it->item_status ) {
		$row['refunded'] += (float) $it->price_at_purchase;
		return;
	}
	$row['commission'] += (float) $it->commission_amount;
	$row['gst']        += (float) $it->commission_gst;
	$row['net']        += (float) $it->seller_net_amount;
	if ( 'settled' === $it->pstatus ) {
		$row['tds']  += (float) $it->tds_amount;
		$row['paid'] += (float) $it->pamount - (float) $it->tds_amount;
	} elseif ( in_array( $it->pstatus, array( 'pending', 'failed' ), true ) ) {
		$row['pending'] += (float) $it->pamount;
	} elseif ( 'not_required' === $it->transfer_status ) {
		$row['paid'] += (float) $it->seller_net_amount;
	}
}

/**
 * Wallet summary: current balance (what the store still owes), lifetime totals,
 * period rows (week / month / year) and the payments received.
 */
function dm_seller_wallet( $uid, $group = 'week', $limit = 12 ) {
	$items   = dm_seller_wallet_items( $uid );
	$life    = dm_wallet_empty_row();
	$periods = array();
	$now     = current_time( 'timestamp' ); // phpcs:ignore
	// Pre-fill the latest periods so empty weeks/months still show.
	for ( $n = 0; $n < $limit; $n++ ) {
		$key = dm_wallet_period_key( 'week' === $group ? $now - $n * WEEK_IN_SECONDS : ( 'month' === $group ? strtotime( gmdate( 'Y-m-01', $now ) . " -$n months" ) : strtotime( ( (int) gmdate( 'Y', $now ) - $n ) . '-06-01' ) ), $group );
		$periods[ $key ] = dm_wallet_empty_row();
	}
	$paid_batches = array();
	foreach ( $items as $it ) {
		dm_wallet_add( $life, $it );
		$key = dm_wallet_period_key( strtotime( $it->sold_at ), $group );
		if ( isset( $periods[ $key ] ) ) {
			dm_wallet_add( $periods[ $key ], $it );
		}
		if ( 'settled' === $it->pstatus && 'refunded' !== $it->item_status && $it->reference && ! $it->transfer_id ) {
			$ref = $it->reference;
			if ( ! isset( $paid_batches[ $ref ] ) ) {
				$paid_batches[ $ref ] = array( 'ref' => $ref, 'date' => $it->settled_at, 'items' => 0, 'amount' => 0, 'tds' => 0 );
			}
			++$paid_batches[ $ref ]['items'];
			$paid_batches[ $ref ]['amount'] += (float) $it->pamount - (float) $it->tds_amount;
			$paid_batches[ $ref ]['tds']    += (float) $it->tds_amount;
		}
	}
	$pending_gross = 0;
	foreach ( $items as $it ) {
		if ( 'paid' === $it->item_status && in_array( $it->pstatus, array( 'pending', 'failed' ), true ) && ! $it->transfer_id ) {
			$pending_gross += (float) $it->price_at_purchase;
		}
	}
	$tds_due = dm_round( $pending_gross * dm_tds_rate() / 100 );
	$recover = dm_seller_recoverable( $uid );
	usort( $paid_batches, function ( $a, $b ) {
		return strcmp( (string) $b['date'], (string) $a['date'] );
	} );
	return array(
		'balance'  => max( 0, dm_round( $life['pending'] - $tds_due - $recover ) ),
		'tds_due'  => $tds_due,
		'recover'  => $recover,
		'life'     => $life,
		'periods'  => $periods,
		'payments' => $paid_batches,
		'last'     => $paid_batches ? $paid_batches[0] : null,
	);
}

function dm_wallet_period_key( $ts, $group ) {
	if ( 'year' === $group ) {
		return gmdate( 'Y', $ts );
	}
	if ( 'month' === $group ) {
		return gmdate( 'Y-m', $ts );
	}
	return gmdate( 'o-\WW', $ts ); // ISO week, Monday start.
}

function dm_wallet_period_label( $key, $group ) {
	if ( 'year' === $group ) {
		return $key;
	}
	if ( 'month' === $group ) {
		return wp_date( 'F Y', strtotime( $key . '-01 12:00:00' ), new DateTimeZone( 'UTC' ) );
	}
	list( $y, $w ) = explode( '-W', $key );
	$start = ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->setISODate( (int) $y, (int) $w, 1 )->setTime( 12, 0 );
	return dm_week_label( $start->format( 'Y-m-d H:i:s' ) );
}
