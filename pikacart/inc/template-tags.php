<?php
/**
 * Small helpers for templates. They work even if the plugin is off.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

function pkc_theme_setting( $key, $fallback = '' ) {
	return function_exists( 'pkc_setting' ) ? pkc_setting( $key, $fallback ) : $fallback;
}

function pkc_theme_url( $route ) {
	return function_exists( 'pkc_url' ) ? pkc_url( $route ) : wp_login_url();
}

function pkc_theme_page( $key ) {
	return function_exists( 'pkc_page_url' ) ? pkc_page_url( $key ) : '';
}

function pkc_theme_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	if ( function_exists( 'pkc_logo_html' ) ) {
		echo pkc_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped inside the plugin.
		return;
	}
	echo '<a class="pkc-logo" href="' . esc_url( home_url( '/' ) ) . '"><span class="pkc-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span></a>';
}

function pkc_theme_icon( $name ) {
	$paths = array(
		'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'upload'  => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
		'qr'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>',
		'printer' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
		'ruler'   => '<path d="M3 17 17 3l4 4L7 21z"/><path d="m7 13 2 2M10 10l2 2M13 7l2 2"/>',
		'image'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
		'sparkles' => '<path d="M12 3l1.8 4.7L18.5 9.5 13.8 11.3 12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/>',
		'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.5-.8 1.5-1.6 0-1.2-1-1.6-1-2.7 0-.9.7-1.7 1.7-1.7H16a5 5 0 0 0 5-5C21 6.4 17 3 12 3z"/>',
		'users'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
		'menu'    => '<path d="M4 6h16M4 12h16M4 18h16"/>',
		'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
		'shield'  => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'phone'   => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
	);
	$path = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
	return '<svg class="t-i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/**
 * Visible categories for the homepage grid, read from the plugin's tables.
 */
function pkc_theme_categories() {
	if ( ! class_exists( 'PKC_REST_Account' ) ) {
		return array();
	}
	$cats = PKC_REST_Account::catalog();
	$pick = array_filter( array_map( 'trim', explode( ',', (string) pkc_theme_setting( 'home_featured_cats', '' ) ) ) );
	if ( ! $pick ) {
		return $cats;
	}
	$out = array();
	foreach ( $pick as $slug ) {
		foreach ( $cats as $c ) {
			if ( isset( $c['slug'] ) && $c['slug'] === $slug ) {
				$out[] = $c;
			}
		}
	}
	return $out ? $out : $cats;
}

/**
 * One sample ID card drawn in HTML/CSS (original artwork).
 */
function pkc_theme_sample_card( $variant, $org, $name, $role, $color ) {
	ob_start();
	?>
	<div class="sc sc-<?php echo esc_attr( $variant ); ?>" style="--c: <?php echo esc_attr( $color ); ?>" aria-hidden="true">
		<div class="sc-head"><span class="sc-logo"></span><span class="sc-org"><?php echo esc_html( $org ); ?></span></div>
		<div class="sc-photo"><span></span></div>
		<div class="sc-name"><?php echo esc_html( $name ); ?></div>
		<div class="sc-role"><?php echo esc_html( $role ); ?></div>
		<div class="sc-rows"><i></i><i></i><i class="short"></i></div>
		<div class="sc-foot"><span class="sc-qr"></span><span class="sc-bar"></span></div>
	</div>
	<?php
	return ob_get_clean();
}
