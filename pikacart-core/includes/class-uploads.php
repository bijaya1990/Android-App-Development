<?php
/**
 * Safe uploads: real file-type checks, random names, a folder per account,
 * and no PHP execution inside upload folders.
 *
 * @package Pikacart
 */

defined( 'ABSPATH' ) || exit;

class PKC_Uploads {

	const IMAGE_TYPES = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'webp' => 'image/webp',
	);

	public static function base() {
		$up = wp_upload_dir( null, false );
		return array(
			'dir' => trailingslashit( $up['basedir'] ) . 'pikacart',
			'url' => trailingslashit( $up['baseurl'] ) . 'pikacart',
		);
	}

	/**
	 * Create the base folder with rules that block scripts and folder listing.
	 */
	public static function protect_base_dir() {
		$base = self::base();
		if ( ! wp_mkdir_p( $base['dir'] ) ) {
			return;
		}
		$htaccess = $base['dir'] . '/.htaccess';
		$rules    = "# Pikacart: never run scripts in upload folders\nOptions -Indexes\n<FilesMatch \"\\.(php|php[0-9]|phtml|phar|pl|py|cgi|sh|asp|aspx|jsp|htaccess)$\">\n  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n</FilesMatch>\n<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n";
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( $base['dir'] . '/index.php' ) ) {
			file_put_contents( $base['dir'] . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	public static function org_dir( $org ) {
		$base = self::base();
		$name = 'org-' . (int) $org->id . '-' . sanitize_key( $org->upload_dir );
		$dir  = $base['dir'] . '/' . $name;
		if ( ! is_dir( $dir ) ) {
			self::protect_base_dir();
			wp_mkdir_p( $dir );
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return array(
			'dir' => $dir,
			'url' => $base['url'] . '/' . $name,
		);
	}

	/**
	 * Save an uploaded image for an organisation.
	 *
	 * @param array  $file   One entry of $_FILES.
	 * @param object $org    Organisation row.
	 * @param string $prefix Short name for the file, e.g. logo.
	 * @return array|WP_Error [ 'url' => ..., 'path' => ... ]
	 */
	public static function save_image( $file, $org, $prefix ) {
		if ( empty( $file ) || ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'], $file['size'], $file['name'] ) ) {
			return new WP_Error( 'pkc_upload', __( 'Please choose an image file.', 'pikacart' ), array( 'status' => 400 ) );
		}
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$msg = in_array( (int) $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
				? __( 'This image is too large.', 'pikacart' )
				: __( 'The upload did not finish. Please try again.', 'pikacart' );
			return new WP_Error( 'pkc_upload', $msg, array( 'status' => 400 ) );
		}
		$max = max( 1, (float) pkc_setting( 'upload_max_mb', 5 ) ) * MB_IN_BYTES;
		if ( (int) $file['size'] > $max ) {
			/* translators: %s: size in MB */
			return new WP_Error( 'pkc_upload', sprintf( __( 'This image is too large. The limit is %s MB.', 'pikacart' ), pkc_setting( 'upload_max_mb', 5 ) ), array( 'status' => 400 ) );
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'pkc_upload', __( 'Invalid upload.', 'pikacart' ), array( 'status' => 400 ) );
		}

		// Check the real content, not just the name.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], self::IMAGE_TYPES );
		$info  = @getimagesize( $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$mime  = $info ? $info['mime'] : '';
		if ( empty( $check['ext'] ) || ! in_array( $mime, array_values( self::IMAGE_TYPES ), true ) ) {
			return new WP_Error( 'pkc_upload', __( 'Only JPG, PNG and WebP images are allowed.', 'pikacart' ), array( 'status' => 400 ) );
		}
		$ext = array_search( $mime, array( 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ), true );

		$target = self::org_dir( $org );
		$name   = sanitize_key( $prefix ) . '-' . strtolower( pkc_token( 16 ) ) . '.' . $ext;
		$path   = $target['dir'] . '/' . $name;

		if ( ! @move_uploaded_file( $file['tmp_name'], $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'pkc_upload', __( 'Could not save the image. Please try again.', 'pikacart' ), array( 'status' => 500 ) );
		}
		@chmod( $path, 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		return array(
			'url'  => $target['url'] . '/' . $name,
			'path' => $path,
		);
	}

	/**
	 * Delete a file that belongs to this organisation's folder.
	 */
	public static function delete_url( $org, $url ) {
		if ( ! $url ) {
			return;
		}
		$target = self::org_dir( $org );
		if ( 0 !== strpos( $url, $target['url'] . '/' ) ) {
			return;
		}
		$file = $target['dir'] . '/' . basename( wp_parse_url( $url, PHP_URL_PATH ) );
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
		}
	}

	public static function delete_org_dir( $org ) {
		$base = self::base();
		$dir  = $base['dir'] . '/org-' . (int) $org->id . '-' . sanitize_key( $org->upload_dir );
		if ( ! $org->upload_dir || ! is_dir( $dir ) ) {
			return;
		}
		self::rrmdir( $dir );
	}

	public static function rrmdir( $dir ) {
		$items = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $items ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( is_dir( $path ) && ! is_link( $path ) ) {
				self::rrmdir( $path );
			} else {
				wp_delete_file( $path );
			}
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
}
