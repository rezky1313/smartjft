@extends('layouts.users.master')
@section('title', 'Laporan Terpadu')

@section('isi')
<div class="container-fluid">

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="preview-card mb-4">
    <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <span class="preview-header-title">Laporan Terpadu</span>
        <span class="preview-header-subtitle d-block">Rekap data JFT nasional lintas modul: Dashboard, Unit Kerja, Formasi, Pegawai JFT, Uji Kompetensi, Pengangkatan JFT, dan Pendaftaran Ujikom</span>
      </div>
    </div>

    <div class="preview-body p-0">
      <ul class="nav nav-tabs px-3 pt-3" id="laporanTabs" role="tablist" style="border-bottom:1px solid #e2e8f0;">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="dashboard-tab" data-toggle="tab" data-target="#dashboard" type="button" role="tab">
            <i class="fas fa-chart-line mr-1"></i> Dashboard
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="unit-kerja-tab" data-toggle="tab" data-target="#unit-kerja" type="button" role="tab">
            <i class="fas fa-building mr-1"></i> Unit Kerja
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="formasi-tab" data-toggle="tab" data-target="#formasi" type="button" role="tab">
            <i class="fas fa-sitemap mr-1"></i> Formasi
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="pegawai-tab" data-toggle="tab" data-target="#pegawai" type="button" role="tab">
            <i class="fas fa-users mr-1"></i> Pegawai JFT
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="ujikom-tab" data-toggle="tab" data-target="#ujikom" type="button" role="tab">
            <i class="fas fa-file-signature mr-1"></i> Uji Kompetensi
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="pengangkatan-tab" data-toggle="tab" data-target="#pengangkatan" type="button" role="tab">
            <i class="fas fa-user-tie mr-1"></i> Pengangkatan JFT
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="pendaftaran-tab" data-toggle="tab" data-target="#pendaftaran" type="button" role="tab">
            <i class="fas fa-clipboard-list mr-1"></i> Pendaftaran Ujikom
          </button>
        </li>
      </ul>

      <div class="tab-content p-3" id="laporanTabsContent">

        {{-- ============================================================ --}}
        {{-- TAB 1: DASHBOARD --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Tahun</label>
              <select name="tahun" class="form-control form-control-sm" style="width:150px;">
                <option value="">Semua Tahun</option>
                @foreach($tahuns as $t)
                  <option value="{{ $t }}" {{ (request('tahun') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Provinsi</label>
              <select name="province_id" id="provFilter" class="form-control form-control-sm" style="width:200px;">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" {{ (request('province_id') == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Kab/Kota</label>
              <select name="regency_id" id="regFilter" class="form-control form-control-sm" style="width:200px;">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" {{ (request('regency_id') == $r->id) ? 'selected' : '' }}>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'dashboard') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'dashboard') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="row mb-2">
            <div class="col-md-3 mb-3">
              <div class="card card-primary card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Unit Kerja</h5>
                  <h3>{{ number_format($dashboardData['summary']['total_unit_kerja'] ?? 0) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card card-success card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Kuota</h5>
                  <h3>{{ number_format($dashboardData['summary']['total_kuota'] ?? 0) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card card-info card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Terisi</h5>
                  <h3>{{ number_format($dashboardData['summary']['total_terisi'] ?? 0) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card card-warning card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Sisa</h5>
                  <h3>{{ number_format($dashboardData['summary']['total_sisa'] ?? 0) }}</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-4 mb-3">
              <div class="card card-info card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Pegawai</h5>
                  <h3>{{ number_format($dashboardData['summary']['total_pegawai'] ?? 0) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card card-danger card-outline">
                <div class="card-body">
                  <h5 class="card-title">Di Luar Formasi</h5>
                  <h3>{{ number_format($dashboardData['summary']['total_di_luar_formasi'] ?? 0) }}</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Perbandingan Kuota vs Terisi per Provinsi</h5>
                </div>
                <div class="card-body">
                  <canvas id="chartProvinsi" height="250"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Distribusi Pegawai per Jenjang</h5>
                </div>
                <div class="card-body">
                  <canvas id="chartJenjang" height="250"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="thead-dark">
                <tr>
                  <th>No</th>
                  <th>Provinsi</th>
                  <th>Jml Unit Kerja</th>
                  <th>Kuota</th>
                  <th>Terisi</th>
                  <th>Sisa</th>
                  <th>Jml Pegawai</th>
                </tr>
              </thead>
              <tbody>
                @foreach($dashboardData['province_summary'] as $i => $row)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['province'] }}</td>
                    <td>{{ number_format($row['jml_unit_kerja']) }}</td>
                    <td>{{ number_format($row['total_kuota']) }}</td>
                    <td>{{ number_format($row['total_terisi']) }}</td>
                    <td class="{{ $row['total_sisa'] < 0 ? 'text-danger font-weight-bold' : ($row['total_sisa'] == 0 ? 'text-warning font-weight-bold' : '') }}">
                      {{ number_format($row['total_sisa']) }}
                    </td>
                    <td>{{ number_format($row['jml_pegawai']) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 2: UNIT KERJA --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="unit-kerja" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <input type="hidden" name="tab" value="unit-kerja">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Provinsi</label>
              <select name="province_id" id="unitProvFilter" class="form-control form-control-sm" style="width:200px;">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" {{ (request('province_id') == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Kab/Kota</label>
              <select name="regency_id" id="unitRegFilter" class="form-control form-control-sm" style="width:200px;">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" {{ (request('regency_id') == $r->id) ? 'selected' : '' }}>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'unit_kerja') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'unit_kerja') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="thead-dark">
                <tr>
                  <th style="width:50px">No</th>
                  <th>Nama Unit Kerja</th>
                  <th>Jenis UPT</th>
                  <th>Provinsi</th>
                  <th>Kab/Kota</th>
                  <th>Jumlah Jabatan Formasi</th>
                  <th>Jumlah Pegawai</th>
                </tr>
              </thead>
              <tbody>
                @foreach($unitKerjaData as $i => $row)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['nama_unit_kerja'] }}</td>
                    <td>{{ $row['jenis_upt'] }}</td>
                    <td>{{ $row['provinsi'] }}</td>
                    <td>{{ $row['kab_kota'] }}</td>
                    <td>{{ number_format($row['jumlah_jabatan_formasi']) }}</td>
                    <td>{{ number_format($row['jumlah_pegawai']) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 3: FORMASI --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="formasi" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Tahun</label>
              <select name="tahun" class="form-control form-control-sm" style="width:120px;">
                <option value="">Semua Tahun</option>
                @foreach($tahuns as $t)
                  <option value="{{ $t }}" {{ (request('tahun') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Provinsi</label>
              <select name="province_id" id="formasiProvFilter" class="form-control form-control-sm" style="width:180px;">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" {{ (request('province_id') == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Kab/Kota</label>
              <select name="regency_id" id="formasiRegFilter" class="form-control form-control-sm" style="width:180px;">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" {{ (request('regency_id') == $r->id) ? 'selected' : '' }}>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-control form-control-sm" style="width:220px;">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->id }}" {{ (request('unit_kerja_id') == $u->id) ? 'selected' : '' }}>{{ $u->nama_unit_kerja }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Jabatan</label>
              <input type="text" name="jabatan" class="form-control form-control-sm" style="width:180px;" value="{{ request('jabatan') }}" placeholder="Cari jabatan...">
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'formasi') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'formasi') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark">
                <tr>
                  <th rowspan="2" style="width:50px">No</th>
                  <th rowspan="2">Unit Kerja</th>
                  <th rowspan="2">Nama Jabatan</th>
                  <th rowspan="2">Tahun</th>
                  <th colspan="9">Kuota</th>
                  <th colspan="9">Terisi</th>
                  <th colspan="9">Sisa</th>
                </tr>
                <tr>
                  @foreach($formasiData['cols'] as $c)
                    <th style="font-size:10px">{{ $c }}</th>
                  @endforeach
                  <th>TOTAL</th>
                  @foreach($formasiData['cols'] as $c)
                    <th style="font-size:10px">{{ $c }}</th>
                  @endforeach
                  <th>TOTAL</th>
                  @foreach($formasiData['cols'] as $c)
                    <th style="font-size:10px">{{ $c }}</th>
                  @endforeach
                  <th>TOTAL</th>
                </tr>
              </thead>
              <tbody>
                @foreach($formasiData['data'] as $i => $row)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['unit_kerja'] }}</td>
                    <td>{{ $row['nama_jabatan'] }}</td>
                    <td>{{ $row['tahun'] }}</td>

                    @foreach($formasiData['cols'] as $c)
                      <td>{{ $row['kuota'][$c] }}</td>
                    @endforeach
                    <td><b>{{ array_sum($row['kuota']) }}</b></td>

                    @foreach($formasiData['cols'] as $c)
                      <td>{{ $row['terisi'][$c] }}</td>
                    @endforeach
                    <td><b>{{ array_sum($row['terisi']) }}</b></td>

                    @foreach($formasiData['cols'] as $c)
                      <td @class([
                        'text-danger font-weight-bold' => $row['sisa'][$c] < 0,
                        'text-warning font-weight-bold' => $row['sisa'][$c] == 0
                      ])>{{ $row['sisa'][$c] }}</td>
                    @endforeach
                    <td @class([
                      'text-danger font-weight-bold' => array_sum($row['sisa']) < 0,
                      'text-warning font-weight-bold' => array_sum($row['sisa']) == 0
                    ])><b>{{ array_sum($row['sisa']) }}</b></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 4: PEGAWAI JFT --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="pegawai" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Tahun</label>
              <select name="tahun" class="form-control form-control-sm" style="width:120px;">
                <option value="">Semua Tahun</option>
                @foreach($tahuns as $t)
                  <option value="{{ $t }}" {{ (request('tahun') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-control form-control-sm" style="width:220px;">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->id }}" {{ (request('unit_kerja_id') == $u->id) ? 'selected' : '' }}>{{ $u->nama_unit_kerja }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Jabatan</label>
              <input type="text" name="jabatan" class="form-control form-control-sm" style="width:160px;" value="{{ request('jabatan') }}" placeholder="Cari jabatan...">
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Jenjang</label>
              <select name="jenjang" class="form-control form-control-sm" style="width:160px;">
                <option value="">Semua Jenjang</option>
                @foreach($jenjangs as $j)
                  <option value="{{ $j->id }}" {{ (request('jenjang') == $j->id) ? 'selected' : '' }}>{{ $j->nama_jenjang }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Status Formasi</label>
              <select name="status_formasi" class="form-control form-control-sm" style="width:160px;">
                <option value="">Semua Status</option>
                <option value="terpenuhi" {{ (request('status_formasi') == 'terpenuhi') ? 'selected' : '' }}>Terpenuhi</option>
                <option value="di_luar_formasi" {{ (request('status_formasi') == 'di_luar_formasi') ? 'selected' : '' }}>Di Luar Formasi</option>
              </select>
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'pegawai') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'pegawai') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark">
                <tr>
                  <th style="width:50px">No</th>
                  <th>Nama Pegawai</th>
                  <th>NIP</th>
                  <th>Jabatan</th>
                  <th>Jenjang</th>
                  <th>Unit Kerja</th>
                  <th>Provinsi</th>
                  <th>Kab/Kota</th>
                  <th>TMT Jabatan</th>
                  <th>Status Formasi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pegawaiData as $i => $row)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['nip'] }}</td>
                    <td>{{ $row['jabatan'] }}</td>
                    <td>{{ $row['jenjang'] }}</td>
                    <td>{{ $row['unit_kerja'] }}</td>
                    <td>{{ $row['provinsi'] }}</td>
                    <td>{{ $row['kab_kota'] }}</td>
                    <td>{{ $row['tmt_jabatan'] }}</td>
                    <td>
                      @if($row['status_formasi'] === 'di_luar_formasi')
                        <span class="do-badge" style="background:#fee2e2; color:#991b1b;">Di Luar Formasi</span>
                      @elseif($row['status_formasi'] === 'terpenuhi')
                        <span class="do-badge" style="background:#d1fae5; color:#065f46;">Terpenuhi</span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 5: UJI KOMPETENSI --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="ujikom" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Jadwal Ujikom</label>
              <select name="jadwal_id" class="form-control form-control-sm" style="width:220px;">
                <option value="">Semua Jadwal</option>
                @foreach($jadwalList as $j)
                  <option value="{{ $j->id }}" {{ (request('jadwal_id') == $j->id) ? 'selected' : '' }}>{{ $j->judul }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Tahun</label>
              <select name="tahun_ujikom" class="form-control form-control-sm" style="width:120px;">
                <option value="">Semua Tahun</option>
                @foreach($tahunsUjikom as $t)
                  <option value="{{ $t }}" {{ (request('tahun_ujikom') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Jenjang</label>
              <select name="jenjang_ujikom" class="form-control form-control-sm" style="width:160px;">
                <option value="">Semua Jenjang</option>
                @foreach($jenjangUjikomOptions as $val => $label)
                  <option value="{{ $val }}" {{ (request('jenjang_ujikom') == $val) ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-control form-control-sm" style="width:220px;">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->id }}" {{ (request('unit_kerja_id') == $u->id) ? 'selected' : '' }}>{{ $u->nama_unit_kerja }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'ujikom') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'ujikom') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="row mb-2">
            <div class="col-md-3 mb-3">
              <div class="card card-primary card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Jadwal</h5>
                  <h3>{{ number_format($ujikomData['summary']['total_jadwal']) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card card-info card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Peserta</h5>
                  <h3>{{ number_format($ujikomData['summary']['total_peserta']) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card card-success card-outline">
                <div class="card-body">
                  <h5 class="card-title">Tingkat Kelulusan</h5>
                  <h3>{{ $ujikomData['summary']['tingkat_kelulusan'] }}%</h3>
                </div>
              </div>
            </div>
            <div class="col-md-3 mb-3">
              <div class="card card-dark card-outline">
                <div class="card-body">
                  <h5 class="card-title">Terindikasi Kecurangan</h5>
                  <h3>{{ number_format($ujikomData['summary']['terindikasi_kecurangan']) }} <small style="font-size:14px;">sesi</small></h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-7">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Tren Kelulusan per Periode (%)</h5>
                </div>
                <div class="card-body">
                  <canvas id="chartTrenKelulusan" height="220"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-5">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Rata-rata Nilai per Aspek</h5>
                </div>
                <div class="card-body p-0">
                  <table class="table table-sm table-bordered mb-0 text-center">
                    <thead class="thead-light">
                      <tr><th class="text-left">Kompetensi</th><th>CAT</th><th>Wawancara</th><th>Presentasi</th></tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="text-left">Teknis</td>
                        <td>{{ $ujikomData['aspek']['teknis_cat'] }}</td>
                        <td>{{ $ujikomData['aspek']['teknis_wawancara'] }}</td>
                        <td>{{ $ujikomData['aspek']['teknis_presentasi'] }}</td>
                      </tr>
                      <tr>
                        <td class="text-left">Mansoskul</td>
                        <td>{{ $ujikomData['aspek']['mansoskul_cat'] }}</td>
                        <td>{{ $ujikomData['aspek']['mansoskul_wawancara'] }}</td>
                        <td>{{ $ujikomData['aspek']['mansoskul_presentasi'] }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="p-3 border-top">
                    <span class="subsection-label mb-1">Rata-rata Nilai per Kompetensi</span>
                    <div class="d-flex">
                      <div class="mr-4"><strong>Teknis:</strong> {{ $ujikomData['kompetensi']['teknis'] }}</div>
                      <div><strong>Mansoskul:</strong> {{ $ujikomData['kompetensi']['mansoskul'] }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark">
                <tr>
                  <th style="width:50px">No</th>
                  <th>Nama Jadwal</th>
                  <th>Jenjang</th>
                  <th>Jml Peserta</th>
                  <th>Lulus</th>
                  <th>Tidak Lulus</th>
                  <th>Belum Dinilai</th>
                  <th>Rata-rata Nilai</th>
                  <th>Kecurangan</th>
                </tr>
              </thead>
              <tbody>
                @forelse($ujikomData['per_jadwal'] as $i => $row)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['jadwal'] }}</td>
                    <td>{{ $row['jenjang'] }}</td>
                    <td>{{ $row['jumlah_peserta'] }}</td>
                    <td>{{ $row['lulus'] }}</td>
                    <td>{{ $row['tidak_lulus'] }}</td>
                    <td>{{ $row['belum_dinilai'] }}</td>
                    <td>{{ $row['rata_nilai'] }}</td>
                    <td>
                      @if($row['kecurangan'] > 0)
                        <span class="do-badge" style="background:#111827; color:#fff;">{{ $row['kecurangan'] }} sesi</span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 6: PENGANGKATAN JFT --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="pengangkatan" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Tahun</label>
              <select name="tahun_pengangkatan" class="form-control form-control-sm" style="width:120px;">
                <option value="">Semua Tahun</option>
                @foreach($tahunsPengangkatan as $t)
                  <option value="{{ $t }}" {{ (request('tahun_pengangkatan') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-control form-control-sm" style="width:220px;">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->id }}" {{ (request('unit_kerja_id') == $u->id) ? 'selected' : '' }}>{{ $u->nama_unit_kerja }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Jabatan</label>
              <input type="text" name="jabatan" class="form-control form-control-sm" style="width:180px;" value="{{ request('jabatan') }}" placeholder="Cari jabatan...">
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="alert alert-light border mb-3" style="font-size:12px;">
            <i class="fas fa-info-circle mr-1"></i>
            Filter/breakdown "Jalur Pengangkatan" tidak tersedia -- kolom tersebut sudah dihapus total dari skema sejak penyederhanaan alur Pengangkatan JFT (v1.14.0).
          </div>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'pengangkatan') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'pengangkatan') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="row mb-4">
            <div class="col-md-4 mb-3">
              <div class="card card-primary card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Permohonan</h5>
                  <h3>{{ number_format($pengangkatanData['summary']['total_permohonan']) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card card-success card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Pegawai Diangkat</h5>
                  <h3>{{ number_format($pengangkatanData['summary']['total_diangkat']) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card card-info card-outline">
                <div class="card-body">
                  <h5 class="card-title">Rata-rata Waktu Proses</h5>
                  <h3>{{ $pengangkatanData['summary']['rata_waktu_proses_hari'] !== null ? $pengangkatanData['summary']['rata_waktu_proses_hari'].' hari' : '-' }}</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Tren Jumlah Pengangkatan per Tahun</h5>
                </div>
                <div class="card-body">
                  <canvas id="chartTrenPengangkatan" height="180"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark">
                <tr>
                  <th style="width:50px">No</th>
                  <th>Unit Kerja</th>
                  <th>Jabatan</th>
                  <th>Jenjang</th>
                  <th>Jumlah Diangkat</th>
                </tr>
              </thead>
              <tbody>
                @php $no = 1; @endphp
                @forelse($pengangkatanData['rekap_unit'] as $unit)
                  @if(empty($unit['rincian']))
                    <tr class="text-muted">
                      <td>{{ $no++ }}</td>
                      <td>{{ $unit['unit_kerja'] }}</td>
                      <td colspan="3">Tidak ada pengangkatan selesai pada periode ini</td>
                    </tr>
                  @else
                    @foreach($unit['rincian'] as $r)
                      <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ $unit['unit_kerja'] }}</td>
                        <td>{{ $r['jabatan'] }}</td>
                        <td>{{ $r['jenjang'] }}</td>
                        <td>{{ $r['jumlah'] }}</td>
                      </tr>
                    @endforeach
                  @endif
                @empty
                  <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 7: PENDAFTARAN UJIKOM --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="pendaftaran" role="tabpanel">

          <form method="get" class="d-flex flex-wrap align-items-end mb-3">
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Tahun</label>
              <select name="tahun_pendaftaran" class="form-control form-control-sm" style="width:120px;">
                <option value="">Semua Tahun</option>
                @foreach($tahunsPendaftaran as $t)
                  <option value="{{ $t }}" {{ (request('tahun_pendaftaran') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="mr-2 mb-2">
              <label class="subsection-label mb-1">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-control form-control-sm" style="width:220px;">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->id }}" {{ (request('unit_kerja_id') == $u->id) ? 'selected' : '' }}>{{ $u->nama_unit_kerja }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
          </form>

          <div class="mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'pendaftaran') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger btn-sm mr-2">
              <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'pendaftaran') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success btn-sm">
              <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>
          </div>

          <div class="row mb-2">
            <div class="col-md-4 mb-3">
              <div class="card card-primary card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Permohonan</h5>
                  <h3>{{ number_format($pendaftaranData['summary']['total_permohonan']) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card card-danger card-outline">
                <div class="card-body">
                  <h5 class="card-title">Total Ditolak</h5>
                  <h3>{{ number_format($pendaftaranData['summary']['total_ditolak']) }}</h3>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="card card-warning card-outline">
                <div class="card-body">
                  <h5 class="card-title">Tingkat Penolakan</h5>
                  <h3>{{ $pendaftaranData['summary']['tingkat_penolakan'] }}%</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Breakdown per Status</h5>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 text-center">
                      <thead class="thead-light">
                        <tr>
                          @foreach($pendaftaranData['per_status'] as $row)
                            <th>{{ $row['label'] }}</th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          @foreach($pendaftaranData['per_status'] as $row)
                            <td>{{ $row['jumlah'] }}</td>
                          @endforeach
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-warning mb-4" style="font-size:12px;">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Keterbatasan data:</strong> Rata-rata waktu verifikasi per tahap (Admin Unit / Admin Pusbin) tidak dapat dihitung akurat
            karena tabel pendaftaran hanya menyimpan status terakhir beserta <em>created_at</em>/<em>updated_at</em>, tanpa timestamp
            di setiap transisi status. Metrik di bawah ini hanya yang bisa dihitung valid dari data yang tersedia.
          </div>

          <h6 class="font-weight-bold mb-2">Permohonan Belum Selesai (diurutkan dari paling lama menunggu)</h6>
          <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark">
                <tr>
                  <th style="width:50px">No</th>
                  <th>Kode</th>
                  <th>Unit Kerja</th>
                  <th>Jadwal</th>
                  <th>Status</th>
                  <th>Menunggu Sejak</th>
                  <th>Jumlah Hari</th>
                </tr>
              </thead>
              <tbody>
                @forelse($pendaftaranData['nyangkut'] as $i => $row)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['kode'] }}</td>
                    <td>{{ $row['unit_kerja'] }}</td>
                    <td>{{ $row['jadwal'] }}</td>
                    <td><span class="do-badge" style="background:#e0e7ff; color:#3730a3;">{{ $row['status'] }}</span></td>
                    <td>{{ $row['menunggu_sejak']->format('d-m-Y') }}</td>
                    <td class="{{ $row['jumlah_hari'] > 14 ? 'text-danger font-weight-bold' : '' }}">{{ $row['jumlah_hari'] }} hari</td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center text-muted">Tidak ada permohonan yang tertunda</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if($pendaftaranData['catatan_penolakan']->isNotEmpty())
          <h6 class="font-weight-bold mb-2">Catatan Penolakan</h6>
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark">
                <tr>
                  <th>Kode</th>
                  <th>Unit Kerja</th>
                  <th>Status</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pendaftaranData['catatan_penolakan'] as $row)
                  <tr>
                    <td>{{ $row['kode'] }}</td>
                    <td>{{ $row['unit_kerja'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['catatan'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @endif
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js for Dashboard tab
const ctxProv = document.getElementById('chartProvinsi');
const ctxJen = document.getElementById('chartJenjang');

@if(isset($dashboardData['province_summary']) && count($dashboardData['province_summary']) > 0)
if (ctxProv) {
  const provinceLabels = @json(collect($dashboardData['province_summary'])->pluck('province'));
  const kuotaData = @json(collect($dashboardData['province_summary'])->pluck('total_kuota'));
  const terisiData = @json(collect($dashboardData['province_summary'])->pluck('total_terisi'));

  new Chart(ctxProv, {
    type: 'bar',
    data: {
      labels: provinceLabels,
      datasets: [
        { label: 'Kuota', data: kuotaData, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 },
        { label: 'Terisi', data: terisiData, backgroundColor: 'rgba(75, 192, 192, 0.6)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 }
      ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });
}
@endif

@if(isset($dashboardData['jenjang_distribution']))
if (ctxJen) {
  const jenjangLabels = @json(array_keys($dashboardData['jenjang_distribution']));
  const jenjangData = @json(array_values($dashboardData['jenjang_distribution']));

  new Chart(ctxJen, {
    type: 'pie',
    data: {
      labels: jenjangLabels,
      datasets: [{
        data: jenjangData,
        backgroundColor: [
          'rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)', 'rgba(199, 199, 199, 0.6)', 'rgba(83, 102, 255, 0.6)'
        ]
      }]
    },
    options: { responsive: true, plugins: { legend: { position: 'right' } } }
  });
}
@endif

// Chart Tab 5: Tren Kelulusan
const ctxTrenKelulusan = document.getElementById('chartTrenKelulusan');
@if(isset($ujikomData['tren']) && count($ujikomData['tren']) > 0)
if (ctxTrenKelulusan) {
  new Chart(ctxTrenKelulusan, {
    type: 'line',
    data: {
      labels: @json(array_keys($ujikomData['tren'])),
      datasets: [{
        label: 'Tingkat Kelulusan (%)',
        data: @json(array_values($ujikomData['tren'])),
        borderColor: 'rgba(40, 167, 69, 1)',
        backgroundColor: 'rgba(40, 167, 69, 0.15)',
        fill: true,
        tension: 0.3
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
  });
}
@endif

// Chart Tab 6: Tren Pengangkatan
const ctxTrenPengangkatan = document.getElementById('chartTrenPengangkatan');
@if(isset($pengangkatanData['tren']) && count($pengangkatanData['tren']) > 0)
if (ctxTrenPengangkatan) {
  new Chart(ctxTrenPengangkatan, {
    type: 'bar',
    data: {
      labels: @json(array_keys($pengangkatanData['tren'])),
      datasets: [{
        label: 'Jumlah Diangkat',
        data: @json(array_values($pengangkatanData['tren'])),
        backgroundColor: 'rgba(23, 162, 184, 0.6)',
        borderColor: 'rgba(23, 162, 184, 1)',
        borderWidth: 1
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });
}
@endif

// Regency cascading filter per tab
(function() {
  const baseUrl = @json(route('user.wilayah.regencies', ['province' => '__PID__']));

  function wireCascade(provId, regId) {
    const provFilter = document.getElementById(provId);
    const regFilter = document.getElementById(regId);
    if (!provFilter || !regFilter) return;

    provFilter.addEventListener('change', async function() {
      const pid = this.value;
      if (!pid) {
        regFilter.innerHTML = '<option value="">Semua Kab/Kota</option>';
        return;
      }
      const url = baseUrl.replace('__PID__', pid);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      let html = '<option value="">Semua Kab/Kota</option>';
      (data || []).forEach(r => { html += `<option value="${r.id}">${r.type} ${r.name}</option>`; });
      regFilter.innerHTML = html;
    });
  }

  wireCascade('provFilter', 'regFilter');
  wireCascade('unitProvFilter', 'unitRegFilter');
  wireCascade('formasiProvFilter', 'formasiRegFilter');
})();
</script>
@endpush
