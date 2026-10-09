<?php
/**
 * Organisation dashboard shell at /app/. Screens are drawn by assets/js/app/.
 * Variable: $pkc['org'].
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_org    = $pkc['org'];
$pkc_title  = __( 'Dashboard', 'pikacart' );
$pkc_styles = array( 'pkc-app', 'pkc-card-fonts', 'pkc-cropper' );
$pkc_nav    = array(
	array( '', 'home', __( 'Dashboard', 'pikacart' ) ),
	array( 'create', 'plus', __( 'Create ID card', 'pikacart' ) ),
	array( 'projects', 'folder', __( 'My Projects', 'pikacart' ) ),
	array( 'members', 'users', __( 'Members', 'pikacart' ) ),
	array( 'print', 'printer', __( 'Print Sheets', 'pikacart' ) ),
	array( 'designs', 'palette', __( 'My Designs', 'pikacart' ) ),
	array( 'organisation', 'building', __( 'Organisation', 'pikacart' ) ),
	array( 'subscription', 'card', __( 'Subscription', 'pikacart' ) ),
	array( 'support', 'help', __( 'Support', 'pikacart' ) ),
	array( 'account', 'user', __( 'Account', 'pikacart' ) ),
);

PKC_Assets::register();
wp_enqueue_script( 'pkc-jspdf' );
PKC_Assets::print_config(
	'pkc-app',
	array(
		'boot' => PKC_REST_Account::me_payload( $pkc_org ),
	)
);

require PKC_DIR . 'templates/partials/head.php';
?>
<body class="pkc pkc-app-page">
<?php require PKC_DIR . 'templates/icons.php'; ?>
<a class="skip-link" href="#view"><?php esc_html_e( 'Skip to content', 'pikacart' ); ?></a>
<div class="app" id="pkc-app">
	<aside class="sidebar" id="sidebar" aria-label="<?php esc_attr_e( 'Main menu', 'pikacart' ); ?>">
		<div class="sidebar-head">
			<?php echo pkc_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<button type="button" class="icon-btn sidebar-close" data-action="close-menu" aria-label="<?php esc_attr_e( 'Close menu', 'pikacart' ); ?>"><svg class="pkc-i"><use href="#i-x"></use></svg></button>
		</div>
		<nav class="nav">
			<?php foreach ( $pkc_nav as $pkc_item ) : ?>
				<a class="nav-link" href="<?php echo esc_url( pkc_url( 'app', $pkc_item[0] ) ); ?>" data-route="<?php echo esc_attr( $pkc_item[0] ); ?>">
					<svg class="pkc-i"><use href="#i-<?php echo esc_attr( $pkc_item[1] ); ?>"></use></svg>
					<span><?php echo esc_html( $pkc_item[2] ); ?></span>
					<?php if ( 'support' === $pkc_item[0] ) : ?>
						<span class="nav-badge" id="support-badge" hidden></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<div class="sidebar-foot">
			<a class="nav-link" href="<?php echo esc_url( wp_logout_url( pkc_url( 'login' ) ) ); ?>">
				<svg class="pkc-i"><use href="#i-logout"></use></svg><span><?php esc_html_e( 'Log out', 'pikacart' ); ?></span>
			</a>
		</div>
	</aside>
	<div class="scrim" data-action="close-menu"></div>

	<div class="main">
		<header class="topbar">
			<button type="button" class="icon-btn menu-btn" data-action="open-menu" aria-label="<?php esc_attr_e( 'Open menu', 'pikacart' ); ?>"><svg class="pkc-i"><use href="#i-menu"></use></svg></button>
			<h1 class="page-title" id="page-title"><?php esc_html_e( 'Dashboard', 'pikacart' ); ?></h1>
			<div class="topbar-right">
				<a class="status-pill" id="status-pill" href="<?php echo esc_url( pkc_url( 'app', 'subscription' ) ); ?>" data-link hidden></a>
				<div class="bell">
					<button type="button" class="icon-btn bell-btn" data-action="toggle-bell" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'Notifications', 'pikacart' ); ?>">
						<svg class="pkc-i"><use href="#i-bell"></use></svg>
						<span class="bell-count" id="bell-count" hidden></span>
					</button>
					<div class="bell-panel" id="bell-panel" hidden>
						<div class="bell-head">
							<strong><?php esc_html_e( 'Notifications', 'pikacart' ); ?></strong>
							<button type="button" class="btn btn-ghost btn-sm" data-action="read-all"><?php esc_html_e( 'Mark all read', 'pikacart' ); ?></button>
						</div>
						<div class="bell-list" id="bell-list"></div>
					</div>
				</div>
				<div class="profile">
					<button type="button" class="profile-btn" data-action="toggle-profile" aria-haspopup="true" aria-expanded="false">
						<span class="avatar" id="avatar"></span>
						<span class="profile-name" id="profile-name"></span>
						<svg class="pkc-i"><use href="#i-chevron-down"></use></svg>
					</button>
					<div class="profile-menu" id="profile-menu" hidden>
						<a href="<?php echo esc_url( pkc_url( 'app', 'account' ) ); ?>" data-route="account"><svg class="pkc-i"><use href="#i-user"></use></svg><?php esc_html_e( 'Account', 'pikacart' ); ?></a>
						<a href="<?php echo esc_url( pkc_url( 'app', 'organisation' ) ); ?>" data-route="organisation"><svg class="pkc-i"><use href="#i-building"></use></svg><?php esc_html_e( 'Organisation', 'pikacart' ); ?></a>
						<a href="<?php echo esc_url( pkc_url( 'app', 'subscription' ) ); ?>" data-route="subscription"><svg class="pkc-i"><use href="#i-card"></use></svg><?php esc_html_e( 'Subscription', 'pikacart' ); ?></a>
						<a href="<?php echo esc_url( wp_logout_url( pkc_url( 'login' ) ) ); ?>"><svg class="pkc-i"><use href="#i-logout"></use></svg><?php esc_html_e( 'Log out', 'pikacart' ); ?></a>
					</div>
				</div>
			</div>
		</header>
		<div id="banners"></div>
		<main class="view" id="view" tabindex="-1">
			<div class="skeleton-grid">
				<div class="skeleton sk-card"></div><div class="skeleton sk-card"></div><div class="skeleton sk-card"></div>
				<div class="skeleton sk-wide"></div>
			</div>
		</main>
	</div>
</div>
<div class="toasts" id="toasts" aria-live="polite" aria-atomic="false"></div>
<div id="modal-root"></div>
<noscript><p class="noscript"><?php esc_html_e( 'Please turn on JavaScript to use the Pikacart dashboard.', 'pikacart' ); ?></p></noscript>
<?php wp_print_scripts( array( 'pkc-jspdf', 'pkc-qrcode', 'pkc-barcode', 'pkc-jszip', 'pkc-xlsx', 'pkc-cropper', 'pkc-app' ) ); ?>
</body>
</html>
