<?php
/**
 * Seller reviews: reply & flag.
 *
 * @package DigiMarket
 */

global $wpdb;
$dm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . ' WHERE seller_id = %d ORDER BY id DESC LIMIT 100', $dm_uid ) );
if ( ! $dm_rows ) {
	dm_empty_state( __( 'No reviews yet', 'digimarket' ), __( 'Reviews from verified buyers will appear here.', 'digimarket' ) );
	return;
}
?>
<ul class="dm-review-list">
	<?php foreach ( $dm_rows as $dm_r ) : ?>
		<?php $dm_u = get_userdata( $dm_r->buyer_id ); ?>
		<li class="dm-card dm-pad dm-review">
			<div class="dm-review-head"><?php echo get_avatar( $dm_r->buyer_id, 36 ); ?><div><strong><?php echo esc_html( $dm_u ? $dm_u->display_name : '' ); ?></strong> <?php esc_html_e( 'on', 'digimarket' ); ?> <a href="<?php echo esc_url( get_permalink( $dm_r->product_id ) ); ?>"><?php echo esc_html( get_the_title( $dm_r->product_id ) ); ?></a><br><?php echo dm_stars( $dm_r->rating ); // phpcs:ignore ?> <small class="dm-muted"><?php echo esc_html( mysql2date( 'M j, Y', $dm_r->created_at ) ); ?></small> <?php echo 'approved' !== $dm_r->status ? dm_status_badge( $dm_r->status ) : ''; // phpcs:ignore ?> <?php echo $dm_r->flagged ? '<span class="dm-badge dm-badge-warning">' . esc_html__( 'Flagged', 'digimarket' ) . '</span>' : ''; ?></div></div>
			<?php if ( $dm_r->comment ) : ?><p><?php echo nl2br( esc_html( $dm_r->comment ) ); ?></p><?php endif; ?>
			<form method="post" class="dm-form">
				<?php dm_nonce_field( 'seller_review_reply' ); ?>
				<input type="hidden" name="review_id" value="<?php echo (int) $dm_r->id; ?>">
				<label><?php esc_html_e( 'Public reply', 'digimarket' ); ?><textarea name="reply" rows="2"><?php echo esc_textarea( (string) $dm_r->seller_reply ); ?></textarea></label>
				<div class="dm-form-actions">
					<button class="dm-btn dm-btn-primary dm-btn-sm"><?php echo $dm_r->seller_reply ? esc_html__( 'Update reply', 'digimarket' ) : esc_html__( 'Reply', 'digimarket' ); ?></button>
					<?php if ( ! $dm_r->flagged ) : ?>
						<input type="text" name="flag_reason" placeholder="<?php esc_attr_e( 'Reason to flag (optional)', 'digimarket' ); ?>" aria-label="<?php esc_attr_e( 'Flag reason', 'digimarket' ); ?>">
						<button class="dm-btn dm-btn-ghost dm-btn-sm" name="flag" value="1"><?php esc_html_e( 'Flag as inappropriate', 'digimarket' ); ?></button>
					<?php endif; ?>
				</div>
			</form>
		</li>
	<?php endforeach; ?>
</ul>
