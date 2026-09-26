<?php
/**
 * Shared helpers: settings, URLs, money, sellers, commission, flash messages.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

function dm_default_settings() {
	return array(
		'currency_symbol'        => '₹',
		'currency_code'          => 'INR',
		'commission_global'      => 10,
		'commission_start_date'  => '',
		'auto_approve_sellers'   => 1,
		'require_email_verify'   => 1,
		'gateway'                => 'demo',
		'rzp_key_id'             => '',
		'rzp_key_secret'         => '',
		'rzp_webhook_secret'     => '',
		'rzp_profile_category'   => 'ecommerce',
		'rzp_profile_subcategory'=> 'digital_goods',
		'allow_sales_without_kyc'=> 0,
		'admin_2fa'              => 0,
		'admin_idle_minutes'     => 30,
		'refund_window_days'     => 7,
		'sellers_can_refund'     => 1,
		'link_expiry_minutes'    => 30,
		'max_upload_mb'          => 200,
		'allowed_extensions'     => 'pdf,zip,rar,7z,epub,mobi,mp3,wav,flac,mp4,mov,webm,psd,ai,eps,svg,png,jpg,jpeg,webp,fig,sketch,xd,lrtemplate,xmp,dng,cube,txt,md,docx,xlsx,pptx,json,csv,ttf,otf,woff,woff2',
		'featured_shops'         => array(),
		'login_max_attempts'     => 5,
		'login_lockout_minutes'  => 15,
		'invoice_company'        => get_bloginfo( 'name' ),
		'invoice_address'        => '',
		'invoice_gstin'          => '',
		'support_email'          => get_option( 'admin_email' ),
		'order_prefix'           => 'DM-',
	);
}

function dm_settings() {
	static $cache = null;
	if ( null === $cache || did_action( 'dm_settings_saved' ) ) {
		$saved = get_option( 'dm_settings', array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), dm_default_settings() );
	}
	return $cache;
}

function dm_opt( $key, $default = null ) {
	$s = dm_settings();
	return array_key_exists( $key, $s ) ? $s[ $key ] : $default;
}

/* -------------------------------------------------------------------------
 * DB helpers
 * ---------------------------------------------------------------------- */

function dm_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'dm_' . $name;
}

function dm_now() {
	return current_time( 'mysql' );
}

/* -------------------------------------------------------------------------
 * URLs
 * ---------------------------------------------------------------------- */

function dm_pretty_permalinks() {
	return (bool) get_option( 'permalink_structure' );
}

/**
 * Absolute URL for a pretty path, honouring "/index.php/..." style permalinks
 * used on servers without mod_rewrite.
 */
function dm_pretty_url( $path ) {
	global $wp_rewrite;
	$root = ( $wp_rewrite instanceof WP_Rewrite ) ? $wp_rewrite->root : '';
	return home_url( '/' . $root . ltrim( $path, '/' ) );
}

/**
 * Build a URL for one of the theme's virtual routes.
 *
 * @param string $route  cart|checkout|account|dashboard|login|register|sell|...
 * @param string $tab    Optional sub path.
 * @param string $id     Optional id segment.
 * @param array  $args   Extra query args.
 */
function dm_url( $route, $tab = '', $id = '', $args = array() ) {
	if ( dm_pretty_permalinks() ) {
		$path = $route . '/';
		if ( '' !== (string) $tab ) {
			$path .= rawurlencode( $tab ) . '/';
		}
		if ( '' !== (string) $id ) {
			$path .= rawurlencode( $id ) . '/';
		}
		$url = dm_pretty_url( $path );
	} else {
		$q = array( 'dm_route' => $route );
		if ( '' !== (string) $tab ) {
			$q['dm_tab'] = $tab;
		}
		if ( '' !== (string) $id ) {
			$q['dm_id'] = $id;
		}
		$url = add_query_arg( $q, home_url( '/' ) );
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}

function dm_store_url( $seller_id ) {
	$slug = get_user_meta( $seller_id, 'dm_shop_slug', true );
	if ( ! $slug ) {
		return home_url( '/' );
	}
	return dm_pretty_permalinks() ? dm_pretty_url( 'store/' . $slug . '/' ) : add_query_arg( 'dm_store', $slug, home_url( '/' ) );
}

function dm_products_url( $args = array() ) {
	$url = get_post_type_archive_link( 'dm_product' );
	if ( ! $url ) {
		$url = add_query_arg( 'post_type', 'dm_product', home_url( '/' ) );
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}

function dm_current_url() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
	return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri );
}

