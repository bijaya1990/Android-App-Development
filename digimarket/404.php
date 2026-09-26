<?php
/**
 * 404.
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-container dm-page">
	<?php dm_empty_state( __( 'Page not found', 'digimarket' ), __( 'The page you’re looking for doesn’t exist or was moved.', 'digimarket' ), dm_products_url(), __( 'Browse products', 'digimarket' ) ); ?>
</div>
<?php
get_footer();
