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

$np_files = array( 'settings', 'defaults', 'helpers', 'setup', 'yoast', 'rest', 'schema', 'lists', 'seo', 'ads', 'content', 'post-form', 'template-tags' );
foreach ( $np_files as $np_file ) {
	require_once NP_DIR . '/inc/' . $np_file . '.php';
}
if ( is_admin() ) {
	require_once NP_DIR . '/inc/admin.php';
}
unset( $np_files, $np_file );
