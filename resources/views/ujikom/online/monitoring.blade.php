@extends('layouts.users.master')

@section('title', 'Monitoring Ujian — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-1"><i class="fas fa-tv mr-2"></i>Monitoring Ujian</h4>
        <p class="text-muted mb-0">{{ $jadwal->judul }} &mdash; {{ $jadwal->tanggal_mulai?->format('d M Y') }}</p>
      </div>
      <div>
        <a href="{{ route('ujikom-online.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    {{-- Statistik --}}
    <div class="row mb-3">
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-secondary"><i class="fas fa-users"></i></span>
          <div class="info-box-content"><span class="info-box-text">Total Peserta</span><span class="info-box-number">{{ $statistik['total'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
          <div class="info-box-content"><span class="info-box-text">Belum Mulai</span><span class="info-box-number">{{ $statistik['menunggu'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-primary"><i class="fas fa-spinner fa-spin"></i></span>
          <div class="info-box-content"><span class="info-box-text">Sedang Ujian</span><span class="info-box-number">{{ $statistik['berlangsung'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content"><span class="info-box-text">Selesai</span><span class="info-box-number">{{ $statistik['selesai'] }}</span></div>
        </div>
      </div>
    </div>

    {{-- Tabel Peserta --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Daftar Peserta</h3>
        <small class="text-muted" id="refreshInfo">Auto-refresh setiap 30 detik</small>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.84rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama Peserta</th>
                <th width="100" class="text-center">Status</th>
                <th width="130" class="text-center">Progress</th>
                <th width="80" class="text-center">Nilai</th>
                <th width="90" class="text-center">Status Lulus</th>
                <th width="90" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($sesiList as $i => $s)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $s->peserta?->pegawai?->nama_lengkap ?? '-' }}</strong>
                  <br><small class="text-muted">{{ $s->peserta?->pegawai?->nip ?? '-' }}</small>
                </td>
                <td class="text-center">
                  <span class="badge badge-{{ $s->badge_status_sesi }}">{{ $s->label_status_sesi }}</span>
                </td>
                <td class="text-center">
                  @if ($s->status_sesi === 'berlangsung')
                    <div class="d-flex align-items-center">
                      <div class="progress flex-grow-1 mr-2" style="height:8px;">
                        <div class="progress-bar bg-primary" style="width:{{ $s->progress_data['persen'] }}%"></div>
                      </div>
                      <small>{{ $s->progress_data['dijawab'] }}/{{ $s->progress_data['total'] }}</small>
                    </div>
                  @elseif (in_array($s->status_sesi, ['selesai', 'timeout']))
                    <small class="text-muted">{{ $s->progress_data['dijawab'] }}/{{ $s->progress_data['total'] }}</small>
                  @else
                    <small class="text-muted">—</small>
                  @endif
                </td>
                <td class="text-center">
                  @if ($s->nilai_akhir !== null)
                    <strong>{{ number_format($s->nilai_akhir, 0) }}</strong>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-center">
                  @if ($s->status_lulus === 'lulus')
                    <span class="badge badge-success">Lulus</span>
                  @elseif ($s->status_lulus === 'tidak_lulus')
                    <span class="badge badge-danger">Tidak Lulus</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-center">
                  @if (in_array($s->status_sesi, ['berlangsung', 'menunggu']))
                  <form action="{{ route('ujikom-online.admin.force-submit', $s->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Force submit peserta ini?')">
                    @csrf
                    <button class="btn btn-xs btn-outline-danger" title="Force Submit">
                      <i class="fas fa-stop-circle"></i>
                    </button>
                  </form>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="7" class="text-center text-muted py-4">Belum ada sesi peserta.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh setiap 30 detik
setInterval(function() {
  location.reload();
}, 30000);
</script>
@endpush
