@extends('layouts.users.master')
@section('title', 'Edit Permohonan Uji Kompetensi')
@section('isi')

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Edit Permohonan Uji Kompetensi</h4>
    <a href="{{ route('ujikom.show', $permohonan->id) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('ujikom.update', $permohonan->id) }}" enctype="multipart/form-data" id="formPermohonan">
        @csrf
        @method('PUT')

        {{-- Informasi Permohonan --}}
        <div class="row mb-4">
          <div class="col-12">
            <h5 class="border-bottom pb-2">Informasi Permohonan</h5>
          </div>
        </div>

        <div class="alert alert-info">
          <strong>Nomor Permohonan:</strong> {{ $permohonan->nomor_permohonan }}
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Unit Kerja <span class="text-danger">*</span></label>
            <select name="unit_kerja_id" id="unitKerja" class="form-control select2" required onchange="filterPegawaiByUnitKerja()">
              <option value="">Pilih Unit Kerja</option>
              @foreach($unitKerja as $uk)
                <option value="{{ $uk->no_rs }}" {{ $permohonan->unit_kerja_id == $uk->no_rs ? 'selected' : '' }}>
                  {{ $uk->nama_rumahsakit }}
                </option>
              @endforeach
            </select>
            @error('unit_kerja_id')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Tanggal Permohonan <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_permohonan" class="form-control"
                   value="{{ $permohonan->tanggal_permohonan->format('Y-m-d') }}" required>
            @error('tanggal_permohonan')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-12">
            <label class="form-label">File Surat Permohonan (PDF)</label>
            <input type="file" name="file_surat_permohonan" class="form-control" accept=".pdf">
            <small class="text-muted">
              Kosongkan jika tidak ingin mengubah file.
              @if($permohonan->file_surat_permohonan)
                <br>File saat ini: <a href="{{ asset('storage/' . $permohonan->file_surat_permohonan) }}" target="_blank">Lihat File</a>
              @endif
            </small>
            @error('file_surat_permohonan')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Daftar Peserta --}}
        <div class="row mb-4">
          <div class="col-12">
            <h5 class="border-bottom pb-2">Daftar Peserta</h5>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-12">
            <label class="form-label">Pilih Pegawai untuk Ditambahkan</label>
            <select id="pegawaiSelect" class="form-control select2" style="width: 100%;">
              <option value="">-- Pilih Pegawai (Pilih Unit Kerja Terlebih Dahulu) --</option>
              @foreach($pegawai as $p)
                @php
                  // Cek unit kerja pegawai (langsung atau lewat formasi)
                  $unitKerjaIds = [];
                  if ($p->unit_kerja_id) {
                    $unitKerjaIds[] = $p->unit_kerja_id;
                  }
                  if ($p->formasi && $p->formasi->unit_kerja_id) {
                    $unitKerjaIds[] = $p->formasi->unit_kerja_id;
                  }

                  $namaUnitKerja = '';
                  if ($p->unitKerja) {
                    $namaUnitKerja = $p->unitKerja->nama_rumahsakit;
                  } elseif ($p->formasi && $p->formasi->unit_kerja) {
                    $namaUnitKerja = $p->formasi->unit_kerja->nama_rumahsakit;
                  }

                  $namaJabatan = $p->formasi ? $p->formasi->nama_formasi : '-';
                  $namaJenjang = $p->formasi && $p->formasi->jenjang ? $p->formasi->jenjang->nama_jenjang : '-';
                  $textPegawai = $p->nama_lengkap . ' - ' . ($p->nip ?: 'N/A') . ' - ' . $namaJabatan . ($namaJenjang !== '-' ? ' (' . $namaJenjang . ')' : '');

                  // Handle empty unit kerja IDs
                  $unitKerjaIdsStr = !empty($unitKerjaIds) ? implode(',', $unitKerjaIds) : '';
                @endphp
                <option value="{{ $p->id }}"
                        class="pegawai-option"
                        data-unit-kerja-ids="{{ $unitKerjaIdsStr }}"
                        data-nama="{{ $p->nama_lengkap }}"
                        data-nip="{{ $p->nip ?? '' }}"
                        data-jabatan="{{ $namaJabatan }}"
                        data-jenjang="{{ $namaJenjang }}">
                  {{ $textPegawai }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Pilih unit kerja terlebih dahulu, lalu pilih pegawai dari dropdown (bisa dicari dengan mengetik nama atau NIP)</small>
          </div>
        </div>

        {{-- Tabel Peserta --}}
        <div class="row mb-4">
          <div class="col-12">
            <div class="table-responsive">
              <table class="table table-bordered table-striped" id="tabelPeserta">
                <thead>
                  <tr>
                    <th width="50">No</th>
                    <th>Nama Pegawai</th>
                    <th>NIP</th>
                    <th>Jabatan</th>
                    <th>Jenjang</th>
                    <th width="80">Aksi</th>
                  </tr>
                </thead>
                <tbody id="tbodyPeserta">
                  {{-- Peserta akan ditambahkan di sini --}}
                </tbody>
              </table>
              <input type="hidden" name="peserta[]" id="pesertaInput">
            </div>
            @error('peserta')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Tombol Aksi --}}
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between">
              <a href="{{ route('ujikom.show', $permohonan->id) }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
              </a>
              <div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i> Simpan Perubahan
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
let pesertaList = [];
let counter = 0;

