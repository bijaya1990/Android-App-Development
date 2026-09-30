<?php
/**
 * Built-in SEO: titles, descriptions, canonical, robots, Open Graph,
 * JSON-LD structured data, robots.txt, sitemaps, redirects, 404 log,
 * IndexNow and front-end performance.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Context helpers
 * ---------------------------------------------------------------------- */

function dm_site_name() {
	return get_bloginfo( 'name' );
}

/**
 * Trim text to a length at a word boundary.
 */
function dm_trim_chars( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	if ( $sp && $sp > $max * 0.6 ) {
		$cut = mb_substr( $cut, 0, $sp );
	}
	return rtrim( $cut, " ,.;:-–" ) . '…';
}

function dm_paged() {
	$p = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	return $p > 1 ? $p : 1;
}

function dm_primary_term( $pid, $tax = 'dm_category' ) {
	$terms = get_the_terms( $pid, $tax );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Auto SEO title for a product when none is set: "Name – Category | Site".
 */
function dm_auto_product_title( $pid ) {
	$site = dm_site_name();
	$name = get_the_title( $pid );
	$term = dm_primary_term( $pid );
	$full = $name . ( $term ? ' – ' . $term->name : '' ) . ' | ' . $site;
	if ( mb_strlen( $full ) > 62 && $term ) {
		$full = $name . ' | ' . $site;
	}
	return $full;
}

function dm_auto_product_desc( $pid ) {
	$post = get_post( $pid );
	$base = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
	$base = dm_trim_chars( $base, 150 );
	if ( mb_strlen( $base ) < 70 ) {
		$type = dm_listing_type( $pid );
		if ( 'service' === $type ) {
			/* translators: 1 name 2 price */
			$base = sprintf( __( '%1$s starting from %2$s. Tell us what you need on WhatsApp — pay only after we agree on the details.', 'digimarket' ), get_the_title( $pid ), dm_money_short( dm_product_price( $pid ) ) );
		} elseif ( 'affiliate' === $type ) {
			/* translators: %s name */
			$base = sprintf( __( '%s — honest review, pros and cons, and the best current deal.', 'digimarket' ), get_the_title( $pid ) );
		} else {
			/* translators: 1 name 2 price */
			$base = sprintf( __( '%1$s for just %2$s. Secure UPI payment and instant download — lifetime access from your account.', 'digimarket' ), get_the_title( $pid ), dm_money_short( dm_product_price( $pid ) ) );
		}
	}
	return dm_trim_chars( $base, 158 );
}

/**
 * Fill empty SEO fields and missing image alt text when a listing goes live.
 */
function dm_seo_autofill( $pid ) {
	if ( '' === trim( (string) get_post_meta( $pid, '_dm_meta_title', true ) ) ) {
		update_post_meta( $pid, '_dm_meta_title', dm_auto_product_title( $pid ) );
	}
	if ( '' === trim( (string) get_post_meta( $pid, '_dm_meta_desc', true ) ) ) {
		update_post_meta( $pid, '_dm_meta_desc', dm_auto_product_desc( $pid ) );
	}
	$imgs = array_filter( array_merge( array( get_post_thumbnail_id( $pid ) ), array_map( 'absint', (array) get_post_meta( $pid, '_dm_gallery', true ) ) ) );
	foreach ( array_values( $imgs ) as $i => $img ) {
		if ( '' === (string) get_post_meta( $img, '_wp_attachment_image_alt', true ) ) {
			update_post_meta( $img, '_wp_attachment_image_alt', get_the_title( $pid ) . ( $i ? ' – ' . sprintf( /* translators: %d */ __( 'preview %d', 'digimarket' ), $i ) : '' ) );
		}
	}
}

/**
 * Breadcrumb trail for the current view: list of [label, url].
 */
function dm_breadcrumb_trail() {
	$trail = array( array( __( 'Home', 'digimarket' ), home_url( '/' ) ) );
	if ( is_singular( 'dm_product' ) ) {
		$pid  = get_queried_object_id();
		$term = dm_primary_term( $pid );
		if ( $term ) {
			foreach ( array_reverse( get_ancestors( $term->term_id, 'dm_category' ) ) as $anc ) {
				$a       = get_term( $anc, 'dm_category' );
				$trail[] = array( $a->name, get_term_link( $a ) );
			}
			$trail[] = array( $term->name, get_term_link( $term ) );
		}
		$trail[] = array( get_the_title( $pid ), get_permalink( $pid ) );
	} elseif ( is_tax( array( 'dm_category', 'dm_tag' ) ) ) {
		$term = get_queried_object();
		$trail[] = array( __( 'All products', 'digimarket' ), dm_products_url() );
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) ) as $anc ) {
			$a       = get_term( $anc, $term->taxonomy );
			$trail[] = array( $a->name, get_term_link( $a ) );
		}
		$trail[] = array( $term->name, get_term_link( $term ) );
	} elseif ( is_post_type_archive( 'dm_product' ) ) {
		$trail[] = array( __( 'All products', 'digimarket' ), dm_products_url() );
	} elseif ( is_singular( 'post' ) ) {
		$trail[] = array( dm_store_opt( 'articles_label' ), dm_articles_url() );
		$cat     = dm_primary_term( get_queried_object_id(), 'category' );
		if ( $cat && 'uncategorized' !== $cat->slug ) {
			$trail[] = array( $cat->name, add_query_arg( 'topic', $cat->slug, dm_articles_url() ) );
		}
		$trail[] = array( get_the_title(), get_permalink() );
	} elseif ( is_singular( 'dm_portfolio' ) ) {
		$trail[] = array( __( 'Sample websites', 'digimarket' ), get_post_type_archive_link( 'dm_portfolio' ) );
		$trail[] = array( get_the_title(), get_permalink() );
	} elseif ( is_post_type_archive( 'dm_portfolio' ) ) {
		$trail[] = array( __( 'Sample websites', 'digimarket' ), get_post_type_archive_link( 'dm_portfolio' ) );
	} elseif ( is_page() ) {
		$trail[] = array( get_the_title(), get_permalink() );
	} elseif ( 'articles' === dm_route() ) {
		$trail[] = array( dm_store_opt( 'articles_label' ), dm_articles_url() );
	} elseif ( 'sale' === dm_route() ) {
		$trail[] = array( __( 'Sale', 'digimarket' ), dm_url( 'sale' ) );
	} elseif ( 'website-services' === dm_route() ) {
		$trail[] = array( __( 'Website services', 'digimarket' ), dm_url( 'website-services' ) );
	} elseif ( 'wordpress-themes' === dm_route() ) {
		$trail[] = array( __( 'WordPress themes', 'digimarket' ), dm_url( 'wordpress-themes' ) );
	}
	return $trail;
}

