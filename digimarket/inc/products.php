<?php
/**
 * Products: post type, taxonomies, statuses, saving, catalogue queries.
 *
 * @package DigiMarket
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'dm_register_content_types', 5 );
function dm_register_content_types() {
	register_post_type(
		'dm_product',
		array(
			'labels'          => array(
				'name'          => __( 'Products', 'digimarket' ),
				'singular_name' => __( 'Product', 'digimarket' ),
				'add_new_item'  => __( 'Add product', 'digimarket' ),
				'edit_item'     => __( 'Edit product', 'digimarket' ),
				'all_items'     => __( 'All products', 'digimarket' ),
				'search_items'  => __( 'Search products', 'digimarket' ),
			),
			'public'          => true,
			'has_archive'     => 'products',
			'rewrite'         => array( 'slug' => 'product', 'with_front' => false ),
			'menu_icon'       => 'dashicons-download',
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
			'show_in_rest'    => true,
			'show_in_menu'    => 'dm-marketplace',
			'capability_type' => 'post',
		)
	);

	register_taxonomy(
		'dm_category',
		'dm_product',
		array(
			'labels'            => array( 'name' => __( 'Product categories', 'digimarket' ), 'singular_name' => __( 'Category', 'digimarket' ) ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'category-products', 'with_front' => false ),
		)
	);
	register_taxonomy(
		'dm_tag',
		'dm_product',
		array(
			'labels'            => array( 'name' => __( 'Product tags', 'digimarket' ), 'singular_name' => __( 'Tag', 'digimarket' ) ),
			'hierarchical'      => false,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'product-tag', 'with_front' => false ),
		)
	);

	register_post_status(
		'dm_unpublished',
		array(
			'label'                     => __( 'Unpublished', 'digimarket' ),
			'public'                    => false,
			'internal'                  => false,
			'protected'                 => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s count */
			'label_count'               => _n_noop( 'Unpublished <span class="count">(%s)</span>', 'Unpublished <span class="count">(%s)</span>', 'digimarket' ),
		)
	);
	register_post_status(
		'dm_deleted',
		array(
			'label'                     => __( 'Deleted by seller', 'digimarket' ),
			'public'                    => false,
			'internal'                  => false,
			'protected'                 => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			/* translators: %s count */
			'label_count'               => _n_noop( 'Deleted <span class="count">(%s)</span>', 'Deleted <span class="count">(%s)</span>', 'digimarket' ),
		)
	);
}

/* -------------------------------------------------------------------------
 * Private storage
 * ---------------------------------------------------------------------- */

function dm_private_dir() {
	$key = get_option( 'dm_private_key' );
	if ( ! $key ) {
		$key = wp_generate_password( 20, false );
		update_option( 'dm_private_key', $key, false );
	}
	$up  = wp_upload_dir( null, false );
	$dir = trailingslashit( $up['basedir'] ) . 'dm-private-' . strtolower( $key );
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		@file_put_contents( $dir . '/.htaccess', "# Deny direct access to purchased files\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore
		@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
		@file_put_contents( $dir . '/web.config', '<?xml version="1.0"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' ); // phpcs:ignore
	}
	return $dir;
}

function dm_allowed_extensions() {
	return array_filter( array_map( 'trim', explode( ',', strtolower( (string) dm_opt( 'allowed_extensions' ) ) ) ) );
}

/**
 * Store an uploaded digital file privately. Returns relative filename or WP_Error.
 */
