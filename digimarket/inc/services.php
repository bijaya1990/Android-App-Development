<?php
/**
 * Services toolkit: portfolio, testimonials, enquiry form + leads,
 * verified client reviews via one-time links.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Portfolio + testimonials post types
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type(
		'dm_portfolio',
		array(
			'labels'       => array(
				'name'          => __( 'Portfolio', 'digimarket' ),
				'singular_name' => __( 'Portfolio item', 'digimarket' ),
				'add_new_item'  => __( 'Add portfolio item', 'digimarket' ),
				'edit_item'     => __( 'Edit portfolio item', 'digimarket' ),
				'all_items'     => __( 'Portfolio', 'digimarket' ),
			),
			'public'       => true,
			'has_archive'  => 'portfolio',
			'rewrite'      => array( 'slug' => 'portfolio', 'with_front' => false ),
			'show_in_menu' => 'dm-marketplace',
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
		)
	);
	register_post_type(
		'dm_testimonial',
		array(
			'labels'       => array(
				'name'          => __( 'Testimonials', 'digimarket' ),
				'singular_name' => __( 'Testimonial', 'digimarket' ),
				'add_new_item'  => __( 'Add testimonial', 'digimarket' ),
				'edit_item'     => __( 'Edit testimonial', 'digimarket' ),
				'all_items'     => __( 'Testimonials', 'digimarket' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'dm-marketplace',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);
}, 6 );

function dm_service_options( $selected ) {
	$out = '<option value="0">' . esc_html__( '— Any / none —', 'digimarket' ) . '</option>';
	foreach ( get_posts( array( 'post_type' => 'dm_product', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => 200, 'meta_key' => '_dm_service_mode', 'meta_value' => 1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		$out .= '<option value="' . (int) $p->ID . '"' . selected( (int) $selected, $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
	}
	return $out;
}

add_action( 'add_meta_boxes_dm_portfolio', function () {
	add_meta_box( 'dm_portfolio_data', __( 'Project details', 'digimarket' ), function ( $post ) {
		wp_nonce_field( 'dm_pf_save', 'dm_pf_nonce' );
		$g   = function ( $k ) use ( $post ) {
			return get_post_meta( $post->ID, '_dm_pf_' . $k, true );
		};
		$mob = (int) $g( 'mobile' );
		echo '<table class="form-table">';
		echo '<tr><th>' . esc_html__( 'Live demo URL', 'digimarket' ) . '</th><td><input type="url" class="large-text" name="pf[url]" value="' . esc_attr( $g( 'url' ) ) . '" placeholder="https://"></td></tr>';
		echo '<tr><th>' . esc_html__( 'Kind', 'digimarket' ) . '</th><td><select name="pf[kind]"><option value="demo"' . selected( 'client' !== $g( 'kind' ), true, false ) . '>' . esc_html__( 'Sample design', 'digimarket' ) . '</option><option value="client"' . selected( $g( 'kind' ), 'client', false ) . '>' . esc_html__( 'Real client project', 'digimarket' ) . '</option></select><p class="description">' . esc_html__( 'Choose “Real client project” only for websites you actually delivered to a client. Everything else is labelled “Sample”.', 'digimarket' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'Client type', 'digimarket' ) . '</th><td><input type="text" class="regular-text" name="pf[client]" value="' . esc_attr( $g( 'client' ) ) . '" placeholder="' . esc_attr__( 'School, Puja committee, Café…', 'digimarket' ) . '"></td></tr>';
		echo '<tr><th>' . esc_html__( 'Related service', 'digimarket' ) . '</th><td><select name="pf[service]">' . dm_service_options( $g( 'service' ) ) . '</select><p class="description">' . esc_html__( 'The item appears on that service’s page.', 'digimarket' ) . '</p></td></tr>'; // phpcs:ignore
		echo '<tr><th>' . esc_html__( 'Mobile screenshot', 'digimarket' ) . '</th><td><input type="number" min="0" id="pf_mobile" name="pf[mobile]" value="' . esc_attr( $mob ) . '" style="width:90px"> <button type="button" class="button dm-media-pick" data-target="pf_mobile">' . esc_html__( 'Choose image', 'digimarket' ) . '</button> <span class="dm-media-preview" id="pf_mobile_prev">' . ( $mob ? wp_get_attachment_image( $mob, array( 60, 100 ) ) : '' ) . '</span><p class="description">' . esc_html__( 'Desktop screenshot = Featured image.', 'digimarket' ) . '</p></td></tr>';
		foreach ( array( 'problem' => __( 'Who it is for', 'digimarket' ), 'built' => __( 'What the website includes', 'digimarket' ), 'result' => __( 'How it helps', 'digimarket' ) ) as $k => $l ) {
			echo '<tr><th>' . esc_html( $l ) . '</th><td><textarea class="large-text" rows="3" name="pf[' . esc_attr( $k ) . ']">' . esc_textarea( $g( $k ) ) . '</textarea></td></tr>';
		}
		echo '</table>';
	}, 'dm_portfolio', 'normal', 'high' );
} );

add_action( 'save_post_dm_portfolio', function ( $pid ) {
	if ( ! isset( $_POST['dm_pf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_pf_nonce'] ) ), 'dm_pf_save' ) || ! current_user_can( 'edit_post', $pid ) ) {
		return;
	}
	$d = wp_unslash( (array) ( $_POST['pf'] ?? array() ) ); // phpcs:ignore
	update_post_meta( $pid, '_dm_pf_url', esc_url_raw( $d['url'] ?? '' ) );
	update_post_meta( $pid, '_dm_pf_kind', 'demo' === ( $d['kind'] ?? '' ) ? 'demo' : 'client' );
	update_post_meta( $pid, '_dm_pf_client', sanitize_text_field( $d['client'] ?? '' ) );
	update_post_meta( $pid, '_dm_pf_service', absint( $d['service'] ?? 0 ) );
	update_post_meta( $pid, '_dm_pf_mobile', absint( $d['mobile'] ?? 0 ) );
	foreach ( array( 'problem', 'built', 'result' ) as $k ) {
		update_post_meta( $pid, '_dm_pf_' . $k, sanitize_textarea_field( $d[ $k ] ?? '' ) );
	}
} );

add_action( 'add_meta_boxes_dm_testimonial', function () {
	add_meta_box( 'dm_t_data', __( 'Client details', 'digimarket' ), function ( $post ) {
		wp_nonce_field( 'dm_t_save', 'dm_t_nonce' );
		$g = function ( $k ) use ( $post ) {
			return get_post_meta( $post->ID, '_dm_t_' . $k, true );
		};
		echo '<p class="description">' . esc_html__( 'Title = client name. Content = their words. Featured image = photo or logo. Only add real clients.', 'digimarket' ) . '</p><table class="form-table">';
		echo '<tr><th>' . esc_html__( 'Organisation', 'digimarket' ) . '</th><td><input type="text" class="regular-text" name="t[org]" value="' . esc_attr( $g( 'org' ) ) . '"></td></tr>';
		echo '<tr><th>' . esc_html__( 'City', 'digimarket' ) . '</th><td><input type="text" class="regular-text" name="t[city]" value="' . esc_attr( $g( 'city' ) ) . '"></td></tr>';
		echo '<tr><th>' . esc_html__( 'Rating', 'digimarket' ) . '</th><td><select name="t[rating]">';
		for ( $i = 5; $i >= 1; $i-- ) {
			echo '<option value="' . (int) $i . '"' . selected( (int) ( $g( 'rating' ) ? $g( 'rating' ) : 5 ), $i, false ) . '>' . esc_html( str_repeat( '★', $i ) ) . '</option>';
		}
		echo '</select></td></tr><tr><th>' . esc_html__( 'Service', 'digimarket' ) . '</th><td><select name="t[service]">' . dm_service_options( $g( 'service' ) ) . '</select></td></tr></table>'; // phpcs:ignore
	}, 'dm_testimonial', 'normal', 'high' );
} );
add_action( 'save_post_dm_testimonial', function ( $pid ) {
	if ( ! isset( $_POST['dm_t_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_t_nonce'] ) ), 'dm_t_save' ) || ! current_user_can( 'edit_post', $pid ) ) {
		return;
	}
	$d = wp_unslash( (array) ( $_POST['t'] ?? array() ) ); // phpcs:ignore
	update_post_meta( $pid, '_dm_t_org', sanitize_text_field( $d['org'] ?? '' ) );
	update_post_meta( $pid, '_dm_t_city', sanitize_text_field( $d['city'] ?? '' ) );
	update_post_meta( $pid, '_dm_t_rating', max( 1, min( 5, absint( $d['rating'] ?? 5 ) ) ) );
	update_post_meta( $pid, '_dm_t_service', absint( $d['service'] ?? 0 ) );
} );

function dm_get_portfolio( $service_id = 0, $limit = 6 ) {
	$args = array(
		'post_type'      => 'dm_portfolio',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'no_found_rows'  => true,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	);
	if ( $service_id ) {
		$args['meta_query'] = array( array( 'key' => '_dm_pf_service', 'value' => (int) $service_id ) );
	}
	return get_posts( $args );
}

function dm_get_testimonials( $service_id = 0, $limit = 6 ) {
	$args = array(
		'post_type'      => 'dm_testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'no_found_rows'  => true,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	);
	$all  = get_posts( $args );
	if ( ! $service_id ) {
		return $all;
	}
	$mine = array_filter(
		$all,
		function ( $t ) use ( $service_id ) {
			$s = (int) get_post_meta( $t->ID, '_dm_t_service', true );
			return ! $s || $s === (int) $service_id;
		}
	);
	return array_values( $mine );
}

function dm_portfolio_card( $p ) {
	$url  = get_post_meta( $p->ID, '_dm_pf_url', true );
	$kind = get_post_meta( $p->ID, '_dm_pf_kind', true );
	$cli  = get_post_meta( $p->ID, '_dm_pf_client', true );
	echo '<article class="dm-pf-card"><a class="dm-pf-media" href="' . esc_url( get_permalink( $p ) ) . '">';
	echo has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'dm-card', array( 'loading' => 'lazy' ) ) : '<span class="dm-thumb-placeholder"><span>' . esc_html( mb_substr( $p->post_title, 0, 1 ) ) . '</span></span>';
	$real = 'client' === $kind;
	echo '<span class="dm-pf-kind' . ( $real ? '' : ' is-demo' ) . '">' . ( $real ? esc_html__( 'Client project', 'digimarket' ) : esc_html__( 'Sample', 'digimarket' ) ) . '</span></a>';
	echo '<div class="dm-pf-body"><h3><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $p->post_title ) . '</a></h3>';
	if ( $cli ) {
		echo '<p class="dm-muted">' . esc_html( $cli ) . '</p>';
	}
	if ( $url ) {
		echo '<a class="dm-pf-live" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . ( 'client' === $kind ? esc_html__( 'View live site', 'digimarket' ) : esc_html__( 'View sample', 'digimarket' ) ) . ' ' . dm_icon( 'external', 14 ) . '</a>'; // phpcs:ignore
	}
	echo '</div></article>';
}

function dm_testimonial_card( $t ) {
	$rating = (int) get_post_meta( $t->ID, '_dm_t_rating', true );
	$org    = get_post_meta( $t->ID, '_dm_t_org', true );
	$city   = get_post_meta( $t->ID, '_dm_t_city', true );
	echo '<figure class="dm-testi"><div class="dm-testi-stars" aria-label="' . esc_attr( sprintf( /* translators: %d */ __( '%d out of 5 stars', 'digimarket' ), $rating ? $rating : 5 ) ) . '">' . esc_html( str_repeat( '★', $rating ? $rating : 5 ) ) . '</div>';
	echo '<blockquote>' . wp_kses_post( wpautop( $t->post_content ) ) . '</blockquote><figcaption>';
	echo has_post_thumbnail( $t ) ? get_the_post_thumbnail( $t, array( 48, 48 ), array( 'class' => 'dm-testi-img', 'loading' => 'lazy' ) ) : '<span class="dm-testi-img dm-avatar-letter">' . esc_html( mb_substr( $t->post_title, 0, 1 ) ) . '</span>';
	echo '<span><strong>' . esc_html( $t->post_title ) . '</strong><small>' . esc_html( implode( ' · ', array_filter( array( $org, $city ) ) ) ) . '</small></span></figcaption></figure>';
}

