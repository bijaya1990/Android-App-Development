<?php
/**
 * Buyer account: /account/{tab}
 *
 * @package DigiMarket
 */

global $wpdb;
$dm_uid  = get_current_user_id();
$dm_user = wp_get_current_user();
$dm_tab  = dm_tab() ? dm_tab() : 'purchases';
$dm_nav  = array(
	'purchases'     => array( '📚', __( 'My purchases', 'digimarket' ) ),
	'orders'        => array( '🧾', __( 'Order history', 'digimarket' ) ),
	'wishlist'      => array( '♥', __( 'Wishlist', 'digimarket' ) ),
	'following'     => array( '🏪', __( 'Following', 'digimarket' ) ),
	'reviews'       => array( '⭐', __( 'My reviews', 'digimarket' ) ),
	'notifications' => array( '🔔', __( 'Notifications', 'digimarket' ) ),
	'support'       => array( '💬', __( 'Support', 'digimarket' ) ),
	'profile'       => array( '👤', __( 'Profile', 'digimarket' ) ),
);
if ( ! isset( $dm_nav[ $dm_tab ] ) ) {
	$dm_tab = 'purchases';
}
get_header();
?>
<div class="dm-dash">
	<aside class="dm-dash-side">
		<div class="dm-dash-shop"><?php echo get_avatar( $dm_uid, 40, '', '', array( 'class' => 'dm-avatar' ) ); ?><div><strong><?php echo esc_html( $dm_user->display_name ); ?></strong><small class="dm-muted"><?php echo esc_html( $dm_user->user_email ); ?></small></div></div>
		<nav class="dm-dash-nav" aria-label="<?php esc_attr_e( 'My account', 'digimarket' ); ?>">
			<?php foreach ( $dm_nav as $dm_k => $dm_n ) : ?>
				<a class="<?php echo $dm_k === $dm_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( dm_url( 'account', $dm_k ) ); ?>"><span aria-hidden="true"><?php echo esc_html( $dm_n[0] ); ?></span> <?php echo esc_html( $dm_n[1] ); ?><?php if ( 'notifications' === $dm_k && dm_unread_notifications( $dm_uid ) ) : ?> <span class="dm-dot dm-dot-inline"><?php echo (int) dm_unread_notifications( $dm_uid ); ?></span><?php endif; ?></a>
			<?php endforeach; ?>
			<?php if ( dm_is_seller() ) : ?>
				<a href="<?php echo esc_url( dm_url( 'dashboard' ) ); ?>"><span aria-hidden="true">🏪</span> <?php esc_html_e( 'Seller dashboard', 'digimarket' ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><span aria-hidden="true">🚀</span> <?php esc_html_e( 'Start selling', 'digimarket' ); ?></a>
			<?php endif; ?>
		</nav>
	</aside>
	<section class="dm-dash-main">
		<div class="dm-dash-top"><h1><?php echo esc_html( $dm_nav[ $dm_tab ][1] ); ?></h1></div>
		<?php if ( ! dm_email_verified( $dm_uid ) ) : ?>
			<div class="dm-notice dm-notice-warning"><?php esc_html_e( 'Please verify your email address to make purchases.', 'digimarket' ); ?> <form method="post" style="display:inline"><?php dm_nonce_field( 'resend_verification' ); ?><button class="dm-link"><?php esc_html_e( 'Resend verification email', 'digimarket' ); ?></button></form></div>
		<?php endif; ?>

		<?php
		switch ( $dm_tab ) :
			case 'purchases':
				$dm_items = $wpdb->get_results( $wpdb->prepare( 'SELECT i.* FROM ' . dm_table( 'order_items' ) . ' i INNER JOIN ' . dm_table( 'orders' ) . " o ON o.id = i.order_id WHERE o.buyer_id = %d AND i.item_status IN ('paid','refunded') ORDER BY i.id DESC", $dm_uid ) );
				if ( $dm_items ) {
					echo '<div class="dm-library">';
					foreach ( $dm_items as $dm_it ) {
						get_template_part( 'template-parts/library-item', null, array( 'item' => $dm_it ) );
					}
					echo '</div>';
				} else {
					dm_empty_state( __( 'Your library is empty', 'digimarket' ), __( 'Products you buy appear here with an instant download button.', 'digimarket' ), dm_products_url(), __( 'Discover products', 'digimarket' ) );
				}
				break;

			case 'orders':
				$dm_orders = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . ' WHERE buyer_id = %d ORDER BY id DESC LIMIT 100', $dm_uid ) );
				if ( $dm_orders ) :
					?>
					<div class="dm-card dm-table-wrap"><table class="dm-table">
						<thead><tr><th><?php esc_html_e( 'Order', 'digimarket' ); ?></th><th><?php esc_html_e( 'Date', 'digimarket' ); ?></th><th><?php esc_html_e( 'Items', 'digimarket' ); ?></th><th><?php esc_html_e( 'Total', 'digimarket' ); ?></th><th><?php esc_html_e( 'Status', 'digimarket' ); ?></th><th></th></tr></thead>
						<tbody>
						<?php foreach ( $dm_orders as $dm_o ) : ?>
							<tr>
								<td><?php echo esc_html( dm_order_number( $dm_o ) ); ?></td>
								<td><?php echo esc_html( mysql2date( 'M j, Y', $dm_o->created_at ) ); ?></td>
								<td><?php echo esc_html( implode( ', ', wp_list_pluck( dm_get_order_items( $dm_o->id ), 'product_title' ) ) ); ?></td>
								<td><?php echo esc_html( dm_money( $dm_o->order_total ) ); ?></td>
								<td><?php echo dm_status_badge( $dm_o->payment_status ); // phpcs:ignore ?></td>
								<td class="dm-row-actions">
									<?php if ( in_array( $dm_o->payment_status, array( 'paid', 'refunded', 'partially_refunded' ), true ) ) : ?>
										<a href="<?php echo esc_url( dm_url( 'invoice', $dm_o->id ) ); ?>" target="_blank"><?php esc_html_e( 'Invoice (PDF)', 'digimarket' ); ?></a>
									<?php elseif ( in_array( $dm_o->payment_status, array( 'pending', 'failed' ), true ) ) : ?>
										<a href="<?php echo esc_url( dm_url( 'checkout', 'pay', $dm_o->id ) ); ?>"><?php esc_html_e( 'Complete payment', 'digimarket' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table></div>
					<?php
				else :
					dm_empty_state( __( 'No orders yet', 'digimarket' ), '', dm_products_url(), __( 'Start shopping', 'digimarket' ) );
				endif;
				break;

			case 'wishlist':
				$dm_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT product_id FROM ' . dm_table( 'wishlists' ) . ' WHERE buyer_id = %d ORDER BY id DESC', $dm_uid ) );
				if ( $dm_ids ) {
					$dm_q = dm_query_products( array( 'post__in' => array_map( 'intval', $dm_ids ), 'posts_per_page' => 100, 'orderby' => 'post__in' ) );
					echo '<div class="dm-grid dm-grid-3">';
					while ( $dm_q->have_posts() ) {
						$dm_q->the_post();
						get_template_part( 'template-parts/product-card' );
					}
					wp_reset_postdata();
					echo '</div>';
				} else {
					dm_empty_state( __( 'Your wishlist is empty', 'digimarket' ), __( 'Tap ♥ on any product to save it for later.', 'digimarket' ), dm_products_url(), __( 'Browse products', 'digimarket' ) );
				}
				break;

			case 'following':
				$dm_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT seller_id FROM ' . dm_table( 'follows' ) . ' WHERE user_id = %d ORDER BY id DESC', $dm_uid ) );
				if ( $dm_ids ) {
					echo '<div class="dm-grid dm-grid-shops">';
					foreach ( $dm_ids as $dm_sid ) {
						get_template_part( 'template-parts/shop-card', null, array( 'seller_id' => (int) $dm_sid ) );
					}
					echo '</div>';
				} else {
					dm_empty_state( __( 'You’re not following any shops', 'digimarket' ), __( 'Follow shops to get notified when they release new products.', 'digimarket' ), dm_url( 'shops' ), __( 'Explore shops', 'digimarket' ) );
				}
				break;

			case 'reviews':
				$dm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . ' WHERE buyer_id = %d ORDER BY id DESC', $dm_uid ) );
				if ( $dm_rows ) :
					echo '<ul class="dm-review-list">';
					foreach ( $dm_rows as $dm_r ) :
						?>
						<li class="dm-card dm-pad dm-review">
							<div class="dm-card-head"><a href="<?php echo esc_url( get_permalink( $dm_r->product_id ) ); ?>#reviews"><strong><?php echo esc_html( get_the_title( $dm_r->product_id ) ); ?></strong></a> <?php echo 'approved' !== $dm_r->status ? dm_status_badge( $dm_r->status ) : ''; // phpcs:ignore ?></div>
							<?php echo dm_stars( $dm_r->rating ); // phpcs:ignore ?>
							<p><?php echo nl2br( esc_html( $dm_r->comment ) ); ?></p>
							<?php if ( $dm_r->seller_reply ) : ?><div class="dm-reply"><strong><?php esc_html_e( 'Seller:', 'digimarket' ); ?></strong> <?php echo esc_html( $dm_r->seller_reply ); ?></div><?php endif; ?>
							<div class="dm-row-actions">
								<a href="<?php echo esc_url( get_permalink( $dm_r->product_id ) ); ?>#reviews"><?php esc_html_e( 'Edit', 'digimarket' ); ?></a>
								<form method="post" style="display:inline"><?php dm_nonce_field( 'delete_review' ); ?><input type="hidden" name="review_id" value="<?php echo (int) $dm_r->id; ?>"><button class="dm-link dm-danger" data-confirm="<?php esc_attr_e( 'Delete this review?', 'digimarket' ); ?>"><?php esc_html_e( 'Delete', 'digimarket' ); ?></button></form>
							</div>
						</li>
						<?php
					endforeach;
					echo '</ul>';
				else :
					dm_empty_state( __( 'No reviews yet', 'digimarket' ), __( 'Review products you bought to help other buyers.', 'digimarket' ), dm_url( 'account', 'purchases' ), __( 'Go to purchases', 'digimarket' ) );
				endif;
				break;

			case 'notifications':
				$dm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'notifications' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT 100', $dm_uid ) );
				if ( $dm_rows ) :
					?>
					<p><a class="dm-small" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'read', 1, dm_url( 'account', 'notifications' ) ), 'dm_read' ) ); ?>"><?php esc_html_e( 'Mark all as read', 'digimarket' ); ?></a></p>
					<ul class="dm-card dm-feed dm-notifications">
						<?php foreach ( $dm_rows as $dm_n ) : ?>
							<li class="<?php echo $dm_n->is_read ? '' : 'is-unread'; ?>"><a href="<?php echo esc_url( dm_url( 'account', 'notification', $dm_n->id ) ); ?>"><?php echo esc_html( $dm_n->message ); ?></a><small class="dm-muted"><?php echo esc_html( human_time_diff( strtotime( $dm_n->created_at ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'digimarket' ); ?></small></li>
						<?php endforeach; ?>
					</ul>
					<?php
				else :
					dm_empty_state( __( 'You’re all caught up', 'digimarket' ), __( 'Order updates and news from shops you follow appear here.', 'digimarket' ) );
				endif;
				break;

			case 'support':
				$dm_orders  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'orders' ) . " WHERE buyer_id = %d AND payment_status <> 'pending' ORDER BY id DESC LIMIT 50", $dm_uid ) );
				$dm_tickets = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'tickets' ) . ' WHERE user_id = %d ORDER BY id DESC', $dm_uid ) );
				$dm_pre     = absint( $_GET['order'] ?? 0 );
				?>
				<div class="dm-card dm-pad">
					<h2 class="dm-h3"><?php esc_html_e( 'Need help with an order?', 'digimarket' ); ?></h2>
					<form method="post" class="dm-form">
						<?php dm_nonce_field( 'open_ticket' ); ?>
						<div class="dm-form-grid">
							<label><?php esc_html_e( 'Order', 'digimarket' ); ?>
								<select name="order_id"><option value="0"><?php esc_html_e( 'General question', 'digimarket' ); ?></option>
									<?php foreach ( $dm_orders as $dm_o ) : ?><option value="<?php echo (int) $dm_o->id; ?>" <?php selected( $dm_pre, (int) $dm_o->id ); ?>><?php echo esc_html( dm_order_number( $dm_o ) . ' — ' . implode( ', ', wp_list_pluck( dm_get_order_items( $dm_o->id ), 'product_title' ) ) ); ?></option><?php endforeach; ?>
								</select>
							</label>
							<label><?php esc_html_e( 'Topic', 'digimarket' ); ?>
								<select name="subject">
									<?php foreach ( array( __( 'File didn’t download', 'digimarket' ), __( 'Refund request', 'digimarket' ), __( 'Product not as described', 'digimarket' ), __( 'License key problem', 'digimarket' ), __( 'Payment issue', 'digimarket' ), __( 'Other', 'digimarket' ) ) as $dm_s ) : ?><option><?php echo esc_html( $dm_s ); ?></option><?php endforeach; ?>
								</select>
							</label>
							<label class="dm-span-2"><?php esc_html_e( 'Describe the issue', 'digimarket' ); ?><textarea name="message" rows="4" required></textarea></label>
						</div>
						<button class="dm-btn dm-btn-primary"><?php esc_html_e( 'Send request', 'digimarket' ); ?></button>
					</form>
				</div>
				<?php foreach ( $dm_tickets as $dm_t ) : ?>
					<div class="dm-card dm-pad dm-ticket">
						<div class="dm-card-head"><h3 class="dm-h3"><?php echo esc_html( $dm_t->subject ); ?> <?php echo dm_status_badge( $dm_t->status ); // phpcs:ignore ?></h3><small class="dm-muted"><?php echo esc_html( ( $dm_t->order_id ? dm_order_number( $dm_t->order_id ) . ' · ' : '' ) . mysql2date( 'M j, Y', $dm_t->created_at ) ); ?></small></div>
						<p><?php echo nl2br( esc_html( $dm_t->message ) ); ?></p>
						<?php if ( $dm_t->reply ) : ?><div class="dm-reply"><strong><?php esc_html_e( 'Reply:', 'digimarket' ); ?></strong> <?php echo nl2br( esc_html( $dm_t->reply ) ); ?></div><?php endif; ?>
					</div>
				<?php endforeach; ?>
				<?php
				break;

			case 'profile':
				?>
				<div class="dm-two">
					<div class="dm-card dm-pad">
						<h2 class="dm-h3"><?php esc_html_e( 'Profile', 'digimarket' ); ?></h2>
						<form method="post" class="dm-form">
							<?php dm_nonce_field( 'update_profile' ); ?>
							<label><?php esc_html_e( 'Full name', 'digimarket' ); ?><input type="text" name="name" required value="<?php echo esc_attr( $dm_user->display_name ); ?>"></label>
							<label><?php esc_html_e( 'Email', 'digimarket' ); ?><input type="email" name="email" required value="<?php echo esc_attr( $dm_user->user_email ); ?>"></label>
							<label><?php esc_html_e( 'Phone', 'digimarket' ); ?><input type="tel" name="phone" value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_phone', true ) ); ?>"></label>
							<label><?php esc_html_e( 'Current password (required to change email)', 'digimarket' ); ?><input type="password" name="current_password" autocomplete="current-password"></label>
							<button class="dm-btn dm-btn-primary"><?php esc_html_e( 'Save profile', 'digimarket' ); ?></button>
						</form>
					</div>
					<div class="dm-card dm-pad">
						<h2 class="dm-h3"><?php esc_html_e( 'Change password', 'digimarket' ); ?></h2>
						<form method="post" class="dm-form">
							<?php dm_nonce_field( 'change_password' ); ?>
							<label><?php esc_html_e( 'Current password', 'digimarket' ); ?><input type="password" name="current_password" required autocomplete="current-password"></label>
							<label><?php esc_html_e( 'New password', 'digimarket' ); ?><input type="password" name="new_password" minlength="8" required autocomplete="new-password"></label>
							<label><?php esc_html_e( 'Confirm new password', 'digimarket' ); ?><input type="password" name="new_password2" minlength="8" required autocomplete="new-password"></label>
							<button class="dm-btn dm-btn-primary"><?php esc_html_e( 'Update password', 'digimarket' ); ?></button>
						</form>
					</div>
				</div>
				<div class="dm-card dm-pad">
					<h2 class="dm-h3"><?php esc_html_e( 'Your data', 'digimarket' ); ?></h2>
					<p class="dm-muted"><?php esc_html_e( 'Download a copy of your data, or permanently delete your account.', 'digimarket' ); ?></p>
					<p><a class="dm-btn dm-btn-ghost" href="<?php echo esc_url( wp_nonce_url( dm_url( 'account', 'export' ), 'dm_export_me' ) ); ?>"><?php esc_html_e( 'Export my data (JSON)', 'digimarket' ); ?></a></p>
					<?php if ( ! current_user_can( 'manage_options' ) ) : ?>
						<details>
							<summary class="dm-danger"><?php esc_html_e( 'Delete my account', 'digimarket' ); ?></summary>
							<form method="post" class="dm-form">
								<?php dm_nonce_field( 'delete_account' ); ?>
								<p class="dm-muted"><?php esc_html_e( 'This removes your personal data. Past orders are kept anonymously for accounting. This cannot be undone.', 'digimarket' ); ?></p>
								<label><?php esc_html_e( 'Password', 'digimarket' ); ?><input type="password" name="current_password" required></label>
								<button class="dm-btn dm-btn-danger" data-confirm="<?php esc_attr_e( 'Permanently delete your account?', 'digimarket' ); ?>"><?php esc_html_e( 'Delete account', 'digimarket' ); ?></button>
							</form>
						</details>
					<?php endif; ?>
				</div>
				<?php
				break;
		endswitch;
		?>
	</section>
</div>
<?php
get_footer();
