<?php
/**
 * Logged-in account endpoints: profile, organisation details, uploads, onboarding.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Account {

	public static function routes() {
		$routes = array(
			array( 'me', 'GET', 'me' ),
			array( 'me/profile', 'POST', 'profile' ),
			array( 'me/password', 'POST', 'password' ),
			array( 'me/resend-verification', 'POST', 'resend' ),
			array( 'me/delete-request', 'POST', 'delete_request' ),
			array( 'org', 'POST', 'org' ),
			array( 'org/upload', 'POST', 'upload' ),
			array( 'org/remove-image', 'POST', 'remove_image' ),
			array( 'onboarding', 'POST', 'onboarding' ),
		);
		foreach ( $routes as $r ) {
			register_rest_route(
				PKC_REST::NS,
				'/' . $r[0],
				array(
					'methods'             => $r[1],
					'callback'            => array( __CLASS__, $r[2] ),
					'permission_callback' => array( 'PKC_REST', 'can_org' ),
				)
			);
		}
	}

	/**
	 * Everything the app needs on load.
	 */
	public static function me_payload( $org ) {
		global $wpdb;
		$user = get_userdata( (int) $org->user_id );
		$plan = PKC_Billing::default_plan();
		$sub  = PKC_Billing::active_subscription( $org->id );

		$projects = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'projects' ) . ' WHERE org_id = %d AND deleted_at IS NULL', $org->id ) );
		$by_status = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS n FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND deleted_at IS NULL GROUP BY status', $org->id ) );
		$cards     = array(
			'draft'     => 0,
			'ready'     => 0,
			'printed'   => 0,
			'expired'   => 0,
			'cancelled' => 0,
		);
		foreach ( $by_status as $row ) {
			$cards[ $row->status ] = (int) $row->n;
		}

		return array(
			'user'     => array(
				'name'     => $user ? $user->display_name : '',
				'email'    => $user ? $user->user_email : '',
				'verified' => $user ? PKC_Accounts::is_verified( $user->ID ) : false,
			),
			'org'      => PKC_Organisations::to_app( $org ),
			'state'    => array(
				'status'     => $org->status,
				'label'      => PKC_Access::label( $org->status ),
				'trial_end'  => pkc_ts( $org->trial_end ),
				'period_end' => pkc_ts( $org->period_end ),
				'period_end_text' => $org->period_end ? pkc_date( $org->period_end, get_option( 'date_format', 'j M Y' ) ) : '',
				'now'        => time(),
				'can_work'   => PKC_Access::can_work( $org ),
				'watermark'  => PKC_Access::needs_watermark( $org ),
				'autopay'    => (bool) $sub,
			),
			'plan'     => $plan ? PKC_Billing::plan_to_app( $plan ) : null,
			'stats'    => array(
				'projects' => $projects,
				'cards'    => array_sum( $cards ),
				'by_status' => $cards,
			),
			'catalog'  => self::catalog(),
			'notices'  => self::notices( $org->id ),
			'links'    => array(
				'terms'   => pkc_page_url( 'terms' ),
				'privacy' => pkc_page_url( 'privacy' ),
				'refund'  => pkc_page_url( 'refund' ),
				'contact' => pkc_page_url( 'contact' ),
			),
		);
	}

	/** Visible categories with their sub-types. */
	public static function catalog() {
		global $wpdb;
		$cats = $wpdb->get_results( 'SELECT id, slug, name, description, icon FROM ' . pkc_table( 'categories' ) . ' WHERE is_hidden = 0 ORDER BY sort_order ASC, id ASC' );
		$subs = $wpdb->get_results( 'SELECT id, category_id, slug, name FROM ' . pkc_table( 'subtypes' ) . ' WHERE is_hidden = 0 ORDER BY sort_order ASC, id ASC' );
		$out  = array();
		foreach ( $cats as $c ) {
			$item = array(
				'id'          => (int) $c->id,
				'slug'        => $c->slug,
				'name'        => $c->name,
				'description' => (string) $c->description,
				'icon'        => $c->icon,
				'subtypes'    => array(),
			);
			foreach ( $subs as $s ) {
				if ( (int) $s->category_id === (int) $c->id ) {
					$item['subtypes'][] = array(
						'id'   => (int) $s->id,
						'name' => $s->name,
					);
				}
			}
			$out[] = $item;
		}
		return $out;
	}

	/** Announcements for everyone or for this account. */
	public static function notices( $org_id ) {
		global $wpdb;
		$now  = pkc_now();
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, title, body, created_at FROM ' . pkc_table( 'notices' ) . ' WHERE org_id IN (0, %d) AND (starts_at IS NULL OR starts_at <= %s) AND (ends_at IS NULL OR ends_at >= %s) ORDER BY id DESC LIMIT 5',
				$org_id,
				$now,
				$now
			)
		);
		return array_map(
			function ( $n ) {
				return array(
					'id'    => (int) $n->id,
					'title' => $n->title,
					'body'  => wp_kses_post( (string) $n->body ),
					'date'  => pkc_date( $n->created_at, get_option( 'date_format', 'j M Y' ) ),
				);
			},
			$rows
		);
	}

	public static function me() {
		return PKC_REST::ok( self::me_payload( PKC_REST::org() ) );
	}

	public static function profile( WP_REST_Request $r ) {
		$org     = PKC_REST::org();
		$contact = sanitize_text_field( (string) $r->get_param( 'contact_name' ) );
		$mobile  = pkc_normalize_mobile( (string) $r->get_param( 'mobile' ) );

		if ( mb_strlen( $contact ) < 2 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter your name.', 'pikacart' ), array( 'status' => 400, 'field' => 'contact_name' ) );
		}
		if ( ! PKC_Accounts::valid_mobile( $mobile ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter a valid mobile number.', 'pikacart' ), array( 'status' => 400, 'field' => 'mobile' ) );
		}
		if ( PKC_Organisations::mobile_exists( $mobile, $org->id ) ) {
			return new WP_Error( 'pkc_invalid', __( 'This mobile number is used by another account.', 'pikacart' ), array( 'status' => 400, 'field' => 'mobile' ) );
		}
		PKC_Organisations::update(
			$org->id,
			array(
				'contact_name' => $contact,
				'mobile'       => $mobile,
			)
		);
		wp_update_user(
			array(
				'ID'           => (int) $org->user_id,
				'display_name' => $contact,
				'first_name'   => $contact,
			)
		);
		PKC_Activity_Log::add( 'profile.updated', $org->id );
		return PKC_REST::ok(
			array(
				'message' => __( 'Profile saved.', 'pikacart' ),
				'me'      => self::me_payload( PKC_Organisations::get( $org->id ) ),
			)
		);
	}

	public static function password( WP_REST_Request $r ) {
		$result = PKC_Accounts::change_password( get_current_user_id(), (string) $r->get_param( 'current_password' ), (string) $r->get_param( 'new_password' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		// The login cookie changed, so the app needs a fresh nonce.
		$result['nonce'] = wp_create_nonce( 'wp_rest' );
		return PKC_REST::ok( $result );
	}

	public static function resend() {
		if ( ! PKC_Rate_Limit::hit( 'resend_verify', (string) get_current_user_id(), 3, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		PKC_Accounts::send_verification( get_current_user_id() );
		return PKC_REST::ok( array( 'message' => __( 'We have sent a new verification link to your email.', 'pikacart' ) ) );
	}

	public static function delete_request( WP_REST_Request $r ) {
		$org    = PKC_REST::org();
		$reason = sanitize_textarea_field( (string) $r->get_param( 'reason' ) );
		PKC_Organisations::set_meta( $org->id, 'delete_requested', pkc_now() );
		PKC_Activity_Log::add( 'deletion.requested', $org->id, $reason );
		$admin = pkc_support_email();
		wp_mail(
			$admin,
			/* translators: %s: organisation name */
			sprintf( __( 'Account deletion request: %s', 'pikacart' ), $org->name ),
			sprintf(
				"%s\n%s\n%s\n\n%s\n\n%s",
				$org->name,
				$org->email,
				$org->mobile,
				$reason,
				admin_url( 'admin.php?page=pikacart-accounts&org=' . (int) $org->id )
			)
		);
		return PKC_REST::ok( array( 'message' => __( 'Your request has been sent. We will delete your account and data and confirm by email.', 'pikacart' ) ) );
	}

	/**
	 * Save organisation details (and verification privacy settings).
	 */
	public static function org( WP_REST_Request $r ) {
		$org  = PKC_REST::org();
		$data = self::clean_org_fields( $r );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		PKC_Organisations::update( $org->id, $data );
		PKC_Activity_Log::add( 'org.updated', $org->id );
		return PKC_REST::ok(
			array(
				'message' => __( 'Organisation details saved.', 'pikacart' ),
				'me'      => self::me_payload( PKC_Organisations::get( $org->id ) ),
			)
		);
	}

	private static function clean_org_fields( WP_REST_Request $r ) {
		$data = array();
		foreach ( PKC_Organisations::EDITABLE as $key ) {
			if ( ! $r->has_param( $key ) ) {
				continue;
			}
			$value = $r->get_param( $key );
			switch ( $key ) {
				case 'category_id':
					$data[ $key ] = absint( $value );
					break;
				case 'address':
				case 'terms':
					$data[ $key ] = sanitize_textarea_field( (string) $value );
					break;
				case 'org_email':
					$value = sanitize_email( (string) $value );
					if ( '' !== $value && ! is_email( $value ) ) {
						return new WP_Error( 'pkc_invalid', __( 'Please enter a valid organisation email.', 'pikacart' ), array( 'status' => 400, 'field' => 'org_email' ) );
					}
					$data[ $key ] = $value;
					break;
				case 'website':
					$data[ $key ] = esc_url_raw( (string) $value );
					break;
				default:
					$data[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		if ( isset( $data['name'] ) && mb_strlen( $data['name'] ) < 2 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter your organisation name.', 'pikacart' ), array( 'status' => 400, 'field' => 'name' ) );
		}
		if ( $r->has_param( 'verify_fields' ) && is_array( $r->get_param( 'verify_fields' ) ) ) {
			$in    = $r->get_param( 'verify_fields' );
			$clean = array();
			foreach ( PKC_Organisations::verify_field_defaults() as $k => $default ) {
				$clean[ $k ] = ! empty( $in[ $k ] );
			}
			$data['verify_fields'] = wp_json_encode( $clean );
		}
		return $data;
	}

	/**
	 * Upload logo, signature or seal (PNG keeps transparency).
	 */
	public static function upload( WP_REST_Request $r ) {
		$org   = PKC_REST::org();
		$type  = sanitize_key( (string) $r->get_param( 'type' ) );
		$files = $r->get_file_params();
		if ( ! in_array( $type, array( 'logo', 'sign', 'seal' ), true ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Unknown image type.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( ! PKC_Rate_Limit::hit( 'upload', (string) $org->id, 60, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$saved = PKC_Uploads::save_image( $files['file'] ?? null, $org, $type );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		PKC_Uploads::delete_url( $org, $org->{$type} );
		PKC_Organisations::update( $org->id, array( $type => esc_url_raw( $saved['url'] ) ) );
		return PKC_REST::ok(
			array(
				'message' => __( 'Image uploaded.', 'pikacart' ),
				'url'     => $saved['url'],
				'me'      => self::me_payload( PKC_Organisations::get( $org->id ) ),
			)
		);
	}

	public static function remove_image( WP_REST_Request $r ) {
		$org  = PKC_REST::org();
		$type = sanitize_key( (string) $r->get_param( 'type' ) );
		if ( ! in_array( $type, array( 'logo', 'sign', 'seal' ), true ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Unknown image type.', 'pikacart' ), array( 'status' => 400 ) );
		}
		PKC_Uploads::delete_url( $org, $org->{$type} );
		PKC_Organisations::update( $org->id, array( $type => '' ) );
		return PKC_REST::ok(
			array(
				'message' => __( 'Image removed.', 'pikacart' ),
				'me'      => self::me_payload( PKC_Organisations::get( $org->id ) ),
			)
		);
	}

	public static function onboarding( WP_REST_Request $r ) {
		$org  = PKC_REST::org();
		$data = self::clean_org_fields( $r );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( $r->get_param( 'finish' ) ) {
			$data['onboarded'] = 1;
			PKC_Activity_Log::add( 'onboarding.completed', $org->id );
		}
		PKC_Organisations::update( $org->id, $data );
		return PKC_REST::ok(
			array(
				'message' => __( 'Saved.', 'pikacart' ),
				'me'      => self::me_payload( PKC_Organisations::get( $org->id ) ),
			)
		);
	}
}
