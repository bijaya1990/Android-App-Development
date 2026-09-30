<?php
/**
 * Marketplace homepage design (orange): top utility bar, full-width slider,
 * category circles, flash sale, best sellers, promo cards, trust row,
 * customer reviews, newsletter band and a four-column footer.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_mk() {
	return 'market' === dm_store_opt( 'home_design', 'market' );
}

add_filter( 'body_class', function ( $c ) {
	if ( dm_mk() ) {
		$c[] = 'dm-mk';
	}
	return $c;
} );

/* -------------------------------------------------------------------------
 * Header pieces
 * ---------------------------------------------------------------------- */

function dm_mk_utility_bar() {
	if ( ! dm_mk() ) {
		return;
	}
	$right = (string) dm_store_opt( 'util_right' );
	if ( '' === $right ) {
		$b     = dm_business();
		$num   = $b['phone'] ? $b['phone'] : $b['whatsapp'];
		/* translators: %s phone */
		$right = $num ? sprintf( __( 'Support: %s', 'digimarket' ), $num ) : '';
	}
	echo '<div class="mk-util"><div class="dm-container mk-util-in">';
	echo '<span class="mk-util-l">' . dm_icon( 'bolt', 16 ) . ' ' . esc_html( dm_store_opt( 'util_left' ) ) . '</span>'; // phpcs:ignore
	echo '<span class="mk-util-c">' . dm_icon( 'fire', 16 ) . ' ' . esc_html( dm_store_opt( 'util_center' ) ) . '</span>'; // phpcs:ignore
	if ( $right ) {
		$tel = preg_replace( '/[^0-9+]/', '', $right );
		echo '<span class="mk-util-r">' . dm_icon( 'phone', 16 ) . ' ' . ( strlen( $tel ) >= 10 ? '<a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $right ) . '</a>' : esc_html( $right ) ) . '</span>'; // phpcs:ignore
	}
	echo '</div></div>';
}

function dm_mk_logo_src( $white = false ) {
	if ( dm_mk() ) {
		return DM_URI . '/assets/img/' . ( $white ? 'pikacart-logo-white-orange.svg' : 'pikacart-logo-orange.svg' );
	}
	return DM_URI . '/assets/img/' . ( $white ? 'pikacart-logo-white.svg' : 'pikacart-logo.svg' );
}

/* -------------------------------------------------------------------------
 * Product card (flash sale / best sellers)
 * ---------------------------------------------------------------------- */

function dm_mk_media( $pid, $eager = false ) {
	$pages = dm_product_flip_pages( $pid );
	$attr  = $eager ? array( 'loading' => 'eager' ) : array();
	$out   = '<a class="mk-card-media' . ( $pages ? ' dm-flip' : '' ) . '" href="' . esc_url( get_permalink( $pid ) ) . '" tabindex="-1" aria-hidden="true"' . ( $pages ? ' data-flip' : '' ) . '>';
	if ( $pages ) {
		$out .= '<span class="dm-flip-page">' . dm_product_thumb( $pid, 'dm-square', $attr ) . '</span>';
		foreach ( $pages as $pg ) {
			$out .= '<span class="dm-flip-page"><img class="dm-thumb-img" alt="" decoding="async" width="' . (int) $pg[1] . '" height="' . (int) $pg[2] . '" data-src="' . esc_url( $pg[0] ) . '"></span>';
		}
		$out .= '<span class="dm-flip-dots" aria-hidden="true">' . str_repeat( '<i></i>', count( $pages ) + 1 ) . '</span>';
	} else {
		$out .= dm_product_thumb( $pid, 'dm-square', $attr );
	}
	return $out;
}

function dm_mk_price( $pid ) {
	$type  = dm_listing_type( $pid );
	$price = dm_product_price( $pid );
	if ( 'service' === $type ) {
		return '<div class="mk-price"><small>' . esc_html__( 'Starting', 'digimarket' ) . '</small> <b>' . esc_html( dm_money_short( $price ) ) . '</b></div>';
	}
	if ( 'affiliate' === $type ) {
		return '<div class="mk-price">' . ( $price > 0 ? '<small>' . esc_html__( 'From', 'digimarket' ) . '</small> <b>' . esc_html( dm_money_short( $price ) ) . '</b>' : '<b>' . esc_html__( 'See deal', 'digimarket' ) . '</b>' ) . '</div>';
	}
	if ( $price <= 0 ) {
		return '<div class="mk-price"><b>' . esc_html__( 'Free', 'digimarket' ) . '</b></div>';
	}
	$sale = dm_product_sale_price( $pid );
	return '<div class="mk-price"><b>' . esc_html( dm_money_short( $price ) ) . '</b>' . ( null !== $sale ? ' <del>' . esc_html( dm_money_short( dm_product_regular_price( $pid ) ) ) . '</del>' : '' ) . '</div>';
}

function dm_mk_rating( $pid ) {
	$count = (int) get_post_meta( $pid, '_dm_rating_count', true );
	if ( ! $count ) {
		return '<div class="mk-rate is-empty"></div>';
	}
	$avg = (float) get_post_meta( $pid, '_dm_rating_avg', true );
	return '<div class="mk-rate">' . dm_star_row( $avg, 13 ) . ' <span>(' . esc_html( number_format_i18n( $avg, 1 ) ) . ')</span></div>';
}

/**
 * $variant: 'flash' (red % OFF badge + Add to Cart button) or 'best' (green Bestseller badge).
 */
function dm_mk_card( $pid, $variant = 'flash', $eager = false ) {
	$title = get_the_title( $pid );
	$url   = get_permalink( $pid );
	$type  = dm_listing_type( $pid );
	$off   = dm_discount_percent( $pid );
	echo '<article class="mk-card mk-card-' . esc_attr( $variant ) . '">';
	echo dm_mk_media( $pid, $eager ); // phpcs:ignore
	if ( 'flash' === $variant && $off > 0 ) {
		echo '<span class="mk-badge is-off">' . esc_html( sprintf( /* translators: %d */ __( '%d%% OFF', 'digimarket' ), $off ) ) . '</span>';
	} elseif ( 'best' === $variant && (int) get_post_meta( $pid, '_dm_sales', true ) > 0 ) {
		echo '<span class="mk-badge is-best">' . esc_html__( 'Bestseller', 'digimarket' ) . '</span>';
	} elseif ( 'service' === $type ) {
		echo '<span class="mk-badge is-svc">' . esc_html__( 'Service', 'digimarket' ) . '</span>';
	}
	echo '</a>';
	echo '<h3 class="mk-card-title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';
	echo dm_mk_price( $pid ) . dm_mk_rating( $pid ); // phpcs:ignore
	if ( 'flash' === $variant ) {
		if ( 'service' === $type ) {
			$wa = dm_service_whatsapp_url( $pid );
			echo '<a class="mk-btn-add" href="' . esc_url( $wa ? $wa : $url ) . '"' . ( $wa ? ' target="_blank" rel="noopener" data-track="whatsapp" data-ref="' . (int) $pid . '"' : '' ) . '>' . esc_html__( 'Enquire', 'digimarket' ) . '</a>';
		} elseif ( 'affiliate' === $type ) {
			echo '<a class="mk-btn-add" href="' . esc_url( $url ) . '">' . esc_html__( 'View deal', 'digimarket' ) . '</a>';
		} elseif ( get_current_user_id() && dm_user_purchase( get_current_user_id(), $pid ) ) {
			echo '<a class="mk-btn-add" href="' . esc_url( dm_url( 'account', 'purchases' ) ) . '">' . esc_html__( 'Download', 'digimarket' ) . '</a>';
		} elseif ( dm_can_purchase( $pid )[0] ) {
			echo '<button class="mk-btn-add" type="button" data-add-to-cart="' . (int) $pid . '">' . esc_html__( 'Add to Cart', 'digimarket' ) . '</button>';
		} else {
			echo '<a class="mk-btn-add" href="' . esc_url( $url ) . '">' . esc_html__( 'View', 'digimarket' ) . '</a>';
		}
	}
	echo '</article>';
}

function dm_mk_head( $title, $more = '', $more_label = '', $extra = '' ) {
	echo '<div class="mk-head"><h2>' . $title . '</h2>' . $extra; // phpcs:ignore
	if ( $more ) {
		echo '<a class="mk-more" href="' . esc_url( $more ) . '">' . esc_html( $more_label ? $more_label : __( 'View All', 'digimarket' ) ) . ' ' . dm_icon( 'arrow', 16 ) . '</a>'; // phpcs:ignore
	}
	echo '</div>';
}

function dm_mk_arrows() {
	return '<button class="dm-car-nav is-prev" type="button" aria-label="' . esc_attr__( 'Previous', 'digimarket' ) . '">' . dm_icon( 'chev-l', 20 ) . '</button><button class="dm-car-nav is-next" type="button" aria-label="' . esc_attr__( 'Next', 'digimarket' ) . '">' . dm_icon( 'chev-r', 20 ) . '</button>';
}

/* -------------------------------------------------------------------------
 * Homepage sections
 * ---------------------------------------------------------------------- */

function dm_mk_hero() {
	$slides  = array();
	$banners = dm_get_banners( 'mainslide', 0, 8 );
	foreach ( $banners as $i => $b ) {
		list( $open, $close ) = dm_banner_link_open( $b, 'mk-slide mk-slide-img' );
		$slides[]             = $open . dm_banner_img( $b, 0 === $i, '100vw' ) . $close;
	}
	if ( ! $slides ) {
		$slides = dm_mk_auto_slides();
	}
	if ( ! $slides ) {
		return;
	}
	echo '<section class="mk-hero" aria-roledescription="carousel" aria-label="' . esc_attr__( 'Offers', 'digimarket' ) . '"><div class="dm-car" data-carousel data-autoplay="5000"><div class="dm-car-track" tabindex="0">';
	echo implode( '', $slides ); // phpcs:ignore
	echo '</div>';
	if ( count( $slides ) > 1 ) {
		echo dm_mk_arrows(); // phpcs:ignore
		echo '<div class="dm-car-dots" role="tablist">';
		foreach ( $slides as $i => $s ) {
			echo '<button type="button" role="tab" aria-label="' . esc_attr( sprintf( /* translators: %d */ __( 'Show slide %d', 'digimarket' ), $i + 1 ) ) . '"' . ( 0 === $i ? ' class="is-active" aria-selected="true"' : '' ) . '></button>';
		}
		echo '</div>';
	}
	echo '</div></section>';
}

/**
 * Designed slides built from the store's own products when no slider banners exist.
 */
function dm_mk_auto_slides() {
	$slides = array();
	$max    = 0;
	foreach ( dm_sale_product_ids( 50 ) as $id ) {
		$max = max( $max, dm_discount_percent( $id ) );
	}
	$top = dm_product_ids( array( 'posts_per_page' => 3, 'meta_key' => '_dm_sales', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) ) );
	$img = (int) dm_store_opt( 'hero_img' );
	$art = $img ? '<span class="mk-slide-cut">' . wp_get_attachment_image( $img, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '' ) ) . '</span>' : dm_mk_collage( $top, true );
	$slides[] = dm_mk_slide( 'is-a', dm_store_opt( 'hero_line1' ), dm_store_opt( 'hero_line2' ), dm_store_opt( 'hero_sub' ), dm_store_opt( 'hero_cta' ), dm_products_url(), $art, $max >= 5 ? $max : 0 );

	$svc = function_exists( 'dm_lp_service_ids' ) ? dm_lp_service_ids( 3 ) : array();
	if ( $svc ) {
		$from = min( array_map( 'dm_product_price', $svc ) );
		/* translators: %s price */
		$slides[] = dm_mk_slide( 'is-b', __( 'Websites for', 'digimarket' ), __( 'Schools & Shops', 'digimarket' ), sprintf( __( 'Starting %s | Order on WhatsApp | Free support', 'digimarket' ), dm_money_short( $from ) ), __( 'See Packages', 'digimarket' ), dm_url( 'website-services' ), dm_mk_collage( $svc ), 0 );
	}
	$thm = function_exists( 'dm_lp_theme_ids' ) ? dm_lp_theme_ids( 3 ) : array();
	if ( $thm ) {
		$tmax = 0;
		foreach ( $thm as $id ) {
			$tmax = max( $tmax, dm_discount_percent( $id ) );
		}
		$slides[] = dm_mk_slide( 'is-c', __( 'Ready-made', 'digimarket' ), __( 'WordPress Themes', 'digimarket' ), __( 'Instant Download | Easy Setup | Mobile Friendly', 'digimarket' ), __( 'Browse Themes', 'digimarket' ), dm_url( 'wordpress-themes' ), dm_mk_collage( $thm ), $tmax >= 5 ? $tmax : 0 );
	}
	return $slides;
}

