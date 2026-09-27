<?php
/**
 * Portfolio archive (/portfolio/).
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-container dm-page">
	<?php dm_render_breadcrumbs(); ?>
	<header class="dm-cat-head">
		<div>
			<h1><?php esc_html_e( 'Our work', 'digimarket' ); ?></h1>
			<p class="dm-muted"><?php esc_html_e( 'Websites we have built for schools, committees, shops and cafés. Open any live demo to see it for yourself.', 'digimarket' ); ?></p>
		</div>
	</header>
	<?php if ( have_posts() ) : ?>
		<div class="dm-pf-grid">
			<?php while ( have_posts() ) : the_post(); dm_portfolio_card( get_post() ); endwhile; ?>
		</div>
		<div class="dm-paginate"><?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?></div>
	<?php else : ?>
		<?php dm_empty_state( __( 'Portfolio coming soon', 'digimarket' ), __( 'We are adding our latest projects here.', 'digimarket' ) ); ?>
	<?php endif; ?>
	<?php
	$dm_wa = dm_whatsapp_url( __( 'Hi, I saw your portfolio and want a website like this.', 'digimarket' ) );
	if ( $dm_wa ) :
		?>
		<div class="dm-ft-cta dm-inline-cta-lg"><div><h2><?php esc_html_e( 'Want a website like these?', 'digimarket' ); ?></h2><p><?php esc_html_e( 'Tell us about your school, committee or shop — we reply on WhatsApp.', 'digimarket' ); ?></p></div><a class="dm-btn dm-btn-wa dm-btn-lg" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 20 ); // phpcs:ignore ?> <?php esc_html_e( 'Chat on WhatsApp', 'digimarket' ); ?></a></div>
	<?php endif; ?>
</div>
<?php
get_footer();
