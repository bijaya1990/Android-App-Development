<?php
/**
 * Single post.
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-container dm-page">
	<div class="dm-blog">
		<div class="dm-blog-main">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'dm-card dm-pad dm-prose' ); ?>>
					<header>
						<span class="dm-eyebrow"><?php echo esc_html( get_the_date() ); ?> · <?php the_author(); ?></span>
						<h1><?php the_title(); ?></h1>
					</header>
					<?php if ( has_post_thumbnail() ) : ?><figure class="dm-featured"><?php the_post_thumbnail( 'large' ); ?></figure><?php endif; ?>
					<?php the_content(); ?>
					<?php wp_link_pages( array( 'before' => '<nav class="dm-page-links">', 'after' => '</nav>' ) ); ?>
					<footer class="dm-post-foot"><?php the_category( ', ' ); ?> <?php the_tags( '<span class="dm-tags">', ', ', '</span>' ); ?></footer>
				</article>
				<?php the_post_navigation(); ?>
				<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
			<?php endwhile; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
