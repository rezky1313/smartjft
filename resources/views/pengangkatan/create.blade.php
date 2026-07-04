@extends('layouts.users.master')

@section('title', isset($permohonan) ? 'Edit Permohonan Pengangkatan' : 'Buat Permohonan Pengangkatan')

@section('isi')
<div class="row">
  <div class="col-md-8 offset-md-2">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">
          <i class="fas fa-file-alt mr-2"></i>
          {{ isset($permohonan) ? 'Edit Permohonan Pengangkatan' : 'Buat Permohonan Pengangkatan' }}
        </h3>
      </div>

      <form action="{{ isset($permohonan) ? route('pengangkatan.update', $permohonan->id) : route('pengangkatan.store') }}"
            method="POST" enctype="multipart/form-data">
        @csrf
        @if (isset($permohonan)) @method('PUT') @endif

        <div class="card-body">
          {{-- Info --}}
          <div class="alert alert-info py-2">
            <i class="fas fa-info-circle mr-1"></i>
            Sistem akan otomatis menentukan kandidat berdasarkan hasil uji kompetensi dan ketersediaan formasi
            saat admin Pusbin memproses permohonan ini.
          </div>

          {{-- Unit Kerja --}}
          <div class="form-group">
            <label>Unit Kerja <span class="text-danger">*</span></label>
            @if (isset($unitKerja) && $unitKerja)
              {{-- Admin unit: auto-fill --}}
              <input type="hidden" name="unit_kerja_id" value="{{ $unitKerja->no_rs }}">
              <input type="text" class="form-control" value="{{ $unitKerja->nama_rumahsakit }}" readonly>
            @else
              <select name="unit_kerja_id" class="form-control select2 @error('unit_kerja_id') is-invalid @enderror" required>
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitKerjaList as $uk)
                  <option value="{{ $uk->no_rs }}" @selected(old('unit_kerja_id', $permohonan->unit_kerja_id ?? '') == $uk->no_rs)>
                    {{ $uk->nama_rumahsakit }}
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
        </div>

        <div class="card-footer d-flex justify-content-between">
          <a href="{{ route('pengangkatan.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
          </a>
          <div>
            <button type="submit" name="aksi" value="draft" class="btn btn-secondary">
              <i class="fas fa-save mr-1"></i>Simpan Draft
            </button>
            <button type="submit" name="aksi" value="ajukan" class="btn btn-primary">
              <i class="fas fa-paper-plane mr-1"></i>Simpan & Ajukan
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
$(function(){
  if ($.fn.select2) {
    $('.select2').select2({ width: '100%', placeholder: 'Pilih…', allowClear: true });
  }
});
</script>
@endpush
