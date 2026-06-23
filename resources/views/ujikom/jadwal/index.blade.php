@extends('layouts.users.master')

@section('title', 'Pengumuman Jadwal Uji Kompetensi — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
          <i class="fas fa-calendar-alt mr-2"></i>Pengumuman Jadwal Uji Kompetensi
        </h3>
        @role('admin|super_admin')
        <a href="{{ route('ujikom.jadwal.create') }}" class="btn btn-primary btn-sm">
          <i class="fas fa-plus mr-1"></i> Tambah Jadwal
        </a>
        @endrole
      </div>

      {{-- ========== TAMPILAN ADMIN ========== --}}
      @if ($viewMode === 'admin')
      <div class="card-body">

        {{-- Filter Status --}}
        <form method="GET" action="{{ route('ujikom.jadwal.index') }}" class="mb-3">
          <div class="row align-items-end">
            <div class="col-md-3">
              <label class="font-weight-bold">Filter Status</label>
              <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">-- Semua Status --</option>
                <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Dipublikasikan</option>
                <option value="selesai"   {{ request('status') === 'selesai'   ? 'selected' : '' }}>Selesai</option>
              </select>
            </div>
            @if(request('status'))
            <div class="col-md-2">
              <a href="{{ route('ujikom.jadwal.index') }}" class="btn btn-default btn-sm">Reset</a>
            </div>
            @endif
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm" id="jadwalTable">
            <thead class="thead-light">
              <tr>
                <th width="40">No</th>
                <th>Judul</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Tempat</th>
                <th width="70">Kuota</th>
                <th width="130">Status</th>
                <th width="150">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($jadwals as $i => $j)
              <tr>
                <td>{{ $jadwals->firstItem() + $i }}</td>
                <td>{{ $j->judul }}</td>
                <td>{{ $j->tanggal_mulai->format('d/m/Y') }}</td>
                <td>{{ $j->tanggal_selesai->format('d/m/Y') }}</td>
                <td>{{ $j->tempat }}</td>
                <td class="text-center">{{ $j->kuota }}</td>
                <td>
                  <span class="badge badge-{{ $j->badge_status }}">{{ $j->status_label }}</span>
                </td>
                <td>
                  <a href="{{ route('ujikom.jadwal.show', $j->id) }}" class="btn btn-info btn-xs" title="Lihat">
                    <i class="fas fa-eye"></i>
                  </a>
                  @if ($j->status === 'draft')
                  <a href="{{ route('ujikom.jadwal.edit', $j->id) }}" class="btn btn-warning btn-xs" title="Edit">
                    <i class="fas fa-pencil-alt"></i>
                  </a>
                  <form action="{{ route('ujikom.jadwal.publish', $j->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Publikasikan jadwal ini?')">
                    @csrf
                    <button type="submit" class="btn btn-success btn-xs" title="Publikasikan">
                      <i class="fas fa-globe"></i>
                    </button>
                  </form>
                  <form action="{{ route('ujikom.jadwal.destroy', $j->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus jadwal ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-xs" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                  @endif
                  @if ($j->status === 'published')
                  <form action="{{ route('ujikom.jadwal.selesaikan', $j->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Tandai jadwal ini sebagai selesai?')">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-xs" title="Selesaikan">
                      <i class="fas fa-check-double"></i>
                    </button>
                  </form>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                  Belum ada jadwal uji kompetensi.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          {{ $jadwals->withQueryString()->links() }}
        </div>
      </div>

      {{-- ========== TAMPILAN PUBLIK (OPERATOR / VIEWER) ========== --}}
      @else
      <div class="card-body">
        @if ($jadwals->isEmpty())
          <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3"></i>
            <p class="mb-0">Belum ada jadwal uji kompetensi yang dipublikasikan.</p>
          </div>
        @else
          <div class="row">
            @foreach ($jadwals as $j)
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="card h-100 shadow-sm border-left border-success" style="border-left-width: 4px !important;">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge badge-success">Dibuka</span>
                    <small class="text-muted">Kuota: <strong>{{ $j->kuota }}</strong></small>
                  </div>
                  <h5 class="card-title font-weight-bold" style="font-size:1rem;">{{ $j->judul }}</h5>
                  <p class="mb-1 text-muted" style="font-size:0.85rem;">
                    <i class="fas fa-calendar mr-1"></i>
                    {{ $j->tanggal_mulai->format('d M Y') }}
                    @if (!$j->tanggal_mulai->equalTo($j->tanggal_selesai))
                      — {{ $j->tanggal_selesai->format('d M Y') }}
                    @endif
                  </p>
                  <p class="mb-2 text-muted" style="font-size:0.85rem;">
                    <i class="fas fa-map-marker-alt mr-1"></i> {{ $j->tempat }}
                  </p>
                  @if ($j->deskripsi)
                  <p class="text-muted mb-3" style="font-size:0.85rem; overflow:hidden; max-height:48px;">
                    {{ Str::limit($j->deskripsi, 100) }}
                  </p>
                  @endif
                </div>
                <div class="card-footer bg-transparent border-0">
                  <a href="{{ route('ujikom.jadwal.show', $j->id) }}" class="btn btn-outline-primary btn-sm btn-block">
                    <i class="fas fa-eye mr-1"></i> Lihat Detail
                  </a>
                </div>
              </div>
            </div>
            @endforeach
          </div>
          <div class="mt-2">
            {{ $jadwals->links() }}
          </div>
        @endif
      </div>
      @endif

    </div>
  </div>
</div>
@endsection

@push('scripts')
@role('admin|super_admin')
<script>
$(document).ready(function () {
  $('#jadwalTable').DataTable({
    paging:   false,
    info:     false,
    ordering: true,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data.' },
  });
});
</script>
@endrole
@endpush
