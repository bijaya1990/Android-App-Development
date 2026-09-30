<?php
/**
 * Storefront: store settings, homepage blocks, banner manager, category extras,
 * announcement bar and demo content import.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Store settings (separate option so payment settings stay untouched)
 * ---------------------------------------------------------------------- */

function dm_home_block_defs() {
	return array(
		'hero'       => __( 'Hero banner carousel', 'digimarket' ),
		'landing'    => __( 'Website services & WordPress themes cards', 'digimarket' ),
		'spotlight'  => __( 'Bestsellers + Shop by category + Limited time offer', 'digimarket' ),
		'tiles'      => __( 'Offer tiles', 'digimarket' ),
		'deals'      => __( 'Deals row (products on sale)', 'digimarket' ),
		'campaign'   => __( 'Campaign banner', 'digimarket' ),
		'services'   => __( 'Website services band (featured services only)', 'digimarket' ),
		'new'        => __( 'New arrivals', 'digimarket' ),
		'sections'   => __( 'Category collections', 'digimarket' ),
		'portfolio'  => __( 'Portfolio / our work', 'digimarket' ),
		'reviews'    => __( 'What our customers say (reviews)', 'digimarket' ),
		'trust'      => __( 'Trust strip', 'digimarket' ),
		'content'    => __( 'Homepage page content', 'digimarket' ),
		'categories' => __( 'Category icon row (top)', 'digimarket' ),
	);
}

function dm_store_defaults() {
	$blocks = array();
	$i      = 1;
	foreach ( array_keys( dm_home_block_defs() ) as $k ) {
		$blocks[ $k ] = array( 'on' => 'categories' === $k ? 0 : 1, 'order' => $i++ );
	}
	return array(
		'blocks'            => $blocks,
		'section_count'     => 4,
		'announce_on'       => 0,
		'announce_text'     => '',
		'announce_link'     => '',
		'announce_end'      => '',
		'announce_bg'       => '#1E1B4B',
		'bottom_nav'        => 1,
		'articles_show'     => 1,
		'articles_label'    => __( 'Articles', 'digimarket' ),
		'header_links'      => '',
		'trust_projects'    => '',
		'trust_reply'       => __( 'Usually replies within 1 hour', 'digimarket' ),
		'process_steps'     => __( "Chat on WhatsApp | Tell us what you need — we reply fast\nDesign approval | See the look before we build\nBuild & review | We build it, you request changes\nLaunch + support | Go live with free support", 'digimarket' ),
		'default_faq'       => __( "How long does it take? | Most websites are ready in 5–10 working days after we confirm your requirements.\nDo I need to buy hosting? | Yes, a website needs hosting and a domain. We recommend good options and can set everything up for you.\nCan I update the website myself? | Yes. We build on WordPress and show you how to edit text, images and notices.\nWhat if I don’t like the design? | You approve the design before we build, and revisions are included.\nWhat if you can’t deliver? | If we cannot deliver what was agreed, you get a full refund of any amount already paid.", 'digimarket' ),
		'refund_promise'    => __( 'If we cannot deliver what was agreed, you get a full refund of any amount already paid.', 'digimarket' ),
		'seo_home_title'    => '',
		'seo_home_desc'     => '',
		'seo_default_image' => 0,
		'gsc_verify'        => '',
		'bing_verify'       => '',
		'social_links'      => '',
		'area_served'       => 'India',
		'business_phone'    => '',
		'affiliate_note'    => __( 'Some links on this page are affiliate links — PikaCart may earn a commission at no extra cost to you.', 'digimarket' ),
		'owner_name'        => '',
		'owner_bio'         => '',
		'owner_photo'       => 0,
	) + dm_landing_defaults() + dm_legal_defaults();
}

function dm_store() {
	static $cache = null;
	if ( null === $cache || did_action( 'dm_store_saved' ) ) {
		$saved = get_option( 'dm_store', array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), dm_store_defaults() );
		$saved_blocks    = (array) $cache['blocks'];
		$cache['blocks'] = wp_parse_args( $saved_blocks, dm_store_defaults()['blocks'] );
		// Blocks added in an update slot in right after the hero on existing sites.
		if ( $saved_blocks && ! isset( $saved_blocks['landing'] ) && isset( $saved_blocks['hero'] ) ) {
			$cache['blocks']['landing']['order'] = (int) $saved_blocks['hero']['order'];
		}
	}
	return $cache;
}

function dm_store_opt( $key, $default = null ) {
	$s = dm_store();
	return array_key_exists( $key, $s ) ? $s[ $key ] : $default;
}

/**
 * Enabled homepage blocks in display order.
 */
function dm_home_blocks() {
	$blocks = dm_store_opt( 'blocks' );
	$on     = array();
	foreach ( dm_home_block_defs() as $k => $label ) {
		$b = isset( $blocks[ $k ] ) ? $blocks[ $k ] : array( 'on' => 1, 'order' => 99 );
		if ( ! empty( $b['on'] ) ) {
			$on[ $k ] = (int) $b['order'];
		}
	}
	asort( $on );
	return array_keys( $on );
}

/**
 * Parse "Question | Answer" lines into pairs.
 */
function dm_parse_pairs( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
	}
	return $out;
}

function dm_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}

