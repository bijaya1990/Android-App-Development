<?php
/**
 * Runs when the plugin is deleted from Plugins. Data is removed ONLY if the
 * owner ticked "Remove all data" in Settings > Maintenance and data.
 *
 * @package Pikacart
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$pkc_settings = get_option( 'pkc_settings', array() );
if ( empty( $pkc_settings['remove_data'] ) ) {
	return;
}

global $wpdb;

$pkc_tables = array( 'organisations', 'plans', 'subscriptions', 'payments', 'expenses', 'coupons', 'categories', 'subtypes', 'sizes', 'palettes', 'templates', 'projects', 'members', 'selffill_links', 'design_requests', 'tickets', 'ticket_replies', 'notices', 'activity_log', 'webhook_events', 'reports', 'trial_claims' );

// Delete organisation logins (never administrators or editors).
$pkc_org_users = get_users(
	array(
		'role'   => 'pkc_org',
		'fields' => 'ID',
	)
);
if ( $pkc_org_users ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $pkc_org_users as $pkc_uid ) {
		if ( ! user_can( (int) $pkc_uid, 'edit_posts' ) ) {
			wp_delete_user( (int) $pkc_uid );
		}
	}
}

foreach ( $pkc_tables as $pkc_t ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'pkc_' . $pkc_t ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// Uploaded files.
$pkc_up  = wp_upload_dir( null, false );
$pkc_dir = trailingslashit( $pkc_up['basedir'] ) . 'pikacart';
if ( is_dir( $pkc_dir ) ) {
	$pkc_it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $pkc_dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $pkc_it as $pkc_file ) {
		$pkc_file->isDir() ? @rmdir( $pkc_file->getPathname() ) : @unlink( $pkc_file->getPathname() ); // phpcs:ignore
	}
	@rmdir( $pkc_dir ); // phpcs:ignore
}

// Options and transients.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'pkc\\_%' OR option_name LIKE '\\_transient\\_pkc\\_%' OR option_name LIKE '\\_transient\\_timeout\\_pkc\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'pkc\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

remove_role( 'pkc_org' );
$pkc_admin_role = get_role( 'administrator' );
if ( $pkc_admin_role ) {
	$pkc_admin_role->remove_cap( 'pkc_manage' );
}
wp_clear_scheduled_hook( 'pkc_cron_tick' );
