@extends('layouts.users.master')
@section('title','Formasi Terhapus')
@section('isi')

<div class="preview-card">
  <div class="preview-header d-flex justify-content-between align-items-center">
    <div>
      <span class="preview-header-title">Sampah - Formasi</span>
      <span class="preview-header-subtitle d-block">Data yang telah dihapus, dapat dipulihkan</span>
    </div>
    <a href="{{ route('user.formasi.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Kembali ke Daftar
    </a>
  </div>
  <div class="preview-body p-0">
    <div class="table-responsive">
      <table id="trashFormasi" class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>No</th><th>Nama Formasi</th><th>Jenjang</th><th>Unit Kerja</th><th>Tahun</th><th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($formasi as $i => $f)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $f->nama_formasi }}</td>
              <td>{{ $f->jenjang->nama_jenjang ?? '-' }}</td>
              <td>{{ $f->unitKerja->nama_unit_kerja ?? '-' }}</td>
              <td>{{ $f->tahun_formasi }}</td>
              <td>
                <div class="d-flex justify-content-center">
                  <form action="{{ route('user.formasi.restore',$f->id) }}" method="POST" class="d-inline mr-1">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-success" title="Pulihkan"><i class="fas fa-trash-restore"></i></button>
                  </form>
                  <form action="{{ route('user.formasi.force-delete',$f->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus permanen? Tidak bisa dibatalkan!')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Hapus Permanen"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script> $(function(){ $('#trashFormasi').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
