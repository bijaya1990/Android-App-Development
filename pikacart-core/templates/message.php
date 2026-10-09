<?php
/**
 * Simple full-page message (suspended, maintenance, email verified...).
 * Variables: $pkc['title'], $pkc['message'], $pkc['icon'], optional $pkc['button'] = [label, url].
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_title  = $pkc['title'];
$pkc_styles = array( 'pkc-auth' );
require PKC_DIR . 'templates/partials/head.php';
?>
<body class="pkc pkc-message-page">
<?php require PKC_DIR . 'templates/icons.php'; ?>
<main class="msg-wrap">
	<div class="msg-card">
		<?php echo pkc_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="msg-icon msg-icon-<?php echo esc_attr( $pkc['icon'] ); ?>"><svg class="pkc-i"><use href="#i-<?php echo esc_attr( $pkc['icon'] ); ?>"></use></svg></div>
		<h1><?php echo esc_html( $pkc['title'] ); ?></h1>
		<p><?php echo esc_html( $pkc['message'] ); ?></p>
		<?php if ( ! empty( $pkc['button'] ) ) : ?>
			<a class="btn btn-primary" href="<?php echo esc_url( $pkc['button'][1] ); ?>"><?php echo esc_html( $pkc['button'][0] ); ?></a>
		<?php endif; ?>
		<p class="msg-help"><a href="mailto:<?php echo esc_attr( pkc_support_email() ); ?>"><?php echo esc_html( pkc_support_email() ); ?></a></p>
	</div>
</main>
</body>
</html>
