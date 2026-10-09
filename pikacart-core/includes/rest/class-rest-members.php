<?php
/**
 * Members (people on a card project), Excel import, photos, self-fill links,
 * and the public self-fill form and card report endpoints.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Members {

	public static function routes() {
		$org = array(
			array( 'projects/(?P<id>\d+)/members', 'GET', 'list' ),
			array( 'projects/(?P<id>\d+)/members', 'POST', 'create' ),
			array( 'projects/(?P<id>\d+)/members/fetch', 'POST', 'fetch' ),
			array( 'projects/(?P<id>\d+)/members/bulk', 'POST', 'bulk' ),
			array( 'projects/(?P<id>\d+)/members/import', 'POST', 'import' ),
			array( 'projects/(?P<id>\d+)/selffill', 'GET', 'selffill_get' ),
			array( 'projects/(?P<id>\d+)/selffill', 'POST', 'selffill_save' ),
			array( 'members/(?P<mid>\d+)', 'POST', 'update' ),
			array( 'members/(?P<mid>\d+)/photo', 'POST', 'photo' ),
			array( 'members/(?P<mid>\d+)/duplicate', 'POST', 'duplicate' ),
		);
		foreach ( $org as $r ) {
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
		register_rest_route(
			PKC_REST::NS,
			'/public/fill/(?P<token>[A-Za-z0-9]{16,64})',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'fill_info' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'fill_submit' ),
					'permission_callback' => '__return_true',
				),
			)
		);
		register_rest_route(
			PKC_REST::NS,
			'/public/report',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'report' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	private static function project( $r ) {
		$org = PKC_REST::org();
		$p   = PKC_Projects::get( $org->id, absint( $r['id'] ) );
		return array( $org, $p );
	}

	private static function nf( $what = 'project' ) {
		return new WP_Error( 'pkc_not_found', 'project' === $what ? __( 'Project not found.', 'pikacart' ) : __( 'Person not found.', 'pikacart' ), array( 'status' => 404 ) );
	}

	public static function list( WP_REST_Request $r ) {
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		$args = array();
		foreach ( array( 'search', 'status', 'class', 'section', 'department', 'session', 'sort', 'order', 'page', 'per_page', 'trash' ) as $k ) {
			$v = $r->get_param( $k );
			if ( null !== $v && '' !== $v ) {
				$args[ $k ] = sanitize_text_field( (string) $v );
			}
		}
		list( $rows, $total ) = PKC_Members::query( $org->id, $p->id, $args );
		return PKC_REST::ok(
			array(
				'members' => array_map( array( 'PKC_Members', 'to_app' ), $rows ),
				'total'   => $total,
				'facets'  => $r->get_param( 'facets' ) ? PKC_Members::facets( $org->id, $p->id ) : null,
				'counts'  => self::counts( $org->id, $p->id ),
			)
		);
	}

	private static function counts( $org_id, $project_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS n FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND project_id = %d AND deleted_at IS NULL GROUP BY status', $org_id, $project_id ) );
		$out  = array_fill_keys( PKC_Members::STATUSES, 0 );
		foreach ( $rows as $row ) {
			$out[ $row->status ] = (int) $row->n;
		}
		$out['trash'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND project_id = %d AND deleted_at IS NOT NULL', $org_id, $project_id ) );
		return $out;
	}

	/** People by ID (for downloads of selected rows). */
	public static function fetch( WP_REST_Request $r ) {
		global $wpdb;
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		$ids = array_slice( array_filter( array_map( 'absint', (array) $r->get_param( 'ids' ) ) ), 0, 1000 );
		if ( ! $ids ) {
			return PKC_REST::ok( array( 'members' => array() ) );
		}
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'members' ) . ' WHERE org_id = %d AND project_id = %d AND deleted_at IS NULL AND id IN (' . implode( ',', $ids ) . ') ORDER BY id ASC', $org->id, $p->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return PKC_REST::ok( array( 'members' => array_map( array( 'PKC_Members', 'to_app' ), $rows ) ) );
	}

	public static function create( WP_REST_Request $r ) {
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		$lock = PKC_Access::require_work( $org );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		$m = PKC_Members::save( $org, $p, (array) $r->get_param( 'person' ) );
		return is_wp_error( $m ) ? $m : PKC_REST::ok(
			array(
				'member'  => PKC_Members::to_app( $m ),
				'message' => __( 'Person added.', 'pikacart' ),
			)
		);
	}

	public static function update( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$m   = PKC_Members::get( $org->id, absint( $r['mid'] ) );
		if ( ! $m ) {
			return self::nf( 'member' );
		}
		$p   = PKC_Projects::get( $org->id, $m->project_id );
		$res = PKC_Members::save( $org, $p, (array) $r->get_param( 'person' ), $m->id );
		return is_wp_error( $res ) ? $res : PKC_REST::ok(
			array(
				'member'  => PKC_Members::to_app( $res ),
				'message' => __( 'Saved.', 'pikacart' ),
			)
		);
	}

	public static function photo( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$m   = PKC_Members::get( $org->id, absint( $r['mid'] ) );
		if ( ! $m ) {
			return self::nf( 'member' );
		}
		if ( ! PKC_Rate_Limit::hit( 'photo', (string) $org->id, 2000, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$files = $r->get_file_params();
		$saved = PKC_Uploads::save_image( $files['file'] ?? null, $org, 'photo' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		$m = PKC_Members::set_photo( $org, $m, $saved['url'] );
		return PKC_REST::ok( array( 'member' => PKC_Members::to_app( $m ) ) );
	}

	public static function duplicate( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$m   = PKC_Members::get( $org->id, absint( $r['mid'] ) );
		if ( ! $m ) {
			return self::nf( 'member' );
		}
		return PKC_REST::ok(
			array(
				'member'  => PKC_Members::to_app( PKC_Members::duplicate( $org, $m ) ),
				'message' => __( 'Copy created. Please give it a new ID number.', 'pikacart' ),
			)
		);
	}

	public static function bulk( WP_REST_Request $r ) {
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		$ids    = (array) $r->get_param( 'ids' );
		$action = sanitize_key( (string) $r->get_param( 'action' ) );
		$n      = 0;
		switch ( $action ) {
			case 'delete':
				$n   = PKC_Members::bulk_delete( $org->id, $p->id, $ids );
				/* translators: %d: number of people */
				$msg = sprintf( _n( '%d person moved to the recycle bin.', '%d people moved to the recycle bin.', $n, 'pikacart' ), $n );
				break;
			case 'restore':
				$n   = PKC_Members::bulk_restore( $org->id, $p->id, $ids );
				/* translators: %d: number of people */
				$msg = sprintf( _n( '%d person restored.', '%d people restored.', $n, 'pikacart' ), $n );
				break;
			case 'approve':
				foreach ( array_map( 'absint', $ids ) as $id ) {
					$m = PKC_Members::get( $org->id, $id );
					if ( $m && (int) $m->project_id === (int) $p->id && 'pending' === $m->status ) {
						global $wpdb;
						$wpdb->update( pkc_table( 'members' ), array( 'status' => 'draft' ), array( 'id' => $m->id ) );
						PKC_Members::refresh_status( $org, PKC_Members::get( $org->id, $id ) );
						++$n;
					}
				}
				/* translators: %d: number of people */
				$msg = sprintf( _n( '%d entry approved.', '%d entries approved.', $n, 'pikacart' ), $n );
				break;
			case 'status':
				$n   = PKC_Members::bulk_status( $org->id, $p->id, $ids, sanitize_key( (string) $r->get_param( 'status' ) ) );
				/* translators: %d: number of people */
				$msg = sprintf( _n( '%d person updated.', '%d people updated.', $n, 'pikacart' ), $n );
				break;
			default:
				return new WP_Error( 'pkc_invalid', __( 'Unknown action.', 'pikacart' ), array( 'status' => 400 ) );
		}
		return PKC_REST::ok(
			array(
				'count'   => $n,
				'message' => $msg,
			)
		);
	}

	/**
	 * Import a batch of rows (the browser reads the Excel file and maps columns).
	 * Each row reports success or a plain-language reason. Duplicate ID numbers are
	 * flagged, and only updated when the owner explicitly chose "update".
	 */
	public static function import( WP_REST_Request $r ) {
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		$lock = PKC_Access::require_work( $org );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		$rows   = array_slice( (array) $r->get_param( 'rows' ), 0, 200 );
		$mode   = 'update' === $r->get_param( 'mode' ) ? 'update' : 'skip';
		$start  = absint( $r->get_param( 'start' ) );
		$out    = array();
		$settings = pkc_json( $p->fields );
		foreach ( $rows as $i => $row ) {
			$rownum = $start + $i;
			$row    = is_array( $row ) ? $row : array();
			$id_no  = trim( (string) ( $row['id_no'] ?? '' ) );
			$exists = '' !== $id_no ? PKC_Members::id_taken( $org->id, $id_no, (string) ( $row['session'] ?? $settings['session'] ?? '' ) ) : 0;
			if ( $exists ) {
				$existing = PKC_Members::get( $org->id, $exists );
				if ( 'update' !== $mode || ! $existing || (int) $existing->project_id !== (int) $p->id ) {
					$out[] = array(
						'row'       => $rownum,
						'ok'        => false,
						'duplicate' => true,
						/* translators: %s: ID number */
						'error'     => sprintf( __( 'ID number %s already exists.', 'pikacart' ), $id_no ),
					);
					continue;
				}
				$m = PKC_Members::save( $org, $p, $row, $existing->id, 'import' );
			} else {
				$m = PKC_Members::save( $org, $p, $row, 0, 'import' );
			}
			if ( is_wp_error( $m ) ) {
				$out[] = array(
					'row'   => $rownum,
					'ok'    => false,
					'error' => $m->get_error_message(),
				);
			} else {
				$out[] = array(
					'row'   => $rownum,
					'ok'    => true,
					'id'    => (int) $m->id,
					'id_no' => $m->id_no,
				);
			}
		}
		return PKC_REST::ok( array( 'results' => $out ) );
	}

	/* ---------- Self-fill link (organisation side) ---------- */

	private static function link_for( $org_id, $project_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'selffill_links' ) . ' WHERE org_id = %d AND project_id = %d ORDER BY id DESC LIMIT 1', $org_id, $project_id ) );
	}

	private static function link_to_app( $l ) {
		if ( ! $l ) {
			return null;
		}
		return array(
			'url'     => home_url( '/fill/' . $l->token . '/' ),
			'active'  => (bool) $l->is_active,
			'expires' => $l->expires_at ? pkc_date( $l->expires_at, 'Y-m-d' ) : '',
		);
	}

	public static function selffill_get( WP_REST_Request $r ) {
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		return PKC_REST::ok( array( 'link' => self::link_to_app( self::link_for( $org->id, $p->id ) ) ) );
	}

	public static function selffill_save( WP_REST_Request $r ) {
		global $wpdb;
		list( $org, $p ) = self::project( $r );
		if ( ! $p ) {
			return self::nf();
		}
		$link    = self::link_for( $org->id, $p->id );
		$expires = null;
		$date    = (string) $r->get_param( 'expires' );
		if ( '' !== $date ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $date . ' 23:59:59', wp_timezone() );
			if ( ! $dt ) {
				return new WP_Error( 'pkc_invalid', __( 'Please choose a valid expiry date.', 'pikacart' ), array( 'status' => 400 ) );
			}
			$expires = $dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		}
		$data = array(
			'is_active'  => $r->get_param( 'active' ) ? 1 : 0,
			'expires_at' => $expires,
		);
		if ( $r->get_param( 'regenerate' ) || ! $link ) {
			if ( $link ) {
				$wpdb->update( pkc_table( 'selffill_links' ), array( 'is_active' => 0 ), array( 'id' => $link->id ) );
			}
			$wpdb->insert(
				pkc_table( 'selffill_links' ),
				array_merge(
					$data,
					array(
						'org_id'     => $org->id,
						'project_id' => $p->id,
						'token'      => pkc_token( 24 ),
						'is_active'  => 1,
						'created_at' => pkc_now(),
					)
				)
			);
		} else {
			$wpdb->update( pkc_table( 'selffill_links' ), $data, array( 'id' => $link->id ) );
		}
		return PKC_REST::ok(
			array(
				'link'    => self::link_to_app( self::link_for( $org->id, $p->id ) ),
				'message' => __( 'Self-fill link saved.', 'pikacart' ),
			)
		);
	}

	/* ---------- Public self-fill form ---------- */

	/** @return array|WP_Error [ link, org, project ] */
	public static function open_link( $token ) {
		global $wpdb;
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'selffill_links' ) . ' WHERE token = %s', $token ) );
		if ( ! $link || ! $link->is_active || ( $link->expires_at && pkc_ts( $link->expires_at ) < time() ) ) {
			return new WP_Error( 'pkc_closed', __( 'This form is closed. Please contact your institution.', 'pikacart' ), array( 'status' => 410 ) );
		}
		$org = PKC_Access::refresh( PKC_Organisations::get( $link->org_id ) );
		$p   = $org ? PKC_Projects::get( $org->id, $link->project_id ) : null;
		if ( ! $org || ! $p || 'suspended' === $org->status ) {
			return new WP_Error( 'pkc_closed', __( 'This form is closed. Please contact your institution.', 'pikacart' ), array( 'status' => 410 ) );
		}
		return array( $link, $org, $p );
	}

	/** Fields the public form asks for (switched-on fields of the project). */
	public static function form_fields( $p ) {
		$settings = pkc_json( $p->fields );
		$on       = (array) ( $settings['on'] ?? array() );
		$subtype  = PKC_Catalog::subtype( $p->subtype_id );
		$fields   = array();
		foreach ( pkc_json( $subtype ? $subtype->fields : '[]' ) as $f ) {
			if ( in_array( $f['key'], $on, true ) && ! in_array( $f['key'], array( 'photo', 'name', 'id_no', 'valid_from', 'valid_until' ), true ) ) {
				$fields[] = array(
					'key'      => $f['key'],
					'label'    => $f['label'],
					'required' => ! empty( $f['required'] ),
				);
			}
		}
		foreach ( (array) ( $settings['custom'] ?? array() ) as $c ) {
			if ( in_array( $c['key'], $on, true ) ) {
				$fields[] = array(
					'key'      => $c['key'],
					'label'    => $c['label'],
					'required' => false,
				);
			}
		}
		return $fields;
	}

	public static function fill_info( WP_REST_Request $r ) {
		$res = self::open_link( (string) $r['token'] );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		list( , $org, $p ) = $res;
		return PKC_REST::ok(
			array(
				'org'    => array(
					'name' => $org->name,
					'logo' => $org->logo,
				),
				'fields' => self::form_fields( $p ),
			)
		);
	}

	public static function fill_submit( WP_REST_Request $r ) {
		if ( ! PKC_Rate_Limit::hit( 'selffill', pkc_ip(), 20, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$res = self::open_link( (string) $r['token'] );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		list( , $org, $p ) = $res;
		if ( '' !== (string) $r->get_param( 'website' ) ) {
			return PKC_REST::ok( array( 'message' => __( 'Thank you! Your details were sent.', 'pikacart' ) ) );
		}
		if ( ! $r->get_param( 'consent' ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please agree to the consent notice.', 'pikacart' ), array( 'status' => 400, 'field' => 'consent' ) );
		}
		$person = array();
		foreach ( self::form_fields( $p ) as $f ) {
			$v = (string) $r->get_param( $f['key'] );
			if ( $f['required'] && '' === trim( $v ) ) {
				/* translators: %s: field label */
				return new WP_Error( 'pkc_invalid', sprintf( __( '%s is required.', 'pikacart' ), $f['label'] ), array( 'status' => 400, 'field' => $f['key'] ) );
			}
			$person[ $f['key'] ] = $v;
		}
		$person['name']  = (string) $r->get_param( 'name' );
		$person['id_no'] = (string) $r->get_param( 'id_no' );
		$files           = $r->get_file_params();
		if ( empty( $files['photo'] ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please add your photo.', 'pikacart' ), array( 'status' => 400, 'field' => 'photo' ) );
		}
		$m = PKC_Members::save( $org, $p, $person, 0, 'selffill' );
		if ( is_wp_error( $m ) ) {
			return $m;
		}
		$saved = PKC_Uploads::save_image( $files['photo'], $org, 'photo' );
		if ( is_wp_error( $saved ) ) {
			global $wpdb;
			$wpdb->delete( pkc_table( 'members' ), array( 'id' => $m->id ) );
			return $saved;
		}
		PKC_Members::set_photo( $org, $m, $saved['url'] );
		PKC_Notifications::add(
			$org->id,
			'account',
			__( 'New self-fill entry', 'pikacart' ),
			/* translators: 1: person name, 2: project */
			sprintf( __( '%1$s submitted details for %2$s. Approve it in Members.', 'pikacart' ), $m->name, $p->name ),
			'members/' . $p->id
		);
		return PKC_REST::ok( array( 'message' => __( 'Thank you! Your details were sent to your institution for approval.', 'pikacart' ) ) );
	}

	/** "Report this card" from the public verification page. */
	public static function report( WP_REST_Request $r ) {
		global $wpdb;
		if ( ! PKC_Rate_Limit::hit( 'report', pkc_ip(), 5, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$m      = PKC_Members::by_token( (string) $r->get_param( 'token' ) );
		$reason = mb_substr( sanitize_textarea_field( (string) $r->get_param( 'reason' ) ), 0, 1000 );
		if ( ! $m || mb_strlen( $reason ) < 5 ) {
			return new WP_Error( 'pkc_invalid', __( 'Please describe the problem.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$wpdb->insert(
			pkc_table( 'reports' ),
			array(
				'org_id'         => $m->org_id,
				'member_id'      => $m->id,
				'reason'         => $reason,
				'reporter_email' => sanitize_email( (string) $r->get_param( 'email' ) ),
				'status'         => 'open',
				'created_at'     => pkc_now(),
			)
		);
		wp_mail( pkc_support_email(), __( 'New card report on Pikacart', 'pikacart' ), $reason . "\n\n" . admin_url( 'admin.php?page=pikacart-reports' ) );
		return PKC_REST::ok( array( 'message' => __( 'Thank you. Our team will review this card.', 'pikacart' ) ) );
	}
}
