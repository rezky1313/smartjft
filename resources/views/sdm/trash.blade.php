@extends('layouts.users.master')
@section('title','SDM Terhapus')
@section('isi')

<div class="preview-card">
  <div class="preview-header d-flex justify-content-between align-items-center">
    <div>
      <span class="preview-header-title">Sampah - Pegawai JFT</span>
      <span class="preview-header-subtitle d-block">Data yang telah dihapus, dapat dipulihkan</span>
    </div>
    <a href="{{ route('user.sdm.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Kembali ke Daftar
    </a>
  </div>
  <div class="preview-body p-0">
    <div class="table-responsive">
      <table id="trashTable" class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>No</th><th>NIP</th><th>Nama</th><th>Unit Kerja</th><th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sdm as $i => $row)
            @php $uk = $row->formasi?->unitKerja ?? $row->unitKerja; @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $row->nip }}</td>
              <td>{{ $row->nama_lengkap }}</td>
              <td>{{ $uk->nama_unit_kerja ?? '-' }}</td>
              <td>
                <div class="d-flex justify-content-center">
                  <form action="{{ route('user.sdm.restore',$row->id) }}" method="POST" class="d-inline mr-1">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-success" title="Pulihkan"><i class="fas fa-trash-restore"></i></button>
                  </form>
                  <form action="{{ route('user.sdm.force-delete',$row->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus permanen? Tindakan ini tidak bisa dibatalkan!')">
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
<script> $(function(){ $('#trashTable').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
