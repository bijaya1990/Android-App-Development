<?php
/**
 * Own designs and Design on Demand.
 *
 * - "Use my own design": the organisation uploads front and back artwork, places
 *   placeholders in the editor and gets a private template.
 * - "Design on Demand": the organisation sends artwork, the Pikacart team builds
 *   the template and publishes it to that organisation (and, if allowed, to everyone).
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Designs {

	const STATUSES = array( 'new', 'progress', 'live', 'rejected' );

	public static function status_labels() {
		return array(
			'new'      => __( 'New', 'pikacart' ),
			'progress' => __( 'In progress', 'pikacart' ),
			'live'     => __( 'Live', 'pikacart' ),
			'rejected' => __( 'Rejected', 'pikacart' ),
		);
	}

	public static function hours() {
		return max( 1, (int) pkc_setting( 'dod_hours', 6 ) );
	}

	/**
	 * Is this URL an image inside the organisation's own upload folder?
	 */
	public static function own_url( $org, $url ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		$dir = PKC_Uploads::org_dir( $org );
		return 0 === strpos( $url, $dir['url'] . '/' ) && preg_match( '/\.(jpe?g|png|webp)$/i', $url ) ? $url : '';
	}

	private static function size_ok( $size_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'sizes' ) . ' WHERE id = %d AND is_active = 1', $size_id ) );
	}

	private static function first_palettes() {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( 'SELECT id FROM ' . pkc_table( 'palettes' ) . ' WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 6' ) );
	}

	/* ---------- Own designs ---------- */

	/**
	 * Create a private template from uploaded artwork.
	 */
	public static function create_own( $org, $in ) {
		global $wpdb;
		$subtype = PKC_Catalog::subtype( absint( $in['subtype_id'] ?? 0 ) );
		if ( ! $subtype ) {
			return new WP_Error( 'pkc_invalid', __( 'Please choose the card type.', 'pikacart' ), array( 'status' => 400, 'field' => 'subtype_id' ) );
		}
		$size_id = self::size_ok( absint( $in['size_id'] ?? 0 ) );
		if ( ! $size_id ) {
			return new WP_Error( 'pkc_invalid', __( 'Please choose the card size.', 'pikacart' ), array( 'status' => 400, 'field' => 'size_id' ) );
		}
		$front = self::own_url( $org, $in['front'] ?? '' );
		$back  = self::own_url( $org, $in['back'] ?? '' );
		if ( ! $front ) {
			return new WP_Error( 'pkc_invalid', __( 'Please upload the front image.', 'pikacart' ), array( 'status' => 400, 'field' => 'front' ) );
		}
		$orient = 'landscape' === ( $in['orientation'] ?? '' ) ? 'landscape' : 'portrait';
		$name   = mb_substr( sanitize_text_field( (string) ( $in['name'] ?? '' ) ), 0, 120 );
		$name   = '' !== $name ? $name : __( 'My design', 'pikacart' ) . ' · ' . wp_date( 'j M Y' );
		$layout = array(
			'artwork' => array(
				'front'       => $front,
				'back'        => $back,
				'orientation' => $orient,
			),
			'meta'    => array(
				'size_id'     => $size_id,
				'orientation' => $orient,
			),
		);
		$now    = pkc_now();
		$wpdb->insert(
			pkc_table( 'templates' ),
			array(
				'owner_org_id' => $org->id,
				'category_id'  => (int) $subtype->category_id,
				'subtype_id'   => (int) $subtype->id,
				'name'         => $name,
				'slug'         => sanitize_title( $name ) . '-' . strtolower( pkc_token( 6 ) ),
				'style_level'  => 'own',
				'layout'       => wp_json_encode( $layout ),
				'palettes'     => wp_json_encode( self::first_palettes() ),
				'status'       => 'published',
				'source'       => 'own',
				'created_at'   => $now,
				'updated_at'   => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		PKC_Activity_Log::add( 'design.created', $org->id, $name );
		return PKC_Catalog::template( $id );
	}

	/** An organisation's own template (any source), or null. */
	public static function get_own( $org, $id ) {
		$t = PKC_Catalog::template( $id );
		return $t && (int) $t->owner_org_id === (int) $org->id ? $t : null;
	}

	/**
	 * Clean a stored layout: keep artwork and meta, clean each orientation.
	 */
	public static function clean_layout( $layout, $old = array() ) {
		$out = array();
		foreach ( array( 'portrait', 'landscape' ) as $o ) {
			if ( isset( $layout[ $o ] ) && is_array( $layout[ $o ] ) ) {
				$out[ $o ] = PKC_Projects::clean_design( $layout[ $o ] );
			}
		}
		foreach ( array( 'artwork', 'meta', 'recipe' ) as $k ) {
			$src = isset( $layout[ $k ] ) ? $layout[ $k ] : ( 'recipe' !== $k && isset( $old[ $k ] ) ? $old[ $k ] : null );
			if ( is_array( $src ) ) {
				$out[ $k ] = map_deep(
					$src,
					function ( $v ) {
						return is_string( $v ) ? sanitize_text_field( $v ) : ( is_numeric( $v ) ? $v + 0 : $v );
					}
				);
			}
		}
		// A full layout replaces a built-in recipe.
		if ( isset( $out['portrait'] ) || isset( $out['landscape'] ) ) {
			unset( $out['recipe'] );
		}
		return $out;
	}

	public static function update_own( $org, $tpl, $in ) {
		global $wpdb;
		$data = array();
		if ( isset( $in['name'] ) ) {
			$name = mb_substr( sanitize_text_field( (string) $in['name'] ), 0, 120 );
			if ( '' !== $name ) {
				$data['name'] = $name;
			}
		}
		if ( isset( $in['layout'] ) && is_array( $in['layout'] ) ) {
			$json = wp_json_encode( self::clean_layout( $in['layout'], pkc_json( $tpl->layout ) ) );
			if ( strlen( $json ) > PKC_Projects::MAX_DESIGN_BYTES * 2 ) {
				return new WP_Error( 'pkc_invalid', __( 'This design is too large to save. Please remove some elements.', 'pikacart' ), array( 'status' => 400 ) );
			}
			$data['layout'] = $json;
		}
		if ( $data ) {
			$data['updated_at'] = pkc_now();
			$wpdb->update( pkc_table( 'templates' ), $data, array( 'id' => $tpl->id ) );
		}
		return PKC_Catalog::template( $tpl->id );
	}

	/** Number of active projects that use a template. */
	public static function usage( $template_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'projects' ) . ' WHERE template_id = %d AND deleted_at IS NULL', $template_id ) );
	}

	public static function delete_own( $org, $tpl ) {
		global $wpdb;
		$used = self::usage( $tpl->id );
		if ( $used ) {
			return new WP_Error(
				'pkc_in_use',
				/* translators: %d: number of projects */
				sprintf( _n( 'This design is used by %d project. Delete or change that project first.', 'This design is used by %d projects. Delete or change those projects first.', $used, 'pikacart' ), $used ),
				array( 'status' => 409 )
			);
		}
		$wpdb->delete( pkc_table( 'templates' ), array( 'id' => $tpl->id ) );
		PKC_Activity_Log::add( 'design.deleted', $org->id, $tpl->name );
		return true;
	}

	/* ---------- Design on Demand ---------- */

	public static function create_request( $org, $in ) {
		global $wpdb;
		$subtype = PKC_Catalog::subtype( absint( $in['subtype_id'] ?? 0 ) );
		$size_id = self::size_ok( absint( $in['size_id'] ?? 0 ) );
		$front   = self::own_url( $org, $in['front'] ?? '' );
		$back    = self::own_url( $org, $in['back'] ?? '' );
		if ( ! $front ) {
			return new WP_Error( 'pkc_invalid', __( 'Please upload the front of your design.', 'pikacart' ), array( 'status' => 400, 'field' => 'front' ) );
		}
		if ( ! $size_id ) {
			return new WP_Error( 'pkc_invalid', __( 'Please choose the card size.', 'pikacart' ), array( 'status' => 400, 'field' => 'size_id' ) );
		}
		if ( empty( $in['confirm'] ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please confirm that this is your own design and not a copy of a government ID.', 'pikacart' ), array( 'status' => 400, 'field' => 'confirm' ) );
		}
		$open = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . pkc_table( 'design_requests' ) . " WHERE org_id = %d AND status IN ('new','progress')", $org->id ) );
		if ( $open >= 5 ) {
			return new WP_Error( 'pkc_limit', __( 'You already have 5 requests in progress. Please wait until they are ready.', 'pikacart' ), array( 'status' => 429 ) );
		}
		$now = pkc_now();
		$wpdb->insert(
			pkc_table( 'design_requests' ),
			array(
				'org_id'      => $org->id,
				'subtype_id'  => $subtype ? (int) $subtype->id : 0,
				'front'       => $front,
				'back'        => $back,
				'size_id'     => $size_id,
				'orientation' => 'landscape' === ( $in['orientation'] ?? '' ) ? 'landscape' : 'portrait',
				'notes'       => mb_substr( sanitize_textarea_field( (string) ( $in['notes'] ?? '' ) ), 0, 2000 ),
				'public_ok'   => empty( $in['public_ok'] ) ? 0 : 1,
				'status'      => 'new',
				'due_at'      => pkc_now( self::hours() * HOUR_IN_SECONDS ),
				'created_at'  => $now,
				'updated_at'  => $now,
			)
		);
		$id  = (int) $wpdb->insert_id;
		$req = self::request( $id );
		PKC_Activity_Log::add( 'design.requested', $org->id, '#' . $id );

		$to = pkc_setting( 'dod_notify_email' );
		$to = is_email( $to ) ? $to : pkc_support_email();
		PKC_Emails::send(
			$to,
			'design_request',
			array(
				'org'     => $org->name,
				'name'    => $org->name,
				'subtype' => $subtype ? $subtype->name : '—',
				'notes'   => $req->notes ? $req->notes : '—',
				'date'    => pkc_date( $req->due_at, 'j M Y, g:i a' ),
				'link'    => admin_url( 'admin.php?page=pikacart-requests' ),
			)
		);
		return $req;
	}

	public static function request( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'design_requests' ) . ' WHERE id = %d', $id ) );
	}

	public static function requests_for( $org_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'design_requests' ) . ' WHERE org_id = %d ORDER BY id DESC LIMIT 50', $org_id ) );
	}

	public static function request_to_app( $r ) {
		$labels  = self::status_labels();
		$subtype = $r->subtype_id ? PKC_Catalog::subtype( $r->subtype_id ) : null;
		return array(
			'id'          => (int) $r->id,
			'status'      => $r->status,
			'label'       => $labels[ $r->status ] ?? $r->status,
			'front'       => $r->front,
			'back'        => $r->back,
			'orientation' => $r->orientation,
			'size_id'     => (int) $r->size_id,
			'subtype'     => $subtype ? $subtype->name : '',
			'notes'       => (string) $r->notes,
			'reason'      => (string) $r->reject_reason,
			'template_id' => (int) $r->template_id,
			'created'     => pkc_date( $r->created_at, 'j M Y, g:i a' ),
			'due_ts'      => pkc_ts( $r->due_at ),
		);
	}

	/**
	 * Super Admin: make sure the request has a draft template to build in.
	 */
	public static function start_build( $req ) {
		global $wpdb;
		$tpl = $req->template_id ? PKC_Catalog::template( $req->template_id ) : null;
		if ( ! $tpl ) {
			$org     = PKC_Organisations::get( $req->org_id );
			$subtype = PKC_Catalog::subtype( $req->subtype_id );
			if ( ! $subtype ) {
				$subtype = $wpdb->get_row( 'SELECT * FROM ' . pkc_table( 'subtypes' ) . ' ORDER BY sort_order ASC, id ASC LIMIT 1' );
			}
			$name = sprintf(
				/* translators: %s: organisation name */
				__( '%s design', 'pikacart' ),
				$org ? $org->name : __( 'Custom', 'pikacart' )
			);
			$now  = pkc_now();
			$wpdb->insert(
				pkc_table( 'templates' ),
				array(
					'owner_org_id' => (int) $req->org_id,
					'category_id'  => $subtype ? (int) $subtype->category_id : 0,
					'subtype_id'   => $subtype ? (int) $subtype->id : 0,
					'name'         => mb_substr( $name, 0, 120 ),
					'slug'         => 'dod-' . (int) $req->id . '-' . strtolower( pkc_token( 6 ) ),
					'style_level'  => 'own',
					'layout'       => wp_json_encode(
						array(
							'artwork' => array(
								'front'       => $req->front,
								'back'        => $req->back,
								'orientation' => $req->orientation,
							),
							'meta'    => array(
								'size_id'     => (int) $req->size_id,
								'orientation' => $req->orientation,
							),
						)
					),
					'palettes'     => wp_json_encode( self::first_palettes() ),
					'status'       => 'draft',
					'source'       => 'dod',
					'created_at'   => $now,
					'updated_at'   => $now,
				)
			);
			$tpl = PKC_Catalog::template( (int) $wpdb->insert_id );
			$wpdb->update( pkc_table( 'design_requests' ), array( 'template_id' => $tpl->id ), array( 'id' => $req->id ) );
		}
		if ( 'new' === $req->status ) {
			self::set_status( $req, 'progress' );
		}
		return $tpl;
	}

	public static function set_status( $req, $status ) {
		global $wpdb;
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return;
		}
		$wpdb->update(
			pkc_table( 'design_requests' ),
			array(
				'status'     => $status,
				'updated_at' => pkc_now(),
			),
			array( 'id' => $req->id )
		);
	}

	/**
	 * Publish the built template to the organisation (and optionally to everyone).
	 *
	 * @return array|WP_Error [ 'template' => id, 'public' => id|0 ]
	 */
	public static function publish( $req, $to_public = false ) {
		global $wpdb;
		$tpl = $req->template_id ? PKC_Catalog::template( $req->template_id ) : null;
		if ( ! $tpl ) {
			return new WP_Error( 'pkc_invalid', __( 'Open the request in the builder and place the placeholders first.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$layout = pkc_json( $tpl->layout );
		if ( empty( $layout['portrait'] ) && empty( $layout['landscape'] ) ) {
			return new WP_Error( 'pkc_invalid', __( 'Please place the placeholders in the builder and save before publishing.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$now = pkc_now();
		$wpdb->update(
			pkc_table( 'templates' ),
			array(
				'status'     => 'published',
				'updated_at' => $now,
			),
			array( 'id' => $tpl->id )
		);
		$public_id = 0;
		if ( $to_public ) {
			$row = (array) $tpl;
			unset( $row['id'] );
			$row['owner_org_id'] = 0;
			$row['status']       = 'published';
			$row['style_level']  = 'premium';
			$row['slug']         = 'public-' . $tpl->slug;
			$row['created_at']   = $now;
			$row['updated_at']   = $now;
			$wpdb->insert( pkc_table( 'templates' ), $row );
			$public_id = (int) $wpdb->insert_id;
		}
		self::set_status( $req, 'live' );
		$org = PKC_Organisations::get( $req->org_id );
		if ( $org ) {
			PKC_Notifications::add( $org->id, 'design', __( 'Your design is live', 'pikacart' ), __( 'The design you sent is ready under My Designs. Start making cards with it now.', 'pikacart' ), 'designs' );
			PKC_Emails::send_to_org( $org, 'design_live', array( 'link' => pkc_url( 'app', 'designs' ) ) );
		}
		PKC_Activity_Log::add( 'design.published', (int) $req->org_id, '#' . $req->id . ( $public_id ? ' + public #' . $public_id : '' ) );
		return array(
			'template' => (int) $tpl->id,
			'public'   => $public_id,
		);
	}

	public static function reject( $req, $reason ) {
		global $wpdb;
		$reason = mb_substr( sanitize_textarea_field( (string) $reason ), 0, 1000 );
		if ( '' === $reason ) {
			return new WP_Error( 'pkc_invalid', __( 'Please write the reason.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$wpdb->update(
			pkc_table( 'design_requests' ),
			array(
				'status'        => 'rejected',
				'reject_reason' => $reason,
				'updated_at'    => pkc_now(),
			),
			array( 'id' => $req->id )
		);
		// Remove an unfinished draft template.
		if ( $req->template_id ) {
			$tpl = PKC_Catalog::template( $req->template_id );
			if ( $tpl && 'draft' === $tpl->status && ! self::usage( $tpl->id ) ) {
				$wpdb->delete( pkc_table( 'templates' ), array( 'id' => $tpl->id ) );
			}
		}
		$org = PKC_Organisations::get( $req->org_id );
		if ( $org ) {
			PKC_Notifications::add( $org->id, 'design', __( 'Design request not accepted', 'pikacart' ), $reason, 'designs' );
			PKC_Emails::send_to_org(
				$org,
				'design_rejected',
				array(
					'reason' => $reason,
					'link'   => pkc_url( 'app', 'designs' ),
				)
			);
		}
		PKC_Activity_Log::add( 'design.rejected', (int) $req->org_id, '#' . $req->id . ': ' . $reason );
		return true;
	}

	public static function pending_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'design_requests' ) . " WHERE status IN ('new','progress')" );
	}
}
