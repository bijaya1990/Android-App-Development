<?php
/**
 * Organisations an account makes cards for (schools, colleges, offices...).
 *
 * One account (a school, a computer shop or a person) can keep many
 * organisations. Each has its own profile (name, logo, address, signature,
 * seal...) and its own projects, so the work of one never mixes with another.
 * The account always works inside one "active" organisation at a time.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Clients {

	/** Profile columns, named exactly like the matching organisation columns. */
	const PROFILE = array( 'name', 'category_id', 'tagline', 'address', 'phone', 'org_email', 'website', 'logo', 'sign', 'seal', 'signatory_name', 'signatory_title', 'event_name', 'session_year', 'terms', 'verify_fields' );

	/** @var array Request cache: org_id => checked. */
	private static $checked = array();

	public static function get( $org_id, $id ) {
		global $wpdb;
		if ( ! $id ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'clients' ) . ' WHERE id = %d AND org_id = %d AND deleted_at IS NULL', $id, $org_id ) );
	}

	/** Every organisation of an account with its project and card counts. */
	public static function list_for( $org_id ) {
		global $wpdb;
		$c = pkc_table( 'clients' );
		$p = pkc_table( 'projects' );
		$m = pkc_table( 'members' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT cl.*,
					(SELECT COUNT(*) FROM $p pr WHERE pr.client_id = cl.id AND pr.deleted_at IS NULL) AS projects,
					(SELECT COUNT(*) FROM $m mm INNER JOIN $p pp ON pp.id = mm.project_id WHERE pp.client_id = cl.id AND pp.deleted_at IS NULL AND mm.deleted_at IS NULL) AS cards,
					(SELECT MAX(pr2.updated_at) FROM $p pr2 WHERE pr2.client_id = cl.id AND pr2.deleted_at IS NULL) AS last_work
				FROM $c cl WHERE cl.org_id = %d AND cl.deleted_at IS NULL ORDER BY cl.name ASC, cl.id ASC",
				$org_id
			)
		);
	}

	public static function count_for( $org_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'clients' ) . ' WHERE org_id = %d AND deleted_at IS NULL', $org_id ) );
	}

	public static function create( $org_id, $data ) {
		global $wpdb;
		$row = array(
			'org_id'        => (int) $org_id,
			'verify_fields' => wp_json_encode( PKC_Organisations::verify_field_defaults() ),
			'created_at'    => pkc_now(),
			'updated_at'    => pkc_now(),
		);
		foreach ( self::PROFILE as $k ) {
			if ( array_key_exists( $k, $data ) && null !== $data[ $k ] ) {
				$row[ $k ] = $data[ $k ];
			}
		}
		$wpdb->insert( pkc_table( 'clients' ), $row );
		$id = (int) $wpdb->insert_id;
		if ( $id ) {
			PKC_Activity_Log::add( 'organisation.added', $org_id, (string) ( $row['name'] ?? '' ) );
		}
		return $id;
	}

	public static function update( $id, $data ) {
		global $wpdb;
		$clean = array();
		foreach ( self::PROFILE as $k ) {
			if ( array_key_exists( $k, $data ) ) {
				$clean[ $k ] = $data[ $k ];
			}
		}
		if ( ! $clean ) {
			return true;
		}
		$clean['updated_at'] = pkc_now();
		return false !== $wpdb->update( pkc_table( 'clients' ), $clean, array( 'id' => (int) $id ) );
	}

	/**
	 * Move an account to organisations the first time it is used after the update:
	 * the profile it already has becomes its first organisation and every existing
	 * project is placed inside it. Projects saved without an organisation are put
	 * in the active one. Nothing is ever lost.
	 */
	public static function ensure( $org ) {
		global $wpdb;
		if ( ! $org || isset( self::$checked[ $org->id ] ) ) {
			return;
		}
		self::$checked[ $org->id ] = true;
		$p      = pkc_table( 'projects' );
		$loose  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $p WHERE org_id = %d AND client_id = 0", $org->id ) );
		$count  = self::count_for( $org->id );
		$active = 0;
		if ( ! $count ) {
			$has_profile = $loose || (int) $org->onboarded || $org->logo || $org->sign || $org->address || $org->category_id;
			if ( ! $has_profile ) {
				return; // A new account: it adds its first organisation itself.
			}
			$data = array();
			foreach ( self::PROFILE as $k ) {
				$data[ $k ] = $org->{$k};
			}
			$active = self::create( $org->id, $data );
			PKC_Organisations::set_meta( $org->id, 'client', $active );
		}
		if ( $loose ) {
			$active = $active ? $active : self::active_id( $org );
			if ( $active ) {
				$wpdb->query( $wpdb->prepare( "UPDATE $p SET client_id = %d WHERE org_id = %d AND client_id = 0", $active, $org->id ) );
			}
		}
	}

	/** The organisation the account is working in (0 when it has none yet). */
	public static function active_id( $org ) {
		global $wpdb;
		$fresh = PKC_Organisations::get( $org->id );
		$id    = (int) PKC_Organisations::meta( $fresh ? $fresh : $org, 'client', 0 );
		if ( $id && self::get( $org->id, $id ) ) {
			return $id;
		}
		$first = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'clients' ) . ' WHERE org_id = %d AND deleted_at IS NULL ORDER BY updated_at DESC, id DESC LIMIT 1', $org->id ) );
		if ( $first !== $id ) {
			PKC_Organisations::set_meta( $org->id, 'client', $first );
		}
		return $first;
	}

	public static function active( $org ) {
		self::ensure( $org );
		return self::get( $org->id, self::active_id( $org ) );
	}

	public static function set_active( $org, $id ) {
		if ( ! self::get( $org->id, $id ) ) {
			return false;
		}
		PKC_Organisations::set_meta( $org->id, 'client', (int) $id );
		return true;
	}

	/**
	 * A copy of the account with the organisation's profile on top, so every
	 * existing screen, card and page shows that organisation's details.
	 */
	public static function overlay( $org, $client ) {
		if ( ! $org ) {
			return $org;
		}
		$out            = clone $org;
		$out->client_id = $client ? (int) $client->id : 0;
		if ( $client ) {
			foreach ( self::PROFILE as $k ) {
				$out->{$k} = $client->{$k};
			}
		}
		return $out;
	}

	/** The account seen through the organisation that owns a project. */
	public static function for_project( $org, $project_id ) {
		global $wpdb;
		if ( ! $org ) {
			return $org;
		}
		self::ensure( $org );
		$cid = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT client_id FROM ' . pkc_table( 'projects' ) . ' WHERE id = %d AND org_id = %d', $project_id, $org->id ) );
		$client = $cid ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'clients' ) . ' WHERE id = %d AND org_id = %d', $cid, $org->id ) ) : null;
		return self::overlay( $org, $client );
	}

	/**
	 * Remove an organisation. Its projects go with it; their people stay in the
	 * recycle bin for 30 days like any deleted project.
	 */
	public static function delete( $org, $id ) {
		global $wpdb;
		$client = self::get( $org->id, $id );
		if ( ! $client ) {
			return false;
		}
		$projects = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'projects' ) . ' WHERE org_id = %d AND client_id = %d AND deleted_at IS NULL', $org->id, $id ) );
		foreach ( $projects as $p ) {
			PKC_Projects::delete( $org, $p );
		}
		$wpdb->update( pkc_table( 'clients' ), array( 'deleted_at' => pkc_now() ), array( 'id' => (int) $id ) );
		PKC_Activity_Log::add( 'organisation.deleted', $org->id, $client->name );
		if ( (int) PKC_Organisations::meta( PKC_Organisations::get( $org->id ), 'client', 0 ) === (int) $id ) {
			PKC_Organisations::set_meta( $org->id, 'client', 0 );
		}
		return true;
	}

	public static function to_app( $c ) {
		return array(
			'id'          => (int) $c->id,
			'name'        => (string) $c->name,
			'logo'        => (string) $c->logo,
			'category_id' => (int) $c->category_id,
			'address'     => (string) $c->address,
			'projects'    => isset( $c->projects ) ? (int) $c->projects : 0,
			'cards'       => isset( $c->cards ) ? (int) $c->cards : 0,
			'last_work'   => ! empty( $c->last_work ) ? human_time_diff( pkc_ts( $c->last_work ), time() ) : '',
			'ready'       => '' !== trim( (string) $c->name ),
		);
	}
}
