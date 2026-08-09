@extends('layouts.users.master')
@section('title', 'Riwayat Diklat')
@section('isi')

<div class="preview-card mb-4">
  <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="preview-header-title">Riwayat Diklat Pegawai</span>
      <span class="preview-header-subtitle d-block">Data pendukung keputusan pengembangan karir JFT</span>
    </div>
    <div>
      <a href="{{ route('karir.diklat.rekap') }}" class="btn btn-outline-primary btn-sm mr-1">Rekap per Unit</a>
      @can('create pegawai')
      <a href="{{ route('karir.diklat.create') }}" class="btn btn-primary btn-sm">+ Tambah Diklat</a>
      @endcan
    </div>
  </div>
  <div class="preview-body">

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($belumDiklatCount > 0)
      <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
        <span>
          <i class="fas fa-exclamation-triangle mr-1"></i>
          <strong>{{ $belumDiklatCount }} pegawai</strong> belum pernah mengikuti diklat sama sekali.
        </span>
        <a href="{{ route('karir.diklat.belum') }}" class="btn btn-sm btn-outline-dark mt-2 mt-md-0">Lihat Daftar</a>
      </div>
    @endif

    <div class="form-row align-items-end mb-3">
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Unit Kerja</label>
        @if($unitTerkunci)
          <select class="form-control form-control-sm" disabled>
            <option>{{ $unitKerjaList->first()->nama_unit_kerja ?? '-' }}</option>
          </select>
        @else
          <select id="filter-unit-kerja" class="form-control form-control-sm">
            <option value="">Semua Unit Kerja</option>
            @foreach($unitKerjaList as $u)
              <option value="{{ $u->id }}">{{ $u->nama_unit_kerja }}</option>
            @endforeach
          </select>
        @endif
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Jenis Diklat</label>
        <select id="filter-jenis" class="form-control form-control-sm">
          <option value="">Semua Jenis</option>
          @foreach($daftarJenis as $kode => $label)
            <option value="{{ $kode }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="table-responsive">
      <table id="tabelDiklat" class="table table-hover mb-0 w-100">
        <thead style="background:#f8fafc;">
          <tr>
            <th>Nama</th>
            <th>NIP</th>
            <th>Unit Kerja</th>
            <th>Jenis Diklat Terakhir</th>
            <th class="text-center">Jumlah Diklat</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
  const unitTerkunci = @json($unitTerkunci);

  const table = $('#tabelDiklat').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("karir.diklat.data") }}',
      data: function (d) {
        d.unit_kerja_id = unitTerkunci || $('#filter-unit-kerja').val();
        d.jenis_diklat = $('#filter-jenis').val();
      }
    },
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [[0, 'asc']],
    columns: [
      { data: 'nama_lengkap' },
      { data: 'nip' },
      { data: 'unit_kerja' },
      { data: 'jenis_terakhir' },
      { data: 'jumlah', className: 'text-center' },
      { data: 'aksi', orderable: false, searchable: false, className: 'text-center' },
    ],
  });

  $('#filter-unit-kerja, #filter-jenis').on('change', function () {
    table.ajax.reload();
  });
});
</script>
@endpush