function dm_mk_collage( $ids, $eager = false ) {
	if ( ! $ids ) {
		return '';
	}
	$out = '<span class="mk-collage">';
	foreach ( array_slice( $ids, 0, 3 ) as $i => $id ) {
		$out .= '<span class="mk-col-' . (int) $i . '">' . dm_product_thumb( $id, 'dm-square', array( 'loading' => $eager ? 'eager' : 'lazy', 'alt' => '' ) ) . '</span>';
	}
	return $out . '</span>';
}

function dm_mk_slide( $class, $l1, $l2, $sub, $cta, $url, $art, $off ) {
	$out  = '<div class="mk-slide mk-slide-auto ' . esc_attr( $class ) . '"><div class="dm-container mk-slide-in">';
	$out .= '<div class="mk-slide-copy"><p class="mk-slide-h"><span>' . esc_html( $l1 ) . '</span><span class="is-accent">' . esc_html( $l2 ) . '</span></p>';
	$out .= '<p class="mk-slide-sub">' . esc_html( $sub ) . '</p><a class="mk-btn-orange" href="' . esc_url( $url ) . '">' . esc_html( $cta ) . ' ' . dm_icon( 'arrow', 18 ) . '</a></div>';
	$out .= '<div class="mk-slide-art">' . $art . '</div>';
	if ( $off ) {
		$out .= '<div class="mk-slide-off"><small>' . esc_html__( 'Up to', 'digimarket' ) . '</small><b>' . (int) $off . '%</b><span>' . esc_html__( 'Off', 'digimarket' ) . '</span></div>';
	}
	return $out . '</div></div>';
}

