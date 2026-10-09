<?php
/**
 * Shortcodes used on the info pages:
 * [pikacart_contact_form] [pikacart_contact_details] [pikacart_pricing]
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Shortcodes {

	public static function init() {
		add_shortcode( 'pikacart_contact_form', array( __CLASS__, 'contact_form' ) );
		add_shortcode( 'pikacart_contact_details', array( __CLASS__, 'contact_details' ) );
		add_shortcode( 'pikacart_pricing', array( __CLASS__, 'pricing' ) );
	}

	private static function enqueue() {
		PKC_Assets::register();
		wp_enqueue_style( 'pkc-public' );
		if ( ! wp_script_is( 'pkc-public', 'enqueued' ) ) {
			wp_enqueue_script( 'pkc-public' );
			PKC_Assets::print_config( 'pkc-public' );
		}
	}

	public static function contact_form() {
		self::enqueue();
		ob_start();
		?>
		<form class="pkc-public-form pkc-contact-form" novalidate>
			<div class="pkc-grid-2">
				<p class="pkc-field"><label for="pkc-c-name"><?php esc_html_e( 'Your name', 'pikacart' ); ?></label><input id="pkc-c-name" name="name" type="text" autocomplete="name" required></p>
				<p class="pkc-field"><label for="pkc-c-email"><?php esc_html_e( 'Email', 'pikacart' ); ?></label><input id="pkc-c-email" name="email" type="email" autocomplete="email" required></p>
			</div>
			<p class="pkc-field"><label for="pkc-c-phone"><?php esc_html_e( 'Phone (optional)', 'pikacart' ); ?></label><input id="pkc-c-phone" name="phone" type="tel" autocomplete="tel"></p>
			<p class="pkc-field"><label for="pkc-c-msg"><?php esc_html_e( 'Message', 'pikacart' ); ?></label><textarea id="pkc-c-msg" name="message" rows="5" required></textarea></p>
			<p class="pkc-hp" aria-hidden="true"><label for="pkc-c-web"><?php esc_html_e( 'Leave this empty', 'pikacart' ); ?></label><input id="pkc-c-web" name="website" type="text" tabindex="-1" autocomplete="off"></p>
			<input type="hidden" name="stamp" value="<?php echo esc_attr( PKC_Accounts::form_stamp() ); ?>">
			<div class="pkc-form-msg" role="alert" aria-live="polite"></div>
			<button type="submit" class="pkc-btn pkc-btn-primary"><?php esc_html_e( 'Send message', 'pikacart' ); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function contact_details() {
		self::enqueue();
		$email = pkc_support_email();
		$phone = (string) pkc_setting( 'contact_phone', '' );
		$addr  = (string) pkc_setting( 'business_address', '' );
		$out   = '<ul class="pkc-contact-list">';
		$out  .= '<li><strong>' . esc_html__( 'Email', 'pikacart' ) . ':</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></li>';
		if ( $phone ) {
			$out .= '<li><strong>' . esc_html__( 'Phone', 'pikacart' ) . ':</strong> <a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></li>';
		}
		if ( $addr ) {
			$out .= '<li><strong>' . esc_html__( 'Address', 'pikacart' ) . ':</strong> ' . nl2br( esc_html( $addr ) ) . '</li>';
		}
		return $out . '</ul>';
	}

	public static function pricing() {
		self::enqueue();
		$plans   = PKC_Billing::plans();
		$minutes = (int) pkc_setting( 'trial_minutes', 120 );
		$trial   = ( 0 === $minutes % 60 )
			/* translators: %d: hours */
			? sprintf( _n( '%d hour', '%d hours', $minutes / 60, 'pikacart' ), $minutes / 60 )
			/* translators: %d: minutes */
			: sprintf( _n( '%d minute', '%d minutes', $minutes, 'pikacart' ), $minutes );
		$features = array(
			__( 'Unlimited ID cards and projects', 'pikacart' ),
			__( 'All categories and designs', 'pikacart' ),
			__( 'Excel import and bulk photo upload', 'pikacart' ),
			__( 'Real QR verification and barcodes', 'pikacart' ),
			__( 'PDF, JPG and PNG at 300 DPI', 'pikacart' ),
			__( 'Print sheets with cut marks', 'pikacart' ),
			__( 'Own design upload and Design on Demand', 'pikacart' ),
			__( 'Email support', 'pikacart' ),
		);
		ob_start();
		?>
		<div class="pkc-pricing">
			<div class="pkc-price-card pkc-price-trial">
				<h3><?php esc_html_e( 'Free trial', 'pikacart' ); ?></h3>
				<p class="pkc-price"><?php esc_html_e( 'Free', 'pikacart' ); ?></p>
				<p class="pkc-price-note">
					<?php
					/* translators: %s: trial length */
					echo esc_html( sprintf( __( 'Every feature for %s. Exports carry a watermark.', 'pikacart' ), $trial ) );
					?>
				</p>
				<a class="pkc-btn pkc-btn-ghost" href="<?php echo esc_url( pkc_url( 'register' ) ); ?>"><?php esc_html_e( 'Start free trial', 'pikacart' ); ?></a>
			</div>
			<?php foreach ( $plans as $plan ) : ?>
			<div class="pkc-price-card pkc-price-main">
				<h3><?php echo esc_html( $plan->name ); ?></h3>
				<p class="pkc-price"><?php echo esc_html( pkc_money( $plan->price_paise ) ); ?><span>
					<?php
					/* translators: %d: number of days */
					echo esc_html( 30 === (int) $plan->period_days ? __( '/ month', 'pikacart' ) : sprintf( __( '/ %d days', 'pikacart' ), $plan->period_days ) );
					?>
				</span></p>
				<?php if ( $plan->description ) : ?>
					<p class="pkc-price-note"><?php echo esc_html( $plan->description ); ?></p>
				<?php endif; ?>
				<ul>
					<?php foreach ( $features as $feature ) : ?>
						<li><?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>
				<a class="pkc-btn pkc-btn-primary" href="<?php echo esc_url( pkc_url( 'register' ) ); ?>"><?php esc_html_e( 'Get started', 'pikacart' ); ?></a>
				<p class="pkc-price-small"><?php esc_html_e( 'Secure payment by Razorpay. UPI autopay, cards and net banking. Cancel anytime.', 'pikacart' ); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
