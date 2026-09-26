<?php
/**
 * Registration page (buyers; sellers continue to /sell).
 *
 * @package DigiMarket
 */

get_header();
$dm_intent   = isset( $_GET['intent'] ) && 'seller' === $_GET['intent'] ? 'seller' : 'buyer';
$dm_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['redirect_to'] ) ) ) : '';
?>
<div class="dm-auth">
	<div class="dm-auth-card dm-card">
		<?php if ( 'seller' === $dm_intent ) : ?>
			<ol class="dm-steps"><li class="is-active"><?php esc_html_e( 'Account', 'digimarket' ); ?></li><li><?php esc_html_e( 'Shop', 'digimarket' ); ?></li><li><?php esc_html_e( 'Payouts', 'digimarket' ); ?></li><li><?php esc_html_e( 'Launch', 'digimarket' ); ?></li></ol>
		<?php endif; ?>
		<h1><?php echo 'seller' === $dm_intent ? esc_html__( 'Create your seller account', 'digimarket' ) : esc_html__( 'Create your account', 'digimarket' ); ?></h1>
		<p class="dm-muted"><?php esc_html_e( 'One account to buy from every shop — and open your own.', 'digimarket' ); ?></p>
		<form method="post" class="dm-form">
			<?php dm_nonce_field( 'register' ); ?>
			<input type="hidden" name="intent" value="<?php echo esc_attr( $dm_intent ); ?>">
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $dm_redirect ); ?>">
			<div class="dm-hp" aria-hidden="true"><label>Website<input type="text" name="dm_website" tabindex="-1" autocomplete="off"></label></div>
			<label><?php esc_html_e( 'Full name', 'digimarket' ); ?><input type="text" name="name" autocomplete="name" required></label>
			<label><?php esc_html_e( 'Email', 'digimarket' ); ?><input type="email" name="email" autocomplete="email" required></label>
			<label><?php esc_html_e( 'Phone (optional)', 'digimarket' ); ?><input type="tel" name="phone" autocomplete="tel" placeholder="+91"></label>
			<label><?php esc_html_e( 'Password', 'digimarket' ); ?><span class="dm-pass"><input type="password" name="password" minlength="8" autocomplete="new-password" required><button type="button" class="dm-pass-toggle" aria-label="<?php esc_attr_e( 'Show password', 'digimarket' ); ?>">👁</button></span><small class="dm-muted"><?php esc_html_e( 'At least 8 characters.', 'digimarket' ); ?></small></label>
			<label class="dm-check"><input type="checkbox" name="agree" value="1" required> <span><?php echo wp_kses_post( sprintf( /* translators: 1 terms 2 privacy */ __( 'I agree to the <a href="%1$s" target="_blank">Terms of Service</a> and <a href="%2$s" target="_blank">Privacy Policy</a>.', 'digimarket' ), esc_url( dm_legal_url( 'terms' ) ), esc_url( dm_legal_url( 'privacy' ) ) ) ); ?></span></label>
			<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" type="submit"><?php esc_html_e( 'Create account', 'digimarket' ); ?></button>
		</form>
		<p class="dm-auth-alt"><?php esc_html_e( 'Already have an account?', 'digimarket' ); ?> <a href="<?php echo esc_url( dm_url( 'login', '', '', 'seller' === $dm_intent ? array( 'redirect_to' => rawurlencode( dm_url( 'sell' ) ) ) : array() ) ); ?>"><?php esc_html_e( 'Log in', 'digimarket' ); ?></a></p>
	</div>
</div>
<?php
get_footer();
