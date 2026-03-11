<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Pusbin JFT')</title>

  {{-- Fonts & Icons --}}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="/library/plugins/fontawesome-free/css/all.min.css">
  <link rel="icon" type="image/x-icon" href="/library/assets/img/favicon.ico" />
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  {{-- AdminLTE CSS --}}
  <link rel="stylesheet" href="/library/dist/css/adminlte.min.css">

  {{-- Leaflet CSS (peta) --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" crossorigin=""/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css"/>

  {{-- DataTables CSS --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

  {{-- Select2 CSS (untuk dropdown searchable) --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

  {{-- SweetAlert (opsional) --}}
  <link rel="stylesheet" href="https://unpkg.com/sweetalert/dist/sweetalert.css">

  {{-- CSS custom --}}
  <link rel="stylesheet" href="/library/dist/css/map.css">
  <style>
    #leafletMap-registration { height: 400px; }
  </style>

  
@stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  Preloader
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="/library/dist/img/logo-kemenhub.png" alt="Logo" height="300" width="300">
  </div>

  {{-- Navbar --}}
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      {{-- User Info & Role Badge --}}
      <li class="nav-item">
        <span class="nav-link" style="cursor: default;">
          <i class="fas fa-user-circle"></i>
          <span class="ml-2">{{ Auth::user()->name }}</span>
          @role('super_admin')
            <span class="badge badge-danger ml-2">Super Admin</span>
          @elserole('admin')
            <span class="badge badge-primary ml-2">Admin</span>
          @elserole('operator')
            <span class="badge badge-warning text-dark ml-2">Operator</span>
          @elserole('viewer')
            <span class="badge badge-success ml-2">Viewer</span>
          @endrole
        </span>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>

  {{-- Sidebar --}}
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link">
      <img src="/library/dist/img/logopusbinbaru.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
      <span class="brand-text font-weight-light">Pusbin JFT</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-header">MENU</li>

          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon far fa-user"></i>
              <p>Akun <i class="fas fa-angle-left right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('logout') }}" class="nav-link" onclick="pindah2(event)">
                  <i class="far fa-circle nav-icon"></i><p>Logout</p>
                </a>
              </li>
            </ul>
          </li>

          {{-- Dashboard - Semua Role --}}
          <li class="nav-item">
            <a href="{{ route('user.peta') }}" class="nav-link" onclick="pindah(event)">
              <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
            </a>
          </li>

          <li class="nav-header">FITUR</li>

          {{-- Unit Kerja --}}
          @can('view unit kerja')
          <li class="nav-item">
            <a href="{{ route('user.unitkerja.index') }}" class="nav-link" onclick="pindah(event)">
              <i class="nav-icon far fa-image"></i><p>Unit Kerja</p>
            </a>
          </li>
          @endcan

          {{-- Formasi --}}
          @can('view formasi')
          <li class="nav-item">
            <a href="{{ route('user.formasi.index') }}" class="nav-link" onclick="pindah(event)">
              <i class="nav-icon fas fa-briefcase"></i><p>Formasi</p>
            </a>
          </li>
          @endcan

          {{-- Pegawai JFT --}}
          @can('view pegawai')
          <li class="nav-item">
            <a href="{{ route('user.sdm.index') }}" class="nav-link" onclick="pindah(event)">
              <i class="nav-icon fas fa-users"></i><p>Pegawai JFT</p>
            </a>
          </li>
          @endcan

          {{-- Laporan - Hanya Admin & Super Admin --}}
          @role('admin|super_admin')
          <li class="nav-item">
            <a href="{{ route('user.laporan.index') }}" class="nav-link" onclick="pindah(event)">
              <i class="nav-icon fas fa-file-alt"></i><p>Laporan</p>
            </a>
          </li>
          @endrole

          {{-- Manajemen User - Hanya Super Admin --}}
          @role('super_admin')
          <li class="nav-header">ADMINISTRASI</li>
          <li class="nav-item">
            <a href="{{ route('user.manajemen-user.index') }}" class="nav-link">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>Manajemen User</p>
            </a>
          </li>
          @endrole
        </ul>
      </nav>
    </div>
  </aside>

  {{-- Content --}}
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        @yield('isi')
        @include('layouts.component.alert')
      </div>
    </section>
  </div>

  {{-- Footer --}}
  <footer class="main-footer">
    <strong>Copyright &copy; Pusbin JFT 2025</strong>
  </footer>
</div>

{{-- ========== JS (hanya sekali & urut) ========== --}}
<script src="/library/plugins/jquery/jquery.min.js"></script>
<script src="/library/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/library/plugins/jquery-ui/jquery-ui.min.js"></script>
<script src="/library/dist/js/adminlte.js"></script>

{{-- Plugins --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

{{-- Leaflet JS (peta) --}}
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-geosearch@3.1.0/dist/geosearch.umd.js"></script>
<script src="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

{{-- Komponen map bawaan project (kalau ada script di dalamnya) --}}
@include('layouts.component.map')

{{-- Script halaman anak (Select2 init, dsb) HARUS setelah semua library di-load --}}
@stack('scripts')


@push('styles')
<style>
  /* Kartu filter: visual halus */
  .filter-card .card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e9ecef;
  }
  /* Label & kontrol konsisten */
  .filter-row .form-label {
    font-weight: 600;
    margin-bottom: .35rem;
  }
  .filter-row .form-select {
    min-height: 42px;
  }
  /* Spasi antar kontrol nyaman di layar kecil */
  @media (max-width: 991.98px) {
    .filter-row .col-12 { margin-bottom: .25rem; }
  }
</style>
@endpush

</body>
</html>
