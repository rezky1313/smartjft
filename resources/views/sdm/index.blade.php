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
      <table id="sdmTable" class="table table-hover mb-0 w-100">
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
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
  const table = $('#sdmTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("user.sdm.data") }}',
      data: function (d) {
        d.filter_status = $('#filterStatusFormasi').val();
      }
    },
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[2, 'asc']], // sort Nama
    columns: [
      {
        data: null, orderable: false, searchable: false,
        render: function (data, type, row, meta) {
          return meta.settings._iDisplayStart + meta.row + 1;
        }
      },
      { data: 'nip' },
      { data: 'nama_lengkap' },
      { data: 'jenis_kelamin' },
      { data: 'status_kepegawaian' },
      { data: 'pangkat_golongan' },
      { data: 'jenjang' },
      { data: 'unit_kerja' },
      { data: 'provinsi' },
      { data: 'tmt' },
      { data: 'masa_jabatan', orderable: false },
      { data: 'status_formasi', orderable: false },
      { data: 'aktif' },
      { data: 'aksi', orderable: false, searchable: false, className: 'text-center' },
    ],
  });

  // Handle Filter Status Formasi -- sekarang reload AJAX, bukan reload halaman penuh
  $('#filterStatusFormasi').on('change', function () {
    table.ajax.reload();
  });
});

const csrfToken = @json(csrf_token());
const urlSdmBase = @json(url('user/sdm'));

function konfirmasiHapusSdm(id) {
  Swal.fire({
    title: 'Hapus Data Pegawai?',
    text: 'Data pegawai ini akan dihapus permanen.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc3545',
  }).then((result) => {
    if (!result.isConfirmed) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${urlSdmBase}/${id}`;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden'; csrfInput.name = '_token'; csrfInput.value = csrfToken;

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden'; methodInput.name = '_method'; methodInput.value = 'DELETE';

    form.appendChild(csrfInput);
    form.appendChild(methodInput);
    document.body.appendChild(form);
    form.submit();
  });
}
</script>
@endpush
