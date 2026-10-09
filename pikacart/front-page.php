<?php
/**
 * Homepage: hero with a fan of sample cards, categories, how it works,
 * features, pricing and FAQ. Texts come from Pikacart Settings > Homepage.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pkc_cats  = pkc_theme_categories();
$pkc_price = class_exists( 'PKC_Billing' ) ? pkc_money( PKC_Billing::default_plan_price() ) : '₹59';
$pkc_mins  = (int) pkc_theme_setting( 'trial_minutes', 120 );
$pkc_trial = ( 0 === $pkc_mins % 60 )
	/* translators: %d: hours */
	? sprintf( _n( '%d hour', '%d hours', $pkc_mins / 60, 'pikacart' ), $pkc_mins / 60 )
	/* translators: %d: minutes */
	: sprintf( _n( '%d minute', '%d minutes', $pkc_mins, 'pikacart' ), $pkc_mins );
$pkc_forever = ! function_exists( 'pkc_setting' ) || 'trial' !== pkc_setting( 'free_mode', 'forever' );
$pkc_colors = array( '#1E3A8A', '#9F1239', '#065F46', '#C2410C', '#5B21B6', '#0369A1', '#111827', '#115E59', '#7F1D1D', '#334155' );
?>

<section class="hero">
	<div class="wrap hero-grid">
		<div class="hero-copy">
			<span class="hero-badge"><?php echo pkc_theme_icon( 'sparkles' ); // phpcs:ignore ?> <?php echo esc_html( pkc_theme_setting( 'tagline', 'Expert in ID Card Industry' ) ); ?></span>
			<h1><?php echo esc_html( pkc_theme_setting( 'home_hero_title', __( 'Professional ID cards in minutes', 'pikacart' ) ) ); ?></h1>
			<p class="hero-text"><?php echo esc_html( pkc_theme_setting( 'home_hero_text', '' ) ); ?></p>
			<div class="hero-cta">
				<a class="t-btn t-btn-primary t-btn-lg" href="<?php echo esc_url( pkc_theme_url( 'register' ) ); ?>"><?php echo esc_html( pkc_theme_setting( 'home_hero_button', __( 'Start Free', 'pikacart' ) ) ); ?></a>
				<a class="t-btn t-btn-ghost t-btn-lg" href="#how"><?php esc_html_e( 'See how it works', 'pikacart' ); ?></a>
			</div>
			<p class="hero-note">
				<?php
				echo esc_html(
					$pkc_forever
						/* translators: %s: price */
						? sprintf( __( 'Free forever with a small watermark. %s per month for clean cards.', 'pikacart' ), $pkc_price )
						/* translators: 1: trial length, 2: price */
						: sprintf( __( '%1$s free, then %2$s per month. No card needed to try.', 'pikacart' ), ucfirst( $pkc_trial ), $pkc_price )
				);
				?>
			</p>
		</div>
		<div class="hero-art">
			<div class="fan">
				<?php
				echo pkc_theme_sample_card( 'a', 'Green Valley School', 'Aarav Mehta', 'Class VIII · B', '#065F46' ); // phpcs:ignore
				echo pkc_theme_sample_card( 'b', 'Northstar College', 'Priya Nair', 'B.Sc. Computer Science', 'var(--pkc-brand)' ); // phpcs:ignore
				echo pkc_theme_sample_card( 'c', 'Orbit Technologies', 'Rahul Verma', 'Product Designer', '#C2410C' ); // phpcs:ignore
				?>
			</div>
		</div>
	</div>
</section>

<section class="trust-strip">
	<div class="wrap trust-inner">
		<span><?php echo pkc_theme_icon( 'check' ); // phpcs:ignore ?> <?php esc_html_e( 'Schools & colleges', 'pikacart' ); ?></span>
		<span><?php echo pkc_theme_icon( 'check' ); // phpcs:ignore ?> <?php esc_html_e( 'Companies & hospitals', 'pikacart' ); ?></span>
		<span><?php echo pkc_theme_icon( 'check' ); // phpcs:ignore ?> <?php esc_html_e( 'Events & clubs', 'pikacart' ); ?></span>
		<span><?php echo pkc_theme_icon( 'shield' ); // phpcs:ignore ?> <?php esc_html_e( 'Secure payment by Razorpay', 'pikacart' ); ?></span>
	</div>
