@extends('layouts.users.master')

@section('title', 'Paket Ujian — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    {{-- Statistik --}}
    <div class="row mb-3">
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-secondary"><i class="fas fa-file-alt"></i></span>
          <div class="info-box-content"><span class="info-box-text">Total Paket</span><span class="info-box-number">{{ $statistik['total'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content"><span class="info-box-text">Aktif</span><span class="info-box-number">{{ $statistik['aktif'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-warning"><i class="fas fa-edit"></i></span>
          <div class="info-box-content"><span class="info-box-text">Draft</span><span class="info-box-number">{{ $statistik['draft'] }}</span></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="info-box shadow-sm mb-0">
          <span class="info-box-icon bg-danger"><i class="fas fa-ban"></i></span>
          <div class="info-box-content"><span class="info-box-text">Nonaktif</span><span class="info-box-number">{{ $statistik['nonaktif'] }}</span></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0"><i class="fas fa-file-alt mr-2"></i>Paket Ujian</h3>
        <a href="{{ route('paket-ujian.create') }}" class="btn btn-primary btn-sm">
          <i class="fas fa-plus mr-1"></i> Buat Paket
        </a>
      </div>

      {{-- Filter --}}
      <div class="card-body border-bottom pt-3 pb-3">
        <form method="GET" action="{{ route('paket-ujian.index') }}" class="row align-items-end">
          <div class="col-md-3 mb-2">
            <label class="small font-weight-bold mb-1">Status</label>
            <select name="status" class="form-control form-control-sm">
              <option value="">— Semua Status —</option>
              <option value="aktif"    {{ request('status') === 'aktif'    ? 'selected' : '' }}>Aktif</option>
              <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
              <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
          </div>
          <div class="col-md-4 mb-2">
            <label class="small font-weight-bold mb-1">Jadwal Ujikom</label>
            <select name="jadwal_id" class="form-control form-control-sm">
              <option value="">— Semua Jadwal —</option>
              @foreach ($jadwals as $j)
              <option value="{{ $j->id }}" {{ request('jadwal_id') == $j->id ? 'selected' : '' }}>{{ $j->judul }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 mb-2">
            <label class="small font-weight-bold mb-1">Mode Pemilihan</label>
            <select name="mode" class="form-control form-control-sm">
              <option value="">— Semua Mode —</option>
              <option value="acak_otomatis" {{ request('mode') === 'acak_otomatis' ? 'selected' : '' }}>Acak Otomatis</option>
              <option value="manual"        {{ request('mode') === 'manual'        ? 'selected' : '' }}>Manual</option>
            </select>
          </div>
          <div class="col-md-2 mb-2 d-flex">
            <button type="submit" class="btn btn-primary btn-sm mr-1 flex-fill">Filter</button>
            <a href="{{ route('paket-ujian.index') }}" class="btn btn-default btn-sm">Reset</a>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        @if (session('success'))
        <div class="alert alert-success mx-3 mt-3 mb-0 py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger mx-3 mt-3 mb-0 py-2">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama Paket</th>
                <th>Jadwal Ujikom</th>
                <th width="120">Mode</th>
                <th width="80" class="text-center">Durasi</th>
                <th width="90" class="text-center">Passing Grade</th>
                <th width="80" class="text-center">Jml Soal</th>
                <th width="80" class="text-center">Status</th>
                <th width="130" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($pakets as $i => $p)
              <tr>
                <td class="text-center">{{ $pakets->firstItem() + $i }}</td>
                <td>
                  <strong>{{ $p->nama }}</strong>
                  @if ($p->deskripsi)
                  <br><small class="text-muted">{{ Str::limit($p->deskripsi, 60) }}</small>
                  @endif
                </td>
                <td><small class="text-muted">{{ $p->jadwal?->judul ?? '— Standalone —' }}</small></td>
                <td>
                  <span class="badge badge-{{ $p->mode_pemilihan === 'manual' ? 'info' : 'primary' }}">
                    {{ $p->label_mode }}
                  </span>
                </td>
                <td class="text-center"><small>{{ $p->durasi_menit }} mnt</small></td>
                <td class="text-center"><span class="badge badge-secondary">{{ $p->passing_grade }}%</span></td>
                <td class="text-center"><strong>{{ $p->jumlah_soal }}</strong></td>
                <td class="text-center">
                  <span class="badge badge-{{ $p->badge_status }}">{{ $p->label_status }}</span>
                </td>
                <td class="text-center">
                  <a href="{{ route('paket-ujian.show', $p->id) }}" class="btn btn-xs btn-outline-info" title="Lihat"><i class="fas fa-eye"></i></a>
                  @if ($p->status === 'draft')
                  <a href="{{ route('paket-ujian.edit', $p->id) }}" class="btn btn-xs btn-outline-warning" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                  <form action="{{ route('paket-ujian.aktifkan', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Aktifkan paket ini?')">
                    @csrf <button class="btn btn-xs btn-outline-success" title="Aktifkan"><i class="fas fa-check"></i></button>
                  </form>
                  <form action="{{ route('paket-ujian.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                  </form>
                  @elseif ($p->status === 'aktif')
                  <form action="{{ route('paket-ujian.nonaktifkan', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan paket ini?')">
                    @csrf <button class="btn btn-xs btn-outline-secondary" title="Nonaktifkan"><i class="fas fa-ban"></i></button>
                  </form>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="9" class="text-center text-muted py-4">Belum ada paket ujian.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if ($pakets->hasPages())
      <div class="card-footer">{{ $pakets->links() }}</div>
      @endif
    </div>

  </div>
</div>
@endsection
