<?php
/**
 * "NaukriPatra Control": tabbed settings screen, Meta Key Scanner, approval queue, import/export.
 *
 * @package naukripatra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_menu_page( 'NaukriPatra Control', 'NaukriPatra', 'manage_options', 'naukripatra-control', 'nppro_render_control', 'dashicons-megaphone', 3 );
} );

function nppro_tabs() {
	return array( 'general' => 'General', 'home' => 'Homepage', 'tools' => 'Tools', 'locations' => 'Locations', 'lists' => 'Lists', 'ads' => 'Ads', 'seo' => 'SEO', 'footer' => 'Footer / Social', 'data' => 'Data' );
}

/** Field specs per tab: path, type, label, optional help/options. */
function nppro_fields( $tab ) {
	$f = array();
	switch ( $tab ) {
		case 'general':
			$f = array(
				array( 'brand_text', 'text', 'Brand text (H1 on home)' ),
				array( 'brand_logo_id', 'media', 'Logo (optional; text is used when empty)' ),
				array( 'tagline', 'text', 'Tagline' ),
				array( 'show_post_btn', 'check', 'Show Post Job button in header' ),
				array( 'post_role', 'select', 'Who may post jobs (always saved as draft)', array( 'any' => 'Any logged-in user', 'contributor' => 'Contributor and above', 'author' => 'Author and above', 'editor' => 'Editor and above', 'manage_options' => 'Administrators only' ) ),
				array( 'notify_email', 'text', 'Email for new submissions (empty = admin email)' ),
				array( 'form_rate', 'number', 'Max submissions per user per hour' ),
				array( 'dark_default', 'select', 'Default colour mode', array( 'system' => 'Follow visitor system', 'light' => 'Light', 'dark' => 'Dark' ) ),
				array( 'gp_base_css', 'check', 'Keep GeneratePress base CSS (off = faster)' ),
				array( 'rest_clean', 'check', 'App/REST: remove inline colours from article content so the app shows it clearly in dark mode (keys unchanged)' ),
				array( 'toc_mode', 'select', 'Article "Quick Links" box', array( 'end' => 'At the end of the article (recommended)', 'top' => 'At the top', 'off' => 'Hide' ) ),
				array( 'menu_force_cats', 'check', 'Menu items named Result / Admit Card / Latest Jobs etc. always open the category page' ),
				array( 'inline_css', 'check', 'Inline the theme CSS in <head> (faster first paint)' ),
				array( 'meta_prefix', 'text', 'Meta key prefix for plugin fields (optional, see Data > Meta Key Scanner)' ),
			);
			break;
		case 'home':
			$f[] = array( null, 'note', 'Show/hide and order each homepage block (lower number = higher on page).' );
			foreach ( array( 'ticker_jobs' => 'Latest Jobs ticker', 'tools' => 'Tools row', 'ticker_results' => 'Live Results ticker', 'cats' => 'Six category buttons', 'states' => 'State & UT grid', 'main' => 'Jobs table + boxes + sidebar', 'seo_text' => 'SEO text block' ) as $k => $l ) {
				$f[] = array( 'blocks.' . $k . '.show', 'check', $l . ' - show' );
				$f[] = array( 'blocks.' . $k . '.order', 'number', $l . ' - order' );
			}
			array_push(
				$f,
				array( 'ticker_jobs_label', 'text', 'Jobs ticker label' ), array( 'ticker_jobs_count', 'number', 'Jobs ticker items' ),
				array( 'ticker_results_label', 'text', 'Results ticker label' ), array( 'ticker_results_count', 'number', 'Results ticker items' ),
				array( 'ticker_results_answer', 'check', 'Include Answer Key posts in results ticker' ),
				array( 'ticker_speed', 'number', 'Ticker speed (seconds per loop; higher = slower)' ),
				array( 'home_jobs_count', 'number', 'Jobs in homepage table' ), array( 'home_box_count', 'number', 'Links per section box' ),
				array( 'trending_count', 'number', 'Trending jobs in sidebar' ), array( 'states_title', 'text', 'State grid title' ),
				array( 'seo_text', 'html', 'SEO text block (about site, FAQ)' )
			);
			foreach ( nppro_opt( 'cats' ) as $slug => $c ) {
				$f[] = array( 'cats.' . $slug . '.label', 'text', 'Button label: ' . $slug );
				$f[] = array( 'cats.' . $slug . '.color', 'color', 'Button colour: ' . $slug );
			}
			break;
		case 'locations':
			$f = array(
				array( null, 'note', 'Use state slugs, e.g. goa, delhi.' ),
				array( 'hidden_locations', 'textarea', 'Hidden states/UTs (comma separated slugs)' ),
				array( 'location_names', 'textarea', 'Rename (one per line: slug=New Name)' ),
				array( 'extra_locations', 'textarea', 'Extra location buttons (one per line: Label|URL; empty URL = search)' ),
			);
			break;
		case 'lists':
			$f = array(
				array( 'rows_per_page', 'number', 'Rows per page' ), array( 'new_badge_days', 'number', 'NEW badge days' ), array( 'soon_days', 'number', 'Orange warning when days left <=' ),
				array( 'col_advt', 'check', 'Show Adv No column' ), array( 'col_posts', 'check', 'Show No. of Posts column' ), array( 'live_filter', 'check', 'Show live filter box' ),
				array( 'color_soon', 'color', 'Colour: closing soon' ), array( 'color_expired', 'color', 'Colour: expired' ),
			);
			break;
		case 'ads':
			$f[] = array( null, 'note', 'Paste Adsterra code per slot. Code loads lazily after first interaction. Admins never see ads.' );
			foreach ( nppro_ad_slots() as $k => $s ) {
				$f[] = array( 'ads.' . $k . '.on', 'check', $s[0] . ' - on' );
				$f[] = array( 'ads.' . $k . '.device', 'select', $s[0] . ' - device', array( 'all' => 'All devices', 'desktop' => 'Desktop only', 'mobile' => 'Mobile only' ) );
				$f[] = array( 'ads.' . $k . '.code', 'code', $s[0] . ' - code (' . $s[1][0] . 'x' . $s[1][1] . ' desktop / ' . $s[2][0] . 'x' . $s[2][1] . ' mobile)' );
			}
			array_push( $f, array( 'ad_delay', 'number', 'Load delay in ms if no interaction' ), array( 'ad_max', 'number', 'Max ads per page (density)' ), array( 'ad_social_bar', 'code', 'Social Bar code (optional)' ), array( 'ad_popunder', 'code', 'Popunder code (optional)' ) );
			break;
		case 'seo':
			$f = array(
				array( 'seo_home_title', 'text', 'Home title' ), array( 'seo_home_desc', 'textarea', 'Home meta description' ), array( 'seo_og_image', 'text', 'Default OG image URL' ),
				array( 'noindex_search', 'check', 'noindex internal search' ), array( 'noindex_tags', 'check', 'noindex tag pages' ), array( 'robots_extra', 'textarea', 'Extra robots.txt lines' ),
			);
			break;
		case 'footer':
			$f = array(
				array( 'social.telegram', 'text', 'Telegram URL' ), array( 'social.whatsapp', 'text', 'WhatsApp URL' ), array( 'social.youtube', 'text', 'YouTube URL' ), array( 'social.playstore', 'text', 'Play Store URL' ),
				array( 'join_text', 'text', 'Sidebar join-card text' ), array( 'footer_states', 'check', 'Show state links in footer' ),
				array( 'disclaimer', 'textarea', 'Disclaimer' ), array( 'copyright', 'text', 'Copyright ({year} = current year)' ),
			);
			break;
	}
	return $f;
}

