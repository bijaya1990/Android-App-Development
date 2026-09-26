<?php
/**
 * Seller dashboard: /dashboard/{tab}/{id}
 *
 * @package DigiMarket
 */

$dm_uid    = get_current_user_id();
$dm_tab    = dm_tab() ? dm_tab() : 'overview';
$dm_status = dm_seller_status( $dm_uid );
$dm_kyc    = get_user_meta( $dm_uid, 'dm_kyc_status', true );
$dm_nav    = array(
	'overview' => array( '📊', __( 'Overview', 'digimarket' ) ),
	'products' => array( '📦', __( 'Products', 'digimarket' ) ),
	'orders'   => array( '🧾', __( 'Orders', 'digimarket' ) ),
	'payouts'  => array( '💸', __( 'Payouts', 'digimarket' ) ),
	'reviews'  => array( '⭐', __( 'Reviews', 'digimarket' ) ),
	'support'  => array( '💬', __( 'Support', 'digimarket' ) ),
	'settings' => array( '⚙️', __( 'Shop settings', 'digimarket' ) ),
);
$dm_active = 'edit' === $dm_tab ? 'products' : $dm_tab;
if ( ! isset( $dm_nav[ $dm_active ] ) ) {
	$dm_active = 'overview';
	$dm_tab    = 'overview';
}
if ( dm_onboarding_step( $dm_uid ) < 4 ) {
	dm_redirect( dm_url( 'sell' ) );
}
get_header();
?>
<div class="dm-dash">
	<aside class="dm-dash-side">
		<div class="dm-dash-shop">
			<?php echo dm_shop_logo( $dm_uid, 'dm-avatar' ); // phpcs:ignore ?>
			<div><strong><?php echo esc_html( dm_shop_name( $dm_uid ) ); ?></strong><a class="dm-small" href="<?php echo esc_url( dm_store_url( $dm_uid ) ); ?>" target="_blank"><?php esc_html_e( 'View shop ↗', 'digimarket' ); ?></a></div>
		</div>
		<nav class="dm-dash-nav" aria-label="<?php esc_attr_e( 'Seller dashboard', 'digimarket' ); ?>">
			<?php foreach ( $dm_nav as $dm_k => $dm_n ) : ?>
				<a class="<?php echo $dm_k === $dm_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( dm_url( 'dashboard', 'overview' === $dm_k ? '' : $dm_k ) ); ?>"><span aria-hidden="true"><?php echo esc_html( $dm_n[0] ); ?></span> <?php echo esc_html( $dm_n[1] ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( dm_url( 'account' ) ); ?>"><span aria-hidden="true">👤</span> <?php esc_html_e( 'Buyer account', 'digimarket' ); ?></a>
		</nav>
	</aside>

	<section class="dm-dash-main">
		<div class="dm-dash-top">
			<h1><?php echo esc_html( 'edit' === $dm_tab ? ( dm_route_id() ? __( 'Edit product', 'digimarket' ) : __( 'Add product', 'digimarket' ) ) : $dm_nav[ $dm_active ][1] ); ?></h1>
			<div class="dm-dash-top-actions">
				<a class="dm-icon-btn" href="<?php echo esc_url( dm_url( 'account', 'notifications' ) ); ?>" aria-label="<?php esc_attr_e( 'Notifications', 'digimarket' ); ?>">🔔<?php $dm_un = dm_unread_notifications( $dm_uid ); echo $dm_un ? '<span class="dm-dot">' . (int) $dm_un . '</span>' : ''; ?></a>
				<?php if ( 'suspended' !== $dm_status ) : ?><a class="dm-btn dm-btn-primary" href="<?php echo esc_url( dm_url( 'dashboard', 'edit' ) ); ?>">+ <?php esc_html_e( 'Add product', 'digimarket' ); ?></a><?php endif; ?>
			</div>
		</div>

		<?php if ( 'pending' === $dm_status ) : ?>
			<div class="dm-notice dm-notice-warning"><?php esc_html_e( 'Your shop is under review. You can prepare products as drafts; they can be published once approved.', 'digimarket' ); ?></div>
		<?php elseif ( 'suspended' === $dm_status ) : ?>
			<div class="dm-notice dm-notice-error"><?php esc_html_e( 'Your shop is suspended and hidden from buyers. Please contact support.', 'digimarket' ); ?></div>
		<?php elseif ( 'rejected' === $dm_status ) : ?>
			<div class="dm-notice dm-notice-error"><?php esc_html_e( 'Your shop application was not approved. Contact support for details.', 'digimarket' ); ?></div>
		<?php endif; ?>
		<?php if ( 'verified' !== $dm_kyc && 'razorpay' === dm_opt( 'gateway' ) ) : ?>
			<div class="dm-notice dm-notice-info"><?php esc_html_e( 'Payout verification is pending — you can build your shop, but live payments start once your Razorpay linked account is activated.', 'digimarket' ); ?> <a href="<?php echo esc_url( dm_url( 'dashboard', 'payouts' ) ); ?>"><?php esc_html_e( 'Check status', 'digimarket' ); ?></a></div>
		<?php endif; ?>

		<?php
		$dm_file = DM_DIR . '/templates/seller/' . $dm_tab . '.php';
		if ( file_exists( $dm_file ) ) {
			include $dm_file;
		}
		?>
	</section>
</div>
<?php
get_footer();