/* -------------------------------------------------------------------------
 * Enquiry form → leads
 * ---------------------------------------------------------------------- */

function dm_site_types() {
	return array(
		__( 'School / College website', 'digimarket' ),
		__( 'Puja / Committee website', 'digimarket' ),
		__( 'Shop / Café / Restaurant website', 'digimarket' ),
		__( 'Business / Portfolio website', 'digimarket' ),
		__( 'Website maintenance', 'digimarket' ),
		__( 'Something else', 'digimarket' ),
	);
}

function dm_budgets() {
	return array( __( 'Under ₹3,000', 'digimarket' ), __( '₹3,000 – ₹5,000', 'digimarket' ), __( '₹5,000 – ₹10,000', 'digimarket' ), __( '₹10,000+', 'digimarket' ), __( 'Not sure yet', 'digimarket' ) );
}

function dm_render_enquiry_form( $pid = 0 ) {
	$pre = $pid ? get_the_title( $pid ) : '';
	echo '<form method="post" class="dm-form dm-enquiry" id="enquiry">';
	dm_nonce_field( 'enquiry' );
	echo '<input type="hidden" name="product_id" value="' . (int) $pid . '"><input type="text" name="website" class="dm-hp" tabindex="-1" autocomplete="off" aria-hidden="true">';
	echo '<div class="dm-form-grid"><label>' . esc_html__( 'Your name', 'digimarket' ) . ' *<input type="text" name="name" required maxlength="100" autocomplete="name"></label>';
	echo '<label>' . esc_html__( 'Phone / WhatsApp', 'digimarket' ) . ' *<input type="tel" name="phone" required maxlength="20" pattern="[0-9+ \-]{8,20}" autocomplete="tel"></label>';
	echo '<label>' . esc_html__( 'Email', 'digimarket' ) . '<input type="email" name="email" maxlength="120" autocomplete="email"></label>';
	echo '<label>' . esc_html__( 'Website type', 'digimarket' ) . '<select name="site_type">';
	$pick = '';
	$map  = array( 'school' => 0, 'college' => 0, 'puja' => 1, 'committee' => 1, 'shop' => 2, 'café' => 2, 'cafe' => 2, 'restaurant' => 2, 'business' => 3, 'portfolio' => 3, 'maintenance' => 4, 'amc' => 4 );
	foreach ( $map as $needle => $idx ) {
		if ( '' === $pick && false !== stripos( $pre, $needle ) ) {
			$pick = dm_site_types()[ $idx ];
		}
	}
	foreach ( dm_site_types() as $t ) {
		echo '<option' . selected( $pick, $t, false ) . '>' . esc_html( $t ) . '</option>';
	}
	echo '</select></label><label class="dm-span-2">' . esc_html__( 'Budget', 'digimarket' ) . '<select name="budget">';
	foreach ( dm_budgets() as $b ) {
		echo '<option>' . esc_html( $b ) . '</option>';
	}
	echo '</select></label></div><label>' . esc_html__( 'What do you need?', 'digimarket' ) . '<textarea name="message" rows="3" maxlength="2000" placeholder="' . esc_attr__( 'Pages you want, deadline, any website you like…', 'digimarket' ) . '"></textarea></label>';
	echo '<button class="dm-btn dm-btn-primary dm-btn-lg">' . esc_html__( 'Send enquiry', 'digimarket' ) . '</button> <small class="dm-muted">' . esc_html__( 'We reply on WhatsApp or phone. No spam.', 'digimarket' ) . '</small></form>';
}

