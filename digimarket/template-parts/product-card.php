<?php
/**
 * Product card. Expects global $post (inside a loop).
 *
 * @package DigiMarket
 */

dm_render_product_card( get_the_ID(), isset( $args ) && is_array( $args ) ? $args : array() );
