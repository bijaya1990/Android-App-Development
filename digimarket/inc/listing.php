<?php
/**
 * Listing types (digital / service / partner offer), extra product fields,
 * the admin product editor, affiliate /go/ links and click tracking.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Listing type helpers
 * ---------------------------------------------------------------------- */

function dm_is_affiliate( $pid ) {
	return (bool) get_post_meta( $pid, '_dm_affiliate', true );
}

function dm_listing_type( $pid ) {
	if ( dm_is_affiliate( $pid ) ) {
		return 'affiliate';
	}
	return dm_is_service( $pid ) ? 'service' : 'digital';
}

function dm_age_bands() {
	return array(
		''     => __( '— Not for kids —', 'digimarket' ),
		'2-4'  => __( '2–4 yrs', 'digimarket' ),
		'3-5'  => __( '3–5 yrs', 'digimarket' ),
		'4-6'  => __( '4–6 yrs', 'digimarket' ),
		'5-7'  => __( '5–7 yrs', 'digimarket' ),
		'6-8'  => __( '6–8 yrs', 'digimarket' ),
		'7-9'  => __( '7–9 yrs', 'digimarket' ),
		'8-10' => __( '8–10 yrs', 'digimarket' ),
		'9-12' => __( '9–12 yrs', 'digimarket' ),
	);
}

function dm_age_band_label( $pid ) {
	$b    = (string) get_post_meta( $pid, '_dm_age_band', true );
	$list = dm_age_bands();
	return ( $b && isset( $list[ $b ] ) ) ? $list[ $b ] : '';
}

/**
 * Extra listing fields. key => [label, type, group, help].
 * Stored as post meta "_dm_{key}".
 */
function dm_listing_fields() {
	return array(
		// Everything.
		'what_you_get'  => array( __( '“What you get” checklist', 'digimarket' ), 'lines', 'digital', __( 'One item per line, e.g. “PDF, 42 pages”, “Editable Word file”, “Lifetime updates”. Shown next to the price.', 'digimarket' ) ),
		'age_band'      => array( __( 'Age band (Kids)', 'digimarket' ), 'age', 'digital', __( 'Shown as a badge on Kids products.', 'digimarket' ) ),
		'badge'         => array( __( 'Custom badge', 'digimarket' ), 'text', 'all', __( 'Optional short badge on the card, e.g. “Bestseller”, “New”, “Editor’s pick”.', 'digimarket' ) ),
		'demo_url'      => array( __( 'Live demo URL', 'digimarket' ), 'url', 'digital', __( 'Optional. For themes/templates: a link to the live preview. Shows a “Live preview” button.', 'digimarket' ) ),
		'sale_from'     => array( __( 'Sale starts', 'digimarket' ), 'datetime', 'price', __( 'Optional. The discount price applies only between these times.', 'digimarket' ) ),
		'sale_to'       => array( __( 'Sale ends', 'digimarket' ), 'datetime', 'price', __( 'Optional. After this the regular price returns automatically, and the card shows a countdown.', 'digimarket' ) ),
		// Services.
		'turnaround'    => array( __( 'Turnaround time', 'digimarket' ), 'text', 'service', __( 'e.g. “5–7 working days after we confirm requirements”.', 'digimarket' ) ),
		'included'      => array( __( '“What’s included” checklist', 'digimarket' ), 'lines', 'service', __( 'One per line, e.g. “Up to 6 pages”, “Admission form”, “Mobile-friendly”, “3 months free support”.', 'digimarket' ) ),
		'area_served'   => array( __( 'Area served', 'digimarket' ), 'text', 'service', __( 'e.g. “Odisha · All India (remote)”.', 'digimarket' ) ),
		'packages'      => array( __( 'Packages', 'digimarket' ), 'packages', 'service', __( 'Up to 3 packages. Each gets its own WhatsApp button that names the package.', 'digimarket' ) ),
		'faq'           => array( __( 'FAQ', 'digimarket' ), 'pairs', 'service', __( 'One per line: Question | Answer. At least 3 recommended — shown as an accordion and in Google.', 'digimarket' ) ),
		'show_partners' => array( __( 'Recommend hosting', 'digimarket' ), 'checkbox', 'service', __( 'Show “Recommended hosting for this website” partner offers on this service.', 'digimarket' ) ),
		// Partner offers.
		'aff_url'       => array( __( 'Affiliate link', 'digimarket' ), 'url', 'affiliate', __( 'Your tracking link from the partner’s affiliate dashboard. Never shown in the page — visitors go through the short link below.', 'digimarket' ) ),
		'aff_slug'      => array( __( 'Short link', 'digimarket' ), 'slug', 'affiliate', __( 'Public link becomes /go/your-slug/ — change the partner later without touching any page.', 'digimarket' ) ),
		'aff_button'    => array( __( 'Button text', 'digimarket' ), 'text', 'affiliate', __( 'Default: “Get this deal”.', 'digimarket' ) ),
		'aff_suffix'    => array( __( 'Price suffix', 'digimarket' ), 'text', 'affiliate', __( 'e.g. “/month”. The price field is shown as “From ₹X/month”.', 'digimarket' ) ),
		'aff_coupon'    => array( __( 'Partner coupon code', 'digimarket' ), 'text', 'affiliate', __( 'Optional code visitors can copy.', 'digimarket' ) ),
		'aff_best_for'  => array( __( 'Best for', 'digimarket' ), 'text', 'affiliate', __( 'e.g. “School and small business websites”.', 'digimarket' ) ),
		'aff_pros'      => array( __( 'Pros', 'digimarket' ), 'lines', 'affiliate', __( 'One per line.', 'digimarket' ) ),
		'aff_cons'      => array( __( 'Cons', 'digimarket' ), 'lines', 'affiliate', __( 'One per line — honest reviews convert better.', 'digimarket' ) ),
		// SEO.
		'focus_kw'      => array( __( 'Focus keyword', 'digimarket' ), 'text', 'seo', __( 'The main phrase people search for, e.g. “class 10 physics notes pdf”.', 'digimarket' ) ),
		'related_posts' => array( __( 'Related articles', 'digimarket' ), 'text', 'seo', __( 'Optional article IDs (comma-separated) to link from this product.', 'digimarket' ) ),
	);
}

