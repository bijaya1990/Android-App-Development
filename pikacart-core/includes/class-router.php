<?php
/**
 * Clean addresses for the app and account pages:
 * /app/, /login/, /register/, /forgot-password/, /reset-password/, /verify-email/
 * These pages are kept out of search engines.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Router {

	const ROUTES = array( 'app', 'login', 'register', 'forgot-password', 'reset-password', 'verify-email', 'verify', 'fill' );

	/** @var string */
	private static $route = '';

	public static function init() {
		self::add_rewrite_rules();
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maintenance' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'dispatch' ), 5 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_action( 'wp_head', array( __CLASS__, 'public_head' ), 2 );
		add_action( 'wp_footer', array( __CLASS__, 'public_footer' ), 50 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'no_canonical_redirect' ) );

		if ( get_option( 'pkc_flush_rewrite' ) ) {
			delete_option( 'pkc_flush_rewrite' );
			add_action( 'wp_loaded', 'flush_rewrite_rules' );
		}
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule( '^app/?$', 'index.php?pkc_route=app', 'top' );
		add_rewrite_rule( '^app/(.+?)/?$', 'index.php?pkc_route=app&pkc_path=$matches[1]', 'top' );
		add_rewrite_rule( '^verify/([A-Za-z0-9]+)/?$', 'index.php?pkc_route=verify&pkc_path=$matches[1]', 'top' );
		add_rewrite_rule( '^fill/([A-Za-z0-9]+)/?$', 'index.php?pkc_route=fill&pkc_path=$matches[1]', 'top' );
		foreach ( array( 'login', 'register', 'forgot-password', 'reset-password', 'verify-email' ) as $route ) {
			add_rewrite_rule( '^' . $route . '/?$', 'index.php?pkc_route=' . $route, 'top' );
		}
	}

	public static function query_vars( $vars ) {
		$vars[] = 'pkc_route';
		$vars[] = 'pkc_path';
		return $vars;
	}

	public static function current() {
		if ( '' === self::$route ) {
			$route       = (string) get_query_var( 'pkc_route' );
			self::$route = in_array( $route, self::ROUTES, true ) ? $route : '-';
		}
		return '-' === self::$route ? '' : self::$route;
	}

	public static function no_canonical_redirect( $redirect ) {
		return self::current() ? false : $redirect;
	}

	public static function robots( $robots ) {
		if ( self::current() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['max-image-preview'] );
		}
		return $robots;
	}

	/**
	 * Maintenance mode: everyone except Super Admins sees a "back soon" page.
	 */
	public static function maintenance() {
		if ( ! pkc_setting( 'maintenance', 0 ) || current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			return;
		}
		status_header( 503 );
		header( 'Retry-After: 1800' );
		self::render(
			'message',
			array(
				'title'   => __( 'We will be back soon', 'pikacart' ),
				'message' => pkc_setting( 'maintenance_text', '' ),
				'icon'    => 'tools',
			)
		);
	}

	public static function dispatch() {
		$route = self::current();
		if ( ! $route ) {
			return;
		}
		nocache_headers();
		// Never let a page cache (LiteSpeed Cache, WP Rocket, W3TC…) store app pages.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'X-Robots-Tag: noindex, nofollow', true );

		$user = wp_get_current_user();

		switch ( $route ) {
			case 'login':
			case 'register':
			case 'forgot-password':
				if ( $user->exists() && PKC_Organisations::get_by_user( $user->ID ) ) {
					wp_safe_redirect( pkc_url( 'app' ) );
					exit;
				}
				self::render( 'auth', array( 'view' => $route ) );
				break;

			case 'reset-password':
				self::render( 'auth', array( 'view' => 'reset-password' ) );
				break;

			case 'verify-email':
				$uid   = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
				$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$ok    = $uid && PKC_Accounts::verify_email( $uid, $token );
				if ( ! $ok && $uid && PKC_Accounts::is_verified( $uid ) ) {
					$ok = true;
				}
				self::render(
					'message',
					array(
						'title'   => $ok ? __( 'Email verified', 'pikacart' ) : __( 'Link not valid', 'pikacart' ),
						'message' => $ok ? __( 'Thank you! Your email address is confirmed.', 'pikacart' ) : __( 'This verification link is invalid or was already used. You can send a new link from your dashboard.', 'pikacart' ),
						'icon'    => $ok ? 'check' : 'alert',
						'button'  => array( __( 'Go to my dashboard', 'pikacart' ), pkc_url( 'app' ) ),
					)
				);
				break;

			case 'app':
				self::app( $user );
				break;

			case 'verify':
				self::verify( (string) get_query_var( 'pkc_path' ) );
				break;

			case 'fill':
				$res = PKC_REST_Members::open_link( (string) get_query_var( 'pkc_path' ) );
				if ( is_wp_error( $res ) ) {
					status_header( 410 );
					self::render(
						'message',
						array(
							'title'   => __( 'Form closed', 'pikacart' ),
							'message' => $res->get_error_message(),
							'icon'    => 'lock',
						)
					);
				}
				self::render(
					'fill',
					array(
						'token' => (string) get_query_var( 'pkc_path' ),
						'org'   => $res[1],
					)
				);
				break;
		}
	}

	private static function app( $user ) {
		if ( ! $user->exists() ) {
			$target = home_url( add_query_arg( array() ) );
			wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $target ), pkc_url( 'login' ) ) );
			exit;
		}
		$org = PKC_Organisations::current();
		if ( ! $org ) {
			self::render(
				'message',
				array(
					'title'   => __( 'This is the organisation dashboard', 'pikacart' ),
					'message' => current_user_can( PKC_Roles::ADMIN_CAP )
						? __( 'You are logged in as a Super Admin. Manage Pikacart from the WordPress dashboard.', 'pikacart' )
						: __( 'Your login is not linked to an organisation. Please contact support.', 'pikacart' ),
					'icon'    => 'info',
					'button'  => current_user_can( PKC_Roles::ADMIN_CAP ) ? array( __( 'Open Pikacart control room', 'pikacart' ), admin_url( 'admin.php?page=pikacart' ) ) : array( __( 'Log out', 'pikacart' ), wp_logout_url( pkc_url( 'login' ) ) ),
				)
			);
		}
		$org = PKC_Access::refresh( $org );
		if ( 'suspended' === $org->status && ! PKC_Organisations::viewing_as() ) {
			self::render(
				'message',
				array(
					'title'   => __( 'Account suspended', 'pikacart' ),
					'message' => sprintf(
						/* translators: %s: support email */
						__( 'This account has been suspended. Please contact %s for help.', 'pikacart' ),
						pkc_support_email()
					),
					'icon'    => 'lock',
					'button'  => array( __( 'Log out', 'pikacart' ), wp_logout_url( pkc_url( 'login' ) ) ),
				)
			);
		}
		self::render( 'app', array( 'org' => $org ) );
	}

	/**
	 * Public card verification page (opened by scanning the QR code).
	 */
	private static function verify( $token ) {
		if ( ! PKC_Rate_Limit::hit( 'verify', pkc_ip(), 60, MINUTE_IN_SECONDS ) ) {
			status_header( 429 );
			self::render(
				'message',
				array(
					'title'   => __( 'Please wait a moment', 'pikacart' ),
					'message' => __( 'Too many checks from this device. Please try again in a minute.', 'pikacart' ),
					'icon'    => 'clock',
				)
			);
		}
		if ( 'demo' === $token ) {
			self::render( 'verify', array( 'demo' => true ) );
		}
		$m   = PKC_Members::by_token( $token );
		$org = $m ? PKC_Access::refresh( PKC_Organisations::get( $m->org_id ) ) : null;
		if ( ! $m || ! $org ) {
			status_header( 404 );
			self::render( 'verify', array( 'missing' => true ) );
		}
		$status = 'valid';
		if ( $m->deleted_at || 'cancelled' === $m->status || 'suspended' === $org->status ) {
			$status = 'cancelled';
		} elseif ( 'pending' === $m->status ) {
			$status = 'cancelled';
		} elseif ( 'expired' === PKC_Members::display_status( $m ) ) {
			$status = 'expired';
		}
		self::render(
			'verify',
			array(
				'member' => $m,
				'org'    => $org,
				'status' => $status,
			)
		);
	}

	/**
	 * Output a template and stop. A theme can override it with
	 * yourtheme/pikacart/{name}.php.
	 */
	public static function render( $name, $args = array() ) {
		$file = locate_template( 'pikacart/' . $name . '.php' );
		if ( ! $file ) {
			$file = PKC_DIR . 'templates/' . $name . '.php';
		}
		$pkc = $args; // Available inside the template.
		include $file;
		exit;
	}

	/**
	 * Search Console, Analytics and custom scripts on public pages only.
	 */
	public static function public_head() {
		if ( self::current() || is_admin() ) {
			return;
		}
		$gsc = trim( (string) pkc_setting( 'seo_gsc', '' ) );
		if ( $gsc ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '" />' . "\n";
		}
		$ga = trim( (string) pkc_setting( 'seo_ga', '' ) );
		if ( $ga && preg_match( '/^[A-Z0-9-]+$/i', $ga ) ) {
			echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $ga ) . '"></script>' . "\n";
			echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . esc_js( $ga ) . "');</script>\n";
		}
		$custom = (string) pkc_setting( 'seo_head_scripts', '' );
		if ( '' !== $custom ) {
			echo $custom . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- Saved only by users with unfiltered_html.
		}
	}

	public static function public_footer() {
		if ( self::current() || is_admin() ) {
			return;
		}
		$custom = (string) pkc_setting( 'seo_footer_scripts', '' );
		if ( '' !== $custom ) {
			echo $custom . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- Saved only by users with unfiltered_html.
		}
	}
}
