<?php
/**
 * Fallback list template (blog posts, archives, search).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="page-hero">
	<div class="wrap"><h1><?php echo is_search() ? esc_html( sprintf( /* translators: %s: search term */ __( 'Search: %s', 'pikacart' ), get_search_query() ) ) : esc_html( wp_strip_all_tags( get_the_archive_title() ? get_the_archive_title() : get_bloginfo( 'name' ) ) ); ?></h1></div>
</header>
<div class="wrap entry">
	<?php if ( have_posts() ) : ?>
		<div class="post-list">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'post-card' ); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<p class="post-meta"><?php echo esc_html( get_the_date() ); ?></p>
				<?php if ( is_singular() ) : ?>
					<?php the_content(); ?>
				<?php else : ?>
					<?php the_excerpt(); ?>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'pikacart' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
