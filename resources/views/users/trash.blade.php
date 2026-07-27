@extends('layouts.users.master')
@section('title','Unit Kerja Terhapus')
@section('isi')

<div class="preview-card">
  <div class="preview-header d-flex justify-content-between align-items-center">
    <div>
      <span class="preview-header-title">Sampah - Unit Kerja</span>
      <span class="preview-header-subtitle d-block">Data yang telah dihapus, dapat dipulihkan</span>
    </div>
    <a href="{{ route('user.unitkerja.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Kembali ke Daftar
    </a>
  </div>
  <div class="preview-body p-0">
    <div class="table-responsive">
      <table id="trashUk" class="table table-hover mb-0">
        <thead style="background:#f8fafc;">
          <tr>
            <th>No</th><th>Kode</th><th>Nama</th><th>Provinsi</th><th>Kab/Kota</th><th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($unitKerjas as $i => $unitKerja)
            @php $kab = $unitKerja->regency; $prov = $kab?->province; @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $unitKerja->id }}</td>
              <td>{{ $unitKerja->nama_unit_kerja }}</td>
              <td>{{ $prov->name ?? '-' }}</td>
              <td>{{ $kab ? ($kab->type.' '.$kab->name) : '-' }}</td>
              <td>
                <div class="d-flex justify-content-center">
                  <form action="{{ route('user.unitkerja.restore',$unitKerja->id) }}" method="POST" class="d-inline mr-1">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-success" title="Pulihkan"><i class="fas fa-trash-restore"></i></button>
                  </form>
                  <form action="{{ route('user.unitkerja.force-delete',$unitKerja->id) }}" method="POST" class="d-inline"
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
<script> $(function(){ $('#trashUk').DataTable({ pageLength:10, autoWidth:false }); }); </script>
@endpush
