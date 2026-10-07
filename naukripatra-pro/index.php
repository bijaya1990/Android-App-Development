<?php
/**
 * Fallback template. Dedicated templates (front-page, archive, single...) arrive in later stages.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="np-wrap">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'np-card' ); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php the_excerpt(); ?>
			</article>
		<?php endwhile; ?>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'naukripatra' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
