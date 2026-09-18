<?php
/**
 * Shared helpers: field access, date handling, status calculation.
 *
 * DATA-SAFETY RULE #5: every job field is stored as ordinary WordPress post
 * meta. ACF is only a nicer editing UI on top of it. If ACF is deactivated,
 * the data stays in wp_postmeta untouched and np_job_field() keeps reading it
 * through get_post_meta(). Nothing here ever deletes meta.
 *
 * @package Naukripatra
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canonical list of job meta keys.
 *
 * Key => admin label. Used by the ACF field group and by the native metabox
 * fallback so both write to exactly the same meta keys.
 *
 * @return array<string,array{label:string,type:string,help:string}>
 */
function np_job_field_schema() {
	return array(
		'np_organization'     => array(
			'label' => __( 'Organization / Department', 'naukripatra' ),
			'type'  => 'text',
			'help'  => __( 'e.g. Staff Selection Commission (SSC)', 'naukripatra' ),
		),
		'np_vacancy_count'    => array(
			'label' => __( 'Total Vacancies', 'naukripatra' ),
			'type'  => 'number',
			'help'  => __( 'Numbers only. Leave empty if not announced.', 'naukripatra' ),
		),
		'np_advt_number'      => array(
			'label' => __( 'Advertisement No.', 'naukripatra' ),
			'type'  => 'text',
			'help'  => __( 'e.g. 05/2026', 'naukripatra' ),
		),
		'np_notification_date' => array(
			'label' => __( 'Notification Date', 'naukripatra' ),
			'type'  => 'date',
			'help'  => '',
		),
		'np_apply_start_date' => array(
			'label' => __( 'Apply Start Date', 'naukripatra' ),
			'type'  => 'date',
			'help'  => '',
		),
		'np_last_date'        => array(
			'label' => __( 'Last Date to Apply', 'naukripatra' ),
			'type'  => 'date',
			'help'  => __( 'Drives the status badge automatically — no manual tagging needed.', 'naukripatra' ),
		),
		'np_exam_date'        => array(
			'label' => __( 'Exam Date', 'naukripatra' ),
			'type'  => 'date',
			'help'  => '',
		),
		'np_application_mode' => array(
			'label' => __( 'Application Mode', 'naukripatra' ),
			'type'  => 'text',
			'help'  => __( 'e.g. Online / Offline', 'naukripatra' ),
		),
		'np_location'         => array(
			'label' => __( 'Job Location', 'naukripatra' ),
			'type'  => 'text',
			'help'  => __( 'e.g. All India, Patna, Hyderabad', 'naukripatra' ),
		),
		'np_apply_link'       => array(
			'label' => __( 'Apply Online Link', 'naukripatra' ),
			'type'  => 'url',
			'help'  => __( 'Full URL starting with https://', 'naukripatra' ),
		),
		'np_notification_pdf' => array(
			'label' => __( 'Notification PDF Link', 'naukripatra' ),
			'type'  => 'url',
			'help'  => '',
		),
		'np_official_website' => array(
			'label' => __( 'Official Website Link', 'naukripatra' ),
			'type'  => 'url',
			'help'  => '',
		),
	);
}

/**
 * Read a job field.
 *
 * Uses ACF when available (so ACF formatting/return types apply), otherwise
 * falls back to raw post meta. Always safe to call.
 *
 * @param string   $key     Meta key, e.g. 'np_last_date'.
 * @param int|null $post_id Post ID. Defaults to current post.
 * @return mixed Empty string when unset.
 */
function np_job_field( $key, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $key, $post_id );
		if ( null !== $value && '' !== $value && false !== $value ) {
			return $value;
		}
	}

	$value = get_post_meta( $post_id, $key, true );

	return ( '' === $value || null === $value ) ? '' : $value;
}

/**
 * Normalise a stored date into a DateTimeImmutable (site timezone, midnight).
 *
 * Accepts the ACF picker format (Ymd), ISO (Y-m-d) and most human formats, so
 * it keeps working whether the value was written by ACF, the fallback metabox
 * or an import.
 *
 * @param string $value Stored date value.
 * @return DateTimeImmutable|null
 */
function np_parse_date( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( '' === $value ) {
		return null;
	}

	$tz = wp_timezone();

	foreach ( array( 'Ymd', 'Y-m-d', 'd/m/Y', 'd-m-Y' ) as $format ) {
		$date = DateTimeImmutable::createFromFormat( '!' . $format, $value, $tz );
		if ( $date instanceof DateTimeImmutable ) {
			return $date;
		}
	}

	try {
		return ( new DateTimeImmutable( $value, $tz ) )->setTime( 0, 0 );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Format a stored date for display.
 *
 * @param string $value  Stored date value.
 * @param string $format PHP date format.
 * @return string Em dash when the date is missing/unparseable.
 */
function np_format_date( $value, $format = 'd M Y' ) {
	$date = np_parse_date( $value );

	return $date ? $date->format( $format ) : '—';
}

/**
 * Whole days remaining until the last date.
 *
 * @param string $last_date Stored last-date value.
 * @return int|null Null when there is no usable last date. Negative when past.
 */
function np_days_remaining( $last_date ) {
	$date = np_parse_date( $last_date );
	if ( ! $date ) {
		return null;
	}

	$today = ( new DateTimeImmutable( 'now', wp_timezone() ) )->setTime( 0, 0 );

	return (int) $today->diff( $date )->format( '%r%a' );
}

/**
 * Auto-calculated application status for a job.
 *
 * No manual tagging: derived purely from np_last_date.
 *   > 7 days left  → open   (Apply Now)
 *   <= 7 days left → soon   (Closing Soon)
 *   past last date → closed (Closed)
 *   no last date   → open   (treated as open-until-further-notice)
 *
 * @param int|null $post_id Post ID.
 * @return array{key:string,label:string,class:string}
 */
function np_job_status( $post_id = null ) {
	$days = np_days_remaining( np_job_field( 'np_last_date', $post_id ) );

	if ( null === $days ) {
		$key   = 'open';
		$label = __( 'Apply Now', 'naukripatra' );
	} elseif ( $days < 0 ) {
		$key   = 'closed';
		$label = __( 'Closed', 'naukripatra' );
	} elseif ( $days <= 7 ) {
		$key   = 'soon';
		$label = __( 'Closing Soon', 'naukripatra' );
	} else {
		$key   = 'open';
		$label = __( 'Apply Now', 'naukripatra' );
	}

	return array(
		'key'   => $key,
		'label' => $label,
		'class' => 'np-status np-status--' . $key,
	);
}
