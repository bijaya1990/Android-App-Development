<?php
/**
 * Seller receipts: one downloadable receipt per order (only this seller's items).
 *
 * @package DigiMarket
 */

global $wpdb;
$dm_i    = dm_table( 'order_items' );
$dm_o    = dm_table( 'orders' );
$dm_pg   = dm_get_page_num();
$dm_per  = 25;
$dm_rows = $wpdb->get_results( $wpdb->prepare( "SELECT o.id, o.buyer_name, o.buyer_email, o.paid_at, o.created_at, COUNT(i.id) items, SUM(i.price_at_purchase) amount, SUM(i.seller_net_amount) net, GROUP_CONCAT(i.product_title SEPARATOR ', ') titles FROM $dm_i i INNER JOIN $dm_o o ON o.id = i.order_id WHERE i.seller_id = %d AND o.payment_status IN ('paid','refunded','partially_refunded') GROUP BY o.id ORDER BY o.id DESC LIMIT %d OFFSET %d", $dm_uid, $dm_per, ( $dm_pg - 1 ) * $dm_per ) ); // phpcs:ignore
$dm_all  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT i.order_id) FROM $dm_i i INNER JOIN $dm_o o ON o.id = i.order_id WHERE i.seller_id = %d AND o.payment_status IN ('paid','refunded','partially_refunded')", $dm_uid ) ); // phpcs:ignore
$dm_gin  = dm_seller_gstin( $dm_uid );
?>
<div class="dm-card dm-pad">
	<div class="dm-card-head"><h2 class="dm-h3"><?php esc_html_e( 'Sales receipts', 'digimarket' ); ?></h2><span class="dm-muted"><?php echo esc_html( sprintf( /* translators: 1 FY 2 amount */ __( '%1$s sales: %2$s', 'digimarket' ), dm_fy_label(), dm_inr( dm_fy_turnover( $dm_uid ) ) ) ); ?></span></div>
	<p class="dm-muted">
		<?php if ( dm_gst_enabled() && ( dm_is_store_owner( $dm_uid ) ? dm_valid_gstin( dm_store_gstin() ) : ( $dm_gin && get_user_meta( $dm_uid, 'dm_gst_show', true ) ) ) ) : ?>
			<?php esc_html_e( 'Your receipts are GST tax invoices with your GSTIN and the GST breakdown.', 'digimarket' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Your receipts show “GST not applicable”.', 'digimarket' ); ?>
			<?php if ( ! dm_is_store_owner( $dm_uid ) ) : ?><a href="<?php echo esc_url( dm_url( 'dashboard', 'settings' ) . '#gst' ); ?>"><?php esc_html_e( 'Add your GSTIN to show GST on receipts', 'digimarket' ); ?></a><?php endif; ?>
		<?php endif; ?>
	</p>
	<?php if ( $dm_rows ) : ?>
		<div class="dm-table-wrap"><table class="dm-table">
			<thead><tr><th><?php esc_html_e( 'Receipt', 'digimarket' ); ?></th><th><?php esc_html_e( 'Date', 'digimarket' ); ?></th><th><?php esc_html_e( 'Buyer', 'digimarket' ); ?></th><th><?php esc_html_e( 'Items', 'digimarket' ); ?></th><th><?php esc_html_e( 'Amount', 'digimarket' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $dm_rows as $dm_r ) : ?>
				<tr>
					<td><strong><?php echo esc_html( dm_order_number( $dm_r->id ) ); ?></strong></td>
					<td><?php echo esc_html( mysql2date( 'j M Y', $dm_r->paid_at ? $dm_r->paid_at : $dm_r->created_at ) ); ?></td>
					<td><?php echo esc_html( $dm_r->buyer_name ); ?></td>
					<td><?php echo esc_html( wp_trim_words( $dm_r->titles, 8 ) ); ?></td>
					<td><?php echo esc_html( dm_money( $dm_r->amount ) ); ?></td>
					<td><a class="dm-btn dm-btn-outline dm-btn-sm" href="<?php echo esc_url( add_query_arg( 'as', 'seller', dm_url( 'invoice', $dm_r->id ) ) ); ?>" target="_blank"><?php esc_html_e( 'Download', 'digimarket' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<?php if ( $dm_all > $dm_per ) : ?>
			<nav class="dm-pagination">
				<?php for ( $dm_n = 1; $dm_n <= ceil( $dm_all / $dm_per ); $dm_n++ ) : ?>
					<a class="dm-pagenum<?php echo $dm_n === $dm_pg ? ' current' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'pg', $dm_n, dm_url( 'dashboard', 'receipts' ) ) ); ?>"><?php echo (int) $dm_n; ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<?php dm_empty_state( __( 'No receipts yet', 'digimarket' ), __( 'A receipt is created automatically for every paid order.', 'digimarket' ) ); ?>
	<?php endif; ?>
</div>
