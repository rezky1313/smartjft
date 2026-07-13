@extends('layouts.users.master')

@section('title', isset($permohonan) ? 'Edit Permohonan Pengangkatan' : 'Buat Permohonan Pengangkatan')

@section('isi')
<div class="row">
  <div class="col-md-10 offset-md-1">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">
          <i class="fas fa-file-alt mr-2"></i>
          {{ isset($permohonan) ? 'Edit Permohonan Pengangkatan' : 'Buat Permohonan Pengangkatan' }}
        </h3>
      </div>

      <form id="formPengangkatan"
            action="{{ isset($permohonan) ? route('pengangkatan.update', $permohonan->id) : route('pengangkatan.store') }}"
            method="POST" enctype="multipart/form-data">
        @csrf
        @if (isset($permohonan)) @method('PUT') @endif

        <div class="card-body">
          {{-- Info --}}
          <div class="alert alert-info py-2">
            <i class="fas fa-info-circle mr-1"></i>
            Peserta yang diusulkan di bawah otomatis berstatus "Direkomendasikan" — tidak ada proses
            ranking. Sistem hanya menolak usulan yang melebihi sisa formasi jabatan tujuan.
          </div>

          {{-- Unit Kerja --}}
          <div class="form-group">
            <label>Unit Kerja <span class="text-danger">*</span></label>
            @if (isset($unitKerja) && $unitKerja)
              {{-- Admin unit: auto-fill --}}
              <input type="hidden" name="unit_kerja_id" id="unitKerjaId" value="{{ $unitKerja->id }}">
              <input type="text" class="form-control" value="{{ $unitKerja->nama_unit_kerja }}" readonly>
            @else
              <select name="unit_kerja_id" id="unitKerjaId" class="form-control select2 @error('unit_kerja_id') is-invalid @enderror" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitKerjaList as $uk)
                  <option value="{{ $uk->id }}" @selected(old('unit_kerja_id', $permohonan->unit_kerja_id ?? '') == $uk->id)>
                    {{ $uk->nama_unit_kerja }}
                  </option>
                @endforeach
              </select>
              @error('unit_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @endif
          </div>

          {{-- Tanggal Permohonan --}}
          <div class="form-group">
            <label>Tanggal Permohonan <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_permohonan"
                   class="form-control @error('tanggal_permohonan') is-invalid @enderror"
                   value="{{ old('tanggal_permohonan', isset($permohonan) ? $permohonan->tanggal_permohonan->format('Y-m-d') : now()->format('Y-m-d')) }}"
                   required>
            @error('tanggal_permohonan') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Upload Surat --}}
          <div class="form-group">
            <label>Surat Permohonan (PDF)</label>
            <input type="file" name="file_surat_permohonan" class="form-control-file @error('file_surat_permohonan') is-invalid @enderror" accept=".pdf">
            @if (isset($permohonan) && $permohonan->file_surat_permohonan)
              <small class="text-muted d-block mt-1">
                File saat ini: <a href="{{ asset('storage/' . $permohonan->file_surat_permohonan) }}" target="_blank">Lihat PDF</a>
              </small>
            @endif
            @error('file_surat_permohonan') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-muted">Maks. 5 MB, format PDF.</small>
          </div>

          <hr>

          {{-- ==================== PESERTA YANG DIUSULKAN ==================== --}}
          <div class="form-group">
            <label class="font-weight-bold">Tambah Peserta yang Diusulkan <span class="text-danger">*</span></label>
            <div class="form-row align-items-center">
              <div class="col-md-9">
                <select id="pesertaPicker" class="form-control" style="width:100%;" disabled>
                  <option value="">-- Pilih Unit Kerja dahulu --</option>
                </select>
              </div>
              <div class="col-md-3">
                <button type="button" id="btnTambahPeserta" class="btn btn-primary btn-block" disabled>
                  <i class="fas fa-plus mr-1"></i>Tambah
                </button>
              </div>
            </div>
            <small class="text-muted">Hanya menampilkan pegawai yang sudah <strong>lulus uji kompetensi</strong> dan belum pernah diusulkan di permohonan lain yang masih berjalan.</small>
          </div>

          @error('peserta') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="tabelPeserta">
              <thead class="thead-light">
                <tr>
                  <th>Nama Pegawai</th>
                  <th width="110">NIP</th>
                  <th width="180">Jabatan &amp; Jenjang Tujuan</th>
                  <th width="80" class="text-center">Nilai</th>
                  <th width="160" class="text-center">Status Formasi</th>
                  <th width="50" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="tbodyPeserta">
                <tr id="rowKosong">
                  <td colspan="6" class="text-center text-muted py-3">Belum ada peserta ditambahkan.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="hiddenInputsPeserta"></div>
        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('pengangkatan.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
          </a>
          <div>
            <button type="submit" name="aksi" value="draft" id="btnSimpanDraft" class="btn btn-secondary">
              <i class="fas fa-save mr-1"></i>Simpan Draft
            </button>
            <button type="submit" name="aksi" value="ajukan" id="btnSimpanAjukan" class="btn btn-primary">
              <i class="fas fa-paper-plane mr-1"></i>Simpan &amp; Ajukan
            </button>
          </div>
        </div>
      </form>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  if ($.fn.select2) {
    $('.select2').select2({ width: '100%', placeholder: 'Pilih…', allowClear: true });
    $('#pesertaPicker').select2({ width: '100%', placeholder: 'Cari nama pegawai…' });
  }

  const urlPesertaLulus = @json(route('pengangkatan.peserta-lulus'));
  const urlValidasi     = @json(route('pengangkatan.validasi-formasi-peserta'));
  const permohonanId    = @json($permohonan->id ?? null);

  let daftarTersedia = [];   // pool hasil AJAX, dikurangi tiap kali ditambahkan
  let baris = [];            // { ujikom_hasil_id, pegawai_id, nama, nip, jabatan_tujuan_id, jabatan_tujuan, jenjang_tujuan, nilai, valid }

  // Pra-isi baris dari kandidat existing (mode edit)
  @if (isset($permohonan))
    @foreach ($permohonan->kandidat as $k)
    baris.push({
      ujikom_hasil_id: {{ $k->ujikom_hasil_id }},
      pegawai_id: {{ $k->pegawai_id }},
      nama: @json($k->pegawai?->nama_lengkap ?? '-'),
      nip: @json($k->pegawai?->nip ?? '-'),
      jabatan_tujuan_id: {{ $k->jabatan_tujuan_id }},
      jabatan_tujuan: @json($k->jabatanTujuan?->nama_formasi ?? '-'),
      jenjang_tujuan: @json($k->jenjang_tujuan),
      nilai: {{ $k->nilai_ujikom ?? 'null' }},
      valid: true,
      pesan: ''
    });
    @endforeach
  @endif

  function renderTabel() {
    const tbody = $('#tbodyPeserta');
    const hidden = $('#hiddenInputsPeserta');
    tbody.empty();
    hidden.empty();

    if (baris.length === 0) {
      tbody.append('<tr id="rowKosong"><td colspan="6" class="text-center text-muted py-3">Belum ada peserta ditambahkan.</td></tr>');
    }

    baris.forEach(function (b, i) {
      const badge = b.valid
        ? '<span class="badge badge-success">Sesuai Formasi</span>'
        : `<span class="badge badge-danger" title="${b.pesan}">Melebihi Formasi</span>`;
      const nilaiText = (b.nilai === null || b.nilai === undefined) ? '-' : Number(b.nilai).toFixed(1);

      tbody.append(`
        <tr class="${b.valid ? '' : 'table-danger'}">
          <td>${b.nama}</td>
          <td>${b.nip}</td>
          <td>${b.jabatan_tujuan} / ${b.jenjang_tujuan}</td>
          <td class="text-center">${nilaiText}</td>
          <td class="text-center">${badge}${b.valid ? '' : `<br><small class="text-danger">${b.pesan}</small>`}</td>
          <td class="text-center">
            <button type="button" class="btn btn-xs btn-outline-danger btn-hapus-peserta" data-index="${i}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `);

      hidden.append(`
        <input type="hidden" name="peserta[${i}][ujikom_hasil_id]" value="${b.ujikom_hasil_id}">
        <input type="hidden" name="peserta[${i}][pegawai_id]" value="${b.pegawai_id}">
        <input type="hidden" name="peserta[${i}][jabatan_tujuan_id]" value="${b.jabatan_tujuan_id}">
        <input type="hidden" name="peserta[${i}][jenjang_tujuan]" value="${b.jenjang_tujuan}">
      `);
    });

    const adaYangTidakValid = baris.some(b => !b.valid);
    $('#btnSimpanDraft, #btnSimpanAjukan').prop('disabled', adaYangTidakValid || baris.length === 0);
  }

  function isiPicker() {
    const picker = $('#pesertaPicker');
    picker.empty().append('<option value="">-- Pilih Peserta --</option>');

    const dipakai = new Set(baris.map(b => b.ujikom_hasil_id));
    daftarTersedia
      .filter(p => !dipakai.has(p.ujikom_hasil_id))
      .forEach(function (p) {
        const label = `${p.nama} (${p.nip}) — ${p.jabatan_tujuan}/${p.jenjang_tujuan} — Nilai: ${p.nilai ?? '-'}`;
        picker.append(new Option(label, p.ujikom_hasil_id, false, false));
      });

    picker.trigger('change');
  }

  function muatPesertaLulus(unitKerjaId) {
    if (!unitKerjaId) return;

    $('#pesertaPicker, #btnTambahPeserta').prop('disabled', true);
    $('#pesertaPicker').empty().append('<option value="">Memuat…</option>').trigger('change');

    $.getJSON(urlPesertaLulus, {
      unit_kerja_id: unitKerjaId,
      kecuali_permohonan_id: permohonanId
    }).done(function (data) {
      daftarTersedia = data;
      isiPicker();
      $('#pesertaPicker, #btnTambahPeserta').prop('disabled', false);
    }).fail(function () {
      $('#pesertaPicker').empty().append('<option value="">Gagal memuat data</option>').trigger('change');
    });
  }

  function validasiDanTambah(item) {
    const jumlahSama = baris.filter(b => b.jabatan_tujuan_id === item.jabatan_tujuan_id).length;

    $.post(urlValidasi, {
      _token: @json(csrf_token()),
      jabatan_tujuan_id: item.jabatan_tujuan_id,
      jumlah_sudah_diajukan_jabatan_sama: jumlahSama
    }).done(function (res) {
      baris.push({
        ujikom_hasil_id: item.ujikom_hasil_id,
        pegawai_id: item.pegawai_id,
        nama: item.nama,
        nip: item.nip,
        jabatan_tujuan_id: item.jabatan_tujuan_id,
        jabatan_tujuan: item.jabatan_tujuan,
        jenjang_tujuan: item.jenjang_tujuan,
        nilai: item.nilai,
        valid: res.valid,
        pesan: res.valid ? '' : res.pesan
      });
      renderTabel();
      isiPicker();
    }).fail(function () {
      swal ? swal('Gagal', 'Tidak bisa memvalidasi formasi. Coba lagi.', 'error')
           : alert('Tidak bisa memvalidasi formasi. Coba lagi.');
    });
  }

  $('#unitKerjaId').on('change', function () {
    baris = [];
    renderTabel();
    muatPesertaLulus($(this).val());
  });

  $('#btnTambahPeserta').on('click', function () {
    const id = parseInt($('#pesertaPicker').val(), 10);
    if (!id) return;
    const item = daftarTersedia.find(p => p.ujikom_hasil_id === id);
    if (!item) return;
    validasiDanTambah(item);
  });

  $(document).on('click', '.btn-hapus-peserta', function () {
    const idx = parseInt($(this).data('index'), 10);
    baris.splice(idx, 1);
    renderTabel();
    isiPicker();
  });

  $('#formPengangkatan').on('submit', function (e) {
    if (baris.length === 0) {
      e.preventDefault();
      alert('Tambahkan minimal satu peserta yang diusulkan.');
      return false;
    }
    if (baris.some(b => !b.valid)) {
      e.preventDefault();
      alert('Ada peserta yang melebihi sisa formasi. Hapus atau perbaiki dulu sebelum menyimpan.');
      return false;
    }
  });

  // Inisialisasi
  renderTabel();
  const initialUnitKerjaId = $('#unitKerjaId').val();
  if (initialUnitKerjaId) {
    muatPesertaLulus(initialUnitKerjaId);
  }
});
</script>
@endpush