/* -------------------------------------------------------------------------
 * Storefront admin page
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'dm-marketplace', __( 'Storefront', 'digimarket' ), __( 'Storefront & SEO', 'digimarket' ), 'dm_manage_marketplace', 'dm-storefront', 'dm_admin_storefront' );
}, 20 );

function dm_admin_storefront() {
	$s = dm_store();
	dm_admin_header( __( 'Storefront & SEO', 'digimarket' ) );
	echo '<p class="description">' . wp_kses_post( sprintf( /* translators: %s url */ __( 'Banners live in <a href="%s">Marketplace → Banners</a>. Category icons, colours, intro text and FAQ are set on each category.', 'digimarket' ), esc_url( admin_url( 'edit.php?post_type=dm_banner' ) ) ) ) . '</p>';
	dm_admin_form_open( 'save_store' );
	$f = function ( $key, $label, $type = 'text', $help = '', $attrs = '' ) use ( $s ) {
		echo '<tr><th><label for="dms_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="dms_' . esc_attr( $key ) . '" name="st[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $s[ $key ] ), true, false ) . '> ' . esc_html( $help ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="5" id="dms_' . esc_attr( $key ) . '" name="st[' . esc_attr( $key ) . ']">' . esc_textarea( $s[ $key ] ) . '</textarea>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' );
		} elseif ( 'media' === $type ) {
			$id = (int) $s[ $key ];
			echo '<input type="number" min="0" class="small-text" id="dms_' . esc_attr( $key ) . '" name="st[' . esc_attr( $key ) . ']" value="' . esc_attr( $id ) . '"> ';
			echo '<button type="button" class="button dm-media-pick" data-target="dms_' . esc_attr( $key ) . '">' . esc_html__( 'Choose image', 'digimarket' ) . '</button> ';
			echo '<span class="dm-media-preview" id="dms_' . esc_attr( $key ) . '_prev">' . ( $id ? wp_get_attachment_image( $id, array( 80, 80 ) ) : '' ) . '</span>';
			echo $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '';
		} else {
			echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="dms_' . esc_attr( $key ) . '" name="st[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[ $key ] ) . '" ' . $attrs . '>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore
		}
		echo '</td></tr>';
	};

	echo '<h2>' . esc_html__( 'Homepage blocks', 'digimarket' ) . '</h2><table class="form-table"><tr><th>' . esc_html__( 'Show & order', 'digimarket' ) . '</th><td><table class="dm-block-table">';
	$blocks = $s['blocks'];
	$defs   = dm_home_block_defs();
	uksort(
		$defs,
		function ( $a, $b ) use ( $blocks ) {
			return (int) ( $blocks[ $a ]['order'] ?? 99 ) <=> (int) ( $blocks[ $b ]['order'] ?? 99 );
		}
	);
	foreach ( $defs as $k => $label ) {
		$b = $blocks[ $k ] ?? array( 'on' => 1, 'order' => 99 );
		echo '<tr><td><input type="number" class="small-text" min="1" max="99" name="blocks[' . esc_attr( $k ) . '][order]" value="' . (int) $b['order'] . '"></td><td><label><input type="checkbox" name="blocks[' . esc_attr( $k ) . '][on]" value="1"' . checked( ! empty( $b['on'] ), true, false ) . '> ' . esc_html( $label ) . '</label></td></tr>';
	}
	echo '</table><p class="description">' . esc_html__( 'Lower numbers show first. Blocks with nothing to show (e.g. no banners yet) hide themselves.', 'digimarket' ) . '</p></td></tr>';
	$f( 'section_count', __( 'Category boxes on homepage', 'digimarket' ), 'number', __( 'How many category section boxes to show (each shows 4 best-selling items).', 'digimarket' ), 'min="0" max="12"' );
	$f( 'bottom_nav', __( 'Mobile bottom bar', 'digimarket' ), 'checkbox', __( 'Show the app-style bottom navigation on phones.', 'digimarket' ) );
	$f( 'header_links', __( 'Extra header links', 'digimarket' ), 'textarea', __( 'One per line: Label | URL. Shown in the header bar next to Articles.', 'digimarket' ) );
	echo '</table>';

	echo '<h2>' . esc_html__( 'Announcement bar', 'digimarket' ) . '</h2><table class="form-table">';
	$f( 'announce_on', __( 'Show bar', 'digimarket' ), 'checkbox', __( 'Thin offer bar above the header on every page.', 'digimarket' ) );
	$f( 'announce_text', __( 'Text', 'digimarket' ), 'text', __( 'e.g. Dussehra Sale: 30% off with DUSSEHRA30', 'digimarket' ) );
	$f( 'announce_link', __( 'Link', 'digimarket' ), 'url' );
	$f( 'announce_end', __( 'Countdown ends', 'digimarket' ), 'datetime-local', __( 'Optional. The bar shows a live timer and hides itself after this time.', 'digimarket' ) );
	$f( 'announce_bg', __( 'Background colour', 'digimarket' ), 'color' );
	echo '</table>';

	echo '<h2>' . esc_html__( 'Services & trust', 'digimarket' ) . '</h2><table class="form-table">';
	$f( 'trust_projects', __( 'Projects delivered', 'digimarket' ), 'text', __( 'Shown in the service trust bar, e.g. 25+. Leave blank to hide.', 'digimarket' ) );
	$f( 'trust_reply', __( 'Reply time text', 'digimarket' ) );
	$f( 'refund_promise', __( 'Service refund promise', 'digimarket' ), 'text', __( 'Shown on every service page.', 'digimarket' ) );
	$f( 'process_steps', __( 'How it works (services)', 'digimarket' ), 'textarea', __( 'One step per line: Title | short text.', 'digimarket' ) );
	$f( 'default_faq', __( 'Default service FAQ', 'digimarket' ), 'textarea', __( 'Used when a service has no FAQ of its own. One per line: Question | Answer.', 'digimarket' ) );
	$f( 'owner_name', __( 'Your name (author box)', 'digimarket' ) );
	$f( 'owner_bio', __( 'Short bio', 'digimarket' ), 'textarea' );
	$f( 'owner_photo', __( 'Your photo', 'digimarket' ), 'media' );
	echo '</table>';

	echo '<h2 id="landing">' . esc_html__( 'Landing pages', 'digimarket' ) . '</h2><p class="description">' . wp_kses_post( sprintf( /* translators: 1 url 2 url */ __( 'Two homepage cards open <a href="%1$s" target="_blank">Website services</a> (every Service listing, ordered on WhatsApp) and <a href="%2$s" target="_blank">WordPress themes</a> (digital products in the themes category, paid via Razorpay). Rotating images come from each listing’s featured image + gallery.', 'digimarket' ), esc_url( dm_url( 'website-services' ) ), esc_url( dm_url( 'wordpress-themes' ) ) ) ) . '</p><table class="form-table">';
	$f( 'lp_svc_card_title', __( 'Services card headline', 'digimarket' ) );
	$f( 'lp_svc_card_btn', __( 'Services card button', 'digimarket' ) );
	$f( 'lp_svc_wall', __( 'Services wallpaper', 'digimarket' ), 'media', __( 'Optional background for the services card and page top (1600×900). Leave empty to rotate your service images.', 'digimarket' ) );
	$f( 'lp_svc_h1', __( 'Services page heading (H1)', 'digimarket' ) );
	$f( 'lp_svc_sub', __( 'Services page subtitle', 'digimarket' ), 'textarea' );
	$f( 'lp_svc_coupon', __( 'Services coupon code', 'digimarket' ), 'text', __( 'e.g. FIRSTSITE. Shown with a Copy button and added to every WhatsApp order message. Leave blank to hide.', 'digimarket' ) );
	$f( 'lp_svc_coupon_txt', __( 'Services coupon text', 'digimarket' ), 'text', __( 'e.g. ₹500 off your first website with FIRSTSITE.', 'digimarket' ) );
	$f( 'lp_hours', __( 'Working hours', 'digimarket' ) );
	$f( 'lp_svc_seo_title', __( 'Services page SEO title', 'digimarket' ), 'text', __( 'Leave blank for the built-in keyword title.', 'digimarket' ), 'maxlength="70" data-counter="60"' );
	$f( 'lp_svc_seo_desc', __( 'Services page meta description', 'digimarket' ), 'textarea' );
	echo '<tr><th><label for="dms_lp_thm_cat">' . esc_html__( 'Themes category', 'digimarket' ) . '</label></th><td><select id="dms_lp_thm_cat" name="st[lp_thm_cat]"><option value="0">' . esc_html__( 'Auto (category named WordPress / Themes)', 'digimarket' ) . '</option>';
	foreach ( dm_categories() as $c ) {
		echo '<option value="' . (int) $c->term_id . '"' . selected( (int) $s['lp_thm_cat'], (int) $c->term_id, false ) . '>' . esc_html( $c->name ) . '</option>';
	}
	$auto = dm_lp_theme_term();
	echo '</select><p class="description">' . esc_html( $auto ? sprintf( /* translators: %s */ __( 'Currently showing: %s. Add a “Live demo URL” on each theme for the Live preview button.', 'digimarket' ), $auto->name ) : __( 'No themes category found yet.', 'digimarket' ) ) . '</p></td></tr>';
	$f( 'lp_thm_card_title', __( 'Themes card headline', 'digimarket' ) );
	$f( 'lp_thm_card_btn', __( 'Themes card button', 'digimarket' ) );
	$f( 'lp_thm_h1', __( 'Themes page heading (H1)', 'digimarket' ) );
	$f( 'lp_thm_sub', __( 'Themes page subtitle', 'digimarket' ), 'textarea' );
	$f( 'lp_thm_includes', __( '“What you get” list', 'digimarket' ), 'textarea', __( 'One item per line.', 'digimarket' ) );
	$f( 'lp_thm_faq', __( 'Themes FAQ', 'digimarket' ), 'textarea', __( 'One per line: Question | Answer.', 'digimarket' ) );
	$f( 'lp_thm_seo_title', __( 'Themes page SEO title', 'digimarket' ), 'text', __( 'Leave blank for the built-in keyword title.', 'digimarket' ), 'maxlength="70" data-counter="60"' );
	$f( 'lp_thm_seo_desc', __( 'Themes page meta description', 'digimarket' ), 'textarea' );
	echo '</table>';

	echo '<h2>' . esc_html__( 'Articles', 'digimarket' ) . '</h2><table class="form-table">';
	$f( 'articles_show', __( 'Articles menu item', 'digimarket' ), 'checkbox', __( 'Show the Articles link in the header and footer. Articles never appear on the homepage.', 'digimarket' ) );
	$f( 'articles_label', __( 'Menu label', 'digimarket' ) );
	echo '</table>';

	echo '<h2>' . esc_html__( 'SEO & business details', 'digimarket' ) . '</h2><table class="form-table">';
	$f( 'seo_home_title', __( 'Homepage SEO title', 'digimarket' ), 'text', __( '50–60 characters. Leave blank for “Site name – tagline”.', 'digimarket' ), 'maxlength="70" data-counter="60"' );
	$f( 'seo_home_desc', __( 'Homepage meta description', 'digimarket' ), 'textarea', __( '120–160 characters.', 'digimarket' ) );
	$f( 'seo_default_image', __( 'Default share image', 'digimarket' ), 'media', __( 'Used for WhatsApp/Facebook previews when a page has no image (1200×630 recommended).', 'digimarket' ) );
	$f( 'gsc_verify', __( 'Google Search Console code', 'digimarket' ), 'text', __( 'Only the content="…" value of the verification meta tag.', 'digimarket' ) );
	$f( 'bing_verify', __( 'Bing Webmaster code', 'digimarket' ) );
	$f( 'social_links', __( 'Social profiles', 'digimarket' ), 'textarea', __( 'One URL per line (Instagram, Facebook, YouTube, LinkedIn…). Used in the footer and structured data.', 'digimarket' ) );
	$f( 'area_served', __( 'Area served', 'digimarket' ), 'text', __( 'e.g. Odisha · All India', 'digimarket' ) );
	$f( 'business_phone', __( 'Business phone', 'digimarket' ), 'tel' );
	$f( 'business_email', __( 'Business email', 'digimarket' ), 'email' );
	$f( 'business_legal_name', __( 'Legal business name', 'digimarket' ), 'text', __( 'Shown in legal pages and invoices. Leave blank to use the site name.', 'digimarket' ) );
	$f( 'business_address', __( 'Business address', 'digimarket' ), 'text', __( 'Shown in the footer, Contact page, Terms and Privacy Policy (required for Razorpay and Indian e-commerce rules).', 'digimarket' ) );
	$f( 'grievance_name', __( 'Grievance Officer name', 'digimarket' ), 'text', __( 'Required by the Consumer Protection (E-Commerce) Rules and IT Rules. Uses the business email.', 'digimarket' ) );
	$f( 'affiliate_note', __( 'Affiliate disclosure', 'digimarket' ), 'text', __( 'Shown on every page that contains partner links.', 'digimarket' ) );
	echo '</table>';

	submit_button( __( 'Save storefront settings', 'digimarket' ) );
	echo '</form>';
	echo '<h2 id="legal">' . esc_html__( 'Legal pages', 'digimarket' ) . '</h2><p class="description">' . esc_html__( 'Terms, Privacy, Refund, Shipping & Delivery, Contact, About, Affiliate and Content & IP pages use the business details above automatically. Use this button to rewrite all of them with the latest launch-ready text (your own edits to those pages will be replaced; WordPress keeps the old version in Revisions).', 'digimarket' ) . '</p>';
	dm_admin_form_open( 'refresh_legal' );
	submit_button( __( 'Update all legal pages to the latest text', 'digimarket' ), 'secondary', 'submit', false, array( 'onclick' => "return confirm('" . esc_js( __( 'Rewrite all legal pages with the latest text?', 'digimarket' ) ) . "');" ) );
	echo '</form></div>';
}