function nppro_path_get( $arr, $path ) {
	foreach ( explode( '.', $path ) as $p ) {
		if ( ! is_array( $arr ) || ! isset( $arr[ $p ] ) ) {
			return null;
		}
		$arr = $arr[ $p ];
	}
	return $arr;
}
function nppro_path_set( &$arr, $path, $val ) {
	$ref =& $arr;
	foreach ( explode( '.', $path ) as $p ) {
		if ( ! isset( $ref[ $p ] ) || ! is_array( $ref[ $p ] ) ) {
			$ref[ $p ] = isset( $ref[ $p ] ) ? $ref[ $p ] : array();
		}
		$ref =& $ref[ $p ];
	}
	$ref = $val;
}
function nppro_field_name( $path ) {
	return 'np[' . implode( '][', explode( '.', $path ) ) . ']';
}

function nppro_render_field( $f ) {
	list( $path, $type, $label ) = $f;
	if ( 'note' === $type ) {
		echo '<p class="description">' . esc_html( $label ) . '</p>';
		return;
	}
	$val  = nppro_path_get( nppro_opt_all(), $path );
	$name = nppro_field_name( $path );
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	switch ( $type ) {
		case 'check':
			printf( '<label><input type="checkbox" name="%s" value="1" %s> %s</label>', esc_attr( $name ), checked( ! empty( $val ), true, false ), esc_html__( 'Yes', 'naukripatra' ) );
			break;
		case 'select':
			echo '<select name="' . esc_attr( $name ) . '">';
			foreach ( $f[3] as $k => $l ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( (string) $val, (string) $k, false ), esc_html( $l ) );
			}
			echo '</select>';
			break;
		case 'number':
			printf( '<input type="number" class="small-text" name="%s" value="%s">', esc_attr( $name ), esc_attr( $val ) );
			break;
		case 'color':
			printf( '<input type="text" class="regular-text" name="%s" value="%s" placeholder="#1D4ED8">', esc_attr( $name ), esc_attr( $val ) );
			break;
		case 'textarea':
		case 'html':
		case 'code':
			printf( '<textarea class="large-text %s" rows="%d" name="%s">%s</textarea>', 'code' === $type ? 'code' : '', 'textarea' === $type ? 3 : 6, esc_attr( $name ), esc_textarea( $val ) );
			break;
		case 'media':
			printf( '<input type="number" class="small-text" name="%s" value="%s"> <span class="description">%s</span>', esc_attr( $name ), esc_attr( $val ), esc_html__( 'Media Library attachment ID', 'naukripatra' ) );
			break;
		default:
			printf( '<input type="text" class="large-text" name="%s" value="%s">', esc_attr( $name ), esc_attr( $val ) );
	}
	echo '</td></tr>';
}

