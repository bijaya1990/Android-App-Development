<?php
/**
 * Data helpers: meta-key resolver, overview-table parser, dates, states, icons.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nppro_year() {
	return wp_date( 'Y' );
}

/** Section category slugs. */
function nppro_section_slugs() {
	return array( 'latest-jobs', 'admit-card', 'result', 'answer-key', 'syllabus', 'admission', 'scholarship-schemes' );
}

/** Slugs of the 28 states + 8 UTs in display order. */
function nppro_location_slugs() {
	$out = array();
	foreach ( array_keys( nppro_default_categories() ) as $slug ) {
		if ( ! in_array( $slug, nppro_section_slugs(), true ) && 'all-india' !== $slug ) {
			$out[] = $slug;
		}
	}
	return $out;
}

/** Short names for long location labels. */
function nppro_short_name( $slug, $name ) {
	$short = array( 'dadra-nagar-haveli-daman-diu' => 'D. Nagar Haveli & Daman Diu', 'andaman-nicobar' => 'Andaman & Nicobar', 'arunachal-pradesh' => 'Arunachal Pradesh' );
	return isset( $short[ $slug ] ) ? $short[ $slug ] : $name;
}

/** Inline SVG icons for the tools row. */
function nppro_icon( $name ) {
	$p = array(
		'resume'    => '<path d="M6 3h9l4 4v14H6z"/><path d="M9 12h7M9 16h7M9 8h3"/>',
		'photo'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 17-5-5-8 7"/>',
		'signature' => '<path d="M3 17c3-6 5-10 7-8s-3 8 0 7 4-5 5-3 3 2 6 0"/><path d="M3 21h18"/>',
		'link'      => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'star'      => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.2 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
		'calc'      => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h2M12 12h2M8 16h2M12 16h2"/>',
		'doc'       => '<path d="M7 3h8l4 4v14H7z"/><path d="M15 3v4h4"/>',
	);
	$d = isset( $p[ $name ] ) ? $p[ $name ] : $p['link'];
	return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/** Plugin metabox keys (the 17 API fields). */
function nppro_api_fields() {
	return array( 'age_limit', 'application_fee', 'application_mode', 'apply_link', 'department', 'job_location', 'job_type', 'last_date', 'notification_link', 'official_website', 'organization', 'post_name', 'qualification', 'salary', 'selection_process', 'vacancy' );
}

/** Overview-table label synonyms (normalised, lower case). */
function nppro_synonyms() {
	return array(
		'organization'      => array( 'organization', 'organisation', 'recruitment board', 'conducting body', 'board name', 'company name' ),
		'post_name'         => array( 'post name', 'name of post', 'name of the post', 'post' ),
		'vacancy'           => array( 'vacancy', 'total vacancy', 'total vacancies', 'no of posts', 'total posts', 'number of posts', 'vacancies' ),
		'qualification'     => array( 'qualification', 'educational qualification', 'eligibility', 'education' ),
		'age_limit'         => array( 'age limit', 'age' ),
		'salary'            => array( 'salary', 'pay scale', 'stipend', 'pay level', 'pay' ),
		'application_fee'   => array( 'application fee', 'fee', 'exam fee' ),
		'application_mode'  => array( 'application mode', 'mode of application', 'apply mode' ),
		'selection_process' => array( 'selection process', 'selection procedure', 'mode of selection' ),
		'job_type'          => array( 'job type', 'employment type', 'type of job' ),
		'job_location'      => array( 'job location', 'location', 'place of posting', 'job place' ),
		'department'        => array( 'department', 'division' ),
		'last_date'         => array( 'last date', 'closing date', 'last date to apply', 'last date of application', 'last date for online application' ),
		'advt_no'           => array( 'advt no', 'advertisement no', 'advertisement number', 'notification no', 'advt number', 'employment notice no' ),
	);
}

function nppro_norm_label( $s ) {
	$s = strtolower( html_entity_decode( wp_strip_all_tags( $s ), ENT_QUOTES, 'UTF-8' ) );
	return trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9 ]+/', ' ', $s ) ) );
}