add_action( 'dm_admin_do_save_store', function ( $r ) {
	$in  = isset( $r['st'] ) ? (array) $r['st'] : array();
	$def = dm_store_defaults();
	$new = dm_store();
	foreach ( $def as $k => $d ) {
		if ( 'blocks' === $k ) {
			continue;
		}
		if ( in_array( $k, array( 'announce_on', 'bottom_nav', 'articles_show' ), true ) ) {
			$new[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		} elseif ( isset( $in[ $k ] ) ) {
			if ( in_array( $k, array( 'section_count', 'seo_default_image', 'owner_photo', 'lp_svc_wall', 'lp_thm_cat' ), true ) ) {
				$new[ $k ] = absint( $in[ $k ] );
			} elseif ( in_array( $k, array( 'announce_link' ), true ) ) {
				$new[ $k ] = esc_url_raw( $in[ $k ] );
			} elseif ( 'announce_bg' === $k ) {
				$new[ $k ] = sanitize_hex_color( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : $d;
			} elseif ( in_array( $k, array( 'process_steps', 'default_faq', 'social_links', 'owner_bio', 'seo_home_desc', 'header_links', 'lp_svc_sub', 'lp_svc_seo_desc', 'lp_thm_sub', 'lp_thm_includes', 'lp_thm_faq', 'lp_thm_seo_desc' ), true ) ) {
				$new[ $k ] = sanitize_textarea_field( $in[ $k ] );
			} else {
				$new[ $k ] = sanitize_text_field( $in[ $k ] );
			}
		}
	}
	$blocks = array();
	foreach ( array_keys( dm_home_block_defs() ) as $k ) {
		$b            = isset( $r['blocks'][ $k ] ) ? (array) $r['blocks'][ $k ] : array();
		$blocks[ $k ] = array( 'on' => empty( $b['on'] ) ? 0 : 1, 'order' => max( 1, min( 99, absint( $b['order'] ?? 50 ) ) ) );
	}
	$new['blocks']        = $blocks;
	$new['lp_svc_coupon'] = strtoupper( preg_replace( '/\s+/', '', (string) $new['lp_svc_coupon'] ) );
	update_option( 'dm_store', $new );
	do_action( 'dm_store_saved' );
	do_action( 'litespeed_purge_all' ); // Show homepage changes at once on LiteSpeed hosting.
	dm_admin_back( __( 'Storefront settings saved.', 'digimarket' ) );
} );

/* Media picker + SEO helpers for our admin screens. */
add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	$ids    = array( 'marketplace_page_dm-storefront', 'dm_banner', 'dm_product', 'dm_portfolio', 'dm_testimonial', 'edit-dm_category', 'post' );
	if ( $screen && ( in_array( $screen->id, $ids, true ) || in_array( $screen->post_type, array( 'dm_banner', 'dm_product', 'dm_portfolio', 'dm_testimonial', 'post' ), true ) ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'dm-admin', DM_URI . '/assets/js/admin.js', array( 'jquery' ), DM_VERSION, true );
		wp_localize_script(
			'dm-admin',
			'DMA',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'dm_admin_ajax' ),
				'site'  => get_bloginfo( 'name' ),
				'home'  => home_url( '/' ),
			)
		);
	}
} );

/* -------------------------------------------------------------------------
 * Category extras: icon, colour, intro, FAQ, SEO
 * ---------------------------------------------------------------------- */

