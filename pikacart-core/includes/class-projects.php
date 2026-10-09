<?php
/**
 * Card projects: one design + settings + a list of people.
 * Every query is filtered by the organisation ID.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Projects {

	const MAX_DESIGN_BYTES = 400000;

	public static function get( $org_id, $id, $with_deleted = false ) {
		global $wpdb;
		$extra = $with_deleted ? '' : ' AND deleted_at IS NULL';
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'projects' ) . " WHERE id = %d AND org_id = %d$extra", $id, $org_id ) );
	}

	public static function list_for( $org_id ) {
		global $wpdb;
		$p = pkc_table( 'projects' );
		$m = pkc_table( 'members' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pr.*, (SELECT COUNT(*) FROM $m mm WHERE mm.project_id = pr.id AND mm.deleted_at IS NULL) AS people FROM $p pr WHERE pr.org_id = %d AND pr.deleted_at IS NULL ORDER BY pr.updated_at DESC LIMIT 200",
				$org_id
			)
		);
	}

	/** Default field settings for a sub-type. */
	public static function default_settings( $subtype, $org ) {
		$fields = pkc_json( $subtype ? $subtype->fields : '[]' );
		$on     = array();
		foreach ( $fields as $f ) {
			if ( ! empty( $f['on'] ) ) {
				$on[] = $f['key'];
			}
		}
		return array(
			'on'         => $on,
			'custom'     => array(),
			'flags'      => array(
				'qr_front'  => true,
				'qr_back'   => true,
				'bar_front' => true,
				'bar_back'  => true,
				'renewal'   => true,
			),
			'card_title' => '',
			'terms'      => '',
			'session'    => $org && $org->session_year ? $org->session_year : '',
			'bleed'      => false,
		);
	}

	public static function create( $org, $subtype_id, $template_id, $name = '' ) {
		global $wpdb;
		$subtype = PKC_Catalog::subtype( $subtype_id );
		if ( ! $subtype ) {
			return new WP_Error( 'pkc_invalid', __( 'Please choose a card type.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$tpl = PKC_Catalog::template( $template_id );
		if ( ! PKC_Catalog::template_allowed( $tpl, $org->id ) ) {
			return new WP_Error( 'pkc_invalid', __( 'This design is not available.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$size   = $wpdb->get_var( 'SELECT id FROM ' . pkc_table( 'sizes' ) . ' WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1' );
		$pals   = array_map( 'intval', pkc_json( $tpl->palettes ) );
		$name   = $name ? mb_substr( sanitize_text_field( $name ), 0, 120 ) : $subtype->name . ' · ' . wp_date( 'j M Y' );
		$layout = pkc_json( $tpl->layout );
		$orient = ( ! isset( $layout['recipe'] ) && empty( $layout['portrait'] ) && ! empty( $layout['landscape'] ) ) ? 'landscape' : 'portrait';
		if ( ! empty( $layout['meta']['orientation'] ) ) {
			// Own artwork is made for one orientation and size.
			$orient = 'landscape' === $layout['meta']['orientation'] ? 'landscape' : 'portrait';
		}
		if ( ! empty( $layout['meta']['size_id'] ) && $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'sizes' ) . ' WHERE id = %d', $layout['meta']['size_id'] ) ) ) {
			$size = (int) $layout['meta']['size_id'];
		}
		$now    = pkc_now();
		$wpdb->insert(
			pkc_table( 'projects' ),
			array(
				'org_id'      => $org->id,
				'subtype_id'  => $subtype->id,
				'template_id' => $tpl->id,
				'name'        => $name,
				'size_id'     => (int) $size,
				'orientation' => $orient,
				'palette'     => wp_json_encode( array( 'id' => $pals ? $pals[0] : 0 ) ),
				'design'      => '',
				'fields'      => wp_json_encode( self::default_settings( $subtype, $org ) ),
				'step'        => 3,
				'status'      => 'draft',
				'created_at'  => $now,
				'updated_at'  => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		PKC_Activity_Log::add( 'project.created', $org->id, $name );
		return self::get( $org->id, $id );
	}

	/**
	 * Save changes from the app (autosave). Only known keys are accepted.
	 *
	 * @return object|WP_Error
	 */
	public static function update( $org, $project, $in ) {
		global $wpdb;
		$data = array();
		if ( isset( $in['name'] ) ) {
			$name = mb_substr( sanitize_text_field( (string) $in['name'] ), 0, 120 );
			if ( '' !== $name ) {
				$data['name'] = $name;
			}
		}
		if ( isset( $in['template_id'] ) && (int) $in['template_id'] !== (int) $project->template_id ) {
			$tpl = PKC_Catalog::template( absint( $in['template_id'] ) );
			if ( ! PKC_Catalog::template_allowed( $tpl, $org->id ) ) {
				return new WP_Error( 'pkc_invalid', __( 'This design is not available.', 'pikacart' ), array( 'status' => 400 ) );
			}
			$data['template_id'] = (int) $tpl->id;
			$data['design']      = ''; // A new template starts from its original layout.
			if ( ! isset( $in['palette'] ) ) {
				$pals            = array_map( 'intval', pkc_json( $tpl->palettes ) );
				$data['palette'] = wp_json_encode( array( 'id' => $pals ? $pals[0] : 0 ) );
			}
		}
		if ( isset( $in['size_id'] ) ) {
			$data['size_id'] = absint( $in['size_id'] );
		}
		foreach ( array( 'custom_w', 'custom_h' ) as $k ) {
			if ( isset( $in[ $k ] ) ) {
				$v = (float) $in[ $k ];
				if ( $v && ( $v < 20 || $v > 330 ) ) {
					return new WP_Error( 'pkc_invalid', __( 'Custom size must be between 20 and 330 mm.', 'pikacart' ), array( 'status' => 400 ) );
				}
				$data[ $k ] = $v;
			}
		}
		if ( isset( $in['orientation'] ) ) {
			$data['orientation'] = 'landscape' === $in['orientation'] ? 'landscape' : 'portrait';
		}
		if ( isset( $in['palette'] ) && is_array( $in['palette'] ) ) {
			$data['palette'] = wp_json_encode( self::clean_palette( $in['palette'] ) );
		}
		if ( array_key_exists( 'design', $in ) ) {
			if ( empty( $in['design'] ) ) {
				$data['design'] = '';
			} else {
				$json = wp_json_encode( self::clean_design( $in['design'] ) );
				if ( strlen( $json ) > self::MAX_DESIGN_BYTES ) {
					return new WP_Error( 'pkc_invalid', __( 'This design is too large to save. Please remove some elements.', 'pikacart' ), array( 'status' => 400 ) );
				}
				$data['design'] = $json;
			}
		}
		if ( isset( $in['fields'] ) && is_array( $in['fields'] ) ) {
			$data['fields'] = wp_json_encode( self::clean_settings( $in['fields'] ) );
		}
		if ( isset( $in['step'] ) ) {
			$data['step'] = max( 1, min( 9, absint( $in['step'] ) ) );
		}
		if ( isset( $in['status'] ) && in_array( $in['status'], array( 'draft', 'ready', 'archived' ), true ) ) {
			$data['status'] = $in['status'];
		}
		if ( ! $data ) {
			return $project;
		}
		$data['updated_at'] = pkc_now();
		$wpdb->update( pkc_table( 'projects' ), $data, array( 'id' => $project->id, 'org_id' => $org->id ) );
		return self::get( $org->id, $project->id );
	}

	private static function clean_color( $v ) {
		$c = sanitize_hex_color( (string) $v );
		return $c ? $c : '';
	}

	public static function clean_palette( $p ) {
		if ( ! empty( $p['id'] ) ) {
			return array( 'id' => absint( $p['id'] ) );
		}
		$out = array();
		foreach ( array( 'p', 's', 'a', 't' ) as $k ) {
			$out[ $k ] = self::clean_color( $p[ $k ] ?? '' );
		}
		return $out;
	}

	/**
	 * Designs are drawn only on a canvas (never as HTML), so text is safe;
	 * we still strip control characters and limit image sources.
	 */
	public static function clean_design( $d ) {
		if ( ! is_array( $d ) ) {
			return array();
		}
		array_walk_recursive(
			$d,
			function ( &$v, $k ) {
				if ( is_string( $v ) ) {
					$v = wp_check_invalid_utf8( $v );
					if ( in_array( $k, array( 'src', 'img' ), true ) && preg_match( '#^\s*(javascript|data):#i', $v ) ) {
						$v = '';
					}
					$v = mb_substr( $v, 0, 2000 );
				}
			}
		);
		return $d;
	}

	public static function clean_settings( $s ) {
		$keys = array();
		foreach ( (array) ( $s['on'] ?? array() ) as $k ) {
			$keys[] = sanitize_key( $k );
		}
		$custom = array();
		$used = array();
		foreach ( array_slice( (array) ( $s['custom'] ?? array() ), 0, 5 ) as $i => $c ) {
			$label = mb_substr( sanitize_text_field( $c['label'] ?? '' ), 0, 40 );
			$key   = isset( $c['key'] ) && preg_match( '/^cf[1-5]$/', $c['key'] ) ? $c['key'] : 'cf' . ( $i + 1 );
			if ( '' !== $label && ! isset( $used[ $key ] ) ) {
				$used[ $key ] = true;
				$custom[]     = array(
					'key'   => $key,
					'label' => $label,
				);
			}
		}
		$flags = array();
		foreach ( array( 'qr_front', 'qr_back', 'bar_front', 'bar_back', 'renewal' ) as $f ) {
			$flags[ $f ] = ! empty( $s['flags'][ $f ] );
		}
		return array(
			'on'         => array_values( array_unique( $keys ) ),
			'custom'     => $custom,
			'flags'      => $flags,
			'card_title' => mb_substr( sanitize_text_field( $s['card_title'] ?? '' ), 0, 60 ),
			'terms'      => mb_substr( sanitize_textarea_field( $s['terms'] ?? '' ), 0, 1200 ),
			'session'    => mb_substr( sanitize_text_field( $s['session'] ?? '' ), 0, 40 ),
			'bleed'      => ! empty( $s['bleed'] ),
		);
	}

	public static function duplicate( $org, $project, $name = '' ) {
		global $wpdb;
		$row = (array) $project;
		unset( $row['id'], $row['people'] );
		$row['name']       = $name ? $name : sprintf( /* translators: %s: project name */ __( '%s (copy)', 'pikacart' ), $project->name );
		$row['created_at'] = pkc_now();
		$row['updated_at'] = pkc_now();
		$row['deleted_at'] = null;
		$wpdb->insert( pkc_table( 'projects' ), $row );
		return self::get( $org->id, (int) $wpdb->insert_id );
	}

	public static function delete( $org, $project ) {
		global $wpdb;
		$wpdb->update( pkc_table( 'projects' ), array( 'deleted_at' => pkc_now() ), array( 'id' => $project->id, 'org_id' => $org->id ) );
		// People go to the recycle bin with the project.
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . pkc_table( 'members' ) . ' SET deleted_at = %s WHERE project_id = %d AND org_id = %d AND deleted_at IS NULL', pkc_now(), $project->id, $org->id ) );
		PKC_Activity_Log::add( 'project.deleted', $org->id, $project->name );
	}

	public static function to_app( $p ) {
		$size = null;
		if ( (int) $p->size_id ) {
			global $wpdb;
			$s = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'sizes' ) . ' WHERE id = %d', $p->size_id ) );
			if ( $s ) {
				$size = array(
					'id' => (int) $s->id,
					'w'  => (float) $s->width_mm,
					'h'  => (float) $s->height_mm,
				);
			}
		}
		return array(
			'id'          => (int) $p->id,
			'name'        => $p->name,
			'subtype_id'  => (int) $p->subtype_id,
			'template_id' => (int) $p->template_id,
			'size_id'     => (int) $p->size_id,
			'size'        => $size,
			'custom_w'    => (float) $p->custom_w,
			'custom_h'    => (float) $p->custom_h,
			'orientation' => $p->orientation,
			'palette'     => pkc_json( $p->palette ),
			'design'      => $p->design ? pkc_json( $p->design ) : null,
			'fields'      => pkc_json( $p->fields ),
			'step'        => (int) $p->step,
			'status'      => $p->status,
			'people'      => isset( $p->people ) ? (int) $p->people : null,
			'updated'     => human_time_diff( pkc_ts( $p->updated_at ), time() ),
			'updated_ts'  => pkc_ts( $p->updated_at ),
		);
	}
}