/**
 * Label => value pairs from the article's overview-style tables, keyed by field name.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function nppro_overview_map( $post_id ) {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	$out     = array();
	$content = (string) get_post_field( 'post_content', $post_id );
	$syn     = nppro_synonyms();
	if ( $content && preg_match_all( '#<tr[^>]*>(.*?)</tr>#is', $content, $rows ) ) {
		foreach ( $rows[1] as $row ) {
			if ( ! preg_match_all( '#<t[dh][^>]*>(.*?)</t[dh]>#is', $row, $cells ) || count( $cells[1] ) < 2 ) {
				continue;
			}
			$label = nppro_norm_label( $cells[1][0] );
			$value = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $cells[1][1] ), ENT_QUOTES, 'UTF-8' ) ) );
			if ( '' === $label || '' === $value ) {
				continue;
			}
			foreach ( $syn as $field => $labels ) {
				if ( isset( $out[ $field ] ) ) {
					continue;
				}
				foreach ( $labels as $l ) {
					if ( $label === $l || 0 === strpos( $label, $l . ' ' ) ) {
						$out[ $field ] = $value;
						break 2;
					}
				}
			}
		}
	}
	$cache[ $post_id ] = $out;
	return $out;
}

/** Key aliases from the old theme's field names. */
function nppro_aliases( $key ) {
	$a = array( 'vacancy' => array( 'posts_count' ), 'job_type' => array( 'employment_type' ), 'job_location' => array( 'locality' ), 'apply_link' => array( 'apply_url', 'official_link' ), 'notification_link' => array( 'notification_pdf' ) );
	return isset( $a[ $key ] ) ? $a[ $key ] : array();
}

/** Meta key names tried for a field, in order. */
function nppro_key_variants( $key ) {
	$names  = array();
	$prefix = trim( (string) nppro_opt( 'meta_prefix' ) );
	foreach ( array_merge( array( $key ), nppro_aliases( $key ) ) as $k ) {
		if ( $prefix ) {
			$names[] = $prefix . $k;
		}
		array_push( $names, $k, '_' . $k, 'np_' . $k, '_np_' . $k );
	}
	return array_values( array_unique( $names ) );
}

/**
 * Resolve a field: plugin's real key (+ variants), then the overview table.
 *
 * @param int    $post_id  Post ID.
 * @param string $key      Field name.
 * @param bool   $fallback Use overview table when meta is empty.
 * @return string
 */
function nppro_meta( $post_id, $key, $fallback = true ) {
	foreach ( nppro_key_variants( $key ) as $name ) {
		$v = get_post_meta( $post_id, $name, true );
		if ( is_scalar( $v ) && '' !== trim( (string) $v ) ) {
			return trim( (string) $v );
		}
	}
	if ( $fallback ) {
		$o = nppro_overview_map( $post_id );
		if ( isset( $o[ $key ] ) ) {
			return $o[ $key ];
		}
		if ( 'advt_no' === $key ) {
			return nppro_advt_from_title( get_the_title( $post_id ) );
		}
	}
	return '';
}

function nppro_advt_from_title( $title ) {
	if ( preg_match( '/(?:advt|advertisement|notification|notice)\.?\s*(?:no\.?|number)?\s*[:\-]?\s*([A-Za-z0-9][A-Za-z0-9\/\-\.]{2,})/i', $title, $m ) ) {
		return rtrim( $m[1], '.' );
	}
	return '';
}

/**
 * Parse many date formats. Returns array( ts, has_time, iso ) or null.
 *
 * @param string $raw Raw text, e.g. "04/11/2026 at 5:00 PM" or "04 Nov 2026".
 * @return array|null
 */
function nppro_parse_date( $raw ) {
	$s = trim( html_entity_decode( (string) $raw, ENT_QUOTES, 'UTF-8' ) );
	if ( '' === $s ) {
		return null;
	}
	$h = 23; $i = 59; $sec = 59; $has_time = false;
	if ( preg_match( '/(\d{1,2}):(\d{2})\s*(am|pm)?/i', $s, $t ) ) {
		$h = (int) $t[1]; $i = (int) $t[2]; $sec = 0; $has_time = true;
		if ( ! empty( $t[3] ) ) {
			$pm = 'pm' === strtolower( $t[3] );
			if ( $pm && $h < 12 ) { $h += 12; }
			if ( ! $pm && 12 === $h ) { $h = 0; }
		}
		$s = str_replace( $t[0], ' ', $s );
	}
	$d = $m_ = $y = 0;
	if ( preg_match( '/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/', $s, $m ) ) {
		$d = (int) $m[1]; $m_ = (int) $m[2]; $y = (int) $m[3];
	} elseif ( preg_match( '/(\d{4})-(\d{1,2})-(\d{1,2})/', $s, $m ) ) {
		$y = (int) $m[1]; $m_ = (int) $m[2]; $d = (int) $m[3];
	} elseif ( preg_match( '/(\d{1,2})(?:st|nd|rd|th)?[\s\-]+([A-Za-z]{3,9})\.?,?[\s\-]+(\d{4})/', $s, $m ) ) {
		$p = date_parse( $m[1] . ' ' . $m[2] . ' ' . $m[3] );
		if ( $p && empty( $p['error_count'] ) && $p['month'] ) {
			$d = (int) $m[1]; $m_ = (int) $p['month']; $y = (int) $m[3];
		}
	}
	if ( ! $d || ! $m_ || ! checkdate( $m_, $d, $y ) ) {
		return null;
	}
	$dt = new DateTimeImmutable( sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $y, $m_, $d, $h, $i, $sec ), wp_timezone() );
	return array( 'ts' => $dt->getTimestamp(), 'has_time' => $has_time, 'iso' => $dt->format( 'c' ) );
}

