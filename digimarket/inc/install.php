<?php
/**
 * Installation: custom tables, roles, default categories, legal pages.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_switch_theme', 'dm_install' );
add_action( 'init', 'dm_maybe_upgrade', 1 );

function dm_maybe_upgrade() {
	if ( get_option( 'dm_db_version' ) !== DM_DB_VERSION ) {
		dm_install();
	}
}

function dm_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$c = $wpdb->get_charset_collate();
	$p = $wpdb->prefix . 'dm_';

	$tables = array();

	$tables[] = "CREATE TABLE {$p}orders (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  buyer_id bigint(20) unsigned NOT NULL DEFAULT 0,
  buyer_email varchar(190) NOT NULL DEFAULT '',
  buyer_name varchar(190) NOT NULL DEFAULT '',
  subtotal decimal(12,2) NOT NULL DEFAULT 0,
  discount decimal(12,2) NOT NULL DEFAULT 0,
  order_total decimal(12,2) NOT NULL DEFAULT 0,
  coupon_code varchar(64) NOT NULL DEFAULT '',
  payment_status varchar(32) NOT NULL DEFAULT 'pending',
  gateway varchar(32) NOT NULL DEFAULT '',
  razorpay_order_id varchar(64) NOT NULL DEFAULT '',
  razorpay_payment_id varchar(64) NOT NULL DEFAULT '',
  disputed tinyint(1) NOT NULL DEFAULT 0,
  note text NULL,
  ip varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  paid_at datetime NULL,
  PRIMARY KEY  (id),
  KEY buyer_id (buyer_id),
  KEY payment_status (payment_status),
  KEY razorpay_order_id (razorpay_order_id),
  KEY razorpay_payment_id (razorpay_payment_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}order_items (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
  product_title varchar(255) NOT NULL DEFAULT '',
  list_price decimal(12,2) NOT NULL DEFAULT 0,
  price_at_purchase decimal(12,2) NOT NULL DEFAULT 0,
  commission_percent_applied decimal(5,2) NOT NULL DEFAULT 0,
  commission_amount decimal(12,2) NOT NULL DEFAULT 0,
  seller_net_amount decimal(12,2) NOT NULL DEFAULT 0,
  item_status varchar(32) NOT NULL DEFAULT 'pending',
  transfer_id varchar(64) NOT NULL DEFAULT '',
  transfer_status varchar(32) NOT NULL DEFAULT 'pending',
  transfer_note varchar(255) NOT NULL DEFAULT '',
  license_key text NULL,
  download_count int(11) NOT NULL DEFAULT 0,
  access_expires datetime NULL,
  refunded_at datetime NULL,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY order_id (order_id),
  KEY product_id (product_id),
  KEY seller_id (seller_id),
  KEY transfer_id (transfer_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}payouts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
  order_item_id bigint(20) unsigned NOT NULL DEFAULT 0,
  amount decimal(12,2) NOT NULL DEFAULT 0,
  status varchar(32) NOT NULL DEFAULT 'pending',
  reference varchar(128) NOT NULL DEFAULT '',
  note varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  settled_at datetime NULL,
  PRIMARY KEY  (id),
  KEY seller_id (seller_id),
  KEY order_item_id (order_item_id),
  KEY status (status)
) $c;";

	$tables[] = "CREATE TABLE {$p}license_keys (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  key_value text NOT NULL,
  is_used tinyint(1) NOT NULL DEFAULT 0,
  assigned_order_id bigint(20) unsigned NULL,
  assigned_item_id bigint(20) unsigned NULL,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY product_id (product_id),
  KEY is_used (is_used)
) $c;";

	$tables[] = "CREATE TABLE {$p}reviews (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
  buyer_id bigint(20) unsigned NOT NULL DEFAULT 0,
  reviewer_name varchar(190) NOT NULL DEFAULT '',
  reviewer_type varchar(20) NOT NULL DEFAULT 'buyer',
  photo_id bigint(20) unsigned NOT NULL DEFAULT 0,
  rating tinyint(1) NOT NULL DEFAULT 5,
  comment text NULL,
  seller_reply text NULL,
  flagged tinyint(1) NOT NULL DEFAULT 0,
  flag_reason varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'approved',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at datetime NULL,
  PRIMARY KEY  (id),
  KEY product_id (product_id),
  KEY seller_id (seller_id),
  KEY buyer_id (buyer_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}coupons (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code varchar(64) NOT NULL DEFAULT '',
  discount_type varchar(10) NOT NULL DEFAULT 'percent',
  discount_value decimal(12,2) NOT NULL DEFAULT 0,
  seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
  min_order decimal(12,2) NOT NULL DEFAULT 0,
  valid_from date NULL,
  valid_until date NULL,
  usage_limit int(11) NOT NULL DEFAULT 0,
  times_used int(11) NOT NULL DEFAULT 0,
  category_ids varchar(255) NOT NULL DEFAULT '',
  product_ids varchar(255) NOT NULL DEFAULT '',
  first_order tinyint(1) NOT NULL DEFAULT 0,
  per_user_limit int(11) NOT NULL DEFAULT 0,
  max_discount decimal(12,2) NOT NULL DEFAULT 0,
  is_public tinyint(1) NOT NULL DEFAULT 0,
  active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY code (code)
) $c;";

	$tables[] = "CREATE TABLE {$p}wishlists (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  buyer_id bigint(20) unsigned NOT NULL DEFAULT 0,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY buyer_id (buyer_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}follows (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY user_id (user_id),
  KEY seller_id (seller_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}audit_logs (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  admin_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(100) NOT NULL DEFAULT '',
  target_type varchar(40) NOT NULL DEFAULT '',
  target_id bigint(20) unsigned NOT NULL DEFAULT 0,
  details longtext NULL,
  ip varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY action (action)
) $c;";

	$tables[] = "CREATE TABLE {$p}downloads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  order_item_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  ip varchar(64) NOT NULL DEFAULT '',
  user_agent varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY order_item_id (order_item_id),
  KEY user_id (user_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}tickets (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  seller_id bigint(20) unsigned NOT NULL DEFAULT 0,
  subject varchar(190) NOT NULL DEFAULT '',
  message text NULL,
  reply text NULL,
  status varchar(20) NOT NULL DEFAULT 'open',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at datetime NULL,
  PRIMARY KEY  (id),
  KEY user_id (user_id),
  KEY seller_id (seller_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}notifications (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  message varchar(255) NOT NULL DEFAULT '',
  link varchar(255) NOT NULL DEFAULT '',
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY user_id (user_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}reports (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  reason text NULL,
  status varchar(20) NOT NULL DEFAULT 'open',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY product_id (product_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}webhook_events (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event_id varchar(100) NOT NULL DEFAULT '',
  event varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY event_id (event_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(190) NOT NULL DEFAULT '',
  phone varchar(40) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  site_type varchar(100) NOT NULL DEFAULT '',
  budget varchar(60) NOT NULL DEFAULT '',
  message text NULL,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  source varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'new',
  notes text NULL,
  ip varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at datetime NULL,
  PRIMARY KEY  (id),
  KEY status (status)
) $c;";

	$tables[] = "CREATE TABLE {$p}clicks (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  click_type varchar(20) NOT NULL DEFAULT '',
  ref_id bigint(20) unsigned NOT NULL DEFAULT 0,
  page varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY click_type (click_type),
  KEY ref_id (ref_id)
) $c;";

	$tables[] = "CREATE TABLE {$p}review_requests (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  token varchar(64) NOT NULL DEFAULT '',
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  client_name varchar(190) NOT NULL DEFAULT '',
  contact varchar(190) NOT NULL DEFAULT '',
  review_id bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  expires_at datetime NULL,
  used_at datetime NULL,
  PRIMARY KEY  (id),
  KEY token (token)
) $c;";

	$tables[] = "CREATE TABLE {$p}redirects (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  source varchar(255) NOT NULL DEFAULT '',
  target varchar(255) NOT NULL DEFAULT '',
  hits int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY source (source(191))
) $c;";

	$tables[] = "CREATE TABLE {$p}not_found (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL DEFAULT '',
  referrer varchar(255) NOT NULL DEFAULT '',
  hits int(11) NOT NULL DEFAULT 1,
  last_seen datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY path (path(191))
) $c;";

	foreach ( $tables as $sql ) {
		dbDelta( $sql );
	}

	// Roles & capabilities.
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'dm_view_marketplace' );
		$admin->add_cap( 'dm_manage_marketplace' );
	}
	if ( ! get_role( 'dm_support' ) ) {
		add_role(
			'dm_support',
			__( 'Marketplace Support (view only)', 'digimarket' ),
			array(
				'read'                => true,
				'dm_view_marketplace' => true,
			)
		);
	}

	// Default settings.
	if ( false === get_option( 'dm_settings' ) ) {
		add_option( 'dm_settings', dm_default_settings() );
	}

	// Private storage for digital files.
	dm_private_dir();

	// Taxonomies must exist before inserting terms.
	if ( function_exists( 'dm_register_content_types' ) ) {
		dm_register_content_types();
	}
	// Starter categories on a brand-new install only — never re-create ones the owner deleted.
	if ( ! get_option( 'dm_default_cats_done' ) ) {
		$existing = get_terms( array( 'taxonomy' => 'dm_category', 'hide_empty' => false, 'fields' => 'ids', 'number' => 1 ) );
		if ( ! $existing || is_wp_error( $existing ) ) {
			$cats = array( 'Study Notes', 'Resume & Career', 'Kids Zone', 'Design Templates', 'Business & Committee Tools', 'WordPress Templates', 'Website Services', 'Hosting & Tools' );
			foreach ( $cats as $cat ) {
				if ( ! term_exists( $cat, 'dm_category' ) ) {
					wp_insert_term( $cat, 'dm_category' );
				}
			}
		}
		update_option( 'dm_default_cats_done', 1 );
	}

	dm_create_legal_pages();

	if ( ! get_option( 'permalink_structure' ) ) {
		// Marketplace URLs (/cart/, /store/{shop}/) need pretty permalinks.
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		update_option( 'permalink_structure', got_url_rewrite() ? '/%postname%/' : '/index.php/%postname%/' );
		if ( isset( $GLOBALS['wp_rewrite'] ) ) {
			$GLOBALS['wp_rewrite']->init();
		}
	}
	update_option( 'dm_flush_rewrite', 1 );
	update_option( 'dm_db_version', DM_DB_VERSION );
}

function dm_create_legal_pages() {
	$pages = get_option( 'dm_legal_pages', array() );
	$defs  = dm_legal_page_defs();
	foreach ( $defs as $key => $def ) {
		if ( ! empty( $pages[ $key ] ) && get_post( $pages[ $key ] ) && 'trash' !== get_post_status( $pages[ $key ] ) ) {
			if ( 'draft' === get_post_status( $pages[ $key ] ) && 'privacy' === $key ) {
				wp_update_post( array( 'ID' => $pages[ $key ], 'post_status' => 'publish', 'post_content' => $def[1] ) );
				update_post_meta( $pages[ $key ], '_dm_legal_hash', md5( (string) get_post_field( 'post_content', $pages[ $key ] ) ) );
			}
			continue;
		}
		$existing = get_page_by_path( sanitize_title( $def[0] ) );
		if ( $existing && 'trash' !== $existing->post_status ) {
			if ( 'publish' !== $existing->post_status ) {
				// e.g. WordPress' default draft "Privacy Policy" page.
				wp_update_post( array( 'ID' => $existing->ID, 'post_status' => 'publish', 'post_content' => $def[1] ) );
				update_post_meta( $existing->ID, '_dm_legal_hash', md5( (string) get_post_field( 'post_content', $existing->ID ) ) );
			}
			$pages[ $key ] = $existing->ID;
			continue;
		}
		$pages[ $key ] = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $def[0],
				'post_content' => $def[1],
			)
		);
		update_post_meta( $pages[ $key ], '_dm_legal_hash', md5( (string) get_post_field( 'post_content', $pages[ $key ] ) ) );
	}
	update_option( 'dm_legal_pages', $pages );
	dm_legal_upgrade();
}

function dm_legal_url( $key ) {
	$pages = get_option( 'dm_legal_pages', array() );
	return ! empty( $pages[ $key ] ) ? get_permalink( $pages[ $key ] ) : home_url( '/' );
}
