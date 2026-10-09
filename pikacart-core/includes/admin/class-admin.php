<?php
/**
 * Super Admin control room inside wp-admin: menu, assets and shared layout.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PKC_FILE ), array( __CLASS__, 'action_links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'setup_notice' ) );
		PKC_Admin_Accounts::init();
		PKC_Admin_Payments::init();
		PKC_Admin_Support::init();
		PKC_Admin_Settings::init();
		PKC_Admin_Templates::init();
		PKC_Admin_Requests::init();
		PKC_Admin_Catalog::init();
		PKC_Admin_Finance::init();
		PKC_Admin_Notices::init();
		PKC_Admin_Reports::init();
		PKC_Admin_Activity::init();
	}

	public static function pages() {
		return array(
			'pikacart'          => array( __( 'Dashboard', 'pikacart' ), array( 'PKC_Admin_Dashboard', 'render' ) ),
			'pikacart-accounts' => array( __( 'Accounts', 'pikacart' ), array( 'PKC_Admin_Accounts', 'render' ) ),
			'pikacart-payments'  => array( __( 'Payments', 'pikacart' ), array( 'PKC_Admin_Payments', 'render' ) ),
			'pikacart-finance'   => array( __( 'Finance', 'pikacart' ), array( 'PKC_Admin_Finance', 'render' ) ),
			'pikacart-templates' => array( __( 'Templates', 'pikacart' ), array( 'PKC_Admin_Templates', 'render' ) ),
			'pikacart-requests'  => array( __( 'Design requests', 'pikacart' ), array( 'PKC_Admin_Requests', 'render' ) ),
			'pikacart-catalog'   => array( __( 'Categories & sizes', 'pikacart' ), array( 'PKC_Admin_Catalog', 'render' ) ),
			'pikacart-support'   => array( __( 'Support', 'pikacart' ), array( 'PKC_Admin_Support', 'render' ) ),
			'pikacart-notices'   => array( __( 'Notices', 'pikacart' ), array( 'PKC_Admin_Notices', 'render' ) ),
			'pikacart-reports'   => array( __( 'Reports', 'pikacart' ), array( 'PKC_Admin_Reports', 'render' ) ),
			'pikacart-activity'  => array( __( 'Activity log', 'pikacart' ), array( 'PKC_Admin_Activity', 'render' ) ),
			'pikacart-settings'  => array( __( 'Settings', 'pikacart' ), array( 'PKC_Admin_Settings', 'render' ) ),
		);
	}

	public static function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M5 1h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2zm3 2v1h4V3zm2 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM6 14v1.5h8V14a4 4 0 0 0-8 0z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$count = PKC_Support::admin_unread_count() + PKC_Designs::pending_count() + PKC_Admin_Reports::open_count();
		add_menu_page( __( 'Pikacart', 'pikacart' ), __( 'Pikacart', 'pikacart' ) . ( $count ? ' <span class="awaiting-mod">' . (int) $count . '</span>' : '' ), PKC_Roles::ADMIN_CAP, 'pikacart', array( 'PKC_Admin_Dashboard', 'render' ), $icon, 3 );
		$unread = PKC_Support::admin_unread_count();
		foreach ( self::pages() as $slug => $page ) {
			$label = $page[0];
			$badge = 0;
			if ( 'pikacart-support' === $slug ) {
				$badge = $unread;
			} elseif ( 'pikacart-requests' === $slug ) {
				$badge = PKC_Designs::pending_count();
			} elseif ( 'pikacart-reports' === $slug ) {
				$badge = PKC_Admin_Reports::open_count();
			}
			if ( $badge ) {
				$label .= ' <span class="awaiting-mod">' . (int) $badge . '</span>';
			}
			add_submenu_page( 'pikacart', $page[0] . ' · Pikacart', $label, PKC_Roles::ADMIN_CAP, $slug, $page[1] );
		}
	}

	public static function is_pkc_screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		return array_key_exists( $page, self::pages() ) ? $page : '';
	}

	public static function body_class( $classes ) {
		return self::is_pkc_screen() ? $classes . ' pkc-admin-screen' : $classes;
	}

	public static function assets() {
		$page = self::is_pkc_screen();
		if ( ! $page ) {
			return;
		}
		PKC_Assets::register();
		wp_enqueue_style( 'pkc-admin' );
		wp_enqueue_script( 'pkc-admin' );
		if ( 'pikacart' === $page ) {
			wp_enqueue_script( 'pkc-chart' );
		}
		if ( 'pikacart-templates' === $page ) {
			wp_enqueue_style( 'pkc-card-fonts' );
		}
		if ( 'pikacart-settings' === $page ) {
			wp_enqueue_media();
		}
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=pikacart-settings' ) ) . '">' . esc_html__( 'Settings', 'pikacart' ) . '</a>' );
		return $links;
	}

	/**
	 * Remind the owner to finish the Razorpay setup.
	 */
	public static function setup_notice() {
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) || PKC_Razorpay::configured() ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ( 'dashboard' !== $screen->id && ! self::is_pkc_screen() ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . wp_kses(
			sprintf(
				/* translators: %s: settings link */
				__( '<strong>Pikacart:</strong> add your Razorpay keys so customers can pay. <a href="%s">Open Razorpay settings</a>', 'pikacart' ),
				esc_url( admin_url( 'admin.php?page=pikacart-settings&tab=razorpay' ) )
			),
			array(
				'strong' => array(),
				'a'      => array( 'href' => array() ),
			)
		) . '</p></div>';
	}

	/**
	 * Shared page header with tabs between Pikacart screens.
	 */
	public static function header( $title, $subtitle = '', $actions = '' ) {
		$current = self::is_pkc_screen();
		?>
		<div class="pkc-admin">
		<div class="pkc-ad-top">
			<div class="pkc-ad-brand"><?php echo pkc_logo_html( false ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="pkc-ad-badge"><?php esc_html_e( 'Control room', 'pikacart' ); ?></span></div>
			<nav class="pkc-ad-nav">
				<?php foreach ( self::pages() as $slug => $page ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="<?php echo $slug === $current ? 'is-active' : ''; ?>"><?php echo esc_html( $page[0] ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'pikacart' ); ?> ↗</a>
			</nav>
		</div>
		<div class="pkc-ad-head">
			<div>
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( $subtitle ) : ?>
					<p class="pkc-ad-sub"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $actions ) : ?>
				<div class="pkc-ad-actions"><?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput -- Built by callers with escaped parts. ?></div>
			<?php endif; ?>
		</div>
		<?php self::flash(); ?>
		<?php
	}

	public static function footer() {
		echo '</div>';
	}

	/** One-time message after a form action. */
	public static function set_flash( $message, $type = 'success' ) {
		set_transient(
			'pkc_flash_' . get_current_user_id(),
			array(
				'message' => $message,
				'type'    => $type,
			),
			60
		);
	}

	private static function flash() {
		$key   = 'pkc_flash_' . get_current_user_id();
		$flash = get_transient( $key );
		if ( $flash ) {
			delete_transient( $key );
			printf( '<div class="pkc-flash pkc-flash-%s" role="status">%s</div>', esc_attr( $flash['type'] ), esc_html( $flash['message'] ) );
		}
	}

	/**
	 * Check capability and nonce for admin-post actions.
	 */
	public static function guard( $action ) {
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pikacart' ), 403 );
		}
		check_admin_referer( $action );
	}

	public static function status_badge( $status ) {
		return '<span class="pkc-badge pkc-badge-' . esc_attr( $status ) . '">' . esc_html( PKC_Access::label( $status ) ) . '</span>';
	}

	public static function pay_badge( $status ) {
		$labels = array(
			'captured' => __( 'Paid', 'pikacart' ),
			'failed'   => __( 'Failed', 'pikacart' ),
			'refunded' => __( 'Refunded', 'pikacart' ),
			'created'  => __( 'Not completed', 'pikacart' ),
		);
		return '<span class="pkc-badge pkc-pay-' . esc_attr( $status ) . '">' . esc_html( $labels[ $status ] ?? $status ) . '</span>';
	}

	/**
	 * Simple pagination links.
	 */
	public static function pagination( $total, $per_page, $paged, $base_args ) {
		$pages = (int) ceil( $total / $per_page );
		if ( $pages < 2 ) {
			return;
		}
		echo '<div class="pkc-pager">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			if ( $pages > 10 && $i > 2 && $i < $pages - 1 && abs( $i - $paged ) > 2 ) {
				if ( 3 === $i || $pages - 2 === $i ) {
					echo '<span>…</span>';
				}
				continue;
			}
			$url = add_query_arg( array_merge( $base_args, array( 'paged' => $i ) ), admin_url( 'admin.php' ) );
			printf( '<a href="%s" class="%s">%d</a>', esc_url( $url ), $i === $paged ? 'is-active' : '', (int) $i );
		}
		echo '</div>';
	}

	/**
	 * Send rows as a CSV file that opens in Excel (UTF-8 with BOM).
	 */
	public static function send_csv( $filename, $header, $rows ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, $header );
		foreach ( $rows as $row ) {
			// Stop spreadsheet formula injection.
			$row = array_map(
				function ( $v ) {
					$v = (string) $v;
					return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@' ), true ) ) ? "'" . $v : $v;
				},
				$row
			);
			fputcsv( $out, $row );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
