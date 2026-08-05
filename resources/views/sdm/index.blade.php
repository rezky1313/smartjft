@extends('layouts.users.master')
@section('title', 'Data Pegawai')
@section('isi')

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="preview-card mb-4">
  <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="preview-header-title">Data Pemangku JFT</span>
      <span class="preview-header-subtitle d-block">Kelola data individu pemangku Jabatan Fungsional Transportasi</span>
    </div>
    @can('create pegawai')
    <div>
      <a href="{{ route('user.sdm.create') }}" class="btn btn-primary btn-sm mr-1">+ Tambah Pemangku JFT</a>
      <a href="{{ route('user.sdm.import.form') }}" class="btn btn-success btn-sm mr-1">+ Import Excel</a>
      <a href="{{ route('user.sdm.template') }}" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-file-excel mr-1"></i> Download Template
      </a>
    </div>
    @endcan
  </div>

  <div class="preview-body p-0">
    <div class="p-3 border-bottom" style="background:#f8fafc;">
      <select id="filterStatusFormasi" class="form-control form-control-sm" style="width: 220px;">
        <option value="">Semua Status Formasi</option>
        <option value="terpenuhi" {{ $filterStatus === 'terpenuhi' ? 'selected' : '' }}>Terpenuhi</option>
        <option value="di_luar_formasi" {{ $filterStatus === 'di_luar_formasi' ? 'selected' : '' }}>Di Luar Formasi</option>
      </select>
    </div>

    <div class="table-responsive">
      <table id="sdmTable" class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>JK</th>
            <th>Status</th>
            <th>Pangkat/Gol</th>
            <th>Jenjang</th>
            <th>Unit Kerja</th>
            <th>Provinsi</th>
            <th>TMT</th>
            <th>Masa Jabatan</th>
            <th>Status Formasi</th>
            <th>Aktif</th>
            <th class="text-center" width="150">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sdm as $i => $row)
            @php
              $uk   = $row->formasi?->unitKerja ?? $row->unitKerja;
              $kab  = $uk?->regency;
              $prov = $kab?->province;
            @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $row->nip ?? '-' }}</td>
              <td>{{ $row->nama_lengkap }}</td>
              <td>{{ $row->jenis_kelamin }}</td>
              <td>{{ $row->status_kepegawaian }}</td>
              <td>{{ $row->pangkat_golongan }}</td>
              <td>{{ $row->formasi?->jenjang?->nama_jenjang ?? '-' }}</td>
              <td>{{ $uk->nama_unit_kerja ?? '-' }}</td>
              <td>{{ $prov->name ?? '-' }}</td>
              <td>{{ $row->tmt_pengangkatan?->format('d-m-Y') ?? '-' }}</td>
              <td>{{ $row->masa_jabatan ?? '-' }}</td>
              <td>
                @if($row->formasi_jabatan_id)
                  @if($row->status_formasi === 'di_luar_formasi')
                    <span class="do-badge" style="background:#fee2e2; color:#991b1b;">Di Luar Formasi</span>
                  @else
                    <span class="do-badge" style="background:#d1fae5; color:#065f46;">Terpenuhi</span>
                  @endif
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                @if($row->aktif)
                  <span class="do-badge" style="background:#d1fae5; color:#065f46;">Aktif</span>
                @else
                  <span class="do-badge" style="background:#fee2e2; color:#991b1b;">Nonaktif</span>
                @endif
              </td>
              <td class="text-center">
                <div class="d-flex justify-content-center">
                  <a href="{{ route('user.pkr.show', $row->id) }}" class="btn btn-sm btn-outline-info mr-1" title="Pengembangan Karir">
                    <i class="fas fa-id-card"></i>
                  </a>
                  @can('edit pegawai')
                  <a href="{{ route('user.sdm.edit', $row->id) }}" class="btn btn-sm btn-outline-warning mr-1" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  @endcan
                  @can('delete pegawai')
                  <form action="{{ route('user.sdm.destroy', $row->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus SDM ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                  @endcan
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
  $('#sdmTable').DataTable({
    pageLength: 10,
    lengthMenu: [10,25,50,100],
    order: [[2,'asc']], // sort Nama
  });

  // Handle Filter Status Formasi
  $('#filterStatusFormasi').on('change', function() {
    const status = $(this).val();
    const url = new URL(window.location);

    if (status === '') {
      url.searchParams.delete('filter_status');
    } else {
      url.searchParams.set('filter_status', status);
    }

    window.location.href = url.toString();
  });
});
</script>
@endpush
