<?php
/**
 * Theme setup, assets, head cleanup and basic hardening.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'np_setup' );
function np_setup() {
	load_child_theme_textdomain( 'naukripatra', NP_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 240, 'flex-width' => true, 'flex-height' => true ) );
	add_image_size( 'np-card', 600, 338, true );
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'naukripatra' ),
			'footer'  => __( 'Footer Menu', 'naukripatra' ),
		)
	);
}

/**
 * Tiny inline script in <head>: applies saved/system colour scheme before first paint (no flash).
 */
add_action( 'wp_head', 'np_theme_init_script', 1 );
function np_theme_init_script() {
	$default = in_array( np_opt( 'dark_default' ), array( 'light', 'dark' ), true ) ? np_opt( 'dark_default' ) : 'system';
	?>
<script>(function(){try{var t=localStorage.getItem('np-theme');if(!t){t=<?php echo wp_json_encode( $default ); ?>;if(t==='system'){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
	<?php
}

add_action( 'wp_enqueue_scripts', 'np_assets', 20 );
function np_assets() {
	// Remove WP bloat; blocks are re-styled by the theme.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );

	if ( ! np_opt( 'gp_base_css' ) ) {
		wp_dequeue_style( 'generate-style' );
		wp_dequeue_style( 'generate-child' );
		wp_dequeue_style( 'generate-widget-areas' );
		wp_dequeue_style( 'generate-font-icons' );
	}

	np_print_css();
	if ( np_ads_present() ) {
		wp_enqueue_script( 'np-ads', NP_URI . '/assets/js/ads.js', array(), NP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script( 'np-ads', 'npAds', array( 'delay' => (int) np_opt( 'ad_delay' ) ) );
	}
	wp_enqueue_script( 'np-main', NP_URI . '/assets/js/main.js', array(), NP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'np-main', 'npData', array( 'view' => is_singular( 'post' ) ? esc_url_raw( rest_url( 'naukripatra/v1/view/' . get_queried_object_id() ) ) : '' ) );
	// Page-specific assets (tools load only on their own pages).
	$tools = array( 'photo-resizer' => 'tool-photo', 'signature-scanner' => 'tool-signature', 'resume-maker' => 'tool-resume' );
	foreach ( $tools as $slug => $handle ) {
		if ( is_page( $slug ) ) {
			wp_enqueue_style( 'np-tools', NP_URI . '/assets/css/tools.css', array(), NP_VERSION );
			wp_enqueue_script( 'np-' . $handle, NP_URI . '/assets/js/' . $handle . '.js', array(), NP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}
	if ( is_page( 'post-job' ) ) {
		wp_enqueue_style( 'np-tools', NP_URI . '/assets/css/tools.css', array(), NP_VERSION );
		wp_enqueue_script( 'np-post-form', NP_URI . '/assets/js/post-form.js', array(), NP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}

// Remove emoji script, embeds, version leaks, XML-RPC.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
add_filter( 'the_generator', '__return_empty_string' );
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'login_errors', function () {
	return __( 'Login failed. Please check your details.', 'naukripatra' );
} );

/**
 * Allow large image previews (Discover).
 */
add_filter( 'wp_robots', function ( $robots ) {
	$robots['max-image-preview'] = 'large';
	return $robots;
} );

/**
 * Brand markup: logo image if set, otherwise bold text.
 *
 * @return string
 */
function np_brand_html() {
	$logo = (int) np_opt( 'brand_logo_id' );
	if ( $logo ) {
		return wp_get_attachment_image( $logo, 'full', false, array( 'class' => 'np-logo', 'alt' => esc_attr( np_opt( 'brand_text' ) ) ) );
	}
	return esc_html( np_opt( 'brand_text' ) );
}

/** May the current user submit jobs (logged in + role setting)? */
function np_user_may_post() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$role = np_opt( 'post_role' );
	return ( 'any' === $role ) ? true : current_user_can( $role );
}

/** Show the Post Job button? */
function np_can_post() {
	return np_opt( 'show_post_btn' ) && np_user_may_post();
}

/**
 * Print theme CSS: inlined (minified, cached) by default, otherwise a normal stylesheet.
 */
function np_print_css() {
	$file = NP_DIR . '/assets/css/main.css';
	$font = file_exists( NP_DIR . '/assets/fonts/inter-var.woff2' ) ? '@font-face{font-family:Inter;font-weight:100 900;font-display:swap;src:url(' . NP_URI . '/assets/fonts/inter-var.woff2) format("woff2")}' : '';
	$vars = ':root{--np-soon:' . esc_attr( np_opt( 'color_soon' ) ) . ';--np-expired:' . esc_attr( np_opt( 'color_expired' ) ) . '}';
	if ( np_opt( 'inline_css' ) ) {
		$css = get_transient( 'np_css_' . NP_VERSION . filemtime( $file ) );
		if ( false === $css ) {
			$css = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$css = preg_replace( array( '#/\*.*?\*/#s', '#\s+#', '#\s*([{};:,>])\s*#' ), array( '', ' ', '$1' ), $css );
			set_transient( 'np_css_' . NP_VERSION . filemtime( $file ), $css, WEEK_IN_SECONDS );
		}
		wp_register_style( 'np-main', false, array(), NP_VERSION );
		wp_enqueue_style( 'np-main' );
		wp_add_inline_style( 'np-main', $font . $vars . $css );
	} else {
		wp_enqueue_style( 'np-main', NP_URI . '/assets/css/main.css', array(), NP_VERSION );
		wp_add_inline_style( 'np-main', $font . $vars );
	}
}

// Preload the self-hosted font if it was added.
add_action( 'wp_head', function () {
	if ( file_exists( NP_DIR . '/assets/fonts/inter-var.woff2' ) ) {
		echo '<link rel="preload" href="' . esc_url( NP_URI . '/assets/fonts/inter-var.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}, 2 );
