<?php
/**
 * Photo Resizer page template (runs fully in the browser; nothing is uploaded).
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
		<div class="np-tool-ui" id="np-photo">
			<p class="np-field"><label for="ph-file">Choose photo</label><input type="file" id="ph-file" accept="image/*"></p>
			<p class="np-field"><label for="ph-preset">Exam preset</label><select id="ph-preset" class="np-input"></select></p>
			<div class="np-grid2">
				<p class="np-field"><label for="ph-w">Width (px)</label><input type="number" id="ph-w" class="np-input" min="10" max="4000" value="200"></p>
				<p class="np-field"><label for="ph-h">Height (px)</label><input type="number" id="ph-h" class="np-input" min="10" max="4000" value="230"></p>
				<p class="np-field"><label for="ph-min">Min size (KB)</label><input type="number" id="ph-min" class="np-input" min="0" value="20"></p>
				<p class="np-field"><label for="ph-max">Max size (KB)</label><input type="number" id="ph-max" class="np-input" min="1" value="50"></p>
			</div>
			<p class="np-field"><label for="ph-fmt">Format</label><select id="ph-fmt" class="np-input"><option value="image/jpeg">JPG</option><option value="image/png">PNG</option></select></p>
			<p><button type="button" class="np-btn" id="ph-go">Resize</button></p>
			<p id="ph-info" role="status"></p>
			<canvas id="ph-canvas" hidden></canvas>
			<p><img id="ph-prev" alt="Resized photo preview" hidden> </p>
			<p><a class="np-btn np-btn--green" id="ph-dl" download="photo.jpg" hidden>Download</a></p>
		</div>
	</article>
</div>
<?php
get_footer();