function dm_do_enquiry() {
	global $wpdb;
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	if ( ! empty( $_POST['website'] ) ) { // phpcs:ignore — honeypot.
		dm_redirect( $back );
	}
	$ip   = dm_client_ip();
	$key  = 'dm_enq_' . md5( $ip );
	$hits = (int) get_transient( $key );
	if ( $hits >= 5 ) {
		dm_flash( 'error', __( 'Too many enquiries from your connection. Please message us on WhatsApp instead.', 'digimarket' ) );
		dm_redirect( $back );
	}
	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore
	$phone = preg_replace( '/[^0-9+ \-]/', '', (string) wp_unslash( $_POST['phone'] ?? '' ) ); // phpcs:ignore
	if ( '' === $name || strlen( preg_replace( '/\D/', '', $phone ) ) < 8 ) {
		dm_flash( 'error', __( 'Please enter your name and a valid phone number.', 'digimarket' ) );
		dm_redirect( $back . '#enquiry' );
	}
	$pid  = absint( $_POST['product_id'] ?? 0 ); // phpcs:ignore
	$data = array(
		'name'       => $name,
		'phone'      => $phone,
		'email'      => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ), // phpcs:ignore
		'site_type'  => sanitize_text_field( wp_unslash( $_POST['site_type'] ?? '' ) ), // phpcs:ignore
		'budget'     => sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) ), // phpcs:ignore
		'message'    => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ), // phpcs:ignore
		'product_id' => $pid && 'dm_product' === get_post_type( $pid ) ? $pid : 0,
		'source'     => substr( esc_url_raw( $back ), 0, 255 ),
		'status'     => 'new',
		'ip'         => $ip,
		'created_at' => dm_now(),
	);
	$wpdb->insert( dm_table( 'leads' ), $data );
	set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
	dm_log_click( 'enquiry', $data['product_id'], $back );
	$to = dm_store_opt( 'business_email' ) ? dm_store_opt( 'business_email' ) : dm_opt( 'support_email' );
	if ( function_exists( 'dm_mail' ) && $to ) {
		$body = '<p><strong>' . esc_html( $name ) . '</strong> · ' . esc_html( $phone ) . ( $data['email'] ? ' · ' . esc_html( $data['email'] ) : '' ) . '</p><p>' . esc_html( $data['site_type'] . ' · ' . $data['budget'] ) . '</p><p>' . nl2br( esc_html( $data['message'] ) ) . '</p>';
		/* translators: %s name */
		dm_mail( $to, sprintf( __( 'New website enquiry from %s', 'digimarket' ), $name ), $body, admin_url( 'admin.php?page=dm-leads' ), __( 'Open leads', 'digimarket' ) );
	}
	$msg = sprintf(
		/* translators: 1 name 2 type 3 budget 4 message */
		__( "Hi, I'm %1\$s. I just sent an enquiry for: %2\$s (budget %3\$s). %4\$s", 'digimarket' ),
		$name,
		$data['site_type'],
		$data['budget'],
		$data['message']
	);
	$wa = dm_whatsapp_url( trim( $msg ), $pid ? get_post_meta( $pid, '_dm_service_whatsapp', true ) : '' );
	dm_flash( 'success', __( 'Thanks! Your enquiry is saved — we will contact you soon.', 'digimarket' ), $wa, __( 'Continue on WhatsApp now →', 'digimarket' ) );
	dm_redirect( $back );
}