function dm_store_private_file( $file ) {
	if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
		return new WP_Error( 'upload', __( 'The file failed to upload. Check the size limit and try again.', 'digimarket' ) );
	}
	$name = sanitize_file_name( wp_basename( $file['name'] ) );
	$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! $ext || ! in_array( $ext, dm_allowed_extensions(), true ) ) {
		return new WP_Error( 'type', sprintf( /* translators: %s ext */ __( 'Files of type .%s are not allowed.', 'digimarket' ), $ext ) );
	}
	if ( in_array( $ext, array( 'php', 'phtml', 'phar', 'php5', 'php7', 'pht', 'cgi', 'pl', 'asp', 'aspx', 'jsp', 'sh', 'exe', 'htaccess' ), true ) ) {
		return new WP_Error( 'type', __( 'Executable files are not allowed.', 'digimarket' ) );
	}
	$max = (float) dm_opt( 'max_upload_mb', 200 ) * MB_IN_BYTES;
	if ( $file['size'] > $max ) {
		return new WP_Error( 'size', sprintf( /* translators: %s MB */ __( 'File is larger than the %s MB limit.', 'digimarket' ), dm_opt( 'max_upload_mb' ) ) );
	}
	$stored = wp_generate_password( 24, false ) . '.' . $ext . '.bin';
	$dest   = dm_private_dir() . '/' . $stored;
	$moved  = is_uploaded_file( $file['tmp_name'] ) ? move_uploaded_file( $file['tmp_name'], $dest ) : @rename( $file['tmp_name'], $dest ); // phpcs:ignore
	if ( ! $moved ) {
		return new WP_Error( 'move', __( 'Could not save the file on the server.', 'digimarket' ) );
	}
	@chmod( $dest, 0640 ); // phpcs:ignore
	return array( 'stored' => $stored, 'name' => $name, 'size' => (int) $file['size'] );
}

/**
 * Upload an image into the media library, owned by $owner.
 */
function dm_upload_image( $field, $owner, $index = null, $parent = 0 ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	if ( null !== $index ) {
		if ( empty( $_FILES[ $field ]['name'][ $index ] ) ) {
			return 0;
		}
		$single = array(
			'name'     => $_FILES[ $field ]['name'][ $index ],
			'type'     => $_FILES[ $field ]['type'][ $index ],
			'tmp_name' => $_FILES[ $field ]['tmp_name'][ $index ],
			'error'    => $_FILES[ $field ]['error'][ $index ],
			'size'     => $_FILES[ $field ]['size'][ $index ],
		);
		$key              = $field . '_single_' . $index;
		$_FILES[ $key ]   = $single;
		$field            = $key;
	}
	if ( empty( $_FILES[ $field ]['name'] ) ) {
		return 0;
	}
	if ( $_FILES[ $field ]['size'] > 10 * MB_IN_BYTES ) {
		return new WP_Error( 'size', __( 'Images must be under 10 MB.', 'digimarket' ) );
	}
	$check = wp_check_filetype_and_ext( $_FILES[ $field ]['tmp_name'], $_FILES[ $field ]['name'] );
	if ( empty( $check['type'] ) || 0 !== strpos( $check['type'], 'image/' ) || 'image/svg+xml' === $check['type'] ) {
		return new WP_Error( 'type', __( 'Please upload a JPG, PNG, GIF or WebP image.', 'digimarket' ) );
	}
	$id = media_handle_upload( $field, $parent, array( 'post_author' => $owner ), array( 'test_form' => false ) );
	return $id;
}

/* -------------------------------------------------------------------------
 * Saving a product (shared by seller dashboard and wp-admin)
 * ---------------------------------------------------------------------- */

/**
 * Create or update a product.
 *
 * @param int   $pid     0 for new.
 * @param int   $owner   Seller user id.
 * @param array $data    Unslashed $_POST.
 * @return int|WP_Error
 */
