<?php
/**
 * GST: master on/off switch, store GSTIN, seller GSTINs, financial-year
 * turnover tracking with ₹20 lakh alerts, per-seller "GST required" control,
 * and tax figures for receipts.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

function dm_gst_defaults() {
	return array(
		'enabled'   => 0,
		'gstin'     => '',
		'rate'      => 18,
		'state'     => 'Odisha',
		'threshold' => 2000000,
		'warn_pct'  => 80,
		'comm_rate' => 18,
	);
}

/**
 * Indian-style rupees: ₹20,00,000 (paise only when present).
 */
function dm_inr( $amount ) {
	$amount = round( (float) $amount, 2 );
	$neg    = $amount < 0;
	$int    = (string) floor( abs( $amount ) );
	$dec    = round( ( abs( $amount ) - floor( abs( $amount ) ) ) * 100 );
	$last3  = substr( $int, -3 );
	$rest   = substr( $int, 0, -3 );
	$out    = $rest ? preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest ) . ',' . $last3 : $last3;
	return ( $neg ? '−' : '' ) . '₹' . $out . ( $dec ? '.' . str_pad( (string) $dec, 2, '0', STR_PAD_LEFT ) : '' );
}

function dm_gst() {
	$saved = get_option( 'dm_gst', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), dm_gst_defaults() );
}

function dm_gst_opt( $key ) {
	$g = dm_gst();
	return $g[ $key ] ?? null;
}

function dm_gst_enabled() {
	return ! empty( dm_gst()['enabled'] );
}

/**
 * The store's own GSTIN (GST settings, else the older invoice setting).
 */
function dm_store_gstin() {
	$g = (string) dm_gst_opt( 'gstin' );
	return $g ? $g : strtoupper( (string) dm_opt( 'invoice_gstin' ) );
}

/**
 * 15-character GSTIN: 2-digit state code, PAN, entity number, Z, checksum.
 */
function dm_valid_gstin( $g ) {
	return (bool) preg_match( '/^[0-3][0-9][A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', strtoupper( trim( (string) $g ) ) );
}

function dm_seller_gstin( $uid ) {
	return strtoupper( (string) get_user_meta( $uid, 'dm_gstin', true ) );
}

/**
 * GST charged to a seller on the platform commission: only when GST is ON,
 * never on the store owner's own sales.
 */
function dm_commission_gst( $commission, $seller_id ) {
	if ( ! dm_gst_enabled() || $commission <= 0 || user_can( $seller_id, 'manage_options' ) ) {
		return 0.0;
	}
	return dm_round( $commission * (float) dm_gst_opt( 'comm_rate' ) / 100 );
}

/* -------------------------------------------------------------------------
 * Turnover (Indian financial year: 1 April – 31 March)
 * ---------------------------------------------------------------------- */

function dm_fy_start( $ts = 0 ) {
	$ts = $ts ? $ts : current_time( 'timestamp' );
	$y  = (int) gmdate( 'Y', $ts );
	if ( (int) gmdate( 'n', $ts ) < 4 ) {
		--$y;
	}
	return $y . '-04-01 00:00:00';
}

function dm_fy_label() {
	$y = (int) substr( dm_fy_start(), 0, 4 );
	return 'FY ' . $y . '–' . substr( (string) ( $y + 1 ), 2 );
}

/**
 * Paid (not refunded) sales this financial year: one seller, or the whole store when $uid = 0.
 */
function dm_fy_turnover( $uid = 0 ) {
	global $wpdb;
	$i   = dm_table( 'order_items' );
	$sql = "SELECT COALESCE(SUM(price_at_purchase),0) FROM $i WHERE item_status = 'paid' AND created_at >= %s";
	return $uid ? (float) $wpdb->get_var( $wpdb->prepare( $sql . ' AND seller_id = %d', dm_fy_start(), $uid ) ) : (float) $wpdb->get_var( $wpdb->prepare( $sql, dm_fy_start() ) ); // phpcs:ignore
}

