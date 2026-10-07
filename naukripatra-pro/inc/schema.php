<?php
/**
 * Auto-generated JSON-LD. Nothing is typed by hand; unknown values are omitted, never invented.
 * With Yoast/Rank Math active we skip Breadcrumb/Organization/WebSite (they output those) and
 * add only what they do not: JobPosting, ItemList, FAQPage.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'np_output_schema', 30 );
function np_output_schema() {
	$nodes = array();
	$seo   = np_seo_plugin_active();
	if ( ! $seo ) {
		$nodes[] = array( '@type' => 'Organization', '@id' => home_url( '/#org' ), 'name' => np_opt( 'brand_text' ), 'url' => home_url( '/' ) );
		$nodes[] = array(
			'@type' => 'WebSite', '@id' => home_url( '/#site' ), 'name' => np_opt( 'brand_text' ), 'url' => home_url( '/' ),
			'potentialAction' => array( '@type' => 'SearchAction', 'target' => home_url( '/?s={search_term_string}' ), 'query-input' => 'required name=search_term_string' ),
		);
	}
	if ( is_singular( 'post' ) ) {
		$id = get_queried_object_id();
		if ( np_is_job_post( $id ) ) {
			$nodes[] = np_job_posting( $id );
		}
		$faq = np_faq_pairs( get_post_field( 'post_content', $id ) );
		if ( $faq ) {
			$q = array();
			foreach ( $faq as $p ) {
				$q[] = array( '@type' => 'Question', 'name' => $p[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $p[1] ) );
			}
			$nodes[] = array( '@type' => 'FAQPage', 'mainEntity' => $q );
		}
	}
	if ( ! $seo ) {
		$crumbs = np_breadcrumbs();
		if ( count( $crumbs ) > 1 ) {
			$items = array();
			foreach ( $crumbs as $i => $c ) {
				$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
			}
			$nodes[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $items );
		}
	}
	if ( is_category() ) {
		global $wp_query;
		$items = array();
		$pos   = 1;
		foreach ( (array) $wp_query->posts as $p ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'url' => get_permalink( $p ) );
		}
		if ( $items ) {
			$nodes[] = array( '@type' => 'ItemList', 'itemListElement' => $items );
		}
	}
	foreach ( $nodes as $n ) {
		$n = array( '@context' => 'https://schema.org' ) + $n;
		echo '<script type="application/ld+json">' . wp_json_encode( $n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/** Job posts = Latest Jobs section (or a post with vacancy data and no other section). */
function np_is_job_post( $id ) {
	$sec = np_section_of( $id );
	if ( $sec ) {
		return 'latest-jobs' === $sec->slug;
	}
	return '' !== np_meta( $id, 'vacancy', false );
}

function np_breadcrumbs() {
	$c = array( array( __( 'Home', 'naukripatra' ), home_url( '/' ) ) );
	if ( is_singular( 'post' ) ) {
		$cat = np_section_of( get_queried_object_id() );
		if ( ! $cat ) {
			$cats = get_the_category();
			$cat  = $cats ? $cats[0] : null;
		}
		if ( $cat ) {
			$c[] = array( $cat->name, get_category_link( $cat ) );
		}
		$c[] = array( get_the_title(), get_permalink() );
	} elseif ( is_category() ) {
		$c[] = array( single_cat_title( '', false ), get_category_link( get_queried_object_id() ) );
	} elseif ( is_page() ) {
		$c[] = array( get_the_title(), get_permalink() );
	}
	return $c;
}

