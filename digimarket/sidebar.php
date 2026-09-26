<?php
/**
 * Blog sidebar.
 *
 * @package DigiMarket
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside class="dm-blog-side" aria-label="<?php esc_attr_e( 'Sidebar', 'digimarket' ); ?>"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