function dm_render_breadcrumbs() {
	$trail = dm_breadcrumb_trail();
	if ( count( $trail ) < 2 ) {
		return;
	}
	echo '<nav class="dm-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'digimarket' ) . '"><ol>';
	$last = count( $trail ) - 1;
	foreach ( $trail as $i => $t ) {
		echo '<li>' . ( $i === $last ? '<span aria-current="page">' . esc_html( $t[0] ) . '</span>' : '<a href="' . esc_url( $t[1] ) . '">' . esc_html( $t[0] ) . '</a>' ) . '</li>';
	}
	echo '</ol></nav>';
}

/* -------------------------------------------------------------------------
 * Title, description, canonical, robots
 * ---------------------------------------------------------------------- */

add_filter( 'pre_get_document_title', 'dm_seo_title', 20 );
// Escape once for the <title> tag (existing entities are not double-encoded).
add_filter( 'pre_get_document_title', function ( $t ) {
	return '' === (string) $t ? $t : esc_html( $t );
}, 21 );
function dm_seo_title( $title = '' ) {
	$site = dm_site_name();
	$pg   = dm_paged() > 1 ? ' – ' . sprintf( /* translators: %d */ __( 'Page %d', 'digimarket' ), dm_paged() ) : '';
	if ( is_front_page() ) {
		$t = dm_store_opt( 'seo_home_title' );
		return $t ? $t : $site . ( get_bloginfo( 'description' ) ? ' – ' . get_bloginfo( 'description' ) : '' );
	}
	if ( is_singular( array( 'dm_product', 'post', 'page', 'dm_portfolio' ) ) ) {
		$pid = get_queried_object_id();
		$mt  = trim( (string) get_post_meta( $pid, '_dm_meta_title', true ) );
		if ( $mt ) {
			return $mt;
		}
		if ( is_singular( 'dm_product' ) ) {
			return dm_auto_product_title( $pid );
		}
		if ( is_singular( 'dm_portfolio' ) ) {
			return get_the_title( $pid ) . ' – ' . __( 'Website Portfolio', 'digimarket' ) . ' | ' . $site;
		}
		return get_the_title( $pid ) . ' | ' . $site;
	}
	if ( is_tax( array( 'dm_category', 'dm_tag' ) ) ) {
		$term = get_queried_object();
		$mt   = trim( (string) get_term_meta( $term->term_id, 'dm_seo_title', true ) );
		/* translators: %s term */
		return ( $mt ? $mt : sprintf( __( '%s – Buy Online', 'digimarket' ), $term->name ) . ' | ' . $site ) . $pg;
	}
	if ( is_post_type_archive( 'dm_product' ) && ! is_search() ) {
		return __( 'All Products', 'digimarket' ) . $pg . ' | ' . $site;
	}
	if ( is_post_type_archive( 'dm_portfolio' ) ) {
		return __( 'Our Work – Website Portfolio', 'digimarket' ) . $pg . ' | ' . $site;
	}
	if ( is_search() ) {
		/* translators: %s query */
		return sprintf( __( 'Search results for “%s”', 'digimarket' ), get_search_query() ) . ' | ' . $site;
	}
	if ( is_404() ) {
		return __( 'Page not found', 'digimarket' ) . ' | ' . $site;
	}
	$route = dm_route();
	if ( $route ) {
		$names = array(
			'cart'           => __( 'Cart', 'digimarket' ),
			'checkout'       => __( 'Checkout', 'digimarket' ),
			'order-received' => __( 'Order confirmed', 'digimarket' ),
			'account'        => __( 'My Account', 'digimarket' ),
			'dashboard'      => __( 'Seller Dashboard', 'digimarket' ),
			'login'          => __( 'Log in', 'digimarket' ),
			'register'       => __( 'Create account', 'digimarket' ),
			'forgot'         => __( 'Forgot password', 'digimarket' ),
			'reset'          => __( 'Reset password', 'digimarket' ),
			'verify'         => __( 'Verify email', 'digimarket' ),
			'sell'           => __( 'Start selling', 'digimarket' ),
			'shops'          => __( 'All shops', 'digimarket' ),
			'invoice'        => __( 'Invoice', 'digimarket' ),
			'review'         => __( 'Write a review', 'digimarket' ),
			'sale'           => __( 'Sale – Best Deals on Notes, Templates & More', 'digimarket' ),
			'articles'       => dm_store_opt( 'articles_label' ) . ' – ' . __( 'Guides & Tips', 'digimarket' ),
		);
		if ( dm_is_landing() ) {
			return dm_lp_seo_title( $route ) . ' | ' . $site;
		}
		if ( isset( $names[ $route ] ) ) {
			return $names[ $route ] . $pg . ' | ' . $site;
		}
	}
	if ( get_query_var( 'dm_store' ) ) {
		$sid = dm_get_seller_by_slug( get_query_var( 'dm_store' ) );
		if ( $sid ) {
			return dm_shop_name( $sid ) . ' | ' . $site;
		}
	}
	return $title;
}

