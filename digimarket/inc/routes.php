<?php
/**
 * Virtual routes (cart, checkout, dashboards, auth, shops) and form dispatch.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_routes() {
	return array( 'cart', 'checkout', 'order-received', 'account', 'dashboard', 'login', 'register', 'forgot', 'reset', 'verify', 'sell', 'shops', 'invoice', 'download' );
}

add_action( 'init', 'dm_add_rewrites', 20 );

/**
 * Regex prefix for our rules. With "/index.php/..." (PATHINFO) permalinks WordPress
 * matches requests as "index.php/cart", so rules must carry the same prefix.
 */
function dm_rule_prefix() {
	global $wp_rewrite;
	return ( $wp_rewrite instanceof WP_Rewrite && $wp_rewrite->using_index_permalinks() ) ? $wp_rewrite->index . '/' : '^';
}

function dm_add_rewrites() {
	$p = dm_rule_prefix();
	add_rewrite_rule( $p . 'store/([^/]+)/?$', 'index.php?dm_store=$matches[1]', 'top' );
	add_rewrite_rule( $p . 'store/([^/]+)/page/([0-9]+)/?$', 'index.php?dm_store=$matches[1]&paged=$matches[2]', 'top' );
	add_rewrite_rule( $p . 'admin/login/?$', 'index.php?dm_route=adminlogin', 'top' );
	foreach ( dm_routes() as $r ) {
		add_rewrite_rule( $p . $r . '/?$', 'index.php?dm_route=' . $r, 'top' );
		add_rewrite_rule( $p . $r . '/([^/]+)/?$', 'index.php?dm_route=' . $r . '&dm_tab=$matches[1]', 'top' );
		add_rewrite_rule( $p . $r . '/([^/]+)/([^/]+)/?$', 'index.php?dm_route=' . $r . '&dm_tab=$matches[1]&dm_id=$matches[2]', 'top' );
	}
	// Self-heal: flush when asked to, or when our rules are missing from the stored rules.
	$rules = get_option( 'rewrite_rules' );
	if ( get_option( 'dm_flush_rewrite' ) || ( dm_pretty_permalinks() && ( ! is_array( $rules ) || ! isset( $rules[ $p . 'cart/?$' ] ) ) ) ) {
		dm_hard_flush_rewrites();
		delete_option( 'dm_flush_rewrite' );
	}
}

/**
 * Flush rewrite rules AND write the .htaccess / web.config file.
 * A soft flush only updates the database; on Apache/LiteSpeed pretty URLs
 * then 404 because the server never forwards them to WordPress.
 */
function dm_hard_flush_rewrites() {
	if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	flush_rewrite_rules( true );
}

/**
 * Warn the admin when pretty permalinks are on but .htaccess has no WordPress rules.
 */
add_action( 'admin_notices', 'dm_htaccess_notice' );
function dm_htaccess_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! dm_pretty_permalinks() ) {
		return;
	}
	global $is_apache;
	if ( ! $is_apache ) {
		return;
	}
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$file = get_home_path() . '.htaccess';
	$ok   = file_exists( $file ) && false !== strpos( (string) file_get_contents( $file ), 'RewriteRule . /' ); // phpcs:ignore
	if ( ! $ok ) {
		$ok = file_exists( $file ) && false !== strpos( (string) file_get_contents( $file ), 'index.php [L]' ); // phpcs:ignore
	}
	if ( $ok ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>DigiMarket:</strong> ' . wp_kses_post(
		sprintf(
			/* translators: %s: permalinks settings URL */
			__( 'Your .htaccess file is missing the WordPress rewrite rules, so pages like /cart/ and /sell/ will show 404. Open <a href="%s">Settings → Permalinks</a> and click “Save Changes”. If the file is not writable, copy the rules shown there into the .htaccess file in your site root (cPanel → File Manager).', 'digimarket' ),
			esc_url( admin_url( 'options-permalink.php' ) )
		)
	) . '</p></div>';
}

add_filter( 'query_vars', 'dm_query_vars' );
function dm_query_vars( $vars ) {
	return array_merge( $vars, array( 'dm_route', 'dm_tab', 'dm_id', 'dm_store' ) );
}

/**
 * Stop WordPress treating our routes as the blog home / 404.
 */
add_action( 'parse_query', 'dm_parse_query' );
function dm_parse_query( $q ) {
	if ( ! $q->is_main_query() ) {
		return;
	}
	if ( $q->get( 'dm_route' ) || $q->get( 'dm_store' ) ) {
		$q->is_home     = false;
		$q->is_404      = false;
		$q->is_archive  = false;
		$q->is_singular = false;
	}
}

