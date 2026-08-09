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

  {{-- AdminLTE CSS (JANGAN DIHAPUS — masih dipakai komponen di banyak halaman) --}}
  <link rel="stylesheet" href="/library/dist/css/adminlte.min.css">

  {{-- Font Plus Jakarta Sans (tema E-Kinerja) --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  {{-- Bootstrap Icons (dipakai tema E-Kinerja) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  {{-- Tema E-Kinerja (Digital Office Kemenhub) - HARUS setelah adminlte.css --}}
  <link rel="stylesheet" href="{{ asset('library/dist/css/do-layout.css') }}">

  {{-- Override warna primer & font SMART JFT di atas tema E-Kinerja --}}
  <link rel="stylesheet" href="{{ asset('library/dist/css/smartjft-theme.css') }}">

  {{-- SweetAlert2 untuk modal/konfirmasi yang lebih baik --}}
  <link rel="stylesheet" href="{{ asset('library/css/sweetalert2.min.css') }}">

  {{-- Leaflet CSS: dipush hanya oleh halaman yang membutuhkan peta --}}

  {{-- DataTables CSS --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

  {{-- Select2 CSS (untuk dropdown searchable) --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

  {{-- SweetAlert (lama, dipakai fungsi pindah()/pindah2() — dipertahankan) --}}
  <link rel="stylesheet" href="https://unpkg.com/sweetalert/dist/sweetalert.css">

  {{-- CSS custom --}}
  <link rel="stylesheet" href="/library/dist/css/map.css">
  <style>
    #leafletMap-registration { height: 400px; }
  </style>


@stack('styles')
</head>

<body>

{{-- Topbar SMART JFT (Digital Office) --}}
<nav class="main-header-custom d-flex align-items-center justify-content-between"
     style="background:#0e1937; height:64px; position:fixed; top:0; left:0; right:0; z-index:1060; padding:0 24px;">
  <div class="d-flex align-items-center" style="gap:16px;">
    <button class="btn btn-sm" style="color:#fff;" type="button" onclick="toggleSidebar()">
      <i class="bi bi-list" style="font-size:1.4rem;"></i>
    </button>
    <div class="d-flex align-items-center" style="gap:8px;">
      <img src="{{ asset('library/dist/img/logopusbinbaru.png') }}" alt="Logo" style="height:32px;">
      <div>
        <div style="color:#fff; font-size:14px; font-weight:600; line-height:1.2;">SMART JFT</div>
        <div style="color:rgba(255,255,255,.7); font-size:11px;">Pusat Pembinaan Jabatan Fungsional Transportasi</div>
      </div>
    </div>
    <div class="d-none d-md-block" style="padding-left:16px; border-left:1px solid rgba(255,255,255,.3); margin-left:4px;">
      <span style="color:#fff; font-size:12px; font-weight:500; opacity:.85;">Digital Office</span>
    </div>
  </div>

  <div class="d-flex align-items-center" style="gap:12px;">
    <span class="do-badge d-none d-sm-inline-flex" style="background:rgba(255,255,255,.15); color:#fff;">
      @role('super_admin')
        Super Admin
      @elserole('admin')
        Admin Pusbin
      @elserole('admin_unit')
        Admin Unit
      @elserole('pemangku')
        Pemangku JFT
      @elserole('viewer')
        Viewer
      @endrole
    </span>
    <div class="dropdown">
      <button class="btn btn-sm dropdown-toggle" style="color:#fff;" type="button" data-toggle="dropdown">
        <i class="bi bi-person-circle" style="font-size:1.1rem;"></i> {{ Auth::user()->name }}
      </button>
      <div class="dropdown-menu dropdown-menu-right">
        <a href="{{ route('logout') }}" class="dropdown-item text-danger" onclick="pindah2(event)">
          <i class="bi bi-box-arrow-right mr-1"></i> Logout
        </a>
      </div>
    </div>
  </div>
</nav>

{{-- Backdrop sidebar mobile --}}
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<div class="layout-wrapper">

  {{-- Sidebar --}}
  <aside class="sidebar" id="appSidebar">
    <div class="sidebar-header-custom d-flex align-items-center border-bottom" style="gap:10px;">
      <img src="{{ asset('library/dist/img/logopusbinbaru.png') }}" alt="Logo" style="height:32px;">
      <div>
        <p class="brand-title-custom">SMART JFT</p>
        <p class="brand-subtitle-custom">Digital Office &middot; Pusbin JFT</p>
      </div>
    </div>

    <nav class="sidebar-nav-custom" style="overflow-y:auto; flex:1;">
      <div class="sidebar-nav-container-custom">

        <div>
          <p class="category-header-custom">MENU</p>
          <ul class="sidebar-nav-list-custom">

            {{-- Akun (submenu berisi Logout, sama seperti sebelumnya) --}}
            <li>
              <button class="nav-sub-link" style="width:100%; justify-content:space-between; display:flex; align-items:center;" onclick="toggleSubmenu('akun')" type="button">
                <span style="display:flex; align-items:center; gap:12px;">
                  <span class="nav-icon"><i class="bi bi-person"></i></span>
                  Akun
                </span>
                <i class="bi bi-chevron-down chevron-icon" id="chevron-akun"></i>
              </button>
              <ul class="sidebar-nav-sub-list-custom hidden" id="submenu-akun">
                <li>
                  <a href="{{ route('logout') }}" class="nav-sub-link" onclick="pindah2(event)">
                    <span class="nav-dot"></span> Logout
                  </a>
                </li>
              </ul>
            </li>

            {{-- Dashboard - Semua Role --}}
            <li>
              <a href="{{ route('user.peta') }}" class="nav-link {{ request()->routeIs('user.peta') ? 'active' : '' }}" onclick="pindah(event)">
                <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                Dashboard
              </a>
            </li>
          </ul>
        </div>

        <div>
          <p class="category-header-custom">FITUR</p>
          <ul class="sidebar-nav-list-custom">

            {{-- Unit Kerja --}}
            @can('view unit kerja')
            <li>
              <a href="{{ route('user.unitkerja.index') }}" class="nav-link {{ request()->routeIs('user.unitkerja.*') ? 'active' : '' }}" onclick="pindah(event)">
                <span class="nav-icon"><i class="bi bi-building"></i></span>
                Unit Kerja
              </a>
            </li>
            @endcan

            {{-- Formasi --}}
            @can('view formasi')
            <li>
              <a href="{{ route('user.formasi.index') }}" class="nav-link {{ request()->routeIs('user.formasi.*') ? 'active' : '' }}" onclick="pindah(event)">
                <span class="nav-icon"><i class="bi bi-briefcase"></i></span>
                Formasi
              </a>
            </li>
            @endcan

            {{-- Pegawai JFT --}}
            @can('view pegawai')
            <li>
              <a href="{{ route('user.sdm.index') }}" class="nav-link {{ request()->routeIs('user.sdm.*') ? 'active' : '' }}" onclick="pindah(event)">
                <span class="nav-icon"><i class="bi bi-people"></i></span>
                Pegawai JFT
              </a>
            </li>
            @endcan

            {{-- Rekomendasi Formasi (RF-01) --}}
            @can('view rekomendasi formasi')
            <li>
              <a href="{{ route('user.rekomendasi-formasi.index') }}" class="nav-link {{ request()->routeIs('user.rekomendasi-formasi.*') ? 'active' : '' }}" onclick="pindah(event)">
                <span class="nav-icon"><i class="bi bi-calculator"></i></span>
                Rekomendasi Formasi
              </a>
            </li>
            @endcan

            {{-- Kompetensi JFT: semua role kecuali viewer --}}
            @hasanyrole('super_admin|admin|admin_unit|pemangku')
            @php
              $kompetensiActive = request()->routeIs('ujikom.jadwal.*')
                || request()->routeIs('ujikom.permohonan.*')
                || request()->routeIs('ujikom-online.*')
                || request()->routeIs('ujikom.hasil.*');
            @endphp
            <li>
              <button class="nav-sub-link" style="width:100%; justify-content:space-between; display:flex; align-items:center;" onclick="toggleSubmenu('kompetensi')" type="button">
                <span style="display:flex; align-items:center; gap:12px;">
                  <span class="nav-icon"><i class="bi bi-clipboard-check"></i></span>
                  Kompetensi JFT
                </span>
                <i class="bi bi-chevron-down chevron-icon {{ $kompetensiActive ? 'rotate-180' : '' }}" id="chevron-kompetensi"></i>
              </button>
              <ul class="sidebar-nav-sub-list-custom {{ $kompetensiActive ? '' : 'hidden' }}" id="submenu-kompetensi">
                {{-- Jadwal Ujikom: semua role --}}
                @can('view ujikom jadwal')
                <li>
                  <a href="{{ route('ujikom.jadwal.index') }}" class="nav-sub-link {{ request()->routeIs('ujikom.jadwal.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Pengumuman Jadwal
                  </a>
                </li>
                @endcan

                {{-- Pendaftaran Ujikom: semua kecuali viewer --}}
                @can('view ujikom permohonan')
                <li>
                  <a href="{{ route('ujikom.permohonan.index') }}" class="nav-sub-link {{ request()->routeIs('ujikom.permohonan.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Pendaftaran Ujikom
                  </a>
                </li>
                @endcan

                <li>
                  <a href="{{ route('ujikom-online.index') }}" class="nav-sub-link {{ request()->routeIs('ujikom-online.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Uji Kompetensi
                  </a>
                </li>

                @hasanyrole('super_admin|admin|admin_unit|pemangku')
                <li>
                  <a href="{{ auth()->user()->hasRole('pemangku') ? route('ujikom.hasil.riwayat') : route('ujikom.hasil.index') }}"
                     class="nav-sub-link {{ request()->routeIs('ujikom.hasil.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Hasil Uji Kompetensi
                  </a>
                </li>
                @endhasanyrole
              </ul>
            </li>
            @endhasanyrole

            {{-- Pengembangan Karir JFT: admin, super_admin, admin_unit --}}
            @hasanyrole('super_admin|admin|admin_unit')
            @php $karirActive = request()->routeIs('pengangkatan.*') || request()->routeIs('user.pkr.*') || request()->routeIs('karir.analitik.*') || request()->routeIs('karir.diklat.*'); @endphp
            <li>
              <button class="nav-sub-link" style="width:100%; justify-content:space-between; display:flex; align-items:center;" onclick="toggleSubmenu('karir')" type="button">
                <span style="display:flex; align-items:center; gap:12px;">
                  <span class="nav-icon"><i class="bi bi-graph-up"></i></span>
                  Pengembangan Karir JFT
                </span>
                <i class="bi bi-chevron-down chevron-icon {{ $karirActive ? 'rotate-180' : '' }}" id="chevron-karir"></i>
              </button>
              <ul class="sidebar-nav-sub-list-custom {{ $karirActive ? '' : 'hidden' }}" id="submenu-karir">
                <li>
                  <a href="{{ route('user.pkr.index') }}" class="nav-sub-link {{ request()->routeIs('user.pkr.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Tabel Pengembangan Karir
                  </a>
                </li>
                <li>
                  <a href="{{ route('karir.diklat.index') }}" class="nav-sub-link {{ request()->routeIs('karir.diklat.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Riwayat Diklat
                  </a>
                </li>
                <li>
                  <a href="{{ route('pengangkatan.index') }}" class="nav-sub-link {{ request()->routeIs('pengangkatan.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Pengangkatan JFT
                  </a>
                </li>
                <li>
                  <a href="{{ route('karir.analitik.index') }}" class="nav-sub-link {{ request()->routeIs('karir.analitik.*') ? 'active' : '' }}" onclick="pindah(event)">
                    <span class="nav-dot"></span> Analitik Pengembangan
                  </a>
                </li>
              </ul>
            </li>
            @endhasanyrole

          </ul>
        </div>

        {{-- ADMINISTRASI: hanya admin & super_admin --}}
        @hasanyrole('admin|super_admin')
        <div>
          <p class="category-header-custom">ADMINISTRASI</p>
          <ul class="sidebar-nav-list-custom">
            <li>
              <a href="{{ route('user.laporan.index') }}" class="nav-link {{ request()->routeIs('user.laporan.*') ? 'active' : '' }}" onclick="pindah(event)">
                <span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span>
                Laporan
              </a>
            </li>
            <li>
              <a href="{{ route('bank-soal.index') }}" class="nav-link {{ request()->routeIs('bank-soal.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="bi bi-database"></i></span>
                Bank Soal
              </a>
            </li>
            <li>
              <a href="{{ route('soal-kategori.index') }}" class="nav-link {{ request()->routeIs('soal-kategori.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="bi bi-tags"></i></span>
                Kategori Soal
              </a>
            </li>
            <li>
              <a href="{{ route('paket-ujian.index') }}" class="nav-link {{ request()->routeIs('paket-ujian.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="bi bi-box-seam"></i></span>
                Paket Ujian
              </a>
            </li>

            {{-- Manajemen User: hanya super_admin --}}
            @role('super_admin')
            <li>
              <a href="{{ route('user.manajemen-user.index') }}" class="nav-link {{ request()->routeIs('user.manajemen-user.*') ? 'active' : '' }}">
                <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
                Manajemen User
              </a>
            </li>
            @endrole
          </ul>
        </div>
        @endhasanyrole

      </div>
    </nav>
  </aside>

  {{-- Content --}}
  <div class="main-content">
    <section class="content" style="overflow-y:auto; flex:1;">
      <div class="container-fluid main-content-custom">
        @yield('isi')
        @include('layouts.component.alert')
      </div>
    </section>

    {{-- Footer --}}
    <footer class="main-footer" style="padding:12px 24px; background:#fff; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b; flex-shrink:0;">
      <strong>Copyright &copy; Pusbin JFT 2025</strong>
    </footer>
  </div>

</div>

{{-- ========== JS (hanya sekali & urut) ========== --}}
<script src="/library/plugins/jquery/jquery.min.js"></script>
<script src="/library/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/library/plugins/jquery-ui/jquery-ui.min.js"></script>
<script src="/library/dist/js/adminlte.js"></script>
<script src="{{ asset('library/dist/js/sweetalert2.all.min.js') }}"></script>

{{-- Toggle sidebar (mobile: geser masuk/keluar, desktop: collapse/expand) --}}
<script>
  function toggleSidebar() {
    const sidebar  = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (window.innerWidth >= 1024) {
      sidebar.classList.toggle('collapsed-desktop');
    } else {
      sidebar.classList.toggle('show-mobile');
      backdrop.classList.toggle('show');
    }
  }

  function toggleSubmenu(id) {
    const submenu = document.getElementById('submenu-' + id);
    const chevron = document.getElementById('chevron-' + id);
    submenu.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
  }
</script>

{{-- Plugins --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

{{-- Leaflet JS: dipush hanya oleh halaman yang membutuhkan peta --}}
@stack('leaflet')

{{-- Komponen map bawaan project (inisialisasi dengan existence check) --}}
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
