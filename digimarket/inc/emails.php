<?php
/**
 * Transactional emails.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/**
 * Send a branded HTML email.
 *
 * @param string $to       Recipient.
 * @param string $subject  Subject.
 * @param string $html     Body HTML (already escaped).
 * @param string $cta_url  Optional button URL.
 * @param string $cta_text Optional button text.
 */
function dm_mail( $to, $subject, $html, $cta_url = '', $cta_text = '' ) {
	$brand = sanitize_hex_color( get_theme_mod( 'dm_primary_color', '#5b4bff' ) );
	$brand = $brand ? $brand : '#5b4bff';
	$site  = get_bloginfo( 'name' );
	$btn   = $cta_url ? '<p style="margin:28px 0"><a href="' . esc_url( $cta_url ) . '" style="background:' . esc_attr( $brand ) . ';color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">' . esc_html( $cta_text ) . '</a></p><p style="font-size:12px;color:#888">' . esc_html__( 'If the button does not work, copy this link:', 'digimarket' ) . '<br>' . esc_html( $cta_url ) . '</p>' : '';
	$body  = '<!doctype html><html><body style="margin:0;background:#f4f4f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1d1d1f">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 12px">'
		. '<table role="presentation" width="100%" style="max-width:560px;background:#fff;border-radius:12px;overflow:hidden" cellpadding="0" cellspacing="0">'
		. '<tr><td style="background:' . esc_attr( $brand ) . ';padding:18px 28px;color:#fff;font-weight:700;font-size:18px">' . esc_html( $site ) . '</td></tr>'
		. '<tr><td style="padding:28px;font-size:15px;line-height:1.6">' . $html . $btn . '</td></tr>'
		. '<tr><td style="padding:16px 28px;background:#fafafa;font-size:12px;color:#888">' . esc_html( sprintf( /* translators: %s site */ __( 'You received this email because of your account at %s.', 'digimarket' ), $site ) ) . '</td></tr>'
		. '</table></td></tr></table></body></html>';
	return wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
}

function dm_email_order_confirmation( $order_id ) {
	$order = dm_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$rows = '';
	foreach ( dm_get_order_items( $order_id ) as $it ) {
		$extra = $it->license_key ? '<br><span style="font-family:monospace;background:#f4f4f7;padding:2px 6px;border-radius:4px">' . esc_html( $it->license_key ) . '</span>' : '';
		$rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #eee">' . esc_html( $it->product_title ) . '<br><small style="color:#888">' . esc_html( dm_shop_name( $it->seller_id ) ) . '</small>' . $extra . '</td><td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">' . esc_html( dm_money( $it->price_at_purchase ) ) . '</td></tr>';
	}
	$html = '<p>' . sprintf( /* translators: %s name */ esc_html__( 'Hi %s, thanks for your purchase!', 'digimarket' ), esc_html( $order->buyer_name ) ) . '</p>'
		. '<p>' . sprintf( /* translators: %s order */ esc_html__( 'Order %s is confirmed. Your files are ready in My Purchases.', 'digimarket' ), '<strong>' . esc_html( dm_order_number( $order ) ) . '</strong>' ) . '</p>'
		. '<table width="100%" cellpadding="0" cellspacing="0">' . $rows . '<tr><td style="padding:10px 0;font-weight:700">' . esc_html__( 'Total', 'digimarket' ) . '</td><td style="padding:10px 0;text-align:right;font-weight:700">' . esc_html( dm_money( $order->order_total ) ) . '</td></tr></table>'
		. '<p style="font-size:13px"><a href="' . esc_url( dm_url( 'invoice', $order->id ) ) . '">' . esc_html__( 'View / download invoice', 'digimarket' ) . '</a></p>';
	/* translators: %s order */
	dm_mail( $order->buyer_email, sprintf( __( 'Your order %s is ready', 'digimarket' ), dm_order_number( $order ) ), $html, dm_url( 'account', 'purchases' ), __( 'Go to My Purchases', 'digimarket' ) );

	// Seller notifications.
	$by_seller = array();
	foreach ( dm_get_order_items( $order_id ) as $it ) {
		$by_seller[ $it->seller_id ][] = $it;
	}
	foreach ( $by_seller as $sid => $its ) {
		$seller = get_userdata( $sid );
		if ( ! $seller ) {
			continue;
		}
		$list = '';
		foreach ( $its as $it ) {
			$list .= '<li>' . esc_html( $it->product_title ) . ' — ' . esc_html( dm_money( $it->price_at_purchase ) ) . ' (' . esc_html__( 'you earn', 'digimarket' ) . ' ' . esc_html( dm_money( $it->seller_net_amount ) ) . ')</li>';
		}
		dm_mail( $seller->user_email, __( 'You made a sale!', 'digimarket' ), '<p>' . esc_html__( 'Congratulations — new order:', 'digimarket' ) . '</p><ul>' . $list . '</ul>', dm_url( 'dashboard', 'orders' ), __( 'View orders', 'digimarket' ) );
	}
}

