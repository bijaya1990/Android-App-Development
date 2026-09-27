<?php
/**
 * /sale/ — every product currently on sale, biggest discount first.
 *
 * @package DigiMarket
 */

get_header();

$dm_ids  = dm_sale_product_ids( 200 );
$dm_page = max( 1, absint( $_GET['pg'] ?? 1 ) ); // phpcs:ignore
$dm_per  = 24;
$dm_show = array_slice( $dm_ids, ( $dm_page - 1 ) * $dm_per, $dm_per );
$dm_ends = array_filter( array_map( 'dm_sale_ends_ts', $dm_ids ) );
?>
<div class="dm-container dm-page dm-sale-page">
	<?php dm_render_breadcrumbs(); ?>
	<header class="dm-sale-hero">
		<div>
			<span class="dm-kicker"><?php esc_html_e( 'Limited-time prices', 'digimarket' ); ?></span>
			<h1><?php esc_html_e( 'Sale — best deals right now', 'digimarket' ); ?></h1>
			<p><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d product on sale, biggest discounts first.', '%d products on sale, biggest discounts first.', count( $dm_ids ), 'digimarket' ), count( $dm_ids ) ) ); ?></p>
		</div>
		<?php echo $dm_ends ? dm_countdown_html( min( $dm_ends ), __( 'Next deal ends in', 'digimarket' ) ) : ''; // phpcs:ignore ?>
	</header>
	<?php if ( $dm_show ) : ?>
		<div class="dm-grid">
			<?php foreach ( $dm_show as $dm_i => $dm_id ) { dm_render_product_card( $dm_id, array( 'eager' => $dm_i < 4, 'h' => 'h2' ) ); } ?>
		</div>
		<?php if ( count( $dm_ids ) > $dm_per ) : ?>
			<nav class="dm-pagination" aria-label="<?php esc_attr_e( 'Pages', 'digimarket' ); ?>">
				<?php for ( $dm_n = 1; $dm_n <= ceil( count( $dm_ids ) / $dm_per ); $dm_n++ ) : ?>
					<a class="dm-pagenum<?php echo $dm_n === $dm_page ? ' current' : ''; ?>" href="<?php echo esc_url( $dm_n > 1 ? add_query_arg( 'pg', $dm_n, dm_url( 'sale' ) ) : dm_url( 'sale' ) ); ?>"><?php echo (int) $dm_n; ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<?php dm_empty_state( __( 'No sale is running right now', 'digimarket' ), __( 'Our next sale is coming soon. Meanwhile, browse everything in the shop.', 'digimarket' ), dm_products_url(), __( 'Browse products', 'digimarket' ) ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
