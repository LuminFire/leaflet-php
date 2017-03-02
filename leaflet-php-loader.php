<?php
/**
 * This file is the loader for the LeafletPHP class.
 *
 * @package LeafletPHP
 */

$version = '0.0.1';

if ( !class_exists( 'leafletphp_loader' ) ) {

	/**
	 * A small simple loader class. It collects all instances of LeafletPHP, then loads one of them. 
	 */
	class leafletphp_loader {
		public static $versions = array();

		// All instances call this static function to load in their verions and files.
		public static function register_version($version_number,$file) {
			leafletphp_loader::$versions[$version_number] = $file;
		}

		/**
		 * A loader function for apl_autoload_register.
		 *
		 * This gets called by PHP if LeafletPHP is not a class.
		 *
		 * This is preferable to always loading the class, since
		 * this will avoid loading it if it's not needed.
		 */
		public static function load( $className ){
			if ( $className === 'LeafletPHP' ) {
				// Sort keys by version_compare.
				uksort( leafletphp_loader::$versions, 'version_compare' );

				// Go to the end of the array and require the file.
				require_once( end( leafletphp_loader::$versions ) );

				// Set the $version variable. Means we only have to keep the version in one place (each individual install's loader file).
				LeafletPHP::$version = key( leafletphp_loader::$versions );
			}
		}
	}

	// Let PHP auto loading only include the file if needed
	spl_autoload_register( array( 'leafletphp_loader','load' ) );
}

leafletphp_loader::register_version($version,dirname( __FILE__ ) . '/leaflet-php.php' );
