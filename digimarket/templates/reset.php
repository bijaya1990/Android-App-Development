<?php
/**
 * Reset password (from emailed link).
 *
 * @package DigiMarket
 */

$dm_u  = absint( $_GET['u'] ?? 0 );
$dm_k  = sanitize_text_field( wp_unslash( $_GET['k'] ?? '' ) );
$dm_ok = dm_reset_token_valid( $dm_u, $dm_k );
get_header();
?>
<div class="dm-auth">
	<div class="dm-auth-card dm-card">
		<?php if ( $dm_ok ) : ?>
			<h1><?php esc_html_e( 'Choose a new password', 'digimarket' ); ?></h1>
			<form method="post" class="dm-form">
				<?php dm_nonce_field( 'reset' ); ?>
				<input type="hidden" name="u" value="<?php echo (int) $dm_u; ?>"><input type="hidden" name="k" value="<?php echo esc_attr( $dm_k ); ?>">
				<label><?php esc_html_e( 'New password', 'digimarket' ); ?><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
				<label><?php esc_html_e( 'Confirm new password', 'digimarket' ); ?><input type="password" name="password2" minlength="8" autocomplete="new-password" required></label>
				<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block"><?php esc_html_e( 'Update password', 'digimarket' ); ?></button>
			</form>
		<?php else : ?>
			<h1><?php esc_html_e( 'Link expired', 'digimarket' ); ?></h1>
			<p class="dm-muted"><?php esc_html_e( 'This password reset link is invalid or has expired.', 'digimarket' ); ?></p>
			<a class="dm-btn dm-btn-primary dm-btn-block" href="<?php echo esc_url( dm_url( 'forgot' ) ); ?>"><?php esc_html_e( 'Request a new link', 'digimarket' ); ?></a>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
