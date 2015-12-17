function geoJsonPopup(feature, layer) {
    // does this feature have a property named popupContent?
    if (feature.properties && feature.properties.name) {
        layer.bindPopup(feature.properties.name);
    }
}

function planeMarker(feature, latlng){    
    var myIcon = L.icon({ 
        iconUrl: feature.properties.externalGraphic, // pull out values as desired from the feature feature.properties.style.externalGraphic.
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        //popupAnchor: [-14, 0],
    });

    marker = L.marker(latlng, {icon: myIcon});
    return marker; 
}  


var googleStreet = new L.TileLayer('http://mt1.google.com/vt/lyrs=m@129&hl=en&x={x}&y={y}&z={z}&s=Galileo', {
    maxZoom:18,
    attribution:'Map data Google'
});

var googleHybrid = new L.TileLayer('http://mt1.google.com/vt/lyrs=y@129&hl=en&x={x}&y={y}&z={z}&s=Galileo', {
    maxZoom:18,
    attribution:'Map data Google'
});

//var skyVector= new L.TileLayer('http://t0.skyvector.net/tiles/304/1307/{z}/{x}/{y}.jpg', {
 //   maxZoom:15,
  //  minZoom:8,
//    attribution:'Map data SkyVector',
 //   zulu: '({z}-7)*2'
//});

var skyVector = new L.TileLayer.Functional(function (view) {
    		    var url = 'http://t0.skyvector.net/tiles/304/1307/{z}/{x}/{y}.jpg'
			        .replace('{z}', (view.zoom-7)*2)
			        .replace('{x}', view.tile.row)
			        .replace('{y}', view.tile.column);
			    return url;
			}, {
				subdomains: '1234',
                maxZoom:15,
                minZoom:8
			});

var destination = new L.KML("include/wx.php", {async:true});
destination.on("loaded", function (e) {
    map.fitBounds(e.target.getBounds());
});
var alternates = new L.KML("include/wx.php?altn=1", {async:true});
//var flightradar = new L.geoJson('', {
//    onEachFeature: geoJsonPopup,
//    pointToLayer: planeMarker
//});
var flightradar = L.geoJson.ajax("http://gwi.lidousers.com/flightradar.php",{onEachFeature: geoJsonPopup, pointToLayer: planeMarker});
var baseMaps = {
    'Google Street':googleStreet,
    'Google Hybrid':googleHybrid
 //'SkyVector':skyVector
};

var overlayMaps = {
    "Destinations":destination,
    "Alternates":alternates
};

var map = L.map('map', {
    center:new L.LatLng(51.505, -0.09),
    zoom:5,
    layers:[googleStreet, destination]
});

//$.ajax({
//    type: "POST",
//    url: "http://gwi.lidousers.com/flightradar.php",
//    dataType: 'json',
//    success: function (response) {
//        flightradar.addData(response);
//    }
//});


var control = L.control.layers(baseMaps, overlayMaps).addTo(map);


/**
 * Called to refresh Destination and Alternates layers
 */
function refreshKML() {
    var enable_alternate = 0;
    var enable_destination = 0;
    if (map.hasLayer(destination) == true) {
        enable_destination = 1;
    }
    if (map.hasLayer(alternates) == true) {
        enable_alternate = 1;
    }
    control.removeLayer(destination);
    control.removeLayer(alternates);
    map.removeLayer(destination);
    map.removeLayer(alternates);

    destination = new L.KML("include/wx.php", {async:true});
    alternates = new L.KML("include/wx.php?altn=1", {async:true});

    control.addOverlay(destination, 'Destinations');
    control.addOverlay(alternates, 'Alternates');
    if (enable_destination == 1) {
        map.addLayer(destination);
    }
    if (enable_alternate == 1) {
        map.addLayer(alternates);
    }
    setTimeout(refreshKML, 300000);
    setTimeout(updateHeader, 10000);
}

/**
 * Called to update heade information - old METAR status for DEST and ALTN
 */
function updateHeader(){
    $.get('include/wx.php?status=1', function(data) {
        $('.header').html(data);
    });
}

/*function refreshFR(){
    flightradar.refresh();
    setTimeout(refreshFR, 30000);
}*/

setTimeout(refreshKML, 300000);
setTimeout(updateHeader, 10000);
setTimeout(refreshFR, 30000);