<?php
/**
 * Search results (noindex by default).
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
global $wp_query;
$offset = max( 0, (int) get_query_var( 'paged' ) - 1 ) * max( 5, (int) np_opt( 'rows_per_page' ) );
?>
<div class="np-wrap np-layout">
	<div class="np-col">
		<header class="np-card">
			<h1><?php echo esc_html( np_list_heading() ); ?></h1>
			<?php get_search_form(); ?>
		</header>
		<section class="np-card">
			<?php if ( have_posts() ) : np_list_table( $wp_query, $offset ); np_pagination(); else : ?>
				<p><?php esc_html_e( 'Nothing matched your search. Try a shorter word such as "railway" or "bihar".', 'naukripatra' ); ?></p>
			<?php endif; ?>
		</section>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
