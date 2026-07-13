@extends('layouts.users.master')

@section('title', 'Tambah Jadwal Uji Kompetensi — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">
          <i class="fas fa-plus-circle mr-2"></i>Tambah Jadwal Uji Kompetensi
        </h3>
      </div>
      <form action="{{ route('ujikom.jadwal.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card-body">

          @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          {{-- INFO JADWAL --}}
          <h5 class="font-weight-bold border-bottom pb-2 mb-3">Informasi Jadwal</h5>
          <div class="form-group">
            <label>Judul <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror"
                   value="{{ old('judul') }}" placeholder="Contoh: Uji Kompetensi PKB Gelombang I Tahun 2026" required>
            @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Jenis Ujian</label>
                <select name="jenis_ujian" class="form-control @error('jenis_ujian') is-invalid @enderror">
                  <option value="">-- Pilih Jenis Ujian --</option>
                  <option value="kenaikan_jabatan"      {{ old('jenis_ujian') === 'kenaikan_jabatan'      ? 'selected' : '' }}>Kenaikan Jabatan</option>
                  <option value="perpindahan_jabatan"   {{ old('jenis_ujian') === 'perpindahan_jabatan'   ? 'selected' : '' }}>Perpindahan Jabatan</option>
                  <option value="penyesuaian_inpassing" {{ old('jenis_ujian') === 'penyesuaian_inpassing' ? 'selected' : '' }}>Penyesuaian (Inpassing)</option>
                </select>
                @error('jenis_ujian')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Matra</label>
                <select name="matra" class="form-control @error('matra') is-invalid @enderror">
                  <option value="">-- Pilih Matra --</option>
                  <option value="Darat"  {{ old('matra') === 'Darat'  ? 'selected' : '' }}>Darat</option>
                  <option value="Laut"   {{ old('matra') === 'Laut'   ? 'selected' : '' }}>Laut</option>
                  <option value="Udara"  {{ old('matra') === 'Udara'  ? 'selected' : '' }}>Udara</option>
                  <option value="ASDP"   {{ old('matra') === 'ASDP'   ? 'selected' : '' }}>ASDP</option>
                  <option value="Semua"  {{ old('matra') === 'Semua'  ? 'selected' : '' }}>Semua Matra</option>
                </select>
                @error('matra')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror"
                      rows="4" placeholder="Keterangan tambahan tentang pelaksanaan ujian...">{{ old('deskripsi') }}</textarea>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                       class="form-control @error('tanggal_mulai') is-invalid @enderror"
                       value="{{ old('tanggal_mulai') }}" required>
                @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                       class="form-control @error('tanggal_selesai') is-invalid @enderror"
                       value="{{ old('tanggal_selesai') }}" required>
                @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Kuota <span class="text-danger">*</span></label>
                <input type="number" name="kuota" class="form-control @error('kuota') is-invalid @enderror"
                       value="{{ old('kuota', 30) }}" min="1" required>
                @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Tempat Pelaksanaan <span class="text-danger">*</span></label>
            <input type="text" name="tempat" class="form-control @error('tempat') is-invalid @enderror"
                   value="{{ old('tempat') }}" placeholder="Contoh: Gedung Pusbin JFT, Jakarta" required>
            @error('tempat')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- KONFIGURASI ASPEK PENILAIAN --}}
          <h5 class="font-weight-bold border-bottom pb-2 mb-3 mt-4">Konfigurasi Aspek Penilaian</h5>
          <div class="card mb-3">
            <div class="card-body">
              <div class="form-group">
                <label>Jenjang Tujuan Ujian <span class="text-danger">*</span></label>
                <select name="jenjang_tujuan" class="form-control @error('jenjang_tujuan') is-invalid @enderror" required>
                  <option value="">-- Pilih Jenjang Tujuan --</option>
                  <option value="ahli_utama"   {{ old('jenjang_tujuan') === 'ahli_utama'   ? 'selected' : '' }}>Ahli Utama</option>
                  <option value="ahli_madya"   {{ old('jenjang_tujuan') === 'ahli_madya'   ? 'selected' : '' }}>Ahli Madya</option>
                  <option value="ahli_muda"    {{ old('jenjang_tujuan') === 'ahli_muda'    ? 'selected' : '' }}>Ahli Muda</option>
                  <option value="ahli_pertama" {{ old('jenjang_tujuan') === 'ahli_pertama' ? 'selected' : '' }}>Ahli Pertama</option>
                  <option value="penyelia"     {{ old('jenjang_tujuan') === 'penyelia'     ? 'selected' : '' }}>Penyelia</option>
                  <option value="mahir"        {{ old('jenjang_tujuan') === 'mahir'        ? 'selected' : '' }}>Mahir</option>
                  <option value="terampil"     {{ old('jenjang_tujuan') === 'terampil'     ? 'selected' : '' }}>Terampil</option>
                  <option value="pemula"       {{ old('jenjang_tujuan') === 'pemula'       ? 'selected' : '' }}>Pemula</option>
                </select>
                @error('jenjang_tujuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="row">
                <div class="col-md-6">
                  <strong>Kompetensi Teknis</strong>
                  <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" class="custom-control-input" id="teknisWawancara" name="teknis_wawancara_aktif" value="1" {{ old('teknis_wawancara_aktif') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="teknisWawancara">Aktifkan Wawancara</label>
                  </div>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="teknisPresentasi" name="teknis_presentasi_aktif" value="1" {{ old('teknis_presentasi_aktif') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="teknisPresentasi">Aktifkan Presentasi</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <strong>Kompetensi Mansoskul</strong>
                  <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" class="custom-control-input" id="mansoskulWawancara" name="mansoskul_wawancara_aktif" value="1" {{ old('mansoskul_wawancara_aktif') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="mansoskulWawancara">Aktifkan Wawancara</label>
                  </div>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="mansoskulPresentasi" name="mansoskul_presentasi_aktif" value="1" {{ old('mansoskul_presentasi_aktif') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="mansoskulPresentasi">Aktifkan Presentasi</label>
                  </div>
                </div>
              </div>
              <small class="text-muted d-block mt-2"><i class="fas fa-info-circle mr-1"></i>Tes CAT selalu aktif secara otomatis untuk kedua kompetensi.</small>
            </div>
          </div>

          {{-- PERSYARATAN --}}
          <h5 class="font-weight-bold border-bottom pb-2 mb-3 mt-4">Persyaratan Peserta</h5>
          <p class="text-muted small mb-2">
            <i class="fas fa-info-circle mr-1"></i>
            12 persyaratan standar telah diisi otomatis. Anda dapat mengedit, menghapus, atau menambah persyaratan tambahan.
          </p>

          <table class="table table-bordered table-sm" id="tabelPersyaratan">
            <thead class="thead-light">
              <tr>
                <th width="40">No</th>
                <th>Nama Syarat <span class="text-danger">*</span></th>
                <th>Keterangan</th>
                <th width="70">Urutan</th>
                <th>File Contoh</th>
                <th width="60">Aksi</th>
              </tr>
            </thead>
            <tbody id="persyaratanBody">
              @php
              $persyaratanDefault = [
                  'Surat Usulan Uji Kompetensi dari Pimpinan Unit Kerja',
                  'Surat Keterangan Integritas dan Moralitas',
                  'Surat Keterangan Sehat dari Dokter',
                  'SK PNS',
                  'SK Kenaikan Pangkat Terakhir',
                  'SK Jabatan Terakhir',
                  'Ijazah Terakhir',
                  'Dokumen Evaluasi Kinerja Minimal Predikat BAIK 2 (dua) Tahun Terakhir',
                  'PAK Kumulatif',
                  'Surat Keterangan Tidak Sedang Menjalani Tugas Belajar (lebih dari 6 bulan dan diberhentikan dari jabatan) dan Tidak Sedang Cuti Diluar Tanggungan Negara',
                  'Sertifikat Kompetensi Pelatihan yang Dipersyaratkan untuk Menduduki Jabatan',
                  'Karya Tulis Ilmiah (bagi usulan ke Jenjang Ahli Madya)',
              ];
              @endphp
              @foreach ($persyaratanDefault as $idx => $namaSyarat)
              <tr class="baris-persyaratan">
                <td class="nomor-baris text-center">{{ $idx + 1 }}</td>
                <td><input type="text" name="persyaratan[{{ $idx }}][nama_syarat]" class="form-control form-control-sm"
                           value="{{ old("persyaratan.{$idx}.nama_syarat", $namaSyarat) }}"></td>
                <td><input type="text" name="persyaratan[{{ $idx }}][keterangan]" class="form-control form-control-sm"
                           value="{{ old("persyaratan.{$idx}.keterangan") }}" placeholder="Keterangan (opsional)"></td>
                <td><input type="number" name="persyaratan[{{ $idx }}][urutan]" class="form-control form-control-sm"
                           value="{{ old("persyaratan.{$idx}.urutan", $idx + 1) }}" min="1"></td>
                <td><input type="file" name="persyaratan[{{ $idx }}][file_contoh]" class="form-control-file" style="font-size:0.8rem;"></td>
                <td class="text-center">
                  <button type="button" class="btn btn-danger btn-xs hapus-baris" title="Hapus baris ini">
                    <i class="fas fa-times"></i>
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <button type="button" class="btn btn-default btn-sm" id="tambahBaris">
            <i class="fas fa-plus mr-1"></i> Tambah Persyaratan
          </button>

        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('ujikom.jadwal.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
          </a>
          <div>
            <button type="submit" name="draft" class="btn btn-secondary mr-2">
              <i class="fas fa-save mr-1"></i> Simpan sebagai Draft
            </button>
            <button type="submit" name="publish" class="btn btn-success">
              <i class="fas fa-globe mr-1"></i> Simpan & Publikasikan
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
let barisIndex = 12;

$('#tambahBaris').on('click', function () {
  const html = `
    <tr class="baris-persyaratan">
      <td class="nomor-baris text-center">${barisIndex + 1}</td>
      <td><input type="text" name="persyaratan[${barisIndex}][nama_syarat]" class="form-control form-control-sm" placeholder="Nama syarat..."></td>
      <td><input type="text" name="persyaratan[${barisIndex}][keterangan]" class="form-control form-control-sm" placeholder="Keterangan (opsional)"></td>
      <td><input type="number" name="persyaratan[${barisIndex}][urutan]" class="form-control form-control-sm" value="${barisIndex + 1}" min="1"></td>
      <td><input type="file" name="persyaratan[${barisIndex}][file_contoh]" class="form-control-file" style="font-size:0.8rem;"></td>
      <td class="text-center"><button type="button" class="btn btn-danger btn-xs hapus-baris"><i class="fas fa-times"></i></button></td>
    </tr>`;
  $('#persyaratanBody').append(html);
  barisIndex++;
  updateNomor();
});

$(document).on('click', '.hapus-baris', function () {
  $(this).closest('tr').remove();
  updateNomor();
});

function updateNomor() {
  $('.baris-persyaratan').each(function (i) {
    $(this).find('.nomor-baris').text(i + 1);
  });
}

// Validasi tanggal selesai >= tanggal mulai
$('#tanggal_mulai').on('change', function () {
  $('#tanggal_selesai').attr('min', $(this).val());
});
</script>
@endpush
