<?php
/**
 * DigiMarket — Multi-Vendor Digital Products Marketplace theme.
 *
 * Everything (sellers, shops, products, cart, Razorpay Route split payments,
 * secure downloads, dashboards, admin console) is bundled in this theme.
 * No plugin is required.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

define( 'DM_VERSION', '1.0.0' );
define( 'DM_DB_VERSION', '1.0.0' );
define( 'DM_DIR', get_template_directory() );
define( 'DM_URI', get_template_directory_uri() );

$dm_includes = array(
	'helpers',
	'install',
	'setup',
	'routes',
	'products',
	'auth',
	'seller',
	'cart',
	'payments',
	'delivery',
	'reviews',
	'buyer',
	'emails',
	'admin',
);

foreach ( $dm_includes as $dm_file ) {
	require_once DM_DIR . '/inc/' . $dm_file . '.php';
}
