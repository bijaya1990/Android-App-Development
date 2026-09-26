<?php
/**
 * Main fallback template (blog index, archives, search).
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-container dm-page">
	<header class="dm-page-head">
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<h1><?php single_post_title(); ?></h1>
		<?php elseif ( is_search() ) : ?>
			<h1><?php echo esc_html( sprintf( /* translators: %s */ __( 'Search results for “%s”', 'digimarket' ), get_search_query() ) ); ?></h1>
			<p class="dm-muted"><a href="<?php echo esc_url( add_query_arg( array( 's' => get_search_query(), 'post_type' => 'dm_product' ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Search products instead →', 'digimarket' ); ?></a></p>
		<?php elseif ( is_archive() ) : ?>
			<?php the_archive_title( '<h1>', '</h1>' ); ?>
			<?php the_archive_description( '<div class="dm-lead">', '</div>' ); ?>
		<?php else : ?>
			<h1><?php esc_html_e( 'Blog', 'digimarket' ); ?></h1>
		<?php endif; ?>
	</header>
	<div class="dm-blog">
		<div class="dm-blog-main">
			<?php if ( have_posts() ) : ?>
				<div class="dm-post-list">
					<?php while ( have_posts() ) : the_post(); ?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'dm-card dm-post-card' ); ?>>
							<?php if ( has_post_thumbnail() ) : ?><a class="dm-card-media" href="<?php the_permalink(); ?>" tabindex="-1"><?php the_post_thumbnail( 'dm-card' ); ?></a><?php endif; ?>
							<div class="dm-card-body">
								<span class="dm-eyebrow"><?php echo esc_html( get_the_date() ); ?></span>
								<h2 class="dm-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<div class="dm-muted"><?php the_excerpt(); ?></div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<div class="dm-paginate"><?php the_posts_pagination(); ?></div>
			<?php else : ?>
				<?php dm_empty_state( __( 'Nothing found', 'digimarket' ), __( 'Try a different search.', 'digimarket' ) ); ?>
				<?php get_search_form(); ?>
			<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
