<?php
/**
 * Authentication: front-end login/register, email verification, password reset,
 * brute-force lockout, admin email OTP (2FA), suspended accounts.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Brute-force protection (applies to wp-login.php and the front-end form)
 * ---------------------------------------------------------------------- */

function dm_lock_key( $username ) {
	return 'dm_lock_' . md5( strtolower( (string) $username ) . '|' . dm_client_ip() );
}

// Runs after core's password check (priority 20) so the lockout cannot be overridden.
add_filter( 'authenticate', 'dm_auth_lockout_check', 30, 3 );
function dm_auth_lockout_check( $user, $username, $password ) {
	if ( empty( $username ) ) {
		return $user;
	}
	$data = get_transient( dm_lock_key( $username ) );
	if ( is_array( $data ) && $data['count'] >= (int) dm_opt( 'login_max_attempts', 5 ) ) {
		$mins = max( 1, (int) ceil( ( $data['until'] - time() ) / 60 ) );
		/* translators: %d minutes */
		return new WP_Error( 'dm_locked', sprintf( __( 'Too many failed attempts. Try again in %d minutes.', 'digimarket' ), $mins ) );
	}
	return $user;
}

add_action( 'wp_login_failed', 'dm_auth_failed' );
function dm_auth_failed( $username ) {
	$key  = dm_lock_key( $username );
	$mins = (int) dm_opt( 'login_lockout_minutes', 15 );
	$data = get_transient( $key );
	$data = is_array( $data ) ? $data : array( 'count' => 0 );
	++$data['count'];
	$data['until'] = time() + $mins * 60;
	set_transient( $key, $data, $mins * 60 );
}

add_action( 'wp_login', 'dm_auth_success', 10, 2 );
function dm_auth_success( $login, $user ) {
	delete_transient( dm_lock_key( $login ) );
	delete_transient( dm_lock_key( $user->user_email ) );
}

/* Suspended users cannot log in (admins excepted). */
add_filter( 'authenticate', 'dm_auth_block_suspended', 40, 1 );
function dm_auth_block_suspended( $user ) {
	if ( $user instanceof WP_User && dm_user_suspended( $user->ID ) && ! user_can( $user, 'manage_options' ) ) {
		return new WP_Error( 'dm_suspended', __( 'This account has been suspended. Please contact support.', 'digimarket' ) );
	}
	return $user;
}

/* -------------------------------------------------------------------------
 * Admin 2FA via emailed one-time code (wp-login.php)
 * ---------------------------------------------------------------------- */

add_action( 'login_form', 'dm_login_otp_field' );
function dm_login_otp_field() {
	if ( ! dm_opt( 'admin_2fa' ) ) {
		return;
	}
	echo '<p><label for="dm_otp">' . esc_html__( 'Admin verification code (if requested)', 'digimarket' ) . '</label><input type="text" name="dm_otp" id="dm_otp" class="input" autocomplete="one-time-code" inputmode="numeric" size="20"></p>';
}

add_filter( 'authenticate', 'dm_auth_admin_otp', 50, 3 );
function dm_auth_admin_otp( $user, $username, $password ) {
	if ( ! dm_opt( 'admin_2fa' ) || ! ( $user instanceof WP_User ) || ! user_can( $user, 'dm_view_marketplace' ) ) {
		return $user;
	}
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return new WP_Error( 'dm_2fa', __( 'XML-RPC login is disabled for marketplace admins.', 'digimarket' ) );
	}
	$key    = 'dm_otp_' . $user->ID;
	$stored = get_transient( $key );
	$given  = isset( $_POST['dm_otp'] ) ? preg_replace( '/\D/', '', wp_unslash( $_POST['dm_otp'] ) ) : '';
	if ( $given && is_array( $stored ) && hash_equals( $stored['hash'], wp_hash( $given ) ) ) {
		delete_transient( $key );
		return $user;
	}
	if ( $given && is_array( $stored ) ) {
		$stored['tries'] = ( $stored['tries'] ?? 0 ) + 1;
		if ( $stored['tries'] >= 5 ) {
			delete_transient( $key );
			return new WP_Error( 'dm_2fa', __( 'Too many wrong codes. Log in again to receive a new code.', 'digimarket' ) );
		}
		set_transient( $key, $stored, 10 * MINUTE_IN_SECONDS );
		return new WP_Error( 'dm_2fa', __( 'That verification code is not correct.', 'digimarket' ) );
	}
	$code = (string) random_int( 100000, 999999 );
	set_transient( $key, array( 'hash' => wp_hash( $code ), 'tries' => 0 ), 10 * MINUTE_IN_SECONDS );
	/* translators: %s code */
	wp_mail( $user->user_email, sprintf( __( '[%s] Admin login code', 'digimarket' ), get_bloginfo( 'name' ) ), sprintf( __( "Your admin login code is: %s\nIt expires in 10 minutes. If you did not try to log in, change your password immediately.", 'digimarket' ), $code ) );
	return new WP_Error( 'dm_2fa', __( 'We emailed you a 6-digit verification code. Enter it in the “Admin verification code” field together with your password and log in again.', 'digimarket' ) );
}