// Load existing peserta
@php
  $existingPeserta = [];
  foreach($permohonan->peserta as $p) {
    $pegawai = $p->pegawai;
    $existingPeserta[] = [
      'id' => $p->pegawai_id,
      'nama' => $pegawai->nama_lengkap,
      'nip' => $pegawai->nip,
      'jabatan' => $pegawai->formasi?->nama_formasi ?? '-',
      'jenjang' => $pegawai->formasi?->jenjang?->nama_jenjang ?? '-',
    ];
  }
@endphp

const existingPeserta = @json($existingPeserta);

$(function() {
  // Initialize Select2
  $('.select2').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: '-- Pilih Pegawai (Pilih Unit Kerja Terlebih Dahulu) --',
    allowClear: true
  });

  // Disable pegawai select saat awal
  if (!$('#unitKerja').val()) {
    $('#pegawaiSelect').prop('disabled', true);
    // Disable semua option pegawai
    $('#pegawaiSelect option[data-unit-kerja-id]').each(function() {
      $(this).prop('disabled', true);
    });
  }

  // Load existing peserta
  existingPeserta.forEach(function(p) {
    pesertaList.push({
      counter: ++counter,
      id: p.id,
      nama: p.nama,
      nip: p.nip,
      jabatan: p.jabatan,
      jenjang: p.jenjang,
    });
  });

  renderTabelPeserta();

  // Event listener saat pegawai dipilih
  $('#pegawaiSelect').on('select2:select', function(e) {
    var data = e.params.data;
    tambahPeserta(data.id, data.text);
    $(this).val(null).trigger('change');
  });
});

