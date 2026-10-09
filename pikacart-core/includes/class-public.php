<?php
/**
 * Public design galleries: /id-card/, /id-card/{category}/ and
 * /id-card/{category}/{sub-type}/, plus the homepage showcase data.
 * Pages are indexable (unlike /app/ and /verify/) and appear in the sitemap.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Public {

	/** @var array|null Current gallery context. */
	private static $ctx = null;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 5 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'pre_handle_404', array( __CLASS__, 'pre_404' ), 10, 2 );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 50 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'redirect_canonical', array( __CLASS__, 'no_canonical' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 30 );
		add_action( 'init', array( __CLASS__, 'sitemap' ), 20 );
	}

	public static function rewrite() {
		add_rewrite_rule( '^id-card/?$', 'index.php?pkc_idcard=1', 'top' );
		add_rewrite_rule( '^id-card/([a-z0-9-]+)/?$', 'index.php?pkc_idcard=1&pkc_cat=$matches[1]', 'top' );
		add_rewrite_rule( '^id-card/([a-z0-9-]+)/([a-z0-9-]+)/?$', 'index.php?pkc_idcard=1&pkc_cat=$matches[1]&pkc_sub=$matches[2]', 'top' );
	}

	public static function query_vars( $vars ) {
		return array_merge( $vars, array( 'pkc_idcard', 'pkc_cat', 'pkc_sub' ) );
	}

	public static function url( $cat = '', $sub = '' ) {
		$path = 'id-card/' . ( $cat ? $cat . '/' : '' ) . ( $cat && $sub ? $sub . '/' : '' );
		return home_url( user_trailingslashit( $path ) );
	}

	/**
	 * Work out which gallery is requested. Returns null for other pages and
	 * false for a gallery URL that does not exist.
	 */
	public static function context() {
		if ( null !== self::$ctx ) {
			return self::$ctx;
		}
		if ( ! get_query_var( 'pkc_idcard' ) ) {
			return null;
		}
		$tree     = PKC_Catalog::tree();
		$cat_slug = sanitize_key( (string) get_query_var( 'pkc_cat' ) );
		$sub_slug = sanitize_key( (string) get_query_var( 'pkc_sub' ) );
		$ctx      = array(
			'tree'     => $tree,
			'category' => null,
			'subtype'  => null,
		);
		if ( $cat_slug ) {
			foreach ( $tree as $c ) {
				if ( $c['slug'] === $cat_slug ) {
					$ctx['category'] = $c;
				}
			}
			if ( ! $ctx['category'] || ! $ctx['category']['subtypes'] ) {
				self::$ctx = false;
				return false;
			}
			if ( $sub_slug ) {
				foreach ( $ctx['category']['subtypes'] as $s ) {
					if ( $s['slug'] === $sub_slug ) {
						$ctx['subtype'] = $s;
					}
				}
				if ( ! $ctx['subtype'] ) {
					self::$ctx = false;
					return false;
				}
			} else {
				$ctx['subtype'] = $ctx['category']['subtypes'][0];
			}
			$ctx['templates'] = PKC_Catalog::templates_for( $ctx['subtype']['id'], 0 );
			$ctx['seo']       = self::seo_for( $ctx );
		}
		self::$ctx = $ctx;
		return $ctx;
	}

	/** SEO values for a gallery page (admin-entered values first, then sensible defaults). */
	public static function seo_for( $ctx ) {
		global $wpdb;
		$cat     = $ctx['category'];
		$sub     = $ctx['subtype'];
		$own     = array();
		$is_sub  = $sub && get_query_var( 'pkc_sub' );
		$table   = $is_sub ? 'subtypes' : 'categories';
		$id      = $is_sub ? $sub['id'] : $cat['id'];
		$raw     = $wpdb->get_var( $wpdb->prepare( 'SELECT seo FROM ' . pkc_table( $table ) . ' WHERE id = %d', $id ) );
		$own     = pkc_json( $raw );
		$count   = count( $ctx['templates'] );
		$name    = $is_sub ? $cat['name'] . ' ' . $sub['name'] : $cat['name'];
		$default = array(
			/* translators: 1: category/sub-type name, 2: number of designs */
			'title'       => sprintf( __( '%1$s ID Card Design: %2$d Free Templates', 'pikacart' ), $name, $count ),
			/* translators: 1: category name, 2: number */
			'description' => sprintf( __( 'Browse %2$d professional %1$s ID card designs in portrait and landscape. Add your logo, import from Excel, verify with QR and print at 300 DPI. Free to start.', 'pikacart' ), $name, $count ),
			'keywords'    => strtolower( $name ) . ' id card, ' . strtolower( $name ) . ' id card design, id card maker',
			'image'       => '',
			'text'        => '',
		);
		foreach ( $default as $k => $v ) {
			if ( empty( $own[ $k ] ) ) {
				$own[ $k ] = $v;
			}
		}
		$own['h1']        = sprintf( /* translators: %s: name */ __( '%s ID Card Designs', 'pikacart' ), $name );
		$own['canonical'] = $is_sub ? self::url( $cat['slug'], $sub['slug'] ) : self::url( $cat['slug'] );
		return $own;
	}

	public static function pre_404( $preempt, $query ) {
		if ( $query->is_main_query() && $query->get( 'pkc_idcard' ) ) {
			if ( false === self::context() ) {
				return $preempt;
			}
			status_header( 200 );
			$query->is_404  = false;
			$query->is_home = false;
			return true;
		}
		return $preempt;
	}

	public static function template( $template ) {
		if ( ! get_query_var( 'pkc_idcard' ) ) {
			return $template;
		}
		$ctx = self::context();
		if ( false === $ctx ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			$t = get_query_template( '404' );
			return $t ? $t : $template;
		}
		$theme = locate_template( 'pikacart-id-card.php' );
		return $theme ? $theme : PKC_DIR . 'templates/id-card.php';
	}

	public static function body_class( $classes ) {
		if ( get_query_var( 'pkc_idcard' ) && self::context() ) {
			$classes[] = 'pkc-gallery-page';
		}
		return $classes;
	}

	public static function no_canonical( $redirect ) {
		return get_query_var( 'pkc_idcard' ) ? false : $redirect;
	}

	/* ---------- Data for the browser ---------- */

	public static function base_payload() {
		$org = is_user_logged_in() ? PKC_Organisations::current() : null;
		return array(
			'palettes' => PKC_Catalog::palettes(),
			'levels'   => PKC_Catalog::level_labels(),
			'idcard'   => self::url(),
			'app'      => pkc_url( 'app' ),
			'register' => pkc_url( 'register' ),
			'loggedIn' => (bool) $org,
			'home'     => home_url( '/' ),
			'price'    => pkc_money( PKC_Billing::default_plan_price() ),
		);
	}

	/** Homepage: best designs per category, premium first. */
	public static function home_payload() {
		$tree     = PKC_Catalog::tree();
		$showcase = array_map( array( __CLASS__, 'mix' ), PKC_Catalog::showcase( 60 ) );
		$pick     = array_filter( array_map( 'sanitize_key', explode( ',', (string) pkc_setting( 'home_featured_cats', '' ) ) ) );
		if ( $pick ) {
			$ordered = array();
			foreach ( $pick as $slug ) {
				foreach ( $showcase as $row ) {
					if ( $row['category']['slug'] === $slug ) {
						$ordered[] = $row;
					}
				}
			}
			$showcase = $ordered ? $ordered : $showcase;
		}
		$counts   = self::design_counts();
		foreach ( $showcase as &$row ) {
			$row['count'] = $counts[ $row['category']['id'] ] ?? 0;
			$row['url']   = self::url( $row['category']['slug'] );
		}
		return array_merge(
			self::base_payload(),
			array(
				'mode'     => 'home',
				'tree'     => $tree,
				'showcase' => $showcase,
			)
		);
	}

	/**
	 * Mix a category's designs for the showcase: premium designs lead, with
	 * professional, modern and simple ones in between (16 in total).
	 */
	public static function mix( $row ) {
		$premium = array();
		$others  = array();
		$seen    = array();
		foreach ( $row['templates'] as $t ) {
			$key = $t['name'];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			if ( 'premium' === $t['level'] ) {
				$premium[] = $t;
			} else {
				$others[] = $t;
			}
		}
		// Spread the others over the style levels.
		usort(
			$others,
			function ( $a, $b ) {
				$rank = array( 'professional' => 0, 'modern' => 1, 'simple' => 2 );
				return ( $rank[ $a['level'] ] ?? 3 ) <=> ( $rank[ $b['level'] ] ?? 3 );
			}
		);
		// Start each category at a different design so the rows look different.
		$shift = abs( crc32( $row['category']['slug'] ) );
		if ( count( $premium ) > 1 ) {
			$k       = $shift % count( $premium );
			$premium = array_merge( array_slice( $premium, $k ), array_slice( $premium, 0, $k ) );
		}
		if ( count( $others ) > 1 ) {
			$k      = ( $shift >> 3 ) % count( $others );
			$others = array_merge( array_slice( $others, $k ), array_slice( $others, 0, $k ) );
		}
		$pattern = array( 'p', 'p', 'o', 'p', 'o', 'p', 'p', 'o' );
		$out     = array();
		for ( $i = 0; count( $out ) < 16 && ( $premium || $others ); $i++ ) {
			$want = $pattern[ $i % count( $pattern ) ];
			if ( ( 'p' === $want && $premium ) || ! $others ) {
				$out[] = array_shift( $premium );
			} else {
				$out[] = array_shift( $others );
			}
		}
		$row['templates'] = $out;
		return $row;
	}

	public static function design_counts() {
		global $wpdb;
		$out = array();
		foreach ( $wpdb->get_results( 'SELECT category_id, COUNT(*) AS n FROM ' . pkc_table( 'templates' ) . " WHERE owner_org_id = 0 AND status = 'published' GROUP BY category_id" ) as $r ) {
			$out[ (int) $r->category_id ] = (int) $r->n;
		}
		return $out;
	}

	public static function gallery_payload( $ctx ) {
		return array_merge(
			self::base_payload(),
			array(
				'mode'      => $ctx['category'] ? 'gallery' : 'index',
				'tree'      => $ctx['tree'],
				'category'  => $ctx['category'] ? array( 'id' => $ctx['category']['id'], 'slug' => $ctx['category']['slug'], 'name' => $ctx['category']['name'] ) : null,
				'subtype'   => $ctx['subtype'],
				'templates' => $ctx['category'] ? $ctx['templates'] : array(),
				'showcase'  => $ctx['category'] ? array() : PKC_Catalog::showcase( 1 ),
			)
		);
	}

	public static function assets() {
		$home    = is_front_page();
		$gallery = get_query_var( 'pkc_idcard' ) && self::context();
		if ( ! $home && ! $gallery ) {
			return;
		}
		PKC_Assets::register();
		wp_enqueue_style( 'pkc-public' );
		wp_enqueue_style( 'pkc-card-fonts' );
		wp_enqueue_script( 'pkc-showcase' );
		wp_add_inline_script( 'pkc-showcase', 'window.PKC_PUB = ' . wp_json_encode( $home ? self::home_payload() : self::gallery_payload( self::context() ) ) . ';', 'before' );
	}

	/* ---------- Sitemap ---------- */

	public static function sitemap() {
		if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
			return;
		}
		require_once PKC_DIR . 'includes/class-sitemap.php';
		wp_register_sitemap_provider( 'pikacart', new PKC_Sitemap_Provider() );
	}

	/** Every gallery URL. */
	public static function urls() {
		$urls = array( self::url() );
		foreach ( PKC_Catalog::tree() as $c ) {
			if ( ! $c['subtypes'] ) {
				continue;
			}
			$urls[] = self::url( $c['slug'] );
			foreach ( $c['subtypes'] as $s ) {
				$urls[] = self::url( $c['slug'], $s['slug'] );
			}
		}
		return $urls;
	}
}
