@extends('layouts.users.master')
@section('title', 'Riwayat Diklat - ' . $sdm->nama_lengkap)
@section('isi')

<div class="preview-card mb-4">
  <div class="preview-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <span class="preview-header-title">Riwayat Diklat</span>
      <span class="preview-header-subtitle d-block">{{ $sdm->nama_lengkap }} — NIP {{ $sdm->nip ?? '-' }} — {{ $sdm->unitKerja->nama_unit_kerja ?? '-' }}</span>
    </div>
    @can('create pegawai')
    <a href="{{ route('karir.diklat.create', ['sdm_id' => $sdm->id]) }}" class="btn btn-primary btn-sm">+ Tambah Diklat</a>
    @endcan
  </div>
  <div class="preview-body">

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>Nama Diklat</th>
            <th>Penyelenggara</th>
            <th>Jenis</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Selesai</th>
            <th>Sertifikat</th>
            <th>Diinput Oleh</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @php
            $labelJenis = ['teknis' => 'Teknis', 'fungsional' => 'Fungsional', 'kepemimpinan' => 'Kepemimpinan', 'lainnya' => 'Lainnya'];
          @endphp
          @forelse($riwayat as $r)
            <tr>
              <td>{{ $r->nama_diklat }}</td>
              <td>{{ $r->penyelenggara }}</td>
              <td>{{ $labelJenis[$r->jenis_diklat] ?? $r->jenis_diklat }}</td>
              <td>{{ $r->tanggal_mulai->format('d-m-Y') }}</td>
              <td>{{ $r->tanggal_selesai->format('d-m-Y') }}</td>
              <td>
                <a href="{{ asset('storage/' . $r->path_sertifikat) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                  <i class="fas fa-file"></i> Lihat
                </a>
              </td>
              <td>{{ $r->inputBy->name ?? '-' }}</td>
              <td class="text-center">
                @can('edit pegawai')
                <a href="{{ route('karir.diklat.edit', $r->id) }}" class="btn btn-sm btn-outline-warning mr-1" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                @endcan
                @can('delete pegawai')
                <form action="{{ route('karir.diklat.destroy', $r->id) }}" method="POST" class="d-inline formHapusDiklat">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="konfirmasiHapusDiklat(this)">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
                @endcan
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted">Belum ada riwayat diklat untuk pegawai ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      <a href="{{ route('karir.diklat.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Kembali ke Daftar</a>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
function konfirmasiHapusDiklat(btn) {
  Swal.fire({
    title: 'Hapus Data Diklat?',
    text: 'Data dan berkas sertifikat yang sudah diunggah akan dihapus permanen.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc3545',
  }).then((result) => {
    if (result.isConfirmed) {
      btn.closest('form').submit();
    }
  });
}
</script>
@endpush
