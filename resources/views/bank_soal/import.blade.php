@extends('layouts.users.master')

@section('title', 'Import Bank Soal — Pusbin JFT')

@section('isi')
<div class="row">
  <div class="col-lg-8">

    {{-- Header --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0"><i class="fas fa-file-excel mr-2"></i>Import Soal dari Excel</h3>
        <a href="{{ route('bank-soal.index') }}" class="btn btn-default btn-sm">
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
          <p class="text-muted mb-0 small">Template sudah berisi petunjuk pengisian, daftar kategori, dan contoh data soal.</p>
        </div>
        <a href="{{ route('bank-soal.template') }}" class="btn btn-primary mt-2">
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
          <li>Maksimal <strong>500 soal</strong> per sekali import</li>
          <li>Gunakan template yang disediakan — <strong>jangan ubah nama kolom header</strong></li>
          <li>Isi data di sheet <strong>"Data Soal"</strong>, bukan sheet Petunjuk</li>
          <li>Lihat sheet <strong>"Daftar Kategori"</strong> untuk mendapatkan <code>soal_kategori_id</code> yang valid</li>
          <li><code>soal_kategori_id</code> wajib diisi jika <code>jenis = spesifik</code>, boleh kosong jika <code>jenis = umum</code></li>
          <li>Baris dengan error akan <strong>diskip</strong> — baris yang valid tetap diimport</li>
          <li>Nilai <code>status</code>: <code>draft</code> atau <code>aktif</code></li>
        </ul>
      </div>
    </div>

    {{-- Form Upload --}}
    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title mb-0"><i class="fas fa-upload mr-2"></i>Upload File Excel</h3>
      </div>
      <div class="card-body">
        <form action="{{ route('bank-soal.import.store') }}" method="POST" enctype="multipart/form-data" id="formImport">
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
        <p class="mb-1"><strong>jenis:</strong></p>
        <p class="text-muted mb-2">umum &nbsp;|&nbsp; spesifik</p>
        <p class="mb-1"><strong>jawaban_benar:</strong></p>
        <p class="text-muted mb-2">A &nbsp;|&nbsp; B &nbsp;|&nbsp; C &nbsp;|&nbsp; D</p>
        <p class="mb-1"><strong>tingkat_kesulitan:</strong></p>
        <p class="text-muted mb-2">mudah &nbsp;|&nbsp; sedang &nbsp;|&nbsp; sulit</p>
        <p class="mb-1"><strong>taksonomi_bloom:</strong></p>
        <ul class="text-muted mb-2 pl-3">
          <li>C1_mengingat</li>
          <li>C2_memahami</li>
          <li>C3_menerapkan</li>
          <li>C4_menganalisis</li>
          <li>C5_mengevaluasi</li>
          <li>C6_mencipta</li>
        </ul>
        <p class="mb-1"><strong>status:</strong></p>
        <p class="text-muted mb-0">draft &nbsp;|&nbsp; aktif</p>
      </div>
    </div>
  </div>
</div>

{{-- Riwayat Import --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Riwayat Import</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.85rem;">
            <thead class="thead-light">
              <tr>
                <th width="40" class="text-center">No</th>
                <th>Nama File</th>
                <th width="90" class="text-center">Total Baris</th>
                <th width="90" class="text-center">Berhasil</th>
                <th width="80" class="text-center">Gagal</th>
                <th>Diimport Oleh</th>
                <th width="140">Tanggal</th>
                <th width="100" class="text-center">Detail Error</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($logs as $i => $log)
              <tr>
                <td class="text-center">{{ $logs->firstItem() + $i }}</td>
                <td>
                  <i class="fas fa-file-excel text-success mr-1"></i>
                  <small>{{ $log->nama_file }}</small>
                </td>
                <td class="text-center"><span class="badge badge-secondary">{{ $log->total_baris }}</span></td>
                <td class="text-center"><span class="badge badge-success">{{ $log->berhasil }}</span></td>
                <td class="text-center">
                  @if ($log->gagal > 0)
                  <span class="badge badge-danger">{{ $log->gagal }}</span>
                  @else
                  <span class="badge badge-light border">0</span>
                  @endif
                </td>
                <td><small>{{ $log->pengimport?->name ?? '-' }}</small></td>
                <td><small>{{ $log->created_at->format('d/m/Y H:i') }}</small></td>
                <td class="text-center">
                  @if ($log->gagal > 0)
                  <button class="btn btn-xs btn-outline-danger"
                          onclick="lihatError({{ $log->id }}, '{{ addslashes($log->nama_file) }}')"
                          title="Lihat Detail Error">
                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ $log->gagal }} error
                  </button>
                  @else
                  <span class="text-muted small">—</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Belum ada riwayat import.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if ($logs->hasPages())
      <div class="card-footer">{{ $logs->links() }}</div>
      @endif
    </div>
  </div>
</div>

{{-- Modal Detail Error --}}
<div class="modal fade" id="modalError" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Detail Error Import — <span id="modalErrorFile"></span></h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0" style="font-size:0.82rem;">
            <thead class="thead-light">
              <tr>
                <th width="70" class="text-center">Baris</th>
                <th width="200">Pertanyaan (preview)</th>
                <th>Pesan Error</th>
              </tr>
            </thead>
            <tbody id="modalErrorBody">
              <tr><td colspan="3" class="text-center text-muted">Memuat...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
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

function lihatError(logId, namaFile) {
  document.getElementById('modalErrorFile').textContent = namaFile;
  document.getElementById('modalErrorBody').innerHTML = '<tr><td colspan="3" class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat...</td></tr>';
  $('#modalError').modal('show');

  fetch('/bank-soal/' + logId + '/import-error')
    .then(res => res.json())
    .then(data => {
      const detail = data.detail_gagal || [];
      if (detail.length === 0) {
        document.getElementById('modalErrorBody').innerHTML = '<tr><td colspan="3" class="text-center text-muted">Tidak ada detail error.</td></tr>';
        return;
      }
      let html = '';
      detail.forEach(function(item) {
        const errors = Array.isArray(item.errors) ? item.errors.join('<br>') : item.errors;
        html += `<tr>
          <td class="text-center font-weight-bold">${item.baris}</td>
          <td><small class="text-muted">${item.pertanyaan || '-'}</small></td>
          <td><span class="text-danger">${errors}</span></td>
        </tr>`;
      });
      document.getElementById('modalErrorBody').innerHTML = html;
    })
    .catch(function() {
      document.getElementById('modalErrorBody').innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data error.</td></tr>';
    });
}
</script>
@endpush