function dm_redirect( $url ) {
	// Carry any not-yet-shown messages across the redirect.
	$pending = array_merge(
		isset( $GLOBALS['dm_flash_inline'] ) ? (array) $GLOBALS['dm_flash_inline'] : array(),
		isset( $GLOBALS['dm_flash_queue'] ) ? (array) $GLOBALS['dm_flash_queue'] : array()
	);
	if ( $pending && ! headers_sent() ) {
		setcookie( 'dm_flash', wp_json_encode( $pending ), time() + 120, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
	wp_safe_redirect( $url );
	exit;
}

/* -------------------------------------------------------------------------
 * Money
 * ---------------------------------------------------------------------- */

function dm_money( $amount ) {
	return dm_opt( 'currency_symbol', '₹' ) . number_format_i18n( (float) $amount, 2 );
}

function dm_round( $amount ) {
	return round( (float) $amount, 2 );
}

function dm_to_paise( $amount ) {
	return (int) round( (float) $amount * 100 );
}

/* -------------------------------------------------------------------------
 * Products
 * ---------------------------------------------------------------------- */

function dm_product_regular_price( $pid ) {
	return (float) get_post_meta( $pid, '_dm_price', true );
}

function dm_product_sale_price( $pid ) {
	$sale = get_post_meta( $pid, '_dm_sale_price', true );
	if ( '' === $sale || null === $sale ) {
		return null;
	}
	$sale = (float) $sale;
	return ( $sale < dm_product_regular_price( $pid ) ) ? $sale : null;
}

function dm_product_price( $pid ) {
	$sale = dm_product_sale_price( $pid );
	return null === $sale ? dm_product_regular_price( $pid ) : $sale;
}

function dm_price_html( $pid ) {
	$sale = dm_product_sale_price( $pid );
	$reg  = dm_product_regular_price( $pid );
	if ( $reg <= 0 && null === $sale ) {
		return '<span class="dm-price"><span class="dm-price-now">' . esc_html__( 'Free', 'digimarket' ) . '</span></span>';
	}
	if ( null !== $sale ) {
		$off = $reg > 0 ? round( ( ( $reg - $sale ) / $reg ) * 100 ) : 0;
		return '<span class="dm-price"><span class="dm-price-now">' . esc_html( dm_money( $sale ) ) . '</span> <del>' . esc_html( dm_money( $reg ) ) . '</del>' . ( $off > 0 ? ' <span class="dm-off">-' . (int) $off . '%</span>' : '' ) . '</span>';
	}
	return '<span class="dm-price"><span class="dm-price-now">' . esc_html( dm_money( $reg ) ) . '</span></span>';
}

function dm_product_thumb( $pid, $size = 'dm-card' ) {
	if ( has_post_thumbnail( $pid ) ) {
		return get_the_post_thumbnail( $pid, $size, array( 'loading' => 'lazy', 'class' => 'dm-thumb-img' ) );
	}
	return '<div class="dm-thumb-placeholder" aria-hidden="true"><span>' . esc_html( mb_substr( get_the_title( $pid ), 0, 1 ) ) . '</span></div>';
}

function dm_delivery_label( $type ) {
	$map = array(
		'file'          => __( 'Instant download', 'digimarket' ),
		'license_key'   => __( 'License key', 'digimarket' ),
		'external_link' => __( 'Access link', 'digimarket' ),
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : $map['file'];
}

/**
 * Whether a product can be bought right now.
 *
 * @return array [bool $ok, string $reason]
 */
function dm_can_purchase( $pid ) {
	$post = get_post( $pid );
	if ( ! $post || 'dm_product' !== $post->post_type || 'publish' !== $post->post_status ) {
		return array( false, __( 'This product is not available.', 'digimarket' ) );
	}
	$seller = (int) $post->post_author;
	if ( 'active' !== dm_seller_status( $seller ) && ! user_can( $seller, 'manage_options' ) ) {
		return array( false, __( 'This shop is not accepting orders right now.', 'digimarket' ) );
	}
	if ( 'razorpay' === dm_opt( 'gateway' ) && dm_product_price( $pid ) > 0 && ! dm_opt( 'allow_sales_without_kyc' ) && ! user_can( $seller, 'manage_options' ) ) {
		if ( 'verified' !== get_user_meta( $seller, 'dm_kyc_status', true ) || ! get_user_meta( $seller, 'dm_rzp_account_id', true ) ) {
			return array( false, __( 'This seller is completing payout verification. Purchases open soon.', 'digimarket' ) );
		}
	}
	if ( 'license_key' === get_post_meta( $pid, '_dm_delivery', true ) && dm_license_keys_available( $pid ) < 1 ) {
		return array( false, __( 'Sold out — no license keys left.', 'digimarket' ) );
	}
	return array( true, '' );
}

function dm_license_keys_available( $pid ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dm_table( 'license_keys' ) . ' WHERE product_id = %d AND is_used = 0', $pid ) );
}

/**
 * Returns the paid order item giving $uid access to $pid, or null.
 */
function dm_user_purchase( $uid, $pid ) {
	global $wpdb;
	if ( ! $uid ) {
		return null;
	}
	return $wpdb->get_row(
		$wpdb->prepare(
			'SELECT i.* FROM ' . dm_table( 'order_items' ) . ' i INNER JOIN ' . dm_table( 'orders' ) . " o ON o.id = i.order_id
			 WHERE o.buyer_id = %d AND i.product_id = %d AND i.item_status = 'paid' ORDER BY i.id DESC LIMIT 1",
			$uid,
			$pid
		)
	);
}

/* -------------------------------------------------------------------------
 * Sellers
 * ---------------------------------------------------------------------- */

function dm_seller_status( $uid ) {
	return (string) get_user_meta( $uid, 'dm_seller_status', true );
}

function dm_is_seller( $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	return $uid && in_array( dm_seller_status( $uid ), array( 'draft', 'pending', 'active', 'suspended', 'rejected' ), true );
}

function dm_is_active_seller( $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	return $uid && 'active' === dm_seller_status( $uid );
}

function dm_shop_name( $uid ) {
	$n = get_user_meta( $uid, 'dm_shop_name', true );
	if ( ! $n ) {
		$u = get_userdata( $uid );
		$n = $u ? $u->display_name : __( 'Shop', 'digimarket' );
	}
	return $n;
}

function dm_shop_logo( $uid, $class = 'dm-avatar' ) {
	$id = (int) get_user_meta( $uid, 'dm_shop_logo', true );
	if ( $id && wp_attachment_is_image( $id ) ) {
		return wp_get_attachment_image( $id, 'thumbnail', false, array( 'class' => $class, 'loading' => 'lazy', 'alt' => dm_shop_name( $uid ) ) );
	}
	$name = dm_shop_name( $uid );
	return '<span class="' . esc_attr( $class ) . ' dm-avatar-letter" style="--dm-accent:' . esc_attr( dm_shop_accent( $uid ) ) . '">' . esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ) . '</span>';
}

