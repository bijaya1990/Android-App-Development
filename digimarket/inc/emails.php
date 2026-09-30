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
	$biz   = function_exists( 'dm_business' ) ? dm_business() : array( 'legal' => $site, 'address' => '', 'email' => get_option( 'admin_email' ) );
	$btn   = $cta_url ? '<p style="margin:28px 0"><a href="' . esc_url( $cta_url ) . '" style="background:' . esc_attr( $brand ) . ';color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">' . esc_html( $cta_text ) . '</a></p><p style="font-size:12px;color:#888">' . esc_html__( 'If the button does not work, copy this link:', 'digimarket' ) . '<br>' . esc_html( $cta_url ) . '</p>' : '';
	$links = array();
	$pages = (array) get_option( 'dm_legal_pages', array() );
	foreach ( array( 'terms' => __( 'Terms', 'digimarket' ), 'privacy' => __( 'Privacy', 'digimarket' ), 'refund' => __( 'Refunds', 'digimarket' ), 'contact' => __( 'Contact', 'digimarket' ) ) as $k => $label ) {
		if ( ! empty( $pages[ $k ] ) && get_post( $pages[ $k ] ) ) {
			$links[] = '<a href="' . esc_url( get_permalink( $pages[ $k ] ) ) . '" style="color:#888;text-decoration:underline">' . esc_html( $label ) . '</a>';
		}
	}
	$foot  = '<strong style="color:#555">' . esc_html( $biz['legal'] ) . '</strong>'
		. ( $biz['address'] ? '<br>' . esc_html( $biz['address'] ) : '' )
		. ( $biz['email'] ? '<br><a href="mailto:' . esc_attr( $biz['email'] ) . '" style="color:#888">' . esc_html( $biz['email'] ) . '</a>' : '' )
		. ( $links ? '<br>' . implode( ' · ', $links ) : '' )
		. '<br><br>' . esc_html( sprintf( /* translators: %s site */ __( 'You received this email because of your account at %s.', 'digimarket' ), $site ) );
	$body  = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $subject ) . '</title></head><body style="margin:0;background:#f4f4f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1d1d1f">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 12px">'
		. '<table role="presentation" width="100%" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #ececf1" cellpadding="0" cellspacing="0">'
		. '<tr><td style="height:4px;background:' . esc_attr( $brand ) . ';font-size:0;line-height:0">&nbsp;</td></tr>'
		. '<tr><td style="padding:22px 28px 18px;border-bottom:1px solid #f0f0f4"><a href="' . esc_url( home_url( '/' ) ) . '" style="text-decoration:none">' . dm_email_logo() . '</a></td></tr>'
		. '<tr><td style="padding:28px;font-size:15px;line-height:1.6">' . $html . $btn . '</td></tr>'
		. '<tr><td style="padding:18px 28px;background:#fafafb;font-size:12px;line-height:1.6;color:#888;border-top:1px solid #f0f0f4">' . $foot . '</td></tr>'
		. '</table></td></tr></table></body></html>';
	return wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
}

/**
 * Logo for email headers. Email apps (Gmail, Outlook) do not show SVG, so a PNG is used:
 * the site's custom logo when it is a PNG/JPG, else the bundled PikaCart logo.
 */
function dm_email_logo() {
	$site = get_bloginfo( 'name' );
	$src  = DM_URI . '/assets/img/email-logo.png';
	$w    = 164;
	$h    = 36;
	$cid  = (int) get_theme_mod( 'custom_logo' );
	if ( $cid ) {
		$img = wp_get_attachment_image_src( $cid, 'full' );
		if ( $img && preg_match( '/\.(png|jpe?g|gif)$/i', $img[0] ) && $img[2] > 0 ) {
			$src = $img[0];
			$h   = 36;
			$w   = (int) round( $img[1] * $h / $img[2] );
		}
	}
	return '<img src="' . esc_url( $src ) . '" width="' . (int) $w . '" height="' . (int) $h . '" alt="' . esc_attr( $site ) . '" style="display:block;border:0;height:' . (int) $h . 'px;width:' . (int) $w . 'px;max-width:100%">';
}

/**
 * Order receipt email: itemised table (list price, discount, amount), totals,
 * payment details and billing info — like a proper store receipt.
 */