/**
 * Last-date status for list rows.
 *
 * @param string $raw Raw last_date value.
 * @return array class (ok|soon|expired|''), label, text
 */
function nppro_date_status( $raw ) {
	$p = nppro_parse_date( $raw );
	if ( ! $p ) {
		return array( 'class' => '', 'label' => '', 'text' => '' !== $raw ? $raw : '—' );
	}
	$text = wp_date( 'd M Y', $p['ts'] );
	$left = $p['ts'] - time();
	if ( $left < 0 ) {
		return array( 'class' => 'expired', 'label' => __( 'Expired', 'naukripatra' ), 'text' => $text );
	}
	if ( ceil( $left / DAY_IN_SECONDS ) <= (int) nppro_opt( 'soon_days' ) ) {
		return array( 'class' => 'soon', 'label' => '', 'text' => $text );
	}
	return array( 'class' => 'ok', 'label' => '', 'text' => $text );
}

function nppro_posts_count( $post_id ) {
	$v = nppro_meta( $post_id, 'vacancy' );
	if ( '' === $v ) {
		return '—';
	}
	return preg_match( '/\d[\d,]*/', $v, $m ) ? $m[0] : $v;
}

function nppro_is_new( $post_id ) {
	return ( time() - get_post_time( 'U', true, $post_id ) ) < (int) nppro_opt( 'new_badge_days' ) * DAY_IN_SECONDS;
}

/** State / UT / All India terms of a post. */
function nppro_states_of( $post_id ) {
	$out = array();
	$ok  = array_merge( nppro_location_slugs(), array( 'all-india' ) );
	foreach ( (array) get_the_category( $post_id ) as $t ) {
		if ( in_array( $t->slug, $ok, true ) ) {
			$out[] = $t;
		}
	}
	return $out;
}

function nppro_section_of( $post_id ) {
	foreach ( (array) get_the_category( $post_id ) as $t ) {
		if ( in_array( $t->slug, nppro_section_slugs(), true ) ) {
			return $t;
		}
	}
	return null;
}

function nppro_reading_time( $post_id ) {
	return max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) ) / 200 ) );
}

/**
 * Ordered rows for the overview table.
 *
 * @param int $post_id Post ID.
 * @return array label => value
 */
function nppro_overview_rows( $post_id ) {
	$map = array(
		'organization' => __( 'Organization', 'naukripatra' ), 'post_name' => __( 'Post Name', 'naukripatra' ),
		'vacancy' => __( 'Vacancy', 'naukripatra' ), 'qualification' => __( 'Qualification', 'naukripatra' ),
		'age_limit' => __( 'Age Limit', 'naukripatra' ), 'salary' => __( 'Salary', 'naukripatra' ),
		'application_fee' => __( 'Application Fee', 'naukripatra' ), 'application_mode' => __( 'Application Mode', 'naukripatra' ),
		'selection_process' => __( 'Selection Process', 'naukripatra' ), 'job_type' => __( 'Job Type', 'naukripatra' ),
		'job_location' => __( 'Job Location', 'naukripatra' ), 'department' => __( 'Department', 'naukripatra' ),
		'last_date' => __( 'Last Date', 'naukripatra' ),
	);
	$rows = array();
	foreach ( $map as $k => $label ) {
		$v = nppro_meta( $post_id, $k );
		if ( '' !== $v ) {
			$rows[ $label ] = $v;
		}
	}
	return $rows;
}

/**
 * Map free text employment type to Google enum.
 */
function nppro_employment_type( $text ) {
	$t = strtolower( $text );
	$map = array( 'apprentice' => 'OTHER', 'intern' => 'INTERN', 'contract' => 'CONTRACTOR', 'part' => 'PART_TIME', 'temporary' => 'TEMPORARY', 'full' => 'FULL_TIME', 'permanent' => 'FULL_TIME', 'regular' => 'FULL_TIME' );
	foreach ( $map as $needle => $enum ) {
		if ( false !== strpos( $t, $needle ) ) {
			return $enum;
		}
	}
	return 'FULL_TIME';
}
