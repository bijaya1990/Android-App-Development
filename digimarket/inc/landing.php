<?php
/**
 * Landing pages: /website-services/ (WhatsApp orders) and /wordpress-themes/
 * (Razorpay checkout), plus the two showcase cards on the homepage.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/**
 * Storefront settings used by the landing pages (merged into dm_store_defaults()).
 */
function dm_landing_defaults() {
	return array(
		'lp_svc_card_title' => __( 'Professional websites for schools, committees & shops', 'digimarket' ),
		'lp_svc_card_btn'   => __( 'See details & price', 'digimarket' ),
		'lp_svc_wall'       => 0,
		'lp_thm_card_title' => __( 'Ready-made WordPress themes', 'digimarket' ),
		'lp_thm_card_btn'   => __( 'Buy now', 'digimarket' ),
		'lp_svc_h1'         => __( 'Website Development for Schools, Committees & Shops', 'digimarket' ),
		'lp_svc_sub'        => __( 'Pick your website type, see the price, and order on WhatsApp. We discuss first — you pay only after we agree.', 'digimarket' ),
		'lp_svc_seo_title'  => '',
		'lp_svc_seo_desc'   => '',
		'lp_svc_coupon'     => '',
		'lp_svc_coupon_txt' => '',
		'lp_hours'          => __( 'Mon–Sat, 10am–7pm IST', 'digimarket' ),
		'lp_thm_cat'        => 0,
		'lp_thm_h1'         => __( 'Ready-made WordPress Themes', 'digimarket' ),
		'lp_thm_sub'        => __( 'Beautiful, mobile-friendly themes you can install today. Pay securely with UPI and download instantly.', 'digimarket' ),
		'lp_thm_seo_title'  => '',
		'lp_thm_seo_desc'   => '',
		'lp_thm_includes'   => __( "Theme ZIP file (upload in WordPress → Appearance → Themes)\nDemo content to look exactly like the preview\nStep-by-step setup guide (PDF)\nFree updates for bug fixes\nWhatsApp support for installation questions", 'digimarket' ),
		'lp_thm_faq'        => __( "How do I install the theme? | In WordPress go to Appearance → Themes → Add New → Upload, choose the ZIP from your download, then Activate. The setup guide shows every step.\nWill it work on my hosting? | Yes — any hosting that runs WordPress 6.0+ and PHP 7.4+ (MilesWeb, Hostinger, Bluehost and others).\nWhen do I get the files? | Immediately. After payment the download button appears on the order page, in your email and in My Account → My Purchases.\nCan I use it for a client? | One purchase covers one website. Buy again for each extra site.\nCan you install and customise it for me? | Yes — see our website services, or message us on WhatsApp.\nWhat if it doesn’t work? | Message us first — we fix most issues within a day. If we cannot, you get a refund as per our refund policy.", 'digimarket' ),
	);
}

function dm_landing_routes() {
	return array( 'website-services', 'wordpress-themes' );
}

function dm_is_landing() {
	return in_array( dm_route(), dm_landing_routes(), true );
}

/* -------------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------------- */

/**
 * Published website services (service listings), in menu order.
 */
