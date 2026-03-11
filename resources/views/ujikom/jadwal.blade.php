@extends('layouts.users.master')
@section('title', 'Input Jadwal Uji Kompetensi')
@section('isi')

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Input Jadwal Uji Kompetensi</h4>
    <a href="{{ route('ujikom.show', $permohonan->id) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="alert alert-info">
        <strong>Nomor Permohonan:</strong> {{ $permohonan->nomor_permohonan }}<br>
        <strong>Unit Kerja:</strong> {{ $permohonan->unitKerja->nama_rumahsakit }}
      </div>

      <form method="POST" action="{{ route('ujikom.simpan-jadwal', $permohonan->id) }}">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Tanggal Jadwal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_jadwal" class="form-control" required>
            <small class="text-muted">Tanggal harus setelah hari ini</small>
            @error('tanggal_jadwal')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Tempat Pelaksanaan <span class="text-danger">*</span></label>
            <input type="text" name="tempat_ujikom" class="form-control" placeholder="Contoh: Aula Serbaguna, Kantor KSOP..." required>
            @error('tempat_ujikom')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-12">
            <div class="alert alert-warning">
              <i class="fas fa-info-circle"></i>
              Setelah jadwal disimpan, Berita Acara Verifikasi akan otomatis dibuat dan status permohonan akan berubah menjadi "Terjadwal".
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between">
              <a href="{{ route('ujikom.show', $permohonan->id) }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Jadwal
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