function dm_category_extra_fields() {
	return array(
		'dm_icon'      => array( __( 'Icon (emoji or short text)', 'digimarket' ), 'text', __( 'Optional. e.g. 📚 — leave blank for an automatic icon.', 'digimarket' ) ),
		'dm_icon_img'  => array( __( 'Icon image', 'digimarket' ), 'media', __( 'Optional square image (overrides the emoji).', 'digimarket' ) ),
		'dm_color'     => array( __( 'Colour', 'digimarket' ), 'color', '' ),
		'dm_intro'     => array( __( 'Intro text (above products)', 'digimarket' ), 'textarea', __( '100–200 words describing this category. Helps Google rank the page.', 'digimarket' ) ),
		'dm_faq'       => array( __( 'FAQ (below products)', 'digimarket' ), 'textarea', __( 'One per line: Question | Answer', 'digimarket' ) ),
		'dm_seo_title' => array( __( 'SEO title', 'digimarket' ), 'text', __( '50–60 characters.', 'digimarket' ) ),
		'dm_seo_desc'  => array( __( 'SEO description', 'digimarket' ), 'textarea', __( '120–160 characters.', 'digimarket' ) ),
	);
}

function dm_render_category_field( $key, $def, $value, $table ) {
	list( $label, $type, $help ) = $def;
	$input = '';
	if ( 'textarea' === $type ) {
		$input = '<textarea name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" rows="4" data-counter="' . ( 'dm_seo_desc' === $key ? '160' : '' ) . '">' . esc_textarea( $value ) . '</textarea>';
	} elseif ( 'media' === $type ) {
		$input = '<input type="number" min="0" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" style="width:90px"> <button type="button" class="button dm-media-pick" data-target="' . esc_attr( $key ) . '">' . esc_html__( 'Choose image', 'digimarket' ) . '</button> <span class="dm-media-preview" id="' . esc_attr( $key ) . '_prev">' . ( $value ? wp_get_attachment_image( (int) $value, array( 48, 48 ) ) : '' ) . '</span>';
	} else {
		$input = '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"' . ( 'dm_seo_title' === $key ? ' data-counter="60"' : '' ) . '>';
	}
	if ( $table ) {
		echo '<tr class="form-field"><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>' . $input . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore
	} else {
		echo '<div class="form-field"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>' . $input . ( $help ? '<p>' . esc_html( $help ) . '</p>' : '' ) . '</div>'; // phpcs:ignore
	}
}