function dm_lp_service_ids( $limit = 30 ) {
	return dm_product_ids(
		array(
			'posts_per_page' => $limit,
			'meta_key'       => '_dm_service_mode',
			'meta_value'     => 1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);
}

/**
 * The category that holds WordPress themes: the chosen one, else the first whose
 * slug or name mentions WordPress/theme.
 */
function dm_lp_theme_term() {
	$id = (int) dm_store_opt( 'lp_thm_cat' );
	if ( $id ) {
		$t = get_term( $id, 'dm_category' );
		if ( $t && ! is_wp_error( $t ) ) {
			return $t;
		}
	}
	// Auto: the WordPress/theme category holding the most products.
	$best = null;
	$top  = -1;
	foreach ( dm_categories() as $t ) {
		$hay = strtolower( $t->slug . ' ' . $t->name );
		if ( false === strpos( $hay, 'wordpress' ) && ! preg_match( '/\\btheme/', $hay ) ) {
			continue;
		}
		$score = (int) $t->count * 2 + ( false !== strpos( $hay, 'wordpress' ) ? 1 : 0 );
		if ( $score > $top ) {
			$top  = $score;
			$best = $t;
		}
	}
	return $best;
}

/**
 * Digital products in the themes category (services and partner offers excluded).
 */
function dm_lp_theme_ids( $limit = 60 ) {
	$term = dm_lp_theme_term();
	if ( ! $term ) {
		return array();
	}
	return dm_product_ids(
		array(
			'posts_per_page' => $limit,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'tax_query'      => array( array( 'taxonomy' => 'dm_category', 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => true ) ), // phpcs:ignore
			'meta_query'     => array( // phpcs:ignore
				'relation' => 'AND',
				array( 'relation' => 'OR', array( 'key' => '_dm_service_mode', 'compare' => 'NOT EXISTS' ), array( 'key' => '_dm_service_mode', 'value' => '1', 'compare' => '!=' ) ),
				array( 'relation' => 'OR', array( 'key' => '_dm_affiliate', 'compare' => 'NOT EXISTS' ), array( 'key' => '_dm_affiliate', 'value' => '1', 'compare' => '!=' ) ),
			),
		)
	);
}

/**
 * Featured image + gallery attachment IDs.
 */
function dm_lp_images( $pid, $max = 5 ) {
	$ids = array_merge( array( (int) get_post_thumbnail_id( $pid ) ), array_map( 'absint', (array) get_post_meta( $pid, '_dm_gallery', true ) ) );
	return array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, $max );
}

function dm_lp_min_price( $ids ) {
	$min = null;
	foreach ( $ids as $id ) {
		$p = dm_product_price( $id );
		if ( $p > 0 && ( null === $min || $p < $min ) ) {
			$min = $p;
		}
	}
	return $min;
}

/**
 * Approved reviews across several products, newest first, plus the average.
 */
function dm_lp_reviews( $ids, $limit = 6 ) {
	global $wpdb;
	$ids = array_filter( array_map( 'absint', (array) $ids ) );
	if ( ! $ids ) {
		return array( array(), 0, 0 );
	}
	$in    = implode( ',', $ids );
	$table = dm_table( 'reviews' );
	$stats = $wpdb->get_row( "SELECT COUNT(*) c, AVG(rating) a FROM {$table} WHERE status = 'approved' AND product_id IN ({$in})" ); // phpcs:ignore
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'approved' AND product_id IN ({$in}) AND comment <> '' ORDER BY rating DESC, id DESC LIMIT %d", $limit ) ); // phpcs:ignore
	return array( $rows, $stats ? (int) $stats->c : 0, $stats ? round( (float) $stats->a, 1 ) : 0 );
}

/**
 * WhatsApp order link for a website type, naming the coupon when one is set.
 */
function dm_lp_order_url( $pid, $package = '' ) {
	$title = get_the_title( $pid );
	/* translators: %s website type */
	$msg  = $package ? sprintf( __( 'Hi, I want the %1$s package for "%2$s".', 'digimarket' ), $package, $title ) : sprintf( __( 'Hi, I want to order: %s.', 'digimarket' ), $title );
	$code = dm_store_opt( 'lp_svc_coupon' );
	if ( $code ) {
		/* translators: %s coupon */
		$msg .= ' ' . sprintf( __( 'Coupon: %s.', 'digimarket' ), $code );
	}
	$msg .= ' ' . __( 'Please share the details.', 'digimarket' );
	return dm_whatsapp_url( $msg, (string) get_post_meta( $pid, '_dm_service_whatsapp', true ) );
}

/**
 * The best public coupon across the themes: array( code, saving ) or null.
 */
function dm_lp_best_theme_coupon( $ids ) {
	$best = null;
	foreach ( $ids as $id ) {
		$c = dm_best_coupon_for( $id );
		if ( $c ) {
			$save = dm_product_price( $id ) - $c[1];
			if ( null === $best || $save > $best[1] ) {
				$best = array( $c[0], $save, $c[2] );
			}
		}
	}
	return $best;
}

/* -------------------------------------------------------------------------
 * Components
 * ---------------------------------------------------------------------- */

/**
 * Self-changing image stack: images cross-fade every few seconds (main.js).
 */
