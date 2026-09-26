<?php
/**
 * Seller orders with filters, CSV export and refunds.
 *
 * @package DigiMarket
 */

$dm_pg   = dm_get_page_num();
$dm_res  = dm_seller_orders_query( $dm_uid, 20, ( $dm_pg - 1 ) * 20 );
$dm_prod = get_posts( array( 'post_type' => 'dm_product', 'author' => $dm_uid, 'post_status' => array( 'publish', 'draft', 'dm_unpublished', 'dm_deleted' ), 'numberposts' => 200 ) );
$dm_view = isset( $_GET['item'] ) ? dm_get_order_item( absint( $_GET['item'] ) ) : null;
if ( $dm_view && (int) $dm_view->seller_id !== $dm_uid ) {
	$dm_view = null;
}
?>
<?php if ( $dm_view ) : ?>
	<?php $dm_o = dm_get_order( $dm_view->order_id ); ?>
	<div class="dm-card dm-pad">
		<div class="dm-card-head"><h2 class="dm-h3"><?php echo esc_html( dm_order_number( $dm_view->order_id ) ); ?> <?php echo dm_status_badge( $dm_view->item_status ); // phpcs:ignore ?></h2><a href="<?php echo esc_url( dm_url( 'dashboard', 'orders' ) ); ?>">← <?php esc_html_e( 'All orders', 'digimarket' ); ?></a></div>
		<dl class="dm-dl">
			<dt><?php esc_html_e( 'Buyer', 'digimarket' ); ?></dt><dd><?php echo esc_html( $dm_o->buyer_name . ' · ' . $dm_o->buyer_email ); ?></dd>
			<dt><?php esc_html_e( 'Product', 'digimarket' ); ?></dt><dd><?php echo esc_html( $dm_view->product_title ); ?></dd>
			<dt><?php esc_html_e( 'Date', 'digimarket' ); ?></dt><dd><?php echo esc_html( mysql2date( 'M j, Y H:i', $dm_o->created_at ) ); ?></dd>
			<dt><?php esc_html_e( 'Amount paid', 'digimarket' ); ?></dt><dd><?php echo esc_html( dm_money( $dm_view->price_at_purchase ) ); ?><?php echo $dm_view->list_price > $dm_view->price_at_purchase ? ' <small class="dm-muted">(' . esc_html__( 'coupon applied', 'digimarket' ) . ')</small>' : ''; ?></dd>
			<dt><?php esc_html_e( 'Commission', 'digimarket' ); ?></dt><dd><?php echo esc_html( dm_money( $dm_view->commission_amount ) . ' (' . (float) $dm_view->commission_percent_applied . '%)' ); ?></dd>
			<dt><?php esc_html_e( 'Net credited to you', 'digimarket' ); ?></dt><dd><strong><?php echo esc_html( dm_money( $dm_view->seller_net_amount ) ); ?></strong></dd>
			<dt><?php esc_html_e( 'Transfer', 'digimarket' ); ?></dt><dd><?php echo dm_status_badge( $dm_view->transfer_status ); // phpcs:ignore ?> <code><?php echo esc_html( $dm_view->transfer_id ); ?></code> <?php echo esc_html( $dm_view->transfer_note ); ?></dd>
			<dt><?php esc_html_e( 'Downloads', 'digimarket' ); ?></dt><dd><?php echo (int) $dm_view->download_count; ?></dd>
			<?php if ( $dm_view->license_key ) : ?><dt><?php esc_html_e( 'License key', 'digimarket' ); ?></dt><dd><code><?php echo esc_html( $dm_view->license_key ); ?></code></dd><?php endif; ?>
		</dl>
		<?php if ( 'paid' === $dm_view->item_status && dm_opt( 'sellers_can_refund' ) ) : ?>
			<form method="post" class="dm-refund-form">
				<?php dm_nonce_field( 'seller_refund' ); ?>
				<input type="hidden" name="item_id" value="<?php echo (int) $dm_view->id; ?>">
				<label><?php esc_html_e( 'Refund reason (sent to buyer)', 'digimarket' ); ?><input type="text" name="reason" maxlength="200"></label>
				<button class="dm-btn dm-btn-danger" data-confirm="<?php echo esc_attr( sprintf( /* translators: %s */ __( 'Refund %s? The buyer loses access and the commission is reversed.', 'digimarket' ), dm_money( $dm_view->price_at_purchase ) ) ); ?>"><?php esc_html_e( 'Issue refund', 'digimarket' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
<?php endif; ?>

<form method="get" class="dm-filters-bar">
	<?php if ( ! dm_pretty_permalinks() ) : ?><input type="hidden" name="dm_route" value="dashboard"><input type="hidden" name="dm_tab" value="orders"><?php endif; ?>
	<input type="date" name="from" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'From', 'digimarket' ); ?>">
	<input type="date" name="to" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) ) ); ?>" aria-label="<?php esc_attr_e( 'To', 'digimarket' ); ?>">
	<select name="product" aria-label="<?php esc_attr_e( 'Product', 'digimarket' ); ?>"><option value=""><?php esc_html_e( 'All products', 'digimarket' ); ?></option><?php foreach ( $dm_prod as $dm_p ) : ?><option value="<?php echo (int) $dm_p->ID; ?>" <?php selected( absint( $_GET['product'] ?? 0 ), $dm_p->ID ); ?>><?php echo esc_html( $dm_p->post_title ); ?></option><?php endforeach; ?></select>
	<select name="status" aria-label="<?php esc_attr_e( 'Payment status', 'digimarket' ); ?>"><option value=""><?php esc_html_e( 'Any status', 'digimarket' ); ?></option><?php foreach ( array( 'paid' => __( 'Success', 'digimarket' ), 'failed' => __( 'Failed', 'digimarket' ), 'refunded' => __( 'Refunded', 'digimarket' ) ) as $dm_k => $dm_l ) : ?><option value="<?php echo esc_attr( $dm_k ); ?>" <?php selected( sanitize_key( $_GET['status'] ?? '' ), $dm_k ); ?>><?php echo esc_html( $dm_l ); ?></option><?php endforeach; ?></select>
	<button class="dm-btn dm-btn-ghost"><?php esc_html_e( 'Filter', 'digimarket' ); ?></button>
	<a class="dm-btn dm-btn-ghost" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array_map( 'sanitize_text_field', wp_unslash( $_GET ) ), array( 'export' => 'orders' ) ), dm_url( 'dashboard', 'orders' ) ), 'dm_export' ) ); ?>">⬇ <?php esc_html_e( 'Export CSV', 'digimarket' ); ?></a>
