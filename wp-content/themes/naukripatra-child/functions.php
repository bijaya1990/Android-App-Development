<?php
/**
 * Naukripatra Skin — GeneratePress child theme (LOOK ONLY).
 *
 * WHAT THIS DOES
 *   1. Loads the parent (GeneratePress) styles, then this theme's style.css.
 *   2. Adds the body class `np-skin`, which every rule in style.css is scoped
 *      to. That is how the skin wins over CSS already saved in
 *      Appearance > Customize > Additional CSS, without editing it.
 *   3. On single posts only, loads a tiny script that ADDS CSS CLASSES to the
 *      markup WordPress already printed (and wraps wide tables in a scroll
 *      box) so the Naukri.com-style job layout can be done in CSS.
 *
 * WHAT THIS DOES NOT DO — by design
 *   - No custom post type, no custom fields, no taxonomy, no database table.
 *   - No rewrite rule, no permalink change, no redirect, no slug change.
 *   - No change to the REST API (/wp-json), sitemaps, canonical tags, schema
 *     or any SEO plugin output.
 *   - No parent theme file is edited. No plugin is touched.
 *   - No post content is edited or deleted. The script only re-labels and
 *     re-orders nothing: text stays in the DOM in the same reading order.
 *
 * ROLLBACK
 *   Appearance > Themes > activate GeneratePress. Instant, nothing lost.
 *   Or switch off one piece from wp-config.php:
 *     define( 'NP_SKIN', false );            // whole skin off
 *     define( 'NP_SKIN_FONTS', false );      // keep current fonts
 *     define( 'NP_SKIN_POST_JS', false );    // no FAQ accordion / apply bar
 *     define( 'NP_SKIN_FAQ', false );        // FAQ stays plain headings
 *     define( 'NP_SKIN_APPLY_BAR', false );  // no sticky mobile apply bar
 *
 * @package Naukripatra_Skin
 */

defined( 'ABSPATH' ) || exit;

define( 'NP_SKIN_VERSION', '1.0.0' );

/**
 * Read a switch. Constants defined in wp-config.php win.
 *
 * @param string $name    Constant name.
 * @param bool   $default Default value.
 * @return bool
 */
function np_skin_on( $name = 'NP_SKIN', $default = true ) {
	$value = defined( $name ) ? constant( $name ) : $default;

	// The master switch turns everything off.
	if ( 'NP_SKIN' !== $name && defined( 'NP_SKIN' ) && ! NP_SKIN ) {
		return false;
	}

	return (bool) apply_filters( 'np_skin_on', (bool) $value, $name );
}

/**
 * Add the scoping class to <body>.
 *
 * Every rule in style.css starts with `.np-skin`, so removing this one class
 * disables the entire skin without removing the stylesheet.
 *
 * @param array $classes Body classes.
 * @return array
 */
function np_skin_body_class( $classes ) {
	if ( np_skin_on() ) {
		$classes[] = 'np-skin';
	}

	return $classes;
}
add_filter( 'body_class', 'np_skin_body_class' );

/**
 * Enqueue styles and, on single posts, the class-adding script.
 */