function np_job_posting( $id ) {
	$g = function ( $k ) use ( $id ) {
		return np_meta( $id, $k );
	};
	$post = get_post( $id );
	$desc = wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $post->post_content ), 120 ) ) );
	$org  = $g( 'organization' );
	$job  = array(
		'@type' => 'JobPosting', 'title' => $g( 'post_name' ) ? $g( 'post_name' ) : get_the_title( $id ),
		'description' => $desc ? $desc : esc_html( get_the_title( $id ) ), 'datePosted' => get_post_time( 'c', false, $id ),
		'url' => get_permalink( $id ), 'directApply' => false,
		'hiringOrganization' => array( '@type' => 'Organization', 'name' => $org ? $org : np_opt( 'brand_text' ) ),
	);
	$site = $g( 'official_website' );
	if ( $site && preg_match( '#^https?://#i', $site ) ) {
		$job['hiringOrganization']['sameAs'] = esc_url_raw( strtok( $site, ' ' ) );
	}
	$d = np_parse_date( $g( 'last_date' ) );
	if ( $d ) {
		$job['validThrough'] = $d['iso'];
	}
	// Locations: one per state/UT category; India fallback so jobLocation is never missing.
	$places = array();
	foreach ( np_states_of( $id ) as $t ) {
		if ( 'all-india' === $t->slug ) {
			continue;
		}
		$places[] = array( '@type' => 'Place', 'address' => array( '@type' => 'PostalAddress', 'addressRegion' => $t->name, 'addressCountry' => 'IN' ) );
	}
	if ( ! $places ) {
		$places[] = array( '@type' => 'Place', 'address' => array( '@type' => 'PostalAddress', 'addressCountry' => 'IN' ) );
	}
	$job['jobLocation'] = count( $places ) > 1 ? $places : $places[0];
	$job['employmentType'] = np_employment_type( $g( 'job_type' ) );
	if ( preg_match( '/\d[\d,]*/', $g( 'vacancy' ), $m ) ) {
		$job['totalJobOpenings'] = (int) str_replace( ',', '', $m[0] );
	}
	if ( $g( 'qualification' ) ) {
		$job['qualifications'] = $g( 'qualification' );
		$edu = np_education_enum( $g( 'qualification' ) );
		if ( $edu ) {
			$job['educationRequirements'] = array( '@type' => 'EducationalOccupationalCredential', 'credentialCategory' => $edu );
		}
	}
	$sal = np_base_salary( $g( 'salary' ) );
	if ( $sal ) {
		$job['baseSalary'] = $sal;
	}
	$app = $g( 'apply_link' );
	if ( $app && preg_match( '#https?://[^\s/]+[^\s]*#i', $app, $m ) ) {
		$job['applicationContact'] = array( '@type' => 'ContactPoint', 'url' => esc_url_raw( $m[0] ) );
	}
	if ( $g( 'advt_no' ) ) {
		$job['identifier'] = array( '@type' => 'PropertyValue', 'name' => $org ? $org : np_opt( 'brand_text' ), 'value' => $g( 'advt_no' ) );
	}
	return $job;
}

/** Valid Google educationRequirements enum or ''. */
function np_education_enum( $text ) {
	$t = strtolower( $text );
	if ( preg_match( '/\b(ph\.?d|doctorate)\b/', $t ) ) { return 'doctorate'; }
	if ( preg_match( '/\b(master|m\.?sc|m\.?com|m\.?a|mba|m\.?tech|post ?graduate|pg)\b/', $t ) ) { return 'postgraduate degree'; }
	if ( preg_match( '/\b(graduat\w*|bachelor|b\.?sc|b\.?com|b\.?a|b\.?tech|b\.?e|bba|degree)\b/', $t ) ) { return 'bachelor degree'; }
	if ( preg_match( '/\b(diploma|iti|certificate)\b/', $t ) ) { return 'associate degree'; }
	if ( preg_match( '/\b(12th|10th|matric|class 1[02]|intermediate|high school)\b/', $t ) ) { return 'high school'; }
	return '';
}

/** baseSalary only when a real rupee figure and a clear unit exist. */
function np_base_salary( $text ) {
	if ( ! preg_match( '/(?:₹|rs\.?|inr)\s*([\d,]+)(?:\s*(?:-|to)\s*(?:₹|rs\.?|inr)?\s*([\d,]+))?/i', $text, $m ) ) {
		return null;
	}
	if ( ! preg_match( '/per\s+(month|year|week|day|hour)|\/\s*(month|year|week|day|hour)|(monthly|yearly|annual|weekly|daily)/i', $text, $u ) ) {
		return null;
	}
	$unit = strtoupper( ! empty( $u[1] ) ? $u[1] : ( ! empty( $u[2] ) ? $u[2] : ( 'monthly' === strtolower( $u[3] ) ? 'month' : ( 'weekly' === strtolower( $u[3] ) ? 'week' : ( 'daily' === strtolower( $u[3] ) ? 'day' : 'year' ) ) ) ) );
	$lo   = (int) str_replace( ',', '', $m[1] );
	$val  = array( '@type' => 'QuantitativeValue', 'unitText' => $unit );
	if ( ! empty( $m[2] ) ) {
		$val['minValue'] = $lo;
		$val['maxValue'] = (int) str_replace( ',', '', $m[2] );
	} else {
		$val['value'] = $lo;
	}
	return $lo > 0 ? array( '@type' => 'MonetaryAmount', 'currency' => 'INR', 'value' => $val ) : null;
}

/** Question/answer pairs from a FAQ section in the article. */
function np_faq_pairs( $html ) {
	$pairs = array();
	if ( ! preg_match( '#<h[23][^>]*>[^<]*(?:FAQ|Frequently Asked)[^<]*</h[23]>(.*?)(?=<h2|$)#is', $html, $sec ) ) {
		return $pairs;
	}
	if ( preg_match_all( '#<h[34][^>]*>(.*?)</h[34]>\s*<p[^>]*>(.*?)</p>#is', $sec[1], $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			$q = trim( wp_strip_all_tags( $x[1] ) );
			$a = trim( wp_strip_all_tags( $x[2] ) );
			if ( $q && $a ) {
				$pairs[] = array( $q, $a );
			}
		}
	}
	return $pairs;
}
