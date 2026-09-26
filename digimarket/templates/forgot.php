<?php
/**
 * Forgot password.
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-auth">
	<div class="dm-auth-card dm-card">
		<h1><?php esc_html_e( 'Forgot your password?', 'digimarket' ); ?></h1>
		<p class="dm-muted"><?php esc_html_e( 'Enter your email and we’ll send a reset link (valid for 30 minutes).', 'digimarket' ); ?></p>
		<form method="post" class="dm-form">
			<?php dm_nonce_field( 'forgot' ); ?>
			<label><?php esc_html_e( 'Email', 'digimarket' ); ?><input type="email" name="email" required autofocus></label>
			<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" type="submit"><?php esc_html_e( 'Send reset link', 'digimarket' ); ?></button>
		</form>
		<p class="dm-auth-alt"><a href="<?php echo esc_url( dm_url( 'login' ) ); ?>">← <?php esc_html_e( 'Back to log in', 'digimarket' ); ?></a></p>
	</div>
</div>
<?php
get_footer();