function dm_save_product( $pid, $owner, $data ) {
	$errors = new WP_Error();
	$title  = sanitize_text_field( $data['title'] ?? '' );
	if ( '' === $title ) {
		$errors->add( 'title', __( 'Title is required.', 'digimarket' ) );
	}
	if ( mb_strlen( $title ) > 100 ) {
		$errors->add( 'title', __( 'Title must be 100 characters or fewer.', 'digimarket' ) );
	}
	$price = isset( $data['price'] ) && '' !== $data['price'] ? round( (float) $data['price'], 2 ) : 0;
	$sale  = isset( $data['sale_price'] ) && '' !== trim( (string) $data['sale_price'] ) ? round( (float) $data['sale_price'], 2 ) : '';
	if ( $price < 0 ) {
		$errors->add( 'price', __( 'Price cannot be negative.', 'digimarket' ) );
	}
	if ( '' !== $sale && ( $sale < 0 || $sale >= $price ) ) {
		$errors->add( 'sale', __( 'Discount price must be lower than the regular price.', 'digimarket' ) );
	}
	$delivery = in_array( $data['delivery'] ?? 'file', array( 'file', 'license_key', 'external_link' ), true ) ? $data['delivery'] : 'file';
	$external = esc_url_raw( trim( (string) ( $data['external_url'] ?? '' ) ) );
	if ( 'external_link' === $delivery && $external && ! wp_http_validate_url( $external ) ) {
		$errors->add( 'url', __( 'The access link must be a valid URL.', 'digimarket' ) );
	}
	$status = in_array( $data['status'] ?? 'draft', array( 'draft', 'publish', 'dm_unpublished' ), true ) ? $data['status'] : 'draft';

	if ( $errors->has_errors() ) {
		return $errors;
	}

	if ( $pid && get_post_meta( $pid, '_dm_forced', true ) && 'publish' === $status && ! current_user_can( 'dm_manage_marketplace' ) ) {
		$status = 'dm_unpublished';
		dm_flash( 'warning', __( 'This product was unpublished by the marketplace team and cannot be republished. Contact support.', 'digimarket' ) );
	}
	if ( 'publish' === $status && ! dm_is_active_seller( $owner ) && ! user_can( $owner, 'manage_options' ) ) {
		$status = 'draft';
		dm_flash( 'info', __( 'Saved as draft — your shop must be approved before products go live.', 'digimarket' ) );
	}

	$postarr = array(
		'post_type'    => 'dm_product',
		'post_title'   => $title,
		'post_excerpt' => sanitize_textarea_field( mb_substr( (string) ( $data['short_description'] ?? '' ), 0, 300 ) ),
		'post_content' => wp_kses_post( (string) ( $data['full_description'] ?? '' ) ),
		'post_author'  => $owner,
		'post_status'  => 'publish' === $status ? 'draft' : $status, // Publish after validation below.
	);
	if ( ! empty( $data['slug'] ) ) {
		$postarr['post_name'] = sanitize_title( $data['slug'] );
	}
	if ( $pid ) {
		$postarr['ID'] = $pid;
		$result        = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$pid = (int) $result;

	update_post_meta( $pid, '_dm_price', $price );
	update_post_meta( $pid, '_dm_sale_price', $sale );
	update_post_meta( $pid, '_dm_effective_price', '' !== $sale ? $sale : $price );
	update_post_meta( $pid, '_dm_delivery', $delivery );
	update_post_meta( $pid, '_dm_external_url', $external );
	update_post_meta( $pid, '_dm_download_limit', max( 0, absint( $data['download_limit'] ?? 0 ) ) );
	update_post_meta( $pid, '_dm_access_days', max( 0, absint( $data['access_days'] ?? 0 ) ) );
	update_post_meta( $pid, '_dm_meta_title', sanitize_text_field( $data['meta_title'] ?? '' ) );
	update_post_meta( $pid, '_dm_meta_desc', sanitize_textarea_field( $data['meta_desc'] ?? '' ) );
	foreach ( array( '_dm_sales', '_dm_views', '_dm_rating_avg', '_dm_rating_count' ) as $counter ) {
		if ( '' === get_post_meta( $pid, $counter, true ) ) {
			update_post_meta( $pid, $counter, 0 );
		}
	}

	// Category & tags.
	$cat = absint( $data['category'] ?? 0 );
	wp_set_object_terms( $pid, $cat ? array( $cat ) : array(), 'dm_category' );
	$tags = array_filter( array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', (string) ( $data['tags'] ?? '' ) ) ) ) );
	wp_set_object_terms( $pid, array_slice( $tags, 0, 20 ), 'dm_tag' );

	// Thumbnail.
	if ( ! empty( $_FILES['thumbnail']['name'] ) ) {
		$img = dm_upload_image( 'thumbnail', $owner, null, $pid );
		if ( is_wp_error( $img ) ) {
			dm_flash( 'error', $img->get_error_message() );
		} elseif ( $img ) {
			set_post_thumbnail( $pid, $img );
		}
	}

	// Gallery (max 5).
	$gallery = array_map( 'absint', (array) get_post_meta( $pid, '_dm_gallery', true ) );
	$gallery = array_values( array_filter( $gallery ) );
	if ( ! empty( $data['gallery_remove'] ) ) {
		$gallery = array_values( array_diff( $gallery, array_map( 'absint', (array) $data['gallery_remove'] ) ) );
	}
	if ( ! empty( $_FILES['gallery']['name'] ) && is_array( $_FILES['gallery']['name'] ) ) {
		foreach ( array_keys( $_FILES['gallery']['name'] ) as $i ) {
			if ( count( $gallery ) >= 5 ) {
				dm_flash( 'warning', __( 'Only 5 gallery images are allowed; extra images were skipped.', 'digimarket' ) );
				break;
			}
			$img = dm_upload_image( 'gallery', $owner, $i, $pid );
			if ( is_wp_error( $img ) ) {
				dm_flash( 'error', $img->get_error_message() );
			} elseif ( $img ) {
				$gallery[] = $img;
			}
		}
	}
	update_post_meta( $pid, '_dm_gallery', $gallery );

	// Digital file.
	if ( ! empty( $_FILES['digital_file']['name'] ) ) {
		$stored = dm_store_private_file( $_FILES['digital_file'] );
		if ( is_wp_error( $stored ) ) {
			dm_flash( 'error', $stored->get_error_message() );
		} else {
			$old = get_post_meta( $pid, '_dm_file', true );
			if ( $old && file_exists( dm_private_dir() . '/' . $old ) ) {
				wp_delete_file( dm_private_dir() . '/' . $old );
			}
			update_post_meta( $pid, '_dm_file', $stored['stored'] );
			update_post_meta( $pid, '_dm_file_name', $stored['name'] );
			update_post_meta( $pid, '_dm_file_size', $stored['size'] );
		}
	}

	// License keys (append new).
	if ( ! empty( $data['license_keys'] ) ) {
		global $wpdb;
		$keys  = array_unique( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $data['license_keys'] ) ) ) );
		$added = 0;
		foreach ( array_slice( $keys, 0, 5000 ) as $k ) {
			$k = sanitize_text_field( $k );
			if ( '' === $k ) {
				continue;
			}
			$wpdb->insert( dm_table( 'license_keys' ), array( 'product_id' => $pid, 'key_value' => $k, 'is_used' => 0, 'created_at' => dm_now() ) );
			++$added;
		}
		if ( $added ) {
			/* translators: %d count */
			dm_flash( 'success', sprintf( _n( '%d license key added.', '%d license keys added.', $added, 'digimarket' ), $added ) );
		}
	}
	if ( ! empty( $data['delete_unused_keys'] ) ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . dm_table( 'license_keys' ) . ' WHERE product_id = %d AND is_used = 0', $pid ) );
	}

	// Validate requirements for publishing.
	if ( 'publish' === $status ) {
		$missing = array();
		if ( ! has_post_thumbnail( $pid ) ) {
			$missing[] = __( 'a thumbnail image', 'digimarket' );
		}
		if ( 'file' === $delivery && ! get_post_meta( $pid, '_dm_file', true ) ) {
			$missing[] = __( 'the digital file', 'digimarket' );
		}
		if ( 'external_link' === $delivery && ! $external ) {
			$missing[] = __( 'the access link', 'digimarket' );
		}
		if ( 'license_key' === $delivery && dm_license_keys_available( $pid ) < 1 ) {
			$missing[] = __( 'at least one license key', 'digimarket' );
		}
		if ( $missing ) {
			/* translators: %s list */
			dm_flash( 'warning', sprintf( __( 'Saved as draft. To publish, add %s.', 'digimarket' ), implode( ', ', $missing ) ) );
		} else {
			$was_published = (bool) get_post_meta( $pid, '_dm_published_once', true );
			wp_update_post( array( 'ID' => $pid, 'post_status' => 'publish' ) );
			if ( ! $was_published ) {
				update_post_meta( $pid, '_dm_published_once', 1 );
				do_action( 'dm_product_first_published', $pid, $owner );
			}
		}
	}

	return $pid;
}

