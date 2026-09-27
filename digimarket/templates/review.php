<?php
/**
 * /review/{token}/ — verified client review form (no account needed).
 *
 * @package DigiMarket
 */

get_header();

$dm_row = dm_review_request_row( (string) get_query_var( 'dm_tab' ) );
?>
<div class="dm-auth">
	<div class="dm-card dm-auth-card dm-review-card">
		<?php if ( ! $dm_row ) : ?>
			<h1><?php esc_html_e( 'This review link has expired', 'digimarket' ); ?></h1>
			<p class="dm-muted"><?php esc_html_e( 'Review links work once and expire after 30 days. Ask us on WhatsApp for a new one.', 'digimarket' ); ?></p>
			<a class="dm-btn dm-btn-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to homepage', 'digimarket' ); ?></a>
		<?php else : ?>
			<div class="dm-review-card-head">
				<?php echo dm_product_thumb( $dm_row->product_id, 'thumbnail' ); // phpcs:ignore ?>
				<div><span class="dm-kicker"><?php esc_html_e( 'Rate our work', 'digimarket' ); ?></span><h1><?php echo esc_html( get_the_title( $dm_row->product_id ) ); ?></h1></div>
			</div>
			<p class="dm-muted"><?php echo esc_html( sprintf( /* translators: %s */ __( 'Hi %s, thank you for working with us! Your honest review helps other schools, committees and shops choose with confidence.', 'digimarket' ), $dm_row->client_name ) ); ?></p>
			<form method="post" enctype="multipart/form-data" class="dm-form">
				<?php dm_nonce_field( 'client_review' ); ?>
				<input type="hidden" name="token" value="<?php echo esc_attr( $dm_row->token ); ?>">
				<fieldset class="dm-star-input">
					<legend><?php esc_html_e( 'Your rating', 'digimarket' ); ?> *</legend>
					<?php for ( $dm_s = 5; $dm_s >= 1; $dm_s-- ) : ?>
						<input type="radio" id="dm-cstar-<?php echo (int) $dm_s; ?>" name="rating" value="<?php echo (int) $dm_s; ?>" <?php checked( 5, $dm_s ); ?>><label for="dm-cstar-<?php echo (int) $dm_s; ?>" title="<?php echo (int) $dm_s; ?>">★</label>
					<?php endfor; ?>
				</fieldset>
				<label><?php esc_html_e( 'Your name (shown publicly)', 'digimarket' ); ?><input type="text" name="display_name" maxlength="80" value="<?php echo esc_attr( $dm_row->client_name ); ?>"></label>
				<label><?php esc_html_e( 'Title', 'digimarket' ); ?><input type="text" name="title" maxlength="100" placeholder="<?php esc_attr_e( 'e.g. Great website for our school', 'digimarket' ); ?>"></label>
				<label><?php esc_html_e( 'Your review', 'digimarket' ); ?><textarea name="comment" rows="4" maxlength="2000" placeholder="<?php esc_attr_e( 'How was the experience? What did you like?', 'digimarket' ); ?>"></textarea></label>
				<label><?php esc_html_e( 'Photo of your website (optional)', 'digimarket' ); ?><input type="file" name="photo" accept="image/*"></label>
				<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block"><?php esc_html_e( 'Publish review', 'digimarket' ); ?></button>
				<p class="dm-muted dm-small"><?php esc_html_e( 'Your review appears with a “Verified client” badge. We can reply to it, but we can never change your words or rating.', 'digimarket' ); ?></p>
			</form>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