add_action( 'admin_menu', function () {
	global $wpdb;
	$new = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . dm_table( 'leads' ) . " WHERE status = 'new'" ); // phpcs:ignore
	add_submenu_page( 'dm-marketplace', __( 'Leads', 'digimarket' ), __( 'Leads', 'digimarket' ) . ( $new ? ' <span class="awaiting-mod">' . (int) $new . '</span>' : '' ), 'dm_view_marketplace', 'dm-leads', 'dm_admin_leads' );
	add_submenu_page( 'dm-marketplace', __( 'Request a review', 'digimarket' ), __( 'Request review', 'digimarket' ), 'dm_manage_marketplace', 'dm-review-request', 'dm_admin_review_request' );
}, 21 );

function dm_lead_statuses() {
	return array(
		'new'       => __( 'New', 'digimarket' ),
		'contacted' => __( 'Contacted', 'digimarket' ),
		'quoted'    => __( 'Quoted', 'digimarket' ),
		'won'       => __( 'Won', 'digimarket' ),
		'lost'      => __( 'Lost', 'digimarket' ),
	);
}

function dm_admin_leads() {
	global $wpdb;
	$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore
	$where  = $status && isset( dm_lead_statuses()[ $status ] ) ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';
	$rows   = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'leads' ) . " $where ORDER BY id DESC LIMIT 300" ); // phpcs:ignore
	dm_admin_header( __( 'Leads', 'digimarket' ), ' <a class="page-title-action" href="' . esc_url( dm_admin_action_url( 'export_leads' ) ) . '">' . esc_html__( 'Export CSV', 'digimarket' ) . '</a>' );
	echo '<ul class="subsubsub"><li><a href="' . esc_url( dm_admin_url( 'dm-leads' ) ) . '"' . ( $status ? '' : ' class="current"' ) . '>' . esc_html__( 'All', 'digimarket' ) . '</a> | </li>';
	foreach ( dm_lead_statuses() as $k => $l ) {
		echo '<li><a href="' . esc_url( dm_admin_url( 'dm-leads', array( 'status' => $k ) ) ) . '"' . ( $status === $k ? ' class="current"' : '' ) . '>' . esc_html( $l ) . '</a> | </li>';
	}
	echo '</ul><table class="widefat striped" style="clear:both"><thead><tr><th>' . esc_html__( 'Date', 'digimarket' ) . '</th><th>' . esc_html__( 'Contact', 'digimarket' ) . '</th><th>' . esc_html__( 'Need', 'digimarket' ) . '</th><th>' . esc_html__( 'Status & notes', 'digimarket' ) . '</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="4">' . esc_html__( 'No enquiries yet. The enquiry form appears on every service page.', 'digimarket' ) . '</td></tr>';
	}
	foreach ( $rows as $r ) {
		$wa = dm_whatsapp_url( sprintf( /* translators: %s name */ __( 'Hi %s, thanks for your enquiry on PikaCart!', 'digimarket' ), $r->name ), $r->phone );
		echo '<tr><td>' . esc_html( mysql2date( 'd M Y, H:i', $r->created_at ) ) . '</td>';
		echo '<td><strong>' . esc_html( $r->name ) . '</strong><br>' . esc_html( $r->phone ) . ( $r->email ? '<br>' . esc_html( $r->email ) : '' ) . ( $wa ? '<br><a href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">WhatsApp ↗</a>' : '' ) . '</td>';
		echo '<td>' . esc_html( $r->site_type ) . '<br><em>' . esc_html( $r->budget ) . '</em>' . ( $r->product_id ? '<br><a href="' . esc_url( get_permalink( $r->product_id ) ) . '">' . esc_html( get_the_title( $r->product_id ) ) . '</a>' : '' ) . ( $r->message ? '<p>' . nl2br( esc_html( $r->message ) ) . '</p>' : '' ) . '</td><td>';
		dm_admin_form_open( 'lead_update' );
		echo '<input type="hidden" name="id" value="' . (int) $r->id . '"><select name="status">';
		foreach ( dm_lead_statuses() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $r->status, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select><br><textarea name="notes" rows="2" style="width:100%">' . esc_textarea( (string) $r->notes ) . '</textarea><button class="button button-small">' . esc_html__( 'Save', 'digimarket' ) . '</button></form></td></tr>';
	}
	echo '</tbody></table></div>';
}

