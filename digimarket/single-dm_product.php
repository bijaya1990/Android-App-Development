<?php
/**
 * Product / service / partner-offer detail page.
 *
 * @package DigiMarket
 */

get_header();

while ( have_posts() ) :
	the_post();
	$dm_pid      = get_the_ID();
	$dm_seller   = (int) get_the_author_meta( 'ID' );
	$dm_uid      = get_current_user_id();
	$dm_type     = dm_listing_type( $dm_pid );
	$dm_gallery  = array_filter( array_map( 'absint', (array) get_post_meta( $dm_pid, '_dm_gallery', true ) ) );
	$dm_thumb    = get_post_thumbnail_id( $dm_pid );
	$dm_images   = array_values( array_unique( array_filter( array_merge( array( $dm_thumb ), $dm_gallery ) ) ) );
	$dm_owned    = 'digital' === $dm_type ? dm_user_purchase( $dm_uid, $dm_pid ) : null;
	$dm_mine     = $dm_uid && $dm_uid === $dm_seller;
	$dm_delivery = get_post_meta( $dm_pid, '_dm_delivery', true );
	$dm_cats     = get_the_terms( $dm_pid, 'dm_category' );
	$dm_tags     = get_the_terms( $dm_pid, 'dm_tag' );
	$dm_rcount   = (int) get_post_meta( $dm_pid, '_dm_rating_count', true );
	$dm_sales    = (int) get_post_meta( $dm_pid, '_dm_sales', true );
	$dm_price    = dm_product_price( $dm_pid );
	$dm_regular  = dm_product_regular_price( $dm_pid );
	$dm_off      = dm_discount_percent( $dm_pid );
	$dm_wa       = 'service' === $dm_type ? dm_service_whatsapp_url( $dm_pid ) : '';
	$dm_checks   = dm_lines( get_post_meta( $dm_pid, 'service' === $dm_type ? '_dm_included' : '_dm_what_you_get', true ) );
	list( $dm_can, $dm_reason ) = dm_can_purchase( $dm_pid );
	$dm_share = dm_whatsapp_share_url( get_permalink(), get_the_title() );
	?>
	<div class="dm-container dm-page dm-pdp dm-pdp-<?php echo esc_attr( $dm_type ); ?>">
		<?php dm_render_breadcrumbs(); ?>

		<?php if ( 'publish' !== get_post_status() ) : ?>
			<div class="dm-notice dm-notice-warning"><?php echo esc_html( sprintf( /* translators: %s status */ __( 'Preview — this listing is %s and not visible to visitors.', 'digimarket' ), wp_strip_all_tags( dm_status_badge( get_post_status() ) ) ) ); ?></div>
		<?php endif; ?>

		<div class="dm-pdp-top">
			<div class="dm-pdp-gallery" data-gallery>
				<?php if ( count( $dm_images ) > 1 ) : ?>
					<div class="dm-gallery-thumbs" aria-label="<?php esc_attr_e( 'Images', 'digimarket' ); ?>">
						<?php foreach ( $dm_images as $dm_n => $dm_img ) : ?>
							<button type="button" class="<?php echo 0 === $dm_n ? 'is-active' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $dm_img, 'dm-gallery' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d */ __( 'Show image %d', 'digimarket' ), $dm_n + 1 ) ); ?>">
								<?php echo wp_get_attachment_image( $dm_img, 'thumbnail', false, array( 'loading' => 'lazy' ) ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="dm-gallery-main" data-zoom>
					<?php if ( $dm_images ) : ?>
						<?php echo wp_get_attachment_image( $dm_images[0], 'dm-gallery', false, array( 'class' => 'dm-gallery-img', 'data-main' => '1', 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(max-width: 960px) 100vw, 560px' ) ); ?>
					<?php else : ?>
						<?php echo dm_product_thumb( $dm_pid, 'dm-gallery' ); // phpcs:ignore ?>
					<?php endif; ?>
					<?php if ( $dm_off >= 5 ) : ?><span class="dm-badge2 is-off dm-gallery-off"><?php echo esc_html( sprintf( /* translators: %d */ __( '%d%% OFF', 'digimarket' ), $dm_off ) ); ?></span><?php endif; ?>
				</div>
			</div>

			<div class="dm-pdp-info">
				<?php if ( $dm_cats && ! is_wp_error( $dm_cats ) ) : ?><a class="dm-eyebrow" href="<?php echo esc_url( get_term_link( $dm_cats[0] ) ); ?>"><?php echo esc_html( $dm_cats[0]->name ); ?></a><?php endif; ?>
				<h1 class="dm-pdp-title"><?php the_title(); ?></h1>
				<div class="dm-pdp-meta">
					<?php if ( $dm_rcount ) : ?>
						<a href="#reviews" class="dm-pdp-rating"><?php echo dm_rating_chip( $dm_pid, false ); // phpcs:ignore ?> <span><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d rating & review', '%d ratings & reviews', $dm_rcount, 'digimarket' ), $dm_rcount ) ); ?></span></a>
					<?php else : ?>
						<span class="dm-muted"><?php esc_html_e( 'No reviews yet', 'digimarket' ); ?></span>
					<?php endif; ?>
					<?php if ( 'digital' === $dm_type && $dm_sales ) : ?><span class="dm-muted">· <?php echo esc_html( sprintf( /* translators: %d */ _n( '%d sale', '%d sales', $dm_sales, 'digimarket' ), $dm_sales ) ); ?></span><?php endif; ?>
					<?php foreach ( dm_product_badges( $dm_pid, 3 ) as $dm_b ) : ?><span class="dm-badge2 <?php echo esc_attr( $dm_b[1] ); ?>"><?php echo esc_html( $dm_b[0] ); ?></span><?php endforeach; ?>
				</div>

				<div class="dm-pdp-price">
					<?php if ( 'service' === $dm_type ) : ?>
						<div class="dm-pp-row"><small><?php esc_html_e( 'Starting from', 'digimarket' ); ?></small><b><?php echo esc_html( dm_money_short( $dm_price ) ); ?></b></div>
						<p class="dm-muted dm-small"><?php esc_html_e( 'Final price depends on your requirements — let’s discuss on WhatsApp. No payment now.', 'digimarket' ); ?></p>
					<?php elseif ( 'affiliate' === $dm_type ) : ?>
						<?php if ( $dm_price > 0 ) : ?><div class="dm-pp-row"><small><?php esc_html_e( 'From', 'digimarket' ); ?></small><b><?php echo esc_html( dm_money_short( $dm_price ) ); ?></b><small><?php echo esc_html( get_post_meta( $dm_pid, '_dm_aff_suffix', true ) ); ?></small></div><?php endif; ?>
						<p class="dm-muted dm-small"><?php esc_html_e( 'Prices on the partner’s website may change — check the final price there.', 'digimarket' ); ?></p>
					<?php else : ?>
						<div class="dm-pp-row">
							<b><?php echo $dm_price > 0 ? esc_html( dm_money_short( $dm_price ) ) : esc_html__( 'Free', 'digimarket' ); ?></b>
							<?php if ( $dm_off ) : ?><del><?php echo esc_html( dm_money_short( $dm_regular ) ); ?></del><span class="dm-cp-off"><?php echo esc_html( sprintf( /* translators: %d */ __( '%d%% off', 'digimarket' ), $dm_off ) ); ?></span><?php endif; ?>
						</div>
						<?php if ( $dm_off ) : ?><p class="dm-save"><?php echo esc_html( sprintf( /* translators: %s */ __( 'You save %s', 'digimarket' ), dm_money_short( $dm_regular - $dm_price ) ) ); ?></p><?php endif; ?>
						<?php echo dm_countdown_html( dm_sale_ends_ts( $dm_pid ), __( 'Offer ends in', 'digimarket' ) ); // phpcs:ignore ?>
					<?php endif; ?>
				</div>

				<?php
				if ( 'digital' === $dm_type && $dm_can && ! $dm_owned ) :
					$dm_best = dm_best_coupon_for( $dm_pid );
					if ( $dm_best ) :
						?>
						<div class="dm-offers">
							<strong><?php echo dm_icon( 'tag', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Available offer', 'digimarket' ); ?></strong>
							<p><?php echo esc_html( sprintf( /* translators: 1 code 2 price */ __( 'Use code %1$s at checkout and get it for %2$s', 'digimarket' ), $dm_best[0], dm_money_short( $dm_best[1] ) ) ); ?> <button type="button" class="dm-copy" data-copy="<?php echo esc_attr( $dm_best[0] ); ?>"><?php echo dm_icon( 'copy', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'Copy', 'digimarket' ); ?></button></p>
						</div>
						<?php
					endif;
				endif;
				?>

				<?php if ( $dm_checks ) : ?>
					<div class="dm-checklist">
						<h2><?php echo 'service' === $dm_type ? esc_html__( 'What’s included', 'digimarket' ) : esc_html__( 'What you get', 'digimarket' ); ?></h2>
						<ul><?php foreach ( $dm_checks as $dm_c ) : ?><li><?php echo dm_icon( 'check', 16 ); // phpcs:ignore ?><?php echo esc_html( $dm_c ); ?></li><?php endforeach; ?></ul>
					</div>
				<?php endif; ?>

				<div class="dm-pdp-actions" id="dm-buy">
					<?php if ( 'service' === $dm_type ) : ?>
						<?php $dm_turn = get_post_meta( $dm_pid, '_dm_turnaround', true ); ?>
						<?php if ( $dm_turn ) : ?><p class="dm-turn"><?php echo dm_icon( 'clock', 16 ); // phpcs:ignore ?> <?php echo esc_html( $dm_turn ); ?></p><?php endif; ?>
						<?php if ( $dm_mine && ! current_user_can( 'manage_options' ) ) : ?>
							<a class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" href="<?php echo esc_url( dm_url( 'dashboard', 'edit', $dm_pid ) ); ?>"><?php esc_html_e( 'Edit this service', 'digimarket' ); ?></a>
						<?php elseif ( $dm_wa ) : ?>
							<a class="dm-btn dm-btn-wa dm-btn-lg dm-btn-block" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp" data-ref="<?php echo (int) $dm_pid; ?>"><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Enquire on WhatsApp', 'digimarket' ); ?></a>
						<?php else : ?>
							<div class="dm-notice dm-notice-info"><?php esc_html_e( 'Contact us for pricing and details.', 'digimarket' ); ?></div>
						<?php endif; ?>
						<a class="dm-btn dm-btn-outline dm-btn-lg dm-btn-block" href="#enquiry"><?php esc_html_e( 'Send an enquiry form', 'digimarket' ); ?></a>
						<p class="dm-promise"><?php echo dm_icon( 'shield', 16 ); // phpcs:ignore ?> <?php echo esc_html( dm_store_opt( 'refund_promise' ) ); ?></p>
					<?php elseif ( 'affiliate' === $dm_type ) : ?>
						<?php $dm_pc = get_post_meta( $dm_pid, '_dm_aff_coupon', true ); ?>
						<?php if ( $dm_pc ) : ?><div class="dm-offers"><strong><?php echo dm_icon( 'tag', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Partner coupon', 'digimarket' ); ?></strong><p><code><?php echo esc_html( $dm_pc ); ?></code> <button type="button" class="dm-copy" data-copy="<?php echo esc_attr( $dm_pc ); ?>"><?php echo dm_icon( 'copy', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'Copy', 'digimarket' ); ?></button></p></div><?php endif; ?>
						<?php echo dm_affiliate_button( $dm_pid, 'dm-btn dm-btn-primary dm-btn-lg dm-btn-block' ); // phpcs:ignore ?>
						<p class="dm-disclose"><?php echo esc_html( dm_store_opt( 'affiliate_note' ) ); ?></p>
					<?php elseif ( $dm_owned ) : ?>
						<div class="dm-owned"><?php echo dm_icon( 'check', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'You own this product', 'digimarket' ); ?></div>
						<a class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php esc_html_e( 'Go to My Purchases', 'digimarket' ); ?></a>
					<?php elseif ( $dm_mine ) : ?>
						<a class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" href="<?php echo esc_url( current_user_can( 'edit_post', $dm_pid ) && current_user_can( 'manage_options' ) ? get_edit_post_link( $dm_pid ) : dm_url( 'dashboard', 'edit', $dm_pid ) ); ?>"><?php esc_html_e( 'Edit this product', 'digimarket' ); ?></a>
					<?php elseif ( $dm_can ) : ?>
						<form method="post" class="dm-buy-form">
							<?php dm_nonce_field( 'cart_add' ); ?>
							<input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>">
							<button class="dm-btn dm-btn-cart dm-btn-lg" type="submit" data-add-to-cart="<?php echo (int) $dm_pid; ?>"><?php echo dm_icon( 'cart', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Add to cart', 'digimarket' ); ?></button>
							<button class="dm-btn dm-btn-buy dm-btn-lg" name="buy_now" value="1" type="submit"><?php echo dm_icon( 'bolt', 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Buy now', 'digimarket' ); ?></button>
						</form>
						<?php $dm_demo = (string) get_post_meta( $dm_pid, '_dm_demo_url', true ); ?>
						<?php if ( $dm_demo ) : ?><a class="dm-btn dm-btn-outline dm-btn-block dm-demo-btn" href="<?php echo esc_url( $dm_demo ); ?>" target="_blank" rel="noopener nofollow"><?php echo dm_icon( 'external', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Live preview', 'digimarket' ); ?></a><?php endif; ?>
					<?php else : ?>
						<div class="dm-notice dm-notice-info"><?php echo esc_html( $dm_reason ); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( 'digital' === $dm_type ) : ?>
					<ul class="dm-facts">
						<li><?php echo dm_icon( 'bolt', 18 ); // phpcs:ignore ?><span><strong><?php echo esc_html( dm_delivery_label( $dm_delivery ) ); ?></strong><small><?php esc_html_e( 'Right after payment', 'digimarket' ); ?></small></span></li>
						<?php if ( 'file' === $dm_delivery && get_post_meta( $dm_pid, '_dm_file_size', true ) ) : ?>
							<li><?php echo dm_icon( 'download', 18 ); // phpcs:ignore ?><span><strong><?php echo esc_html( strtoupper( pathinfo( (string) get_post_meta( $dm_pid, '_dm_file_name', true ), PATHINFO_EXTENSION ) ) ); ?></strong><small><?php echo esc_html( size_format( (int) get_post_meta( $dm_pid, '_dm_file_size', true ) ) ); ?></small></span></li>
						<?php endif; ?>
						<?php $dm_days = (int) get_post_meta( $dm_pid, '_dm_access_days', true ); ?>
						<li><?php echo dm_icon( 'clock', 18 ); // phpcs:ignore ?><span><strong><?php echo $dm_days ? esc_html( sprintf( /* translators: %d */ _n( '%d day access', '%d days access', $dm_days, 'digimarket' ), $dm_days ) ) : esc_html__( 'Lifetime access', 'digimarket' ); ?></strong><small><?php esc_html_e( 'From My Purchases', 'digimarket' ); ?></small></span></li>
						<?php $dm_lim = (int) get_post_meta( $dm_pid, '_dm_download_limit', true ); ?>
						<?php if ( $dm_lim ) : ?><li><?php echo dm_icon( 'download', 18 ); // phpcs:ignore ?><span><strong><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d download', '%d downloads', $dm_lim, 'digimarket' ), $dm_lim ) ); ?></strong><small><?php esc_html_e( 'Per purchase', 'digimarket' ); ?></small></span></li><?php endif; ?>
						<li><?php echo dm_icon( 'shield', 18 ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'Secure checkout', 'digimarket' ); ?></strong><small><?php esc_html_e( 'UPI · Cards · Netbanking', 'digimarket' ); ?></small></span></li>
					</ul>
				<?php endif; ?>

				<div class="dm-pdp-tools">
					<?php if ( $dm_uid && 'digital' === $dm_type && ! $dm_mine ) : ?>
						<button class="dm-tool dm-wish-inline<?php echo dm_in_wishlist( $dm_pid ) ? ' is-on' : ''; ?>" data-product="<?php echo (int) $dm_pid; ?>" aria-pressed="<?php echo dm_in_wishlist( $dm_pid ) ? 'true' : 'false'; ?>"><?php echo dm_icon( 'heart', 18 ); // phpcs:ignore ?> <span><?php echo dm_in_wishlist( $dm_pid ) ? esc_html__( 'Saved', 'digimarket' ) : esc_html__( 'Save to wishlist', 'digimarket' ); ?></span></button>
					<?php endif; ?>
					<a class="dm-tool" href="<?php echo esc_url( $dm_share ); ?>" target="_blank" rel="noopener" data-track="share" data-ref="<?php echo (int) $dm_pid; ?>"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Share', 'digimarket' ); ?></a>
					<button type="button" class="dm-tool dm-copy" data-copy="<?php echo esc_attr( get_permalink() ); ?>"><?php echo dm_icon( 'copy', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Copy link', 'digimarket' ); ?></button>
				</div>

				<?php if ( dm_single_seller_mode() && dm_show_sold_by( $dm_seller ) ) : ?>
					<p class="dm-muted dm-small dm-soldby"><?php echo esc_html( sprintf( /* translators: %s shop */ __( 'Sold by %s · payment & delivery handled by this store', 'digimarket' ), dm_shop_name( $dm_seller ) ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! dm_single_seller_mode() ) : ?>
					<?php $dm_shop_r = dm_shop_rating( $dm_seller ); ?>
					<div class="dm-seller-card">
						<a href="<?php echo esc_url( dm_store_url( $dm_seller ) ); ?>" class="dm-seller-link">
							<?php echo dm_shop_logo( $dm_seller, 'dm-avatar dm-avatar-lg' ); // phpcs:ignore ?>
							<span>
								<strong><?php echo esc_html( dm_shop_name( $dm_seller ) ); ?></strong>
								<small class="dm-muted"><?php echo $dm_shop_r['count'] ? '★ ' . esc_html( number_format_i18n( $dm_shop_r['avg'], 1 ) ) . ' · ' : ''; ?><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d product', '%d products', dm_seller_product_count( $dm_seller ), 'digimarket' ), dm_seller_product_count( $dm_seller ) ) ); ?></small>
							</span>
						</a>
						<a class="dm-btn dm-btn-ghost" href="<?php echo esc_url( dm_store_url( $dm_seller ) ); ?>"><?php esc_html_e( 'Visit shop', 'digimarket' ); ?></a>
					</div>
				<?php endif; ?>

				<?php if ( $dm_tags && ! is_wp_error( $dm_tags ) ) : ?>
					<div class="dm-chips">
						<?php foreach ( $dm_tags as $dm_tag ) : ?><a class="dm-chip-link" href="<?php echo esc_url( get_term_link( $dm_tag ) ); ?>">#<?php echo esc_html( $dm_tag->name ); ?></a><?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php
		$dm_pk    = array_filter( (array) get_post_meta( $dm_pid, '_dm_packages', true ) );
		$dm_pf    = 'service' === $dm_type ? dm_get_portfolio( $dm_pid, 6 ) : array();
		$dm_faq   = 'service' === $dm_type ? dm_service_faq( $dm_pid ) : dm_parse_pairs( get_post_meta( $dm_pid, '_dm_faq', true ) );
		$dm_testi = 'service' === $dm_type ? dm_get_testimonials( $dm_pid, 3 ) : array();
		$dm_tabs  = array( 'about' => __( 'Details', 'digimarket' ) );
		if ( 'service' === $dm_type && $dm_pk ) {
			$dm_tabs['packages'] = __( 'Packages', 'digimarket' );
		}
		if ( $dm_pf ) {
			$dm_tabs['work'] = __( 'Samples', 'digimarket' );
		}
		if ( $dm_faq ) {
			$dm_tabs['faq'] = __( 'FAQ', 'digimarket' );
		}
		$dm_tabs['reviews'] = __( 'Reviews', 'digimarket' );
		if ( 'service' === $dm_type ) {
			$dm_tabs['enquiry'] = __( 'Enquire', 'digimarket' );
		}
		?>
		<nav class="dm-ptabs" aria-label="<?php esc_attr_e( 'On this page', 'digimarket' ); ?>">
			<?php foreach ( $dm_tabs as $dm_k => $dm_l ) : ?><a href="#<?php echo esc_attr( $dm_k ); ?>"><?php echo esc_html( $dm_l ); ?></a><?php endforeach; ?>
		</nav>

		<div class="dm-pdp-body">
			<?php if ( 'service' === $dm_type ) : ?>
				<ul class="dm-trustbar">
					<?php if ( dm_store_opt( 'trust_projects' ) ) : ?><li><b><?php echo esc_html( dm_store_opt( 'trust_projects' ) ); ?></b><span><?php esc_html_e( 'projects delivered', 'digimarket' ); ?></span></li><?php endif; ?>
					<?php if ( $dm_rcount ) : ?><li><b><?php echo esc_html( number_format_i18n( (float) get_post_meta( $dm_pid, '_dm_rating_avg', true ), 1 ) ); ?> ★</b><span><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d client review', '%d client reviews', $dm_rcount, 'digimarket' ), $dm_rcount ) ); ?></span></li><?php endif; ?>
					<li><b><?php echo dm_icon( 'chat', 18 ); // phpcs:ignore ?></b><span><?php echo esc_html( dm_store_opt( 'trust_reply' ) ); ?></span></li>
					<li><b><?php echo dm_icon( 'refund', 18 ); // phpcs:ignore ?></b><span><?php esc_html_e( 'Full refund if we can’t deliver', 'digimarket' ); ?></span></li>
				</ul>
			<?php endif; ?>

			<section class="dm-psec dm-prose" id="about">
				<h2><?php echo 'service' === $dm_type ? esc_html__( 'About this service', 'digimarket' ) : esc_html__( 'Product details', 'digimarket' ); ?></h2>
				<?php if ( has_excerpt() ) : ?><p class="dm-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
				<?php the_content(); ?>
				<?php
				if ( 'affiliate' === $dm_type ) :
					$dm_pros = dm_lines( get_post_meta( $dm_pid, '_dm_aff_pros', true ) );
					$dm_cons = dm_lines( get_post_meta( $dm_pid, '_dm_aff_cons', true ) );
					$dm_bf   = get_post_meta( $dm_pid, '_dm_aff_best_for', true );
					if ( $dm_bf ) :
						?>
						<p><strong><?php esc_html_e( 'Best for:', 'digimarket' ); ?></strong> <?php echo esc_html( $dm_bf ); ?></p>
					<?php endif; ?>
					<?php if ( $dm_pros || $dm_cons ) : ?>
						<div class="dm-proscons">
							<?php if ( $dm_pros ) : ?><div class="is-pro"><h3><?php esc_html_e( 'Pros', 'digimarket' ); ?></h3><ul><?php foreach ( $dm_pros as $dm_x ) : ?><li><?php echo dm_icon( 'check', 16 ); // phpcs:ignore ?><?php echo esc_html( $dm_x ); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
							<?php if ( $dm_cons ) : ?><div class="is-con"><h3><?php esc_html_e( 'Cons', 'digimarket' ); ?></h3><ul><?php foreach ( $dm_cons as $dm_x ) : ?><li><?php echo dm_icon( 'close', 16 ); // phpcs:ignore ?><?php echo esc_html( $dm_x ); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
						</div>
					<?php endif; ?>
					<p class="dm-disclose"><?php echo esc_html( dm_store_opt( 'affiliate_note' ) ); ?> <a href="<?php echo esc_url( dm_legal_url( 'affiliate' ) ); ?>"><?php esc_html_e( 'Learn more', 'digimarket' ); ?></a></p>
				<?php endif; ?>
				<?php if ( 'digital' === $dm_type ) : ?>
					<?php $dm_pol = dm_single_seller_mode() ? '' : get_user_meta( $dm_seller, 'dm_policy_refund', true ); ?>
					<h3><?php esc_html_e( 'Refunds', 'digimarket' ); ?></h3>
					<p><?php echo $dm_pol ? nl2br( esc_html( $dm_pol ) ) : wp_kses_post( sprintf( /* translators: %s url */ __( 'Covered by our <a href="%s">Refund Policy</a>.', 'digimarket' ), esc_url( dm_legal_url( 'refund' ) ) ) ); ?></p>
				<?php endif; ?>
			</section>

			<?php
			if ( 'service' === $dm_type ) {
				dm_render_packages( $dm_pid );
				echo '<section class="dm-psec" id="process"><h2>' . esc_html__( 'How it works', 'digimarket' ) . '</h2>';
				dm_render_process_steps();
				echo '</section>';
				if ( $dm_pf ) {
					echo '<section class="dm-psec" id="work"><h2>' . esc_html__( 'Sample websites you can get', 'digimarket' ) . '</h2><div class="dm-pf-grid">';
					foreach ( $dm_pf as $dm_p ) {
						dm_portfolio_card( $dm_p );
					}
					echo '</div></section>';
				}
				if ( $dm_testi ) {
					echo '<section class="dm-psec"><h2>' . esc_html__( 'What clients say', 'digimarket' ) . '</h2><div class="dm-testi-grid">';
					foreach ( $dm_testi as $dm_t ) {
						dm_testimonial_card( $dm_t );
					}
					echo '</div></section>';
				}
				if ( get_post_meta( $dm_pid, '_dm_show_partners', true ) ) {
					dm_render_partner_offers( 3 );
				}
			}
			dm_render_faq( $dm_faq );

			$dm_form = '';
			if ( $dm_owned ) {
				$dm_review = dm_user_review( $dm_uid, $dm_pid );
				ob_start();
				?>
				<form method="post" class="dm-review-form">
					<?php dm_nonce_field( 'submit_review' ); ?>
					<input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>">
					<fieldset class="dm-star-input">
						<legend><?php echo $dm_review ? esc_html__( 'Update your review', 'digimarket' ) : esc_html__( 'Rate this product', 'digimarket' ); ?></legend>
						<?php for ( $dm_s = 5; $dm_s >= 1; $dm_s-- ) : ?>
							<input type="radio" id="dm-star-<?php echo (int) $dm_s; ?>" name="rating" value="<?php echo (int) $dm_s; ?>" <?php checked( $dm_review ? (int) $dm_review->rating : 5, $dm_s ); ?>><label for="dm-star-<?php echo (int) $dm_s; ?>" title="<?php echo (int) $dm_s; ?>">★</label>
						<?php endfor; ?>
					</fieldset>
					<textarea name="comment" rows="3" placeholder="<?php esc_attr_e( 'What did you like? How did it help you?', 'digimarket' ); ?>"><?php echo $dm_review ? esc_textarea( $dm_review->comment ) : ''; ?></textarea>
					<button class="dm-btn dm-btn-primary"><?php echo $dm_review ? esc_html__( 'Update review', 'digimarket' ) : esc_html__( 'Post review', 'digimarket' ); ?></button>
				</form>
				<?php
				$dm_form = ob_get_clean();
			} elseif ( $dm_uid && 'digital' === $dm_type && ! $dm_mine ) {
				$dm_form = '<p class="dm-muted">' . esc_html__( 'Only verified buyers can review this product.', 'digimarket' ) . '</p>';
			}
			dm_render_reviews( $dm_pid, $dm_form );

			if ( 'service' === $dm_type ) :
				?>
				<section class="dm-psec dm-enquiry-sec" id="enquiry">
					<div>
						<h2><?php esc_html_e( 'Tell us what you need', 'digimarket' ); ?></h2>
						<p class="dm-muted"><?php esc_html_e( 'Prefer a form? Send your details and we will call or WhatsApp you. No payment until we agree on everything.', 'digimarket' ); ?></p>
						<?php if ( $dm_wa ) : ?><a class="dm-btn dm-btn-wa" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp" data-ref="<?php echo (int) $dm_pid; ?>"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Or chat on WhatsApp now', 'digimarket' ); ?></a><?php endif; ?>
					</div>
					<?php dm_render_enquiry_form( $dm_pid ); ?>
				</section>
			<?php endif; ?>

			<?php
			$dm_rel_posts = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $dm_pid, '_dm_related_posts', true ) ) ) );
			if ( $dm_rel_posts ) :
				$dm_rel_q = get_posts( array( 'post__in' => $dm_rel_posts, 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3 ) );
				if ( $dm_rel_q ) :
					?>
					<section class="dm-psec"><h2><?php esc_html_e( 'Helpful reads', 'digimarket' ); ?></h2><div class="dm-alist">
						<?php foreach ( $dm_rel_q as $dm_a ) { dm_article_row( $dm_a ); } ?>
					</div></section>
					<?php
				endif;
			endif;
			?>
		</div>

		<?php
		$dm_rel_terms = $dm_cats && ! is_wp_error( $dm_cats ) ? wp_list_pluck( $dm_cats, 'term_id' ) : array();
		$dm_related   = dm_product_ids(
			array(
				'posts_per_page' => 10,
				'post__not_in'   => array( $dm_pid ),
				'tax_query'      => $dm_rel_terms ? array( array( 'taxonomy' => 'dm_category', 'terms' => $dm_rel_terms ) ) : array(),
				'meta_key'       => '_dm_sales',
				'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
			)
		);
		if ( $dm_related ) {
			echo '<div class="dm-pdp-related">';
			dm_render_row( $dm_related, __( 'You may also like', 'digimarket' ) );
			echo '</div>';
		}
		?>

		<?php if ( $dm_uid && ! $dm_mine && ! dm_single_seller_mode() ) : ?>
			<details class="dm-report">
				<summary><?php esc_html_e( 'Report this product', 'digimarket' ); ?></summary>
				<form method="post">
					<?php dm_nonce_field( 'report_product' ); ?>
					<input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>">
					<textarea name="reason" rows="3" required placeholder="<?php esc_attr_e( 'Copyright infringement, prohibited content, misleading listing…', 'digimarket' ); ?>"></textarea>
					<button class="dm-btn dm-btn-ghost"><?php esc_html_e( 'Send report', 'digimarket' ); ?></button>
				</form>
			</details>
		<?php endif; ?>
	</div>

	<?php if ( ! $dm_owned && ! $dm_mine ) : ?>
	<div class="dm-buybar">
		<div class="dm-buybar-price"><?php echo wp_kses_post( dm_card_price_html( $dm_pid ) ); ?></div>
		<?php if ( 'service' === $dm_type && $dm_wa ) : ?>
			<a class="dm-btn dm-btn-wa" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp" data-ref="<?php echo (int) $dm_pid; ?>"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Enquire', 'digimarket' ); ?></a>
		<?php elseif ( 'affiliate' === $dm_type ) : ?>
			<?php echo dm_affiliate_button( $dm_pid, 'dm-btn dm-btn-primary' ); // phpcs:ignore ?>
		<?php elseif ( 'digital' === $dm_type && $dm_can ) : ?>
			<form method="post" class="dm-buybar-form"><?php dm_nonce_field( 'cart_add' ); ?><input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>"><button class="dm-btn dm-btn-cart" type="submit" data-add-to-cart="<?php echo (int) $dm_pid; ?>"><?php esc_html_e( 'Add to cart', 'digimarket' ); ?></button><button class="dm-btn dm-btn-buy" name="buy_now" value="1" type="submit"><?php esc_html_e( 'Buy now', 'digimarket' ); ?></button></form>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
