<?php
/**
 * List pages: rows per page, state<->section filtering via query vars, noindex/canonical rules.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'np_section';
	$v[] = 'np_state';
	return $v;
} );

add_action( 'pre_get_posts', 'nppro_list_query' );
function nppro_list_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_category() || $q->is_search() || $q->is_tag() || $q->is_home() ) {
		$q->set( 'posts_per_page', max( 5, (int) nppro_opt( 'rows_per_page' ) ) );
	}
	if ( $q->is_search() ) {
		$q->set( 'post_type', 'post' );
	}
	if ( $q->is_category() ) {
		$extra = sanitize_title( (string) $q->get( 'np_section' ) );
		$extra = $extra ? $extra : sanitize_title( (string) $q->get( 'np_state' ) );
		if ( $extra && term_exists( $extra, 'category' ) ) {
			$tax = (array) $q->get( 'tax_query' );
			$tax[] = array( 'taxonomy' => 'category', 'field' => 'slug', 'terms' => $extra );
			$q->set( 'tax_query', $tax );
			$GLOBALS['nppro_base_cat'] = sanitize_title( (string) $q->get( 'category_name' ) );
		}
	}
}

// The extra tax clause would change the queried object; keep the page's own category as queried object.
add_action( 'wp', function () {
	global $wp_query, $nppro_base_cat;
	if ( ! empty( $nppro_base_cat ) && $wp_query->is_category() ) {
		$term = get_term_by( 'slug', basename( $nppro_base_cat ), 'category' );
		if ( $term ) {
			$wp_query->queried_object    = $term;
			$wp_query->queried_object_id = (int) $term->term_id;
		}
	}
} );

/** Current filter slug on a list page ('' if none). */
function nppro_active_filter() {
	$s = sanitize_title( (string) get_query_var( 'np_section' ) );
	return $s ? $s : sanitize_title( (string) get_query_var( 'np_state' ) );
}

/** H1 for a list page, e.g. "Jharkhand Government Jobs 2026". */
function nppro_list_heading() {
	$t = single_cat_title( '', false );
	if ( is_search() ) {
		/* translators: %s: search term */
		return sprintf( __( 'Search results for "%s"', 'naukripatra' ), get_search_query() );
	}
	$term = get_queried_object();
	$custom = ( $term instanceof WP_Term ) ? get_term_meta( $term->term_id, 'np_h1', true ) : '';
	if ( $custom ) {
		return $custom;
	}
	if ( $term instanceof WP_Term && in_array( $term->slug, nppro_section_slugs(), true ) ) {
		return $t . ' ' . nppro_year();
	}
	return $t . ' ' . __( 'Government Jobs', 'naukripatra' ) . ' ' . nppro_year();
}

// Canonical for filtered/paginated lists when no SEO plugin; Yoast canonical filtered too.
function nppro_canonical_url() {
	if ( is_category() ) {
		$u = get_category_link( get_queried_object_id() );
		return is_paged() ? trailingslashit( $u ) . 'page/' . (int) get_query_var( 'paged' ) . '/' : $u;
	}
	return '';
}
add_filter( 'wpseo_canonical', function ( $c ) {
	return ( is_category() && nppro_active_filter() ) ? nppro_canonical_url() : $c;
} );
add_action( 'wp_head', function () {
	if ( nppro_seo_plugin_active() ) {
		return;
	}
	$u = '';
	if ( is_category() ) {
		$u = nppro_canonical_url();
	} elseif ( is_singular() ) {
		$u = get_permalink();
	} elseif ( is_front_page() ) {
		$u = home_url( '/' );
	}
	if ( $u ) {
		echo '<link rel="canonical" href="' . esc_url( $u ) . "\">\n";
	}
}, 5 );

// rel prev/next on paginated archives.
add_action( 'wp_head', function () {
	if ( ! is_category() && ! is_home() && ! is_search() ) {
		return;
	}
	$prev = get_previous_posts_page_link();
	$next = get_next_posts_page_link();
	if ( $prev && is_paged() ) {
		echo '<link rel="prev" href="' . esc_url( $prev ) . "\">\n";
	}
	if ( $next ) {
		echo '<link rel="next" href="' . esc_url( $next ) . "\">\n";
	}
}, 6 );

// noindex rules.
add_filter( 'wp_robots', function ( $r ) {
	if ( ( is_search() && nppro_opt( 'noindex_search' ) ) || ( is_tag() && nppro_opt( 'noindex_tags' ) ) || ( is_category() && nppro_active_filter() ) ) {
		$r['noindex'] = true;
		$r['follow']  = true;
		unset( $r['index'] );
	}
	return $r;
} );

// Category SEO fields: title, description, H1, intro, SEO text (edit screen).
add_action( 'category_edit_form_fields', function ( $term ) {
	$f = array( 'np_seo_title' => 'SEO title', 'np_seo_desc' => 'Meta description', 'np_h1' => 'H1 override', 'np_intro' => 'Intro text (shown under H1)', 'np_seo_text' => 'SEO text box (shown below list)' );
	wp_nonce_field( 'np_term', 'np_term_nonce' );
	foreach ( $f as $k => $label ) {
		$v = get_term_meta( $term->term_id, $k, true );
		echo '<tr class="form-field"><th><label for="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( in_array( $k, array( 'np_intro', 'np_seo_text', 'np_seo_desc' ), true ) ) {
			echo '<textarea name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" rows="4" class="large-text">' . esc_textarea( $v ) . '</textarea>';
		} else {
			echo '<input type="text" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '" class="large-text">';
		}
		echo '<p class="description">{year} = current year.</p></td></tr>';
	}
} );
add_action( 'edited_category', function ( $term_id ) {
	if ( ! isset( $_POST['np_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['np_term_nonce'] ) ), 'np_term' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	foreach ( array( 'np_seo_title', 'np_h1', 'np_seo_desc' ) as $k ) {
		update_term_meta( $term_id, $k, sanitize_text_field( wp_unslash( isset( $_POST[ $k ] ) ? $_POST[ $k ] : '' ) ) );
	}
	foreach ( array( 'np_intro', 'np_seo_text' ) as $k ) {
		update_term_meta( $term_id, $k, wp_kses_post( wp_unslash( isset( $_POST[ $k ] ) ? $_POST[ $k ] : '' ) ) );
	}
} );

function nppro_term_meta( $key ) {
	$t = get_queried_object();
	if ( ! ( $t instanceof WP_Term ) ) {
		return '';
	}
	return str_replace( '{year}', nppro_year(), (string) get_term_meta( $t->term_id, $key, true ) );
}