function dm_shop_banner_url( $uid ) {
	$id = (int) get_user_meta( $uid, 'dm_shop_banner', true );
	return $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
}

function dm_shop_accent( $uid ) {
	$c = sanitize_hex_color( get_user_meta( $uid, 'dm_shop_accent', true ) );
	return $c ? $c : get_theme_mod( 'dm_primary_color', '#5b4bff' );
}

function dm_get_seller_by_slug( $slug ) {
	$users = get_users(
		array(
			'meta_key'   => 'dm_shop_slug',
			'meta_value' => sanitize_title( $slug ),
			'number'     => 1,
			'fields'     => 'ID',
		)
	);
	return $users ? (int) $users[0] : 0;
}

function dm_slug_available( $slug, $exclude_uid = 0 ) {
	$slug = sanitize_title( $slug );
	if ( strlen( $slug ) < 3 ) {
		return false;
	}
	$reserved = array( 'admin', 'store', 'shop', 'cart', 'checkout', 'account', 'dashboard', 'login', 'register', 'sell', 'products', 'api', 'wp-admin', 'support', 'help' );
	if ( in_array( $slug, $reserved, true ) ) {
		return false;
	}
	$uid = dm_get_seller_by_slug( $slug );
	return ! $uid || (int) $uid === (int) $exclude_uid;
}