add_action( 'dm_category_add_form_fields', function () {
	foreach ( dm_category_extra_fields() as $k => $d ) {
		dm_render_category_field( $k, $d, '', false );
	}
}, 20 );
add_action( 'dm_category_edit_form_fields', function ( $term ) {
	foreach ( dm_category_extra_fields() as $k => $d ) {
		dm_render_category_field( $k, $d, get_term_meta( $term->term_id, $k, true ), true );
	}
}, 20 );
add_action( 'created_dm_category', 'dm_save_category_extras' );
add_action( 'edited_dm_category', 'dm_save_category_extras' );
function dm_save_category_extras( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	foreach ( dm_category_extra_fields() as $k => $d ) {
		if ( ! isset( $_POST[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			continue;
		}
		$v = wp_unslash( $_POST[ $k ] ); // phpcs:ignore
		if ( 'dm_color' === $k ) {
			$v = sanitize_hex_color( $v );
			// The colour picker always posts a value; only keep it when it differs from the default black.
			$v = ( $v && '#000000' !== $v ) ? $v : '';
		} elseif ( 'dm_icon_img' === $k ) {
			$v = absint( $v );
		} elseif ( 'textarea' === $d[1] ) {
			$v = sanitize_textarea_field( $v );
		} else {
			$v = sanitize_text_field( $v );
		}
		update_term_meta( $term_id, $k, $v );
	}
}

/* -------------------------------------------------------------------------
 * Banner manager (custom post type dm_banner)
 * ---------------------------------------------------------------------- */

function dm_banner_placements() {
	return array(
		'hero'     => array( __( 'Hero card (top carousel)', 'digimarket' ), '1000×500 px (2:1)' ),
		'tile'     => array( __( 'Offer tile', 'digimarket' ), '600×600 px square + typed label & caption' ),
		'campaign' => array( __( 'Campaign banner (homepage)', 'digimarket' ), '1460×325 px + optional 1080×540 mobile' ),
		'category' => array( __( 'Category page top', 'digimarket' ), '1460×325 px + optional 1080×540 mobile' ),
		'articles' => array( __( 'Articles page top', 'digimarket' ), '1460×325 px + optional 1080×540 mobile' ),
		'cart'     => array( __( 'Cart / checkout strip', 'digimarket' ), '1200×150 px' ),
	);
}

add_action( 'init', function () {
	register_post_type(
		'dm_banner',
		array(
			'labels'       => array(
				'name'          => __( 'Banners', 'digimarket' ),
				'singular_name' => __( 'Banner', 'digimarket' ),
				'add_new_item'  => __( 'Add banner', 'digimarket' ),
				'edit_item'     => __( 'Edit banner', 'digimarket' ),
				'all_items'     => __( 'Banners', 'digimarket' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'dm-marketplace',
			'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
			'menu_icon'    => 'dashicons-images-alt2',
		)
	);
} );

add_action( 'add_meta_boxes_dm_banner', function () {
	add_meta_box( 'dm_banner_data', __( 'Banner settings', 'digimarket' ), 'dm_banner_metabox', 'dm_banner', 'normal', 'high' );
	add_meta_box( 'dm_banner_stats', __( 'Performance', 'digimarket' ), 'dm_banner_stats_box', 'dm_banner', 'side' );
} );

add_filter( 'admin_post_thumbnail_html', function ( $html, $post_id ) {
	if ( 'dm_banner' === get_post_type( $post_id ) ) {
		$html = '<p class="description">' . esc_html__( 'This is the main (desktop) banner image. See the size guide in Banner settings.', 'digimarket' ) . '</p>' . $html;
	}
	return $html;
}, 10, 2 );

function dm_banner_meta( $id, $key, $default = '' ) {
	$v = get_post_meta( $id, '_dm_b_' . $key, true );
	return '' === $v ? $default : $v;
}

function dm_banner_metabox( $post ) {
	wp_nonce_field( 'dm_banner_save', 'dm_banner_nonce' );
	$p     = dm_banner_meta( $post->ID, 'placement', 'hero' );
	$cats  = dm_categories();
	$field = function ( $label, $html, $help = '' ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . $html . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore
	};
	echo '<table class="form-table dm-banner-form">';
	$opts = '';
	foreach ( dm_banner_placements() as $k => $d ) {
		$opts .= '<option value="' . esc_attr( $k ) . '"' . selected( $p, $k, false ) . '>' . esc_html( $d[0] . ' — ' . $d[1] ) . '</option>';
	}
	$field( __( 'Placement', 'digimarket' ), '<select name="dmb[placement]" id="dmb_placement">' . $opts . '</select>', __( 'Set the image with “Banner image” in the right sidebar (Featured image).', 'digimarket' ) );
	$mob = (int) dm_banner_meta( $post->ID, 'mobile', 0 );
	$field( __( 'Mobile image (optional)', 'digimarket' ), '<input type="number" min="0" id="dmb_mobile" name="dmb[mobile]" value="' . esc_attr( $mob ) . '" style="width:90px"> <button type="button" class="button dm-media-pick" data-target="dmb_mobile">' . esc_html__( 'Choose image', 'digimarket' ) . '</button> <span class="dm-media-preview" id="dmb_mobile_prev">' . ( $mob ? wp_get_attachment_image( $mob, array( 120, 60 ) ) : '' ) . '</span>', __( 'Campaign/category banners: 1080×540 px for phones. Hero cards and tiles use one image everywhere.', 'digimarket' ) );
	$field( __( 'Link', 'digimarket' ), '<input type="url" class="large-text" name="dmb[link]" value="' . esc_attr( dm_banner_meta( $post->ID, 'link' ) ) . '" placeholder="' . esc_attr( home_url( '/sale/' ) ) . '">', __( 'Category, product, service, /sale/, a coupon link like /?coupon=DUSSEHRA30, or any URL.', 'digimarket' ) );
	$field( __( 'Alt text', 'digimarket' ), '<input type="text" class="large-text" name="dmb[alt]" value="' . esc_attr( dm_banner_meta( $post->ID, 'alt' ) ) . '" required>', __( 'Describe the offer, e.g. “Dussehra sale – flat 30% off on notes and templates”. Required for SEO and screen readers.', 'digimarket' ) );
	$field( __( 'Tile label', 'digimarket' ), '<input type="text" name="dmb[label]" value="' . esc_attr( dm_banner_meta( $post->ID, 'label' ) ) . '" placeholder="Min. 50% Off">', __( 'Offer tiles only — the coloured strip on the tile.', 'digimarket' ) );
	$field( __( 'Tile caption', 'digimarket' ), '<input type="text" name="dmb[caption]" value="' . esc_attr( dm_banner_meta( $post->ID, 'caption' ) ) . '" placeholder="Resume templates">', __( 'Offer tiles only — one line under the tile.', 'digimarket' ) );
	$copts = '<option value="0">' . esc_html__( 'All categories', 'digimarket' ) . '</option>';
	$cur   = (int) dm_banner_meta( $post->ID, 'term', 0 );
	foreach ( $cats as $c ) {
		$copts .= '<option value="' . (int) $c->term_id . '"' . selected( $cur, $c->term_id, false ) . '>' . esc_html( $c->name ) . '</option>';
	}
	$field( __( 'Category (category banners)', 'digimarket' ), '<select name="dmb[term]">' . $copts . '</select>' );
	$field( __( 'Start', 'digimarket' ), '<input type="datetime-local" name="dmb[start]" value="' . esc_attr( dm_banner_meta( $post->ID, 'start' ) ) . '">', __( 'Leave empty to show immediately.', 'digimarket' ) );
	$field( __( 'End', 'digimarket' ), '<input type="datetime-local" name="dmb[end]" value="' . esc_attr( dm_banner_meta( $post->ID, 'end' ) ) . '">', __( 'Leave empty to show until you pause it. Uses the site timezone.', 'digimarket' ) );
	$field( __( 'Countdown', 'digimarket' ), '<label><input type="checkbox" name="dmb[countdown]" value="1"' . checked( dm_banner_meta( $post->ID, 'countdown' ), '1', false ) . '> ' . esc_html__( 'Show a live “Ends in” timer on the banner (needs an End time).', 'digimarket' ) . '</label>' );
	$aud = dm_banner_meta( $post->ID, 'audience', 'all' );
	$field( __( 'Audience', 'digimarket' ), '<select name="dmb[audience]"><option value="all"' . selected( $aud, 'all', false ) . '>' . esc_html__( 'Everyone', 'digimarket' ) . '</option><option value="guests"' . selected( $aud, 'guests', false ) . '>' . esc_html__( 'Logged-out visitors', 'digimarket' ) . '</option><option value="members"' . selected( $aud, 'members', false ) . '>' . esc_html__( 'Logged-in buyers', 'digimarket' ) . '</option></select>' );
	echo '</table><p class="description">' . esc_html__( 'Order: use “Order” in the Banner attributes box (lower shows first). Draft or Pending = paused.', 'digimarket' ) . '</p>';
}

function dm_banner_stats_box( $post ) {
	$v = (int) get_post_meta( $post->ID, '_dm_b_views', true );
	$c = (int) get_post_meta( $post->ID, '_dm_b_clicks', true );
	echo '<p>' . esc_html__( 'Views', 'digimarket' ) . ': <strong>' . (int) $v . '</strong><br>' . esc_html__( 'Clicks', 'digimarket' ) . ': <strong>' . (int) $c . '</strong><br>' . esc_html__( 'Click-through rate', 'digimarket' ) . ': <strong>' . ( $v ? esc_html( round( $c / $v * 100, 2 ) ) . '%' : '—' ) . '</strong></p>';
	echo '<p>' . esc_html__( 'Status', 'digimarket' ) . ': ' . wp_kses_post( dm_banner_status_label( $post->ID ) ) . '</p>';
}

add_action( 'save_post_dm_banner', function ( $pid ) {
	if ( ! isset( $_POST['dm_banner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_banner_nonce'] ) ), 'dm_banner_save' ) || ! current_user_can( 'edit_post', $pid ) ) {
		return;
	}
	$d = isset( $_POST['dmb'] ) ? wp_unslash( (array) $_POST['dmb'] ) : array(); // phpcs:ignore
	update_post_meta( $pid, '_dm_b_placement', array_key_exists( $d['placement'] ?? '', dm_banner_placements() ) ? $d['placement'] : 'hero' );
	update_post_meta( $pid, '_dm_b_mobile', absint( $d['mobile'] ?? 0 ) );
	update_post_meta( $pid, '_dm_b_link', esc_url_raw( trim( (string) ( $d['link'] ?? '' ) ) ) );
	update_post_meta( $pid, '_dm_b_alt', sanitize_text_field( $d['alt'] ?? '' ) );
	update_post_meta( $pid, '_dm_b_label', sanitize_text_field( $d['label'] ?? '' ) );
	update_post_meta( $pid, '_dm_b_caption', sanitize_text_field( $d['caption'] ?? '' ) );
	update_post_meta( $pid, '_dm_b_term', absint( $d['term'] ?? 0 ) );
	foreach ( array( 'start', 'end' ) as $k ) {
		$v = sanitize_text_field( $d[ $k ] ?? '' );
		update_post_meta( $pid, '_dm_b_' . $k, preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $v ) ? $v : '' );
	}
	update_post_meta( $pid, '_dm_b_countdown', empty( $d['countdown'] ) ? '' : '1' );
	update_post_meta( $pid, '_dm_b_audience', in_array( $d['audience'] ?? '', array( 'all', 'guests', 'members' ), true ) ? $d['audience'] : 'all' );
} );

/**
 * Local (site timezone) timestamp for a "Y-m-dTH:i" value.
 */
function dm_local_ts( $value ) {
	if ( ! $value ) {
		return 0;
	}
	try {
		$dt = new DateTime( str_replace( 'T', ' ', $value ), wp_timezone() );
		return $dt->getTimestamp();
	} catch ( Exception $e ) {
		return 0;
	}
}

function dm_banner_live( $id ) {
	$now   = time();
	$start = dm_local_ts( dm_banner_meta( $id, 'start' ) );
	$end   = dm_banner_meta( $id, 'end' ) ? dm_local_ts( dm_banner_meta( $id, 'end' ) ) : 0;
	if ( ( $start && $start > $now ) || ( $end && $end < $now ) ) {
		return false;
	}
	$aud = dm_banner_meta( $id, 'audience', 'all' );
	if ( ( 'guests' === $aud && is_user_logged_in() ) || ( 'members' === $aud && ! is_user_logged_in() ) ) {
		return false;
	}
	return true;
}

function dm_banner_status_label( $id ) {
	if ( 'publish' !== get_post_status( $id ) ) {
		return '<span class="dm-badge-admin is-muted">' . esc_html__( 'Paused', 'digimarket' ) . '</span>';
	}
	$now   = time();
	$start = dm_local_ts( dm_banner_meta( $id, 'start' ) );
	$end   = dm_banner_meta( $id, 'end' ) ? dm_local_ts( dm_banner_meta( $id, 'end' ) ) : 0;
	if ( $start && $start > $now ) {
		return '<span class="dm-badge-admin is-info">' . esc_html__( 'Scheduled', 'digimarket' ) . '</span>';
	}
	if ( $end && $end < $now ) {
		return '<span class="dm-badge-admin is-muted">' . esc_html__( 'Ended', 'digimarket' ) . '</span>';
	}
	return '<span class="dm-badge-admin is-ok">' . esc_html__( 'Live', 'digimarket' ) . '</span>';
}

/**
 * Live banners for a placement.
 */
function dm_get_banners( $placement, $term_id = 0, $limit = 12 ) {
	$q   = new WP_Query(
		array(
			'post_type'      => 'dm_banner',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'no_found_rows'  => true,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'meta_query'     => array( array( 'key' => '_dm_b_placement', 'value' => $placement ) ),
		)
	);
	$out = array();
	foreach ( $q->posts as $b ) {
		if ( ! has_post_thumbnail( $b->ID ) || ! dm_banner_live( $b->ID ) ) {
			continue;
		}
		if ( 'category' === $placement ) {
			$t = (int) dm_banner_meta( $b->ID, 'term', 0 );
			if ( $t && $t !== (int) $term_id ) {
				continue;
			}
		}
		$out[] = $b;
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/**
 * Banner image markup (picture element when a mobile image is set).
 */
function dm_banner_img( $b, $eager = false, $sizes = '' ) {
	$alt  = dm_banner_meta( $b->ID, 'alt', get_the_title( $b ) );
	$attr = array(
		'alt'      => $alt,
		'loading'  => $eager ? 'eager' : 'lazy',
		'decoding' => 'async',
		'class'    => 'dm-banner-img',
	);
	if ( $eager ) {
		$attr['fetchpriority'] = 'high';
	}
	if ( $sizes ) {
		$attr['sizes'] = $sizes;
	}
	$img = wp_get_attachment_image( get_post_thumbnail_id( $b->ID ), 'full', false, $attr );
	$mob = (int) dm_banner_meta( $b->ID, 'mobile', 0 );
	if ( $mob ) {
		$src = wp_get_attachment_image_srcset( $mob, 'full' );
		$src = $src ? $src : wp_get_attachment_image_url( $mob, 'full' );
		return '<picture><source media="(max-width: 640px)" srcset="' . esc_attr( $src ) . '">' . $img . '</picture>';
	}
	return $img;
}

function dm_banner_countdown_html( $b ) {
	if ( ! dm_banner_meta( $b->ID, 'countdown' ) || ! dm_banner_meta( $b->ID, 'end' ) ) {
		return '';
	}
	return '<span class="dm-banner-timer" data-countdown="' . esc_attr( dm_local_ts( dm_banner_meta( $b->ID, 'end' ) ) ) . '">' . esc_html__( 'Ends in', 'digimarket' ) . ' <b>--:--:--</b></span>';
}

function dm_banner_link_open( $b, $class ) {
	$link = dm_banner_meta( $b->ID, 'link' );
	$tag  = $link ? 'a' : 'div';
	$out  = '<' . $tag . ' class="' . esc_attr( $class ) . '" data-banner="' . (int) $b->ID . '"';
	if ( $link ) {
		$out .= ' href="' . esc_url( $link ) . '"';
		if ( 0 !== strpos( $link, home_url() ) && 0 !== strpos( $link, '/' ) ) {
			$out .= ' target="_blank" rel="noopener"';
		}
	}
	return array( $out . '>', '</' . $tag . '>' );
}

/**
 * Hero card carousel.
 */
function dm_render_hero_carousel() {
	$banners = dm_get_banners( 'hero' );
	if ( ! $banners ) {
		return false;
	}
	echo '<section class="dm-hero-car" aria-roledescription="carousel" aria-label="' . esc_attr__( 'Offers', 'digimarket' ) . '"><div class="dm-container">';
	echo '<div class="dm-car" data-carousel data-autoplay="4000"><div class="dm-car-track" tabindex="0">';
	foreach ( $banners as $i => $b ) {
		list( $open, $close ) = dm_banner_link_open( $b, 'dm-car-slide' );
		echo $open . dm_banner_img( $b, $i < 3, '(max-width: 640px) 92vw, 460px' ) . dm_banner_countdown_html( $b ) . $close; // phpcs:ignore
	}
	echo '</div>';
	if ( count( $banners ) > 1 ) {
		echo '<button class="dm-car-nav is-prev" type="button" aria-label="' . esc_attr__( 'Previous', 'digimarket' ) . '">' . dm_icon( 'chev-l', 22 ) . '</button>'; // phpcs:ignore
		echo '<button class="dm-car-nav is-next" type="button" aria-label="' . esc_attr__( 'Next', 'digimarket' ) . '">' . dm_icon( 'chev-r', 22 ) . '</button>'; // phpcs:ignore
		echo '<div class="dm-car-dots" role="tablist">';
		foreach ( $banners as $i => $b ) {
			echo '<button type="button" role="tab" aria-label="' . esc_attr( sprintf( /* translators: %d */ __( 'Show offer %d', 'digimarket' ), $i + 1 ) ) . '"' . ( 0 === $i ? ' class="is-active" aria-selected="true"' : '' ) . '></button>';
		}
		echo '</div><button class="dm-car-pause" type="button" aria-label="' . esc_attr__( 'Pause slideshow', 'digimarket' ) . '"></button>';
	}
	echo '</div></div></section>';
	return true;
}

function dm_render_offer_tiles() {
	$tiles = dm_get_banners( 'tile', 0, 12 );
	if ( ! $tiles ) {
		return false;
	}
	echo '<section class="dm-block"><div class="dm-container"><div class="dm-tiles">';
	foreach ( $tiles as $b ) {
		list( $open, $close ) = dm_banner_link_open( $b, 'dm-tile' );
		$label   = dm_banner_meta( $b->ID, 'label' );
		$caption = dm_banner_meta( $b->ID, 'caption' );
		echo $open . '<span class="dm-tile-media">' . dm_banner_img( $b, false, '(max-width: 640px) 31vw, 190px' ) . '</span>'; // phpcs:ignore
		if ( $label ) {
			echo '<span class="dm-tile-label">' . esc_html( $label ) . '</span>';
		}
		if ( $caption ) {
			echo '<span class="dm-tile-cap">' . esc_html( $caption ) . '</span>';
		}
		echo $close; // phpcs:ignore
	}
	echo '</div></div></section>';
	return true;
}

function dm_render_wide_banner( $placement, $term_id = 0, $wrap = true ) {
	$list = dm_get_banners( $placement, $term_id, 1 );
	if ( ! $list ) {
		return false;
	}
	$b                    = $list[0];
	list( $open, $close ) = dm_banner_link_open( $b, 'dm-wide-banner dm-wide-' . $placement );
	if ( $wrap ) {
		echo '<section class="dm-block"><div class="dm-container">';
	}
	echo $open . dm_banner_img( $b, false, '(max-width: 640px) 100vw, 1200px' ) . dm_banner_countdown_html( $b ) . $close; // phpcs:ignore
	if ( $wrap ) {
		echo '</div></section>';
	}
	return true;
}

/* Banner analytics: batched view/click beacons from the front end. */
add_action( 'wp_ajax_dm_banner_stat', 'dm_ajax_banner_stat' );
add_action( 'wp_ajax_nopriv_dm_banner_stat', 'dm_ajax_banner_stat' );
function dm_ajax_banner_stat() {
	$type = isset( $_POST['type'] ) && 'click' === $_POST['type'] ? 'clicks' : 'views'; // phpcs:ignore
	$ids  = array_slice( array_filter( array_map( 'absint', explode( ',', (string) ( $_POST['ids'] ?? '' ) ) ) ), 0, 20 ); // phpcs:ignore
	foreach ( $ids as $id ) {
		if ( 'dm_banner' === get_post_type( $id ) ) {
			update_post_meta( $id, '_dm_b_' . $type, (int) get_post_meta( $id, '_dm_b_' . $type, true ) + 1 );
		}
	}
	wp_send_json_success();
}

/* Admin list columns. */
add_filter( 'manage_dm_banner_posts_columns', function ( $cols ) {
	return array(
		'cb'        => $cols['cb'],
		'dm_prev'   => __( 'Banner', 'digimarket' ),
		'title'     => __( 'Title', 'digimarket' ),
		'dm_place'  => __( 'Placement', 'digimarket' ),
		'dm_status' => __( 'Status', 'digimarket' ),
		'dm_perf'   => __( 'Views / clicks', 'digimarket' ),
		'dm_order'  => __( 'Order', 'digimarket' ),
	);
} );
add_action( 'manage_dm_banner_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'dm_prev':
			echo get_the_post_thumbnail( $id, array( 160, 80 ), array( 'style' => 'max-width:160px;height:auto;border-radius:8px' ) );
			break;
		case 'dm_place':
			$p = dm_banner_placements();
			$k = dm_banner_meta( $id, 'placement', 'hero' );
			echo esc_html( isset( $p[ $k ] ) ? $p[ $k ][0] : $k );
			$e = dm_banner_meta( $id, 'end' );
			if ( $e ) {
				echo '<br><small>' . esc_html__( 'Ends', 'digimarket' ) . ' ' . esc_html( str_replace( 'T', ' ', $e ) ) . '</small>';
			}
			break;
		case 'dm_status':
			echo wp_kses_post( dm_banner_status_label( $id ) );
			break;
		case 'dm_perf':
			$v = (int) get_post_meta( $id, '_dm_b_views', true );
			$c = (int) get_post_meta( $id, '_dm_b_clicks', true );
			echo (int) $v . ' / ' . (int) $c . ( $v ? ' <small>(' . esc_html( round( $c / $v * 100, 1 ) ) . '%)</small>' : '' );
			break;
		case 'dm_order':
			echo (int) get_post_field( 'menu_order', $id );
			break;
	}
}, 10, 2 );

add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'dm_banner' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
} );

