<?php
/**
 * Seller payouts & payout details.
 *
 * @package DigiMarket
 */

global $wpdb;
$dm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT p.*, i.order_id, i.product_title FROM ' . dm_table( 'payouts' ) . ' p LEFT JOIN ' . dm_table( 'order_items' ) . ' i ON i.id = p.order_item_id WHERE p.seller_id = %d ORDER BY p.id DESC LIMIT 100', $dm_uid ) );
$dm_sum  = $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) pending, COALESCE(SUM(CASE WHEN status='settled' THEN amount ELSE 0 END),0) settled, MAX(settled_at) last_at FROM " . dm_table( 'payouts' ) . ' WHERE seller_id = %d', $dm_uid ) );
$dm_kyc  = get_user_meta( $dm_uid, 'dm_kyc_status', true );
$dm_kyc  = $dm_kyc ? $dm_kyc : 'pending';
$dm_act  = get_user_meta( $dm_uid, 'dm_rzp_activation', true );
?>
<div class="dm-stats">
	<div class="dm-stat accent"><span><?php esc_html_e( 'Available / in transit', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_sum->pending ) ); ?></strong></div>
	<div class="dm-stat"><span><?php esc_html_e( 'Total transferred', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_sum->settled ) ); ?></strong></div>
	<div class="dm-stat"><span><?php esc_html_e( 'Last payout', 'digimarket' ); ?></span><strong><?php echo $dm_sum->last_at ? esc_html( mysql2date( 'M j, Y', $dm_sum->last_at ) ) : '—'; ?></strong></div>
	<div class="dm-stat"><span><?php esc_html_e( 'Payout status', 'digimarket' ); ?></span><strong><?php echo 'verified' === $dm_kyc ? '✅ ' . esc_html__( 'Verified & active', 'digimarket' ) : ( 'rejected' === $dm_kyc ? '⛔ ' . esc_html__( 'Rejected', 'digimarket' ) : '⏳ ' . esc_html__( 'Pending KYC / under review', 'digimarket' ) ); ?></strong><?php if ( $dm_act ) : ?><small><?php echo esc_html( 'Razorpay: ' . $dm_act ); ?></small><?php endif; ?></div>
</div>
<?php if ( get_user_meta( $dm_uid, 'dm_kyc_error', true ) ) : ?>
	<div class="dm-notice dm-notice-warning"><?php echo esc_html( sprintf( /* translators: %s */ __( 'Verification note: %s', 'digimarket' ), get_user_meta( $dm_uid, 'dm_kyc_error', true ) ) ); ?></div>
<?php endif; ?>
<p class="dm-muted dm-small"><?php esc_html_e( 'Your share of every sale is transferred to your Razorpay linked account automatically at the moment of payment, then settled to your bank on Razorpay’s settlement cycle (usually T+2 working days).', 'digimarket' ); ?></p>

<div class="dm-card dm-table-wrap">
	<table class="dm-table">
		<thead><tr><th><?php esc_html_e( 'Date', 'digimarket' ); ?></th><th><?php esc_html_e( 'Order', 'digimarket' ); ?></th><th><?php esc_html_e( 'Amount', 'digimarket' ); ?></th><th><?php esc_html_e( 'Status', 'digimarket' ); ?></th><th><?php esc_html_e( 'Reference', 'digimarket' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $dm_rows as $dm_r ) : ?>
			<tr><td><?php echo esc_html( mysql2date( 'M j, Y', $dm_r->settled_at ? $dm_r->settled_at : $dm_r->created_at ) ); ?></td><td><?php echo esc_html( dm_order_number( $dm_r->order_id ) ); ?><br><small class="dm-muted"><?php echo esc_html( $dm_r->product_title ); ?></small></td><td><?php echo esc_html( dm_money( $dm_r->amount ) ); ?></td><td><?php echo dm_status_badge( $dm_r->status ); // phpcs:ignore ?><?php echo $dm_r->note ? '<br><small class="dm-muted">' . esc_html( $dm_r->note ) . '</small>' : ''; ?></td><td><code><?php echo esc_html( $dm_r->reference ); ?></code></td></tr>
		<?php endforeach; ?>
		<?php if ( ! $dm_rows ) : ?><tr><td colspan="5" class="dm-muted"><?php esc_html_e( 'No payouts yet.', 'digimarket' ); ?></td></tr><?php endif; ?>
		</tbody>
	</table>
</div>

<div class="dm-card dm-pad">
	<h2 class="dm-h3"><?php esc_html_e( 'Payout account', 'digimarket' ); ?></h2>
	<?php get_template_part( 'template-parts/kyc-form' ); ?>
</div>
