@extends('layouts.users.master')
@include('layouts.component.leaflet-assets')

@section('title')
@if (Auth::user()->role =='user') Pusbin JFT - ADMIN @else Pusbin JFT - USER @endif
@endsection

@section('isi')
<div class="container-fluid">

  {{-- ============ DASHBOARD PEMANGKU ============ --}}
  @role('pemangku')
  <h3 class="mb-4 fw-bold">Dashboard Saya</h3>

  {{-- Profil Pemangku --}}
  @if ($sdm)
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <h5 class="fw-semibold mb-3"><i class="fas fa-user-circle text-primary mr-2"></i>Profil Pemangku JFT</h5>
      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm table-borderless mb-0">
            <tr><td width="140" class="text-muted">Nama Lengkap</td><td>: <strong>{{ $sdm->nama_lengkap ?? '-' }}</strong></td></tr>
            <tr><td class="text-muted">NIP</td><td>: {{ $sdm->nip ?? '-' }}</td></tr>
            <tr><td class="text-muted">Jabatan</td><td>: {{ $sdm->formasiJabatan->nama_formasi ?? '-' }}</td></tr>
            <tr><td class="text-muted">Jenjang</td><td>: {{ $sdm->formasiJabatan->jenjang->nama_jenjang ?? '-' }}</td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm table-borderless mb-0">
            <tr><td width="140" class="text-muted">Pangkat/Gol.</td><td>: {{ $sdm->pangkat_golongan ?? '-' }}</td></tr>
            <tr><td class="text-muted">Unit Kerja</td><td>: {{ $sdm->unitKerja->nama_rumahsakit ?? '-' }}</td></tr>
            <tr><td class="text-muted">Status</td><td>: <span class="badge badge-success">Aktif</span></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>
  @else
  <div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Data profil pegawai belum terhubung ke akun ini. Hubungi admin.
  </div>
  @endif

  {{-- Jadwal Ujikon Terdekat --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
      <h6 class="mb-0"><i class="fas fa-calendar-alt text-primary mr-2"></i>Jadwal Uji Kompetensi Terdekat</h6>
    </div>
    <div class="card-body p-0">
      @if ($jadwalTerdekat->isEmpty())
        <div class="text-center text-muted py-4">
          <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
          Belum ada jadwal ujian yang dipublikasikan.
        </div>
      @else
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Judul Ujian</th>
              <th width="120" class="text-center">Tanggal</th>
              <th width="80" class="text-center">Jenis</th>
              <th width="100" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($jadwalTerdekat as $jadwal)
            <tr>
              <td><strong>{{ $jadwal->judul }}</strong><br><small class="text-muted">{{ $jadwal->tempat ?? '-' }}</small></td>
              <td class="text-center"><small>{{ $jadwal->tanggal_mulai?->format('d M Y') ?? '-' }}</small></td>
              <td class="text-center">
                <span class="badge badge-{{ $jadwal->jenis_ujian === 'online' ? 'primary' : 'secondary' }}">
                  {{ ucfirst($jadwal->jenis_ujian ?? '-') }}
                </span>
              </td>
              <td class="text-center">
                <a href="{{ route('ujikom.jadwal.show', $jadwal->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-eye"></i> Detail
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>

  {{-- Riwayat Hasil Ujian --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex align-items-center">
      <h6 class="mb-0"><i class="fas fa-history text-primary mr-2"></i>Riwayat Uji Kompetensi</h6>
      <a href="{{ route('ujikom.hasil.riwayat') }}" class="btn btn-sm btn-outline-primary ml-auto">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
      @if ($riwayatHasil->isEmpty())
        <div class="text-center text-muted py-4">
          <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
          Belum ada riwayat uji kompetensi.
        </div>
      @else
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Nama Jadwal</th>
              <th width="110" class="text-center">Tanggal</th>
              <th width="70" class="text-center">Nilai</th>
              <th width="110" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($riwayatHasil as $hasil)
            <tr>
              <td>{{ $hasil->jadwal?->judul ?? '-' }}</td>
              <td class="text-center"><small>{{ $hasil->tanggal_ujian?->format('d M Y') ?? '-' }}</small></td>
              <td class="text-center">
                <strong class="text-{{ $hasil->status_kelulusan === 'lulus' ? 'success' : ($hasil->status_kelulusan === 'tidak_lulus' ? 'danger' : 'muted') }}">
                  {{ $hasil->nilai !== null ? number_format($hasil->nilai, 0) : '—' }}
                </strong>
              </td>
              <td class="text-center">
                <span class="badge badge-{{ $hasil->badge_status }}">{{ $hasil->label_status }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>

  {{-- Permohonan Aktif --}}
  @if ($pendaftaranAktif->isNotEmpty())
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
      <h6 class="mb-0"><i class="fas fa-file-alt text-primary mr-2"></i>Permohonan Pendaftaran Ujikom Aktif</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Jadwal</th>
              <th width="110" class="text-center">Tanggal Daftar</th>
              <th width="150" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($pendaftaranAktif as $daftar)
            <tr>
              <td>{{ $daftar->jadwal?->judul ?? '-' }}</td>
              <td class="text-center"><small>{{ $daftar->created_at?->format('d M Y') ?? '-' }}</small></td>
              <td class="text-center">
                <span class="badge badge-info" style="font-size:0.75rem;">{{ str_replace('_', ' ', $daftar->status) }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif
  @endrole

  {{-- ============ DASHBOARD ADMIN UNIT ============ --}}
  @role('admin_unit')
  <h3 class="mb-4 fw-bold">Dashboard Unit Kerja</h3>
  @if ($unitKerja)
  <div class="alert alert-info mb-3 py-2">
    <i class="fas fa-building mr-2"></i>
    <strong>{{ $unitKerja->nama_rumahsakit }}</strong>
    — {{ optional($unitKerja->regency)->type }} {{ optional($unitKerja->regency)->name }},
    {{ optional(optional($unitKerja->regency)->province)->name }}
  </div>
  @endif

  {{-- Stat Cards --}}
  <div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <div class="text-info" style="font-size:2rem;"><i class="fas fa-users"></i></div>
          <h3 class="mb-0 font-weight-bold">{{ number_format($totalPegawai) }}</h3>
          <small class="text-muted">Total Pemangku JFT Aktif</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <div class="text-warning" style="font-size:2rem;"><i class="fas fa-clock"></i></div>
          <h3 class="mb-0 font-weight-bold text-warning">{{ number_format($permohonanMenunggu) }}</h3>
          <small class="text-muted">Permohonan Menunggu Verifikasi</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <div class="text-primary" style="font-size:2rem;"><i class="fas fa-spinner"></i></div>
          <h3 class="mb-0 font-weight-bold text-primary">{{ number_format($permohonanDiproses) }}</h3>
          <small class="text-muted">Permohonan Diproses Pusbin</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body text-center">
          <div class="text-success" style="font-size:2rem;"><i class="fas fa-check-circle"></i></div>
          <h3 class="mb-0 font-weight-bold text-success">{{ number_format($permohonanSelesai) }}</h3>
          <small class="text-muted">Permohonan Selesai</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Rekap Formasi Unit --}}
  @if ($formasiUnit->isNotEmpty())
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
      <h6 class="mb-0"><i class="fas fa-briefcase text-primary mr-2"></i>Rekap Formasi Unit Kerja</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Nama Formasi</th>
              <th width="120">Jenjang</th>
              <th width="80" class="text-center">Kuota</th>
              <th width="80" class="text-center">Terisi</th>
              <th width="80" class="text-center">Sisa</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($formasiUnit as $f)
            @php $terisi = (int)($terisiMap[$f->id] ?? 0); $sisa = max((int)$f->kuota - $terisi, 0); @endphp
            <tr>
              <td>{{ $f->nama_formasi }}</td>
              <td>{{ $f->nama_jenjang }}</td>
              <td class="text-center">{{ $f->kuota }}</td>
              <td class="text-center">{{ $terisi }}</td>
              <td class="text-center">
                <span class="badge badge-{{ $sisa > 0 ? 'success' : 'danger' }}">{{ $sisa }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  {{-- Jadwal Ujikon Aktif --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex align-items-center">
      <h6 class="mb-0"><i class="fas fa-calendar-check text-primary mr-2"></i>Jadwal Ujian Aktif</h6>
      <a href="{{ route('ujikom.jadwal.index') }}" class="btn btn-sm btn-outline-primary ml-auto">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
      @if ($jadwalAktif->isEmpty())
        <div class="text-center text-muted py-4">Belum ada jadwal aktif.</div>
      @else
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>Judul Ujian</th>
              <th width="120" class="text-center">Tanggal</th>
              <th width="80" class="text-center">Jenis</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($jadwalAktif as $jadwal)
            <tr>
              <td><a href="{{ route('ujikom.jadwal.show', $jadwal->id) }}">{{ $jadwal->judul }}</a></td>
              <td class="text-center"><small>{{ $jadwal->tanggal_mulai?->format('d M Y') ?? '-' }}</small></td>
              <td class="text-center">
                <span class="badge badge-{{ $jadwal->jenis_ujian === 'online' ? 'primary' : 'secondary' }}">
                  {{ ucfirst($jadwal->jenis_ujian ?? '-') }}
                </span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>
  @endrole

  {{-- ============ DASHBOARD SUPER ADMIN / ADMIN / VIEWER ============ --}}
  @hasanyrole('super_admin|admin|viewer')

  {{-- Perlu Tindakan (hanya admin & super admin) --}}
  @hasanyrole('super_admin|admin')
  @if ($perluTindakan)
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
      <h6 class="mb-0"><i class="fas fa-bell text-danger mr-2"></i>Perlu Tindakan</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 col-6 mb-3">
          <a href="{{ route('ujikom.permohonan.index', ['status' => 'diajukan_pusbin']) }}" class="text-decoration-none">
            <div class="p-3 rounded border border-danger bg-light d-flex align-items-center">
              <div class="text-danger mr-3" style="font-size:1.8rem;"><i class="fas fa-file-import"></i></div>
              <div>
                <div class="h4 mb-0 font-weight-bold text-danger">{{ $perluTindakan['permohonan_pending_pusbin'] }}</div>
                <small class="text-muted">Permohonan Menunggu Verifikasi Pusbin</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-4 col-6 mb-3">
          <a href="{{ route('ujikom.permohonan.index', ['status' => 'diajukan_admin_unit']) }}" class="text-decoration-none">
            <div class="p-3 rounded border border-warning bg-light d-flex align-items-center">
              <div class="text-warning mr-3" style="font-size:1.8rem;"><i class="fas fa-hourglass-half"></i></div>
              <div>
                <div class="h4 mb-0 font-weight-bold text-warning">{{ $perluTindakan['permohonan_pending_admin_unit'] }}</div>
                <small class="text-muted">Permohonan di Admin Unit</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-4 col-6 mb-3">
          <a href="{{ route('ujikom.jadwal.index') }}" class="text-decoration-none">
            <div class="p-3 rounded border border-primary bg-light d-flex align-items-center">
              <div class="text-primary mr-3" style="font-size:1.8rem;"><i class="fas fa-calendar-check"></i></div>
              <div>
                <div class="h4 mb-0 font-weight-bold text-primary">{{ $perluTindakan['jadwal_aktif'] }}</div>
                <small class="text-muted">Jadwal Ujian Aktif</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-4 col-6 mb-3">
          <a href="{{ route('ujikom-online.index') }}" class="text-decoration-none">
            <div class="p-3 rounded border border-info bg-light d-flex align-items-center">
              <div class="text-info mr-3" style="font-size:1.8rem;"><i class="fas fa-desktop"></i></div>
              <div>
                <div class="h4 mb-0 font-weight-bold text-info">{{ $perluTindakan['sesi_berlangsung'] }}</div>
                <small class="text-muted">Sesi Ujian Berlangsung</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-4 col-6 mb-3">
          <a href="{{ route('ujikom.hasil.index') }}" class="text-decoration-none">
            <div class="p-3 rounded border border-secondary bg-light d-flex align-items-center">
              <div class="text-secondary mr-3" style="font-size:1.8rem;"><i class="fas fa-clipboard-list"></i></div>
              <div>
                <div class="h4 mb-0 font-weight-bold text-secondary">{{ $perluTindakan['hasil_belum_dinilai'] }}</div>
                <small class="text-muted">Hasil Belum Dinilai</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-4 col-6 mb-3">
          <a href="{{ route('pengangkatan.index', ['status' => 'diajukan']) }}" class="text-decoration-none">
            <div class="p-3 rounded border border-danger bg-light d-flex align-items-center">
              <div class="text-danger mr-3" style="font-size:1.8rem;"><i class="fas fa-user-check"></i></div>
              <div>
                <div class="h4 mb-0 font-weight-bold text-danger">{{ $perluTindakan['permohonan_pengangkatan_pending'] }}</div>
                <small class="text-muted">Permohonan Pengangkatan Menunggu</small>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
  @endif
  @endhasanyrole

  {{-- ============ HERO ============ --}}

      <h3 class="mb-4 fw-bold">Halaman Dashboard</h3>

{{-- RINGKASAN NASIONAL --}}
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <h5 class="fw-semibold mb-3">Ringkasan Nasional</h5>
    <p class="text-muted small mb-4">
      Data Pemangku JFT aktif berdasarkan data terkini.
    </p>
    <div class="row g-3">


{{-- Total JFT Aktif --}}
<div class="col-md-3 col-6">
  <div class="p-3 rounded-3 bg-light h-100 d-flex flex-column">
    <div class="d-flex flex-column align-items-center text-center">
      <div class="stat-ico-circle bg-info text-white mb-2">
        <i class="far fa-user"></i>
      </div>
      <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalJftAktif) }}</h4>
      <small class="text-muted">Total Pemangku JFT Aktif</small>
    </div>
  </div>
</div>


      {{-- Rekap Jenjang --}}
      <div class="col-md-9 col-12">
        <div class="p-3 rounded-3 bg-light h-100">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fas fa-layer-group text-primary"></i>
            <span class="fw-semibold">Rekap JFT per Jenjang</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  @foreach($levels as $lvl) <th>{{ $lvl }}</th> @endforeach
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  @php $sum = array_sum($perJenjang ?? []); @endphp
                  @foreach($levels as $lvl)
                    <td>{{ number_format($perJenjang[$lvl] ?? 0) }}</td>
                  @endforeach
                  <td class="fw-bold">{{ number_format($sum) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


  {{-- ============ FILTER (Collapsible) ============ --}}
  <div class="glass rounded-4 mb-4">
    <div class="p-3 border-bottom d-flex align-items-center">
      <h6 class="mb-0"><i class="fas fa-filter me-2 text-primary"></i>Filter Rekap</h6>
      <button class="btn btn-link ms-auto text-decoration-none" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
        Tampilkan/Sembunyikan
      </button>
    </div>
    <div class="collapse show" id="filterCollapse">
      <div class="p-3">
        <form method="GET" action="{{ url('user/dashboard/peta') }}">
          <div class="row gy-3 gx-3">
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Moda</label>
              <select name="matra" class="form-select select2">
                <option value="">Semua Moda</option>
                @foreach($matras as $m)
                  <option value="{{ $m }}" @selected(($fMatra ?? null) === $m)>{{ $m }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Nama Formasi</label>
              <select name="formasi" class="form-select select2">
                <option value="">Semua Nama Formasi</option>
                @foreach($daftarFormasi as $nm)
                  <option value="{{ $nm }}" @selected(($fFormasi ?? null) === $nm)>{{ $nm }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Provinsi</label>
              <select name="province_id" id="provFilter" class="form-select select2">
                <option value="">Semua Provinsi</option>
                @foreach($provinces as $p)
                  <option value="{{ $p->id }}" @selected(($fProvinceId ?? null) == $p->id)>{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Kabupaten/Kota</label>
              <select name="regency_id" id="regFilter" class="form-select select2">
                <option value="">Semua Kab/Kota</option>
                @foreach($regencies as $r)
                  <option value="{{ $r->id }}" @selected(($fRegencyId ?? null) == $r->id)>{{ $r->type }} {{ $r->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="d-flex gap-2 justify-content-end mt-3">
            <a href="{{ url('user/dashboard/peta') }}" class="btn btn-light">Reset</a>
            <button class="btn btn-primary px-4">Terapkan</button>
          </div>
          <div class="text-muted small mt-2">
            Default menampilkan agregat nasional (Semua Moda, Semua Formasi, Semua Provinsi, Semua Kab/Kota).
          </div>
        </form>
      </div>
    </div>
  </div>


  {{-- ============ REKAP TERFILTER ============ --}}
<div class="glass rounded-4 mb-4">
  
  <div class="p-3 border-bottom d-flex align-items-center">
  <h6 class="mb-0">
    <i class="fas fa-chart-bar me-2 text-primary"></i>Rekap Terfilter
    ... {{-- (teks filter yang sudah ada) --}}
  </h6>

 <div class="ms-auto d-flex gap-2">
  <a class="btn btn-sm btn-outline-success"
     href="{{ route('user.dashboard.peta.export-excel', request()->query()) }}">
     Export Excel
  </a>
  <a class="btn btn-sm btn-outline-danger"
     href="{{ route('user.dashboard.peta.export-pdf', request()->query()) }}">
     Export PDF
  </a>
</div>
</div>

  <div class="p-3 pt-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="table-light">
          {{-- BEFORE (header 1 baris) --}}
          {{-- AFTER (header 2 baris + kolom Jenis JFT) --}}
          <tr>
            <th rowspan="2" style="width:36px">#</th>
            <th rowspan="2" style="min-width:260px">Nama Jabatan</th>
            <th colspan="{{ count($levels) }}" class="text-center">Jenjang</th>
            <th rowspan="2" class="text-end">Total</th>
          </tr>
          <tr>
            @foreach($levels as $lvl)
              <th class="text-end text-nowrap">{{ $lvl }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>

          {{-- RINCIAN 22 JFT (hanya baris yang punya data) --}}
@php
  $J  = $matrixJft['jenjangOrder'] ?? $levels ?? [];
  $N  = $matrixJft['allJft']      ?? [];
  $M  = $matrixJft['matrix']      ?? [];
  $RT = $matrixJft['rowTotals']   ?? [];

  // Ambil hanya JFT dengan total > 0 (setelah filter diterapkan)
  $visibleJft = array_values(array_filter($N, function($nm) use ($RT) {
      return ((int)($RT[$nm] ?? 0)) > 0;
  }));
@endphp

@if(empty($visibleJft))
  <tr>
    <td colspan="{{ 3 + count($J) }}" class="text-center text-muted">
      Tidak ada data untuk kombinasi filter ini.
    </td>
  </tr>
@else
  @php $no = 1; @endphp
  @foreach($visibleJft as $jft)
    <tr>
      <td>{{ $no++ }}</td>
      <td>{{ $jft }}</td>
      @foreach($J as $jj)
        <td class="text-end">{{ number_format((int)($M[$jft][$jj] ?? 0)) }}</td>
      @endforeach
      <td class="text-end fw-semibold">{{ number_format((int)($RT[$jft] ?? 0)) }}</td>
    </tr>
  @endforeach
@endif

        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="2">Total per Jenjang</th>
            @php $CT = $matrixJft['colTotals'] ?? []; @endphp
            @foreach($levels as $lvl)
              <th class="text-end">{{ number_format($CT[$lvl] ?? 0) }}</th>
            @endforeach
            <th class="text-end">{{ number_format($matrixJft['grand'] ?? 0) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>


  {{-- ------------------------- --}}


<div class="col-14 col-lg-12">
  <div class="glass rounded-4 p-3 mb-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0"><i class="fas fa-sort-amount-down me-2 text-primary"></i>Piramida Pemangku JFT</h6>
    </div>
    <div id="pyramidOneSide" style="height:240px;"></div>

    <div class="text-muted small mt-2">Urutan kecil → besar (atas ke bawah). Mengikuti filter yang dipilih.</div>
  </div>
</div>



  {{-- ============ MAP ============ --}}
  <div class="glass rounded-4 mb-4">
    <div class="p-3 border-bottom">
      <h6 class="mb-0"><i class="fas fa-map me-2 text-primary"></i>Peta Persebaran</h6>
    </div>
    <div id="leafletMap-dashboard" style="height: 650px;"></div>



  </div>

  @endhasanyrole {{-- end super_admin|admin|viewer --}}

</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .stat-ico-circle{
    width:56px;height:56px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:22px;
  }

  /* Theme bits */
  .bg-gradient-primary{
    background: linear-gradient(135deg,#4e73df 0%, #1cc88a 100%);
  }
  .glass{
    background: rgba(255,255,255,.9);
    box-shadow: 0 8px 24px rgba(16,24,40,.08);
    backdrop-filter: blur(6px);
  }
  .stat-card{ border:1px solid rgba(16,24,40,.06); }
  .stat-icon{
    width:48px;height:48px; display:grid; place-items:center; font-size:20px;
    box-shadow: 0 6px 16px rgba(16,24,40,.12);
  }
  .stat-value{ font-size:1.35rem; font-weight:700; line-height:1; }
  .stat-label{ font-size:.85rem; color:#667085; }

  /* Select2 height match */
  .select2-container .select2-selection--single{
    height: 42px!important; padding: 6px 12px; border: 1px solid #ced4da; border-radius: .5rem;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height: 28px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
{{-- <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-funnel"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>




<script>
document.addEventListener('DOMContentLoaded', function () {
  // INIT Select2
  $('.select2').select2({ width: '100%', placeholder: 'Pilih…', allowClear: true });

  // --- Dependent Prov -> Regency
  const baseUrl = @json(url('/user/wilayah/regencies'));
  const $prov = $('#provFilter'); const $reg = $('#regFilter');
  const initialProv = @json($fProvinceId ?? ''); const initialReg = @json($fRegencyId ?? '');

  function normalizeItem(raw){
    const id = raw.id ?? raw.value ?? '';
    const name = raw.name ?? raw.nama ?? raw.text ?? '';
    const type = raw.type ?? raw.tipe ?? '';
    const label = [type, name].filter(Boolean).join(' ').trim() || id;
    return { id: String(id), text: label };
  }
  function setRegencyOptions(list, selectedId=''){
    if ($reg.hasClass('select2-hidden-accessible')) $reg.select2('destroy');
    $reg.empty().append(new Option('Semua Kab/Kota','',true,selectedId===''));
    (list||[]).map(normalizeItem).forEach(x=> $reg.append(new Option(x.text, x.id, false, x.id===String(selectedId))));
    $reg.select2({width:'100%',placeholder:'Pilih…',allowClear:true});
  }
  async function loadRegencies(pid, preselect=''){
    if (!pid){ setRegencyOptions([], ''); return; }
    const res = await fetch(`${baseUrl}/${pid}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
    setRegencyOptions(await res.json(), preselect);
  }
  $prov.on('change', ()=> loadRegencies($prov.val(), ''));
  if(initialProv){ $prov.val(String(initialProv)).trigger('change.select2'); loadRegencies(initialProv, initialReg); }
  else{ setRegencyOptions([], ''); }


// --- Step pyramid: urut jenjang hierarkis (bawah=terendah, atas=tertinggi)
const orderLowToHigh = @json($levels ?? []);     // ['Pemula','Terampil',...,'Utama']
const srcNames       = @json($pyramidLabels ?? []);
const srcValues      = @json($pyramidValues ?? []);

// Map sumber (mungkin sudah di-sort lainnya) -> urutan hierarkis
const idxMap   = Object.fromEntries(srcNames.map((n, i) => [n, i]));
const names    = orderLowToHigh.slice(); // label sumbu Y
const real     = names.map(n => Number((idxMap[n] != null ? srcValues[idxMap[n]] : 0)) || 0);

// Hitung padding kiri/kanan agar batang tetap di tengah (opsional)
const maxVal   = Math.max(...real, 1);
const leftPad  = real.map(v => (maxVal - v) / 2);
const rightPad = leftPad.slice();

const dom = document.getElementById('pyramidOneSide');
if (dom && window.echarts) {
  const chart  = echarts.init(dom);
  const colors = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#fd7e14','#20c997'];

  chart.setOption({
    grid: { left: 10, right: 10, top: 10, bottom: 10, containLabel: true },
    xAxis: {
      type: 'value', min: 0, max: maxVal,
      axisLabel: { formatter: v => Number(v).toLocaleString('id-ID') }
    },
    yAxis: {
      type: 'category',
      data: names,            // urutan hierarkis
      inverse: false,          // item pertama (Pemula) ditaruh DI BAWAH
      axisTick: { show: false }
    },
    tooltip: {
      trigger: 'axis', axisPointer: { type: 'shadow' },
      formatter: (params) => {
        const p = params.find(x => x.seriesName === 'Nilai') ?? params[0];
        return `${p.name}: ${Number(p.value).toLocaleString('id-ID')}`;
      }
    },
    series: [
      { name:'PadLeft',  type:'bar', stack:'total', data:leftPad,
        itemStyle:{ color:'transparent' }, emphasis:{ disabled:true }, silent:true },
      { name:'Nilai',    type:'bar', stack:'total', data:real, barWidth:28,
        itemStyle:{ color:p=>colors[p.dataIndex%colors.length], borderRadius:[4,4,4,4] },
        label:{ show:true, position:'inside',
                formatter:p=>`${p.name}\n${Number(p.value).toLocaleString('id-ID')}` } },
      { name:'PadRight', type:'bar', stack:'total', data:rightPad,
        itemStyle:{ color:'transparent' }, emphasis:{ disabled:true }, silent:true }
    ],
    animationDuration: 400
  });

  // aman untuk container yang sempat tersembunyi
  setTimeout(() => chart.resize(), 50);
  window.addEventListener('resize', () => chart.resize());
}


  // --- Line: Tren per tahun
  const lineYears = @json($lineChartYears ?? []);
  const lineDatasetsRaw = @json($lineChartData ?? []);
  const ctxLine = document.getElementById('chartLineJenjang');
  if (ctxLine) {
    const colors = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#fd7e14','#20c997'];
    const ds = (lineDatasetsRaw||[]).map((d,i)=>({ label:d.label, data:d.data, borderColor:colors[i%colors.length], backgroundColor:colors[i%colors.length], tension:.35, fill:false }));
    new Chart(ctxLine, {
      type:'line', data:{ labels:lineYears, datasets:ds },
      options:{
        responsive:true, interaction:{mode:'index',intersect:false},
        plugins:{ legend:{position:'bottom'}, tooltip:{callbacks:{label:(c)=>`${c.dataset.label}: ${c.parsed.y?.toLocaleString?.()}`}} },
        scales:{ y:{beginAtZero:true} }
      }
    });
  }
});

// --- Leaflet Map
var map = L.map('leafletMap-dashboard').setView([-2.5489,118.0149],5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'&copy; OpenStreetMap contributors' }).addTo(map);
const markers = @json($markers ?? []);

// ICON UNTUK SETIAP MATRA
const iconDarat = L.icon({
    iconUrl: '/images/matra/darat.png',
    iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

const iconLaut = L.icon({
    iconUrl: '/images/matra/laut.png',
    iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

const iconUdara = L.icon({
    iconUrl: '/images/matra/udara.png',
   iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

const iconKereta = L.icon({
    iconUrl: '/images/matra/kereta.png',
    iconSize: [75, 45],
    // iconAnchor: [16, 32]
});

// FUNGSI memilih icon berdasarkan matra
function getMatraIcon(matra) {
   switch (matra) {
      case 'Darat': return iconDarat;
      case 'Laut': return iconLaut;
      case 'Udara': return iconUdara;
      case 'Kereta': return iconKereta;
      default: return iconDarat; // fallback
   }
}


markers.forEach(m=>{
  if(!m.lat||!m.lng) return;
  const headerHtml = `<div><b>${m.unit??'-'}</b></div>
    <div>Provinsi: ${m.prov??'-'}</div>
    <div>Kab/Kota: ${m.kab??'-'}</div>
    <div>Moda: ${m.matra??'-'}</div>
    <div>Instansi: ${m.instansi??'-'}</div>
    <div><b>Total Formasi:</b> ${m.total_kuota??0}</div>
    <div><b>Total Terisi:</b> ${m.total_terisi??0}</div>
    <div><b>Total Sisa:</b> ${m.total_sisa??0}</div>`;
  let bodyHtml=''; if(m.per_jenjang&&m.per_jenjang.length){
    bodyHtml+=`<div class="mt-2"><i>Rincian per Jenjang</i>:</div><ul class="mb-0">`;
    m.per_jenjang.forEach(j=>{ bodyHtml+=`<li><b>${j.nama}:</b> Kuota: ${j.kuota}, Terisi: ${j.terisi}, Sisa: ${j.sisa}</li>`; });
    bodyHtml+='</ul>';
  }
 // L.marker([m.lat,m.lng]).addTo(map).bindPopup(`<div style="min-width:260px">${headerHtml}${bodyHtml}</div>`);
 L.marker([m.lat, m.lng], { icon: getMatraIcon(m.matra) })
 .addTo(map)
 .bindPopup(`<div style="min-width:260px">${headerHtml}${bodyHtml}</div>`);
});
</script>

<script>
(function(){
  // base URL export
  const baseExcel = @json(route('user.dashboard.peta.export-excel'));
  const basePdf   = @json(route('user.dashboard.peta.export-pdf'));

  // ambil query filter yang sedang aktif agar file = tampilan
  const qsObj = @json(request()->query());
  const qs = new URLSearchParams(qsObj).toString();
  const urlExcel = qs ? `${baseExcel}?${qs}` : baseExcel;
  const urlPdf   = qs ? `${basePdf}?${qs}`   : basePdf;

  async function downloadViaFetch(url, fallbackName){
    try{
      const res = await fetch(url, {
        method:'GET',
        headers:{ 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if(!res.ok) throw new Error('Gagal mengunduh');
      const blob = await res.blob();

      // coba ambil nama file dari header; jika tidak ada, pakai fallback
      let filename = fallbackName;
      const cd = res.headers.get('Content-Disposition');
      if(cd){
        const m = /filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i.exec(cd);
        if(m) filename = decodeURIComponent(m[1] || m[2] || fallbackName);
      }

      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      setTimeout(()=>{ URL.revokeObjectURL(link.href); link.remove(); }, 100);
    }catch(e){
      console.error(e);
      alert('Unduhan gagal. Coba lagi.');
    }
  }

  document.getElementById('btnExportExcel')
          ?.addEventListener('click', ()=> downloadViaFetch(urlExcel, 'rekap_pemangku.xlsx'));
  document.getElementById('btnExportPdf')
          ?.addEventListener('click',   ()=> downloadViaFetch(urlPdf,   'rekap_pemangku.pdf'));
})();
</script>
@endpush
