<?php
/**
 * Settings storage. One option array: np_settings. Dashboard UI arrives in stage 7;
 * every value used by templates already goes through np_opt() so nothing is hard-coded.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default values for every setting.
 *
 * @return array
 */
function np_default_settings() {
	return array(
		'brand_text'      => 'NaukriPatra',
		'brand_logo_id'   => 0,
		'tagline'         => 'Latest Government Jobs, Results & Admit Cards',
		'show_post_btn'   => 1,
		'post_role'       => 'any',      // 'any' = every logged-in user; drafts always need approval.
		'dark_default'    => 'system',   // system | light | dark.
		'gp_base_css'     => 0,          // 1 = keep GeneratePress base CSS.
		'meta_prefix'     => '',
		'rows_per_page'   => 20,
		'new_badge_days'  => 3,
		'social'          => array(
			'telegram' => '', 'whatsapp' => '', 'youtube' => '', 'playstore' => '',
		),
		'copyright'       => '© {year} NaukriPatra. All rights reserved.',
		'disclaimer'      => 'NaukriPatra is not a government website. We share job information collected from official sources; always verify on the official website before applying.',
	);
}

/**
 * Read a setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Optional override default.
 * @return mixed
 */
function np_opt( $key, $default = null ) {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( 'np_settings', array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), np_default_settings() );
	}
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	return $default;
}