function dm_mk_categories() {
	$cats = dm_categories( array( 'parent' => 0, 'hide_empty' => false ) );
	if ( ! $cats ) {
		return;
	}
	echo '<section class="mk-sec mk-cats"><div class="dm-container">';
	dm_mk_head( esc_html__( 'Shop by Category', 'digimarket' ), dm_products_url() );
	echo '<div class="mk-cat-row">';
	foreach ( array_slice( $cats, 0, 9 ) as $i => $t ) {
		echo '<a class="mk-cat" href="' . esc_url( get_term_link( $t ) ) . '" style="--dm-cat:' . esc_attr( dm_category_color( $t, $i ) ) . '"><span class="mk-cat-circle">' . dm_category_icon_html( $t, $i ) . '</span><span class="mk-cat-name">' . esc_html( $t->name ) . '</span></a>'; // phpcs:ignore
	}
	echo '<a class="mk-cat is-more" href="' . esc_url( dm_products_url() ) . '" style="--dm-cat:#7C5CFF"><span class="mk-cat-circle"><span class="mk-dots"><i></i><i></i><i></i></span></span><span class="mk-cat-name">' . esc_html__( 'More Categories', 'digimarket' ) . '</span></a>';
	echo '</div></div></section>';
}

function dm_mk_flash() {
	$ids = dm_sale_product_ids( 12 );
	if ( ! $ids ) {
		return;
	}
	$now  = time();
	$ends = array();
	foreach ( $ids as $id ) {
		$e = dm_sale_ends_ts( $id );
		if ( $e > $now ) {
			$ends[] = $e;
		}
	}
	// Soonest real sale end; otherwise the end of today (site time).
	$end   = $ends ? min( $ends ) : (int) ( new DateTimeImmutable( 'tomorrow', wp_timezone() ) )->getTimestamp();
	$clock = '<div class="mk-clock" data-cd="' . (int) $end . '" aria-label="' . esc_attr__( 'Time left', 'digimarket' ) . '"><span><b data-cd-h>00</b><small>' . esc_html__( 'Hours', 'digimarket' ) . '</small></span><i>:</i><span><b data-cd-m>00</b><small>' . esc_html__( 'Minutes', 'digimarket' ) . '</small></span><i>:</i><span><b data-cd-s>00</b><small>' . esc_html__( 'Seconds', 'digimarket' ) . '</small></span></div>';
	echo '<section class="mk-sec mk-flash"><div class="dm-container">';
	dm_mk_head( dm_icon( 'bolt', 30 ) . ' ' . esc_html__( 'Flash Sale', 'digimarket' ), dm_url( 'sale' ), __( 'View All Deals', 'digimarket' ), $clock ); // phpcs:ignore
	echo '<div class="mk-rowcar" data-carousel><div class="dm-car-track mk-track" tabindex="0">';
	foreach ( $ids as $i => $id ) {
		dm_mk_card( $id, 'flash', $i < 5 );
	}
	echo '</div>' . dm_mk_arrows() . '</div></div></section>'; // phpcs:ignore
}

