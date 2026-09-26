<?php
/**
 * Product detail page.
 *
 * @package DigiMarket
 */

get_header();

while ( have_posts() ) :
	the_post();
	$dm_pid      = get_the_ID();
	$dm_seller   = (int) get_the_author_meta( 'ID' );
	$dm_uid      = get_current_user_id();
	$dm_gallery  = array_filter( array_map( 'absint', (array) get_post_meta( $dm_pid, '_dm_gallery', true ) ) );
	$dm_thumb    = get_post_thumbnail_id( $dm_pid );
	$dm_images   = array_values( array_unique( array_filter( array_merge( array( $dm_thumb ), $dm_gallery ) ) ) );
	$dm_owned    = dm_user_purchase( $dm_uid, $dm_pid );
	$dm_own      = $dm_uid && $dm_uid === $dm_seller;
	$dm_delivery = get_post_meta( $dm_pid, '_dm_delivery', true );
	$dm_cats     = get_the_terms( $dm_pid, 'dm_category' );
	$dm_tags     = get_the_terms( $dm_pid, 'dm_tag' );
	$dm_rating   = (float) get_post_meta( $dm_pid, '_dm_rating_avg', true );
	$dm_rcount   = (int) get_post_meta( $dm_pid, '_dm_rating_count', true );
	$dm_shop_r   = dm_shop_rating( $dm_seller );
	list( $dm_can, $dm_reason ) = dm_can_purchase( $dm_pid );
	?>
	<div class="dm-container dm-page">
		<nav class="dm-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'digimarket' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'digimarket' ); ?></a> /
			<?php if ( $dm_cats && ! is_wp_error( $dm_cats ) ) : ?><a href="<?php echo esc_url( get_term_link( $dm_cats[0] ) ); ?>"><?php echo esc_html( $dm_cats[0]->name ); ?></a> /<?php endif; ?>
			<span><?php the_title(); ?></span>
		</nav>

		<?php if ( 'publish' !== get_post_status() ) : ?>
			<div class="dm-notice dm-notice-warning"><?php echo esc_html( sprintf( /* translators: %s status */ __( 'Preview — this product is %s and not visible to buyers.', 'digimarket' ), wp_strip_all_tags( dm_status_badge( get_post_status() ) ) ) ); ?></div>
		<?php endif; ?>

		<div class="dm-product">
			<div class="dm-product-gallery" data-gallery>
				<div class="dm-gallery-main">
					<?php if ( $dm_images ) : ?>
						<?php echo wp_get_attachment_image( $dm_images[0], 'dm-gallery', false, array( 'class' => 'dm-gallery-img', 'data-main' => '1', 'fetchpriority' => 'high' ) ); ?>
					<?php else : ?>
						<?php echo dm_product_thumb( $dm_pid ); // phpcs:ignore ?>
					<?php endif; ?>
				</div>
				<?php if ( count( $dm_images ) > 1 ) : ?>
					<div class="dm-gallery-thumbs">
						<?php foreach ( $dm_images as $dm_n => $dm_img ) : ?>
							<button class="<?php echo 0 === $dm_n ? 'is-active' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $dm_img, 'dm-gallery' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d */ __( 'Image %d', 'digimarket' ), $dm_n + 1 ) ); ?>">
								<?php echo wp_get_attachment_image( $dm_img, 'thumbnail' ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="dm-product-info">
				<?php if ( $dm_cats && ! is_wp_error( $dm_cats ) ) : ?><a class="dm-eyebrow" href="<?php echo esc_url( get_term_link( $dm_cats[0] ) ); ?>"><?php echo esc_html( $dm_cats[0]->name ); ?></a><?php endif; ?>
				<h1 class="dm-product-title"><?php the_title(); ?></h1>
				<div class="dm-product-meta">
					<?php if ( $dm_rcount ) : ?><a href="#reviews"><?php echo dm_stars( $dm_rating, $dm_rcount ); // phpcs:ignore ?></a><?php else : ?><span class="dm-muted"><?php esc_html_e( 'No reviews yet', 'digimarket' ); ?></span><?php endif; ?>
					<span class="dm-muted">· <?php echo esc_html( sprintf( /* translators: %d */ _n( '%d sale', '%d sales', (int) get_post_meta( $dm_pid, '_dm_sales', true ), 'digimarket' ), (int) get_post_meta( $dm_pid, '_dm_sales', true ) ) ); ?></span>
				</div>
				<?php if ( has_excerpt() ) : ?><p class="dm-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>

				<div class="dm-buy-box">
					<div class="dm-buy-price"><?php echo wp_kses_post( dm_price_html( $dm_pid ) ); ?></div>
					<ul class="dm-buy-facts">
						<li>⚡ <?php echo esc_html( dm_delivery_label( $dm_delivery ) ); ?></li>
						<?php if ( 'file' === $dm_delivery && get_post_meta( $dm_pid, '_dm_file_size', true ) ) : ?>
							<li>📦 <?php echo esc_html( strtoupper( pathinfo( (string) get_post_meta( $dm_pid, '_dm_file_name', true ), PATHINFO_EXTENSION ) ) . ' · ' . size_format( (int) get_post_meta( $dm_pid, '_dm_file_size', true ) ) ); ?></li>
						<?php endif; ?>
						<?php $dm_days = (int) get_post_meta( $dm_pid, '_dm_access_days', true ); ?>
						<li>♾️ <?php echo $dm_days ? esc_html( sprintf( /* translators: %d */ _n( '%d day access', '%d days access', $dm_days, 'digimarket' ), $dm_days ) ) : esc_html__( 'Lifetime access', 'digimarket' ); ?></li>
						<?php $dm_lim = (int) get_post_meta( $dm_pid, '_dm_download_limit', true ); ?>
						<?php if ( $dm_lim ) : ?><li>⬇️ <?php echo esc_html( sprintf( /* translators: %d */ _n( '%d download', '%d downloads', $dm_lim, 'digimarket' ), $dm_lim ) ); ?></li><?php endif; ?>
					</ul>
					<?php if ( $dm_owned ) : ?>
						<div class="dm-owned">✓ <?php esc_html_e( 'You own this product', 'digimarket' ); ?></div>
						<a class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php esc_html_e( 'Go to My Purchases', 'digimarket' ); ?></a>
					<?php elseif ( $dm_own ) : ?>
						<a class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" href="<?php echo esc_url( dm_url( 'dashboard', 'edit', $dm_pid ) ); ?>"><?php esc_html_e( 'Edit this product', 'digimarket' ); ?></a>
					<?php elseif ( $dm_can ) : ?>
						<form method="post" class="dm-buy-form">
							<?php dm_nonce_field( 'cart_add' ); ?>
							<input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>">
							<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" name="buy_now" value="1" type="submit"><?php esc_html_e( 'Buy now', 'digimarket' ); ?></button>
							<button class="dm-btn dm-btn-outline dm-btn-lg dm-btn-block" type="submit" data-add-to-cart="<?php echo (int) $dm_pid; ?>"><?php esc_html_e( 'Add to cart', 'digimarket' ); ?></button>
						</form>
					<?php else : ?>
						<div class="dm-notice dm-notice-info"><?php echo esc_html( $dm_reason ); ?></div>
					<?php endif; ?>
					<?php if ( $dm_uid && ! $dm_own ) : ?>
						<button class="dm-btn dm-btn-ghost dm-btn-block dm-wish-inline<?php echo dm_in_wishlist( $dm_pid ) ? ' is-on' : ''; ?>" data-product="<?php echo (int) $dm_pid; ?>" aria-pressed="<?php echo dm_in_wishlist( $dm_pid ) ? 'true' : 'false'; ?>">♥ <span><?php echo dm_in_wishlist( $dm_pid ) ? esc_html__( 'Saved', 'digimarket' ) : esc_html__( 'Save to wishlist', 'digimarket' ); ?></span></button>
					<?php endif; ?>
					<p class="dm-secure">🔒 <?php esc_html_e( 'Secure checkout · UPI, cards, netbanking, wallets', 'digimarket' ); ?></p>
				</div>

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

				<?php if ( $dm_tags && ! is_wp_error( $dm_tags ) ) : ?>
					<div class="dm-chips">
						<?php foreach ( $dm_tags as $dm_tag ) : ?><a class="dm-chip-link" href="<?php echo esc_url( get_term_link( $dm_tag ) ); ?>">#<?php echo esc_html( $dm_tag->name ); ?></a><?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="dm-product-body">
			<article class="dm-prose dm-card dm-pad">
				<h2><?php esc_html_e( 'Description', 'digimarket' ); ?></h2>
				<?php the_content(); ?>
				<?php $dm_pol = get_user_meta( $dm_seller, 'dm_policy_delivery', true ); ?>
				<?php if ( $dm_pol ) : ?><h3><?php esc_html_e( 'Delivery notes', 'digimarket' ); ?></h3><p><?php echo nl2br( esc_html( $dm_pol ) ); ?></p><?php endif; ?>
				<?php $dm_pol = get_user_meta( $dm_seller, 'dm_policy_refund', true ); ?>
				<h3><?php esc_html_e( 'Refunds', 'digimarket' ); ?></h3>
				<p><?php echo $dm_pol ? nl2br( esc_html( $dm_pol ) ) : wp_kses_post( sprintf( /* translators: %s url */ __( 'Covered by the marketplace <a href="%s">Refund Policy</a>.', 'digimarket' ), esc_url( dm_legal_url( 'refund' ) ) ) ); ?></p>
			</article>

			<section id="reviews" class="dm-card dm-pad dm-reviews">
				<h2><?php esc_html_e( 'Reviews', 'digimarket' ); ?> <?php if ( $dm_rcount ) { echo dm_stars( $dm_rating, $dm_rcount ); } // phpcs:ignore ?></h2>
				<?php
				if ( $dm_owned ) :
					$dm_mine = dm_user_review( $dm_uid, $dm_pid );
					?>
					<form method="post" class="dm-review-form">
						<?php dm_nonce_field( 'submit_review' ); ?>
						<input type="hidden" name="product_id" value="<?php echo (int) $dm_pid; ?>">
						<fieldset class="dm-star-input">
							<legend><?php echo $dm_mine ? esc_html__( 'Update your review', 'digimarket' ) : esc_html__( 'Rate this product', 'digimarket' ); ?></legend>
							<?php for ( $dm_s = 5; $dm_s >= 1; $dm_s-- ) : ?>
								<input type="radio" id="dm-star-<?php echo (int) $dm_s; ?>" name="rating" value="<?php echo (int) $dm_s; ?>" <?php checked( $dm_mine ? (int) $dm_mine->rating : 5, $dm_s ); ?>><label for="dm-star-<?php echo (int) $dm_s; ?>" title="<?php echo (int) $dm_s; ?>">★</label>
							<?php endfor; ?>
						</fieldset>
						<textarea name="comment" rows="3" placeholder="<?php esc_attr_e( 'What did you think?', 'digimarket' ); ?>"><?php echo $dm_mine ? esc_textarea( $dm_mine->comment ) : ''; ?></textarea>
						<button class="dm-btn dm-btn-primary"><?php echo $dm_mine ? esc_html__( 'Update review', 'digimarket' ) : esc_html__( 'Post review', 'digimarket' ); ?></button>
					</form>
				<?php elseif ( $dm_uid && ! $dm_own ) : ?>
					<p class="dm-muted"><?php esc_html_e( 'Only verified buyers can review this product.', 'digimarket' ); ?></p>
				<?php endif; ?>

				<?php $dm_reviews = dm_get_reviews( $dm_pid ); ?>
				<?php if ( $dm_reviews ) : ?>
					<ul class="dm-review-list">
						<?php foreach ( $dm_reviews as $dm_r ) : ?>
							<?php $dm_ru = get_userdata( $dm_r->buyer_id ); ?>
							<li class="dm-review">
								<div class="dm-review-head">
									<?php echo get_avatar( $dm_r->buyer_id, 36 ); ?>
									<div><strong><?php echo esc_html( $dm_ru ? $dm_ru->display_name : __( 'Buyer', 'digimarket' ) ); ?></strong> <span class="dm-badge dm-badge-success"><?php esc_html_e( 'Verified buyer', 'digimarket' ); ?></span><br><?php echo dm_stars( $dm_r->rating ); // phpcs:ignore ?> <small class="dm-muted"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $dm_r->created_at ) ); ?></small></div>
								</div>
								<?php if ( $dm_r->comment ) : ?><p><?php echo nl2br( esc_html( $dm_r->comment ) ); ?></p><?php endif; ?>
								<?php if ( $dm_r->seller_reply ) : ?>
									<div class="dm-reply"><strong><?php echo esc_html( dm_shop_name( $dm_seller ) ); ?>:</strong> <?php echo nl2br( esc_html( $dm_r->seller_reply ) ); ?></div>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! $dm_owned ) : ?>
					<p class="dm-muted"><?php esc_html_e( 'No reviews yet.', 'digimarket' ); ?></p>
				<?php endif; ?>
			</section>
		</div>

		<?php
		$dm_rel_terms = $dm_cats && ! is_wp_error( $dm_cats ) ? wp_list_pluck( $dm_cats, 'term_id' ) : array();
		$dm_related   = dm_query_products(
			array(
				'posts_per_page' => 8,
				'post__not_in'   => array( $dm_pid ),
				'tax_query'      => $dm_rel_terms ? array( array( 'taxonomy' => 'dm_category', 'terms' => $dm_rel_terms ) ) : array(),
				'orderby'        => 'rand',
			)
		);
		if ( $dm_related->have_posts() ) :
			?>
			<section class="dm-section">
				<div class="dm-section-head"><h2><?php esc_html_e( 'You may also like', 'digimarket' ); ?></h2></div>
				<div class="dm-carousel dm-carousel-products" tabindex="0">
					<?php while ( $dm_related->have_posts() ) : $dm_related->the_post(); get_template_part( 'template-parts/product-card' ); endwhile; wp_reset_postdata(); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $dm_uid && ! $dm_own ) : ?>
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
	<?php
endwhile;

get_footer();