function dm_seo_description() {
	if ( is_front_page() ) {
		$d = dm_store_opt( 'seo_home_desc' );
		$d = $d ? $d : get_bloginfo( 'description' );
		if ( mb_strlen( (string) $d ) < 50 ) {
			$names = wp_list_pluck( array_slice( dm_categories( array( 'parent' => 0, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) ), 0, 4 ), 'name' );
			/* translators: 1 site 2 categories */
			$d = sprintf( __( 'Shop %2$s at %1$s. Secure UPI payment, instant download and WhatsApp support.', 'digimarket' ), dm_site_name(), $names ? implode( ', ', $names ) : __( 'digital products and website services', 'digimarket' ) );
		}
		return $d;
	}
	if ( is_singular() ) {
		$pid = get_queried_object_id();
		$d   = trim( (string) get_post_meta( $pid, '_dm_meta_desc', true ) );
		if ( $d ) {
			return $d;
		}
		if ( is_singular( 'dm_product' ) ) {
			return dm_auto_product_desc( $pid );
		}
		$p = get_post( $pid );
		return dm_trim_chars( $p->post_excerpt ? $p->post_excerpt : $p->post_content, 158 );
	}
	if ( is_tax( array( 'dm_category', 'dm_tag' ) ) ) {
		$term = get_queried_object();
		$d    = trim( (string) get_term_meta( $term->term_id, 'dm_seo_desc', true ) );
		if ( ! $d ) {
			$d = get_term_meta( $term->term_id, 'dm_intro', true ) ? get_term_meta( $term->term_id, 'dm_intro', true ) : $term->description;
		}
		if ( ! $d ) {
			/* translators: 1 count 2 term 3 site */
			$d = sprintf( __( 'Browse %1$d %2$s on %3$s. Secure UPI payment, instant download and verified reviews.', 'digimarket' ), (int) $term->count, $term->name, dm_site_name() );
		}
		return dm_trim_chars( $d, 158 );
	}
	if ( dm_is_landing() ) {
		return dm_lp_seo_desc( dm_route() );
	}
	if ( 'sale' === dm_route() ) {
		return __( 'Today’s best discounts on study notes, resume templates, design packs and kids worksheets. Limited-time prices — instant download after UPI payment.', 'digimarket' );
	}
	if ( 'articles' === dm_route() ) {
		return __( 'Helpful guides on websites for schools, committees and small businesses, plus study and career tips.', 'digimarket' );
	}
	if ( is_post_type_archive( 'dm_portfolio' ) ) {
		return __( 'Sample website designs for schools, puja committees, shops and cafés — see what your website can look like, with clear starting prices.', 'digimarket' );
	}
	if ( is_post_type_archive( 'dm_product' ) ) {
		/* translators: %s site */
		return sprintf( __( 'All digital products and website services on %s — notes, templates, kids worksheets and more with instant download.', 'digimarket' ), dm_site_name() );
	}
	return '';
}

function dm_seo_canonical() {
	$pg = dm_paged();
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink( get_queried_object_id() );
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) ? ( is_array( get_query_var( 'post_type' ) ) ? get_query_var( 'post_type' )[0] : get_query_var( 'post_type' ) ) : 'dm_product' );
	} elseif ( 'sale' === dm_route() || dm_is_landing() ) {
		$url = dm_url( dm_route() );
	} elseif ( 'articles' === dm_route() ) {
		$url = dm_articles_url();
	} else {
		return '';
	}
	if ( is_wp_error( $url ) || ! $url ) {
		return '';
	}
	return $pg > 1 ? trailingslashit( $url ) . 'page/' . $pg . '/' : $url;
}

/**
 * Pages that must stay out of Google.
 */
function dm_seo_noindex() {
	$route = dm_route();
	if ( $route && ! in_array( $route, array( 'sale', 'articles', 'website-services', 'wordpress-themes' ), true ) ) {
		return true;
	}
	if ( is_search() || is_404() || is_author() || is_date() || is_attachment() ) {
		return true;
	}
	foreach ( array( 'sort', 'min_price', 'max_price', 'rating', 'type', 'age', 'pcat', 'ptag', 'topic', 'q', 's', 'coupon', 'ref', 'replytocom' ) as $p ) {
		if ( isset( $_GET[ $p ] ) ) { // phpcs:ignore
			return true;
		}
	}
	if ( is_tax( 'dm_tag' ) || is_tag() ) {
		$t = get_queried_object();
		return (int) $t->count < 5;
	}
	if ( is_tax( 'dm_category' ) ) {
		return 0 === (int) get_queried_object()->count;
	}
	if ( get_query_var( 'dm_store' ) && dm_single_seller_mode() ) {
		return true;
	}
	return false;
}

