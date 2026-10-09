<?php
/**
 * All dashboard settings: definitions, defaults, reading and saving.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Settings {

	const OPTION = 'pkc_settings';

	/** @var array|null */
	private static $cache = null;

	/**
	 * Settings tabs shown in wp-admin.
	 */
	public static function tabs() {
		return array(
			'general'     => __( 'General', 'pikacart' ),
			'trial'       => __( 'Free plan and Plans', 'pikacart' ),
			'razorpay'    => __( 'Razorpay', 'pikacart' ),
			'emails'      => __( 'Emails', 'pikacart' ),
			'homepage'    => __( 'Homepage', 'pikacart' ),
			'seo'         => __( 'SEO', 'pikacart' ),
			'legal'       => __( 'Legal pages', 'pikacart' ),
			'limits'      => __( 'Limits', 'pikacart' ),
			'maintenance' => __( 'Maintenance and data', 'pikacart' ),
		);
	}

	/**
	 * Every field: key => [tab, type, label, default, help].
	 */
	public static function fields() {
		$f = array(
			// General.
			'site_name'          => array( 'general', 'text', __( 'Business name', 'pikacart' ), 'Pikacart', __( 'Shown on invoices, emails and the site.', 'pikacart' ) ),
			'tagline'            => array( 'general', 'text', __( 'Tagline', 'pikacart' ), 'Expert in ID Card Industry', '' ),
			'logo_url'           => array( 'general', 'media', __( 'Logo', 'pikacart' ), '', __( 'Upload a PNG or SVG logo. Leave empty to show the text logo.', 'pikacart' ) ),
			'brand_color'        => array( 'general', 'color', __( 'Brand colour', 'pikacart' ), '#4F46E5', __( 'Main colour of buttons and highlights.', 'pikacart' ) ),
			'accent_color'       => array( 'general', 'color', __( 'Accent colour', 'pikacart' ), '#F97316', '' ),
			'contact_email'      => array( 'general', 'email', __( 'Support email', 'pikacart' ), 'contact@pikacart.in', '' ),
			'contact_phone'      => array( 'general', 'text', __( 'Support phone', 'pikacart' ), '', '' ),
			'business_address'   => array( 'general', 'textarea', __( 'Business address', 'pikacart' ), '', __( 'Printed on invoices.', 'pikacart' ) ),
			'gstin'              => array( 'general', 'text', __( 'GSTIN (optional)', 'pikacart' ), '', __( 'If filled, invoices show your GSTIN and the GST included in the price.', 'pikacart' ) ),
			'gst_rate'           => array( 'general', 'number', __( 'GST rate %', 'pikacart' ), 18, __( 'Prices are treated as inclusive of GST.', 'pikacart' ) ),
			'invoice_prefix'     => array( 'general', 'text', __( 'Invoice number prefix', 'pikacart' ), 'PKC-', '' ),
			'social_facebook'    => array( 'general', 'url', __( 'Facebook link', 'pikacart' ), '', '' ),
			'social_instagram'   => array( 'general', 'url', __( 'Instagram link', 'pikacart' ), '', '' ),
			'social_youtube'     => array( 'general', 'url', __( 'YouTube link', 'pikacart' ), '', '' ),
			'social_whatsapp'    => array( 'general', 'text', __( 'WhatsApp number', 'pikacart' ), '', '' ),

			// Free plan / trial.
			'free_mode'          => array( 'trial', 'select', __( 'Free plan mode', 'pikacart' ), 'forever', __( 'Lifetime free: every account can use Pikacart free forever, and downloads carry the watermark. Trial: free only for the trial length, then locked until they pay.', 'pikacart' ), array( 'forever' => __( 'Lifetime free with watermark (recommended)', 'pikacart' ), 'trial' => __( 'Time-limited trial, then locked', 'pikacart' ) ) ),
			'watermark_text'     => array( 'trial', 'text', __( 'Watermark text on free cards', 'pikacart' ), 'Made with www.pikacart.in', __( 'Drawn across every free card and on a ribbon at the bottom. Paid plans have no watermark.', 'pikacart' ) ),
			'trial_minutes'      => array( 'trial', 'number', __( 'Trial length in minutes (trial mode only)', 'pikacart' ), 120, __( '120 minutes = 2 hours.', 'pikacart' ) ),
			'trial_warn_minutes' => array( 'trial', 'number', __( 'Send "trial ending" email this many minutes before the end', 'pikacart' ), 30, '' ),
			'expiry_warn_days'   => array( 'trial', 'number', __( 'Send "subscription expiring" email this many days before the end', 'pikacart' ), 3, '' ),
			'grace_hours'        => array( 'trial', 'number', __( 'Autopay grace period (hours)', 'pikacart' ), 48, __( 'Keeps an autopay account active while Razorpay retries a renewal.', 'pikacart' ) ),

			// Razorpay.
			'rzp_mode'           => array( 'razorpay', 'select', __( 'Mode', 'pikacart' ), 'test', '', array( 'test' => __( 'Test mode', 'pikacart' ), 'live' => __( 'Live mode', 'pikacart' ) ) ),
			'rzp_key_test'       => array( 'razorpay', 'text', __( 'Test Key ID', 'pikacart' ), '', __( 'Starts with rzp_test_', 'pikacart' ) ),
			'rzp_secret_test'    => array( 'razorpay', 'secret', __( 'Test Key Secret', 'pikacart' ), '', '' ),
			'rzp_key_live'       => array( 'razorpay', 'text', __( 'Live Key ID', 'pikacart' ), '', __( 'Starts with rzp_live_', 'pikacart' ) ),
			'rzp_secret_live'    => array( 'razorpay', 'secret', __( 'Live Key Secret', 'pikacart' ), '', '' ),
			'rzp_webhook_secret' => array( 'razorpay', 'secret', __( 'Webhook Secret', 'pikacart' ), '', __( 'The secret you typed when creating the webhook in Razorpay.', 'pikacart' ) ),
			'rzp_onetime'        => array( 'razorpay', 'checkbox', __( 'Offer one-time payment', 'pikacart' ), 1, __( 'Lets users whose bank does not support autopay pay once for one period.', 'pikacart' ) ),

			// Homepage.
			'home_hero_title'    => array( 'homepage', 'text', __( 'Hero headline', 'pikacart' ), 'Professional ID cards in minutes', '' ),
			'home_hero_text'     => array( 'homepage', 'textarea', __( 'Hero text', 'pikacart' ), 'Design, manage and print ID cards for your school, college or company. Import from Excel, verify with real QR codes, print on any holder size.', '' ),
			'home_hero_button'   => array( 'homepage', 'text', __( 'Hero button text', 'pikacart' ), 'Start Free', '' ),

			// SEO.
			'seo_gsc'            => array( 'seo', 'text', __( 'Google Search Console verification code', 'pikacart' ), '', __( 'Only the code inside content="...".', 'pikacart' ) ),
			'seo_ga'             => array( 'seo', 'text', __( 'Google Analytics ID', 'pikacart' ), '', __( 'Example: G-XXXXXXX', 'pikacart' ) ),
			'seo_head_scripts'   => array( 'seo', 'code', __( 'Custom header scripts', 'pikacart' ), '', __( 'Added to the public pages only, never to the app.', 'pikacart' ) ),
			'seo_footer_scripts' => array( 'seo', 'code', __( 'Custom footer scripts', 'pikacart' ), '', '' ),

			// Limits.
			'upload_max_mb'      => array( 'limits', 'number', __( 'Maximum image upload size (MB)', 'pikacart' ), 5, '' ),
			'members_per_project' => array( 'limits', 'number', __( 'Maximum people per card project', 'pikacart' ), 5000, '' ),
			'dod_hours'          => array( 'limits', 'number', __( 'Design on Demand: promised delivery time (hours)', 'pikacart' ), 6, __( 'Shown to customers as "Your design will be live in your account within X hours".', 'pikacart' ) ),
			'dod_notify_email'   => array( 'limits', 'email', __( 'Send new Design on Demand requests to', 'pikacart' ), '', __( 'Leave empty to use the support email.', 'pikacart' ) ),
			'login_attempts'     => array( 'limits', 'number', __( 'Login attempts allowed per 15 minutes', 'pikacart' ), 5, '' ),

			// Maintenance.
			'maintenance'        => array( 'maintenance', 'checkbox', __( 'Maintenance mode', 'pikacart' ), 0, __( 'Visitors and users see a "back soon" page. Super Admins still see the site.', 'pikacart' ) ),
			'maintenance_text'   => array( 'maintenance', 'textarea', __( 'Maintenance message', 'pikacart' ), 'We are making Pikacart better. Please check back in a few minutes.', '' ),
			'remove_data'        => array( 'maintenance', 'checkbox', __( 'Remove all data when the plugin is deleted', 'pikacart' ), 0, __( 'Leave this off unless you really want to erase every account, card and payment record.', 'pikacart' ) ),
		);

		foreach ( PKC_Emails::defaults() as $key => $mail ) {
			$f[ 'email_' . $key . '_subject' ] = array( 'emails', 'text', sprintf( /* translators: %s: email name */ __( '%s: subject', 'pikacart' ), $mail['label'] ), $mail['subject'], '' );
			$f[ 'email_' . $key . '_body' ]    = array( 'emails', 'emailbody', sprintf( /* translators: %s: email name */ __( '%s: message', 'pikacart' ), $mail['label'] ), $mail['body'], '' );
		}
		$f['email_support_reply'] = array( 'emails', 'checkbox', __( 'Email customers when support replies and they are not online', 'pikacart' ), 1, '' );
		$f['email_from_name']  = array( 'emails', 'text', __( 'Sender name', 'pikacart' ), 'Pikacart', '' );
		$f['email_from_email'] = array( 'emails', 'email', __( 'Sender email', 'pikacart' ), '', __( 'Leave empty to use the support email. Use an address on your own domain.', 'pikacart' ) );

		return $f;
	}

	public static function defaults() {
		$out = array();
		foreach ( self::fields() as $key => $def ) {
			$out[ $key ] = $def[3];
		}
		return $out;
	}

	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	public static function get( $key, $fallback = null ) {
		$all = self::all();
		if ( array_key_exists( $key, $all ) && '' !== $all[ $key ] && null !== $all[ $key ] ) {
			return $all[ $key ];
		}
		return null === $fallback && array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Save the fields of one tab from posted data.
	 */
	public static function save_tab( $tab, $posted ) {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();

		foreach ( self::fields() as $key => $def ) {
			if ( $def[0] !== $tab ) {
				continue;
			}
			$type = $def[1];
			$raw  = isset( $posted[ $key ] ) ? wp_unslash( $posted[ $key ] ) : null;

			switch ( $type ) {
				case 'checkbox':
					$saved[ $key ] = $raw ? 1 : 0;
					break;
				case 'secret':
					// Empty means "keep the saved secret".
					if ( null !== $raw && '' !== trim( $raw ) ) {
						$saved[ $key ] = sanitize_text_field( $raw );
					}
					break;
				case 'number':
					$saved[ $key ] = max( 0, (float) $raw );
					break;
				case 'email':
					$saved[ $key ] = sanitize_email( (string) $raw );
					break;
				case 'url':
				case 'media':
					$saved[ $key ] = esc_url_raw( (string) $raw );
					break;
				case 'color':
					$color         = sanitize_hex_color( (string) $raw );
					$saved[ $key ] = $color ? $color : $def[3];
					break;
				case 'select':
					$options       = isset( $def[5] ) ? $def[5] : array();
					$saved[ $key ] = array_key_exists( (string) $raw, $options ) ? (string) $raw : $def[3];
					break;
				case 'textarea':
					$saved[ $key ] = sanitize_textarea_field( (string) $raw );
					break;
				case 'emailbody':
					$saved[ $key ] = wp_kses_post( (string) $raw );
					break;
				case 'code':
					// Only Super Admins with unfiltered_html may store scripts.
					$saved[ $key ] = current_user_can( 'unfiltered_html' ) ? (string) $raw : wp_kses_post( (string) $raw );
					break;
				default:
					$saved[ $key ] = sanitize_text_field( (string) $raw );
			}
		}

		update_option( self::OPTION, $saved );
		self::$cache = null;
	}
}
