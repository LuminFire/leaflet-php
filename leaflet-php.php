<?php
/**
 * This file provides the LeafletPHP class
 *
 * @package LeafletPHP
 */

/**
 * A PHP class for building custom Leaflet.js initialization functions.
 */
class LeafletPHP {

	/**
	 * The version of LeafletPHP.
	 *
	 * @var $version
	 */
	public static $version;

	/**
	 * An additional classname to add to the wrapper div.
	 *
	 * @var $classname
	 */
	var $classname;

	/**
	 * A global JS ID for the map wrapped object.
	 *
	 * @var $jsid
	 */
	var $jsid;

	/**
	 * Are we in debug mode.
	 *
	 * @var $debug
	 */
	var $debug = true;

	/**
	 * Default settings for our known plugins.
	 *
	 * @var $settings
	 */
	var $settings = array(
		'leaflet' => array( 'center' => array( 0, 0 ), 'zoom' => 1 ),
		'locatecontrol' => array(),
		'draw' => array(),
	);

	/**
	 * The basemaps we're going to show.
	 *
	 * @var $basemaps
	 */
	var $basemaps = array(
		array(
			'type' => 'L.tileLayer',
			'args' => array(
				'//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	array(
					'maxZoom' => 19,
					'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
				),
			),
			'name' => 'default_basemap',
		),
	);

	/**
	 * The regular layers we're going to show.
	 *
	 * @var $layers
	 */
	var $layers = array();

	/**
	 * The map controls we're going to show.
	 *
	 * @var $controls
	 */
	var $controls = array();

	/**
	 * Any additional scripts we're going to include within the IIFE
	 *
	 * @var $scripts
	 */
	var $scripts = array();

	/**
	 * The constructor.
	 *
	 * @param array  $args An array of Leaflet.js constructor arguments.
	 * @param string $jsid The ID that this IIFE will have.
	 * @param string $classname Additional classnames to put in the wrapper div.
	 */
	public function __construct( $args = array(), $jsid = '', $classname = '' ) {
		$this->add_settings( 'leaflet', $args );
		$this->jsid = ( !empty( $jsid ) ? $jsid : 'leafletphp_' . rand() );
		$this->classname = $classname;
	}

	/**
	 * Add settings for known objects.
	 *
	 * @param string $target What are these settings for.
	 * @param array  $settings The array of settings.
	 */
	public function add_settings( $target, $settings ) {
		$this->settings[ $target ] = array_merge( $this->settings[ $target ], $settings );
	}

	/**
	 * Get the output HTML, including the JS which will initialize the map.
	 */
	public function get_html() {
		$this->enqueue_scripts();

		if ( ! empty( $this->jsid ) ) {
			$this->jsid = $this->jsid;
		}

		$idtag = 'id="' . $this->jsid . '" ';

		$classtag = 'class="leafletphp" ';
		if ( ! empty( $this->classname ) ) {
			$classtag = 'class="leafletphp ' . $this->classname . '" ';
		}

		$html = array();

		// Set up the div wrapper.
		$html[] = '<div ' . $idtag . $classtag . 'data-leafletphp="' . $this->jsid . '">';
		$html[] = '<script data-leafletphp="' . $this->jsid . '">' . "\n" . 'jQuery(document).ready(function() { new function(){';

		$html[] = 'this.scriptid = "' . $this->jsid . '"';

		// Initialize Leaflet.
		$html[] = 'var map = this.map = L.map("' . $this->jsid . '", ' . $this->json_encode( $this->settings['leaflet'] ) . ');';

		// Initialize basemap(s).
		$html[] = 'this.basemaps = {};';
		foreach ( $this->basemaps as $basemap ) {

			if ( empty( $basemap['name'] ) ) {
				$basemap['name'] = 'basemap_' . rand();
			}

			$html[] = 'var ' . $basemap['name'] . ' = this.basemaps.' . $basemap['name'] . ' = ' . $basemap['type'] . '.apply(this,' . $this->json_encode( $basemap['args'] ) . ').addTo(this.map);';
		}

		// Initialize layers.
		$html[] = 'this.layers = {};';
		foreach ( $this->layers as $layer ) {
			if ( empty( $layer['name'] ) ) {
				$layer['name'] = 'layer_' . rand();
			}

			$html[] = 'var ' . $layer['name'] . ' = this.layers.' . $layer['name']  . '= ' . $layer['type'] . '.apply(this,' . $this->json_encode( $layer['args'] ) . ').addTo(this.map);';
		}

		// Initialize controls.
		$html[] = 'this.controls = {};';
		foreach ( $this->controls as $control ) {
			if ( empty( $control['name'] ) ) {
				$control['name'] = 'control_' . rand();
			}

			$html[] = 'var ' . $control['name'] . ' = this.controls.' . $control['name'] . ' = new ' . $control['type'] . '(' . $this->json_encode( $control['args'] ) . ');';
			$html[] = 'this.map.addControl(' . $control['name'] . ');';
		}

		// Set up reference to inside the container.
		$html[] = 'window.' . $this->jsid . ' = this;';

		// Add user scripts here at the bottom.
		foreach ( $this->scripts as $script ) {
			$html[] = $script;
		}

		$html[] = 'jQuery("#' . $this->jsid . '").trigger("leafletphp/loaded",this);';

		$html[] = '};});' . "\n" . '</script>';
		$html[] = '</div>';

		if ( $this->debug ) {
			$output = implode( "\n",$html );
		} else {
			$output = implode( '',$html );
		}

		return $output;
	}

	/**
	 * Add a basemap.
	 *
	 * @param string $type The type of basemap to add.
	 * @param array  $args The arguments for the basemap.
	 * @param string $layer_name A name for the layer.
	 */
	public function add_basemap( $type, $args, $layer_name = '' ) {
		$this->layers[] = array(
			'type' => $type,
			'args' => $args,
			'name' => $layer_name,
		);
	}

	/**
	 * Add a regular layer.
	 *
	 * @param string $type The type of layer to add.
	 * @param array  $args The arguments for the layer.
	 * @param string $layer_name A name for the layer.
	 */
	public function add_layer( $type, $args, $layer_name = '' ) {
		$this->layers[] = array(
			'type' => $type,
			'args' => $args,
			'name' => $layer_name,
		);
	}

	/**
	 * Add a control.
	 *
	 * @param string $type The type of control to add.
	 * @param array  $args The arguments for the control.
	 * @param string $control_name A name for the control.
	 */
	public function add_control( $type, $args, $control_name = '' ) {
		$this->controls[] = array(
			'type' => $type,
			'args' => $args,
			'name' => $control_name,
		);
	}

	/**
	 * Add user scripts which will run inside the IIFE.
	 *
	 * @param string $script The JS script to run (no <script> tags needed).
	 */
	public function add_script( $script ) {
		$this->scripts[] = $script;
	}

	/**
	 * Callback handler to enqueue scripts.
	 */
	public function enqueue_scripts() {
		$baseurl = plugins_url( dirname( plugin_basename( __FILE__ ) ) );

		// Always enqueue these.
		wp_enqueue_script( 'leafletphp-leaflet-js', $baseurl . '/assets/leaflet/leaflet.js', array( 'jquery' ), LeafletPHP::$version );
		wp_enqueue_style( 'leafletphp-css', $baseurl . '/assets/leafletphp.css', array(), LeafletPHP::$version );
		wp_enqueue_style( 'leafletphp-leaflet-css', $baseurl . '/assets/leaflet/leaflet.css', array( 'leafletphp-css' ), LeafletPHP::$version );

		// Enqueue needed control scripts.
		foreach ( $this->controls as $control ) {
			switch ( $control['type'] ) {
				case 'L.Control.Draw':
					wp_enqueue_script( 'leafletphp-draw-js', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw.js', array( 'leafletphp-leaflet-js' ), LeafletPHP::$version );
					wp_enqueue_style( 'leafletphp-draw-css', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw.css', array( 'leafletphp-leaflet-css' ), LeafletPHP::$version );
					break;
				case 'L.Control.Locate':
					wp_enqueue_script( 'leafletphp-locate-js', $baseurl . '/assets/leaflet-locatecontrol/dist/L.Control.Locate.min.js', array( 'leafletphp-leaflet-js' ), LeafletPHP::$version );
					wp_enqueue_style( 'leafletphp-locate-css', $baseurl . '/assets/Leaflet-locatecontrol/dist/L.Control.Locate.min.css', array( 'leafletphp-leaflet-css' ), LeafletPHP::$version );
					break;
			}
		}
	}

	/**
	 * Get the map ID.
	 */
	public function get_id() {
		return $this->jsid;
	}

	/**
	 * Encode JSON, but strip quotes from strings wrapped in "@@@this@@@" so they'll be variables.
	 *
	 * @param array $array Something to json_encode.
	 */
	public function json_encode( $array ) {
		$ret = wp_json_encode( $array );
		$ret = preg_replace( '/:"@@@(.*?)@@@"([,}])/',':\1\2',$ret );
		return $ret;
	}

	/**
	 * Magic method to print the html.
	 */
	public function __toString() {
		return $this->get_html();
	}
}