function nppro_opt_all() {
	$all = array();
	foreach ( array_keys( nppro_default_settings() ) as $k ) {
		$all[ $k ] = nppro_opt( $k );
	}
	return $all;
}

function nppro_sanitize_value( $type, $v ) {
	$v = is_scalar( $v ) ? (string) $v : '';
	switch ( $type ) {
		case 'check':
			return empty( $v ) ? 0 : 1;
		case 'number':
		case 'media':
			return absint( $v );
		case 'color':
			return sanitize_hex_color( $v ) ? sanitize_hex_color( $v ) : '';
		case 'textarea':
			return sanitize_textarea_field( $v );
		case 'html':
			return wp_kses_post( $v );
		case 'code':
			return current_user_can( 'unfiltered_html' ) || ! is_multisite() ? (string) $v : '';
		case 'select':
			return sanitize_text_field( $v );
		default:
			return sanitize_text_field( $v );
	}
}

add_action( 'admin_post_np_save', function () {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'np_save' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'naukripatra' ), '', array( 'response' => 403 ) );
	}
	$tab   = isset( $_POST['np_tab'] ) ? sanitize_key( $_POST['np_tab'] ) : 'general';
	$saved = get_option( 'nppro_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$posted = isset( $_POST['np'] ) ? wp_unslash( $_POST['np'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( nppro_fields( $tab ) as $f ) {
		if ( null === $f[0] ) {
			continue;
		}
		$v = nppro_path_get( $posted, $f[0] );
		if ( 'select' === $f[1] && ! isset( $f[3][ $v ] ) ) {
			continue;
		}
		nppro_path_set( $saved, $f[0], nppro_sanitize_value( $f[1], is_array( $v ) ? '' : $v ) );
	}
	if ( 'tools' === $tab ) {
		$tools = array();
		$rows  = isset( $posted['tools'] ) && is_array( $posted['tools'] ) ? $posted['tools'] : array();
		foreach ( $rows as $r ) {
			if ( empty( $r['label'] ) ) {
				continue;
			}
			$tools[] = array(
				'label' => sanitize_text_field( $r['label'] ), 'link' => sanitize_text_field( isset( $r['link'] ) ? $r['link'] : '' ),
				'icon'  => sanitize_key( isset( $r['icon'] ) ? $r['icon'] : 'link' ), 'color' => sanitize_hex_color( isset( $r['color'] ) ? $r['color'] : '' ) ? sanitize_hex_color( $r['color'] ) : '#1D4ED8',
				'show'  => empty( $r['show'] ) ? 0 : 1,
			);
		}
		$saved['tools'] = $tools;
	}
	update_option( 'nppro_settings', $saved );
	nppro_opt_flush();
	wp_safe_redirect( admin_url( 'admin.php?page=naukripatra-control&tab=' . $tab . '&saved=1' ) );
	exit;
} );