/**
 * Normalise the posted listing type into the legacy service/affiliate flags.
 */
function dm_normalise_listing_type( $data ) {
	if ( isset( $data['listing_type'] ) ) {
		$t                    = in_array( $data['listing_type'], array( 'digital', 'service', 'affiliate' ), true ) ? $data['listing_type'] : 'digital';
		$data['service_mode'] = 'service' === $t ? 1 : '';
		$data['affiliate']    = 'affiliate' === $t ? 1 : '';
	}
	return $data;
}

/**
 * Validate type-specific requirements. Returns list of error strings.
 */
function dm_validate_listing_fields( $data ) {
	$errors = array();
	if ( ! empty( $data['affiliate'] ) ) {
		$url = trim( (string) ( $data['aff_url'] ?? '' ) );
		if ( '' === $url || ! dm_valid_url( $url ) ) {
			$errors[] = __( 'Add a valid affiliate link for this partner offer.', 'digimarket' );
		}
	}
	return $errors;
}

/**
 * Save the extra listing fields from a posted array.
 */
function dm_save_listing_fields( $pid, $data ) {
	if ( array_key_exists( 'affiliate', $data ) || isset( $data['listing_type'] ) ) {
		update_post_meta( $pid, '_dm_affiliate', empty( $data['affiliate'] ) ? 0 : 1 );
	}
	foreach ( dm_listing_fields() as $key => $f ) {
		$type = $f[1];
		if ( 'packages' === $type ) {
			if ( isset( $data['pkg'] ) && is_array( $data['pkg'] ) ) {
				$pk      = array();
				$popular = absint( $data['pkg_popular'] ?? 0 );
				foreach ( array_slice( $data['pkg'], 0, 3 ) as $i => $p ) {
					$name = sanitize_text_field( $p['name'] ?? '' );
					if ( '' === $name ) {
						continue;
					}
					$pk[] = array(
						'name'     => $name,
						'price'    => round( (float) ( $p['price'] ?? 0 ), 2 ),
						'features' => dm_lines( sanitize_textarea_field( $p['features'] ?? '' ) ),
						'popular'  => ( (int) $i + 1 ) === $popular ? 1 : 0,
					);
				}
				update_post_meta( $pid, '_dm_packages', $pk );
			}
			continue;
		}
		if ( 'checkbox' === $type ) {
			if ( isset( $data[ $key . '__present' ] ) || isset( $data[ $key ] ) ) {
				update_post_meta( $pid, '_dm_' . $key, empty( $data[ $key ] ) ? 0 : 1 );
			}
			continue;
		}
		if ( ! array_key_exists( $key, $data ) ) {
			continue;
		}
		$v = $data[ $key ];
		switch ( $type ) {
			case 'lines':
			case 'pairs':
				$v = sanitize_textarea_field( $v );
				break;
			case 'url':
				$v = esc_url_raw( trim( (string) $v ) );
				break;
			case 'slug':
				$v = sanitize_title( $v );
				break;
			case 'age':
				$v = array_key_exists( (string) $v, dm_age_bands() ) ? (string) $v : '';
				break;
			case 'datetime':
				$v = sanitize_text_field( $v );
				$v = preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $v ) ? $v : '';
				break;
			default:
				$v = sanitize_text_field( $v );
		}
		update_post_meta( $pid, '_dm_' . $key, $v );
	}
	// Short link: default to the product slug, keep unique.
	if ( dm_is_affiliate( $pid ) ) {
		$slug = (string) get_post_meta( $pid, '_dm_aff_slug', true );
		if ( '' === $slug ) {
			$slug = get_post_field( 'post_name', $pid );
			$slug = $slug ? $slug : sanitize_title( get_the_title( $pid ) );
		}
		$base = $slug;
		$n    = 2;
		while ( dm_find_affiliate_by_slug( $slug, $pid ) ) {
			$slug = $base . '-' . $n++;
		}
		update_post_meta( $pid, '_dm_aff_slug', $slug );
	}
	dm_sync_effective_price( $pid );
}

