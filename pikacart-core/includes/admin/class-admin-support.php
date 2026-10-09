<?php
/**
 * Super Admin support inbox: live chat with every customer.
 * The screen is drawn by assets/js/admin/support.js.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Support {

	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
	}

	/** Unread chats in the WordPress top bar, on every admin page. */
	public static function admin_bar( $bar ) {
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			return;
		}
		$n = PKC_Support::admin_unread_count();
		$bar->add_node(
			array(
				'id'    => 'pkc-support',
				'title' => '<span class="ab-icon dashicons dashicons-format-chat" style="top:2px"></span><span class="ab-label">' . esc_html__( 'Support', 'pikacart' ) . ( $n ? ' <span class="pkc-ab-count" style="display:inline-block;min-width:18px;padding:0 6px;border-radius:9px;background:#d63638;color:#fff;font-size:11px;line-height:18px;text-align:center">' . (int) $n . '</span>' : '' ) . '</span>',
				'href'  => admin_url( 'admin.php?page=pikacart-support' ),
			)
		);
	}

	public static function assets() {
		if ( 'pikacart-support' !== PKC_Admin::is_pkc_screen() ) {
			return;
		}
		wp_enqueue_script( 'pkc-admin-support', PKC_URL . 'assets/js/admin/support.js', array( 'wp-i18n' ), PKC_VERSION, true );
		wp_set_script_translations( 'pkc-admin-support', 'pikacart', PKC_DIR . 'languages' );
		wp_add_inline_script(
			'pkc-admin-support',
			'window.PKC_SUPPORT = ' . wp_json_encode(
				array(
					'rest'   => esc_url_raw( rest_url( 'pkc/v1/admin/support/' ) ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
					'ticket' => isset( $_GET['ticket'] ) ? absint( $_GET['ticket'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
				)
			) . ';',
			'before'
		);
	}

	public static function render() {
		PKC_Admin::header( __( 'Support inbox', 'pikacart' ), __( 'Chat live with your customers. New messages appear automatically.', 'pikacart' ) );
		?>
		<div class="pkc-chat-app" id="pkc-chat-app">
			<aside class="pkc-chat-list">
				<div class="pkc-chat-tools">
					<input type="search" id="pkc-chat-search" placeholder="<?php esc_attr_e( 'Search name, email, mobile or subject', 'pikacart' ); ?>">
					<div class="pkc-chat-filters" role="tablist">
						<button type="button" data-filter="open" class="is-active"><?php esc_html_e( 'Open', 'pikacart' ); ?></button>
						<button type="button" data-filter="unread"><?php esc_html_e( 'Unread', 'pikacart' ); ?></button>
						<button type="button" data-filter="closed"><?php esc_html_e( 'Solved', 'pikacart' ); ?></button>
						<button type="button" data-filter="all"><?php esc_html_e( 'All', 'pikacart' ); ?></button>
					</div>
				</div>
				<div class="pkc-chat-items" id="pkc-chat-items"><p class="pkc-empty"><?php esc_html_e( 'Loading…', 'pikacart' ); ?></p></div>
			</aside>
			<section class="pkc-chat-thread" id="pkc-chat-thread">
				<div class="pkc-chat-placeholder">
					<span class="dashicons dashicons-format-chat"></span>
					<p><?php esc_html_e( 'Choose a conversation on the left to start chatting.', 'pikacart' ); ?></p>
				</div>
			</section>
		</div>
		<?php
		PKC_Admin::footer();
	}
}
