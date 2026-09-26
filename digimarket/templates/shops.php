<?php
/**
 * All shops directory: /shops
 *
 * @package DigiMarket
 */

$dm_page = dm_get_page_num();
$dm_args = array(
	'meta_key'   => 'dm_seller_status',
	'meta_value' => 'active',
	'number'     => 24,
	'paged'      => $dm_page,
	'fields'     => 'ID',
);
$dm_q    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
if ( $dm_q ) {
	$dm_args['meta_query'] = array(
		array( 'key' => 'dm_seller_status', 'value' => 'active' ),
		array( 'key' => 'dm_shop_name', 'value' => $dm_q, 'compare' => 'LIKE' ),
	);
	unset( $dm_args['meta_key'], $dm_args['meta_value'] );
}
$dm_users = new WP_User_Query( $dm_args );
get_header();
?>
<div class="dm-container dm-page">
	<header class="dm-page-head">
		<h1><?php esc_html_e( 'All shops', 'digimarket' ); ?></h1>
		<form method="get" class="dm-inline-search">
			<?php if ( ! dm_pretty_permalinks() ) : ?><input type="hidden" name="dm_route" value="shops"><?php endif; ?>
			<input type="search" name="q" value="<?php echo esc_attr( $dm_q ); ?>" placeholder="<?php esc_attr_e( 'Find a shop…', 'digimarket' ); ?>" aria-label="<?php esc_attr_e( 'Find a shop', 'digimarket' ); ?>">
			<button class="dm-btn dm-btn-primary"><?php esc_html_e( 'Search', 'digimarket' ); ?></button>
		</form>
	</header>
	<?php if ( $dm_users->get_results() ) : ?>
		<div class="dm-grid dm-grid-shops">
			<?php foreach ( $dm_users->get_results() as $dm_sid ) { get_template_part( 'template-parts/shop-card', null, array( 'seller_id' => (int) $dm_sid ) ); } ?>
		</div>
		<?php echo dm_pagination( $dm_users->get_total(), 24, $dm_page, remove_query_arg( 'pg' ) ); // phpcs:ignore ?>
	<?php else : ?>
		<?php dm_empty_state( __( 'No shops found', 'digimarket' ), '', dm_url( 'sell' ), __( 'Open the first one', 'digimarket' ) ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