/* -------------------------------------------------------------------------
 * Scheduled sale prices
 * ---------------------------------------------------------------------- */

/**
 * Whether the sale window (if any) is open right now.
 */
function dm_sale_window_open( $pid ) {
	$from = dm_local_ts( (string) get_post_meta( $pid, '_dm_sale_from', true ) );
	$to   = dm_local_ts( (string) get_post_meta( $pid, '_dm_sale_to', true ) );
	$now  = time();
	return ! ( ( $from && $from > $now ) || ( $to && $to < $now ) );
}

function dm_sale_ends_ts( $pid ) {
	if ( null === dm_product_sale_price( $pid ) ) {
		return 0;
	}
	return dm_local_ts( (string) get_post_meta( $pid, '_dm_sale_to', true ) );
}

/**
 * Keep _dm_effective_price (used for sorting/filters) in line with the live price.
 */
function dm_sync_effective_price( $pid ) {
	$live = dm_product_price( $pid );
	if ( (string) get_post_meta( $pid, '_dm_effective_price', true ) !== (string) $live ) {
		update_post_meta( $pid, '_dm_effective_price', $live );
	}
}

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'dm_hourly_maintenance' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'dm_hourly_maintenance' );
	}
} );
add_action( 'dm_hourly_maintenance', function () {
	$ids = get_posts(
		array(
			'post_type'   => 'dm_product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array(
				'relation' => 'OR',
				array( 'key' => '_dm_sale_from', 'value' => '', 'compare' => '!=' ),
				array( 'key' => '_dm_sale_to', 'value' => '', 'compare' => '!=' ),
			),
		)
	);
	foreach ( $ids as $id ) {
		dm_sync_effective_price( $id );
	}
} );

/* -------------------------------------------------------------------------
 * Admin product editor (replaces the basic meta box)
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes_dm_product', function () {
	remove_meta_box( 'dm_product_data', 'dm_product', 'normal' );
	add_meta_box( 'dm_product_data', __( 'Product details', 'digimarket' ), 'dm_product_editor_box', 'dm_product', 'normal', 'high' );
	add_meta_box( 'dm_product_seo', __( 'SEO — how this looks on Google', 'digimarket' ), 'dm_product_seo_box', 'dm_product', 'normal', 'high' );
}, 20 );

/**
 * Render one extra field. $prefix is "dm" in wp-admin, "" on the front-end editor.
 */
