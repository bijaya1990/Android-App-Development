<?php
/**
 * /wordpress-themes/ — every theme with rotating screenshots, tags, price,
 * best coupon and a Buy now button that goes straight to Razorpay checkout.
 *
 * @package DigiMarket
 */

get_header();

$dm_ids  = dm_lp_theme_ids();
$dm_best = dm_lp_best_theme_coupon( $dm_ids );
$dm_uid  = get_current_user_id();
$dm_hero = array();
foreach ( $dm_ids as $dm_id ) {
	$dm_hero[] = (int) get_post_thumbnail_id( $dm_id );
}
$dm_hero = array_slice( array_values( array_unique( array_filter( $dm_hero ) ) ), 0, 6 );

// Tag chips from the themes' own tags.
$dm_tags = array();
foreach ( $dm_ids as $dm_id ) {
	foreach ( (array) wp_get_post_terms( $dm_id, 'dm_tag' ) as $dm_t ) {
		if ( $dm_t instanceof WP_Term ) {
			$dm_tags[ $dm_t->slug ] = $dm_t->name;
		}
	}
}
asort( $dm_tags );
$dm_term = dm_lp_theme_term();
?>
<div class="dm-lp dm-lp-thm">
	<header class="dm-lp-hero">
		<?php echo $dm_hero ? dm_lp_fade( $dm_hero, 'full', dm_store_opt( 'lp_thm_h1' ), true, false ) : dm_lp_wallpaper( 'wordpress' ); // phpcs:ignore ?>
		<div class="dm-container dm-lp-hero-in">
			<?php dm_render_breadcrumbs(); ?>
			<span class="dm-kicker"><?php esc_html_e( 'WordPress themes', 'digimarket' ); ?></span>
			<h1><?php echo esc_html( dm_store_opt( 'lp_thm_h1' ) ); ?></h1>
			<p><?php echo esc_html( dm_store_opt( 'lp_thm_sub' ) ); ?></p>
			<ul class="dm-lp-chips">
				<li><?php echo dm_icon( 'bolt', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Instant download', 'digimarket' ); ?></li>
				<li><?php echo dm_icon( 'shield', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Secure Razorpay payment', 'digimarket' ); ?></li>
				<li><?php echo dm_icon( 'book', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Setup guide included', 'digimarket' ); ?></li>
			</ul>
			<?php if ( $dm_ids ) : ?><div class="dm-lp-cta"><a class="dm-btn dm-btn-buy dm-btn-lg" href="#themes"><?php esc_html_e( 'Browse themes', 'digimarket' ); ?> <?php echo dm_icon( 'chev-d', 18 ); // phpcs:ignore ?></a></div><?php endif; ?>
		</div>
	</header>

	<div class="dm-container">
		<?php
		if ( $dm_best ) {
			$dm_c = $dm_best[2];
			/* translators: 1 discount 2 code */
			$dm_txt = sprintf( __( 'Save %1$s on your theme — use code %2$s at checkout.', 'digimarket' ), 'percent' === $dm_c->discount_type ? (float) $dm_c->discount_value . '%' : dm_money_short( $dm_c->discount_value ), $dm_best[0] );
			dm_lp_coupon_strip( $dm_best[0], $dm_txt );
		}
		?>

		<section class="dm-lp-sec" id="themes">
			<div class="dm-lp-head"><h2><?php esc_html_e( 'All themes', 'digimarket' ); ?></h2><span class="dm-muted"><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d theme', '%d themes', count( $dm_ids ), 'digimarket' ), count( $dm_ids ) ) ); ?></span></div>
			<?php if ( count( $dm_tags ) > 1 ) : ?>
				<div class="dm-lp-filter" role="group" aria-label="<?php esc_attr_e( 'Filter themes', 'digimarket' ); ?>" data-lp-filter>
					<button type="button" class="dm-lp-chip is-active" data-tag="" aria-pressed="true"><?php esc_html_e( 'All', 'digimarket' ); ?></button>
					<?php foreach ( $dm_tags as $dm_slug => $dm_name ) : ?>
						<button type="button" class="dm-lp-chip" data-tag="<?php echo esc_attr( $dm_slug ); ?>" aria-pressed="false"><?php echo esc_html( $dm_name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! $dm_ids ) : ?>
				<?php dm_empty_state( __( 'New themes are coming soon', 'digimarket' ), __( 'Meanwhile, we can build a custom website for you.', 'digimarket' ), dm_url( 'website-services' ), __( 'See website services', 'digimarket' ) ); ?>
			<?php endif; ?>
			<div class="dm-lp-themes">
				<?php foreach ( $dm_ids as $dm_i => $dm_id ) : ?>
					<?php
					$dm_ttags = wp_get_post_terms( $dm_id, 'dm_tag' );
					$dm_ttags = is_wp_error( $dm_ttags ) ? array() : $dm_ttags;
					$dm_demo  = (string) get_post_meta( $dm_id, '_dm_demo_url', true );
					$dm_cpn   = dm_best_coupon_for( $dm_id );
					$dm_owned = $dm_uid && dm_user_purchase( $dm_uid, $dm_id );
					list( $dm_can, $dm_reason ) = dm_can_purchase( $dm_id );
					$dm_desc  = has_excerpt( $dm_id ) ? get_the_excerpt( $dm_id ) : dm_trim_chars( get_post_field( 'post_content', $dm_id ), 160 );
					$dm_badge = get_post_meta( $dm_id, '_dm_badge', true );
					?>
					<article class="dm-lp-theme" data-tags="<?php echo esc_attr( implode( ' ', wp_list_pluck( $dm_ttags, 'slug' ) ) ); ?>">
						<a class="dm-lp-theme-media" href="<?php echo esc_url( get_permalink( $dm_id ) ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo dm_lp_fade( dm_lp_images( $dm_id ), 'dm-wide', get_the_title( $dm_id ), $dm_i < 2 ); // phpcs:ignore ?>
							<?php if ( dm_discount_percent( $dm_id ) ) : ?><span class="dm-lp-badge is-off"><?php echo esc_html( sprintf( /* translators: %d */ __( '%d%% off', 'digimarket' ), dm_discount_percent( $dm_id ) ) ); ?></span><?php elseif ( $dm_badge ) : ?><span class="dm-lp-badge"><?php echo esc_html( $dm_badge ); ?></span><?php endif; ?>
						</a>
						<div class="dm-lp-theme-body">
							<h3><a href="<?php echo esc_url( get_permalink( $dm_id ) ); ?>"><?php echo esc_html( get_the_title( $dm_id ) ); ?></a></h3>
							<?php echo dm_rating_chip( $dm_id ); // phpcs:ignore ?>
							<?php if ( $dm_desc ) : ?><p class="dm-lp-desc"><?php echo esc_html( $dm_desc ); ?></p><?php endif; ?>
							<?php if ( $dm_ttags ) : ?>
								<ul class="dm-lp-tags"><?php foreach ( array_slice( $dm_ttags, 0, 4 ) as $dm_t ) : ?><li><?php echo esc_html( $dm_t->name ); ?></li><?php endforeach; ?></ul>
							<?php endif; ?>
							<div class="dm-lp-theme-price"><?php echo wp_kses_post( dm_card_price_html( $dm_id ) ); ?></div>
							<?php if ( $dm_cpn && ! $dm_owned ) : ?>
								<p class="dm-lp-offer"><?php echo dm_icon( 'tag', 14 ); // phpcs:ignore ?> <?php echo esc_html( sprintf( /* translators: 1 price 2 code */ __( 'Best price %1$s with %2$s', 'digimarket' ), dm_money_short( $dm_cpn[1] ), $dm_cpn[0] ) ); ?></p>
							<?php endif; ?>
							<div class="dm-lp-actions">
								<?php if ( $dm_owned ) : ?>
									<a class="dm-btn dm-btn-primary" href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php echo dm_icon( 'download', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Download', 'digimarket' ); ?></a>
								<?php elseif ( $dm_can ) : ?>
									<form method="post" class="dm-lp-buy">
										<?php dm_nonce_field( 'cart_add' ); ?>
										<input type="hidden" name="product_id" value="<?php echo (int) $dm_id; ?>">
										<button class="dm-btn dm-btn-buy" name="buy_now" value="1" type="submit" aria-label="<?php echo esc_attr( sprintf( /* translators: %s */ __( 'Buy %s now', 'digimarket' ), get_the_title( $dm_id ) ) ); ?>"><?php echo dm_icon( 'bolt', 18 ); // phpcs:ignore ?> <?php echo esc_html( dm_product_price( $dm_id ) > 0 ? __( 'Buy now', 'digimarket' ) : __( 'Get it free', 'digimarket' ) ); ?></button>
									</form>
								<?php else : ?>
									<span class="dm-muted dm-lp-na"><?php echo esc_html( $dm_reason ); ?></span>
								<?php endif; ?>
								<?php if ( $dm_demo ) : ?>
									<a class="dm-btn dm-btn-outline" href="<?php echo esc_url( $dm_demo ); ?>" target="_blank" rel="noopener nofollow"><?php echo dm_icon( 'external', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Live preview', 'digimarket' ); ?></a>
								<?php else : ?>
									<a class="dm-btn dm-btn-outline" href="<?php echo esc_url( get_permalink( $dm_id ) ); ?>"><?php esc_html_e( 'Details', 'digimarket' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( $dm_term && ! is_wp_error( get_term_link( $dm_term ) ) ) : ?>
				<p class="dm-lp-more"><a class="dm-more" href="<?php echo esc_url( get_term_link( $dm_term ) ); ?>"><?php esc_html_e( 'Sort & filter all themes', 'digimarket' ); ?> <?php echo dm_icon( 'arrow', 16 ); // phpcs:ignore ?></a></p>
			<?php endif; ?>
		</section>

		<?php $dm_incl = dm_lines( dm_store_opt( 'lp_thm_includes' ) ); ?>
		<?php if ( $dm_incl ) : ?>
			<section class="dm-lp-sec dm-lp-get">
				<h2><?php esc_html_e( 'What you get with every theme', 'digimarket' ); ?></h2>
				<ul class="dm-lp-inc is-grid"><?php foreach ( $dm_incl as $dm_l ) : ?><li><?php echo dm_icon( 'check', 18 ); // phpcs:ignore ?> <?php echo esc_html( $dm_l ); ?></li><?php endforeach; ?></ul>
				<p class="dm-lp-steps"><span><b>1</b> <?php esc_html_e( 'Buy now', 'digimarket' ); ?></span><span><b>2</b> <?php esc_html_e( 'Pay with UPI / card', 'digimarket' ); ?></span><span><b>3</b> <?php esc_html_e( 'Download instantly', 'digimarket' ); ?></span></p>
			</section>
		<?php endif; ?>

		<?php dm_lp_reviews_block( $dm_ids, __( 'What buyers say', 'digimarket' ), __( 'Buyer reviews appear here after purchase.', 'digimarket' ) ); ?>

		<div class="dm-lp-sec"><?php dm_render_faq( dm_parse_pairs( dm_store_opt( 'lp_thm_faq' ) ) ); ?></div>
	</div>

	<?php if ( dm_lp_service_ids( 1 ) ) : ?>
		<section class="dm-lp-band">
			<div class="dm-container">
				<div><h2><?php esc_html_e( 'Need it installed and customised?', 'digimarket' ); ?></h2><p><?php esc_html_e( 'We set up the theme with your logo, content and pages — ready to launch.', 'digimarket' ); ?></p></div>
				<a class="dm-btn dm-btn-light dm-btn-lg" href="<?php echo esc_url( dm_url( 'website-services' ) ); ?>"><?php esc_html_e( 'See website services', 'digimarket' ); ?> <?php echo dm_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
