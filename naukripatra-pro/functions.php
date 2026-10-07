<?php
/**
 * NaukriPatra Pro - bootstrap.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NP_VERSION', '1.0.0' );
define( 'NP_DIR', get_stylesheet_directory() );
define( 'NP_URI', get_stylesheet_directory_uri() );

require_once NP_DIR . '/inc/settings.php';   // Option defaults + np_opt() helper.
require_once NP_DIR . '/inc/setup.php';      // Theme supports, menus, assets, head cleanup, security.
require_once NP_DIR . '/inc/defaults.php';   // Auto-created categories, pages, menus.
