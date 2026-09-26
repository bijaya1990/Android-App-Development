<?php
/**
 * Support tickets relating to this seller's orders.
 *
 * @package DigiMarket
 */

global $wpdb;
$dm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'tickets' ) . " WHERE seller_id = %d ORDER BY status = 'open' DESC, id DESC LIMIT 100", $dm_uid ) );
if ( ! $dm_rows ) {
	dm_empty_state( __( 'No support requests', 'digimarket' ), __( 'When a buyer needs help with one of your orders, it shows up here.', 'digimarket' ) );
	return;
}
foreach ( $dm_rows as $dm_t ) :
	$dm_u = get_userdata( $dm_t->user_id );
	?>
	<div class="dm-card dm-pad dm-ticket">
		<div class="dm-card-head"><h3 class="dm-h3"><?php echo esc_html( $dm_t->subject ); ?> <?php echo dm_status_badge( $dm_t->status ); // phpcs:ignore ?></h3><small class="dm-muted"><?php echo esc_html( ( $dm_u ? $dm_u->display_name : '' ) . ' · ' . dm_order_number( $dm_t->order_id ) . ' · ' . mysql2date( 'M j, Y', $dm_t->created_at ) ); ?></small></div>
		<p><?php echo nl2br( esc_html( $dm_t->message ) ); ?></p>
		<form method="post" class="dm-form">
			<?php dm_nonce_field( 'ticket_reply' ); ?>
			<input type="hidden" name="ticket_id" value="<?php echo (int) $dm_t->id; ?>">
			<label><?php esc_html_e( 'Your reply', 'digimarket' ); ?><textarea name="reply" rows="2"><?php echo esc_textarea( (string) $dm_t->reply ); ?></textarea></label>
			<div class="dm-form-actions">
				<select name="status" aria-label="<?php esc_attr_e( 'Status', 'digimarket' ); ?>"><option value="open" <?php selected( $dm_t->status, 'open' ); ?>><?php esc_html_e( 'Open', 'digimarket' ); ?></option><option value="closed" <?php selected( $dm_t->status, 'closed' ); ?>><?php esc_html_e( 'Resolved', 'digimarket' ); ?></option></select>
				<button class="dm-btn dm-btn-primary dm-btn-sm"><?php esc_html_e( 'Send', 'digimarket' ); ?></button>
				<?php
				$dm_items = $dm_t->order_id ? dm_get_order_items( $dm_t->order_id ) : array();
				foreach ( $dm_items as $dm_i ) {
					if ( (int) $dm_i->seller_id === $dm_uid ) {
						echo '<a class="dm-btn dm-btn-ghost dm-btn-sm" href="' . esc_url( add_query_arg( 'item', $dm_i->id, dm_url( 'dashboard', 'orders' ) ) ) . '">' . esc_html__( 'Open order', 'digimarket' ) . '</a>';
						break;
					}
				}
				?>
			</div>
		</form>
	</div>
<?php endforeach; ?>