add_filter( 'wp_robots', function ( $robots ) {
	if ( dm_seo_noindex() ) {
		unset( $robots['index'], $robots['max-image-preview'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;
	} elseif ( get_option( 'blog_public' ) ) {
		$robots['max-image-preview'] = 'large';
		$robots['max-snippet']       = '-1';
	}
	return $robots;
} );

/* Our own canonical tag everywhere (WordPress' only covers single posts). */
remove_action( 'wp_head', 'rel_canonical' );

add_action( 'wp_head', 'dm_seo_head', 2 );
function dm_seo_head() {
	$desc  = dm_seo_description();
	$canon = dm_seo_noindex() ? '' : dm_seo_canonical();
	$title = wp_get_document_title();
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( dm_trim_chars( $desc, 170 ) ) . '">' . "\n";
	}
	if ( $canon ) {
		echo '<link rel="canonical" href="' . esc_url( $canon ) . '">' . "\n";
	}
	$gsc  = dm_store_opt( 'gsc_verify' );
	$bing = dm_store_opt( 'bing_verify' );
	if ( $gsc && is_front_page() ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
	}
	if ( $bing && is_front_page() ) {
		echo '<meta name="msvalidate.01" content="' . esc_attr( $bing ) . '">' . "\n";
	}

	// Open Graph / Twitter (these make WhatsApp & Facebook shares look right).
	$img  = dm_seo_image();
	$type = is_singular( 'post' ) ? 'article' : ( is_singular( 'dm_product' ) ? 'product' : 'website' );
	$og   = array(
		'og:locale'      => 'en_IN',
		'og:type'        => $type,
		'og:site_name'   => dm_site_name(),
		'og:title'       => $title,
		'og:description' => $desc ? dm_trim_chars( $desc, 200 ) : '',
		'og:url'         => $canon ? $canon : ( is_singular() ? get_permalink() : home_url( '/' ) ),
	);
	if ( $img ) {
		$og['og:image']        = $img[0];
		$og['og:image:width']  = $img[1];
		$og['og:image:height'] = $img[2];
		$og['og:image:alt']    = $title;
	}
	if ( is_singular( 'dm_product' ) && ! dm_is_affiliate( get_queried_object_id() ) ) {
		$og['product:price:amount']   = number_format( dm_product_price( get_queried_object_id() ), 2, '.', '' );
		$og['product:price:currency'] = dm_opt( 'currency_code', 'INR' );
	}
	if ( is_singular( 'post' ) ) {
		$og['article:published_time'] = get_the_date( 'c' );
		$og['article:modified_time']  = get_the_modified_date( 'c' );
	}
	foreach ( $og as $k => $v ) {
		if ( '' !== (string) $v ) {
			echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}
	echo '<meta name="twitter:card" content="' . ( $img ? 'summary_large_image' : 'summary' ) . '">' . "\n";

	$graph = dm_seo_schema();
	if ( $graph ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
}

/**
 * Share image [url, w, h] for the current page.
 */
function dm_seo_image() {
	$id = 0;
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$id = get_post_thumbnail_id( get_queried_object_id() );
	} elseif ( is_tax( 'dm_category' ) ) {
		$id = (int) get_term_meta( get_queried_object_id(), 'dm_icon_img', true );
	} elseif ( dm_is_landing() ) {
		$id = 'website-services' === dm_route() ? (int) dm_store_opt( 'lp_svc_wall' ) : 0;
		if ( ! $id ) {
			$ids = 'website-services' === dm_route() ? dm_lp_service_ids( 1 ) : dm_lp_theme_ids( 1 );
			$id  = $ids ? (int) get_post_thumbnail_id( $ids[0] ) : 0;
		}
	}
	if ( ! $id ) {
		$id = (int) dm_store_opt( 'seo_default_image' );
	}
	if ( ! $id ) {
		return null;
	}
	$src = wp_get_attachment_image_src( $id, 'dm-og' );
	if ( ! $src || $src[1] < 600 ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
	}
	return $src ? array( $src[0], $src[1], $src[2] ) : null;
}

add_action( 'after_setup_theme', function () {
	add_image_size( 'dm-og', 1200, 630, true );
}, 20 );

/* -------------------------------------------------------------------------
 * Structured data
 * ---------------------------------------------------------------------- */

function dm_org_logo_url() {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$u = wp_get_attachment_image_url( $id, 'full' );
		if ( $u ) {
			return $u;
		}
	}
	$icon = get_site_icon_url( 512 );
	return $icon ? $icon : DM_URI . '/assets/img/pikacart-icon.svg';
}

function dm_seo_schema() {
	$home  = home_url( '/' );
	$org   = array(
		'@type' => 'Organization',
		'@id'   => $home . '#org',
		'name'  => dm_site_name(),
		'url'   => $home,
		'logo'  => dm_org_logo_url(),
	);
	$same  = array_values( array_filter( array_map( 'esc_url_raw', dm_lines( dm_store_opt( 'social_links' ) ) ) ) );
	if ( $same ) {
		$org['sameAs'] = $same;
	}
	$addr = (string) dm_store_opt( 'business_address' );
	if ( $addr ) {
		$org['address'] = array_filter(
			array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => $addr,
				'postalCode'     => preg_match( '/\b(\d{6})\b/', $addr, $m ) ? $m[1] : null,
				'addressRegion'  => false !== stripos( $addr, 'odisha' ) ? 'Odisha' : null,
				'addressCountry' => 'IN',
			)
		);
	}
	if ( dm_store_opt( 'business_legal_name' ) ) {
		$org['legalName'] = dm_store_opt( 'business_legal_name' );
	}
	$phone = dm_store_opt( 'business_phone' ) ? dm_store_opt( 'business_phone' ) : dm_opt( 'whatsapp_number' );
	$email = dm_store_opt( 'business_email' ) ? dm_store_opt( 'business_email' ) : dm_opt( 'support_email' );
	if ( $phone || $email ) {
		$org['contactPoint'] = array_filter(
			array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'customer support',
				'telephone'         => $phone ? '+' . ltrim( preg_replace( '/[^0-9]/', '', $phone ), '+' ) : null,
				'email'             => $email ? $email : null,
				'areaServed'        => 'IN',
				'availableLanguage' => array( 'en', 'hi' ),
			)
		);
	}
	$graph = array(
		$org,
		array(
			'@type'     => 'WebSite',
			'@id'       => $home . '#website',
			'url'       => $home,
			'name'      => dm_site_name(),
			'publisher' => array( '@id' => $home . '#org' ),
			'inLanguage' => 'en-IN',
		),
	);

	$trail = dm_breadcrumb_trail();
	if ( count( $trail ) > 1 && ! dm_seo_noindex() ) {
		$items = array();
		foreach ( $trail as $i => $t ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( $t[0] ), 'item' => $t[1] );
		}
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $items );
	}

	if ( is_singular( 'dm_product' ) ) {
		$pid  = get_queried_object_id();
		$type = dm_listing_type( $pid );
		$imgs = array();
		foreach ( array_filter( array_merge( array( get_post_thumbnail_id( $pid ) ), array_map( 'absint', (array) get_post_meta( $pid, '_dm_gallery', true ) ) ) ) as $im ) {
			$u = wp_get_attachment_image_url( $im, 'full' );
			if ( $u ) {
				$imgs[] = $u;
			}
		}
		$count   = (int) get_post_meta( $pid, '_dm_rating_count', true );
		$rating  = $count ? array( '@type' => 'AggregateRating', 'ratingValue' => (float) get_post_meta( $pid, '_dm_rating_avg', true ), 'reviewCount' => $count, 'bestRating' => 5, 'worstRating' => 1 ) : null;
		$reviews = array();
		foreach ( dm_get_reviews( $pid, 5 ) as $r ) {
			$reviews[] = array(
				'@type'         => 'Review',
				'author'        => array( '@type' => 'Person', 'name' => dm_review_author( $r ) ),
				'datePublished' => mysql2date( 'Y-m-d', $r->created_at ),
				'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => (int) $r->rating, 'bestRating' => 5 ),
				'reviewBody'    => dm_trim_chars( (string) $r->comment, 300 ),
			);
		}
		$desc = dm_seo_description();
		if ( 'service' === $type ) {
			$node = array(
				'@type'       => 'Service',
				'@id'         => get_permalink( $pid ) . '#service',
				'name'        => get_the_title( $pid ),
				'description' => $desc,
				'url'         => get_permalink( $pid ),
				'provider'    => array( '@id' => $home . '#org' ),
				'areaServed'  => get_post_meta( $pid, '_dm_area_served', true ) ? get_post_meta( $pid, '_dm_area_served', true ) : dm_store_opt( 'area_served' ),
				'offers'      => array(
					'@type'              => 'Offer',
					'priceCurrency'      => dm_opt( 'currency_code', 'INR' ),
					'priceSpecification' => array( '@type' => 'PriceSpecification', 'minPrice' => dm_product_price( $pid ), 'priceCurrency' => dm_opt( 'currency_code', 'INR' ) ),
					'url'                => get_permalink( $pid ),
				),
			);
			$term = dm_primary_term( $pid );
			if ( $term ) {
				$node['serviceType'] = $term->name;
			}
			if ( $imgs ) {
				$node['image'] = $imgs;
			}
			$pk = array_filter( (array) get_post_meta( $pid, '_dm_packages', true ) );
			if ( $pk ) {
				$offers = array();
				foreach ( $pk as $p ) {
					$offers[] = array( '@type' => 'Offer', 'name' => $p['name'], 'priceSpecification' => array( '@type' => 'PriceSpecification', 'minPrice' => (float) $p['price'], 'priceCurrency' => dm_opt( 'currency_code', 'INR' ) ) );
				}
				$node['hasOfferCatalog'] = array( '@type' => 'OfferCatalog', 'name' => get_the_title( $pid ), 'itemListElement' => $offers );
			}
			if ( $rating ) {
				$node['aggregateRating'] = $rating;
				$node['review']          = $reviews;
			}
			$graph[] = $node;
			$faq     = dm_service_faq( $pid );
			if ( $faq ) {
				$graph[] = dm_faq_schema( $faq );
			}
		} elseif ( 'digital' === $type ) {
			$offer = array(
				'@type'         => 'Offer',
				'price'         => number_format( dm_product_price( $pid ), 2, '.', '' ),
				'priceCurrency' => dm_opt( 'currency_code', 'INR' ),
				'availability'  => dm_can_purchase( $pid )[0] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'url'           => get_permalink( $pid ),
				'seller'        => array( '@id' => $home . '#org' ),
			);
			$ends = dm_sale_ends_ts( $pid );
			if ( $ends ) {
				$offer['priceValidUntil'] = wp_date( 'Y-m-d', $ends );
			}
			$node = array(
				'@type'       => 'Product',
				'@id'         => get_permalink( $pid ) . '#product',
				'name'        => get_the_title( $pid ),
				'description' => $desc,
				'sku'         => 'PK-' . $pid,
				'brand'       => array( '@type' => 'Brand', 'name' => dm_single_seller_mode() ? dm_site_name() : dm_shop_name( get_post_field( 'post_author', $pid ) ) ),
				'offers'      => $offer,
			);
			if ( $imgs ) {
				$node['image'] = $imgs;
			}
			$term = dm_primary_term( $pid );
			if ( $term ) {
				$node['category'] = $term->name;
			}
			if ( $rating ) {
				$node['aggregateRating'] = $rating;
				$node['review']          = $reviews;
			}
			$graph[] = $node;
		}
	} elseif ( is_tax( 'dm_category' ) && ! dm_seo_noindex() ) {
		global $wp_query;
		$list = array();
		foreach ( $wp_query->posts as $i => $p ) {
			$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $p ) );
		}
		$graph[] = array(
			'@type'      => 'CollectionPage',
			'@id'        => dm_seo_canonical() . '#page',
			'url'        => dm_seo_canonical(),
			'name'       => single_term_title( '', false ),
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'mainEntity' => array( '@type' => 'ItemList', 'itemListElement' => $list ),
		);
		$faq = dm_parse_pairs( get_term_meta( get_queried_object_id(), 'dm_faq', true ) );
		if ( $faq ) {
			$graph[] = dm_faq_schema( $faq );
		}
	} elseif ( is_singular( 'post' ) ) {
		$pid     = get_queried_object_id();
		$img     = get_the_post_thumbnail_url( $pid, 'full' );
		$author  = dm_store_opt( 'owner_name' ) ? dm_store_opt( 'owner_name' ) : get_the_author_meta( 'display_name', get_post_field( 'post_author', $pid ) );
		$graph[] = array_filter(
			array(
				'@type'            => 'BlogPosting',
				'@id'              => get_permalink( $pid ) . '#article',
				'headline'         => get_the_title( $pid ),
				'description'      => dm_seo_description(),
				'image'            => $img ? array( $img ) : null,
				'datePublished'    => get_the_date( 'c', $pid ),
				'dateModified'     => get_the_modified_date( 'c', $pid ),
				'author'           => array( '@type' => 'Person', 'name' => $author, 'url' => dm_legal_url( 'about' ) ),
				'publisher'        => array( '@id' => $home . '#org' ),
				'mainEntityOfPage' => get_permalink( $pid ),
				'inLanguage'       => 'en-IN',
				'wordCount'        => str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $pid ) ) ),
			)
		);
	} elseif ( dm_is_landing() ) {
		$graph = array_merge( $graph, dm_lp_schema( dm_route() ) );
	} elseif ( 'articles' === dm_route() ) {
		$graph[] = array( '@type' => 'CollectionPage', 'url' => dm_articles_url(), 'name' => dm_store_opt( 'articles_label' ), 'isPartOf' => array( '@id' => $home . '#website' ) );
	} elseif ( is_singular( 'dm_portfolio' ) ) {
		$pid     = get_queried_object_id();
		$graph[] = array_filter(
			array(
				'@type'      => 'CreativeWork',
				'name'       => get_the_title( $pid ),
				'url'        => get_permalink( $pid ),
				'image'      => get_the_post_thumbnail_url( $pid, 'full' ) ? get_the_post_thumbnail_url( $pid, 'full' ) : null,
				'about'      => get_post_meta( $pid, '_dm_pf_client', true ),
				'creator'    => array( '@id' => $home . '#org' ),
				'sameAs'     => get_post_meta( $pid, '_dm_pf_url', true ) ? get_post_meta( $pid, '_dm_pf_url', true ) : null,
			)
		);
	}
	return $graph;
}

