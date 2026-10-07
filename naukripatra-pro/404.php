<?php
/**
 * 404 template.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="np-wrap np-card">
	<h1><?php esc_html_e( 'Page not found', 'naukripatra' ); ?></h1>
	<p><?php esc_html_e( 'The page you want is not here. Try the latest jobs or search again.', 'naukripatra' ); ?></p>
	<p><a class="np-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to home', 'naukripatra' ); ?></a></p>
</div>
<?php
get_footer();
