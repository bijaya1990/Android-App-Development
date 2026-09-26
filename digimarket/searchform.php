<?php
/**
 * Search form.
 *
 * @package DigiMarket
 */
?>
<form role="search" method="get" class="dm-inline-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="dm-sf-<?php echo esc_attr( wp_unique_id() ); ?>"><?php esc_html_e( 'Search', 'digimarket' ); ?></label>
	<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'digimarket' ); ?>">
	<button class="dm-btn dm-btn-primary" type="submit"><?php esc_html_e( 'Search', 'digimarket' ); ?></button>
</form>
