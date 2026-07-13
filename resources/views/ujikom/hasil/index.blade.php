@extends('layouts.users.master')

@section('title', 'Hasil Uji Kompetensi — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    {{-- Statistik Global --}}
    <div class="row mb-3">
      <div class="col-6 col-md-2">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-secondary"><i class="fas fa-calendar-alt"></i></span>
          <div class="info-box-content"><span class="info-box-text">Total Jadwal</span><span class="info-box-number">{{ $statistik['total_jadwal'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
          <div class="info-box-content"><span class="info-box-text">Total Peserta</span><span class="info-box-number">{{ $statistik['total_peserta'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content"><span class="info-box-text">Lulus</span><span class="info-box-number">{{ $statistik['lulus'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
          <div class="info-box-content"><span class="info-box-text">Tidak Lulus</span><span class="info-box-number">{{ $statistik['tidak_lulus'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
          <div class="info-box-content"><span class="info-box-text">Belum Dinilai</span><span class="info-box-number">{{ $statistik['belum_dinilai'] }}</span></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0"><i class="fas fa-chart-bar mr-2"></i>Rekap Hasil Uji Kompetensi</h3>
      </div>

      {{-- Filter --}}
      <div class="card-body border-bottom pt-3 pb-3">
        <form method="GET" action="{{ route('ujikom.hasil.index') }}" class="row align-items-end">
          <div class="col-md-3 mb-2">
            <label class="small font-weight-bold mb-1">Tahun</label>
            <select name="tahun" class="form-control form-control-sm">
              <option value="">— Semua Tahun —</option>
              @foreach ($tahunList as $t)
              <option value="{{ $t }}" {{ request('tahun') == $t ? 'selected' : '' }}>{{ $t }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 mb-2">
            <label class="small font-weight-bold mb-1">Jenis Ujian</label>
            <select name="jenis" class="form-control form-control-sm">
              <option value="">— Semua Jenis —</option>
              <option value="kenaikan_jabatan"      {{ request('jenis') === 'kenaikan_jabatan'      ? 'selected' : '' }}>Kenaikan Jabatan</option>
              <option value="perpindahan_jabatan"   {{ request('jenis') === 'perpindahan_jabatan'   ? 'selected' : '' }}>Perpindahan Jabatan</option>
              <option value="penyesuaian_inpassing" {{ request('jenis') === 'penyesuaian_inpassing' ? 'selected' : '' }}>Penyesuaian (Inpassing)</option>
            </select>
          </div>
          <div class="col-md-2 mb-2 d-flex">
            <button type="submit" class="btn btn-primary btn-sm mr-1 flex-fill">Filter</button>
            <a href="{{ route('ujikom.hasil.index') }}" class="btn btn-default btn-sm">Reset</a>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama Jadwal</th>
                <th width="110" class="text-center">Tanggal</th>
                <th width="110" class="text-center">Jenis</th>
                <th width="80" class="text-center">Peserta</th>
                <th width="70" class="text-center text-success">Lulus</th>
                <th width="80" class="text-center text-danger">Tdk Lulus</th>
                <th width="90" class="text-center text-muted">Blm Dinilai</th>
                <th width="90" class="text-center">Kecurangan</th>
                <th width="180" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($jadwals as $i => $j)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <strong>{{ $j->judul }}</strong>
                  @if ($j->stat_hasil['ada_online'] && $j->stat_hasil['ada_offline'])
                    <span class="badge badge-info ml-1" style="font-size:0.7rem;">Campuran</span>
                  @elseif ($j->stat_hasil['ada_online'])
                    <span class="badge badge-primary ml-1" style="font-size:0.7rem;">Online</span>
                  @elseif ($j->stat_hasil['ada_offline'])
                    <span class="badge badge-secondary ml-1" style="font-size:0.7rem;">Offline</span>
                  @endif
                </td>
                <td class="text-center"><small>{{ $j->tanggal_mulai?->format('d M Y') }}</small></td>
                <td class="text-center"><small>{{ $j->jenis_ujian_label }}</small></td>
                <td class="text-center"><strong>{{ $j->stat_hasil['total'] }}</strong></td>
                <td class="text-center">
                  @if ($j->stat_hasil['lulus'] > 0)
                  <span class="badge badge-success">{{ $j->stat_hasil['lulus'] }}</span>
                  @else <span class="text-muted">0</span> @endif
                </td>
                <td class="text-center">
                  @if ($j->stat_hasil['tidak_lulus'] > 0)
                  <span class="badge badge-danger">{{ $j->stat_hasil['tidak_lulus'] }}</span>
                  @else <span class="text-muted">0</span> @endif
                </td>
                <td class="text-center">
                  @if ($j->stat_hasil['belum_dinilai'] > 0)
                  <span class="badge badge-secondary">{{ $j->stat_hasil['belum_dinilai'] }}</span>
                  @else <span class="text-muted">0</span> @endif
                </td>
                <td class="text-center">
                  @if ($j->stat_hasil['kecurangan'] > 0)
                  <span class="badge badge-dark" title="Peserta terindikasi kecurangan"><i class="fas fa-exclamation-triangle mr-1"></i>{{ $j->stat_hasil['kecurangan'] }}</span>
                  @else <span class="text-muted">0</span> @endif
                </td>
                <td class="text-center">
                  <a href="{{ route('ujikom.hasil.show', $j->id) }}" class="btn btn-xs btn-outline-info" title="Detail">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="{{ route('ujikom.hasil.export-pdf', $j->id) }}" class="btn btn-xs btn-outline-danger" title="Export PDF" target="_blank">
                    <i class="fas fa-file-pdf"></i>
                  </a>
                  <a href="{{ route('ujikom.hasil.export-excel', $j->id) }}" class="btn btn-xs btn-outline-success" title="Export Excel">
                    <i class="fas fa-file-excel"></i>
                  </a>
                </td>
              </tr>
              @empty
              <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data hasil ujian.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
