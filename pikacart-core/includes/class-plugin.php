<?php
/**
 * Main plugin loader. Loads every file and wires the hooks.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

final class PKC_Plugin {

	/** @var PKC_Plugin|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		// Upgrades run on init so translations are ready (WordPress 6.7+ requirement).
		add_action( 'init', array( 'PKC_Installer', 'maybe_upgrade' ), 1 );
		add_action( 'init', array( 'PKC_Router', 'init' ) );
		add_action( 'init', array( 'PKC_Roles', 'init' ) );
		add_action( 'init', array( 'PKC_Shortcodes', 'init' ) );
		add_action( 'rest_api_init', array( 'PKC_REST', 'register_routes' ) );
		add_filter( 'rest_post_dispatch', array( 'PKC_REST', 'no_cache' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( 'PKC_Assets', 'register' ), 5 );
		add_action( 'admin_enqueue_scripts', array( 'PKC_Assets', 'register' ), 5 );
		add_filter( 'script_loader_tag', array( 'PKC_Assets', 'module_tag' ), 10, 3 );

		PKC_Cron::init();
		PKC_Emails::init();
		PKC_Public::init();
		PKC_SEO::init();

		if ( is_admin() ) {
			PKC_Admin::init();
		}
	}

	private function includes() {
		$files = array(
			'helpers.php',
			'class-installer.php',
			'class-settings.php',
			'class-roles.php',
			'class-activity-log.php',
			'class-rate-limit.php',
			'class-organisations.php',
			'class-access.php',
			'class-uploads.php',
			'class-notifications.php',
			'class-support.php',
			'class-catalog.php',
			'class-projects.php',
			'class-members.php',
			'class-designs.php',
			'class-coupons.php',
			'class-notices.php',
			'class-finance.php',
			'class-emails.php',
			'class-accounts.php',
			'class-razorpay.php',
			'class-billing.php',
			'class-webhooks.php',
			'class-cron.php',
			'class-assets.php',
			'class-router.php',
			'class-shortcodes.php',
			'class-stats.php',
			'class-public.php',
			'class-seo.php',
			'rest/class-rest.php',
			'rest/class-rest-auth.php',
			'rest/class-rest-account.php',
			'rest/class-rest-billing.php',
			'rest/class-rest-public.php',
			'rest/class-rest-support.php',
			'rest/class-rest-cards.php',
			'rest/class-rest-members.php',
			'rest/class-rest-designs.php',
			'admin/class-admin.php',
			'admin/class-admin-dashboard.php',
			'admin/class-admin-accounts.php',
			'admin/class-admin-payments.php',
			'admin/class-admin-support.php',
			'admin/class-admin-settings.php',
			'admin/class-admin-templates.php',
			'admin/class-admin-requests.php',
			'admin/class-admin-catalog.php',
			'admin/class-admin-finance.php',
			'admin/class-admin-notices.php',
			'admin/class-admin-reports.php',
			'admin/class-admin-activity.php',
		);
		foreach ( $files as $file ) {
			require_once PKC_DIR . 'includes/' . $file;
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'pikacart', false, dirname( plugin_basename( PKC_FILE ) ) . '/languages' );
	}
}
