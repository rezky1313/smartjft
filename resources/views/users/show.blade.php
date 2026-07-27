@extends('layouts.users.master')
@include('layouts.component.leaflet-assets')
@section('title')
@if (Auth::user()->hasAnyRole(['admin','super_admin']))
    Pusbin JFT - ADMIN
@else
    Pusbin JFT - USER
@endif
@endsection
@section('isi')

@php
  $kab = $unitKerja->regency;
  $prov = $kab?->province;
@endphp

<div class="preview-card">
  <div class="preview-header d-flex justify-content-between align-items-center">
    <div>
      <span class="preview-header-title">Detail Unit Kerja</span>
      <span class="preview-header-subtitle d-block">Informasi lengkap unit kerja</span>
    </div>
    <a href="{{ route('user.unitkerja.index') }}" class="btn btn-outline-secondary btn-sm" onclick="pindah(event)">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>
  <div class="preview-body">

    <span class="subsection-label">Informasi Dasar</span>
    <table class="table table-borderless table-sm mb-4">
      <tr>
        <td style="width:220px;" class="font-weight-bold">Kode Unit Kerja</td>
        <td>: {{ $unitKerja->id }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Nama Unit Kerja</td>
        <td>: {{ $unitKerja->nama_unit_kerja }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Alamat</td>
        <td>: {{ $unitKerja->alamat }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">No. Telepon</td>
        <td>: {{ $unitKerja->no_telp }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Provinsi</td>
        <td>: {{ $prov->name ?? '-' }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Kabupaten/Kota</td>
        <td>: {{ $kab ? ($kab->type.' '.$kab->name) : '-' }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Matra</td>
        <td>: {{ $unitKerja->matra ?? '-' }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Instansi</td>
        <td>: {{ $unitKerja->instansi ?? '-' }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Latitude</td>
        <td>: {{ $unitKerja->latitude }}</td>
      </tr>
      <tr>
        <td class="font-weight-bold">Longitude</td>
        <td>: {{ $unitKerja->longitude }}</td>
      </tr>
    </table>

    <span class="subsection-label">Lokasi</span>
    <div id="map" style="height:400px; border-radius:8px;"></div>
  </div>
</div>

@push('scripts')
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
@endpush

@endsection
