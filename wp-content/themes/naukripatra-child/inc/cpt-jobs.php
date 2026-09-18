<?php
/**
 * Jobs custom post type + taxonomies.
 *
 * ── URL SAFETY (non-negotiable rule #2) ───────────────────────────────────
 * Nothing here touches existing posts, categories or the permalink structure.
 * The new URLs live in their own namespaces that do NOT exist on the site
 * today:
 *
 *   Single job  : /job/<slug>/          (CPT rewrite slug, filterable)
 *   Job archive : /jobs/                (CPT archive)
 *   State term  : /job-state/<slug>/    (NEW taxonomy — the live state pages
 *                                        at /bihar/ etc. are WP categories and
 *                                        keep working exactly as they are)
 *   Category    : /job-category/<slug>/
 *
 * The existing category tree (Latest Jobs, Admit Card, Result, …) and the
 * 36 state category pages are left completely alone. Migration in a later
 * step is additive: a job gets a CPT entry AND the old post stays published
 * at its old URL until you explicitly decide otherwise.
 *
 * If any of these slugs ever collides with an existing page/category, change
 * it from wp-config.php or a snippet without editing this file:
 *
 *     add_filter( 'np_job_rewrite_slug', fn() => 'sarkari-job' );
 *
 * ── ADMIN SCREENS THIS CREATES ─────────────────────────────────────────────
 *   Jobs                    → list of all job postings
 *   Jobs > Add New          → the form a non-technical admin fills in
 *   Jobs > States           → manage the state list
 *   Jobs > Job Categories   → Latest Job / Admit Card / Result / …
 *
 * @package Naukripatra
 */

defined( 'ABSPATH' ) || exit;

const NP_JOB_POST_TYPE = 'np_job';
const NP_TAX_STATE     = 'np_job_state';
const NP_TAX_CATEGORY  = 'np_job_category';

/**
 * Register the Jobs post type.
 */
function np_register_job_post_type() {
	if ( ! np_flag( 'jobs_cpt' ) ) {
		return;
	}

	$labels = array(
		'name'               => __( 'Jobs', 'naukripatra' ),
		'singular_name'      => __( 'Job', 'naukripatra' ),
		'menu_name'          => __( 'Jobs', 'naukripatra' ),
		'add_new'            => __( 'Add New Job', 'naukripatra' ),
		'add_new_item'       => __( 'Add New Job', 'naukripatra' ),
		'edit_item'          => __( 'Edit Job', 'naukripatra' ),
		'new_item'           => __( 'New Job', 'naukripatra' ),
		'view_item'          => __( 'View Job', 'naukripatra' ),
		'search_items'       => __( 'Search Jobs', 'naukripatra' ),
		'not_found'          => __( 'No jobs found', 'naukripatra' ),
		'not_found_in_trash' => __( 'No jobs found in Trash', 'naukripatra' ),
		'all_items'          => __( 'All Jobs', 'naukripatra' ),
	);

	register_post_type(
		NP_JOB_POST_TYPE,
		array(
			'labels'        => $labels,
			'public'        => true,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => true, // Gutenberg editor + future AJAX search.
			'menu_position' => 5,
			'menu_icon'     => 'dashicons-businessperson',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'comments', 'custom-fields' ),
			'has_archive'   => apply_filters( 'np_job_archive_slug', 'jobs' ),
			'rewrite'       => array(
				'slug'       => apply_filters( 'np_job_rewrite_slug', 'job' ),
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'delete_with_user'    => false,
			'can_export'          => true,
			'exclude_from_search' => false,
		)
	);
}
add_action( 'init', 'np_register_job_post_type', 5 );

/**
 * Register the State and Job Category taxonomies.
 *
 * Both are hierarchical so they behave like categories in wp-admin (checkbox
 * list), which is what a non-technical editor expects.
 */
