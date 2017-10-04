/**
* Tooltips don't do well on mobile / with touch devices. If we're on a touch device, 
* put the Leaflet.draw tooltips right under the active draw tool.
*/
if ( 
	L.Browser.touch && 
	L.Draw !== undefined && 
	L.Draw.Tooltips !== undefined &&
	L.Draw.Tooltip.prototype._UpdatePosition === undefined 
) {
	L.Draw.Tooltip.prototype._UpdatePosition = L.Draw.Tooltip.prototype.updatePosition;
	L.Draw.Tooltip.prototype.updatePosition = function(latlng){
		if ( L.Browser.touch ) {
			var active_button = document.getElementsByClassName('leaflet-draw-toolbar-button-enabled');

			if ( active_button.length > 0 ) {
				var a_bounds = active_button[0].getBoundingClientRect();
				var map_bounds = this._map._container.getBoundingClientRect();

				a_bounds.x -= map_bounds.x - a_bounds.width;
				a_bounds.y -= map_bounds.y - a_bounds.height;

				a_bounds.y += 11;
				a_bounds.x -= 19;
				a_bounds = this._map.containerPointToLatLng( a_bounds );	
				this._UpdatePosition( a_bounds );

				latlng = a_bounds;
			}
		}

		this._UpdatePosition(latlng);
	};
}
