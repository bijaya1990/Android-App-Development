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

	wp_enqueue_style( 'np-main', NP_URI . '/assets/css/main.css', array(), NP_VERSION );
	wp_enqueue_script( 'np-main', NP_URI . '/assets/js/main.js', array(), NP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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

/**
 * May the current visitor see the Post Job button?
 *
 * @return bool
 */
function np_can_post() {
	if ( ! np_opt( 'show_post_btn' ) || ! is_user_logged_in() ) {
		return false;
	}
	$role = np_opt( 'post_role' );
	return ( 'any' === $role ) ? true : current_user_can( $role );
}
