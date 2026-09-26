<?php
/**
 * Cart (items from multiple sellers).
 *
 * @package DigiMarket
 */

$dm_t = dm_cart_totals();
if ( $dm_t['invalid'] ) {
	dm_cart_set( array_keys( $dm_t['lines'] ) );
}
get_header();
?>
<div class="dm-container dm-page">
	<ol class="dm-steps dm-steps-inline"><li class="is-active"><?php esc_html_e( 'Cart', 'digimarket' ); ?></li><li><?php esc_html_e( 'Payment', 'digimarket' ); ?></li><li><?php esc_html_e( 'Download', 'digimarket' ); ?></li></ol>
	<h1><?php esc_html_e( 'Your cart', 'digimarket' ); ?></h1>
	<?php foreach ( $dm_t['invalid'] as $dm_reason ) : ?>
		<div class="dm-notice dm-notice-warning"><?php echo esc_html( sprintf( /* translators: %s reason */ __( 'An item was removed: %s', 'digimarket' ), $dm_reason ) ); ?></div>
	<?php endforeach; ?>

	<?php if ( ! $dm_t['lines'] ) : ?>
		<?php dm_empty_state( __( 'Your cart is empty', 'digimarket' ), __( 'Discover ebooks, templates, courses and more from independent creators.', 'digimarket' ), dm_products_url(), __( 'Browse products', 'digimarket' ) ); ?>
	<?php else : ?>
		<div class="dm-cart">
			<div class="dm-cart-items">
				<?php
				$dm_by_seller = array();
				foreach ( $dm_t['lines'] as $dm_l ) {
					$dm_by_seller[ $dm_l['seller'] ][] = $dm_l;
				}
				foreach ( $dm_by_seller as $dm_sid => $dm_lines ) :
					?>
					<div class="dm-card dm-cart-group">
						<a class="dm-cart-seller" href="<?php echo esc_url( dm_store_url( $dm_sid ) ); ?>"><?php echo dm_shop_logo( $dm_sid, 'dm-avatar dm-avatar-xs' ); // phpcs:ignore ?> <?php echo esc_html( dm_shop_name( $dm_sid ) ); ?></a>
						<?php foreach ( $dm_lines as $dm_l ) : ?>
							<div class="dm-cart-line">
								<a class="dm-cart-thumb" href="<?php echo esc_url( get_permalink( $dm_l['pid'] ) ); ?>"><?php echo dm_product_thumb( $dm_l['pid'], 'thumbnail' ); // phpcs:ignore ?></a>
								<div class="dm-cart-info">
									<a href="<?php echo esc_url( get_permalink( $dm_l['pid'] ) ); ?>"><strong><?php echo esc_html( $dm_l['title'] ); ?></strong></a>
									<small class="dm-muted"><?php echo esc_html( dm_delivery_label( get_post_meta( $dm_l['pid'], '_dm_delivery', true ) ) ); ?></small>
								</div>
								<div class="dm-cart-price">
									<?php echo wp_kses_post( dm_price_html( $dm_l['pid'] ) ); ?>
									<?php if ( $dm_l['discount'] > 0 ) : ?><small class="dm-success">−<?php echo esc_html( dm_money( $dm_l['discount'] ) ); ?></small><?php endif; ?>
								</div>
								<form method="post"><?php dm_nonce_field( 'cart_remove' ); ?><input type="hidden" name="product_id" value="<?php echo (int) $dm_l['pid']; ?>"><button class="dm-icon-btn" aria-label="<?php esc_attr_e( 'Remove', 'digimarket' ); ?>">✕</button></form>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<aside class="dm-card dm-summary">
				<h2><?php esc_html_e( 'Order summary', 'digimarket' ); ?></h2>
				<dl>
					<dt><?php esc_html_e( 'Subtotal', 'digimarket' ); ?></dt><dd><?php echo esc_html( dm_money( $dm_t['subtotal'] ) ); ?></dd>
					<?php if ( $dm_t['discount'] > 0 ) : ?>
						<dt><?php echo esc_html( sprintf( /* translators: %s code */ __( 'Coupon %s', 'digimarket' ), $dm_t['coupon']->code ) ); ?></dt><dd class="dm-success">−<?php echo esc_html( dm_money( $dm_t['discount'] ) ); ?></dd>
					<?php endif; ?>
					<dt class="dm-total"><?php esc_html_e( 'Total', 'digimarket' ); ?></dt><dd class="dm-total"><?php echo esc_html( dm_money( $dm_t['total'] ) ); ?></dd>
				</dl>
				<?php if ( $dm_t['coupon_error'] ) : ?><p class="dm-notice dm-notice-warning"><?php echo esc_html( $dm_t['coupon_error'] ); ?></p><?php endif; ?>
				<?php if ( $dm_t['coupon'] ) : ?>
					<form method="post" class="dm-coupon"><?php dm_nonce_field( 'remove_coupon' ); ?><span><?php echo esc_html( $dm_t['coupon']->code ); ?> ✓</span><button class="dm-link"><?php esc_html_e( 'Remove', 'digimarket' ); ?></button></form>
				<?php else : ?>
					<form method="post" class="dm-coupon"><?php dm_nonce_field( 'apply_coupon' ); ?><input type="text" name="coupon" placeholder="<?php esc_attr_e( 'Coupon code', 'digimarket' ); ?>" aria-label="<?php esc_attr_e( 'Coupon code', 'digimarket' ); ?>"><button class="dm-btn dm-btn-ghost"><?php esc_html_e( 'Apply', 'digimarket' ); ?></button></form>
				<?php endif; ?>
				<a class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" href="<?php echo esc_url( is_user_logged_in() ? dm_url( 'checkout' ) : dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_url( 'checkout' ) ) ) ) ); ?>"><?php esc_html_e( 'Proceed to checkout', 'digimarket' ); ?></a>
				<p class="dm-secure">🔒 <?php esc_html_e( 'Secure payment · Instant delivery', 'digimarket' ); ?></p>
			</aside>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
