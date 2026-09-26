<?php
/**
 * Theme setup, assets, customizer, SEO tags.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'dm_theme_setup' );
function dm_theme_setup() {
	load_theme_textdomain( 'digimarket', DM_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 200, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'editor-styles' );

	add_image_size( 'dm-card', 640, 480, true );
	add_image_size( 'dm-gallery', 1200, 900, false );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'digimarket' ),
			'footer'  => __( 'Footer menu', 'digimarket' ),
		)
	);

	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1200;
	}
}

add_action( 'widgets_init', 'dm_widgets_init' );
function dm_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Blog sidebar', 'digimarket' ),
			'id'            => 'sidebar-1',
			'before_widget' => '<section id="%1$s" class="widget dm-card %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'Footer column', 'digimarket' ),
			'id'            => 'footer-1',
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		)
	);
}

add_action( 'wp_enqueue_scripts', 'dm_enqueue_assets' );
function dm_enqueue_assets() {
	wp_enqueue_style( 'digimarket', get_stylesheet_uri(), array(), DM_VERSION );
	$primary = sanitize_hex_color( get_theme_mod( 'dm_primary_color', '#5b4bff' ) );
	if ( $primary ) {
		wp_add_inline_style( 'digimarket', ':root{--dm-primary:' . $primary . ';}' );
	}
	wp_enqueue_script( 'digimarket', DM_URI . '/assets/js/main.js', array(), DM_VERSION, true );
	wp_localize_script(
		'digimarket',
		'DM',
		array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'dm_ajax' ),
			'loggedIn' => is_user_logged_in(),
			'loginUrl' => dm_url( 'login' ),
			'cartUrl'  => dm_url( 'cart' ),
			'i18n'     => array(
				'available'   => __( 'Available', 'digimarket' ),
				'taken'       => __( 'Not available', 'digimarket' ),
				'checking'    => __( 'Checking…', 'digimarket' ),
				'added'       => __( 'Added to cart', 'digimarket' ),
				'confirm'     => __( 'Are you sure?', 'digimarket' ),
				'error'       => __( 'Something went wrong. Please try again.', 'digimarket' ),
				'paymentFail' => __( 'Payment was not completed. You can retry below.', 'digimarket' ),
			),
		)
	);
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

/* Prevent flash of wrong theme: set dark/light before paint. */
add_action( 'wp_head', 'dm_theme_mode_script', 0 );
function dm_theme_mode_script() {
	$default = get_theme_mod( 'dm_default_mode', 'system' );
	echo "<script>(function(){try{var m=localStorage.getItem('dm-mode')||'" . esc_js( $default ) . "';if(m==='system'){m=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',m);}catch(e){}})();</script>\n";
}

/* -------------------------------------------------------------------------
 * Customizer
 * ---------------------------------------------------------------------- */

add_action( 'customize_register', 'dm_customize_register' );
function dm_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'dm_theme', array( 'title' => __( 'Marketplace Appearance', 'digimarket' ), 'priority' => 30 ) );

	$wp_customize->add_setting( 'dm_primary_color', array( 'default' => '#5b4bff', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dm_primary_color', array( 'label' => __( 'Brand colour', 'digimarket' ), 'section' => 'dm_theme' ) ) );

	$wp_customize->add_setting( 'dm_default_mode', array( 'default' => 'system', 'sanitize_callback' => 'dm_sanitize_mode' ) );
	$wp_customize->add_control(
		'dm_default_mode',
		array(
			'label'   => __( 'Default colour mode', 'digimarket' ),
			'section' => 'dm_theme',
			'type'    => 'select',
			'choices' => array( 'system' => __( 'Follow device', 'digimarket' ), 'light' => __( 'Light', 'digimarket' ), 'dark' => __( 'Dark', 'digimarket' ) ),
		)
	);

	$texts = array(
		'dm_hero_title'    => array( __( 'Hero title', 'digimarket' ), __( 'Digital products from independent creators', 'digimarket' ) ),
		'dm_hero_subtitle' => array( __( 'Hero subtitle', 'digimarket' ), __( 'Ebooks, courses, templates, software and more — delivered instantly, paid securely.', 'digimarket' ) ),
		'dm_footer_text'   => array( __( 'Footer about text', 'digimarket' ), __( 'A marketplace where creators open their own shop and get paid automatically on every sale.', 'digimarket' ) ),
	);
	foreach ( $texts as $id => $t ) {
		$wp_customize->add_setting( $id, array( 'default' => $t[1], 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( $id, array( 'label' => $t[0], 'section' => 'dm_theme', 'type' => 'dm_hero_title' === $id ? 'text' : 'textarea' ) );
	}

	$wp_customize->add_setting( 'dm_hero_image', array( 'default' => '', 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'dm_hero_image', array( 'label' => __( 'Hero background image (optional)', 'digimarket' ), 'section' => 'dm_theme', 'mime_type' => 'image' ) ) );

	$toggles = array(
		'dm_show_trending' => __( 'Show “Trending products”', 'digimarket' ),
		'dm_show_new'      => __( 'Show “New arrivals”', 'digimarket' ),
		'dm_show_shops'    => __( 'Show “Featured shops”', 'digimarket' ),
		'dm_show_trust'    => __( 'Show trust badges strip', 'digimarket' ),
	);
	foreach ( $toggles as $id => $label ) {
		$wp_customize->add_setting( $id, array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control( $id, array( 'label' => $label, 'section' => 'dm_theme', 'type' => 'checkbox' ) );
	}
}

function dm_sanitize_mode( $v ) {
	return in_array( $v, array( 'system', 'light', 'dark' ), true ) ? $v : 'system';
}

/* -------------------------------------------------------------------------
 * Access: keep non-admins out of wp-admin, hide admin bar for them.
 * ---------------------------------------------------------------------- */

add_action( 'admin_init', 'dm_block_wp_admin' );
function dm_block_wp_admin() {
	if ( wp_doing_ajax() || ! is_user_logged_in() ) {
		return;
	}
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'dm_view_marketplace' ) ) {
		dm_redirect( dm_is_seller() ? dm_url( 'dashboard' ) : dm_url( 'account' ) );
	}
}

add_filter( 'show_admin_bar', 'dm_admin_bar' );
function dm_admin_bar( $show ) {
	return ( current_user_can( 'edit_posts' ) || current_user_can( 'dm_view_marketplace' ) ) ? $show : false;
}

/* -------------------------------------------------------------------------
 * SEO meta for products & shops.
 * ---------------------------------------------------------------------- */

add_filter( 'pre_get_document_title', 'dm_document_title', 20 );
function dm_document_title( $title ) {
	if ( is_singular( 'dm_product' ) ) {
		$mt = get_post_meta( get_queried_object_id(), '_dm_meta_title', true );
		if ( $mt ) {
			return $mt;
		}
	}
	$route = get_query_var( 'dm_route' );
	$store = get_query_var( 'dm_store' );
	if ( $store ) {
		$sid = dm_get_seller_by_slug( $store );
		if ( $sid ) {
			return dm_shop_name( $sid ) . ' – ' . get_bloginfo( 'name' );
		}
	}
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
		);
		if ( isset( $names[ $route ] ) ) {
			return $names[ $route ] . ' – ' . get_bloginfo( 'name' );
		}
	}
	return $title;
}

