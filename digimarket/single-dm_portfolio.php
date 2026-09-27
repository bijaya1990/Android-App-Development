<?php
/**
 * Single portfolio project.
 *
 * @package DigiMarket
 */

get_header();

while ( have_posts() ) :
	the_post();
	$dm_id   = get_the_ID();
	$dm_url  = get_post_meta( $dm_id, '_dm_pf_url', true );
	$dm_kind = get_post_meta( $dm_id, '_dm_pf_kind', true );
	$dm_svc  = (int) get_post_meta( $dm_id, '_dm_pf_service', true );
	$dm_mob  = (int) get_post_meta( $dm_id, '_dm_pf_mobile', true );
	?>
	<div class="dm-container dm-page dm-pf-single">
		<?php dm_render_breadcrumbs(); ?>
		<header class="dm-cat-head">
			<div>
				<span class="dm-pf-kind<?php echo 'demo' === $dm_kind ? ' is-demo' : ''; ?>"><?php echo 'demo' === $dm_kind ? esc_html__( 'Demo site', 'digimarket' ) : esc_html__( 'Client work', 'digimarket' ); ?></span>
				<h1><?php the_title(); ?></h1>
				<?php if ( get_post_meta( $dm_id, '_dm_pf_client', true ) ) : ?><p class="dm-muted"><?php echo esc_html( get_post_meta( $dm_id, '_dm_pf_client', true ) ); ?></p><?php endif; ?>
			</div>
			<?php if ( $dm_url ) : ?><a class="dm-btn dm-btn-primary" href="<?php echo esc_url( $dm_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View live site', 'digimarket' ); ?> <?php echo dm_icon( 'external', 16 ); // phpcs:ignore ?></a><?php endif; ?>
		</header>
		<div class="dm-pf-shots">
			<?php if ( has_post_thumbnail() ) : ?><figure class="dm-pf-desk"><?php the_post_thumbnail( 'full', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure><?php endif; ?>
			<?php if ( $dm_mob ) : ?><figure class="dm-pf-mob"><?php echo wp_get_attachment_image( $dm_mob, 'large' ); ?></figure><?php endif; ?>
		</div>
		<div class="dm-pf-case">
			<?php foreach ( array( 'problem' => __( 'The problem', 'digimarket' ), 'built' => __( 'What we built', 'digimarket' ), 'result' => __( 'The result', 'digimarket' ) ) as $dm_k => $dm_l ) : ?>
				<?php $dm_v = get_post_meta( $dm_id, '_dm_pf_' . $dm_k, true ); ?>
				<?php if ( $dm_v ) : ?><section><h2><?php echo esc_html( $dm_l ); ?></h2><p><?php echo nl2br( esc_html( $dm_v ) ); ?></p></section><?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php if ( trim( get_the_content() ) ) : ?><div class="dm-prose dm-psec"><?php the_content(); ?></div><?php endif; ?>
		<?php if ( $dm_svc && 'publish' === get_post_status( $dm_svc ) ) : ?>
			<div class="dm-ft-cta dm-inline-cta-lg">
				<div><h2><?php echo esc_html( sprintf( /* translators: %s */ __( 'Want this for your %s?', 'digimarket' ), get_post_meta( $dm_id, '_dm_pf_client', true ) ? get_post_meta( $dm_id, '_dm_pf_client', true ) : __( 'organisation', 'digimarket' ) ) ); ?></h2><p><?php echo esc_html( get_the_title( $dm_svc ) ); ?> — <?php echo wp_kses_post( dm_card_price_html( $dm_svc ) ); ?></p></div>
				<a class="dm-btn dm-btn-light dm-btn-lg" href="<?php echo esc_url( get_permalink( $dm_svc ) ); ?>"><?php esc_html_e( 'See the package', 'digimarket' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<?php
endwhile;

get_footer();
