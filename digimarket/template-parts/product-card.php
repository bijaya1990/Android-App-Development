<?php
/**
 * Product card. Expects global $post (inside a loop).
 *
 * @package DigiMarket
 */

$dm_pid    = get_the_ID();
$dm_seller = (int) get_post_field( 'post_author', $dm_pid );
$dm_rating = (float) get_post_meta( $dm_pid, '_dm_rating_avg', true );
$dm_rcount = (int) get_post_meta( $dm_pid, '_dm_rating_count', true );
$dm_cats   = get_the_terms( $dm_pid, 'dm_category' );
?>
<article class="dm-card dm-product-card">
	<a class="dm-card-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php echo dm_product_thumb( $dm_pid ); // phpcs:ignore ?>
		<?php if ( null !== dm_product_sale_price( $dm_pid ) ) : ?><span class="dm-ribbon"><?php esc_html_e( 'Sale', 'digimarket' ); ?></span><?php endif; ?>
	</a>
	<?php if ( is_user_logged_in() ) : ?>
		<button class="dm-wish<?php echo dm_in_wishlist( $dm_pid ) ? ' is-on' : ''; ?>" data-product="<?php echo (int) $dm_pid; ?>" aria-label="<?php esc_attr_e( 'Save to wishlist', 'digimarket' ); ?>" aria-pressed="<?php echo dm_in_wishlist( $dm_pid ) ? 'true' : 'false'; ?>">♥</button>
	<?php endif; ?>
	<div class="dm-card-body">
		<?php if ( $dm_cats && ! is_wp_error( $dm_cats ) ) : ?>
			<span class="dm-eyebrow"><?php echo esc_html( $dm_cats[0]->name ); ?></span>
		<?php endif; ?>
		<h3 class="dm-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<a class="dm-card-shop" href="<?php echo esc_url( dm_store_url( $dm_seller ) ); ?>"><?php echo dm_shop_logo( $dm_seller, 'dm-avatar dm-avatar-xs' ); // phpcs:ignore ?> <?php echo esc_html( dm_shop_name( $dm_seller ) ); ?></a>
		<div class="dm-card-foot">
			<?php echo wp_kses_post( dm_price_html( $dm_pid ) ); ?>
			<?php if ( $dm_rcount ) : ?><span class="dm-card-rating">★ <?php echo esc_html( number_format_i18n( $dm_rating, 1 ) ); ?> <small>(<?php echo (int) $dm_rcount; ?>)</small></span><?php endif; ?>
		</div>
	</div>
</article>
