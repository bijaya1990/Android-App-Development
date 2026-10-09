<?php
/**
 * Roles: Super Admin (WordPress administrators) and organisation Admins.
 * Organisation users never see wp-admin or the admin bar.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Roles {

	const ORG_ROLE  = 'pkc_org';
	const ADMIN_CAP = 'pkc_manage';

	public static function add_roles() {
		if ( ! get_role( self::ORG_ROLE ) ) {
			add_role( self::ORG_ROLE, __( 'Pikacart Organisation', 'pikacart' ), array( 'read' => true ) );
		}
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::ADMIN_CAP ) ) {
			$admin->add_cap( self::ADMIN_CAP );
		}
	}

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'block_admin' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
	}

	public static function is_super_admin( $user_id = 0 ) {
		return $user_id ? user_can( $user_id, self::ADMIN_CAP ) : current_user_can( self::ADMIN_CAP );
	}

	public static function is_org_user( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		return $user && $user->exists() && in_array( self::ORG_ROLE, (array) $user->roles, true );
	}

	/**
	 * Send organisation users from wp-admin to /app/.
	 */
	public static function block_admin() {
		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}
		if ( self::is_org_user() && ! current_user_can( 'edit_posts' ) ) {
			wp_safe_redirect( pkc_url( 'app' ) );
			exit;
		}
	}

	public static function admin_bar( $show ) {
		return self::is_org_user() && ! current_user_can( 'edit_posts' ) ? false : $show;
	}

	public static function login_redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && self::is_org_user( $user ) && ! user_can( $user, 'edit_posts' ) ) {
			return pkc_url( 'app' );
		}
		return $redirect_to;
	}
}
