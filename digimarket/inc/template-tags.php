<?php
/**
 * Template tags: product cards, price/rating display, homepage blocks and
 * service-page components.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Small display helpers
 * ---------------------------------------------------------------------- */

/**
 * Price for cards: "₹299 ₹499 40% off" / "Starting ₹5,000" / "From ₹69/month" / "Free".
 */
function dm_card_price_html( $pid ) {
	$type  = dm_listing_type( $pid );
	$price = dm_product_price( $pid );
	if ( 'service' === $type ) {
		return '<span class="dm-cp"><small>' . esc_html__( 'Starting', 'digimarket' ) . '</small> <b>' . esc_html( dm_money_short( $price ) ) . '</b></span>';
	}
	if ( 'affiliate' === $type ) {
		$suffix = (string) get_post_meta( $pid, '_dm_aff_suffix', true );
		return $price > 0 ? '<span class="dm-cp"><small>' . esc_html__( 'From', 'digimarket' ) . '</small> <b>' . esc_html( dm_money_short( $price ) ) . '</b>' . ( $suffix ? '<small>' . esc_html( $suffix ) . '</small>' : '' ) . '</span>' : '<span class="dm-cp"><b>' . esc_html__( 'See deal', 'digimarket' ) . '</b></span>';
	}
	if ( $price <= 0 ) {
		return '<span class="dm-cp"><b class="is-free">' . esc_html__( 'Free', 'digimarket' ) . '</b></span>';
	}
	$sale = dm_product_sale_price( $pid );
	$out  = '<span class="dm-cp"><b>' . esc_html( dm_money_short( $price ) ) . '</b>';
	if ( null !== $sale ) {
		$out .= ' <del>' . esc_html( dm_money_short( dm_product_regular_price( $pid ) ) ) . '</del> <span class="dm-cp-off">' . esc_html( sprintf( /* translators: %d */ __( '%d%% off', 'digimarket' ), dm_discount_percent( $pid ) ) ) . '</span>';
	}
	return $out . '</span>';
}

/**
 * Green rating chip "4.5 ★ (12)".
 */
function dm_rating_chip( $pid, $show_count = true ) {
	$count = (int) get_post_meta( $pid, '_dm_rating_count', true );
	if ( ! $count ) {
		return '';
	}
	$avg   = (float) get_post_meta( $pid, '_dm_rating_avg', true );
	$class = $avg >= 4 ? 'is-good' : ( $avg >= 3 ? 'is-ok' : 'is-low' );
	return '<span class="dm-rchip-wrap"><span class="dm-rchip ' . $class . '" aria-label="' . esc_attr( sprintf( /* translators: %s */ __( 'Rated %s out of 5', 'digimarket' ), number_format_i18n( $avg, 1 ) ) ) . '">' . esc_html( number_format_i18n( $avg, 1 ) ) . ' ' . dm_icon( 'star', 11 ) . '</span>' . ( $show_count ? '<span class="dm-rcount">(' . esc_html( number_format_i18n( $count ) ) . ')</span>' : '' ) . '</span>';
}

function dm_countdown_html( $ts, $label = '' ) {
	if ( ! $ts || $ts < time() ) {
		return '';
	}
	return '<span class="dm-countdown" data-countdown="' . (int) $ts . '">' . ( $label ? esc_html( $label ) . ' ' : '' ) . dm_icon( 'clock', 14 ) . ' <b>--:--:--</b></span>';
}

/**
 * One short "meta" line under a card title.
 */
function dm_card_meta_line( $pid ) {
	$type = dm_listing_type( $pid );
	if ( 'service' === $type ) {
		$t = get_post_meta( $pid, '_dm_turnaround', true );
		return $t ? dm_icon( 'clock', 13 ) . ' ' . esc_html( $t ) : dm_icon( 'chat', 13 ) . ' ' . esc_html__( 'Discuss on WhatsApp', 'digimarket' );
	}
	if ( 'affiliate' === $type ) {
		$b = get_post_meta( $pid, '_dm_aff_best_for', true );
		return $b ? esc_html( sprintf( /* translators: %s */ __( 'Best for: %s', 'digimarket' ), $b ) ) : esc_html__( 'Partner deal', 'digimarket' );
	}
	return dm_icon( 'bolt', 13 ) . ' ' . esc_html( dm_delivery_label( get_post_meta( $pid, '_dm_delivery', true ) ) );
}

