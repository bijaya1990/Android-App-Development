<?php
/**
 * Email verification landing (successful links redirect before this renders).
 *
 * @package DigiMarket
 */

get_header();
?>
<div class="dm-auth">
	<div class="dm-auth-card dm-card">
		<h1><?php esc_html_e( 'Verify your email', 'digimarket' ); ?></h1>
		<?php if ( ! empty( $GLOBALS['dm_verify_failed'] ) ) : ?>
			<p class="dm-notice dm-notice-error"><?php esc_html_e( 'This verification link is invalid or has expired.', 'digimarket' ); ?></p>
		<?php endif; ?>
		<?php if ( is_user_logged_in() ) : ?>
			<?php if ( dm_email_verified( get_current_user_id() ) ) : ?>
				<p><?php esc_html_e( 'Your email is verified. You’re all set!', 'digimarket' ); ?></p>
				<a class="dm-btn dm-btn-primary dm-btn-block" href="<?php echo esc_url( dm_url( 'account' ) ); ?>"><?php esc_html_e( 'Go to my account', 'digimarket' ); ?></a>
			<?php else : ?>
				<p class="dm-muted"><?php echo esc_html( sprintf( /* translators: %s email */ __( 'We sent a link to %s. Click it to verify your address.', 'digimarket' ), wp_get_current_user()->user_email ) ); ?></p>
				<form method="post"><?php dm_nonce_field( 'resend_verification' ); ?><button class="dm-btn dm-btn-primary dm-btn-block"><?php esc_html_e( 'Resend verification email', 'digimarket' ); ?></button></form>
			<?php endif; ?>
		<?php else : ?>
			<a class="dm-btn dm-btn-primary dm-btn-block" href="<?php echo esc_url( dm_url( 'login' ) ); ?>"><?php esc_html_e( 'Log in', 'digimarket' ); ?></a>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
