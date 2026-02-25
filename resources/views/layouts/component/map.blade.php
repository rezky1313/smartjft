{{-- @if (Auth::user()->role =='admin') --}}

{{-- <script type="text/javascript">

    $(document).ready(function() {
      const providerOSM = new GeoSearch.OpenStreetMapProvider();

      //leaflet map
      var leafletMap = L.map('leafletMap-registration', {
        fullscreenControl: true,
        // OR
        fullscreenControl: {
          pseudoFullscreen: false // if true, fullscreen to page width and height
        },
        minZoom: 2
      }).setView([0,0], 2);

      L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(leafletMap);

      let theMarker = {};

      leafletMap.on('click',function(e) {
        let latitude  = e.latlng.lat.toString().substring(0,15);
        let longitude = e.latlng.lng.toString().substring(0,15);

        if (theMarker != undefined) {
          leafletMap.removeLayer(theMarker);
        };

        $("#latitude").val(latitude);
        $("#longitude").val(longitude);

        var popup = L.popup()
          .setLatLng([latitude,longitude])
          .setContent("Kordinat : " + latitude +" - "+  longitude )
          .openOn(leafletMap);

        theMarker = L.marker([latitude,longitude]).addTo(leafletMap);
      });

      const search = new GeoSearch.GeoSearchControl({
        provider: providerOSM,
        style: 'bar',
        searchLabel: 'Indonesia',
      });

      leafletMap.addControl(search);
    });

    </script> --}}
<!-- # -7.9784694, 112.56157 -->
    <script>
    var map = L.map('leafletMap-registration').setView([-2.5489, 118.0149], 5); //lat, lng, zoom level
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var popup = L.popup();

function onMapClick(e) {
    var latitudeInput = document.getElementById('latitude');
    var longitudeInput = document.getElementById('longitude');

    latitudeInput.value = e.latlng.lat;
    longitudeInput.value = e.latlng.lng;

    popup
        .setLatLng(e.latlng)
        .setContent("Titik Koordinat Berada Pada: " + e.latlng.toString())
        .openOn(map);
}

map.on('click', onMapClick);

L.Control.geocoder().addTo(map);

L.control.locate().addTo(map);

</script>


{{-- @endif --}}


