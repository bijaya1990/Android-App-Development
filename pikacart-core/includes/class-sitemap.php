<?php
/**
 * Adds the public design galleries to the WordPress XML sitemap (wp-sitemap.xml).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Sitemap_Provider extends WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'pikacart';
		$this->object_type = 'pikacart';
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		if ( $page_num > 1 ) {
			return array();
		}
		return array_map(
			function ( $url ) {
				return array( 'loc' => $url );
			},
			PKC_Public::urls()
		);
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		return 1;
	}
}