/* Demo banner import button. */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-dm_banner' !== $screen->id || ! current_user_can( 'dm_manage_marketplace' ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Size guide:', 'digimarket' ) . '</strong> ' . esc_html__( 'Hero card 1000×500 · Offer tile 600×600 · Campaign 1460×325 (+1080×540 mobile) · Cart strip 1200×150. Keep text 40px from the edges.', 'digimarket' ) . '</p>';
	echo '<p><a class="button button-primary" href="' . esc_url( dm_admin_action_url( 'import_demo_banners' ) ) . '" data-confirm="1">' . esc_html__( 'Import demo banners', 'digimarket' ) . '</a> <span class="description">' . esc_html__( 'Adds 6 hero cards, 6 offer tiles, a Dussehra campaign banner and a cart strip so the homepage never looks empty. Replace them with your own any time.', 'digimarket' ) . '</span></p></div>';
} );

function dm_demo_banner_set() {
	$cat = function ( $needles ) {
		foreach ( dm_categories() as $t ) {
			foreach ( (array) $needles as $n ) {
				if ( false !== stripos( $t->slug . ' ' . $t->name, $n ) ) {
					return get_term_link( $t );
				}
			}
		}
		return dm_products_url();
	};
	$sale = dm_url( 'sale' );
	return array(
		array( 'hero-1-dussehra-sale', 'hero', __( 'Dussehra sale – flat 30% off on notes, templates and kids sheets', 'digimarket' ), $sale, '', '' ),
		array( 'hero-2-school-website', 'hero', __( 'School and college website development starting at ₹5,000', 'digimarket' ), $cat( array( 'website', 'service' ) ), '', '' ),
		array( 'hero-3-resume-templates', 'hero', __( 'ATS resume templates from ₹49 – Word and PDF, instant download', 'digimarket' ), $cat( array( 'resume', 'career' ) ), '', '' ),
		array( 'hero-4-study-notes', 'hero', __( 'Class 10 and 12 notes with previous year papers from ₹29', 'digimarket' ), $cat( array( 'note', 'study' ) ), '', '' ),
		array( 'hero-5-puja-committee', 'hero', __( 'Puja committee website with donation tracking starting at ₹3,000', 'digimarket' ), $cat( array( 'committee', 'website', 'service' ) ), '', '' ),
		array( 'hero-6-kids-zone', 'hero', __( 'Kids worksheets and colouring sheets from ₹29', 'digimarket' ), $cat( array( 'kid' ) ), '', '' ),
		array( 'tile-resume', 'tile', __( 'Resume templates from ₹49', 'digimarket' ), $cat( array( 'resume', 'career' ) ), __( 'From ₹49', 'digimarket' ), __( 'Resume templates', 'digimarket' ) ),
		array( 'tile-notes', 'tile', __( 'Class 12 notes from ₹29', 'digimarket' ), $cat( array( 'note', 'study' ) ), __( 'From ₹29', 'digimarket' ), __( 'Class 12 notes', 'digimarket' ) ),
		array( 'tile-kids', 'tile', __( 'Kids worksheets from ₹29', 'digimarket' ), $cat( array( 'kid' ) ), __( 'From ₹29', 'digimarket' ), __( 'Kids worksheets', 'digimarket' ) ),
		array( 'tile-school-website', 'tile', __( 'School websites starting ₹5,000', 'digimarket' ), $cat( array( 'website', 'service' ) ), __( 'Starting ₹5,000', 'digimarket' ), __( 'School websites', 'digimarket' ) ),
		array( 'tile-committee-website', 'tile', __( 'Committee websites starting ₹3,000', 'digimarket' ), $cat( array( 'committee', 'website' ) ), __( 'Starting ₹3,000', 'digimarket' ), __( 'Committee websites', 'digimarket' ) ),
		array( 'tile-canva-packs', 'tile', __( 'Canva social media post packs from ₹49', 'digimarket' ), $cat( array( 'design', 'canva' ) ), __( 'From ₹49', 'digimarket' ), __( 'Canva post packs', 'digimarket' ) ),
		array( 'campaign-dussehra-desktop-1460x325', 'campaign', __( 'Dussehra mega sale – flat 30% off with code DUSSEHRA30', 'digimarket' ), $sale, '', '' ),
		array( 'strip-first-order', 'cart', __( 'Save 10% on your first order with code PIKA10', 'digimarket' ), '', '', '' ),
	);
}

