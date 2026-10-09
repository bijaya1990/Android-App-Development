<?php
/**
 * Page not found.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="wrap notfound">
	<p class="eyebrow">404</p>
	<h1><?php esc_html_e( 'This page could not be found', 'pikacart' ); ?></h1>
	<p><?php esc_html_e( 'The link may be old or mistyped. Let\'s get you back on track.', 'pikacart' ); ?></p>
	<a class="t-btn t-btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to homepage', 'pikacart' ); ?></a>
</section>
<?php
get_footer();