function dm_lp_fade( $att_ids, $size, $alt, $eager = false, $dots = true ) {
	$att_ids = array_values( array_filter( $att_ids ) );
	if ( ! $att_ids ) {
		return '<div class="dm-fade is-empty" aria-hidden="true"><span>' . esc_html( mb_substr( $alt, 0, 1 ) ) . '</span></div>';
	}
	$out = '<div class="dm-fade"' . ( count( $att_ids ) > 1 ? ' data-fade' : '' ) . '>';
	foreach ( $att_ids as $i => $a ) {
		dm_ensure_image_size( $a, $size );
		$out .= wp_get_attachment_image(
			$a,
			$size,
			false,
			array(
				'class'         => 0 === $i ? 'is-on' : '',
				'alt'           => 0 === $i ? $alt : '',
				'loading'       => ( $eager && 0 === $i ) ? 'eager' : 'lazy',
				'fetchpriority' => ( $eager && 0 === $i ) ? 'high' : 'auto',
				'decoding'      => 'async',
				'sizes'         => '(max-width: 700px) 100vw, 50vw',
			)
		);
	}
	if ( $dots && count( $att_ids ) > 1 ) {
		$out .= '<span class="dm-fade-dots" aria-hidden="true">' . str_repeat( '<i></i>', count( $att_ids ) ) . '</span>';
	}
	return $out . '</div>';
}

function dm_lp_coupon_strip( $code, $text ) {
	if ( ! $code ) {
		return;
	}
	echo '<div class="dm-lp-coupon"><span class="dm-lp-coupon-ico">' . dm_icon( 'percent', 22 ) . '</span><p>' . esc_html( $text ) . '</p><span class="dm-lp-code"><code>' . esc_html( $code ) . '</code><button type="button" class="dm-copy" data-copy="' . esc_attr( $code ) . '">' . dm_icon( 'copy', 14 ) . ' ' . esc_html__( 'Copy', 'digimarket' ) . '</button></span></div>'; // phpcs:ignore
}

function dm_lp_reviews_block( $ids, $title, $empty ) {
	list( $rows, $count, $avg ) = dm_lp_reviews( $ids, 6 );
	$testi                       = dm_get_testimonials( 0, 3 );
	echo '<section class="dm-lp-sec" id="reviews"><div class="dm-lp-head"><h2>' . esc_html( $title ) . '</h2>';
	if ( $count ) {
		echo '<span class="dm-lp-avg"><b>' . esc_html( number_format_i18n( $avg, 1 ) ) . ' ' . dm_icon( 'star', 16 ) . '</b> ' . esc_html( sprintf( /* translators: %d */ _n( '%d verified review', '%d verified reviews', $count, 'digimarket' ), $count ) ) . '</span>'; // phpcs:ignore
	}
	echo '</div>';
	if ( ! $rows && ! $testi ) {
		echo '<p class="dm-muted">' . esc_html( $empty ) . '</p></section>';
		return;
	}
	echo '<div class="dm-lp-revs">';
	foreach ( $rows as $r ) {
		$lines = explode( "\n", (string) $r->comment, 2 );
		$head  = 'client' === ( $r->reviewer_type ?? '' ) && count( $lines ) > 1 ? $lines[0] : '';
		$body  = $head ? $lines[1] : (string) $r->comment;
		echo '<figure class="dm-lp-rev"><div class="dm-rv-top"><span class="dm-rchip ' . ( $r->rating >= 4 ? 'is-good' : ( $r->rating >= 3 ? 'is-ok' : 'is-low' ) ) . '">' . (int) $r->rating . ' ' . dm_icon( 'star', 11 ) . '</span>' . ( $head ? '<strong>' . esc_html( $head ) . '</strong>' : '' ) . '</div>'; // phpcs:ignore
		echo '<blockquote>' . esc_html( dm_trim_chars( $body, 260 ) ) . '</blockquote>';
		echo '<figcaption><strong>' . esc_html( dm_review_author( $r ) ) . '</strong><span class="dm-verified">' . dm_icon( 'check', 13 ) . ' ' . esc_html( dm_review_badge( $r ) ) . '</span><small><a href="' . esc_url( get_permalink( $r->product_id ) . '#reviews' ) . '">' . esc_html( get_the_title( $r->product_id ) ) . '</a></small></figcaption></figure>'; // phpcs:ignore
	}
	echo '</div>';
	if ( $testi ) {
		echo '<div class="dm-testi-grid">';
		foreach ( $testi as $t ) {
			dm_testimonial_card( $t );
		}
		echo '</div>';
	}
	echo '</section>';
}

