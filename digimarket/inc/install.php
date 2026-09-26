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
	$cats = array( 'Ebooks', 'Courses', 'Templates', 'Software', 'Music', 'Graphics', 'Presets', 'Fonts', 'Design Assets', 'Plugins & Code' );
	foreach ( $cats as $cat ) {
		if ( ! term_exists( $cat, 'dm_category' ) ) {
			wp_insert_term( $cat, 'dm_category' );
		}
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
	$site  = get_bloginfo( 'name' );
	$defs  = array(
		'terms'   => array( __( 'Terms of Service', 'digimarket' ), "<h2>1. About {$site}</h2><p>{$site} is a marketplace where independent sellers list and sell digital products directly to buyers. {$site} provides the platform and payment processing; each seller is responsible for the products they sell.</p><h2>2. Buyer terms</h2><p>When you buy a product you receive a personal, non-transferable licence to use it as described on the product page. Redistribution or resale is not permitted unless the product page says otherwise. Download links are personal and time-limited.</p><h2>3. Seller terms</h2><p>Sellers must own or hold the rights to everything they sell, keep their payout (KYC) details accurate, and follow our Content &amp; IP Policy. A platform commission is deducted automatically from every sale as described in the Seller Agreement. Sellers are responsible for their own GST registration and filing unless the platform states otherwise.</p><h2>4. Payments</h2><p>Payments are processed by Razorpay. We never store card details. Money is split automatically between the seller and the platform at the time of payment.</p><h2>5. Suspension</h2><p>We may suspend accounts involved in fraud, chargeback abuse, or policy violations.</p><p><em>This is a starter template. Please have it reviewed by a lawyer before launch.</em></p>" ),
		'privacy' => array( __( 'Privacy Policy', 'digimarket' ), "<h2>What we collect</h2><p>Account details (name, email, phone), order history, and — for sellers — payout/KYC details (legal name, PAN, bank account or UPI). Sensitive seller details are encrypted at rest.</p><h2>How we use it</h2><p>To deliver purchases, pay sellers, prevent fraud, send transactional emails and comply with tax law.</p><h2>Sharing</h2><p>Payment and KYC data is shared with Razorpay to process payments and payouts. Buyers' names and emails are shared with the seller of the product purchased.</p><h2>Your rights</h2><p>You can export or delete your personal data from your account settings or by contacting us.</p><p><em>This is a starter template. Please have it reviewed before launch.</em></p>" ),
		'refund'  => array( __( 'Refund & Cancellation Policy', 'digimarket' ), '<p>Because digital products can be downloaded instantly, refunds are generally available only <strong>before the product has been downloaded or accessed</strong>, within ' . (int) dm_opt( 'refund_window_days', 7 ) . ' days of purchase. Faulty or not-as-described items are handled case by case. To request a refund, open a support ticket from <em>My Account → Support</em> and choose the order.</p><p>When a refund is issued, both the seller’s share and the platform commission are reversed, and access to the product is revoked.</p>' ),
		'seller'  => array( __( 'Seller Agreement', 'digimarket' ), "<h2>Commission</h2><p>{$site} deducts a commission from every sale. The rate applied is the one in force at the time of each sale; later rate changes never affect past orders. Launch promotions may temporarily set the commission to 0%.</p><h2>Payouts</h2><p>Your share is transferred automatically to your Razorpay linked account and settled to your bank on Razorpay’s settlement cycle.</p><h2>Content ownership</h2><p>You keep ownership of your products and grant {$site} a licence to display and deliver them to buyers.</p><h2>Prohibited items</h2><p>Pirated, stolen, illegal, adult, hateful or malicious content is prohibited and will be removed.</p>" ),
		'content' => array( __( 'Content & IP Policy', 'digimarket' ), '<p>Sellers must confirm they own the rights to everything they upload. If you believe a product infringes your copyright or trademark, report it using the “Report” link on the product page or contact us with proof of ownership. We will review and remove infringing listings promptly.</p>' ),
	);
	foreach ( $defs as $key => $def ) {
		if ( ! empty( $pages[ $key ] ) && get_post( $pages[ $key ] ) && 'trash' !== get_post_status( $pages[ $key ] ) ) {
			if ( 'draft' === get_post_status( $pages[ $key ] ) && 'privacy' === $key ) {
				wp_update_post( array( 'ID' => $pages[ $key ], 'post_status' => 'publish', 'post_content' => $def[1] ) );
			}
			continue;
		}
		$existing = get_page_by_path( sanitize_title( $def[0] ) );
		if ( $existing && 'trash' !== $existing->post_status ) {
			if ( 'publish' !== $existing->post_status ) {
				// e.g. WordPress' default draft "Privacy Policy" page.
				wp_update_post( array( 'ID' => $existing->ID, 'post_status' => 'publish', 'post_content' => $def[1] ) );
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
	}
	update_option( 'dm_legal_pages', $pages );
}

function dm_legal_url( $key ) {
	$pages = get_option( 'dm_legal_pages', array() );
	return ! empty( $pages[ $key ] ) ? get_permalink( $pages[ $key ] ) : home_url( '/' );
}
