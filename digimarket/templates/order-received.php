<?php
/**
 * Order confirmation with direct download buttons.
 *
 * @package DigiMarket
 */

$dm_order = dm_get_order( absint( dm_tab() ) );
if ( ! $dm_order || (int) $dm_order->buyer_id !== get_current_user_id() ) {
	dm_redirect( dm_url( 'account', 'orders' ) );
}
$dm_items = dm_get_order_items( $dm_order->id );
get_header();
?>
<div class="dm-container dm-page dm-narrow">
	<ol class="dm-steps dm-steps-inline"><li class="is-done"><?php esc_html_e( 'Cart', 'digimarket' ); ?></li><li class="is-done"><?php esc_html_e( 'Payment', 'digimarket' ); ?></li><li class="is-active"><?php esc_html_e( 'Download', 'digimarket' ); ?></li></ol>
	<?php if ( in_array( $dm_order->payment_status, array( 'paid', 'partially_refunded' ), true ) ) : ?>
		<div class="dm-success-hero">
			<div class="dm-check-circle" aria-hidden="true">✓</div>
			<h1><?php esc_html_e( 'Payment successful!', 'digimarket' ); ?></h1>
			<p class="dm-muted"><?php echo esc_html( sprintf( /* translators: 1 order 2 email */ __( 'Order %1$s is confirmed. A receipt was sent to %2$s.', 'digimarket' ), dm_order_number( $dm_order ), $dm_order->buyer_email ) ); ?></p>
		</div>
		<div class="dm-library">
			<?php foreach ( $dm_items as $dm_it ) : ?>
				<?php get_template_part( 'template-parts/library-item', null, array( 'item' => $dm_it ) ); ?>
			<?php endforeach; ?>
		</div>
		<p class="dm-center">
			<a class="dm-btn dm-btn-ghost" href="<?php echo esc_url( dm_url( 'invoice', $dm_order->id ) ); ?>" target="_blank"><?php esc_html_e( 'View invoice', 'digimarket' ); ?></a>
			<a class="dm-btn dm-btn-ghost" href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php esc_html_e( 'All my purchases', 'digimarket' ); ?></a>
			<a class="dm-btn dm-btn-ghost" href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'Keep shopping', 'digimarket' ); ?></a>
		</p>
	<?php else : ?>
		<?php dm_empty_state( __( 'Payment not completed', 'digimarket' ), __( 'We did not receive payment for this order. No money was taken.', 'digimarket' ), dm_url( 'checkout', 'pay', $dm_order->id ), __( 'Retry payment', 'digimarket' ) ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
