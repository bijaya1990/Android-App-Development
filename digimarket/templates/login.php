<?php
/**
 * Login page.
 *
 * @package DigiMarket
 */

get_header();
$dm_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['redirect_to'] ) ) ) : '';
?>
<div class="dm-auth">
	<div class="dm-auth-card dm-card">
		<h1><?php esc_html_e( 'Welcome back', 'digimarket' ); ?></h1>
		<p class="dm-muted"><?php esc_html_e( 'Log in to buy, download and manage your shop.', 'digimarket' ); ?></p>
		<form method="post" class="dm-form" novalidate>
			<?php dm_nonce_field( 'login' ); ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $dm_redirect ); ?>">
			<label><?php esc_html_e( 'Email', 'digimarket' ); ?><input type="text" name="log" autocomplete="username" required autofocus></label>
			<label><?php esc_html_e( 'Password', 'digimarket' ); ?><span class="dm-pass"><input type="password" name="pwd" autocomplete="current-password" required><button type="button" class="dm-pass-toggle" aria-label="<?php esc_attr_e( 'Show password', 'digimarket' ); ?>">👁</button></span></label>
			<?php if ( dm_opt( 'admin_2fa' ) ) : ?>
				<details><summary class="dm-small"><?php esc_html_e( 'Admin verification code', 'digimarket' ); ?></summary><label><input type="text" name="dm_otp" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"></label></details>
			<?php endif; ?>
			<div class="dm-form-row-between">
				<label class="dm-check"><input type="checkbox" name="rememberme" value="1" checked> <?php esc_html_e( 'Remember me', 'digimarket' ); ?></label>
				<a href="<?php echo esc_url( dm_url( 'forgot' ) ); ?>"><?php esc_html_e( 'Forgot password?', 'digimarket' ); ?></a>
			</div>
			<button class="dm-btn dm-btn-primary dm-btn-lg dm-btn-block" type="submit"><?php esc_html_e( 'Log in', 'digimarket' ); ?></button>
		</form>
		<p class="dm-auth-alt"><?php esc_html_e( 'New here?', 'digimarket' ); ?> <a href="<?php echo esc_url( dm_url( 'register', '', '', $dm_redirect ? array( 'redirect_to' => rawurlencode( $dm_redirect ) ) : array() ) ); ?>"><?php esc_html_e( 'Create an account', 'digimarket' ); ?></a></p>
	</div>
</div>
<?php
get_footer();
