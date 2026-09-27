<?php
/**
 * /website-services/ — every website type with rotating photos, prices,
 * reviews and a WhatsApp "Order now" button.
 *
 * @package DigiMarket
 */

get_header();

$dm_ids   = dm_lp_service_ids();
$dm_code  = dm_store_opt( 'lp_svc_coupon' );
$dm_wa    = dm_whatsapp_url( __( 'Hi, I want a website. Can we discuss?', 'digimarket' ) . ( $dm_code ? ' ' . sprintf( /* translators: %s */ __( 'Coupon: %s.', 'digimarket' ), $dm_code ) : '' ) );
$dm_hero  = array();
$dm_wall  = (int) dm_store_opt( 'lp_svc_wall' );
if ( $dm_wall ) {
	$dm_hero[] = $dm_wall;
}
foreach ( $dm_ids as $dm_id ) {
	$dm_hero[] = (int) get_post_thumbnail_id( $dm_id );
}
$dm_hero  = array_slice( array_values( array_unique( array_filter( $dm_hero ) ) ), 0, 5 );
$dm_phone = dm_store_opt( 'business_phone' ) ? dm_store_opt( 'business_phone' ) : dm_opt( 'whatsapp_number' );
$dm_email = dm_store_opt( 'business_email' ) ? dm_store_opt( 'business_email' ) : dm_opt( 'support_email' );
?>
<div class="dm-lp dm-lp-svc">
	<header class="dm-lp-hero">
		<?php echo dm_lp_fade( $dm_hero, 'full', dm_store_opt( 'lp_svc_h1' ), true, false ); // phpcs:ignore ?>
		<div class="dm-container dm-lp-hero-in">
			<?php dm_render_breadcrumbs(); ?>
			<span class="dm-kicker"><?php esc_html_e( 'Website services', 'digimarket' ); ?></span>
			<h1><?php echo esc_html( dm_store_opt( 'lp_svc_h1' ) ); ?></h1>
			<p><?php echo esc_html( dm_store_opt( 'lp_svc_sub' ) ); ?></p>
			<ul class="dm-lp-chips">
				<li><?php echo dm_icon( 'clock', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Ready in 5–10 days', 'digimarket' ); ?></li>
				<li><?php echo dm_icon( 'chat', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Pay after we agree', 'digimarket' ); ?></li>
				<li><?php echo dm_icon( 'shield', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Refund if we can’t deliver', 'digimarket' ); ?></li>
			</ul>
			<div class="dm-lp-cta">
				<?php if ( $dm_wa ) : ?><a class="dm-btn dm-btn-wa dm-btn-lg" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Chat on WhatsApp', 'digimarket' ); ?></a><?php endif; ?>
				<?php if ( $dm_ids ) : ?><a class="dm-btn dm-btn-light dm-btn-lg" href="#types"><?php esc_html_e( 'See website types', 'digimarket' ); ?> <?php echo dm_icon( 'chev-d', 18 ); // phpcs:ignore ?></a><?php endif; ?>
			</div>
		</div>
	</header>

	<div class="dm-container">
		<?php dm_lp_coupon_strip( $dm_code, dm_store_opt( 'lp_svc_coupon_txt' ) ? dm_store_opt( 'lp_svc_coupon_txt' ) : sprintf( /* translators: %s code */ __( 'Use code %s when you order — we apply the discount to your website quote.', 'digimarket' ), $dm_code ) ); ?>

		<section class="dm-lp-sec" id="types">
			<div class="dm-lp-head"><h2><?php esc_html_e( 'Choose your website type', 'digimarket' ); ?></h2><span class="dm-muted"><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d type', '%d types', count( $dm_ids ), 'digimarket' ), count( $dm_ids ) ) ); ?></span></div>
			<?php if ( ! $dm_ids ) : ?>
				<?php dm_empty_state( __( 'Website types are coming soon', 'digimarket' ), __( 'Message us on WhatsApp and tell us what you need.', 'digimarket' ), $dm_wa, __( 'Chat on WhatsApp', 'digimarket' ) ); ?>
			<?php endif; ?>
			<?php foreach ( $dm_ids as $dm_i => $dm_id ) : ?>
				<?php
				$dm_pk    = array_values( array_filter( (array) get_post_meta( $dm_id, '_dm_packages', true ) ) );
				$dm_inc   = array_slice( dm_lines( get_post_meta( $dm_id, '_dm_included', true ) ), 0, 6 );
				$dm_turn  = get_post_meta( $dm_id, '_dm_turnaround', true );
				$dm_badge = get_post_meta( $dm_id, '_dm_badge', true );
				$dm_order = dm_lp_order_url( $dm_id );
				$dm_desc  = has_excerpt( $dm_id ) ? get_the_excerpt( $dm_id ) : dm_trim_chars( get_post_field( 'post_content', $dm_id ), 220 );
				?>
				<article class="dm-lp-type<?php echo $dm_i % 2 ? ' is-flip' : ''; ?>" id="type-<?php echo (int) $dm_id; ?>">
					<a class="dm-lp-type-media" href="<?php echo esc_url( get_permalink( $dm_id ) ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo dm_lp_fade( dm_lp_images( $dm_id ), 'dm-wide', get_the_title( $dm_id ), 0 === $dm_i ); // phpcs:ignore ?>
						<?php if ( $dm_badge ) : ?><span class="dm-lp-badge"><?php echo esc_html( $dm_badge ); ?></span><?php endif; ?>
					</a>
					<div class="dm-lp-type-body">
						<h3><a href="<?php echo esc_url( get_permalink( $dm_id ) ); ?>"><?php echo esc_html( get_the_title( $dm_id ) ); ?></a></h3>
						<?php echo dm_rating_chip( $dm_id ); // phpcs:ignore ?>
						<?php if ( $dm_desc ) : ?><p><?php echo esc_html( $dm_desc ); ?></p><?php endif; ?>
						<?php if ( $dm_inc ) : ?>
							<ul class="dm-lp-inc">
								<?php foreach ( $dm_inc as $dm_l ) : ?><li><?php echo dm_icon( 'check', 16 ); // phpcs:ignore ?> <?php echo esc_html( $dm_l ); ?></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="dm-lp-price">
							<span><small><?php esc_html_e( 'Starting', 'digimarket' ); ?></small> <b><?php echo esc_html( dm_money_short( dm_product_price( $dm_id ) ) ); ?></b></span>
							<?php if ( $dm_turn ) : ?><span class="dm-lp-turn"><?php echo dm_icon( 'clock', 14 ); // phpcs:ignore ?> <?php echo esc_html( $dm_turn ); ?></span><?php endif; ?>
						</div>
						<?php if ( $dm_pk ) : ?>
							<div class="dm-lp-pkgs">
								<?php foreach ( $dm_pk as $dm_p ) : ?>
									<a class="dm-lp-pkg<?php echo ! empty( $dm_p['popular'] ) ? ' is-popular' : ''; ?>" href="<?php echo esc_url( dm_lp_order_url( $dm_id, $dm_p['name'] ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp" data-ref="<?php echo (int) $dm_id; ?>"><span><?php echo esc_html( $dm_p['name'] ); ?></span><b><?php echo esc_html( dm_money_short( $dm_p['price'] ) ); ?></b></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<div class="dm-lp-actions">
							<?php if ( $dm_order ) : ?><a class="dm-btn dm-btn-wa" href="<?php echo esc_url( $dm_order ); ?>" target="_blank" rel="noopener" data-track="whatsapp" data-ref="<?php echo (int) $dm_id; ?>"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Order now', 'digimarket' ); ?></a><?php endif; ?>
							<a class="dm-btn dm-btn-outline" href="<?php echo esc_url( get_permalink( $dm_id ) ); ?>"><?php esc_html_e( 'Full details', 'digimarket' ); ?></a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</section>

		<?php if ( dm_parse_pairs( dm_store_opt( 'process_steps' ) ) ) : ?>
			<section class="dm-lp-sec"><div class="dm-lp-head"><h2><?php esc_html_e( 'How it works', 'digimarket' ); ?></h2></div><?php dm_render_process_steps(); ?></section>
		<?php endif; ?>

		<?php dm_lp_reviews_block( $dm_ids, __( 'Reviews & feedback', 'digimarket' ), __( 'Client reviews appear here after their websites are delivered.', 'digimarket' ) ); ?>

		<?php $dm_pf = dm_get_portfolio( 0, 6 ); ?>
		<?php if ( $dm_pf ) : ?>
			<section class="dm-lp-sec"><div class="dm-lp-head"><h2><?php esc_html_e( 'Websites we have built', 'digimarket' ); ?></h2><a class="dm-more" href="<?php echo esc_url( get_post_type_archive_link( 'dm_portfolio' ) ); ?>"><?php esc_html_e( 'All work', 'digimarket' ); ?> <?php echo dm_icon( 'arrow', 16 ); // phpcs:ignore ?></a></div><div class="dm-pf-grid"><?php foreach ( $dm_pf as $dm_p ) { dm_portfolio_card( $dm_p ); } ?></div></section>
		<?php endif; ?>

		<div class="dm-lp-sec"><?php dm_render_faq( dm_parse_pairs( dm_store_opt( 'default_faq' ) ) ); ?></div>

		<section class="dm-lp-sec dm-lp-contact" id="contact">
			<div>
				<h2><?php esc_html_e( 'Contact details', 'digimarket' ); ?></h2>
				<ul class="dm-lp-cinfo">
					<?php if ( $dm_wa ) : ?><li><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?><span><small><?php esc_html_e( 'WhatsApp', 'digimarket' ); ?></small><a href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php esc_html_e( 'Chat now', 'digimarket' ); ?> — <?php echo esc_html( dm_store_opt( 'trust_reply' ) ); ?></a></span></li><?php endif; ?>
					<?php if ( $dm_phone ) : ?><li><?php echo dm_icon( 'phone', 20 ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Phone', 'digimarket' ); ?></small><a href="tel:+<?php echo esc_attr( ltrim( preg_replace( '/[^0-9]/', '', $dm_phone ), '+' ) ); ?>">+<?php echo esc_html( ltrim( preg_replace( '/[^0-9]/', '', $dm_phone ), '+' ) ); ?></a></span></li><?php endif; ?>
					<?php if ( $dm_email ) : ?><li><?php echo dm_icon( 'mail', 20 ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Email', 'digimarket' ); ?></small><a href="mailto:<?php echo esc_attr( $dm_email ); ?>"><?php echo esc_html( $dm_email ); ?></a></span></li><?php endif; ?>
					<?php if ( dm_store_opt( 'area_served' ) ) : ?><li><?php echo dm_icon( 'pin', 20 ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Area served', 'digimarket' ); ?></small><?php echo esc_html( dm_store_opt( 'area_served' ) ); ?></span></li><?php endif; ?>
					<?php if ( dm_store_opt( 'lp_hours' ) ) : ?><li><?php echo dm_icon( 'clock', 20 ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Working hours', 'digimarket' ); ?></small><?php echo esc_html( dm_store_opt( 'lp_hours' ) ); ?></span></li><?php endif; ?>
				</ul>
				<p class="dm-promise"><?php echo dm_icon( 'shield', 16 ); // phpcs:ignore ?> <?php echo esc_html( dm_store_opt( 'refund_promise' ) ); ?></p>
			</div>
			<div class="dm-lp-form">
				<h3><?php esc_html_e( 'Or send us your requirement', 'digimarket' ); ?></h3>
				<?php dm_render_enquiry_form( 0 ); ?>
			</div>
		</section>
	</div>

	<?php if ( $dm_wa ) : ?>
		<section class="dm-lp-band">
			<div class="dm-container">
				<div><h2><?php esc_html_e( 'Have a website idea? Let’s talk.', 'digimarket' ); ?></h2><p><?php esc_html_e( 'Tell us about your school, committee or shop — we reply with a clear price.', 'digimarket' ); ?></p></div>
				<a class="dm-btn dm-btn-wa dm-btn-lg" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Order on WhatsApp', 'digimarket' ); ?></a>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
