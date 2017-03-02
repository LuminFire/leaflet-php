<?php
/**
 * This file is the loader for the LeafletPHP class.
 *
 * @package LeafletPHP
 */

$version = '0.0.1';

if ( !class_exists( 'leafletphp_loader' ) ) {

	/**
	 * A small simple loader
	 */
	class leafletphp_loader {
		public static $versions = array();

		public static function register_version($version_number,$file) {
			leafletphp_loader::$versions[$version_number] = $file;
		}

		public static function load(){
			uksort( leafletphp_loader::$versions, 'version_compare' );
			require_once( end( leafletphp_loader::$versions ) );
			LeafletPHP::$version = key( leafletphp_loader::$versions );
		}
	}

	add_action('plugins_loaded',array('leafletphp_loader','load'));
}

leafletphp_loader::register_version($version,dirname( __FILE__ ) . '/leaflet-php.php' );
