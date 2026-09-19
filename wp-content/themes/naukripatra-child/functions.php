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
