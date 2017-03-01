Leaflet PHP
===========

Leaflet PHP lets you build a Leaflet.js map in PHP and then generates the HTML and JavaScript to initialize it based on the settings you provided.

This is provided AS IS with no guarantees. It is an experament at this point.

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
