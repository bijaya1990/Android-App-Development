<?php
/**
 * Seller wallet: balance the store owes, deductions, and week / month / year history.
 *
 * @package DigiMarket
 */

$dm_wuid  = get_current_user_id();
$dm_group = isset( $_GET['wv'] ) ? sanitize_key( $_GET['wv'] ) : 'week';
$dm_group = in_array( $dm_group, array( 'week', 'month', 'year' ), true ) ? $dm_group : 'week';
$dm_w     = dm_seller_wallet( $dm_wuid, $dm_group, 'year' === $dm_group ? 3 : 12 );
$dm_l     = $dm_w['life'];
$dm_base  = remove_query_arg( 'wv', dm_current_url() );
?>
<section class="dm-wallet" id="wallet">
	<div class="dm-wallet-main">
		<div>
			<span class="dm-wallet-label"><?php esc_html_e( 'Wallet balance', 'digimarket' ); ?></span>
			<strong class="dm-wallet-amount"><?php echo esc_html( dm_money( $dm_w['balance'] ) ); ?></strong>
			<span class="dm-wallet-sub"><?php echo $dm_w['balance'] > 0 ? esc_html__( 'Your earnings waiting to be paid to your bank / UPI', 'digimarket' ) : esc_html__( 'All settled — nothing pending right now', 'digimarket' ); ?></span>
		</div>
		<div class="dm-wallet-last">
			<?php if ( $dm_w['last'] ) : ?>
				<span><?php esc_html_e( 'Last payment received', 'digimarket' ); ?></span>
				<strong><?php echo esc_html( dm_money( $dm_w['last']['amount'] ) ); ?></strong>
				<small><?php echo esc_html( mysql2date( 'j M Y', $dm_w['last']['date'] ) . ' · UTR ' . $dm_w['last']['ref'] ); ?></small>
			<?php else : ?>
				<span><?php esc_html_e( 'Last payment received', 'digimarket' ); ?></span><strong>—</strong>
			<?php endif; ?>
		</div>
	</div>
	<div class="dm-wallet-grid">
		<div><span><?php esc_html_e( 'Total sales', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_l['sales'] ) ); ?></strong><small><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d order', '%d orders', count( $dm_l['orders'] ), 'digimarket' ), count( $dm_l['orders'] ) ) ); ?></small></div>
		<div><span><?php esc_html_e( 'Platform commission', 'digimarket' ); ?></span><strong>−<?php echo esc_html( dm_money( $dm_l['commission'] ) ); ?></strong></div>
		<?php if ( $dm_l['gst'] > 0 ) : ?>
			<div><span><?php esc_html_e( 'GST on commission', 'digimarket' ); ?></span><strong>−<?php echo esc_html( dm_money( $dm_l['gst'] ) ); ?></strong></div>
		<?php endif; ?>
		<div><span><?php esc_html_e( 'Refunds', 'digimarket' ); ?></span><strong><?php echo $dm_l['refunded'] > 0 ? '−' : ''; ?><?php echo esc_html( dm_money( $dm_l['refunded'] ) ); ?></strong></div>
		<?php if ( $dm_l['tds'] > 0 || $dm_w['tds_due'] > 0 ) : ?>
			<div><span><?php esc_html_e( 'TDS (194-O)', 'digimarket' ); ?></span><strong>−<?php echo esc_html( dm_money( $dm_l['tds'] + $dm_w['tds_due'] ) ); ?></strong></div>
		<?php endif; ?>
		<div><span><?php esc_html_e( 'You earned', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_l['net'] ) ); ?></strong></div>
		<div class="is-good"><span><?php esc_html_e( 'Paid to you', 'digimarket' ); ?></span><strong><?php echo esc_html( dm_money( $dm_l['paid'] ) ); ?></strong></div>
	</div>
	<?php if ( $dm_w['recover'] > 0 ) : ?>
		<p class="dm-small dm-muted"><?php echo esc_html( sprintf( /* translators: %s */ __( '%s will be adjusted from your next payout for orders refunded after you were paid.', 'digimarket' ), dm_money( $dm_w['recover'] ) ) ); ?></p>
	<?php endif; ?>
</section>

