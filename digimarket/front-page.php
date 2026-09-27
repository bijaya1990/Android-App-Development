<?php
/**
 * Homepage: storefront blocks in the order set in Marketplace → Storefront & SEO.
 *
 * @package DigiMarket
 */

get_header();

$dm_blocks = dm_home_blocks();
$dm_has_banner_hero = in_array( 'hero', $dm_blocks, true ) && dm_get_banners( 'hero', 0, 1 );
?>
<div class="dm-home">
	<?php if ( $dm_has_banner_hero || ! in_array( 'hero', $dm_blocks, true ) ) : ?>
		<h1 class="screen-reader-text"><?php echo esc_html( dm_store_opt( 'seo_home_title' ) ? dm_store_opt( 'seo_home_title' ) : get_bloginfo( 'name' ) . ' – ' . get_bloginfo( 'description' ) ); ?></h1>
	<?php endif; ?>
	<?php
	$dm_shown = 0;
	foreach ( $dm_blocks as $dm_block ) {
		if ( dm_render_home_block( $dm_block ) ) {
			++$dm_shown;
		}
	}
	if ( ! dm_query_products( array( 'posts_per_page' => 1, 'fields' => 'ids' ) )->posts ) :
		?>
		<section class="dm-block"><div class="dm-container">
			<?php
			if ( dm_single_seller_mode() ) {
				dm_empty_state( __( 'The shop is getting ready', 'digimarket' ), __( 'New notes, templates and website packages are coming soon.', 'digimarket' ) );
			} else {
				dm_empty_state( __( 'The marketplace is just getting started', 'digimarket' ), __( 'Be one of the first creators to open a shop here.', 'digimarket' ), dm_url( 'sell' ), __( 'Open your shop', 'digimarket' ) );
			}
			?>
		</div></section>
	<?php endif; ?>
	<?php
	if ( ! dm_single_seller_mode() && get_theme_mod( 'dm_show_shops', 1 ) ) :
		$dm_shop_ids = array_filter( array_map( 'intval', (array) dm_opt( 'featured_shops', array() ) ), 'dm_is_active_seller' );
		if ( ! $dm_shop_ids ) {
			$dm_shop_ids = get_users( array( 'meta_key' => 'dm_seller_status', 'meta_value' => 'active', 'number' => 8, 'fields' => 'ID' ) );
		}
		if ( $dm_shop_ids ) :
			?>
			<section class="dm-block"><div class="dm-container">
				<div class="dm-block-head"><h2><?php esc_html_e( 'Featured shops', 'digimarket' ); ?></h2><a class="dm-more" href="<?php echo esc_url( dm_url( 'shops' ) ); ?>"><?php esc_html_e( 'All shops', 'digimarket' ); ?> <?php echo dm_icon( 'arrow', 16 ); // phpcs:ignore ?></a></div>
				<div class="dm-carousel" tabindex="0">
					<?php foreach ( $dm_shop_ids as $dm_sid ) { get_template_part( 'template-parts/shop-card', null, array( 'seller_id' => $dm_sid ) ); } ?>
				</div>
			</div></section>
			<?php
		endif;
	endif;
	?>
</div>
<?php
get_footer();
