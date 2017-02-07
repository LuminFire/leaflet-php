<?php

/**
 * A PHP class for building custom Leaflet.js initialization functions.
 */
class leafletphp {

	var $version = '2017-02-04';
	var $baseurl;
	var $classname;
	var $jsid;

	var $debug = true;

	var $settings = array(
		'leaflet' => array('center' => array(0,0), 'zoom' => 1),
		'locatecontrol' => array(),
		'draw' => array(),
	);

	var $basemaps = array(
		array(
			'type' => 'L.tileLayer', 
			'args' => array(
				'//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', array(
					'maxZoom' => 19,
					'attribution' =>  '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
				) 
			),
			'name' => 'default_basemap'	
		) 
	);

	var $layers = array();

	var $controls = array();

	var $scripts = array();

	public function __construct( $args = array(), $jsid = '', $classname = '' ) {
		$this->add_settings( 'leaflet', $args );
		$this->jsid = $jsid;
		$this->classname = $classname;
	}	

	public function add_settings( $target, $settings ) {
		$this->settings[$target] = array_merge( $this->settings[$target], $settings );
	}

	public function get_html() {
		$this->enqueue_scripts();

		$scriptid = 'leafletphp_' . rand();
		if ( !empty( $this->jsid ) ) {
			$scriptid = $this->jsid;
		}

		$idtag = 'id="' . $scriptid . '" ';

		$classtag = 'class="leafletphp" ';
		if ( !empty( $this->classname ) ) {
			$classtag = 'class="leafletphp ' . $this->classname . '" ';
		}

		$html = array();

		// Set up the div wrapper
		$html[] = '<div ' . $idtag . $classtag . 'data-leafletphp="' . $scriptid . '">';
		$html[] = '<script data-leafletphp="' . $scriptid . '">jQuery(document).ready(function() { new function(){';

		// Initialize Leaflet
		$html[] = 'var map = this.map = L.map("' . $scriptid . '", ' . $this->json_encode( $this->settings['leaflet'] ) . ');';


		// Initialize basemap(s)
		$html[] = 'this.basemaps = {};';
		foreach( $this->basemaps as $basemap ){

			if ( empty( $basemap['name'] ) ) {
				$basemap['name'] = 'basemap_' . rand();
			}

			$html[] = 'var ' . $basemap['name'] . ' = this.basemaps.' . $basemap['name'] . ' = ' . $basemap['type'] . '.apply(this,' . $this->json_encode( $basemap['args'] ) . ').addTo(this.map);';
		}

		// Initialize layers
		$html[] = 'this.layers = {};';
		foreach( $this->layers as $layer ) {
			if ( empty( $layer['name'] ) ) {
				$layer['name'] = 'layer_' . rand();
			}

			$html[] = 'var ' . $layer['name'] . ' = this.layers.' . $layer['name']  . '= ' . $layer['type'] . '.apply(this,' . $this->json_encode( $layer['args'] ) . ').addTo(this.map);';
		}

		// Initialize controls
		$html[] = 'this.controls = {};';
		foreach( $this->controls as $control ) {
			if ( empty( $control['name'] ) ) {
				$control['name'] = 'control_' . rand();
			}

			$html[] = 'var ' . $control['name'] . ' = this.controls.' . $control['name'] . ' = new ' . $control['type'] . '(' . $this->json_encode( $control['args'] ) . ');';
			$html[] = 'this.map.addControl(' . $control['name'] . ');';
		}

		// Set up reference to inside the container
		$html[] = 'window.' . $scriptid . ' = this;';

		// Add user scripts here at the bottom;
		foreach( $this->scripts as $script ) {
			$html[] = $script;
		}

		$html[] = '};});</script>';
		$html[] = '</div>';

		if ( $this->debug ) {
			$output = implode("\n",$html);
		} else {
			$output = implode('',$html);
		}

		return $output;
	}


	public function add_basemap($type,$args,$layer_name = ''){
		$this->layers[] = array(
			'type' => $type, 
			'args' => $args,
			'name' => $layer_name
		);
	}

	public function add_layer($type,$args,$layer_name = ''){
		$this->layers[] = array(
			'type' => $type, 
			'args' => $args,
			'name' => $layer_name
		);
	}

	public function add_control($type,$args,$control_name= ''){
		$this->controls[] = array(
			'type' => $type, 
			'args' => $args,
			'name' => $control_name
		);
	}

	public function enqueue_scripts() {
		$baseurl = plugins_url( dirname( plugin_basename( __FILE__ )  ) );

		// Always enqueue these
		wp_enqueue_script( 'leafletphp-leaflet-js', $baseurl . '/assets/leaflet/leaflet.js', array('jquery'), $this->version );
		wp_enqueue_style( 'leafletphp-css', $baseurl . '/assets/leafletphp.css', array(), $this->version );
		wp_enqueue_style( 'leafletphp-leaflet-css', $baseurl . '/assets/leaflet/leaflet.css', array('leafletphp-css'), $this->version );

		// Enqueue needed control scripts
		foreach( $this->controls as $control ) {
			switch ( $control['type'] ) {
				case 'L.Control.Draw':
					wp_enqueue_script( 'leafletphp-draw-js', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw.js', array('leafletphp-leaflet-js'), $this->version );
					wp_enqueue_style( 'leafletphp-draw-css', $baseurl . '/assets/Leaflet.draw/dist/leaflet.draw.css', array('leafletphp-leaflet-css'), $this->version );
					break;
			}
		}
	}

	public function add_script( $script ) {
		$this->scripts[] = $script;
	}

	public function json_encode($array){
		$ret = json_encode( $array );
		$ret = preg_replace('/:"@@@(.*?)@@@"([,}])/',':\1\2',$ret);
		return $ret;
	}

	public function print() {
		print $this->get_html();
	}

	public function __toString() {
		return $this->get_html();
	}

}
