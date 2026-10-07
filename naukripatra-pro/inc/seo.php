<?php
/**
 * SEO gap-fillers. Yoast / Rank Math stay authoritative; we fill titles, descriptions and
 * social tags only when no SEO plugin is active, plus robots.txt additions.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'pre_get_document_title', function ( $title ) {
	if ( nppro_seo_plugin_active() ) {
		return $title;
	}
	$site = nppro_opt( 'brand_text' );
	if ( is_front_page() ) {
		$h = nppro_opt( 'seo_home_title' );
		return $h ? $h : $site . ' - ' . nppro_opt( 'tagline' );
	}
	if ( is_category() ) {
		$t = nppro_term_meta( 'np_seo_title' );
		return ( $t ? $t : nppro_list_heading() . ' - Notifications, Last Date, Vacancy' ) . ' | ' . $site;
	}
	if ( is_singular( 'post' ) ) {
		$t = get_the_title();
		$v = nppro_meta( get_the_ID(), 'vacancy', false );
		return $t . ( $v && ! preg_match( '/\d{4}/', $t ) ? ' ' . nppro_year() : '' ) . ' | ' . $site;
	}
	return $title;
} );

add_action( 'wp_head', 'nppro_meta_tags', 4 );
function nppro_meta_tags() {
	if ( nppro_seo_plugin_active() ) {
		return;
	}
	$desc = '';
	$img  = nppro_opt( 'seo_og_image' );
	$ttl  = wp_get_document_title();
	if ( is_front_page() ) {
		$desc = nppro_opt( 'seo_home_desc' ) ? nppro_opt( 'seo_home_desc' ) : nppro_opt( 'tagline' ) . '. Apply online, check last date, vacancy and eligibility.';
	} elseif ( is_category() ) {
		$desc = nppro_term_meta( 'np_seo_desc' );
		$desc = $desc ? $desc : sprintf( 'Latest %s with last date, number of posts and apply links. Updated daily on %s.', nppro_list_heading(), nppro_opt( 'brand_text' ) );
	} elseif ( is_singular( 'post' ) ) {
		$desc = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 30 );
		if ( has_post_thumbnail() ) {
			$img = get_the_post_thumbnail_url( null, 'full' );
		}
	}
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . "\">\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . "\">\n";
	}
	echo '<meta property="og:title" content="' . esc_attr( $ttl ) . "\">\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . "\">\n";
	echo '<meta property="og:site_name" content="' . esc_attr( nppro_opt( 'brand_text' ) ) . "\">\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . "\">\n";
	}
}

add_filter( 'robots_txt', function ( $out ) {
	$extra = trim( (string) nppro_opt( 'robots_extra' ) );
	return $extra ? $out . "\n" . $extra . "\n" : $out;
} );