/**
 * Notify followers when a product goes live for the first time.
 */
add_action( 'dm_product_first_published', 'dm_notify_followers', 10, 2 );
function dm_notify_followers( $pid, $owner ) {
	global $wpdb;
	$followers = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . dm_table( 'follows' ) . ' WHERE seller_id = %d', $owner ) );
	foreach ( $followers as $f ) {
		/* translators: 1 shop 2 product */
		dm_notify( $f, sprintf( __( '%1$s released a new product: %2$s', 'digimarket' ), dm_shop_name( $owner ), get_the_title( $pid ) ), get_permalink( $pid ) );
	}
}

function dm_duplicate_product( $pid, $owner ) {
	$src = get_post( $pid );
	if ( ! $src ) {
		return 0;
	}
	$new = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'dm_product',
				'post_title'   => mb_substr( $src->post_title . ' ' . __( '(copy)', 'digimarket' ), 0, 100 ),
				'post_excerpt' => $src->post_excerpt,
				'post_content' => $src->post_content,
				'post_author'  => $owner,
				'post_status'  => 'draft',
			)
		)
	);
	if ( ! $new || is_wp_error( $new ) ) {
		return 0;
	}
	foreach ( array( '_dm_price', '_dm_sale_price', '_dm_effective_price', '_dm_delivery', '_dm_external_url', '_dm_download_limit', '_dm_access_days', '_dm_meta_title', '_dm_meta_desc', '_dm_gallery' ) as $k ) {
		update_post_meta( $new, $k, get_post_meta( $pid, $k, true ) );
	}
	foreach ( array( '_dm_sales', '_dm_views', '_dm_rating_avg', '_dm_rating_count' ) as $k ) {
		update_post_meta( $new, $k, 0 );
	}
	// Copy the private file so the duplicate is independent.
	$file = get_post_meta( $pid, '_dm_file', true );
	if ( $file && file_exists( dm_private_dir() . '/' . $file ) ) {
		$ext    = pathinfo( get_post_meta( $pid, '_dm_file_name', true ), PATHINFO_EXTENSION );
		$stored = wp_generate_password( 24, false ) . '.' . $ext . '.bin';
		if ( copy( dm_private_dir() . '/' . $file, dm_private_dir() . '/' . $stored ) ) {
			update_post_meta( $new, '_dm_file', $stored );
			update_post_meta( $new, '_dm_file_name', get_post_meta( $pid, '_dm_file_name', true ) );
			update_post_meta( $new, '_dm_file_size', get_post_meta( $pid, '_dm_file_size', true ) );
		}
	}
	$thumb = get_post_thumbnail_id( $pid );
	if ( $thumb ) {
		set_post_thumbnail( $new, $thumb );
	}
	wp_set_object_terms( $new, wp_get_object_terms( $pid, 'dm_category', array( 'fields' => 'ids' ) ), 'dm_category' );
	wp_set_object_terms( $new, wp_get_object_terms( $pid, 'dm_tag', array( 'fields' => 'names' ) ), 'dm_tag' );
	return $new;
}

