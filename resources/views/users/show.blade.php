@extends('layouts.users.master')
@include('layouts.component.leaflet-assets')
@section('title')
@if (Auth::user()->role =='admin')
    Pusbin JFT - ADMIN
@else
    Pusbin JFT - USER
@endif
@endsection
@section('isi')
    <div class="container">
        <h1>Detail Unit Kerja</h1>
        <div class="table-responsive">
            <table class="table mt-4">
                <tbody>
                    <tr>
                        <td>Kode Unit Kerja:</td>
                        <td>{{ $unitKerja->id }}</td>
                    </tr>
                    <tr>
                        <td>Nama Unit Kerja:</td>
                        <td>{{ $unitKerja->nama_unit_kerja }}</td>
                    </tr>
                    <tr>
                        <td>Alamat:</td>
                        <td>{{ $unitKerja->alamat }}</td>
                    </tr>
                    <tr>
                        <td>No. Telepon:</td>
                        <td>{{ $unitKerja->no_telp }}</td>
                    </tr>
                    <tr>
                        <td>Latitude:</td>
                        <td>{{ $unitKerja->latitude }}</td>
                    </tr>
                    <tr>
                        <td>Longitude:</td>
                        <td>{{ $unitKerja->longitude }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="map" class="mt-4"></div>
        <a href="{{ route('user.unitkerja.index') }}" class="btn btn-primary mt-4" onclick="pindah(event)">Kembali</a>
    </div>


    <script>
        var map = L.map('map').setView([{{ $unitKerja->latitude }}, {{ $unitKerja->longitude }}], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var popup = L.popup();

        function onMapClick(e) {
            popup
                .setLatLng(e.latlng)
                .setContent("Titik Koordinat Berada Pada: " + e.latlng.toString())
                .openOn(map);
        }

        map.on('click', onMapClick);

        L.Control.geocoder().addTo(map);
        L.control.locate().addTo(map);

        var marker = L.marker([{{ $unitKerja->latitude }}, {{ $unitKerja->longitude }}]).addTo(map);

        marker.bindPopup("<b>{{ $unitKerja->nama_unit_kerja }}</b><br>"+
        "<br> Alamat : {{ $unitKerja->alamat }} <br>" +
        "<br> No Telp : {{ $unitKerja->no_telp }} <br> "
        ).openPopup();

    </script>


@endsection