add_filter( 'redirect_canonical', function ( $redirect ) {
	return ( get_query_var( 'dm_route' ) || get_query_var( 'dm_store' ) ) ? false : $redirect;
} );

function dm_route() {
	return (string) get_query_var( 'dm_route' );
}

function dm_tab() {
	return sanitize_key( (string) get_query_var( 'dm_tab' ) );
}

function dm_route_id() {
	return sanitize_text_field( (string) get_query_var( 'dm_id' ) );
}

/**
 * Access guards + non-template routes.
 */
add_action( 'template_redirect', 'dm_route_guard', 5 );
function dm_route_guard() {
	$route = dm_route();
	if ( ! $route && ! get_query_var( 'dm_store' ) ) {
		return;
	}
	status_header( 200 );
	nocache_headers();

	switch ( $route ) {
		case 'adminlogin':
			dm_redirect( wp_login_url( admin_url( 'admin.php?page=dm-marketplace' ) ) );
			break;
		case 'account':
		case 'checkout':
		case 'order-received':
		case 'invoice':
			if ( ! is_user_logged_in() ) {
				dm_redirect( dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_current_url() ) ) ) );
			}
			break;
		case 'dashboard':
			if ( ! is_user_logged_in() ) {
				dm_redirect( dm_url( 'login', '', '', array( 'redirect_to' => rawurlencode( dm_current_url() ) ) ) );
			}
			if ( ! dm_is_seller() ) {
				dm_redirect( dm_url( 'sell' ) );
			}
			break;
		case 'login':
		case 'register':
		case 'forgot':
			if ( is_user_logged_in() ) {
				dm_redirect( dm_url( 'account' ) );
			}
			break;
		case 'download':
			dm_serve_download( dm_tab() ? get_query_var( 'dm_tab' ) : ( isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '' ) );
			break;
		case 'verify':
			dm_handle_verify_email();
			break;
	}

	if ( 'account' === $route && is_user_logged_in() ) {
		dm_account_early_actions();
	}
	if ( 'dashboard' === $route && is_user_logged_in() ) {
		dm_dashboard_early_actions();
	}
	if ( 'invoice' === $route ) {
		dm_render_invoice( absint( dm_tab() ) );
	}
}

add_filter( 'template_include', 'dm_template_include', 99 );
function dm_template_include( $template ) {
	if ( get_query_var( 'dm_store' ) ) {
		return DM_DIR . '/templates/store.php';
	}
	$route = dm_route();
	if ( $route && in_array( $route, dm_routes(), true ) ) {
		$file = DM_DIR . '/templates/' . $route . '.php';
		if ( file_exists( $file ) ) {
			return $file;
		}
	}
	return $template;
}

/**
 * POST form dispatcher. Every form includes dm_action + _dmnonce.
 */
add_action( 'wp_loaded', 'dm_dispatch_forms' );
function dm_dispatch_forms() {
	if ( empty( $_POST['dm_action'] ) || 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return;
	}
	$action = sanitize_key( wp_unslash( $_POST['dm_action'] ) );
	$fn     = 'dm_do_' . $action;
	if ( ! function_exists( $fn ) ) {
		return;
	}
	if ( empty( $_POST['_dmnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_dmnonce'] ) ), 'dm_' . $action ) ) {
		dm_flash( 'error', __( 'Your session expired. Please try again.', 'digimarket' ) );
		dm_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
	}
	if ( is_user_logged_in() && dm_user_suspended( get_current_user_id() ) && ! current_user_can( 'manage_options' ) && 'logout' !== $action ) {
		dm_flash( 'error', __( 'Your account is suspended. Please contact support.', 'digimarket' ) );
		dm_redirect( home_url( '/' ) );
	}
	call_user_func( $fn );
}

/**
 * Helper to require login inside handlers.
 */
function dm_require_login() {
	if ( ! is_user_logged_in() ) {
		dm_flash( 'error', __( 'Please log in first.', 'digimarket' ) );
		dm_redirect( dm_url( 'login' ) );
	}
}

function dm_back( $fallback = '' ) {
	$ref = wp_get_referer();
	dm_redirect( $ref ? $ref : ( $fallback ? $fallback : home_url( '/' ) ) );
}

/**
 * Shared AJAX guard.
 */
function dm_ajax_check() {
	if ( ! check_ajax_referer( 'dm_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Session expired. Reload the page.', 'digimarket' ) ), 403 );
	}
}
