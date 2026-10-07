<?php
/**
 * REST compatibility. GOLDEN RULE: never override a key the plugin/old theme already registers.
 * Old keys are re-declared only if absent; advt_no is a new, additive key.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'np_rest_guarded_fields', 99 );
function np_rest_guarded_fields() {
	global $wp_rest_additional_fields;
	$old = array( 'qualification', 'last_date', 'posts_count', 'organization', 'salary', 'employment_type', 'locality', 'street', 'postal_code', 'job_details', 'advt_no' );
	foreach ( $old as $field ) {
		if ( isset( $wp_rest_additional_fields['post'][ $field ] ) ) {
			continue; // Already supplied by plugin/old theme: leave untouched.
		}
		$meta = ( 'advt_no' === $field ) ? 'advt_no' : '_np_' . $field;
		register_rest_field(
			'post',
			$field,
			array(
				'get_callback'    => function ( $obj ) use ( $meta ) {
					return (string) get_post_meta( $obj['id'], $meta, true );
				},
				'update_callback' => function ( $value, $post ) use ( $meta ) {
					if ( current_user_can( 'edit_post', $post->ID ) ) {
						update_post_meta( $post->ID, $meta, sanitize_text_field( $value ) );
					}
					return true;
				},
				'schema'          => array( 'type' => 'string', 'context' => array( 'view', 'edit' ) ),
			)
		);
	}
}

// Lightweight view counter (no per-visitor HTML, so pages stay cacheable).
add_action( 'rest_api_init', function () {
	register_rest_route(
		'naukripatra/v1',
		'/view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => function ( $req ) {
				$id = (int) $req['id'];
				if ( 'publish' === get_post_status( $id ) && 'post' === get_post_type( $id ) ) {
					update_post_meta( $id, '_np_views', (int) get_post_meta( $id, '_np_views', true ) + 1 );
				}
				return new WP_REST_Response( null, 204 );
			},
		)
	);
} );

// advt_no meta box in the post editor (additive field).
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'np_advt', __( 'Advertisement No', 'naukripatra' ), function ( $post ) {
		wp_nonce_field( 'np_advt', 'np_advt_nonce' );
		printf( '<input type="text" class="widefat" name="np_advt_no" value="%s"><p class="description">%s</p>', esc_attr( get_post_meta( $post->ID, 'advt_no', true ) ), esc_html__( 'Leave empty to auto-detect from the overview table or title.', 'naukripatra' ) );
	}, 'post', 'side' );
} );
add_action( 'save_post_post', function ( $id ) {
	if ( ! isset( $_POST['np_advt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['np_advt_nonce'] ) ), 'np_advt' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	update_post_meta( $id, 'advt_no', sanitize_text_field( wp_unslash( isset( $_POST['np_advt_no'] ) ? $_POST['np_advt_no'] : '' ) ) );
} );
