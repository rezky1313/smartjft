<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pusbin JFT</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="/library/plugins/fontawesome-free/css/all.min.css">
  <link rel="icon" type="image/x-icon" href="library/assets/favicon.ico" />
  {{-- <link rel="stylesheet" href="/library/dist/leaflet/leaflet.css"> --}}
  {{-- <script src="/library/dist/leaflet/leaflet.js"></script> --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
  integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
  crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
  integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
  crossorigin=""></script>
  <!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="/library/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://unpkg.com/sweetalert/dist/sweetalert.css">
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  {{-- <link rel="stylesheet" href="/library/plugins/sweetalert/sweetalert.css">
  <script src="/library/plugins/sweetalert/sweetalert.min.js"></script> --}}
  <link rel="stylesheet" href="/library/dist/css/map.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.css" />
  <script src="https://unpkg.com/leaflet-geosearch@3.1.0/dist/geosearch.umd.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
<script src="https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

  <style>
    #leafletMap-registration {

    height: 400px;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="/library/dist/img/logo-kemenhub.png" alt="AdminLTELogo" height="300" width="300">
  </div>



  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto">

      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>

    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="" class="brand-link">
      <img src="/library/dist/img/logo-kemenhub.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Pusbin JFT</span>
    </a>

    <div class="sidebar">


      <nav class="mt-2">

        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

            <li class="nav-header">MENU</li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon far fa-user"></i>
                  <p>
                    Akun
                    <i class="fas fa-angle-left right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('logout')}}" class="nav-link" onclick="pindah2(event)">
                      <i class="far fa-circle nav-icon" ></i>
                      <p>Logout</p>
                    </a>
                  </li>
                </ul>

                @if (Auth::user()->role =='admin')
                <li class="nav-item">
                    <a href="{{ url('admin/dashboard/peta') }}" class="nav-link" onclick="pindah(event)">
                      <i class="nav-icon fas fa-tachometer-alt"></i>
                      <p>
                        Dashboard
                      </p>
                    </a>
                  </li>

                  <li class="nav-header">FITUR</li>
                  <li class="nav-item">
                    <a href="{{ url('admin/') }}" class="nav-link" onclick="pindah(event)">
                      <i class="nav-icon far fa-image"></i>
                      <p>
                        Data Unit Kerja
                      </p>
                    </a>
                  </li>

                  <li class="nav-item">
                    <a href="{{ url('admin/create') }}" class="nav-link" onclick="pindah(event)">
                      <i class="nav-icon fas fa-columns"></i>
                      <p>
                        Tambah Data
                      </p>
                    </a>
                  </li>

                  <li class="nav-item">
  <a href="{{ route('admin.formasi-jabatan.index') }}" class="nav-link" onclick="pindah(event)">
    <i class="nav-icon fas fa-briefcase"></i>
    <p>Formasi Jabatan</p>
  </a>
</li>


                @endif

                @if (Auth::user()->role =='user')

                <li class="nav-item">
                    <a href="{{ url('user/dashboard/peta') }}" class="nav-link" onclick="pindah(event)">
                      <i class="nav-icon fas fa-tachometer-alt"></i>
                      <p>
                        Dashboard
                      </p>
                    </a>
                  </li>

                  <li class="nav-header">FITUR</li>
                  <li class="nav-item">
                    <a href="{{ url('user/') }}" class="nav-link" onclick="pindah(event)">
                      <i class="nav-icon far fa-image"></i>
                      <p>
                        Data Unit Kerja
                      </p>
                    </a>
                  </li>

                @endif




          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">



@yield('isi')

        @include('layouts.component.alert')



</div>
</section>

</div>

<footer class="main-footer">
    <strong>Copyright &copy; Pusbin JFT 2025 </strong>
  </footer>

  </aside>

</div>

<script src="/library/jquery-3.6.4.min.js"></script>
<script src="/library/plugins/bootstrap/js/bootstrap.bundle.js"></script>
<script src="/library/plugins/jquery/jquery.min.js"></script>
<script src="/library/plugins/jquery-ui/jquery-ui.min.js"></script>
<script src="/library/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/library/dist/js/adminlte.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

@include('layouts.component.map')

</body>
</html>
