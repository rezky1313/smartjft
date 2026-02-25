@extends('layouts.users.master')
@section('title','Import Formasi (Pivot Lebar)')

@section('isi')
<div class="container-fluid">
  <h4 class="mb-3">Import Formasi</h4>
  {{-- <h4 class="mb-3">Import Formasi + Kuota (Format Pivot)</h4> --}}

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold">Periksa file Anda:</div>
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- <div class="card">
    <div class="card-body">
      <p class="mb-2">Struktur minimal (header bisa 1–3 baris, merged cell OK):</p>
      <ul class="small">
        <li><b>Nama Unit Kerja</b> (boleh merged/diulang tiap blok)</li>
        <li><b>Nama Jabatan</b></li>
        <li>Kolom jenjang (sebagian/semua): <code>Pemula, Terampil, Mahir, Penyelia, Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama</code></li>
      </ul> --}}

      <form method="post" action="{{ route('user.formasi.import-pivot.store') }}" enctype="multipart/form-data" class="mt-3">
        @csrf

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tahun Formasi (diterapkan ke semua baris)</label>
            <input type="text" name="tahun_formasi" class="form-control" placeholder="mis. 2025" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">File (.xlsx, .xls)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
          </div>
        </div>

        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-primary">Import</button>
          <a href="{{ route('user.formasi.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