/* -------------------------------------------------------------------------
 * Front-end handlers
 * ---------------------------------------------------------------------- */

function dm_safe_redirect_target( $fallback ) {
	$to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_REQUEST['redirect_to'] ) ) ) : '';
	return $to ? wp_validate_redirect( $to, $fallback ) : $fallback;
}

function dm_do_login() {
	$login    = sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) );
	$password = (string) wp_unslash( $_POST['pwd'] ?? '' );
	if ( '' === $login || '' === $password ) {
		dm_flash( 'error', __( 'Enter your email and password.', 'digimarket' ) );
		dm_back( dm_url( 'login' ) );
	}
	if ( is_email( $login ) ) {
		$u = get_user_by( 'email', $login );
		if ( $u ) {
			$login = $u->user_login;
		}
	}
	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['rememberme'] ),
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		$code = $user->get_error_code();
		$msg  = in_array( $code, array( 'dm_locked', 'dm_suspended', 'dm_2fa' ), true ) ? $user->get_error_message() : __( 'Incorrect email or password.', 'digimarket' );
		dm_flash( 'error', wp_strip_all_tags( $msg ) );
		dm_back( dm_url( 'login' ) );
	}
	$fallback = user_can( $user, 'dm_view_marketplace' ) ? admin_url( 'admin.php?page=dm-marketplace' ) : ( dm_is_seller( $user->ID ) ? dm_url( 'dashboard' ) : dm_url( 'account' ) );
	dm_redirect( dm_safe_redirect_target( $fallback ) );
}

function dm_do_register() {
	if ( ! get_option( 'users_can_register' ) && ! apply_filters( 'dm_force_registration', true ) ) {
		dm_flash( 'error', __( 'Registration is closed.', 'digimarket' ) );
		dm_back();
	}
	// Honeypot.
	if ( ! empty( $_POST['dm_website'] ) ) {
		dm_redirect( home_url( '/' ) );
	}
	$name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$phone    = preg_replace( '/[^0-9+]/', '', (string) wp_unslash( $_POST['phone'] ?? '' ) );
	$password = (string) wp_unslash( $_POST['password'] ?? '' );
	$errors   = array();
	if ( '' === $name ) {
		$errors[] = __( 'Enter your full name.', 'digimarket' );
	}
	if ( ! is_email( $email ) ) {
		$errors[] = __( 'Enter a valid email address.', 'digimarket' );
	} elseif ( email_exists( $email ) ) {
		$errors[] = __( 'An account with this email already exists. Try logging in.', 'digimarket' );
	}
	if ( strlen( $password ) < 8 ) {
		$errors[] = __( 'Password must be at least 8 characters.', 'digimarket' );
	}
	if ( empty( $_POST['agree'] ) ) {
		$errors[] = __( 'Please accept the Terms of Service and Privacy Policy.', 'digimarket' );
	}
	if ( $errors ) {
		foreach ( $errors as $e ) {
			dm_flash( 'error', $e );
		}
		dm_back( dm_url( 'register' ) );
	}
	$base  = sanitize_user( current( explode( '@', $email ) ), true );
	$login = $base ? $base : 'user';
	$i     = 1;
	while ( username_exists( $login ) ) {
		$login = $base . $i++;
	}
	$uid = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => $password,
			'display_name' => $name,
			'first_name'   => $name,
			'role'         => 'subscriber',
		)
	);
	if ( is_wp_error( $uid ) ) {
		dm_flash( 'error', $uid->get_error_message() );
		dm_back( dm_url( 'register' ) );
	}
	update_user_meta( $uid, 'dm_phone', $phone );
	update_user_meta( $uid, 'dm_email_verified', 0 );
	dm_send_verification( $uid );
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true, is_ssl() );
	dm_flash( 'success', __( 'Welcome! We sent a verification link to your email — please confirm it before your first purchase.', 'digimarket' ) );
	$to = ! empty( $_POST['intent'] ) && 'seller' === $_POST['intent'] ? dm_url( 'sell' ) : dm_url( 'account' );
	dm_redirect( dm_safe_redirect_target( $to ) );
}

function dm_email_verified( $uid ) {
	if ( ! dm_opt( 'require_email_verify' ) || user_can( $uid, 'manage_options' ) ) {
		return true;
	}
	$v = get_user_meta( $uid, 'dm_email_verified', true );
	// Users created before the theme (no meta) are treated as verified.
	return '' === $v || (bool) $v;
}

function dm_send_verification( $uid ) {
	$user  = get_userdata( $uid );
	$token = wp_generate_password( 32, false );
	update_user_meta( $uid, 'dm_verify_token', wp_hash( $token ) );
	update_user_meta( $uid, 'dm_verify_expires', time() + 2 * DAY_IN_SECONDS );
	$link = dm_url( 'verify', '', '', array( 'u' => $uid, 'k' => $token ) );
	dm_mail(
		$user->user_email,
		__( 'Confirm your email address', 'digimarket' ),
		/* translators: %s name */
		'<p>' . sprintf( esc_html__( 'Hi %s,', 'digimarket' ), esc_html( $user->display_name ) ) . '</p><p>' . esc_html__( 'Please confirm your email address to start buying and selling.', 'digimarket' ) . '</p>',
		$link,
		__( 'Verify email', 'digimarket' )
	);
}

