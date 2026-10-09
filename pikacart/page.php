<?php
/**
 * Normal pages (About, Contact, Pricing, legal pages...).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-article' ); ?>>
		<header class="page-hero">
			<div class="wrap"><h1><?php the_title(); ?></h1></div>
		</header>
		<div class="wrap entry">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
