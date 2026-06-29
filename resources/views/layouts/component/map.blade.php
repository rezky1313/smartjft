<script>
// Hanya inisialisasi map jika container ada di halaman ini
if (document.getElementById('leafletMap-registration')) {
    var map = L.map('leafletMap-registration').setView([-2.5489, 118.0149], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var popup = L.popup();

    function onMapClick(e) {
        var latitudeInput  = document.getElementById('latitude');
        var longitudeInput = document.getElementById('longitude');
        latitudeInput.value  = e.latlng.lat;
        longitudeInput.value = e.latlng.lng;
        popup
            .setLatLng(e.latlng)
            .setContent("Titik Koordinat Berada Pada: " + e.latlng.toString())
            .openOn(map);
    }

    map.on('click', onMapClick);
    L.Control.geocoder().addTo(map);
    L.control.locate().addTo(map);
}
</script>
