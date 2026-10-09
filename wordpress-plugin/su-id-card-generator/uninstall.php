<?php
/**
 * Runs when the plugin is deleted: removes its page and options.
 * Card data lives in users' browsers and is not touched.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
$suidc_page = (int) get_option( 'suidc_page_id' );
if ( $suidc_page ) {
	wp_delete_post( $suidc_page, true );
}
delete_option( 'suidc_page_id' );
delete_option( 'suidc_require_login' );
