<?php
/**
 * Seller applications: "Apply now for a seller account" button + form (switchable
 * from the dashboard), batches (Batch 1, Batch 2 …) and an admin review screen
 * with date sorting, filters, documents, CSV and one-click approval.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Settings & helpers
 * ---------------------------------------------------------------------- */

function dm_apply_defaults() {
	return array(
		'enabled'    => 0,
		'batch'      => 1,
		'closes'     => '',
		'headline'   => __( 'Now accepting sellers', 'digimarket' ),
		'categories' => "Ebooks\nThemes, software & plugins\nCourses\nGraphics\nOther digital products",
	);
}

function dm_apply_opt( $key ) {
	$o = wp_parse_args( (array) get_option( 'dm_apply', array() ), dm_apply_defaults() );
	return $o[ $key ] ?? null;
}

function dm_apply_update( $changes ) {
	$o = wp_parse_args( (array) get_option( 'dm_apply', array() ), dm_apply_defaults() );
	update_option( 'dm_apply', array_merge( $o, $changes ) );
}

/**
 * Applications accepted right now: switched on and the closing date (if any) not passed.
 */
function dm_apply_open() {
	if ( ! dm_apply_opt( 'enabled' ) ) {
		return false;
	}
	$closes = (string) dm_apply_opt( 'closes' );
	return '' === $closes || current_time( 'Y-m-d' ) <= $closes;
}

function dm_apply_categories() {
	return array_values( array_filter( array_map( 'trim', explode( "\n", (string) dm_apply_opt( 'categories' ) ) ) ) );
}

function dm_apply_batch_label( $n ) {
	/* translators: %d batch number */
	return sprintf( __( 'Batch %d', 'digimarket' ), (int) $n );
}

function dm_apply_ref( $app ) {
	return 'PK-B' . (int) $app->batch . '-' . str_pad( (string) (int) $app->id, 4, '0', STR_PAD_LEFT );
}

function dm_apply_statuses() {
	return array(
		'new'      => __( 'New', 'digimarket' ),
		'approved' => __( 'Approved', 'digimarket' ),
		'changes'  => __( 'Needs changes', 'digimarket' ),
		'waitlist' => __( 'Waiting list', 'digimarket' ),
		'rejected' => __( 'Rejected', 'digimarket' ),
	);
}

function dm_apply_seller_types() {
	return array(
		'individual'     => __( 'Individual / freelancer', 'digimarket' ),
		'proprietorship' => __( 'Proprietorship', 'digimarket' ),
		'partnership'    => __( 'Partnership', 'digimarket' ),
		'llp'            => __( 'LLP', 'digimarket' ),
		'private_limited'=> __( 'Private limited company', 'digimarket' ),
		'other'          => __( 'Other', 'digimarket' ),
	);
}

function dm_apply_ready_options() {
	return array( '1-2' => '1–2', '3-5' => '3–5', '6-10' => '6–10', '10+' => '10+' );
}

function dm_apply_get( $id ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'applications' ) . ' WHERE id = %d', $id ) );
	if ( $row ) {
		$row->d    = (array) json_decode( (string) $row->data, true );
		$row->docs = (array) json_decode( (string) $row->docs, true );
	}
	return $row;
}

function dm_apply_url() {
	return add_query_arg( 'apply-seller', '1', home_url( '/' ) ) . '#apply';
}

/* -------------------------------------------------------------------------
 * Front end: buttons, bar, footer band, modal form
 * ---------------------------------------------------------------------- */

/**
 * The professional "Apply now for a seller account" button. Opens the form pop-up.
 */
function dm_apply_button( $label = '', $class = 'dm-btn dm-btn-primary dm-btn-lg' ) {
	if ( ! dm_apply_open() ) {
		return '';
	}
	$label = $label ? $label : __( 'Apply now for a seller account', 'digimarket' );
	return '<a class="' . esc_attr( $class . ' dm-apply-btn' ) . '" href="' . esc_url( dm_apply_url() ) . '" data-dm-apply>' . dm_icon( 'briefcase', 18 ) . ' <span>' . esc_html( $label ) . '</span></a>';
}

add_shortcode( 'pikacart_apply_button', function ( $atts ) {
	$a = shortcode_atts( array( 'label' => '' ), $atts );
	if ( ! dm_apply_open() ) {
		return '<p class="dm-apply-closed"><strong>' . esc_html__( 'Seller applications are closed right now.', 'digimarket' ) . '</strong> ' . esc_html( sprintf( /* translators: %s email */ __( 'Email %s to join the waiting list.', 'digimarket' ), dm_business()['email'] ) ) . '</p>';
	}
	return '<p class="dm-apply-sc">' . dm_apply_button( $a['label'] ) . '</p>';
} );

/**
 * Slim bar above the header while applications are open.
 */
function dm_apply_render_bar() {
	if ( ! dm_apply_open() || dm_is_seller() ) {
		return;
	}
	$closes = (string) dm_apply_opt( 'closes' );
	echo '<div class="dm-apply-bar"><div class="dm-container"><span class="dm-apply-bar-dot" aria-hidden="true"></span><strong>' . esc_html( dm_apply_opt( 'headline' ) ) . '</strong><span class="dm-apply-bar-sep">·</span><span>' . esc_html( dm_apply_batch_label( dm_apply_opt( 'batch' ) ) ) . ( $closes ? ' ' . esc_html( sprintf( /* translators: %s date */ __( 'closes %s', 'digimarket' ), wp_date( 'j M', strtotime( $closes . ' 12:00:00' ), new DateTimeZone( 'UTC' ) ) ) ) : '' ) . '</span><a href="' . esc_url( dm_apply_url() ) . '" data-dm-apply>' . esc_html__( 'Apply now', 'digimarket' ) . ' →</a></div></div>';
}

/**
 * Field helpers for the big form.
 */
function dm_apply_field( $name, $label, $type = 'text', $args = array() ) {
	$req  = ! empty( $args['required'] );
	$id   = 'dmap-' . $name;
	$attr = '';
	foreach ( array( 'placeholder', 'pattern', 'maxlength', 'minlength', 'autocomplete', 'inputmode', 'accept' ) as $k ) {
		if ( isset( $args[ $k ] ) ) {
			$attr .= ' ' . $k . '="' . esc_attr( $args[ $k ] ) . '"';
		}
	}
	$attr .= $req ? ' required' : '';
	$out   = '<label class="dm-apf' . ( ! empty( $args['wide'] ) ? ' dm-apf-wide' : '' ) . '" for="' . esc_attr( $id ) . '"><span class="dm-apf-l">' . esc_html( $label ) . ( $req ? ' <em>*</em>' : ' <small>' . esc_html__( '(optional)', 'digimarket' ) . '</small>' ) . '</span>';
	if ( 'textarea' === $type ) {
		$out .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . (int) ( $args['rows'] ?? 3 ) . '"' . $attr . '></textarea>';
	} elseif ( 'select' === $type ) {
		$out .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $attr . '><option value="">' . esc_html__( 'Choose…', 'digimarket' ) . '</option>';
		foreach ( $args['options'] as $v => $l ) {
			$out .= '<option value="' . esc_attr( $v ) . '">' . esc_html( $l ) . '</option>';
		}
		$out .= '</select>';
	} elseif ( 'file' === $type ) {
		$out .= '<span class="dm-apf-file"><input type="file" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $attr . '><span class="dm-apf-file-ui">' . dm_icon( 'upload', 20 ) . ' <span data-file-name>' . esc_html__( 'Choose file — JPG, PNG or PDF, max 5 MB', 'digimarket' ) . '</span></span></span>';
	} else {
		$out .= '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $attr . '>';
	}
	if ( ! empty( $args['help'] ) ) {
		$out .= '<small class="dm-apf-h">' . esc_html( $args['help'] ) . '</small>';
	}
	$out .= '<small class="dm-apf-err" data-err="' . esc_attr( $name ) . '" hidden></small></label>';
	return $out;
}