function dm_email_receipt_html( $order ) {
	$items   = dm_get_order_items( $order->id );
	$muted   = 'color:#6b6b76';
	$th      = 'padding:10px 8px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b6b76;border-bottom:2px solid #ececf1';
	$td      = 'padding:12px 8px;font-size:14px;border-bottom:1px solid #f0f0f4;vertical-align:top';
	$list    = 0;
	$final   = 0;
	$gst     = 0;
	$rows    = '';
	$n       = 0;
	foreach ( $items as $it ) {
		// MRP: the product's regular price (before sale and coupon) at the time of purchase.
		$lp     = max( (float) $it->list_price, (float) $it->price_at_purchase, function_exists( 'dm_product_regular_price' ) && get_post( $it->product_id ) ? (float) dm_product_regular_price( $it->product_id ) : 0 );
		$disc   = max( 0, $lp - (float) $it->price_at_purchase );
		$list  += $lp;
		$final += (float) $it->price_at_purchase;
		$tax    = function_exists( 'dm_item_tax' ) ? dm_item_tax( $it ) : null;
		$gst   += $tax ? $tax['tax'] : 0;
		$by     = dm_show_sold_by( $it->seller_id ) ? dm_shop_name( $it->seller_id ) : get_bloginfo( 'name' );
		$extra  = $it->license_key ? '<div style="margin-top:6px"><span style="' . $muted . ';font-size:12px">' . esc_html__( 'License key:', 'digimarket' ) . '</span> <span style="font-family:monospace;background:#f4f4f7;padding:2px 6px;border-radius:4px;font-size:13px">' . esc_html( $it->license_key ) . '</span></div>' : '';
		$rows  .= '<tr><td style="' . $td . ';' . $muted . '">' . ( ++$n ) . '</td><td style="' . $td . '"><strong>' . esc_html( $it->product_title ) . '</strong><div style="' . $muted . ';font-size:12px">' . esc_html( sprintf( /* translators: %s shop */ __( 'Sold by %s', 'digimarket' ), $by ) ) . ( $tax ? ' · ' . esc_html( sprintf( /* translators: %s rate */ __( 'incl. %s%% GST', 'digimarket' ), rtrim( rtrim( number_format( $tax['rate'], 2 ), '0' ), '.' ) ) ) : '' ) . '</div>' . $extra . '</td>'
			. '<td style="' . $td . ';text-align:right;white-space:nowrap">' . esc_html( dm_money( $lp ) ) . '</td>'
			. '<td style="' . $td . ';text-align:right;white-space:nowrap;color:#12805c">' . ( $disc > 0 ? '−' . esc_html( dm_money( $disc ) ) : '<span style="' . $muted . '">—</span>' ) . '</td>'
			. '<td style="' . $td . ';text-align:right;white-space:nowrap;font-weight:600">' . esc_html( dm_money( $it->price_at_purchase ) ) . '</td></tr>';
	}
	$line = function ( $label, $value, $style = '' ) {
		return '<tr><td style="padding:5px 8px;font-size:14px;color:#44444c;' . $style . '">' . $label . '</td><td style="padding:5px 8px;font-size:14px;text-align:right;white-space:nowrap;' . $style . '">' . $value . '</td></tr>';
	};
	$saved  = max( 0, $list - $final );
	$totals = $line( esc_html__( 'Subtotal (MRP)', 'digimarket' ), esc_html( dm_money( $list ) ) );
	if ( $saved > 0 ) {
		$label   = $order->coupon_code ? sprintf( /* translators: %s code */ esc_html__( 'Discount (incl. coupon %s)', 'digimarket' ), '<strong>' . esc_html( $order->coupon_code ) . '</strong>' ) : esc_html__( 'Discount', 'digimarket' );
		$totals .= $line( $label, '−' . esc_html( dm_money( $saved ) ), 'color:#12805c' );
	}
	if ( $gst > 0 ) {
		$totals .= $line( esc_html__( 'GST (included in prices)', 'digimarket' ), esc_html( dm_money( $gst ) ) );
	}
	$totals .= '<tr><td colspan="2" style="padding:6px 8px 0"><div style="border-top:2px solid #1d1d1f;font-size:0;line-height:0">&nbsp;</div></td></tr>';
	$totals .= $line( '<strong>' . esc_html__( 'Total paid', 'digimarket' ) . '</strong>', '<strong style="font-size:18px">' . esc_html( dm_money( $order->order_total ) ) . '</strong>', 'padding-top:8px;color:#1d1d1f' );

	$gateways = array( 'razorpay' => 'Razorpay', 'cashfree' => 'Cashfree Payments', 'demo' => __( 'Test payment', 'digimarket' ) );
	$method   = isset( $gateways[ $order->gateway ] ) ? $gateways[ $order->gateway ] : ucfirst( (string) $order->gateway );
	$paid_at  = ! empty( $order->paid_at ) ? $order->paid_at : $order->created_at;
	$meta     = function ( $label, $value ) use ( $muted ) {
		return '<tr><td style="padding:3px 0;font-size:13px;' . $muted . ';width:120px">' . esc_html( $label ) . '</td><td style="padding:3px 0;font-size:13px">' . $value . '</td></tr>';
	};
	$left  = '<table role="presentation" cellpadding="0" cellspacing="0">'
		. $meta( __( 'Receipt no.', 'digimarket' ), '<strong>' . esc_html( dm_order_number( $order ) ) . '</strong>' )
		. $meta( __( 'Date', 'digimarket' ), esc_html( mysql2date( 'j M Y, g:i a', $paid_at ) ) )
		. $meta( __( 'Payment', 'digimarket' ), esc_html( (float) $order->order_total > 0 ? $method : __( 'Free order', 'digimarket' ) ) )
		. ( $order->razorpay_payment_id ? $meta( __( 'Transaction ID', 'digimarket' ), '<span style="font-family:monospace">' . esc_html( $order->razorpay_payment_id ) . '</span>' ) : '' )
		. $meta( __( 'Status', 'digimarket' ), '<span style="display:inline-block;padding:1px 10px;border-radius:99px;background:#e7f6ef;color:#12805c;font-weight:600;font-size:12px">' . esc_html__( 'PAID', 'digimarket' ) . '</span>' )
		. '</table>';
	$right = '<div style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;' . $muted . '">' . esc_html__( 'Billed to', 'digimarket' ) . '</div><div style="font-size:14px;font-weight:600;margin-top:4px">' . esc_html( $order->buyer_name ) . '</div><div style="font-size:13px;' . $muted . '">' . esc_html( $order->buyer_email ) . '</div>';

	return '<p style="margin:0 0 4px;font-size:22px;font-weight:700">' . esc_html__( 'Payment receipt', 'digimarket' ) . '</p>'
		. '<p style="margin:0 0 20px;' . $muted . '">' . sprintf( /* translators: %s name */ esc_html__( 'Hi %s, thank you for your purchase! Your order is confirmed and your downloads are ready.', 'digimarket' ), esc_html( $order->buyer_name ) ) . '</p>'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8fb;border-radius:10px"><tr><td style="padding:16px 18px;vertical-align:top">' . $left . '</td><td style="padding:16px 18px;vertical-align:top;text-align:right">' . $right . '</td></tr></table>'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:22px"><tr><th style="' . $th . ';text-align:left;width:20px">#</th><th style="' . $th . ';text-align:left">' . esc_html__( 'Item', 'digimarket' ) . '</th><th style="' . $th . ';text-align:right">' . esc_html__( 'Price', 'digimarket' ) . '</th><th style="' . $th . ';text-align:right">' . esc_html__( 'Discount', 'digimarket' ) . '</th><th style="' . $th . ';text-align:right">' . esc_html__( 'Amount', 'digimarket' ) . '</th></tr>' . $rows . '</table>'
		. '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:14px 0 0 auto;min-width:280px">' . $totals . '</table>'
		. ( $saved > 0 ? '<p style="margin:14px 0 0;text-align:right;font-size:13px;color:#12805c;font-weight:600">' . esc_html( sprintf( /* translators: %s amount */ __( 'You saved %s on this order', 'digimarket' ), dm_money( $saved ) ) ) . '</p>' : '' )
		. '<p style="margin:24px 0 0;font-size:13px"><a href="' . esc_url( dm_url( 'invoice', $order->id ) ) . '" style="color:#5b4bff;font-weight:600">' . esc_html( $gst > 0 ? __( 'Download tax invoice (PDF / print)', 'digimarket' ) : __( 'Download receipt (PDF / print)', 'digimarket' ) ) . '</a> · <a href="' . esc_url( dm_url( 'account', 'support' ) ) . '" style="color:#5b4bff">' . esc_html__( 'Need help?', 'digimarket' ) . '</a></p>';
}

function dm_email_order_confirmation( $order_id ) {
	$order = dm_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	/* translators: %s order */
	dm_mail( $order->buyer_email, sprintf( __( 'Receipt for your order %s', 'digimarket' ), dm_order_number( $order ) ), dm_email_receipt_html( $order ), dm_url( 'account', 'purchases' ), __( 'Download your files', 'digimarket' ) );

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
		dm_mail( $seller->user_email, __( 'You made a sale!', 'digimarket' ), '<p>' . sprintf( /* translators: %s order */ esc_html__( 'Congratulations — new order %s:', 'digimarket' ), '<strong>' . esc_html( dm_order_number( $order ) ) . '</strong>' ) . '</p><ul>' . $list . '</ul>', dm_url( 'dashboard', 'orders' ), __( 'View orders', 'digimarket' ) );
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
		__( 'A commission is deducted from each sale at the rate shown in your dashboard at the time of sale (plus GST on the commission while our GST is active). Your share is paid automatically to your bank through Razorpay.', 'digimarket' ),
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
