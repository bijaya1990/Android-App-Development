<?php
/**
 * Shows a clear notice when the Pikacart Core plugin is not active.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

function pkc_theme_plugin_active() {
	return class_exists( 'PKC_Plugin' );
}

add_action( 'admin_notices', 'pkc_theme_plugin_notice' );
function pkc_theme_plugin_notice() {
	if ( pkc_theme_plugin_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Pikacart theme:', 'pikacart' ) . '</strong> ' . esc_html__( 'Please install and activate the "Pikacart Core" plugin. Accounts, the dashboard and payments need it.', 'pikacart' ) . ' <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Go to Plugins', 'pikacart' ) . '</a></p></div>';
}

/**
 * Banner on the public site for logged-in admins only.
 */
add_action( 'wp_body_open', 'pkc_theme_plugin_banner' );
function pkc_theme_plugin_banner() {
	if ( pkc_theme_plugin_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="pkc-plugin-missing">' . esc_html__( 'Pikacart Core plugin is not active. Visitors cannot register or log in until you activate it.', 'pikacart' ) . ' <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Activate now', 'pikacart' ) . '</a></div>';
}
