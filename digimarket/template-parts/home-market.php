<?php
/**
 * Marketplace homepage: slider, categories, flash sale, best sellers, promos,
 * trust row and customer reviews (newsletter + footer come from footer.php).
 *
 * @package DigiMarket
 */

?>
<div class="dm-home mk-home">
	<h1 class="screen-reader-text"><?php echo esc_html( dm_store_opt( 'seo_home_title' ) ? dm_store_opt( 'seo_home_title' ) : get_bloginfo( 'name' ) . ' – ' . get_bloginfo( 'description' ) ); ?></h1>
	<?php
	dm_mk_hero();
	dm_mk_categories();
	dm_mk_flash();
	dm_mk_bestsellers();
	dm_mk_promos();
	dm_mk_trust();
	dm_mk_reviews();
	if ( ! dm_query_products( array( 'posts_per_page' => 1, 'fields' => 'ids' ) )->posts ) {
		echo '<section class="mk-sec"><div class="dm-container">';
		dm_empty_state( __( 'The shop is getting ready', 'digimarket' ), __( 'New notes, templates and website packages are coming soon.', 'digimarket' ) );
		echo '</div></section>';
	}
	?>
</div>