add_action( 'dm_admin_do_lead_update', function ( $r ) {
	global $wpdb;
	$st = sanitize_key( $r['status'] ?? 'new' );
	$wpdb->update(
		dm_table( 'leads' ),
		array(
			'status'     => isset( dm_lead_statuses()[ $st ] ) ? $st : 'new',
			'notes'      => sanitize_textarea_field( $r['notes'] ?? '' ),
			'updated_at' => dm_now(),
		),
		array( 'id' => absint( $r['id'] ?? 0 ) )
	);
	dm_admin_back( __( 'Lead updated.', 'digimarket' ) );
} );

add_action( 'dm_admin_do_export_leads', function () {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'leads' ) . ' ORDER BY id DESC', ARRAY_A ); // phpcs:ignore
	$out  = array();
	foreach ( $rows as $r ) {
		$out[] = array( $r['created_at'], $r['name'], $r['phone'], $r['email'], $r['site_type'], $r['budget'], $r['message'], $r['product_id'] ? get_the_title( $r['product_id'] ) : '', $r['status'], $r['notes'] );
	}
	dm_output_csv( 'leads-' . gmdate( 'Y-m-d' ) . '.csv', array( 'Date', 'Name', 'Phone', 'Email', 'Type', 'Budget', 'Message', 'Service', 'Status', 'Notes' ), $out );
} );