/* -------------------------------------------------------------------------
 * Catalogue queries (archive filters & sorting)
 * ---------------------------------------------------------------------- */

add_action( 'pre_get_posts', 'dm_catalogue_query' );
function dm_catalogue_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$is_catalogue = $q->is_post_type_archive( 'dm_product' ) || $q->is_tax( array( 'dm_category', 'dm_tag' ) ) || ( $q->is_search() && 'dm_product' === $q->get( 'post_type' ) );
	if ( ! $is_catalogue ) {
		return;
	}
	$q->set( 'post_type', 'dm_product' );
	$q->set( 'posts_per_page', 12 );
	dm_apply_catalogue_args( $q );
}

/**
 * Applies filter/sort query-string params to a WP_Query (main or custom).
 */
function dm_apply_catalogue_args( $q ) {
	$meta = array( 'relation' => 'AND' );
	$min  = isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] ? (float) $_GET['min_price'] : null;
	$max  = isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] ? (float) $_GET['max_price'] : null;
	if ( null !== $min ) {
		$meta[] = array( 'key' => '_dm_effective_price', 'value' => $min, 'compare' => '>=', 'type' => 'DECIMAL(12,2)' );
	}
	if ( null !== $max ) {
		$meta[] = array( 'key' => '_dm_effective_price', 'value' => $max, 'compare' => '<=', 'type' => 'DECIMAL(12,2)' );
	}
	if ( ! empty( $_GET['rating'] ) ) {
		$meta[] = array( 'key' => '_dm_rating_avg', 'value' => (float) $_GET['rating'], 'compare' => '>=', 'type' => 'DECIMAL(3,1)' );
	}
	if ( count( $meta ) > 1 ) {
		$q->set( 'meta_query', $meta );
	}
	$tax = array();
	if ( ! empty( $_GET['pcat'] ) ) {
		$tax[] = array( 'taxonomy' => 'dm_category', 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET['pcat'] ) ) );
	}
	if ( ! empty( $_GET['ptag'] ) ) {
		$tax[] = array( 'taxonomy' => 'dm_tag', 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET['ptag'] ) ) );
	}
	if ( $tax ) {
		$q->set( 'tax_query', $tax );
	}
	$sort = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'newest';
	switch ( $sort ) {
		case 'bestselling':
			$q->set( 'meta_key', '_dm_sales' );
			$q->set( 'orderby', array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) );
			break;
		case 'price_asc':
			$q->set( 'meta_key', '_dm_effective_price' );
			$q->set( 'orderby', array( 'meta_value_num' => 'ASC' ) );
			break;
		case 'price_desc':
			$q->set( 'meta_key', '_dm_effective_price' );
			$q->set( 'orderby', array( 'meta_value_num' => 'DESC' ) );
			break;
		case 'rating':
			$q->set( 'meta_key', '_dm_rating_avg' );
			$q->set( 'orderby', array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) );
			break;
		default:
			$q->set( 'orderby', 'date' );
			$q->set( 'order', 'DESC' );
	}
}

