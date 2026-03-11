@extends('layouts.users.master')
@section('title', 'Laporan Terpadu')

@section('isi')
<div class="container-fluid">
  <h4 class="mb-3">Laporan Terpadu</h4>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <!-- Tabs Navigation -->
  <ul class="nav nav-tabs mb-3" id="laporanTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="dashboard-tab" data-toggle="tab" data-target="#dashboard" type="button" role="tab">
        <i class="fas fa-chart-line"></i> Dashboard
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="unit-kerja-tab" data-toggle="tab" data-target="#unit-kerja" type="button" role="tab">
        <i class="fas fa-building"></i> Unit Kerja
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="formasi-tab" data-toggle="tab" data-target="#formasi" type="button" role="tab">
        <i class="fas fa-sitemap"></i> Formasi
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="pegawai-tab" data-toggle="tab" data-target="#pegawai" type="button" role="tab">
        <i class="fas fa-users"></i> Pegawai JFT
      </button>
    </li>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content" id="laporanTabsContent">

    {{-- TAB 1: DASHBOARD --}}
    <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
      <div class="card">
        <div class="card-body">
          {{-- Filter --}}
          <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
              <label class="form-label">Tahun</label>
              <select name="tahun" class="form-select">
                <option value="">Semua Tahun</option>
                @foreach($tahuns as $t)
                  <option value="{{ $t }}" {{ (request('tahun') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Provinsi</label>
              <select name="province_id" id="provFilter" class="form-select">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" {{ (request('province_id') == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Kab/Kota</label>
              <select name="regency_id" id="regFilter" class="form-select">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" {{ (request('regency_id') == $r->id) ? 'selected' : '' }}>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button type="submit" class="btn btn-primary">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
          </form>

          {{-- Export Buttons --}}
          <div class="d-flex gap-2 mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'dashboard') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger">
              <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'dashboard') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success">
              <i class="fas fa-file-excel"></i> Export Excel
            </a>
          </div>

          {{-- Summary Cards --}}
          <div class="row mb-4">
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

          {{-- Charts --}}
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

          {{-- Table --}}
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="table-dark">
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
                    <td class="{{ $row['total_sisa'] < 0 ? 'text-danger fw-bold' : ($row['total_sisa'] == 0 ? 'text-warning fw-bold' : '') }}">
                      {{ number_format($row['total_sisa']) }}
                    </td>
                    <td>{{ number_format($row['jml_pegawai']) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- TAB 2: UNIT KERJA --}}
    <div class="tab-pane fade" id="unit-kerja" role="tabpanel">
      <div class="card">
        <div class="card-body">
          {{-- Filter --}}
          <form method="get" class="row g-3 mb-4">
            <input type="hidden" name="tab" value="unit-kerja">
            <div class="col-md-4">
              <label class="form-label">Provinsi</label>
              <select name="province_id" id="unitProvFilter" class="form-select">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" {{ (request('province_id') == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Kab/Kota</label>
              <select name="regency_id" id="unitRegFilter" class="form-select">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" {{ (request('regency_id') == $r->id) ? 'selected' : '' }}>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button type="submit" class="btn btn-primary">Terapkan</button>
              <a href="{{ route('user.laporan.index') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
          </form>

          {{-- Export Buttons --}}
          <div class="d-flex gap-2 mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'unit_kerja') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger">
              <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'unit_kerja') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success">
              <i class="fas fa-file-excel"></i> Export Excel
            </a>
          </div>

          {{-- Table --}}
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="table-dark">
                <tr>
                  <th style="width: 50px">No</th>
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
      </div>
    </div>

    {{-- TAB 3: FORMASI --}}
    <div class="tab-pane fade" id="formasi" role="tabpanel">
      <div class="card">
        <div class="card-body">
          {{-- Filter --}}
          <form method="get" class="row g-3 mb-4">
            <div class="col-md-2">
              <label class="form-label">Tahun</label>
              <select name="tahun" class="form-select">
                <option value="">Semua Tahun</option>
                @foreach($tahuns as $t)
                  <option value="{{ $t }}" {{ (request('tahun') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Provinsi</label>
              <select name="province_id" id="formasiProvFilter" class="form-select">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" {{ (request('province_id') == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Kab/Kota</label>
              <select name="regency_id" id="formasiRegFilter" class="form-select">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" {{ (request('regency_id') == $r->id) ? 'selected' : '' }}>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-select">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->no_rs }}" {{ (request('unit_kerja_id') == $u->no_rs) ? 'selected' : '' }}>{{ $u->nama_rumahsakit }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Jabatan</label>
              <input type="text" name="jabatan" class="form-control" value="{{ request('jabatan') }}" placeholder="Cari jabatan...">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="submit" class="btn btn-primary">Terapkan</button>
            </div>
            <div class="col-12">
              <a href="{{ route('user.laporan.index') }}" class="btn btn-secondary">Reset</a>
            </div>
          </form>

          {{-- Export Buttons --}}
          <div class="d-flex gap-2 mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'formasi') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger">
              <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'formasi') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success">
              <i class="fas fa-file-excel"></i> Export Excel
            </a>
          </div>

          {{-- Table --}}
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="table-dark">
                <tr>
                  <th rowspan="2" style="width: 50px">No</th>
                  <th rowspan="2">Unit Kerja</th>
                  <th rowspan="2">Nama Jabatan</th>
                  <th rowspan="2">Tahun</th>
                  <th colspan="10">Kuota</th>
                  <th colspan="10">Terisi</th>
                  <th colspan="10">Sisa</th>
                </tr>
                <tr>
                  @foreach($formasiData['cols'] as $c)
                    <th style="font-size: 10px">{{ $c }}</th>
                  @endforeach
                  <th>TOTAL</th>
                  @foreach($formasiData['cols'] as $c)
                    <th style="font-size: 10px">{{ $c }}</th>
                  @endforeach
                  <th>TOTAL</th>
                  @foreach($formasiData['cols'] as $c)
                    <th style="font-size: 10px">{{ $c }}</th>
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

                    {{-- Kuota --}}
                    @foreach($formasiData['cols'] as $c)
                      <td>{{ $row['kuota'][$c] }}</td>
                    @endforeach
                    <td><b>{{ array_sum($row['kuota']) }}</b></td>

                    {{-- Terisi --}}
                    @foreach($formasiData['cols'] as $c)
                      <td>{{ $row['terisi'][$c] }}</td>
                    @endforeach
                    <td><b>{{ array_sum($row['terisi']) }}</b></td>

                    {{-- Sisa --}}
                    @foreach($formasiData['cols'] as $c)
                      <td @class([
                        'text-danger fw-bold' => $row['sisa'][$c] < 0,
                        'text-warning fw-bold' => $row['sisa'][$c] == 0
                      ])>{{ $row['sisa'][$c] }}</td>
                    @endforeach
                    <td @class([
                      'text-danger fw-bold' => array_sum($row['sisa']) < 0,
                      'text-warning fw-bold' => array_sum($row['sisa']) == 0
                    ])><b>{{ array_sum($row['sisa']) }}</b></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- TAB 4: PEGAWAI JFT --}}
    <div class="tab-pane fade" id="pegawai" role="tabpanel">
      <div class="card">
        <div class="card-body">
          {{-- Filter --}}
          <form method="get" class="row g-3 mb-4">
            <div class="col-md-2">
              <label class="form-label">Tahun</label>
              <select name="tahun" class="form-select">
                <option value="">Semua Tahun</option>
                @foreach($tahuns as $t)
                  <option value="{{ $t }}" {{ (request('tahun') == $t) ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Unit Kerja</label>
              <select name="unit_kerja_id" class="form-select">
                <option value="">Semua Unit Kerja</option>
                @foreach($unitKerja as $u)
                  <option value="{{ $u->no_rs }}" {{ (request('unit_kerja_id') == $u->no_rs) ? 'selected' : '' }}>{{ $u->nama_rumahsakit }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Jabatan</label>
              <input type="text" name="jabatan" class="form-control" value="{{ request('jabatan') }}" placeholder="Cari jabatan...">
            </div>
            <div class="col-md-2">
              <label class="form-label">Jenjang</label>
              <select name="jenjang" class="form-select">
                <option value="">Semua Jenjang</option>
                @foreach($jenjangs as $j)
                  <option value="{{ $j->id }}" {{ (request('jenjang') == $j->id) ? 'selected' : '' }}>{{ $j->nama_jenjang }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Status Formasi</label>
              <select name="status_formasi" class="form-select">
                <option value="">Semua Status</option>
                <option value="terpenuhi" {{ (request('status_formasi') == 'terpenuhi') ? 'selected' : '' }}>Terpenuhi</option>
                <option value="di_luar_formasi" {{ (request('status_formasi') == 'di_luar_formasi') ? 'selected' : '' }}>Di Luar Formasi</option>
              </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="submit" class="btn btn-primary">Terapkan</button>
            </div>
            <div class="col-12">
              <a href="{{ route('user.laporan.index') }}" class="btn btn-secondary">Reset</a>
            </div>
          </form>

          {{-- Export Buttons --}}
          <div class="d-flex gap-2 mb-4">
            <a href="{{ route('user.laporan.export-pdf', 'pegawai') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-danger">
              <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('user.laporan.export-excel', 'pegawai') }}?{{ http_build_query(request()->query()) }}"
               class="btn btn-success">
              <i class="fas fa-file-excel"></i> Export Excel
            </a>
          </div>

          {{-- Table --}}
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="table-dark">
                <tr>
                  <th style="width: 50px">No</th>
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
                        <span class="badge bg-danger">Di Luar Formasi</span>
                      @elseif($row['status_formasi'] === 'terpenuhi')
                        <span class="badge bg-success">Terpenuhi</span>
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
        {
          label: 'Kuota',
          data: kuotaData,
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        },
        {
          label: 'Terisi',
          data: terisiData,
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true }
      }
    }
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
          'rgba(255, 99, 132, 0.6)',
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)',
          'rgba(255, 159, 64, 0.6)',
          'rgba(199, 199, 199, 0.6)',
          'rgba(83, 102, 255, 0.6)'
        ]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'right' }
      }
    }
  });
}
@endif

