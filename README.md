Leaflet PHP
===========

Leaflet PHP lets you build a Leaflet.js map in PHP and then generates the HTML and JavaScript to initialize it based on the settings you provided.

The JavaScript is encapsulated so that you can have multiple maps on the same page without worrying about conflicting variables.


Usage
--------------

Print a default Leaflet map.

```
print new LeafletPHP();
```

Set the ID of the div map the map will be in AND the global variable name the map will use. 
```
print LeafletPHP(array(),'wpgmmap');
```

Pass Leaflet.js initialization options in to LeafletPHP.
```
$map = new LeafletPHP(array(
  'scrollWheelZoom' => false
),'wpgmmap');
```

Variables and Function in Javascript and Args
---------------------------------------------

Because json_encode doesn't allow variables as values, you'll need to wrap any variables with `@@@` and leaflet-php 
will strip the quotes and @@@. 

```
$geojsonFeature = array(
		array(
		"type" => "Feature",
		"properties" => array(
			"name" =>"Coors Field",
			"amenity" => "Baseball Stadium",
			"popupContent" => "This is where the Rockies play!"
			),
		"geometry" => array(
			"type" =>"Point",
			"coordinates" => array(-104.99404, 39.75621)
			)
		),
		array(
			'onEachFeature' => '@@@onEachFeature@@@'
		)
);

$map->add_script('
		function onEachFeature(feature, layer) {
		// does this feature have a property named popupContent?
		if (feature.properties && feature.properties.popupContent) {
			layer.bindPopup(feature.properties.popupContent);
		}
	}
');
$map->add_layer('L.geoJSON',$geojsonFeature,'destpoints');
```

JavaScript
----------

When the map is initialized, a 'leafletphp/loaded' event is trigger on the div containing the map.

You can respond to it in several ways including:

```
jQuery(document).on('leafletphp/loaded',function(e,mapobj){
	if ( mapobj.scriptid === 'mymapid' ) {
		console.log("My map was loaded!");
	}
});
```

or 

```
jQuery('#mymapid').on('leafletphp/loaded',function(e){
	mymapid.map.on('focus',function(e){
		console.log("Focused!");
	});
});
```
