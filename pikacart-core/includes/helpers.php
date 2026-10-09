<?php
/**
 * Small helper functions used everywhere.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Full table name, e.g. pkc_table( 'organisations' ) => wp_pkc_organisations.
 */
function pkc_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'pkc_' . $name;
}

/**
 * Current time in UTC, MySQL format. All dates are stored in UTC.
 */
function pkc_now( $offset_seconds = 0 ) {
	return gmdate( 'Y-m-d H:i:s', time() + (int) $offset_seconds );
}

/**
 * Convert a stored UTC MySQL date to a Unix timestamp (0 when empty).
 */
function pkc_ts( $mysql_utc ) {
	if ( empty( $mysql_utc ) || '0000-00-00 00:00:00' === $mysql_utc ) {
		return 0;
	}
	$ts = strtotime( $mysql_utc . ' UTC' );
	return $ts ? $ts : 0;
}

/**
 * Show a stored UTC date in the site time zone (Asia/Kolkata by default).
 */
function pkc_date( $mysql_utc, $format = '' ) {
	$ts = pkc_ts( $mysql_utc );
	if ( ! $ts ) {
		return '';
	}
	if ( '' === $format ) {
		$format = get_option( 'date_format', 'j M Y' ) . ', ' . get_option( 'time_format', 'g:i a' );
	}
	return wp_date( $format, $ts );
}

/**
 * Read one Pikacart setting.
 */
function pkc_setting( $key, $fallback = null ) {
	return PKC_Settings::get( $key, $fallback );
}

/**
 * Format an amount stored in paise as Indian Rupees.
 */
function pkc_money( $paise, $symbol = true ) {
	$rupees = (int) $paise / 100;
	$text   = ( floor( $rupees ) == $rupees ) ? number_format_i18n( $rupees ) : number_format_i18n( $rupees, 2 );
	return $symbol ? '₹' . $text : $text;
}

/**
 * Visitor IP address. REMOTE_ADDR only, because forwarded headers can be faked.
 */
function pkc_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Keep only digits of a mobile number and use the last 10 for Indian numbers.
 */
function pkc_normalize_mobile( $mobile ) {
	$digits = preg_replace( '/\D+/', '', (string) $mobile );
	if ( strlen( $digits ) > 10 && 0 === strpos( $digits, '91' ) && 12 === strlen( $digits ) ) {
		$digits = substr( $digits, 2 );
	}
	if ( strlen( $digits ) === 11 && '0' === $digits[0] ) {
		$digits = substr( $digits, 1 );
	}
	return $digits;
}

/**
 * Long random token made of letters and digits.
 */
function pkc_token( $length = 32 ) {
	$chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$max   = strlen( $chars ) - 1;
	$out   = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$out .= $chars[ random_int( 0, $max ) ];
	}
	return $out;
}

/**
 * Public URL of a Pikacart route such as login, register or app.
 */
function pkc_url( $route = 'app', $path = '' ) {
	$route = trim( $route, '/' );
	$path  = trim( $path, '/' );
	$url   = home_url( '/' . $route . '/' . ( $path ? $path . '/' : '' ) );
	return $url;
}

/**
 * Decode JSON safely into an array.
 */
function pkc_json( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}
	if ( ! is_string( $value ) || '' === $value ) {
		return array();
	}
	$data = json_decode( $value, true );
	return is_array( $data ) ? $data : array();
}

/**
 * Support email shown everywhere.
 */
function pkc_support_email() {
	$email = pkc_setting( 'contact_email' );
	return $email ? $email : 'contact@pikacart.in';
}

/**
 * Brand logo: the uploaded logo, or a built-in mark with the site name.
 */
function pkc_logo_html( $link = true ) {
	$name = pkc_setting( 'site_name', 'Pikacart' );
	$logo = pkc_setting( 'logo_url', '' );
	if ( $logo ) {
		$inner = '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $name ) . '" class="pkc-logo-img" height="36">';
	} else {
		$inner = '<svg class="pkc-logo-mark" viewBox="0 0 40 40" aria-hidden="true"><rect x="6" y="2" width="28" height="36" rx="6" fill="var(--pkc-brand)"/><rect x="16" y="5.5" width="8" height="2.5" rx="1.25" fill="#fff" opacity=".7"/><circle cx="20" cy="17" r="5" fill="#fff"/><rect x="12" y="25" width="16" height="3" rx="1.5" fill="#fff"/><rect x="15" y="30.5" width="10" height="2.5" rx="1.25" fill="var(--pkc-accent)"/></svg><span class="pkc-logo-text">' . esc_html( $name ) . '</span>';
	}
	if ( ! $link ) {
		return '<span class="pkc-logo">' . $inner . '</span>';
	}
	return '<a class="pkc-logo" href="' . esc_url( home_url( '/' ) ) . '">' . $inner . '</a>';
}

/**
 * URL of a page created on activation (about, contact, pricing, privacy, terms, refund, aup).
 */
function pkc_page_url( $key ) {
	$pages = get_option( 'pkc_pages', array() );
	if ( is_array( $pages ) && ! empty( $pages[ $key ] ) && 'publish' === get_post_status( (int) $pages[ $key ] ) ) {
		return get_permalink( (int) $pages[ $key ] );
	}
	return '';
}
