<?php
/**
 * Catalogue: categories, sub-types, card sizes, colour palettes and templates.
 *
 * Built-in designs are stored as small recipes ({"recipe":{"f":3,"v":1}}) that
 * the browser expands into the full layout (assets/js/card/families.js).
 * Designs made in the template builder are stored as full layout JSON.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Catalog {

	/** Family names and style levels (must match families.js order). */
	const FAMILIES = array(
		array( 'Classic Band', 'simple' ),
		array( 'Angled', 'modern' ),
		array( 'Wave', 'modern' ),
		array( 'Side Stripe', 'professional' ),
		array( 'Corporate Split', 'professional' ),
		array( 'Royal Arch', 'premium' ),
		array( 'Geometric', 'modern' ),
		array( 'Minimal Line', 'simple' ),
		array( 'Midnight', 'premium' ),
		array( 'Gradient Panel', 'premium' ),
		array( 'Ribbon Corner', 'professional' ),
		array( 'Halo Circle', 'modern' ),
		array( 'Bottom Block', 'simple' ),
		array( 'Dotted Header', 'modern' ),
		array( 'Heritage Frame', 'professional' ),
		array( 'Crest', 'premium' ),
		array( 'Tech Edge', 'modern' ),
	);

	const LEVELS = array( 'simple', 'modern', 'professional', 'premium' );

	public static function level_labels() {
		return array(
			'simple'       => __( 'Simple', 'pikacart' ),
			'modern'       => __( 'Modern', 'pikacart' ),
			'professional' => __( 'Professional', 'pikacart' ),
			'premium'      => __( 'Premium', 'pikacart' ),
		);
	}

	/* ---------- Seeding ---------- */

	/**
	 * Create 50 built-in designs for every sub-type that has none yet.
	 */
	public static function seed_templates() {
		global $wpdb;
		$table    = pkc_table( 'templates' );
		$palettes = $wpdb->get_col( 'SELECT id FROM ' . pkc_table( 'palettes' ) . ' ORDER BY sort_order ASC, id ASC' );
		if ( ! $palettes ) {
			return;
		}
		$subtypes = $wpdb->get_results( 'SELECT id, category_id FROM ' . pkc_table( 'subtypes' ) . ' ORDER BY id ASC' );
		$recipes  = self::builtin_recipes();
		$now      = pkc_now();
		$n        = count( $palettes );

		foreach ( $subtypes as $si => $sub ) {
			$has = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE subtype_id = %d AND source = 'builtin'", $sub->id ) );
			if ( $has ) {
				continue;
			}
			$values = array();
			foreach ( $recipes as $i => $r ) {
				$fam   = self::FAMILIES[ $r['f'] ];
				$start = ( $r['f'] * 5 + $r['v'] * 3 + $si ) % $n;
				$pals  = array();
				for ( $k = 0; $k < 6; $k++ ) {
					$pals[] = (int) $palettes[ ( $start + $k * 5 ) % $n ];
				}
				$pals     = array_values( array_unique( $pals ) );
				$name     = $fam[0] . ' ' . array( 'I', 'II', 'III' )[ $r['v'] ];
				$featured = ( 'premium' === $fam[1] || 'professional' === $fam[1] ) && 0 === $r['v'] ? 1 : 0;
				$values[] = $wpdb->prepare(
					'(0, %d, %d, %s, %s, %s, %s, %s, %d, %s, %s, %d, %s, %s)',
					$sub->category_id,
					$sub->id,
					$name,
					sanitize_title( $name ),
					$fam[1],
					wp_json_encode( array( 'recipe' => $r ) ),
					wp_json_encode( $pals ),
					$featured,
					'published',
					'builtin',
					$i + 1,
					$now,
					$now
				);
			}
			// One multi-row insert per sub-type keeps activation fast on shared hosting.
			$wpdb->query( "INSERT INTO $table (owner_org_id, category_id, subtype_id, name, slug, style_level, layout, palettes, is_featured, status, source, sort_order, created_at, updated_at) VALUES " . implode( ',', $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
	}

	/** 17 families x 3 variants; the first 50 are used. */
	public static function builtin_recipes() {
		$out = array();
		for ( $v = 0; $v < 3; $v++ ) {
			foreach ( array_keys( self::FAMILIES ) as $f ) {
				$out[] = array(
					'f' => $f,
					'v' => $v,
				);
			}
		}
		return array_slice( $out, 0, 50 );
	}

	/* ---------- Reading ---------- */

	public static function sizes( $active_only = true ) {
		global $wpdb;
		$where = $active_only ? 'WHERE is_active = 1' : '';
		return array_map(
			function ( $s ) {
				return array(
					'id'     => (int) $s->id,
					'name'   => $s->name,
					'w'      => (float) $s->width_mm,
					'h'      => (float) $s->height_mm,
					'note'   => $s->note,
					'active' => (bool) $s->is_active,
				);
			},
			$wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'sizes' ) . " $where ORDER BY sort_order ASC, id ASC" )
		);
	}

	public static function palettes( $active_only = true ) {
		global $wpdb;
		$where = $active_only ? 'WHERE is_active = 1' : '';
		return array_map(
			function ( $p ) {
				return array(
					'id'     => (int) $p->id,
					'name'   => $p->name,
					'p'      => $p->primary_c,
					's'      => $p->secondary_c,
					'a'      => $p->accent_c,
					't'      => $p->text_c,
					'active' => (bool) $p->is_active,
				);
			},
			$wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'palettes' ) . " $where ORDER BY sort_order ASC, id ASC" )
		);
	}

	public static function subtype( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'subtypes' ) . ' WHERE id = %d', $id ) );
	}

	public static function category( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'categories' ) . ' WHERE id = %d', $id ) );
	}

	/**
	 * Categories with sub-types (and their default fields).
	 */
	public static function tree( $include_hidden = false ) {
		global $wpdb;
		$hidden = $include_hidden ? '' : 'WHERE is_hidden = 0';
		$cats   = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'categories' ) . " $hidden ORDER BY sort_order ASC, id ASC" );
		$subs   = $wpdb->get_results( 'SELECT * FROM ' . pkc_table( 'subtypes' ) . " $hidden ORDER BY sort_order ASC, id ASC" );
		$out    = array();
		foreach ( $cats as $c ) {
			$item = array(
				'id'          => (int) $c->id,
				'slug'        => $c->slug,
				'name'        => $c->name,
				'description' => (string) $c->description,
				'icon'        => $c->icon,
				'hidden'      => (bool) $c->is_hidden,
				'subtypes'    => array(),
			);
			foreach ( $subs as $s ) {
				if ( (int) $s->category_id === (int) $c->id ) {
					$item['subtypes'][] = self::subtype_to_app( $s );
				}
			}
			$out[] = $item;
		}
		return $out;
	}

	public static function subtype_to_app( $s ) {
		return array(
			'id'          => (int) $s->id,
			'category_id' => (int) $s->category_id,
			'slug'        => $s->slug,
			'name'        => $s->name,
			'fields'      => pkc_json( $s->fields ),
			'terms'       => (string) $s->terms,
			'hidden'      => (bool) $s->is_hidden,
		);
	}

	public static function template( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'templates' ) . ' WHERE id = %d', $id ) );
	}

	/** Can this organisation use this template? (public, or its own private design) */
	public static function template_allowed( $tpl, $org_id ) {
		if ( ! $tpl ) {
			return false;
		}
		if ( (int) $tpl->owner_org_id ) {
			return (int) $tpl->owner_org_id === (int) $org_id && 'published' === $tpl->status;
		}
		return 'published' === $tpl->status;
	}

	public static function template_to_app( $t ) {
		return array(
			'id'          => (int) $t->id,
			'name'        => $t->name,
			'level'       => $t->style_level,
			'subtype_id'  => (int) $t->subtype_id,
			'category_id' => (int) $t->category_id,
			'layout'      => pkc_json( $t->layout ),
			'palettes'    => array_map( 'intval', pkc_json( $t->palettes ) ),
			'featured'    => (bool) $t->is_featured,
			'own'         => (int) $t->owner_org_id > 0,
			'source'      => $t->source,
			'status'      => $t->status,
		);
	}

	/**
	 * Published public templates for a sub-type (plus the org's private designs).
	 */
	public static function templates_for( $subtype_id, $org_id = 0 ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . pkc_table( 'templates' ) . " WHERE ( subtype_id = %d AND owner_org_id = 0 AND status = 'published' ) OR ( owner_org_id = %d AND owner_org_id > 0 AND status = 'published' ) ORDER BY owner_org_id DESC, is_featured DESC, sort_order ASC, id ASC",
				$subtype_id,
				$org_id ? $org_id : -1
			)
		);
		return array_map( array( __CLASS__, 'template_to_app' ), $rows );
	}

	public static function org_templates( $org_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . pkc_table( 'templates' ) . " WHERE owner_org_id = %d AND status = 'published' ORDER BY id DESC", $org_id ) );
		return array_map( array( __CLASS__, 'template_to_app' ), $rows );
	}

	/**
	 * Featured designs across categories (homepage showcase).
	 */
	public static function showcase( $per_category = 8 ) {
		global $wpdb;
		$out = array();
		foreach ( self::tree() as $cat ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM ' . pkc_table( 'templates' ) . " WHERE category_id = %d AND owner_org_id = 0 AND status = 'published' ORDER BY is_featured DESC, CASE style_level WHEN 'premium' THEN 0 WHEN 'professional' THEN 1 WHEN 'modern' THEN 2 ELSE 3 END, sort_order ASC LIMIT %d",
					$cat['id'],
					$per_category
				)
			);
			if ( $rows ) {
				$out[] = array(
					'category'  => array(
						'id'   => $cat['id'],
						'slug' => $cat['slug'],
						'name' => $cat['name'],
					),
					'subtypes'  => $cat['subtypes'],
					'templates' => array_map( array( __CLASS__, 'template_to_app' ), $rows ),
				);
			}
		}
		return $out;
	}
}