</section>

<?php if ( $pkc_cats ) : ?>
<section class="section" id="categories">
	<div class="wrap">
		<div class="section-head">
			<span class="eyebrow"><?php esc_html_e( 'Categories', 'pikacart' ); ?></span>
			<h2><?php esc_html_e( 'ID cards for every kind of organisation', 'pikacart' ); ?></h2>
			<p><?php esc_html_e( 'Each category has ready designs for students, staff, visitors and more — from simple to premium.', 'pikacart' ); ?></p>
		</div>
		<div class="cat-cards">
			<?php foreach ( $pkc_cats as $pkc_i => $pkc_cat ) : ?>
				<a class="cat-card" href="<?php echo esc_url( pkc_theme_url( 'register' ) ); ?>">
					<div class="cat-card-art">
						<?php echo pkc_theme_sample_card( 'mini', $pkc_cat['name'], __( 'Your Name', 'pikacart' ), $pkc_cat['subtypes'][0]['name'] ?? '', $pkc_colors[ $pkc_i % count( $pkc_colors ) ] ); // phpcs:ignore ?>
					</div>
					<h3><?php echo esc_html( $pkc_cat['name'] ); ?></h3>
					<p><?php echo esc_html( implode( ' · ', wp_list_pluck( $pkc_cat['subtypes'], 'name' ) ) ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="section section-alt" id="how">
	<div class="wrap">
		<div class="section-head">
			<span class="eyebrow"><?php esc_html_e( 'How it works', 'pikacart' ); ?></span>
			<h2><?php esc_html_e( 'Three simple steps', 'pikacart' ); ?></h2>
		</div>
		<ol class="steps">
			<li><span class="step-n">1</span><h3><?php esc_html_e( 'Choose a design', 'pikacart' ); ?></h3><p><?php esc_html_e( 'Pick a style, card size and colours. Your logo, signature and seal are added automatically.', 'pikacart' ); ?></p></li>
			<li><span class="step-n">2</span><h3><?php esc_html_e( 'Add your people', 'pikacart' ); ?></h3><p><?php esc_html_e( 'Type details, import from Excel, upload photos in bulk, or share a self-fill link.', 'pikacart' ); ?></p></li>
			<li><span class="step-n">3</span><h3><?php esc_html_e( 'Download or print', 'pikacart' ); ?></h3><p><?php esc_html_e( 'Get sharp 300 DPI PDF, JPG or PNG files, or print sheets with cut marks.', 'pikacart' ); ?></p></li>
		</ol>
	</div>
</section>

<section class="section" id="features">
	<div class="wrap">
		<div class="section-head">
			<span class="eyebrow"><?php esc_html_e( 'Features', 'pikacart' ); ?></span>
			<h2><?php esc_html_e( 'Everything you need, nothing you don\'t', 'pikacart' ); ?></h2>
		</div>
		<div class="features">
			<?php
			$pkc_features = array(
				array( 'upload', __( 'Bulk import', 'pikacart' ), __( 'Import hundreds of people from Excel or CSV, and match photos by ID number automatically.', 'pikacart' ) ),
				array( 'qr', __( 'Real QR verification', 'pikacart' ), __( 'Every card carries a scannable QR code that opens a secure "Verified" page.', 'pikacart' ) ),
				array( 'printer', __( 'Print sheets with cut marks', 'pikacart' ), __( 'Arrange cards on A4 and larger paper, front and back side by side, ready to cut.', 'pikacart' ) ),
				array( 'ruler', __( '11 card sizes', 'pikacart' ), __( 'From standard PVC (CR80) to 4 x 6 inch event passes, in portrait and landscape.', 'pikacart' ) ),
				array( 'image', __( 'Use your own design', 'pikacart' ), __( 'Upload your existing card artwork and place the photo, name and QR on it.', 'pikacart' ) ),
				array( 'users', __( 'Live chat support', 'pikacart' ), __( 'Chat with our team right inside your dashboard and get notified the moment we reply.', 'pikacart' ) ),
				array( 'sparkles', __( 'Design on Demand', 'pikacart' ), __( 'Send us your design and our team sets it up in your account within hours.', 'pikacart' ) ),
			);
			foreach ( $pkc_features as $pkc_f ) :
				?>
				<div class="feature">
					<span class="feature-icon"><?php echo pkc_theme_icon( $pkc_f[0] ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $pkc_f[1] ); ?></h3>
					<p><?php echo esc_html( $pkc_f[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section-alt" id="pricing">
	<div class="wrap">
		<div class="section-head">
			<span class="eyebrow"><?php esc_html_e( 'Pricing', 'pikacart' ); ?></span>
			<h2><?php echo esc_html( $pkc_forever ? __( 'Free forever. Pro when you need it.', 'pikacart' ) : __( 'One simple plan', 'pikacart' ) ); ?></h2>
			<p>
				<?php
				echo esc_html(
					$pkc_forever
						/* translators: %s: price */
						? sprintf( __( 'Use every feature free. Remove the watermark for just %s per month.', 'pikacart' ), $pkc_price )
						/* translators: 1: trial length, 2: price */
						: sprintf( __( 'Try everything free for %1$s, then just %2$s per month.', 'pikacart' ), $pkc_trial, $pkc_price )
				);
				?>
			</p>
		</div>
		<?php echo shortcode_exists( 'pikacart_pricing' ) ? do_shortcode( '[pikacart_pricing]' ) : ''; // phpcs:ignore ?>
	</div>
</section>

<section class="section" id="faq">
	<div class="wrap faq-wrap">
		<div class="section-head">
			<span class="eyebrow"><?php esc_html_e( 'FAQ', 'pikacart' ); ?></span>
			<h2><?php esc_html_e( 'Questions, answered', 'pikacart' ); ?></h2>
		</div>
		<div class="faq">
			<?php
			$pkc_faq = array(
				array( __( 'Do I need to install any software?', 'pikacart' ), __( 'No. Pikacart works in your web browser on computer, tablet and phone.', 'pikacart' ) ),
				$pkc_forever
					? array( __( 'Is it really free?', 'pikacart' ), __( 'Yes, free forever with every feature. Free cards carry a small "Made with www.pikacart.in" watermark. Pro removes it.', 'pikacart' ) )
					/* translators: %s: trial length */
					: array( __( 'How does the free trial work?', 'pikacart' ), sprintf( __( 'Register and use every feature free for %s. Downloads during the trial carry a watermark.', 'pikacart' ), $pkc_trial ) ),
				array( __( 'Can I pay without autopay?', 'pikacart' ), __( 'Yes. You can pay once for one month by UPI, card or net banking if your bank does not support autopay.', 'pikacart' ) ),
				array( __( 'Which printers and holders are supported?', 'pikacart' ), __( 'Files are exact millimetre size at 300 DPI, so they work with PVC card printers, inkjet and laser printers and all common holder sizes.', 'pikacart' ) ),
				array( __( 'Is my students\' data safe?', 'pikacart' ), __( 'Each organisation\'s data is kept separate and private. You choose what the QR verification page shows, and you can delete your data any time.', 'pikacart' ) ),
			);
			foreach ( $pkc_faq as $pkc_q ) :
				?>
				<details><summary><?php echo esc_html( $pkc_q[0] ); ?></summary><p><?php echo esc_html( $pkc_q[1] ); ?></p></details>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="cta-band">
	<div class="wrap cta-inner">
		<h2><?php esc_html_e( 'Make your first ID card today', 'pikacart' ); ?></h2>
		<a class="t-btn t-btn-white t-btn-lg" href="<?php echo esc_url( pkc_theme_url( 'register' ) ); ?>"><?php esc_html_e( 'Start Free', 'pikacart' ); ?></a>
	</div>
</section>

<?php
get_footer();
