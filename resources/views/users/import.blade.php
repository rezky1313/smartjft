@extends('layouts.users.master')

@section('title', 'Import Unit Kerja — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-lg-8">

    {{-- Header --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-file-excel mr-2"></i>Import Unit Kerja dari Excel</h3>
        <a href="{{ route('user.unitkerja.index') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    {{-- Alert --}}
    @if (session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</div>
    @endif
    @if (session('warning'))
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i>{{ session('warning') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger"><i class="fas fa-times-circle mr-1"></i>{{ session('error') }}</div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Download Template --}}
    <div class="card border-primary">
      <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div>
          <h5 class="mb-1"><i class="fas fa-download mr-2 text-primary"></i>Download Template Excel</h5>
          <p class="text-muted mb-0 small">Template sudah berisi petunjuk pengisian dan contoh data unit kerja.</p>
        </div>
        <a href="{{ route('user.unitkerja.template') }}" class="btn btn-primary mt-2">
          <i class="fas fa-file-excel mr-1"></i> Download Template
        </a>
      </div>
    </div>

    {{-- Petunjuk --}}
    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Petunjuk Import</h3>
      </div>
      <div class="card-body">
        <ul class="mb-0 pl-3">
          <li>Format file yang diterima: <strong>.xlsx</strong> atau <strong>.xls</strong></li>
          <li>Gunakan template yang disediakan — <strong>jangan ubah nama kolom header</strong></li>
          <li>Isi data di sheet <strong>"Data Unit Kerja"</strong>, bukan sheet Petunjuk</li>
          <li><code>nama_unit_kerja</code>, <code>kab_kota</code>, <code>matra</code>, dan <code>instansi</code> wajib diisi</li>
          <li><code>kab_kota</code> harus persis sama dengan master data wilayah di sistem</li>
          <li>Baris dengan error akan <strong>diskip</strong> — baris yang valid tetap diimport</li>
        </ul>
      </div>
    </div>

    {{-- Form Upload --}}
    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title mb-0"><i class="fas fa-upload mr-2"></i>Upload File Excel</h3>
      </div>
      <div class="card-body">
        <form action="{{ route('user.unitkerja.import.store') }}" method="POST" enctype="multipart/form-data" id="formImport">
          @csrf
          <div class="form-group">
            <label class="font-weight-bold">Pilih File Excel <span class="text-danger">*</span></label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="fileExcel" name="file"
                     accept=".xlsx,.xls" onchange="updateFileName(this)">
              <label class="custom-file-label" for="fileExcel" id="fileLabel">Pilih file .xlsx / .xls...</label>
            </div>
            <small class="text-muted">Maksimal ukuran file: 10 MB</small>
          </div>
          <div class="d-flex align-items-center">
            <button type="submit" class="btn btn-success" id="btnImport" disabled>
              <i class="fas fa-file-import mr-1"></i> Import Sekarang
            </button>
            <span class="ml-3 text-muted small" id="loadingText" style="display:none;">
              <i class="fas fa-spinner fa-spin mr-1"></i>Sedang mengimport, harap tunggu...
            </span>
          </div>
        </form>
      </div>
    </div>

  </div>

  {{-- Sidebar Info --}}
  <div class="col-lg-4">
    <div class="card bg-light">
      <div class="card-header"><h3 class="card-title mb-0">Nilai Valid per Kolom</h3></div>
      <div class="card-body" style="font-size:0.82rem;">
        <p class="mb-1"><strong>matra</strong> <small>(wajib):</small></p>
        <p class="text-muted mb-2">Darat &nbsp;|&nbsp; Laut &nbsp;|&nbsp; Udara &nbsp;|&nbsp; Kereta</p>
        <p class="mb-1"><strong>instansi</strong> <small>(wajib):</small></p>
        <p class="text-muted mb-2">Pusat &nbsp;|&nbsp; Daerah</p>
        <p class="mb-1"><strong>kab_kota</strong> <small>(wajib):</small></p>
        <p class="text-muted mb-0">Harus sama persis dengan master data wilayah. Boleh diawali "Kota "/"Kabupaten " jika nama kembar antar tipe.</p>
      </div>
    </div>
  </div>
</div>

{{-- Detail Error Import Terakhir --}}
@if (session('import_errors') && count(session('import_errors')) > 0)
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-danger text-white">
        <h3 class="card-title mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Detail Baris Gagal — Import Terakhir</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="70" class="text-center">Baris</th>
                <th width="260">Nama Unit Kerja</th>
                <th>Pesan Error</th>
              </tr>
            </thead>
            <tbody>
              @foreach (session('import_errors') as $item)
              <tr>
                <td class="text-center font-weight-bold">{{ $item['baris'] }}</td>
                <td>{{ $item['nama'] }}</td>
                <td class="text-danger">{{ implode('; ', $item['errors']) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function updateFileName(input) {
  const label = document.getElementById('fileLabel');
  const btn   = document.getElementById('btnImport');
  if (input.files && input.files[0]) {
    label.textContent = input.files[0].name;
    btn.disabled = false;
  } else {
    label.textContent = 'Pilih file .xlsx / .xls...';
    btn.disabled = true;
  }
}

document.getElementById('formImport').addEventListener('submit', function() {
  document.getElementById('btnImport').disabled = true;
  document.getElementById('loadingText').style.display = 'inline';
});
</script>
@endpush