function dm_mk_bestsellers() {
	$ids = dm_product_ids( array( 'posts_per_page' => 6, 'meta_key' => '_dm_sales', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) ) );
	if ( ! $ids ) {
		return;
	}
	echo '<section class="mk-sec mk-best"><div class="dm-container">';
	dm_mk_head( esc_html__( 'Best Sellers', 'digimarket' ), add_query_arg( 'sort', 'bestselling', dm_products_url() ) );
	echo '<div class="mk-grid6">';
	foreach ( $ids as $id ) {
		dm_mk_card( $id, 'best' );
	}
	echo '</div></div></section>';
}

/**
 * Three promo cards: "Promo card" banners, else Website services, WordPress themes
 * and the biggest category, each with its own picture and offer line.
 */
function dm_mk_promos() {
	$banners = dm_get_banners( 'promo', 0, 3 );
	echo '<section class="mk-sec mk-promos"><div class="dm-container"><div class="mk-promo-row">';
	if ( $banners ) {
		foreach ( $banners as $b ) {
			list( $open, $close ) = dm_banner_link_open( $b, 'mk-promo mk-promo-img' );
			echo $open . dm_banner_img( $b, false, '(max-width: 700px) 92vw, 400px' ) . $close; // phpcs:ignore
		}
		echo '</div></div></section>';
		return;
	}
	$cards = array();
	$svc   = function_exists( 'dm_lp_service_ids' ) ? dm_lp_service_ids( 1 ) : array();
	if ( $svc ) {
		/* translators: %s price */
		$cards[] = array( __( 'Website', 'digimarket' ), __( 'Services', 'digimarket' ), sprintf( __( 'From %s', 'digimarket' ), dm_money_short( dm_product_price( $svc[0] ) ) ), dm_url( 'website-services' ), $svc[0] );
	}
	$thm = function_exists( 'dm_lp_theme_ids' ) ? dm_lp_theme_ids( 20 ) : array();
	if ( $thm ) {
		$cards[] = array( __( 'WordPress', 'digimarket' ), __( 'Themes', 'digimarket' ), dm_mk_offer_line( $thm ), dm_url( 'wordpress-themes' ), $thm[0] );
	}
	$skip = function_exists( 'dm_lp_theme_term' ) && dm_lp_theme_term() ? array( dm_lp_theme_term()->term_id ) : array();
	$cats = get_terms( array( 'taxonomy' => 'dm_category', 'parent' => 0, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => $skip, 'number' => 3 ) );
	foreach ( is_wp_error( $cats ) ? array() : $cats as $t ) {
		if ( count( $cards ) >= 3 ) {
			break;
		}
		$ids = dm_product_ids( array( 'posts_per_page' => 20, 'tax_query' => array( array( 'taxonomy' => 'dm_category', 'terms' => $t->term_id ) ), 'meta_key' => '_dm_sales', 'orderby' => array( 'meta_value_num' => 'DESC' ) ) ); // phpcs:ignore
		if ( ! $ids ) {
			continue;
		}
		$words   = explode( ' ', $t->name, 2 );
		$cards[] = array( $words[0], $words[1] ?? __( 'Collection', 'digimarket' ), dm_mk_offer_line( $ids ), get_term_link( $t ), $ids[0] );
	}
	foreach ( $cards as $i => $c ) {
		echo '<a class="mk-promo is-' . (int) $i . '" href="' . esc_url( $c[3] ) . '"><span class="mk-promo-copy"><span class="mk-promo-t">' . esc_html( $c[0] ) . '<b>' . esc_html( $c[1] ) . '</b></span><span class="mk-promo-tag">' . esc_html( $c[2] ) . '</span><span class="mk-promo-btn">' . esc_html__( 'Shop Now', 'digimarket' ) . ' ' . dm_icon( 'arrow', 14 ) . '</span></span><span class="mk-promo-art">' . dm_product_thumb( $c[4], 'dm-square', array( 'alt' => '' ) ) . '</span></a>'; // phpcs:ignore
	}
	echo '</div></div></section>';
}

