<?php
/**
 * Seller onboarding wizard: account → shop → payout KYC → review & launch.
 *
 * @package DigiMarket
 */

$dm_uid  = get_current_user_id();
$dm_step = dm_onboarding_step( $dm_uid );
if ( 5 === $dm_step ) {
	dm_redirect( dm_url( 'dashboard' ) );
}
get_header();

$dm_labels = array( 1 => __( 'Account', 'digimarket' ), 2 => __( 'Shop', 'digimarket' ), 3 => __( 'Payouts', 'digimarket' ), 4 => __( 'Launch', 'digimarket' ) );
?>
<div class="dm-container dm-page dm-narrow">
	<ol class="dm-steps">
		<?php foreach ( $dm_labels as $dm_n => $dm_l ) : ?>
			<li class="<?php echo $dm_n < $dm_step ? 'is-done' : ( $dm_n === $dm_step ? 'is-active' : '' ); ?>"><?php echo esc_html( $dm_l ); ?></li>
		<?php endforeach; ?>
	</ol>

	<?php if ( 1 === $dm_step ) : ?>
		<div class="dm-sell-hero">
			<h1><?php esc_html_e( 'Open your shop in minutes', 'digimarket' ); ?></h1>
			<p class="dm-lead"><?php esc_html_e( 'Sell ebooks, courses, templates, software, presets, music and more. We handle checkout, delivery and payouts — you keep creating.', 'digimarket' ); ?></p>
			<ul class="dm-benefits">
				<li><strong>💸 <?php esc_html_e( 'Automatic payouts', 'digimarket' ); ?></strong><span><?php esc_html_e( 'Your share goes straight to your bank account via Razorpay Route.', 'digimarket' ); ?></span></li>
				<li><strong>🏪 <?php esc_html_e( 'Your own branded shop', 'digimarket' ); ?></strong><span><?php esc_html_e( 'Custom name, URL, logo, banner and colours.', 'digimarket' ); ?></span></li>
				<li><strong>⚡ <?php esc_html_e( 'Instant, secure delivery', 'digimarket' ); ?></strong><span><?php esc_html_e( 'Files, license keys or access links — protected by expiring links.', 'digimarket' ); ?></span></li>
				<li><strong>📈 <?php esc_html_e( 'Real-time dashboard', 'digimarket' ); ?></strong><span><?php esc_html_e( 'Sales, orders, payouts and reviews in one place.', 'digimarket' ); ?></span></li>
			</ul>
			<?php
			$dm_start = dm_opt( 'commission_start_date' );
			if ( $dm_start && strtotime( $dm_start ) > current_time( 'timestamp' ) ) :
				?>
				<div class="dm-notice dm-notice-success"><?php echo esc_html( sprintf( /* translators: %s date */ __( 'Launch offer: 0%% commission until %s — keep 100%% of every sale.', 'digimarket' ), date_i18n( get_option( 'date_format' ), strtotime( $dm_start ) ) ) ); ?></div>
			<?php else : ?>
				<p class="dm-muted"><?php echo esc_html( sprintf( /* translators: %s percent */ __( 'Simple pricing: a %s%% commission per sale. No monthly fees.', 'digimarket' ), number_format_i18n( (float) dm_opt( 'commission_global' ), 1 ) ) ); ?></p>
			<?php endif; ?>
			<div class="dm-hero-cta">
				<a class="dm-btn dm-btn-primary dm-btn-lg" href="<?php echo esc_url( dm_url( 'register', '', '', array( 'intent' => 'seller' ) ) ); ?>"><?php esc_html_e( 'Create seller account', 'digimarket' ); ?></a>
				<a class="dm-btn dm-btn-ghost dm-btn-lg" href="<?php echo esc_url( dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_url( 'sell' ) ) ) ) ); ?>"><?php esc_html_e( 'I already have an account', 'digimarket' ); ?></a>
			</div>
		</div>

	<?php elseif ( 2 === $dm_step ) : ?>
		<div class="dm-card dm-pad">
			<h1><?php esc_html_e( 'Set up your shop', 'digimarket' ); ?></h1>
			<p class="dm-muted"><?php esc_html_e( 'You can change all of this later in Shop Settings.', 'digimarket' ); ?></p>
			<?php get_template_part( 'template-parts/shop-form', null, array( 'new' => true ) ); ?>
		</div>

	<?php elseif ( 3 === $dm_step ) : ?>
		<div class="dm-card dm-pad">
			<h1><?php esc_html_e( 'Payout details (KYC)', 'digimarket' ); ?></h1>
			<p class="dm-muted"><?php esc_html_e( 'Required to receive money. These details create your Razorpay linked account so your earnings are transferred to you automatically. Sensitive data is encrypted.', 'digimarket' ); ?></p>
			<?php get_template_part( 'template-parts/kyc-form' ); ?>
		</div>

	<?php else : ?>
		<div class="dm-card dm-pad">
			<h1><?php esc_html_e( 'Review & launch', 'digimarket' ); ?></h1>
			<div class="dm-review-shop">
				<?php echo dm_shop_logo( $dm_uid, 'dm-avatar dm-avatar-xl' ); // phpcs:ignore ?>
				<div>
					<h2><?php echo esc_html( dm_shop_name( $dm_uid ) ); ?></h2>
					<p class="dm-muted"><?php echo esc_html( dm_store_url( $dm_uid ) ); ?></p>
					<p><?php echo esc_html( get_user_meta( $dm_uid, 'dm_shop_bio', true ) ); ?></p>
					<p><?php esc_html_e( 'Payout method:', 'digimarket' ); ?> <?php echo get_user_meta( $dm_uid, 'dm_bank_last4', true ) ? esc_html( '•••• ' . get_user_meta( $dm_uid, 'dm_bank_last4', true ) . ' (' . get_user_meta( $dm_uid, 'dm_ifsc', true ) . ')' ) : esc_html( get_user_meta( $dm_uid, 'dm_upi', true ) ); ?></p>
				</div>
			</div>
			<p><a href="<?php echo esc_url( dm_url( 'dashboard', 'settings' ) ); ?>"><?php esc_html_e( 'Edit shop', 'digimarket' ); ?></a> · <a href="<?php echo esc_url( dm_store_url( $dm_uid ) ); ?>" target="_blank"><?php esc_html_e( 'Preview my shop', 'digimarket' ); ?></a></p>
			<form method="post" class="dm-form">
				<?php dm_nonce_field( 'seller_submit' ); ?>
				<label class="dm-check"><input type="checkbox" name="agree" value="1" required> <span><?php echo wp_kses_post( sprintf( /* translators: %s url */ __( 'I accept the <a href="%s" target="_blank">Seller Agreement</a>, including the commission terms.', 'digimarket' ), esc_url( dm_legal_url( 'seller' ) ) ) ); ?></span></label>
				<label class="dm-check"><input type="checkbox" name="own_rights" value="1" required> <span><?php echo wp_kses_post( sprintf( /* translators: %s url */ __( 'I confirm I own the rights to everything I will sell (<a href="%s" target="_blank">Content & IP Policy</a>).', 'digimarket' ), esc_url( dm_legal_url( 'content' ) ) ) ); ?></span></label>
				<button class="dm-btn dm-btn-primary dm-btn-lg"><?php echo dm_opt( 'auto_approve_sellers' ) ? esc_html__( 'Launch my shop 🚀', 'digimarket' ) : esc_html__( 'Submit for review', 'digimarket' ); ?></button>
			</form>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