function np_register_job_taxonomies() {
	if ( ! np_flag( 'jobs_cpt' ) ) {
		return;
	}

	register_taxonomy(
		NP_TAX_STATE,
		array( NP_JOB_POST_TYPE ),
		array(
			'labels'            => array(
				'name'          => __( 'States', 'naukripatra' ),
				'singular_name' => __( 'State', 'naukripatra' ),
				'menu_name'     => __( 'States', 'naukripatra' ),
				'search_items'  => __( 'Search States', 'naukripatra' ),
				'all_items'     => __( 'All States', 'naukripatra' ),
				'edit_item'     => __( 'Edit State', 'naukripatra' ),
				'add_new_item'  => __( 'Add New State', 'naukripatra' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => apply_filters( 'np_state_rewrite_slug', 'job-state' ),
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		NP_TAX_CATEGORY,
		array( NP_JOB_POST_TYPE ),
		array(
			'labels'            => array(
				'name'          => __( 'Job Categories', 'naukripatra' ),
				'singular_name' => __( 'Job Category', 'naukripatra' ),
				'menu_name'     => __( 'Job Categories', 'naukripatra' ),
				'search_items'  => __( 'Search Job Categories', 'naukripatra' ),
				'all_items'     => __( 'All Job Categories', 'naukripatra' ),
				'edit_item'     => __( 'Edit Job Category', 'naukripatra' ),
				'add_new_item'  => __( 'Add New Job Category', 'naukripatra' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => apply_filters( 'np_job_category_rewrite_slug', 'job-category' ),
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'np_register_job_taxonomies', 5 );

/**
 * Seed the default taxonomy terms once, on first theme activation.
 *
 * Idempotent: existing terms are never overwritten or renamed, and this never
 * runs twice (guarded by the np_terms_seeded option).
 */
function np_seed_default_terms() {
	if ( get_option( 'np_terms_seeded' ) ) {
		return;
	}

	$categories = array(
		'latest-job'  => 'Latest Job',
		'admit-card'  => 'Admit Card',
		'result'      => 'Result',
		'syllabus'    => 'Syllabus',
		'answer-key'  => 'Answer Key',
		'admission'   => 'Admission',
	);

	foreach ( $categories as $slug => $name ) {
		if ( ! term_exists( $slug, NP_TAX_CATEGORY ) ) {
			wp_insert_term( $name, NP_TAX_CATEGORY, array( 'slug' => $slug ) );
		}
	}

	foreach ( np_default_states() as $slug => $name ) {
		if ( ! term_exists( $slug, NP_TAX_STATE ) ) {
			wp_insert_term( $name, NP_TAX_STATE, array( 'slug' => $slug ) );
		}
	}

	update_option( 'np_terms_seeded', 1 );
}
add_action( 'after_switch_theme', 'np_seed_default_terms' );
add_action( 'admin_init', 'np_seed_default_terms', 100 );

/**
 * The 36 states/UTs + All India + USA, grouped by region.
 *
 * The region key drives the Phase 2 mega-menu columns. Slugs deliberately
 * match the existing live category slugs (/bihar/, /usa-jobs/, /all-india/)
 * so links and future cross-references stay predictable — but these are terms
 * in a NEW taxonomy, so nothing about the existing category URLs changes.
 *
 * @return array<string,string> slug => name
 */
function np_default_states() {
	$states = array();
	foreach ( np_state_regions() as $group ) {
		$states += $group['states'];
	}
	return $states;
}

/**
 * State groupings used by the mega menu (Phase 2).
 *
 * @return array<string,array{label:string,states:array<string,string>}>
 */
function np_state_regions() {
	return array(
		'national' => array(
			'label'  => 'All India',
			'states' => array(
				'all-india' => 'All India',
				'usa-jobs'  => 'USA Jobs',
			),
		),
		'north'    => array(
			'label'  => 'North India',
			'states' => array(
				'chandigarh'        => 'Chandigarh',
				'delhi'             => 'Delhi',
				'haryana'           => 'Haryana',
				'himachal-pradesh'  => 'Himachal Pradesh',
				'jammu-and-kashmir' => 'Jammu and Kashmir',
				'ladakh'            => 'Ladakh',
				'punjab'            => 'Punjab',
				'rajasthan'         => 'Rajasthan',
				'uttar-pradesh'     => 'Uttar Pradesh',
				'uttarakhand'       => 'Uttarakhand',
			),
		),
		'south'    => array(
			'label'  => 'South India',
			'states' => array(
				'andhra-pradesh'                => 'Andhra Pradesh',
				'andaman-and-nicobar-islands'   => 'Andaman and Nicobar Islands',
				'karnataka'                     => 'Karnataka',
				'kerala'                        => 'Kerala',
				'lakshadweep'                   => 'Lakshadweep',
				'puducherry'                    => 'Puducherry',
				'tamil-nadu'                    => 'Tamil Nadu',
				'telangana'                     => 'Telangana',
			),
		),
		'east'     => array(
			'label'  => 'East India',
			'states' => array(
				'bihar'         => 'Bihar',
				'chhattisgarh'  => 'Chhattisgarh',
				'jharkhand'     => 'Jharkhand',
				'odisha'        => 'Odisha',
				'west-bengal'   => 'West Bengal',
			),
		),
		'west'     => array(
			'label'  => 'West India',
			'states' => array(
				'dadra-and-nagar-haveli-and-daman-and-diu' => 'Dadra and Nagar Haveli and Daman and Diu',
				'goa'             => 'Goa',
				'gujarat'         => 'Gujarat',
				'madhya-pradesh'  => 'Madhya Pradesh',
				'maharashtra'     => 'Maharashtra',
			),
		),
		'northeast' => array(
			'label'  => 'Northeast India',
			'states' => array(
				'arunachal-pradesh' => 'Arunachal Pradesh',
				'assam'             => 'Assam',
				'manipur'           => 'Manipur',
				'meghalaya'         => 'Meghalaya',
				'mizoram'           => 'Mizoram',
				'nagaland'          => 'Nagaland',
				'sikkim'            => 'Sikkim',
				'tripura'           => 'Tripura',
			),
		),
	);
}
