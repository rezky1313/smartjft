@extends('layouts.users.master')
@section('title','Import Pegawai')

@section('isi')
<div class="container-fluid">
  <h4 class="mb-3">Import Pegawai (SDM)</h4>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold">Periksa file Anda:</div>
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <p class="mb-2">Format header (CSV/XLSX):</p>
      {{-- <pre class="bg-light p-3 rounded small">
nip,nik,nama,jenis_kelamin,pendidikan_terakhir,pangkat_golongan,status_kepegawaian,aktif,unit_name,tahun,nama_formasi,level,tmt_pengangkatan
1987xxxxxxxxxxxx,3301xxxxxxxxxxxx,"Budi Santoso",L,S1,"III/b","PNS",1,"Direktorat Sarana Perkeretaapian",2025,"Inspektur Sarana Perkeretaapian","Ahli Muda",2024-07-01
      </pre> --}}
      <ul class="small mb-3">
        <li><b>jenis_kelamin</b>: L/P, <b>status_kepegawaian</b>: PNS/PPPK/CPNS/Non PNS.</li>
        <li><b>unit_name + tahun + nama_formasi + level</b> dipakai untuk menghubungkan ke formasi.</li>
        <li>Jika NIP ada, data akan di-<i>update</i>; jika kosong → dibuat baris baru.</li>
      </ul>

      <form method="post" action="{{ route('user.sdm.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-md-8">
            <label class="form-label">File (.xlsx, .xls, .csv)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Default Aktif</label>
            <select name="default_aktif" class="form-select">
              <option value="1" selected>Aktif</option>
              <option value="0">Tidak Aktif</option>
            </select>
          </div>
        </div>
        <div class="mt-3">
          <button class="btn btn-primary">Import</button>
          <a href="{{ route('user.sdm.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