function dm_faq_schema( $pairs ) {
	$q = array();
	foreach ( $pairs as $p ) {
		if ( '' === $p[1] ) {
			continue;
		}
		$q[] = array( '@type' => 'Question', 'name' => $p[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $p[1] ) );
	}
	return array( '@type' => 'FAQPage', 'mainEntity' => $q );
}

function dm_service_faq( $pid ) {
	$own = dm_parse_pairs( get_post_meta( $pid, '_dm_faq', true ) );
	return $own ? $own : dm_parse_pairs( dm_store_opt( 'default_faq' ) );
}

/* -------------------------------------------------------------------------
 * robots.txt + sitemaps
 * ---------------------------------------------------------------------- */

add_filter( 'robots_txt', function ( $out, $public ) {
	if ( ! $public ) {
		return $out;
	}
	$path  = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$path  = $path ? $path : '/';
	$lines = array( 'cart/', 'checkout/', 'account/', 'dashboard/', 'download/', 'invoice/', 'go/', 'review/', 'order-received/', 'login/', 'register/', '?s=', '*?sort=', '*?coupon=' );
	$add   = '';
	foreach ( $lines as $l ) {
		$add .= 'Disallow: ' . $path . $l . "\n";
	}
	return str_replace( "Disallow: /wp-admin/\n", "Disallow: /wp-admin/\n" . $add, $out );
}, 10, 2 );

