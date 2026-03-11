@extends('layouts.users.master')
@section('title', 'Input Hasil Uji Kompetensi')
@section('isi')

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Input Hasil Uji Kompetensi</h4>
    <a href="{{ route('ujikom.show', $permohonan->id) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="alert alert-info">
        <strong>Nomor Permohonan:</strong> {{ $permohonan->nomor_permohonan }}<br>
        <strong>Unit Kerja:</strong> {{ $permohonan->unitKerja->nama_rumahsakit }}<br>
        <strong>Jadwal:</strong> {{ $permohonan->tanggal_jadwal?->format('d/m/Y') }} @ {{ $permohonan->tempat_ujikom }}
      </div>

      <form method="POST" action="{{ route('ujikom.simpan-hasil', $permohonan->id) }}">
        @csrf

        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th width="50">No</th>
                <th>Nama Pegawai</th>
                <th>NIP</th>
                <th>Jabatan</th>
                <th>Jenjang</th>
                <th width="150">Hasil <span class="text-danger">*</span></th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @foreach($permohonan->peserta as $i => $peserta)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $peserta->pegawai->nama_lengkap }}</td>
                  <td>{{ $peserta->pegawai->nip ?? '-' }}</td>
                  <td>{{ $peserta->pegawai->formasi->nama_formasi ?? '-' }}</td>
                  <td>{{ $peserta->pegawai->formasi->jenjang->nama_jenjang ?? '-' }}</td>
                  <td>
                    <select name="hasil[{{ $peserta->id }}]" class="form-control" required>
                      <option value="">-- Pilih Hasil --</option>
                      <option value="lulus">Lulus</option>
                      <option value="tidak_lulus">Tidak Lulus</option>
                    </select>
                    @error('hasil.' . $peserta->id)
                      <div class="text-danger">{{ $message }}</div>
                    @enderror
                  </td>
                  <td>
                    <input type="text" name="catatan[{{ $peserta->id }}]" class="form-control"
                           placeholder="Alasan..." value="{{ $peserta->catatan_hasil ?? '' }}">
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="alert alert-warning mt-3">
          <i class="fas fa-info-circle"></i>
          Setelah hasil disimpan, status permohonan akan berubah menjadi "Hasil Diinput". Setelah itu, Anda dapat generate Berita Acara Hasil untuk menyelesaikan permohonan.
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Simpan Hasil
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(function() {
  // Auto-fill semua hasil sebagai "Lulus"
  $('#fillAllLulus').click(function() {
    $('select[name^="hasil"]').val('lulus');
  });

  // Auto-fill semua hasil sebagai "Tidak Lulus"
  $('#fillAllTidakLulus').click(function() {
    $('select[name^="hasil"]').val('tidak_lulus');
  });
});
</script>
@endpush
@endsection
