<?php
/**
 * Marketplace homepage.
 *
 * @package DigiMarket
 */

get_header();

$dm_hero_img = (int) get_theme_mod( 'dm_hero_image', 0 );
$dm_featured = dm_query_products( array( 'posts_per_page' => 5, 'meta_key' => '_dm_featured', 'meta_value' => 1 ) );
if ( ! $dm_featured->have_posts() ) {
	$dm_featured = dm_query_products( array( 'posts_per_page' => 5, 'meta_key' => '_dm_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ) );
}
?>
<section class="dm-hero"<?php echo $dm_hero_img ? ' style="--dm-hero-img:url(' . esc_url( wp_get_attachment_image_url( $dm_hero_img, 'full' ) ) . ')"' : ''; ?>>
	<div class="dm-container dm-hero-inner">
		<div class="dm-hero-copy">
			<span class="dm-pill"><?php esc_html_e( 'Instant delivery · Secure checkout', 'digimarket' ); ?></span>
			<h1><?php echo esc_html( get_theme_mod( 'dm_hero_title', __( 'Digital products from independent creators', 'digimarket' ) ) ); ?></h1>
			<p class="dm-lead"><?php echo esc_html( get_theme_mod( 'dm_hero_subtitle', __( 'Ebooks, courses, templates, software and more — delivered instantly, paid securely.', 'digimarket' ) ) ); ?></p>
			<form class="dm-hero-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="hidden" name="post_type" value="dm_product">
				<label class="screen-reader-text" for="dm-hero-s"><?php esc_html_e( 'Search', 'digimarket' ); ?></label>
				<input id="dm-hero-s" type="search" name="s" placeholder="<?php esc_attr_e( 'Try “Notion template”, “Lightroom presets”…', 'digimarket' ); ?>">
				<button class="dm-btn dm-btn-primary" type="submit"><?php esc_html_e( 'Search', 'digimarket' ); ?></button>
			</form>
			<div class="dm-hero-tags">
				<?php foreach ( array_slice( dm_categories( array( 'parent' => 0 ) ), 0, 5 ) as $dm_cat ) : ?>
					<a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( $dm_featured->have_posts() ) : ?>
			<div class="dm-rotator" data-rotator>
				<?php $dm_i = 0; ?>
				<?php while ( $dm_featured->have_posts() ) : $dm_featured->the_post(); ?>
					<a class="dm-rotator-slide<?php echo 0 === $dm_i ? ' is-active' : ''; ?>" href="<?php the_permalink(); ?>" aria-hidden="<?php echo 0 === $dm_i ? 'false' : 'true'; ?>">
						<?php echo dm_product_thumb( get_the_ID(), 'dm-gallery' ); // phpcs:ignore ?>
						<span class="dm-rotator-caption">
							<small><?php esc_html_e( 'Featured', 'digimarket' ); ?> · <?php echo esc_html( dm_shop_name( get_the_author_meta( 'ID' ) ) ); ?></small>
							<strong><?php the_title(); ?></strong>
							<?php echo wp_kses_post( dm_price_html( get_the_ID() ) ); ?>
						</span>
					</a>
					<?php $dm_i++; ?>
				<?php endwhile; wp_reset_postdata(); ?>
				<?php if ( $dm_i > 1 ) : ?>
					<div class="dm-rotator-dots" role="tablist">
						<?php for ( $dm_d = 0; $dm_d < $dm_i; $dm_d++ ) : ?>
							<button role="tab" aria-label="<?php echo esc_attr( sprintf( /* translators: %d */ __( 'Slide %d', 'digimarket' ), $dm_d + 1 ) ); ?>" class="<?php echo 0 === $dm_d ? 'is-active' : ''; ?>"></button>
						<?php endfor; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="dm-hero-art" aria-hidden="true">
				<div class="dm-art-card a">📘 <span>Ebook</span></div>
				<div class="dm-art-card b">🎨 <span>Templates</span></div>
				<div class="dm-art-card c">🎵 <span>Music</span></div>
				<div class="dm-art-card d">💻 <span>Software</span></div>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( get_theme_mod( 'dm_show_trust', 1 ) ) : ?>
<section class="dm-trust">
	<div class="dm-container dm-trust-grid">
		<div><strong>🔒 <?php esc_html_e( 'Secure payments', 'digimarket' ); ?></strong><span><?php esc_html_e( 'UPI, cards, netbanking & wallets via Razorpay', 'digimarket' ); ?></span></div>
		<div><strong>⚡ <?php esc_html_e( 'Instant delivery', 'digimarket' ); ?></strong><span><?php esc_html_e( 'Download right after payment', 'digimarket' ); ?></span></div>
		<div><strong>✅ <?php esc_html_e( 'Verified reviews', 'digimarket' ); ?></strong><span><?php esc_html_e( 'Only real buyers can review', 'digimarket' ); ?></span></div>
		<div><strong>↩️ <?php esc_html_e( 'Clear refunds', 'digimarket' ); ?></strong><span><a href="<?php echo esc_url( dm_legal_url( 'refund' ) ); ?>"><?php esc_html_e( 'Read our refund policy', 'digimarket' ); ?></a></span></div>
	</div>
</section>
<?php endif; ?>

<section class="dm-section">
	<div class="dm-container">
		<div class="dm-section-head"><h2><?php esc_html_e( 'Browse by category', 'digimarket' ); ?></h2><a href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'View all →', 'digimarket' ); ?></a></div>
		<div class="dm-cat-grid">
			<?php
			$dm_icons = array( 'ebooks' => '📘', 'courses' => '🎓', 'templates' => '🧩', 'software' => '💻', 'music' => '🎵', 'graphics' => '🎨', 'presets' => '🎛️', 'fonts' => '🔤', 'design-assets' => '✏️', 'plugins-code' => '🧑‍💻' );
			foreach ( dm_categories( array( 'parent' => 0 ) ) as $dm_cat ) :
				?>
				<a class="dm-cat-tile" href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>">
					<span class="dm-cat-icon" aria-hidden="true"><?php echo esc_html( $dm_icons[ $dm_cat->slug ] ?? '✦' ); ?></span>
					<strong><?php echo esc_html( $dm_cat->name ); ?></strong>
					<small><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d product', '%d products', $dm_cat->count, 'digimarket' ), $dm_cat->count ) ); ?></small>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
$dm_trending = dm_query_products( array( 'posts_per_page' => 8, 'meta_key' => '_dm_sales', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) ) );
$dm_new      = dm_query_products( array( 'posts_per_page' => 8 ) );
$dm_has_any  = $dm_new->have_posts();
?>

