@extends('layouts.users.master')
@section('title')
@if (Auth::user()->role =='admin')
    SIGPELAKES - ADMIN
@else
    SIGPELAKES - USER
@endif
@endsection
@section('isi')
<div class="container-fluid">

@if (Auth::user()->role =='admin')
         <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $jumlahrs }}</h3>
                    <p>Jumlah Unit Kerja</p>
                </div>
                <div class="icon">
                    <i class="far fa-building"></i>
                </div>
                <a class="small-box-footer">.</a>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $jumlahuser }}</h3>
                    <p>Jumlah User</p>
                </div>
                <div class="icon">
                    <i class="far fa-user"></i>
                </div>
                <a class="small-box-footer">.</a>
            </div>
        </div>
    </div>
    @endif


    <section class="col-lg-12 connectedSortable">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-map"></i>
                    Peta Dashboard
                </h3>
            </div>
            <div id="leafletMap-dashboard" class="embed-responsive embed-responsive-16by9"></div>
        </div>
        <script>
            //-7.9486301, 112.6223047], 11.87
            var map = L.map('leafletMap-dashboard').setView([-2.5489, 118.0149], 5 );
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
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
        </script>

        @foreach ($rumahsakits as $peta)
        <script>
            var imageUrl = "{{ $peta->img_file }}"; // Ambil URL gambar dari database
            var imageHtml = '<img src="' + imageUrl + '" width="300px" class="img-fluid">'; // Buat tag HTML untuk gambar
            L.marker([{{ $peta->latitude }}, {{ $peta->longitude }}], ).addTo(map).bindPopup(
                "<br><b> Nama Unit Kerja : {{ $peta->nama_rumahsakit}}</b></br>" + //unit Kerja
                "<br> Alamat : {{ $peta->alamat}}</br><br>" +
                "No.Telp : {{ $peta->no_telp}}</br><br> " +
                "Jam Kerja : {{ $peta->jam_kerja}}</br><br>" +
                "Kabupaten/Kota : {{ $peta->fasilitas}}</br><br>" //kab/kota
                );
        </script>
        @endforeach
    </section>

</div>
@endsection
