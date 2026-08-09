@extends('layouts.users.master')
@section('title', ($mode === 'edit' ? 'Edit' : 'Tambah') . ' Data Diklat')
@section('isi')

<div class="preview-card">
  <div class="preview-header">
    <span class="preview-header-title">{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Data Diklat</span>
    <span class="preview-header-subtitle d-block">Lengkapi data di bawah ini — berkas sertifikat wajib diunggah</span>
  </div>
  <div class="preview-body">

    @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Whoops!</strong> Ada masalah dengan inputanmu.<br><br>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form action="{{ $mode === 'edit' ? route('karir.diklat.update', $item->id) : route('karir.diklat.store') }}"
          method="POST" enctype="multipart/form-data">
      @csrf
      @if($mode === 'edit') @method('PUT') @endif

      <span class="subsection-label">Pegawai</span>
      <div class="form-row">
        <div class="form-group col-md-8">
          <label class="font-weight-bold">Nama Pegawai <span class="text-danger">*</span></label>
          @if($mode === 'edit' || $sdmTerpilih)
            <input type="text" class="form-control" value="{{ $sdmTerpilih->nama_lengkap }} — {{ $sdmTerpilih->nip ?? '-' }}" readonly>
            <input type="hidden" name="sdm_id" value="{{ $sdmTerpilih->id }}">
          @else
            <select name="sdm_id" id="pegawaiSelect" class="form-control" required style="width:100%;">
            </select>
          @endif
          @error('sdm_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
      </div>

      <hr class="my-4">

      <span class="subsection-label">Data Diklat</span>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Nama Diklat <span class="text-danger">*</span></label>
          <input type="text" name="nama_diklat" value="{{ old('nama_diklat', $item->nama_diklat ?? '') }}" class="form-control" required>
          @error('nama_diklat') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Penyelenggara <span class="text-danger">*</span></label>
          <input type="text" name="penyelenggara" value="{{ old('penyelenggara', $item->penyelenggara ?? '') }}" class="form-control" required>
          @error('penyelenggara') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Tanggal Mulai <span class="text-danger">*</span></label>
          <input type="date" id="tanggalMulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', optional($item->tanggal_mulai ?? null)->format('Y-m-d')) }}" class="form-control" required>
          @error('tanggal_mulai') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Tanggal Selesai <span class="text-danger">*</span></label>
          <input type="date" id="tanggalSelesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', optional($item->tanggal_selesai ?? null)->format('Y-m-d')) }}" class="form-control" required>
          <small id="pesanTanggal" class="text-danger" style="display:none;">Tanggal selesai tidak boleh sebelum tanggal mulai.</small>
          @error('tanggal_selesai') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Jenis Diklat <span class="text-danger">*</span></label>
          <select name="jenis_diklat" class="form-control" required>
            <option value="">-- Pilih --</option>
            @foreach($daftarJenis as $kode => $label)
              <option value="{{ $kode }}" @selected(old('jenis_diklat', $item->jenis_diklat ?? '') === $kode)>{{ $label }}</option>
            @endforeach
          </select>
          @error('jenis_diklat') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <div class="form-group col-md-3">
          <label class="font-weight-bold">Berkas Sertifikat {{ $mode === 'edit' ? '' : '<span class="text-danger">*</span>' }}</label>
          <input type="file" name="sertifikat" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" {{ $mode === 'edit' ? '' : 'required' }}>
          <small class="text-muted d-block">PDF/JPG/PNG, maks 5MB.</small>
          @if($mode === 'edit' && $item->path_sertifikat)
            <small class="d-block mt-1"><a href="{{ asset('storage/' . $item->path_sertifikat) }}" target="_blank">Lihat berkas saat ini</a> (kosongkan kalau tidak ingin mengganti)</small>
          @endif
          @error('sertifikat') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <a href="{{ $mode === 'edit' ? route('karir.diklat.riwayat', $item->sdm_id) : route('karir.diklat.index') }}" class="btn btn-outline-secondary mr-2">Batal</a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> {{ $mode === 'edit' ? 'Update' : 'Simpan' }}
        </button>
      </div>
    </form>

  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const pegawaiSelect = document.getElementById('pegawaiSelect');
  if (pegawaiSelect) {
    $('#pegawaiSelect').select2({
      width: '100%',
      placeholder: '-- Cari Nama/NIP Pegawai --',
      allowClear: true,
      ajax: {
        url: '{{ route("karir.diklat.pegawai-options") }}',
        dataType: 'json',
        delay: 250,
        data: params => ({ q: params.term }),
        processResults: data => ({ results: data.results }),
      },
      minimumInputLength: 2,
    });
  }

  const mulai = document.getElementById('tanggalMulai');
  const selesai = document.getElementById('tanggalSelesai');
  const pesan = document.getElementById('pesanTanggal');
  const form = mulai.closest('form');

  function cekTanggal() {
    if (mulai.value && selesai.value && selesai.value < mulai.value) {
      pesan.style.display = 'block';
      selesai.setCustomValidity('Tanggal selesai tidak boleh sebelum tanggal mulai.');
    } else {
      pesan.style.display = 'none';
      selesai.setCustomValidity('');
    }
  }
  mulai.addEventListener('change', cekTanggal);
  selesai.addEventListener('change', cekTanggal);
});
</script>
@endpush
