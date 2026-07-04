@extends('layouts.users.master')

@section('title', 'Pertimbangan Pengangkatan JFT — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">
    <h3 class="mb-4"><i class="fas fa-user-check mr-2"></i>Pertimbangan Pengangkatan JFT</h3>

    {{-- Stat Cards --}}
    <div class="row mb-4">
      <div class="col-md-3 col-6 mb-3">
        <div class="small-box bg-info">
          <div class="inner"><h3>{{ $stats['total'] }}</h3><p>Total Permohonan</p></div>
          <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
      </div>
      <div class="col-md-3 col-6 mb-3">
        <div class="small-box bg-primary">
          <div class="inner"><h3>{{ $stats['diajukan'] }}</h3><p>Menunggu Diproses</p></div>
          <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
      </div>
      <div class="col-md-3 col-6 mb-3">
        <div class="small-box bg-teal">
          <div class="inner"><h3>{{ $stats['disetujui'] }}</h3><p>Disetujui</p></div>
          <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
      </div>
      <div class="col-md-3 col-6 mb-3">
        <div class="small-box bg-success">
          <div class="inner"><h3>{{ $stats['selesai'] }}</h3><p>Selesai</p></div>
          <div class="icon"><i class="fas fa-flag-checkered"></i></div>
        </div>
      </div>
    </div>

    {{-- Filter & Tombol --}}
    <div class="card">
      <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">Daftar Permohonan</h3>
        @hasanyrole('admin_unit|admin|super_admin')
        <a href="{{ route('pengangkatan.create') }}" class="btn btn-primary btn-sm ml-auto">
          <i class="fas fa-plus mr-1"></i>Buat Permohonan
        </a>
        @endhasanyrole
      </div>
      <div class="card-body">
        {{-- Filter --}}
        <form method="GET" class="row mb-3">
          <div class="col-md-3 col-6 mb-2">
            <select name="status" class="form-control form-control-sm">
              <option value="">Semua Status</option>
              @foreach (['draft','diajukan','diproses','disetujui','ditolak','selesai'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          @hasanyrole('admin|super_admin')
          <div class="col-md-3 col-6 mb-2">
            <select name="unit_kerja_id" class="form-control form-control-sm select2">
              <option value="">Semua Unit Kerja</option>
              @foreach ($unitKerjaList as $uk)
                <option value="{{ $uk->no_rs }}" @selected(request('unit_kerja_id') == $uk->no_rs)>{{ $uk->nama_rumahsakit }}</option>
              @endforeach
            </select>
          </div>
          @endhasanyrole
          <div class="col-md-2 col-6 mb-2">
            <select name="tahun" class="form-control form-control-sm">
              <option value="">Semua Tahun</option>
              @for ($y = now()->year; $y >= 2024; $y--)
                <option value="{{ $y }}" @selected(request('tahun') == $y)>{{ $y }}</option>
              @endfor
            </select>
          </div>
          <div class="col-md-2 col-6 mb-2">
            <button class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('pengangkatan.index') }}" class="btn btn-sm btn-light">Reset</a>
          </div>
        </form>

        {{-- Tabel --}}
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm" id="tblPengangkatan" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Kode Permohonan</th>
                <th>Unit Kerja</th>
                <th width="100" class="text-center">Tanggal</th>
                <th width="70" class="text-center">Kandidat</th>
                <th width="70" class="text-center">Direkomendasi</th>
                <th width="100" class="text-center">Status</th>
                <th width="80" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($permohonan as $i => $p)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td><strong>{{ $p->kode_permohonan }}</strong></td>
                <td>{{ $p->unitKerja?->nama_rumahsakit ?? '-' }}</td>
                <td class="text-center">{{ $p->tanggal_permohonan?->format('d M Y') }}</td>
                <td class="text-center">{{ $p->kandidat_count }}</td>
                <td class="text-center">
                  <span class="badge badge-success">{{ $p->kandidat_direkomendasikan_count }}</span>
                </td>
                <td class="text-center">
                  <span class="badge badge-{{ $p->badge_status }}">{{ $p->label_status }}</span>
                </td>
                <td class="text-center">
                  <a href="{{ route('pengangkatan.show', $p->id) }}" class="btn btn-xs btn-outline-primary" title="Detail">
                    <i class="fas fa-eye"></i>
                  </a>
                  @if ($p->status === 'draft')
                  <a href="{{ route('pengangkatan.edit', $p->id) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Belum ada permohonan pengangkatan.</td></tr>
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
$(function(){
  if ($.fn.DataTable) {
    $('#tblPengangkatan').DataTable({
      paging: true, searching: true, ordering: true,
      language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data' }
    });
  }
  if ($.fn.select2) {
    $('.select2').select2({ width: '100%', placeholder: 'Pilih…', allowClear: true });
  }
});
</script>
@endpush
