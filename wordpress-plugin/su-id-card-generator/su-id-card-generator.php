<?php
/**
 * Plugin Name:       SU Election ID Card Generator
 * Description:       Students' Union Election ID card generator: college profiles, member list, Excel import, real QR codes and barcodes, single-card downloads and A4 print sheets. Adds an "ID Card Generator" page you can link from any menu.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       su-id-card-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUIDC_VERSION', '1.0.0' );
define( 'SUIDC_FILE', __FILE__ );
define( 'SUIDC_OPT_PAGE', 'suidc_page_id' );
define( 'SUIDC_OPT_LOGIN', 'suidc_require_login' );

/* -------------------------------------------------------------------------
 * The generator page
 * ---------------------------------------------------------------------- */

/**
 * Returns the ID of the generator page, creating it when it is missing.
 *
 * @return int Page ID, or 0 on failure.
 */
function suidc_ensure_page() {
	$id   = (int) get_option( SUIDC_OPT_PAGE );
	$page = $id ? get_post( $id ) : null;
	if ( $page && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
		if ( 'publish' !== $page->post_status ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		}
		return $id;
	}
	$id = wp_insert_post(
		array(
			'post_title'     => __( 'ID Card Generator', 'su-id-card-generator' ),
			'post_name'      => 'id-card-generator',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => '[id_card_generator]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_option( SUIDC_OPT_PAGE, (int) $id );
	return (int) $id;
}

/** URL of the generator page (the link to put in a menu). */
function suidc_page_url() {
	$id = (int) get_option( SUIDC_OPT_PAGE );
	return $id ? get_permalink( $id ) : '';
}

register_activation_hook(
	__FILE__,
	function () {
		suidc_ensure_page();
		set_transient( 'suidc_activated', 1, 60 );
	}
);

/**
 * The generator HTML, with asset paths pointing at this plugin and a link
 * back to the website.
 */
function suidc_app_html() {
	$file = plugin_dir_path( SUIDC_FILE ) . 'app/index.html';
	$html = file_exists( $file ) ? file_get_contents( $file ) : '';
	if ( '' === $html ) {
		return '<!doctype html><p>ID Card Generator files are missing. Please reinstall the plugin.</p>';
	}
	$base = plugins_url( 'app/', SUIDC_FILE );
	$html = preg_replace( '/<head>/', "<head>\n<base href=\"" . esc_url( $base ) . '">', $html, 1 );
	$back = '<a class="suidc-back" href="' . esc_url( home_url( '/' ) ) . '">&larr; ' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
	$html = preg_replace( '/<h1>/', $back . '<h1>', $html, 1 );
	$css  = '.suidc-back{color:#fff;text-decoration:none;font-size:13px;opacity:.85;white-space:nowrap}'
		. '.suidc-back:hover{opacity:1;text-decoration:underline}';
	$html = preg_replace( '#</style>#', $css . '</style>', $html, 1 );
	return $html;
}

// Serve the full-screen generator on its page, outside the theme so the
// theme's styles and scripts cannot interfere with the card drawing.
add_action(
	'template_redirect',
	function () {
		$id = (int) get_option( SUIDC_OPT_PAGE );
		if ( ! $id || ! is_page( $id ) ) {
			return;
		}
		if ( get_option( SUIDC_OPT_LOGIN ) && ! is_user_logged_in() ) {
			auth_redirect();
		}
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo suidc_app_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- static app file shipped with the plugin.
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Shortcodes
 * ---------------------------------------------------------------------- */

// [id_card_button text="Make ID Cards"] - a button linking to the generator.
add_shortcode(
	'id_card_button',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'text'   => __( 'ID Card Generator', 'su-id-card-generator' ),
				'newtab' => 'no',
			),
			$atts,
			'id_card_button'
		);
		$url = suidc_page_url();
		if ( ! $url ) {
			return '';
		}
		$target = 'yes' === strtolower( $atts['newtab'] ) ? ' target="_blank" rel="noopener"' : '';
		return '<a class="suidc-button" href="' . esc_url( $url ) . '"' . $target
			. ' style="display:inline-block;background:#F57B30;color:#fff;padding:12px 22px;border-radius:8px;font-weight:600;text-decoration:none;line-height:1.2">'
			. esc_html( $atts['text'] ) . '</a>';
	}
);

// [id_card_generator height="1400"] - the generator embedded inside any page.
add_shortcode(
	'id_card_generator',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'height' => '1400' ), $atts, 'id_card_generator' );
		$src  = plugins_url( 'app/index.html', SUIDC_FILE );
		return '<iframe class="suidc-frame" src="' . esc_url( $src ) . '" title="' . esc_attr__( 'ID Card Generator', 'su-id-card-generator' )
			. '" style="width:100%;height:' . absint( $atts['height'] ) . 'px;border:0" loading="lazy" allow="clipboard-write"></iframe>';
	}
);