/**
 * Product card (grid).
 */
/**
 * Extra photos (after the cover) for the page-turning card thumbnail: up to 3.
 * Returns [ [url, w, h], … ] or an empty array when the product has only a cover.
 */
function dm_product_flip_pages( $pid ) {
	if ( ! has_post_thumbnail( $pid ) ) {
		return array();
	}
	$out = array();
	foreach ( array_slice( dm_product_photo_ids( $pid ), 1, DM_REQUIRED_PHOTOS - 1 ) as $id ) {
		dm_ensure_image_size( $id, 'dm-square' );
		$src = wp_get_attachment_image_src( $id, 'dm-square' );
		if ( $src ) {
			$out[] = array( $src[0], $src[1], $src[2] );
		}
	}
	return $out;
}

function dm_render_product_card( $pid, $args = array() ) {
	$args  = wp_parse_args( $args, array( 'eager' => false, 'class' => '', 'h' => 'h3' ) );
	$htag  = 'h2' === $args['h'] ? 'h2' : 'h3';
	$type  = dm_listing_type( $pid );
	$url   = get_permalink( $pid );
	$title = get_the_title( $pid );
	$off   = dm_discount_percent( $pid );
	echo '<article class="dm-pc dm-pc-' . esc_attr( $type ) . ( $args['class'] ? ' ' . esc_attr( $args['class'] ) : '' ) . '">';
	$pages = dm_product_flip_pages( $pid );
	echo '<a class="dm-pc-media' . ( $pages ? ' dm-flip' : '' ) . '" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true"' . ( $pages ? ' data-flip' : '' ) . '>';
	if ( $pages ) {
		echo '<span class="dm-flip-page">' . dm_product_thumb( $pid, 'dm-square', $args['eager'] ? array( 'loading' => 'eager' ) : array() ) . '</span>'; // phpcs:ignore
		foreach ( $pages as $pg ) {
			echo '<span class="dm-flip-page"><img class="dm-thumb-img" alt="" decoding="async" width="' . (int) $pg[1] . '" height="' . (int) $pg[2] . '" data-src="' . esc_url( $pg[0] ) . '"></span>';
		}
		echo '<span class="dm-flip-dots" aria-hidden="true">' . str_repeat( '<i></i>', count( $pages ) + 1 ) . '</span>';
	} else {
		echo dm_product_thumb( $pid, 'dm-square', $args['eager'] ? array( 'loading' => 'eager' ) : array() ); // phpcs:ignore
	}
	$badges = dm_product_badges( $pid, $off >= 5 ? 1 : 2 );
	if ( $off >= 5 || $badges ) {
		echo '<span class="dm-pc-badges">';
		if ( $off >= 5 ) {
			echo '<span class="dm-badge2 is-off">' . esc_html( sprintf( /* translators: %d */ __( '%d%% OFF', 'digimarket' ), $off ) ) . '</span>';
		}
		foreach ( $badges as $b ) {
			echo '<span class="dm-badge2 ' . esc_attr( $b[1] ) . '">' . esc_html( $b[0] ) . '</span>';
		}
		echo '</span>';
	}
	echo '</a>';
	if ( is_user_logged_in() && 'digital' === $type ) {
		$on = dm_in_wishlist( $pid );
		echo '<button class="dm-wish' . ( $on ? ' is-on' : '' ) . '" data-product="' . (int) $pid . '" aria-label="' . esc_attr__( 'Save to wishlist', 'digimarket' ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">' . dm_icon( 'heart', 18 ) . '</button>'; // phpcs:ignore
	}
	echo '<div class="dm-pc-body">';
	echo '<' . $htag . ' class="dm-pc-title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></' . $htag . '>';
	echo '<div class="dm-pc-rate">' . dm_rating_chip( $pid ) . '</div>'; // phpcs:ignore
	echo '<div class="dm-pc-price">' . wp_kses_post( dm_card_price_html( $pid ) ) . '</div>';
	echo '<div class="dm-pc-meta">' . dm_card_meta_line( $pid ) . '</div>'; // phpcs:ignore
	$ends = 'digital' === $type ? dm_sale_ends_ts( $pid ) : 0;
	if ( $ends && $ends - time() < 7 * DAY_IN_SECONDS ) {
		echo dm_countdown_html( $ends, __( 'Ends in', 'digimarket' ) ); // phpcs:ignore
	}
	echo '</div>';
	if ( 'service' === $type ) {
		$wa = dm_service_whatsapp_url( $pid );
		if ( $wa ) {
			echo '<a class="dm-pc-act is-wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" data-track="whatsapp" data-ref="' . (int) $pid . '" aria-label="' . esc_attr( sprintf( /* translators: %s */ __( 'Enquire about %s on WhatsApp', 'digimarket' ), $title ) ) . '">' . dm_icon_whatsapp( 18 ) . '</a>'; // phpcs:ignore
		}
	} elseif ( 'digital' === $type && dm_can_purchase( $pid )[0] && ! dm_user_purchase( get_current_user_id(), $pid ) ) {
		echo '<button class="dm-pc-act" type="button" data-add-to-cart="' . (int) $pid . '" aria-label="' . esc_attr( sprintf( /* translators: %s */ __( 'Add %s to cart', 'digimarket' ), $title ) ) . '">' . dm_icon( 'cart', 18 ) . '</button>'; // phpcs:ignore
	}
	echo '</article>';
}

/**
 * Compact tile used inside homepage section boxes.
 */
function dm_render_mini_card( $pid ) {
	$off  = dm_discount_percent( $pid );
	$line = dm_offer_line( $pid );
	echo '<a class="dm-mc" href="' . esc_url( get_permalink( $pid ) ) . '"><span class="dm-mc-img">' . dm_product_thumb( $pid, 'dm-square' ) . '</span>'; // phpcs:ignore
	echo '<span class="dm-mc-name">' . esc_html( get_the_title( $pid ) ) . '</span><span class="dm-mc-offer' . ( $off >= 5 ? ' is-off' : '' ) . '">' . esc_html( $line ) . '</span></a>';
}

/**
 * Horizontal scrolling product row with arrows.
 */
function dm_render_row( $ids, $title, $more_url = '', $extra = '' ) {
	if ( ! $ids ) {
		return false;
	}
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-rowbox">';
	echo '<div class="dm-block-head"><h2>' . esc_html( $title ) . '</h2>' . $extra; // phpcs:ignore
	if ( $more_url ) {
		echo '<a class="dm-more" href="' . esc_url( $more_url ) . '">' . esc_html__( 'View all', 'digimarket' ) . ' ' . dm_icon( 'arrow', 16 ) . '</a>'; // phpcs:ignore
	}
	echo '</div><div class="dm-row" data-carousel><div class="dm-car-track dm-row-track" tabindex="0">';
	foreach ( $ids as $id ) {
		dm_render_product_card( $id );
	}
	echo '</div><button class="dm-car-nav is-prev" type="button" aria-label="' . esc_attr__( 'Scroll left', 'digimarket' ) . '">' . dm_icon( 'chev-l', 22 ) . '</button><button class="dm-car-nav is-next" type="button" aria-label="' . esc_attr__( 'Scroll right', 'digimarket' ) . '">' . dm_icon( 'chev-r', 22 ) . '</button></div></div></div></section>'; // phpcs:ignore
	return true;
}

function dm_product_ids( $args ) {
	$q = dm_query_products( array_merge( array( 'fields' => 'ids' ), $args ) );
	return $q->posts;
}

/* -------------------------------------------------------------------------
 * Homepage blocks
 * ---------------------------------------------------------------------- */

function dm_render_home_block( $key ) {
	$fn = 'dm_home_block_' . $key;
	return function_exists( $fn ) ? call_user_func( $fn ) : false;
}

function dm_home_block_categories() {
	$cats = dm_categories( array( 'parent' => 0, 'hide_empty' => false, 'orderby' => 'term_order' ) );
	if ( ! $cats ) {
		return false;
	}
	echo '<nav class="dm-catrow" aria-label="' . esc_attr__( 'Shop by category', 'digimarket' ) . '"><div class="dm-container"><div class="dm-catrow-track">';
	if ( dm_sale_product_ids( 1 ) ) {
		echo '<a class="dm-catrow-item is-sale" href="' . esc_url( dm_url( 'sale' ) ) . '"><span class="dm-cat-ico" style="--dm-cat:#E11D48">' . dm_icon( 'percent', 26 ) . '</span><span>' . esc_html__( 'Sale', 'digimarket' ) . '</span></a>'; // phpcs:ignore
	}
	foreach ( $cats as $i => $c ) {
		echo '<a class="dm-catrow-item" href="' . esc_url( get_term_link( $c ) ) . '">' . dm_category_icon_html( $c, $i ) . '<span>' . esc_html( $c->name ) . '</span></a>'; // phpcs:ignore
	}
	echo '</div></div></nav>';
	return true;
}

function dm_home_block_hero() {
	if ( dm_render_hero_carousel() ) {
		return true;
	}
	// First-run hero until banners are added.
	echo '<section class="dm-hero2"><div class="dm-container"><div class="dm-hero2-card"><div class="dm-hero2-copy">';
	echo '<span class="dm-hero2-chip">' . esc_html__( 'Instant download · Secure UPI checkout', 'digimarket' ) . '</span>';
	echo '<h1>' . esc_html( get_theme_mod( 'dm_hero_title', __( 'Notes, templates & websites — all in one place', 'digimarket' ) ) ) . '</h1>';
	echo '<p>' . esc_html( get_theme_mod( 'dm_hero_subtitle', __( 'Study notes, resume templates, kids worksheets and professional websites for schools, committees and shops.', 'digimarket' ) ) ) . '</p>';
	echo '<div class="dm-hero2-cta"><a class="dm-btn dm-btn-light dm-btn-lg" href="' . esc_url( dm_products_url() ) . '">' . esc_html__( 'Shop now', 'digimarket' ) . '</a>';
	$wa = dm_whatsapp_url( __( 'Hi, I want to discuss a website.', 'digimarket' ) );
	if ( $wa ) {
		echo ' <a class="dm-btn dm-btn-wa dm-btn-lg" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" data-track="whatsapp">' . dm_icon_whatsapp( 20 ) . ' ' . esc_html__( 'Get a website', 'digimarket' ) . '</a>'; // phpcs:ignore
	}
	echo '</div>';
	if ( current_user_can( 'dm_manage_marketplace' ) ) {
		echo '<p class="dm-hero2-admin">' . esc_html__( 'Admin tip: add banners or import the demo set in Marketplace → Banners to replace this box with a sliding carousel.', 'digimarket' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=dm_banner' ) ) . '">' . esc_html__( 'Open Banners', 'digimarket' ) . '</a></p>';
	}
	echo '</div><div class="dm-hero2-art" aria-hidden="true">';
	foreach ( array( array( 'book', __( 'Notes', 'digimarket' ) ), array( 'resume', __( 'Resumes', 'digimarket' ) ), array( 'globe', __( 'Websites', 'digimarket' ) ), array( 'kids', __( 'Kids', 'digimarket' ) ) ) as $i => $a ) {
		echo '<span class="dm-hero2-tile t' . (int) $i . '">' . dm_icon( $a[0], 28 ) . '<b>' . esc_html( $a[1] ) . '</b></span>'; // phpcs:ignore
	}
	echo '</div></div></div></section>';
	return true;
}

function dm_home_block_tiles() {
	return dm_render_offer_tiles();
}

function dm_home_block_deals() {
	$ids = dm_sale_product_ids( 12 );
	if ( ! $ids ) {
		return false;
	}
	$ends = array_filter( array_map( 'dm_sale_ends_ts', $ids ) );
	$soon = $ends ? min( $ends ) : 0;
	$head = $soon ? dm_countdown_html( $soon, __( 'Ends in', 'digimarket' ) ) : '';
	return dm_render_row( $ids, $soon ? __( 'Deals of the day', 'digimarket' ) : __( 'Top deals', 'digimarket' ), dm_url( 'sale' ), $head );
}

function dm_home_block_sections() {
	$n = (int) dm_store_opt( 'section_count', 4 );
	if ( $n < 1 ) {
		return false;
	}
	$grads = dm_section_gradients();
	$done  = false;
	echo '<section class="dm-block"><div class="dm-container dm-boxes">';
	foreach ( dm_home_section_terms( $n ) as $i => $t ) {
		$ids = dm_product_ids(
			array(
				'posts_per_page' => 4,
				'tax_query'      => array( array( 'taxonomy' => 'dm_category', 'terms' => $t->term_id ) ),
				'meta_key'       => '_dm_sales',
				'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
			)
		);
		if ( count( $ids ) < 2 ) {
			continue;
		}
		$ids  = array_slice( $ids, 0, 2 );
		$g    = $grads[ $i % count( $grads ) ];
		$done = true;
		echo '<div class="dm-box dm-box-n' . count( $ids ) . '" style="--g1:' . esc_attr( $g[0] ) . ';--g2:' . esc_attr( $g[1] ) . '"><div class="dm-box-head"><h2>' . esc_html( sprintf( /* translators: %s */ __( 'Best of %s', 'digimarket' ), $t->name ) ) . '</h2><a class="dm-box-go" href="' . esc_url( get_term_link( $t ) ) . '" aria-label="' . esc_attr( sprintf( /* translators: %s */ __( 'See all %s', 'digimarket' ), $t->name ) ) . '">' . dm_icon( 'arrow', 18 ) . '</a></div><div class="dm-box-grid">'; // phpcs:ignore
		foreach ( $ids as $id ) {
			dm_render_mini_card( $id );
		}
		echo '</div></div>';
	}
	echo '</div></section>';
	return $done;
}

function dm_home_block_campaign() {
	return dm_render_wide_banner( 'campaign' );
}

function dm_home_block_services() {
	// Only services ticked "Featured on homepage" appear in the band.
	$ids = dm_product_ids(
		array(
			'posts_per_page' => 8,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'meta_query'     => array( // phpcs:ignore
				array( 'key' => '_dm_service_mode', 'value' => '1' ),
				array( 'key' => '_dm_featured', 'value' => '1' ),
			),
		)
	);
	if ( ! $ids ) {
		return false;
	}
	$wa = dm_whatsapp_url( __( 'Hi, I want to discuss a website.', 'digimarket' ) );
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-svcband"><div class="dm-svcband-head"><div><span class="dm-kicker">' . esc_html__( 'Website services', 'digimarket' ) . '</span><h2>' . esc_html__( 'Professional websites for schools, committees & shops', 'digimarket' ) . '</h2><p>' . esc_html__( 'Clear starting prices. We discuss on WhatsApp first — you pay only after we agree.', 'digimarket' ) . '</p></div>';
	if ( $wa ) {
		echo '<a class="dm-btn dm-btn-wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" data-track="whatsapp">' . dm_icon_whatsapp( 18 ) . ' ' . esc_html__( 'Chat now', 'digimarket' ) . '</a>'; // phpcs:ignore
	}
	echo '</div><div class="dm-row" data-carousel><div class="dm-car-track dm-row-track" tabindex="0">';
	foreach ( $ids as $id ) {
		dm_render_product_card( $id );
	}
	echo '</div><button class="dm-car-nav is-prev" type="button" aria-label="' . esc_attr__( 'Scroll left', 'digimarket' ) . '">' . dm_icon( 'chev-l', 22 ) . '</button><button class="dm-car-nav is-next" type="button" aria-label="' . esc_attr__( 'Scroll right', 'digimarket' ) . '">' . dm_icon( 'chev-r', 22 ) . '</button></div>'; // phpcs:ignore
	dm_render_process_steps();
	echo '</div></div></section>';
	return true;
}

function dm_home_block_portfolio() {
	$items = dm_get_portfolio( 0, 3 );
	if ( ! $items ) {
		return false;
	}
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-panel">';
	dm_panel_head( __( 'Sample websites you can get', 'digimarket' ), get_post_type_archive_link( 'dm_portfolio' ), __( 'See all samples', 'digimarket' ) );
	echo '<div class="dm-pf-grid">';
	foreach ( $items as $p ) {
		dm_portfolio_card( $p );
	}
	echo '</div></div></div></section>';
	return true;
}

function dm_home_block_new() {
	// Featured products are pinned first, then the newest.
	// Digital products only — services have their own band and landing page.
	$pin = dm_product_ids( array( 'posts_per_page' => 12, 'meta_query' => array( 'relation' => 'AND', array( 'key' => '_dm_featured', 'value' => '1' ), dm_digital_meta_query() ) ) ); // phpcs:ignore
	$ids = array_slice( array_values( array_unique( array_merge( $pin, dm_product_ids( array( 'posts_per_page' => 12, 'meta_query' => dm_digital_meta_query() ) ) ) ) ), 0, 12 ); // phpcs:ignore
	return dm_render_row( $ids, __( 'New arrivals', 'digimarket' ), dm_products_url( array( 'sort' => 'newest' ) ) );
}

function dm_trust_items() {
	return array(
		array( 'shield', __( 'Secure payments', 'digimarket' ), __( 'UPI, cards & netbanking via Razorpay', 'digimarket' ) ),
		array( 'bolt', __( 'Instant download', 'digimarket' ), __( 'Files ready right after payment', 'digimarket' ) ),
		array( 'chat', __( 'WhatsApp support', 'digimarket' ), dm_store_opt( 'trust_reply' ) ),
		array( 'refund', __( 'Refund promise', 'digimarket' ), __( 'Clear, written refund policy', 'digimarket' ) ),
	);
}

function dm_home_block_trust() {
	echo '<section class="dm-block"><div class="dm-container"><ul class="dm-trust2">';
	foreach ( dm_trust_items() as $t ) {
		echo '<li>' . dm_icon( $t[0], 26 ) . '<span><strong>' . esc_html( $t[1] ) . '</strong><small>' . esc_html( $t[2] ) . '</small></span></li>'; // phpcs:ignore
	}
	echo '</ul></div></section>';
	return true;
}

function dm_home_block_content() {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return false;
	}
	$page = get_post( (int) get_option( 'page_on_front' ) );
	if ( ! $page || '' === trim( $page->post_content ) ) {
		return false;
	}
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-prose dm-home-content">' . apply_filters( 'the_content', $page->post_content ) . '</div></div></section>'; // phpcs:ignore
	return true;
}

/* -------------------------------------------------------------------------
 * Service page components
 * ---------------------------------------------------------------------- */

function dm_render_process_steps() {
	$steps = dm_parse_pairs( dm_store_opt( 'process_steps' ) );
	if ( ! $steps ) {
		return;
	}
	echo '<ol class="dm-steps2">';
	foreach ( $steps as $i => $s ) {
		echo '<li><span class="dm-steps2-n">' . (int) ( $i + 1 ) . '</span><span><strong>' . esc_html( $s[0] ) . '</strong>' . ( $s[1] ? '<small>' . esc_html( $s[1] ) . '</small>' : '' ) . '</span></li>';
	}
	echo '</ol>';
}

function dm_package_whatsapp_url( $pid, $package ) {
	/* translators: 1 package 2 service */
	$msg = sprintf( __( 'Hi, I want the %1$s package for "%2$s". Can we discuss?', 'digimarket' ), $package, get_the_title( $pid ) );
	return dm_whatsapp_url( $msg, (string) get_post_meta( $pid, '_dm_service_whatsapp', true ) );
}

function dm_render_packages( $pid ) {
	$pk = array_values( array_filter( (array) get_post_meta( $pid, '_dm_packages', true ) ) );
	if ( ! $pk ) {
		return false;
	}
	echo '<section class="dm-psec" id="packages"><h2>' . esc_html__( 'Choose a package', 'digimarket' ) . '</h2><div class="dm-pkgs dm-pkgs-' . count( $pk ) . '">';
	foreach ( $pk as $p ) {
		$wa = dm_package_whatsapp_url( $pid, $p['name'] );
		echo '<div class="dm-pkg' . ( ! empty( $p['popular'] ) ? ' is-popular' : '' ) . '">';
		if ( ! empty( $p['popular'] ) ) {
			echo '<span class="dm-pkg-flag">' . esc_html__( 'Most popular', 'digimarket' ) . '</span>';
		}
		echo '<h3>' . esc_html( $p['name'] ) . '</h3><div class="dm-pkg-price"><small>' . esc_html__( 'Starting', 'digimarket' ) . '</small> ' . esc_html( dm_money_short( $p['price'] ) ) . '</div><ul>';
		foreach ( (array) $p['features'] as $f ) {
			$neg = 0 === strpos( $f, '-' ) || 0 === strpos( $f, '✗' );
			echo '<li class="' . ( $neg ? 'is-no' : 'is-yes' ) . '">' . dm_icon( $neg ? 'close' : 'check', 16 ) . esc_html( ltrim( $f, '-✗ ' ) ) . '</li>'; // phpcs:ignore
		}
		echo '</ul>';
		if ( $wa ) {
			echo '<a class="dm-btn ' . ( ! empty( $p['popular'] ) ? 'dm-btn-wa' : 'dm-btn-outline' ) . ' dm-btn-block" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" data-track="whatsapp" data-ref="' . (int) $pid . '">' . dm_icon_whatsapp( 18 ) . ' ' . esc_html( sprintf( /* translators: %s */ __( 'Choose %s', 'digimarket' ), $p['name'] ) ) . '</a>'; // phpcs:ignore
		}
		echo '</div>';
	}
	echo '</div></section>';
	return true;
}

function dm_render_faq( $pairs, $title = '' ) {
	if ( ! $pairs ) {
		return;
	}
	echo '<section class="dm-psec dm-faq" id="faq"><h2>' . esc_html( $title ? $title : __( 'Frequently asked questions', 'digimarket' ) ) . '</h2>';
	foreach ( $pairs as $i => $p ) {
		echo '<details' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $p[0] ) . dm_icon( 'chev-d', 18 ) . '</summary><div>' . wp_kses_post( wpautop( $p[1] ) ) . '</div></details>'; // phpcs:ignore
	}
	echo '</section>';
}

