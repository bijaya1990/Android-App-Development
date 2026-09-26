<?php
/**
 * Static page.
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-container dm-page dm-narrow-lg">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'dm-card dm-pad dm-prose' ); ?>>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		</article>
		<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
	<?php endwhile; ?>
</div>
<?php
get_footer();