function np_skin_assets() {
	if ( ! np_skin_on() ) {
		return;
	}

	// Fonts: Poppins for headings, Inter for body. display=swap so text is
	// never invisible while the font loads (no render blocking, no CLS).
	if ( np_skin_on( 'NP_SKIN_FONTS' ) ) {
		wp_enqueue_style(
			'np-skin-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap',
			array(),
			null
		);
	}

	wp_enqueue_style(
		'np-skin',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'generate-style' ),
		NP_SKIN_VERSION
	);

	if ( is_single() && np_skin_on( 'NP_SKIN_POST_JS' ) ) {
		wp_enqueue_script(
			'np-skin-post',
			get_stylesheet_directory_uri() . '/assets/js/skin.js',
			array(),
			NP_SKIN_VERSION,
			true // in the footer, after the content it decorates.
		);

		wp_localize_script(
			'np-skin-post',
			'npSkinCfg',
			array(
				'faq'      => np_skin_on( 'NP_SKIN_FAQ' ),
				'applyBar' => np_skin_on( 'NP_SKIN_APPLY_BAR' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'np_skin_assets', 20 );

/**
 * Speed up the font request (preconnect), matching the enqueue above.
 *
 * @param array  $urls          URLs to print.
 * @param string $relation_type Relation type.
 * @return array
 */
function np_skin_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type && np_skin_on() && np_skin_on( 'NP_SKIN_FONTS' ) ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'np_skin_resource_hints', 10, 2 );

/**
 * Reminder in wp-admin if the parent theme is missing.
 */
function np_skin_parent_notice() {
	if ( ! current_user_can( 'manage_options' ) || wp_get_theme( 'generatepress' )->exists() ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'Naukripatra Skin:', 'naukripatra-skin' ),
		esc_html__( 'GeneratePress is not installed. Install the free GeneratePress theme — this skin is its child.', 'naukripatra-skin' )
	);
}
add_action( 'admin_notices', 'np_skin_parent_notice' );

/**
 * SETTINGS SAFETY NET — the one real risk of activating any new theme.
 *
 * WordPress stores Customizer settings PER THEME, in the option
 * `theme_mods_<theme-folder>`. So the moment you activate a different theme,
 * these look "gone" even though nothing was deleted:
 *   - site logo,
 *   - menu locations (which menu shows in the header / slide-out),
 *   - Appearance > Customize > Additional CSS,
 *   - GeneratePress Customizer layout choices.
 *
 * They are all still sitting safely under the OLD theme's option, and come
 * back the instant you switch back. But to avoid even that momentary scare,
 * this copies the previous theme's settings across on first activation.
 *
 * It only COPIES. The old theme's settings are never modified or removed, so
 * switching back to GeneratePress still restores the exact current site.
 * It also runs only once — if this theme already has settings, it does nothing.
 *
 * @param string   $old_name  Name of the previously active theme.
 * @param WP_Theme $old_theme The previously active theme object.
 */
function np_skin_inherit_settings( $old_name = '', $old_theme = null ) {
	$current = get_stylesheet();

	// Already configured? Leave it alone.
	$existing = get_option( 'theme_mods_' . $current );
	if ( ! empty( $existing ) && is_array( $existing ) ) {
		return;
	}

	$previous = ( $old_theme instanceof WP_Theme ) ? $old_theme->get_stylesheet() : '';
	$mods     = $previous ? get_option( 'theme_mods_' . $previous ) : array();

	// Fall back to the parent theme's settings.
	if ( empty( $mods ) || ! is_array( $mods ) ) {
		$mods     = get_option( 'theme_mods_generatepress' );
		$previous = 'generatepress';
	}

	if ( empty( $mods ) || ! is_array( $mods ) ) {
		return;
	}

	$mods['np_skin_inherited_from'] = $previous;
	update_option( 'theme_mods_' . $current, $mods );
	update_option( 'np_skin_inherited', $previous );
}
add_action( 'after_switch_theme', 'np_skin_inherit_settings', 10, 2 );

/**
 * Tell the admin what was inherited, once.
 */
function np_skin_inherited_notice() {
	$from = get_option( 'np_skin_inherited' );
	if ( ! $from || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_user_meta( get_current_user_id(), 'np_skin_notice_seen', true ) ) {
		return;
	}

	printf(
		'<div class="notice notice-success"><p><strong>%s</strong> %s <code>%s</code>. <a href="%s">%s</a></p></div>',
		esc_html__( 'Naukripatra Skin active.', 'naukripatra-skin' ),
		esc_html__( 'Your logo, menu locations and Additional CSS were copied over from', 'naukripatra-skin' ),
		esc_html( $from ),
		esc_url( wp_nonce_url( add_query_arg( 'np_skin_seen', 1 ), 'np_skin_seen' ) ),
		esc_html__( 'Got it', 'naukripatra-skin' )
	);
}
add_action( 'admin_notices', 'np_skin_inherited_notice' );

/**
 * Dismiss the notice above.
 */
function np_skin_dismiss_notice() {
	if ( ! isset( $_GET['np_skin_seen'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'np_skin_seen' );
	update_user_meta( get_current_user_id(), 'np_skin_notice_seen', 1 );
}
add_action( 'admin_init', 'np_skin_dismiss_notice' );
