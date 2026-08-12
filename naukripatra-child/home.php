<?php
/**
 * NaukriPatra — Blog index fallback (agar posts page alag set ho)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<div class="np-archive">
	<header class="np-archive-hero">
		<h1 class="np-archive-title">Latest Updates</h1>
	</header>

	<?php np_ad_slot( 'list_top', 'np-ad-list' ); ?>

	<?php np_render_job_table(); ?>
</div>
<?php
get_footer();
