<?php
/**
 * Plugin Name:       Pikacart Core
 * Plugin URI:        https://pikacart.in
 * Description:       Pikacart ID card platform: accounts, free trial, Razorpay subscriptions, organisation dashboard and Super Admin control room.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Pikacart
 * Author URI:        https://pikacart.in
 * License:           GPL-2.0-or-later
 * Text Domain:       pikacart
 * Domain Path:       /languages
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

define( 'PKC_VERSION', '1.0.0' );
define( 'PKC_DB_VERSION', 1 );
define( 'PKC_FILE', __FILE__ );
define( 'PKC_DIR', plugin_dir_path( __FILE__ ) );
define( 'PKC_URL', plugin_dir_url( __FILE__ ) );

require_once PKC_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'PKC_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PKC_Installer', 'deactivate' ) );

PKC_Plugin::instance();
