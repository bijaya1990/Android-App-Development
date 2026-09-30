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
<?php dm_mk_newsletter(); ?>
<footer class="dm-ft">
	<div class="dm-container">
		<?php if ( dm_apply_open() && ! dm_is_seller() ) : ?>
			<div class="dm-ft-cta dm-ft-apply">
				<div>
					<span class="dm-ft-kicker"><?php echo esc_html( dm_apply_batch_label( dm_apply_opt( 'batch' ) ) . ' · ' . dm_apply_opt( 'headline' ) ); ?></span>
					<h2><?php esc_html_e( 'Sell your ebooks, themes, courses & designs on PikaCart', 'digimarket' ); ?></h2>
					<p><?php esc_html_e( 'Low commission, weekly payouts to your bank or UPI, and your own seller dashboard. Limited seats in every batch.', 'digimarket' ); ?></p>
				</div>
				<?php echo dm_apply_button( '', 'dm-btn dm-btn-light dm-btn-lg' ); // phpcs:ignore ?>
			</div>
		<?php elseif ( ! dm_mk() && ! dm_single_seller_mode() ) : ?>
			<div class="dm-ft-cta">
				<div>
					<h2><?php esc_html_e( 'Sell your digital products here', 'digimarket' ); ?></h2>
					<p><?php esc_html_e( 'Open your own shop in minutes. Get paid automatically on every sale.', 'digimarket' ); ?></p>
				</div>
				<a class="dm-btn dm-btn-light dm-btn-lg" href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a>
			</div>
		<?php elseif ( ! dm_mk() && $dm_wa && ! dm_is_landing() ) : ?>
			<div class="dm-ft-cta">
				<div>
					<h2><?php esc_html_e( 'Need a website built?', 'digimarket' ); ?></h2>
					<p><?php esc_html_e( 'Chat with us on WhatsApp — we discuss first, you pay only after we agree.', 'digimarket' ); ?></p>
				</div>
				<a class="dm-btn dm-btn-wa dm-btn-lg" href="<?php echo esc_url( dm_whatsapp_url( __( 'Hi, I have a question about a website/service.', 'digimarket' ) ) ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Chat on WhatsApp', 'digimarket' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( dm_mk() ) : ?>
		<?php $dm_biz = dm_business(); $dm_app = array_filter( array( 'android' => dm_store_opt( 'app_android' ), 'ios' => dm_store_opt( 'app_ios' ) ) ); ?>
		<div class="dm-ft-grid mk-ft-grid">
			<div class="dm-ft-brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-logo"><img src="<?php echo esc_url( dm_mk_logo_src( true ) ); ?>" width="160" height="35" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></a>
				<p><?php echo esc_html( get_theme_mod( 'dm_footer_text', __( 'Your one-stop shop for study notes, templates, WordPress themes and professional websites at the best prices.', 'digimarket' ) ) ); ?></p>
				<?php echo dm_mk_socials(); // phpcs:ignore ?>
			</div>
			<div>
				<h2 class="dm-ft-title"><?php esc_html_e( 'Shop By Category', 'digimarket' ); ?></h2>
				<ul>
					<?php foreach ( array_slice( dm_categories( array( 'parent' => 0 ) ), 0, 6 ) as $dm_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( dm_url( 'website-services' ) ); ?>"><?php esc_html_e( 'Website Services', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'wordpress-themes' ) ); ?>"><?php esc_html_e( 'WordPress Themes', 'digimarket' ); ?></a></li>
					<?php if ( ! dm_single_seller_mode() ) : ?>
						<li><a href="<?php echo esc_url( dm_url( 'shops' ) ); ?>"><?php esc_html_e( 'All shops', 'digimarket' ); ?></a></li>
						<li><a href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>
			<div>
				<h2 class="dm-ft-title"><?php esc_html_e( 'Customer Service', 'digimarket' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( dm_url( 'account', 'orders' ) ); ?>"><?php esc_html_e( 'Track Your Order', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'refund' ) ); ?>"><?php esc_html_e( 'Returns & Refunds', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'delivery' ) ); ?>"><?php esc_html_e( 'Shipping & Delivery', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'privacy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'terms' ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact Us', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'about' ) ); ?>"><?php esc_html_e( 'About Us', 'digimarket' ); ?></a></li>
				</ul>
			</div>
			<div>
				<h2 class="dm-ft-title"><?php esc_html_e( 'My Account', 'digimarket' ); ?></h2>
				<ul>
					<?php if ( is_user_logged_in() ) : ?>
						<li><a href="<?php echo esc_url( dm_url( 'account' ) ); ?>"><?php esc_html_e( 'My Account', 'digimarket' ); ?></a></li>
					<?php else : ?>
						<li><a href="<?php echo esc_url( dm_url( 'login' ) ); ?>"><?php esc_html_e( 'Login / Register', 'digimarket' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( dm_url( 'account', 'orders' ) ); ?>"><?php esc_html_e( 'My Orders', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'account', 'purchases' ) ); ?>"><?php esc_html_e( 'My Downloads', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'account', 'wishlist' ) ); ?>"><?php esc_html_e( 'Wishlist', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'account', 'support' ) ); ?>"><?php esc_html_e( 'Help & Support', 'digimarket' ); ?></a></li>
					<?php if ( dm_store_opt( 'articles_show' ) ) : ?><li><a href="<?php echo esc_url( dm_articles_url() ); ?>"><?php echo esc_html( dm_store_opt( 'articles_label' ) ); ?></a></li><?php endif; ?>
				</ul>
			</div>
			<div class="mk-ft-last">
				<?php if ( $dm_app ) : ?>
					<h2 class="dm-ft-title"><?php esc_html_e( 'Download Our App', 'digimarket' ); ?></h2>
					<p><?php echo esc_html( sprintf( /* translators: %s site */ __( 'Get the %s app for a better shopping experience.', 'digimarket' ), get_bloginfo( 'name' ) ) ); ?></p>
					<div class="mk-apps">
						<?php if ( ! empty( $dm_app['android'] ) ) : ?><a class="mk-app" href="<?php echo esc_url( $dm_app['android'] ); ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="#34A853" d="M4 3.5v17l9-8.5z"/><path fill="#FBBC04" d="M16.5 15.8 13 12.5 4 20.5c.3.2.7.2 1.1 0z"/><path fill="#4285F4" d="M20.5 12.8c.6-.4.6-1.2 0-1.6l-4-2.4-3.5 3.7 3.5 3.3z"/><path fill="#EA4335" d="M16.5 8.8 5.1 3.3c-.4-.2-.8-.2-1.1 0l9 9z"/></svg><span><small><?php esc_html_e( 'GET IT ON', 'digimarket' ); ?></small>Google Play</span></a><?php endif; ?>
						<?php if ( ! empty( $dm_app['ios'] ) ) : ?><a class="mk-app" href="<?php echo esc_url( $dm_app['ios'] ); ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="#fff" d="M16.4 12.6c0-2.4 2-3.5 2-3.6-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.2-2.8.9-3.5.9-.7 0-1.9-.8-3.1-.8-1.6 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3.1.7 2.1-1.1 2.8-2.3c.9-1.3 1.3-2.6 1.3-2.6s-2.5-1-2.5-3.7zM14.1 5.6c.6-.8 1.1-1.9 1-3-1 0-2.1.7-2.8 1.4-.6.7-1.1 1.8-1 2.9 1 .1 2.1-.6 2.8-1.3z"/></svg><span><small><?php esc_html_e( 'Download on the', 'digimarket' ); ?></small>App Store</span></a><?php endif; ?>
					</div>
				<?php else : ?>
					<h2 class="dm-ft-title"><?php esc_html_e( 'Contact Us', 'digimarket' ); ?></h2>
					<ul class="mk-contact">
						<?php if ( $dm_biz['email'] ) : ?><li><?php echo dm_icon( 'mail', 16 ); // phpcs:ignore ?> <a href="mailto:<?php echo esc_attr( $dm_biz['email'] ); ?>"><?php echo esc_html( $dm_biz['email'] ); ?></a></li><?php endif; ?>
						<?php if ( $dm_biz['phone'] ) : ?><li><?php echo dm_icon( 'phone', 16 ); // phpcs:ignore ?> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $dm_biz['phone'] ) ); ?>"><?php echo esc_html( $dm_biz['phone'] ); ?></a></li><?php endif; ?>
						<?php if ( $dm_wa ) : ?><li><?php echo dm_icon( 'chat', 16 ); // phpcs:ignore ?> <a href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php esc_html_e( 'Chat on WhatsApp', 'digimarket' ); ?></a></li><?php endif; ?>
						<?php if ( $dm_biz['address'] ) : ?><li><?php echo dm_icon( 'pin', 16 ); // phpcs:ignore ?> <span><?php echo esc_html( $dm_biz['address'] ); ?></span></li><?php endif; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php else : ?>
		<div class="dm-ft-grid">
			<div class="dm-ft-brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-logo"><img src="<?php echo esc_url( dm_mk_logo_src( true ) ); ?>" width="148" height="32" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></a>
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
					<?php if ( wp_count_posts( 'dm_portfolio' )->publish ) : ?><li><a href="<?php echo esc_url( get_post_type_archive_link( 'dm_portfolio' ) ); ?>"><?php esc_html_e( 'Sample websites', 'digimarket' ); ?></a></li><?php endif; ?>
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
		<?php endif; ?>
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