function dm_handle_verify_email() {
	$uid = absint( $_GET['u'] ?? 0 );
	$key = sanitize_text_field( wp_unslash( $_GET['k'] ?? '' ) );
	if ( ! $uid || ! $key ) {
		return;
	}
	$hash = get_user_meta( $uid, 'dm_verify_token', true );
	$exp  = (int) get_user_meta( $uid, 'dm_verify_expires', true );
	if ( $hash && hash_equals( $hash, wp_hash( $key ) ) && $exp > time() ) {
		update_user_meta( $uid, 'dm_email_verified', 1 );
		delete_user_meta( $uid, 'dm_verify_token' );
		dm_flash( 'success', __( 'Email verified — thank you!', 'digimarket' ) );
		dm_redirect( is_user_logged_in() ? dm_url( 'account' ) : dm_url( 'login' ) );
	}
	$GLOBALS['dm_verify_failed'] = true;
}

function dm_do_resend_verification() {
	dm_require_login();
	$last = (int) get_user_meta( get_current_user_id(), 'dm_verify_sent', true );
	if ( $last > time() - 60 ) {
		dm_flash( 'warning', __( 'Please wait a minute before requesting another email.', 'digimarket' ) );
	} else {
		dm_send_verification( get_current_user_id() );
		update_user_meta( get_current_user_id(), 'dm_verify_sent', time() );
		dm_flash( 'success', __( 'Verification email sent.', 'digimarket' ) );
	}
	dm_back( dm_url( 'account' ) );
}

function dm_do_forgot() {
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$user  = $email ? get_user_by( 'email', $email ) : false;
	$rl    = 'dm_forgot_' . md5( dm_client_ip() );
	$count = (int) get_transient( $rl );
	if ( $count >= 5 ) {
		dm_flash( 'error', __( 'Too many requests. Try again later.', 'digimarket' ) );
		dm_back( dm_url( 'forgot' ) );
	}
	set_transient( $rl, $count + 1, HOUR_IN_SECONDS );
	if ( $user ) {
		$token = wp_generate_password( 32, false );
		update_user_meta( $user->ID, 'dm_reset_token', wp_hash( $token ) );
		update_user_meta( $user->ID, 'dm_reset_expires', time() + 30 * MINUTE_IN_SECONDS );
		dm_mail(
			$user->user_email,
			__( 'Reset your password', 'digimarket' ),
			'<p>' . esc_html__( 'Someone requested a password reset for your account. The link below expires in 30 minutes. If this was not you, ignore this email.', 'digimarket' ) . '</p>',
			dm_url( 'reset', '', '', array( 'u' => $user->ID, 'k' => $token ) ),
			__( 'Choose a new password', 'digimarket' )
		);
	}
	dm_flash( 'success', __( 'If an account exists for that email, a reset link is on its way. It expires in 30 minutes.', 'digimarket' ) );
	dm_redirect( dm_url( 'login' ) );
}

function dm_reset_token_valid( $uid, $key ) {
	$hash = get_user_meta( $uid, 'dm_reset_token', true );
	$exp  = (int) get_user_meta( $uid, 'dm_reset_expires', true );
	return $uid && $key && $hash && hash_equals( $hash, wp_hash( $key ) ) && $exp > time();
}

function dm_do_reset() {
	$uid  = absint( $_POST['u'] ?? 0 );
	$key  = sanitize_text_field( wp_unslash( $_POST['k'] ?? '' ) );
	$pass = (string) wp_unslash( $_POST['password'] ?? '' );
	if ( ! dm_reset_token_valid( $uid, $key ) ) {
		dm_flash( 'error', __( 'This reset link is invalid or has expired. Request a new one.', 'digimarket' ) );
		dm_redirect( dm_url( 'forgot' ) );
	}
	if ( strlen( $pass ) < 8 || $pass !== (string) wp_unslash( $_POST['password2'] ?? '' ) ) {
		dm_flash( 'error', __( 'Passwords must match and be at least 8 characters.', 'digimarket' ) );
		dm_back();
	}
	wp_set_password( $pass, $uid );
	delete_user_meta( $uid, 'dm_reset_token' );
	delete_user_meta( $uid, 'dm_reset_expires' );
	delete_transient( dm_lock_key( get_userdata( $uid )->user_login ) );
	dm_flash( 'success', __( 'Password updated. You can log in now.', 'digimarket' ) );
	dm_redirect( dm_url( 'login' ) );
}

function dm_do_logout() {
	wp_logout();
	dm_redirect( home_url( '/' ) );
}

/* Email changes in profile re-require verification (handled in buyer.php). */
