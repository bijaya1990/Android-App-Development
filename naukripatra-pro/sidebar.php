<?php
/**
 * Sidebar wrapper.
 *
 * @package naukripatra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
nppro_sidebar( is_singular() ? 'single' : 'home' );