function dm_render_partner_offers( $limit = 3 ) {
	$ids = dm_product_ids(
		array(
			'posts_per_page' => $limit,
			'meta_key'       => '_dm_affiliate',
			'meta_value'     => 1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);
	if ( ! $ids ) {
		return false;
	}
	echo '<section class="dm-psec dm-partners"><h2>' . esc_html__( 'Recommended hosting for this website', 'digimarket' ) . '</h2><div class="dm-partner-list">';
	foreach ( $ids as $id ) {
		echo '<div class="dm-partner"><a class="dm-partner-img" href="' . esc_url( get_permalink( $id ) ) . '">' . dm_product_thumb( $id, 'thumbnail' ) . '</a><div><strong><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></strong><div>' . wp_kses_post( dm_card_price_html( $id ) ) . '</div></div>' . dm_affiliate_button( $id, 'dm-btn dm-btn-outline dm-btn-sm' ) . '</div>'; // phpcs:ignore
	}
	echo '</div><p class="dm-disclose">' . esc_html( dm_store_opt( 'affiliate_note' ) ) . '</p></section>';
	return true;
}

/**
 * Reviews block (summary + breakdown + list) for any listing.
 */
function dm_render_reviews( $pid, $can_review_form = '' ) {
	$count = (int) get_post_meta( $pid, '_dm_rating_count', true );
	$avg   = (float) get_post_meta( $pid, '_dm_rating_avg', true );
	echo '<section class="dm-psec dm-reviews2" id="reviews"><h2>' . esc_html__( 'Ratings & reviews', 'digimarket' ) . '</h2>';
	if ( $count ) {
		$bd = dm_rating_breakdown( $pid );
		echo '<div class="dm-rsum"><div class="dm-rsum-big"><b>' . esc_html( number_format_i18n( $avg, 1 ) ) . ' ' . dm_icon( 'star', 22 ) . '</b><small>' . esc_html( sprintf( /* translators: %d */ _n( '%d rating', '%d ratings', $count, 'digimarket' ), $count ) ) . '</small></div><ul class="dm-rbars">'; // phpcs:ignore
		foreach ( $bd as $star => $n ) {
			$w = $count ? round( $n / $count * 100 ) : 0;
			echo '<li><span>' . (int) $star . ' ' . dm_icon( 'star', 11 ) . '</span><span class="dm-rbar"><i style="width:' . (int) $w . '%" class="s' . (int) $star . '"></i></span><span>' . (int) $n . '</span></li>'; // phpcs:ignore
		}
		echo '</ul></div>';
	}
	echo $can_review_form; // phpcs:ignore — pre-escaped form markup from the template.
	$reviews = dm_get_reviews( $pid, 30 );
	if ( $reviews ) {
		echo '<ul class="dm-rlist">';
		foreach ( $reviews as $r ) {
			$lines = explode( "\n", (string) $r->comment, 2 );
			$head  = 'client' === ( $r->reviewer_type ?? '' ) && count( $lines ) > 1 ? $lines[0] : '';
			$body  = $head ? $lines[1] : (string) $r->comment;
			echo '<li class="dm-rv"><div class="dm-rv-top"><span class="dm-rchip ' . ( $r->rating >= 4 ? 'is-good' : ( $r->rating >= 3 ? 'is-ok' : 'is-low' ) ) . '">' . (int) $r->rating . ' ' . dm_icon( 'star', 11 ) . '</span>' . ( $head ? '<strong>' . esc_html( $head ) . '</strong>' : '' ) . '</div>'; // phpcs:ignore
			if ( '' !== trim( $body ) ) {
				echo '<p>' . nl2br( esc_html( $body ) ) . '</p>';
			}
			if ( ! empty( $r->photo_id ) ) {
				echo '<div class="dm-rv-photo">' . wp_get_attachment_image( (int) $r->photo_id, 'thumbnail', false, array( 'loading' => 'lazy' ) ) . '</div>';
			}
			echo '<div class="dm-rv-by"><span>' . esc_html( dm_review_author( $r ) ) . '</span><span class="dm-verified">' . dm_icon( 'check', 13 ) . ' ' . esc_html( dm_review_badge( $r ) ) . '</span><span>' . esc_html( mysql2date( get_option( 'date_format' ), $r->created_at ) ) . '</span></div>'; // phpcs:ignore
			if ( $r->seller_reply ) {
				echo '<div class="dm-reply"><strong>' . esc_html( dm_single_seller_mode() ? get_bloginfo( 'name' ) : dm_shop_name( $r->seller_id ) ) . ':</strong> ' . nl2br( esc_html( $r->seller_reply ) ) . '</div>';
			}
			echo '</li>';
		}
		echo '</ul>';
	} elseif ( ! $can_review_form ) {
		echo '<p class="dm-muted">' . ( dm_is_service( $pid ) ? esc_html__( 'No reviews yet. Clients receive a review link after their website is delivered.', 'digimarket' ) : esc_html__( 'No reviews yet. Buyers can review after purchase.', 'digimarket' ) ) . '</p>';
	}
	echo '</section>';
}

/**
 * wa.me share link (no number) with the page title and URL.
 */
function dm_whatsapp_share_url( $url, $title ) {
	return 'https://wa.me/?text=' . rawurlencode( $title . ' – ' . $url );
}

/**
 * Article list row: thumbnail, category chip, title, excerpt, meta, View button.
 */
function dm_article_row( $post, $eager = false ) {
	$post = get_post( $post );
	$url  = get_permalink( $post );
	$cat  = dm_primary_term( $post->ID, 'category' );
	$min  = dm_reading_minutes( $post->post_content );
	echo '<article class="dm-arow"><a class="dm-arow-img" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">';
	if ( has_post_thumbnail( $post ) ) {
		echo get_the_post_thumbnail( $post, 'dm-wide', array( 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 640px) 100vw, 280px' ) );
	} else {
		echo '<span class="dm-arow-ph">' . dm_icon( 'book', 36 ) . '</span>'; // phpcs:ignore
	}
	echo '</a><div class="dm-arow-body">';
	if ( $cat && 'uncategorized' !== $cat->slug ) {
		echo '<a class="dm-arow-cat" href="' . esc_url( dm_articles_url( array( 'topic' => $cat->slug ) ) ) . '">' . esc_html( $cat->name ) . '</a>';
	}
	echo '<h2 class="dm-arow-title"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h2>';
	echo '<p class="dm-arow-ex">' . esc_html( dm_trim_chars( $post->post_excerpt ? $post->post_excerpt : $post->post_content, 160 ) ) . '</p>';
	echo '<div class="dm-arow-meta"><time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( '', $post ) ) . '</time><span>' . esc_html( sprintf( /* translators: %d */ __( '%d min read', 'digimarket' ), $min ) ) . '</span></div>';
	echo '</div><a class="dm-btn dm-btn-primary dm-arow-view" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( /* translators: %s */ __( 'View article: %s', 'digimarket' ), get_the_title( $post ) ) ) . '">' . esc_html__( 'View', 'digimarket' ) . '</a></article>';
}
