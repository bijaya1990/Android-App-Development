<?php
/**
 * Branded HTML emails. Every text is editable in Settings > Emails.
 *
 * Placeholders: {name} {org} {site} {link} {amount} {invoice_no} {date}
 * {support_email} {reason} {minutes} {price}
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Emails {

	public static function init() {
		// Nothing to hook yet; kept for future filters.
	}

	public static function defaults() {
		return array(
			'verify'          => array(
				'label'   => __( 'Verify email', 'pikacart' ),
				'subject' => 'Please verify your email for {site}',
				'body'    => "Hello {name},\n\nThank you for registering {org} on {site}. Please confirm your email address by clicking the button below.\n\n[button]Verify my email[/button]\n\nIf you did not create this account, you can ignore this email.",
			),
			'welcome'         => array(
				'label'   => __( 'Welcome', 'pikacart' ),
				'subject' => 'Welcome to {site}! Your free trial has started',
				'body'    => "Hello {name},\n\nWelcome to {site}. Your free trial for {org} has started and lasts {minutes} minutes. Every feature is open, and exports carry a trial watermark.\n\n[button]Open my dashboard[/button]\n\nAfter the trial, continue for just {price} per month.",
			),
			'trial_ending'    => array(
				'label'   => __( 'Trial ending soon', 'pikacart' ),
				'subject' => 'Your {site} trial ends in {minutes} minutes',
				'body'    => "Hello {name},\n\nYour free trial for {org} ends in about {minutes} minutes. Subscribe now for {price} per month to keep creating, downloading and printing ID cards without a watermark.\n\n[button]Subscribe now[/button]",
			),
			'trial_ended'     => array(
				'label'   => __( 'Trial ended', 'pikacart' ),
				'subject' => 'Your {site} free trial has ended',
				'body'    => "Hello {name},\n\nYour free trial for {org} has ended. Your designs and data are saved. Subscribe for {price} per month to continue.\n\n[button]Subscribe now[/button]",
			),
			'payment_success' => array(
				'label'   => __( 'Payment successful', 'pikacart' ),
				'subject' => 'Payment received: {amount} (Invoice {invoice_no})',
				'body'    => "Hello {name},\n\nThank you! We received your payment of {amount} for {org}. Your plan is active until {date}.\n\nInvoice number: {invoice_no}. You can download the invoice from Subscription in your dashboard.\n\n[button]View my subscription[/button]",
			),
			'payment_failed'  => array(
				'label'   => __( 'Payment failed', 'pikacart' ),
				'subject' => 'Your {site} payment did not go through',
				'body'    => "Hello {name},\n\nWe could not collect your payment for {org}. {reason}\n\nPlease try again or use another payment method so your cards keep working.\n\n[button]Pay now[/button]",
			),
			'expiring_soon'   => array(
				'label'   => __( 'Subscription expiring', 'pikacart' ),
				'subject' => 'Your {site} plan ends on {date}',
				'body'    => "Hello {name},\n\nYour plan for {org} ends on {date}. Renew now to avoid any break in creating, downloading and printing cards.\n\n[button]Renew now[/button]",
			),
			'suspended'       => array(
				'label'   => __( 'Account suspended', 'pikacart' ),
				'subject' => 'Your {site} account has been suspended',
				'body'    => "Hello {name},\n\nYour {site} account for {org} has been suspended.\n\nReason: {reason}\n\nIf you think this is a mistake, please write to {support_email}.",
			),
			'reactivated'     => array(
				'label'   => __( 'Account reactivated', 'pikacart' ),
				'subject' => 'Your {site} account is active again',
				'body'    => "Hello {name},\n\nGood news: your {site} account for {org} has been reactivated. You can log in and continue.\n\n[button]Open my dashboard[/button]",
			),
			'reset_password'  => array(
				'label'   => __( 'Reset password', 'pikacart' ),
				'subject' => 'Reset your {site} password',
				'body'    => "Hello {name},\n\nWe received a request to reset your password. Click the button below to choose a new password. The link works for 24 hours.\n\n[button]Choose a new password[/button]\n\nIf you did not ask for this, you can ignore this email.",
			),
		);
	}

	/**
	 * Send one template to an organisation's contact email.
	 */
	public static function send_to_org( $org, $key, $vars = array() ) {
		if ( ! $org || ! is_email( $org->email ) ) {
			return false;
		}
		$vars = array_merge(
			array(
				'name' => $org->contact_name ? $org->contact_name : $org->name,
				'org'  => $org->name,
			),
			$vars
		);
		return self::send( $org->email, $key, $vars );
	}

	/**
	 * Send one template to any address.
	 */
	public static function send( $to, $key, $vars = array() ) {
		$defaults = self::defaults();
		if ( ! isset( $defaults[ $key ] ) ) {
			return false;
		}
		$site = pkc_setting( 'site_name', 'Pikacart' );
		$vars = array_merge(
			array(
				'site'          => $site,
				'support_email' => pkc_support_email(),
				'link'          => pkc_url( 'app' ),
				'minutes'       => (int) pkc_setting( 'trial_minutes', 120 ),
				'price'         => pkc_money( PKC_Billing::default_plan_price() ),
				'reason'        => '',
			),
			$vars
		);

		$subject = self::fill( (string) pkc_setting( 'email_' . $key . '_subject', $defaults[ $key ]['subject'] ), $vars, false );
		$body    = (string) pkc_setting( 'email_' . $key . '_body', $defaults[ $key ]['body'] );
		$html    = self::wrap( self::fill( $body, $vars, true ), $vars['link'] );

		$from_email = pkc_setting( 'email_from_email' );
		$from_email = is_email( $from_email ) ? $from_email : pkc_support_email();
		$from_name  = wp_specialchars_decode( (string) pkc_setting( 'email_from_name', $site ), ENT_QUOTES );
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', str_replace( array( "\r", "\n", '<', '>' ), '', $from_name ), $from_email ),
			'Reply-To: ' . pkc_support_email(),
		);

		return wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), $html, $headers );
	}

	/**
	 * Replace {placeholders}. Values are escaped for HTML bodies.
	 */
	private static function fill( $text, $vars, $html ) {
		$map = array();
		foreach ( $vars as $k => $v ) {
			$map[ '{' . $k . '}' ] = $html ? esc_html( (string) $v ) : wp_strip_all_tags( (string) $v );
		}
		return strtr( $text, $map );
	}

	/**
	 * Turn the plain text body into a clean branded HTML email.
	 */
	private static function wrap( $body, $link ) {
		$brand = sanitize_hex_color( (string) pkc_setting( 'brand_color', '#4F46E5' ) );
		$brand = $brand ? $brand : '#4F46E5';
		$site  = esc_html( pkc_setting( 'site_name', 'Pikacart' ) );
		$tag   = esc_html( pkc_setting( 'tagline', '' ) );

		$body = wp_kses_post( $body );
		$body = preg_replace_callback(
			'/\[button\](.*?)\[\/button\]/s',
			function ( $m ) use ( $link, $brand ) {
				return '<p style="margin:24px 0"><a href="' . esc_url( $link ) . '" style="background:' . $brand . ';color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;display:inline-block">' . $m[1] . '</a></p>';
			},
			$body
		);
		$body = wpautop( $body );

		$footer = sprintf(
			/* translators: %s: support email */
			esc_html__( 'Need help? Write to %s', 'pikacart' ),
			'<a href="mailto:' . esc_attr( pkc_support_email() ) . '" style="color:' . $brand . '">' . esc_html( pkc_support_email() ) . '</a>'
		);

		return '<!doctype html><html><body style="margin:0;background:#f4f5f9;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2433">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f9;padding:24px 12px"><tr><td align="center">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden">'
			. '<tr><td style="background:' . $brand . ';padding:20px 28px;color:#ffffff"><strong style="font-size:20px">' . $site . '</strong>' . ( $tag ? '<div style="opacity:.85;font-size:13px">' . $tag . '</div>' : '' ) . '</td></tr>'
			. '<tr><td style="padding:28px;font-size:15px;line-height:1.6">' . $body . '</td></tr>'
			. '<tr><td style="padding:16px 28px;background:#fafafc;color:#6b7080;font-size:12px">' . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}
}