/* Search results for products use the catalogue template. */
add_filter( 'template_include', 'dm_search_template', 50 );
function dm_search_template( $template ) {
	if ( is_search() && 'dm_product' === get_query_var( 'post_type' ) ) {
		$t = locate_template( 'archive-dm_product.php' );
		return $t ? $t : $template;
	}
	return $template;
}

/* Count product views (once per visitor per product per day). */
add_action( 'template_redirect', 'dm_count_view' );
function dm_count_view() {
	if ( ! is_singular( 'dm_product' ) ) {
		return;
	}
	$pid    = get_queried_object_id();
	$cookie = 'dm_v_' . $pid;
	if ( isset( $_COOKIE[ $cookie ] ) ) {
		return;
	}
	update_post_meta( $pid, '_dm_views', (int) get_post_meta( $pid, '_dm_views', true ) + 1 );
	setcookie( $cookie, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
}

/* Hide non-public products from anyone but owner/admin. */
add_action( 'template_redirect', 'dm_protect_unpublished' );
function dm_protect_unpublished() {
	if ( ! is_singular( 'dm_product' ) ) {
		return;
	}
	$post = get_queried_object();
	if ( 'publish' !== $post->post_status && (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'dm_view_marketplace' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
}

/**
 * Query helper for product grids.
 */
function dm_query_products( $args = array() ) {
	return new WP_Query(
		wp_parse_args(
			$args,
			array(
				'post_type'           => 'dm_product',
				'post_status'         => 'publish',
				'posts_per_page'      => 8,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		)
	);
}

/**
 * Recalculate a product's rating aggregates.
 */
function dm_refresh_product_rating( $pid ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT AVG(rating) a, COUNT(*) c FROM ' . dm_table( 'reviews' ) . " WHERE product_id = %d AND status = 'approved'", $pid ) );
	update_post_meta( $pid, '_dm_rating_avg', $row && $row->c ? round( (float) $row->a, 1 ) : 0 );
	update_post_meta( $pid, '_dm_rating_count', $row ? (int) $row->c : 0 );
}

/* -------------------------------------------------------------------------
 * Admin product meta box (for editing in wp-admin).
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes_dm_product', 'dm_add_product_metabox' );
function dm_add_product_metabox() {
	add_meta_box( 'dm_product_data', __( 'Product data', 'digimarket' ), 'dm_product_metabox', 'dm_product', 'normal', 'high' );
}

add_action( 'post_edit_form_tag', function ( $post ) {
	if ( $post && 'dm_product' === $post->post_type ) {
		echo ' enctype="multipart/form-data"';
	}
} );

function dm_product_metabox( $post ) {
	wp_nonce_field( 'dm_admin_product', 'dm_admin_product_nonce' );
	$delivery = get_post_meta( $post->ID, '_dm_delivery', true );
	$delivery = $delivery ? $delivery : 'file';
	$fields   = array(
		'price'          => array( __( 'Price', 'digimarket' ), get_post_meta( $post->ID, '_dm_price', true ), 'number' ),
		'sale_price'     => array( __( 'Discount price (optional)', 'digimarket' ), get_post_meta( $post->ID, '_dm_sale_price', true ), 'number' ),
		'external_url'   => array( __( 'External access link', 'digimarket' ), get_post_meta( $post->ID, '_dm_external_url', true ), 'url' ),
		'download_limit' => array( __( 'Download limit per purchase (0 = unlimited)', 'digimarket' ), get_post_meta( $post->ID, '_dm_download_limit', true ), 'number' ),
		'access_days'    => array( __( 'Access days (0 = lifetime)', 'digimarket' ), get_post_meta( $post->ID, '_dm_access_days', true ), 'number' ),
		'meta_title'     => array( __( 'SEO meta title', 'digimarket' ), get_post_meta( $post->ID, '_dm_meta_title', true ), 'text' ),
		'meta_desc'      => array( __( 'SEO meta description', 'digimarket' ), get_post_meta( $post->ID, '_dm_meta_desc', true ), 'text' ),
	);
	echo '<table class="form-table"><tbody>';
	foreach ( $fields as $k => $f ) {
		echo '<tr><th><label for="dm_' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td><input class="regular-text" type="' . esc_attr( $f[2] ) . '" step="0.01" min="0" id="dm_' . esc_attr( $k ) . '" name="dm[' . esc_attr( $k ) . ']" value="' . esc_attr( $f[1] ) . '"></td></tr>';
	}
	echo '<tr><th>' . esc_html__( 'Delivery type', 'digimarket' ) . '</th><td><select name="dm[delivery]">';
	foreach ( array( 'file', 'license_key', 'external_link' ) as $d ) {
		echo '<option value="' . esc_attr( $d ) . '"' . selected( $delivery, $d, false ) . '>' . esc_html( dm_delivery_label( $d ) ) . '</option>';
	}
	echo '</select></td></tr>';
	$fname = get_post_meta( $post->ID, '_dm_file_name', true );
	echo '<tr><th>' . esc_html__( 'Digital file', 'digimarket' ) . '</th><td>' . ( $fname ? '<p><code>' . esc_html( $fname ) . '</code></p>' : '' ) . '<input type="file" name="digital_file"></td></tr>';
	echo '<tr><th>' . esc_html__( 'Add license keys (one per line)', 'digimarket' ) . '</th><td><textarea name="dm[license_keys]" rows="4" class="large-text"></textarea><p class="description">' . esc_html( sprintf( /* translators: %d */ __( '%d unused keys available.', 'digimarket' ), dm_license_keys_available( $post->ID ) ) ) . '</p></td></tr>';
	echo '<tr><th>' . esc_html__( 'Featured on homepage', 'digimarket' ) . '</th><td><label><input type="checkbox" name="dm[featured]" value="1"' . checked( get_post_meta( $post->ID, '_dm_featured', true ), 1, false ) . '> ' . esc_html__( 'Show in the homepage hero rotator', 'digimarket' ) . '</label></td></tr>';
	echo '<tr><th>' . esc_html__( 'Moderation lock', 'digimarket' ) . '</th><td><label><input type="checkbox" name="dm[forced]" value="1"' . checked( get_post_meta( $post->ID, '_dm_forced', true ), 1, false ) . '> ' . esc_html__( 'Force-unpublished (seller cannot republish)', 'digimarket' ) . '</label></td></tr>';
	echo '</tbody></table>';
}

add_action( 'save_post_dm_product', 'dm_admin_save_product', 10, 2 );
function dm_admin_save_product( $pid, $post ) {
	if ( ! is_admin() || ! isset( $_POST['dm_admin_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dm_admin_product_nonce'] ) ), 'dm_admin_product' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $pid ) ) {
		return;
	}
	$d     = isset( $_POST['dm'] ) ? wp_unslash( (array) $_POST['dm'] ) : array();
	$price = round( (float) ( $d['price'] ?? 0 ), 2 );
	$sale  = isset( $d['sale_price'] ) && '' !== $d['sale_price'] ? round( (float) $d['sale_price'], 2 ) : '';
	if ( '' !== $sale && $sale >= $price ) {
		$sale = '';
	}
	update_post_meta( $pid, '_dm_price', $price );
	update_post_meta( $pid, '_dm_sale_price', $sale );
	update_post_meta( $pid, '_dm_effective_price', '' !== $sale ? $sale : $price );
	update_post_meta( $pid, '_dm_delivery', in_array( $d['delivery'] ?? '', array( 'file', 'license_key', 'external_link' ), true ) ? $d['delivery'] : 'file' );
	update_post_meta( $pid, '_dm_external_url', esc_url_raw( $d['external_url'] ?? '' ) );
	update_post_meta( $pid, '_dm_download_limit', absint( $d['download_limit'] ?? 0 ) );
	update_post_meta( $pid, '_dm_access_days', absint( $d['access_days'] ?? 0 ) );
	update_post_meta( $pid, '_dm_meta_title', sanitize_text_field( $d['meta_title'] ?? '' ) );
	update_post_meta( $pid, '_dm_meta_desc', sanitize_text_field( $d['meta_desc'] ?? '' ) );
	foreach ( array( '_dm_sales', '_dm_views', '_dm_rating_avg', '_dm_rating_count' ) as $counter ) {
		if ( '' === get_post_meta( $pid, $counter, true ) ) {
			update_post_meta( $pid, $counter, 0 );
		}
	}
	if ( current_user_can( 'dm_manage_marketplace' ) ) {
		$was_forced = (bool) get_post_meta( $pid, '_dm_forced', true );
		update_post_meta( $pid, '_dm_featured', empty( $d['featured'] ) ? 0 : 1 );
		update_post_meta( $pid, '_dm_forced', empty( $d['forced'] ) ? 0 : 1 );
		if ( ! empty( $d['forced'] ) && ! $was_forced ) {
			dm_audit( 'product_force_unpublish', 'product', $pid );
			if ( 'publish' === $post->post_status ) {
				remove_action( 'save_post_dm_product', 'dm_admin_save_product', 10 );
				wp_update_post( array( 'ID' => $pid, 'post_status' => 'dm_unpublished' ) );
				add_action( 'save_post_dm_product', 'dm_admin_save_product', 10, 2 );
			}
		}
	}
	if ( ! empty( $_FILES['digital_file']['name'] ) ) {
		$stored = dm_store_private_file( $_FILES['digital_file'] );
		if ( ! is_wp_error( $stored ) ) {
			update_post_meta( $pid, '_dm_file', $stored['stored'] );
			update_post_meta( $pid, '_dm_file_name', $stored['name'] );
			update_post_meta( $pid, '_dm_file_size', $stored['size'] );
		}
	}
	if ( ! empty( $d['license_keys'] ) ) {
		global $wpdb;
		foreach ( array_unique( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $d['license_keys'] ) ) ) ) as $k ) {
			$wpdb->insert( dm_table( 'license_keys' ), array( 'product_id' => $pid, 'key_value' => sanitize_text_field( $k ), 'is_used' => 0, 'created_at' => dm_now() ) );
		}
	}
}