function dm_email_refund( $item, $reason = '' ) {
	$order = dm_get_order( $item->order_id );
	if ( ! $order ) {
		return;
	}
	$html = '<p>' . sprintf( /* translators: 1 amount 2 product */ esc_html__( 'A refund of %1$s for “%2$s” has been processed. It usually reaches your account in 5–7 working days.', 'digimarket' ), '<strong>' . esc_html( dm_money( $item->price_at_purchase ) ) . '</strong>', esc_html( $item->product_title ) ) . '</p>';
	if ( $reason ) {
		$html .= '<p style="color:#666">' . esc_html__( 'Note:', 'digimarket' ) . ' ' . esc_html( $reason ) . '</p>';
	}
	dm_mail( $order->buyer_email, __( 'Your refund has been processed', 'digimarket' ), $html, dm_url( 'account', 'orders' ), __( 'View order history', 'digimarket' ) );
}

/**
 * Sent right after a seller finishes registration: thanks, status, the legal
 * record of what they agreed to, GST rules and where to get help.
 */
function dm_email_seller_welcome( $uid, $status ) {
	$u = get_userdata( $uid );
	if ( ! $u ) {
		return false;
	}
	$b     = dm_business();
	$ag    = (array) get_user_meta( $uid, 'dm_agreement_accepted', true );
	$gin   = dm_seller_gstin( $uid );
	$pan   = dm_decrypt( get_user_meta( $uid, 'dm_pan', true ) );
	$last4 = get_user_meta( $uid, 'dm_bank_last4', true );
	$payto = $last4 ? '•••• ' . $last4 . ' (' . get_user_meta( $uid, 'dm_ifsc', true ) . ')' : get_user_meta( $uid, 'dm_upi', true );
	$li    = function ( $k, $v ) {
		return '<tr><td style="padding:6px 10px 6px 0;color:#666;vertical-align:top;white-space:nowrap">' . esc_html( $k ) . '</td><td style="padding:6px 0">' . $v . '</td></tr>';
	};
	$links = '';
	foreach ( array( 'seller' => __( 'Seller Agreement', 'digimarket' ), 'terms' => __( 'Terms & Conditions', 'digimarket' ), 'privacy' => __( 'Privacy Policy', 'digimarket' ), 'refund' => __( 'Refund & Cancellation Policy', 'digimarket' ), 'content' => __( 'Content & IP Policy', 'digimarket' ) ) as $k => $label ) {
		$links .= '<li><a href="' . esc_url( dm_legal_url( $k ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	$html  = '<p>' . esc_html( sprintf( /* translators: %s name */ __( 'Hi %s,', 'digimarket' ), $u->display_name ) ) . '</p>';
	$html .= '<p><strong>' . esc_html( sprintf( /* translators: %s site */ __( 'Thank you for registering as a seller on %s!', 'digimarket' ), $b['name'] ) ) . '</strong> ' . ( 'active' === $status ? esc_html__( 'Your shop is live — you can add products and start selling now.', 'digimarket' ) : esc_html__( 'Your shop is under review. We usually review within 2 working days; you can prepare products as drafts meanwhile.', 'digimarket' ) ) . '</p>';
	$html .= '<h3 style="margin:22px 0 6px;font-size:15px">' . esc_html__( 'Your registration record', 'digimarket' ) . '</h3><table role="presentation" style="font-size:14px;border-collapse:collapse">';
	$html .= $li( __( 'Shop', 'digimarket' ), '<a href="' . esc_url( dm_store_url( $uid ) ) . '">' . esc_html( dm_shop_name( $uid ) ) . '</a>' );
	$html .= $li( __( 'Registered email', 'digimarket' ), esc_html( $u->user_email ) );
	$html .= $li( __( 'Legal name', 'digimarket' ), esc_html( get_user_meta( $uid, 'dm_legal_name', true ) ) );
	$html .= $li( __( 'PAN', 'digimarket' ), esc_html( $pan ? dm_mask( $pan, 4 ) : '—' ) );
	$html .= $li( __( 'Payout to', 'digimarket' ), esc_html( $payto ? $payto : '—' ) );
	$html .= $li( __( 'GSTIN', 'digimarket' ), esc_html( $gin ? $gin : sprintf( /* translators: %s limit */ __( 'Not provided (optional until your sales cross %s in a financial year)', 'digimarket' ), dm_inr( dm_gst_opt( 'threshold' ) ) ) ) );
	if ( ! empty( $ag['time'] ) ) {
		$html .= $li( __( 'Seller Agreement', 'digimarket' ), esc_html( sprintf( /* translators: 1 date 2 version 3 ip */ __( 'Accepted on %1$s (version %2$s) from IP %3$s', 'digimarket' ), wp_date( 'j M Y, g:i a T', (int) $ag['time'] ), $ag['version'] ?? '', $ag['ip'] ?? '' ) ) );
	}
	$html .= '</table>';
	$html .= '<h3 style="margin:22px 0 6px;font-size:15px">' . esc_html__( 'Key points you agreed to', 'digimarket' ) . '</h3><ul style="padding-left:18px;margin:0">';
	foreach ( array(
		__( 'Sell only digital products you created or have the right to sell. Pirated, illegal, adult, hateful or malicious content is prohibited and will be removed.', 'digimarket' ),
		__( 'A commission is deducted from each sale at the rate shown in your dashboard at the time of sale. Your share is paid to your bank through Razorpay.', 'digimarket' ),
		__( 'When a refund or chargeback is approved, your share and the commission for that order are reversed.', 'digimarket' ),
		sprintf( /* translators: %s limit */ __( 'GST registration is compulsory once your turnover crosses %s in a financial year; below that it is optional. You alone are responsible for your own GST and income-tax compliance. If we mark your shop “GST required”, your products are paused until you add a valid GSTIN.', 'digimarket' ), dm_inr( dm_gst_opt( 'threshold' ) ) ),
		__( 'You keep ownership of your products and give us a licence to display and deliver them to buyers.', 'digimarket' ),
		__( 'Fake reviews, fraud or taking buyers off the site to avoid commission can lead to suspension.', 'digimarket' ),
		__( 'The agreement is governed by Indian law; courts at Bargarh, Odisha have jurisdiction.', 'digimarket' ),
	) as $pt ) {
		$html .= '<li style="margin:4px 0">' . esc_html( $pt ) . '</li>';
	}
	$html .= '</ul><h3 style="margin:22px 0 6px;font-size:15px">' . esc_html__( 'Full documents', 'digimarket' ) . '</h3><ul style="padding-left:18px;margin:0">' . $links . '</ul>';
	$html .= '<h3 style="margin:22px 0 6px;font-size:15px">' . esc_html__( 'Help & grievances', 'digimarket' ) . '</h3><p style="margin:0">' . esc_html( sprintf( /* translators: 1 officer 2 business 3 address */ __( 'Grievance Officer: %1$s, %2$s, %3$s', 'digimarket' ), $b['grievance'] ? $b['grievance'] : __( 'Grievance Officer', 'digimarket' ), $b['legal'], $b['address'] ) ) . '<br>' . esc_html__( 'Email:', 'digimarket' ) . ' <a href="mailto:' . esc_attr( $b['email'] ) . '">' . esc_html( $b['email'] ) . '</a> — ' . esc_html__( 'we acknowledge within 48 hours.', 'digimarket' ) . '</p>';
	$html .= '<p style="margin-top:20px;color:#666;font-size:13px">' . esc_html__( 'Please keep this email for your records.', 'digimarket' ) . '</p>';
	/* translators: %s site */
	return dm_mail( $u->user_email, sprintf( __( 'Welcome to %s — seller registration successful', 'digimarket' ), $b['name'] ), $html, dm_url( 'dashboard' ), __( 'Go to my seller dashboard', 'digimarket' ) );
}