add_filter( 'wp_sitemaps_post_types', function ( $types ) {
	unset( $types['attachment'], $types['dm_banner'], $types['dm_testimonial'] );
	return $types;
} );
add_filter( 'wp_sitemaps_taxonomies', function ( $tax ) {
	unset( $tax['dm_tag'], $tax['post_tag'], $tax['post_format'] );
	return $tax;
} );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies_query_args', function ( $args ) {
	$args['hide_empty'] = true;
	return $args;
} );

/* -------------------------------------------------------------------------
 * Redirects, 404 log, thin archives
 * ---------------------------------------------------------------------- */

function dm_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return '/' . trim( rawurldecode( $path ), '/' ) . ( '/' === substr( $path, -1 ) || '' === trim( $path, '/' ) ? '/' : '' );
}

add_action( 'template_redirect', 'dm_seo_redirects', 0 );
function dm_seo_redirects() {
	// Author & date archives add nothing for a single shop.
	if ( is_author() || is_date() ) {
		wp_safe_redirect( is_date() ? dm_articles_url() : home_url( '/' ), 301 );
		exit;
	}
	if ( is_attachment() ) {
		$parent = wp_get_post_parent_id( get_queried_object_id() );
		wp_safe_redirect( $parent ? get_permalink( $parent ) : home_url( '/' ), 301 );
		exit;
	}
	if ( ! is_404() ) {
		return;
	}
	global $wpdb;
	$path = dm_request_path();
	$row  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'redirects' ) . ' WHERE source = %s OR source = %s LIMIT 1', $path, untrailingslashit( $path ) ) );
	if ( $row && $row->target ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'redirects' ) . ' SET hits = hits + 1 WHERE id = %d', $row->id ) );
		wp_redirect( 0 === strpos( $row->target, '/' ) ? home_url( $row->target ) : $row->target, 301 ); // phpcs:ignore
		exit;
	}
	// A product that was unpublished or deleted → its category (keeps the link value).
	if ( preg_match( '#/product/([^/]+)/?$#', $path, $m ) ) {
		$p = get_page_by_path( sanitize_title( $m[1] ), OBJECT, 'dm_product' );
		if ( $p ) {
			$term = dm_primary_term( $p->ID );
			wp_safe_redirect( $term ? get_term_link( $term ) : dm_products_url(), 301 );
			exit;
		}
	}
	// Log the miss (skip static assets and scanners).
	if ( preg_match( '#\.(css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|txt|xml|php)$|/wp-(content|includes|admin)/#i', $path ) ) {
		return;
	}
	$ref = isset( $_SERVER['HTTP_REFERER'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, 255 ) : '';
	$id  = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'not_found' ) . ' WHERE path = %s', substr( $path, 0, 255 ) ) );
	if ( $id ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . dm_table( 'not_found' ) . ' SET hits = hits + 1, last_seen = %s, referrer = IF(%s <> "", %s, referrer) WHERE id = %d', dm_now(), $ref, $ref, $id ) );
	} else {
		$wpdb->insert( dm_table( 'not_found' ), array( 'path' => substr( $path, 0, 255 ), 'referrer' => $ref, 'hits' => 1, 'last_seen' => dm_now() ) );
		if ( 0 === wp_rand( 0, 50 ) ) {
			$wpdb->query( 'DELETE FROM ' . dm_table( 'not_found' ) . ' WHERE last_seen < DATE_SUB(NOW(), INTERVAL 90 DAY)' ); // phpcs:ignore
		}
	}
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'dm-marketplace', __( 'SEO health', 'digimarket' ), __( 'SEO health', 'digimarket' ), 'dm_view_marketplace', 'dm-seo', 'dm_admin_seo' );
}, 23 );