function dm_gst_level( $amount ) {
	$lim = max( 1, (float) dm_gst_opt( 'threshold' ) );
	if ( $amount >= $lim ) {
		return 'over';
	}
	return $amount >= $lim * (float) dm_gst_opt( 'warn_pct' ) / 100 ? 'near' : 'ok';
}

/* -------------------------------------------------------------------------
 * Seller "GST required" control and gating
 * ---------------------------------------------------------------------- */

function dm_seller_gst_required( $uid ) {
	return (bool) get_user_meta( $uid, 'dm_gst_required', true );
}

/**
 * Required by the admin but no valid GSTIN yet → the seller cannot sell.
 */
function dm_seller_gst_blocked( $uid ) {
	if ( ! $uid || user_can( $uid, 'manage_options' ) ) {
		return false;
	}
	return dm_seller_gst_required( $uid ) && ! dm_valid_gstin( dm_seller_gstin( $uid ) );
}

/**
 * Tax figures for one order item (prices are GST-inclusive).
 * Returns null when the supplier does not charge GST.
 */
function dm_item_tax( $item ) {
	if ( ! dm_gst_enabled() ) {
		return null;
	}
	$seller = (int) $item->seller_id;
	if ( user_can( $seller, 'manage_options' ) || dm_single_seller_mode() ) {
		$gstin = dm_store_gstin();
	} else {
		$gstin = get_user_meta( $seller, 'dm_gst_show', true ) ? dm_seller_gstin( $seller ) : '';
	}
	if ( ! dm_valid_gstin( $gstin ) ) {
		return null;
	}
	$rate    = (float) dm_gst_opt( 'rate' );
	$amount  = (float) $item->price_at_purchase;
	$taxable = round( $amount / ( 1 + $rate / 100 ), 2 );
	$tax     = round( $amount - $taxable, 2 );
	return array(
		'gstin'   => $gstin,
		'rate'    => $rate,
		'taxable' => $taxable,
		'tax'     => $tax,
		'cgst'    => round( $tax / 2, 2 ),
		'sgst'    => $tax - round( $tax / 2, 2 ),
	);
}

/* -------------------------------------------------------------------------
 * Threshold alerts (after every paid order, once per level per financial year)
 * ---------------------------------------------------------------------- */

add_action( 'dm_order_paid', 'dm_gst_check_thresholds', 20 );
function dm_gst_check_thresholds( $order_id = 0 ) {
	$fy      = substr( dm_fy_start(), 0, 4 );
	$targets = array( 0 );
	if ( $order_id && ! dm_single_seller_mode() ) {
		foreach ( dm_get_order_items( $order_id ) as $it ) {
			$targets[] = (int) $it->seller_id;
		}
	}
	foreach ( array_unique( $targets ) as $uid ) {
		$amount = dm_fy_turnover( $uid );
		$level  = dm_gst_level( $amount );
		if ( 'ok' === $level ) {
			continue;
		}
		$key  = $fy . ':' . $level;
		$sent = $uid ? get_user_meta( $uid, 'dm_gst_alert', true ) : get_option( 'dm_gst_alert' );
		if ( $sent === $key || ( 'near' === $level && $sent === $fy . ':over' ) ) {
			continue;
		}
		$uid ? update_user_meta( $uid, 'dm_gst_alert', $key ) : update_option( 'dm_gst_alert', $key );
		$who = $uid ? dm_shop_name( $uid ) : __( 'Your store', 'digimarket' );
		/* translators: 1 who 2 amount 3 FY 4 limit */
		$msg = sprintf( 'over' === $level ? __( 'GST alert: %1$s has crossed %4$s in sales (%2$s in %3$s). GST registration is now required.', 'digimarket' ) : __( 'GST alert: %1$s has reached %2$s in sales in %3$s — close to the %4$s GST limit.', 'digimarket' ), $who, dm_inr( $amount ), dm_fy_label(), dm_inr( dm_gst_opt( 'threshold' ) ) );
		dm_notify_admins( $msg, admin_url( 'admin.php?page=dm-gst' ) );
		foreach ( get_users( array( 'capability' => 'dm_manage_marketplace', 'fields' => array( 'user_email' ) ) ) as $a ) {
			dm_mail( $a->user_email, __( 'GST turnover alert', 'digimarket' ), '<p>' . esc_html( $msg ) . '</p>', admin_url( 'admin.php?page=dm-gst' ), __( 'Open GST settings', 'digimarket' ) );
		}
	}
}

