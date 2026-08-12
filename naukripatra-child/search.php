<?php
/**
 * NaukriPatra — Search results (same shining list view)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<div class="np-archive">
	<header class="np-archive-hero">
		<h1 class="np-archive-title">🔍 Search: "<?php echo esc_html( get_search_query() ); ?>"</h1>
		<?php global $wp_query; ?>
		<span class="np-archive-count"><?php echo (int) $wp_query->found_posts; ?> results mile</span>
	</header>

	<?php np_ad_slot( 'list_top', 'np-ad-list' ); ?>

	<?php np_render_job_table(); ?>
</div>
<?php
get_footer();
