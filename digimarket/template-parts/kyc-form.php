<?php
/**
 * Payout / KYC form.
 *
 * @package DigiMarket
 */

$dm_uid  = get_current_user_id();
$dm_addr = (array) get_user_meta( $dm_uid, 'dm_address', true );
$dm_has  = (bool) get_user_meta( $dm_uid, 'dm_kyc_submitted', true );
$dm_pan  = dm_decrypt( get_user_meta( $dm_uid, 'dm_pan', true ) );
$dm_bt   = get_user_meta( $dm_uid, 'dm_business_type', true );
?>
<form method="post" class="dm-form" autocomplete="off">
	<?php dm_nonce_field( 'seller_kyc' ); ?>
	<div class="dm-form-grid">
		<label class="dm-span-2"><?php esc_html_e( 'Full legal name (as on PAN)', 'digimarket' ); ?> *<input type="text" name="legal_name" required value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_legal_name', true ) ); ?>"></label>
		<label><?php esc_html_e( 'PAN', 'digimarket' ); ?> *<input type="text" name="pan" maxlength="10" style="text-transform:uppercase" placeholder="<?php echo $dm_pan ? esc_attr( dm_mask( $dm_pan ) ) : 'ABCDE1234F'; ?>" <?php echo $dm_pan ? '' : 'required'; ?>><?php if ( $dm_pan ) : ?><small class="dm-muted"><?php esc_html_e( 'Saved. Leave blank to keep.', 'digimarket' ); ?></small><?php endif; ?></label>
		<label><?php esc_html_e( 'Phone', 'digimarket' ); ?> *<input type="tel" name="phone" required value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_phone', true ) ); ?>" placeholder="+91XXXXXXXXXX"></label>
		<label class="dm-span-2"><?php esc_html_e( 'Business type', 'digimarket' ); ?> *
			<select name="business_type">
				<?php foreach ( array( 'individual' => __( 'Individual', 'digimarket' ), 'proprietorship' => __( 'Proprietorship', 'digimarket' ), 'partnership' => __( 'Partnership', 'digimarket' ), 'private_limited' => __( 'Private Limited (registered business)', 'digimarket' ), 'llp' => __( 'LLP', 'digimarket' ), 'public_limited' => __( 'Public Limited', 'digimarket' ) ) as $dm_k => $dm_l ) : ?>
					<option value="<?php echo esc_attr( $dm_k ); ?>" <?php selected( $dm_bt, $dm_k ); ?>><?php echo esc_html( $dm_l ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<fieldset class="dm-span-2 dm-fieldset">
			<legend><?php esc_html_e( 'Where should we send your money?', 'digimarket' ); ?></legend>
			<div class="dm-form-grid">
				<label><?php esc_html_e( 'Bank account number', 'digimarket' ); ?><input type="text" name="bank_account" inputmode="numeric" placeholder="<?php echo get_user_meta( $dm_uid, 'dm_bank_last4', true ) ? esc_attr( '•••• ' . get_user_meta( $dm_uid, 'dm_bank_last4', true ) . ' — ' . __( 'leave blank to keep', 'digimarket' ) ) : ''; ?>"></label>
				<label><?php esc_html_e( 'IFSC', 'digimarket' ); ?><input type="text" name="ifsc" maxlength="11" style="text-transform:uppercase" value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_ifsc', true ) ); ?>" placeholder="HDFC0001234"></label>
				<label class="dm-span-2"><?php esc_html_e( 'or UPI ID', 'digimarket' ); ?><input type="text" name="upi" value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_upi', true ) ); ?>" placeholder="name@okbank"><small class="dm-muted"><?php esc_html_e( 'Automatic Razorpay Route settlements require a bank account; UPI-only payouts are processed manually.', 'digimarket' ); ?></small></label>
			</div>
		</fieldset>
		<label class="dm-span-2"><?php esc_html_e( 'Street address', 'digimarket' ); ?><input type="text" name="addr_street" value="<?php echo esc_attr( $dm_addr['street'] ?? '' ); ?>"></label>
		<label><?php esc_html_e( 'City', 'digimarket' ); ?><input type="text" name="addr_city" value="<?php echo esc_attr( $dm_addr['city'] ?? '' ); ?>"></label>
		<label><?php esc_html_e( 'State', 'digimarket' ); ?><input type="text" name="addr_state" value="<?php echo esc_attr( $dm_addr['state'] ?? '' ); ?>"></label>
		<label><?php esc_html_e( 'PIN code', 'digimarket' ); ?><input type="text" name="addr_postal_code" inputmode="numeric" maxlength="6" value="<?php echo esc_attr( $dm_addr['postal_code'] ?? '' ); ?>"></label>
	</div>
	<?php if ( $dm_has ) : ?><p class="dm-notice dm-notice-info"><?php esc_html_e( 'Changing bank/UPI details triggers re-verification before the next payout.', 'digimarket' ); ?></p><?php endif; ?>
	<div class="dm-form-actions"><button class="dm-btn dm-btn-primary dm-btn-lg"><?php echo $dm_has ? esc_html__( 'Update payout details', 'digimarket' ) : esc_html__( 'Save & continue', 'digimarket' ); ?></button></div>
</form>