function dm_render_listing_field( $key, $pid, $prefix = 'dm', $front = false ) {
	$fields = dm_listing_fields();
	if ( ! isset( $fields[ $key ] ) ) {
		return;
	}
	list( $label, $type, $group, $help ) = $fields[ $key ];
	$name  = $prefix ? $prefix . '[' . $key . ']' : $key;
	$id    = 'dmf_' . $key;
	$value = $pid ? get_post_meta( $pid, '_dm_' . $key, true ) : '';
	$input = '';
	switch ( $type ) {
		case 'lines':
		case 'pairs':
			$input = '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . ( 'pairs' === $type ? 6 : 4 ) . '" class="large-text">' . esc_textarea( (string) $value ) . '</textarea>';
			break;
		case 'checkbox':
			$input = '<input type="hidden" name="' . esc_attr( $prefix ? $prefix . '[' . $key . '__present]' : $key . '__present' ) . '" value="1"><label class="dm-check"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( (int) $value, 1, false ) . '> ' . esc_html( $help ) . '</label>';
			$help  = '';
			break;
		case 'age':
			$input = '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( dm_age_bands() as $k => $l ) {
				$input .= '<option value="' . esc_attr( $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			$input .= '</select>';
			break;
		case 'datetime':
			$input = '<input type="datetime-local" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
			break;
		case 'url':
			$input = '<input type="url" class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" placeholder="https://">';
			break;
		case 'slug':
			$input = '<code>' . esc_html( home_url( '/go/' ) ) . '</code><input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" style="width:180px">/';
			break;
		case 'packages':
			$input = dm_packages_editor( $pid, $prefix );
			break;
		default:
			$input = '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
	}
	if ( $front ) {
		echo '<label class="dm-label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . $input . ( $help ? '<small class="dm-muted">' . esc_html( $help ) . '</small>' : '' ) . '</label>'; // phpcs:ignore
	} else {
		echo '<tr class="dm-f-' . esc_attr( $group ) . '"><th><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>' . $input . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore
	}
}

function dm_packages_editor( $pid, $prefix ) {
	$pk   = $pid ? (array) get_post_meta( $pid, '_dm_packages', true ) : array();
	$base = $prefix ? $prefix . '[pkg]' : 'pkg';
	$pop  = $prefix ? $prefix . '[pkg_popular]' : 'pkg_popular';
	$def  = array( __( 'Basic', 'digimarket' ), __( 'Standard', 'digimarket' ), __( 'Premium', 'digimarket' ) );
	$out  = '<div class="dm-pkg-editor">';
	$popi = 0;
	for ( $i = 0; $i < 3; $i++ ) {
		$p = isset( $pk[ $i ] ) && is_array( $pk[ $i ] ) ? $pk[ $i ] : array( 'name' => '', 'price' => '', 'features' => array(), 'popular' => 0 );
		if ( ! empty( $p['popular'] ) ) {
			$popi = $i + 1;
		}
		$out .= '<fieldset class="dm-pkg-col"><legend>' . esc_html( sprintf( /* translators: %d */ __( 'Package %d', 'digimarket' ), $i + 1 ) ) . '</legend>';
		$out .= '<label>' . esc_html__( 'Name', 'digimarket' ) . '<input type="text" name="' . esc_attr( $base . '[' . $i . '][name]' ) . '" value="' . esc_attr( $p['name'] ) . '" placeholder="' . esc_attr( $def[ $i ] ) . '"></label>';
		$out .= '<label>' . esc_html__( 'Starting price', 'digimarket' ) . '<input type="number" min="0" step="1" name="' . esc_attr( $base . '[' . $i . '][price]' ) . '" value="' . esc_attr( $p['price'] ) . '"></label>';
		$out .= '<label>' . esc_html__( 'Features (one per line)', 'digimarket' ) . '<textarea rows="5" name="' . esc_attr( $base . '[' . $i . '][features]' ) . '">' . esc_textarea( implode( "\n", (array) $p['features'] ) ) . '</textarea></label>';
		$out .= '</fieldset>';
	}
	$out .= '</div><label>' . esc_html__( 'Most popular', 'digimarket' ) . ' <select name="' . esc_attr( $pop ) . '"><option value="0">—</option>';
	for ( $i = 1; $i <= 3; $i++ ) {
		$out .= '<option value="' . $i . '"' . selected( $popi, $i, false ) . '>' . esc_html( sprintf( /* translators: %d */ __( 'Package %d', 'digimarket' ), $i ) ) . '</option>';
	}
	return $out . '</select></label>';
}

function dm_product_editor_box( $post ) {
	wp_nonce_field( 'dm_admin_product', 'dm_admin_product_nonce' );
	$pid      = $post->ID;
	$type     = dm_listing_type( $pid );
	$delivery = get_post_meta( $pid, '_dm_delivery', true );
	$delivery = $delivery ? $delivery : 'file';
	$val      = function ( $k ) use ( $pid ) {
		return get_post_meta( $pid, '_dm_' . $k, true );
	};
	echo '<div class="dm-editor" data-listing-editor>';
	echo '<div class="dm-type-switch" role="radiogroup" aria-label="' . esc_attr__( 'Listing type', 'digimarket' ) . '">';
	foreach (
		array(
			'digital'   => array( __( 'Digital product', 'digimarket' ), __( 'Paid online, instant download', 'digimarket' ) ),
			'service'   => array( __( 'Service', 'digimarket' ), __( 'WhatsApp enquiry, no online payment', 'digimarket' ) ),
			'affiliate' => array( __( 'Partner offer', 'digimarket' ), __( 'Affiliate link to another site', 'digimarket' ) ),
		) as $k => $l
	) {
		echo '<label class="dm-type-opt"><input type="radio" name="dm[listing_type]" value="' . esc_attr( $k ) . '"' . checked( $type, $k, false ) . '><span><strong>' . esc_html( $l[0] ) . '</strong><small>' . esc_html( $l[1] ) . '</small></span></label>';
	}
	echo '</div>';

	echo '<h3 class="dm-ed-h">' . esc_html__( 'Price', 'digimarket' ) . '</h3><table class="form-table"><tbody>';
	echo '<tr><th><label for="dm_price" data-label-digital="' . esc_attr__( 'Price (₹)', 'digimarket' ) . '" data-label-service="' . esc_attr__( 'Starting price (₹)', 'digimarket' ) . '" data-label-affiliate="' . esc_attr__( 'Price shown “From” (₹)', 'digimarket' ) . '">' . esc_html__( 'Price (₹)', 'digimarket' ) . '</label></th><td><input type="number" step="0.01" min="0" id="dm_price" name="dm[price]" value="' . esc_attr( $val( 'price' ) ) . '"></td></tr>';
	echo '<tr class="dm-f-digital"><th><label for="dm_sale_price">' . esc_html__( 'Discount price (₹)', 'digimarket' ) . '</label></th><td><input type="number" step="0.01" min="0" id="dm_sale_price" name="dm[sale_price]" value="' . esc_attr( $val( 'sale_price' ) ) . '"> <span class="description">' . esc_html__( 'Optional — shows the struck-through price and % off badge.', 'digimarket' ) . '</span></td></tr>';
	echo '</tbody></table><table class="form-table dm-f-digital"><tbody>';
	dm_render_listing_field( 'sale_from', $pid );
	dm_render_listing_field( 'sale_to', $pid );
	echo '</tbody></table>';

	echo '<div class="dm-f-digital"><h3 class="dm-ed-h">' . esc_html__( 'Delivery', 'digimarket' ) . '</h3><table class="form-table"><tbody>';
	echo '<tr><th>' . esc_html__( 'Delivery type', 'digimarket' ) . '</th><td><select name="dm[delivery]">';
	foreach ( array( 'file', 'license_key', 'external_link' ) as $d ) {
		echo '<option value="' . esc_attr( $d ) . '"' . selected( $delivery, $d, false ) . '>' . esc_html( dm_delivery_label( $d ) ) . '</option>';
	}
	echo '</select></td></tr>';
	$fname = get_post_meta( $pid, '_dm_file_name', true );
	echo '<tr><th>' . esc_html__( 'Digital file', 'digimarket' ) . '</th><td>' . ( $fname ? '<p><code>' . esc_html( $fname ) . '</code> ' . esc_html( size_format( (int) get_post_meta( $pid, '_dm_file_size', true ) ) ) . '</p>' : '' ) . '<input type="file" name="digital_file"><p class="description">' . esc_html__( 'Stored privately — buyers get expiring download links.', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dm_external_url">' . esc_html__( 'External access link', 'digimarket' ) . '</label></th><td><input class="large-text" type="url" id="dm_external_url" name="dm[external_url]" value="' . esc_attr( $val( 'external_url' ) ) . '"></td></tr>';
	echo '<tr><th>' . esc_html__( 'Add license keys (one per line)', 'digimarket' ) . '</th><td><textarea name="dm[license_keys]" rows="3" class="large-text"></textarea><p class="description">' . esc_html( sprintf( /* translators: %d */ __( '%d unused keys available.', 'digimarket' ), dm_license_keys_available( $pid ) ) ) . '</p></td></tr>';
	echo '<tr><th><label for="dm_download_limit">' . esc_html__( 'Download limit', 'digimarket' ) . '</label></th><td><input type="number" min="0" id="dm_download_limit" name="dm[download_limit]" value="' . esc_attr( $val( 'download_limit' ) ) . '" class="small-text"> <span class="description">' . esc_html__( '0 = unlimited', 'digimarket' ) . '</span></td></tr>';
	echo '<tr><th><label for="dm_access_days">' . esc_html__( 'Access days', 'digimarket' ) . '</label></th><td><input type="number" min="0" id="dm_access_days" name="dm[access_days]" value="' . esc_attr( $val( 'access_days' ) ) . '" class="small-text"> <span class="description">' . esc_html__( '0 = lifetime', 'digimarket' ) . '</span></td></tr>';
	dm_render_listing_field( 'what_you_get', $pid );
	dm_render_listing_field( 'demo_url', $pid );
	dm_render_listing_field( 'age_band', $pid );
	echo '</tbody></table></div>';

	echo '<div class="dm-f-service"><h3 class="dm-ed-h">' . esc_html__( 'Service details', 'digimarket' ) . '</h3><table class="form-table"><tbody>';
	echo '<tr><th><label for="dm_service_whatsapp">' . esc_html__( 'WhatsApp number', 'digimarket' ) . '</label></th><td><input class="regular-text" type="text" id="dm_service_whatsapp" name="dm[service_whatsapp]" value="' . esc_attr( $val( 'service_whatsapp' ) ) . '" placeholder="' . esc_attr( dm_opt( 'whatsapp_number', '91XXXXXXXXXX' ) ) . '"><p class="description">' . esc_html__( 'Leave blank to use the default number from Marketplace → Settings.', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dm_service_message">' . esc_html__( 'WhatsApp opening message', 'digimarket' ) . '</label></th><td><input class="large-text" type="text" id="dm_service_message" name="dm[service_message]" value="' . esc_attr( $val( 'service_message' ) ) . '" placeholder="' . esc_attr__( 'Hi, I\'m interested in your School Website package…', 'digimarket' ) . '"></td></tr>';
	foreach ( array( 'turnaround', 'included', 'area_served', 'packages', 'faq', 'show_partners' ) as $k ) {
		dm_render_listing_field( $k, $pid );
	}
	echo '</tbody></table></div>';

	echo '<div class="dm-f-affiliate"><h3 class="dm-ed-h">' . esc_html__( 'Partner offer', 'digimarket' ) . '</h3><table class="form-table"><tbody>';
	foreach ( array( 'aff_url', 'aff_slug', 'aff_button', 'aff_suffix', 'aff_coupon', 'aff_best_for', 'aff_pros', 'aff_cons' ) as $k ) {
		dm_render_listing_field( $k, $pid );
	}
	if ( $pid && dm_is_affiliate( $pid ) ) {
		echo '<tr><th>' . esc_html__( 'Clicks', 'digimarket' ) . '</th><td>' . esc_html( sprintf( /* translators: 1: 30 day clicks 2: total */ __( '%1$d in the last 30 days · %2$d total', 'digimarket' ), dm_click_count( 'affiliate', $pid, 30 ), dm_click_count( 'affiliate', $pid ) ) ) . '</td></tr>';
	}
	echo '</tbody></table></div>';

	echo '<h3 class="dm-ed-h">' . esc_html__( 'Display', 'digimarket' ) . '</h3><table class="form-table"><tbody>';
	dm_render_listing_field( 'badge', $pid );
	echo '<tr><th>' . esc_html__( 'Featured on homepage', 'digimarket' ) . '</th><td><label><input type="checkbox" name="dm[featured]" value="1"' . checked( $val( 'featured' ), 1, false ) . '> ' . esc_html__( 'Show on the homepage', 'digimarket' ) . '</label><p class="description">' . esc_html__( 'Services: shown in the big “Website services” band on the homepage (untick to remove). Other products: pinned first in “New arrivals”.', 'digimarket' ) . '</p></td></tr>';
	if ( ! dm_single_seller_mode() ) {
		echo '<tr><th>' . esc_html__( 'Moderation lock', 'digimarket' ) . '</th><td><label><input type="checkbox" name="dm[forced]" value="1"' . checked( $val( 'forced' ), 1, false ) . '> ' . esc_html__( 'Force-unpublished (seller cannot republish)', 'digimarket' ) . '</label></td></tr>';
	} else {
		echo '<input type="hidden" name="dm[forced]" value="' . esc_attr( $val( 'forced' ) ? 1 : 0 ) . '">';
	}
	echo '</tbody></table>';
	echo '<p class="description">' . esc_html__( 'Tip: the Excerpt box (short description) is used on cards and as the fallback Google description.', 'digimarket' ) . '</p>';
	echo '</div>';
}

function dm_product_seo_box( $post ) {
	$pid   = $post->ID;
	$title = get_post_meta( $pid, '_dm_meta_title', true );
	$desc  = get_post_meta( $pid, '_dm_meta_desc', true );
	echo '<div class="dm-seo" data-seo-box data-post="' . (int) $pid . '" data-type="dm_product">';
	echo '<div class="dm-serp" aria-live="polite"><div class="dm-serp-site">' . esc_html( get_bloginfo( 'name' ) ) . ' <span>' . esc_html( untrailingslashit( home_url() ) ) . ' › product</span></div><div class="dm-serp-title" data-serp-title></div><div class="dm-serp-desc" data-serp-desc></div></div>';
	echo '<table class="form-table"><tbody>';
	dm_render_listing_field( 'focus_kw', $pid );
	echo '<tr><th><label for="dm_meta_title">' . esc_html__( 'SEO title', 'digimarket' ) . '</label></th><td><input class="large-text" type="text" id="dm_meta_title" name="dm[meta_title]" value="' . esc_attr( $title ) . '" data-seo-title maxlength="80"><p class="description"><span data-count-for="dm_meta_title"></span> ' . esc_html__( 'Aim for 50–60 characters. Formula: Keyword – Benefit | PikaCart. Left blank, it is generated for you.', 'digimarket' ) . '</p></td></tr>';
	echo '<tr><th><label for="dm_meta_desc">' . esc_html__( 'Meta description', 'digimarket' ) . '</label></th><td><textarea class="large-text" rows="3" id="dm_meta_desc" name="dm[meta_desc]" data-seo-desc maxlength="300">' . esc_textarea( $desc ) . '</textarea><p class="description"><span data-count-for="dm_meta_desc"></span> ' . esc_html__( 'Aim for 120–160 characters: what it is + who it’s for + a call to action.', 'digimarket' ) . '</p></td></tr>';
	dm_render_listing_field( 'related_posts', $pid );
	echo '</tbody></table><ul class="dm-seo-checks" data-seo-checks></ul><div class="dm-seo-dupes" data-seo-dupes></div></div>';
}

/* Duplicate title/description/keyword check for the SEO box. */
add_action( 'wp_ajax_dm_seo_dupes', function () {
	check_ajax_referer( 'dm_admin_ajax', 'nonce' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}
	global $wpdb;
	$pid  = absint( $_POST['post'] ?? 0 );
	$out  = array();
	$keys = array(
		'_dm_meta_title' => __( 'SEO title', 'digimarket' ),
		'_dm_meta_desc'  => __( 'Meta description', 'digimarket' ),
		'_dm_focus_kw'   => __( 'Focus keyword', 'digimarket' ),
	);
	$in   = array(
		'_dm_meta_title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
		'_dm_meta_desc'  => sanitize_textarea_field( wp_unslash( $_POST['desc'] ?? '' ) ),
		'_dm_focus_kw'   => sanitize_text_field( wp_unslash( $_POST['kw'] ?? '' ) ),
	);
	foreach ( $keys as $k => $label ) {
		if ( '' === trim( $in[ $k ] ) ) {
			continue;
		}
		$other = $wpdb->get_var( $wpdb->prepare( "SELECT m.post_id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = %s AND LOWER(m.meta_value) = LOWER(%s) AND m.post_id <> %d AND p.post_status IN ('publish','draft','future') LIMIT 1", $k, $in[ $k ], $pid ) );
		if ( $other ) {
			/* translators: 1: field 2: title */
			$out[] = sprintf( __( '%1$s is already used by “%2$s” — make it unique so the two pages don’t compete.', 'digimarket' ), $label, get_the_title( $other ) );
		}
	}
	wp_send_json_success( $out );
} );

/* -------------------------------------------------------------------------
 * Affiliate: /go/{slug}/ redirects and click tracking
 * ---------------------------------------------------------------------- */

function dm_find_affiliate_by_slug( $slug, $exclude = 0 ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dm_aff_slug' AND meta_value = %s AND post_id <> %d LIMIT 1", $slug, $exclude ) );
}

function dm_affiliate_go_url( $pid ) {
	$slug = get_post_meta( $pid, '_dm_aff_slug', true );
	return $slug ? dm_pretty_url( 'go/' . $slug . '/' ) : '';
}

/**
 * Outbound partner button (rel=sponsored per Google's rules for paid links).
 */
function dm_affiliate_button( $pid, $class = 'dm-btn dm-btn-primary' ) {
	$url  = dm_affiliate_go_url( $pid );
	$text = get_post_meta( $pid, '_dm_aff_button', true );
	$text = $text ? $text : __( 'Get this deal', 'digimarket' );
	if ( ! $url ) {
		return '';
	}
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="sponsored nofollow noopener" data-track="affiliate" data-ref="' . (int) $pid . '">' . esc_html( $text ) . ' ' . dm_icon( 'external', 16 ) . '</a>';
}

add_action( 'init', function () {
	add_rewrite_rule( dm_rule_prefix() . 'go/([^/]+)/?$', 'index.php?dm_go=$matches[1]', 'top' );
}, 21 );
add_filter( 'query_vars', function ( $v ) {
	$v[] = 'dm_go';
	return $v;
} );
add_action( 'template_redirect', function () {
	$slug = get_query_var( 'dm_go' );
	if ( ! $slug ) {
		return;
	}
	$pid = dm_find_affiliate_by_slug( sanitize_title( $slug ) );
	$url = $pid && 'publish' === get_post_status( $pid ) ? get_post_meta( $pid, '_dm_aff_url', true ) : '';
	if ( ! $url ) {
		dm_redirect( home_url( '/' ) );
	}
	$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	dm_log_click( 'affiliate', $pid, $ref );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	wp_redirect( $url, 302, 'PikaCart' ); // phpcs:ignore WordPress.Security.SafeRedirect -- admin-entered partner URL.
	exit;
}, 1 );

function dm_log_click( $type, $ref_id, $page = '' ) {
	global $wpdb;
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( $ua && preg_match( '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp/', $ua ) ) {
		return;
	}
	$wpdb->insert(
		dm_table( 'clicks' ),
		array(
			'click_type' => substr( sanitize_key( $type ), 0, 20 ),
			'ref_id'     => absint( $ref_id ),
			'page'       => substr( esc_url_raw( $page ), 0, 255 ),
			'created_at' => dm_now(),
		)
	);
}

function dm_click_count( $type, $ref_id = 0, $days = 0 ) {
	global $wpdb;
	$sql  = 'SELECT COUNT(*) FROM ' . dm_table( 'clicks' ) . ' WHERE click_type = %s';
	$args = array( $type );
	if ( $ref_id ) {
		$sql   .= ' AND ref_id = %d';
		$args[] = $ref_id;
	}
	if ( $days ) {
		$sql   .= ' AND created_at >= %s';
		$args[] = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $days * DAY_IN_SECONDS ); // phpcs:ignore
	}
	return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore
}

/* WhatsApp / outbound click beacon from the front end. */
add_action( 'wp_ajax_dm_track', 'dm_ajax_track' );
add_action( 'wp_ajax_nopriv_dm_track', 'dm_ajax_track' );
function dm_ajax_track() {
	$type = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) ); // phpcs:ignore
	if ( in_array( $type, array( 'whatsapp', 'enquiry', 'share' ), true ) ) {
		dm_log_click( $type, absint( $_POST['ref'] ?? 0 ), esc_url_raw( wp_unslash( $_POST['page'] ?? '' ) ) ); // phpcs:ignore
	}
	wp_send_json_success();
}

