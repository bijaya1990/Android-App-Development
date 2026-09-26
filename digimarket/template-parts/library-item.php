<?php
/**
 * One purchased item in the buyer's library. Expects $args['item'].
 *
 * @package DigiMarket
 */

$dm_it       = $args['item'];
$dm_delivery = get_post_meta( $dm_it->product_id, '_dm_delivery', true );
$dm_access   = dm_item_access( $dm_it, get_current_user_id() );
$dm_post     = get_post( $dm_it->product_id );
?>
<div class="dm-card dm-library-item">
	<div class="dm-library-thumb"><?php echo dm_product_thumb( $dm_it->product_id, 'dm-card' ); // phpcs:ignore ?></div>
	<div class="dm-library-body">
		<strong><?php echo esc_html( $dm_it->product_title ); ?></strong>
		<small class="dm-muted"><?php echo esc_html( dm_shop_name( $dm_it->seller_id ) ); ?> · <?php echo esc_html( mysql2date( get_option( 'date_format' ), $dm_it->created_at ) ); ?></small>
		<?php if ( 'paid' !== $dm_it->item_status ) : ?>
			<?php echo dm_status_badge( $dm_it->item_status ); // phpcs:ignore ?>
		<?php elseif ( is_wp_error( $dm_access ) ) : ?>
			<small class="dm-error"><?php echo esc_html( $dm_access->get_error_message() ); ?></small>
		<?php else : ?>
			<?php if ( $dm_it->license_key ) : ?>
				<div class="dm-license"><code><?php echo esc_html( $dm_it->license_key ); ?></code><button class="dm-link" data-copy="<?php echo esc_attr( $dm_it->license_key ); ?>"><?php esc_html_e( 'Copy', 'digimarket' ); ?></button></div>
			<?php endif; ?>
			<?php if ( 'license_key' !== $dm_delivery || get_post_meta( $dm_it->product_id, '_dm_file', true ) ) : ?>
				<a class="dm-btn dm-btn-primary" href="<?php echo esc_url( dm_download_button_url( $dm_it->id ) ); ?>"><?php echo 'external_link' === $dm_delivery ? esc_html__( 'Access now ↗', 'digimarket' ) : esc_html__( 'Download', 'digimarket' ); ?></a>
			<?php endif; ?>
			<?php if ( $dm_it->access_expires ) : ?><small class="dm-muted"><?php echo esc_html( sprintf( /* translators: %s date */ __( 'Access until %s', 'digimarket' ), mysql2date( get_option( 'date_format' ), $dm_it->access_expires ) ) ); ?></small><?php endif; ?>
			<?php $dm_lim = (int) get_post_meta( $dm_it->product_id, '_dm_download_limit', true ); ?>
			<?php if ( $dm_lim ) : ?><small class="dm-muted"><?php echo esc_html( sprintf( /* translators: 1 used 2 limit */ __( '%1$d of %2$d downloads used', 'digimarket' ), $dm_it->download_count, $dm_lim ) ); ?></small><?php endif; ?>
		<?php endif; ?>
		<?php if ( $dm_post && 'publish' === $dm_post->post_status ) : ?><a class="dm-small" href="<?php echo esc_url( get_permalink( $dm_post ) ); ?>#reviews"><?php esc_html_e( 'Leave a review', 'digimarket' ); ?></a><?php endif; ?>
	</div>
</div>
