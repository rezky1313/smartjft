@extends('layouts.users.master')

@section('title', 'Edit Jadwal Uji Kompetensi — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">
          <i class="fas fa-pencil-alt mr-2"></i>Edit Jadwal Uji Kompetensi
        </h3>
      </div>
      <form action="{{ route('ujikom.jadwal.update', $jadwal->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
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

          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Mengedit jadwal akan <strong>menghapus dan membuat ulang</strong> semua persyaratan, termasuk file yang sudah diunggah sebelumnya.
          </div>

          {{-- INFO JADWAL --}}
          <h5 class="font-weight-bold border-bottom pb-2 mb-3">Informasi Jadwal</h5>
          <div class="form-group">
            <label>Judul <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror"
                   value="{{ old('judul', $jadwal->judul) }}" required>
            @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Jenis Ujian</label>
                <select name="jenis_ujian" class="form-control @error('jenis_ujian') is-invalid @enderror">
                  <option value="">-- Pilih Jenis Ujian --</option>
                  <option value="kenaikan_jabatan"      {{ old('jenis_ujian', $jadwal->jenis_ujian) === 'kenaikan_jabatan'      ? 'selected' : '' }}>Kenaikan Jabatan</option>
                  <option value="perpindahan_jabatan"   {{ old('jenis_ujian', $jadwal->jenis_ujian) === 'perpindahan_jabatan'   ? 'selected' : '' }}>Perpindahan Jabatan</option>
                  <option value="penyesuaian_inpassing" {{ old('jenis_ujian', $jadwal->jenis_ujian) === 'penyesuaian_inpassing' ? 'selected' : '' }}>Penyesuaian (Inpassing)</option>
                </select>
                @error('jenis_ujian')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Matra</label>
                <select name="matra" class="form-control @error('matra') is-invalid @enderror">
                  <option value="">-- Pilih Matra --</option>
                  <option value="Darat"  {{ old('matra', $jadwal->matra) === 'Darat'  ? 'selected' : '' }}>Darat</option>
                  <option value="Laut"   {{ old('matra', $jadwal->matra) === 'Laut'   ? 'selected' : '' }}>Laut</option>
                  <option value="Udara"  {{ old('matra', $jadwal->matra) === 'Udara'  ? 'selected' : '' }}>Udara</option>
                  <option value="ASDP"   {{ old('matra', $jadwal->matra) === 'ASDP'   ? 'selected' : '' }}>ASDP</option>
                  <option value="Semua"  {{ old('matra', $jadwal->matra) === 'Semua'  ? 'selected' : '' }}>Semua Matra</option>
                </select>
                @error('matra')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror"
                      rows="4">{{ old('deskripsi', $jadwal->deskripsi) }}</textarea>
            @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                       class="form-control @error('tanggal_mulai') is-invalid @enderror"
                       value="{{ old('tanggal_mulai', $jadwal->tanggal_mulai->format('Y-m-d')) }}" required>
                @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                       class="form-control @error('tanggal_selesai') is-invalid @enderror"
                       value="{{ old('tanggal_selesai', $jadwal->tanggal_selesai->format('Y-m-d')) }}" required>
                @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Kuota <span class="text-danger">*</span></label>
                <input type="number" name="kuota" class="form-control @error('kuota') is-invalid @enderror"
                       value="{{ old('kuota', $jadwal->kuota) }}" min="1" required>
                @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Tempat Pelaksanaan <span class="text-danger">*</span></label>
            <input type="text" name="tempat" class="form-control @error('tempat') is-invalid @enderror"
                   value="{{ old('tempat', $jadwal->tempat) }}" required>
            @error('tempat')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- PERSYARATAN --}}
          <h5 class="font-weight-bold border-bottom pb-2 mb-3 mt-4">Persyaratan Peserta</h5>

          @if ($jadwal->persyaratan->isNotEmpty())
          <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            Persyaratan saat ini (akan diganti setelah disimpan):
            <ul class="mb-0 mt-1">
              @foreach ($jadwal->persyaratan as $p)
              <li>{{ $p->nama_syarat }}</li>
              @endforeach
            </ul>
          </div>
          @endif

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
              @if ($jadwal->persyaratan->isNotEmpty())
                @foreach ($jadwal->persyaratan as $idx => $p)
                <tr class="baris-persyaratan">
                  <td class="nomor-baris text-center">{{ $idx + 1 }}</td>
                  <td><input type="text" name="persyaratan[{{ $idx }}][nama_syarat]" class="form-control form-control-sm" value="{{ $p->nama_syarat }}"></td>
                  <td><input type="text" name="persyaratan[{{ $idx }}][keterangan]" class="form-control form-control-sm" value="{{ $p->keterangan }}"></td>
                  <td><input type="number" name="persyaratan[{{ $idx }}][urutan]" class="form-control form-control-sm" value="{{ $p->urutan }}" min="1"></td>
                  <td><input type="file" name="persyaratan[{{ $idx }}][file_contoh]" class="form-control-file" style="font-size:0.8rem;"></td>
                  <td class="text-center"><button type="button" class="btn btn-danger btn-xs hapus-baris"><i class="fas fa-times"></i></button></td>
                </tr>
                @endforeach
              @else
                <tr class="baris-persyaratan">
                  <td class="nomor-baris text-center">1</td>
                  <td><input type="text" name="persyaratan[0][nama_syarat]" class="form-control form-control-sm" placeholder="Nama syarat..."></td>
                  <td><input type="text" name="persyaratan[0][keterangan]" class="form-control form-control-sm" placeholder="Keterangan (opsional)"></td>
                  <td><input type="number" name="persyaratan[0][urutan]" class="form-control form-control-sm" value="1" min="1"></td>
                  <td><input type="file" name="persyaratan[0][file_contoh]" class="form-control-file" style="font-size:0.8rem;"></td>
                  <td class="text-center"><button type="button" class="btn btn-danger btn-xs hapus-baris"><i class="fas fa-times"></i></button></td>
                </tr>
              @endif
            </tbody>
          </table>

          <button type="button" class="btn btn-default btn-sm" id="tambahBaris">
            <i class="fas fa-plus mr-1"></i> Tambah Persyaratan
          </button>

        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('ujikom.jadwal.show', $jadwal->id) }}" class="btn btn-default">
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
let barisIndex = {{ $jadwal->persyaratan->count() ?: 1 }};

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
  if ($('.baris-persyaratan').length > 1) {
    $(this).closest('tr').remove();
    updateNomor();
  }
});

function updateNomor() {
  $('.baris-persyaratan').each(function (i) {
    $(this).find('.nomor-baris').text(i + 1);
  });
}

$('#tanggal_mulai').on('change', function () {
  $('#tanggal_selesai').attr('min', $(this).val());
});
</script>
@endpush
