<?php
/**
 * Card endpoints: catalogue, templates, projects, own designs.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Cards {

	public static function routes() {
		$org = array(
			array( 'catalog', 'GET', 'catalog' ),
			array( 'templates', 'GET', 'templates' ),
			array( 'projects', 'GET', 'projects' ),
			array( 'projects', 'POST', 'create' ),
			array( 'projects/(?P<id>\d+)', 'GET', 'project' ),
			array( 'projects/(?P<id>\d+)', 'POST', 'update' ),
			array( 'projects/(?P<id>\d+)/duplicate', 'POST', 'duplicate' ),
			array( 'projects/(?P<id>\d+)/delete', 'POST', 'delete' ),
			array( 'projects/(?P<id>\d+)/new-session', 'POST', 'new_session' ),
			array( 'uploads/image', 'POST', 'upload_image' ),
			array( 'downloads/count', 'POST', 'count_download' ),
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
		// Public, read-only: galleries on the website.
		foreach ( array( 'public/catalog' => 'public_catalog', 'public/templates' => 'public_templates', 'public/showcase' => 'public_showcase' ) as $route => $cb ) {
			register_rest_route(
				PKC_REST::NS,
				'/' . $route,
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, $cb ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	private static function not_found() {
		return new WP_Error( 'pkc_not_found', __( 'Project not found.', 'pikacart' ), array( 'status' => 404 ) );
	}

	/** Everything the designer needs: categories, sub-types, sizes, palettes. */
	public static function catalog() {
		return PKC_REST::ok(
			array(
				'tree'     => PKC_Catalog::tree(),
				'sizes'    => PKC_Catalog::sizes(),
				'palettes' => PKC_Catalog::palettes(),
				'levels'   => array_merge( PKC_Catalog::level_labels(), array( 'own' => __( 'Your design', 'pikacart' ) ) ),
			)
		);
	}

	public static function templates( WP_REST_Request $r ) {
		$org = PKC_Organisations::current();
		if ( $r->get_param( 'own' ) ) {
			return PKC_REST::ok( array( 'templates' => PKC_Catalog::org_templates( $org->id ) ) );
		}
		return PKC_REST::ok( array( 'templates' => PKC_Catalog::templates_for( absint( $r->get_param( 'subtype' ) ), $org->id ) ) );
	}

	public static function projects() {
		$org       = PKC_Organisations::current();
		$templates = array();
		$subtypes  = array();
		$out       = array();
		foreach ( PKC_Projects::list_for( $org->id ) as $p ) {
			$row = PKC_Projects::to_app( $p );
			if ( ! isset( $templates[ $p->template_id ] ) ) {
				$t                            = PKC_Catalog::template( $p->template_id );
				$templates[ $p->template_id ] = $t && PKC_Catalog::template_allowed( $t, $org->id ) ? PKC_Catalog::template_to_app( $t ) : null;
			}
			if ( ! isset( $subtypes[ $p->subtype_id ] ) ) {
				$st                          = PKC_Catalog::subtype( $p->subtype_id );
				$subtypes[ $p->subtype_id ] = $st ? PKC_Catalog::subtype_to_app( $st ) : null;
			}
			$row['template'] = $templates[ $p->template_id ];
			$row['subtype']  = $subtypes[ $p->subtype_id ];
			$out[]           = $row;
		}
		return PKC_REST::ok( array( 'projects' => $out ) );
	}

	private static function project_payload( $org, $project ) {
		global $wpdb;
		$tpl     = PKC_Catalog::template( $project->template_id );
		$subtype = PKC_Catalog::subtype( $project->subtype_id );
		$app     = PKC_Projects::to_app( $project );
		$app['people'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'members' ) . ' WHERE project_id = %d AND deleted_at IS NULL', $project->id ) );
		return array(
			'project'  => $app,
			'template' => $tpl && PKC_Catalog::template_allowed( $tpl, $org->id ) ? PKC_Catalog::template_to_app( $tpl ) : null,
			'subtype'  => $subtype ? PKC_Catalog::subtype_to_app( $subtype ) : null,
			'category' => $subtype ? array( 'id' => (int) $subtype->category_id, 'name' => (string) PKC_Catalog::category( $subtype->category_id )->name ) : null,
		);
	}

	public static function create( WP_REST_Request $r ) {
		$org  = PKC_REST::org();
		$lock = PKC_Access::require_work( $org );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		$p = PKC_Projects::create( $org, absint( $r->get_param( 'subtype_id' ) ), absint( $r->get_param( 'template_id' ) ), (string) $r->get_param( 'name' ) );
		if ( is_wp_error( $p ) ) {
			return $p;
		}
		return PKC_REST::ok( self::project_payload( $org, $p ) );
	}

	public static function project( WP_REST_Request $r ) {
		$org = PKC_Organisations::current();
		$p   = PKC_Projects::get( $org->id, absint( $r['id'] ) );
		return $p ? PKC_REST::ok( self::project_payload( $org, $p ) ) : self::not_found();
	}

	public static function update( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$p   = PKC_Projects::get( $org->id, absint( $r['id'] ) );
		if ( ! $p ) {
			return self::not_found();
		}
		$lock = PKC_Access::require_work( $org );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		$res = PKC_Projects::update( $org, $p, $r->get_json_params() ? $r->get_json_params() : $r->get_params() );
		return is_wp_error( $res ) ? $res : PKC_REST::ok( self::project_payload( $org, $res ) );
	}

	public static function duplicate( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$p   = PKC_Projects::get( $org->id, absint( $r['id'] ) );
		if ( ! $p ) {
			return self::not_found();
		}
		return PKC_REST::ok( self::project_payload( $org, PKC_Projects::duplicate( $org, $p ) ) );
	}

	public static function delete( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$p   = PKC_Projects::get( $org->id, absint( $r['id'] ) );
		if ( ! $p ) {
			return self::not_found();
		}
		PKC_Projects::delete( $org, $p );
		return PKC_REST::ok( array( 'message' => __( 'Project deleted. Its people are in the recycle bin for 30 days.', 'pikacart' ) ) );
	}

	/**
	 * New session: copy the project, promote students to the next class, keep photos,
	 * and archive last year's project.
	 */
	public static function new_session( WP_REST_Request $r ) {
		global $wpdb;
		$org = PKC_REST::org();
		$p   = PKC_Projects::get( $org->id, absint( $r['id'] ) );
		if ( ! $p ) {
			return self::not_found();
		}
		$session = mb_substr( sanitize_text_field( (string) $r->get_param( 'session' ) ), 0, 40 );
		if ( '' === $session ) {
			return new WP_Error( 'pkc_invalid', __( 'Please enter the new session, e.g. 2027-28.', 'pikacart' ), array( 'status' => 400, 'field' => 'session' ) );
		}
		$promote  = (bool) $r->get_param( 'promote' );
		$new      = PKC_Projects::duplicate( $org, $p, $p->name . ' · ' . $session );
		$settings = pkc_json( $new->fields );
		$settings['session'] = $session;
		$wpdb->update( pkc_table( 'projects' ), array( 'fields' => wp_json_encode( $settings ) ), array( 'id' => $new->id ) );

		$members = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'members' ) . " WHERE project_id = %d AND org_id = %d AND deleted_at IS NULL AND status <> 'cancelled'", $p->id, $org->id ) );
		$count   = 0;
		foreach ( $members as $m ) {
			$data = pkc_json( $m->data );
			if ( $promote && ! empty( $data['class'] ) ) {
				$data['class'] = PKC_Members::next_class( $data['class'] );
			}
			unset( $data['valid_from'], $data['valid_until'] );
			$wpdb->insert(
				pkc_table( 'members' ),
				array(
					'org_id'       => $org->id,
					'project_id'   => $new->id,
					'id_no'        => $m->id_no,
					'name'         => $m->name,
					'data'         => wp_json_encode( $data ),
					'photo'        => $m->photo, // Photos are kept.
					'status'       => $m->photo ? 'ready' : 'draft',
					'verify_token' => pkc_token( 32 ),
					'session'      => $session,
					'source'       => 'session',
					'created_at'   => pkc_now(),
					'updated_at'   => pkc_now(),
				)
			);
			++$count;
		}
		$wpdb->update( pkc_table( 'projects' ), array( 'status' => 'archived' ), array( 'id' => $p->id ) );
		PKC_Activity_Log::add( 'project.session', $org->id, $p->name . ' → ' . $session );
		return PKC_REST::ok(
			array(
				/* translators: %d: number of people */
				'message' => sprintf( _n( 'New session created with %d person.', 'New session created with %d people.', $count, 'pikacart' ), $count ),
				'project' => PKC_Projects::to_app( PKC_Projects::get( $org->id, $new->id ) ),
			)
		);
	}

	/**
	 * Upload an image used in the editor (custom image, own-design background).
	 */
	public static function upload_image( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		if ( ! PKC_Rate_Limit::hit( 'upload', (string) $org->id, 120, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$files = $r->get_file_params();
		$saved = PKC_Uploads::save_image( $files['file'] ?? null, $org, 'img' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return PKC_REST::ok( array( 'url' => $saved['url'] ) );
	}

	/** Count downloads for the Super Admin dashboard. */
	public static function count_download( WP_REST_Request $r ) {
		$n = max( 1, min( 5000, absint( $r->get_param( 'n' ) ) ) );
		if ( PKC_Organisations::viewing_as() ) {
			return PKC_REST::ok( array( 'watermark' => PKC_Access::needs_watermark( PKC_REST::org() ) ) );
		}
		update_option( 'pkc_download_count', (int) get_option( 'pkc_download_count', 0 ) + $n, false );
		$org = PKC_Organisations::current();
		PKC_Organisations::set_meta( $org->id, 'downloads', (int) PKC_Organisations::meta( $org, 'downloads', 0 ) + $n );
		return PKC_REST::ok( array( 'watermark' => PKC_Access::needs_watermark( PKC_REST::org() ) ) );
	}

	/* ---------- Public ---------- */

	public static function public_catalog() {
		$res = PKC_REST::ok(
			array(
				'tree'     => PKC_Catalog::tree(),
				'palettes' => PKC_Catalog::palettes(),
				'levels'   => PKC_Catalog::level_labels(),
			)
		);
		$res->header( 'Cache-Control', 'public, max-age=600' );
		return $res;
	}

	public static function public_templates( WP_REST_Request $r ) {
		$res = PKC_REST::ok( array( 'templates' => PKC_Catalog::templates_for( absint( $r->get_param( 'subtype' ) ), 0 ) ) );
		$res->header( 'Cache-Control', 'public, max-age=600' );
		return $res;
	}

	public static function public_showcase() {
		$res = PKC_REST::ok( array( 'showcase' => PKC_Catalog::showcase( 10 ) ) );
		$res->header( 'Cache-Control', 'public, max-age=600' );
		return $res;
	}
}