add_action( 'wp_head', 'dm_meta_tags', 5 );
function dm_meta_tags() {
	$desc  = '';
	$image = '';
	if ( is_singular( 'dm_product' ) ) {
		$pid   = get_queried_object_id();
		$desc  = get_post_meta( $pid, '_dm_meta_desc', true );
		$desc  = $desc ? $desc : get_the_excerpt( $pid );
		$image = get_the_post_thumbnail_url( $pid, 'dm-gallery' );
		$price = dm_product_price( $pid );
		$data  = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => get_the_title( $pid ),
			'description' => wp_strip_all_tags( $desc ),
			'image'       => $image ? $image : null,
			'brand'       => array( '@type' => 'Brand', 'name' => dm_shop_name( get_post_field( 'post_author', $pid ) ) ),
			'offers'      => array(
				'@type'         => 'Offer',
				'price'         => number_format( $price, 2, '.', '' ),
				'priceCurrency' => dm_opt( 'currency_code', 'INR' ),
				'availability'  => 'https://schema.org/InStock',
				'url'           => get_permalink( $pid ),
			),
		);
		$count = (int) get_post_meta( $pid, '_dm_rating_count', true );
		if ( $count ) {
			$data['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => (float) get_post_meta( $pid, '_dm_rating_avg', true ), 'reviewCount' => $count );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array_filter( $data ) ) . "</script>\n";
	} elseif ( get_query_var( 'dm_store' ) ) {
		$sid  = dm_get_seller_by_slug( get_query_var( 'dm_store' ) );
		$desc = $sid ? get_user_meta( $sid, 'dm_shop_bio', true ) : '';
		$image = $sid ? dm_shop_banner_url( $sid ) : '';
	} elseif ( is_front_page() ) {
		$desc = get_bloginfo( 'description' );
	}
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $desc ), 30, '…' ) ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $desc ), 30, '…' ) ) . '">' . "\n";
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
}

add_filter( 'body_class', 'dm_body_class' );
function dm_body_class( $classes ) {
	$route = get_query_var( 'dm_route' );
	if ( $route ) {
		$classes[] = 'dm-route-' . sanitize_html_class( $route );
	}
	if ( get_query_var( 'dm_store' ) ) {
		$classes[] = 'dm-route-store';
	}
	return $classes;
}

add_filter( 'excerpt_length', function () {
	return 24;
} );

/**
 * Admin idle auto-logout for marketplace admins (front and back end).
 */
add_action( 'admin_footer', 'dm_idle_logout_script' );
add_action( 'wp_footer', 'dm_idle_logout_script' );
function dm_idle_logout_script() {
	if ( ! current_user_can( 'dm_view_marketplace' ) ) {
		return;
	}
	$mins = (int) dm_opt( 'admin_idle_minutes', 30 );
	if ( $mins < 1 ) {
		return;
	}
	$url = wp_logout_url( wp_login_url() );
	echo "<script>(function(){var t,ms=" . (int) ( $mins * 60000 ) . ";function r(){clearTimeout(t);t=setTimeout(function(){window.location.href=" . wp_json_encode( html_entity_decode( $url ) ) . ";},ms);}['mousemove','keydown','click','scroll','touchstart'].forEach(function(e){document.addEventListener(e,r,{passive:true});});r();})();</script>";
}

/*
 * Use native emoji instead of WordPress' CDN-hosted emoji images
 * (faster, no third-party requests; the theme uses emoji as icons).
 */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
} );
