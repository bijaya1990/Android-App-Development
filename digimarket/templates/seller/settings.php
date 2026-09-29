<?php
/**
 * Shop settings.
 *
 * @package DigiMarket
 */
?>
<div class="dm-card dm-pad">
	<?php get_template_part( 'template-parts/shop-form', null, array( 'new' => false ) ); ?>
</div>
<div class="dm-card dm-pad" id="gst">
	<h2 class="dm-h3"><?php esc_html_e( 'GST details', 'digimarket' ); ?></h2>
	<p class="dm-muted"><?php echo esc_html( sprintf( /* translators: %s limit */ __( 'GST registration is compulsory once your sales cross %s in a financial year (April–March). Below that it is optional — and you are solely responsible for your own tax compliance.', 'digimarket' ), dm_inr( dm_gst_opt( 'threshold' ) ) ) ); ?></p>
	<p><?php echo esc_html( sprintf( /* translators: 1 FY 2 amount */ __( 'Your sales in %1$s: %2$s', 'digimarket' ), dm_fy_label(), dm_inr( dm_fy_turnover( $dm_uid ) ) ) ); ?></p>
	<form method="post" class="dm-form">
		<?php dm_nonce_field( 'seller_gst' ); ?>
		<?php dm_seller_gst_fields( $dm_uid ); ?>
		<div class="dm-form-actions"><button class="dm-btn dm-btn-primary"><?php esc_html_e( 'Save GST details', 'digimarket' ); ?></button></div>
	</form>
</div>
