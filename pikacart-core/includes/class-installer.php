<?php
/**
 * Creates and upgrades database tables, adds starting data and pages.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Installer {

	/**
	 * Runs when the owner clicks Activate.
	 */
	public static function activate() {
		self::install();
		PKC_Roles::add_roles();
		PKC_Router::add_rewrite_rules();
		flush_rewrite_rules();
		PKC_Cron::schedule();
	}

	public static function deactivate() {
		PKC_Cron::unschedule();
		flush_rewrite_rules();
	}

	/**
	 * Runs on every load: upgrades the database after a plugin update.
	 */
	public static function maybe_upgrade() {
		$from = (int) get_option( 'pkc_db_version', 0 );
		if ( $from < PKC_DB_VERSION ) {
			self::install();
			if ( $from > 0 && $from < 2 ) {
				// Version 2 introduced the lifetime free plan.
				PKC_Access::recompute_all();
			}
			PKC_Roles::add_roles();
			update_option( 'pkc_flush_rewrite', 1 );
		}
	}

	public static function install() {
		self::create_tables();
		self::seed();
		self::set_timezone();
		PKC_Uploads::protect_base_dir();
		self::create_pages();
		update_option( 'pkc_db_version', PKC_DB_VERSION );
		update_option( 'pkc_version', PKC_VERSION );
	}

	/**
	 * Table definitions. dbDelta adds missing tables and columns safely.
	 */
	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$t = function ( $name ) {
			return pkc_table( $name );
		};

		$sql = array();

		$sql[] = "CREATE TABLE {$t('organisations')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  name varchar(190) NOT NULL DEFAULT '',
  contact_name varchar(190) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  mobile varchar(20) NOT NULL DEFAULT '',
  category_id bigint(20) unsigned NOT NULL DEFAULT 0,
  tagline varchar(255) NOT NULL DEFAULT '',
  address text NULL,
  phone varchar(40) NOT NULL DEFAULT '',
  org_email varchar(190) NOT NULL DEFAULT '',
  website varchar(190) NOT NULL DEFAULT '',
  logo varchar(255) NOT NULL DEFAULT '',
  sign varchar(255) NOT NULL DEFAULT '',
  seal varchar(255) NOT NULL DEFAULT '',
  signatory_name varchar(190) NOT NULL DEFAULT '',
  signatory_title varchar(190) NOT NULL DEFAULT '',
  event_name varchar(190) NOT NULL DEFAULT '',
  session_year varchar(40) NOT NULL DEFAULT '',
  terms text NULL,
  verify_fields text NULL,
  status varchar(20) NOT NULL DEFAULT 'trial',
  trial_start datetime NULL DEFAULT NULL,
  trial_end datetime NULL DEFAULT NULL,
  period_end datetime NULL DEFAULT NULL,
  plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
  suspend_reason text NULL,
  onboarded tinyint(1) NOT NULL DEFAULT 0,
  upload_dir varchar(64) NOT NULL DEFAULT '',
  meta longtext NULL,
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY user_id (user_id),
  KEY status (status),
  KEY mobile (mobile),
  KEY email (email),
  KEY created_at (created_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('plans')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(190) NOT NULL DEFAULT '',
  slug varchar(64) NOT NULL DEFAULT '',
  price_paise int(11) unsigned NOT NULL DEFAULT 0,
  period_days int(11) unsigned NOT NULL DEFAULT 30,
  rzp_plan_test varchar(64) NOT NULL DEFAULT '',
  rzp_plan_live varchar(64) NOT NULL DEFAULT '',
  description text NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  sort_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY slug (slug)
) $c;";

		$sql[] = "CREATE TABLE {$t('subscriptions')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
  rzp_subscription_id varchar(64) NOT NULL DEFAULT '',
  mode varchar(8) NOT NULL DEFAULT 'test',
  status varchar(20) NOT NULL DEFAULT 'created',
  current_end datetime NULL DEFAULT NULL,
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY status (status),
  KEY rzp_subscription_id (rzp_subscription_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('payments')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
  kind varchar(20) NOT NULL DEFAULT 'one_time',
  rzp_payment_id varchar(64) NULL DEFAULT NULL,
  rzp_order_id varchar(64) NOT NULL DEFAULT '',
  rzp_subscription_id varchar(64) NOT NULL DEFAULT '',
  amount_paise int(11) unsigned NOT NULL DEFAULT 0,
  discount_paise int(11) unsigned NOT NULL DEFAULT 0,
  coupon_id bigint(20) unsigned NOT NULL DEFAULT 0,
  currency varchar(8) NOT NULL DEFAULT 'INR',
  method varchar(40) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'created',
  invoice_no varchar(40) NOT NULL DEFAULT '',
  mode varchar(8) NOT NULL DEFAULT 'test',
  error text NULL,
  period_start datetime NULL DEFAULT NULL,
  period_end datetime NULL DEFAULT NULL,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY rzp_payment_id (rzp_payment_id),
  KEY org_id (org_id),
  KEY status (status),
  KEY rzp_order_id (rzp_order_id),
  KEY created_at (created_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('expenses')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  spent_on date NULL DEFAULT NULL,
  category varchar(64) NOT NULL DEFAULT '',
  amount_paise int(11) unsigned NOT NULL DEFAULT 0,
  note text NULL,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY spent_on (spent_on)
) $c;";

		$sql[] = "CREATE TABLE {$t('coupons')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code varchar(40) NOT NULL DEFAULT '',
  type varchar(10) NOT NULL DEFAULT 'percent',
  value int(11) unsigned NOT NULL DEFAULT 0,
  expires_at datetime NULL DEFAULT NULL,
  usage_limit int(11) unsigned NOT NULL DEFAULT 0,
  used_count int(11) unsigned NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY code (code)
) $c;";

		$sql[] = "CREATE TABLE {$t('categories')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  slug varchar(64) NOT NULL DEFAULT '',
  name varchar(190) NOT NULL DEFAULT '',
  description text NULL,
  icon varchar(64) NOT NULL DEFAULT '',
  sort_order int(11) NOT NULL DEFAULT 0,
  is_hidden tinyint(1) NOT NULL DEFAULT 0,
  seo longtext NULL,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY slug (slug)
) $c;";

		$sql[] = "CREATE TABLE {$t('subtypes')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  category_id bigint(20) unsigned NOT NULL DEFAULT 0,
  slug varchar(64) NOT NULL DEFAULT '',
  name varchar(190) NOT NULL DEFAULT '',
  description text NULL,
  fields longtext NULL,
  terms text NULL,
  sort_order int(11) NOT NULL DEFAULT 0,
  is_hidden tinyint(1) NOT NULL DEFAULT 0,
  seo longtext NULL,
  PRIMARY KEY  (id),
  KEY category_id (category_id),
  KEY slug (slug)
) $c;";

		$sql[] = "CREATE TABLE {$t('sizes')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(190) NOT NULL DEFAULT '',
  width_mm decimal(7,2) NOT NULL DEFAULT 0,
  height_mm decimal(7,2) NOT NULL DEFAULT 0,
  note varchar(255) NOT NULL DEFAULT '',
  sort_order int(11) NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY  (id)
) $c;";

		$sql[] = "CREATE TABLE {$t('palettes')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(190) NOT NULL DEFAULT '',
  primary_c varchar(9) NOT NULL DEFAULT '',
  secondary_c varchar(9) NOT NULL DEFAULT '',
  accent_c varchar(9) NOT NULL DEFAULT '',
  text_c varchar(9) NOT NULL DEFAULT '',
  sort_order int(11) NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY  (id)
) $c;";

		$sql[] = "CREATE TABLE {$t('templates')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  owner_org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  category_id bigint(20) unsigned NOT NULL DEFAULT 0,
  subtype_id bigint(20) unsigned NOT NULL DEFAULT 0,
  name varchar(190) NOT NULL DEFAULT '',
  slug varchar(190) NOT NULL DEFAULT '',
  style_level varchar(20) NOT NULL DEFAULT 'simple',
  layout longtext NULL,
  palettes text NULL,
  thumb varchar(255) NOT NULL DEFAULT '',
  is_featured tinyint(1) NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'draft',
  source varchar(20) NOT NULL DEFAULT 'builtin',
  sort_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY owner_org_id (owner_org_id),
  KEY subtype_id (subtype_id),
  KEY status (status)
) $c;";

		$sql[] = "CREATE TABLE {$t('projects')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  subtype_id bigint(20) unsigned NOT NULL DEFAULT 0,
  template_id bigint(20) unsigned NOT NULL DEFAULT 0,
  name varchar(190) NOT NULL DEFAULT '',
  size_id bigint(20) unsigned NOT NULL DEFAULT 0,
  custom_w decimal(7,2) NOT NULL DEFAULT 0,
  custom_h decimal(7,2) NOT NULL DEFAULT 0,
  orientation varchar(10) NOT NULL DEFAULT 'portrait',
  palette longtext NULL,
  design longtext NULL,
  fields longtext NULL,
  step tinyint(3) unsigned NOT NULL DEFAULT 1,
  status varchar(20) NOT NULL DEFAULT 'draft',
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  deleted_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY status (status)
) $c;";

		$sql[] = "CREATE TABLE {$t('members')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  project_id bigint(20) unsigned NOT NULL DEFAULT 0,
  id_no varchar(64) NOT NULL DEFAULT '',
  name varchar(190) NOT NULL DEFAULT '',
  data longtext NULL,
  photo varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'draft',
  verify_token varchar(64) NOT NULL DEFAULT '',
  valid_from date NULL DEFAULT NULL,
  valid_until date NULL DEFAULT NULL,
  session varchar(40) NOT NULL DEFAULT '',
  source varchar(20) NOT NULL DEFAULT 'manual',
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  deleted_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY project_id (project_id),
  KEY id_no (id_no),
  KEY status (status),
  KEY verify_token (verify_token)
) $c;";

		$sql[] = "CREATE TABLE {$t('selffill_links')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  project_id bigint(20) unsigned NOT NULL DEFAULT 0,
  token varchar(64) NOT NULL DEFAULT '',
  expires_at datetime NULL DEFAULT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY token (token)
) $c;";

		$sql[] = "CREATE TABLE {$t('design_requests')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  front varchar(255) NOT NULL DEFAULT '',
  back varchar(255) NOT NULL DEFAULT '',
  size_id bigint(20) unsigned NOT NULL DEFAULT 0,
  orientation varchar(10) NOT NULL DEFAULT 'portrait',
  notes text NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  reject_reason text NULL,
  template_id bigint(20) unsigned NOT NULL DEFAULT 0,
  due_at datetime NULL DEFAULT NULL,
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY status (status)
) $c;";

		$sql[] = "CREATE TABLE {$t('tickets')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  subject varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'open',
  is_read_admin tinyint(1) NOT NULL DEFAULT 0,
  is_read_user tinyint(1) NOT NULL DEFAULT 1,
  last_author varchar(10) NOT NULL DEFAULT 'org',
  last_message text NULL,
  last_message_at datetime NULL DEFAULT NULL,
  user_seen_at datetime NULL DEFAULT NULL,
  admin_seen_at datetime NULL DEFAULT NULL,
  last_emailed_at datetime NULL DEFAULT NULL,
  created_at datetime NULL DEFAULT NULL,
  updated_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY status (status),
  KEY last_message_at (last_message_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('ticket_replies')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ticket_id bigint(20) unsigned NOT NULL DEFAULT 0,
  author_type varchar(10) NOT NULL DEFAULT 'org',
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  message longtext NULL,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY ticket_id (ticket_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('notifications')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  type varchar(30) NOT NULL DEFAULT '',
  title varchar(255) NOT NULL DEFAULT '',
  body text NULL,
  link varchar(255) NOT NULL DEFAULT '',
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_read (org_id,is_read)
) $c;";

		$sql[] = "CREATE TABLE {$t('notices')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  body longtext NULL,
  starts_at datetime NULL DEFAULT NULL,
  ends_at datetime NULL DEFAULT NULL,
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('activity_log')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(64) NOT NULL DEFAULT '',
  details text NULL,
  ip varchar(45) NOT NULL DEFAULT '',
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY action (action),
  KEY created_at (created_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('webhook_events')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event_id varchar(80) NOT NULL DEFAULT '',
  event varchar(64) NOT NULL DEFAULT '',
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY event_id (event_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('reports')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  org_id bigint(20) unsigned NOT NULL DEFAULT 0,
  member_id bigint(20) unsigned NOT NULL DEFAULT 0,
  reason text NULL,
  reporter_email varchar(190) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'open',
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY org_id (org_id),
  KEY status (status)
) $c;";

		$sql[] = "CREATE TABLE {$t('trial_claims')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  kind varchar(10) NOT NULL DEFAULT '',
  hash char(64) NOT NULL DEFAULT '',
  created_at datetime NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY kind_hash (kind,hash)
) $c;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Starting data. Each block only runs when its table is empty.
	 */
	private static function seed() {
		global $wpdb;
		$now = pkc_now();

		// Plans.
		if ( ! (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'plans' ) ) ) {
			$wpdb->insert(
				pkc_table( 'plans' ),
				array(
					'name'        => 'Monthly',
					'slug'        => 'monthly',
					'price_paise' => 5900,
					'period_days' => 30,
					'description' => 'Every feature, unlimited cards, bulk import, print sheets and Design on Demand.',
					'is_active'   => 1,
					'sort_order'  => 1,
					'created_at'  => $now,
				)
			);
		}

		// Card sizes.
		if ( ! (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'sizes' ) ) ) {
			$sizes = array(
				array( 54, 86, 'Standard PVC card (CR80)' ),
				array( 57, 89, '2.25 x 3.5 inch holder' ),
				array( 60, 90, 'School and college holder' ),
				array( 67, 98, 'Large PVC card (CR100)' ),
				array( 70, 100, 'Lanyard holder, staff card' ),
				array( 74, 105, 'A7 event badge' ),
				array( 76, 102, '3 x 4 inch holder' ),
				array( 85, 110, 'Large lanyard holder' ),
				array( 90, 130, 'Conference badge' ),
				array( 102, 152, '4 x 6 inch event pass' ),
				array( 105, 148, 'A6 delegate badge' ),
			);
			foreach ( $sizes as $i => $s ) {
				$wpdb->insert(
					pkc_table( 'sizes' ),
					array(
						'name'       => $s[0] . ' x ' . $s[1] . ' mm',
						'width_mm'   => $s[0],
						'height_mm'  => $s[1],
						'note'       => $s[2],
						'sort_order' => $i + 1,
						'is_active'  => 1,
					)
				);
			}
		}

		// Colour palettes: primary, secondary, accent, text.
		if ( ! (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'palettes' ) ) ) {
			$palettes = array(
				array( 'Royal Blue', '#1E3A8A', '#3B82F6', '#F59E0B', '#0F172A' ),
				array( 'Emerald', '#065F46', '#10B981', '#FBBF24', '#052E2B' ),
				array( 'Crimson', '#9F1239', '#F43F5E', '#FCD34D', '#1F0A12' ),
				array( 'Midnight', '#111827', '#374151', '#EAB308', '#111827' ),
				array( 'Saffron', '#C2410C', '#FB923C', '#1E3A8A', '#1C1917' ),
				array( 'Teal', '#115E59', '#14B8A6', '#F97316', '#042F2E' ),
				array( 'Purple', '#5B21B6', '#8B5CF6', '#F472B6', '#1E1B4B' ),
				array( 'Maroon & Gold', '#7F1D1D', '#B91C1C', '#D4A017', '#1C0A0A' ),
				array( 'Sky', '#0369A1', '#38BDF8', '#F43F5E', '#082F49' ),
				array( 'Forest', '#14532D', '#22C55E', '#A3E635', '#052E16' ),
				array( 'Slate & Coral', '#334155', '#64748B', '#FB7185', '#0F172A' ),
				array( 'Indigo & Orange', '#3730A3', '#6366F1', '#F97316', '#1E1B4B' ),
			);
			foreach ( $palettes as $i => $p ) {
				$wpdb->insert(
					pkc_table( 'palettes' ),
					array(
						'name'        => $p[0],
						'primary_c'   => $p[1],
						'secondary_c' => $p[2],
						'accent_c'    => $p[3],
						'text_c'      => $p[4],
						'sort_order'  => $i + 1,
						'is_active'   => 1,
					)
				);
			}
		}

		// Categories and sub-types.
		if ( ! (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . pkc_table( 'categories' ) ) ) {
			foreach ( self::category_seed() as $i => $cat ) {
				$wpdb->insert(
					pkc_table( 'categories' ),
					array(
						'slug'        => $cat['slug'],
						'name'        => $cat['name'],
						'description' => $cat['desc'],
						'icon'        => $cat['icon'],
						'sort_order'  => $i + 1,
						'is_hidden'   => 0,
						'seo'         => wp_json_encode( array() ),
						'created_at'  => $now,
					)
				);
				$cat_id = (int) $wpdb->insert_id;
				foreach ( $cat['subtypes'] as $j => $sub ) {
					$wpdb->insert(
						pkc_table( 'subtypes' ),
						array(
							'category_id' => $cat_id,
							'slug'        => $sub[0],
							'name'        => $sub[1],
							'description' => '',
							'fields'      => wp_json_encode( self::default_fields( $sub[2] ) ),
							'terms'       => $cat['terms'],
							'sort_order'  => $j + 1,
							'is_hidden'   => 0,
							'seo'         => wp_json_encode( array() ),
						)
					);
				}
			}
		}
	}

	/**
	 * Field sets by kind of card. "on" means switched on by default.
	 */
	public static function default_fields( $kind ) {
		$base = array(
			array( 'key' => 'photo', 'label' => 'Photo', 'required' => true, 'on' => true ),
			array( 'key' => 'name', 'label' => 'Full name', 'required' => true, 'on' => true ),
			array( 'key' => 'id_no', 'label' => 'ID number', 'required' => true, 'on' => true ),
		);
		$extra = array();
		switch ( $kind ) {
			case 'student':
				$extra = array(
					array( 'key' => 'class', 'label' => 'Class / Course', 'required' => false, 'on' => true ),
					array( 'key' => 'section', 'label' => 'Section', 'required' => false, 'on' => true ),
					array( 'key' => 'guardian', 'label' => 'Father / Guardian name', 'required' => false, 'on' => true ),
					array( 'key' => 'dob', 'label' => 'Date of birth', 'required' => false, 'on' => true ),
					array( 'key' => 'blood_group', 'label' => 'Blood group', 'required' => false, 'on' => true ),
				);
				break;
			case 'staff':
				$extra = array(
					array( 'key' => 'designation', 'label' => 'Designation', 'required' => false, 'on' => true ),
					array( 'key' => 'department', 'label' => 'Department', 'required' => false, 'on' => true ),
					array( 'key' => 'blood_group', 'label' => 'Blood group', 'required' => false, 'on' => true ),
					array( 'key' => 'dob', 'label' => 'Date of birth', 'required' => false, 'on' => false ),
				);
				break;
			default:
				$extra = array(
					array( 'key' => 'designation', 'label' => 'Designation / Pass type', 'required' => false, 'on' => true ),
					array( 'key' => 'department', 'label' => 'Department / Area', 'required' => false, 'on' => false ),
				);
		}
		$tail = array(
			array( 'key' => 'mobile', 'label' => 'Mobile number', 'required' => true, 'on' => true ),
			array( 'key' => 'email', 'label' => 'Email', 'required' => false, 'on' => false ),
			array( 'key' => 'address', 'label' => 'Address', 'required' => false, 'on' => false ),
			array( 'key' => 'emergency', 'label' => 'Emergency contact', 'required' => false, 'on' => false ),
			array( 'key' => 'valid_from', 'label' => 'Valid from', 'required' => false, 'on' => false ),
			array( 'key' => 'valid_until', 'label' => 'Valid until', 'required' => false, 'on' => true ),
		);
		return array_merge( $base, $extra, $tail );
	}

	private static function category_seed() {
		$t_edu  = "This card is the property of the institution and must be carried at all times on campus.\nIt is not transferable. Loss of the card must be reported immediately.\nIf found, please return to the address below.";
		$t_corp = "This card is the property of the organisation and must be shown on request.\nIt is not transferable and must be returned when leaving the organisation.\nIf found, please return to the address below.";
		$t_gen  = "This card is issued by the organisation named on the front and is not transferable.\nPlease carry it at all times and report a lost card immediately.\nIf found, please return to the address below.";

		return array(
			array( 'slug' => 'school', 'name' => 'School', 'icon' => 'school', 'desc' => 'ID cards for students, teachers and staff, plus bus passes and visitor cards.', 'terms' => $t_edu, 'subtypes' => array( array( 'student', 'Student', 'student' ), array( 'staff', 'Teacher / Staff', 'staff' ), array( 'other', 'Bus pass, parent pickup, visitor', 'other' ) ) ),
			array( 'slug' => 'college', 'name' => 'College / University', 'icon' => 'college', 'desc' => 'Student, faculty, hostel, library and event cards for colleges and universities.', 'terms' => $t_edu, 'subtypes' => array( array( 'student', 'Student', 'student' ), array( 'staff', 'Faculty / Staff', 'staff' ), array( 'other', 'Hostel, library, event', 'other' ) ) ),
			array( 'slug' => 'coaching', 'name' => 'Coaching / Institute', 'icon' => 'book', 'desc' => 'Cards for coaching centres, academies and training institutes.', 'terms' => $t_edu, 'subtypes' => array( array( 'student', 'Student', 'student' ), array( 'staff', 'Faculty', 'staff' ), array( 'other', 'Batch or exam pass', 'other' ) ) ),
			array( 'slug' => 'company', 'name' => 'Company / Corporate', 'icon' => 'briefcase', 'desc' => 'Employee, intern, contractor and visitor cards for businesses.', 'terms' => $t_corp, 'subtypes' => array( array( 'employee', 'Employee', 'staff' ), array( 'intern', 'Intern / Contractor', 'staff' ), array( 'visitor', 'Visitor', 'other' ) ) ),
			array( 'slug' => 'hospital', 'name' => 'Hospital / Clinic', 'icon' => 'health', 'desc' => 'Doctor, nurse, staff and attendant cards for healthcare.', 'terms' => $t_corp, 'subtypes' => array( array( 'doctor', 'Doctor', 'staff' ), array( 'nurse', 'Nurse / Staff', 'staff' ), array( 'attendant', 'Attendant or visitor pass', 'other' ) ) ),
			array( 'slug' => 'ngo', 'name' => 'NGO / Trust / Organisation', 'icon' => 'heart', 'desc' => 'Member, volunteer and office-bearer cards.', 'terms' => $t_gen, 'subtypes' => array( array( 'member', 'Member', 'staff' ), array( 'volunteer', 'Volunteer', 'staff' ), array( 'office-bearer', 'Office bearer', 'staff' ) ) ),
			array( 'slug' => 'event', 'name' => 'Event / Conference', 'icon' => 'ticket', 'desc' => 'Delegate, organiser and VIP badges for events.', 'terms' => $t_gen, 'subtypes' => array( array( 'delegate', 'Delegate', 'other' ), array( 'organiser', 'Organiser / Volunteer', 'staff' ), array( 'vip', 'VIP / Guest', 'other' ) ) ),
			array( 'slug' => 'security', 'name' => 'Security / Service agency', 'icon' => 'shield', 'desc' => 'Guard, field staff and supervisor cards.', 'terms' => $t_corp, 'subtypes' => array( array( 'guard', 'Guard', 'staff' ), array( 'field-staff', 'Field staff', 'staff' ), array( 'supervisor', 'Supervisor', 'staff' ) ) ),
			array( 'slug' => 'club', 'name' => 'Club / Gym / Society', 'icon' => 'users', 'desc' => 'Member, staff and resident cards for clubs, gyms and housing societies.', 'terms' => $t_gen, 'subtypes' => array( array( 'member', 'Member', 'staff' ), array( 'staff', 'Staff', 'staff' ), array( 'resident', 'Resident / Guest', 'other' ) ) ),
			array( 'slug' => 'press', 'name' => 'Press / Media house', 'icon' => 'mic', 'desc' => 'Reporter, staff and event passes for media houses.', 'terms' => $t_corp, 'subtypes' => array( array( 'reporter', 'Reporter', 'staff' ), array( 'staff', 'Staff', 'staff' ), array( 'event-pass', 'Event pass', 'other' ) ) ),
		);
	}

	/**
	 * Default the site time zone to Asia/Kolkata if it was never set.
	 */
	private static function set_timezone() {
		if ( '' === (string) get_option( 'timezone_string' ) && 0.0 === (float) get_option( 'gmt_offset' ) ) {
			update_option( 'timezone_string', 'Asia/Kolkata' );
		}
	}

	/**
	 * Create the legal and info pages Razorpay asks for. Never overwrites edited pages.
	 */
	private static function create_pages() {
		$pages   = get_option( 'pkc_pages', array() );
		$pages   = is_array( $pages ) ? $pages : array();
		$changed = false;

		foreach ( PKC_Default_Pages::all() as $key => $page ) {
			if ( ! empty( $pages[ $key ] ) && get_post_status( (int) $pages[ $key ] ) ) {
				continue;
			}
			$existing = get_page_by_path( $page['slug'] );
			if ( $existing ) {
				// Keep a page the owner already published; fill in drafts (like WordPress's sample privacy page).
				if ( 'publish' !== $existing->post_status ) {
					wp_update_post(
						array(
							'ID'           => $existing->ID,
							'post_status'  => 'publish',
							'post_title'   => $page['title'],
							'post_content' => $page['content'],
						)
					);
				}
				$pages[ $key ] = $existing->ID;
				$changed       = true;
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_content' => $page['content'],
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				$pages[ $key ] = $id;
				$changed       = true;
			}
		}
		if ( $changed ) {
			update_option( 'pkc_pages', $pages );
		}
		if ( ! empty( $pages['privacy'] ) && 'publish' === get_post_status( (int) $pages['privacy'] ) ) {
			update_option( 'wp_page_for_privacy_policy', (int) $pages['privacy'] );
		}
	}
}

require_once PKC_DIR . 'includes/class-default-pages.php';
