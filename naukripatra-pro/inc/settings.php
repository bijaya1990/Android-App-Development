<?php
/**
 * Settings storage: one option array "nppro_settings". Edited in NaukriPatra Control (inc/admin.php).
 * Every value used by templates goes through nppro_opt(), so no copy is hard-coded.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ad slot definitions: key => label, desktop size, mobile size.
 *
 * @return array
 */
function nppro_ad_slots() {
	return array(
		'header'       => array( 'Header leaderboard', array( 728, 90 ), array( 320, 50 ) ),
		'below_states' => array( 'Below states', array( 728, 90 ), array( 320, 100 ) ),
		'infeed'       => array( 'In-feed (home)', array( 728, 90 ), array( 300, 250 ) ),
		'list'         => array( 'In list (every 5th row)', array( 300, 250 ), array( 300, 250 ) ),
		'article'      => array( 'In-article', array( 300, 250 ), array( 300, 250 ) ),
		'after'        => array( 'After content', array( 728, 90 ), array( 300, 250 ) ),
		'sidebar1'     => array( 'Sidebar 300x250', array( 300, 250 ), array( 300, 250 ) ),
		'sidebar2'     => array( 'Sidebar sticky 300x600', array( 300, 600 ), array( 300, 250 ) ),
		'end'          => array( 'End of page (mobile)', array( 300, 250 ), array( 300, 250 ) ),
		'footer'       => array( 'Footer banner', array( 728, 90 ), array( 320, 50 ) ),
		'sticky'       => array( 'Sticky mobile bottom', array( 320, 50 ), array( 320, 50 ) ),
	);
}

/**
 * Default values for every setting.
 *
 * @return array
 */
function nppro_default_settings() {
	$ads = array();
	foreach ( nppro_ad_slots() as $key => $def ) {
		$ads[ $key ] = array( 'on' => 1, 'code' => '', 'device' => ( 'sticky' === $key ? 'mobile' : ( 'sidebar1' === $key || 'sidebar2' === $key ? 'desktop' : ( 'end' === $key ? 'mobile' : 'all' ) ) ) );
	}
	return array(
		'brand_text'     => 'NaukriPatra',
		'brand_logo_id'  => 0,
		'tagline'        => 'Latest Government Jobs, Results & Admit Cards',
		'show_post_btn'  => 1,
		'post_role'      => 'any',
		'dark_default'   => 'system',
		'gp_base_css'    => 0,
		'inline_css'     => 1,
		'menu_force_cats' => 1,
		'toc_mode'        => 'end',
		'rest_clean'      => 1,
		'meta_prefix'    => '',
		'rows_per_page'  => 20,
		'new_badge_days' => 3,
		'soon_days'      => 3,
		'col_advt'       => 1,
		'col_posts'      => 1,
		'color_soon'     => '#C2410C',
		'color_expired'  => '#B91C1C',
		'live_filter'    => 1,
		'blocks'         => array(
			'ticker_jobs'    => array( 'show' => 1, 'order' => 1 ),
			'tools'          => array( 'show' => 1, 'order' => 2 ),
			'ticker_results' => array( 'show' => 1, 'order' => 3 ),
			'cats'           => array( 'show' => 1, 'order' => 4 ),
			'states'         => array( 'show' => 1, 'order' => 5 ),
			'main'           => array( 'show' => 1, 'order' => 6 ),
			'seo_text'       => array( 'show' => 1, 'order' => 7 ),
		),
		'ticker_jobs_count'    => 10,
		'ticker_results_count' => 10,
		'ticker_speed'         => 40,
		'ticker_jobs_label'    => 'LATEST JOBS',
		'ticker_results_label' => 'LIVE RESULTS',
		'ticker_results_answer' => 0,
		'home_jobs_count'      => 10,
		'home_box_count'       => 4,
		'trending_count'       => 8,
		'states_title'         => 'State & UT Wise Jobs',
		'tools'                => array(
			array( 'label' => 'Resume Maker', 'link' => '/resume-maker/', 'icon' => 'resume', 'color' => '#1D4ED8', 'show' => 1 ),
			array( 'label' => 'Photo Resizer', 'link' => '/photo-resizer/', 'icon' => 'photo', 'color' => '#0F766E', 'show' => 1 ),
			array( 'label' => 'Signature Scanner', 'link' => '/signature-scanner/', 'icon' => 'signature', 'color' => '#6D28D9', 'show' => 1 ),
		),
		'cats'                 => array(
			'latest-jobs' => array( 'label' => 'Latest Jobs', 'color' => '#1D4ED8' ),
			'admit-card'  => array( 'label' => 'Admit Card', 'color' => '#0F766E' ),
			'result'      => array( 'label' => 'Result', 'color' => '#15803D' ),
			'answer-key'  => array( 'label' => 'Answer Key', 'color' => '#C2410C' ),
			'syllabus'    => array( 'label' => 'Syllabus', 'color' => '#6D28D9' ),
			'admission'   => array( 'label' => 'Admission', 'color' => '#BE185D' ),
		),
		'hidden_locations'     => '',
		'location_names'       => '',
		'extra_locations'      => 'USA Jobs|',
		'seo_text'             => '',
		'seo_home_title'       => '',
		'seo_home_desc'        => '',
		'seo_og_image'         => '',
		'noindex_search'       => 1,
		'noindex_tags'         => 1,
		'robots_extra'         => '',
		'ads'                  => $ads,
		'ad_delay'             => 3500,
		'ad_max'               => 6,
		'ad_social_bar'        => '',
		'ad_popunder'          => '',
		'form_rate'            => 5,
		'notify_email'         => '',
		'social'               => array( 'telegram' => '', 'whatsapp' => '', 'youtube' => '', 'playstore' => '' ),
		'join_text'            => 'Get every new job alert instantly. Join our channel.',
		'footer_states'        => 1,
		'copyright'            => '© {year} NaukriPatra. All rights reserved.',
		'disclaimer'           => 'NaukriPatra is not a government website. We share job information collected from official sources; always verify on the official website before applying.',
	);
}

/**
 * Read a setting (saved value merged over defaults).
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback when key is unknown.
 * @return mixed
 */
function nppro_opt( $key, $default = null ) {
	global $nppro_settings_cache;
	if ( null === $nppro_settings_cache ) {
		$saved = get_option( 'nppro_settings', array() );
		$saved = is_array( $saved ) ? $saved : array();
		$nppro_settings_cache = array_replace_recursive( nppro_default_settings(), $saved );
		if ( isset( $saved['tools'] ) && is_array( $saved['tools'] ) ) {
			$nppro_settings_cache['tools'] = $saved['tools']; // Repeater: saved list wins entirely.
		}
	}
	return array_key_exists( $key, $nppro_settings_cache ) ? $nppro_settings_cache[ $key ] : $default;
}

/** Reset the in-request cache after saving. */
function nppro_opt_flush() {
	global $nppro_settings_cache;
	$nppro_settings_cache = null;
}
