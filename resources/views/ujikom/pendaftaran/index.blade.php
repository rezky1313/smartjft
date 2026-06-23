@extends('layouts.users.master')

@section('title', 'Pendaftaran Uji Kompetensi — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
          <i class="fas fa-clipboard-list mr-2"></i>Pendaftaran Uji Kompetensi
        </h3>
        @role('operator|admin|super_admin')
        <a href="{{ route('ujikom.permohonan.create') }}" class="btn btn-primary btn-sm">
          <i class="fas fa-plus mr-1"></i> Daftar Ujikom
        </a>
        @endrole
      </div>
      <div class="card-body">

        {{-- Filter --}}
        <form method="GET" action="{{ route('ujikom.permohonan.index') }}" class="mb-3">
          <div class="row align-items-end">
            <div class="col-md-3">
              <label class="font-weight-bold small">Filter Status</label>
              <select name="status" class="form-control form-control-sm">
                <option value="">-- Semua Status --</option>
                @foreach ([
                    'draft'                   => 'Draft',
                    'diajukan_admin_unit'     => 'Diajukan (Admin Unit)',
                    'diverifikasi_admin_unit' => 'Diverifikasi Admin Unit',
                    'diajukan_pusbin'         => 'Diajukan ke Pusbin',
                    'diverifikasi_pusbin'     => 'Diverifikasi Pusbin',
                    'ditolak_admin_unit'      => 'Ditolak Admin Unit',
                    'ditolak_pusbin'          => 'Ditolak Pusbin',
                    'selesai'                 => 'Selesai',
                ] as $val => $label)
                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="font-weight-bold small">Filter Jadwal</label>
              <select name="jadwal_id" class="form-control form-control-sm">
                <option value="">-- Semua Jadwal --</option>
                @foreach ($jadwals as $j)
                <option value="{{ $j->id }}" {{ request('jadwal_id') == $j->id ? 'selected' : '' }}>{{ $j->judul }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-default btn-sm btn-block mt-3">
                <i class="fas fa-search mr-1"></i> Filter
              </button>
            </div>
            @if (request()->hasAny(['status','jadwal_id']))
            <div class="col-md-2">
              <a href="{{ route('ujikom.permohonan.index') }}" class="btn btn-light btn-sm btn-block mt-3">Reset</a>
            </div>
            @endif
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm" id="tabelPendaftaran">
            <thead class="thead-light">
              <tr>
                <th width="40">No</th>
                <th>Kode Pendaftaran</th>
                <th>Jadwal Ujikom</th>
                <th>Unit Kerja</th>
                <th>Jenis</th>
                <th width="70">Peserta</th>
                <th width="170">Status</th>
                <th width="90">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($pendaftarans as $i => $p)
              <tr>
                <td class="text-center">{{ $pendaftarans->firstItem() + $i }}</td>
                <td><small class="font-weight-bold">{{ $p->kode_pendaftaran }}</small></td>
                <td>
                  <small>{{ $p->jadwal?->judul ?? '-' }}</small><br>
                  <small class="text-muted">{{ $p->jadwal?->tanggal_mulai?->format('d/m/Y') ?? '' }}</small>
                </td>
                <td><small>{{ $p->unitKerja?->nama_rumahsakit ?? '-' }}</small></td>
                <td class="text-center">
                  <span class="badge badge-{{ $p->jenis_pendaftaran === 'batch' ? 'primary' : 'secondary' }}">
                    {{ $p->jenis_pendaftaran === 'batch' ? 'Batch' : 'Mandiri' }}
                  </span>
                </td>
                <td class="text-center">{{ $p->peserta_count ?? $p->peserta->count() }}</td>
                <td>
                  @php
                    $extraStyle = '';
                    if ($p->status === 'diajukan_pusbin') $extraStyle = 'background:#6f42c1;color:#fff;border:none;';
                    elseif ($p->status === 'diverifikasi_pusbin') $extraStyle = 'background:#20c997;color:#fff;border:none;';
                  @endphp
                  <span class="badge badge-{{ $p->badge_status }}" style="{{ $extraStyle }}">
                    {{ $p->label_status }}
                  </span>
                </td>
                <td class="text-center">
                  <a href="{{ route('ujikom.permohonan.show', $p->id) }}" class="btn btn-info btn-xs" title="Lihat">
                    <i class="fas fa-eye"></i>
                  </a>
                  @if ($p->bisaDiedit() && ($p->dibuat_oleh === Auth::id() || Auth::user()->hasRole(['admin','super_admin'])))
                  <a href="{{ route('ujikom.permohonan.edit', $p->id) }}" class="btn btn-warning btn-xs" title="Edit">
                    <i class="fas fa-pencil-alt"></i>
                  </a>
                  <form action="{{ route('ujikom.permohonan.destroy', $p->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus permohonan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                  </form>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                  Belum ada permohonan pendaftaran.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">{{ $pendaftarans->withQueryString()->links() }}</div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
  $('#tabelPendaftaran').DataTable({
    paging: false, info: false, ordering: true,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data.' }
  });
});
</script>
@endpush
