<?php
/**
 * Admin health checks.
 *
 * These are the automated part of the Phase 1 checklist: they warn in wp-admin
 * if something about the environment could break the redesign, instead of
 * failing silently on the live site.
 *
 * @package Naukripatra
 */

defined( 'ABSPATH' ) || exit;

/**
 * Warn if the GeneratePress parent theme is missing.
 */
function np_notice_parent_theme() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( wp_get_theme( 'generatepress' )->exists() ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'Naukripatra Child:', 'naukripatra' ),
		esc_html__( 'the GeneratePress parent theme is not installed. Install GeneratePress (free) before using this child theme.', 'naukripatra' )
	);
}
add_action( 'admin_notices', 'np_notice_parent_theme' );

/**
 * Recommend ACF (free) — dismissible, non-blocking.
 */
function np_notice_acf_recommended() {
	if ( ! current_user_can( 'manage_options' ) || function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	if ( get_user_meta( get_current_user_id(), 'np_dismissed_acf_notice', true ) ) {
		return;
	}

	$url = wp_nonce_url( add_query_arg( 'np_dismiss', 'acf' ), 'np_dismiss_notice' );

	printf(
		'<div class="notice notice-info"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
		esc_html__( 'Naukripatra Child:', 'naukripatra' ),
		esc_html__( 'Advanced Custom Fields (free) is recommended for a nicer job-entry form. Basic fields are working without it, and no data is lost either way.', 'naukripatra' ),
		esc_url( $url ),
		esc_html__( 'Dismiss', 'naukripatra' )
	);
}
add_action( 'admin_notices', 'np_notice_acf_recommended' );

/**
 * Handle notice dismissal.
 */
function np_handle_notice_dismiss() {
	if ( ! isset( $_GET['np_dismiss'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'np_dismiss_notice' );

	update_user_meta( get_current_user_id(), 'np_dismissed_acf_notice', 1 );
}
add_action( 'admin_init', 'np_handle_notice_dismiss' );

/**
 * URL-collision guard.
 *
 * The new Jobs URLs (/job/, /jobs/, /job-state/, /job-category/) must not
 * shadow anything that already exists and ranks. If an existing page,
 * category or tag already uses one of those slugs, WordPress would resolve it
 * unpredictably — so we warn loudly rather than silently changing a live URL.
 */
function np_notice_slug_collisions() {
	if ( ! current_user_can( 'manage_options' ) || ! np_flag( 'jobs_cpt' ) ) {
		return;
	}

	$slugs = array(
		apply_filters( 'np_job_rewrite_slug', 'job' ),
		apply_filters( 'np_job_archive_slug', 'jobs' ),
		apply_filters( 'np_state_rewrite_slug', 'job-state' ),
		apply_filters( 'np_job_category_rewrite_slug', 'job-category' ),
	);

	$conflicts = array();

	foreach ( $slugs as $slug ) {
		if ( get_page_by_path( $slug ) ) {
			$conflicts[] = sprintf( '%s (page)', $slug );
		}
		foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
			if ( get_term_by( 'slug', $slug, $taxonomy ) ) {
				$conflicts[] = sprintf( '%s (%s)', $slug, $taxonomy );
			}
		}
	}

	if ( ! $conflicts ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> %s <code>%s</code>. %s</p></div>',
		esc_html__( 'Naukripatra Child — URL collision:', 'naukripatra' ),
		esc_html__( 'the new Jobs URLs overlap existing content:', 'naukripatra' ),
		esc_html( implode( ', ', $conflicts ) ),
		esc_html__( 'Change the Jobs slug via the np_job_rewrite_slug filter before publishing jobs — do not rename the existing content.', 'naukripatra' )
	);
}
add_action( 'admin_notices', 'np_notice_slug_collisions' );