/* -------------------------------------------------------------------------
 * Clicks report (WhatsApp enquiries + partner offers)
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'dm-marketplace', __( 'Clicks & enquiries', 'digimarket' ), __( 'Clicks report', 'digimarket' ), 'dm_view_marketplace', 'dm-clicks', 'dm_admin_clicks' );
}, 22 );

function dm_admin_clicks() {
	global $wpdb;
	$days = isset( $_GET['days'] ) ? max( 1, min( 365, absint( $_GET['days'] ) ) ) : 30; // phpcs:ignore
	$from = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $days * DAY_IN_SECONDS ); // phpcs:ignore
	dm_admin_header( __( 'Clicks report', 'digimarket' ) );
	echo '<p>';
	foreach ( array( 7, 30, 90, 365 ) as $d ) {
		echo '<a class="button' . ( $d === $days ? ' button-primary' : '' ) . '" href="' . esc_url( dm_admin_url( 'dm-clicks', array( 'days' => $d ) ) ) . '">' . esc_html( sprintf( /* translators: %d */ __( 'Last %d days', 'digimarket' ), $d ) ) . '</a> ';
	}
	echo '</p>';
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT click_type, ref_id, COUNT(*) c FROM ' . dm_table( 'clicks' ) . ' WHERE created_at >= %s GROUP BY click_type, ref_id ORDER BY c DESC LIMIT 200', $from ) ); // phpcs:ignore
	$views = array();
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Type', 'digimarket' ) . '</th><th>' . esc_html__( 'Listing', 'digimarket' ) . '</th><th>' . esc_html__( 'Clicks', 'digimarket' ) . '</th><th>' . esc_html__( 'Page views (all time)', 'digimarket' ) . '</th></tr></thead><tbody>';
	$labels = array(
		'whatsapp'  => __( 'WhatsApp enquiry', 'digimarket' ),
		'affiliate' => __( 'Partner offer', 'digimarket' ),
		'enquiry'   => __( 'Enquiry form', 'digimarket' ),
		'share'     => __( 'Share', 'digimarket' ),
	);
	if ( ! $rows ) {
		echo '<tr><td colspan="4">' . esc_html__( 'No clicks recorded yet.', 'digimarket' ) . '</td></tr>';
	}
	foreach ( $rows as $r ) {
		$title = $r->ref_id ? get_the_title( $r->ref_id ) : __( 'Site-wide button', 'digimarket' );
		echo '<tr><td>' . esc_html( $labels[ $r->click_type ] ?? $r->click_type ) . '</td><td>' . ( $r->ref_id ? '<a href="' . esc_url( get_edit_post_link( $r->ref_id ) ) . '">' . esc_html( $title ) . '</a>' : esc_html( $title ) ) . '</td><td><strong>' . (int) $r->c . '</strong></td><td>' . ( $r->ref_id ? (int) get_post_meta( $r->ref_id, '_dm_views', true ) : '—' ) . '</td></tr>';
	}
	echo '</tbody></table><p class="description">' . esc_html__( 'Compare partner-offer clicks with the conversions in each partner’s affiliate dashboard. Bots are not counted.', 'digimarket' ) . '</p></div>';
}

