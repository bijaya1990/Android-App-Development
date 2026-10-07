<?php
/**
 * Auto-create default categories, pages and menus on activation.
 * Existing terms/pages are never overwritten or renamed.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_switch_theme', 'nppro_create_defaults' );

/**
 * Slug => name list. Slugs follow the old site's convention.
 *
 * @return array
 */
function nppro_default_categories() {
	return array(
		'latest-jobs' => 'Latest Jobs', 'admit-card' => 'Admit Card', 'result' => 'Result',
		'answer-key' => 'Answer Key', 'syllabus' => 'Syllabus', 'admission' => 'Admission',
		'scholarship-schemes' => 'Scholarship & Schemes', 'all-india' => 'All India',
		// States.
		'andhra-pradesh' => 'Andhra Pradesh', 'arunachal-pradesh' => 'Arunachal Pradesh', 'assam' => 'Assam',
		'bihar' => 'Bihar', 'chhattisgarh' => 'Chhattisgarh', 'goa' => 'Goa', 'gujarat' => 'Gujarat',
		'haryana' => 'Haryana', 'himachal-pradesh' => 'Himachal Pradesh', 'jharkhand' => 'Jharkhand',
		'karnataka' => 'Karnataka', 'kerala' => 'Kerala', 'madhya-pradesh' => 'Madhya Pradesh',
		'maharashtra' => 'Maharashtra', 'manipur' => 'Manipur', 'meghalaya' => 'Meghalaya', 'mizoram' => 'Mizoram',
		'nagaland' => 'Nagaland', 'odisha' => 'Odisha', 'punjab' => 'Punjab', 'rajasthan' => 'Rajasthan',
		'sikkim' => 'Sikkim', 'tamil-nadu' => 'Tamil Nadu', 'telangana' => 'Telangana', 'tripura' => 'Tripura',
		'uttar-pradesh' => 'Uttar Pradesh', 'uttarakhand' => 'Uttarakhand', 'west-bengal' => 'West Bengal',
		// Union Territories.
		'andaman-nicobar' => 'Andaman & Nicobar', 'chandigarh' => 'Chandigarh',
		'dadra-nagar-haveli-daman-diu' => 'Dadra & Nagar Haveli and Daman & Diu', 'delhi' => 'Delhi',
		'jammu-kashmir' => 'Jammu & Kashmir', 'ladakh' => 'Ladakh', 'lakshadweep' => 'Lakshadweep',
		'puducherry' => 'Puducherry',
	);
}

/**
 * Slugs of the 8 union territories (used for the "UT" tag and form grouping).
 *
 * @return string[]
 */
function nppro_ut_slugs() {
	return array( 'andaman-nicobar', 'chandigarh', 'dadra-nagar-haveli-daman-diu', 'delhi', 'jammu-kashmir', 'ladakh', 'lakshadweep', 'puducherry' );
}

function nppro_create_defaults() {
	foreach ( nppro_default_categories() as $slug => $name ) {
		if ( term_exists( $slug, 'category' ) || term_exists( $name, 'category' ) ) {
			continue;
		}
		wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
	}

	$pages = array(
		'post-job'          => array( 'Post a Job', 'Use this form to submit a job notification. Every submission is reviewed before it goes live.' ),
		'resume-maker'      => array( 'Resume Maker', 'Build a clean resume and save it as PDF. Everything runs in your browser; nothing is uploaded.' ),
		'photo-resizer'     => array( 'Photo Resizer', 'Resize your photo to exam size and KB limits. Everything runs in your browser; nothing is uploaded.' ),
		'signature-scanner' => array( 'Signature Scanner', 'Clean and resize your signature to exam limits. Everything runs in your browser; nothing is uploaded.' ),
		'about'             => array( 'About', 'NaukriPatra shares the latest government job notifications, admit cards, results, answer keys, syllabus and admissions from across India in simple language.' ),
		'contact'           => array( 'Contact', 'For corrections, advertising or feedback, please write to us using the details on this page.' ),
		'privacy-policy'    => array( 'Privacy Policy', 'This page explains what information this website collects and how it is used. Edit this text from the WordPress dashboard.' ),
		'disclaimer'        => array( 'Disclaimer', 'NaukriPatra is not a government website. Please verify every detail on the official website before applying.' ),
		'dmca'              => array( 'DMCA', 'If you believe content on this site infringes your copyright, contact us with the page URL and proof of ownership and we will respond promptly.' ),
	);
	$ids = array();
	foreach ( $pages as $slug => $data ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		$ids[ $slug ] = wp_insert_post(
			array(
				'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $data[0],
				'post_name' => $slug, 'post_content' => '<p>' . esc_html( $data[1] ) . '</p>',
			)
		);
	}

	// Menus: create only when the location has no menu assigned.
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$primary   = array( 'Home' => home_url( '/' ) );
	foreach ( array( 'latest-jobs', 'admit-card', 'result', 'answer-key', 'syllabus', 'admission' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term ) {
			$primary[ $term->name ] = get_term_link( $term );
		}
	}
	$footer = array();
	foreach ( array( 'about', 'contact', 'privacy-policy', 'disclaimer', 'dmca' ) as $slug ) {
		if ( ! empty( $ids[ $slug ] ) && ! is_wp_error( $ids[ $slug ] ) ) {
			$footer[ get_the_title( $ids[ $slug ] ) ] = get_permalink( $ids[ $slug ] );
		}
	}
	foreach ( array( 'primary' => $primary, 'footer' => $footer ) as $loc => $items ) {
		if ( ! empty( $locations[ $loc ] ) || ! $items ) {
			continue;
		}
		$menu_id = wp_create_nav_menu( 'NaukriPatra ' . ucfirst( $loc ) . ' ' . wp_generate_password( 4, false ) );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}
		foreach ( $items as $title => $url ) {
			wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-status' => 'publish' ) );
		}
		$locations[ $loc ] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}