add_action( 'admin_post_np_export', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'np_export' ) ) {
		wp_die( 'Not allowed.' );
	}
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename=naukripatra-settings.json' );
	echo wp_json_encode( get_option( 'nppro_settings', array() ), JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
} );

add_action( 'admin_post_np_import', function () {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'np_import' ) ) {
		wp_die( 'Not allowed.' );
	}
	$data = json_decode( wp_unslash( isset( $_POST['np_json'] ) ? $_POST['np_json'] : '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( is_array( $data ) ) {
		$allowed = nppro_default_settings();
		update_option( 'nppro_settings', array_intersect_key( $data, $allowed ) );
		nppro_opt_flush();
	}
	wp_safe_redirect( admin_url( 'admin.php?page=naukripatra-control&tab=data&saved=1' ) );
	exit;
} );

function nppro_render_control() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = nppro_tabs();
	$tab  = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap"><h1>NaukriPatra Control</h1>';
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $l ) {
		printf( '<a class="nav-tab %s" href="%s">%s</a>', $k === $tab ? 'nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=naukripatra-control&tab=' . $k ) ), esc_html( $l ) );
	}
	echo '</nav>';
	if ( 'data' === $tab ) {
		nppro_render_data_tab();
	} else {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="np_save"><input type="hidden" name="np_tab" value="' . esc_attr( $tab ) . '">';
		wp_nonce_field( 'np_save' );
		echo '<table class="form-table" role="presentation">';
		foreach ( nppro_fields( $tab ) as $f ) {
			nppro_render_field( $f );
		}
		echo '</table>';
		if ( 'tools' === $tab ) {
			nppro_render_tools_repeater();
		}
		submit_button();
		echo '</form>';
	}
	echo '</div>';
}

function nppro_render_tools_repeater() {
	$tools = (array) nppro_opt( 'tools' );
	$tools[] = array( 'label' => '', 'link' => '', 'icon' => 'link', 'color' => '#1D4ED8', 'show' => 1 );
	$tools[] = array( 'label' => '', 'link' => '', 'icon' => 'link', 'color' => '#1D4ED8', 'show' => 1 );
	echo '<p class="description">Tools: name, link (relative or full URL), icon, colour. Leave name empty to remove a row. Two empty rows are provided for new tools.</p><table class="widefat striped"><thead><tr><th>Name</th><th>Link</th><th>Icon</th><th>Colour</th><th>Show</th></tr></thead><tbody>';
	foreach ( $tools as $i => $t ) {
		$n = 'np[tools][' . $i . ']';
		echo '<tr><td><input type="text" name="' . esc_attr( $n ) . '[label]" value="' . esc_attr( $t['label'] ) . '"></td>';
		echo '<td><input type="text" name="' . esc_attr( $n ) . '[link]" value="' . esc_attr( $t['link'] ) . '"></td><td><select name="' . esc_attr( $n ) . '[icon]">';
		foreach ( array( 'resume', 'photo', 'signature', 'link', 'star', 'calc', 'doc' ) as $ic ) {
			printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $ic ), selected( $t['icon'], $ic, false ) );
		}
		echo '</select></td><td><input type="text" class="small-text" name="' . esc_attr( $n ) . '[color]" value="' . esc_attr( $t['color'] ) . '"></td>';
		echo '<td><input type="checkbox" name="' . esc_attr( $n ) . '[show]" value="1" ' . checked( ! empty( $t['show'] ), true, false ) . '></td></tr>';
	}
	echo '</tbody></table>';
}