function dm_shop_rating( $uid ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT AVG(rating) avg_r, COUNT(*) cnt FROM ' . dm_table( 'reviews' ) . " WHERE seller_id = %d AND status = 'approved'", $uid ) );
	return array(
		'avg'   => $row && $row->cnt ? round( (float) $row->avg_r, 1 ) : 0,
		'count' => $row ? (int) $row->cnt : 0,
	);
}

function dm_seller_product_count( $uid ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'dm_product' AND post_status = 'publish' AND post_author = %d", $uid ) );
}

/* -------------------------------------------------------------------------
 * Commission engine
 * ---------------------------------------------------------------------- */

/**
 * Commission percent that applies right now for a product.
 * Priority: launch promo (0%) > per-seller override > category override > global.
 */
function dm_commission_rate( $seller_id, $product_id = 0 ) {
	$start = dm_opt( 'commission_start_date' );
	if ( $start && strtotime( $start . ' 00:00:00' ) > current_time( 'timestamp' ) ) {
		return 0.0;
	}
	$override = get_user_meta( $seller_id, 'dm_commission_override', true );
	if ( '' !== $override && null !== $override && is_numeric( $override ) ) {
		return max( 0, min( 100, (float) $override ) );
	}
	if ( $product_id ) {
		$terms = get_the_terms( $product_id, 'dm_category' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$cat = get_term_meta( $t->term_id, 'dm_commission', true );
				if ( '' !== $cat && is_numeric( $cat ) ) {
					return max( 0, min( 100, (float) $cat ) );
				}
			}
		}
	}
	return max( 0, min( 100, (float) dm_opt( 'commission_global', 10 ) ) );
}

/* -------------------------------------------------------------------------
 * Orders
 * ---------------------------------------------------------------------- */

function dm_get_order( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . ' WHERE id = %d', $id ) );
}

function dm_get_order_items( $order_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'order_items' ) . ' WHERE order_id = %d ORDER BY id ASC', $order_id ) );
}

function dm_get_order_item( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'order_items' ) . ' WHERE id = %d', $id ) );
}

function dm_order_number( $order ) {
	$id = is_object( $order ) ? $order->id : $order;
	return dm_opt( 'order_prefix', 'DM-' ) . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
}

function dm_status_badge( $status ) {
	$status = (string) $status;
	$map    = array(
		'paid'               => 'success',
		'success'            => 'success',
		'active'             => 'success',
		'verified'           => 'success',
		'processed'          => 'success',
		'settled'            => 'success',
		'publish'            => 'success',
		'approved'           => 'success',
		'open'               => 'warning',
		'pending'            => 'warning',
		'draft'              => 'neutral',
		'under_review'       => 'warning',
		'needs_clarification'=> 'warning',
		'failed'             => 'danger',
		'suspended'          => 'danger',
		'rejected'           => 'danger',
		'refunded'           => 'info',
		'partially_refunded' => 'info',
		'reversed'           => 'info',
		'dm_unpublished'     => 'neutral',
		'not_required'       => 'neutral',
		'closed'             => 'neutral',
		'hidden'             => 'neutral',
	);
	$labels = array(
		'publish'        => __( 'Published', 'digimarket' ),
		'dm_unpublished' => __( 'Unpublished', 'digimarket' ),
		'not_required'   => __( 'Not required', 'digimarket' ),
	);
	$tone  = isset( $map[ $status ] ) ? $map[ $status ] : 'neutral';
	$label = isset( $labels[ $status ] ) ? $labels[ $status ] : ucwords( str_replace( '_', ' ', $status ) );
	return '<span class="dm-badge dm-badge-' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
}

