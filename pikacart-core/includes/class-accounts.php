<?php
/**
 * Registration, login, email verification and passwords.
 * Uses WordPress users and WordPress password hashing.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Accounts {

	const MIN_FORM_SECONDS = 3;

	/**
	 * Signed timestamp put in the registration form (anti-spam time check).
	 */
	public static function form_stamp() {
		$t = time();
		return $t . '.' . hash_hmac( 'sha256', (string) $t, wp_salt( 'nonce' ) );
	}

	private static function stamp_ok( $stamp ) {
		$parts = explode( '.', (string) $stamp );
		if ( 2 !== count( $parts ) ) {
			return false;
		}
		$t = (int) $parts[0];
		if ( ! hash_equals( hash_hmac( 'sha256', (string) $t, wp_salt( 'nonce' ) ), $parts[1] ) ) {
			return false;
		}
		$age = time() - $t;
		return $age >= self::MIN_FORM_SECONDS && $age < DAY_IN_SECONDS;
	}

	private static function err( $message, $field = '', $status = 400 ) {
		return new WP_Error(
			'pkc_invalid',
			$message,
			array(
				'status' => $status,
				'field'  => $field,
			)
		);
	}

	public static function valid_mobile( $mobile ) {
		return (bool) preg_match( '/^\d{8,15}$/', $mobile );
	}

	/**
	 * Register a new organisation account and start its trial.
	 *
	 * @return array|WP_Error
	 */
	public static function register( $in ) {
		// Honeypot: real people never fill this hidden field.
		if ( ! empty( $in['website'] ) ) {
			return self::err( __( 'Registration failed. Please try again.', 'pikacart' ) );
		}
		if ( ! self::stamp_ok( $in['stamp'] ?? '' ) ) {
			return self::err( __( 'Please take a moment to fill the form, then try again.', 'pikacart' ) );
		}
		if ( ! PKC_Rate_Limit::hit( 'register', pkc_ip(), 5, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}

		$org_name = sanitize_text_field( $in['org_name'] ?? '' );
		$contact  = sanitize_text_field( $in['contact_name'] ?? '' );
		$email    = sanitize_email( $in['email'] ?? '' );
		$mobile   = pkc_normalize_mobile( $in['mobile'] ?? '' );
		$password = (string) ( $in['password'] ?? '' );

		if ( mb_strlen( $org_name ) < 2 ) {
			return self::err( __( 'Please enter your organisation name.', 'pikacart' ), 'org_name' );
		}
		if ( mb_strlen( $contact ) < 2 ) {
			return self::err( __( 'Please enter the contact person name.', 'pikacart' ), 'contact_name' );
		}
		if ( ! is_email( $email ) ) {
			return self::err( __( 'Please enter a valid email address.', 'pikacart' ), 'email' );
		}
		if ( ! self::valid_mobile( $mobile ) ) {
			return self::err( __( 'Please enter a valid mobile number.', 'pikacart' ), 'mobile' );
		}
		if ( strlen( $password ) < 8 ) {
			return self::err( __( 'Password must be at least 8 characters.', 'pikacart' ), 'password' );
		}
		if ( empty( $in['authorised'] ) ) {
			return self::err( __( 'Please confirm that you are authorised to issue ID cards for this organisation.', 'pikacart' ), 'authorised' );
		}
		if ( email_exists( $email ) ) {
			return self::err( __( 'An account with this email already exists. Please log in.', 'pikacart' ), 'email' );
		}
		if ( PKC_Organisations::mobile_exists( $mobile ) ) {
			return self::err( __( 'An account with this mobile number already exists. Please log in.', 'pikacart' ), 'mobile' );
		}

		// One trial per email and per mobile, even after an account is deleted.
		$trial_allowed = PKC_Access::free_forever() || ( ! self::claimed( 'email', $email ) && ! self::claimed( 'mobile', $mobile ) );

		$login   = self::unique_login( $email );
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $contact,
				'first_name'   => $contact,
				'role'         => PKC_Roles::ORG_ROLE,
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return self::err( $user_id->get_error_message() );
		}

		$org_id = PKC_Organisations::create(
			$user_id,
			array(
				'name'         => $org_name,
				'contact_name' => $contact,
				'email'        => $email,
				'mobile'       => $mobile,
			),
			$trial_allowed
		);
		if ( ! $org_id ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
			return self::err( __( 'Could not create the account. Please try again.', 'pikacart' ), '', 500 );
		}

		self::claim( 'email', $email );
		self::claim( 'mobile', $mobile );
		update_user_meta( $user_id, 'pkc_authorised_at', pkc_now() );
		PKC_Activity_Log::add( 'account.registered', $org_id, $org_name );

		$org = PKC_Organisations::get( $org_id );
		self::send_verification( $user_id );
		if ( $trial_allowed ) {
			PKC_Emails::send_to_org( $org, 'welcome' );
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );

		return array(
			'redirect' => pkc_url( 'app' ),
			'message'  => self::welcome_message( $trial_allowed ),
		);
	}

	private static function welcome_message( $trial_allowed ) {
		if ( PKC_Access::free_forever() ) {
			return __( 'Welcome! Your free account is ready.', 'pikacart' );
		}
		return $trial_allowed
			? __( 'Welcome! Your free trial has started.', 'pikacart' )
			: __( 'Your account is ready. A free trial was already used with this email or mobile number, so please subscribe to continue.', 'pikacart' );
	}

	private static function unique_login( $email ) {
		$base  = sanitize_user( current( explode( '@', $email ) ), true );
		$base  = $base ? substr( $base, 0, 40 ) : 'org';
		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . ++$i;
		}
		return $login;
	}

	private static function hash_claim( $value ) {
		return hash( 'sha256', strtolower( trim( $value ) ) . '|' . wp_salt( 'auth' ) );
	}

	private static function claimed( $kind, $value ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . pkc_table( 'trial_claims' ) . ' WHERE kind = %s AND hash = %s', $kind, self::hash_claim( $value ) )
		);
	}

	private static function claim( $kind, $value ) {
		global $wpdb;
		if ( self::claimed( $kind, $value ) ) {
			return;
		}
		$wpdb->insert(
			pkc_table( 'trial_claims' ),
			array(
				'kind'       => $kind,
				'hash'       => self::hash_claim( $value ),
				'created_at' => pkc_now(),
			)
		);
	}

	/**
	 * Log in with email and password.
	 *
	 * @return array|WP_Error
	 */
	public static function login( $in ) {
		$email    = sanitize_email( $in['email'] ?? '' );
		$password = (string) ( $in['password'] ?? '' );
		$max      = max( 3, (int) pkc_setting( 'login_attempts', 5 ) );

		if ( ! PKC_Rate_Limit::hit( 'login_ip', pkc_ip(), $max * 3, 15 * MINUTE_IN_SECONDS )
			|| ! PKC_Rate_Limit::hit( 'login_email', $email, $max, 15 * MINUTE_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		if ( ! is_email( $email ) || '' === $password ) {
			return self::err( __( 'Please enter your email and password.', 'pikacart' ) );
		}

		$user = wp_authenticate( $email, $password );
		if ( is_wp_error( $user ) ) {
			return self::err( __( 'The email or password is not correct.', 'pikacart' ), '', 401 );
		}

		if ( PKC_Roles::is_org_user( $user ) ) {
			$org = PKC_Organisations::get_by_user( $user->ID );
			if ( $org && 'suspended' === $org->status ) {
				return self::err(
					sprintf(
						/* translators: %s: support email */
						__( 'This account is suspended. Please contact %s for help.', 'pikacart' ),
						pkc_support_email()
					),
					'',
					403
				);
			}
		}

		PKC_Rate_Limit::clear( 'login_email', $email );
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, ! empty( $in['remember'] ), is_ssl() );
		do_action( 'wp_login', $user->user_login, $user );

		$org = PKC_Organisations::get_by_user( $user->ID );
		if ( $org ) {
			PKC_Activity_Log::add( 'account.login', $org->id );
		}

		$redirect = ( user_can( $user, 'edit_posts' ) && ! $org ) ? admin_url( 'admin.php?page=pikacart' ) : pkc_url( 'app' );
		return array( 'redirect' => $redirect );
	}

	/**
	 * Email verification.
	 */
	public static function send_verification( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		$token = pkc_token( 40 );
		update_user_meta( $user_id, 'pkc_verify_hash', wp_hash_password( $token ) );
		$link = add_query_arg(
			array(
				'uid'   => $user_id,
				'token' => $token,
			),
			pkc_url( 'verify-email' )
		);
		$org = PKC_Organisations::get_by_user( $user_id );
		return PKC_Emails::send(
			$user->user_email,
			'verify',
			array(
				'name' => $user->display_name,
				'org'  => $org ? $org->name : '',
				'link' => $link,
			)
		);
	}

	public static function verify_email( $user_id, $token ) {
		$hash = get_user_meta( $user_id, 'pkc_verify_hash', true );
		if ( ! $hash || ! $token || ! wp_check_password( $token, $hash ) ) {
			return false;
		}
		update_user_meta( $user_id, 'pkc_email_verified', 1 );
		delete_user_meta( $user_id, 'pkc_verify_hash' );
		$org = PKC_Organisations::get_by_user( $user_id );
		if ( $org ) {
			PKC_Activity_Log::add( 'email.verified', $org->id );
		}
		return true;
	}

	public static function is_verified( $user_id ) {
		return (bool) get_user_meta( $user_id, 'pkc_email_verified', true );
	}

	/**
	 * Forgot password: always answers the same way so emails cannot be guessed.
	 */
	public static function forgot( $in ) {
		$email = sanitize_email( $in['email'] ?? '' );
		if ( ! PKC_Rate_Limit::hit( 'forgot', pkc_ip(), 5, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$user = $email ? get_user_by( 'email', $email ) : false;
		if ( $user ) {
			self::send_reset_link( $user );
		}
		return array( 'message' => __( 'If an account exists for this email, we have sent a link to reset the password.', 'pikacart' ) );
	}

	public static function send_reset_link( $user ) {
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return false;
		}
		$link = add_query_arg(
			array(
				'login' => rawurlencode( $user->user_login ),
				'key'   => $key,
			),
			pkc_url( 'reset-password' )
		);
		return PKC_Emails::send(
			$user->user_email,
			'reset_password',
			array(
				'name' => $user->display_name,
				'link' => $link,
			)
		);
	}

	public static function reset( $in ) {
		if ( ! PKC_Rate_Limit::hit( 'reset', pkc_ip(), 10, HOUR_IN_SECONDS ) ) {
			return PKC_Rate_Limit::error();
		}
		$user = check_password_reset_key( (string) ( $in['key'] ?? '' ), (string) ( $in['login'] ?? '' ) );
		if ( is_wp_error( $user ) ) {
			return self::err( __( 'This reset link is invalid or has expired. Please ask for a new one.', 'pikacart' ) );
		}
		$password = (string) ( $in['password'] ?? '' );
		if ( strlen( $password ) < 8 ) {
			return self::err( __( 'Password must be at least 8 characters.', 'pikacart' ), 'password' );
		}
		reset_password( $user, $password );
		$org = PKC_Organisations::get_by_user( $user->ID );
		if ( $org ) {
			PKC_Activity_Log::add( 'password.reset', $org->id );
		}
		return array(
			'message'  => __( 'Your password has been changed. Please log in.', 'pikacart' ),
			'redirect' => pkc_url( 'login' ),
		);
	}

	public static function change_password( $user_id, $current, $new ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! wp_check_password( (string) $current, $user->user_pass, $user_id ) ) {
			return self::err( __( 'Your current password is not correct.', 'pikacart' ), 'current_password' );
		}
		if ( strlen( (string) $new ) < 8 ) {
			return self::err( __( 'New password must be at least 8 characters.', 'pikacart' ), 'new_password' );
		}
		wp_set_password( $new, $user_id );
		// wp_set_password logs the user out; log them back in on this device.
		// Use the new cookie right away so the fresh REST nonce matches it.
		add_action(
			'set_logged_in_cookie',
			function ( $cookie ) {
				$_COOKIE[ LOGGED_IN_COOKIE ] = $cookie;
			}
		);
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		$org = PKC_Organisations::get_by_user( $user_id );
		if ( $org ) {
			PKC_Activity_Log::add( 'password.changed', $org->id );
		}
		return array( 'message' => __( 'Password changed.', 'pikacart' ) );
	}
}
