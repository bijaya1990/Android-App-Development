<?php
/**
 * Shared <head> for Pikacart's own pages (app, login, messages).
 * Variables: $pkc_title (string), $pkc_styles (array of handles).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

PKC_Assets::register();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="<?php echo esc_attr( pkc_setting( 'brand_color', '#4F46E5' ) ); ?>">
<title><?php echo esc_html( $pkc_title . ' · ' . pkc_setting( 'site_name', 'Pikacart' ) ); ?></title>
<?php
wp_site_icon();
wp_print_styles( $pkc_styles );
?>
</head>
