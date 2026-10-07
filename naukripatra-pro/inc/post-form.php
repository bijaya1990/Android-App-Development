<?php
/**
 * Front-end "Post Job" form: saves a DRAFT; nothing goes live until approved in NaukriPatra Control.
 * Security: login + nonce, honeypot, per-user rate limit, field sanitising, file type/size checks, KSES.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_post_np_submit_job', 'np_handle_job_form' );
add_action( 'admin_post_nopriv_np_submit_job', function () {
	wp_safe_redirect( wp_login_url( home_url( '/post-job/' ) ) );
	exit;
} );

function np_form_redirect( $status ) {
	wp_safe_redirect( add_query_arg( 'np_status', $status, home_url( '/post-job/' ) ) );
	exit;
}

/** Validate and upload one file input; returns attachment ID, 0 (none) or WP_Error. */
function np_form_upload( $field, $post_id, $types, $max_kb ) {
	if ( empty( $_FILES[ $field ]['name'] ) ) {
		return 0;
	}
	$file = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( $file['size'] > $max_kb * 1024 ) {
		return new WP_Error( 'size', 'File too large.' );
	}
	$check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ), $types );
	if ( empty( $check['type'] ) || ! in_array( $check['type'], $types, true ) ) {
		return new WP_Error( 'type', 'File type not allowed.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_handle_upload( $field, $post_id, array(), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf' ) ) );
	return $id;
}

function np_handle_job_form() {
	if ( ! np_user_may_post() ) {
		wp_die( esc_html__( 'You are not allowed to post.', 'naukripatra' ), '', array( 'response' => 403 ) );
	}
	if ( ! isset( $_POST['np_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['np_nonce'] ) ), 'np_submit_job' ) ) {
		wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'naukripatra' ), '', array( 'response' => 403 ) );
	}
	if ( ! empty( $_POST['np_website'] ) ) { // Honeypot.
		np_form_redirect( 'ok' );
	}
	$uid  = get_current_user_id();
	$key  = 'np_rate_' . $uid;
	$used = (int) get_transient( $key );
	if ( $used >= max( 1, (int) np_opt( 'form_rate' ) ) ) {
		np_form_redirect( 'rate' );
	}
	$t = function ( $k ) {
		return isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
	};
	$title = $t( 'np_title' );
	if ( '' === $title ) {
		np_form_redirect( 'title' );
	}
	$content = wp_kses_post( wp_unslash( isset( $_POST['np_article'] ) ? $_POST['np_article'] : '' ) );
	$cats    = isset( $_POST['np_cats'] ) ? array_filter( array_map( 'absint', (array) $_POST['np_cats'] ) ) : array();
	$post_id = wp_insert_post(
		array(
			'post_title' => $title, 'post_content' => $content, 'post_status' => 'draft', 'post_type' => 'post',
			'post_author' => $uid, 'post_category' => $cats,
			'tags_input' => array_map( 'trim', explode( ',', $t( 'np_tags' ) ) ),
			'post_name' => sanitize_title( $t( 'y_slug' ) ),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		np_form_redirect( 'error' );
	}
	update_post_meta( $post_id, '_np_front_submission', 1 );

	// 16 plugin API fields (saved under the plugin's real key, honouring the prefix setting).
	$prefix = trim( (string) np_opt( 'meta_prefix' ) );
	foreach ( np_api_fields() as $f ) {
		$v = in_array( $f, array( 'apply_link', 'notification_link', 'official_website' ), true ) ? esc_url_raw( wp_unslash( isset( $_POST[ 'f_' . $f ] ) ? $_POST[ 'f_' . $f ] : '' ) ) : $t( 'f_' . $f );
		if ( 'apply_link' === $f && '' === $v ) {
			$v = $t( 'f_apply_link' ); // Allows "url / url" multi-link text.
		}
		if ( '' !== $v ) {
			update_post_meta( $post_id, $prefix . $f, $v );
		}
	}
	// Overrides (schema group) and new advt_no.
	$ov = array( 'job_sector' => 'o_sector', 'qualification' => 'o_qualification', 'last_date' => 'o_last_date', 'posts_count' => 'o_posts', 'apply_link' => 'o_apply', 'notification_link' => 'o_notification', 'application_fee' => 'o_fee' );
	foreach ( $ov as $meta => $field ) {
		if ( '' !== $t( $field ) ) {
			update_post_meta( $post_id, '_np_' . $meta, $t( $field ) );
		}
	}
	if ( '' !== $t( 'o_advt' ) ) {
		update_post_meta( $post_id, 'advt_no', $t( 'o_advt' ) );
	}
	// Yoast block.
	foreach ( np_yoast_keys() as $field => $ykey ) {
		$v = ( 'canonical' === $field ) ? esc_url_raw( wp_unslash( isset( $_POST['y_canonical'] ) ? $_POST['y_canonical'] : '' ) ) : $t( 'y_' . $field );
		if ( '' !== $v ) {
			update_post_meta( $post_id, $ykey, $v );
		}
	}
	// Files.
	$errors = array();
	$img    = np_form_upload( 'np_image', $post_id, array( 'image/jpeg', 'image/png', 'image/webp' ), 2048 );
	if ( is_wp_error( $img ) ) {
		$errors[] = 'image';
	} elseif ( $img ) {
		set_post_thumbnail( $post_id, $img ); // Triggers the Yoast image sync.
	}
	$pdf = np_form_upload( 'np_pdf', $post_id, array( 'application/pdf' ), 5120 );
	if ( is_wp_error( $pdf ) ) {
		$errors[] = 'pdf';
	} elseif ( $pdf && '' === (string) get_post_meta( $post_id, $prefix . 'notification_link', true ) ) {
		update_post_meta( $post_id, $prefix . 'notification_link', esc_url_raw( wp_get_attachment_url( $pdf ) ) );
	}

	set_transient( $key, $used + 1, HOUR_IN_SECONDS );
	$to = np_opt( 'notify_email' ) ? np_opt( 'notify_email' ) : get_option( 'admin_email' );
	wp_mail( $to, '[' . np_opt( 'brand_text' ) . '] New job submitted: ' . $title, "A new job was submitted for approval.\n\nReview: " . admin_url( 'admin.php?page=naukripatra-control&tab=data' ) );
	np_form_redirect( $errors ? 'ok-files' : 'ok' );
}

/** Render a category checklist group. */
function np_cat_checklist( $title, $slugs ) {
	echo '<fieldset class="np-fs"><legend>' . esc_html( $title ) . '</legend><div class="np-checks">';
	foreach ( $slugs as $slug ) {
		$t = get_term_by( 'slug', $slug, 'category' );
		if ( $t ) {
			printf( '<label><input type="checkbox" name="np_cats[]" value="%d"> %s</label>', (int) $t->term_id, esc_html( $t->name ) );
		}
	}
	echo '</div></fieldset>';
}

// Admin: approval actions (Approve & Publish / Reject).
add_action( 'admin_post_np_review', function () {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( ! $id || ! current_user_can( 'publish_posts' ) || ! current_user_can( 'edit_post', $id ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'np_review_' . $id ) ) {
		wp_die( esc_html__( 'Not allowed.', 'naukripatra' ), '', array( 'response' => 403 ) );
	}
	$do = isset( $_GET['do'] ) ? sanitize_key( $_GET['do'] ) : '';
	if ( 'approve' === $do ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	} elseif ( 'reject' === $do ) {
		wp_trash_post( $id );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=naukripatra-control&tab=data' ) );
	exit;
} );
