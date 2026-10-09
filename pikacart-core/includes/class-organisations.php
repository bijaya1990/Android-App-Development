<?php
/**
 * Organisation accounts: one row per registered organisation.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Organisations {

	/** Columns an organisation Admin may edit from the app. */
	const EDITABLE = array( 'name', 'contact_name', 'category_id', 'tagline', 'address', 'phone', 'org_email', 'website', 'signatory_name', 'signatory_title', 'event_name', 'session_year', 'terms' );

	/** Fields the verification page may show; hidden by default where noted. */
	public static function verify_field_defaults() {
		return array(
			'photo'       => true,
			'name'        => true,
			'id_no'       => true,
			'class'       => true,
			'designation' => true,
			'department'  => true,
			'validity'    => true,
			'blood_group' => false,
			'mobile'      => false,
			'email'       => false,
			'address'     => false,
			'dob'         => false,
		);
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'organisations' ) . ' WHERE id = %d', $id ) );
	}

	public static function get_by_user( $user_id ) {
		global $wpdb;
		if ( ! $user_id ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'organisations' ) . ' WHERE user_id = %d', $user_id ) );
	}

	public static function current() {
		$view = self::viewing_as();
		return $view ? self::get( $view ) : self::get_by_user( get_current_user_id() );
	}

	/**
	 * Super Admin "view as user": the organisation being viewed, or 0.
	 * Stored per admin as "org_id|started" and valid for two hours.
	 */
	public static function viewing_as() {
		$uid = get_current_user_id();
		if ( ! $uid || ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			return 0;
		}
		$raw = (string) get_user_meta( $uid, 'pkc_view_as', true );
		if ( '' === $raw ) {
			return 0;
		}
		list( $org_id, $started ) = array_map( 'intval', array_pad( explode( '|', $raw ), 2, 0 ) );
		if ( ! $org_id || $started < time() - 2 * HOUR_IN_SECONDS || ! self::get( $org_id ) ) {
			delete_user_meta( $uid, 'pkc_view_as' );
			return 0;
		}
		return $org_id;
	}

	public static function mobile_exists( $mobile, $exclude_id = 0 ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'organisations' ) . ' WHERE mobile = %s AND id <> %d LIMIT 1', $mobile, $exclude_id )
		);
	}

	/**
	 * Create the organisation row for a new user.
	 */
	public static function create( $user_id, $data, $trial_allowed = true ) {
		global $wpdb;
		$now     = pkc_now();
		$minutes = max( 1, (int) pkc_setting( 'trial_minutes', 120 ) );
		$wpdb->insert(
			pkc_table( 'organisations' ),
			array(
				'user_id'       => $user_id,
				'name'          => $data['name'],
				'contact_name'  => $data['contact_name'],
				'email'         => $data['email'],
				'mobile'        => $data['mobile'],
				'org_email'     => $data['email'],
				'phone'         => $data['mobile'],
				'status'        => PKC_Access::free_forever() ? 'free' : ( $trial_allowed ? 'trial' : 'expired' ),
				'trial_start'   => $now,
				'trial_end'     => $trial_allowed ? pkc_now( $minutes * 60 ) : $now,
				'verify_fields' => wp_json_encode( self::verify_field_defaults() ),
				'upload_dir'    => strtolower( pkc_token( 20 ) ),
				'meta'          => wp_json_encode( array() ),
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $data ) {
		global $wpdb;
		$data['updated_at'] = pkc_now();
		return false !== $wpdb->update( pkc_table( 'organisations' ), $data, array( 'id' => (int) $id ) );
	}

	public static function meta( $org, $key, $fallback = null ) {
		$meta = pkc_json( $org->meta ?? '' );
		return array_key_exists( $key, $meta ) ? $meta[ $key ] : $fallback;
	}

	public static function set_meta( $org_id, $key, $value ) {
		$org = self::get( $org_id );
		if ( ! $org ) {
			return;
		}
		$meta         = pkc_json( $org->meta );
		$meta[ $key ] = $value;
		self::update( $org_id, array( 'meta' => wp_json_encode( $meta ) ) );
	}

	/**
	 * Data sent to the organisation's own app. Never includes secrets.
	 */
	public static function to_app( $org ) {
		$verify = array_merge( self::verify_field_defaults(), pkc_json( $org->verify_fields ) );
		return array(
			'id'              => (int) $org->id,
			'name'            => $org->name,
			'contact_name'    => $org->contact_name,
			'email'           => $org->email,
			'mobile'          => $org->mobile,
			'category_id'     => (int) $org->category_id,
			'tagline'         => $org->tagline,
			'address'         => (string) $org->address,
			'phone'           => $org->phone,
			'org_email'       => $org->org_email,
			'website'         => $org->website,
			'logo'            => $org->logo,
			'sign'            => $org->sign,
			'seal'            => $org->seal,
			'signatory_name'  => $org->signatory_name,
			'signatory_title' => $org->signatory_title,
			'event_name'      => $org->event_name,
			'session_year'    => $org->session_year,
			'terms'           => (string) $org->terms,
			'verify_fields'   => $verify,
			'onboarded'       => (bool) $org->onboarded,
		);
	}

	/**
	 * Permanently delete an organisation, its data, files and WordPress user.
	 * Payment rows are kept for the owner's accounts.
	 */
	public static function delete( $org_id ) {
		global $wpdb;
		$org = self::get( $org_id );
		if ( ! $org ) {
			return false;
		}

		// Stop autopay at Razorpay so the customer is never charged again.
		$sub = PKC_Billing::active_subscription( $org_id );
		if ( $sub ) {
			PKC_Razorpay::request( 'POST', 'subscriptions/' . rawurlencode( $sub->rzp_subscription_id ) . '/cancel', array( 'cancel_at_cycle_end' => 0 ), $sub->mode );
		}

		foreach ( array( 'projects', 'members', 'selffill_links', 'design_requests', 'subscriptions', 'notices' ) as $table ) {
			$wpdb->delete( pkc_table( $table ), array( 'org_id' => $org_id ) );
		}
		$wpdb->delete( pkc_table( 'templates' ), array( 'owner_org_id' => $org_id ) );
		$tickets = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'tickets' ) . ' WHERE org_id = %d', $org_id ) );
		foreach ( $tickets as $tid ) {
			$wpdb->delete( pkc_table( 'ticket_replies' ), array( 'ticket_id' => (int) $tid ) );
		}
		$wpdb->delete( pkc_table( 'tickets' ), array( 'org_id' => $org_id ) );

		PKC_Uploads::delete_org_dir( $org );
		$wpdb->delete( pkc_table( 'organisations' ), array( 'id' => $org_id ) );

		if ( $org->user_id && ! user_can( (int) $org->user_id, 'edit_posts' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( (int) $org->user_id );
		}
		PKC_Activity_Log::add( 'account.deleted', $org_id, $org->name . ' (' . $org->email . ')' );
		return true;
	}
}