/* -------------------------------------------------------------------------
 * Flash messages (cookie based so they survive redirects for guests too)
 * ---------------------------------------------------------------------- */

function dm_flash( $type, $message ) {
	$GLOBALS['dm_flash_queue'][] = array( 'type' => $type, 'msg' => $message );
	if ( ! headers_sent() ) {
		setcookie( 'dm_flash', wp_json_encode( $GLOBALS['dm_flash_queue'] ), time() + 120, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
}

/**
 * Move flash messages from the cookie into memory before any output,
 * so the cookie can be expired while headers are still open.
 */
add_action( 'template_redirect', 'dm_consume_flash', 1 );
function dm_consume_flash() {
	if ( empty( $_COOKIE['dm_flash'] ) ) {
		return;
	}
	$decoded = json_decode( wp_unslash( $_COOKIE['dm_flash'] ), true );
	if ( is_array( $decoded ) ) {
		$GLOBALS['dm_flash_inline'] = array_merge( isset( $GLOBALS['dm_flash_inline'] ) ? $GLOBALS['dm_flash_inline'] : array(), $decoded );
	}
	if ( ! headers_sent() ) {
		setcookie( 'dm_flash', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
	unset( $_COOKIE['dm_flash'] );
}

function dm_render_flash() {
	$items = array();
	if ( ! empty( $GLOBALS['dm_flash_inline'] ) ) {
		$items = $GLOBALS['dm_flash_inline'];
		$GLOBALS['dm_flash_inline'] = array();
	}
	foreach ( $items as $item ) {
		if ( empty( $item['msg'] ) ) {
			continue;
		}
		$type = in_array( $item['type'] ?? '', array( 'success', 'error', 'info', 'warning' ), true ) ? $item['type'] : 'info';
		echo '<div class="dm-notice dm-notice-' . esc_attr( $type ) . '" role="alert">' . esc_html( $item['msg'] ) . '<button type="button" class="dm-notice-close" aria-label="' . esc_attr__( 'Dismiss', 'digimarket' ) . '">&times;</button></div>';
	}
}

/* -------------------------------------------------------------------------
 * Misc
 * ---------------------------------------------------------------------- */

function dm_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

function dm_audit( $action, $target_type = '', $target_id = 0, $details = array() ) {
	global $wpdb;
	$wpdb->insert(
		dm_table( 'audit_logs' ),
		array(
			'admin_id'    => get_current_user_id(),
			'action'      => $action,
			'target_type' => $target_type,
			'target_id'   => (int) $target_id,
			'details'     => wp_json_encode( $details ),
			'ip'          => dm_client_ip(),
			'created_at'  => dm_now(),
		)
	);
}

function dm_notify( $uid, $message, $link = '' ) {
	global $wpdb;
	if ( ! $uid ) {
		return;
	}
	$wpdb->insert(
		dm_table( 'notifications' ),
		array(
			'user_id'    => (int) $uid,
			'message'    => wp_strip_all_tags( $message ),
			'link'       => esc_url_raw( $link ),
			'is_read'    => 0,
			'created_at' => dm_now(),
		)
	);
}

function dm_unread_notifications( $uid ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . dm_table( 'notifications' ) . ' WHERE user_id = %d AND is_read = 0', $uid ) );
}

function dm_stars( $rating, $count = null ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$pct    = $rating / 5 * 100;
	$out    = '<span class="dm-stars" role="img" aria-label="' . esc_attr( sprintf( /* translators: %s rating */ __( 'Rated %s out of 5', 'digimarket' ), number_format_i18n( $rating, 1 ) ) ) . '"><span class="dm-stars-fill" style="width:' . esc_attr( $pct ) . '%"></span></span>';
	if ( null !== $count ) {
		$out .= ' <span class="dm-rating-count">' . esc_html( number_format_i18n( $rating, 1 ) ) . ' (' . (int) $count . ')</span>';
	}
	return $out;
}

/**
 * Symmetric encryption for sensitive seller data (PAN, bank account) at rest.
 */
function dm_crypto_key() {
	$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'dm';
	$salt .= defined( 'SECURE_AUTH_SALT' ) ? SECURE_AUTH_SALT : wp_salt( 'secure_auth' );
	return hash( 'sha256', $salt, true );
}

function dm_encrypt( $plain ) {
	if ( '' === (string) $plain ) {
		return '';
	}
	if ( function_exists( 'sodium_crypto_secretbox' ) ) {
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return 's1:' . base64_encode( $nonce . sodium_crypto_secretbox( (string) $plain, $nonce, dm_crypto_key() ) );
	}
	$iv = random_bytes( 16 );
	return 'o1:' . base64_encode( $iv . openssl_encrypt( (string) $plain, 'aes-256-cbc', dm_crypto_key(), OPENSSL_RAW_DATA, $iv ) );
}

function dm_decrypt( $cipher ) {
	if ( ! is_string( $cipher ) || strlen( $cipher ) < 4 ) {
		return '';
	}
	$raw = base64_decode( substr( $cipher, 3 ), true );
	if ( false === $raw ) {
		return '';
	}
	if ( 0 === strpos( $cipher, 's1:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
		$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$box   = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain = sodium_crypto_secretbox_open( $box, $nonce, dm_crypto_key() );
		return false === $plain ? '' : $plain;
	}
	if ( 0 === strpos( $cipher, 'o1:' ) ) {
		$plain = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', dm_crypto_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
		return false === $plain ? '' : $plain;
	}
	return '';
}

function dm_mask( $value, $visible = 4 ) {
	$value = (string) $value;
	$len   = strlen( $value );
	return $len <= $visible ? $value : str_repeat( '•', $len - $visible ) . substr( $value, -$visible );
}

/**
 * Tiny dependency-free SVG line/area chart.
 *
 * @param array $labels Labels (x axis).
 * @param array $values Numeric values.
 */
function dm_svg_chart( $labels, $values, $money = true ) {
	$w     = 640;
	$h     = 220;
	$pad   = 30;
	$n     = count( $values );
	$max   = $n ? max( $values ) : 0;
	$max   = $max > 0 ? $max : 1;
	$step  = $n > 1 ? ( $w - $pad * 2 ) / ( $n - 1 ) : 0;
	$pts   = array();
	foreach ( array_values( $values ) as $i => $v ) {
		$x     = $pad + $i * $step;
		$y     = $h - $pad - ( (float) $v / $max ) * ( $h - $pad * 2 );
		$pts[] = round( $x, 1 ) . ',' . round( $y, 1 );
	}
	$line = implode( ' ', $pts );
	$area = $n ? $pad . ',' . ( $h - $pad ) . ' ' . $line . ' ' . round( $pad + ( $n - 1 ) * $step, 1 ) . ',' . ( $h - $pad ) : '';
	$out  = '<svg class="dm-chart" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="' . esc_attr__( 'Trend chart', 'digimarket' ) . '">';
	for ( $g = 0; $g <= 4; $g++ ) {
		$gy   = $pad + $g * ( ( $h - $pad * 2 ) / 4 );
		$out .= '<line x1="' . $pad . '" x2="' . ( $w - $pad ) . '" y1="' . $gy . '" y2="' . $gy . '" class="dm-chart-grid"/>';
	}
	if ( $n ) {
		$out .= '<polygon points="' . esc_attr( $area ) . '" class="dm-chart-area"/>';
		$out .= '<polyline points="' . esc_attr( $line ) . '" class="dm-chart-line"/>';
		foreach ( array_values( $values ) as $i => $v ) {
			list( $x, $y ) = explode( ',', $pts[ $i ] );
			$label         = isset( $labels[ $i ] ) ? $labels[ $i ] : '';
			$out          .= '<circle cx="' . $x . '" cy="' . $y . '" r="3" class="dm-chart-dot"><title>' . esc_html( $label . ': ' . ( $money ? dm_money( $v ) : number_format_i18n( $v ) ) ) . '</title></circle>';
		}
		$every = max( 1, (int) ceil( $n / 7 ) );
		foreach ( array_values( $labels ) as $i => $l ) {
			if ( $i % $every ) {
				continue;
			}
			$out .= '<text x="' . round( $pad + $i * $step, 1 ) . '" y="' . ( $h - 8 ) . '" class="dm-chart-label" text-anchor="middle">' . esc_html( $l ) . '</text>';
		}
	}
	$out .= '<text x="' . $pad . '" y="' . ( $pad - 10 ) . '" class="dm-chart-label">' . esc_html( $money ? dm_money( $max ) : number_format_i18n( $max ) ) . '</text>';
	$out .= '</svg>';
	return $out;
}

/**
 * Daily series helper for charts: returns [labels, values] for last $days days.
 *
 * @param array $rows Rows with ->d (Y-m-d) and ->v.
 */
function dm_series( $rows, $days, $from = null ) {
	$map = array();
	foreach ( (array) $rows as $r ) {
		$map[ $r->d ] = (float) $r->v;
	}
	$labels = array();
	$values = array();
	$start  = $from ? strtotime( $from ) : strtotime( '-' . ( $days - 1 ) . ' days', current_time( 'timestamp' ) );
	for ( $i = 0; $i < $days; $i++ ) {
		$d        = gmdate( 'Y-m-d', $start + $i * DAY_IN_SECONDS );
		$labels[] = date_i18n( 'M j', strtotime( $d ) );
		$values[] = isset( $map[ $d ] ) ? $map[ $d ] : 0;
	}
	return array( $labels, $values );
}

/**
 * Render a CSV download and exit.
 */
function dm_output_csv( $filename, $header, $rows ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, $header, ',', '"', '\\' );
	foreach ( $rows as $r ) {
		// Neutralise spreadsheet formula injection.
		$r = array_map(
			function ( $c ) {
				$c = (string) $c;
				return ( '' !== $c && in_array( $c[0], array( '=', '+', '-', '@' ), true ) && ! is_numeric( $c ) ) ? "'" . $c : $c;
			},
			(array) $r
		);
		fputcsv( $out, $r, ',', '"', '\\' );
	}
	fclose( $out );
	exit;
}

function dm_pagination( $total, $per_page, $current, $base_url ) {
	$pages = (int) ceil( $total / max( 1, $per_page ) );
	if ( $pages < 2 ) {
		return '';
	}
	$out = '<nav class="dm-pagination" aria-label="' . esc_attr__( 'Pagination', 'digimarket' ) . '">';
	for ( $i = 1; $i <= $pages; $i++ ) {
		if ( $pages > 10 && abs( $i - $current ) > 2 && 1 !== $i && $pages !== $i ) {
			if ( abs( $i - $current ) === 3 ) {
				$out .= '<span class="dm-page-gap">…</span>';
			}
			continue;
		}
		$out .= $i === $current ? '<span class="dm-pagenum current" aria-current="page">' . $i . '</span>' : '<a class="dm-pagenum" href="' . esc_url( add_query_arg( 'pg', $i, $base_url ) ) . '">' . $i . '</a>';
	}
	return $out . '</nav>';
}

function dm_get_page_num() {
	return isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
}

function dm_empty_state( $title, $text = '', $cta_url = '', $cta_label = '' ) {
	echo '<div class="dm-empty"><div class="dm-empty-icon" aria-hidden="true">✦</div><h3>' . esc_html( $title ) . '</h3>';
	if ( $text ) {
		echo '<p>' . esc_html( $text ) . '</p>';
	}
	if ( $cta_url ) {
		echo '<a class="dm-btn dm-btn-primary" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_label ) . '</a>';
	}
	echo '</div>';
}

function dm_nonce_field( $action ) {
	wp_nonce_field( 'dm_' . $action, '_dmnonce' );
	echo '<input type="hidden" name="dm_action" value="' . esc_attr( $action ) . '">';
}

function dm_categories( $args = array() ) {
	$terms = get_terms( wp_parse_args( $args, array( 'taxonomy' => 'dm_category', 'hide_empty' => false ) ) );
	return is_wp_error( $terms ) ? array() : $terms;
}

function dm_user_suspended( $uid ) {
	return (bool) get_user_meta( $uid, 'dm_suspended', true );
}