// Regency filter for each tab
(function() {
  // Dashboard tab
  const provFilter = document.getElementById('provFilter');
  const regFilter = document.getElementById('regFilter');

  if (provFilter && regFilter) {
    const baseUrl = {{ route('user.wilayah.regencies', ['province' => '__PID__']) }};

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
      (data || []).forEach(r => {
        html += `<option value="${r.id}">${r.type} ${r.name}</option>`;
      });
      regFilter.innerHTML = html;
    });
  }

  // Unit Kerja tab
  const unitProvFilter = document.getElementById('unitProvFilter');
  const unitRegFilter = document.getElementById('unitRegFilter');

  if (unitProvFilter && unitRegFilter) {
    const baseUrl = {{ route('user.wilayah.regencies', ['province' => '__PID__']) }};

    unitProvFilter.addEventListener('change', async function() {
      const pid = this.value;
      if (!pid) {
        unitRegFilter.innerHTML = '<option value="">Semua Kab/Kota</option>';
        return;
      }

      const url = baseUrl.replace('__PID__', pid);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();

      let html = '<option value="">Semua Kab/Kota</option>';
      (data || []).forEach(r => {
        html += `<option value="${r.id}">${r.type} ${r.name}</option>`;
      });
      unitRegFilter.innerHTML = html;
    });
  }

  // Formasi tab
  const formasiProvFilter = document.getElementById('formasiProvFilter');
  const formasiRegFilter = document.getElementById('formasiRegFilter');

  if (formasiProvFilter && formasiRegFilter) {
    const baseUrl = {{ route('user.wilayah.regencies', ['province' => '__PID__']) }};

    formasiProvFilter.addEventListener('change', async function() {
      const pid = this.value;
      if (!pid) {
        formasiRegFilter.innerHTML = '<option value="">Semua Kab/Kota</option>';
        return;
      }

      const url = baseUrl.replace('__PID__', pid);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();

      let html = '<option value="">Semua Kab/Kota</option>';
      (data || []).forEach(r => {
        html += `<option value="${r.id}">${r.type} ${r.name}</option>`;
      });
      formasiRegFilter.innerHTML = html;
    });
  }
})();
</script>
@endpush