function dm_mk_offer_line( $ids ) {
	$max = 0;
	$min = null;
	foreach ( $ids as $id ) {
		$max = max( $max, dm_discount_percent( $id ) );
		$p   = dm_product_price( $id );
		if ( $p > 0 ) {
			$min = null === $min ? $p : min( $min, $p );
		}
	}
	if ( $max >= 5 ) {
		/* translators: %d */
		return sprintf( __( 'Up to %d%% Off', 'digimarket' ), $max );
	}
	/* translators: %s price */
	return null !== $min ? sprintf( __( 'From %s', 'digimarket' ), dm_money_short( $min ) ) : __( 'Free downloads', 'digimarket' );
}

function dm_mk_trust_items() {
	$items = array();
	foreach ( dm_lines( dm_store_opt( 'mk_trust' ) ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( $p[0] ) {
			$items[] = array( $p[0], $p[1] ?? '' );
		}
	}
	if ( ! $items ) {
		$days  = (int) dm_opt( 'refund_window_days', 7 );
		$items = array(
			array( __( 'Instant Download', 'digimarket' ), __( 'Files ready right after payment', 'digimarket' ) ),
			array( __( 'Secure Payment', 'digimarket' ), __( '100% safe UPI, cards & netbanking', 'digimarket' ) ),
			/* translators: %d days */
			array( __( 'Easy Refunds', 'digimarket' ), sprintf( __( '%d-day refund policy', 'digimarket' ), $days ) ),
			array( __( 'WhatsApp Support', 'digimarket' ), __( 'We’re always here to help', 'digimarket' ) ),
		);
	}
	return array_slice( $items, 0, 4 );
}

function dm_mk_trust() {
	$icons = array( 'download', 'shield', 'refund', 'chat' );
	echo '<section class="mk-trust"><div class="dm-container"><ul class="mk-trust-row">';
	foreach ( dm_mk_trust_items() as $i => $t ) {
		echo '<li>' . dm_icon( $icons[ $i ] ?? 'check', 34 ) . '<span><strong>' . esc_html( $t[0] ) . '</strong><small>' . esc_html( $t[1] ) . '</small></span></li>'; // phpcs:ignore
	}
	echo '</ul></div></section>';
}

function dm_mk_reviews() {
	$rows = array_slice( dm_home_reviews( 6 ), 0, 3 );
	if ( ! $rows ) {
		return;
	}
	echo '<section class="mk-sec mk-say"><div class="dm-container">';
	dm_mk_head( esc_html__( 'What Our Customers Say', 'digimarket' ) );
	echo '<div class="mk-say-row">';
	$colors = array( '#F26A1B', '#2563EB', '#16A34A', '#DB2777' );
	foreach ( $rows as $i => $r ) {
		$av = ! empty( $r['img'] ) ? wp_get_attachment_image( $r['img'], 'thumbnail', false, array( 'class' => 'mk-say-av', 'alt' => '' ) ) : '<span class="mk-say-av" style="background:' . esc_attr( $colors[ $i % 4 ] ) . '">' . esc_html( mb_strtoupper( mb_substr( $r['name'], 0, 1 ) ) ) . '</span>';
		echo '<figure class="mk-say-card">' . $av . '<figcaption>' . dm_star_row( $r['rating'], 15 ) . '<blockquote>“' . esc_html( $r['text'] ) . '”</blockquote><strong>' . esc_html( $r['name'] ) . '</strong></figcaption></figure>'; // phpcs:ignore
	}
	echo '</div></div></section>';
}

/* -------------------------------------------------------------------------
 * Footer pieces
 * ---------------------------------------------------------------------- */

function dm_social_icon( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$map  = array(
		'facebook'  => array( '#1877F2', '<path fill="#fff" d="M13.5 21v-7h2.4l.4-2.9h-2.8V9.3c0-.8.2-1.4 1.4-1.4h1.5V5.3c-.3 0-1.1-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.1H8v2.9h2.5v7z"/>' ),
		'instagram' => array( 'linear-gradient(45deg,#F58529,#DD2A7B 50%,#8134AF)', '<rect x="5.5" y="5.5" width="13" height="13" rx="4" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="12" cy="12" r="3.2" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="16.1" cy="7.9" r="1" fill="#fff"/>' ),
		'youtube'   => array( '#FF0000', '<path fill="#fff" d="M10 8.8v6.4l5.5-3.2z"/>' ),
		'youtu.be'  => array( '#FF0000', '<path fill="#fff" d="M10 8.8v6.4l5.5-3.2z"/>' ),
		'pinterest' => array( '#E60023', '<path fill="#fff" d="M12.3 5C8.4 5 6.5 7.8 6.5 10.1c0 1.4.5 2.7 1.7 3.1.2.1.4 0 .4-.2l.2-.7c.1-.2 0-.3-.1-.5-.3-.4-.6-1-.6-1.7 0-2.2 1.6-4.1 4.2-4.1 2.3 0 3.6 1.4 3.6 3.3 0 2.5-1.1 4.6-2.7 4.6-.9 0-1.6-.8-1.4-1.7.3-1.1.8-2.3.8-3.1 0-.7-.4-1.3-1.2-1.3-.9 0-1.7 1-1.7 2.3 0 .8.3 1.4.3 1.4l-1.1 4.6c-.3 1.4 0 3.1 0 3.3 0 .1.2.1.2.1.1-.1 1.2-1.5 1.6-2.9l.6-2.4c.3.6 1.2 1.1 2.2 1.1 2.9 0 4.8-2.6 4.8-6.1C18.8 7.4 16.3 5 12.3 5z"/>' ),
		'linkedin'  => array( '#0A66C2', '<path fill="#fff" d="M7 9.5h2.3V17H7zM8.2 6a1.3 1.3 0 1 1 0 2.7 1.3 1.3 0 0 1 0-2.7zM10.8 9.5H13v1c.3-.6 1.1-1.2 2.3-1.2 2.4 0 2.9 1.6 2.9 3.7V17h-2.3v-3.6c0-.9 0-2-1.2-2s-1.4 1-1.4 1.9V17h-2.3z"/>' ),
		'twitter'   => array( '#111', '<path fill="#fff" d="M14.6 6h2l-4.4 5 5.2 7h-4.1l-3.2-4.2L6.4 18h-2l4.7-5.4L4.2 6h4.2l2.9 3.9zm-.7 10.8h1.1L8.1 7.1H6.9z"/>' ),
		'x.com'     => array( '#111', '<path fill="#fff" d="M14.6 6h2l-4.4 5 5.2 7h-4.1l-3.2-4.2L6.4 18h-2l4.7-5.4L4.2 6h4.2l2.9 3.9zm-.7 10.8h1.1L8.1 7.1H6.9z"/>' ),
		'whatsapp'  => array( '#25D366', '<path fill="#fff" d="M12 5a7 7 0 0 0-6 10.6L5 19l3.5-.9A7 7 0 1 0 12 5zm3.5 9.6c-.2.4-.9.8-1.3.8-.3 0-.8.1-2.6-.6-2.2-.9-3.6-3.1-3.7-3.2-.1-.2-.9-1.2-.9-2.2s.5-1.6.8-1.8c.2-.2.4-.2.5-.2h.4c.1 0 .3 0 .4.3l.6 1.4c0 .1.1.3 0 .4l-.3.4-.3.3c-.1.1-.2.2-.1.4.1.2.6 1 1.3 1.6.9.8 1.6 1 1.8 1.1.2.1.3.1.4-.1l.6-.7c.1-.2.3-.2.4-.1l1.4.7c.2.1.3.1.4.2 0 .1 0 .5-.1.9z"/>' ),
	);
	foreach ( $map as $k => $v ) {
		if ( false !== strpos( $host, $k ) ) {
			$bg = 0 === strpos( $v[0], 'linear' ) ? 'background-image:' . $v[0] : 'background:' . $v[0];
			return '<a class="mk-soc" href="' . esc_url( $url ) . '" target="_blank" rel="noopener me" style="' . esc_attr( $bg ) . '" aria-label="' . esc_attr( ucfirst( rtrim( $k, '.com' ) ) ) . '"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">' . $v[1] . '</svg></a>';
		}
	}
	return '<a class="mk-soc" href="' . esc_url( $url ) . '" target="_blank" rel="noopener me" style="background:#475569" aria-label="' . esc_attr( $host ) . '">' . dm_icon( 'globe', 18 ) . '</a>';
}

function dm_mk_socials() {
	$out = '';
	foreach ( dm_lines( dm_store_opt( 'social_links' ) ) as $u ) {
		$out .= dm_social_icon( $u );
	}
	return $out ? '<div class="mk-socs">' . $out . '</div>' : '';
}

function dm_mk_newsletter() {
	if ( ! dm_mk() || ! dm_store_opt( 'newsletter_on' ) ) {
		return;
	}
	echo '<section class="mk-news"><div class="dm-container mk-news-in"><div class="mk-news-copy">' . dm_icon( 'mail', 40 ) . '<span><strong>' . esc_html__( 'Get Exclusive Offers & Updates', 'digimarket' ) . '</strong><small>' . esc_html__( 'Subscribe to our newsletter and never miss a deal!', 'digimarket' ) . '</small></span></div>'; // phpcs:ignore
	echo '<form class="mk-news-form" method="post" action="' . esc_url( home_url( '/' ) ) . '"><input type="hidden" name="dm_action" value="newsletter">';
	dm_nonce_field( 'newsletter' );
	echo '<label class="screen-reader-text" for="mk-news-email">' . esc_html__( 'Email address', 'digimarket' ) . '</label><input id="mk-news-email" type="email" name="email" required placeholder="' . esc_attr__( 'Enter your email address', 'digimarket' ) . '"><input type="text" name="company" class="dm-apply-hp" tabindex="-1" autocomplete="off" aria-hidden="true"><button type="submit">' . esc_html__( 'Subscribe', 'digimarket' ) . '</button></form>';
	echo dm_mk_socials() . '</div></section>'; // phpcs:ignore
}

function dm_do_newsletter() {
	global $wpdb;
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ); // phpcs:ignore
	if ( ! empty( $_POST['company'] ) || ! is_email( $email ) ) { // phpcs:ignore
		dm_flash( 'error', __( 'Please enter a valid email address.', 'digimarket' ) );
		dm_back();
	}
	$rl = 'dm_news_' . md5( dm_client_ip() );
	if ( (int) get_transient( $rl ) >= 5 ) {
		dm_flash( 'error', __( 'Too many requests. Please try again later.', 'digimarket' ) );
		dm_back();
	}
	set_transient( $rl, (int) get_transient( $rl ) + 1, HOUR_IN_SECONDS );
	$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'leads' ) . " WHERE email = %s AND source = 'newsletter'", $email ) );
	if ( ! $exists ) {
		$wpdb->insert( dm_table( 'leads' ), array( 'email' => $email, 'source' => 'newsletter', 'status' => 'new', 'message' => __( 'Newsletter subscription', 'digimarket' ), 'ip' => dm_client_ip(), 'created_at' => dm_now() ) );
	}
	dm_flash( 'success', __( 'Thanks for subscribing! You will get our best offers first.', 'digimarket' ) );
	dm_back();
}