/* -------------------------------------------------------------------------
 * Homepage block: two showcase cards
 * ---------------------------------------------------------------------- */

function dm_home_block_landing() {
	$svc = dm_lp_service_ids( 12 );
	$thm = dm_lp_theme_ids( 12 );
	if ( ! $svc && ! $thm ) {
		return false;
	}
	echo '<section class="dm-block dm-show"><div class="dm-container"><div class="dm-show-grid' . ( $svc && $thm ? '' : ' is-one' ) . '">';
	if ( $svc ) {
		$wall = (int) dm_store_opt( 'lp_svc_wall' );
		$imgs = $wall ? array( $wall ) : array();
		if ( ! $imgs ) {
			foreach ( $svc as $id ) {
				$imgs[] = (int) get_post_thumbnail_id( $id );
			}
		}
		$min = dm_lp_min_price( $svc );
		echo '<a class="dm-show-card is-svc" href="' . esc_url( dm_url( 'website-services' ) ) . '" data-track="landing-services">';
		echo dm_lp_fade( array_slice( array_filter( $imgs ), 0, 5 ), 'dm-wide', dm_store_opt( 'lp_svc_card_title' ), true, false ); // phpcs:ignore
		echo '<span class="dm-show-body"><span class="dm-kicker">' . esc_html__( 'Website services', 'digimarket' ) . '</span><strong class="dm-show-title">' . esc_html( dm_store_opt( 'lp_svc_card_title' ) ) . '</strong>';
		echo '<span class="dm-show-meta">' . ( $min ? esc_html( sprintf( /* translators: %s price */ __( 'Starting %s', 'digimarket' ), dm_money_short( $min ) ) ) . ' · ' : '' ) . esc_html__( 'Order on WhatsApp', 'digimarket' ) . '</span>';
		echo '<span class="dm-btn dm-btn-light">' . esc_html( dm_store_opt( 'lp_svc_card_btn' ) ) . ' ' . dm_icon( 'arrow', 18 ) . '</span></span></a>'; // phpcs:ignore
	}
	if ( $thm ) {
		$imgs = array();
		foreach ( $thm as $id ) {
			$imgs[] = (int) get_post_thumbnail_id( $id );
		}
		$min = dm_lp_min_price( $thm );
		echo '<a class="dm-show-card is-thm" href="' . esc_url( dm_url( 'wordpress-themes' ) ) . '" data-track="landing-themes">';
		echo dm_lp_fade( array_slice( array_filter( $imgs ), 0, 6 ), 'dm-wide', dm_store_opt( 'lp_thm_card_title' ), true, false ); // phpcs:ignore
		echo '<span class="dm-show-body"><span class="dm-kicker">' . esc_html__( 'WordPress themes', 'digimarket' ) . '</span><strong class="dm-show-title">' . esc_html( dm_store_opt( 'lp_thm_card_title' ) ) . '</strong>';
		echo '<span class="dm-show-meta">' . ( $min ? esc_html( sprintf( /* translators: %s price */ __( 'From %s', 'digimarket' ), dm_money_short( $min ) ) ) . ' · ' : '' ) . esc_html__( 'Instant download', 'digimarket' ) . '</span>';
		echo '<span class="dm-btn dm-btn-buy">' . esc_html( dm_store_opt( 'lp_thm_card_btn' ) ) . ' ' . dm_icon( 'arrow', 18 ) . '</span></span></a>'; // phpcs:ignore
	}
	echo '</div></div></section>';
	return true;
}

/* -------------------------------------------------------------------------
 * SEO
 * ---------------------------------------------------------------------- */

function dm_lp_seo_title( $route ) {
	if ( 'website-services' === $route ) {
		$t = dm_store_opt( 'lp_svc_seo_title' );
		return $t ? $t : __( 'Website Development Services – Schools, Committees & Shops', 'digimarket' );
	}
	$t = dm_store_opt( 'lp_thm_seo_title' );
	return $t ? $t : __( 'Buy WordPress Themes – Instant Download', 'digimarket' );
}

