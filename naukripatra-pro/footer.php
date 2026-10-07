<?php
/**
 * Footer: brand, legal links, social buttons, disclaimer, copyright (all from settings).
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$social = (array) np_opt( 'social' );
$labels = array( 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp', 'youtube' => 'YouTube', 'playstore' => 'Play Store' );
?>
</main>

<footer class="np-footer">
	<div class="np-wrap">
		<p class="np-footer__brand"><?php echo np_brand_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'menu_class'     => 'np-footer__links',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		?>
		<ul class="np-social">
			<?php foreach ( $labels as $key => $label ) : ?>
				<?php if ( ! empty( $social[ $key ] ) ) : ?>
					<li><a class="np-btn np-btn--ghost" href="<?php echo esc_url( $social[ $key ] ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<p class="np-footer__note"><?php echo esc_html( np_opt( 'disclaimer' ) ); ?></p>
		<p class="np-footer__copy"><?php echo esc_html( str_replace( '{year}', gmdate( 'Y' ), np_opt( 'copyright' ) ) ); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
