<?php
/**
 * People on a card project (students, staff, members...).
 * Every query is filtered by the organisation ID.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Members {

	const STATUSES = array( 'draft', 'ready', 'printed', 'expired', 'cancelled', 'pending' );

	/** Field keys a person can have (plus cf1..cf5). */
	const KEYS = array( 'name', 'id_no', 'class', 'section', 'designation', 'department', 'mobile', 'email', 'dob', 'blood_group', 'guardian', 'address', 'emergency', 'valid_from', 'valid_until', 'cf1', 'cf2', 'cf3', 'cf4', 'cf5' );

	public static function status_labels() {
		return array(
			'draft'     => __( 'Draft', 'pikacart' ),
			'ready'     => __( 'Ready', 'pikacart' ),
			'printed'   => __( 'Printed', 'pikacart' ),
			'expired'   => __( 'Expired', 'pikacart' ),
			'cancelled' => __( 'Cancelled', 'pikacart' ),
			'pending'   => __( 'Pending approval', 'pikacart' ),
		);
	}

	public static function get( $org_id, $id, $with_deleted = false ) {
		global $wpdb;
		$extra = $with_deleted ? '' : ' AND deleted_at IS NULL';
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'members' ) . " WHERE id = %d AND org_id = %d$extra", $id, $org_id ) );
	}

	public static function by_token( $token ) {
		global $wpdb;
		if ( ! preg_match( '/^[A-Za-z0-9]{20,64}$/', (string) $token ) ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'members' ) . ' WHERE verify_token = %s', $token ) );
	}

	/** Accept dd-mm-yyyy, dd/mm/yyyy, yyyy-mm-dd and Excel serial numbers. */
	public static function parse_date( $v ) {
		$v = trim( (string) $v );
		if ( '' === $v ) {
			return null;
		}
		if ( preg_match( '/^\d{5}$/', $v ) ) {
			return gmdate( 'Y-m-d', ( (int) $v - 25569 ) * DAY_IN_SECONDS );
		}
		if ( preg_match( '/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $v, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			return sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] );
		}
		if ( preg_match( '/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})$/', $v, $m ) ) {
			$y = (int) $m[3];
			$y = $y < 100 ? 2000 + $y : $y;
			if ( checkdate( (int) $m[2], (int) $m[1], $y ) ) {
				return sprintf( '%04d-%02d-%02d', $y, $m[2], $m[1] );
			}
		}
		$ts = strtotime( $v );
		return $ts ? gmdate( 'Y-m-d', $ts ) : false;
	}

	public static function format_date( $ymd ) {
		if ( ! $ymd || '0000-00-00' === $ymd ) {
			return '';
		}
		$ts = strtotime( $ymd . ' 00:00:00 UTC' );
		return $ts ? gmdate( 'd-m-Y', $ts ) : '';
	}

	/**
	 * Clean incoming person data. Returns [ columns, data-json-array ] or WP_Error.
	 *
	 * @param array  $in       Raw values.
	 * @param object $project  Project row (for required fields).
	 * @param bool   $partial  Allow missing required fields (self-fill drafts).
	 */
	public static function clean( $in, $project, $partial = false ) {
		$data = array();
		foreach ( self::KEYS as $k ) {
			if ( ! array_key_exists( $k, $in ) ) {
				continue;
			}
			$v = in_array( $k, array( 'address' ), true ) ? sanitize_textarea_field( (string) $in[ $k ] ) : sanitize_text_field( (string) $in[ $k ] );
			$data[ $k ] = mb_substr( trim( $v ), 0, 'address' === $k ? 300 : 150 );
		}
		$name  = $data['name'] ?? '';
		$id_no = isset( $data['id_no'] ) ? preg_replace( '/\s+/', ' ', $data['id_no'] ) : '';
		if ( ! $partial ) {
			if ( mb_strlen( $name ) < 1 ) {
				return new WP_Error( 'pkc_invalid', __( 'Full name is required.', 'pikacart' ), array( 'status' => 400, 'field' => 'name' ) );
			}
			if ( '' === $id_no ) {
				return new WP_Error( 'pkc_invalid', __( 'Roll number / ID number is required.', 'pikacart' ), array( 'status' => 400, 'field' => 'id_no' ) );
			}
		}
		if ( isset( $data['mobile'] ) && '' !== $data['mobile'] && ! preg_match( '/^[0-9+\-\s()]{6,20}$/', $data['mobile'] ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Mobile number looks wrong.', 'pikacart' ), array( 'status' => 400, 'field' => 'mobile' ) );
		}
		if ( isset( $data['email'] ) && '' !== $data['email'] && ! is_email( $data['email'] ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Email address looks wrong.', 'pikacart' ), array( 'status' => 400, 'field' => 'email' ) );
		}
		$cols = array(
			'name'  => $name,
			'id_no' => $id_no,
		);
		foreach ( array( 'valid_from', 'valid_until' ) as $dk ) {
			if ( array_key_exists( $dk, $data ) ) {
				$d = self::parse_date( $data[ $dk ] );
				if ( false === $d ) {
					return new WP_Error( 'pkc_invalid', __( 'Please enter dates as DD-MM-YYYY.', 'pikacart' ), array( 'status' => 400, 'field' => $dk ) );
				}
				$cols[ $dk ] = $d;
				$data[ $dk ] = self::format_date( $d );
			}
		}
		unset( $data['name'], $data['id_no'] );
		return array( $cols, $data );
	}

	/** Is this ID number already used in the account for the same session? */
	public static function id_taken( $org_id, $id_no, $session, $exclude = 0 ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND id_no = %s AND session = %s AND id <> %d AND deleted_at IS NULL LIMIT 1',
				$org_id,
				$id_no,
				(string) $session,
				$exclude
			)
		);
	}

	/** Required fields that are switched on for the project. */
	public static function required_keys( $project ) {
		$settings = pkc_json( $project->fields );
		$on       = (array) ( $settings['on'] ?? array() );
		$subtype  = PKC_Catalog::subtype( $project->subtype_id );
		$req      = array();
		foreach ( pkc_json( $subtype ? $subtype->fields : '[]' ) as $f ) {
			if ( ! empty( $f['required'] ) && in_array( $f['key'], $on, true ) && 'photo' !== $f['key'] ) {
				$req[] = $f['key'];
			}
		}
		return $req;
	}

	/** Draft until the photo and required fields are present. */
	public static function auto_status( $row_cols, $data, $photo, $project, $current = '' ) {
		if ( in_array( $current, array( 'printed', 'cancelled', 'pending' ), true ) ) {
			return $current;
		}
		$settings = pkc_json( $project->fields );
		$on       = (array) ( $settings['on'] ?? array() );
		if ( in_array( 'photo', $on, true ) && ! $photo ) {
			return 'draft';
		}
		foreach ( self::required_keys( $project ) as $k ) {
			$v = in_array( $k, array( 'name', 'id_no' ), true ) ? ( $row_cols[ $k ] ?? '' ) : ( $data[ $k ] ?? '' );
			if ( '' === trim( (string) $v ) ) {
				return 'draft';
			}
		}
		return 'ready';
	}

	/**
	 * Create or update one person.
	 *
	 * @return object|WP_Error Member row.
	 */
	public static function save( $org, $project, $in, $id = 0, $source = 'manual' ) {
		global $wpdb;
		$existing = $id ? self::get( $org->id, $id ) : null;
		if ( $id && ( ! $existing || (int) $existing->project_id !== (int) $project->id ) ) {
			return new WP_Error( 'pkc_not_found', __( 'Person not found.', 'pikacart' ), array( 'status' => 404 ) );
		}
		$clean = self::clean( $in, $project, 'selffill' === $source && ! $id );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		list( $cols, $data ) = $clean;
		$settings = pkc_json( $project->fields );
		// Empty card dates take the project's common "valid from / valid upto" date.
		foreach ( array( 'valid_from', 'valid_until' ) as $dk ) {
			if ( empty( $cols[ $dk ] ) && ! ( $existing && $existing->$dk ) && ! empty( $settings[ $dk ] ) ) {
				$d = self::parse_date( $settings[ $dk ] );
				if ( $d ) {
					$cols[ $dk ] = $d;
					$data[ $dk ] = self::format_date( $d );
				}
			}
		}
		$session  = isset( $in['session'] ) ? mb_substr( sanitize_text_field( (string) $in['session'] ), 0, 40 ) : ( $existing ? $existing->session : (string) ( $settings['session'] ?? '' ) );
		if ( '' !== $cols['id_no'] && self::id_taken( $org->id, $cols['id_no'], $session, $existing ? (int) $existing->id : 0 ) ) {
			return new WP_Error(
				'pkc_duplicate',
				/* translators: %s: ID number */
				sprintf( __( 'ID number %s is already used by another person in this session.', 'pikacart' ), $cols['id_no'] ),
				array(
					'status' => 409,
					'field'  => 'id_no',
				)
			);
		}
		if ( ! $existing ) {
			$limit = (int) pkc_setting( 'members_per_project', 5000 );
			$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'members' ) . ' WHERE project_id = %d AND deleted_at IS NULL', $project->id ) );
			if ( $limit && $count >= $limit ) {
				/* translators: %d: limit */
				return new WP_Error( 'pkc_limit', sprintf( __( 'A project can hold up to %d people. Please start a new project.', 'pikacart' ), $limit ), array( 'status' => 400 ) );
			}
		}

		$merged = $existing ? array_merge( pkc_json( $existing->data ), $data ) : $data;
		$photo  = $existing ? $existing->photo : '';
		$status = isset( $in['status'] ) && in_array( $in['status'], self::STATUSES, true ) ? $in['status'] : ( $existing ? $existing->status : ( 'selffill' === $source ? 'pending' : '' ) );
		$status = self::auto_status( $cols, $merged, $photo, $project, $status );
		$row    = array_merge(
			$cols,
			array(
				'data'       => wp_json_encode( $merged ),
				'session'    => $session,
				'status'     => $status,
				'updated_at' => pkc_now(),
			)
		);
		if ( $existing ) {
			$wpdb->update( pkc_table( 'members' ), $row, array( 'id' => $existing->id, 'org_id' => $org->id ) );
			return self::get( $org->id, $existing->id );
		}
		$row['org_id']       = $org->id;
		$row['project_id']   = $project->id;
		$row['verify_token'] = pkc_token( 32 );
		$row['source']       = $source;
		$row['created_at']   = pkc_now();
		$wpdb->insert( pkc_table( 'members' ), $row );
		return self::get( $org->id, (int) $wpdb->insert_id );
	}

	/** Recalculate draft/ready after a photo change. */
	public static function refresh_status( $org, $member ) {
		global $wpdb;
		$project = PKC_Projects::get( $org->id, $member->project_id );
		if ( ! $project ) {
			return $member;
		}
		$status = self::auto_status( array( 'name' => $member->name, 'id_no' => $member->id_no ), pkc_json( $member->data ), $member->photo, $project, $member->status );
		if ( $status !== $member->status ) {
			$wpdb->update( pkc_table( 'members' ), array( 'status' => $status ), array( 'id' => $member->id ) );
		}
		return self::get( $org->id, $member->id );
	}

	public static function set_photo( $org, $member, $url ) {
		global $wpdb;
		if ( $member->photo && $member->photo !== $url && ! self::photo_shared( $org->id, $member->photo, $member->id ) ) {
			PKC_Uploads::delete_url( $org, $member->photo );
		}
		$wpdb->update( pkc_table( 'members' ), array( 'photo' => esc_url_raw( $url ), 'updated_at' => pkc_now() ), array( 'id' => $member->id, 'org_id' => $org->id ) );
		return self::refresh_status( $org, self::get( $org->id, $member->id ) );
	}

	/** Photos are reused when a session is promoted; do not delete a shared file. */
	private static function photo_shared( $org_id, $url, $exclude ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND photo = %s AND id <> %d LIMIT 1', $org_id, $url, $exclude ) );
	}

	/**
	 * List with search, filters, sorting and pagination.
	 *
	 * @return array [ rows, total, facets ]
	 */
	public static function query( $org_id, $project_id, $args ) {
		global $wpdb;
		$t     = pkc_table( 'members' );
		$where = array( 'org_id = %d', 'project_id = %d' );
		$vals  = array( $org_id, $project_id );
		$where[] = ! empty( $args['trash'] ) ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';

		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(name LIKE %s OR id_no LIKE %s OR data LIKE %s)';
			array_push( $vals, $like, $like, $like );
		}
		if ( ! empty( $args['status'] ) ) {
			if ( 'expired' === $args['status'] ) {
				$where[] = "(status = 'expired' OR (valid_until IS NOT NULL AND valid_until < %s AND status <> 'cancelled'))";
				$vals[]  = gmdate( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			} else {
				$where[] = 'status = %s';
				$vals[]  = $args['status'];
			}
		}
		if ( ! empty( $args['session'] ) ) {
			$where[] = 'session = %s';
			$vals[]  = $args['session'];
		}
		foreach ( array( 'class', 'section', 'department' ) as $f ) {
			if ( ! empty( $args[ $f ] ) ) {
				$where[] = 'data LIKE %s';
				$vals[]  = '%' . $wpdb->esc_like( '"' . $f . '":' . wp_json_encode( (string) $args[ $f ] ) ) . '%';
			}
		}
		$sort_map = array(
			'name'    => 'name',
			'id_no'   => 'id_no',
			'status'  => 'status',
			'updated' => 'updated_at',
			'created' => 'id',
		);
		$sort  = $sort_map[ $args['sort'] ?? 'created' ] ?? 'id';
		$order = ( isset( $args['order'] ) && 'desc' === strtolower( $args['order'] ) ) ? 'DESC' : 'ASC';
		$per   = max( 1, min( 500, (int) ( $args['per_page'] ?? 25 ) ) );
		$page  = max( 1, (int) ( $args['page'] ?? 1 ) );
		$w     = implode( ' AND ', $where );

		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE $w", $vals ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $w ORDER BY $sort $order, id ASC LIMIT %d OFFSET %d", array_merge( $vals, array( $per, ( $page - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array( $rows, $total );
	}

	/** Distinct values for the filter dropdowns. */
	public static function facets( $org_id, $project_id ) {
		global $wpdb;
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT data, session FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND project_id = %d AND deleted_at IS NULL LIMIT 5000', $org_id, $project_id ) );
		$out    = array(
			'class'      => array(),
			'section'    => array(),
			'department' => array(),
			'session'    => array(),
		);
		foreach ( $rows as $r ) {
			$d = pkc_json( $r->data );
			foreach ( array( 'class', 'section', 'department' ) as $k ) {
				if ( ! empty( $d[ $k ] ) ) {
					$out[ $k ][ $d[ $k ] ] = true;
				}
			}
			if ( '' !== (string) $r->session ) {
				$out['session'][ $r->session ] = true;
			}
		}
		foreach ( $out as $k => $v ) {
			$keys = array_keys( $v );
			natcasesort( $keys );
			$out[ $k ] = array_values( $keys );
		}
		return $out;
	}

	public static function display_status( $m ) {
		if ( 'cancelled' !== $m->status && 'pending' !== $m->status && $m->valid_until && $m->valid_until < gmdate( 'Y-m-d', current_time( 'timestamp' ) ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			return 'expired';
		}
		return $m->status;
	}

	public static function to_app( $m ) {
		$data = pkc_json( $m->data );
		return array(
			'id'           => (int) $m->id,
			'project_id'   => (int) $m->project_id,
			'name'         => $m->name,
			'id_no'        => $m->id_no,
			'photo'        => $m->photo,
			'data'         => $data,
			'status'       => self::display_status( $m ),
			'session'      => $m->session,
			'valid_from'   => self::format_date( $m->valid_from ),
			'valid_until'  => self::format_date( $m->valid_until ),
			'verify_token' => $m->verify_token,
			'source'       => $m->source,
			'deleted'      => $m->deleted_at ? human_time_diff( pkc_ts( $m->deleted_at ), time() ) : '',
		);
	}

	/* ---------- Bulk actions ---------- */

	private static function ids_in( $ids ) {
		$ids = array_slice( array_filter( array_map( 'absint', (array) $ids ) ), 0, 1000 );
		return $ids ? implode( ',', $ids ) : '0';
	}

	public static function bulk_delete( $org_id, $project_id, $ids ) {
		global $wpdb;
		return (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . pkc_table( 'members' ) . ' SET deleted_at = %s WHERE org_id = %d AND project_id = %d AND deleted_at IS NULL AND id IN (' . self::ids_in( $ids ) . ')', pkc_now(), $org_id, $project_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	public static function bulk_restore( $org_id, $project_id, $ids ) {
		global $wpdb;
		$count = 0;
		foreach ( array_filter( array_map( 'absint', (array) $ids ) ) as $id ) {
			$m = self::get( $org_id, $id, true );
			if ( ! $m || (int) $m->project_id !== (int) $project_id || ! $m->deleted_at ) {
				continue;
			}
			if ( self::id_taken( $org_id, $m->id_no, $m->session, $m->id ) ) {
				continue; // Someone else now uses this ID number.
			}
			$wpdb->update( pkc_table( 'members' ), array( 'deleted_at' => null ), array( 'id' => $id ) );
			++$count;
		}
		return $count;
	}

	public static function bulk_status( $org_id, $project_id, $ids, $status ) {
		global $wpdb;
		if ( ! in_array( $status, array( 'draft', 'ready', 'printed', 'cancelled' ), true ) ) {
			return 0;
		}
		return (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . pkc_table( 'members' ) . ' SET status = %s, updated_at = %s WHERE org_id = %d AND project_id = %d AND deleted_at IS NULL AND id IN (' . self::ids_in( $ids ) . ')', $status, pkc_now(), $org_id, $project_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	public static function duplicate( $org, $member ) {
		global $wpdb;
		$row = (array) $member;
		unset( $row['id'] );
		$base  = $member->id_no . '-copy';
		$id_no = $base;
		$i     = 1;
		while ( self::id_taken( $org->id, $id_no, $member->session ) ) {
			$id_no = $base . ( ++$i );
		}
		$row['id_no']        = $id_no;
		$row['verify_token'] = pkc_token( 32 );
		$row['status']       = 'draft';
		$row['created_at']   = pkc_now();
		$row['updated_at']   = pkc_now();
		$wpdb->insert( pkc_table( 'members' ), $row );
		return self::get( $org->id, (int) $wpdb->insert_id );
	}

	/** Empty the recycle bin after 30 days (called by cron). */
	public static function purge_trash() {
		global $wpdb;
		$old = $wpdb->get_results( $wpdb->prepare( 'SELECT id, org_id, photo FROM ' . pkc_table( 'members' ) . ' WHERE deleted_at IS NOT NULL AND deleted_at < %s LIMIT 200', pkc_now( -30 * DAY_IN_SECONDS ) ) );
		foreach ( $old as $m ) {
			$org = PKC_Organisations::get( $m->org_id );
			if ( $org && $m->photo && ! self::photo_shared( $m->org_id, $m->photo, $m->id ) ) {
				PKC_Uploads::delete_url( $org, $m->photo );
			}
			$wpdb->delete( pkc_table( 'members' ), array( 'id' => $m->id ) );
		}
	}

	/**
	 * Next class for promotion: numbers and Roman numerals go up by one,
	 * "Class 5" -> "Class 6", "Nursery" -> "LKG" -> "UKG" -> "1".
	 */
	public static function next_class( $class ) {
		$c     = trim( (string) $class );
		$named = array(
			'nursery' => 'LKG',
			'lkg'     => 'UKG',
			'ukg'     => '1',
		);
		if ( isset( $named[ strtolower( $c ) ] ) ) {
			return $named[ strtolower( $c ) ];
		}
		$romans = array( 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII' );
		if ( preg_match( '/^(.*?)(\d+)(\D*)$/', $c, $m ) ) {
			return $m[1] . ( (int) $m[2] + 1 ) . $m[3];
		}
		if ( preg_match( '/^(.*?\b)?(' . implode( '|', array_reverse( $romans ) ) . ')$/i', $c, $m ) ) {
			$idx = array_search( strtoupper( $m[2] ), $romans, true );
			if ( false !== $idx && isset( $romans[ $idx + 1 ] ) ) {
				return ( $m[1] ?? '' ) . $romans[ $idx + 1 ];
			}
			return $c; // Final class stays the same.
		}
		return $c;
	}
}
