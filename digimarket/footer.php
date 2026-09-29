<?php
/**
 * Site footer, floating WhatsApp button and mobile bottom navigation.
 *
 * @package DigiMarket
 */

$dm_wa     = dm_whatsapp_url( __( 'Hi, I have a question.', 'digimarket' ) );
$dm_social = dm_lines( dm_store_opt( 'social_links' ) );
$dm_route  = dm_route();
?>
</main>
<footer class="dm-ft">
	<div class="dm-container">
		<?php if ( ! dm_single_seller_mode() ) : ?>
			<div class="dm-ft-cta">
				<div>
					<h2><?php esc_html_e( 'Sell your digital products here', 'digimarket' ); ?></h2>
					<p><?php esc_html_e( 'Open your own shop in minutes. Get paid automatically on every sale.', 'digimarket' ); ?></p>
				</div>
				<a class="dm-btn dm-btn-light dm-btn-lg" href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a>
			</div>
		<?php elseif ( $dm_wa && ! dm_is_landing() ) : ?>
			<div class="dm-ft-cta">
				<div>
					<h2><?php esc_html_e( 'Need a website built?', 'digimarket' ); ?></h2>
					<p><?php esc_html_e( 'Chat with us on WhatsApp — we discuss first, you pay only after we agree.', 'digimarket' ); ?></p>
				</div>
				<a class="dm-btn dm-btn-wa dm-btn-lg" href="<?php echo esc_url( dm_whatsapp_url( __( 'Hi, I have a question about a website/service.', 'digimarket' ) ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Chat on WhatsApp', 'digimarket' ); ?></a>
			</div>
		<?php endif; ?>

		<div class="dm-ft-grid">
			<div class="dm-ft-brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-logo"><img src="<?php echo esc_url( DM_URI . '/assets/img/pikacart-logo-white.svg' ); ?>" width="148" height="32" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></a>
				<p><?php echo esc_html( get_theme_mod( 'dm_footer_text', __( 'Study notes, resume templates, kids worksheets and professional websites for schools, committees and small businesses.', 'digimarket' ) ) ); ?></p>
				<?php if ( $dm_social ) : ?>
					<ul class="dm-ft-social">
						<?php foreach ( $dm_social as $dm_s ) : ?>
							<?php $dm_host = preg_replace( '/^www\./', '', (string) wp_parse_url( $dm_s, PHP_URL_HOST ) ); ?>
							<li><a href="<?php echo esc_url( $dm_s ); ?>" target="_blank" rel="noopener me"><?php echo esc_html( ucfirst( strtok( $dm_host, '.' ) ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<div>
				<h2 class="dm-ft-title"><?php esc_html_e( 'Shop', 'digimarket' ); ?></h2>
				<ul>
					<?php foreach ( array_slice( dm_categories( array( 'parent' => 0 ) ), 0, 7 ) as $dm_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'All products', 'digimarket' ); ?></a></li>
						<li><a href="<?php echo esc_url( dm_url( 'website-services' ) ); ?>"><?php esc_html_e( 'Website Services', 'digimarket' ); ?></a></li>
						<li><a href="<?php echo esc_url( dm_url( 'wordpress-themes' ) ); ?>"><?php esc_html_e( 'WordPress Themes', 'digimarket' ); ?></a></li>
					<?php if ( ! dm_single_seller_mode() ) : ?>
						<li><a href="<?php echo esc_url( dm_url( 'shops' ) ); ?>"><?php esc_html_e( 'All shops', 'digimarket' ); ?></a></li>
						<li><a href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>
			<div>
				<h2 class="dm-ft-title"><?php esc_html_e( 'Help', 'digimarket' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( dm_legal_url( 'about' ) ); ?>"><?php esc_html_e( 'About us', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact us', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'account' ) ); ?>"><?php esc_html_e( 'My account', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'delivery' ) ); ?>"><?php esc_html_e( 'Shipping & delivery', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'refund' ) ); ?>"><?php esc_html_e( 'Refund & cancellation', 'digimarket' ); ?></a></li>
					<?php if ( dm_store_opt( 'articles_show' ) ) : ?><li><a href="<?php echo esc_url( dm_articles_url() ); ?>"><?php echo esc_html( dm_store_opt( 'articles_label' ) ); ?></a></li><?php endif; ?>
					<?php if ( wp_count_posts( 'dm_portfolio' )->publish ) : ?><li><a href="<?php echo esc_url( get_post_type_archive_link( 'dm_portfolio' ) ); ?>"><?php esc_html_e( 'Our work', 'digimarket' ); ?></a></li><?php endif; ?>
				</ul>
			</div>
			<div>
				<h2 class="dm-ft-title"><?php esc_html_e( 'Legal', 'digimarket' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( dm_legal_url( 'terms' ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'privacy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'affiliate' ) ); ?>"><?php esc_html_e( 'Affiliate disclosure', 'digimarket' ); ?></a></li>
					<?php if ( ! dm_single_seller_mode() ) : ?>
						<li><a href="<?php echo esc_url( dm_legal_url( 'seller' ) ); ?>"><?php esc_html_e( 'Seller Agreement', 'digimarket' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( dm_legal_url( 'content' ) ); ?>"><?php esc_html_e( 'Content & IP Policy', 'digimarket' ); ?></a></li>
				</ul>
			</div>
			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<div><?php dynamic_sidebar( 'footer-1' ); ?></div>
			<?php endif; ?>
		</div>
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="dm-ft-menu" aria-label="<?php esc_attr_e( 'Footer', 'digimarket' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'depth' => 1 ) ); ?></nav>
		<?php endif; ?>
		<div class="dm-ft-pay" aria-label="<?php esc_attr_e( 'Payment methods', 'digimarket' ); ?>">
			<span><?php echo dm_icon( 'shield', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Secure payments by Razorpay', 'digimarket' ); ?></span>
			<ul><li>UPI</li><li>RuPay</li><li>Visa</li><li>Mastercard</li><li><?php esc_html_e( 'Netbanking', 'digimarket' ); ?></li><li><?php esc_html_e( 'Wallets', 'digimarket' ); ?></li></ul>
		</div>
		<div class="dm-ft-bottom">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
			<?php $dm_biz = dm_business(); ?>
			<span class="dm-ft-biz"><?php echo esc_html( implode( ' · ', array_filter( array( $dm_biz['legal'], $dm_biz['address'] ) ) ) ); ?><?php if ( $dm_biz['email'] ) : ?> · <a href="mailto:<?php echo esc_attr( $dm_biz['email'] ); ?>"><?php echo esc_html( $dm_biz['email'] ); ?></a><?php endif; ?></span>
		</div>
	</div>
</footer>

<?php if ( $dm_wa && ! in_array( $dm_route, array( 'checkout', 'dashboard' ), true ) ) : ?>
	<a class="dm-wa-float" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'digimarket' ); ?>"><?php echo dm_icon_whatsapp( 28 ); // phpcs:ignore ?></a>
<?php endif; ?>

<?php if ( dm_store_opt( 'bottom_nav' ) && ! in_array( $dm_route, array( 'checkout', 'dashboard' ), true ) ) : ?>
	<nav class="dm-bnav" aria-label="<?php esc_attr_e( 'Quick navigation', 'digimarket' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><?php echo dm_icon( 'home', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Home', 'digimarket' ); ?></span></a>
		<button type="button" class="dm-bnav-cats" aria-controls="dm-drawer"><?php echo dm_icon( 'grid', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Categories', 'digimarket' ); ?></span></button>
		<button type="button" class="dm-bnav-search"><?php echo dm_icon( 'search', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Search', 'digimarket' ); ?></span></button>
		<a href="<?php echo esc_url( dm_url( 'cart' ) ); ?>"<?php echo 'cart' === $dm_route ? ' aria-current="page"' : ''; ?>><?php echo dm_icon( 'cart', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Cart', 'digimarket' ); ?></span><i class="dm-dot dm-cart-count"<?php echo dm_cart_count() ? '' : ' hidden'; ?>><?php echo (int) dm_cart_count(); ?></i></a>
		<a href="<?php echo esc_url( is_user_logged_in() ? dm_url( 'account' ) : dm_url( 'login' ) ); ?>"<?php echo 'account' === $dm_route ? ' aria-current="page"' : ''; ?>><?php echo dm_icon( 'user', 22 ); // phpcs:ignore ?><span><?php esc_html_e( 'Account', 'digimarket' ); ?></span></a>
	</nav>
<?php endif; ?>
<div class="dm-toast" role="status" aria-live="polite" hidden></div>
<?php wp_footer(); ?>
</body>
</html>
