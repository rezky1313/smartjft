@extends('layouts.users.master')
@section('title', 'Tabel Pengembangan Karir JFT')
@section('isi')

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="preview-card mb-4">
  <div class="preview-header">
    <span class="preview-header-title">Tabel Pengembangan Karir JFT</span>
    <span class="preview-header-subtitle d-block">Prediksi kenaikan pangkat &amp; status kesiapan kenaikan jenjang seluruh pemangku JFT aktif</span>
  </div>
  <div class="preview-body">

    @if($peringatanLulusBelumDiangkat > 0)
      <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
        <span>
          <i class="fas fa-exclamation-triangle mr-1"></i>
          <strong>{{ $peringatanLulusBelumDiangkat }} pegawai</strong> sudah lulus uji kompetensi lebih dari 6 bulan lalu tapi belum ada record pengangkatan yang cocok.
        </span>
        <a href="{{ route('pengangkatan.index') }}" class="btn btn-sm btn-outline-dark mt-2 mt-md-0">Lihat Daftar Pengangkatan</a>
      </div>
    @endif

    <div class="form-row align-items-end mb-3">
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Unit Kerja</label>
        <select id="filter-unit-kerja" class="form-control form-control-sm">
          <option value="">Semua Unit Kerja</option>
          @foreach($unitKerjaList as $u)
            <option value="{{ $u->id }}">{{ $u->nama_unit_kerja }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Jenjang</label>
        <select id="filter-jenjang" class="form-control form-control-sm">
          <option value="">Semua Jenjang</option>
          @foreach($daftarJenjang as $kode => $label)
            <option value="{{ $kode }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3">
        <label class="font-weight-bold small">Status Komposit</label>
        <select id="filter-status" class="form-control form-control-sm">
          <option value="">Semua Status</option>
          @foreach(['Siap Diangkat','Perlu Ujikom','AK Kurang','Formasi Penuh','Predikat Belum Terpenuhi','Data Tidak Lengkap'] as $s)
            <option value="{{ $s }}">{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3">
        <button type="button" id="btnResetFilter" class="btn btn-outline-secondary btn-sm">Reset Filter</button>
      </div>
    </div>

    <form id="formWorkingList" action="{{ route('user.pkr.working-list.export') }}" method="POST">
      @csrf
      <div id="hiddenSdmIds"></div>

      <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <small class="text-muted">
          <span class="do-badge" style="background:#fef3c7; color:#92400e;">■</span>
          Baris ditandai = prediksi kenaikan pangkat &le; 3 bulan dari sekarang (kandidat working list).
          <strong>Centang hanya berlaku untuk baris yang sedang tampil di halaman ini</strong> — tabel dipaginasi server-side, centang tidak otomatis ikut saat pindah halaman.
        </small>
        <button type="button" class="btn btn-success btn-sm mt-2 mt-md-0" onclick="konfirmasiExportWorkingList()">
          <i class="fas fa-file-excel mr-1"></i> Export Working List Terpilih
        </button>
      </div>

      <div class="table-responsive">
        <table id="tabelPkr" class="table table-hover mb-0 w-100">
          <thead style="background:#f8fafc;">
            <tr>
              <th width="30"><input type="checkbox" id="checkAll"></th>
              <th>Nama</th>
              <th>NIP</th>
              <th>Unit Kerja</th>
              <th>Jenjang Saat Ini</th>
              <th>Prediksi Naik Pangkat</th>
              <th>Status Komposit Jenjang</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
  const table = $('#tabelPkr').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("user.pkr.data") }}',
      data: function (d) {
        d.unit_kerja_id = $('#filter-unit-kerja').val();
        d.jenjang_kode = $('#filter-jenjang').val();
        d.status_komposit = $('#filter-status').val();
      }
    },
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'asc']],
    columns: [
      { data: 'checkbox', orderable: false, searchable: false },
      { data: 'nama_lengkap' },
      { data: 'nip' },
      { data: 'unit_kerja' },
      { data: 'jenjang' },
      { data: 'prediksi_pangkat', orderable: false },
      { data: 'status_komposit', orderable: false },
      { data: 'aksi', orderable: false, searchable: false, className: 'text-center' },
    ],
    createdRow: function (row, data) {
      if (data.dekat) {
        $(row).css('background', '#fffbeb');
      }
    },
  });

  $('#filter-unit-kerja, #filter-jenjang, #filter-status').on('change', function () {
    table.ajax.reload();
  });

  $('#btnResetFilter').on('click', function () {
    $('#filter-unit-kerja, #filter-jenjang, #filter-status').val('');
    table.ajax.reload();
  });

  // Event delegation: baris tabel diganti tiap draw ajax, checkbox lama tidak ada lagi.
  $('#checkAll').on('change', function () {
    $('#tabelPkr tbody .chkSdm').prop('checked', $(this).is(':checked'));
  });
  $('#tabelPkr tbody').on('change', '.chkSdm', function () {
    // uncheck checkAll kalau ada baris yg di-uncheck manual
    if (!$(this).is(':checked')) {
      $('#checkAll').prop('checked', false);
    }
  });
});

function konfirmasiExportWorkingList() {
  const ids = $('#tabelPkr tbody .chkSdm:checked').map(function () { return this.value; }).get();

  if (ids.length === 0) {
    Swal.fire({
      title: 'Belum Ada Baris Dipilih',
      text: 'Centang minimal satu pegawai (baris yang ditandai kuning) di halaman yang sedang tampil untuk di-export.',
      icon: 'warning',
    });
    return;
  }

  Swal.fire({
    title: 'Export Working List?',
    text: `${ids.length} pegawai akan di-export ke Excel.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Export',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#28a745',
  }).then((result) => {
    if (result.isConfirmed) {
      const container = document.getElementById('hiddenSdmIds');
      container.innerHTML = '';
      ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'sdm_ids[]';
        input.value = id;
        container.appendChild(input);
      });
      document.getElementById('formWorkingList').submit();
    }
  });
}
</script>
@endpush
