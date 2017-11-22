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
	 * Debug or not. 
	 *
	 * @var $debug
	 */
	var $debug;

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

		if ( !defined( 'JSON_PRETTY_PRINT') ) {
			define( 'JSON_PRETTY_PRINT', 128 );
		}

		$this->debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || !empty($_GET['LEAFLETPHP_DEBUG']);
		$this->pretty_print_json = ( $this->debug ? JSON_PRETTY_PRINT : 0 );
		$this->newline = ( $this->debug ? "\n" : '' );
		$this->tab = ( $this->debug ? "    " : '' );
		$this->newline_tab = $this->newline . $this->tab;

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
		global $wp_scripts, $wp_styles;
		$this->enqueue_scripts();

		if ( ! empty( $this->jsid ) ) {
			$this->jsid = $this->jsid;
		}

		$idtag = 'id="' . $this->jsid . '" ';

		$classnames = array('leafletphp');
		if ( ! empty( $this->classname ) ) {
			$classnames[] = $this->classname;
		}

		/**
		 * Verify that needed css/js is available.
		 *
		 * If a handle is still in the queue, then it wasn't printed yet. 
		 */
		$maybe_missing_css = array();
		foreach( $wp_styles->queue as $handle ){
			if ( strpos( $handle, 'leafletphp' ) === 0 ) {
				$maybe_missing_css[] = $handle;
			}
		}

		$missing_css = array();
		if ( !empty( $maybe_missing_css ) ) {
			$wp_styles->all_deps( $maybe_missing_css, true );
			foreach ( $wp_styles->to_do as $handle ) {
				$src = $wp_styles->registered[$handle]->src;

				if ( empty( $src ) ) {
					continue;
				}

				if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $wp_styles->content_url && 0 === strpos( $src, $wp_styles->content_url ) ) ) {
					$src = $wp_styles->base_url . $src;
				}

				$missing_css[] = add_query_arg( 'ver', $wp_styles->registered[$handle]->ver, $src);
			}
		}

		$maybe_missing_js = array();
		foreach( $wp_scripts->queue as $handle ){
			if ( strpos( $handle, 'leafletphp' ) === 0 ) {
				$maybe_missing_js[] = $handle;
			}
		}

		$missing_js = array();
		if ( !empty( $maybe_missing_js ) ) {
			$wp_scripts->all_deps( $maybe_missing_js, true );
			foreach( $wp_scripts->to_do as $handle ) {
				$src = $wp_scripts->registered[$handle]->src;

				if ( empty( $src ) ) {
					continue;
				}

				if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $wp_scripts->content_url && 0 === strpos( $src, $wp_scripts->content_url ) ) ) {
					$src = $wp_scripts->base_url . $src;
				}

				$missing_js[$handle] = add_query_arg( 'ver', $wp_scripts->registered[$handle]->ver, $src );
			}
		}

		$html = array();

		// Set up the div wrapper.
		$html[] = '<div class="leafletphpwrap">';
		$html[] = '<div ' . $idtag . ' class="' . implode( ' ', $classnames ) . '" data-leafletphp="' . $this->jsid . '">';
		$html[] = '<script data-leafletphp="' . $this->jsid . '">';
		$html[] = 'window.leafletphp = window.leafletphp || {' . $this->newline_tab . 'js_deferreds:[],' . $this->newline_tab . 'maps:{}' . $this->newline . '};';
		$html[] = 'jQuery(document).ready(function(){';

		// Load needed css and JS
		if ( !empty( $missing_css ) ) {
			$html[] = 'var maybe_missing_css = ' . $this->json_encode( $missing_css ) . ";";
			$html[] = 'jQuery(maybe_missing_css).each(function(i,css){';
			$html[] =  $this->tab . 'if ( jQuery(\'link[href="\'+css+\'"]\').length === 0 ) {';
			$html[] =  $this->tab . 'jQuery(\'head\').append(\'<link rel="stylesheet" href="\'+css+\'">\');';
			$html[] = '}});';
		}


		if ( !empty( $missing_js ) ) {

			$html[] = 'var load_missing_js = function(js){';
			$html[] = $this->tab . 'if ( jQuery(\'script[src="\'+js+\'"]\').length === 0 ) {';
			$html[] = $this->tab . $this->tab .'jQuery(\'head\').append(\'<script src="\'+js+\'">\');';
			$html[] = $this->tab . $this->tab .'window.leafletphp.js_deferreds.push(jQuery.getScript(js));';
			$html[] = '}};';

			/**
			 * Do smart loading here so we don't wipe out any plugins that have been loaded for JQuery or Leaflet.
			 */
			if ( !empty( $missing_js['leafletphp-leaflet-js'] ) ) {
				$html[] = "if ( typeof L === 'undefined' ) { " . $this->newline_tab .  "load_missing_js('" . $missing_js['leafletphp-leaflet-js'] . "'); " . $this->newline . "};";
			}
			if ( !empty( $missing_js['leafletphp-draw-js'] ) ) {
				$html[] = "if ( typeof L === 'undefined' || typeof L.Draw === 'undefined' ) { " . $this->newline_tab . "load_missing_js('" . $missing_js['leafletphp-draw-js'] . "'); " . $this->newline . "};";
			}
			if ( !empty( $missing_js['leafletphp-locate-js'] ) ) {
				$html[] = "if ( typeof L === 'undefined' || typeof L.Control.Locate=== 'undefined' ) { " . $this->newline_tab . "load_missing_js('" . $missing_js['leafletphp-locate-js'] . "'); " . $this->newline . "};";
			}
		}

		$html[] = 'jQuery.when.apply(jQuery,window.leafletphp.js_deferreds).then( function() { ' . $this->newline_tab . 'new function(){';

		// Short icons for draw toolbar.
		$html[] = $this->tab . "if ( L.drawLocal !== undefined ) {";
		$html[] = $this->tab . $this->tab . "L.drawLocal.draw.toolbar.actions.text = 'X';"; // Cancel
		$html[] = $this->tab . $this->tab . "L.drawLocal.draw.toolbar.finish.text = '💾';";  // Save
		$html[] = $this->tab . $this->tab . "L.drawLocal.draw.toolbar.undo.text = '↩';"; // Undo

		$html[] = $this->tab . $this->tab . "L.drawLocal.edit.toolbar.actions.save.text = '💾';";  // Save
		$html[] = $this->tab . $this->tab . "L.drawLocal.edit.toolbar.actions.cancel.text = 'X';"; // Cancel
		$html[] = $this->tab . $this->tab . "L.drawLocal.edit.toolbar.actions.clearAll.text = '💥';"; // Cancel
		$html[] = $this->tab . "}";


		// Set a script ID
		$html[] = $this->tab . 'this.scriptid = "' . $this->jsid . '";';

		// Initialize Leaflet.
		$html[] = $this->tab . 'var map = this.map = L.map("' . $this->jsid . '", ' . $this->json_encode( $this->settings['leaflet'] ) . ');';

		// Initialize basemap(s).
		$html[] = 'this.basemaps = {};';
		foreach ( $this->basemaps as $basemap ) {

			if ( empty( $basemap['name'] ) ) {
				$basemap['name'] = 'basemap_' . rand();
			}

			$html[] = $this->tab . 'var ' . $basemap['name'] . ' = this.basemaps.' . $basemap['name'] . ' = ' . $basemap['type'] . $this->newline_tab . '.apply(this,' . $this->json_encode( $basemap['args'] ) . ')' . $this->newline_tab . '.addTo(this.map);';
		}

		// Initialize layers.
		$html[] = 'this.layers = {};';
		foreach ( $this->layers as $layer ) {
			if ( empty( $layer['name'] ) ) {
				$layer['name'] = 'layer_' . rand();
			}

			$html[] = $this->tab . 'var ' . $layer['name'] . ' = this.layers.' . $layer['name']  . '= ' . $layer['type'] . $this->newline_tab . '.apply(this,' . $this->json_encode( $layer['args'] ) . ')' . $this->newline_tab . '.addTo(this.map);';
		}

		// Initialize controls.
		$html[] = 'this.controls = {};';
		foreach ( $this->controls as $control ) {
			if ( empty( $control['name'] ) ) {
				$control['name'] = 'control_' . rand();
			}

			$html[] = $this->tab . 'var ' . $control['name'] . ' = this.controls.' . $control['name'] . ' = new ' . $control['type'] . '(' . $this->json_encode( $control['args'] ) . ');';
			$html[] = $this->tab . 'this.map.addControl(' . $control['name'] . ');';
		}

		// Set up reference to inside the container.
		$html[] = $this->tab . 'window.' . $this->jsid . ' = window.leafletphp.maps.' . $this->jsid . ' = this;';

		// Add user scripts here at the bottom.
		foreach ( $this->scripts as $script ) {
			$html[] = $this->tab . $script;
		}

		$html[] = $this->tab . 'jQuery("#' . $this->jsid . '").trigger("leafletphp/loaded",this);';

		$html[] = '};});});' . "\n" . '</script>';
		$html[] = '</div></div>';
		$html[] = '<div class="leafletphpspacer" data-leafletphp="' . $this->jsid . '"></div>';

		if ( $this->debug ) {
			$output = "\n" . implode( "\n",$html ) . "\n";
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
	 *
	 * NOTE: Don't forget to add conditional browser-side auto-loading down in get_html().
	 */
	public function enqueue_scripts() {
		$baseurl = plugins_url( dirname( plugin_basename( __FILE__ ) ) );

		$leafletphp_needs_js = array('leafletphp-leaflet-js');
		$leafletphp_needs_css = array('leafletphp-leaflet-css');

		// Always enqueue these.
		if ( $this->debug ) {
			wp_enqueue_script( 'leafletphp-leaflet-js', $baseurl . '/assets/leaflet/leaflet-src.js', array( 'jquery' ), LeafletPHP::$version );
		} else {
			wp_enqueue_script( 'leafletphp-leaflet-js', $baseurl . '/assets/leaflet/leaflet.js', array( 'jquery' ), LeafletPHP::$version );
		}

		wp_enqueue_style( 'leafletphp-leaflet-css', $baseurl . '/assets/leaflet/leaflet.css', array(), LeafletPHP::$version );

		// Enqueue needed control scripts.
		foreach ( $this->controls as $control ) {
			switch ( $control['type'] ) {
				case 'L.Control.Draw':
					$leafletphp_needs_js[] = 'leafletphp-draw-js';
					if ( $this->debug ) {
						wp_enqueue_script( 'leafletphp-draw-js', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw-src.js', array( 'leafletphp-leaflet-js' ), LeafletPHP::$version );
					} else {
						wp_enqueue_script( 'leafletphp-draw-js', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw.js', array( 'leafletphp-leaflet-js' ), LeafletPHP::$version );
					}

					$leafletphp_needs_css[] = 'leafletphp-draw-css';
					wp_enqueue_style( 'leafletphp-draw-css', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw.css', array( 'leafletphp-leaflet-css' ), LeafletPHP::$version );
					break;
				case 'L.Control.Locate':
					$leafletphp_needs_js[] = 'leafletphp-locate-js';
					if ( $this->debug ) {
						wp_enqueue_script( 'leafletphp-locate-js', $baseurl . '/assets/leaflet-locatecontrol/dist/L.Control.Locate.js', array( 'leafletphp-leaflet-js' ), LeafletPHP::$version );
					} else {
						wp_enqueue_script( 'leafletphp-locate-js', $baseurl . '/assets/leaflet-locatecontrol/dist/L.Control.Locate.min.js', array( 'leafletphp-leaflet-js' ), LeafletPHP::$version );
					}

					$leafletphp_needs_css[] = 'leafletphp-locate-css';
					wp_enqueue_style( 'leafletphp-locate-css', $baseurl . '/assets/Leaflet-locatecontrol/dist/L.Control.Locate.min.css', array( 'leafletphp-leaflet-css' ), LeafletPHP::$version );
					break;
			}
		}

		// Finally, enqueue leafletphp, so it's last.
		wp_enqueue_script( 'leafletphp-leafletphp-js', $baseurl . '/assets/leafletphp.js', $leafletphp_needs_js, LeafletPHP::$version );
		wp_enqueue_style( 'leafletphp-css', $baseurl . '/assets/leafletphp.css', $leafletphp_needs_css, LeafletPHP::$version );
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
		$ret = wp_json_encode( $array, $this->pretty_print_json );
		$ret = preg_replace( '/:\s*"@@@(.*?)@@@"([,}])?/s',':\1\2',$ret );
		return $ret;
	}

	/**
	 * Magic method to print the html.
	 */
	public function __toString() {
		return $this->get_html();
	}
}