function dm_lp_seo_desc( $route ) {
	if ( 'website-services' === $route ) {
		$d = dm_store_opt( 'lp_svc_seo_desc' );
		/* translators: %s price */
		return $d ? $d : sprintf( __( 'Professional websites for schools, puja committees, shops and businesses. See prices%s, real client reviews and order on WhatsApp — pay only after we agree.', 'digimarket' ), dm_lp_min_price( dm_lp_service_ids() ) ? ' ' . sprintf( __( 'from %s', 'digimarket' ), dm_money_short( dm_lp_min_price( dm_lp_service_ids() ) ) ) : '' );
	}
	$d = dm_store_opt( 'lp_thm_seo_desc' );
	return $d ? $d : __( 'Ready-made, mobile-friendly WordPress themes for schools, committees, shops and portfolios. Secure UPI payment via Razorpay and instant download.', 'digimarket' );
}

function dm_lp_schema( $route ) {
	$url  = dm_url( $route );
	$ids  = 'website-services' === $route ? dm_lp_service_ids() : dm_lp_theme_ids();
	$list = array();
	foreach ( $ids as $i => $id ) {
		$item = array(
			'@type' => 'website-services' === $route ? 'Service' : 'Product',
			'name'  => get_the_title( $id ),
			'url'   => get_permalink( $id ),
		);
		$img = get_the_post_thumbnail_url( $id, 'full' );
		if ( $img ) {
			$item['image'] = $img;
		}
		if ( 'website-services' === $route ) {
			$item['provider'] = array( '@id' => home_url( '/' ) . '#org' );
			$item['offers']   = array( '@type' => 'Offer', 'priceSpecification' => array( '@type' => 'PriceSpecification', 'minPrice' => dm_product_price( $id ), 'priceCurrency' => dm_opt( 'currency_code', 'INR' ) ) );
		} else {
			$item['offers'] = array( '@type' => 'Offer', 'price' => dm_product_price( $id ), 'priceCurrency' => dm_opt( 'currency_code', 'INR' ), 'availability' => 'https://schema.org/InStock', 'url' => get_permalink( $id ) );
			$cnt            = (int) get_post_meta( $id, '_dm_rating_count', true );
			if ( $cnt ) {
				$item['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => (float) get_post_meta( $id, '_dm_rating_avg', true ), 'reviewCount' => $cnt );
			}
		}
		$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => $item );
	}
	$graph = array(
		array(
			'@type'       => 'CollectionPage',
			'@id'         => $url . '#page',
			'url'         => $url,
			'name'        => dm_lp_seo_title( $route ),
			'description' => dm_lp_seo_desc( $route ),
			'isPartOf'    => array( '@id' => home_url( '/' ) . '#website' ),
			'mainEntity'  => array( '@type' => 'ItemList', 'itemListElement' => $list ),
		),
	);
	$faq = dm_parse_pairs( 'website-services' === $route ? dm_store_opt( 'default_faq' ) : dm_store_opt( 'lp_thm_faq' ) );
	if ( $faq ) {
		$graph[] = dm_faq_schema( $faq );
	}
	return $graph;
}

/* The two pages in the XML sitemap. */
add_action( 'wp_sitemaps_init', function ( $server ) {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}
	$server->registry->add_provider( 'dmpages', new DM_Sitemap_Landing() );
} );

if ( class_exists( 'WP_Sitemaps_Provider' ) ) {
	class DM_Sitemap_Landing extends WP_Sitemaps_Provider { // phpcs:ignore
		public function __construct() {
			$this->name        = 'dmpages';
			$this->object_type = 'dmpages';
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			if ( $page_num > 1 ) {
				return array();
			}
			$out = array();
			if ( dm_lp_service_ids( 1 ) ) {
				$out[] = array( 'loc' => dm_url( 'website-services' ) );
			}
			if ( dm_lp_theme_ids( 1 ) ) {
				$out[] = array( 'loc' => dm_url( 'wordpress-themes' ) );
			}
			return $out;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return 1;
		}
	}
}
