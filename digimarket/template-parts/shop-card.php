<?php
/**
 * Shop card. Expects $args['seller_id'].
 *
 * @package DigiMarket
 */

$dm_sid    = (int) ( $args['seller_id'] ?? 0 );
$dm_banner = dm_shop_banner_url( $dm_sid );
$dm_rating = dm_shop_rating( $dm_sid );
?>
<a class="dm-card dm-shop-card" href="<?php echo esc_url( dm_store_url( $dm_sid ) ); ?>" style="--dm-accent:<?php echo esc_attr( dm_shop_accent( $dm_sid ) ); ?>">
	<span class="dm-shop-card-banner"<?php echo $dm_banner ? ' style="background-image:url(' . esc_url( $dm_banner ) . ')"' : ''; ?>></span>
	<span class="dm-shop-card-body">
		<?php echo dm_shop_logo( $dm_sid, 'dm-avatar dm-avatar-lg' ); // phpcs:ignore ?>
		<strong><?php echo esc_html( dm_shop_name( $dm_sid ) ); ?></strong>
		<span class="dm-muted dm-small"><?php echo esc_html( wp_trim_words( (string) get_user_meta( $dm_sid, 'dm_shop_bio', true ), 12 ) ); ?></span>
		<span class="dm-shop-meta">
			<span><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d product', '%d products', dm_seller_product_count( $dm_sid ), 'digimarket' ), dm_seller_product_count( $dm_sid ) ) ); ?></span>
			<?php if ( $dm_rating['count'] ) : ?><span>★ <?php echo esc_html( number_format_i18n( $dm_rating['avg'], 1 ) ); ?></span><?php endif; ?>
		</span>
	</span>
</a>