// Filter pegawai berdasarkan unit kerja yang dipilih
function filterPegawaiByUnitKerja() {
  var unitKerjaId = $('#unitKerja').val();

  if (!unitKerjaId) {
    // Jika tidak ada unit kerja yang dipilih, disable dropdown pegawai
    $('#pegawaiSelect').prop('disabled', true);
    $('#pegawaiSelect').val(null).trigger('change');

    // Reset semua ke disabled
    $('#pegawaiSelect option[data-unit-kerja-ids]').each(function() {
      $(this).prop('disabled', true);
    });

    // Hapus semua peserta jika ada (konfirmasi dulu jika ada peserta)
    if (pesertaList.length > 0) {
      pesertaList = [];
      counter = 0;
      renderTabelPeserta();
    }
    return;
  }

  // Convert selected unit kerja ID to string and trim
  unitKerjaId = String(unitKerjaId).trim();

  // Enable dropdown pegawai
  $('#pegawaiSelect').prop('disabled', false);

  // Hancurkan Select2 dulu
  $('#pegawaiSelect').select2('destroy');

  // Filter options berdasarkan unit kerja
  $('.pegawai-option').each(function() {
    var pegawaiId = $(this).val();

    if (pegawaiId === '') {
      // Skip placeholder
      return;
    }

    // Get the raw attribute value to avoid jQuery's data parsing
    var unitKerjaIdsAttr = $(this).attr('data-unit-kerja-ids');

    // Cek apakah pegawai ini termasuk dalam unit kerja yang dipilih
    var isMatch = false;

    if (unitKerjaIdsAttr && unitKerjaIdsAttr !== '') {
      // Split by comma and trim each value
      var unitKerjaIdArray = unitKerjaIdsAttr.split(',').map(function(id) {
        return String(id).trim();
      });

      // Check if selected unit kerja ID is in the array
      if (unitKerjaIdArray.indexOf(unitKerjaId) !== -1) {
        isMatch = true;
      }
    }

    if (isMatch) {
      $(this).prop('disabled', false);
    } else {
      $(this).prop('disabled', true);
    }
  });

  // Re-initialize Select2 dengan options yang sudah difilter
  $('#pegawaiSelect').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: '-- Pilih Pegawai --',
    allowClear: true,
    // Template result untuk menyembunyikan option yang disabled
    templateResult: function(result) {
      if (!result.id) {
        return result.text;
      }
      // Cek apakah option disabled
      var $option = $(result.element);
      if ($option.prop('disabled')) {
        // Return null untuk menyembunyikan dari dropdown
        return null;
      }
      return result.text;
    }
  });

  // Reset pilihan pegawai
  $('#pegawaiSelect').val(null).trigger('change');

  // Hapus semua peserta yang sudah ditambahkan (karena unit kerja berubah)
  if (pesertaList.length > 0) {
    pesertaList = [];
    counter = 0;
    renderTabelPeserta();
  }
}

function tambahPeserta(pegawaiId, pegawaiText) {
  // Cari data pegawai dari option
  var selectedOption = $('#pegawaiSelect option[value="' + pegawaiId + '"]');

  if (!selectedOption.length) {
    swal('Peringatan', 'Data pegawai tidak ditemukan!', 'warning');
    return;
  }

  var pegawaiData = {
    id: pegawaiId,
    nama: selectedOption.data('nama'),
    nip: selectedOption.data('nip'),
    jabatan: selectedOption.data('jabatan'),
    jenjang: selectedOption.data('jenjang'),
  };

  // Cek duplikasi
  if (pesertaList.find(p => p.id === pegawaiId)) {
    swal('Peringatan', 'Pegawai ini sudah ditambahkan!', 'warning');
    return;
  }

  // Tambahkan ke list
  pesertaList.push({
    counter: ++counter,
    id: pegawaiData.id,
    nama: pegawaiData.nama,
    nip: pegawaiData.nip,
    jabatan: pegawaiData.jabatan,
    jenjang: pegawaiData.jenjang,
  });

  renderTabelPeserta();
}

function hapusPeserta(counter) {
  pesertaList = pesertaList.filter(p => p.counter !== counter);
  renderTabelPeserta();
}

function renderTabelPeserta() {
  var tbody = $('#tbodyPeserta');

  tbody.empty();

  if (pesertaList.length === 0) {
    tbody.append('<tr><td colspan="6" class="text-center text-muted">Belum ada peserta ditambahkan</td></tr>');
  } else {
    pesertaList.forEach(function(peserta, index) {
      tbody.append(`
        <tr>
          <td>${index + 1}</td>
          <td>${peserta.nama}</td>
          <td>${peserta.nip || '-'}</td>
          <td>${peserta.jabatan}</td>
          <td>${peserta.jenjang}</td>
          <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusPeserta(${peserta.counter})">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `);
    });
  }

  // Update hidden inputs
  $('input[name="peserta[]"]').remove();
  pesertaList.forEach(function(p) {
    $('#formPermohonan').append('<input type="hidden" name="peserta[]" value="' + p.id + '">');
  });
}
</script>
@endpush
@endsection
