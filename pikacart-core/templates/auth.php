<?php
/**
 * Login, register, forgot password and reset password pages.
 * Variable: $pkc['view'].
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_view   = $pkc['view'];
$pkc_titles = array(
	'login'           => __( 'Log in', 'pikacart' ),
	'register'        => PKC_Access::free_forever() ? __( 'Create your free account', 'pikacart' ) : __( 'Start your free trial', 'pikacart' ),
	'forgot-password' => __( 'Forgot password', 'pikacart' ),
	'reset-password'  => __( 'Choose a new password', 'pikacart' ),
);
$pkc_title  = $pkc_titles[ $pkc_view ];
$pkc_styles = array( 'pkc-auth' );
$pkc_terms  = pkc_page_url( 'terms' );
$pkc_aup    = pkc_page_url( 'aup' );
$pkc_trial  = (int) pkc_setting( 'trial_minutes', 120 );
$pkc_trial_text = ( 0 === $pkc_trial % 60 )
	/* translators: %d: hours */
	? sprintf( _n( '%d hour', '%d hours', $pkc_trial / 60, 'pikacart' ), $pkc_trial / 60 )
	/* translators: %d: minutes */
	: sprintf( _n( '%d minute', '%d minutes', $pkc_trial, 'pikacart' ), $pkc_trial );

$pkc_redirect = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

PKC_Assets::print_config(
	'pkc-auth',
	array(
		'view'     => $pkc_view,
		'redirect' => $pkc_redirect,
	)
);