add_action( 'dm_admin_do_import_demo_banners', function () {
	if ( ! current_user_can( 'dm_manage_marketplace' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'digimarket' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$made  = 0;
	$order = 1;
	$media = function ( $name ) {
		$src = DM_DIR . '/assets/demo/' . $name . '.webp';
		if ( ! file_exists( $src ) ) {
			return 0;
		}
		$tmp = wp_tempnam( $name . '.webp' );
		copy( $src, $tmp );
		$id = media_handle_sideload( array( 'name' => $name . '.webp', 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			return 0;
		}
		return (int) $id;
	};
	foreach ( dm_demo_banner_set() as $row ) {
		list( $file, $place, $alt, $link, $label, $caption ) = $row;
		$img = $media( $file );
		if ( ! $img ) {
			continue;
		}
		update_post_meta( $img, '_wp_attachment_image_alt', $alt );
		$id = wp_insert_post(
			array(
				'post_type'   => 'dm_banner',
				'post_status' => 'publish',
				'post_title'  => $alt,
				'menu_order'  => $order++,
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		set_post_thumbnail( $id, $img );
		update_post_meta( $id, '_dm_b_placement', $place );
		update_post_meta( $id, '_dm_b_link', $link );
		update_post_meta( $id, '_dm_b_alt', $alt );
		update_post_meta( $id, '_dm_b_label', $label );
		update_post_meta( $id, '_dm_b_caption', $caption );
		update_post_meta( $id, '_dm_b_audience', 'all' );
		if ( 'campaign' === $place ) {
			update_post_meta( $id, '_dm_b_mobile', $media( 'campaign-dussehra-mobile-1080x540' ) );
		}
		++$made;
	}
	// Create the codes the demo banners mention, switched OFF so no discount goes live by surprise.
	global $wpdb;
	foreach ( array( array( 'DUSSEHRA30', 30, 0 ), array( 'PIKA10', 10, 1 ) ) as $c ) {
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . dm_table( 'coupons' ) . ' WHERE code = %s', $c[0] ) ) ) {
			$wpdb->insert(
				dm_table( 'coupons' ),
				array(
					'code'           => $c[0],
					'discount_type'  => 'percent',
					'discount_value' => $c[1],
					'first_order'    => $c[2],
					'is_public'      => 1,
					'active'         => 0,
					'created_at'     => dm_now(),
				)
			);
		}
	}
	/* translators: %d count */
	dm_admin_back( sprintf( __( '%d demo banners imported. Coupons DUSSEHRA30 and PIKA10 were created switched OFF — activate them in Coupons when your sale starts.', 'digimarket' ), $made ) );
} );

/* -------------------------------------------------------------------------
 * Announcement bar
 * ---------------------------------------------------------------------- */

function dm_render_announcement() {
	$s = dm_store();
	if ( empty( $s['announce_on'] ) || '' === trim( $s['announce_text'] ) ) {
		return;
	}
	$end = $s['announce_end'] ? dm_local_ts( $s['announce_end'] ) : 0;
	if ( $end && $end < time() ) {
		return;
	}
	$tag = $s['announce_link'] ? 'a' : 'div';
	echo '<' . $tag . ' class="dm-announce" style="--dm-ann:' . esc_attr( $s['announce_bg'] ) . '"' . ( $s['announce_link'] ? ' href="' . esc_url( $s['announce_link'] ) . '"' : '' ) . '><span>' . esc_html( $s['announce_text'] ) . '</span>'; // phpcs:ignore
	if ( $end ) {
		echo ' <span class="dm-announce-timer" data-countdown="' . (int) $end . '">' . esc_html__( 'ends in', 'digimarket' ) . ' <b>--:--:--</b></span>';
	}
	if ( $s['announce_link'] ) {
		echo ' <span class="dm-announce-go" aria-hidden="true">→</span>';
	}
	echo '</' . $tag . '>'; // phpcs:ignore
}

/* -------------------------------------------------------------------------
 * Product helpers used by storefront blocks
 * ---------------------------------------------------------------------- */

function dm_discount_percent( $pid ) {
	$sale = dm_product_sale_price( $pid );
	$reg  = dm_product_regular_price( $pid );
	if ( null === $sale || $reg <= 0 ) {
		return 0;
	}
	return (int) round( ( $reg - $sale ) / $reg * 100 );
}

/**
 * IDs of published products currently on sale, biggest discount first.
 */
function dm_sale_product_ids( $limit = 24 ) {
	global $wpdb;
	$ids = $wpdb->get_col(
		"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_dm_sale_price'
		WHERE p.post_type = 'dm_product' AND p.post_status = 'publish' AND m.meta_value <> ''"
	); // phpcs:ignore
	$out = array();
	foreach ( $ids as $id ) {
		$off = dm_discount_percent( $id );
		if ( $off > 0 ) {
			$out[ (int) $id ] = $off;
		}
	}
	arsort( $out );
	return array_slice( array_keys( $out ), 0, $limit );
}

/**
 * Short offer line for compact cards ("Min. 30% Off", "From ₹49", "Starting ₹5,000").
 */
function dm_offer_line( $pid ) {
	$off = dm_discount_percent( $pid );
	if ( $off >= 5 ) {
		/* translators: %d percent */
		return sprintf( __( '%d%% Off', 'digimarket' ), $off );
	}
	$price = dm_product_price( $pid );
	if ( dm_is_service( $pid ) ) {
		/* translators: %s price */
		return sprintf( __( 'Starting %s', 'digimarket' ), dm_money_short( $price ) );
	}
	if ( $price <= 0 ) {
		return __( 'Free', 'digimarket' );
	}
	/* translators: %s price */
	return sprintf( __( 'Just %s', 'digimarket' ), dm_money_short( $price ) );
}

/**
 * Money without paise when it's a whole number (₹49 instead of ₹49.00).
 */
function dm_money_short( $amount ) {
	$amount = (float) $amount;
	$dec    = ( floor( $amount ) == $amount ) ? 0 : 2; // phpcs:ignore
	return dm_opt( 'currency_symbol', '₹' ) . number_format_i18n( $amount, $dec );
}

/**
 * Top-level categories that have products, for homepage section boxes.
 */
function dm_home_section_terms( $limit ) {
	$out = array();
	foreach ( dm_categories( array( 'parent' => 0, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) ) as $t ) {
		$out[] = $t;
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

function dm_section_gradients() {
	return array(
		array( '#EDE9FE', '#FCE7F3' ),
		array( '#DCFCE7', '#E0F2FE' ),
		array( '#FEF3C7', '#FFE4E6' ),
		array( '#E0E7FF', '#F3E8FF' ),
		array( '#CCFBF1', '#FEF9C3' ),
		array( '#FFE4E6', '#EDE9FE' ),
	);
}

/* -------------------------------------------------------------------------
 * Search suggestions (header search box)
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_dm_suggest', 'dm_ajax_suggest' );
add_action( 'wp_ajax_nopriv_dm_suggest', 'dm_ajax_suggest' );
function dm_ajax_suggest() {
	$q = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore
	if ( mb_strlen( $q ) < 2 ) {
		wp_send_json_success( array( 'items' => array() ) );
	}
	$items = array();
	foreach ( dm_categories( array( 'search' => $q, 'number' => 3, 'hide_empty' => true ) ) as $t ) {
		$items[] = array(
			'type'  => 'cat',
			'title' => $t->name,
			'url'   => get_term_link( $t ),
			/* translators: %d */
			'sub'   => sprintf( _n( '%d item', '%d items', $t->count, 'digimarket' ), $t->count ),
		);
	}
	$res = new WP_Query(
		array(
			'post_type'      => 'dm_product',
			'post_status'    => 'publish',
			's'              => $q,
			'posts_per_page' => 6,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		)
	);
	foreach ( $res->posts as $pid ) {
		$items[] = array(
			'type'  => 'product',
			'title' => get_the_title( $pid ),
			'url'   => get_permalink( $pid ),
			'img'   => has_post_thumbnail( $pid ) ? get_the_post_thumbnail_url( $pid, 'thumbnail' ) : '',
			'price' => wp_strip_all_tags( dm_card_price_html( $pid ) ),
		);
	}
	wp_send_json_success( array( 'items' => $items, 'all' => add_query_arg( array( 's' => $q, 'post_type' => 'dm_product' ), home_url( '/' ) ) ) );
}

/* Tidy the Marketplace menu: Overview first; hide marketplace-only screens in single-seller mode. */
add_action( 'admin_menu', function () {
	global $submenu;
	if ( empty( $submenu['dm-marketplace'] ) ) {
		return;
	}
	$items = $submenu['dm-marketplace'];
	$first = array();
	$rest  = array();
	foreach ( $items as $it ) {
		if ( dm_single_seller_mode() && in_array( $it[2], array( 'dm-moderation' ), true ) ) {
			continue;
		}
		if ( 'dm-marketplace' === $it[2] ) {
			$first[] = $it;
		} else {
			$rest[] = $it;
		}
	}
	$submenu['dm-marketplace'] = array_merge( $first, $rest ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}, 999 );