<?php if ( ! $dm_has_any ) : ?>
	<section class="dm-section"><div class="dm-container">
		<?php dm_empty_state( __( 'The marketplace is just getting started', 'digimarket' ), __( 'Be one of the first creators to open a shop here.', 'digimarket' ), dm_url( 'sell' ), __( 'Open your shop', 'digimarket' ) ); ?>
	</div></section>
<?php endif; ?>

<?php if ( $dm_has_any && get_theme_mod( 'dm_show_trending', 1 ) ) : ?>
<section class="dm-section">
	<div class="dm-container">
		<div class="dm-section-head"><h2><?php esc_html_e( 'Trending products', 'digimarket' ); ?></h2><a href="<?php echo esc_url( dm_products_url( array( 'sort' => 'bestselling' ) ) ); ?>"><?php esc_html_e( 'See more →', 'digimarket' ); ?></a></div>
		<div class="dm-grid">
			<?php while ( $dm_trending->have_posts() ) : $dm_trending->the_post(); get_template_part( 'template-parts/product-card' ); endwhile; wp_reset_postdata(); ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
$dm_shop_ids = array_map( 'intval', (array) dm_opt( 'featured_shops', array() ) );
if ( ! $dm_shop_ids ) {
	global $wpdb;
	$dm_shop_ids = array_map( 'intval', $wpdb->get_col( 'SELECT seller_id FROM ' . dm_table( 'order_items' ) . " WHERE item_status = 'paid' GROUP BY seller_id ORDER BY SUM(price_at_purchase) DESC LIMIT 8" ) ); // phpcs:ignore
}
if ( ! $dm_shop_ids ) {
	$dm_shop_ids = get_users( array( 'meta_key' => 'dm_seller_status', 'meta_value' => 'active', 'number' => 8, 'fields' => 'ID' ) );
}
$dm_shop_ids = array_filter( $dm_shop_ids, 'dm_is_active_seller' );
if ( $dm_shop_ids && get_theme_mod( 'dm_show_shops', 1 ) ) :
	?>
<section class="dm-section dm-section-alt">
	<div class="dm-container">
		<div class="dm-section-head"><h2><?php esc_html_e( 'Featured shops', 'digimarket' ); ?></h2><a href="<?php echo esc_url( dm_url( 'shops' ) ); ?>"><?php esc_html_e( 'All shops →', 'digimarket' ); ?></a></div>
		<div class="dm-carousel" tabindex="0">
			<?php foreach ( $dm_shop_ids as $dm_sid ) { get_template_part( 'template-parts/shop-card', null, array( 'seller_id' => $dm_sid ) ); } ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $dm_has_any && get_theme_mod( 'dm_show_new', 1 ) ) : ?>
<section class="dm-section">
	<div class="dm-container">
		<div class="dm-section-head"><h2><?php esc_html_e( 'New arrivals', 'digimarket' ); ?></h2><a href="<?php echo esc_url( dm_products_url( array( 'sort' => 'newest' ) ) ); ?>"><?php esc_html_e( 'See more →', 'digimarket' ); ?></a></div>
		<div class="dm-grid">
			<?php while ( $dm_new->have_posts() ) : $dm_new->the_post(); get_template_part( 'template-parts/product-card' ); endwhile; wp_reset_postdata(); ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
if ( 'page' === get_option( 'show_on_front' ) && have_posts() ) :
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) :
			?>
			<section class="dm-section"><div class="dm-container dm-prose"><?php the_content(); ?></div></section>
			<?php
		endif;
	endwhile;
endif;

get_footer();
