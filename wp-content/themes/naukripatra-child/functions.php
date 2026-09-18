<?php
/**
 * Naukripatra Child Theme — bootstrap.
 *
 * PHASE 1 (foundation). Activating this theme must not change a single URL,
 * a single existing post, or the look of the live site. It only:
 *   1. loads the parent (GeneratePress) stylesheet + this child stylesheet,
 *   2. registers the "Jobs" custom post type and its taxonomies,
 *   3. registers the job fields (ACF field group, with a native metabox
 *      fallback so data is never lost if ACF is deactivated),
 *   4. exposes helper functions used from Phase 3 onward.
 *
 * Everything is feature-flagged (see inc/flags.php) so any piece can be
 * switched off from wp-config.php without editing theme code.
 *
 * @package Naukripatra
 */

defined( 'ABSPATH' ) || exit;

define( 'NP_CHILD_VERSION', '1.0.0' );
define( 'NP_CHILD_DIR', get_stylesheet_directory() );
define( 'NP_CHILD_URI', get_stylesheet_directory_uri() );

require_once NP_CHILD_DIR . '/inc/flags.php';
require_once NP_CHILD_DIR . '/inc/helpers.php';
require_once NP_CHILD_DIR . '/inc/cpt-jobs.php';
require_once NP_CHILD_DIR . '/inc/fields-jobs.php';
require_once NP_CHILD_DIR . '/inc/admin-notices.php';

/**
 * Enqueue parent + child styles.
 *
 * GeneratePress loads its CSS via the handle 'generate-style'. We depend on it
 * so the child stylesheet always wins the cascade without !important.
 */
function np_enqueue_assets() {
	wp_enqueue_style(
		'np-child-style',
		NP_CHILD_URI . '/style.css',
		array( 'generate-style' ),
		NP_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'np_enqueue_assets', 20 );

/**
 * Flush rewrite rules exactly once after the CPT/taxonomies are registered.
 *
 * Doing this on every load is a performance killer; doing it never means the
 * new /job/ URLs 404. We flag it and flush on the next admin request only.
 * Existing permalinks are untouched — a flush only rebuilds the rule cache.
 */
function np_maybe_flush_rewrites() {
	if ( get_option( 'np_rewrite_flush_version' ) === NP_CHILD_VERSION ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'np_rewrite_flush_version', NP_CHILD_VERSION );
}
add_action( 'admin_init', 'np_maybe_flush_rewrites', 99 );