/* -------------------------------------------------------------------------
 * Admin: GST page, master switch and per-seller control
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'dm-marketplace', __( 'GST & tax', 'digimarket' ), __( 'GST & tax', 'digimarket' ), 'dm_manage_marketplace', 'dm-gst', 'dm_admin_gst' );
}, 25 );

/**
 * The big ON/OFF card (GST page, admin Overview and the owner's front dashboard).
 */
function dm_gst_switch_card() {
	$on    = dm_gst_enabled();
	$gstin = dm_store_gstin();
	$sales = dm_fy_turnover( 0 );
	$lim   = (float) dm_gst_opt( 'threshold' );
	$pct   = min( 100, $lim ? round( $sales / $lim * 100 ) : 0 );
	$level = dm_gst_level( $sales );
	echo '<div class="dm-gst-card ' . ( $on ? 'is-on' : 'is-off' ) . '">';
	echo '<div class="dm-gst-card-main"><strong class="dm-gst-state">' . ( $on ? esc_html__( 'GST is ON', 'digimarket' ) : esc_html__( 'GST is OFF', 'digimarket' ) ) . '</strong>';
	echo '<span>' . ( $on ? esc_html( sprintf( /* translators: 1 gstin 2 rate */ __( 'Receipts show GST (GSTIN %1$s, %2$s%% included in prices).', 'digimarket' ), $gstin ? $gstin : '—', (float) dm_gst_opt( 'rate' ) ) ) : esc_html__( 'No GST is charged or shown. Receipts say “GST not applicable”.', 'digimarket' ) ) . '</span></div>';
	dm_admin_form_open( 'gst_toggle' );
	echo '<input type="hidden" name="to" value="' . ( $on ? '0' : '1' ) . '"><button type="submit" class="dm-gst-btn ' . ( $on ? 'is-off' : 'is-on' ) . '"' . ( ! $on && ! dm_valid_gstin( $gstin ) ? ' data-need-gstin="1"' : '' ) . '>' . ( $on ? esc_html__( 'Turn GST OFF', 'digimarket' ) : esc_html__( 'Turn GST ON', 'digimarket' ) ) . '</button></form>';
	echo '<div class="dm-gst-meter is-' . esc_attr( $level ) . '"><span>' . esc_html( sprintf( /* translators: 1 FY 2 amount 3 limit */ __( '%1$s sales: %2$s of %3$s GST limit', 'digimarket' ), dm_fy_label(), dm_inr( $sales ), dm_inr( $lim ) ) ) . '</span><i style="width:' . (int) $pct . '%"></i></div>';
	if ( 'ok' !== $level && ! $on ) {
		echo '<p class="dm-gst-warn">' . esc_html( 'over' === $level ? __( 'Your sales have crossed the GST limit — register for GST, add your GSTIN below and turn GST ON.', 'digimarket' ) : __( 'You are close to the GST limit. Start your GST registration now.', 'digimarket' ) ) . '</p>';
	}
	echo '</div>';
}

