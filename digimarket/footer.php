<?php
/**
 * Site footer.
 *
 * @package DigiMarket
 */

?>
</main>
<footer class="dm-footer">
	<div class="dm-container">
		<div class="dm-footer-cta">
			<div>
				<h2><?php esc_html_e( 'Sell your digital products here', 'digimarket' ); ?></h2>
				<p><?php esc_html_e( 'Open your own shop in minutes. Get paid automatically on every sale.', 'digimarket' ); ?></p>
			</div>
			<a class="dm-btn dm-btn-light dm-btn-lg" href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a>
		</div>
		<div class="dm-footer-grid">
			<div>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dm-logo-text"><span class="dm-logo-mark" aria-hidden="true">◆</span><?php bloginfo( 'name' ); ?></a>
				<p class="dm-muted"><?php echo esc_html( get_theme_mod( 'dm_footer_text', __( 'A marketplace where creators open their own shop and get paid automatically on every sale.', 'digimarket' ) ) ); ?></p>
				<div class="dm-trust-mini">
					<span>🔒 <?php esc_html_e( 'Secure payments by Razorpay', 'digimarket' ); ?></span>
					<span>⚡ <?php esc_html_e( 'Instant delivery', 'digimarket' ); ?></span>
				</div>
			</div>
			<div>
				<h4><?php esc_html_e( 'Categories', 'digimarket' ); ?></h4>
				<ul>
					<?php foreach ( array_slice( dm_categories( array( 'parent' => 0 ) ), 0, 6 ) as $dm_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $dm_cat ) ); ?>"><?php echo esc_html( $dm_cat->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h4><?php esc_html_e( 'Marketplace', 'digimarket' ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( dm_products_url() ); ?>"><?php esc_html_e( 'All products', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'shops' ) ); ?>"><?php esc_html_e( 'All shops', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'sell' ) ); ?>"><?php esc_html_e( 'Become a seller', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_url( 'account' ) ); ?>"><?php esc_html_e( 'My account', 'digimarket' ); ?></a></li>
				</ul>
			</div>
			<div>
				<h4><?php esc_html_e( 'Legal', 'digimarket' ); ?></h4>
				<ul>
					<li><a href="<?php echo esc_url( dm_legal_url( 'terms' ) ); ?>"><?php esc_html_e( 'Terms of Service', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'privacy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'refund' ) ); ?>"><?php esc_html_e( 'Refund Policy', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'seller' ) ); ?>"><?php esc_html_e( 'Seller Agreement', 'digimarket' ); ?></a></li>
					<li><a href="<?php echo esc_url( dm_legal_url( 'content' ) ); ?>"><?php esc_html_e( 'Content & IP Policy', 'digimarket' ); ?></a></li>
				</ul>
			</div>
			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<div><?php dynamic_sidebar( 'footer-1' ); ?></div>
			<?php endif; ?>
		</div>
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="dm-footer-menu" aria-label="<?php esc_attr_e( 'Footer', 'digimarket' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'depth' => 1 ) ); ?></nav>
		<?php endif; ?>
		<div class="dm-footer-bottom">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
			<span class="dm-muted"><?php echo esc_html( sprintf( /* translators: %s email */ __( 'Support: %s', 'digimarket' ), dm_opt( 'support_email' ) ) ); ?></span>
		</div>
	</div>
</footer>
<div class="dm-toast" role="status" aria-live="polite" hidden></div>
<?php wp_footer(); ?>
</body>
</html>
