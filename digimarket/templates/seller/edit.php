<?php
/**
 * Add / edit product form.
 *
 * @package DigiMarket
 */

$dm_pid = absint( dm_route_id() );
if ( $dm_pid && ! dm_seller_owns( $dm_pid ) ) {
	dm_empty_state( __( 'Product not found', 'digimarket' ), __( 'You can only edit your own products.', 'digimarket' ), dm_url( 'dashboard', 'products' ), __( 'Back to products', 'digimarket' ) );
	return;
}
$dm_p        = $dm_pid ? get_post( $dm_pid ) : null;
$dm_get      = function ( $key, $default = '' ) use ( $dm_pid ) {
	if ( ! $dm_pid ) {
		return $default;
	}
	$v = get_post_meta( $dm_pid, $key, true );
	return '' === $v ? $default : $v;
};
$dm_cat      = $dm_pid ? wp_get_object_terms( $dm_pid, 'dm_category', array( 'fields' => 'ids' ) ) : array( (int) get_user_meta( $dm_uid, 'dm_shop_category', true ) );
$dm_tags     = $dm_pid ? implode( ', ', wp_get_object_terms( $dm_pid, 'dm_tag', array( 'fields' => 'names' ) ) ) : '';
$dm_delivery = $dm_get( '_dm_delivery', 'file' );
$dm_gallery  = array_filter( array_map( 'absint', (array) $dm_get( '_dm_gallery', array() ) ) );
$dm_status   = $dm_p ? $dm_p->post_status : 'draft';
?>
<form method="post" enctype="multipart/form-data" class="dm-form dm-product-form">
	<?php dm_nonce_field( 'seller_product_save' ); ?>
	<input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>">
	<div class="dm-edit-grid">
		<div class="dm-edit-main">
			<div class="dm-card dm-pad">
				<h2 class="dm-h3"><?php esc_html_e( 'Basics', 'digimarket' ); ?></h2>
				<label><?php esc_html_e( 'Title', 'digimarket' ); ?> *<input type="text" name="title" maxlength="100" required value="<?php echo esc_attr( $dm_p ? $dm_p->post_title : '' ); ?>" data-count="100"><small class="dm-muted dm-counter"></small></label>
				<label><?php esc_html_e( 'URL slug', 'digimarket' ); ?><input type="text" name="slug" value="<?php echo esc_attr( $dm_p ? $dm_p->post_name : '' ); ?>" placeholder="<?php esc_attr_e( 'auto-generated from title', 'digimarket' ); ?>"></label>
				<label><?php esc_html_e( 'Short description', 'digimarket' ); ?><textarea name="short_description" rows="2" maxlength="300" placeholder="<?php esc_attr_e( '1–2 lines shown on product cards', 'digimarket' ); ?>"><?php echo esc_textarea( $dm_p ? $dm_p->post_excerpt : '' ); ?></textarea></label>
				<div class="dm-label"><?php esc_html_e( 'Full description', 'digimarket' ); ?></div>
				<?php
				wp_editor(
					$dm_p ? $dm_p->post_content : '',
					'full_description',
					array(
						'textarea_name' => 'full_description',
						'media_buttons' => false,
						'textarea_rows' => 12,
						'teeny'         => false,
						'quicktags'     => true,
						'tinymce'       => array( 'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,image,undo,redo', 'toolbar2' => '' ),
					)
				);
				?>
			</div>

			<div class="dm-card dm-pad">
				<h2 class="dm-h3"><?php esc_html_e( 'Images', 'digimarket' ); ?></h2>
				<div class="dm-form-grid">
					<label><?php esc_html_e( 'Thumbnail', 'digimarket' ); ?> *
						<span class="dm-upload-preview dm-thumb-preview"><?php echo $dm_pid && has_post_thumbnail( $dm_pid ) ? get_the_post_thumbnail( $dm_pid, 'dm-card' ) : ''; ?></span>
						<input type="file" name="thumbnail" accept="image/*" data-preview>
					</label>
					<div>
						<div class="dm-label"><?php esc_html_e( 'Gallery (up to 5)', 'digimarket' ); ?></div>
						<div class="dm-gallery-edit">
							<?php foreach ( $dm_gallery as $dm_g ) : ?>
								<label class="dm-gallery-item"><?php echo wp_get_attachment_image( $dm_g, 'thumbnail' ); ?><span class="dm-check"><input type="checkbox" name="gallery_remove[]" value="<?php echo (int) $dm_g; ?>"> <?php esc_html_e( 'Remove', 'digimarket' ); ?></span></label>
							<?php endforeach; ?>
						</div>
						<?php if ( count( $dm_gallery ) < 5 ) : ?>
							<input type="file" name="gallery[]" accept="image/*" multiple data-max="<?php echo (int) ( 5 - count( $dm_gallery ) ); ?>">
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="dm-card dm-pad">
				<h2 class="dm-h3"><?php esc_html_e( 'Delivery', 'digimarket' ); ?></h2>
				<div class="dm-segmented" role="radiogroup">
					<?php foreach ( array( 'file' => __( 'File download', 'digimarket' ), 'license_key' => __( 'License keys', 'digimarket' ), 'external_link' => __( 'External link', 'digimarket' ) ) as $dm_k => $dm_l ) : ?>
						<label><input type="radio" name="delivery" value="<?php echo esc_attr( $dm_k ); ?>" <?php checked( $dm_delivery, $dm_k ); ?> data-toggle-delivery><span><?php echo esc_html( $dm_l ); ?></span></label>
					<?php endforeach; ?>
				</div>
				<div class="dm-delivery-panel" data-delivery="file">
					<?php $dm_fname = $dm_get( '_dm_file_name' ); ?>
					<?php if ( $dm_fname ) : ?>
						<p class="dm-file-current">📦 <strong><?php echo esc_html( $dm_fname ); ?></strong> · <?php echo esc_html( size_format( (int) $dm_get( '_dm_file_size', 0 ) ) ); ?> · <a href="<?php echo esc_url( dm_url( 'dashboard', 'file', $dm_pid ) ); ?>"><?php esc_html_e( 'Download', 'digimarket' ); ?></a></p>
					<?php endif; ?>
					<label><?php echo $dm_fname ? esc_html__( 'Replace file', 'digimarket' ) : esc_html__( 'Upload file', 'digimarket' ); ?><input type="file" name="digital_file"></label>
					<small class="dm-muted"><?php echo esc_html( sprintf( /* translators: 1 size 2 types */ __( 'Max %1$s. Allowed: %2$s. Files are stored privately and only delivered via expiring links.', 'digimarket' ), size_format( min( wp_max_upload_size(), (float) dm_opt( 'max_upload_mb' ) * MB_IN_BYTES ) ), implode( ', ', array_slice( dm_allowed_extensions(), 0, 14 ) ) . '…' ) ); ?></small>
				</div>
				<div class="dm-delivery-panel" data-delivery="license_key">
					<p><?php echo esc_html( sprintf( /* translators: %d */ __( '%d unused keys in the pool.', 'digimarket' ), $dm_pid ? dm_license_keys_available( $dm_pid ) : 0 ) ); ?> <?php esc_html_e( 'Each purchase automatically receives one unused key.', 'digimarket' ); ?></p>
					<label><?php esc_html_e( 'Add keys (one per line)', 'digimarket' ); ?><textarea name="license_keys" rows="5" placeholder="XXXX-XXXX-XXXX-XXXX"></textarea></label>
					<?php if ( $dm_pid && dm_license_keys_available( $dm_pid ) ) : ?><label class="dm-check"><input type="checkbox" name="delete_unused_keys" value="1"> <?php esc_html_e( 'Delete all unused keys before adding', 'digimarket' ); ?></label><?php endif; ?>				</div>
				<div class="dm-delivery-panel" data-delivery="external_link">
					<label><?php esc_html_e( 'Access link (revealed only after payment)', 'digimarket' ); ?><input type="url" name="external_url" value="<?php echo esc_attr( $dm_get( '_dm_external_url' ) ); ?>" placeholder="https://"></label>
				</div>
				<div class="dm-form-grid">
					<label><?php esc_html_e( 'Download limit per purchase', 'digimarket' ); ?><input type="number" name="download_limit" min="0" value="<?php echo esc_attr( $dm_get( '_dm_download_limit', 0 ) ); ?>"><small class="dm-muted"><?php esc_html_e( '0 = unlimited', 'digimarket' ); ?></small></label>
					<label><?php esc_html_e( 'Access duration (days)', 'digimarket' ); ?><input type="number" name="access_days" min="0" value="<?php echo esc_attr( $dm_get( '_dm_access_days', 0 ) ); ?>"><small class="dm-muted"><?php esc_html_e( '0 = lifetime', 'digimarket' ); ?></small></label>
				</div>
			</div>

			<details class="dm-card dm-pad">
				<summary class="dm-h3"><?php esc_html_e( 'SEO (optional)', 'digimarket' ); ?></summary>
				<label><?php esc_html_e( 'Meta title', 'digimarket' ); ?><input type="text" name="meta_title" maxlength="70" value="<?php echo esc_attr( $dm_get( '_dm_meta_title' ) ); ?>"></label>
				<label><?php esc_html_e( 'Meta description', 'digimarket' ); ?><textarea name="meta_desc" rows="2" maxlength="160"><?php echo esc_textarea( $dm_get( '_dm_meta_desc' ) ); ?></textarea></label>
			</details>
		</div>

		<aside class="dm-edit-side">
			<div class="dm-card dm-pad dm-sticky">
				<h2 class="dm-h3"><?php esc_html_e( 'Publish', 'digimarket' ); ?></h2>
				<label><?php esc_html_e( 'Status', 'digimarket' ); ?>
					<select name="status">
						<option value="draft" <?php selected( $dm_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'digimarket' ); ?></option>
						<option value="publish" <?php selected( $dm_status, 'publish' ); ?>><?php esc_html_e( 'Published', 'digimarket' ); ?></option>
						<option value="dm_unpublished" <?php selected( $dm_status, 'dm_unpublished' ); ?>><?php esc_html_e( 'Unpublished', 'digimarket' ); ?></option>
					</select>
				</label>
				<label><?php esc_html_e( 'Price', 'digimarket' ); ?> (<?php echo esc_html( dm_opt( 'currency_symbol' ) ); ?>) *<input type="number" name="price" min="0" step="0.01" required value="<?php echo esc_attr( $dm_get( '_dm_price', '' ) ); ?>"><small class="dm-muted"><?php esc_html_e( '0 = free product', 'digimarket' ); ?></small></label>
				<label><?php esc_html_e( 'Discount price', 'digimarket' ); ?><input type="number" name="sale_price" min="0" step="0.01" value="<?php echo esc_attr( $dm_get( '_dm_sale_price', '' ) ); ?>"></label>
				<?php $dm_rate = dm_commission_rate( $dm_uid, $dm_pid ); ?>
				<p class="dm-muted dm-small" data-commission="<?php echo esc_attr( $dm_rate ); ?>"><?php echo esc_html( sprintf( /* translators: %s */ __( 'Current commission: %s%%. You receive the rest.', 'digimarket' ), number_format_i18n( $dm_rate, 2 ) ) ); ?> <strong data-you-earn></strong></p>
				<label><?php esc_html_e( 'Category', 'digimarket' ); ?> *
					<select name="category" required>
						<option value=""><?php esc_html_e( 'Choose…', 'digimarket' ); ?></option>
						<?php foreach ( dm_categories() as $dm_c ) : ?>
							<option value="<?php echo (int) $dm_c->term_id; ?>" <?php selected( in_array( $dm_c->term_id, (array) $dm_cat, true ) ); ?>><?php echo esc_html( ( $dm_c->parent ? '— ' : '' ) . $dm_c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Tags', 'digimarket' ); ?><input type="text" name="tags" value="<?php echo esc_attr( $dm_tags ); ?>" placeholder="<?php esc_attr_e( 'notion, planner, productivity', 'digimarket' ); ?>"><small class="dm-muted"><?php esc_html_e( 'Comma separated', 'digimarket' ); ?></small></label>
				<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block"><?php esc_html_e( 'Save product', 'digimarket' ); ?></button>
				<?php if ( $dm_pid ) : ?><a class="dm-btn dm-btn-ghost dm-btn-block" target="_blank" href="<?php echo esc_url( get_permalink( $dm_pid ) ); ?>"><?php esc_html_e( 'Preview ↗', 'digimarket' ); ?></a><?php endif; ?>
			</div>
		</aside>
	</div>
</form>
