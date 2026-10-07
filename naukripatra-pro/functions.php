<?php
/**
 * NaukriPatra Pro - bootstrap.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NPPRO_VERSION', '1.0.8' );
define( 'NPPRO_DIR', get_stylesheet_directory() );
define( 'NPPRO_URI', get_stylesheet_directory_uri() );

$nppro_files = array( 'settings', 'defaults', 'helpers', 'setup', 'yoast', 'rest', 'schema', 'lists', 'seo', 'ads', 'content', 'post-form', 'template-tags' );
foreach ( $nppro_files as $nppro_file ) {
	require_once NPPRO_DIR . '/inc/' . $nppro_file . '.php';
}
if ( is_admin() ) {
	require_once NPPRO_DIR . '/inc/admin.php';
}
unset( $nppro_files, $nppro_file );
