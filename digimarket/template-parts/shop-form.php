<?php
/**
 * Shop details form (onboarding + settings). $args['new'] = bool.
 *
 * @package DigiMarket
 */

$dm_uid  = get_current_user_id();
$dm_new  = ! empty( $args['new'] );
$dm_slug = (string) get_user_meta( $dm_uid, 'dm_shop_slug', true );
$dm_base = dm_pretty_permalinks() ? dm_pretty_url( 'store/' ) : add_query_arg( 'dm_store', '', home_url( '/' ) );
$dm_created = (int) get_user_meta( $dm_uid, 'dm_shop_created', true );
?>
<form method="post" enctype="multipart/form-data" class="dm-form">
	<?php dm_nonce_field( 'seller_shop' ); ?>
	<div class="dm-form-grid">
		<label class="dm-span-2"><?php esc_html_e( 'Shop name', 'digimarket' ); ?> *
			<input type="text" name="shop_name" maxlength="60" required value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_shop_name', true ) ); ?>" data-slug-source>
		</label>
		<label class="dm-span-2"><?php esc_html_e( 'Shop URL', 'digimarket' ); ?> *
			<span class="dm-slug-field"><span class="dm-slug-prefix"><?php echo esc_html( preg_replace( '#^https?://#', '', $dm_base ) ); ?></span><input type="text" name="shop_slug" required pattern="[a-z0-9\-]{3,}" value="<?php echo esc_attr( $dm_slug ); ?>" data-slug-input autocomplete="off"><span class="dm-slug-status" data-slug-status aria-live="polite"></span></span>
			<small class="dm-muted"><?php echo $dm_new ? esc_html__( 'Lowercase letters, numbers and dashes. You can change it within 7 days.', 'digimarket' ) : ( $dm_created && $dm_created < time() - 7 * DAY_IN_SECONDS ? esc_html__( 'Changing your URL now keeps the old link working as a redirect.', 'digimarket' ) : esc_html__( 'You can still change your URL freely during the first 7 days.', 'digimarket' ) ); ?></small>
		</label>
		<label><?php esc_html_e( 'Logo', 'digimarket' ); ?>
			<span class="dm-upload-preview"><?php echo dm_shop_logo( $dm_uid, 'dm-avatar dm-avatar-lg' ); // phpcs:ignore ?></span>
			<input type="file" name="shop_logo" accept="image/*" data-preview>
			<?php if ( get_user_meta( $dm_uid, 'dm_shop_logo', true ) ) : ?><span class="dm-check"><input type="checkbox" name="remove_shop_logo" value="1"> <?php esc_html_e( 'Remove', 'digimarket' ); ?></span><?php endif; ?>
		</label>
		<label><?php esc_html_e( 'Banner image', 'digimarket' ); ?>
			<?php $dm_b = dm_shop_banner_url( $dm_uid ); ?>
			<span class="dm-upload-preview dm-banner-preview"<?php echo $dm_b ? ' style="background-image:url(' . esc_url( $dm_b ) . ')"' : ''; ?>></span>
			<input type="file" name="shop_banner" accept="image/*" data-preview>
			<?php if ( $dm_b ) : ?><span class="dm-check"><input type="checkbox" name="remove_shop_banner" value="1"> <?php esc_html_e( 'Remove', 'digimarket' ); ?></span><?php endif; ?>
		</label>
		<label class="dm-span-2"><?php esc_html_e( 'Short bio', 'digimarket' ); ?>
			<textarea name="shop_bio" rows="3" maxlength="500"><?php echo esc_textarea( get_user_meta( $dm_uid, 'dm_shop_bio', true ) ); ?></textarea>
		</label>
		<label><?php esc_html_e( 'Primary category', 'digimarket' ); ?>
			<select name="shop_category">
				<option value="0"><?php esc_html_e( 'Choose…', 'digimarket' ); ?></option>
				<?php foreach ( dm_categories( array( 'parent' => 0 ) ) as $dm_c ) : ?>
					<option value="<?php echo (int) $dm_c->term_id; ?>" <?php selected( (int) get_user_meta( $dm_uid, 'dm_shop_category', true ), $dm_c->term_id ); ?>><?php echo esc_html( $dm_c->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php if ( ! $dm_new ) : ?>
			<label><?php esc_html_e( 'Accent colour', 'digimarket' ); ?>
				<input type="color" name="shop_accent" value="<?php echo esc_attr( dm_shop_accent( $dm_uid ) ); ?>">
			</label>
			<?php foreach ( array( 'website' => __( 'Website', 'digimarket' ), 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'twitter' => 'X / Twitter' ) as $dm_net => $dm_lbl ) : ?>
				<label><?php echo esc_html( $dm_lbl ); ?><input type="url" name="social_<?php echo esc_attr( $dm_net ); ?>" placeholder="https://" value="<?php echo esc_attr( get_user_meta( $dm_uid, 'dm_social_' . $dm_net, true ) ); ?>"></label>
			<?php endforeach; ?>
			<label class="dm-span-2"><?php esc_html_e( 'Refund policy (shown on your products)', 'digimarket' ); ?>
				<textarea name="policy_refund" rows="3" placeholder="<?php esc_attr_e( 'Leave blank to use the marketplace refund policy.', 'digimarket' ); ?>"><?php echo esc_textarea( get_user_meta( $dm_uid, 'dm_policy_refund', true ) ); ?></textarea>
			</label>
			<label class="dm-span-2"><?php esc_html_e( 'Delivery notes', 'digimarket' ); ?>
				<textarea name="policy_delivery" rows="2" placeholder="<?php esc_attr_e( 'e.g. Files are ZIP archives. Course access links are emailed within 5 minutes.', 'digimarket' ); ?>"><?php echo esc_textarea( get_user_meta( $dm_uid, 'dm_policy_delivery', true ) ); ?></textarea>
			</label>
		<?php endif; ?>
	</div>
	<div class="dm-form-actions">
		<button class="dm-btn dm-btn-primary dm-btn-lg"><?php echo $dm_new ? esc_html__( 'Create shop & continue', 'digimarket' ) : esc_html__( 'Save shop settings', 'digimarket' ); ?></button>
		<?php if ( ! $dm_new ) : ?><a class="dm-btn dm-btn-ghost" target="_blank" href="<?php echo esc_url( dm_store_url( $dm_uid ) ); ?>"><?php esc_html_e( 'Preview my shop ↗', 'digimarket' ); ?></a><?php endif; ?>
	</div>
</form>