/* -------------------------------------------------------------------------
 * Admin: settings page, menu helper, notices
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'ID Card Generator', 'su-id-card-generator' ),
			__( 'ID Card Generator', 'su-id-card-generator' ),
			'manage_options',
			'su-id-card-generator',
			'suidc_settings_page'
		);
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=su-id-card-generator' ) ) . '">' . esc_html__( 'Settings & link', 'su-id-card-generator' ) . '</a>' );
		$url = suidc_page_url();
		if ( $url ) {
			$links[] = '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'Open generator', 'su-id-card-generator' ) . '</a>';
		}
		return $links;
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! get_transient( 'suidc_activated' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		delete_transient( 'suidc_activated' );
		$url = suidc_page_url();
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'ID Card Generator is ready.', 'su-id-card-generator' ) . '</strong> '
			. esc_html__( 'Link:', 'su-id-card-generator' ) . ' <a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a> &nbsp; '
			. '<a href="' . esc_url( admin_url( 'options-general.php?page=su-id-card-generator' ) ) . '">' . esc_html__( 'Add it to a menu', 'su-id-card-generator' ) . ' &rarr;</a></p></div>';
	}
);

/** Handles the settings forms (login option, add to menu, recreate page). */
add_action(
	'admin_post_suidc_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'su-id-card-generator' ) );
		}
		check_admin_referer( 'suidc_save' );
		$do  = isset( $_POST['suidc_do'] ) ? sanitize_key( wp_unslash( $_POST['suidc_do'] ) ) : '';
		$msg = 'saved';

		if ( 'settings' === $do ) {
			update_option( SUIDC_OPT_LOGIN, empty( $_POST['suidc_require_login'] ) ? 0 : 1 );
		} elseif ( 'page' === $do ) {
			suidc_ensure_page();
			$msg = 'page';
		} elseif ( 'menu' === $do ) {
			$menu_id = isset( $_POST['suidc_menu'] ) ? absint( $_POST['suidc_menu'] ) : 0;
			$title   = isset( $_POST['suidc_title'] ) ? sanitize_text_field( wp_unslash( $_POST['suidc_title'] ) ) : '';
			$page_id = suidc_ensure_page();
			if ( $menu_id && $page_id && wp_get_nav_menu_object( $menu_id ) ) {
				$item = wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => '' !== $title ? $title : __( 'ID Card Generator', 'su-id-card-generator' ),
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $page_id,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
				$msg = is_wp_error( $item ) ? 'menu_error' : 'menu';
			} else {
				$msg = 'menu_error';
			}
		}
		wp_safe_redirect( add_query_arg( 'suidc_msg', $msg, admin_url( 'options-general.php?page=su-id-card-generator' ) ) );
		exit;
	}
);

