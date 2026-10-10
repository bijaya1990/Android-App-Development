<?php
/**
 * Pikacart theme setup. All business logic lives in the Pikacart Core plugin;
 * this theme only draws the public website.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

define( 'PKC_THEME_VERSION', '2.1.0' );

require get_template_directory() . '/inc/plugin-check.php';
require get_template_directory() . '/inc/template-tags.php';

add_action( 'after_setup_theme', 'pkc_theme_setup' );
function pkc_theme_setup() {
	load_theme_textdomain( 'pikacart', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 280, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'pikacart' );
	register_nav_menus(
		array(
			'primary' => __( 'Main menu', 'pikacart' ),
			'footer'  => __( 'Footer menu', 'pikacart' ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'pkc_theme_assets', 20 );
function pkc_theme_assets() {
	$deps = array();
	if ( wp_style_is( 'pkc-tokens', 'registered' ) ) {
		$deps[] = 'pkc-tokens';
	} else {
		wp_register_style( 'pkc-theme-fallback', get_template_directory_uri() . '/assets/css/fallback-tokens.css', array(), PKC_THEME_VERSION );
		$deps[] = 'pkc-theme-fallback';
	}
	wp_enqueue_style( 'pkc-theme', get_template_directory_uri() . '/assets/css/theme.css', $deps, PKC_THEME_VERSION );
	wp_enqueue_script( 'pkc-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), PKC_THEME_VERSION, true );
	if ( ( is_front_page() || get_query_var( 'pkc_idcard' ) ) && wp_style_is( 'pkc-public', 'registered' ) ) {
		wp_enqueue_style( 'pkc-public' );
	}
}

/* Faster: no emoji scripts on the public site. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/* Preload the main fonts for a fast first paint. */
add_action( 'wp_head', 'pkc_theme_preload', 1 );
function pkc_theme_preload() {
	if ( ! defined( 'PKC_URL' ) ) {
		return;
	}
	foreach ( array( 'inter-latin-400-normal.woff2', 'plus-jakarta-sans-latin-800-normal.woff2' ) as $font ) {
		echo '<link rel="preload" href="' . esc_url( PKC_URL . 'assets/fonts/' . $font ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}

/* Description meta when no SEO plugin is active (full SEO arrives with the plugin). */
add_action( 'wp_head', 'pkc_theme_meta_description', 3 );
function pkc_theme_meta_description() {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || class_exists( 'PKC_SEO' ) ) {
		return;
	}
	$desc = is_front_page() ? pkc_theme_setting( 'home_hero_text', get_bloginfo( 'description' ) ) : ( is_singular() ? get_the_excerpt() : get_bloginfo( 'description' ) );
	$desc = wp_strip_all_tags( (string) $desc );
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( $desc, 30, '' ) ) . '">' . "\n";
	}
}