function dm_apply_form_html() {
	$cats   = dm_apply_categories();
	$states = dm_indian_states();
	$f      = 'dm_apply_field';
	ob_start();
	?>
	<form class="dm-apply-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( home_url( '/' ) ); ?>" novalidate data-apply-form>
		<input type="hidden" name="dm_action" value="seller_apply">
		<?php wp_nonce_field( 'dm_seller_apply', '_dmnonce' ); ?>
		<div class="dm-apply-hp" aria-hidden="true"><label>Website <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>

		<fieldset class="dm-aps">
			<legend><span>1</span><?php esc_html_e( 'Your details', 'digimarket' ); ?></legend>
			<div class="dm-apg">
				<?php
				echo $f( 'full_name', __( 'Full name (as on PAN)', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 100, 'autocomplete' => 'name' ) ); // phpcs:ignore
				echo $f( 'email', __( 'Email address', 'digimarket' ), 'email', array( 'required' => 1, 'autocomplete' => 'email', 'help' => __( 'Your seller login and all updates are sent here.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'phone', __( 'Mobile number', 'digimarket' ), 'tel', array( 'required' => 1, 'inputmode' => 'numeric', 'placeholder' => '98765 43210', 'autocomplete' => 'tel' ) ); // phpcs:ignore
				echo $f( 'whatsapp', __( 'WhatsApp number', 'digimarket' ), 'tel', array( 'inputmode' => 'numeric', 'help' => __( 'Leave blank if same as mobile.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'address', __( 'Full address', 'digimarket' ), 'text', array( 'required' => 1, 'wide' => 1, 'autocomplete' => 'street-address', 'placeholder' => __( 'House / street / area', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'city', __( 'City / town', 'digimarket' ), 'text', array( 'required' => 1, 'autocomplete' => 'address-level2' ) ); // phpcs:ignore
				echo $f( 'state', __( 'State', 'digimarket' ), 'select', array( 'required' => 1, 'options' => array_combine( $states, $states ) ) ); // phpcs:ignore
				echo $f( 'pin', __( 'PIN code', 'digimarket' ), 'text', array( 'required' => 1, 'inputmode' => 'numeric', 'maxlength' => 6, 'autocomplete' => 'postal-code' ) ); // phpcs:ignore
				?>
			</div>
		</fieldset>

		<fieldset class="dm-aps">
			<legend><span>2</span><?php esc_html_e( 'Your shop & products', 'digimarket' ); ?></legend>
			<div class="dm-apg">
				<?php
				echo $f( 'shop_name', __( 'Shop name', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 60, 'placeholder' => __( 'e.g. Priya’s Study Notes', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'category', __( 'Main category', 'digimarket' ), 'select', array( 'required' => 1, 'options' => array_combine( $cats, $cats ) ) ); // phpcs:ignore
				echo $f( 'about', __( 'About you', 'digimarket' ), 'textarea', array( 'required' => 1, 'wide' => 1, 'maxlength' => 1000, 'placeholder' => __( 'Who you are, your experience, what you create.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'products', __( 'What will you sell?', 'digimarket' ), 'textarea', array( 'required' => 1, 'wide' => 1, 'rows' => 4, 'maxlength' => 2000, 'placeholder' => __( 'List the products you plan to sell: titles, what each includes, who it is for.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'ready', __( 'Products ready to list now', 'digimarket' ), 'select', array( 'required' => 1, 'options' => dm_apply_ready_options() ) ); // phpcs:ignore
				echo $f( 'price_range', __( 'Price range', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 60, 'placeholder' => '₹49 – ₹999' ) ); // phpcs:ignore
				echo $f( 'samples', __( 'Sample / portfolio links', 'digimarket' ), 'textarea', array( 'required' => 1, 'wide' => 1, 'maxlength' => 1500, 'placeholder' => "https://drive.google.com/…\nhttps://youtube.com/…", 'help' => __( 'One link per line: sample chapter, demo site, course preview, Behance / Dribbble, YouTube, Drive folder.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'hosting', __( 'Where are your course videos hosted?', 'digimarket' ), 'text', array( 'wide' => 1, 'maxlength' => 200, 'placeholder' => __( 'YouTube (unlisted), Vimeo, Google Drive, Graphy, Teachable…', 'digimarket' ), 'help' => __( 'Only for courses. Courses are delivered by a private link or enrolment code shown after payment.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'other_sites', __( 'Do you already sell anywhere?', 'digimarket' ), 'text', array( 'wide' => 1, 'maxlength' => 300, 'placeholder' => __( 'Instamojo, Gumroad, your website, Instagram…', 'digimarket' ) ) ); // phpcs:ignore
				?>
			</div>
		</fieldset>

		<fieldset class="dm-aps">
			<legend><span>3</span><?php esc_html_e( 'PAN & tax details', 'digimarket' ); ?></legend>
			<div class="dm-apg">
				<?php
				echo $f( 'seller_type', __( 'You are selling as', 'digimarket' ), 'select', array( 'required' => 1, 'options' => dm_apply_seller_types() ) ); // phpcs:ignore
				echo $f( 'business_name', __( 'Business name', 'digimarket' ), 'text', array( 'maxlength' => 150, 'help' => __( 'If you sell as a business.', 'digimarket' ) ) ); // phpcs:ignore
				echo $f( 'pan', __( 'PAN number', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 10, 'placeholder' => 'ABCDE1234F', 'autocomplete' => 'off' ) ); // phpcs:ignore
				echo $f( 'gstin', __( 'GSTIN', 'digimarket' ), 'text', array( 'maxlength' => 15, 'autocomplete' => 'off', 'help' => __( 'Not compulsory. GST registration is needed only once your yearly sales cross ₹20 lakh (or if you are already registered).', 'digimarket' ) ) ); // phpcs:ignore
				?>
			</div>
		</fieldset>

		<fieldset class="dm-aps">
			<legend><span>4</span><?php esc_html_e( 'Bank details for payouts', 'digimarket' ); ?></legend>
			<div class="dm-apg">
				<?php
				echo $f( 'acct_name', __( 'Account holder name', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 120 ) ); // phpcs:ignore
				echo $f( 'bank_name', __( 'Bank name', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 120 ) ); // phpcs:ignore
				echo $f( 'acct', __( 'Account number', 'digimarket' ), 'password', array( 'required' => 1, 'inputmode' => 'numeric', 'maxlength' => 18, 'autocomplete' => 'off' ) ); // phpcs:ignore
				echo $f( 'acct2', __( 'Confirm account number', 'digimarket' ), 'text', array( 'required' => 1, 'inputmode' => 'numeric', 'maxlength' => 18, 'autocomplete' => 'off' ) ); // phpcs:ignore
				echo $f( 'ifsc', __( 'IFSC code', 'digimarket' ), 'text', array( 'required' => 1, 'maxlength' => 11, 'placeholder' => 'SBIN0001234', 'autocomplete' => 'off' ) ); // phpcs:ignore
				echo $f( 'upi', __( 'UPI ID', 'digimarket' ), 'text', array( 'maxlength' => 100, 'placeholder' => 'name@okbank', 'autocomplete' => 'off' ) ); // phpcs:ignore
				?>
			</div>
		</fieldset>

		<fieldset class="dm-aps">
			<legend><span>5</span><?php esc_html_e( 'Documents', 'digimarket' ); ?></legend>
			<div class="dm-apg">
				<?php
				echo $f( 'pan_doc', __( 'PAN card', 'digimarket' ), 'file', array( 'required' => 1, 'accept' => '.jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf' ) ); // phpcs:ignore
				echo $f( 'bank_doc', __( 'Bank proof — cancelled cheque, passbook first page or bank statement', 'digimarket' ), 'file', array( 'required' => 1, 'accept' => '.jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf' ) ); // phpcs:ignore
				?>
			</div>
			<p class="dm-apply-secure"><?php echo dm_icon( 'shield', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Your PAN and bank details are encrypted and your documents are stored privately. They are used only to verify you and pay your earnings.', 'digimarket' ); ?></p>
		</fieldset>

		<fieldset class="dm-aps">
			<legend><span>6</span><?php esc_html_e( 'Declaration', 'digimarket' ); ?></legend>
			<label class="dm-check"><input type="checkbox" name="own_rights" value="1" required> <span><?php esc_html_e( 'I own the rights to everything I will sell, or hold a licence to resell it. No pirated, copied, nulled or PLR content without a licence.', 'digimarket' ); ?></span></label>
			<label class="dm-check"><input type="checkbox" name="correct" value="1" required> <span><?php esc_html_e( 'The details and documents I have given are true and belong to me or my business.', 'digimarket' ); ?></span></label>
			<label class="dm-check"><input type="checkbox" name="terms" value="1" required> <span><?php echo wp_kses_post( sprintf( /* translators: 1 seller agreement url 2 terms url */ __( 'I have read and agree to the <a href="%1$s" target="_blank">Seller Agreement</a> and <a href="%2$s" target="_blank">Terms &amp; Conditions</a>.', 'digimarket' ), esc_url( dm_legal_url( 'seller' ) ), esc_url( dm_legal_url( 'terms' ) ) ) ); ?></span></label>
			<small class="dm-apf-err" data-err="declaration" hidden></small>
		</fieldset>

		<div class="dm-apply-submit">
			<small class="dm-apf-err dm-apf-err-form" data-err="form" hidden></small>
			<button class="dm-btn dm-btn-primary dm-btn-lg" type="submit" data-apply-submit><?php esc_html_e( 'Submit application', 'digimarket' ); ?></button>
			<small class="dm-muted"><?php esc_html_e( 'We review applications in the order they arrive and reply by email within 2–3 working days.', 'digimarket' ); ?></small>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * The pop-up (printed once in the footer while applications are open).
 */
add_action( 'wp_footer', function () {
	if ( ! dm_apply_open() || is_admin() ) {
		return;
	}
	$auto   = ! empty( $_GET['apply-seller'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$closes = (string) dm_apply_opt( 'closes' );
	?>
	<dialog class="dm-agree-modal dm-apply-modal" id="dm-apply-modal" aria-labelledby="dm-apply-title"<?php echo $auto ? ' data-autoopen' : ''; ?>>
		<div class="dm-apply-head">
			<div>
				<span class="dm-apply-kicker"><?php echo esc_html( dm_apply_batch_label( dm_apply_opt( 'batch' ) ) . ( $closes ? ' · ' . sprintf( /* translators: %s date */ __( 'closes %s', 'digimarket' ), wp_date( 'j M Y', strtotime( $closes . ' 12:00:00' ), new DateTimeZone( 'UTC' ) ) ) : '' ) ); ?></span>
				<h2 id="dm-apply-title"><?php esc_html_e( 'Apply for a seller account', 'digimarket' ); ?></h2>
				<p><?php esc_html_e( 'Sell your ebooks, themes, plugins, courses and designs on PikaCart. Fill in every section — it takes about 5 minutes.', 'digimarket' ); ?></p>
			</div>
			<button type="button" class="dm-icon-btn" data-apply-close aria-label="<?php esc_attr_e( 'Close', 'digimarket' ); ?>"><?php echo dm_icon( 'close', 20 ); // phpcs:ignore ?></button>
		</div>
		<div class="dm-apply-body">
			<ul class="dm-apply-perks">
				<li><?php echo dm_icon( 'percent', 18 ); // phpcs:ignore ?> <?php echo esc_html( sprintf( /* translators: %s rate */ __( 'Only %s%% commission', 'digimarket' ), rtrim( rtrim( number_format( (float) dm_opt( 'commission_global' ), 2 ), '0' ), '.' ) ) ); ?></li>
				<li><?php echo dm_icon( 'calendar', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Weekly payouts to bank / UPI', 'digimarket' ); ?></li>
				<li><?php echo dm_icon( 'grid', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Seller dashboard & wallet', 'digimarket' ); ?></li>
				<li><?php echo dm_icon( 'shield', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'No joining fee', 'digimarket' ); ?></li>
			</ul>
			<?php echo dm_apply_form_html(); // phpcs:ignore ?>
			<div class="dm-apply-done" data-apply-done hidden>
				<div class="dm-apply-done-icon"><?php echo dm_icon( 'check', 34 ); // phpcs:ignore ?></div>
				<h3><?php esc_html_e( 'Application received!', 'digimarket' ); ?></h3>
				<p><?php esc_html_e( 'Your reference number is', 'digimarket' ); ?> <strong data-apply-ref></strong></p>
				<p class="dm-muted"><?php esc_html_e( 'We have emailed you a copy. We review applications in the order they arrive and will reply within 2–3 working days.', 'digimarket' ); ?></p>
				<button type="button" class="dm-btn dm-btn-outline" data-apply-close><?php esc_html_e( 'Close', 'digimarket' ); ?></button>
			</div>
		</div>
	</dialog>
	<?php
} );

/* -------------------------------------------------------------------------
 * Form handler
 * ---------------------------------------------------------------------- */

function dm_apply_respond( $ok, $payload ) {
	$ajax = ! empty( $_POST['ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( $ajax ) {
		wp_send_json( array_merge( array( 'success' => $ok ), $payload ), $ok ? 200 : 422 );
	}
	if ( $ok ) {
		dm_flash( 'success', sprintf( /* translators: %s reference */ __( 'Application received! Your reference is %s. We have emailed you a copy.', 'digimarket' ), $payload['ref'] ) );
	} else {
		dm_flash( 'error', implode( ' ', array_values( $payload['errors'] ) ) );
	}
	dm_redirect( remove_query_arg( 'apply-seller', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
}

function dm_do_seller_apply() {
	global $wpdb;
	if ( ! dm_apply_open() ) {
		dm_apply_respond( false, array( 'errors' => array( 'form' => __( 'Seller applications are closed right now.', 'digimarket' ) ) ) );
	}
	if ( ! empty( $_POST['website_url'] ) ) { // Honeypot: bots fill every field.
		dm_apply_respond( false, array( 'errors' => array( 'form' => __( 'Could not submit the form.', 'digimarket' ) ) ) );
	}
	$rl    = 'dm_apply_rl_' . md5( dm_client_ip() );
	$count = (int) get_transient( $rl );
	if ( $count >= 5 ) {
		dm_apply_respond( false, array( 'errors' => array( 'form' => __( 'Too many applications from your network. Please try again in an hour.', 'digimarket' ) ) ) );
	}

	$p   = function ( $k, $max = 200 ) {
		return mb_substr( trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) ), 0, $max ); // phpcs:ignore
	};
	$pt  = function ( $k, $max = 2000 ) {
		return mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST[ $k ] ?? '' ) ) ), 0, $max ); // phpcs:ignore
	};
	$digits = function ( $v ) {
		$v = preg_replace( '/\D/', '', (string) $v );
		return ( 12 === strlen( $v ) && 0 === strpos( $v, '91' ) ) ? substr( $v, 2 ) : ltrim( $v, '0' );
	};
	$d = array(
		'full_name'     => $p( 'full_name', 100 ),
		'email'         => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ), // phpcs:ignore
		'phone'         => $digits( $p( 'phone', 20 ) ),
		'whatsapp'      => $digits( $p( 'whatsapp', 20 ) ),
		'address'       => $p( 'address', 250 ),
		'city'          => $p( 'city', 80 ),
		'state'         => $p( 'state', 64 ),
		'pin'           => preg_replace( '/\D/', '', $p( 'pin', 10 ) ),
		'shop_name'     => $p( 'shop_name', 60 ),
		'category'      => $p( 'category', 64 ),
		'about'         => $pt( 'about', 1000 ),
		'products'      => $pt( 'products', 2000 ),
		'ready'         => $p( 'ready', 10 ),
		'price_range'   => $p( 'price_range', 60 ),
		'samples'       => $pt( 'samples', 1500 ),
		'hosting'       => $p( 'hosting', 200 ),
		'other_sites'   => $p( 'other_sites', 300 ),
		'seller_type'   => sanitize_key( $p( 'seller_type', 30 ) ),
		'business_name' => $p( 'business_name', 150 ),
		'pan'           => strtoupper( preg_replace( '/\s+/', '', $p( 'pan', 12 ) ) ),
		'gstin'         => strtoupper( preg_replace( '/\s+/', '', $p( 'gstin', 20 ) ) ),
		'acct_name'     => $p( 'acct_name', 120 ),
		'bank_name'     => $p( 'bank_name', 120 ),
		'acct'          => preg_replace( '/\D/', '', $p( 'acct', 30 ) ),
		'ifsc'          => strtoupper( preg_replace( '/\s+/', '', $p( 'ifsc', 15 ) ) ),
		'upi'           => $p( 'upi', 100 ),
	);
	$acct2 = preg_replace( '/\D/', '', $p( 'acct2', 30 ) );

	$e = array();
	if ( mb_strlen( $d['full_name'] ) < 3 ) {
		$e['full_name'] = __( 'Enter your full name as on PAN.', 'digimarket' );
	}
	if ( ! is_email( $d['email'] ) ) {
		$e['email'] = __( 'Enter a valid email address.', 'digimarket' );
	}
	if ( ! preg_match( '/^[6-9]\d{9}$/', $d['phone'] ) ) {
		$e['phone'] = __( 'Enter a valid 10-digit Indian mobile number.', 'digimarket' );
	}
	if ( '' !== $d['whatsapp'] && ! preg_match( '/^[6-9]\d{9}$/', $d['whatsapp'] ) ) {
		$e['whatsapp'] = __( 'Enter a valid 10-digit WhatsApp number or leave it blank.', 'digimarket' );
	}
	if ( mb_strlen( $d['address'] ) < 5 ) {
		$e['address'] = __( 'Enter your full address.', 'digimarket' );
	}
	if ( mb_strlen( $d['city'] ) < 2 ) {
		$e['city'] = __( 'Enter your city or town.', 'digimarket' );
	}
	if ( ! in_array( $d['state'], dm_indian_states(), true ) ) {
		$e['state'] = __( 'Choose your state.', 'digimarket' );
	}
	if ( ! preg_match( '/^[1-9]\d{5}$/', $d['pin'] ) ) {
		$e['pin'] = __( 'Enter a valid 6-digit PIN code.', 'digimarket' );
	}
	if ( mb_strlen( $d['shop_name'] ) < 3 ) {
		$e['shop_name'] = __( 'Enter a shop name (3–60 characters).', 'digimarket' );
	}
	if ( ! in_array( $d['category'], dm_apply_categories(), true ) ) {
		$e['category'] = __( 'Choose your main category.', 'digimarket' );
	}
	if ( mb_strlen( $d['about'] ) < 20 ) {
		$e['about'] = __( 'Tell us a little more about you (at least 20 characters).', 'digimarket' );
	}
	if ( mb_strlen( $d['products'] ) < 20 ) {
		$e['products'] = __( 'Describe the products you will sell (at least 20 characters).', 'digimarket' );
	}
	if ( ! isset( dm_apply_ready_options()[ $d['ready'] ] ) ) {
		$e['ready'] = __( 'Choose how many products are ready.', 'digimarket' );
	}
	if ( '' === $d['price_range'] ) {
		$e['price_range'] = __( 'Enter your price range.', 'digimarket' );
	}
	$links = array();
	foreach ( preg_split( '/\s+/', $d['samples'] ) as $u ) {
		if ( $u && wp_http_validate_url( $u ) && preg_match( '#^https?://#i', $u ) ) {
			$links[] = esc_url_raw( $u );
		}
	}
	if ( ! $links ) {
		$e['samples'] = __( 'Add at least one working sample or portfolio link (starting with https://).', 'digimarket' );
	}
	$d['samples'] = implode( "\n", array_slice( array_unique( $links ), 0, 10 ) );
	if ( ! isset( dm_apply_seller_types()[ $d['seller_type'] ] ) ) {
		$e['seller_type'] = __( 'Choose how you are selling.', 'digimarket' );
	}
	if ( ! preg_match( '/^[A-Z]{5}[0-9]{4}[A-Z]$/', $d['pan'] ) ) {
		$e['pan'] = __( 'Enter a valid PAN (e.g. ABCDE1234F).', 'digimarket' );
	}
	if ( '' !== $d['gstin'] ) {
		if ( ! dm_valid_gstin( $d['gstin'] ) ) {
			$e['gstin'] = __( 'Enter a valid 15-character GSTIN or leave it blank.', 'digimarket' );
		} elseif ( empty( $e['pan'] ) && substr( $d['gstin'], 2, 10 ) !== $d['pan'] ) {
			$e['gstin'] = __( 'This GSTIN does not belong to the PAN entered above.', 'digimarket' );
		}
	}
	if ( mb_strlen( $d['acct_name'] ) < 3 ) {
		$e['acct_name'] = __( 'Enter the account holder name.', 'digimarket' );
	}
	if ( mb_strlen( $d['bank_name'] ) < 2 ) {
		$e['bank_name'] = __( 'Enter your bank name.', 'digimarket' );
	}
	if ( ! preg_match( '/^\d{9,18}$/', $d['acct'] ) ) {
		$e['acct'] = __( 'Enter a valid bank account number (9–18 digits).', 'digimarket' );
	} elseif ( $acct2 !== $d['acct'] ) {
		$e['acct2'] = __( 'Account numbers do not match.', 'digimarket' );
	}
	if ( ! preg_match( '/^[A-Z]{4}0[A-Z0-9]{6}$/', $d['ifsc'] ) ) {
		$e['ifsc'] = __( 'Enter a valid 11-character IFSC (e.g. SBIN0001234).', 'digimarket' );
	}
	if ( '' !== $d['upi'] && ! preg_match( '/^[A-Za-z0-9.\-_]{2,256}@[A-Za-z]{2,64}$/', $d['upi'] ) ) {
		$e['upi'] = __( 'Enter a valid UPI ID (e.g. name@okbank) or leave it blank.', 'digimarket' );
	}
	if ( empty( $_POST['own_rights'] ) || empty( $_POST['correct'] ) || empty( $_POST['terms'] ) ) { // phpcs:ignore
		$e['declaration'] = __( 'Please tick all three declarations.', 'digimarket' );
	}
	foreach ( array( 'pan_doc' => __( 'Upload your PAN card.', 'digimarket' ), 'bank_doc' => __( 'Upload a bank proof (cancelled cheque, passbook or statement).', 'digimarket' ) ) as $k => $msg ) {
		$err = dm_apply_check_file( $k );
		if ( true !== $err ) {
			$e[ $k ] = $err ? $err : $msg;
		}
	}
	if ( ! $e ) {
		$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'applications' ) . " WHERE batch = %d AND email = %s AND status <> 'rejected'", (int) dm_apply_opt( 'batch' ), $d['email'] ) );
		if ( $dup ) {
			$e['email'] = sprintf( /* translators: %s reference */ __( 'You have already applied in this batch (reference %s). We will reply by email.', 'digimarket' ), dm_apply_ref( (object) array( 'id' => $dup, 'batch' => dm_apply_opt( 'batch' ) ) ) );
		}
	}
	if ( $e ) {
		dm_apply_respond( false, array( 'errors' => $e ) );
	}

	// Save documents privately.
	$docs = array();
	foreach ( array( 'pan_doc', 'bank_doc' ) as $k ) {
		$saved = dm_apply_store_file( $k );
		if ( is_wp_error( $saved ) ) {
			dm_apply_respond( false, array( 'errors' => array( $k => $saved->get_error_message() ) ) );
		}
		$docs[ $k ] = $saved;
	}
	set_transient( $rl, $count + 1, HOUR_IN_SECONDS );

	// Encrypt sensitive values at rest.
	$store                = $d;
	$store['pan']         = dm_encrypt( $d['pan'] );
	$store['pan_last4']   = substr( $d['pan'], -4 );
	$store['acct']        = dm_encrypt( $d['acct'] );
	$store['acct_last4']  = substr( $d['acct'], -4 );
	$store['agreed']      = array( 'time' => time(), 'ip' => dm_client_ip(), 'version' => function_exists( 'dm_seller_agreement_version' ) ? dm_seller_agreement_version() : '' );
	$wpdb->insert(
		dm_table( 'applications' ),
		array(
			'batch'      => (int) dm_apply_opt( 'batch' ),
			'status'     => 'new',
			'name'       => $d['full_name'],
			'email'      => $d['email'],
			'phone'      => $d['phone'],
			'category'   => $d['category'],
			'shop_name'  => $d['shop_name'],
			'state'      => $d['state'],
			'data'       => wp_json_encode( $store ),
			'docs'       => wp_json_encode( $docs ),
			'ip'         => dm_client_ip(),
			'created_at' => dm_now(),
		)
	);
	$app = dm_apply_get( (int) $wpdb->insert_id );
	$ref = dm_apply_ref( $app );

	// Applicant copy (no sensitive numbers).
	$rows = '';
	foreach ( array( __( 'Reference', 'digimarket' ) => $ref, __( 'Batch', 'digimarket' ) => dm_apply_batch_label( $app->batch ), __( 'Shop name', 'digimarket' ) => $d['shop_name'], __( 'Category', 'digimarket' ) => $d['category'], __( 'PAN', 'digimarket' ) => '••••••' . $store['pan_last4'], __( 'Bank account', 'digimarket' ) => '•••• ' . $store['acct_last4'] . ' (' . $d['ifsc'] . ')' ) as $k => $v ) {
		$rows .= '<tr><td style="padding:6px 0;color:#6b6b76;width:140px">' . esc_html( $k ) . '</td><td style="padding:6px 0;font-weight:600">' . esc_html( $v ) . '</td></tr>';
	}
	dm_mail(
		$d['email'],
		/* translators: %s reference */
		sprintf( __( 'We received your seller application (%s)', 'digimarket' ), $ref ),
		'<p>' . sprintf( /* translators: %s name */ esc_html__( 'Hi %s,', 'digimarket' ), esc_html( $d['full_name'] ) ) . '</p><p>' . esc_html__( 'Thank you for applying to sell on PikaCart. Here is a summary of your application:', 'digimarket' ) . '</p><table cellpadding="0" cellspacing="0" style="font-size:14px">' . $rows . '</table><p>' . esc_html__( 'We review applications in the order they arrive and will reply within 2–3 working days. If you are selected, you will receive an email to set your password and open your seller dashboard.', 'digimarket' ) . '</p>'
	);
	// Admin alert.
	$admin_url = admin_url( 'admin.php?page=dm-applications&app=' . $app->id );
	/* translators: 1 name 2 category */
	dm_notify_admins( sprintf( __( 'New seller application: %1$s (%2$s)', 'digimarket' ), $d['full_name'], $d['category'] ), $admin_url );
	$to = dm_business()['email'] ? dm_business()['email'] : get_option( 'admin_email' );
	/* translators: 1 reference 2 name */
	dm_mail( $to, sprintf( __( 'New seller application %1$s – %2$s', 'digimarket' ), $ref, $d['full_name'] ), '<p>' . esc_html( sprintf( /* translators: 1 name 2 shop 3 category 4 batch */ __( '%1$s applied to sell as “%2$s” (%3$s) in %4$s.', 'digimarket' ), $d['full_name'], $d['shop_name'], $d['category'], dm_apply_batch_label( $app->batch ) ) ) . '</p>', $admin_url, __( 'Review application', 'digimarket' ) );

	dm_apply_respond( true, array( 'ref' => $ref ) );
}

/**
 * Validate an uploaded document. Returns true, '' (missing) or an error message.
 */
function dm_apply_check_file( $key ) {
	if ( empty( $_FILES[ $key ]['name'] ) || UPLOAD_ERR_NO_FILE === (int) ( $_FILES[ $key ]['error'] ?? 0 ) ) { // phpcs:ignore
		return '';
	}
	$f = $_FILES[ $key ]; // phpcs:ignore
	if ( UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) {
		return __( 'The file could not be uploaded. Please try again.', 'digimarket' );
	}
	if ( (int) $f['size'] > 5 * MB_IN_BYTES ) {
		return __( 'The file is larger than 5 MB.', 'digimarket' );
	}
	$check = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf' ) );
	if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
		return __( 'Upload a JPG, PNG or PDF file.', 'digimarket' );
	}
	return true;
}

function dm_apply_store_file( $key ) {
	$f     = $_FILES[ $key ]; // phpcs:ignore
	$check = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf' ) );
	$dir   = trailingslashit( dm_private_dir() ) . 'applications';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
	}
	$name = $key . '-' . wp_generate_password( 24, false ) . '.' . $check['ext'];
	if ( ! @move_uploaded_file( $f['tmp_name'], $dir . '/' . $name ) ) { // phpcs:ignore
		return new WP_Error( 'upload', __( 'The file could not be saved. Please try again.', 'digimarket' ) );
	}
	return array( 'file' => $name, 'type' => $check['type'], 'orig' => sanitize_file_name( $f['name'] ) );
}

/* -------------------------------------------------------------------------
 * Admin: Marketplace → Seller applications
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	global $wpdb;
	$new = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . dm_table( 'applications' ) . " WHERE status = 'new'" ); // phpcs:ignore
	add_submenu_page( 'dm-marketplace', __( 'Seller applications', 'digimarket' ), __( 'Seller applications', 'digimarket' ) . ( $new ? ' <span class="awaiting-mod">' . $new . '</span>' : '' ), 'dm_view_marketplace', 'dm-applications', 'dm_admin_applications' );
}, 20 );

function dm_apply_admin_filters() {
	$g = function ( $k ) {
		return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore
	};
	$batch = $g( 'batch' );
	return array(
		'batch'    => '' === $batch ? (string) (int) dm_apply_opt( 'batch' ) : $batch,
		'status'   => sanitize_key( $g( 'status' ) ),
		'category' => $g( 'category' ),
		's'        => $g( 's' ),
		'from'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $g( 'from' ) ) ? $g( 'from' ) : '',
		'to'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $g( 'to' ) ) ? $g( 'to' ) : '',
		'order'    => 'asc' === $g( 'order' ) ? 'asc' : 'desc',
	);
}

function dm_apply_query( $f, $limit = 200 ) {
	global $wpdb;
	$w = array( '1=1' );
	if ( 'all' !== $f['batch'] ) {
		$w[] = $wpdb->prepare( 'batch = %d', (int) $f['batch'] );
	}
	if ( $f['status'] ) {
		$w[] = $wpdb->prepare( 'status = %s', $f['status'] );
	}
	if ( $f['category'] ) {
		$w[] = $wpdb->prepare( 'category = %s', $f['category'] );
	}
	if ( $f['s'] ) {
		$like = '%' . $wpdb->esc_like( $f['s'] ) . '%';
		$w[]  = $wpdb->prepare( '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR shop_name LIKE %s)', $like, $like, $like, $like );
	}
	if ( $f['from'] ) {
		$w[] = $wpdb->prepare( 'created_at >= %s', $f['from'] . ' 00:00:00' );
	}
	if ( $f['to'] ) {
		$w[] = $wpdb->prepare( 'created_at <= %s', $f['to'] . ' 23:59:59' );
	}
	$rows = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'applications' ) . ' WHERE ' . implode( ' AND ', $w ) . ' ORDER BY created_at ' . ( 'asc' === $f['order'] ? 'ASC' : 'DESC' ) . ', id ' . ( 'asc' === $f['order'] ? 'ASC' : 'DESC' ) . ' LIMIT ' . (int) $limit ); // phpcs:ignore
	foreach ( $rows as $r ) {
		$r->d = (array) json_decode( (string) $r->data, true );
	}
	return $rows;
}

function dm_admin_applications() {
	global $wpdb;
	if ( ! empty( $_GET['app'] ) ) { // phpcs:ignore
		dm_admin_application_detail( absint( $_GET['app'] ) ); // phpcs:ignore
		return;
	}
	$can  = current_user_can( 'dm_manage_marketplace' );
	$f    = dm_apply_admin_filters();
	$rows = dm_apply_query( $f );
	$cur  = (int) dm_apply_opt( 'batch' );
	dm_admin_header( __( 'Seller applications', 'digimarket' ) );

	// Control panel.
	echo '<div class="dm-panel dm-apply-ctl"><div class="dm-apply-ctl-state">';
	echo '<span class="dm-apply-light ' . ( dm_apply_open() ? 'is-on' : 'is-off' ) . '"></span><div><strong>' . esc_html( dm_apply_open() ? __( 'Applications are OPEN', 'digimarket' ) : __( 'Applications are CLOSED', 'digimarket' ) ) . '</strong><br><small>' . esc_html( dm_apply_batch_label( $cur ) ) . ( dm_apply_opt( 'closes' ) ? ' · ' . esc_html( sprintf( /* translators: %s date */ __( 'closes %s', 'digimarket' ), wp_date( 'j M Y', strtotime( dm_apply_opt( 'closes' ) . ' 12:00:00' ), new DateTimeZone( 'UTC' ) ) ) ) : '' ) . ( dm_apply_opt( 'enabled' ) && ! dm_apply_open() ? ' · ' . esc_html__( 'closing date has passed', 'digimarket' ) : '' ) . '</small></div></div>';
	if ( $can ) {
		dm_admin_form_open( 'apply_settings' );
		echo '<div class="dm-form-row">';
		echo '<label>' . esc_html__( '“Apply now” button', 'digimarket' ) . '<select name="enabled"><option value="1"' . selected( (int) dm_apply_opt( 'enabled' ), 1, false ) . '>' . esc_html__( 'ON — show button & form', 'digimarket' ) . '</option><option value="0"' . selected( (int) dm_apply_opt( 'enabled' ), 0, false ) . '>' . esc_html__( 'OFF — hide everywhere', 'digimarket' ) . '</option></select></label>';
		echo '<label>' . esc_html__( 'Current batch', 'digimarket' ) . '<input type="number" name="batch" min="1" value="' . (int) $cur . '" style="width:90px"></label>';
		echo '<label>' . esc_html__( 'Closes on (optional)', 'digimarket' ) . '<input type="date" name="closes" value="' . esc_attr( dm_apply_opt( 'closes' ) ) . '"></label>';
		echo '<label>' . esc_html__( 'Top bar text', 'digimarket' ) . '<input type="text" name="headline" value="' . esc_attr( dm_apply_opt( 'headline' ) ) . '" class="regular-text"></label>';
		echo '<label>' . esc_html__( 'Categories (one per line)', 'digimarket' ) . '<textarea name="categories" rows="3" cols="30">' . esc_textarea( dm_apply_opt( 'categories' ) ) . '</textarea></label>';
		echo '</div><p><button class="button button-primary">' . esc_html__( 'Save', 'digimarket' ) . '</button> ';
		/* translators: %d batch */
		echo '<a class="button" onclick="return confirm(\'' . esc_js( sprintf( __( 'Start Batch %d? New applications will go into the new batch and the button will be switched ON.', 'digimarket' ), $cur + 1 ) ) . '\')" href="' . esc_url( dm_admin_action_url( 'apply_new_batch' ) ) . '">' . esc_html( sprintf( /* translators: %d batch */ __( 'Start Batch %d →', 'digimarket' ), $cur + 1 ) ) . '</a></p></form>';
		echo '<p class="description">' . esc_html__( 'Shortcode for any page or article: [pikacart_apply_button]. The button also appears in the top bar, the header and the footer while applications are open.', 'digimarket' ) . '</p>';
	}
	echo '</div>';

	// Stats for the selected batch.
	$sb    = 'all' === $f['batch'] ? '' : $wpdb->prepare( ' WHERE batch = %d', (int) $f['batch'] );
	$stats = $wpdb->get_results( 'SELECT status, category, COUNT(*) n FROM ' . dm_table( 'applications' ) . $sb . ' GROUP BY status, category' ); // phpcs:ignore
	$by_st = array_fill_keys( array_keys( dm_apply_statuses() ), 0 );
	$by_ct = array();
	$total = 0;
	foreach ( $stats as $s ) {
		$by_st[ $s->status ] = ( $by_st[ $s->status ] ?? 0 ) + (int) $s->n;
		if ( ! isset( $by_ct[ $s->category ] ) ) {
			$by_ct[ $s->category ] = array( 'all' => 0, 'approved' => 0 );
		}
		$by_ct[ $s->category ]['all'] += (int) $s->n;
		if ( 'approved' === $s->status ) {
			$by_ct[ $s->category ]['approved'] += (int) $s->n;
		}
		$total += (int) $s->n;
	}
	echo '<div class="dm-stats">';
	dm_stat_card( __( 'Applications', 'digimarket' ), number_format_i18n( $total ), 'accent' );
	foreach ( dm_apply_statuses() as $k => $l ) {
		dm_stat_card( $l, number_format_i18n( $by_st[ $k ] ?? 0 ) );
	}
	echo '</div>';
	if ( $by_ct ) {
		echo '<p class="dm-apply-cats">';
		foreach ( $by_ct as $c => $n ) {
			/* translators: 1 category 2 approved 3 total */
			echo '<span>' . esc_html( sprintf( __( '%1$s: %2$d approved / %3$d applied', 'digimarket' ), $c, $n['approved'], $n['all'] ) ) . '</span>';
		}
		echo '</p>';
	}

	// Filters.
	$max_batch = max( $cur, (int) $wpdb->get_var( 'SELECT MAX(batch) FROM ' . dm_table( 'applications' ) ) ); // phpcs:ignore
	echo '<form method="get" class="dm-filters"><input type="hidden" name="page" value="dm-applications">';
	echo '<select name="batch"><option value="all"' . selected( $f['batch'], 'all', false ) . '>' . esc_html__( 'All batches', 'digimarket' ) . '</option>';
	for ( $b = $max_batch; $b >= 1; $b-- ) {
		echo '<option value="' . (int) $b . '"' . selected( $f['batch'], (string) $b, false ) . '>' . esc_html( dm_apply_batch_label( $b ) . ( $b === $cur ? ' ' . __( '(current)', 'digimarket' ) : '' ) ) . '</option>';
	}
	echo '</select><select name="status"><option value="">' . esc_html__( 'All statuses', 'digimarket' ) . '</option>';
	foreach ( dm_apply_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $f['status'], $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select><select name="category"><option value="">' . esc_html__( 'All categories', 'digimarket' ) . '</option>';
	foreach ( dm_apply_categories() as $c ) {
		echo '<option value="' . esc_attr( $c ) . '"' . selected( $f['category'], $c, false ) . '>' . esc_html( $c ) . '</option>';
	}
	echo '</select><label>' . esc_html__( 'From', 'digimarket' ) . ' <input type="date" name="from" value="' . esc_attr( $f['from'] ) . '"></label><label>' . esc_html__( 'To', 'digimarket' ) . ' <input type="date" name="to" value="' . esc_attr( $f['to'] ) . '"></label>';
	echo '<select name="order"><option value="desc"' . selected( $f['order'], 'desc', false ) . '>' . esc_html__( 'Newest first', 'digimarket' ) . '</option><option value="asc"' . selected( $f['order'], 'asc', false ) . '>' . esc_html__( 'Oldest first (first come)', 'digimarket' ) . '</option></select>';
	echo '<input type="search" name="s" value="' . esc_attr( $f['s'] ) . '" placeholder="' . esc_attr__( 'Name, email, phone, shop', 'digimarket' ) . '"><button class="button">' . esc_html__( 'Filter', 'digimarket' ) . '</button>';
	echo ' <a class="button" href="' . esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'dm_admin', 'do' => 'apply_csv' ), $f ), admin_url( 'admin-post.php' ) ), 'dm_admin_apply_csv' ) ) . '">' . esc_html__( 'Download CSV', 'digimarket' ) . '</a></form>';

	// Table.
	echo '<table class="widefat striped dm-table dm-apply-table"><thead><tr><th>#</th><th>' . esc_html__( 'Applied on', 'digimarket' ) . '</th><th>' . esc_html__( 'Applicant', 'digimarket' ) . '</th><th>' . esc_html__( 'Shop / category', 'digimarket' ) . '</th><th>' . esc_html__( 'Products ready', 'digimarket' ) . '</th><th>' . esc_html__( 'Location', 'digimarket' ) . '</th><th>' . esc_html__( 'PAN / bank', 'digimarket' ) . '</th><th>' . esc_html__( 'Score', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th><th></th></tr></thead><tbody>';
	$badge = array( 'new' => 'info', 'approved' => 'success', 'changes' => 'warning', 'waitlist' => 'neutral', 'rejected' => 'danger' );
	foreach ( $rows as $r ) {
		$url = dm_admin_url( 'dm-applications', array( 'app' => $r->id ) );
		echo '<tr><td><a href="' . esc_url( $url ) . '"><code>' . esc_html( dm_apply_ref( $r ) ) . '</code></a></td>';
		echo '<td>' . esc_html( mysql2date( 'j M Y, g:i a', $r->created_at ) ) . '</td>';
		echo '<td><strong><a href="' . esc_url( $url ) . '">' . esc_html( $r->name ) . '</a></strong><br><small>' . esc_html( $r->email ) . '<br>' . esc_html( $r->phone ) . '</small></td>';
		echo '<td>' . esc_html( $r->shop_name ) . '<br><small>' . esc_html( $r->category ) . '</small></td>';
		echo '<td>' . esc_html( $r->d['ready'] ?? '' ) . '<br><small>' . esc_html( $r->d['price_range'] ?? '' ) . '</small></td>';
		echo '<td>' . esc_html( ( $r->d['city'] ?? '' ) . ', ' . $r->state ) . '</td>';
		echo '<td><small>PAN ••••' . esc_html( $r->d['pan_last4'] ?? '' ) . '<br>A/c ••••' . esc_html( $r->d['acct_last4'] ?? '' ) . '</small></td>';
		echo '<td>' . ( $r->score >= 0 ? '<strong>' . (int) $r->score . '</strong>/10' : '—' ) . '</td>';
		echo '<td><span class="dm-badge dm-badge-' . esc_attr( $badge[ $r->status ] ?? 'neutral' ) . '">' . esc_html( dm_apply_statuses()[ $r->status ] ?? $r->status ) . '</span></td>';
		echo '<td><a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html__( 'Review', 'digimarket' ) . '</a></td></tr>';
	}
	if ( ! $rows ) {
		echo '<tr><td colspan="10">' . esc_html__( 'No applications match these filters.', 'digimarket' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

function dm_admin_application_detail( $id ) {
	$a = dm_apply_get( $id );
	if ( ! $a ) {
		echo '<div class="wrap"><p>' . esc_html__( 'Application not found.', 'digimarket' ) . '</p></div>';
		return;
	}
	$can = current_user_can( 'dm_manage_marketplace' );
	$d   = $a->d;
	dm_admin_header( sprintf( '%s — %s', dm_apply_ref( $a ), $a->name ), ' <a class="page-title-action" href="' . esc_url( dm_admin_url( 'dm-applications', array( 'batch' => $a->batch ) ) ) . '">' . esc_html__( '← All applications', 'digimarket' ) . '</a>' );
	$badge = array( 'new' => 'info', 'approved' => 'success', 'changes' => 'warning', 'waitlist' => 'neutral', 'rejected' => 'danger' );
	echo '<p><span class="dm-badge dm-badge-' . esc_attr( $badge[ $a->status ] ?? 'neutral' ) . '">' . esc_html( dm_apply_statuses()[ $a->status ] ?? $a->status ) . '</span> &nbsp;' . esc_html( dm_apply_batch_label( $a->batch ) ) . ' · ' . esc_html( sprintf( /* translators: %s date */ __( 'applied %s', 'digimarket' ), mysql2date( 'j M Y, g:i a', $a->created_at ) ) ) . ( $a->reviewed_at ? ' · ' . esc_html( sprintf( /* translators: %s date */ __( 'reviewed %s', 'digimarket' ), mysql2date( 'j M Y', $a->reviewed_at ) ) ) : '' ) . ( $a->seller_id ? ' · <a href="' . esc_url( dm_admin_url( 'dm-sellers', array( 'seller' => $a->seller_id ) ) ) . '">' . esc_html__( 'Open seller', 'digimarket' ) . '</a>' : '' ) . '</p>';

	$show = function ( $title, $rows ) {
		echo '<div class="dm-panel"><h2>' . esc_html( $title ) . '</h2><table class="form-table dm-apply-dl">';
		foreach ( $rows as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore
		}
		echo '</table></div>';
	};
	$e       = 'esc_html';
	$samples = '';
	foreach ( array_filter( explode( "\n", (string) ( $d['samples'] ?? '' ) ) ) as $u ) {
		$samples .= '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $u ) . '</a><br>';
	}
	echo '<div class="dm-grid2">';
	$show(
		__( 'Applicant', 'digimarket' ),
		array(
			__( 'Full name', 'digimarket' ) => $e( $d['full_name'] ?? '' ),
			__( 'Email', 'digimarket' )     => '<a href="mailto:' . esc_attr( $a->email ) . '">' . esc_html( $a->email ) . '</a>',
			__( 'Mobile', 'digimarket' )    => $e( $a->phone ) . ( ! empty( $d['whatsapp'] ) ? ' · WhatsApp ' . $e( $d['whatsapp'] ) : '' ),
			__( 'Address', 'digimarket' )   => $e( implode( ', ', array_filter( array( $d['address'] ?? '', $d['city'] ?? '', $d['state'] ?? '', $d['pin'] ?? '' ) ) ) ),
		)
	);
	$show(
		__( 'PAN, tax & bank', 'digimarket' ),
		array(
			__( 'Selling as', 'digimarket' )     => $e( dm_apply_seller_types()[ $d['seller_type'] ?? '' ] ?? '' ) . ( ! empty( $d['business_name'] ) ? ' — ' . $e( $d['business_name'] ) : '' ),
			__( 'PAN', 'digimarket' )            => '<code>' . $e( $can ? dm_decrypt( $d['pan'] ?? '' ) : '••••••' . ( $d['pan_last4'] ?? '' ) ) . '</code>',
			__( 'GSTIN', 'digimarket' )          => ! empty( $d['gstin'] ) ? '<code>' . $e( $d['gstin'] ) . '</code>' : esc_html__( 'Not registered (not compulsory below ₹20 lakh yearly sales)', 'digimarket' ),
			__( 'Account holder', 'digimarket' ) => $e( $d['acct_name'] ?? '' ),
			__( 'Bank', 'digimarket' )           => $e( $d['bank_name'] ?? '' ),
			__( 'Account number', 'digimarket' ) => '<code>' . $e( $can ? dm_decrypt( $d['acct'] ?? '' ) : '••••' . ( $d['acct_last4'] ?? '' ) ) . '</code>',
			__( 'IFSC', 'digimarket' )           => '<code>' . $e( $d['ifsc'] ?? '' ) . '</code>',
			__( 'UPI', 'digimarket' )            => $e( $d['upi'] ?? '—' ),
		)
	);
	echo '</div>';
	$show(
		__( 'Shop & products', 'digimarket' ),
		array(
			__( 'Shop name', 'digimarket' )       => $e( $a->shop_name ),
			__( 'Category', 'digimarket' )        => $e( $a->category ),
			__( 'About', 'digimarket' )           => nl2br( $e( $d['about'] ?? '' ) ),
			__( 'Products', 'digimarket' )        => nl2br( $e( $d['products'] ?? '' ) ),
			__( 'Ready to list', 'digimarket' )   => $e( $d['ready'] ?? '' ),
			__( 'Price range', 'digimarket' )     => $e( $d['price_range'] ?? '' ),
			__( 'Samples', 'digimarket' )         => $samples,
			__( 'Course hosting', 'digimarket' )  => $e( $d['hosting'] ?? '' ),
			__( 'Sells elsewhere', 'digimarket' ) => $e( $d['other_sites'] ?? '' ),
		)
	);

	// Documents.
	echo '<div class="dm-panel"><h2>' . esc_html__( 'Documents', 'digimarket' ) . '</h2><p>';
	foreach ( array( 'pan_doc' => __( 'PAN card', 'digimarket' ), 'bank_doc' => __( 'Bank proof', 'digimarket' ) ) as $k => $l ) {
		if ( ! empty( $a->docs[ $k ] ) && $can ) {
			echo '<a class="button" target="_blank" href="' . esc_url( dm_admin_action_url( 'apply_doc', array( 'app' => $a->id, 'doc' => $k ) ) ) . '">' . dm_icon( 'download', 16 ) . ' ' . esc_html( $l ) . '</a> '; // phpcs:ignore
		} elseif ( ! empty( $a->docs[ $k ] ) ) {
			echo '<span class="button disabled">' . esc_html( $l ) . '</span> ';
		}
	}
	$ag = $d['agreed'] ?? array();
	echo '</p><p class="description">' . esc_html( sprintf( /* translators: 1 date 2 IP */ __( 'Declarations accepted %1$s from IP %2$s.', 'digimarket' ), ! empty( $ag['time'] ) ? wp_date( 'j M Y, g:i a', (int) $ag['time'] ) : '—', $ag['ip'] ?? $a->ip ) ) . '</p></div>';

	if ( ! $can ) {
		echo '</div>';
		return;
	}
	// Review panel.
	echo '<div class="dm-panel dm-apply-review"><h2>' . esc_html__( 'Review', 'digimarket' ) . '</h2>';
	echo '<table class="widefat dm-apply-rubric"><thead><tr><th>' . esc_html__( 'Quality check', 'digimarket' ) . '</th><th>' . esc_html__( 'Points', 'digimarket' ) . '</th></tr></thead><tbody>';
	foreach ( array( __( 'Samples / portfolio good and original', 'digimarket' ) => 3, __( '3–5 products ready to list', 'digimarket' ) => 2, __( 'Clear product plan and fair price', 'digimarket' ) => 2, __( 'Rights confirmed (no PLR / nulled / copied)', 'digimarket' ) => 2, __( 'Complete application, replies on time', 'digimarket' ) => 1 ) as $k => $v ) {
		echo '<tr><td>' . esc_html( $k ) . '</td><td>' . (int) $v . '</td></tr>';
	}
	echo '</tbody></table>';
	dm_admin_form_open( 'apply_review' );
	echo '<input type="hidden" name="app" value="' . (int) $a->id . '"><div class="dm-form-row">';
	echo '<label>' . esc_html__( 'Score (out of 10)', 'digimarket' ) . '<select name="score"><option value="-1">—</option>';
	for ( $i = 0; $i <= 10; $i++ ) {
		echo '<option value="' . (int) $i . '"' . selected( (int) $a->score, $i, false ) . '>' . (int) $i . '</option>';
	}
	echo '</select></label>';
	echo '<label>' . esc_html__( 'Decision', 'digimarket' ) . '<select name="status">';
	foreach ( dm_apply_statuses() as $k => $l ) {
		if ( 'approved' === $k ) {
			continue;
		}
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $a->status, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></label>';
	echo '<label class="dm-apply-wide">' . esc_html__( 'Message to applicant (sent by email for Needs changes / Waiting list / Rejected)', 'digimarket' ) . '<textarea name="message" rows="3" class="large-text"></textarea></label>';
	echo '<label class="dm-apply-wide">' . esc_html__( 'Private note', 'digimarket' ) . '<textarea name="note" rows="2" class="large-text">' . esc_textarea( (string) $a->admin_note ) . '</textarea></label>';
	echo '<label><input type="checkbox" name="notify" value="1" checked> ' . esc_html__( 'Email the applicant about this decision', 'digimarket' ) . '</label>';
	echo '</div><p><button class="button">' . esc_html__( 'Save review', 'digimarket' ) . '</button></p></form>';

	if ( 'approved' !== $a->status ) {
		echo '<hr><h3>' . esc_html__( 'Approve & create seller account', 'digimarket' ) . '</h3><p class="description">' . esc_html__( 'Creates the seller account with this shop name, saves the PAN, bank and address as verified payout details, and emails a set-password link. The seller only has to accept the Seller Agreement to start listing.', 'digimarket' ) . '</p>';
		dm_admin_form_open( 'apply_approve' );
		echo '<input type="hidden" name="app" value="' . (int) $a->id . '"><label>' . esc_html__( 'Commission % (blank = default)', 'digimarket' ) . ' <input type="number" name="commission" min="0" max="100" step="0.01" style="width:100px"></label> ';
		echo '<button class="button button-primary" onclick="return confirm(\'' . esc_js( __( 'Approve this application and create the seller account?', 'digimarket' ) ) . '\')">' . esc_html__( 'Approve & create seller', 'digimarket' ) . '</button></form>';
	}
	echo '<hr><p><a class="button dm-danger" onclick="return confirm(\'' . esc_js( __( 'Delete this application and its documents permanently?', 'digimarket' ) ) . '\')" href="' . esc_url( dm_admin_action_url( 'apply_delete', array( 'app' => $a->id ) ) ) . '">' . esc_html__( 'Delete application & documents', 'digimarket' ) . '</a></p>';
	echo '</div></div>';
}

/* -------------------------------------------------------------------------
 * Admin actions
 * ---------------------------------------------------------------------- */

add_action( 'dm_admin_do_apply_settings', function ( $r ) {
	$closes = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['closes'] ?? '' ) ) ? $r['closes'] : '';
	$cats   = implode( "\n", array_slice( array_filter( array_map( 'sanitize_text_field', explode( "\n", (string) ( $r['categories'] ?? '' ) ) ) ), 0, 20 ) );
	$was    = (int) dm_apply_opt( 'enabled' );
	dm_apply_update(
		array(
			'enabled'    => empty( $r['enabled'] ) ? 0 : 1,
			'batch'      => max( 1, absint( $r['batch'] ?? 1 ) ),
			'closes'     => $closes,
			'headline'   => sanitize_text_field( $r['headline'] ?? '' ) ? sanitize_text_field( $r['headline'] ) : dm_apply_defaults()['headline'],
			'categories' => $cats ? $cats : dm_apply_defaults()['categories'],
		)
	);
	dm_audit( 'apply_settings', 'settings', 0, array( 'enabled' => dm_apply_opt( 'enabled' ), 'batch' => dm_apply_opt( 'batch' ) ) );
	if ( function_exists( 'dm_purge_page_cache' ) ) {
		dm_purge_page_cache();
	} else {
		do_action( 'litespeed_purge_all' );
	}
	dm_admin_back( $was !== (int) dm_apply_opt( 'enabled' ) ? ( dm_apply_opt( 'enabled' ) ? __( 'Apply button switched ON.', 'digimarket' ) : __( 'Apply button switched OFF.', 'digimarket' ) ) : __( 'Saved.', 'digimarket' ) );
} );

add_action( 'dm_admin_do_apply_new_batch', function () {
	$next = (int) dm_apply_opt( 'batch' ) + 1;
	dm_apply_update( array( 'batch' => $next, 'enabled' => 1, 'closes' => '' ) );
	dm_audit( 'apply_new_batch', 'settings', 0, array( 'batch' => $next ) );
	do_action( 'litespeed_purge_all' );
	/* translators: %d batch */
	wp_safe_redirect( add_query_arg( 'dm_msg', rawurlencode( sprintf( __( 'Batch %d started — applications are open.', 'digimarket' ), $next ) ), admin_url( 'admin.php?page=dm-applications' ) ) );
	exit;
} );

add_action( 'dm_admin_do_apply_review', function ( $r ) {
	global $wpdb;
	$a = dm_apply_get( absint( $r['app'] ?? 0 ) );
	if ( ! $a ) {
		dm_admin_back( '', __( 'Application not found.', 'digimarket' ) );
	}
	$status = sanitize_key( $r['status'] ?? $a->status );
	$status = isset( dm_apply_statuses()[ $status ] ) && 'approved' !== $status ? $status : $a->status;
	$score  = max( -1, min( 10, (int) ( $r['score'] ?? -1 ) ) );
	$msg    = sanitize_textarea_field( $r['message'] ?? '' );
	$wpdb->update( dm_table( 'applications' ), array( 'status' => $status, 'score' => $score, 'admin_note' => sanitize_textarea_field( $r['note'] ?? '' ), 'reviewed_at' => dm_now() ), array( 'id' => $a->id ) );
	if ( ! empty( $r['notify'] ) && $status !== $a->status && in_array( $status, array( 'changes', 'waitlist', 'rejected' ), true ) ) {
		$subjects = array(
			'changes'  => __( 'Your seller application needs a few changes', 'digimarket' ),
			'waitlist' => __( 'You are on the PikaCart seller waiting list', 'digimarket' ),
			'rejected' => __( 'Update on your PikaCart seller application', 'digimarket' ),
		);
		$bodies = array(
			'changes'  => __( 'Thank you for applying. Before we can approve your shop, we need a few changes. Please reply to this email with the details below.', 'digimarket' ),
			'waitlist' => __( 'Thank you for applying. All spots in your category for this batch are filled, so we have added you to the waiting list in the order your application arrived. We will email you as soon as a spot opens.', 'digimarket' ),
			'rejected' => __( 'Thank you for applying. After reviewing your application we are unable to approve it at this time. You are welcome to apply again in a future batch.', 'digimarket' ),
		);
		$html = '<p>' . sprintf( /* translators: %s name */ esc_html__( 'Hi %s,', 'digimarket' ), esc_html( $a->name ) ) . '</p><p>' . esc_html( $bodies[ $status ] ) . '</p>' . ( $msg ? '<p style="background:#f6f6f9;border-radius:8px;padding:12px 14px">' . nl2br( esc_html( $msg ) ) . '</p>' : '' ) . '<p style="color:#777;font-size:13px">' . esc_html( sprintf( /* translators: %s reference */ __( 'Application reference: %s', 'digimarket' ), dm_apply_ref( $a ) ) ) . '</p>';
		dm_mail( $a->email, $subjects[ $status ], $html );
	}
	dm_audit( 'apply_review', 'application', $a->id, array( 'status' => $status, 'score' => $score ) );
	dm_admin_back( __( 'Review saved.', 'digimarket' ) );
} );

add_action( 'dm_admin_do_apply_approve', function ( $r ) {
	global $wpdb;
	$a = dm_apply_get( absint( $r['app'] ?? 0 ) );
	if ( ! $a || 'approved' === $a->status ) {
		dm_admin_back( '', __( 'Application not found or already approved.', 'digimarket' ) );
	}
	$d       = $a->d;
	$acct    = dm_decrypt( $d['acct'] ?? '' );
	$btype   = 'individual' === ( $d['seller_type'] ?? '' ) ? 'individual' : ( 'private_limited' === ( $d['seller_type'] ?? '' ) ? 'private_limited' : ( in_array( $d['seller_type'] ?? '', array( 'proprietorship', 'partnership', 'llp' ), true ) ? $d['seller_type'] : 'individual' ) );
	$prefill = array(
		'dm_legal_name'    => $d['full_name'] ?? $a->name,
		'dm_pan'           => $d['pan'] ?? '',
		'dm_phone'         => '+91' . $a->phone,
		'dm_business_type' => $btype,
		'dm_bank_account'  => $acct ? dm_encrypt( $acct ) : '',
		'dm_bank_last4'    => $acct ? substr( $acct, -4 ) : '',
		'dm_ifsc'          => $d['ifsc'] ?? '',
		'dm_upi'           => $d['upi'] ?? '',
		'dm_address'       => array( 'street' => $d['address'] ?? '', 'city' => $d['city'] ?? '', 'state' => $d['state'] ?? '', 'postal_code' => $d['pin'] ?? '' ),
		'dm_shop_bio'      => $d['about'] ?? '',
		'dm_kyc_submitted' => time(),
		'dm_kyc_status'    => 'verified',
		'dm_application'   => $a->id,
	);
	if ( ! empty( $d['gstin'] ) ) {
		$prefill['dm_gstin'] = $d['gstin'];
	}
	$uid = dm_create_invited_seller( $a->email, $d['full_name'] ?? $a->name, $a->shop_name, $r['commission'] ?? '', $prefill );
	if ( is_wp_error( $uid ) ) {
		dm_admin_back( '', $uid->get_error_message() );
	}
	$wpdb->update( dm_table( 'applications' ), array( 'status' => 'approved', 'seller_id' => $uid, 'reviewed_at' => dm_now() ), array( 'id' => $a->id ) );
	dm_audit( 'apply_approve', 'application', $a->id, array( 'seller' => $uid ) );
	/* translators: %s email */
	dm_admin_back( sprintf( __( 'Approved! Seller account created and the welcome email sent to %s.', 'digimarket' ), $a->email ) );
} );

add_action( 'dm_admin_do_apply_delete', function ( $r ) {
	global $wpdb;
	$a = dm_apply_get( absint( $r['app'] ?? 0 ) );
	if ( $a ) {
		foreach ( $a->docs as $doc ) {
			$path = trailingslashit( dm_private_dir() ) . 'applications/' . basename( (string) ( $doc['file'] ?? '' ) );
			if ( ! empty( $doc['file'] ) && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$wpdb->delete( dm_table( 'applications' ), array( 'id' => $a->id ) );
		dm_audit( 'apply_delete', 'application', $a->id );
	}
	wp_safe_redirect( add_query_arg( 'dm_msg', rawurlencode( __( 'Application deleted.', 'digimarket' ) ), admin_url( 'admin.php?page=dm-applications' ) ) );
	exit;
} );

add_action( 'dm_admin_do_apply_doc', function ( $r ) {
	$a   = dm_apply_get( absint( $r['app'] ?? 0 ) );
	$key = in_array( $r['doc'] ?? '', array( 'pan_doc', 'bank_doc' ), true ) ? $r['doc'] : '';
	if ( ! $a || ! $key || empty( $a->docs[ $key ]['file'] ) ) {
		wp_die( esc_html__( 'Document not found.', 'digimarket' ), '', array( 'response' => 404 ) );
	}
	$doc  = $a->docs[ $key ];
	$path = trailingslashit( dm_private_dir() ) . 'applications/' . basename( $doc['file'] );
	if ( ! file_exists( $path ) ) {
		wp_die( esc_html__( 'Document not found.', 'digimarket' ), '', array( 'response' => 404 ) );
	}
	dm_audit( 'apply_doc_view', 'application', $a->id, array( 'doc' => $key ) );
	nocache_headers();
	header( 'Content-Type: ' . ( in_array( $doc['type'], array( 'image/jpeg', 'image/png', 'application/pdf' ), true ) ? $doc['type'] : 'application/octet-stream' ) );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( dm_apply_ref( $a ) . '-' . $key . '.' . pathinfo( $doc['file'], PATHINFO_EXTENSION ) ) . '"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); // phpcs:ignore
	exit;
} );

add_action( 'dm_admin_do_apply_csv', function ( $r ) {
	$f = array(
		'batch'    => 'all' === ( $r['batch'] ?? '' ) ? 'all' : (string) max( 1, absint( $r['batch'] ?? dm_apply_opt( 'batch' ) ) ),
		'status'   => sanitize_key( $r['status'] ?? '' ),
		'category' => sanitize_text_field( $r['category'] ?? '' ),
		's'        => sanitize_text_field( $r['s'] ?? '' ),
		'from'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['from'] ?? '' ) ) ? $r['from'] : '',
		'to'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $r['to'] ?? '' ) ) ? $r['to'] : '',
		'order'    => 'asc' === ( $r['order'] ?? '' ) ? 'asc' : 'desc',
	);
	$out = array();
	foreach ( dm_apply_query( $f, 5000 ) as $a ) {
		$d     = $a->d;
		$out[] = array( dm_apply_ref( $a ), $a->batch, $a->created_at, dm_apply_statuses()[ $a->status ] ?? $a->status, $a->score >= 0 ? $a->score : '', $a->name, $a->email, $a->phone, $d['whatsapp'] ?? '', $d['city'] ?? '', $a->state, $d['pin'] ?? '', $a->shop_name, $a->category, $d['ready'] ?? '', $d['price_range'] ?? '', str_replace( "\n", ' ', $d['samples'] ?? '' ), dm_apply_seller_types()[ $d['seller_type'] ?? '' ] ?? '', '••••••' . ( $d['pan_last4'] ?? '' ), $d['gstin'] ?? '', $d['bank_name'] ?? '', '••••' . ( $d['acct_last4'] ?? '' ), $d['ifsc'] ?? '', $d['upi'] ?? '' );
	}
	dm_output_csv( 'seller-applications-' . ( 'all' === $f['batch'] ? 'all' : 'batch-' . $f['batch'] ) . '.csv', array( 'Reference', 'Batch', 'Applied on', 'Status', 'Score', 'Name', 'Email', 'Mobile', 'WhatsApp', 'City', 'State', 'PIN', 'Shop', 'Category', 'Products ready', 'Price range', 'Samples', 'Selling as', 'PAN', 'GSTIN', 'Bank', 'Account', 'IFSC', 'UPI' ), $out );
} );