/* -------------------------------------------------------------------------
 * Cards/pages: badges
 * ---------------------------------------------------------------------- */

/**
 * Badges for a product card: [label, class].
 */
function dm_product_badges( $pid, $max = 2 ) {
	$out    = array();
	$custom = (string) get_post_meta( $pid, '_dm_badge', true );
	if ( '' !== $custom ) {
		$out[] = array( $custom, 'is-custom' );
	}
	$type = dm_listing_type( $pid );
	if ( 'service' === $type ) {
		$out[] = array( __( 'Service', 'digimarket' ), 'is-service' );
	} elseif ( 'affiliate' === $type ) {
		$out[] = array( __( 'Partner', 'digimarket' ), 'is-partner' );
	}
	$age = dm_age_band_label( $pid );
	if ( $age ) {
		$out[] = array( $age, 'is-age' );
	}
	if ( (int) get_post_meta( $pid, '_dm_sales', true ) >= 10 ) {
		$out[] = array( __( 'Bestseller', 'digimarket' ), 'is-best' );
	}
	if ( strtotime( get_post_field( 'post_date_gmt', $pid ) ) > time() - 14 * DAY_IN_SECONDS ) {
		$out[] = array( __( 'New', 'digimarket' ), 'is-new' );
	}
	return array_slice( $out, 0, $max );
}

/* Show validation messages from the product editor after the redirect. */
add_action( 'admin_notices', function () {
	$key = 'dm_admin_notice_' . get_current_user_id();
	$msg = get_transient( $key );
	if ( $msg ) {
		delete_transient( $key );
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
} );

/* Products use the classic editor so the product details and SEO boxes sit right under the description. */
add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return in_array( $type, array( 'dm_product', 'dm_portfolio' ), true ) ? false : $use;
}, 10, 2 );
add_filter( 'default_hidden_meta_boxes', function ( $hidden, $screen ) {
	if ( $screen && 'dm_product' === $screen->post_type ) {
		$hidden = array_diff( $hidden, array( 'postexcerpt' ) );
	}
	return $hidden;
}, 10, 2 );
add_filter( 'admin_post_thumbnail_html', function ( $html, $post_id ) {
	if ( 'dm_product' === get_post_type( $post_id ) ) {
		$html = '<p class="description">' . esc_html__( 'Square image, at least 1000×1000 px, works best on cards.', 'digimarket' ) . '</p>' . $html;
	}
	return $html;
}, 10, 2 );
