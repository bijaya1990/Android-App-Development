<?php
/**
 * Signature Scanner page template (runs fully in the browser; nothing is uploaded).
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
		<div class="np-tool-ui" id="np-sign">
			<p class="np-field"><label for="sg-file">Choose signature photo</label><input type="file" id="sg-file" accept="image/*"></p>
			<div class="np-grid2">
				<p class="np-field"><label for="sg-th">Ink threshold</label><input type="range" id="sg-th" min="40" max="230" value="150"></p>
				<p class="np-field"><label for="sg-ct">Contrast</label><input type="range" id="sg-ct" min="0" max="100" value="60"></p>
				<p class="np-field"><label for="sg-w">Width (px)</label><input type="number" id="sg-w" class="np-input" value="140" min="10" max="2000"></p>
				<p class="np-field"><label for="sg-h">Height (px)</label><input type="number" id="sg-h" class="np-input" value="60" min="10" max="2000"></p>
				<p class="np-field"><label for="sg-max">Max size (KB)</label><input type="number" id="sg-max" class="np-input" value="20" min="1"></p>
			</div>
			<p><label><input type="checkbox" id="sg-crop" checked> Auto-crop empty space</label></p>
			<canvas id="sg-canvas"></canvas>
			<p id="sg-info" role="status"></p>
			<p><a class="np-btn np-btn--green" id="sg-dl" download="signature.jpg" hidden>Download</a></p>
		</div>
	</article>
</div>
<?php
get_footer();
