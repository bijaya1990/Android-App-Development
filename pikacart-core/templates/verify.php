<?php
/**
 * Public card verification page: /verify/{token}
 * Shows only the fields the organisation allowed. Never indexed by search engines.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

$pkc_title  = __( 'Card verification', 'pikacart' );
$pkc_styles = array( 'pkc-auth' );

if ( ! empty( $pkc['demo'] ) ) {
	$pkc_status = 'valid';
	$pkc_orgn   = 'Sunrise Public School';
	$pkc_logo   = '';
	$pkc_rows   = array(
		__( 'Name', 'pikacart' )     => 'Aarav Mehta',
		__( 'ID number', 'pikacart' ) => 'STU1040',
		__( 'Class', 'pikacart' )    => 'VIII - A',
		__( 'Valid till', 'pikacart' ) => '31-03-' . ( (int) wp_date( 'Y' ) + 1 ),
	);
	$pkc_photo  = '';
	$pkc_free   = false;
	$pkc_token  = '';
	$pkc_demo   = true;
} elseif ( empty( $pkc['missing'] ) ) {
	$pkc_m      = $pkc['member'];
	$pkc_org    = $pkc['org'];
	$pkc_status = $pkc['status'];
	$pkc_orgn   = $pkc_org->name;
	$pkc_logo   = $pkc_org->logo;
	$pkc_allow  = array_merge( PKC_Organisations::verify_field_defaults(), pkc_json( $pkc_org->verify_fields ) );
	$pkc_data   = pkc_json( $pkc_m->data );
	$pkc_rows   = array();
	if ( $pkc_allow['name'] ) {
		$pkc_rows[ __( 'Name', 'pikacart' ) ] = $pkc_m->name;
	}
	if ( $pkc_allow['id_no'] ) {
		$pkc_rows[ __( 'ID number', 'pikacart' ) ] = $pkc_m->id_no;
	}
	$pkc_map = array(
		'class'       => array( __( 'Class / Course', 'pikacart' ), trim( ( $pkc_data['class'] ?? '' ) . ( ! empty( $pkc_data['section'] ) ? ' - ' . $pkc_data['section'] : '' ) ) ),
		'designation' => array( __( 'Designation', 'pikacart' ), $pkc_data['designation'] ?? '' ),
		'department'  => array( __( 'Department', 'pikacart' ), $pkc_data['department'] ?? '' ),
		'blood_group' => array( __( 'Blood group', 'pikacart' ), $pkc_data['blood_group'] ?? '' ),
		'mobile'      => array( __( 'Mobile', 'pikacart' ), $pkc_data['mobile'] ?? '' ),
		'email'       => array( __( 'Email', 'pikacart' ), $pkc_data['email'] ?? '' ),
		'dob'         => array( __( 'Date of birth', 'pikacart' ), $pkc_data['dob'] ?? '' ),
		'address'     => array( __( 'Address', 'pikacart' ), $pkc_data['address'] ?? '' ),
	);
	foreach ( $pkc_map as $pkc_k => $pkc_v ) {
		if ( ! empty( $pkc_allow[ $pkc_k ] ) && '' !== trim( (string) $pkc_v[1] ) ) {
			$pkc_rows[ $pkc_v[0] ] = $pkc_v[1];
		}
	}
	if ( $pkc_allow['validity'] && ( $pkc_m->valid_from || $pkc_m->valid_until ) ) {
		$pkc_rows[ __( 'Validity', 'pikacart' ) ] = trim( PKC_Members::format_date( $pkc_m->valid_from ) . ' → ' . PKC_Members::format_date( $pkc_m->valid_until ), ' →' );
	}
	if ( '' !== (string) $pkc_m->session ) {
		$pkc_rows[ __( 'Session', 'pikacart' ) ] = $pkc_m->session;
	}
	$pkc_photo = $pkc_allow['photo'] ? $pkc_m->photo : '';
	$pkc_free  = PKC_Access::needs_watermark( $pkc_org );
	$pkc_token = $pkc_m->verify_token;
	$pkc_demo  = false;
}

$pkc_labels = array(
	'valid'     => array( __( 'Valid card', 'pikacart' ), 'check', __( 'This card was issued by the organisation below and is currently valid.', 'pikacart' ) ),
	'expired'   => array( __( 'Card expired', 'pikacart' ), 'clock', __( 'This card was issued by the organisation below but its validity has ended.', 'pikacart' ) ),
	'cancelled' => array( __( 'Card cancelled', 'pikacart' ), 'alert', __( 'This card is no longer valid. Do not accept it as identification.', 'pikacart' ) ),
);

PKC_Assets::register();
require PKC_DIR . 'templates/partials/head.php';
?>
<body class="pkc pkc-verify-page">
<?php require PKC_DIR . 'templates/icons.php'; ?>
<main class="msg-wrap">
	<div class="msg-card verify-card">
		<div class="verify-brand"><?php echo pkc_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Card verification', 'pikacart' ); ?></span></div>
		<?php if ( ! empty( $pkc['missing'] ) ) : ?>
			<div class="msg-icon msg-icon-alert"><svg class="pkc-i"><use href="#i-alert"></use></svg></div>
			<h1><?php esc_html_e( 'Card not found', 'pikacart' ); ?></h1>
			<p><?php esc_html_e( 'We could not find a card for this code. It may have been deleted, or the code is not genuine. Do not accept this card as identification.', 'pikacart' ); ?></p>
		<?php else : ?>
			<div class="verify-status verify-<?php echo esc_attr( $pkc_status ); ?>">
				<svg class="pkc-i"><use href="#i-<?php echo esc_attr( $pkc_labels[ $pkc_status ][1] ); ?>"></use></svg>
				<div><strong><?php echo esc_html( $pkc_labels[ $pkc_status ][0] ); ?></strong><span><?php esc_html_e( 'Verified by Pikacart', 'pikacart' ); ?></span></div>
			</div>
			<div class="verify-org">
				<?php if ( $pkc_logo ) : ?>
					<img src="<?php echo esc_url( $pkc_logo ); ?>" alt="">
				<?php endif; ?>
				<strong><?php echo esc_html( $pkc_orgn ); ?></strong>
			</div>
			<?php if ( $pkc_photo ) : ?>
				<img class="verify-photo" src="<?php echo esc_url( $pkc_photo ); ?>" alt="<?php esc_attr_e( 'Card holder photo', 'pikacart' ); ?>">
			<?php endif; ?>
			<dl class="verify-rows">
				<?php foreach ( $pkc_rows as $pkc_l => $pkc_v ) : ?>
					<dt><?php echo esc_html( $pkc_l ); ?></dt><dd><?php echo esc_html( $pkc_v ); ?></dd>
				<?php endforeach; ?>
			</dl>
			<p class="verify-note"><?php echo esc_html( $pkc_labels[ $pkc_status ][2] ); ?></p>
			<?php if ( $pkc_demo ) : ?>
				<p class="verify-free"><?php esc_html_e( 'This is a demo card shown in the Pikacart design gallery.', 'pikacart' ); ?></p>
			<?php elseif ( $pkc_free ) : ?>
				<p class="verify-free"><?php esc_html_e( 'Made with the free version of Pikacart.', 'pikacart' ); ?></p>
			<?php endif; ?>
			<?php if ( $pkc_token ) : ?>
				<details class="verify-report">
					<summary><?php esc_html_e( 'Report a problem with this card', 'pikacart' ); ?></summary>
					<form id="pkc-report" data-token="<?php echo esc_attr( $pkc_token ); ?>">
						<div class="field"><label for="r-reason"><?php esc_html_e( 'What is wrong?', 'pikacart' ); ?></label><textarea id="r-reason" name="reason" rows="3" required></textarea></div>
						<div class="field"><label for="r-email"><?php esc_html_e( 'Your email (optional)', 'pikacart' ); ?></label><input id="r-email" type="email" name="email"></div>
						<div class="form-msg" role="alert"></div>
						<button class="btn btn-primary btn-sm" type="submit"><?php esc_html_e( 'Send report', 'pikacart' ); ?></button>
					</form>
				</details>
				<script>
				( function () {
					var f = document.getElementById( 'pkc-report' );
					f.addEventListener( 'submit', function ( e ) {
						e.preventDefault();
						var msg = f.querySelector( '.form-msg' );
						fetch( <?php echo wp_json_encode( esc_url_raw( rest_url( 'pkc/v1/public/report' ) ) ); ?>, {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify( { token: f.dataset.token, reason: f.reason.value, email: f.email.value } )
						} ).then( function ( r ) { return r.json().then( function ( d ) { return { ok: r.ok, d: d }; } ); } )
							.then( function ( x ) { msg.textContent = x.d.message || ''; msg.className = 'form-msg ' + ( x.ok ? 'is-success' : 'is-error' ); if ( x.ok ) { f.reset(); } } );
					} );
				}() );
				</script>
			<?php endif; ?>
		<?php endif; ?>
		<p class="msg-help"><?php echo esc_html( wp_date( 'j M Y, g:i a' ) ); ?></p>
	</div>
</main>
</body>
</html>
