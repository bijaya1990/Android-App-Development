<?php
/**
 * Public self-fill form: /fill/{token}
 * Students or staff fill their own details and photo; entries arrive as "Pending".
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_org    = $pkc['org'];
$pkc_title  = __( 'Fill your ID card details', 'pikacart' );
$pkc_styles = array( 'pkc-auth', 'pkc-cropper' );
PKC_Assets::register();
wp_register_style( 'pkc-cropper', PKC_URL . 'assets/vendor/cropper.min.css', array(), '1.6.2' );
wp_register_script( 'pkc-cropper', PKC_URL . 'assets/vendor/cropper.min.js', array(), '1.6.2', true );
wp_register_script( 'pkc-fill', PKC_URL . 'assets/js/fill.js', array( 'wp-i18n', 'pkc-cropper' ), PKC_VERSION, true );
wp_set_script_translations( 'pkc-fill', 'pikacart', PKC_DIR . 'languages' );
PKC_Assets::print_config(
	'pkc-fill',
	array(
		'token' => $pkc['token'],
	)
);
require PKC_DIR . 'templates/partials/head.php';
?>
<body class="pkc pkc-fill-page">
<?php require PKC_DIR . 'templates/icons.php'; ?>
<main class="fill-wrap">
	<div class="fill-card">
		<header class="fill-head">
			<?php if ( $pkc_org->logo ) : ?>
				<img src="<?php echo esc_url( $pkc_org->logo ); ?>" alt="">
			<?php endif; ?>
			<div>
				<h1><?php echo esc_html( $pkc_org->name ); ?></h1>
				<p><?php esc_html_e( 'Fill in your details for your ID card. Your institution will check and approve them.', 'pikacart' ); ?></p>
			</div>
		</header>
		<form id="fill-form" class="pkc-form" novalidate>
			<div id="fill-fields"><p class="hint"><?php esc_html_e( 'Loading…', 'pikacart' ); ?></p></div>
			<div class="field">
				<label for="fill-photo"><?php esc_html_e( 'Your photo', 'pikacart' ); ?></label>
				<p class="hint"><?php esc_html_e( 'A clear, front-facing passport-style photo with a plain background.', 'pikacart' ); ?></p>
				<input type="file" id="fill-photo" accept="image/png,image/jpeg,image/webp">
				<div class="fill-crop" id="fill-crop" hidden><img id="fill-crop-img" alt=""></div>
			</div>
			<div class="hp" aria-hidden="true"><label for="fill-web"><?php esc_html_e( 'Leave this empty', 'pikacart' ); ?></label><input id="fill-web" name="website" tabindex="-1" autocomplete="off"></div>
			<label class="check check-block">
				<input type="checkbox" name="consent" value="1" required>
				<span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: organisation name */
							__( 'I agree that %s may use these details and photo only to make and verify my ID card. If I am under 18, my parent or guardian agrees too.', 'pikacart' ),
							$pkc_org->name
						)
					);
					?>
				</span>
			</label>
			<div class="form-msg" role="alert" aria-live="polite"></div>
			<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Send my details', 'pikacart' ); ?></button>
		</form>
		<p class="msg-help"><?php esc_html_e( 'Powered by Pikacart', 'pikacart' ); ?></p>
	</div>
</main>
<?php wp_print_scripts( array( 'pkc-fill' ) ); ?>
</body>
</html>