/* Commission override field on product categories. */
add_action( 'dm_category_edit_form_fields', function ( $term ) {
	$v = get_term_meta( $term->term_id, 'dm_commission', true );
	echo '<tr class="form-field"><th><label for="dm_commission">' . esc_html__( 'Commission override (%)', 'digimarket' ) . '</label></th><td><input type="number" step="0.01" min="0" max="100" name="dm_commission" id="dm_commission" value="' . esc_attr( $v ) . '"><p class="description">' . esc_html__( 'Leave blank to use the global rate.', 'digimarket' ) . '</p></td></tr>';
} );
add_action( 'dm_category_add_form_fields', function () {
	echo '<div class="form-field"><label for="dm_commission">' . esc_html__( 'Commission override (%)', 'digimarket' ) . '</label><input type="number" step="0.01" min="0" max="100" name="dm_commission" id="dm_commission"><p>' . esc_html__( 'Leave blank to use the global rate.', 'digimarket' ) . '</p></div>';
} );
add_action( 'created_dm_category', 'dm_save_category_commission' );
add_action( 'edited_dm_category', 'dm_save_category_commission' );
function dm_save_category_commission( $term_id ) {
	if ( ! current_user_can( 'dm_manage_marketplace' ) || ! isset( $_POST['dm_commission'] ) ) {
		return;
	}
	$old = get_term_meta( $term_id, 'dm_commission', true );
	$new = '' === $_POST['dm_commission'] ? '' : max( 0, min( 100, (float) $_POST['dm_commission'] ) );
	if ( (string) $old !== (string) $new ) {
		update_term_meta( $term_id, 'dm_commission', $new );
		dm_audit( 'commission_category_change', 'category', $term_id, array( 'from' => $old, 'to' => $new ) );
	}
}
