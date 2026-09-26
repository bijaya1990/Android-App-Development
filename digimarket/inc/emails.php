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
