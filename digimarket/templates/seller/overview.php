<?php
/**
 * Seller overview.
 *
 * @package DigiMarket
 */

$dm_range = isset( $_GET['range'] ) ? sanitize_key( $_GET['range'] ) : '30';
$dm_days  = in_array( $dm_range, array( '7', '30', '90' ), true ) ? (int) $dm_range : 30;
$dm_s     = dm_seller_stats( $dm_uid, $dm_days );
list( $dm_labels, $dm_values ) = $dm_s['series'];
$dm_recent = dm_seller_orders_query( $dm_uid, 8, 0 );
?>
<div class="dm-stats">
	<div class="dm-stat"><span><?php esc_html_e( 'Total revenue', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_s['revenue'] ) ); ?></strong></div>
	<div class="dm-stat accent"><span><?php esc_html_e( 'Net earnings', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_s['net'] ) ); ?></strong><small><?php esc_html_e( 'after commission', 'digimarket' ); ?></small></div>
	<div class="dm-stat"><span><?php esc_html_e( 'Orders', 'digimarket' ); ?></span><strong><?php echo esc_html( number_format_i18n( $dm_s['orders'] ) ); ?></strong></div>
	<div class="dm-stat"><span><?php esc_html_e( 'Products', 'digimarket' ); ?></span><strong><?php echo esc_html( number_format_i18n( $dm_s['products'] ) ); ?></strong></div>
	<div class="dm-stat"><span><?php esc_html_e( 'Pending payout', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_s['pending'] ) ); ?></strong></div>
</div>
<div class="dm-card dm-pad">
	<div class="dm-card-head">
		<h2 class="dm-h3"><?php esc_html_e( 'Sales trend', 'digimarket' ); ?></h2>
		<form method="get" class="dm-range-form">
			<?php foreach ( array( '7' => __( '7 days', 'digimarket' ), '30' => __( '30 days', 'digimarket' ), '90' => __( '90 days', 'digimarket' ) ) as $dm_k => $dm_l ) : ?>
				<a class="dm-chip-link<?php echo (string) $dm_days === $dm_k && 'custom' !== $dm_range ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'range', $dm_k, dm_url( 'dashboard' ) ) ); ?>"><?php echo esc_html( $dm_l ); ?></a>
			<?php endforeach; ?>
			<input type="hidden" name="range" value="custom">
			<input type="date" name="from" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'From', 'digimarket' ); ?>">
			<input type="date" name="to" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'To', 'digimarket' ); ?>">
			<button class="dm-btn dm-btn-ghost dm-btn-sm"><?php esc_html_e( 'Apply', 'digimarket' ); ?></button>
		</form>
	</div>
	<?php echo dm_svg_chart( $dm_labels, $dm_values ); // phpcs:ignore ?>
</div>
<div class="dm-two">
	<div class="dm-card dm-pad">
		<h2 class="dm-h3"><?php esc_html_e( 'Top 5 products', 'digimarket' ); ?></h2>
		<?php if ( $dm_s['top'] ) : ?>
			<ol class="dm-rank">
				<?php foreach ( $dm_s['top'] as $dm_t ) : ?><li><span><?php echo esc_html( $dm_t->product_title ); ?></span><span class="dm-muted"><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d sale', '%d sales', (int) $dm_t->c, 'digimarket' ), (int) $dm_t->c ) ); ?> · <?php echo esc_html( dm_money( $dm_t->r ) ); ?></span></li><?php endforeach; ?>
			</ol>
		<?php else : ?>
			<?php dm_empty_state( __( 'No sales yet', 'digimarket' ), __( 'Share your shop link to get your first sale.', 'digimarket' ) ); ?>
		<?php endif; ?>
	</div>
	<div class="dm-card dm-pad">
		<h2 class="dm-h3"><?php esc_html_e( 'Recent orders', 'digimarket' ); ?></h2>
		<?php if ( $dm_recent['items'] ) : ?>
			<ul class="dm-feed">
				<?php foreach ( $dm_recent['items'] as $dm_r ) : ?>
					<li><span><strong><?php echo esc_html( $dm_r->buyer_name ); ?></strong> · <?php echo esc_html( $dm_r->product_title ); ?><br><small class="dm-muted"><?php echo esc_html( human_time_diff( strtotime( $dm_r->created_at ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'digimarket' ); ?></small></span><span><?php echo esc_html( dm_money( $dm_r->seller_net_amount ) ); ?> <?php echo dm_status_badge( $dm_r->item_status ); // phpcs:ignore ?></span></li>
				<?php endforeach; ?>
			</ul>
			<a class="dm-small" href="<?php echo esc_url( dm_url( 'dashboard', 'orders' ) ); ?>"><?php esc_html_e( 'All orders →', 'digimarket' ); ?></a>
		<?php else : ?>
			<?php dm_empty_state( __( 'No orders yet', 'digimarket' ), '', dm_url( 'dashboard', 'edit' ), __( 'Add your first product', 'digimarket' ) ); ?>
		<?php endif; ?>
	</div>
</div>
