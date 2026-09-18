<?php
/**
 * Job fields — ACF field group with a native metabox fallback.
 *
 * WHY BOTH:
 *   - ACF (free) gives the admin a clean, labelled form: Jobs > Add New.
 *   - If ACF is ever deactivated, WordPress would normally show nothing and an
 *     editor could re-save a job and silently blank the data. The fallback
 *     metabox renders the same fields against the same meta keys, so the data
 *     is always visible and always safe (rule #5).
 *
 * Both paths write identical meta keys (see np_job_field_schema()), so you can
 * switch between them at any time with no migration.
 *
 * ADMIN SCREEN: Jobs > Add New / Edit Job → "Job Details" box.
 *
 * @package Naukripatra
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the ACF field group in code.
 *
 * Code-registered groups cannot be accidentally deleted from the UI and travel
 * with the theme (no JSON import step during deployment).
 */
function np_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) || ! np_flag( 'jobs_cpt' ) ) {
		return;
	}

	$fields = array();
	$order  = 0;

	foreach ( np_job_field_schema() as $key => $meta ) {
		$field = array(
			'key'           => 'field_' . $key,
			'label'         => $meta['label'],
			'name'          => $key,
			'instructions'  => $meta['help'],
			'wrapper'       => array( 'width' => '50' ),
			'menu_order'    => $order++,
		);

		switch ( $meta['type'] ) {
			case 'date':
				$field['type']            = 'date_picker';
				$field['display_format']  = 'd/m/Y';
				$field['return_format']   = 'Ymd';
				$field['first_day']       = 1;
				break;
			case 'number':
				$field['type'] = 'number';
				$field['min']  = 0;
				break;
			case 'url':
				$field['type']          = 'url';
				$field['wrapper']['width'] = '100';
				break;
			default:
				$field['type'] = 'text';
		}

		$fields[] = $field;
	}

	acf_add_local_field_group(
		array(
			'key'        => 'group_np_job_details',
			'title'      => __( 'Job Details', 'naukripatra' ),
			'fields'     => $fields,
			'location'   => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => NP_JOB_POST_TYPE,
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'active'                => true,
			'description'           => __( 'Status badges and countdowns are calculated automatically from "Last Date to Apply".', 'naukripatra' ),
			'show_in_rest'          => true,
		)
	);
}
add_action( 'acf/init', 'np_register_acf_fields' );

/**
 * Fallback metabox, used only when ACF is not active.
 */
function np_register_fallback_metabox() {
	if ( function_exists( 'acf_add_local_field_group' ) || ! np_flag( 'jobs_cpt' ) ) {
		return;
	}

	add_meta_box(
		'np_job_details',
		__( 'Job Details', 'naukripatra' ),
		'np_render_fallback_metabox',
		NP_JOB_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'np_register_fallback_metabox' );

/**
 * Render the fallback metabox.
 *
 * @param WP_Post $post Current post.
 */
function np_render_fallback_metabox( $post ) {
	wp_nonce_field( 'np_save_job_details', 'np_job_details_nonce' );

	echo '<p class="description">' . esc_html__( 'Advanced Custom Fields is not active, so these basic fields are shown instead. The data is identical — activating ACF will show the same values in a nicer form.', 'naukripatra' ) . '</p>';
	echo '<table class="form-table"><tbody>';

	foreach ( np_job_field_schema() as $key => $meta ) {
		$value = get_post_meta( $post->ID, $key, true );
		$type  = 'date' === $meta['type'] ? 'text' : ( 'number' === $meta['type'] ? 'number' : ( 'url' === $meta['type'] ? 'url' : 'text' ) );

		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="regular-text"%5$s />%6$s</td></tr>',
			esc_attr( $key ),
			esc_html( $meta['label'] ),
			esc_attr( $type ),
			esc_attr( $value ),
			'date' === $meta['type'] ? ' placeholder="YYYYMMDD"' : '',
			$meta['help'] ? '<p class="description">' . esc_html( $meta['help'] ) . '</p>' : ''
		);
	}

	echo '</tbody></table>';
}

/**
 * Save the fallback metabox values.
 *
 * Only runs when ACF is absent; ACF handles saving otherwise. Never deletes a
 * value that was not part of the submitted form.
 *
 * @param int $post_id Post ID.
 */
function np_save_fallback_metabox( $post_id ) {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	if ( ! isset( $_POST['np_job_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['np_job_details_nonce'] ) ), 'np_save_job_details' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( np_job_field_schema() as $key => $meta ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $key ] );

		switch ( $meta['type'] ) {
			case 'url':
				$value = esc_url_raw( $raw );
				break;
			case 'number':
				$value = '' === $raw ? '' : (string) absint( $raw );
				break;
			default:
				$value = sanitize_text_field( $raw );
		}

		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_' . NP_JOB_POST_TYPE, 'np_save_fallback_metabox' );

/**
 * Add useful columns to the Jobs list table (Jobs > All Jobs).
 *
 * @param array $columns Existing columns.
 * @return array
 */
function np_job_admin_columns( $columns ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['np_last_date'] = __( 'Last Date', 'naukripatra' );
			$new['np_status']    = __( 'Status', 'naukripatra' );
		}
	}

	return $new;
}
add_filter( 'manage_' . NP_JOB_POST_TYPE . '_posts_columns', 'np_job_admin_columns' );

/**
 * Render the custom Jobs list columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function np_job_admin_column_content( $column, $post_id ) {
	if ( 'np_last_date' === $column ) {
		echo esc_html( np_format_date( np_job_field( 'np_last_date', $post_id ) ) );
	}

	if ( 'np_status' === $column ) {
		$status = np_job_status( $post_id );
		echo esc_html( $status['label'] );
	}
}
add_action( 'manage_' . NP_JOB_POST_TYPE . '_posts_custom_column', 'np_job_admin_column_content', 10, 2 );
