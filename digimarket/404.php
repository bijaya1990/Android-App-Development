<?php
/**
 * 404: search, popular categories and a WhatsApp way out.
 *
 * @package DigiMarket
 */

get_header();
$dm_wa = dm_whatsapp_url( __( 'Hi, I could not find a page on your website.', 'digimarket' ) );
?>
<div class="dm-container dm-page dm-404">
	<div class="dm-404-card">
		<span class="dm-404-code" aria-hidden="true">404</span>
		<h1><?php esc_html_e( 'Oops! This page is not here', 'digimarket' ); ?></h1>
		<p class="dm-muted"><?php esc_html_e( 'The link may be old or mistyped. Try searching, or jump into a category below.', 'digimarket' ); ?></p>
		<form class="dm-srch dm-404-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="dm_product">
			<span class="dm-srch-ico"><?php echo dm_icon( 'search', 20 ); // phpcs:ignore ?></span>
			<label class="screen-reader-text" for="dm-404-s"><?php esc_html_e( 'Search products', 'digimarket' ); ?></label>
			<input id="dm-404-s" type="search" name="s" placeholder="<?php esc_attr_e( 'Search notes, templates, websites…', 'digimarket' ); ?>">
			<button class="dm-srch-btn" type="submit" aria-label="<?php esc_attr_e( 'Search', 'digimarket' ); ?>"><?php echo dm_icon( 'search', 18 ); // phpcs:ignore ?></button>
		</form>
		<div class="dm-404-cats">
			<?php foreach ( array_slice( dm_categories( array( 'parent' => 0, 'hide_empty' => true ) ), 0, 8 ) as $dm_i => $dm_c ) : ?>
				<a class="dm-catrow-item" href="<?php echo esc_url( get_term_link( $dm_c ) ); ?>"><?php echo dm_category_icon_html( $dm_c, $dm_i ); // phpcs:ignore ?><span><?php echo esc_html( $dm_c->name ); ?></span></a>
			<?php endforeach; ?>
		</div>
		<p>
			<a class="dm-btn dm-btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to homepage', 'digimarket' ); ?></a>
			<?php if ( $dm_wa ) : ?><a class="dm-btn dm-btn-wa" href="<?php echo esc_url( $dm_wa ); ?>" target="_blank" rel="noopener" data-track="whatsapp"><?php echo dm_icon_whatsapp( 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Ask on WhatsApp', 'digimarket' ); ?></a><?php endif; ?>
		</p>
	</div>
</div>
<?php
get_footer();
