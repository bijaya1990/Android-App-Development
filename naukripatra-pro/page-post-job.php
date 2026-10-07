<?php
/**
 * Front-end Post Job form (slug: post-job). Saves a draft; admin approves in NaukriPatra Control.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$status = isset( $_GET['np_status'] ) ? sanitize_key( $_GET['np_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$msgs   = array(
	'ok' => 'Thank you! Your job was saved as a draft and will go live after approval.', 'ok-files' => 'Saved as a draft, but a file was rejected (wrong type or too large).',
	'rate' => 'Too many submissions. Please try again later.', 'title' => 'Please enter a title.', 'error' => 'Something went wrong. Please try again.',
);
$f = function ( $name, $label, $type = 'text', $help = '' ) {
	printf( '<p class="np-field"><label for="%1$s">%2$s</label><input type="%3$s" id="%1$s" name="%1$s" class="np-input">%4$s</p>', esc_attr( $name ), esc_html( $label ), esc_attr( $type ), $help ? '<small>' . esc_html( $help ) . '</small>' : '' );
};
?>
<div class="np-wrap">
	<article class="np-card np-prose">
		<h1><?php the_title(); ?></h1>
		<?php if ( $status && isset( $msgs[ $status ] ) ) : ?><p class="np-notice <?php echo 0 === strpos( $status, 'ok' ) ? 'is-ok' : 'is-err'; ?>" role="status"><?php echo esc_html( $msgs[ $status ] ); ?></p><?php endif; ?>
		<?php if ( ! np_user_may_post() ) : ?>
			<p><?php esc_html_e( 'Please log in to post a job.', 'naukripatra' ); ?> <a class="np-btn" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'naukripatra' ); ?></a></p>
		<?php else : ?>
		<form class="np-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-np-postform>
			<input type="hidden" name="action" value="np_submit_job">
			<?php wp_nonce_field( 'np_submit_job', 'np_nonce' ); ?>
			<p class="np-hp" aria-hidden="true"><label>Website <input type="text" name="np_website" tabindex="-1" autocomplete="off"></label></p>

			<fieldset class="np-fs"><legend><?php esc_html_e( 'Article', 'naukripatra' ); ?></legend>
				<?php $f( 'np_title', 'Title' ); ?>
				<p class="np-field"><label for="np_article"><?php esc_html_e( 'Full article (paste HTML or text)', 'naukripatra' ); ?></label><textarea id="np_article" name="np_article" rows="12" class="np-input"></textarea></p>
				<p><button type="button" class="np-btn np-btn--ghost np-btn--sm" data-np-autofill><?php esc_html_e( 'Auto-fill from overview', 'naukripatra' ); ?></button> <small><?php esc_html_e( 'Reads the overview table in your article and fills empty fields below.', 'naukripatra' ); ?></small></p>
				<?php $f( 'np_tags', 'Tags (comma separated)' ); ?>
			</fieldset>

			<?php
			np_cat_checklist( 'Sections', array( 'latest-jobs', 'admit-card', 'result', 'answer-key', 'syllabus', 'admission', 'scholarship-schemes' ) );
			np_cat_checklist( 'All India / States', array_merge( array( 'all-india' ), array_diff( np_location_slugs(), np_ut_slugs() ) ) );
			np_cat_checklist( 'Union Territories', np_ut_slugs() );
			?>

			<fieldset class="np-fs"><legend><?php esc_html_e( 'Schema / Overview (fill only to override auto-detected values)', 'naukripatra' ); ?></legend>
				<p class="np-field"><label for="o_sector">Job Sector</label><select id="o_sector" name="o_sector" class="np-input"><option value="">Auto</option><option>Government</option><option>Private</option></select></p>
				<?php
				$f( 'o_qualification', 'Qualification' );
				$f( 'o_last_date', 'Last Date' );
				$f( 'o_posts', 'No. of Posts' );
				$f( 'o_apply', 'Apply / Official Link' );
				$f( 'o_notification', 'Notification PDF Link' );
				$f( 'o_fee', 'Application Fee' );
				$f( 'o_advt', 'Advt No' );
				?>
			</fieldset>

			<fieldset class="np-fs"><legend><?php esc_html_e( 'API Data (flows straight to the app)', 'naukripatra' ); ?></legend>
				<?php foreach ( np_api_fields() as $k ) { $f( 'f_' . $k, ucwords( str_replace( '_', ' ', $k ) ) ); } ?>
			</fieldset>

			<fieldset class="np-fs"><legend>Yoast SEO</legend>
				<?php
				$f( 'y_slug', 'Slug' );
				$f( 'y_focuskw', 'Focus keyphrase' );
				$f( 'y_seo_title', 'SEO title', 'text', 'Aim for under 60 characters.' );
				$f( 'y_seo_desc', 'Meta description', 'text', 'Aim for 120-155 characters.' );
				$f( 'y_og_title', 'Facebook title' );
				$f( 'y_og_desc', 'Facebook description' );
				$f( 'y_tw_title', 'X (Twitter) title' );
				$f( 'y_tw_desc', 'X (Twitter) description' );
				$f( 'y_canonical', 'Canonical URL', 'url' );
				?>
				<div class="np-serp" aria-live="polite"><div class="np-serp__t" data-np-serp-t></div><div class="np-serp__u"><?php echo esc_html( home_url( '/' ) ); ?>...</div><div class="np-serp__d" data-np-serp-d></div></div>
				<small data-np-count></small>
			</fieldset>

			<fieldset class="np-fs"><legend><?php esc_html_e( 'Files', 'naukripatra' ); ?></legend>
				<p class="np-field"><label for="np_image">Featured image (JPG, PNG or WebP, max 2 MB)</label><input type="file" id="np_image" name="np_image" accept="image/jpeg,image/png,image/webp"></p>
				<p class="np-field"><label for="np_pdf">Notification PDF (max 5 MB)</label><input type="file" id="np_pdf" name="np_pdf" accept="application/pdf"></p>
			</fieldset>
			<p><button type="submit" class="np-btn"><?php esc_html_e( 'Submit for approval', 'naukripatra' ); ?></button></p>
		</form>
		<?php endif; ?>
	</article>
</div>
<?php
get_footer();
