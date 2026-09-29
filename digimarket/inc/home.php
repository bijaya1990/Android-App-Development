<?php
/**
 * Homepage layout v2: a clean, systematic store front.
 *
 * Hero → landing cards → Spotlight (Bestsellers | Shop by category + Limited
 * time offer) → deals / new arrivals → collections → What our customers say → trust.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recommended block order for layout v2. Applied once on upgrade; the owner's
 * on/off choices are kept, and the order can still be changed in Storefront & SEO.
 */
function dm_home_layout_order() {
	return array( 'hero', 'landing', 'spotlight', 'tiles', 'deals', 'campaign', 'services', 'new', 'sections', 'portfolio', 'reviews', 'trust', 'content', 'categories' );
}

function dm_home_layout_migrate() {
	if ( (int) get_option( 'dm_home_layout' ) >= 2 ) {
		return;
	}
	$st     = get_option( 'dm_store', array() );
	$st     = is_array( $st ) ? $st : array();
	$saved  = isset( $st['blocks'] ) ? (array) $st['blocks'] : array();
	$blocks = array();
	foreach ( dm_home_layout_order() as $i => $k ) {
		$on           = isset( $saved[ $k ]['on'] ) ? (int) $saved[ $k ]['on'] : 1;
		$blocks[ $k ] = array( 'on' => 'categories' === $k ? 0 : $on, 'order' => $i + 1 );
	}
	$st['blocks'] = $blocks;
	update_option( 'dm_store', $st );
	// Portfolio items so far are sample designs, not delivered client projects.
	foreach ( get_posts( array( 'post_type' => 'dm_portfolio', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $pf ) {
		update_post_meta( $pf, '_dm_pf_kind', 'demo' );
	}
	update_option( 'dm_home_layout', 2 );
	do_action( 'dm_store_saved' );
}

/* -------------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------------- */

/**
 * Digital products only (no services, no partner offers).
 */
function dm_digital_meta_query() {
	return array(
		'relation' => 'AND',
		array( 'relation' => 'OR', array( 'key' => '_dm_service_mode', 'compare' => 'NOT EXISTS' ), array( 'key' => '_dm_service_mode', 'value' => '1', 'compare' => '!=' ) ),
		array( 'relation' => 'OR', array( 'key' => '_dm_affiliate', 'compare' => 'NOT EXISTS' ), array( 'key' => '_dm_affiliate', 'value' => '1', 'compare' => '!=' ) ),
	);
}

function dm_bestseller_ids( $limit = 4 ) {
	$ids = dm_product_ids(
		array(
			'posts_per_page' => $limit,
			'meta_query'     => array( 'relation' => 'AND', 'sales' => array( 'key' => '_dm_sales', 'type' => 'NUMERIC' ), dm_digital_meta_query() ), // phpcs:ignore
			'orderby'        => array( 'sales' => 'DESC', 'date' => 'DESC' ),
		)
	);
	if ( count( $ids ) < $limit ) {
		$more = dm_product_ids( array( 'posts_per_page' => $limit, 'post__not_in' => $ids ? $ids : array( 0 ), 'meta_query' => dm_digital_meta_query() ) ); // phpcs:ignore
		$ids  = array_slice( array_merge( $ids, $more ), 0, $limit );
	}
	return $ids;
}

/**
 * The offer shown in the Spotlight band: an announcement with an end time,
 * else the soonest-ending sale, else the best public coupon. Null when none.
 */
function dm_spotlight_offer() {
	$end = dm_store_opt( 'announce_end' ) ? dm_local_ts( dm_store_opt( 'announce_end' ) ) : 0;
	if ( dm_store_opt( 'announce_on' ) && dm_store_opt( 'announce_text' ) && $end > time() ) {
		return array(
			'title' => dm_store_opt( 'announce_text' ),
			'link'  => dm_store_opt( 'announce_link' ) ? dm_store_opt( 'announce_link' ) : dm_url( 'sale' ),
			'end'   => $end,
		);
	}
	$sale = dm_sale_product_ids( 50 );
	if ( $sale ) {
		$max  = max( array_map( 'dm_discount_percent', $sale ) );
		$ends = array_filter( array_map( 'dm_sale_ends_ts', $sale ) );
		return array(
			/* translators: %d percent */
			'title' => sprintf( __( 'Up to %d%% OFF on selected items', 'digimarket' ), $max ),
			'link'  => dm_url( 'sale' ),
			'end'   => $ends ? min( $ends ) : 0,
		);
	}
	return null;
}

/**
 * Latest good reviews (buyers and clients) plus testimonials, for the homepage slider.
 */
function dm_home_reviews( $limit = 12 ) {
	global $wpdb;
	$out  = array();
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'reviews' ) . " WHERE status = 'approved' AND rating >= 4 AND comment IS NOT NULL AND comment <> '' ORDER BY id DESC LIMIT %d", $limit ) ); // phpcs:ignore
	foreach ( $rows as $r ) {
		$lines = explode( "\n", (string) $r->comment, 2 );
		$body  = 'client' === ( $r->reviewer_type ?? '' ) && count( $lines ) > 1 ? $lines[1] : (string) $r->comment;
		$out[] = array(
			'name'   => dm_review_author( $r ),
			'rating' => (int) $r->rating,
			'text'   => dm_trim_chars( $body, 180 ),
			'meta'   => get_the_title( $r->product_id ),
			'link'   => get_permalink( $r->product_id ),
			'badge'  => dm_review_badge( $r ),
		);
	}
	foreach ( dm_get_testimonials( 0, 6 ) as $t ) {
		$rating = (int) get_post_meta( $t->ID, '_dm_t_rating', true );
		$out[]  = array(
			'name'   => $t->post_title,
			'rating' => $rating ? $rating : 5,
			'text'   => dm_trim_chars( wp_strip_all_tags( $t->post_content ), 180 ),
			'meta'   => implode( ' · ', array_filter( array( get_post_meta( $t->ID, '_dm_t_org', true ), get_post_meta( $t->ID, '_dm_t_city', true ) ) ) ),
			'link'   => '',
			'badge'  => __( 'Verified client', 'digimarket' ),
			'img'    => get_post_thumbnail_id( $t->ID ),
		);
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Components
 * ---------------------------------------------------------------------- */

function dm_star_row( $rating, $size = 14 ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$out    = '<span class="dm-srow" role="img" aria-label="' . esc_attr( sprintf( /* translators: %s */ __( '%s out of 5 stars', 'digimarket' ), number_format_i18n( $rating, 1 ) ) ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<i class="' . ( $rating >= $i - 0.25 ? 'on' : ( $rating >= $i - 0.75 ? 'half' : '' ) ) . '">' . dm_icon( 'star', $size ) . '</i>';
	}
	return $out . '</span>';
}

function dm_panel_head( $title, $more = '', $more_label = '' ) {
	echo '<div class="dm-panel-head"><h2>' . esc_html( $title ) . '</h2>';
	if ( $more ) {
		echo '<a class="dm-more" href="' . esc_url( $more ) . '">' . esc_html( $more_label ? $more_label : __( 'View all', 'digimarket' ) ) . ' ' . dm_icon( 'arrow', 15 ) . '</a>'; // phpcs:ignore
	}
	echo '</div>';
}

/**
 * Compact product tile used in the Bestsellers panel.
 */
function dm_render_bs_item( $pid, $eager = false ) {
	$off   = dm_discount_percent( $pid );
	$count = (int) get_post_meta( $pid, '_dm_rating_count', true );
	$sale  = dm_product_sale_price( $pid );
	echo '<a class="dm-bs-item" href="' . esc_url( get_permalink( $pid ) ) . '"><span class="dm-bs-img">' . dm_product_thumb( $pid, 'dm-square', array( 'loading' => $eager ? 'eager' : 'lazy', 'alt' => get_the_title( $pid ) ) ); // phpcs:ignore
	if ( $off ) {
		echo '<span class="dm-bs-off">-' . (int) $off . '%</span>';
	}
	echo '</span><span class="dm-bs-title">' . esc_html( get_the_title( $pid ) ) . '</span>';
	if ( $count ) {
		echo '<span class="dm-bs-rate">' . dm_star_row( get_post_meta( $pid, '_dm_rating_avg', true ), 13 ) . '<small>(' . esc_html( number_format_i18n( $count ) ) . ')</small></span>'; // phpcs:ignore
	}
	$price = dm_product_price( $pid );
	echo '<span class="dm-bs-price"><b>' . ( $price > 0 ? esc_html( dm_money_short( $price ) ) : esc_html__( 'Free', 'digimarket' ) ) . '</b>' . ( null !== $sale ? ' <del>' . esc_html( dm_money_short( dm_product_regular_price( $pid ) ) ) . '</del>' : '' ) . '</span></a>';
}

/* -------------------------------------------------------------------------
 * Blocks
 * ---------------------------------------------------------------------- */

function dm_home_block_spotlight() {
	$offer = dm_spotlight_offer();
	// Two rows of bestsellers when the offer band makes the right column taller.
	$best  = dm_bestseller_ids( $offer ? 8 : 4 );
	$cats  = array_slice( dm_categories( array( 'parent' => 0, 'hide_empty' => false, 'orderby' => 'term_order' ) ), 0, 8 );
	if ( ! $best && ! $cats ) {
		return false;
	}
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-spot' . ( $best ? '' : ' no-best' ) . ( $offer ? '' : ' no-offer' ) . '">';
	if ( $best ) {
		echo '<div class="dm-panel dm-spot-best">';
		dm_panel_head( __( 'Bestsellers', 'digimarket' ), dm_products_url( array( 'sort' => 'popular' ) ) );
		echo '<div class="dm-bs-grid">';
		foreach ( $best as $i => $id ) {
			dm_render_bs_item( $id, $i < 2 );
		}
		echo '</div></div>';
	}
	if ( $cats ) {
		echo '<div class="dm-panel dm-spot-cats">';
		dm_panel_head( __( 'Shop by category', 'digimarket' ), dm_products_url() );
		echo '<div class="dm-cat-grid">';
		foreach ( $cats as $i => $c ) {
			echo '<a class="dm-cat-cell" href="' . esc_url( get_term_link( $c ) ) . '">' . dm_category_icon_html( $c, $i ) . '<span>' . esc_html( $c->name ) . '</span></a>'; // phpcs:ignore
		}
		echo '</div></div>';
	}
	if ( $offer ) {
		echo '<a class="dm-offer" href="' . esc_url( $offer['link'] ) . '"><span class="dm-offer-ico" aria-hidden="true">' . dm_icon( 'percent', 34 ) . '</span><span class="dm-offer-copy"><small>' . esc_html__( 'Limited time offer', 'digimarket' ) . '</small><strong>' . esc_html( $offer['title'] ) . '</strong><span class="dm-offer-btn">' . esc_html__( 'Shop now', 'digimarket' ) . '</span></span>'; // phpcs:ignore
		if ( $offer['end'] ) {
			echo '<span class="dm-cd" data-cd="' . (int) $offer['end'] . '" aria-label="' . esc_attr__( 'Time left', 'digimarket' ) . '">';
			foreach ( array( 'd' => __( 'Days', 'digimarket' ), 'h' => __( 'Hours', 'digimarket' ), 'm' => __( 'Mins', 'digimarket' ), 's' => __( 'Secs', 'digimarket' ) ) as $k => $l ) {
				echo '<span><b data-cd-' . esc_attr( $k ) . '>--</b><small>' . esc_html( $l ) . '</small></span>';
			}
			echo '</span>';
		}
		echo '</a>';
	}
	echo '</div></div></section>';
	return true;
}

function dm_home_block_reviews() {
	$items = dm_home_reviews( 12 );
	if ( ! $items ) {
		return false;
	}
	$colors = array( '#5B4BFF', '#0EA5E9', '#16A34A', '#F59E0B', '#E11D48', '#8B5CF6' );
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-panel dm-says">';
	dm_panel_head( __( 'What our customers say', 'digimarket' ) );
	echo '<div class="dm-row" data-carousel><div class="dm-car-track dm-says-track" tabindex="0">';
	foreach ( $items as $i => $r ) {
		$initial = mb_strtoupper( mb_substr( trim( $r['name'] ), 0, 1 ) );
		echo '<figure class="dm-say"><div class="dm-say-top">';
		echo ! empty( $r['img'] ) ? wp_get_attachment_image( (int) $r['img'], array( 56, 56 ), false, array( 'class' => 'dm-say-av', 'loading' => 'lazy', 'alt' => '' ) ) : '<span class="dm-say-av" style="background:' . esc_attr( $colors[ $i % count( $colors ) ] ) . '" aria-hidden="true">' . esc_html( $initial ) . '</span>';
		echo '<span class="dm-say-who"><strong>' . esc_html( $r['name'] ) . '</strong>' . dm_star_row( $r['rating'], 14 ) . '</span><span class="dm-say-q" aria-hidden="true">&rdquo;</span></div>'; // phpcs:ignore
		echo '<blockquote>' . esc_html( $r['text'] ) . '</blockquote>';
		echo '<figcaption><span class="dm-verified">' . dm_icon( 'check', 12 ) . ' ' . esc_html( $r['badge'] ) . '</span>' . ( $r['meta'] ? ( $r['link'] ? '<a href="' . esc_url( $r['link'] ) . '">' . esc_html( $r['meta'] ) . '</a>' : '<span>' . esc_html( $r['meta'] ) . '</span>' ) : '' ) . '</figcaption></figure>'; // phpcs:ignore
	}
	echo '</div><button class="dm-car-nav is-prev" type="button" aria-label="' . esc_attr__( 'Previous reviews', 'digimarket' ) . '">' . dm_icon( 'chev-l', 22 ) . '</button><button class="dm-car-nav is-next" type="button" aria-label="' . esc_attr__( 'More reviews', 'digimarket' ) . '">' . dm_icon( 'chev-r', 22 ) . '</button></div>'; // phpcs:ignore
	echo '</div></div></section>';
	return true;
}
