<?php
/**
 * Comments.
 *
 * @package DigiMarket
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="dm-card dm-pad dm-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="dm-h3"><?php echo esc_html( sprintf( /* translators: %d */ _n( '%d comment', '%d comments', get_comments_number(), 'digimarket' ), get_comments_number() ) ); ?></h2>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?></ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