</form>

<?php if ( $dm_res['items'] ) : ?>
	<div class="dm-card dm-table-wrap">
		<table class="dm-table">
			<thead><tr><th><?php esc_html_e( 'Order', 'digimarket' ); ?></th><th><?php esc_html_e( 'Buyer', 'digimarket' ); ?></th><th><?php esc_html_e( 'Product', 'digimarket' ); ?></th><th><?php esc_html_e( 'Date', 'digimarket' ); ?></th><th><?php esc_html_e( 'Paid', 'digimarket' ); ?></th><th><?php esc_html_e( 'Commission', 'digimarket' ); ?></th><th><?php esc_html_e( 'Net', 'digimarket' ); ?></th><th><?php esc_html_e( 'Status', 'digimarket' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $dm_res['items'] as $dm_r ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( add_query_arg( 'item', $dm_r->id, dm_url( 'dashboard', 'orders' ) ) ); ?>"><?php echo esc_html( dm_order_number( $dm_r->order_id ) ); ?></a></td>
					<td><?php echo esc_html( $dm_r->buyer_name ); ?></td>
					<td><?php echo esc_html( $dm_r->product_title ); ?></td>
					<td><?php echo esc_html( mysql2date( 'M j, Y', $dm_r->created_at ) ); ?></td>
					<td><?php echo esc_html( dm_money( $dm_r->price_at_purchase ) ); ?></td>
					<td>−<?php echo esc_html( dm_money( $dm_r->commission_amount ) ); ?></td>
					<td><strong><?php echo esc_html( dm_money( $dm_r->seller_net_amount ) ); ?></strong></td>
					<td><?php echo dm_status_badge( 'paid' === $dm_r->item_status ? 'success' : $dm_r->item_status ); // phpcs:ignore ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php echo dm_pagination( $dm_res['total'], 20, $dm_pg, remove_query_arg( 'pg' ) ); // phpcs:ignore ?>
<?php else : ?>
	<?php dm_empty_state( __( 'No orders found', 'digimarket' ), __( 'Orders appear here the moment a buyer pays.', 'digimarket' ) ); ?>
<?php endif; ?>
