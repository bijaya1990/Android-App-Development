<?php
/**
 * Site footer: categories, legal links, contact, social.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_email = function_exists( 'pkc_support_email' ) ? pkc_support_email() : get_option( 'admin_email' );
$pkc_phone = pkc_theme_setting( 'contact_phone', '' );
$pkc_legal = array(
	'about'   => __( 'About', 'pikacart' ),
	'pricing' => __( 'Pricing', 'pikacart' ),
	'contact' => __( 'Contact', 'pikacart' ),
	'privacy' => __( 'Privacy Policy', 'pikacart' ),
	'terms'   => __( 'Terms and Conditions', 'pikacart' ),
	'refund'  => __( 'Refund and Cancellation', 'pikacart' ),
	'aup'     => __( 'Acceptable Use Policy', 'pikacart' ),
);
$pkc_social = array(
	'social_facebook'  => 'Facebook',
	'social_instagram' => 'Instagram',
	'social_youtube'   => 'YouTube',
);
?>
</main>
<footer class="site-footer">
	<div class="wrap footer-grid">
		<div class="footer-brand">
			<?php pkc_theme_logo(); ?>
			<p><?php echo esc_html( pkc_theme_setting( 'tagline', get_bloginfo( 'description' ) ) ); ?></p>
			<p class="footer-contact">
				<a href="mailto:<?php echo esc_attr( $pkc_email ); ?>"><?php echo pkc_theme_icon( 'mail' ); // phpcs:ignore ?> <?php echo esc_html( $pkc_email ); ?></a>
				<?php if ( $pkc_phone ) : ?>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $pkc_phone ) ); ?>"><?php echo pkc_theme_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( $pkc_phone ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Categories', 'pikacart' ); ?></h2>
			<ul class="footer-links">
				<?php foreach ( array_slice( pkc_theme_categories(), 0, 8 ) as $pkc_cat ) : ?>
					<li><a href="<?php echo esc_url( home_url( '/#categories' ) ); ?>"><?php echo esc_html( $pkc_cat['name'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Company', 'pikacart' ); ?></h2>
			<ul class="footer-links">
				<?php foreach ( $pkc_legal as $pkc_key => $pkc_label ) : ?>
					<?php if ( pkc_theme_page( $pkc_key ) ) : ?>
						<li><a href="<?php echo esc_url( pkc_theme_page( $pkc_key ) ); ?>"><?php echo esc_html( $pkc_label ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Get started', 'pikacart' ); ?></h2>
			<ul class="footer-links">
				<li><a href="<?php echo esc_url( pkc_theme_url( 'register' ) ); ?>"><?php esc_html_e( 'Start free trial', 'pikacart' ); ?></a></li>
				<li><a href="<?php echo esc_url( pkc_theme_url( 'login' ) ); ?>"><?php esc_html_e( 'Login', 'pikacart' ); ?></a></li>
				<?php foreach ( $pkc_social as $pkc_key => $pkc_label ) : ?>
					<?php if ( pkc_theme_setting( $pkc_key, '' ) ) : ?>
						<li><a href="<?php echo esc_url( pkc_theme_setting( $pkc_key, '' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $pkc_label ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ( pkc_theme_setting( 'social_whatsapp', '' ) ) : ?>
					<li><a href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/\D+/', '', pkc_theme_setting( 'social_whatsapp', '' ) ) ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
	<div class="wrap footer-bottom">
		<span>&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . pkc_theme_setting( 'site_name', get_bloginfo( 'name' ) ) ); ?></span>
		<span><?php echo pkc_theme_icon( 'shield' ); // phpcs:ignore ?> <?php esc_html_e( 'Secure payments by Razorpay', 'pikacart' ); ?></span>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