function nppro_render_data_tab() {
	// Meta Key Scanner.
	echo '<h2>Meta Key Scanner</h2>';
	$p = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish' ) );
	if ( $p ) {
		$id = $p[0]->ID;
		echo '<p>Latest post: <strong>' . esc_html( get_the_title( $id ) ) . '</strong>. Raw keys found:</p><table class="widefat striped"><thead><tr><th>Meta key</th><th>Value (first 80 chars)</th></tr></thead><tbody>';
		foreach ( get_post_meta( $id ) as $k => $v ) {
			echo '<tr><td><code>' . esc_html( $k ) . '</code></td><td>' . esc_html( mb_substr( is_scalar( $v[0] ) ? (string) $v[0] : '(array)', 0, 80 ) ) . '</td></tr>';
		}
		echo '</tbody></table><h3>How the theme resolves each field</h3><table class="widefat striped"><thead><tr><th>Field</th><th>Value used</th><th>Source</th></tr></thead><tbody>';
		foreach ( array_merge( nppro_api_fields(), array( 'advt_no' ) ) as $f ) {
			$v   = nppro_meta( $id, $f );
			$src = '';
			if ( '' !== $v ) {
				$src = ( '' !== nppro_meta( $id, $f, false ) ) ? 'meta' : 'overview table / title';
			}
			echo '<tr><td><code>' . esc_html( $f ) . '</code></td><td>' . esc_html( mb_substr( $v, 0, 80 ) ) . '</td><td>' . esc_html( $src ? $src : 'not found' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No published posts yet.</p>';
	}
	// Approval queue.
	echo '<h2>Approval queue</h2>';
	$q = get_posts( array( 'post_status' => 'draft', 'meta_key' => '_np_front_submission', 'meta_value' => '1', 'numberposts' => 50 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( ! $q ) {
		echo '<p>No submissions waiting.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Title</th><th>By</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
		foreach ( $q as $post ) {
			$nonce = wp_create_nonce( 'np_review_' . $post->ID );
			$link  = function ( $do ) use ( $post, $nonce ) {
				return esc_url( admin_url( 'admin-post.php?action=np_review&do=' . $do . '&post=' . $post->ID . '&_wpnonce=' . $nonce ) );
			};
			echo '<tr><td>' . esc_html( $post->post_title ) . '</td><td>' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</td><td>' . esc_html( get_the_date( '', $post ) ) . '</td><td>';
			echo '<a href="' . esc_url( get_preview_post_link( $post ) ) . '" target="_blank" rel="noopener">Preview</a> | <a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Edit</a> | <a href="' . $link( 'approve' ) . '"><strong>Approve and Publish</strong></a> | <a href="' . $link( 'reject' ) . '" onclick="return confirm(\'Reject and trash?\')">Reject</a></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table>';
	}
	// Import / export.
	echo '<h2>Import / export settings</h2><p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=np_export' ), 'np_export' ) ) . '">Export JSON</a></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="np_import">';
	wp_nonce_field( 'np_import' );
	echo '<textarea name="np_json" rows="6" class="large-text code" placeholder="Paste exported JSON"></textarea>';
	submit_button( 'Import', 'secondary' );
	echo '</form>';
}
