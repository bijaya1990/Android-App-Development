<?php
/**
 * Registers CSS and JavaScript. Files load only on the screens that need them.
 * All libraries are bundled locally; nothing depends on a CDN.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Assets {

	const MODULES = array( 'pkc-app' );

	/** @var bool */
	private static $done = false;

	public static function register() {
		if ( self::$done ) {
			return;
		}
		self::$done = true;
		$v          = PKC_VERSION;
		$u = PKC_URL . 'assets/';

		wp_register_style( 'pkc-tokens', $u . 'css/tokens.css', array(), $v );
		wp_add_inline_style( 'pkc-tokens', self::brand_css() );

		wp_register_style( 'pkc-auth', $u . 'css/auth.css', array( 'pkc-tokens' ), $v );
		wp_register_script( 'pkc-auth', $u . 'js/auth.js', array( 'wp-i18n' ), $v, true );
		wp_set_script_translations( 'pkc-auth', 'pikacart', PKC_DIR . 'languages' );

		wp_register_style( 'pkc-app', $u . 'css/app.css', array( 'pkc-tokens' ), $v );
		wp_register_script( 'pkc-jspdf', $u . 'vendor/jspdf.umd.min.js', array(), '2.5.2', true );
		wp_register_script( 'pkc-app', $u . 'js/app/main.js', array( 'wp-i18n' ), $v, true );
		wp_set_script_translations( 'pkc-app', 'pikacart', PKC_DIR . 'languages' );

		// Card designer libraries (all bundled locally).
		wp_register_style( 'pkc-card-fonts', $u . 'css/card-fonts.css', array(), $v );
		wp_register_style( 'pkc-cropper', $u . 'vendor/cropper.min.css', array(), '1.6.2' );
		wp_register_script( 'pkc-qrcode', $u . 'vendor/qrcode.js', array(), '1.4.4', true );
		wp_register_script( 'pkc-barcode', $u . 'vendor/JsBarcode.code128.min.js', array(), '3.11.6', true );
		wp_register_script( 'pkc-jszip', $u . 'vendor/jszip.min.js', array(), '3.10.1', true );
		wp_register_script( 'pkc-xlsx', $u . 'vendor/xlsx.mini.min.js', array(), '0.18.5', true );
		wp_register_script( 'pkc-cropper', $u . 'vendor/cropper.min.js', array(), '1.6.2', true );

		wp_register_style( 'pkc-public', $u . 'css/public.css', array( 'pkc-tokens' ), $v );
		wp_register_script( 'pkc-public', $u . 'js/public.js', array( 'wp-i18n' ), $v, true );
		wp_set_script_translations( 'pkc-public', 'pikacart', PKC_DIR . 'languages' );

		wp_register_style( 'pkc-admin', $u . 'css/admin.css', array( 'pkc-tokens' ), $v );
		wp_register_script( 'pkc-chart', $u . 'vendor/chart.umd.min.js', array(), '4.4.4', true );
		wp_register_script( 'pkc-admin', $u . 'js/admin/admin.js', array( 'wp-i18n' ), $v, true );
		wp_set_script_translations( 'pkc-admin', 'pikacart', PKC_DIR . 'languages' );
	}

	/**
	 * Brand colours chosen in Settings, as CSS variables.
	 */
	public static function brand_css() {
		$brand  = sanitize_hex_color( (string) pkc_setting( 'brand_color', '#4F46E5' ) );
		$accent = sanitize_hex_color( (string) pkc_setting( 'accent_color', '#F97316' ) );
		$brand  = $brand ? $brand : '#4F46E5';
		$accent = $accent ? $accent : '#F97316';
		return ':root{--pkc-brand:' . $brand . ';--pkc-accent:' . $accent . ';}';
	}

	/**
	 * Load app scripts as ES modules.
	 */
	public static function module_tag( $tag, $handle, $src ) {
		if ( ! in_array( $handle, self::MODULES, true ) ) {
			return $tag;
		}
		// Only change the tag that loads the file, not inline config scripts.
		return preg_replace_callback(
			'/<script\b([^>]*\ssrc=[^>]*)>/i',
			function ( $m ) {
				$attrs = preg_replace( '/\stype=(["\'])[^"\']*\1/i', '', $m[1] );
				return '<script type="module"' . $attrs . '>';
			},
			$tag
		);
	}

	/**
	 * Data every Pikacart script needs.
	 */
	public static function base_config() {
		return array(
			'rest'     => esc_url_raw( rest_url( 'pkc/v1/' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'home'     => esc_url_raw( home_url( '/' ) ),
			'app'      => esc_url_raw( pkc_url( 'app' ) ),
			'login'    => esc_url_raw( pkc_url( 'login' ) ),
			'register' => esc_url_raw( pkc_url( 'register' ) ),
			'logout'   => esc_url_raw( wp_logout_url( pkc_url( 'login' ) ) ),
			'assets'   => esc_url_raw( PKC_URL . 'assets/' ),
			'site'     => pkc_setting( 'site_name', 'Pikacart' ),
			'support'  => pkc_support_email(),
			'phone'    => (string) pkc_setting( 'contact_phone', '' ),
			'uploadMb' => max( 1, (float) pkc_setting( 'upload_max_mb', 5 ) ),
		);
	}

	public static function print_config( $handle, $extra = array() ) {
		self::register();
		$config = array_merge( self::base_config(), $extra );
		wp_add_inline_script( $handle, 'window.PKC = ' . wp_json_encode( $config ) . ';', 'before' );
	}
}
