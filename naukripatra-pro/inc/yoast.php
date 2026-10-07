<?php
/**
 * Yoast SEO helpers: write fields and sync the featured image into empty social-image fields.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** True when a full SEO plugin outputs titles/schema (we then only fill gaps). */
function np_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' );
}

/** Map of form field => Yoast meta key. */
function np_yoast_keys() {
	return array(
		'seo_title' => '_yoast_wpseo_title', 'seo_desc' => '_yoast_wpseo_metadesc', 'focuskw' => '_yoast_wpseo_focuskw',
		'canonical' => '_yoast_wpseo_canonical', 'og_title' => '_yoast_wpseo_opengraph-title', 'og_desc' => '_yoast_wpseo_opengraph-description',
		'tw_title' => '_yoast_wpseo_twitter-title', 'tw_desc' => '_yoast_wpseo_twitter-description',
	);
}

/** Copy the featured image into empty Yoast Facebook/Twitter image fields (never overwrite). */
function np_sync_yoast_images( $post_id ) {
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( ! $thumb ) {
		return;
	}
	$url = wp_get_attachment_image_url( $thumb, 'full' );
	if ( ! $url ) {
		return;
	}
	foreach ( array( '_yoast_wpseo_opengraph-image', '_yoast_wpseo_twitter-image' ) as $k ) {
		if ( '' === (string) get_post_meta( $post_id, $k, true ) ) {
			update_post_meta( $post_id, $k, esc_url_raw( $url ) );
		}
		if ( '' === (string) get_post_meta( $post_id, $k . '-id', true ) ) {
			update_post_meta( $post_id, $k . '-id', $thumb );
		}
	}
}
// Fires for admin editor, Gutenberg/REST and the front-end form alike.
add_action( 'added_post_meta', 'np_thumb_meta_hook', 10, 3 );
add_action( 'updated_post_meta', 'np_thumb_meta_hook', 10, 3 );
function np_thumb_meta_hook( $meta_id, $post_id, $key ) {
	if ( '_thumbnail_id' === $key ) {
		np_sync_yoast_images( $post_id );
	}
}
