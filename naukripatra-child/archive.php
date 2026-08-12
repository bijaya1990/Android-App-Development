<?php
/**
 * NaukriPatra — Archive (category / state / tag / date)
 * Full-width shining blue list view. No thumbnails, no sidebar.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<div class="np-archive">
	<header class="np-archive-hero">
		<h1 class="np-archive-title"><?php echo esc_html( single_term_title( '', false ) ?: get_the_archive_title() ); ?></h1>
		<?php
		$term = get_queried_object();
		if ( $term && isset( $term->count ) ) {
			echo '<span class="np-archive-count">📌 Total ' . (int) $term->count . ' Updates</span>';
		}
		?>
	</header>

	<?php np_ad_slot( 'list_top', 'np-ad-list' ); ?>

	<?php np_render_job_table(); ?>
</div>
<?php
get_footer();