function dm_admin_seo() {
	global $wpdb;
	dm_admin_header( __( 'SEO health', 'digimarket' ) );
	$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'checks'; // phpcs:ignore
	echo '<h2 class="nav-tab-wrapper">';
	foreach ( array( 'checks' => __( 'Listing checks', 'digimarket' ), 'redirects' => __( 'Redirects & 404s', 'digimarket' ) ) as $k => $l ) {
		echo '<a class="nav-tab' . ( $tab === $k ? ' nav-tab-active' : '' ) . '" href="' . esc_url( dm_admin_url( 'dm-seo', array( 'tab' => $k ) ) ) . '">' . esc_html( $l ) . '</a>';
	}
	echo '</h2>';
	if ( 'redirects' === $tab ) {
		dm_admin_form_open( 'redirect_add' );
		echo '<p><input type="text" name="source" placeholder="/old-page/" required> → <input type="text" name="target" class="regular-text" placeholder="/new-page/ or https://…" required> <button class="button button-primary">' . esc_html__( 'Add 301 redirect', 'digimarket' ) . '</button></p></form>';
		echo '<h3>' . esc_html__( 'Redirects', 'digimarket' ) . '</h3><table class="widefat striped"><thead><tr><th>' . esc_html__( 'From', 'digimarket' ) . '</th><th>' . esc_html__( 'To', 'digimarket' ) . '</th><th>' . esc_html__( 'Hits', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'redirects' ) . ' ORDER BY id DESC LIMIT 300' ) as $r ) { // phpcs:ignore
			echo '<tr><td><code>' . esc_html( $r->source ) . '</code></td><td>' . esc_html( $r->target ) . '</td><td>' . (int) $r->hits . '</td><td><a href="' . esc_url( dm_admin_action_url( 'redirect_delete', array( 'id' => $r->id ) ) ) . '">' . esc_html__( 'Delete', 'digimarket' ) . '</a></td></tr>';
		}
		echo '</tbody></table><h3>' . esc_html__( 'Pages not found (last 90 days)', 'digimarket' ) . '</h3><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Path', 'digimarket' ) . '</th><th>' . esc_html__( 'Hits', 'digimarket' ) . '</th><th>' . esc_html__( 'Last seen', 'digimarket' ) . '</th><th>' . esc_html__( 'From', 'digimarket' ) . '</th><th>' . esc_html__( 'Fix', 'digimarket' ) . '</th></tr></thead><tbody>';
		foreach ( $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'not_found' ) . ' ORDER BY hits DESC LIMIT 200' ) as $r ) { // phpcs:ignore
			echo '<tr><td><code>' . esc_html( $r->path ) . '</code></td><td>' . (int) $r->hits . '</td><td>' . esc_html( $r->last_seen ) . '</td><td>' . esc_html( $r->referrer ) . '</td><td>';
			dm_admin_form_open( 'redirect_add' );
			echo '<input type="hidden" name="source" value="' . esc_attr( $r->path ) . '"><input type="text" name="target" placeholder="/new-page/" required> <button class="button button-small">' . esc_html__( 'Redirect', 'digimarket' ) . '</button></form></td></tr>';
		}
		echo '</tbody></table></div>';
		return;
	}
	echo '<p>' . esc_html__( 'Every published listing and article, checked against the rules in the build spec. Fix red items first.', 'digimarket' ) . '</p>';
	$posts = get_posts( array( 'post_type' => array( 'dm_product', 'post' ), 'post_status' => 'publish', 'numberposts' => 500 ) );
	$seen  = array( 't' => array(), 'd' => array() );
	foreach ( $posts as $p ) {
		$seen['t'][ strtolower( (string) get_post_meta( $p->ID, '_dm_meta_title', true ) ) ][] = $p->ID;
		$seen['d'][ strtolower( (string) get_post_meta( $p->ID, '_dm_meta_desc', true ) ) ][]  = $p->ID;
	}
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Page', 'digimarket' ) . '</th><th>' . esc_html__( 'Issues', 'digimarket' ) . '</th></tr></thead><tbody>';
	$ok = 0;
	foreach ( $posts as $p ) {
		$issues = array();
		$t      = (string) get_post_meta( $p->ID, '_dm_meta_title', true );
		$d      = (string) get_post_meta( $p->ID, '_dm_meta_desc', true );
		$kw     = (string) get_post_meta( $p->ID, '_dm_focus_kw', true );
		$words  = str_word_count( wp_strip_all_tags( $p->post_content . ' ' . $p->post_excerpt ) );
		if ( '' === $t ) {
			$issues[] = __( 'No SEO title (auto-generated one is used)', 'digimarket' );
		} elseif ( mb_strlen( $t ) < 30 || mb_strlen( $t ) > 65 ) {
			/* translators: %d */
			$issues[] = sprintf( __( 'SEO title is %d characters (aim 50–60)', 'digimarket' ), mb_strlen( $t ) );
		}
		if ( '' === $d ) {
			$issues[] = __( 'No meta description', 'digimarket' );
		} elseif ( mb_strlen( $d ) < 100 || mb_strlen( $d ) > 170 ) {
			/* translators: %d */
			$issues[] = sprintf( __( 'Description is %d characters (aim 120–160)', 'digimarket' ), mb_strlen( $d ) );
		}
		if ( $t && count( $seen['t'][ strtolower( $t ) ] ) > 1 ) {
			$issues[] = __( 'Duplicate SEO title', 'digimarket' );
		}
		if ( $d && count( $seen['d'][ strtolower( $d ) ] ) > 1 ) {
			$issues[] = __( 'Duplicate description', 'digimarket' );
		}
		if ( '' === $kw ) {
			$issues[] = __( 'No focus keyword', 'digimarket' );
		} elseif ( false === stripos( $t . ' ' . $p->post_title, $kw ) ) {
			$issues[] = __( 'Focus keyword not in title', 'digimarket' );
		}
		if ( ! has_post_thumbnail( $p ) ) {
			$issues[] = __( 'No image', 'digimarket' );
		} elseif ( '' === (string) get_post_meta( get_post_thumbnail_id( $p ), '_wp_attachment_image_alt', true ) ) {
			$issues[] = __( 'Image has no alt text', 'digimarket' );
		}
		$min = 'post' === $p->post_type ? 900 : ( dm_is_service( $p->ID ) ? 500 : 150 );
		if ( $words < $min ) {
			/* translators: 1 words 2 min */
			$issues[] = sprintf( __( '%1$d words (aim %2$d+)', 'digimarket' ), $words, $min );
		}
		if ( 'post' === $p->post_type ) {
			$linked = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND ID <> %d AND post_content LIKE %s", $p->ID, '%' . $wpdb->esc_like( $p->post_name ) . '%' ) );
			$linked += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_dm_related_posts' AND FIND_IN_SET(%d, REPLACE(meta_value, ' ', ''))", $p->ID ) );
			if ( ! $linked ) {
				$issues[] = __( 'Orphan: no product or article links to it', 'digimarket' );
			}
		}
		if ( ! $issues ) {
			++$ok;
			continue;
		}
		echo '<tr><td><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '"><strong>' . esc_html( $p->post_title ) . '</strong></a><br><small>' . esc_html( 'post' === $p->post_type ? __( 'Article', 'digimarket' ) : ucfirst( dm_listing_type( $p->ID ) ) ) . '</small></td><td>' . esc_html( implode( ' · ', $issues ) ) . '</td></tr>';
	}
	if ( $ok === count( $posts ) ) {
		echo '<tr><td colspan="2">' . esc_html__( 'Everything looks good.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table><p class="description">' . esc_html( sprintf( /* translators: 1 ok 2 total */ __( '%1$d of %2$d pages pass every check.', 'digimarket' ), $ok, count( $posts ) ) ) . '</p></div>';
}

add_action( 'dm_admin_do_redirect_add', function ( $r ) {
	global $wpdb;
	$src = '/' . trim( sanitize_text_field( $r['source'] ?? '' ), '/' ) . '/';
	$tgt = trim( sanitize_text_field( $r['target'] ?? '' ) );
	if ( '//' === $src || '' === $tgt ) {
		dm_admin_back( '', __( 'Enter both paths.', 'digimarket' ) );
	}
	$tgt = 0 === strpos( $tgt, 'http' ) ? esc_url_raw( $tgt ) : '/' . ltrim( $tgt, '/' );
	$wpdb->insert( dm_table( 'redirects' ), array( 'source' => $src, 'target' => $tgt, 'hits' => 0, 'created_at' => dm_now() ) );
	$wpdb->delete( dm_table( 'not_found' ), array( 'path' => $src ) );
	dm_admin_back( __( 'Redirect added.', 'digimarket' ) );
} );
add_action( 'dm_admin_do_redirect_delete', function ( $r ) {
	global $wpdb;
	$wpdb->delete( dm_table( 'redirects' ), array( 'id' => absint( $r['id'] ?? 0 ) ) );
	dm_admin_back( __( 'Redirect deleted.', 'digimarket' ) );
} );

/* -------------------------------------------------------------------------
 * IndexNow: tell Bing/Yandex instantly when a page is published or updated
 * ---------------------------------------------------------------------- */

function dm_indexnow_key() {
	$k = get_option( 'dm_indexnow_key' );
	if ( ! $k ) {
		$k = strtolower( wp_generate_password( 32, false, false ) );
		update_option( 'dm_indexnow_key', $k );
	}
	return $k;
}

add_action( 'init', function () {
	add_rewrite_rule( dm_rule_prefix() . 'indexnow-([a-z0-9]{32})\.txt$', 'index.php?dm_indexnow=$matches[1]', 'top' );
}, 21 );
add_filter( 'query_vars', function ( $v ) {
	$v[] = 'dm_indexnow';
	return $v;
} );
add_action( 'template_redirect', function () {
	$k = get_query_var( 'dm_indexnow' );
	if ( $k && hash_equals( dm_indexnow_key(), (string) $k ) ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html( $k );
		exit;
	}
}, 0 );

add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'publish' !== $new || ! in_array( $post->post_type, array( 'dm_product', 'post', 'page', 'dm_portfolio' ), true ) || ! get_option( 'blog_public' ) ) {
		return;
	}
	if ( wp_is_post_revision( $post ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! $host || in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) {
		return;
	}
	$key = dm_indexnow_key();
	wp_remote_post(
		'https://api.indexnow.org/indexnow',
		array(
			'blocking' => false,
			'timeout'  => 2,
			'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'     => wp_json_encode(
				array(
					'host'        => $host,
					'key'         => $key,
					'keyLocation' => dm_pretty_url( 'indexnow-' . $key . '.txt' ),
					'urlList'     => array( get_permalink( $post ) ),
				)
			),
		)
	);
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Images & performance
 * ---------------------------------------------------------------------- */

/* Resized images are generated as WebP (much smaller) when the server supports it. */
add_filter( 'image_editor_output_format', function ( $formats, $filename = '' ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/jpeg'] = 'image/webp';
		// GD cannot write palette (8-bit) PNGs as WebP; keep those as PNG.
		if ( ! dm_is_palette_png( $filename ) ) {
			$formats['image/png'] = 'image/webp';
		}
	}
	return $formats;
}, 10, 2 );

