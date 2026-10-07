<?php
/**
 * Adsterra slots. Fixed reserved sizes (zero CLS). Code sits inside inert <template> tags and is
 * injected by assets/js/ads.js only after first interaction/delay, when the slot nears the viewport,
 * one at a time so atOptions never collide. Admins never see ads.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nppro_ads_enabled() {
	return ! is_user_logged_in() || ! current_user_can( 'manage_options' );
}

/**
 * Return markup for a slot ('' when off/empty/over density cap).
 *
 * @param string $slot  Slot key.
 * @param string $extra Extra CSS class.
 * @return string
 */
function nppro_ad_html( $slot, $extra = '' ) {
	static $count = 0;
	$slots = nppro_ad_slots();
	$cfg   = nppro_opt( 'ads' );
	if ( ! nppro_ads_enabled() || ! isset( $slots[ $slot ], $cfg[ $slot ] ) ) {
		return '';
	}
	$c = $cfg[ $slot ];
	if ( empty( $c['on'] ) || '' === trim( (string) $c['code'] ) ) {
		return '';
	}
	if ( 'sticky' !== $slot && $count >= (int) nppro_opt( 'ad_max' ) ) {
		return '';
	}
	++$count;
	$dev = isset( $c['device'] ) ? $c['device'] : 'all';
	if ( 'header' === $slot && is_singular() ) {
		$dev = ( 'mobile' === $dev ) ? 'none' : 'desktop'; // Never above the title on mobile.
	}
	$cls = 'np-ad np-ad--' . $slot . ( 'desktop' === $dev ? ' np-hide-m' : '' ) . ( 'mobile' === $dev ? ' np-hide-d' : '' ) . ( 'none' === $dev ? ' np-hide-m np-hide-d' : '' ) . ( $extra ? ' ' . $extra : '' );
	$s   = $slots[ $slot ];
	$out = sprintf( '<aside class="%s" style="--dw:%dpx;--dh:%dpx;--mw:%dpx;--mh:%dpx" aria-label="%s" data-np-ad>', esc_attr( $cls ), $s[1][0], $s[1][1], $s[2][0], $s[2][1], esc_attr__( 'Advertisement', 'naukripatra' ) );
	$out .= '<span class="np-ad__label">' . esc_html__( 'Advertisement', 'naukripatra' ) . '</span><div class="np-ad__box"></div>';
	if ( 'sticky' === $slot ) {
		$out .= '<button type="button" class="np-ad__close" aria-label="' . esc_attr__( 'Close advertisement', 'naukripatra' ) . '">&times;</button>';
	}
	$out .= '<template>' . $c['code'] . '</template></aside>'; // Admin-supplied ad code (manage_options only).
	return $out;
}

function nppro_ad( $slot, $extra = '' ) {
	echo nppro_ad_html( $slot, $extra ); // phpcs:ignore WordPress.Security.EscapeOutput
}

function nppro_ads_present() {
	if ( ! nppro_ads_enabled() ) {
		return false;
	}
	foreach ( (array) nppro_opt( 'ads' ) as $c ) {
		if ( ! empty( $c['on'] ) && '' !== trim( (string) $c['code'] ) ) {
			return true;
		}
	}
	return '' !== trim( (string) nppro_opt( 'ad_social_bar' ) ) || '' !== trim( (string) nppro_opt( 'ad_popunder' ) );
}

// Sticky mobile ad, footer banner, and optional Social Bar / Popunder (global, lazy).
add_action( 'wp_footer', function () {
	nppro_ad( 'footer' );
	nppro_ad( 'sticky' );
	if ( nppro_ads_enabled() ) {
		foreach ( array( 'ad_social_bar', 'ad_popunder' ) as $k ) {
			if ( '' !== trim( (string) nppro_opt( $k ) ) ) {
				echo '<template data-np-global>' . nppro_opt( $k ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}
}, 5 );