function suidc_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$page_id = (int) get_option( SUIDC_OPT_PAGE );
	$page    = $page_id ? get_post( $page_id ) : null;
	$ok      = $page && 'trash' !== $page->post_status;
	$url     = $ok ? get_permalink( $page_id ) : '';
	$menus   = wp_get_nav_menus();
	$msg     = isset( $_GET['suidc_msg'] ) ? sanitize_key( wp_unslash( $_GET['suidc_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$notes   = array(
		'saved'      => array( 'success', __( 'Settings saved.', 'su-id-card-generator' ) ),
		'page'       => array( 'success', __( 'The generator page is ready.', 'su-id-card-generator' ) ),
		'menu'       => array( 'success', __( 'Link added to the menu. Check it on your website.', 'su-id-card-generator' ) ),
		'menu_error' => array( 'error', __( 'Could not add the link to that menu.', 'su-id-card-generator' ) ),
	);
	$form = function ( $do, $inner ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="suidc_save"><input type="hidden" name="suidc_do" value="' . esc_attr( $do ) . '">';
		wp_nonce_field( 'suidc_save' );
		echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts below.
		echo '</form>';
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SU Election ID Card Generator', 'su-id-card-generator' ); ?></h1>
		<?php if ( isset( $notes[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notes[ $msg ][0] ); ?> is-dismissible"><p><?php echo esc_html( $notes[ $msg ][1] ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( '1. Your generator link', 'su-id-card-generator' ); ?></h2>
		<?php if ( $ok ) : ?>
			<p>
				<input type="text" id="suidc-url" class="regular-text code" readonly value="<?php echo esc_attr( $url ); ?>" style="max-width:100%;width:520px">
				<button type="button" class="button" onclick="var i=document.getElementById('suidc-url');i.select();(navigator.clipboard?navigator.clipboard.writeText(i.value):document.execCommand('copy'));this.textContent='<?php echo esc_js( __( 'Copied!', 'su-id-card-generator' ) ); ?>';"><?php esc_html_e( 'Copy link', 'su-id-card-generator' ); ?></button>
				<a class="button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'Open generator', 'su-id-card-generator' ); ?></a>
			</p>
			<p class="description"><?php esc_html_e( 'This is the page "ID Card Generator". You can change its title or address (slug) under Pages like any other page.', 'su-id-card-generator' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'The generator page is missing (it may have been deleted).', 'su-id-card-generator' ); ?></p>
			<?php $form( 'page', get_submit_button( __( 'Create the page again', 'su-id-card-generator' ), 'primary', 'submit', false ) ); ?>
		<?php endif; ?>

		<h2><?php esc_html_e( '2. Put the link in a menu', 'su-id-card-generator' ); ?></h2>
		<?php if ( $ok && $menus ) : ?>
			<?php
			$opts = '';
			foreach ( $menus as $m ) {
				$opts .= '<option value="' . esc_attr( $m->term_id ) . '">' . esc_html( $m->name ) . '</option>';
			}
			$form(
				'menu',
				'<p><label>' . esc_html__( 'Menu', 'su-id-card-generator' ) . ' <select name="suidc_menu">' . $opts . '</select></label> &nbsp; '
				. '<label>' . esc_html__( 'Link text', 'su-id-card-generator' ) . ' <input type="text" name="suidc_title" value="' . esc_attr__( 'ID Card Generator', 'su-id-card-generator' ) . '"></label> &nbsp; '
				. get_submit_button( __( 'Add link to menu', 'su-id-card-generator' ), 'primary', 'submit', false ) . '</p>'
			);
			?>
		<?php endif; ?>
		<p class="description">
			<?php esc_html_e( 'Or do it by hand: Appearance → Menus → "Pages" box → tick "ID Card Generator" → Add to Menu → Save Menu. With a block theme: Appearance → Editor → Navigation → add a Page Link to "ID Card Generator". You can also paste the link above as a Custom Link.', 'su-id-card-generator' ); ?>
		</p>

		<h2><?php esc_html_e( '3. A button on any page (optional)', 'su-id-card-generator' ); ?></h2>
		<p><?php esc_html_e( 'Add this shortcode to any page or post to show an orange button that opens the generator:', 'su-id-card-generator' ); ?></p>
		<p><code>[id_card_button text="Make ID Cards"]</code> &nbsp; <code>[id_card_button text="Make ID Cards" newtab="yes"]</code></p>
		<p><?php esc_html_e( 'To show the whole generator inside a normal page instead, use:', 'su-id-card-generator' ); ?> <code>[id_card_generator height="1400"]</code></p>

		<h2><?php esc_html_e( '4. Access', 'su-id-card-generator' ); ?></h2>
		<?php
		$form(
			'settings',
			'<p><label><input type="checkbox" name="suidc_require_login" value="1"' . checked( (bool) get_option( SUIDC_OPT_LOGIN ), true, false ) . '> '
			. esc_html__( 'Only logged-in users can open the generator', 'su-id-card-generator' ) . '</label></p>'
			. get_submit_button( __( 'Save', 'su-id-card-generator' ), 'secondary', 'submit', false )
		);
		?>
		<p class="description"><?php esc_html_e( 'Card data (colleges, members, photos) is stored only in each user\'s own browser, not on this server. Users can move it with "Export backup" / "Import backup" inside the generator.', 'su-id-card-generator' ); ?></p>
	</div>
	<?php
}
