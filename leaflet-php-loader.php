<?php
/**
 * This file is the loader for the LeafletPHP class.
 *
 * @package LeafletPHP
 */

/**
 * TODO: Handle loading the most recent version.
 */
if ( ! class_exists( 'LeafletPHP' ) ) {
	require_once( dirname( __FILE__ ) . '/leaflet-php.php' );
}
