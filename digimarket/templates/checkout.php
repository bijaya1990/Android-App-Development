<?php
/**
 * Checkout: review + pay. /checkout and /checkout/pay/{order}
 *
 * @package DigiMarket
 */

$dm_uid = get_current_user_id();

/* ---------- Payment step ---------- */
if ( 'pay' === dm_tab() ) {
	$dm_order = dm_get_order( absint( dm_route_id() ) );
	if ( ! $dm_order || (int) $dm_order->buyer_id !== $dm_uid ) {
		dm_redirect( dm_url( 'account', 'orders' ) );
	}
	if ( 'paid' === $dm_order->payment_status ) {
		dm_redirect( dm_url( 'order-received', $dm_order->id ) );
	}
	$dm_items = dm_get_order_items( $dm_order->id );
	if ( 'razorpay' === $dm_order->gateway ) {
		wp_enqueue_script( 'razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, true ); // phpcs:ignore
	}
	get_header();
	?>
	<div class="dm-container dm-page dm-narrow">
		<ol class="dm-steps dm-steps-inline"><li class="is-done"><?php esc_html_e( 'Cart', 'digimarket' ); ?></li><li class="is-active"><?php esc_html_e( 'Payment', 'digimarket' ); ?></li><li><?php esc_html_e( 'Download', 'digimarket' ); ?></li></ol>
		<div class="dm-card dm-pad dm-pay">
			<h1><?php echo esc_html( sprintf( /* translators: %s amount */ __( 'Pay %s', 'digimarket' ), dm_money( $dm_order->order_total ) ) ); ?></h1>
			<p class="dm-muted"><?php echo esc_html( dm_order_number( $dm_order ) ); ?> · <?php echo esc_html( sprintf( /* translators: %d */ _n( '%d item', '%d items', count( $dm_items ), 'digimarket' ), count( $dm_items ) ) ); ?></p>
			<ul class="dm-pay-items">
				<?php foreach ( $dm_items as $dm_it ) : ?><li><span><?php echo esc_html( $dm_it->product_title ); ?> <small class="dm-muted">— <?php echo esc_html( dm_shop_name( $dm_it->seller_id ) ); ?></small></span><strong><?php echo esc_html( dm_money( $dm_it->price_at_purchase ) ); ?></strong></li><?php endforeach; ?>
			</ul>
			<?php if ( 'failed' === $dm_order->payment_status ) : ?>
				<div class="dm-notice dm-notice-error"><?php esc_html_e( 'The last payment attempt failed. No money was taken — you can safely retry.', 'digimarket' ); ?></div>
				<form method="post"><?php dm_nonce_field( 'retry_payment' ); ?><input type="hidden" name="order_id" value="<?php echo (int) $dm_order->id; ?>"><button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block"><?php esc_html_e( 'Retry payment', 'digimarket' ); ?></button></form>
			<?php elseif ( 'razorpay' === $dm_order->gateway ) : ?>
				<?php
				$dm_user = wp_get_current_user();
				$dm_cfg  = array(
					'key'         => dm_opt( 'rzp_key_id' ),
					'amount'      => dm_to_paise( $dm_order->order_total ),
					'currency'    => dm_opt( 'currency_code', 'INR' ),
					'name'        => get_bloginfo( 'name' ),
					'description' => dm_order_number( $dm_order ),
					'order_id'    => $dm_order->razorpay_order_id,
					'prefill'     => array( 'name' => $dm_user->display_name, 'email' => $dm_user->user_email, 'contact' => (string) get_user_meta( $dm_uid, 'dm_phone', true ) ),
					'theme'       => array( 'color' => get_theme_mod( 'dm_primary_color', '#5b4bff' ) ),
					'dmOrder'     => (int) $dm_order->id,
				);
				?>
				<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" id="dm-rzp-pay" data-config="<?php echo esc_attr( wp_json_encode( $dm_cfg ) ); ?>"><?php esc_html_e( 'Pay securely with Razorpay', 'digimarket' ); ?></button>
				<div class="dm-pay-status" id="dm-pay-status" role="status" aria-live="polite"></div>
			<?php else : ?>
				<div class="dm-notice dm-notice-info"><?php esc_html_e( 'Demo payment mode: no real money is charged. The site admin must connect Razorpay before launch.', 'digimarket' ); ?></div>
				<form method="post"><?php dm_nonce_field( 'demo_pay' ); ?><input type="hidden" name="order_id" value="<?php echo (int) $dm_order->id; ?>">
					<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block"><?php esc_html_e( 'Complete demo payment', 'digimarket' ); ?></button>
					<button class="dm-btn dm-btn-ghost dm-btn-block" name="simulate_fail" value="1"><?php esc_html_e( 'Simulate a failed payment', 'digimarket' ); ?></button>
				</form>
			<?php endif; ?>
			<p class="dm-secure">🔒 <?php esc_html_e( 'Payments are processed by Razorpay. We never see or store your card details.', 'digimarket' ); ?></p>
		</div>
	</div>
	<?php
	get_footer();
	return;
}

/* ---------- Review step ---------- */
$dm_t = dm_cart_totals();
if ( ! $dm_t['lines'] ) {
	dm_redirect( dm_url( 'cart' ) );
}
get_header();
$dm_user = wp_get_current_user();
?>
<div class="dm-container dm-page">
	<ol class="dm-steps dm-steps-inline"><li class="is-done"><?php esc_html_e( 'Cart', 'digimarket' ); ?></li><li class="is-active"><?php esc_html_e( 'Payment', 'digimarket' ); ?></li><li><?php esc_html_e( 'Download', 'digimarket' ); ?></li></ol>
	<h1><?php esc_html_e( 'Checkout', 'digimarket' ); ?></h1>
	<?php if ( ! dm_email_verified( $dm_uid ) ) : ?>
		<div class="dm-notice dm-notice-warning">
			<?php esc_html_e( 'Please verify your email before your first purchase.', 'digimarket' ); ?>
			<form method="post" style="display:inline"><?php dm_nonce_field( 'resend_verification' ); ?><button class="dm-link"><?php esc_html_e( 'Resend link', 'digimarket' ); ?></button></form>
		</div>
	<?php endif; ?>
	<div class="dm-cart">
		<div class="dm-card dm-pad">
			<h2 class="dm-h3"><?php esc_html_e( 'Buyer', 'digimarket' ); ?></h2>
			<p><?php echo esc_html( $dm_user->display_name ); ?> · <?php echo esc_html( $dm_user->user_email ); ?> <a class="dm-small" href="<?php echo esc_url( dm_url( 'account', 'profile' ) ); ?>"><?php esc_html_e( 'Edit', 'digimarket' ); ?></a></p>
			<h2 class="dm-h3"><?php esc_html_e( 'Items', 'digimarket' ); ?></h2>
			<ul class="dm-pay-items">
				<?php foreach ( $dm_t['lines'] as $dm_l ) : ?>
					<li><span><?php echo esc_html( $dm_l['title'] ); ?> <small class="dm-muted">— <?php echo esc_html( dm_shop_name( $dm_l['seller'] ) ); ?></small></span><strong><?php echo esc_html( dm_money( $dm_l['final'] ) ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
			<p class="dm-muted dm-small"><?php esc_html_e( 'Digital products are delivered instantly after payment. You will find them in My Purchases and in your confirmation email.', 'digimarket' ); ?></p>
		</div>
		<aside class="dm-card dm-summary">
			<h2><?php esc_html_e( 'Total', 'digimarket' ); ?></h2>
			<dl>
				<dt><?php esc_html_e( 'Subtotal', 'digimarket' ); ?></dt><dd><?php echo esc_html( dm_money( $dm_t['subtotal'] ) ); ?></dd>
				<?php if ( $dm_t['discount'] > 0 ) : ?><dt><?php esc_html_e( 'Discount', 'digimarket' ); ?></dt><dd class="dm-success">−<?php echo esc_html( dm_money( $dm_t['discount'] ) ); ?></dd><?php endif; ?>
				<dt class="dm-total"><?php esc_html_e( 'To pay', 'digimarket' ); ?></dt><dd class="dm-total"><?php echo esc_html( dm_money( $dm_t['total'] ) ); ?></dd>
			</dl>
			<form method="post">
				<?php dm_nonce_field( 'place_order' ); ?>
				<label class="dm-check"><input type="checkbox" name="agree" value="1" required> <span><?php echo wp_kses_post( sprintf( /* translators: 1 terms 2 refund */ __( 'I agree to the <a href="%1$s" target="_blank">Terms</a> and <a href="%2$s" target="_blank">Refund Policy</a>.', 'digimarket' ), esc_url( dm_legal_url( 'terms' ) ), esc_url( dm_legal_url( 'refund' ) ) ) ); ?></span></label>
				<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" type="submit"><?php echo $dm_t['total'] > 0 ? esc_html__( 'Continue to payment', 'digimarket' ) : esc_html__( 'Get it free', 'digimarket' ); ?></button>
			</form>
			<a class="dm-btn dm-btn-ghost dm-btn-block" href="<?php echo esc_url( dm_url( 'cart' ) ); ?>"><?php esc_html_e( '← Back to cart', 'digimarket' ); ?></a>
			<div class="dm-pay-badges" aria-label="<?php esc_attr_e( 'Accepted payment methods', 'digimarket' ); ?>"><span>UPI</span><span>Visa</span><span>Mastercard</span><span>RuPay</span><span>Netbanking</span><span>Wallets</span></div>
		</aside>
	</div>
</div>
<?php
get_footer();