/* -------------------------------------------------------------------------
 * Verified client reviews for services (one-time links)
 * ---------------------------------------------------------------------- */

function dm_admin_review_request() {
	global $wpdb;
	dm_admin_header( __( 'Request a review from a client', 'digimarket' ) );
	$new = isset( $_GET['token'] ) ? sanitize_key( $_GET['token'] ) : ''; // phpcs:ignore
	if ( $new ) {
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'review_requests' ) . ' WHERE token = %s', $new ) );
		if ( $row ) {
			$link = dm_url( 'review', $row->token );
			/* translators: 1 name 2 service 3 link */
			$msg = sprintf( __( "Hi %1\$s, thank you for choosing us for your %2\$s! Could you rate our work? It takes 30 seconds: %3\$s", 'digimarket' ), $row->client_name, get_the_title( $row->product_id ), $link );
			$wa  = dm_whatsapp_url( $msg, preg_match( '/^\+?[0-9 \-]{8,}$/', $row->contact ) ? $row->contact : '' );
			echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Review link ready (valid 30 days, single use):', 'digimarket' ) . '</strong><br><input type="text" readonly class="large-text" value="' . esc_attr( $link ) . '" onclick="this.select()"></p>';
			if ( $wa ) {
				echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $wa ) . '">' . esc_html__( 'Send on WhatsApp', 'digimarket' ) . '</a></p>';
			}
			echo '</div>';
		}
	}
	echo '<p>' . esc_html__( 'After you deliver a website, create a one-time review link and send it to the client. Their review shows under the service with a “Verified client” badge. You can reply and hide abusive text, but you cannot change their words or rating.', 'digimarket' ) . '</p>';
	dm_admin_form_open( 'review_request' );
	echo '<table class="form-table"><tr><th>' . esc_html__( 'Service', 'digimarket' ) . '</th><td><select name="product_id" required>' . dm_service_options( 0 ) . '</select></td></tr>'; // phpcs:ignore
	echo '<tr><th>' . esc_html__( 'Client name', 'digimarket' ) . '</th><td><input type="text" name="client_name" class="regular-text" required></td></tr>';
	echo '<tr><th>' . esc_html__( 'Client WhatsApp / email', 'digimarket' ) . '</th><td><input type="text" name="contact" class="regular-text" placeholder="919XXXXXXXXX"></td></tr></table>';
	submit_button( __( 'Create review link', 'digimarket' ) );
	echo '</form><h2>' . esc_html__( 'Recent requests', 'digimarket' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Client', 'digimarket' ) . '</th><th>' . esc_html__( 'Service', 'digimarket' ) . '</th><th>' . esc_html__( 'Created', 'digimarket' ) . '</th><th>' . esc_html__( 'Status', 'digimarket' ) . '</th></tr></thead><tbody>';
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . dm_table( 'review_requests' ) . ' ORDER BY id DESC LIMIT 50' ) as $q ) { // phpcs:ignore
		$state = $q->used_at ? __( 'Reviewed', 'digimarket' ) : ( strtotime( $q->expires_at ) < current_time( 'timestamp' ) ? __( 'Expired', 'digimarket' ) : __( 'Waiting', 'digimarket' ) ); // phpcs:ignore
		echo '<tr><td>' . esc_html( $q->client_name ) . '</td><td>' . esc_html( get_the_title( $q->product_id ) ) . '</td><td>' . esc_html( mysql2date( 'd M Y', $q->created_at ) ) . '</td><td>' . esc_html( $state ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

add_action( 'dm_admin_do_review_request', function ( $r ) {
	global $wpdb;
	$pid  = absint( $r['product_id'] ?? 0 );
	$name = sanitize_text_field( $r['client_name'] ?? '' );
	if ( ! $pid || '' === $name ) {
		dm_admin_back( '', __( 'Choose a service and enter the client name.', 'digimarket' ) );
	}
	$token = strtolower( wp_generate_password( 32, false, false ) );
	$wpdb->insert(
		dm_table( 'review_requests' ),
		array(
			'token'       => $token,
			'product_id'  => $pid,
			'client_name' => $name,
			'contact'     => sanitize_text_field( $r['contact'] ?? '' ),
			'created_at'  => dm_now(),
			'expires_at'  => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + 30 * DAY_IN_SECONDS ), // phpcs:ignore
		)
	);
	wp_safe_redirect( dm_admin_url( 'dm-review-request', array( 'token' => $token ) ) );
	exit;
} );

function dm_review_request_row( $token ) {
	global $wpdb;
	$token = sanitize_key( $token );
	if ( strlen( $token ) < 20 ) {
		return null;
	}
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . dm_table( 'review_requests' ) . ' WHERE token = %s', $token ) );
	if ( ! $row || $row->used_at || strtotime( $row->expires_at ) < current_time( 'timestamp' ) ) { // phpcs:ignore
		return null;
	}
	return $row;
}

