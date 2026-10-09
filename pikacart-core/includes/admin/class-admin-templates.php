<?php
/**
 * Template manager (list with thumbnails, filters, import/export) and the
 * full-screen visual template builder. Adding a design never needs code.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Admin_Templates {

	public static function init() {
		add_action( 'admin_action_pkc_builder', array( __CLASS__, 'builder' ) );
	}

	/** Catalogue data the admin scripts need (hidden categories included). */
	public static function catalog_config() {
		return array(
			'tree'     => PKC_Catalog::tree( true ),
			'sizes'    => PKC_Catalog::sizes(),
			'palettes' => PKC_Catalog::palettes( false ),
			'levels'   => PKC_Catalog::level_labels(),
		);
	}

	public static function builder_url( $template_id, $request_id = 0 ) {
		$args = array(
			'action' => 'pkc_builder',
			'id'     => (int) $template_id,
		);
		if ( $request_id ) {
			$args['request'] = (int) $request_id;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	public static function render() {
		PKC_Assets::print_config(
			'pkc-admin-templates',
			array(
				'catalog' => self::catalog_config(),
				'builder' => self::builder_url( 0 ),
			)
		);
		wp_enqueue_script( 'pkc-admin-templates' );
		$actions = '<button type="button" class="pkc-btn" data-import>' . esc_html__( 'Import JSON', 'pikacart' ) . '</button>'
			. '<button type="button" class="pkc-btn pkc-btn-primary" data-new>' . esc_html__( '+ New design', 'pikacart' ) . '</button>';
		PKC_Admin::header( __( 'Templates', 'pikacart' ), __( 'Every public design. Build new ones visually, duplicate, import or export JSON, publish and feature.', 'pikacart' ), $actions );
		?>
		<div class="pkc-panel pkc-filters" data-filters></div>
		<div class="pkc-tpl-grid" data-grid><p class="pkc-muted"><?php esc_html_e( 'Loading designs…', 'pikacart' ); ?></p></div>
		<div class="pkc-pager" data-pager></div>
		<input type="file" accept="application/json,.json" data-import-file hidden>
		<div class="toasts pkc" id="toasts" aria-live="polite"></div>
		<div id="modal-root" class="pkc"></div>
		<?php
		require PKC_DIR . 'templates/icons.php';
		PKC_Admin::footer();
	}

	/**
	 * Full-screen builder page (no WordPress admin chrome, so the editor has room).
	 */
	public static function builder() {
		if ( ! current_user_can( PKC_Roles::ADMIN_CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pikacart' ), 403 );
		}
		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$req_id  = isset( $_GET['request'] ) ? absint( $_GET['request'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$request = $req_id ? PKC_Designs::request( $req_id ) : null;
		if ( $request ) {
			$tpl = PKC_Designs::start_build( $request );
			$id  = (int) $tpl->id;
			$request = PKC_Designs::request( $req_id );
		}
		$tpl = PKC_Catalog::template( $id );
		if ( ! $tpl ) {
			wp_die( esc_html__( 'Design not found.', 'pikacart' ), 404 );
		}
		$org = $tpl->owner_org_id ? PKC_Organisations::get( $tpl->owner_org_id ) : null;

		PKC_Assets::register();
		PKC_Assets::print_config(
			'pkc-builder',
			array(
				'catalog'  => self::catalog_config(),
				'template' => PKC_Catalog::template_to_app( $tpl ),
				'org'      => $org ? PKC_Organisations::to_app( $org ) : null,
				'request'  => $request ? array_merge(
					PKC_Designs::request_to_app( $request ),
					array(
						'public_ok' => (bool) $request->public_ok,
						'org_name'  => $org ? $org->name : '',
					)
				) : null,
				'back'     => $request ? admin_url( 'admin.php?page=pikacart-requests' ) : admin_url( 'admin.php?page=pikacart-templates' ),
			)
		);

		$pkc_title  = __( 'Template builder', 'pikacart' );
		$pkc_styles = array( 'pkc-app', 'pkc-card-fonts', 'pkc-admin' );
		require PKC_DIR . 'templates/partials/head.php';
		?>
<body class="pkc pkc-builder-page">
		<?php require PKC_DIR . 'templates/icons.php'; ?>
<div id="builder" class="builder"><p class="pkc-muted" style="padding:24px"><?php esc_html_e( 'Loading the builder…', 'pikacart' ); ?></p></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<div id="modal-root"></div>
		<?php wp_print_scripts( array( 'pkc-qrcode', 'pkc-barcode', 'pkc-builder' ) ); ?>
</body>
</html>
		<?php
		exit;
	}
}