require PKC_DIR . 'templates/partials/head.php';
?>
<body class="pkc pkc-auth-page">
<?php require PKC_DIR . 'templates/icons.php'; ?>
<div class="auth">
	<aside class="auth-art" aria-hidden="true">
		<div class="auth-art-inner">
			<?php echo pkc_logo_html( false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<p class="auth-tag"><?php echo esc_html( pkc_setting( 'tagline', 'Expert in ID Card Industry' ) ); ?></p>
			<h2><?php esc_html_e( 'Beautiful ID cards for your whole organisation, ready to print.', 'pikacart' ); ?></h2>
			<ul class="auth-points">
				<li><svg class="pkc-i"><use href="#i-check"></use></svg><?php esc_html_e( 'Hundreds of professional designs', 'pikacart' ); ?></li>
				<li><svg class="pkc-i"><use href="#i-check"></use></svg><?php esc_html_e( 'Import students or staff from Excel', 'pikacart' ); ?></li>
				<li><svg class="pkc-i"><use href="#i-check"></use></svg><?php esc_html_e( 'Real QR verification on every card', 'pikacart' ); ?></li>
				<li><svg class="pkc-i"><use href="#i-check"></use></svg><?php esc_html_e( 'Print sheets with cut marks', 'pikacart' ); ?></li>
			</ul>
			<div class="card-fan">
				<?php foreach ( array( 'a', 'b', 'c' ) as $pkc_c ) : ?>
				<div class="demo-card demo-card-<?php echo esc_attr( $pkc_c ); ?>">
					<div class="dc-head"><span class="dc-logo"></span><span class="dc-org"></span></div>
					<div class="dc-photo"></div>
					<div class="dc-name"></div>
					<div class="dc-line"></div>
					<div class="dc-line short"></div>
					<div class="dc-foot"><span class="dc-qr"></span><span class="dc-bar"></span></div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</aside>

	<main class="auth-main">
		<div class="auth-top">
			<span class="auth-mobile-logo"><?php echo pkc_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<a class="auth-back" href="<?php echo esc_url( home_url( '/' ) ); ?>"><svg class="pkc-i"><use href="#i-chevron-left"></use></svg><?php esc_html_e( 'Back to website', 'pikacart' ); ?></a>
		</div>

		<div class="auth-box">
			<h1><?php echo esc_html( $pkc_title ); ?></h1>

			<?php if ( 'login' === $pkc_view ) : ?>
				<p class="auth-sub"><?php esc_html_e( 'Welcome back. Log in to manage your ID cards.', 'pikacart' ); ?></p>
				<form class="pkc-form" data-endpoint="auth/login" novalidate>
					<div class="field">
						<label for="email"><?php esc_html_e( 'Email', 'pikacart' ); ?></label>
						<input type="email" id="email" name="email" autocomplete="email" required>
					</div>
					<div class="field">
						<label for="password"><?php esc_html_e( 'Password', 'pikacart' ); ?></label>
						<div class="pw">
							<input type="password" id="password" name="password" autocomplete="current-password" required>
							<button type="button" class="pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'pikacart' ); ?>"><svg class="pkc-i"><use href="#i-eye"></use></svg></button>
						</div>
					</div>
					<div class="row-between">
						<label class="check"><input type="checkbox" name="remember" value="1" checked> <?php esc_html_e( 'Keep me logged in', 'pikacart' ); ?></label>
						<a href="<?php echo esc_url( pkc_url( 'forgot-password' ) ); ?>"><?php esc_html_e( 'Forgot password?', 'pikacart' ); ?></a>
					</div>
					<div class="form-msg" role="alert" aria-live="polite"></div>
					<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Log in', 'pikacart' ); ?></button>
				</form>
				<p class="auth-switch"><?php esc_html_e( 'New to Pikacart?', 'pikacart' ); ?> <a href="<?php echo esc_url( pkc_url( 'register' ) ); ?>"><?php esc_html_e( 'Create a free account', 'pikacart' ); ?></a></p>

			<?php elseif ( 'register' === $pkc_view ) : ?>
				<p class="auth-sub">
					<?php
					echo esc_html(
						PKC_Access::free_forever()
							? __( 'Free forever. Every feature included. No card needed.', 'pikacart' )
							/* translators: %s: trial length like "2 hours" */
							: sprintf( __( 'Every feature free for %s. No card needed.', 'pikacart' ), $pkc_trial_text )
					);
					?>
				</p>
				<form class="pkc-form" data-endpoint="auth/register" novalidate>
					<div class="field">
						<label for="org_name"><?php esc_html_e( 'Organisation name', 'pikacart' ); ?></label>
						<input type="text" id="org_name" name="org_name" autocomplete="organization" placeholder="<?php esc_attr_e( 'e.g. Sunrise Public School', 'pikacart' ); ?>" required>
					</div>
					<div class="field">
						<label for="contact_name"><?php esc_html_e( 'Contact person', 'pikacart' ); ?></label>
						<input type="text" id="contact_name" name="contact_name" autocomplete="name" required>
					</div>
					<div class="grid-2">
						<div class="field">
							<label for="email"><?php esc_html_e( 'Email', 'pikacart' ); ?></label>
							<input type="email" id="email" name="email" autocomplete="email" required>
						</div>
						<div class="field">
							<label for="mobile"><?php esc_html_e( 'Mobile number', 'pikacart' ); ?></label>
							<input type="tel" id="mobile" name="mobile" autocomplete="tel" inputmode="tel" placeholder="98765 43210" required>
						</div>
					</div>
					<div class="field">
						<label for="password"><?php esc_html_e( 'Password', 'pikacart' ); ?></label>
						<div class="pw">
							<input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
							<button type="button" class="pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'pikacart' ); ?>"><svg class="pkc-i"><use href="#i-eye"></use></svg></button>
						</div>
						<small class="hint"><?php esc_html_e( 'At least 8 characters.', 'pikacart' ); ?></small>
					</div>
					<div class="hp" aria-hidden="true">
						<label for="website"><?php esc_html_e( 'Leave this empty', 'pikacart' ); ?></label>
						<input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
					</div>
					<input type="hidden" name="stamp" value="<?php echo esc_attr( PKC_Accounts::form_stamp() ); ?>">
					<label class="check check-block">
						<input type="checkbox" name="authorised" value="1" required>
						<span>
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: terms link, 2: acceptable use link */
									__( 'I am authorised to issue ID cards for this organisation, and I agree to the <a href="%1$s" target="_blank">Terms</a> and <a href="%2$s" target="_blank">Acceptable Use Policy</a>.', 'pikacart' ),
									esc_url( $pkc_terms ? $pkc_terms : '#' ),
									esc_url( $pkc_aup ? $pkc_aup : '#' )
								),
								array(
									'a' => array(
										'href'   => array(),
										'target' => array(),
									),
								)
							);
							?>
						</span>
					</label>
					<div class="form-msg" role="alert" aria-live="polite"></div>
					<button type="submit" class="btn btn-primary btn-block"><?php echo esc_html( PKC_Access::free_forever() ? __( 'Create free account', 'pikacart' ) : __( 'Create account and start trial', 'pikacart' ) ); ?></button>
				</form>
				<p class="auth-switch"><?php esc_html_e( 'Already have an account?', 'pikacart' ); ?> <a href="<?php echo esc_url( pkc_url( 'login' ) ); ?>"><?php esc_html_e( 'Log in', 'pikacart' ); ?></a></p>

			<?php elseif ( 'forgot-password' === $pkc_view ) : ?>
				<p class="auth-sub"><?php esc_html_e( 'Enter your email and we will send you a link to choose a new password.', 'pikacart' ); ?></p>
				<form class="pkc-form" data-endpoint="auth/forgot" novalidate>
					<div class="field">
						<label for="email"><?php esc_html_e( 'Email', 'pikacart' ); ?></label>
						<input type="email" id="email" name="email" autocomplete="email" required>
					</div>
					<div class="form-msg" role="alert" aria-live="polite"></div>
					<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Send reset link', 'pikacart' ); ?></button>
				</form>
				<p class="auth-switch"><a href="<?php echo esc_url( pkc_url( 'login' ) ); ?>"><?php esc_html_e( 'Back to login', 'pikacart' ); ?></a></p>

			<?php else : ?>
				<p class="auth-sub"><?php esc_html_e( 'Choose a new password for your account.', 'pikacart' ); ?></p>
				<form class="pkc-form" data-endpoint="auth/reset" novalidate>
					<input type="hidden" name="login" value="<?php echo esc_attr( isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification ?>">
					<input type="hidden" name="key" value="<?php echo esc_attr( isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification ?>">
					<div class="field">
						<label for="password"><?php esc_html_e( 'New password', 'pikacart' ); ?></label>
						<div class="pw">
							<input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
							<button type="button" class="pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'pikacart' ); ?>"><svg class="pkc-i"><use href="#i-eye"></use></svg></button>
						</div>
						<small class="hint"><?php esc_html_e( 'At least 8 characters.', 'pikacart' ); ?></small>
					</div>
					<div class="form-msg" role="alert" aria-live="polite"></div>
					<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Save new password', 'pikacart' ); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<p class="auth-help">
			<?php
			/* translators: %s: support email */
			echo wp_kses( sprintf( __( 'Need help? Write to %s', 'pikacart' ), '<a href="mailto:' . esc_attr( pkc_support_email() ) . '">' . esc_html( pkc_support_email() ) . '</a>' ), array( 'a' => array( 'href' => array() ) ) );
			?>
		</p>
	</main>
</div>
<?php wp_print_scripts( array( 'pkc-auth' ) ); ?>
</body>
</html>