<div class="dm-card dm-table-wrap dm-wallet-history">
	<div class="dm-card-head dm-pad">
		<h2 class="dm-h3"><?php esc_html_e( 'Earnings history', 'digimarket' ); ?></h2>
		<div class="dm-range-form">
			<?php foreach ( array( 'week' => __( 'Week-wise', 'digimarket' ), 'month' => __( 'Month-wise', 'digimarket' ), 'year' => __( 'Year-wise', 'digimarket' ) ) as $dm_k => $dm_lbl ) : ?>
				<a class="dm-chip-link<?php echo $dm_group === $dm_k ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'wv', $dm_k, $dm_base ) . '#wallet' ); ?>"><?php echo esc_html( $dm_lbl ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<table class="dm-table">
		<thead><tr><th><?php echo esc_html( 'week' === $dm_group ? __( 'Week (Mon–Sun)', 'digimarket' ) : ( 'month' === $dm_group ? __( 'Month', 'digimarket' ) : __( 'Year', 'digimarket' ) ) ); ?></th><th><?php esc_html_e( 'Orders', 'digimarket' ); ?></th><th><?php esc_html_e( 'Sales', 'digimarket' ); ?></th><th><?php esc_html_e( 'Commission + GST', 'digimarket' ); ?></th><th><?php esc_html_e( 'Refunds', 'digimarket' ); ?></th><th><?php esc_html_e( 'You earned', 'digimarket' ); ?></th><th><?php esc_html_e( 'Paid', 'digimarket' ); ?></th><th><?php esc_html_e( 'Pending', 'digimarket' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $dm_w['periods'] as $dm_key => $dm_p ) : ?>
			<tr class="<?php echo $dm_p['items'] ? '' : 'dm-muted'; ?>">
				<td><?php echo esc_html( dm_wallet_period_label( $dm_key, $dm_group ) ); ?></td>
				<td><?php echo (int) count( $dm_p['orders'] ); ?></td>
				<td><?php echo esc_html( dm_money( $dm_p['sales'] ) ); ?></td>
				<td><?php echo $dm_p['commission'] + $dm_p['gst'] > 0 ? '−' . esc_html( dm_money( $dm_p['commission'] + $dm_p['gst'] ) ) : '—'; ?></td>
				<td><?php echo $dm_p['refunded'] > 0 ? '−' . esc_html( dm_money( $dm_p['refunded'] ) ) : '—'; ?></td>
				<td><strong><?php echo esc_html( dm_money( $dm_p['net'] ) ); ?></strong></td>
				<td><?php echo esc_html( dm_money( $dm_p['paid'] ) ); ?><?php echo $dm_p['tds'] > 0 ? '<br><small class="dm-muted">' . esc_html( sprintf( /* translators: %s */ __( '%s TDS', 'digimarket' ), dm_money( $dm_p['tds'] ) ) ) . '</small>' : ''; ?></td>
				<td><?php echo $dm_p['pending'] > 0 ? '<span class="dm-badge dm-badge-warning">' . esc_html( dm_money( $dm_p['pending'] ) ) . '</span>' : ( $dm_p['items'] ? '<span class="dm-badge dm-badge-success">' . esc_html__( 'Settled', 'digimarket' ) . '</span>' : '—' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php if ( $dm_w['payments'] ) : ?>
<div class="dm-card dm-table-wrap">
	<h2 class="dm-h3 dm-pad"><?php esc_html_e( 'Payments received', 'digimarket' ); ?></h2>
	<table class="dm-table">
		<thead><tr><th><?php esc_html_e( 'Date', 'digimarket' ); ?></th><th><?php esc_html_e( 'Reference (UTR)', 'digimarket' ); ?></th><th><?php esc_html_e( 'Items', 'digimarket' ); ?></th><th><?php esc_html_e( 'Amount', 'digimarket' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( array_slice( $dm_w['payments'], 0, 24 ) as $dm_pay ) : ?>
			<tr><td><?php echo esc_html( mysql2date( 'j M Y', $dm_pay['date'] ) ); ?></td><td><code><?php echo esc_html( $dm_pay['ref'] ); ?></code></td><td><?php echo (int) $dm_pay['items']; ?></td><td><strong><?php echo esc_html( dm_money( $dm_pay['amount'] ) ); ?></strong><?php echo $dm_pay['tds'] > 0 ? ' <small class="dm-muted">(' . esc_html( sprintf( /* translators: %s */ __( 'after %s TDS', 'digimarket' ), dm_money( $dm_pay['tds'] ) ) ) . ')</small>' : ''; ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>
