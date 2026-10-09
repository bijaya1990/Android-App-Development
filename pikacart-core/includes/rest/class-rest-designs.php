<?php
/**
 * Own designs, Design on Demand, and the Super Admin template manager API.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_REST_Designs {

	public static function routes() {
		$org = array(
			array( 'designs', 'GET', 'list_designs' ),
			array( 'designs', 'POST', 'create_design' ),
			array( 'designs/request', 'POST', 'create_request' ),
			array( 'designs/(?P<id>\d+)', 'GET', 'get_design' ),
			array( 'designs/(?P<id>\d+)', 'POST', 'update_design' ),
			array( 'designs/(?P<id>\d+)/delete', 'POST', 'delete_design' ),
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

		$admin = array(
			array( 'admin/templates', 'GET', 'admin_list' ),
			array( 'admin/templates', 'POST', 'admin_create' ),
			array( 'admin/templates/(?P<id>\d+)', 'GET', 'admin_get' ),
			array( 'admin/templates/(?P<id>\d+)', 'POST', 'admin_update' ),
			array( 'admin/templates/(?P<id>\d+)/duplicate', 'POST', 'admin_duplicate' ),
			array( 'admin/templates/(?P<id>\d+)/delete', 'POST', 'admin_delete' ),
			array( 'admin/requests/(?P<id>\d+)/publish', 'POST', 'admin_publish' ),
			array( 'admin/uploads/image', 'POST', 'admin_upload' ),
		);
		foreach ( $admin as $r ) {
			register_rest_route(
				PKC_REST::NS,
				'/' . $r[0],
				array(
					'methods'             => $r[1],
					'callback'            => array( __CLASS__, $r[2] ),
					'permission_callback' => array( __CLASS__, 'can_admin' ),
				)
			);
		}
	}

	public static function can_admin() {
		return current_user_can( PKC_Roles::ADMIN_CAP ) ? true : new WP_Error( 'pkc_forbidden', __( 'You are not allowed to do this.', 'pikacart' ), array( 'status' => 403 ) );
	}

	private static function not_found() {
		return new WP_Error( 'pkc_not_found', __( 'Design not found.', 'pikacart' ), array( 'status' => 404 ) );
	}

	private static function params( WP_REST_Request $r ) {
		$json = $r->get_json_params();
		return is_array( $json ) && $json ? $json : $r->get_params();
	}

	/* ---------- Organisation ---------- */

	public static function list_designs() {
		$org = PKC_Organisations::current();
		return PKC_REST::ok(
			array(
				'templates' => PKC_Catalog::org_templates( $org->id ),
				'requests'  => array_map( array( 'PKC_Designs', 'request_to_app' ), PKC_Designs::requests_for( $org->id ) ),
				'hours'     => PKC_Designs::hours(),
			)
		);
	}

	public static function create_design( WP_REST_Request $r ) {
		$org  = PKC_REST::org();
		$lock = PKC_Access::require_work( $org );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}
		$tpl = PKC_Designs::create_own( $org, self::params( $r ) );
		return is_wp_error( $tpl ) ? $tpl : PKC_REST::ok( array( 'template' => PKC_Catalog::template_to_app( $tpl ) ) );
	}

	public static function get_design( WP_REST_Request $r ) {
		$org = PKC_Organisations::current();
		$tpl = PKC_Designs::get_own( $org, absint( $r['id'] ) );
		if ( ! $tpl || 'published' !== $tpl->status ) {
			return self::not_found();
		}
		return PKC_REST::ok( array( 'template' => PKC_Catalog::template_to_app( $tpl ) ) );
	}

	public static function update_design( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$tpl = PKC_Designs::get_own( $org, absint( $r['id'] ) );
		if ( ! $tpl || 'published' !== $tpl->status ) {
			return self::not_found();
		}
		$res = PKC_Designs::update_own( $org, $tpl, self::params( $r ) );
		return is_wp_error( $res ) ? $res : PKC_REST::ok( array( 'template' => PKC_Catalog::template_to_app( $res ) ) );
	}

	public static function delete_design( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		$tpl = PKC_Designs::get_own( $org, absint( $r['id'] ) );
		if ( ! $tpl ) {
			return self::not_found();
		}
		$res = PKC_Designs::delete_own( $org, $tpl );
		return is_wp_error( $res ) ? $res : PKC_REST::ok( array( 'message' => __( 'Design deleted.', 'pikacart' ) ) );
	}

	public static function create_request( WP_REST_Request $r ) {
		$org = PKC_REST::org();
		if ( ! PKC_Rate_Limit::hit( 'dod', (string) $org->id, 10, DAY_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$req = PKC_Designs::create_request( $org, self::params( $r ) );
		if ( is_wp_error( $req ) ) {
			return $req;
		}
		return PKC_REST::ok(
			array(
				'request' => PKC_Designs::request_to_app( $req ),
				/* translators: %d: hours */
				'message' => sprintf( _n( 'Thank you! Your design will be live in your account within %d hour.', 'Thank you! Your design will be live in your account within %d hours.', PKC_Designs::hours(), 'pikacart' ), PKC_Designs::hours() ),
			)
		);
	}

	/* ---------- Super Admin: template manager ---------- */

	private static function admin_row( $t ) {
		$app             = PKC_Catalog::template_to_app( $t );
		$app['slug']     = $t->slug;
		$app['owner']    = (int) $t->owner_org_id;
		$app['sort']     = (int) $t->sort_order;
		$app['updated']  = pkc_date( $t->updated_at ? $t->updated_at : $t->created_at, 'j M Y' );
		$app['used']     = PKC_Designs::usage( $t->id );
		$subtype         = PKC_Catalog::subtype( $t->subtype_id );
		$category        = $subtype ? PKC_Catalog::category( $subtype->category_id ) : null;
		$app['subtype']  = $subtype ? array( 'id' => (int) $subtype->id, 'slug' => $subtype->slug, 'name' => $subtype->name ) : null;
		$app['category'] = $category ? array( 'id' => (int) $category->id, 'slug' => $category->slug, 'name' => $category->name ) : null;
		if ( $t->owner_org_id ) {
			$org          = PKC_Organisations::get( $t->owner_org_id );
			$app['org']   = $org ? $org->name : '';
		}
		return $app;
	}

	public static function admin_list( WP_REST_Request $r ) {
		global $wpdb;
		$where = array( 'owner_org_id = 0' );
		$args  = array();
		$map   = array(
			'category' => 'category_id = %d',
			'subtype'  => 'subtype_id = %d',
		);
		foreach ( $map as $k => $sql ) {
			if ( absint( $r->get_param( $k ) ) ) {
				$where[] = $sql;
				$args[]  = absint( $r->get_param( $k ) );
			}
		}
		$level = sanitize_key( (string) $r->get_param( 'level' ) );
		if ( $level ) {
			$where[] = 'style_level = %s';
			$args[]  = $level;
		}
		$status = sanitize_key( (string) $r->get_param( 'status' ) );
		if ( in_array( $status, array( 'published', 'draft' ), true ) ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		$source = sanitize_key( (string) $r->get_param( 'source' ) );
		if ( $source ) {
			$where[] = 'builtin' === $source ? "source = 'builtin'" : "source <> 'builtin'";
		}
		if ( $r->get_param( 'featured' ) ) {
			$where[] = 'is_featured = 1';
		}
		$q = sanitize_text_field( (string) $r->get_param( 'q' ) );
		if ( '' !== $q ) {
			$where[] = 'name LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $q ) . '%';
		}
		$per   = 36;
		$page  = max( 1, absint( $r->get_param( 'page' ) ) );
		$sql_w = implode( ' AND ', $where );
		$t     = pkc_table( 'templates' );
		$count = $args ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE $sql_w", $args ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE $sql_w" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t WHERE $sql_w ORDER BY subtype_id ASC, sort_order ASC, id ASC LIMIT %d OFFSET %d", array_merge( $args, array( $per, ( $page - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return PKC_REST::ok(
			array(
				'templates' => array_map( array( __CLASS__, 'admin_row' ), $rows ),
				'total'     => (int) $count,
				'pages'     => (int) ceil( $count / $per ),
				'page'      => $page,
			)
		);
	}

	public static function admin_get( WP_REST_Request $r ) {
		$t = PKC_Catalog::template( absint( $r['id'] ) );
		return $t ? PKC_REST::ok( array( 'template' => self::admin_row( $t ) ) ) : self::not_found();
	}

	/**
	 * Apply admin fields to a row array.
	 */
	private static function admin_fields( $in, $old = null ) {
		$data = array();
		if ( isset( $in['name'] ) ) {
			$name = mb_substr( sanitize_text_field( (string) $in['name'] ), 0, 120 );
			if ( '' !== $name ) {
				$data['name'] = $name;
			}
		}
		if ( isset( $in['subtype_id'] ) ) {
			$st = PKC_Catalog::subtype( absint( $in['subtype_id'] ) );
			if ( ! $st ) {
				return new WP_Error( 'pkc_invalid', __( 'Please choose a sub-type.', 'pikacart' ), array( 'status' => 400 ) );
			}
			$data['subtype_id']  = (int) $st->id;
			$data['category_id'] = (int) $st->category_id;
		}
		if ( isset( $in['level'] ) && array_key_exists( $in['level'], PKC_Catalog::level_labels() ) ) {
			$data['style_level'] = $in['level'];
		}
		if ( isset( $in['status'] ) ) {
			$data['status'] = 'published' === $in['status'] ? 'published' : 'draft';
		}
		if ( isset( $in['featured'] ) ) {
			$data['is_featured'] = $in['featured'] ? 1 : 0;
		}
		if ( isset( $in['sort'] ) ) {
			$data['sort_order'] = (int) $in['sort'];
		}
		if ( isset( $in['palettes'] ) && is_array( $in['palettes'] ) ) {
			$data['palettes'] = wp_json_encode( array_values( array_filter( array_map( 'absint', $in['palettes'] ) ) ) );
		}
		if ( isset( $in['layout'] ) && is_array( $in['layout'] ) ) {
			$json = wp_json_encode( PKC_Designs::clean_layout( $in['layout'], $old ? pkc_json( $old->layout ) : array() ) );
			if ( strlen( $json ) > PKC_Projects::MAX_DESIGN_BYTES * 2 ) {
				return new WP_Error( 'pkc_invalid', __( 'This design is too large to save. Please remove some elements.', 'pikacart' ), array( 'status' => 400 ) );
			}
			$data['layout'] = $json;
			// A built-in recipe that was edited becomes a custom layout.
			if ( $old && 'builtin' === $old->source && ( isset( $in['layout']['portrait'] ) || isset( $in['layout']['landscape'] ) ) ) {
				$data['source'] = 'custom';
			}
		}
		return $data;
	}

	/**
	 * Create a template: blank, or imported from an exported JSON file.
	 */
	public static function admin_create( WP_REST_Request $r ) {
		global $wpdb;
		$in = self::params( $r );
		if ( isset( $in['import'] ) && is_array( $in['import'] ) ) {
			$imp = $in['import'];
			$st  = null;
			if ( ! empty( $imp['subtype']['slug'] ) ) {
				$st = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'subtypes' ) . ' WHERE slug = %s', sanitize_key( $imp['subtype']['slug'] ) ) );
			}
			$in = array(
				'name'       => isset( $imp['name'] ) ? $imp['name'] : __( 'Imported design', 'pikacart' ),
				'subtype_id' => $st ? $st->id : ( isset( $in['subtype_id'] ) ? $in['subtype_id'] : 0 ),
				'level'      => isset( $imp['level'] ) ? $imp['level'] : 'modern',
				'layout'     => isset( $imp['layout'] ) && is_array( $imp['layout'] ) ? $imp['layout'] : array(),
				'palettes'   => isset( $imp['palettes'] ) ? $imp['palettes'] : array(),
				'status'     => 'draft',
			);
			if ( empty( $in['layout']['portrait'] ) && empty( $in['layout']['landscape'] ) && empty( $in['layout']['recipe'] ) ) {
				return new WP_Error( 'pkc_invalid', __( 'This file is not a Pikacart template.', 'pikacart' ), array( 'status' => 400 ) );
			}
		}
		$in = array_merge(
			array(
				'name'     => __( 'New design', 'pikacart' ),
				'level'    => 'modern',
				'status'   => 'draft',
				'layout'   => array(
					'portrait'  => array(
						'front' => array( 'bg' => '@w', 'els' => array() ),
						'back'  => array( 'bg' => '@w', 'els' => array() ),
					),
					'landscape' => array(
						'front' => array( 'bg' => '@w', 'els' => array() ),
						'back'  => array( 'bg' => '@w', 'els' => array() ),
					),
				),
			),
			$in
		);
		if ( empty( $in['subtype_id'] ) ) {
			$in['subtype_id'] = (int) $wpdb->get_var( 'SELECT id FROM ' . pkc_table( 'subtypes' ) . ' ORDER BY category_id ASC, sort_order ASC, id ASC LIMIT 1' );
		}
		$data = self::admin_fields( $in );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['palettes'] ) || '[]' === $data['palettes'] ) {
			$data['palettes'] = wp_json_encode( array_map( 'intval', $wpdb->get_col( 'SELECT id FROM ' . pkc_table( 'palettes' ) . ' WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 6' ) ) );
		}
		$now = pkc_now();
		$wpdb->insert(
			pkc_table( 'templates' ),
			array_merge(
				$data,
				array(
					'owner_org_id' => 0,
					'slug'         => sanitize_title( $data['name'] ) . '-' . strtolower( pkc_token( 6 ) ),
					'source'       => 'custom',
					'sort_order'   => 1000,
					'created_at'   => $now,
					'updated_at'   => $now,
				)
			)
		);
		$t = PKC_Catalog::template( (int) $wpdb->insert_id );
		PKC_Activity_Log::add( 'template.created', 0, $t->name );
		return PKC_REST::ok( array( 'template' => self::admin_row( $t ) ) );
	}

	public static function admin_update( WP_REST_Request $r ) {
		global $wpdb;
		$t = PKC_Catalog::template( absint( $r['id'] ) );
		if ( ! $t ) {
			return self::not_found();
		}
		$data = self::admin_fields( self::params( $r ), $t );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( $data ) {
			$data['updated_at'] = pkc_now();
			$wpdb->update( pkc_table( 'templates' ), $data, array( 'id' => $t->id ) );
			$changes = array_diff( array_keys( $data ), array( 'updated_at', 'layout' ) );
			if ( $changes || isset( $data['layout'] ) ) {
				PKC_Activity_Log::add( 'template.updated', (int) $t->owner_org_id, $t->name . ( $changes ? ' (' . implode( ', ', $changes ) . ')' : '' ) );
			}
		}
		return PKC_REST::ok( array( 'template' => self::admin_row( PKC_Catalog::template( $t->id ) ) ) );
	}

	public static function admin_duplicate( WP_REST_Request $r ) {
		global $wpdb;
		$t = PKC_Catalog::template( absint( $r['id'] ) );
		if ( ! $t ) {
			return self::not_found();
		}
		$row = (array) $t;
		unset( $row['id'] );
		/* translators: %s: design name */
		$row['name']         = mb_substr( sprintf( __( '%s (copy)', 'pikacart' ), $t->name ), 0, 120 );
		$row['slug']         = sanitize_title( $row['name'] ) . '-' . strtolower( pkc_token( 6 ) );
		$row['owner_org_id'] = 0;
		$row['status']       = 'draft';
		$row['is_featured']  = 0;
		$row['source']       = 'custom';
		$row['created_at']   = pkc_now();
		$row['updated_at']   = pkc_now();
		$wpdb->insert( pkc_table( 'templates' ), $row );
		$copy = PKC_Catalog::template( (int) $wpdb->insert_id );
		PKC_Activity_Log::add( 'template.duplicated', 0, $t->name );
		return PKC_REST::ok( array( 'template' => self::admin_row( $copy ) ) );
	}

	public static function admin_delete( WP_REST_Request $r ) {
		global $wpdb;
		$t = PKC_Catalog::template( absint( $r['id'] ) );
		if ( ! $t ) {
			return self::not_found();
		}
		$used = PKC_Designs::usage( $t->id );
		if ( $used ) {
			return new WP_Error(
				'pkc_in_use',
				/* translators: %d: number of projects */
				sprintf( _n( 'This design is used by %d project, so it cannot be deleted. Unpublish it instead.', 'This design is used by %d projects, so it cannot be deleted. Unpublish it instead.', $used, 'pikacart' ), $used ),
				array( 'status' => 409 )
			);
		}
		$wpdb->delete( pkc_table( 'templates' ), array( 'id' => $t->id ) );
		PKC_Activity_Log::add( 'template.deleted', (int) $t->owner_org_id, $t->name );
		return PKC_REST::ok( array( 'message' => __( 'Design deleted.', 'pikacart' ) ) );
	}

	/** Publish a Design on Demand request from the builder. */
	public static function admin_publish( WP_REST_Request $r ) {
		$req = PKC_Designs::request( absint( $r['id'] ) );
		if ( ! $req ) {
			return self::not_found();
		}
		$res = PKC_Designs::publish( $req, (bool) $r->get_param( 'public' ) );
		return is_wp_error( $res ) ? $res : PKC_REST::ok(
			array_merge(
				$res,
				array( 'message' => __( 'Published. The customer has been notified by email.', 'pikacart' ) )
			)
		);
	}

	/** Images used in Super Admin designs live in a shared library folder. */
	public static function admin_upload( WP_REST_Request $r ) {
		$files   = $r->get_file_params();
		$library = (object) array(
			'id'         => 0,
			'upload_dir' => 'library',
		);
		$saved   = PKC_Uploads::save_image( $files['file'] ?? null, $library, 'tpl' );
		return is_wp_error( $saved ) ? $saved : PKC_REST::ok( array( 'url' => $saved['url'] ) );
	}
}