function dm_admin_gst() {
	$g = dm_gst();
	dm_admin_header( __( 'GST & tax', 'digimarket' ) );
	echo '<p class="description">' . esc_html__( 'Keep GST OFF until you register. When your GSTIN is ready, add it below and press “Turn GST ON” — receipts then become tax invoices automatically.', 'digimarket' ) . '</p>';
	dm_gst_switch_card();

	echo '<h2>' . esc_html__( 'GST settings', 'digimarket' ) . '</h2>';
	dm_admin_form_open( 'gst_save' );
	echo '<table class="form-table">';
	echo '<tr><th><label for="dmg_gstin">' . esc_html__( 'Store GSTIN', 'digimarket' ) . '</label></th><td><input class="regular-text" id="dmg_gstin" name="g[gstin]" maxlength="15" style="text-transform:uppercase" value="' . esc_attr( dm_store_gstin() ) . '" placeholder="21ABCDE1234F1Z5"><p class="description">' . esc_html__( '15 characters. Odisha GSTINs start with 21. Needed before GST can be turned on.', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dmg_rate">' . esc_html__( 'GST rate (%)', 'digimarket' ) . '</label></th><td><input class="small-text" type="number" step="0.01" min="0" max="28" id="dmg_rate" name="g[rate]" value="' . esc_attr( $g['rate'] ) . '"><p class="description">' . esc_html__( 'Prices you enter are treated as GST-inclusive. Most digital products and website services are 18%. Confirm your rate with your CA.', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dmg_cr">' . esc_html__( 'GST on commission (%)', 'digimarket' ) . '</label></th><td><input class="small-text" type="number" step="0.01" min="0" max="28" id="dmg_cr" name="g[comm_rate]" value="' . esc_attr( $g['comm_rate'] ) . '"><p class="description">' . esc_html__( 'While GST is ON, this GST is added to the platform commission and deducted from the seller’s share (e.g. ₹100 sale, 6% commission: ₹6 + ₹1.08 GST, seller gets ₹92.92). While GST is OFF, only the commission is deducted.', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dmg_state">' . esc_html__( 'Your state', 'digimarket' ) . '</label></th><td><input class="regular-text" id="dmg_state" name="g[state]" value="' . esc_attr( $g['state'] ) . '"><p class="description">' . esc_html__( 'Place of supply on receipts (tax shown as CGST + SGST).', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dmg_th">' . esc_html__( 'Registration limit (₹)', 'digimarket' ) . '</label></th><td><input class="regular-text" type="number" min="0" step="1000" id="dmg_th" name="g[threshold]" value="' . esc_attr( (int) $g['threshold'] ) . '"> <input class="small-text" type="number" min="10" max="99" name="g[warn_pct]" value="' . esc_attr( (int) $g['warn_pct'] ) . '">% ' . esc_html__( 'early warning', 'digimarket' ) . '<p class="description">' . esc_html__( 'Default ₹20,00,000 per financial year. You get a notification and email at the warning level and when the limit is crossed — for your store and for each seller.', 'digimarket' ) . '</p></td></tr>';
	echo '</table>';
	submit_button( __( 'Save GST settings', 'digimarket' ) );
	echo '</form>';

	if ( dm_single_seller_mode() ) {
		echo '<p class="description">' . esc_html__( 'Single seller mode: only your own store is tracked. Seller GST controls appear when marketplace sellers are enabled.', 'digimarket' ) . '</p></div>';
		return;
	}
	$sellers = get_users( array( 'meta_key' => 'dm_seller_status', 'meta_value' => array( 'active', 'pending', 'suspended' ), 'meta_compare' => 'IN', 'number' => 500 ) );
	$rows    = array();
	foreach ( $sellers as $u ) {
		$rows[] = array( $u, dm_fy_turnover( $u->ID ) );
	}
	usort( $rows, function ( $a, $b ) {
		return $b[1] <=> $a[1];
	} );
	echo '<h2>' . esc_html( sprintf( /* translators: %s FY */ __( 'Sellers — %s turnover', 'digimarket' ), dm_fy_label() ) ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Turn “GST required” ON for a seller who reaches the limit. They immediately see a pop-up asking for their GSTIN, and their products cannot be bought until a valid GSTIN is added.', 'digimarket' ) . '</p>';
	echo '<table class="widefat striped dm-gst-table"><thead><tr><th>' . esc_html__( 'Shop', 'digimarket' ) . '</th><th>' . esc_html__( 'Sales this FY', 'digimarket' ) . '</th><th>' . esc_html__( 'GSTIN', 'digimarket' ) . '</th><th>' . esc_html__( 'GST required', 'digimarket' ) . '</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="4">' . esc_html__( 'No sellers yet.', 'digimarket' ) . '</td></tr>';
	}
	foreach ( $rows as $r ) {
		list( $u, $amt ) = $r;
		$lvl  = dm_gst_level( $amt );
		$pct  = min( 100, round( $amt / max( 1, (float) $g['threshold'] ) * 100 ) );
		$gin  = dm_seller_gstin( $u->ID );
		$req  = dm_seller_gst_required( $u->ID );
		echo '<tr><td><a href="' . esc_url( admin_url( 'admin.php?page=dm-sellers&seller=' . $u->ID ) ) . '"><strong>' . esc_html( dm_shop_name( $u->ID ) ) . '</strong></a><br><small>' . esc_html( $u->user_email ) . '</small></td>';
		echo '<td><div class="dm-gst-meter is-' . esc_attr( $lvl ) . '"><span>' . esc_html( dm_inr( $amt ) ) . ' · ' . (int) $pct . '%</span><i style="width:' . (int) $pct . '%"></i></div></td>';
		echo '<td>' . ( $gin ? '<code>' . esc_html( $gin ) . '</code>' . ( dm_valid_gstin( $gin ) ? '' : ' <span style="color:#b32d2e">' . esc_html__( 'invalid', 'digimarket' ) . '</span>' ) : '<span class="description">' . esc_html__( 'Not provided', 'digimarket' ) . '</span>' ) . '</td><td>';
		dm_admin_form_open( 'gst_seller' );
		echo '<input type="hidden" name="uid" value="' . (int) $u->ID . '"><input type="hidden" name="to" value="' . ( $req ? '0' : '1' ) . '"><button class="dm-gst-mini ' . ( $req ? 'is-on' : 'is-off' ) . '" type="submit" aria-pressed="' . ( $req ? 'true' : 'false' ) . '">' . ( $req ? esc_html__( 'ON — click to turn off', 'digimarket' ) : esc_html__( 'OFF — click to require', 'digimarket' ) ) . '</button></form>';
		if ( $req && ! dm_valid_gstin( $gin ) ) {
			echo '<small style="color:#b32d2e">' . esc_html__( 'Sales paused until GSTIN is added', 'digimarket' ) . '</small>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}

add_action( 'dm_admin_do_gst_toggle', function ( $r ) {
	$g  = get_option( 'dm_gst', array() );
	$g  = is_array( $g ) ? $g : array();
	$to = ! empty( $r['to'] ) ? 1 : 0;
	if ( $to && ! dm_valid_gstin( dm_store_gstin() ) ) {
		dm_admin_back( '', __( 'Add a valid 15-character GSTIN in GST settings first, then turn GST on.', 'digimarket' ) );
	}
	$g['enabled'] = $to;
	update_option( 'dm_gst', $g );
	dm_audit( $to ? 'gst_on' : 'gst_off', 'settings', 0 );
	do_action( 'litespeed_purge_all' );
	dm_admin_back( $to ? __( 'GST is now ON. Receipts show GST.', 'digimarket' ) : __( 'GST is now OFF.', 'digimarket' ) );
} );

add_action( 'dm_admin_do_gst_save', function ( $r ) {
	$in = isset( $r['g'] ) ? (array) $r['g'] : array();
	$g  = dm_gst();
	$gi = strtoupper( preg_replace( '/\s+/', '', (string) ( $in['gstin'] ?? '' ) ) );
	if ( '' !== $gi && ! dm_valid_gstin( $gi ) ) {
		dm_admin_back( '', __( 'That GSTIN does not look right — it must be 15 characters like 21ABCDE1234F1Z5.', 'digimarket' ) );
	}
	$g['gstin']     = $gi;
	$g['rate']      = max( 0, min( 28, (float) ( $in['rate'] ?? 18 ) ) );
	$g['state']     = sanitize_text_field( $in['state'] ?? 'Odisha' );
	$g['threshold'] = max( 0, absint( $in['threshold'] ?? 2000000 ) );
	$g['warn_pct']  = max( 10, min( 99, absint( $in['warn_pct'] ?? 80 ) ) );
	$g['comm_rate'] = max( 0, min( 28, (float) ( $in['comm_rate'] ?? 18 ) ) );
	if ( '' === $gi ) {
		$g['enabled'] = 0;
	}
	update_option( 'dm_gst', $g );
	dm_admin_back( __( 'GST settings saved.', 'digimarket' ) );
} );

add_action( 'dm_admin_do_gst_seller', function ( $r ) {
	$uid = absint( $r['uid'] ?? 0 );
	if ( ! $uid || ! get_userdata( $uid ) ) {
		dm_admin_back( '', __( 'Seller not found.', 'digimarket' ) );
	}
	$to = ! empty( $r['to'] );
	update_user_meta( $uid, 'dm_gst_required', $to ? 1 : 0 );
	dm_audit( $to ? 'gst_required_on' : 'gst_required_off', 'seller', $uid );
	if ( $to ) {
		$msg = __( 'GST registration is now required for your shop. Add your GSTIN in Shop settings to keep selling.', 'digimarket' );
		dm_notify( $uid, $msg, dm_url( 'dashboard', 'settings' ) . '#gst' );
		$u = get_userdata( $uid );
		dm_mail(
			$u->user_email,
			__( 'Action needed: add your GST number', 'digimarket' ),
			'<p>' . esc_html( sprintf( /* translators: %s shop */ __( 'Hi %s,', 'digimarket' ), dm_shop_name( $uid ) ) ) . '</p><p>' . esc_html( sprintf( /* translators: %s limit */ __( 'Your sales on this site have reached the GST registration limit of %s per financial year. As per our Seller Agreement, you have to register for GST and add your GSTIN to keep selling.', 'digimarket' ), dm_inr( dm_gst_opt( 'threshold' ) ) ) ) . '</p><p>' . esc_html__( 'Until a valid GSTIN is added, your products are paused for new purchases. Existing buyers keep their downloads.', 'digimarket' ) . '</p>',
			dm_url( 'dashboard', 'settings' ) . '#gst',
			__( 'Add my GSTIN', 'digimarket' )
		);
	}
	do_action( 'litespeed_purge_all' );
	/* translators: %s shop */
	dm_admin_back( sprintf( $to ? __( 'GST is now required for %s.', 'digimarket' ) : __( 'GST requirement removed for %s.', 'digimarket' ), dm_shop_name( $uid ) ) );
} );

/* -------------------------------------------------------------------------
 * Seller side: GSTIN form, pop-up
 * ---------------------------------------------------------------------- */

/**
 * GSTIN fields (shop settings and onboarding).
 */
function dm_seller_gst_fields( $uid ) {
	$gin = dm_seller_gstin( $uid );
	echo '<label>' . ( dm_seller_gst_required( $uid ) ? esc_html__( 'GSTIN', 'digimarket' ) . ' *' : esc_html__( 'GSTIN (optional)', 'digimarket' ) ) . '<input type="text" name="gstin" maxlength="15" style="text-transform:uppercase" value="' . esc_attr( $gin ) . '" placeholder="21ABCDE1234F1Z5"' . ( dm_seller_gst_required( $uid ) ? ' required' : '' ) . ' pattern="[0-3][0-9][A-Za-z]{5}[0-9]{4}[A-Za-z][1-9A-Za-z][Zz][0-9A-Za-z]"><small class="dm-muted">' . esc_html( sprintf( /* translators: %s limit */ __( 'Required once your sales cross %s in a financial year. Leave blank if you are not registered.', 'digimarket' ), dm_inr( dm_gst_opt( 'threshold' ) ) ) ) . '</small></label>';
	echo '<label class="dm-check"><input type="checkbox" name="gst_show" value="1"' . checked( (bool) get_user_meta( $uid, 'dm_gst_show', true ), true, false ) . '> <span>' . esc_html__( 'Show my GSTIN and GST on my receipts', 'digimarket' ) . '</span></label>';
}

/**
 * Save GSTIN from a posted form. Returns an error message or ''.
 */
function dm_save_seller_gstin( $uid, $post ) {
	$gi = strtoupper( preg_replace( '/\s+/', '', (string) wp_unslash( $post['gstin'] ?? '' ) ) );
	if ( '' !== $gi && ! dm_valid_gstin( $gi ) ) {
		return __( 'That GSTIN does not look right — it must be 15 characters like 21ABCDE1234F1Z5.', 'digimarket' );
	}
	if ( '' === $gi && dm_seller_gst_required( $uid ) ) {
		return __( 'A GSTIN is required for your shop.', 'digimarket' );
	}
	update_user_meta( $uid, 'dm_gstin', $gi );
	update_user_meta( $uid, 'dm_gst_show', ! empty( $post['gst_show'] ) && $gi ? 1 : 0 );
	return '';
}

function dm_do_seller_gst() {
	dm_require_login();
	$uid = get_current_user_id();
	$err = dm_save_seller_gstin( $uid, $_POST ); // phpcs:ignore
	if ( $err ) {
		dm_flash( 'error', $err );
		dm_back();
	}
	if ( dm_seller_gst_required( $uid ) ) {
		dm_notify_admins( sprintf( /* translators: %s shop */ __( '%s added a GSTIN.', 'digimarket' ), dm_shop_name( $uid ) ), admin_url( 'admin.php?page=dm-gst' ) );
	}
	do_action( 'litespeed_purge_all' );
	dm_flash( 'success', __( 'GST details saved.', 'digimarket' ) );
	dm_back();
}

/**
 * Blocking pop-up in the seller dashboard when GST is required but missing.
 */
function dm_seller_gst_popup( $uid ) {
	if ( ! dm_seller_gst_blocked( $uid ) ) {
		return;
	}
	echo '<div class="dm-notice dm-notice-error">' . esc_html__( 'GST number required — your products are paused for new purchases until you add your GSTIN.', 'digimarket' ) . ' <a href="#" data-gst-open>' . esc_html__( 'Add GSTIN', 'digimarket' ) . '</a></div>';
	echo '<dialog class="dm-agree-modal dm-gst-modal" id="dm-gst-modal" data-autoopen aria-labelledby="dm-gst-title"><div class="dm-agree-head"><h2 id="dm-gst-title">' . esc_html__( 'You have to generate a GST number to sell products on this site', 'digimarket' ) . '</h2></div><div class="dm-agree-body">';
	echo '<p>' . esc_html( sprintf( /* translators: 1 limit 2 FY */ __( 'Your sales have reached the GST registration limit of %1$s for %2$s. As per the Seller Agreement, register for GST (gst.gov.in) and add your 15-character GSTIN below.', 'digimarket' ), dm_inr( dm_gst_opt( 'threshold' ) ), dm_fy_label() ) ) . '</p><p>' . esc_html__( 'Until then your products cannot be bought. Existing buyers keep their downloads, and your earlier payouts are not affected.', 'digimarket' ) . '</p>';
	echo '<form method="post" class="dm-form">';
	dm_nonce_field( 'seller_gst' );
	dm_seller_gst_fields( $uid );
	echo '<div class="dm-agree-foot" style="padding:12px 0 0;border:0"><button type="button" class="dm-btn dm-btn-outline" data-gst-close>' . esc_html__( 'Later', 'digimarket' ) . '</button><button class="dm-btn dm-btn-primary" type="submit">' . esc_html__( 'Save GSTIN', 'digimarket' ) . '</button></div></form></div></dialog>';
}
