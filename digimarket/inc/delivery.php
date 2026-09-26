<?php
/**
 * Secure digital delivery: signed expiring links, access checks, logging.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_sign( $payload ) {
	return rtrim( strtr( base64_encode( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) . 'dm-dl', true ) ), '+/', '-_' ), '=' );
}

/**
 * Checks if a paid order item still grants access. Returns true or WP_Error.
 */
function dm_item_access( $item, $uid ) {
	if ( ! $item ) {
		return new WP_Error( 'missing', __( 'Purchase not found.', 'digimarket' ) );
	}
	$order = dm_get_order( $item->order_id );
	if ( ! $order || (int) $order->buyer_id !== (int) $uid ) {
		return new WP_Error( 'owner', __( 'This purchase belongs to another account.', 'digimarket' ) );
	}
	if ( 'paid' !== $item->item_status ) {
		return new WP_Error( 'status', __( 'Access to this item has been revoked (refunded or unpaid).', 'digimarket' ) );
	}
	if ( $item->access_expires && strtotime( $item->access_expires ) < current_time( 'timestamp' ) ) {
		return new WP_Error( 'expired', __( 'Your access period for this product has ended.', 'digimarket' ) );
	}
	$limit = (int) get_post_meta( $item->product_id, '_dm_download_limit', true );
	if ( $limit > 0 && (int) $item->download_count >= $limit ) {
		return new WP_Error( 'limit', __( 'You have reached the download limit for this product. Contact support if you need help.', 'digimarket' ) );
	}
	return true;
}

/**
 * Generate a fresh signed link (valid N minutes) for an order item.
 */
function dm_signed_download_url( $item_id, $uid ) {
	$exp     = time() + max( 1, (int) dm_opt( 'link_expiry_minutes', 30 ) ) * 60;
	$payload = $item_id . '.' . $uid . '.' . $exp;
	return dm_url( 'download', $payload . '.' . dm_sign( $payload ) );
}

/**
 * Buyer clicks "Download" → /account/download/{item}?_wpnonce → redirect to a signed link.
 */
function dm_account_download_redirect( $item_id ) {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'dm_dl_' . $item_id ) ) {
		dm_flash( 'error', __( 'Link expired. Click Download again.', 'digimarket' ) );
		dm_redirect( dm_url( 'account', 'purchases' ) );
	}
	$item = dm_get_order_item( $item_id );
	$ok   = dm_item_access( $item, get_current_user_id() );
	if ( is_wp_error( $ok ) ) {
		dm_flash( 'error', $ok->get_error_message() );
		dm_redirect( dm_url( 'account', 'purchases' ) );
	}
	dm_redirect( dm_signed_download_url( $item_id, get_current_user_id() ) );
}

function dm_download_button_url( $item_id ) {
	return wp_nonce_url( dm_url( 'account', 'download', $item_id ), 'dm_dl_' . $item_id );
}

/**
 * Serve /download/{token}.
 */
function dm_serve_download( $token ) {
	$parts = explode( '.', (string) $token );
	if ( 4 !== count( $parts ) ) {
		wp_die( esc_html__( 'Invalid download link.', 'digimarket' ), '', array( 'response' => 403 ) );
	}
	list( $item_id, $uid, $exp, $sig ) = $parts;
	if ( ! hash_equals( dm_sign( $item_id . '.' . $uid . '.' . $exp ), $sig ) ) {
		wp_die( esc_html__( 'Invalid download link.', 'digimarket' ), '', array( 'response' => 403 ) );
	}
	if ( (int) $exp < time() ) {
		wp_die( wp_kses_post( sprintf( /* translators: %s url */ __( 'This download link has expired. <a href="%s">Get a fresh link from My Purchases</a>.', 'digimarket' ), esc_url( dm_url( 'account', 'purchases' ) ) ) ), '', array( 'response' => 410 ) );
	}
	if ( ! is_user_logged_in() || get_current_user_id() !== (int) $uid ) {
		dm_redirect( dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_url( 'account', 'purchases' ) ) ) ) );
	}
	$item = dm_get_order_item( absint( $item_id ) );
	$ok   = dm_item_access( $item, (int) $uid );
	if ( is_wp_error( $ok ) ) {
		wp_die( esc_html( $ok->get_error_message() ), '', array( 'response' => 403 ) );
	}
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'order_items' ) . ' SET download_count = download_count + 1 WHERE id = %d', $item->id ) );
	$wpdb->insert(
		dm_table( 'downloads' ),
		array(
			'order_item_id' => $item->id,
			'user_id'       => (int) $uid,
			'product_id'    => $item->product_id,
			'ip'            => dm_client_ip(),
			'user_agent'    => mb_substr( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '', 0, 250 ),
			'created_at'    => dm_now(),
		)
	);
	$delivery = get_post_meta( $item->product_id, '_dm_delivery', true );
	if ( 'external_link' === $delivery ) {
		$url = get_post_meta( $item->product_id, '_dm_external_url', true );
		if ( $url ) {
			wp_redirect( $url ); // phpcs:ignore -- external destination set by seller.
			exit;
		}
	}
	dm_stream_product_file( $item->product_id, $item->id );
}

/**
 * Stream the private file of a product.
 */
function dm_stream_product_file( $pid, $item_id ) {
	$stored = get_post_meta( $pid, '_dm_file', true );
	$path   = $stored ? dm_private_dir() . '/' . basename( $stored ) : '';
	if ( ! $path || ! is_readable( $path ) ) {
		wp_die( esc_html__( 'The file is not available right now. The seller has been notified.', 'digimarket' ), '', array( 'response' => 404 ) );
	}
	$name = get_post_meta( $pid, '_dm_file_name', true );
	$name = $name ? $name : 'download';
	$type = wp_check_filetype( $name );
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	nocache_headers();
	header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', $name ) . '"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	$fh = fopen( $path, 'rb' ); // phpcs:ignore
	while ( $fh && ! feof( $fh ) ) {
		echo fread( $fh, 1048576 ); // phpcs:ignore
		flush();
	}
	if ( $fh ) {
		fclose( $fh ); // phpcs:ignore
	}
	exit;
}
