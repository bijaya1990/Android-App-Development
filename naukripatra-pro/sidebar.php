<?php
/**
 * Sidebar wrapper.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
np_sidebar( is_singular() ? 'single' : 'home' );