function dm_do_client_review() {
	global $wpdb;
	$row = dm_review_request_row( wp_unslash( $_POST['token'] ?? '' ) ); // phpcs:ignore
	if ( ! $row ) {
		dm_flash( 'error', __( 'This review link has expired or was already used.', 'digimarket' ) );
		dm_redirect( home_url( '/' ) );
	}
	$rating = max( 1, min( 5, absint( $_POST['rating'] ?? 5 ) ) ); // phpcs:ignore
	$title  = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ); // phpcs:ignore
	$text   = sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ); // phpcs:ignore
	$name   = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? $row->client_name ) ); // phpcs:ignore
	$photo  = 0;
	if ( ! empty( $_FILES['photo']['name'] ) ) {
		$img = dm_upload_image( 'photo', 0, null, 0 );
		$photo = is_wp_error( $img ) ? 0 : (int) $img;
	}
	$wpdb->insert(
		dm_table( 'reviews' ),
		array(
			'product_id'    => $row->product_id,
			'seller_id'     => (int) get_post_field( 'post_author', $row->product_id ),
			'buyer_id'      => 0,
			'reviewer_name' => $name ? $name : $row->client_name,
			'reviewer_type' => 'client',
			'photo_id'      => $photo,
			'rating'        => $rating,
			'comment'       => trim( ( $title ? $title . "\n" : '' ) . $text ),
			'status'        => 'approved',
			'created_at'    => dm_now(),
		)
	);
	$wpdb->update( dm_table( 'review_requests' ), array( 'used_at' => dm_now(), 'review_id' => $wpdb->insert_id ), array( 'id' => $row->id ) );
	dm_refresh_product_rating( $row->product_id );
	/* translators: 1 rating 2 service */
	dm_notify_admins( sprintf( __( 'New %1$d★ client review on %2$s', 'digimarket' ), $rating, get_the_title( $row->product_id ) ), admin_url( 'admin.php?page=dm-reviews' ) );
	dm_flash( 'success', __( 'Thank you! Your review is live.', 'digimarket' ) );
	dm_redirect( get_permalink( $row->product_id ) . '#reviews' );
}

/**
 * Display name + badge for a review row (buyers and verified clients).
 */
function dm_review_author( $r ) {
	if ( ! empty( $r->reviewer_name ) ) {
		return $r->reviewer_name;
	}
	$u = $r->buyer_id ? get_userdata( $r->buyer_id ) : null;
	return $u ? $u->display_name : __( 'Buyer', 'digimarket' );
}

function dm_review_badge( $r ) {
	return ( isset( $r->reviewer_type ) && 'client' === $r->reviewer_type ) ? __( 'Verified client', 'digimarket' ) : __( 'Verified buyer', 'digimarket' );
}

/**
 * Star breakdown counts for a product: [5 => n, 4 => n, ...].
 */
function dm_rating_breakdown( $pid ) {
	global $wpdb;
	$out  = array_fill_keys( array( 5, 4, 3, 2, 1 ), 0 );
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT rating, COUNT(*) c FROM ' . dm_table( 'reviews' ) . " WHERE product_id = %d AND status = 'approved' GROUP BY rating", $pid ) );
	foreach ( $rows as $r ) {
		$out[ (int) $r->rating ] = (int) $r->c;
	}
	return $out;
}
