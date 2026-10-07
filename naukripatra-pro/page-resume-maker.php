<?php
/**
 * Resume Maker page template (runs fully in the browser; nothing is uploaded).
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="np-wrap">
	<article class="np-card np-prose">
		<h1><?php the_title(); ?></h1>
		<?php the_content(); ?>
		<p class="np-notice is-ok"><?php esc_html_e( 'Privacy: this tool works 100% in your browser. Your files are never uploaded to our server.', 'naukripatra' ); ?></p>
		<div class="np-tool-ui np-resume" id="np-resume">
			<form class="np-resume__form" onsubmit="return false">
				<p class="np-field"><label for="rs-tpl">Template</label><select id="rs-tpl" class="np-input"><option value="classic">Classic</option><option value="modern">Modern</option><option value="compact">Compact</option></select></p>
				<p class="np-field"><label for="rs-name">Full name</label><input id="rs-name" class="np-input" data-k="name"></p>
				<p class="np-field"><label for="rs-title">Job title</label><input id="rs-title" class="np-input" data-k="title"></p>
				<p class="np-field"><label for="rs-contact">Phone, email, city</label><input id="rs-contact" class="np-input" data-k="contact"></p>
				<p class="np-field"><label for="rs-obj">Objective</label><textarea id="rs-obj" class="np-input" rows="3" data-k="obj"></textarea></p>
				<p class="np-field"><label for="rs-edu">Education (one per line)</label><textarea id="rs-edu" class="np-input" rows="4" data-k="edu"></textarea></p>
				<p class="np-field"><label for="rs-exp">Experience (one per line)</label><textarea id="rs-exp" class="np-input" rows="4" data-k="exp"></textarea></p>
				<p class="np-field"><label for="rs-skills">Skills (one per line)</label><textarea id="rs-skills" class="np-input" rows="3" data-k="skills"></textarea></p>
				<p class="np-field"><label for="rs-lang">Languages (one per line)</label><textarea id="rs-lang" class="np-input" rows="2" data-k="lang"></textarea></p>
				<p><button type="button" class="np-btn" id="rs-print">Print / Save as PDF</button></p>
			</form>
			<div class="np-resume__preview" id="rs-preview" aria-live="polite"></div>
		</div>
	</article>
</div>
<?php
get_footer();