function dm_is_palette_png( $file ) {
	if ( ! $file || ! is_readable( $file ) ) {
		return false;
	}
	$head = (string) file_get_contents( $file, false, null, 0, 26 ); // phpcs:ignore
	return 26 === strlen( $head ) && "\x89PNG" === substr( $head, 0, 4 ) && 3 === ord( $head[25] );
}

/* Readable default alt text for new uploads (from the file name). */
add_action( 'add_attachment', function ( $id ) {
	if ( ! wp_attachment_is_image( $id ) || get_post_meta( $id, '_wp_attachment_image_alt', true ) ) {
		return;
	}
	$t = get_the_title( $id );
	$t = trim( preg_replace( '/[-_]+|\s+/', ' ', preg_replace( '/\d{3,}x\d{3,}|img|dsc|screenshot|scaled/i', '', $t ) ) );
	if ( strlen( $t ) > 2 ) {
		update_post_meta( $id, '_wp_attachment_image_alt', ucfirst( $t ) );
	}
} );

add_action( 'init', function () {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
} );

/* Block-editor CSS only where block content can appear. */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular() ) {
		return;
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}, 100 );

add_action( 'wp_head', function () {
	foreach ( array( 'figtree-latin', 'source-serif-latin' ) as $f ) {
		echo '<link rel="preload" href="' . esc_url( DM_URI . '/assets/fonts/' . $f . '.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
	echo '<meta name="theme-color" content="#5B4BFF">' . "\n";
}, 1 );
